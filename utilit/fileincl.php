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
require_once dirname(__FILE__) . '/filenotifyincl.php';
require_once dirname(__FILE__) . '/fmset.class.php';



define('BAB_FVERSION_FOLDER', 'OVF');

/* 0 -> other, 1 -> edit, 2 -> unedit, 3 -> commit, 4 -> initial upload */
define('BAB_FACTION_OTHER',				0);
define('BAB_FACTION_EDIT',				1);
define('BAB_FACTION_UNEDIT',			2);
define('BAB_FACTION_COMMIT',			3);
define('BAB_FACTION_INITIAL_UPLOAD',	4);

$babFileActions = array(bab_translate("Other"), bab_translate("Edit file"),
bab_translate("Unedit file"), bab_translate("Commit file"));



function bab_notifyAdminQuota($folder = false)
{
	if($folder === false){
		return false;
	}elseif($folder === true){
		global $babDB;
		$res = $babDB->db_query("
			select *
			from ".BAB_FILES_TBL."
		");
		
		$deleteSize = 0;
		$notDeleteSize = 0;
		while($arr = $babDB->db_fetch_array($res)){
			if($arr['state'] == 'D'){
				$deleteSize+= $arr['size'];
			}else{
				$notDeleteSize+= $arr['size'];
			}
		}
		
		$maxSize = $GLOBALS['babMaxTotalSize'];
		$quota = $GLOBALS['babQuotaFM'];
		
		$mailTos = bab_getGroupsMembers(BAB_ADMINISTRATOR_GROUP);
		$title = bab_translate('File manager size quota exceed');
	}else{
		
		global $babDB;
		$res = $babDB->db_query("
			select *
			from ".BAB_FILES_TBL."
			where bgroup ='Y'	
			and path LIKE ".$babDB->quote($folder.'%')."
		");
		
		$deleteSize = 0;
		$notDeleteSize = 0;
		while($arr = $babDB->db_fetch_array($res)){
			$dgOwner = $arr['iIdDgOwner'];
			if($arr['state'] == 'D'){
				$deleteSize+= $arr['size'];
			}else{
				$notDeleteSize+= $arr['size'];
			}
		}
		
		$maxSize = $GLOBALS['babMaxGroupSize'];
		$quota = $GLOBALS['babQuotaFolder'];
		if($dgOwner != 0){
			$delegAdmin = bab_getAdministratorsDelegation($dgOwner);
		}else{
			$delegAdmin = array();
		}
		$mailTos = bab_getGroupsMembers(BAB_ADMINISTRATOR_GROUP);
		$mailTos = array_merge($mailTos, $delegAdmin);
		$title = sprintf(bab_translate("'%s' folder size quota exceed"),$folder);
	}
	
	$babMail = bab_mail();
	$babMail->mailFrom($GLOBALS['babAdminEmail'], $GLOBALS['babAdminName']);

	foreach($mailTos as $mailTo){
		$babMail->mailBcc($mailTo['email'], $mailTo['name']);
	}
	$babMail->mailSubject($title);
	$babMail->mailBody(
		sprintf(
			bab_translate('_MAILQUOTA_'),
			$title,
			number_format($deleteSize/1000, 0, '.' ,' '),
			number_format(($deleteSize*100/$maxSize),2, '.' ,' ').' %',
			number_format($notDeleteSize/1000,0, '.' ,' '),
			number_format(($notDeleteSize*100/$maxSize),2, '.' ,' ').' %',
			number_format(($deleteSize+$notDeleteSize)/1000,0, '.' ,' '),
			number_format((($deleteSize+$notDeleteSize)*100/$maxSize),2, '.' ,' ').' %',
			number_format($maxSize/1000,0, '.' ,' '),
			$quota.' %'
		)
	);
	$babMail->send();
	
	return true;
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


/**
 *
 * @param string $gr
 * @param int $id
 * @return bool
 */
function bab_isAccessFileValid($gr, $id)
{
	global $babDB;

	$access = false;

	$ovgroups = bab_Groups::getGroups();

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
		if( $ovgroups[BAB_REGISTERED_GROUP]['ustorage'] == 'Y')
		{
			$access = true;
		}
		else
		{
			$usergroups = bab_Groups::getUserGroups();
			foreach($usergroups as $grpid)
			{
				if( $ovgroups[$grpid]['ustorage'] == 'Y')
				{
					$access = true;
					break;
				}
			}
		}
	}
	return $access;
}


function acceptFileVersion($oFolderFile, $oFolderFileVersion)
{
	require_once dirname(__FILE__) . '/eventfm.php';
	require_once dirname(__FILE__) . '/reference.class.php';

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

	$filereference = bab_Reference::makeReference('ovidentia', '', 'files', 'file', $oFolderFile->getId());

	$eventfiles = new bab_eventFmAfterAddVersion;
	$eventfiles->setFolderId($oFolderFile->getOwnerId());
	$eventfiles->addReference($filereference);
	bab_fireEvent($eventfiles);

}










/**
 *
 * @param 	string 	$tags		Comma separated tags
 * @param	string	$baddtags	Y | N	Users allowed to add tags in thesorus
 * @return array
 */
function bab_getTagsFromString($tags, $baddtags)
{
	$aTags = array();



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
						$oTag = $oTagMgr->create($sTagName, false);
						if($oTag instanceof bab_Tag)
						{
							$aTags[] = $oTag;
						}
					}
				}
			}
		}
	}

	return $aTags;
}











