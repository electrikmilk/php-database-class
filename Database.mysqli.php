<?php

// todo: use bind_param

class Database
{
	private mysqli $connection;
	private string $error;

	/**
	 * @throws Exception
	 */
	public function __construct(string $db)
	{
		try {
			$this->connection = new mysqli('hostname', 'username', 'password', $db);
			if (!$this->connection || mysqli_connect_errno()) throw new Exception("Database connection failed: ".mysqli_connect_error());
		} catch (Exception $e) {
			die('Caught exception: ' . $e->getMessage());
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
				$values[] = $this->connection->real_escape_string($value);
			}
		}
		$values = implode("`,`", $values);
		$query = $this->connection->query("insert into $table ($cols) values (`$values`)");
		if (!$query) {
			$this->error = "Database Error: " . $this->connection->error;
			return false;
		}
		return $this->connection->insert_id;
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
							throw new Exception("Tried to sort by $order[1].");
						}
					} catch (Exception $e) {
						die('Caught exception: ' . $e->getMessage());
					}
				}
			} else {
				return false;
			}
		}
		if ($limit) {
			$limit = "limit $limit";
		}
		$query = $this->connection->query("select * from $table where $where $order $limit");
		if ($query->num_rows) {
			return false;
		}
		return $query->fetch_assoc();
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
					$value = "'" . $this->connection->real_escape_string($value) . "'";
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
		$query = $this->connection->query("$action $table $update_fields where $where");
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
