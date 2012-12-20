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
require_once dirname(__FILE__).'/../utilit/registerglobals.php';
include_once $GLOBALS['babInstallPath'] . 'utilit/fileincl.php';

function DisplayFileManager()
{
	global $babBody;
	
	class DisplayFileManager_Class
	{
		function DisplayFileManager_Class()
		{
			global $babDB;
			$this->infotxt = bab_translate("Specify which fields will be displayed when browsing files");
			$this->listftxt = '---- '.bab_translate("Fields").' ----';
			$this->listdftxt = '---- '.bab_translate("Fields to display").' ----';
			$this->moveup = bab_translate("Move Up");
			$this->movedown = bab_translate("Move Down");
			$this->update = bab_translate("Update");
			$this->thelp = bab_translate("Column hits will be visible only for users who can manage the folder");
			$this->resfd = $babDB->db_query("select * from ".BAB_FM_HEADERS_TBL." where fmh_order != '0' order by fmh_order asc");
			$this->resf = $babDB->db_query("select * from ".BAB_FM_HEADERS_TBL." where fmh_order = '0' order by fmh_description asc");
			$this->countf = $babDB->db_num_rows($this->resf);
			$this->countfd = $babDB->db_num_rows($this->resfd);
		}
		function getnextf()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->countf)
				{
				$arr = $babDB->db_fetch_array($this->resf);
				$this->fid = $arr['id'];
				$this->fieldval = bab_translate($arr['fmh_description']);
				$i++;
				return true;
				}
			return false;
			}

		function getnextdf()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->countfd)
				{
				$arr = $babDB->db_fetch_array($this->resfd);
				$this->fid = $arr['id'];
				$this->fid = $arr['id'];
				$this->fieldval = bab_translate($arr['fmh_description']);
				$i++;
				return true;
				}
			return false;
			}
	}
	
	$dfm = new DisplayFileManager_Class();
	$babBody->babecho(bab_printTemplate($dfm,"admfms.html", "displayfm"));
}


function addFolder()
{
	global $babBody;
	
	class temp
	{
		var $name;
		var $description;
		var $moderation;
		var $notification;
		var $usersbrowurl;
		var $yes;
		var $no;
		var $add;
		var $active;
		var $none;
		var $sares;
		var $sacount;
		var $saname;
		var $said;
		var $version;
		var $orderm;

		function temp()
		{
			global $babBody, $babDB;
			
			$this->name					= bab_translate("Name");
			$this->description			= bab_translate("Description");
			$this->moderation			= bab_translate("Approbation schema");
			$this->notification			= bab_translate("Notification");
			$this->version				= bab_translate("Versioning");
			$this->yes					= bab_translate("Yes");
			$this->no					= bab_translate("No");
			$this->add					= bab_translate("Add");
			$this->active				= bab_translate("Active");
			$this->none					= bab_translate("None");
			$this->display				= bab_translate("Visible in file manager?");
			$this->autoapprobationtxt	= bab_translate("Automatically approve author if he belongs to approbation schema");
			$this->addtags_txt			= bab_translate("Users can add new tags");
			$this->orderm				= bab_translate("Manual order");
	
			$this->downloadscappingtxt	= bab_translate("Manage maximum number of downloads per file");
			$this->maxdownloadstxt		= bab_translate("Default value");
			$this->downloadhistorytxt	= bab_translate("Manage downloads history");

			$this->thelp1				= bab_translate("Deactivate a folder allows to archive it: it and its contents will not be visible in the file manager");
			$this->thelp2				= bab_translate("Activate the management of the versions allows to keep a history of all the modifications brought to the same file");
			$this->thelp3				= bab_translate("If the folder is hidden, it will not be visible in the file manager, its contents remain accessible except the file manager (link since an article, a file OVML...)");
			$this->thelp4				= bab_translate("If this option is activated, the keywords of files will be seized freely by their authors and automatically added in the thesaurus. If the option is deactivated, only the keywords seized by the managers of the thesaurus can be selected by the authors of files");
			$this->thelp5				= bab_translate("Allows to specify how many times a file can be downloaded. Any user downloading the file adds one hit to this counter. Once the counter reaches the set value, the file cannot be downloaded anymore.");
			$this->thelp6				= bab_translate("Sets the default value that appears in the upload form. The upolading user can change this value while filling the upload form.");
			$this->thelp7				= bab_translate("Allow to record which user has downloaded the files included in this folder. Downloads by anonymous users are counted as done by one single 'anonymous user'.");
			$this->thelp8				= bab_translate("Allows the user granted with management rights on this folder to order manually the files. Subfolders are not affected by this option.");
			
			$this->sares = $babDB->db_query("select * from ".BAB_FLOW_APPROVERS_TBL." where id_dgowner='".$babBody->currentAdmGroup."' order by name asc");
			if(!$this->sares)
			{
				$this->sacount = 0;
			}
			else
			{
				$this->sacount = $babDB->db_num_rows($this->sares);
			}
		}

		function getnextschapp()
		{
			global $babDB;
			static $i = 0;
			if( $i < $this->sacount)
			{
				$arr = $babDB->db_fetch_array($this->sares);
				$this->saname = $arr['name'];
				$this->said = $arr['id'];
				$i++;
				return true;
			}
			else
			{
				return false;
			}
		}
	}

	$temp = new temp();
	$babBody->babecho(bab_printTemplate($temp,"admfms.html", "foldercreate"));
}


