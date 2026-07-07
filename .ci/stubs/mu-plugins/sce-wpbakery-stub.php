<?php
/**
 * Plugin Name: SCE CI WPBakery Stub
 * Description: Minimal WPBakery stubs so Salient Custom Elements can activate in CI.
 *
 * @package SalientCustomElements
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'vc_map' ) ) {
	/**
	 * @param array<string,mixed>|string $settings Map settings.
	 */
	function vc_map( $settings = array() ): void {}
}

if ( ! function_exists( 'vc_shortcode_custom' ) ) {
	/**
	 * @param string               $tag  Shortcode tag.
	 * @param callable|string|null $func Callback.
	 */
	function vc_shortcode_custom( string $tag, $func = null ): void {}
}

if ( ! class_exists( 'WPBMap' ) ) {
	final class WPBMap {
		/**
		 * @return array<string,mixed>
		 */
		public static function getShortCodes(): array {
			return array();
		}
	}
}
