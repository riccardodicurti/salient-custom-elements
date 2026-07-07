<?php
/**
 * Uninstall cleanup for Salient Custom Elements.
 *
 * @package SalientCustomElements
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

require_once plugin_dir_path( __FILE__ ) . 'includes/class-sce-element-store.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-sce-code-generator.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-sce-preview.php';

$elements = get_posts(
	array(
		'post_type'      => SCE_Element_Store::POST_TYPE,
		'post_status'    => 'any',
		'posts_per_page' => -1,
		'fields'         => 'ids',
	)
);

foreach ( $elements as $element_id ) {
	$element_id = (int) $element_id;
	$def        = get_post_meta( $element_id, SCE_Element_Store::META, true );
	$definition = is_string( $def ) ? json_decode( $def, true ) : array();

	if ( is_array( $definition ) && ! empty( $definition['base'] ) ) {
		$path = SCE_Code_Generator::path( (string) $definition['base'] );
		if ( is_file( $path ) ) {
			wp_delete_file( $path );
		}
	}

	SCE_Preview::delete( $element_id );
	wp_delete_post( $element_id, true );
}

$upload_dir = wp_upload_dir();
$generated  = trailingslashit( $upload_dir['basedir'] ) . 'sce-generated';

if ( is_dir( $generated ) ) {
	$files = glob( $generated . '/*' );
	if ( is_array( $files ) ) {
		foreach ( $files as $file ) {
			if ( is_file( $file ) && 'index.php' !== basename( $file ) && '.htaccess' !== basename( $file ) ) {
				wp_delete_file( $file );
			}
		}
	}
}

delete_option( 'sce_seeded' );
