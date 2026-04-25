<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/* Hijack the content when restrictions are set on a single post */
function wppb_content_restriction_filter_content( $content, $post = null ) {

    global $user_ID, $wppb_show_content, $pms_is_post_restricted_arr;

    if( is_null( $post ) ) {
        global $post;
    }

	if( empty( $post->ID ) )
		return $content;

    $user_ID = get_current_user_id();

    /*
     * Defining this variable:
     *
     * $wppb_show_content can have 3 states: null, true and false
     *
     * - if the state is "null" the $content is showed, but it did not go through any actual filtering
     * - if the state is "true" the $content is showed, but it did go through filters that explicitly said the $content should be shown
     * - if the state is "false" the $content is not showed, it is replaced with a restriction message, thus it explicitly says that it was filtered and access is denied to it
     *
     */
    $wppb_show_content = null;

    // Show for administrators
    if( current_user_can( 'manage_options' ) ) {
        return $content;
    }

    // Check if any PMS restriction should take place. PMS restrictions have priority
    if( isset( $pms_is_post_restricted_arr[$post->ID] ) && $pms_is_post_restricted_arr[$post->ID] === true ) {
        return $content;
    }

    $restricted_term = wppb_content_restriction_get_restricted_term_for_post( $post->ID );

    if ( $restricted_term ) {
        $term_user_status = get_term_meta( $restricted_term->term_id, 'wppb-content-restrict-user-status', true );
        $term_user_roles  = get_term_meta( $restricted_term->term_id, 'wppb-content-restrict-user-role' );

        if ( ! wppb_content_restriction_check_user_access( $term_user_status, $term_user_roles, $user_ID ) ) {
            $wppb_show_content = false;

            if ( is_user_logged_in() ) {
                $message = wppb_content_restriction_process_term_content_message( 'logged_in', $user_ID, $restricted_term->term_id );

                return do_shortcode( apply_filters( 'wppb_content_restriction_message_logged_in', $message, $content, $post, $user_ID ) );
            }

            $message = wppb_content_restriction_process_term_content_message( 'logged_out', $user_ID, $restricted_term->term_id );

            return do_shortcode( apply_filters( 'wppb_content_restriction_message_logged_out', $message, $content, $post, $user_ID ) );
        }
    }

    // Get user roles that have access to this post
    if ( isset( $post ) && isset( $post->ID ) ) {
        $user_status = get_post_meta($post->ID, 'wppb-content-restrict-user-status', true);
        $post_user_roles = get_post_meta($post->ID, 'wppb-content-restrict-user-role');

        /**
         * Filter for disabling/enabling User Role based content restriction for Logged-Out users.
         * By default, restrictions apply even if logged in users is not selected. 
         * If this filter is set to false, restrictions will apply only if the logged in users option is selected alongside user roles.
         *
         * @param bool $user_roles_without_logged_in_option -> Defaults to TRUE.
         *
         */
        $user_roles_without_logged_in_option = apply_filters( 'wppb_content_restriction_enable_user_roles_without_logged_in_option', true );
    }

    if( empty( $user_status ) && empty( $post_user_roles ) ) {
		$wppb_show_content = true;
        return $content;
    } else if( $user_status == 'loggedin' || ( $user_roles_without_logged_in_option && !empty( $post_user_roles ) ) ) {
        if( is_user_logged_in() ) {
            if( ! empty( $post_user_roles ) ) {
                $user_data = get_userdata( $user_ID );

                foreach( $post_user_roles as $post_user_role ) {
                    foreach( $user_data->roles as $role ) {
                        if( $post_user_role == $role ) {
                            $wppb_show_content = true;
                            return $content;
                        }
                    }
                }

                $wppb_show_content = false;

                $message = wppb_content_restriction_process_content_message( 'logged_in', $user_ID, $post->ID );

                return do_shortcode( apply_filters( 'wppb_content_restriction_message_logged_in', $message, $content, $post, $user_ID ) );
            } else {
                return $content;
            }
        } else {
            // If user is not logged in prompt the correct message
            $wppb_show_content = false;

            $message = wppb_content_restriction_process_content_message( 'logged_out', $user_ID, $post->ID );

            return do_shortcode( apply_filters( 'wppb_content_restriction_message_logged_out', $message, $content, $post, $user_ID ) );
        }
    }

    return $content;

}
add_filter( 'the_content', 'wppb_content_restriction_filter_content', 12, 2 );
add_filter( 'wppb_content_restriction_post_check', 'wppb_content_restriction_filter_content', 10, 2 );

