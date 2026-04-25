<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Element_Animated_Typing extends Element {
	public $category = 'general';
	public $name     = 'animated-typing';
	public $icon     = 'ti-more';
	public $scripts  = [ 'bricksAnimatedTyping' ];

	public function get_label() {
		return esc_html__( 'Anim. Typing', 'bricks' );
	}

	public function enqueue_scripts() {
		wp_enqueue_script( 'bricks-typed' );
	}

	public function set_control_groups() {
		$this->control_groups['settings'] = [
			'title' => esc_html__( 'Settings', 'bricks' ),
			'tab'   => 'content',
		];
	}

	public function set_controls() {
		$this->controls['tag'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Tag', 'bricks' ),
			'type'        => 'select',
			'options'     => [
				'div' => 'div',
				'h1'  => 'h1',
				'h2'  => 'h2',
				'h3'  => 'h3',
				'h4'  => 'h4',
				'h5'  => 'h5',
				'h6'  => 'h6',
			],
			'clearable'   => false,
			'inline'      => true,
			'rerender'    => true,
			'placeholder' => 'div',
		];

		$this->controls['prefix'] = [
			'tab'     => 'content',
			'label'   => esc_html__( 'Prefix', 'bricks' ),
			'type'    => 'text',
			'default' => 'We ',
			'inline'  => true,
		];

		$this->controls['suffix'] = [
			'tab'     => 'content',
			'label'   => esc_html__( 'Suffix', 'bricks' ),
			'type'    => 'text',
			'default' => ' for you!',
			'inline'  => true,
		];

		$this->controls['strings'] = [
			'tab'           => 'content',
			'label'         => esc_html__( 'Strings', 'bricks' ),
			'type'          => 'repeater',
			'titleProperty' => 'text',
			'default'       => [
				[ 'text' => 'design' ],
				[ 'text' => 'code' ],
				[ 'text' => 'launch' ],
			],
			'placeholder'   => esc_html__( 'Text block', 'bricks' ),
			'fields'        => [
				'text' => [
					'label' => esc_html__( 'Text', 'bricks' ),
					'type'  => 'text',
				],
			],
			'reloadScripts' => true,
		];

		// SETTINGS

		$this->controls['typeSpeed'] = [
			'tab'           => 'content',
			'group'         => 'settings',
			'label'         => esc_html__( 'Type speed in ms', 'bricks' ),
			'type'          => 'number',
			'default'       => 55,
			'small'         => false,
			'reloadScripts' => true,
		];

		$this->controls['backSpeed'] = [
			'tab'           => 'content',
			'group'         => 'settings',
			'label'         => esc_html__( 'Back speed in ms', 'bricks' ),
			'type'          => 'number',
			'small'         => false,
			'default'       => 30,
			'reloadScripts' => true,
		];

		$this->controls['startDelay'] = [
			'tab'           => 'content',
			'group'         => 'settings',
			'label'         => esc_html__( 'Start delay in ms', 'bricks' ),
			'type'          => 'number',
			'small'         => false,
			'default'       => 500,
			'reloadScripts' => true,
		];

		$this->controls['backDelay'] = [
			'tab'           => 'content',
			'group'         => 'settings',
			'label'         => esc_html__( 'Back delay in ms', 'bricks' ),
			'type'          => 'number',
			'small'         => false,
			'default'       => 500,
			'reloadScripts' => true,
		];

		$this->controls['cursorChar'] = [
			'tab'           => 'content',
			'group'         => 'settings',
			'label'         => esc_html__( 'Cursor character', 'bricks' ),
			'type'          => 'text',
			'inline'        => true,
			'default'       => '|',
			'reloadScripts' => true,
		];

		$this->controls['loop'] = [
			'tab'           => 'content',
			'group'         => 'settings',
			'label'         => esc_html__( 'Loop', 'bricks' ),
			'type'          => 'checkbox',
			'default'       => true,
			'reloadScripts' => true,
		];

		$this->controls['shuffle'] = [
			'tab'           => 'content',
			'group'         => 'settings',
			'label'         => esc_html__( 'Shuffle', 'bricks' ),
			'type'          => 'checkbox',
			'reloadScripts' => true,
		];

	}

	public function render() {
		$settings = $this->settings;
		$strings  = [];

		if ( isset( $settings['strings'] ) && is_array( $settings['strings'] ) && count( $settings['strings'] ) ) {
			foreach ( $settings['strings'] as $string ) {
				if ( isset( $string['text'] ) && $string['text'] != '' ) {
					$strings[] = $string['text'];
				}
			}
		}

		if ( ! count( $strings ) ) {
			return $this->render_element_placeholder(
				[
					'title' => esc_html__( 'No content', 'bricks' ),
				]
			);
		}

		$animated_typing_options = [
			'strings'    => $strings,
			'typeSpeed'  => isset( $settings['typeSpeed'] ) ? intval( $settings['typeSpeed'] ) : 55,
			'backSpeed'  => isset( $settings['backSpeed'] ) ? intval( $settings['backSpeed'] ) : 30,
			'startDelay' => isset( $settings['startDelay'] ) ? intval( $settings['startDelay'] ) : 500,
			'backDelay'  => isset( $settings['backDelay'] ) ? intval( $settings['backDelay'] ) : 500,
			'cursorChar' => isset( $settings['cursorChar'] ) ? $settings['cursorChar'] : '',
			'loop'       => isset( $settings['loop'] ) ? true : false,
			'shuffle'    => isset( $settings['shuffle'] ) ? true : false,
		];

		$this->set_attribute( '_root', 'data-script-args', wp_json_encode( $animated_typing_options ) );

		echo "<{$this->tag} {$this->render_attributes( '_root' )}>";

		if ( isset( $settings['prefix'] ) ) {
			echo '<span class="prefix">' . wp_kses_post( $settings['prefix'] ) . '</span>';
		}

		echo '<span class="typed"></span>';

		if ( isset( $settings['suffix'] ) ) {
			echo '<span class="suffix">' . wp_kses_post( $settings['suffix'] ) . '</span>';
		}

		echo "</{$this->tag}>";
	}

	public static function render_builder() { ?>
		<script type="text/x-template" id="tmpl-bricks-element-animated-typing">
			<component
				v-if="settings.strings && settings.strings.length"
				:is="tag"
				:data-script-args="JSON.stringify({
					strings: settings.strings && settings.strings.length ? settings.strings.map(string => string.text) : false,
					typeSpeed: settings.hasOwnProperty('typeSpeed') ? parseInt(settings.typeSpeed) : 55,
					backSpeed: settings.hasOwnProperty('backSpeed') ? parseInt(settings.backSpeed) : 30,
					startDelay: settings.hasOwnProperty('startDelay') ? parseInt(settings.startDelay) : 500,
					backDelay: settings.hasOwnProperty('backDelay') ? parseInt(settings.backDelay) : 500,
					cursorChar: settings.hasOwnProperty('cursorChar') ? settings.cursorChar : '',
					loop: settings.hasOwnProperty('loop'),
					shuffle: settings.hasOwnProperty('shuffle')
				})"
			>
				<span v-if="settings.prefix" class="prefix" v-html="settings.prefix"></span>
				<span class="typed"></span>
				<span v-if="settings.suffix" class="suffix" v-html="settings.suffix"></span>

				<slot></slot>
			</component>
			<div v-else v-html="renderElementPlaceholder()"></div>
		</script>
		<?php
	}
}
