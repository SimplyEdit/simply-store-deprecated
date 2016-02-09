<?php

	$basePath = __DIR__;
	$protocol = $_SERVER['SERVER_PROTOCOL']?:'HTTP/1.1';

	if (
		(isset($_SERVER['REQUEST_METHOD'])) &&
		($_SERVER['REQUEST_METHOD'] != 'PUT') &&
		($_SERVER['REQUEST_METHOD'] != 'DELETE')
	) {
		header($protocol ." 405 Method not allowed");
		exit;
	}

	function sanitizeTarget( $target) {
		$target = preg_replace("@^/@", "", $target);

		// Only allow A-Z, 0-9, .-_/
		$target = preg_replace("/[^A-Za-z\.\/0-9_-]/", "", $target);

		// Remove any runs of periods
		$target = preg_replace("/([\.]{2,})/", "", $target);

		return $target;
	}

	function checkTarget($target) {
		return preg_match("@^(img|data)/@", $target);
	}

	$target = $_SERVER["REQUEST_URI"];
	$target = sanitizeTarget($target);

	if (!checkTarget($target)) {
		header($protocol ." 403 Forbidden");
		exit;
	}

	preg_match('@(?<dirname>.+/)(?<filename>[^/]*)@',$target,$matches);
	$filename = $matches['filename'];
	$dirname  = $basePath . '/' . $matches['dirname'];

	switch ($_SERVER['REQUEST_METHOD']) {
		case 'PUT':
			if (!file_exists($dirname)) {
				// suppress errors from mkdir, it is noisy
				$res = @mkdir($dirname, true);
				if ($res == false) {
					header($protocol . " 412 Precondition Failed");
					echo "Directory not writeable\n";
					exit();
				}
			}

			if ( !is_writable($dirname) ){
				header($protocol . " 412 Precondition Failed");
				echo "Directory not writeable\n";
				exit();
			} else {
				if ( $filename ) {
					$exists = file_exists($dirname.$filename);
					if (
						($exists === true && is_writable($dirname.$filename) ) ||
						$exists === false
					){
						/* PUT data comes in on the stdin stream */
						$in = fopen("php://input", "r");

						/* Open a file for writing */
						$tempfile = tempnam($dirname, 'put-XXXXXX');

						$out = fopen($tempfile, "w");
						$res = stream_copy_to_stream($in,$out);

						/* Close the streams */
						fclose($out);
						fclose($in);

						if($res) {
							$res = rename($tempfile, $dirname.$filename);
							if ($res == false) {
								header($protocol . " 412 Precondition Failed");
								unlink($tempfile);
							}
						} else {
							unlink($tempfile);
						}
					} else {
						header($protocol . " 412 Precondition Failed");
						echo "Target file not writeable\n";
					}
				}
			}
			break;
		case 'DELETE':
			$target = $dirname . $filename;
			if ( file_exists($target ) ) {
				if ( $filename ) {
					unlink($target);
				} else {
					rmdir($target);
				}
			} else {
				header($protocol . " 404 Not Found");
			}
			break;
		default:
			header($protocol ." 405 Method not allowed");
			echo $_SERVER['REQUEST_METHOD'];
			break;
	}
