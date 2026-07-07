<?php
/**
 * Rigenera un file PHP elemento da riga di comando (dev).
 *
 * Usage: php tools/regenerate-element.php sce_gsap_latest_posts
 */

if ( php_sapi_name() !== 'cli' ) {
	exit( 1 );
}

$base = $argv[1] ?? '';
if ( '' === $base ) {
	fwrite( STDERR, "Usage: php tools/regenerate-element.php <base>\n" );
	exit( 1 );
}

$wp_load = dirname( __DIR__, 4 ) . '/wp-load.php';
if ( ! is_file( $wp_load ) ) {
	fwrite( STDERR, "wp-load.php not found at {$wp_load}\n" );
	exit( 1 );
}

require $wp_load;

if ( ! class_exists( 'SCE_Element_Store' ) || ! class_exists( 'SCE_Code_Generator' ) ) {
	fwrite( STDERR, "Salient Custom Elements plugin not loaded.\n" );
	exit( 1 );
}

$definition = null;
foreach ( SCE_Element_Store::all() as $def ) {
	if ( ( $def['base'] ?? '' ) === $base ) {
		$definition = $def;
		break;
	}
}

if ( null === $definition ) {
	fwrite( STDERR, "Element not found: {$base}\n" );
	exit( 1 );
}

$result = SCE_Code_Generator::write( $definition );
if ( is_wp_error( $result ) ) {
	fwrite( STDERR, $result->get_error_message() . "\n" );
	exit( 1 );
}

echo "Regenerated: {$result}\n";
