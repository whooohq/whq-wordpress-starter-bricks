<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Element_Code extends Element {
	public $block    = [ 'core/code', 'core/preformatted' ];
	public $category = 'general';
	public $name     = 'code';
	public $icon     = 'ion-ios-code';
	public $scripts  = [ 'bricksPrettify' ];

	public function enqueue_scripts() {
		if ( ! empty( $this->theme_styles['prettify'] ) || ! empty( $this->settings['prettify'] ) ) {
			wp_enqueue_script( 'bricks-prettify' );
			wp_enqueue_style( 'bricks-prettify' );
		}
	}

	public function get_label() {
		return esc_html__( 'Code', 'bricks' );
	}

	public function get_keywords() {
		return [ 'code', 'css', 'html', 'javascript', 'js', 'php', 'script', 'snippet', 'style' ];
	}

	public function set_controls() {
		$this->controls['code'] = [
			'tab'       => 'content',
			'type'      => 'code',
			'mode'      => 'php', // 'css', 'javascript', 'php',
			'clearable' => false, // Required to always have 'mode' set for CodeMirror
			'default'   => "<style>\nh1.my-heading {\n  color: crimson;\n}\n</style>\n\n<h1 class='my-heading'>Just some custom HTML</h1>",
			'required'  => [ 'useDynamicData', '=', '' ],
			'rerender'  => true,
		];

		$this->controls['useDynamicData'] = [
			'deprecated'  => true, // @since 1.9.5
			'tab'         => 'content',
			'label'       => '',
			'type'        => 'text',
			'placeholder' => esc_html__( 'Select dynamic data', 'bricks' ),
			'required'    => [ 'code', '=', '' ],
		];

		$user_can_execute_code = Capabilities::current_user_can_execute_code();
		if ( $user_can_execute_code ) {
			$this->controls['executeCode'] = [
				'tab'   => 'content',
				'label' => esc_html__( 'Execute code', 'bricks' ),
				'type'  => 'checkbox',
			];

			$this->controls['noRoot'] = [
				'tab'         => 'content',
				'label'       => esc_html__( 'Render without wrapper', 'bricks' ),
				'type'        => 'checkbox',
				'description' => esc_html__( 'Render on the front-end without the div wrapper.', 'bricks' ),
				'required'    => [ 'executeCode', '!=', '' ],
			];

			$this->controls['infoExecuteCode'] = [
				'tab'      => 'content',
				'content'  => esc_html__( 'Important: The code above will run on your site! Only add code that you consider safe. Especially when executing PHP & JS code.', 'bricks' ),
				'type'     => 'info',
				'required' => [ 'executeCode', '!=', '' ],
			];
		}

		// Code execution not allowed
		else {
			$this->controls['infoExecuteCodeOff'] = [
				'tab'     => 'content',
				'content' => esc_html__( 'Code execution not allowed.', 'bricks' ) . ' ' . esc_html__( 'You can manage code execution permissions under: Bricks > Settings > Builder Access > Code Execution', 'bricks' ),
				'type'    => 'info',
			];
		}

		$this->controls['language'] = [
			'tab'            => 'content',
			'label'          => esc_html__( 'Language', 'bricks' ),
			'type'           => 'text',
			'hasDynamicData' => false,
			'placeholder'    => esc_html__( 'Auto detect', 'bricks' ),
			'description'    => esc_html__( 'Set language if auto detect fails (e.g. "css").', 'bricks' ),
			'required'       => [ 'executeCode', '=', '' ],
		];
	}

	public function render() {
		$settings = $this->settings;
		$code     = $settings['code'] ?? false;

		// STEP: Get Dynamic code
		if ( ! empty( $settings['useDynamicData'] ) ) {
			$dynamic_data_code = $this->render_dynamic_data_tag( $settings['useDynamicData'] );

			if ( empty( $dynamic_data_code ) ) {
				return $this->render_element_placeholder(
					[
						'title' => esc_html__( 'Dynamic data is empty.', 'bricks' )
					]
				);
			}

			$code = $dynamic_data_code;
		}

		// STEP: Execute code
		if ( isset( $settings['executeCode'] ) ) {
			// Return: Code execution not enabled (Bricks setting or filter)
			if ( ! Helpers::code_execution_enabled() ) {
				return $this->render_element_placeholder(
					[
						'title'       => esc_html__( 'Code execution not allowed.', 'bricks' ),
						'description' => esc_html__( 'You can manage code execution permissions under: Bricks > Settings > Builder Access > Code Execution', 'bricks' )
					]
				);
			}

			// Sanitize element code
			$post_id = Database::$page_data['preview_or_post_id'] ?? $this->post_id;

			// Get code signature
			$signature = $settings['signature'] ?? false;

			// Verfiy code signature
			$code = Helpers::sanitize_element_php_code( $post_id, $this->id, $code, $signature );

			// Return error: Code signature not valid
			if ( isset( $code['error'] ) ) {
				return $this->render_element_placeholder( [ 'title' => $code['error'] ], 'error' );
			}

			// Sets context on AJAX/REST API calls or when reloading the builder
			if ( bricks_is_builder() || bricks_is_builder_call() ) {
				global $post;

				$post = get_post( $this->post_id );

				setup_postdata( $post );
			}

			ob_start();

			// Prepare & set error reporting
			$error_reporting = error_reporting( E_ALL );
			$display_errors  = ini_get( 'display_errors' );
			ini_set( 'display_errors', 1 );

			try {
				$result = eval( ' ?>' . $code . '<?php ' );
			} catch ( \Exception $error ) {
				echo 'Exception: ' . $error->getMessage();

				return;
			} catch ( \ParseError $error ) {
				echo 'ParseError: ' . $error->getMessage();

				return;
			} catch ( \Error $error ) {
				echo 'Error: ' . $error->getMessage();

				return;
			}

			// Reset error reporting
			ini_set( 'display_errors', $display_errors );
			error_reporting( $error_reporting );

			// @see https://www.php.net/manual/en/function.eval.php
			if ( version_compare( PHP_VERSION, '7', '<' ) && $result === false || ! empty( $error ) ) {
				$output = $error;

				ob_end_clean();
			} else {
				$output = ob_get_clean();
			}

			if ( bricks_is_builder() || bricks_is_builder_call() ) {
				wp_reset_postdata();
			}

			// No root wrapper (frontend only, wrapper required in builder to get all inner nodes)
			if ( isset( $settings['noRootForce'] ) || ( isset( $settings['noRoot'] ) && ! bricks_is_builder() && ! bricks_is_builder_call() ) ) {
				echo $output;
			} else {
				echo "<div {$this->render_attributes( '_root' )}>{$output}</div>";
			}

			return;
		}

		// Default: Print code snippet
		$theme = false;

		if ( ! empty( $this->theme_styles['prettify'] ) ) {
			$theme = $this->theme_styles['prettify'];
		} elseif ( ! empty( $settings['prettify'] ) ) {
			$theme = $settings['prettify'];
		}

		$language = ! empty( $settings['language'] ) ? ' lang-' . strtolower( $settings['language'] ) : '';

		// Escaping
		$code = esc_html( $code );

		// If code comes already formatted, assure the language is set and leave
		if ( strpos( $code, '<pre' ) === 0 ) {
			$code = $theme && ! empty( $language ) ? str_replace( 'class="prettyprint', 'class="prettyprint' . $language . ' ', $code ) : $code;

			echo $code;

			return;
		}

		// Prettyprint theme set
		if ( $theme ) {
			echo "<div {$this->render_attributes( '_root' )}>";
			echo '<pre class="prettyprint ' . $theme . $language . '"><code>' . $code . '</code></pre>';
			echo '</div>';
		} else {
			// Default: Code snippet
			echo "<pre {$this->render_attributes( '_root' )}>{$code}</pre>";
		}
	}

	public function convert_element_settings_to_block( $settings ) {
		if ( isset( $settings['executeCode'] ) ) {
			return;
		}

		if ( ! empty( $settings['useDynamicData'] ) ) {
			$code = $this->render_dynamic_data_tag( $settings['useDynamicData'] );

			// If code comes already formatted, extract the code only
			if ( strpos( $code, '<pre' ) === 0 ) {
				preg_match( '#<\s*?code\b[^>]*>(.*?)</code\b[^>]*>#s', $code, $matches );
				$code = isset( $matches[1] ) ? $matches[1] : $code;
			}
		} else {
			$code = isset( $settings['code'] ) ? trim( $settings['code'] ) : '';
		}

		$html = '<pre class="wp-block-code"><code>' . esc_html( $code ) . '</code></pre>';

		$block = [
			'blockName'    => 'core/code',
			'attrs'        => [],
			'innerContent' => [ $html ],
		];

		return $block;
	}

	public function convert_block_to_element_settings( $block, $attributes ) {
		$code = trim( $block['innerHTML'] );
		$code = substr( $code, strpos( $code, '>' ) + 1 ); // Remove starting <pre>
		$code = substr_replace( $code, '', -6 ); // Remove last </pre>

		// Remove <code> (core/code block)
		if ( substr( $code, 0, 6 ) === '<code>' ) {
			$code = substr( $code, strpos( $code, '>' ) + 1 ); // Remove starting <code>
			$code = substr_replace( $code, '', -7 ); // Remove last </code>
		}

		return [ 'code' => $code ];
	}
}
