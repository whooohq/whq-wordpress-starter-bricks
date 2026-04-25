<?php
/*
 +=====================================================================+
 |    ____          _        ____             __ _ _                   |
 |   / ___|___   __| | ___  |  _ \ _ __ ___  / _(_) | ___ _ __         |
 |  | |   / _ \ / _` |/ _ \ | |_) | '__/ _ \| |_| | |/ _ \ '__|        |
 |  | |__| (_) | (_| |  __/ |  __/| | | (_) |  _| | |  __/ |           |
 |   \____\___/ \__,_|\___| |_|   |_|  \___/|_| |_|_|\___|_|           |
 |                                                                     |
 |  (c) Jerome Bruandet ~ https://nintechnet.com/codeprofiler/         |
 +=====================================================================+
*/

if (! defined('ABSPATH') ) { die('Forbidden'); }

// =====================================================================
// Display Summary tab.

// Used to display profiler's error messages while running
echo '<div class="error notice is-dismissible" style="display:none" id="code-profiler-error"><p></p></div>';
echo code_profiler_display_tabs( 1 );
$error = 0;
if (! function_exists('register_tick_function') ) {
	$error = 'register_tick_function';
} elseif (! function_exists('stream_wrapper_unregister') ) {
	$error = 'stream_wrapper_unregister';
} elseif (! function_exists('stream_wrapper_register') ) {
	$error = 'stream_wrapper_register';
} elseif (! function_exists('stream_wrapper_restore') ) {
	$error = 'stream_wrapper_restore';
}
if (! empty( $error ) ) {
	printf( CODE_PROFILER_ERROR_NOTICE, sprintf(
		esc_html__('Your PHP configuration is missing the "%s" function. Code Profiler cannot run without it. Please contact your server administrator about this error.', 'code-profiler'), $error )
	);
}
if (! is_writable( CODE_PROFILER_UPLOAD_DIR ) ) {
	printf( CODE_PROFILER_ERROR_NOTICE, sprintf(
		esc_html__('The following folder is not writable: %s. Please change its permissions or ownership so that Code Profiler can write to it.', 'code-profiler'), CODE_PROFILER_UPLOAD_DIR )
	);
}

/**
 * Check and warn if the Xdebug extension is loaded.
 */
if ( extension_loaded('xdebug') ) {
	printf( CODE_PROFILER_WARNING_NOTICE,
		esc_html__('Warning: the PHP Xdebug extension is loaded. Consider disabling it as it can drastically impact the performance and results of Code Profiler.', 'code-profiler')
	);
}

// Clear the log if it's too big
code_profiler_clearlog();

// Clean-up temp files left in the profiles folder
code_profiler_cleantmpfiles();

/**
 * Create temporary folder and file used to profile cron events.
 */
$error = code_profiler_create_tmpfile();
if ( $error ) {
	printf( CODE_PROFILER_ERROR_NOTICE,	esc_html( $error ) );
}

$home = home_url('/'); // Frontend
$site = site_url('/'); // Backend

// Get user login name
$current_user = wp_get_current_user();

$cp_options = get_option('code-profiler');
/**
 * Check if we've been asked to re-run a profile.
 */
if (! empty( $_REQUEST['action'] ) && $_REQUEST['action'] == 'rerun' &&
	! empty( $_REQUEST['profiles'][0] ) ) {
	/**
	 * Make sure the profile exists and get its full path.
	 */
	$profile_path = code_profiler_get_profile_path( $_REQUEST['profiles'][0] );
	if ( $profile_path !== false ) {

		$cp_data	= json_decode( file_get_contents("$profile_path.summary.profile"), true );
		if (! empty( $cp_data['rerun'] ) ) {
			$cp_options['mem'] = $cp_data['rerun'];
			$rerun = true;
		}
	}
}
// Profile's name
if (! empty( $cp_options['mem']['profile'] ) ) {
	$profile = $cp_options['mem']['profile'];
} else {
	$profile = code_profiler_profile_name();
}
/**
 * Memorized options from last run.
 */
