(function ($) {
	'use strict';

	const $settings = $('.lstwc-settings');

	if (!$settings.length) {
		return;
	}

	const $status = $('#lstwc-settings-status');
	const ICONS = {
		saving: 'dashicons-update spin',
		saved: 'dashicons-yes-alt',
		error: 'dashicons-warning',
	};
	let saveTimer = null;

	function setStatus(text, type) {
		const $icon = $status.find('.dashicons');
		$icon.removeClass('dashicons-update dashicons-yes-alt dashicons-warning spin').addClass(ICONS[type]);
		$status.find('.lstwc-status__text').text(text);
		$status.attr('data-type', type).addClass('lstwc-status--visible');

		clearTimeout($status.data('hideTimer'));
		if (type !== 'saving') {
			$status.data('hideTimer', setTimeout(function () {
				$status.removeClass('lstwc-status--visible');
			}, 2500));
		}
	}

	$settings.on('change', 'input[type="checkbox"]', function () {
		const data = {
			action: 'tweaks_for_woo_save',
			nonce: lstwcSettings.nonce,
		};

		$settings.find('input[type="checkbox"]').each(function () {
			data[this.name] = this.checked ? '1' : '0';
		});

		setStatus('Saving\u2026', 'saving');
		clearTimeout(saveTimer);
		saveTimer = setTimeout(function () {
			$.post(lstwcSettings.ajaxUrl, data, function (response) {
				if (response && response.success) {
					// Clear any unsaved-changes warning left by older WooCommerce versions.
					window.onbeforeunload = '';
					setStatus('Changes saved', 'saved');
				} else {
					setStatus('Save failed \u2014 please retry', 'error');
				}
			}).fail(function () {
				setStatus('Save failed \u2014 please retry', 'error');
			});
		}, 400);
	});
})(jQuery);
