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

define("BAB_FVERSION_FOLDER", "OVF");

/* 0 -> other, 1 -> edit, 2 -> unedit, 3 -> commit */
define("BAB_FACTION_OTHER"	, 0);
define("BAB_FACTION_EDIT"	, 1);
define("BAB_FACTION_UNEDIT"	, 2);
define("BAB_FACTION_COMMIT"	, 3);
$babFileActions = array(bab_translate("Other"), bab_translate("Edit file"),
				bab_translate("Unedit file"), bab_translate("Commit file"));

function getDirSize( $dir )
	{
	if( !is_dir($dir))
		return 0;
	$h = opendir($dir);
	$size = 0;
	while (($f = readdir($h)) != false)
		{
		if ($f != "." and $f != "..") 
			{
			$path = $dir."/".$f;
			if (is_dir($path))
				$size += getDirSize($path."/");
			elseif (is_file($path))
				$size += filesize($path);
			}
		}
	closedir($h);
	return $size;
	}


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
			$size = floor($size / 1024);
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
	global $babBody, $babDB;
	$access = false;
	if( $gr == "Y")
		{
		$res = $babDB->db_query("select id, manager from ".BAB_FM_FOLDERS_TBL." where id ='".$id."' and active='Y'");
		if( $res && $babDB->db_num_rows($res) > 0 )
			{
			$arr = $babDB->db_fetch_array($res);
			if( $arr['manager'] == $GLOBALS['BAB_SESS_USERID'] || bab_isAccessValid(BAB_FMDOWNLOAD_GROUPS_TBL, $id))
				$access = true;
			}
		}
	else if( !empty($GLOBALS['BAB_SESS_USERID']) && $id == $GLOBALS['BAB_SESS_USERID'])
		{
		$res = $babDB->db_query("select ".BAB_GROUPS_TBL.".id from ".BAB_GROUPS_TBL." join ".BAB_USERS_GROUPS_TBL." where id_object='".$GLOBALS['BAB_SESS_USERID']."' and ".BAB_GROUPS_TBL.".id=".BAB_USERS_GROUPS_TBL.".id_group and ".BAB_GROUPS_TBL.".ustorage ='Y'");

		if( $res && $babDB->db_num_rows($res) > 0 )
			{
			$access = true;
			}
		else
			{
			$arr = $babDB->db_fetch_array($babDB->db_query("select ustorage from ".BAB_GROUPS_TBL." where id='1'"));
			if( $arr['ustorage'] == "Y")
				$access = true;
			}
		}
	return $access;
	}

function notifyFileApprovers($id, $users, $msg)
	{
	global $babBody, $BAB_SESS_USER, $BAB_SESS_EMAIL, $babAdminEmail, $babInstallPath;
    include_once $babInstallPath."utilit/mailincl.php";

	class tempa
		{
		var $filename;
		var $message;
        var $from;
        var $author;
        var $path;
        var $pathname;
        var $file;
        var $site;
        var $date;
        var $dateval;
		var $group;
		var $groupname;


		function tempa($id, $msg)
			{
            global $babDB, $BAB_SESS_USER, $BAB_SESS_EMAIL;
			$arr = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_FILES_TBL." where id='".$id."'"));
            $this->filename = $arr['name'];
            $this->message = $msg;
            $this->from = bab_translate("Author");
            $this->path = bab_translate("Path");
            $this->file = bab_translate("File");
            $this->group = bab_translate("Folder");
            $this->pathname = $arr['path'] == ""? "/": $arr['path'];
            $this->groupname = bab_getFolderName($arr['id_owner']);
            $this->site = bab_translate("Web site");
            $this->date = bab_translate("Date");
            $this->dateval = bab_strftime(mktime());
            if( !empty($arr['author']))
				{
                $this->author = bab_getUserName($arr['author']);
                $this->authoremail = bab_getUserEmail($arr['author']);
				}
            else
				{
                $this->author = bab_translate("Unknown user");
                $this->authoremail = "";
				}
			}
		}
    $mail = bab_mail();
	if( $mail == false )
		return;
	
	for( $i=0; $i < count($users); $i++)
		$mail->mailTo(bab_getUserEmail($users[$i]), bab_getUserName($users[$i]));
    $mail->mailFrom($babAdminEmail, bab_translate("Ovidentia Administrator"));
    $mail->mailSubject(bab_translate("New waiting file"));

	$tempa = new tempa($id, $msg);
	$message = bab_printTemplate($tempa,"mailinfo.html", "filewait");
    $mail->mailBody($message, "html");

	$message = bab_printTemplate($tempa,"mailinfo.html", "filewaittxt");
    $mail->mailAltBody($message);

	$mail->send();
	}


