<?php
/**
 * @var string $css_classes
 * @var string $format
 * @var string $selected_currency
 * @var string[] $currencies
 */
?>
<div class="<?php echo esc_attr( $css_classes ) ?>">
	<ul>
		<li class="wcml-cs-active-currency">
			<a class="wcml-cs-item-toggle"><?php echo wp_kses_post( WCML_Currency_Switcher_Template::get_formatted_price( $selected_currency, $format ) ); ?></a>
			<ul class="wcml-cs-submenu">
				<?php foreach ( $currencies as $currency ) : ?>
					<?php if ( $currency != $selected_currency ) : ?>
						<li>
							<a rel="<?php echo esc_attr( $currency ); ?>"><?php echo wp_kses_post( WCML_Currency_Switcher_Template::get_formatted_price( $currency, $format ) ); ?></a>
						</li>
					<?php endif; ?>
				<?php endforeach; ?>
			</ul>
		</li>
	</ul>
</div>