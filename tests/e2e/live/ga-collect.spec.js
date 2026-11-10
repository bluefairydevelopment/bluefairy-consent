// Live-only check (not part of the gate suite): with NO request stubbing, no
// Google Analytics request leaves the browser before consent, and after Accept
// a real GA4 collect hit is sent and answered.
//   BFC_BASE=https://blog.bluefairydevelopment.com BFC_GA4=G-... npx playwright test -c tests/e2e/live
const { test, expect } = require( '@playwright/test' );
const BASE = process.env.BFC_BASE;
const GA4 = process.env.BFC_GA4;

test( 'real GA4 hit only after Accept', async ( { page } ) => {
	const ga = [];
	page.on( 'request', ( r ) => {
		if ( /googletagmanager\.com|google-analytics\.com/.test( r.url() ) ) {
			ga.push( r.url() );
		}
	} );
	const collects = [];
	page.on( 'response', ( r ) => {
		if ( /\/g\/collect/.test( r.url() ) ) {
			collects.push( r.status() );
		}
	} );
	await page.goto( BASE + '/?bfc=' + Date.now() );
	await expect( page.locator( '#bfc-banner' ) ).toBeVisible();
	await page.waitForTimeout( 2000 );
	expect( ga ).toEqual( [] );
	await page.locator( '[data-bfc-action="accept"]' ).click();
	await expect.poll( () => collects.length, { timeout: 15000 } ).toBeGreaterThan( 0 );
	expect( ga.some( ( u ) => u.includes( 'id=' + GA4 ) ) ).toBe( true );
	console.log( 'collect statuses', JSON.stringify( collects ), 'first GA url', ga[ 0 ] );
} );
