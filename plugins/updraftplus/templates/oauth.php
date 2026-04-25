<?php
/**
 * Oauth Callback Modal
 */
if (!defined('UPDRAFTPLUS_DIR')) die('No direct access allowed.');
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
	<head>
		<meta charset="<?php bloginfo('charset'); ?>">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title><?php echo esc_html($label).' '.esc_html__('Authentication', 'updraftplus'); ?></title>
		<style>
		body {
			font-family: sans-serif;
			background: #fff;
			display: flex;
			justify-content: center;
			align-items: center;
			height: 100vh;
			margin: 0;
		}
		.modal {
			padding: 2rem 2.5rem;
			border-radius: 1rem;
			text-align: center;
			max-width: 440px;
			width: 100%;
		}
		.modal img {
			width: 64px;
			height: 64px;
			object-fit: contain;
			margin-bottom: 1.5rem;
		}
		.modal h2 {
			margin: 0 0 .5rem 0;
			font-size: 1.4rem;
			color: #111827;
		}
		.modal p {
			color: #4b5563;
			line-height: 1.5;
		}
		a {
			color: #1d4ed8;
			text-decoration: underline;
		}
		.alert {
			margin-top: 1.5rem;
			padding: 1rem;
			border-radius: .5rem;
			text-align: left;
			font-size: .95rem;
		}
		.alert.success {
			background: #ecfdf5;
			border: 1px solid #a7f3d0;
			color: #065f46;
		}
		.alert.error {
			background: #fef2f2;
			border: 1px solid #fecaca;
			color: #991b1b;
		}
		button {
			background: #b45309;
			color: #fff;
			border: none;
			padding: .8rem 1.5rem;
			border-radius: .5rem;
			cursor: pointer;
			font-size: 1rem;
		}
		button:hover {
			background: #92400e;
		}
		.btn-primary {
			background: #b45309;
			color: #fff;
		}
		.btn-secondary {
			background: #f3f4f6;
			color: #374151;
			margin-left: .5rem;
		}
		.btn-secondary:hover {
			background: #e5e7eb;
		}
		.lead {
			color: #374151;
			margin-top: 1.2rem;
		}
		.hint {
			margin-top: 1rem;
			color: #6b7280;
			font-size: .9rem;
		}
		.actions {
			margin-top: 1rem;
		}
		.countdown {
			font-weight: bold;
		}
		</style>
	</head>
	<body>
		<div class="modal">
			<img src="<?php echo esc_url($logo); ?>" alt="<?php echo esc_attr($label); ?>">
			<h2 id="title">
				<?php
				/* translators: %s: storage label (e.g., Google Drive) */
				printf(esc_html__('%s authentication required', 'updraftplus'), esc_html($label));
				?>
			</h2>
			<p>
				<?php
				echo esc_html__('Your backup data is never sent to us.', 'updraftplus').' ';
				?>
				<br>
				<a href="https://teamupdraft.com/privacy/" target="_blank"><?php esc_html_e('View privacy policy.', 'updraftplus'); ?></a>
			</p>

			<?php if ('success' === $status) { ?>
				<div class="alert success">
					<strong>
						<?php
						/* translators: %s: storage label (e.g., Google Drive) */
						printf(esc_html__('%s connected successfully', 'updraftplus'), esc_html($label));
						?>
					</strong><br>
					<?php
					echo esc_html__('Follow the link below to return to UpdraftPlus and finish setup on your site.', 'updraftplus').' ';
					/* translators: %s: storage label (e.g., Google Drive) */
					printf(
						esc_html__('Once complete, backups will be sent securely to your %s.', 'updraftplus'),
						esc_html($label)
					);
					?>
				</div>

				<p id="desc" class="lead">
					<?php
					printf(
						wp_kses(
							__(
								/* translators: %s: countdown seconds number */
								'This window will close automatically in %s seconds.',
								'updraftplus'
							),
							$kses_allow_tags
						),
						'<span class="countdown" id="seconds">5</span>'
					);
					?>
				</p>

				<div class="actions" id="actions">
					<button class="btn-primary" id="close_now"><?php esc_html_e('Close now', 'updraftplus'); ?></button>
					<button class="btn-secondary" id="stay_open"><?php esc_html_e('Stay here', 'updraftplus'); ?></button>
				</div>

				<div class="hint" id="hint" aria-live="polite" style="display:none;">
					<?php esc_html_e('If the window doesn\'t close automatically, please close this window manually.', 'updraftplus'); ?>
				</div>
			<?php } else { ?>
				<div class="alert error">
					<strong><?php esc_html_e('Something went wrong', 'updraftplus'); ?></strong><br>
					<?php
					printf(
						/* translators: %s: storage label (e.g., Google Drive) */
						esc_html__('Your %s connection wasn\'t completed.', 'updraftplus'),
						esc_html($label)
					);
					echo esc_html__('This can happen if the authorization expired or the browser blocked the redirect.', 'updraftplus');
					?>
				</div>

				<p id="desc" class="lead">
					<?php
					printf(
						wp_kses(
							__(
								/* translators: %s: countdown seconds number */
								'This window will close automatically in %s seconds.',
								'updraftplus'
							),
							$kses_allow_tags
						),
						'<span class="countdown" id="seconds">5</span>'
					);
					?>
				</p>

				<div class="actions" id="actions">
					<button class="btn-primary" id="close_now"><?php esc_html_e('Close now', 'updraftplus'); ?></button>
					<button class="btn-secondary" id="stay_open"><?php esc_html_e('Stay here', 'updraftplus'); ?></button>
				</div>

				<div class="hint" id="hint" aria-live="polite" style="display:none;">
					<?php esc_html_e('If the window doesn\'t close automatically, please close this window manually.', 'updraftplus'); ?>
				</div>
			<?php } ?>
		</div>

		<script>
			(function(){
				let countdown_seconds = 5;
				const seconds_el = document.getElementById('seconds');
				const hint_el = document.getElementById('hint');
				const close_now_btn = document.getElementById('close_now');
				const stay_open_btn = document.getElementById('stay_open');

				function render_seconds(s){ seconds_el.textContent = String(s); }
				render_seconds(countdown_seconds);

				function notify_opener() {
					try {
						if (window.opener && !window.opener.closed) {
							window.opener.postMessage && window.opener.postMessage({ type: 'auth_success' }, '*');
							try { window.opener.focus && window.opener.focus(); } catch (e){}
						}
					} catch (err) {}
				}

				function attempt_close() {
					<?php if ('error' !== $status) { ?>
					notify_opener();
					<?php } ?>
					try { window.close(); } catch (err) {}
					setTimeout(function(){
						if (!window.closed) {
							hint_el.style.display = 'block';
						}
					}, 250);
				}

				let remaining = countdown_seconds;
				const timer = setInterval(function(){
					remaining--;
					if (remaining <= 0) {
						clearInterval(timer);
						render_seconds(0);
						attempt_close();
						return;
					}
					render_seconds(remaining);
				}, 1000);

				close_now_btn.addEventListener('click', function(){
					clearInterval(timer);
					render_seconds(0);
					attempt_close();
				});

				stay_open_btn.addEventListener('click', function(){
					clearInterval(timer);
					hint_el.style.display = 'none';
					document.getElementById('title').textContent = '<?php
					/* translators: %s: storage label (e.g., Google Drive) */
					echo esc_js(sprintf(__('You are authenticated with %s', 'updraftplus'), $label));
					?>';
					document.getElementById('desc').textContent = '<?php echo esc_js(__('You chose to keep this window open.', 'updraftplus')); ?>';
				});

				window.addEventListener('message', function(ev){
					try {
						if (ev && ev.data && ev.data.type === 'close_popup') {
							attempt_close();
						}
					} catch(e){}
				});
			})();
		</script>
	</body>
</html>