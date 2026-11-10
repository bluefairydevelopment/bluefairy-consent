<?php
/**
 * Settings defaults, merging and sanitising.
 *
 * @package BlueFairy\Consent
 */

declare(strict_types=1);

namespace BlueFairy\Consent\Tests\Unit;

use BlueFairy\Consent\Settings;
use BlueFairy\Consent\Tests\TestCase;

final class SettingsTest extends TestCase {

	public function test_defaults_give_equal_accept_and_reject_labels_and_respect_gpc(): void {
		$d = Settings::defaults();
		$this->assertSame( 'Accept All', $d['accept_label'] );
		$this->assertSame( 'Reject All', $d['reject_label'] );
		$this->assertTrue( $d['respect_gpc'] );
		$this->assertSame( '', $d['ga4_id'] );
	}

	public function test_all_merges_saved_over_defaults_and_drops_unknown_keys(): void {
		update_option( Settings::OPTION, [ 'title' => 'Hi', 'bogus' => 'x' ] );
		$all = Settings::all();
		$this->assertSame( 'Hi', $all['title'] );
		$this->assertArrayNotHasKey( 'bogus', $all );
		$this->assertSame( 'Accept All', $all['accept_label'] );
	}

	public function test_all_survives_a_non_array_option(): void {
		update_option( Settings::OPTION, 'garbage' );
		$this->assertSame( Settings::defaults(), Settings::all() );
	}

	public function test_get_returns_one_value_or_empty_string(): void {
		$this->assertSame( 182, Settings::get( 'cookie_days' ) );
		$this->assertSame( '', Settings::get( 'nope' ) );
	}

	public function test_sanitize_validates_ids_numbers_position_and_colour(): void {
		$out = Settings::sanitize(
			[
				'title'          => '<b>Title</b>',
				'message'        => 'Read <a href="https://x.test/p">this</a><script>bad()</script>',
				'privacy_url'    => 'javascript:alert(1)',
				'position'       => 'top',
				'ga4_id'         => ' g-abc1234 ',
				'gtm_id'         => 'GTM-1',
				'cookie_days'    => 9999,
				'policy_version' => 0,
				'reopen_button'  => '',
				'respect_gpc'    => '1',
				'accent_color'   => 'red',
			]
		);
		$this->assertSame( 'Title', $out['title'] );
		$this->assertStringContainsString( '<a href="https://x.test/p">', (string) $out['message'] );
		$this->assertStringNotContainsString( '<script>', (string) $out['message'] );
		$this->assertSame( '', $out['privacy_url'] );
		$this->assertSame( 'bottom-left', $out['position'] );
		$this->assertSame( 'G-ABC1234', $out['ga4_id'] );
		$this->assertSame( '', $out['gtm_id'] );
		$this->assertSame( 395, $out['cookie_days'] );
		$this->assertSame( 1, $out['policy_version'] );
		$this->assertFalse( $out['reopen_button'] );
		$this->assertTrue( $out['respect_gpc'] );
		$this->assertSame( '#1863dc', $out['accent_color'] );
	}

	public function test_sanitize_keeps_good_values_and_defaults_blank_labels(): void {
		$out = Settings::sanitize(
			[
				'accept_label' => '  ',
				'position'     => 'bottom-right',
				'gtm_id'       => 'gtm-ab12cd',
				'accent_color' => '#000',
				'privacy_url'  => 'https://example.com/privacy',
			]
		);
		$this->assertArrayNotHasKey( 'accept_label', $out );
		update_option( Settings::OPTION, $out );
		$this->assertSame( 'Accept All', Settings::get( 'accept_label' ) );
		$this->assertSame( 'bottom-right', $out['position'] );
		$this->assertSame( 'GTM-AB12CD', $out['gtm_id'] );
		$this->assertSame( '#000', $out['accent_color'] );
		$this->assertSame( 'https://example.com/privacy', $out['privacy_url'] );
	}

	public function test_sanitize_of_non_array_returns_defaults_with_checkboxes_off(): void {
		$out = Settings::sanitize( 'x' );
		$this->assertArrayNotHasKey( 'title', $out );
		$this->assertFalse( $out['respect_gpc'] );
	}

