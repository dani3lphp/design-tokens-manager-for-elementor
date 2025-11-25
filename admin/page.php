<?php
/**
 * Admin settings page: Design Tokens (compact UI + view switcher + tabs + bulk delete).
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Determine active section order: GET param -> POST form value -> per-user preference -> default
$edtm_current_section = '';

/**
 * Section detection from GET/POST is safe without nonce verification because:
 * 1. This code only affects which panel is visible initially
 * 2. All actions (save/import/export/etc) verify nonces separately
 * 3. The section value is validated against a whitelist (colors|fonts)
 * 4. User preference updates use nonce-verified AJAX
 */
$edtm_raw_section = '';
// phpcs:disable WordPress.Security.NonceVerification
if ( isset( $_GET['edtm_section'] ) ) {
    $edtm_raw_section = sanitize_text_field( wp_unslash( $_GET['edtm_section'] ) );
} elseif ( isset( $_POST['edtm_current_section'] ) ) {
    $edtm_raw_section = sanitize_text_field( wp_unslash( $_POST['edtm_current_section'] ) );
}
// phpcs:enable

if ( in_array( $edtm_raw_section, array( 'colors', 'fonts' ), true ) ) {
    $edtm_current_section = $edtm_raw_section;
} else {
    // Try user preference if GET/POST wasn't valid
    $edtm_user_id = get_current_user_id();
    if ( $edtm_user_id ) {
        $edtm_pref = get_user_meta( $edtm_user_id, 'edtm_last_active_section', true );
        if ( in_array( $edtm_pref, array( 'colors', 'fonts' ), true ) ) {
            $edtm_current_section = $edtm_pref;
        }
    }
    // Final fallback to fonts if nothing else was valid
    if ( ! in_array( $edtm_current_section, array( 'colors', 'fonts' ), true ) ) {
        $edtm_current_section = 'fonts';
    }
}

$edtm_color_options = get_option( 'elementor_scheme_color', array() );
if ( ! is_array( $edtm_color_options ) ) { $edtm_color_options = array(); }

$edtm_font_options = get_option( 'elementor_scheme_typography', array() );
if ( ! is_array( $edtm_font_options ) ) { $edtm_font_options = array(); }

// Pending snapshot so UI reflects deletes immediately (if queued)
$edtm_pending      = get_option( 'edtm_pending_kit_sync' );
$edtm_pending_cols = ( is_array( $edtm_pending ) && isset( $edtm_pending['colors_norm'] ) && is_array( $edtm_pending['colors_norm'] ) ) ? $edtm_pending['colors_norm'] : array();
$edtm_pending_fnts = ( is_array( $edtm_pending ) && isset( $edtm_pending['fonts_norm'] ) && is_array( $edtm_pending['fonts_norm'] ) ) ? $edtm_pending['fonts_norm'] : array();

function edtm_page_get_active_kit() {
	if ( function_exists( 'edtm_get_active_kit' ) ) { return edtm_get_active_kit(); }
	return false;
}
function edtm_page_get_kit_meta() {
	if ( function_exists( 'edtm_get_active_kit_id' ) && function_exists( 'edtm_get_kit_settings_meta' ) ) {
		$kit_id = edtm_get_active_kit_id();
		return edtm_get_kit_settings_meta( $kit_id );
	}
	return array();
}
/**
 * Extract size string from an item (prefer custom clamp).
 */
function edtm_get_size_string_from_item( $item, $prefix ) {
	if ( ! is_array( $item ) ) { return ''; }
	// Any *_font_size_custom key first (covers clamp display)
	foreach ( array_keys( $item ) as $k ) {
		if ( preg_match( '/_font_size_custom$/', $k ) && is_string( $item[ $k ] ) && '' !== trim( $item[ $k ] ) ) {
			return trim( $item[ $k ] );
		}
	}
	// Fallback: if unit=custom and size is string, use it
	if ( isset( $item[ $prefix . '_font_size' ] ) && is_array( $item[ $prefix . '_font_size' ] ) ) {
		$dim = $item[ $prefix . '_font_size' ];
		if ( isset( $dim['unit'] ) && 'custom' === $dim['unit'] && isset( $dim['size'] ) && is_string( $dim['size'] ) && '' !== trim( $dim['size'] ) ) {
			return trim( $dim['size'] );
		}
		if ( function_exists( 'edtm_dimension_to_string' ) ) {
			$str = edtm_dimension_to_string( $dim ); if ( $str ) return $str;
		}
	}
	if ( isset( $item['typography_font_size' ] ) && is_array( $item['typography_font_size' ] ) ) {
		$dim = $item['typography_font_size' ];
		if ( isset( $dim['unit'] ) && 'custom' === $dim['unit'] && isset( $dim['size'] ) && is_string( $dim['size'] ) && '' !== trim( $dim['size'] ) ) {
			return trim( $dim['size'] );
		}
		if ( function_exists( 'edtm_dimension_to_string' ) ) {
			$str = edtm_dimension_to_string( $dim ); if ( $str ) return $str;
		}
	}
	return '';
}

