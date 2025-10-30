<?php
/**
 * Admin Settings
 *
 * Handles SMS settings page registration and rendering
 *
 * @package SierraSMSCommands
 */

namespace SierraSMSCommands\Admin;

use SierraSMSCommands\Provider_Manager;

/**
 * Admin_Settings Class
 */
class Admin_Settings {
	/**
	 * Register admin menu
	 */
	public static function register() {
		add_submenu_page(
			'options-general.php',
			__( 'SMS Commands', 'sierra-sms-commands' ),
			__( 'SMS Commands', 'sierra-sms-commands' ),
			'manage_options',
			'sierra-sms-commands',
			[ self::class, 'render_settings_page' ]
		);
	}

	/**
	 * Register settings
	 */
	public static function register_settings() {
		register_setting( 'sierra_sms_settings', 'sierra_sms_provider' );
		register_setting( 'sierra_sms_settings', 'sierra_sms_confirmation_mode' );
		register_setting( 'sierra_sms_settings', 'sierra_sms_undo_window' );

		// Register provider-specific settings
		register_setting( 'sierra_sms_settings', 'sierra_sms_config_telnyx' );
		register_setting( 'sierra_sms_settings', 'sierra_sms_config_textbelt' );
		register_setting( 'sierra_sms_settings', 'sierra_sms_config_twilio' );
	}

	/**
	 * Render settings page
	 */
	public static function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Handle form submission
		if ( isset( $_POST['sierra_sms_submit'] ) ) {
			check_admin_referer( 'sierra_sms_settings' );
			self::save_settings();
			echo '<div class="notice notice-success"><p>' . __( 'Settings saved.', 'sierra-sms-commands' ) . '</p></div>';
		}

		$current_provider = get_option( 'sierra_sms_provider', 'telnyx' );
		$confirmation_mode = get_option( 'sierra_sms_confirmation_mode', 'immediate_undo' );
		$undo_window = get_option( 'sierra_sms_undo_window', 120 );
		$providers = Provider_Manager::get_providers();

		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<p class="description"><?php _e( 'Configure SMS providers and command settings for snow reporters.', 'sierra-sms-commands' ); ?></p>

			<form method="post" action="">
				<?php wp_nonce_field( 'sierra_sms_settings' ); ?>

