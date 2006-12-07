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
include_once 'base.php';

define('BAB_FMFILE_UPLOAD'	, 1);
define('BAB_FMFILE_MOVE'	, 2);
define('BAB_FMFILE_COPY'	, 3);


/**
 * Wrapper for file manager insertion
 * Instance of this object is the source to create a file in the filemanager
 */
class bab_fmFile {

	/**#@+
	 * @private
	 */
	var $type;
	var $source;
	/**#@-*/
	
	/**#@+
	 * @public
	 */
	var $filename;
	var $size;
	/**#@-*/
	
	/**
	 * @private
	 * Create object with specified type
	 * @param	int		$type		BAB_FMFILE_UPLOAD, BAB_FMFILE_MOVE, BAB_FMFILE_COPY
	 */
	function bab_fmFile($type, $source) {
		$this->type = $type;
		$this->source = $source;
	}
	
	/**
	 * @static
	 * @public
	 * Prepare file for upload
	 * @param	string	$fieldname
	 */
	function upload($fieldname) {
		$obj = new bab_fmFile(BAB_FMFILE_UPLOAD, $_FILES[$fieldname]['tmp_name']);
		$obj->filename 	= $_FILES[$fieldname]['name'];
		$obj->size	 	= $_FILES[$fieldname]['size'];
		return $obj;
	}
	
	/**
	 * @static
	 * @public
	 * Prepare file for copy
	 * @param	string	$sourcefile
	 */
	function copy($sourcefile) {
		$obj = new bab_fmFile(BAB_FMFILE_COPY, $sourcefile);
		$obj->filename 	= basename($sourcefile);
		$obj->size	 	= filesize($sourcefile);
		return $obj;
	}
	
	/**
	 * @static
	 * @public
	 * Prepare file for move
	 * @param	string	$sourcefile
	 */
	function move($sourcefile) {
		$obj = new bab_fmFile(BAB_FMFILE_MOVE, $sourcefile);
		$obj->filename 	= basename($sourcefile);
		$obj->size	 	= filesize($sourcefile);
		return $obj;
	}
	
	/**
	 * install the prepared file into destination
	 * @param	string	$destination 	(destination full path and file name)
	 * @return	boolean
	 */
	function import($destination) {
	
		if( !get_cfg_var('safe_mode')) {
			set_time_limit(0);
		}
	
		switch($this->type) {
			case BAB_FMFILE_UPLOAD:
				return move_uploaded_file($this->source, $destination);
				break;
				
			case BAB_FMFILE_COPY:
				return copy($this->source, $destination);
				break;
				
			case BAB_FMFILE_MOVE:
				return move($this->source, $destination);
				break;
		}
	}
}



/** 
 * Import a file into the file manager
 * if the file exists, the file is updated or a new version of the file is created
 * @param 	object	$fmFile			bab_fmFile instance
 * @param	int		$id_owner
 * @param	string	$path
 * @param	boolean	$bgroup			true if the $id_owner is a folder, false if the $id_owner is a user
 *
 * @return 	boolean	id_file
 */
function bab_importFmFile($fmFile, $id_owner, $path, $bgroup) {
	
	global $babDB;
	include_once $GLOBALS['babInstallPath'].'utilit/fileincl.php';

	$filename = $_FILES[$field_name]['name'];
	$pathx = bab_getUploadFullPath($arr['bgroup'], $arr['id_owner'], $path);
	$gr = $bgroup ? 'Y' : 'N';

	if( file_exists($pathx.$fmFile->filename)) {

		$res = $babDB->db_query('
		SELECT 
			id, description, keywords 
		FROM '.BAB_FILES_TBL.' 
			WHERE path	='.$babDB->quote($path).' 
			AND name	='.$babDB->quote($fmFile->filename).' 
			AND id_owner='.$babDB->quote($id_owner).' 
			AND bgroup	='.$babDB->quote($gr)
		);
		
		$arr = $babDB->db_fetch_assoc($res);
		$fm_file = fm_getFileAccess($arr['id']);
		
		if (!$fm_file['bupdate']) {
			return false;
		}

		if ($bgroup && 'Y' == $fm_file['arrfold']['version']) {
			// add a version
			fm_lockFile($arr['id'], ''); 
			return fm_commitFile($arr['id'], '', 'N', $fmFile->filename, $_FILES[$field_name]['size'], $_FILES[$field_name]['tmp_name']);
		}

	
		// update a file
		return saveUpdateFile(
			$arr['id'], 
			$fmFile->filename, 
			$_FILES[$field_name]['size'],
			$_FILES[$field_name]['tmp_name'], 
			$fmFile->filename, 
			$arr['description'], 
			$arr['keywords'], 
			$arr['readonly'], 'Y', false, false, false
		);
			
	} else {
		// create new file
		return saveFile(
			array($field_name => $_FILES[$field_name]),
			$id_owner, 
			$gr, 
			$path, '', '', 'N'
		);
	}
}



/**
 * get the content of a file from upload
 * the file is opened in the upload directory
 * @param	string	$fieldname
 * @return	string|false
 */
function bab_getUploadedFileContent($fieldname) {
	if (!isset($_FILES[$fieldname]['tmp_name'])) {
		return false;
	}
	
	if (!is_dir($GLOBALS['babUploadPath'].'/tmp/')) {
		bab_mkdir($GLOBALS['babUploadPath'].'/tmp/');
	}
	
	$tmpfile = $GLOBALS['babUploadPath'].'/tmp/'.$_FILES[$fieldname]['name'];
	if (move_uploaded_file($_FILES[$fieldname]['tmp_name'],$tmpfile)) {
	
		$return = '';
		
		$fp=fopen($tmpfile,"rb");
		if( $fp )
			{
			while (!feof($fp)) {
				$return .= fread($fp,8192);
			}
			fclose($fp);
			
			unlink($tmpfile);
			return $return;
		}
	}
	return false;
}

?>