if ( empty( $cp_options['mem']['x_end'] ) ) {
	$cp_options['mem']['x_end'] = 'frontend';
}
if ( empty( $cp_options['mem']['post'] ) ) {
	$cp_options['mem']['post'] = '';
}
if ( empty( $cp_options['mem']['x_auth'] ) ) {
	$cp_options['mem']['x_auth'] = 'unauthenticated';
}
if ( $cp_options['mem']['x_end'] == 'backend') {
	$cp_options['mem']['x_auth'] = 'authenticated';
}
if ( empty( $cp_options['mem']['method'] ) ) {
	$cp_options['mem']['method'] = 'get';
}
$default_theme = get_option('stylesheet');
if ( empty( $cp_options['mem']['theme'] ) ) {
	$cp_options['mem']['theme'] = $default_theme;
}
if ( empty( $cp_options['mem']['user_agent'] ) ) {
	$cp_options['mem']['user_agent'] = 'Firefox';
}
if ( isset( $cp_options['mem']['cookies'] ) ) {
	// stripslashes is only needed for cookies
	$cookies = trim( stripslashes( json_decode( $cp_options['mem']['cookies'] ) ) );
} else {
	$cookies = '';
}
if ( empty( $cp_options['mem']['content_type'] ) ||
	! in_array( $cp_options['mem']['content_type'], [ 1, 2, 3 ] ) ) {

	$cp_options['mem']['content_type'] = 1;
}
if ( isset( $cp_options['mem']['payload'] ) ) {
	$payload	=  trim( json_decode( $cp_options['mem']['payload'] ) );
} else {
	$payload = '';
}
if ( isset( $cp_options['mem']['custom_headers'] ) ) {
	$custom_headers = trim( json_decode( $cp_options['mem']['custom_headers'] ) );
} else {
	$custom_headers = '';
}
if ( isset( $cp_options['mem']['exclusions'] ) ) {
	$exclusions = json_decode( $cp_options['mem']['exclusions'] );
} else {
	$exclusions = [];
}

?>

