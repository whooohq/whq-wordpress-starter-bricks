<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

function wppb_render_blocks( $block_content, $block ) {
    $block_attrs = isset( $block['attrs']['wppbContentRestriction'] ) ? $block['attrs']['wppbContentRestriction'] : null;

    // Abort if:
    // the block does not have the content restriction settings attribute or
    // the block is to be displayed to all users or
    // the current block is the Content Restriction Start block
    if ( !isset( $block_attrs ) || $block_attrs['display_to'] === 'all' || $block['blockName'] === 'wppb/content-restriction-start' ) {
        return $block_content;
    }

    // Map the block content restriction settings to the wppb-restrict shortcode parameters
    $atts = array(
            'user_roles'    => is_array( $block_attrs ) && array_key_exists( 'user_roles', $block_attrs ) ? implode( ',', $block_attrs['user_roles'] ) : '',
            'display_to'    => $block_attrs['display_to'],
            'message'       => $block_attrs['display_to'] === 'not_logged_in'
                ? ( $block_attrs['enable_message_logged_out'] ? $block_attrs['message_logged_out'] : '' )
                : ( $block_attrs['enable_message_logged_in']  ? $block_attrs['message_logged_in']  : '' ),
            'users_id'      => $block_attrs['users_ids'],
        );

    return '<div>'.wppb_content_restriction_shortcode( $atts, $block_content ).'</div>';
}
add_filter( 'render_block', 'wppb_render_blocks', 10, 2 );


/**
 * Adds the `wppbContentRestriction` attribute to all blocks
 */
add_action( 'wp_loaded', 'wppb_add_custom_attributes_to_blocks', 199 );
function wppb_add_custom_attributes_to_blocks() {

	$registered_blocks = WP_Block_Type_Registry::get_instance()->get_all_registered();

	foreach( $registered_blocks as $name => $block ) {

		$block->attributes['wppbContentRestriction'] = array(
			'type'    => 'object',
            'properties' => array(
                'user_roles' => array(
                    'type' => 'array',
                ),
                'users_ids' => array(
                    'type' => 'string',
                ),
                'display_to' => array(
                    'type' => 'string',
                ),
                'enable_message_logged_in' => array(
                    'type' => 'boolean',
                ),
                'enable_message_logged_out' => array(
                    'type' => 'boolean',
                ),
                'message_logged_in' => array(
                    'type' => 'string',
                ),
                'message_logged_out' => array(
                    'type' => 'string',
                ),

                'panel_open' => array(
                    'type' => 'boolean',
                ),
            ),
			'default' => array(
                'user_roles'                => array(),
                'users_ids'                 => '',
                'display_to'                => 'all',
                'enable_message_logged_in'  => false,
                'enable_message_logged_out' => false,
                'message_logged_in'         => '',
                'message_logged_out'        => '',
                'panel_open'                => false,
            ),
		);
	}

}

