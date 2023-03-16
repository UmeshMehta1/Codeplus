/**
 * Plugins interface
 * @author Webcraftic <wordpress.webraftic@gmail.com>
 * @copyright (c) 10.09.2017, Webcraftic
 * @version 1.0
 */

jQuery(function($) {

	window.um_add_plugin_icons = function(info) {
		$('#the-list tr[data-plugin]').each(function(k, v) {
			var title = window.wbcr_upm_plugins_label_l10n['default'];
			var plugin_path = $(v).data('plugin'),
				slug_parts = plugin_path.split('/'),
				slug = slug_parts[0],
				update_class = '',
				is_auto_update = false,
				is_update_disabled = false;

			if( (info['filters']['disable_auto_updates'] === undefined || !info['filters']['disable_auto_updates'][slug]) ) {
				is_auto_update = true;
			}
			if( (info['filters']['disable_updates'] !== undefined && info['filters']['disable_updates'][slug]) ) {
				is_update_disabled = true;
			}

			if( is_auto_update ) {
				update_class = 'wbcr-upm-purple';
				title = window.wbcr_upm_plugins_label_l10n['auto_update'];
			}
			if( is_update_disabled ) {
				update_class = 'wbcr-upm-red';
				title = window.wbcr_upm_plugins_label_l10n['disable_updates'];
			}

			$(v).find('.check-column').addClass('hide-placeholder').append('<span class="dashicons dashicons-update wbcr-upm-plugin-status ' + update_class + '" title="' + title + '"></span>');
		});
	};
});