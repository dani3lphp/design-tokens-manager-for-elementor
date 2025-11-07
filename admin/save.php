<?php
/**
 * Save handler and Elementor Kit sync (merge-based with clamp() support + meta fallback).
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/* ========== Sanitizers ========== */
function edtm_sanitize_font_family( $value ) {
	$value = wp_strip_all_tags( (string) $value );
	$value = str_replace( array( '"', "'" ), '', $value );
	$value = preg_replace( '/[^a-z0-9\-,\s]/i', '', $value );
	return trim( $value );
}
function edtm_sanitize_font_size( $value ) {
	$value = wp_strip_all_tags( (string) $value );
	$value = trim( $value );
	if ( preg_match( '/^\d*\.?\d+\s*(px|rem|em)$/i', $value ) ) {
		return $value;
	}
	if ( preg_match( '/^clamp\(\s*.+\s*,\s*.+\s*,\s*.+\s*\)$/i', $value ) ) {
		return $value;
	}
	return '';
}
function edtm_sanitize_line_height( $value ) {
	$value = wp_strip_all_tags( (string) $value );
	$value = trim( $value );
	if ( preg_match( '/^\d*\.?\d+\s*em$/i', $value ) ) {
		return $value;
	}
	return '';
}

/* ========== Parsing helpers (bulk with clamp/font stacks) ========== */
function edtm_split_top_level( $text ) {
	$out = array(); $buf = ''; $depth = 0; $len = strlen( $text );
	for ( $i = 0; $i < $len; $i++ ) {
		$ch = $text[ $i ];
		if ( '(' === $ch ) { $depth++; $buf .= $ch; continue; }
		if ( ')' === $ch ) { if ( $depth > 0 ) { $depth--; } $buf .= $ch; continue; }
		if ( ',' === $ch && 0 === $depth ) { $out[] = trim( $buf ); $buf = ''; continue; }
		$buf .= $ch;
	}
	if ( '' !== trim( $buf ) ) { $out[] = trim( $buf ); }
	return $out;
}
function edtm_is_size_token( $value ) {
	$value = trim( (string) $value );
	if ( preg_match( '/^clamp\(.+\)$/i', $value ) ) { return true; }
	if ( preg_match( '/^\d*\.?\d+\s*(px|rem|em)$/i', $value ) ) { return true; }
	return false;
}
function edtm_parse_bulk_colors( $text ) {
	$tokens = array(); if ( empty( $text ) ) return $tokens;
	$lines = preg_split( '/\r\n|\r|\n/', $text );
	foreach ( $lines as $line ) {
		$line = trim( $line ); if ( '' === $line || false === strpos( $line, ':' ) ) continue;
		list( $name, $color ) = array_map( 'trim', explode( ':', $line, 2 ) );
		$name = sanitize_text_field( $name ); $color = sanitize_hex_color( $color );
		if ( $name && $color ) { $tokens[] = array( 'id' => '', 'title' => $name, 'color' => $color ); }
	}
	return $tokens;
}
function edtm_parse_bulk_fonts( $text ) {
	$tokens = array(); if ( empty( $text ) ) return $tokens;
	$lines = preg_split( '/\r\n|\r|\n/', $text );
	foreach ( $lines as $line ) {
		$line = trim( $line ); if ( '' === $line || false === strpos( $line, ':' ) ) continue;
		list( $name, $rest ) = array_map( 'trim', explode( ':', $line, 2 ) );
		$name = sanitize_text_field( $name ); if ( '' === $name ) continue;

		$parts = edtm_split_top_level( $rest );

		$family_parts = array();
		$size = ''; $weight = 0; $line_height = '';

		foreach ( $parts as $seg ) {
			if ( '' === $size && edtm_is_size_token( $seg ) ) { $size = edtm_sanitize_font_size( $seg ); continue; }
			if ( '' !== $size && 0 === $weight && preg_match( '/^\d{2,4}$/', trim( $seg ) ) ) { $weight = absint( trim( $seg ) ); continue; }
			if ( '' !== $size && '' === $line_height ) {
				$lh = edtm_sanitize_line_height( $seg );
				if ( '' !== $lh ) { $line_height = $lh; continue; }
			}
			$family_parts[] = $seg;
		}

		$family = edtm_sanitize_font_family( implode( ', ', $family_parts ) );
		if ( $weight && ( $weight < 100 || $weight > 1000 ) ) { $weight = 400; }

		$tokens[] = array(
			'id'          => '',
			'title'       => $name,
			'family'      => $family,
			'size'        => $size,
			'weight'      => $weight,
			'line_height' => $line_height,
		);
	}
	return $tokens;
}

