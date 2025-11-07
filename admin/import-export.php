<?php
/**
 * Import/Export handlers + Two-way Sync (Pull/Push).
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Build export payload (kit|plugin).
 */
function edtm_build_export_payload( $source = 'kit', $preserve_ids = true ) {
	$payload = array(
		'schema'       => 'edtm-1.0',
		'generated_at' => current_time( 'mysql' ),
		'site'         => site_url(),
		'colors'       => array(),
		'fonts'        => array(),
	);

	$colors_norm = array();
	$fonts_norm  = array();

	if ( 'kit' === $source ) {
		$kit = function_exists( 'edtm_get_active_kit' ) ? edtm_get_active_kit() : false;
		$kit_colors = array();
		$kit_fonts  = array();

		if ( $kit && method_exists( $kit, 'get_settings' ) ) {
			try { $kit_colors = $kit->get_settings( 'custom_colors' ); } catch ( \Throwable $e ) {}
			try { $kit_fonts  = $kit->get_settings( 'custom_typography' ); } catch ( \Throwable $e ) {}
		} else {
			if ( function_exists( 'edtm_get_active_kit_id' ) ) {
				$kit_id   = edtm_get_active_kit_id();
				$settings = get_post_meta( $kit_id, '_elementor_page_settings', true );
				$kit_colors = is_array( $settings ) && isset( $settings['custom_colors'] ) ? $settings['custom_colors'] : array();
				$kit_fonts  = is_array( $settings ) && isset( $settings['custom_typography'] ) ? $settings['custom_typography'] : array();
			}
		}
		$prefix = function_exists( 'edtm_detect_typo_prefix' ) ? edtm_detect_typo_prefix( $kit_fonts ) : 'typography_typo';

		if ( is_array( $kit_colors ) ) {
			foreach ( $kit_colors as $item ) {
				if ( ! is_array( $item ) ) { continue; }
				$id    = isset( $item['_id'] ) ? (string) $item['_id'] : '';
				$title = isset( $item['title'] ) ? (string) $item['title'] : '';
				$hex   = isset( $item['color'] ) ? (string) $item['color'] : '';
				if ( '' === $title || '' === $hex ) { continue; }
				$colors_norm[] = array(
					'id'    => $preserve_ids ? $id : '',
					'title' => $title,
					'color' => $hex,
				);
			}
		}
		if ( is_array( $kit_fonts ) ) {
			foreach ( $kit_fonts as $item ) {
				if ( ! is_array( $item ) ) { continue; }
				$id    = isset( $item['_id'] ) ? (string) $item['_id'] : '';
				$title = isset( $item['title'] ) ? (string) $item['title'] : '';
				if ( '' === $title ) { continue; }

				$family = isset( $item[ $prefix . '_font_family' ] ) ? (string) $item[ $prefix . '_font_family' ] : ( isset( $item['typography_font_family'] ) ? (string) $item['typography_font_family'] : '' );

				$size = '';
				foreach ( array_keys( $item ) as $k ) {
					if ( preg_match( '/_font_size_custom$/', $k ) && is_string( $item[ $k ] ) && '' !== trim( $item[ $k ] ) ) {
						$size = trim( $item[ $k ] );
						break;
					}
				}
				if ( '' === $size ) {
					foreach ( array( $prefix . '_font_size', 'typography_font_size' ) as $k ) {
						if ( isset( $item[ $k ] ) && is_array( $item[ $k ] ) && isset( $item[ $k ]['unit'] ) && 'custom' === $item[ $k ]['unit'] && ! empty( $item[ $k ]['size'] ) && is_string( $item[ $k ]['size'] ) ) {
							$size = trim( (string) $item[ $k ]['size'] );
							break;
						}
					}
				}
				if ( '' === $size && isset( $item[ $prefix . '_font_size' ] ) && is_array( $item[ $prefix . '_font_size' ] ) && function_exists( 'edtm_dimension_to_string' ) ) {
					$size = edtm_dimension_to_string( $item[ $prefix . '_font_size' ] );
				} elseif ( '' === $size && isset( $item['typography_font_size' ] ) && is_array( $item['typography_font_size' ] ) && function_exists( 'edtm_dimension_to_string' ) ) {
					$size = edtm_dimension_to_string( $item['typography_font_size'] );
				}

				$weight = isset( $item[ $prefix . '_font_weight' ] ) ? (string) $item[ $prefix . '_font_weight' ] : ( isset( $item['typography_font_weight'] ) ? (string) $item['typography_font_weight'] : '' );
				$lh     = '';
				if ( isset( $item[ $prefix . '_line_height' ] ) && is_array( $item[ $prefix . '_line_height' ] ) && function_exists( 'edtm_dimension_to_string' ) ) {
					$lh = edtm_dimension_to_string( $item[ $prefix . '_line_height' ] );
				} elseif ( isset( $item['typography_line_height' ] ) && is_array( $item['typography_line_height' ] ) && function_exists( 'edtm_dimension_to_string' ) ) {
					$lh = edtm_dimension_to_string( $item['typography_line_height'] );
				}

				$fonts_norm[] = array(
					'id'          => $preserve_ids ? $id : '',
					'title'       => $title,
					'family'      => $family,
					'size'        => $size,
					'weight'      => $weight,
					'line_height' => $lh,
				);
			}
		}
	} else {
		$opt_colors = get_option( 'elementor_scheme_color', array() );
		$opt_fonts  = get_option( 'elementor_scheme_typography', array() );
		$opt_colors = is_array( $opt_colors ) ? $opt_colors : array();
		$opt_fonts  = is_array( $opt_fonts ) ? $opt_fonts : array();

		$ids_by_title_color = array();
		$ids_by_title_font  = array();
		if ( $preserve_ids ) {
			$kit = function_exists( 'edtm_get_active_kit' ) ? edtm_get_active_kit() : false;
			if ( $kit && method_exists( $kit, 'get_settings' ) ) {
				try {
					$kcolors = $kit->get_settings( 'custom_colors' );
					if ( is_array( $kcolors ) ) {
						foreach ( $kcolors as $it ) {
							if ( isset( $it['title'], $it['_id'] ) ) {
								$ids_by_title_color[ strtolower( (string) $it['title'] ) ] = (string) $it['_id'];
							}
						}
					}
				} catch ( \Throwable $e ) {}
				try {
					$kfonts = $kit->get_settings( 'custom_typography' );
					if ( is_array( $kfonts ) ) {
						foreach ( $kfonts as $it ) {
							if ( isset( $it['title'], $it['_id'] ) ) {
								$ids_by_title_font[ strtolower( (string) $it['title'] ) ] = (string) $it['_id'];
							}
						}
					}
				} catch ( \Throwable $e ) {}
			}
		}

		foreach ( $opt_colors as $title => $hex ) {
			$colors_norm[] = array(
				'id'    => $preserve_ids && isset( $ids_by_title_color[ strtolower( (string) $title ) ] ) ? $ids_by_title_color[ strtolower( (string) $title ) ] : '',
				'title' => (string) $title,
				'color' => (string) $hex,
			);
		}
		foreach ( $opt_fonts as $title => $props ) {
			$props = is_array( $props ) ? $props : array();
			$fonts_norm[] = array(
				'id'          => $preserve_ids && isset( $ids_by_title_font[ strtolower( (string) $title ) ] ) ? $ids_by_title_font[ strtolower( (string) $title ) ] : '',
				'title'       => (string) $title,
				'family'      => isset( $props['family'] ) ? (string) $props['family'] : '',
				'size'        => isset( $props['size'] ) ? (string) $props['size'] : '',
				'weight'      => isset( $props['weight'] ) ? (string) $props['weight'] : '',
				'line_height' => isset( $props['line_height'] ) ? (string) $props['line_height'] : '',
			);
		}
	}

	$payload['colors'] = array_values( $colors_norm );
	$payload['fonts']  = array_values( $fonts_norm );
	return $payload;
}

