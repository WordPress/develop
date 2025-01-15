<?php
/**
 * Block Metadata Registry
 *
 * @package WordPress
 * @subpackage Blocks
 * @since 6.7.0
 */

/**
 * Class used for managing block metadata collections.
 *
 * The WP_Block_Metadata_Registry allows plugins to register metadata for large
 * collections of blocks (e.g., 50-100+) using a single PHP file. This approach
 * reduces the need to read and decode multiple `block.json` files, enhancing
 * performance through opcode caching.
 *
 * @since 6.7.0
 */
class WP_Block_Metadata_Registry {

	/**
	 * Container for storing block metadata collections.
	 *
	 * Each entry maps a base path to its corresponding metadata and callback.
	 *
	 * @since 6.7.0
	 * @var array<string, array<string, mixed>>
	 */
	private static $collections = array();

	/**
	 * Caches the last matched collection path for performance optimization.
	 *
	 * @since 6.7.0
	 * @var string|null
	 */
	private static $last_matched_collection = null;

	/**
	 * Stores the default allowed collection root paths.
	 *
	 * @since 6.8.0
	 * @var string[]|null
	 */
	private static $default_allowed_collection_roots = null;

	/**
	 * Registers a block metadata collection.
	 *
	 * This method allows registering a collection of block metadata from a single
	 * manifest file, improving performance for large sets of blocks.
	 *
	 * The manifest file should be a PHP file that returns an associative array, where
	 * the keys are the block identifiers (without their namespace) and the values are
	 * the corresponding block metadata arrays. The block identifiers must match the
	 * parent directory name for the respective `block.json` file.
	 *
	 * Example manifest file structure:
	 * ```
	 * return array(
	 *     'example-block' => array(
	 *         'title' => 'Example Block',
	 *         'category' => 'widgets',
	 *         'icon' => 'smiley',
	 *         // ... other block metadata
	 *     ),
	 *     'another-block' => array(
	 *         'title' => 'Another Block',
	 *         'category' => 'formatting',
	 *         'icon' => 'star-filled',
	 *         // ... other block metadata
	 *     ),
	 *     // ... more block metadata entries
	 * );
	 * ```
	 *
	 * @since 6.7.0
	 *
	 * @param string $path     The absolute base path for the collection ( e.g., WP_PLUGIN_DIR . '/my-plugin/blocks/' ).
	 * @param string $manifest The absolute path to the manifest file containing the metadata collection.
	 * @return bool True if the collection was registered successfully, false otherwise.
	 */
	public static function register_collection( $path, $manifest ) {
		$path = wp_normalize_path( rtrim( $path, '/' ) );

		$allowed_collection_roots = self::get_default_allowed_collection_roots();

		/**
		 * Filters in which root directory paths block metadata collections are allowed.
		 *
		 * The default list encompasses the `wp-includes` directory, as well as the root directories for plugins,
		 * must-use plugins, and themes. This filter can be used to expand the list, e.g. to custom directories with
		 * symlinked plugins.
		 *
		 * Any block metadata collection that is registered must be within one of these directories. It must however
		 * not match any of these directories exactly, as then the collection may conflict with another one within the
		 * same root.
		 *
		 * @since 6.8.0
		 *
		 * @param string[] $allowed_collection_roots List of allowed metadata collection root paths.
		 */
		$allowed_collection_roots = apply_filters( 'wp_allowed_block_metadata_collection_roots', $allowed_collection_roots );

		$allowed_collection_roots = array_unique(
			array_map(
				static function ( $allowed_root ) {
					return rtrim( $allowed_root, '/' );
				},
				$allowed_collection_roots
			)
		);

		// Check if the path is valid:
		if ( ! self::is_valid_collection_path( $path, $allowed_collection_roots ) ) {
			_doing_it_wrong(
				__METHOD__,
				sprintf(
					__( 'Block metadata collections can only be registered within one of the following directories: %s' ),
					esc_html( implode( wp_get_list_item_separator(), $allowed_collection_roots ) )
				),
				'6.8.0'
			);
			return false;
		}

		if ( ! file_exists( $manifest ) ) {
			_doing_it_wrong(
				__METHOD__,
				__( 'The specified manifest file does not exist.' ),
				'6.7.0'
			);
			return false;
		}

		self::$collections[ $path ] = array(
			'manifest' => $manifest,
			'metadata' => null,
		);

		return true;
	}

	/**
	 * Retrieves block metadata for a given block within a specific collection.
	 *
	 * This method uses the registered collections to efficiently lookup
	 * block metadata without reading individual `block.json` files.
	 *
	 * @since 6.7.0
	 *
	 * @param string $file_or_folder The path to the file or folder containing the block.
	 * @return array|null The block metadata for the block, or null if not found.
	 */
	public static function get_metadata( $file_or_folder ) {
		$path = self::find_collection_path( $file_or_folder );
		if ( ! $path ) {
			return null;
		}

		$collection = &self::$collections[ $path ];

		if ( null === $collection['metadata'] ) {
			// Load the manifest file if not already loaded
			$collection['metadata'] = require $collection['manifest'];
		}

		// Get the block name from the path.
		$block_name = self::default_identifier_callback( $file_or_folder );

		return isset( $collection['metadata'][ $block_name ] ) ? $collection['metadata'][ $block_name ] : null;
	}

