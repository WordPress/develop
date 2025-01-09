<?php
/**
 * Tests for the wp_get_speculation_rules() function.
 *
 * @package WordPress
 * @subpackage Speculative Loading
 */

/**
 * @group speculative-loading
 * @covers ::wp_get_speculation_rules
 */
class Tests_Speculative_Loading_wpGetSpeculationRules extends WP_UnitTestCase {

	private $prefetch_config  = array(
		'mode'      => 'prefetch',
		'eagerness' => 'conservative',
	);
	private $prerender_config = array(
		'mode'      => 'prerender',
		'eagerness' => 'conservative',
	);

	public function set_up() {
		parent::set_up();

		add_filter(
			'template_directory_uri',
			static function () {
				return content_url( 'themes/template' );
			}
		);

		add_filter(
			'stylesheet_directory_uri',
			static function () {
				return content_url( 'themes/stylesheet' );
			}
		);

		update_option( 'permalink_structure', '/%year%/%monthnum%/%day%/%postname%/' );
	}

	/**
	 * Tests speculation rules output with prefetch for the different eagerness levels.
	 *
	 * @ticket 62503
	 * @dataProvider data_eagerness
	 */
	public function test_wp_get_speculation_rules_with_prefetch( string $eagerness ) {
		$rules = wp_get_speculation_rules(
			array(
				'mode'      => 'prefetch',
				'eagerness' => $eagerness,
			)
		);

		$this->assertArrayHasKey( 'prefetch', $rules );
		$this->assertIsArray( $rules['prefetch'] );
		foreach ( $rules['prefetch'] as $entry ) {
			$this->assertIsArray( $entry );
			$this->assertArrayHasKey( 'source', $entry );
			$this->assertSame( 'document', $entry['source'] );
			$this->assertArrayHasKey( 'eagerness', $entry );
			$this->assertSame( $eagerness, $entry['eagerness'] );
		}
	}

	/**
	 * Tests speculation rules output with prerender for the different eagerness levels.
	 *
	 * @ticket 62503
	 * @dataProvider data_eagerness
	 */
	public function test_wp_get_speculation_rules_with_prerender( string $eagerness ) {
		$rules = wp_get_speculation_rules(
			array(
				'mode'      => 'prerender',
				'eagerness' => $eagerness,
			)
		);

		$this->assertArrayHasKey( 'prerender', $rules );
		$this->assertIsArray( $rules['prerender'] );
		foreach ( $rules['prerender'] as $entry ) {
			$this->assertIsArray( $entry );
			$this->assertArrayHasKey( 'source', $entry );
			$this->assertSame( 'document', $entry['source'] );
			$this->assertArrayHasKey( 'eagerness', $entry );
			$this->assertSame( $eagerness, $entry['eagerness'] );
		}
	}

	public function data_eagerness(): array {
		return array(
			array( 'conservative' ),
			array( 'moderate' ),
			array( 'eager' ),
		);
	}

	/**
	 * Tests that the number of entries included for prefetch configuration is correct.
	 *
	 * @ticket 62503
	 */
	public function test_wp_get_speculation_rules_prefetch_entries() {
		$rules = wp_get_speculation_rules( $this->prefetch_config );

		$this->assertArrayHasKey( 'prefetch', $rules );
		$this->assertCount( 4, $rules['prefetch'][0]['where']['and'] );
		$this->assertArrayHasKey( 'not', $rules['prefetch'][0]['where']['and'][3] );
		$this->assertArrayHasKey( 'selector_matches', $rules['prefetch'][0]['where']['and'][3]['not'] );
		$this->assertSame( '.no-prefetch', $rules['prefetch'][0]['where']['and'][3]['not']['selector_matches'] );
	}

	/**
	 * Tests that the number of entries included for prerender configuration is correct.
	 *
	 * @ticket 62503
	 */
	public function test_wp_get_speculation_rules_prerender_entries() {
		$rules = wp_get_speculation_rules( $this->prerender_config );

		$this->assertArrayHasKey( 'prerender', $rules );
		$this->assertCount( 4, $rules['prerender'][0]['where']['and'] );
		$this->assertArrayHasKey( 'not', $rules['prerender'][0]['where']['and'][3] );
		$this->assertArrayHasKey( 'selector_matches', $rules['prerender'][0]['where']['and'][3]['not'] );
		$this->assertSame( '.no-prerender', $rules['prerender'][0]['where']['and'][3]['not']['selector_matches'] );
	}

