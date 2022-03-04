<?php

class MyDynamicModel extends Model {

	const SCHEME = '/posts/{year}/{month}/{day}/{post_name}.html';

	public function call($tags) {
		$this->return($tags);
		return true;
	}

}
