<?php

/**
 * Test WP_List_Util class.
 *
 * @group functions.php
 */
class Tests_Functions_wpListUtil extends WP_UnitTestCase {

	/**
	 * @covers WP_List_Util::get_input
	 */
	public function test_wp_list_util_get_input() {
		$input = array( 'foo', 'bar' );
		$util  = new WP_List_Util( $input );

		$this->assertSameSets( $input, $util->get_input() );
	}

	/**
	 * @covers WP_List_Util::get_output
	 */
	public function test_wp_list_util_get_output_immediately() {
		$input = array( 'foo', 'bar' );
		$util  = new WP_List_Util( $input );

		$this->assertSameSets( $input, $util->get_output() );
	}

	/**
	 * @covers WP_List_Util::get_output
	 */
	public function test_wp_list_util_get_output() {
		$expected = array(
			(object) array(
				'foo' => 'bar',
				'bar' => 'baz',
			),
		);

		$util   = new WP_List_Util(
			array(
				(object) array(
					'foo' => 'bar',
					'bar' => 'baz',
				),
				(object) array( 'bar' => 'baz' ),
			)
		);
		$actual = $util->filter( array( 'foo' => 'bar' ) );

		$this->assertEqualSets( $expected, $actual );
		$this->assertEqualSets( $expected, $util->get_output() );
	}

	/**
	 *
	 * @dataProvider data_wp_list_util_pluck
	 *
	 * @covers       WP_List_Util::pluck
	 * @covers ::wp_list_pluck
	 */
	public function test_wp_list_util_pluck( $target_array, $target_key, $expected, $index_key = null ) {

		$util = new WP_List_Util( $target_array );

		$actual = $util->pluck( $target_key, $index_key );

		$this->assertEqualSetsWithIndex( $expected, $actual );
		$this->assertEqualSetsWithIndex( $expected, $util->get_output(), 'output failed' );
		// test wrapper in functions.php
//		$this->assertEqualSetsWithIndex( $expected, wp_list_pluck( $target_array, $target_key, $index_key ) );
	}

	/**
	 * @return array[]  data for test_wp_list_util_pluck_simple
	 */
	public function data_wp_list_util_pluck() {
		return array(
			'simple'                 => array(
				'target_array' => array(
					0 => array( 'foo' => 'bar' ),
				),
				'target_key'   => 'foo',
				'expected'     => array( 'bar' ),
			),
			'simple_object'          => array(
				'target_array' => array(
					0 => (object) array( 'foo' => 'bar' ),
				),
				'target_key'   => 'foo',
				'expected'     => array( 'bar' ),
			),
			'not_found'              => array(
				'target_array' => array(
					0 => array( 'foo' => 'bar' ),
				),
				'target_key'   => 'not_found',
				'expected'     => array(),
			),
			'not_found_object'       => array(
				'target_array' => array(
					0 => (object) array( 'foo' => 'bar' ),
				),
				'target_key'   => 'not_found',
				'expected'     => array(),
			),
			'complex'                => array(
				'target_array' => array(
					'foo' => array( 'foo' => 'bar' ),
					1     => array(
						'foo' => 'bar',
						'bar' => 'baz',
					),
					2     => array( 'bar' => 'baz' ),
				),
				'target_key'   => 'foo',
				'expected'     => array(
					'foo' => 'bar',
					1     => 'bar',
				),
			),
			'complex_object'         => array(
				'target_array' => array(
					'foo' => (object) array( 'foo' => 'bar' ),
					1     => (object) array(
						'foo' => 'bar',
						'bar' => 'baz',
					),
					2     => (object) array( 'bar' => 'baz' ),
				),
				'target_key'   => 'foo',
				'expected'     => array(
					'foo' => 'bar',
					1     => 'bar',
				),
			),
			'index_not_found'        => array(
				'target_array' => array(
					'bar' => array(
						'foo' => 'bar',
						'bar' => 'baz',
					),
					'ddd' => array(
						'foo'   => 'bar2',
						'bar2i' => array(
							'foo' => 'bar3',
							'bar' => 'baz',
						),
					),
					'xxx' => array( 'bar' => 'baz2' ),
				),
				'target_key'   => 'bar',
				'expected'     => array( 'baz', 'baz2' ),
				'index_key'    => 'id',
			),
			'index_not_found_object' => array(
				'target_array' => array(
					'bar' => (object) array(
						'foo' => 'bar',
						'bar' => 'baz',
					),
					'ddd' => (object) array(
						'foo'   => 'bar2',
						'bar2i' => array(
							'foo' => 'bar3',
							'bar' => 'baz',
						),
					),
					'xxx' => (object) array( 'bar' => 'baz2' ),
				),
				'target_key'   => 'bar',
				'expected'     => array( 'baz', 'baz2' ),
				'index_key'    => 'id',
			),
			'index_id'               => array(
				'target_array' => array(
					'bar' => array(
						'foo' => 'bar',
						'bar' => 'baz',
						'id'  => 'id_1',
					),
					'ddd' => array(
						'foo'   => 'bar2',
						'bar2i' => array(
							'foo' => 'bar3',
							'bar' => 'baz',
						),
						'id'    => 'id_2',
					),
					'xxx' => array(
						'bar' => 'baz2',
						'id'  => 'id_3',
					),
					(object) array(
						'bar' => 'no id',
					),
				),
				'target_key'   => 'bar',
				'expected'     => array(
					'id_1' => 'baz',
					'id_3' => 'baz2',
					0      => 'no id',
				),
				'index_key'    => 'id',
			),
			'index_id_object'        => array(
				'target_array' => array(
					'bar' => (object) array(
						'foo' => 'bar',
						'bar' => 'baz',
						'id'  => 'id_1',
					),
					'ddd' => (object) array(
						'foo'   => 'bar2',
						'bar2i' => array(
							'foo' => 'bar3',
							'bar' => 'baz',
						),
						'id'    => 'id_2',
					),
					'xxx' => (object) array(
						'bar' => 'baz2',
						'id'  => 'id_3',
					),
					(object) array(
						'bar' => 'no id',
					),
				),
				'target_key'   => 'bar',
				'expected'     => array(
					'id_1' => 'baz',
					'id_3' => 'baz2',
					0      => 'no id',
				),
				'index_key'    => 'id',
			),
			'not_array_passed'       => array(
				'target_array' => 'I am a string',
				'target_key'   => 'foo',
				'expected'     => array(),
			),
		);
	}

