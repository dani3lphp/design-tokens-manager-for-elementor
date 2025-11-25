<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

/**
 * WP-CLI commands for Design Tokens Manager for Elementor.
 *
 * Examples:
 *  wp edtm export --file=./tokens.json --source=kit --preserve-ids
 *  wp edtm import ./tokens.json --mode=replace --preserve-ids
 *  wp edtm sync
 */
class EDTM_CLI_Command {

	/**
	 * Export tokens to JSON (stdout or file).
	 *
	 * ## OPTIONS
	 * [--file=<path>]
	 * : File path to write. If omitted, prints to STDOUT.
	 *
	 * [--source=<source>]
	 * : kit or plugin. Default: kit
	 *
	 * [--preserve-ids]
	 * : Include token IDs in the export.
	 *
	 * ## EXAMPLES
	 *   wp edtm export --file=./tokens.json --source=kit --preserve-ids
	 *   wp edtm export --source=plugin
	 */
	public function export( $args, $assoc_args ) {
		$source       = isset( $assoc_args['source'] ) ? $assoc_args['source'] : 'kit';
		$preserve_ids = isset( $assoc_args['preserve-ids'] );

		$payload = edtm_build_export_payload( $source, $preserve_ids );
		$json    = wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );

		if ( ! empty( $assoc_args['file'] ) ) {
			$path = $assoc_args['file'];
			if ( false === file_put_contents( $path, $json ) ) {
				WP_CLI::error( 'Failed to write file: ' . $path );
			}
			WP_CLI::success( 'Exported tokens to ' . $path );
		} else {
			WP_CLI::line( $json );
		}
	}

	/**
	 * Import tokens from JSON.
	 *
	 * ## OPTIONS
	 * <file>
	 * : Path to JSON file.
	 *
	 * [--mode=<mode>]
	 * : replace or merge. Default: merge
	 *
	 * [--preserve-ids]
	 * : Keep IDs from JSON. Useful if migrating content that references global tokens.
	 *
	 * ## EXAMPLES
	 *   wp edtm import ./tokens.json --mode=replace --preserve-ids
	 *   wp edtm import ./tokens.json --mode=merge
	 */
	public function import( $args, $assoc_args ) {
		list( $file ) = $args;

		if ( ! file_exists( $file ) ) {
			WP_CLI::error( 'File not found: ' . $file );
		}
		
		// Validate file size (2MB limit)
		$file_size = filesize( $file );
		if ( false === $file_size || $file_size > 2 * 1024 * 1024 ) {
			WP_CLI::error( 'File size exceeds 2MB limit.' );
		}
		
		$mode         = isset( $assoc_args['mode'] ) ? $assoc_args['mode'] : 'merge';
		$preserve_ids = isset( $assoc_args['preserve-ids'] );

		$content = file_get_contents( $file );
		$data    = json_decode( $content, true );
		if ( empty( $data ) || ! is_array( $data ) ) {
			WP_CLI::error( 'Invalid JSON payload.' );
		}
		
		// Validate JSON structure
		if ( ! isset( $data['colors'] ) && ! isset( $data['fonts'] ) ) {
			WP_CLI::error( 'Invalid JSON structure: missing colors or fonts data.' );
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

		// Persist plugin options for UI
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

		// Apply to Kit
		if ( 'replace' === $mode ) {
			$ok = edtm_import_apply_replace( $colors_norm, $fonts_norm );
			if ( $ok ) {
				WP_CLI::success( 'Imported tokens (replace).' );
			} else {
				WP_CLI::error( 'Import failed (replace).' );
			}
		} else {
			$status = edtm_apply_kit_settings_from_normalized( $colors_norm, $fonts_norm );
			if ( 'success' === $status ) {
				WP_CLI::success( 'Imported tokens (merge).' );
			} else {
				WP_CLI::error( 'Import queued or failed to apply immediately.' );
			}
		}
	}

	/**
	 * Re-push plugin options to Elementor Site Settings (merge).
	 *
	 * ## EXAMPLES
	 *   wp edtm sync
	 */
	public function sync() {
		$colors = get_option( 'elementor_scheme_color', array() );
		$fonts  = get_option( 'elementor_scheme_typography', array() );

		$colors_norm = array();
		foreach ( (array) $colors as $title => $hex ) {
			$colors_norm[] = array( 'id' => '', 'title' => (string) $title, 'color' => (string) $hex );
		}
		$fonts_norm = array();
		foreach ( (array) $fonts as $title => $props ) {
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
		$status = edtm_apply_kit_settings_from_normalized( $colors_norm, $fonts_norm );
		if ( 'success' === $status ) {
			WP_CLI::success( 'Synced plugin options to Site Settings.' );
		} else {
			WP_CLI::error( 'Sync queued or failed to apply immediately.' );
		}
	}
}

WP_CLI::add_command( 'edtm', 'EDTM_CLI_Command' );