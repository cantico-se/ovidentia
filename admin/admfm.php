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
include_once $GLOBALS['babInstallPath'] . 'admin/acl.php';
include_once $GLOBALS['babInstallPath'] . 'utilit/fileincl.php';


function modifyFolder($fid)
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
		var $del;
		var $active;
		var $fid;
		var $folderval;
		var $said;
		var $manager;
		var $none;
		var $yactsel;
		var $nactsel;
		var $ynfsel;
		var $safm;
		var $sares;
		var $sacount;
		var $version;
		var $yversel;
		var $nversel;
		var $orderm;
		var $boolorderm;

		function temp($fid)
		{
			global $babDB, $babBody;
			
			$this->name					= bab_translate("Name");
			$this->description			= bab_translate("Description");
			$this->moderation			= bab_translate("Approbation schema");
			$this->notification			= bab_translate("Notification");
			$this->version				= bab_translate("Versioning");
			$this->yes					= bab_translate("Yes");
			$this->no					= bab_translate("No");
			$this->add					= bab_translate("Update");
			$this->del					= bab_translate("Delete");
			$this->active				= bab_translate("Active");
			$this->display				= bab_translate("Visible in file manager?");
			$this->addtags_txt			= bab_translate("Users can add new tags");
			$this->autoapprobationtxt	= bab_translate("Automatically approve author if he belongs to approbation schema");
			$this->none					= bab_translate("None");
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
			$this->fid					= $fid;
			
			$sFolderName = '';
			$oFmFolder = BAB_FmFolderHelper::getFmFolderById($this->fid);
			if(!is_null($oFmFolder))
			{
				$sFolderName	= $this->folderval = $oFmFolder->getName();
				$this->said 	= $oFmFolder->getDelegationOwnerId();

				$this->yactsel = '';
				$this->nactsel = 'selected="selected"';
				if('Y' === $oFmFolder->getActive())
				{
					$this->yactsel = 'selected="selected"';
					$this->nactsel = '';
				}
				
				$this->ynfsel = '';
				$this->nnfsel = 'selected="selected"';
				if('Y' === $oFmFolder->getFileNotify())
				{
					$this->ynfsel = 'selected="selected"';
					$this->nnfsel = '';
				}
				
				$this->yversel = '';
				$this->nversel = 'selected="selected"';
				if('Y' === $oFmFolder->getVersioning())
				{
					$this->yversel = 'selected="selected"';
					$this->nversel = '';
				}
				
				$this->nhidesel = '';
				$this->yhidesel = 'selected="selected"';
				if('Y' === $oFmFolder->getHide())
				{
					$this->nhidesel = 'selected="selected"';
					$this->yhidesel = '';
				}
				
				$this->autoappysel = '';
				$this->autoappnsel = 'selected="selected"';
				if('Y' === $oFmFolder->getAutoApprobation())
				{
					$this->autoappysel = 'selected="selected"';
					$this->autoappnsel = '';
				}
				
				$this->ntagssel = '';
				$this->ytagssel = 'selected="selected"';
				if('Y' === $oFmFolder->getAddTags())
				{
					$this->ntagssel = 'selected="selected"';
					$this->ytagssel = '';
				}
				
				$this->boolorderm = $oFmFolder->getManualOrder();
				
				$this->isdownloadscapping	= ($oFmFolder->getDownloadsCapping() == 'Y');
				$this->maxdownloads			= (int) $oFmFolder->getMaxDownloads();
				$this->isdownloadhistory	= ($oFmFolder->getDownloadHistory() == 'Y');

				$this->safm = $oFmFolder->getApprobationSchemeId();
				
				list($n) = $babDB->db_fetch_array($babDB->db_query("select COUNT(i.id) from ".BAB_FA_INSTANCES_TBL." i, ".BAB_FILES_TBL." f where i.idsch='".$this->safm."' AND i.id=f.idfai"));
				if($n > 0)
				{
					$this->js_appflowlock = bab_translate("Approbation can't be disabled").', '.$n.' '.bab_translate("file(s) must be accepted or refused before");
					$this->js_appflowlock = str_replace("'", "\'", $this->js_appflowlock );
					$this->js_appflowlock = str_replace('"', "'+String.fromCharCode(34)+'",$this->js_appflowlock );
				}
				$this->sares = $babDB->db_query("select * from ".BAB_FLOW_APPROVERS_TBL." order by name asc");
				if(!$this->sares)
				{
					$this->sacount = 0;
				}
				else
				{
					$this->sacount = $babDB->db_num_rows($this->sares);
				}
			}
			$babBody->title = $sFolderName . ": ".bab_translate("Modify folder");
		}

		function getnextschapp()
		{
			global $babDB;
			static $i = 0;
			if($i < $this->sacount)
			{
				$arr = $babDB->db_fetch_array($this->sares);
				$this->saname = $arr['name'];
				$this->said = $arr['id'];
				if($this->said == $this->safm)
				{
					$this->sasel = 'selected="selected"';
				}
				else
				{
					$this->sasel = "";
				}
				$i++;
				return true;
			}
			else
			{
				return false;
			}
		}
	}
	$temp = new temp($fid);

	$babBody->addItemMenu("list", bab_translate("Folders"), $GLOBALS['babUrlScript']."?tg=admfms&idx=list");
	$babBody->addItemMenu("addf", bab_translate("Add"), $GLOBALS['babUrlScript']."?tg=admfms&idx=addf");
	$babBody->addItemMenu("modify", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=admfm&idx=modify");
	if( $temp->boolorderm ){
		$babBody->addItemMenu("order", bab_translate("Order files"), $GLOBALS['babUrlScript']."?tg=admfm&idx=order&fid=".$fid);
	}
	$babBody->addItemMenu("fields", bab_translate("Fields"), $GLOBALS['babUrlScript']."?tg=admfm&idx=fields&fid=".$fid);
	
	$babBody->babecho(bab_printTemplate($temp,"admfms.html", "foldermodify"));
}


