<?php
/**
 * Plugin Name: Design Tokens Manager for Elementor
 * Description: Take full control of your Elementor design system. Effortlessly manage Global Colors and Fonts with perfect ID consistency, smart clamp() sizing support, and real-time sync to Site Settings.
 * Version: 1.5.1
 * Author: Lupu Daniel Gabriel
 * Author URI: https://github.com/dani3lphp
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: design-tokens-manager-for-elementor
 * Domain Path: /languages
 * Requires at least: 5.6
 * Requires PHP: 7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'EDTM_VERSION', '1.5.1' );
define( 'EDTM_PATH', plugin_dir_path( __FILE__ ) );
define( 'EDTM_URL', plugin_dir_url( __FILE__ ) );
define( 'EDTM_BASENAME', plugin_basename( __FILE__ ) );

define( 'EDTM_COLOR_ID_PREFIX', 'edtm_c_' );
define( 'EDTM_TYPO_ID_PREFIX',  'edtm_t_' );

function edtm_activate() {
	if ( false === get_option( 'elementor_scheme_color', false ) ) {
		add_option( 'elementor_scheme_color', array() );
	}
	if ( false === get_option( 'elementor_scheme_typography', false ) ) {
		add_option( 'elementor_scheme_typography', array() );
	}
}
register_activation_hook( __FILE__, 'edtm_activate' );

/**
 * Helper: return the correct admin page URL for our screen (Templates submenu or top-level fallback).
 */
function edtm_get_admin_page_url( array $args = array() ) {
	$base = admin_url( post_type_exists( 'elementor_library' ) ? 'edit.php?post_type=elementor_library' : 'admin.php' );
	$default = array( 'page' => 'edtm-settings' );
	return add_query_arg( array_merge( $default, $args ), $base );
}

/**
 * Admin menu:
 * - If Elementor Templates menu exists: add submenu under Templates.
 * - Else: add a top-level "Design Tokens" menu (dashicons-art).
 */
function edtm_admin_menu() {
	$slug = 'edtm-settings';

	if ( post_type_exists( 'elementor_library' ) ) {
		add_submenu_page(
			'edit.php?post_type=elementor_library',
			__( 'Design Tokens', 'design-tokens-manager-for-elementor' ),
			__( 'Design Tokens', 'design-tokens-manager-for-elementor' ),
			'manage_options',
			$slug,
			'edtm_render_page'
		);
	} else {
		add_menu_page(
			__( 'Design Tokens', 'design-tokens-manager-for-elementor' ),
			__( 'Design Tokens', 'design-tokens-manager-for-elementor' ),
			'manage_options',
			$slug,
			'edtm_render_page',
			'dashicons-art',
			58
		);
	}
}
add_action( 'admin_menu', 'edtm_admin_menu' );

function edtm_render_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'design-tokens-manager-for-elementor' ) );
	}
	require EDTM_PATH . 'admin/page.php';
}

/**
 * Enqueue admin assets only when page=edtm-settings (works under Templates submenu or top-level).
 */
function edtm_enqueue_admin_assets( $hook_suffix ) {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Page identification only, no data processing
	if ( empty( $_GET['page'] ) || 'edtm-settings' !== sanitize_key( $_GET['page'] ) ) {
		return;
	}
	wp_enqueue_style( 'wp-color-picker' );
	wp_enqueue_style( 'edtm-admin', EDTM_URL . 'assets/css/admin.css', array(), EDTM_VERSION );
	wp_enqueue_script( 'edtm-admin', EDTM_URL . 'assets/js/admin.js', array( 'jquery', 'wp-color-picker' ), EDTM_VERSION, true );
	wp_localize_script( 'edtm-admin', 'EDTM_I18N', array(
		'removeConfirm'    => __( 'Remove this row?', 'design-tokens-manager-for-elementor' ),
		'pushConfirm'      => __( 'This will overwrite Elementor Site Settings with the tokens in this table. Continue?', 'design-tokens-manager-for-elementor' ),
		'noItemsSelected'  => __( 'No items selected.', 'design-tokens-manager-for-elementor' ),
		/* translators: %d: number of selected items to delete */
		'deleteConfirm'    => __( 'Delete %d selected item(s)?', 'design-tokens-manager-for-elementor' ),
	) );

	// Expose AJAX endpoint and nonce for section preference updates
	wp_localize_script( 'edtm-admin', 'EDTM_ADMIN', array(
		'ajax_url' => admin_url( 'admin-ajax.php' ),
		'edtm_section_nonce' => wp_create_nonce( 'edtm_section_pref' ),
	) );
}
add_action( 'admin_enqueue_scripts', 'edtm_enqueue_admin_assets' );

/**
 * AJAX: update user's last active section preference (admin-only).
 */