function listFolders()
{
	global $babBody;
	
	class temp
	{
		var $fullname;
		var $notify;
		var $access;
		var $modify;
		var $uncheckall;
		var $checkall;
		var $fnotify;
		var $factive;
		var $fversion;
		var $fid;
		var $url;
		var $urlname;
		var $managername;
		var $urluplo;
		var $urldown;
		var $urlupda;
		var $urluploname;
		var $urldownname;
		var $urlupdaname;
		var $version;
		var $altbg = true;
		var $add = '';
		var $oFmFolderSet = null;
		var $sAddUrl = '';
		
		function temp()
		{
			global $babBody, $babDB;
			
			$this->fullname			= bab_translate("Folders");
			$this->notify			= bab_translate("Notify");
			$this->version			= bab_translate("Versioning");
			$this->access			= bab_translate("Access");
			$this->active			= bab_translate("Enabled");
			$this->notify			= bab_translate("Notify");
			$this->modify			= bab_translate("Update");
			$this->display			= bab_translate("Hidden");
			$this->urlrightsname	= bab_translate("Rights");
			$this->uncheckall		= bab_translate("Uncheck all");
			$this->checkall			= bab_translate("Check all");
			$this->add				= bab_translate("Add");
			$this->sAddUrl			= bab_toHtml('?tg=admfms&idx=addf');
			
			$this->oFmFolderSet	= new BAB_FmFolderSet();
			$oRelativePath		= $this->oFmFolderSet->aField['sRelativePath']; 
			$oIdDgOwner			= $this->oFmFolderSet->aField['iIdDgOwner']; 
			
			$oCriteria	= $oRelativePath->in($babDB->db_escape_like(''));
			$oCriteria	= $oCriteria->_and($oIdDgOwner->in($babBody->currentAdmGroup));
			$aOrder		= array('sName' => 'ASC');
			
			$this->oFmFolderSet->select($oCriteria, $aOrder);
		}

		function getnext()
		{
			$this->fnotify = '';
			$this->factive = '';
			$this->fversion = '';
			$this->fbhide = '';
			
			if(!is_null($this->oFmFolderSet))
			{
				$oFmFolder = $this->oFmFolderSet->next();
				if(!is_null($oFmFolder))
				{
					if('Y' === $oFmFolder->getFileNotify())
					{
						$this->fnotify = 'checked="checked"';
					}
					
					if('Y' === $oFmFolder->getActive())
					{
						$this->factive = 'checked="checked"';
					}
					
					if('Y' === $oFmFolder->getVersioning())
					{
						$this->fversion = 'checked="checked"';
					}
					
					if('Y' === $oFmFolder->getHide())
					{
						$this->fbhide = 'checked="checked"';
					}
					
					$this->fid			= $oFmFolder->getId();
					$this->url			= bab_toHtml($GLOBALS['babUrlScript'] . '?tg=admfm&idx=modify&fid=' . $this->fid);
					$this->urlname		= $oFmFolder->getName();
					$this->urlrights	= bab_toHtml($GLOBALS['babUrlScript'] . '?tg=admfm&idx=rights&fid=' . $this->fid);
					$this->access		= bab_translate("Access");
					return true;
				}
			}
			return false;
		}
		
		function count()
		{
			if(!is_null($this->oFmFolderSet))
			{
				return $this->oFmFolderSet->count();
			}
			return 0;			
		}
	}

	$temp = new temp();
	$babBody->babecho(bab_printTemplate($temp, "admfms.html", "folderlist"));
	return $temp->count();
}


