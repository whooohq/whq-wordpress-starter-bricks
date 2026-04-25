<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Element_Countdown extends Element {
	public $category     = 'general';
	public $name         = 'countdown';
	public $icon         = 'ti-timer';
	public $css_selector = '.field';
	public $scripts      = [ 'bricksCountdown' ];

	public function get_label() {
		return esc_html__( 'Countdown', 'bricks' );
	}

	public function enqueue_scripts() {
		wp_enqueue_script( 'bricks-countdown' );
	}

	public function set_controls() {
		$this->controls['date'] = [
			'label'          => esc_html__( 'Date', 'bricks' ),
			'type'           => 'datepicker',
			'default'        => '2024-01-01 12:00',
			'hasDynamicData' => false, // TODO: Set to true to use dynamic data
			'rerender'       => true,
		];

		$this->controls['timezone'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Time zone', 'bricks' ),
			'type'        => 'select',
			'options'     => [
				'UTC-12:00' => 'UTC-12:00',
				'UTC-11:00' => 'UTC-11:00',
				'UTC-10:00' => 'UTC-10:00',
				'UTC-09:30' => 'UTC-09:30',
				'UTC-09:00' => 'UTC-09:00',
				'UTC-08:00' => 'UTC-08:00',
				'UTC-07:00' => 'UTC-07:00',
				'UTC-06:00' => 'UTC-06:00',
				'UTC-05:00' => 'UTC-05:00',
				'UTC-04:30' => 'UTC-04:30',
				'UTC-04:00' => 'UTC-04:00',
				'UTC-03:30' => 'UTC-03:30',
				'UTC-03:00' => 'UTC-03:00',
				'UTC-02:00' => 'UTC-02:00',
				'UTC-01:00' => 'UTC-01:00',
				'UTC+00:00' => 'UTC+00:00',
				'UTC+01:00' => 'UTC+01:00',
				'UTC+02:00' => 'UTC+02:00',
				'UTC+03:00' => 'UTC+03:00',
				'UTC+03:30' => 'UTC+03:30',
				'UTC+04:00' => 'UTC+04:00',
				'UTC+04:30' => 'UTC+04:30',
				'UTC+05:00' => 'UTC+05:00',
				'UTC+05:30' => 'UTC+05:30',
				'UTC+05:45' => 'UTC+05:45',
				'UTC+06:00' => 'UTC+06:00',
				'UTC+06:30' => 'UTC+06:30',
				'UTC+07:00' => 'UTC+07:00',
				'UTC+08:00' => 'UTC+08:00',
				'UTC+08:45' => 'UTC+08:45',
				'UTC+09:00' => 'UTC+09:00',
				'UTC+09:30' => 'UTC+09:30',
				'UTC+10:00' => 'UTC+10:00',
				'UTC+10:30' => 'UTC+10:30',
				'UTC+11:00' => 'UTC+11:00',
				'UTC+12:00' => 'UTC+12:00',
				'UTC+12:45' => 'UTC+12:45',
				'UTC+13:00' => 'UTC+13:00',
				'UTC+14:00' => 'UTC+14:00',
			],
			'inline'      => true,
			'placeholder' => 'UTC+00',
		];

		$this->controls['action'] = [
			'label'       => esc_html__( 'Date Reached', 'bricks' ),
			'type'        => 'select',
			'options'     => [
				'countdown' => esc_html__( 'Countdown', 'bricks' ),
				'hide'      => esc_html__( 'Hide', 'bricks' ),
				'text'      => esc_html__( 'Custom text', 'bricks' ),
			],
			'placeholder' => esc_html__( 'Countdown', 'bricks' ),
			'inline'      => true,
			'rerender'    => true,
		];

		$this->controls['actionText'] = [
			'label'    => esc_html__( 'Date Reached', 'bricks' ) . ': ' . esc_html__( 'Custom text', 'bricks' ),
			'type'     => 'text',
			'required' => [ 'action', '=', 'text' ],
			'rerender' => true,
		];

		// FIELDS

		$this->controls['fieldsSeparator'] = [
			'label' => esc_html__( 'Fields', 'bricks' ),
			'type'  => 'separator',
		];

		$this->controls['fields'] = [
			'type'          => 'repeater',
			'titleProperty' => 'format',
			'placeholder'   => esc_html__( 'Field', 'bricks' ),
			'fields'        => [
				'prefix' => [
					'label'  => esc_html__( 'Prefix', 'bricks' ),
					'type'   => 'text',
					'inline' => true,
				],

				'format' => [
					'label'       => esc_html__( 'Format', 'bricks' ),
					'type'        => 'text',
					'placeholder' => '%D',
					'inline'      => true,
					'info'        => '%D, %H, %M, %S (' . esc_html__( 'Lowercase removes leading zeros', 'bricks' ) . ')',
				],

				'suffix' => [
					'label'  => esc_html__( 'Suffix', 'bricks' ),
					'type'   => 'text',
					'inline' => true,
				],
			],
			'default'       => [
				[ 'format' => '%D days' ],
				[ 'format' => '%H hours' ],
				[ 'format' => '%M minutes' ],
				[ 'format' => '%S seconds' ],
			],
			'rerender'      => true,
		];

		$this->controls['flexDirectionFields'] = [
			'label'  => esc_html__( 'Direction', 'bricks' ),
			'type'   => 'direction',
			'css'    => [
				[
					'property' => 'flex-direction',
					'selector' => '',
				],
			],
			'inline' => true,
		];

		$this->controls['justifyContent'] = [
			'label'   => esc_html__( 'Align main axis', 'bricks' ),
			'tooltip' => [
				'content'  => 'justify-content',
				'position' => 'top-left',
			],
			'type'    => 'justify-content',
			'css'     => [
				[
					'property' => 'justify-content',
					'selector' => '',
				],
			],
		];

		$this->controls['alignItems'] = [
			'label'   => esc_html__( 'Align cross axis', 'bricks' ),
			'tooltip' => [
				'content'  => 'align-items',
				'position' => 'top-left',
			],
			'type'    => 'align-items',
			'css'     => [
				[
					'property' => 'align-items',
					'selector' => '',
				],
			],
		];

		// FIELD

		$this->controls['fieldSeparator'] = [
			'label' => esc_html__( 'Field', 'bricks' ),
			'type'  => 'separator',
		];

		$this->controls['flexDirection'] = [
			'label'  => esc_html__( 'Direction', 'bricks' ),
			'type'   => 'direction',
			'css'    => [
				[
					'property' => 'flex-direction',
					'selector' => '.field',
				],
			],
			'inline' => true,
		];

		$this->controls['gutter'] = [
			'label'   => esc_html__( 'Margin', 'bricks' ),
			'type'    => 'spacing',
			'css'     => [
				[
					'property' => 'margin',
					'selector' => '.field',
				],
			],
			'default' => [
				'top'    => 0,
				'right'  => 5,
				'bottom' => 0,
				'left'   => 0,
			],
		];

		// STYLE: TYPOGRAPHY

		// Remove default '_typography'
		unset( $this->controls['_typography'] );

		$this->controls['typography'] = [
			'tab'   => 'style',
			'group' => '_typography',
			'label' => esc_html__( 'Typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
				],
			],
		];

		$this->controls['typographyPrefix'] = [
			'tab'   => 'style',
			'group' => '_typography',
			'label' => esc_html__( 'Typography', 'bricks' ) . ' (' . esc_html__( 'Prefix', 'bricks' ) . ')',
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.prefix',
				],
			],
		];

		$this->controls['typographySuffix'] = [
			'tab'   => 'style',
			'group' => '_typography',
			'label' => esc_html__( 'Typography', 'bricks' ) . ' (' . esc_html__( 'Suffix', 'bricks' ) . ')',
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.suffix',
				],
			],
		];
	}

	public function render() {
		$settings    = $this->settings;
		$date        = ! empty( $settings['date'] ) ? $settings['date'] : false;
		$fields      = ! empty( $settings['fields'] ) ? $settings['fields'] : false;
		$action      = ! empty( $settings['action'] ) ? $settings['action'] : 'countdown';
		$action_text = ! empty( $settings['actionText'] ) ? $this->render_dynamic_data( $settings['actionText'] ) : '';
		$timezone    = ! empty( $settings['timezone'] ) ? $settings['timezone'] : 'UTC+00:00';

		if ( ! $date || ! $fields ) {
			return $this->render_element_placeholder( [ 'title' => esc_html__( 'No date/fields set.', 'bricks' ) ] );
		}

		// Render dynamic data for prefix, format, suffix fields (@since 1.9.1)
		$keys_to_check = [ 'prefix', 'format', 'suffix' ];

		$fields = array_map(
			function( $field ) use ( $keys_to_check ) {
				$keys_to_render = array_intersect( array_keys( $field ), $keys_to_check );

				foreach ( $keys_to_render as $key ) {
					$field[ $key ] = $this->render_dynamic_data( $field[ $key ] );
				}

				return $field;
			},
			$fields
		);

		$this->set_attribute(
			'_root',
			'data-bricks-countdown-options',
			wp_json_encode(
				[
					'date'       => $date,
					'fields'     => $fields,
					'action'     => $action,
					'actionText' => $action_text,
					'timezone'   => $timezone,
				]
			)
		);

		echo "<div {$this->render_attributes( '_root' )}></div>";
	}
}
