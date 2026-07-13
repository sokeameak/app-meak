<?php

namespace RocketLazyLoadPlugin\Dependencies\LaunchpadOptions;

use RocketLazyLoadPlugin\Dependencies\LaunchpadOptions\Interfaces\SetInterface;

class Set implements SetInterface {

	/**
	 * Set values.
	 *
	 * @var array
	 */
	protected $values = [];

	/**
	 * Set slug for filters.
	 *
	 * @var string
	 */
	protected $slug = '';

	/**
	 * Instantiate the Set.
	 *
	 * @param array  $values Set values.
	 * @param string $slug Set slug for filters.
	 */
	public function __construct( $values, string $slug ) {
		$this->values = (array) $values;
		$this->slug   = $slug;
	}


	/**
	 * Deletes the option with the given name.
	 *
	 * @param string $name Name of the option to delete.
	 *
	 * @return void
	 */
	public function delete( string $name ) {
		unset( $this->values[ $name ] );
	}

	/**
	 * Gets the value for the given name. Returns the default value if the value does not exist.
	 *
	 * @param string $name   Name of the option to get.
	 * @param mixed  $default Default value to return if the value does not exist.
	 *
	 * @return mixed
	 */
	public function get( string $name, $default = null ) {
		/**
		 * Pre-filter any setting before read
		 *
		 * @param mixed $default The default value.
		 */
		$value = apply_filters( "pre_get_{$this->slug}" . $name, null, $default ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound

		if ( null !== $value ) {
			return $value;
		}

		if ( ! $this->has( $name ) ) {
			return $default;
		}

		/**
		 * Filter any setting after read
		 *
		 * @param mixed $default The default value.
		 */
		return apply_filters( "get_{$this->slug}" . $name, $this->values[ $name ], $default ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
	}

	/**
	 * Checks if the option with the given name exists.
	 *
	 * @param string $name Name of the option to check.
	 *
	 * @return bool
	 */
	public function has( string $name ): bool {
		return key_exists( $name, $this->values );
	}

	/**
	 * Sets the value of an option. Update the value if the option for the given name already exists.
	 *
	 * @param string $name Name of the option to set.
	 * @param mixed  $value Value to set for the option.
	 *
	 * @return void
	 */
	public function set( string $name, $value ) {
		$this->values[ $name ] = $value;
	}

	/**
	 * Sets multiple values.
	 *
	 * @param array $values An array of key/value pairs to set.
	 *
	 * @return void
	 */
	public function set_values( array $values ) {
		foreach ( $values as $name => $value ) {
			$this->values[ $name ] = $value;
		}
	}

	/**
	 * Gets the set values.
	 *
	 * @return array
	 */
	public function get_values(): array {
		$output = [];

		foreach ( $this->values as $name => $value ) {
			$output[ $name ] = $this->get( $name );
		}

		return $output;
	}

		/**
		 * Gets the option array.
		 *
		 * @deprecated Only for WP Rocket backward compatibility.
		 * @return array
		 */
	public function get_options(): array {
		return $this->get_values();
	}
}