/**
 * Determine current section from the request context.
 * Priority: referer query -> POST edtm_current_section -> user meta -> default 'fonts'
 */
function edtm_detect_section_from_request() {
	$section = '';

	// Try referer first - but sanitize heavily and only accept our known values
	if ( ! empty( $_SERVER['HTTP_REFERER'] ) ) {
		$referer = wp_sanitize_redirect( wp_unslash( $_SERVER['HTTP_REFERER'] ) );
		if ( $referer ) {
			$ref = wp_parse_url( $referer );
			if ( ! empty( $ref['query'] ) ) {
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$ref_q = array();
				parse_str( $ref['query'], $ref_q );
				if ( isset( $ref_q['edtm_section'] ) && in_array( sanitize_key( $ref_q['edtm_section'] ), array( 'colors', 'fonts' ), true ) ) {
					$section = sanitize_key( $ref_q['edtm_section'] );
				}
			}
		}
	}

	/**
	 * Section detection from POST is safe without nonce verification because:
	 * 1. This helper only returns a whitelisted value (colors|fonts)
	 * 2. The calling handlers (export/import/push/pull) all verify nonces
	 * 3. At worst, returning the wrong section just shows wrong panel
	 * 4. User preference updates have separate nonce checks
	 */
	// phpcs:disable WordPress.Security.NonceVerification
	if ( empty( $section ) && isset( $_POST['edtm_current_section'] ) ) {
		$val = sanitize_key( wp_unslash( $_POST['edtm_current_section'] ) );
		if ( in_array( $val, array( 'colors', 'fonts' ), true ) ) {
			$section = $val;
		}
	}
	// phpcs:enable
	if ( empty( $section ) ) {
		$user_id = get_current_user_id();
		if ( $user_id ) {
			$pref = get_user_meta( $user_id, 'edtm_last_active_section', true );
			if ( in_array( $pref, array( 'colors', 'fonts' ), true ) ) {
				$section = $pref;
			}
		}
	}
	return $section ?: 'fonts';
}

