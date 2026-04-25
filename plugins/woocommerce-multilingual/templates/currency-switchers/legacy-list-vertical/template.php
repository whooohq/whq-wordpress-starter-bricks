<?php
/**
 * @var string $css_classes
 * @var string $format
 * @var string $selected_currency
 * @var string[] $currencies
 */
?>
<div class="<?php echo esc_attr( $css_classes ); ?>">
	<ul>
		<?php foreach ( $currencies as $currency ) : ?>
			<li <?php echo ( $currency == $selected_currency ) ? 'class="wcml-cs-active-currency"' : ''; ?>>
				<a rel="<?php echo esc_attr( $currency ); ?>"><?php echo wp_kses_post( WCML_Currency_Switcher_Template::get_formatted_price( $currency, $format ) ); ?></a>
			</li>
		<?php endforeach; ?>
	</ul>
</div>