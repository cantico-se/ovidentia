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


define("BAB_INDEX_FREE"				, 1);
define("BAB_INDEX_PENDING"			, 2);
define("BAB_INDEX_RUNNING"			, 3);


class bab_indexReturn {

	var $result = NULL;			// public
	var $msgerror = array();	// private
	var $debuginfos = array();	// private
	var $infos = array();		// private

	function addError($msg) {
		$this->msgerror[] = $msg;
	}

	function getNextError() {
		if (list(,$e) = each($this->msgerror)) {
			return $e;
		}
		return false;
	}

	function addDebug($debuginfos) {
		// bab_debug($debuginfos);
		$this->debuginfos[] = $debuginfos;
	}

	function getNextDebug() {
		if (list(,$e) = each($this->debuginfos)) {
			return $e;
		}
		return false;
	}


	function addInfo($info) {
		$this->infos[] = $info;
	}

	function getNextInfo() {
		if (list(,$e) = each($this->infos)) {
			return $e;
		}
		return false;
	}

	function merge($r) {
		$this->result = false === $this->result ? false : $r->result;
		while ($msg = $r->getNextDebug()) {
			$this->addDebug($msg);
		}

		while ($msg = $r->getNextError()) {
			$this->addError($msg);
		}

		while ($msg = $r->getNextInfo()) {
			$this->addInfo($msg);
		}
	}
}



class bab_indexObject {

	var $disabled;
	var $engineName;

	function bab_indexObject($object) {

		$arr = bab_searchEngineInfos();

		if (false === $arr) {
			$this->disabled = true;
			return;
		}

		$this->disabled = $arr['indexes'][$object]['index_disabled'];
		$this->onload = $arr['indexes'][$object]['index_onload'];
		$this->engineName = $arr['name'];
		$this->label = bab_translate($arr['indexes'][$object]['name']);

		$this->object = $object;
		$this->db = &$GLOBALS['babDB'];
	}


	/**
	 * @private
	 * @param array $arr files
	 */
	function autorized_files_only(&$arr) {
		$tmp = bab_searchEngineInfos();
		if( is_array($tmp['types']))
		{
			$types = array_flip($tmp['types']);
			
			foreach($arr as $k => $file) {
				
				$t = bab_getFileMimeType($file);
				if (!isset($types[$t])) {
					unset($arr[$k]);
				}
			}
		}
	}

	/**
	 * get status for new uploaded files for a index file
	 * this function take care of disabled status, on upload status, ...
	 * @param int $id_indexFile
	 * @return integer 
	 */
	function get_onLoadStatus() {

		if ($this->disabled) {
			return BAB_INDEX_STATUS_NOINDEX;
		}
		if ($this->onload) {
			return BAB_INDEX_STATUS_INDEXED;
		} else {
			return BAB_INDEX_STATUS_TOINDEX;
		}
	}


	/**
	 * reset index with a new set of files
	 * @param array $files full path to the file
	 * @return object bab_indexReturn
	 */
	function resetIndex($files) {
		$this->autorized_files_only($files);
		$this->db->db_query("DELETE FROM ".BAB_INDEX_ACCESS_TBL." WHERE object = '".$this->db->db_escape_string($this->object)."'");

		if ($this->disabled) {
			$r = new bab_indexReturn;
			$r->addError(sprintf(bab_translate("This indexation is disabled : %s"),$this->label));
			$r->result = false;
			return $r;
		}

		if (0 === count($files)) {
			$r = new bab_indexReturn;
			$r->result = false;
			return $r;
		}

		switch($this->engineName) {
			case 'swish':
				include_once $GLOBALS['babInstallPath'].'utilit/searchincl.swish.php';
				break;
		}

		$obj = new bab_indexFilesCls( $files, $this->object);
		$r = $obj->indexFiles();
		$r->addInfo(sprintf(bab_translate("%s : All the files has been indexed (%u files)"),$this->label, count($files)));
		return $r;
	}



	/**
	 * Buid environement for indexation by command line
	 * Once the indexation is done, required file is included and the callback function is called
	 * Use the callback to set the flags coorectly in the database for the files
	 * The function_parameter is the only parameter given to the callback function
	 * this value will be serialized if necessary, so non serializable objects are forbidden
	 * @param array $files full path to the file
	 * @param string $require_once file to include
	 * @param string|array $function callback
	 * @param mixed $function_parameter
	 * @return object bab_indexReturn
	 */
	function prepareIndex($files, $require_once, $function, $function_parameter) {

		$this->autorized_files_only($files);
		
		if ($this->disabled) {
			$r = new bab_indexReturn;
			$r->addError(sprintf(bab_translate("This indexation is disabled : %s"),$this->label));
			$r->result = false;
			return $r;
		}

		if (0 === count($files)) {
			$r = new bab_indexReturn;
			$r->result = false;
			return $r;
		}

		switch($this->engineName) {
			case 'swish':
				include_once $GLOBALS['babInstallPath'].'utilit/searchincl.swish.php';
				break;
		}

		$obj = new bab_indexFilesCls( $files, $this->object);
		$r = $obj->prepareIndex($require_once, $function, $function_parameter);
		if (true === $r->result) {
			$r->addInfo(sprintf(bab_translate("%s : the indexation has been shudeled (%u files)"),$this->label, count($files)));
		}
		return $r;
	}


