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
require_once $GLOBALS['babInstallPath'].'utilit/delegincl.php';

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
			return $oFmFolder->getRelativePath() . '/'.$oFmFolder->getName().'/';
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

	$users = aclGetAccessUsers(BAB_FMNOTIFY_GROUPS_TBL, $idgrp);

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

function acceptFileVersion($oFolderFile, $oFolderFileVersion, $bnotify)
{
	$oFileManagerEnv	=& getEnvObject();
	$sUploadPath		= $oFileManagerEnv->getCollectiveFolderPath();

	$sSrcFile = $sUploadPath . $oFolderFile->getPathName() . $oFolderFile->getName();
	$sTrgFile = $sUploadPath . $oFolderFile->getPathName() . BAB_FVERSION_FOLDER . '/' .
	$oFolderFile->getMajorVer() . ',' . $oFolderFile->getMinorVer() . ',' . $oFolderFile->getName();
	copy($sSrcFile, $sTrgFile);
	
	$sSrcFile = $sUploadPath . $oFolderFile->getPathName() . BAB_FVERSION_FOLDER . '/' .
	$oFolderFileVersion->getMajorVer() . ',' . $oFolderFileVersion->getMinorVer() . ',' . $oFolderFile->getName();
	$sTrgFile = $sUploadPath . $oFolderFile->getPathName() . $oFolderFile->getName();
	copy($sSrcFile, $sTrgFile);
	unlink($sSrcFile);

	$iOldMajorVer = $oFolderFile->getMajorVer();
	$iOldMinorVer = $oFolderFile->getMinorVer();
	$sVerComment = $oFolderFile->getCommentVer();

	$oFolderFile->setFolderFileVersionId(0);
	$oFolderFile->setModifiedDate($oFolderFileVersion->getCreationDate());
	$oFolderFile->setModifierId($oFolderFileVersion->getAuthorId());
	$oFolderFile->setMajorVer($oFolderFileVersion->getMajorVer());
	$oFolderFile->setMinorVer($oFolderFileVersion->getMinorVer());
	$oFolderFile->setCommentVer($oFolderFileVersion->getComment());
	$oFolderFile->save();

	$oFolderFileLog = new BAB_FolderFileLog();
	$oFolderFileLog->setIdFile($oFolderFile->getId());
	$oFolderFileLog->setCreationDate(date("Y-m-d H:i:s"));
	$oFolderFileLog->setAuthorId($oFolderFileVersion->getAuthorId());
	$oFolderFileLog->setAction(BAB_FACTION_COMMIT);
	$oFolderFileLog->setComment($oFolderFileVersion->getComment());
	$oFolderFileLog->setVersion($oFolderFileVersion->getMajorVer() . '.' . $oFolderFileVersion->getMinorVer());
	$oFolderFileLog->save();

	$iIdAuthor = (0 === $oFolderFile->getModifierId() ? $oFolderFile->getAuthorId() : $oFolderFile->getModifierId());

	$oFolderFileVersion->setFlowApprobationInstanceId(0);
	$oFolderFileVersion->setConfirmed('Y');
	$oFolderFileVersion->setMajorVer($iOldMajorVer);
	$oFolderFileVersion->setMinorVer($iOldMinorVer);
	$oFolderFileVersion->setAuthorId($iIdAuthor);
	$oFolderFileVersion->setComment($sVerComment);
	$oFolderFileVersion->save();

	notifyFileAuthor(bab_translate("Your new file version has been accepted"),
	$oFolderFile->getMajorVer() . '.' . $oFolderFile->getMinorVer(), $iIdAuthor, $oFolderFile->getName());

	if($bnotify == "Y")
	{
		fileNotifyMembers($oFolderFile->getName(), $oFolderFile->getPathName(),
		$oFolderFile->getOwnerId(), bab_translate("A new version file has been uploaded"));
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

	$oFileManagerEnv =& getEnvObject();
	
	if(!empty($BAB_SESS_USERID))
	{
		if('N' === $gr && userHavePersonnalStorage())
		{
			$access = true;
			$confirmed = 'Y';

			$sRelativePath = $oFileManagerEnv->sRelativePath;
			$sFullUploadPath = $oFileManagerEnv->getCurrentFmPath();
			$baddtags = 'N';
		}
		else if('Y' === $gr)
		{
			$rr = $babDB->db_fetch_array($babDB->db_query("select baddtags from ".BAB_FM_FOLDERS_TBL." where id='".$babDB->db_escape_string($iIdOwner)."'"));
			$baddtags = $rr['baddtags'];
			
			$oFmFolder = null;
			$access = BAB_FmFolderHelper::getInfoFromCollectivePath($oFileManagerEnv->sPath, $iIdRootFolder, $oFmFolder);
			if($access)
			{
				$iIdOwner = $oFmFolder->getId();
				$sFullUploadPath = $oFileManagerEnv->getCurrentFmPath();
				$sRelativePath = $oFileManagerEnv->sRelativePath;
				
				if(canManage($oFileManagerEnv->sRelativePath) || canDownload($oFileManagerEnv->sRelativePath))
				{
					$access = true;
				}
			}
		}
	}
//	bab_debug('iIdOwner ==> ' . $iIdOwner . ' sRelativePath ==> ' . $sRelativePath . ' sFullUploadPath ==> ' . $sFullUploadPath);

	if(!$access)
	{
		$babBody->msgerror = bab_translate("Access denied");
		return false;
	}


	$pathx = $sFullUploadPath;
	$okfiles = array();
	$errfiles = array();

	$count = 0;
	foreach($fmFiles as $fmFile)
	{
		$otags = array();

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
					//$bexist = true;
					$errfiles[] = array('error'=> bab_translate("A file with the same name already exists in the basket"), 'file' => $file['name']);
					continue;
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


		if( !is_array($keywords))
		{
			$tags = trim($keywords);
		}
		else
		{
			$tags = trim($keywords[$count]);
		}

		if( !empty($tags))
		{
			$atags = explode(',', $tags);
			$message = '';
			for( $k = 0; $k < count($atags); $k++ )
			{
				$tag = trim($atags[$k]);
				if( !empty($tag) )
				{
					$res = $babDB->db_query("select id from ".BAB_TAGS_TBL." where tag_name='".$babDB->db_escape_string($tag)."'");
					if( $res && $babDB->db_num_rows($res))
					{
						$arr = $babDB->db_fetch_array($res);
						if( !isset($otags[$arr['id']]))
						{
						$otags[$arr['id']] = $arr['id'];
						}

					}
					else
					{
						if( $baddtags == 'Y')
						{
						$babDB->db_query("insert into ".BAB_TAGS_TBL." (tag_name) values ('".$babDB->db_escape_string($tag)."')");
						$iidtag = $babDB->db_insert_id();
						$otags[$iidtag] = $iidtag;
						}
						else
						{
						$message = bab_translate("Some tags doesn't exist");
						break;
						}
					}
				}
			}

			if( $message )
			{
			$errfiles[] = array('error'=> $message, 'file' => $file['name']);
			continue;
			}
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

		if($readonly[$count] != 'Y')
		{
			$readonly[$count] = 'N';
		}



		if($bexist)
		{
			$req = "
			UPDATE ".BAB_FILES_TBL." set 
				description='".$babDB->db_escape_string($description[$count])."', 
				readonly='".$babDB->db_escape_string($readonly[$count])."', 
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
			$babDB->db_query("delete from ".BAB_FILES_TAGS_TBL." where id_file='".$babDB->db_escape_string($idf)."'");
		}
		else
		{
			$req = "insert into ".BAB_FILES_TBL."
			(name, description, path, id_owner, bgroup, link, readonly, state, created, author, modified, modifiedby, confirmed, index_status, iIdDgOwner) values ";
			$req .= "('" .$babDB->db_escape_string($name). "', '" . $babDB->db_escape_string($description[$count]). "', '".$babDB->db_escape_string($sRelativePath). "', '" . $babDB->db_escape_string($iIdOwner). "', '" . $babDB->db_escape_string($gr). "', '0', '" . $babDB->db_escape_string($readonly[$count]). "', '', now(), '" . $babDB->db_escape_string($idcreator). "', now(), '" . $babDB->db_escape_string($idcreator). "', '". $babDB->db_escape_string($confirmed)."', '".$babDB->db_escape_string($index_status)."', '".$babDB->db_escape_string(bab_getCurrentUserDelegation())."')";
			$babDB->db_query($req);
			$idf = $babDB->db_insert_id();
		}

		if( count($otags))
		{
			foreach( $otags as $k => $v )
				{
				$babDB->db_query("insert into ".BAB_FILES_TAGS_TBL." (id_file ,id_tag) values ('".$babDB->db_escape_string($idf)."','".$babDB->db_escape_string($k)."')");
				}
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
					$fval = $babDB->db_escape_string($GLOBALS[$fd][$count]);

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
	$count++;
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
function saveUpdateFile($idf, $fmFile, $fname, $description, $keywords, $readonly, $confirm, $bnotify, $descup)
{
	global $babBody, $babDB, $BAB_SESS_USERID;

	if($fmFile)
	{
		$uploadf_name = $fmFile->filename;
		$uploadf_size = $fmFile->size;
	}
	else
	{
		$uploadf_name = '';
		$uploadf_size = '';
	}



	$oFolderFileSet = new BAB_FolderFileSet();
	$oId =& $oFolderFileSet->aField['iId'];
	$oFolderFile = $oFolderFileSet->get($oId->in($idf));

	if(!is_null($oFolderFile))
	{

		if('Y' === $oFolderFile->getGroup())
		{
		$rr = $babDB->db_fetch_array($babDB->db_query("select baddtags from ".BAB_FM_FOLDERS_TBL." where id='".$babDB->db_escape_string($oFolderFile->getOwnerId())."'"));
		}
		else
		{
		$rr['baddtags'] = 'N';
		}

		$otags = array();
		$tags = trim($keywords);
		if( !empty($tags))
		{
			$atags = explode(',', $tags);
			for( $k = 0; $k < count($atags); $k++ )
			{
				$tag = trim($atags[$k]);
				if( !empty($tag) )
				{
					$res = $babDB->db_query("select id from ".BAB_TAGS_TBL." where tag_name='".$babDB->db_escape_string($tag)."'");
					if( $res && $babDB->db_num_rows($res))
					{
						$arr = $babDB->db_fetch_array($res);
						if( !isset($otags[$arr['id']]))
						{
						$otags[$arr['id']] = $arr['id'];
						}

					}
					else
					{
						if( $rr['baddtags'] == 'Y' )
						{
						$babDB->db_query("insert into ".BAB_TAGS_TBL." (tag_name) values ('".$babDB->db_escape_string($tag)."')");
						$iidtag = $babDB->db_insert_id();
						$otags[$iidtag] = $iidtag;
						}
						else
						{
						$babBody->msgerror = bab_translate("Some tags doesn't exist");
						return false;
						}
					}
				}
			}
		}
		
		
		if('Y' === $oFolderFile->getGroup())
		{
			$bManager = bab_isAccessValid(BAB_FMMANAGERS_GROUPS_TBL, $oFolderFile->getOwnerId());
			$bUpdate = bab_isAccessValid(BAB_FMUPDATE_GROUPS_TBL, $oFolderFile->getOwnerId());
			if(!($bManager || $bUpdate))
			{
				$arrschi = bab_getWaitingIdSAInstance($GLOBALS['BAB_SESS_USERID']);
				if(count($arrschi) > 0 && in_array($oFolderFile->getFlowApprobationInstanceId(), $arrschi))
				{
//					break;
				}
				else
				{
					$babBody->msgerror = bab_translate("Access denied");
					return false;
				}
			}
		}

		$oFileManagerEnv =& getEnvObject();
		$sUploadPath = $oFileManagerEnv->getRootFmPath();
		$sFullPathName = $sUploadPath . $oFolderFile->getPathName() . $oFolderFile->getName();

		if(!file_exists($sFullPathName))
		{
			$babBody->msgerror = bab_translate("File does'nt exist");
			return false;
		}

		$bmodified = false;
		if(!empty($uploadf_name) && $uploadf_name != "none")
		{
			if($uploadf_size > $GLOBALS['babMaxFileSize'])
			{
				$babBody->msgerror = bab_translate("The file was greater than the maximum allowed") ." :". $GLOBALS['babMaxFileSize'];
				return false;
			}
			$totalsize = getDirSize($GLOBALS['babUploadPath']);
			if($uploadf_size + $totalsize > $GLOBALS['babMaxTotalSize'])
			{
				$babBody->msgerror = bab_translate("There is not enough free space");
				return false;
			}

			$totalsize = getDirSize($sUploadPath . $oFolderFile->getPathName());
			if($uploadf_size + $totalsize > ('Y' === $oFolderFile->getGroup() ? $GLOBALS['babMaxGroupSize']: $GLOBALS['babMaxUserSize']))
			{
				$babBody->msgerror = bab_translate("There is not enough free space");
				return false;
			}

			if(isset($GLOBALS['babFileNameTranslation']))
			{
				$uploadf_name = strtr($uploadf_name, $GLOBALS['babFileNameTranslation']);
			}

			if(!$fmFile->import($sFullPathName))
			{
				$babBody->msgerror = bab_translate("The file could not be uploaded");
				return false;
			}
			$bmodified = true;
		}

		$fname = trim($fname);
		$frename = false;
		$osfname = $fname;

		if(!empty($fname) && strcmp($oFolderFile->getName(), $osfname))
		{
			if(isset($GLOBALS['babFileNameTranslation']))
			{
				$osfname = strtr($osfname, $GLOBALS['babFileNameTranslation']);
			}

			if(rename($sFullPathName, $sUploadPath . $oFolderFile->getPathName() . $osfname))
			{
				$frename = true;
				if(is_dir($sUploadPath . $oFolderFile->getPathName() . BAB_FVERSION_FOLDER . '/'))
				{
					$oFolderFileVersionSet = new BAB_FolderFileVersionSet();
					$oIdFile =& $oFolderFileVersionSet->aField['iIdFile'];
					$oFolderFileVersionSet->select($oIdFile->in($idf));

					while(null !== ($oFolderFileVersion = $oFolderFileVersionSet->next()))
					{
						$sSrc = $sUploadPath . $oFolderFile->getPathName() . BAB_FVERSION_FOLDER . '/' .
						$oFolderFileVersion->getMajorVer() . ',' . $oFolderFileVersion->getMinorVer() .
						',' . $oFolderFile->getName();

						$sTrg = $sUploadPath . $oFolderFile->getPathName() . BAB_FVERSION_FOLDER . '/' .
						$oFolderFileVersion->getMajorVer() . ',' . $oFolderFileVersion->getMinorVer() .
						',' . $osfname;

						rename($sSrc, $sTrg);
					}
				}
			}
		}



		if(empty($BAB_SESS_USERID))
		{
			$idcreator = 0;
		}
		else
		{
			$idcreator = $BAB_SESS_USERID;
		}

		$tmp = array();
		if($descup)
		{
			$tmp[] = "description='".$babDB->db_escape_string($description)."'";

			$babDB->db_query("delete from ".BAB_FILES_TAGS_TBL." where id_file='".$babDB->db_escape_string($idf)."'");
			if( count($otags))
			{
				foreach( $otags as $k => $v )
					{
					$babDB->db_query("insert into ".BAB_FILES_TAGS_TBL." (id_file ,id_tag) values ('".$babDB->db_escape_string($idf)."','".$babDB->db_escape_string($k)."')");
					}
			}

		}

		if($bmodified)
		{
			$tmp[] = "modified=now()";
			$tmp[] = "modifiedby='".$babDB->db_escape_string($idcreator)."'";
		}
		if($frename)
		{
			$tmp[] = "name='".$babDB->db_escape_string($fname)."'";
		}
		else
		{
			$osfname = $oFolderFile->getName();
		}

		if(!empty($readonly))
		{
			if($readonly != 'Y' )
			{
				$readonly = 'N';
			}
			$tmp[] = "readonly='".$babDB->db_escape_string($readonly)."'";
		}
		
		if(count($tmp) > 0)
		{
			$babDB->db_query("update ".BAB_FILES_TBL." set ".implode(", ", $tmp)." where id='".$babDB->db_escape_string($idf)."'");
		}
		
		if('Y' === $oFolderFile->getGroup())
		{
			$res = $babDB->db_query("select id from ".BAB_FM_FIELDS_TBL." where id_folder='".$babDB->db_escape_string($oFolderFile->getOwnerId())."'");
			while($arrf = $babDB->db_fetch_array($res))
			{
				$fd = 'field'.$oFolderFile->getId();
				if(isset($GLOBALS[$fd]))
				{

					$fval = $babDB->db_escape_string($GLOBALS[$fd]);

					$res2 = $babDB->db_query("select id from ".BAB_FM_FIELDSVAL_TBL." where id_file='".$babDB->db_escape_string($idf)."' and id_field='".$babDB->db_escape_string($arrf['id'])."'");
					if($res2 && $babDB->db_num_rows($res2) > 0)
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

		$rr = $babDB->db_fetch_array($babDB->db_query("select filenotify, id_dgowner from ".BAB_FM_FOLDERS_TBL." where id='".$babDB->db_escape_string($oFolderFile->getOwnerId())."'"));
		if(empty($bnotify))
		{
			$bnotify = $rr['filenotify'];
		}
		if('Y' === $oFolderFile->getGroup())
		{
			if('N' === $oFolderFile->getConfirmed())
			{
				include_once $GLOBALS['babInstallPath']."utilit/afincl.php";
				$res = updateFlowInstance($oFolderFile->getFlowApprobationInstanceId(), $GLOBALS['BAB_SESS_USERID'], $confirm == "Y"? true: false);
				switch($res)
				{
					case 0:
						$oFolderFileSet = new BAB_FolderFileSet();
						$oId =& $oFolderFileSet->aField['iId'];
						$oFolderFileSet->remove($oId->in($oFolderFile->getId()));
						notifyFileAuthor(bab_translate("Your file has been refused"),"", $oFolderFile->getAuthorId(), $oFolderFile->getName());
						break;
					case 1:
						deleteFlowInstance($oFolderFile->getFlowApprobationInstanceId());
						$babDB->db_query("update ".BAB_FILES_TBL." set confirmed='Y', idfai='0' where id = '".$babDB->db_escape_string($oFolderFile->getId())."'");
						$GLOBALS['babWebStat']->addNewFile($rr['id_dgowner']);
						notifyFileAuthor(bab_translate("Your file has been accepted"),"", $oFolderFile->getAuthorId(), $oFolderFile->getName());
						if($bnotify == "Y")
						{
							fileNotifyMembers($oFolderFile->getName(), $oFolderFile->getPathName(), $oFolderFile->getOwnerId(), bab_translate("A new file has been uploaded"));
						}
						break;
					default:
						$nfusers = getWaitingApproversFlowInstance($oFolderFile->getFlowApprobationInstanceId(), true);
						if(count($nfusers) > 0)
						{
							notifyFileApprovers($oFolderFile->getId(), $nfusers, bab_translate("A new file is waiting for you"));
						}
						break;
				}
			}
			else if($bnotify == "Y" && $bmodified)
			{
				fileNotifyMembers($oFolderFile->getName(), $oFolderFile->getPathName(), $oFolderFile->getOwnerId(), bab_translate("File has been updated"));
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
							if(0 !== $oFolderFile->getFolderFileVersionId())
							{
								$oFolderFileVersionSet = new BAB_FolderFileVersionSet();

								$oId =& $oFmFolderSet->aField['iId'];
								$oFolderFileVersion = $oFolderFileVersionSet->get($oId->in($oFolderFile->getFolderFileVersionId()));
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
		if(0 === $oFolderFile->getFolderFileVersionId() && $GLOBALS['BAB_SESS_USERID'] != '')
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

			//bab_debug('Version ==> ' . $oFolderFileVersion->getId());

			$oFolderFile->setFolderFileVersionId($oFolderFileVersion->getId());
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
function fm_unlockFile($idf, $comment)
{
	global $babBody, $babDB;

	$fm_file = fm_getFileAccess($idf);
	$oFolderFile =& $fm_file['oFolderFile'];
	$oFmFolder =& $fm_file['oFmFolder'];
	$lockauthor = $fm_file['lockauthor'];

	if(!is_null($oFmFolder) && !is_null($oFolderFile))
	{
		if(0 !== $oFolderFile->getFolderFileVersionId() && $GLOBALS['BAB_SESS_USERID'] != '')
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
				$oFolderFileVersion = $oFolderFileVersionSet->get($oId->in($oFolderFile->getFolderFileVersionId()));

				$oFileManagerEnv =& getEnvObject();
				$sUploadPath = $oFileManagerEnv->getCollectiveRootFmPath();

				if(!is_null($oFolderFileVersion) && 0 !== $oFolderFileVersion->getFlowApprobationInstanceId())
				{
					include_once $GLOBALS['babInstallPath']."utilit/afincl.php";
					deleteFlowInstance($oFolderFileVersion->getFlowApprobationInstanceId());

					$sFullPathName = $sUploadPath . $oFolderFile->getPathName() . BAB_FVERSION_FOLDER . '/' .
					$oFolderFileVersion->getMajorVer() . ',' . $oFolderFileVersion->getMinorVer() . ',' . $oFolderFile->getName();

					unlink($sFullPathName);
				}

				$oFolderFileVersionSet->remove($oId->in($oFolderFile->getFolderFileVersionId()),
				$sUploadPath . $oFolderFile->getPathName(), $oFolderFile->getName());

				$oFolderFile->setFolderFileVersionId(0);
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

	if($fmFile)
	{
		$filename = $fmFile->filename;
		$size = $fmFile->size;
	}
	else
	{
		$filename = '';
		$size = 0;
	}

	$fm_file = fm_getFileAccess($idf);
	$oFmFolder =& $fm_file['oFmFolder'];
	$oFolderFile =& $fm_file['oFolderFile'];
	$lockauthor = $fm_file['lockauthor'];

	if(!is_null($oFolderFile) && !is_null($oFmFolder))
	{
		if($lockauthor != $GLOBALS['BAB_SESS_USERID'])
		{
			$babBody->msgerror = bab_translate("Access denied");
			return false;
		}

		if(empty($filename) || $filename == "none")
		{
			$babBody->msgerror = bab_translate("Please select a file to upload");
			return false;
		}

		if($size > $GLOBALS['babMaxFileSize'])
		{
			$babBody->msgerror = bab_translate("The file was greater than the maximum allowed") ." :". $GLOBALS['babMaxFileSize'];
			return false;
		}

		$totalsize = getDirSize($GLOBALS['babUploadPath']);
		if($size + $totalsize > $GLOBALS['babMaxTotalSize'])
		{
			$babBody->msgerror = bab_translate("There is not enough free space");
			return false;
		}

		$sUploadPath		= BAB_FileManagerEnv::getCollectivePath($oFmFolder->getDelegationOwnerId());
		$sFullPathName		= $sUploadPath . $oFolderFile->getPathName();

		$totalsize = getDirSize($sFullPathName);
		if($size + $totalsize > $GLOBALS['babMaxGroupSize'] )
		{
			$babBody->msgerror = bab_translate("There is not enough free space");
			return false;
		}

		if(!is_dir($sFullPathName . BAB_FVERSION_FOLDER))
		{
			bab_mkdir($sFullPathName . BAB_FVERSION_FOLDER, $GLOBALS['babMkdirMode']);
		}

		if($vermajor == 'Y')
		{
			$vmajor = ($oFolderFile->getMajorVer() + 1);
			$vminor = 0;
		}
		else
		{
			$vmajor = $oFolderFile->getMajorVer();
			$vminor = ($oFolderFile->getMinorVer() + 1);
		}
		if(!$fmFile->import($sFullPathName . BAB_FVERSION_FOLDER . '/' . $vmajor . ',' . $vminor . ',' . $oFolderFile->getName()))
		{
			$babBody->msgerror = bab_translate("The file could not be uploaded");
			return false;
		}

		if(0 !== $oFmFolder->getApprobationSchemeId())
		{
			include_once $GLOBALS['babInstallPath']."utilit/afincl.php";
			if('Y' === $oFmFolder->getAutoApprobation())
			{
				$idfai = makeFlowInstance($oFmFolder->getApprobationSchemeId(), 'filv-'.$oFolderFile->getFlowApprobationInstanceId(), $GLOBALS['BAB_SESS_USERID']);
			}
			else
			{
				$idfai = makeFlowInstance($oFmFolder->getApprobationSchemeId(), 'filv-'.$oFolderFile->getFlowApprobationInstanceId());
			}
		}

		if(0 === $oFmFolder->getApprobationSchemeId() || $idfai === true)
		{
			$oFolderFileLog = new BAB_FolderFileLog();
			$oFolderFileLog->setIdFile($idf);
			$oFolderFileLog->setCreationDate(date("Y-m-d H:i:s"));
			$oFolderFileLog->setAuthorId($GLOBALS['BAB_SESS_USERID']);
			$oFolderFileLog->setAction(BAB_FACTION_COMMIT);
			$oFolderFileLog->setComment($comment);
			$oFolderFileLog->setVersion($vmajor . '.' . $vminor);
			$oFolderFileLog->save();

			$sSrc = $sFullPathName . $oFolderFile->getName();
			$sTrg = $sFullPathName . BAB_FVERSION_FOLDER . '/'. $oFolderFile->getMajorVer() .
			',' . $oFolderFile->getMinorVer() . ',' . $oFolderFile->getName();
			copy($sSrc, $sTrg);

			$sSrc = $sFullPathName . BAB_FVERSION_FOLDER . '/'. $vmajor . ',' . $vminor .
			',' . $oFolderFile->getName();
			$sTrg = $sFullPathName . $oFolderFile->getName();
			copy($sSrc, $sTrg);

			unlink($sSrc);

			// index
			include_once $GLOBALS['babInstallPath']."utilit/indexincl.php";
			$index_status = bab_indexOnLoadFiles(
			array($sFullPathName . $oFolderFile->getName(),
			$sFullPathName . BAB_FVERSION_FOLDER . '/'. $oFolderFile->getMajorVer() .
			',' . $oFolderFile->getMinorVer() . ',' . $oFolderFile->getName()),
			'bab_files'
			);


			$oFolderFileVersionSet = new BAB_FolderFileVersionSet();
			$oId =& $oFolderFileVersionSet->aField['iId'];
			$oFolderFileVersion = $oFolderFileVersionSet->get($oId->in($oFolderFile->getFolderFileVersionId()));
			if(!is_null($oFolderFileVersion))
			{
				$oFolderFileVersion->setMajorVer($oFolderFile->getMajorVer());
				$oFolderFileVersion->setMinorVer($oFolderFile->getMinorVer());
				$oFolderFileVersion->setComment($oFolderFile->getCommentVer());
				$oFolderFileVersion->setFlowApprobationInstanceId(0);
				$oFolderFileVersion->setConfirmed('Y');
				$oFolderFileVersion->setStatusIndex($index_status);
				$oFolderFileVersion->save();
			}

			if(BAB_INDEX_STATUS_INDEXED === $index_status)
			{
				$obj = new bab_indexObject('bab_files');
				$obj->setIdObjectFile($oFolderFile->getPathName() . $oFolderFile->getName(), $idf, $oFolderFile->getOwnerId());
				$obj->setIdObjectFile($oFolderFile->getPathName() . BAB_FVERSION_FOLDER . '/' .
				$oFolderFile->getMajorVer() . ',' . $oFolderFile->getMinorVer() . ',' .
				$oFolderFile->getName(), $idf, $oFolderFile->getOwnerId());
			}

			if('Y' === $oFmFolder->getFileNotify())
			{
				fileNotifyMembers($filename, $oFolderFile->getPathName(), $oFolderFile->getOwnerId(),
				bab_translate("A new version file has been uploaded"));
			}

			$oFolderFile->setFolderFileVersionId(0);
			$oFolderFile->setModifiedDate(date("Y-m-d H:i:s"));
			$oFolderFile->setModifierId($GLOBALS['BAB_SESS_USERID']);
			$oFolderFile->setMajorVer($vmajor);
			$oFolderFile->setMinorVer($vminor);
			$oFolderFile->setCommentVer($comment);
			$oFolderFile->setStatusIndex($index_status);
			$oFolderFile->save();
		}
		else if(!empty($idfai))
		{
			$oFolderFileLog = new BAB_FolderFileLog();
			$oFolderFileLog->setIdFile($idf);
			$oFolderFileLog->setCreationDate(date("Y-m-d H:i:s"));
			$oFolderFileLog->setAuthorId($GLOBALS['BAB_SESS_USERID']);
			$oFolderFileLog->setAction(BAB_FACTION_COMMIT);
			$oFolderFileLog->setComment(bab_translate("Waiting to be validate"));
			$oFolderFileLog->setVersion($vmajor . '.' . $vminor);
			$oFolderFileLog->save();

			$oFolderFileVersionSet = new BAB_FolderFileVersionSet();
			$oId =& $oFolderFileVersionSet->aField['iId'];
			$oFolderFileVersion = $oFolderFileVersionSet->get($oId->in($oFolderFile->getFolderFileVersionId()));
			if(!is_null($oFolderFileVersion))
			{
				$oFolderFileVersion->setMajorVer($vmajor);
				$oFolderFileVersion->setMinorVer($vminor);
				$oFolderFileVersion->setComment($comment);
				$oFolderFileVersion->setFlowApprobationInstanceId($idfai);
				$oFolderFileVersion->save();
			}

			$nfusers = getWaitingApproversFlowInstance($idfai, true);
			notifyFileApprovers($oFolderFile->getId(), $nfusers, bab_translate("A new version file is waiting for you"));
		}

		return true;
	}
	return false;
}







/**
 * Index all files of file manager
 * @param 	array 	$status
 * @param 	boolean $prepare
 */
function indexAllFmFiles($status, $prepare) {

	global $babDB;

	$sQuery = 
		'SELECT  
			f.id,
			f.name,
			f.path, 
			f.id_owner, 
			f.bgroup, 
			f.iIdDgOwner,
			d.id version 
		FROM 
			' . BAB_FILES_TBL . ' f 
		LEFT JOIN ' . 
			BAB_FM_FOLDERS_TBL . ' d ON d.id = f.id_owner AND f.bgroup =\'Y\' AND d.version =\'Y\'
		WHERE 
			f.index_status IN(' . $babDB->quote($status) . ')';

	//bab_debug($sQuery);
	$res = $babDB->db_query($sQuery);

	$files = array();
	$rights = array();
			
	
	while ($arr = $babDB->db_fetch_assoc($res)) {
		//$pathx = $oFileManagerEnv->getCollectiveRootFmPath();
		
		$pathx = BAB_FileManagerEnv::getCollectivePath($arr['iIdDgOwner']);
		
//		bab_debug('sFullPathName ==> ' . $pathx . $arr['path'] . $arr['name']);
		
		$files[] = $pathx . $arr['path'] . $arr['name'];
		$rights[ $arr['path'] . $arr['name'] ] = array(
		'id' => $arr['id'],
		'id_owner' => $arr['id_owner']
		);

		if(null != $arr['version']) 
		{
			$sQuery = 
				'SELECT 
					id,	
					ver_major, 
					ver_minor 
				FROM 
					' . BAB_FM_FILESVER_TBL . ' 
				WHERE 
					id_file= \'' . $babDB->db_escape_string($arr['id']) . '\' 
					AND index_status IN(' . $babDB->quote($status) . ')';
			
			//bab_debug($sQuery);
			$resv = $babDB->db_query($sQuery);

			while($arrv = $babDB->db_fetch_assoc($resv)) 
			{
				//bab_debug('sVersion ==> ' . $pathx . $arr['path'] . BAB_FVERSION_FOLDER);
				if(is_dir( $pathx . $arr['path'] . BAB_FVERSION_FOLDER)) 
				{
					$file = BAB_FVERSION_FOLDER."/".$arrv['ver_major'].",".$arrv['ver_minor'].",".$arr['name'];
					$files[] = $pathx . $arr['path'] . $file;
					//bab_debug('sFile ==> ' . $pathx . $arr['path'] . $file);
					$rights[$arr['path'] . $file] = array(
					'id' => $arrv['id'],
					'id_owner' => $arr['id_owner']
					);
				}
			}
		}
	}

//	bab_debug($rights);
	
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

	function save(&$oObject)
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
			'(' . implode(',', $aValue) . ') ';
			
//			bab_debug($sQuery);
			$oResult = $babDB->db_queryWem($sQuery);
			if(false !== $oResult)
			{
				$oObject->_get('iId', $iId);
				if(is_null($iId))
				{
					$oObject->_set('iId', $babDB->db_insert_id());
				}
				return true;
			}
			else 
			{
				$sQuery = 
					'UPDATE ' . 
						$this->sTableName . ' ' .
					'SET ' .
						implode(',', $aOnDuplicateKey) .
					'WHERE ' . $this->aField['iId']->getName() . ' =\'' . $iId . '\'';
			
//				bab_debug($sQuery);
				$oResult = $babDB->db_queryWem($sQuery);
				return (false !== $oResult);
			}
			
			//En MySql 3.23 cela ne marche pas
			/*
			$sQuery =
			'INSERT INTO ' . $this->sTableName . ' ' .
			'(' . implode(',', $aInto) . ') ' .
			'VALUES ' .
			'(' . implode(',', $aValue) . ') ' .
			'ON DUPLICATE KEY UPDATE ' .
			implode(',', $aOnDuplicateKey);
			
//			bab_debug($sQuery);
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
		//*/
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


	function getSelectQuery($oCriteria = null, $aOrder = array(), $aLimit = array())
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

//		bab_debug($sQuery);
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
//		require_once $GLOBALS['babInstallPath'] . 'utilit/devtools.php';
//		bab_debug_print_backtrace();

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
		'sAddTags' => new BAB_StringField('`baddtags`'),
		'sAutoApprobation' => new BAB_StringField('`auto_approbation`')
		);
	}

	function remove($oCriteria, $bDbRecordOnly)
	{
		$this->select($oCriteria);

		while(null !== ($oFmFolder = $this->next()))
		{
			$this->delete($oFmFolder, $bDbRecordOnly);
		}
	}

	function delete($oFmFolder, $bDbRecordOnly)
	{
		if(is_a($oFmFolder, 'BAB_FmFolder'))
		{
			$oFileManagerEnv =& getEnvObject();
			
			require_once $GLOBALS['babInstallPath'].'admin/acl.php';
			aclDelete(BAB_FMUPLOAD_GROUPS_TBL, $oFmFolder->getId());
			aclDelete(BAB_FMDOWNLOAD_GROUPS_TBL, $oFmFolder->getId());
			aclDelete(BAB_FMUPDATE_GROUPS_TBL, $oFmFolder->getId());
			aclDelete(BAB_FMMANAGERS_GROUPS_TBL, $oFmFolder->getId());

			$oFolderFileSet = new BAB_FolderFileSet();
			if(true === $bDbRecordOnly)
			{
				if(strlen(trim($oFmFolder->getRelativePath())) > 0)
				{
					$oFirstFmFolder = BAB_FmFolderSet::getFirstCollectiveFolder($oFmFolder->getRelativePath());
					$sPathName = $oFmFolder->getRelativePath() . $oFmFolder->getName() . '/';
					$oFolderFileSet->setOwnerId($sPathName, $oFmFolder->getId(), $oFirstFmFolder->getId());
					
					$oFmFolderCliboardSet = new BAB_FmFolderCliboardSet();
					$oFmFolderCliboardSet->setOwnerId($sPathName, $oFmFolder->getId(), $oFirstFmFolder->getId());
				}
				
				$sUploadPath = $oFileManagerEnv->getCollectiveRootFmPath();

				$oIdOwner =& $oFolderFileSet->aField['iIdOwner'];
				$oPathName =& $oFolderFileSet->aField['sPathName'];
				$oIdDgOwner =& $oFolderFileSet->aField['iIdDgOwner'];
				
				$oCriteria = $oIdDgOwner->in(bab_getCurrentUserDelegation());
				$oCriteria = $oCriteria->_and($oIdOwner->in($oFirstFmFolder->getId()));
				$oCriteria = $oCriteria->_and($oPathName->in($oFmFolder->getRelativePath() . $oFmFolder->getName() . '/'));

				$oFolderFileSet->select($oCriteria);

				$oFolderFileVersionSet = new BAB_FolderFileVersionSet();
				while(null !== ($oFolderFile = $oFolderFileSet->next()))
				{
					$oIdFile =& $oFolderFileVersionSet->aField['iIdFile'];
					$oFolderFileVersionSet->remove($oIdFile->in($oFolderFile->getId()),
					$sUploadPath . $oFolderFile->getPathName(), $oFolderFile->getName());

					require_once $GLOBALS['babInstallPath'].'utilit/afincl.php';
					deleteFlowInstance($oFolderFile->getFlowApprobationInstanceId());
					$oFolderFile->setMajorVer(1);
					$oFolderFile->setMinorVer(0);
					$oFolderFile->setFlowApprobationInstanceId(0);
					$oFolderFile->setConfirmed('Y');
					$oFolderFile->save();
				}
			}
			else if(false === $bDbRecordOnly)
			{
				global $babDB, $babBody;
				
				$oPathName =& $oFolderFileSet->aField['sPathName'];
				$oIdDgOwner =& $oFolderFileSet->aField['iIdDgOwner'];
				
				$oCriteria = $oIdDgOwner->in(bab_getCurrentUserDelegation());
				$oCriteria = $oCriteria->_and($oPathName->like($babDB->db_escape_like($oFmFolder->getRelativePath() . $oFmFolder->getName() . '/') . '%'));
				$oFolderFileSet->remove($oCriteria);
				
				$sRootFmPath = BAB_FileManagerEnv::getCollectivePath($oFmFolder->getDelegationOwnerId());
				$sFullPathNane = $sRootFmPath . $oFmFolder->getRelativePath() . $oFmFolder->getName();
//				bab_debug(__FUNCTION__ . ' ' . $sFullPathNane);
				$this->removeDir($sFullPathNane);

				$oFmFolderCliboardSet = new BAB_FmFolderCliboardSet();
				$oFmFolderCliboardSet->deleteFolder($oFmFolder->getName(), $oFmFolder->getRelativePath(), 'Y');
			}

			$oId =& $this->aField['iId'];
			return parent::remove($oId->in($oFmFolder->getId()));
		}
	}
	
	
	function save(&$oFmFolder)
	{
		if(is_a($oFmFolder, 'BAB_FmFolder'))
		{
			return parent::save($oFmFolder);
		}
	}


	//--------------------------------------
	function getFirstCollectiveParentFolder($sRelativePath)
	{
//		bab_debug(__FUNCTION__ . ' sRelativePath ==> ' . removeLastPath($sRelativePath));
		return BAB_FmFolderSet::getFirstCollectiveFolder(removeLastPath($sRelativePath));
	}

	function getFirstCollectiveFolder($sRelativePath)
	{
//		bab_debug(__FUNCTION__ . ' sRelativePath ==> ' . $sRelativePath);
		global $babBody;
		
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

				//bab_debug($aPath);
				$oFmFolderSet = new BAB_FmFolderSet();
				$oRelativePath =& $oFmFolderSet->aField['sRelativePath'];
				$oIdDgOwner =& $oFmFolderSet->aField['iIdDgOwner'];
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
					$oCriteria = $oCriteria->_and($oIdDgOwner->in(bab_getCurrentUserDelegation()));
					$oFmFolder = $oFmFolderSet->get($oCriteria);
//					bab_debug($oFmFolderSet->getSelectQuery($oCriteria));
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
	
	function getRootCollectiveFolder($sRelativePath)
	{
		global $babBody;
		
		$aPath = explode('/', $sRelativePath);
		if(is_array($aPath))
		{
			$iLength = count($aPath);
			if($iLength >= 1)
			{
				$sName = $aPath[0];

				$oFmFolderSet = new BAB_FmFolderSet();
				$oRelativePath =& $oFmFolderSet->aField['sRelativePath'];
				$oName =& $oFmFolderSet->aField['sName'];
				$oIdDgOwner =& $oFmFolderSet->aField['iIdDgOwner'];

				global $babDB;
				$oCriteria = $oRelativePath->like($babDB->db_escape_like(''));
				$oCriteria = $oCriteria->_and($oName->in($sName));
				$oCriteria = $oCriteria->_and($oIdDgOwner->in(bab_getCurrentUserDelegation()));
				$oFmFolder = $oFmFolderSet->get($oCriteria);

				if(!is_null($oFmFolder))
				{
					return $oFmFolder;
				}
			}
		}
		return null;
	}

	function removeDir($sFullPathName)
	{
		if(is_dir($sFullPathName))
		{
			$oHandle = opendir($sFullPathName);
			if(false !== $oHandle)
			{
				while($sName = readdir($oHandle))
				{
					if('.' !== $sName && '..' !== $sName)
					{
						if(is_dir($sFullPathName . '/' . $sName))
						{
							$this->removeDir($sFullPathName . '/' . $sName);
						}
						else if(file_exists($sFullPathName . '/' . $sName))
						{
							@unlink($sFullPathName . '/' . $sName);
						}
					}
				}
				closedir($oHandle);
				@rmdir($sFullPathName);
			}
		}
	}
	
	
	function rename($sUploadPath, $sRelativePath, $sOldName, $sNewName)
	{
//		bab_debug(__FUNCTION__);
//		bab_debug('sUploadPath ==> ' . $sUploadPath);
//		bab_debug('sRelativePath ==> ' . $sRelativePath);
//		bab_debug('sOldName ==> ' . $sOldName);
//		bab_debug('sNewName ==> ' . $sNewName);
		
		if(BAB_FmFolderHelper::renameDirectory($sUploadPath, $sRelativePath, $sOldName, $sNewName))
		{
			global $babBody, $babBody;
			
			$sOldRelativePath = $sRelativePath . $sOldName . '/';
			$sNewRelativePath = $sRelativePath . $sNewName . '/';

//			$sOldRelativePath = $sRelativePath . $sOldName . ((strlen(trim($sOldName)) !== 0) ? '/' : '');
//			$sNewRelativePath = $sRelativePath . $sNewName . ((strlen(trim($sNewName)) !== 0) ? '/' : '');

//			bab_debug('sOldRelativePath ==> ' . $sOldRelativePath);
//			bab_debug('sNewRelativePath ==> ' . $sNewRelativePath);

			global $babDB;
			$oFmFolderSet = new BAB_FmFolderSet();
			$oRelativePath =& $oFmFolderSet->aField['sRelativePath'];
			$oIdDgOwner =& $oFmFolderSet->aField['iIdDgOwner'];
			
			$oCriteria = $oIdDgOwner->in(bab_getCurrentUserDelegation());
			$oCriteria = $oRelativePath->like($babDB->db_escape_like($sOldRelativePath) . '%');
			
			$oFmFolderSet = $oFmFolderSet->select($oCriteria);
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
	
	
	function move($sUploadPath, $sOldRelativePath, $sNewRelativePath)
	{
		$sSrc = removeEndSlah($sUploadPath . $sOldRelativePath);
		$sTrg = removeEndSlah($sUploadPath . $sNewRelativePath);
		
		if(rename($sSrc, $sTrg))
		{
			global $babBody, $babDB;
			$oFmFolderSet = new BAB_FmFolderSet();
			
			$oIdDgOwner		= $oFmFolderSet->aField['iIdDgOwner'];
			$oName			= $oFmFolderSet->aField['sName'];
			$oRelativePath	= $oFmFolderSet->aField['sRelativePath'];

			//1 changer le répertoire
			$sName = getLastPath($sOldRelativePath);
			$sRelativePath = removeLastPath($sOldRelativePath);
			$sRelativePath .= (strlen(trim($sRelativePath)) !== 0 ) ? '/' : '';
			
			$oCriteria = $oIdDgOwner->in(bab_getCurrentUserDelegation());
			$oCriteria = $oCriteria->_and($oName->in($sName));
			$oCriteria = $oCriteria->_and($oRelativePath->in($sRelativePath));
			
//			bab_debug($oFmFolderSet->getSelectQuery($oCriteria));
			$oFmFolder = $oFmFolderSet->get($oCriteria);
			if(!is_null($oFmFolder))
			{
				$sNewRelPath = removeLastPath($sNewRelativePath);
				$sNewRelPath .= (strlen(trim($sNewRelPath)) !== 0 ) ? '/' : '';
				$oFmFolder->setRelativePath($sNewRelPath);
				$oFmFolder->save();
			}
			
			//2 changer les sous répertoires
			$oCriteria = $oRelativePath->like($babDB->db_escape_like($sOldRelativePath) . '%');
			$oCriteria = $oCriteria->_and($oIdDgOwner->in($babBody->currentAdmGroup));
			
//			bab_debug($oFmFolderSet->getSelectQuery($oCriteria));
			$oFmFolderSet->select($oCriteria);
			while(null !== ($oFmFolder = $oFmFolderSet->next()))
			{
				$sNewRelativePath = $sNewRelativePath . substr($oFmFolder->getRelativePath(), strlen($sOldRelativePath));
				$oFmFolder->setRelativePath($sNewRelativePath);
				$oFmFolder->save();
			}
			return true;
		}
		return false;
	}
}


class BAB_FmFolderCliboardSet extends BAB_BaseSet
{
	function BAB_FmFolderCliboardSet()
	{
		parent::BAB_BaseSet(BAB_FM_FOLDERS_CLIPBOARD_TBL);

		$this->aField = array(
		'iId' => new BAB_IntField('`iId`'),
		'iIdDgOwner' => new BAB_IntField('`iIdDgOwner`'),
		'iIdRootFolder' => new BAB_IntField('`iIdRootFolder`'),
		'iIdFolder' => new BAB_IntField('`iIdFolder`'),
		'sName' => new BAB_StringField('`sName`'),
		'sRelativePath' => new BAB_StringField('`sRelativePath`'),
		'sGroup' => new BAB_StringField('`sGroup`'),
		'sCollective' => new BAB_StringField('`sCollective`'),
		'iIdOwner' => new BAB_IntField('`iIdOwner`'),
		'sCheckSum' => new BAB_IntField('`sCheckSum`')
		);
	}
	

	function rename($sRelativePath, $sOldName, $sNewName, $sGr)
	{
//		bab_debug(__FUNCTION__ . ' sRelativePath ==> ' . $sRelativePath . ' sOldName ==> ' . $sOldName . ' sNewName ==> ' . $sNewName);
		$iOffset = 2;
		
		global $babBody, $babDB, $BAB_SESS_USERID;
		
		$oFileManagerEnv =& getEnvObject();
		
		$oFmFolderCliboardSet = new BAB_FmFolderCliboardSet();
		$oRelativePath	=& $oFmFolderCliboardSet->aField['sRelativePath'];
		$oIdDgOwner		=& $oFmFolderCliboardSet->aField['iIdDgOwner'];
		$oGroup			=& $oFmFolderCliboardSet->aField['sGroup'];
		$oIdOwner		=& $oFmFolderCliboardSet->aField['iIdOwner'];
		$oName			=& $oFmFolderCliboardSet->aField['sName'];
		
		$oCriteria = $oRelativePath->like($babDB->db_escape_like($sRelativePath . $sOldName . '/') . '%');
		
		$oCriteria = $oCriteria->_and($oGroup->in($sGr));
		if('N' === $sGr)
		{
			$oCriteria = $oCriteria->_and($oIdOwner->in($BAB_SESS_USERID));
			$oCriteria = $oCriteria->_and($oIdDgOwner->in(0));
		}
		else 
		{
			$oCriteria = $oCriteria->_and($oIdDgOwner->in(bab_getCurrentUserDelegation()));
		}
		
//		bab_debug($oFmFolderCliboardSet->getSelectQuery($oCriteria));
		
		$oFmFolderCliboardSet->select($oCriteria);

		$sRelPath = $sRelativePath . $sOldName . '/';
		$iLength = strlen(trim($sRelPath));
		
		while(null !== ($oFmFolderCliboard = $oFmFolderCliboardSet->next()))
		{
			$sBegin = substr($oFmFolderCliboard->getRelativePath(), 0, $iLength);
			$sEnd = (string) substr($oFmFolderCliboard->getRelativePath(), strlen($sBegin), strlen($oFmFolderCliboard->getRelativePath()));

			$aPath = explode('/', $sBegin);
			if(is_array($aPath))
			{
				$sNewRelativePath = '';

				$iCount = count($aPath);
				if($iCount >= $iOffset)
				{
					$aPath[$iCount - $iOffset] = $sNewName;
					$sNewRelativePath = implode('/', $aPath) . $sEnd;
				}

				//bab_debug('sRelativePath ==> ' . $oFmFolderCliboard->getRelativePath() . ' sBegin ==> ' . $sBegin . ' sEnd ==> ' . $sEnd . ' sNewRelativePath ==> ' . $sNewRelativePath);
				$oFmFolderCliboard->setRelativePath($sNewRelativePath);
				$oFmFolderCliboard->save();
			}
		}
	}
	
	function setOwnerId($sPathName, $iOldIdOwner, $iNewIdOwner)
	{
		global $babBody, $babDB;
//		$oIdDgOwner =& $this->aField['iIdDgOwner'];
		$oRelativePath =& $this->aField['sRelativePath'];
		$oIdOwner =& $this->aField['iIdOwner'];

		$oCriteria = $oRelativePath->like($babDB->db_escape_like($sPathName) . '%');
		$oCriteria = $oCriteria->_and($oIdOwner->in($iOldIdOwner));
//		$oCriteria = $oCriteria->_and($oIdDgOwner->in($babBody->currentAdmGroup));
		$this->select($oCriteria);

//		bab_debug($this->getSelectQuery($oCriteria));
		
		while(null !== ($oFmFolderCliboard = $this->next()))
		{
			$oFmFolderCliboard->setOwnerId($iNewIdOwner);
			$oFmFolderCliboard->save();
		}
	}
	
	function deleteEntry($sName, $sRelativePath, $sGroup)
	{
		$oIdDgOwner		= $this->aField['iIdDgOwner'];
		$oGroup 		= $this->aField['sGroup'];
		$oName 			= $this->aField['sName'];
		$oRelativePath	= $this->aField['sRelativePath'];
		
		$iDelegation = ('Y' === $sGroup) ? bab_getCurrentUserDelegation() : 0;
		
		global $babBody;
		$oCriteria = $oIdDgOwner->in($iDelegation);
		$oCriteria = $oCriteria->_and($oGroup->in($sGroup));
		$oCriteria = $oCriteria->_and($oName->in($sName));
		$oCriteria = $oCriteria->_and($oRelativePath->in($sRelativePath));
		
//		bab_debug($this->getSelectQuery($oCriteria));
		
		$this->remove($oCriteria);
	}
	
	function deleteFolder($sName, $sRelativePath, $sGroup)
	{
		$oIdDgOwner = $this->aField['iIdDgOwner'];
		$oIdOwner = $this->aField['iIdOwner'];
		$oName = $this->aField['sName'];
		$oRelativePath = $this->aField['sRelativePath'];
		$oGroup = $this->aField['sGroup'];
		
		$iDelegation = ('Y' === $sGroup) ? bab_getCurrentUserDelegation() : 0;
		
		global $babBody, $babDB;
		$oCriteria = $oIdDgOwner->in($iDelegation);
		$oCriteria = $oCriteria->_and($oRelativePath->like($babDB->db_escape_like($sRelativePath . $sName . '/') . '%'));
		$oCriteria = $oCriteria->_and($oGroup->in('Y'));
//		bab_debug($this->getSelectQuery($oCriteria));
		$this->remove($oCriteria);
		
		$oCriteria = $oCriteria->_and($oName->in($sName));
		$oCriteria = $oIdDgOwner->in($iDelegation);
		$oCriteria = $oCriteria->_and($oRelativePath->like($babDB->db_escape_like($sRelativePath)));
		$oCriteria = $oCriteria->_and($oGroup->in('Y'));
//		bab_debug($this->getSelectQuery($oCriteria));
		$this->remove($oCriteria);
	}
	
	function move($sOldRelativePath, $sNewRelativePath, $sGr)
	{
		$oIdDgOwner		= $this->aField['iIdDgOwner'];
		$oIdOwner		= $this->aField['iIdOwner'];
		$oGroup 		= $this->aField['sGroup'];
		$oName 			= $this->aField['sName'];
		$oRelativePath	= $this->aField['sRelativePath'];
		
		global $babBody, $babDB, $BAB_SESS_USERID;
		
		$oCriteria = $oRelativePath->like($babDB->db_escape_like($sOldRelativePath) . '%');
		$oCriteria = $oCriteria->_and($oGroup->in($sGr));
		if('N' === $sGr)
		{
			$oCriteria = $oCriteria->_and($oIdOwner->in($BAB_SESS_USERID));
			$oCriteria = $oCriteria->_and($oIdDgOwner->in(0));
		}
		else 
		{
			$oCriteria = $oCriteria->_and($oIdDgOwner->in(bab_getCurrentUserDelegation()));
		}
		
//		bab_debug($this->getSelectQuery($oCriteria));
		$aProcessedPath = array();
		$iIdRootFolder = 0;
		$this->select($oCriteria);
		while(null !== ($oFmFolderCliboard = $this->next()))
		{
			$sOldRelPath = $oFmFolderCliboard->getRelativePath();
			$sNewRelPath = $sNewRelativePath . substr($sOldRelPath, strlen($sOldRelativePath));
			
			if(false === array_key_exists($sNewRelPath, $aProcessedPath))
			{
				if('Y' === $sGr)
				{
					$_oFmFolder = null;
					BAB_FmFolderHelper::getInfoFromCollectivePath($sNewRelPath, $iIdRootFolder, $_oFmFolder);							
					$iIdOwner = $_oFmFolder->getId();
				}
				else 
				{
					$iIdOwner = $BAB_SESS_USERID;
				}
				$aProcessedPath[$sNewRelPath] = array('iIdRootFolder' => $iIdRootFolder, 'iIdOwner' => $iIdOwner);
			}
			$oFmFolderCliboard->setRelativePath($sNewRelPath);
			$oFmFolderCliboard->setOwnerId($aProcessedPath[$sNewRelPath]['iIdOwner']);
			$oFmFolderCliboard->setRootFolderId($aProcessedPath[$sNewRelPath]['iIdRootFolder']);
			$this->save($oFmFolderCliboard);
		}
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
		'iIdFlowApprobationInstance' => new BAB_IntField('`idfai`'),
		'iIdFolderFileVersion' => new BAB_IntField('`edit`'),
		'iVerMajor' => new BAB_IntField('`ver_major`'),
		'iVerMinor' => new BAB_IntField('`ver_minor`'),
		'sVerComment' => new BAB_StringField('`ver_comment`'),
		'iIndexStatus' => new BAB_IntField('`index_status`'),
		'iIdDgOwner' => new BAB_IntField('`iIdDgOwner`')
		);
	}

	function remove($oCriteria)
	{
//		bab_debug(__LINE__ . ' ' . basename(__FILE__) . ' ' . __FUNCTION__);
		$oFileManagerEnv =& getEnvObject();
		
		$sUploadPath = $oFileManagerEnv->getRootFmPath();

		$this->select($oCriteria);

		$aIdFile = array();

		$oFolderFileVersionSet = new BAB_FolderFileVersionSet();
		$oId =& $oFolderFileVersionSet->aField['iIdFile'];

		while(null !== ($oFolderFile = $this->next()))
		{
			$sFullPathName = $sUploadPath . $oFolderFile->getPathName() . $oFolderFile->getName();
			if(file_exists($sFullPathName))
			{
//				bab_debug('unlink ==> ' . $sFullPathName);
				unlink($sFullPathName);
			}

			$oFolderFileVersionSet->remove($oId->in($oFolderFile->getId()),
			$sUploadPath . $oFolderFile->getPathName(), $oFolderFile->getName());

			if(0 !== $oFolderFile->getFlowApprobationInstanceId())
			{
				include_once $GLOBALS['babInstallPath']."utilit/afincl.php";
				deleteFlowInstance($oFolderFile->getFlowApprobationInstanceId());
			}

			$aIdFile[] = $oFolderFile->getId();
		}

		$oFolderFileLogSet = new BAB_FolderFileLogSet();
		$oId =& $oFolderFileLogSet->aField['iIdFile'];
		$oFolderFileLogSet->remove($oId->in($aIdFile));

		$oFolderFileFieldValueSet = new BAB_FolderFileFieldValueSet();
		$oId =& $oFolderFileFieldValueSet->aField['iIdFile'];
		$oFolderFileFieldValueSet->remove($oId->in($aIdFile));

		parent::remove($oCriteria);
	}

	function removeVersions($oCriteria)
	{
		$oFileManagerEnv =& getEnvObject();
		$sUploadPath = $oFileManagerEnv->getCollectiveRootFmPath();
		
//		bab_debug($this->getSelectQuery($oCriteria));
		$this->select($oCriteria);
			
		$oFolderFileLogSet = new BAB_FolderFileLogSet();
		$oIdVersion =& $oFolderFileLogSet->aField['iIdFile'];

		$oFolderFileVersionSet = new BAB_FolderFileVersionSet();
		$oId =& $oFolderFileVersionSet->aField['iIdFile'];
		
		while(null !== ($oFolderFile = $this->next()))
		{
//			bab_debug(__LINE__ . ' ' . basename(__FILE__) . ' ' . __FUNCTION__ . 
//				' Supprimer toutes les versions du fichier ==> ' . 
//				$sUploadPath . $oFolderFile->getPathName() . $oFolderFile->getName());
				
//			bab_debug($sUploadPath . $oFolderFile->getPathName() . $oFolderFile->getName());
//			bab_debug($oFolderFileVersionSet->getSelectQuery($oId->in($oFolderFile->getId())));
			
			$oFolderFileVersionSet->remove($oId->in($oFolderFile->getId()),
				$sUploadPath . $oFolderFile->getPathName(), $oFolderFile->getName());

			if(0 !== $oFolderFile->getFlowApprobationInstanceId())
			{
				include_once $GLOBALS['babInstallPath']."utilit/afincl.php";
				deleteFlowInstance($oFolderFile->getFlowApprobationInstanceId());
			}
			
			$oFolderFileLogSet->remove($oIdVersion->in($oFolderFile->getId()));
		}
	}
	
	function setOwnerId($sPathName, $iOldIdOwner, $iNewIdOwner)
	{
		$oPathName =& $this->aField['sPathName'];
		$oIdOwner =& $this->aField['iIdOwner'];

		global $babDB;
		$oCriteria = $oPathName->like($babDB->db_escape_like($sPathName) . '%');
		$oCriteria = $oCriteria->_and($oIdOwner->in($iOldIdOwner));
		$this->select($oCriteria);

		while(null !== ($oFolderFile = $this->next()))
		{
			//bab_debug('sFileName ==> ' . $oFolderFile->getName() . ' sPath ==> ' . $oFolderFile->getPathName() .
			//	' iOldIdOwner ==> ' . $oFolderFile->getOwnerId() . ' iNewIdOwner ==> ' . $iNewIdOwner);

			$oFolderFile->setOwnerId($iNewIdOwner);
			$oFolderFile->save();
		}
	}

	function renameFolder($sRelativePath, $sNewName, $sGr)
	{
//		bab_debug(__FUNCTION__ . ' sRelativePath ==> ' . $sRelativePath . ' sNewName ==> ' . $sNewName);
		$iOffset = 2; //pour le slash à la fin
		
		global $babBody, $babDB, $BAB_SESS_USERID;
		
		$oFolderFileSet	= new BAB_FolderFileSet();
		$oPathName		=& $oFolderFileSet->aField['sPathName'];
		$oIdDgOwner		=& $oFolderFileSet->aField['iIdDgOwner'];
		$oGroup			=& $oFolderFileSet->aField['sGroup'];
		$oIdOwner		=& $oFolderFileSet->aField['iIdOwner'];
		
		$oCriteria = $oPathName->like($babDB->db_escape_like($sRelativePath) . '%');
		$oCriteria = $oCriteria->_and($oIdDgOwner->in( (('Y' === $sGr) ? bab_getCurrentUserDelegation() : 0) ));
		$oCriteria = $oCriteria->_and($oGroup->in($sGr));
		if('N' === $sGr)
		{
			$oCriteria = $oCriteria->_and($oIdOwner->in($BAB_SESS_USERID));
		}
		
//		bab_debug($oFolderFileSet->getSelectQuery($oCriteria));
		
		$oFolderFileSet->select($oCriteria);

		while(null !== ($oFolderFile = $oFolderFileSet->next()))
		{
			$sBegin = substr($oFolderFile->getPathName(), 0, strlen($sRelativePath));
			$sEnd = (string) substr($oFolderFile->getPathName(), strlen($sRelativePath), strlen($oFolderFile->getPathName()));

			$aPath = explode('/', $sBegin);
			if(is_array($aPath))
			{
				$sNewPathName = '';

				$iCount = count($aPath);
				if($iCount >= $iOffset)
				{
					$aPath[$iCount - $iOffset] = $sNewName;
					$sNewPathName = implode('/', $aPath) . $sEnd;
				}

//				bab_debug('sBegin ==> ' . $sBegin . ' sEnd ==> ' . $sEnd . ' sNewPathName ==> ' . $sNewPathName);
//				bab_debug('sFullPathName ==> ' . $sNewPathName . $oFolderFile->getName());
				$oFolderFile->setPathName($sNewPathName);
				$oFolderFile->save();
			}
		}
	}
	
	
	function move($sOldRelativePath, $sNewRelativePath, $sGr)
	{
		global $babBody, $babDB;
		
		$oFolderFileSet	= new BAB_FolderFileSet();
		$oPathName		=& $oFolderFileSet->aField['sPathName'];
		$oIdDgOwner		=& $oFolderFileSet->aField['iIdDgOwner'];
		$oGroup			=& $oFolderFileSet->aField['sGroup'];

		$oCriteria = $oPathName->like($babDB->db_escape_like($sOldRelativePath) . '%');
		$oCriteria = $oCriteria->_and($oGroup->in($sGr));
		$oCriteria = $oCriteria->_and($oIdDgOwner->in(bab_getCurrentUserDelegation()));

//		bab_debug($oFmFolderSet->getSelectQuery($oCriteria));
		$aProcessedPath = array();
		$oFolderFileSet->select($oCriteria);
		while(null !== ($oFolderFile = $oFolderFileSet->next()))
		{
			$sOldPathName = $oFolderFile->getPathName();
			$sNewPathName = $sNewRelativePath . substr($sOldPathName, strlen($sOldRelativePath));
			
			if(false === array_key_exists($sNewPathName, $aProcessedPath))
			{
				$iIdRootFolder = 0;
				$_oFmFolder = null;
				BAB_FmFolderHelper::getInfoFromCollectivePath($sNewPathName, $iIdRootFolder, $_oFmFolder);							
				$iIdOwner = $_oFmFolder->getId();
				
				$aProcessedPath[$sNewPathName] = $iIdOwner;
			}
			$oFolderFile->setPathName($sNewPathName);
			$oFolderFile->setOwnerId($aProcessedPath[$sNewPathName]);
			$oFolderFile->save();
		}
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

	function remove($oCriteria, $sPathName, $sFileName)
	{
		//bab_debug(__LINE__ . ' ' . basename(__FILE__) . ' ' . __FUNCTION__);

		$this->select($oCriteria);
		while(null !== ($oFolderFileVersion = $this->next()))
		{
			$sFullPathName = $sPathName . BAB_FVERSION_FOLDER . '/' . $oFolderFileVersion->getMajorVer() .
			',' . $oFolderFileVersion->getMinorVer() . ',' . $sFileName;

			if(file_exists($sFullPathName))
			{
//				bab_debug(__LINE__ . ' ' . basename(__FILE__) . ' ' . __FUNCTION__ . ' unlink(' . $sFullPathName . ')');
				unlink($sFullPathName);
			}
			else
			{
				bab_debug(__LINE__ . ' ' . basename(__FILE__) . ' ' . __FUNCTION__ . ' ERROR ==> ' . $sFullPathName);
			}

			if(0 !== $oFolderFileVersion->getFlowApprobationInstanceId())
			{
				include_once $GLOBALS['babInstallPath']."utilit/afincl.php";
				deleteFlowInstance($oFolderFileVersion->getFlowApprobationInstanceId());
			}
		}
		parent::remove($oCriteria);
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

class BAB_FolderFileFieldValueSet extends BAB_BaseSet
{
	function BAB_FolderFileFieldValueSet()
	{
		parent::BAB_BaseSet(BAB_FM_FIELDSVAL_TBL);

		$this->aField = array(
		'iId' => new BAB_IntField('`id`'),
		'iIdField' => new BAB_IntField('`id_field`'),
		'iIdFile' => new BAB_IntField('`id_file`'),
		'sValue' => new BAB_StringField('`fvalue`')
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

	function setState($sState)
	{
		$this->_set('sState', $sState);
	}

	function getState()
	{
		return $this->_sGet('sState');
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

	function setAddTags($sAddTags)
	{
		$this->_set('sAddTags', $sAddTags);
	}

	function getAddTags()
	{
		return $this->_sGet('sAddTags');
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


class BAB_FmFolderCliboard extends BAB_DbRecord
{
	function BAB_FmFolderCliboard()
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

	function setDelegationOwnerId($iId)
	{
		$this->_set('iIdDgOwner', $iId);
	}

	function getDelegationOwnerId()
	{
		return $this->_iGet('iIdDgOwner');
	}

	function setRootFolderId($iId)
	{
		$this->_set('iIdRootFolder', $iId);
	}

	function getRootFolderId()
	{
		return $this->_iGet('iIdRootFolder');
	}

	function setFolderId($iId)
	{
		$this->_set('iIdFolder', $iId);
	}

	function getFolderId()
	{
		return $this->_iGet('iIdFolder');
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

	function setGroup($sGroup)
	{
		$this->_set('sGroup', $sGroup);
	}

	function getGroup()
	{
		return $this->_sGet('sGroup');
	}

	function setCollective($sCollective)
	{
		$this->_set('sCollective', $sCollective);
	}

	function getCollective()
	{
		return $this->_sGet('sCollective');
	}

	function setOwnerId($iIdOwner)
	{
		$this->_set('iIdOwner', $iIdOwner);
	}

	function getOwnerId()
	{
		return $this->_iGet('iIdOwner');
	}

	function setCheckSum($sCheckSum)
	{
		$this->_set('sCheckSum', $sCheckSum);
	}

	function getCheckSum()
	{
		return $this->_sGet('sCheckSum');
	}


	function save()
	{
		$oFmFolderCliboardSet = new BAB_FmFolderCliboardSet();
		return $oFmFolderCliboardSet->save($this);
	}
}


/**
 * Corresponds to a file
 *
 */
class BAB_FolderFile extends BAB_DbRecord
{
	function BAB_FolderFile()
	{
		parent::BAB_DbRecord();
	}

	/**
	 * Set the file identifier
	 *
	 * @param int $iId The file identifier
	 */
	function setId($iId)
	{
		$this->_set('iId', $iId);
	}

	/**
	 * Get the file identifier
	 *
	 * @return int The file identifier
	 */
	function getId()
	{
		return $this->_iGet('iId');
	}

	/**
	 * Set the filename
	 *
	 * @param string $sName The filename
	 */
	function setName($sName)
	{
		$this->_set('sName', $sName);
	}

	/**
	 * Get the filename
	 *
	 * @return string The filename
	 */
	function getName()
	{
		return $this->_sGet('sName');
	}

	/**
	 * Set the file description
	 *
	 * @param string $sDescription The file description
	 */
	function setDescription($sDescription)
	{
		$this->_set('sDescription', $sDescription);
	}

	/**
	 * Get the file description
	 *
	 * @return string The file description
	 */
	function getDescription()
	{
		return $this->_sGet('sDescription');
	}

	/**
	 * Set the relative pathname of the file, the pathname must not begin with a slash
	 * and must ending with a slash i.e(F1/F1.1/F1.1.1/)
	 *
	 * @param string $sPathName The relative pathname of the file
	 */
	function setPathName($sPathName)
	{
		if(isset($GLOBALS['babFileNameTranslation']))
		{
			$sPathName = strtr($sPathName, $GLOBALS['babFileNameTranslation']);
		}
		$this->_set('sPathName', $sPathName);
	}

	/**
	 * Get the relative pathname of the file
	 *
	 * @return string The relative pathname of the file
	 */
	function getPathName()
	{
		return $this->_sGet('sPathName');
	}

	/**
	 * Set the first parent collective path identifier that the file belong to
	 *
	 * @param int $iIdOwner The first parent collective path identifier that the file belong to
	 */
	function setOwnerId($iIdOwner)
	{
		$this->_set('iIdOwner', $iIdOwner);
	}

	/**
	 * Get the first parent collective path identifier that the file belong to
	 *
	 * @return int The first parent collective path identifier that the file belong to
	 */
	function getOwnerId()
	{
		return $this->_iGet('iIdOwner');
	}

	/**
	 * Set if the file is a personnal file or a file manager file
	 * 'Y' for a file manager.
	 * 'N' for a personnal file.
	 *
	 * @param string $sGroup 'Y' if the file is a file manager file. 'N' if the file is a personnal file
	 */
	function setGroup($sGroup)
	{
		$this->_set('sGroup', $sGroup);
	}

	/**
	 * Get if the file is a personnal file or a file manager file
	 *
	 * @return string 'Y' if the file is a file manager file. 'N' if the file is a personnal file
	 */
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


	/**
	 * Set the read only status of the file
	 * 'Y' if the file is read only
	 * 'N' if the file is not read only
	 * 
	 * @param string $sReadOnly The read only flag of the file
	 */
	function setReadOnly($sReadOnly)
	{
		$this->_set('sReadOnly', $sReadOnly);
	}

	/**
	 * Get the read only status of the file
	 * 'Y' if the file is read only
	 * 'N' if the file is not read only
	 * 
	 * @return string 'Y' if the file is read only. 'N' if the file is not read only
	 */
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

	/**
	 * Set the creation date of the file in ISO format
	 *
	 * @param string $sCreation ISO datetime
	 */
	function setCreationDate($sCreation)
	{
		$this->_set('sCreation', $sCreation);
	}

	/**
	 * Set the creation date of the file in ISO
	 * 
	 * @return string The ISO datetime
	 */
	function getCreationDate()
	{
		return $this->_sGet('sCreation');
	}

	/**
	 * Set the author identifier of the file
	 *
	 * @param int $iIdAuthor Identifier of the author
	 */
	function setAuthorId($iIdAuthor)
	{
		$this->_set('iIdAuthor', $iIdAuthor);
	}

	/**
	 * Get the identifier of the file author
	 *
	 * @return int The identifier of the file author
	 */
	function getAuthorId()
	{
		return $this->_iGet('iIdAuthor');
	}

	/**
	 * Set the modified date of the file in ISO format
	 *
	 * @param string $sModified The modified date of the file in ISO format
	 */
	function setModifiedDate($sModified)
	{
		$this->_set('sModified', $sModified);
	}

	/**
	 * Get the modified date of the file in ISO format
	 *
	 * @return string  The modified date of the file in ISO format
	 */
	function getModifiedDate()
	{
		return $this->_sGet('sModified');
	}

	/**
	 * Set the user identifier of the file modifier 
	 *
	 * @param int $iIdModifier The user identifier of the file modifier
	 */
	function setModifierId($iIdModifier)
	{
		$this->_set('iIdModifier', $iIdModifier);
	}

	/**
	 * Get the user identifier of the file modifier 
	 *
	 * @return int The user identifier of the file modifier
	 */
	function getModifierId()
	{
		return $this->_iGet('iIdModifier');
	}

	/**
	 * Set the file approbation status
	 *
	 * @param string $sConfirmed The file approbation status. 'Y' the file is approuved. 'N' the is waiting for approbation
	 */
	function setConfirmed($sConfirmed)
	{
		$this->_set('sConfirmed', $sConfirmed);
	}

	/**
	 * Get the file approbation status
	 *
	 * @return string The file approbation status. 'Y' the file is approuved. 'N' the is waiting for approbation
	 */
	function getConfirmed()
	{
		return $this->_sGet('sConfirmed');
	}

	/**
	 * Set the hits number of the file
	 *
	 * @param int $iHits The hit number
	 */
	function setHits($iHits)
	{
		$this->_set('iHits', $iHits);
	}

	/**
	 * Get the hits number of the file
	 *
	 * @return int The hit number of the file
	 */
	function getHits()
	{
		return $this->_iGet('iHits');
	}

	/**
	 * Set the identifier of the approbation scheme
	 *
	 * @param int $iIdFlowApprobationInstance The identifier of the approbation scheme
	 */
	function setFlowApprobationInstanceId($iIdFlowApprobationInstance)
	{
		$this->_set('iIdFlowApprobationInstance', $iIdFlowApprobationInstance);
	}

	/**
	 * Get the identifier of the approbation scheme
	 *
	 * @return int The identifier of the approbation scheme
	 */
	function getFlowApprobationInstanceId()
	{
		return $this->_iGet('iIdFlowApprobationInstance');
	}

	/**
	 * Set the identifier of the file version
	 *
	 * @param int $iIdFolderFileVersion The identifier of the file version
	 */
	function setFolderFileVersionId($iIdFolderFileVersion)
	{
		$this->_set('iIdFolderFileVersion', $iIdFolderFileVersion);
	}

	/**
	 * Get the identifier of the file version
	 *
	 * @return int The identifier of the file version
	 */
	function getFolderFileVersionId()
	{
		return $this->_iGet('iIdFolderFileVersion');
	}

	/**
	 * Set the major version of the file
	 *
	 * @param int $iVerMajor The major version of the file
	 */
	function setMajorVer($iVerMajor)
	{
		$this->_set('iVerMajor', $iVerMajor);
	}

	/**
	 * Get the major version of the file
	 *
	 * @return int The major version of the file
	 */
	function getMajorVer()
	{
		return $this->_iGet('iVerMajor');
	}

	/**
	 * Set the minor version of the file
	 *
	 * @param int $iVerMinor The minor version of the file
	 */
	function setMinorVer($iVerMinor)
	{
		$this->_set('iVerMinor', $iVerMinor);
	}

	/**
	 * Get the minor version of the file
	 *
	 * @return int The minor version of the file
	 */
	function getMinorVer()
	{
		return $this->_iGet('iVerMinor');
	}

	/**
	 * Set the comment of the file
	 *
	 * @param string $sVerComment The comment of the file
	 */
	function setCommentVer($sVerComment)
	{
		$this->_set('sVerComment', $sVerComment);
	}

	/**
	 * Get the comment of the file
	 *
	 * @return string The comment of the file
	 */
	function getCommentVer()
	{
		return $this->_sGet('sVerComment');
	}

	/**
	 * Set the status index of the file
	 *
	 * @param int $iIndexStatus The status index of the file
	 */
	function setStatusIndex($iIndexStatus)
	{
		$this->_set('iIndexStatus', $iIndexStatus);
	}

	/**
	 * Get the status index of the file
	 *
	 * @return int The status index of the file
	 */
	function getStatusIndex()
	{
		return $this->_iGet('iIndexStatus');
	}

	/**
	 * Set the delegation identifier of the file
	 *
	 * @param int $iId The delagation identifier
	 */
	function setDelegationOwnerId($iId)
	{
		$this->_set('iIdDgOwner', $iId);
	}

	/**
	 * Get the delegation identifier of the file
	 *
	 * @return int The delagation identifier 
	 */
	function getDelegationOwnerId()
	{
		return $this->_iGet('iIdDgOwner');
	}

	/**
	 * Save the file
	 *
	 */
	function save()
	{
		$oFolderFileSet = new BAB_FolderFileSet();
		$oFolderFileSet->save($this);
	}
	
	/**
	 * Returns the full pathname of the file
	 * 
	 * @return string
	 */
	function getFullPathname()
	{
		$sFmPath = BAB_FileManagerEnv::getCollectivePath($this->getDelegationOwnerId());
		return $sFmPath . $this->getPathName() . $this->getName();
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

	function getComment()
	{
		return $this->_sGet('sComment');
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


class BAB_FolderFileFieldValue extends BAB_DbRecord
{
	function BAB_FolderFileFieldValue()
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

	function setIdField($iIdField)
	{
		$this->_set('iIdField', $iIdField);
	}

	function getIdField()
	{
		return $this->_iGet('iIdField');
	}

	function setValue($sValue)
	{
		$this->_set('sValue', $sValue);
	}

	function getValue()
	{
		return $this->_sGet('sValue');
	}

	function save()
	{
		$oFolderFileFieldValueSet = new BAB_FolderFileFieldValueSet();
		$oFolderFileFieldValueSet->save($this);
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
		global $babBody;
		
		$oFmFolderSet = new BAB_FmFolderSet();
		$oId =& $oFmFolderSet->aField['iId'];
		return $oFmFolderSet->get($oId->in($iId));
	}

	function getFileInfoForCollectiveDir($iIdFolder, $sPath, &$iIdOwner, &$sRelativePath, &$oFmFolder)
	{
		$bSuccess = true;

		$oFmFolder = BAB_FmFolderHelper::getFmFolderById($iIdFolder);
		if(!is_null($oFmFolder))
		{
			$iIdOwner = $oFmFolder->getId();
			
			if($oFmFolder->getName() === $sPath || '' === $sPath)
			{
				$sRelativePath = $oFmFolder->getName() . '/';
			}
			else 
			{
				$sRelativePath = $sPath . (($sPath{strlen($sPath) - 1} !== '/') ? '/' : '');

				$oFmFolder = BAB_FmFolderSet::getFirstCollectiveFolder($sRelativePath);
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

	function getInfoFromCollectivePath($sPath, &$iIdRootFolder, &$oFmFolder, $bParentPath = false)
	{
		$bSuccess = false;
		
		$oRootFmFolder = BAB_FmFolderSet::getRootCollectiveFolder($sPath);
		if(!is_null($oRootFmFolder))
		{
			$iIdRootFolder = $oRootFmFolder->getId();

			$sRelativePath = canonizePath($sPath);

			$oFmFolder = null;

			if(false === $bParentPath)
			{
//				require_once $GLOBALS['babInstallPath'] . 'utilit/devtools.php';
//				bab_debug_print_backtrace();
//				bab_debug('sRelativePath ==> ' . $sRelativePath);
								
				$oFmFolder = BAB_FmFolderSet::getFirstCollectiveFolder($sRelativePath);
			}
			else 
			{
				$oFmFolder = BAB_FmFolderSet::getFirstCollectiveParentFolder($sRelativePath);
			}
				
			if(!is_null($oFmFolder))
			{
//				$iIdRootFolder = $oFmFolder->getId();
				$bSuccess = true;
			}
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

	function createDirectory($sFullPathName)
	{
		global $babBody;
		$bSuccess = true;
		
		if(strlen(trim($sFullPathName)) > 0 && false === strstr($sFullPathName, '..'))
		{
			if(isset($GLOBALS['babFileNameTranslation']))
			{
				$sFullPathName = strtr($sFullPathName, $GLOBALS['babFileNameTranslation']);
			}

			if(!is_dir($sFullPathName))
			{
				$sUploadPath = BAB_FmFolderHelper::getUploadPath();
				$sRelativePath = substr($sFullPathName, strlen($sUploadPath));
				$bSuccess = BAB_FmFolderHelper::makeDirectory($sUploadPath, $sRelativePath);
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

	function makeDirectory($sUploadPath, $sRelativePath)
	{
		$aPaths = explode('/', $sRelativePath);
		if(is_array($aPaths) && count($aPaths) > 0)
		{
			$sPath = removeEndSlah($sUploadPath);
			foreach($aPaths as $sPathItem)
			{
				if(strlen(trim($sPathItem)) !== 0)
				{
					$sPath .= '/' . $sPathItem;
					if(!is_dir($sPath))
					{
						if(!bab_mkdir($sPath, $GLOBALS['babMkdirMode']))
						{
							return false;
						}
					}
				}
			}
			return true;
		}
		return false;
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

			$sUploadPath = canonizePath(realpath($sUploadPath));
			if(strlen(trim($sRelativePath)) > 0)
			{
				$sPathName		= canonizePath(realpath($sUploadPath . $sRelativePath));
				$sOldPathName	= canonizePath(realpath($sPathName . $sOldName));
//				bab_debug('*** sUploadPath ==> ' . $sUploadPath);
//				bab_debug('*** sRelativePath ==> ' . $sRelativePath);
//				bab_debug('*** sPathName ==> ' . $sPathName);
				$sNewPathName	= $sPathName . $sNewName;
			}
			else
			{
				$sOldPathName	= canonizePath(realpath($sUploadPath . $sOldName));
				$sNewPathName	= $sUploadPath . $sNewName;
			}

			/*
			bab_debug('sUploadPath ==> ' . $sUploadPath);
			bab_debug('sRelativePath ==> ' . $sRelativePath);
			bab_debug('sOldPathName ==> ' . $sOldPathName);
			bab_debug('sNewPathName ==> ' . $sNewPathName);
			//*/
			
			$sUploadPath = realpath($sUploadPath);
			$bOldPathNameValid = (realpath(substr($sOldPathName, 0, strlen($sUploadPath))) === $sUploadPath);

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
				$babBody->msgerror = bab_translate("Access denied");
				$bSuccess = false;
			}
		}
		else
		{
			$babBody->msgerror = bab_translate("Access denied");
			$bSuccess = false;
		}
		return $bSuccess;
	}
}


/**
 * This class give information about the environment of the file manager.
 * The path, the acl
 * 
 */
class BAB_FileManagerEnv
{
 	/**
	 * Upload path of the file manager, the upload path
	 * contain two folders the root collectives folders and
	 * the root user personnals folders
	 * 
	 * @access private 
	 * @var string Upload path of the file manager
	 */
	var $sFmUploadPath = null;
	
	var $sFmRootPath = null;
	
 	/**
	 * Path of the collective folder where is the user
	 * 
	 * @access private 
	 * @var string The full path name
	 */
	var $sFmCollectivePath = null;
	
	
 	/**
	 * Path of the user folder where is the user
	 * 
	 * @access private 
	 * @var string The full path name
	 */
	var $sFmPersonnalPath = null;


 	/**
	 * Allow to know if the directory currently processed is a collective or user
	 * 
	 * @access private 
	 * @var string Y if this is a collective folder, N if this is a user folder
	 */
	var $sGr = null;


 	/**
	 * Relative path of folder where the user is.
	 * 
	 * @access private 
	 * @var string The relative path
	 */
	var $sPath = null;


 	/**
	 * If $sGr == 'Y' so this variable is the identifier of the first collective folder
	 * If $sGr == 'N' so this variable is the identifier of the user
	 * 
	 * @access private 
	 * @var integer
	 */
	var $iId = null;


 	/**
	 * If $sGr == 'Y' so this variable is the identifier of the collective folder or the identifier 
	 * of the first collective parent folder
	 * 
	 * @access private 
	 * @var integer
	 */
	var $iIdObject = null;


 	/**
	 * Is the connected user have a personnal storage
	 * 
	 * @access private 
	 * @var boolean True if the user have a personnal storage, false otherwise
	 */
	var $bUserHaveStorage = false;


 	/**
	 * The first directory collective compared to the position of the user
	 * 
	 * @access private 
	 * @var BAB_FmFolder The first directory collective compared to the position of the user or null
	 */
	var $oFmFolder = null;
	
	var $sEndSlash = '';
	var $iPathLength = 0;
	var $sRelativePath = '';
	var $oAclFm	= null;
	var $sFmCollectiveRootPath = '';
	
	var $oFmRootFolder = null;
	
	function BAB_FileManagerEnv()
	{
		
	}
	
	
	function getValuesFromRequest() {
		global $BAB_SESS_USERID, $babBody;

		$this->sPath = (string) bab_rp('path');
		$this->sGr = (string) bab_rp('gr', '');

		if(!empty($BAB_SESS_USERID))
		{
			$this->iIdObject = (int) bab_rp('id', $BAB_SESS_USERID);
		}
		else
		{
			$this->iIdObject = (int) bab_rp('id', 0);
		}
	}
	
	
	
	function init()
	{
		global $BAB_SESS_USERID, $babBody;
		
		$this->iPathLength = strlen(trim($this->sPath));
		$this->sEndSlash = '';
		if($this->iPathLength > 0)
		{
			$this->sEndSlash = '/';
		}	

		//
		{
			$this->iId				= $this->iIdObject;
			$this->sFmUploadPath	= BAB_FmFolderHelper::getUploadPath() . 'fileManager/';
			$this->sRelativePath	= $this->sPath . $this->sEndSlash;
	
			$this->oFmRootFolder = BAB_FmFolderHelper::getFmFolderById($this->iIdObject);			
			//'' === $this->sGr si on à cliqué depuis la section utilisateur
			if(!is_null($this->oFmRootFolder) && '' !== $this->sGr)
			{
				$this->iIdObject = $this->oFmRootFolder->getId();
				
				$this->sFmCollectiveRootPath	= $this->sFmUploadPath . 'collectives/DG' . $this->oFmRootFolder->getDelegationOwnerId() . '/';	
				$this->sFmCollectivePath		= $this->sFmCollectiveRootPath;
				$this->sFmRootPath				= $this->sFmCollectivePath;
				$this->sFmCollectivePath		.= $this->sRelativePath;
				
				bab_setCurrentUserDelegation($this->oFmRootFolder->getDelegationOwnerId());
				$iIdRootFolder = 0;
				BAB_FmFolderHelper::getInfoFromCollectivePath($this->sPath, $iIdRootFolder, $this->oFmFolder);
				if(!is_null($this->oFmFolder))
				{
					$this->iIdObject = $this->oFmFolder->getId();
				}
			}
		}
		
		if('N' === $this->sGr)
		{
			if(userHavePersonnalStorage())
			{
				$this->sFmRootPath		= $this->sFmUploadPath . 'users/U' . $BAB_SESS_USERID . '/';
				$this->sFmPersonnalPath	= $this->sFmRootPath;
				
				if(!is_dir($this->sFmPersonnalPath))
				{
					BAB_FmFolderHelper::createDirectory($this->sFmPersonnalPath);
				}
				
				if(0 !== strlen(trim($this->sPath)))
				{
					$this->sFmPersonnalPath .= $this->sRelativePath;
				}
			}
		}
		else if('Y' === $this->sGr)
		{
//			$this->sFmCollectiveRootPath	= $this->sFmUploadPath . 'collectives/DG' . $oFmFolder->getDelegationOwnerId() . '/';	
//			$this->sFmCollectivePath		= $this->sFmCollectiveRootPath;
//			$this->sFmRootPath				= $this->sFmCollectivePath;
//			$this->sFmCollectivePath		.= $this->sRelativePath;
//			
//			BAB_FmFolderHelper::getInfoFromCollectivePath($this->sPath, $this->iIdObject, $this->oFmFolder);
		}
		if('' === $this->sGr)
		{
			if(userHavePersonnalStorage())
			{
				$this->sFmPersonnalPath = $this->sFmUploadPath . 'users/';
			}
		}
	}
	
	
	function setParentPath(&$sUrl)
	{
		if(0 !== $this->iPathLength) 
		{
			$sParentPath = removeLastPath($this->sPath);
			if('N' === $this->sGr)
			{
				if($sParentPath !== $this->sPath)
				{
					$sUrl .= urlencode($sParentPath);
				}
				return true;
			}
			else if('Y' === $this->sGr)
			{
				if($sParentPath !== $this->sPath)
				{
					$sUrl .= urlencode($sParentPath);
					return true;
				}
			}
		}
		return false;
	}
	
	function getParentPath()
	{
		return $this->sParentPath;
	}
	
	function getPersonnalFolderPath()
	{
		return $this->sFmPersonnalPath;
	}
	
	function getCollectiveFolderPath()
	{
		return $this->sFmCollectivePath;
	}
	
	function getCurrentFmPath()
	{
		if('Y' === $this->sGr)
		{
			return $this->getCollectiveFolderPath();
		}
		else if('N' === $this->sGr)
		{
			return $this->getPersonnalFolderPath();
		}
		return '';
	}
	
	function getFmUploadPath()
	{
		return $this->sFmUploadPath;
	}
	
	function getRootFmPath()
	{
		return $this->sFmRootPath;
	}
	
	function getCollectiveRootFmPath()
	{
		return $this->sFmCollectiveRootPath;
	}
	
	function getCollectivePath($iIdDelegation)
	{
		return BAB_FmFolderHelper::getUploadPath() . 'fileManager/collectives/DG' . $iIdDelegation . '/';
	}
	
	function userIsInRootFolder()
	{
		return ('' === (string) $this->sGr && 0 === (int) $this->iPathLength);
	}
	
	function userIsInCollectiveFolder()
	{
		return ('Y' === (string) $this->sGr);
	}
	
	function userIsInPersonnalFolder()
	{
		return ('N' === (string) $this->sGr);
	}
	
	function accessValid()
	{
		if($this->pathValid())
		{
			if('N' === $this->sGr)
			{
				if(true === userHaveRightOnCollectiveFolder() || true === userHavePersonnalStorage())
				{
					if(0 === $this->iPathLength)
					{
						return true;
					}
					else 
					{
						if(true === bab_userIsloggedin())
						{
							if(is_dir(realpath($this->sFmPersonnalPath)))
							{
								return true;
							}
						}
					}
				}
			}
			else if('Y' === $this->sGr)
			{
				$sParentPath = $this->sRelativePath;
				if(!is_null($this->oFmFolder))
				{
					if(true === canManage($sParentPath) || true === haveRightOn($sParentPath, BAB_FMMANAGERS_GROUPS_TBL))
					{
						return true;
					}
					else if(true === canUpload($sParentPath) || true === canDownload($sParentPath) || true === canUpdate($sParentPath))
					{
						if(/*'N' === $this->oFmFolder->getHide() &&*/ 'Y' === $this->oFmFolder->getActive())
						{					
							return true;
						}
					}
					else
					{
						$arrschi = bab_getWaitingIdSAInstance($GLOBALS['BAB_SESS_USERID']);
						return (is_array($arrschi) && count($arrschi) > 0);
					}
				}
			}
			else if('' === $this->sGr)
			{
				if(true === userHaveRightOnCollectiveFolder() || true === userHavePersonnalStorage())
				{
					if(0 === $this->iPathLength)
					{
						return true;
					}
				}
			}
		}
		return false;		
	}
	
	function pathValid()
	{
		global $babBody;
		
		$sUploadPath = BAB_FmFolderHelper::getUploadPath();

		if(strlen(trim($sUploadPath)) === 0)
		{
			$babBody->addError(bab_translate("The upload path is not set"));
			return false;
		}
		else
		{
			if(!is_dir(realpath($sUploadPath)))
			{
				$babBody->addError(bab_translate("The upload path is not a dir"));
				return false;
			}
		}

		if(false !== strstr($this->sPath, '..'))
		{
			$babBody->addError(bab_translate("Access denied"));
			return false;
		}
		
		if(false === $this->userIsInRootFolder())
		{
			$sRootFmPath = $this->getRootFmPath();
			
			if(!is_dir(realpath($this->getCurrentFmPath())))
			{
				$babBody->addError(bab_translate("The folder does not exist"));
				
				if(bab_isUserAdministrator())
				{
					$babBody->addError(bab_translate("Folder path") . ' : ' . $this->getCurrentFmPath());
				}
				return false;
			}
		}
		return true;
	}
}
		
function canonizePath($sPath)
{
	return addEndSlash(removeEndSlashes($sPath));
}


function replaceInvalidFolderNameChar($sName)
{
	if(is_string($sName) && strlen(trim($sName)) > 0)
	{
		if(isset($GLOBALS['babFileNameTranslation']))
		{
			$sName = strtr($sName, $GLOBALS['babFileNameTranslation']);
		}
		
		static $aTranslation = array('\\' => '_', '/' => '_', ':' => '_', '*' => '_', '?' => '_', '<' => '_', '>' => '_', '|' => '_');
		$sName = strtr($sName, $aTranslation);
	}
	return $sName;
}

function processFolderName($sUploadPath, $sName)
{
	$sName = replaceInvalidFolderNameChar($sName);

	$iIdx = 0;
	
	$sTempDirName = $sName;
	while(is_dir($sUploadPath . $sTempDirName))
	{
		$sTempDirName = $sName . ((string) $iIdx);
		$iIdx++;
	}
	return $sTempDirName;
}


function addEndSlash($sPath)
{
	if(is_string($sPath))
	{
		$iLength = strlen(trim($sPath));
		if($iLength > 0)
		{
			if($sPath{$iLength - 1} !== '/' && $sPath{$iLength - 1} !== '\\')
			{
				$sPath .= '/';
			}
		}
	}
	return $sPath;
}

function removeEndSlah($sPath)
{
	if(is_string($sPath))
	{
		$iLength = strlen(trim($sPath));
		if($iLength > 0)
		{
			if($sPath{$iLength - 1} === '/' || $sPath{$iLength - 1} === '\\')
			{
				return substr($sPath, 0, -1);
			}
		}
	}
	return $sPath;
}


function haveEndSlash($sPath)
{
	$iLength = strlen(trim($sPath));
	if($iLength > 0)
	{
		return ($sPath{$iLength - 1} === '/' || $sPath{$iLength - 1} === '\\');
	}
	return false;	
}




function removeEndSlashes($sPath)
{
	while(haveEndSlash($sPath))
	{
		$sPath = removeEndSlah($sPath);
	}
	return $sPath;
}




function getUrlPath($sRelativePath)
{
	$sPathName = '';
	$aPath = explode('/', $sRelativePath);
	if(is_array($aPath))
	{
		$iCount = count($aPath);
		if($iCount >= 2)
		{
			unset($aPath[0]);

			if(count($aPath) > 0)
			{
				$sPathName = implode('/', $aPath);
				$sPathName = substr($sPathName, 0, -1);
			}
		}
	}
	return $sPathName;
}

function getFirstPath($sPath)
{
	$iLength = strlen(trim($sPath));
	if($iLength > 0)
	{
		$aPath = explode('/', $sPath);
		if(is_array($aPath))
		{
			return $aPath[0];
		}
	}
	return $sPath;	
}

function getLastPath($sPath)
{
	$iLength = strlen(trim($sPath));
	if($iLength > 0)
	{
		$aPath = explode('/', $sPath);
		if(is_array($aPath))
		{
			$iCount = count($aPath);
			if($iCount >= 2)
			{
				if('/' === $sPath{$iLength - 1})
				{
					return $aPath[$iCount - 2];
				}
				else 
				{
					return $aPath[$iCount - 1];
				}
			}
		}
	}
	return $sPath;	
}

function removeFirstPath($sPath)
{
	$iLength = strlen(trim($sPath));
	if($iLength > 0)
	{
		$aPath = explode('/', $sPath);
		if(is_array($aPath))
		{
			$iCount = count($aPath);
			if($iCount >= 2)
			{
				unset($aPath[0]);
				return implode('/', $aPath);
			}
		}
	}
	return $sPath;	
}

function removeLastPath($sPath)
{
	$iLength = strlen(trim($sPath));
	if($iLength > 0)
	{
		$aPath = explode('/', $sPath);
		if(is_array($aPath))
		{
			$iCount = count($aPath);
			if($iCount >= 2)
			{
				$aToRemove = array();
				if($sPath{$iLength - 1} == '/')
				{
					$aToRemove = array($iCount - 1, $iCount - 2);
				}
				else
				{
					$aToRemove = array($iCount - 1);
				}
				
				foreach($aToRemove as $iIdx)
				{
					unset($aPath[$iIdx]);
				}
				return implode('/', $aPath);
			}
		}
	}
	return $sPath;
}


if(!function_exists('is_a'))
{
	function is_a($object, $class)
	{
		if(!is_object($object))
		{
			return false;
		}
		if(strtolower(get_class($object)) === strtolower($class))
		{
			return true;
		}
		return is_subclass_of($object, $class);
	}
}

function initEnvObject()
{
	$oFileManagerEnv =& getEnvObject();
	$oFileManagerEnv->getValuesFromRequest();
	$oFileManagerEnv->init();
}

function &getEnvObject()
{
	if(!array_key_exists('babFmEnvObject', $GLOBALS))
	{
		$GLOBALS['babFmEnvObject'] = new BAB_FileManagerEnv();
		$GLOBALS['babFmEnvObject']->init();
	}
	return $GLOBALS['babFmEnvObject'];
}



/////// ACL For the filemanager


function canManage($sPath)
{
	$oFileManagerEnv =& getEnvObject();
	$sFullPath = $oFileManagerEnv->getRootFmPath() . $sPath;
	
	static $aPath = array();
	if(!array_key_exists($sFullPath, $aPath))
	{
//		require_once $GLOBALS['babInstallPath'] . 'utilit/devtools.php';
//		bab_debug_print_backtrace();
//	
//		bab_debug(__FUNCTION__ . ' sPath ==> ' . $sPath . ' CurrentFmPath ==> ' . $sFullPath);

		$aPath[$sFullPath] = haveRightOnParent($sPath, BAB_FMMANAGERS_GROUPS_TBL);
	}
	return $aPath[$sFullPath];
}


function canUpload($sPath)
{
	$oFileManagerEnv =& getEnvObject();
	$sFullPath = $oFileManagerEnv->getRootFmPath() . $sPath;
	
	static $aPath = array();
	if(!array_key_exists($sFullPath, $aPath))
	{
		$aPath[$sFullPath] = haveRightOn($sPath, BAB_FMUPLOAD_GROUPS_TBL);
	}	
	return $aPath[$sFullPath];
}


function canDownload($sPath)
{
	$oFileManagerEnv =& getEnvObject();
	$sFullPath = $oFileManagerEnv->getRootFmPath() . $sPath;
	
	static $aPath = array();
	if(!array_key_exists($sFullPath, $aPath))
	{
		$aPath[$sFullPath] = haveRightOn($sPath, BAB_FMDOWNLOAD_GROUPS_TBL);
	}	
	return $aPath[$sFullPath];
}


function canUpdate($sPath)
{
	$oFileManagerEnv =& getEnvObject();
	$sFullPath = $oFileManagerEnv->getRootFmPath() . $sPath;
	
	static $aPath = array();
	if(!array_key_exists($sPath, $aPath))
	{
		$aPath[$sFullPath] = haveRightOn($sPath, BAB_FMUPDATE_GROUPS_TBL);
	}	
	return $aPath[$sFullPath];
}


function canBrowse($sPath)
{
	$oFileManagerEnv =& getEnvObject();
	$sFullPath = $oFileManagerEnv->getRootFmPath() . $sPath;
	
	static $aPath = array();
	if(!array_key_exists($sFullPath, $aPath))
	{
		$aPath[$sFullPath] = haveRightOn($sPath, BAB_FMMANAGERS_GROUPS_TBL) || canUpload($sPath) ||
			canDownload($sPath) || canUpdate($sPath);
	}
	return $aPath[$sFullPath];
}


function canCreateFolder($sPath)
{
	$oFileManagerEnv =& getEnvObject();
	$sFullPath = $oFileManagerEnv->getRootFmPath() . $sPath;
	
	static $aPath = array();
	if(!array_key_exists($sFullPath, $aPath))
	{
		$aPath[$sFullPath] = haveRight($sPath, BAB_FMMANAGERS_GROUPS_TBL);
	}
	return $aPath[$sFullPath];
}


function canEdit($sPath)
{
	return canManage($sPath);
}


function canSetRight($sPath)
{
	return canManage($sPath);
}


function canCutFolder($sPath)
{
	return canManage($sPath);
}


function canCutFile($sPath)
{
	$oFileManagerEnv =& getEnvObject();
	$sFullPath = $oFileManagerEnv->getRootFmPath() . $sPath;
	
	static $aPath = array();
	if(!array_key_exists($sFullPath, $aPath))
	{
		$aPath[$sFullPath] = haveRightOn($sPath, BAB_FMMANAGERS_GROUPS_TBL);
	}	
	return $aPath[$sFullPath];
}


function canDelFile($sPath)
{
	return canCutFile($sPath);
}


function canPasteFolder($iIdSrcRootFolder, $sSrcPath, $bSrcPathIsCollective, $iIdTrgRootFolder, $sTrgPath)
{
//	bab_debug(__LINE__ . ' ' . basename(__FILE__) . ' ' . __FUNCTION__ . 
//		' iIdSrcRootFolder ==> ' . $iIdSrcRootFolder . ' sSrcPath ==> ' . $sSrcPath .
//		' iIdTrgRootFolder ==> ' . $iIdTrgRootFolder . ' sTrgPath ==> ' . $sTrgPath);
	
	$oFileManagerEnv		=& getEnvObject();
	$bHaveRightOnSrc		= false;
	$bHaveRightOnTrg		= false;
	$bFileExistOnTrgPath	= false;
	$iIdLocalSrcRootFolder	= 0;
	
	if($oFileManagerEnv->userIsInCollectiveFolder() || ($oFileManagerEnv->userIsInRootFolder() && $bSrcPathIsCollective))
	{
		$sRootFmPath = BAB_FileManagerEnv::getCollectivePath(bab_getCurrentUserDelegation());
		$oOwnerSrcFmFolder = null;
		$bParentPath = true;
		
		$sSrcPath = canonizePath($sSrcPath);
		
		$bIsRootFolder = ('' === removeLastPath($sSrcPath));
		
		if(false === $bIsRootFolder)
		{
			$bSuccess = BAB_FmFolderHelper::getInfoFromCollectivePath($sSrcPath, $iIdLocalSrcRootFolder, $oOwnerSrcFmFolder, $bParentPath);
			if($bSuccess)
			{
				if(0 !== $iIdLocalSrcRootFolder && $iIdLocalSrcRootFolder === $iIdSrcRootFolder)
				{
					if($iIdLocalSrcRootFolder === $oOwnerSrcFmFolder->getId() && $oFileManagerEnv->userIsInRootFolder())
					{
						$bHaveRightOnSrc = haveAdministratorRight();
					}
					else 
					{
						$bHaveRightOnSrc = bab_isAccessValid(BAB_FMMANAGERS_GROUPS_TBL, $oOwnerSrcFmFolder->getId());
					}
				}
				else 
				{
//					bab_debug(__LINE__ . ' ' . basename(__FILE__) . ' ' . __FUNCTION__ . 
//						' Bad root folder id iIdRootFolder ==> ' . $iIdLocalSrcRootFolder .
//						' iIdSrcRootFolder ==> ' . $iIdSrcRootFolder);
					return false;
				}
			}
			else 
			{
//				bab_debug(__LINE__ . ' ' . basename(__FILE__) . ' ' . __FUNCTION__ . 
//					' Cannot get info for  iIdSrcRootFolder ==> ' . $iIdSrcRootFolder .
//					' sSrcPath ==> ' . $sSrcPath);
				return false;
			}
		}
		else 
		{
			$bHaveRightOnSrc = haveAdministratorRight();
		}

		if(!$oFileManagerEnv->userIsInRootFolder())
		{
			$iIdRootFolder = 0;
			$oOwnerTrgFmFolder = null;
			$sTrgPath = canonizePath($sTrgPath);
			
			$bIsRootFolder = ('' === removeLastPath($sTrgPath));
			
			if(false === $bIsRootFolder)
			{
				$bSuccess = BAB_FmFolderHelper::getInfoFromCollectivePath($sTrgPath, $iIdRootFolder, $oOwnerTrgFmFolder, $bParentPath);
				if($bSuccess)
				{
					if(0 !== $iIdRootFolder && $iIdRootFolder === $iIdTrgRootFolder && !is_null($oOwnerTrgFmFolder))
					{
						$bHaveRightOnTrg = bab_isAccessValid(BAB_FMMANAGERS_GROUPS_TBL, $oOwnerTrgFmFolder->getId());
					}
					else 
					{
						bab_debug(__LINE__ . ' ' . basename(__FILE__) . ' ' . __FUNCTION__ . ' PAS GLOP For sTrgPath 1 ==> ' . $sTrgPath);
						return false;
					}
				}
				else 
				{
					bab_debug(__LINE__ . ' ' . basename(__FILE__) . ' ' . __FUNCTION__ . ' PAS GLOP For sTrgPath 2 ==> ' . $sTrgPath);
					return false;
				}
			}
			else 
			{
				$bHaveRightOnTrg = haveRight($sTrgPath, BAB_FMMANAGERS_GROUPS_TBL);
			}
		}
		else 
		{
			$bHaveRightOnTrg = haveAdministratorRight();
		}
	}
	else if($oFileManagerEnv->userIsInPersonnalFolder())
	{
		$sRootFmPath = $oFileManagerEnv->getRootFmPath();
		$bHaveRightOnSrc = isUserFolder($sSrcPath);
		$bHaveRightOnTrg = isUserFolder($sTrgPath);
	}	
	
//	bab_debug(__LINE__ . ' ' . basename(__FILE__) . ' ' . __FUNCTION__ . ' bHaveRightOnSrc ==> ' . (($bHaveRightOnSrc) ? 'Yes' : 'No') . ' bHaveRightOnTrg ==> ' . (($bHaveRightOnTrg) ? 'Yes' : 'No'));
	
	if($bHaveRightOnSrc && $bHaveRightOnTrg)
	{
		$sFullCurrFolder	= removeEndSlah(realpath($sRootFmPath . $sTrgPath));
		$sFullPathToPaste	= removeEndSlah(realpath($sRootFmPath . $sSrcPath));
		$sFullParentPath	= substr($sFullCurrFolder, 0, strlen($sFullPathToPaste));
		
		/*
		bab_debug('sRootFmPath ==> ' . $sRootFmPath);
		bab_debug('sSrcPath ==> ' . $sSrcPath);
		bab_debug('sTrgPath ==> ' . $sTrgPath);
		bab_debug('sFullCurrFolder ==> ' . $sFullCurrFolder);
		bab_debug('sFullPathToPaste ==> ' . $sFullPathToPaste);
		bab_debug('sFullParentPath ==> ' . $sFullParentPath);
		//*/
		
		//Est ce que dans le répertoire courant il y a un répertoire de même nom ?
		$bSameDir = true;
		$sTrgFullPathName = $sRootFmPath . $sTrgPath . getLastPath($sSrcPath);
		$sSrcFullPathName = $sRootFmPath . removeEndSlah($sSrcPath);
		if(is_dir($sTrgFullPathName))
		{
			$bSameDir = ($sSrcFullPathName === $sTrgFullPathName);
		}
		
		/*
		bab_debug(
			'bRetVal ==> ' .  (($sFullParentPath !== $sFullPathToPaste && $bSameDir) ? 'Yes' : 'No') . 
			' bSameDir ==> ' .  (($bSameDir) ? 'Yes' : 'No') . 
			' sSrcFullPathName ==> ' .  $sSrcFullPathName . 
			' sTrgFullPathName ==> ' .  $sTrgFullPathName . 
			' sFullParentPath !== sFullPathToPaste ==> ' .  (($sFullParentPath !== $sFullPathToPaste) ? 'Yes' : 'No'));
		//*/
		return ($sFullParentPath !== $sFullPathToPaste && $bSameDir);
	}
	return false;
}


function canPasteFile($iIdSrcRootFolder, $sSrcPath, $iIdTrgRootFolder, $sTrgPath, $sFilename)
{
	$oFileManagerEnv		=& getEnvObject();
	$sUpLoadPath			= $oFileManagerEnv->getCurrentFmPath();
	$bHaveRightOnSrc		= false;
	$bHaveRightOnTrg		= false;
	$bFileExistOnTrgPath	= false;
	
	if($oFileManagerEnv->userIsInCollectiveFolder())
	{
		$iIdRootFolder = 0;
		$oOwnerSrcFmFolder = null;
		BAB_FmFolderHelper::getInfoFromCollectivePath($sSrcPath, $iIdRootFolder, $oOwnerSrcFmFolder);
		if($iIdRootFolder !== $iIdSrcRootFolder || is_null($oOwnerSrcFmFolder))
		{
//			bab_debug(__LINE__ . ' ' . basename(__FILE__) . ' ' . __FUNCTION__ . ' PAS GLOP For sSrcPath');
			return false;
		}
		
		$iIdRootFolder = 0;
		$oOwnerTrgFmFolder = null;
		BAB_FmFolderHelper::getInfoFromCollectivePath($sTrgPath, $iIdRootFolder, $oOwnerTrgFmFolder);
		if($iIdRootFolder !== $iIdTrgRootFolder || is_null($oOwnerTrgFmFolder))
		{
//			bab_debug(__LINE__ . ' ' . basename(__FILE__) . ' ' . __FUNCTION__ . ' PAS GLOP For sTrgPath');
			return false;
		}
	
		$bHaveRightOnSrc = bab_isAccessValid(BAB_FMMANAGERS_GROUPS_TBL, $oOwnerSrcFmFolder->getId());
		$bHaveRightOnTrg = bab_isAccessValid(BAB_FMMANAGERS_GROUPS_TBL, $oOwnerTrgFmFolder->getId());
		
//		bab_debug($oOwnerSrcFmFolder);
//		bab_debug($oOwnerTrgFmFolder);
	}
	else if($oFileManagerEnv->userIsInPersonnalFolder())
	{
		$bHaveRightOnSrc = isUserFolder($sSrcPath);
		$bHaveRightOnTrg = isUserFolder($sTrgPath);
	}
	
	if(!is_null($sFilename))
	{
		$sFullPathname = $sUpLoadPath . $sFilename;
		$bFileExistOnTrgPath = file_exists($sFullPathname);
		
	}
	
	$iLength = strlen(trim($sSrcPath));
	if($iLength > 0 &&  '/' === $sSrcPath{$iLength - 1})
	{
		$sSrcPath = substr($sSrcPath, 0, -1);
	}
	
	$iLength = strlen(trim($sTrgPath));
	if($iLength > 0 && '/' === $sTrgPath{$iLength - 1})
	{
		$sTrgPath = substr($sTrgPath, 0, -1);
	}
	
//	bab_debug(__LINE__ . ' ' . basename(__FILE__) . ' ' . __FUNCTION__ . ' bHaveRightOnSrc ==> ' . (($bHaveRightOnSrc) ? 'Yes' : 'No') . ' bHaveRightOnTrg ==> ' . (($bHaveRightOnTrg) ? 'Yes' : 'No'));
//	bab_debug(__LINE__ . ' ' . basename(__FILE__) . ' ' . __FUNCTION__ . ' sUpLoadPath ==> ' . $sUpLoadPath);
//	bab_debug(__LINE__ . ' ' . basename(__FILE__) . ' ' . __FUNCTION__ . ' file_exists ==> ' . (($bFileExistOnTrgPath) ? 'Yes' : 'No'));
//	bab_debug(__LINE__ . ' ' . basename(__FILE__) . ' ' . __FUNCTION__ . ' iIdSrcRootFolder ==> ' . $iIdSrcRootFolder . ' iIdTrgRootFolder ==> ' . $iIdTrgRootFolder);
//	bab_debug(__LINE__ . ' ' . basename(__FILE__) . ' ' . __FUNCTION__ . ' sSrcPath ==> ' . $sSrcPath . ' sTrgPath ==> ' . $sTrgPath);
//	bab_debug(__LINE__ . ' ' . basename(__FILE__) . ' ' . __FUNCTION__ . ' sFullPathname ==> ' . $sFullPathname);
	
	return ($bHaveRightOnSrc && $bHaveRightOnTrg && (!$bFileExistOnTrgPath || ($iIdSrcRootFolder === $iIdTrgRootFolder && $sSrcPath === $sTrgPath)));
}


function haveRight($sPath, $sTableName)
{
	$oFileManagerEnv =& getEnvObject();
//	$bHaveAdminRight = bab_isUserAdministrator();
	$bHaveAdminRight = haveAdministratorRight();
	
	if($oFileManagerEnv->userIsInCollectiveFolder() || ($oFileManagerEnv->userIsInRootFolder() && !$bHaveAdminRight))
	{
		$oFmFolder = BAB_FmFolderSet::getFirstCollectiveFolder($sPath);
		if(!is_null($oFmFolder))
		{
			return bab_isAccessValid($sTableName, $oFmFolder->getId());
		}
		else 
		{
			return false;
		}
	}
	else if($bHaveAdminRight && $oFileManagerEnv->userIsInRootFolder())
	{
		return $bHaveAdminRight;
	}
	else if($oFileManagerEnv->userIsInPersonnalFolder())
	{
		return isUserFolder($sPath);
	}
	else 
	{
		return false;
	}
}


function haveRightOnParent($sPath, $sTableName)
{
	$oFileManagerEnv =& getEnvObject();
	
	if($oFileManagerEnv->userIsInCollectiveFolder())
	{
		$sRelativePath = removeLastPath($sPath);
		if($sRelativePath === '')
		{
			return bab_isUserAdministrator();
		}
		else 
		{
			$oFmFolder = BAB_FmFolderSet::getFirstCollectiveFolder($sRelativePath);
			if(!is_null($oFmFolder))
			{
				return bab_isAccessValid($sTableName, $oFmFolder->getId());
			}
			else 
			{
				return false;
			}
		}
	}
	else if($oFileManagerEnv->userIsInRootFolder())
	{
		return haveAdministratorRight();
	}
	else if($oFileManagerEnv->userIsInPersonnalFolder())
	{
		return isUserFolder($sPath);
	}
	else 
	{
		return false;
	}
}


function haveRightOn($sPath, $sTableName)
{
	$oFileManagerEnv =& getEnvObject();
	if($oFileManagerEnv->userIsInCollectiveFolder() || $oFileManagerEnv->userIsInRootFolder())
	{
		$oFmFolder = BAB_FmFolderSet::getFirstCollectiveFolder($sPath);
		if(!is_null($oFmFolder))
		{
			return bab_isAccessValid($sTableName, $oFmFolder->getId());
		}
		else 
		{
			return false;
		}
	}
	else if($oFileManagerEnv->userIsInPersonnalFolder())
	{
		return isUserFolder($sPath);
	}
	else 
	{
		return false;
	}
}

/* A utiliser seulement pour les fonction haveRightxxx quand on est à la racine*/
function haveAdministratorRight()
{
	global $babBody;
	if(0 === bab_getCurrentUserDelegation())
	{
		return bab_isUserAdministrator();
	}
	else 
	{
		return array_key_exists(bab_getCurrentUserDelegation(), array_flip($babBody->dgAdmGroups));
	}
}


function isUserFolder($sPath)
{
	global $BAB_SESS_USERID;
	
	$oFileManagerEnv =& getEnvObject();
	$sPathName = $oFileManagerEnv->getCurrentFmPath();
	
	if(userHavePersonnalStorage() && is_dir($sPathName))
	{
		$aBuffer = array();
		if(preg_match('/(U)(\d+)/', $sPathName, $aBuffer))
		{
			$iIdUser = (int) $aBuffer[2];
			return (0 !== $iIdUser && $iIdUser === (int) $oFileManagerEnv->iIdObject && (int) $BAB_SESS_USERID === $iIdUser);
		}
	}
	return false;
}


function userHavePersonnalStorage()
{
	static $bHavePersonnalStorage = null;

	if(is_null($bHavePersonnalStorage))	
	{
		global $babDB;
	
		$sQuery = 
			'SELECT  
				id
			FROM 
				' . BAB_GROUPS_TBL . '
			WHERE 
				ustorage IN(' . $babDB->quote('Y') . ')';
	
		//bab_debug($sQuery);
		$oResult = $babDB->db_query($sQuery);
		if(false !== $oResult)
		{
			$iNumRows = $babDB->db_num_rows($oResult);
			$iIndex = 0;
			while($iIndex < $iNumRows && false !== ($aDatas = $babDB->db_fetch_array($oResult)))
			{
				$iIndex++;
				if(bab_isMemberOfGroup($aDatas['id']))
				{
					$bHavePersonnalStorage = true;
				}
			}
		}
	}
	return $bHavePersonnalStorage;
}

	
function userHaveRightOnCollectiveFolder()
{
	static $bHaveRightOnCollectiveFolder = null;

	if(is_null($bHaveRightOnCollectiveFolder))	
	{
		$aIdObject = bab_getUserIdObjects(BAB_FMUPLOAD_GROUPS_TBL);
		if(is_array($aIdObject) && count($aIdObject) > 0)
		{
			$bHaveRightOnCollectiveFolder = true;
			return true;
		}
		
		$aIdObject = bab_getUserIdObjects(BAB_FMDOWNLOAD_GROUPS_TBL);
		if(is_array($aIdObject) && count($aIdObject) > 0)
		{
			$bHaveRightOnCollectiveFolder = true;
			return true;
		}
		
		$aIdObject = bab_getUserIdObjects(BAB_FMUPDATE_GROUPS_TBL);
		if(is_array($aIdObject) && count($aIdObject) > 0)
		{
			$bHaveRightOnCollectiveFolder = true;
			return true;
		}
		
		$aIdObject = bab_getUserIdObjects(BAB_FMMANAGERS_GROUPS_TBL);
		if(is_array($aIdObject) && count($aIdObject) > 0)
		{
			$bHaveRightOnCollectiveFolder = true;
			return true;
		}
	}
	return $bHaveRightOnCollectiveFolder;
}


function bab_getUserFmVisibleDelegations()
{
	static $aVisibleDelegation = null;
	
	if(is_null($aVisibleDelegation))
	{
		$aVisibleDelegation		= array();
		$aProcessedDelegation	= array();
		$bMainSite				= false;
		$iMainDelegation		= (int) 0;
		
		if(bab_isUserAdministrator())
		{
			$aProcessedDelegation[$iMainDelegation] = $iMainDelegation;
			$bMainSite = true;
		}
		
		global $babBody;
		if(is_array($babBody->dgAdmGroups) && count($babBody->dgAdmGroups) > 0)
		{
			foreach($babBody->dgAdmGroups as $iKey => $iIdDelegation)
			{
				$aProcessedDelegation[$iIdDelegation] = $iIdDelegation;
			}
		}
		
		$oFmFolderSet = new BAB_FmFolderSet();
		$oRelativePath =& $oFmFolderSet->aField['sRelativePath'];
		$oIdDgOwner =& $oFmFolderSet->aField['iIdDgOwner'];
		
		$oCriteria = $oRelativePath->in('');
		if(count($aProcessedDelegation) > 0)
		{
			$oCriteria = $oCriteria->_and($oIdDgOwner->notIn($aProcessedDelegation));
		}
		
//		bab_debug($oFmFolderSet->getSelectQuery($oCriteria));
		$oFmFolderSet->select($oCriteria);
		
		while(null !== ($oFmFolder = $oFmFolderSet->next()))
		{
			if(!array_key_exists($oFmFolder->getDelegationOwnerId(), $aProcessedDelegation))
			{
				$bManager = bab_isAccessValid(BAB_FMMANAGERS_GROUPS_TBL, $oFmFolder->getId());
				$bUpdate = bab_isAccessValid(BAB_FMUPDATE_GROUPS_TBL, $oFmFolder->getId());
				$bDownload = bab_isAccessValid(BAB_FMDOWNLOAD_GROUPS_TBL, $oFmFolder->getId());
				$bUpload = bab_isAccessValid(BAB_FMUPLOAD_GROUPS_TBL, $oFmFolder->getId());
				
				/*
				bab_debug('sName ==> ' . $oFmFolder->getName() . ' iIdDgOwner ==> ' . $oFmFolder->getDelegationOwnerId() . 
				 (($bManager) ? ' Yes' : ' No') . (($bUpdate) ? ' Yes' : ' No') . (($bDownload) ? ' Yes' : ' No') . (($bUpload) ? ' Yes' : ' No'));
				//*/
				
				if($bManager || $bUpdate || $bDownload || $bUpload)
				{
					$aProcessedDelegation[$oFmFolder->getDelegationOwnerId()] = $oFmFolder->getDelegationOwnerId();
					if(0 === $oFmFolder->getDelegationOwnerId())
					{
						$bMainSite = true;
					}
				}
			}
		}
	
		global $babDB;
		
		$sQuery =
			'SELECT ' .
				'id iId, ' .
				'name sName ' .
			'FROM ' .
				BAB_DG_GROUPS_TBL . ' ' .
			'WHERE ' .
				'id IN(' . $babDB->quote($aProcessedDelegation) . ')';
			
//		bab_debug($sQuery);	
		$oResult = $babDB->db_query($sQuery);
		$iNumRows = $babDB->db_num_rows($oResult);
		$iIndex = 0;
	
		while($iIndex < $iNumRows && null !== ($aDatas = $babDB->db_fetch_assoc($oResult)))
		{
			$iIndex++;
			$aVisibleDelegation[$aDatas['iId']] =  $aDatas['sName'];
		}
		
		natcasesort($aVisibleDelegation);
		
		if($bMainSite)
		{
			$aVisibleDelegation = array(0 => bab_translate("All site")) + $aVisibleDelegation;
		}
	}
	return $aVisibleDelegation;
}


?>