/* ========== Elementor helpers ========== */
function edtm_get_active_kit() {
	if ( class_exists( '\Elementor\Plugin' ) ) {
		try {
			$plugin = ! empty( \Elementor\Plugin::$instance ) ? \Elementor\Plugin::$instance : ( method_exists( '\Elementor\Plugin', 'instance' ) ? \Elementor\Plugin::instance() : null );
			if ( $plugin && isset( $plugin->kits_manager ) && is_object( $plugin->kits_manager ) ) {
				$kit = $plugin->kits_manager->get_active_kit();
				if ( $kit ) { return $kit; }
			}
		} catch ( \Throwable $e ) {}
	}
	if ( class_exists( '\Elementor\Core\Kits\Documents\Kit' ) && method_exists( '\Elementor\Core\Kits\Documents\Kit', 'get_instance' ) ) {
		try { return \Elementor\Core\Kits\Documents\Kit::get_instance(); } catch ( \Throwable $e ) { return false; }
	}
	return false;
}
function edtm_get_active_kit_id() {
	if ( class_exists( '\Elementor\Plugin' ) ) {
		try {
			$plugin = ! empty( \Elementor\Plugin::$instance ) ? \Elementor\Plugin::$instance : ( method_exists( '\Elementor\Plugin', 'instance' ) ? \Elementor\Plugin::instance() : null );
			if ( $plugin && isset( $plugin->kits_manager ) && is_object( $plugin->kits_manager ) ) {
				if ( method_exists( $plugin->kits_manager, 'get_active_id' ) ) {
					$id = (int) $plugin->kits_manager->get_active_id();
					if ( $id > 0 ) { return $id; }
				}
				$kit = $plugin->kits_manager->get_active_kit();
				if ( $kit && method_exists( $kit, 'get_main_id' ) ) {
					$id = (int) $kit->get_main_id();
					if ( $id > 0 ) { return $id; }
				}
				if ( $kit && method_exists( $kit, 'get_id' ) ) {
					$id = (int) $kit->get_id();
					if ( $id > 0 ) { return $id; }
				}
			}
		} catch ( \Throwable $e ) {}
	}
	$id = (int) get_option( 'elementor_active_kit', 0 );
	if ( $id > 0 ) { return $id; }
	$q = new \WP_Query( array(
		'post_type'      => 'elementor_library',
		'post_status'    => 'publish',
		'posts_per_page' => 1,
		'meta_query'     => array(
			array( 'key' => '_elementor_template_type', 'value' => 'kit' ),
		),
	) );
	if ( $q->have_posts() ) {
		return (int) $q->posts[0]->ID;
	}
	return 0;
}
function edtm_add_sync_notice( $message, $type = 'success' ) {
	set_transient( 'edtm_sync_notice', array( 'message' => (string) $message, 'type' => (string) $type ), 60 );
}

