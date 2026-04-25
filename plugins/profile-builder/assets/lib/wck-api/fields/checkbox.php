<?php
 /* @param string $meta Meta name.
 * @param array $details Contains the details for the field.
 * @param string $value Contains input value;
 * @param string $context Context where the function is used. Depending on it some actions are preformed.;
 * @return string $element input element html string. */

if( !empty( $details['options'] ) ){

    $options_number = count( $details['options'] );

    if ( $options_number > 1 ) {
        $checkboxes_wrapper_open = '<div class="wck-checkboxes cozmoslabs-checkbox-list '. (( $details['slug'] === 'buttons-order' ) ? ' cozmoslabs-checkbox-1-col-list' : ' cozmoslabs-checkbox-2-col-list') .'">';
        $checkboxes_wrapper_close = '</div>';
        $toggle_container_extra_class = 'cozmoslabs-chckbox-container';
    }
    else {
        $checkboxes_wrapper_open = '';
        $checkboxes_wrapper_close = '';
        $toggle_container_extra_class = 'cozmoslabs-toggle-container';
    }

	$element .= $checkboxes_wrapper_open;
	foreach( $details['options'] as $option ){
		$found = false;

		if( !is_array( $value ) )
			$values = !empty( $value ) ? explode( ', ', $value ) : array();
		else
			$values = $value;

		if( strpos( $option, '%' ) === false  ){
			$label = $option;
			$value_attr = $option;
			if ( in_array( $option, $values ) )
				$found = true;
		}
		else{
			$option_parts = explode( '%', $option );
			if( !empty( $option_parts ) ){
				if( empty( $option_parts[0] ) && count( $option_parts ) == 3 ){
					$label = $option_parts[1];
					$value_attr = $option_parts[2];
					if ( in_array( $option_parts[2], $values ) )
						$found = true;
				}
			}
		}

        $element .= '<div class="'. $toggle_container_extra_class .'"><input type="checkbox" name="'. $single_prefix . esc_attr( Wordpress_Creation_Kit_PB::wck_generate_slug( $details['title'], $details ) );
		if( $this->args['single'] && $this->args['context'] != 'option' ) {
			$element .= '[]';
		}
        $element .= '" id="';

        if( !empty( $frontend_prefix ) )
			$element .= $frontend_prefix;

        /* since the slug below is generated from the value as well we need to determine here if we have a slug or not and not let the wck_generate_slug() function do that */
        if( !empty( $details['slug'] ) )
            $slug_from = $details['slug'];
        else
            $slug_from = $details['title'];

        $context_slug = !empty($context) ? $context . '_' : '';

		$element .= esc_attr( Wordpress_Creation_Kit_PB::wck_generate_slug( $context_slug . $slug_from . '_' . $value_attr ) ) .'" value="'. esc_attr( $value_attr ) .'"  '. checked( $found, true, false ) .'class="mb-checkbox mb-field" />';

        if ( $options_number > 1 )
            $element .= '<label for="'. esc_attr( Wordpress_Creation_Kit_PB::wck_generate_slug( $context_slug . $slug_from . '_' . $value_attr ) ) .'">'. esc_html( $label ) .'</label>';
        else
            $element .= '<label class="cozmoslabs-toggle-track" for="'. esc_attr( Wordpress_Creation_Kit_PB::wck_generate_slug( $context_slug . $slug_from . '_' . $value_attr ) ) .'"></label>';

        $element .= '</div>';

        if ( $options_number == 1 ){
			$include_title_in_toggle = '<strong>'. $details['title'] .'</strong>';

			if( isset( $details['include_title_in_toggle'] ) && $details['include_title_in_toggle'] == false )
				$include_title_in_toggle = '';

			$element .= '<div class="cozmoslabs-toggle-description">
				<label for="'. esc_attr( Wordpress_Creation_Kit_PB::wck_generate_slug( $context_slug . $slug_from . '_' . $value_attr ) ) .'" class="cozmoslabs-description">'. esc_html__( 'Enable ', 'profile-builder' ) . $include_title_in_toggle .'</label>
			</div>';
		}

	}

	$element .= $checkboxes_wrapper_close;
}
?>