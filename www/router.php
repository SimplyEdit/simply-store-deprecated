<?php
	require_once(__DIR__.'/../http.php');
	require_once(__DIR__.'/../filesystem.php');

	filesystem::basedir(__DIR__);
	http::format('html');

	$datafile    = __DIR__.'/data/data.json';
	$templateDir = __DIR__.'/templates/';
	$request     = null;
	
	$request     = http::request();

	$data        = json_decode(filesystem::get($request['dirname'], $request['filename']));

	$path        = $request['dirname'].$request['filename'];
	$status      = 200;

	if( !isset($data[$path]) ) {
		$path   = '/404.html';
		$status = 404;
	}

	if ( isset($data[$path]) ) {
		$template = "index.html";

		if( isset($data[$path]['data-simply-page-template']['content'])) {
			$pageTemplate = $data[$path]['data-simply-page-template']['content'];
			if (preg_match("/\.html$/", $pageTemplate) && file_exists($templateDir . $pageTemplate)) {
				$template = $pageTemplate;
			}
		}

		http::response($status);
		filesystem::readfile($templateDir, $template);

	} else {
		http::response(404);
		echo '
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<title>404 Not Found</title>
</head>
<body>
	<h1>Page not found (error: 404)</h1>
</body>
</html>
';
	}
