<?php
/************************************************************************
 * Ovidentia                                                            *
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ************************************************************************
 * This program is free software; you can redistribute it and/or modify *
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

function bab_getUploadFullPath($gr, $id)
{
	if( substr($GLOBALS['babUploadPath'], -1) == "/" )
		$path = $GLOBALS['babUploadPath'];
	else
		$path = $GLOBALS['babUploadPath']."/";

	if( $gr == "Y")
		return $path."G".$id."/";
	else
		return $path."U".$id."/";
}

function bab_formatSizeFile($size, $roundoff = true)
{
	if( $size <= 0 )
		return 0;

	if( $size <= 1024 )
		return 1;
	else
		{
		if( $roundoff)
			$size = (int)($size / 1024);
		if( ($l = strlen($size)) > 3)
			{
			if( $l % 3 > 0 )
				{
				$txt = substr( $size, 0, $l % 3);
				}
			for( $i = 0; $i < ($l / 3); $i++)
				{
				$txt .= " ". substr($size, $l%3 + $i*3, 3);
				}
			}
		else
			$txt = $size;
		return $txt;
		}
		
}

function bab_deleteUploadDir($path)
	{
	if (file_exists($path))
		{
	    chmod($file,0777);
		if (is_dir($path))
			{
			$handle = opendir($path);
		    while($filename = readdir($handle))
				{
		        if ($filename != "." && $filename != "..")
					{
			        bab_deleteUploadDir($path."/".$filename);
					}
				}
			closedir($handle);
			@rmdir($path);
			} 
		else
			{
			@unlink($path);
			}
		}
	}


function bab_deleteUploadUserFiles($gr, $id)
	{
	$db = $GLOBALS['babDB'];	
	$pathx = bab_getUploadFullPath($gr, $id);
	$db->db_query("delete from ".BAB_FILES_TBL." where id_owner='".$id."' and bgroup='".$gr."'");
	@bab_deleteUploadDir($pathx);
	}

function bab_isAccessFileValid($gr, $id)
	{
	$aclfm = bab_fileManagerAccessLevel();
	$access = false;
	if( $gr == "Y")
		{
		for( $i = 0; $i < count($aclfm['id']); $i++)
			{
			if( $aclfm['id'][$i] == $id && $aclfm['pu'][$i] == 1)
				{
				$access = true;
				break;
				}
			}
		}
	else if( !empty($GLOBALS['BAB_SESS_USERID']) && $id == $GLOBALS['BAB_SESS_USERID'])
		{
		if( in_array(1, $aclfm['pr']))
			{
			$access = true;
			}
		}
	return $access;
	}

?>
