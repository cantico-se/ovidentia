<?php
/************************************************************************
 * OVIDENTIA http://www.ovidentia.org                                   *
 ************************************************************************
 * Copyright (c) 2003 by CANTICO ( http://www.cantico.fr )              *
 *                                                                      *
 * This file is part of Ovidentia.                                      *
 *                                                                      *
 * Ovidentia is free software; you can redistribute it and/or modify    *
 * it under the terms of the GNU General Public License as published by *
 * the Free Software Foundation; either version 2, or (at your option)  *
 * any later version.													*
 *																		*
 * This program is distributed in the hope that it will be useful, but  *
 * WITHOUT ANY WARRANTY; without even the implied warranty of			*
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.					*
 * See the  GNU General Public License for more details.				*
 *																		*
 * You should have received a copy of the GNU General Public License	*
 * along with this program; if not, write to the Free Software			*
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307,*
 * USA.																	*
************************************************************************/
include_once "base.php";


/**
 * Manage a row from mysql table 
 * work with associative array, result from babDB::db_fetch_assoc()
 * Used by addons
 *
 * @since 6.4.94
 */
class bab_dbdata {

	var $row = array();
	var $tablename;
	var $primaryautoincremented;
	
	
	/**
	 * @param array
	 */
	function setRow($row) {
		$this->row = $row;
	}

	/**
	 * @return array
	 */
	function getRow() {
		return $this->row;
	}
	
	/**
	 * Get the value from row
	 * @param	string	$key
	 * @param	mixed	$value
	 */
	function setValue($key, $value) {
		$this->row[$key] = $value;
	}
	
	
	/**
	 * Get the value from row
	 * @param	string	$key
	 * @return	mixed
	 */
	function getValue($key) {
		return array_key_exists($key, $this->row) ? $this->row[$key] : '';
	}
	
