<?php
/**
 * Webhook Handler
 *
 * Receives and processes inbound SMS messages
 * Orchestrates the entire command flow
 *
 * @package SierraSMSCommands
 */

namespace SierraSMSCommands;

use WP_REST_Controller;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * Webhook_Handler Class
 */
class Webhook_Handler extends WP_REST_Controller {
	/**
	 * Constructor
	 */
	public function __construct() {
		$this->namespace = 'sierra/v1';
		$this->rest_base = 'sms/webhook';
	}

	/**
	 * Register REST routes
	 */
	public function register_routes() {
		register_rest_route( $this->namespace, '/' . $this->rest_base, [
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => [ $this, 'handle_webhook' ],
			'permission_callback' => '__return_true', // Validation done in provider
		] );
	}

	/**
	 * Send TwiML response
	 *
	 * Twilio expects TwiML (XML) responses, not JSON
	 *
	 * @param string $message Optional message to include in TwiML
	 */
	private function send_twiml_response( $message = '' ) {
		status_header( 200 );
		header( 'Content-Type: text/xml; charset=utf-8' );
		echo '<?xml version="1.0" encoding="UTF-8"?>';
		echo '<Response>';
		if ( ! empty( $message ) ) {
			echo '<Message><Body>' . esc_xml( $message ) . '</Body></Message>';
		}
		echo '</Response>';
		exit;
	}

	/**
	 * Handle incoming SMS webhook
	 */
	public function handle_webhook( $request ) {
		// Get active provider
		$provider = Provider_Manager::get_active_provider();

		if ( ! $provider ) {
			// Twilio needs TwiML response
			if ( $this->is_twilio_request( $request ) ) {
				$this->send_twiml_response();
			}
			return new WP_Error( 'no_provider', __( 'No SMS provider configured', 'sierra-sms-commands' ), [ 'status' => 500 ] );
		}

		// Validate webhook (allow bypass for local development)
		$bypass_validation = apply_filters( 'sierra_sms_bypass_signature_validation', false );
		if ( ! $bypass_validation && ! $provider->validate_webhook( $request ) ) {
			do_action( 'sierra_sms_webhook_invalid', $request, $provider->get_slug() );
			// Twilio needs TwiML response
			if ( $provider->get_slug() === 'twilio' ) {
				$this->send_twiml_response();
			}
			return new WP_Error( 'invalid_webhook', __( 'Invalid webhook signature', 'sierra-sms-commands' ), [ 'status' => 403 ] );
		}

		// Parse inbound message
		$message_data = $provider->parse_inbound_message( $request );

		if ( empty( $message_data['from'] ) || empty( $message_data['message'] ) ) {
			return new WP_Error( 'invalid_message', __( 'Invalid message format', 'sierra-sms-commands' ), [ 'status' => 400 ] );
		}

		// Lookup user by phone number
		if ( ! class_exists( '\SierraResortData\Snow_Reporter_Role' ) ) {
			return new WP_Error( 'missing_dependency', __( 'Sierra Resort Data plugin not active', 'sierra-sms-commands' ), [ 'status' => 500 ] );
		}

		$user = \SierraResortData\Snow_Reporter_Role::get_user_by_phone( $message_data['from'] );

		if ( ! $user ) {
			// Unknown number - send help message
			$this->send_reply( $message_data['from'], __( 'Unrecognized phone number. Please contact an administrator to register your number.', 'sierra-sms-commands' ), $provider );
			// Twilio needs TwiML response
			if ( $provider->get_slug() === 'twilio' ) {
				$this->send_twiml_response();
			}
			return new WP_REST_Response( [ 'status' => 'unknown_user' ], 200 );
		}

		// Check capabilities
		if ( ! user_can( $user->ID, 'edit_sierra_lifts' ) ) {
			$this->send_reply( $message_data['from'], __( 'You do not have permission to update resort data.', 'sierra-sms-commands' ), $provider );
			// Twilio needs TwiML response
			if ( $provider->get_slug() === 'twilio' ) {
				$this->send_twiml_response();
			}
			return new WP_REST_Response( [ 'status' => 'unauthorized' ], 200 );
		}

		// Process the command
		$reply = $this->process_command( $message_data['message'], $message_data['from'], $user->ID, $provider );

		// Send reply
		$this->send_reply( $message_data['from'], $reply, $provider );

		// Log the command
		do_action( 'sierra_sms_command_processed', $message_data, $user->ID, $reply, $provider->get_slug() );

		// Twilio needs TwiML response
		if ( $provider->get_slug() === 'twilio' ) {
			$this->send_twiml_response();
		}

		return new WP_REST_Response( [ 'status' => 'success' ], 200 );
	}

	/**
	 * Check if request is from Twilio
	 *
	 * @param WP_REST_Request $request Request object
	 * @return bool True if Twilio request
	 */
	private function is_twilio_request( $request ) {
		$params = $request->get_body_params();
		return isset( $params['MessageSid'] ) || isset( $params['SmsSid'] );
	}

