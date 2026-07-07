<?php
$dir = dirname( __DIR__ );
$strings = array();
$it    = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $dir ) );
foreach ( $it as $f ) {
	if ( ! $f->isFile() || 'php' !== $f->getExtension() ) {
		continue;
	}
	if ( str_contains( $f->getPathname(), '/tools/' ) ) {
		continue;
	}
	$c = file_get_contents( $f->getPathname() );
	if ( preg_match_all( "/(?:__|esc_html__|esc_attr__|_e|esc_html_e)\(\s*([\"'])(?:\\\\.|(?!\\1).)*\\1/s", $c, $m ) ) {
		foreach ( $m[0] as $call ) {
			if ( preg_match( "/(?:__|esc_html__|esc_attr__|_e|esc_html_e)\(\s*([\"'])((?:\\\\.|(?!\\1).)*)\\1/s", $call, $one ) ) {
				$strings[ stripcslashes( $one[2] ) ] = true;
			}
		}
	}
}
ksort( $strings );
foreach ( array_keys( $strings ) as $s ) {
	echo $s, "\n---\n";
}