				<h2><?php _e( 'Provider Settings', 'sierra-sms-commands' ); ?></h2>
				<table class="form-table">
					<tr>
						<th scope="row"><?php _e( 'SMS Provider', 'sierra-sms-commands' ); ?></th>
						<td>
							<select name="sierra_sms_provider" id="sierra_sms_provider">
								<?php foreach ( $providers as $slug => $provider ) : ?>
									<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $current_provider, $slug ); ?>>
										<?php echo esc_html( $provider->get_name() ); ?>
									</option>
								<?php endforeach; ?>
							</select>
							<p class="description"><?php _e( 'Select your SMS service provider.', 'sierra-sms-commands' ); ?></p>
						</td>
					</tr>
				</table>

				<?php foreach ( $providers as $slug => $provider ) : ?>
					<div class="provider-config" data-provider="<?php echo esc_attr( $slug ); ?>" style="<?php echo $current_provider === $slug ? '' : 'display:none;'; ?>">
						<h3><?php echo esc_html( $provider->get_name() ); ?> <?php _e( 'Configuration', 'sierra-sms-commands' ); ?></h3>
						<table class="form-table">
							<?php
							$config = Provider_Manager::get_provider_config( $slug );
							$fields = $provider->get_config_fields();

							foreach ( $fields as $field_key => $field ) :
								$value = $field['value'] ?? ( $config[ $field_key ] ?? '' );
								?>
								<tr>
									<th scope="row">
										<label for="<?php echo esc_attr( $slug . '_' . $field_key ); ?>">
											<?php echo esc_html( $field['label'] ); ?>
											<?php if ( ! empty( $field['required'] ) ) : ?>
												<span class="required">*</span>
											<?php endif; ?>
										</label>
									</th>
									<td>
										<?php if ( $field['type'] === 'checkbox' ) : ?>
											<input
												type="checkbox"
												name="sierra_sms_config_<?php echo esc_attr( $slug ); ?>[<?php echo esc_attr( $field_key ); ?>]"
												id="<?php echo esc_attr( $slug . '_' . $field_key ); ?>"
												value="1"
												<?php checked( ! empty( $config[ $field_key ] ) ); ?>
											/>
										<?php else : ?>
											<input
												type="<?php echo esc_attr( $field['type'] ); ?>"
												name="sierra_sms_config_<?php echo esc_attr( $slug ); ?>[<?php echo esc_attr( $field_key ); ?>]"
												id="<?php echo esc_attr( $slug . '_' . $field_key ); ?>"
												value="<?php echo esc_attr( $value ); ?>"
												class="regular-text"
												placeholder="<?php echo esc_attr( $field['placeholder'] ?? '' ); ?>"
												<?php echo ! empty( $field['readonly'] ) ? 'readonly' : ''; ?>
											/>
										<?php endif; ?>
										<?php if ( ! empty( $field['description'] ) ) : ?>
											<p class="description"><?php echo esc_html( $field['description'] ); ?></p>
										<?php endif; ?>
									</td>
								</tr>
							<?php endforeach; ?>
						</table>
					</div>
				<?php endforeach; ?>

				<h2><?php _e( 'Command Settings', 'sierra-sms-commands' ); ?></h2>
				<table class="form-table">
					<tr>
						<th scope="row"><?php _e( 'Confirmation Mode', 'sierra-sms-commands' ); ?></th>
						<td>
							<fieldset>
								<label>
									<input type="radio" name="sierra_sms_confirmation_mode" value="immediate_undo" <?php checked( $confirmation_mode, 'immediate_undo' ); ?> />
									<?php _e( 'Immediate + Undo', 'sierra-sms-commands' ); ?>
								</label><br>
								<label>
									<input type="radio" name="sierra_sms_confirmation_mode" value="two_step" <?php checked( $confirmation_mode, 'two_step' ); ?> />
									<?php _e( 'Two-Step Confirmation', 'sierra-sms-commands' ); ?>
								</label>
								<p class="description"><?php _e( 'Choose default confirmation mode. Users can override in their profile.', 'sierra-sms-commands' ); ?></p>
							</fieldset>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php _e( 'Undo Window', 'sierra-sms-commands' ); ?></th>
						<td>
							<input type="number" name="sierra_sms_undo_window" value="<?php echo esc_attr( $undo_window ); ?>" min="30" max="600" />
							<?php _e( 'seconds', 'sierra-sms-commands' ); ?>
							<p class="description"><?php _e( 'How long users have to undo a command (30-600 seconds).', 'sierra-sms-commands' ); ?></p>
						</td>
					</tr>
				</table>

				<?php submit_button( __( 'Save Settings', 'sierra-sms-commands' ), 'primary', 'sierra_sms_submit' ); ?>
			</form>
		</div>

		<script>
		jQuery(document).ready(function($) {
			$('#sierra_sms_provider').on('change', function() {
				var selectedProvider = $(this).val();
				$('.provider-config').hide();
				$('.provider-config[data-provider="' + selectedProvider + '"]').show();
			});
		});
		</script>
		<?php
	}

	/**
	 * Save settings
	 */
	private static function save_settings() {
		if ( isset( $_POST['sierra_sms_provider'] ) ) {
			update_option( 'sierra_sms_provider', sanitize_text_field( $_POST['sierra_sms_provider'] ) );
		}

		if ( isset( $_POST['sierra_sms_confirmation_mode'] ) ) {
			update_option( 'sierra_sms_confirmation_mode', sanitize_text_field( $_POST['sierra_sms_confirmation_mode'] ) );
		}

		if ( isset( $_POST['sierra_sms_undo_window'] ) ) {
			$undo_window = absint( $_POST['sierra_sms_undo_window'] );
			$undo_window = max( 30, min( 600, $undo_window ) ); // Clamp between 30-600
			update_option( 'sierra_sms_undo_window', $undo_window );
		}

		// Save provider configs
		foreach ( [ 'telnyx', 'textbelt', 'twilio' ] as $provider ) {
			if ( isset( $_POST[ "sierra_sms_config_{$provider}" ] ) ) {
				$config = array_map( 'sanitize_text_field', $_POST[ "sierra_sms_config_{$provider}" ] );
				update_option( "sierra_sms_config_{$provider}", $config );
			}
		}
	}
}