	/**
	 * Apply prepared index
	 * Does the pendings jobs initiated by the preparation step
	 * @see self::prepareIndex
	 * @return object bab_indexReturn
	 */
	function applyIndex() {
		if ($this->disabled) {
			$r = new bab_indexReturn;
			$r->addError(sprintf(bab_translate("This indexation is disabled : %s"),$this->label));
			$r->result = false;
			return $r;
		}

		switch($this->engineName) {
			case 'swish':
				include_once $GLOBALS['babInstallPath'].'utilit/searchincl.swish.php';
				break;
		}

		$obj = new bab_indexFilesCls( array(), $this->object);
		$r = $obj->checkTimeout();
		return $r;
	}

	
	/**
	 * Add files into current index for the object
	 * if the file has been added, return true
	 * if the index has been created for the file, return string
	 * @param array $files full path to the file
	 * @return object bab_indexReturn
	 */
	function addFilesToIndex($files) {

		$this->autorized_files_only($files);

		if ($this->disabled || 0 === count($files)) {
			$r = new bab_indexReturn;
			$r->result = false;
			return $r;
		}


		switch($this->engineName) {
			case 'swish':
				include_once $GLOBALS['babInstallPath'].'utilit/searchincl.swish.php';
				break;
		}

		$obj = new bab_indexFilesCls( $files, $this->object);
		
		$r = $obj->addFilesToIndex();
		$r->addInfo(sprintf(bab_translate("%s : All collection of files has been added into the index (%u files)"),$this->label, count($files)));
		return $r;
	}



	/**
	 * Set a id_object associated to an indexed file
	 * this will be used for bab_isAccessValid
	 * @param string $file
	 * @param integer $id_object_access
	 * @see bab_isAccessValid()
	 */
	function setIdObjectFile($file, $id_object, $id_object_access) {

		if ($this->disabled) {
			return false;
		}

		$this->db->db_query("REPLACE INTO ".BAB_INDEX_ACCESS_TBL." 
				(file_path, id_object, id_object_access, object) 
			VALUES 
				(
					'".$this->db->db_escape_string($file)."',
					'".$this->db->db_escape_string($id_object)."',
					'".$this->db->db_escape_string($id_object_access)."',
					'".$this->db->db_escape_string($this->object)."' 
				)
			");

	}


	/**
	 * Remove files from the index
	 */
	function removeFilesFromIndex($files) {

		if ($this->disabled) {
			return false;
		}

		switch($this->engineName) {
			case 'swish':
				include_once $GLOBALS['babInstallPath'].'utilit/searchincl.swish.php';
				break;
		}

		$query = array();
		foreach($files as $f) {
			$query[] = $db->db_escape_string($f);
		}

		$this->db->db_query("DELETE FROM ".BAB_INDEX_ACCESS_TBL." WHERE file_path IN('".implode("','", $query)."')");

		$obj = new bab_indexFilesCls($files, $this->object);
		return $obj->removeFilesFromIndex();
	}
}


/**
 * Call this function when a file is loaded, 
 * the file will be indexed if necessary
 * 
 * @param string $file full path to the file, usually in upload directory
 * @param string $object if not given, the current addon name will be used
 * @param integer $id_object_access
 * @return integer
 */
function bab_indexOnLoadFiles($files, $object) {

	
	
	$obj = new bab_indexObject($object);
	$obj->autorized_files_only($files);
	$status = $obj->get_onLoadStatus();

	if (BAB_INDEX_STATUS_INDEXED === $status && 0 < count($files)) {
		if (false !== $obj->addFilesToIndex($files)) {
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
				'name' =>			$arr['name'],
				'index_onload' =>	1 == $arr['index_onload'], 
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

/**
 * Internationalized string
 * @return string
 */
function bab_getIndexStatusLabel($status) {

	switch ($status) {
		case BAB_INDEX_STATUS_NOINDEX:
			return bab_translate("No indexation");

		case BAB_INDEX_STATUS_INDEXED:
			return bab_translate("Indexed");

		case BAB_INDEX_STATUS_TOINDEX:
			return bab_translate("Wait indexation");
	}
}


function bab_isFileIndex($filename) {
	$engine = bab_searchEngineInfos();
	if (!$engine)
		return false;
	$type = bab_getFileMimeType($filename);
	if (!in_array($type, $engine['types']))
		return false;
	return true;
}

?>