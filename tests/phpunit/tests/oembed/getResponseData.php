<?php

/**
 * @group oembed
 */
class Tests_oEmbed_Response_Data extends WP_UnitTestCase {
	public function set_up() {
		parent::set_up();

		// `get_post_embed_html()` assumes `wp-includes/js/wp-embed.js` is present:
		self::touch( ABSPATH . WPINC . '/js/wp-embed.js' );
	}

	function test_get_oembed_response_data_non_existent_post() {
		$this->assertFalse( get_oembed_response_data( 0, 100 ) );
	}

	function test_get_oembed_response_data() {
		$post = self::factory()->post->create_and_get(
			array(
				'post_title' => 'Some Post',
			)
		);

		$data = get_oembed_response_data( $post, 400 );

		$this->assertSameSets(
			array(
				'version'       => '1.0',
				'provider_name' => get_bloginfo( 'name' ),
				'provider_url'  => home_url(),
				'author_name'   => get_bloginfo( 'name' ),
				'author_url'    => home_url(),
				'title'         => 'Some Post',
				'type'          => 'rich',
				'width'         => 400,
				'height'        => 225,
				'html'          => get_post_embed_html( 400, 225, $post ),
			),
			$data
		);
	}

	/**
	 * Test get_oembed_response_data with an author.
	 *
	 * @covers ::get_oembed_response_data
	 * @covers ::get_post_embed_html
	 */
	function test_get_oembed_response_data_author() {
		$user_id = self::factory()->user->create(
			array(
				'display_name' => 'John Doe',
			)
		);

		$post = self::factory()->post->create_and_get(
			array(
				'post_title'  => 'Some Post',
				'post_author' => $user_id,
			)
		);

		$data = get_oembed_response_data( $post, 400 );

		$this->assertSameSets(
			array(
				'version'       => '1.0',
				'provider_name' => get_bloginfo( 'name' ),
				'provider_url'  => home_url(),
				'author_name'   => 'John Doe',
				'author_url'    => get_author_posts_url( $user_id ),
				'title'         => 'Some Post',
				'type'          => 'rich',
				'width'         => 400,
				'height'        => 225,
				'html'          => get_post_embed_html( 400, 225, $post ),
			),
			$data
		);
	}

	/**
	 * @covers ::get_oembed_response_data
	 */
	function test_get_oembed_response_link() {
		remove_filter( 'oembed_response_data', 'get_oembed_response_data_rich' );

		$post = self::factory()->post->create_and_get(
			array(
				'post_title' => 'Some Post',
			)
		);

		$data = get_oembed_response_data( $post, 600 );

		$this->assertSameSets(
			array(
				'version'       => '1.0',
				'provider_name' => get_bloginfo( 'name' ),
				'provider_url'  => home_url(),
				'author_name'   => get_bloginfo( 'name' ),
				'author_url'    => home_url(),
				'title'         => 'Some Post',
				'type'          => 'link',
			),
			$data
		);

		add_filter( 'oembed_response_data', 'get_oembed_response_data_rich', 10, 4 );
	}

	/**
	 * @covers ::get_oembed_response_data
	 */
	function test_get_oembed_response_data_with_draft_post() {
		$post = self::factory()->post->create_and_get(
			array(
				'post_status' => 'draft',
			)
		);

		$this->assertFalse( get_oembed_response_data( $post, 100 ) );
	}

	/**
	 * @covers ::get_oembed_response_data
	 */
	function test_get_oembed_response_data_with_scheduled_post() {
		$post = self::factory()->post->create_and_get(
			array(
				'post_status' => 'future',
				'post_date'   => date_format( date_create( '+1 day' ), 'Y-m-d H:i:s' ),
			)
		);

		$this->assertFalse( get_oembed_response_data( $post, 100 ) );
	}

	/**
	 * @covers ::get_oembed_response_data
	 */
	function test_get_oembed_response_data_with_private_post() {
		$post = self::factory()->post->create_and_get(
			array(
				'post_status' => 'private',
			)
		);

		$this->assertFalse( get_oembed_response_data( $post, 100 ) );
	}