<table style="width:100%">
	<tr>
		<td>
			<table class="form-table">
				<tr>
					<th scope="row"><?php esc_html_e('Page to profile', 'code-profiler') ?> <span class="code-profiler-tip" data-tip="<?php esc_attr_e('Select the page you want the profiler to analyze. It can be in the frontend of the site, in the admin backend, a custom URL or a cron event. Note that in case of a cron event, the "HTTP Method" option will be ignored by Code Profiler as it will always send a POST request with an empty payload.', 'code-profiler') ?>"></span></th>
					<td>
						<p><label><input type="radio" name="x_end" value="frontend" onclick="cpjs_front_or_backend(this.value);"<?php checked( $cp_options['mem']['x_end'], 'frontend') ?> /> <?php esc_html_e('Website frontend', 'code-profiler') ?></label>
						&nbsp;&nbsp;&nbsp;&nbsp;
						<label><input type="radio" name="x_end" value="custom" onclick="cpjs_front_or_backend(this.value);"<?php checked( $cp_options['mem']['x_end'], 'custom') ?> /> <?php esc_html_e('Custom post/URL', 'code-profiler') ?></label>
						&nbsp;&nbsp;&nbsp;&nbsp;
						<label><input type="radio" name="x_end" value="backend" onclick="cpjs_front_or_backend(this.value);"<?php checked( $cp_options['mem']['x_end'], 'backend') ?> /> <?php esc_html_e('Admin backend', 'code-profiler') ?></label>
						&nbsp;&nbsp;&nbsp;&nbsp;
						<label><input type="radio" name="x_end" value="wpcron" onclick="cpjs_front_or_backend(this.value);"<?php checked( $cp_options['mem']['x_end'], 'wpcron') ?> /> <?php esc_html_e('WP-Cron', 'code-profiler') ?></label>
						</p>
						<br />
						<?php
						if ( $cp_options['mem']['x_end'] == 'frontend') {
							echo '<p id="p-frontend">';
						} else {
							echo '<p id="p-frontend" style="display:none">';
						}
						?>
							<?php esc_html_e('Select a page:', 'code-profiler') ?>
							<select name="frontend" id="id-frontend">
								<?php echo code_profiler_fetch_pages( $home, $cp_options ); ?>
							</select>
						</p>
						<?php
						if ( $cp_options['mem']['x_end'] == 'custom') {
							$value = $cp_options['mem']['post'];
							echo '<p id="p-custom">';
						} else {
							$value = '';
							echo '<p id="p-custom" style="display:none">';
						}
						?>
							<input name="custom" id="id-custom" type="text" value="<?php echo esc_attr( $value ) ?>" size="70" placeholder="<?php
							echo esc_attr(
								sprintf(
									__('e.g., %s', 'code-profiler'),
									"{$home}foo/bar/"
								)
							) ?>" />
						</p>
						<?php
						if ( $cp_options['mem']['x_end'] == 'backend') {
							echo '<p id="p-backend">';
						} else {
							echo '<p id="p-backend" style="display:none">';
						}
						if (! empty( $cp_options['mem']['username'] ) ) {
							$username = $cp_options['mem']['username'];
						} else {
							$username = $current_user->user_login;
						}
						?>
							<?php esc_html_e('Select a page:', 'code-profiler') ?>
							<select name="backend" id="id-backend"><?php echo code_profiler_fetch_admin_pages( $site, $cp_options ) ?></select>
						</p>
						<?php
						/**
						 * Cron tasks.
						 */
						if ( $cp_options['mem']['x_end'] == 'wpcron') {
							echo '<p id="p-wpcron">';
						} else {
							echo '<p id="p-wpcron" style="display:none">';
						}
						?>
							<?php esc_html_e('Select a cron event:', 'code-profiler') ?>
							<select name="wpcron" id="id-wpcron">
								<?php echo code_profiler_fetch_crontasks( $cp_options['mem']['post'] ); ?>
							</select>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e('Run profiler as', 'code-profiler') ?> <span class="code-profiler-tip" data-tip="<?php esc_attr_e('The profiler can access the requested page as an unauthenticated user (frontend only) or as an authenticated user (frontend and backend). You can enter the login name of any registered user, but make sure that the user has the required capability to access the page you are going to profile, otherwise Code Profiler will return an error if the user isn\'t allowed to access it.', 'code-profiler') ?>"></span></th>
					<td>
						<p><label><input type="radio" onclick="cpjs_authenticated(0);" name="x_auth" value="unauthenticated" id="user-unauthenticated"<?php checked( $cp_options['mem']['x_auth'], 'unauthenticated'); disabled( $cp_options['mem']['x_end'], 'backend'); ?> /> <?php esc_html_e('Unauthenticated user', 'code-profiler') ?></label></p>
						<p><label><input type="radio" onclick="cpjs_authenticated(1);" name="x_auth" value="authenticated" id="user-authenticated"<?php checked( $cp_options['mem']['x_auth'], 'authenticated') ?> /> <?php esc_html_e('Authenticated user', 'code-profiler') ?></label></p>
						<p><label><?php esc_html_e('User:', 'code-profiler') ?> <input type="text" name="username" id="user-name" value ="<?php esc_attr_e( $username ) ?>"<?php disabled( $cp_options['mem']['x_auth'], 'unauthenticated') ?> /></label></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e('Theme', 'code-profiler') ?> <span class="code-profiler-tip" data-tip="<?php esc_attr_e('If you want to profile different themes, you can use this option so that you don\'t need to modify your WordPress settings. The change will not affect your visitors, but only the profiler when it is running.', 'code-profiler') ?>"></span></th>
					<td>
						<select name="theme" id="id-theme">
						<?php
						$themes = code_profiler_get_themes();
						foreach( $themes as $slug => $name ) {
							if ( $slug == $default_theme ) {
								echo '<option value=""'. selected( $cp_options['mem']['theme'], $slug, false ) .'>'.
									esc_html( $name['n'] ) .' '. __('(active theme)', 'code-profiler') .'</option>';
							} else {
								echo '<option value="'. esc_attr( $slug ) .'"'. selected( $cp_options['mem']['theme'], $slug, false ) .'>'.
									esc_html( $name['n'] ) .'</option>';
							}
						}
						?>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e('User agent', 'code-profiler') ?> <span class="code-profiler-tip" data-tip="<?php esc_attr_e('Some themes and plugins may execute different code depending on the type of device used to visit the website (e.g., a desktop computer, a mobile phone, a search engine bot etc). This option lets you change the User-Agent request header used by Code Profiler.', 'code-profiler') ?>"></span></th>
					<td>
						<select name="user_agent" id="ua-id">
						<?php
						foreach( CODE_PROFILER_UA as $types => $types_array ) {
							echo '<optgroup label="'. esc_attr( $types ) .'">';
							foreach( $types_array as $name => $value ) {
								echo '<option value="'. esc_attr( $name ) .'"'. selected( $cp_options['mem']['user_agent'], $name, false ) .'>'. esc_html( $name ) .'</option>';
							}
							echo '</optgroup>';
						}
						?>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e('Name of the profile', 'code-profiler') ?> <span class="code-profiler-tip" data-tip="<?php esc_attr_e('Enter the name for this profile. It will be saved to the "Profiles List" page, with all other profiles.', 'code-profiler') ?>"></span></th>
					<td>
						<p><label><input type="text" class="regular-text" name="profile" maxlength="100" value="<?php echo esc_attr( $profile ) ?>" id="profile-name" /></label></p>
						<p class="description"><?php esc_html_e('Max 100 characters', 'code-profiler') ?></p>
					</td>
				</tr>
			</table>
		</td>
		<td style="vertical-align:top;text-align: center">
		<?php
		/**
		 * Display any available discount coupon.
		 */
		CodeProfiler_WPCron::display_coupon();
		?>
		</td>
	</tr>
