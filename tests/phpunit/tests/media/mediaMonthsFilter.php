<?php
/**
 * Test WP_Media months filter functionality
 *
 * @group media
 */
class Tests_Media_MonthsFilter extends WP_UnitTestCase {

	/**
	 * Test that the show_media_library_months_select filter works
	 *
	 * @ticket 41675
	 */
	public function test_show_media_library_months_select_filter() {
		$attachment1 = self::factory()->attachment->create_object(
			array(
				'file'           => 'test1.jpg',
				'post_parent'    => 0,
				'post_date'      => '2023-01-15 12:00:00',
				'post_type'      => 'attachment',
				'post_mime_type' => 'image/jpeg',
			)
		);

		$attachment2 = self::factory()->attachment->create_object(
			array(
				'file'           => 'test2.jpg',
				'post_parent'    => 0,
				'post_date'      => '2023-02-15 12:00:00',
				'post_type'      => 'attachment',
				'post_mime_type' => 'image/jpeg',
			)
		);

		$this->assertTrue( apply_filters( 'show_media_library_months_select', true ) );

		add_filter( 'show_media_library_months_select', '__return_false' );
		$this->assertFalse( apply_filters( 'show_media_library_months_select', true ) );

		wp_delete_attachment( $attachment1, true );
		wp_delete_attachment( $attachment2, true );
	}
}
