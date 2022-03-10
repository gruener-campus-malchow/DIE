<?php

class Jokes extends Model
{

	const SCHEME = '/jokes/';

	public function call($tags)
	{
		$response = [];
		$dir = '../humor/';
		foreach (scandir($dir) as $file)
		{
			$path = $dir . $file;
			if (!is_dir($path) && substr($file, -3) == '.md')
			{
				array_push($response, [
					'filename' => $file,
					'content' => file_get_contents($path)
				]);
			}
		}
		$this->return($response);
		return true;
	}

}