/**
 * Function that checks if a post id is restricted with profile builder
 * @param $post_id
 * @return bool true for when the post is restricted and false for when it's not
 */
function wppb_check_content_restriction_on_post_id( $post_id ){
    global $user_ID;

    // Get user roles that have access to this post
    $user_status        = get_post_meta( $post_id, 'wppb-content-restrict-user-status', true );
    $post_user_roles    = get_post_meta( $post_id, 'wppb-content-restrict-user-role' );

    /**
     * Filter for disabling/enabling User Role based content restriction for Logged-Out users.
     * By default, restrictions apply even if logged in users is not selected. 
     * If this filter is set to false, restrictions will apply only if the logged in users option is selected alongside user roles.
     *
     * @param bool $user_roles_without_logged_in_option -> Defaults to TRUE.
     *
     */
    $user_roles_without_logged_in_option = apply_filters( 'wppb_content_restriction_enable_user_roles_without_logged_in_option', true );

    if( empty( $user_status ) && empty( $post_user_roles ) ) {
        return false;
    } else if( $user_status == 'loggedin' || ( $user_roles_without_logged_in_option && !empty( $post_user_roles ) ) ) {
        if( is_user_logged_in() ) {
            if( ! empty( $post_user_roles ) ) {
                $user_data = get_userdata( $user_ID );
                foreach( $post_user_roles as $post_user_role ) {
                    foreach( $user_data->roles as $role ) {
                        if( $post_user_role == $role ) {
                            return false;
                        }
                    }
                }
                return true;
            } else {
                return false;
            }
        } else {
            return true;
        }
    }

    return false;
}


/* Checks to see if the attachment image is restricted and returns false instead of the image if it is restricted */
function wppb_content_restriction_filter_attachment_image_src( $image, $attachment_id ) {

    if( is_admin() ) {
        return $image;
    }

    if( wppb_content_restriction_is_post_restricted( $attachment_id ) ) {
        return false;
    }

    return $image;

}
add_filter( 'wp_get_attachment_image_src', 'wppb_content_restriction_filter_attachment_image_src', 10, 2 );

/* Checks to see if the attachment is restricted and returns false instead of the metadata if it is restricted */
function wppb_content_restriction_filter_attachment_metadata( $data, $attachment_id ) {

    if( is_admin() ) {
        return $data;
    }

    if( wppb_content_restriction_is_post_restricted( $attachment_id ) ) {
        return false;
    }

    return $data;

}
add_filter( 'wp_get_attachment_metadata', 'wppb_content_restriction_filter_attachment_metadata', 10, 2 );

/* Checks to see if the attachment thumb is restricted and returns false instead of the thumb url if it is restricted */
function wppb_content_restriction_filter_attachment_thumb_url( $url, $attachment_id ) {

    if( is_admin() ) {
        return $url;
    }

    if( wppb_content_restriction_is_post_restricted( $attachment_id ) ) {
        return false;
    }

    return $url;

}
add_filter( 'wp_get_attachment_thumb_url', 'wppb_content_restriction_filter_attachment_thumb_url', 10, 2 );

/* Checks to see if the attachment is restricted and returns an empty string instead of the attachment url if it is restricted*/
function wppb_content_restriction_filter_attachment_url( $url, $attachment_id ) {

    if( is_admin() ) {
        return $url;
    }

    if( wppb_content_restriction_is_post_restricted( $attachment_id ) ) {
        return '';
    }

    return $url;

}
add_filter( 'wp_get_attachment_url', 'wppb_content_restriction_filter_attachment_url', 10, 2 );
add_filter( 'attachment_link', 'wppb_content_restriction_filter_attachment_url', 10, 2 );

