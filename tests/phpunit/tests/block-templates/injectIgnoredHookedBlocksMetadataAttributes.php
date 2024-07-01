<?php

require_once __DIR__ . '/base.php';

/**
 * @group block-templates
 * @covers ::inject_ignored_hooked_blocks_metadata_attributes
 */
class Tests_Block_Templates_InjectIgnoredHookedBlocksMetadataAttributes extends WP_Block_Templates_UnitTestCase {

	/**
	 * Tear down after each test.
	 *
	 * @since 6.5.3
	 */
	public function tear_down() {
		if ( WP_Block_Type_Registry::get_instance()->is_registered( 'tests/hooked-block' ) ) {
			unregister_block_type( 'tests/hooked-block' );
		}
		delete_post_meta( self::$template_part_post->ID, '_wp_ignored_hooked_blocks' );

		parent::tear_down();
	}

	/**
	 * @ticket 60754
	 */
	public function test_hooked_block_types_filter_with_newly_created_template() {
		$action = new MockAction();
		add_filter( 'hooked_block_types', array( $action, 'filter' ), 10, 4 );

		$changes               = new stdClass();
		$changes->post_type    = 'wp_template';
		$changes->post_status  = 'publish';
		$changes->post_content = '<!-- wp:tests/anchor-block -->Hello<!-- /wp:tests/anchor-block -->';
		$changes->tax_input    = array(
			'wp_theme' => get_stylesheet(),
		);

		inject_ignored_hooked_blocks_metadata_attributes( $changes );

		$args              = $action->get_args();
		$anchor_block_type = end( $args )[2];
		$context           = end( $args )[3];

		$this->assertSame( 'tests/anchor-block', $anchor_block_type );

		$this->assertInstanceOf( 'WP_Block_Template', $context );

		$this->assertSame(
			$changes->post_type,
			$context->type,
			'The type field of the context passed to the hooked_block_types filter doesn\'t match the template changes.'
		);
		$this->assertSame(
			$changes->post_status,
			$context->status,
			'The status field of the context passed to the hooked_block_types filter doesn\'t match the template changes.'
		);
		$this->assertSame(
			$changes->post_content,
			$context->content,
			'The content field of the context passed to the hooked_block_types filter doesn\'t match the template changes.'
		);
		$this->assertFalse(
			$context->has_theme_file,
			'The has_theme_file field of the context passed to the hooked_block_types filter isn\'t set to false.'
		);
	}

	/**
	 * @ticket 60754
	 */
	public function test_hooked_block_types_filter_with_newly_created_template_part() {
		$action = new MockAction();
		add_filter( 'hooked_block_types', array( $action, 'filter' ), 10, 4 );

		$changes               = new stdClass();
		$changes->post_type    = 'wp_template_part';
		$changes->post_status  = 'publish';
		$changes->post_content = '<!-- wp:tests/anchor-block -->Hello<!-- /wp:tests/anchor-block -->';
		$changes->tax_input    = array(
			'wp_theme'              => get_stylesheet(),
			'wp_template_part_area' => WP_TEMPLATE_PART_AREA_HEADER,
		);

		inject_ignored_hooked_blocks_metadata_attributes( $changes );

		$args = $action->get_args();

		$args_for_template_part = end( $args );
		$relative_position      = $args_for_template_part[1];
		$anchor_block_type      = $args_for_template_part[2];
		$context                = $args_for_template_part[3];

		$this->assertSame( 'last_child', $relative_position );
		$this->assertSame( 'core/template-part', $anchor_block_type );
		$this->assertInstanceOf( 'WP_Block_Template', $context );

		$this->assertSame(
			$changes->post_type,
			$context->type,
			'The type field of the context passed to the hooked_block_types filter doesn\'t match the template changes.'
		);
		$this->assertSame(
			$changes->post_status,
			$context->status,
			'The status field of the context passed to the hooked_block_types filter doesn\'t match the template changes.'
		);
		$this->assertSame(
			$changes->post_content,
			$context->content,
			'The content field of the context passed to the hooked_block_types filter doesn\'t match the template changes.'
		);
		$this->assertFalse(
			$context->has_theme_file,
			'The has_theme_file field of the context passed to the hooked_block_types filter isn\'t set to false.'
		);
		$this->assertSame(
			$changes->tax_input['wp_template_part_area'],
			$context->area,
			'The area field of the context passed to the hooked_block_types filter doesn\'t match the template changes.'
		);

		$args_for_anchor_block = prev( $args );
		$relative_position     = $args_for_template_part[1];
		$anchor_block_type     = $args_for_anchor_block[2];

		$this->assertSame( 'last_child', $relative_position );
		$this->assertSame( 'tests/anchor-block', $anchor_block_type );
		$this->assertSame( $context, $args_for_anchor_block[3] );
	}

