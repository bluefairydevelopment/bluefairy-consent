// @ts-check
/**
 * BlueFairy Consent (free) browser E2E. Run on the AWS test rig:
 *   BFC_BASE=https://wp-testing.test npx playwright test -c tests/e2e
 *
 * The site must have BlueFairy Consent active with GA4 ID BFC_GA4 (default G-TEST12345).
 * Requests to googletagmanager.com are intercepted and recorded, never sent.
 *
 * "Cached page": every navigation after the first is answered from ONE copy
 * of the HTML fetched without cookies, exactly as a page cache would serve it,
 * so the banner decision is proved to happen in the browser.
 */
const { test, expect } = require( '@playwright/test' );

const BASE = process.env.BFC_BASE || 'https://wp-testing.test';
const GA4 = process.env.BFC_GA4 || 'G-TEST12345';
let cachedHtml = '';

test.beforeAll( async ( { request } ) => {
	const res = await request.get( BASE + '/', { headers: { Cookie: '' } } );
	expect( res.ok() ).toBeTruthy();
	cachedHtml = await res.text();
	expect( cachedHtml ).toContain( 'id="bfc-banner"' );
} );

/**
 * Serve the cached HTML for the home page and record tag requests.
 *
 * @param {import('@playwright/test').Page} page
 */
async function setup( page ) {
	const tags = [];
	await page.route( /googletagmanager\.com|google-analytics\.com/, ( route ) => {
		tags.push( route.request().url() );
		return route.fulfill( { status: 200, contentType: 'text/javascript', body: '/* stub */' } );
	} );
	await page.route( BASE + '/', ( route ) => route.fulfill( { status: 200, contentType: 'text/html; charset=utf-8', body: cachedHtml } ) );
	return tags;
}

async function consentCommands( page ) {
	return page.evaluate( () => ( window.dataLayer || [] )
		.filter( ( e ) => e && e[ 0 ] === 'consent' )
		.map( ( e ) => [ e[ 1 ], Object.assign( {}, e[ 2 ] ) ] ) );
}

async function stored( page ) {
	const c = ( await page.context().cookies() ).find( ( x ) => x.name === 'bfc_consent' );
	return c ? JSON.parse( decodeURIComponent( c.value ) ) : null;
}

test( 'banner shows with Accept and Reject of equal weight; nothing loads before consent', async ( { page } ) => {
	const tags = await setup( page );
	await page.goto( BASE + '/' );
	const banner = page.locator( '#bfc-banner' );
	await expect( banner ).toBeVisible();
	const accept = page.locator( '[data-bfc-action="accept"]' );
	const reject = page.locator( '[data-bfc-action="reject"]' );
	await expect( accept ).toBeVisible();
	await expect( reject ).toBeVisible();
	const style = async ( l ) => l.evaluate( ( el ) => {
		const s = getComputedStyle( el );
		return [ s.backgroundColor, s.color, s.fontSize, s.fontWeight, el.getBoundingClientRect().height ].join( '|' );
	} );
	expect( await style( accept ) ).toBe( await style( reject ) );
	const cmds = await consentCommands( page );
	expect( cmds[ 0 ] ).toEqual( [ 'default', expect.objectContaining( { analytics_storage: 'denied', ad_storage: 'denied', security_storage: 'granted' } ) ] );
	expect( cmds.length ).toBe( 1 );
	await page.waitForTimeout( 500 );
	expect( tags ).toEqual( [] );
	expect( await stored( page ) ).toBeNull();
} );

test( 'Accept grants analytics, stores the choice and loads GA4 only then', async ( { page } ) => {
	const tags = await setup( page );
	await page.goto( BASE + '/' );
	expect( tags ).toEqual( [] );
	await page.locator( '[data-bfc-action="accept"]' ).click();
	await expect( page.locator( '#bfc-banner' ) ).toBeHidden();
	await expect.poll( () => tags.length ).toBe( 1 );
	expect( tags[ 0 ] ).toContain( 'gtag/js?id=' + GA4 );
	const cmds = await consentCommands( page );
	expect( cmds[ cmds.length - 1 ] ).toEqual( [ 'update', expect.objectContaining( { analytics_storage: 'granted' } ) ] );
	const rec = await stored( page );
	expect( rec.c.analytics ).toBe( true );
	expect( rec.v ).toBe( 1 );
	expect( rec.id ).toMatch( /^[0-9a-f-]{36}$/ );

	// Returning visit, same cached HTML: no banner, consent applied in the first head script, GA loads.
	const tags2 = tags.length;
	await page.goto( BASE + '/' );
	await expect( page.locator( '#bfc-banner' ) ).toBeHidden();
	await expect( page.locator( '.bfc-reopen' ) ).toBeVisible();
	const again = await consentCommands( page );
	expect( again[ 1 ] ).toEqual( [ 'update', expect.objectContaining( { analytics_storage: 'granted' } ) ] );
	await expect.poll( () => tags.length ).toBe( tags2 + 1 );
} );

