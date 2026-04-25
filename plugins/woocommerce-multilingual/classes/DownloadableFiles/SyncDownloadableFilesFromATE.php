<?php

namespace WCML\DownloadableFiles;

use WCML\TranslationJob\Hooks;

class SyncDownloadableFilesFromATE {

	public function add_hooks() {
		add_action( 'wpml_pro_translation_completed', [
			$this,
			'save_downloadable_files_fields_translations',
		], 20, 3 );
	}

	/**
	 * @param int       $post_id
	 * @param array     $fields
	 * @param \stdClass $job
	 */
	public function save_downloadable_files_fields_translations( $post_id, $fields, $job ) {
		if ( ! Hooks::isProduct( $job ) ) {
			return null;
		}

		$groupedDownloadableFiles = $this->groupDownloadableFilesByProductIdConsideringProductVariants( $post_id, $fields, $job->language_code );

		foreach ( $groupedDownloadableFiles as $product_id => $downloadableFiles ) {
			$needUpdate     = false;
			$orig_file_path = maybe_unserialize( get_post_meta( $product_id, \WCML_Downloadable_Products::DOWNLOADABLE_FILES_META, true ) );

			if ( ! is_array( $orig_file_path ) ) {
				$orig_file_path = [];
			}

			foreach ( $downloadableFiles as $downloadableFileId => $downloadableFile ) {
				$id = str_replace( \WCML_Synchronize_Product_Data::CUSTOM_FIELD_KEY_SEPARATOR, '-', $downloadableFileId );

				$translatedDownloadableFileData = [
					'name' => $downloadableFile['name'],
					'file' => $downloadableFile['file'],
				];

				if ( isset( $orig_file_path[ $id ] ) ) {
					$translatedDownloadableFileData = array_merge( $orig_file_path[ $id ], $translatedDownloadableFileData );
				}

				$orig_file_path[ $id ] = $translatedDownloadableFileData;
				$needUpdate            = true;
			}

			if ( $needUpdate ) {
				update_post_meta( $product_id, \WCML_Downloadable_Products::DOWNLOADABLE_FILES_META, $orig_file_path );
			}
		}
	}

	/**
	 * @param int    $product_id
	 * @param array  $fields
	 * @param string $language
	 *
	 * @return array
	 */
	private function groupDownloadableFilesByProductIdConsideringProductVariants( $product_id, array $fields, $language ) {
		$data = [];
		foreach ( $fields as $field ) {

			list( , $fileNo, $fileId, $title ) = \WCML_Downloadable_Products::parseDownloadableFileField( $field['field_type'] );
			if ( null === $fileNo ) {
				continue;
			}

			// Default: When a product has no variants
			$variation_id = $product_id;

			$exp = explode( ':', $title, 2 );

			if ( ! empty( $exp[1] ) ) {
				// Product Variant
				$title        = $exp[0];
				$variation_id = $exp[1];
			}

			if ( is_post_type_translated( 'product_variation' ) ) {
				$translated_variation_id = apply_filters( 'wpml_object_id', $variation_id, 'product_variation', false, $language );
			} else {
				global $wpml_post_translations;
				$translations            = $wpml_post_translations->get_element_translations( $variation_id );
				$translated_variation_id = $translations[ $language ] ?? null;
			}

			if ( ! isset( $data[ $translated_variation_id ] ) ) {
				$data[ $translated_variation_id ] = [];
			}
			if ( ! isset( $data[ $translated_variation_id ][ $fileId ] ) ) {
				$data[ $translated_variation_id ][ $fileId ] = [];
			}
			$data[ $translated_variation_id ][ $fileId ][ $title ] = $field['data'];
		}

		return $data;
	}
}
