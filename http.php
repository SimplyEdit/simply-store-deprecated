<?php

class http {

	private static $format = 'json';

	private static function sanitizeTarget($target)
	{
		// convert \ to /
		$target = str_replace('\\','/',$target);

		// Only allow A-Z, 0-9, .-_/
		$target = preg_replace('/[^A-Za-z\.\/0-9_-]/', '', $target);

		// Remove any double periods
		$target = preg_replace('{(^|\/)[\.]{1,2}\/}', '/', $target);

		$target = preg_replace('@^/@', '', $target);

		return $target;
	}

	public static function format($format)
	{
		self::$format = $format;
	}

	public static function request()
	{
		$target = $_SERVER["REQUEST_URI"];
		$target = self::sanitizeTarget($target);

		preg_match('@(?<dirname>.+/)?(?<filename>[^/]*)@',$target,$matches);

		$filename = isset($matches['filename']) ? $matches['filename'] : '';
		$dirname  = '/' . ( isset($matches['dirname']) ? $matches['dirname'] : '');
		$docroot  = $_SERVER['DOCUMENT_ROOT'];
		$subdir   = '/' . substr( dirname($_SERVER['SCRIPT_FILENAME']), strlen($docroot) );
		$dirname  = substr($dirname, strlen($subdir));
		return [
			'protocol'  => $_SERVER['SERVER_PROTOCOL']?:'HTTP/1.1',
			'method'    => $_SERVER['REQUEST_METHOD'],
			'target'    => '/'.$target,
			'directory' => $dirname,
			'filename'  => $filename,
			'user'      => isset($_SERVER['REMOTE_USER']) ? $_SERVER['REMOTE_USER'] : (
							isset($_SERVER['REDIRECT_REMOTE_USER']) ? $_SERVER['REDIRECT_REMOTE_USER'] :
							''),
			'docroot'   => $docroot
		];
	}

	public static function response($status, $data='')
	{
		http_response_code($status);
		switch(self::$format) {
			case 'html':
				echo $data;
			break;
			case 'json':
			default:
				echo json_encode($data, JSON_UNESCAPED_UNICODE);
			break;
		}
	}

}
