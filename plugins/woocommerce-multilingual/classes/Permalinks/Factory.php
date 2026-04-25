<?php

namespace WCML\Permalinks;

use function WPML\Container\make;

class Factory implements \IWPML_Backend_Action_Loader {

	public function create() {
		return [
			make( Settings\TranslationControls::class ),
		];
	}

}
