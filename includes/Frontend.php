<?php
/**
 * Front end: Consent Mode defaults in <head>, the banner markup, the script,
 * and the [bluefairy_consent_settings] shortcode.
 *
 * Nothing here reads the visitor's cookie. Every visitor receives the same
 * HTML, so a page cache (Cloudflare, a caching plugin) can serve it to anyone;
 * the browser script decides whether to show the banner and what to load.
 *
 * @package BlueFairy\Consent
 */

declare(strict_types=1);

namespace BlueFairy\Consent;

/**
 * Front-end output.
 */
final class Frontend {

	public const COOKIE = 'bfc_consent';

	/**
	 * Register front-end hooks.
	 */
	public static function register(): void {
		add_action( 'wp_head', [ self::class, 'print_head' ], 1 );
		add_action( 'wp_enqueue_scripts', [ self::class, 'enqueue' ] );
		add_action( 'wp_footer', [ self::class, 'print_banner' ], 5 );
		add_shortcode( 'bluefairy_consent_settings', [ self::class, 'shortcode' ] );
		add_shortcode( 'bluefairy_consent_cookie_list', [ self::class, 'cookie_list_shortcode' ] );
	}

	/**
	 * The configuration handed to the browser.
	 *
	 * @return array<string, mixed>
	 */
	public static function config(): array {
		$settings   = Settings::all();
		$categories = [];
		foreach ( Categories::all() as $slug => $category ) {
			$categories[ $slug ] = [
				'required' => $category['required'],
				'signals'  => $category['signals'],
				'cookies'  => $category['cookies'],
			];
		}
		$config = [
			'cookie'     => self::COOKIE,
			'days'       => (int) $settings['cookie_days'],
			'version'    => (int) $settings['policy_version'],
			'gpc'        => (bool) $settings['respect_gpc'],
			'ga4'        => (string) $settings['ga4_id'],
			'gtm'        => (string) $settings['gtm_id'],
			'categories' => $categories,
		];
		/**
		 * Filter the browser configuration.
		 *
		 * @param array<string, mixed> $config Configuration.
		 */
		$filtered = apply_filters( 'bfconsent_config', $config );
		return is_array( $filtered ) ? $filtered : $config;
	}

