<?php

class DB
{

	private $connection;


	public function __construct($host, $username, $password, $database)
	{
		try
		{
			$this->connection = new PDO("mysql:host=" . $host . ";dbname=" . $database, $username, $password);
		}
		catch (PDOException $e)
		{
			if (ENV == 'DEV') echo 'Connection failed: ' . $e->getMessage();
		}
	}


	public function query($query, $values = [], $fetchMode = PDO::FETCH_ASSOC)
	{
		if (!isset($this->connection)) return false;

		$statement = $this->connection->prepare($query);

		foreach ($values as $key => $value)
		{
			$statement->bindValue(':' . $key, $value);
		}

		$statement->execute();
		return $statement->fetchAll($fetchMode);
	}

}
