<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
function bab_getFolderName($id)
	{
	global $babDB;
	$res = $babDB->db_query("select folder from ".BAB_FM_FOLDERS_TBL." where id='".$id."'");
	if( $res && $babDB->db_num_rows($res) > 0)
		{
		$arr = $babDB->db_fetch_array($res);
		return $arr['folder'];
		}
	else
		{
		return "";
		}
	}

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
	global $babDB;
	$pathx = bab_getUploadFullPath($gr, $id);
	$babDB->db_query("delete from ".BAB_FILES_TBL." where id_owner='".$id."' and bgroup='".$gr."'");
	@bab_deleteUploadDir($pathx);
	}

function bab_isAccessFileValid($gr, $id)
	{
	global $babBody;

	bab_fileManagerAccessLevel();
	$access = false;
	if( $gr == "Y")
		{
		for( $i = 0; $i < count($babBody->aclfm['id']); $i++)
			{
			if( $babBody->aclfm['id'][$i] == $id && $babBody->aclfm['down'][$i])
				{
				$access = true;
				break;
				}
			}
		}
	else if( !empty($GLOBALS['BAB_SESS_USERID']) && $id == $GLOBALS['BAB_SESS_USERID'])
		{
		if( $babBody->ustorage)
			{
			$access = true;
			}
		}
	return $access;
	}
?>