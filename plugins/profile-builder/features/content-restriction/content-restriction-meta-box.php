<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

function wppb_content_restriction_initialize_meta_box() {

    add_action( 'add_meta_boxes', 'wppb_content_restriction_add_meta_box' );

}
add_action( 'init', 'wppb_content_restriction_initialize_meta_box', 999 );

function wppb_content_restriction_add_meta_box() {

    $post_types = get_post_types( array( 'public' => true ) );

    if( ! empty( $post_types ) ) {
        foreach( $post_types as $post_type ) {

            if( apply_filters( 'wppb_content_restriction_enabled_for_post_type', true, $post_type ) ){
                add_meta_box(
                    'wppb_post_content_restriction',
                    __( 'Profile Builder Content Restriction', 'profile-builder' ),
                    'wppb_content_restriction_meta_box_output',
                    $post_type,
                    'normal'
                );
            }

        }
    }

}

function wppb_content_restriction_get_supported_taxonomies() {

    $taxonomies = get_taxonomies( array( 'public' => true ), 'objects' );

    if ( empty( $taxonomies ) ) {
        return array();
    }

    $disabled_taxonomies = array(
            'nav_menu',
            'link_category',
            'post_format',
            'product_visibility',
            'product_shipping_class',
            'action-group',
            'product_type',
            'wp_theme',
            'wp_template_part_area',
    );

    foreach ( $taxonomies as $taxonomy => $taxonomy_object ) {
        if ( in_array( $taxonomy, $disabled_taxonomies, true ) ) {
            unset( $taxonomies[ $taxonomy ] );
            continue;
        }

        if ( ! apply_filters( 'wppb_content_restriction_enabled_for_taxonomy', true, $taxonomy, $taxonomy_object ) ) {
            unset( $taxonomies[ $taxonomy ] );
        }
    }

    return $taxonomies;
}

function wppb_content_restriction_initialize_taxonomy_fields() {

    $taxonomies = wppb_content_restriction_get_supported_taxonomies();

    foreach ( $taxonomies as $taxonomy => $taxonomy_object ) {
        add_action( $taxonomy . '_add_form_fields', 'wppb_content_restriction_taxonomy_add_form_fields' );
        add_action( $taxonomy . '_edit_form_fields', 'wppb_content_restriction_taxonomy_edit_form_fields', 10, 2 );
    }

    add_action( 'create_term', 'wppb_content_restriction_save_term_data', 10, 3 );
    add_action( 'edit_term', 'wppb_content_restriction_save_term_data', 10, 3 );
}
add_action( 'init', 'wppb_content_restriction_initialize_taxonomy_fields', 1000 );

function wppb_content_restriction_taxonomy_add_form_fields( $taxonomy ) {
    wppb_content_restriction_taxonomy_fields_output( null, $taxonomy );
}

function wppb_content_restriction_taxonomy_edit_form_fields( $term, $taxonomy ) {
    wppb_content_restriction_taxonomy_fields_output( $term, $taxonomy );
}

