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
register_block_type( __DIR__ . '/build/login',
    [
        'render_callback' => function( $attributes, $content ) {
            ob_start();
            do_action( 'wppb/login/render_callback', $attributes, $content );
            return ob_get_clean();
        },
    ]
);

add_action(
    'admin_enqueue_scripts',
    function () {
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
        wp_add_inline_script('wppb-login-editor-script', 'window.wppbLoginBlockConfig = ' . json_encode(array(
                'wppb_paid' => defined( 'WPPB_PAID_PLUGIN_DIR' ),
                'is_2fa_active' => wppb_is_2fa_active(),
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
    'wppb/login/render_callback',
    function( $attributes, $content ) {
        if ( isset($attributes['is_preview']) && $attributes['is_preview'] === 'true' ) {
            echo '
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 230 130"
                    style="width: "100%";"
                >
                    <title>Login Block Preview</title>
                    <rect
                        width="36.328499"
                        height="10.030812"
                        x="35.712097"
                        y="82.629547"
                        rx="1.9783683"
                        id="rect4"
                        style="fill:#a0a5aa;stroke-width:1.22376513" />
                    <rect
                        width="32.748241"
                        height="15"
                        x="28.485535"
                        y="27.950914"
                        rx="2.6073439"
                        id="rect6"
                        style="fill:#a0a5aa;stroke-width:0.72212797" />
                    <rect
                        width="6.177"
                        height="6.177"
                        x="28.503256"
                        y="84.55645"
                        rx="3"
                        id="rect38"
                        style="fill:#a0a5aa" />
                    <rect
                        width="49.609749"
                        height="10.030812"
                        x="28.485535"
                        y="53.187717"
                        rx="2.7016351"
                        id="rect4-3"
                        style="fill:#a0a5aa;stroke-width:1.43007195" />
                    <rect
                        width="47.265999"
                        height="10.030812"
                        x="28.485535"
                        y="67.738503"
                        rx="2.5739999"
                        id="rect4-6"
                        style="fill:#a0a5aa;stroke-width:1.39588225" />
                    <rect
                        width="28.646679"
                        height="15"
                        x="28.485535"
                        y="95.820312"
                        rx="2.2807865"
                        id="rect6-7"
                        style="fill:#a0a5aa;stroke-width:0.67539418" />
                    <rect
                        width="120.60584"
                        height="10.030812"
                        x="81.445129"
                        y="53.187717"
                        rx="6.5679221"
                        id="rect4-3-5"
                        style="fill:#a0a5aa;stroke-width:2.22976208" />
                    <rect
                        width="120.60584"
                        height="10.030812"
                        x="81.445129"
                        y="67.738503"
                        rx="6.5679221"
                        id="rect4-3-5-5"
                        style="fill:#a0a5aa;stroke-width:2.22976208" />
                </svg>';
        } else {
            $atts = [
                'redirect_url' => $attributes['redirect_url'] !== '' ? ' redirect_url="' . esc_url( $attributes['redirect_url'] ) . '"' : '',
                'logout_redirect_url' => $attributes['logout_redirect_url'] !== '' ? ' logout_redirect_url="' . esc_url( $attributes['logout_redirect_url'] ) . '"' : '',
                'register_url' => $attributes['register_url'] !== '' ? ' register_url="' . esc_url( $attributes['register_url'] ) . '"' : '',
                'lostpassword_url' => $attributes['lostpassword_url'] !== '' ? ' lostpassword_url="' . esc_url( $attributes['lostpassword_url'] ) . '"' : '',
                'show_2fa_field' => $attributes['auth_field'] ? ' show_2fa_field="yes"' : '',
                'block' => $attributes['is_editor'] ? ' block="true"' : '',
                'ajax' => $attributes['ajax'] ? ' ajax="true"' : '',
            ];
            echo '<div class="wppb-block-container">' . do_shortcode( '[wppb-login' . $atts['redirect_url'] . $atts['logout_redirect_url'] . $atts['register_url'] . $atts['lostpassword_url'] . $atts['show_2fa_field'] . $atts['block'] . $atts['ajax'] . ' ]' ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }
    }
, 10, 2 );
