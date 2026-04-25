<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Element_Audio extends Element {
	public $block    = 'core/audio';
	public $category = 'media';
	public $name     = 'audio';
	public $icon     = 'ti-volume';
	public $scripts  = [ 'bricksAudio' ];

	public function get_label() {
		return esc_html__( 'Audio', 'bricks' );
	}

	public function set_controls() {
		$this->controls['source'] = [
			'label'       => esc_html__( 'Source', 'bricks' ),
			'tab'         => 'content',
			'type'        => 'select',
			'options'     => [
				'file'     => esc_html__( 'File', 'bricks' ),
				'external' => esc_html__( 'External URL', 'bricks' ),
				'dynamic'  => esc_html__( 'Dynamic Data', 'bricks' ),
			],
			'inline'      => true,
			'placeholder' => esc_html__( 'File', 'bricks' ),
		];

		$this->controls['file'] = [
			'tab'      => 'content',
			'type'     => 'audio',
			'required' => [ 'source', '=', [ '', 'file' ] ],
		];

		$this->controls['external'] = [
			'tab'         => 'content',
			'type'        => 'text',
			'required'    => [ 'source', '=', 'external' ],
			'placeholder' => 'https://yoursite.com/audio-test.mp3',
		];

		$this->controls['useDynamicData'] = [
			'tab'            => 'content',
			'label'          => '',
			'type'           => 'text',
			'placeholder'    => esc_html__( 'Select dynamic data', 'bricks' ),
			'hasDynamicData' => 'media',
			'required'       => [ 'source', '=', 'dynamic' ],
		];

		$this->controls['titleCustom'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Custom title', 'bricks' ),
			'type'     => 'text',
			'required' => [ 'source', '!=', 'external' ],
		];

		$this->controls['artist'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Show artist', 'bricks' ),
			'type'     => 'checkbox',
			'required' => [
				[ 'source', '!=', 'external' ],
				[ 'titleCustom', '=', '' ],
			],
		];

		$this->controls['title'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Show title', 'bricks' ),
			'required' => [
				[ 'source', '!=', 'external' ],
				[ 'titleCustom', '=', '' ],
			],
			'type'     => 'checkbox',
		];

		// NOTE: Autoplay is blocked in new Google Chrome
		$this->controls['autoplay'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Autoplay', 'bricks' ),
			'type'  => 'checkbox',
		];

		$this->controls['loop'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Loop', 'bricks' ),
			'type'  => 'checkbox',
		];

		$this->controls['tag'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Tag', 'bricks' ),
			'type'        => 'select',
			'options'     => [
				'p'  => 'p',
				'h1' => 'h1',
				'h2' => 'h2',
				'h3' => 'h3',
				'h4' => 'h4',
				'h5' => 'h5',
				'h6' => 'h6',
			],
			'clearable'   => false,
			'inline'      => true,
			'placeholder' => 'p',
			'required'    => [ 'file', '!=', '' ],
		];

		$this->controls['preload'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Preload', 'bricks' ),
			'type'        => 'select',
			'options'     => [
				'metadata' => esc_html__( 'Metadata', 'bricks' ),
				'auto'     => esc_html__( 'Auto', 'bricks' ),
			],
			'placeholder' => esc_html__( 'None', 'bricks' ),
			'inline'      => true,
		];

		$this->controls['theme'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Theme', 'bricks' ),
			'type'        => 'select',
			'options'     => [
				'light' => esc_html__( 'Light', 'bricks' ),
				'dark'  => esc_html__( 'Dark', 'bricks' ),
			],
			'inline'      => true,
			'placeholder' => esc_html__( 'Light', 'bricks' ),
		];
	}

	public function render() {
		$settings  = $this->settings;
		$source    = ! empty( $settings['source'] ) ? $settings['source'] : 'file';
		$audio_id  = '';
		$audio_url = '';

		// Source: File
		if ( $source === 'file' ) {
			$audio_id  = isset( $settings['file']['id'] ) ? $settings['file']['id'] : false;
			$audio_url = isset( $settings['file']['url'] ) ? $settings['file']['url'] : false;

		}

		// Source: External
		elseif ( $source === 'external' ) {
			$audio_url = isset( $settings['external'] ) ? $settings['external'] : false;
		}

		// Source: DD
		elseif ( $source === 'dynamic' ) {
			if ( ! empty( $settings['useDynamicData'] ) ) {

				$metas = $this->render_dynamic_data_tag( $settings['useDynamicData'], 'media' );

				$audio_id  = isset( $metas[0]['id'] ) ? $metas[0]['id'] : false;
				$audio_url = isset( $metas[0]['url'] ) ? $metas[0]['url'] : false;

				if ( ! $audio_url ) {
					$message = esc_html__( 'The dynamic data is empty.', 'bricks' );
				}
			} else {
				$message = esc_html__( 'No dynamic data set.', 'bricks' );
			}
		}

		if ( empty( $audio_url ) ) {
			return $this->render_element_placeholder(
				[
					'title' => isset( $message ) ? $message : esc_html__( 'No audio file selected.', 'bricks' ),
				]
			);
		}

		$audio_meta_data = $audio_id ? wp_get_attachment_metadata( $audio_id ) : [];
		$audio_artist    = ! empty( $audio_meta_data['artist'] ) ? $audio_meta_data['artist'] : '';
		$audio_title     = $audio_id ? get_the_title( $audio_id ) : '';

		$this->set_attribute( '_root', 'class', ! empty( $settings['theme'] ) ? "theme-{$settings['theme']}" : 'theme-light' );

		echo "<div {$this->render_attributes( '_root' )}>";

		if ( $audio_artist || $audio_title ) {
			$title_parts = [];

			if ( isset( $settings['artist'] ) && $audio_artist ) {
				$title_parts[] = $audio_artist;
			}

			if ( isset( $settings['title'] ) && $audio_title ) {
				$title_parts[] = $audio_title;
			}

			$tag = isset( $settings['tag'] ) ? $settings['tag'] : 'p';

			if ( ! empty( $settings['titleCustom'] ) ) {
				$title_parts = [ $settings['titleCustom'] ];
			}

			if ( $title_parts ) {
				echo '<' . $tag . ' class="audio-title">' . join( ' - ', $title_parts ) . "</$tag>";
			}
		}

		$audio_shortcode = [];

		$audio_shortcode['src'] = esc_url( $audio_url );

		if ( isset( $settings['autoplay'] ) && $this->is_frontend ) {
			$audio_shortcode['autoplay'] = $settings['autoplay'];
		}

		if ( isset( $settings['loop'] ) ) {
			$audio_shortcode['loop'] = $settings['loop'];
		}

		if ( isset( $settings['preload'] ) ) {
			$audio_shortcode['preload'] = $settings['preload'];
		}

		echo wp_audio_shortcode( $audio_shortcode );

		echo '</div>';
	}

	public function convert_element_settings_to_block( $settings ) {
		if ( $source === 'file' ) {
			$audio_id = isset( $settings['file']['id'] ) ? $settings['file']['id'] : false;
		} elseif ( $source === 'dynamic' && ! empty( $settings['useDynamicData'] ) ) {
			$metas = $this->render_dynamic_data_tag( $settings['useDynamicData'], 'media' );

			$audio_id = isset( $metas[0]['id'] ) ? $metas[0]['id'] : false;
		}

		if ( empty( $audio_id ) ) {
			return;
		}

		$block = [
			'blockName'    => $this->block,
			'attrs'        => [ 'id' => $audio_id ],
			'innerContent' => [],
		];

		$audio_html = '<figure class="wp-block-audio"><audio controls ';

		if ( isset( $settings['autoplay'] ) ) {
			$audio_html .= 'autoplay ';
		}

		if ( isset( $settings['loop'] ) ) {
			$audio_html .= 'loop ';
		}

		if ( isset( $settings['preload'] ) ) {
			$audio_html .= 'preload="' . $settings['preload'] . '" ';
		}

		$audio_html .= 'src="' . wp_get_attachment_url( $audio_id ) . '"></audio></figure>';

		$block['innerContent'] = [ $audio_html ];

		return $block;
	}

	public function convert_block_to_element_settings( $block, $attributes ) {
		$audio_file_id = isset( $attributes['id'] ) ? $attributes['id'] : false;

		if ( ! count( $audio_file_id ) ) {
			return;
		}

		$audio_html = $block['innerHTML'];

		$element_settings = [
			'file'     => [
				'id'       => $audio_file_id,
				'filename' => basename( get_attached_file( $audio_file_id ) ),
				'url'      => wp_get_attachment_url( $audio_file_id ),
			],
			'autoplay' => strpos( $audio_html, ' autoplay' ) !== false,
			'loop'     => strpos( $audio_html, ' loop' ) !== false,
		];

		if ( strpos( $audio_html, ' preload="auto"' ) !== false ) {
			$element_settings['preload'] = 'auto';
		}

		if ( strpos( $audio_html, ' preload="metadata"' ) !== false ) {
			$element_settings['preload'] = 'metadata';
		}

		return $element_settings;
	}
}
