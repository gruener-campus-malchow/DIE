<?php

abstract class Model
{

	abstract public function call($tags);

	protected function return($data)
	{
		if (is_array($data))
		{
			header('Content-Type: application/json');
			echo json_encode($data);
		}
		else
		{
			header('Content-Type: text/plain');
			echo $data;
		}


	}

}
