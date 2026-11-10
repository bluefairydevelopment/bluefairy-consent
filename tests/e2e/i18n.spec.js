// @ts-check
/**
 * WPMAP-324: the bundled translations reach the banner. Rig only: switches
 * the site language with wp-cli at WP_TEST_PATH and restores it.
 */
const { test, expect } = require( '@playwright/test' );
const { execFileSync } = require( 'child_process' );

const BASE = process.env.BFC_BASE || 'https://wp-testing.test';
const WP_PATH = process.env.WP_TEST_PATH || '';
const wp = ( ...a ) => execFileSync( 'wp', [ '--path=' + WP_PATH, ...a ], { encoding: 'utf8' } );
// The rig's mu-plugin fixture (tests/e2e/fixture-locale.php in the free plugin) reads bfc_test_locale.
const resetLocale = () => {
	try {
		wp( 'option', 'delete', 'bfc_test_locale' );
	} catch ( e ) {
		// Not set.
	}
};

test.beforeAll( () => {
	test.skip( ! WP_PATH || process.env.BFC_LIVE === '1', 'rig only (WP_TEST_PATH)' );
} );
test.afterAll( () => {
	if ( WP_PATH && process.env.BFC_LIVE !== '1' ) {
		resetLocale();
	}
} );

const CASES = [
	[ 'de_DE', 'Wir respektieren Ihre Privatsphäre', 'Alle akzeptieren', 'Alle ablehnen', 'Cookie-Liste', 'Speichert Ihre Cookie-Auswahl' ],
	[ 'fr_FR', 'Nous respectons votre vie privée', 'Tout accepter', 'Tout refuser', 'Liste des cookies', 'Mémorise votre choix concernant les cookies' ],
	[ 'es_ES', 'Respetamos tu privacidad', 'Aceptar todo', 'Rechazar todo', 'Lista de cookies', 'Recuerda tu elección sobre las cookies' ],
];

for ( const [ locale, title, accept, reject, list, purpose ] of CASES ) {
	test( `banner in ${ locale }`, async ( { page } ) => {
		wp( 'option', 'update', 'bfc_test_locale', locale );
		await page.route( /googletagmanager\.com/, ( r ) => r.fulfill( { status: 200, body: '' } ) );
		await page.goto( BASE + '/' );
		await expect( page.locator( '#bfc-title' ) ).toHaveText( title );
		await expect( page.locator( '[data-bfc-action="accept"]' ) ).toHaveText( accept );
		await expect( page.locator( '[data-bfc-action="reject"]' ) ).toHaveText( reject );
		await expect( page.locator( '.bfc-cookies summary' ) ).toHaveText( list );
		await expect( page.locator( '.bfc-cookie-table' ) ).toContainText( purpose );
	} );
}

test( 'English stays the default', async ( { page } ) => {
	resetLocale();
	await page.goto( BASE + '/' );
	await expect( page.locator( '#bfc-title' ) ).toHaveText( 'We respect your privacy' );
} );
