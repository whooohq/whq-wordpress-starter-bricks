<?php
namespace Bricks;

$post_id             = get_the_ID();
$post_header_classes = 'bricks-post-header';
$post_header_style   = '';

if ( has_post_thumbnail( $post_id ) ) {
	$post_header_classes .= ' has-image';
	$post_header_style    = ' style="background-image: url(' . get_the_post_thumbnail_url( $post_id, BRICKS_DEFAULT_IMAGE_SIZE ) . ')"';
}

ob_start();
?>
<div class="<?php echo $post_header_classes; ?>"<?php echo $post_header_style; ?>>
	<div class="brxe-container">
		<?php
		$post_title = new Element_Post_Title(
			[
				'settings' => [
					'tag' => 'h1',
				],
			]
		);
		$post_title->load();
		$post_title->init();

		$post_meta = new Element_Post_Meta(
			[
				'settings' => [
					'meta'      => [
						[ 'dynamicData' => '{author_name}' ],
						[ 'dynamicData' => '{post_date}' ],
						[ 'dynamicData' => '{post_comments}' ],
					],
					'separator' => '&middot;',
				]
			]
		);

		$post_meta->load();
		$post_meta->init();
		?>
	</div>
</div>

<article class="brxe-container">
	<?php
	// Password protected
	if ( post_password_required( get_the_ID() ) ) {
		echo get_the_password_form( get_the_ID() );
	} else {
		$post_content = new Element_Post_Content();
		$post_content->load();
		$post_content->init();
		?>

		<?php
		$post_author = new Element_Post_Author(
			[
				'settings' => [
					'avatar'  => true,
					'name'    => true,
					'website' => true,
					'bio'     => true,
				]
			]
		);

		$post_author->load();
		$post_author->init();
		?>

		<div class="bricks-post-meta-wrapper">
			<?php
			$post_tags = new Element_Post_Taxonomy(
				[
					'settings' => [
						'style' => 'dark',
					],
				]
			);

			$post_tags->load();
			$post_tags->init();
			?>

			<?php
			$post_sharing = new Element_Post_Sharing(
				[
					'settings' => [
						'items'       => [
							[ 'service' => 'facebook' ],
							[ 'service' => 'twitter' ],
							[ 'service' => 'google' ],
							[ 'service' => 'linkedin' ],
							[ 'service' => 'pinterest' ],
							[ 'service' => 'email' ],
						],
						'brandColors' => true,
					],
				]
			);

			$post_sharing->load();
			$post_sharing->init();
			?>
		</div>

		<?php
		echo '<h3>' . esc_html__( 'Related posts', 'bricks' ) . '</h3>';

		$related_posts = new Element_Related_Posts(
			[
				'name'     => 'related-posts',
				'settings' => [
					'content' => [
						[
							'dynamicData' => '{post_title:link}',
							'tag'         => 'h3',
						],
						[ 'dynamicData' => '{post_date}' ],
					],
				],
			]
		);

		$related_posts->load();
		$related_posts->init();
		?>

		<?php
		$post_comments = new Element_Post_Comments(
			[
				'settings' => [
					'title'             => true,
					'avatar'            => true,
					'submitButtonStyle' => 'primary',
				],
			]
		);

		$post_comments->load();
		$post_comments->init();
		?>

		<?php
		$post_navigation = new Element_Post_Navigation(
			[
				'settings' => [
					'image' => true,
					'title' => true,
					'label' => true,
				],
			]
		);

		$post_navigation->load();
		$post_navigation->init();
	}
	?>
</article>

<?php
$attributes = [ 'class' => 'layout-default' ];

$html = ob_get_clean();

Frontend::render_content( [], $attributes, $html );
