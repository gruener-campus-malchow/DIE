<?php

abstract class Model
{
	// reference to the DB instance, since we want to avoid static classes/methods
	protected $db;
	// boilerplate model config
	protected $name, $id, $searchable, $insertable;

	public function __construct($db)
	{
		$this->db = $db;
	}


	// this method handles api calls that target the model by class name
	// it is called by Controller::autoload()
	// $identifier and $attribute refer to the second and third subdirectory
	public function call($identifier, $attribute)
	{
		// prevent SQL injection
		$pattern = "\b(ALTER|CREATE|DELETE|DROP|EXEC(UTE){0,1}|INSERT( +INTO){0,1}|MERGE|SELECT|UPDATE|UNION( +ALL){0,1})\b";
		if (preg_match($pattern, $identifier) || preg_match($pattern, $attribute)) {
			http_response_code(403);
			die("Be a nice person, don't inject SQL");
		}
		
		switch ($_SERVER['REQUEST_METHOD'])
		{
			case 'GET':
				// GET /items/ [returns list with all items]
				// this can be filtered using GET URL parameters,
				// such as GET /items/?color=red [returns list with all red items]
				if (!isset($identifier)) return $this->getAll($_SERVER['QUERY_STRING']);
				// GET /items/42 [returns item #42]
				if (!isset($attribute)) return $this->getSingle($identifier);
				// GET /items/42/color [returns the color of item #42]
				return $this->getAttribute($identifier, $attribute);
			case 'POST':
				// all post requests include a request body with some JSON data
				$body = json_decode(file_get_contents('php://input'), true);
				// POST /items/ [creates new item from request body data, returns id of the new element]
				// for AUTO_INCREMENT only, if your model doesn't use A_I, use PUT instead
				if (!isset($identifier)) return $this->createSingle($body);
				// POST /items/42 [updates (not replaces) the trageted item #42 with data from the request body,
				// keeps old values if they aren't mentioned in the request]
				if (!isset($attribute)) return $this->updateSingle($identifier, $body);
				// we don't allow using POST to set individual attributes,
				// use POST /items/42 and wrap the atribute in a JSON object instead
				return $this->api_response('Too Many Arguments', 400);
			case 'PUT':
				// like POST, all PUT requests include a request body
				$body = json_decode(file_get_contents('php://input'), true);
				// PUT /items/42 [replaces (not updates) the targeted item #42 with the request body,
				// values not included in the body default to whatever the default attribute value is (null, empty string, etc)]
				if (isset($identifier) && !isset($attribute)) return $this->replaceSingle($identifier, $body);
				// we don't allow setting individual attributes (use POST instead),
				// or replacing entire lists (if you really need this, you'll have to implement it yourself)
				return $this->api_response('Bad Request', 400);
			case 'DELETE':
				// DELETE /items/42 [deletes item #42]
				if (isset($identifier) && !isset($attribute)) return $this->deleteSingle($identifier);
				// we don't allow deleting of attributes (not really possible, use POST to reset them),
				// or deleting the entire list (obviously a bad idea, again, if you absolutely can't live without this, implement it yourself)
				return $this->api_response('Unable To Delete Target Resource', 400);
			default:
				// other request methods are not available right now
				// PATCH is implemented by POST, since that seems to be the general convention,
				// HEAD and OPTIONS might be implemented later on, but those are only nice to have features (i know REST says otherwise)
				return $this->api_response('Request Method Is Not Implemented By The Target Resource', 405);
		}
	}


	// returns a list of all elements, can be filtered by attributes, via GET
	// parameters using the structures defined in README.md
	public function getAll($filter)
	{
		// filters are key-value pairs separated by `&` characters
		$filter = explode('&', $filter);
		// the SELECT query; the WHERE 1 exists to make it easier to append more
		// conditions using AND
		$query = "SELECT * FROM `$this->name` WHERE 1";
		// array containing the values representing the values to be bound to
		// the query; this exists to prevent sql injections
		// (yes, I studied boolean algebra in university)
		$params = [];
		// $pair is a key-value pair in the form key=value
		foreach ($filter as $pair)
		{
			// if there are more than one = sign, the filter is invalid, and we ignore it
			if (substr_count($pair, '=') > 1) continue;
			// if there is exactly one = sign, we are dealing with a normal key-value pair
			if (substr_count($pair, '=') == 1)
			{
				// position (index) of the = in the string
				$eqpos = strpos($pair, '=');
				// attribute (everything before the = sign)
				$attr = substr($pair, 0, $eqpos);
				// value (everything after the = sign, can be an empty string)
				$value = substr($pair, $eqpos + 1);
			}
			// if there are no = signs, we treat the filter as a NOT NULL
			else
			{
				// in this case, the whole thing is the attribute
				$attr = $pair;
				// value is null, note that this value is not used in the actual query
				$value = null;
			}
			// to prevent sql injections, we only allow filtering by existing attributes
			if (!in_array($attr, $this->searchable)) continue;

			// special case for the NULL check, since it requires special sql syntax
			if ($value === null)
			{
				$query .= " AND $attr IS NOT NULL";
				continue;
			}

			// if a value contains commas, that means it targets multiple options
			$parts = explode(',', $value);
			// this part of the query is a series of conditions joined with OR,
			// joined to the query with an AND
			// e.g. /items/?color=red,blue&type=car&name -> SELECT * FROM items WHERE 1 AND (0 OR color = 'red' OR color = 'blue') AND (0 OR type = 'car') AND name IS NOT NULL
			// it's longer than neccessary, but it gets the job done and the implementation is cleaner
			$query .= " AND (0";
			// $part is a single value such as 'red', 'blue', ''
			foreach ($parts as $part)
			{
				// add condition to query
				$query .= " OR $attr = ?";
				// push value to $params, this value will be escaped and replace the ? in the query
				$params[] = $part;
			}
			// end the condition
			$query .= ")";
		}
		// return response from the database
		$this->api_response($this->db->query($query, $params));
	}


