<?php
	if (!defined('ABSPATH')) die('No direct access allowed');
?>
<div class="advanced_tools site_info">
	<h3><?php esc_html_e('Site information', 'updraftplus');?></h3>
	<table>
	<?php
	if (isset($site_info_data) && is_array($site_info_data)) {
		foreach ($site_info_data as $info) {
			if (isset($info['is_html']) && $info['is_html']) {
				$updraftplus_admin->settings_debugrow($info['label'], $info['value']);
			} else {
				$updraftplus_admin->settings_debugrow($info['label'], wp_kses($info['value'], $updraftplus_admin->kses_allow_tags()));
			}
		}
	}
	?>
	</table>
</div>
