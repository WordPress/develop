<?php

/**
 * @group formatting
 *
 * @covers ::antispambot
 */
class Tests_Formatting_antispambot extends WP_UnitTestCase {
	public function test_returns_valid_utf8() {
		$data = array(
			'bob@example.com',
			'phil@example.info',
			'ace@204.32.222.14',
			'kevin@many.subdomains.make.a.happy.man.edu',
			'a@b.co',
			'bill+ted@example.com',
			'info@grå.org',
			'grå@grå.org',
			"gr\u{0061}\u{030a}blå@grå.org",
			'..@example.com',
		);
		foreach ( $data as $datum ) {
			$this->assertTrue( seems_utf8( antispambot( $datum ) ) );
		}
	}
}
