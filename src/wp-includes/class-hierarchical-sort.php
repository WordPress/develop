<?php

/**
 * Implements sorting posts by parent-child relationship.
 *
 * @package WordPress
 * @since   6.8.0
 */

/**
 * Sort post by hierarchy (parent-child relationship).
 *
 * @since 6.8.0
 */
class Hierarchical_Sort {

	/**
	 * Check if the request is eligible for hierarchical sorting.
	 *
	 * @param array $request The request data.
	 *
	 * @return bool Return true if the request is eligible for hierarchical sorting.
	 */
	public static function is_eligible( $request ) {
		if ( ! isset( $request['orderby_hierarchy'] ) || true !== $request['orderby_hierarchy'] ) {
			return false;
		}

		return true;
	}

	public static function run( $args ) {
		$new_args = array_merge(
			$args,
			array(
				'fields'         => 'id=>parent',
				'posts_per_page' => -1,
			)
		);
		$query    = new WP_Query( $new_args );
		$posts    = $query->posts;

		return self::sort( $posts );
	}

	private static function get_ancestor( $post_id ) {
		return get_post( $post_id )->post_parent ?? 0;
	}

	/**
	 * Sort posts by hierarchy.
	 *
	 * Takes an array of posts and sorts them based on their parent-child relationships.
	 * It also tracks the level depth of each post in the hierarchy.
	 *
	 * Example input:
	 * ```
	 * [
	 *   ['ID' => 4, 'post_parent' => 2],
	 *   ['ID' => 2, 'post_parent' => 0],
	 *   ['ID' => 3, 'post_parent' => 2],
	 * ]
	 * ```
	 *
	 * Example output:
	 * ```
	 * [
	 *   'post_ids' => [2, 4, 3],
	 *   'levels'   => [0, 1, 1]
	 * ]
	 * ```
	 *
	 * @param array $posts Array of post objects containing ID and post_parent properties.
	 *
	 * @return array {
	 *     Sorted post IDs and their hierarchical levels
	 *
	 *     @type array $post_ids Array of post IDs
	 *     @type array $levels   Array of levels for the corresponding post ID in the same index
	 * }
	 */
	private static function sort( $posts ) {
		/*
		 * Arrange pages in two arrays:
		 *
		 * - $top_level: posts whose parent is 0
		 * - $children: post ID as the key and an array of children post IDs as the value.
		 *   Example: $children[10][] contains all sub-pages whose parent is 10.
		 *
		 * Additionally, keep track of the levels of each post in $levels.
		 * Example: $levels[10] = 0 means the post ID is a top-level page.
		 *
		 */
		$top_level = array();
		$children  = array();
		foreach ( $posts as $post ) {
			if ( empty( $post->post_parent ) ) {
				$top_level[] = $post->ID;
			} else {
				$children[ $post->post_parent ][] = $post->ID;
			}
		}

		$ids    = array();
		$levels = array();
		self::add_hierarchical_ids( $ids, $levels, 0, $top_level, $children );

		// Process remaining children.
		if ( ! empty( $children ) ) {
			foreach ( $children as $parent_id => $child_ids ) {
				$level    = 0;
				$ancestor = $parent_id;
				while ( 0 !== $ancestor ) {
					++$level;
					$ancestor = self::get_ancestor( $ancestor );
				}
				self::add_hierarchical_ids( $ids, $levels, $level, $child_ids, $children );
			}
		}

		return array(
			'post_ids' => $ids,
			'levels'   => $levels,
		);
	}

	private static function add_hierarchical_ids( &$ids, &$levels, $level, $to_process, $children ) {
		foreach ( $to_process as $id ) {
			if ( in_array( $id, $ids, true ) ) {
				continue;
			}
			$ids[]         = $id;
			$levels[ $id ] = $level;

			if ( isset( $children[ $id ] ) ) {
				self::add_hierarchical_ids( $ids, $levels, $level + 1, $children[ $id ], $children );
				unset( $children[ $id ] );
			}
		}
	}
}
