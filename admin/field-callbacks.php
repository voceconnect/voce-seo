<?php

if ( !function_exists( 'vseo_field_text' ) ) {

	function vseo_field_text( $field_id, $value = '', $args = array( ) ) {
		if ( empty( $value ) && !empty( $args['default'] ) ) {
			$value = $args['default'];
		}

		$html = sprintf( '<input type="text" id="%1$s" name="%1$s" class="widefat" value="%2$s" />', esc_attr( $field_id ), esc_attr( $value ) );

		if ( !empty( $args['description'] ) )
			$html .=  wp_kses_post( (string) $args['description'] );

		return $html;
	}

}

if ( !function_exists( 'vseo_field_textarea' ) ) {

	function vseo_field_textarea( $field_id, $value = '', $args = array( ) ) {

		if ( empty( $value ) && !empty( $args['default'] ) ) {
			$value = $args['default'];
		}

		$html = sprintf( '<textarea id="%1$s" name="%1$s" class="widefat" rows="7" cols="50" type="textarea" class="widefat">%2$s</textarea>', esc_attr( $field_id ), esc_attr( $value ) );

		if ( !empty( $args['description'] ) )
			$html .= wp_kses_post( (string) $args['description'] );

		return $html;
	}

}

if ( !function_exists( 'vseo_field_select' ) ) {

	function vseo_field_select( $field_id, $value = null, $args = array( ) ) {
		if ( !isset( $args['options'] ) ) {
			$html = '<p class="error">An options argument is required</p>';
		} else {
			if ( !in_array( $value, $args['options'] ) && isset( $args['default'] ) ) {
				$value = $args['default'];
			}

			$html = sprintf( '<select id="%1$s" name="%1$s">', esc_attr( $field_id ) );

			foreach ( $args['options'] as $option_value => $option_text ) {
				$html .= sprintf( '<option value="%1$s" %3$s>%2$s</option>', esc_attr( $option_value ), esc_html( $option_text ), selected($option_value, $value, false) );
			}
			$html.= '</select>';
			if ( !empty( $args['description'] ) )
				$html .= wp_kses_post( (string) $args['description'] );
		}
		$html = '<br/>'.$html;

		return $html;
	}

}

if ( !function_exists( 'vseo_sanitize_select' ) ) {

	function vseo_sanitize_select( $value = '', $args = array( ) ) {
		if ( in_array( $value, $args['options'] ) ) {
			return $value;
		}
		return isset($args['default']) ? $args['default'] : null;
	}

}

if ( !function_exists( 'vseo_sanitize_url' ) ) {

	function vseo_sanitize_url( $value = '', $args = array( ) ) {
		return esc_url_raw( $value );
	}

}