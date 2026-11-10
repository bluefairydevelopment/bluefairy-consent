// @ts-check
/**
 * WooCommerce order attribution off-switch, on a rig site with WooCommerce
 * active. Run on the AWS test rig:
 *   BFC_BASE=https://wp-testing.test WP_TEST_PATH=~/Sites/wp-testing npx playwright test -c tests/e2e woo-attribution
 *
 * Setting off (the default): WooCommerce's Sourcebuster script sets sbjs_*
 * cookies on the first page view and the order carries attribution meta.
 * Setting on: no sourcebuster script, no sbjs_* cookie, no attribution meta.
 * Both ways a guest checkout reaches the order-received page.
 *
 * The settings are written through the plugin's own Settings::sanitize().
 * The test product, checkout page and orders are deleted afterwards.
 */
const { test, expect } = require( '@playwright/test' );
const { execFileSync } = require( 'child_process' );
const fs = require( 'fs' );
const os = require( 'os' );
const path = require( 'path' );

const BASE = process.env.BFC_BASE || 'https://wp-testing.test';
const WP_PATH = process.env.WP_TEST_PATH || path.join( os.homedir(), 'Sites', 'wp-testing' );

/** Run PHP inside WordPress; `$in` is the JSON payload. */
function wpEval( php, payload = null ) {
	const file = path.join( os.tmpdir(), `bfc-woo-${ process.pid }-${ Date.now() }.php` );
	fs.writeFileSync( file, `<?php\n$in = json_decode(base64_decode("${ Buffer.from( JSON.stringify( payload ) ).toString( 'base64' ) }"), true); ${ php }\n` );
	try {
		return execFileSync( 'wp', [ `--path=${ WP_PATH }`, 'eval-file', file ], { encoding: 'utf8' } ).trim();
	} finally {
		fs.unlinkSync( file );
	}
}

function setSwitch( on ) {
	const out = wpEval( 'update_option("bfconsent_settings", BlueFairy\\Consent\\Settings::sanitize(array_merge(BlueFairy\\Consent\\Settings::all(), ["disable_wc_order_attribution" => $in ? "1" : ""]))); echo wp_json_encode(BlueFairy\\Consent\\Settings::get("disable_wc_order_attribution"));', on );
	expect( out ).toBe( on ? 'true' : 'false' );
}

let ids = { product: 0, page: 0 };

test.describe.configure( { mode: 'serial' } );
test.use( { storageState: { cookies: [], origins: [] } } );

test.beforeAll( () => {
	expect( wpEval( 'echo class_exists("WooCommerce") ? "yes" : "no";' ), 'WooCommerce must be active on the rig site' ).toBe( 'yes' );
	ids = JSON.parse( wpEval( `
		$p = new WC_Product_Simple();
		$p->set_name("BFC attribution test item"); $p->set_status("publish");
		$p->set_regular_price("0"); $p->set_virtual(true); $p->save();
		$page = wp_insert_post(["post_type" => "page", "post_status" => "publish", "post_title" => "BFC attribution checkout", "post_content" => "[woocommerce_checkout]"]);
		echo wp_json_encode(["product" => $p->get_id(), "page" => $page]);` ) );
} );

test.afterAll( () => {
	setSwitch( false );
	wpEval( `
		foreach ( wc_get_orders( [ "limit" => -1, "status" => "any" ] ) as $o ) {
			foreach ( $o->get_items() as $i ) { if ( (int) $i->get_product_id() === (int) $in["product"] ) { $o->delete( true ); break; } }
		}
		wp_delete_post( (int) $in["page"], true ); wp_delete_post( (int) $in["product"], true );`, ids );
} );

/**
 * Visit the home page as a new visitor, then buy the free product as a guest.
 *
 * @param {import('@playwright/test').Page} page
 */
async function visitAndCheckout( page ) {
	const scripts = [];
	page.on( 'request', ( r ) => {
		if ( /sourcebuster|order-attribution/.test( r.url() ) ) {
			scripts.push( r.url() );
		}
	} );
	await page.goto( BASE + '/', { waitUntil: 'load' } );
	await page.waitForTimeout( 1000 );
	const firstView = ( await page.context().cookies() ).map( ( c ) => c.name ).filter( ( n ) => n.startsWith( 'sbjs_' ) );

	await page.goto( `${ BASE }/?add-to-cart=${ ids.product }&quantity=1`, { waitUntil: 'domcontentloaded' } );
	await page.goto( `${ BASE }/?page_id=${ ids.page }`, { waitUntil: 'load' } );
	const attributionFields = await page.locator( 'input[name^="wc_order_attribution_"]' ).count();
	await page.locator( '#billing_first_name' ).fill( 'Attribution' );
	await page.locator( '#billing_last_name' ).fill( 'Test' );
	await page.locator( '#billing_email' ).fill( 'attribution-test@example.invalid' );
	for ( const [ sel, val ] of [ [ '#billing_address_1', '1 Test Street' ], [ '#billing_city', 'Austin' ], [ '#billing_postcode', '78701' ], [ '#billing_phone', '5125550100' ] ] ) {
		if ( await page.locator( sel ).count() ) {
			await page.locator( sel ).fill( val );
		}
	}
	if ( await page.locator( 'select#billing_country' ).count() ) {
		await page.locator( 'select#billing_country' ).selectOption( 'US' );
	}
	if ( await page.locator( 'select#billing_state' ).count() ) {
		await page.locator( 'select#billing_state' ).selectOption( 'TX' );
	}
	const terms = page.locator( '#terms' );
	if ( await terms.count() ) {
		await terms.check();
	}
	await page.locator( '#place_order' ).click();
	await page.waitForURL( /order-received/, { timeout: 30000 } );
	const orderId = Number( ( page.url().match( /order-received[=/](\d+)/ ) || [] )[ 1 ] );
	expect( orderId, 'order id in the order-received URL' ).toBeGreaterThan( 0 );
	const meta = JSON.parse( wpEval( 'echo wp_json_encode(wc_get_order((int) $in)->get_meta("_wc_order_attribution_source_type"));', orderId ) );
	return { firstView, scripts, attributionFields, meta };
}

test( 'W-1 setting off: WooCommerce attribution sets sbjs_* on the first view; checkout completes with attribution', async ( { page } ) => {
	setSwitch( false );
	const r = await visitAndCheckout( page );
	expect( r.scripts.some( ( u ) => u.includes( 'sourcebuster' ) ), 'sourcebuster script loaded' ).toBe( true );
	expect( r.firstView.length, 'sbjs_* cookies after the first page view' ).toBeGreaterThan( 0 );
	expect( r.attributionFields ).toBeGreaterThan( 0 );
	expect( r.meta ).not.toBe( '' );
} );

test( 'W-2 setting on: no sourcebuster script, no sbjs_* cookie; checkout still reaches the order page', async ( { page } ) => {
	setSwitch( true );
	const r = await visitAndCheckout( page );
	expect( r.scripts ).toEqual( [] );
	expect( r.firstView ).toEqual( [] );
	expect( ( await page.context().cookies() ).filter( ( c ) => c.name.startsWith( 'sbjs_' ) ) ).toEqual( [] );
	expect( r.attributionFields ).toBe( 0 );
	expect( r.meta ).toBe( '' );
	expect( wpEval( 'echo get_option("woocommerce_feature_order_attribution_enabled", "unset") === "no" ? "no" : "other";' ), 'feature reads as off through the filter' ).toBe( 'no' );
} );
