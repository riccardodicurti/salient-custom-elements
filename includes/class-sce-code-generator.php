<?php
/**
 * Generatore di codice: definizione -> file PHP standalone e production-ready.
 *
 * Il file prodotto dipende SOLO da WordPress, WPBakery e Salient. Non richiama
 * nessuna classe di questo plugin, cosi' resta valido quando rimuovi il
 * generatore in produzione. Applica le regole di SCE_Rules: output escapato,
 * markup semantico, attributi di accessibilita', CSS responsive scoped.
 *
 * @package SalientCustomElements
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SCE_Code_Generator {

	/**
	 * Cartella dei file generati (scrivibile, con guardie anti accesso diretto).
	 */
	public static function dir(): string {
		$uploads = wp_upload_dir();
		$dir     = trailingslashit( $uploads['basedir'] ) . 'sce-generated';

		if ( ! is_dir( $dir ) ) {
			wp_mkdir_p( $dir );
			@file_put_contents( $dir . '/index.php', "<?php // Silence is golden.\n" );
			@file_put_contents( $dir . '/.htaccess', "Require all denied\n" );
		}

		return $dir;
	}

	public static function path( string $base ): string {
		return self::dir() . '/' . sanitize_file_name( $base ) . '.php';
	}

	/**
	 * Genera e scrive il file dell'elemento. Ritorna il path o WP_Error.
	 *
	 * @return string|WP_Error
	 */
	public static function write( array $definition ) {
		$definition = SCE_Element_Store::sanitize( $definition );
		if ( '' === $definition['base'] ) {
			return new WP_Error( 'sce_gen_no_base', __( 'Missing base, unable to generate.', 'salient-custom-elements' ) );
		}

		$path    = self::path( $definition['base'] );
		$written = file_put_contents( $path, self::build( $definition ) );

		if ( false === $written ) {
			return new WP_Error( 'sce_gen_write', __( 'Unable to write the generated file. Check uploads permissions.', 'salient-custom-elements' ) );
		}

		return $path;
	}

	public static function delete( string $base ): void {
		$path = self::path( $base );
		if ( is_file( $path ) ) {
			@unlink( $path );
		}
	}

	/**
	 * Legge il PHP generato (per la review). Stringa vuota se assente.
	 */
	public static function read( string $base ): string {
		$path = self::path( $base );
		return is_file( $path ) ? (string) file_get_contents( $path ) : '';
	}

	/**
	 * Costruisce il sorgente PHP standalone dell'elemento.
	 *
	 * Nota: precalcoliamo tutte le stringhe var_export e interpoliamo solo
	 * variabili semplici nell'heredoc (le chiamate a closure in {$...} non sono
	 * affidabili nell'heredoc).
	 */
	public static function build( array $definition ): string {
		$base = $definition['base'];
		$fn   = 'sce_gen_' . $base;
		$name = $definition['name'];
		$cat  = $definition['category'] ?: 'Salient Custom';
		$icon = $definition['icon'] ?: 'icon-wpb-salient';

		$defaults    = array();
		$types       = array();
		$salient_map = array();
		foreach ( $definition['params'] as $p ) {
			$defaults[ $p['param_name'] ] = $p['std'] ?? '';
			$types[ $p['param_name'] ]    = $p['type'] ?? 'textfield';
			if ( ! empty( $p['salient_option'] ) ) {
				$salient_map[ $p['param_name'] ] = $p['salient_option'];
			}
		}

		// Precalcolo dei literal PHP.
		$vc_params_ex   = var_export( self::vc_params( $definition['params'] ), true );
		$salient_map_ex = var_export( $salient_map, true );
		$defaults_ex    = var_export( $defaults, true );
		$types_ex       = var_export( $types, true );
		$bindings_ex    = var_export( $definition['bindings'], true );
		$template_ex    = var_export( $definition['template'], true );
		$css_ex         = var_export( SCE_Rules::responsive_css( $base ), true );
		$name_ex        = var_export( $name, true );
		$base_ex        = var_export( $base, true );
		$cat_ex         = var_export( $cat, true );
		$icon_ex        = var_export( $icon, true );

		$header  = "<?php\n/**\n";
		$header .= ' * Generato da Salient Custom Elements. DA REVISIONARE prima della produzione.' . "\n";
		$header .= ' * Elemento: ' . str_replace( array( '*/', "\n" ), ' ', $name ) . '  |  base: ' . $base . "\n";
		$header .= ' * Standalone: dipende solo da WordPress + WPBakery + Salient.' . "\n";
		$header .= ' * Generato il: ' . gmdate( 'Y-m-d H:i' ) . " UTC\n */\n";

		$body = <<<PHP

if ( ! defined( 'ABSPATH' ) ) { exit; }

/* ---- Helper condivisi (definiti una sola volta) ---- */
if ( ! function_exists( 'sce_shipped_color' ) ) {
	function sce_shipped_color( \$c ) {
		\$c = trim( (string) \$c );
		if ( '' === \$c ) { return ''; }
		\$hex = sanitize_hex_color( \$c );
		if ( \$hex ) { return \$hex; }
		if ( preg_match( '/^(rgb|rgba|hsl|hsla)\\([0-9.,%\\s\\/]+\\)\$/i', \$c ) ) { return \$c; }
		return '';
	}
}
if ( ! function_exists( 'sce_shipped_esc' ) ) {
	function sce_shipped_esc( \$type, \$value ) {
		switch ( \$type ) {
			case 'colorpicker':
				return sce_shipped_color( \$value );
			case 'vc_link':
			case 'href':
			case 'url':
				return esc_url( \$value );
			case 'attach_image':
				return (string) absint( \$value );
			case 'textarea':
				return nl2br( esc_html( \$value ) );
			default:
				return esc_html( \$value );
		}
	}
}

/* ---- Registrazione elemento in WPBakery ---- */
if ( ! function_exists( '{$fn}_map' ) ) {
	add_action( 'vc_before_init', '{$fn}_map' );
	function {$fn}_map() {
		if ( ! function_exists( 'vc_map' ) ) { return; }

		\$params      = {$vc_params_ex};
		\$salient_map = {$salient_map_ex};
		\$opts        = function_exists( 'get_nectar_theme_options' ) ? (array) get_nectar_theme_options() : array();

		foreach ( \$params as &\$param ) {
			\$pn = isset( \$param['param_name'] ) ? \$param['param_name'] : '';
			if ( isset( \$salient_map[ \$pn ] ) && ( '' === ( isset( \$param['value'] ) ? \$param['value'] : '' ) ) ) {
				\$k = \$salient_map[ \$pn ];
				\$param['value'] = isset( \$opts[ \$k ] ) ? \$opts[ \$k ] : '';
			}
		}
		unset( \$param );

		\$sce_name = {$name_ex};
		\$sce_desc = 'Elemento custom (Salient).';
		do_action( 'wpml_register_single_string', 'salient-shipped-elements', '{$base}_name', \$sce_name );
		do_action( 'wpml_register_single_string', 'salient-shipped-elements', '{$base}_desc', \$sce_desc );
		\$sce_name = apply_filters( 'wpml_translate_single_string', \$sce_name, 'salient-shipped-elements', '{$base}_name' );
		\$sce_desc = apply_filters( 'wpml_translate_single_string', \$sce_desc, 'salient-shipped-elements', '{$base}_desc' );

		vc_map( array(
			'name'        => \$sce_name,
			'base'        => {$base_ex},
			'category'    => {$cat_ex},
			'icon'        => {$icon_ex},
			'description' => \$sce_desc,
			'params'      => \$params,
		) );
	}
}

/* ---- Rendering ---- */
if ( ! function_exists( '{$fn}_render' ) ) {
	add_shortcode( {$base_ex}, '{$fn}_render' );
	function {$fn}_render( \$atts, \$content = '' ) {
		\$defaults    = {$defaults_ex};
		\$types       = {$types_ex};
		\$salient_map = {$salient_map_ex};
		\$bindings    = {$bindings_ex};
		\$template    = {$template_ex};

		\$a    = shortcode_atts( \$defaults, is_array( \$atts ) ? \$atts : array(), {$base_ex} );
		\$opts = function_exists( 'get_nectar_theme_options' ) ? (array) get_nectar_theme_options() : array();

		foreach ( \$salient_map as \$pn => \$k ) {
			if ( isset( \$a[ \$pn ] ) && '' === \$a[ \$pn ] ) {
				\$a[ \$pn ] = isset( \$opts[ \$k ] ) ? \$opts[ \$k ] : '';
			}
		}

		\$tokens = array();
		foreach ( \$a as \$pn => \$val ) {
			\$type = isset( \$types[ \$pn ] ) ? \$types[ \$pn ] : 'textfield';
			\$tokens[ '{{' . \$pn . '}}' ] = sce_shipped_esc( \$type, \$val );
		}
		foreach ( \$bindings as \$token => \$k ) {
			\$safe = isset( \$opts[ \$k ] ) ? esc_attr( \$opts[ \$k ] ) : '';
			\$tokens[ '{{binding:' . \$k . '}}' ] = \$safe;
			\$tokens[ '{{' . \$token . '}}' ]      = \$safe;
		}
		\$tokens['{{content}}'] = do_shortcode( wp_kses_post( (string) \$content ) );

		\$html = strtr( \$template, \$tokens );
		\$html = preg_replace( '/\\{\\{[a-z0-9_:\\-]+\\}\\}/i', '', \$html );

		{$fn}_css();

		return '<section class="sce-el sce-{$base}">' . \$html . '</section>';
	}
}

/* ---- CSS scoped, responsive e accessibile (una volta sola) ---- */
if ( ! function_exists( '{$fn}_css' ) ) {
	function {$fn}_css() {
		static \$done = false;
		if ( \$done ) { return; }
		\$done   = true;
		\$handle = 'sce-{$base}';
		wp_register_style( \$handle, false, array(), null );
		wp_enqueue_style( \$handle );
		wp_add_inline_style( \$handle, {$css_ex} );
	}
}
PHP;

		return $header . $body . "\n";
	}

	/**
	 * Converte i parametri della definizione in formato vc_map.
	 */
	private static function vc_params( array $params ): array {
		$mapped = array();
		foreach ( $params as $p ) {
			$entry = array(
				'type'        => $p['type'] ?: 'textfield',
				'heading'     => $p['heading'] ?: $p['param_name'],
				'param_name'  => $p['param_name'],
				'description' => $p['description'] ?? '',
				'value'       => $p['value'] ?? ( $p['std'] ?? '' ),
			);
			if ( ! empty( $p['group'] ) ) {
				$entry['group'] = $p['group'];
			}
			$mapped[] = $entry;
		}
		return $mapped;
	}
}
