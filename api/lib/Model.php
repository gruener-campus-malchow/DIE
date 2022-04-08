<?php

abstract class Model
{

	protected $db;
	protected $name, $id, $searchable, $insertable;

	public function __construct($db)
	{
		$this->db = $db;
	}



	public function call($identifier, $attribute)
	{
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


	public function getAll($filter)
	{
		$filter = explode('&', $filter);
		$query = "SELECT * FROM `$this->name` WHERE 1";
		$params = [];
		foreach ($filter as $pair)
		{
			if (substr_count($pair, '=') > 1) continue;
			if (substr_count($pair, '=') == 1)
			{
				$eqpos = strpos($pair, '=');
				$attr = substr($pair, 0, $eqpos);
				$value = substr($pair, $eqpos + 1);
			}
			else
			{
				$attr = $pair;
				$value = null;
			}
			if (!in_array($attr, $this->searchable)) continue;
			if ($value === null)
			{
				$query .= " AND $attr IS NOT NULL";
				continue;
			}

			$parts = explode(',', $value);
			$query .= " AND (0";
			foreach ($parts as $part)
			{
				$query .= " OR $attr = ?";
				$params[] = $part;
			}
			$query .= ")";
		}
		$this->api_response($this->db->query($query, $params));
	}

	public function getSingle($identifier)
	{
		$result = $this->db->query("SELECT * FROM $this->name WHERE $this->id = ?", [$identifier]);
		if (!$result) $this->api_response('Item Does Not Exist', 404);
		else $this->api_response($result[0]);
	}

	public function getAttribute($identifier, $attribute)
	{
		if (!in_array($attribute, $this->searchable)) return $this->api_response('Invalid Attribute', 404);

		$result = $this->db->query("SELECT $attribute FROM $this->name WHERE $this->id = ?", [$identifier]);
		if (!$result) $this->api_response('Item Does Not Exist', 404);
		else $this->api_response($result[0][$attribute]);
	}

	public function createSingle($data)
	{
		$columns = $values = [];
		foreach ($data as $attr => $value)
		{
			if (!in_array($attr, $this->insertable)) continue;
			$columns[] = $attr;
			$values[] = $value;
		}
		$columns = implode(', ', $columns);
		$values_template = implode(', ', array_fill(0, count($values), '?'));
		$this->db->query("INSERT INTO $this->name ($columns) VALUES ($values_template)", $values);
		$this->api_response($this->db->getLastInsertId());
	}

	public function updateSingle($identifier, $data)
	{
		$updates = $values = [];
		foreach ($data as $attr => $value)
		{
			if (!in_array($attr, $this->insertable)) continue;
			$updates[] = "$attr = ?";
			$values[] = $value;
		}
		$updates = implode(', ', $updates);
		$values[] = $identifier;
		$this->api_response($this->db->query("UPDATE $this->name SET $updates WHERE $this->id = ?", $values));
	}

	public function replaceSingle($identifier, $data)
	{
		$columns = $values = [];
		foreach ($data as $attr => $value)
		{
			if (!in_array($attr, $this->insertable)) continue;
			$columns[] = $attr;
			$values[] = $value;
		}
		$columns = implode(', ', $columns);
		$values_template = implode(', ', array_fill(0, count($values), '?'));
		$values[] = $identifier;
		$this->api_response($this->db->query("REPLACE INTO $this->name ($columns) VALUES ($values_template)", $values));
	}

	public function deleteSingle($identifier)
	{
		$this->api_response($this->db->query("DELETE FROM $this->name WHERE $this->id = ?", [$identifier]));
	}



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
