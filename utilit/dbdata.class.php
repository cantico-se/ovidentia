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
		$this->row[$key];
	}
	
	
	/**
	 * Get the value from row
	 * @param	string	$key
	 * @return	mixed
	 */
	function getValue($key) {
		return isset($this->row[$key]) : $this->row[$key] : '';
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
			
			$row = $babDB->db_fetch_assoc($res);
			$this->setRow($row);
			return $row;
		}
		
		return false;
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
	 * @return boolean|int
	 */
	function insertDbRow() {
		
		global $babDB;
		
		if ($this->row) {
			$row = $this->row;
		
			// remove auto incremented collums
			if (isset($this->primaryautoincremented)) {
				unset($row[$this->primaryautoincremented]);
			}
			
			$keys = $array();
			foreach($row as $key => $value) {
				$keys[] = $babDB->backTick($key);
			}
			
			$babDB->db_query('
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
	 * 
	 * @return boolean
	 */
	function updateDbRow() {
	
		global $babDB;
		
		$row = $this->row;
		
		// remove auto incremented collums
		if (isset($this->primaryautoincremented)) {
			unset($row[$this->primaryautoincremented]);
		}
		
		$keys = $array();
		foreach($row as $key => $value) {
			$keys[] = $babDB->backTick($key).' = '.$babDB->quote($value);
		}
		
		$id = $this->getPrimaryAutoIncremented();
		
		if ($id) {
			$babDB->db_query('
				UPDATE '.$babDB->backTick($this->tablename).' 
				SET '.implode(',',$keys).' 
				WHERE '.$babDB->backTick($this->primaryautoincremented).' = '.$babDB->quote($id).'
			');
			
			return true;
		}
		
		return false;
	}
	
	
	/**
	 * Count rows into table with same values as $this->row
	 * @return 	int
	 */
	function countDbRows() {
		global $babDB;
		

		$keys = $array();
		foreach($this->row as $key => $value) {
			$keys[] = $babDB->backTick($key).' = '.$babDB->quote($value);
		}
		
		$res = $babDB->db_query('
			SELECT COUNT(*) FROM '.$babDB->backTick($this->tablename).' 
			WHERE '.implode(',',$keys).' 
		');
		
		if ($res) {
			return $babDB->db_num_rows($res);
		}
		
		return false;
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
}

?>