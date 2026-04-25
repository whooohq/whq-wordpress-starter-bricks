<?php

namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * Builder access 'bricks_full_access' or 'bricks_edit_content'
 *
 * Set per user role under 'Bricks > Settings > Builder access OR by editing a user profile individually.
 *
 * 'bricks_edit_content' capability can't:
 *
 * - Add, clone, delete, save elements/templates
 * - Resize elements (width, height)
 * - Adjust element spacing (padding, margin)
 * - Access custom context menu
 * - Edit any CSS controls (property 'css' check)
 * - Edit any controls under "Style" tab
 * - Edit any controls with 'fullAccess' set to true
 * - Delete revisions
 * - Edit template settings
 * - Edit any page settings except 'SEO' (default panel)
 */
class Capabilities {
	const FULL_ACCESS        = 'bricks_full_access';
	const EDIT_CONTENT       = 'bricks_edit_content';
	const UPLOAD_SVG         = 'bricks_upload_svg';
	const EXECUTE_CODE       = 'bricks_execute_code';
	const BYPASS_MAINTENANCE = 'bricks_bypass_maintenance';

	// Allow to disable for individual user (@since 1.6)
	const NO_ACCESS              = 'bricks_no_access';
	const UPLOAD_SVG_OFF         = 'bricks_upload_svg_off';
	const EXECUTE_CODE_OFF       = 'bricks_execute_code_off';
	const BYPASS_MAINTENANCE_OFF = 'bricks_bypass_maintenance_off';

	// To run set_user_capabilities only once
	public static $capabilities_set = false;

	// Builder access (default: no access)
	public static $full_access  = false;
	public static $edit_content = false;
	public static $no_access    = true;

	// Upload SVG & execute code (default: false)
	public static $upload_svg   = false;
	public static $execute_code = false;

	// Bypass maintenance mode (default: false)
	public static $bypass_maintenance = false;

	public function __construct() {
		add_action( 'init', [ $this, 'set_user_capabilities' ] );
		add_action( 'edit_user_profile', [ $this, 'user_profile' ] );
		add_action( 'edit_user_profile_update', [ $this, 'update_user_profile' ] );

		add_filter( 'manage_users_columns', [ $this, 'manage_users_columns' ] );
		add_filter( 'manage_users_custom_column', [ $this, 'manage_users_custom_column' ], 10, 3 );
	}

