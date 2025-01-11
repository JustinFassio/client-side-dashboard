<?php

class wpdb {
	public $prefix = 'wp_';
	private $dbh;
	private $dbname;
	private $dbuser;
	private $dbpassword;
	private $dbhost;
	private $last_prepared_query;

	public function __construct( $dbuser, $dbpassword, $dbname, $dbhost ) {
		$this->dbuser     = $dbuser;
		$this->dbpassword = $dbpassword;
		$this->dbname     = $dbname;
		$this->dbhost     = $dbhost;
		$this->connect();
	}

	private function connect() {
		$socket = strstr( $this->dbhost, ':' );
		if ( $socket ) {
			$socket    = substr( $socket, 1 );
			$this->dbh = new PDO(
				"mysql:unix_socket={$socket};dbname={$this->dbname}",
				$this->dbuser,
				$this->dbpassword
			);
		} else {
			$this->dbh = new PDO(
				"mysql:host={$this->dbhost};dbname={$this->dbname}",
				$this->dbuser,
				$this->dbpassword
			);
		}
		$this->dbh->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
	}

	public function prepare( $query, $args = array() ) {
		if ( $query instanceof PDOStatement ) {
			return $query;
		}

		if ( is_array( $args ) && ! empty( $args ) ) {
			$values = array_values( $args );
			$types  = array_map(
				function ( $value ) {
					if ( is_int( $value ) ) {
						return '%d';
					}
					if ( is_float( $value ) ) {
						return '%f';
					}
					return '%s';
				},
				$values
			);
			$query  = vsprintf( $query, $types );
		}

		$stmt = $this->dbh->prepare( $query );
		if ( is_array( $args ) && ! empty( $args ) ) {
			foreach ( $args as $i => $value ) {
				$stmt->bindValue( $i + 1, $value, is_int( $value ) ? PDO::PARAM_INT : PDO::PARAM_STR );
			}
		}
		return $stmt;
	}

	public function query( $query ) {
		try {
			return $this->dbh->exec( $query );
		} catch ( PDOException $e ) {
			error_log( 'Database error: ' . $e->getMessage() );
			return false;
		}
	}

	public function get_var( $query, $args = array() ) {
		try {
			if ( is_array( $args ) && ! empty( $args ) ) {
				$stmt = $this->prepare( $query );
				$stmt->execute( $args );
			} elseif ( $query instanceof PDOStatement ) {
					$stmt = $query;
					$stmt->execute();
			} else {
				$stmt = $this->dbh->query( $query );
			}
			return $stmt->fetchColumn();
		} catch ( PDOException $e ) {
			error_log( 'Database error: ' . $e->getMessage() );
			return null;
		}
	}

	public function get_results( $query, $output = OBJECT ) {
		$stmt = $this->prepare( $query );
		$stmt->execute();
		return $stmt->fetchAll( PDO::FETCH_OBJ );
	}

	public function insert( $table, $data, $format = null ) {
		$fields  = array_keys( $data );
		$formats = $format ? $format : array_fill( 0, count( $fields ), '%s' );
		$values  = array_values( $data );

		$sql = "INSERT INTO $table (" . implode( ',', $fields ) . ') VALUES (' . implode( ',', array_fill( 0, count( $values ), '?' ) ) . ')';

		$stmt = $this->prepare( $sql );
		return $stmt->execute( $values );
	}

	public function replace( $table, $data, $format = null ) {
		$fields  = array_keys( $data );
		$formats = $format ? $format : array_fill( 0, count( $fields ), '%s' );
		$values  = array_values( $data );

		$sql = "REPLACE INTO $table (" . implode( ',', $fields ) . ') VALUES (' . implode( ',', array_fill( 0, count( $values ), '?' ) ) . ')';

		$stmt = $this->prepare( $sql );
		return $stmt->execute( $values );
	}

	public function update( $table, $data, $where, $format = null, $where_format = null ) {
		$fields = array();
		$values = array();

		foreach ( $data as $field => $value ) {
			$fields[] = "$field = ?";
			$values[] = $value;
		}

		$where_clause = array();
		foreach ( $where as $field => $value ) {
			$where_clause[] = "$field = ?";
			$values[]       = $value;
		}

		$sql = "UPDATE $table SET " . implode( ',', $fields ) . ' WHERE ' . implode( ' AND ', $where_clause );

		$stmt = $this->prepare( $sql );
		return $stmt->execute( $values );
	}

	public function delete( $table, $where, $where_format = null ) {
		$where_clause = array();
		$values       = array();

		foreach ( $where as $field => $value ) {
			$where_clause[] = "$field = ?";
			$values[]       = $value;
		}

		$sql = "DELETE FROM $table WHERE " . implode( ' AND ', $where_clause );

		$stmt = $this->prepare( $sql );
		return $stmt->execute( $values );
	}
}
