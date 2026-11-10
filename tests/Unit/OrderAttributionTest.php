<?php
/**
 * WooCommerce order attribution off-switch.
 *
 * @package BlueFairy\Consent
 */

declare(strict_types=1);

namespace BlueFairy\Consent\Tests\Unit;

use BlueFairy\Consent\Admin;
use BlueFairy\Consent\OrderAttribution;
use BlueFairy\Consent\Plugin;
use BlueFairy\Consent\Settings;
use BlueFairy\Consent\Tests\TestCase;

final class OrderAttributionTest extends TestCase {

	private const HOOK = 'pre_option_woocommerce_feature_order_attribution_enabled';

	private function woo( bool $active ): void {
		add_filter( 'bfconsent_woocommerce_active', static fn() => $active );
	}

	public function test_setting_defaults_off_and_sanitizes_to_a_bool(): void {
		$this->assertFalse( Settings::defaults()['disable_wc_order_attribution'] );
		$this->assertTrue( Settings::sanitize( [ 'disable_wc_order_attribution' => '1' ] )['disable_wc_order_attribution'] );
		$this->assertFalse( Settings::sanitize( [] )['disable_wc_order_attribution'] );
	}

	public function test_setting_on_with_woocommerce_answers_the_feature_check_no(): void {
		$this->woo( true );
		update_option( Settings::OPTION, [ 'disable_wc_order_attribution' => true ] );
		Plugin::boot();
		$this->assertSame( 10, has_filter( self::HOOK, [ OrderAttribution::class, 'feature_off' ] ) );
		// What WordPress's get_option() returns when a pre_option filter answers.
		$this->assertSame( 'no', apply_filters( self::HOOK, false, OrderAttribution::WC_OPTION, 'yes' ) );
		$this->assertArrayNotHasKey( OrderAttribution::WC_OPTION, $GLOBALS['bfct']['options'], 'WooCommerce option must never be written' );
	}

	public function test_setting_off_leaves_woocommerce_alone(): void {
		$this->woo( true );
		OrderAttribution::register();
		$this->assertFalse( has_filter( self::HOOK ) );
	}

	public function test_setting_on_without_woocommerce_does_nothing(): void {
		$this->woo( false );
		update_option( Settings::OPTION, [ 'disable_wc_order_attribution' => true ] );
		OrderAttribution::register();
		$this->assertFalse( has_filter( self::HOOK ) );
	}

	public function test_switched_on_reads_only_the_stored_option(): void {
		$this->assertFalse( OrderAttribution::switched_on() );
		update_option( Settings::OPTION, 'garbage' );
		$this->assertFalse( OrderAttribution::switched_on() );
		update_option( Settings::OPTION, [ 'disable_wc_order_attribution' => true ] );
		$this->assertTrue( OrderAttribution::switched_on() );
	}

	public function test_woocommerce_active_reads_the_class_by_default(): void {
		$this->assertSame( class_exists( 'WooCommerce', false ), OrderAttribution::woocommerce_active() );
		$this->assertSame( 'no', OrderAttribution::feature_off() );
	}

	public function test_admin_row_shows_only_with_woocommerce(): void {
		$this->woo( true );
		update_option( Settings::OPTION, [ 'disable_wc_order_attribution' => true ] );
		$out = $this->capture( [ Admin::class, 'render' ] );
		$this->assertStringContainsString( 'name="bfconsent_settings[disable_wc_order_attribution]" value="1" checked="checked"', $out );
		$this->assertStringContainsString( 'sbjs_*', $out );
		$this->assertStringNotContainsString( 'type="hidden" name="bfconsent_settings[disable_wc_order_attribution]"', $out );
	}

	public function test_admin_keeps_the_saved_choice_while_woocommerce_is_inactive(): void {
		$this->woo( false );
		update_option( Settings::OPTION, [ 'disable_wc_order_attribution' => true ] );
		$out = $this->capture( [ Admin::class, 'render' ] );
		$this->assertStringNotContainsString( 'WooCommerce order attribution', $out );
		$this->assertStringContainsString( '<input type="hidden" name="bfconsent_settings[disable_wc_order_attribution]" value="1">', $out );
		remove_all_filters( 'bfconsent_woocommerce_active' );
		$this->woo( false );
		update_option( Settings::OPTION, [] );
		$this->assertStringNotContainsString( 'disable_wc_order_attribution', $this->capture( [ Admin::class, 'render' ] ) );
	}
}