	/**
	 * @ticket 60754
	 */
	public function test_hooked_block_types_filter_with_existing_template_file() {
		$action = new MockAction();
		add_filter( 'hooked_block_types', array( $action, 'filter' ), 10, 4 );

		$changes               = new stdClass();
		$changes->post_name    = 'index';
		$changes->post_type    = 'wp_template';
		$changes->post_status  = 'publish';
		$changes->post_content = '<!-- wp:tests/anchor-block -->Hello<!-- /wp:tests/anchor-block -->';
		$changes->meta_input   = array(
			'origin' => 'theme',
		);
		$changes->tax_input    = array(
			'wp_theme' => get_stylesheet(),
		);

		inject_ignored_hooked_blocks_metadata_attributes( $changes );

		$args              = $action->get_args();
		$anchor_block_type = end( $args )[2];
		$context           = end( $args )[3];

		$this->assertSame( 'tests/anchor-block', $anchor_block_type );

		$this->assertInstanceOf( 'WP_Block_Template', $context );

		$this->assertSame(
			$changes->post_name,
			$context->slug,
			'The slug field of the context passed to the hooked_block_types filter doesn\'t match the template changes.'
		);
		$this->assertSame(
			$changes->post_type,
			$context->type,
			'The type field of the context passed to the hooked_block_types filter doesn\'t match the template changes.'
		);
		$this->assertSame(
			$changes->post_status,
			$context->status,
			'The status field of the context passed to the hooked_block_types filter doesn\'t match the template changes.'
		);
		$this->assertSame(
			$changes->post_content,
			$context->content,
			'The content field of the context passed to the hooked_block_types filter doesn\'t match the template changes.'
		);
		$this->assertTrue(
			$context->has_theme_file,
			'The has_theme_file field of the context passed to the hooked_block_types filter isn\'t set to true.'
		);
		$this->assertSame(
			$changes->meta_input['origin'],
			$context->origin,
			'The origin field of the context passed to the hooked_block_types filter doesn\'t match the template changes.'
		);
	}

	/**
	 * @ticket 60754
	 */
	public function test_hooked_block_types_filter_with_existing_template_part_file() {
		$action = new MockAction();
		add_filter( 'hooked_block_types', array( $action, 'filter' ), 10, 4 );

		$changes               = new stdClass();
		$changes->post_name    = 'small-header';
		$changes->post_type    = 'wp_template_part';
		$changes->post_status  = 'publish';
		$changes->post_content = '<!-- wp:tests/anchor-block -->Hello<!-- /wp:tests/anchor-block -->';
		$changes->meta_input   = array(
			'origin' => 'theme',
		);
		$changes->tax_input    = array(
			'wp_theme'              => get_stylesheet(),
			'wp_template_part_area' => WP_TEMPLATE_PART_AREA_HEADER,
		);

		inject_ignored_hooked_blocks_metadata_attributes( $changes );

		$args              = $action->get_args();
		$anchor_block_type = end( $args )[2];
		$context           = end( $args )[3];

		$this->assertSame( 'tests/anchor-block', $anchor_block_type );

		$this->assertInstanceOf( 'WP_Block_Template', $context );

		$this->assertSame(
			$changes->post_name,
			$context->slug,
			'The slug field of the context passed to the hooked_block_types filter doesn\'t match the template changes.'
		);
		$this->assertSame(
			$changes->post_type,
			$context->type,
			'The type field of the context passed to the hooked_block_types filter doesn\'t match the template changes.'
		);
		$this->assertSame(
			$changes->post_status,
			$context->status,
			'The status field of the context passed to the hooked_block_types filter doesn\'t match the template changes.'
		);
		$this->assertSame(
			$changes->post_content,
			$context->content,
			'The content field of the context passed to the hooked_block_types filter doesn\'t match the template changes.'
		);
		$this->assertTrue(
			$context->has_theme_file,
			'The has_theme_file field of the context passed to the hooked_block_types filter isn\'t set to true.'
		);
		$this->assertSame(
			$changes->meta_input['origin'],
			$context->origin,
			'The origin field of the context passed to the hooked_block_types filter doesn\'t match the template changes.'
		);
		$this->assertSame(
			$changes->tax_input['wp_template_part_area'],
			$context->area,
			'The area field of the context passed to the hooked_block_types filter doesn\'t match the template changes.'
		);
	}