/* ========== Dimension helpers (numeric only; clamp custom handled elsewhere) ========== */
function edtm_to_elementor_dimension( $value ) {
	$value = trim( (string) $value );
	if ( preg_match( '/^\s*(\d*\.?\d+)\s*(px|rem|em)\s*$/i', $value, $m ) ) {
		return array( 'unit' => strtolower( $m[2] ), 'size' => (float) $m[1], 'sizes' => array() );
	}
	return null;
}
function edtm_dimension_to_string( $dim ) {
	if ( is_array( $dim ) && isset( $dim['unit'] ) && isset( $dim['size'] ) ) {
		$unit = trim( (string) $dim['unit'] );
		$size = $dim['size'];
		if ( is_numeric( $size ) ) {
			$size_num = (float) $size;
			return ( ( $size_num == (int) $size_num ) ? (string) (int) $size_num : (string) $size_num ) . $unit;
		}
		if ( is_string( $size ) && '' !== trim( $size ) ) {
			return trim( $size );
		}
	}
	return '';
}

/* ========== Prefix + custom-size keys detection ========== */
function edtm_detect_typo_prefix( $existing_fonts ) {
	if ( ! is_array( $existing_fonts ) ) { return 'typography_typo'; }
	foreach ( $existing_fonts as $item ) {
		if ( ! is_array( $item ) ) { continue; }
		foreach ( array_keys( $item ) as $key ) {
			if ( preg_match( '/^(.+)_font_size_custom$/', $key, $m ) ) { return $m[1]; }
			if ( preg_match( '/^(.+)_font_size$/', $key, $m ) ) { return $m[1]; }
			if ( preg_match( '/^(.+)_font_family$/', $key, $m ) ) { return $m[1]; }
			if ( preg_match( '/^(.+)_typography$/', $key, $m ) ) { return $m[1]; }
		}
	}
	return 'typography_typo';
}
function edtm_collect_item_custom_size_keys( $item ) {
	$keys = array();
	if ( ! is_array( $item ) ) { return $keys; }
	foreach ( array_keys( $item ) as $k ) {
		if ( preg_match( '/_font_size_custom$/', $k ) ) {
			$keys[] = $k;
		}
	}
	return array_values( array_unique( $keys ) );
}
function edtm_all_size_custom_keys( $prefix ) {
	$prefix = (string) $prefix;
	return array_values( array_unique( array(
		$prefix . '_font_size_custom',
		'typography_font_size_custom',
		'typography_typo_font_size_custom',
	) ) );
}
function edtm_set_clamp_on_item( &$item, $prefix, $clamp ) {
	$existing_custom_keys = edtm_collect_item_custom_size_keys( $item );
	$targets = array_unique( array_merge( $existing_custom_keys, edtm_all_size_custom_keys( $prefix ) ) );
	foreach ( $targets as $k ) {
		$item[ $k ] = $clamp;
	}
	// Some builds read clamp from size when unit=custom:
	$item[ $prefix . '_font_size' ] = array( 'unit' => 'custom', 'size' => $clamp, 'sizes' => array() );
	$item['typography_font_size' ]  = array( 'unit' => 'custom', 'size' => $clamp, 'sizes' => array() );
}

/* ========== ID generators ========== */
function edtm_generate_color_id() { return EDTM_COLOR_ID_PREFIX . strtolower( wp_generate_password( 8, false, false ) ); }
function edtm_generate_typo_id()  { return EDTM_TYPO_ID_PREFIX  . strtolower( wp_generate_password( 8, false, false ) ); }

