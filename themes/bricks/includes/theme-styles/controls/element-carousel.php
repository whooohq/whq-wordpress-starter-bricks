<?php
$swiper_controls = \Bricks\Element_Carousel::get_swiper_controls();
$controls        = [];

// ARROWS
$controls['arrowsSeparator'] = [
	'type'  => 'separator',
	'label' => esc_html__( 'Arrows', 'bricks' ),
];

$controls['arrowHeight']     = $swiper_controls['arrowHeight'];
$controls['arrowWidth']      = $swiper_controls['arrowWidth'];
$controls['arrowBackground'] = $swiper_controls['arrowBackground'];
$controls['arrowBorder']     = $swiper_controls['arrowBorder'];
$controls['arrowTypography'] = $swiper_controls['arrowTypography'];

$controls['prevArrowSeparator'] = [
	'type'  => 'separator',
	'label' => esc_html__( 'Prev arrow', 'bricks' ),
];

$controls['prevArrow']       = $swiper_controls['prevArrow'];
$controls['prevArrowTop']    = $swiper_controls['prevArrowTop'];
$controls['prevArrowRight']  = $swiper_controls['prevArrowRight'];
$controls['prevArrowBottom'] = $swiper_controls['prevArrowBottom'];
$controls['prevArrowLeft']   = $swiper_controls['prevArrowLeft'];

$controls['nextArrowSeparator'] = [
	'type'  => 'separator',
	'label' => esc_html__( 'Next arrow', 'bricks' ),
];

$controls['nextArrow']       = $swiper_controls['nextArrow'];
$controls['nextArrowTop']    = $swiper_controls['nextArrowTop'];
$controls['nextArrowRight']  = $swiper_controls['nextArrowRight'];
$controls['nextArrowBottom'] = $swiper_controls['nextArrowBottom'];
$controls['nextArrowLeft']   = $swiper_controls['nextArrowLeft'];

// DOTS

$controls['dotsSeparator'] = [
	'type'  => 'separator',
	'label' => esc_html__( 'Dots', 'bricks' ),
];

$controls['dotsHeight']      = $swiper_controls['dotsHeight'];
$controls['dotsWidth']       = $swiper_controls['dotsWidth'];
$controls['dotsTop']         = $swiper_controls['dotsTop'];
$controls['dotsRight']       = $swiper_controls['dotsRight'];
$controls['dotsBottom']      = $swiper_controls['dotsBottom'];
$controls['dotsLeft']        = $swiper_controls['dotsLeft'];
$controls['dotsBorder']      = $swiper_controls['dotsBorder'];
$controls['dotsColor']       = $swiper_controls['dotsColor'];
$controls['dotsActiveColor'] = $swiper_controls['dotsActiveColor'];
$controls['dotsSpacing']     = $swiper_controls['dotsSpacing'];

foreach ( $controls as $key => $control ) {
	unset( $controls[ $key ]['group'] );
	unset( $controls[ $key ]['required'] );
}

return [
	'name'        => 'carousel',
	'controls'    => $controls,
	'cssSelector' => '.brxe-carousel',
];
