<?php

defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * @var array     $data
 * @var WGZ_Views $this
 */
if ( empty( $data['loaded_plugins'] ) ) {
	echo 'Plugins is not found!';

	return;
}

$active_plugin = reset( $data['loaded_plugins'] );
?>
<table class="wam-table">
    <thead>
    <tr>
        <th class="wam-table__th-plugins-list"><?php _e( "Plugins", 'gonzales' ) ?></th>
        <th class="wam-table__th-plugins-settings"><?php echo $active_plugin['info']['Title']; ?></th>
    </tr>
    </thead>
    <tbody>
    <tr>
        <td class="wam-table__td-plugins-list">
            <ul class="wam-nav-plugins">
				<?php foreach ( (array) $data['loaded_plugins'] as $plugin_name => $plugin ): ?>
                    <li class="wam-nav-plugins__tab wam-nav-plugins__tab-load-mode--<?php echo esc_attr( str_replace( '_', '-', $plugin['load_mode'] ) ); ?> js-wam-nav-plugins__tab-switch<?php echo( $active_plugin['name'] == $plugin_name ? ' wam-nav-plugins__tab--active' : '' ) ?>">
                        <a href="#wam-<?php echo esc_attr( $plugin_name ); ?>">
                            <strong class="wam-plugin-name"><?php echo $plugin['info']['Title']; ?></strong>
                            <span><?php _e( 'Author', 'gonzales' ) ?>: <?php echo $plugin['info']['Author']; ?></span>
                            <span><?php _e( 'Version', 'gonzales' ) ?>: <?php echo $plugin['info']['Version']; ?></span>
                        </a>
                    </li>
				<?php endforeach; ?>
            </ul>
        </td>
        <td class="wam-table__td-plugins-settings">
			<?php foreach ( (array) $data['loaded_plugins'] as $plugin_name => $plugin ): ?>
                <div id="wam-<?php echo esc_attr( $plugin_name ); ?>" class="wam-nav-plugins__tab-content<?php echo( $active_plugin['name'] == $plugin_name ? ' js-wam-nav-plugins__tab-content--active' : '' ) ?>">
					<?php $this->print_template( 'part-tab-content-assets-plugins-settings', $plugin ); ?>
                </div>
			<?php endforeach; ?>
        </td>
    </tr>
    </tbody>
</table> <!-- /end .wam-table -->