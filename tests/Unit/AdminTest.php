<?php
/**
 * Settings screen.
 *
 * @package BlueFairy\Consent
 */

declare(strict_types=1);

namespace BlueFairy\Consent\Tests\Unit;

use BlueFairy\Consent\Admin;
use BlueFairy\Consent\Settings;
use BlueFairy\Consent\Tests\TestCase;

final class AdminTest extends TestCase {

	public function test_register_hooks_setting_and_menu(): void {
		Admin::register();
		do_action( 'admin_init' );
		do_action( 'admin_menu' );
		$this->assertSame( [ Settings::class, 'sanitize' ], $GLOBALS['bfct']['settings'][ Settings::OPTION ]['sanitize_callback'] );
		$this->assertSame( 'manage_options', $GLOBALS['bfct']['pages'][ Admin::PAGE ]['cap'] );
	}

	public function test_render_shows_every_field_and_the_template_notice(): void {
		add_action( 'bfconsent_settings_rows', static function () { echo '<tr id="pro-row"></tr>'; } );
		add_filter( 'bfconsent_woocommerce_active', static fn() => true );
		$out = $this->capture( [ Admin::class, 'render' ] );
		foreach ( array_keys( Settings::defaults() ) as $key ) {
			$this->assertStringContainsString( 'name="bfconsent_settings[' . $key . ']"', $out, $key );
		}
		$this->assertStringContainsString( 'it is not legal advice', $out );
		$this->assertStringContainsString( 'pro-row', $out );
		$this->assertStringContainsString( 'value="bottom-left" selected="selected"', $out );
		$this->assertStringContainsString( 'name="bfconsent_settings[respect_gpc]" value="1" checked="checked"', $out );
	}

	public function test_render_refuses_without_capability(): void {
		$GLOBALS['bfct']['can'] = false;
		$this->assertSame( '', $this->capture( [ Admin::class, 'render' ] ) );
	}

	public function test_text_fields_and_row(): void {
		$this->assertArrayHasKey( 'reject_label', Admin::text_fields() );
		$out = $this->capture( static fn() => Admin::row( 'L<', 'k', '<i>c</i>' ) );
		$this->assertSame( '<tr><th scope="row"><label for="bfc-k">L&lt;</label></th><td><i>c</i></td></tr>', $out );
	}
}
