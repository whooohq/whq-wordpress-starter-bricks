<?php

namespace OTGS\Installer\Templates\Repository;

use WP_Installer;

class Registered {

	public static function render( $model ) {
		$expires = call_user_func( $model->whenExpires );
		$message = $expires ?
			sprintf(
				__( '%s is registered on this site using the following sitekey: <strong>%s</strong>. You will receive automatic updates until %s', 'installer' ),
				$model->productName,
				self::getMaskedSiteKey( $model->repoId ),
				date_i18n( 'F j, Y', strtotime( $expires ) )
			) :
			sprintf(
				__( '%s is registered on this site using the following sitekey: <strong>%s</strong>. Your Lifetime account gives you updates for life.', 'installer' ),
				$model->productName,
				self::getMaskedSiteKey( $model->repoId )
			);

		?>
		<div class="otgs-installer-registered wp-clearfix">
			<div class="inline otgs-installer-notice otgs-installer-notice-confirm otgs-installer-notice-<?php echo $model->repoId; ?>">
				<div class="otgs-installer-notice-content">
					<?php
						echo wp_kses(
								$message,
								[
									'strong' => []
								]
						)
					?>
					<?php \OTGS\Installer\Templates\Repository\RegisteredButtons::render( $model ); ?>
				</div>
			</div>
		</div>
		<?php

	}

	/**
	 * @param $repoId
	 * @return string
	 */
	private static function getMaskedSiteKey( $repoId ) {
		$siteKey = \WP_Installer()->get_site_key( $repoId );
		return str_repeat('*', strlen( $siteKey ) - 4 ) . substr( $siteKey, -4 );
	}
}
