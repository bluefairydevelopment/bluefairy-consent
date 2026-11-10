<?php
/**
 * Rig-only E2E fixture (WPMAP-324): lets the i18n specs switch the site
 * language without installing core language packs (WPLANG only accepts an
 * installed language). Copied to wp-content/mu-plugins for the run and
 * removed afterwards. Never shipped.
 *
 * @package BlueFairy\Consent
 */

// phpcs:ignoreFile
add_filter(
	'locale',
	static function ( $locale ) {
		$test = get_option( 'bfc_test_locale' );
		return is_string( $test ) && '' !== $test ? $test : $locale;
	}
);
