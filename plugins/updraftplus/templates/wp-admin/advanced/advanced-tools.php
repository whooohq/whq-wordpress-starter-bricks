<?php
	if (!defined('ABSPATH')) die('No direct access allowed');
?>
<div class="expertmode">
	<p>
		<em><?php esc_html_e('Unless you have a problem, you can completely ignore everything here.', 'updraftplus');?></em>
	</p>
	<div class="advanced_settings_container">
		<div class="advanced_settings_menu">
			<?php
				$updraftplus_admin->include_template('/wp-admin/advanced/tools-menu.php');
			?>
		</div>
		<div class="advanced_settings_content">
			<?php
				if (empty($options)) $options = array();
				$site_info_pass_through = array('options' => $options);
				if (isset($site_info_data)) $site_info_pass_through['site_info_data'] = $site_info_data;
				$updraftplus_admin->include_template('/wp-admin/advanced/site-info.php', false, $site_info_pass_through);
				$updraftplus_admin->include_template('/wp-admin/advanced/lock-admin.php');
				$updraftplus_admin->include_template('/wp-admin/advanced/updraftcentral.php');
				$updraftplus_admin->include_template('/wp-admin/advanced/search-replace.php');
				$updraftplus_admin->include_template('/wp-admin/advanced/total-size.php');
				$updraftplus_admin->include_template('/wp-admin/advanced/db-size.php');
				$updraftplus_admin->include_template('/wp-admin/advanced/cron-events.php');
				$updraftplus_admin->include_template('/wp-admin/advanced/export-settings.php');
				$updraftplus_admin->include_template('/wp-admin/advanced/wipe-settings.php');
			?>
		</div>
	</div>
</div>