	/**
	 * @ticket 47574
	 *
	 * @covers ::get_oembed_response_data
	 */
	function test_get_oembed_response_data_with_public_true_custom_post_status() {
		// Custom status with 'public' => true.
		register_post_status( 'public', array( 'public' => true ) );

		$post = self::factory()->post->create_and_get(
			array(
				'post_status' => 'public',
			)
		);

		$this->assertNotFalse( get_oembed_response_data( $post, 100 ) );
	}

	/**
	 * @ticket 47574
	 *
	 * @covers ::get_oembed_response_data
	 */
	function test_get_oembed_response_data_with_public_false_custom_post_status() {
		// Custom status with 'public' => false.
		register_post_status( 'private_foo', array( 'public' => false ) );

		$post = self::factory()->post->create_and_get(
			array(
				'post_status' => 'private_foo',
			)
		);

		$this->assertFalse( get_oembed_response_data( $post, 100 ) );
	}

	/**
	 * @ticket 47574
	 *
	 * @covers ::get_oembed_response_data
	 */
	function test_get_oembed_response_data_with_unregistered_custom_post_status() {
		$post = self::factory()->post->create_and_get(
			array(
				'post_status' => 'unknown_foo',
			)
		);

		$this->assertFalse( get_oembed_response_data( $post, 100 ) );
	}

	/**
	 * @covers ::get_oembed_response_data
	 */
	function test_get_oembed_response_data_maxwidth_too_high() {
		$post = self::factory()->post->create_and_get();

		$data = get_oembed_response_data( $post, 1000 );

		$this->assertSame( 600, $data['width'] );
		$this->assertSame( 338, $data['height'] );
	}

	/**
	 * @covers ::get_oembed_response_data
	 */
	function test_get_oembed_response_data_maxwidth_too_low() {
		$post = self::factory()->post->create_and_get();

		$data = get_oembed_response_data( $post, 100 );

		$this->assertSame( 200, $data['width'] );
		$this->assertSame( 200, $data['height'] );
	}

	/**
	 * @covers ::get_oembed_response_data
	 */
	function test_get_oembed_response_data_maxwidth_invalid() {
		$post = self::factory()->post->create_and_get();

		$data = get_oembed_response_data( $post, '400;" DROP TABLES' );

		$this->assertSame( 400, $data['width'] );
		$this->assertSame( 225, $data['height'] );

		$data = get_oembed_response_data( $post, "lol this isn't even a number?!?!?" );

		$this->assertSame( 200, $data['width'] );
		$this->assertSame( 200, $data['height'] );
	}

	/**
	 * @covers ::get_oembed_response_data
	 */
	function test_get_oembed_response_data_with_thumbnail() {
		$post          = self::factory()->post->create_and_get();
		$file          = DIR_TESTDATA . '/images/canola.jpg';
		$attachment_id = self::factory()->attachment->create_object(
			$file,
			$post->ID,
			array(
				'post_mime_type' => 'image/jpeg',
			)
		);
		set_post_thumbnail( $post, $attachment_id );

		$data = get_oembed_response_data( $post, 400 );

		$this->assertArrayHasKey( 'thumbnail_url', $data );
		$this->assertArrayHasKey( 'thumbnail_width', $data );
		$this->assertArrayHasKey( 'thumbnail_height', $data );
		$this->assertTrue( 400 >= $data['thumbnail_width'] );
	}

	/**
	 * @covers ::get_oembed_response_data
	 */
	function test_get_oembed_response_data_for_attachment() {
		$parent = self::factory()->post->create();
		$file   = DIR_TESTDATA . '/images/canola.jpg';
		$post   = self::factory()->attachment->create_object(
			$file,
			$parent,
			array(
				'post_mime_type' => 'image/jpeg',
			)
		);

		$data = get_oembed_response_data( $post, 400 );

		$this->assertArrayHasKey( 'thumbnail_url', $data );
		$this->assertArrayHasKey( 'thumbnail_width', $data );
		$this->assertArrayHasKey( 'thumbnail_height', $data );
		$this->assertTrue( 400 >= $data['thumbnail_width'] );
	}
}
