<?php
/**
 * WooCommerce order attribution off-switch.
 *
 * WooCommerce's Order Attribution feature loads the Sourcebuster script, which
 * sets sbjs_* cookies on the first page view, before the visitor has chosen
 * anything. When the site owner ticks "Turn off WooCommerce order attribution",
 * this answers WooCommerce's own feature check with "no" for every request:
 * WooCommerce then registers none of the feature (no script, no cookies, no
 * hidden checkout fields), for every visitor, including those who accept.
 * This plugin never writes WooCommerce's option; unticking the box hands the
 * decision back to WooCommerce's own Features setting.
 *
 * @package BlueFairy\Consent
 */

declare(strict_types=1);

namespace BlueFairy\Consent;

/**
 * Turns WooCommerce order attribution off while the setting is on.
 */
final class OrderAttribution {

	public const SETTING = 'disable_wc_order_attribution';

	/**
	 * The option WooCommerce's FeaturesController::feature_is_enabled() reads
	 * for the order_attribution feature.
	 */
	public const WC_OPTION = 'woocommerce_feature_order_attribution_enabled';

	/**
	 * Register the filter. Runs on plugins_loaded, before WooCommerce checks
	 * the feature on init. Does nothing unless WooCommerce is active and the
	 * setting is on.
	 */
	public static function register(): void {
		if ( self::woocommerce_active() && self::switched_on() ) {
			add_filter( 'pre_option_' . self::WC_OPTION, [ self::class, 'feature_off' ] );
		}
	}

	/**
	 * Whether the saved setting is on. Reads the stored option directly:
	 * Settings::all() builds translated defaults, and translations must not
	 * load before init.
	 */
	public static function switched_on(): bool {
		$saved = get_option( Settings::OPTION, [] );
		return is_array( $saved ) && ! empty( $saved[ self::SETTING ] );
	}

	/**
	 * The value WooCommerce reads for the feature option: "no" (off).
	 */
	public static function feature_off(): string {
		return 'no';
	}

	/**
	 * Whether WooCommerce is loaded.
	 */
	public static function woocommerce_active(): bool {
		/**
		 * Filter whether WooCommerce counts as active.
		 *
		 * @param bool $active True when the WooCommerce class is loaded.
		 */
		return (bool) apply_filters( 'bfconsent_woocommerce_active', class_exists( 'WooCommerce', false ) );
	}
}