/**
 * Export handler.
 */
function edtm_handle_export_tokens() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to export.', 'design-tokens-manager-for-elementor' ) );
	}
	check_admin_referer( 'edtm_import_export', 'edtm_ie_nonce' );

	$source       = isset( $_POST['edtm_export_source'] ) ? sanitize_text_field( wp_unslash( $_POST['edtm_export_source'] ) ) : 'kit';
	$preserve_ids = ! empty( $_POST['edtm_export_preserve_ids'] );

	$payload = edtm_build_export_payload( $source, $preserve_ids );

	// Persist user's section preference (detect robustly)
	$current_section = edtm_detect_section_from_request();
	if ( $current_section && get_current_user_id() ) {
		update_user_meta( get_current_user_id(), 'edtm_last_active_section', $current_section );
	}

	// FIX: Use gmdate() instead of date() for timezone safety
	$filename = 'elementor-tokens-' . gmdate( 'Ymd-His' ) . '.json';
	// Sanitize filename and quote it for Content-Disposition
	$safe_filename = function_exists( 'sanitize_file_name' ) ? sanitize_file_name( $filename ) : $filename;
	nocache_headers();
	header( 'Content-Type: application/json; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="' . $safe_filename . '"' );
	echo wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
	exit;
}

/**
 * Import handler (JSON upload).
 */
function edtm_handle_import_tokens() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to import.', 'design-tokens-manager-for-elementor' ) );
	}
	check_admin_referer( 'edtm_import_export', 'edtm_ie_nonce' );

	$mode         = isset( $_POST['edtm_import_mode'] ) ? sanitize_text_field( wp_unslash( $_POST['edtm_import_mode'] ) ) : 'merge';
	$preserve_ids = ! empty( $_POST['edtm_import_preserve_ids'] );

	// FIX: Properly validate and sanitize $_FILES access
	if ( ! isset( $_FILES['edtm_import_file'] ) ) {
		wp_safe_redirect( edtm_get_admin_page_url( array( 'edtm-import' => '0' ) ) );
		exit;
	}

// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- File validation handled via wp_check_filetype() and tmp_name is server-generated
$uploaded_file = $_FILES['edtm_import_file'];

	if ( empty( $uploaded_file['tmp_name'] ) ) {
		wp_safe_redirect( edtm_get_admin_page_url( array( 'edtm-import' => '0' ) ) );
		exit;
	}

	// Validate file type
	$file_type = wp_check_filetype( $uploaded_file['name'], array( 'json' => 'application/json' ) );
	if ( 'json' !== $file_type['ext'] ) {
		wp_safe_redirect( edtm_get_admin_page_url( array( 'edtm-import' => '0' ) ) );
		exit;
	}

	// Read file content (tmp_name is already validated)
	$content = file_get_contents( $uploaded_file['tmp_name'] );
	$data    = json_decode( $content, true );

	if ( empty( $data ) || ! is_array( $data ) ) {
		wp_safe_redirect( edtm_get_admin_page_url( array( 'edtm-import' => '0' ) ) );
		exit;
	}

	$colors = isset( $data['colors'] ) && is_array( $data['colors'] ) ? $data['colors'] : array();
	$fonts  = isset( $data['fonts'] ) && is_array( $data['fonts'] ) ? $data['fonts'] : array();

	$colors_norm = array();
	foreach ( $colors as $c ) {
		$title = isset( $c['title'] ) ? sanitize_text_field( $c['title'] ) : '';
		$hex   = isset( $c['color'] ) ? sanitize_hex_color( $c['color'] ) : '';
		$id    = isset( $c['id'] ) ? sanitize_text_field( $c['id'] ) : '';
		if ( '' === $title || '' === $hex ) { continue; }
		$colors_norm[] = array(
			'id'    => $preserve_ids ? $id : '',
			'title' => $title,
			'color' => $hex,
		);
	}

	$fonts_norm = array();
	foreach ( $fonts as $f ) {
		$title       = isset( $f['title'] ) ? sanitize_text_field( $f['title'] ) : '';
		$id          = isset( $f['id'] ) ? sanitize_text_field( $f['id'] ) : '';
		$family      = isset( $f['family'] ) ? edtm_sanitize_font_family( $f['family'] ) : '';
		$size        = isset( $f['size'] ) ? edtm_sanitize_font_size( $f['size'] ) : '';
		$weight      = isset( $f['weight'] ) ? absint( $f['weight'] ) : 0;
		$line_height = isset( $f['line_height'] ) ? edtm_sanitize_line_height( $f['line_height'] ) : '';
		if ( '' === $title ) { continue; }
		$fonts_norm[] = array(
			'id'          => $preserve_ids ? $id : '',
			'title'       => $title,
			'family'      => $family,
			'size'        => $size,
			'weight'      => $weight,
			'line_height' => $line_height,
		);
	}

	$colors_option = array();
	foreach ( $colors_norm as $c ) {
		$colors_option[ $c['title'] ] = $c['color'];
	}
	$fonts_option = array();
	foreach ( $fonts_norm as $f ) {
		$fonts_option[ $f['title'] ] = array(
			'family'      => $f['family'],
			'size'        => $f['size'],
			'weight'      => $f['weight'],
			'line_height' => $f['line_height'],
		);
	}
	update_option( 'elementor_scheme_color', $colors_option );
	update_option( 'elementor_scheme_typography', $fonts_option );

	$ok = false;
	if ( 'replace' === $mode ) {
		$ok = edtm_import_apply_replace( $colors_norm, $fonts_norm );
	} else {
		$ok = ( 'success' === edtm_apply_kit_settings_from_normalized( $colors_norm, $fonts_norm ) );
	}

	// Persist user's preference (detect robustly)
	$current_section = edtm_detect_section_from_request();
	$user_id = get_current_user_id();
	if ( $user_id ) {
		update_user_meta( $user_id, 'edtm_last_active_section', $current_section );
	}

	wp_safe_redirect( edtm_get_admin_page_url( array(
		'settings-updated' => 1,
		'edtm-import'      => $ok ? '1' : '0',
		'edtm_section'     => $current_section,
		'edtm_view'        => 'manage',
	) ) );
	exit;
}

