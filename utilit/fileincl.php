<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
function getFullPath($gr, $id)
{
	if( $gr == "Y")
		return $GLOBALS['babUploadPath']."/G".$id."/";
	else
		return $GLOBALS['babUploadPath']."/U".$id."/";
}

function babDeleteFiles($path)
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
			        babDeleteFiles($path."/".$filename);
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


function deleteUserFiles($gr, $id)
	{
	$db = new db_mysql();	
	$pathx = getFullPath($gr, $id);
	$db->db_query("delete from files where id_owner='".$id."' and bgroup='".$gr."'");
	@babDeleteFiles($pathx);
	}
?>