</table>
<?php
if ( (! empty( $cookies ) || ! empty( $custom_headers ) ||
	$cp_options['mem']['method'] == 'post' || ! empty( $exclusions ) ) &&
	// We don't display the advanced section if the last profile was a cron event.
	$cp_options['mem']['x_end'] != 'wpcron' ) {

	echo '<div id="cp-advanced-settings">';
	$disabled_button = ' disabled';
} else {
	echo '<div id="cp-advanced-settings" style="display:none">';
	$disabled_button = '';
}
?>
	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e('HTTP Method', 'code-profiler') ?> <span class="code-profiler-tip" data-tip="<?php esc_attr_e('You can select which HTTP method will be used by the profiler: GET or POST. If you select POST, you can also send an optional payload.', 'code-profiler') ?>"></span></th>
			<td>
				<p>
					<label>
						<input onclick="cpjs_get_post(0);" type="radio" name="method" value="get" id="get-method"<?php checked( $cp_options['mem']['method'], 'get') ?> /> GET(<?php esc_html_e('default', 'code-profiler') ?>)
					</label>
				</p>
				<p>
					<label>
						<input onclick="cpjs_get_post(1);" type="radio" name="method" value="post" id="post-method"<?php checked( $cp_options['mem']['method'], 'post') ?> /> POST
					</label>
				</p>
				<p>
					<?php esc_html_e('Content-type:', 'code-profiler') ?>
					<select name="cp-content-type" id="id-content-type"<?php disabled( $cp_options['mem']['method'], 'get') ?> onChange="cpjs_content_type(this.value);">
						<option value="1"<?php selected( $cp_options['mem']['content_type'], 1 )?>>application/x-www-form-urlencoded (<?php esc_html_e('formatted', 'code-profiler') ?>)</option>
						<option value="3"<?php selected( $cp_options['mem']['content_type'], 3 )?>>application/x-www-form-urlencoded (<?php esc_html_e('raw', 'code-profiler') ?>)</option>
						<option value="2"<?php selected( $cp_options['mem']['content_type'], 2 )?>>application/json</option>
					</select>
				</p>
				<p>
					<textarea name="post-value" id="post-value" class="regular-text code"<?php disabled( $cp_options['mem']['method'], 'get') ?> maxlength="4000" rows="6"><?php echo esc_textarea( $payload ) ?></textarea>
				</p>
				<p class="description" id="ct-1"<?php echo code_profiler_showhide( $cp_options['mem']['content_type'], 1 ) ?>>
					<?php printf( esc_html__('Optional POST payload in %s format, one item per line.', 'code-profiler'), '<code>name=value</code>') ?> <?php printf( esc_html__('%sView example%s', 'code-profiler'), '<a href="'. plugins_url('/static/help/http_method-1.png', dirname( __FILE__ ) ) .'" target="_blank" rel="noopener noreferrer">', '</a>') ?>
				</p>
				<p class="description" id="ct-3"<?php echo code_profiler_showhide( $cp_options['mem']['content_type'], 3 ) ?>>
					<?php printf( esc_html__('Optional raw POST payload, on a single line.', 'code-profiler'), '<code>name=value</code>') ?> <?php printf( esc_html__('%sView example%s', 'code-profiler'), '<a href="'. plugins_url('/static/help/http_method-3.png', dirname( __FILE__ ) ) .'" target="_blank" rel="noopener noreferrer">', '</a>') ?>
				</p>
				<p class="description" id="ct-2"<?php echo code_profiler_showhide( $cp_options['mem']['content_type'], 2 ) ?>>
					<?php printf( esc_html__('Optional JSON-encoded payload, on a single line.', 'code-profiler'), '<code>name=value</code>') ?> <?php printf( esc_html__('%sView example%s', 'code-profiler'), '<a href="'. plugins_url('/static/help/http_method-2.png', dirname( __FILE__ ) ) .'" target="_blank" rel="noopener noreferrer">', '</a>') ?>
				</p>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e('Cookie', 'code-profiler') ?> <span class="code-profiler-tip" data-tip="<?php esc_attr_e('Some websites may execute different code if the user has one or more specific cookies. You can use this field for that purpose.', 'code-profiler') ?>"></span></th>
			<td>
				<p><textarea name="cp-cookies" id="cp-cookies" class="regular-text code" maxlength="4000" rows="6"><?php echo esc_textarea( $cookies ) ?></textarea></p>
				<p class="description"><?php printf( esc_html__('Optional cookie in %s format, one item per line.', 'code-profiler'), '<code>name=value</code>') ?> <?php printf( esc_html__('%sView example%s', 'code-profiler'), '<a href="'. plugins_url('/static/help/cookies.png', dirname( __FILE__ ) ) .'" target="_blank" rel="noopener noreferrer">', '</a>') ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row" class="row-med"><?php esc_html_e('HTTP headers', 'code-profiler'); ?> <span class="code-profiler-tip" data-tip="<?php esc_attr_e('You can use this field to add custom HTTP headers or even override existing ones (e.g., host, user-agent, accept-language etc). HTTP header names are case-insensitive and Code Profiler will automatically convert them to lowercase. HTTP header values are case-sensitive and only ASCII printable characters are allowed.', 'code-profiler') ?>"></span></th>
			<td>
				<textarea autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" id="custom-headers" name="custom_headers" class="regular-text code" rows="6"><?php echo esc_textarea( $custom_headers ) ?></textarea>
				<p class="description"><?php printf( esc_html__('Optional HTTP header in %s format, one item per line.', 'code-profiler'), '<code>name: value</code>') ?> <?php printf( esc_html__('%sView example%s', 'code-profiler'), '<a href="'. plugins_url('/static/help/custom_headers.png', dirname( __FILE__ ) ) .'" target="_blank" rel="noopener noreferrer">', '</a>') ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row" class="row-med"><?php esc_html_e('File and folder exclusions', 'code-profiler'); ?> <span class="code-profiler-tip" data-tip="<?php esc_attr_e('This option lets you exclude files and folders from the profiling process. It can be a full path, a file or a folder name, or any part of them (substring). Values are case-sensitive and only ASCII printable characters are allowed.', 'code-profiler') ?>"></span></th>
			<td>
				<textarea autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" id="exclusions" name="exclusions" class="regular-text code" rows="6"><?php
					foreach( $exclusions as $item ) {
						echo esc_textarea( $item ) ."\n";
					}
				?></textarea>
			<p class="description"><?php esc_html_e('One item per line.', 'code-profiler') ?> <?php printf( esc_html__('%sView example%s', 'code-profiler'), '<a href="'. plugins_url('/static/help/exclusions.png', dirname( __FILE__ ) ) .'" target="_blank" rel="noopener noreferrer">', '</a>') ?></p>
			</td>
		</tr>
	</table>
