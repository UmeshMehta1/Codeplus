<?php

defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * @var array     $data
 * @var WGZ_Views $this
 */

?>
<div id="WBCR-AM" class="wam-wrapper" style="display: block;">
	<?php $this->print_template( 'part-assets-manager-header', $data ); ?>
    <main class="wam-content">
		<?php $this->print_template( 'part-assets-manager-tabs-menu' ); ?>
		<?php //$this->print_template( 'part-assets-manager-info-bar' ); ?>
        <div id="wam-assets-type-tab-content__theme" data-category="theme" class="wam-assets-type-tab-content">
			<?php $this->print_template( 'tab-content-assets', [
				'type'   => 'theme',
				'assets' => $data['theme_assets']
			] ); ?>
        </div>
        <div id="wam-assets-type-tab-content__misc" data-category="misc" class="wam-assets-type-tab-content">
			<?php $this->print_template( 'tab-content-assets', [
				'type'   => 'misc',
				'assets' => $data['misc_assets']
			] ); ?>
        </div>
        <div id="wam-assets-type-tab-content__plugins" data-category="plugins" class="wam-assets-type-tab-content wam-assets-type-tab-content__active">
			<?php $this->print_template( 'tab-content-assets-plugins', $data ); ?>
        </div>
    </main>
    <!-- Html template Conditions Editor -->
    <script type="text/html" id="wam-conditions-builder-template">
		<?php $this->print_template( 'conditions-logic-editor-template', $data ); ?>
    </script>
    <!-- /End Html template -->
</div> <!-- /div2 -->