/* ========== Meta fallback (merge-based) ========== */
function edtm_get_kit_settings_meta( $kit_id ) {
	if ( $kit_id <= 0 ) { return array(); }
	$settings = get_post_meta( $kit_id, '_elementor_page_settings', true );
	return is_array( $settings ) ? $settings : array();
}
function edtm_apply_kit_settings_via_meta_merged( $colors_norm, $fonts_norm ) {
	$kit_id = edtm_get_active_kit_id();
	if ( $kit_id <= 0 ) { return false; }

	$settings = edtm_get_kit_settings_meta( $kit_id );
	$existing_colors = isset( $settings['custom_colors'] ) && is_array( $settings['custom_colors'] ) ? $settings['custom_colors'] : array();
	$existing_fonts  = isset( $settings['custom_typography'] ) && is_array( $settings['custom_typography'] ) ? $settings['custom_typography'] : array();

	$by_id = array(); $by_title = array();
	foreach ( $existing_fonts as $it ) {
		if ( is_array( $it ) ) {
			if ( ! empty( $it['_id'] ) ) { $by_id[ $it['_id'] ] = $it; }
			if ( ! empty( $it['title'] ) ) { $by_title[ strtolower( $it['title'] ) ] = $it; }
		}
	}
	$prefix = edtm_detect_typo_prefix( $existing_fonts );

	$final_colors = array();
	foreach ( $colors_norm as $c ) {
		$title = isset( $c['title'] ) ? (string) $c['title'] : '';
		$hex   = isset( $c['color'] ) ? (string) $c['color'] : '';
		$id    = isset( $c['id'] ) ? (string) $c['id'] : '';
		if ( '' === $title || '' === $hex ) { continue; }
		if ( '' === $id ) { $id = edtm_generate_color_id(); }
		$final_colors[] = array( '_id' => $id, 'title' => $title, 'color' => $hex );
	}

	$final_fonts = array();
	foreach ( $fonts_norm as $f ) {
		$title = isset( $f['title'] ) ? (string) $f['title'] : '';
		if ( '' === $title ) { continue; }
		$id    = isset( $f['id'] ) ? (string) $f['id'] : '';

		$family      = isset( $f['family'] ) ? (string) $f['family'] : '';
		$size        = isset( $f['size'] ) ? (string) $f['size'] : '';
		$weight      = isset( $f['weight'] ) ? (string) ( '' === $f['weight'] ? '' : (string) absint( $f['weight'] ) ) : '';
		$line_height = isset( $f['line_height'] ) ? (string) $f['line_height'] : '';

		$base = array();
		if ( '' !== $id && isset( $by_id[ $id ] ) ) {
			$base = $by_id[ $id ];
		} elseif ( isset( $by_title[ strtolower( $title ) ] ) ) {
			$base = $by_title[ strtolower( $title ) ];
			$id   = isset( $base['_id'] ) ? $base['_id'] : $id;
		}
		if ( '' === $id ) { $id = edtm_generate_typo_id(); }

		$item = is_array( $base ) ? $base : array();
		$item['_id']   = $id;
		$item['title'] = $title;

		$item[ $prefix . '_typography' ] = 'custom';
		$item['typography_typo']         = 'custom';
		$item['typography_typo_typography'] = 'custom';

		if ( '' !== $family ) {
			$first = trim( explode( ',', $family )[0] );
			$item[ $prefix . '_font_family' ] = $first;
			$item['typography_font_family' ]  = $first;
		}
		if ( '' !== $size ) {
			if ( preg_match( '/^clamp\(.+\)$/i', $size ) ) {
				edtm_set_clamp_on_item( $item, $prefix, $size );
			} else {
				$dim = edtm_to_elementor_dimension( $size );
				if ( $dim ) {
					$item[ $prefix . '_font_size' ] = $dim;
					$item['typography_font_size' ]  = $dim;
				}
			}
		}
		if ( '' !== $weight ) {
			$item[ $prefix . '_font_weight' ] = $weight;
			$item['typography_font_weight' ]  = $weight;
		}
		if ( preg_match( '/^\s*(\d*\.?\d+)\s*em\s*$/i', $line_height, $m ) ) {
			$lh_dim = array( 'unit' => 'em', 'size' => (float) $m[1], 'sizes' => array() );
			$item[ $prefix . '_line_height' ] = $lh_dim;
			$item['typography_line_height' ]  = $lh_dim;
		}

		$final_fonts[] = $item;
	}

	$settings['custom_colors']     = $final_colors;
	$settings['custom_typography'] = $final_fonts;

	update_post_meta( $kit_id, '_elementor_page_settings', $settings );
	return true;
}

/**
 * Apply to Kit via API (merge existing items to preserve unknown keys). Fallback to meta.
 */
