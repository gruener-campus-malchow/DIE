<?php

class MyDynamicModel extends Model
{

	const SCHEME = '/posts/{year}/{month}/{day}/{post_name}.html';

	public function call($tags)
	{
		$tags['get_params'] = $_GET;
		$this->return($tags);
		return true;
	}

}
