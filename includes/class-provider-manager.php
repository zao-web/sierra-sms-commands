<?php
/**
 * Provider Manager
 *
 * Manages SMS providers and handles provider selection
 *
 * @package SierraSMSCommands
 */

namespace SierraSMSCommands;

use SierraSMSCommands\Providers\SMS_Provider;
use SierraSMSCommands\Providers\Telnyx_Provider;
use SierraSMSCommands\Providers\Textbelt_Provider;
use SierraSMSCommands\Providers\Twilio_Provider;

/**
 * Provider_Manager Class
 */
class Provider_Manager {
	/**
	 * Registered providers
	 *
	 * @var array
	 */
	private static $providers = [];

	/**
	 * Active provider instance
	 *
	 * @var SMS_Provider
	 */
	private static $active_provider = null;

	/**
	 * Register default providers
	 */
	public static function register_default_providers() {
		self::register_provider( new Telnyx_Provider() );
		self::register_provider( new Textbelt_Provider() );
		self::register_provider( new Twilio_Provider() );

		// Allow custom providers to be registered
		do_action( 'sierra_sms_register_providers' );
	}

	/**
	 * Register a provider
	 *
	 * @param SMS_Provider $provider Provider instance
	 */
	public static function register_provider( SMS_Provider $provider ) {
		self::$providers[ $provider->get_slug() ] = $provider;
	}

	/**
	 * Get all registered providers
	 *
	 * @return array Array of provider instances
	 */
	public static function get_providers() {
		if ( empty( self::$providers ) ) {
			self::register_default_providers();
		}

		return self::$providers;
	}

	/**
	 * Get active provider
	 *
	 * @return SMS_Provider|null Active provider instance or null
	 */
	public static function get_active_provider() {
		if ( null !== self::$active_provider ) {
			return self::$active_provider;
		}

		$providers = self::get_providers();
		$active_slug = get_option( 'sierra_sms_provider', 'telnyx' );

		if ( isset( $providers[ $active_slug ] ) ) {
			self::$active_provider = $providers[ $active_slug ];
			return self::$active_provider;
		}

		return null;
	}

	/**
	 * Get provider by slug
	 *
	 * @param string $slug Provider slug
	 * @return SMS_Provider|null Provider instance or null
	 */
	public static function get_provider( $slug ) {
		$providers = self::get_providers();
		return $providers[ $slug ] ?? null;
	}

	/**
	 * Set active provider
	 *
	 * @param string $slug Provider slug
	 * @return bool Success
	 */
	public static function set_active_provider( $slug ) {
		$providers = self::get_providers();

		if ( ! isset( $providers[ $slug ] ) ) {
			return false;
		}

		update_option( 'sierra_sms_provider', $slug );
		self::$active_provider = $providers[ $slug ];

		return true;
	}

	/**
	 * Get provider configuration
	 *
	 * @param string $provider_slug Provider slug
	 * @return array Configuration values
	 */
	public static function get_provider_config( $provider_slug ) {
		$config = get_option( "sierra_sms_config_{$provider_slug}", [] );
		return is_array( $config ) ? $config : [];
	}

	/**
	 * Save provider configuration
	 *
	 * @param string $provider_slug Provider slug
	 * @param array  $config Configuration values
	 * @return bool Success
	 */
	public static function save_provider_config( $provider_slug, $config ) {
		return update_option( "sierra_sms_config_{$provider_slug}", $config );
	}

	/**
	 * Send SMS using active provider
	 *
	 * @param string $to Recipient phone number
	 * @param string $message Message text
	 * @return array Result from provider
	 */
	public static function send_sms( $to, $message ) {
		$provider = self::get_active_provider();

		if ( ! $provider ) {
			return [
				'success' => false,
				'error'   => __( 'No SMS provider configured', 'sierra-sms-commands' ),
			];
		}

		// Log outbound message
		do_action( 'sierra_sms_before_send', $to, $message, $provider->get_slug() );

		$result = $provider->send_sms( $to, $message );

		// Log result
		do_action( 'sierra_sms_after_send', $to, $message, $result, $provider->get_slug() );

		return $result;
	}
}
