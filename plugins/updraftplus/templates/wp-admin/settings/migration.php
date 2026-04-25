<?php
if (!defined('UPDRAFTPLUS_DIR')) die('No access.');

?>
<?php if ($is_extendify_migration_active) { ?>
	<h2><?php esc_html_e('Simple Migration (copy a site using WordPress login details)', 'updraftplus'); ?></h2>
<?php } else { ?>
	<h2><?php esc_html_e('Simple Migration (move your site with just login details)', 'updraftplus'); ?></h2>
<?php } ?>

<div id="updraftplus-migration" class="postbox updraftplus-migration">

	<div class="<?php echo esc_attr($updraftplus_module_widget_class); ?>">
		<header>
			<h3><span class="dashicons dashicons-migrate"></span><?php esc_html_e('Simple Migration', 'updraftplus'); ?>
			</h3>
			<button class="button button-link updraft_migrate_widget_migration_show_stage0"><span
					class="dashicons dashicons-info"></span></button>
		</header>

		<?php
		/**
		 * Fires to render the Simple Migration UI in the Migrate/Clone tab.
		 *
		 * This action allows integrations (such as UpdraftPlus Premium or Extendify)
		 * to output their own Simple Migration interface.
		 *
		 * Callbacks should check the `$context` parameter and render output only
		 * when it matches the integration they are responsible for (e.g. 'premium',
		 * 'extendify').
		 *
		 * Callbacks that need to run before others may use an earlier priority.
		 *
		 * @param string $context The current migration context determining which
		 *                        integration should render the UI.
		 */
		do_action('updraftplus_render_simple_migration_ui', $context);
		?>
	</div>
</div>