$edtm_kit         = edtm_page_get_active_kit();
$edtm_kit_colors  = array();
$edtm_kit_fonts   = array();
$edtm_typo_prefix = 'typography_typo';

if ( $edtm_kit && method_exists( $edtm_kit, 'get_settings' ) ) {
	try { $edtm_kit_colors = $edtm_kit->get_settings( 'custom_colors' ); } catch ( \Throwable $e ) {}
	try { $edtm_kit_fonts  = $edtm_kit->get_settings( 'custom_typography' ); } catch ( \Throwable $e ) {}
} else {
	$edtm_meta = edtm_page_get_kit_meta();
	$edtm_kit_colors = isset( $edtm_meta['custom_colors'] ) && is_array( $edtm_meta['custom_colors'] ) ? $edtm_meta['custom_colors'] : array();
	$edtm_kit_fonts  = isset( $edtm_meta['custom_typography'] ) && is_array( $edtm_meta['custom_typography'] ) ? $edtm_meta['custom_typography'] : array();
}
if ( function_exists( 'edtm_detect_typo_prefix' ) ) {
	$edtm_typo_prefix = edtm_detect_typo_prefix( $edtm_kit_fonts );
}

// Build rows from Kit overlaid with plugin options, unless pending snapshot exists
if ( ! empty( $edtm_pending_cols ) || ! empty( $edtm_pending_fnts ) ) {
	$edtm_rows_colors = array();
	foreach ( $edtm_pending_cols as $edtm_c ) {
		$edtm_rows_colors[] = array(
			'id'    => isset( $edtm_c['id'] ) ? $edtm_c['id'] : '',
			'title' => isset( $edtm_c['title'] ) ? $edtm_c['title'] : '',
			'color' => isset( $edtm_c['color'] ) ? $edtm_c['color'] : '',
		);
	}
	$edtm_rows_fonts = array();
	foreach ( $edtm_pending_fnts as $edtm_f ) {
		$edtm_rows_fonts[] = array(
			'id'          => isset( $edtm_f['id'] ) ? $edtm_f['id'] : '',
			'title'       => isset( $edtm_f['title'] ) ? $edtm_f['title'] : '',
			'family'      => isset( $edtm_f['family'] ) ? $edtm_f['family'] : '',
			'size'        => isset( $edtm_f['size'] ) ? $edtm_f['size'] : '',
			'weight'      => isset( $edtm_f['weight'] ) ? $edtm_f['weight'] : '',
			'line_height' => isset( $edtm_f['line_height'] ) ? $edtm_f['line_height'] : '',
		);
	}
} else {
	$edtm_rows_colors = array();
	foreach ( (array) $edtm_kit_colors as $edtm_item ) {
		if ( ! is_array( $edtm_item ) ) continue;
		$edtm_id    = isset( $edtm_item['_id'] ) ? $edtm_item['_id'] : '';
		$edtm_title = isset( $edtm_item['title'] ) ? $edtm_item['title'] : '';
		$edtm_hex   = isset( $edtm_item['color'] ) ? $edtm_item['color'] : '';
		if ( '' === $edtm_title ) continue;
		$edtm_rows_colors[ strtolower( $edtm_title ) ] = array( 'id' => $edtm_id, 'title' => $edtm_title, 'color' => $edtm_hex );
	}
	foreach ( (array) $edtm_color_options as $edtm_title => $edtm_hex ) {
		$edtm_key = strtolower( $edtm_title );
		if ( isset( $edtm_rows_colors[ $edtm_key ] ) ) {
			$edtm_rows_colors[ $edtm_key ]['color'] = $edtm_hex;
		} else {
			$edtm_rows_colors[ $edtm_key ] = array( 'id' => '', 'title' => $edtm_title, 'color' => $edtm_hex );
		}
	}
	$edtm_rows_colors = array_values( $edtm_rows_colors );

	$edtm_rows_fonts = array();
	foreach ( (array) $edtm_kit_fonts as $edtm_item ) {
		if ( ! is_array( $edtm_item ) ) continue;
		$edtm_id    = isset( $edtm_item['_id'] ) ? $edtm_item['_id'] : '';
		$edtm_title = isset( $edtm_item['title'] ) ? $edtm_item['title'] : '';
		if ( '' === $edtm_title ) continue;

		$edtm_family = isset( $edtm_item[ $edtm_typo_prefix . '_font_family' ] ) ? $edtm_item[ $edtm_typo_prefix . '_font_family' ] : ( isset( $edtm_item['typography_font_family'] ) ? $edtm_item['typography_font_family'] : '' );
		$edtm_size_s = edtm_get_size_string_from_item( $edtm_item, $edtm_typo_prefix );
		$edtm_weight = isset( $edtm_item[ $edtm_typo_prefix . '_font_weight' ] ) ? $edtm_item[ $edtm_typo_prefix . '_font_weight' ] : ( isset( $edtm_item['typography_font_weight'] ) ? $edtm_item['typography_font_weight'] : '' );
		$edtm_lh_s   = isset( $edtm_item[ $edtm_typo_prefix . '_line_height' ] ) && is_array( $edtm_item[ $edtm_typo_prefix . '_line_height' ] ) && function_exists( 'edtm_dimension_to_string' )
			? edtm_dimension_to_string( $edtm_item[ $edtm_typo_prefix . '_line_height' ] )
			: ( isset( $edtm_item['typography_line_height'] ) && is_array( $edtm_item['typography_line_height'] ) && function_exists( 'edtm_dimension_to_string' )
				? edtm_dimension_to_string( $edtm_item['typography_line_height'] )
				: '' );

		$edtm_rows_fonts[ strtolower( $edtm_title ) ] = array(
			'id'          => $edtm_id,
			'title'       => $edtm_title,
			'family'      => $edtm_family,
			'size'        => $edtm_size_s,
			'weight'      => $edtm_weight,
			'line_height' => $edtm_lh_s,
		);
	}
	foreach ( (array) $edtm_font_options as $edtm_title => $edtm_props ) {
		$edtm_key   = strtolower( $edtm_title );
		$edtm_props = is_array( $edtm_props ) ? $edtm_props : array();
		if ( isset( $edtm_rows_fonts[ $edtm_key ] ) ) {
			if ( isset( $edtm_props['family'] ) && '' !== $edtm_props['family'] )         $edtm_rows_fonts[ $edtm_key ]['family']      = $edtm_props['family'];
			if ( isset( $edtm_props['size'] ) && '' !== $edtm_props['size'] )             $edtm_rows_fonts[ $edtm_key ]['size']        = $edtm_props['size'];
			if ( isset( $edtm_props['weight'] ) && '' !== $edtm_props['weight'] )         $edtm_rows_fonts[ $edtm_key ]['weight']      = $edtm_props['weight'];
			if ( isset( $edtm_props['line_height'] ) && '' !== $edtm_props['line_height'] ) $edtm_rows_fonts[ $edtm_key ]['line_height'] = $edtm_props['line_height'];
		} else {
			$edtm_rows_fonts[ $edtm_key ] = array(
				'id'          => '',
				'title'       => $edtm_title,
				'family'      => isset( $edtm_props['family'] ) ? $edtm_props['family'] : '',
				'size'        => isset( $edtm_props['size'] ) ? $edtm_props['size'] : '',
				'weight'      => isset( $edtm_props['weight'] ) ? $edtm_props['weight'] : '',
				'line_height' => isset( $edtm_props['line_height'] ) ? $edtm_props['line_height'] : '',
			);
		}
	}
	$edtm_rows_fonts = array_values( $edtm_rows_fonts );
}

