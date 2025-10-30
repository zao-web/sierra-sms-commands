<?php
/**
 * Textbelt SMS Provider
 *
 * Implementation for Textbelt SMS service
 * - Simple pay-as-you-go
 * - No recurring fees
 * - Reply webhooks (US only)
 *
 * @package SierraSMSCommands
 */

namespace SierraSMSCommands\Providers;

/**
 * Textbelt_Provider Class
 */
class Textbelt_Provider implements SMS_Provider {
	/**
	 * Textbelt API endpoint
	 */
	const API_ENDPOINT = 'https://textbelt.com/text';

	/**
	 * Get provider name
	 */
	public function get_name() {
		return __( 'Textbelt', 'sierra-sms-commands' );
	}

	/**
	 * Get provider slug
	 */
	public function get_slug() {
		return 'textbelt';
	}

	/**
	 * Send SMS
	 */
	public function send_sms( $to, $message ) {
		$config = $this->get_config();

		if ( empty( $config['api_key'] ) ) {
			return [
				'success' => false,
				'error'   => __( 'Textbelt not configured. Please add API key.', 'sierra-sms-commands' ),
			];
		}

		$webhook_url = ! empty( $config['enable_replies'] ) ? rest_url( 'sierra/v1/sms/webhook' ) : '';

		$response = wp_remote_post( self::API_ENDPOINT, [
			'body' => [
				'phone'            => $to,
				'message'          => $message,
				'key'              => $config['api_key'],
				'replyWebhookUrl'  => $webhook_url,
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

		if ( ! isset( $body['success'] ) || ! $body['success'] ) {
			$error = $body['error'] ?? __( 'Unknown error', 'sierra-sms-commands' );
			return [
				'success' => false,
				'error'   => $error,
			];
		}

		return [
			'success'    => true,
			'message_id' => $body['textId'] ?? '',
		];
	}

	/**
	 * Validate webhook
	 */
	public function validate_webhook( $request ) {
		// Textbelt doesn't use signature validation
		// Validate by checking required parameters exist
		$params = $request->get_json_params();
		return isset( $params['fromNumber'] ) && isset( $params['text'] );
	}

	/**
	 * Parse inbound message
	 */
	public function parse_inbound_message( $request ) {
		$body = $request->get_json_params();

		return [
			'from'       => $body['fromNumber'] ?? '',
			'message'    => $body['text'] ?? '',
			'message_id' => $body['textId'] ?? '',
		];
	}

	/**
	 * Get configuration fields
	 */
	public function get_config_fields() {
		return [
			'api_key'        => [
				'label'       => __( 'API Key', 'sierra-sms-commands' ),
				'type'        => 'password',
				'description' => __( 'Your Textbelt API key.', 'sierra-sms-commands' ),
				'required'    => true,
			],
			'enable_replies' => [
				'label'       => __( 'Enable Reply Webhooks', 'sierra-sms-commands' ),
				'type'        => 'checkbox',
				'description' => __( 'Enable inbound SMS (US numbers only). Requires webhook configuration.', 'sierra-sms-commands' ),
			],
			'webhook_url'    => [
				'label'       => __( 'Webhook URL', 'sierra-sms-commands' ),
				'type'        => 'text',
				'description' => __( 'Use this URL for reply webhooks (US only).', 'sierra-sms-commands' ),
				'readonly'    => true,
				'value'       => rest_url( 'sierra/v1/sms/webhook' ),
			],
		];
	}

	/**
	 * Get provider configuration
	 */
	private function get_config() {
		return get_option( 'sierra_sms_config_textbelt', [] );
	}
}
