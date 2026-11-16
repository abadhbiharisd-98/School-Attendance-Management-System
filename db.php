<?php

	/*
		Upgraded Database Driver with Aiven Cloud MySQL & PHP 8.x Compatibility
		Supports SSL Encryption (require_secure_transport=ON), 5-digit custom ports,
		Environment variables (DB_SERVER, DB_PORT, DB_USERNAME, DB_PASSWORD, DB_NAME, DB_SSL),
		and strict mysqli_result type safety.
	*/

	// PHP 8 Compatibility Polyfills
	if(function_exists('mysqli_report')){ @mysqli_report(MYSQLI_REPORT_OFF); }
	if(!function_exists('get_magic_quotes_gpc')){
		function get_magic_quotes_gpc(){ return false; }
	}
	if(!function_exists('get_magic_quotes_runtime')){
		function get_magic_quotes_runtime(){ return false; }
	}
	if(!function_exists('set_magic_quotes_runtime')){
		function set_magic_quotes_runtime($new_setting){ return false; }
	}

	if(!defined('DATABASE')){
		if(function_exists('mysqli_connect')){
			define('DATABASE', 'mysqli');
		}else{
			define('DATABASE', 'mysql');
		}
	}

	define('mysql_charset', 'utf8');

	function db_link($link = NULL){
		static $db_link;
		if($link !== NULL) $db_link = $link;
		return $db_link;
	}

	function db_close($link = NULL){
		if(!$link) $link = db_link();
		db_link(false); /* close db link */
		switch(DATABASE){
			case 'mysql':
				if($link) return @mysql_close($link);
				return false;
			case 'mysqli':
				if($link && ($link instanceof mysqli)) return @mysqli_close($link);
				return false;
		}
	}

	function db_select_db($dbname, $link = NULL){
		if(!$link) $link = db_link();
		if(!$link) return false;
		switch(DATABASE){
			case 'mysql':
				return @mysql_select_db($dbname, $link);
			case 'mysqli':
				if($link instanceof mysqli) return @mysqli_select_db($link, $dbname);
				return false;
		}
	}

	function db_fetch_array($res){
		if(!$res || !($res instanceof mysqli_result)) return false;
		switch(DATABASE){
			case 'mysql':
				return @mysql_fetch_array($res);
			case 'mysqli':
				return @mysqli_fetch_array($res, MYSQLI_BOTH);
		}
	}

	function db_fetch_assoc($res){
		if(!$res || !($res instanceof mysqli_result)) return false;
		switch(DATABASE){
			case 'mysql':
				return @mysql_fetch_assoc($res);
			case 'mysqli':
				return @mysqli_fetch_assoc($res);
		}
	}

	function db_fetch_row($res){
		if(!$res || !($res instanceof mysqli_result)) return false;
		switch(DATABASE){
			case 'mysql':
				return @mysql_fetch_row($res);
			case 'mysqli':
				return @mysqli_fetch_row($res);
		}
	}

	function db_num_fields($res){
		if(!$res || !($res instanceof mysqli_result)) return 0;
		switch(DATABASE){
			case 'mysql':
				return @mysql_num_fields($res);
			case 'mysqli':
				return @mysqli_num_fields($res);
		}
	}

	function db_num_rows($res){
		if(!$res || !($res instanceof mysqli_result)) return 0;
		switch(DATABASE){
			case 'mysql':
				return @mysql_num_rows($res);
			case 'mysqli':
				return @mysqli_num_rows($res);
		}
	}

	function db_affected_rows($link = NULL){
		if(!$link) $link = db_link();
		if(!$link) return 0;
		switch(DATABASE){
			case 'mysql':
				return @mysql_affected_rows($link);
			case 'mysqli':
				if($link instanceof mysqli) return @mysqli_affected_rows($link);
				return 0;
		}
	}

	function db_query($query, $link = NULL){
		if(!$link) $link = db_link();
		if(!$link) return false;
		switch(DATABASE){
			case 'mysql':
				return @mysql_query($query, $link);
			case 'mysqli':
				if($link instanceof mysqli) return @mysqli_query($link, $query);
				return false;
		}
	}

	function db_insert_id($link = NULL){
		if(!$link) $link = db_link();
		if(!$link) return 0;
		switch(DATABASE){
			case 'mysql':
				return @mysql_insert_id($link);
			case 'mysqli':
				if($link instanceof mysqli) return @mysqli_insert_id($link);
				return 0;
		}
	}

	function db_field_name($res, $field_offset){
		if(!$res || !($res instanceof mysqli_result)) return false;
		switch(DATABASE){
			case 'mysql':
				return @mysql_field_name($res, $field_offset);
			case 'mysqli':
				$fo = @mysqli_fetch_field_direct($res, $field_offset);
				if($fo === false) return false;
				return $fo->name;
		}
	}

	function db_field_type($res, $field_offset){
		if(!$res || !($res instanceof mysqli_result)) return false;
		switch(DATABASE){
			case 'mysql':
				return @mysql_field_type($res, $field_offset);
			case 'mysqli':
				$fo = @mysqli_fetch_field_direct($res, $field_offset);
				if($fo === false) return false;
				return $fo->type;
		}
	}

	function db_escape($str = NULL, $link = NULL){
		if($str === NULL) $str = '';
		if(!$link) $link = db_link();
		if(!$link) return addslashes((string)$str);
		switch(DATABASE){
			case 'mysql':
				if(function_exists('mysql_real_escape_string')) return @mysql_real_escape_string((string)$str, $link);
				return @mysql_escape_string((string)$str);
			case 'mysqli':
				if($link instanceof mysqli) return @mysqli_real_escape_string($link, (string)$str);
				return addslashes((string)$str);
		}
	}

		function db_connect($host = NULL, $username = NULL, $passwd = NULL, $dbname = NULL, $port = NULL, $socket = NULL){
		if(function_exists('mysqli_report')){ @mysqli_report(MYSQLI_REPORT_OFF); }

		// 1. Resolve host and port from environment or parameters
		if($host === NULL || $host === ''){
			$env_server = getenv('DB_SERVER');
			if($env_server !== false && $env_server !== '') {
				$host = $env_server;
			} elseif(isset($_ENV['DB_SERVER']) && $_ENV['DB_SERVER'] !== '') {
				$host = $_ENV['DB_SERVER'];
			} else {
				$host = 'mysql-32d19a6e-abadhbihari-33eb.f.aivencloud.com';
			}
		}

		// Handle host with embedded port (e.g., mysql-host.aivencloud.com:26284)
		if(strpos($host, ':') !== false){
			list($parsed_host, $parsed_port) = explode(':', $host, 2);
			$host = $parsed_host;
			if(is_numeric($parsed_port)){
				$port = (int)$parsed_port;
			}
		}

		if($port === NULL || $port === '' || $port === 0){
			$env_port = getenv('DB_PORT');
			if($env_port !== false && $env_port !== '') {
				$port = (int)$env_port;
			} elseif(isset($_ENV['DB_PORT']) && $_ENV['DB_PORT'] !== '') {
				$port = (int)$_ENV['DB_PORT'];
			} else {
				$port = 12893;
			}
		} else {
			$port = (int)$port;
		}

		if($username === NULL){
			$env_user = getenv('DB_USERNAME');
			if($env_user !== false && $env_user !== '') {
				$username = $env_user;
			} elseif(isset($_ENV['DB_USERNAME']) && $_ENV['DB_USERNAME'] !== '') {
				$username = $_ENV['DB_USERNAME'];
			} else {
				$username = 'avnadmin';
			}
		}

		if($passwd === NULL || $passwd === ''){
			$env_pass = getenv('DB_PASSWORD');
			if($env_pass !== false && $env_pass !== '') {
				$passwd = $env_pass;
			} elseif(isset($_ENV['DB_PASSWORD']) && $_ENV['DB_PASSWORD'] !== '') {
				$passwd = $_ENV['DB_PASSWORD'];
			} else {
				$passwd = base64_decode('QVZOU19IX0dqY3JoX1c0WmJURUZsMXVJ');
			}
		}

		if($dbname === NULL || $dbname === ''){
			$env_db = getenv('DB_NAME');
			if($env_db !== false && $env_db !== '') {
				$dbname = $env_db;
			} elseif(isset($_ENV['DB_NAME']) && $_ENV['DB_NAME'] !== '') {
				$dbname = $_ENV['DB_NAME'];
			} else {
				$dbname = 'school_attendance';
			}
		}

		// Check SSL configuration (Default to true for remote/cloud hosts like Aiven)
		$use_ssl = true;
		$env_ssl = getenv('DB_SSL');
		if($env_ssl === false && isset($_ENV['DB_SSL'])) $env_ssl = $_ENV['DB_SSL'];
		if($env_ssl !== false && (strtolower($env_ssl) === 'false' || $env_ssl === '0' || strtolower($env_ssl) === 'off')) {
			$use_ssl = false;
		}

		try {
			switch(DATABASE){
				case 'mysql':
					$link = @mysql_connect(($port && $port != 3306 ? "$host:$port" : $host), $username, $passwd);
					if(!$link) return false;
					db_link($link);
					@mysql_query("SET NAMES '" . mysql_charset . "'");
					if($dbname){ if(!@mysql_select_db($dbname, $link)) return false; }
					return $link;

				case 'mysqli':
					$link = @mysqli_init();
					if(!$link) return false;

					if(defined('MYSQLI_OPT_CONNECT_TIMEOUT')){
						@mysqli_options($link, MYSQLI_OPT_CONNECT_TIMEOUT, 15);
					}

					$connected = false;

					// Try connecting with SSL flag (Required by Aiven Cloud MySQL)
					if($use_ssl && defined('MYSQLI_CLIENT_SSL')){
						$connected = @mysqli_real_connect($link, $host, $username, $passwd, $dbname, $port, $socket, MYSQLI_CLIENT_SSL);
						if(!$connected){
							// Fallback without SSL if target is local non-SSL MySQL
							$connected = @mysqli_real_connect($link, $host, $username, $passwd, $dbname, $port, $socket);
						}
					} else {
						$connected = @mysqli_real_connect($link, $host, $username, $passwd, $dbname, $port, $socket);
					}

					if(!$connected) return false;

					db_link($link);
					@mysqli_set_charset($link, mysql_charset);
					return $link;
			}
		} catch (Throwable $e) {
			return false;
		}
	}


	function db_errno($link = NULL, $mysqli_connect = false){
		if(!$link) $link = db_link();
		switch(DATABASE){
			case 'mysql':
				return @mysql_errno($link);
			case 'mysqli':
				if($mysqli_connect) return @mysqli_connect_errno();
				if($link instanceof mysqli) return @mysqli_errno($link);
				return @mysqli_connect_errno();
		}
	}

	function db_error($link = NULL, $mysqli_connect = false){
		if(!$link) $link = db_link();
		switch(DATABASE){
			case 'mysql':
				return @mysql_error($link);
			case 'mysqli':
				if($mysqli_connect) return @mysqli_connect_error();
				if($link instanceof mysqli) return @mysqli_error($link);
				return @mysqli_connect_error();
		}
	}
