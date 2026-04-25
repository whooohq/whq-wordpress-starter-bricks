<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Pagination extends Element {
	public $category = 'wordpress';
	public $name     = 'pagination';
	public $icon     = 'ti-angle-double-right';

	public function get_label() {
		return esc_html__( 'Pagination', 'bricks' );
	}

	public function set_controls() {
		$this->controls['queryId'] = [
			'tab'    => 'content',
			'label'  => esc_html__( 'Query', 'bricks' ),
			'type'   => 'query-list',
			'inline' => true,
		];

		$this->controls['justifyContent'] = [
			'tab'     => 'content',
			'label'   => esc_html__( 'Alignment', 'bricks' ),
			'type'    => 'justify-content',
			'exclude' => 'space',
			'inline'  => true,
			'css'     => [
				[
					'selector' => '.bricks-pagination',
					'property' => 'justify-content',
				],
			],
		];

		$this->controls['navigationHeight'] = [
			'tab'   => 'content',
			'type'  => 'number',
			'units' => true,
			'label' => esc_html__( 'Height', 'bricks' ),
			'css'   => [
				[
					'property' => 'height',
					'selector' => '.bricks-pagination ul .page-numbers',
				],
			],
		];

		$this->controls['navigationWidth'] = [
			'tab'   => 'content',
			'type'  => 'number',
			'units' => true,
			'label' => esc_html__( 'Width', 'bricks' ),
			'css'   => [
				[
					'property' => 'width',
					'selector' => '.bricks-pagination ul .page-numbers',
				],
			],
		];

		$this->controls['gap'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Spacing', 'bricks' ),
			'type'        => 'number',
			'units'       => true,
			'css'         => [
				[
					'selector' => 'ul',
					'property' => 'gap',
				],
			],
			'placeholder' => 20,
		];

		$this->controls['navigationBackground'] = [
			'tab'   => 'content',
			'type'  => 'color',
			'label' => esc_html__( 'Background', 'bricks' ),
			'css'   => [
				[
					'property' => 'background',
					'selector' => '.bricks-pagination ul .page-numbers',
				],
			],
		];

		$this->controls['navigationBorder'] = [
			'tab'   => 'content',
			'type'  => 'border',
			'label' => esc_html__( 'Border', 'bricks' ),
			'css'   => [
				[
					'property' => 'border',
					'selector' => '.bricks-pagination ul .page-numbers',
				],
			],
		];

		$this->controls['navigationTypography'] = [
			'tab'   => 'content',
			'type'  => 'typography',
			'label' => esc_html__( 'Typography', 'bricks' ),
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.bricks-pagination ul .page-numbers',
				],
			],
		];

		// CURRENT PAGE

		$this->controls['navigationActiveSeparator'] = [
			'tab'   => 'content',
			'type'  => 'separator',
			'label' => esc_html__( 'Current', 'bricks' ),
		];

		$this->controls['navigationBackgroundActive'] = [
			'tab'   => 'content',
			'type'  => 'color',
			'label' => esc_html__( 'Background', 'bricks' ),
			'css'   => [
				[
					'property' => 'background',
					'selector' => '.bricks-pagination ul .page-numbers.current',
				],
			],
		];

		$this->controls['navigationBorderActive'] = [
			'tab'   => 'content',
			'type'  => 'border',
			'label' => esc_html__( 'Border', 'bricks' ),
			'css'   => [
				[
					'property' => 'border',
					'selector' => '.bricks-pagination ul .page-numbers.current',
				],
			],
		];

		$this->controls['navigationTypographyActive'] = [
			'tab'   => 'content',
			'type'  => 'typography',
			'label' => esc_html__( 'Typography', 'bricks' ),
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.bricks-pagination ul .page-numbers.current',
				],
			],
		];

		// ICONS

		$this->controls['iconSeparator'] = [
			'type'  => 'separator',
			'label' => esc_html__( 'Icons', 'bricks' ),
		];

		$this->controls['prevIcon'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Previous Icon', 'bricks' ),
			'type'  => 'icon',
		];

		$this->controls['nextIcon'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Next Icon', 'bricks' ),
			'type'  => 'icon',
		];

		// MISC

		$this->controls['miscSeparator'] = [
			'type'  => 'separator',
			'label' => esc_html__( 'Miscellaneous', 'bricks' ),
		];

		$this->controls['endSize'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'End Size', 'bricks' ),
			'type'        => 'number',
			'min'         => 1,
			'placeholder' => 1,
			'description' => esc_html__( 'How many numbers on either the start and the end list edges.', 'bricks' ),
		];

		$this->controls['midSize'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Mid Size', 'bricks' ),
			'type'        => 'number',
			'min'         => 1,
			'placeholder' => 2,
			'description' => esc_html__( 'How many numbers on either side of the current page.', 'bricks' ),
		];

		$this->controls['ajax'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Enable AJAX', 'bricks' ),
			'type'        => 'checkbox',
			'description' => esc_html__( 'Navigate through the different query pages without reloading the page.', 'bricks' ),
			'required'    => [ 'queryId', '!=', [ '', 'main' ] ],
		];
	}

	public function render() {
		$settings = $this->settings;

		// Query from a Query Loop
		if ( ! empty( $settings['queryId'] ) && $settings['queryId'] !== 'main' ) {
			$element_settings = Helpers::get_element_settings( $this->post_id, $settings['queryId'] );

			if ( empty( $element_settings ) ) {
				return $this->render_element_placeholder(
					[
						'title' => esc_html__( 'The query element doesn\'t exist.', 'bricks' ),
					]
				);
			}

			$query_obj = new Query(
				[
					'id'       => $settings['queryId'],
					'settings' => $element_settings,
				]
			);

			// Support pagination for post, user and term query object type (@since 1.9.1)
			if ( ! in_array( $query_obj->object_type, [ 'post','user','term' ] ) ) {
				return $this->render_element_placeholder(
					[
						'title' => esc_html__( 'This query type doesn\'t support pagination.', 'bricks' ),
					]
				);
			}

			// Use Bricks query object to get the current page and total pages as global $wp_query might be changed and inconsistent (#86bwqwa31)
			$current_page = isset( $query_obj->query_vars['paged'] ) ? max( 1, $query_obj->query_vars['paged'] ) : 1;
			$total_pages  = $query_obj->max_num_pages;

			// Destroy query to explicitly remove it from the global store
			$query_obj->destroy();
			unset( $query_obj );
		}

		// Default: Main query
		else {
			global $wp_query;
			$current_page = max( 1, $wp_query->get( 'paged', 1 ) );
			$total_pages  = $wp_query->max_num_pages;
		}

		// Return: Less than two pages (@since 1.9.1)
		if ( $total_pages <= 1 && ( bricks_is_builder_call() || bricks_is_builder() ) ) {
			return $this->render_element_placeholder(
				[
					'title' => esc_html__( 'No pagination results.', 'bricks' ),
				]
			);
		}

		// Hooks
		add_filter( 'bricks/paginate_links_args', [ $this, 'pagination_args' ] );

		// Render
		$pagination = Helpers::posts_navigation( $current_page, $total_pages );

		// Reset hooks
		remove_filter( 'bricks/paginate_links_args', [ $this, 'pagination_args' ] );

		if ( is_singular() && ! strlen( $pagination ) ) {
			return $this->render_element_placeholder(
				[
					'title' => esc_html__( 'No pagination on singular posts/pages.', 'bricks' ),
				]
			);
		}

		$this->set_ajax_attributes();

		echo "<div {$this->render_attributes( '_root' )}>" . $pagination . '</div>';
	}

	public function pagination_args( $args ) {
		$settings = $this->settings;

		if ( ! empty( $settings['prevIcon'] ) ) {
			$args['prev_text'] = self::render_icon( $settings['prevIcon'] );
		}

		if ( ! empty( $settings['nextIcon'] ) ) {
			$args['next_text'] = self::render_icon( $settings['nextIcon'] );
		}

		if ( ! empty( $settings['endSize'] ) ) {
			$args['end_size'] = $settings['endSize'];
		}

		if ( ! empty( $settings['midSize'] ) ) {
			$args['mid_size'] = $settings['midSize'];
		}

		return $args;
	}

	/**
	 * Set AJAX attributes
	 */
	private function set_ajax_attributes() {
		$settings = $this->settings;

		if ( ! isset( $settings['ajax'] ) || empty( $settings['queryId'] ) || $settings['queryId'] === 'main' ) {
			return;
		}

		if ( ! Helpers::enabled_query_filters() ) {
			// Normal AJAX pagination
			$this->set_attribute( '_root', 'class', 'brx-ajax-pagination' );
			$this->set_attribute( '_root', 'data-query-element-id', $settings['queryId'] );
		} else {
			// Filter type AJAX Pagination
			$filter_settings = [
				'filterId'            => $this->id,
				'targetQueryId'       => $settings['queryId'],
				'filterAction'        => 'filter',
				'filterType'          => 'pagination',
				'filterMethod'        => 'ajax',
				'filterApplyOn'       => 'change',
				'filterInputDebounce' => 500,
			];
			$this->set_attribute( '_root', 'data-brx-filter', wp_json_encode( $filter_settings ) );
		}
	}

}