/* Formats the error messages to display accordingly to the WYSIWYG editor */
function wppb_content_restriction_message_wpautop( $message = '' ) {

    if( ! empty( $message ) ) {
        $message = wpautop( $message );
    }

    return apply_filters( 'wppb_content_restriction_message_wpautop', $message );

}
add_filter( 'wppb_content_restriction_message_logged_in', 'wppb_content_restriction_message_wpautop', 30, 1 );
add_filter( 'wppb_content_restriction_message_logged_out', 'wppb_content_restriction_message_wpautop', 30, 1 );

/* Adds a preview of the restricted post before the default restriction messages */
function wppb_content_restriction_add_post_preview( $message, $content, $post, $user_ID ) {

    $preview        = '';
    $settings       = get_option( 'wppb_content_restriction_settings' );
    $preview_option = ( ! empty( $settings['post_preview'] ) ? $settings['post_preview'] : '' );

    if( empty( $preview_option ) || $preview_option == 'none' ) {
        return $message;
    }

    $post_content = $content;

    // Trim the content
    if( $preview_option == 'trim-content' ) {
        $length = ( ! empty( $settings['post_preview_length'] ) ? (int) $settings['post_preview_length'] : 0 );

        if( $length !== 0 ) {
            // Do shortcodes on the content
            $post_content = do_shortcode( $post_content );

            // Trim the preview
            $preview = wp_trim_words( $post_content, $length, apply_filters( 'wppb_content_restriction_post_preview_more', __( '&hellip;', 'profile-builder' ) ) );
        }
    }

    // More tag
    if( $preview_option == 'more-tag' ) {
        $content_parts = get_extended( $post->post_content );

        if( ! empty( $content_parts['extended'] ) ) {
            $preview = $content_parts['main'];
        }
    }

    // Return the preview
    return wpautop( $preview ) . $message;

}
add_filter( 'wppb_content_restriction_message_logged_in', 'wppb_content_restriction_add_post_preview', 30, 4 );
add_filter( 'wppb_content_restriction_message_logged_out', 'wppb_content_restriction_add_post_preview', 30, 4 );

/* if the Static Posts Page has a restriction on it hijack the query */
add_action( 'template_redirect', 'wppb_content_restriction_posts_page_handle_query', 1 );
function wppb_content_restriction_posts_page_handle_query(){
    if( is_home() ){
        $posts_page_id = get_option( 'page_for_posts' );
        if( $posts_page_id ) {
            if (wppb_check_content_restriction_on_post_id($posts_page_id)) {
                wppb_content_restriction_force_page($posts_page_id);
            }
        }
    }
}


/* if the Static Posts Page has a restriction on it hijack the template back to the Page Template */
add_filter( 'template_include', 'wppb_content_restriction_posts_page_template', 100 );
function wppb_content_restriction_posts_page_template( $template ){
    if( is_home() ){
        $posts_page_id = get_option( 'page_for_posts' );
        if( $posts_page_id ) {
            if (wppb_check_content_restriction_on_post_id($posts_page_id)) {
                $template = get_page_template();
            }
        }
    }
    return $template;
}

/* Change the query to a single post */
function wppb_content_restriction_force_page( $posts_page_id ){
    if( $posts_page_id ) {
        global $wp_query, $post;
        $post = get_post($posts_page_id);
        $wp_query->posts = array($post);
        $wp_query->post_count = 1;
        $wp_query->is_singular = true;
        $wp_query->is_singule = true;
        $wp_query->is_archive = false;
    }
}

// add callback to function that hides comments if post content is restricted
function wppb_comments_hide_callback_function( $args ) {
    global $post;

    if ( empty( $post->ID ) ) return $args;

    if( wppb_content_restriction_is_post_restricted( $post->ID ) )
        $args[ 'callback' ] = 'wppb_comments_restrict_view';

    return $args;
}

// display restriction message if post content is restricted
function wppb_comments_restrict_view( $comment, $args, $depth ) {
    static $message_shown = false;

    if ( !$message_shown ) {

        if ( is_user_logged_in() )
            printf( '<p>%s</p>', esc_html( apply_filters( 'wppb_comments_restriction_message_user_role', __( 'Comments are restricted for your user role.', 'profile-builder' ) ) ) );
        else
            printf( '<p>%s</p>', esc_html( apply_filters( 'wppb_comments_restriction_message_logged_out', __( 'You must be logged in to view the comments.', 'profile-builder' ) ) ) );

        $message_shown = true;
    }
}

