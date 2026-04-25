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
register_block_type( __DIR__ . '/build/recover-password',
    [
        'render_callback' => function( $attributes, $content ) {
            ob_start();
            do_action( 'wppb/recover_password/render_callback', $attributes, $content );
            return ob_get_clean();
        },
    ]
);

add_action(
    'admin_enqueue_scripts',
    function () {
        // Add pre-loaded data for my-namespace/my-block
        wp_add_inline_script('wppb-recover-password-editor-script', 'window.wppbRecoverPasswordBlockConfig = ' . json_encode(array(
                'wppb_paid' => defined( 'WPPB_PAID_PLUGIN_DIR' ),
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
    'wppb/recover_password/render_callback',
    function( $attributes, $content ) {
        if ( isset($attributes['is_preview']) && $attributes['is_preview'] === 'true' ) {
            echo '
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 230 130"
                    style="width: "100%";"
                >
                    <title>Recover Password Block Preview</title>
                    <rect
                        width="100.80727"
                        height="15"
                        x="28.485535"
                        y="27.950914"
                        rx="8.0260563"
                        id="rect6"
                        style="fill:#a0a5aa;stroke-width:1.26696932" />
                    <rect
                        width="146.85718"
                        height="4.0700259"
                        x="28.485535"
                        y="64.117226"
                        rx="7.9975109"
                        id="rect4-3-5-7"
                        style="fill:#a0a5aa;stroke-width:1.56730127" />
                    <rect
                        width="50.390999"
                        height="10.030812"
                        x="28.485535"
                        y="78.605385"
                        rx="2.7441804"
                        id="rect4-6"
                        style="fill:#a0a5aa;stroke-width:1.44128823" />
                    <rect
                        width="57.461281"
                        height="15"
                        x="28.485535"
                        y="100.59328"
                        rx="4.5749426"
                        id="rect6-7"
                        style="fill:#a0a5aa;stroke-width:0.95655036" />
                    <rect
                        width="115.16996"
                        height="4.0700259"
                        x="28.485535"
                        y="55.627056"
                        rx="6.2718964"
                        id="rect4-3-5"
                        style="fill:#a0a5aa;stroke-width:1.38795221" />
                    <rect
                        width="120.38487"
                        height="10.030812"
                        x="80.340279"
                        y="78.605385"
                        rx="6.5558887"
                        id="rect4-3-5-5"
                        style="fill:#a0a5aa;stroke-width:2.22771859" />
                </svg>';
        } else {
            $atts = [
                'block' => $attributes['is_editor'] ? ' block="true"' : '',
                'ajax' => $attributes['ajax'] ? ' ajax="true"' : '',
            ];
            echo '<div class="wppb-block-container">' . do_shortcode( '[wppb-recover-password' . $atts['block'] . $atts['ajax'] . ' ]' ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }
    }
, 10, 2 );
