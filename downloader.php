<?
class Downloader {
	private $dir = '';
	private $url = '';
	private $size = 0;
	private $bytes = 0;
	private $progress = 0;
	private $ctx = null;

	function __construct( $dir ) {
		if ( !is_dir( $dir ) ) $dir = __DIR__;

		$this->dir = $dir;

		if ( !is_dir( $this->dir ) ) mkdir( $this->dir, 0777, true );
	}

	function import_urls( $file ) {
		$pathinfo = pathinfo( $file );

		switch ( $pathinfo['extension'] ) {
			case 'json':
				$contents = file_get_contents( $file );
				return json_decode( $contents, true );
				break;
			case 'php':
				return include( $file );
				break;
			case 'txt':
				$contents = file_get_contents( $file );
				return explode( "\n", $contents );
				break;
			default:
				$contents = file_get_contents( $file );
				return explode( "\n", $contents );
				break;
		}
	}

	function download( $url ) {
		if ( is_array( $url ) ) {
			foreach ( $url as $_url ) {
				$this->download( $_url );
			}
		} else {
			$url = trim( $url );

			if ( !filter_var( $url, FILTER_VALIDATE_URL ) ) return;

			$this->_reset();
			$this->_register_ctx();

			$this->url = $url;

			$filename = $this->_get_filename();
			
			$this->_print("{$this->url} [?.?? MB]: ");

			$contents = file_get_contents( $url, false, $this->ctx );

			$this->_print("{$this->url} [{$this->size}]: 100%");

			echo "\n";

			file_put_contents( $filename, $contents );
		}
	}

	/* private */

	private function _reset() {
		$this->url = '';
		$this->size = 0;
		$this->bytes = 0;
		$this->progress = 0;
	}

	private function _register_ctx() {
		if ( !empty( $this->ctx ) ) return;

		$this->ctx = stream_context_create();

		stream_context_set_params( $this->ctx, [
			'notification' => [ $this, '_stream_notification_callback' ],
		]);
	}

	private function _get_filename() {
		$pathinfo = pathinfo( $this->url );
		$name = $pathinfo['filename'];
		$ext = $pathinfo['extension'];
		$path = $this->dir . DIRECTORY_SEPARATOR;
		$filename = "{$path}{$name}.{$ext}";
		$counter = 1;

		while ( file_exists( $filename ) ) {
			$filename = "{$path}{$name} ({$counter}).{$ext}";
			$counter++;
		}

		return $filename;
	}

	private function _stream_notification_callback(
			$notification_code,
			$severity,
			$message,
			$message_code,
			$bytes_transferred,
			$bytes_max
		) {
		switch( $notification_code ) {
			case STREAM_NOTIFY_RESOLVE:
			case STREAM_NOTIFY_AUTH_REQUIRED:
			case STREAM_NOTIFY_COMPLETED:
			case STREAM_NOTIFY_FAILURE:
			case STREAM_NOTIFY_AUTH_RESULT:
				/* Ignore */
				break;
			case STREAM_NOTIFY_REDIRECTED:
			case STREAM_NOTIFY_CONNECT:
			case STREAM_NOTIFY_MIME_TYPE_IS:
				/* Ignore */
				break;
			case STREAM_NOTIFY_FILE_SIZE_IS:
				$this->bytes = $bytes_max;
				$this->size = $this->_convert_filesize( $this->bytes );
				$this->_print("{$this->url} [{$this->size}]: 0%");
				break;
			case STREAM_NOTIFY_PROGRESS:
				if ( !empty( $this->bytes ) ) {
					$progress = round( 100 * $bytes_transferred / $this->bytes ) . '%';
				} else if ( !empty( $bytes_max ) ) {
					$progress = round( 100 * $bytes_transferred / $bytes_max ) . '%';
				} else {
					$progress = $this->_convert_filesize( $bytes_transferred );
				}

				if ( $progress > $this->progress ) {
					$this->progress = $progress;
					$this->_print("{$this->url} [{$this->size}]: {$progress}");
				}
				break;
		}
	}

	private function _print( string $str ) {
		if ( empty( $str ) ) return;

		$len = strlen( $str );

		if ( $len > 77 ) {
			$str = '...' . substr( $str, -77, 77 );
			$len = strlen( $str );
		}

		echo "\r{$str}";
	}

	private function _convert_filesize( $bytes, $decimals=2 ) {
		$size = [ 'B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB' ];
		$factor = floor( ( strlen( $bytes ) - 1 ) / 3 );
		$n = sprintf( "%.{$decimals}f", $bytes / pow( 1024, $factor ) );

		return $n . ' ' . $size[ $factor ];
	}

}