	/**
	 * @covers WP_List_Util::sort
	 * @covers ::wp_list_sort
	 */
	public function test_wp_list_util_sort_simple() {
		$expected     = array(
			1 => 'one',
			2 => 'two',
			3 => 'three',
			4 => 'four',
		);
		$target_array = array(
			4 => 'four',
			2 => 'two',
			3 => 'three',
			1 => 'one',
		);

		$util   = new WP_List_Util( $target_array );
		$actual = $util->sort();

		$this->assertEqualSets( $expected, $actual );
		$this->assertEqualSets( $expected, $util->get_output(), 'output failed' );
		// test wrapper in functions.php
//		$this->assertEqualSets( $expected, wp_list_sort( $target_array ) );
	}

	/**
	 *
	 * @dataProvider data_wp_list_util_sort
	 *
	 * @covers       WP_List_Util::sort
	 * @covers ::wp_list_sort
	 */
	public function test_wp_list_util_sort( $expected, $target_array, $orderby = array(), $order = 'ASC', $preserve_keys = false ) {

		$util   = new WP_List_Util( $target_array );
		$actual = $util->sort( $orderby, $order, $preserve_keys );

		$this->assertEqualSetsWithIndex( $expected, $actual );
		$this->assertEqualSetsWithIndex( $expected, $util->get_output(), 'output failed' );
		// test wrapper in functions.php
		$this->assertEqualSetsWithIndex( $expected, wp_list_sort( $target_array, $orderby, $order, $preserve_keys ) );
	}


