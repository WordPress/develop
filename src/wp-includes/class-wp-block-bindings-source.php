<?php
/**
 * Block Bindings API: WP_Block_Bindings_Source class.
 *
 *
 * @package WordPress
 * @subpackage Block Bindings
 * @since 6.5.0
 */

/**
 * Class representing block bindings source.
 *
 * This class is designed for internal use by the Block Bindings registry.
 *
 * @since 6.5.0
 * @access private
 *
 * @see WP_Block_Bindings_Registry
 */
final class WP_Block_Bindings_Source {

	public function __construct( string $name, array $source_properties ) {
		$this->name = $name;

		/* Validate that the source properties contain the label */
		if ( ! isset( $source_properties['label'] ) ) {
			_doing_it_wrong(
				__METHOD__,
				__( 'The $source_properties must contain a "label".' ),
				'6.5.0'
			);
			return;
		}

		/* Validate that the source properties contain the get_value_callback */
		if ( ! isset( $source_properties['get_value_callback'] ) ) {
			_doing_it_wrong(
				__METHOD__,
				__( 'The $source_properties must contain a "get_value_callback".' ),
				'6.5.0'
			);
			return;
		}

		$this->label              = $source_properties['label'];
		$this->get_value_callback = $source_properties['get_value_callback'];
	}

	/**
	 * The name of the source.
	 *
	 * @since 6.5.0
	 * @var string
	 */
	public $name;

	/**
	 * The label of the source.
	 *
	 * @since 6.5.0
	 * @var string
	 */
	public $label;


	/**
	 * The function used to get the value of the source.
	 *
	 * @since 6.5.0
	 * @var callable
	 */
	private $get_value_callback;

	/**
	 * Retrieves the value of the source.
	 *
	 * @since 6.5.0
	 *
	 * @param array    $source_args     Array containing source arguments used to look up the override value, i.e. {"key": "foo"}.
	 * @param WP_Block $block_instance  The block instance.
	 * @param string   $attribute_name  The name of the target attribute.
	 *
	 * @return mixed The value of the source.
	 */
	public function get_value( array $source_args, $block_instance, string $attribute_name ) {
		return call_user_func_array( $this->get_value_callback, array( $source_args, $block_instance, $attribute_name ) );
	}
}
