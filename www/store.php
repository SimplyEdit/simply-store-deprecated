<?php
	require_once(__DIR__.'/../http.php');
	require_once(__DIR__.'/../filesystem.php');

	filesystem::basedir(__DIR__);

	filesystem::allow('/data/','application/json');
	filesystem::allow('/data/','text/javascript');
	filesystem::allow('/data/','text/x-json');

	filesystem::allow('/img/','image/*');

	filesystem::check('put', '/data/', function($filename, $realfile) {
		$contents = file_get_contents($realfile);
		$result   = json_decode($contents);
		if ( $result === null ) {
			throw new \Exception('File does not contain valid JSON',1);
		}
		return true;
	});

	filesystem::check('delete', '/data/data.json', function() {
		throw new \Exception('You cannot delete the data.json file',3);
	});

	filesystem::check('put', '/', function($filename, $realfile) {
		$disallowed = ['php','phtml','inc','phar','cgi'];
		$extension  = pathinfo($filename, PATHINFO_EXTENSION);
		if ( in_array($extension, $disallowed) ) {
			throw new \Exception('Extension '.$extension.' is disallowed', 2);
		}
	});

	$statusCodes = [
		1   => 412,
		2   => 403,
		3   => 403,
		102 => 412,	// precondition failed
		103 => 412,
		104 => 412,
		105 => 404,	// not found
		106 => 403, // access denied
		107 => 403,
		108 => 403,
		109 => 412,
		110 => 403
	];

	$request = http::request();

	$result = [];
	$status = 200;

	try {
		if ( $request['method']=='PUT') {
			$result = filesystem::put($request['directory'], $request['filename']);
		} else if ( $request['method']=='DELETE' ) {
			$result = filesystem::delete($request['directory'], $request['filename']);
		} else {
			$status = 405; //Method not allowed
		}
	} catch( \Exception $e ) {
		$code = $e->getCode();
		if ( isset($statusCodes[$code]) ) {
			$status = $statusCode[$code];
		} else {
			$status = 500; // internal error
		}
		$result = [ 'error' => $code, 'message' => $e->getMessage() ];
	}

	http::response( $status, $result );
