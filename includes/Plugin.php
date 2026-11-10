<?php
/**
 * Boot: wires the front end, the shortcode and the settings screen.
 *
 * @package BlueFairy\Consent
 */

declare(strict_types=1);

namespace BlueFairy\Consent;

/**
 * Entry point.
 */
final class Plugin {

	/**
	 * Register hooks. Fires bfconsent_loaded so add-ons can attach.
	 */
	public static function boot(): void {
		Frontend::register();
		Admin::register();
		OrderAttribution::register();
		add_action( 'init', [ self::class, 'load_textdomain' ] );
		/**
		 * Fires once the free plugin has registered its hooks.
		 */
		do_action( 'bfconsent_loaded' );
	}

	/**
	 * Load the bundled translations from languages/. A translation installed
	 * in wp-content/languages/plugins takes precedence (WordPress loads it
	 * first).
	 */
	public static function load_textdomain(): void {
		load_plugin_textdomain( 'bluefairy-consent', false, dirname( plugin_basename( BFCONSENT_FILE ) ) . '/languages' );
	}
}
