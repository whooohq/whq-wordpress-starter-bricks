<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Registers the block using the metadata loaded from the `block.json` file.
 * Behind the scenes, it registers also all assets so they can be enqueued
 * through the block editor in the corresponding context.
 *
 * @see https://developer.wordpress.org/reference/functions/register_block_type/
 */
register_block_type( __DIR__ . '/build/edit-profile',
    [
        'render_callback' => function( $attributes, $content ) {
            ob_start();
            do_action( 'wppb/edit_profile/render_callback', $attributes, $content );
            return ob_get_clean();
        },
    ]
);

add_action(
    'admin_enqueue_scripts',
    function () {
        $wppb_module_settings = get_option( 'wppb_module_settings', 'not_found' );

        $registration_form_options[] = [ "label" => __( 'Default' , 'profile-builder' ), "value" => "" ];

        if ( !( ( $wppb_module_settings !== 'not_found' && (
                    !isset( $wppb_module_settings['wppb_multipleEditProfileForms'] ) ||
                    $wppb_module_settings['wppb_multipleEditProfileForms'] !== 'show'
                ) ) ||
            $wppb_module_settings === 'not_found' ) ){
            $args = array(
                'post_type'      => 'wppb-epf-cpt',
                'posts_per_page' => -1
            );

            $the_query = new WP_Query( $args );

            if ( $the_query->have_posts() ) {
                foreach ( $the_query->posts as $post ) {

                    $registration_form_options[] = [ "label" => esc_html( $post->post_title ) , "value" => esc_attr( Wordpress_Creation_Kit_PB::wck_generate_slug( $post->post_title ) ) ];
                }
                wp_reset_postdata();
            }
        }

        $args = array(
            'post_type'         => 'page',
            'posts_per_page'    => -1
        );

        if( function_exists( 'wc_get_page_id' ) )
            $args['exclude'] = wc_get_page_id( 'shop' );

        $all_pages = get_posts( $args );

        $url_options[] = [ "label" => "", "value" => "" ];
        if( !empty( $all_pages ) ) {
            foreach ( $all_pages as $page ) {
                $url_options[] = [ "label" => esc_html( $page->post_title ) , "value" => esc_url( get_page_link( $page->ID ) ) ];
            }
        }

        // Add pre-loaded data for my-namespace/my-block
        wp_add_inline_script('wppb-register-editor-script', 'window.wppbEditProfileBlockConfig = ' . json_encode(array(
                'wppb_paid' => defined( 'WPPB_PAID_PLUGIN_DIR' ),
                'edit_profile_form_options' => $registration_form_options,
                'button' => esc_url( admin_url( 'edit.php?post_type=wppb-epf-cpt' ) ),
                'url_options' => $url_options,
            )), 'before');
    }
);

/**
 * Render: PHP.
 *
 * @param array  $attributes Optional. Block attributes. Default empty array.
 * @param string $content    Optional. Block content. Default empty string.
 */
