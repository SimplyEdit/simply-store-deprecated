<?php
	if (
		($_SERVER['REQUEST_METHOD'] != 'PUT') &&
		($_SERVER['REQUEST_METHOD'] != 'DELETE')
	) {
		header("HTTP/1.1 403 Bad request");
		exit;
	}

	function sanitizeTarget($target) {
		$target = str_replace(dirname($_SERVER['SCRIPT_NAME']), '', $target);
		$target = preg_replace("/^\//", "", $target);
	
		// Only allow A-Z, 0-9, .-_/
		$target = preg_replace("/[^A-Za-z\.\/0-9_-]/", "", $target);

		// Remove any runs of periods
		$target = preg_replace("/([\.]{2,})/", "", $target);

		return $target;
	}

	function checkTarget($target) {
		if (preg_match("/^data\//", $target)) {
			return true;
		}
		if (preg_match("/^img\//", $target)) {
			return true;
		}
		return false;
	}

	$target = $_SERVER["REQUEST_URI"];
	$target = sanitizeTarget($target);

	if (!checkTarget($target)) {
		header("HTTP/1.1 404 Not found");
		exit;
	}

	$fileinfo = explode("/", $target);
	$filename = array_pop($fileinfo);

	switch ($_SERVER['REQUEST_METHOD']) {
		case 'PUT':
			foreach ($fileinfo as $dirname) {
				if (!file_exists($dirname)) {
					mkdir($dirname);
				}
				chdir($dirname);
			}

			if ($filename) {
				/* PUT data comes in on the stdin stream */
				$putdata = fopen("php://input", "r");

				/* Open a file for writing */
				//$temp = tempnam("data/", "puthandler-");
				$fp = fopen($filename, "w");

				/* Read the data 65 KB at a time and write to the file */
				while ($data = fread($putdata, 65535)) {
					fwrite($fp, $data);	
					echo $data;
				}

				/* Close the streams */
				fclose($fp);
				fclose($putdata);
			}
		break;
		case 'DELETE':
			if ($filename) {
				unlink($target);
			} else {
				rmdir($target);
			}
		break;
		default:
			header("HTTP/1.1 403 Method not allowed");
			echo $_SERVER['REQUEST_METHOD'];
		break;
	}
?>