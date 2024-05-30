<?php
/**
 * A class for setting fields.
 *
 * @package First-Party-Analytics
 */

namespace WP_Plugins_Core;

/**
 * Class Setting_Fields.
 */
final class Setting_Fields {

    /**
     * Escape JSON for use on HTML or attribute text nodes.
     *
     * @param string $json JSON to escape.
     * @param bool   $html True if escaping for HTML text node, false for attributes. Determines how quotes are handled.
     * @return string Escaped JSON.
     *
     * @see wc_esc_json()
     */
    public static function esc_json( $json, $html = false ) {
        return _wp_specialchars(
            $json,
            $html ? ENT_NOQUOTES : ENT_QUOTES, // Escape quotes in attribute nodes only.
            'UTF-8',                           // json_encode() outputs UTF-8 (really just ASCII), not the blog's charset.
            true                               // Double escape entities: `&amp;` -> `&amp;amp;`.
        );
    }

    /**
     * Return the html selected attribute if stringified $value is found in array of stringified $options
     * or if stringified $value is the same as scalar stringified $options.
     *
     * @param string|int       $value   Value to find within options.
     * @param string|int|array $options Options to go through when looking for value.
     * @return string
     *
     * @see wc_selected()
     */
    public static function selected( $value, $options ) {
        if ( is_array( $options ) ) {
            $options = array_map( 'strval', $options );
            return selected( in_array( (string) $value, $options, true ), true, false );
        }

        return selected( $value, $options, false );
    }

    /**
     * Parse a relative date option from the settings API into a standard format.
     *
     * @param mixed $raw_value Value stored in DB.
     * @return array Nicely formatted array with number and unit values.
     *
     * @see wc_parse_relative_date_option()
     */
    public static function parse_relative_date_option( $raw_value ) {
        $periods = [
            'days'   => __( 'Day(s)', 'tmm-wp-plugins-core' ),
            'weeks'  => __( 'Week(s)', 'tmm-wp-plugins-core' ),
            'months' => __( 'Month(s)', 'tmm-wp-plugins-core' ),
            'years'  => __( 'Year(s)', 'tmm-wp-plugins-core' ),
        ];

        $value = wp_parse_args(
            (array) $raw_value,
            array(
                'number' => '',
                'unit'   => 'days',
            )
        );

        $value['number'] = ! empty( $value['number'] ) ? absint( $value['number'] ) : '';

        if ( ! in_array( $value['unit'], array_keys( $periods ), true ) ) {
            $value['unit'] = 'days';
        }

        return $value;
    }

    /**
     * Helper function to get the formatted description and tip HTML for a
     * given form field. Plugins can call this when implementing their own custom
     * settings types.
     *
     * @param  array $value The form field value array.
     *
     * @return array The description and tip as a 2 element array.
     *
     * @see \WC_Admin_Settings::get_field_description
     */
    public static function get_field_description( $value ) {
        $description  = '';
        $tooltip_html = '';

        if ( true === $value['desc_tip'] ) {
            $tooltip_html = $value['desc'];
        } elseif ( ! empty( $value['desc_tip'] ) ) {
            $description  = $value['desc'];
            $tooltip_html = $value['desc_tip'];
        } elseif ( ! empty( $value['desc'] ) ) {
            $description = $value['desc'];
        }

        if ( $description && in_array( $value['type'], array( 'textarea', 'radio' ), true ) ) {
            $description = '<p style="margin-top:0">' . wp_kses_post( $description ) . '</p>';
        } elseif ( $description && in_array( $value['type'], array( 'checkbox' ), true ) ) {
            $description = wp_kses_post( $description );
        } elseif ( $description ) {
            $description = '<p class="description">' . wp_kses_post( $description ) . '</p>';
        }

        $tooltip_html = '<p class="description">' . $tooltip_html . '</p>';

        return array(
            'description'  => $description,
            'tooltip_html' => $tooltip_html,
        );
    }

