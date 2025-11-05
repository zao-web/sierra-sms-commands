<?php
/**
 * Mobiniti SMS Provider
 *
 * Implementation for Mobiniti SMS service
 * - OAuth 2.0 authentication with long-lived tokens (10 years)
 * - Contact-based messaging system
 * - 1 request/second rate limit per route
 * - Webhook support for inbound messages
 *
 * @package SierraSMSCommands
 */

namespace SierraSMSCommands\Providers;

/**
 * Mobiniti_Provider Class
 */
class Mobiniti_Provider implements SMS_Provider {
	/**
	 * Mobiniti API base URL
	 */
	const API_BASE = 'https://api.mobiniti.com/v1';

	/**
	 * Contact ID cache (phone number => contact ID)
	 *
	 * @var array
	 */
	private $contact_cache = [];

	/**
	 * Get provider name
	 */
	public function get_name() {
		return __( 'Mobiniti', 'sierra-sms-commands' );
	}

	/**
	 * Get provider slug
	 */
	public function get_slug() {
		return 'mobiniti';
	}

	/**
	 * Send SMS
	 */
	public function send_sms( $to, $message ) {
		$config = $this->get_config();

		if ( empty( $config['access_token'] ) ) {
			return [
				'success' => false,
				'error'   => __( 'Mobiniti not configured. Please add Access Token.', 'sierra-sms-commands' ),
			];
		}

		// First, get or create contact ID for this phone number
		$contact_id = $this->get_contact_id( $to );

		if ( is_wp_error( $contact_id ) ) {
			return [
				'success' => false,
				'error'   => $contact_id->get_error_message(),
			];
		}

		// Send message to contact
		$endpoint = self::API_BASE . '/messages';

		$response = wp_remote_post( $endpoint, [
			'headers' => [
				'Authorization' => 'Bearer ' . $config['access_token'],
				'Content-Type'  => 'application/json',
			],
			'body'    => wp_json_encode( [
				'contact_id' => $contact_id,
				'message'    => $message,
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

		// Check for rate limit (429)
		if ( $code === 429 ) {
			return [
				'success' => false,
				'error'   => __( 'Rate limit exceeded. Mobiniti allows 1 request per second.', 'sierra-sms-commands' ),
			];
		}

		if ( $code !== 200 && $code !== 201 ) {
			$error = $body['message'] ?? $body['error'] ?? __( 'Unknown error', 'sierra-sms-commands' );
			return [
				'success' => false,
				'error'   => $error,
			];
		}

		return [
			'success'    => true,
			'message_id' => $body['id'] ?? '',
		];
	}

	/**
	 * Get or create contact ID for phone number
	 *
	 * @param string $phone_number Phone number in E.164 format
	 * @return string|WP_Error Contact ID or error
	 */
	private function get_contact_id( $phone_number ) {
		// Check cache first
		if ( isset( $this->contact_cache[ $phone_number ] ) ) {
			return $this->contact_cache[ $phone_number ];
		}

		$config = $this->get_config();

		// Search for existing contact by phone number
		$endpoint = self::API_BASE . '/contacts';

		$response = wp_remote_get( add_query_arg( 'phone_number', $phone_number, $endpoint ), [
			'headers' => [
				'Authorization' => 'Bearer ' . $config['access_token'],
			],
			'timeout' => 15,
		] );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		$code = wp_remote_retrieve_response_code( $response );

		// If contact found, cache and return ID
		if ( $code === 200 && ! empty( $body['data'] ) && is_array( $body['data'] ) ) {
			foreach ( $body['data'] as $contact ) {
				if ( isset( $contact['phone_number'] ) && $contact['phone_number'] === $phone_number ) {
					$this->contact_cache[ $phone_number ] = $contact['id'];
					return $contact['id'];
				}
			}
		}

		// Contact doesn't exist, create it
		return $this->create_contact( $phone_number );
	}

	/**
	 * Create new contact
	 *
	 * @param string $phone_number Phone number in E.164 format
	 * @return string|WP_Error Contact ID or error
	 */
	private function create_contact( $phone_number ) {
		$config = $this->get_config();

		$endpoint = self::API_BASE . '/contacts';

		$response = wp_remote_post( $endpoint, [
			'headers' => [
				'Authorization' => 'Bearer ' . $config['access_token'],
				'Content-Type'  => 'application/json',
			],
			'body'    => wp_json_encode( [
				'phone_number' => $phone_number,
			] ),
			'timeout' => 15,
		] );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		$code = wp_remote_retrieve_response_code( $response );

		if ( $code !== 200 && $code !== 201 ) {
			$error = $body['message'] ?? $body['error'] ?? __( 'Failed to create contact', 'sierra-sms-commands' );
			return new \WP_Error( 'mobiniti_contact_error', $error );
		}

		if ( empty( $body['id'] ) ) {
			return new \WP_Error( 'mobiniti_contact_error', __( 'Contact created but no ID returned', 'sierra-sms-commands' ) );
		}

		// Cache the contact ID
		$this->contact_cache[ $phone_number ] = $body['id'];

		return $body['id'];
	}

	/**
	 * Validate webhook
	 *
	 * Mobiniti doesn't provide signature validation, so we use IP allowlist
	 */
	public function validate_webhook( $request ) {
		$config = $this->get_config();

		// Allow bypassing validation for development (use with extreme caution)
		if ( apply_filters( 'sierra_sms_bypass_webhook_validation', false ) ) {
			error_log( 'Sierra SMS Commands: Webhook validation bypassed (development mode)' );
			return true;
		}

		// Get allowed IPs from config
		$allowed_ips = $config['allowed_ips'] ?? '';
		if ( empty( $allowed_ips ) ) {
			error_log( 'Sierra SMS Commands: No allowed IPs configured for Mobiniti webhook validation' );
			return false;
		}

		// Parse allowed IPs (comma or newline separated)
		$allowed_ips = preg_split( '/[\s,]+/', $allowed_ips, -1, PREG_SPLIT_NO_EMPTY );

		// Get request IP
		$request_ip = $this->get_request_ip();

		if ( empty( $request_ip ) ) {
			error_log( 'Sierra SMS Commands: Could not determine request IP' );
			return false;
		}

		// Check if IP is in allowlist
		$is_allowed = in_array( $request_ip, $allowed_ips, true );

		if ( ! $is_allowed ) {
			error_log( sprintf(
				'Sierra SMS Commands: Webhook from unauthorized IP: %s (allowed: %s)',
				$request_ip,
				implode( ', ', $allowed_ips )
			) );
		}

		return $is_allowed;
	}

	/**
	 * Get request IP address
	 *
	 * @return string|null IP address or null if not found
	 */
	private function get_request_ip() {
		// Check for proxy headers first
		$headers = [
			'HTTP_CF_CONNECTING_IP', // Cloudflare
			'HTTP_X_FORWARDED_FOR',  // Standard proxy header
			'HTTP_X_REAL_IP',        // Nginx proxy
			'REMOTE_ADDR',           // Direct connection
		];

		foreach ( $headers as $header ) {
			if ( ! empty( $_SERVER[ $header ] ) ) {
				// Handle comma-separated list (proxy chain)
				$ips = explode( ',', $_SERVER[ $header ] );
				return trim( $ips[0] );
			}
		}

		return null;
	}

	/**
	 * Parse inbound message
	 */
	public function parse_inbound_message( $request ) {
		// Mobiniti sends JSON payload
		$body = $request->get_json_params();

		if ( empty( $body ) ) {
			// Fallback to body params if JSON parsing failed
			$body = $request->get_body_params();
		}

		return [
			'from'       => $body['phone_number'] ?? '',
			'message'    => $body['message'] ?? '',
			'message_id' => $body['id'] ?? '',
		];
	}

	/**
	 * Get configuration fields
	 */
	public function get_config_fields() {
		return [
			'access_token' => [
				'label'       => __( 'Access Token', 'sierra-sms-commands' ),
				'type'        => 'password',
				'description' => __( 'Your Mobiniti Personal Access Token or OAuth token (valid for 10 years).', 'sierra-sms-commands' ),
				'required'    => true,
			],
			'short_code'   => [
				'label'       => __( 'Short Code', 'sierra-sms-commands' ),
				'type'        => 'text',
				'description' => __( 'Your Mobiniti short code (e.g., 64600) for reference only.', 'sierra-sms-commands' ),
				'required'    => false,
				'placeholder' => '64600',
			],
			'allowed_ips'  => [
				'label'       => __( 'Allowed Webhook IPs', 'sierra-sms-commands' ),
				'type'        => 'textarea',
				'description' => __( 'Comma or newline-separated list of IP addresses allowed to send webhooks. Contact Mobiniti support for their webhook source IPs.', 'sierra-sms-commands' ),
				'required'    => true,
				'placeholder' => "192.0.2.1\n192.0.2.2",
			],
			'webhook_url'  => [
				'label'       => __( 'Webhook URL', 'sierra-sms-commands' ),
				'type'        => 'text',
				'description' => __( 'Configure this URL in your Mobiniti account under Message Settings > Webhooks.', 'sierra-sms-commands' ),
				'readonly'    => true,
				'value'       => rest_url( 'sierra/v1/sms/webhook' ),
			],
		];
	}

	/**
	 * Get provider configuration
	 */
	private function get_config() {
		return get_option( 'sierra_sms_config_mobiniti', [] );
	}
}
