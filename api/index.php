<?php

require_once 'config/config.php';

if (!defined('ENV')) define('ENV', 'PROD');
if (ENV == 'DEV') error_reporting(E_ALL);
else error_reporting(0);

if (!config_defined('BASE_PATH')) die('base path configuration missing');
if (substr(BASE_PATH, 0, 1) != '/' || substr(BASE_PATH, -1) != '/') die('base path misconfigured: make sure first and last character are a /');

if (!config_defined('DB_NAME', 'DB_USER', 'DB_PASSWORD', 'DB_HOST')) die('database configuration missing');


function config_defined()
{
	$keys = func_get_args();
	foreach ($keys as $key)
	{
		if (!defined($key))
		{
			return false;
		}
	}

	return true;
}




require_once 'lib/Model.php';
require_once 'lib/API.php';


(function () {
	// request url starting after the BASE_PATH, starting with a / character
	// /api/entry/ -> /entry/ if BASE_PATH is /api/
	$local_path = substr($_SERVER['REQUEST_URI'], strlen(BASE_PATH) - 1);

	$dir = 'models';

	// iterate over all files in dir
	foreach (scandir($dir) as $file)
	{
		// path to file relative to index.php
		$path = "$dir/$file";
		// split file name into parts, see php manual entry for pathinfo()
		$parts = pathinfo($path);
		// filter out all directories and non-php files
		if (is_dir($path) || $parts['extension'] !== 'php') continue;
		// the class contained in the file should have the same name as the file
		$classname = $parts['filename'];

		// include the file
		require_once $path;

		// check if class with same name as the file exists
		if (!class_exists($classname))
		{
			// if we are in a dev environment, alert the user
			if (ENV === 'DEV') echo "ERROR: Class $classname not found (in $path)\n";
			// skip this file
			continue;
		}

		// test if class is set to handle the current url
		if (check_scheme($local_path, $classname::SCHEME))
		{
			// create instance
			$instance = new $classname();
			// call instance, this method should return true on success, false if it
			// found it is not responsible for handling this request. this can be
			// used if there are multiple possible url schemes that are handled by
			// different classes, i.e. /posts/{post-id}/ and /posts/all/
			if ($instance->call()) return;
		}
	}

	http_response_code(404);
	header('Content-Type: text/plain');
	die('404 Not Found');
})();


// this function checks whether a given url matches a scheme
// most importantly, this function implements dynamic schemes
function check_scheme($url, $scheme)
{
	// TODO: implement dynamic schemes
	return $url == $scheme;
}
