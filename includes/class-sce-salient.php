<?php
/**
 * Ponte verso Salient: opzioni tema + estrazione a runtime della mappa WPBakery.
 *
 * Questa classe e' la "documentazione a runtime" di cui parlavamo: invece di
 * imballare i doc di Salient/WPBakery, leggiamo dall'install le definizioni reali
 * degli elementi e i valori delle opzioni tema. Cosi' gli elementi generati
 * restano allineati alla versione di Salient installata.
 *
 * @package SalientCustomElements
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SCE_Salient {

	/**
	 * Ritorna le opzioni del tema Salient (Redux) come array.
	 *
	 * Il nome dell'helper e' cambiato tra le versioni di Salient. Proviamo le
	 * strade note in ordine e degradiamo a array vuoto.
	 *
	 * @return array<string,mixed>
	 */
	public static function options(): array {
		if ( function_exists( 'get_nectar_theme_options' ) ) {
			$opts = get_nectar_theme_options();
			if ( is_array( $opts ) ) {
				return $opts;
			}
		}

		// Fallback: option Redux grezza. Il key set piu' comune di Salient e' 'salient_redux'.
		foreach ( array( 'salient_redux', 'salient', 'nectar' ) as $key ) {
			$opts = get_option( $key );
			if ( is_array( $opts ) && ! empty( $opts ) ) {
				return $opts;
			}
		}

		return array();
	}

	/**
	 * Legge una singola opzione Salient con default.
	 *
	 * @param string $key     Chiave opzione.
	 * @param mixed  $default Valore di ripiego.
	 * @return mixed
	 */
	public static function option( string $key, $default = '' ) {
		$opts = self::options();
		return array_key_exists( $key, $opts ) && '' !== $opts[ $key ] ? $opts[ $key ] : $default;
	}

	/**
	 * Risolve un valore binding Salient in stringa sicura per CSS/HTML (typography array, colori, stringhe).
	 *
	 * @param string               $key  Chiave opzione Salient.
	 * @param array<string,mixed>|null $opts Opzioni tema (default: options()).
	 */
	public static function binding_value( string $key, ?array $opts = null ): string {
		if ( null === $opts ) {
			$opts = self::options();
		}

		if ( ! isset( $opts[ $key ] ) || '' === $opts[ $key ] ) {
			return '';
		}

		$val = $opts[ $key ];

		if ( is_string( $val ) || is_numeric( $val ) ) {
			$str = trim( (string) $val );
			if ( '' === $str || '-' === $str ) {
				return '';
			}
			return esc_attr( $str );
		}

		if ( ! is_array( $val ) ) {
			return '';
		}

		if ( isset( $val['font-family'] ) && is_string( $val['font-family'] ) ) {
			$family = trim( $val['font-family'] );
			if ( '' === $family || '-' === $family ) {
				return '';
			}
			if ( str_contains( $family, '"' ) ) {
				return htmlspecialchars( $family, ENT_NOQUOTES, 'UTF-8' );
			}
			return esc_attr( $family );
		}

		$legacy = preg_replace( '/_font_family$/', '', $key );
		if ( $legacy !== $key && isset( $opts[ $legacy ] ) && is_string( $opts[ $legacy ] ) ) {
			$family = trim( $opts[ $legacy ] );
			if ( '' === $family || '-' === $family ) {
				return '';
			}
			if ( preg_match( '/[0-9]/', $family ) ) {
				$family = '"' . $family . '"';
			}
			return esc_attr( $family );
		}

		return '';
	}

	/**
	 * Chiavi opzione piu' utili come binding di default per gli elementi.
	 * (Verifica i nomi esatti sulla tua versione di Salient e affina questa mappa.)
	 *
	 * @return array<string,string> token => descrizione
	 */
	public static function bindable_options(): array {
		if ( class_exists( 'SCE_Reference' ) ) {
			return SCE_Reference::bindable_options_flat();
		}
		return array(
			'accent-color'       => __( 'Accent color', 'salient-custom-elements' ),
			'extra-color-1'      => __( 'Extra color 1', 'salient-custom-elements' ),
			'extra-color-2'      => __( 'Extra color 2', 'salient-custom-elements' ),
			'body_font_family'   => __( 'Body font', 'salient-custom-elements' ),
			'navigation_font_family' => __( 'Navigation font', 'salient-custom-elements' ),
			'button-styling'     => __( 'Button styling', 'salient-custom-elements' ),
		);
	}

	/**
	 * Estrae la mappa degli shortcode registrati da WPBakery, filtrando gli
	 * elementi Salient (nectar_*). Serve a due cose: dare all'AI i pattern reali
	 * dei parametri, e permetterci di clonare le convenzioni Salient.
	 *
	 * @param bool $salient_only Se true, ritorna solo gli elementi nectar_*.
	 * @return array<string,array> base => definizione vc_map
	 */
	public static function wpbakery_shortcodes( bool $salient_only = true ): array {
		if ( ! class_exists( 'WPBMap' ) ) {
			return array();
		}

		// WPBMap potrebbe non aver ancora inizializzato la mappa completa.
		if ( method_exists( 'WPBMap', 'getShortCodes' ) ) {
			$all = WPBMap::getShortCodes();
		} else {
			return array();
		}

		if ( ! is_array( $all ) ) {
			return array();
		}

		if ( ! $salient_only ) {
			return $all;
		}

		$filtered = array();
		foreach ( $all as $base => $def ) {
			if ( 0 === strpos( (string) $base, 'nectar_' ) ) {
				$filtered[ $base ] = $def;
			}
		}

		return $filtered;
	}

	/**
	 * Riferimento compatto pensato per essere passato al prompt dell'AI:
	 * lista di elementi Salient con i loro parametri principali.
	 *
	 * @return array<int,array{base:string,name:string,params:array}>
	 */
	public static function reference_for_ai(): array {
		$ref = array();
		foreach ( self::wpbakery_shortcodes( true ) as $base => $def ) {
			$params = array();
			if ( ! empty( $def['params'] ) && is_array( $def['params'] ) ) {
				foreach ( $def['params'] as $p ) {
					$params[] = array(
						'param_name' => $p['param_name'] ?? '',
						'type'       => $p['type'] ?? '',
						'heading'    => $p['heading'] ?? '',
					);
				}
			}
			$ref[] = array(
				'base'   => (string) $base,
				'name'   => (string) ( $def['name'] ?? $base ),
				'params' => $params,
			);
		}
		return $ref;
	}
}
