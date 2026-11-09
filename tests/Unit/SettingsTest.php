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
		$this->assertSame( 365, Settings::get( 'cookie_days' ) );
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
		$this->assertSame( 'Accept All', $out['accept_label'] );
		$this->assertSame( 'bottom-right', $out['position'] );
		$this->assertSame( 'GTM-AB12CD', $out['gtm_id'] );
		$this->assertSame( '#000', $out['accent_color'] );
		$this->assertSame( 'https://example.com/privacy', $out['privacy_url'] );
	}

	public function test_sanitize_of_non_array_returns_defaults_with_checkboxes_off(): void {
		$out = Settings::sanitize( 'x' );
		$this->assertSame( 'We respect your privacy', $out['title'] );
		$this->assertFalse( $out['respect_gpc'] );
	}

	public function test_allowed_html_has_no_script_or_event_attributes(): void {
		$allowed = Settings::allowed_html();
		$this->assertArrayNotHasKey( 'script', $allowed );
		$this->assertArrayNotHasKey( 'onclick', $allowed['a'] );
	}
}