$edtm_colors_count = count( $edtm_rows_colors );
$edtm_fonts_count  = count( $edtm_rows_fonts );
?>
<div class="wrap edtm-wrap">
	<!-- Inline CSS to prevent flash of wrong section -->
	<style>
		/* Hide sections by default, show active one */
		.edtm-panel[data-section] { display: none; }
		<?php if ($edtm_current_section === 'colors'): ?>
		.edtm-panel[data-section="colors"] { display: block; }
		<?php else: ?>
		.edtm-panel[data-section="fonts"] { display: block; }
		<?php endif; ?>
	</style>
	

	<h1 class="edtm-title"><?php esc_html_e( 'Design Tokens', 'design-tokens-manager-for-elementor' ); ?></h1>

	<!-- View switcher: section selector + tabs -->
	<div class="edtm-card edtm-modebar">
		<div class="edtm-modebar-left">
			<label for="edtm-section-switcher" class="edtm-modebar-label"><?php esc_html_e( 'Section', 'design-tokens-manager-for-elementor' ); ?></label>
			<select id="edtm-section-switcher">
				<option value="fonts" <?php selected( $edtm_current_section, 'fonts' ); ?>><?php esc_html_e( 'Global Fonts', 'design-tokens-manager-for-elementor' ); ?></option>
				<option value="colors" <?php selected( $edtm_current_section, 'colors' ); ?>><?php esc_html_e( 'Global Colors', 'design-tokens-manager-for-elementor' ); ?></option>
			</select>
		</div>
		<div class="edtm-tabs" role="tablist" aria-label="<?php esc_attr_e( 'View', 'design-tokens-manager-for-elementor' ); ?>">
			<button type="button" class="edtm-tab is-active" data-tab="manage" role="tab" aria-selected="true"><?php esc_html_e( 'Manage', 'design-tokens-manager-for-elementor' ); ?></button>
			<button type="button" class="edtm-tab" data-tab="import" role="tab" aria-selected="false"><?php esc_html_e( 'Import / Export', 'design-tokens-manager-for-elementor' ); ?></button>
		</div>
	</div>

	<!-- Two-way Sync Toolbar (visible only in Manage) -->
	<div class="edtm-card edtm-toolbar" id="edtm-toolbar-card">
		<div class="edtm-toolbar-left">
			<h2 class="edtm-card-title"><?php esc_html_e( 'Two-way sync', 'design-tokens-manager-for-elementor' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Pull from Elementor Site Settings to refresh this UI, or Push to overwrite Site Settings with the tokens in this UI.', 'design-tokens-manager-for-elementor' ); ?></p>
		</div>
		<div class="edtm-toolbar-right">
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="edtm-inline-form">
				<?php wp_nonce_field( 'edtm_sync_tools', 'edtm_sync_nonce' ); ?>
				<input type="hidden" name="action" value="edtm_pull_from_kit" />
				<input type="hidden" name="edtm_current_section" value="<?php echo esc_attr( $edtm_current_section ); ?>" />
				<button type="submit" class="button"><?php esc_html_e( 'Pull from Site Settings', 'design-tokens-manager-for-elementor' ); ?></button>
			</form>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="edtm-inline-form">
				<?php wp_nonce_field( 'edtm_sync_tools', 'edtm_sync_nonce' ); ?>
				<input type="hidden" name="action" value="edtm_push_to_kit" />
				<input type="hidden" name="edtm_current_section" value="<?php echo esc_attr( $edtm_current_section ); ?>" />
				<button type="submit" class="button button-primary edtm-confirm-push"><?php esc_html_e( 'Push to Site Settings (overwrite)', 'design-tokens-manager-for-elementor' ); ?></button>
			</form>
		</div>
	</div>

	<!-- Manage form (both panels inside; we hide the inactive panel with JS) -->
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="edtm-card" id="edtm-form-card">
		<?php wp_nonce_field( 'edtm_save_tokens', 'edtm_nonce' ); ?>
		<input type="hidden" name="action" value="edtm_save_tokens" />
		<input type="hidden" name="edtm_current_section" value="<?php echo esc_attr( $edtm_current_section ); ?>" />

		<!-- Fonts panel -->
		<div class="edtm-section edtm-panel" id="edtm-fonts-section" data-section="fonts">
			<h2 class="edtm-card-title"><?php esc_html_e( 'Global Fonts', 'design-tokens-manager-for-elementor' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Bulk format: Name: Family, Size, Weight, Line-height. Size accepts clamp(...) or numeric (e.g., 1 rem).', 'design-tokens-manager-for-elementor' ); ?></p>

			<div class="edtm-bulk-box">
				<label for="edtm_fonts_bulk" class="edtm-bulk-label"><?php esc_html_e( 'Bulk paste fonts', 'design-tokens-manager-for-elementor' ); ?></label>
				<textarea id="edtm_fonts_bulk" name="edtm_fonts_bulk" class="large-text code" placeholder="Heading: Inter, system-ui, sans-serif, clamp(3rem, 2.5385rem + 2.0513vw, 4rem), 700, 1.2em&#10;Body: Roboto, 1 rem, 400, 1.5em"></textarea>
			</div>

			<div class="edtm-bulk-actions">
				<label class="screen-reader-text" for="edtm-fonts-action"><?php esc_html_e( 'Bulk actions', 'design-tokens-manager-for-elementor' ); ?></label>
				<select id="edtm-fonts-action" class="edtm-bulk-select" data-table="#edtm-fonts-wrap">
					<option value=""><?php esc_html_e( 'Bulk actions', 'design-tokens-manager-for-elementor' ); ?></option>
					<option value="delete"><?php esc_html_e( 'Delete', 'design-tokens-manager-for-elementor' ); ?></option>
				</select>
				<button type="button" class="button action edtm-bulk-apply" data-table="#edtm-fonts-wrap"><?php esc_html_e( 'Apply', 'design-tokens-manager-for-elementor' ); ?></button>
			</div>

			<table class="widefat striped edtm-table" id="edtm-fonts-wrap" data-next-index="<?php echo esc_attr( max( 0, $edtm_fonts_count ) ); ?>">
				<thead>
					<tr>
						<th class="check-column"><input type="checkbox" class="edtm-check-all" data-table="#edtm-fonts-wrap" /></th>
						<th style="width: 18%"><?php esc_html_e( 'Token name', 'design-tokens-manager-for-elementor' ); ?></th>
						<th style="width: 27%"><?php esc_html_e( 'Font family', 'design-tokens-manager-for-elementor' ); ?></th>
						<th style="width: 15%"><?php esc_html_e( 'Font size', 'design-tokens-manager-for-elementor' ); ?></th>
						<th style="width: 15%"><?php esc_html_e( 'Font weight', 'design-tokens-manager-for-elementor' ); ?></th>
						<th style="width: 15%"><?php esc_html_e( 'Line height (em)', 'design-tokens-manager-for-elementor' ); ?></th>
						<th style="width: 10%"><?php esc_html_e( 'Actions', 'design-tokens-manager-for-elementor' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( $edtm_fonts_count ) :
						foreach ( $edtm_rows_fonts as $edtm_j => $edtm_row ) : ?>
							<tr class="edtm-row" data-index="<?php echo esc_attr( $edtm_j ); ?>">
								<th class="check-column"><input type="checkbox" class="edtm-row-check" /></th>
								<td>
									<input type="hidden" name="edtm_fonts[<?php echo esc_attr( $edtm_j ); ?>][id]" value="<?php echo esc_attr( $edtm_row['id'] ); ?>" />
									<input type="text" name="edtm_fonts[<?php echo esc_attr( $edtm_j ); ?>][token]" value="<?php echo esc_attr( $edtm_row['title'] ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'Heading', 'design-tokens-manager-for-elementor' ); ?>" />
								</td>
								<td><input type="text" name="edtm_fonts[<?php echo esc_attr( $edtm_j ); ?>][family]" value="<?php echo esc_attr( $edtm_row['family'] ); ?>" class="regular-text" placeholder="Poppins, system-ui, sans-serif" /></td>
								<td><input type="text" name="edtm_fonts[<?php echo esc_attr( $edtm_j ); ?>][size]" value="<?php echo esc_attr( $edtm_row['size'] ); ?>" class="regular-text edtm-short" placeholder="clamp(3rem, 2.5rem + 2vw, 4rem) or 16px or 1 rem" /></td>
								<td><input type="number" name="edtm_fonts[<?php echo esc_attr( $edtm_j ); ?>][weight]" value="<?php echo esc_attr( $edtm_row['weight'] ); ?>" class="small-text" placeholder="400" min="100" max="1000" step="50" /></td>
								<td><input type="text" name="edtm_fonts[<?php echo esc_attr( $edtm_j ); ?>][line_height]" value="<?php echo esc_attr( $edtm_row['line_height'] ); ?>" class="regular-text edtm-short" placeholder="1.5em" /></td>
								<td><button type="button" class="button button-link-delete edtm-remove-row"><?php esc_html_e( 'Remove', 'design-tokens-manager-for-elementor' ); ?></button></td>
							</tr>
					<?php endforeach; else : ?>
						<tr class="edtm-row" data-index="0">
							<th class="check-column"><input type="checkbox" class="edtm-row-check" /></th>
							<td>
								<input type="hidden" name="edtm_fonts[0][id]" value="" />
								<input type="text" name="edtm_fonts[0][token]" value="" class="regular-text" placeholder="<?php esc_attr_e( 'Heading', 'design-tokens-manager-for-elementor' ); ?>" />
							</td>
							<td><input type="text" name="edtm_fonts[0][family]" value="" class="regular-text" placeholder="Poppins, system-ui, sans-serif" /></td>
							<td><input type="text" name="edtm_fonts[0][size]" value="" class="regular-text edtm-short" placeholder="clamp(3rem, 2.5rem + 2vw, 4rem) or 16px or 1 rem" /></td>
							<td><input type="number" name="edtm_fonts[0][weight]" value="" class="small-text" placeholder="400" min="100" max="1000" step="50" /></td>
							<td><input type="text" name="edtm_fonts[0][line_height]" value="" class="regular-text edtm-short" placeholder="1.5em" /></td>
							<td><button type="button" class="button button-link-delete edtm-remove-row"><?php esc_html_e( 'Remove', 'design-tokens-manager-for-elementor' ); ?></button></td>
						</tr>
					<?php endif; ?>
				</tbody>
				<tfoot>
					<tr>
						<th class="check-column"><input type="checkbox" class="edtm-check-all" data-table="#edtm-fonts-wrap" /></th>
						<td colspan="6"><button type="button" class="button button-secondary" id="edtm-add-font">+ <?php esc_html_e( 'Add Font', 'design-tokens-manager-for-elementor' ); ?></button></td>
					</tr>
				</tfoot>
			</table>
		</div>

		<!-- Colors panel -->
		<div class="edtm-section edtm-panel" id="edtm-colors-section" data-section="colors">
			<h2 class="edtm-card-title"><?php esc_html_e( 'Global Colors', 'design-tokens-manager-for-elementor' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Bulk paste with "Name: #hex", one per line, or manage row-by-row.', 'design-tokens-manager-for-elementor' ); ?></p>

			<div class="edtm-bulk-box">
				<label for="edtm_colors_bulk" class="edtm-bulk-label"><?php esc_html_e( 'Bulk paste colors', 'design-tokens-manager-for-elementor' ); ?></label>
				<textarea id="edtm_colors_bulk" name="edtm_colors_bulk" class="large-text code" placeholder="Primary: #ff0000&#10;Secondary: #00ff00&#10;Accent: #0000ff"></textarea>
			</div>

			<div class="edtm-bulk-actions">
				<label class="screen-reader-text" for="edtm-colors-action"><?php esc_html_e( 'Bulk actions', 'design-tokens-manager-for-elementor' ); ?></label>
				<select id="edtm-colors-action" class="edtm-bulk-select" data-table="#edtm-colors-wrap">
					<option value=""><?php esc_html_e( 'Bulk actions', 'design-tokens-manager-for-elementor' ); ?></option>
					<option value="delete"><?php esc_html_e( 'Delete', 'design-tokens-manager-for-elementor' ); ?></option>
				</select>
				<button type="button" class="button action edtm-bulk-apply" data-table="#edtm-colors-wrap"><?php esc_html_e( 'Apply', 'design-tokens-manager-for-elementor' ); ?></button>
			</div>

			<table class="widefat striped edtm-table" id="edtm-colors-wrap" data-next-index="<?php echo esc_attr( max( 0, $edtm_colors_count ) ); ?>">
				<thead>
					<tr>
						<th class="check-column"><input type="checkbox" class="edtm-check-all" data-table="#edtm-colors-wrap" /></th>
						<th style="width: 30%"><?php esc_html_e( 'Token name', 'design-tokens-manager-for-elementor' ); ?></th>
						<th style="width: 40%"><?php esc_html_e( 'Color code (hex)', 'design-tokens-manager-for-elementor' ); ?></th>
						<th style="width: 20%"><?php esc_html_e( 'Actions', 'design-tokens-manager-for-elementor' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( $edtm_colors_count ) :
						foreach ( $edtm_rows_colors as $edtm_i => $edtm_row ) : ?>
							<tr class="edtm-row" data-index="<?php echo esc_attr( $edtm_i ); ?>">
								<th class="check-column"><input type="checkbox" class="edtm-row-check" /></th>
								<td>
									<input type="hidden" name="edtm_colors[<?php echo esc_attr( $edtm_i ); ?>][id]" value="<?php echo esc_attr( $edtm_row['id'] ); ?>" />
									<input type="text" name="edtm_colors[<?php echo esc_attr( $edtm_i ); ?>][token]" value="<?php echo esc_attr( $edtm_row['title'] ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'Primary', 'design-tokens-manager-for-elementor' ); ?>" />
								</td>
								<td><input type="text" name="edtm_colors[<?php echo esc_attr( $edtm_i ); ?>][color]" value="<?php echo esc_attr( $edtm_row['color'] ); ?>" class="edtm-color" placeholder="#1e73be" /></td>
								<td><button type="button" class="button button-link-delete edtm-remove-row"><?php esc_html_e( 'Remove', 'design-tokens-manager-for-elementor' ); ?></button></td>
							</tr>
					<?php endforeach; else : ?>
						<tr class="edtm-row" data-index="0">
							<th class="check-column"><input type="checkbox" class="edtm-row-check" /></th>
							<td>
								<input type="hidden" name="edtm_colors[0][id]" value="" />
								<input type="text" name="edtm_colors[0][token]" value="" class="regular-text" placeholder="<?php esc_attr_e( 'Primary', 'design-tokens-manager-for-elementor' ); ?>" />
							</td>
							<td><input type="text" name="edtm_colors[0][color]" value="" class="edtm-color" placeholder="#1e73be" /></td>
							<td><button type="button" class="button button-link-delete edtm-remove-row"><?php esc_html_e( 'Remove', 'design-tokens-manager-for-elementor' ); ?></button></td>
						</tr>
					<?php endif; ?>
				</tbody>
				<tfoot>
					<tr>
						<th class="check-column"><input type="checkbox" class="edtm-check-all" data-table="#edtm-colors-wrap" /></th>
						<td colspan="3"><button type="button" class="button button-secondary" id="edtm-add-color">+ <?php esc_html_e( 'Add Color', 'design-tokens-manager-for-elementor' ); ?></button></td>
					</tr>
				</tfoot>
			</table>
		</div>

		<?php submit_button( __( 'Save Tokens', 'design-tokens-manager-for-elementor' ) ); ?>
	</form>

	<!-- Import/Export panel (hidden when Manage) -->
	<div class="edtm-card edtm-panel" id="edtm-import-card" data-section="import">
		<h2 class="edtm-card-title"><?php esc_html_e( 'Import / Export', 'design-tokens-manager-for-elementor' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Export your Global Colors/Fonts to JSON or import them from a JSON file. Use Preserve IDs for migrations where content already references these global tokens.', 'design-tokens-manager-for-elementor' ); ?></p>

		<div class="edtm-grid">
			<div>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="edtm-export-form">
					<?php wp_nonce_field( 'edtm_import_export', 'edtm_ie_nonce' ); ?>
					<input type="hidden" name="action" value="edtm_export_tokens" />
					<input type="hidden" name="edtm_current_section" value="<?php echo esc_attr( $edtm_current_section ); ?>" />
					<p>
						<label for="edtm_export_source"><strong><?php esc_html_e( 'Source', 'design-tokens-manager-for-elementor' ); ?></strong></label>
						<select id="edtm_export_source" name="edtm_export_source">
							<option value="kit"><?php esc_html_e( 'Site Kit (Elementor Site Settings)', 'design-tokens-manager-for-elementor' ); ?></option>
							<option value="plugin"><?php esc_html_e( 'Plugin Options (this UI)', 'design-tokens-manager-for-elementor' ); ?></option>
						</select>
					</p>
					<p>
						<label><input type="checkbox" name="edtm_export_preserve_ids" value="1" checked="checked" /> <?php esc_html_e( 'Preserve IDs (keep token IDs for reference integrity)', 'design-tokens-manager-for-elementor' ); ?></label>
					</p>
					<?php submit_button( __( 'Download JSON', 'design-tokens-manager-for-elementor' ), 'secondary', '', false ); ?>
				</form>
			</div>
			<div>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data" class="edtm-import-form">
					<?php wp_nonce_field( 'edtm_import_export', 'edtm_ie_nonce' ); ?>
					<input type="hidden" name="action" value="edtm_import_tokens" />
					<input type="hidden" name="edtm_current_section" value="<?php echo esc_attr( $edtm_current_section ); ?>" />
					<p>
						<label for="edtm_import_file"><strong><?php esc_html_e( 'Import JSON file', 'design-tokens-manager-for-elementor' ); ?></strong></label><br />
						<input type="file" id="edtm_import_file" name="edtm_import_file" accept="application/json,.json" required />
					</p>
					<p>
						<strong><?php esc_html_e( 'Import mode', 'design-tokens-manager-for-elementor' ); ?></strong><br />
						<label><input type="radio" name="edtm_import_mode" value="merge" checked="checked" /> <?php esc_html_e( 'Merge (add/update by name; keep existing)', 'design-tokens-manager-for-elementor' ); ?></label><br />
						<label><input type="radio" name="edtm_import_mode" value="replace" /> <?php esc_html_e( 'Replace (overwrite Site Settings; delete anything not in file)', 'design-tokens-manager-for-elementor' ); ?></label>
					</p>
					<p><label><input type="checkbox" name="edtm_import_preserve_ids" value="1" /> <?php esc_html_e( 'Preserve IDs (keep IDs from file to maintain references)', 'design-tokens-manager-for-elementor' ); ?></label></p>
					<?php submit_button( __( 'Import JSON', 'design-tokens-manager-for-elementor' ), 'primary', '', false ); ?>
				</form>
			</div>
		</div>
	</div>

	<!-- Row templates for Add Color / Add Font -->
	<script type="text/template" id="edtm-color-row-template">
		<tr class="edtm-row" data-index="__INDEX__">
			<th class="check-column"><input type="checkbox" class="edtm-row-check" /></th>
			<td>
				<input type="hidden" name="edtm_colors[__INDEX__][id]" value="" />
				<input type="text" name="edtm_colors[__INDEX__][token]" value="" class="regular-text" placeholder="<?php echo esc_attr__( 'Primary', 'design-tokens-manager-for-elementor' ); ?>" />
			</td>
			<td>
				<input type="text" name="edtm_colors[__INDEX__][color]" value="" class="edtm-color" placeholder="#1e73be" />
			</td>
			<td>
				<button type="button" class="button button-link-delete edtm-remove-row"><?php echo esc_html__( 'Remove', 'design-tokens-manager-for-elementor' ); ?></button>
			</td>
		</tr>
	</script>

	<script type="text/template" id="edtm-font-row-template">
		<tr class="edtm-row" data-index="__INDEX__">
			<th class="check-column"><input type="checkbox" class="edtm-row-check" /></th>
			<td>
				<input type="hidden" name="edtm_fonts[__INDEX__][id]" value="" />
				<input type="text" name="edtm_fonts[__INDEX__][token]" value="" class="regular-text" placeholder="<?php echo esc_attr__( 'Heading', 'design-tokens-manager-for-elementor' ); ?>" />
			</td>
			<td>
				<input type="text" name="edtm_fonts[__INDEX__][family]" value="" class="regular-text" placeholder="Poppins, system-ui, sans-serif" />
			</td>
			<td>
				<input type="text" name="edtm_fonts[__INDEX__][size]" value="" class="regular-text edtm-short" placeholder="clamp(3rem, 2.5rem + 2vw, 4rem) or 16px or 1 rem" />
			</td>
			<td>
				<input type="number" name="edtm_fonts[__INDEX__][weight]" value="" class="small-text" placeholder="400" min="100" max="1000" step="50" />
			</td>
			<td>
				<input type="text" name="edtm_fonts[__INDEX__][line_height]" value="" class="regular-text edtm-short" placeholder="1.5em" />
			</td>
			<td>
				<button type="button" class="button button-link-delete edtm-remove-row"><?php echo esc_html__( 'Remove', 'design-tokens-manager-for-elementor' ); ?></button>
			</td>
		</tr>
	</script>
</div>