	public function test_allowed_html_has_no_script_or_event_attributes(): void {
		$allowed = Settings::allowed_html();
		$this->assertArrayNotHasKey( 'script', $allowed );
		$this->assertArrayNotHasKey( 'onclick', $allowed['a'] );
	}

	public function test_defaults_remember_six_months_and_disclose_consent_mode_and_the_cookie_list(): void {
		$d = Settings::defaults();
		$this->assertSame( 182, $d['cookie_days'] );
		$this->assertStringContainsString( 'Google Consent Mode', (string) $d['message'] );
		$this->assertStringContainsString( 'list of all the cookies', (string) $d['message'] );
		$this->assertStringContainsString( '_ga|analytics|Google Analytics', (string) $d['cookie_list'] );
		$this->assertSame( [ '_ga', '_ga_*' ], array_column( Settings::parse_cookies( (string) $d['cookie_list'] ), 'name' ) );
	}

	public function test_sanitize_without_cookie_days_defaults_to_182(): void {
		$this->assertSame( 182, Settings::sanitize( [] )['cookie_days'] );
	}

	public function test_parse_cookies_keeps_five_field_lines_with_a_valid_category(): void {
		$rows = Settings::parse_cookies( "a|analytics|P|Why|1 day\nbad line\n|analytics|P|W|D\nb|Bad Cat|P|W|D\nc|marketing|<b>Q</b>|W|D" );
		$this->assertCount( 2, $rows );
		$this->assertSame( [ 'name' => 'a', 'category' => 'analytics', 'provider' => 'P', 'purpose' => 'Why', 'duration' => '1 day' ], $rows[0] );
		$this->assertSame( 'Q', $rows[1]['provider'] );
	}

	public function test_cookies_to_text_round_trips(): void {
		$text = "a|analytics|P|Why|1 day\nc|marketing|Q|W|D";
		$this->assertSame( $text, Settings::cookies_to_text( Settings::parse_cookies( $text ) ) );
	}

	public function test_sanitize_cleans_the_cookie_list_and_keeps_the_default_when_absent(): void {
		$this->assertSame( 'x|analytics|P|W|D', Settings::sanitize( [ 'cookie_list' => "x|analytics|P|W|D\njunk" ] )['cookie_list'] );
		$this->assertSame( Settings::default_cookie_list(), Settings::sanitize( [] )['cookie_list'] );
	}

	public function test_default_cookie_list_is_filterable_and_survives_a_bad_filter(): void {
		add_filter( 'bfconsent_default_cookie_list', fn( $l ) => array_merge( $l, [ '_fbp|marketing|Meta|Ads|3 months', 'broken' ] ) );
		$this->assertStringEndsWith( '_fbp|marketing|Meta|Ads|3 months', Settings::default_cookie_list() );
		bfconsent_test_reset();
		add_filter( 'bfconsent_default_cookie_list', fn() => 'nope' );
		$this->assertStringStartsWith( '_ga|analytics', Settings::default_cookie_list() );
	}

	public function test_sanitize_does_not_store_texts_equal_to_the_default_so_they_follow_the_site_language(): void {
		$defaults = Settings::defaults();
		$input    = [];
		foreach ( Settings::TRANSLATED as $key ) {
			$input[ $key ] = $defaults[ $key ];
		}
		$out = Settings::sanitize( $input );
		foreach ( Settings::TRANSLATED as $key ) {
			$this->assertArrayNotHasKey( $key, $out, $key );
		}
		update_option( Settings::OPTION, $out );
		// The site language changes after the save: the default follows it.
		add_filter( 'bfconsent_test_gettext', static fn( string $t ): string => 'We respect your privacy' === $t ? 'Wir respektieren Ihre Privatsphäre' : $t );
		$this->assertSame( 'Wir respektieren Ihre Privatsphäre', Settings::get( 'title' ) );
	}

	public function test_sanitize_stores_customised_texts(): void {
		$out = Settings::sanitize( [ 'title' => 'Cookies on this site', 'message' => 'Custom <b>message</b>', 'settings_label' => 'Privacy choices' ] );
		$this->assertSame( 'Cookies on this site', $out['title'] );
		$this->assertSame( 'Custom <b>message</b>', $out['message'] );
		$this->assertSame( 'Privacy choices', $out['settings_label'] );
		$this->assertArrayNotHasKey( 'accept_label', $out );
	}
}
