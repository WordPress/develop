<?php
/**
 * Tests for the wp_is_valid_speculation_rules_eagerness() function.
 *
 * @package WordPress
 * @subpackage Speculative Loading
 */

/**
 * @group speculative-loading
 * @covers ::wp_is_valid_speculation_rules_eagerness
 */
class Tests_Speculative_Loading_wpIsValidSpeculationRulesEagerness extends WP_UnitTestCase {

	/**
	 * Tests that the function correctly identifies valid and invalid values.
	 *
	 * @ticket 62503
	 * @dataProvider data_is_valid_speculation_rules_eagerness
	 */
	public function test_wp_is_valid_speculation_rules_eagerness( $eagerness, $expected ) {
		if ( $expected ) {
			$this->assertTrue( wp_is_valid_speculation_rules_eagerness( $eagerness ) );
		} else {
			$this->assertFalse( wp_is_valid_speculation_rules_eagerness( $eagerness ) );
		}
	}

	public static function data_is_valid_speculation_rules_eagerness(): array {
		return array(
			'conservative' => array( 'conservative', true ),
			'moderate'     => array( 'moderate', true ),
			'eager'        => array( 'eager', true ),
			'immediate'    => array( 'immediate', true ),
			'auto'         => array( 'auto', false ),
			'none'         => array( 'none', false ),
			'42'           => array( 42, false ),
			'empty string' => array( '', false ),
		);
	}
}
