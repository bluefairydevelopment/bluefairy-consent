<?php
/**
 * Front-end output, categories, boot and uninstall.
 *
 * @package BlueFairy\Consent
 */

declare(strict_types=1);

namespace BlueFairy\Consent\Tests\Unit;

use BlueFairy\Consent\Admin;
use BlueFairy\Consent\Categories;
use BlueFairy\Consent\Frontend;
use BlueFairy\Consent\Plugin;
use BlueFairy\Consent\Settings;
use BlueFairy\Consent\Tests\TestCase;

final class FrontendTest extends TestCase {

	public function test_boot_registers_hooks_and_fires_loaded(): void {
		Plugin::boot();
		$this->assertSame( 1, has_action( 'wp_head', [ Frontend::class, 'print_head' ] ) );
		$this->assertNotFalse( has_action( 'wp_footer', [ Frontend::class, 'print_banner' ] ) );
		$this->assertNotFalse( has_action( 'admin_menu', [ Admin::class, 'menu' ] ) );
		$this->assertArrayHasKey( 'bluefairy_consent_settings', $GLOBALS['bfct']['shortcodes'] );
		$this->assertContains( 'bfconsent_loaded', $GLOBALS['bfct']['actions'] );
		$this->assertNotFalse( has_action( 'init', [ Plugin::class, 'load_textdomain' ] ) );
	}

	public function test_load_textdomain_points_at_the_bundled_languages_folder(): void {
		Plugin::load_textdomain();
		$this->assertSame( basename( BFCONSENT_DIR ) . '/languages', $GLOBALS['bfct']['textdomains']['bluefairy-consent'] );
	}

	public function test_categories_default_to_necessary_and_analytics(): void {
		$cats = Categories::all();
		$this->assertSame( [ 'necessary', 'analytics' ], array_keys( $cats ) );
		$this->assertTrue( $cats['necessary']['required'] );
		$this->assertSame( [ 'analytics_storage' ], $cats['analytics']['signals'] );
	}

	public function test_categories_filter_is_cleaned(): void {
		add_filter(
			'bfconsent_categories',
			static function ( array $c ): array {
				$c['marketing'] = [ 'label' => 'M', 'signals' => [ 'ad_storage', 'made_up' ], 'cookies' => [ '^_fbp' ] ];
				$c['Bad Slug']  = [ 'label' => 'x' ];
				$c['notarray']  = 'x';
				return $c;
			}
		);
		$cats = Categories::all();
		$this->assertSame( [ 'ad_storage' ], $cats['marketing']['signals'] );
		$this->assertFalse( $cats['marketing']['required'] );
		$this->assertArrayNotHasKey( 'Bad Slug', $cats );
		$this->assertArrayNotHasKey( 'notarray', $cats );
	}

	public function test_categories_filter_returning_garbage_falls_back(): void {
		add_filter( 'bfconsent_categories', static fn() => 'nope' );
		$this->assertArrayHasKey( 'analytics', Categories::all() );
	}

	public function test_config_carries_settings_and_category_signals(): void {
		update_option( Settings::OPTION, [ 'ga4_id' => 'G-TEST1234', 'policy_version' => 3 ] );
		$config = Frontend::config();
		$this->assertSame( 'bfc_consent', $config['cookie'] );
		$this->assertSame( 3, $config['version'] );
		$this->assertSame( 'G-TEST1234', $config['ga4'] );
		$this->assertSame( [ 'analytics_storage' ], $config['categories']['analytics']['signals'] );
		$this->assertArrayNotHasKey( 'label', $config['categories']['analytics'] );
	}

	public function test_config_filter_garbage_falls_back(): void {
		add_filter( 'bfconsent_config', static fn() => null );
		$this->assertSame( 'bfc_consent', Frontend::config()['cookie'] );
	}

	public function test_head_prints_config_then_consent_mode_default_denied(): void {
		$out = $this->capture( [ Frontend::class, 'print_head' ] );
		$this->assertStringContainsString( 'id="bfconsent-mode"', $out );
		$this->assertStringContainsString( 'window.bfconsentConfig={', $out );
		$this->assertStringContainsString( "analytics_storage: 'denied'", $out );
		$this->assertLessThan( strpos( $out, "'consent', 'default'" ), strpos( $out, 'bfconsentConfig' ) );
	}