	/**
	 * Print the config and the Consent Mode default as the first scripts in
	 * <head>, before any tag can read consent state.
	 */
	public static function print_head(): void {
		$json = wp_json_encode( self::config() );
		$mode = (string) file_get_contents( BFCONSENT_DIR . 'assets/js/consent-mode.js' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local plugin file.
		wp_print_inline_script_tag(
			'window.bfconsentConfig=' . $json . ";\n" . $mode,
			[ 'id' => 'bfconsent-mode' ]
		);
	}

	/**
	 * Enqueue the banner script and stylesheet.
	 */
	public static function enqueue(): void {
		wp_enqueue_style( 'bfconsent', BFCONSENT_URL . 'assets/css/consent.css', [], BFCONSENT_VERSION );
		wp_add_inline_style( 'bfconsent', '.bfc{--bfc-accent:' . (string) Settings::get( 'accent_color' ) . '}' );
		wp_enqueue_script(
			'bfconsent',
			BFCONSENT_URL . 'assets/js/consent.js',
			[],
			BFCONSENT_VERSION,
			[
				'in_footer' => true,
				'strategy'  => 'defer',
			]
		);
	}

	/**
	 * The banner markup. Hidden until the script decides it is needed.
	 */
	public static function print_banner(): void {
		echo self::banner_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped while built.
	}

	/**
	 * Build the banner markup.
	 */
	public static function banner_html(): string {
		$s        = Settings::all();
		$position = sanitize_html_class( (string) $s['position'] );
		$privacy  = '';
		if ( '' !== $s['privacy_url'] ) {
			$privacy = ' <a class="bfc-privacy" href="' . esc_url( (string) $s['privacy_url'] ) . '">' . esc_html__( 'Privacy policy', 'bluefairy-consent' ) . '</a>';
		}

		ob_start();
		/**
		 * Extra content inside the banner, after the message (Pro: category preferences).
		 */
		do_action( 'bfconsent_banner_body' );
		$body = (string) ob_get_clean();

		ob_start();
		/**
		 * Extra buttons between Reject and Accept (Pro: Customize, Save).
		 */
		do_action( 'bfconsent_banner_actions' );
		$actions = (string) ob_get_clean();

		$html  = '<div id="bfc-banner" class="bfc bfc--' . esc_attr( $position ) . '" role="region" aria-labelledby="bfc-title" hidden>';
		$html .= '<h2 id="bfc-title" class="bfc-title" tabindex="-1">' . esc_html( (string) $s['title'] ) . '</h2>';
		$html .= '<p class="bfc-message">' . wp_kses( (string) $s['message'], Settings::allowed_html() ) . $privacy . '</p>';
		$html .= '<details class="bfc-cookies"><summary>' . esc_html__( 'Cookie list', 'bluefairy-consent' ) . '</summary>' . self::cookie_table() . '</details>';
		$html .= $body;
		$html .= '<div class="bfc-actions">';
		$html .= '<button type="button" class="bfc-btn" data-bfc-action="reject">' . esc_html( (string) $s['reject_label'] ) . '</button>';
		$html .= $actions;
		$html .= '<button type="button" class="bfc-btn" data-bfc-action="accept">' . esc_html( (string) $s['accept_label'] ) . '</button>';
		$html .= '</div></div>';

		if ( $s['reopen_button'] ) {
			$html .= '<button type="button" class="bfc bfc-reopen bfc--' . esc_attr( $position ) . '" data-bfc-open hidden aria-label="' . esc_attr( (string) $s['settings_label'] ) . '" title="' . esc_attr( (string) $s['settings_label'] ) . '">'
				. '<svg aria-hidden="true" focusable="false" width="22" height="22" viewBox="0 0 24 24"><path fill="currentColor" d="M12 2a10 10 0 1 0 10 10 4 4 0 0 1-5-5 4 4 0 0 1-5-5zm-3.5 7a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3zm2 6a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3zm5 0a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/></svg>'
				. '</button>';
		}
		return $html;
	}

	/**
	 * [bluefairy_consent_settings label="..."] — a button that reopens the banner.
	 *
	 * @param mixed $atts Shortcode attributes.
	 */
	public static function shortcode( mixed $atts ): string {
		$atts = shortcode_atts( [ 'label' => (string) Settings::get( 'settings_label' ) ], is_array( $atts ) ? $atts : [], 'bluefairy_consent_settings' );
		return '<button type="button" class="bfc-settings-link" data-bfc-open>' . esc_html( (string) $atts['label'] ) . '</button>';
	}

	/**
	 * Every cookie the site declares, as rows: the consent cookie first (with
	 * the configured lifetime), then the cookie list setting, keeping only
	 * categories that exist (an add-on's category disappears with it).
	 *
	 * @return list<array{name: string, category: string, provider: string, purpose: string, duration: string}>
	 */
	public static function cookies(): array {
		$days       = (int) Settings::get( 'cookie_days' );
		$categories = Categories::all();
		$rows       = [
			[
				'name'     => self::COOKIE,
				'category' => 'necessary',
				'provider' => __( 'This site', 'bluefairy-consent' ),
				'purpose'  => __( 'Remembers your cookie choice', 'bluefairy-consent' ),
				/* translators: %d: number of days */
				'duration' => sprintf( _n( '%d day', '%d days', $days, 'bluefairy-consent' ), $days ),
			],
		];
		foreach ( Settings::parse_cookies( (string) Settings::get( 'cookie_list' ) ) as $cookie ) {
			if ( isset( $categories[ $cookie['category'] ] ) ) {
				$rows[] = $cookie;
			}
		}
		return $rows;
	}

	/**
	 * The cookie table (banner and shortcode).
	 */
	public static function cookie_table(): string {
		$categories = Categories::all();
		$html       = '<table class="bfc-cookie-table"><thead><tr>';
		foreach ( [ __( 'Cookie', 'bluefairy-consent' ), __( 'Category', 'bluefairy-consent' ), __( 'Provider', 'bluefairy-consent' ), __( 'Purpose', 'bluefairy-consent' ), __( 'Duration', 'bluefairy-consent' ) ] as $head ) {
			$html .= '<th scope="col">' . esc_html( $head ) . '</th>';
		}
		$html .= '</tr></thead><tbody>';
		foreach ( self::cookies() as $c ) {
			$label = $categories[ $c['category'] ]['label'] ?? $c['category'];
			$html .= '<tr><td><code>' . esc_html( $c['name'] ) . '</code></td><td>' . esc_html( $label ) . '</td><td>' . esc_html( $c['provider'] ) . '</td><td>' . esc_html( $c['purpose'] ) . '</td><td>' . esc_html( $c['duration'] ) . '</td></tr>';
		}
		return $html . '</tbody></table>';
	}

	/**
	 * [bluefairy_consent_cookie_list] — the cookie table, for a privacy policy page.
	 */
	public static function cookie_list_shortcode(): string {
		return '<div class="bfc-cookie-list">' . self::cookie_table() . '</div>';
	}
}