/**
 * Replace Site Settings directly via meta (Elementor-agnostic), with clamp handling.
 */
function edtm_import_apply_replace( $colors_norm, $fonts_norm ) {
	if ( ! function_exists( 'edtm_get_active_kit_id' ) ) {
		return false;
	}
	$kit_id = edtm_get_active_kit_id();
	if ( $kit_id <= 0 ) {
		return false;
	}
	$settings = get_post_meta( $kit_id, '_elementor_page_settings', true );
	$settings = is_array( $settings ) ? $settings : array();
	$existing_fonts = isset( $settings['custom_typography'] ) && is_array( $settings['custom_typography'] ) ? $settings['custom_typography'] : array();
	$prefix = function_exists( 'edtm_detect_typo_prefix' ) ? edtm_detect_typo_prefix( $existing_fonts ) : 'typography_typo';

	$final_colors = array();
	foreach ( $colors_norm as $c ) {
		$final_colors[] = array(
			'_id'   => ! empty( $c['id'] ) ? $c['id'] : ( 'edtm_c_' . strtolower( wp_generate_password( 8, false, false ) ) ),
			'title' => $c['title'],
			'color' => $c['color'],
		);
	}

	$final_fonts = array();
	foreach ( $fonts_norm as $f ) {
		$id = ! empty( $f['id'] ) ? $f['id'] : ( 'edtm_t_' . strtolower( wp_generate_password( 8, false, false ) ) );

		$item = array(
			'_id'   => $id,
			'title' => $f['title'],
			$prefix . '_typography' => 'custom',
			'typography_typo'       => 'custom',
			'typography_typo_typography' => 'custom',
		);
		if ( ! empty( $f['family'] ) ) {
			$first_family = trim( explode( ',', $f['family'] )[0] );
			$item[ $prefix . '_font_family' ] = $first_family;
			$item['typography_font_family' ]  = $first_family;
		}
		if ( ! empty( $f['size'] ) && preg_match( '/^clamp\(.+\)$/i', $f['size'] ) ) {
			$item[ $prefix . '_font_size_custom' ] = $f['size'];
			$item['typography_font_size_custom' ]  = $f['size'];
			$item['typography_typo_font_size_custom' ] = $f['size'];
			$item[ $prefix . '_font_size' ] = array( 'unit' => 'custom', 'size' => $f['size'], 'sizes' => array() );
			$item['typography_font_size' ]  = array( 'unit' => 'custom', 'size' => $f['size'], 'sizes' => array() );
		} elseif ( ! empty( $f['size'] ) && preg_match( '/^\d*\.?\d+\s*(px|rem|em)$/i', $f['size'] ) ) {
			if ( function_exists( 'edtm_to_elementor_dimension' ) ) {
				$dim = edtm_to_elementor_dimension( $f['size'] );
				if ( $dim ) {
					$item[ $prefix . '_font_size' ] = $dim;
					$item['typography_font_size' ]  = $dim;
				}
			}
		}
		if ( ! empty( $f['weight'] ) ) {
			$item[ $prefix . '_font_weight' ] = (string) absint( $f['weight'] );
			$item['typography_font_weight' ]  = (string) absint( $f['weight'] );
		}
		if ( ! empty( $f['line_height'] ) && preg_match( '/^\s*(\d*\.?\d+)\s*em\s*$/i', $f['line_height'], $m ) ) {
			$item[ $prefix . '_line_height' ] = array( 'unit' => 'em', 'size' => (float) $m[1], 'sizes' => array() );
			$item['typography_line_height' ]  = array( 'unit' => 'em', 'size' => (float) $m[1], 'sizes' => array() );
		}
		$final_fonts[] = $item;
	}

	$settings['custom_colors']     = $final_colors;
	$settings['custom_typography'] = $final_fonts;

	update_post_meta( $kit_id, '_elementor_page_settings', $settings );

	if ( class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->files_manager ) ) {
		try { \Elementor\Plugin::$instance->files_manager->clear_cache(); } catch ( \Throwable $e ) {}
	}
	return true;
}