	/**
	 * Get the DbRow from table with autoincremented value as reference
	 * set as row for other access
	 * @return	array|false
	 */
	function getDbRow() {
		global $babDB;

		$id = $this->getPrimaryAutoIncremented();
		
		if ($id) {
			$res = $babDB->db_query('
				SELECT * FROM '.$babDB->backTick($this->tablename).' 
				WHERE '.$babDB->backTick($this->primaryautoincremented).' = '.$babDB->quote($id).'
			');
			
			if ($row = $babDB->db_fetch_assoc($res)) {
				$this->setRow($row);
			} else {
				$this->setRow(array());
			}
			return $row;
		}
		
		return false;
	}
	
	/**
	 * Get the DbRow from table with a specified keys as reference
	 * @param	array	$keys
	 */
	function getDbRowByKeys($keys) {
		global $babDB;
		$where = array();
		foreach($keys as $key) {
			$where[] = $babDB->backTick($key).' = '.$babDB->quote($this->getValue($key));
		}

		$res = $babDB->db_query('
			SELECT * FROM '.$babDB->backTick($this->tablename).' 
			WHERE '.implode(' AND ',$where).'
		');
		
		if ($row = $babDB->db_fetch_assoc($res)) {
			$this->setRow($row);
		} else {
			$this->setRow(array());
		}
		return $row;
	}
	
	
	/**
	 * Get the value from table with autoincremented value as reference
	 * @param	string	$key
	 * @return	string
	 */
	function getDbValue($key) {
		$arr = $this->getDbRow();
		return $arr[$key];
	}
	
	/**
	 * Set the name of the table
	 * @param	string	$key
	 */
	function setTableName($tablename) {
		$this->tablename = $tablename;
	}
	
	/**
	 * Get the table name
	 * @return 	false|string
	 */
	function getTableName() {
		if (!isset($this->tablename)) {
			return false;
		}
		return $this->tablename;
	}
	
	/**
	 * Set the name of the primary key
	 * @param	string	$key
	 */
	function setPrimaryAutoIncremented($key) {
		$this->primaryautoincremented = $key;
	}
	
	/**
	 * Get the auto incremented value
	 * @return 	false|int
	 */
	function getPrimaryAutoIncremented() {
	
		if (!isset($this->row[$this->primaryautoincremented])) {
			return false;
		}
	
		return (int) $this->row[$this->primaryautoincremented];
	}
	
	/**
	 * Insert Row into table
	 * @param	string	[$method]
	 * @return boolean|int
	 */
	function insertDbRow($method = 'db_query') {
		
		global $babDB;
		
		if ($this->row) {
			$row = $this->row;
		
			// remove auto incremented collums
			if (isset($this->primaryautoincremented)) {
				unset($row[$this->primaryautoincremented]);
			}
			
			$keys = array();
			foreach($row as $key => $value) {
				$keys[] = $babDB->backTick($key);
			}
			
			$babDB->$method('
				INSERT INTO '.$babDB->backTick($this->tablename).' 
				('.implode(', ',$keys).') 
				VALUES 
				('.$babDB->quote($row).') 
			');
			
			if (isset($this->primaryautoincremented)) {
				return $babDB->db_insert_id();
			} else {
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * Update row into table
	 * @param	string	[$method]
	 * @return boolean
	 */
	function updateDbRow($method = 'db_query') {
	
		global $babDB;
		
		$row = $this->row;
		// remove auto incremented collums
		if (isset($this->primaryautoincremented)) {
			unset($row[$this->primaryautoincremented]);
		}
		
		$id = $this->getPrimaryAutoIncremented();
		
		$keys = array();
		foreach($row as $key => $value) {
			$keys[] = $babDB->backTick($key).' = '.$babDB->quote($value);
		}

		if ($id) {
			$babDB->$method('
				UPDATE '.$babDB->backTick($this->tablename).' 
				SET '.implode(',',$keys).' 
				WHERE '.$babDB->backTick($this->primaryautoincremented).' = '.$babDB->quote($id).'
			');
			
			return true;
		}
		
		return false;
	}
	
	
	/**
	 * Update row into table
	 * @param	string	$ikey
	 * @return boolean
	 */
	function updateDbRowByKey($ikey) {
	
		global $babDB;
		
		$row = $this->row;
		unset($row[$ikey]);
		$id = $this->getValue($ikey);

		$keys = array();
		foreach($row as $key => $value) {
			$keys[] = $babDB->backTick($key).' = '.$babDB->quote($value);
		}

		if ($id) {
			$babDB->db_query('
				UPDATE '.$babDB->backTick($this->tablename).' 
				SET '.implode(',',$keys).' 
				WHERE '.$babDB->backTick($ikey).' = '.$babDB->quote($id).'
			');
			
			return true;
		}
		
		return false;
	}
	
	
	/**
	 * Count rows into table with same values as $this->row
	 * if the filter parameter is used, only keys defined as key in the filter array will be used in were clause
	 * @since 6.5.1
	 * @param	array|false		[$filter]	the keys of the array are collumn names
	 * @return 	int
	 */
	function countDbRows($filter = false) {
		global $babDB;
		

		$keys = array();
		foreach($this->row as $key => $value) {
			
			if (false === $filter || isset($filter[$key])) {
				$keys[] = $babDB->backTick($key).' = '.$babDB->quote($value);
			}
		}
		
		$res = $babDB->db_query('
			SELECT COUNT(*) FROM '.$babDB->backTick($this->tablename).' 
			WHERE '.implode(' AND ',$keys).' 
		');
		
		if ($res) {
			$arr = $babDB->db_fetch_array($res);
			return (int) $arr[0];
		}
		
		return 0;
	}
	
	
	
	/**
	 * Count rows into table with same values as $this->row
	 * the autoincremented collumn value will be used to ignore le current row 
	 * if the filter parameter is used, only keys defined as key in the filter array will be used in were clause
	 * @since 6.6.94
	 * @param	array|false		[$filter]	the keys of the array are collumn names
	 * @return 	int
	 */
	function countDuplicates($filter = false) {
	
		global $babDB;
		

		$keys = array();
		foreach($this->row as $key => $value) {
			
			if (false === $filter || isset($filter[$key])) {
				$keys[] = $babDB->backTick($key).' = '.$babDB->quote($value);
			}
		}
		
		$req = '
			SELECT COUNT(*) FROM '.$babDB->backTick($this->tablename).' 
			WHERE '.implode(' AND ',$keys).' 
		';
		
		$id = $this->getPrimaryAutoIncremented();
		if (!empty($id)) {
			$req .= ' AND '.$babDB->backTick($this->primaryautoincremented).' <> '.$babDB->quote($id);
		}
		
		$res = $babDB->db_query($req);
		
		if ($res) {
			$arr = $babDB->db_fetch_array($res);
			return (int) $arr[0];
		}
		
		return 0;
	}
	
	
	
	
	
	/**
	 * Create row with defaut table data
	 */
	function setRowDefault() {
		global $babDB;
		
		$this->row = array();

		$res = $babDB->db_query('DESCRIBE '.$babDB->backTick($this->tablename));
		while ($arr = $babDB->db_fetch_assoc($res)) {
			$this->row[$arr['Field']] = $arr['Default'];
		}
		
		return $this->row;
	}
	
	/**
	 * set row and verify with database structure
	 * @param	array	$row
	 */
	function setAndValidateRow($row) {
		$default = $this->setRowDefault();
		foreach($default as $key => $value) {
			if (isset($row[$key])) {
				$this->setValue($key, $row[$key]);
			} else {
				unset($this->row[$key]);
			}
		}
	}
	
	
	/**
	 * @since 6.6.0
	 * Delete row with autoincremented column
	 */
	function deleteDbRow() {
		global $babDB;
		
		$id = $this->getPrimaryAutoIncremented();
		
		if ($id) {
			$res = $babDB->db_query('DELETE FROM '.$babDB->backTick($this->tablename).' WHERE '.$babDB->backTick($this->primaryautoincremented).' = '.$babDB->quote($id).'');
			
			return 1 === $babDB->db_affected_rows($res);
		}
		
		return false;
	}
	
	/**
	 * @since 6.6.0
	 * @param	string	$ikey
	 */
	function deleteDbRowByKey($ikey) {
		global $babDB;
		
		$id = $this->getValue($ikey);
		if ($id) {
			$res = $babDB->db_query('DELETE FROM '.$babDB->backTick($this->tablename).' WHERE '.$babDB->backTick($ikey).' = '.$babDB->quote($id).'');
			
			return 1 === $babDB->db_affected_rows($res);
		}
		
		return false;
	}
}

?>