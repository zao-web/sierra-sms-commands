<?php
/**
 * Confirmation Manager
 *
 * Manages confirmation flow and undo functionality
 * Supports both immediate+undo and two-step confirmation modes
 *
 * @package SierraSMSCommands
 */

namespace SierraSMSCommands;

/**
 * Confirmation_Manager Class
 */
class Confirmation_Manager {
	/**
	 * Store pending command for confirmation
	 *
	 * @param string $phone_number User's phone number
	 * @param array  $command Parsed command
	 * @param int    $user_id User ID
	 * @return bool Success
	 */
	public static function store_pending_command( $phone_number, $command, $user_id ) {
		$key = self::get_transient_key( $phone_number, 'pending' );
		$data = [
			'command' => $command,
			'user_id' => $user_id,
			'stored_at' => time(),
		];

		return set_transient( $key, $data, 300 ); // 5 minutes
	}

	/**
	 * Get pending command
	 *
	 * @param string $phone_number User's phone number
	 * @return array|false Command data or false
	 */
	public static function get_pending_command( $phone_number ) {
		$key = self::get_transient_key( $phone_number, 'pending' );
		return get_transient( $key );
	}

	/**
	 * Clear pending command
	 *
	 * @param string $phone_number User's phone number
	 * @return bool Success
	 */
	public static function clear_pending_command( $phone_number ) {
		$key = self::get_transient_key( $phone_number, 'pending' );
		return delete_transient( $key );
	}

	/**
	 * Store undo data
	 *
	 * @param string $phone_number User's phone number
	 * @param array  $undo_data Undo data from command execution
	 * @param int    $user_id User ID
	 * @return bool Success
	 */
	public static function store_undo_data( $phone_number, $undo_data, $user_id ) {
		$key = self::get_transient_key( $phone_number, 'undo' );
		$data = [
			'undo_data' => $undo_data,
			'user_id'   => $user_id,
			'stored_at' => time(),
		];

		$undo_window = get_option( 'sierra_sms_undo_window', 120 ); // Default 2 minutes
		return set_transient( $key, $data, $undo_window );
	}

	/**
	 * Get undo data
	 *
	 * @param string $phone_number User's phone number
	 * @return array|false Undo data or false
	 */
	public static function get_undo_data( $phone_number ) {
		$key = self::get_transient_key( $phone_number, 'undo' );
		return get_transient( $key );
	}

	/**
	 * Clear undo data
	 *
	 * @param string $phone_number User's phone number
	 * @return bool Success
	 */
	public static function clear_undo_data( $phone_number ) {
		$key = self::get_transient_key( $phone_number, 'undo' );
		return delete_transient( $key );
	}

	/**
	 * Execute undo
	 *
	 * @param string $phone_number User's phone number
	 * @param string $provider Provider slug
	 * @return array Result
	 */
	public static function execute_undo( $phone_number, $provider ) {
		$undo = self::get_undo_data( $phone_number );

		if ( ! $undo ) {
			return [
				'success' => false,
				'message' => __( 'Nothing to undo. Undo window expired or no recent command.', 'sierra-sms-commands' ),
			];
		}

		$undo_data = $undo['undo_data'];
		$user_id = $undo['user_id'];

		// Restore old status
		wp_set_current_user( $user_id );

		$request = new \WP_REST_Request( 'PUT', '/sierra/v1/' . self::get_rest_endpoint( $undo_data['post_type'] ) . '/' . $undo_data['post_id'] );
		$request->set_param( 'status', $undo_data['old_status'] );

		$response = rest_do_request( $request );

		if ( is_wp_error( $response ) || $response->is_error() ) {
			return [
				'success' => false,
				'message' => __( 'Failed to undo command', 'sierra-sms-commands' ),
			];
		}

		// Update audit log source
		global $wpdb;
		$audit_table = $wpdb->prefix . 'sierra_audit_logs';
		$wpdb->query( $wpdb->prepare(
			"UPDATE $audit_table
			SET change_source = %s
			WHERE post_id = %d
			AND field_changed = 'status'
			ORDER BY created_at DESC
			LIMIT 1",
			'sms:' . $provider . ':undo',
			$undo_data['post_id']
		) );

		self::clear_undo_data( $phone_number );

		return [
			'success' => true,
			'message' => sprintf(
				__( 'Undone: %s restored to %s', 'sierra-sms-commands' ),
				$undo_data['post_title'],
				$undo_data['old_status']
			),
		];
	}

	/**
	 * Get confirmation mode for user
	 *
	 * @param int $user_id User ID
	 * @return string Mode (immediate_undo or two_step)
	 */
	public static function get_confirmation_mode( $user_id ) {
		// Check user preference first
		$user_mode = get_user_meta( $user_id, 'sierra_sms_confirmation_mode', true );

		if ( $user_mode ) {
			return $user_mode;
		}

		// Fall back to global setting
		return get_option( 'sierra_sms_confirmation_mode', 'immediate_undo' );
	}

	/**
	 * Store disambiguation choices
	 *
	 * @param string $phone_number User's phone number
	 * @param string $action Action (open/close/reopen)
	 * @param array  $matches Array of WP_Post objects
	 * @param int    $user_id User ID
	 * @return bool Success
	 */
	public static function store_disambiguation_choices( $phone_number, $action, $matches, $user_id ) {
		$key = self::get_transient_key( $phone_number, 'disambiguate' );
		$data = [
			'action'     => $action,
			'matches'    => $matches,
			'user_id'    => $user_id,
			'stored_at'  => time(),
		];

		return set_transient( $key, $data, 300 ); // 5 minutes
	}

	/**
	 * Get disambiguation choices
	 *
	 * @param string $phone_number User's phone number
	 * @return array|false Disambiguation data or false
	 */
	public static function get_disambiguation_choices( $phone_number ) {
		$key = self::get_transient_key( $phone_number, 'disambiguate' );
		return get_transient( $key );
	}

	/**
	 * Clear disambiguation choices
	 *
	 * @param string $phone_number User's phone number
	 * @return bool Success
	 */
	public static function clear_disambiguation_choices( $phone_number ) {
		$key = self::get_transient_key( $phone_number, 'disambiguate' );
		return delete_transient( $key );
	}

	/**
	 * Get transient key
	 *
	 * @param string $phone_number Phone number
	 * @param string $type Type (pending, undo, or disambiguate)
	 * @return string Transient key
	 */
	private static function get_transient_key( $phone_number, $type ) {
		// Remove + and other non-alphanumeric characters for transient key
		$clean_phone = preg_replace( '/[^0-9]/', '', $phone_number );
		return "sierra_sms_{$type}_{$clean_phone}";
	}

	/**
	 * Get REST endpoint for post type
	 */
	private static function get_rest_endpoint( $post_type ) {
		$map = [
			'sierra_lift'         => 'lifts',
			'sierra_trail'        => 'trails',
			'sierra_gate'         => 'gates',
			'sierra_park_feature' => 'park-features',
		];

		return $map[ $post_type ] ?? 'lifts';
	}
}
