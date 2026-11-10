<?php
/**
 * Plugin Name:       BlueFairy Consent
 * Plugin URI:        https://wordpress.bluefairydevelopment.com/product/bluefairy-consent/
 * Description:       Cookie consent banner with Accept and Reject given equal weight, Google Consent Mode v2, Global Privacy Control, and Google Analytics 4 / Tag Manager loaded only after consent. Page-cache safe: the decision is made in the browser. No CDN, no account.
 * Version:           1.2.0
 * Requires at least: 6.6
 * Requires PHP:      8.1
 * Author:            Blue Fairy Development
 * Author URI:        https://bluefairydevelopment.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       bluefairy-consent
 *
 * @package BlueFairy\Consent
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BFCONSENT_FILE', __FILE__ );
define( 'BFCONSENT_DIR', plugin_dir_path( __FILE__ ) );
define( 'BFCONSENT_URL', plugin_dir_url( __FILE__ ) );
define( 'BFCONSENT_VERSION', '1.2.0' );

spl_autoload_register(
	static function ( string $class_name ): void {
		$prefix = 'BlueFairy\\Consent\\';
		if ( ! str_starts_with( $class_name, $prefix ) ) {
			return;
		}
		$file = __DIR__ . '/includes/' . str_replace( '\\', '/', substr( $class_name, strlen( $prefix ) ) ) . '.php';
		if ( is_readable( $file ) ) {
			require $file;
		}
	}
);

add_action( 'plugins_loaded', [ BlueFairy\Consent\Plugin::class, 'boot' ] );
