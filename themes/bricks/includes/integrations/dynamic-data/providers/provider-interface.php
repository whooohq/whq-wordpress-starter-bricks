<?php

namespace Bricks\Integrations\Dynamic_Data\Providers;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

interface Provider_Interface {

	const CONTEXT_TEXT = 'text';
	const CONTEXT_LINK = 'link';

	const CONTEXT_IMAGE = 'image';
	const CONTEXT_VIDEO = 'video'; // Deprecated

	const CONTEXT_MEDIA = 'media'; // Used by audio, maybe in future will replace video and image (should return an array of media Ids and/or URLs)

	const CONTEXT_LOOP = 'loop';

}
