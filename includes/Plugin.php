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
		/**
		 * Fires once the free plugin has registered its hooks.
		 */
		do_action( 'bfconsent_loaded' );
	}
}