	/**
	 * Set capabilities of logged in user
	 *
	 * - builder access
	 * - upload svg
	 * - exectute code
	 *
	 * @since 1.6
	 */
	public static function set_user_capabilities() {
		// Return: User not logged in
		if ( ! is_user_logged_in() ) {
			return;
		}

		if ( self::$capabilities_set ) {
			return;
		}

		$user     = wp_get_current_user();
		$user_can = array_keys( $user->caps );
		$role_can = array_keys( $user->allcaps );

		// STEP: Builder access

		// Current user
		if ( in_array( self::FULL_ACCESS, $user_can ) ) {
			self::$full_access = true;
			self::$no_access   = false;
		} elseif ( in_array( self::EDIT_CONTENT, $user_can ) ) {
			self::$edit_content = true;
			self::$no_access    = false;
		} elseif ( in_array( self::NO_ACCESS, $user_can ) ) {
			self::$no_access = true;
		}

		// User role
		elseif ( in_array( self::FULL_ACCESS, $role_can ) ) {
			self::$full_access = true;
			self::$no_access   = false;
		} elseif ( in_array( self::EDIT_CONTENT, $role_can ) ) {
			self::$edit_content = true;
			self::$no_access    = false;
		} elseif ( in_array( self::NO_ACCESS, $role_can ) ) {
			self::$no_access = true;
		}

		/**
		 * Default: Full access for (super) administrator
		 *
		 * NOTE: Super admin check requires is_multisite() check.
		 * Because user on single site with 'delete_users' capability is also considered super admin.
		 */
		elseif ( ( is_multisite() && is_super_admin( $user->ID ?? 0 ) ) || current_user_can( 'manage_options' ) ) {
			self::$full_access = true;
		} else {
			self::$no_access = true;
		}

		// STEP: Upload SVG

		// Current user
		if ( in_array( self::UPLOAD_SVG, $user_can ) ) {
			self::$upload_svg = true;
		} elseif ( in_array( self::UPLOAD_SVG_OFF, $user_can ) ) {
			self::$upload_svg = false;
		}

		// User role
		elseif ( in_array( self::UPLOAD_SVG, $role_can ) ) {
			self::$upload_svg = true;
		} elseif ( in_array( self::UPLOAD_SVG_OFF, $role_can ) ) {
			self::$upload_svg = false;
		}

		// STEP: Execute code

		// User setting
		if ( in_array( self::EXECUTE_CODE, $user_can ) ) {
			self::$execute_code = true;
		} elseif ( in_array( self::EXECUTE_CODE_OFF, $user_can ) ) {
			self::$execute_code = false;
		}

		// User role
		elseif ( in_array( self::EXECUTE_CODE, $role_can ) ) {
			self::$execute_code = true;
		} elseif ( in_array( self::EXECUTE_CODE_OFF, $role_can ) ) {
			self::$execute_code = false;
		}

		// STEP: Bypass maintenance mode (@since 1.9.4)

		// Current user
		if ( in_array( self::BYPASS_MAINTENANCE, $user_can ) ) {
			self::$bypass_maintenance = true;
		} elseif ( in_array( self::BYPASS_MAINTENANCE_OFF, $user_can ) ) {
			self::$bypass_maintenance = false;
		}

		// User role
		elseif ( in_array( self::BYPASS_MAINTENANCE, $role_can ) ) {
			self::$bypass_maintenance = true;
		} elseif ( in_array( self::BYPASS_MAINTENANCE_OFF, $role_can ) ) {
			self::$bypass_maintenance = false;
		}

		// Bypass maintenance mode for (super) administrator
		elseif ( ( is_multisite() && is_super_admin( $user->ID ?? 0 ) ) || in_array( 'manage_options', $role_can ) ) {
			self::$bypass_maintenance = true;
		}

		// Bypass maintenance mode for any logged-in user
		elseif ( ! Database::get_setting( 'bypassMaintenanceUserRoles' ) && is_user_logged_in() ) {
			self::$bypass_maintenance = true;
		}

		// To run logic above only once
		self::$capabilities_set = true;
	}

	public function manage_users_columns( $columns ) {
		$columns['bricks_capabilities'] = esc_html__( 'Capabilities', 'bricks' ) . ' (Bricks)';

		return $columns;
	}