/**
 * Pull from Site Settings → plugin options (UI).
 */
function edtm_handle_pull_from_kit() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to pull.', 'design-tokens-manager-for-elementor' ) );
	}
	check_admin_referer( 'edtm_sync_tools', 'edtm_sync_nonce' );

	$payload = edtm_build_export_payload( 'kit', false );
	$colors  = isset( $payload['colors'] ) ? $payload['colors'] : array();
	$fonts   = isset( $payload['fonts'] ) ? $payload['fonts'] : array();

	$colors_option = array();
	foreach ( $colors as $c ) {
		if ( empty( $c['title'] ) || empty( $c['color'] ) ) { continue; }
		$colors_option[ $c['title'] ] = $c['color'];
	}
	$fonts_option = array();
	foreach ( $fonts as $f ) {
		if ( empty( $f['title'] ) ) { continue; }
		$fonts_option[ $f['title'] ] = array(
			'family'      => isset( $f['family'] ) ? $f['family'] : '',
			'size'        => isset( $f['size'] ) ? $f['size'] : '',
			'weight'      => isset( $f['weight'] ) ? $f['weight'] : '',
			'line_height' => isset( $f['line_height'] ) ? $f['line_height'] : '',
		);
	}

	update_option( 'elementor_scheme_color', $colors_option );
	update_option( 'elementor_scheme_typography', $fonts_option );

	// Preserve current section and persist preference (detect robustly)
	$current_section = edtm_detect_section_from_request();

	// Persist user's preference server-side so future loads remain on this section
	$user_id = get_current_user_id();
	if ( $user_id ) {
		update_user_meta( $user_id, 'edtm_last_active_section', $current_section );
	}

	wp_safe_redirect( edtm_get_admin_page_url( array(
		'settings-updated' => 1,
		'edtm-pulled'      => 1,
		'edtm_section'     => $current_section,
		'edtm_view'        => 'manage',
	) ) );
	exit;
}

/**
 * Push (overwrite) Site Settings from plugin options.
 */
function edtm_handle_push_to_kit() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to push.', 'design-tokens-manager-for-elementor' ) );
	}
	check_admin_referer( 'edtm_sync_tools', 'edtm_sync_nonce' );

	$opt_colors = get_option( 'elementor_scheme_color', array() );
	$opt_fonts  = get_option( 'elementor_scheme_typography', array() );

	$colors_norm = array();
	foreach ( (array) $opt_colors as $title => $hex ) {
		if ( empty( $title ) || empty( $hex ) ) { continue; }
		$colors_norm[] = array(
			'id'    => '',
			'title' => (string) $title,
			'color' => (string) $hex,
		);
	}
	$fonts_norm = array();
	foreach ( (array) $opt_fonts as $title => $props ) {
		$props = is_array( $props ) ? $props : array();
		$fonts_norm[] = array(
			'id'          => '',
			'title'       => (string) $title,
			'family'      => isset( $props['family'] ) ? (string) $props['family'] : '',
			'size'        => isset( $props['size'] ) ? (string) $props['size'] : '',
			'weight'      => isset( $props['weight'] ) ? (string) $props['weight'] : '',
			'line_height' => isset( $props['line_height'] ) ? (string) $props['line_height'] : '',
		);
	}

	$ok = edtm_import_apply_replace( $colors_norm, $fonts_norm );


	// Preserve current section and persist preference (detect robustly)
	$current_section = edtm_detect_section_from_request();
	$user_id = get_current_user_id();
	if ( $user_id ) {
		update_user_meta( $user_id, 'edtm_last_active_section', $current_section );
	}

	wp_safe_redirect( edtm_get_admin_page_url( array(
		'settings-updated' => 1,
		'edtm-pushed'      => $ok ? 1 : 0,
		'edtm_section'     => $current_section,
		'edtm_view'        => 'manage',
	) ) );
	exit;
}
