<?php
/**
 * SMS Provider Interface
 *
 * Defines the contract that all SMS providers must implement
 *
 * @package SierraSMSCommands
 */

namespace SierraSMSCommands\Providers;

/**
 * SMS_Provider Interface
 */
interface SMS_Provider {
	/**
	 * Send an SMS message
	 *
	 * @param string $to Recipient phone number in E.164 format
	 * @param string $message Message text
	 * @return array {
	 *     @type bool   $success Whether the message was sent successfully
	 *     @type string $message_id Provider's message ID (if successful)
	 *     @type string $error Error message (if failed)
	 * }
	 */
	public function send_sms( $to, $message );

	/**
	 * Validate incoming webhook request
	 *
	 * @param \WP_REST_Request $request WordPress REST request object
	 * @return bool True if webhook is valid, false otherwise
	 */
	public function validate_webhook( $request );

	/**
	 * Parse inbound message from webhook
	 *
	 * @param \WP_REST_Request $request WordPress REST request object
	 * @return array {
	 *     @type string $from Sender's phone number in E.164 format
	 *     @type string $message Message text
	 *     @type string $message_id Provider's message ID
	 * }
	 */
	public function parse_inbound_message( $request );

	/**
	 * Get configuration fields for this provider
	 *
	 * @return array Configuration fields definition
	 */
	public function get_config_fields();

	/**
	 * Get provider name
	 *
	 * @return string Provider name
	 */
	public function get_name();

	/**
	 * Get provider slug
	 *
	 * @return string Provider slug
	 */
	public function get_slug();
}