	/**
	 * Process SMS command
	 *
	 * @param string $message Command text
	 * @param string $phone_number User's phone number
	 * @param int    $user_id User ID
	 * @param object $provider SMS provider
	 * @return string Reply message
	 */
	private function process_command( $message, $phone_number, $user_id, $provider ) {
		// Check if this is a numeric response to disambiguation
		if ( preg_match( '/^\s*(\d+)\s*$/', $message, $matches ) ) {
			return $this->handle_disambiguation_selection( $phone_number, $user_id, (int) $matches[1], $provider );
		}

		// Parse command
		$command = Command_Parser::parse( $message );

		// Handle special commands
		if ( isset( $command['action'] ) ) {
			// Help command
			if ( $command['action'] === 'help' ) {
				return Command_Parser::get_help_message();
			}

			// Status command
			if ( $command['action'] === 'status' ) {
				return Command_Executor::get_status();
			}

			// Undo command
			if ( $command['action'] === 'undo' ) {
				$result = Confirmation_Manager::execute_undo( $phone_number, $provider->get_slug() );
				return $result['message'];
			}

			// Confirm command (two-step mode)
			if ( $command['action'] === 'confirm' ) {
				return $this->handle_confirmation( $phone_number, $user_id, $provider );
			}

			// Cancel command
			if ( $command['action'] === 'cancel' ) {
				Confirmation_Manager::clear_pending_command( $phone_number );
				return __( 'Command cancelled.', 'sierra-sms-commands' );
			}
		}

		// Handle errors
		if ( isset( $command['error'] ) ) {
			// Check for ambiguous matches
			if ( isset( $command['matches'] ) && ! empty( $command['matches'] ) ) {
				// Store disambiguation choices
				Confirmation_Manager::store_disambiguation_choices(
					$phone_number,
					$command['action'],
					$command['matches'],
					$user_id
				);

				// Build numbered list
				$action_verb = $command['action'] === 'close' ? __( 'close', 'sierra-sms-commands' ) : __( 'open', 'sierra-sms-commands' );
				$options = [];
				foreach ( $command['matches'] as $index => $match ) {
					$number = $index + 1;
					$type_label = Command_Parser::get_type_label( $match->post_type );
					$options[] = sprintf(
						__( 'Press %d to %s %s %s', 'sierra-sms-commands' ),
						$number,
						$action_verb,
						$type_label,
						$match->post_title
					);
				}

				return implode( "\n", $options );
			}

			return $command['error'];
		}

		// Valid command - check confirmation mode
		$confirmation_mode = Confirmation_Manager::get_confirmation_mode( $user_id );

		if ( $confirmation_mode === 'two_step' ) {
			// Store command for confirmation
			Confirmation_Manager::store_pending_command( $phone_number, $command, $user_id );

			return sprintf(
				__( "%s? Reply YES to confirm or NO to cancel.", 'sierra-sms-commands' ),
				Command_Parser::format_command( $command )
			);
		}

		// Immediate mode with undo
		return $this->execute_and_reply( $command, $user_id, $phone_number, $provider );
	}

	/**
	 * Execute command and generate reply
	 *
	 * @param array  $command Parsed command
	 * @param int    $user_id User ID
	 * @param string $phone_number Phone number
	 * @param object $provider Provider
	 * @return string Reply message
	 */
	private function execute_and_reply( $command, $user_id, $phone_number, $provider ) {
		$result = Command_Executor::execute( $command, $user_id, $provider->get_slug() );

		if ( ! $result['success'] ) {
			return $result['message'];
		}

		// Store undo data
		if ( isset( $result['undo_data'] ) ) {
			Confirmation_Manager::store_undo_data( $phone_number, $result['undo_data'], $user_id );

			$undo_window = get_option( 'sierra_sms_undo_window', 120 );
			$undo_minutes = ceil( $undo_window / 60 );

			return sprintf(
				__( "%s. Reply UNDO within %d min to reverse.", 'sierra-sms-commands' ),
				$result['message'],
				$undo_minutes
			);
		}

		return $result['message'];
	}

	/**
	 * Handle confirmation response
	 *
	 * @param string $phone_number Phone number
	 * @param int    $user_id User ID
	 * @param object $provider Provider
	 * @return string Reply message
	 */
	private function handle_confirmation( $phone_number, $user_id, $provider ) {
		$pending = Confirmation_Manager::get_pending_command( $phone_number );

		if ( ! $pending ) {
			return __( 'No pending command to confirm.', 'sierra-sms-commands' );
		}

		// Clear pending command
		Confirmation_Manager::clear_pending_command( $phone_number );

		// Execute the command
		return $this->execute_and_reply( $pending['command'], $user_id, $phone_number, $provider );
	}

	/**
	 * Handle disambiguation selection
	 *
	 * @param string $phone_number Phone number
	 * @param int    $user_id User ID
	 * @param int    $selection Selected number (1-based)
	 * @param object $provider Provider
	 * @return string Reply message
	 */
	private function handle_disambiguation_selection( $phone_number, $user_id, $selection, $provider ) {
		$disambiguation = Confirmation_Manager::get_disambiguation_choices( $phone_number );

		if ( ! $disambiguation ) {
			return __( 'No pending selection. Please send a new command.', 'sierra-sms-commands' );
		}

		// Validate selection number
		$matches = $disambiguation['matches'];
		$index = $selection - 1; // Convert to 0-based

		if ( $index < 0 || $index >= count( $matches ) ) {
			return sprintf(
				__( 'Invalid selection. Please choose 1-%d.', 'sierra-sms-commands' ),
				count( $matches )
			);
		}

		// Get the selected match
		$selected_match = $matches[ $index ];

		// Build command from selection
		$command = [
			'action'  => $disambiguation['action'],
			'type'    => $selected_match->post_type,
			'name'    => $selected_match->post_title,
			'post_id' => $selected_match->ID,
		];

		// Clear disambiguation choices
		Confirmation_Manager::clear_disambiguation_choices( $phone_number );

		// Execute the command
		return $this->execute_and_reply( $command, $user_id, $phone_number, $provider );
	}

	/**
	 * Send SMS reply
	 *
	 * @param string $to Recipient phone number
	 * @param string $message Message text
	 * @param object $provider Provider
	 * @return bool Success
	 */
	private function send_reply( $to, $message, $provider ) {
		$result = Provider_Manager::send_sms( $to, $message );
		return $result['success'] ?? false;
	}
}
