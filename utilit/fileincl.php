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
require_once dirname(__FILE__) . '/criteria.class.php';
require_once dirname(__FILE__) . '/delegincl.php';
require_once dirname(__FILE__) . '/pathUtil.class.php';


define('BAB_FVERSION_FOLDER', 'OVF');

/* 0 -> other, 1 -> edit, 2 -> unedit, 3 -> commit, 4 -> initial upload */
define('BAB_FACTION_OTHER',				0);
define('BAB_FACTION_EDIT',				1);
define('BAB_FACTION_UNEDIT',			2);
define('BAB_FACTION_COMMIT',			3);
define('BAB_FACTION_INITIAL_UPLOAD',	4);

$babFileActions = array(bab_translate("Other"), bab_translate("Edit file"),
bab_translate("Unedit file"), bab_translate("Commit file"));


/**
 * For test purpose not finished
 * The purpose of this class is to handle 
 * all supported compressed file by php
 *
 * Futur Unpack functionality
 * 
 * NOT FINISHED
 * 
 * @author Z�bina Samuel
 */
class bab_CompressedFileHelper
{
	private $sFullPathName		= null;
	private $sRootPathName		= null;
	private $sMimeType			= null;
	private $bIsCompressedFile	= false; 
	private $aSuppotedFormat	= null;
	
	private $aError				= array(); 
	private $aPath				= array();
	
	public function __construct()
	{
		$this->aSuppotedFormat = array(
			'application/zip' => 1);
	}
	
	public function setUp(array $aFileInfo)
	{
		$this->reset();
		
		$oFileManagerEnv =& getEnvObject();
		
		$this->sFirstPath		= getFirstPath($aFileInfo['path']);
		$this->sRootPathName	= '';
		
		if($oFileManagerEnv->userIsInCollectiveFolder())
		{
			$this->sRootPathName = BAB_FileManagerEnv::getCollectivePath($aFileInfo['iIdDgOwner']) . $this->sFirstPath;
			$this->sFullPathName = BAB_FileManagerEnv::getCollectivePath($aFileInfo['iIdDgOwner']) . $aFileInfo['path'] . $aFileInfo['name'];
		}
		else if($oFileManagerEnv->userIsInPersonnalFolder())
		{
			$this->sRootPathName = $oFileManagerEnv->getRootFmPath() . $this->sFirstPath;
			$this->sFullPathName = $oFileManagerEnv->getRootFmPath() . $aFileInfo['path'] . $aFileInfo['name'];
		}
		else
		{
			//Error
		}
		
		$this->sRootPathName = BAB_PathUtil::addEndSlash(BAB_PathUtil::sanitize($oFileManagerEnv->getRootFmPath() . $this->sFirstPath));
		
		/*
		bab_debug(
			'sRootPathName ==> ' . $this->sRootPathName . "\n" .
			'sFullPathName ==> ' . $this->sFullPathName . "\n" .
			'sMimeType     ==> ' . bab_getFileMimeType($this->sFullPathName)
		);
		//*/
		
		if(is_file($this->sFullPathName))
		{
			$this->processFileMimeType();
			return $this->isCompressedFile();
		}
		
		return false;
	}
	
	public function isCompressedFile()
	{
		return $this->bIsCompressedFile;
	}
	
	public function canUnCompressFile()
	{
		$oIterator = $this->getIterator();
		if(isset($oIterator))
		{		
			$iZise = 0;
			$oIterator->setFullPathName($this->sFullPathName);
			foreach($oIterator as $oEntry)
			{
				//bab_debug(
				//	'sName              ==> ' . iconv("CP850", "ISO-8859-1", $oEntry->getName()) . "\n" .
				//	'iSize              ==> ' . $oEntry->getSize() . "\n" .
				//	'iCompressedSize    ==> ' . $oEntry->getCompressedSize() . "\n" .
				//	'sCompressionMethod ==> ' . $oEntry->getCompressionMethod()
				//);
				
				$this->fileNameReserved($oEntry);
				$this->fileNameSupportedByFileSystem($oEntry);
				$this->fileExists($oEntry);
				$iZise += $oEntry->getSize();
			}
			$this->uncompressedfileSizeExceedFmLimit($iZise);
			$this->uncompressedfileSizeExceedFolderLimit($iZise);
			
			return (0 == count($this->aError));
		}
		return false;
	}
	
	public function getError()
	{
		return $this->aError;
	}
	
	//Private Tools function
	
	private function reset()
	{
		$this->sFullPathName		= null;
		$this->sRootPathName		= null;
		$this->sMimeType			= null;
		$this->bIsCompressedFile	= false; 
		$this->aError				= array();
		$this->aPath				= array();
	}
	
	private function fileNameReserved($oEntry)
	{
		if(mb_strtolower(BAB_FVERSION_FOLDER) == mb_strtolower($oEntry->getBaseName()))
		{
			$aSearch		= array('%compressedFile%');
			$aReplace		= array(basename($this->sFullPathName));
			$sMessage		= str_replace($aSearch, $aReplace, bab_translate("The file %compressedFile% can not be unpacked because it contains an entry named OVF. OVF is a name reserved."));
			$this->aError[]	= $sMessage;
			return true;
		}
		return false;
	}
	
	private function fileNameSupportedByFileSystem($oEntry)
	{
		if(!isStringSupportedByFileSystem($oEntry->getBaseName()))
		{
			$aSearch		= array('%compressedFile%', '%compressedEntry%');
			$aReplace		= array(basename($this->sFullPathName), $oEntry->getBaseName());
			$sMessage		= str_replace($aSearch, $aReplace, bab_translate("The file %compressedFile% can not be unpacked because the file name %compressedEntry% contains characters supported by the file system."));
			$this->aError[]	= $sMessage;
			return true;
		}
		return false;
	}
	
	private function uncompressedfileSizeExceedFmLimit($iZise)
	{
		$oFileManagerEnv =& getEnvObject();
		if($iZise + $oFileManagerEnv->getFMTotalSize() > $GLOBALS['babMaxTotalSize'])
		{
			$aSearch		= array('%compressedFile%');
			$aReplace		= array(basename($this->sFullPathName));
			$sMessage		= str_replace($aSearch, $aReplace, bab_translate("The %compressedFile% can not be unpacked because the size of uncompressed files exceeds the limit set by the file manager."));
			$this->aError[]	= $sMessage;
			return true;
		}
		return false;
	}
	
	private function uncompressedfileSizeExceedFolderLimit($iZise)
	{
		$oFileManagerEnv =& getEnvObject();
		$sGr = $oFileManagerEnv->userIsInCollectiveFolder() ? 'Y' : 'N';
		$iTotalSize = getDirSize($this->sRootPathName);
		if($iZise + $iTotalSize > ($sGr == 'Y' ? $GLOBALS['babMaxGroupSize']: $GLOBALS['babMaxUserSize']))
		{
			$aSearch		= array('%compressedFile%');
			$aReplace		= array(basename($this->sFullPathName));
			$sMessage		= str_replace($aSearch, $aReplace, bab_translate("The %compressedFile% can not be unpacked because the size of uncompressed files exceeds the limit set for the folder."));
			$this->aError[]	= $sMessage;
			return true;
		}
		return false;
	}
	
	private function fileExists($oEntry)
	{
		$sFileName = $oEntry->getName();
		$iPos = mb_strpos($sFileName, '/');
		if(false !== $iPos)	
		{	
			$sFileName = mb_substr($sFileName, 0, $iPos);
		}
		
		if(!array_key_exists($sFileName, $this->aPath))
		{
			$this->aPath[$sFileName] = 0;
			
			if(file_exists($this->sRootPathName . $sFileName))
			{
				$aSearch		= array('%compressedFile%', '%compressedEntry%');
				$aReplace		= array(basename($this->sFullPathName), $oEntry->getBaseName());
				$sMessage		= str_replace($aSearch, $aReplace, bab_translate("The file %compressedFile% can not be unpacked because the file %compressedEntry% already exists."));
				$this->aError[]	= $sMessage;
				return true;
			}
		}
		return false;
	}
	
	private function processFileMimeType()
	{
		$this->sMimeType = bab_getFileMimeType($this->sFullPathName);

		if(array_key_exists($this->sMimeType, $this->aSuppotedFormat))
		{
			$this->bIsCompressedFile = true;
			return true;
		}
		
		$this->reset();
		return false;
	}
	