	public function test_enqueue_registers_deferred_footer_script_and_accent(): void {
		update_option( Settings::OPTION, [ 'accent_color' => '#112233' ] );
		Frontend::enqueue();
		$this->assertSame( 'defer', $GLOBALS['bfct']['scripts']['bfconsent']['args']['strategy'] );
		$this->assertTrue( $GLOBALS['bfct']['scripts']['bfconsent']['args']['in_footer'] );
		$this->assertStringContainsString( '#112233', $GLOBALS['bfct']['inline']['style:bfconsent'] );
	}

	public function test_banner_has_equal_weight_buttons_and_is_hidden_until_script_runs(): void {
		$html = $this->capture( [ Frontend::class, 'print_banner' ] );
		$this->assertMatchesRegularExpression( '/<div id="bfc-banner"[^>]* hidden>/', $html );
		$this->assertStringContainsString( '<button type="button" class="bfc-btn" data-bfc-action="reject">Reject All</button>', $html );
		$this->assertStringContainsString( '<button type="button" class="bfc-btn" data-bfc-action="accept">Accept All</button>', $html );
		$this->assertStringContainsString( 'class="bfc bfc-reopen', $html );
		$this->assertStringNotContainsString( 'bfc-privacy', $html );
	}

	public function test_banner_is_identical_whatever_cookie_the_visitor_sends(): void {
		$before                    = Frontend::banner_html();
		$_COOKIE['bfc_consent']    = rawurlencode( '{"v":1,"c":{"analytics":true}}' );
		$after                     = Frontend::banner_html();
		unset( $_COOKIE['bfc_consent'] );
		$this->assertSame( $before, $after );
	}

	public function test_banner_includes_privacy_link_hooks_and_honours_reopen_off(): void {
		update_option( Settings::OPTION, [ 'privacy_url' => 'https://example.com/privacy', 'reopen_button' => false, 'position' => 'bottom' ] );
		add_action( 'bfconsent_banner_body', static function () { echo '<div id="body-hook"></div>'; } );
		add_action( 'bfconsent_banner_actions', static function () { echo '<button id="action-hook"></button>'; } );
		$html = Frontend::banner_html();
		$this->assertStringContainsString( 'href="https://example.com/privacy"', $html );
		$this->assertStringContainsString( 'body-hook', $html );
		$this->assertLessThan( strpos( $html, 'data-bfc-action="accept"' ), strpos( $html, 'action-hook' ) );
		$this->assertGreaterThan( strpos( $html, 'data-bfc-action="reject"' ), strpos( $html, 'action-hook' ) );
		$this->assertStringNotContainsString( 'bfc-reopen', $html );
		$this->assertStringContainsString( 'bfc--bottom"', $html );
	}

	public function test_banner_escapes_the_title(): void {
		update_option( Settings::OPTION, [ 'title' => '<img src=x onerror=alert(1)>' ] );
		$this->assertStringNotContainsString( '<img', Frontend::banner_html() );
	}

	public function test_shortcode_renders_reopen_button_with_custom_label(): void {
		$this->assertSame( '<button type="button" class="bfc-settings-link" data-bfc-open>Cookie settings</button>', Frontend::shortcode( '' ) );
		$this->assertStringContainsString( '>Cookies &amp; you<', Frontend::shortcode( [ 'label' => 'Cookies & you' ] ) );
	}

