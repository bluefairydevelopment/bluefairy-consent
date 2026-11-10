<?php
/**
 * Settings: one option, defaults, sanitising.
 *
 * @package BlueFairy\Consent
 */

declare(strict_types=1);

namespace BlueFairy\Consent;

/**
 * Reads and sanitises the bfconsent_settings option.
 */
final class Settings {

	public const OPTION = 'bfconsent_settings';

	/**
	 * Default settings. Wording is a template for the site owner to review with
	 * their own legal adviser; it is not legal advice.
	 *
	 * @return array<string, string|int|bool>
	 */
	public static function defaults(): array {
		return [
			'title'          => __( 'We respect your privacy', 'bluefairy-consent' ),
			'message'        => __( 'We use cookies to run this site and, with your permission, to measure how it is used. Choose Accept All to allow analytics cookies or Reject All to decline everything that is not essential. You can change your mind at any time from Cookie settings.', 'bluefairy-consent' ),
			'accept_label'   => __( 'Accept All', 'bluefairy-consent' ),
			'reject_label'   => __( 'Reject All', 'bluefairy-consent' ),
			'settings_label' => __( 'Cookie settings', 'bluefairy-consent' ),
			'privacy_url'    => '',
			'position'       => 'bottom-left',
			'ga4_id'         => '',
			'gtm_id'         => '',
			'cookie_days'    => 365,
			'policy_version' => 1,
			'reopen_button'  => true,
			'respect_gpc'    => true,
			'accent_color'   => '#1863dc',
		];
	}

	/**
	 * Saved settings merged over the defaults.
	 *
	 * @return array<string, string|int|bool>
	 */
	public static function all(): array {
		$saved = get_option( self::OPTION, [] );
		if ( ! is_array( $saved ) ) {
			$saved = [];
		}
		return array_merge( self::defaults(), array_intersect_key( $saved, self::defaults() ) );
	}

	/**
	 * One setting.
	 *
	 * @param string $key Setting key.
	 */
	public static function get( string $key ): string|int|bool {
		$all = self::all();
		return $all[ $key ] ?? '';
	}

	/**
	 * Sanitise a submitted settings array. Unknown keys are dropped.
	 *
	 * @param mixed $input Raw input.
	 * @return array<string, string|int|bool>
	 */
	public static function sanitize( mixed $input ): array {
		$input = is_array( $input ) ? $input : [];
		$out   = self::defaults();
		foreach ( [ 'title', 'accept_label', 'reject_label', 'settings_label' ] as $key ) {
			if ( isset( $input[ $key ] ) && '' !== trim( (string) $input[ $key ] ) ) {
				$out[ $key ] = sanitize_text_field( (string) $input[ $key ] );
			}
		}
		if ( isset( $input['message'] ) && '' !== trim( (string) $input['message'] ) ) {
			$out['message'] = wp_kses( (string) $input['message'], self::allowed_html() );
		}
		$out['privacy_url']    = isset( $input['privacy_url'] ) ? esc_url_raw( (string) $input['privacy_url'] ) : '';
		$out['position']       = in_array( $input['position'] ?? '', [ 'bottom-left', 'bottom-right', 'bottom' ], true ) ? (string) $input['position'] : 'bottom-left';
		$out['ga4_id']         = self::match_id( $input['ga4_id'] ?? '', '/^G-[A-Z0-9]{4,16}$/' );
		$out['gtm_id']         = self::match_id( $input['gtm_id'] ?? '', '/^GTM-[A-Z0-9]{4,12}$/' );
		$out['cookie_days']    = max( 1, min( 395, (int) ( $input['cookie_days'] ?? 365 ) ) );
		$out['policy_version'] = max( 1, (int) ( $input['policy_version'] ?? 1 ) );
		$out['reopen_button']  = ! empty( $input['reopen_button'] );
		$out['respect_gpc']    = ! empty( $input['respect_gpc'] );
		$color                 = sanitize_hex_color( (string) ( $input['accent_color'] ?? '' ) );
		$out['accent_color']   = $color ? $color : '#1863dc';
		return $out;
	}

	/**
	 * Tags allowed in the banner message.
	 *
	 * @return array<string, array<string, bool>>
	 */
	public static function allowed_html(): array {
		return [
			'a'      => [
				'href'   => true,
				'target' => true,
				'rel'    => true,
			],
			'b'      => [],
			'strong' => [],
			'em'     => [],
			'br'     => [],
		];
	}

	/**
	 * Upper-cased ID when it matches the pattern, else empty.
	 *
	 * @param mixed  $value   Raw value.
	 * @param string $pattern Regex.
	 */
	private static function match_id( mixed $value, string $pattern ): string {
		$value = strtoupper( trim( (string) $value ) );
		return 1 === preg_match( $pattern, $value ) ? $value : '';
	}
}
