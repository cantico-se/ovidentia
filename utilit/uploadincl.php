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
	var $error;
	/**#@-*/
	
	/**
	 * @private
	 * Create object with specified type
	 * @param	int		$type		BAB_FMFILE_UPLOAD, BAB_FMFILE_MOVE, BAB_FMFILE_COPY
	 */
	function bab_fmFile($type, $source) {
		$this->type = $type;
		$this->source = $source;
		$this->error = false;
	}
	
	/**
	 * @static
	 * @public
	 * Prepare file for upload
	 * @param	string	$fieldname
	 */
	function upload($fieldname) {
		if (!isset($_FILES[$fieldname])) {
			return false;
		}
		
		$tmp_error = false;
		
		if (isset($_FILES[$fieldname]['error'])) {
		
			/**
			 * ['error'] is defined since php 4.2.0
			 * constants are defined since php 4.3.0
			 */
		
			switch($_FILES[$fieldname]['error']) {
				case 0 : // UPLOAD_ERR_OK
					break;
					
				case 1 : // UPLOAD_ERR_INI_SIZE
					$tmp_error = bab_translate('The uploaded file exceeds the upload_max_filesize directive.');
					break;
					
				case 2 : // UPLOAD_ERR_FORM_SIZE
					$tmp_error = bab_translate('The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.');
					break;
					
				case 3 : // UPLOAD_ERR_PARTIAL
					$tmp_error = bab_translate('The uploaded file was only partially uploaded.');
					break;
					
				case 4 : // UPLOAD_ERR_NO_FILE
					$tmp_error = bab_translate('No file was uploaded.');
					break;	
						
				case 6 : // UPLOAD_ERR_NO_TMP_DIR since PHP 4.3.10 and PHP 5.0.3
					$tmp_error = bab_translate('Missing a temporary folder.');
					break;	
					
				case 7 : // UPLOAD_ERR_CANT_WRITE since php 5.1.0
					$tmp_error = bab_translate('Failed to write file to disk.');
					break;
					
				default :
					$tmp_error = bab_translate('Unknown File Error.');
					break;
			}
		}
		
		$obj = new bab_fmFile(BAB_FMFILE_UPLOAD, $_FILES[$fieldname]['tmp_name']);
		$obj->filename 	= $_FILES[$fieldname]['name'];
		$obj->size	 	= $_FILES[$fieldname]['size'];
		$obj->error		= $tmp_error;
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
function bab_importFmFile($fmFile, $id_owner, $path, $bgroup) 
{
	global $babDB;
	include_once $GLOBALS['babInstallPath'] . 'utilit/fileincl.php';

	$gr = $bgroup ? 'Y' : 'N';
	
	$sEndSlash = '';
	if(strlen(trim($path)) > 0)
	{
		$sEndSlash = '/';
	}
	
	$sPathName = '';
	if('Y' === $gr)
	{
		$oFmFolder = null;
		BAB_FmFolderHelper::getFileInfoForCollectiveDir($id_owner, $path, $id_owner, $sPathName, $oFmFolder);
	}
	else 
	{
		$sPathName = 'U' . $id_owner . '/' . $path . $sEndSlash;
	}
	
	$sFullPathNane = BAB_FmFolderHelper::getUploadPath() . $sPathName . $fmFile->filename;
	
	if(file_exists($sFullPathNane)) 
	{
		$oFolderFileSet = new BAB_FolderFileSet();
		
		$oPathName =& $oFolderFileSet->aField['sPathName'];
		$oName =& $oFolderFileSet->aField['sName'];
		$oIdOwner =& $oFolderFileSet->aField['iIdOwner'];
		$oGroup =& $oFolderFileSet->aField['sGroup'];
				
		$oCriteria = $oPathName->in($sPathName);
		$oCriteria = $oCriteria->_and($oName->in($fmFile->filename));
		$oCriteria = $oCriteria->_and($oIdOwner->in($id_owner));
		$oCriteria = $oCriteria->_and($oGroup->in($gr));
		$oFolderFile = $oFolderFileSet->get($oCriteria);
		
		if(!is_null($oFolderFile))
		{
			$fm_file = fm_getFileAccess($oFolderFile->getId());
			$oFmFolder =& $fm_file['oFmFolder'];
			
			if(!$fm_file['bupdate']) 
			{
				return false;
			}
	
			if($bgroup && 'Y' == $oFmFolder->getVersioning())
			{
				// add a version
				fm_lockFile($oFolderFile->getId(), ''); 
				return fm_commitFile($oFolderFile->getId(), '', 'N', $fmFile);
			}
		
			// update a file
			return saveUpdateFile($oFolderFile->getId(), $fmFile, $fmFile->filename, 
				$oFolderFile->getDescription(), '', 
				$oFolderFile->getReadOnly(), 'Y', false, false, false);
		}
		return false;
	}
	else 
	{
		// create new file
		return saveFile(array($fmFile), $id_owner, $gr, $sPathName, '', '', 'N');
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