function saveFolder($fname, $active, $said, $notification, $version, $bhide, $bautoapp, $baddtags, $bdownloadscapping, $maxdownloads, $bdownloadhistory, $orderm)
{
	global $babBody, $babDB;
	if(empty($fname))
	{
		$babBody->msgerror = bab_translate("ERROR: You must provide a name !!");
		return false;
	}
	
	include_once $GLOBALS['babInstallPath'] . 'utilit/delegincl.php';
	bab_setCurrentUserDelegation($babBody->currentAdmGroup);
	$oFileManagerEnv = getEnvObject();
	if(!$oFileManagerEnv->pathValid())
	{
		return false;
	}
	
	global $babBody;
	
	$oFmFolderSet	= new BAB_FmFolderSet();
	$oName			= $oFmFolderSet->aField['sName']; 
	$oIdDgOwner		= $oFmFolderSet->aField['iIdDgOwner']; 
	
	$sName = replaceInvalidFolderNameChar($fname);
			
	if(!isStringSupportedByFileSystem($sName))
	{
		$babBody->addError(bab_translate("The directory name contains characters not supported by the file system"));
		return false;
	}
			
	$oCriteria = $oName->in($sName);
	$oCriteria = $oCriteria->_and($oIdDgOwner->in($babBody->currentAdmGroup));
	$oFmFolder = $oFmFolderSet->get($oCriteria);
	if(is_null($oFmFolder))
	{
		if(empty($said))
		{
			$said = 0;
		}

		$sFullPathName = BAB_FileManagerEnv::getCollectivePath($babBody->currentAdmGroup) . $sName;

		//bab_debug($sFullPathName);
		
		if(BAB_FmFolderHelper::createDirectory($sFullPathName))
		{
			$oFmFolder = new BAB_FmFolder();
			$oFmFolder->setApprobationSchemeId($said);
			$oFmFolder->setDelegationOwnerId($babBody->currentAdmGroup);
			$oFmFolder->setName($sName);
			$oFmFolder->setRelativePath('');
			$oFmFolder->setFileNotify($notification);
			$oFmFolder->setActive($active);
			$oFmFolder->setAddTags($baddtags);
			$oFmFolder->setVersioning($version);
			$oFmFolder->setHide($bhide);
			$oFmFolder->setAutoApprobation($bautoapp);

			$oFmFolder->setDownloadsCapping($bdownloadscapping);
			$oFmFolder->setMaxDownloads($maxdownloads);
			$oFmFolder->setDownloadHistory($bdownloadhistory);
			$oFmFolder->setManualOrder($orderm);

			return $oFmFolder->save();
		}
		return false;
	}
	else 
	{
		$babBody->msgerror = bab_translate("This folder already exists");
		return false;
	}
}

