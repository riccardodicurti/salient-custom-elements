<?php
/**
 * Controlli di dipendenza: tema Salient + WPBakery Page Builder.
 *
 * @package SalientCustomElements
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SCE_Dependencies {

	/**
	 * Salient è attivo (come tema o come parent di un child theme)?
	 */
	public static function salient_active(): bool {
		// get_template() restituisce lo slug del tema padre; per Salient è 'salient'.
		if ( 'salient' === get_template() ) {
			return true;
		}

		// Fallback difensivo: alcune installazioni rinominano la cartella.
		$theme = wp_get_theme();
		if ( 'salient' === strtolower( (string) $theme->get( 'Template' ) ) ) {
			return true;
		}
		if ( 'salient' === strtolower( (string) $theme->get( 'Name' ) ) ) {
			return true;
		}

		return false;
	}

	/**
	 * WPBakery Page Builder è attivo?
	 */
	public static function wpbakery_active(): bool {
		return defined( 'WPB_VC_VERSION' ) || function_exists( 'vc_map' ) || class_exists( 'WPBMap' );
	}

	/**
	 * Elenco leggibile delle dipendenze mancanti (vuoto se tutto ok).
	 *
	 * @return string[]
	 */
	public static function missing(): array {
		$missing = array();

		if ( ! self::salient_active() ) {
			$missing[] = __( 'Salient theme not active', 'salient-custom-elements' );
		}
		if ( ! self::wpbakery_active() ) {
			$missing[] = __( 'WPBakery Page Builder not active', 'salient-custom-elements' );
		}

		return $missing;
	}
}
