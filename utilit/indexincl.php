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



define("BAB_INDEX_STATUS_NOINDEX"	, 0);
define("BAB_INDEX_STATUS_INDEXED"	, 2);
define("BAB_INDEX_STATUS_TOINDEX"	, 3);

define("BAB_INDEX_WAITING"			, 1);
define("BAB_INDEX_ALL"				, 2);



class bab_indexObject {

	var $enabled;
	var $engineName;

	function bab_indexObject($object) {

		$arr = bab_searchEngineInfos();

		if ($arr) {
			$this->enabled = true;
			$this->engineName = $arr['name'];

			$this->object = $object;
			return true;
		} 
		$this->enabled = false;
		return false;
	}


	/**
	 * get status for new uploaded files for a index file
	 * this function take care of disabled status, on upload status, ...
	 * @param int $id_indexFile
	 * @return boolean 
	 */
	function get_onLoadStatus() {

		if (false === $this->enabled) {
			return BAB_INDEX_STATUS_NOINDEX;
		}

		$db = $GLOBALS['babDB'];
		list($index_onload) = $db->db_fetch_array($db->db_query("
		
			SELECT index_onload 
				FROM ".BAB_INDEX_FILES_TBL." 
			WHERE 
				object='".$this->object."' 
				AND index_disabled='0' 
				AND index_onload='1'
		"));

		if ($index_onload) {
			return BAB_INDEX_STATUS_INDEXED;
		} else {
			return BAB_INDEX_STATUS_TOINDEX;
		}
	}

	
	/**
	 * get index objects for the current addon
	 * @return array 
	 */
	function get_indexObjects() {

		$db = $GLOBALS['babDB'];
		$return = array();
		$res = $db->db_query("
		
			SELECT 
				i.name, i.object  
			FROM 
				".BAB_INDEX_FILES_TBL." i,
				".BAB_ADDONS." a
			WHERE 
				i.id_addon=a.id 
				AND a.title='".$db->db_escape_string($GLOBALS['babAddonFolder'])."'
		");

		while ($arr = $db->db_fetch_assoc($res)) {
			$return[$arr['object']] = $arr['name'];
		}

		return $return;
	}

	/**
	 * Add file into current index for the object
	 */
	function addFileToIndex($file) {

		if (false === $this->enabled) {
			return BAB_INDEX_STATUS_NOINDEX;
		}

		switch($this->engineName) {
			case 'swish':
				include_once $GLOBALS['babInstallPath'].'utilit/searchincl.swish.php';
				break;
		}

		$obj = new bab_indexFilesCls( array($file), $object);
		return $obj->addFilesToIndex();
	}
}


/**
 * Call this function when a file is loaded, 
 * the file will be indexed if necessary
 * 
 * @param string $file full path to the file, usually in upload directory
 * @param string $object if not given, the current addon name will be used
 * @return int
 */
function bab_indexOnLoadFile($file, $object) {
	
	$obj = new bab_indexObject($object);
	$status = $obj->get_onLoadStatus();

	if (BAB_INDEX_STATUS_INDEXED === $status) {
		if (false !== $obj->addFileToIndex($file)) {
			return BAB_INDEX_STATUS_INDEXED;
		} else {
			return BAB_INDEX_STATUS_NOINDEX;
		}
	}

	return $status;
}


/**
 * List of available index files
 */
function bab_searchEngineIndexes() {

	$db = &$GLOBALS['babDB'];
	$return = array();

	$res = $db->db_query("SELECT * FROM ".BAB_INDEX_FILES_TBL."");
	while ($arr = $db->db_fetch_assoc($res)) {
		$return[$arr['object']] = array(
				'name' => $arr['name'],
				'index_onload' => 1 == $arr['index_onload'], 
				'index_disabled' => 1 == $arr['index_disabled']
			);
	}

	return $return;
}



/**
 * return informations on the current index engine
 */
function bab_searchEngineInfosObj($engine) {

	switch($engine) {
			case 'swish':
				include_once $GLOBALS['babInstallPath'].'utilit/searchincl.swish.php';
				break;
		}

	return new searchEngineInfosObjCls();
}

?>