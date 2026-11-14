<?php
/**
 * composer.json must not carry a "version" field. This plugin
 * has no composer.lock; the field would make one stale at every version bump
 * if a lock were added. The plugin's version lives in the plugin header and
 * BFCONSENT_VERSION only.
 *
 * @package BlueFairy\Consent
 */

declare(strict_types=1);

namespace BlueFairy\Consent\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class ComposerManifestTest extends TestCase {

	/**
	 * Reads and decodes a JSON file at the repository root.
	 *
	 * @return array<string, mixed>
	 */
	private static function read_json( string $file ): array {
		$raw = file_get_contents( dirname( __DIR__, 2 ) . '/' . $file );
		self::assertIsString( $raw, $file . ' is readable' );
		$data = json_decode( $raw, true, 512, JSON_THROW_ON_ERROR );
		self::assertIsArray( $data, $file . ' decodes to an object' );
		return $data;
	}

	public function test_composer_json_has_no_version_field(): void {
		self::assertArrayNotHasKey(
			'version',
			self::read_json( 'composer.json' ),
			'composer.json must not carry "version" (WPMAP-374): it makes composer.lock stale at every bump. '
			. 'The version lives in the plugin header and BFCONSENT_VERSION.'
		);
	}
}