function fileNotifyMembers($file, $path, $idgrp, $msg)
	{
	global $babBody, $BAB_SESS_USER, $BAB_SESS_EMAIL, $babAdminEmail, $babInstallPath;
    include_once $babInstallPath."utilit/mailincl.php";

	class tempb
		{
		var $filename;
		var $message;
        var $author;
        var $path;
        var $pathname;
        var $file;
        var $site;
        var $date;
        var $dateval;
		var $group;
		var $groupname;


		function tempb($file, $path, $idgrp, $msg)
			{
            $this->filename = $file;
            $this->message = $msg;
            $this->path = bab_translate("Path");
            $this->file = bab_translate("File");
            $this->group = bab_translate("Folder");
            $this->pathname = $path == ""? "/": $path;
            $this->groupname = bab_getFolderName($idgrp);
            $this->site = bab_translate("Web site");
            $this->date = bab_translate("Date");
            $this->dateval = bab_strftime(mktime());
			}
		}

	$mail = bab_mail();
	if( $mail == false )
		return;

	$mail->mailTo($babAdminEmail, bab_translate("Ovidentia Administrator"));
	$mail->mailFrom($babAdminEmail, bab_translate("Ovidentia Administrator"));

	if( $bnew )
		$mail->mailSubject(bab_translate("New file"));
	else
		$mail->mailSubject(bab_translate("File has been updated"));

	$tempa = new tempb($file, $path, $idgrp, $msg);
	$message = bab_printTemplate($tempa,"mailinfo.html", "fileuploaded");

	$messagetxt = bab_printTemplate($tempa,"mailinfo.html", "fileuploadedtxt");

	$db = $GLOBALS['babDB'];
	$res = $db->db_query("select id_group from ".BAB_FMDOWNLOAD_GROUPS_TBL." where  id_object='".$idgrp."'");
	if( $res && $db->db_num_rows($res) > 0 )
		{
		while( $row = $db->db_fetch_array($res))
			{

			switch($row['id_group'])
				{
				case 0:
				case 1:
					$res2 = $db->db_query("select id, email, firstname, lastname from ".BAB_USERS_TBL." where is_confirmed='1' and disabled='0'");
					break;
				case 2:
					return;
				default:
					$res2 = $db->db_query("select ".BAB_USERS_TBL.".id, ".BAB_USERS_TBL.".email, ".BAB_USERS_TBL.".firstname, ".BAB_USERS_TBL.".lastname from ".BAB_USERS_TBL." join ".BAB_USERS_GROUPS_TBL." where is_confirmed='1' and disabled='0' and ".BAB_USERS_GROUPS_TBL.".id_group='".$row['id_group']."' and ".BAB_USERS_GROUPS_TBL.".id_object=".BAB_USERS_TBL.".id");
					break;
				}

			if( $res2 && $db->db_num_rows($res2) > 0 )
				{
				$count = 0;
				while(($arr = $db->db_fetch_array($res2)))
					{
					$mail->mailBcc($arr['email'], bab_composeUserName($arr['firstname'],$arr['lastname']));
					$count++;
					if( $count == 25 )
						{
						$mail->mailBody($message, "html");
						$mail->mailAltBody($messagetxt);
						$mail->send();
						$mail->clearBcc();
						$count = 0;
						}
					}

				if( $count > 0 )
					{
					$mail->mailBody($message, "html");
					$mail->mailAltBody($messagetxt);
					$mail->send();
					}
				}
			}
		}
	}
?>