add_action(
    'wppb/edit_profile/render_callback',
    function( $attributes, $content ) {
        if ( isset($attributes['is_preview']) && $attributes['is_preview'] === 'true' ) {
            echo '
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 230 430"
                    style="width: "100%";"
                >
                    <title>Edit Profile Block Preview</title>
                    <rect
                        width="44.373241"
                        height="15"
                        x="27.955204"
                        y="27.950914"
                        rx="3.5329013"
                        id="rect6"
                        style="fill:#a0a5aa;stroke-width:0.84058326" />
                    <rect
                        width="6.177"
                        height="6.177"
                        x="27.955204"
                        y="388.05646"
                        rx="3"
                        id="rect38"
                        style="fill:#a0a5aa" />
                    <rect
                        width="35.717747"
                        height="15"
                        x="27.955204"
                        y="406.9473"
                        rx="2.8437696"
                        id="rect6-7"
                        style="fill:#a0a5aa;stroke-width:0.75415772" />
                    <rect
                        width="121.18815"
                        height="9.191123"
                        x="80.097206"
                        y="101.45288"
                        rx="6.5996327"
                        id="rect4-3-5"
                        style="fill:#a0a5aa;stroke-width:2.13954115" />
                    <rect
                        width="169.35583"
                        height="4.6558123"
                        x="27.955204"
                        y="56.672096"
                        rx="9.2227373"
                        id="rect4-3-5-9"
                        style="fill:#a0a5aa;stroke-width:1.80013108" />
                    <rect
                        width="22.35239"
                        height="11.730559"
                        x="27.955204"
                        y="79.031174"
                        rx="1.2172608"
                        id="rect4-3-1"
                        style="fill:#a0a5aa;stroke-width:1.03807247" />
                    <rect
                        width="58.980843"
                        height="2.9058123"
                        x="80.097206"
                        y="112.6096"
                        rx="3.2119634"
                        id="rect4-3-5-2"
                        style="fill:#a0a5aa;stroke-width:0.83925813" />
                    <rect
                        width="121.18815"
                        height="9.191123"
                        x="80.097206"
                        y="121.21694"
                        rx="6.5996327"
                        id="rect4-3-5-7"
                        style="fill:#a0a5aa;stroke-width:2.13954115" />
                    <rect
                        width="121.18815"
                        height="9.191123"
                        x="80.097206"
                        y="134.21693"
                        rx="6.5996327"
                        id="rect4-3-5-0"
                        style="fill:#a0a5aa;stroke-width:2.13954115" />
                    <rect
                        width="121.18815"
                        height="9.191123"
                        x="80.097206"
                        y="147.15443"
                        rx="6.5996327"
                        id="rect4-3-5-93"
                        style="fill:#a0a5aa;stroke-width:2.13954115" />
                    <rect
                        width="121.18815"
                        height="9.191123"
                        x="80.097206"
                        y="202.06604"
                        rx="6.5996327"
                        id="rect4-3-5-6"
                        style="fill:#a0a5aa;stroke-width:2.13954115" />
                    <rect
                        width="121.18815"
                        height="9.191123"
                        x="80.097206"
                        y="215.05089"
                        rx="6.5996327"
                        id="rect4-3-5-06"
                        style="fill:#a0a5aa;stroke-width:2.13954115" />
                    <rect
                        width="121.18815"
                        height="47.81683"
                        x="80.097206"
                        y="266.19736"
                        rx="6.5996327"
                        id="rect4-3-5-26"
                        style="fill:#a0a5aa;stroke-width:4.88007784" />
                    <rect
                        width="121.18815"
                        height="9.191123"
                        x="80.097206"
                        y="331.47766"
                        rx="6.5996327"
                        id="rect4-3-5-1"
                        style="fill:#a0a5aa;stroke-width:2.13954115" />
                    <rect
                        width="121.18815"
                        height="9.191123"
                        x="80.097206"
                        y="351.15445"
                        rx="6.5996327"
                        id="rect4-3-5-8"
                        style="fill:#a0a5aa;stroke-width:2.13954115" />
                    <rect
                        width="116.34488"
                        height="2.9058123"
                        x="80.097206"
                        y="316.20871"
                        rx="6.3358793"
                        id="rect4-3-5-2-7"
                        style="fill:#a0a5aa;stroke-width:1.17872834" />
                    <rect
                        width="52.793659"
                        height="2.9058123"
                        x="80.097206"
                        y="322.88547"
                        rx="2.8750234"
                        id="rect4-3-5-2-9"
                        style="fill:#a0a5aa;stroke-width:0.7940191" />
                    <rect
                        width="39.270241"
                        height="2.9058123"
                        x="80.097206"
                        y="342.66321"
                        rx="2.1385686"
                        id="rect4-3-5-2-2"
                        style="fill:#a0a5aa;stroke-width:0.68481278" />
                    <rect
                        width="51.114281"
                        height="2.9058123"
                        x="80.097206"
                        y="362.35513"
                        rx="2.7835681"
                        id="rect4-3-5-2-0"
                        style="fill:#a0a5aa;stroke-width:0.78128809" />
                    <rect
                        width="28.901199"
                        height="9.191123"
                        x="27.955204"
                        y="101.45288"
                        rx="1.573894"
                        id="rect4-3-5-5"
                        style="fill:#a0a5aa;stroke-width:1.04483688" />
                    <rect
                        width="27.651199"
                        height="9.191123"
                        x="27.955204"
                        y="121.21694"
                        rx="1.5058219"
                        id="rect4-3-5-5-9"
                        style="fill:#a0a5aa;stroke-width:1.02199209" />
                    <rect
                        width="26.151199"
                        height="9.191123"
                        x="27.955204"
                        y="134.21693"
                        rx="1.4241353"
                        id="rect4-3-5-5-2"
                        style="fill:#a0a5aa;stroke-width:0.99388552" />
                    <rect
                        width="29.151199"
                        height="9.191123"
                        x="27.955204"
                        y="147.15443"
                        rx="1.5875086"
                        id="rect4-3-5-5-28"
                        style="fill:#a0a5aa;stroke-width:1.04934609" />
                    <rect
                        width="19.901199"
                        height="9.191123"
                        x="27.955204"
                        y="202.06604"
                        rx="1.0837744"
                        id="rect4-3-5-5-97"
                        style="fill:#a0a5aa;stroke-width:0.8670221" />
                    <rect
                        width="20.026199"
                        height="9.191123"
                        x="27.955204"
                        y="215.05089"
                        rx="1.0905817"
                        id="rect4-3-5-5-3"
                        style="fill:#a0a5aa;stroke-width:0.86974072" />
                    <rect
                        width="42.526199"
                        height="9.191123"
                        x="27.955204"
                        y="266.19736"
                        rx="2.3158808"
                        id="rect4-3-5-5-6"
                        style="fill:#a0a5aa;stroke-width:1.26741493" />
                    <rect
                        width="27.526199"
                        height="9.191123"
                        x="27.955204"
                        y="331.47766"
                        rx="1.4990147"
                        id="rect4-3-5-5-1"
                        style="fill:#a0a5aa;stroke-width:1.01967943" />
                    <rect
                        width="45.901199"
                        height="9.191123"
                        x="27.955204"
                        y="351.15445"
                        rx="2.4996758"
                        id="rect4-3-5-5-29"
                        style="fill:#a0a5aa;stroke-width:1.31674767" />
                    <rect
                        width="37.85239"
                        height="11.730559"
                        x="27.955204"
                        y="180.14156"
                        rx="2.0613558"
                        id="rect4-3-1-31"
                        style="fill:#a0a5aa;stroke-width:1.3508662" />
                    <rect
                        width="46.10239"
                        height="11.730559"
                        x="27.955204"
                        y="244.31151"
                        rx="2.5106323"
                        id="rect4-3-1-9"
                        style="fill:#a0a5aa;stroke-width:1.49082756" />
                    <rect
                        width="79.401199"
                        height="9.191123"
                        x="35.0494"
                        y="386.54941"
                        rx="4.3240104"
                        id="rect4-3-5-5-29-4"
                        style="fill:#a0a5aa;stroke-width:1.73182523" />
                </svg>';
        } else {
            $form_name = '';
            if ( array_key_exists( 'form_name', $attributes ) ) {
                $form_name = $attributes['form_name'];
            }
            if ( !$form_name || $form_name === '' ) {
                $atts = [
                    'form_name' => '',
                    'redirect_url' => $attributes['redirect_url'] !== '' ? ' redirect_url="' . esc_url( $attributes['redirect_url'] ) . '"' : '',
                    'ajax' => $attributes['ajax'] ? ' ajax="true"' : '',
                ];
            } else {
                $atts = [
                    'form_name' => ' form_name="' . $form_name . '"',
                    'redirect_url' => '',
                    'ajax' => ' ajax="multiple-register-form"',
                ];
            }
            echo '<div class="wppb-block-container">' . do_shortcode( '[wppb-edit-profile' . $atts['form_name'] . $atts['redirect_url'] . $atts['ajax'] . ' ]' ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }
    }
, 10, 2 );
