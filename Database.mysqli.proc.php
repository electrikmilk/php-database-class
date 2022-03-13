<?php
/**
 * Database.php
 *
 * @author brandonjordan
 * @datetime 3/12/2022 19:11
 * @copyright (c) 2022 Brandon Jordan
 */

class Database
{
	private $connection;
	private string $db;
	private string $error;
	private string $count;

	/**
	 * @throws Error
	 */
	public function __construct(string $db)
	{
		try {
			$this->db = $db;
			$this->connection = mysqli_connect('hostname', 'user', 'password', $this->db);
			if (!$this->connection || mysqli_connect_errno()) throw new Error("Database connection failed: " . mysqli_connect_error());
		} catch (Error $e) {
			die('Caught error: ' . $e->getMessage());
		}
	}

	public function count()
	{
		return $this->count ?? 0;
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

	private function read(string $table, $where, ?array $order, ?int $limit)
	{
		if (is_array($where)) {
			if (count($where) === 3) {
				$where = implode(" ", $where);
			} else {
				return false;
			}
		}
		if ($where) {
			$where = "where $where";
		}
		if ($order) {
			if (count($order) === 2 || (count($order) === 1 && $order[0] === "RAND()")) {
				$order = implode(" ", $order);
				if ($order[1]) {
					try {
						if ($order[1] !== "asc" || $order[1] !== "desc") {
							throw new Error("Tried to sort by $order[1].");
						}
					} catch (Error $e) {
						die('Caught error: ' . $e->getMessage());
					}
				}
			} else {
				return false;
			}
		}
		if ($limit) {
			$limit = "limit $limit";
		}
		$query = mysqli_query($this->connection, "select * from $this->db.$table $where $order $limit");
		if (!$query) {
			return false;
		}
		$this->count = mysqli_num_rows($query);
		if ($this->count === 0) {
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

	public function get(string $table, $where = null, ?array $order = null, ?int $limit = null)
	{
		return $this->read($table, $where, $order, $limit);
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