	/**
	 * Tests the default exclude paths and ensures they cannot be altered via filter.
	 *
	 * @ticket 62503
	 */
	public function test_wp_get_speculation_rules_href_exclude_paths() {
		$rules              = wp_get_speculation_rules( $this->prefetch_config );
		$href_exclude_paths = $rules['prefetch'][0]['where']['and'][1]['not']['href_matches'];

		$this->assertSameSets(
			array(
				'/wp-login.php',
				'/wp-admin/*',
				'/wp-content/uploads/*',
				'/wp-content/*',
				'/wp-content/plugins/*',
				'/wp-content/themes/stylesheet/*',
				'/wp-content/themes/template/*',
				'/*\\?(.+)',
			),
			$href_exclude_paths,
			'Snapshot: ' . var_export( $href_exclude_paths, true )
		);

		// Add filter that attempts to replace base exclude paths with a custom path to exclude.
		add_filter(
			'wp_speculation_rules_href_exclude_paths',
			static function () {
				return array( 'custom-file.php' );
			}
		);

		$rules              = wp_get_speculation_rules( $this->prefetch_config );
		$href_exclude_paths = $rules['prefetch'][0]['where']['and'][1]['not']['href_matches'];

		// Ensure the base exclude paths are still present and that the custom path was formatted correctly.
		$this->assertSameSets(
			array(
				'/wp-login.php',
				'/wp-admin/*',
				'/wp-content/uploads/*',
				'/wp-content/*',
				'/wp-content/plugins/*',
				'/wp-content/themes/stylesheet/*',
				'/wp-content/themes/template/*',
				'/*\\?(.+)',
				'/custom-file.php',
			),
			$href_exclude_paths,
			'Snapshot: ' . var_export( $href_exclude_paths, true )
		);
	}

	/**
	 * Tests the default exclude paths and ensures they cannot be altered via filter.
	 *
	 * @ticket 62503
	 */
	public function test_wp_get_speculation_rules_href_exclude_paths_without_pretty_permalinks() {
		update_option( 'permalink_structure', '' );

		$rules              = wp_get_speculation_rules( $this->prefetch_config );
		$href_exclude_paths = $rules['prefetch'][0]['where']['and'][1]['not']['href_matches'];

		$this->assertSameSets(
			array(
				'/wp-login.php',
				'/wp-admin/*',
				'/wp-content/uploads/*',
				'/wp-content/*',
				'/wp-content/plugins/*',
				'/wp-content/themes/stylesheet/*',
				'/wp-content/themes/template/*',
				'/*\\?*(^|&)_wpnonce=*',
			),
			$href_exclude_paths,
			'Snapshot: ' . var_export( $href_exclude_paths, true )
		);
	}

	/**
	 * Tests that exclude paths can be altered specifically based on the mode used.
	 *
	 * @ticket 62503
	 */
	public function test_wp_get_speculation_rules_href_exclude_paths_with_mode() {
		// Add filter that adds an exclusion only if the mode is 'prerender'.
		add_filter(
			'wp_speculation_rules_href_exclude_paths',
			static function ( $exclude_paths, $mode ) {
				if ( 'prerender' === $mode ) {
					$exclude_paths[] = '/products/*';
				}
				return $exclude_paths;
			},
			10,
			2
		);

		$rules              = wp_get_speculation_rules( $this->prerender_config );
		$href_exclude_paths = $rules['prerender'][0]['where']['and'][1]['not']['href_matches'];

		// Ensure the additional exclusion is present because the mode is 'prerender'.
		// Also ensure keys are sequential starting from 0 (that is, that array_is_list()).
		$this->assertSame(
			array(
				'/wp-login.php',
				'/wp-admin/*',
				'/wp-content/uploads/*',
				'/wp-content/*',
				'/wp-content/plugins/*',
				'/wp-content/themes/stylesheet/*',
				'/wp-content/themes/template/*',
				'/*\\?(.+)',
				'/products/*',
			),
			$href_exclude_paths,
			'Snapshot: ' . var_export( $href_exclude_paths, true )
		);

		// Redo with 'prefetch'.
		$rules              = wp_get_speculation_rules( $this->prefetch_config );
		$href_exclude_paths = $rules['prefetch'][0]['where']['and'][1]['not']['href_matches'];

		// Ensure the additional exclusion is not present because the mode is 'prefetch'.
		$this->assertSame(
			array(
				'/wp-login.php',
				'/wp-admin/*',
				'/wp-content/uploads/*',
				'/wp-content/*',
				'/wp-content/plugins/*',
				'/wp-content/themes/stylesheet/*',
				'/wp-content/themes/template/*',
				'/*\\?(.+)',
			),
			$href_exclude_paths,
			'Snapshot: ' . var_export( $href_exclude_paths, true )
		);
	}