test( 'Reject leaves GA unloaded and analytics denied', async ( { page } ) => {
	const tags = await setup( page );
	await page.goto( BASE + '/' );
	await page.locator( '[data-bfc-action="reject"]' ).click();
	await expect( page.locator( '#bfc-banner' ) ).toBeHidden();
	await page.waitForTimeout( 500 );
	expect( tags ).toEqual( [] );
	const cmds = await consentCommands( page );
	expect( cmds[ cmds.length - 1 ] ).toEqual( [ 'update', expect.objectContaining( { analytics_storage: 'denied' } ) ] );
	expect( ( await stored( page ) ).c.analytics ).toBe( false );
	await page.goto( BASE + '/' );
	await page.waitForTimeout( 500 );
	expect( tags ).toEqual( [] );
} );

test( 'Global Privacy Control is treated as Reject without a banner', async ( { page } ) => {
	await page.addInitScript( () => Object.defineProperty( navigator, 'globalPrivacyControl', { get: () => true } ) );
	const tags = await setup( page );
	await page.goto( BASE + '/' );
	await page.waitForTimeout( 500 );
	await expect( page.locator( '#bfc-banner' ) ).toBeHidden();
	const rec = await stored( page );
	expect( rec.s ).toBe( 'gpc' );
	expect( rec.c.analytics ).toBe( false );
	expect( tags ).toEqual( [] );
} );

test( 'reopen button and #bfc-settings reopen the banner; withdrawing deletes analytics cookies', async ( { page } ) => {
	await setup( page );
	await page.goto( BASE + '/' );
	await page.locator( '[data-bfc-action="accept"]' ).click();
	const host = new URL( BASE ).hostname;
	await page.context().addCookies( [
		{ name: '_ga', value: 'GA1.1.1', domain: host, path: '/' },
		{ name: '_ga_TEST12345', value: 'GS1', domain: '.' + host, path: '/' },
		{ name: 'keepme', value: '1', domain: host, path: '/' },
	] );
	await page.locator( '.bfc-reopen' ).click();
	await expect( page.locator( '#bfc-banner' ) ).toBeVisible();
	await expect( page.locator( '#bfc-title' ) ).toBeFocused();
	await page.locator( '[data-bfc-action="reject"]' ).click();
	const names = ( await page.context().cookies() ).map( ( c ) => c.name );
	expect( names ).not.toContain( '_ga' );
	expect( names ).not.toContain( '_ga_TEST12345' );
	expect( names ).toContain( 'keepme' );
	expect( await page.evaluate( ( id ) => window[ 'ga-disable-' + id ], GA4 ) ).toBe( true );

	await page.goto( BASE + '/#bfc-settings' );
	await expect( page.locator( '#bfc-banner' ) ).toBeVisible();
} );

test( 'raising the policy version asks again', async ( { page } ) => {
	await setup( page );
	await page.goto( BASE + '/' );
	await page.locator( '[data-bfc-action="accept"]' ).click();
	const rec = await stored( page );
	rec.v = 0;
	const host = new URL( BASE ).hostname;
	await page.context().addCookies( [ { name: 'bfc_consent', value: encodeURIComponent( JSON.stringify( rec ) ), domain: host, path: '/' } ] );
	await page.goto( BASE + '/' );
	await expect( page.locator( '#bfc-banner' ) ).toBeVisible();
} );

test( 'GPC turned on after an earlier Accept wins; an opt-in made under GPC is kept', async ( { page } ) => {
	const tags = await setup( page );
	await page.goto( BASE + '/' );
	await page.locator( '[data-bfc-action="accept"]' ).click();
	expect( ( await stored( page ) ).g ).toBe( false );
	await page.addInitScript( () => Object.defineProperty( navigator, 'globalPrivacyControl', { get: () => true } ) );
	const before = tags.length;
	await page.goto( BASE + '/' );
	await expect.poll( async () => ( await stored( page ) ).s ).toBe( 'gpc' );
	expect( ( await stored( page ) ).c.analytics ).toBe( false );
	await page.waitForTimeout( 300 );
	expect( tags.length ).toBe( before );
	// Explicit opt-in while GPC is on is respected on the next visit.
	await page.locator( '.bfc-reopen' ).click();
	await page.locator( '[data-bfc-action="accept"]' ).click();
	expect( ( await stored( page ) ).g ).toBe( true );
	await page.goto( BASE + '/' );
	await page.waitForTimeout( 300 );
	expect( ( await stored( page ) ).s ).toBe( 'accept' );
} );

test( 'withdraw then re-grant on the same page re-enables GA4', async ( { page } ) => {
	await setup( page );
	await page.goto( BASE + '/' );
	await page.locator( '[data-bfc-action="accept"]' ).click();
	await page.locator( '.bfc-reopen' ).click();
	await page.locator( '[data-bfc-action="reject"]' ).click();
	expect( await page.evaluate( ( id ) => window[ 'ga-disable-' + id ], GA4 ) ).toBe( true );
	await page.locator( '.bfc-reopen' ).click();
	await page.locator( '[data-bfc-action="accept"]' ).click();
	expect( await page.evaluate( ( id ) => window[ 'ga-disable-' + id ], GA4 ) ).toBe( false );
} );
