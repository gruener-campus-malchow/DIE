<?php

abstract class Model
{

	abstract public function call($tags);

	protected function return($data)
	{
		$this->response(200, $data);
	}

	protected function response($code, $data)
	{
		http_response_code($code);
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
