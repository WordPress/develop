<?php
/**
 * Tests for the Block Templates Registry.
 *
 * @package WordPress
 * @subpackage Block Templates
 *
 * @group block-templates
 */
class Tests_Block_Templates_Registry extends WP_UnitTestCase {
	/**
	 * @var WP_Block_Templates_Registry
	 */
	private $registry;

	public function set_up() {
		parent::set_up();
		$this->registry = new WP_Block_Templates_Registry();
	}

	/**
	 * Data provider for test_template_name_validation.
	 *
	 * @return array[] Test data.
	 */
	public function data_template_name_validation() {
		return array(
			'valid_simple_name'      => array(
				'my-plugin//my-template',
				true,
				'Valid template name with simple characters should be accepted',
			),
			'valid_with_underscores' => array(
				'my-plugin//my_template',
				true,
				'Template name with underscores should be accepted',
			),
			'valid_cpt_archive'      => array(
				'my-plugin//archive-my_post_type',
				true,
				'Template name for CPT archive with underscore should be accepted',
			),
		);
	}

	/**
	 * Tests template name validation with various inputs.
	 *
	 * @dataProvider data_template_name_validation
	 *
	 * @ticket 62523
	 *
	 * @param string $template_name The template name to test.
	 * @param bool   $expected      Expected validation result.
	 * @param string $message       Test assertion message.
	 */
	public function test_template_name_validation( $template_name, $expected, $message ) {
		$result = $this->registry->register( $template_name, array() );

		if ( $expected ) {
			$this->assertTrue( ! is_wp_error( $result ), $message );
		} else {
			$this->assertWPError( $result, $message );
		}
	}
}