</div>
<?php wp_nonce_field('start_profiler_nonce', 'cp_nonce', 0); ?>
<br />
<div>
	<input type="button" class="button button-secondary" id="button-adv-settings" name="adv-settings" value="<?php esc_attr_e('Advanced Options', 'code-profiler') ?>" onclick="cpjs_show_adv_settings();"<?php echo $disabled_button ?> />
	&nbsp;&nbsp;&nbsp;
	<input type="button" class="button button-primary" id="start-profile" name="start-profile" onClick="cpjs_start_profiler();" value="<?php esc_attr_e('Start Profiling', 'code-profiler') ?> »" title="<?php esc_attr_e('Click to start profiling your code.', 'code-profiler') ?>" />
</div>
<div id="cp-progress-div" style="display:none">
	<br />
	<div class="cp-progress-bar"><span id="cp-span-progress" style="width:0%"></span></div>
	<img style="vertical-align:middle;display:none" id="progress-gif" src="<?php echo plugins_url('/static/progress.gif', dirname (__FILE__ ) ) ?>" />&nbsp;&nbsp;<font id="progress-text" style="display:none"><?php esc_html_e('Starting the profiler...', 'code-profiler') ?></font>
</div>
<?php

/**
 * Re-run a profile.
 */
if (! empty( $rerun ) ) {
	echo '<script>window.addEventListener("load", cpjs_start_profiler);</script>';
}

