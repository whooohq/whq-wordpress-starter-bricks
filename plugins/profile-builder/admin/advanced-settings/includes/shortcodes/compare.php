<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

add_shortcode( 'compare', 'wppb_toolbox_compare_shortcode' );
function wppb_toolbox_compare_shortcode( $atts, $content ){

    $atts = shortcode_atts( array(
        'val1'     => '',
        'val2'     => '',
        'operator' => '',
    ), $atts, 'compare' );

	foreach($atts as $key => $value){
		$atts[$key] = str_replace('&#8221;', '', $value );
	}

	$l = $atts['val1'];
	$r = $atts['val2'];

    $operators = array(
        '=='    => function($l, $r) {
                    return $l == $r;
                },
        '==='   => function($l, $r) {
                    return $l === $r;
                },
        '!='    => function($l, $r) {
                    return $l != $r;
                },
        '<'     => function($l, $r) {
                    return $l < $r;
                },
        '>'     => function($l, $r) {
                    return $l > $r;
                },
        '<='    => function($l, $r) {
                    return $l <= $r;
                },
        '>='    => function($l, $r) {
                    return $l >= $r;
                },
        ''      => function($l, $r) {
                    return $l == $r;
                },
    );

	if ( !array_key_exists($atts['operator'], $operators ) )
		return '<p>The compare operator <strong style="padding:0 10px;">' . esc_html( $atts["operator"] ) . '</strong> is not recognized. Please try: == , ===, !=, <, >, <=, >=';

	$bool = $operators[$atts['operator']]($l, $r);

	if( $bool )
		return do_shortcode( $content );
}
