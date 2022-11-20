<?php

class Items extends Model
{
	protected $name = 'items';
	protected $id = 'id';
	protected $searchable = ['name', 'color'];
	protected $insertable = ['name', 'color'];
}
