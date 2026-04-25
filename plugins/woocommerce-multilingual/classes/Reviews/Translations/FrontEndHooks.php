<?php

namespace WCML\Reviews\Translations;

use IWPML_Action;
use WPML\FP\Obj;

class FrontEndHooks implements IWPML_Action {

	const CONTEXT             = 'wcml-reviews';
	const COMMENT_TYPES       = [ self::COMMENT_TYPE_REVIEW, self::COMMENT_TYPE_REPLY ];
	const COMMENT_TYPE_REVIEW = 'review';
	const COMMENT_TYPE_REPLY  = 'comment';
	const HOOK_ENABLE         = 'wcml_enable_product_review_translation';

	public function add_hooks() {
		/**
		 * Allows disabling product reviews translation.
		 *
		 * @param bool $true
		 */
		if ( apply_filters( self::HOOK_ENABLE, true ) ) {
			add_action( 'wp_insert_comment', [ $this, 'insertCommentAction' ], 10, 2 );
			add_action( 'woocommerce_review_before', [ $this, 'translateReview' ] );
		}
	}

	/**
	 * @param int         $commentId
	 * @param \WP_Comment $comment
	 */
	public function insertCommentAction( $commentId, $comment ) {
		self::registerReviewString( $comment );
	}

	/**
	 * @param \WP_Comment|\stdClass $comment
	 */
	public function translateReview( $comment ) {
		if ( self::isNonEmptyReview( $comment ) ) {
			if ( \WCML_Comments::is_translated( $comment ) ) {
				return;
			}

			$reviewTranslation = apply_filters(
				'wpml_translate_single_string',
				$comment->comment_content,
				self::CONTEXT,
				self::getReviewStringName( $comment )
			);

			if ( $reviewTranslation !== $comment->comment_content ) {
				$comment->is_translated   = true;
				$comment->comment_content = $reviewTranslation;
			}
		}
	}

	/**
	 * @param \WP_Comment|\stdClass $review
	 */
	public static function registerReviewString( $review ) {
		if ( self::isNonEmptyReview( $review ) ) {
			$language = Obj::prop( 'language_code', $review ) ?: apply_filters( 'wpml_current_language', null );
			do_action(
				'wpml_register_single_string',
				self::CONTEXT,
				self::getReviewStringName( $review ),
				$review->comment_content,
				false,
				$language
			);
		}
	}

	/**
	 * @param \WP_Comment|\stdClass $comment
	 */
	private static function isNonEmptyReview( $comment ): bool {
		if ( ! Obj::prop( 'comment_content', $comment ) ) {
			return false;
		}

		return in_array( Obj::prop( 'comment_type', $comment ), self::COMMENT_TYPES, true );
	}

	/**
	 * @param \WP_Comment|\stdClass $review
	 *
	 * @return string (e.g. "product-123-review-456")
	 */
	private static function getReviewStringName( $review ): string {
		return 'product-' . \WCML_Comments::getOriginalPostId( $review ) . '-review-' . Obj::prop( 'comment_ID', $review );
	}
}
