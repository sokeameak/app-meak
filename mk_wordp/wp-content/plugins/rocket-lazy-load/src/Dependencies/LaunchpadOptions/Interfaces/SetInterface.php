<?php

namespace RocketLazyLoadPlugin\Dependencies\LaunchpadOptions\Interfaces;

use RocketLazyLoadPlugin\Dependencies\LaunchpadOptions\Interfaces\Actions\DeleteInterface;
use RocketLazyLoadPlugin\Dependencies\LaunchpadOptions\Interfaces\Actions\FetchInterface;

interface SetInterface extends DeleteInterface, FetchInterface, \RocketLazyLoadPlugin\Dependencies\LaunchpadOptions\Interfaces\Actions\SetInterface {

	/**
	 * Sets multiple values.
	 *
	 * @param array $values An array of key/value pairs to set.
	 *
	 * @return void
	 */
	public function set_values( array $values );

	/**
	 * Gets the set values.
	 *
	 * @return array
	 */
	public function get_values(): array;

	/**
	 * Gets the option array.
	 *
	 * @deprecated Only for WP Rocket backward compatibility.
	 * @return array
	 */
	public function get_options(): array;
}