// restrict replying for restricted posts
function wppb_comments_restrict_replying( $open, $post_id ) {
    // Show for administrators
    if( current_user_can( 'manage_options' ) && is_admin() )
        return $open;

    if( wppb_content_restriction_is_post_restricted( $post_id ) )
        return false;

    return $open;
}

$wppb_cr_settings = get_option( 'wppb_content_restriction_settings' );

// add filter to hide comments and replies if post content is restricted
if ( isset( $wppb_cr_settings[ 'contentRestriction' ] ) && $wppb_cr_settings[ 'contentRestriction' ] == 'yes' && apply_filters( 'wppb_enable_comment_restriction', true ) ) {
    add_filter( 'comments_open', 'wppb_comments_restrict_replying', 20, 2 );
    add_filter( 'wp_list_comments_args', 'wppb_comments_hide_callback_function', 999 );
}

if( !function_exists( 'pms_exclude_restricted_comments' ) ){
    add_filter( 'the_comments', 'wppb_exclude_restricted_comments', 10, 2 );
    function wppb_exclude_restricted_comments( $comments, $query ){
        if( !empty( $comments ) && !current_user_can( 'manage_options' ) ){
            $user_id = get_current_user_id();
            foreach ( $comments as $key => $comment ){
                $post = get_post( $comment->comment_post_ID );
                if( ( $post->post_type == 'private-page' && $user_id != (int)$post->post_author ) || ( function_exists( 'wppb_content_restriction_is_post_restricted' ) && wppb_content_restriction_is_post_restricted( $comment->comment_post_ID ) ) || ( function_exists( 'pms_is_post_restricted' ) && pms_is_post_restricted( $comment->comment_post_ID ) ) ){
                    unset( $comments[$key] );
                }
            }
        }
        return $comments;
    }
}


/**
 * Clean the LearnDash post type slug before output
 * - displayed in Content Restriction Meta-box settings description
 */
function wppb_ld_handle_cr_settings_description_cpt( $post_type ) {

    if ( substr( $post_type, 0, 5 ) === "sfwd-" ) {
        $post_type = substr( $post_type, 5 );

        if ( substr( $post_type, -1 ) === "s" )
            $post_type = substr( $post_type, 0, -1 );

    }

    return $post_type;
}
add_filter( 'wppb_content_restrict_settings_description_cpt', 'wppb_ld_handle_cr_settings_description_cpt', 20 );


/**
 * WooCommerce specific filters
 */