	public function manage_users_custom_column( $output, $column_name, $user_id ) {
		if ( $column_name !== 'bricks_capabilities' ) {
			return $output;
		}

		$output   = [];
		$user     = get_user_by( 'ID', $user_id );
		$user_can = array_keys( $user->caps );

		// Ensure the capabilities are true booleans as capability could be set to false (@since 1.9.5)
		$all_caps = $user->allcaps ?? [];
		$role_can = array_keys(
			array_filter(
				$all_caps,
				function( $cap ) {
					return true === $cap;
				}
			)
		);

		// STEP: Builder access

		// Current user
		if ( in_array( self::FULL_ACCESS, $user_can ) ) {
			$output[] = esc_html__( 'Builder', 'bricks' ) . ': ' . esc_html__( 'Full access', 'bricks' );
		} elseif ( in_array( self::EDIT_CONTENT, $user_can ) ) {
			$output[] = esc_html__( 'Builder', 'bricks' ) . ': ' . esc_html__( 'Edit content', 'bricks' );
		} elseif ( in_array( self::NO_ACCESS, $user_can ) ) {
			$output[] = esc_html__( 'Builder', 'bricks' ) . ': ' . esc_html__( 'No access', 'bricks' );
		}

		// User role
		elseif ( in_array( self::FULL_ACCESS, $role_can ) ) {
			$output[] = esc_html__( 'Builder', 'bricks' ) . ': ' . esc_html__( 'Full access', 'bricks' );
		} elseif ( in_array( self::EDIT_CONTENT, $role_can ) ) {
			$output[] = esc_html__( 'Builder', 'bricks' ) . ': ' . esc_html__( 'Edit content', 'bricks' );
		} elseif ( in_array( self::NO_ACCESS, $role_can ) ) {
			$output[] = esc_html__( 'Builder', 'bricks' ) . ': ' . esc_html__( 'No access', 'bricks' );
		}

		// Default: Full access for (super) administrator
		elseif ( ( is_multisite() && is_super_admin( $user_id ) ) || in_array( 'manage_options', $role_can ) ) {
			$output[] = esc_html__( 'Builder', 'bricks' ) . ': ' . esc_html__( 'Full access', 'bricks' );
		} else {
			$output[] = esc_html__( 'Builder', 'bricks' ) . ': ' . esc_html__( 'No access', 'bricks' );
		}

		// STEP: Upload SVG

		// Current user
		if ( in_array( self::UPLOAD_SVG, $user_can ) ) {
			$output[] = esc_html__( 'Upload SVG', 'bricks' );
		} elseif ( in_array( self::UPLOAD_SVG_OFF, $user_can ) ) {
		}

		// User role
		elseif ( in_array( self::UPLOAD_SVG, $role_can ) ) {
			$output[] = esc_html__( 'Upload SVG', 'bricks' );
		} elseif ( in_array( self::UPLOAD_SVG_OFF, $role_can ) ) {
		}

		// STEP: Execute code

		// User setting
		if ( in_array( self::EXECUTE_CODE, $user_can ) ) {
			$output[] = esc_html__( 'Execute code', 'bricks' );
		} elseif ( in_array( self::EXECUTE_CODE_OFF, $user_can ) ) {
		}

		// User role
		elseif ( in_array( self::EXECUTE_CODE, $role_can ) ) {
			$output[] = esc_html__( 'Execute code', 'bricks' );
		} elseif ( in_array( self::EXECUTE_CODE_OFF, $role_can ) ) {
		}

		// STEP: Bypass maintenance mode (@since 1.9.4)

		// User setting
		if ( in_array( self::BYPASS_MAINTENANCE, $user_can ) ) {
			$output[] = esc_html__( 'Bypass maintenance', 'bricks' );
		} elseif ( in_array( self::BYPASS_MAINTENANCE_OFF, $user_can ) ) {
		}

		// User role
		elseif ( in_array( self::BYPASS_MAINTENANCE, $role_can ) ) {
			$output[] = esc_html__( 'Bypass maintenance', 'bricks' );
		} elseif ( in_array( self::BYPASS_MAINTENANCE_OFF, $role_can ) ) {
		}

		// Default: Bypass maintenance for (super) administrator
		elseif ( ( is_multisite() && is_super_admin( $user_id ) ) || in_array( 'manage_options', $role_can ) ) {
			$output[] = esc_html__( 'Bypass maintenance', 'bricks' );
		}

		return implode( ', ', $output );
	}

