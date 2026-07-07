<?php
/**
 * Base di conoscenza condivisa: wiki admin + contesto AI.
 *
 * @package SalientCustomElements
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SCE_Reference {

	public const TIER_COLORS     = 'colors';
	public const TIER_TYPOGRAPHY = 'typography';
	public const TIER_LAYOUT     = 'layout';

	/**
	 * Documentazione ufficiale linkata.
	 *
	 * @return array<int,array{title:string,url:string,note:string}>
	 */
	public static function external_docs(): array {
		return array(
			array(
				'title' => __( 'Salient Docs', 'salient-custom-elements' ),
				'url'   => 'https://themenectar.com/docs/salient/',
				'note'  => __( 'Official theme documentation.', 'salient-custom-elements' ),
			),
			array(
				'title' => __( 'Salient Page Builder / Global Sections', 'salient-custom-elements' ),
				'url'   => 'https://themenectar.com/docs/salient/page-builder-global-sections/',
				'note'  => __( 'Global Sections and Salient page builder.', 'salient-custom-elements' ),
			),
			array(
				'title' => __( 'WPBakery vc_map API', 'salient-custom-elements' ),
				'url'   => 'https://kb.wpbakery.com/docs/inner-api/vc_map/',
				'note'  => __( 'API for registering custom shortcodes.', 'salient-custom-elements' ),
			),
			array(
				'title' => __( 'WPBakery Knowledge Base', 'salient-custom-elements' ),
				'url'   => 'https://kb.wpbakery.com',
				'note'  => __( 'Knowledge base WPBakery.', 'salient-custom-elements' ),
			),
			array(
				'title' => __( 'WPBakery Support', 'salient-custom-elements' ),
				'url'   => 'https://support.wpbakery.com',
				'note'  => __( 'Official WPBakery support.', 'salient-custom-elements' ),
			),
		);
	}

	/**
	 * Param types standard WPBakery.
	 *
	 * @return array<int,array{type:string,recommended:bool,note:string}>
	 */
	public static function wpbakery_param_types(): array {
		$recommended = array(
			'textfield', 'textarea', 'dropdown', 'checkbox', 'attach_image',
			'attach_images', 'colorpicker', 'href', 'vc_link', 'css_editor', 'iconpicker',
		);
		$all = array(
			'textfield', 'dropdown', 'textarea_html', 'checkbox', 'posttypes', 'taxonomies',
			'exploded_textarea', 'textarea', 'attach_images', 'attach_image',
			'widgetised_sidebars', 'colorpicker', 'loop', 'vc_link', 'options',
			'sorted_list', 'css_editor', 'font_container', 'google_fonts', 'autocomplete',
			'tab_id', 'href', 'params_preset', 'param_group', 'custom_markup',
			'animation_style', 'iconpicker', 'el_id', 'gutenberg', 'textarea_ace', 'range',
			'column_offset',
		);

		$out = array();
		foreach ( $all as $type ) {
			$out[] = array(
				'type'        => $type,
				'recommended' => in_array( $type, $recommended, true ),
				'note'        => in_array( $type, $recommended, true )
					? __( 'Recommended for generated elements.', 'salient-custom-elements' )
					: __( 'Use only when necessary.', 'salient-custom-elements' ),
			);
		}
		return $out;
	}

	/**
	 * Param types custom Salient.
	 *
	 * @return array<int,array{type:string,recommended:bool,avoid_for_ai:bool,note:string}>
	 */
	public static function salient_param_types(): array {
		return array(
			array( 'type' => 'nectar_theme_color', 'recommended' => true, 'avoid_for_ai' => false, 'note' => __( 'Theme color dropdown with swatches.', 'salient-custom-elements' ) ),
			array( 'type' => 'nectar_numerical', 'recommended' => true, 'avoid_for_ai' => false, 'note' => __( 'Responsive margins/padding.', 'salient-custom-elements' ) ),
			array( 'type' => 'nectar_range_slider', 'recommended' => false, 'avoid_for_ai' => false, 'note' => __( 'Numeric slider.', 'salient-custom-elements' ) ),
			array( 'type' => 'fws_image', 'recommended' => false, 'avoid_for_ai' => false, 'note' => __( 'Full-width section image.', 'salient-custom-elements' ) ),
			array( 'type' => 'nectar_radio_html', 'recommended' => false, 'avoid_for_ai' => false, 'note' => __( 'Visual shape/underline selection.', 'salient-custom-elements' ) ),
			array( 'type' => 'dropdown_multi', 'recommended' => false, 'avoid_for_ai' => false, 'note' => __( 'Multiple selection.', 'salient-custom-elements' ) ),
			array( 'type' => 'nectar_cf_repeater', 'recommended' => false, 'avoid_for_ai' => true, 'note' => __( 'Too complex for AI generation.', 'salient-custom-elements' ) ),
			array( 'type' => 'hotspot_image_preview', 'recommended' => false, 'avoid_for_ai' => true, 'note' => __( 'Avoid for AI.', 'salient-custom-elements' ) ),
			array( 'type' => 'nectar_box_shadow_generator', 'recommended' => false, 'avoid_for_ai' => true, 'note' => __( 'Avoid for AI.', 'salient-custom-elements' ) ),
			array( 'type' => 'nectar_lottie', 'recommended' => false, 'avoid_for_ai' => true, 'note' => __( 'Avoid for AI.', 'salient-custom-elements' ) ),
			array( 'type' => 'nectar_global_section_select', 'recommended' => false, 'avoid_for_ai' => true, 'note' => __( 'Avoid for AI.', 'salient-custom-elements' ) ),
		);
	}

	/**
	 * Opzioni tema raggruppate per tier.
	 *
	 * @return array<string,array<int,array{key:string,label:string,value:string}>>
	 */
	public static function bindable_options_grouped(): array {
		$groups = array(
			self::TIER_COLORS     => array(),
			self::TIER_TYPOGRAPHY => array(),
			self::TIER_LAYOUT     => array(),
		);

		if ( class_exists( 'NectarThemeManager' ) && ! empty( NectarThemeManager::$available_theme_colors ) ) {
			foreach ( NectarThemeManager::$available_theme_colors as $key => $label ) {
				$groups[ self::TIER_COLORS ][] = self::bindable_row( (string) $key, (string) $label );
			}
		} else {
			foreach ( array(
				'accent-color'  => __( 'Accent color', 'salient-custom-elements' ),
				'extra-color-1' => __( 'Extra color 1', 'salient-custom-elements' ),
				'extra-color-2' => __( 'Extra color 2', 'salient-custom-elements' ),
				'extra-color-3' => __( 'Extra color 3', 'salient-custom-elements' ),
			) as $key => $label ) {
				$groups[ self::TIER_COLORS ][] = self::bindable_row( $key, $label );
			}
		}

		foreach ( array(
			'extra-color-gradient'   => __( 'Theme gradient 1', 'salient-custom-elements' ),
			'extra-color-gradient-2' => __( 'Theme gradient 2', 'salient-custom-elements' ),
			'overall-font-color'     => __( 'Overall font color', 'salient-custom-elements' ),
			'overall-bg-color'       => __( 'Overall background', 'salient-custom-elements' ),
			'body-border-color'      => __( 'Body border color', 'salient-custom-elements' ),
		) as $key => $label ) {
			$groups[ self::TIER_COLORS ][] = self::bindable_row( $key, $label );
		}

		$font_keys = array(
			'body_font_family', 'navigation_font_family', 'page_heading_font_family',
			'h1_font_family', 'h2_font_family', 'h3_font_family', 'logo_font_family',
		);
		$opts = SCE_Salient::options();
		foreach ( $opts as $key => $val ) {
			if ( is_string( $key ) && str_ends_with( $key, '_font_family' ) && ! in_array( $key, $font_keys, true ) ) {
				$font_keys[] = $key;
			}
		}
		foreach ( $font_keys as $key ) {
			$groups[ self::TIER_TYPOGRAPHY ][] = self::bindable_row(
				$key,
				ucwords( str_replace( '_', ' ', $key ) )
			);
		}

		foreach ( array(
			'button-styling'           => __( 'Button styling', 'salient-custom-elements' ),
			'button-styling-roundness' => __( 'Button roundness', 'salient-custom-elements' ),
			'column-spacing'           => __( 'Column spacing', 'salient-custom-elements' ),
			'max_container_width'      => __( 'Max container width', 'salient-custom-elements' ),
			'theme-skin'               => __( 'Theme skin', 'salient-custom-elements' ),
			'ext_responsive_padding'   => __( 'Responsive padding', 'salient-custom-elements' ),
		) as $key => $label ) {
			$groups[ self::TIER_LAYOUT ][] = self::bindable_row( $key, $label );
		}

		return $groups;
	}

	/**
	 * Mappa piatta key => label (compatibilità).
	 *
	 * @return array<string,string>
	 */
	public static function bindable_options_flat(): array {
		$flat = array();
		foreach ( self::bindable_options_grouped() as $tier => $items ) {
			foreach ( $items as $item ) {
				$flat[ $item['key'] ] = $item['label'];
			}
		}
		return $flat;
	}

	/**
	 * Etichette tier per la wiki.
	 *
	 * @return array<string,string>
	 */
	public static function tier_labels(): array {
		return array(
			self::TIER_COLORS     => __( 'Colors', 'salient-custom-elements' ),
			self::TIER_TYPOGRAPHY => __( 'Typography', 'salient-custom-elements' ),
			self::TIER_LAYOUT     => __( 'Layout', 'salient-custom-elements' ),
		);
	}

	/**
	 * Pattern vc_map Salient.
	 *
	 * @return array<int,string>
	 */
	public static function vc_map_patterns(): array {
		return array(
			__( 'Salient overrides vc_row and vc_column: do not use standard WPB container maps.', 'salient-custom-elements' ),
			__( 'Salient elements use vc_lean_map() with files in salient-core/includes/nectar_maps/{base}.php.', 'salient-custom-elements' ),
			__( 'Structure: return array( name, base, category, icon, params => [...] ).', 'salient-custom-elements' ),
			__( 'Theme colors in dropdowns: accent-color, extra-color-1, extra-color-gradient-1 (lowercase).', 'salient-custom-elements' ),
			__( 'Use group (e.g. Design Options) and dependency for conditional show/hide.', 'salient-custom-elements' ),
			__( 'For SCE elements: template with semantic HTML ONLY and {{param}} tokens; no raw CSS/JS.', 'salient-custom-elements' ),
		);
	}

	/**
	 * Esempi golden da nectar_maps.
	 *
	 * @return array<int,array{base:string,name:string,source:string,params:array}>
	 */
	public static function golden_elements(): array {
		$bases  = array( 'nectar_btn', 'nectar_cta', 'nectar_highlighted_text' );
		$out    = array();
		$maps_dir = WP_PLUGIN_DIR . '/salient-core/includes/nectar_maps/';

		foreach ( $bases as $base ) {
			$from_map = SCE_Salient::wpbakery_shortcodes( true );
			if ( isset( $from_map[ $base ] ) ) {
				$def    = $from_map[ $base ];
				$params = array();
				if ( ! empty( $def['params'] ) && is_array( $def['params'] ) ) {
					foreach ( array_slice( $def['params'], 0, 8 ) as $p ) {
						$params[] = array(
							'param_name' => $p['param_name'] ?? '',
							'type'       => $p['type'] ?? '',
							'heading'    => $p['heading'] ?? '',
						);
					}
				}
				$out[] = array(
					'base'   => $base,
					'name'   => (string) ( $def['name'] ?? $base ),
					'source' => 'salient-core/includes/nectar_maps/' . $base . '.php',
					'params' => $params,
				);
				continue;
			}

			$file = $maps_dir . $base . '.php';
			if ( is_file( $file ) ) {
				$map = include $file;
				if ( is_array( $map ) ) {
					$params = array();
					if ( ! empty( $map['params'] ) && is_array( $map['params'] ) ) {
						foreach ( array_slice( $map['params'], 0, 8 ) as $p ) {
							$params[] = array(
								'param_name' => $p['param_name'] ?? '',
								'type'       => $p['type'] ?? '',
								'heading'    => is_string( $p['heading'] ?? '' ) ? wp_strip_all_tags( (string) $p['heading'] ) : '',
							);
						}
					}
					$out[] = array(
						'base'   => $base,
						'name'   => (string) ( $map['name'] ?? $base ),
						'source' => 'salient-core/includes/nectar_maps/' . $base . '.php',
						'params' => $params,
					);
				}
			}
		}

		return $out;
	}

	/**
	 * Payload compatto per il prompt AI.
	 *
	 * @return array<string,mixed>
	 */
	public static function context_for_ai(): array {
		$bindable = array();
		$grouped  = self::bindable_options_grouped();
		foreach ( array( self::TIER_COLORS, self::TIER_TYPOGRAPHY ) as $tier ) {
			foreach ( array_slice( $grouped[ $tier ] ?? array(), 0, 8 ) as $item ) {
				$bindable[ $item['key'] ] = $item['label'];
			}
		}
		foreach ( array_slice( $grouped[ self::TIER_LAYOUT ] ?? array(), 0, 4 ) as $item ) {
			$bindable[ $item['key'] ] = $item['label'];
		}

		$param_types = array();
		foreach ( self::wpbakery_param_types() as $pt ) {
			if ( $pt['recommended'] ) {
				$param_types[] = $pt['type'];
			}
		}
		foreach ( self::salient_param_types() as $pt ) {
			if ( $pt['recommended'] && ! $pt['avoid_for_ai'] ) {
				$param_types[] = $pt['type'];
			}
		}

		return array(
			'documentation'    => self::external_docs(),
			'param_types'      => $param_types,
			'bindable_options' => $bindable,
			'vc_map_patterns'  => self::vc_map_patterns(),
			'golden_elements'  => self::golden_elements(),
		);
	}

	/**
	 * Path sorgente nectar_map per un base.
	 */
	public static function nectar_map_source( string $base ): string {
		return 'salient-core/includes/nectar_maps/' . sanitize_file_name( $base ) . '.php';
	}

	/**
	 * @return array{key:string,label:string,value:string}
	 */
	private static function bindable_row( string $key, string $label ): array {
		$val = SCE_Salient::option( $key, '' );
		$val = is_scalar( $val ) ? (string) $val : wp_json_encode( $val );
		return array(
			'key'   => $key,
			'label' => $label,
			'value' => $val,
		);
	}
}
