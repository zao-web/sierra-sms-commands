<?php
/**
 * Telnyx SMS Provider
 *
 * Implementation for Telnyx SMS service
 * - FREE inbound SMS
 * - $0.0025/outbound SMS
 * - Webhook signature validation
 *
 * @package SierraSMSCommands
 */

namespace SierraSMSCommands\Providers;

/**
 * Telnyx_Provider Class
 */
class Telnyx_Provider implements SMS_Provider {
	/**
	 * Telnyx API endpoint
	 */
	const API_ENDPOINT = 'https://api.telnyx.com/v2/messages';

	/**
	 * Get provider name
	 */
	public function get_name() {
		return __( 'Telnyx', 'sierra-sms-commands' );
	}

	/**
	 * Get provider slug
	 */
	public function get_slug() {
		return 'telnyx';
	}

	/**
	 * Send SMS
	 */
	public function send_sms( $to, $message ) {
		$config = $this->get_config();

		if ( empty( $config['api_key'] ) || empty( $config['from_number'] ) ) {
			return [
				'success' => false,
				'error'   => __( 'Telnyx not configured. Please add API key and phone number.', 'sierra-sms-commands' ),
			];
		}

		$response = wp_remote_post( self::API_ENDPOINT, [
			'headers' => [
				'Authorization' => 'Bearer ' . $config['api_key'],
				'Content-Type'  => 'application/json',
			],
			'body'    => wp_json_encode( [
				'from' => $config['from_number'],
				'to'   => $to,
				'text' => $message,
			] ),
			'timeout' => 15,
		] );

		if ( is_wp_error( $response ) ) {
			return [
				'success' => false,
				'error'   => $response->get_error_message(),
			];
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		$code = wp_remote_retrieve_response_code( $response );

		if ( $code !== 200 || ! isset( $body['data']['id'] ) ) {
			$error = $body['errors'][0]['detail'] ?? __( 'Unknown error', 'sierra-sms-commands' );
			return [
				'success' => false,
				'error'   => $error,
			];
		}

		return [
			'success'    => true,
			'message_id' => $body['data']['id'],
		];
	}

	/**
	 * Validate webhook
	 */
	public function validate_webhook( $request ) {
		// Telnyx uses public key signature verification
		// For now, we'll do basic validation
		// In production, implement proper signature verification

		$body = $request->get_body();
		$signature = $request->get_header( 'telnyx-signature-ed25519' );
		$timestamp = $request->get_header( 'telnyx-timestamp' );

		// Basic validation - ensure required headers exist
		if ( empty( $signature ) || empty( $timestamp ) ) {
			return false;
		}

		// TODO: Implement full Ed25519 signature verification
		// For now, accept if headers are present
		return true;
	}

	/**
	 * Parse inbound message
	 */
	public function parse_inbound_message( $request ) {
		$body = $request->get_json_params();

		if ( ! isset( $body['data']['event_type'] ) || $body['data']['event_type'] !== 'message.received' ) {
			return [
				'from'       => '',
				'message'    => '',
				'message_id' => '',
			];
		}

		$payload = $body['data']['payload'] ?? [];

		return [
			'from'       => $payload['from']['phone_number'] ?? '',
			'message'    => $payload['text'] ?? '',
			'message_id' => $payload['id'] ?? '',
		];
	}

	/**
	 * Get configuration fields
	 */
	public function get_config_fields() {
		return [
			'api_key'     => [
				'label'       => __( 'API Key', 'sierra-sms-commands' ),
				'type'        => 'password',
				'description' => __( 'Your Telnyx API key (starts with "KEY").', 'sierra-sms-commands' ),
				'required'    => true,
			],
			'from_number' => [
				'label'       => __( 'From Phone Number', 'sierra-sms-commands' ),
				'type'        => 'tel',
				'description' => __( 'Your Telnyx phone number in E.164 format (e.g., +14155551234).', 'sierra-sms-commands' ),
				'required'    => true,
				'placeholder' => '+14155551234',
			],
			'webhook_url' => [
				'label'       => __( 'Webhook URL', 'sierra-sms-commands' ),
				'type'        => 'text',
				'description' => __( 'Configure this URL in your Telnyx messaging profile.', 'sierra-sms-commands' ),
				'readonly'    => true,
				'value'       => rest_url( 'sierra/v1/sms/webhook' ),
			],
		];
	}

	/**
	 * Get provider configuration
	 */
	private function get_config() {
		return get_option( 'sierra_sms_config_telnyx', [] );
	}
}
