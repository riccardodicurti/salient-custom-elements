<?php
/**
 * Loader for generated files and production plugin packaging.
 *
 * In dev/staging, loads generated files immediately. For production, installs a
 * standalone mini-plugin with all elements so you can remove this generator.
 *
 * @package SalientCustomElements
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SCE_Loader {

	public const SHIP_SLUG = 'salient-shipped-elements';

	/**
	 * Load all generated files (on plugins_loaded, before vc_before_init).
	 */
	public static function load(): void {
		$dir = SCE_Code_Generator::dir();
		foreach ( (array) glob( $dir . '/*.php' ) as $file ) {
			if ( 'index.php' === basename( (string) $file ) ) {
				continue;
			}
			require_once $file;
		}
	}

	/**
	 * Build the mini-plugin folder (elements + loader) in a directory.
	 *
	 * @param int[]  $ids  Definition IDs.
	 * @param string $dest Plugin destination folder (no trailing slash).
	 * @return int|WP_Error Number of elements written or error.
	 */
	private static function build_plugin_dir( array $ids, string $dest ) {
		if ( ! wp_mkdir_p( $dest . '/elements' ) ) {
			return new WP_Error( 'sce_mkdir', __( 'Unable to create the plugin folder.', 'salient-custom-elements' ) );
		}

		$count = 0;
		foreach ( $ids as $id ) {
			$def = SCE_Element_Store::get( (int) $id );
			if ( null === $def || '' === $def['base'] ) {
				continue;
			}
			$ok = file_put_contents( $dest . '/elements/' . sanitize_file_name( $def['base'] ) . '.php', SCE_Code_Generator::build( $def ) );
			if ( false !== $ok ) {
				++$count;
			}
		}

		if ( 0 === $count ) {
			return new WP_Error( 'sce_export_empty', __( 'No elements to package.', 'salient-custom-elements' ) );
		}

		file_put_contents( $dest . '/' . self::SHIP_SLUG . '.php', self::loader_plugin_header() );
		return $count;
	}

	/**
	 * Install the mini-plugin directly in wp-content/plugins and return the
	 * relative main file path (for activation).
	 *
	 * @param int[] $ids Definition IDs.
	 * @return string|WP_Error Plugin basename (slug/main.php) or error.
	 */
	public static function install_production_plugin( array $ids ) {
		$dest = trailingslashit( WP_PLUGIN_DIR ) . self::SHIP_SLUG;

		$built = self::build_plugin_dir( $ids, $dest );
		if ( is_wp_error( $built ) ) {
			return $built;
		}

		return self::SHIP_SLUG . '/' . self::SHIP_SLUG . '.php';
	}

	/**
	 * Build a zip of the mini-plugin (manual download option).
	 *
	 * @param int[] $ids Definition IDs.
	 * @return string|WP_Error Zip path or error.
	 */
	public static function export_zip( array $ids ) {
		if ( ! class_exists( 'ZipArchive' ) ) {
			return new WP_Error( 'sce_no_zip', __( 'PHP Zip extension not available.', 'salient-custom-elements' ) );
		}

		$tmp_base = trailingslashit( get_temp_dir() ) . self::SHIP_SLUG . '-' . wp_generate_password( 8, false );
		$plugin   = $tmp_base . '/' . self::SHIP_SLUG;

		$built = self::build_plugin_dir( $ids, $plugin );
		if ( is_wp_error( $built ) ) {
			return $built;
		}

		$zip_path = $tmp_base . '.zip';
		$zip      = new ZipArchive();
		if ( true !== $zip->open( $zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE ) ) {
			return new WP_Error( 'sce_zip_open', __( 'Unable to create the zip file.', 'salient-custom-elements' ) );
		}

		$base_len = strlen( $tmp_base ) + 1;
		$it       = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $plugin, FilesystemIterator::SKIP_DOTS ) );
		foreach ( $it as $file ) {
			$zip->addFile( (string) $file, substr( (string) $file, $base_len ) );
		}
		$zip->close();

		return $zip_path;
	}

	/**
	 * Main file of the production mini-plugin.
	 */
	private static function loader_plugin_header(): string {
		$slug = self::SHIP_SLUG;
		return <<<PHP
<?php
/**
 * Plugin Name: Salient Shipped Elements
 * Description: WPBakery/Salient elements packaged for production.
 * Version:     1.0.0
 * Requires PHP: 8.0
 * Author:       Riccardo Di Curti
 * Author URI:   https://riccardodicurti.it
 * License:      GPL v2 or later
 * License URI:  https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: {$slug}
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'plugins_loaded', function () {
	if ( 'salient' !== get_template() || ! function_exists( 'vc_map' ) ) {
		return; // Requires Salient + WPBakery; degrades silently.
	}
	load_plugin_textdomain( '{$slug}', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	foreach ( (array) glob( plugin_dir_path( __FILE__ ) . 'elements/*.php' ) as \$file ) {
		require_once \$file;
	}
} );
PHP;
	}
}
