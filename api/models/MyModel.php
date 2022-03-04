<?php

class MyModel extends Model {

	const SCHEME = '/';

	public function call() {
		$this->return([
			'status' => 'success',
			'content' => 'hello world'
		]);
		return true;
	}

}
