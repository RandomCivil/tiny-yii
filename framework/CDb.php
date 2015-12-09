<?php
class CDb {
	public $driver;
	public $hostname;
	public $username;
	public $password;
	public $database;
	protected static $db, $link;
	public function init() {
		$driver = $this->driver;
		$hostname = $this->hostname;
		$database = $this->database;
		$username = $this->username;
		$password = $this->password;
		$pdo_options = array ();
		if (! in_array ( $driver, PDO::getAvailableDrivers () )) {
			die ( "Error!: could not find a <a href=\"http://php.net/pdo.drivers.php\" target=\"_blank\">" . $driver . "</a> driver<br/>" );
		}
		switch ('mysql') {
			case 'mysql' :
				$string = "mysql:host=$hostname;dbname=$database";
				$pdo_options = array (
						PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8" 
				);
				break;
			case 'pgsql' :
				$string = "pqsql:host=$hostname;dbname=$database";
				break;
			case 'sqlite' :
				$string = "sqlite:$database_path";
				break;
			case 'oracle' :
				$string = "OCI:";
				break;
			case 'odbc' :
				$string = "odbc:Driver={Microsoft Access Driver (*.mdb)};Dbq=$database;Uid=$username";
				break;
			default :
				die ( "Error!: Driver $driver not recognized in DB class" );
		}
		self::setup ( $string, $username, $password, $pdo_options );
	}
	static function setup($string, $username, $password, $pdo_options = array()) {
		try {
			self::$link = new PDO ( $string, $username, $password, $pdo_options );
			self::$link->setAttribute ( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );
		} catch ( PDOException $e ) {
			die ( "Error!: " . $e->getMessage () . "<br/>" );
		}
	}
	function query($sql, $params = array()) {
		$stmt = self::$link->prepare ( $sql );
		$stmt->execute ( $params );
		$inner = array ();
		$outer = array ();
		while ( $row = $stmt->fetch ( PDO::FETCH_ASSOC ) ) {
			foreach ( $row as $k => $v ) {
				$inner [$k] = $v;
			}
			array_push ( $outer, $inner );
		}
		return $outer;
	}
	function insert($table, $data) {
		if ($n = count ( $data )) {
			$fields = implode ( ',', array_keys ( $data ) );
			$values = implode ( ',', array_fill ( 0, $n, '?' ) );
			$prepared = array_values ( $data );
			$stmt = self::$link->prepare ( "INSERT INTO $table ($fields) VALUES ($values)" );
			$stmt->execute ( $prepared );
		}
	}
	function delete($table, $where, $params = array()) {
		$stmt = self::$link->prepare ( 'delete from ' . $table . " where " . $where );
		$stmt->execute ( $params );
	}
	function update($table, $data, $where, $param = null) {
		if (! $where) {
			die ( 'You have to set the parameter $where in order to use db::update()' );
		}
		if (count ( $data )) {
			foreach ( $data as $field => $value ) {
				$fields [] = $field . '=?';
			}
			$prepared = array_values ( $data );
			$fields_query = implode ( ',', $fields );
			$where = " WHERE $where";
			$stmt = self::$link->prepare ( "UPDATE $table SET $fields_query $where" );
			foreach ( $param as $v1 )
				$prepared [] = $v1;
			$stmt->execute ( $prepared );
		}
	}
	static function disconnect() {
		unset ( self::$link );
	}
	static function begin() {
		return self::$link->beginTransaction ();
	}
	static function commit() {
		return self::$link->commit ();
	}
	static function rollback() {
		return self::$link->rollBack ();
	}
}
?>