function edtm_apply_kit_settings_from_normalized( $colors_norm, $fonts_norm ) {
	$kit = edtm_get_active_kit();

	if ( ! $kit || ! method_exists( $kit, 'get_settings' ) || ! method_exists( $kit, 'set_settings' ) ) {
		return edtm_apply_kit_settings_via_meta_merged( $colors_norm, $fonts_norm ) ? 'success' : 'queued';
	}

	try {
		$existing_colors = $kit->get_settings( 'custom_colors' );
		$existing_fonts  = $kit->get_settings( 'custom_typography' );
	} catch ( \Throwable $e ) {
		$existing_colors = array();
		$existing_fonts  = array();
	}

	$existing_colors = is_array( $existing_colors ) ? $existing_colors : array();
	$existing_fonts  = is_array( $existing_fonts ) ? $existing_fonts  : array();

	$by_id = array(); $by_title = array();
	foreach ( $existing_fonts as $it ) {
		if ( is_array( $it ) ) {
			if ( ! empty( $it['_id'] ) )     { $by_id[ $it['_id'] ] = $it; }
			if ( ! empty( $it['title'] ) )   { $by_title[ strtolower( $it['title'] ) ] = $it; }
		}
	}
	$prefix = edtm_detect_typo_prefix( $existing_fonts );

	$final_colors = array();
	foreach ( $colors_norm as $c ) {
		$title = isset( $c['title'] ) ? (string) $c['title'] : '';
		$hex   = isset( $c['color'] ) ? (string) $c['color'] : '';
		$id    = isset( $c['id'] ) ? (string) $c['id'] : '';
		if ( '' === $title || '' === $hex ) { continue; }
		if ( '' === $id ) { $id = edtm_generate_color_id(); }
		$final_colors[] = array( '_id' => $id, 'title' => $title, 'color' => $hex );
	}

	$final_fonts = array();
	foreach ( $fonts_norm as $f ) {
		$title = isset( $f['title'] ) ? (string) $f['title'] : '';
		if ( '' === $title ) { continue; }
		$id    = isset( $f['id'] ) ? (string) $f['id'] : '';

		$family      = isset( $f['family'] ) ? (string) $f['family'] : '';
		$size        = isset( $f['size'] ) ? (string) $f['size'] : '';
		$weight      = isset( $f['weight'] ) ? (string) ( '' === $f['weight'] ? '' : (string) absint( $f['weight'] ) ) : '';
		$line_height = isset( $f['line_height'] ) ? (string) $f['line_height'] : '';

		$base = array();
		if ( '' !== $id && isset( $by_id[ $id ] ) ) {
			$base = $by_id[ $id ];
		} elseif ( isset( $by_title[ strtolower( $title ) ] ) ) {
			$base = $by_title[ strtolower( $title ) ];
			$id   = isset( $base['_id'] ) ? $base['_id'] : $id;
		}
		if ( '' === $id ) { $id = edtm_generate_typo_id(); }

		$item = is_array( $base ) ? $base : array();
		$item['_id']   = $id;
		$item['title'] = $title;

		$item[ $prefix . '_typography' ] = 'custom';
		$item['typography_typo']         = 'custom';
		$item['typography_typo_typography'] = 'custom';

		if ( '' !== $family ) {
			$first = trim( explode( ',', $family )[0] );
			$item[ $prefix . '_font_family' ] = $first;
			$item['typography_font_family' ]  = $first;
		}
		if ( '' !== $size ) {
			if ( preg_match( '/^clamp\(.+\)$/i', $size ) ) {
				edtm_set_clamp_on_item( $item, $prefix, $size );
			} else {
				$dim = edtm_to_elementor_dimension( $size );
				if ( $dim ) {
					$item[ $prefix . '_font_size' ] = $dim;
					$item['typography_font_size' ]  = $dim;
				}
			}
		}
		if ( '' !== $weight ) {
			$item[ $prefix . '_font_weight' ] = $weight;
			$item['typography_font_weight' ]  = $weight;
		}
		if ( preg_match( '/^\s*(\d*\.?\d+)\s*em\s*$/i', $line_height, $m ) ) {
			$lh_dim = array( 'unit' => 'em', 'size' => (float) $m[1], 'sizes' => array() );
			$item[ $prefix . '_line_height' ] = $lh_dim;
			$item['typography_line_height' ]  = $lh_dim;
		}

		$final_fonts[] = $item;
	}

	try {
		$kit->set_settings( 'custom_colors', $final_colors );
		$kit->set_settings( 'custom_typography', $final_fonts );
		$kit->save();

		if ( class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->files_manager ) ) {
			try { \Elementor\Plugin::$instance->files_manager->clear_cache(); } catch ( \Throwable $e ) {}
		}
	} catch ( \Throwable $e ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging only when WP_DEBUG_LOG enabled
			error_log( '[EDTM] Kit API save failed, trying meta fallback: ' . $e->getMessage() );
		}
		return edtm_apply_kit_settings_via_meta_merged( $colors_norm, $fonts_norm ) ? 'success' : 'queued';
	}
	return 'success';
}