	/**
	 * @ticket 60754
	 */
	public function test_hooked_block_types_filter_with_existing_template_post() {
		$action = new MockAction();
		add_filter( 'hooked_block_types', array( $action, 'filter' ), 10, 4 );

		$changes               = new stdClass();
		$changes->post_name    = 'my-updated-template';
		$changes->ID           = self::$template_post->ID;
		$changes->post_content = '<!-- wp:tests/anchor-block -->Hello<!-- /wp:tests/anchor-block -->';

		inject_ignored_hooked_blocks_metadata_attributes( $changes );

		$args              = $action->get_args();
		$anchor_block_type = end( $args )[2];
		$context           = end( $args )[3];

		$this->assertSame( 'tests/anchor-block', $anchor_block_type );

		$this->assertInstanceOf( 'WP_Block_Template', $context );

		$this->assertSame(
			$changes->post_name,
			$context->slug,
			'The slug field of the context passed to the hooked_block_types filter doesn\'t match the template changes.'
		);
		$this->assertSame(
			$changes->ID,
			$context->wp_id,
			'The wp_id field of the context passed to the hooked_block_types filter doesn\'t match the template changes.'
		);
		$this->assertSame(
			'publish',
			$context->status,
			'The status field of the context passed to the hooked_block_types filter isn\'t set to publish.'
		);
		$this->assertSame(
			$changes->post_content,
			$context->content,
			'The content field of the context passed to the hooked_block_types filter doesn\'t match the template changes.'
		);

		$this->assertSame(
			self::$template_post->post_title,
			$context->title,
			'The title field of the context passed to the hooked_block_types filter doesn\'t match the template post object.'
		);
		$this->assertSame(
			self::$template_post->post_excerpt,
			$context->description,
			'The description field of the context passed to the hooked_block_types filter doesn\'t match the template post object.'
		);
	}

	/**
	 * @ticket 60754
	 */
	public function test_hooked_block_types_filter_with_existing_template_part_post() {
		$action = new MockAction();
		add_filter( 'hooked_block_types', array( $action, 'filter' ), 10, 4 );

		$changes               = new stdClass();
		$changes->post_name    = 'my-updated-template-part';
		$changes->ID           = self::$template_part_post->ID;
		$changes->post_content = '<!-- wp:tests/anchor-block -->Hello<!-- /wp:tests/anchor-block -->';

		$changes->tax_input = array(
			'wp_template_part_area' => WP_TEMPLATE_PART_AREA_FOOTER,
		);

		inject_ignored_hooked_blocks_metadata_attributes( $changes );

		$args              = $action->get_args();
		$anchor_block_type = end( $args )[2];
		$context           = end( $args )[3];

		$this->assertSame( 'tests/anchor-block', $anchor_block_type );

		$this->assertInstanceOf( 'WP_Block_Template', $context );

		$this->assertSame(
			$changes->post_name,
			$context->slug,
			'The slug field of the context passed to the hooked_block_types filter doesn\'t match the template changes.'
		);
		$this->assertSame(
			$changes->ID,
			$context->wp_id,
			'The wp_id field of the context passed to the hooked_block_types filter doesn\'t match the template changes.'
		);
		$this->assertSame(
			'publish',
			$context->status,
			'The status field of the context passed to the hooked_block_types filter isn\'t set to publish.'
		);
		$this->assertSame(
			$changes->post_content,
			$context->content,
			'The content field of the context passed to the hooked_block_types filter doesn\'t match the template changes.'
		);
		$this->assertSame(
			$changes->tax_input['wp_template_part_area'],
			$context->area,
			'The area field of the context passed to the hooked_block_types filter doesn\'t match the template changes.'
		);

		$this->assertSame(
			self::$template_part_post->post_title,
			$context->title,
			'The title field of the context passed to the hooked_block_types filter doesn\'t match the template post object.'
		);
		$this->assertSame(
			self::$template_part_post->post_excerpt,
			$context->description,
			'The description field of the context passed to the hooked_block_types filter doesn\'t match the template post object.'
		);
	}

