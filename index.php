<?
define( 'HOME', realpath( __DIR__ ) . '/' );
define( 'SETTINGS', include( HOME . 'settings.php' ) );

require_once __DIR__ . '/downloader.php';

$dl = new Downloader( SETTINGS['destination'] );

$urls = [];

if ( !empty( $argv[1] ) && file_exists( $argv[1] ) ) {
	$urls = $dl->import_urls( $argv[1] );
} else {
	echo "Enter urls one per line:\n";

	do {
		$urls[] = $url = readline();
	} while ( !empty( $url ) );
}

$dl->download( $urls );