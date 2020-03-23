<?php

namespace Kyte;

/*
 * Class ModelObject
 *
 * @package Kyte
 *
 */

class ModelObject
{
	// key-value describing model
	// 
	//	[
	// 		'name'		=> 'name of table (also name of object)',
	// 		'struct'	=> [
	//			'column name' => [
	//				'type'		=>	'i/s/d',		(*required*)
	// 				'requred'	=>	true/false,		(*required*)
	// 				'date'		=>	true/false,
	// 				'kms'		=>	true/false,
	//		 	],
	//			...
	//			'column name' => [ 'type' => 'i/s/d', 'requred' => true/false ],
	//		]
	//	]
	protected $model = [];

	protected $values = [];

	public function __construct($model) {
		$this->model = $model;
	}

	/*
	 * Return bind param types based on params for each subclass that exteds this
	 * Can be overridden by child class
	 *
	 * @param array $params
	 */
	protected function bindTypes($params) {
		$types = '';
		foreach ($params as $key => $value) {
			if (array_key_exists($key, $this->model['struct'])) {
				$this->setParam($key, $value);
				$types .= $this->model['struct'][$key]['type'];
			}
		}

		return $types;
	}

	/*
	 * Check if the minimum required params for SQL insert query are met
	 * Can be overridden by child
	 *
	 * @param array $params
	 */
	protected function validateRequiredParams($params) {
		if (count($params) == 0) {
			throw new \Exception("Unable to create new entry without valid parameters.");
			return false;
		} else {
			foreach ($this->model['struct'] as $key => $value) {
				if ($value['required'] && !isset($params[$key])) {
					throw new \Exception("Column $key cannot be null.");
					return false;
				}
			}
		}
	}

	/*
	 * Create a new entry in the Object_core database
	 *
	 * @param array $params
	 */
	public function create($params)
	{
		$this->validateRequiredParams($params);

		try {
			$types = $this->bindTypes($params);
			$id = DBI::insert($this->model['name'], $params, $types);
			$this->populate($id);

			return true;
		} catch (\Exception $e) {
			throw $e;
			return false;
		}
	}

	/*
	 * Retrieve entry information with specified conditions
	 *
	 * @param string $field
	 * @param string $value
	 * @param integer $id
	 */
	public function retrieve($field = null, $value = null, $conditions = null, $id = null, $all = false)
	{
		try {
			if (isset($field, $value)) {
				$sql = $all ? "WHERE `$field` = '$value'" : "WHERE `$field` = '$value' AND `deleted` = '0'";
				if(isset($conditions)) {
					foreach($conditions as $condition) {
						$sql .= " AND `{$condition['field']}` = '{$condition['value']}'";
					}
				}
				$data = DBI::select($this->model['name'], null, $sql);
				if (count($data) > 0) {
					$id = $data[0]['id'];
				}
			}

			if (isset($id)) {
				$this->populate($id);

				return true;
			} else return false;
		} catch (\Exception $e) {
			throw $e;
			return false;
		}
	}

	/*
	 * Update entry information for item that was retrieved
	 *
	 * @param array $params
	 */
	public function save($params)
	{
		$id = $this->getParam('id');
		if (!isset($id)) {
			throw new \Exception("No retrieved data to update.  Please try retrieving information with retrieve() first.");
			return false;
		}

		$types = $this->bindTypes($params);

		try {
			DBI::update($this->model['name'], $id, $params, $types);
			return true;
		} catch (\Exception $e) {
			throw $e;
			return false;
		}
	}

	/*
	 * Populate object with entry information
	 *
	 */
	public function populate($id = null)
	{
		try {
			if ($this->getParam('id') === false && !isset($id)) {
				throw new \Exception("No object id was found to retrieve data.");
				return false;
			}

			// if $id is null from parameter, set it to the object's id value
			if (!isset($id)) {
				$id = $this->getParam('id') === false;
			}

			$data = DBI::select($this->model['name'], $id);

			if (count($data[0]) == 0) { return false; }

			foreach ($data[0] as $key => $value) {
				$this->setParam($key, $value);
			}
			
			return true;
		} catch (\Exception $e) {
			throw $e;
			return false;
		}
	}

	/*
	 * Delete entry information with specified conditions
	 *
	 * @param string $field
	 * @param string $value
	 * @param integer $id
	 */
	public function delete($field = null, $value = null)
	{
		try {
			if (isset($field, $value)) {
				$data = DBI::select($this->model['name'], null, "WHERE `$field` = '$value'");
				if (!isset($data[0]['id'])) {
					$id = $data[0]['id'];
				} else {
					throw new \Exception("No entry found for provided id.");
					return false;
				}
			} else if (!isset($field, $value, $id)) {
				$id = $this->getParam('id');
			}
				
			// last check to make sure id is set
			if (!isset($id)) {
				throw new \Exception("No condition or prior entry information was provided for data to be deleted.");
				return false;
			}

			DBI::update($this->model['name'], $id, ['deleted' => 1], 'i');

			return true;
		} catch (\Exception $e) {
			throw $e;
			return false;
		}
	}

	public function purge($field = null, $value = null)
	{
		try {
			if (isset($field, $value)) {
				$data = DBI::select($this->model['name'], null, "WHERE `$field` = '$value'");
				if (!isset($data[0]['id'])) {
					$id = $data[0]['id'];
				} else {
					throw new \Exception("No entry found for provided id.");
					return false;
				}
			} else if (!isset($field, $value, $id)) {
				$id = $this->getParam('id');
			}
				
			// last check to make sure id is set
			if (!isset($id)) {
				throw new \Exception("No condition or prior entry information was provided for data to be deleted.");
				return false;
			}

			DBI::delete($this->model['name'], $id);
			$this->clearParams();

			return true;
		} catch (\Exception $e) {
			throw $e;
			return false;
		}
	}

	protected function setParam($key, $value) {
		$this->values[$key] = $value;
	}

	public function getParam($key) {
		if (array_key_exists($key, $this->values)) {
			return $this->values[$key];
		} else {
			return false;
		}
	}

	public function getParams($keys) {
		$retvals = [];
		foreach ($keys as $key) {
			$retvals[$key] = (array_key_exists($key, $this->values) ? $this->values[$key] : null);
		}
		return $retvals;
	}

	public function getAllParams($formatdate = false) {
		if ($formatdate) {
			$retvals = [];
			foreach ($this->values as $key => $value) {
				if (array_key_exists($key, $this->model['struct'])) {
					if ($this->model['struct'][$key]['date']) {
						$retvals[$key] = date('Y/m/d', $value);
					} else {
						$retvals[$key] = $value;
					}
				} else {
					$retvals[$key] = $value;
				}
			}
			return $retvals;
		} else {
			return $this->values;
		}
	}

	protected function clearParams() {
		foreach ($this->values as $key => $value) {
			unset($this->values[$key]);
		}
	}

	public function paramKeys() {
		return array_keys($this->values);
	}

}