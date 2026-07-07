<?php
/**
 * Plugin Name:       Salient Custom Elements
 * Plugin URI:        https://github.com/riccardodicurti/salient-custom-elements
 * Description:       Generate native WPBakery elements integrated with the Salient theme. Create with AI or manually, preview instantly, and ship a production plugin.
 * Version:           0.2.0
 * Requires at least: 6.9
 * Requires PHP:      8.0
 * Author:            Riccardo Di Curti
 * Author URI:        https://riccardodicurti.it
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       salient-custom-elements
 * Domain Path:       /languages
 *
 * This plugin requires the commercial Salient theme and WPBakery Page Builder.
 * It does not include or redistribute them; it detects their presence and
 * degrades gracefully when they are missing.
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SCE_VERSION', '0.2.0' );
define( 'SCE_FILE', __FILE__ );
define( 'SCE_PATH', plugin_dir_path( __FILE__ ) );
define( 'SCE_URL', plugin_dir_url( __FILE__ ) );
define( 'SCE_DONATE_URL', 'https://liberapay.com/riccardodicurti/donate' );
define( 'SCE_AUTHOR_URL', 'https://riccardodicurti.it' );

require_once SCE_PATH . 'includes/class-sce-dependencies.php';

/**
 * Block activation when Salient or WPBakery is missing.
 */
register_activation_hook(
	__FILE__,
	static function (): void {
		$missing = SCE_Dependencies::missing();

		if ( ! empty( $missing ) ) {
			deactivate_plugins( plugin_basename( __FILE__ ) );

			$message  = '<h1>' . esc_html__( 'Unable to activate Salient Custom Elements', 'salient-custom-elements' ) . '</h1>';
			$message .= '<p>' . esc_html__( 'One or more required dependencies are missing:', 'salient-custom-elements' ) . '</p>';
			$message .= '<ul style="list-style:disc;margin-left:20px;">';
			foreach ( $missing as $dep ) {
				$message .= '<li>' . esc_html( $dep ) . '</li>';
			}
			$message .= '</ul>';
			$message .= '<p><a href="' . esc_url( admin_url( 'plugins.php' ) ) . '">' . esc_html__( 'Back to plugins', 'salient-custom-elements' ) . '</a></p>';

			wp_die(
				wp_kses_post( $message ),
				esc_html__( 'Missing dependencies', 'salient-custom-elements' ),
				array( 'back_link' => true )
			);
		}
	}
);

/**
 * Runtime bootstrap. Degrades with an admin notice if dependencies disappear.
 */
add_action(
	'plugins_loaded',
	static function (): void {
		$missing = SCE_Dependencies::missing();

		if ( ! empty( $missing ) ) {
			add_action(
				'admin_notices',
				static function () use ( $missing ): void {
					if ( ! current_user_can( 'activate_plugins' ) ) {
						return;
					}
					echo '<div class="notice notice-error"><p><strong>' . esc_html__( 'Salient Custom Elements', 'salient-custom-elements' ) . '</strong>: ';
					echo esc_html__( 'missing dependencies, functionality disabled.', 'salient-custom-elements' );
					echo ' ' . esc_html( implode( ', ', $missing ) );
					echo '</p></div>';
				}
			);
			return;
		}

		require_once SCE_PATH . 'includes/class-sce-salient.php';
		require_once SCE_PATH . 'includes/class-sce-reference.php';
		require_once SCE_PATH . 'includes/class-sce-element-store.php';
		require_once SCE_PATH . 'includes/class-sce-rules.php';
		require_once SCE_PATH . 'includes/class-sce-code-generator.php';
		require_once SCE_PATH . 'includes/class-sce-loader.php';
		require_once SCE_PATH . 'includes/class-sce-ai-generator.php';
		require_once SCE_PATH . 'includes/class-sce-generator.php';
		require_once SCE_PATH . 'includes/class-sce-preview.php';
		require_once SCE_PATH . 'includes/class-sce-rest.php';
		require_once SCE_PATH . 'includes/class-sce-admin.php';
		require_once SCE_PATH . 'includes/class-sce-wiki.php';
		require_once SCE_PATH . 'includes/class-sce-plugin.php';

		SCE_Plugin::instance()->boot();
	}
);
