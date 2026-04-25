<?php
/**
 * Comments list
 *
 * @since 1.0
 */
function bricks_list_comments( $comment, $args, $depth ) {
	$GLOBALS['comment'] = $comment; // phpcs:ignore

	// Comment is unapproved & the user is not an admin
	if ( $comment->comment_approved == '0' && ! current_user_can( 'manage_options' ) ) {
		// Return: Not logged in OR logged-in, but not the comment author (@since 1.9.2)
		if ( ! get_current_user_id() || get_current_user_id() != $comment->user_id ) {
			return;
		}
	}

	if ( $args['style'] === 'div' ) {
		$tag       = 'div';
		$add_below = 'comment';
	} else {
		$tag       = 'li';
		$add_below = 'div-comment';
	}
	?>

	<<?php echo esc_html( $tag ); ?> <?php comment_class( empty( $args['has_children'] ) ? '' : 'parent' ); ?> id="comment-<?php comment_ID(); ?>">
		<?php if ( $args['style'] !== 'div' ) { ?>
		<div id="div-comment-<?php comment_ID(); ?>" class="comment-body">
		<?php } ?>

			<?php if ( $args['bricks_avatar'] == true ) { ?>
			<div class="comment-avatar">
				<?php
				if ( $args['avatar_size'] != 0 ) {
					echo get_avatar(
						$comment,
						$args['avatar_size'],
						'',
						'',
						[ 'class' => 'css-filter' ]
					);
				}
				?>

				<?php
				$commentator = get_comment();
				if ( user_can( $commentator->user_id, 'manage_options' ) ) {
					?>
				<div class="administrator-badge" data-balloon="<?php esc_attr_e( 'Admin', 'bricks' ); ?>" data-balloon-pos="top">A</div>
				<?php } ?>
			</div>
			<?php } ?>

			<div class="comment-data">
				<div class="comment-author vcard">
					<h5 class="fn"><?php echo get_comment_author_link(); ?></h5>

					<?php if ( $comment->comment_approved == '0' ) { ?>
						<?php
						if ( get_current_user_id() == $comment->user_id ) {
							?>
							<em class="comment-awaiting-moderation"><?php esc_html_e( 'Your comment is awaiting moderation. This is a preview; your comment will be visible after it has been approved.', 'bricks' ); ?></em><br />
							<?php
						} elseif ( current_user_can( 'manage_options' ) || current_user_can( 'edit_posts' ) ) {
							?>
							<em class="comment-awaiting-moderation"><?php esc_html_e( 'This comment is awaiting moderation.', 'bricks' ); ?></em><br />
							<?php
						}
						?>
					<?php } ?>

					<div class="comment-meta">
						<?php
						// translators: %s: Human time diff
						$timestamp = sprintf( __( '%s ago', 'bricks' ), human_time_diff( get_comment_time( 'U' ), current_time( 'timestamp' ) ) );

						// NOTE: Undocumented
						$timestamp = apply_filters( 'bricks/comments/timestamp', $timestamp, $comment );

						echo '<a href="' . get_comment_link() . '"><span>' . $timestamp . '</span></a>';
						?>

						<?php if ( comments_open() ) { ?>
						<span class="reply">
							<?php
							comment_reply_link(
								array_merge(
									$args,
									[
										'add_below' => $add_below,
										'depth'     => $depth,
										'max_depth' => $args['max_depth']
									]
								)
							);
							?>
						</span>
						<?php } ?>
					</div>
				</div>

				<div class="comment-content">
					<?php comment_text(); ?>
				</div>
			</div>
		<?php if ( $args['style'] !== 'div' ) { ?>
		</div>
			<?php
		}
}

/**
 * Move comment form textarea to the bottom
 *
 * @since 1.0
 */
function bricks_comment_form_fields_order( $fields ) {
	$comment_field = $fields['comment'];
	unset( $fields['comment'] );
	$fields['comment'] = $comment_field;

	return $fields;
}
add_filter( 'comment_form_fields', 'bricks_comment_form_fields_order' );
