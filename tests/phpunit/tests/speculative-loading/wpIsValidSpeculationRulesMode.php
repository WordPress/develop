<?php
/**
 * Tests for the wp_is_valid_speculation_rules_mode() function.
 *
 * @package WordPress
 * @subpackage Speculative Loading
 */

/**
 * @group speculative-loading
 * @covers ::wp_is_valid_speculation_rules_mode
 */
class Tests_Speculative_Loading_wpIsValidSpeculationRulesMode extends WP_UnitTestCase {

	/**
	 * Tests that the function correctly identifies valid and invalid values.
	 *
	 * @ticket 62503
	 * @dataProvider data_is_valid_speculation_rules_mode
	 */
	public function test_wp_is_valid_speculation_rules_mode( $mode, $expected ) {
		if ( $expected ) {
			$this->assertTrue( wp_is_valid_speculation_rules_mode( $mode ) );
		} else {
			$this->assertFalse( wp_is_valid_speculation_rules_mode( $mode ) );
		}
	}

	public static function data_is_valid_speculation_rules_mode(): array {
		return array(
			'prefetch'     => array( 'prefetch', true ),
			'prerender'    => array( 'prerender', true ),
			'auto'         => array( 'auto', false ),
			'none'         => array( 'none', false ),
			'42'           => array( 42, false ),
			'empty string' => array( '', false ),
		);
	}
}