	public function getSingle($identifier)
	{
		// get element from database
		$result = $this->db->query("SELECT * FROM $this->name WHERE $this->id = ?", [$identifier]);
		// if it doesn't exist, return a 404
		if (!$result) $this->api_response('Item Does Not Exist', 404);
		// else return the item; [0] is needed since DB::query() returns the element wrapped in an array
		else $this->api_response($result[0]);
	}


	public function getAttribute($identifier, $attribute)
	{
		// if the attribute is not supposed to be accessed, or doesn't exist, return a 404
		// this _could_ be a 403 if the attribute exists, but is not supposed to be accessed,
		// but the benefit of this wouldn't justify the amount of work required to implement this
		// this also serves as protection against sql injections
		if (!in_array($attribute, $this->searchable)) return $this->api_response('Invalid Attribute', 404);

		// run the query
		$result = $this->db->query("SELECT $attribute FROM $this->name WHERE $this->id = ?", [$identifier]);
		// if nothing is returned, we'll assume that the item does not exist and return a 404
		if (!$result) $this->api_response('Item Does Not Exist', 404);
		// else, return the attribute; the [0] is needed since the result is wrapped in an array
		else $this->api_response($result[0][$attribute]);
	}


	public function createSingle($data)
	{
		// $columns stores all attributes from the request body, $values stores the corresponding values
		$columns = $values = [];
		foreach ($data as $attr => $value)
		{
			// if an attribute is not included in $insertable, we ignore it
			// this also serves as protection against sql injections
			if (!in_array($attr, $this->insertable)) continue;
			// push attribute and value to the corresponding arrays
			$columns[] = $attr;
			$values[] = $value;
		}
		// join column names with commas
		$columns = implode(', ', $columns);
		// this neat line adds a question mark for every item in $values and joins them with commas
		// this is used to create the query template
		// e.g. ['red', 'car'] -> '?, ?'
		$values_template = implode(', ', array_fill(0, count($values), '?'));
		// execute the query
		$this->db->query("INSERT INTO $this->name ($columns) VALUES ($values_template)", $values);
		// since a new id is automatically generated, respond with the new id
		$this->api_response($this->db->getLastInsertId());
	}


	public function updateSingle($identifier, $data)
	{
		// $updates stores all attributes from the request body, $values stores the corresponding values
		$updates = $values = [];
		foreach ($data as $attr => $value)
		{
			// if an attribute is not included in $insertable, we ignore it
			// this also serves as protection against sql injections
			if (!in_array($attr, $this->insertable)) continue;
			// push attribute and value to the corresponding arrays
			// also adds a placeholder for the value after every attribute
			$updates[] = "$attr = ?";
			$values[] = $value;
		}
		// join column names with commas
		$updates = implode(', ', $updates);
		// push the primary key ($identifier) to $values, the last placeholder will be for the id (see query below)
		$values[] = $identifier;
		// execute the query
		$this->api_response($this->db->query("UPDATE $this->name SET $updates WHERE $this->id = ?", $values));
	}


	public function replaceSingle($identifier, $data)
	{
		// $columns stores all attributes from the request body, $values stores the corresponding values
		$columns = $values = [];
		foreach ($data as $attr => $value)
		{
			// if an attribute is not included in $insertable, we ignore it
			// this also serves as protection against sql injections
			if (!in_array($attr, $this->insertable)) continue;
			// push attribute and value to the corresponding arrays
			$columns[] = $attr;
			$values[] = $value;
		}
		// join column names with commas
		$columns = implode(', ', $columns);
		// this line adds a question mark for every item in $values and joins them with commas
		// this is used to create the query template
		// e.g. ['red', 'car'] -> '?, ?'
		$values_template = implode(', ', array_fill(0, count($values), '?'));
		// push the primary key ($identifier) to $values, the last placeholder will be for the id (see query below)
		$values[] = $identifier;
		// execute the query
		$this->api_response($this->db->query("REPLACE INTO $this->name ($columns) VALUES ($values_template)", $values));
	}


	public function deleteSingle($identifier)
	{
		// this one is straight forward, simply delete the targeted element
		$this->api_response($this->db->query("DELETE FROM $this->name WHERE $this->id = ?", [$identifier]));
	}



	// returns a valid api response as json
	// json allows the root element to be of any data type
	// this means that strings will be wrapped in quotation marks
	protected function api_response($data, $code = 200)
	{
		http_response_code($code);
		header('Content-Type: application/json');
		echo json_encode($data);
	}

	/*
	protected function text_response($text, $code = 200)
	{
		http_response_code($code);
		header('Content-Type: text/plain');
		echo json_encode($text);
	}
	*/

}
