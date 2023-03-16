<?php

defined('ABSPATH') || die('Cheatin’ uh?');

/**
 * @var array $data
 * @var WGZ_Views $this
 */

$plugin_name = $data['name'];
?>
<div class="wam-plugin-settings">
	<div class="wam-plugin-settings__controls">
		<select class="wam-select<?php echo $data['select_control_classes']; ?> js-wam-select-plugin-load-mode" data-plugin-name="<?php echo esc_attr($plugin_name) ?>">
			<option value="enable"<?php selected('enable', $data['load_mode']) ?>>
				<?php _e("Load plugin and its assets", 'gonzales') ?>
			</option>
			<option value="disable_assets"<?php selected('disable_assets', $data['load_mode']) ?>>
				<?php _e("Don't load plugin assets", 'gonzales') ?>
			</option>
			<option value="disable_plugin"<?php selected('disable_plugin', $data['load_mode']) ?>>
				<?php _e("Don't load plugin", 'gonzales') ?>
			</option>
		</select>
		<button class="wam-button wam-button--default wam-button__icon js-wam-button__icon--cogs js-wam-open-plugin-settings<?php echo esc_attr($data['settings_button_classes']) ?>"></button>
	</div>
	<div class="js-wam-plugin-settings__conditions">
		<input type="hidden" data-plugin-name="<?php echo esc_attr($plugin_name) ?>" class="wam-conditions-builder__settings" value="<?php echo esc_attr($data['visability']) ?>">
	</div>
</div>
<div class="wam-plugin-assets wam-plugin-<?php echo esc_attr($plugin_name) ?>-assets">
	<h2><?php _e('Loaded resourses on current page', 'gonzales') ?>:</h2>
	<table class="wam-table wam-plugin-assets__table" style="margin:0;">
		<tr>
			<th class="wam-table__th-actions"><?php _e('Actions', 'gonzales') ?></th>
			<th class="wam-table__th-type"><?php _e('Type', 'gonzales') ?></th>
			<th class="wam-table__th-handle"><?php _e('Handle/Source', 'gonzales') ?></th>
			<th class="wam-table__th-version"><?php _e('Version', 'gonzales') ?></th>
			<th class="wam-table__th-size"><?php _e('Size', 'gonzales') ?></th>
		</tr>
		<?php if( !empty($data['assets']) ): ?>
			<?php foreach((array)$data['assets'] as $resource_type => $assets): ?>
				<?php foreach((array)$assets as $resource_handle => $item): ?>
					<tr data-size="<?php echo esc_attr($item['size']); ?>" data-resource-type="<?php echo esc_attr($resource_type) ?>" data-resource-handle="<?php echo esc_attr($resource_handle) ?>" data-asset-handle="<?php echo esc_attr($resource_handle . '-' . $resource_type); ?>" class="js-wam-asset js-wam-<?php echo esc_attr($resource_type); ?>-asset wam-table__asset-settings<?php echo $item['row_classes']; ?>" id="wam-table__loaded-resourse-<?php echo md5($resource_handle . $resource_type . $item['url_full']); ?>">
						<td class="wam-table__td-actions">
							<select class="wam-select<?php echo $item['select_control_classes']; ?> js-wam-select-asset-load-mode"<?php disabled('enable' !== $data['load_mode']) ?>>
								<option value="enable"<?php selected('enable', $item['load_mode']) ?>>
									<?php _e('Enable', 'gonzales') ?>
								</option>
								<option value="disable"<?php selected('disable', $item['load_mode']) ?>>
									<?php _e('Disable', 'gonzales') ?>
								</option>
							</select>
							<button class="wam-button wam-button--default wam-button__icon js-wam-button__icon--cogs js-wam-open-asset-settings<?php echo esc_attr($item['settings_button_classes']); ?>"></button>
						</td>
						<td class="wam-table__td-type">
                        <span class="wam-asset-type wam-asset-type--<?php echo esc_attr($resource_type); ?>">
                            <?php echo esc_attr($resource_type); ?>
                        </span>
						</td>
						<td class="wam-table__td-handle">
							<?php echo esc_html($resource_handle); ?><br>
							<a href="<?php echo esc_url($item['url_full']); ?>">
								<?php echo esc_html($item['url_short']); ?>
							</a>
							<?php do_action('wam/views/assets/handle_column/after_url', $item); ?>
							<div class="wam-table__handle-deps">
								<?php if( !empty($item['deps']) ): ?>
									<span class="wam-colors--grey"><?php _e('Dependency by', 'gonzales') ?></span>:
									<span class="wam-table__asset-deps js-wam-table__asset-deps">
                                        <?php echo implode(', ', $item['deps']); ?>
                                    </span><br>
								<?php endif; ?>
								<?php if( !empty($item['requires']) ): ?>
									<span class="wam-colors--red"><?php _e('Requires for', 'gonzales') ?></span>:
									<span class="wam-table__asset-requires js-wam-table__asset-requires">
                                        <?php echo implode(', ', $item['requires']); ?>
                                    </span>
								<?php endif; ?>
							</div>
						</td>
						<td class="wam-table__td-version"><?php echo esc_html($item['ver']); ?></td>
						<td class="wam-table__td-size"><?php echo esc_html($item['size']); ?> KB</td>
					</tr>
					<tr id="wam-table__loaded-resourse-<?php echo md5($resource_handle . $resource_type . $item['url_full']); ?>-conditions" class="wam-table__asset-settings-conditions">
						<td colspan="5">
							<!--<p>
                                <input type="checkbox" class="wam-checkbox wam-table__checkbox">
								<?php _e('Don\'t optimize file', 'gonzales') ?>
                                <i class="wam-help-hint wam-tooltip wam-tooltip--bottom" data-tooltip="<?php _e('You’ve enabled the &#34;Optimize js scripts?&#34; and &#34;Optimize CSS options&#34; in the &#34;Minify & Combine plugin&#34;. These settings exclude scripts and styles that you don’t want to optimize. Press No to add a file to the excluded list.', 'gonzales') ?>"></i>
                            </p>
                            <p>
                                <input type="checkbox" class="wam-checkbox wam-table__checkbox">
								<?php _e('Don\'t remove query string (version)', 'gonzales') ?>
                                <i class="wam-help-hint wam-tooltip wam-tooltip--bottom" data-tooltip="<?php _e('You’ve enabled &#34;Remove query strings&#34; from static resources in the &#34;Clearfy&#34; plugin. This list of settings helps you to exclude the necessary scripts and styles with remaining query strings. Press No to add a file to the excluded list.', 'gonzales') ?>"></i>
                            </p>-->
							<p>
								<?php _e('<strong> You must set rules to disable the resource.</strong>
                            For example, if you select Page -> Equals -> All posts, then the script or style will not
                            loaded on all pages of type post.', 'gonzales') ?>
							</p>
							<div class="wam-asset-conditions-builder">
								<input type="hidden" data-plugin-name="<?php echo esc_attr($plugin_name) ?>" data-resource-type="<?php echo esc_attr($resource_type) ?>" data-resource-handle="<?php echo esc_attr($resource_handle) ?>" class="wam-conditions-builder__settings" value="<?php echo esc_attr($item['visability']) ?>">
							</div>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endforeach; ?>
		<?php endif; ?>
	</table>
</div>