	/**
	 * Finds the collection path for a given file or folder.
	 *
	 * @since 6.7.0
	 *
	 * @param string $file_or_folder The path to the file or folder.
	 * @return string|null The collection path if found, or null if not found.
	 */
	private static function find_collection_path( $file_or_folder ) {
		if ( empty( $file_or_folder ) ) {
			return null;
		}

		// Check the last matched collection first, since block registration usually happens in batches per plugin or theme.
		$path = wp_normalize_path( rtrim( $file_or_folder, '/' ) );
		if ( self::$last_matched_collection && str_starts_with( $path, self::$last_matched_collection ) ) {
			return self::$last_matched_collection;
		}

		$collection_paths = array_keys( self::$collections );
		foreach ( $collection_paths as $collection_path ) {
			if ( str_starts_with( $path, $collection_path ) ) {
				self::$last_matched_collection = $collection_path;
				return $collection_path;
			}
		}
		return null;
	}

	/**
	 * Checks if metadata exists for a given block name in a specific collection.
	 *
	 * @since 6.7.0
	 *
	 * @param string $file_or_folder The path to the file or folder containing the block metadata.
	 * @return bool True if metadata exists for the block, false otherwise.
	 */
	public static function has_metadata( $file_or_folder ) {
		return null !== self::get_metadata( $file_or_folder );
	}

	/**
	 * Default identifier function to determine the block identifier from a given path.
	 *
	 * This function extracts the block identifier from the path:
	 * - For 'block.json' files, it uses the parent directory name.
	 * - For directories, it uses the directory name itself.
	 * - For empty paths, it returns an empty string.
	 *
	 * For example:
	 * - Path: '/wp-content/plugins/my-plugin/blocks/example/block.json'
	 *   Identifier: 'example'
	 * - Path: '/wp-content/plugins/my-plugin/blocks/another-block'
	 *   Identifier: 'another-block'
	 *
	 * This default behavior matches the standard WordPress block structure.
	 *
	 * @since 6.7.0
	 *
	 * @param string $path The file or folder path to determine the block identifier from.
	 * @return string The block identifier, or an empty string if the path is empty.
	 */
	private static function default_identifier_callback( $path ) {
		// Ensure $path is not empty to prevent unexpected behavior.
		if ( empty( $path ) ) {
			return '';
		}

		if ( str_ends_with( $path, 'block.json' ) ) {
			// Return the parent directory name if it's a block.json file.
			return basename( dirname( $path ) );
		}

		// Otherwise, assume it's a directory and return its name.
		return basename( $path );
	}

	/**
	 * Checks whether the given block metadata collection path is valid against the list of allowed collection roots.
	 *
	 * @since 6.8.0
	 *
	 * @param string   $path                     Block metadata collection path, without trailing slash.
	 * @param string[] $allowed_collection_roots List of allowed collection root paths, without trailing slashes.
	 * @return bool True if the path is allowed, false otherwise.
	 */
	private static function is_valid_collection_path( $path, $allowed_collection_roots ) {
		$matching_root_found = false;

		foreach ( $allowed_collection_roots as $allowed_root ) {
			// If the path matches any allowed root exactly, it is invalid.
			if ( $allowed_root === $path ) {
				return false;
			}

			// Otherwise, if the path is within any of the allowed roots, it is valid.
			if ( str_starts_with( $path, $allowed_root ) ) {
				$matching_root_found = true;
			}
		}

		return $matching_root_found;
	}

	/**
	 * Gets the default allowed collection root paths.
	 *
	 * @since 6.8.0
	 *
	 * @return string[] List of directory paths within which metadata collections are allowed.
	 */
	private static function get_default_allowed_collection_roots() {
		if ( isset( self::$default_allowed_collection_roots ) ) {
			return self::$default_allowed_collection_roots;
		}

		$allowed_collection_roots = array(
			wp_normalize_path( ABSPATH . WPINC ),
			wp_normalize_path( WPMU_PLUGIN_DIR ),
			wp_normalize_path( WP_PLUGIN_DIR ),
		);

		$theme_roots = get_theme_roots();
		if ( ! is_array( $theme_roots ) ) {
			$theme_roots = array( $theme_roots );
		}
		foreach ( $theme_roots as $theme_root ) {
			$allowed_collection_roots[] = trailingslashit( wp_normalize_path( WP_CONTENT_DIR ) ) . ltrim( wp_normalize_path( $theme_root ), '/' );
		}

		self::$default_allowed_collection_roots = array_unique( $allowed_collection_roots );
		return self::$default_allowed_collection_roots;
	}
}
