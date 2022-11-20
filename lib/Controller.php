<?php

class Controller
{

	private $db;

	public function __construct()
	{
		$this->db = new DB(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
	}

	// converts CamelCase to snake_case
	private function cc2sc($cc, $d = '_')
	{
		$sc = '';
		foreach (str_split($cc) as $i => $l)
		{
			if (ctype_upper($l) && $i > 0) $sc .= $d;
			$sc .= strtolower($l);
		}
		return $sc;
	}

	// converts snake_case to CamelCase
	private function sc2cc($sc, $d = '_')
	{
		$cc = '';
		foreach (str_split($sc) as $i => $l)
		{
			if ($l == $d) continue;
			if ($i == 0 || $sc[$i - 1] == $d) $l = strtoupper($l);
			$cc .= $l;
		}
		return $cc;
	}


	public function autoload($dir)
	{
		// request url starting after the BASE_PATH, starting with a / character
		// /api/entry/ -> /entry/ if BASE_PATH is /api/
		$local_path = substr($_SERVER['REQUEST_URI'], strlen(BASE_PATH) - 1);

		// remove query string (url get parameters) if it exists
		// find index of the first ? character
		$query_index = strpos($local_path, '?');
		// if it was found, remove all characters starting at that index
		if ($query_index !== false) $local_path = substr($local_path, 0, $query_index);


		// array containing all file (and directory) names from the request url
		$url_parts = array_values(array_filter(explode('/', $local_path)));
		// extract resource, identifier and attribute from url; resource defaults to `root` (/)
		$resource = isset($url_parts[0]) ? $url_parts[0] : 'root';
		$identifier = isset($url_parts[1]) ? $url_parts[1] : null;
		$attribute = isset($url_parts[2]) ? $url_parts[2] : null;
		if (isset($url_parts[3])) $this->error(400, 'Too Many Parameters');

		// enforce REST-style URLs (a / at the end of a resource name indicates the resource is a list, not an object)
		if (!isset($identifier) && substr($local_path, -1) != '/') $this->error(400, 'Malformed URL: Accessing List As Object');
		if (isset($identifier) && substr($local_path, -1) == '/') $this->error(400, 'Malformed URL: Accessing Object As List');

		// the class name is derived from the first subdirectory
		$classname = $this->sc2cc($resource, '-');
		// path to file relative to index.php, the class contained in the file
		// should have the same name as the file
		$path = "$dir/$classname.php";

		if (file_exists($path))
		{
			// include the file
			require_once $path;

			// check if class with same name as the file exists
			if (class_exists($classname))
			{
				// create instance
				$instance = new $classname($this->db);
				// make api call
				return $instance->call($identifier, $attribute);
			}
			else
			{
				// if we are in a dev environment, alert the user
				if (ENV === 'DEV') echo "ERROR: Class $classname not found (in $path)\n";
			}
		}

		// if no model was able to handle the request, return a 404 error
		$this->error(404, '404 Not Found');
	}

	// returns an http error
	private function error($code, $message)
	{
		http_response_code($code);
		header('Content-Type: text/plain');
		die($message);
	}

}
