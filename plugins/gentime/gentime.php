<?php
/**
 * GenTime
 *
 * @package   GenTime
 * @author    Sybre Waaijer
 * @copyright 2025 Sybre Waaijer, CyberWire B.V. (https://cyberwire.nl/)
 * @license   GPLv3
 * @link      https://github.com/sybrew/gentime/
 * @access    private
 *
 * @troy-repo
 * Troy: repo.cyberwire.nl
 *
 * @wordpress-plugin
 * Plugin Name: GenTime
 * Plugin URI: https://wordpress.org/plugins/gentime
 * Description: GenTime shows the page generation time in the WordPress admin bar.
 * Version: 2.0.0
 * Author: Sybre Waaijer
 * Author URI: https://cyberwire.nl/
 * License: GPLv3
 * Text Domain: gentime
 * Requires at least: 5.3
 * Tested up to: 6.9
 * Requires PHP: 7.4
 */

namespace GenTime;

\defined( 'ABSPATH' ) or die;

/**
 * Gentime plugin
 * Copyright (C) 2015 - 2025 Sybre Waaijer, CyberWire B.V. (https://cyberwire.nl/)
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 3 as published
 * by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

\add_action( 'admin_bar_menu', __NAMESPACE__ . '\add_admin_item', 912 );

/**
 * Adds admin node for the generation time.
 *
 * @hook admin_bar_menu 912
 * @since 2.0.0
 *
 * @param \WP_Admin_Bar $wp_admin_bar The WP_Admin_Bar instance
 */
function add_admin_item( $wp_admin_bar ) {

	\defined( 'GENTIME_VIEW_CAPABILITY' )
		or \define( 'GENTIME_VIEW_CAPABILITY', 'manage_options' );

	if ( ! \current_user_can( \GENTIME_VIEW_CAPABILITY ) )
		return;

	// Redundant for most sites, but the plugin may be loaded via Composer
	\load_plugin_textdomain(
		'gentime',
		false,
		\dirname( \plugin_basename( __FILE__ ) ) . '/language',
	);

	echo '<style>#wp-admin-bar-gentime .ab-icon:before{font-family:dashicons;content:"\f469";top:2px}</style>';

	// Enqueued with print_late_styles(). Dashicons is a 'common' script, but WP appears to be phasing it out
	\wp_enqueue_style( 'dashicons' );

	$wp_admin_bar->add_node(
		[
			'id'    => 'gentime',
			'title' => \sprintf(
				'<span class=ab-icon></span><span class=ab-label>%s</span>',
				\number_format_i18n(
					\timer_float(),
					/**
					 * @since 1.0.0
					 * @param int $decimals The generation time decimals amount
					 */
					\apply_filters( 'gentime_decimals', 3 ),
				)
				. \esc_html_x( 's', 'seconds', 'gentime' ),
			),
			'href'  => '',
			'meta'  => [
				'title' => \esc_attr__( 'Page Generation Time', 'gentime' ),
			],
		],
	);
}
