<?php

class Database
{
	private mysqli $connection;
	private string $error;

	/**
	 * @throws ErrorException
	 */
	public function __construct(string $db)
	{
		try {
			$this->connection = mysqli_connect('hostname', 'username', 'password', $db);
			if (!$this->connection) throw new RuntimeException("Database connection failed.");
		} catch (Exception $e) {
			echo 'Caught exception: ' . $e->getMessage();
		}
	}

	public function error()
	{
		return $this->error ?? false;
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
		$query = mysqli_query($this->connection, "insert into $table ($cols) values (`$values`)");
		if (!$query) {
			$this->error = "Database Error: " . mysqli_error($this->connection);
			return false;
		}
		return mysqli_insert_id($this->connection);
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
			if (count($order) === 2 || (count($order) === 1 && $order[0] === "RAND()")) {
				$order = implode(" ", $order);
				if ($order[1]) {
					try {
						if ($order[1] !== "asc" || $order[1] !== "desc") {
							throw new RuntimeException("Tried to sort by $order[1].");
						}
					} catch (Exception $e) {
						echo 'Caught exception: ' . $e->getMessage();
					}
				}
			} else {
				return false;
			}
		}
		if ($limit) {
			$limit = "limit $limit";
		}
		$query = mysqli_query($this->connection, "select * from $table where $where $order $limit");
		if (mysqli_num_rows($query) === 0) {
			return false;
		}
		return mysqli_fetch_array($query);
	}

	private function modify($action, $table, $fields, $where): bool
	{
		$update_fields = [];
		if (!count($where) || count($where) !== 3) {
			return false;
		}
		if ($action === "update" && !count($fields)) {
			return false;
		}
		if ($action === "delete") {
			$action = "delete from";
		}
		if (count($fields)) {
			foreach ($fields as $column => $value) {
				if (!$value) {
					$value = "NULL";
				} else {
					$value = "'" . mysqli_real_escape_string($this->connection, $value) . "'";
				}
				$update_fields[] = "$column = $value";
			}
			$update_fields = "set " . implode(", ", $update_fields);
		}
		if (count($where) === 3) {
			$where = implode(" ", $where);
		} else {
			return false;
		}
		if (is_array($update_fields)) {
			unset($update_fields);
		}
		$query = mysqli_query($this->connection, "$action $table $update_fields where $where");
		if (!$query) {
			return false;
		}
		return true;
	}

	public function get(string $table, $where, ?array $order, ?int $limit)
	{
		return $this->read($table, $where);
	}

	public function update(string $table, array $fields, array $where): bool
	{
		return $this->modify("update", $table, $fields, $where);
	}

	public function insert(string $table, array $fields)
	{
		return $this->create($table, $fields);
	}

	public function delete(string $table, array $where): bool
	{
		return $this->modify("delete", $table, false, $where);
	}
}