	/**
	 * @ticket 60671
	 */
	public function test_inject_ignored_hooked_blocks_metadata_attributes_into_template() {
		register_block_type(
			'tests/hooked-block',
			array(
				'block_hooks' => array(
					'tests/anchor-block' => 'after',
				),
			)
		);

		$id       = self::TEST_THEME . '//' . 'my_template';
		$template = get_block_template( $id, 'wp_template' );

		$changes               = new stdClass();
		$changes->ID           = $template->wp_id;
		$changes->post_content = '<!-- wp:tests/anchor-block -->Hello<!-- /wp:tests/anchor-block -->';

		$post = inject_ignored_hooked_blocks_metadata_attributes( $changes );
		$this->assertSame(
			'<!-- wp:tests/anchor-block {"metadata":{"ignoredHookedBlocks":["tests/hooked-block"]}} -->Hello<!-- /wp:tests/anchor-block -->',
			$post->post_content,
			'The hooked block was not injected into the anchor block\'s ignoredHookedBlocks metadata.'
		);
	}

	/**
	 * @ticket 60671
	 */
	public function test_inject_ignored_hooked_blocks_metadata_attributes_into_template_part() {
		register_block_type(
			'tests/hooked-block',
			array(
				'block_hooks' => array(
					'tests/anchor-block' => 'after',
				),
			)
		);

		$id       = self::TEST_THEME . '//' . 'my_template_part';
		$template = get_block_template( $id, 'wp_template_part' );

		$changes               = new stdClass();
		$changes->ID           = $template->wp_id;
		$changes->post_content = '<!-- wp:tests/anchor-block -->Hello<!-- /wp:tests/anchor-block -->';

		$post = inject_ignored_hooked_blocks_metadata_attributes( $changes );
		$this->assertSame(
			'<!-- wp:tests/anchor-block {"metadata":{"ignoredHookedBlocks":["tests/hooked-block"]}} -->Hello<!-- /wp:tests/anchor-block -->',
			$post->post_content,
			'The hooked block was not injected into the anchor block\'s ignoredHookedBlocks metadata.'
		);
	}

	/**
	 * @ticket 60854
	 */
	public function test_inject_ignored_hooked_blocks_metadata_attributes_into_template_part_postmeta() {
		register_block_type(
			'tests/hooked-block',
			array(
				'block_hooks' => array(
					'core/template-part' => 'last_child',
				),
			)
		);

		$id       = self::TEST_THEME . '//' . 'my_template_part';
		$template = get_block_template( $id, 'wp_template_part' );

		$changes               = new stdClass();
		$changes->ID           = $template->wp_id;
		$changes->post_content = '<!-- wp:tests/anchor-block -->Hello<!-- /wp:tests/anchor-block -->';

		$post = inject_ignored_hooked_blocks_metadata_attributes( $changes );
		$this->assertSame(
			array( 'tests/hooked-block' ),
			json_decode( $post->meta_input['_wp_ignored_hooked_blocks'], true ),
			'The hooked block was not injected into the wp_template_part\'s _wp_ignored_hooked_blocks postmeta.'
		);
		$this->assertSame(
			$changes->post_content,
			$post->post_content,
			'The template part\'s post content was modified.'
		);
	}
}