class bab_FmFileErrorException extends Exception
{
	/**
	 * Filename of file with error
	 */
	public $fmFileName = null;
}





/**
 * Add one file
 * @see saveFile
 *
 * @param 	bab_fileHandler 			$fmFile
 * @param	int							$count				position
 * @param 	int 						$id					Folder id
 *
 * @param 	string 						$gr					Y | N
 * @param 	string 						$sRelativePath
 * @param	string						$pathx				Full path on filesystem
 * @param 	array 						$description
 * @param 	string | array				$keywords
 * @param 	array 						$readonly
 * @param 	string 						$maxdownloads
 * @param	bab_eventFmAfterFileUpload	$eventfiles
 * @param	string						$confirmed			Y | N
 * @param	string						$baddtags			Y | N	Add tag allowed or not
 *
 * @throw 	bab_FmFileErrorException
 *
 * @return unknown_type
 */
function bab_addUploadedFile(bab_fileHandler $fmFile, $count, $id, $gr, $sRelativePath, $pathx, $description, $keywords, $readonly, $maxdownloads, bab_eventFmAfterFileUpload $eventfiles, $confirmed, $baddtags)
{

	global $babDB;
	$oFileManagerEnv =& getEnvObject();
	$aTags = array();


	if(empty($fmFile->filename) || $fmFile->filename == 'none')
	{
		return false;
	}

	if($fmFile->size > $GLOBALS['babMaxFileSize'])
	{
		$exception = new bab_FmFileErrorException(bab_translate("The file was larger than the maximum allowed size") ." : ". $GLOBALS['babMaxFileSize']);
		$exception->fmFileName = $fmFile->filename;
		throw $exception;
		return false;
	}

	static $FMTotalSize = null;
	if(!isset($FMTotalSize)){
		$FMTotalSize = $oFileManagerEnv->getFMTotalSize();
	}

	if($fmFile->size +  $FMTotalSize > $GLOBALS['babMaxTotalSize'])
	{
		$exception = new bab_FmFileErrorException(bab_translate("The file size exceed the limit configured for the file manager"));
		$exception->fmFileName = $fmFile->filename;
		throw $exception;
		return false;
	}

	$totalsize = getDirSize($pathx);
	if($fmFile->size + $totalsize > ($gr == "Y"? $GLOBALS['babMaxGroupSize']: $GLOBALS['babMaxUserSize']))
	{
		$exception = new bab_FmFileErrorException(bab_translate("The file size exceed the limit configured for the current type of folder"));
		$exception->fmFileName = $fmFile->filename;
		throw $exception;
		return false;
	}

	if(false !== $fmFile->error)
	{
		$exception = new bab_FmFileErrorException($fmFile->error);
		$exception->fmFileName = $fmFile->filename;
		throw $exception;
		return false;
	}

	$osfname = $fmFile->filename;
	$osfname = replaceInvalidFolderNameChar($fmFile->filename);

	if(!isStringSupportedByFileSystem($osfname))
	{
		$exception = new bab_FmFileErrorException(str_replace('%file%', $osfname, bab_translate("The file %file% contains characters not supported by the file system")));
		$exception->fmFileName = $fmFile->filename;
		throw $exception;
		return false;
	}

	$name = $osfname;
	$bexist = false;
	if(file_exists($pathx.$osfname))
	{
		$res = $babDB->db_query("
			SELECT * FROM ".BAB_FILES_TBL."
			WHERE
				id_owner=	'".$babDB->db_escape_string($id)."'
				AND bgroup=	'".$babDB->db_escape_string($gr)."'
				AND name=	'".$babDB->db_escape_string($name)."'
				AND path=	'".$babDB->db_escape_string($sRelativePath)."'
			");

		if($res && $babDB->db_num_rows($res) > 0)
		{

			$arr = $babDB->db_fetch_array($res);
			if($arr['state'] == "D")
			{
				$exception = new bab_FmFileErrorException(bab_translate("A file with the same name already exists in the basket"));
				$exception->fmFileName = $fmFile->filename;
				throw $exception;
				return false;
			}
		}

		if($bexist == false)
		{
			$exception = new bab_FmFileErrorException(bab_translate("A file with the same name already exists"));
			$exception->fmFileName = $fmFile->filename;
			throw $exception;
			return false;
		}
	}

	bab_setTimeLimit(0);

	if(!$fmFile->import($pathx.$osfname))
	{
		$exception = new bab_FmFileErrorException(bab_translate("The file could not be uploaded"));
		$exception->fmFileName = $fmFile->filename;
		throw $exception;
		return false;
	}


	if(empty($GLOBALS['BAB_SESS_USERID']))
	{
		$idcreator = 0;
	}
	else
	{
		$idcreator = $GLOBALS['BAB_SESS_USERID'];
	}

	$bnotify = false;
	if($gr == "Y")
	{
		$rr = $babDB->db_fetch_array($babDB->db_query("select filenotify from ".BAB_FM_FOLDERS_TBL." where id='".$babDB->db_escape_string($id)."'"));
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


	if ($bexist)
	{
		$req = "
		UPDATE ".BAB_FILES_TBL." set
			description='".$babDB->db_escape_string($description[$count])."',
			readonly='".$babDB->db_escape_string($readonly[$count])."',
			max_downloads='".$babDB->db_escape_string($maxdownloads[$count])."',
			confirmed='".$babDB->db_escape_string($confirmed)."',
			modified=now(),
			hits='0',
			modifiedby='".$babDB->db_escape_string($idcreator)."',
			state='',
			index_status='".$babDB->db_escape_string($index_status)."',
			size='".$babDB->db_escape_string($fmFile->size)."'
		WHERE
			id='".$babDB->db_escape_string($arr['id'])."'";
		$babDB->db_query($req);
		$idf = $arr['id'];


	}
	else
	{
		$req = 'INSERT INTO ' . BAB_FILES_TBL . '(
					name,
					description, '
					. (isset($maxdownloads) ? 'max_downloads,' : '') . '
					path,
					id_owner,
					bgroup,
					link,
					readonly,
					state,
					created,
					author,
					modified,
					modifiedby,
					confirmed,
					index_status,
					iIdDgOwner,
					size)
				VALUES (';
		$req .= $babDB->quote($name). ',
					' . $babDB->quote($description[$count]) . ',
					' . (isset($maxdownloads) ? $babDB->quote($maxdownloads[$count]) . ', ' : '') .
					$babDB->quote($sRelativePath) . ',
					' . $babDB->quote($id). ',
					' . $babDB->quote($gr). ',
					0,
					' . $babDB->quote($readonly[$count]). ',
					\'\',
					NOW(),
					' . $babDB->quote($idcreator) . ',
					NOW(),
					' . $babDB->quote($idcreator) . ',
					' . $babDB->quote($confirmed) . ',
					' . $babDB->quote($index_status) . ',
					' . $babDB->quote(bab_getCurrentUserDelegation()) . ',
					' . $babDB->quote($fmFile->size) . ')';
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



	$filereference = bab_Reference::makeReference('ovidentia', '', 'files', 'file', $idf);

	// ceanup existing tags
	$oReferenceMgr	= bab_getInstance('bab_ReferenceMgr');
	$oReferenceMgr->removeByReference($filereference);



	if( !is_array($keywords))
	{
		$tags = trim($keywords);
	}
	else
	{
		$tags = trim($keywords[$count]);
	}

	$aTags = bab_getTagsFromString($tags, $baddtags);

	// add tags
	if(count($aTags))
	{
		$oReferenceMgr = bab_getInstance('bab_ReferenceMgr');

		foreach($aTags as $k => $oTag)
		{
			$oReferenceMgr->add($oTag->getName(), $filereference);
		}
	}



	if(BAB_INDEX_STATUS_INDEXED === $index_status)
	{
		$obj = new bab_indexObject('bab_files');
		$obj->setIdObjectFile($pathx.$osfname, $idf, $id);
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
		if(notifyApprovers($idf, $id))
		{
			// the file si visible
			$eventfiles->addReference($filereference);
		}


	}
	
	if($GLOBALS['babQuotaFM']
			&& ($FMTotalSize <= $GLOBALS['babMaxTotalSize']*$GLOBALS['babQuotaFM']/100)
			&& ($fmFile->size +  $FMTotalSize > $GLOBALS['babMaxTotalSize']*$GLOBALS['babQuotaFM']/100)){
		bab_notifyAdminQuota(true);//notify when exceed the quota on FILE MANAGER
	}
	
	if($GLOBALS['babQuotaFolder']
			&& $gr == "Y"
			&& ($totalsize <= $GLOBALS['babMaxGroupSize']*$GLOBALS['babQuotaFolder']/100)
			&& ($fmFile->size +  $totalsize > $GLOBALS['babMaxGroupSize']*$GLOBALS['babQuotaFolder']/100)){
		bab_notifyAdminQuota($sRelativePath);//notify when exceed the quota on Group Folder
	}

	return $idf;
}












/**
 * @param 	array 	$fmFiles	(array of bab_fileHandler instances)
 * @param	int		$id
 * @param	Y|N		$gr
 * @param	string	$path
 * @param	string	$description
 * @param	string	$keywords
 * @param	string	$readonly		Y|N
 * @param	int		$maxdownloads
 *
 * @return	boolean
 */
function saveFile($fmFiles, $id, $gr, $path, $description, $keywords, $readonly, $maxdownloads)
{
	require_once dirname(__FILE__) . '/tagApi.php';
	require_once dirname(__FILE__) . '/eventfm.php';

	global $babBody, $babDB, $BAB_SESS_USERID;
	$access = false;
	$bmanager = false;
	$access = false;
	$confirmed = 'N';
	$baddtags = 'N';

	$sRelativePath	= '';
	$pathx 			= '';

	$oFileManagerEnv =& getEnvObject();

	if(!empty($BAB_SESS_USERID))
	{
		if('N' === $gr && userHavePersonnalStorage())
		{
			$access = true;
			$confirmed = 'Y';

			$sRelativePath = $oFileManagerEnv->sRelativePath;
			$pathx = $oFileManagerEnv->getCurrentFmPath();

		}
		else if('Y' === $gr)
		{
			$rr = $babDB->db_fetch_array($babDB->db_query("select baddtags from ".BAB_FM_FOLDERS_TBL." where id='".$babDB->db_escape_string($id)."'"));
			$baddtags = $rr['baddtags'];


			$oFmFolder = null;
			$access = BAB_FmFolderHelper::getInfoFromCollectivePath($oFileManagerEnv->sPath, $iIdRootFolder, $oFmFolder);
			if($access)
			{
				$id = $oFmFolder->getId();
				$pathx = $oFileManagerEnv->getCurrentFmPath();
				$sRelativePath = $oFileManagerEnv->sRelativePath;

				if(canManage($oFileManagerEnv->sRelativePath) || canDownload($oFileManagerEnv->sRelativePath))
				{
					$access = true;
				}
			}
		}
	}


	if(!$access)
	{
		$babBody->msgerror = bab_translate("Access denied");
		return false;
	}


	/**
	 * Files correctly uploaded
	 * @var array of id_file
	 */
	$okfiles = array();

	/**
	 * List of errors
	 * @var array
	 */
	$errfiles = array();

	/**
	 * Event used triggered after file upload if files are made available to users
	 */
	$eventfiles = new bab_eventFmAfterFileUpload;
	$eventfiles->setFolderId($id);




	$count = 0;
	foreach($fmFiles as $fmFile)
	{

		try {
			$idf = bab_addUploadedFile($fmFile, $count, $id, $gr, $sRelativePath, $pathx, $description, $keywords, $readonly, $maxdownloads, $eventfiles, $confirmed, $baddtags);

			if ($idf) {
				$okfiles[] = $idf;
			}

		} catch (bab_FmFileErrorException $e) {
			$errfiles[] = array(
				'error' => $e->getMessage(),
				'file' 	=> $e->fmFileName
			);
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


	if (0 < count($eventfiles->getReferences()))
	{
		bab_fireEvent($eventfiles);
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
 * @param	int			$maxdownloads
 */
function saveUpdateFile($idf, $fmFile, $fname, $description, $keywords, $readonly, $confirm, $bnotify, $descup, $maxdownloads)
{
	require_once dirname(__FILE__) . '/eventfm.php';
	require_once dirname(__FILE__) . '/tagApi.php';

	global $babBody, $babDB, $BAB_SESS_USERID;

	$oReference		= bab_Reference::makeReference('ovidentia', '', 'files', 'file', $idf);

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


		$tags = trim($keywords);
		$aTags = bab_getTagsFromString($tags, $rr['baddtags']);


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

		/**
		 * If a new file has been uploaded or not
		 * @var bool
		 */
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

		if (!empty($maxdownloads)) {
			$tmp[] = 'max_downloads=' . $babDB->quote($maxdownloads);
		}

		if (!empty($uploadf_size)) {
			$tmp[] = 'size=' . $babDB->quote($uploadf_size);
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
						$rr = $babDB->db_fetch_assoc($babDB->db_query("select id_dgowner from ".BAB_FM_FOLDERS_TBL." where id='".$babDB->db_escape_string($oFolderFile->getOwnerId())."'"));
						$babDB->db_query("update ".BAB_FILES_TBL." set confirmed='Y', idfai='0' where id = '".$babDB->db_escape_string($oFolderFile->getId())."'");
						$GLOBALS['babWebStat']->addNewFile($rr['id_dgowner']);
						notifyFileAuthor(bab_translate("Your file has been accepted"),"", $oFolderFile->getAuthorId(), $oFolderFile->getName());

						$eventfiles = new bab_eventFmAfterFileUpload;
						$eventfiles->setFolderId($oFolderFile->getOwnerId());
						$eventfiles->addReference($oReference);
						bab_fireEvent($eventfiles);

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
			else if($bmodified)
			{
				/**
				 * Event used triggered after file update
				 */
				$eventfiles = new bab_eventFmAfterFileUpdate;
				$eventfiles->setFolderId($oFolderFile->getOwnerId());
				if ($bnotify) {
					$eventfiles->setUserOptionNotify('Y' === $bnotify);
				}
				$eventfiles->addReference($oReference);
				bab_fireEvent($eventfiles);

			}
		}
		return true;
	}

	// the file does not exists
	return false;
}




/**
 * Get file array and access rights on the file
 *
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
					if(bab_isAccessValid(BAB_FMMANAGERS_GROUPS_TBL, $oFmFolder->getId()) || bab_isAccessValid(BAB_FMUPDATE_GROUPS_TBL, $oFmFolder->getId()))
					{
						$bupdate = true;
						if('Y' === $oFmFolder->getVersioning() && 0 !== $oFolderFile->getFolderFileVersionId())
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
		'lockauthor' => $lockauthor			// versionning only
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
 * NYI @param	Y|N		$filename
 *
 * @return boolean
 */
function fm_commitFile($idf, $comment, $vermajor, $fmFile/*, $filename = 'N'*/)
{
	require_once dirname(__FILE__) . '/eventfm.php';
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


			$oFolderFile->setFolderFileVersionId(0);
			$oFolderFile->setModifiedDate(date("Y-m-d H:i:s"));
			$oFolderFile->setModifierId($GLOBALS['BAB_SESS_USERID']);
			$oFolderFile->setMajorVer($vmajor);
			$oFolderFile->setMinorVer($vminor);
			$oFolderFile->setCommentVer($comment);
			$oFolderFile->setStatusIndex($index_status);
			$oFolderFile->save();

			global $babDB;
			$req = "
				UPDATE ".BAB_FILES_TBL." set
					size='".$babDB->db_escape_string($size)."'
				WHERE
					id='".$babDB->db_escape_string($idf)."'";
			$babDB->db_query($req);

			require_once dirname(__FILE__) . '/reference.class.php';
			$oReference		= bab_Reference::makeReference('ovidentia', '', 'files', 'file', $idf);

			$event = new bab_eventFmAfterAddVersion;
			$event->setFolderId($oFolderFile->getOwnerId());
			$event->addReference($oReference);
			bab_fireEvent($event);
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
	 * If $sGr == 'Y', $iId is the identifier of the first collective folder.
	 * If $sGr == 'N', $iId is the user id.
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

		if(!empty($BAB_SESS_USERID) && $this->sGr != 'Y')
		{
			$this->iIdObject = (int) bab_rp('id', $BAB_SESS_USERID);
		}
		else
		{
			$this->iIdObject = (int) bab_rp('id', 0);

			// autodetect id object from path

			if (0 === $this->iIdObject && !empty($this->sPath))
			{
				BAB_FmFolderHelper::getInfoFromCollectivePath($this->sPath, $iIdRootFolder, $oFmFolder);
				$this->iIdObject = $iIdRootFolder;
			}
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

				bab_setCurrentUserDelegation(0);

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

		if (preg_match('#^(|.*[/\\\\])\.\.(|[/\\\\].*)$#', $this->sPath) !== 0) {
			$babBody->addError(bab_translate("Invalid path. Should not contain '..'"));
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


/**
 * Canonicalize the path given in parameter.
 *
 * @param string $sPath
 */
function canonicalizePath($sPath)
{
	return addEndSlash(removeEndSlashes($sPath));
}

/**
 *
 * @deprecated
 * @see canonicalizePath
 */
function canonizePath($sPath)
{
	return canonicalizePath($sPath);
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


function canUnzip($sPath)
{
	$oFileManagerEnv = getEnvObject();
	$sFullPath = $oFileManagerEnv->getRootFmPath() . $sPath;

	static $aPath = array();
	if(!array_key_exists($sFullPath, $aPath))
	{
		$aPath[$sFullPath] = haveRightOnParent($sPath, 'bab_fmunzip_groups');
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

		$sSrcPath = canonicalizePath($sSrcPath);

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
			$sTrgPath = canonicalizePath($sTrgPath);

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


	//$oFileMgrEnv = getEnvObject();
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
		return isUserFolder($sPath, $oFileMgrEnv);
	}
	else
	{
		return false;
	}
}

/* A utiliser seulement pour les fonction haveRightxxx quand on est � la racine*/
function haveAdministratorRight()
{
	if(0 === bab_getCurrentUserDelegation())
	{
		return bab_isUserAdministrator();
	}
	else
	{
		$dgAdmGroups = bab_getDgAdmGroups();
		if($dgAdmGroups === null){
			$dgAdmGroups = array();
		}
		return array_key_exists(bab_getCurrentUserDelegation(), array_flip($dgAdmGroups));
	}
}


function isUserFolder($sPath, $oFileManagerEnv = null)
{
	global $BAB_SESS_USERID;

	if (null === $oFileManagerEnv)
	{
		$oFileManagerEnv = getEnvObject();
	}

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


/**
 * return accessibles ACL id object
 * @param string $table
 * @param bool $ignore_hidden_folders
 * @return array
 */
function bab_getUserFmIdObjects($table, $ignore_hidden_folders)
{
	static $ignore = null;

	if (null === $ignore) {

		$ignore = array();

		if ($ignore_hidden_folders) {
			global $babDB;

			$res = $babDB->db_query('SELECT id FROM '.BAB_FM_FOLDERS_TBL.' WHERE bhide='.$babDB->quote('Y'));
			while($arr = $babDB->db_fetch_assoc($res)) {
				$id = (int) $arr['id'];
				$ignore[$id] = $id;
			}
		}
	}


	$arr = bab_getUserIdObjects($table);


	foreach($ignore as $id_object) {
		if (isset($arr[$id_object])) {
			unset($arr[$id_object]);
		}
	}

	return $arr;
}




/**
 * Test if the user have right on one collective folder
 * @param	bool	$ignore_hidden_folders	 hidden folders are ignored only for download right
 * @return 	bool
 */
function userHaveRightOnCollectiveFolder($ignore_hidden_folders = false)
{
	static $bHaveRightOnCollectiveFolder = null;

	if(is_null($bHaveRightOnCollectiveFolder))
	{
		$bHaveRightOnCollectiveFolder = false;



		$aIdObject = bab_getUserIdObjects(BAB_FMUPLOAD_GROUPS_TBL);
		if(is_array($aIdObject) && count($aIdObject) > 0)
		{
			$bHaveRightOnCollectiveFolder = true;
			return true;
		}

		$aIdObject = bab_getUserFmIdObjects(BAB_FMDOWNLOAD_GROUPS_TBL, $ignore_hidden_folders);
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

		
		$dgAdmGroups = bab_getDgAdmGroups();
		
		if(is_array($dgAdmGroups) && count($dgAdmGroups) > 0)
		{
			foreach($dgAdmGroups as $iKey => $iIdDelegation)
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


	static $oFolderFileSet	= null;
	static $oFolderSet		= null;
	static $aAccessFolders	= array();


	$oFmEnv 			= new BAB_FileManagerEnv;
	$oFolderFileSet		= bab_getInstance('BAB_FolderFileSet');
	$oFolderSet			= bab_getInstance('BAB_FmFolderSet');


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

	$iIdObject = 0;
	$oFolder = $oFolderSet->get($oCriteria);
	if($oFolder instanceof BAB_FmFolder)
	{
		$iIdObject = $oFolder->getId();
	}

	if ('N' === $sGr)
	{
		$iIdObject = $GLOBALS['BAB_SESS_USERID'];
	}

	$oFmEnv->sGr		= $sGr;
	$oFmEnv->sPath		= BAB_PathUtil::removeEndSlashes($sPathName);
	$oFmEnv->iIdObject	= $iIdObject;
	$oFmEnv->init();


	$aAccessFolders[$uid] = canDownload($sPathName, $oFmEnv);
	return $aAccessFolders[$uid];
}

