<?php
	$datafile = "data/data.json";
	$templateContainer = '../';
	$request = null;
	$data = null;

	if ( isset($_SERVER['REQUEST_URI'] ) ){
		$request = $_SERVER['REQUEST_URI'];
	}

	if ( file_exists($datafile) ) {
		$json = file_get_contents($datafile);
		$data = json_decode($json, true);
	}

	if( isset($request) && isset($data[$request]) ) {
		$template = "index.html";
		// FIXME: make sure pageTemplate is not some ../../../etc/passwd\0.html

		if( isset($data[$request]['data-simply-page-template']['content'])) {
			$pageTemplate = $data[$request]['data-simply-page-template']['content'];
			if (preg_match("/\.html$/", $pageTemplate) && file_exists($templateContainer . $pageTemplate)) {
				$template = $pageTemplate;
			}
		}

		header("HTTP/1.1 200 OK");
		readfile($templateContainer . $template);
	} else {
		header("HTTP/1.1 404 Not Found");
		echo "<html><head><title>404 Not Found</title></head><body><h1>Page not found (error: 404)</h1></body></hhtml>";
	}
