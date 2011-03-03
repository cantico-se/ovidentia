<?php

//-------------------------------------------------------------------------
// OVIDENTIA http://www.ovidentia.org
// Ovidentia is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2, or (at your option)
// any later version.
//
// This program is distributed in the hope that it will be useful, but
// WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
// See the GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307,
// USA.
//-------------------------------------------------------------------------
/**
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 * @copyright Copyright (c) 2008 by CANTICO ({@link http://www.cantico.fr})
 */
require_once 'base.php';

/**
 * 
 * @param unknown_type $id
 * @param unknown_type $users
 * @param unknown_type $msg
 * @return unknown_type
 */
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
				if( mb_substr($arr['path'], -1) == "/" )
					{
					$arr['path'] = substr($arr['path'], 0, -1);
					}
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

					$iIdRootFolder = 0;
					$oRootFmFolder = null; 
					BAB_FmFolderHelper::getInfoFromCollectivePath($oFmFolder->getRelativePath() . $oFmFolder->getName(), $iIdRootFolder, $oRootFmFolder);
					if(null !== $oRootFmFolder)
					{
						$this->pathname = bab_toHtml($GLOBALS['babUrlScript'] . '?tg=fileman&idx=list&id=' . $iIdRootFolder . '&gr=Y&path=' . urlencode($arr['path']));
					}
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
	$mailBCT = 'mail'.$babBody->babsite['mail_fieldaddress'];

	if( count($users) > 0 )
	{
		$sql = "select email, firstname, lastname from ".BAB_USERS_TBL." where id IN (".$babDB->quote($users).")";
		$result=$babDB->db_query($sql);
		while( $arr = $babDB->db_fetch_array($result))
		{
			$mail->$mailBCT($arr['email'], bab_composeUserName($arr['firstname'],$arr['lastname']));
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










/**
 * Notify approvers or auto approve file
 * return true if file si confirmed by auto approval or if not approbation sheme
 * 
 * @see notifyFileApprovers()
 * 
 * 
 * @param int $id		folder id
 * @param int $fid		file id
 * @return bool
 */
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














/**
 * Notify file recipient for one file by email
 * when a file has been uploaded or a file has been updated
 * 
 * @param BAB_FolderFile 	$file		Filename
 * @param Array 			$users		users to notify
 * @param string			$msg		message displayed in email
 * @param bool				$bnew		true = new file uploaded | false = the file has been updated
 * 
 * @return bool
 */
function fileNotifyMembers(BAB_FolderFile $file, $users, $msg, $bnew = true)
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

			function fileNotifyMembersCls(BAB_FolderFile $file, $msg)
			{
				$path = $file->getPathName();
				
				
				if( mb_substr($path, -1) == "/" )
					{
					$path = substr($path, 0, -1);
					}
				
				$this->filename = $file->getName();
				$this->message = $msg;
				$this->from = bab_translate("Author");
				$this->path = bab_translate("Path");
				$this->file = bab_translate("File");
				$this->group = bab_translate("Folder");
				$this->pathname = $path == ""? "/": $path;
				$this->groupname = '';
				$oFmFolderSet = new BAB_FmFolderSet();
				$oId =& $oFmFolderSet->aField['iId'];
				$oFmFolder = $oFmFolderSet->get($oId->in($file->getOwnerId()));
				if(!is_null($oFmFolder))
				{
					$this->groupname = $oFmFolder->getName();

					$iIdRootFolder = 0;
					$oRootFmFolder = null; 
					BAB_FmFolderHelper::getInfoFromCollectivePath($oFmFolder->getRelativePath() . $oFmFolder->getName(), $iIdRootFolder, $oRootFmFolder);
					if(null !== $oRootFmFolder)
					{
						$this->pathname = bab_toHtml($GLOBALS['babUrlScript'] . '?tg=fileman&idx=list&id=' . $iIdRootFolder . '&gr=Y&path=' . urlencode($path));
					}
				}
				$this->site = bab_translate("Web site");
				$this->date = bab_translate("Date");
				$this->dateval = bab_strftime(mktime());
				
				$this->author = bab_getUserName($GLOBALS['BAB_SESS_USERID']);
				$this->authoremail = bab_getUserEmail($GLOBALS['BAB_SESS_USERID']);
				
				
			}
		}
	}

	$mail = bab_mail();
	if ($mail == false) {
		return;
	}
	$mailBCT = 'mail'.$babBody->babsite['mail_fieldaddress'];
	$clearBCT = 'clear'.$babBody->babsite['mail_fieldaddress'];

	$mail->mailFrom($babAdminEmail, $GLOBALS['babAdminName']);

	if ($bnew) {
		$mail->mailSubject(bab_translate("New file file uploaded")." ".$file->getPathName().$file->getName());
	} else {
		$mail->mailSubject(bab_translate("File has been updated")." ".$file->getPathName().$file->getName());
	}

	$tempa = new fileNotifyMembersCls($file, $msg);
	$message = $mail->mailTemplate(bab_printTemplate($tempa,"mailinfo.html", "fileuploaded"));
	$messagetxt = bab_printTemplate($tempa,"mailinfo.html", "fileuploadedtxt");

	$mail->mailBody($message, "html");
	$mail->mailAltBody($messagetxt);

	
	$arrusers = array();
	$count = 0;
	foreach($users as $id => $arr)
	{
		if( count($arrusers) == 0 || !isset($arrusers[$id]))
		{
			$arrusers[$id] = $id;
			$mail->$mailBCT($arr['email'], $arr['name']);
			$count++;
		}

		if( $count == $babBody->babsite['mail_maxperpacket'] )
		{
			$result = $mail->send();
			$mail->$clearBCT();
			$mail->clearTo();
			$count = 0;
			
			if (!$result)
			{
				return false;
			}
		}
	}

	if( $count > 0 )
	{
		$result = $mail->send();
		$mail->$clearBCT();
		$mail->clearTo();
		$count = 0;
	}
	
	if (!$result)
	{
		return false;
	}
	
	return true;
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
 * Send notification for file manager files
 * @param bab_eventFmFile $event
 * 
 */
function bab_onFmFile(bab_eventFmFile $event)
{
	$references = $event->getReferences();
	$users = $event->getUsersToNotify();
	
	if (empty($users) || empty($references)) {
		return;
	}
	
	$itsANewFile = true;
	
	if ($event instanceOf bab_eventFmAfterAddVersion) {
		$message = bab_translate("A new version file has been uploaded");
		$itsANewFile = false;
	} elseif ($event instanceOf bab_eventFmAfterFileUpdate) {
		$message = bab_translate("File has been updated");
		$itsANewFile = false;
	} elseif ($event instanceOf bab_eventFmAfterFileUpload) {
		$message = bab_translate("A new file has been uploaded");
	}
	
	
	foreach($references as $reference) {
		/* @var $reference bab_Reference */
		
		$oFolderFileSet = bab_getInstance('BAB_FolderFileSet');
		$oId			= $oFolderFileSet->aField['iId'];
		$folderFile		= $oFolderFileSet->get($oId->in($reference->getObjectId()));
		
		if ($folderFile) {
			if (!fileNotifyMembers($folderFile, $users, $message, $itsANewFile)) {
				return false;
			}
		}
	}
	
	
	
	foreach($users as $id_user => $arr)
	{
		$event->addInformedUser($id_user);
	}
	
	return true;
}