if( function_exists( 'wc_get_page_id' ) ) {

    /**
     * Function that restricts product content
     *
     * @param $output What is returned
     * @return string
     */
    function wppb_woo_restrict_product_content( $output ){
        global $post, $user_ID;

        if ( strpos( $post->post_password, 'wppb_woo_product_restricted_' ) !== false ) {

            // user does not have access, filter the content
            $output = '';

            // check if restricted post preview is set
            $settings       = get_option( 'wppb_content_restriction_settings' );
            $preview_option = ( !empty( $settings['restricted_post_preview']['option'] ) ? $settings['restricted_post_preview']['option'] : '' );

            if ( !empty($preview_option) && ($preview_option != 'none') ) {
                // display product title

                ob_start();

                echo '<div class="summary entry-summary">';
                wc_get_template( 'single-product/title.php' );
                echo '</div>';

                $output = ob_get_clean();
            }

            if( !is_user_logged_in() )
                $message = wppb_content_restriction_process_content_message( 'logged_out', $user_ID, $post->ID );
            else if( !wppb_woo_is_product_purchasable() && !wppb_content_restriction_is_post_restricted() )
                $message = wppb_content_restriction_process_content_message( 'purchasing_restricted', $user_ID, $post->ID );
            else 
                $message = wppb_content_restriction_process_content_message( 'logged_in', $user_ID, $post->ID );
                
            $message = '<div class="woocommerce"><div class="woocommerce-info wppb-woo-restriction-message wppb-woo-restricted-product-purchasing-message">' . $message . '</div></div>';

            $output .= do_shortcode( $message );

            $post->post_password = null;

        }

        return $output;
    }

    /**
     * Function that checks if current user can purchase the product
     *
     * @param string|WC_Product $product The product
     * @return bool
     */
    function wppb_woo_is_product_purchasable( $product = '' ){
        global $user_ID, $post;

        if ( empty($product) )
            $product = wc_get_product( $post->ID );

        if( false == $product )
            return false;

        /**
         * Show "buy now" for the `manage_options` and `pms_bypass_content_restriction` capabilities
         */
        if( current_user_can( 'manage_options' ) )
            return true;

        // if is variation, use the id of the parent product
        if ( $product->is_type( 'variation' ) )
            $product_id = $product->get_parent_id();
        else
            $product_id = $product->get_id();

        // Get subscription plans that can purchase this product
        $user_status        = get_post_meta( $product_id, 'wppb-purchase-restrict-user-status', true );
        $product_user_roles = get_post_meta( $product_id, 'wppb-purchase-restrict-user-role' );

        if( empty( $user_status ) && empty( $product_user_roles ) ) {
            //everyone can purchase
            return true;

        } else if( !empty( $product_user_roles ) && is_user_logged_in() ) {

            $user_data = get_userdata( $user_ID );
            foreach( $product_user_roles as $product_user_role ) {
                foreach( $user_data->roles as $role ) {
                    if( $product_user_role == $role ) {
                        return true;
                    }
                }
            }

            return false;

        } else if ( !is_user_logged_in() && (
                    ( !empty( $user_status ) && $user_status == 'loggedin' ) || ( !empty( $product_user_roles ) )
                ) ) {

            return false;
        }

        return true;
    }

    /**
     * Restrict the Shop page
     *
     * @param $template The shop page template to return
     * @return string
     */
    function wppb_woo_restrict_shop_page($template){

        // check if we're on the Shop page (set under WooCommerce Settings -> Products -> Display)
        if (is_post_type_archive('product') || is_page(wc_get_page_id('shop'))) {

            // get the ID of the shop page
            $post_id = wc_get_page_id('shop');

            if (($post_id != -1) && wppb_check_content_restriction_on_post_id($post_id)) {

                $shop_page = get_post($post_id);

                setup_postdata($shop_page);

                $template = WPPB_PLUGIN_DIR . 'features/content-restriction/templates/archive-product.php';

                wp_reset_postdata();
            }

        }

        return $template;
    }
    add_filter( 'template_include', 'wppb_woo_restrict_shop_page', 40 );

    /**
     * Restrict single product view by marking restricted products as password-protected
     *
     * - Runs on 'template_redirect' so it executes early enough for both classic and block themes
     * - Applies only to 'message' restriction type. Redirect mode is handled separately
     */
    function wppb_woo_maybe_password_protect_product(){
        global $post;

        if ( ! is_singular( 'product' ) || empty( $post ) || empty( $post->ID ) )
            return;

        $post_restriction_type = get_post_meta( $post->ID, 'wppb-content-restrict-type', true );
        $settings              = get_option( 'wppb_content_restriction_settings', array() );

        if ( $post_restriction_type == 'default' || empty( $post_restriction_type ) )
            $post_restriction_type = ( ! empty( $settings['restrict_type'] ) ? $settings['restrict_type'] : 'message' );

        if ( $post_restriction_type !== 'message' )
            return;

        // if the product is restricted and doesn't already require a password, set a temporary password before Woo renders product templates
        if ( wppb_content_restriction_is_post_restricted( $post->ID ) && ! post_password_required() ) {
            $post->post_password = uniqid( 'wppb_woo_product_restricted_' );

            if ( ! has_filter( 'the_password_form', 'wppb_woo_restrict_product_content' ) )
                add_filter( 'the_password_form', 'wppb_woo_restrict_product_content' );
        }
    }
    add_action( 'template_redirect', 'wppb_woo_maybe_password_protect_product', 0 );


    /**
     * Function that hides the price for view-restricted products
     *
     * @param float $price The product price
     * @param WC_Product $product The product
     * @return string
     */
    function wppb_woo_hide_restricted_product_price( $price, WC_Product $product ){
        // check if current user can view this product, and if not, remove the price
        if ( wppb_content_restriction_is_post_restricted( $product->get_id() ) ) {

            $price = '';
        }

        return $price;
    }
    add_filter( 'woocommerce_get_price_html', 'wppb_woo_hide_restricted_product_price', 9, 2);


    /**
     * Function that hides the product image thumbnail for view-restricted products
     *
     */
    function wppb_woo_maybe_remove_product_thumbnail(){
        global $post, $wppb_woo_product_thumbnail_restricted;

        $wppb_woo_product_thumbnail_restricted = false;

        // skip if the product thumbnail is not shown anyway
        if ( ! has_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail' ) ) {
            return;
        }

        // if product is view restricted, do not display the product thumbnail
        if ( wppb_content_restriction_is_post_restricted($post->ID) ) {

            // indicate that we removed the product thumbnail
            $wppb_woo_product_thumbnail_restricted = true;

            // remove the product thumbnail and replace it with the placeholder image
            remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail' );
            add_action( 'woocommerce_before_shop_loop_item_title', 'wppb_woo_template_loop_product_thumbnail_placeholder', 10 );
        }

    }
    add_action( 'woocommerce_before_shop_loop_item_title', 'wppb_woo_maybe_remove_product_thumbnail', 5 );


    // return placeholder thumbnail instead of image for view-restricted products
    function wppb_woo_template_loop_product_thumbnail_placeholder(){
        if ( wc_placeholder_img_src() ) {

            echo wp_kses_post( wc_placeholder_img( 'shop_catalog' ) );
        }
    }

    // restore product thumbnail for the next product in the loop
    function wppb_woo_restore_product_thumbnail(){
        global $wppb_woo_product_thumbnail_restricted;

        if (  $wppb_woo_product_thumbnail_restricted
            && ! has_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail' ) ) {

            add_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail', 10 );
            remove_action( 'woocommerce_before_shop_loop_item_title', 'wppb_woo_template_loop_product_thumbnail_placeholder' );
        }
    }
    add_action( 'woocommerce_after_shop_loop_item_title', 'wppb_woo_restore_product_thumbnail', 5 );


    /**
     * Function that restricts product purchasing
     *
     * @param boolean $purchasable Whether the product is purchasable or not
     * @param $product The product
     * @return bool
     */
    function wppb_woo_product_is_purchasable( $purchasable, $product ){

        // if the product is a variation, we need to grab the parent product before checking if it is purchasable
        if( $product->is_type( array( 'variation' ) ) ){
            $product = wc_get_product( $product->get_parent_id() );
        }

        if ( wppb_content_restriction_is_post_restricted( $product->get_id() ) || !wppb_woo_is_product_purchasable( $product ) )
            $purchasable = false;

        // double-check for variations; if parent is not purchasable, then neither should be the variation
        // NOTE: This was removed because it doesn't make sense for varations. We need to check if the product that we receive is a variation and go directly
        // to the parent product to check settings
        // if ( $purchasable && $product->is_type( array( 'variation' ) ) ) {

        //     $parent = wc_get_product( $product->get_parent_id() );

        //     if( !empty( $parent ) && is_object( $parent ) )
        //         $purchasable = $parent->is_purchasable();

        // }

        return $purchasable;

    }
    add_filter( 'woocommerce_is_purchasable', 'wppb_woo_product_is_purchasable', 10, 2 );
    add_filter( 'woocommerce_variation_is_purchasable', 'wppb_woo_product_is_purchasable', 10, 2 );

    /**
     * Function that shows the product purchasing restricted message
     *
     **/
    function wppb_woo_single_product_purchasing_restricted_message(){
        global $wppb_show_content, $post;

        if( empty( $post->ID ) )
            return;

        // View restrictions already render their own message
        if( wppb_content_restriction_is_post_restricted( $post->ID ) ) {
            return;
        }

        if ( !wppb_woo_is_product_purchasable() ) {

            // product purchasing is restricted
            $wppb_show_content = false;

            if( is_user_logged_in() )
                $message = wppb_content_restriction_process_content_message( 'purchasing_restricted', get_current_user_id(), $post->ID );
            else 
                $message = wppb_content_restriction_process_content_message( 'logged_out', get_current_user_id(), $post->ID );

            echo wp_kses_post( $message );
        }
    }
    add_action( 'woocommerce_single_product_summary', 'wppb_woo_single_product_purchasing_restricted_message', 30 );

    // Apply wpautop() to "purchasing restricted" messages as well
    add_filter( 'wppb_content_restriction_message_purchasing_restricted', 'wppb_content_restriction_message_wpautop', 30, 1 );

}

