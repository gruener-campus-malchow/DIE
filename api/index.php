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




require_once 'lib/DB.php';
require_once 'lib/API.php';
require_once 'lib/Model.php';


$api = new API();
$api->autoload('models');
