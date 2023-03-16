<?php
defined('ABSPATH') || die('Cheatin’ uh?');

/**
 * @var array $data
 * @var WGZ_Views $this
 */
?>
<div class="wam-cleditor__empty wam-cleditor">
	<div class="wam-cleditor__wrap">
		<div class="wam-cleditor__when-empty">
			<?php _e('No filters specified.', 'gonzales') ?>
			<a href="#" class="js-wam-cleditor__add-group"><?php _e('Click here', 'gonzales') ?></a> <?php _e('to add one.', 'gonzales') ?>
		</div>
		<div class="wam-cleditor__groups"></div>
	</div>
	<div class="wam-cleditor__group">
		<div class="wam-cleditor__point"></div>
		<div class="wam-cleditor__head">
			<div class="wam-cleditor__head-left">
                <span class="wam-cleditor__first-group-title">
                    <?php _e('Disable If', 'gonzales') ?>
                </span>
				<span class="wam-cleditor__group-type"><?php _e('OR', 'gonzales') ?></span>
			</div>
			<div class="wam-cleditor__head-right">
				<button type="button" class="wam-button wam-button--small wam-button--danger js-wam-cleditor__remove-group">
					<?php _e('Delete', 'gonzales'); ?>
				</button>
			</div>
		</div>
		<div class="wam-cleditor__box">
			<div class="wam-cleditor__when-empty">
				<?php _e('No filters specified.', 'gonzales') ?>
				<a href="#" class="js-wam-cleditor__add-condition"><?php _e('Click here', 'gonzales') ?></a> <?php _e('to add one.', 'gonzales') ?>
			</div>
			<div class="wam-cleditor__conditions"></div>
		</div>
	</div>
	<div class="wam-cleditor__condition">
		<div class="wam-cleditor__operator-and"><span><?php _e('and', 'gonzales') ?></span></div>
		<span class="wam-cleditor__params">
            <select class="wam-cleditor__param-select">
                <?php if( !empty($data['conditions_logic_params']) ): ?>
	                <?php foreach((array)$data['conditions_logic_params'] as $filter_param) { ?>
		                <optgroup label="<?php echo $filter_param['title'] ?>">
                        <?php foreach((array)$filter_param['items'] as $param) { ?>
	                        <?php
	                        $option_attrs = [];
	                        $option_attrs[] = 'data-type="' . esc_attr($param['type']) . '"';

	                        if( isset($param['default_value']) ) {
		                        $option_attrs[] = 'data-default-value="' . esc_attr($param['default_value']) . '"';
	                        }

	                        if( isset($param['placeholder']) ) {
		                        $placeholder = is_array($param['placeholder']) ? @json_encode($param['placeholder'], JSON_UNESCAPED_UNICODE, JSON_HEX_QUOT) : $param['placeholder'];
		                        $option_attrs[] = 'data-placeholder="' . esc_attr($placeholder) . '"';
	                        }

	                        if( isset($param['params']) ) {
		                        $option_attrs[] = 'data-params="' . esc_attr(@json_encode($param['params'], JSON_UNESCAPED_UNICODE, JSON_HEX_QUOT)) . '"';
	                        }

	                        if( isset($param['only_equals']) ) {
		                        $option_attrs[] = 'data-only-equals="' . intval($param['only_equals']) . '"';
	                        }
	                        if( isset($param['description']) ) {
		                        $option_attrs[] = 'data-hint="' . esc_attr($param['description']) . '"';
	                        }

	                        $option_disabled = isset($param['disabled']) ? $param['disabled'] : false;

	                        ?>
	                        <option<?php echo ' ' . implode(' ', $option_attrs) ?> value="<?php echo esc_attr($param['id']) ?>"<?php disabled($option_disabled) ?>>
                                <?php echo $param['title'] ?>
                            </option>
                        <?php } ?>
                    </optgroup>
	                <?php } ?>
                <?php endif; ?>
            </select>
            <i class="wam-cleditor__hint">
                <span class="wam-cleditor__hint-icon"></span>
                <span class="wam-cleditor__hint-content"></span>
            </i>
        </span>
		<span class="wam-cleditor__condition-operators">
            <select class="wam-cleditor__operator-select">
                <option value="equals"><?php _e('Equals', 'gonzales') ?></option>
                <option value="notequal"><?php _e('Doesn\'t Equal', 'gonzales') ?></option>
                <option value="greater"><?php _e('Greater Than', 'gonzales') ?></option>
                <option value="less"><?php _e('Less Than', 'gonzales') ?></option>
                <option value="older"><?php _e('Older Than', 'gonzales') ?></option>
                <option value="younger"><?php _e('Younger Than', 'gonzales') ?></option>
                <option value="contains"><?php _e('Contains', 'gonzales') ?></option>
                <option value="notcontain"><?php _e('Doesn\'t Сontain', 'gonzales') ?></option>
                <option value="between"><?php _e('Between', 'gonzales') ?></option>
            </select>
        </span>
		<span class="wam-cleditor__condition-value"></span>
		<span class="wam-cleditor__condition-actions">
                <a href="#" class="wam-button wam-button--danger button-sm js-wam-cleditor__condition-remove"><?php _e('X', 'gonzales') ?></a>
                <a href="#" class="wam-button wam-button--yellow button-sm js-wam-cleditor__condition-add-and"><?php _e('AND', 'gonzales') ?></a>
        </span>
	</div>
	<div class="wam-cleditor__buttons-group">
		<button type="button" class="wam-button wam-button--default wam-cleditor__button-left js-wam-cleditor__add-group">
			<?php _e('Add new group', 'gonzales') ?>
		</button>
	</div>
</div>
