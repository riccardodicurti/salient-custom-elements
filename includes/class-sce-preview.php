<?php
/**
 * Pagina privata di anteprima per ogni elemento generato.
 *
 * @package SalientCustomElements
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SCE_Preview {

	private const META = '_sce_preview_id';

	/**
	 * Crea o aggiorna la pagina privata di anteprima per un elemento.
	 *
	 * @return int|WP_Error ID pagina anteprima.
	 */
	public static function sync( int $element_id, array $def ) {
		$shortcode = self::build_sample_shortcode( $def );
		$content   = '[vc_row type="in_container"][vc_column width="1/1"]' . $shortcode . '[/vc_column][/vc_row]';

		$postarr = array(
			'post_type'    => 'page',
			'post_status'  => 'private',
			'post_title'   => sprintf(
				/* translators: %s: element name */
				__( 'Preview: %s', 'salient-custom-elements' ),
				$def['name'] ?? ''
			),
			'post_content' => $content,
		);

		$preview_id = (int) get_post_meta( $element_id, self::META, true );

		if ( $preview_id > 0 && get_post( $preview_id ) ) {
			$postarr['ID'] = $preview_id;
			$result        = wp_update_post( $postarr, true );
		} else {
			$result = wp_insert_post( $postarr, true );
			if ( ! is_wp_error( $result ) ) {
				update_post_meta( $element_id, self::META, (int) $result );
			}
		}

		if ( ! is_wp_error( $result ) && (int) $result > 0 ) {
			update_post_meta( (int) $result, '_wpb_vc_js_status', 'true' );
			update_post_meta( (int) $result, '_wpb_post_custom_css', '' );
		}

		return $result;
	}

	/**
	 * URL della pagina di anteprima, o null se assente.
	 */
	public static function get_url( int $element_id ): ?string {
		$preview_id = (int) get_post_meta( $element_id, self::META, true );
		if ( $preview_id <= 0 ) {
			return null;
		}
		$url = get_permalink( $preview_id );
		return is_string( $url ) && '' !== $url ? $url : null;
	}

	/**
	 * Elimina la pagina di anteprima associata a un elemento.
	 */
	public static function delete( int $element_id ): void {
		$preview_id = (int) get_post_meta( $element_id, self::META, true );
		if ( $preview_id > 0 ) {
			wp_delete_post( $preview_id, true );
			delete_post_meta( $element_id, self::META );
		}
	}

	/**
	 * Costruisce uno shortcode con valori std o segnaposto generici.
	 */
	public static function build_sample_shortcode( array $def ): string {
		$base = $def['base'] ?? '';
		if ( '' === $base ) {
			return '';
		}

		$attrs = array();
		if ( ! empty( $def['params'] ) && is_array( $def['params'] ) ) {
			foreach ( $def['params'] as $param ) {
				if ( empty( $param['param_name'] ) ) {
					continue;
				}
				$name  = $param['param_name'];
				$value = isset( $param['std'] ) ? (string) $param['std'] : '';
				if ( '' === $value ) {
					$value = self::placeholder_for_param( $param );
				}
				$attrs[] = $name . '="' . esc_attr( $value ) . '"';
			}
		}

		$attr_string = empty( $attrs ) ? '' : ' ' . implode( ' ', $attrs );
		return '[' . $base . $attr_string . ']';
	}

	/**
	 * Segnaposto generico in base al tipo di parametro WPBakery.
	 */
	private static function placeholder_for_param( array $param ): string {
		$type = $param['type'] ?? 'textfield';
		switch ( $type ) {
			case 'textarea':
			case 'textarea_html':
				return __( 'Sample text for preview.', 'salient-custom-elements' );
			case 'href':
			case 'vc_link':
				return '#';
			case 'attach_image':
			case 'attach_images':
				return '';
			case 'colorpicker':
				return '';
			default:
				$heading = $param['heading'] ?? $param['param_name'] ?? '';
				if ( '' !== $heading ) {
					return sprintf(
						/* translators: %s: param heading */
						__( 'Example: %s', 'salient-custom-elements' ),
						$heading
					);
				}
				return __( 'Example', 'salient-custom-elements' );
		}
	}
}
