<?php

namespace Kyte;

class DBI {
	private static $dbConn;

	public static $dbUser;
	public static $dbPassword;
	public static $dbName;
	public static $dbHost;

	/*
	 * Sets the database username to be used to connect to DB
	 *
	 * @param string $dbUser
	 */
	public static function setDbUser($dbUser)
	{
		self::$dbUser = $dbUser;
	}

	/*
	 * Sets the database password to be used to connect to DB
	 *
	 * @param string $dbPassword
	 */
	public static function setDbPassword($dbPassword)
	{
		self::$dbPassword = $dbPassword;
	}

	/*
	 * Sets the database host to be used to connect to DB
	 *
	 * @param string $dbUser
	 */
	public static function setDbHost($dbHost)
	{
		self::$dbHost = $dbHost;
	}

	/*
	 * Sets the database name to be used to connect to DB
	 *
	 * @param string $dbName
	 */
	public static function setDbName($dbName)
	{
		self::$dbName = $dbName;
	}

	/*
	 * Connect to database
	 *
	 * @param string $dbName
	 */
	public static function connect()
	{
		if (!self::$dbConn) {
			try {
				self::$dbConn = new \mysqli(self::$dbHost, self::$dbUser, self::$dbPassword, self::$dbName);
			} catch (mysqli_sql_exception $e) {
				throw $e;
			}
		}

		return self::$dbConn;
	}

	/*
	 * Make an insert into table in database
	 *
	 * @param string $table
	 * @param array $params
	 * @param string $types
	 */
	public static function insert($table, $params, $types)
	{
		try {
			if (!self::$dbConn) {
				self::connect();
			}
		} catch (\Exception $e) {
			throw $e;
			return false;
		}

		// prepare bind params for call_user_func_array
		$bindParams = array();
		$columns = array_keys($params);
		
		for ($i = 0; $i < count($columns); $i++) {
			$bindParams[] = $params[$columns[$i]];
			$columns[$i] = '`'.$columns[$i].'`';
		}

		$placeholder = str_repeat("?, ", count($params));
		$placeholder = substr($placeholder, 0, -2);

		$query = sprintf("INSERT INTO `%s`(%s) VALUES(%s)", $table, implode(',', $columns), $placeholder);

		$stmt = self::$dbConn->prepare($query);
		if($stmt === false) {
  			throw new \Exception("Error preparing mysql statement '$query'; ".htmlspecialchars(self::$dbConn->error), 1);
  			return false;
		}
 
 		$stmt->bind_param($types, ...$bindParams);
		// call_user_func_array(array($stmt, 'bind_param'), $bindParams);

		if (!$stmt->execute()) {
			throw new \Exception("Error executing mysql statement '$query'; ".htmlspecialchars(self::$dbConn->error), 1);
			$stmt->close();
			return false;
		}

		$insertId = $stmt->insert_id;
		$stmt->close();

		return $insertId;
	}

	/*
	 * Make a table update in database
	 *
	 * @param string $table
	 * @param integer $id
	 * @param array $params
	 * @param string $types
	 */
	public static function update($table, $id, $params, $types)
	{
		if (!self::$dbConn) {
			self::connect();
		}

		$query = "UPDATE `$table` SET ";

		// prepare bind params for call_user_func_array
		$bindParams = array();
		// add integer to to end of types for the where condition
		$types .= 'i';
		// $bindParams[] = &$types;
		foreach ($params as $key => $value) {
			$query .= "`$key` = ?, ";
			$bindParams[] = $value;
		}
		// add the id of row to update
		$bindParams[] = $id;
		// fix query to remove last comma and space
		$query = substr($query, 0, -2);
		// add condition
		$query .= " WHERE id = ?";

		$stmt = self::$dbConn->prepare($query);
		if($stmt === false) {
  			throw new \Exception("Error preparing mysql statement '$query'; ".htmlspecialchars(self::$dbConn->error), 1);
  			return false;
		}
 
 		$stmt->bind_param($types, ...$bindParams);
		// call_user_func_array(array($stmt, 'bind_param'), $bindParams);

		if (!$stmt->execute()) {
			throw new \Exception("Error executing mysql statement '$query'; ".htmlspecialchars(self::$dbConn->error), 1);
			$stmt->close();
			return false;
		}

		$stmt->close();
		
		return true;
	}

	/*
	 * Delete an entry in database table
	 *
	 * @param string $table
	 * @param integer $id
	 */
	public static function delete($table, $id)
	{
		if (!self::$dbConn) {
			self::connect();
		}

		$query = "DELETE FROM `$table` WHERE id = ?";

		$stmt = self::$dbConn->prepare($query);
		if($stmt === false) {
  			throw new \Exception("Error preparing mysql statement '$query'; ".htmlspecialchars(self::$dbConn->error), 1);
  			return false;
		}
 
		$stmt->bind_param('i', $id);

		if (!$stmt->execute()) {
			throw new \Exception("Error executing mysql statement '$query'; ".htmlspecialchars(self::$dbConn->error), 1);
			$stmt->close();
			return false;
		}

		$stmt->close();
		
		return true;
	}

	/*
	 * Select from table in database and returns the first row only
	 *
	 * @param string $table
	 * @param integer $id
	 * @param string $condition
	 */
	public static function select($table, $id = null, $condition = null)
	{
		if (!self::$dbConn) {
			self::connect();
		}

		$query = "SELECT * FROM `$table`";

		if(isset($id)) {
			$query .= " WHERE id = $id";
		} else {
			$query .= " $condition";
		}

		error_log($query);

		$result = self::$dbConn->query($query);
		if($result === false) {
  			throw new \Exception("Error with mysql query '$query'.");
  			return false;
		}

		$data = array();
		while ($row = $result->fetch_assoc()) {
			$data[] = $row;
		}

		$result->free();
		
		return $data;
	}
}

?>
