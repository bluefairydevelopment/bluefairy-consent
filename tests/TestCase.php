<?php
/**
 * Base test case: resets stub state around every test.
 *
 * @package BlueFairy\Consent
 */

declare(strict_types=1);

namespace BlueFairy\Consent\Tests;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;

/**
 * Every unit test extends this.
 */
abstract class TestCase extends PHPUnitTestCase {

	protected function setUp(): void {
		parent::setUp();
		bfconsent_test_reset();
		$_REQUEST = [];
		$_POST    = [];
		$_GET     = [];
		$_SERVER['REQUEST_METHOD'] = 'GET';
	}

	protected function tearDown(): void {
		bfconsent_test_reset();
		parent::tearDown();
	}

	/**
	 * Capture echoed output of a callable.
	 */
	protected function capture( callable $fn ): string {
		ob_start();
		try {
			$fn();
		} finally {
			$out = (string) ob_get_clean();
		}
		return $out;
	}
}
