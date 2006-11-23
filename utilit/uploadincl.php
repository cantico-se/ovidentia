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