	/**
	 * Check current user capability to use builder (full access OR edit content)
	 *
	 * @return boolean
	 */
	public static function current_user_can_use_builder( $post_id = 0 ) {
		// Post status: 'trash'
		if ( 'trash' === get_post_status( $post_id ) ) {
			return false;
		}

		if ( ! self::$capabilities_set ) {
			$set = self::set_user_capabilities();
		}

		// Full access OR Edit content
		if ( self::$full_access || self::$edit_content ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if current user has full access
	 *
	 * @return boolean
	 */
	public static function current_user_has_full_access() {
		if ( ! self::$capabilities_set ) {
			$set = self::set_user_capabilities();
		}

		return self::$full_access;
	}

	/**
	 * Check if current user has no access
	 *
	 * @return boolean
	 * @since 1.6
	 */
	public static function current_user_has_no_access() {
		if ( ! self::$capabilities_set ) {
			$set = self::set_user_capabilities();
		}

		// No full access nor edit content
		return ! self::$full_access && ! self::$edit_content;
	}

	/**
	 * Logged-in user can upload SVGs
	 *
	 * current_user_can not working on multisite for super admin.
	 *
	 * @return boolean
	 * @since 1.6
	 */
	public static function current_user_can_upload_svg() {
		if ( ! self::$capabilities_set ) {
			$set = self::set_user_capabilities();
		}

		return self::$upload_svg;
	}

	/**
	 * Logged-in user can execute code
	 *
	 * current_user_can not working on multisite for super admin.
	 *
	 * @return boolean
	 * @since 1.6
	 */
	public static function current_user_can_execute_code() {
		// Return false: Code execution disabled globally (Bricks setting or filter)
		if ( ! Helpers::code_execution_enabled() ) {
			return false;
		}

		if ( ! self::$capabilities_set ) {
			$set = self::set_user_capabilities();
		}

		return self::$execute_code;
	}

	/**
	 * Logged-in user can bypass maintenance mode
	 *
	 * current_user_can not working on multisite for super admin.
	 *
	 * @return boolean
	 * @since 1.9.4
	 */
	public static function current_user_can_bypass_maintenance_mode() {
		if ( ! self::$capabilities_set ) {
			$set = self::set_user_capabilities();
		}

		return self::$bypass_maintenance;
	}

	/**
	 * Reset user role capabilities for Bricks
	 */
	public static function set_defaults() {
		$bricks_caps = [
			self::EDIT_CONTENT,
			self::FULL_ACCESS,
			self::NO_ACCESS,
			self::UPLOAD_SVG,
			self::EXECUTE_CODE,
			self::BYPASS_MAINTENANCE,
			self::UPLOAD_SVG_OFF,
			self::EXECUTE_CODE_OFF,
			self::BYPASS_MAINTENANCE_OFF,
		];

		$roles = wp_roles()->get_names();

		// Remove Bricks capabilities for all user roles
		foreach ( $roles as $role_key => $role_name ) {
			foreach ( $bricks_caps as $cap ) {
				wp_roles()->remove_cap( $role_key, $cap );
			}
		}

		// Set defaults: Administrator always has full access to Bricks
		wp_roles()->add_cap( 'administrator', self::FULL_ACCESS );
		wp_roles()->add_cap( 'administrator', self::BYPASS_MAINTENANCE );
	}

	/**
	 * Capabilities for access to the builder
	 *
	 * @return array
	 */
	public static function builder_caps() {
		return [
			[
				'capability' => '',
				'label'      => esc_html__( 'No access', 'bricks' ),
			],
			[
				'capability' => self::EDIT_CONTENT,
				'label'      => esc_html__( 'Edit content', 'bricks' ),
			],
			[
				'capability' => self::FULL_ACCESS,
				'label'      => esc_html__( 'Full access', 'bricks' ),
			],
		];
	}

	public static function save_builder_capabilities( $capabilities = [] ) {
		$allowed_caps = array_filter( array_column( self::builder_caps(), 'capability' ) );

		foreach ( $capabilities as $role => $capability ) {
			if ( ! empty( $capability ) && ! in_array( $capability, $allowed_caps ) ) {
				continue;
			}

			// Reset Bricks capabilities for this role
			foreach ( $allowed_caps as $allowed_cap ) {
				wp_roles()->remove_cap( $role, $allowed_cap );
			}

			// Set the selected Bricks capability for this role
			if ( ! empty( $capability ) ) {
				wp_roles()->add_cap( $role, $capability );
			}
		}
	}

	public static function save_capabilities( $capability, $add_to_roles = [] ) {
		if ( empty( $capability ) ) {
			return;
		}

		$roles = wp_roles()->get_names();

		foreach ( $roles as $role_key => $label ) {
			if ( in_array( $role_key, $add_to_roles ) ) {
				wp_roles()->add_cap( $role_key, $capability );
			} else {
				wp_roles()->remove_cap( $role_key, $capability );
			}
		}
	}

	/**
	 * Update Bricks-specific user capabilities:
	 *
	 * - bricks_cap_builder_access
	 * - bricks_cap_upload_svg
	 * - bricks_cap_execute_code
	 * - bricks_cap_bypass_maintenance (@since 1.9.4)
	 */
	public function update_user_profile( $user_id ) {
		$user = get_user_by( 'ID', $user_id );

		// Set builder access capability
		$user->remove_cap( self::FULL_ACCESS );
		$user->remove_cap( self::EDIT_CONTENT );
		$user->remove_cap( self::NO_ACCESS );

		if ( ! empty( $_POST['bricks_cap_builder_access'] ) ) {
			$user->add_cap( sanitize_text_field( $_POST['bricks_cap_builder_access'] ) );
		}

		// Set SVG upload capability
		$user->remove_cap( self::UPLOAD_SVG );
		$user->remove_cap( self::UPLOAD_SVG_OFF );

		if ( ! empty( $_POST['bricks_cap_upload_svg'] ) ) {
			$user->add_cap( sanitize_text_field( $_POST['bricks_cap_upload_svg'] ) );
		}

		// Set code execution capability
		$user->remove_cap( self::EXECUTE_CODE );
		$user->remove_cap( self::EXECUTE_CODE_OFF );

		if ( ! empty( $_POST['bricks_cap_execute_code'] ) ) {
			$user->add_cap( sanitize_text_field( $_POST['bricks_cap_execute_code'] ) );
		}

		// Set maintenance bypass capability (@since 1.9.4)
		$user->remove_cap( self::BYPASS_MAINTENANCE );
		$user->remove_cap( self::BYPASS_MAINTENANCE_OFF );

		if ( ! empty( $_POST['bricks_cap_bypass_maintenance'] ) ) {
			$user->add_cap( sanitize_text_field( $_POST['bricks_cap_bypass_maintenance'] ) );
		}
	}

	public function user_profile( $user ) {
		$role_can = array_keys( $user->allcaps );
		$user_can = array_keys( $user->caps );

		/**
		 * STEP: Builder access
		 *
		 * Administrator has full access by default.
		 * All other user roles have no access by default.
		 */
		$role_can_builder_access = user_can( $user, 'manage_options' ) ? esc_html__( 'Full access', 'bricks' ) : esc_html__( 'No access', 'bricks' );

		// User-specific builder access
		if ( in_array( self::FULL_ACCESS, $role_can ) ) {
			$role_can_builder_access = esc_html__( 'Full access', 'bricks' );
		} elseif ( in_array( self::EDIT_CONTENT, $role_can ) ) {
			$role_can_builder_access = esc_html__( 'Edit content', 'bricks' );
		} elseif ( in_array( self::NO_ACCESS, $role_can ) ) {
			$role_can_builder_access = esc_html__( 'No access', 'bricks' );
		}

		$role_can_builder_access .= ' (' . esc_html__( 'Default', 'bricks' ) . ')';

		// STEP: Upload SVG
		$role_can_upload_svg = esc_html__( 'Disabled', 'bricks' ) . ' (' . esc_html__( 'Default', 'bricks' ) . ')';

		if ( in_array( self::UPLOAD_SVG, $role_can ) ) {
			$role_can_upload_svg = esc_html__( 'Enabled', 'bricks' ) . ' (' . esc_html__( 'Settings', 'bricks' ) . ')';
		}

		// STEP: Execute code
		$role_can_execute_code = esc_html__( 'Disabled', 'bricks' ) . ' (' . esc_html__( 'Default', 'bricks' ) . ')';

		if ( in_array( self::EXECUTE_CODE, $role_can ) ) {
			$role_can_execute_code = esc_html__( 'Enabled', 'bricks' ) . ' (' . esc_html__( 'Settings', 'bricks' ) . ')';
		}

		// STEP: Bypass maintenance mode (@since 1.9.4)
		$role_can_bypass_maintenance = esc_html__( 'Disabled', 'bricks' ) . ' (' . esc_html__( 'Default', 'bricks' ) . ')';

		if ( in_array( self::BYPASS_MAINTENANCE, $role_can ) ) {
			$role_can_bypass_maintenance = esc_html__( 'Enabled', 'bricks' ) . ' (' . esc_html__( 'Settings', 'bricks' ) . ')';
		}

		echo '<h2>' . BRICKS_NAME . '</h2>';
		?>
		<table class="form-table">
			<tbody>
			<tr>
				<th><label for="bricks_cap_builder_access"><?php esc_html_e( 'Builder access', 'bricks' ); ?></label></th>
				<td>
					<select name="bricks_cap_builder_access" id="bricks_cap_builder_access">
						<option value=""><?php echo $role_can_builder_access; ?></option>
						<option value="<?php echo self::FULL_ACCESS; ?>" <?php selected( in_array( self::FULL_ACCESS, $user_can ) ); ?>><?php esc_html_e( 'Full access', 'bricks' ); ?></option>
						<option value="<?php echo self::EDIT_CONTENT; ?>" <?php selected( in_array( self::EDIT_CONTENT, $user_can ) ); ?>><?php esc_html_e( 'Edit content', 'bricks' ); ?></option>
						<option value="<?php echo self::NO_ACCESS; ?>" <?php selected( in_array( self::NO_ACCESS, $user_can ) ); ?>><?php esc_html_e( 'No access', 'bricks' ); ?></option>
					</select>
				</td>
			</tr>

			<tr>
				<th><label for="bricks_cap_upload_svg"><?php esc_html_e( 'Upload SVG', 'bricks' ); ?></label></th>
				<td>
					<select name="bricks_cap_upload_svg" id="bricks_cap_upload_svg">
					<option value=""><?php echo $role_can_upload_svg; ?></option>
						<option value="<?php echo self::UPLOAD_SVG; ?>" <?php selected( in_array( self::UPLOAD_SVG, $user_can ) ); ?>><?php esc_html_e( 'Enabled', 'bricks' ); ?></option>
						<option value="<?php echo self::UPLOAD_SVG_OFF; ?>" <?php selected( in_array( self::UPLOAD_SVG_OFF, $user_can ) ); ?>><?php esc_html_e( 'Disabled', 'bricks' ); ?></option>
					</select>

					<p class="description"><?php esc_html_e( 'Allow user to upload SVG files', 'bricks' ); ?>.</p>
				</td>
			</tr>

			<tr>
				<th><label for="bricks_cap_execute_code"><?php esc_html_e( 'Execute code', 'bricks' ); ?></label></th>
				<td>
					<select name="bricks_cap_execute_code" id="bricks_cap_execute_code">
					<option value=""><?php echo $role_can_execute_code; ?></option>
						<option value="<?php echo self::EXECUTE_CODE; ?>" <?php selected( in_array( self::EXECUTE_CODE, $user_can ) ); ?>><?php esc_html_e( 'Enabled', 'bricks' ); ?></option>
						<option value="<?php echo self::EXECUTE_CODE_OFF; ?>" <?php selected( in_array( self::EXECUTE_CODE_OFF, $user_can ) ); ?>><?php esc_html_e( 'Disabled', 'bricks' ); ?></option>
					</select>

					<p class="description"><?php esc_html_e( 'Allow user to change and execute code through the Code element', 'bricks' ); ?>.</p>
					<br>
				</td>
			</tr>

			<tr>
				<th><label for="bricks_cap_bypass_maintenance"><?php esc_html_e( 'Bypass maintenance', 'bricks' ); ?></label></th>
				<td>
					<select name="bricks_cap_bypass_maintenance" id="bricks_cap_bypass_maintenance">
						<option value=""><?php echo esc_html( $role_can_bypass_maintenance ); ?></option>
						<option value="<?php echo esc_attr( self::BYPASS_MAINTENANCE ); ?>" <?php selected( in_array( self::BYPASS_MAINTENANCE, $user_can, true ) ); ?>><?php esc_html_e( 'Enabled', 'bricks' ); ?></option>
						<option value="<?php echo esc_attr( self::BYPASS_MAINTENANCE_OFF ); ?>" <?php selected( in_array( self::BYPASS_MAINTENANCE_OFF, $user_can, true ) ); ?>><?php esc_html_e( 'Disabled', 'bricks' ); ?></option>
					</select>
				</td>
			</tr>

			</tbody>
		</table>
		<?php
	}
}
