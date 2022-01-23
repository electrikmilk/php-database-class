<?php

class Database {
	private $connection;
	public function __construct() {
		$this->connection = mysqli_connect('host','user','pass');
	}
	private function create() {

	}
	private function read() {

	}
	private function modify() {

	}
	public function get($table,$where) {
		$this->read($table,$where);
	}
	public function update() {

	}
	public function insert() {

	}
	public function delete() {

	}
}