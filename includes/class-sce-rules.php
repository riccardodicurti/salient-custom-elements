<?php
/**
 * Regole di produzione: SEO, sicurezza, accessibilita' (WCAG 2.2 AA), responsive.
 *
 * Usate da: generatore di codice, contesto AI, auditor e wiki interna.
 * Le stringhe sono traducibili (gettext -> WPML String Translation).
 *
 * @package SalientCustomElements
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SCE_Rules {

	public const ALLOWED_HEADING_TAGS = array( 'h2', 'h3', 'h4' );

	/**
	 * Regole in forma leggibile, iniettate nel prompt AI e mostrate nella wiki.
	 *
	 * @return array<string,string[]>
	 */
	public static function ruleset(): array {
		return array(
			'security' => array(
				__( 'Every dynamic value must be escaped on output: esc_html for text, esc_attr for attributes, esc_url for URLs, wp_kses_post for rich content.', 'salient-custom-elements' ),
				__( 'Never use eval, create_function, dynamic includes, or unescaped output.', 'salient-custom-elements' ),
				__( 'Inputs go through shortcode_atts with explicit defaults; no extract().', 'salient-custom-elements' ),
				__( 'Colors pass through sanitize_hex_color (or rgb/hsl whitelist); image IDs through absint.', 'salient-custom-elements' ),
				__( 'Every file has an ABSPATH guard and prefixed functions to avoid collisions.', 'salient-custom-elements' ),
			),
			'seo' => array(
				__( 'Semantic HTML5: section/article/header/figure/nav instead of generic divs.', 'salient-custom-elements' ),
				__( 'One logical heading per element, configurable level among h2, h3, h4, without skipping levels.', 'salient-custom-elements' ),
				__( 'Images with meaningful alt, loading="lazy", decoding="async", and width/height to prevent CLS.', 'salient-custom-elements' ),
				__( 'Descriptive links, never "click here"; rel="noopener" on target _blank.', 'salient-custom-elements' ),
				__( 'No informational text injected only via CSS.', 'salient-custom-elements' ),
			),
			'accessibility' => array(
				__( 'Interactive elements keyboard-focusable with visible focus (:focus-visible).', 'salient-custom-elements' ),
				__( 'Discernible text or aria-label on buttons and links; empty alt for decorative images.', 'salient-custom-elements' ),
				__( 'ARIA only when needed; prefer native elements.', 'salient-custom-elements' ),
				__( 'Respect prefers-reduced-motion for every animation.', 'salient-custom-elements' ),
				__( 'Touch targets at least 44x44px; no information conveyed by color alone; AA contrast.', 'salient-custom-elements' ),
			),
			'responsive' => array(
				__( 'Mobile-first, fluid units (rem, %, clamp()), no fixed widths that break the layout.', 'salient-custom-elements' ),
				__( 'Images with max-width:100% and height:auto.', 'salient-custom-elements' ),
				__( 'Flex/grid layouts that reflow; breakpoints consistent with Salient.', 'salient-custom-elements' ),
				__( 'Fluid type with clamp(); custom properties for gap and colors.', 'salient-custom-elements' ),
			),
			'template' => array(
				__( 'The template contains ONLY semantic HTML with {{param_name}} and {{binding:opt-key}} tokens.', 'salient-custom-elements' ),
				__( 'FORBIDDEN in template: raw CSS, JavaScript, <script> tags, <style> tags, IIFEs, window.addEventListener.', 'salient-custom-elements' ),
				__( 'Put all element CSS in the styles field (scoped under .sce-{base}). Put JavaScript in the scripts field.', 'salient-custom-elements' ),
				__( 'Base responsive CSS (focus, images, reduced-motion) is added automatically; extend it in styles.', 'salient-custom-elements' ),
			),
		);
	}

	/**
	 * Etichette tradotte dei gruppi di regole.
	 *
	 * @return array<string,string>
	 */
	public static function group_labels(): array {
		return array(
			'security'      => __( 'Security', 'salient-custom-elements' ),
			'seo'           => __( 'SEO', 'salient-custom-elements' ),
			'accessibility' => __( 'Accessibility', 'salient-custom-elements' ),
			'responsive'    => __( 'Responsive', 'salient-custom-elements' ),
			'template'      => __( 'HTML Template', 'salient-custom-elements' ),
		);
	}

	/**
	 * CSS scoped, responsive e accessibile per un elemento (iniettato una volta).
	 */
	public static function responsive_css( string $base ): string {
		$c = '.sce-' . $base;
		return implode(
			'',
			array(
				$c . '{--sce-gap:clamp(.75rem,2vw,1.25rem);display:block;box-sizing:border-box}',
				$c . ' :where(img){max-width:100%;height:auto}',
				$c . ' :where(a,button):focus-visible{outline:2px solid currentColor;outline-offset:2px}',
				$c . '__title{font-size:clamp(1.5rem,1rem + 2.5vw,2.5rem);line-height:1.2;margin:0 0 var(--sce-gap)}',
				$c . '__subtitle{font-size:clamp(1rem,.9rem + .8vw,1.25rem);line-height:1.5;margin:0 0 var(--sce-gap)}',
				$c . '__btn{display:inline-flex;align-items:center;justify-content:center;padding:.75rem 1.5rem;border-radius:.375rem;color:#fff;text-decoration:none;min-height:44px;min-width:44px;line-height:1.4}',
				'@media (max-width:600px){' . $c . '{text-align:center}}',
				'@media (prefers-reduced-motion:reduce){' . $c . ' *{animation:none!important;transition:none!important;scroll-behavior:auto!important}}',
			)
		);
	}

	/**
	 * Audit best-effort di una definizione: avvisi da mostrare in review.
	 *
	 * @param array $definition Definizione elemento.
	 * @return string[]
	 */
	public static function audit( array $definition ): array {
		$warnings = array();
		$tpl      = (string) ( $definition['template'] ?? '' );

		if ( ! preg_match( '/<h[2-4][\s>]/i', $tpl ) && false === strpos( $tpl, '{{heading_tag}}' ) ) {
			$warnings[] = __( 'SEO: no heading (h2-h4) in the template. Add one for page structure.', 'salient-custom-elements' );
		}
		if ( preg_match( '/<img\b(?![^>]*\balt=)/i', $tpl ) ) {
			$warnings[] = __( 'Accessibility: an <img> is missing the alt attribute.', 'salient-custom-elements' );
		}
		if ( preg_match( '/target=("|\')_blank\1(?![^>]*rel=)/i', $tpl ) ) {
			$warnings[] = __( 'Security: link with target=_blank without rel="noopener".', 'salient-custom-elements' );
		}
		if ( preg_match( '/<(div|span)\b/i', $tpl ) && ! preg_match( '/<(section|article|header|figure|nav|aside)\b/i', $tpl ) ) {
			$warnings[] = __( 'SEO: only div/span, no semantic tags. Consider section/article/figure.', 'salient-custom-elements' );
		}
		if ( preg_match( '/\son[a-z]+\s*=/i', $tpl ) ) {
			$warnings[] = __( 'Security: on* attributes (inline handlers) in the template. Remove them.', 'salient-custom-elements' );
		}

		if ( '' === trim( (string) ( $definition['styles'] ?? '' ) ) && preg_match( '/class="[^"]*sce-[^"]+"/i', $tpl ) ) {
			$warnings[] = __( 'The template uses custom CSS classes but the styles field is empty. Add scoped CSS in styles.', 'salient-custom-elements' );
		}

		$template_err = self::validate_template( $tpl );
		if ( is_wp_error( $template_err ) ) {
			$warnings[] = $template_err->get_error_message();
		}

		return $warnings;
	}

	/**
	 * Sanitizza il template preservando tag e token dinamici {{param}} / {{binding:key}}.
	 */
	public static function sanitize_template( string $template ): string {
		if ( '' === trim( $template ) ) {
			return '';
		}

		$placeholders = array();
		$index        = 0;

		$protect = static function ( array $matches ) use ( &$placeholders, &$index ): string {
			$key                    = "\x00SCEPH{$index}\x00";
			$placeholders[ $key ] = $matches[0];
			++$index;
			return $key;
		};

		$tpl = preg_replace_callback( '/<\{\{[a-z0-9_-]+\}\}(?:\s[^>]*)?>/i', $protect, $template );
		$tpl = preg_replace_callback( '/<\/\{\{[a-z0-9_-]+\}\}>/i', $protect, $tpl );
		$tpl = preg_replace_callback( '/\{\{[a-z0-9_:-]+\}\}/i', $protect, $tpl );
		$tpl = wp_kses_post( $tpl );

		return '' !== $placeholders ? strtr( $tpl, $placeholders ) : $tpl;
	}

	/**
	 * Normalizza un template prima della validazione (output AI o codice incollato).
	 */
	public static function normalize_template( string $template ): string {
		$tpl = trim( $template );
		if ( '' === $tpl ) {
			return $tpl;
		}

		$tpl = self::fix_unpaired_dynamic_closing_tags( $tpl );
		$tpl = self::strip_inline_css_blocks( $tpl );
		$tpl = self::strip_inline_js_blocks( $tpl );

		return trim( preg_replace( "/\n{3,}/", "\n\n", $tpl ) );
	}

	/**
	 * Ripara tag dinamici di chiusura senza apertura, es. {{title}} seguito da </{{heading_tag}}>.
	 */
	private static function fix_unpaired_dynamic_closing_tags( string $template ): string {
		if ( ! preg_match_all( '/<\/\{\{([a-z0-9_-]+)\}\}>/i', $template, $matches, PREG_SET_ORDER ) ) {
			return $template;
		}

		foreach ( $matches as $match ) {
			$param     = $match[1];
			$close_tag = $match[0];
			$open_tag  = '<{{' . $param . '}}>';

			if ( str_contains( $template, $open_tag ) ) {
				continue;
			}

			$pattern = '/(\n\s*)((?:\{\{[a-z0-9_-]+\}\}|[^<\n]+?)+)(\s*' . preg_quote( $close_tag, '/' ) . ')/is';
			$template = preg_replace(
				$pattern,
				'$1' . $open_tag . '$2$3',
				$template,
				1
			);
		}

		return $template;
	}

	/**
	 * Rimuove blocchi CSS grezzi non racchiusi in tag style.
	 */
	private static function strip_inline_css_blocks( string $template ): string {
		$template = preg_replace( '/\s+\.[a-z0-9_-][a-z0-9_\s.-]*\{[^}]*\}/is', '', $template );
		$template = preg_replace( '/\s+#[a-z0-9_-][a-z0-9_\s.-]*\{[^}]*\}/is', '', $template );
		$template = preg_replace( '/\s+@media[^{]*\{(?:[^{}]|\{[^{}]*\})*\}/is', '', $template );

		return $template;
	}

	/**
	 * Rimuove IIFE e snippet JS grezzi non racchiusi in tag script.
	 */
	private static function strip_inline_js_blocks( string $template ): string {
		$template = preg_replace( '/\s*\(\s*function\s*\(\s*\)\s*\{.*?\}\s*\)\s*\(\s*\)\s*;?/is', '', $template );
		$template = preg_replace( '/\s*window\.addEventListener\s*\([^;]+\)\s*;?/is', '', $template );

		return $template;
	}

	/**
	 * Valida i tag dinamici di apertura/chiusura nel template.
	 *
	 * @return true|WP_Error
	 */
	private static function validate_dynamic_tags( string $template ) {
		preg_match_all( '/<\{\{([a-z0-9_-]+)\}\}>/i', $template, $opens );
		preg_match_all( '/<\/\{\{([a-z0-9_-]+)\}\}>/i', $template, $closes );

		$open_counts  = array_count_values( $opens[1] ?? array() );
		$close_counts = array_count_values( $closes[1] ?? array() );

		foreach ( $close_counts as $param => $close_count ) {
			$open_count = $open_counts[ $param ] ?? 0;
			if ( $open_count < $close_count ) {
				return new WP_Error(
					'sce_template_malformed',
					sprintf(
						/* translators: %s: dynamic tag param name */
						__( 'Malformed HTML template: closing tag </{{%1$s}}> without opening <{{%1$s}}>. If you paste HTML code, ask the AI to convert it into a valid template with paired dynamic tags.', 'salient-custom-elements' ),
						$param
					)
				);
			}
		}

		foreach ( $open_counts as $param => $open_count ) {
			$close_count = $close_counts[ $param ] ?? 0;
			if ( $open_count > $close_count ) {
				return new WP_Error(
					'sce_template_malformed',
					sprintf(
						/* translators: %s: dynamic tag param name */
						__( 'Malformed HTML template: opening tag <{{%1$s}}> without closing </{{%1$s}}>.', 'salient-custom-elements' ),
						$param
					)
				);
			}
		}

		return true;
	}

	/**
	 * Valida il template HTML (reject per AI, warning in audit).
	 *
	 * @return true|WP_Error
	 */
	public static function validate_template( string $template ) {
		$tpl = trim( $template );
		if ( '' === $tpl ) {
			return new WP_Error( 'sce_empty_template', __( 'The HTML template is required.', 'salient-custom-elements' ) );
		}

		if ( preg_match( '/<script\b/i', $tpl ) || preg_match( '/<style\b/i', $tpl ) ) {
			return new WP_Error( 'sce_template_script_style', __( 'The template cannot contain script or style tags. Use semantic HTML only.', 'salient-custom-elements' ) );
		}

		if ( preg_match( '/\bfunction\s*\(/i', $tpl ) || preg_match( '/window\.addEventListener/i', $tpl ) || preg_match( '/\(\s*function\s*\(\s*\)/i', $tpl ) ) {
			return new WP_Error( 'sce_template_js', __( 'The template cannot contain JavaScript. Move it to the scripts field.', 'salient-custom-elements' ) );
		}

		if ( preg_match( '/\.\s*sce-[a-z0-9_-]+\s*\{/i', $tpl ) || preg_match( '/@media\s*\(/i', $tpl ) ) {
			return new WP_Error( 'sce_template_css', __( 'The template cannot contain raw CSS. Move it to the styles field.', 'salient-custom-elements' ) );
		}

		$dynamic = self::validate_dynamic_tags( $tpl );
		if ( is_wp_error( $dynamic ) ) {
			return $dynamic;
		}

		return true;
	}

	/**
	 * Sanitize CSS/JS asset code from AI or manual edit.
	 */
	public static function sanitize_asset_code( string $code, string $type ): string {
		$code = str_replace( array( "\0", "\r" ), '', $code );
		$code = preg_replace( '/<\/?script\b[^>]*>/i', '', $code );
		$code = preg_replace( '/<\/?style\b[^>]*>/i', '', $code );

		if ( 'css' === $type ) {
			$code = preg_replace( '/@import\b[^;]+;?/i', '', $code );
		}

		return trim( (string) $code );
	}

	/**
	 * @return true|WP_Error
	 */
	public static function validate_styles( string $styles ) {
		if ( '' === trim( $styles ) ) {
			return true;
		}

		$blocked = array(
			'/<script/i',
			'/javascript\s*:/i',
			'/expression\s*\(/i',
			'/-moz-binding/i',
			'/behavior\s*:/i',
			'/@import/i',
		);
		foreach ( $blocked as $pattern ) {
			if ( preg_match( $pattern, $styles ) ) {
				return new WP_Error( 'sce_styles_unsafe', __( 'The styles field contains unsafe or unsupported CSS.', 'salient-custom-elements' ) );
			}
		}

		return true;
	}

	/**
	 * @return true|WP_Error
	 */
	public static function validate_scripts( string $scripts ) {
		if ( '' === trim( $scripts ) ) {
			return true;
		}

		$blocked = array(
			'/<script/i',
			'/\beval\s*\(/i',
			'/\bnew\s+Function\s*\(/i',
			'/(?<![.\w])\bFunction\s*\(/',
			'/document\.write\s*\(/i',
		);
		foreach ( $blocked as $pattern ) {
			if ( preg_match( $pattern, $scripts ) ) {
				return new WP_Error( 'sce_scripts_unsafe', __( 'The scripts field contains unsafe JavaScript.', 'salient-custom-elements' ) );
			}
		}

		return true;
	}

	/**
	 * Merge base responsive CSS with element-specific styles.
	 */
	public static function compiled_css( string $base, string $styles = '' ): string {
		$css = self::responsive_css( $base );
		$styles = trim( $styles );
		if ( '' !== $styles ) {
			$css .= "\n" . $styles;
		}

		return $css;
	}
}
