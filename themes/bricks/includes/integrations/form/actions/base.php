<?php
namespace Bricks\Integrations\Form\Actions;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

abstract class Base {
	/**
	 * Action name
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * The Contructor
	 *
	 * @param string $name Action name.
	 */
	public function __construct( $name ) {
		$this->name = $name;

		$this->init_action();
	}

	/**
	 * Init action
	 *
	 * @return void
	 */
	public function init_action() {}

	/**
	 * Run action
	 *
	 * @param Bricks\Integrations\Form\Init $form
	 * @return void
	 */
	public function run( $form ) {}
}
