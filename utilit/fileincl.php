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
require_once $GLOBALS['babInstallPath'] . 'utilit/criteria.class.php';


define('BAB_FVERSION_FOLDER', 'OVF');

/* 0 -> other, 1 -> edit, 2 -> unedit, 3 -> commit */
define('BAB_FACTION_OTHER'	, 0);
define('BAB_FACTION_EDIT'	, 1);
define('BAB_FACTION_UNEDIT'	, 2);
define('BAB_FACTION_COMMIT'	, 3);
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

/**
 * @param	Y|N		$gr
 * @param	int		$id
 * @param	string	$path
 * @return 	string
 */
function bab_getUploadFullPath($gr, $id, $path = '')
{
	if( substr($GLOBALS['babUploadPath'], -1) == "/" )
		$root = $GLOBALS['babUploadPath'];
	else
		$root = $GLOBALS['babUploadPath']."/";
		
	// clear path security
	$path = trim(str_replace('..','',$path),' /');
	
	if (!empty($path)) {
		$path .= '/';
	}
		
	return $root.bab_getUploadFmPath($gr, $id).$path;
}

function bab_getUploadFmPath($gr, $id)
{
	if($gr == "Y")
	{
//		return "G".$id."/";

		$oFmFolderSet = new BAB_FmFolderSet();
		$oId =& $oFmFolderSet->aField['iId']; 
		$oFmFolder = $oFmFolderSet->get($oId->in($id));
		if(!is_null($oFmFolder))
		{
			return $oFmFolder->getName() . '/';
		}
	}
	else
	{
		return "U".$id."/";
	}
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
			else $txt = '';
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
		$res = $babDB->db_query("select id from ".BAB_FM_FOLDERS_TBL." where id ='".$babDB->db_escape_string($id)."' and active='Y'");
		if( $res && $babDB->db_num_rows($res) > 0 )
			{
			$arr = $babDB->db_fetch_array($res);
			if( bab_isAccessValid(BAB_FMMANAGERS_GROUPS_TBL, $id) || bab_isAccessValid(BAB_FMDOWNLOAD_GROUPS_TBL, $id))
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
			foreach( $babBody->usergroups as $grpid)
				{
				if( $babBody->ovgroups[$grpid]['ustorage'] == 'Y')
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

	if(!class_exists("notifyFileApproversCls"))
		{
		class notifyFileApproversCls
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


			function notifyFileApproversCls($id, $msg)
				{
				global $babDB, $BAB_SESS_USER, $BAB_SESS_EMAIL;
				$arr = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_FILES_TBL." where id='".$babDB->db_escape_string($id)."'"));
				$this->filename = $arr['name'];
				$this->message = $msg;
				$this->from = bab_translate("Author");
				$this->path = bab_translate("Path");
				$this->file = bab_translate("File");
				$this->group = bab_translate("Folder");
				$this->pathname = $arr['path'] == ""? "/": $arr['path'];
				$this->groupname = '';

			
				$oFmFolderSet = new BAB_FmFolderSet();
				$oId =& $oFmFolderSet->aField['iId']; 
				$oFmFolder = $oFmFolderSet->get($oId->in($arr['id_owner']));
				if(!is_null($oFmFolder))
				{
					$this->groupname = $oFmFolder->getName();
				}
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
		}
    $mail = bab_mail();
	if( $mail == false )
		return;
	
	if( count($users) > 0 )
		{
		$sql = "select email, firstname, lastname from ".BAB_USERS_TBL." where id IN (".$babDB->quote($users).")";
		$result=$babDB->db_query($sql);
		while( $arr = $babDB->db_fetch_array($result))
			{
			$mail->mailBcc($arr['email'], bab_composeUserName($arr['firstname'],$arr['lastname']));
			}
		}
    $mail->mailFrom($babAdminEmail, $GLOBALS['babAdminName']);
    $mail->mailSubject(bab_translate("New waiting file"));

	$tempa = new notifyFileApproversCls($id, $msg);
	$message = $mail->mailTemplate(bab_printTemplate($tempa,"mailinfo.html", "filewait"));
    $mail->mailBody($message, "html");

	$message = bab_printTemplate($tempa,"mailinfo.html", "filewaittxt");
    $mail->mailAltBody($message);

	$mail->send();
	}


function fileNotifyMembers($file, $path, $idgrp, $msg, $bnew = true)
	{
	global $babBody, $BAB_SESS_USER, $BAB_SESS_EMAIL, $babAdminEmail, $babInstallPath;
    include_once $babInstallPath."utilit/mailincl.php";
	include_once $babInstallPath."admin/acl.php";

	if(!class_exists("fileNotifyMembersCls"))
		{
		class fileNotifyMembersCls
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

			function fileNotifyMembersCls($file, $path, $idgrp, $msg)
				{
				$this->filename = $file;
				$this->message = $msg;
				$this->path = bab_translate("Path");
				$this->file = bab_translate("File");
				$this->group = bab_translate("Folder");
				$this->pathname = $path == ""? "/": $path;
				$this->groupname = '';
				$oFmFolderSet = new BAB_FmFolderSet();
				$oId =& $oFmFolderSet->aField['iId']; 
				$oFmFolder = $oFmFolderSet->get($oId->in($idgrp));
				if(!is_null($oFmFolder))
				{
					$this->groupname = $oFmFolder->getName();
				}
				$this->site = bab_translate("Web site");
				$this->date = bab_translate("Date");
				$this->dateval = bab_strftime(mktime());
				}
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

	$tempa = new fileNotifyMembersCls($file, $path, $idgrp, $msg);
	$message = $mail->mailTemplate(bab_printTemplate($tempa,"mailinfo.html", "fileuploaded"));
	$messagetxt = bab_printTemplate($tempa,"mailinfo.html", "fileuploadedtxt");

	$mail->mailBody($message, "html");
	$mail->mailAltBody($messagetxt);

	$users = aclGetAccessUsers(BAB_FMDOWNLOAD_GROUPS_TBL, $idgrp);

	$arrusers = array();
	$count = 0;
	foreach($users as $id => $arr)
		{
		if( count($arrusers) == 0 || !isset($arrusers[$id]))
			{
			$arrusers[$id] = $id;
			$mail->mailBcc($arr['email'], $arr['name']);
			$count++;
			}

		if( $count == 25 )
			{
			$mail->send();
			$mail->clearBcc();
			$mail->clearTo();
			$count = 0;
			}
		}

	if( $count > 0 )
		{
		$mail->send();
		$mail->clearBcc();
		$mail->clearTo();
		$count = 0;
		}
	}

function acceptFileVersion($arrfile, $arrvf, $bnotify)
{
	global $babDB;

	$pathx = bab_getUploadFullPath($arrfile['bgroup'], $arrfile['id_owner'], $arrfile['path']);
	

	copy($pathx.$arrfile['name'], $pathx.BAB_FVERSION_FOLDER."/".$arrfile['ver_major'].",".$arrfile['ver_minor'].",".$arrfile['name']);
	copy($pathx.BAB_FVERSION_FOLDER."/".$arrvf['ver_major'].",".$arrvf['ver_minor'].",".$arrfile['name'], $pathx.$arrfile['name']);
	unlink($pathx.BAB_FVERSION_FOLDER."/".$arrvf['ver_major'].",".$arrvf['ver_minor'].",".$arrfile['name']);
	$babDB->db_query("
	
	update ".BAB_FILES_TBL." 
	set 
		edit='0', 
		modified='".$babDB->db_escape_string($arrvf['date'])."', 
		modifiedby='".$babDB->db_escape_string($arrvf['author'])."', 
		ver_major='".$babDB->db_escape_string($arrvf['ver_major'])."', 
		ver_minor='".$babDB->db_escape_string($arrvf['ver_minor'])."', 
		ver_comment='".$babDB->db_escape_string($arrvf['comment'])."' 
	where 
		id='".$babDB->db_escape_string($arrfile['id'])."'
	");

	$babDB->db_query("
	
	insert into ".BAB_FM_FILESLOG_TBL." 
		( 
			id_file, 
			date, 
			author, 
			action, 
			comment, 
			version
		) 
	values 
		(
			'".$babDB->db_escape_string($arrfile['id'])."', 
			now(), 
			'".$babDB->db_escape_string($arrvf['author'])."', 
			'".BAB_FACTION_COMMIT."', 
			'".$babDB->db_escape_string($arrvf['comment'])."', 
			'".$babDB->db_escape_string($arrvf['ver_major']).".".$babDB->db_escape_string($arrvf['ver_minor'])."'
		)
	");
	
	
	$babDB->db_query("
	
	update ".BAB_FM_FILESVER_TBL." 
	set 
		idfai='0', 
		confirmed='Y', 
		ver_major='".$babDB->db_escape_string($arrfile['ver_major'])."', 
		ver_minor='".$babDB->db_escape_string($arrfile['ver_minor'])."', 
		author='".($arrfile['modifiedby']==0 ? $babDB->db_escape_string($arrfile['author']): $babDB->db_escape_string($arrfile['modifiedby']))."',
		comment='".$babDB->db_escape_string($arrfile['ver_comment'])."' 
	where 
		id='".$babDB->db_escape_string($arrfile['edit'])."'
	");
	
	notifyFileAuthor(bab_translate("Your new file version has been accepted"), $arrvf['ver_major'].".".$arrvf['ver_minor'], $arrvf['author'], $arrfile['name']);
	
	if( $bnotify == "Y")
		{
		fileNotifyMembers($arrfile['name'], $arrfile['path'], $arrfile['id_owner'], bab_translate("A new version file has been uploaded"));
		}
}

function notifyFileAuthor($subject, $version, $author, $filename)
	{
	global $babBody, $babAdminEmail, $babInstallPath;
	include_once $babInstallPath."utilit/mailincl.php";

	class tempc
		{
		var $message;
        var $from;
        var $author;
        var $about;
        var $title;
        var $titlename;
        var $site;
        var $sitename;
        var $date;
        var $dateval;


		function tempc($version, $filename)
			{
            global $babSiteName;
            $this->about = bab_translate("About your file");
            $this->title = bab_translate("Name");
            $this->titlename = $filename;
			if(!empty($version))
				{
				$this->titlename .= " (".$version.")";
				}
            $this->site = bab_translate("Web site");
            $this->sitename = $babSiteName;
            $this->date = bab_translate("Date");
            $this->dateval = bab_strftime(mktime());
            $this->message = '';
			}
		}
	
    $mail = bab_mail();
	if( $mail == false )
		return;

    $mail->mailTo(bab_getUserEmail($author), bab_getUserName($author));
    $mail->mailFrom($babAdminEmail, $GLOBALS['babAdminName']);
    $mail->mailSubject($subject);

	$tempc = new tempc($version, $filename);
	$message = $mail->mailTemplate(bab_printTemplate($tempc,"mailinfo.html", "confirmfileversion"));
    $mail->mailBody($message, "html");

	$message = bab_printTemplate($tempc,"mailinfo.html", "confirmfileversiontxt");
    $mail->mailAltBody($message);
	$mail->send();
	}





/**
 * @param 	array 	$fmFiles	(array of bab_fmFile instances)
 * @param	int		$id
 * @param	Y|N		$gr
 * @param	string	$path
 * @param	string	$description
 * @param	string	$keywords
 * @param	Y|N		$readonly
 *
 * @return	boolean
 */
function saveFile($fmFiles, $id, $gr, $path, $description, $keywords, $readonly)
{
	global $babBody, $babDB, $BAB_SESS_USERID;
	$access = false;
	$bmanager = false;
	$access = false;
	$confirmed = 'N';

	$iIdOwner		= $id;
	$sRelativePath	= '';
	$sFullUploadPath = '';

	if(!empty($BAB_SESS_USERID))
	{
		if('N' === $gr)
		{
			if($babBody->ustorage) 
			{
				$access = true;
				$confirmed = "Y";
				
				$sRelativePath = BAB_FmFolderHelper::getUserDirUploadPath($id) . $path . '/';
				$sFullUploadPath = BAB_FmFolderHelper::getUploadPath() . $sRelativePath;
			}
		}
		else if('Y' === $gr)
		{
			for($i = 0; $i < count($babBody->aclfm['id']); $i++)
			{
				if($babBody->aclfm['id'][$i] == $id && ($babBody->aclfm['uplo'][$i] || $babBody->aclfm['ma'][$i] == 1))
				{
					$access = true;
					break;
				}
			}
			
			if(true === $access)
			{
				$access = BAB_FmFolderHelper::getFileInfoForCollectiveDir($id, $path, $iIdOwner, $sRelativePath);
				$sFullUploadPath = BAB_FmFolderHelper::getUploadPath() . $sRelativePath;
			}
		}
	}
	
//bab_debug('iIdOwner ==> ' . $iIdOwner . ' sRelativePath ==> ' . $sRelativePath . ' sUploadPath ==> ' . $sUploadPath);
	
	if(!$access)
	{
		$babBody->msgerror = bab_translate("Access denied");
		return false;
	}

	
	$pathx = $sFullUploadPath;
	$okfiles = array();
	$errfiles = array();

	foreach($fmFiles as $fmFile) 
	{
		$file = array(
			'name' => trim($fmFile->filename),
			'size' => $fmFile->size
		);

		if(empty($file['name']) || $file['name'] == 'none')
		{
			continue;
		}

		if($file['size'] > $GLOBALS['babMaxFileSize'])
		{
				$errfiles[] = array('error' => bab_translate("The file was greater than the maximum allowed") ." : ". $GLOBALS['babMaxFileSize'], 'file' => $file['name']);
				continue;
		}

		$totalsize = getDirSize($GLOBALS['babUploadPath']);
		if($file['size'] + $totalsize > $GLOBALS['babMaxTotalSize'])
		{
				$errfiles[] = array('error' => bab_translate("There is not enough free space"), 'file'=>$file['name']);
				continue;
		}

		$totalsize = getDirSize($pathx);
		if($file['size'] + $totalsize > ($gr == "Y"? $GLOBALS['babMaxGroupSize']: $GLOBALS['babMaxUserSize']))
		{
				$errfiles[] = array('error' => bab_translate("There is not enough free space"), 'file'=>$file['name']);
				continue;
		}
			
		if(false !== $fmFile->error) 
		{
			$errfiles[] = array(
				'error' => $fmFile->error, 
				'file' => $fmFile->filename
			);
			continue;
		}

		$osfname = $file['name'];

		if(isset($GLOBALS['babFileNameTranslation'])) 
		{
			$osfname = strtr($osfname, $GLOBALS['babFileNameTranslation']);
		}
	
		$name = $osfname;

		$bexist = false;
		if(file_exists($pathx.$osfname))
		{
			$res = $babDB->db_query("
				SELECT * FROM ".BAB_FILES_TBL." 
				WHERE 
					id_owner=	'".$babDB->db_escape_string($iIdOwner)."' 
					AND bgroup=	'".$babDB->db_escape_string($gr)."' 
					AND name=	'".$babDB->db_escape_string($name)."' 
					AND path=	'".$babDB->db_escape_string($sRelativePath)."'
				");
			
			if($res && $babDB->db_num_rows($res) > 0)
			{
				$arr = $babDB->db_fetch_array($res);
				if($arr['state'] == "D")
				{
					$bexist = true;
				}
			}
	
			if($bexist == false)
			{
				$errfiles[] = array('error'=> bab_translate("A file with the same name already exists"), 'file' => $file['name']);
				continue;
			}
		}

		if(!get_cfg_var('safe_mode')) 
		{
			set_time_limit(0);
		}
			
		if(!$fmFile->import($pathx.$osfname))
		{
			$errfiles[] = array('error'=> bab_translate("The file could not be uploaded"), 'file' => $file['name']);
			continue;
		}
	
	
		if(empty($BAB_SESS_USERID))
		{
			$idcreator = 0;
		}
		else
		{
			$idcreator = $BAB_SESS_USERID;
		}
	
		$bnotify = false;
		if($gr == "Y")
		{
			$rr = $babDB->db_fetch_array($babDB->db_query("select filenotify from ".BAB_FM_FOLDERS_TBL." where id='".$babDB->db_escape_string($iIdOwner)."'"));
			if($rr['filenotify'] == "Y")
			{
				$bnotify = true;
			}
	
			if($bexist)
			{
				if(is_dir($pathx.BAB_FVERSION_FOLDER."/"))
				{
					$res = $babDB->db_query("select * from ".BAB_FM_FILESVER_TBL." where id_file='".$babDB->db_escape_string($arr['id'])."'");
					while($rr = $babDB->db_fetch_array($res))
					{
						unlink($pathx.BAB_FVERSION_FOLDER."/".$rr['ver_major'].",".$rr['ver_minor'].",".$osfname);
					}
				}
				$babDB->db_query("delete from ".BAB_FM_FILESVER_TBL." where id_file='".$babDB->db_escape_string($arr['id'])."'");
				$babDB->db_query("delete from ".BAB_FM_FILESLOG_TBL." where id_file='".$babDB->db_escape_string($arr['id'])."'");
				$babDB->db_query("delete from ".BAB_FM_FIELDSVAL_TBL." where id_file='".$babDB->db_escape_string($arr['id'])."'");
			}
			
		}

		include_once $GLOBALS['babInstallPath']."utilit/indexincl.php";
		$index_status = bab_indexOnLoadFiles(array($pathx.$osfname), 'bab_files');

		if($readonly != 'Y')
		{
			$readonly = 'N';
		}
	
		if($bexist)
		{
			$req = "
			UPDATE ".BAB_FILES_TBL." set 
				description='".$babDB->db_escape_string($description)."', 
				keywords='".$babDB->db_escape_string($keywords)."', 
				readonly='".$babDB->db_escape_string($readonly)."', 
				confirmed='".$babDB->db_escape_string($confirmed)."', 
				modified=now(), 
				hits='0', 
				modifiedby='".$babDB->db_escape_string($idcreator)."', 
				state='', 
				index_status='".$babDB->db_escape_string($index_status)."' 
			WHERE 
				id='".$babDB->db_escape_string($arr['id'])."'";
			$babDB->db_query($req);
			$idf = $arr['id'];
		}
		else
		{
			$req = "insert into ".BAB_FILES_TBL." 
			(name, description, keywords, path, id_owner, bgroup, link, readonly, state, created, author, modified, modifiedby, confirmed, index_status) values ";
			$req .= "('" .$babDB->db_escape_string($name). "', '" . $babDB->db_escape_string($description). "', '" . $babDB->db_escape_string($keywords). "', '" .$babDB->db_escape_string($sRelativePath). "', '" . $babDB->db_escape_string($iIdOwner). "', '" . $babDB->db_escape_string($gr). "', '0', '" . $babDB->db_escape_string($readonly). "', '', now(), '" . $babDB->db_escape_string($idcreator). "', now(), '" . $babDB->db_escape_string($idcreator). "', '". $babDB->db_escape_string($confirmed)."', '".$babDB->db_escape_string($index_status)."')";
			$babDB->db_query($req);
			$idf = $babDB->db_insert_id(); 
		}

		$okfiles[] = $idf;

		if(BAB_INDEX_STATUS_INDEXED === $index_status) 
		{
			$obj = new bab_indexObject('bab_files');
			$obj->setIdObjectFile($pathx.$osfname, $idf, $iIdOwner);
		}

		if($gr == 'Y')
		{
			if($confirmed == "Y")
			{
				$GLOBALS['babWebStat']->addNewFile($babBody->currentAdmGroup);
			}
	
			$res = $babDB->db_query("select id from ".BAB_FM_FIELDS_TBL." where id_folder='".$babDB->db_escape_string($id)."'");
			while($arr = $babDB->db_fetch_array($res))
			{
				$fd = 'field'.$arr['id'];
				if(isset($GLOBALS[$fd]))
				{
					$fval = $babDB->db_escape_string($GLOBALS[$fd]);
	
					$res2 = $babDB->db_query("select id from ".BAB_FM_FIELDSVAL_TBL." where id_file='".$babDB->db_escape_string($idf)."' and id_field='".$babDB->db_escape_string($arr['id'])."'");
					if($res2 && $babDB->db_num_rows($res2) > 0)
					{
						$arr2 = $babDB->db_fetch_array($res2);
						$babDB->db_query("update ".BAB_FM_FIELDSVAL_TBL." set fvalue='".$babDB->db_escape_string($fval)."' where id='".$babDB->db_escape_string($arr2['id'])."'");
					}
					else
					{
						$babDB->db_query("insert into ".BAB_FM_FIELDSVAL_TBL." set fvalue='".$babDB->db_escape_string($fval)."', id_file='".$babDB->db_escape_string($idf)."', id_field='".$babDB->db_escape_string($arr['id'])."'");
					}
				}
			}
		}

		if($gr == "Y" && $confirmed == "N")
		{
			if(notifyApprovers($idf, $iIdOwner) && $bnotify)
			{
				fileNotifyMembers($osfname, $path, $iIdOwner, bab_translate("A new file has been uploaded"));
			}
		}
	}

	if(count($errfiles))
	{
		for($k=0; $k < count($errfiles); $k++)
		{
			$babBody->addError($errfiles[$k]['file'].'['.$errfiles[$k]['error'].']');
		}
		return false;
	}
	
	if(!count($okfiles))
	{
		$babBody->msgerror = bab_translate("Please select a file to upload");
		return false;
	}
		
	return true;
}
	
	
	

	
/**
 * Modify a file
 * @param	int			$idf
 * @param	object		$fmFile			bab_fmFile instance
 * @param	string		$fname
 * @param	string		$description
 * @param	string		$keywords
 * @param	Y|N			$readonly
 * @param	Y|N			$confirm
 * @param	Y|N|false	$bnotify
 * @param	int			$newfolder
 * @param	boolean		$descup			Update description & keywords
 */
function saveUpdateFile($idf, $fmFile, $fname, $description, $keywords, $readonly, $confirm, $bnotify, $newfolder, $descup)
	{
	global $babBody, $babDB, $BAB_SESS_USERID;
	
	if ($fmFile) {
		$uploadf_name = $fmFile->filename; 
		$uploadf_size = $fmFile->size;
	} else {
		$uploadf_name = ''; 
		$uploadf_size = '';
	}

	$res = $babDB->db_query("select * from ".BAB_FILES_TBL." where id='".$babDB->db_escape_string($idf)."'");
	if( $res && $babDB->db_num_rows($res))
		{
		$arr = $babDB->db_fetch_array($res);
		if( $arr['bgroup'] == "Y" )
			{
			for( $i = 0; $i < count($babBody->aclfm['id']); $i++)
				{
				if( $babBody->aclfm['id'][$i] == $arr['id_owner'] )
					{
					if ( $babBody->aclfm['upda'][$i] || $babBody->aclfm['ma'][$i] == 1)
						{
						break;
						}
					else
						{
						$arrschi = bab_getWaitingIdSAInstance($GLOBALS['BAB_SESS_USERID']);
						if( count($arrschi) > 0 && in_array($arr['idfai'], $arrschi))
							{
							break;
							}
						else
							{
								$babBody->msgerror = bab_translate("Access denied");
								return false;
							}
						}
					}
				}
			}

		$pathx = bab_getUploadFullPath($arr['bgroup'], $arr['id_owner'], $arr['path']);
		

		if( !file_exists($pathx.$arr['name']))
			{
			$babBody->msgerror = bab_translate("File does'nt exist");
			return false;
			}

		$bmodified = false;
		if( !empty($uploadf_name) && $uploadf_name != "none")
			{
			if( $size > $GLOBALS['babMaxFileSize'])
				{
				$babBody->msgerror = bab_translate("The file was greater than the maximum allowed") ." :". $GLOBALS['babMaxFileSize'];
				return false;
				}
			$totalsize = getDirSize($GLOBALS['babUploadPath']);
			if( $size + $totalsize > $GLOBALS['babMaxTotalSize'])
				{
				$babBody->msgerror = bab_translate("There is not enough free space");
				return false;
				}

			$totalsize = getDirSize($pathx);
			if( $size + $totalsize > ($arr['bgroup'] == "Y"? $GLOBALS['babMaxGroupSize']: $GLOBALS['babMaxUserSize']))
				{
				$babBody->msgerror = bab_translate("There is not enough free space");
				return false;
				}

			if( isset($GLOBALS['babFileNameTranslation'])) {
				$uploadf_name = strtr($uploadf_name, $GLOBALS['babFileNameTranslation']);
			}

			if( !$fmFile->import($pathx.$arr['name']))
				{
				$babBody->msgerror = bab_translate("The file could not be uploaded");
				return false;
				}
			$bmodified = true;
			}

		$fname = trim($fname);
		$frename = false;
		$osfname = $fname;

		if( !empty($fname) && strcmp($arr['name'], $osfname))
			{
			if( isset($GLOBALS['babFileNameTranslation']))
				$osfname = strtr($osfname, $GLOBALS['babFileNameTranslation']);
			if( rename($pathx.$arr['name'], $pathx.$osfname))
				{
				$frename = true;
				if( is_dir($pathx.BAB_FVERSION_FOLDER."/"))
					{
					$res = $babDB->db_query("select * from ".BAB_FM_FILESVER_TBL." where id_file='".$babDB->db_escape_string($idf)."'");
					while($rr = $babDB->db_fetch_array($res))
						{
						$filename = $rr['ver_major'].",".$rr['ver_minor'].",".$osfname;
						rename($pathx.BAB_FVERSION_FOLDER."/".$rr['ver_major'].",".$rr['ver_minor'].",".$arr['name'], $pathx.BAB_FVERSION_FOLDER."/".$rr['ver_major'].",".$rr['ver_minor'].",".$osfname);
						}
					}
				}
			}



		if( empty($BAB_SESS_USERID))
			$idcreator = 0;
		else
			$idcreator = $BAB_SESS_USERID;
	
		$tmp = array();
		if( $descup )
			{
			$tmp[] = "description='".$babDB->db_escape_string($description)."'";
			$tmp[] = "keywords='".$babDB->db_escape_string($keywords)."'";
			}
		if( $bmodified)
			{
			$tmp[] = "modified=now()";
			$tmp[] = "modifiedby='".$babDB->db_escape_string($idcreator)."'";
			}
		if( $frename)
			{
			$tmp[] = "name='".$babDB->db_escape_string($fname)."'";
			}
		else
			{
			$osfname = $arr['name'];
			}

		if( !empty($readonly))
			{
			if( $readonly != 'Y' ) 
				{
				$readonly = 'N';
				}
			$tmp[] = "readonly='".$babDB->db_escape_string($readonly)."'";
			}

		if( !empty($newfolder))
			{
			$pathxnew = bab_getUploadFullPath($arr['bgroup'], $newfolder);
			if(!is_dir($pathxnew))
				{
				bab_mkdir($pathxnew, $GLOBALS['babMkdirMode']);
				}

			if( rename( $pathx.$osfname, $pathxnew.$osfname))
				{
				$babDB->db_query("delete from ".BAB_FM_FIELDSVAL_TBL." where id_file='".$babDB->db_escape_string($idf)."'");
				$tmp[] = "id_owner='".$babDB->db_escape_string($newfolder)."'";
				$tmp[] = "path=''";
				$arr['id_owner'] = $newfolder;

				if( is_dir($pathx.BAB_FVERSION_FOLDER."/"))
					{
					if( !is_dir($pathxnew.BAB_FVERSION_FOLDER."/"))
						{
						bab_mkdir($pathxnew.BAB_FVERSION_FOLDER, $GLOBALS['babMkdirMode']);
						}

					$res = $babDB->db_query("select * from ".BAB_FM_FILESVER_TBL." where id_file='".$babDB->db_escape_string($idf)."'");
					while($rr = $babDB->db_fetch_array($res))
						{
						$filename = $rr['ver_major'].",".$rr['ver_minor'].",".$osfname;
						rename($pathx.BAB_FVERSION_FOLDER."/".$filename, $pathxnew.BAB_FVERSION_FOLDER."/".$filename);
						}
					}

				}
			}

		if( count($tmp) > 0 )
			{
			$babDB->db_query("update ".BAB_FILES_TBL." set ".implode(", ", $tmp)." where id='".$babDB->db_escape_string($idf)."'");
			}

		if( $arr['bgroup'] == 'Y')
			{
			$res = $babDB->db_query("select id from ".BAB_FM_FIELDS_TBL." where id_folder='".$babDB->db_escape_string($arr['id_owner'])."'");
			while($arrf = $babDB->db_fetch_array($res))
				{
				$fd = 'field'.$arrf['id'];
				if( isset($GLOBALS[$fd]) )
					{

					$fval = $babDB->db_escape_string($GLOBALS[$fd]);

					$res2 = $babDB->db_query("select id from ".BAB_FM_FIELDSVAL_TBL." where id_file='".$babDB->db_escape_string($idf)."' and id_field='".$babDB->db_escape_string($arrf['id'])."'");
					if( $res2 && $babDB->db_num_rows($res2) > 0)
						{
						$arr2 = $babDB->db_fetch_array($res2);
						$babDB->db_query("update ".BAB_FM_FIELDSVAL_TBL." set fvalue='".$babDB->db_escape_string($fval)."' where id='".$babDB->db_escape_string($arr2['id'])."'");
						}
					else
						{
						$babDB->db_query("insert into ".BAB_FM_FIELDSVAL_TBL." set fvalue='".$babDB->db_escape_string($fval)."', id_file='".$babDB->db_escape_string($idf)."', id_field='".$babDB->db_escape_string($arrf['id'])."'");
						}
					}
				}
			}

		$rr = $babDB->db_fetch_array($babDB->db_query("select filenotify, id_dgowner from ".BAB_FM_FOLDERS_TBL." where id='".$babDB->db_escape_string($arr['id_owner'])."'"));
		if( empty($bnotify))
			{
			$bnotify = $rr['filenotify'];
			}
		if( $arr['bgroup'] == "Y" )
			{
			if( $arr['confirmed'] == "N")
				{
				include_once $GLOBALS['babInstallPath']."utilit/afincl.php";
				$res = updateFlowInstance($arr['idfai'], $GLOBALS['BAB_SESS_USERID'], $confirm == "Y"? true: false);
				switch($res)
					{
					case 0:
						deleteFile($arr['id'], $arr['name'], $pathx);
						unlink($pathx.$arr['name']);
						notifyFileAuthor(bab_translate("Your file has been refused"),"", $arr['author'], $arr['name']);
						break;
					case 1:
						deleteFlowInstance($arr['idfai']);
						$babDB->db_query("update ".BAB_FILES_TBL." set confirmed='Y', idfai='0' where id = '".$babDB->db_escape_string($arr['id'])."'");
						$GLOBALS['babWebStat']->addNewFile($rr['id_dgowner']);
						notifyFileAuthor(bab_translate("Your file has been accepted"),"", $arr['author'], $arr['name']);
						if( $bnotify == "Y")
							{
							fileNotifyMembers($arr['name'], $arr['path'], $arr['id_owner'], bab_translate("A new file has been uploaded"));
							}
						break;
					default:
						$nfusers = getWaitingApproversFlowInstance($arr['idfai'], true);
						if( count($nfusers) > 0 )
							notifyFileApprovers($arr['id'], $nfusers, bab_translate("A new file is waiting for you"));
						break;
					}
				}
			else if( $bnotify == "Y" && $bmodified) {
				fileNotifyMembers($arr['name'], $arr['path'], $arr['id_owner'], bab_translate("File has been updated"));
				}
			}
		return true;
		}
		
	// the file does not exists
	return false;
	}




/**
 * Get file array and access rights on the file
 * For versionning
 */
function fm_getFileAccess($idf) 
{
	static $result = array();
	
	if(!isset($result[$idf])) 
	{
		$bupdate = false;
		$bdownload = false;
		$lockauthor = 0;

		$oFmFolder = null;
		$oFolderFile = null;
		
		$oFolderFileSet = new BAB_FolderFileSet();
		
		$oId =& $oFolderFileSet->aField['iId'];
		$oState =& $oFolderFileSet->aField['sState'];
				
		$oCriteria = $oId->in($idf);
		$oCriteria = $oCriteria->_and($oState->in(''));
		$oFolderFile = $oFolderFileSet->get($oCriteria);
		
		if(!is_null($oFolderFile))
		{
			if('Y' === $oFolderFile->getGroup() && 'Y' === $oFolderFile->getConfirmed())			
			{
				$oFmFolderSet = new BAB_FmFolderSet();
				
				$oId =& $oFmFolderSet->aField['iId'];
				$oFmFolder = $oFmFolderSet->get($oId->in($oFolderFile->getOwnerId()));
				if(!is_null($oFmFolder))
				{
					if('Y' === $oFmFolder->getVersioning())					
					{
						if(bab_isAccessValid(BAB_FMMANAGERS_GROUPS_TBL, $oFmFolder->getId()) || bab_isAccessValid(BAB_FMUPDATE_GROUPS_TBL, $oFmFolder->getId()))
						{
							$bupdate = true;
							if(0 !== $oFolderFile->getUserEditId()) 
							{
								$oFolderFileVersionSet = new BAB_FolderFileVersionSet();
				
								$oId =& $oFmFolderSet->aField['iId'];
								$oFolderFileVersion = $oFolderFileVersionSet->get($oId->in($oFolderFile->getUserEditId()));
								if(!is_null($oFolderFileVersion))
								{
									$lockauthor = $oFolderFileVersion->getAuthorId();
								}
							}
						}
		
						if(bab_isAccessValid(BAB_FMDOWNLOAD_GROUPS_TBL, $oFmFolder->getId())) 
						{
							$bdownload = true;
						}
					}
				}
			}
			else if('N' === $oFolderFile->getGroup()) 
			{
				if($GLOBALS['babBody']->ustorage && !empty($BAB_SESS_USERID) && $BAB_SESS_USERID == $oFmFolder->getId()) 
				{
					$bupdate = true;
					$bdownload = true;
				}
			}
		}
		
		$result[$idf] = array(
			'oFolderFile' => $oFolderFile,
			'oFmFolder' => $oFmFolder,
			'bupdate' => $bupdate,
			'bdownload' => $bdownload,
			'lockauthor' => $lockauthor
		);
	}
	
	return $result[$idf];
		
		
/*		
		global $babDB;
	
		$res = $babDB->db_query("select * from ".BAB_FILES_TBL." where id='".$babDB->db_escape_string($idf)."' and state=''");
		if( $res && $babDB->db_num_rows($res) > 0 )
		{
			$arrfile = $babDB->db_fetch_assoc($res);
			if( $arrfile['bgroup'] == 'Y' && $arrfile['confirmed'] == 'Y')
			{
				$res = $babDB->db_query("select * from ".BAB_FM_FOLDERS_TBL." where id='".$babDB->db_escape_string($arrfile['id_owner'])."'");
				$arrfold = $babDB->db_fetch_array($res);
	
				if( $arrfold['version'] ==  'Y' )
				{
					if(bab_isAccessValid(BAB_FMMANAGERS_GROUPS_TBL, $arrfold['id']) || bab_isAccessValid(BAB_FMUPDATE_GROUPS_TBL, $arrfile['id_owner']))
					{
						$bupdate = true;
						if( $arrfile['edit'] != 0 ) {
							list($lockauthor) = $babDB->db_fetch_array($babDB->db_query("select author from ".BAB_FM_FILESVER_TBL." where id='".$babDB->db_escape_string($arrfile['edit'])."'"));
							}
					}
	
					if(bab_isAccessValid(BAB_FMDOWNLOAD_GROUPS_TBL, $arrfile['id_owner'])) {
						$bdownload = true;
					}
				}
			} elseif ('N' == $arrfile['bgroup']) {
			
				
				
				if( $GLOBALS['babBody']->ustorage && !empty($BAB_SESS_USERID) && $BAB_SESS_USERID == $arrfile['id_owner'] ) {
					$bupdate = true;
					$bdownload = true;
				}
			}
		}
		
		$result[$idf] = array(
			'arrfile' => $arrfile,
			'arrfold' => $arrfold,
			'bupdate' => $bupdate,
			'bdownload' => $bdownload,
			'lockauthor' => $lockauthor
		);
	}
	
	return $result[$idf];
//*/
}



/**
 * Lock a file
 * Versionning
 * @param	int		$idf
 * @param	string	$comment
 */
function fm_lockFile($idf, $comment)
{
	global $babBody, $babDB;
	
	$fm_file = fm_getFileAccess($idf);
	$oFolderFile =& $fm_file['oFolderFile'];

	if(!is_null($oFolderFile))
	{
		if(0 === $oFolderFile->getUserEditId() && $GLOBALS['BAB_SESS_USERID'] != '')
		{
			$oFolderFileLog = new BAB_FolderFileLog();
			$oFolderFileLog->setIdFile($idf);
			$oFolderFileLog->setCreationDate(date("Y-m-d H:i:s"));
			$oFolderFileLog->setAuthorId($GLOBALS['BAB_SESS_USERID']);
			$oFolderFileLog->setAction(BAB_FACTION_EDIT);
			$oFolderFileLog->setComment($comment);
			$oFolderFileLog->setVersion($oFolderFile->getMajorVer() . '.' . $oFolderFile->getMinorVer());
			$oFolderFileLog->save();

			$oFolderFileVersion = new BAB_FolderFileVersion();
			$oFolderFileVersion->setIdFile($idf);
			$oFolderFileVersion->setCreationDate(date("Y-m-d H:i:s"));
			$oFolderFileVersion->setAuthorId($GLOBALS['BAB_SESS_USERID']);
			$oFolderFileVersion->setMajorVer($oFolderFile->getMajorVer());
			$oFolderFileVersion->setMinorVer($oFolderFile->getMinorVer());
			$oFolderFileVersion->setConfirmed('');
			$oFolderFileVersion->setFlowApprobationInstanceId(0);
			$oFolderFileVersion->setConfirmed('N');
			$oFolderFileVersion->setStatusIndex(0);
			$oFolderFileVersion->save();
			
			bab_debug('Version ==> ' . $oFolderFileVersion->getId());

			$oFolderFile->setUserEditId($oFolderFileVersion->getId());
			$oFolderFile->save();
		}
	}
}



/**
 * Unlock a file
 * Versionning
 * @param	int		$idf
 * @param	string	$comment
 */
function fm_unlockFile($idf, $comment )
{
	global $babBody, $babDB;
	
	$fm_file = fm_getFileAccess($idf);
	$oFolderFile =& $fm_file['oFolderFile'];
	$oFmFolder =& $fm_file['oFmFolder'];
	$lockauthor = $fm_file['lockauthor'];

	if(!is_null($oFmFolder) && !is_null($oFolderFile))
	{
		if(0 !== $oFolderFile->getUserEditId() && $GLOBALS['BAB_SESS_USERID'] != '')
		{
			if($lockauthor == $GLOBALS['BAB_SESS_USERID'] || bab_isAccessValid(BAB_FMMANAGERS_GROUPS_TBL, $oFmFolder->getId()))
			{
				$oFolderFileLog = new BAB_FolderFileLog();
				$oFolderFileLog->setIdFile($idf);
				$oFolderFileLog->setCreationDate(date("Y-m-d H:i:s"));
				$oFolderFileLog->setAuthorId($GLOBALS['BAB_SESS_USERID']);
				$oFolderFileLog->setAction(BAB_FACTION_UNEDIT);
				$oFolderFileLog->setComment($comment);
				$oFolderFileLog->setVersion($oFolderFile->getMajorVer() . '.' . $oFolderFile->getMinorVer());
				$oFolderFileLog->save();
				
				$oFolderFileVersionSet = new BAB_FolderFileVersionSet();
				$oId =& $oFolderFileVersionSet->aField['iId'];
				$oFolderFileVersion = $oFolderFileVersionSet->get($oId->in($oFolderFile->getUserEditId()));

				if(!is_null($oFolderFileVersion) && 0 !== $oFolderFileVersion->getFlowApprobationInstanceId())
				{
					include_once $GLOBALS['babInstallPath']."utilit/afincl.php";
					deleteFlowInstance($oFolderFileVersion->getFlowApprobationInstanceId());
					
					$sUploadPath = BAB_FmFolderHelper::getUploadPath();
					
					$sFullPathName = $sUploadPath . BAB_FVERSION_FOLDER . '/' . 
						$oFolderFileVersion->getMajorVer() . '.' . $oFolderFileVersion->getMinorVer() . ',' . $oFolderFile->getName();	
						
					unlink($sFullPathName);
				}
	
				$oFolderFileVersionSet->remove($oId->in($oFolderFile->getUserEditId()));
				
				$oFolderFile->setUserEditId(0);
				$oFolderFile->save();
			}
		}
	}
}



/**
 * Commit a file
 * versionning
 * @param	int		$idf
 * @param	string	$comment
 * @param	Y|N		$vermajor
 * @param	object	$fmFile			bab_fmFile instance
 *
 * @return boolean
 */
function fm_commitFile($idf, $comment, $vermajor, $fmFile)
{
	global $babBody, $babDB;
	
	if ($fmFile) {
		$filename = $fmFile->filename;
		$size = $fmFile->size;
	} else {
		$filename = '';
		$size = 0;
	}
	
	$fm_file = fm_getFileAccess($idf);
	$arrfile = $fm_file['arrfile'];
	$arrfold = $fm_file['arrfold'];
	$lockauthor = $fm_file['lockauthor'];

	if( $lockauthor != $GLOBALS['BAB_SESS_USERID'])
		{
		$babBody->msgerror = bab_translate("Access denied");
		return false;
		}

	if( empty($filename) || $filename == "none")
		{
		$babBody->msgerror = bab_translate("Please select a file to upload");
		return false;
		}

	if( $size > $GLOBALS['babMaxFileSize'])
		{
		$babBody->msgerror = bab_translate("The file was greater than the maximum allowed") ." :". $GLOBALS['babMaxFileSize'];
		return false;
		}

	$totalsize = getDirSize($GLOBALS['babUploadPath']);
	if( $size + $totalsize > $GLOBALS['babMaxTotalSize'])
		{
		$babBody->msgerror = bab_translate("There is not enough free space");
		return false;
		}

	$pathx = bab_getUploadFullPath($arrfile['bgroup'], $arrfile['id_owner']);
	$pathy = bab_getUploadFmPath($arrfile['bgroup'], $arrfile['id_owner']);

return;	
	
	$totalsize = getDirSize($pathx);
	if( $size + $totalsize > $GLOBALS['babMaxGroupSize'] )
		{
		$babBody->msgerror = bab_translate("There is not enough free space");
		return false;
		}

	if( substr($arrfile['path'], -1) == "/")
		$pathx .= substr($arrfile['path'], 0 , -1);
	else if( !empty($arrfile['path']))
		$pathx .= $arrfile['path']."/";

	if( !is_dir($pathx.BAB_FVERSION_FOLDER))
		bab_mkdir($pathx.BAB_FVERSION_FOLDER, $GLOBALS['babMkdirMode']);

	if( $vermajor == 'Y' )
		{
		$vmajor = ($arrfile['ver_major'] + 1);
		$vminor = 0;
		}
	else
		{
		$vmajor = $arrfile['ver_major'];
		$vminor = ($arrfile['ver_minor'] + 1);
		}


	if( !$fmFile->import($pathx.BAB_FVERSION_FOLDER."/".$vmajor.",".$vminor.",".$arrfile['name']))
		{
		$babBody->msgerror = bab_translate("The file could not be uploaded");
		return false;
		}

	if( $arrfold['idsa'] != 0 )
		{
		include_once $GLOBALS['babInstallPath']."utilit/afincl.php";
		if( $arrfold['auto_approbation'] == 'Y' )
			{
			$idfai = makeFlowInstance($arrfold['idsa'], "filv-".$arrfile['edit'], $GLOBALS['BAB_SESS_USERID']);
			}
		else
			{
			$idfai = makeFlowInstance($arrfold['idsa'], "filv-".$arrfile['edit']);
			}
		}

	if( $arrfold['idsa'] == 0 || $idfai === true)
		{
		$babDB->db_query("insert into ".BAB_FM_FILESLOG_TBL." ( id_file, date, author, action, comment, version) values ('".$babDB->db_escape_string($idf)."', now(), '".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."', '".BAB_FACTION_COMMIT."', '".$babDB->db_escape_string($comment)."', '".$babDB->db_escape_string($vmajor).".".$babDB->db_escape_string($vminor)."')");

		copy($pathx.$arrfile['name'], $pathx.BAB_FVERSION_FOLDER."/".$arrfile['ver_major'].",".$arrfile['ver_minor'].",".$arrfile['name']);
		copy($pathx.BAB_FVERSION_FOLDER."/".$vmajor.",".$vminor.",".$arrfile['name'], $pathx.$arrfile['name']);
		unlink($pathx.BAB_FVERSION_FOLDER."/".$vmajor.",".$vminor.",".$arrfile['name']);

		// index

		include_once $GLOBALS['babInstallPath']."utilit/indexincl.php";
		$index_status = bab_indexOnLoadFiles(
			array($pathx.$arrfile['name'],  $pathx.BAB_FVERSION_FOLDER."/".$arrfile['ver_major'].",".$arrfile['ver_minor'].",".$arrfile['name']),
			'bab_files'
		);

		$babDB->db_query("update ".BAB_FILES_TBL." set edit='0', modified=now(), modifiedby='".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."', ver_major='".$babDB->db_escape_string($vmajor)."', ver_minor='".$babDB->db_escape_string($vminor)."', ver_comment='".$babDB->db_escape_string($comment)."', index_status='".$babDB->db_escape_string($index_status)."' where id='".$babDB->db_escape_string($idf)."'");

		

		$babDB->db_query("update ".BAB_FM_FILESVER_TBL." set ver_major='".$babDB->db_escape_string($arrfile['ver_major'])."', ver_minor='".$babDB->db_escape_string($arrfile['ver_minor'])."', comment='".$babDB->db_escape_string($arrfile['ver_comment'])."', idfai='0', confirmed='Y', index_status='".$babDB->db_escape_string($index_status)."' where id='".$babDB->db_escape_string($arrfile['edit'])."'");

		if (BAB_INDEX_STATUS_INDEXED === $index_status) {
			$obj = new bab_indexObject('bab_files');
			$obj->setIdObjectFile($pathy.$arrfile['name'], $idf, $arrfile['id_owner']);
		
			$obj->setIdObjectFile($pathy.BAB_FVERSION_FOLDER."/".$arrfile['ver_major'].",".$arrfile['ver_minor'].",".$arrfile['name'], $idf, $arrfile['id_owner']);
		}

		if( $arrfold['filenotify'] == 'Y' )
			fileNotifyMembers($filename, $arrfile['path'], $arrfile['id_owner'], bab_translate("A new version file has been uploaded"));
		}
	elseif(!empty($idfai))
		{
		$babDB->db_query("insert into ".BAB_FM_FILESLOG_TBL." ( id_file, date, author, action, comment, version) values ('".$babDB->db_escape_string($idf)."', now(), '".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."', '".BAB_FACTION_COMMIT."', '".$babDB->db_escape_string(bab_translate("Waiting to be validate"))."', '".$babDB->db_escape_string($vmajor).".".$babDB->db_escape_string($vminor)."')");

		$babDB->db_query("update ".BAB_FM_FILESVER_TBL." set ver_major='".$babDB->db_escape_string($vmajor)."', ver_minor='".$babDB->db_escape_string($vminor)."', comment='".$babDB->db_escape_string($comment)."', idfai='0' where id='".$babDB->db_escape_string($arrfile['edit'])."'");

		$babDB->db_query("update ".BAB_FM_FILESVER_TBL." set idfai='".$babDB->db_escape_string($idfai)."' where id='".$babDB->db_escape_string($arrfile['edit'])."'");
		$nfusers = getWaitingApproversFlowInstance($idfai, true);
		notifyFileApprovers($arrfile['id'], $nfusers, bab_translate("A new version file is waiting for you"));
		}
		
	return true;
}







/**
 * Index all files of file manager
 * @param 	array 	$status
 * @param 	boolean $prepare
 */
function indexAllFmFiles($status, $prepare) {
	
	global $babDB;

	$res = $babDB->db_query("
	
		SELECT 
			f.id,
			f.name,
			f.path, 
			f.id_owner, 
			f.bgroup, 
			d.id version 

		FROM 
			".BAB_FILES_TBL." f 
			LEFT JOIN ".BAB_FM_FOLDERS_TBL." d ON d.id = f.id_owner AND f.bgroup ='Y' AND d.version ='Y'
		WHERE 
			f.index_status IN(".$babDB->quote($status).")
		
	");

	
	$files = array();
	$rights = array();

	while ($arr = $babDB->db_fetch_assoc($res)) {

		$pathx = bab_getUploadFullPath($arr['bgroup'], $arr['id_owner'], $arr['path']);
		$pathy = bab_getUploadFmPath($arr['bgroup'], $arr['id_owner']);

		if (!empty($arr['path'])) {
			$arr['path'] .= '/';
		}

		$files[] = $pathx.$arr['name'];
		$rights[$pathy.$arr['path'].$arr['name']] = array(
			'id' => $arr['id'],
			'id_owner' => $arr['id_owner']
			);

		if (null != $arr['version']) {
			$resv = $babDB->db_query("
			
				SELECT 
					id,	
					ver_major, 
					ver_minor 
				FROM ".BAB_FM_FILESVER_TBL." 
				WHERE 
					id_file='".$babDB->db_escape_string($arr['id'])."' 
					AND index_status IN(".$babDB->quote($status).")
			");

			while ($arrv = $babDB->db_fetch_assoc($resv)) {
				if( is_dir($pathx.BAB_FVERSION_FOLDER)) {
					$file = BAB_FVERSION_FOLDER."/".$arrv['ver_major'].",".$arrv['ver_minor'].",".$arr['name'];
					$files[] = $pathx.$file;
					$rights[$pathy.$file] = array(
						'id' => $arrv['id'],
						'id_owner' => $arr['id_owner']
						);
				}
			}
		}
	}

	include_once $GLOBALS['babInstallPath']."utilit/indexincl.php";


	if (!$files) {
		$r = new bab_indexReturn;
		$r->addError(bab_translate("No files to index in the file manager"));
		$r->result = false;
		return $r;
	}



	
	$obj = new bab_indexObject('bab_files');

	

	$param = array(
			'status' => $status,
			'rights' => $rights
		);
	
	if (in_array(BAB_INDEX_STATUS_INDEXED, $status)) {
		if ($prepare) {
			return $obj->prepareIndex($files, $GLOBALS['babInstallPath'].'utilit/fileincl.php', 'indexAllFmFiles_end', $param );
		} else {
			$r = $obj->resetIndex($files);
		}
	} else {
		$r = $obj->addFilesToIndex($files);
	}

	if (true === $r->result) {
		indexAllFmFiles_end($param);
	}

	return $r;
}




function indexAllFmFiles_end($param) {

	global $babDB;
	$obj = new bab_indexObject('bab_files');

	$res = $babDB->db_query("
		UPDATE ".BAB_FILES_TBL." SET index_status='".BAB_INDEX_STATUS_INDEXED."'
		WHERE 
			index_status IN(".$babDB->quote($param['status']).")
	");


	$res = $babDB->db_query("
		UPDATE ".BAB_FM_FILESVER_TBL." SET index_status='".BAB_INDEX_STATUS_INDEXED."'
		WHERE 
			index_status IN(".$babDB->quote($param['status']).")
	");

	foreach($param['rights'] as $f => $arr) {
		$obj->setIdObjectFile($f, $arr['id'], $arr['id_owner']);
	}

	return true;
}




function notifyApprovers($id, $fid)
	{
	global $babBody, $babDB;

	$arr = $babDB->db_fetch_array($babDB->db_query("select idsa, auto_approbation from ".BAB_FM_FOLDERS_TBL." where id='".$babDB->db_escape_string($fid)."'"));

	if( $arr['idsa'] !=  0 )
		{
		include_once $GLOBALS['babInstallPath']."utilit/afincl.php";
		if( $arr['auto_approbation'] == 'Y' )
			{
			$idfai = makeFlowInstance($arr['idsa'], "fil-".$id, $GLOBALS['BAB_SESS_USERID']);
			}
		else
			{
			$idfai = makeFlowInstance($arr['idsa'], "fil-".$id);
			}
		}

	if( $arr['idsa'] ==  0 || $idfai === true)
		{
		$babDB->db_query("update ".BAB_FILES_TBL." set confirmed='Y' where id='".$babDB->db_escape_string($id)."'");
		$GLOBALS['babWebStat']->addNewFile($babBody->currentAdmGroup);
		return true;
		}
	elseif(!empty($idfai))
		{
		$babDB->db_query("update ".BAB_FILES_TBL." set idfai='".$babDB->db_escape_string($idfai)."' where id='".$babDB->db_escape_string($id)."'");
		$nfusers = getWaitingApproversFlowInstance($idfai, true);
		if( count($nfusers))
			{
			notifyFileApprovers($id, $nfusers, bab_translate("A new file is waiting for you"));
			}
		$babBody->msgerror = bab_translate("Your file is waiting for approval");
		}
	
	return false;
	}

	
	
	
	
	









class BAB_BaseSet extends BAB_MySqlResultIterator 
{
	var $aField = array();
	var $sTableName = '';
	var $bUseAlias = true;
	
	function BAB_BaseSet($sTableName)
	{
		parent::BAB_MySqlResultIterator();
		$this->sTableName = $sTableName;
	}

	function processWhereClause($oCriteria)
	{
//		require_once $GLOBALS['babInstallPath'] . 'utilit/devtools.php';
//		bab_debug_print_backtrace();
		if(!is_null($oCriteria))
		{
			$sWhereClause = $oCriteria->toString();
			if(strlen(trim($sWhereClause)) > 0)
			{
				return 'WHERE ' . $sWhereClause;
			}
		}
		return '';
	}
	
	function processOrder($aOrder)
	{
		$sOrder = '';
		if(count($aOrder) > 0)
		{
			$aValue = array();
			foreach($aOrder as $sKey => $sValue)
			{
				$aValue[] = $this->aField[$sKey]->getName() . ' ' . $sValue;
			}
			$sOrder = 'ORDER BY ' . implode(', ', $aValue);
		}
		return $sOrder;
	}
	
	function processLimit($aLimit)
	{
		$sLimit = '';
		$iCount = count($aLimit);
		if($iCount >= 1 && $iCount <= 2)
		{
			global $babDB;
			
			$aValue = array();
			foreach($aLimit as $sValue)
			{
				$aValue[] = $babDB->db_escape_string($sValue);
			}
			$sLimit = 'LIMIT ' . implode(', ', $aValue);
		}
		return $sLimit;
	}
	
	function save($oObject)
	{
		if(count($this->aField) > 0)
		{
			global $babDB;
			
			$aInto = array();
			$aValue = array();
			$aOnDuplicateKey = array();
	
			reset($this->aField);
			//Primary key processing
			$aItem = each($this->aField);
			if(false !== $aItem)
			{
				$aInto[] = $aItem['value']->getName();
				
				$oObject->_get($aItem['key'], $iId);
				$aValue[] = (is_null($iId)) ? '\'\'' : '\'' . $babDB->db_escape_string($iId) . '\'';
			}
			
			while(false !== ($aItem = each($this->aField)))
			{
				$sColName = $aItem['value']->getName();
				$aInto[] = $sColName;
				
				$sKey = $aItem['key'];
				$oObject->_get($sKey, $sValue);
				
				$sValue = '\'' . $babDB->db_escape_string($sValue) . '\'';
				$aValue[] = $sValue;
				$aOnDuplicateKey[] = $sColName . '= ' . $sValue;
			}
			reset($this->aField);
			
			$sQuery = 
				'INSERT INTO ' . $this->sTableName . ' ' .
					'(' . implode(',', $aInto) . ') ' .
				'VALUES ' . 
					'(' . implode(',', $aValue) . ') ' .
				'ON DUPLICATE KEY UPDATE ' .
					implode(',', $aOnDuplicateKey);
					
			bab_debug($sQuery);
			$oResult = $babDB->db_query($sQuery);
			if(false !== $oResult)
			{
				$oObject->_get('iId', $iId);
				if(is_null($iId))
				{
					$oObject->_set('iId', $babDB->db_insert_id());
				}
				return true;
			}
			return false;
		}
	}
	
	function remove($oCriteria)
	{
		$sWhereClause = $this->processWhereClause($oCriteria);
		if(strlen($sWhereClause) > 0)
		{
			global $babDB;
			$sQuery = 'DELETE FROM ' . $this->sTableName . ' ' . $sWhereClause;
//			bab_debug($sQuery);
			return $babDB->db_query($sQuery);
		}
		return false;
	}

	
	public function getSelectQuery($oCriteria = null, $aOrder = array(), $aLimit = array())
	{
		$sWhereClause = $this->processWhereClause($oCriteria);
		$sOrder = $this->processOrder($aOrder);
		$sLimit = $this->processLimit($aLimit);
		
		$aField = array();

		if(true === $this->bUseAlias)
		{
			foreach($this->aField as $sKey => $oField)
			{
				$aField[] = $oField->getName() . ' ' . $sKey;
			}
		}
		else 
		{
			foreach($this->aField as $sKey => $oField)
			{
				$aField[] = $oField->getName() . ' ';
			}
		}

		$sQuery = 
			'SELECT ' .
				implode(', ', $aField) . ' ' .
			'FROM ' .
				$this->sTableName . ' ' .
			$sWhereClause . ' ' . $sOrder . ' ' . $sLimit;
				
		bab_debug($sQuery);	
		return $sQuery;
	}
	
	function get($oCriteria = null)
	{
//		require_once $GLOBALS['babInstallPath'] . 'utilit/devtools.php';
//		bab_debug_print_backtrace();

		$sQuery = $this->getSelectQuery($oCriteria);
		
		global $babDB;	
		$oResult = $babDB->db_query($sQuery);
		$iNumRows = $babDB->db_num_rows($oResult);
		$iIndex = 0;
		
		if($iIndex < $iNumRows)
		{
			$this->setMySqlResult($oResult);
			return $this->next();
		}
		return null;
	}	
	
	function select($oCriteria = null, $aOrder = array(), $aLimit = array())
	{
		$sQuery = $this->getSelectQuery($oCriteria, $aOrder, $aLimit);
		global $babDB;	
		$oResult = $babDB->db_query($sQuery);
		$this->setMySqlResult($oResult);
		return $this;
	}	
	
	function getObject($aDatas)
	{
		$sClass = substr(get_class($this), 0, -3);
		$oOBject = new $sClass();
		
		foreach($aDatas as $sKey => $sValue)
		{
			$oOBject->_set($sKey, $sValue);
		}
		return $oOBject;
	}
}


class BAB_FmFolderSet extends BAB_BaseSet
{
	function BAB_FmFolderSet()
	{
		parent::BAB_BaseSet(BAB_FM_FOLDERS_TBL);

		$this->aField = array(
			'iId' => new BAB_IntField('`id`'),
			'sName' => new BAB_StringField('`folder`'),
			'sRelativePath' => new BAB_StringField('`sRelativePath`'),
			'iIdApprobationScheme' => new BAB_IntField('`idsa`'),
			'sFileNotify' => new BAB_StringField('`filenotify`'),
			'sActive' => new BAB_IntField('`active`'),
			'sVersioning' => new BAB_StringField('`version`'),
			'iIdDgOwner' => new BAB_IntField('`id_dgowner`'),
			'sHide' => new BAB_StringField('`bhide`'),
			'sAutoApprobation' => new BAB_StringField('`auto_approbation`')
			);
	}
}


class BAB_FolderFileSet extends BAB_BaseSet 
{
	function BAB_FolderFileSet()
	{
		parent::BAB_BaseSet(BAB_FILES_TBL);

		$this->aField = array(
			'iId' => new BAB_IntField('`id`'),
			'sName' => new BAB_StringField('`name`'),
			'sDescription' => new BAB_StringField('`description`'),
			'sKeywords' => new BAB_StringField('`keywords`'),
			'sPathName' => new BAB_StringField('`path`'),
			'iIdOwner' => new BAB_IntField('`id_owner`'),
			'sGroup' => new BAB_StringField('`bgroup`'),
			'iIdLink' => new BAB_IntField('`link`'),
			'sReadOnly' => new BAB_StringField('`readonly`'),
			'sState' => new BAB_StringField('`state`'),
			'sCreation' => new BAB_StringField('`created`'),
			'iIdAuthor' => new BAB_IntField('`author`'),
			'sModified' => new BAB_StringField('`modified`'),
			'iIdModifier' => new BAB_IntField('`modifiedby`'),
			'sConfirmed' => new BAB_StringField('`confirmed`'),
			'iHits' => new BAB_IntField('`hits`'),
			'iIdApprobationInstance' => new BAB_IntField('`idfai`'),
			'iIdUserEdit' => new BAB_IntField('`edit`'),
			'iVerMajor' => new BAB_IntField('`ver_major`'),
			'iVerMinor' => new BAB_IntField('`ver_minor`'),
			'sVerComment' => new BAB_StringField('`ver_comment`'),
			'iIndexStatus' => new BAB_IntField('`index_status`')
			);
	}
}


class BAB_FolderFileVersionSet extends BAB_BaseSet 
{
	function BAB_FolderFileVersionSet()
	{
		parent::BAB_BaseSet(BAB_FM_FILESVER_TBL);

		$this->aField = array(
			'iId' => new BAB_IntField('`id`'),
			'iIdFile' => new BAB_IntField('`id_file`'),
			'sCreationDate' => new BAB_StringField('`date`'),
			'iIdAuthor' => new BAB_IntField('`author`'),
			'iVerMajor' => new BAB_IntField('`ver_major`'),
			'iVerMinor' => new BAB_IntField('`ver_minor`'),
			'sComment' => new BAB_StringField('`comment`'),
			'iIdFlowApprobationInstance' => new BAB_IntField('`idfai`'),
			'sConfirmed' => new BAB_StringField('`confirmed`'),
			'iIndexStatus' => new BAB_IntField('`index_status`')
			);
	}
}

class BAB_FolderFileLogSet extends BAB_BaseSet 
{
	function BAB_FolderFileLogSet()
	{
		parent::BAB_BaseSet(BAB_FM_FILESLOG_TBL);

		$this->aField = array(
			'iId' => new BAB_IntField('`id`'),
			'iIdFile' => new BAB_IntField('`id_file`'),
			'sCreationDate' => new BAB_StringField('`date`'),
			'iIdAuthor' => new BAB_IntField('`author`'),
			'iAction' => new BAB_IntField('`action`'),
			'sComment' => new BAB_StringField('`comment`'),
			'sVersion' => new BAB_StringField('`version`')
			);
	}
}


class BAB_DbRecord
{
	var $aDatas = null;
	
	function BAB_DbRecord()
	{
		
	}
	
	function _iGet($sName)
	{
		$iValue = 0;
		$this->_get($sName, $iValue);
		return (int) $iValue;
	}
	
	function _sGet($sName)
	{
		$sValue = '';
		$this->_get($sName, $sValue);
		return (string) $sValue;
	}
	
	function _get($sName, &$sValue)
	{
		if(array_key_exists($sName, $this->aDatas))
		{
			$sValue = $this->aDatas[$sName];
			return true;
		}
		return false;
	}
	
	function _set($sName, $sValue)
	{
		$this->aDatas[$sName] = $sValue;
		return true;
	}
}

	
class BAB_FmFolder extends BAB_DbRecord
{
	function BAB_FmFolder()
	{
		parent::BAB_DbRecord();
	}
	
	function setId($iId)
	{
		$this->_set('iId', $iId);
	}
	
	function getId()
	{
		return $this->_iGet('iId');
	}
	
	function setName($sName)
	{
		$this->_set('sName', $sName);
	}
	
	function getName()
	{
		return $this->_sGet('sName');
	}
	
	function setRelativePath($sRelativePath)
	{
		if(isset($GLOBALS['babFileNameTranslation']))
		{
			$sRelativePath = strtr($sRelativePath, $GLOBALS['babFileNameTranslation']);
		}
		$this->_set('sRelativePath', $sRelativePath);
	}
	
	function getRelativePath()
	{
		return $this->_sGet('sRelativePath');
	}
	
	function setApprobationSchemeId($iId)
	{
		$this->_set('iIdApprobationScheme', $iId);
	}
	
	function getApprobationSchemeId()
	{
		return $this->_iGet('iIdApprobationScheme');
	}
	
	function setFileNotify($sFileNotify)
	{
		$this->_set('sFileNotify', $sFileNotify);
	}
	
	function getFileNotify()
	{
		return $this->_sGet('sFileNotify');
	}
	
	function setActive($sActive)
	{
		$this->_set('sActive', $sActive);
	}
	
	function getActive()
	{
		return $this->_sGet('sActive');
	}
	
	function setVersioning($sVersioning)
	{
		$this->_set('sVersioning', $sVersioning);
	}
	
	function getVersioning()
	{
		return $this->_sGet('sVersioning');
	}
	
	function setDelegationOwnerId($iId)
	{
		$this->_set('iIdDgOwner', $iId);
	}
	
	function getDelegationOwnerId()
	{
		return $this->_iGet('iIdDgOwner');
	}
	
	function setHide($sHide)
	{
		$this->_set('sHide', $sHide);
	}
	
	function getHide()
	{
		return $this->_sGet('sHide');
	}
	
	function setAutoApprobation($sAutoApprobation)
	{
		$this->_set('sAutoApprobation', $sAutoApprobation);
	}
	
	function getAutoApprobation()
	{
		return $this->_sGet('sAutoApprobation');
	}
	
	function save()
	{
		$oFmFolderSet = new BAB_FmFolderSet();
		return $oFmFolderSet->save($this);
	}
}


class BAB_FolderFile extends BAB_DbRecord 
{
	function BAB_FolderFile()
	{
		parent::BAB_DbRecord();		
	}
	
	function setId($iId)
	{
		$this->_set('iId', $iId);
	}
	
	function getId()
	{
		return $this->_iGet('iId');
	}

	function setName($sName)
	{
		$this->_set('sName', $sName);
	}
	
	function getName()
	{
		return $this->_sGet('sName');
	}

	function setDescription($sDescription)
	{
		$this->_set('sDescription', $sDescription);
	}
	
	function getDescription()
	{
		return $this->_sGet('sDescription');
	}

	function setKeywords($sKeywords)
	{
		$this->_set('sKeywords', $sKeywords);
	}
	
	function getKeywords()
	{
		return $this->_sGet('sKeywords');
	}
	
	function setPathName($sPathName)
	{
		if(isset($GLOBALS['babFileNameTranslation']))
		{
			$sPathName = strtr($sPathName, $GLOBALS['babFileNameTranslation']);
		}
		$this->_set('sPathName', $sPathName);
	}
	
	function getPathName()
	{
		return $this->_sGet('sPathName');
	}
	
	function setOwnerId($iIdOwner)
	{
		$this->_set('iIdOwner', $iIdOwner);
	}
	
	function getOwnerId()
	{
		return $this->_iGet('iIdOwner');
	}
	
	function setGroup($sGroup)
	{
		$this->_set('sGroup', $sGroup);
	}
	
	function getGroup()
	{
		return $this->_sGet('sGroup');
	}
	
	function setLinkId($iIdLink)
	{
		$this->_set('iIdLink', $iIdLink);
	}
	
	function getLinkId()
	{
		return $this->_iGet('iIdLink');
	}
	
	function setReadOnly($sReadOnly)
	{
		$this->_set('sReadOnly', $sReadOnly);
	}
	
	function getReadOnly()
	{
		return $this->_sGet('sReadOnly');
	}
	
	function setState($sState)
	{
		$this->_set('sState', $sState);
	}
	
	function getState()
	{
		return $this->_sGet('sState');
	}
	
	function setCreationDate($sCreation)
	{
		$this->_set('sCreation', $sCreation);
	}
	
	function getCreationDate()
	{
		return $this->_sGet('sCreation');
	}
	
	function setAuthorId($iIdAuthor)
	{
		$this->_set('iIdAuthor', $iIdAuthor);
	}
	
	function getAuthorId()
	{
		return $this->_iGet('iIdAuthor');
	}
	
	function setModifiedDate($sModified)
	{
		$this->_set('sModified', $sModified);
	}
	
	function getModifiedDate()
	{
		return $this->_sGet('sModified');
	}
	
	function setModifierId($iIdModifier)
	{
		$this->_set('iIdModifier', $iIdModifier);
	}
	
	function getModifierId()
	{
		return $this->_iGet('iIdModifier');
	}
	
	function setConfirmed($sConfirmed)
	{
		$this->_set('sConfirmed', $sConfirmed);
	}
	
	function getConfirmed()
	{
		return $this->_sGet('sConfirmed');
	}
	
	function setHits($iHits)
	{
		$this->_set('iHits', $iHits);
	}
	
	function getHits()
	{
		return $this->_iGet('iHits');
	}
	
	function setApprobationInstanceId($iIdApprobationInstance)
	{
		$this->_set('iIdApprobationInstance', $iIdApprobationInstance);
	}
	
	function getApprobationInstanceId()
	{
		return $this->_iGet('iIdApprobationInstance');
	}
	
	function setUserEditId($iIdUserEdit)
	{
		$this->_set('iIdUserEdit', $iIdUserEdit);
	}
	
	function getUserEditId()
	{
		return $this->_iGet('iIdUserEdit');
	}
	
	function setMajorVer($iVerMajor)
	{
		$this->_set('iVerMajor', $iVerMajor);
	}
	
	function getMajorVer()
	{
		return $this->_iGet('iVerMajor');
	}
	
	function setMinorVer($iVerMinor)
	{
		$this->_set('iVerMinor', $iVerMinor);
	}
	
	function getMinorVer()
	{
		return $this->_iGet('iVerMinor');
	}
	
	function setCommentVer($sVerComment)
	{
		$this->_set('sVerComment', $sVerComment);
	}
	
	function getCommentVer()
	{
		return $this->_sGet('iVerMinor');
	}
	
	function setStatusIndex($iIndexStatus)
	{
		$this->_set('iIndexStatus', $iIndexStatus);
	}
	
	function getStatusIndex()
	{
		return $this->_iGet('iIndexStatus');
	}
	
	function save()
	{
		$oFolderFileSet = new BAB_FolderFileSet();
		$oFolderFileSet->save($this);
	}
}


class BAB_FolderFileVersion extends BAB_DbRecord
{
	function BAB_FolderFileVersion()
	{
		parent::BAB_DbRecord();
	}
	
	function setId($iId)
	{
		$this->_set('iId', $iId);
	}
	
	function getId()
	{
		return $this->_iGet('iId');
	}
	
	function setIdFile($iId)
	{
		$this->_set('iIdFile', $iId);
	}
	
	function getIdFile()
	{
		return $this->_iGet('iIdFile');
	}
	
	function setCreationDate($sDate)
	{
		$this->_set('sCreationDate', $sDate);
	}
	
	function getCreationDate()
	{
		return $this->_sGet('sCreationDate');
	}
	
	function setAuthorId($iIdAuthor)
	{
		$this->_set('iIdAuthor', $iIdAuthor);
	}
	
	function getAuthorId()
	{
		return $this->_iGet('iIdAuthor');
	}
	
	function setMajorVer($iVerMajor)
	{
		$this->_set('iVerMajor', $iVerMajor);
	}
	
	function getMajorVer()
	{
		return $this->_iGet('iVerMajor');
	}
	
	function setMinorVer($iVerMinor)
	{
		$this->_set('iVerMinor', $iVerMinor);
	}
	
	function getMinorVer()
	{
		return $this->_iGet('iVerMinor');
	}
	
	function setComment($sComment)
	{
		$this->_set('sComment', $sComment);
	}
	
	function getCommentVer()
	{
		return $this->_sGet('iVerMinor');
	}
	
	function setFlowApprobationInstanceId($iIdFlowApprobationInstance)
	{
		$this->_set('iIdFlowApprobationInstance', $iIdFlowApprobationInstance);
	}
	
	function getFlowApprobationInstanceId()
	{
		return $this->_iGet('iIdFlowApprobationInstance');
	}
	
	function setConfirmed($sConfirmed)
	{
		$this->_set('sConfirmed', $sConfirmed);
	}
	
	function getConfirmed()
	{
		return $this->_sGet('sConfirmed');
	}
		
	function setStatusIndex($iIndexStatus)
	{
		$this->_set('iIndexStatus', $iIndexStatus);
	}
	
	function getStatusIndex()
	{
		return $this->_iGet('iIndexStatus');
	}
	
	function save()
	{
		$oFolderFileVersionSet = new BAB_FolderFileVersionSet();
		$oFolderFileVersionSet->save($this);
	}
}


class BAB_FolderFileLog extends BAB_DbRecord
{
	function BAB_FolderFileLog()
	{
		parent::BAB_DbRecord();
	}
	
	function setId($iId)
	{
		$this->_set('iId', $iId);
	}
	
	function getId()
	{
		return $this->_iGet('iId');
	}
	
	function setIdFile($iId)
	{
		$this->_set('iIdFile', $iId);
	}
	
	function getIdFile()
	{
		return $this->_iGet('iIdFile');
	}
	
	function setCreationDate($sDate)
	{
		$this->_set('sCreationDate', $sDate);
	}
	
	function getCreationDate()
	{
		return $this->_sGet('sCreationDate');
	}
	
	function setAuthorId($iIdAuthor)
	{
		$this->_set('iIdAuthor', $iIdAuthor);
	}
	
	function getAuthorId()
	{
		return $this->_iGet('iIdAuthor');
	}
	
	function setAction($iAction)
	{
		$this->_set('iAction', $iAction);
	}
	
	function getAction()
	{
		return $this->_iGet('iAction');
	}
	
	function setComment($sComment)
	{
		$this->_set('sComment', $sComment);
	}
	
	function getComment()
	{
		return $this->_sGet('sComment');
	}
	
	function setVersion($sVersion)
	{
		$this->_set('sVersion', $sVersion);
	}
	
	function getVersion()
	{
		return $this->_sGet('sVersion');
	}
	
	function save()
	{
		$oFolderFileLogSet = new BAB_FolderFileLogSet();
		$oFolderFileLogSet->save($this);
	}
}



//Il faudra couper cette classe en deux faire une classe de base
//et deux classe dérivées. Une pour les repertoire simple et une
//pour les repertoire collectif
class BAB_FmFolderHelper
{
	function BAB_FmFolderHelper()
	{
		
	}
	
	function getFmFolderById($iId)
	{
		$oFmFolderSet = new BAB_FmFolderSet();
		$oId =& $oFmFolderSet->aField['iId']; 
		return $oFmFolderSet->get($oId->in($iId));
	}
	
	function getFirstCollectiveFolder($sRelativePath)
	{
//		bab_debug(__FUNCTION__ . ' sRelativePath ==> ' . $sRelativePath);
		$aPath = explode('/', $sRelativePath);
		if(is_array($aPath))
		{
			$iLength = count($aPath); 
			if($iLength >= 1)
			{
				$bStop		= false;
				$iIndex		= $iLength - 1;
				$bFinded	= false;
				global $babDB;
				
//				bab_debug($aPath);
				
				$oFmFolderSet = new BAB_FmFolderSet();
				$oRelativePath =& $oFmFolderSet->aField['sRelativePath']; 
				$oName =& $oFmFolderSet->aField['sName']; 
				
				do 
				{
					$sFolderName = $aPath[$iIndex];
					unset($aPath[$iIndex]);
					$sRelativePath	= implode('/', $aPath);
					
					if('' !== $sRelativePath)
					{
						$sRelativePath .= '/';
					}
					
					$oCriteria = $oRelativePath->like($babDB->db_escape_like($sRelativePath));
					$oCriteria = $oCriteria->_and($oName->in($sFolderName));
					$oFmFolder = $oFmFolderSet->get($oCriteria);
					
					if(!is_null($oFmFolder))
					{
						return $oFmFolder;
					}
					
					if($iIndex > 0)
					{
						$iIndex--;
					}
					else 
					{
						$bStop = true;			
					}
				}
				while(false === $bStop);
			}
		}
		return null;		
	}
	
	function getFileInfoForCollectiveDir($iIdFolder, $sPath, &$iIdOwner, &$sRelativePath)
	{
		$bSuccess = true;
		
		$oFmFolder = BAB_FmFolderHelper::getFmFolderById($iIdFolder);
		if(!is_null($oFmFolder))
		{
			//If the method getRelativePath return an empty string so this is a root folder
			$iIdOwner = $oFmFolder->getId();
			$sRelativePath = (strlen(trim($oFmFolder->getRelativePath())) === 0) ? $oFmFolder->getName() . '/' : '';
			
			if(strlen(trim($sPath)) > 0)
			{
				$sRelativePath .= $oFmFolder->getRelativePath() . $sPath . '/';
				
				$oFmFolder = BAB_FmFolderHelper::getFirstCollectiveFolder($sRelativePath);
				if(!is_null($oFmFolder))
				{
					$iIdOwner = $oFmFolder->getId();
				}
				else 
				{
					$bSuccess = false;
				}
			}
		}
		else 
		{
			$bSuccess = false;
		}
		
		return $bSuccess;
	}
	
	function getUploadPath()
	{
		$iLength = strlen(trim($GLOBALS['babUploadPath']));
		if($iLength > 0)			
		{
			$sUploadPath = $GLOBALS['babUploadPath'];
			if('/' !== $sUploadPath{$iLength - 1})
			{
				$sUploadPath .= '/';
				return $sUploadPath;
			}
		}
		return $GLOBALS['babUploadPath'];
	}

	function getUserDirUploadPath($iIdUser)
	{
		return "U" . $iIdUser . '/';
	}
	
	function accessValidForUserDir($iIdUser)
	{
		global $babBody, $BAB_SESS_USERID;
		return ($BAB_SESS_USERID == $iIdUser && $babBody->ustorage);
	}
	
	function accessValidForCollectiveDir($iIdFolder)
	{
		global $babBody, $BAB_SESS_USERID, $aclfm;
		
		$iCount = count($babBody->aclfm['id']);
		for($i = 0; $i < $iCount; $i++)
		{
			if($babBody->aclfm['id'][$i] == $iIdFolder && $babBody->aclfm['ma'][$i] == 1)
			{
				return true;
			}
		}
		return false;
	}

	function createDirectory($sUplaodPath, $sFullPathName)
	{
		global $babBody;
		$bSuccess = true;
			
		$sUplaodPath = realpath($sUplaodPath);
		
		if(strlen(trim($sFullPathName)) > 0 && false === strstr($sFullPathName, '..'))
		{
			if(isset($GLOBALS['babFileNameTranslation']))
			{
				$sFullPathName = strtr($sFullPathName, $GLOBALS['babFileNameTranslation']);
			}
		
			if(!is_dir($sFullPathName))
			{
				$bSuccess = bab_mkdir($sFullPathName, $GLOBALS['babMkdirMode']);
			}
			else
			{
				$babBody->msgerror = bab_translate("This folder already exists");
				$bSuccess = false;
			}
		}
		else 
		{
			$babBody->msgerror = bab_translate("Please give a valid directory name");
			$bSuccess = false;
		}
		return $bSuccess;
	}	
	
	function renameDirectory($sUploadPath, $sRelativePath, $sOldName, $sNewName)
	{
		global $babBody;
		$bSuccess = true;
		
		$bOldNameValid = (strlen(trim($sOldName)) > 0);
		$bNewNameValid = (strlen(trim($sNewName)) > 0 && false === strstr($sNewName, '..'));
		
		if($bOldNameValid && $bNewNameValid)
		{
			$sOldPathName = '';
			$sNewPathName = '';
			
			//$sRelativePath est vide si c'est un repertoire à la racine du répertoire d'upload
			
			$sUploadPath = realpath($sUploadPath);
			if(strlen(trim($sRelativePath)) > 0)
			{
				$sPathName		= realpath($sUploadPath . '/' . $sRelativePath);
				$sOldPathName	= realpath($sPathName . '/' . $sOldName);
//				bab_debug('*** sUploadPath ==> ' . $sUploadPath);
//				bab_debug('*** sRelativePath ==> ' . $sRelativePath);
//				bab_debug('*** sPathName ==> ' . $sPathName);
				$sNewPathName	= $sPathName . '/' . $sNewName;
			}
			else 
			{
				$sOldPathName	= realpath($sUploadPath . '/' . $sOldName);
				$sNewPathName	= $sUploadPath . '/' . $sNewName;
			}
			
//			bab_debug('sUploadPath ==> ' . $sUploadPath);
//			bab_debug('sRelativePath ==> ' . $sRelativePath);
//			bab_debug('sOldPathName ==> ' . $sOldPathName);
//			bab_debug('sNewPathName ==> ' . $sNewPathName);

			$bOldPathNameValid = (substr($sOldPathName, 0, strlen($sUploadPath)) === $sUploadPath);
	
			if($bOldPathNameValid)
			{
				if(is_writable($sOldPathName))
				{
					if(!is_dir($sNewPathName))
					{
						$bSuccess = rename($sOldPathName, $sNewPathName);
					}
					else 
					{
						$babBody->msgerror = bab_translate("This folder already exists");
						$bSuccess = false;
					}
				}
				else 
				{
					$babBody->msgerror = bab_translate("This folder does not exists");
					$bSuccess = false;
				}
			}
			else 
			{
				$babBody->msgerror = bab_translate("Access denied 3");
				$bSuccess = false;
			}
		}
		else 
		{
			$babBody->msgerror = bab_translate("Access denied 6");
			$bSuccess = false;
		}
		return $bSuccess;
	}
	
	
	function updateSubFolderPathName($sUploadPath, $sRelativePath, $sOldName, $sNewName)
	{
//		bab_debug(__FUNCTION__);
//		bab_debug('sUploadPath ==> ' . $sUploadPath);
//		bab_debug('sRelativePath ==> ' . $sRelativePath);
//		bab_debug('sOldName ==> ' . $sOldName);
//		bab_debug('sNewName ==> ' . $sNewName);
		
		if(BAB_FmFolderHelper::renameDirectory($sUploadPath, $sRelativePath, $sOldName, $sNewName))
		{
			$sOldRelativePath = $sRelativePath . $sOldName . '/';
			$sNewRelativePath = $sRelativePath . $sNewName . '/';
			
//			bab_debug('sOldRelativePath ==> ' . $sOldRelativePath);
//			bab_debug('sNewRelativePath ==> ' . $sNewRelativePath);
			
			global $babDB;
			$oFmFolderSet = new BAB_FmFolderSet();
			$oRelativePath =& $oFmFolderSet->aField['sRelativePath']; 
			$oFmFolderSet = $oFmFolderSet->select($oRelativePath->like($babDB->db_escape_like($sOldRelativePath) . '%'));
			while(null !== ($oFmFolder = $oFmFolderSet->next()))
			{
				$sRelPath = $sNewRelativePath . substr($oFmFolder->getRelativePath(), strlen($sOldRelativePath));
//				bab_debug('sRelPath ==> ' . $sRelPath);
				$oFmFolder->setRelativePath($sRelPath);
				$oFmFolder->save();
			}
			return true;
		}
		return false;
	}
}



class BAB_FolderFileHelper
{
	function BAB_FolderFileHelper()
	{
		
	}
	
	function renamePath($sRelativePath, $sNewName)
	{
//		bab_debug(__FUNCTION__ . ' sRelativePath ==> ' . $sRelativePath . ' sNewName ==> ' . $sNewName);
		
		$oFolderFileSet = new BAB_FolderFileSet();
		$oPathName =& $oFolderFileSet->aField['sPathName'];
		
		global $babDB;
		$oFolderFileSet->select($oPathName->like($babDB->db_escape_like($sRelativePath) . '%'));
		
		while(null !== ($oFolderFile = $oFolderFileSet->next()))
		{
			$sBegin = substr($oFolderFile->getPathName(), 0, strlen($sRelativePath));
			$sEnd = (string) substr($oFolderFile->getPathName(), strlen($sRelativePath), strlen($oFolderFile->getPathName()));

			$aPath = explode('/', $sBegin);
			if(is_array($aPath))
			{
				$sNewPathName = '';
				
				$iCount = count($aPath);
				if($iCount >= 2)
				{
					//sRelativePath is always xxx/xxx/
					//at the minimum is xxx/ and count of explode of that is 2
					$aPath[$iCount - 2] = $sNewName;
					$sNewPathName = implode('/', $aPath) . $sEnd;
				}
				
//				bab_debug('sBegin ==> ' . $sBegin . ' sEnd ==> ' . $sEnd . ' sNewPathName ==> ' . $sNewPathName);
//				bab_debug('sFullPathName ==> ' . $sNewPathName . $oFolderFile->getName());
				$oFolderFile->setPathName($sNewPathName);
				$oFolderFile->save();
			}
		}
	}
	
	function setIdOwnerToFirstCollective($sPathName, $iIdOwner)
	{
		$oFmFolder = BAB_FmFolderHelper::getFirstCollectiveFolder($sPathName);
		
		if(!is_null($oFmFolder))
		{
//			bab_debug('iIdOwner ==> ' . $oFmFolder->getId() . ' sPathName ==> ' . $sPathName);
			BAB_FolderFileHelper::changeIdOwner($sPathName, $iIdOwner, $oFmFolder->getId());
		}
		else 
		{
//			bab_debug('NOT FOUND FOR sPathName ==> ' . $sPathName);
		}
	}
	
	function changeIdOwner($sPathName, $iOldIdOwner, $iNewIdOwner)
	{
		$oFolderFileSet = new BAB_FolderFileSet();
		$oPathName =& $oFolderFileSet->aField['sPathName'];
		$oIdOwner =& $oFolderFileSet->aField['iIdOwner'];
		
		global $babDB;
		$oCriteria = $oPathName->like($babDB->db_escape_like($sPathName) . '%');
		$oCriteria = $oCriteria->_and($oIdOwner->in($iOldIdOwner));
		$oFolderFileSet->select($oCriteria);

		while(null !== ($oFolderFile = $oFolderFileSet->next()))
		{
			bab_debug('sFileName ==> ' . $oFolderFile->getName() . ' sPath ==> ' . $oFolderFile->getPathName() . 
				' iOldIdOwner ==> ' . $oFolderFile->getOwnerId() . ' iNewIdOwner ==> ' . $iNewIdOwner);
			
			$oFolderFile->setOwnerId($iNewIdOwner);
//			$oFolderFile->save();
		}		
	}
	
	
}

//---------------------
/*
class BAB_ContextBase
{
	var $aDatas = array();
	
	function BAB_ContextBase()
	{
		
	}
	
	function get($sName)
	{
		if(array_key_exists($sName, $this->aDatas))
		{
			return $this->aDatas[$sName];
		}
		return '';
	}
	
	function set($sName, $sValue)
	{
		$this->aDatas[$sName] = $sValue;
	}
}


class BAB_RenameFolderContext extends BAB_ContextBase
{
	function BAB_RenameFolderContext($sUploadPath, $sRelativePath, $sOldName, $sNewName)
	{
		parent::BAB_ContextBase();
		
		$this->set('sUploadPath', $sUploadPath);
		$this->set('sRelativePath', $sRelativePath);
		$this->set('sOldName', $sOldName);
		$this->set('sNewName', $sNewName);
	}
}


class BAB_RenameFilePathContext extends BAB_ContextBase
{
	function BAB_RenameFilePathContext($sPathName, $sNewName)
	{
		parent::BAB_ContextBase();
		
		$this->set('sPathName', $sPathName);
		$this->set('sNewName', $sNewName);
	}
}

class BAB_CommandBase
{
	var $oContext = null;
	
	function BAB_CommandBase($oContext)
	{
		$this->oContext = $oContext;
	}
	
	function execute()
	{
		
	}
}

class BAB_RenameFolderCommand extends BAB_CommandBase
{
	function BAB_RenameFolderCommand($oContext)
	{
		parent::BAB_CommandBase($oContext);
	}
	
	function execute()
	{
		BAB_FmFolderHelper::updateSubFolderPathName($this->oContext->get('sUploadPath'), 
			$this->oContext->get('sRelativePath'), $this->oContext->get('sOldName'), 
			$this->oContext->get('sNewName'));
	}
}


class BAB_RenameFilePathCommand extends BAB_CommandBase
{
	function BAB_RenameFilePathCommand($oContext)
	{
		parent::BAB_CommandBase($oContext);
		
		$this->set('sPathName', $sPathName);
		$this->set('sNewName', $sNewName);
	}
	
	function execute()
	{
		BAB_FolderFileHelper::renamePath($this->oContext->get('sPathName'), 
			$this->oContext->get('sNewName'));
	}
}


class BAB_CommandProcessor
{
	var $aCommand = array();
	
	function BAB_CommandProcessor()
	{
		
	}
	
	function add($oCommand)
	{
		$this->aCommand[] = $oCommand;
	}
	
	function execute()
	{
		foreach($this->aCommand as $oCommand)
		{
			$oCommand->execute();
		}
	}
}
//*/
?>