    /**
     * Outputs setting fields.
     *
     * Loops through the options array and outputs each field.
     *
     * @param array[] $options Opens array to output.
     *
     * @see \WC_Admin_Settings::output_fields
     */
    public static function output_fields( $options ) {
        foreach ( $options as $value ) {
            if ( ! $value ) {
                continue;
            }

            if ( ! isset( $value['type'] ) ) {
                continue;
            }
            if ( ! isset( $value['id'] ) ) {
                $value['id'] = '';
            }
            if ( ! isset( $value['title'] ) ) {
                $value['title'] = isset( $value['name'] ) ? $value['name'] : '';
            }
            if ( ! isset( $value['class'] ) ) {
                $value['class'] = '';
            }
            if ( ! isset( $value['css'] ) ) {
                $value['css'] = '';
            }
            if ( ! isset( $value['default'] ) ) {
                $value['default'] = '';
            }
            if ( ! isset( $value['desc'] ) ) {
                $value['desc'] = '';
            }
            if ( ! isset( $value['desc_tip'] ) ) {
                $value['desc_tip'] = false;
            }
            if ( ! isset( $value['placeholder'] ) ) {
                $value['placeholder'] = '';
            }
            if ( ! isset( $value['suffix'] ) ) {
                $value['suffix'] = '';
            }
            if ( ! isset( $value['value'] ) ) {
                $value['value'] = $value['default'];
            }

            // Custom attribute handling.
            $custom_attributes = array();

            if ( ! empty( $value['custom_attributes'] ) && is_array( $value['custom_attributes'] ) ) {
                foreach ( $value['custom_attributes'] as $attribute => $attribute_value ) {
                    $custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
                }
            }

            // Description handling.
            $field_description = self::get_field_description( $value );
            $description       = $field_description['description'];
            $tooltip_html      = $field_description['tooltip_html'];

            // Switch based on type.
            switch ( $value['type'] ) {
                // Section Titles.
                case 'title':
                    if ( ! empty( $value['title'] ) ) {
                        echo '<h2>' . esc_html( $value['title'] ) . '</h2>';
                    }
                    if ( ! empty( $value['desc'] ) ) {
                        echo '<div id="' . esc_attr( sanitize_title( $value['id'] ) ) . '-description">';
                        echo wp_kses_post( wpautop( wptexturize( $value['desc'] ) ) );
                        echo '</div>';
                    }
                    echo '<table class="form-table">' . "\n\n";
                    break;

                case 'info':
                    echo '<tr><th scope="row" class="titledesc"/><td style="' . esc_attr( $value['css'] ) . '">';
                    echo wp_kses_post( wpautop( wptexturize( $value['text'] ) ) );
                    echo '</td></tr>';
                    break;

                // Section Ends.
                case 'sectionend':
                    echo '</table>';
                    break;

                // Standard text inputs and subtypes like 'number'.
                case 'text':
                case 'password':
                case 'datetime':
                case 'datetime-local':
                case 'date':
                case 'month':
                case 'time':
                case 'week':
                case 'number':
                case 'email':
                case 'url':
                case 'tel':
                    $option_value = $value['value'];

                    ?><tr>
                        <th scope="row" class="titledesc">
                            <label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?> <?php echo $tooltip_html; ?></label>
                        </th>
                        <td class="forminp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>">
                            <input
                                name="<?php echo esc_attr( $value['id'] ); ?>"
                                id="<?php echo esc_attr( $value['id'] ); ?>"
                                type="<?php echo esc_attr( $value['type'] ); ?>"
                                style="<?php echo esc_attr( $value['css'] ); ?>"
                                value="<?php echo esc_attr( $option_value ); ?>"
                                class="<?php echo esc_attr( $value['class'] ); ?>"
                                placeholder="<?php echo esc_attr( $value['placeholder'] ); ?>"
                                <?php echo implode( ' ', $custom_attributes ); ?>
                                /><?php echo esc_html( $value['suffix'] ); ?> <?php echo $description; ?>
                        </td>
                    </tr>
                    <?php
                    break;

                // Color picker.
                case 'color':
                    $option_value = $value['value'];

                    ?>
                    <tr>
                        <th scope="row" class="titledesc">
                            <label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?> <?php echo $tooltip_html; ?></label>
                        </th>
                        <td class="forminp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>">&lrm;
                            <span class="colorpickpreview" style="background: <?php echo esc_attr( $option_value ); ?>">&nbsp;</span>
                            <input
                                name="<?php echo esc_attr( $value['id'] ); ?>"
                                id="<?php echo esc_attr( $value['id'] ); ?>"
                                type="text"
                                dir="ltr"
                                style="<?php echo esc_attr( $value['css'] ); ?>"
                                value="<?php echo esc_attr( $option_value ); ?>"
                                class="<?php echo esc_attr( $value['class'] ); ?>colorpick"
                                placeholder="<?php echo esc_attr( $value['placeholder'] ); ?>"
                                <?php echo implode( ' ', $custom_attributes ); ?>
                                />&lrm; <?php echo $description; ?>
                                <div id="colorPickerDiv_<?php echo esc_attr( $value['id'] ); ?>" class="colorpickdiv" style="z-index: 100;background:#eee;border:1px solid #ccc;position:absolute;display:none;"></div>
                        </td>
                    </tr>
                    <?php
                    break;

                // Textarea.
                case 'textarea':
                    $option_value = $value['value'];

                    ?>
                    <tr>
                        <th scope="row" class="titledesc">
                            <label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?> <?php echo $tooltip_html; ?></label>
                        </th>
                        <td class="forminp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>">
                            <?php echo $description; ?>

                            <textarea
                                name="<?php echo esc_attr( $value['id'] ); ?>"
                                id="<?php echo esc_attr( $value['id'] ); ?>"
                                style="<?php echo esc_attr( $value['css'] ); ?>"
                                class="<?php echo esc_attr( $value['class'] ); ?>"
                                placeholder="<?php echo esc_attr( $value['placeholder'] ); ?>"
                                <?php echo implode( ' ', $custom_attributes ); ?>
                                ><?php echo esc_textarea( $option_value ); ?></textarea>
                        </td>
                    </tr>
                    <?php
                    break;

                // Select boxes.
                case 'select':
                case 'multiselect':
                    $option_value = $value['value'];

                    ?>
                    <tr>
                        <th scope="row" class="titledesc">
                            <label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?> <?php echo $tooltip_html; ?></label>
                        </th>
                        <td class="forminp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>">
                            <select
                                name="<?php echo esc_attr( $value['id'] ); ?><?php echo ( 'multiselect' === $value['type'] ) ? '[]' : ''; ?>"
                                id="<?php echo esc_attr( $value['id'] ); ?>"
                                style="<?php echo esc_attr( $value['css'] ); ?>"
                                class="<?php echo esc_attr( $value['class'] ); ?>"
                                <?php echo implode( ' ', $custom_attributes ); ?>
                                <?php echo 'multiselect' === $value['type'] ? 'multiple="multiple"' : ''; ?>
                                >
                                <?php
                                foreach ( $value['options'] as $key => $val ) {
                                    ?>
                                    <option value="<?php echo esc_attr( $key ); ?>"
                                        <?php

                                        if ( is_array( $option_value ) ) {
                                            selected( in_array( (string) $key, $option_value, true ), true );
                                        } else {
                                            selected( $option_value, (string) $key );
                                        }

                                        ?>
                                    ><?php echo esc_html( $val ); ?></option>
                                    <?php
                                }
                                ?>
                            </select> <?php echo $description; ?>
                        </td>
                    </tr>
                    <?php
                    break;

                // Radio inputs.
                case 'radio':
                    $option_value    = $value['value'];
                    $disabled_values = self::array_util_get_value_or_default( $value, 'disabled', array() );

                    ?>
                    <tr>
                        <th scope="row" class="titledesc">
                            <label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?> <?php echo $tooltip_html; ?></label>
                        </th>
                        <td class="forminp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>">
                            <fieldset>
                                <?php echo $description; ?>
                                <ul>
                                <?php
                                foreach ( $value['options'] as $key => $val ) {
                                    ?>
                                    <li>
                                        <label><input
                                            name="<?php echo esc_attr( $value['id'] ); ?>"
                                            value="<?php echo esc_attr( $key ); ?>"
                                            type="radio"
                                            <?php
                                            if ( in_array( $key, $disabled_values, true ) ) {
                                                echo 'disabled';
                                            }
                                            ?>
                                            style="<?php echo esc_attr( $value['css'] ); ?>"
                                            class="<?php echo esc_attr( $value['class'] ); ?>"
                                            <?php echo implode( ' ', $custom_attributes ); ?>
                                            <?php checked( $key, $option_value ); ?>
                                            /> <?php echo $val; ?></label>
                                    </li>
                                    <?php
                                }
                                ?>
                                </ul>
                            </fieldset>
                        </td>
                    </tr>
                    <?php
                    break;

                // Checkbox input.
                case 'checkbox':
                    $option_value     = $value['value'];
                    $visibility_class = array();

                    if ( ! isset( $value['hide_if_checked'] ) ) {
                        $value['hide_if_checked'] = false;
                    }
                    if ( ! isset( $value['show_if_checked'] ) ) {
                        $value['show_if_checked'] = false;
                    }
                    if ( 'yes' === $value['hide_if_checked'] || 'yes' === $value['show_if_checked'] ) {
                        $visibility_class[] = 'hidden_option';
                    }
                    if ( 'option' === $value['hide_if_checked'] ) {
                        $visibility_class[] = 'hide_options_if_checked';
                    }
                    if ( 'option' === $value['show_if_checked'] ) {
                        $visibility_class[] = 'show_options_if_checked';
                    }

                    if ( ! isset( $value['checkboxgroup'] ) || 'start' === $value['checkboxgroup'] ) {
                        ?>
                            <tr valign="top" class="<?php echo esc_attr( implode( ' ', $visibility_class ) ); ?>">
                                <th scope="row" class="titledesc"><?php echo esc_html( $value['title'] ); ?></th>
                                <td class="forminp forminp-checkbox">
                                    <fieldset <?php echo isset( $value['desc_tip'] ) ? 'class="mb-10"' : ''; ?>>
                        <?php
                    } else {
                        ?>
                            <fieldset class="<?php echo esc_attr( implode( ' ', $visibility_class ) ); ?> <?php
                                echo isset( $value['desc_tip'] ) && isset( $value['checkboxgroup'] ) ? 'mb-10' : '';
                            ?>
                                ">
                        <?php
                    }

                    if ( ! empty( $value['title'] ) ) {
                        ?>
                            <legend class="screen-reader-text"><span><?php echo esc_html( $value['title'] ); ?></span></legend>
                        <?php
                    }

                    ?>
                        <label for="<?php echo esc_attr( $value['id'] ); ?>">
                            <input
                                name="<?php echo esc_attr( $value['id'] ); ?>"
                                id="<?php echo esc_attr( $value['id'] ); ?>"
                                type="checkbox"
                                class="<?php echo esc_attr( isset( $value['class'] ) ? $value['class'] : '' ); ?>"
                                value="1"
                                <?php checked( $option_value, 'yes' ); ?>
                                <?php echo implode( ' ', $custom_attributes ); ?>
                            /> <?php echo $description; ?>
                        </label> <?php echo $tooltip_html; ?>
                    <?php

                    if ( ! isset( $value['checkboxgroup'] ) || 'end' === $value['checkboxgroup'] ) {
                        ?>
                                    </fieldset>
                                </td>
                            </tr>
                        <?php
                    } else {
                        ?>
                            </fieldset>
                        <?php
                    }
                    break;

                // Image width settings. @todo deprecate and remove in 4.0. No longer needed by core.
                case 'image_width':
                    $image_size       = str_replace( '_image_size', '', $value['id'] );
                    $size             = function_exists( 'wc_get_image_size' ) ? wc_get_image_size( $image_size ) : '';
                    $width            = isset( $size['width'] ) ? $size['width'] : $value['default']['width'];
                    $height           = isset( $size['height'] ) ? $size['height'] : $value['default']['height'];
                    $crop             = isset( $size['crop'] ) ? $size['crop'] : $value['default']['crop'];
                    $disabled_attr    = '';
                    $disabled_message = '';

                    ?>
                    <tr>
                        <th scope="row" class="titledesc">
                        <label><?php echo esc_html( $value['title'] ); ?> <?php echo $tooltip_html . $disabled_message; ?></label>
                    </th>
                        <td class="forminp image_width_settings">

                            <input name="<?php echo esc_attr( $value['id'] ); ?>[width]" <?php echo $disabled_attr; ?> id="<?php echo esc_attr( $value['id'] ); ?>-width" type="text" size="3" value="<?php echo esc_attr( $width ); ?>" /> &times; <input name="<?php echo esc_attr( $value['id'] ); ?>[height]" <?php echo $disabled_attr; ?> id="<?php echo esc_attr( $value['id'] ); ?>-height" type="text" size="3" value="<?php echo esc_attr( $height ); ?>" />px

                            <label><input name="<?php echo esc_attr( $value['id'] ); ?>[crop]" <?php echo $disabled_attr; ?> id="<?php echo esc_attr( $value['id'] ); ?>-crop" type="checkbox" value="1" <?php checked( 1, $crop ); ?> /> <?php esc_html_e( 'Hard crop?', 'first-party-analytics' ); ?></label>

                            </td>
                    </tr>
                    <?php
                    break;

                // Single page selects.
                case 'single_select_page':
                    $args = array(
                        'name'             => $value['id'],
                        'id'               => $value['id'],
                        'sort_column'      => 'menu_order',
                        'sort_order'       => 'ASC',
                        'show_option_none' => ' ',
                        'class'            => $value['class'],
                        'echo'             => false,
                        'selected'         => absint( $value['value'] ),
                        'post_status'      => 'publish,private,draft',
                    );

                    if ( isset( $value['args'] ) ) {
                        $args = wp_parse_args( $value['args'], $args );
                    }

                    ?>
                    <tr class="single_select_page">
                        <th scope="row" class="titledesc">
                            <label><?php echo esc_html( $value['title'] ); ?> <?php echo $tooltip_html; ?></label>
                        </th>
                        <td class="forminp">
                            <?php echo str_replace( ' id=', " data-placeholder='" . esc_attr__( 'Select a page&hellip;', 'first-party-analytics' ) . "' style='" . $value['css'] . "' class='" . $value['class'] . "' id=", wp_dropdown_pages( $args ) ); ?> <?php echo $description; ?>
                        </td>
                    </tr>
                    <?php
                    break;

                case 'single_select_page_with_search':
                    $option_value = $value['value'];
                    $page         = get_post( $option_value );

                    if ( ! is_null( $page ) ) {
                        $page                = get_post( $option_value );
                        $option_display_name = sprintf(
                            /* translators: 1: page name 2: page ID */
                            __( '%1$s (ID: %2$s)', 'first-party-analytics' ),
                            $page->post_title,
                            $option_value
                        );
                    }
                    ?>
                    <tr class="single_select_page">
                        <th scope="row" class="titledesc">
                            <label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?> <?php echo $tooltip_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></label>
                        </th>
                        <td class="forminp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>">
                            <select
                                name="<?php echo esc_attr( $value['id'] ); ?>"
                                id="<?php echo esc_attr( $value['id'] ); ?>"
                                style="<?php echo esc_attr( $value['css'] ); ?>"
                                class="<?php echo esc_attr( $value['class'] ); ?>"
                                <?php echo implode( ' ', $custom_attributes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                data-placeholder="<?php esc_attr_e( 'Search for a page&hellip;', 'first-party-analytics' ); ?>"
                                data-allow_clear="true"
                                data-exclude="<?php echo self::esc_json( wp_json_encode( $value['args']['exclude'] ) ); ?>"
                                >
                                <option value=""></option>
                                <?php if ( ! is_null( $page ) ) { ?>
                                    <option value="<?php echo esc_attr( $option_value ); ?>" selected="selected">
                                    <?php echo wp_strip_all_tags( $option_display_name ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                    </option>
                                <?php } ?>
                            </select> <?php echo $description; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        </td>
                    </tr>
                    <?php
                    break;

                // Single country selects.
                case 'single_select_country':
                    $country_setting = (string) $value['value'];

                    if ( strstr( $country_setting, ':' ) ) {
                        $country_setting = explode( ':', $country_setting );
                        $country         = current( $country_setting );
                        $state           = end( $country_setting );
                    } else {
                        $country = $country_setting;
                        $state   = '*';
                    }
                    ?>
                    <tr>
                        <th scope="row" class="titledesc">
                            <label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?> <?php echo $tooltip_html; ?></label>
                        </th>
                        <td class="forminp"><select name="<?php echo esc_attr( $value['id'] ); ?>" style="<?php echo esc_attr( $value['css'] ); ?>" data-placeholder="<?php esc_attr_e( 'Choose a country / region&hellip;', 'first-party-analytics' ); ?>" aria-label="<?php esc_attr_e( 'Country / Region', 'first-party-analytics' ); ?>" class="wc-enhanced-select">
                            <?php WC()->countries->country_dropdown_options( $country, $state ); ?>
                        </select> <?php echo $description; ?>
                        </td>
                    </tr>
                    <?php
                    break;

                // Country multiselects.
                case 'multi_select_countries':
                    $selections = (array) $value['value'];

                    if ( ! empty( $value['options'] ) ) {
                        $countries = $value['options'];
                    } else {
                        $countries = WC()->countries->countries;
                    }

                    asort( $countries );
                    ?>
                    <tr>
                        <th scope="row" class="titledesc">
                            <label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?> <?php echo $tooltip_html; ?></label>
                        </th>
                        <td class="forminp">
                            <select multiple="multiple" name="<?php echo esc_attr( $value['id'] ); ?>[]" style="width:350px" data-placeholder="<?php esc_attr_e( 'Choose countries / regions&hellip;', 'first-party-analytics' ); ?>" aria-label="<?php esc_attr_e( 'Country / Region', 'first-party-analytics' ); ?>" class="wc-enhanced-select">
                                <?php
                                if ( ! empty( $countries ) ) {
                                    foreach ( $countries as $key => $val ) {
                                        echo '<option value="' . esc_attr( $key ) . '"' . self::selected( $key, $selections ) . '>' . esc_html( $val ) . '</option>';
                                    }
                                }
                                ?>
                            </select> <?php echo ( $description ) ? $description : ''; ?> <br /><a class="select_all button" href="#"><?php esc_html_e( 'Select all', 'first-party-analytics' ); ?></a> <a class="select_none button" href="#"><?php esc_html_e( 'Select none', 'first-party-analytics' ); ?></a>
                        </td>
                    </tr>
                    <?php
                    break;

                // Days/months/years selector.
                case 'relative_date_selector':
                    $periods      = array(
                        'days'   => __( 'Day(s)', 'first-party-analytics' ),
                        'weeks'  => __( 'Week(s)', 'first-party-analytics' ),
                        'months' => __( 'Month(s)', 'first-party-analytics' ),
                        'years'  => __( 'Year(s)', 'first-party-analytics' ),
                    );
                    $option_value = self::parse_relative_date_option( $value['value'] );
                    ?>
                    <tr>
                        <th scope="row" class="titledesc">
                            <label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?> <?php echo $tooltip_html; ?></label>
                        </th>
                        <td class="forminp">
                        <input
                                name="<?php echo esc_attr( $value['id'] ); ?>[number]"
                                id="<?php echo esc_attr( $value['id'] ); ?>"
                                type="number"
                                style="width: 80px;"
                                value="<?php echo esc_attr( $option_value['number'] ); ?>"
                                class="<?php echo esc_attr( $value['class'] ); ?>"
                                placeholder="<?php echo esc_attr( $value['placeholder'] ); ?>"
                                step="1"
                                min="1"
                                <?php echo implode( ' ', $custom_attributes ); ?>
                            />&nbsp;
                            <select name="<?php echo esc_attr( $value['id'] ); ?>[unit]" style="width: auto;">
                                <?php
                                foreach ( $periods as $value => $label ) {
                                    echo '<option value="' . esc_attr( $value ) . '"' . selected( $option_value['unit'], $value, false ) . '>' . esc_html( $label ) . '</option>';
                                }
                                ?>
                            </select> <?php echo ( $description ) ? $description : ''; ?>
                        </td>
                    </tr>
                    <?php
                    break;

                case 'units': // Custom "Units" attribute.
                    $custom_attributes = [];

                    if ( ! empty( $value['custom_attributes'] ) && is_array( $value['custom_attributes'] ) ) {
                        foreach ( $value['custom_attributes'] as $attribute => $attribute_value ) {
                            $custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
                        }
                    }

                    // Description handling.
                    $field_description = self::get_field_description( $value );
                    $description       = $field_description['description'];
                    $tooltip_html      = $field_description['tooltip_html'];

                    ?>
                    <tr>
                        <th scope="row" class="titledesc">
                            <label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?> <?php echo $tooltip_html; ?></label>
                        </th>
                        <td class="forminp">
                            <input
                                name="<?php echo esc_attr( $value['id'] ); ?>[number]"
                                id="<?php echo esc_attr( $value['id'] ); ?>"
                                type="number"
                                style="<?php echo esc_attr( $value['css'] ); ?>"
                                value="<?php echo esc_attr( $value['number'] ); ?>"
                                class="<?php echo esc_attr( $value['class'] ); ?>"
                                placeholder="<?php echo esc_attr( $value['placeholder'] ); ?>"
                                <?php echo implode( ' ', $custom_attributes ); ?>
                            />&nbsp;
                            <select name="<?php echo esc_attr( $value['id'] ); ?>[unit]" style="width: auto;">
                                <?php
                                foreach ( $value['units'] as $unit_value => $label ) {
                                    echo '<option value="' . esc_attr( $unit_value ) . '"' . selected( $value['unit'], $unit_value, false ) . '>' . esc_html( $label ) . '</option>';
                                }
                                ?>
                            </select> <?php echo ( $description ) ? $description : ''; ?>
                        </td>
                    </tr>
                    <?php
                    break;

                case 'html': // Custom "HTML" attribute.
                    $html = $value['value'];

                    ?>
                    <tr>
                        <th scope="row" class="titledesc">
                            <label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?> <?php echo $tooltip_html; ?></label>
                        </th>
                        <td class="forminp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>">
                            <?php echo $html; ?>
                            <?php echo $description; ?>
                        </td>
                    </tr>
                    <?php
                    break;

                case 'pure-html': // Custom "HTML" attribute.
                    $html = $value['value'];

                    if ( isset( $value['cols'] ) && 2 === $value['cols'] ) :
                        ?>
                    <th scope="row" class="titledesc">
                    </th>
                        <?php
                    endif;
                    ?>
                    <tr>
                        <td colspan="2" class="forminp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>">
                            <?php echo $html; ?>
                        </td>
                    </tr>
                    <?php
                    break;
            }
        }
    }

    /**
     * Gets the value for a given key from an array, or a default value if the key doesn't exist in the array.
     *
     * @param array  $array The array to get the value from.
     * @param string $key The key to use to retrieve the value.
     * @param null   $default The default value to return if the key doesn't exist in the array.
     * @return mixed|null The value for the key, or the default value passed.
     *
     * @see ArrayUtil::get_value_or_default()
     */
    public static function array_util_get_value_or_default( array $array, $key, $default = null ) {
        return isset( $array[ $key ] ) ? $array[ $key ] : $default;
    }
}