function edtm_ajax_update_section() {
	// This action is only available to logged-in users; enforce capability
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( 'forbidden', 403 );
	}
	check_ajax_referer( 'edtm_section_pref', 'nonce' );

	$section = isset( $_POST['section'] ) ? sanitize_text_field( wp_unslash( $_POST['section'] ) ) : '';
	if ( ! in_array( $section, array( 'colors', 'fonts' ), true ) ) {
		wp_send_json_error( 'invalid_section', 400 );
	}

	$user_id = get_current_user_id();
	if ( $user_id ) {
		update_user_meta( $user_id, 'edtm_last_active_section', $section );
	}

	wp_send_json_success();
}
add_action( 'wp_ajax_edtm_update_section', 'edtm_ajax_update_section' );

// Save & sync core (Kit + clamp support, queue, etc.)
require_once EDTM_PATH . 'admin/save.php';
add_action( 'admin_post_edtm_save_tokens', 'edtm_handle_save_tokens' );

// Import/Export + Two-way Sync
require_once EDTM_PATH . 'admin/import-export.php';
add_action( 'admin_post_edtm_export_tokens', 'edtm_handle_export_tokens' );
add_action( 'admin_post_edtm_import_tokens', 'edtm_handle_import_tokens' );
add_action( 'admin_post_edtm_pull_from_kit', 'edtm_handle_pull_from_kit' );
add_action( 'admin_post_edtm_push_to_kit',  'edtm_handle_push_to_kit' );

// Queue processing
add_action( 'admin_init', 'edtm_process_pending_kit_sync' );
add_action( 'elementor/loaded', 'edtm_process_pending_kit_sync', 20 );

/**
 * Notices on our page.
 */
function edtm_print_sync_notice() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Notice display only, no data processing
	if ( empty( $_GET['page'] ) || 'edtm-settings' !== sanitize_key( $_GET['page'] ) ) {
		return;
	}
	$notice = get_transient( 'edtm_sync_notice' );
	if ( ! empty( $notice['message'] ) ) {
		$type  = isset( $notice['type'] ) ? $notice['type'] : 'success';
		$class = 'notice-success';
		if ( 'warning' === $type ) { $class = 'notice-warning'; }
		elseif ( 'error' === $type ) { $class = 'notice-error'; }
		echo '<div class="notice ' . esc_attr( $class ) . ' is-dismissible"><p>' . esc_html( $notice['message'] ) . '</p></div>';
		delete_transient( 'edtm_sync_notice' );
	}
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Status parameter display only, no data processing
	if ( isset( $_GET['edtm-pulled'] ) ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Safe read-only parameter for notice display
		$ok = '1' === sanitize_key( $_GET['edtm-pulled'] );
		$msg = $ok ? __( 'Pulled from Site Settings.', 'design-tokens-manager-for-elementor' ) : __( 'Could not pull from Site Settings.', 'design-tokens-manager-for-elementor' );
		echo '<div class="notice ' . ( $ok ? 'notice-success' : 'notice-warning' ) . ' is-dismissible"><p>' . esc_html( $msg ) . '</p></div>';
	}
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Status parameter display only, no data processing
	if ( isset( $_GET['edtm-pushed'] ) ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Safe read-only parameter for notice display
		$ok = '1' === sanitize_key( $_GET['edtm-pushed'] );
		$msg = $ok ? __( 'Pushed to Site Settings (overwritten).', 'design-tokens-manager-for-elementor' ) : __( 'Could not push to Site Settings.', 'design-tokens-manager-for-elementor' );
		echo '<div class="notice ' . ( $ok ? 'notice-success' : 'notice-warning' ) . ' is-dismissible"><p>' . esc_html( $msg ) . '</p></div>';
	}
	// Display import/export status messages
	if ( isset( $_GET['edtm-import'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only status parameter for admin notice display, no data processing or actions performed.
		$ok = '1' === sanitize_key( wp_unslash( $_GET['edtm-import'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$msg = $ok ? __( 'Imported tokens.', 'design-tokens-manager-for-elementor' ) : __( 'Import failed.', 'design-tokens-manager-for-elementor' );
		if ( ! $ok && isset( $_GET['edtm-error'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$error_code = sanitize_key( wp_unslash( $_GET['edtm-error'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( 'file_too_large' === $error_code ) {
				$msg = __( 'Import failed: File size exceeds 2MB limit.', 'design-tokens-manager-for-elementor' );
			} elseif ( 'invalid_file_type' === $error_code ) {
				$msg = __( 'Import failed: Invalid file type. Please upload a JSON file.', 'design-tokens-manager-for-elementor' );
			}
		}
		echo '<div class="notice ' . ( $ok ? 'notice-success' : 'notice-warning' ) . ' is-dismissible"><p>' . esc_html( $msg ) . '</p></div>';
	}
}
add_action( 'admin_notices', 'edtm_print_sync_notice' );

// WP-CLI (optional)
if ( defined( 'WP_CLI' ) && WP_CLI && file_exists( EDTM_PATH . 'includes/cli.php' ) ) {
	require_once EDTM_PATH . 'includes/cli.php';
}