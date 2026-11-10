<?php
/**
 * Settings screen: Settings > Consent.
 *
 * @package BlueFairy\Consent
 */

declare(strict_types=1);

namespace BlueFairy\Consent;

/**
 * Admin page.
 */
final class Admin {

	public const PAGE = 'bluefairy-consent';

	/**
	 * Register admin hooks.
	 */
	public static function register(): void {
		add_action( 'admin_init', [ self::class, 'register_setting' ] );
		add_action( 'admin_menu', [ self::class, 'menu' ] );
	}

	/**
	 * Register the option with its sanitiser.
	 */
	public static function register_setting(): void {
		register_setting(
			'bfconsent',
			Settings::OPTION,
			[
				'type'              => 'array',
				'sanitize_callback' => [ Settings::class, 'sanitize' ],
				'default'           => Settings::defaults(),
			]
		);
	}

	/**
	 * Add the menu entry.
	 */
	public static function menu(): void {
		add_options_page(
			__( 'Consent', 'bluefairy-consent' ),
			__( 'Consent', 'bluefairy-consent' ),
			'manage_options',
			self::PAGE,
			[ self::class, 'render' ]
		);
	}

	/**
	 * Render the settings page.
	 */
	public static function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$s    = Settings::all();
		$name = Settings::OPTION;
		echo '<div class="wrap"><h1>' . esc_html__( 'Consent', 'bluefairy-consent' ) . '</h1>';
		echo '<p>' . esc_html__( 'The default wording is a template. Review it, and your privacy policy, with your own legal adviser; it is not legal advice.', 'bluefairy-consent' ) . '</p>';
		echo '<form method="post" action="options.php">';
		settings_fields( 'bfconsent' );
		echo '<table class="form-table" role="presentation">';
		foreach ( self::text_fields() as $key => $label ) {
			self::row( $label, $key, '<input type="text" class="regular-text" id="bfc-' . esc_attr( $key ) . '" name="' . esc_attr( $name . '[' . $key . ']' ) . '" value="' . esc_attr( (string) $s[ $key ] ) . '">' );
		}
		self::row( __( 'Message', 'bluefairy-consent' ), 'message', '<textarea class="large-text" rows="4" id="bfc-message" name="' . esc_attr( $name . '[message]' ) . '">' . esc_textarea( (string) $s['message'] ) . '</textarea>' );
		self::row( __( 'Privacy policy URL', 'bluefairy-consent' ), 'privacy_url', '<input type="url" class="regular-text" id="bfc-privacy_url" name="' . esc_attr( $name . '[privacy_url]' ) . '" value="' . esc_attr( (string) $s['privacy_url'] ) . '">' );
		$options = '';
		foreach ( [ 'bottom-left', 'bottom-right', 'bottom' ] as $pos ) {
			$options .= '<option value="' . esc_attr( $pos ) . '"' . selected( $s['position'], $pos, false ) . '>' . esc_html( $pos ) . '</option>';
		}
		self::row( __( 'Position', 'bluefairy-consent' ), 'position', '<select id="bfc-position" name="' . esc_attr( $name . '[position]' ) . '">' . $options . '</select>' );
		self::row( __( 'Google Analytics 4 ID', 'bluefairy-consent' ), 'ga4_id', '<input type="text" id="bfc-ga4_id" placeholder="G-XXXXXXX" name="' . esc_attr( $name . '[ga4_id]' ) . '" value="' . esc_attr( (string) $s['ga4_id'] ) . '"> <span class="description">' . esc_html__( 'Loaded only after the visitor allows Analytics.', 'bluefairy-consent' ) . '</span>' );
		self::row( __( 'Tag Manager ID', 'bluefairy-consent' ), 'gtm_id', '<input type="text" id="bfc-gtm_id" placeholder="GTM-XXXXXX" name="' . esc_attr( $name . '[gtm_id]' ) . '" value="' . esc_attr( (string) $s['gtm_id'] ) . '"> <span class="description">' . esc_html__( 'Loaded only after the visitor allows at least one optional category.', 'bluefairy-consent' ) . '</span>' );
		self::row( __( 'Remember choice for (days)', 'bluefairy-consent' ), 'cookie_days', '<input type="number" min="1" max="395" id="bfc-cookie_days" name="' . esc_attr( $name . '[cookie_days]' ) . '" value="' . esc_attr( (string) $s['cookie_days'] ) . '">' );
		self::row( __( 'Policy version', 'bluefairy-consent' ), 'policy_version', '<input type="number" min="1" id="bfc-policy_version" name="' . esc_attr( $name . '[policy_version]' ) . '" value="' . esc_attr( (string) $s['policy_version'] ) . '"> <span class="description">' . esc_html__( 'Raise it to ask every visitor again.', 'bluefairy-consent' ) . '</span>' );
		self::row( __( 'Accent colour', 'bluefairy-consent' ), 'accent_color', '<input type="text" id="bfc-accent_color" name="' . esc_attr( $name . '[accent_color]' ) . '" value="' . esc_attr( (string) $s['accent_color'] ) . '">' );
		self::row( __( 'Floating settings button', 'bluefairy-consent' ), 'reopen_button', '<label><input type="checkbox" id="bfc-reopen_button" name="' . esc_attr( $name . '[reopen_button]' ) . '" value="1"' . checked( (bool) $s['reopen_button'], true, false ) . '> ' . esc_html__( 'Show a small button that reopens the banner. You can also use the [bluefairy_consent_settings] shortcode or a link to #bfc-settings.', 'bluefairy-consent' ) . '</label>' );
		self::row( __( 'Global Privacy Control', 'bluefairy-consent' ), 'respect_gpc', '<label><input type="checkbox" id="bfc-respect_gpc" name="' . esc_attr( $name . '[respect_gpc]' ) . '" value="1"' . checked( (bool) $s['respect_gpc'], true, false ) . '> ' . esc_html__( 'Treat a browser GPC signal as Reject All.', 'bluefairy-consent' ) . '</label>' );
		self::row( __( 'Cookie list', 'bluefairy-consent' ), 'cookie_list', '<textarea class="large-text code" rows="6" id="bfc-cookie_list" name="' . esc_attr( $name . '[cookie_list]' ) . '">' . esc_textarea( (string) $s['cookie_list'] ) . '</textarea><p class="description">' . esc_html__( 'Every cookie your site sets, one per line: name | category | provider | purpose | duration. Shown under Cookie list in the banner and by the [bluefairy_consent_cookie_list] shortcode. The consent cookie is listed automatically. Keep this list complete: the banner tells visitors it lists all cookies.', 'bluefairy-consent' ) . '</p>' );
		/**
		 * Extra rows on the settings table (Pro).
		 */
		do_action( 'bfconsent_settings_rows' );
		echo '</table>';
		submit_button();
		echo '</form></div>';
	}

	/**
	 * Single-line text settings and their labels.
	 *
	 * @return array<string, string>
	 */
	public static function text_fields(): array {
		return [
			'title'          => __( 'Banner title', 'bluefairy-consent' ),
			'accept_label'   => __( 'Accept button', 'bluefairy-consent' ),
			'reject_label'   => __( 'Reject button', 'bluefairy-consent' ),
			'settings_label' => __( 'Settings link text', 'bluefairy-consent' ),
		];
	}

	/**
	 * Print one form-table row. $control is already escaped.
	 *
	 * @param string $label   Row label.
	 * @param string $key     Setting key.
	 * @param string $control Escaped control HTML.
	 */
	public static function row( string $label, string $key, string $control ): void {
		echo '<tr><th scope="row"><label for="bfc-' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label></th><td>' . $control . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $control escaped by caller.
	}
}