function wppb_content_restriction_taxonomy_fields_output( $term = null, $taxonomy = '' ) {

    $term_id                          = ! empty( $term->term_id ) ? (int) $term->term_id : 0;
    $wppb_content_restrict_types      = apply_filters( 'wppb_single_post_content_restrict_types', array( 'message' => __( 'Message', 'profile-builder' ), 'redirect' => __( 'Redirect', 'profile-builder' ) ) );
    $wppb_content_restrict_type       = $term_id ? get_term_meta( $term_id, 'wppb-content-restrict-type', true ) : '';
    $wppb_user_status                 = $term_id ? get_term_meta( $term_id, 'wppb-content-restrict-user-status', true ) : '';
    $wppb_selected_roles              = $term_id ? get_term_meta( $term_id, 'wppb-content-restrict-user-role' ) : array();
    $wppb_custom_redirect_url_enabled = $term_id ? get_term_meta( $term_id, 'wppb-content-restrict-custom-redirect-url-enabled', true ) : '';
    $wppb_custom_redirect_url         = $term_id ? get_term_meta( $term_id, 'wppb-content-restrict-custom-redirect-url', true ) : '';
    $wppb_custom_messages_enabled     = $term_id ? get_term_meta( $term_id, 'wppb-content-restrict-messages-enabled', true ) : '';
    $wppb_roles                       = get_editable_roles();
    $object                           = get_taxonomy( $taxonomy );
    $description_label                = ! empty( $object->labels->singular_name ) ? $object->labels->singular_name : __( 'term', 'profile-builder' );

    if ( empty( $wppb_selected_roles ) || ! is_array( $wppb_selected_roles ) ) {
        $wppb_selected_roles = array();
    }

    ob_start();
    ?>
    <div class="wppb-meta-box-fields-wrapper wppb-taxonomy-content-restriction cozmoslabs-form-subsection-wrapper">
        <h4 class="cozmoslabs-subsection-title"><?php esc_html_e( 'Profile Builder Content Restriction', 'profile-builder' ); ?></h4>

        <div class="wppb-meta-box-field-wrapper cozmoslabs-form-field-wrapper">
            <label class="wppb-meta-box-field-label cozmoslabs-form-field-label"><?php esc_html_e( 'Type of Restriction', 'profile-builder' ); ?></label>

            <div class="cozmoslabs-radio-inputs-row">
                <label class="wppb-meta-box-checkbox-label" for="wppb-content-restrict-type-default">
                    <input type="radio" id="wppb-content-restrict-type-default" value="default" <?php checked( empty( $wppb_content_restrict_type ) || $wppb_content_restrict_type === 'default' ); ?> name="wppb-content-restrict-type">
                    <?php esc_html_e( 'Settings Default', 'profile-builder' ); ?>
                </label>

                <?php foreach ( $wppb_content_restrict_types as $type_slug => $type_label ) : ?>
                    <label class="wppb-meta-box-checkbox-label" for="wppb-content-restrict-type-<?php echo esc_attr( $type_slug ); ?>">
                        <input type="radio" id="wppb-content-restrict-type-<?php echo esc_attr( $type_slug ); ?>" value="<?php echo esc_attr( $type_slug ); ?>" <?php checked( $wppb_content_restrict_type, $type_slug ); ?> name="wppb-content-restrict-type">
                        <?php echo esc_html( $type_label ); ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="wppb-meta-box-field-wrapper cozmoslabs-form-field-wrapper cozmoslabs-checkbox-list-wrapper">
            <label class="wppb-meta-box-field-label cozmoslabs-form-field-label"><?php esc_html_e( 'Display For', 'profile-builder' ); ?></label>

            <div class="cozmoslabs-checkbox-list cozmoslabs-checkbox-multi-col-list">
                <div class="cozmoslabs-chckbox-container">
                    <input type="checkbox" value="loggedin" <?php checked( $wppb_user_status, 'loggedin' ); ?> name="wppb-content-restrict-user-status" id="wppb-content-restrict-user-status">
                    <label class="wppb-meta-box-checkbox-label" for="wppb-content-restrict-user-status">
                        <?php esc_html_e( 'Logged In Users', 'profile-builder' ); ?>
                    </label>
                </div>

                <?php foreach ( $wppb_roles as $wppb_role => $wppb_role_details ) : ?>
                    <div class="cozmoslabs-chckbox-container">
                        <input type="checkbox" value="<?php echo esc_attr( $wppb_role ); ?>" <?php checked( in_array( $wppb_role, $wppb_selected_roles, true ) ); ?> name="wppb-content-restrict-user-role[]" id="wppb-content-restrict-user-role-<?php echo esc_attr( $wppb_role ); ?>">
                        <label class="wppb-meta-box-checkbox-label" for="wppb-content-restrict-user-role-<?php echo esc_attr( $wppb_role ); ?>">
                            <?php echo ! empty( $wppb_role_details['name'] ) ? esc_html( $wppb_role_details['name'] ) : esc_html( $wppb_role ); ?>
                        </label>
                    </div>
                <?php endforeach; ?>

                <p class="cozmoslabs-description">
                    <?php echo esc_html( sprintf( __( 'Checking only "Logged In Users" will show this %s to all logged in users, regardless of user role.', 'profile-builder' ), $description_label ) ); ?>
                </p>

                <p class="cozmoslabs-description">
                    <?php echo esc_html( sprintf( __( 'Checking any user role will show this %s only to users that have one of those user roles assigned.', 'profile-builder' ), $description_label ) ); ?>
                </p>
            </div>
        </div>
    </div>

    <div id="wppb-meta-box-fields-wrapper-restriction-redirect-url" class="wppb-meta-box-fields-wrapper wppb-taxonomy-content-restriction cozmoslabs-form-subsection-wrapper <?php echo ( $wppb_content_restrict_type === 'redirect' ? 'wppb-content-restriction-enabled' : '' ); ?>">
        <h4 class="cozmoslabs-subsection-title"><?php esc_html_e( 'Restriction Redirect URL', 'profile-builder' ); ?></h4>

        <div class="wppb-meta-box-field-wrapper cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
            <label class="wppb-meta-box-field-label cozmoslabs-form-field-label" for="wppb-content-restrict-custom-redirect-url-enabled"><?php esc_html_e( 'Enable Custom Redirect URL', 'profile-builder' ); ?></label>

            <div class="cozmoslabs-toggle-container">
                <input type="checkbox" value="yes" <?php checked( ! empty( $wppb_custom_redirect_url_enabled ) ); ?> name="wppb-content-restrict-custom-redirect-url-enabled" id="wppb-content-restrict-custom-redirect-url-enabled">
                <label class="cozmoslabs-toggle-track" for="wppb-content-restrict-custom-redirect-url-enabled"></label>
            </div>
            <div class="cozmoslabs-toggle-description">
                <label for="wppb-content-restrict-custom-redirect-url-enabled" class="cozmoslabs-description"><?php echo esc_html( sprintf( __( 'Enable if you wish to add a custom redirect URL for this %s.', 'profile-builder' ), $description_label ) ); ?></label>
            </div>
        </div>

        <div class="wppb-meta-box-field-wrapper cozmoslabs-form-field-wrapper wppb-meta-box-field-wrapper-custom-redirect-url <?php echo ( ! empty( $wppb_custom_redirect_url_enabled ) ? 'wppb-content-restriction-enabled' : '' ); ?>">
            <label class="wppb-meta-box-field-label cozmoslabs-form-field-label" for="wppb-content-restrict-custom-redirect-url"><?php esc_html_e( 'Custom Redirect URL', 'profile-builder' ); ?></label>
            <input type="text" value="<?php echo esc_attr( $wppb_custom_redirect_url ); ?>" name="wppb-content-restrict-custom-redirect-url" id="wppb-content-restrict-custom-redirect-url" class="widefat">
            <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php echo esc_html( sprintf( __( 'Add a URL where you wish to redirect users that do not have access to this %s and try to access it directly.', 'profile-builder' ), $description_label ) ); ?></p>
        </div>
    </div>

    <div id="wppb-meta-box-fields-wrapper-restriction-custom-messages" class="wppb-meta-box-fields-wrapper wppb-taxonomy-content-restriction cozmoslabs-form-subsection-wrapper">
        <h4 class="cozmoslabs-subsection-title"><?php esc_html_e( 'Restriction Messages', 'profile-builder' ); ?></h4>

        <div class="wppb-meta-box-field-wrapper cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
            <label class="wppb-meta-box-field-label cozmoslabs-form-field-label" for="wppb-content-restrict-messages-enabled"><?php esc_html_e( 'Enable Custom Messages', 'profile-builder' ); ?></label>

            <div class="cozmoslabs-toggle-container">
                <input type="checkbox" value="yes" <?php checked( ! empty( $wppb_custom_messages_enabled ) ); ?> name="wppb-content-restrict-messages-enabled" id="wppb-content-restrict-messages-enabled">
                <label class="cozmoslabs-toggle-track" for="wppb-content-restrict-messages-enabled"></label>
            </div>
            <div class="cozmoslabs-toggle-description">
                <label for="wppb-content-restrict-messages-enabled" class="cozmoslabs-description"><?php echo esc_html( sprintf( __( 'Enable if you wish to add custom messages for this %s.', 'profile-builder' ), $description_label ) ); ?></label>
            </div>
        </div>

        <div class="wppb-meta-box-field-wrapper-custom-messages <?php echo ( ! empty( $wppb_custom_messages_enabled ) ? 'wppb-content-restriction-enabled' : '' ); ?>">
            <div class="cozmoslabs-form-field-wrapper cozmoslabs-wysiwyg-wrapper cozmoslabs-wysiwyg-indented">
                <label class="cozmoslabs-form-field-label"><?php esc_html_e( 'Messages for logged-out users', 'profile-builder' ); ?></label>
                <?php wp_editor( get_term_meta( $term_id, 'wppb-content-restrict-message-logged_out', true ), 'wppb-content-restrict-message-logged_out', array( 'textarea_name' => 'wppb-content-restrict-message-logged_out', 'editor_height' => 180 ) ); ?>
            </div>

            <div class="cozmoslabs-form-field-wrapper cozmoslabs-wysiwyg-wrapper cozmoslabs-wysiwyg-indented">
                <label class="cozmoslabs-form-field-label"><?php esc_html_e( 'Messages for logged-in users', 'profile-builder' ); ?></label>
                <?php wp_editor( get_term_meta( $term_id, 'wppb-content-restrict-message-logged_in', true ), 'wppb-content-restrict-message-logged_in', array( 'textarea_name' => 'wppb-content-restrict-message-logged_in', 'editor_height' => 180 ) ); ?>
            </div>
        </div>
    </div>

    <?php wp_nonce_field( 'wppb_meta_box_single_content_restriction_nonce', 'wppb_content_restriction_token', false ); ?>
    <?php

    $content = ob_get_clean();

    if ( empty( $term_id ) ) {
        echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        return;
    }
    ?>
    <tr class="form-field wppb-taxonomy-content-restriction-row">
        <th scope="row"><?php esc_html_e( 'Content Restriction', 'profile-builder' ); ?></th>
        <td class="wppb-taxonomy-content-restriction-cell"><?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
    </tr>
    <?php
}

