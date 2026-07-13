<?php

namespace RocketLazyLoadPlugin\Dependencies\LaunchpadOptions;

class Settings implements Interfaces\SettingsInterface {


	/**
	 * WordPress Option facade.
	 *
	 * @var Options
	 */
	protected $options;

	/**
	 * Settings option key.
	 *
	 * @var string
	 */
	protected $settings_key;

	/**
	 * Settings Set.
	 *
	 * @var Set
	 */
	protected $settings;

	/**
	 * Instantiate settings.
	 *
	 * @param Options $options WordPress Option facade.
	 * @param string  $settings_key Settings option key.
	 */
	public function __construct( Options $options, string $settings_key = 'settings' ) {
		$this->options      = $options;
		$this->settings_key = $settings_key;
		$this->settings     = new Set( $this->options->get( $settings_key, [] ), $this->settings_key );
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
		return $this->settings->get( $name, $default );
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
		$this->settings->set( $name, $value );

		$this->persist();
	}

	/**
	 * Deletes the option with the given name.
	 *
	 * @param string $name Name of the option to delete.
	 *
	 * @return void
	 */
	public function delete( string $name ) {
		$this->settings->delete( $name );

		$this->persist();
	}

	/**
	 * Checks if the option with the given name exists.
	 *
	 * @param string $name Name of the option to check.
	 *
	 * @return bool
	 */
	public function has( string $name ): bool {
		return $this->settings->has( $name );
	}

	/**
	 * Persist the settings into the database.
	 *
	 * @return void
	 */
	protected function persist() {
		do_action( "pre_persist_{$this->settings_key}", $this->settings->get_values() );

		$this->options->set( $this->settings_key, $this->settings->get_values() );

		do_action( "persist_{$this->settings_key}", $this->settings->get_values() );
	}

	/**
	 * Import multiple values at once.
	 *
	 * @param array<string,mixed> $values Values to import.
	 *
	 * @return void
	 */
	public function import( array $values ) {
		$this->settings->set_values( $values );

		$this->persist();
	}

	/**
	 * Export settings values.
	 *
	 * @return array<string,mixed>
	 */
	public function dumps(): array {
		return $this->settings->get_values();
	}
}