	public function test_uninstall_deletes_the_option(): void {
		update_option( Settings::OPTION, [ 'title' => 'x' ] );
		if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
			define( 'WP_UNINSTALL_PLUGIN', true );
		}
		require dirname( __DIR__, 2 ) . '/uninstall.php';
		$this->assertFalse( get_option( Settings::OPTION ) );
	}

	public function test_uninstall_on_multisite_cleans_every_site(): void {
		$GLOBALS['bfct']['multisite'] = true;
		$GLOBALS['bfct']['sites']     = [
			1 => [ Settings::OPTION => [ 'title' => 'one' ], 'other' => 'keep' ],
			2 => [ Settings::OPTION => [ 'title' => 'two' ] ],
			3 => [ Settings::OPTION => [ 'title' => 'three' ] ],
		];
		$GLOBALS['bfct']['options'] = $GLOBALS['bfct']['sites'][1];
		if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
			define( 'WP_UNINSTALL_PLUGIN', true );
		}
		require dirname( __DIR__, 2 ) . '/uninstall.php';
		bfconsent_test_select_blog( 1 ); // Flush site 1's live store into the per-site map.
		foreach ( [ 1, 2, 3 ] as $id ) {
			$this->assertArrayNotHasKey( Settings::OPTION, $GLOBALS['bfct']['sites'][ $id ], 'site ' . $id );
		}
		$this->assertSame( 'keep', $GLOBALS['bfct']['sites'][1]['other'] );
		$this->assertSame( 0, $GLOBALS['bfct']['get_sites_args']['number'] );
		$this->assertSame( [], $GLOBALS['bfct']['blogstack'], 'every switch_to_blog restored' );
		$this->assertSame( 1, $GLOBALS['bfct']['blog'] );
	}

	public function test_uninstall_on_single_site_does_not_switch(): void {
		update_option( Settings::OPTION, [ 'title' => 'x' ] );
		if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
			define( 'WP_UNINSTALL_PLUGIN', true );
		}
		require dirname( __DIR__, 2 ) . '/uninstall.php';
		$this->assertFalse( get_option( Settings::OPTION ) );
		$this->assertSame( [], $GLOBALS['bfct']['switches'] );
	}

	public function test_entry_file_defines_constants_and_hooks_boot(): void {
		$src = (string) file_get_contents( dirname( __DIR__, 2 ) . '/bluefairy-consent.php' );
		$this->assertStringContainsString( "define( 'BFCONSENT_VERSION', '1.2.0' );", $src );
		$this->assertStringContainsString( 'Version:           1.2.0', $src );
		$this->assertStringContainsString( "add_action( 'plugins_loaded', [ BlueFairy\\Consent\\Plugin::class, 'boot' ] );", $src );
	}

	public function test_no_forbidden_words_in_shipped_files(): void {
		$root  = dirname( __DIR__, 2 );
		$files = array_merge( glob( $root . '/includes/*.php' ), glob( $root . '/assets/js/*.js' ), [ $root . '/readme.txt', $root . '/bluefairy-consent.php' ] );
		$this->assertGreaterThanOrEqual( 8, count( $files ) );
		foreach ( $files as $file ) {
			$text = strtolower( (string) file_get_contents( $file ) );
			$this->assertStringNotContainsString( 'partnership', $text, $file );
			$this->assertStringNotContainsString( 'freemius', $text, $file );
		}
	}

	public function test_boot_registers_the_cookie_list_shortcode(): void {
		Plugin::boot();
		$this->assertSame( [ Frontend::class, 'cookie_list_shortcode' ], $GLOBALS['bfct']['shortcodes']['bluefairy_consent_cookie_list'] );
	}

	public function test_necessary_description_says_no_consent_needed_under_gdpr(): void {
		$this->assertStringContainsString( 'These cookies do not require your consent under GDPR.', Categories::all()['necessary']['description'] );
	}

	public function test_cookies_start_with_the_consent_cookie_at_the_configured_lifetime_and_drop_unknown_categories(): void {
		update_option( Settings::OPTION, [ 'cookie_days' => 30, 'cookie_list' => "_ga|analytics|Google|Visitors|2 years\n_fbp|marketing|Meta|Ads|3 months" ] );
		$rows = Frontend::cookies();
		$this->assertSame( 'bfc_consent', $rows[0]['name'] );
		$this->assertSame( 'necessary', $rows[0]['category'] );
		$this->assertSame( '30 days', $rows[0]['duration'] );
		$this->assertSame( [ 'bfc_consent', '_ga' ], array_column( $rows, 'name' ) );
		update_option( Settings::OPTION, [ 'cookie_days' => 1 ] );
		$this->assertSame( '1 day', Frontend::cookies()[0]['duration'] );
	}

	public function test_cookie_table_escapes_and_labels_categories(): void {
		update_option( Settings::OPTION, [ 'cookie_list' => '_ga|analytics|Google|Visitors & "sessions"|2 years' ] );
		$html = Frontend::cookie_table();
		$this->assertStringContainsString( '<th scope="col">Duration</th>', $html );
		$this->assertStringContainsString( '<td><code>_ga</code></td><td>Analytics</td><td>Google</td>', $html );
		$this->assertStringContainsString( 'Visitors &amp; &quot;sessions&quot;', $html );
		$this->assertStringContainsString( '<td><code>bfc_consent</code></td><td>Necessary</td>', $html );
	}

	public function test_cookie_list_shortcode_wraps_the_table(): void {
		$html = Frontend::cookie_list_shortcode();
		$this->assertStringStartsWith( '<div class="bfc-cookie-list"><table class="bfc-cookie-table">', $html );
	}

	public function test_banner_contains_the_cookie_list_disclosure(): void {
		$html = Frontend::banner_html();
		$this->assertStringContainsString( '<details class="bfc-cookies"><summary>Cookie list</summary><table class="bfc-cookie-table">', $html );
		$this->assertLessThan( strpos( $html, 'bfc-actions' ), strpos( $html, 'bfc-cookies' ) );
	}
}

