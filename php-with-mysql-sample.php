<?php

	const DB_NAME = "test_name";
	const DISC_DB = "test_disc_db";
	const COURSE_DB = "test_course_db";
	const ERROR_DB = "error_log_db";
	const STATS_DB = "statistics_db";
	const ENCRYPT_KEY = "securePassword";

	define("OVERRIDE_MULTIPLE", 	0x00000100);
	define("SUPPRESS_FETCH", 		0x00000101);
	define("MULTIPLE_AND",			0x00000102);
	define("MULTIPLE_OR",			0x00000103);
	
	function db_connect($db) {
		$connection = mysqli_connect('127.0.0.1', 'user', 'password', $db);
		if ($connection->connect_error) {
			meh("Failed to connect to database: " . $db, CRITICAL);
		}
		else
			return $connection;
	}
	
	function strToSlug($str) {
		return strtolower(preg_replace("/[\s]/", "-", $str));
	}
	
	function updateParentStr($id, $arr, $str) {
		$arrSearch = array_search($id, $arr);
		if($arrSearch === 0) $arrSearch++;
			$arr []= $id;
		
		return serialize($arr);
	}

	function dbInsert($table, $data, $dbName = DB_NAME, &$error = NULL) {
		//INSERT INTO lessons (slug, author, title, category, content, course) VALUES ('$slug', '$author', '$title', '$category', '$content', '$classID')

		if ($db = db_connect($dbName)) {
			$keys = "";
			$values = "";

			foreach($data as $key => $value) {
				$keys .= $key . ", ";
				$values .= "'" . $value . "', ";
			}
			$keys = trim($keys, ", ");
			$values = trim($values, ", ");

			$query = sprintf("INSERT INTO %s (%s) VALUES (%s)", $table, $keys, $values);

			$res = $db->query($query);
			if(!$res) {
				eh(GENERIC_ERROR_MSG);
			}

			if($db->error) {
				$error = $db->error;
				meh($error, MILD);
				return -1;
			} else
				return $db->insert_id;
		} else {
			eh(GENERIC_ERROR_MSG);
		}
	}

	class DBIO {
		public function __construct($table = NULL, $anchorColumn = NULL, $anchorVal = NULL, $dbName = DB_NAME, $suppressFetch = 0) {
			$this->_isCustomSelect = $table == NULL && $anchorColumn == NULL && $anchorVal == NULL;
			if(!$this->_isCustomSelect) {
				$this->_table = $table;
				$this->_anchorColumn = $anchorColumn;
				$this->_anchorVal = $anchorVal;

				$this->_db = db_connect($dbName);

				$this->_suppressFetch = $suppressFetch;
				if($this->_suppressFetch != SUPPRESS_FETCH) {
					$this->_stmt_pull = $this->_db->prepare("SELECT * FROM " . $this->_table . " WHERE " . $this->_anchorColumn . " LIKE ?");
					if($this->_stmt_pull) {
						$this->_stmt_pull->bind_param("s", $this->_anchorVal);
						$this->fetch();
					}
				} 
			}
		}

		public function customSelect($dbName, $prep, $inputs = array()) {
			$this->_db = db_connect($dbName);
			$this->_stmt_pull = $this->_db->prepare($prep);

			if($this->_stmt_pull && count($inputs) > 0) {
				$values = Array();
				$i = 0;
				foreach($inputs as $in) {
					$values []= &$inputs[$i];
					$i++;
				}

				$args = array_merge(Array(str_repeat('s', count($inputs))), array_values($values));

				set_error_handler('handleError');
				call_user_func_array(Array(&$this->_stmt_pull, "bind_param"), $args);
				retore_error_handler();
			}
		}

		public function count() {
			return $this->_count;
		}


		public function assertCount($count) {
			if($this->count() != $count) {
				meh("DBIO Object has count of " . $this->count() . ", " . $count . " asserted.", MILD);
			}
		}

		public function save() {
			if($this->_db) {
				$this->_push();
			} else {
				meh("Database not initialized.", SEVERE);		
				return false;	
			}
			return true;		
		}

		public function fetch() {
			if($this->_db) {
				$this->_pull();
			} else {
				meh("Database not initialized.", SEVERE);		
				return false;	
			}
			return $this->dump();		
		}

		public function dump() {
			return $this->_attributes;
		}

		public function get($col = NULL, $row = 0) {
			if($this->_suppressFetch == SUPPRESS_FETCH) {
				/* eh */
			}
			return isset($this->_attributes[$row][$col]) ? $this->_attributes[$row][$col] : NULL;
		}

		public function set($col, $val, $override = NULL) {
			if($override != OVERRIDE_MULTIPLE && count($this->_attributes) != 1) {
				eh(GENERIC_ERROR_MSG);
				return false;
			}
			foreach($this->_attributes as &$row) {
				if(array_key_exists($col, $row) || $this->_suppressFetch == SUPPRESS_FETCH) {
					$row[$col] = $val;
					if(!in_array($col, $this->_edited))
						$this->_edited []= $col;
				} else {
					eh(GENERIC_ERROR_MSG);
					return false;
				}
			}

			return true;
		}

		private function _pull() {
			if($this->_stmt_pull) {		
				$this->_stmt_pull->execute();		
				$res = $this->_stmt_pull->get_result();

				$i = 0;
				while($row = $res->fetch_assoc()) {
					foreach($row as $key => $val) {
						$this->_attributes[$i][$key] = $val;
					}
					$i++;
				}
				
				$this->_count = $i;
			}
			
			$this->error = $this->_db->error;
		}

		private function _push() {
			$values = "";
			foreach($this->_edited as $key) {
				$values .= $key . "='" . $this->_attributes[0][$key] . "', ";
			}
			if($values == "") {
				eh(GENERIC_ERROR_MSG);

				return false;
			} 
			$values = trim($values, ", ");

			$qry = sprintf("UPDATE %s SET %s WHERE %s='%s'", $this->_table, $values, $this->_anchorColumn, $this->_anchorVal);

			$res = $this->_db->query($qry);
			
			$this->error = $this->_db->error;
			
			if(!$res) {
				eh(GENERIC_ERROR_MSG);

				return false;
			}

			return true;
		}

		private $_table;
		private $_anchorColumn;
		private $_anchorVal;
		
		public $error = false;
		
		private $_count = 0;

		private $_stmt_pull;
		private $_stmt_push;
		
		private $_suppressFetch;
		private $_isCustomSelect;
		
		private $_edited = Array();

		private $_db;

		private $_attributes = Array(Array());
	}

?>
