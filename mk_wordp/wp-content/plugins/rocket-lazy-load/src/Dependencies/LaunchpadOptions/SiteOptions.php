<?php

namespace RocketLazyLoadPlugin\Dependencies\LaunchpadOptions;

use RocketLazyLoadPlugin\Dependencies\LaunchpadOptions\Interfaces\SiteOptionsInterface;
use RocketLazyLoadPlugin\Dependencies\LaunchpadOptions\Traits\PrefixedKeyTrait;

class SiteOptions implements SiteOptionsInterface {

	use PrefixedKeyTrait;

	/**
	 * Constructor
	 *
	 * @param string $prefix options prefix.
	 */
	public function __construct( string $prefix = '' ) {
		$this->prefix = $prefix;
	}

	/**
	 * Deletes the option with the given name.
	 *
	 * @param string $name Name of the option to delete.
	 *
	 * @return void
	 */
	public function delete( string $name ) {
		delete_site_option( $this->get_full_key( $name ) );
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
		$option = get_site_option( $this->get_full_key( $name ), $default );

		if ( is_array( $default ) && ! is_array( $option ) ) {
			$option = (array) $option;
		}

		return $option;
	}

	/**
	 * Gets the option name used to store the option in the WordPress database.
	 *
	 * @param string $name Unprefixed name of the option.
	 * @deprecated Only for WP Rocket backward compatibility.
	 * @return string
	 */
	public function get_option_name( string $name ): string {
		return $this->get_full_key( $name );
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
		update_site_option( $this->get_full_key( $name ), $value );
	}
}
