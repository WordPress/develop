<?php

/**
 * Tests for the wp_parse_list() function.
 *
 * @group functions
 *
 * @covers ::wp_removable_query_args
 */
class Tests_Functions_wpRemovableQueryArgs extends WP_UnitTestCase {

	/**
	 * Test that the wp_removable_query_args() function returns an array.
	 *
	 * This test verifies that the function wp_removable_query_args() returns a valid array.
	 *
	 * @ticket 59938
	 */
	public function test_should_return_array_when_called() {
		$result = wp_removable_query_args();
		$this->assertIsArray( $result, 'The return value is not an array.' );
	}

	/**
	 * Test that the array returned by wp_removable_query_args() is not empty.
	 *
	 * This test ensures that wp_removable_query_args() returns an array that contains
	 * query arguments and is not an empty array.
	 *
	 * @ticket 59938
	 */
	public function test_should_return_non_empty_array_when_called() {
		$result = wp_removable_query_args();
		$this->assertNotEmpty( $result, 'The returned array is empty.' );
	}

	/**
	 * Test that the filter applied to wp_removable_query_args() works as expected.
	 *
	 * This test ensures that the custom filter function applied via `add_filter()`
	 * correctly modifies the array returned by wp_removable_query_args() by adding
	 * a custom query argument.
	 *
	 * @ticket 59938
	 */
	public function test_should_modify_array_when_filter_applied() {
		// Add a custom query argument using a filter
		add_filter(
			'removable_query_args',
			function ( $args ) {
				$args[] = 'custom_arg';
				return $args;
			}
		);

		$result = wp_removable_query_args();

		// Assert that the custom argument is in the array
		$this->assertContains( 'custom_arg', $result, 'The filter did not modify the array as expected.' );

		// Remove the filter after the test
		remove_filter( 'removable_query_args', '__return_true' );
	}

	/**
	 * Test that wp_removable_query_args() reverts to the original array after the filter is removed.
	 *
	 * This test ensures that the original array is restored after removing a custom filter.
	 *
	 * @ticket 59938
	 */
	public function test_should_revert_to_original_array_when_filter_removed() {
		// Define the callback function for the filter
		$custom_filter_callback = function ( $args ) {
			$args[] = 'custom_arg';
			return $args;
		};

		// Apply the custom filter
		add_filter( 'removable_query_args', $custom_filter_callback );

		// Get the array with the filter applied
		$result_with_filter = wp_removable_query_args();
		$this->assertContains( 'custom_arg', $result_with_filter, 'The filter did not add the custom argument.' );

		// Now, remove the custom filter
		remove_filter( 'removable_query_args', $custom_filter_callback );

		// Get the array after the filter is removed
		$result_after_filter_removed = wp_removable_query_args();

		// The original array should not contain 'custom_arg'
		$this->assertNotContains( 'custom_arg', $result_after_filter_removed, 'The array did not revert to the original state after removing the filter.' );
	}
}