function updateFolders($notifies, $actives, $versions, $bhides)
{
	global $babBody;
	
	$oFmFolderSet	= new BAB_FmFolderSet();
	$oIdDgOwner		= $oFmFolderSet->aField['iIdDgOwner'];
	$oRelativePath	= $oFmFolderSet->aField['sRelativePath'];

	$oCriteria = $oIdDgOwner->in($babBody->currentAdmGroup);
	$oCriteria = $oCriteria->_and($oRelativePath->in(''));
	$oFmFolderSet->select($oCriteria);
	
	while(null !== ($oFmFolder = $oFmFolderSet->next()))
	{
		if(is_array($notifies) && count($notifies) > 0 && in_array($oFmFolder->getId(), $notifies))
		{
			$oFmFolder->setFileNotify('Y');	
		}
		else 
		{
			$oFmFolder->setFileNotify('N');	
		}
		
		if(is_array($actives) && count($actives) > 0 && in_array($oFmFolder->getId(), $actives))
		{
			$oFmFolder->setActive('Y');	
		}
		else 
		{
			$oFmFolder->setActive('N');	
		}
		
		if(is_array($versions) && count($versions) > 0 && in_array($oFmFolder->getId(), $versions))
		{
			$oFmFolder->setVersioning('Y');	
		}
		else 
		{
			$oFmFolder->setVersioning('N');	
		}
		
		if(is_array($bhides) && count($bhides) > 0 && in_array($oFmFolder->getId(), $bhides))
		{
			$oFmFolder->setHide('Y');	
		}
		else 
		{
			$oFmFolder->setHide('N');	
		}
		$oFmFolder->save();
	}
	
	bab_siteMap::clearAll();
	header('location:'.$GLOBALS['babUrlScript']."?tg=admfms&idx=list");
}


function updateDisplayFileManager()
{
	global $babDB;

	$babDB->db_query("update ".BAB_FM_HEADERS_TBL." set fmh_order='0'");
	for($i=0; $i < count($_POST['listfd']); $i++)
		{
			$babDB->db_query("update ".BAB_FM_HEADERS_TBL." set fmh_order='".($i+1)."' where id=".$babDB->quote($_POST['listfd'][$i]));
		}
}

function bab_selectPurgeFolder()
{
	global $babBody;
	
	class temp
	{
		var $name;
		var $path;
		var $select;
		var $delegation;
		var $altbg = true;
		var $add = '';
		var $oFmFolderSet = null;
		var $sAddUrl = '';
	
		function temp()
		{
			global $babDB;
			
			$this->name		= bab_translate("Folders");
			$this->path		= bab_translate("Path");
			$this->select	= bab_translate("Select");
			$this->delegation	= bab_translate("Delegation");
				
			$sql = "SELECT
						fm.id as fmid,
						fm.folder as folder,
						fm.sRelativePath as sRelativePath,
						fm.id_dgowner as id_dgowner,
						dgr.id as dgrid,
						dgr.name as name
					FROM ".BAB_FM_FOLDERS_TBL." as fm
					
					LEFT JOIN ".BAB_DG_GROUPS_TBL." as dgr
					ON fm.id_dgowner = dgr.id
					
					ORDER BY name ASC, folder ASC, sRelativePath ASC";
			$this->res = $babDB->db_query($sql);
			
			$fid = bab_gp('folder', '');
			$fid = explode('.', $fid);
			$this->fid = array();
			foreach($fid as $id){
				$this->fid[$id] = $id;
			}
		}
	
		function getnext()
		{
			global $babDB;
			static $end = true;
			
			if($end){
				$end = false;
				
				$this->foldername = bab_translate('All personnal folders');
				$this->folderpath = '';
				$this->dgowner = '';
				$this->folderid = '-1';
				if(isset($this->fid[$this->folderid])){
					$this->checked = ' checked="checked" ';
				}else{
					$this->checked = '';
				}
				
				return true;
			}
							
			while($this->res && $arr = $babDB->db_fetch_array($this->res))
			{
				$this->foldername = $arr['folder'];
				$this->folderpath = $arr['sRelativePath'];
				if($arr['id_dgowner'] == 0){
					$this->dgowner = bab_translate('All site');
				}else{
					$this->dgowner = $arr['name'];
				}
				$this->folderid = $arr['fmid'];
				
				if(isset($this->fid[$this->folderid])){
					$this->checked = ' checked="checked" ';
				}else{
					$this->checked = '';
				}
				
				return true;
			}
			return false;
		}
	}
	
	$temp = new temp();
	$babBody->babecho(bab_printTemplate($temp, "admfms.html", "purgetrashs"));
}