/* ========== Process queued sync early in admin and after Elementor loads ========== */
function edtm_process_pending_kit_sync() {
	$pending = get_option( 'edtm_pending_kit_sync' );
	if ( empty( $pending ) || ! is_array( $pending ) ) { return; }

	$colors_norm = isset( $pending['colors_norm'] ) && is_array( $pending['colors_norm'] ) ? $pending['colors_norm'] : array();
	$fonts_norm  = isset( $pending['fonts_norm'] ) && is_array( $pending['fonts_norm'] ) ? $pending['fonts_norm'] : array();

	$status = edtm_apply_kit_settings_from_normalized( $colors_norm, $fonts_norm );
	if ( 'success' === $status ) {
		delete_option( 'edtm_pending_kit_sync' );
		edtm_add_sync_notice( __( 'Elementor Site Settings updated (queued sync processed).', 'design-tokens-manager-for-elementor' ), 'success' );
	} elseif ( 'queued' !== $status ) {
		edtm_add_sync_notice( __( 'Could not sync Elementor Site Settings. Please reload the page or check debug log.', 'design-tokens-manager-for-elementor' ), 'warning' );
	}
}

/* ========== Handle form submission ========== */
function edtm_handle_save_tokens() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to perform this action.', 'design-tokens-manager-for-elementor' ) );
	}
	check_admin_referer( 'edtm_save_tokens', 'edtm_nonce' );

	$post = wp_unslash( $_POST );

	$colors_norm = array();
	$fonts_norm  = array();

	$have_color_titles = array();
	if ( isset( $post['edtm_colors'] ) && is_array( $post['edtm_colors'] ) ) {
		foreach ( $post['edtm_colors'] as $row ) {
			$id    = isset( $row['id'] ) ? sanitize_text_field( $row['id'] ) : '';
			$title = isset( $row['token'] ) ? sanitize_text_field( $row['token'] ) : '';
			$hex   = isset( $row['color'] ) ? sanitize_hex_color( $row['color'] ) : '';
			if ( '' === $title || '' === $hex ) { continue; }
			$colors_norm[] = array( 'id' => $id, 'title' => $title, 'color' => $hex );
			$have_color_titles[ strtolower( $title ) ] = true;
		}
	}
	if ( isset( $post['edtm_colors_bulk'] ) && '' !== trim( $post['edtm_colors_bulk'] ) ) {
		$bulk = edtm_parse_bulk_colors( $post['edtm_colors_bulk'] );
		foreach ( $bulk as $c ) {
			$key = strtolower( $c['title'] );
			if ( isset( $have_color_titles[ $key ] ) ) { continue; }
			$colors_norm[] = $c;
		}
	}

	$have_font_titles = array();
	if ( isset( $post['edtm_fonts'] ) && is_array( $post['edtm_fonts'] ) ) {
		foreach ( $post['edtm_fonts'] as $row ) {
			$id          = isset( $row['id'] ) ? sanitize_text_field( $row['id'] ) : '';
			$title       = isset( $row['token'] ) ? sanitize_text_field( $row['token'] ) : '';
			if ( '' === $title ) { continue; }
			$family      = isset( $row['family'] ) ? edtm_sanitize_font_family( $row['family'] ) : '';
			$size        = isset( $row['size'] ) ? edtm_sanitize_font_size( $row['size'] ) : '';
			$weight      = isset( $row['weight'] ) ? absint( $row['weight'] ) : 0;
			$line_height = isset( $row['line_height'] ) ? edtm_sanitize_line_height( $row['line_height'] ) : '';
			if ( $weight && ( $weight < 100 || $weight > 1000 ) ) { $weight = 400; }

			$fonts_norm[] = array(
				'id'          => $id,
				'title'       => $title,
				'family'      => $family,
				'size'        => $size,
				'weight'      => $weight,
				'line_height' => $line_height,
			);
			$have_font_titles[ strtolower( $title ) ] = true;
		}
	}
	if ( isset( $post['edtm_fonts_bulk'] ) && '' !== trim( $post['edtm_fonts_bulk'] ) ) {
		$bulk = edtm_parse_bulk_fonts( $post['edtm_fonts_bulk'] );
		foreach ( $bulk as $f ) {
			$key = strtolower( $f['title'] );
			if ( isset( $have_font_titles[ $key ] ) ) { continue; }
			$fonts_norm[] = $f;
		}
	}

	$colors_option = array();
	foreach ( $colors_norm as $c ) { $colors_option[ $c['title'] ] = $c['color']; }
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

	$kit_status = edtm_apply_kit_settings_from_normalized( $colors_norm, $fonts_norm );

	// Determine current section robustly: Referer query -> POST -> user meta -> default
	$current_section = 'fonts';

	// 1) Prefer the edtm_section value from the referer URL when available (reflects visible page state)
	if ( ! empty( $_SERVER['HTTP_REFERER'] ) ) {
		$referer = wp_sanitize_redirect( wp_unslash( $_SERVER['HTTP_REFERER'] ) );
		if ( $referer ) {
			$ref = wp_parse_url( $referer );
			if ( ! empty( $ref['query'] ) ) {
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$ref_q = array();
				parse_str( $ref['query'], $ref_q );
				if ( isset( $ref_q['edtm_section'] ) && in_array( sanitize_key( $ref_q['edtm_section'] ), array( 'colors', 'fonts' ), true ) ) {
					$current_section = sanitize_key( $ref_q['edtm_section'] );
				}
			}
		}
	}

	// 2) If referer didn't yield a valid section, fall back to posted hidden input
	if ( empty( $current_section ) || ! in_array( $current_section, array( 'colors', 'fonts' ), true ) ) {
		if ( isset( $post['edtm_current_section'] ) && in_array( sanitize_key( $post['edtm_current_section'] ), array( 'colors', 'fonts' ), true ) ) {
			$current_section = sanitize_key( $post['edtm_current_section'] );
		}
	}

	// 3) Final fallback: last saved user preference
	if ( empty( $current_section ) || ! in_array( $current_section, array( 'colors', 'fonts' ), true ) ) {
		$user_id = get_current_user_id();
		if ( $user_id ) {
			$pref = get_user_meta( $user_id, 'edtm_last_active_section', true );
			if ( in_array( $pref, array( 'colors', 'fonts' ), true ) ) {
				$current_section = $pref;
			}
		}
	}

	// Persist user's preference server-side so redirects/load will honor it
	$user_id = get_current_user_id();
	if ( $user_id ) {
		update_user_meta( $user_id, 'edtm_last_active_section', $current_section );
	}

	$args = array(
		'settings-updated' => 1,
		'kit-synced'       => ( 'success' === $kit_status ) ? 1 : 0,
		'edtm_section'     => $current_section,
		'edtm_view'        => 'manage',
	);
	if ( 'queued' === $kit_status ) { $args['kit-queued'] = 1; }

	wp_safe_redirect( edtm_get_admin_page_url( $args ) );
	exit;
}