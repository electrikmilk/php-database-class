<?php

class Database
{
	private $connection;
	private $db;
	private $error;

	public function __construct($db)
	{
		$this->db = $db;
		$this->connection = mysqli_connect('host', 'user', 'pass', $this->db);
	}

	private function create($table, $fields)
	{
		$cols = implode(",", array_keys($fields));
		$values = [];
		foreach ($fields as $col => $value) {
			if (!$value) {
				$values[] = null;
			} else {
				$values[] = mysqli_real_escape_string($this->connection, $value);
			}
		}
		$values = implode("`,`", $values);
		$query = mysqli_query($this->connection, "insert into $db.$table ($cols) values (`$values`)");
		if(!$query) {
			$this->$error = mysqli_error($connect);
			return false;
		}
		return mysqli_insert_id();
	}

	private function read($table, $where, $order = null, $limit = null)
	{
		if (is_array($where)) {
			if (count($where) === 3) {
				$where = implode(" ", $where);
			} else {
				return false;
			}
		}
		if ($order) {
			if (count($order) === 2 || ($order === 1 && $order[0] === "RAND()")) {
				$where = implode(" ", $where);
			} else {
				return false;
			}
		}
		if ($limit) {
			$limit = "limit $limit";
		}
		$query = mysqli_query($this->connection, "select * from $db.$table where $where $order $limit");
		if (mysqli_num_rows($query) === 0) {
			return false;
		}
		return mysqli_fetch_array($query);
	}

	private function modify($action, $table, $fields, $where)
	{
		// todo
	}

	public function get(string $table, $where, ?array $order, ?int $limit)
	{
		return $this->read($table, $where);
	}

	public function update(string $table, array $fields, $where)
	{
		return $this->modify("update", $table, $fields, $where);
	}

	public function insert(string $table, array $fields)
	{
		return $this->create($table, $fields);
	}

	public function delete(string $table, $where)
	{
		return $this->modify("delete", $table, false, $where);
	}
}