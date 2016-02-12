<?php

class http {

	private static $format = 'json';

	private static function sanitizeTarget($target)
	{
		// convert \ to /
		$target = str_replace('\\','/',$target);

		// Only allow A-Z, 0-9, .-_/
		$target = preg_replace("/[^A-Za-z\.\/0-9_-]/", "", $target);

		// Remove any double periods
		$target = preg_replace("|(^|/)[\.]{1,2}/|g", "/", $target);

		$target = preg_replace("@^/@", "", $target);

		return $target;
	}

	public static function format($format)
	{
		self::$format = $format;
	}

	public static function request()
	{
		$target = $_SERVER["REQUEST_URI"];
		$target = $this->sanitizeTarget($target);
		preg_match('@(?<dirname>.+/)(?<filename>[^/]*)@',$target,$matches);
		$filename = $matches['filename'];
		$dirname  = '/' . $matches['dirname'];
		return [
			'protocol'  => $_SERVER['SERVER_PROTOCOL']?:'HTTP/1.1',
			'method'    => $_SERVER['REQUEST_METHOD'],
			'directory' => $dirname,
			'filename'  => $filename,
			'user'      => $_SERVER['PHP_AUTH_USER'],
			'password'  => $_SERVER['PHP_AUTH_PW']
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