function wppb_content_restriction_meta_box_output( $post ) {

    ?>
    <div class="wppb-meta-box-fields-wrapper cozmoslabs-form-subsection-wrapper">
        <h4 class="cozmoslabs-subsection-title"><?php echo esc_html__( 'Display Options', 'profile-builder' ); ?></h4>

        <div class="wppb-meta-box-field-wrapper cozmoslabs-form-field-wrapper">
            <?php
            $wppb_content_restrict_types = apply_filters( 'wppb_single_post_content_restrict_types', array( 'message' => __( 'Message', 'profile-builder' ), 'redirect' => __( 'Redirect', 'profile-builder' ) ) );

            $wppb_content_restrict_type = get_post_meta( $post->ID, 'wppb-content-restrict-type', true );
            ?>

            <label class="wppb-meta-box-field-label cozmoslabs-form-field-label"><?php esc_html_e( 'Type of Restriction', 'profile-builder' ); ?></label>

            <div class="cozmoslabs-radio-inputs-row">
                <label class="wppb-meta-box-checkbox-label" for="wppb-content-restrict-type-default">
                    <input type="radio" id="wppb-content-restrict-type-default" value="default" <?php if( empty( $wppb_content_restrict_type ) || $wppb_content_restrict_type == 'default' ) echo 'checked="checked"'; ?> name="wppb-content-restrict-type">
                    <?php echo esc_html__( 'Settings Default', 'profile-builder' ); ?>
                </label>

                <?php foreach( $wppb_content_restrict_types as $type_slug => $type_label ) { ?>
                    <label class="wppb-meta-box-checkbox-label" for="wppb-content-restrict-type-<?php echo esc_attr( $type_slug ); ?>">
                        <input type="radio" id="wppb-content-restrict-type-<?php echo esc_attr( $type_slug ); ?>" value="<?php echo esc_attr( $type_slug ); ?>" <?php if( $wppb_content_restrict_type == $type_slug ) echo 'checked="checked"'; ?> name="wppb-content-restrict-type">
                        <?php echo esc_html( $type_label ); ?>
                    </label>
                <?php } ?>
            </div>

        </div>

        <div class="wppb-meta-box-field-wrapper cozmoslabs-form-field-wrapper cozmoslabs-checkbox-list-wrapper">
            <label class="wppb-meta-box-field-label cozmoslabs-form-field-label"><?php esc_html_e( 'Display For', 'profile-builder' ); ?></label>

            <?php
            $wppb_roles = get_editable_roles();
            $wppb_user_status = get_post_meta( $post->ID, 'wppb-content-restrict-user-status', true );
            $wppb_selected_roles = get_post_meta( $post->ID, 'wppb-content-restrict-user-role' );
            ?>

            <div class="cozmoslabs-checkbox-list cozmoslabs-checkbox-multi-col-list">

                <div class="cozmoslabs-chckbox-container">
                    <input type="checkbox" value="loggedin" <?php if( ! empty( $wppb_user_status ) && $wppb_user_status == 'loggedin' ) echo 'checked="checked"'; ?> name="wppb-content-restrict-user-status" id="wppb-content-restrict-user-status">
                    <label class="wppb-meta-box-checkbox-label" for="wppb-content-restrict-user-status">
                        <?php echo esc_html__( 'Logged In Users', 'profile-builder' ); ?>
                    </label>
                </div>

                <?php
                if( ! empty( $wppb_roles ) ) {
                    foreach( $wppb_roles as $wppb_role => $wppb_role_details ) {
                        ?>
                        <div class="cozmoslabs-chckbox-container">
                            <input type="checkbox" value="<?php echo esc_attr( $wppb_role ); ?>" <?php if( in_array( $wppb_role, $wppb_selected_roles ) ) echo 'checked="checked"'; ?> name="wppb-content-restrict-user-role[]" id="wppb-content-restrict-user-role-<?php echo esc_attr( $wppb_role ) ?>">
                            <label class="wppb-meta-box-checkbox-label" for="wppb-content-restrict-user-role-<?php echo esc_attr( $wppb_role ) ?>">
                                <?php echo !empty( $wppb_role_details['name'] ) ? esc_html( $wppb_role_details['name'] ) : esc_html( $wppb_role ); ?>
                            </label>
                        </div>
                    <?php } ?>

                    <p class="cozmoslabs-description">
                        <?php echo esc_html( sprintf( __( 'Checking only "Logged In Users" will show this %s to all logged in users, regardless of user role.', 'profile-builder' ), apply_filters( 'wppb_content_restrict_settings_description_cpt', $post->post_type ) ) ); ?>
                    </p>

                    <p class="cozmoslabs-description">
                        <?php echo esc_html( sprintf( __( 'Checking any user role will show this %s only to users that have one of those user roles assigned.', 'profile-builder' ), apply_filters( 'wppb_content_restrict_settings_description_cpt', $post->post_type ) ) ); ?>
                    </p>
                <?php } ?>

            </div>
        </div>
    </div>

    <?php if( isset( $post->post_type ) && $post->post_type == 'product' ) : ?>
        <div class="wppb-meta-box-fields-wrapper cozmoslabs-form-subsection-wrapper">
            <h4 class="cozmoslabs-subsection-title"><?php esc_html_e( 'Purchase Options', 'profile-builder' ); ?></h4>

            <?php
            $wppb_purchase_user_status    = get_post_meta( $post->ID, 'wppb-purchase-restrict-user-status', true );
            $wppb_selected_purchase_roles = get_post_meta( $post->ID, 'wppb-purchase-restrict-user-role' );
            ?>

            <!-- Who Can Purchase? options -->
            <div class="wppb-meta-box-field-wrapper cozmoslabs-form-field-wrapper cozmoslabs-checkbox-list-wrapper">
                <label class="wppb-meta-box-field-label cozmoslabs-form-field-label"><?php esc_html_e( 'Who can purchase?', 'profile-builder' ); ?></label>

                <div class="cozmoslabs-checkbox-list cozmoslabs-checkbox-multi-col-list">

                    <div class="cozmoslabs-chckbox-container">
                        <input type="checkbox" value="loggedin" <?php if( ! empty( $wppb_purchase_user_status ) ) checked($wppb_purchase_user_status, 'loggedin' ); ?> name="wppb-purchase-restrict-user-status" id="wppb-purchase-restrict-user-status">
                        <label class="wppb-meta-box-checkbox-label" for="wppb-purchase-restrict-user-status">
                            <?php esc_html_e( 'Logged In Users', 'profile-builder' ); ?>
                        </label>
                    </div>

                    <?php if( !empty( $wppb_roles ) ): foreach( $wppb_roles as $wppb_role => $wppb_role_details ): ?>

                        <div class="cozmoslabs-chckbox-container">
                            <input type="checkbox" value="<?php echo esc_attr( $wppb_role ); ?>" <?php if( in_array( $wppb_role, $wppb_selected_purchase_roles ) ) echo 'checked="checked"'; ?> name="wppb-purchase-restrict-user-role[]" id="wppb-purchase-restrict-user-role-<?php echo esc_attr( $wppb_role ) ?>">
                            <label class="wppb-meta-box-checkbox-label" for="wppb-purchase-restrict-user-role-<?php echo esc_attr( $wppb_role ) ?>">
                                <?php echo !empty( $wppb_role_details['name'] ) ? esc_html( $wppb_role_details['name'] ) : esc_html( $wppb_role ); ?>
                            </label>
                        </div>

                    <?php endforeach; ?>

                        <p class="cozmoslabs-description" style="margin-top: 10px;">
                            <?php esc_html_e( 'Select who can purchase this product.', 'profile-builder' ); ?>
                        </p>

                    <?php endif; ?>
                </div>

            </div>
        </div>
    <?php endif; ?>

    <div id="wppb-meta-box-fields-wrapper-restriction-redirect-url" class="wppb-meta-box-fields-wrapper cozmoslabs-form-subsection-wrapper <?php echo ( $wppb_content_restrict_type == 'redirect' ? 'wppb-content-restriction-enabled' : '' ); ?>">
        <h4 class="cozmoslabs-subsection-title"><?php echo esc_html__( 'Restriction Redirect URL', 'profile-builder' ); ?></h4>

        <div class="wppb-meta-box-field-wrapper cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">

            <?php $wppb_custom_redirect_url_enabled = get_post_meta( $post->ID, 'wppb-content-restrict-custom-redirect-url-enabled', true ); ?>

            <label class="wppb-meta-box-field-label cozmoslabs-form-field-label" for="wppb-content-restrict-custom-redirect-url-enabled"><?php esc_html_e( 'Enable Custom Redirect URL', 'profile-builder' ); ?></label>

            <div class="cozmoslabs-toggle-container">
                <input type="checkbox" value="yes" <?php echo ( ! empty( $wppb_custom_redirect_url_enabled ) ? 'checked="checked"' : '' ); ?> name="wppb-content-restrict-custom-redirect-url-enabled" id="wppb-content-restrict-custom-redirect-url-enabled">
                <label class="cozmoslabs-toggle-track" for="wppb-content-restrict-custom-redirect-url-enabled"></label>
            </div>
            <div class="cozmoslabs-toggle-description">
                <label for="wppb-content-restrict-custom-redirect-url-enabled" class="cozmoslabs-description"><?php echo esc_html( sprintf( __( 'Enable if you wish to add a custom redirect URL for this %s.', 'profile-builder' ), apply_filters( 'wppb_content_restrict_settings_description_cpt', $post->post_type ) ) ); ?></label>
            </div>
        </div>

        <div class="wppb-meta-box-field-wrapper cozmoslabs-form-field-wrapper wppb-meta-box-field-wrapper-custom-redirect-url <?php echo ( ! empty( $wppb_custom_redirect_url_enabled ) ? 'wppb-content-restriction-enabled' : '' ); ?>">
            <?php $wppb_custom_redirect_url = get_post_meta( $post->ID, 'wppb-content-restrict-custom-redirect-url', true ); ?>

            <label class="wppb-meta-box-field-label cozmoslabs-form-field-label" for="wppb-content-restrict-custom-redirect-url"><?php esc_html_e( 'Custom Redirect URL', 'profile-builder' ); ?></label>
            <input type="text" value="<?php echo ( ! empty( $wppb_custom_redirect_url ) ? esc_url( $wppb_custom_redirect_url ) : '' ); ?>" name="wppb-content-restrict-custom-redirect-url" id="wppb-content-restrict-custom-redirect-url" class="widefat">
            <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php echo esc_html( sprintf( __( 'Add a URL where you wish to redirect users that do not have access to this %s and try to access it directly.', 'profile-builder' ), apply_filters( 'wppb_content_restrict_settings_description_cpt', $post->post_type ) ) ); ?></p>
        </div>
    </div>

    <div id="wppb-meta-box-fields-wrapper-restriction-custom-messages" class="wppb-meta-box-fields-wrapper cozmoslabs-form-subsection-wrapper">
        <h4 class="cozmoslabs-subsection-title"><?php echo esc_html__( 'Restriction Messages', 'profile-builder' ); ?></h4>

        <div class="wppb-meta-box-field-wrapper cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
            <?php
            $wppb_custom_messages_enabled = get_post_meta( $post->ID, 'wppb-content-restrict-messages-enabled', true );
            ?>
            <label class="wppb-meta-box-field-label cozmoslabs-form-field-label" for="wppb-content-restrict-messages-enabled"><?php esc_html_e( 'Enable Custom Messages', 'profile-builder' ); ?></label>

            <div class="cozmoslabs-toggle-container">
                <input type="checkbox" value="yes" <?php echo ( ! empty( $wppb_custom_messages_enabled ) ? 'checked="checked"' : '' ); ?> name="wppb-content-restrict-messages-enabled" id="wppb-content-restrict-messages-enabled">
                <label class="cozmoslabs-toggle-track" for="wppb-content-restrict-messages-enabled"></label>
            </div>
            <div class="cozmoslabs-toggle-description">
                <label for="wppb-content-restrict-messages-enabled" class="cozmoslabs-description"><?php echo esc_html( sprintf( __( 'Enable if you wish to add custom messages for this %s.', 'profile-builder' ), apply_filters( 'wppb_content_restrict_settings_description_cpt', $post->post_type ) ) ); ?></label>
            </div>
        </div>

        <div class="wppb-meta-box-field-wrapper-custom-messages <?php echo ( ! empty( $wppb_custom_messages_enabled ) ? 'wppb-content-restriction-enabled' : '' ); ?>">
            <?php do_action( 'wppb_view_meta_box_content_restrict_restriction_messages_top', $post->ID ); ?>

            <div class="cozmoslabs-form-field-wrapper cozmoslabs-wysiwyg-wrapper cozmoslabs-wysiwyg-indented">
                <label class="cozmoslabs-form-field-label"><?php esc_html_e( 'Messages for logged-out users', 'profile-builder' ); ?></label>
                <?php wp_editor( get_post_meta( $post->ID, 'wppb-content-restrict-message-logged_out', true ), 'wppb-content-restrict-message-logged_out', array( 'textarea_name' => 'wppb-content-restrict-message-logged_out', 'editor_height' => 180 ) ); ?>
            </div>

            <div class="cozmoslabs-form-field-wrapper cozmoslabs-wysiwyg-wrapper cozmoslabs-wysiwyg-indented">
                <label class="cozmoslabs-form-field-label"><?php esc_html_e( 'Messages for logged-in users', 'profile-builder' ); ?></label>
                <?php wp_editor( get_post_meta( $post->ID, 'wppb-content-restrict-message-logged_in', true ), 'wppb-content-restrict-message-logged_in', array( 'textarea_name' => 'wppb-content-restrict-message-logged_in', 'editor_height' => 180 ) ); ?>
            </div>

            <?php if( isset( $post->post_type ) && $post->post_type == 'product' ) : ?>

                <div class="cozmoslabs-form-field-wrapper cozmoslabs-wysiwyg-wrapper cozmoslabs-wysiwyg-indented">
                    <label class="cozmoslabs-form-field-label"><?php esc_html_e( 'Messages for restricted product purchase', 'profile-builder' ); ?></label>
                    <?php wp_editor( wp_kses_post( get_post_meta( $post->ID, 'wppb-content-restrict-message-purchasing_restricted', true ) ), 'wppb-content-restrict-message-purchasing_restricted', array( 'textarea_name' => 'wppb-content-restrict-message-purchasing_restricted', 'editor_height' => 180 ) ); ?>
                </div>

            <?php endif; ?>
        </div>
    </div>

    <?php if ( ! defined( 'WPPB_PAID_PLUGIN_DIR' ) || ( defined( 'PROFILE_BUILDER_PAID_VERSION' ) && PROFILE_BUILDER_PAID_VERSION === 'dev' ) ) : ?>

        <div class="cozmoslabs-form-field-wrapper">
            <p class="cozmoslabs-description">
                <?php echo wp_kses_post( sprintf( __( 'Have a question? %1$sOpen a support ticket.%2$s', 'profile-builder' ), '<a href="' . esc_url( 'https://wordpress.org/support/plugin/profile-builder/#new-topic-0' ) . '" target="_blank" rel="noopener">', '</a>' ) ); ?>
            </p>
        </div>

    <?php endif; ?>

    <?php
    wp_nonce_field( 'wppb_meta_box_single_content_restriction_nonce', 'wppb_content_restriction_token', false );

}