	/**
	 * Tests filter that explicitly adds non-sequential keys.
	 *
	 * @ticket 62503
	 */
	public function test_wp_get_speculation_rules_with_filtering_bad_keys() {

		add_filter(
			'wp_speculation_rules_href_exclude_paths',
			static function ( array $exclude_paths ): array {
				$exclude_paths[] = '/next/';
				array_unshift( $exclude_paths, '/unshifted/' );
				$exclude_paths[-1]  = '/negative-one/';
				$exclude_paths[100] = '/one-hundred/';
				$exclude_paths['a'] = '/letter-a/';
				return $exclude_paths;
			}
		);

		$rules              = wp_get_speculation_rules( $this->prerender_config );
		$href_exclude_paths = $rules['prerender'][0]['where']['and'][1]['not']['href_matches'];
		$this->assertSame(
			array(
				'/wp-login.php',
				'/wp-admin/*',
				'/wp-content/uploads/*',
				'/wp-content/*',
				'/wp-content/plugins/*',
				'/wp-content/themes/stylesheet/*',
				'/wp-content/themes/template/*',
				'/*\\?(.+)',
				'/unshifted/',
				'/next/',
				'/negative-one/',
				'/one-hundred/',
				'/letter-a/',
			),
			$href_exclude_paths,
			'Snapshot: ' . var_export( $href_exclude_paths, true )
		);
	}

	/**
	 * Tests scenario when the home_url and site_url have different paths.
	 *
	 * @ticket 62503
	 */
	public function test_wp_get_speculation_rules_different_home_and_site_urls() {
		add_filter(
			'site_url',
			static function (): string {
				return 'https://example.com/wp/';
			}
		);
		add_filter(
			'home_url',
			static function (): string {
				return 'https://example.com/blog/';
			}
		);
		add_filter(
			'wp_speculation_rules_href_exclude_paths',
			static function ( array $exclude_paths ): array {
				$exclude_paths[] = '/store/*';
				return $exclude_paths;
			}
		);

		$rules              = wp_get_speculation_rules( $this->prerender_config );
		$href_exclude_paths = $rules['prerender'][0]['where']['and'][1]['not']['href_matches'];
		$this->assertSame(
			array(
				'/wp/wp-login.php',
				'/wp/wp-admin/*',
				'/wp-content/uploads/*',
				'/wp-content/*',
				'/wp-content/plugins/*',
				'/wp-content/themes/stylesheet/*',
				'/wp-content/themes/template/*',
				'/blog/*\\?(.+)',
				'/blog/store/*',
			),
			$href_exclude_paths,
			'Snapshot: ' . var_export( $href_exclude_paths, true )
		);
	}

	/**
	 * Tests that passing an invalid configuration to the function does not lead to unexpected problems.
	 *
	 * @ticket 62503
	 */
	public function test_wp_get_speculation_rules_with_invalid_configuration() {
		$this->setExpectedIncorrectUsage( 'wp_get_speculation_rules' );

		$rules = wp_get_speculation_rules(
			array(
				'mode'      => 'none',
				'eagerness' => 'none',
			)
		);

		$this->assertArrayHasKey( 'prefetch', $rules );
		$this->assertSame( 'conservative', $rules['prefetch'][0]['eagerness'] );
	}
}
