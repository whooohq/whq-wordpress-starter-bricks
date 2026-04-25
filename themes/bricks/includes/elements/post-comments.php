<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Element_Post_Comments extends Element {
	public $category = 'single';
	public $name     = 'post-comments';
	public $icon     = 'ti-comments';

	public function get_label() {
		return esc_html__( 'Comments', 'bricks' );
	}

	public function enqueue_scripts() {
		// Comments reply: Move form below comment user wants to reply to
		if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
			wp_enqueue_script( 'comment-reply' );
		}
	}

	public function set_control_groups() {
		$this->control_groups['title'] = [
			'title'    => esc_html__( 'Title', 'bricks' ),
			'tab'      => 'content',
			'required' => [ 'source', '!=', 'wordpress' ],
		];

		$this->control_groups['avatar'] = [
			'title'    => esc_html__( 'Avatar', 'bricks' ),
			'tab'      => 'content',
			'required' => [ 'source', '!=', 'wordpress' ],
		];

		$this->control_groups['comment'] = [
			'title'    => esc_html__( 'Comment', 'bricks' ),
			'tab'      => 'content',
			'required' => [ 'source', '!=', 'wordpress' ],
		];

		$this->control_groups['form'] = [
			'title'    => esc_html__( 'Form', 'bricks' ),
			'tab'      => 'content',
			'required' => [ 'source', '!=', 'wordpress' ],
		];

		$this->control_groups['submitButton'] = [
			'title'    => esc_html__( 'Submit button', 'bricks' ),
			'tab'      => 'content',
			'required' => [ 'source', '!=', 'wordpress' ],
		];
	}

	public function set_controls() {
		// Choose between Bricks and WordPress comments (@since 1.8)
		$this->controls['source'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Source', 'bricks' ),
			'type'        => 'select',
			'inline'      => true,
			'options'     => [
				'bricks'    => 'Bricks',
				'wordpress' => 'WordPress',
			],
			'placeholder' => 'Bricks',
		];

		// Group: title

		$this->controls['title'] = [
			'tab'     => 'content',
			'group'   => 'title',
			'label'   => esc_html__( 'Show title', 'bricks' ),
			'type'    => 'checkbox',
			'default' => true,
		];

		$this->controls['titleTypography'] = [
			'tab'      => 'content',
			'group'    => 'title',
			'label'    => esc_html__( 'Typography', 'bricks' ),
			'type'     => 'typography',
			'css'      => [
				[
					'property' => 'font',
					'selector' => '.comments-title',
				],
			],
			'required' => [ 'title', '!=', '' ],
		];

		// Group: avatar

		$this->controls['avatar'] = [
			'tab'     => 'content',
			'group'   => 'avatar',
			'label'   => esc_html__( 'Show avatar', 'bricks' ),
			'type'    => 'checkbox',
			'default' => true,
		];

		$this->controls['avatarSize'] = [
			'tab'         => 'content',
			'group'       => 'avatar',
			'label'       => esc_html__( 'Size', 'bricks' ),
			'type'        => 'number',
			'units'       => true,
			'placeholder' => 60,
			'css'         => [
				[
					'property' => 'margin-left',
					'selector' => '.depth-2',
				],
				[
					'property' => 'margin-left',
					'selector' => '.depth-3',
				],
			],
			'rerender'    => true,
		];

		$this->controls['avatarBorder'] = [
			'tab'   => 'content',
			'group' => 'avatar',
			'label' => esc_html__( 'Border', 'bricks' ),
			'type'  => 'border',
			'css'   => [
				[
					'property' => 'border',
					'selector' => '.avatar',
				],
			],
		];

		$this->controls['avatarBoxShadow'] = [
			'tab'   => 'content',
			'group' => 'avatar',
			'label' => esc_html__( 'Box shadow', 'bricks' ),
			'type'  => 'box-shadow',
			'css'   => [
				[
					'property' => 'box-shadow',
					'selector' => '.avatar',
				],
			],
		];

		// Group: comment

		$this->controls['commentAuthorTypography'] = [
			'tab'   => 'content',
			'group' => 'comment',
			'label' => esc_html__( 'Author typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.comment-author .fn',
				],
			],
		];

		$this->controls['commentMetaTypography'] = [
			'tab'   => 'content',
			'group' => 'comment',
			'label' => esc_html__( 'Meta typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.comment-meta',
				],
			],
		];

		$this->controls['commentContentTypography'] = [
			'tab'   => 'content',
			'group' => 'comment',
			'label' => esc_html__( 'Content typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.comment-content',
				],
			],
		];

		// FORM

		$this->controls['formTitle'] = [
			'tab'     => 'content',
			'group'   => 'form',
			'label'   => esc_html__( 'Form title', 'bricks' ) . ': ' . esc_html__( 'Show', 'bricks' ),
			'type'    => 'checkbox',
			'default' => true,
		];

		$this->controls['formTitleText'] = [
			'tab'         => 'content',
			'group'       => 'form',
			'label'       => esc_html__( 'Form title', 'bricks' ) . ': ' . esc_html__( 'Text', 'bricks' ),
			'type'        => 'text',
			'placeholder' => esc_html__( 'Leave your comment', 'bricks' ),
			'required'    => [ 'formTitle', '!=', '' ],
		];

		$this->controls['label'] = [
			'tab'     => 'content',
			'group'   => 'form',
			'label'   => esc_html__( 'Label', 'bricks' ) . ': ' . esc_html__( 'Show', 'bricks' ),
			'type'    => 'checkbox',
			'default' => true,
		];

		$this->controls['labelTypography'] = [
			'tab'      => 'content',
			'group'    => 'form',
			'label'    => esc_html__( 'Label', 'bricks' ) . ': ' . esc_html__( 'Typography', 'bricks' ),
			'type'     => 'typography',
			'css'      => [
				[
					'property' => 'font',
					'selector' => 'label',
				],
			],
			'required' => [ 'label', '!=', '' ],
		];

		$this->controls['placeholderTypography'] = [
			'tab'      => 'content',
			'group'    => 'form',
			'label'    => esc_html__( 'Placeholder typography', 'bricks' ),
			'type'     => 'typography',
			'css'      => [
				[
					'property' => 'font',
					'selector' => '::placeholder',
				],
			],
			'required' => [ 'label', '=', '' ],
		];

		// COOKIES

		$this->controls['cookiesSep'] = [
			'tab'   => 'content',
			'group' => 'form',
			'label' => esc_html__( 'Cookie consent', 'bricks' ),
			'type'  => 'separator',
		];

		$this->controls['cookies'] = [
			'tab'   => 'content',
			'group' => 'form',
			'label' => esc_html__( 'Cookie consent', 'bricks' ) . ': ' . esc_html__( 'Show', 'bricks' ),
			'type'  => 'checkbox',
		];

		$this->controls['cookiesRequired'] = [
			'tab'      => 'content',
			'group'    => 'form',
			'label'    => esc_html__( 'Cookie consent', 'bricks' ) . ': ' . esc_html__( 'Required', 'bricks' ),
			'type'     => 'checkbox',
			'required' => [ 'cookies', '!=', '' ],
		];

		$this->controls['cookiesText'] = [
			'tab'      => 'content',
			'group'    => 'form',
			'label'    => esc_html__( 'Cookie consent', 'bricks' ) . ': ' . esc_html__( 'Text', 'bricks' ),
			'type'     => 'text',
			'required' => [ 'cookies', '!=', '' ],
		];

		// FIELDS

		$this->controls['fieldsSep'] = [
			'tab'   => 'content',
			'group' => 'form',
			'label' => esc_html__( 'Fields', 'bricks' ),
			'type'  => 'separator',
		];

		$this->controls['fieldKeys'] = [
			'tab'         => 'content',
			'group'       => 'form',
			'type'        => 'select',
			'options'     => [
				'author' => esc_html__( 'Name', 'bricks' ),
				'email'  => esc_html__( 'Email', 'bricks' ),
				'url'    => esc_html__( 'Website', 'bricks' ),
			],
			'multiple'    => true,
			'placeholder' => esc_html__( 'Name', 'bricks' ) . ', ' . esc_html__( 'Email', 'bricks' ) . ', ' . esc_html__( 'Website', 'bricks' ),
		];

		// FIELD

		$this->controls['fieldSep'] = [
			'tab'   => 'content',
			'group' => 'form',
			'label' => esc_html__( 'Field', 'bricks' ),
			'type'  => 'separator',
		];

		$this->controls['fieldBackgroundColor'] = [
			'tab'   => 'content',
			'group' => 'form',
			'label' => esc_html__( 'Background color', 'bricks' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.form-group input',
				],
				[
					'property' => 'background-color',
					'selector' => '.form-group textarea',
				],
			],
		];

		$this->controls['fieldBorder'] = [
			'tab'   => 'content',
			'group' => 'form',
			'label' => esc_html__( 'Border', 'bricks' ),
			'type'  => 'border',
			'css'   => [
				[
					'property' => 'border',
					'selector' => '.form-group input',
				],
				[
					'property' => 'border',
					'selector' => '.form-group textarea',
				],
			],
		];

		$this->controls['fieldTypography'] = [
			'tab'   => 'content',
			'group' => 'form',
			'label' => esc_html__( 'Typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.form-group input',
				],
				[
					'property' => 'font',
					'selector' => '.form-group textarea',
				],
			],
		];

		// Group: submitButton

		$this->controls['submitButtonText'] = [
			'tab'            => 'content',
			'group'          => 'submitButton',
			'label'          => esc_html__( 'Text', 'bricks' ),
			'type'           => 'text',
			'inline'         => true,
			'hasDynamicData' => false,
			'placeholder'    => esc_html__( 'Submit Comment', 'bricks' ),
		];

		$this->controls['submitButtonSize'] = [
			'tab'         => 'content',
			'group'       => 'submitButton',
			'label'       => esc_html__( 'Size', 'bricks' ),
			'type'        => 'select',
			'options'     => $this->control_options['buttonSizes'],
			'inline'      => true,
			'placeholder' => esc_html__( 'Default', 'bricks' ),
		];

		$this->controls['submitButtonStyle'] = [
			'tab'         => 'content',
			'group'       => 'submitButton',
			'label'       => esc_html__( 'Style', 'bricks' ),
			'type'        => 'select',
			'options'     => $this->control_options['styles'],
			'inline'      => true,
			'placeholder' => esc_html__( 'None', 'bricks' ),
			'default'     => 'primary',
		];

		$this->controls['submitButtonBackgroundColor'] = [
			'tab'   => 'content',
			'group' => 'submitButton',
			'label' => esc_html__( 'Background', 'bricks' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.bricks-button',
				],
			],
		];

		$this->controls['submitButtonBorder'] = [
			'tab'   => 'content',
			'group' => 'submitButton',
			'label' => esc_html__( 'Border', 'bricks' ),
			'type'  => 'border',
			'css'   => [
				[
					'property' => 'border',
					'selector' => '.bricks-button',
				],
			],
		];

		$this->controls['submitButtonTypography'] = [
			'tab'   => 'content',
			'group' => 'submitButton',
			'label' => esc_html__( 'Typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.bricks-button',
				],
			],
		];
	}

	public function render() {
		$settings = $this->settings;
		$source   = $settings['source'] ?? 'bricks';

		// STEP: Render WordPress comments (comments_template() handles the rest)
		if ( $source === 'wordpress' ) {
			ob_start();
			comments_template();
			$comments_template = ob_get_clean();

			echo "<div {$this->render_attributes( '_root' )}>" . $comments_template . '</div>';
			return;
		}

		// STEP: Render Bricks comments
		global $post;

		$post = get_post( $this->post_id );

		if ( post_password_required() ) {
			return $this->render_element_placeholder(
				[
					'icon-class' => 'ti-key',
					'title'      => esc_html__( 'Password required.', 'bricks' ),
				]
			);
		}

		if ( ! comments_open() ) {
			return $this->render_element_placeholder(
				[
					'icon-class' => 'ti-comments',
					'title'      => esc_html__( 'Comments are disabled.', 'bricks' ),
				]
			);
		}

		$title_tag = ! empty( $settings['titleTag'] ) ? $settings['titleTag'] : 'h3';

		echo "<div {$this->render_attributes( '_root' )}>";
		?>
		<div id="comments">
			<div class="bricks-comments-inner">
				<?php
				if ( get_comments_number() ) {
					// Set comment pagination
					$paged = get_query_var( 'cpage' ) ? get_query_var( 'cpage' ) : 1; // cpage = comments pagination query var

					if ( ! get_query_var( 'cpage' ) ) {
						set_query_var( 'cpage', $paged );
					}

					// Fetch approved comments for the post
					$comments = get_comments(
						[
							'post_id' => get_the_ID(),
							'status'  => [ 'approve', 'hold' ],
						]
					);

					// Get Comments per page
					$comments_per_page    = get_option( 'comments_per_page' );
					$comments_total_pages = get_comment_pages_count( $comments, $comments_per_page );
					?>

					<?php if ( isset( $settings['title'] ) ) { ?>
					<h3 class="comments-title">
						<?php
						// translators: %1$s: number of comments
						printf( esc_html( _n( '1 comment', '%1$s comments', get_comments_number(), 'bricks' ) ), number_format_i18n( get_comments_number() ) );
						?>
					</h3>
					<?php } ?>
					<?php if ( $comments_total_pages > 1 && get_option( 'page_comments' ) ) { ?>
					<nav class="navigation comment-navigation" id="comment-nav-above" role="navigation">
						<h2 class="screen-reader-text"><?php esc_html_e( 'Comment navigation', 'bricks' ); ?></h2>
						<div class="nav-links">
							<div class="nav-previous"><?php previous_comments_link( esc_html__( 'Older Comments', 'bricks' ) ); ?></div>
							<div class="nav-next"><?php next_comments_link( esc_html__( 'Newer Comments', 'bricks' ), $comments_total_pages ); ?></div>
						</div>
					</nav>
					<?php } ?>

					<?php
					echo '<ul class="comment-list' . ( ! isset( $settings['avatar'] ) ? ' no-avatar' : '' ) . '">';

					wp_list_comments(
						[
							'walker'            => null,
							'max_depth'         => '10',
							'style'             => 'ul',
							'callback'          => 'bricks_list_comments',
							'end-callback'      => null,
							'type'              => 'comment',
							'reply_text'        => esc_html__( 'Reply', 'bricks' ),
							'page'              => $paged,
							'per_page'          => $comments_per_page,
							'avatar_size'       => isset( $settings['avatarSize'] ) ? intval( $settings['avatarSize'] ) : 60,
							// TODO: Fix ordering for main elements
							// NOTE: This will need to be integrated with the WordPress settings
							'reverse_top_level' => '',
							'reverse_children'  => '',
							'format'            => 'html5',
							'short_ping'        => true,
							'echo'              => true,
							// Custom settings
							'bricks_avatar'     => isset( $settings['avatar'] ),
						],
						$comments
					);
					?>
					</ul>

					<?php if ( $comments_total_pages > 1 && get_option( 'page_comments' ) ) { ?>
					<nav class="navigation comment-navigation" id="comment-nav-below" role="navigation">
						<h2 class="screen-reader-text"><?php esc_html_e( 'Comment navigation', 'bricks' ); ?></h2>

						<div class="nav-links">
							<div class="nav-previous"><?php previous_comments_link( esc_html__( 'Older Comments', 'bricks' ) ); ?></div>
							<div class="nav-next"><?php next_comments_link( esc_html__( 'Newer Comments', 'bricks' ), $comments_total_pages ); ?></div>
						</div>
					</nav>
						<?php
					}
				}

				// No comments
				// else {
				// echo '<h3 class="comments-title">' . esc_html__( 'No comments.', 'bricks' ) . '</h3>';
				// }

				// Comments are closed
				if ( ! comments_open() && get_comments_number() && post_type_supports( get_post_type(), 'comments' ) ) {
					echo '<p class="no-comments">' . esc_html__( 'Comments are closed.', 'bricks' ) . '</p>';
				}

				$commenter           = wp_get_current_commenter();
				$required_name_email = get_option( 'require_name_email' );
				$required_attribute  = $required_name_email ? ' required' : '';
				$required_star       = $required_name_email ? ' *' : '';

				$field_keys = $settings['fieldKeys'] ?? [ 'author', 'email', 'url' ];
				$field_keys = is_string( $field_keys ) ? [ $field_keys ] : $field_keys;
				$field_keys = apply_filters( 'comment_form_default_fields', $field_keys );

				$fields = [];

				foreach ( $field_keys as $field ) {
					$field_html = '<div class="form-group">';

					switch ( $field ) {
						case 'author':
							if ( isset( $settings['label'] ) ) {
								$field_html .= '<label for="author">' . esc_html_x( 'Name', 'Author name', 'bricks' ) . $required_star . '</label>';
								$field_html .= '<input id="author" name="author" type="text" value="' . esc_attr( $commenter['comment_author'] ) . '"' . $required_attribute . ' />';
							} else {
								$field_html .= '<input id="author" name="author" type="text" value="' . esc_attr( $commenter['comment_author'] ) . '"' . $required_attribute . ' placeholder="' . esc_html_x( 'Name', 'Author name', 'bricks' ) . $required_star . '" />';
							}
							break;

						case 'email':
							if ( isset( $settings['label'] ) ) {
								$field_html .= '<label for="email">' . esc_html__( 'Email', 'bricks' ) . $required_star . '</label>';
								$field_html .= '<input id="email" name="email" type="email" value="' . esc_attr( $commenter['comment_author_email'] ) . '"' . $required_attribute . ' />';
							} else {
								$field_html .= '<input id="email" name="email" type="email" value="' . esc_attr( $commenter['comment_author_email'] ) . '"' . $required_attribute . ' placeholder="' . esc_html__( 'Email', 'bricks' ) . $required_star . '" />';
							}
							break;

						case 'url':
							if ( isset( $settings['label'] ) ) {
								$field_html .= '<label for="url">' . esc_html__( 'Website', 'bricks' ) . '</label>';
								$field_html .= '<input id="url" name="url" type="url" value="' . esc_attr( $commenter['comment_author_url'] ) . '" />';
							} else {
								$field_html .= '<input id="url" name="url" type="url" value="' . esc_attr( $commenter['comment_author_url'] ) . '" placeholder="' . esc_html__( 'Website', 'bricks' ) . '" />';
							}
							break;
					}

					$field_html      .= '</div>';
					$fields[ $field ] = $field_html;
				}

				if ( isset( $settings['label'] ) ) {
					$comment_field =
					'<div class="form-group">
						<label for="comment">' . esc_html__( 'Comment', 'bricks' ) . ' *</label>
						<textarea id="comment" name="comment" cols="45" rows="8" required></textarea>
					</div>';
				} else {
					$comment_field =
					'<div class="form-group">
						<textarea id="comment" name="comment" cols="45" rows="8" required placeholder="' . esc_html__( 'Comment', 'bricks' ) . ' *"></textarea>
					</div>';
				}

				$submit_text = isset( $settings['submitButtonText'] ) ? $settings['submitButtonText'] : esc_html__( 'Submit Comment', 'bricks' );

				$submit_button_classes = [ 'bricks-button' ];

				if ( isset( $settings['submitButtonStyle'] ) ) {
					$submit_button_classes[] = 'bricks-background-' . $settings['submitButtonStyle'];
				}

				if ( isset( $settings['submitButtonSize'] ) ) {
					$submit_button_classes[] = $settings['submitButtonSize'];
				}

				// Show cookie consent field to Bricks form (@since 1.8)
				if ( ! empty( $settings['cookies'] ) ) {
					$cookies_text = esc_html__( 'Save my name, email, and website in this browser for the next time I comment.', 'bricks' );

					if ( ! empty( $settings['cookiesText'] ) ) {
						$cookies_text = $settings['cookiesText'];
					}

					$cookies_required = isset( $settings['cookiesRequired'] ) ? ' required' : '';

					$fields['cookies'] =
					'<div class="form-group comment-form-cookies-consent">
						<input id="wp-comment-cookies-consent" name="wp-comment-cookies-consent" type="checkbox" value="yes"' . $cookies_required . '>
						<label for="wp-comment-cookies-consent">' . $cookies_text . '</label>
					</div>';
				} else {
					$fields['cookies'] = '';
				}

				$custom_args = [
					'title_reply_before'   => '<h4 id="reply-title" class="comment-reply-title">',
					'title_reply_after'    => '</h4>',
					'cancel_reply_link'    => esc_html__( '(Cancel Reply)', 'bricks' ),
					'label_submit'         => $submit_text,
					'comment_notes_before' => '',
					'comment_notes_after'  => '',
					'fields'               => $fields,
					'comment_field'        => $comment_field,
					'class_submit'         => implode( ' ', $submit_button_classes ),
				];

				if ( isset( $settings['formTitle'] ) ) {
					if ( ! empty( $settings['formTitleText'] ) ) {
						$custom_args['title_reply'] = $settings['formTitleText'];
					} else {
						$custom_args['title_reply'] = get_comments_number() ? esc_html__( 'Leave your comment', 'bricks' ) : esc_html__( 'Leave the first comment', 'bricks' );
					}
				} else {
					$custom_args['title_reply'] = '';
				}

				comment_form( $custom_args );
				?>
			</div>
		</div>
		<?php
		echo '</div>';
	}
}
