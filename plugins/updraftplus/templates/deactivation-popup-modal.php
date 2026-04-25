<?php
	if (!defined('UPDRAFTPLUS_DIR')) die('No direct access allowed');
?>
<div class="udp-dialog-intro">
	<?php esc_html_e('Deactivating stops scheduled backups and all other plugin features, but does not delete any stored backup sets.', 'updraftplus'); ?>
</div>
<div class="udp-deinstall-dialog-content">
	<div class="udp-remove-data">
		<label class="udp-toggle-container">
			<input type="checkbox" name="updraft_deinstall_option" value="yes">
			<span class="udp-toggle-slider"></span>
		</label>
		<div class="udp-remove-text">
			<h4><?php esc_html_e('Remove plugin settings', 'updraftplus'); ?></h4>
			<p><?php esc_html_e('Permanently deletes all UpdraftPlus settings and saved data, but not any stored backup sets.', 'updraftplus'); ?>
				<?php esc_html_e("This can't be undone.", 'updraftplus'); ?>
			</p>
		</div>
	</div>
</div>
