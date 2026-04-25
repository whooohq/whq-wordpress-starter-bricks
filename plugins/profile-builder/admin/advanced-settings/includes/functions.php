<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @param  string        $context   Name of the folder where the code file is placed (tab slug).
 * @param  string        $setting   Name of the file where the code is placed.
 * @return string|false             Returns false if settings are empty (shouldn't reach this point in that case anyway), else returns the setting.
 */
function wppb_toolbox_get_settings( $context, $setting ) {
    $option = 'wppb_toolbox_' . $context . '_settings';

    $settings = get_option( $option );

    if ( $settings == false ) return false;

    if ( isset( $settings[ $setting ] ) )
        return $settings[ $setting ];

    return false;
}


/**
 * Clean unnecessary extra post meta from unrelated posts
 *
 * - wppb_sc_rf_epf_active
 * - wppb-content-restrict-message-purchasing_restricted
 * - wppb-ul-active-theme
 * - wppb-ul-default-single-user-template
 * - wppb-ul-default-all-users-template
 *
 */
function wppb_cleanup_postmeta() {
    check_ajax_referer( 'wppb_cleanup_postmeta', 'nonce' );

    global $wpdb;

    $query         = '';
    $step          = isset( $_POST['step'] ) ? absint( $_POST['step'] ) : 1;
    $batch_size    = 500;
    $rows_affected = 0;

    if ( $step === 1 ) {

        // First query - Social Connect meta
        $query = $wpdb->prepare( "DELETE FROM {$wpdb->postmeta}
                                 WHERE post_id IN (
                                     SELECT ID FROM {$wpdb->posts}
                                     WHERE post_type NOT IN (%s, %s)
                                 )
                                 AND meta_key = %s
                                 LIMIT %d",
                                'wppb-rf-cpt',
                                'wppb-epf-cpt',
                                'wppb_sc_rf_epf_active',
                                $batch_size );

        $rows_affected = $wpdb->query( $query );

        if ( $rows_affected === 0 ) {

            // Move to next step when no more rows to delete
            wp_send_json_success( array( 'step' => 2 ) );

        }

    } else if ( $step === 2 ) {

        // Second query - UserListing themes meta
        $query = $wpdb->prepare( "DELETE FROM {$wpdb->postmeta}
                                 WHERE post_id IN (
                                     SELECT ID FROM {$wpdb->posts}
                                     WHERE post_type != %s
                                 )
                                 AND meta_key IN (%s, %s, %s)
                                 LIMIT %d",
                                'wppb-ul-cpt',
                                'wppb-ul-active-theme',
                                'wppb-ul-default-single-user-template',
                                'wppb-ul-default-all-users-template',
                                $batch_size );

        $rows_affected = $wpdb->query( $query );

        if ( $rows_affected === 0 ) {

            // Move to next step when no more rows to delete
            wp_send_json_success( array( 'step' => 3 ) );

        }

    } else if ( $step === 3 ) {

        // Third query - Placeholder Labels meta
        $query = $wpdb->prepare( "DELETE FROM {$wpdb->postmeta}
                                 WHERE meta_key = %s
                                 AND meta_value = %s
                                 LIMIT %d",
                                    'pbpl-active',
                                    'no',
                                $batch_size );

        $rows_affected = $wpdb->query( $query );

        if ( $rows_affected === 0 ) {

            // Save the new completion flag as non-autoloaded option
            add_option( 'wppb_postmeta_cleanup_completed_v2', true, '', 'no' );

            // Remove the old completion flag
            delete_option( 'wppb_postmeta_cleanup_completed' );

            // All done
            wp_send_json_success( array(
                'step' => 'done',
                'hide_button' => true
            ) );

        }

    }

    // Continue with same step
    wp_send_json_success( array(
        'step' => $step,
        'rows_affected' => $rows_affected
    ) );
}
add_action( 'wp_ajax_wppb_cleanup_postmeta', 'wppb_cleanup_postmeta' );
