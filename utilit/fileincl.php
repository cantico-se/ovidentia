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
		if( $babBody->ovgroups[1]['ustorage'] == 'Y')
			{
			$access = true;
			}
		else
			{
			for( $i = 0; $i < count($babBody->usergroups); $i++)
				{
				if( $babBody->ovgroups[1]['ustorage'] == 'Y')
					{
					$access = true;
					break;
					}
				}
			}
		}
	return $access;
	}

function notifyFileApprovers($id, $users, $msg)
	{
	global $babDB, $babBody, $BAB_SESS_USER, $BAB_SESS_EMAIL, $babAdminEmail, $babInstallPath;
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
	
	if( count($users) > 0 )
		{
		$sql = "select email, firstname, lastname from ".BAB_USERS_TBL." where id IN (".implode(',', $users).")";
		$result=$babDB->db_query($sql);
		while( $arr = $babDB->db_fetch_array($result))
			{
			$mail->mailBcc($arr['email'], bab_composeUserName($arr['firstname'],$arr['lastname']));
			}
		}
    $mail->mailFrom($babAdminEmail, $GLOBALS['babAdminName']);
    $mail->mailSubject(bab_translate("New waiting file"));

	$tempa = new tempa($id, $msg);
	$message = $mail->mailTemplate(bab_printTemplate($tempa,"mailinfo.html", "filewait"));
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

	$mail->mailFrom($babAdminEmail, $GLOBALS['babAdminName']);

	if( $bnew )
		$mail->mailSubject(bab_translate("New file"));
	else
		$mail->mailSubject(bab_translate("File has been updated"));

	$tempa = new tempb($file, $path, $idgrp, $msg);
	$message = $mail->mailTemplate(bab_printTemplate($tempa,"mailinfo.html", "fileuploaded"));

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

function acceptFileVersion($arrfile, $arrvf, $bnotify)
{
	global $babDB;

	$pathx = bab_getUploadFullPath($arrfile['bgroup'], $arrfile['id_owner']);
	if( substr($arrfile['path'], -1) == "/")
		$pathx .= substr($arrfile['path'], 0 , -1);
	else if( !empty($arrfile['path']))
		$pathx .= $arrfile['path']."/";

	copy($pathx.$arrfile['name'], $pathx.BAB_FVERSION_FOLDER."/".$arrfile['ver_major'].",".$arrfile['ver_minor'].",".$arrfile['name']);
	copy($pathx.BAB_FVERSION_FOLDER."/".$arrvf['ver_major'].",".$arrvf['ver_minor'].",".$arrfile['name'], $pathx.$arrfile['name']);
	unlink($pathx.BAB_FVERSION_FOLDER."/".$arrvf['ver_major'].",".$arrvf['ver_minor'].",".$arrfile['name']);
	$babDB->db_query("update ".BAB_FILES_TBL." set edit='0', modified='".$arrvf['date']."', modifiedby='".$arrvf['author']."', ver_major='".$arrvf['ver_major']."', ver_minor='".$arrvf['ver_minor']."', ver_comment='".addslashes($arrvf['comment'])."' where id='".$arrfile['id']."'");

	$babDB->db_query("insert into ".BAB_FM_FILESLOG_TBL." ( id_file, date, author, action, comment, version) values ('".$arrfile['id']."', now(), '".$arrvf['author']."', '".BAB_FACTION_COMMIT."', '".addslashes($arrvf['comment'])."', '".$arrvf['ver_major'].".".$arrvf['ver_minor']."')");
	$babDB->db_query("update ".BAB_FM_FILESVER_TBL." set idfai='0', confirmed='Y', ver_major='".$arrfile['ver_major']."', ver_minor='".$arrfile['ver_minor']."', author='".($arrfile['modifiedby']==0?$arrfile['author']: $arrfile['modifiedby'])."', comment='".addslashes($arrfile['ver_comment'])."' where id='".$arrfile['edit']."'");
	notifyFileAuthor(bab_translate("Your new file version has been accepted"), $arrvf['ver_major'].".".$arrvf['ver_minor'], $arrvf['author']);
	if( $bnotify == "Y")
		{
		fileNotifyMembers($arrfile['name'], $arrfile['path'], $arrfile['id_owner'], bab_translate("A new version file has been uploaded"));
		}
}

?>
