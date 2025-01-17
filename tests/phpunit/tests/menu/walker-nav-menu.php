<?php
/**
 * @group menu
 * @group walker
 */
class Tests_Menu_Walker_Nav_Menu extends WP_UnitTestCase {

	/**
	 * @var \Walker_Nav_Menu The instance of the walker.
	 */
	public $walker;

	/**
	 * Original nav menu max depth.
	 *
	 * @var int
	 */
	private $orig_wp_nav_menu_max_depth;
	/**
	 * The privacy policy page ID.
	 *
	 * @var int
	 */
	protected static $privacy_policy_id;

	/**
	 * Set up before class.
	 */
	public static function set_up_before_class() {
		parent::set_up_before_class();

		$post_id = self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_title'  => 'Test Privacy Policy',
				'post_status' => 'publish',
			)
		);

		update_option( 'wp_page_for_privacy_policy', $post_id );
		self::$privacy_policy_id = (int) get_option( 'wp_page_for_privacy_policy' );
	}

	/**
	 * Setup.
	 */
	public function set_up() {
		global $_wp_nav_menu_max_depth;

		parent::set_up();

		/** Walker_Nav_Menu class */
		require_once ABSPATH . 'wp-includes/class-walker-nav-menu.php';
		$this->walker = new Walker_Nav_Menu();

		$this->orig_wp_nav_menu_max_depth = $_wp_nav_menu_max_depth;
	}

	/**
	 * Tear down
	 */
	public function tear_down() {
		global $_wp_nav_menu_max_depth;

		$_wp_nav_menu_max_depth = $this->orig_wp_nav_menu_max_depth;
		parent::tear_down();
	}

	/**
	 * Tear down after class.
	 */
	public static function tear_down_after_class() {
		if ( self::$privacy_policy_id ) {
			wp_delete_post( self::$privacy_policy_id, true );
			update_option( 'wp_page_for_privacy_policy', 0 );
		}

		parent::tear_down_after_class();
	}

	/**
	 * @ticket 47720
	 *
	 * @dataProvider data_start_el_with_empty_attributes
	 */
	public function test_start_el_with_empty_attributes( $value, $expected ) {
		$output     = '';
		$post_id    = self::factory()->post->create();
		$post_title = get_the_title( $post_id );

		$item = array(
			'ID'        => $post_id,
			'object_id' => $post_id,
			'title'     => $post_title,
			'target'    => '',
			'xfn'       => '',
			'current'   => false,
		);

		$args = array(
			'before'      => '',
			'after'       => '',
			'link_before' => '',
			'link_after'  => '',
		);

		add_filter(
			'nav_menu_link_attributes',
			static function ( $atts ) use ( $value ) {
				$atts['data-test'] = $value;
				return $atts;
			}
		);

		$this->walker->start_el( $output, (object) $item, 0, (object) $args );

		if ( '' !== $expected ) {
			$expected = sprintf( ' data-test="%s"', $expected );
		}

		$this->assertSame( "<li id=\"menu-item-{$post_id}\" class=\"menu-item-{$post_id}\"><a{$expected}>{$post_title}</a>", $output );
	}

	public function data_start_el_with_empty_attributes() {
		return array(
			array(
				'',
				'',
			),
			array(
				0,
				'0',
			),
			array(
				0.0,
				'0',
			),
			array(
				'0',
				'0',
			),
			array(
				null,
				'',
			),
			array(
				false,
				'',
			),
			array(
				true,
				'1',
			),
			array(
				array(),
				'',
			),
		);
	}

	/**
	 * Tests that `Walker_Nav_Menu::start_el()` adds `rel="privacy-policy"`.
	 *
	 * @ticket 56345
	 *
	 * @covers Walker_Nav_Menu::start_el
	 *
	 * @dataProvider data_walker_nav_menu_start_el_should_add_rel_privacy_policy_to_privacy_policy_url
	 *
	 * @param string $expected The expected substring containing the "rel" attribute and value.
	 * @param string $xfn      Optional. The XFN value. Default empty string.
	 * @param string $target   Optional. The target value. Default empty string.
	 */
	public function test_walker_nav_menu_start_el_should_add_rel_privacy_policy_to_privacy_policy_url( $expected, $xfn = '', $target = '' ) {

		$output = '';

		$item = array(
			'ID'        => self::$privacy_policy_id,
			'object_id' => self::$privacy_policy_id,
			'title'     => 'Privacy Policy',
			'target'    => $target,
			'xfn'       => $xfn,
			'current'   => false,
			'url'       => get_privacy_policy_url(),
		);

		$args = array(
			'before'      => '',
			'after'       => '',
			'link_before' => '',
			'link_after'  => '',
		);

		$this->walker->start_el( $output, (object) $item, 0, (object) $args );

		$this->assertStringContainsString( $expected, $output );
	}

	/**
	 * Data provider.
	 *
	 * @return array[]
	 */
	public function data_walker_nav_menu_start_el_should_add_rel_privacy_policy_to_privacy_policy_url() {
		return array(
			'no xfn value'                          => array(
				'expected' => 'rel="privacy-policy"',
			),
			'an xfn value'                          => array(
				'expected' => 'rel="nofollow privacy-policy"',
				'xfn'      => 'nofollow',
			),
			'no xfn value and a target of "_blank"' => array(
				'expected' => 'rel="privacy-policy"',
				'xfn'      => '',
				'target'   => '_blank',
			),
			'an xfn value and a target of "_blank"' => array(
				'expected' => 'rel="nofollow privacy-policy"',
				'xfn'      => 'nofollow',
				'target'   => '_blank',
			),
		);
	}

	/**
	 * Tests that `Walker_Nav_Menu::start_el()` does not add `rel="privacy-policy"` when no
	 * privacy policy page exists.
	 *
	 * @ticket 56345
	 *
	 * @covers Walker_Nav_Menu::start_el
	 */
	public function test_walker_nav_menu_start_el_should_not_add_rel_privacy_policy_when_no_privacy_policy_exists() {
		$post_id = self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_title'  => 'Test Privacy Policy',
				'post_status' => 'publish',
			)
		);

		// Do not set the privacy policy page.
		update_option( 'wp_page_for_privacy_policy', 0 );

		$output = '';

		$item = array(
			'ID'        => $post_id,
			'object_id' => $post_id,
			'title'     => 'Privacy Policy',
			'target'    => '',
			'xfn'       => '',
			'current'   => false,
			'url'       => get_the_permalink( $post_id ),
		);

		$args = array(
			'before'      => '',
			'after'       => '',
			'link_before' => '',
			'link_after'  => '',
		);

		$this->walker->start_el( $output, (object) $item, 0, (object) $args );

		$this->assertStringNotContainsString( 'rel="privacy-policy"', $output );
	}

	/**
	 * Tests that `Walker_Nav_Menu::start_el()` does not add `rel="privacy-policy"` when no URL
	 * is passed in the menu item object.
	 *
	 * @ticket 56345
	 *
	 * @covers Walker_Nav_Menu::start_el
	 */
	public function test_walker_nav_menu_start_el_should_not_add_rel_privacy_policy_when_no_url_is_passed() {

		$output = '';

		$item = array(
			'ID'        => self::$privacy_policy_id,
			'object_id' => self::$privacy_policy_id,
			'title'     => 'Privacy Policy',
			'target'    => '',
			'xfn'       => '',
			'current'   => false,
			// Do not pass URL.
		);

		$args = array(
			'before'      => '',
			'after'       => '',
			'link_before' => '',
			'link_after'  => '',
		);

		$this->walker->start_el( $output, (object) $item, 0, (object) $args );

		$this->assertStringNotContainsString( 'rel="privacy-policy"', $output );
	}

	/**
	 * Tests that `Walker_Nav_Menu::start_el()` does not add `rel="privacy-policy"` when the
	 * menu item's ID does not match the privacy policy page, but the URL does.
	 *
	 * @ticket 56345
	 *
	 * @covers Walker_Nav_Menu::start_el
	 */
	public function test_walker_nav_menu_start_el_should_add_rel_privacy_policy_when_id_does_not_match_but_url_does() {
		$output = '';

		// Ensure the ID does not match the privacy policy.
		$not_privacy_policy_id = self::$privacy_policy_id - 1;

		$item = array(
			'ID'        => $not_privacy_policy_id,
			'object_id' => $not_privacy_policy_id,
			'title'     => 'Privacy Policy',
			'target'    => '',
			'xfn'       => '',
			'current'   => false,
			'url'       => get_privacy_policy_url(),
		);

		$args = array(
			'before'      => '',
			'after'       => '',
			'link_before' => '',
			'link_after'  => '',
		);

		$this->walker->start_el( $output, (object) $item, 0, (object) $args );

		$this->assertStringContainsString( 'rel="privacy-policy"', $output );
	}

	/**
	 * Tests that `Walker_Nav_Menu::start_lvl()` applies 'nav_menu_submenu_attributes' filters.
	 *
	 * @ticket 57278
	 *
	 * @covers Walker_Nav_Menu::start_lvl
	 */
	public function test_start_lvl_should_apply_nav_menu_submenu_attributes_filters() {
		$output = '';
		$args   = (object) array(
			'before'      => '',
			'after'       => '',
			'link_before' => '',
			'link_after'  => '',
		);

		$filter = new MockAction();
		add_filter( 'nav_menu_submenu_attributes', array( $filter, 'filter' ) );

		$this->walker->start_lvl( $output, 0, $args );

		$this->assertSame( 1, $filter->get_call_count() );
	}

	/**
	 * Tests that `Walker_Nav_Menu::start_el()` applies 'nav_menu_item_attributes' filters.
	 *
	 * @ticket 57278
	 *
	 * @covers Walker_Nav_Menu::start_el
	 */
	public function test_start_el_should_apply_nav_menu_item_attributes_filters() {
		$output  = '';
		$post_id = self::factory()->post->create();
		$item    = (object) array(
			'ID'        => $post_id,
			'object_id' => $post_id,
			'title'     => get_the_title( $post_id ),
			'target'    => '',
			'xfn'       => '',
			'current'   => false,
		);
		$args    = (object) array(
			'before'      => '',
			'after'       => '',
			'link_before' => '',
			'link_after'  => '',
		);

		$filter = new MockAction();
		add_filter( 'nav_menu_item_attributes', array( $filter, 'filter' ) );

		$this->walker->start_el( $output, $item, 0, $args );

		$this->assertSame( 1, $filter->get_call_count() );
	}

	/**
	 * Tests that `Walker_Nav_Menu::build_atts()` builds attributes correctly.
	 *
	 * @ticket 57278
	 *
	 * @covers Walker_Nav_Menu::build_atts
	 *
	 * @dataProvider data_build_atts_should_build_attributes
	 *
	 * @param array  $atts     An array of HTML attribute key/value pairs.
	 * @param string $expected The expected built attributes.
	 */
	public function test_build_atts_should_build_attributes( $atts, $expected ) {
		$build_atts_reflection = new ReflectionMethod( $this->walker, 'build_atts' );

		$build_atts_reflection->setAccessible( true );
		$actual = $build_atts_reflection->invoke( $this->walker, $atts );
		$build_atts_reflection->setAccessible( false );

		$this->assertSame( $expected, $actual );
	}

	/**
	 * Test that get_privacy_policy_url() returns the correct URL.
	 */
	public function test_get_privacy_policy_url_returns_correct_url() {
		$expected_url = get_privacy_policy_url();
		$actual_url   = $this->invoke_private_method( 'get_privacy_policy_url' );

		$this->assertSame( $expected_url, $actual_url, 'The URL should match the privacy policy URL set in the option.' );
	}

	/**
	 * Test that get_privacy_policy_url() updates after cache reset.
	 */
	public function test_get_privacy_policy_url_updates_after_reset() {
		// Set initial privacy policy URL.
		$first_url = $this->invoke_private_method( 'get_privacy_policy_url' );

		// Change the privacy policy option.
		$new_policy_id = self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_title'  => 'New Privacy Policy',
				'post_status' => 'publish',
			)
		);
		update_option( 'wp_page_for_privacy_policy', $new_policy_id );

		// Reset the cache by setting the static property to null.
		$reflection = new ReflectionClass( 'Walker_Nav_Menu' );
		$property   = $reflection->getProperty( 'privacy_policy_url' );
		$property->setAccessible( true );
		$property->setValue( null );

		// Fetch the new URL.
		$new_url = $this->invoke_private_method( 'get_privacy_policy_url' );

		$this->assertNotSame( $first_url, $new_url, 'The URL should update after the cache is reset.' );
	}


	/**
	 * Data provider.
	 *
	 * @return array[]
	 */
	public function data_build_atts_should_build_attributes() {
		return array(
			'an empty attributes array'                   => array(
				'atts'     => array(),
				'expected' => '',
			),
			'attributes containing a (bool) false value'  => array(
				'atts'     => array( 'disabled' => false ),
				'expected' => '',
			),
			'attributes containing an empty string value' => array(
				'atts'     => array( 'id' => '' ),
				'expected' => '',
			),
			'attributes containing a non-scalar value'    => array(
				'atts'     => array( 'data-items' => new stdClass() ),
				'expected' => '',
			),
			'attributes containing a "href" -> should escape the URL' => array(
				'atts'     => array( 'href' => 'https://example.org/A File With Spaces.pdf' ),
				'expected' => ' href="https://example.org/A%20File%20With%20Spaces.pdf"',
			),
			'attributes containing a non-"href" attribute -> should escape the value' => array(
				'atts'     => array( 'id' => 'hello&goodbye' ),
				'expected' => ' id="hello&amp;goodbye"',
			),
		);
	}

	/**
	 * Helper method to call private methods.
	 */
	private function invoke_private_method( $method_name ) {
		$reflection = new ReflectionClass( 'Walker_Nav_Menu' );
		$method     = $reflection->getMethod( $method_name );
		$method->setAccessible( true );

		return $method->invoke( null );
	}
}
