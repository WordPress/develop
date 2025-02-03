<?php

/**
 * @group user
 * @group post
 */
class Tests_User_CountUserPosts extends WP_UnitTestCase {
	public static $user_id;
	public static $post_ids = array();

	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		self::$user_id = $factory->user->create(
			array(
				'role'       => 'author',
				'user_login' => 'count_user_posts_user',
				'user_email' => 'count_user_posts_user@example.com',
			)
		);

		self::$post_ids = $factory->post->create_many(
			4,
			array(
				'post_author' => self::$user_id,
				'post_type'   => 'post',
			)
		);
		self::$post_ids = array_merge(
			self::$post_ids,
			$factory->post->create_many(
				3,
				array(
					'post_author' => self::$user_id,
					'post_type'   => 'wptests_pt',
				)
			)
		);
		self::$post_ids = array_merge(
			self::$post_ids,
			$factory->post->create_many(
				2,
				array(
					'post_author' => 12345,
					'post_type'   => 'wptests_pt',
				)
			)
		);

		self::$post_ids[] = $factory->post->create(
			array(
				'post_author' => 12345,
				'post_type'   => 'wptests_pt',
			)
		);
	}

	public function set_up() {
		parent::set_up();
		register_post_type( 'wptests_pt' );
	}

	public function test_count_user_posts_post_type_should_default_to_post() {
		$this->assertSame( '4', count_user_posts( self::$user_id ) );
	}

	/**
	 * @ticket 21364
	 */
	public function test_count_user_posts_post_type_post() {
		$this->assertSame( '4', count_user_posts( self::$user_id, 'post' ) );
	}

	/**
	 * @ticket 21364
	 */
	public function test_count_user_posts_post_type_cpt() {
		$this->assertSame( '3', count_user_posts( self::$user_id, 'wptests_pt' ) );
	}

	/**
	 * @ticket 32243
	 */
	public function test_count_user_posts_with_multiple_post_types() {
		$this->assertSame( '7', count_user_posts( self::$user_id, array( 'wptests_pt', 'post' ) ) );
	}

	/**
	 * @ticket 32243
	 */
	public function test_count_user_posts_should_ignore_non_existent_post_types() {
		$this->assertSame( '4', count_user_posts( self::$user_id, array( 'foo', 'post' ) ) );
	}

	/**
	 * User count should work for users that don't exist but have posts assigned.
	 *
	 * @ticket 39242
	 */
	public function test_count_user_posts_for_non_existent_user() {
		$next_user_id = self::$user_id + 1;

		// Assign post to next user.
		self::factory()->post->create(
			array(
				'post_author' => $next_user_id,
				'post_type'   => 'post',
			)
		);

		$next_user_post_count = count_user_posts( $next_user_id );
		$this->assertSame( '1', $next_user_post_count, 'Non-existent user is expected to have count of one post.' );
	}

	/**
	 * Cached user count value should be accurate after user is created.
	 *
	 * @ticket 39242
	 */
	public function test_count_user_posts_for_user_created_after_being_assigned_posts() {
		$next_user_id = self::$user_id + 1;

		// Assign post to next user.
		self::factory()->post->create(
			array(
				'post_author' => $next_user_id,
				'post_type'   => 'post',
			)
		);

		// Cache the user count.
		count_user_posts( $next_user_id );

		// Create user.
		$real_next_user_id = self::factory()->user->create(
			array(
				'role' => 'author',
			)
		);

		$this->assertSame( $next_user_id, $real_next_user_id, 'User ID should match calculated value' );
		$this->assertSame( '1', count_user_posts( $next_user_id ), 'User is expected to have count of one post.' );
	}

	/**
	 * User count cache should be hit regardless of post type order.
	 *
	 * @ticket 39242
	 */
	public function test_cache_should_be_hit_regardless_of_post_type_order() {
		// Prime Cache
		count_user_posts( self::$user_id, array( 'wptests_pt', 'post' ) );

		$query_num_start = get_num_queries();
		count_user_posts( self::$user_id, array( 'post', 'wptests_pt' ) );
		$total_queries = get_num_queries() - $query_num_start;

		$this->assertSame( 0, $total_queries );
	}

	/**
	 * User count cache should be hit for string and array of post types.
	 *
	 * @ticket 39242
	 */
	public function test_cache_should_be_hit_for_string_and_array_equivalent_queries() {
		// Prime Cache
		count_user_posts( self::$user_id, 'post' );

		$query_num_start = get_num_queries();
		count_user_posts( self::$user_id, array( 'post' ) );
		$total_queries = get_num_queries() - $query_num_start;

		$this->assertSame( 0, $total_queries );
	}
}
