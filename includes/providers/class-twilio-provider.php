<?php
/**
 * Twilio SMS Provider
 *
 * Implementation for Twilio SMS service
 * - Industry standard, most reliable
 * - $0.0079/SMS + $1.50/month per number
 * - Global coverage
 * - Webhook signature validation
 *
 * @package SierraSMSCommands
 */

namespace SierraSMSCommands\Providers;

/**
 * Twilio_Provider Class
 */
class Twilio_Provider implements SMS_Provider {
	/**
	 * Twilio API version
	 */
	const API_VERSION = '2010-04-01';

	/**
	 * Get provider name
	 */
	public function get_name() {
		return __( 'Twilio', 'sierra-sms-commands' );
	}

	/**
	 * Get provider slug
	 */
	public function get_slug() {
		return 'twilio';
	}

	/**
	 * Send SMS
	 */
	public function send_sms( $to, $message ) {
		$config = $this->get_config();

		if ( empty( $config['account_sid'] ) || empty( $config['auth_token'] ) || empty( $config['from_number'] ) ) {
			return [
				'success' => false,
				'error'   => __( 'Twilio not configured. Please add Account SID, Auth Token, and phone number.', 'sierra-sms-commands' ),
			];
		}

		$endpoint = sprintf(
			'https://api.twilio.com/%s/Accounts/%s/Messages.json',
			self::API_VERSION,
			$config['account_sid']
		);

		$response = wp_remote_post( $endpoint, [
			'headers' => [
				'Authorization' => 'Basic ' . base64_encode( $config['account_sid'] . ':' . $config['auth_token'] ),
			],
			'body'    => [
				'From' => $config['from_number'],
				'To'   => $to,
				'Body' => $message,
			],
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

		if ( $code !== 201 || ! isset( $body['sid'] ) ) {
			$error = $body['message'] ?? __( 'Unknown error', 'sierra-sms-commands' );
			return [
				'success' => false,
				'error'   => $error,
			];
		}

		return [
			'success'    => true,
			'message_id' => $body['sid'],
		];
	}

	/**
	 * Validate webhook
	 */
	public function validate_webhook( $request ) {
		$config = $this->get_config();

		if ( empty( $config['auth_token'] ) ) {
			return false;
		}

		// Get Twilio signature from header
		$signature = $request->get_header( 'x-twilio-signature' );
		if ( empty( $signature ) ) {
			return false;
		}

		// Get full URL
		$url = home_url( $request->get_route() );

		// Get POST parameters
		$params = $request->get_body_params();

		// Compute expected signature
		$expected = $this->compute_signature( $url, $params, $config['auth_token'] );

		return hash_equals( $expected, $signature );
	}

	/**
	 * Compute Twilio signature
	 *
	 * @param string $url Full webhook URL
	 * @param array  $params POST parameters
	 * @param string $auth_token Auth token
	 * @return string Base64 encoded signature
	 */
	private function compute_signature( $url, $params, $auth_token ) {
		// Sort parameters by key
		ksort( $params );

		// Concatenate URL and parameters
		$data = $url;
		foreach ( $params as $key => $value ) {
			$data .= $key . $value;
		}

		// Compute HMAC-SHA1 and base64 encode
		return base64_encode( hash_hmac( 'sha1', $data, $auth_token, true ) );
	}

	/**
	 * Parse inbound message
	 */
	public function parse_inbound_message( $request ) {
		$params = $request->get_body_params();

		return [
			'from'       => $params['From'] ?? '',
			'message'    => $params['Body'] ?? '',
			'message_id' => $params['MessageSid'] ?? '',
		];
	}

	/**
	 * Get configuration fields
	 */
	public function get_config_fields() {
		return [
			'account_sid' => [
				'label'       => __( 'Account SID', 'sierra-sms-commands' ),
				'type'        => 'text',
				'description' => __( 'Your Twilio Account SID.', 'sierra-sms-commands' ),
				'required'    => true,
			],
			'auth_token'  => [
				'label'       => __( 'Auth Token', 'sierra-sms-commands' ),
				'type'        => 'password',
				'description' => __( 'Your Twilio Auth Token.', 'sierra-sms-commands' ),
				'required'    => true,
			],
			'from_number' => [
				'label'       => __( 'From Phone Number', 'sierra-sms-commands' ),
				'type'        => 'tel',
				'description' => __( 'Your Twilio phone number in E.164 format (e.g., +14155551234).', 'sierra-sms-commands' ),
				'required'    => true,
				'placeholder' => '+14155551234',
			],
			'webhook_url' => [
				'label'       => __( 'Webhook URL', 'sierra-sms-commands' ),
				'type'        => 'text',
				'description' => __( 'Configure this URL in your Twilio phone number settings.', 'sierra-sms-commands' ),
				'readonly'    => true,
				'value'       => rest_url( 'sierra/v1/sms/webhook' ),
			],
		];
	}

	/**
	 * Get provider configuration
	 */
	private function get_config() {
		return get_option( 'sierra_sms_config_twilio', [] );
	}
}
