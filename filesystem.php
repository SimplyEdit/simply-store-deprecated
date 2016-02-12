<?php

class fsException extends \Exception {}

class filesystem {

	/*
	TODO;
	- get hash of file
	- conditional put - only if hashes match
	x save a log? with jsondiff?
	*/

	private static $allowed = [];
	private static $checks  = [];
	private static $basedir = __DIR__;

	public static function basedir($basedir)
	{
		self::$basedir = $basedir;
	}

	public static function allow($dirname, $mimetype)
	{
		self::$allowed[$dirname][] = $mimetype;
	}

	private static function realpaths($dirname, $filename)
	{
		$realfile = realpath(self::$basedir.$dirname.$filename);
		$realdir  = realpath(self::$basedir.$dirname);
		if ( strpos($realfile, self::$basedir)!==0 
			|| strpos($realdir, self::$basedir)!==0 ) {
			throw new fsException('Attempted file access outside base directory', 110);
		}
		return [ $realdir, $realfile ];
	}

	public static function put($dirname, $filename=null, $hash=null)
	{
		list($realdir, $realfile)=self::realpaths($dirname, $filename);
		if (!file_exists($realdir)) {
			// suppress errors from mkdir, it is noisy
			$res = @mkdir($realdir, true);
			if ($res == false) {
				self::dirNotWritable($dirname);
			}
		}

		if ( !is_writable($realdir) ){
			self::dirNotWritable($dirname);
		} else if ( $filename ) {
			$exists = file_exists($realfile);
			if (
				($exists === true && is_writable($realfile) ) ||
				$exists === false
			){
				return self::passthru($dirname, $filename, $hash);
			} else {
				self::fileNotWritable($dirname.$filename);
			}
		}
		return true;
	}

	public static function delete($dirname, $filename=null)
	{
		list($realdir, $realfile)=self::realpaths($dirname, $filename);
		self::runChecks('delete', $dirname.$filename, $realfile);
		if ( file_exists($realfile ) ) {
			if ( $filename ) {
				unlink($realfile);
			} else {
				rmdir($realfile);
			}
		} else {
			throw new sfException('File not found '.$dirname.$filename, 105);
		}
	}

	public static function check($method, $filename, $callback)
	{
		self::$checks[$method][$filename][] = $callback;
	}

	private static function isAllowed($dirname, $filename, $tempfile)
	{
		$allowed = false;
		foreach ( self::$allowed as $path => $mimetypes ) {
			if ( strpos($dirname, $path)===0 ) {
				$allowed = true;
				break;
			}
		}
		if ( !$allowed ) {
			throw new fsException('Access denied for '.$dirname.$filename, 106);
		}
		$mimetype = finfo::file( $tempfile, FILEINFO_MIME_TYPE );
		$mimetypeRe = '{'.implode($mimetypes, '|').'}i';
		if ( !preg_match($mimetypeRe, $mimetype) ) {
			throw new fsException('Files with mimetype '.$mimetype.' are not allowed in '.$dirname, 108);
		}
		return true;
	}

	private static function runChecks($method, $filename, $tempfile)
	{
		if ( !isset(self::$checks[$method]) ) {
			return;
		}
		foreach ( self::$checks[$method] as $path => $callback ) {
			if ( strpos($filename, $path)===0 ) {
				$callback($filename, $tempfile);
			}
		}
	}

	private static function dirNotWritable($dirname)
	{
		// FIXME: try to find out why it is not writable
		// check if dir exists
		// check if dir is readable
		// check if dirname is a directory
		// check permissions on dirname
		// check owner and current user
		throw new fsException('Directory '.$dirname.' is not writable', 102);
	}
	
	private static function fileNotWritable($file)
	{
		// FIXME: try to find out why it is not writable
		throw new fsException('File '.$file.' is not writeable', 103);
	}

	private static function renameFailed($file, $tempfile)
	{
		// FIXME: try to find out why the rename failed
		unlink($tempfile);
		throw new fsException('Could not move file contents to '.$file, 104);
	}

	private static function passthru($dirname, $filename, $hash=null)
	{
		$lock = self::lock($dirname, $filename);
		if ( !$lock ) {
			throw new fsException('Could not lock '.$dirname.$filename.' for writing', 109);
		}
		/* PUT data comes in on the stdin stream */
		$in       = fopen("php://input", "r");

		/* Open a file for writing */
		$tempfile = tempnam($dirname, 'put-XXXXXX');

		$out      = fopen($tempfile, "w");
		$res      = stream_copy_to_stream($in,$out);

		/* Close the streams */
		fclose($out);
		fclose($in);

		$exception = false;
		try {
			if ($res) {
				if ( !self::isAllowed($dirname, $filename, $tempfile) ) {
					throw new fsException('Access denied for '.$dirname.$filename, 106);
				}
				self::runChecks('put', $dirname.$filename, $tempfile);
				$res = rename($tempfile, $dirname.$filename);
				if ($res == false) {
					self::renameFailed($dirname.$filename, $tempfile);
				}
			} else {
			}
		} catch( \Exception $e ) {
			$exception = $e;
		} finally {
			self::unlock($lock);
			unlink($tempfile);			
		}
		if ( $exception ) {
			throw $exception;
		}
		return true;
	}

	private static function lock($dirname, $filename)
	{
		$fp = fopen($dirname.$filename.'.lock', 'w');
		if ( $fp && flock($fp, LOCK_EX ) ) {
			return [
				'resource' => $fp,
				'dirname'  => $dirname,
				'filename' => $filename
			];
		}
		return false;
	}

	private static function unlock($lock)
	{
		flock($lock['resource'], LOCK_UN);
		fclose($lock['resource']);
		unlink($lock['dirname'].$lock['filename'].'.lock');
	}
}