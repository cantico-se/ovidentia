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
include_once $GLOBALS['babInstallPath'] . 'utilit/fileincl.php';


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

		function temp()
			{
			global $babBody, $babDB;
			$this->name = bab_translate("Name");
			$this->description = bab_translate("Description");
			$this->moderation = bab_translate("Approbation schema");
			$this->notification = bab_translate("Notification");
			$this->version = bab_translate("Versioning");
			$this->yes = bab_translate("Yes");
			$this->no = bab_translate("No");
			$this->add = bab_translate("Add");
			$this->active = bab_translate("Active");
			$this->none = bab_translate("None");
			$this->display = bab_translate("Visible in file manager?");
			$this->autoapprobationtxt = bab_translate("Automatically approve author if he belongs to approbation schema");
			$this->addtags_txt = bab_translate("Users can add new tags");
			
			
			$this->sares = $babDB->db_query("select * from ".BAB_FLOW_APPROVERS_TBL." where id_dgowner='".$babBody->currentAdmGroup."' order by name asc");
			if( !$this->sares )
				$this->sacount = 0;
			else
				$this->sacount = $babDB->db_num_rows($this->sares);
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

		function temp()
		{
			global $babBody, $babDB;
			$this->fullname = bab_translate("Folders");
			$this->notify = bab_translate("Notify");
			$this->version = bab_translate("Versioning");
			$this->access = bab_translate("Access");
			$this->active = bab_translate("Enabled");
			$this->notify = bab_translate("Notify");
			$this->modify = bab_translate("Update");
			$this->display = bab_translate("Hidden");
			$this->urlrightsname = bab_translate("Rights");
			$this->uncheckall = bab_translate("Uncheck all");
			$this->checkall = bab_translate("Check all");
			$this->add = bab_translate("Add");

			global $babBody;
			$this->oFmFolderSet = new BAB_FmFolderSet();
			
			$oRelativePath =& $this->oFmFolderSet->aField['sRelativePath']; 
			$oIdDgOwner =& $this->oFmFolderSet->aField['iIdDgOwner']; 
			
			$oCriteria = $oRelativePath->in($babDB->db_escape_like(''));
			$oCriteria = $oCriteria->_and($oIdDgOwner->in($babBody->currentAdmGroup));
			$aOrder = array('sName' => 'ASC');
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
						$this->fnotify = 'checked';
					}
					
					if('Y' === $oFmFolder->getActive())
					{
						$this->factive = 'checked';
					}
					
					if('Y' === $oFmFolder->getVersioning())
					{
						$this->fversion = 'checked';
					}
					
					if('Y' === $oFmFolder->getHide())
					{
						$this->fbhide = 'checked';
					}
					
					$this->fid = $oFmFolder->getId();
					$this->url = $GLOBALS['babUrlScript'] . '?tg=admfm&idx=modify&fid=' . $this->fid;
					$this->urlname = $oFmFolder->getName();
					$this->urlrights = $GLOBALS['babUrlScript'] . '?tg=admfm&idx=rights&fid=' . $this->fid;
					$this->access = bab_translate("Access");
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
	$babBody->babecho(	bab_printTemplate($temp, "admfms.html", "folderlist"));
	return $temp->count();
}

function saveFolder($fname, $active, $said, $notification, $version, $bhide, $bautoapp, $baddtags)
{
	global $babBody, $babDB;
	if(empty($fname))
	{
		$babBody->msgerror = bab_translate("ERROR: You must provide a name !!");
		return false;
	}
	
	include_once $GLOBALS['babInstallPath'] . 'utilit/delegincl.php';
	bab_setCurrentUserDelegation($babBody->currentAdmGroup);
	$oFileManagerEnv =& getEnvObject();
	if(!$oFileManagerEnv->pathValid())
	{
		return false;
	}
	
	global $babBody;
	
	$oFmFolderSet = new BAB_FmFolderSet();
	
	$oName =& $oFmFolderSet->aField['sName']; 
	$oIdDgOwner =& $oFmFolderSet->aField['iIdDgOwner']; 
	
	$sName = replaceInvalidFolderNameChar($fname);
	
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
	$oFmFolderSet = new BAB_FmFolderSet();
	
	$oIdDgOwner =& $oFmFolderSet->aField['iIdDgOwner'];
	$oId		=& $oFmFolderSet->aField['iId'];
	
	$oCriteria = $oIdDgOwner->in($babBody->currentAdmGroup);
	$oCriteria = $oCriteria->_and($oId->in($versions));
	$oFmFolderSet = $oFmFolderSet->select($oCriteria);

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


/* main */
if( !$babBody->isSuperAdmin && $babBody->currentDGGroup['filemanager'] != 'Y')
{
	$babBody->msgerror = bab_translate("Access denied");
	return;
}

//bab_debug(__FILE__);
//bab_debug($_POST);

if( !isset($idx))
	$idx = "list";

if( isset($add) && $add == "addfolder")
	if (!saveFolder($fname, $active, $said, $notification, $version, $bhide, $bautoapp, $baddtags))
		$idx = "addf";

if( isset($update) && $update == "folders")
	{
	if(!isset($notifies)) { $notifies= array();}
	if(!isset($actives)) { $actives= array();}
	if(!isset($versions)) { $versions= array();}
	if(!isset($bhides)) { $bhides= array();}
	updateFolders($notifies, $actives, $versions, $bhides);
	}

switch($idx)
	{
	case "addf":
		$babBody->title = bab_translate("Add a new folder");
		$babBody->addItemMenu("list", bab_translate("Folders"), $GLOBALS['babUrlScript']."?tg=admfms&idx=list");
		$babBody->addItemMenu("addf", bab_translate("Add"), $GLOBALS['babUrlScript']."?tg=admfms&idx=addf");
		addFolder();
		break;

	default:
	case "list":
		$babBody->title = bab_translate("File manager");
		if( listFolders() > 0 )
			{
			$babBody->addItemMenu("list", bab_translate("Folders"), $GLOBALS['babUrlScript']."?tg=admfms&idx=list");
			}

		//$babBody->addItemMenu("addf", bab_translate("Add"), $GLOBALS['babUrlScript']."?tg=admfms&idx=addf");
		break;
	}
$babBody->setCurrentItemMenu($idx);

?>