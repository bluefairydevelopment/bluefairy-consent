<?php
/**
 * Consent categories and the Google Consent Mode v2 signals each one grants.
 *
 * @package BlueFairy\Consent
 */

declare(strict_types=1);

namespace BlueFairy\Consent;

/**
 * The category list. The free plugin has Necessary and Analytics; add-ons
 * extend it through the bfconsent_categories filter.
 */
final class Categories {

	/**
	 * Every Consent Mode v2 signal the plugin manages.
	 */
	public const SIGNALS = [
		'ad_storage',
		'ad_user_data',
		'ad_personalization',
		'analytics_storage',
		'functionality_storage',
		'personalization_storage',
		'security_storage',
	];

	/**
	 * All categories, after the filter, with malformed entries dropped.
	 *
	 * Each entry: label, description, required (bool), signals (list of
	 * Consent Mode signals), cookies (list of JS regex sources matched against
	 * cookie names to delete when consent is withdrawn).
	 *
	 * @return array<string, array{label: string, description: string, required: bool, signals: list<string>, cookies: list<string>}>
	 */
	public static function all(): array {
		$categories = [
			'necessary' => [
				'label'       => __( 'Necessary', 'bluefairy-consent' ),
				'description' => __( 'Needed for the site to work, including remembering your cookie choice. Always on.', 'bluefairy-consent' ),
				'required'    => true,
				'signals'     => [ 'security_storage' ],
				'cookies'     => [],
			],
			'analytics' => [
				'label'       => __( 'Analytics', 'bluefairy-consent' ),
				'description' => __( 'Help us understand how visitors use the site, for example Google Analytics.', 'bluefairy-consent' ),
				'required'    => false,
				'signals'     => [ 'analytics_storage' ],
				'cookies'     => [ '^_ga', '^_gid$', '^_gat' ],
			],
		];

		/**
		 * Filter the consent categories.
		 *
		 * @param array<string, array<string, mixed>> $categories Categories keyed by slug.
		 */
		$filtered = apply_filters( 'bfconsent_categories', $categories );
		if ( ! is_array( $filtered ) ) {
			return $categories;
		}

		$clean = [];
		foreach ( $filtered as $slug => $category ) {
			if ( ! is_string( $slug ) || 1 !== preg_match( '/^[a-z][a-z0-9_]{0,31}$/', $slug ) || ! is_array( $category ) ) {
				continue;
			}
			$clean[ $slug ] = [
				'label'       => (string) ( $category['label'] ?? $slug ),
				'description' => (string) ( $category['description'] ?? '' ),
				'required'    => ! empty( $category['required'] ),
				'signals'     => array_values( array_intersect( (array) ( $category['signals'] ?? [] ), self::SIGNALS ) ),
				'cookies'     => array_values( array_map( 'strval', (array) ( $category['cookies'] ?? [] ) ) ),
			];
		}
		return $clean;
	}
}