function fieldsFolder($fid)
{
	global $babBody;
	
	class temp
	{
		var $fid;
		var $fieldname;
		var $fieldnameval;
		var $fieldid;
		var $altdelf;
		var $fieldurl;
		var $fielddefval;
		var $defaultname;

		function temp($fid)
		{
			global $babDB;
			
			$this->fid			= $fid;
			$this->fieldname	= bab_translate("Field");
			$this->defaultname	= bab_translate("Default Value");
			$this->altdelf		= bab_translate("Delete fields");
			$this->res			= $babDB->db_query("select * from ".BAB_FM_FIELDS_TBL." where id_folder='".$fid."'order by id asc");
			$this->count		= $babDB->db_num_rows($this->res);
		}

		function getnext()
		{
			global $babDB;
			static $i = 0;
			if($i < $this->count)
			{
				$arr = $babDB->db_fetch_array($this->res);
				$this->fieldnameval	= $arr['name'];
				$this->fielddefval	= $arr['defaultval'];
				$this->fieldid		= $arr['id'];
				$this->fieldurl		= bab_toHtml($GLOBALS['babUrlScript']."?tg=admfm&idx=mfield&fid=".$this->fid."&ffid=".$arr['id']);
				$i++;
				return true;
			}
			else
			{
				return false;
			}
		}
	}
			
	$sFolderName = '';
	$oFmFolder = BAB_FmFolderHelper::getFmFolderById($fid);
	if(!is_null($oFmFolder))
	{
		$sFolderName = $oFmFolder->getName();
	}
		
	$babBody->title = bab_toHtml($sFolderName . ": ".bab_translate("Folder's fields"));
	$babBody->addItemMenu("list", bab_translate("Folders"), $GLOBALS['babUrlScript']."?tg=admfms&idx=list");
	$babBody->addItemMenu("modify", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=admfm&idx=modify&fid=".$fid);
	$babBody->addItemMenu("fields", bab_translate("Fields"), $GLOBALS['babUrlScript']."?tg=admfm&idx=fields&fid=".$fid);
	$babBody->addItemMenu("afield", bab_translate("Add"), $GLOBALS['babUrlScript']."?tg=admfm&idx=afield&fid=".$fid);
		
	$temp = new temp($fid);
	$babBody->babecho(bab_printTemplate($temp,"admfms.html", "fmfields"));
}


function addFieldFolder($fid, $fname, $defval)
{
	global $babBody;
	
	class tempa
	{
		var $fid;
		var $ffid;
		var $field;
		var $add;
		var $fname;
		var $what;
		var $defval;
		var $default;

		function tempa($fid, $fname, $defval)
		{
			$this->fid		= $fid;
			$this->ffid		= '';
			$this->field	= bab_translate("Field name");
			$this->default	= bab_translate("Default Value");
			$this->add		= bab_translate("Add");
			
			if(!empty($fname))
			{
				$fname = bab_toHtml(stripslashes($fname));
			}
			if(!empty($defval))
			{
				$defval = bab_toHtml(stripslashes($defval));
			}
			$this->fname	= $fname;
			$this->defval	= $defval;
			$this->what		= 'fadd';
		}
	}
		
	$sFolderName = '';
	$oFmFolder = BAB_FmFolderHelper::getFmFolderById($fid);
	if(!is_null($oFmFolder))
	{
		$sFolderName = $oFmFolder->getName();
	}
		
	$babBody->title = bab_toHtml($sFolderName . ": ".bab_translate("Add folder's field"));
	$babBody->addItemMenu("list", bab_translate("Folders"), $GLOBALS['babUrlScript']."?tg=admfms&idx=list");
	$babBody->addItemMenu("modify", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=admfm&idx=modify&fid=".$fid);
	$babBody->addItemMenu("fields", bab_translate("Fields"), $GLOBALS['babUrlScript']."?tg=admfm&idx=fields&fid=".$fid);
	$babBody->addItemMenu("afield", bab_translate("Add"), $GLOBALS['babUrlScript']."?tg=admfm&idx=afield&fid=".$fid);

	$temp = new tempa($fid, $fname, $defval);
	$babBody->babecho(bab_printTemplate($temp,"admfms.html", "fmfieldadd"));
}


function modifyFieldFolder($fid, $ffid, $fname, $defval)
{
	global $babBody;
	
	class tempa
	{
		var $fid;
		var $ffid;
		var $field;
		var $add;
		var $fname;
		var $what;
		var $default;
		var $defval;

		function tempa($fid, $ffid, $fname, $defval)
		{
			global $babDB;
			$this->fid		= $fid;
			$this->field	= bab_translate("Field name");
			$this->default	= bab_translate("Default value");
			$this->add		= bab_translate("Modify");
			$this->fname	= $fname;
			$this->defval	= $defval;
			$this->what		= 'fmod';
			$this->ffid		= $ffid;
			
			if(empty($fname))
			{
				$res = $babDB->db_query("select * from ".BAB_FM_FIELDS_TBL." where id='".$ffid."'");
				if($res && $babDB->db_num_rows($res) > 0)
				{
					$arr = $babDB->db_fetch_array($res);
					$this->fname = bab_toHtml($arr['name']);
					$this->defval = bab_toHtml($arr['defaultval']);
				}
			}
		}
	}
	
	$sFolderName = '';
	$oFmFolder = BAB_FmFolderHelper::getFmFolderById($fid);
	if(!is_null($oFmFolder))
	{
		$sFolderName = $oFmFolder->getName();
	}
		
	$babBody->title = bab_toHtml($sFolderName . ": ".bab_translate("Modify folder's field"));
	$babBody->addItemMenu("list", bab_translate("Folders"), $GLOBALS['babUrlScript']."?tg=admfms&idx=list");
	$babBody->addItemMenu("modify", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=admfm&idx=modify&fid=".$fid);
	$babBody->addItemMenu("fields", bab_translate("Fields"), $GLOBALS['babUrlScript']."?tg=admfm&idx=fields&fid=".$fid);
	$babBody->addItemMenu("mfield", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=admfm&idx=mfield&fid=".$fid);

	$temp = new tempa($fid, $ffid, $fname, $defval);
	$babBody->babecho(bab_printTemplate($temp,"admfms.html", "fmfieldadd"));
}


function deleteFolder($fid)
{
	global $babBody;
	
	class temp
	{
		var $warning;
		var $message;
		var $title;
		var $urlyes;
		var $urlno;
		var $yes;
		var $no;
		var $topics;
		var $article;

		function temp($fid)
		{
			$this->message = bab_translate("Are you sure you want to delete this folder");
			
			$this->title = '';
			$oFmFolder = BAB_FmFolderHelper::getFmFolderById($fid);
			if(!is_null($oFmFolder))
			{
				$this->title = $oFmFolder->getName();
			}

			$babBody->title = bab_toHtml($this->title . ": ".bab_translate("Delete folder"));
			$this->warning	= bab_toHtml(bab_translate("WARNING: This operation will delete the folder with all files"). "!");
			$this->urlyes	= bab_toHtml($GLOBALS['babUrlScript']."?tg=admfm&idx=list&fid=".$fid."&action=fyes");
			$this->yes		= bab_toHtml(bab_translate("Yes"));
			$this->urlno	= bab_toHtml($GLOBALS['babUrlScript']."?tg=admfms&idx=list");
			$this->no		= bab_toHtml(bab_translate("No"));
		}
	}

	$babBody->addItemMenu("list", bab_translate("Folders"), $GLOBALS['babUrlScript']."?tg=admfms&idx=list");
	$babBody->addItemMenu("addf", bab_translate("Add"), $GLOBALS['babUrlScript']."?tg=admfms&idx=addf");
	
	$temp = new temp($fid);
	$babBody->babecho(	bab_printTemplate($temp,"warning.html", "warningyesno"));
}


function displayRightForm($fid)
{
	global $babBody;
	
	$sFolderName = '';
	$oFmFolder = BAB_FmFolderHelper::getFmFolderById($fid);
	if(!is_null($oFmFolder))
	{
		$sFolderName = $oFmFolder->getName();
	}
		
	$babBody->title = bab_translate("Rights of directory").' '.$sFolderName;
	$macl = new macl("admfm", "modify", $fid, "aclview", true, $oFmFolder->getDelegationOwnerId());
	$macl->addtable( BAB_FMUPLOAD_GROUPS_TBL,bab_translate("Upload"));
	$macl->filter(0,0,1,0,1);
	$macl->addtable( BAB_FMDOWNLOAD_GROUPS_TBL,bab_translate("Download"));
	$macl->addtable( BAB_FMUPDATE_GROUPS_TBL,bab_translate("Update"));
	$macl->filter(0,0,1,0,1);
	$macl->addtable( BAB_FMMANAGERS_GROUPS_TBL,bab_translate("Manage"));
	$macl->filter(0,0,1,1,1);
	$macl->addtable( BAB_FMNOTIFY_GROUPS_TBL,bab_translate("Who is notified when a new file is uploaded or updated?"));
	$macl->filter(0,0,1,0,1);
	$macl->addtable( 'bab_fmunzip_groups',bab_translate("Who can unzip archives?"));
	$macl->filter(0,0,1,0,1);
	$macl->babecho();
	$babBody->addItemMenu("list", bab_translate("Folders"), $GLOBALS['babUrlScript']."?tg=admfms&idx=list");
	$babBody->addItemMenu("addf", bab_translate("Add"), $GLOBALS['babUrlScript']."?tg=admfms&idx=addf");
	$babBody->addItemMenu("modify", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=admfm&idx=modify&fid=".$fid);
	$babBody->addItemMenu("rights", bab_translate("Rights"), $GLOBALS['babUrlScript']."?tg=admfm&idx=rights&fid=".$fid);
}
	

function deleteFieldsFolder($fid, $fields)
{
	global $babBody;
	
	class temp
	{
		var $warning;
		var $message;
		var $title;
		var $urlyes;
		var $urlno;
		var $yes;
		var $no;
		var $topics;
		var $article;

		function temp($fid, $fields)
		{
			global $babBody;
			$this->message = bab_translate("Are you sure you want to delete selected fields");
			
			$this->title = '';
			$oFmFolder = BAB_FmFolderHelper::getFmFolderById($fid);
			if(!is_null($oFmFolder))
			{
				$this->title = $oFmFolder->getName();
			}
			
			$babBody->title	= bab_toHtml($this->title . ": ".bab_translate("Delete folder's fields"));
			$this->warning	= bab_toHtml(bab_translate("WARNING: This operation will delete those fields with their values"). "!");
			$this->urlyes	= bab_toHtml($GLOBALS['babUrlScript']."?tg=admfm&idx=fields&fid=".$fid."&action=ffyes&fields=".implode(',', $fields));
			$this->yes		= bab_toHtml(bab_translate("Yes"));
			$this->urlno	= bab_toHtml($GLOBALS['babUrlScript']."?tg=admfm&idx=fields&fid=".$fid);
			$this->no		= bab_toHtml(bab_translate("No"));
		}
	}

	$babBody->addItemMenu("list", bab_translate("Folders"), $GLOBALS['babUrlScript']."?tg=admfms&idx=list");
	$babBody->addItemMenu("modify", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=admfm&idx=modify&fid=".$fid);
	$babBody->addItemMenu("fields", bab_translate("Fields"), $GLOBALS['babUrlScript']."?tg=admfm&idx=fields&fid=".$fid);
	$babBody->addItemMenu("delff", bab_translate("Delete"), $GLOBALS['babUrlScript']."?tg=admfm&idx=delff&fid=".$fid);
	
	$temp = new temp($fid, $fields);
	$babBody->babecho(bab_printTemplate($temp,"warning.html", "warningyesno"));
}


function updateFolder($fid, $fname, $active, $said, $notification, $version, $bhide, $bautoapp, $baddtags, $bdownloadscapping, $maxdownloads, $bdownloadhistory, $orderm)
{
	global $babBody, $babDB;
	if(empty($fname))
	{
		$babBody->msgerror = bab_translate("ERROR: You must provide a name !!");
		return;
	}
	
	$oFmFolderSet = new BAB_FmFolderSet();

	$oId = $oFmFolderSet->aField['iId'];
	$oName = $oFmFolderSet->aField['sName'];
	$oIdDgOwner = $oFmFolderSet->aField['iIdDgOwner'];
	
	$sName = replaceInvalidFolderNameChar($fname);
	
	$oCriteria = $oId->notIn($fid);
	$oCriteria = $oCriteria->_and($oName->in($sName));
	$oCriteria = $oCriteria->_and($oIdDgOwner->in($babBody->currentAdmGroup));
	$oFmFolder = $oFmFolderSet->get($oCriteria);
	if(is_null($oFmFolder))
	{
		$oFmFolder = BAB_FmFolderHelper::getFmFolderById($fid);
		if(!is_null($oFmFolder))
		{
			$idsafolder = $oFmFolder->getApprobationSchemeId();
			
			if($idsafolder != $said)
			{
				include_once $GLOBALS['babInstallPath']."utilit/afincl.php";
				
				$oFolderFileSet = new BAB_FolderFileSet();
				$oIdOwner = $oFolderFileSet->aField['iIdOwner'];
				$oGroup = $oFolderFileSet->aField['sGroup'];
				$oConfirmed = $oFolderFileSet->aField['sConfirmed'];
				
				$oCriteria = $oIdOwner->in($fid);
				$oCriteria = $oCriteria->_and($oGroup->in('Y'));
				$oCriteria = $oCriteria->_and($oConfirmed->in('N'));
				
				$oFolderFileSet->select($oCriteria);
				
				while(null !== ($oFolderFile = $oFolderFileSet->next()))
				{
					if(0 !== $oFolderFile->getFlowApprobationInstanceId())
					{
						deleteFlowInstance($oFolderFile->getFlowApprobationInstanceId());
					}
	
	
					if($said != 0)
					{
						if($bautoapp == 'Y')
						{
							$idfai = makeFlowInstance($said, 'fil-'.$oFolderFile->getId(), $GLOBALS['BAB_SESS_USERID']);
						}
						else
						{
							$idfai = makeFlowInstance($said, 'fil-'.$oFolderFile->getId());
						}
					}

					if($said == 0 || $idfai === true)
					{
						$oFolderFile->setFlowApprobationInstanceId(0);
						$oFolderFile->setConfirmed('Y');
						$oFolderFile->save();
					}
					else if(!empty($idfai))
					{
						$oFolderFile->setFlowApprobationInstanceId($idfai);
						$oFolderFile->save();

						$nfusers = getWaitingApproversFlowInstance($idfai, true);
						if(count($nfusers) > 0)
						{
							notifyFileApprovers($oFolderFile->getId(), $nfusers, bab_translate("A new file is waiting for you"));
						}
					}

				
					$oFolderFileVersionSet = new BAB_FolderFileVersionSet();
					$oIdFile = $oFolderFileVersionSet->aField['iIdFile'];
					$oConfirmed = $oFolderFileVersionSet->aField['sConfirmed'];
					
					$oCriteria = $oIdFile->in($oFolderFile->getId());
					$oCriteria = $oCriteria->_and($oConfirmed->in('N'));
					
					$oFolderFileVersionSet->select($oCriteria);

					while(null !== ($oFolderFileVersion = $oFolderFileVersionSet->next()))
					{
						if(0 !== $oFolderFileVersion->getFlowApprobationInstanceId())
						{
							deleteFlowInstance($oFolderFileVersion->getFlowApprobationInstanceId());
						}


						if($said != 0)
						{
							if($bautoapp == 'Y')
							{
								$idfai = makeFlowInstance($said, 'filv-'.$oFolderFileVersion->getId(), $GLOBALS['BAB_SESS_USERID']);
							}
							else
							{
								$idfai = makeFlowInstance($said, 'filv-'.$oFolderFileVersion->getId());
							}
						}

						if($said == 0 || $idfai === true)
						{
							acceptFileVersion($oFolderFile, $oFolderFileVersion);
						}
						else if(!empty($idfai))
						{
							$oFolderFileVersion->setFlowApprobationInstanceId($idfai);
							$oFolderFileVersion->save();
							$nfusers = getWaitingApproversFlowInstance($idfai, true);
							if(count($nfusers) > 0)
							{
								notifyFileApprovers($oFolderFile->getId(), $nfusers, bab_translate("A new version file is waiting for you"));
							}
						}

					}
				}
			}
			
			$oFileManagerEnv	= getEnvObject();
			$sRootFmPath		= BAB_FileManagerEnv::getCollectivePath($oFmFolder->getDelegationOwnerId());
			$sRelativePath		= '';
			
			if(isStringSupportedByFileSystem($sName))
			{
				BAB_FmFolderSet::rename($sRootFmPath, $sRelativePath, $oFmFolder->getName(), $sName);
				BAB_FmFolderCliboardSet::rename($sRelativePath, $oFmFolder->getName(), $sName, 'Y');
				BAB_FolderFileSet::renameFolder($oFmFolder->getName() . '/', $sName, 'Y');
				
				$oFmFolder->setName($sName);
				$oFmFolder->setRelativePath('');
				$oFmFolder->setApprobationSchemeId((int) $said);
				$oFmFolder->setFileNotify($notification);
				$oFmFolder->setActive($active);
				$oFmFolder->setVersioning($version);
				$oFmFolder->setHide($bhide);
				$oFmFolder->setAddTags($baddtags);
				$oFmFolder->setAutoApprobation($bautoapp);

				$oFmFolder->setDownloadsCapping($bdownloadscapping);
				$oFmFolder->setMaxDownloads($maxdownloads);
				$oFmFolder->setDownloadHistory($bdownloadhistory);
				$oFmFolder->setManualOrder($orderm);

				$oFmFolder->save();
			}
			else
			{
				$babBody->addError(bab_translate("The directory name contains characters not supported by the file system"));
				return;	
			}
		}		
		Header("Location: ". $GLOBALS['babUrlScript']."?tg=admfms&idx=list");
		exit;
	}
	else 
	{
		$babBody->msgerror = bab_translate("This folder already exists");
	}
}


function confirmDeleteFolder($fid)
{
	include_once $GLOBALS['babInstallPath']."utilit/delincl.php";
	bab_deleteFolder($fid);
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=admfms&idx=list");
	exit;
}

function confirmDeleteFields($fid, $fields)
{
	global $babDB;

	if( !empty($fields))
	{
	$arr = explode(',', $fields);
	for( $i = 0; $i < count($arr); $i++)
		{
		$babDB->db_query("delete from ".BAB_FM_FIELDSVAL_TBL." where id_field='".$arr[$i]."'");
		$babDB->db_query("delete from ".BAB_FM_FIELDS_TBL." where id='".$arr[$i]."' and id_folder='".$fid."'");
		}
	}
}

function addField($fid, $ffname, $defval)
{
	global $babBody, $babDB;

	$res = $babDB->db_query("select id from ".BAB_FM_FIELDS_TBL." where name='".$babDB->db_escape_string($ffname)."' and id_folder='".$babDB->db_escape_string($fid)."'");
	if( $babDB->db_num_rows($res) > 0)
		{
		$babBody->msgerror = bab_translate("This field already exists");
		return false;
		}
	else
		{
		$babDB->db_query("insert into ".BAB_FM_FIELDS_TBL." (id_folder, name, defaultval) VALUES ('" .$babDB->db_escape_string($fid). "', '" . $babDB->db_escape_string($ffname). "', '" . $babDB->db_escape_string($defval). "')");
		}

	return true;
}

function modifyField($fid, $ffid, $ffname, $defval)
{
	global $babBody, $babDB;

	$babDB->db_query("update ".BAB_FM_FIELDS_TBL." set 
	name='" . $babDB->db_escape_string($ffname). "', 
	defaultval='".$babDB->db_escape_string($defval)."' 
	where id='".$babDB->db_escape_string($ffid)."'
	");

	return true;
}

function orderFiles($fid){
	global $babBody;
	class temp{		
		var $forumtxt;
		var $moveup;
		var $movedown;
		var $create;
		var $db;
		var $res;
		var $count;
		var $arrid = array();
		var $forumid;
		var $forumval;
		var $fid;


		function temp($fid){
			global $babBody, $babDB, $BAB_SESS_USERID;
			$this->moveup = bab_translate("Move Up");
			$this->movedown = bab_translate("Move Down");
			$this->sorta = bab_translate("Sort ascending");
			$this->sortd = bab_translate("Sort descending");
			$this->create = bab_translate("Modify");
			$this->fid = $fid;
			$this->tg = bab_gp('tg');
			$oFmFolder = BAB_FmFolderHelper::getFmFolderById($fid);
			//bab_debug($oFmFolder->getName());
			$req = "select id, name from ".BAB_FILES_TBL." where path='" . $oFmFolder->getName() . "/' order by display_position, name asc";
			$this->res = $babDB->db_query($req);
			while( $arr = $babDB->db_fetch_array($this->res) ){
					$this->arrid[] = $arr['id'];
					$this->arrname[] = $arr['name'];
			}
			$this->count = count($this->arrid);
		}

		function getnext(){
			static $i = 0;
			if( $i < $this->count){
				$this->filesname = bab_toHtml($this->arrname[$i]);
				$this->filesid = $this->arrid[$i];
				$i++;
				return true;
			}else{
				return false;
			}
		}
	}
	$temp = new temp($fid);
	
	$babBody->title = bab_translate("Order files");
	$babBody->addItemMenu("list", bab_translate("Folders"), $GLOBALS['babUrlScript']."?tg=admfms&idx=list");
	$babBody->addItemMenu("addf", bab_translate("Add"), $GLOBALS['babUrlScript']."?tg=admfms&idx=addf");
	$babBody->addItemMenu("modify", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=admfm&idx=modify&fid=".$fid);
	$babBody->addItemMenu("order", bab_translate("Order files"), $GLOBALS['babUrlScript']."?tg=admfm&idx=order&fid=".$fid);
	$babBody->babecho(	bab_printTemplate($temp, "sites.html", "scripts"));
	$babBody->babecho(	bab_printTemplate($temp,"admfms.html", "filesorder"));
	
	return true;
}

function updateOrderFiles($fid,$listfiles){
	global $babDB;
	
	$i = 0;
	foreach($listfiles as $fileID){
		$babDB->db_query("UPDATE " . BAB_FILES_TBL . " SET display_position='" . $i . "' WHERE id='" . $fileID . "'");
		$i++;
	}
}

/* main */
if( !$babBody->isSuperAdmin && $babBody->currentDGGroup['filemanager'] != 'Y')
{
	$babBody->msgerror = bab_translate("Access denied");
	return;
}



//bab_debug(__FILE__);
//bab_debug($_POST);

if( isset($mod) && $mod == "modfolder")
{
	if( isset($bupdate))
		updateFolder($fid, $fname, $active, $said, $notification, $version, $bhide, $bautoapp, $baddtags, $bdownloadscapping, $maxdownloads, $bdownloadhistory, $orderm);
	else if(isset($bdel))
		$idx = "delf";
}else if( isset($action)){
	if( $action == 'fyes'){
		confirmDeleteFolder($fid);
	}else if( $action == 'ffyes' ){
		confirmDeleteFields($fid, $fields);
	}else if( $action == 'order' ){
		updateOrderFiles($fid,$listfiles);
	}
}
else if( isset($fmf))
	{
	if( $fmf == 'fadd' )
		{
		if( !addField($fid, $ffname, $defval))
			$idx = 'afield';
		}
	else if( $fmf == 'fmod' )
		{
		if( !modifyField($fid, $ffid, $ffname, $defval))
			$idx = 'mfield';
		}

	}
else if(isset($aclview))
{
	maclGroups();
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=admfms&idx=list");
	exit;
}

switch($idx)
{
	case "order":
		orderFiles($fid);
		break;
		
	case "rights":
		displayRightForm($fid);
		break;

	case "delf":
		deleteFolder($fid);
		break;

	case "mfield":
		if(!isset($ffname)) $ffname = '';
		if(!isset($defval)) $defval = '';
		modifyFieldFolder($fid, $ffid, $ffname, $defval);
		break;

	case "afield":
		if( !isset($ffname)) $ffname = '';
		if( !isset($defval)) $defval = '';
		addFieldFolder($fid, $ffname, $defval);
		break;

	case "delff":
		if(count($fields) > 0)
		{
			deleteFieldsFolder($fid, $fields);
			break;
		}
		/* no break ; */
	case "fields":
		fieldsFolder($fid);
		break;

	default:
	case "modify":
		modifyFolder($fid);
		break;
}
$babBody->setCurrentItemMenu($idx);

?>