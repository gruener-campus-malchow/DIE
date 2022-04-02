<?php

abstract class Model
{

	protected $db;
	protected $name, $id, $searchable, $insertable;

	public function __construct($db)
	{
		$this->db = $db;
	}



	public function getAll($filter)
	{
		$query = "SELECT * FROM `$this->name` WHERE 1";
		$params = [];
		foreach ($filter as $attr => $value)
		{
			if (!in_array($attr, $this->searchable)) continue;

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

}
