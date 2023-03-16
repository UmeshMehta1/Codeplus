<?php
	
	defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );
	
	/**
	 * @var array $data
	 * @var WGZ_Views $this
	 */
?>
<div class="wam-info-section">
    <div class="wam-info-section__warning">
        <p>
            <b>
				<?php _e( 'Important! Each page of your website has different sets of scripts and styles files.', 'gonzales' ) ?>
            </b>
        </p>
        <p>
			<?php _e( 'Use this feature to disable unwanted scripts and styles by setting up the logic for
                        different types of pages. We recommend working in "Safe mode" because disabling any necessary
                        system script file can corrupt the website. All changes done in Safe mode are available for
                        administrator only. This way only you, as the administrator, can see the result of optimization.
                        To enable the changes for other users, uncheck Safe mode.', 'gonzales' ) ?>
        </p>
        <p>
			<?php echo sprintf( __( 'For more details and user guides, check the plugin’s <a href="%s" target="_blank" rel="noreferrer noopener">documentation</a>.', 'gonzales' ), WGZ_Plugin::app()->get_support()->get_docs_url( true, 'docs' ) ) ?>
        </p>
    </div>
    <a class="wbcr-gnz-button__pro"
       href="<?php echo WGZ_Plugin::app()->get_support()->get_tracking_page_url( 'assets-manager', 'assets-manager' ) ?>"
       target="_blank" rel="noreferrer noopener">'
		<?php _e( 'Upgrade to Premium', 'gonzales' ) ?></a>
    <div class="wam-info-section__go-to-premium">
        <h3>
            <span><?php _e( 'MORE IN CLEARFY BUSINESS', 'gonzales' ) ?></span>
        </h3>
        <ul>
            <li><?php _e( 'Disable plugins (groups of scripts)', 'gonzales' ) ?></li>
            <li><?php _e( 'Conditions by the link template', 'gonzales' ) ?></li>
            <li><?php _e( 'Conditions by the regular expression', 'gonzales' ) ?></li>
            <li><?php _e( 'Safe mode', 'gonzales' ) ?></li>
            <li><?php _e( 'Statistics and optimization results', 'gonzales' ) ?></li>
        </ul>
    </div>
</div>