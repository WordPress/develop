<?php
/**
 * Tests for the wp_is_valid_speculation_rules_source() function.
 *
 * @package WordPress
 * @subpackage Speculative Loading
 */

/**
 * @group speculative-loading
 * @covers ::wp_is_valid_speculation_rules_source
 */
class Tests_Speculative_Loading_wpIsValidSpeculationRulesSource extends WP_UnitTestCase {

	/**
	 * Tests that the function correctly identifies valid and invalid values.
	 *
	 * @ticket 62503
	 * @dataProvider data_is_valid_speculation_rules_source
	 */
	public function test_wp_is_valid_speculation_rules_source( $source, $expected ) {
		if ( $expected ) {
			$this->assertTrue( wp_is_valid_speculation_rules_source( $source ) );
		} else {
			$this->assertFalse( wp_is_valid_speculation_rules_source( $source ) );
		}
	}

	public static function data_is_valid_speculation_rules_source(): array {
		return array(
			'list'         => array( 'list', true ),
			'document'     => array( 'document', true ),
			'auto'         => array( 'auto', false ),
			'none'         => array( 'none', false ),
			'42'           => array( 42, false ),
			'empty string' => array( '', false ),
		);
	}
}
