<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Feedback {
	public function __construct() {
		add_action( 'admin_enqueue_scripts', [ $this, 'add_feedback_script' ] );
		add_action( 'admin_footer-themes.php', [ $this, 'render_feedback_form' ] );
	}

	/**
	 * Load feedback script on themes.php admin page only
	 */
	public function add_feedback_script( $hook_suffix ) {
		if ( $hook_suffix === 'themes.php' ) {
			wp_enqueue_script( 'bricks-feedback', BRICKS_URL_ASSETS . 'js/feedback.min.js', [ 'jquery' ], filemtime( BRICKS_PATH_ASSETS . 'js/feedback.min.js' ), true );
		}
	}

	/**
	 * Render feedback HTML on themes.php admin page only
	 */
	public function render_feedback_form() { ?>
	<div id="bricks-feedback-form-wrapper" style="display: none">
	  <div id="bricks-feedback-form-inner">
			<div class="bricks-title-wrapper">
				<h2 class="bricks-title"><?php esc_html_e( 'Quick Feedback', 'bricks' ); ?></h2>
				<i class="dashicons dashicons-no close"></i>
			</div>

			<p class="bricks-description"><?php esc_html_e( 'Before you deactivate Bricks could you let me know why? I\'d love to incorporate your feedback to improve Bricks. Thank you so much!', 'bricks' ); ?></p>

				<?php
				$reasons = [
					'no_longer_needed'    => [
						'label' => esc_html__( 'I no longer need Bricks', 'bricks' ),
					],

					'found_better_plugin' => [
						'label'    => esc_html__( 'I found a better site builder', 'bricks' ),
						'textarea' => esc_html__( 'What is the name of this site builder? And why did you choose it?', 'bricks' ),
					],

					'how_to_use'          => [
						'label' => esc_html__( 'I don\'t know how to use this Bricks', 'bricks' ),
						'text'  => sprintf(
							// translators: %1$s: Bricks Academy link, %2$s: email link
							esc_html__( 'Did you explore the %1$s? Or get in touch with me via %2$s?', 'bricks' ),
							'<a href="https://academy.bricksbuilder.io" target="_blank" rel="noopener">' . esc_html__( 'Bricks Academy', 'bricks' ) . '</a>',
							'<a href="https://bricksbuilder.io/contact/" target="_blank" rel="noopener">' . esc_html__( 'email', 'bricks' ) . '</a>'
						),
					],

					'temporary'           => [
						'label' => esc_html__( 'It\'s just a temporary deactivation', 'bricks' ),
					],

					'other'               => [
						'label'    => esc_html__( 'Other', 'bricks' ),
						'textarea' => esc_html__( 'Please share your reason(s) for deactivation Bricks. The more details, the better :)', 'bricks' ),
					],
				];
				?>

			<form id="bricks-feedback-form" method="post">
				<?php foreach ( $reasons as $key => $value ) { ?>
				<fieldset>
				<div class="reason">
					<input type="radio" name="bricks_reason" id="bricks_reason_<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $key ); ?>">
					<label for="bricks_reason_<?php echo esc_attr( $key ); ?>"><?php echo esc_attr( $value['label'] ); ?></label>
				</div>

					<?php if ( isset( $value['input'] ) ) { ?>
				<input class="bricks_reason_<?php echo esc_attr( $key ); ?>" type="text" name="bricks_reason_<?php echo esc_attr( $key ); ?>" placeholder="<?php echo esc_attr( $value['input'] ); ?>">
				<?php } ?>

					<?php if ( isset( $value['text'] ) ) { ?>
				<p class="bricks_reason_<?php echo esc_attr( $key ); ?>"><?php echo ( $value['text'] ); ?></p>
				<?php } ?>

					<?php if ( isset( $value['textarea'] ) ) { ?>
				<textarea class="bricks_reason_<?php echo esc_attr( $key ); ?>" rows="3" name="bricks_reason_<?php echo esc_attr( $key ); ?>" placeholder="<?php echo esc_attr( $value['textarea'] ); ?>"></textarea>
				<?php } ?>
				</fieldset>
				<?php } ?>

				<div id="bricks-feedback-form-sumbit-wrapper">
					<button class="button button-primary" id="bricks-feedback-submit"><?php esc_html_e( 'Submit & Deactivate', 'bricks' ); ?></button>
					<button class="button button-secondary" id="bricks-feedback-skip"><?php esc_html_e( 'Skip & Deactivate', 'bricks' ); ?></button>
					<input type="hidden" name="referer" value="<?php echo get_site_url(); ?>">
					<input type="hidden" name="version" value="<?php echo BRICKS_VERSION; ?>">
				</div>
			</form>
	  </div>
	</div>
		<?php
	}
}
