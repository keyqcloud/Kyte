<?php

namespace Kyte;

class Session extends BaseObject {

	protected static function getTable() {
		return [
			'name'		=> 'session',
			'struct'	=> [
				'id'			=> [
					'type'		=> 'i',
					'required'	=> false,
					'date'		=> false,
				],

				'deleted'	=> [
					'type'		=> 'i',
					'required'	=> false,
					'date'		=> false,
				],

				'uid'		=> [
					'type'		=> 'i',
					'required'	=> true,
					'date'		=> false,
				],

				'create_date'	=> [
					'type'		=> 'i',
					'required'	=> true,
					'date'		=> true,
				],

				'exp_date'		=> [
					'type'		=> 'i',
					'required'	=> true,
					'date'		=> true,
				],

				'token'		=> [
					'type'		=> 's',
					'required'	=> true,
					'date'		=> false,
				],
			],
		];
	}

	// override parent constriuctor
	public function __construct() {
		$this->table = static::getTable();
	}
}

?>
