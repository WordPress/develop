<?php

/**
 * Test paginate_links() trailing slash behavior.
 *
 * @group query
 * @group pagination
 */
class Tests_Query_Pagination_Trailing_Slash extends WP_UnitTestCase {

	/**
	 * Test that pagination links respect the permalink structure's trailing slash setting.
	 */
	public function test_pagination_links_trailing_slash_consistency() {
		$this->set_permalink_structure( '/%postname%' );

		$args = array(
			'base'      => 'http://example.org/category/test/%_%',
			'format'    => 'page/%#%',
			'total'     => 5,
			'current'   => 2,
			'prev_next' => true,
		);

		$links = paginate_links( $args );

		// Test page 1 link (should not have trailing slash).
		$this->assertStringContainsString(
			'href="http://example.org/category/test"',
			$links,
			'Page 1 link should not have trailing slash when permalink structure has no trailing slash'
		);

		// Test page 3 link (should not have trailing slash).
		$this->assertStringContainsString(
			'href="http://example.org/category/test/page/3"',
			$links,
			'Page 3 link should not have trailing slash when permalink structure has no trailing slash'
		);

		// Test previous link (should not have trailing slash).
		$this->assertStringContainsString(
			'class="prev page-numbers" href="http://example.org/category/test"',
			$links,
			'Previous link should not have trailing slash when permalink structure has no trailing slash'
		);
	}
}