// =====================================================================
// Fetch pages and posts.

function code_profiler_fetch_pages( $home, $cp_options ) {

	$posts = '';

	if ( empty( $cp_options['mem']['post'] ) ) {
		$cp_options['mem']['post'] = $home;
	}

	if ( $cp_options['mem']['post'] == $home ) {
		$pages = sprintf(
			'<option value="%1$s" title="%1$s" selected>%2$s</option>',
			esc_attr( $home ),
			esc_html__('Homepage', 'code-profiler')
		);
	} else {
		$pages = sprintf(
			'<option value="%1$s" title="%1$s">%2$s</option>',
			esc_attr( $home ),
			esc_html__('Homepage', 'code-profiler')
		);
	}
	$args = [
		'posts_per_page' => -1,
		'post_type'      => ['page'],
		'post_status'    => 'publish',
		'orderby'        => 'post_name',
		'order'          => 'ASC'
	];
	$query = new WP_Query( $args );

	if ( $query ) {
		$items = $query->posts;
		foreach( $items as $item ) {
			if ( empty( $item->post_title ) ) {
				$item->post_title = __('Untitled');
			}
			$permalink = get_permalink( $item->ID );

			if ( $cp_options['mem']['post'] == $permalink ) {
				$pages .= sprintf(
					'<option value="%1$s" title="%1$s" selected>%2$s</option>',
					esc_attr( $permalink ),
					esc_html( $item->post_title )
				);
			} else {
				$pages .= sprintf(
					'<option value="%1$s" title="%1$s">%2$s</option>',
					esc_attr( $permalink ),
					esc_html( $item->post_title )
				);
			}
		}
	}

	return sprintf('<optgroup label="%s">%s</optgroup>',
		esc_attr__('Pages', 'code-profiler'),
		$pages
	);
}

// =====================================================================
// Fetch admin pages (backend)

function code_profiler_fetch_admin_pages( $home, $cp_options ) {

	$backend = '';

	if ( empty( $cp_options['mem']['post'] ) ) {
		$cp_options['mem']['post'] = $home;
	}

	if (! empty( $GLOBALS[ 'menu' ] ) ) {
		foreach( $GLOBALS[ 'menu' ] as $menu => $value ) {
			if (! empty( $value[0] ) && substr( $value[2], -4 ) == '.php') {
				// E.g., "Comments", "Plugins" etc
				$value[0] = trim( preg_replace('/\s*<.+$/', '', $value[0] ) );

				if ( $cp_options['mem']['post'] == "{$home}wp-admin/{$value[2]}" ) {
					$backend .= sprintf(
						'<option value="%1$s" title="%1$s" selected>%2$s</option>',
						esc_attr("{$home}wp-admin/{$value[2]}"),
						esc_html( $value[0] )
					);
				} else {
					$backend .= sprintf(
						'<option value="%1$s" title="%1$s">%2$s</option>',
						esc_attr("{$home}wp-admin/{$value[2]}"),
						esc_html( $value[0] )
					);
				}
			}
		}
	}
	if ( empty( $backend ) ) {
		$backend = sprintf(
			'<option value="%1$swp-admin/" title="%1$swp-admin/">%2$s</option>',
			esc_attr( $home ),
			esc_html__('Dashboard', 'code-profiler')
		);

	}
	return $backend;
}

// =====================================================================
// Fetch scheduled tasks.

function code_profiler_fetch_crontasks( $mem ) {

	$cron_list = '';

	$wp_crons = code_profiler_get_crons();

	if ( empty( $wp_crons ) ) {
		$cron_list = sprintf(
			'<option value="0">%s</option>',
			esc_html__('No cron tasks found.', 'code-profiler')
		);
		return $cron_list;
	}

	foreach( $wp_crons as $cron ) {

		if ( $mem == $cron ) {
			$selected = ' selected';
		} else {
			$selected = '';
		}

		$cron_list .= sprintf(
			"<option value='%s'%s>%s</option>",
			esc_attr( $cron ),
			$selected,
			esc_html( $cron )
		);
	}

	return $cron_list;
}

// =====================================================================
// EOF