	public function data_wp_list_util_sort() {
		return array(
			'default'                    => array(
				'expected'     => array(
					2 => 'two',
					3 => 'three',
					1 => 'one',
					4 => 'four',
				),
				'target_array' => array(
					4 => 'four',
					2 => 'two',
					3 => 'three',
					1 => 'one',
				),
			),
			'default_no_keys'            => array(
				'expected'     => array( 'four', 'two', 'three', 'one' ),
				'target_array' => array( 'four', 'two', 'three', 'one' ),
			),
			'default_int'                => array(
				'expected'     => array(
					1 => 1,
					2 => 2,
					3 => 3,
					4 => 4,
				),
				'target_array' => array(
					4 => 4,
					2 => 2,
					3 => 3,
					1 => 1,
				),
			),
			'DESC'                       => array(
				'expected'     => array(
					1 => 'two',
					2 => 'three',
					3 => 'one',
					0 => 'four',
				),
				'target_array' => array(
					4 => 'four',
					2 => 'two',
					3 => 'three',
					1 => 'one',
				),
				'orderby'      => 'DESC',
			),
			'Empty_by_DESC'              => array(
				'expected'     => array(
					4 => 'four',
					3 => 'three',
					2 => 'two',
					1 => 'one',
				),
				'target_array' => array(
					4 => 'four',
					2 => 'two',
					3 => 'three',
					1 => 'one',
				),
				'orderby'      => array(),
				'order'        => 'DESC',
			),
			'simple_arrays'              => array(
				'expected'     => array(
					array(
						'id'  => 1,
						'val' => 'one',
					),
					array(
						'id'  => 3,
						'val' => 'three',
					),
					array(
						'id'  => 2,
						'val' => 'two',
					),
					array(
						'id'  => 4,
						'val' => 'four',
					),
				),
				'target_array' => array(
					array(
						'id'  => 1,
						'val' => 'one',
					),
					array(
						'id'  => 3,
						'val' => 'three',
					),
					array(
						'id'  => 2,
						'val' => 'two',
					),
					array(
						'id'  => 4,
						'val' => 'four',
					),
				),
				'orderby'      => array( 'id' ),
			),
			'simple_arrays_ASC'          => array(
				'expected'     => array(
					array(
						'id'  => 1,
						'val' => 'one',
					),
					array(
						'id'  => 2,
						'val' => 'two',
					),
					array(
						'id'  => 3,
						'val' => 'three',
					),
					array(
						'id'  => 4,
						'val' => 'four',
					),
				),
				'target_array' => array(
					array(
						'id'  => 1,
						'val' => 'one',
					),
					array(
						'id'  => 3,
						'val' => 'three',
					),
					array(
						'id'  => 2,
						'val' => 'two',
					),
					array(
						'id'  => 4,
						'val' => 'four',
					),
				),
				'orderby'      => array( 'id' => 'asc' ),
			),
			'simple_arrays_DESC'         => array(
				'expected'     => array(
					array(
						'id'  => 4,
						'val' => 'four',
					),
					array(
						'id'  => 3,
						'val' => 'three',
					),
					array(
						'id'  => 2,
						'val' => 'two',
					),
					array(
						'id'  => 1,
						'val' => 'one',
					),
				),
				'target_array' => array(
					array(
						'id'  => 1,
						'val' => 'one',
					),
					array(
						'id'  => 3,
						'val' => 'three',
					),
					array(
						'id'  => 2,
						'val' => 'two',
					),
					array(
						'id'  => 4,
						'val' => 'four',
					),
				),
				'orderby'      => array( 'id' => 'desc' ),
			),
			'simple_arrays_object'       => array(
				'expected'     => array(
					array(
						'group' => 2,
						'id'    => 1,
						'val'   => 'two one',
					),
					array(
						'group' => 2,
						'id'    => 2,
						'val'   => 'two two',
					),
					array(
						'group' => 1,
						'id'    => 3,
						'val'   => 'one three',
					),
					array(
						'group' => 1,
						'id'    => 4,
						'val'   => 'one four',
					),
				),
				'target_array' => array(
					(object) array(
						'group' => 2,
						'id'    => 1,
						'val'   => 'two one',
					),
					(object) array(
						'group' => 1,
						'id'    => 3,
						'val'   => 'one three',
					),
					(object) array(
						'group' => 2,
						'id'    => 2,
						'val'   => 'two two',
					),
					(object) array(
						'group' => 1,
						'id'    => 4,
						'val'   => 'one four',
					),
				),
				'orderby'      => array(
					'id' => 'asc',
				),
			),
			'simple_arrays_multi_object' => array(
				'expected'     => array(
					array(
						'group' => 1,
						'id'    => 4,
						'val'   => 'one four',
					),
					array(
						'group' => 1,
						'id'    => 3,
						'val'   => 'one three',
					),
					array(
						'group' => 2,
						'id'    => 2,
						'val'   => 'two two',
					),
					array(
						'group' => 2,
						'id'    => 1,
						'val'   => 'two one',
					),
				),
				'target_array' => array(
					(object) array(
						'group' => 2,
						'id'    => 1,
						'val'   => 'two one',
					),
					(object) array(
						'group' => 1,
						'id'    => 3,
						'val'   => 'one three',
					),
					(object) array(
						'group' => 2,
						'id'    => 2,
						'val'   => 'two two',
					),
					(object) array(
						'group' => 1,
						'id'    => 4,
						'val'   => 'one four',
					),
				),
				'orderby'      => array(
					'group' => 'asc',
					'id'    => 'desc',
				),
			),
			'simple_arrays_ASC'          => array(
				'expected'     => array(
					'key1' => array(
						'id'  => 1,
						'val' => 'one',
					),
					'key3' => array(
						'id'  => 2,
						'val' => 'two',
					),
					'key2' => array(
						'id'  => 3,
						'val' => 'three',
					),
					'key4' => array(
						'id'  => 4,
						'val' => 'four',
					),
				),
				'target_array' => array(
					'key1' => array(
						'id'  => 1,
						'val' => 'one',
					),
					'key2' => array(
						'id'  => 3,
						'val' => 'three',
					),
					'key3' => array(
						'id'  => 2,
						'val' => 'two',
					),
					'key4' => array(
						'id'  => 4,
						'val' => 'four',
					),
				),
				'orderby'      => array( 'id' => 'asc' ),
				'order' => null,
				'preserve_keys' => true,
			),
		);
	}

}