function bab_actionPurgeFolder(){
	global $babBody;
	
	class temp
	{
		var $name;
		var $path;
		var $select;
		var $delegation;
		var $altbg = true;
		var $add = '';
		var $oFmFolderSet = null;
		var $sAddUrl = '';
	
		function temp()
		{
			global $babDB;
	
			$this->notify			= bab_translate("Notify all managers of public folder and/or user withe personnal folder.");
			$this->purge			= bab_translate("Purge all trashs from all selected folders.");
			$this->notify_confirm	= bab_translate("Proceed to the notification?");
			$this->purge_confirm	= bab_translate("Proceed to the purge?");
			$this->name				= bab_translate("Folders");
			$this->path				= bab_translate("Path");
			$this->select			= bab_translate("Select");
			$this->delegation		= bab_translate("Delegation");
			
			$this->personnal = false;
			
			$arrayIds = bab_pp('selects', array());
			$arrayIdReal = array();
			
			$ids = '';
			foreach($arrayIds as $k => $v){
				if($ids == ''){
					$ids = $k;
				}else{
					$ids.= '.'.$k;
				}
				$arrayIdReal[] = $k;
				if($k == -1){
					$this->personnal = true;
				}
			}
			
			$this->notifyurl		= bab_toHtml($GLOBALS['babUrlScript'] . '?tg=admfms&idx=purgefm&action=notify&folder=' . $ids);
			$this->purgeurl		= bab_toHtml($GLOBALS['babUrlScript'] . '?tg=admfms&idx=purgefm&action=purge&folder=' . $ids);
			
			$sql = "SELECT
				fm.id as fmid,
				fm.folder as folder,
				fm.sRelativePath as sRelativePath,
				fm.id_dgowner as id_dgowner,
				dgr.id as dgrid,
				dgr.name as name
			FROM ".BAB_FM_FOLDERS_TBL." as fm
			
			LEFT JOIN ".BAB_DG_GROUPS_TBL." as dgr
			ON fm.id_dgowner = dgr.id
			
			WHERE fm.id IN(".$babDB->quote($arrayIdReal).")
			
			ORDER BY name ASC, folder ASC, sRelativePath ASC";
			$this->res = $babDB->db_query($sql);
		}
	
		function getnext()
		{
			global $babDB;
			static $end = true;
			
			if($end && $this->personnal){
				$end = false;
				$this->foldername = bab_translate('All personnal folders');
				$this->folderpath = '';
				$this->dgowner = '';
				
				return true;
			}

			$end = false;
			
			while($this->res && $arr = $babDB->db_fetch_array($this->res))
			{
				$this->foldername = $arr['folder'];
				$this->folderpath = $arr['sRelativePath'];
				if($arr['id_dgowner'] == 0){
					$this->dgowner = bab_translate('All site');
				}else{
					$this->dgowner = $arr['name'];
				}
				return true;
			}
			return false;
		}
	}
	
	$temp = new temp();
	$babBody->babecho(bab_printTemplate($temp, "admfms.html", "actionpurgetrashs"));
}

