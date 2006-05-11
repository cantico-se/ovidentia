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


class bab_registry {

	var $dir = '/';

	function bab_registry() {
		$this->db = &$GLOBALS['babDB'];
	}

	/**
	 * Set a directory into the registry, others functions depends on this directory
	 * @param string $dir
	 */
	function changeDirectory($dir) {
		if ('/' === substr($dir,0,1)) {
			$this->dir = $dir;
		} else {
			$this->dir .= $dir;
		}

		if ('/' === substr($dir,-1,0)) {
			$this->dir .= '/';
		}
	}

	/**
	 * Insert or Update a value with a key parameter
	 * The key will be inserted into the current directory
	 * 0 : the function has done nothing
	 * 1 : the value has been updated
	 * 2 : the value has been inserted
	 *
	 * @param string $key
	 * @param mixed $value
	 * @see bab_registry::changeDirectory()
	 * @return 0|1|2
	 */
	function setKeyValue($key, $value) {
		$dirkey = $this->dir.$key;

		$value_type = gettype($value);

		switch($value_type) {

			case 'boolean':
				$value = $value ? 1 : 0;
				break;

			case 'array':
			case 'object':
				$value = serialize($value);
				break;
		}

		$res = $this->db->db_query("SELECT COUNT(*) FROM ".BAB_REGISTRY_TBL." WHERE dirkey='".$this->db->db_escape_string($dirkey)."'");

		list($n) = $this->db->db_fetch_array($res);

		if ($n > 0) {

			$res = $this->db->db_query("
			
			UPDATE ".BAB_REGISTRY_TBL." 
				SET
					value			= '".$this->db->db_escape_string($value)."', 
					value_type		= '".$this->db->db_escape_string($value_type)."', 
					update_id_user	= '".$this->db->db_escape_string($GLOBALS['BAB_SESS_USERID'])."', 
					lastupdate		= NOW() 
				WHERE 
					dirkey			= '".$this->db->db_escape_string($dirkey)."'
			");

			if (0 < $this->db->db_affected_rows($res)) {
				return 1;
			}

		} else {

			$this->db->db_query("
			
			INSERT INTO ".BAB_REGISTRY_TBL." 
				(
					dirkey, 
					value, 
					value_type, 
					create_id_user, 
					update_id_user, 
					createdate, 
					lastupdate
				) 
			VALUES 
				(
					'".$this->db->db_escape_string($dirkey)."',
					'".$this->db->db_escape_string($value)."',
					'".$this->db->db_escape_string($value_type)."',
					'".$this->db->db_escape_string($GLOBALS['BAB_SESS_USERID'])."',
					'".$this->db->db_escape_string($GLOBALS['BAB_SESS_USERID'])."',
					NOW(),
					NOW()
				)
			");

			return 2;

		}

		return 0;
	}

	/**
	 * Remove the key/value pair from the registry
	 * @param string $key
	 * @return boolean
	 * @see bab_registry::changeDirectory()
	 */
	function removeKey($key) {

		$dirkey = $this->dir.$key;
		$res = $this->db->db_query("DELETE FROM ".BAB_REGISTRY_TBL." WHERE dirkey = '".$this->db->db_escape_string($dirkey)."'");

		return 0 < $this->db->db_affected_rows($res);
	}

	
	/**
	 * Get a value
	 * @param string $key
	 * @return mixed|null
	 */
	function getValue($key) {
		
		if ($arr = $this->getValueEx($key)) {
			return $arr['value'];
		}
		return null;
	}


	/**
	 * Get a value with additionnal parameters
	 * @param string $key
	 * @return array|null
	 */
	function getValueEx($key) {
		$dirkey = $this->dir.$key;
		$res = $this->db->db_query("
			SELECT 
				value,
				value_type,
				create_id_user,
				update_id_user,
				UNIX_TIMESTAMP(createdate) createdate,
				UNIX_TIMESTAMP(lastupdate) lastupdate 
			FROM ".BAB_REGISTRY_TBL." 
			WHERE 
				dirkey = '".$this->db->db_escape_string($dirkey)."'
		");

		if ($arr = $this->db->db_fetch_assoc($res)) {

			switch($arr['value_type']) {
				
				case 'boolean':
					$arr['value'] = $arr['value'] ? true : false;
					break;
				
				case 'object':
				case 'array':
					$arr['value'] = unserialize($arr['value']);
					break;

				default:
					settype($arr['value'], $arr['value_type']);

			}

			return array(
				'value' => $arr['value'],
				'create_id_user' => (int) $arr['create_id_user'],
				'update_id_user' => (int) $arr['update_id_user'],
				'createdate' => (int) $arr['createdate'],
				'lastupdate' => (int) $arr['lastupdate'],
			);
		}

		return null;
	}	
}


?>