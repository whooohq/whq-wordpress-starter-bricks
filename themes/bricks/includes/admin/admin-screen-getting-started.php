<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?>

<div class="wrap bricks-admin-wrapper getting-started">
	<h1 class="admin-notices-placeholder"></h1>

	<div class="bricks-admin-inner">
		<h1 class="title">
			<?php esc_html_e( 'Welcome to Bricks', 'bricks' ); ?>
			<span class="badge" title="<?php esc_attr_e( 'Installed version', 'bricks' ); ?>"><?php echo BRICKS_VERSION; ?></span>
		</h1>

		<div class="box-wrapper" data-step="1">
			<p class="bricks-admin-lead">
				<?php esc_html_e( 'Although it\'s super tempting to start editing with Bricks right away, you\'ll have a much better experience quickly going over the "Getting Started" section of the Bricks Academy on how to use Bricks most productively.', 'bricks' ); ?>
			</p>

			<p class="buttons-wrapper">
				<a href="https://academy.bricksbuilder.io/topic/getting-started/" target="_blank" class="button button-primary button-hero"><?php esc_html_e( 'Getting Started', 'bricks' ); ?></a>
				<a href="https://academy.bricksbuilder.io/article/editing-with-bricks/" target="_blank" class="button button-secondary button-hero"><?php esc_html_e( 'How Editing With Bricks Works', 'bricks' ); ?></a>
			</p>
		</div>

		<div class="box-wrapper" data-step="2">
			<p class="bricks-admin-lead">
				<?php esc_html_e( 'Once you are familiar with how Bricks works its a good idea to learn about templates. This is how you create your website header, footer and any other type of reuseable content such as blog post layouts, archives, your search result and error pages.', 'bricks' ); ?>
			</p>

			<p class="bricks-admin-lead">
				<?php esc_html_e( 'The "Features" articles covers topics such as the Unsplash integration, custom code (CSS & JS), gradients, sidebars, shape dividers, global elements, etc.', 'bricks' ); ?>
			</p>

			<p class="buttons-wrapper">
				<a href="https://academy.bricksbuilder.io/topic/templates/" target="_blank" class="button button-primary button-hero"><?php esc_html_e( 'Templates', 'bricks' ); ?></a>
				<a href="https://academy.bricksbuilder.io/topic/features/" target="_blank" class="button button-secondary button-hero"><?php esc_html_e( 'Features', 'bricks' ); ?></a>
			</p>
		</div>

		<div class="box-wrapper" data-step="3">
			<p class="bricks-admin-lead">
				<?php esc_html_e( 'In case you are a developer you can customize Bricks even further with custom hooks, filters, or by creating your own elements.', 'bricks' ); ?>
			</p>

			<p class="buttons-wrapper">
				<a href="https://academy.bricksbuilder.io/collection/developer/" target="_blank" class="button button-primary button-hero"><?php esc_html_e( 'Developer', 'bricks' ); ?></a>
				<a href="https://academy.bricksbuilder.io/article/create-your-own-elements/" target="_blank" class="button button-secondary button-hero"><?php esc_html_e( 'Create your own elements', 'bricks' ); ?></a>
			</p>
		</div>

		<div class="box-wrapper" data-step="4">
			<p class="bricks-admin-lead">
				<?php esc_html_e( 'For questions about Bricks please send an email to help@bricksbuilder.io. If you want to know what is currently in development head over to our public roadmap. There you can also submit your own feature requests for others to upvote and comment on.', 'bricks' ); ?>
			</p>

			<p class="buttons-wrapper">
				<a href="https://bricksbuilder.io/contact/" target="_blank" class="button button-primary button-hero"><?php esc_html_e( 'Get In Touch', 'bricks' ); ?></a>
				<a href="https://bricksbuilder.io/roadmap/" target="_blank" class="button button-secondary button-hero"><?php esc_html_e( 'Roadmap', 'bricks' ); ?></a>
			</p>
		</div>

	</div>
</div>
