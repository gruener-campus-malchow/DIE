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
				//return $instance->call();

				switch ($_SERVER['REQUEST_METHOD'])
				{
					case 'GET':
						// GET /items/ [returns list with all items]
						// this can be filtered using GET URL parameters,
						// such as GET /items/?color=red [returns list with all red items]
						if (!isset($identifier)) return $instance->getAll($_SERVER['QUERY_STRING']);
						// GET /items/42 [returns item #42]
						if (!isset($attribute)) return $instance->getSingle($identifier);
						// GET /items/42/color [returns the color of item #42]
						return $instance->getAttribute($identifier, $attribute);
					case 'POST':
						// all post requests include a request body with some JSON data
						$body = json_decode(file_get_contents('php://input'), true);
						// POST /items/ [creates new item from request body data, returns id of the new element]
						// for AUTO_INCREMENT only, if your model doesn't use A_I, use PUT instead
						if (!isset($identifier)) return $instance->createSingle($body);
						// POST /items/42 [updates (not replaces) the trageted item #42 with data from the request body,
						// keeps old values if they aren't mentioned in the request]
						if (!isset($attribute)) return $instance->updateSingle($identifier, $body);
						// we don't allow using POST to set individual attributes,
						// use POST /items/42 and wrap the atribute in a JSON object instead
						return $this->error(400, 'Too Many Arguments');
					case 'PUT':
						// like POST, all PUT requests include a request body
						$body = json_decode(file_get_contents('php://input'), true);
						// PUT /items/42 [replaces (not updates) the targeted item #42 with the request body,
						// values not included in the body default to whatever the default attribute value is (null, empty string, etc)]
						if (isset($identifier) && !isset($attribute)) return $instance->replaceSingle($identifier, $body);
						// we don't allow setting individual attributes (use POST instead),
						// or replacing entire lists (if you really need this, you'll have to implement it yourself)
						return $this->error(400, 'Bad Request');
					case 'DELETE':
						// DELETE /items/42 [deletes item #42]
						if (isset($identifier) && !isset($attribute)) return $instance->deleteSingle($identifier);
						// we don't allow deleting of attributes (not really possible, use POST to reset them),
						// or deleting the entire list (obviously a bad idea, again, if you absolutely can't live without this, implement it yourself)
						return $this->error(400, 'Unable To Delete Target Resource');
					default:
						// other request methods are not available right now
						// PATCH is implemented by POST, since that seems to be the general convention,
						// HEAD and OPTIONS might be implemented later on, but those are only nice to have features (i know REST says otherwise)
						return $this->error(405, 'Request Method Is Not Implemented By The Target Resource');
				}

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