function bab_notifyPurgeTrashs()
{
	require_once $GLOBALS['babInstallPath'] . 'utilit/mailincl.php';
	require_once $GLOBALS['babInstallPath'] . 'admin/acl.php';
	
	global $babDB;
	
	$fid = bab_gp('folder', '');
	$fid = explode('.', $fid);
	
	$sql = "SELECT
			fm.id as id,
			fm.folder as folder,
			fm.sRelativePath as sRelativePath,
			fm.id_dgowner as id_dgowner,
			dgr.id as dgrid,
			dgr.name as name
		FROM ".BAB_FM_FOLDERS_TBL." as fm
		
		LEFT JOIN ".BAB_DG_GROUPS_TBL." as dgr
		ON fm.id_dgowner = dgr.id
		
		ORDER BY name ASC, folder ASC, sRelativePath ASC";
	
	$res = $babDB->db_query($sql);
	$folders = array();
	while($res && $arr = $babDB->db_fetch_assoc($res))
	{
		$folders[$arr['id']] = $arr;
		if($arr['dgrid'] == 0){
			$folders[$arr['id']]['name'] = bab_translate('All site');
		}
	}

	$usersToNotifyPublic = array();
	$usersToNotifyPrivate = array();
	foreach($fid as $id){
		if($id == '-1'){
			$sql = "SELECT * FROM ".BAB_GROUPS_TBL." WHERE id NOT IN('0','2') AND ustorage = 'Y'";
			$res = $babDB->db_query($sql);
			$groupsId = array();
			
			while($res && $gp = $babDB->db_fetch_assoc($res))
			{
				$groupsId[] = $gp['id'];
			}
			
			$usersToNotifyPrivate = bab_getGroupsMembers($groupsId);
			if(!$usersToNotifyPrivate){
				$usersToNotifyPrivate = array();
			}
		}else{
			$tempArray = aclGetAccessUsers(BAB_FMMANAGERS_GROUPS_TBL, $id);
			foreach($tempArray as $k => $v){
				if(!isset($usersToNotifyPublic[$k])){
					$usersToNotifyPublic[$k] = $v;
				}
				$usersToNotifyPublic[$k]['folders'][] = '(' .$folders[$id]['name']. ') ' . $folders[$id]['sRelativePath'].$folders[$id]['folder'];
			}
		}
	}
	bab_debug($usersToNotifyPublic);
	foreach($usersToNotifyPublic as $user){//Public folder
		$babMail = bab_mail();
		$babMail->mailFrom($GLOBALS['babAdminEmail'], $GLOBALS['babAdminName']);
		$babMail->mailBcc($user['email'], $user['name']);
		
		$folderStr = '';
		foreach($user['folders'] as $folder){
			$folderStr.="\r\n".$folder;
		}
		
		$babMail->mailSubject(bab_translate('Purge trashs'));
		$babMail->mailBody(
			sprintf(
				bab_translate('_MAILPURGEPUBLICTRASHS_'),
				$folderStr
			)
		);
		$babMail->send();
	}
	
	if(!empty($usersToNotifyPrivate)){//User folder
		$babMail = bab_mail();
		$babMail->mailFrom($GLOBALS['babAdminEmail'], $GLOBALS['babAdminName']);
		foreach($usersToNotifyPrivate as $user){
			$babMail->mailBcc($user['email'], $user['name']);
		}
		
		$babMail->mailSubject(bab_translate('Purge trashs'));
		$babMail->mailBody(bab_translate('_MAILPURGEUSERTRASHS_'));
		$babMail->send();
	}
}

function bab_purgeTrashs()
{

	require_once $GLOBALS['babInstallPath'] . 'utilit/fmset.class.php';

	global $babDB;

	$fid = bab_gp('folder', '');
	$fid = explode('.', $fid);

	$filesID = array();
	if(in_array('-1', $fid)){
		$sql = "SELECT * FROM ".BAB_FILES_TBL."
				WHERE bgroup = 'N' AND state = 'D' ";
		
		$res = $babDB->db_query($sql);
		$folders = array();
		while($res && $arr = $babDB->db_fetch_assoc($res))
		{
			$filesID[] = $arr['id'];
		}
	}
	
	if(count($fid) > 1 || (count($fid) > 0 && empty($filesID))){
		$sql = "SELECT * FROM ".BAB_FILES_TBL."
				WHERE bgroup = 'Y' AND state = 'D' AND id_owner IN (".$babDB->quote($fid).")";
		$res = $babDB->db_query($sql);
		$folders = array();
		while($res && $arr = $babDB->db_fetch_assoc($res))
		{
			$filesID[] = $arr['id'];
		}
	}
	
	if(!empty($filesID)){
		$oFolderFileSet = new BAB_FolderFileSet();
		$oId =& $oFolderFileSet->aField['iId'];
		$oFolderFileSet->remove($oId->in($filesID));
	}
}


/* main */
if(!$babBody->isSuperAdmin && $babBody->currentDGGroup['filemanager'] != 'Y')
{
	$babBody->msgerror = bab_translate("Access denied");
	return;
}

