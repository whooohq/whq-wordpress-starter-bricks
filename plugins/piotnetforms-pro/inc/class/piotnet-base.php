<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class piotnetforms_Base extends piotnetforms_Variables_Pro {
	public $settings;
	public $post_id;
	private $additional_attributes;
	public $widget_id;
	private $editor;
	public $control_dynamic_css = [];

	public function __construct() {
		parent::__construct();
		$this->post_id = get_the_ID();
        
		if ( ! wp_script_is( 'jquery', 'enqueued' ) ) {

			//Enqueue
			wp_enqueue_script( 'jquery' );
		}

		if ( method_exists( $this, 'get_script' ) ) {
			$scripts = $this->get_script();
			if ( !empty( $scripts ) ) {
				foreach ( $scripts as $script ) {
					wp_enqueue_script( $script );
				}
			}
		}

		if ( method_exists( $this, 'get_style' ) ) {
			$styles = $this->get_style();
			if ( !empty( $styles ) ) {
				foreach ( $styles as $style ) {
					wp_enqueue_style( $style );
				}
			}
		}
	}

	public function get_id() {
		return $this->widget_id;
	}

	public function get_settings() {
		return $this->settings;
	}

	public function widget_visibility() {
		$settings   = $this->settings;
		$visibility = true;
		if ( !empty( $settings['conditional_visibility_enable'] ) && 'yes' === $settings['conditional_visibility_enable'] ) {
			$visibility_roles = is_array($settings['conditional_visibility_roles']) ? $settings['conditional_visibility_roles'] : [];
			//$condition1       = false;
			$condition2 = true;
			$show = 'show';
			$user             = wp_get_current_user();
			$user_roles       = $user->roles;

			if ( 'yes' == $settings['conditional_visibility_by_roles'] ) {
				$role_condition = false;
				if ( in_array( 'all', $visibility_roles ) || ( in_array( 'logged_in', $visibility_roles ) && is_user_logged_in() ) || ( in_array( 'non_logged_in', $visibility_roles ) && !is_user_logged_in() ) ) {
					$role_condition = true;
				}
				if ( isset( $user_roles[0] ) && in_array( $user_roles[0], $visibility_roles ) ) {
					$role_condition = true;
				}
				if ( !$role_condition ) {
					return  false;
				}
			}
			/*
							if ( in_array( 'all', $visibility_roles ) || in_array( 'logged_in', $visibility_roles ) && is_user_logged_in() || in_array( 'non_logged_in', $visibility_roles ) && ! is_user_logged_in() ) {
								$condition1 = true;
							}

							if ( isset( $user_roles[0] ) ) {
								if ( in_array( $user_roles[0], $visibility_roles ) ) {
									$condition1 = true;
								}
							}*/

			//Conditional Visibility by date and time

			$time_should_render = null;

			if ( 'yes' == $settings['conditional_visibility_by_date_and_time'] ) {
				$time_should_render = false;
				$repeater_for_time =  $settings['conditional_visibility_time_repeater'];
				$repeater_results = [];
				foreach ( $repeater_for_time as $repeater_item ) {
					$repeater_results[] = $this->check_piotnet_forms_conditional_visibility_time_repeater( $repeater_item );
				}
				$time_conditional = false;
				if ( $settings['conditional_visibility_date_and_time_operators'] == 'and' && !in_array( false, $repeater_results ) ) {
					$time_conditional = true;
				}
				if ( $settings['conditional_visibility_date_and_time_operators'] == 'or' && in_array( true, $repeater_results ) ) {
					$time_conditional = true;
				}

				$time_shows = $settings['conditional_visibility_action_for_date_and_time'];
				if ( $time_conditional ) {
					if ( $time_shows == 'show' ) {
						$time_should_render = true;
					} elseif ( $time_shows == 'hide' ) {
						return false;
					}
				} else {
					if ( $time_shows == 'show' ) {
						return false;
					} elseif ( $time_shows == 'hide' ) {
						$time_should_render = true;
					}
				}
			}

			if ( ! empty( $settings['conditional_visibility_by_backend'] ) ) {
				if ( array_key_exists( 'conditional_visibility_by_backend_list', $settings ) ) {
					$list = $settings['conditional_visibility_by_backend_list'];
					$show = $settings['conditional_visibility_action'];

					if ( ! empty( $list[0]['conditional_visibility_by_backend_select'] ) ) {
						$conditionals_count  = count( $list );
						$conditionals_and_or = '';
						$error               = 0;
						$condition           = false;
						foreach ( $list as $item ) {
							$conditionals_and_or = $item['conditional_visibility_and_or_operators'];
							if ( $item['conditional_visibility_by_backend_select'] === 'custom_field' && ! empty( $item['conditional_visibility_custom_field_key'] ) ) {
								$field_key        = $item['conditional_visibility_custom_field_key'];
								$field_source     = $item['conditional_visibility_custom_field_source'];
								$field_value      = '';
								$comparison       = $item['conditional_visibility_custom_field_comparison_operators'];
								$comparison_value = isset( $item['conditional_visibility_custom_field_value'] ) ? $item['conditional_visibility_custom_field_value'] : '';
								$id               = get_the_ID();

								if ( $field_source === 'post_custom_field' ) {
									$field_value = get_post_meta( $id, $field_key, true );
								} else {
									if ( function_exists( 'get_field' ) ) {
										$field_value = get_field( $field_key, $id );
									}
								}

								if ( isset( $item['conditional_visibility_custom_field_type'] ) ) {
									if ( $item['conditional_visibility_custom_field_type'] === 'number' ) {
										$field_value = floatval( $field_value );
									}
								}

								if ( is_array( $field_value ) && $comparison === 'contains' ) {
									if ( in_array( $comparison_value, $field_value ) ) {
										$condition = true;
									} else {
										$error++;
									}
								} else {
									if ( $comparison === 'not-empty' && ! empty( $field_value ) || $comparison === 'empty' && empty( $field_value ) || $comparison === 'true' && $field_value === true || $comparison === 'false' && $field_value === false || $comparison === '=' && $field_value === $comparison_value || $comparison === '!=' && $field_value !== $comparison_value || $comparison === '>' && $field_value > $comparison_value || $comparison === '>=' && $field_value >= $comparison_value || $comparison === '<' && $field_value < $comparison_value || $comparison === '<=' && $field_value <= $comparison_value ) {
										$condition = true;
									} else {
										$error++;
									}
								}
							}

							if ( $item['conditional_visibility_by_backend_select'] === 'url_parameter' && ! empty( $item['conditional_visibility_url_parameter'] ) ) {
								$url_parameter    = $item['conditional_visibility_url_parameter'];
								$comparison       = $item['conditional_visibility_custom_field_comparison_operators'];
								$comparison_value = isset( $item['conditional_visibility_custom_field_value'] ) ? $item['conditional_visibility_custom_field_value'] : '';
								$field_value      = '';

								if ( ! empty( $_GET[ $url_parameter ] ) ) {
									$field_value = $_GET[ $url_parameter ];
								}

								if ( isset( $item['conditional_visibility_custom_field_type'] ) ) {
									if ( $item['conditional_visibility_custom_field_type'] === 'number' ) {
										$field_value = floatval( $field_value );
									}
								}

								if ( $comparison === 'not-empty' && ! empty( $field_value ) || $comparison === 'empty' && empty( $field_value ) || $comparison === 'true' && $field_value === true || $comparison === 'false' && $field_value === false || $comparison === '=' && $field_value === $comparison_value || $comparison === '!=' && $field_value !== $comparison_value || $comparison === '>' && $field_value > $comparison_value || $comparison === '>=' && $field_value >= $comparison_value || $comparison === '<' && $field_value < $comparison_value || $comparison === '<=' && $field_value <= $comparison_value ) {
									$condition = true;
								} else {
									$error++;
								}
							}

							if ( $item['conditional_visibility_by_backend_select'] === 'url_contains' && ! empty( $item['conditional_visibility_custom_field_value_url_contains'] ) ) {
								$url_contains = $item['conditional_visibility_custom_field_value_url_contains'];
								$actual_link  = ( isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http' ) . "://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
								$find         = strpos( $actual_link, $url_contains );

								if ( $find !== false ) {
									$condition = true;
								} else {
									$error++;
								}
							}
						}
						if ( $conditionals_and_or === 'or' ) {
							if ( $conditionals_count <= $error ) {
								$condition2 = false;
							}
						}

						if ( $conditionals_and_or === 'and' ) {
							if ( $error !== 0 ) {
								$condition2 = false;
							}
						}
					}
				}
			}

			if ( ( ( $condition2 == true && $show == 'show' ) || ( $condition2 == false && $show == 'hide' ) ) && ( $time_should_render == null || $time_should_render == true ) ) {
				$visibility = true;
			} else {
				$visibility = false;
			}
		} else {
			$visibility = true;
		}


		return $visibility;
	}
	private function check_piotnet_forms_conditional_visibility_time_repeater( $repeater_item ) {
		$date_start = strtotime( $repeater_item['conditional_visibility_start_date'] );
		$date_end = strtotime( $repeater_item['conditional_visibility_end_date'] );
		$time_end = strtotime( $repeater_item['conditional_visibility_time_end'] );
		$time_start = strtotime( $repeater_item['conditional_visibility_time_start'] );
		$current_time = time();
		$chosen_days = $repeater_item['conditional_visibility_set_days_of_week'];
		$get_the_current_day_of_week = date( 'w' );

		if ( !empty( $chosen_days ) && !in_array( $get_the_current_day_of_week, $chosen_days ) ) {
			return false;
		}

		if ( !empty( $date_start ) && $current_time < $date_start ) {
			return false;
		}

		if ( !empty( $date_end ) && $current_time > $date_end ) {
			return false;
		}

		if ( !empty( $time_start ) && $current_time < $time_start ) {
			return false;
		}

		if ( !empty( $time_end ) && $current_time > $time_end ) {
			return false;
		}

		return true;
	}

	public function dynamic_css() {
		$settings = $this->settings;

		$this->register_controls();
		$control_dynamic_css = $this->control_dynamic_css;
		$dynamic_css = '';
		$widget_id = $this->get_id();
		$class_id = !empty( $GLOBALS['term'] ) && is_object( $GLOBALS['term'] ) ? $GLOBALS['term']->term_id : get_the_ID();

		foreach ( $control_dynamic_css as $control_name => $control_dynamic_css_item ) {
			if ( !empty( $settings[$control_name] ) ) {
				$control_value = piotnetforms_dynamic_tags( $settings[$control_name] );
				if ( $control_value !== '' ) {
					foreach ( $control_dynamic_css_item as $control_selectors => $control_css ) {
						$control_selectors = str_replace( '{{WRAPPER}}', '#piotnetforms ' . $widget_id, $control_selectors );
						$control_selectors = str_replace( '{{WIDGET}}', '.' . $widget_id, $control_selectors );
						$control_selectors = str_replace( '{{WRAPPER_EDITOR}}', '#piotnetforms [data-piotnet-editor-widgets-item-id="' . $widget_id . '"]', $control_selectors );

						$control_css = str_replace( '{{VALUE}}', $control_value, $control_css );

						$dynamic_css .= $control_selectors . '{' . $control_css . '}';
					}
				}
			}
		}

		if ( !empty( $dynamic_css ) ) {
			echo '<style>' . $dynamic_css . '</style>';
		}
	}

	public function custom_css() {
		$settings = $this->settings;
		$custom_css = '';
		$widget_id = $this->get_id();

		if ( !empty( $settings['advanced_custom_css'] ) ) {
			$custom_css = $settings['advanced_custom_css'];
			$custom_css = str_replace( '{{WRAPPER}}', '#piotnetforms .' . $widget_id, $custom_css );
		}

		if ( !empty( $custom_css ) ) {
			echo '<style>' . $custom_css . '</style>';
		}
	}

	public function output_wrapper_start( $widget_id = '', $editor = false ) {
		ob_start();

		$widget_information = [
			'type'       => $this->get_type(),
			'class_name' => $this->get_class_name(),
			'title'      => $this->get_title(),
			'icon'       => $this->get_icon(),
		];

		$settings = $this->settings;

		$visibility = true;

		if ( ! $editor ) {
			$visibility = $this->widget_visibility();
		}

		if ( $editor ) {
			$this->add_render_attribute( 'widget_wrapper', 'class', 'piotnet-widget' );
			$this->add_render_attribute( 'widget_wrapper', 'data-piotnet-editor-widgets-item', json_encode( $widget_information ) );
			$this->add_render_attribute( 'widget_wrapper', 'data-piotnet-editor-widgets-item-id', $widget_id );

			if ( $this->get_type() === 'section' ) {
				$this->add_render_attribute( 'widget_wrapper', 'data-piotnet-editor-widgets-item-section', '' );
				$this->add_render_attribute( 'widget_wrapper', 'data-piotnet-editor-section', '' );
				$this->add_render_attribute( 'widget_wrapper', 'draggable', 'true' );
			}

			if ( $this->get_type() === 'column' ) {
				$this->add_render_attribute( 'widget_wrapper', 'data-piotnet-editor-column', '' );
			}
		}

		if ( $this->get_type() === 'section' ) {
			$this->add_render_attribute( 'widget_wrapper', 'class', 'piotnet-section' );
		}

		if ( $this->get_type() === 'column' ) {
			$this->add_render_attribute( 'widget_wrapper', 'class', 'piotnet-column' );
		}

		if ( $this->get_type() === 'section' || $this->get_type() === 'column' ) {
			$this->add_render_attribute( 'widget_wrapper', 'class', $this->widget_id );

			if ( ! empty( $settings['advanced_custom_id'] ) ) {
				$this->add_render_attribute( 'widget_wrapper', 'id', $settings['advanced_custom_id'] );
			}

			if ( ! empty( $settings['advanced_custom_classes'] ) ) {
				$this->add_render_attribute( 'widget_wrapper', 'class', $settings['advanced_custom_classes'] );
			}

			if ( ! empty( $settings['piotnetforms_repeater_enable'] ) && ! empty( $settings['piotnetforms_repeater_id'] ) && ! empty( $settings['piotnetforms_repeater_label'] ) ) {
				$form_post_id = $this->post_id;
				$form_version = empty( get_post_meta( $form_post_id, '_piotnetforms_version', true ) ) ? 1 : get_post_meta( $form_post_id, '_piotnetforms_version', true );
				$form_id = $form_version == 1 ? $settings['piotnetforms_repeater_form_id'] : $form_post_id;

				$this->add_render_attribute(
					'widget_wrapper',
					[
						'data-piotnetforms-repeater-form-id' => $form_id,
						'data-piotnetforms-repeater-id'    => $settings['piotnetforms_repeater_id'],
						'data-piotnetforms-repeater-label' => $settings['piotnetforms_repeater_label'],
						'data-piotnetforms-repeater-limit' => isset( $settings['piotnetforms_repeater_limit'] ) ? $settings['piotnetforms_repeater_limit'] : '',
					]
				);
				wp_enqueue_script( $this->slug . '-advanced-script' );
			}
		}

		if ( ! $editor ) {
			if ( $this->get_type() !== 'field' && $this->get_type() !== 'submit' && $this->get_type() !== 'multi-step-form' ) {
				if ( ! empty( $settings['piotnetforms_conditional_logic_form_enable_new'] ) && ! empty( $settings['piotnetforms_conditional_logic_form_form_id'] ) ) {
					if ( array_key_exists( 'piotnetforms_conditional_logic_form_list_new', $settings ) ) {
						$list_conditional = $settings['piotnetforms_conditional_logic_form_list_new'];
						$form_post_id = $this->post_id;
						$form_version = empty( get_post_meta( $form_post_id, '_piotnetforms_version', true ) ) ? 1 : get_post_meta( $form_post_id, '_piotnetforms_version', true );
						$form_id = $form_version == 1 ? $settings['piotnetforms_conditional_logic_form_form_id'] : $form_post_id;

						//if ( ! empty( $list_conditional[0]['piotnetforms_conditional_logic_form_if'] ) && ! empty( $list_conditional[0]['piotnetforms_conditional_logic_form_comparison_operators'] ) ) {
						$this->add_render_attribute(
							'widget_wrapper',
							[
								'data-piotnetforms-conditional-logic' => json_encode( $list_conditional ),
								'data-piotnetforms-conditional-logic-not-field' => '',
								'data-piotnetforms-conditional-logic-not-field-form-id' => $form_id,
								'data-piotnetforms-conditional-logic-speed' => $settings['piotnetforms_conditional_logic_form_speed_new'],
								'data-piotnetforms-conditional-logic-easing' => $settings['piotnetforms_conditional_logic_form_easing_new'],
							]
						);
						//}

						wp_enqueue_script( $this->slug . '-advanced-script' );
					}
				}
			}

			if ( !empty( $settings['multi_step_form_animation'] ) ) {
				$this->add_render_attribute(
					'widget_wrapper',
					[
						'class' => 'piotnetforms-animation',
						'data-piotnetforms-animation' => !empty( $settings['multi_step_form_animation_animate'] ) ? $settings['multi_step_form_animation_animate'] : 'fadeIn',
						'data-piotnetforms-animation-delay' => !empty( $settings['multi_step_form_animation_delay'] ) ? $settings['multi_step_form_animation_delay'] : 0,
					]
				);
			}
		}

		if ( !empty( $settings['attributes'] ) ) {
			$attributes = [];
			foreach ( $settings['attributes'] as $attribute ) {
				$attributes[$attribute['attribute_name']] = $attribute['attribute_value'];
			}
			$this->add_render_attribute(
				'widget_wrapper',
				$attributes
			);
		}

		if ( ! empty( $settings['advanced_background_image_dynamic_data'] ) ) {
			$advanced_background_image_url = '';
			if ( empty( $settings['advanced_background_image']['url'] ) && $settings['advanced_background_image_dynamic_data'] == 'featured_image' ) {
				$advanced_background_image_url = get_the_post_thumbnail_url( get_the_ID(), 'post-thumbnail' );
			}
			$this->add_render_attribute( 'widget_wrapper', 'style', 'background-image: url(' . $advanced_background_image_url . ')' );
		}

		$this->before_render();

		echo '<div ' . $this->get_render_attribute_string( 'widget_wrapper' ) . '>';

		if ( $visibility ) {
			$this->render_start( $editor );
		}

		return ob_get_clean();
	}

	public function output_wrapper_end( $widget_id = '', $editor = false ) {
		ob_start();

		$visibility = true;

		if ( ! $editor ) {
			$visibility = $this->widget_visibility();
		}

		if ( $visibility ) {
			$this->render_end( $editor );
			$this->dynamic_css();
			$this->custom_css();
			echo '</div>';
		}

		return ob_get_clean();
	}

	public function output( $widget_id = '', $editor = false ) {
		ob_start();

		$settings = $this->settings;

		$widget_information = [
			'type'       => $this->get_type(),
			'class_name' => $this->get_class_name(),
			'title'      => $this->get_title(),
			'icon'       => $this->get_icon(),
		];

		$visibility = true;

		if ( ! $editor ) {
			$visibility = $this->widget_visibility();
		}

		if ( $editor ) {
			$this->add_render_attribute( 'wrapper', 'data-piotnet-editor-widgets-item-root', '' );

			$this->add_render_attribute( 'widget_wrapper_editor', 'class', 'piotnet-widget' );
			$this->add_render_attribute( 'widget_wrapper_editor', 'data-piotnet-editor-widgets-item', json_encode( $widget_information ) );
			$this->add_render_attribute( 'widget_wrapper_editor', 'data-piotnet-editor-widgets-item-id', $widget_id );
			$this->add_render_attribute( 'widget_wrapper_editor', 'data-piotnet-editor-widgets-item-type', $widget_information['type'] );
			$this->add_render_attribute( 'widget_wrapper_editor', 'draggable', 'true' );

			echo '<div ' . $this->get_render_attribute_string( 'widget_wrapper_editor' ) . '>';

			$duplicate_disabled = $this->get_type() === 'multi-step-start' || $this->get_type() === 'multi-step-end';
			$remove_disabled = $this->get_type() === 'multi-step-start' || $this->get_type() === 'multi-step-end'; ?>
				<div class="piotnet-widget__controls" data-piotnet-controls>
					<div class="piotnet-widget__controls-item piotnet-widget__controls-item--edit" title="Edit" draggable="false" data-piotnet-control-edit>
						<img src="<?php echo plugin_dir_url( __FILE__ ) . '../../assets/icons/e-control-edit.svg'; ?>" draggable="false">
					</div>
                    <?php if ( !$duplicate_disabled ) { ?>
                        <div class="piotnet-widget__controls-item piotnet-widget__controls-item--duplicate" title="Duplicate" draggable="false" data-piotnet-control-duplicate>
                            <img src="<?php echo plugin_dir_url( __FILE__ ) . '../../assets/icons/e-control-duplicate.svg'; ?>" draggable="false">
                        </div>
                    <?php } ?>
                    <?php if ( !$remove_disabled ) { ?>
						<div class="piotnet-widget__controls-item piotnet-widget__controls-item--remove" title="Delete" draggable="false" data-piotnet-control-remove>
							<img src="<?php echo plugin_dir_url( __FILE__ ) . '../../assets/icons/e-control-remove.svg'; ?>" draggable="false">
						</div>
					<?php } ?>
				</div>
				<div class="piotnet-widget__container" data-piotnet-container>
			<?php
		} else {
			if ( !empty( $settings['multi_step_form_animation'] ) ) {
				$this->add_render_attribute(
					'wrapper',
					[
						'class' => 'piotnetforms-animation',
						'data-piotnetforms-animation' => !empty( $settings['multi_step_form_animation_animate'] ) ? $settings['multi_step_form_animation_animate'] : 'fadeIn',
						'data-piotnetforms-animation-delay' => !empty( $settings['multi_step_form_animation_delay'] ) ? $settings['multi_step_form_animation_delay'] : 0,
					]
				);
			}
		}

		$this->add_render_attribute( 'wrapper', 'class', $widget_id );

		if ( ! empty( $settings['advanced_custom_id'] ) ) {
			$this->add_render_attribute( 'wrapper', 'id', $settings['advanced_custom_id'] );
		}

		if ( ! empty( $settings['advanced_hide_desktop'] ) ) {
			$this->add_render_attribute( 'wrapper', 'class', $settings['advanced_hide_desktop'] );
		}

		if ( ! empty( $settings['advanced_hide_tablet'] ) ) {
			$this->add_render_attribute( 'wrapper', 'class', $settings['advanced_hide_tablet'] );
		}

		if ( ! empty( $settings['advanced_hide_mobile'] ) ) {
			$this->add_render_attribute( 'wrapper', 'class', $settings['advanced_hide_mobile'] );
		}

		if ( ! empty( $settings['advanced_custom_classes'] ) ) {
			$this->add_render_attribute( 'wrapper', 'class', $settings['advanced_custom_classes'] );
		}

		if ( ! empty( $settings['piotnetforms_repeater_enable_trigger'] ) && ! empty( $settings['piotnetforms_repeater_id_trigger'] ) && ! empty( $settings['piotnetforms_repeater_trigger_action'] ) ) {
			$form_post_id = $this->post_id;
			$form_version = empty( get_post_meta( $form_post_id, '_piotnetforms_version', true ) ) ? 1 : get_post_meta( $form_post_id, '_piotnetforms_version', true );
			$form_id = $form_version == 1 ? $settings['piotnetforms_repeater_form_id_trigger'] : $form_post_id;

			$this->add_render_attribute(
				'wrapper',
				[
					'data-piotnetforms-repeater-form-id-trigger' => $form_id,
					'data-piotnetforms-repeater-id-trigger' => $settings['piotnetforms_repeater_id_trigger'],
					'data-piotnetforms-repeater-trigger-action' => $settings['piotnetforms_repeater_trigger_action'],
				]
			);
			wp_enqueue_script( $this->slug . '-advanced-script' );
		}

		if ( $this->get_type() !== 'field' && $this->get_type() !== 'submit' && $this->get_type() !== 'multi-step-form' ) {
			if ( ! empty( $settings['piotnetforms_conditional_logic_form_enable_new'] ) && ! empty( $settings['piotnetforms_conditional_logic_form_form_id'] ) ) {
				if ( array_key_exists( 'piotnetforms_conditional_logic_form_list_new', $settings ) ) {
					$list_conditional = $settings['piotnetforms_conditional_logic_form_list_new'];
					$form_post_id = $this->post_id;
					$form_version = empty( get_post_meta( $form_post_id, '_piotnetforms_version', true ) ) ? 1 : get_post_meta( $form_post_id, '_piotnetforms_version', true );
					$form_id = $form_version == 1 ? $settings['piotnetforms_conditional_logic_form_form_id'] : $form_post_id;

					//if ( ! empty( $list_conditional[0]['piotnetforms_conditional_logic_form_if'] ) && ! empty( $list_conditional[0]['piotnetforms_conditional_logic_form_comparison_operators'] ) ) {
					$this->add_render_attribute(
						'wrapper',
						[
							'data-piotnetforms-conditional-logic' => json_encode( $list_conditional ),
							'data-piotnetforms-conditional-logic-not-field' => '',
							'data-piotnetforms-conditional-logic-not-field-form-id' => $form_id,
							'data-piotnetforms-conditional-logic-speed' => $settings['piotnetforms_conditional_logic_form_speed_new'],
							'data-piotnetforms-conditional-logic-easing' => $settings['piotnetforms_conditional_logic_form_easing_new'],
						]
					);
					//}

					wp_enqueue_script( $this->slug . '-advanced-script' );
				}
			}
		}

		if ( !empty( $settings['next_prev_multi_step_form'] ) ) {
			$this->add_render_attribute(
				'wrapper',
				[
					'data-piotnetforms-nav' => $settings['next_prev_multi_step_form_action'],
				]
			);
		}

		if ( !empty( $settings['attributes'] ) ) {
			$attributes = [];
			foreach ( $settings['attributes'] as $attribute ) {
				$attributes[$attribute['attribute_name']] = $attribute['attribute_value'];
			}
			$this->add_render_attribute(
				'wrapper',
				$attributes
			);
		}

		$this->before_render();

		if ( $visibility ) {
			$this->render( $editor );
			$this->dynamic_css();
			$this->custom_css();
		}

		if ( $editor ) {
			echo '</div></div>';
		}

		return ob_get_clean();
	}

	public function before_render() {
	}

	public $render_attributes = [];

	public function add_render_attribute( $element, $key = null, $value = null, $overwrite = false ) {
		if ( is_array( $element ) ) {
			foreach ( $element as $element_key => $attributes ) {
				$this->add_render_attribute( $element_key, $attributes, null, $overwrite );
			}

			return $this;
		}

		if ( is_array( $key ) ) {
			foreach ( $key as $attribute_key => $attributes ) {
				$this->add_render_attribute( $element, $attribute_key, $attributes, $overwrite );
			}

			return $this;
		}

		if ( empty( $this->render_attributes[ $element ][ $key ] ) ) {
			$this->render_attributes[ $element ][ $key ] = [];
		}

		settype( $value, 'array' );

		if ( $overwrite ) {
			$this->render_attributes[ $element ][ $key ] = $value;
		} else {
			$this->render_attributes[ $element ][ $key ] = array_merge( $this->render_attributes[ $element ][ $key ], $value );
		}

		return $this;
	}

	public function remove_render_attribute( $element, $key = null, $values = null ) {
		if ( $key && ! isset( $this->render_attributes[ $element ][ $key ] ) ) {
			return;
		}

		if ( $values ) {
			$values = (array) $values;

			$this->render_attributes[ $element ][ $key ] = array_diff( $this->render_attributes[ $element ][ $key ], $values );

			return;
		}

		if ( $key ) {
			unset( $this->render_attributes[ $element ][ $key ] );

			return;
		}

		if ( isset( $this->render_attributes[ $element ] ) ) {
			unset( $this->render_attributes[ $element ] );
		}
	}

	public static function render_html_attributes( array $attributes ) {
		$rendered_attributes = [];

		foreach ( $attributes as $attribute_key => $attribute_values ) {
			if ( is_array( $attribute_values ) ) {
				$attribute_values = implode( ' ', $attribute_values );
			}

			$rendered_attributes[] = sprintf( '%1$s="%2$s"', $attribute_key, esc_attr( $attribute_values ) );
		}

		return implode( ' ', $rendered_attributes );
	}

	public function get_render_attribute_string( $element ) {
		if ( empty( $this->render_attributes[ $element ] ) ) {
			return '';
		}

		return $this->render_html_attributes( $this->render_attributes[ $element ] );
	}
}
