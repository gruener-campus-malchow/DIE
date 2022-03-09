<?php

class API {

	private $db;

	public function __construct()
	{
		$this->db = new DB(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
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

			// gat tags and values as array
			$tags = $this->check_scheme($local_path, $classname::SCHEME);
			// test if class is set to handle the current url
			if ($tags !== false)
			{
				// create instance
				$instance = new $classname();
				// call instance, this method should return true on success, false if it
				// found it is not responsible for handling this request. this can be
				// used if there are multiple possible url schemes that are handled by
				// different classes, i.e. /posts/{post_id}/ and /posts/all/
				if ($instance->call($tags)) return;
			}
		}

		// if no model was able to handle the request, return a 404 error
		http_response_code(404);
		header('Content-Type: text/plain');
		die('404 Not Found');
	}


	// this function checks whether a given url matches a scheme
	// most importantly, this function implements dynamic schemes
	// returns an array containing a key value pair for each {tag}
	// in the scheme on match or false on failure
	private function check_scheme($url, $scheme)
	{
		// the array containing all tags as key-value pairs
		$tags = [];
		// encode braces in url to avoid infinite loops
		$url = str_replace('{', '%7B', $url);
		$url = str_replace('}', '%7D', $url);
		// keep looping until no tags are left
		while (true)
		{
			// get index of the first { character in scheme to find the first tag
			$start_index = strpos($scheme, '{');
			// if no tags are left break out of the loop
			if ($start_index === false) break;

			// find first occurence of } in scheme
			$end_index = strpos($scheme, '}');
			// get the key between the start and the end index
			$key = substr($scheme, $start_index + 1, $end_index - $start_index - 1);

			// if url ends before tag return false
			if ($start_index > strlen($url)) return false;
			// find the corresponding value in the given url, which is indicated by
			// the character following the tag
			$value_end = strpos($url, $scheme[$end_index + 1], $start_index);
			// get the value from the url corresponding to the key from the scheme
			$value = substr($url, $start_index, $value_end - $start_index);

			// we don't want slashes in tag values since this can potentially break things
			// /posts/20/22/hello-world.html would match /posts/{year}/{post_name}.html
			// and $tags would be ['year' => '20', 'post_name' => '22/hello-world']
			// which is probably not the expected behavoir
			if (strpos($value, '/') !== false) return false;

			// if $value is a numberic string, convert it to a number
			if (is_numeric($value)) $value = $value + 0;

			// add to array
			$tags[$key] = $value;

			// replace tag in scheme with value
			$scheme = substr($scheme, 0, $start_index) . $value . substr($scheme, $end_index + 1);
		}

		// if url and scheme don't match, return false
		// since all tags are replaced with values from the url, these should be identical
		if ($url != $scheme) return false;

		// return tags
		return $tags;
	}

}