/**
 * Exclude Restricted Posts From Query
 */
 if( isset( $wppb_cr_settings['excludePosts'] ) && $wppb_cr_settings['excludePosts'] == 'yes' ) {
	 add_action( 'pre_get_posts', 'wppb_exclude_post_from_query', 40 );
	 function wppb_exclude_post_from_query( $query ) {

		if( !function_exists( 'wppb_content_restriction_is_post_restricted' ) || is_admin() || is_singular() )
			 return;

        if( isset( $query->query_vars['wc_query'] ) && $query->query_vars['wc_query'] == 'product_query' )
            return;

         // Skip Ultimate Member queries
         if( isset( $query->query_vars['um_action'] ) || isset( $query->query_vars['um_user'] ) ) 
            return;

		 if( $query->is_main_query() || ( $query->is_search() && isset( $query->query_vars['s'] ) ) ) {

			 remove_action( 'pre_get_posts', 'wppb_exclude_post_from_query', 40 );

			 $args = $query->query_vars;
			 $args['suppress_filters'] = true;
			 $args['fields']           = 'ids';

			 // Setup paged arguments so we exclude restricted posts from the current page, not only the first N posts.
			 if( !empty( $query->query_vars['paged'] ) ){
				 $args['paged'] = round( $args['paged'] / 2 );
				 if( !empty( $args['posts_per_page'] ) )
					 $args['posts_per_page'] = $args['posts_per_page'] * 2;
				 else
					 $args['posts_per_page'] = get_option( 'posts_per_page' ) * 2;
			 }

			 if( is_search() )
				 $args['post_type'] = 'any';

			 $restricted_posts = get_posts( $args );

			 $previous_restricted_ids = $query->get( 'post__not_in' );
			 if ( ! is_array( $previous_restricted_ids ) ) {
				 $previous_restricted_ids = array();
			 }

			 $restricted_ids = array_filter( $restricted_posts, 'wppb_content_restriction_is_post_restricted' );
			 $query->set( 'post__not_in', array_merge( $previous_restricted_ids, $restricted_ids ) );

		 }
	 }

	 //Remove restricted forums from the main bbPress query
	 add_filter( 'bbp_after_has_forums_parse_args', 'wppb_exclude_restricted_forums_from_main_query' );
	 function wppb_exclude_restricted_forums_from_main_query( $vars ) {
		 if( !function_exists( 'wppb_content_restriction_is_post_restricted' ) ) return;

		 $query = new WP_Query( $vars );

		 $forum_ids = array();

		 foreach ( $query->posts as $forum ) {
			 $forum_ids[] = $forum->ID;
		 }

		 $previous_restricted_forums = $query->get( 'post__not_in' );
		 if ( ! is_array( $previous_restricted_forums ) ) {
			 $previous_restricted_forums = array();
		 }

		 $restricted_forums = array_filter( $forum_ids, 'wppb_content_restriction_is_post_restricted' );
		 $updated_restricted_forums = array_merge( $previous_restricted_forums, $restricted_forums );

		 $vars['post__not_in'] = $updated_restricted_forums;

		 return $vars;
	 }

	 //Alters the output of the query that displays the Category Archive (including Shop) pages, filtering the restricted products from the output
	 add_action( 'pre_get_posts', 'wppb_exclude_restricted_products_from_woocoommerce_category_queries', 11 );
	 function wppb_exclude_restricted_products_from_woocoommerce_category_queries( $query ) {

		 if( is_admin() || !function_exists('wppb_content_restriction_is_post_restricted') )
			 return;

		 if( isset( $query->query_vars['wc_query'] ) && $query->query_vars['wc_query'] == 'product_query' ){

			 remove_action( 'pre_get_posts', 'wppb_exclude_restricted_products_from_woocoommerce_category_queries', 11 );

			 $args = $query->query_vars;
			 $args['suppress_filters'] = true;
			 $args['posts_per_page'] = -1;

			 $previous_restricted_ids = $query->get( 'post__not_in' );
			 if ( ! is_array( $previous_restricted_ids ) ) {
				 $previous_restricted_ids = array();
			 }

			 $transient_key = 'wppb_cr_products_' . md5( serialize( $args ) );
			 if ( false === ( $products = get_transient( $transient_key ) ) ) {
				 $products = wc_get_products( $args );
				 set_transient( $transient_key, $products, 2 * HOUR_IN_SECONDS );
			 }

			 $product_ids = array();

			 foreach ($products as $product) {
				 $product_ids[] = $product->get_id();
			 }

			 $restricted_ids = array_filter( $product_ids, 'wppb_content_restriction_is_post_restricted' );

			 $updated_restricted_ids = array_merge( $previous_restricted_ids, $restricted_ids );
			 $query->set( 'post__not_in', $updated_restricted_ids );

		 }

	 }

	 //Alters the output of the [products] shortcode from WooCommerce, filtering restricted products from the output
	 add_filter( 'woocommerce_shortcode_products_query', 'wppb_exclude_restricted_products_from_woocoommerce_products_shortcode_queries' );
	 function wppb_exclude_restricted_products_from_woocoommerce_products_shortcode_queries( $query_args ) {
		 if( !is_admin() && function_exists('wppb_content_restriction_is_post_restricted') ) {
			 $posts_per_page = $query_args['posts_per_page'];

			 $query_args['suppress_filters'] = true;
			 $query_args['posts_per_page'] = '-1';

			 $products = wc_get_products($query_args);

			 $query_args['posts_per_page'] = $posts_per_page;

			 $product_ids = array();

			 foreach ($products as $product) {
				 $product_ids[] = $product->get_id();
			 }

			 $previous_restricted_ids = isset($query_args['post__not_in']) ? $query_args['post__not_in'] : array();

			 if ( ! is_array( $previous_restricted_ids ) ) {
				 $previous_restricted_ids = array();
			 }

			 $restricted_ids = array_filter( $product_ids, 'wppb_content_restriction_is_post_restricted' );
			 $updated_restricted_ids = array_merge( $previous_restricted_ids, $restricted_ids );

			 $query_args['post__not_in'] = $updated_restricted_ids;
		 }

		 return $query_args;
	 }

	 // Exclude restricted posts from Elementor
	 add_filter( 'elementor/query/query_args', 'wppb_exclude_posts_from_elementor' );
	 function wppb_exclude_posts_from_elementor( $query_args ) {

		 // check if the form is being displayed in the Elementor editor
		 $is_elementor_edit_mode = false;
		 if( class_exists ( '\Elementor\Plugin' ) ){
			 $is_elementor_edit_mode = \Elementor\Plugin::$instance->editor->is_edit_mode();
		 }

		 if ( !$is_elementor_edit_mode && !is_admin() && function_exists( 'wppb_content_restriction_is_post_restricted' ) ) {
			 $args = $query_args;
			 $args['suppress_filters'] = true;
			 $args['fields']           = 'ids';

			 if( !empty( $query_args['paged'] ) ){

				 $args['paged'] = round( $args['paged'] / 2 );

				 if( !empty( $args['posts_per_page'] ) )
					 $args['posts_per_page'] = $args['posts_per_page'] * 2;
				 else
					 $args['posts_per_page'] = get_option( 'posts_per_page' ) * 2;

			 }

			 if( is_search() )
				 $args['post_type'] = 'any';

			 $restricted_posts = get_posts( $args );

			 $restricted_ids = array_filter( $restricted_posts, 'wppb_content_restriction_is_post_restricted' );

			 if ( ! empty( $restricted_ids ) ) {
				 if ( isset( $query_args['post__not_in'] ) && is_array( $query_args['post__not_in'] ) ) {
					 $query_args['post__not_in'] = array_merge( $query_args['post__not_in'], $restricted_ids );
				 } else {
					 $query_args['post__not_in'] = $restricted_ids;
				 }
			 }
		 }
		 return $query_args;
	 }
 }
