<?php
/**
 * Class 'WP_Speculation_Rules'.
 *
 * @package WordPress
 * @subpackage Speculative Loading
 * @since 6.8.0
 */

/**
 * Class representing a set of speculation rules.
 *
 * @since 6.8.0
 * @access private
 */
class WP_Speculation_Rules implements JsonSerializable {

	/**
	 * Stored rules, as a map of `$mode => $rules` pairs.
	 *
	 * Every `$rules` value is a map of `$id => $rule` pairs.
	 *
	 * @since 6.8.0
	 * @var array<string, array<string, mixed>>
	 */
	private $rules_by_mode;

	/**
	 * Adds a speculation rule to the speculation rules to consider.
	 *
	 * @since 6.8.0
	 *
	 * @param string               $mode Speculative loading mode. Either 'prefetch' or 'prerender'.
	 * @param string               $id   Unique string identifier for the speculation rule.
	 * @param array<string, mixed> $rule Associative array of rule arguments.
	 * @return bool True on success, false if invalid parameters are provided.
	 */
	public function add_rule( string $mode, string $id, array $rule ): bool {
		if ( ! wp_is_valid_speculation_rules_mode( $mode ) ) {
			_doing_it_wrong(
				__METHOD__,
				sprintf(
					/* translators: %s: invalid mode value */
					__( 'The value "%s" is not a valid speculation rules mode.' ),
					esc_html( $mode )
				),
				'6.8.0'
			);
			return false;
		}

		if ( ! $this->is_valid_id( $id ) ) {
			_doing_it_wrong(
				__METHOD__,
				sprintf(
					/* translators: %s: invalid ID value */
					__( 'The value "%s" is not a valid ID for a speculation rule.' ),
					esc_html( $id )
				),
				'6.8.0'
			);
			return false;
		}

		if ( $this->has_rule( $mode, $id ) ) {
			_doing_it_wrong(
				__METHOD__,
				sprintf(
					/* translators: %s: invalid ID value */
					__( 'A speculation rule with ID "%s" already exists.' ),
					esc_html( $id )
				),
				'6.8.0'
			);
			return false;
		}

		/*
		 * Perform some basic speculation rule validation.
		 * Every rule must have either a 'where' key or a 'urls' key, but not both.
		 * The presence of a 'where' key implies a 'source' of 'document', while the presence of a 'urls' key implies
		 * a 'source' of 'list'.
		 */
		if (
			( ! isset( $rule['where'] ) && ! isset( $where['urls'] ) ) ||
			( isset( $rule['where'] ) && isset( $rule['urls'] ) )
		) {
			_doing_it_wrong(
				__METHOD__,
				sprintf(
					/* translators: 1: allowed key, 2: alternative allowed key */
					__( 'A speculation rule must include either a "%1$s" key or a "%2$s" key, but not both.' ),
					'where',
					'urls'
				),
				'6.8.0'
			);
			return false;
		}
		if ( isset( $rule['source'] ) ) {
			if ( ! wp_is_valid_speculation_rules_source( $rule['source'] ) ) {
				_doing_it_wrong(
					__METHOD__,
					sprintf(
						/* translators: %s: invalid source value */
						__( 'The value "%s" is not a valid source for a speculation rule.' ),
						esc_html( $rule['source'] )
					),
					'6.8.0'
				);
				return false;
			}

			if ( 'list' === $rule['source'] && isset( $rule['where'] ) ) {
				_doing_it_wrong(
					__METHOD__,
					sprintf(
						/* translators: 1: source value, 2: forbidden key */
						__( 'A speculation rule of source "%1$s" must not include a "%2$s" key.' ),
						'list',
						'where'
					),
					'6.8.0'
				);
				return false;
			}

			if ( 'document' === $rule['source'] && isset( $rule['urls'] ) ) {
				_doing_it_wrong(
					__METHOD__,
					sprintf(
						/* translators: 1: source value, 2: forbidden key */
						__( 'A speculation rule of source "%1$s" must not include a "%2$s" key.' ),
						'document',
						'urls'
					),
					'6.8.0'
				);
				return false;
			}
		}

		// If there is an 'eagerness' key specified, make sure it's valid.
		if ( isset( $rule['eagerness'] ) && ! wp_is_valid_speculation_rules_eagerness( $rule['eagerness'] ) ) {
			_doing_it_wrong(
				__METHOD__,
				sprintf(
					/* translators: %s: invalid eagerness value */
					__( 'The value "%s" is not a valid eagerness for a speculation rule.' ),
					esc_html( $rule['eagerness'] )
				),
				'6.8.0'
			);
			return false;
		}

		if ( ! isset( $this->rules_by_mode[ $mode ] ) ) {
			$this->rules_by_mode[ $mode ] = array();
		}

		$this->rules_by_mode[ $mode ][ $id ] = $rule;
		return true;
	}

	/**
	 * Checks whether a speculation rule for the given mode and ID already exists.
	 *
	 * @since 6.8.0
	 *
	 * @param string $mode Speculative loading mode. Either 'prefetch' or 'prerender'.
	 * @param string $id   Unique string identifier for the speculation rule.
	 * @return bool True if the rule already exists, false otherwise.
	 */
	public function has_rule( string $mode, string $id ): bool {
		return isset( $this->rules_by_mode[ $mode ][ $id ] );
	}

	/**
	 * Returns the associative array representation of the speculation rules data.
	 *
	 * @since 6.8.0
	 *
	 * @return array<string, array<string, mixed>> Speculation rules data.
	 */
	public function to_array(): array {
		return array_filter( $this->rules_by_mode );
	}

	/**
	 * Returns the speculation rules data ready to be JSON-encoded.
	 *
	 * @since 6.8.0
	 *
	 * @return array<string, array<string, mixed>> Speculation rules data.
	 */
	#[ReturnTypeWillChange]
	public function jsonSerialize() {
		// For the JSON output, we need to strip the IDs, which are not relevant for the Speculation Rules API.
		return array_map(
			static function ( array $rules ) {
				return array_values( $rules );
			},
			$this->to_array()
		);
	}

	/**
	 * Checks whether the given ID is valid.
	 *
	 * @since 6.8.0
	 *
	 * @param string $id Unique string identifier for the speculation rule.
	 * @return bool True if the ID is valid, false otherwise.
	 */
	private function is_valid_id( string $id ): bool {
		return (bool) preg_match( '/^[a-z][a-z0-9_-]+$/', $id );
	}
}
