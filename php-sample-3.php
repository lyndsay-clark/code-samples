<?php namespace Course;

include_once $_SERVER['DOCUMENT_ROOT'] . "/_includes/bootstrap.php";

abstract class CourseHierarchyObject {
	protected function init($className, $nameInDB, $id = NULL, $dbName = DB_NAME) {
		if(is_array($id)) {
			$id = dbInsert($nameInDB, $id, $dbName);
		}

		if($id !== NULL) {
			$db = new \DBIO($nameInDB, "id", $id, $dbName);
			$db->assertCount(1);

			$this->_dbObjects []= $db;
			$this->_className = $className;
			$this->_nameInDB = $nameInDB;
			$this->_id = $id;
		}	
	}

	public function attach($in) {
		$this->assertCount(1);

		if(is_array($in)) {
			$arr = $in;
		} else {
			$arr = Array($in);
		}

		foreach($arr as $allowed) {
			if(!in_array($allowed->_nameInDB, $this->_allowedChildren)) {
				$this->_error = eh(
					E, INVALID_COURSE_HIERARCHY_CHILD, 
					__FILE__, __LINE__
				);

				$this->_error = $allowed->_nameInDB . " NOT " . print_r($this->_allowedChildren, true) . " NEXT ";

				return false;
			}
		}

		foreach($arr as &$newChild) {
			$newChild->db()->set("parent", $this->db()->get("id"));
			$newChild->db()->save();

			$this->_children[$newChild->_nameInDB] []= $newChild;

			$childIDs = unserialize($this->db()->get("child_" . $newChild->_nameInDB));
			$childIDs []= $newChild->db()->get("id");

			$childIDs = array_filter($childIDs);
			$this->db()->set("child_" . $newChild->_nameInDB, serialize($childIDs));
		}

		$this->db()->save();

		return true;
	}

	public function warning() {
		return $this->_warning;
	}

	public function all($which = NULL) {
		$ret = Array();
		foreach($this->_dbObjects as $db) {
			$obj = new $this->_className();
			$obj->_dbObjects = [$db]; 
			$obj->_className = $this->_className;
			$ret []= $obj;
		}

		return $ret;
	}

	public function assertCount($count) {
		if($this->count() != $count) {
			meh("CourseHierarchyObject has count of " . $this->count() . ", " . $count . " asserted.", MILD);
		}
	}

	public function ids($which = NULL) {
		$ret = Array();
		foreach($this->_dbObjects as $db) {
			$ret []= $db->get("id");
		}

		return $ret;
	}

	public function count() {
		return count($this->_dbObjects);
	}

	protected function child($className, $colName, $which = NULL) {
		if(empty($this->_children[$colName])) {
			$this->_children[$colName] = Array();
			foreach($this->_dbObjects as $db) {
				if(empty($db->get("child_" . $colName))) {
					continue;
				}

				foreach(unserialize($db->get("child_" . $colName)) as $childID) {
					$this->_children[$colName] []= new $className($childID);
				}	

				$this->_children[$colName] = array_filter($this->_children[$colName]);
			}
		}

		if($which !== NULL && isset($this->_children[$colName][$which])) 
			return $this->_children[$colName][$which];
		else {
			$dbs = Array();
			foreach($this->_children[$colName] as $child) {
				$dbs []= $child->db();
			}

			$super = new $className();
			$super->_dbObjects = $dbs;
			$super->_className = $className;

			return $super;
		}
				
	}

	public function db() {
		if(count($this->_dbObjects) == 1) 
			return $this->_dbObjects[0];
		elseif(count($this->_dbObjects) == 0)
			return new EmptyDBCatcher();
		else {
			meh("Cannot access DB Object on super course. Use ->all() first.", CRITICAL);
		}
	}

	public function className() {
		return $this->_className;
	}
	
	private $_dbObjects = Array();
	private $_error = NULL;
	private $_children = Array(Array());
	private $_className;
	private $_nameInDB;
	private $_id;
}

?>