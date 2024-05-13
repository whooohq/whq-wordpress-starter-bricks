<?php
/**
 * Add custom fields to menu item (Appearance > Menus)
 *
 * Much better than using the Walker_Nav_Menu_Edit class ;)
 *
 * https://make.wordpress.org/core/2020/02/25/wordpress-5-4-introduces-new-hooks-to-add-custom-fields-to-menu-items/
 *
 * @since 1.8
 */
function bricks_nav_menu_item_custom_fields( $item_id, $item ) {
	$is_multilevel         = get_post_meta( $item_id, '_bricks_multilevel', true );
	$mega_menu_template_id = get_post_meta( $item_id, '_bricks_mega_menu_template_id', true );

	// Return: Not a top level menu item and not a mega menu template nor multilevel menu
	if ( $item->menu_item_parent != 0 && ( ! $is_multilevel && ! $mega_menu_template_id ) ) {
		return;
	}

	// Mega menu (Bricks template)
	// NOTE: Meta query not working (too early?)
	$templates = get_posts(
		[
			'post_type'      => BRICKS_DB_TEMPLATE_SLUG,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		// 'meta_query'     => [
		// [
		// 'key'     => BRICKS_DB_TEMPLATE_TYPE,
		// 'value'   => [ 'section', 'single' ],
		// 'compare' => 'IN',
		// ],
		// ],
		]
	);
	?>
	<p class="field-bricks-mega-menu description description-wide">
		<label for="bricks_mega_menu_template_id[<?php echo $item_id; ?>]">
			<?php esc_html_e( 'Mega menu', 'bricks' ); ?> (Bricks template)
			<br>
			<select class="widefat" name="bricks_mega_menu_template_id[<?php echo $item_id; ?>]" id="bricks_mega_menu_template_id[<?php echo $item_id; ?>]">
				<option value=""><?php esc_html_e( 'Select template', 'bricks' ); ?></option>
				<?php
				foreach ( $templates as $template ) {
					echo '<option value="' . $template->ID . '" ' . selected( $template->ID, $mega_menu_template_id, false ) . '>' . $template->post_title . '</option>';
				}
				?>
			</select>
		</label>
	</p>

	<p class="field-bricks-multilevel description description-wide">
		<label for="bricks_multilevel[<?php echo $item_id; ?>]">
			<input type="checkbox" name="bricks_multilevel[<?php echo $item_id; ?>]" id="bricks_multilevel[<?php echo $item_id; ?>]" <?php checked( $is_multilevel ); ?>>
			<?php esc_html_e( 'Multilevel', 'bricks' ); ?> (Bricks)
		</label>
	</p>
	<?php
}
add_action( 'wp_nav_menu_item_custom_fields', 'bricks_nav_menu_item_custom_fields', 10, 2 );

/**
 * Save the menu item postmeta
 *
 * Mega menu (= selected Bricks template ID )
 * Multilevel menu
 *
 * @param int $menu_id
 * @param int $menu_item_db_id
 *
 * @since 1.8
 */
function bricks_update_nav_menu_item( $menu_id, $menu_item_db_id ) {
	// STEP: Save delete mega menu template id in postmeta of menu item
	$mega_menu_template_id = isset( $_POST['bricks_mega_menu_template_id'][ $menu_item_db_id ] ) ? intval( $_POST['bricks_mega_menu_template_id'][ $menu_item_db_id ] ) : 0;

	// Save/delete mega menu template id in postmeta of menu item
	if ( $mega_menu_template_id ) {
		update_post_meta( $menu_item_db_id, '_bricks_mega_menu_template_id', sanitize_text_field( $mega_menu_template_id ) );
	} else {
		delete_post_meta( $menu_item_db_id, '_bricks_mega_menu_template_id' );
	}

	// STEP: Save delete multivel (1) in postmeta of menu item
	$mega_menu_template_id = isset( $_POST['bricks_multilevel'][ $menu_item_db_id ] ) ? intval( $_POST['bricks_multilevel'][ $menu_item_db_id ] ) : '';

	// Save/delete mega menu template id in postmeta of menu item
	if ( $mega_menu_template_id ) {
		update_post_meta( $menu_item_db_id, '_bricks_multilevel', true );
	} else {
		delete_post_meta( $menu_item_db_id, '_bricks_multilevel' );
	}
}
add_action( 'wp_update_nav_menu_item', 'bricks_update_nav_menu_item', 10, 2 );