//bab_debug(__FILE__);
//bab_debug($_POST);

if(!isset($idx))
{
	$idx = 'list';
}


if(isset($add) && $add == 'addfolder')
{
	if(!saveFolder($fname, $active, $said, $notification, $version, $bhide, $bautoapp, $baddtags, $bdownloadscapping, $maxdownloads, $bdownloadhistory, $orderm))
	{
		$idx = 'addf';
	}
}


if(isset($update) && $update == 'folders')
{
	if(!isset($notifies)) { $notifies= array();}
	if(!isset($actives)) { $actives= array();}
	if(!isset($versions)) { $versions= array();}
	if(!isset($bhides)) { $bhides= array();}
	updateFolders($notifies, $actives, $versions, $bhides);
}
elseif(isset($update) && $update == 'displayfm')
{
	updateDisplayFileManager();
}

$action = bab_gp('action', '');
if($action == 'notify')
{
	bab_notifyPurgeTrashs();
	$babBody->msgerror = bab_translate("Notification done");
}
elseif($action == 'purge')
{
	bab_purgeTrashs();
	$babBody->msgerror = bab_translate("Purge done");
}

switch($idx)
{
	case 'purgefm':
		if(!$babBody->isSuperAdmin)
		{
			$babBody->msgerror = bab_translate("Access denied");
			return;
		}
		$babBody->title = bab_translate("Purge trashs");
		$babBody->addItemMenu('list', bab_translate("Folders"), $GLOBALS['babUrlScript'].'?tg=admfms&idx=list');
		$babBody->addItemMenu('purgefm', bab_translate("Purge trashs"), $GLOBALS['babUrlScript'].'?tg=admfms&idx=purgefm');
		bab_selectPurgeFolder();
		break;
		
	case 'actionpurgefm':
		if(!$babBody->isSuperAdmin)
		{
			$babBody->msgerror = bab_translate("Access denied");
			return;
		}
		$babBody->title = bab_translate("Purge trashs");
		$babBody->addItemMenu('list', bab_translate("Folders"), $GLOBALS['babUrlScript'].'?tg=admfms&idx=list');
		$babBody->addItemMenu('actionpurgefm', bab_translate("Purge trashs"), $GLOBALS['babUrlScript'].'?tg=admfms&idx=purgefm');
		bab_actionPurgeFolder();
		break;
		
	case 'addf':
		$babBody->title = bab_translate("Add a new folder");
		$babBody->addItemMenu('list', bab_translate("Folders"), $GLOBALS['babUrlScript'].'?tg=admfms&idx=list');
		$babBody->addItemMenu('addf', bab_translate("Add"), $GLOBALS['babUrlScript'].'?tg=admfms&idx=addf');
		addFolder();
		break;

	case 'dispfm':
		$babBody->title = bab_translate("File manager");
		$babBody->addItemMenu('list', bab_translate("Folders"), $GLOBALS['babUrlScript'].'?tg=admfms&idx=list');
		$babBody->addItemMenu('dispfm', bab_translate("Display"), $GLOBALS['babUrlScript'].'?tg=admfms&idx=dispfm');
		DisplayFileManager();
		break;
	
	default:
	case 'list':
		$babBody->title = bab_translate("File manager");
		if(listFolders() > 0)
		{
			$babBody->addItemMenu('list', bab_translate("Folders"), $GLOBALS['babUrlScript'].'?tg=admfms&idx=list');
			$babBody->addItemMenu('addf', bab_translate("Add"), $GLOBALS['babUrlScript'].'?tg=admfms&idx=addf');
			$babBody->addItemMenu('dispfm', bab_translate("Display"), $GLOBALS['babUrlScript'].'?tg=admfms&idx=dispfm');
			$babBody->addItemMenu('purgefm', bab_translate("Purge trashs"), $GLOBALS['babUrlScript'].'?tg=admfms&idx=purgefm');
		}
		break;
}
$babBody->setCurrentItemMenu($idx);
bab_siteMap::setPosition('bab','AdminFm');
?>
