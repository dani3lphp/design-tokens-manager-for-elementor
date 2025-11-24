(function($) {
	'use strict';

	/**
	 * Initialize WordPress color pickers.
	 */
	function initColorPickers(context) {
		$(context).find('.edtm-color').each(function() {
			if (!$(this).hasClass('wp-color-picker')) {
				$(this).wpColorPicker();
			}
		});
	}

	/**
	 * Get next index for a table.
	 */
	function getNextIndex($table) {
		var idx = parseInt($table.attr('data-next-index'), 10);
		return isNaN(idx) ? 0 : idx;
	}

	/**
	 * Increment and update next index.
	 */
	function incrementIndex($table) {
		var next = getNextIndex($table) + 1;
		$table.attr('data-next-index', next);
		return next;
	}

	/**
	 * Get current section and view from URL.
	 */
	function getCurrentState() {
		var params = new URLSearchParams(window.location.search);
		var section = params.get('edtm_section');
		// If the URL doesn't provide a section, prefer the server-rendered selector value
		if ( ! section ) {
			var $sel = $( '#edtm-section-switcher' );
			if ( $sel.length ) {
				section = $sel.val();
			}
		}
		return {
			section: section || 'fonts',
			view: params.get('edtm_view') || 'manage'
		};
	}

	/**
	 * Update URL without reload.
	 */
	function updateURL(section, view) {
		var params = new URLSearchParams(window.location.search);
		params.set('edtm_section', section);
		params.set('edtm_view', view);
		var newURL = window.location.pathname + '?' + params.toString();
		window.history.replaceState({}, '', newURL);
	}

	/**
	 * Update visible panels based on section and view.
	 */
	function updateView() {
		var state = getCurrentState();
		var section = state.section;
		var view = state.view;

		// Update section dropdown
		$('#edtm-section-switcher').val(section);

		// Update tab states
		$('.edtm-tab').removeClass('is-active').attr('aria-selected', 'false');
		$('.edtm-tab[data-tab="' + view + '"]').addClass('is-active').attr('aria-selected', 'true');

		// Show/hide based on view
		if (view === 'manage') {
			$('#edtm-toolbar-card').show();
			$('#edtm-form-card').show();
			$('#edtm-import-card').hide();

			// Show correct section within manage
			if (section === 'fonts') {
				$('#edtm-fonts-section').show();
				$('#edtm-colors-section').hide();
			} else {
				$('#edtm-fonts-section').hide();
				$('#edtm-colors-section').show();
			}
		} else if (view === 'import') {
			$('#edtm-toolbar-card').hide();
			$('#edtm-form-card').hide();
			$('#edtm-import-card').show();
		}

		// Re-init color pickers for visible section
		initColorPickers(document);
	}

	/**
	 * Inject current section into form before submit.
	 */
	function injectCurrentSection($form) {
		var state = getCurrentState();
		// Ensure hidden input exists and has the correct value
		var $input = $form.find('input[name="edtm_current_section"]');
		if ($input.length === 0) {
			$form.append('<input type="hidden" name="edtm_current_section" value="' + state.section + '" />');
		} else {
			$input.val(state.section);
		}
	}

	/**
	 * Ensure all forms have the hidden section input set to section.
	 *
	 * @param {string} section - The section name (colors|fonts).
	 */
	function updateFormsSection(section) {
		$('form').each(function() {
			var $f = $(this);
			var $i = $f.find('input[name="edtm_current_section"]');
			if ($i.length === 0) {
				$f.append('<input type="hidden" name="edtm_current_section" value="' + section + '" />');
			} else {
				$i.val(section);
			}
		});
	}

	$(document).ready(function() {
		// Initialize view on page load
		var state = getCurrentState();
		var _params = new URLSearchParams(window.location.search);
		// If the URL lacks our state params, initialize them from the server-rendered selector
		if ( !_params.has('edtm_section') || !_params.has('edtm_view') ) {
			updateURL(state.section, state.view);
		}

		// Make sure hidden inputs reflect the current section immediately
		updateFormsSection(state.section);
		updateView();

		// Tab switching
		$(document).on('click', '.edtm-tab', function(e) {
			e.preventDefault();
			var view = $(this).data('tab');
			var state = getCurrentState();
			updateURL(state.section, view);
			updateView();
		});

		// Section dropdown
		$(document).on('change', '#edtm-section-switcher', function() {
			var section = $(this).val();
			var state = getCurrentState();
			updateURL(section, state.view);
			updateView();

			// Keep all forms' hidden inputs in sync immediately
			updateFormsSection(section);

			// Persist user's preference via AJAX (if localized data available)
			if (typeof EDTM_ADMIN !== 'undefined' && EDTM_ADMIN.ajax_url) {
				$.post(EDTM_ADMIN.ajax_url, {
					action: 'edtm_update_section',
					section: section,
					nonce: EDTM_ADMIN.edtm_section_nonce
				}).always(function() {
					// no-op: we don't need to wait for response to continue UX
				});
			}
		});

		// Add color row
		$(document).on('click', '#edtm-add-color', function(e) {
			e.preventDefault();
			var $table = $('#edtm-colors-wrap');
			var template = $('#edtm-color-row-template').html();
			var idx = getNextIndex($table);
			var row = template.replace(/__INDEX__/g, idx);
			$table.find('tbody').append(row);
			incrementIndex($table);
			initColorPickers($table.find('tbody tr:last'));
		});

		// Add font row
		$(document).on('click', '#edtm-add-font', function(e) {
			e.preventDefault();
			var $table = $('#edtm-fonts-wrap');
			var template = $('#edtm-font-row-template').html();
			var idx = getNextIndex($table);
			var row = template.replace(/__INDEX__/g, idx);
			$table.find('tbody').append(row);
			incrementIndex($table);
		});

		// Remove row
		$(document).on('click', '.edtm-remove-row', function(e) {
			e.preventDefault();
			var msg = (typeof EDTM_I18N !== 'undefined' && EDTM_I18N.removeConfirm) 
				? EDTM_I18N.removeConfirm 
				: 'Remove this row?';
			if (confirm(msg)) {
				$(this).closest('tr').remove();
			}
		});

		// Check all
		$(document).on('change', '.edtm-check-all', function() {
			var $table = $($(this).data('table'));
			var checked = $(this).prop('checked');
			$table.find('.edtm-row-check').prop('checked', checked);
		});

		// Individual checkbox sync
		$(document).on('change', '.edtm-row-check', function() {
			var $table = $(this).closest('table');
			var total = $table.find('.edtm-row-check').length;
			var checked = $table.find('.edtm-row-check:checked').length;
			$table.find('.edtm-check-all').prop('checked', total === checked);
		});

		// Bulk delete
		$(document).on('click', '.edtm-bulk-apply', function(e) {
			e.preventDefault();
			var $table = $($(this).data('table'));
			var tableId = $(this).data('table');
			var $select = $table.closest('.edtm-section').find('.edtm-bulk-select[data-table="' + tableId + '"]');
			var action = $select.val();

			if (!action) return;

			if (action === 'delete') {
				var $checked = $table.find('.edtm-row-check:checked');
				if ($checked.length === 0) {
					var noItemsMsg = (typeof EDTM_I18N !== 'undefined' && EDTM_I18N.noItemsSelected)
						? EDTM_I18N.noItemsSelected
						: 'No items selected.';
					alert(noItemsMsg);
					return;
				}
				var deleteMsg = (typeof EDTM_I18N !== 'undefined' && EDTM_I18N.deleteConfirm)
					? EDTM_I18N.deleteConfirm.replace('%d', $checked.length)
					: 'Delete ' + $checked.length + ' selected item(s)?';
				if (confirm(deleteMsg)) {
					$checked.closest('tr').remove();
				}
			}

			$select.val('');
		});

		// Push confirmation
		$(document).on('submit', 'form', function(e) {
			if ($(this).find('.edtm-confirm-push').length) {
				var msg = (typeof EDTM_I18N !== 'undefined' && EDTM_I18N.pushConfirm) 
					? EDTM_I18N.pushConfirm 
					: 'This will overwrite Elementor Site Settings. Continue?';
				if (!confirm(msg)) {
					e.preventDefault();
					return false;
				}
			}
		});

		// Inject current section before form submit (save/pull/push)
		$(document).on('submit', '#edtm-form-card', function() {
			injectCurrentSection($(this));
		});

		// Ensure pull/push forms get the section injected as well
		$(document).on('submit', 'form:has(input[name="action"][value="edtm_pull_from_kit"]), form:has(input[name="action"][value="edtm_push_to_kit"])', function() {
			injectCurrentSection($(this));
		});

		// Initialize color pickers
		initColorPickers(document);
	});

})(jQuery);