function wppb_content_restriction_save_data( $post_id ) {

    if( empty( $_POST['wppb_content_restriction_token'] ) || ! wp_verify_nonce( sanitize_text_field( $_POST['wppb_content_restriction_token'] ), 'wppb_meta_box_single_content_restriction_nonce' ) ) {
        return;
    }

    // Handle restriction rules
    delete_post_meta( $post_id, 'wppb-content-restrict-type' );

    if( ! empty( $_POST['wppb-content-restrict-type'] ) ) {
        update_post_meta( $post_id, 'wppb-content-restrict-type', sanitize_text_field( $_POST['wppb-content-restrict-type'] ) );
    }

    delete_post_meta( $post_id, 'wppb-content-restrict-user-role' );
    if( isset( $_POST['wppb-content-restrict-user-role'] ) && is_array( $_POST['wppb-content-restrict-user-role'] ) ) {
        $user_role_ids = array_map( 'sanitize_text_field', $_POST['wppb-content-restrict-user-role'] );
        foreach( $user_role_ids as $user_role_id ) {
            if( ! empty( $user_role_id ) ) {
                add_post_meta( $post_id, 'wppb-content-restrict-user-role',  $user_role_id );
            }
        }
    }

    if( isset( $_POST['wppb-content-restrict-user-status'] ) && $_POST['wppb-content-restrict-user-status'] == 'loggedin' ) {
        update_post_meta( $post_id, 'wppb-content-restrict-user-status', 'loggedin' );
    } else {
        delete_post_meta( $post_id, 'wppb-content-restrict-user-status' );
    }

    // Handle custom redirect URL
    delete_post_meta( $post_id, 'wppb-content-restrict-custom-redirect-url-enabled' );

    if( isset( $_POST['wppb-content-restrict-custom-redirect-url-enabled'] ) ) {
        update_post_meta( $post_id, 'wppb-content-restrict-custom-redirect-url-enabled', 'yes' );
    }

    update_post_meta( $post_id, 'wppb-content-restrict-custom-redirect-url', ( ! empty( $_POST['wppb-content-restrict-custom-redirect-url'] ) ? esc_url_raw( $_POST['wppb-content-restrict-custom-redirect-url'] ) : '' ) );

    // Handle custom messages
    delete_post_meta( $post_id, 'wppb-content-restrict-messages-enabled' );

    if( isset( $_POST['wppb-content-restrict-messages-enabled'] ) ) {
        update_post_meta( $post_id, 'wppb-content-restrict-messages-enabled', 'yes' );
    }

    update_post_meta( $post_id, 'wppb-content-restrict-message-logged_out', ( ! empty( $_POST['wppb-content-restrict-message-logged_out'] ) ? wp_kses_post( $_POST['wppb-content-restrict-message-logged_out'] ) : '' ) );
    update_post_meta( $post_id, 'wppb-content-restrict-message-logged_in', ( ! empty( $_POST['wppb-content-restrict-message-logged_in'] ) ? wp_kses_post( $_POST['wppb-content-restrict-message-logged_in'] ) : '' ) );

    // Handle WooCommerce purchase settings
    delete_post_meta( $post_id, 'wppb-purchase-restrict-user-role' );
    if( isset( $_POST['wppb-purchase-restrict-user-role'] ) && is_array( $_POST['wppb-purchase-restrict-user-role'] ) ) {
        $restrict_roles = array_map( 'sanitize_text_field', $_POST['wppb-purchase-restrict-user-role'] );
        foreach ( $restrict_roles as $role ) {
            if( !empty( $role ) )
                add_post_meta($post_id, 'wppb-purchase-restrict-user-role', sanitize_text_field( $role ) );
        }

    }

    if( isset( $_POST['wppb-purchase-restrict-user-status'] ) && $_POST['wppb-purchase-restrict-user-status'] === 'loggedin' )
        update_post_meta( $post_id, 'wppb-purchase-restrict-user-status', 'loggedin' );
    else
        delete_post_meta( $post_id, 'wppb-purchase-restrict-user-status' );

    // save custom "product purchase restricted" message
    update_post_meta( $post_id, 'wppb-content-restrict-message-purchasing_restricted', ( ! empty( $_POST['wppb-content-restrict-message-purchasing_restricted'] ) ? wp_kses_post($_POST['wppb-content-restrict-message-purchasing_restricted']) : '' ) );

}
add_action( 'save_post', 'wppb_content_restriction_save_data' );
add_action( 'edit_attachment', 'wppb_content_restriction_save_data' );