	private function getIterator()
	{
		require_once dirname(__FILE__) . '/iterator/archiveIterator.class.php';

		$oIterator = null;
		
		switch($this->sMimeType)
		{
			case 'application/zip':
				$oIterator = new bab_ZipIterator();
				return $oIterator;
				
			default:
				return $oIterator;
		}
	}
}





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
		if( ($l = mb_strlen($size)) > 3)
		{
			if( $l % 3 > 0 )
			{
				$txt = mb_substr( $size, 0, $l % 3);
			}
			else $txt = '';
			for( $i = 0; $i < ($l / 3); $i++)
			{
				$txt .= " ". mb_substr($size, $l%3 + $i*3, 3);
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

					$iIdRootFolder = 0;
					$oRootFmFolder = null; 
					BAB_FmFolderHelper::getInfoFromCollectivePath($oFmFolder->getRelativePath() . $oFmFolder->getName(), $iIdRootFolder, $oRootFmFolder);
					if(null !== $oRootFmFolder)
					{
						$this->pathname = bab_toHtml($GLOBALS['babUrlScript'] . '?tg=fileman&idx=list&id=' . $iIdRootFolder . '&gr=Y&path=' . urlencode($oFmFolder->getRelativePath() . $oFmFolder->getName()));
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

			function fileNotifyMembersCls($file, $path, $idgrp, $msg)
			{
				$this->filename = $file;
				$this->message = $msg;
				$this->from = bab_translate("Author");
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

					$iIdRootFolder = 0;
					$oRootFmFolder = null; 
					BAB_FmFolderHelper::getInfoFromCollectivePath($oFmFolder->getRelativePath() . $oFmFolder->getName(), $iIdRootFolder, $oRootFmFolder);
					if(null !== $oRootFmFolder)
					{
						$this->pathname = bab_toHtml($GLOBALS['babUrlScript'] . '?tg=fileman&idx=list&id=' . $iIdRootFolder . '&gr=Y&path=' . urlencode($oFmFolder->getRelativePath() . $oFmFolder->getName()));
					}
				}
				$this->site = bab_translate("Web site");
				$this->date = bab_translate("Date");
				$this->dateval = bab_strftime(mktime());
				
				$oFolderFileSet				= new BAB_FolderFileSet();
				$oFolderFileSet->bUseAlias	= false;
				$oIdOwner					= $oFolderFileSet->aField['iIdOwner'];
				$oGroup						= $oFolderFileSet->aField['sGroup'];
				$oPathName					= $oFolderFileSet->aField['sPathName'];
				$oIdDgOwner					= $oFolderFileSet->aField['iIdDgOwner'];

				$oCriteria = $oIdOwner->in($idgrp);
				$oCriteria = $oCriteria->_and($oGroup->in('Y'));
				$oCriteria = $oCriteria->_and($oPathName->in($oFmFolder->getRelativePath() . $oFmFolder->getName() . '/'));
				$oCriteria = $oCriteria->_and($oIdDgOwner->in(bab_getCurrentUserDelegation()));
			
				$oFolderFile = $oFolderFileSet->get($oCriteria);
				$oFolderFileSet->bUseAlias = true;
				
				if(null !== $oFolderFile)
				{
					$oFolderFileVersionSet	= new BAB_FolderFileVersionSet();
					$oIdFile				= $oFolderFileVersionSet->aField['iIdFile'];
					
					$oFolderFileVersionSet->select($oIdFile->in($oFolderFile->getId()), array('iVerMajor' => 'DESC', 'iVerMinor' => 'DESC'));
					if(null !== ($oFolderFileVersion = $oFolderFileVersionSet->get()))
					{
						$this->author = bab_getUserName($oFolderFileVersion->getAuthorId());
						$this->authoremail = bab_getUserEmail($oFolderFileVersion->getAuthorId());
					}
					else
					{
						$this->author = bab_translate("Unknown user");
						$this->authoremail = "";
					}
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
	$clearBCT = 'clear'.$babBody->babsite['mail_fieldaddress'];

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
			$mail->$mailBCT($arr['email'], $arr['name']);
			$count++;
		}

		if( $count == $babBody->babsite['mail_maxperpacket'] )
		{
			$mail->send();
			$mail->$clearBCT();
			$mail->clearTo();
			$count = 0;
		}
	}

	if( $count > 0 )
	{
		$mail->send();
		$mail->$clearBCT();
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
	require_once dirname(__FILE__) . '/tagApi.php';
	
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
		$aTags = array();
		
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
			$errfiles[] = array('error' => bab_translate("The file was larger than the maximum allowed size") ." : ". $GLOBALS['babMaxFileSize'], 'file' => $file['name']);
			continue;
		}

		if($file['size'] + $oFileManagerEnv->getFMTotalSize() > $GLOBALS['babMaxTotalSize'])
		{
			$errfiles[] = array('error' => bab_translate("The file size exceed the limit configured for the file manager"), 'file'=>$file['name']);
			continue;
		}

		$totalsize = getDirSize($pathx);
		if($file['size'] + $totalsize > ($gr == "Y"? $GLOBALS['babMaxGroupSize']: $GLOBALS['babMaxUserSize']))
		{
			$errfiles[] = array('error' => bab_translate("The file size exceed the limit configured for the current type of folder"), 'file'=>$file['name']);
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
		$osfname = replaceInvalidFolderNameChar($file['name']);
		
		if(!isStringSupportedByFileSystem($osfname))
		{
			$babBody->addError(str_replace('%file%', $osfname, bab_translate("The file %file% contains characters not supported by the file system")));
			continue;
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

		bab_setTimeLimit(0);

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

		if(!empty($tags))
		{
			$atags		= explode(',', $tags);
			$message	= '';
			for($k = 0; $k < count($atags); $k++)
			{
				$sTagName = trim($atags[$k]);
				if(!empty($sTagName))
				{
					$oTagMgr	= bab_getInstance('bab_TagMgr');
					$oTag		= $oTagMgr->getByName($sTagName);
					if($oTag instanceof bab_Tag)
					{
						$aTags[] = $oTag;
					}
					else
					{
						if($baddtags == 'Y')
						{
							$oTag = $oTagMgr->create($sTagName);
							if($oTag instanceof bab_Tag)
							{
								$aTags[] = $oTag;
							}
						}
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
			
			$oReferenceMgr	= bab_getInstance('bab_ReferenceMgr');
			$oReference		= bab_Reference::makeReference('ovidentia', '', 'files', 'file', $idf);
			$oReferenceMgr->removeByReference($oReference);
		}
		else
		{
			$req = "insert into ".BAB_FILES_TBL."
			(name, description, path, id_owner, bgroup, link, readonly, state, created, author, modified, modifiedby, confirmed, index_status, iIdDgOwner) values ";
			$req .= "('" .$babDB->db_escape_string($name). "', '" . $babDB->db_escape_string($description[$count]). "', '".$babDB->db_escape_string($sRelativePath). "', '" . $babDB->db_escape_string($iIdOwner). "', '" . $babDB->db_escape_string($gr). "', '0', '" . $babDB->db_escape_string($readonly[$count]). "', '', now(), '" . $babDB->db_escape_string($idcreator). "', now(), '" . $babDB->db_escape_string($idcreator). "', '". $babDB->db_escape_string($confirmed)."', '".$babDB->db_escape_string($index_status)."', '".$babDB->db_escape_string(bab_getCurrentUserDelegation())."')";
			$babDB->db_query($req);
			$idf = $babDB->db_insert_id();
			
			$oFolderFileLog = new BAB_FolderFileLog();
			$oFolderFileLog->setIdFile($idf);
			$oFolderFileLog->setCreationDate(date("Y-m-d H:i:s"));
			$oFolderFileLog->setAuthorId($GLOBALS['BAB_SESS_USERID']);
			$oFolderFileLog->setAction(BAB_FACTION_INITIAL_UPLOAD);
			$oFolderFileLog->setComment(bab_translate("Initial upload"));
			$oFolderFileLog->setVersion('1.0');
			$oFolderFileLog->save();
		}

		if(count($aTags))
		{
			$oReferenceMgr = bab_getInstance('bab_ReferenceMgr');
			
			foreach($aTags as $k => $oTag)
			{
				$oReferenceMgr->add($oTag->getName(), bab_Reference::makeReference('ovidentia', '', 'files', 'file', $idf));
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
	require_once dirname(__FILE__) . '/tagApi.php';
	
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

		$aTags = array();
		$tags = trim($keywords);
		if(!empty($tags))
		{
			$_atags		= explode(',', $tags);
			$message	= '';
			for($k = 0; $k < count($_atags); $k++)
			{
				$sTagName = trim($_atags[$k]);
				if(!empty($sTagName))
				{
					$oTagMgr	= bab_getInstance('bab_TagMgr');
					$oTag		= $oTagMgr->getByName($sTagName);
					if($oTag instanceof bab_Tag)
					{
						$aTags[] = $oTag;
					}
					else
					{
						if($rr['baddtags'] == 'Y')
						{
							$oTag = $oTagMgr->create($sTagName);
							if($oTag instanceof bab_Tag)
							{
								$aTags[] = $oTag;
							}
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

		$fname = trim($fname);
		$osfname = $fname;
		
		if(!empty($fname) && strcmp($oFolderFile->getName(), $osfname))
		{
			$osfname = $fname = replaceInvalidFolderNameChar($fname);
			if(!isStringSupportedByFileSystem($fname))
			{
				$babBody->addError(str_replace('%file%', $osfname, bab_translate("The file %file% contains characters not supported by the file system")));
				return false;
			}
			
			if(file_exists($sUploadPath . $oFolderFile->getPathName() . $osfname))
			{
				$babBody->msgerror = bab_translate("File already exist") . ':' . bab_toHtml($osfname);
				return false;
			}
		}		

		$bmodified = false;
		if(!empty($uploadf_name) && $uploadf_name != "none")
		{
			if($uploadf_size > $GLOBALS['babMaxFileSize'])
			{
				$babBody->msgerror = bab_translate("The file was larger than the maximum allowed size") ." :". $GLOBALS['babMaxFileSize'];
				return false;
			}
			
			if($uploadf_size + $oFileManagerEnv->getFMTotalSize() > $GLOBALS['babMaxTotalSize'])
			{
				$babBody->msgerror = bab_translate("The file size exceed the limit configured for the file manager");
				return false;
			}

			$totalsize = getDirSize($sUploadPath . $oFolderFile->getPathName());
			if($uploadf_size + $totalsize > ('Y' === $oFolderFile->getGroup() ? $GLOBALS['babMaxGroupSize']: $GLOBALS['babMaxUserSize']))
			{
				$babBody->msgerror = bab_translate("The file size exceed the limit configured for the current type of folder");
				return false;
			}
			
			$uploadf_name = replaceInvalidFolderNameChar($uploadf_name);
			if(!isStringSupportedByFileSystem($uploadf_name))
			{
				$babBody->addError(str_replace('%file%', $uploadf_name, bab_translate("The file %file% contains characters not supported by the file system")));
				return false;
			}

			if(!$fmFile->import($sFullPathName))
			{
				$babBody->msgerror = bab_translate("The file could not be uploaded");
				return false;
			}
			$bmodified = true;
		}

		$frename = false;
		if(!empty($fname) && strcmp($oFolderFile->getName(), $osfname))
		{
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
						
						if(file_exists($sSrc))
						{
							rename($sSrc, $sTrg);
						}
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
			
			$oReferenceMgr	= bab_getInstance('bab_ReferenceMgr');
			$oReference		= bab_Reference::makeReference('ovidentia', '', 'files', 'file', $idf);
			
			$oReferenceMgr->removeByReference($oReference);
			if(count($aTags))
			{
				foreach($aTags as $k => $oTag)
				{
					$oReferenceMgr->add($oTag->getName(), $oReference);
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
		
		
		$sGr = (string) bab_rp('gr', '');
		$iId = (int) bab_rp('id', 0);
		
		if('Y' === $sGr)
		{
			$res = $babDB->db_query("select id from ".BAB_FM_FIELDS_TBL." where id_folder='".$babDB->db_escape_string($iId)."'");
			while(false !== ($arrf = $babDB->db_fetch_array($res)))
			{
				$fd = 'field'.$arrf['id'];
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
			$babBody->msgerror = bab_translate("The file was larger than the maximum allowed size") ." :". $GLOBALS['babMaxFileSize'];
			return false;
		}
		
		$oFileManagerEnv =& getEnvObject();

		if($size + $oFileManagerEnv->getFMTotalSize() > $GLOBALS['babMaxTotalSize'])
		{
			$babBody->msgerror = bab_translate("The file size exceed the limit configured for the file manager");
			return false;
		}

		$sUploadPath		= BAB_FileManagerEnv::getCollectivePath($oFmFolder->getDelegationOwnerId());
		$sFullPathName		= $sUploadPath . $oFolderFile->getPathName();

		$totalsize = getDirSize($sFullPathName);
		if($size + $totalsize > $GLOBALS['babMaxGroupSize'] )
		{
			$babBody->msgerror = bab_translate("The file size exceed the limit configured for the current type of folder");
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
			if(mb_strlen(trim($sWhereClause)) > 0)
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
		if(mb_strlen($sWhereClause) > 0)
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
		$sClass = mb_substr(get_class($this), 0, -3);
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
			'sAutoApprobation' => new BAB_StringField('`auto_approbation`'),
			'sDownloadsCapping' => new BAB_StringField('`bcap_downloads`'),
			'iMaxDownloads' => new BAB_IntField('`max_downloads`'),
			'sDownloadHistory' => new BAB_StringField('`bdownload_history`')
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
				if(mb_strlen(trim($oFmFolder->getRelativePath())) > 0)
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
				$sFullPathName = $sRootFmPath . $oFmFolder->getRelativePath() . $oFmFolder->getName();
				$this->removeDir($sFullPathName);

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
		return BAB_FmFolderSet::getFirstCollectiveFolder(removeLastPath($sRelativePath));
	}

	function getFirstCollectiveFolder($sRelativePath)
	{
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

	/**
	 * Recusively deletes a folder.
	 *
	 * @param string $sFullPathName
	 * @static
	 */
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
							BAB_FmFolderSet::removeDir($sFullPathName . '/' . $sName);
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
	
	function removeSimpleCollectiveFolder($sRelativePath)
	{
		require_once $GLOBALS['babInstallPath'].'utilit/pathUtil.class.php';
			
		//1 Chercher tous les repertoires collectifs
		//2 Supprimer les repertoires collectifs
		//3 Lister le contenu du repertoire a supprimer
		//4 Pour chaque repertoire rappeler la fonction deleteSimpleCollectiveFolder
		//5 Supprimer le repertoire
		
		global $babBody, $babDB;
	
		$oFileManagerEnv	=& getEnvObject();
		$sUplaodPath		= BAB_PathUtil::addEndSlash(BAB_PathUtil::sanitize($oFileManagerEnv->getRootFmPath()));
	
		$bDbRecordOnly	= false;
		$oFmFolderSet	= new BAB_FmFolderSet();
		$oRelativePath	=& $oFmFolderSet->aField['sRelativePath'];
		$oIdDgOwner		=& $oFmFolderSet->aField['iIdDgOwner'];
		$oName			=& $oFmFolderSet->aField['sName'];
		
		$sRelativePath = BAB_PathUtil::addEndSlash(BAB_PathUtil::sanitize($sRelativePath));
		
		$oCriteria = $oIdDgOwner->in(bab_getCurrentUserDelegation());
		$oCriteria = $oCriteria->_and($oRelativePath->like($babDB->db_escape_like($sRelativePath)));
	
		$oFmFolderSet->select($oCriteria);
		if($oFmFolderSet->count() > 0)
		{
			while( null !== ($oFolder = $oFmFolderSet->next()) )
			{
				require_once $GLOBALS['babInstallPath'] . 'utilit/delincl.php';
				bab_deleteFolder($oFolder->getId());
			}
		}
	
		$sFullPathName = $sUplaodPath . $sRelativePath;
	
		if(is_dir($sFullPathName))
		{
			$oFolderFileSet = new BAB_FolderFileSet();
			$oName =& $oFolderFileSet->aField['sName'];
			$oPathName =& $oFolderFileSet->aField['sPathName'];
			$oIdDgOwnerFile =& $oFolderFileSet->aField['iIdDgOwner'];
	
			$oDir = dir($sFullPathName);
			while(false !== ($sEntry = $oDir->read())) 
			{
				if($sEntry == '.' || $sEntry == '..')
				{
					continue;
				}
				else
				{
					$sFullPathName = $sUplaodPath . $sRelativePath . $sEntry;
	
					if(is_dir($sFullPathName)) 
					{
						$this->removeSimpleCollectiveFolder($sRelativePath . $sEntry . '/');	
					}
					else if(is_file($sFullPathName))
					{
						$oCriteria = $oName->in($sEntry);
						$oCriteria = $oCriteria->_and($oPathName->in($sRelativePath));
						$oCriteria = $oCriteria->_and($oIdDgOwnerFile->in(bab_getCurrentUserDelegation()));
	
						$oFolderFileSet->remove($oCriteria);
					}
				}
			}
			$oDir->close();
			rmdir($sUplaodPath . $sRelativePath);
			
			$sName			= getLastPath($sRelativePath);
			$sRelativePath	= removeLastPath($sRelativePath); 
			if('' != $sRelativePath)
			{
				$sRelativePath = BAB_PathUtil::addEndSlash($sRelativePath); 
			}
				
			$oFmFolderCliboardSet = bab_getInstance('BAB_FmFolderCliboardSet');
			$oFmFolderCliboardSet->deleteFolder($sName, $sRelativePath, 'Y');
		}
	}
		
	function rename($sUploadPath, $sRelativePath, $sOldName, $sNewName)
	{
		if(BAB_FmFolderHelper::renameDirectory($sUploadPath, $sRelativePath, $sOldName, $sNewName))
		{
			global $babBody, $babBody;
			
			$sOldRelativePath = $sRelativePath . $sOldName . '/';
			$sNewRelativePath = $sRelativePath . $sNewName . '/';

			global $babDB;
			$oFmFolderSet = new BAB_FmFolderSet();
			$oRelativePath =& $oFmFolderSet->aField['sRelativePath'];
			$oIdDgOwner =& $oFmFolderSet->aField['iIdDgOwner'];
			
			$oCriteria = $oIdDgOwner->in(bab_getCurrentUserDelegation());
			$oCriteria = $oRelativePath->like($babDB->db_escape_like($sOldRelativePath) . '%');
			
			$oFmFolderSet = $oFmFolderSet->select($oCriteria);
			while(null !== ($oFmFolder = $oFmFolderSet->next()))
			{
				$sRelPath = $sNewRelativePath . mb_substr($oFmFolder->getRelativePath(), mb_strlen($sOldRelativePath));
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
			
			$oIdDgOwner		=& $oFmFolderSet->aField['iIdDgOwner'];
			$oName			=& $oFmFolderSet->aField['sName'];
			$oRelativePath	=& $oFmFolderSet->aField['sRelativePath'];

			//1 changer le repertoire
			$sName = getLastPath($sOldRelativePath);
			$sRelativePath = removeLastPath($sOldRelativePath);
			$sRelativePath .= (mb_strlen(trim($sRelativePath)) !== 0 ) ? '/' : '';
			
			$oCriteria = $oIdDgOwner->in(bab_getCurrentUserDelegation());
			$oCriteria = $oCriteria->_and($oName->in($sName));
			$oCriteria = $oCriteria->_and($oRelativePath->in($sRelativePath));
			
			$oFmFolder = $oFmFolderSet->get($oCriteria);
			if(!is_null($oFmFolder))
			{
				$sNewRelPath = removeLastPath($sNewRelativePath);
				$sNewRelPath .= (mb_strlen(trim($sNewRelPath)) !== 0 ) ? '/' : '';
				$oFmFolder->setRelativePath($sNewRelPath);
				$oFmFolder->save();
			}
			
			//2 changer les sous repertoires
			$oCriteria = $oRelativePath->like($babDB->db_escape_like($sOldRelativePath) . '%');
			$oCriteria = $oCriteria->_and($oIdDgOwner->in($babBody->currentAdmGroup));
			
			$oFmFolderSet->select($oCriteria);
			while(null !== ($oFmFolder = $oFmFolderSet->next()))
			{
				$sNewRelativePath = $sNewRelativePath . mb_substr($oFmFolder->getRelativePath(), mb_strlen($sOldRelativePath));
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
		
		$oFmFolderCliboardSet->select($oCriteria);

		$sRelPath = $sRelativePath . $sOldName . '/';
		$iLength = mb_strlen(trim($sRelPath));
		
		while(null !== ($oFmFolderCliboard = $oFmFolderCliboardSet->next()))
		{
			$sBegin = mb_substr($oFmFolderCliboard->getRelativePath(), 0, $iLength);
			$sEnd = (string) mb_substr($oFmFolderCliboard->getRelativePath(), mb_strlen($sBegin), mb_strlen($oFmFolderCliboard->getRelativePath()));

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

				$oFmFolderCliboard->setRelativePath($sNewRelativePath);
				$oFmFolderCliboard->save();
			}
		}
	}
	
	function setOwnerId($sPathName, $iOldIdOwner, $iNewIdOwner)
	{
		global $babBody, $babDB;
		$oRelativePath =& $this->aField['sRelativePath'];
		$oIdOwner =& $this->aField['iIdOwner'];

		$oCriteria = $oRelativePath->like($babDB->db_escape_like($sPathName) . '%');
		$oCriteria = $oCriteria->_and($oIdOwner->in($iOldIdOwner));
		$this->select($oCriteria);

		while(null !== ($oFmFolderCliboard = $this->next()))
		{
			$oFmFolderCliboard->setOwnerId($iNewIdOwner);
			$oFmFolderCliboard->save();
		}
	}
	
	function deleteEntry($sName, $sRelativePath, $sGroup)
	{
		$oIdDgOwner		=& $this->aField['iIdDgOwner'];
		$oGroup 		=& $this->aField['sGroup'];
		$oName 			=& $this->aField['sName'];
		$oRelativePath	=& $this->aField['sRelativePath'];
		
		$iDelegation = ('Y' === $sGroup) ? bab_getCurrentUserDelegation() : 0;
		
		global $babBody;
		$oCriteria = $oIdDgOwner->in($iDelegation);
		$oCriteria = $oCriteria->_and($oGroup->in($sGroup));
		$oCriteria = $oCriteria->_and($oName->in($sName));
		$oCriteria = $oCriteria->_and($oRelativePath->in($sRelativePath));
		
		$this->remove($oCriteria);
	}
	
	function deleteFolder($sName, $sRelativePath, $sGroup)
	{
		$oIdDgOwner =& $this->aField['iIdDgOwner'];
		$oIdOwner =& $this->aField['iIdOwner'];
		$oName =& $this->aField['sName'];
		$oRelativePath =& $this->aField['sRelativePath'];
		$oGroup =& $this->aField['sGroup'];
		
		$iDelegation = ('Y' === $sGroup) ? bab_getCurrentUserDelegation() : 0;
		
		global $babBody, $babDB;
		$oCriteria = $oIdDgOwner->in($iDelegation);
		$oCriteria = $oCriteria->_and($oRelativePath->like($babDB->db_escape_like($sRelativePath . $sName . '/') . '%'));
		$oCriteria = $oCriteria->_and($oGroup->in('Y'));
		$this->remove($oCriteria);
		
		$oCriteria = $oCriteria->_and($oName->in($sName));
		$oCriteria = $oIdDgOwner->in($iDelegation);
		$oCriteria = $oCriteria->_and($oRelativePath->like($babDB->db_escape_like($sRelativePath)));
		$oCriteria = $oCriteria->_and($oGroup->in('Y'));
		$this->remove($oCriteria);
	}
	
	function move($sOldRelativePath, $sNewRelativePath, $sGr)
	{
		$oIdDgOwner		=& $this->aField['iIdDgOwner'];
		$oIdOwner		=& $this->aField['iIdOwner'];
		$oGroup 		=& $this->aField['sGroup'];
		$oName 			=& $this->aField['sName'];
		$oRelativePath	=& $this->aField['sRelativePath'];
		
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
		
		$aProcessedPath = array();
		$iIdRootFolder = 0;
		$this->select($oCriteria);
		while(null !== ($oFmFolderCliboard = $this->next()))
		{
			$sOldRelPath = $oFmFolderCliboard->getRelativePath();
			$sNewRelPath = $sNewRelativePath . mb_substr($sOldRelPath, mb_strlen($sOldRelativePath));
			
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
			'iMaxDownloads' => new BAB_IntField('`max_downloads`'),
			'iDownloads' => new BAB_IntField('`downloads`'),
			'iIdFlowApprobationInstance' => new BAB_IntField('`idfai`'),
			'iIdFolderFileVersion' => new BAB_IntField('`edit`'),
			'iVerMajor' => new BAB_IntField('`ver_major`'),
			'iVerMinor' => new BAB_IntField('`ver_minor`'),
			'sVerComment' => new BAB_StringField('`ver_comment`'),
			'iIndexStatus' => new BAB_IntField('`index_status`'),
			'iIdDgOwner' => new BAB_IntField('`iIdDgOwner`')
		);
	}

	/**
	 * Loads a file using its id.
	 * 
	 * @param int $iFileId
	 * @return BAB_FolderFile
	 */
	function getById($iFileId)
	{
		$oFolderFileSet = new BAB_FolderFileSet();
		$oId = $oFolderFileSet->aField['iId'];
		$oCriteria = $oId->in($iFileId);
		$file = $oFolderFileSet->get($oCriteria);

		return $file;
	}

	function remove($oCriteria)
	{
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
		$oFileManagerEnv = getEnvObject();
		$sUploadPath = $oFileManagerEnv->getCollectiveRootFmPath();
		
		$this->select($oCriteria);
			
		$oFolderFileLogSet = new BAB_FolderFileLogSet();
		$oIdVersion =& $oFolderFileLogSet->aField['iIdFile'];

		$oFolderFileVersionSet = new BAB_FolderFileVersionSet();
		$oId =& $oFolderFileVersionSet->aField['iIdFile'];
		
		while(null !== ($oFolderFile = $this->next()))
		{
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
			$oFolderFile->setOwnerId($iNewIdOwner);
			$oFolderFile->save();
		}
	}

	function renameFolder($sRelativePath, $sNewName, $sGr)
	{
		$iOffset = 2; //pour le slash a la fin
		
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
		
		$oFolderFileSet->select($oCriteria);

		while(null !== ($oFolderFile = $oFolderFileSet->next()))
		{
			$sBegin = mb_substr($oFolderFile->getPathName(), 0, mb_strlen($sRelativePath));
			$sEnd = (string) mb_substr($oFolderFile->getPathName(), mb_strlen($sRelativePath), mb_strlen($oFolderFile->getPathName()));

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

		$aProcessedPath = array();
		$oFolderFileSet->select($oCriteria);
		while(null !== ($oFolderFile = $oFolderFileSet->next()))
		{
			$sOldPathName = $oFolderFile->getPathName();
			$sNewPathName = $sNewRelativePath . mb_substr($sOldPathName, mb_strlen($sOldRelativePath));
			
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
		$this->select($oCriteria);
		while(null !== ($oFolderFileVersion = $this->next()))
		{
			$sFullPathName = $sPathName . BAB_FVERSION_FOLDER . '/' . $oFolderFileVersion->getMajorVer() .
			',' . $oFolderFileVersion->getMinorVer() . ',' . $sFileName;

			if(file_exists($sFullPathName))
			{
				unlink($sFullPathName);
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


/**
 * Base class for Folders and Files (BAB_FmFolder and BAB_FolderFile).
 */
class BAB_FmFolderFile extends BAB_DbRecord
{
	
}

class BAB_FmFolder extends BAB_FmFolderFile
{
	function BAB_FmFolder()
	{
		parent::BAB_FmFolderFile();
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
		BAB_FmFolderHelper::sanitizePathname($sPathname);
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



	/**
	 * Activates or deactivates the download capping for this folder.
	 * If capping is activated, the maximum number of downloads is set through setMaxDownloads
	 * 
	 * @see setMaxDownloads
	 * @param string	$sMaximumDownloads	'Y' to activate download capping for this folder, 'N' otherwise.
	 */
	function setDownloadsCapping($sDownloadsCapping)
	{
		$this->_set('sDownloadsCapping', $sDownloadsCapping);
	}

	/**
	 * Returns the download capping status for this folder.
	 * 
	 * @return string 	'Y' if download capping is activated for this folder, 'N' otherwise.
	 */
	function getDownloadsCapping()
	{
		return $this->_sGet('sDownloadsCapping');
	}

	/**
	 * Setss the default maximum number of downloads for this folder.
	 * 
	 * @param int	$iMaxDownloads	The default maximum number of downloads for this folder. 
	 */
	function setMaxDownloads($iMaxDownloads)
	{
		$this->_set('iMaxDownloads', $iMaxDownloads);
	}

	/**
	 * Returns the default maximum number of downloads for this folder.
	 * 
	 * @return int		The default maximum number of downloads for this folder. 
	 */
	function getMaxDownloads()
	{
		return $this->_sGet('iMaxDownloads');
	}

	/**
	 * Activates or deactivates the download history for this folder.
	 * 
	 * @param string	$sDownloadHistory	'Y' to activate download history for this folder, 'N' otherwise.
	 */
	function setDownloadHistory($sDownloadHistory)
	{
		$this->_set('sDownloadHistory', $sDownloadHistory);
	}

	/**
	 * Returns the download history activation status for this folder.
	 * 
	 * @return string	'Y' if download history is activated for this folder, 'N' otherwise.
	 */
	function getDownloadHistory()
	{
		return $this->_sGet('sDownloadHistory');
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
		BAB_FmFolderHelper::sanitizePathname($sPathname);
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
class BAB_FolderFile extends BAB_FmFolderFile
{
//	function BAB_FolderFile()
//	{
//		parent::BAB_FmFolderFile();
//	}

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
		BAB_FmFolderHelper::sanitizePathname($sPathname);
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
	 * Set the number of downloads for the file
	 *
	 * @param int $iDownloads The number of downloads
	 */
	function setDownloads($iDownloads)
	{
		$this->_set('iDownloads', $iDownloads);
	}

	/**
	 * Get the number of downloads of the file
	 *
	 * @return int The number of downloads of the file
	 */
	function getDownloads()
	{
		return $this->_iGet('iDownloads');
	}

	/**
	 * Set the maximum number of downloads for the file
	 *
	 * @param int $iMaxDownloads The maximum number of downloads
	 */
	function setMaxDownloads($iMaxDownloads)
	{
		$this->_set('iMaxDownloads', $iMaxDownloads);
	}

	/**
	 * Get the maximum number of downloads of the file
	 *
	 * @return int The maximum number of downloads of the file
	 */
	function getMaxDownloads()
	{
		return $this->_iGet('iMaxDownloads');
	}

	
	/**
	 * Checks if the file maximum download number has been reached.
	 * 
	 * @return bool
	 */
	function downloadLimitReached()
	{
		if ($this->getMaxDownloads() == 0) {
			return false;
		}

		$filePathname = $this->getPathName();
		$firstCollectiveFolder = BAB_FmFolderSet::getFirstCollectiveFolder($filePathname);

		// Checks that downloads capping is active on the file's owner folder.
		if ($firstCollectiveFolder->getDownloadsCapping() == 'Y'
				&& $this->getMaxDownloads() <= $this->getDownloads()) {
			return true;
		}

		return false;		
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
	 * Set the identifier of the file version.
	 * 
	 * @see BAB_FolderFile::getFolderFileVersionId
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
	 * If the file is locked (by the user) the returned value
	 * is the id of the file version table record (bab_fm_filesver).
	 * 
	 * If the file is not locked getFolderFileVersionId returns 0
	 *
	 * @return int		The identifier of the file version record or 0. 
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

	/**
	 * Get root folder
	 * @param	string	$sRelativePathName 		relative path without delegation folder
	 * @param	int		$iIdDelegation
	 *
	 * @return BAB_FmFolder
	 */
	public static function getRootFolder($sRelativePathName, $iIdDelegation)
	{
		$sRootFldName		= getFirstPath($sRelativePathName);
		$oFolderSet			= bab_getInstance('BAB_FmFolderSet');
		$oNameField			= $oFolderSet->aField['sName'];
		$oRelativePathField	= $oFolderSet->aField['sRelativePath'];
		$oIdDgOwnerField	= $oFolderSet->aField['iIdDgOwner'];

		$oCriteria = $oNameField->in($sRootFldName);
		$oCriteria = $oCriteria->_and($oRelativePathField->in(''));
		$oCriteria = $oCriteria->_and($oIdDgOwnerField->in($iIdDelegation));
		
		return $oFolderSet->get($oCriteria);
	}


	/**
	 * Get download url
	 * @return string
	 */
	public function getDownloadUrl() 
	{
		$oFolder = self::getRootFolder($this->getPathName(), $this->getDelegationOwnerId());

		if(!($oFolder instanceof BAB_FmFolder))
		{
			return null;
		}

		return $GLOBALS['babUrlScript'] . '?tg=fileman&id=' . $oFolder->getId() . '&gr=' . $this->getGroup() . '&path=' . urlencode(removeEndSlashes($this->getPathName())).'&sAction=getFile&idf='.$this->getId();
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
//et deux classe d�riv�es. Une pour les repertoire simple et une
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
				$sRelativePath = $sPath . ((mb_substr($sPath, - 1) !== '/') ? '/' : '');

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
				$oFmFolder = BAB_FmFolderSet::getFirstCollectiveFolder($sRelativePath);
			}
			else 
			{
				$oFmFolder = BAB_FmFolderSet::getFirstCollectiveParentFolder($sRelativePath);
			}
				
			if(!is_null($oFmFolder))
			{
				$bSuccess = true;
			}
		}
		return $bSuccess;
	}


	function getUploadPath()
	{
		$sUploadPath = $GLOBALS['babUploadPath'];
		$iLength = mb_strlen(trim($sUploadPath));
		if($iLength > 0)
		{
			return BAB_PathUtil::addEndSlash(BAB_PathUtil::sanitize($sUploadPath));
		}
		return $sUploadPath;
	}

	function createDirectory($sFullPathName)
	{
		global $babBody;
		$bSuccess = true;
		
		if(mb_strlen(trim($sFullPathName)) > 0 && false === mb_strpos($sFullPathName, '..'))
		{
			if(!is_dir($sFullPathName))
			{
				$sUploadPath = BAB_FmFolderHelper::getUploadPath();
				$sRelativePath = mb_substr($sFullPathName, mb_strlen($sUploadPath));
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
				if(mb_strlen(trim($sPathItem)) !== 0)
				{
					$sPathItem = replaceInvalidFolderNameChar($sPathItem);
					
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
	
	/**
	 * The $sPathName must be canonized before calling this function
	 */
	function sanitizePathname(&$sPathname)
	{
		$sPathname	= removeEndSlashes($sPathname);
		$aPaths		= explode('/', $sPathname);
		
		if(is_array($aPaths) && count($aPaths) > 0)
		{
			foreach($aPaths as $iKey => $sPathItem)
			{
				if(mb_strlen(trim($sPathItem)) !== 0)
				{
					$aPaths[$iKey] = replaceInvalidFolderNameChar($sPathItem);
				}
			}
			
			$sPathname = implode('/', $aPaths);
			
			return addEndSlash($sPathname);
		}
		
		return $sPathname;
	}
	
	function renameDirectory($sUploadPath, $sRelativePath, $sOldName, $sNewName)
	{
		global $babBody;
		$bSuccess = true;

		$bOldNameValid = (mb_strlen(trim($sOldName)) > 0);
		$bNewNameValid = (mb_strlen(trim($sNewName)) > 0 && false === mb_strpos($sNewName, '..'));
		
		if($bOldNameValid && $bNewNameValid)
		{
			$sOldPathName = '';
			$sNewPathName = '';

			$sUploadPath = canonizePath(realpath($sUploadPath));
			if(mb_strlen(trim($sRelativePath)) > 0)
			{
				$sPathName		= canonizePath(realpath($sUploadPath . $sRelativePath));
				$sOldPathName	= canonizePath(realpath($sPathName . $sOldName));
				$sNewPathName	= $sPathName . $sNewName;
			}
			else
			{
				$sOldPathName	= canonizePath(realpath($sUploadPath . $sOldName));
				$sNewPathName	= $sUploadPath . $sNewName;
			}

			$sUploadPath = realpath($sUploadPath);
			$bOldPathNameValid = (realpath(mb_substr($sOldPathName, 0, mb_strlen($sUploadPath))) === $sUploadPath);

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
 * This class provides information about the environment of the file manager.
 * The path, the acl
 */
class BAB_FileManagerEnv
{

	/**
	 * Prefix to collective filemanager folders (usually followed by delegation id).
	 * @var string
	 */
	const delegationPrefix = 'DG';

	/**
	 * Prefix to personal filemanager folders (usually followed by user id).
	 * @var string
	 */
	const userPrefix = 'U';

	/**
	 * The path to filemanager folders relative to the ovidentia uploadPath.
	 * @var string
	 */
	const relativeFmRootPath = 'fileManager/';

	/**
	 * The path to collective folders relative to the ovidentia uploadPath.
	 * @var string
	 */
	const relativeFmCollectivePath = 'fileManager/collectives/';

	/**
	 * The path to personal folders relative to the ovidentia uploadPath.
	 * @var string
	 */
	const relativeFmPersonalPath = 'fileManager/users/';

	/**
	 * The path to filemanager temp folders relative to the ovidentia uploadPath.
	 * @var string
	 */
	const relativeFmTempPath = 'fileManager/temp/';


	private static $sRealUploadPath = null;
	private static $sFmRealUploadPath = null;
	private static $sFmRealCollectivePath = null;
	private static $sFmRealPersonalPath = null;
	private static $sFmRealTempPath = null;

	
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


	/**
	 * Returns the real full path to ovidentia upload folder.
	 * @return string
	 */
	public static function getUploadPath()
	{
		if (!isset(self::$sRealUploadPath)) {
			self::$sRealUploadPath = $GLOBALS['babUploadPath'];
			$iLength = mb_strlen(trim(self::$sRealUploadPath));
			if($iLength > 0)
			{
				self::$sRealUploadPath = BAB_PathUtil::addEndSlash(BAB_PathUtil::sanitize(self::$sRealUploadPath));
			}
		}
		return self::$sRealUploadPath;
	}

	/**
	 * Returns the real full path to root folder of filemanager.
	 * @return string
	 */
	public static function getFmRealUploadPath()
	{
		if (!isset(self::$sFmRealUploadPath)) {
			self::$sFmRealUploadPath = self::getUploadPath() . self::relativeFmRootPath;
		}
		return self::$sFmRealUploadPath;
	}

	/**
	 * Returns the real full path to collective root folder of filemanager.
	 * @return string
	 */
	public static function getFmRealCollectivePath()
	{
		if (!isset(self::$sFmRealCollectivePath)) {
			self::$sFmRealCollectivePath = self::getUploadPath() . self::relativeFmCollectivePath;
		}
		return self::$sFmRealCollectivePath;
	}

	/**
	 * Returns the real full path to personal root folder of filemanager.
	 * @return string
	 */
	public static function getFmRealPersonalPath()
	{
		if (!isset(self::$sFmRealPersonalPath)) {
			self::$sFmRealPersonalPath = self::getUploadPath() . self::relativeFmPersonalPath;
		}
		return self::$sFmRealPersonalPath;
	}

	/**
	 * Returns the real full path to Temp folder of filemanager.
	 * @return string
	 */
	public static function getFmRealTempPath()
	{
		if (!isset(self::$sFmRealTempPath)) {
			self::$sFmRealTempPath = self::getUploadPath() . self::relativeFmTempPath;
		}
		return self::$sFmRealTempPath;
	}


	function getValuesFromRequest() 
	{
		global $BAB_SESS_USERID, $babBody;

		$this->sPath = (string) bab_rp('path');
		
		if('' != $this->sPath)
		{
			$this->sPath = (string) bab_convertToDatabaseEncoding($this->sPath);
		}
		
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

		$this->iPathLength = mb_strlen(trim($this->sPath));
		$this->sEndSlash = '';
		if($this->iPathLength > 0)
		{
			$this->sEndSlash = '/';
		}

		{
			$this->iId				= $this->iIdObject;
			$this->sUploadPath		= self::getUploadPath();
			$this->sFmUploadPath	= self::getFmRealUploadPath();
			$this->sRelativePath	= $this->sPath . $this->sEndSlash;


			$this->oFmRootFolder = BAB_FmFolderHelper::getFmFolderById($this->iIdObject);
			//'' === $this->sGr si on a clique depuis la section utilisateur

			if(!is_null($this->oFmRootFolder) && '' !== $this->sGr)
			{
				$this->iIdObject = $this->oFmRootFolder->getId();

				$this->sFmCollectiveRootPath	= self::getFmRealCollectivePath() . self::delegationPrefix . $this->oFmRootFolder->getDelegationOwnerId() . '/';	
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
			else
			{
				$this->sFmCollectiveRootPath	= self::getFmRealCollectivePath() . self::delegationPrefix . bab_getCurrentUserDelegation() . '/';	
				$this->sFmCollectivePath		= $this->sFmCollectiveRootPath;
				$this->sFmRootPath				= $this->sFmCollectivePath;
				$this->sFmCollectivePath		.= $this->sRelativePath;
			}
		}

		if('N' === $this->sGr)
		{
			if(userHavePersonnalStorage())
			{
				$this->sFmRootPath		= self::getFmRealPersonalPath() . self::userPrefix . $BAB_SESS_USERID . '/';
				$this->sFmPersonnalPath	= $this->sFmRootPath;

				if(!is_dir($this->sFmPersonnalPath))
				{
					BAB_FmFolderHelper::createDirectory($this->sFmPersonnalPath);
				}

				if(0 !== mb_strlen(trim($this->sPath)))
				{
					$this->sFmPersonnalPath .= $this->sRelativePath;
				}
			}
		}

		if('' === $this->sGr)
		{
			if(userHavePersonnalStorage())
			{
				$this->sFmPersonnalPath = self::getFmRealPersonalPath();
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


	static public function getPersonalPath($iIdUser)
	{
		return BAB_FmFolderHelper::getUploadPath() . self::relativeFmPersonalPath . self::userPrefix . $iIdUser . '/';
	}

	
	static public function getCollectivePath($iIdDelegation)
	{
		return BAB_FmFolderHelper::getUploadPath() . self::relativeFmCollectivePath . self::delegationPrefix . $iIdDelegation . '/';
	}

	function getTempPath()
	{
		return BAB_FmFolderHelper::getUploadPath() . self::relativeFmTempPath;
	}

	/**
	 * Get total size used in the fileManager directory
	 * @access 	public
	 * @since	6.7.91
	 * @return 	int
	 */
	function getFMTotalSize() 
	{
		return getDirSize($this->sFmUploadPath);
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
			else if ('Y' === $this->sGr)
			{
				$sParentPath = $this->sRelativePath;
				if (!is_null($this->oFmFolder))
				{
					if (true === canManage($sParentPath) || true === haveRightOn($sParentPath, BAB_FMMANAGERS_GROUPS_TBL))
					{
						return true;
					}
					else if (true === canUpload($sParentPath) || true === canDownload($sParentPath) || true === canUpdate($sParentPath))
					{
						if ('Y' === $this->oFmFolder->getActive())
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
			else if ('' === $this->sGr)
			{
				if (true === userHaveRightOnCollectiveFolder() || true === userHavePersonnalStorage())
				{
					if (0 === $this->iPathLength)
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

		if(mb_strlen(trim($sUploadPath)) === 0)
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

		if(false !== mb_strpos($this->sPath, '..'))
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


function isStringSupportedByFileSystem($sName)
{	
	$oFileMgrEnv		= getEnvObject();
	$bCreatable			= false;
	$sFmTempPath		= $oFileMgrEnv->getTempPath();
	$sFmUserTempPath	= $sFmTempPath . session_id() . '/';
	$sFullPath			= $sFmUserTempPath . $sName;

	if(!is_dir($sFmTempPath))
	{
		if(!@bab_mkdir($sFmTempPath, $GLOBALS['babMkdirMode']))
		{
			return false;
		}
	}
	
	if(!is_dir($sFmUserTempPath))
	{
		if(!@bab_mkdir($sFmUserTempPath, $GLOBALS['babMkdirMode']))
		{
			return false;
		}
	}

	if(!is_dir($sFullPath))
	{
		if(@bab_mkdir($sFullPath, $GLOBALS['babMkdirMode']))
		{
			if(is_dir($sFullPath))
			{
				$bCreatable	= true;
			}
		}
	}
	
	BAB_FmFolderSet::removeDir($sFmUserTempPath);
	return $bCreatable;
}


function canonizePath($sPath)
{
	return addEndSlash(removeEndSlashes($sPath));
}


function replaceInvalidFolderNameChar($sName)
{
	if(is_string($sName) && mb_strlen(trim($sName)) > 0)
	{
		static $aOvSearch	= null;
		static $aOvReplace	= null;
		
		if(isset($GLOBALS['babFileNameTranslation']))
		{
			if(!isset($aOvSearch))
			{
				getSearchAndReplace($GLOBALS['babFileNameTranslation'], $aOvSearch, $aOvReplace);
			}
			
			if(isset($aOvSearch))
			{
				$sName = str_replace($aOvSearch, $aOvReplace, $sName);
			}
		}
		
		static $aTranslation	= array('\\' => '_', '/' => '_', ':' => '_', '*' => '_', '?' => '_', '<' => '_', '>' => '_', '|' => '_', '"' => '_');
		static $aFmSearch		= null;
		static $aFmReplace		= null;
		
		if(!isset($aFmSearch))
		{
			getSearchAndReplace($aTranslation, $aFmSearch, $aFmReplace);
		}
		
		
		$sName = str_replace($aFmSearch, $aFmReplace, $sName);
	}
	return $sName;
}


function getSearchAndReplace($aTranslation, &$aSearch, &$aReplace)
{
	if(is_array($aTranslation))
	{
		$aSearch	= array();
		$aReplace	= array();
		
		foreach($aTranslation as $_sSearch => $_sReplace)
		{
			if('' != $_sSearch && '' != $_sReplace)
			{
				$aSearch[]	= $_sSearch;
				$aReplace[]	= $_sReplace;
			}
		}
	}
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
		$iLength = mb_strlen(trim($sPath));
		if($iLength > 0)
		{
			$sLastChar = mb_substr($sPath, - 1);
			if($sLastChar !== '/' && $sLastChar !== '\\')
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
		$iLength = mb_strlen(trim($sPath));
		if($iLength > 0)
		{
			$sLastChar = mb_substr($sPath, - 1);
			if($sLastChar === '/' || $sLastChar === '\\')
			{
				return mb_substr($sPath, 0, -1);
			}
		}
	}
	return $sPath;
}


function haveEndSlash($sPath)
{
	$iLength = mb_strlen(trim($sPath));
	if($iLength > 0)
	{
		$sLastChar = mb_substr($sPath, - 1);
		return ($sLastChar === '/' || $sLastChar === '\\');
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
				$sPathName = mb_substr($sPathName, 0, -1);
			}
		}
	}
	return $sPathName;
}

function getFirstPath($sPath)
{
	$iLength = mb_strlen(trim($sPath));
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
	$iLength = mb_strlen(trim($sPath));
	if($iLength > 0)
	{
		$aPath = explode('/', $sPath);
		if(is_array($aPath))
		{
			$iCount = count($aPath);
			if($iCount >= 2)
			{
				$sLastChar = mb_substr($sPath, - 1);
				if('/' === $sLastChar)
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
	$iLength = mb_strlen(trim($sPath));
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
	$iLength = mb_strlen(trim($sPath));
	if($iLength > 0)
	{
		$aPath = explode('/', $sPath);
		if(is_array($aPath))
		{
			$iCount = count($aPath);
			if($iCount >= 2)
			{
				$aToRemove = array();
				$sLastChar = mb_substr($sPath, - 1);
				if($sLastChar == '/')
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
		if(mb_strtolower(get_class($object)) === mb_strtolower($class))
		{
			return true;
		}
		return is_subclass_of($object, $class);
	}
}

function initEnvObject()
{
	$oFileManagerEnv = getEnvObject();
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
	$oFileManagerEnv = getEnvObject();
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
	$oFileManagerEnv = getEnvObject();
	$sFullPath = $oFileManagerEnv->getRootFmPath() . $sPath;
	
	static $aPath = array();
	if(!array_key_exists($sFullPath, $aPath))
	{
		$aPath[$sFullPath] = haveRightOn($sPath, BAB_FMUPLOAD_GROUPS_TBL);
	}	
	return $aPath[$sFullPath];
}


function canDownload($sPath, $oFileMgrEnv = null)
{
	if (null === $oFileMgrEnv) {
		$oFileMgrEnv = getEnvObject();
	}

	$sFullPath = $oFileMgrEnv->getRootFmPath() . $sPath;
	
	static $aPath = array();
	if(!array_key_exists($sFullPath, $aPath))
	{
		$aPath[$sFullPath] = haveRightOn($sPath, BAB_FMDOWNLOAD_GROUPS_TBL, $oFileMgrEnv);
/*
bab_debug(
	'sFullPath ==> ' . $sFullPath . "\n" .
	'sPath     ==> ' . $sPath . "\n" .
	'sRight    ==> ' . ($aPath[$sFullPath]) ? 'Yes' : 'No'
);
//*/
	}	
	return $aPath[$sFullPath];
}


function canUpdate($sPath)
{
	$oFileManagerEnv = getEnvObject();
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
	$oFileManagerEnv = getEnvObject();
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
	$oFileManagerEnv = getEnvObject();
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
	$oFileManagerEnv = getEnvObject();
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
	$oFileManagerEnv		= getEnvObject();
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
					return false;
				}
			}
			else 
			{
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
						return false;
					}
				}
				else 
				{
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
	

	if($bHaveRightOnSrc && $bHaveRightOnTrg)
	{

		$sFullCurrFolder = realpath($sRootFmPath . $sTrgPath);
		
		$sFullPathToPaste = realpath($sRootFmPath . $sSrcPath);
		if(false === $sFullPathToPaste)
		{
			return false;
		}

		

		$sFullCurrFolder	= removeEndSlah($sFullCurrFolder);
		$sFullPathToPaste	= removeEndSlah($sFullPathToPaste);
		$sFullParentPath 	= mb_substr($sFullCurrFolder, 0, mb_strlen($sFullPathToPaste));

		
		//Est ce que dans le r�pertoire courant il y a un r�pertoire de m�me nom ?
		$bSameDir = true;
		$sTrgFullPathName = $sRootFmPath . addEndSlash($sTrgPath) . getLastPath($sSrcPath);
		$sSrcFullPathName = $sRootFmPath . removeEndSlah($sSrcPath);
		
		if(is_dir($sTrgFullPathName))
		{
			$bSameDir = ($sSrcFullPathName === $sTrgFullPathName);
		}
		
		return ($sFullParentPath !== $sFullPathToPaste && $bSameDir);
	}
	return false;
}


function canPasteFile($iIdSrcRootFolder, $sSrcPath, $iIdTrgRootFolder, $sTrgPath, $sFilename)
{
	$oFileManagerEnv		= getEnvObject();
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
			return false;
		}
		
		$iIdRootFolder = 0;
		$oOwnerTrgFmFolder = null;
		BAB_FmFolderHelper::getInfoFromCollectivePath($sTrgPath, $iIdRootFolder, $oOwnerTrgFmFolder);
		if($iIdRootFolder !== $iIdTrgRootFolder || is_null($oOwnerTrgFmFolder))
		{
			return false;
		}
	
		$bHaveRightOnSrc = bab_isAccessValid(BAB_FMMANAGERS_GROUPS_TBL, $oOwnerSrcFmFolder->getId());
		$bHaveRightOnTrg = bab_isAccessValid(BAB_FMMANAGERS_GROUPS_TBL, $oOwnerTrgFmFolder->getId());
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
	
	$iLength = mb_strlen(trim($sSrcPath));
	if($iLength > 0 &&  '/' === mb_substr($sSrcPath, - 1))
	{
		$sSrcPath = mb_substr($sSrcPath, 0, -1);
	}
	
	$iLength = mb_strlen(trim($sTrgPath));
	if($iLength > 0 && '/' === mb_substr($sTrgPath, - 1))
	{
		$sTrgPath = mb_substr($sTrgPath, 0, -1);
	}
	

	return ($bHaveRightOnSrc && $bHaveRightOnTrg && (!$bFileExistOnTrgPath || ($iIdSrcRootFolder === $iIdTrgRootFolder && $sSrcPath === $sTrgPath)));
}


function haveRight($sPath, $sTableName)
{
	$oFileManagerEnv = getEnvObject();
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
	$oFileManagerEnv = getEnvObject();
	
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


function haveRightOn($sPath, $sTableName, $oFileMgrEnv = null)
{
	if (null === $oFileMgrEnv) {
		$oFileMgrEnv = getEnvObject();
		$sFullPath = $oFileMgrEnv->getRootFmPath() . $sPath;
	}

	
	$oFileMgrEnv = getEnvObject();
	if($oFileMgrEnv->userIsInCollectiveFolder() || $oFileMgrEnv->userIsInRootFolder())
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
	else if($oFileMgrEnv->userIsInPersonnalFolder())
	{
		return isUserFolder($sPath);
	}
	else 
	{
		return false;
	}
}

/* A utiliser seulement pour les fonction haveRightxxx quand on est � la racine*/
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
	
	$oFileManagerEnv = getEnvObject();
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
		
		$oFmFolderSet->select($oCriteria);
		
		while(null !== ($oFmFolder = $oFmFolderSet->next()))
		{
			if(!array_key_exists($oFmFolder->getDelegationOwnerId(), $aProcessedDelegation))
			{
				$bManager = bab_isAccessValid(BAB_FMMANAGERS_GROUPS_TBL, $oFmFolder->getId());
				$bUpdate = bab_isAccessValid(BAB_FMUPDATE_GROUPS_TBL, $oFmFolder->getId());
				$bDownload = bab_isAccessValid(BAB_FMDOWNLOAD_GROUPS_TBL, $oFmFolder->getId());
				$bUpload = bab_isAccessValid(BAB_FMUPLOAD_GROUPS_TBL, $oFmFolder->getId());
				
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
			
		$oResult = $babDB->db_query($sQuery);
		$iNumRows = $babDB->db_num_rows($oResult);
		$iIndex = 0;
	
		while($iIndex < $iNumRows && null !== ($aDatas = $babDB->db_fetch_assoc($oResult)))
		{
			$iIndex++;
			$aVisibleDelegation[$aDatas['iId']] =  $aDatas['sName'];
		}
		
		bab_sort::natcasesort($aVisibleDelegation);
		
		if($bMainSite)
		{
			$aVisibleDelegation = array(0 => bab_translate("All site")) + $aVisibleDelegation;
		}
	}
	return $aVisibleDelegation;
}



/**
 * Test download access of a file from filemanager
 * @param	int		$id_file
 * @return bool
 */
function bab_FmFileCanDownload($id_file) {

	static $oFmEnv 			= null;
	static $oFolderFileSet	= null;
	static $oFolderSet		= null;
	static $aAccessFolders	= array();

	if (null === $oFmEnv) {
		$oFmEnv 			= new BAB_FileManagerEnv;
		$oFolderFileSet		= bab_getInstance('BAB_FolderFileSet');
		$oFolderSet			= bab_getInstance('BAB_FmFolderSet');
	}

	$oNameField			= $oFolderSet->aField['sName'];
	$oRelativePathField	= $oFolderSet->aField['sRelativePath'];
	$oIdDgOwnerField	= $oFolderSet->aField['iIdDgOwner'];

	$oId	= $oFolderFileSet->aField['iId'];
	$oFile	= $oFolderFileSet->get($oId->in($id_file));

	if(!($oFile instanceof BAB_FolderFile)) {
		return false;
	}

	$sRootFldName	= getFirstPath($oFile->getPathName());
	$sPathName		= $oFile->getPathName();
	$iIdDelegation	= $oFile->getDelegationOwnerId();
	$sGr			= $oFile->getGroup();

	$uid = $iIdDelegation.$sGr.$sPathName;

	if (isset($aAccessFolders[$uid])) {
		return $aAccessFolders[$uid];
	}

	
	$oCriteria = $oNameField->in($sRootFldName);
	$oCriteria = $oCriteria->_and($oRelativePathField->in(''));
	$oCriteria = $oCriteria->_and($oIdDgOwnerField->in($iIdDelegation));


	$oFolder = $oFolderSet->get($oCriteria);
	if(!($oFolder instanceof BAB_FmFolder))
	{
		return false;
	}

	$oFmEnv->sGr		= $sGr;					
	$oFmEnv->sPath		= BAB_PathUtil::removeEndSlashes($sPathName);		
	$oFmEnv->iIdObject	= $oFolder->getId();
	$oFmEnv->init();
	
	$aAccessFolders[$uid] = canDownload($sPathName, $oFmEnv);
	return $aAccessFolders[$uid];
}