function wppb_content_restriction_save_term_data( $term_id, $tt_id = 0, $taxonomy = '' ) {

    if ( empty( $_POST['wppb_content_restriction_token'] ) || ! wp_verify_nonce( sanitize_text_field( $_POST['wppb_content_restriction_token'] ), 'wppb_meta_box_single_content_restriction_nonce' ) ) {
        return;
    }

    $supported_taxonomies = wppb_content_restriction_get_supported_taxonomies();

    if ( ! empty( $taxonomy ) && ! isset( $supported_taxonomies[ $taxonomy ] ) ) {
        return;
    }

    delete_term_meta( $term_id, 'wppb-content-restrict-type' );
    if ( ! empty( $_POST['wppb-content-restrict-type'] ) ) {
        update_term_meta( $term_id, 'wppb-content-restrict-type', sanitize_text_field( $_POST['wppb-content-restrict-type'] ) );
    }

    delete_term_meta( $term_id, 'wppb-content-restrict-user-role' );
    if ( isset( $_POST['wppb-content-restrict-user-role'] ) && is_array( $_POST['wppb-content-restrict-user-role'] ) ) {
        $user_role_ids = array_map( 'sanitize_text_field', $_POST['wppb-content-restrict-user-role'] );
        foreach ( $user_role_ids as $user_role_id ) {
            if ( ! empty( $user_role_id ) ) {
                add_term_meta( $term_id, 'wppb-content-restrict-user-role', $user_role_id );
            }
        }
    }

    if ( isset( $_POST['wppb-content-restrict-user-status'] ) && $_POST['wppb-content-restrict-user-status'] === 'loggedin' ) {
        update_term_meta( $term_id, 'wppb-content-restrict-user-status', 'loggedin' );
    } else {
        delete_term_meta( $term_id, 'wppb-content-restrict-user-status' );
    }

    delete_term_meta( $term_id, 'wppb-content-restrict-custom-redirect-url-enabled' );
    if ( isset( $_POST['wppb-content-restrict-custom-redirect-url-enabled'] ) ) {
        update_term_meta( $term_id, 'wppb-content-restrict-custom-redirect-url-enabled', 'yes' );
    }

    update_term_meta( $term_id, 'wppb-content-restrict-custom-redirect-url', ( ! empty( $_POST['wppb-content-restrict-custom-redirect-url'] ) ? esc_url_raw( $_POST['wppb-content-restrict-custom-redirect-url'] ) : '' ) );

    delete_term_meta( $term_id, 'wppb-content-restrict-messages-enabled' );
    if ( isset( $_POST['wppb-content-restrict-messages-enabled'] ) ) {
        update_term_meta( $term_id, 'wppb-content-restrict-messages-enabled', 'yes' );
    }

    update_term_meta( $term_id, 'wppb-content-restrict-message-logged_out', ( ! empty( $_POST['wppb-content-restrict-message-logged_out'] ) ? wp_kses_post( $_POST['wppb-content-restrict-message-logged_out'] ) : '' ) );
    update_term_meta( $term_id, 'wppb-content-restrict-message-logged_in', ( ! empty( $_POST['wppb-content-restrict-message-logged_in'] ) ? wp_kses_post( $_POST['wppb-content-restrict-message-logged_in'] ) : '' ) );
}
