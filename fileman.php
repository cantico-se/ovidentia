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
require_once 'base.php';
require_once $GLOBALS['babInstallPath'].'utilit/delegincl.php';
require_once $GLOBALS['babInstallPath'].'utilit/fileincl.php';
require_once $GLOBALS['babInstallPath'].'utilit/uploadincl.php';
require_once $GLOBALS['babInstallPath'].'utilit/indexincl.php';
require_once $GLOBALS['babInstallPath'].'utilit/baseFormProcessingClass.php';


class listFiles
{
	var $db;
	var $res;
	var $count;
	var $id;
	var $gr;
	var $path;
	var $jpath;
	var $countmgrp;
	var $countwf;
	var $reswf;
	var $buaf;
	
	var $oFolderFileSet = null;
	
	var $aCuttedDir = array();
	
	var $sProcessedIdx = '';	
	var $sListFunctionName = '';
	
	var $bParentUrl = false;
	var $sParentTitle = '';
	var $sParent = '. .';
	var $bVersion = false;

	var $oFileManagerEnv = null;
	
	var $sRootFolderPath = '';
	/**
	 * Files extracted by readdir
	 */
	var $files_from_dir = array();

	var $aFolders = array();
	
	function listFiles($what="list")
	{
		if(!function_exists('bab_compareFmFiles'))
		{		
			function bab_compareFmFiles($f1, $f2)
			{
				return strcasecmp($f1['sName'], $f2['sName']);
			}
		}
		
		$this->sParentTitle = bab_translate("Parent");
		
		global $babBody, $babDB, $BAB_SESS_USERID;
		include_once $GLOBALS['babInstallPath']."utilit/afincl.php";
		
		$this->oFolderFileSet = new BAB_FolderFileSet();
		
		$this->sProcessedIdx = $what;
		$this->initEnv();
	
		$this->{$this->sListFunctionName}();
		
		$this->prepare();
		$this->autoadd_files();
	}
	
	function initEnv()
	{
		global $BAB_SESS_USERID;
		
		$this->oFileManagerEnv =& getEnvObject();
		$this->countwf = 0;

		if($this->oFileManagerEnv->userIsInRootFolder())
		{
			$this->sListFunctionName = 'listRootFolders';
		}
		else if($this->oFileManagerEnv->userIsInCollectiveFolder())
		{
			$this->sListFunctionName = 'listCollectiveFolder';
			
			if(0 !== $this->oFileManagerEnv->iPathLength)
			{
				if('list' === $this->sProcessedIdx)
				{
					$oFmFolder = $this->oFileManagerEnv->oFmFolder;
					if(!is_null($oFmFolder))
					{			
						if(0 !== $oFmFolder->getApprobationSchemeId())
						{
							$this->buaf = isUserApproverFlow($oFmFolder->getApprobationSchemeId(), $BAB_SESS_USERID);
							if($this->buaf)
							{
								$this->selectWaitingFile();
							}
						}
					}
				}
			}
		}
		else if($this->oFileManagerEnv->userIsInPersonnalFolder())
		{
			$this->sListFunctionName = 'listPersonnalFolder';
		}
		$this->getClipboardFolder();

		$this->sParentUrl = $GLOBALS['babUrlScript'] . '?tg=fileman&idx=' . urlencode($this->sProcessedIdx) . '&id=' . $this->oFileManagerEnv->iId . 
			'&gr=' . $this->oFileManagerEnv->sGr . '&path=';
		$this->bParentUrl = $this->oFileManagerEnv->setParentPath($this->sParentUrl);
		$this->sParentUrl = bab_toHtml($this->sParentUrl);
		
		$sPath = $this->oFileManagerEnv->sPath;
		$this->path = $sPath;
		$this->id = $this->oFileManagerEnv->iId;
		$this->gr = $this->oFileManagerEnv->sGr;
		
		$this->jpath = bab_toHtml($sPath, BAB_HTML_JS);
	}
	

	function listRootFolders()
	{
		global $BAB_SESS_USERID;
		
		$oFmFolderSet = new BAB_FmFolderSet();
		$oRelativePath =& $oFmFolderSet->aField['sRelativePath'];
		$oIdDgOwner =& $oFmFolderSet->aField['iIdDgOwner'];
		
		$oCriteria = $oRelativePath->in('');
		$oCriteria = $oCriteria->_and($oIdDgOwner->in(bab_getCurrentUserDelegation()));
		
//		bab_debug($oFmFolderSet->getSelectQuery($oCriteria));
		$oFmFolderSet->select($oCriteria);
		
		while(null !== ($oFmFolder = $oFmFolderSet->next()))
		{
			$this->addCollectiveDirectory($oFmFolder, $oFmFolder->getId());
		}
		uasort($this->aFolders, 'bab_compareFmFiles');
		
		if(userHavePersonnalStorage())
		{
			$aItem = array(
				'iId' => 0, 
				'bCanManageFolder' => false,
				'bCanBrowseFolder' => true,
				'bCanEditFolder' => false, 
				'bCanSetRightOnFolder' => false,
				'bCanCutFolder' => false, 
				'sName' => bab_translate("Personal Folder"), 
				'sGr' => 'N', 
				'sCollective' => 'N', 
				'sHide' => 'N',
				'sUrlPath' => '',
				'iIdUrl' => $BAB_SESS_USERID);

			$this->aFolders[] = $aItem;
		}
	}
	
	function listPersonnalFolder()
	{
		$sFullPathname = (string) $this->oFileManagerEnv->getPersonnalFolderPath();
		if(is_dir(realpath($sFullPathname)))
		{
			$this->walkDirectory($sFullPathname, 'simpleDirectoryCallback');
		}
		uasort($this->aFolders, 'bab_compareFmFiles');
	}

	function listCollectiveFolder()
	{
		$sFullPathname = (string) $this->oFileManagerEnv->getCollectiveFolderPath();
		
		if(is_dir(realpath($sFullPathname)))
		{
			$this->walkDirectory($sFullPathname, 'collectiveDirectoryCallback');
		}
		uasort($this->aFolders, 'bab_compareFmFiles');
	}
	
	function walkDirectory($sPathName, $sCallbackFunction)
	{
		if(is_dir($sPathName))
		{
			$oDir = dir($sPathName);
			while(false !== ($sEntry = $oDir->read())) 
			{
				// Skip pointers
				if($sEntry == '.' || $sEntry == '..' || $sEntry == BAB_FVERSION_FOLDER) 
				{
					continue;
				}
				$this->$sCallbackFunction($sPathName, $sEntry);
			}
			$oDir->close();
		}
	}
	
	function simpleDirectoryCallback($sPathName, $sEntry)
	{
		if(is_dir($sPathName . $sEntry)) 
		{
			$sGr				= '';
			$sRootFmPath		= '';
			$sRelativePath		= $this->oFileManagerEnv->sRelativePath . $sEntry . '/';
			$bCanManage			= canManage($sRelativePath);
			$bCanBrowse			= canBrowse($sRelativePath);
			$bAccessValid		= false;
			$bCanBrowseFolder	= false;
						
			if($this->oFileManagerEnv->userIsInCollectiveFolder() || $this->oFileManagerEnv->userIsInRootFolder())
			{
				$sRootFmPath		= $this->oFileManagerEnv->getCollectiveRootFmPath();
				$sGr				= 'Y';
				$bAccessValid		= ($bCanManage || canUpload($sRelativePath) || canUpdate($sRelativePath) || ($bCanBrowse && 'N' === $this->oFileManagerEnv->oFmFolder->getHide()));
				$bCanBrowseFolder	= ($bCanBrowse && 'Y' === $this->oFileManagerEnv->oFmFolder->getActive());
			}
			else if($this->oFileManagerEnv->userIsInPersonnalFolder())
			{
				$sRootFmPath		= $this->oFileManagerEnv->getPersonnalFolderPath();
				$sGr				= 'N';
				$bAccessValid		= $bCanManage || $bCanBrowse;
				$bCanBrowseFolder	= $bCanBrowse;
			}
			else 
			{
				return;
			}
				
			$sFullPathName	= $sRootFmPath . $this->oFileManagerEnv->sRelativePath . $sEntry;
			$bInClipBoard	= (bool) array_key_exists($sFullPathName, $this->aCuttedDir);
			
			if(false === $bInClipBoard)
			{
				if($bAccessValid)
				{
					$aItem = array(
						'iId' => 0, 
						'bCanManageFolder' => haveRight($sRelativePath, BAB_FMMANAGERS_GROUPS_TBL),
						'bCanBrowseFolder' => $bCanBrowseFolder,
						'bCanEditFolder' => canEdit($sRelativePath), 
						'bCanSetRightOnFolder' => false,
						'bCanCutFolder' => (!$bInClipBoard && canCutFolder($sRelativePath)), 
						'sName' => $sEntry, 
						'sGr' => $sGr, 
						'sCollective' => 'N', 
						'sHide' => 'N',
						'sUrlPath' => $this->oFileManagerEnv->sRelativePath . $sEntry,
						'iIdUrl' => $this->oFileManagerEnv->iId);
						
					$this->aFolders[] = $aItem;
				}
			}
		} 
		else 
		{
			$this->files_from_dir[] = $sEntry;
		}
	}

	function collectiveDirectoryCallback($sPathName, $sEntry)
	{
		global $babBody;
		
		$oFmFolderSet = new BAB_FmFolderSet();
		$oName =& $oFmFolderSet->aField['sName'];
		$oRelativePath =& $oFmFolderSet->aField['sRelativePath'];
		$oIdDgOwner =& $oFmFolderSet->aField['iIdDgOwner'];
		
		$oCriteria = $oName->in($sEntry);
		$oCriteria = $oCriteria->_and($oRelativePath->in($this->oFileManagerEnv->sRelativePath));
		$oCriteria = $oCriteria->_and($oIdDgOwner->in(bab_getCurrentUserDelegation()));

		$oFmFolder = $oFmFolderSet->get($oCriteria);
		if(!is_null($oFmFolder))
		{
			$sUrlPath = $oFmFolder->getRelativePath() . $oFmFolder->getName() . '/';
			$this->addCollectiveDirectory($oFmFolder, $this->oFileManagerEnv->iId);
		}
		else 
		{
			$this->simpleDirectoryCallback($sPathName, $sEntry);
		}
	}

	
	function addCollectiveDirectory($oFmFolder, $iIdRootFolder)
	{
		$sRelativePath = $oFmFolder->getRelativePath() . $oFmFolder->getName() . '/';
		
		$sRootFmPath = $this->oFileManagerEnv->getCollectiveRootFmPath();
		$sFullPathName = $sRootFmPath . $oFmFolder->getRelativePath() . $oFmFolder->getName();
		
		$bInClipBoard = (bool) array_key_exists($sFullPathName, $this->aCuttedDir);
		
		if(false === $bInClipBoard)
		{
			$bCanManage = canManage($sRelativePath);
			$bCanBrowse = canBrowse($sRelativePath);
			
			if($bCanManage || canUpload($sRelativePath) || canUpdate($sRelativePath) || ($bCanBrowse && 'N' === $oFmFolder->getHide()))
			{
				$aItem = array(
					'iId' => $oFmFolder->getId(), 
					'bCanManageFolder' => haveRight($sRelativePath, BAB_FMMANAGERS_GROUPS_TBL),
					'bCanBrowseFolder' => (canBrowse($sRelativePath) && 'Y' === $oFmFolder->getActive()),
					'bCanEditFolder' => canEdit($sRelativePath), 
					'bCanSetRightOnFolder' => canSetRight($sRelativePath),
					'bCanCutFolder' => canCutFolder($sRelativePath), 
					'sName' => $oFmFolder->getName(), 
					'sGr' => 'Y', 
					'sCollective' => 'Y', 
					'sHide' => $oFmFolder->getHide(),
					'sUrlPath' => $oFmFolder->getRelativePath() . $oFmFolder->getName(),
					'iIdUrl' => $iIdRootFolder);
					
				$this->aFolders[] = $aItem;
			}
		}
	}
		
	
	function getClipboardFolder()
	{
		global $babBody;
		
		$sRootFmPath = '';
		$sGr = '';
		
		$oFmFolderCliboardSet = new BAB_FmFolderCliboardSet();
		$oIdDgOwner = $oFmFolderCliboardSet->aField['iIdDgOwner'];
		$oIdOwner = $oFmFolderCliboardSet->aField['iIdOwner'];
		$oGroup = $oFmFolderCliboardSet->aField['sGroup'];
		
		$oCriteria = null;
		
		if($this->oFileManagerEnv->userIsInCollectiveFolder() || $this->oFileManagerEnv->userIsInRootFolder())
		{
			$sGr = 'Y';
			$sRootFmPath = $this->oFileManagerEnv->getCollectiveRootFmPath();
			
			$oCriteria = $oIdDgOwner->in(bab_getCurrentUserDelegation());
		}
		else if($this->oFileManagerEnv->userIsInPersonnalFolder())
		{
			$sGr = 'N';
			$sRootFmPath = $this->oFileManagerEnv->getPersonnalFolderPath();
			
			$oCriteria = $oIdDgOwner->in(0);
		}
		else 
		{
			return;
		}
		
		
		$oCriteria = $oCriteria->_and($oGroup->in($sGr));
		$aOrder = array('sName' => 'ASC');
		$oFmFolderCliboardSet->select($oCriteria, $aOrder);
		
		$bSrcPathIsCollective = true;
		$iIdTrgRootFolder = $this->oFileManagerEnv->iId;
		$sTrgPath = $this->oFileManagerEnv->sPath;
		
		while(null !== ($oFmFolderCliboard = $oFmFolderCliboardSet->next()))
		{
			$sSrcPath = $oFmFolderCliboard->getRelativePath() . $oFmFolderCliboard->getName();
			$iIdSrcRootFolder = $oFmFolderCliboard->getRootFolderId();
			
			$sRelativePath =  $sSrcPath . '/';
			
			if(canPasteFolder($iIdSrcRootFolder, $sSrcPath, $bSrcPathIsCollective, $iIdTrgRootFolder, $sTrgPath))
			{
				$aItem = array(
					'iId' => $oFmFolderCliboard->getFolderId(), 
					'bCanManageFolder' => haveRight($sRelativePath, BAB_FMMANAGERS_GROUPS_TBL), 
					'bCanBrowseFolder' => canBrowse($sRelativePath),
					'bCanEditFolder' => false, 
					'bCanSetRightOnFolder' => false,
					'bCanCutFolder' => false, 
					'sName' => $oFmFolderCliboard->getName(), 
					'sGr' => $sGr, 
					'sCollective' => $oFmFolderCliboard->getCollective(), 
					'sUrlPath' => $sTrgPath,
					'iIdUrl' => $this->oFileManagerEnv->iId,
					'iIdSrcRootFolder' => $iIdSrcRootFolder,
					'sSrcPath' => $sSrcPath);
					
				$sFullPathName = $sRootFmPath . $sSrcPath;
				$this->aCuttedDir[$sFullPathName] = $aItem;
			}
		}
	}			

	
	function selectWaitingFile()
	{
		$aWaitingAppInstanceId = bab_getWaitingIdSAInstance($GLOBALS['BAB_SESS_USERID']);
		if(count($aWaitingAppInstanceId) > 0)
		{
			global $babBody;
			
			$this->oFolderFileSet->bUseAlias = false;
			$oIdOwner =& $this->oFolderFileSet->aField['iIdOwner'];
			$oGroup =& $this->oFolderFileSet->aField['sGroup'];
			$oState =& $this->oFolderFileSet->aField['sState'];
			$oPathName =& $this->oFolderFileSet->aField['sPathName'];
			$oConfirmed =& $this->oFolderFileSet->aField['sConfirmed'];
			$oIdFlowApprobationInstance = $this->oFolderFileSet->aField['iIdFlowApprobationInstance'];
			$oIdDgOwner =& $this->oFolderFileSet->aField['iIdDgOwner'];
			
			$iIdOwner = $this->oFileManagerEnv->iIdObject;
			
			$oCriteria = $oIdOwner->in($iIdOwner);
			$oCriteria = $oCriteria->_and($oGroup->in('Y'));
			$oCriteria = $oCriteria->_and($oState->in(''));
			$oCriteria = $oCriteria->_and($oPathName->in($this->oFileManagerEnv->sRelativePath));
			$oCriteria = $oCriteria->_and($oConfirmed->in('N'));
			$oCriteria = $oCriteria->_and($oIdFlowApprobationInstance->in($aWaitingAppInstanceId));
			$oCriteria = $oCriteria->_and($oIdDgOwner->in(bab_getCurrentUserDelegation()));
			
			$this->oFolderFileSet->select($oCriteria);
			$this->reswf = $this->oFolderFileSet->_oResult;
			$this->countwf = $this->oFolderFileSet->count();
			$this->oFolderFileSet->bUseAlias = true;
		}
	}
		
	function prepare() 
	{
		global $babBody;
			
		$this->oFolderFileSet->bUseAlias = false;
		$oIdOwner = $this->oFolderFileSet->aField['iIdOwner'];
		$oGroup = $this->oFolderFileSet->aField['sGroup'];
		$oState = $this->oFolderFileSet->aField['sState'];
		$oPathName = $this->oFolderFileSet->aField['sPathName'];
		$oConfirmed = $this->oFolderFileSet->aField['sConfirmed'];
		$oIdDgOwner =& $this->oFolderFileSet->aField['iIdDgOwner'];
			
		$iIdOwner = $this->oFileManagerEnv->iIdObject;
		
		$oCriteria = $oIdOwner->in($iIdOwner);
		$oCriteria = $oCriteria->_and($oGroup->in($this->oFileManagerEnv->sGr));
		$oCriteria = $oCriteria->_and($oState->in(''));
		$oCriteria = $oCriteria->_and($oPathName->in($this->oFileManagerEnv->sRelativePath));
		$oCriteria = $oCriteria->_and($oConfirmed->in('Y'));
		$oCriteria = $oCriteria->_and($oIdDgOwner->in(bab_getCurrentUserDelegation()));
		
		$aOrder = array('sName' => 'ASC');
		$this->oFolderFileSet->select($oCriteria, $aOrder);
		
		$this->res = $this->oFolderFileSet->_oResult;
		$this->count = $this->oFolderFileSet->count();
		$this->oFolderFileSet->bUseAlias = true;
	}


	/** 
	 * if there is file not presents in database, add and recreate $this->res
	 */
	function autoadd_files() 
	{
		global $babDB, $babBody;
		if(!isset($GLOBALS['babAutoAddFilesAuthorId']) || empty($GLOBALS['babAutoAddFilesAuthorId']))
		{
			return;
		}
	
		$res = $babDB->db_query('select id from '.BAB_USERS_TBL.' where id='.$babDB->quote($GLOBALS['babAutoAddFilesAuthorId']));
		if(0 == $babDB->db_num_rows($res))
		{
			return;
		}

		if($this->count < count($this->files_from_dir)) 
		{
			$oIdOwner = $this->oFolderFileSet->aField['iIdOwner'];
			$oGroup = $this->oFolderFileSet->aField['sGroup'];
			$oPathName = $this->oFolderFileSet->aField['sPathName'];
			$oName = $this->oFolderFileSet->aField['sName'];
			$oIdDgOwner =& $this->oFolderFileSet->aField['iIdDgOwner'];
			
			$iIdOwner = $this->oFileManagerEnv->iIdObject;
			
			$oFolderFile = new BAB_FolderFile();
			foreach($this->files_from_dir as $dir_file) 
			{
				$oCriteria = $oPathName->in($this->oFileManagerEnv->sRelativePath);
				$oCriteria = $oCriteria->_and($oGroup->in($this->oFileManagerEnv->sGr));
				$oCriteria = $oCriteria->_and($oName->in($dir_file));
				$oCriteria = $oCriteria->_and($oIdDgOwner->in(bab_getCurrentUserDelegation()));
				$oCriteria = $oCriteria->_and($oIdOwner->in($iIdOwner));
				
				$this->oFolderFileSet->select($oCriteria);
				
				if(0 === $this->oFolderFileSet->count())
				{
					$oFolderFile->setName($dir_file);
					$oFolderFile->setPathName($this->oFileManagerEnv->sRelativePath);
					
					$oFolderFile->setOwnerId($iIdOwner);
					$oFolderFile->setGroup($this->oFileManagerEnv->sGr);
					$oFolderFile->setCreationDate(date("Y-m-d H:i:s"));
					$oFolderFile->setAuthorId($GLOBALS['babAutoAddFilesAuthorId']);
					$oFolderFile->setModifiedDate(date("Y-m-d H:i:s"));
					$oFolderFile->setModifierId($GLOBALS['babAutoAddFilesAuthorId']);
					$oFolderFile->setConfirmed('Y');
					
					$oFolderFile->setDescription('');
					$oFolderFile->setLinkId(0);
					$oFolderFile->setReadOnly('N');
					$oFolderFile->setState('');
					$oFolderFile->setHits(0);
					$oFolderFile->setFlowApprobationInstanceId(0);
					$oFolderFile->setFolderFileVersionId(0);
					$oFolderFile->setMajorVer(1);
					$oFolderFile->setMinorVer(0);
					$oFolderFile->setCommentVer('');
					$oFolderFile->setStatusIndex(0);
					$oFolderFile->setDelegationOwnerId(bab_getCurrentUserDelegation());
					
					$oFolderFile->save();
					$oFolderFile->setId(null);
				}
			}
			$this->prepare();
		}
	}
}


class DisplayFolderFormBase extends BAB_BaseFormProcessing 
{
	function DisplayFolderFormBase()
	{
		parent::BAB_BaseFormProcessing();
		
		$sFunction 	= (string) bab_gp('sFunction', '');
		$sDirName	= (string) bab_gp('sDirName', '');
		$iIdFolder 	= (int) bab_gp('iIdFolder', 0);
		
		$this->set_data('sIdx', 'list');
		$this->set_data('sAction', $sFunction);
		$this->set_data('sTg', 'fileman');
		
		$this->setCaption();
		
		$oFileManagerEnv =& getEnvObject();
		$this->set_data('iId', $oFileManagerEnv->iId);
		$this->set_data('sPath', $oFileManagerEnv->sPath);
		$this->set_data('sGr', $oFileManagerEnv->sGr);
		
		$this->set_data('sDirName', $sDirName);
		$this->set_data('sOldDirName', '');
		$this->set_data('iIdFolder', 0);
		
		$this->set_data('sSimple', 'simple');
		$this->set_data('sCollective', 'collective');
		$this->set_data('sHtmlTable', '');
		
		$this->set_data('iIdFolder', $iIdFolder);
		
		$this->set_data('bDelete', false);
		
		if('createFolder' === $sFunction)
		{
			$this->handleCreation();
		}
		else if('editFolder' === $sFunction)
		{
			$this->handleEdition();
		}
	}
	
	function setCaption()
	{
		$this->set_caption('sDirName', bab_translate("Name") . ': ');
		$this->set_caption('sDelete', bab_translate("Delete"));
		$this->set_caption('sSubmit', bab_translate("Submit"));
	}
	
	function handleCreation()
	{
		
	}
	
	function handleEdition()
	{
		$this->get_data('sDirName', $sDirName);
		$this->set_data('sOldDirName', $sDirName);
	}
	
	function printTemplate()
	{
	}
}	

class DisplayUserFolderForm extends DisplayFolderFormBase
{
	function DisplayUserFolderForm()
	{
		parent::DisplayFolderFormBase();
	}
	
	function handleEdition()
	{
		parent::handleEdition();
		
		global $BAB_SESS_USERID;
		$this->get_data('iId', $iId);
		$this->set_data('bDelete', (((int) $iId === (int) $BAB_SESS_USERID) ? true : false));
	}
	
	function printTemplate()
	{
		global $babBody;
		
		$this->set_data('sHtmlTable', bab_printTemplate($this, 'fileman.html', 'userDir'));
		
		$this->raw_2_html(BAB_RAW_2_HTML_CAPTION);
		return bab_printTemplate($this, 'fileman.html', 'displayFolderForm');
	}
}


class DisplayCollectiveFolderForm extends DisplayFolderFormBase
{
	var $iApprobationSchemeId = null;
	var $oAppSchemeRes = false;
	
	function DisplayCollectiveFolderForm()
	{
		parent::DisplayFolderFormBase();
		
		$this->setCaption();
		$this->set_data('sYes', 'Y');
		$this->set_data('sNo', 'N');
		$this->set_data('iNone', 0);
		
		$this->set_data('iAppSchemeId', 0);
		$this->set_data('iAppSchemeName', '');
		
		global $babDB;
		$this->oAppSchemeRes = $babDB->db_query("select * from ".BAB_FLOW_APPROVERS_TBL." order by name asc");
	}
	
	function setCaption()
	{
		parent::setCaption();
		$this->set_caption('sType', bab_translate("Type") . ': ');
		$this->set_caption('sActive', bab_translate("Actif") . ': ');
		$this->set_caption('sApprobationScheme', bab_translate("Approbation schema") . ': ');
		$this->set_caption('sAutoApprobation', bab_translate("Automatically approve author if he belongs to approbation schema") . ': ');
		$this->set_caption('sNotification', bab_translate("Notification") . ': ');
		$this->set_caption('sVersioning', bab_translate("Versioning") . ': ');
		$this->set_caption('sDisplay', bab_translate("Visible in file manager?") . ': ');
		$this->set_caption('sAddTags', bab_translate("Users can add new tags") . ': ');
		$this->set_caption('sSimple', bab_translate("Simple"));
		$this->set_caption('sCollective', bab_translate("Collectif"));
		$this->set_caption('sYes', bab_translate("Yes"));
		$this->set_caption('sNo', bab_translate("No"));
		$this->set_caption('sNone', bab_translate("None"));
		$this->set_caption('sAdd', bab_translate("Add"));
		$this->set_caption('sConfRights', bab_translate("Inherit the rights and the options of the parent directory"));
		
	}
	
	function handleCreation()
	{
		$sActive				= 'Y';
		$iIdApprobationScheme	= 0;
		$sAutoApprobation		= 'N';
		$sNotification			= 'N';
		$sVersioning			= 'N';
		$sDisplay				= 'N';
		$sAddTags				= 'Y';

		$oFileManagerEnv =& getEnvObject();
		$oFirstCollectiveParent = BAB_FmFolderSet::getFirstCollectiveFolder($oFileManagerEnv->sRelativePath);
		if(!is_null($oFirstCollectiveParent))
		{		
			$sActive				= (string) $oFirstCollectiveParent->getActive();
			$iIdApprobationScheme	= (int) $oFirstCollectiveParent->getApprobationSchemeId();
			$sAutoApprobation		= (string) $oFirstCollectiveParent->getAutoApprobation();
			$sNotification			= (string) $oFirstCollectiveParent->getFileNotify();
			$sVersioning			= (string) $oFirstCollectiveParent->getVersioning();
			$sDisplay				= (string) $oFirstCollectiveParent->getHide();
			$sAddTags				= (string) $oFirstCollectiveParent->getAddTags();
		}
		
		$this->iApprobationSchemeId = $iIdApprobationScheme;
		$this->set_data('isCollective', false);
		$this->set_data('isActive', ('Y' === $sActive) ? true : false);
		$this->set_data('isAutoApprobation', ('Y' === $sAutoApprobation) ? true : false);
		$this->set_data('isFileNotify', ('Y' === $sNotification) ? true : false);
		$this->set_data('isVersioning', ('Y' === $sVersioning) ? true : false);
		$this->set_data('isShow', ('Y' === $sDisplay) ? false : true);
		$this->set_data('isAddTags', ('Y' === $sAddTags) ? true : false);
		$this->set_data('sChecked', 'checked');
		$this->set_data('sDisabled', '');
		
		$oFileManagerEnv =& getEnvObject();
		if($oFileManagerEnv->userIsInRootFolder())
		{
			$this->set_data('isCollective', true);
			$this->set_data('sDisabled', 'disabled');
		}
	}
	
	function handleEdition()
	{
		$this->set_data('isCollective', false);
		$this->set_data('isActive', true);
		$this->set_data('isAutoApprobation', false);
		$this->set_data('isFileNotify', false);
		$this->set_data('isVersioning', false);
		$this->set_data('isShow', true);
		$this->set_data('isAddTags', true);
		$this->set_data('sChecked', 'checked');
		$this->set_data('sDisabled', '');

		$this->get_data('iId', $iId);
		$this->get_data('sPath', $sPath);
		$this->get_data('sDirName', $sDirName);
		$this->set_data('sOldDirName', $sDirName);
		$this->get_data('iIdFolder', $iIdFolder);
		
		$oFileManagerEnv =& getEnvObject();
		$oFmFolder = $oFmFolder = BAB_FmFolderHelper::getFmFolderById($iIdFolder);
		if(!is_null($oFmFolder))
		{
			$sActive				= (string) $oFmFolder->getActive();
			$iIdApprobationScheme	= (int) $oFmFolder->getApprobationSchemeId();
			$sAutoApprobation		= (string) $oFmFolder->getAutoApprobation();
			$sNotification			= (string) $oFmFolder->getFileNotify();
			$sVersioning			= (string) $oFmFolder->getVersioning();
			$sDisplay				= (string) $oFmFolder->getHide();
			$sAddTags				= (string) $oFmFolder->getAddTags();

			$this->iApprobationSchemeId = $iIdApprobationScheme;
			$this->set_data('isCollective', true);
			$this->set_data('isActive', ('Y' === $sActive) ? true : false);
			$this->set_data('isAutoApprobation', ('Y' === $sAutoApprobation) ? true : false);
			$this->set_data('isFileNotify', ('Y' === $sNotification) ? true : false);
			$this->set_data('isVersioning', ('Y' === $sVersioning) ? true : false);
			$this->set_data('isShow', ('Y' === $sDisplay) ? false : true);
			$this->set_data('isAddTags', ('Y' === $sAddTags) ? true : false);
			$this->set_data('iIdFolder', $oFmFolder->getId());
			$this->set_data('sOldDirName', $oFmFolder->getName());
			$this->set_data('sChecked', '');
			
			if($oFileManagerEnv->userIsInRootFolder())
			{
				$this->set_data('sDisabled', 'disabled');
			}
		}
		$this->set_data('bDelete', canCreateFolder($oFileManagerEnv->sRelativePath));
	}
	
	function getNextApprobationScheme()
	{
		if(false !== $this->oAppSchemeRes)
		{
			global $babDB;
			$aDatas = $babDB->db_fetch_array($this->oAppSchemeRes);
			if(false !== $aDatas)
			{
				$this->set_data('iAppSchemeId', $aDatas['id']);
				$this->set_data('iAppSchemeName', $aDatas['name']);
				$this->set_data('sAppSchemeNameSelected', '');
				
				if($this->iApprobationSchemeId == $aDatas['id'])
				{
					$this->set_data('sAppSchemeNameSelected', 'selected="selected"');
				}
				
				return true;
			}
		}
		return false;
	}
	
	function printTemplate()
	{
		global $babBody;
		$this->set_data('sHtmlTable', bab_printTemplate($this, 'fileman.html', 'collectiveDir'));
		
		$this->raw_2_html(BAB_RAW_2_HTML_CAPTION);
		$babBody->addJavascriptFile($GLOBALS['babScriptPath']."prototype/prototype.js");
		return bab_printTemplate($this, 'fileman.html', 'displayFolderForm');
	}
}	


function listTrashFiles()
{
	global $babBody;

	$oFileManagerEnv =& getEnvObject();

	$babBody->title = bab_translate("Trash");
	
	$babBody->addItemMenu("list", bab_translate("Folders"), $GLOBALS['babUrlScript'] . 
		'?tg=fileman&idx=list&id=' . $oFileManagerEnv->iId . 
		'&gr=' . $oFileManagerEnv->sGr . '&path=' . urlencode($oFileManagerEnv->sPath));
	
	if(canUpload($oFileManagerEnv->sRelativePath))
	{
		$babBody->addItemMenu("add", bab_translate("Upload"), $GLOBALS['babUrlScript'] . 
			'?tg=fileman&idx=displayAddFileForm&id=' . $oFileManagerEnv->iId . 
			'&gr=' . $oFileManagerEnv->sGr . '&path=' . urlencode($oFileManagerEnv->sPath));
	}
	
	if(canManage($oFileManagerEnv->sRelativePath))
	{
		$babBody->addItemMenu("trash", bab_translate("Trash"), $GLOBALS['babUrlScript'] . 
			'?tg=fileman&idx=trash&id=' . $oFileManagerEnv->iId . 
			'&gr=' . $oFileManagerEnv->sGr . '&path=' . urlencode($oFileManagerEnv->sPath));
	}

	
	class temp
	{
		var $db;
		var $arrext = array();
		var $idfile;
		var $delete;
		var $restore;
		var $nametxt;
		var $modifiedtxt;
		var $sizetxt;
		var $postedtxt;
		var $oFolderFileSet = null;
		var $sPath = '';
		var $sRelativePath = '';
		var $sEndSlash = '';

		var $oFileManagerEnv = null;

		function temp()
		{
			$this->oFileManagerEnv =& getEnvObject();
			
			$this->id = $this->oFileManagerEnv->iId;
			$this->gr = $this->oFileManagerEnv->sGr;
			$this->sPath = $this->oFileManagerEnv->sPath;
			$this->bytes = bab_translate("bytes");
			$this->delete = bab_translate("Delete");
			$this->restore = bab_translate("Restore");
			$this->nametxt = bab_translate("Name");
			$this->sizetxt = bab_translate("Size");
			$this->modifiedtxt = bab_translate("Modified");
			$this->postedtxt = bab_translate("Posted by");
			$this->checkall = bab_translate("Check all");
			$this->uncheckall = bab_translate("Uncheck all");
			$this->selectTrashFile();
		}

		function selectTrashFile()
		{
			
			global $babDB, $babBody;
			$this->oFolderFileSet = new BAB_FolderFileSet();
			$oState =& $this->oFolderFileSet->aField['sState'];
			$oPathName =& $this->oFolderFileSet->aField['sPathName'];
			$oIdOwner =& $this->oFolderFileSet->aField['iIdOwner'];
			$oGroup =& $this->oFolderFileSet->aField['sGroup'];
			$oIdDgOwner =& $this->oFolderFileSet->aField['iIdDgOwner'];
			
			$oCriteria = $oState->in('D');
			$oCriteria = $oCriteria->_and($oPathName->like($babDB->db_escape_like($this->oFileManagerEnv->sRelativePath)));
			$oCriteria = $oCriteria->_and($oIdOwner->in($this->oFileManagerEnv->iIdObject));
			$oCriteria = $oCriteria->_and($oGroup->in($this->oFileManagerEnv->sGr));
			$oCriteria = $oCriteria->_and($oIdDgOwner->in(bab_getCurrentUserDelegation()));
			
			$this->oFolderFileSet->select($oCriteria, array('sName' => 'ASC'));
		}
		
		
		function getnextfile()
		{
			if(!is_null($this->oFolderFileSet) && $this->oFolderFileSet->count() > 0)
			{
				$oFolderFile = $this->oFolderFileSet->next();
				if(!is_null($oFolderFile))
				{
					$ext = substr(strrchr($oFolderFile->getName(), "."), 1);
					if(empty($this->arrext[$ext]))
					{
						$this->arrext[$ext] = bab_printTemplate($this, "config.html", ".".$ext);
					}
					if(empty($this->arrext[$ext]))
					{
						$this->arrext[$ext] = bab_printTemplate($this, "config.html", ".unknown");				
					}
					
					$this->fileimage = $this->arrext[$ext];
					$this->name = bab_toHtml($oFolderFile->getName());
					$this->idfile = $oFolderFile->getId();

					
					if(file_exists($this->oFileManagerEnv->getCurrentFmPath() . $oFolderFile->getName()))
					{
						$fstat = stat($this->oFileManagerEnv->getCurrentFmPath() . $oFolderFile->getName());
						$this->sizef = $fstat[7];
					}
					else
					{
						$this->sizef = "???";
					}
	
					$this->modified = bab_toHtml(bab_shortDate(bab_mktime($oFolderFile->getModifiedDate()), true));
					$this->postedby = bab_toHtml(bab_getUserName($oFolderFile->getModifierId() == 0 ? $oFolderFile->getAuthorId() : $oFolderFile->getModifierId()));
					return true;
				}
			}
			return false;
		}
	}

	$temp = new temp();
	$babBody->babecho(bab_printTemplate($temp,"fileman.html", "trashfiles"));
}

function showDiskSpace()
	{
	global $babBody;

	$oFileManagerEnv =& getEnvObject();

	$babBody->title = bab_translate("Trash");
	
	$babBody->addItemMenu("list", bab_translate("Folders"), $GLOBALS['babUrlScript'] . 
		'?tg=fileman&idx=list&id=' . $oFileManagerEnv->iId . 
		'&gr=' . $oFileManagerEnv->sGr . '&path=' . urlencode($oFileManagerEnv->sPath));
	
	if(canUpload($oFileManagerEnv->sRelativePath))
	{
		$babBody->addItemMenu("add", bab_translate("Upload"), $GLOBALS['babUrlScript'] . 
			'?tg=fileman&idx=displayAddFileForm&id=' . $oFileManagerEnv->iId . 
			'&gr=' . $oFileManagerEnv->sGr . '&path=' . urlencode($oFileManagerEnv->sPath));
	}
	
	if(canManage($oFileManagerEnv->sRelativePath))
	{
		$babBody->addItemMenu("trash", bab_translate("Trash"), $GLOBALS['babUrlScript'] . 
			'?tg=fileman&idx=trash&id=' . $oFileManagerEnv->iId . 
			'&gr=' . $oFileManagerEnv->sGr . '&path=' . urlencode($oFileManagerEnv->sPath));
	}
			
			
			
	class temp
		{
		var $id;
		var $gr;
		var $path;
		var $cancel;
		var $bytes;
		var $babCss;
		var $arrgrp = array();
		var $arrmgrp = array();
		var $countgrp;
		var $countmgrp;
		var $diskp;
		var $diskg;
		var $groupname;
		var $diskspace;
		var $allowedspace;
		var $remainingspace;
		var $grouptxt;
		var $diskspacetxt;
		var $allowedspacetxt;
		var $remainingspacetxt;
		var $oFileManagerEnv;
		function temp()
			{
			global $babBody;
			$oFileManagerEnv =& getEnvObject();
			
			$this->id = $oFileManagerEnv->iId;
			$this->gr = $oFileManagerEnv->sGr;
			$this->path = $oFileManagerEnv->sPath;
			
			$this->grouptxt = bab_translate("Name");
			$this->diskspacetxt = bab_translate("Used");
			$this->allowedspacetxt = bab_translate("Allowed");
			$this->remainingspacetxt = bab_translate("Remaining");
			$this->cancel = bab_translate("Close");
			$this->bytes = bab_translate("bytes");
			$this->kilooctet = " ".bab_translate("Kb");
			$this->babCss = bab_printTemplate($this,"config.html", "babCss");
			
			$this->oFileManagerEnv =& getEnvObject();

			$oFmFolderSet = new BAB_FmFolderSet();
			$oRelativePath =& $oFmFolderSet->aField['sRelativePath'];
			$oFmFolderSet->select($oRelativePath->in(''));
			
			while(null !== ($oFmFolder = $oFmFolderSet->next()))
			{
				if(bab_isAccessValid(BAB_FMMANAGERS_GROUPS_TBL, $oFmFolder->getId()))
				{
					$this->arrmgrp[] = 	$oFmFolder->getId();
				}
				else 
				{
					$sRelativePath = $oFmFolder->getName() . '/';
					if(canUpload($sRelativePath) || canUpdate($sRelativePath) || canDownload($sRelativePath))
					{
						$this->arrgrp[] = 	$oFmFolder->getId();
					}
				}
			}
				
			$oFileManagerEnv =& getEnvObject();
			if(!empty($GLOBALS['BAB_SESS_USERID']) && userHavePersonnalStorage())
				$this->diskp = 1;
			else
				$this->diskp = 0;
			if(!empty($GLOBALS['BAB_SESS_USERID'] ) && bab_isUserAdministrator())
				$this->diskg = 1;
			else
				$this->diskg = 0;
			$this->countgrp = count($this->arrgrp);
			$this->countmgrp = count($this->arrmgrp);
			}

		function getprivatespace()
			{
			static $i = 0;
			if( $i < $this->diskp)
				{
				$pathx = $this->oFileManagerEnv->getPersonnalFolderPath();
				$size = getDirSize($pathx);
				$this->diskspace = bab_toHtml(bab_formatSizeFile($size).$this->kilooctet);
				$this->allowedspace =  bab_toHtml(bab_formatSizeFile($GLOBALS['babMaxUserSize']).$this->kilooctet);
				$this->remainingspace =  bab_toHtml(bab_formatSizeFile($GLOBALS['babMaxUserSize'] - $size).$this->kilooctet);
				$this->groupname = bab_translate("Personal Folder");
				$i++;
				return true;
				}
			else
				return false;
			}

		function getglobalspace()
			{
			static $i = 0;
			if( $i < $this->diskg)
				{
				$size = getDirSize($this->oFileManagerEnv->getFmUploadPath());
				$this->diskspace = bab_toHtml(bab_formatSizeFile($size).$this->kilooctet);
				$this->allowedspace =  bab_toHtml(bab_formatSizeFile($GLOBALS['babMaxTotalSize']).$this->kilooctet);
				$this->remainingspace =  bab_toHtml(bab_formatSizeFile($GLOBALS['babMaxTotalSize'] - $size).$this->kilooctet);
				$this->groupname = bab_translate("Global space");
				$i++;
				return true;
				}
			else
				return false;
			}

		function getnextgrp(&$bSkip)
		{
			static $i = 0;
			if($i < $this->countgrp)
			{
				$this->groupname = 'B';
				$oFmFolder = BAB_FmFolderHelper::getFmFolderById($this->arrgrp[$i]);
				$i++;
				if(is_null($oFmFolder))
				{
					$bSkip = true;
					return true;
				}
				$this->groupname = $oFmFolder->getName();
				$pathx = BAB_FileManagerEnv::getCollectivePath($oFmFolder->getDelegationOwnerId()) . $oFmFolder->getName();
				$size = getDirSize($pathx);
				$this->diskspace = bab_toHtml(bab_formatSizeFile($size).$this->kilooctet);
				$this->allowedspace =  bab_toHtml(bab_formatSizeFile($GLOBALS['babMaxGroupSize']).$this->kilooctet);
				$this->remainingspace =  bab_toHtml(bab_formatSizeFile($GLOBALS['babMaxGroupSize'] - $size).$this->kilooctet);
				return true;
			}
			else
			{
				return false;
			}
		}

		function getnextmgrp(&$bSkip)
		{
			static $i = 0;
			if($i < $this->countmgrp)
			{
				$this->groupname = 'A';
				$oFmFolder = BAB_FmFolderHelper::getFmFolderById($this->arrmgrp[$i]);
				$i++;
				if(is_null($oFmFolder))
				{
					$bSkip = true;
					return true;
				}
					
				$this->groupname = $oFmFolder->getName();
				$pathx = BAB_FileManagerEnv::getCollectivePath($oFmFolder->getDelegationOwnerId()) . $oFmFolder->getName();
				$size = getDirSize($pathx);
				$this->diskspace = bab_toHtml(bab_formatSizeFile($size).$this->kilooctet);
				$this->allowedspace =  bab_toHtml(bab_formatSizeFile($GLOBALS['babMaxGroupSize']).$this->kilooctet);
				$this->remainingspace =  bab_toHtml(bab_formatSizeFile($GLOBALS['babMaxGroupSize'] - $size).$this->kilooctet);
				return true;
			}
			else
			{
				return false;
			}
		}

		}

	$temp = new temp();
	echo bab_printTemplate($temp,"fileman.html", "diskspace");
	exit;
	}


function listFiles()
	{
	global $babBody;

	class temp extends listFiles
		{
        var $bytes;
        var $mkdir;
        var $rename;
        var $delete;
        var $directory;
        var $cuttxt;
        var $paste;
        var $undo;
        var $deltxt;
        var $root;
        var $refresh;
        var $nametxt;
        var $sizetxt;
        var $modifiedtxt;
        var $postedtxt;
        var $diskspace;
        var $hitstxt;
        var $altreadonly;
        var $rooturl;
        var $refreshurl;
        var $urldiskspace;
        var $upfolderimg;
        var $usrfolderimg;
        var $grpfolderimg;
        var $manfolderimg;
        var $xres;
        var $xcount;
		var $block;
		var $blockauth;
		var $ovfurl;
		var $ovfhisturl;
		var $ovfcommiturl;
		var $bfvwait;

		var $sFolderFormAdd;
		var $sFolderFormEdit;
		var $sFolderFormUrl;
		var $sAddFolderFormUrl;
		var $bFolderUrl;
		
		var $sRight;
		var $sRightUrl;
		var $bRightUrl;
		
		var $sCutFolder;
		var $sCutFolderUrl;
		var $bCutFolderUrl;

		
		var $bCollectiveFolder = false;
		var $bCanBrowseFolder;
		var $bCanEditFolder;
		var $bCanSetRightOnFolder;
		var $bCanCutFolder;
		var $bCanCreateFolder;
		var $bCanManageFolder;
		
		
		var $altfilelog;
		var $altfilelock;
		var $altfileunlock;
		var $altfilewrite;
		var $altbg = false;

		var $bCanManageCurrentFolder = false;
		var $bDownload = false;
		var $bUpdate = false;

		var $sUploadPath = '';
		
		var $iCurrentUserDelegation = 0;
		var $bDisplayDelegationSelect = false;
		var $aVisibleDelegation = array();
		var $iIdDelegation = 0;
		var $sDelegationName = '';
		var $sDelegationSelected = '';
		var $sSubmit = 'Soumettre';
		
		function temp()
		{
			$this->listFiles();
			$this->bytes = bab_translate("bytes");
			$this->mkdir = bab_translate("Create");
			$this->rename = bab_translate("Rename");
			$this->delete = bab_translate("Delete");
			$this->directory = bab_translate("Directory");
			$this->download = bab_translate("Download");
			$this->cuttxt = bab_translate("Cut");
			$this->paste = bab_translate("Paste");
			$this->undo = bab_translate("Undo");
			$this->deltxt = bab_translate("Delete");
			$this->root = bab_translate("Home folder");
			$this->refresh = bab_translate("Refresh");
			$this->nametxt = bab_translate("Name");
			$this->sizetxt = bab_translate("Size");
			$this->modifiedtxt = bab_translate("Modified");
			$this->postedtxt = bab_translate("Posted by");
			$this->diskspace = bab_translate("Show disk space usage");
			$this->hitstxt = bab_translate("Hits");
            $this->altreadonly =  bab_translate("Read only");
            $this->sFolderFormAdd = bab_translate("Create a folder"); 
            $this->sFolderFormEdit = bab_translate("Edit folder"); 
            $this->sRight = bab_translate("Rights"); 
            $this->sCutFolder = bab_translate("Cut"); 
            $this->altfilelog =  bab_translate("View log");
            $this->altfilelock =  bab_translate("Edit file");
            $this->altfileunlock =  bab_translate("Unedit file");
            $this->altfilewrite =  bab_translate("Commit file");

			$iId = $this->oFileManagerEnv->iId;
			$sGr = $this->oFileManagerEnv->sGr;
            
			$this->rooturl = bab_toHtml($GLOBALS['babUrlScript']."?tg=fileman&idx=list");
			$this->refreshurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=fileman&idx=list&id=".$iId."&gr=".$sGr."&path=".urlencode($this->path));
			$this->urldiskspace = bab_toHtml($GLOBALS['babUrlScript']."?tg=fileman&idx=disk&id=".$iId."&gr=".$sGr."&path=".urlencode($this->path));
			
			$this->sAddFolderFormUrl = bab_toHtml($GLOBALS['babUrlScript']."?tg=fileman&idx=displayFolderForm&sFunction=createFolder&id=".$iId."&gr=".$sGr."&path=".urlencode($this->path));

			$this->sCutFolderUrl = '#'; 
			$this->bCutFolderUrl = false;

			$this->upfolderimg = bab_printTemplate($this, "config.html", "parentfolder");
			$this->usrfolderimg = bab_printTemplate($this, "config.html", "userfolder");
			$this->grpfolderimg = bab_printTemplate($this, "config.html", "groupfolder");
			$this->manfolderimg = bab_printTemplate($this, "config.html", "managerfolder");
			
			$sRelativePath = $this->oFileManagerEnv->sRelativePath;
			$this->bCanManageCurrentFolder = haveRightOn($sRelativePath, BAB_FMMANAGERS_GROUPS_TBL);


			$this->bDownload = canDownload($sRelativePath); 
			$this->bUpdate = canUpdate($sRelativePath);  
			$this->bCanCreateFolder = canCreateFolder($sRelativePath);


			$this->bVersion = (!is_null($this->oFileManagerEnv->oFmFolder) && 'Y' === $this->oFileManagerEnv->oFmFolder->getVersioning());
			
			
			if($this->oFileManagerEnv->userIsInPersonnalFolder())
			{
				$this->sUploadPath = $this->oFileManagerEnv->getRootFmPath();
			}
			else
			{
				$this->sUploadPath = $this->oFileManagerEnv->getCollectiveRootFmPath();
			}
			
			$this->xcount = 0;
			if($this->bCanManageCurrentFolder)
			{
				$this->selectCuttedFiles();
			}
			
			$this->aVisibleDelegation = bab_getUserFmVisibleDelegations();
			$this->bDisplayDelegationSelect = (count($this->aVisibleDelegation) > 1);
			$this->iCurrentUserDelegation = bab_getCurrentUserDelegation();
		}
		
		
		function getNextUserFmVisibleDelegation()
		{
			$aItem = each($this->aVisibleDelegation);
			if(false !== $aItem)
			{
				$this->iIdDelegation = $aItem['key'];
				$this->sDelegationName = $aItem['value'];
				$this->sDelegationSelected = '';
				global $babBody;
				
				if((int) $this->iCurrentUserDelegation === (int) $this->iIdDelegation)
				{
					$this->sDelegationSelected = 'selected="selected"';
				}
				
				return true;
			}
			return false;
		}
		
		
		function selectCuttedFiles()
		{
			global $babBody;
		
			$this->oFolderFileSet->bUseAlias = false;
			$oState = $this->oFolderFileSet->aField['sState'];
			$oGroup = $this->oFolderFileSet->aField['sGroup'];
			$oIdDgOwner = $this->oFolderFileSet->aField['iIdDgOwner'];
			$oIdOwner = $this->oFolderFileSet->aField['iIdOwner'];
			
			global $babDB;
			$oCriteria = $oGroup->in($this->oFileManagerEnv->sGr);
			$oCriteria = $oCriteria->_and($oState->in('X'));
			
			if($this->oFileManagerEnv->userIsInPersonnalFolder())
			{
				$oCriteria = $oCriteria->_and($oIdOwner->in($this->oFileManagerEnv->iId));
				$oCriteria = $oCriteria->_and($oIdDgOwner->in(0));
			}
			else 
			{
				$oCriteria = $oCriteria->_and($oIdDgOwner->in(bab_getCurrentUserDelegation()));
			}
			
			$this->oFolderFileSet->select($oCriteria);
//			bab_debug($this->oFolderFileSet->getSelectQuery($oCriteria)); 
			$this->xres = $this->oFolderFileSet->_oResult;
			$this->xcount = $this->oFolderFileSet->count();
			$this->oFolderFileSet->bUseAlias = true;
		}
		
		function getNextFolder()
		{
			$aItem = each($this->aFolders);
			if(false !== $aItem)
			{
				$aItem						= $aItem['value'];
				$iIdRootFolder				= $aItem['iIdUrl'];
				$iIdFolder					= $aItem['iId'];
				$this->bCollectiveFolder	= ('Y' === $aItem['sCollective']);
				$sEncodedPath				= urlencode($this->path);
				$sEncodedName				= urlencode($aItem['sName']);
				$sUrlEncodedPath			= urlencode($aItem['sUrlPath']);
				$sGr						= $aItem['sGr'];
				$sCollective				= $aItem['sCollective'];
				
				$this->bCanBrowseFolder		= $aItem['bCanBrowseFolder'];
				$this->bCanEditFolder		= $aItem['bCanEditFolder'];
				$this->bCanSetRightOnFolder	= $aItem['bCanSetRightOnFolder'];
				$this->bCanCutFolder		= $aItem['bCanCutFolder'];
				$this->bCanManageFolder		= $aItem['bCanManageFolder'];

				$this->sRightUrl = bab_toHtml($GLOBALS['babUrlScript'] . '?tg=fileman&idx=displayRightForm&id=' . $iIdRootFolder . 
					'&gr=' . $this->oFileManagerEnv->sGr . '&path=' . $sEncodedPath . '&iIdFolder=' . $iIdFolder);

				$this->sFolderFormUrl = bab_toHtml($GLOBALS['babUrlScript'] . '?tg=fileman&idx=displayFolderForm&sFunction=editFolder&id=' . $iIdRootFolder . 
					'&gr=' . $this->oFileManagerEnv->sGr . '&path=' . $sEncodedPath . '&sDirName=' . $sEncodedName . '&iIdFolder=' . $iIdFolder);
					
				$this->sCutFolderUrl = bab_toHtml($GLOBALS['babUrlScript'] . '?tg=fileman&sAction=cutFolder&id=' . $iIdRootFolder . 
					'&gr=' . $this->oFileManagerEnv->sGr . '&path=' . $sEncodedPath . '&sDirName=' . $sEncodedName);

				$this->url = bab_toHtml($GLOBALS['babUrlScript'] . '?tg=fileman&idx=list&id=' . $iIdRootFolder . '&gr=' . $sGr . '&path=' . $sUrlEncodedPath);
				
				$this->altbg = !$this->altbg;
				$this->name = $aItem['sName'];
				return true;
			}
			return false;
		}

		function getNextCuttedFolder()
		{
			$aItem = each($this->aCuttedDir);
			if(false !== $aItem)
			{
				$aItem						= $aItem['value'];
				$iIdRootFolder				= $aItem['iIdUrl'];
				$iIdFolder					= $aItem['iId'];
				$this->bCollectiveFolder	= ('Y' == $aItem['sCollective']);
				$sEncodedPath				= urlencode($this->path);
				$sEncodedName				= urlencode($aItem['sName']);
				$sUrlEncodedPath			= urlencode($aItem['sUrlPath']);
				$sGr						= $aItem['sGr'];
				$sCollective				= $aItem['sCollective'];
				
				$iIdSrcRootFolder			= $aItem['iIdSrcRootFolder'];
				$sEncodedSrcPath			= urlencode($aItem['sSrcPath']);
				
				$this->bCanBrowseFolder		= $aItem['bCanBrowseFolder'];
				$this->bCanEditFolder		= $aItem['bCanEditFolder'];
				$this->bCanSetRightOnFolder	= $aItem['bCanSetRightOnFolder'];
				$this->bCanCutFolder		= $aItem['bCanCutFolder'];
				$this->bCanManageFolder		= $aItem['bCanManageFolder'];

				
				$this->sRightUrl = bab_toHtml($GLOBALS['babUrlScript'] . '?tg=fileman&idx=displayRightForm&id=' . $iIdRootFolder . 
					'&gr=' . $this->oFileManagerEnv->sGr . '&path=' . $sEncodedPath . '&iIdFolder=' . $iIdFolder);
					
				$this->sFolderFormUrl = bab_toHtml($GLOBALS['babUrlScript'] . '?tg=fileman&idx=displayFolderForm&sFunction=editFolder&id=' . $iIdRootFolder . 
					'&gr=' . $this->oFileManagerEnv->sGr . '&path=' . $sEncodedPath . '&sDirName=' . $sEncodedName . '&iIdFolder=' . $iIdFolder);
				
				$this->pasteurl = bab_toHtml($GLOBALS['babUrlScript'] . '?tg=fileman&sAction=pasteFolder&id=' . $iIdRootFolder . 
					'&gr=' . $this->oFileManagerEnv->sGr . '&path=' . urlencode($this->oFileManagerEnv->sPath) . 
					'&iIdSrcRootFolder=' . $iIdSrcRootFolder . '&sSrcPath=' . $sEncodedSrcPath);
				
				$this->url = bab_toHtml($GLOBALS['babUrlScript'] . '?tg=fileman&idx=list&id=' . $iIdSrcRootFolder . '&gr=' . $sGr . '&path=' . $sEncodedSrcPath);
				
				$this->altbg = !$this->altbg;
				$this->name = $aItem['sName'];
				return true;
			}
			return false;
		}
		
		function updateFileInfo($arr)
			{
			$ext = strtolower(substr(strrchr($arr['name'], "."), 1));
			if( !empty($ext) && empty($this->arrext[$ext]))
				{
				$this->arrext[$ext] = bab_printTemplate($this, "config.html", ".".$ext);
				if( empty($this->arrext[$ext]))
					$this->arrext[$ext] = bab_printTemplate($this, "config.html", ".unknown");						
				$this->fileimage = $this->arrext[$ext];
				}
			else if( empty($ext))
				{
				$this->fileimage = bab_printTemplate($this, "config.html", ".unknown");				
				}
			else
				$this->fileimage = $this->arrext[$ext];
			$this->name = $arr['name'];
			
			$sFullPathName = $this->sUploadPath . $arr['path'] . $arr['name'];
			if( file_exists($sFullPathName))
				{
				$fstat = stat($sFullPathName);
				$this->sizef = bab_toHtml(bab_formatSizeFile($fstat[7])." ".bab_translate("Kb"));
				}
			else
				$this->sizef = "???";

			$this->modified = bab_toHtml(bab_shortDate(bab_mktime($arr['modified']), true));
			$this->postedby = bab_toHtml(bab_getUserName($arr['modifiedby'] == 0? $arr['author']: $arr['modifiedby']));
			$this->hits = bab_toHtml($arr['hits']);
			if( $arr['readonly'] == "Y" )
				$this->readonly = "R";
			else
				$this->readonly = "";
			}

		function getnextfile()
		{
			global $babDB;
			if(false !== $this->res && false !== ($arr	= $babDB->db_fetch_array($this->res)))
			{
				$this->altbg		= !$this->altbg;
				$iId				= $this->oFileManagerEnv->iId;
				$sGr				= $this->oFileManagerEnv->sGr;
				$this->bconfirmed	= 0;
				$this->description	= bab_toHTML($arr['description']);
				$ufile				= urlencode($arr['name']);
				$upath				= urlencode($this->path);

				$sUrlBase		= $GLOBALS['babUrlScript'] . '?tg=fileman&id=' . $iId . '&gr=' . $sGr . '&path=' . $upath;
				$sUrlFileId		= $sUrlBase . '&idf=' . $arr['id'];
				$sUrlFileName	= $sUrlBase . '&file=' . $ufile;
				$sUrlFile		= $sUrlBase . '&idf=' . $arr['id'] . '&file=' . $ufile;

				$this->viewurl	= bab_toHtml($sUrlFile . '&idx=viewFile');
				$this->urlget	= bab_toHtml($sUrlFile . '&sAction=getFile');
				$this->cuturl	= bab_toHtml($sUrlFile . '&sAction=cutFile');
				$this->delurl	= bab_toHtml($sUrlFile . '&sAction=delFile');
				
				$this->updateFileInfo($arr);
				
				if($this->bVersion)
				{
					$sUrlBase		= $GLOBALS['babUrlScript'] . '?tg=filever&id=' . $iId . '&gr=' . $sGr . '&path=' . $upath;
					$sUrlFileId		= $sUrlBase . '&idf=' . $arr['id'];
					$sUrlFileName	= $sUrlBase . '&file=' . $ufile;
					$sUrlFile		= $sUrlBase . '&idf=' . $arr['id'] . '&file=' . $ufile;
				
					$this->lastversion	= bab_toHtml($arr['ver_major'] . '.' . $arr['ver_minor']);
					$this->ovfhisturl	= bab_toHtml($sUrlFileId . '&idx=hist');
					$this->ovfversurl	= bab_toHtml($sUrlFileId . '&idx=lvers');
				
					$this->bfvwait = false;
					$this->blockauth = false;
					if($arr['edit'])
					{
						$this->block = true;
						list($lockauthor, $idfvai) = $babDB->db_fetch_array($babDB->db_query("select author, idfai from ".BAB_FM_FILESVER_TBL." where id='".$babDB->db_escape_string($arr['edit'])."'"));
						if($idfvai == 0 && $lockauthor == $GLOBALS['BAB_SESS_USERID'])
						{
							$this->blockauth = true;
						}

						if($idfvai != 0 && $this->buaf)
						{
							$this->bfvwait = true;
							$this->bupdate = true;
						}
						
						$this->ovfurl = bab_toHtml($sUrlFileId . '&idx=unlock');
						if($this->bfvwait)
						{
							$this->ovfcommiturl = bab_toHtml($sUrlFileId . '&idx=conf');
						}
						else
						{
							$this->ovfcommiturl = bab_toHtml($sUrlFileId . '&idx=commit');
						}
					}
					else
					{
						$this->block = false;
						$this->ovfurl = bab_toHtml($sUrlFileId . '&idx=lock');
					}
				}
				return true;
			}
			else
			{
				return false;
			}
		}

		function getnextwfile()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->countwf)
				{
					$iId = $this->oFileManagerEnv->iId;
					$sGr = $this->oFileManagerEnv->sGr;
					
				$this->altbg = !$this->altbg;
				$arr = $babDB->db_fetch_array($this->reswf);
				$this->bconfirmed = 1;
				$this->updateFileInfo($arr);
				$this->description = bab_toHTML($arr['description']);
				$ufile = urlencode($arr['name']);
				$upath = urlencode($this->path);
				$this->url = bab_toHtml($GLOBALS['babUrlScript']."?tg=fileman&idx=upd&id=".$iId."&gr=".$sGr."&path=".$upath."&file=".$ufile);
				$this->viewurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=fileman&idx=viewFile&idf=".$arr['id']."&id=".$iId."&gr=".$sGr."&path=".$upath."&file=".$ufile);
				$this->urlget = bab_toHtml($GLOBALS['babUrlScript'] . '?tg=fileman&sAction=getFile&id=' . $iId . '&gr=' . $sGr . '&path=' . $upath . '&file=' . $ufile.'&idf='.$arr['id']);
				$this->cuturl = bab_toHtml($GLOBALS['babUrlScript'] . '?tg=fileman&sAction=cutFile&id=' . $iId . '&gr=' . $sGr . '&path=' . $upath . '&file=' . $ufile);
				$this->delurl = bab_toHtml($GLOBALS['babUrlScript'] . '?tg=fileman&sAction=delFile&id=' . $iId . '&gr=' . $sGr . '&path=' . $upath . '&file=' . $ufile);
				$i++;
				return true;
				}
			else
				return false;
			}


		function getnextxfile(&$bSkip)
		{
			global $babDB;
			static $i = 0;
			if($i < $this->xcount)
			{
				$iId = $this->oFileManagerEnv->iId;
				$sGr = $this->oFileManagerEnv->sGr;
					
				$this->altbg = !$this->altbg;
				$arr = $babDB->db_fetch_array($this->xres);
				$this->bconfirmed = 0;
	
				$iIdSrcRootFolder = 0;
				if($this->oFileManagerEnv->userIsInCollectiveFolder())
				{
					$oFmFolder = null;
					BAB_FmFolderHelper::getInfoFromCollectivePath($arr['path'], $iIdSrcRootFolder, $oFmFolder);
				}
				else if($this->oFileManagerEnv->userIsInPersonnalFolder())
				{
					$iIdSrcRootFolder = $iId;
				}
				
//				bab_debug(__LINE__ . ' ' . basename(__FILE__) . ' ' . __FUNCTION__ . ' sSrcPath ==> ' . $arr['path'] . ' sTrgPath ==> ' . $this->path);
				
				$bCanPaste = canPasteFile($iIdSrcRootFolder, $arr['path'], $iId, $this->path, $arr['name']);
				$bSkip = !$bCanPaste;	
				if($bCanPaste)				
				{
					$this->updateFileInfo($arr);
					$this->description = bab_toHTML($arr['description']);
					$ufile = urlencode($arr['name']);
					$upath = '';
					if(strlen(trim($arr['path'])) > 0)
					{
						$upath = urlencode((string) substr($arr['path'], 0, -1));
					}
					$this->url = bab_toHtml($GLOBALS['babUrlScript']."?tg=fileman&idx=upd&id=".$iId."&gr=".$sGr."&path=".$upath."&file=".$ufile);
					$this->urlget = bab_toHtml($GLOBALS['babUrlScript']."?tg=fileman&sAction=getFile&id=".$iId."&gr=".$sGr."&path=".$upath."&file=".$ufile.'&idf='.$arr['id']);
					
					$this->pasteurl = bab_toHtml($GLOBALS['babUrlScript'].'?tg=fileman&idx=list&sAction=pasteFile&id=' . $iId . '&gr=' . $sGr . 
						'&path=' . urlencode($this->path) . '&iIdSrcRootFolder=' . $iIdSrcRootFolder . '&sSrcPath=' . $upath . '&file=' . $ufile);
				}
				$i++;
				return true;
			}
			else
				return false;
		}

	}
	$oFileManagerEnv =& getEnvObject();

	$temp = new temp();
	$babBody->title = bab_translate("File manager");
	$babBody->addItemMenu("list", bab_translate("Folders"), $GLOBALS['babUrlScript']."?tg=fileman&idx=list&id=".$oFileManagerEnv->iId."&gr=".$oFileManagerEnv->sGr."&path=".urlencode($oFileManagerEnv->sPath));
	
	if('Y' === $oFileManagerEnv->sGr)
	{
		if(0 !== $oFileManagerEnv->iId)
		{
			$GLOBALS['babWebStat']->addFolder($oFileManagerEnv->iId);
		}
	}
	
	$sParentPath = $oFileManagerEnv->sRelativePath;
	
	if(canUpload($sParentPath)) 
	{
		$babBody->addItemMenu("add", bab_translate("Upload"), $GLOBALS['babUrlScript']."?tg=fileman&idx=displayAddFileForm&id=".$oFileManagerEnv->iId."&gr=".$oFileManagerEnv->sGr."&path=".urlencode($oFileManagerEnv->sPath));
	}
	
	if(haveRightOn($sParentPath, BAB_FMMANAGERS_GROUPS_TBL)) 
	{
		$babBody->addItemMenu("trash", bab_translate("Trash"), $GLOBALS['babUrlScript']."?tg=fileman&idx=trash&id=".$oFileManagerEnv->iId."&gr=".$oFileManagerEnv->sGr."&path=".urlencode($oFileManagerEnv->sPath));
	}

	$babBody->babecho(bab_printTemplate($temp,"fileman.html", "fileslist"));
	return $temp->count;
}


function displayAddFileForm()
{
	global $babBody, $BAB_SESS_USERID;

	$oFileManagerEnv =& getEnvObject();
	$babBody->title = bab_translate("Upload file to") . ' ' . $oFileManagerEnv->sRelativePath;

	if(!canUpload($oFileManagerEnv->sRelativePath))
	{
		$babBody->msgerror = bab_translate("Access denied");
		return;
	}

	$babBody->addItemMenu("list", bab_translate("Folders"), $GLOBALS['babUrlScript'] . 
		'?tg=fileman&idx=list&id=' . $oFileManagerEnv->iId . "&gr=" . $oFileManagerEnv->sGr . 
		'&path=' . urlencode($oFileManagerEnv->sPath));
	
	$babBody->addItemMenu("displayAddFileForm", bab_translate("Upload"), $GLOBALS['babUrlScript'] . 
		'?tg=fileman&idx=displayAddFileForm&id=' . $oFileManagerEnv->iId . '&gr=' . $oFileManagerEnv->sGr . 
		'&path=' . urlencode($oFileManagerEnv->sPath));
		
	if(canManage($oFileManagerEnv->sRelativePath)) 
	{
		$babBody->addItemMenu("trash", bab_translate("Trash"), $GLOBALS['babUrlScript'] . 
			'?tg=fileman&idx=trash&id=' . $oFileManagerEnv->iId . "&gr=" . $oFileManagerEnv->sGr . 
			'&path=' . urlencode($oFileManagerEnv->sPath));
	}
		
	class temp
	{
		var $name;
		var $description;
		var $keywords;
		var $add;
		var $attribute;
		var $path;
		var $id;
		var $gr;
		var $yes;
		var $no;
		var $maxfilesize;
		var $descval;
		var $keysval;
		var $field;
		var $fieldname;
		var $fieldval;
		var $count;
		var $res;

		function temp()
		{
			global $babBody, $babDB;
			$this->name = bab_translate("Name");
			$this->description = bab_translate("Description");
			$this->keywords = bab_translate("Keywords");
			$this->add = bab_translate("Add");
			$this->attribute = bab_translate("Read only");
			$this->yes = bab_translate("Yes");
			$this->no = bab_translate("No");
			$this->t_warnmaxsize = bab_translate("File size must not exceed");
			$this->t_add_field = bab_translate("Attach another file");
			$this->t_remove_field = bab_translate("Remove");
			if($GLOBALS['babMaxFileSize'] < 1000000)
			{
				$this->maxsize =  bab_formatSizeFile($GLOBALS['babMaxFileSize'])." ".bab_translate("Kb");
			}
			else
			{
				$this->maxsize =  floor($GLOBALS['babMaxFileSize'] / 1000000 )." ".bab_translate("Mb");
			}
			
			$description = bab_pp('description', null);
			$keywords = bab_pp('keywords', null);
			
			$oFileManagerEnv =& getEnvObject();
			
			$this->id = $oFileManagerEnv->iId;
			$this->path = bab_toHtml($oFileManagerEnv->sPath);
			$this->gr = $oFileManagerEnv->sGr;
			
			$this->maxfilesize = $GLOBALS['babMaxFileSize'];
			$this->descval = (!is_null($description)) ? bab_toHtml($description[0]) : '';
			$this->keysval = (!is_null($keywords)) ? bab_toHtml($keywords[0]) : '';
			if($this->gr == 'Y')
			{
				$this->res = $babDB->db_query("select * from ".BAB_FM_FIELDS_TBL." where id_folder='".$babDB->db_escape_string($this->id)."'");
				$this->count = $babDB->db_num_rows($this->res);
			}
			else
			{
				$this->count = 0;
			}
			$babBody->addJavascriptFile($GLOBALS['babScriptPath'].'prototype/prototype.js');
			$babBody->addJavascriptFile($GLOBALS['babScriptPath'].'scriptaculous/scriptaculous.js');
			$babBody->addStyleSheet('ajax.css');
		}
		

		function getnextfield()
		{
			global $babDB;
			static $i = 0;
			if($i < $this->count)
			{
				$arr = $babDB->db_fetch_array($this->res);
				$this->fieldname = bab_translate($arr['name']);
				$this->field = 'field'.$arr['id'];
				$this->fieldval = bab_toHtml($arr['defaultval']);
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
	$babBody->babecho(bab_printTemplate($temp, 'fileman.html', "addfile"));
}


function getFile()
{
	global $babBody;
	
	$inl = bab_rp('inl', false);
	if(false === $inl) 
	{
		$inl = bab_getFileContentDisposition() == 1 ? 1 : '';
	}
	
	$iIdFile = (int) bab_rp('idf', 0);
	
	//OVML ne positionne pas la délégation
	$oFolderFileSet = new BAB_FolderFileSet();
	$oId = $oFolderFileSet->aField['iId'];
	$oFolderFile = $oFolderFileSet->get($oId->in($iIdFile));
	if(!is_null($oFolderFile))
	{
		//Peut être vient-on de l'OVML
		$iCurrentDelegation = bab_getCurrentUserDelegation();
		bab_setCurrentUserDelegation($oFolderFile->getDelegationOwnerId());
	
		$oFileManagerEnv =& getEnvObject();

		if(canDownload($oFileManagerEnv->sRelativePath))
		{
			$oFolderFile->setHits($oFolderFile->getHits() + 1);
			$oFolderFile->save();

			$GLOBALS['babWebStat']->addFilesManagerFile($oFolderFile->getId());
			
			$sUploadPath = '';
			if(!$oFileManagerEnv->userIsInPersonnalFolder())
			{
				$sUploadPath = $oFileManagerEnv->getCollectiveRootFmPath();
			}
			else 
			{
				$sUploadPath = $oFileManagerEnv->getRootFmPath();
			}
			
			$sFullPathName = $sUploadPath . $oFolderFile->getPathName() . $oFolderFile->getName();
			$mime = bab_getFileMimeType($sFullPathName);
			
			if(file_exists($sFullPathName))
			{
				$fsize = filesize($sFullPathName);
				
				set_time_limit(3600);
				
				if(strtolower(bab_browserAgent()) == "msie")
				{
					header('Cache-Control: public');
				}
				$sName = $oFolderFile->getName();
				if($inl == "1")
				{
					header("Content-Disposition: inline; filename=\"$sName\""."\n");
				}
				else
				{
					header("Content-Disposition: attachment; filename=\"$sName\""."\n");
				}
				
				header("Content-Type: $mime"."\n");
				header("Content-Length: ". $fsize."\n");
				header("Content-transfert-encoding: binary"."\n");
				$fp=fopen($sFullPathName, "rb");
				if($fp) 
				{
					while(!feof($fp)) 
					{
						print fread($fp, 8192);
					}
					fclose($fp);
					
					bab_setCurrentUserDelegation($iCurrentDelegation);
					exit;
				}
			}
			else
			{
				bab_setCurrentUserDelegation($iCurrentDelegation);
				
				$babBody->msgerror = bab_translate("The file is not on the server");
			}
		}
		else 
		{
			bab_setCurrentUserDelegation($iCurrentDelegation);
		
			$babBody->msgerror = bab_translate("Access denied");
			return;
		}
	}	
	else
	{
		$babBody->msgerror = bab_translate("The file is not on the server");
	}
}


function cutFile()
{
	global $babBody, $babDB;
	
	$oFileManagerEnv =& getEnvObject();

	if(!canCutFile($oFileManagerEnv->sRelativePath))
	{
		$babBody->msgerror = bab_translate("Access denied");
		return false;
	}

	$file = bab_gp('file');
	
	$oFolderFileSet = new BAB_FolderFileSet();
	
	$oIdOwner =& $oFolderFileSet->aField['iIdOwner'];
	$oGroup =& $oFolderFileSet->aField['sGroup'];
	$oState =& $oFolderFileSet->aField['sState'];
	$oPathName =& $oFolderFileSet->aField['sPathName'];
	$oName =& $oFolderFileSet->aField['sName'];
	$oIdDgOwner =& $oFolderFileSet->aField['iIdDgOwner'];
	
	$oCriteria = $oIdOwner->in($oFileManagerEnv->iIdObject);
	$oCriteria = $oCriteria->_and($oGroup->in($oFileManagerEnv->sGr));
	$oCriteria = $oCriteria->_and($oState->in(''));
	$oCriteria = $oCriteria->_and($oPathName->in($oFileManagerEnv->sRelativePath));
	$oCriteria = $oCriteria->_and($oName->in($file));
	$oCriteria = $oCriteria->_and($oIdDgOwner->in(bab_getCurrentUserDelegation()));
	
	$oFolderFile = $oFolderFileSet->get($oCriteria);
	if(!is_null($oFolderFile))
	{
		$oFolderFile->setState('X');
		$oFolderFile->save();
		return true;
	}
	return false;
}

function delFile()
{
	global $babBody, $babDB;
	
//	bab_rp('file'), $id, $gr, $path, $bmanager

	$oFileManagerEnv =& getEnvObject();

	if(!canDelFile($oFileManagerEnv->sRelativePath))
	{
		$babBody->msgerror = bab_translate("Access denied");
		return false;
	}

	$sFilename = (string) bab_gp('file', '');
	
	$oFolderFileSet = new BAB_FolderFileSet();
	
	$oIdOwner =& $oFolderFileSet->aField['iIdOwner'];
	$oGroup =& $oFolderFileSet->aField['sGroup'];
	$oState =& $oFolderFileSet->aField['sState'];
	$oPathName =& $oFolderFileSet->aField['sPathName'];
	$oName =& $oFolderFileSet->aField['sName'];
	
	$oCriteria = $oIdOwner->in($oFileManagerEnv->iIdObject);
	$oCriteria = $oCriteria->_and($oGroup->in($oFileManagerEnv->sGr));
	$oCriteria = $oCriteria->_and($oState->in(''));
	$oCriteria = $oCriteria->_and($oPathName->in($oFileManagerEnv->sRelativePath));
	$oCriteria = $oCriteria->_and($oName->in($sFilename));
	
	$oFolderFile = $oFolderFileSet->get($oCriteria);
	if(!is_null($oFolderFile))
	{
		$oFolderFile->setState('D');
		$oFolderFile->save();
		return true;
	}
	return false;
}


function pasteFile()
{
	global $babBody, $babDB;
	
	$oFileManagerEnv =& getEnvObject();
	
	$iIdSrcRootFolder	= (int) bab_gp('iIdSrcRootFolder', 0);
	$iIdTrgRootFolder	= $oFileManagerEnv->iId;				
	$sSrcPath			= (string) bab_gp('sSrcPath', '');
	$sTrgPath			= $oFileManagerEnv->sPath; 
	$sFileName			=  (string) bab_gp('file', '');
	$sUpLoadPath		= $oFileManagerEnv->getRootFmPath();
	
	if(canPasteFile($iIdSrcRootFolder, $sSrcPath, $iIdTrgRootFolder, $sTrgPath, $sFileName))
	{
//		bab_debug(__LINE__ . ' ' . basename(__FILE__) . ' ' . __FUNCTION__ . ' Paste OK');	
		
		$iOldIdOwner		= $iIdSrcRootFolder;
		$iNewIdOwner		= $iIdTrgRootFolder;
		$sOldRelativePath	= '';
		$sNewRelativePath	= '';
	
		if($oFileManagerEnv->userIsInPersonnalFolder())
		{
			$sOldEndPath = (strlen(trim($sSrcPath)) > 0) ? '/' : '';
			$sNewEndPath = (strlen(trim($sTrgPath)) > 0) ? '/' : '';
			
			$sOldRelativePath = $sSrcPath . $sOldEndPath;
			$sNewRelativePath = $sTrgPath . $sNewEndPath;
		}
		else if($oFileManagerEnv->userIsInCollectiveFolder()) 
		{
			$oFmFolder = null;
			BAB_FmFolderHelper::getFileInfoForCollectiveDir($iIdSrcRootFolder, $sSrcPath, $iOldIdOwner, $sOldRelativePath, $oFmFolder);
			BAB_FmFolderHelper::getFileInfoForCollectiveDir($iIdTrgRootFolder, $sTrgPath, $iNewIdOwner, $sNewRelativePath, $oFmFolder);
		}
		
		$sOldFullPathName = $sUpLoadPath . $sOldRelativePath . $sFileName;
		$sNewFullPathName = $sUpLoadPath . $sNewRelativePath . $sFileName;
		
//		bab_debug('sFileName ==> ' . $sFileName . ' iOldIdOwner ==> ' . $iOldIdOwner . 
//			' sOldRelativePath ==> ' . $sOldRelativePath . ' iNewIdOwner ==> ' . $iNewIdOwner .
//			' sNewRelativePath ==> ' . $sNewRelativePath);	
//			
//		bab_debug('sOldFullPathName ==> ' . $sUpLoadPath . $sOldRelativePath . $sFileName);
//		bab_debug('sNewFullPathName ==> ' . $sUpLoadPath . $sNewRelativePath . $sFileName);
//		bab_debug('sUpLoadPath ==> ' . $sUpLoadPath);
		
		$oFolderFileSet	= new BAB_FolderFileSet();
		$oIdOwner		=& $oFolderFileSet->aField['iIdOwner'];
		$oGroup			=& $oFolderFileSet->aField['sGroup'];
		$oPathName		=& $oFolderFileSet->aField['sPathName'];
		$oName			=& $oFolderFileSet->aField['sName'];
		$oIdDgOwner		=& $oFolderFileSet->aField['iIdDgOwner'];
		
		if($sOldFullPathName === $sNewFullPathName)
		{
			$oCriteria = $oIdOwner->in($iOldIdOwner);
			$oCriteria = $oCriteria->_and($oGroup->in($oFileManagerEnv->sGr));
			$oCriteria = $oCriteria->_and($oPathName->in($sOldRelativePath));
			$oCriteria = $oCriteria->_and($oName->in($sFileName));
			$oCriteria = $oCriteria->_and($oIdDgOwner->in(bab_getCurrentUserDelegation()));
			
			$oFolderFile = $oFolderFileSet->get($oCriteria);
			if(!is_null($oFolderFile))
			{
				$oFolderFile->setState('');
				$oFolderFile->save();
				return true;
			}
		}

		if(rename($sOldFullPathName, $sNewFullPathName))
		{
			$oCriteria = $oIdOwner->in($iOldIdOwner);
			$oCriteria = $oCriteria->_and($oGroup->in($oFileManagerEnv->sGr));
			$oCriteria = $oCriteria->_and($oPathName->in($sOldRelativePath));
			$oCriteria = $oCriteria->_and($oName->in($sFileName));
			$oCriteria = $oCriteria->_and($oIdDgOwner->in(bab_getCurrentUserDelegation()));
			
			$oFolderFile = $oFolderFileSet->get($oCriteria);
			if(!is_null($oFolderFile))
			{
				$oFolderFile->setState('');
				$oFolderFile->setOwnerId($iNewIdOwner);
				$oFolderFile->setPathName($sNewRelativePath);
				$oFolderFile->save();
				
				if(is_dir($sUpLoadPath . $sOldRelativePath . BAB_FVERSION_FOLDER . '/'))
				{
					if(!is_dir($sUpLoadPath . $sNewRelativePath . BAB_FVERSION_FOLDER . '/'))
					{
						bab_mkdir($sUpLoadPath . $sNewRelativePath . BAB_FVERSION_FOLDER, $GLOBALS['babMkdirMode']);
					}
				}
				
				$oFolderFileVersionSet = new BAB_FolderFileVersionSet();
				$oIdFile =& $oFolderFileVersionSet->aField['iIdFile'];

				$sFn = $sFileName;			
				$oFolderFileVersionSet->select($oIdFile->in($oFolderFile->getId()));
				while(null !== ($oFolderFileVersion = $oFolderFileVersionSet->next()))
				{
					$sFileName = $oFolderFileVersion->getMajorVer() . ',' . $oFolderFileVersion->getMinorVer() . ',' . $sFn;
					$sSrc = $sUpLoadPath . $sOldRelativePath . BAB_FVERSION_FOLDER . '/' . $sFileName;
					$sTrg = $sUpLoadPath . $sNewRelativePath . BAB_FVERSION_FOLDER . '/' . $sFileName;
					rename($sSrc, $sTrg);
				}
			}
			return true;
		}
	}
	else 
	{
		//bab_debug(__LINE__ . ' ' . basename(__FILE__) . ' ' . __FUNCTION__ . ' Cannot Paste');	
		$babBody->msgerror = bab_translate("Cannot paste file");
		return false;
	}
	return;
}

function viewFile()
{
	global $babBody, $babDB, $BAB_SESS_USERID;
	class temp
	{
		var $name;
		var $description;
		var $keywords;
		var $add;
		var $attribute;
		var $path;
		var $id;
		var $gr;
		var $yes;
		var $no;
		var $descval;
		var $keysval;
		var $descvalhtml;
		var $keysvalhtml;
		var $confirm;
		var $confirmno;
		var $confirmyes;
		var $idf;

		var $fmodified;
		var $fpostedby;
		var $fmodifiedtxt;
		var $fpostedbytxt;
		var $fcreatedtxt;
		var $fcreated;
		var $fmodifiedbytxt;
		var $fmodifiedby;
		var $fsizetxt;
		var $fsize;
		var $movetofolder;
		var $oFmFolderSet = null;
		
		var $field;
		var $resff;
		var $countff;
		var $fieldval;
		var $fieldid;
		var $fieldvalhtml;

		function temp($oFmFolder, $oFolderFile, $bmanager, $access, $bconfirm, $bupdate, $bdownload, $bversion)
		{
			global $babBody, $babDB;
			$this->access = $access;
			if($access)
			{
				$oFileManagerEnv =& getEnvObject();
				
				$this->bmanager = $bmanager;
				$this->bconfirm = $bconfirm;
				$this->bupdate = $bupdate;
				$this->bdownload = $bdownload;
				if($bconfirm || $bmanager || $bupdate)
				{
					$this->bsubmit = true;
				}
				else
				{
					$this->bsubmit = false;
				}
				$this->idf = $oFolderFile->getId();

				$this->description = bab_translate("Description");
				$this->t_keywords = bab_translate("Keywords");
				$this->keywords = bab_translate("Keywords");
				$this->notify = bab_translate("Notify members group");
				$this->t_yes = bab_translate("Yes");
				$this->t_no = bab_translate("No");
				$this->t_change_all = bab_translate("Change status for all versions");
				$this->tabIndexStatus = array(BAB_INDEX_STATUS_NOINDEX, BAB_INDEX_STATUS_INDEXED, BAB_INDEX_STATUS_TOINDEX);

				$this->id = $oFileManagerEnv->iId;
				
				$this->gr = $oFolderFile->getGroup();
				$this->path = bab_toHtml($oFileManagerEnv->sPath);
				$this->file = bab_toHtml($oFolderFile->getName());
				$GLOBALS['babBody']->setTitle($oFolderFile->getName() .( ($bversion == 'Y') ? ' (' . $oFolderFile->getMajorVer() . '.' . $oFolderFile->getMinorVer() . ')' : '' ));
				$this->descval = $oFolderFile->getDescription();
				$this->descvalhtml = bab_toHtml($oFolderFile->getDescription());

				$this->keysval = '';
				$res = $babDB->db_query("select tag_name from ".BAB_TAGS_TBL." tt left join ".BAB_FILES_TAGS_TBL." ftt on tt.id=ftt.id_tag where id_file=".$babDB->quote($this->idf)." order by tag_name asc");
				while( $rr = $babDB->db_fetch_array($res))
					{
					$this->keysval .= $rr['tag_name'].', ';
					}

				$this->keysvalhtml = bab_toHtml($this->keysval);

				$this->fsizetxt = bab_translate("Size");
				
				$fullpath = BAB_FileManagerEnv::getCollectivePath($oFolderFile->getDelegationOwnerId()) . $oFolderFile->getPathName() . $oFolderFile->getName();
				if(file_exists($fullpath)) 
				{
					$fstat = stat($fullpath);
					$this->fsize = bab_toHtml(bab_formatSizeFile($fstat[7])." ".bab_translate("Kb")." ( ".bab_formatSizeFile($fstat[7], false) ." ".bab_translate("Bytes") ." )");
				
				}
				else
				{
					$this->fsize = '???';
				}
				
				
				$this->fmodifiedtxt = bab_translate("Modified");
				$this->fmodified = bab_toHtml(bab_shortDate(bab_mktime($oFolderFile->getModifiedDate()), true));
				$this->fmodifiedbytxt = bab_translate("Modified by");
				$this->fmodifiedby = bab_toHtml(bab_getUserName($oFolderFile->getModifierId()));
				$this->fcreatedtxt = bab_translate("Created");
				$this->fcreated = bab_toHtml(bab_shortDate(bab_mktime($oFolderFile->getCreationDate()), true));
				$this->fpostedbytxt = bab_translate("Posted by");
				$this->fpostedby = bab_toHtml(bab_getUserName($oFolderFile->getModifierId() == 0 ? $oFolderFile->getAuthorId() : $oFolderFile->getModifierId()));

				$this->geturl = bab_toHtml($GLOBALS['babUrlScript']."?tg=fileman&sAction=getFile&id=".$this->id."&gr=".$oFolderFile->getGroup()."&path=".urlencode($oFileManagerEnv->sPath)."&file=".urlencode($oFolderFile->getName()).'&idf='.$oFolderFile->getId());
				$this->download = bab_translate("Download");

				$this->file = bab_translate("File");
				$this->name = bab_translate("Name");
				$this->nameval = bab_toHtml($oFolderFile->getName());
				$this->attribute = bab_translate("Read only");
				if('Y' === $oFolderFile->getReadOnly())
				{
					$this->yesselected = "selected";
					$this->noselected = "";
					if($this->bupdate)
					{
						$this->bupdate = false;
					}
				}
				else
				{
					$this->noselected = "selected";
					$this->yesselected = "";
				}

				$this->confirm = bab_translate("Confirm");
				if('N' === $oFolderFile->getConfirmed())
				{
					$this->confirmyes = "selected";
					$this->confirmno = "";
				}
				else
				{
					$this->confirmno = "selected";
					$this->confirmyes = "";
				}

				$this->update= bab_translate("Update");
				$this->yes = bab_translate("Yes");
				$this->no = bab_translate("No");
				$this->bviewnf = false;

				$this->versions = false;
				$this->yesnfselected = "";
				$this->nonfselected = "";
				$this->countff = 0;	
						
				if(!is_null($oFmFolder))
				{
					if('Y' === $oFmFolder->getVersioning()) 
					{
						$this->versions = true;
					} 
					
					if('Y' === $oFolderFile->getGroup() && $this->bupdate)
					{
						if('N' === $oFmFolder->getFileNotify())
						{
							$this->nonfselected = "selected";
							$this->yesnfselected = "";
						}
						else
						{
							$this->yesnfselected = "selected";
							$this->nonfselected = "";
						}
	
						$this->bviewnf = true;
					}
					
					if('Y' === $oFolderFile->getGroup())
					{
						$this->resff = $babDB->db_query("select * from ".BAB_FM_FIELDS_TBL." where id_folder='".$babDB->db_escape_string($oFolderFile->getOwnerId())."'");
						$this->countff = $babDB->db_num_rows($this->resff);
					}
				}
				// indexation

				

				if(bab_isFileIndex($fullpath) && bab_isUserAdministrator()) 
				{
						$engine = bab_searchEngineInfos();
						
						$this->index = true;
						$this->index_status = $oFolderFile->getStatusIndex();
						$this->t_index_status = bab_translate("Index status");

						$this->index_onload = $engine['indexes']['bab_files']['index_onload'];

						if(isset($_POST['index_status'])) 
						{
							// modify status

							$babDB->db_query(
									"UPDATE ".BAB_FILES_TBL." SET index_status='".$babDB->db_escape_string($_POST['index_status'])."' WHERE id='".$babDB->db_escape_string($_POST['idf'])."'"
								);

							$files_to_index = array($fullpath);

							if(isset($_POST['change_all']) && 1 == $_POST['change_all']) 
							{
								// modifiy index status for older versions
								$res = $babDB->db_query("SELECT id, ver_major, ver_minor FROM ".BAB_FM_FILESVER_TBL." WHERE id_file='".$babDB->db_escape_string($_POST['idf'])."'");
								while ($arrfv = $babDB->db_fetch_assoc($res)) 
								{
									
									$babDB->db_query(
										"UPDATE ".BAB_FM_FILESVER_TBL." SET index_status='".$babDB->db_escape_string($_POST['index_status'])."' WHERE id='".$babDB->db_escape_string($arrfv['id'])."'"
									);

									if ($this->index_onload && BAB_INDEX_STATUS_INDEXED == $_POST['index_status']) 
									{
										
										$files_to_index[] = $sFullPathNane = $oFileManagerEnv->getCurrentFmPath() . $oFolderFile->getPathName() . BAB_FVERSION_FOLDER.'/'.$arrfv['ver_major'].','.$arrfv['ver_minor'].','.$oFolderFile->getName();
									}
								}
							}

							

							if($this->index_onload && BAB_INDEX_STATUS_INDEXED == $_POST['index_status']) 
							{
								$this->index_status = bab_indexOnLoadFiles($files_to_index , 'bab_files');
								if(BAB_INDEX_STATUS_INDEXED === $this->index_status) 
								{
									foreach($files_to_index as $f) 
									{
										$obj = new bab_indexObject('bab_files');
										$obj->setIdObjectFile($f, $oFolderFile->getId(), $oFolderFile->getOwnerId());
									}
								}
							} 
							else 
							{
								$this->index_status = $_POST['index_status'];
							}
						}
					}
				$babBody->addJavascriptFile($GLOBALS['babScriptPath']."prototype/prototype.js");
				$babBody->addJavascriptFile($GLOBALS['babScriptPath']."scriptaculous/scriptaculous.js");
				$babBody->addStyleSheet('ajax.css');
				}
			else
			{
				$GLOBALS['babBody']->title = bab_translate("Access denied");
			}
		}

		function getnextfield()
		{
			global $babDB;
			static $i = 0;
			if($i < $this->countff)
			{
				$arr = $babDB->db_fetch_array($this->resff);
				$this->field = bab_translate($arr['name']);
				$this->fieldid = 'field'.$arr['id'];
				$this->fieldval = '';
				$this->fieldvalhtml = '';
				$res = $babDB->db_query("select fvalue from ".BAB_FM_FIELDSVAL_TBL." where id_field='".$babDB->db_escape_string($arr['id'])."' and id_file='".$babDB->db_escape_string($this->idf)."'");
				if($res && $babDB->db_num_rows($res) > 0)
				{
					list($this->fieldval) = $babDB->db_fetch_array($res);
					$this->fieldvalhtml = bab_toHtml($this->fieldval);
				}
				$i++;
				return true;
			}
			else
			{
				if($this->countff > 0)
				{
					$babDB->db_data_seek($this->resff, 0 );
				}
				$i = 0;
				return false;
			}
		}


		function getnextistatus()
		{
			static $m=0;
			if($m < count($this->tabIndexStatus))
			{
				$this->value = $this->tabIndexStatus[$m];
				$this->disabled=false;
				$this->option = bab_toHtml(bab_getIndexStatusLabel($this->value));
				$this->selected = $this->index_status == $this->value;
				if(BAB_INDEX_STATUS_INDEXED == $this->value && !$this->index_onload) 
				{
					$this->disabled=true;
				}
				$m++;
				return true;
			}
			return false;
		}
	}


	$access = false;
	$bmanager = false;
	$bconfirm = false;
	$bupdate = false;
	$bdownload = false;
	$arr = array();
	$bversion = '';
	
	$idf = (int) bab_rp('idf');

	$oFolderFileSet = new BAB_FolderFileSet();
	$oId =& $oFolderFileSet->aField['iId'];
	$oState =& $oFolderFileSet->aField['sState'];
	$oIdDgOwner =& $oFolderFileSet->aField['iIdDgOwner'];

	$oCriteria = $oId->in($idf);
	$oCriteria = $oCriteria->_and($oState->in(''));
	
	$oFolderFile = $oFolderFileSet->get($oCriteria);

	if(!is_null($oFolderFile))
	{
		//A cause de OVML
		bab_setCurrentUserDelegation($oFolderFile->getDelegationOwnerId());
		$oFileManagerEnv =& getEnvObject();
		
		if('N' === $oFolderFile->getGroup())
		{
			if(userHavePersonnalStorage() && $BAB_SESS_USERID == $oFolderFile->getOwnerId())
			{
				$access = true;
				$bmanager = true;
				$bupdate = true;
				$bdownload = true;
			}
		}
		else if('Y' === $oFolderFile->getGroup())
		{
			if('N' === $oFolderFile->getConfirmed())
			{
				$arrschi = bab_getWaitingIdSAInstance($GLOBALS['BAB_SESS_USERID']);
				if(count($arrschi) > 0 && in_array($oFolderFile->getFlowApprobationInstanceId(), $arrschi))
				{
					$bconfirm = true;
				}
			}
			
			$sParentPath = $oFileManagerEnv->sRelativePath;
			
			$access = (!is_null($oFileManagerEnv->oFmFolder));
			$bdownload = canDownload($sParentPath);
			$bmanager = canManage($sParentPath);
			$bupdate = canUpdate($sParentPath);
			
			if($bconfirm)
			{
				$bupdate = false;
				$bmanager = false;
			}
			
			$bversion = $oFileManagerEnv->oFmFolder->getVersioning();
			if(0 !== $oFolderFile->getFolderFileVersionId() || $bversion ==  'Y')
			{
				$bupdate = false;
			}
		}
	}
	
	if($access)
	{
		$temp = new temp($oFileManagerEnv->oFmFolder, $oFolderFile, $bmanager, $access, $bconfirm, $bupdate, $bdownload, $bversion);
	}
	else
	{
		$temp = new temp(null, $oFolderFile, $bmanager, $access, $bconfirm, $bupdate, $bdownload,$bversion);
	}
	$babBody->babpopup(bab_printTemplate($temp,"fileman.html", "viewfile"));
}


function displayRightForm()
{
	global $babBody;

	$oFileManagerEnv =& getEnvObject();
	
	$iIdFolder = (int) bab_gp('iIdFolder', 0);
	
	$sFolderName = '';
	$oFmFolder = BAB_FmFolderHelper::getFmFolderById($iIdFolder);
	if(!is_null($oFmFolder))
	{
		$sFolderName = $oFmFolder->getName();
	
		$babBody->addItemMenu("list", bab_translate("Folders"), $GLOBALS['babUrlScript']."?tg=fileman&idx=list&id=".$oFileManagerEnv->iId."&gr=".$oFileManagerEnv->sGr."&path=".urlencode($oFileManagerEnv->sPath));
		if(canUpload($oFmFolder->getRelativePath() . $oFmFolder->getName() . '/')) 
		{
			$babBody->addItemMenu("add", bab_translate("Upload"), $GLOBALS['babUrlScript']."?tg=fileman&idx=displayAddFileForm&id=".$oFileManagerEnv->iId."&gr=".$oFileManagerEnv->sGr."&path=".urlencode($oFileManagerEnv->sPath));
		}
		if(canManage($oFmFolder->getRelativePath() . $oFmFolder->getName() . '/')) 
		{
			$babBody->addItemMenu("trash", bab_translate("Trash"), $GLOBALS['babUrlScript']."?tg=fileman&idx=trash&id=".$oFileManagerEnv->iId."&gr=".$oFileManagerEnv->sGr."&path=".urlencode($oFileManagerEnv->sPath));
		}
		$babBody->addItemMenu("displayRightForm", bab_translate("Rights"), $GLOBALS['babUrlScript']."?tg=fileman&idx=displayRightForm&id=".$oFileManagerEnv->iId."&gr=".$oFileManagerEnv->sGr."&path=".
			urlencode($oFileManagerEnv->sPath) . '&iIdFolder=' . $iIdFolder);
		
		$babBody->title = bab_translate("Rights of directory") . ' ' . $sFolderName;
	
		if(canSetRight($oFmFolder->getRelativePath() . $oFmFolder->getName() . '/'))
		{
			require_once $GLOBALS['babInstallPath'] . 'admin/acl.php';
			$macl = new macl("fileman", 'list', $iIdFolder, 'setRight', true, $oFmFolder->getDelegationOwnerId());
			
			$macl->set_hidden_field('path', $oFileManagerEnv->sPath);
			$macl->set_hidden_field('sAction', 'setRight');
			$macl->set_hidden_field('sPathName', $oFmFolder->getRelativePath() . $oFmFolder->getName() . '/');
			$macl->set_hidden_field('id', $oFileManagerEnv->iId);
			$macl->set_hidden_field('gr', $oFileManagerEnv->sGr);
			$macl->set_hidden_field('iIdFolder', $iIdFolder);
			
			$macl->addtable( BAB_FMUPLOAD_GROUPS_TBL,bab_translate("Upload"));
			$macl->filter(0,0,1,0,1);
			$macl->addtable( BAB_FMDOWNLOAD_GROUPS_TBL,bab_translate("Download"));
			$macl->addtable( BAB_FMUPDATE_GROUPS_TBL,bab_translate("Update"));
			$macl->filter(0,0,1,0,1);
			$macl->addtable( BAB_FMMANAGERS_GROUPS_TBL,bab_translate("Manage"));
			$macl->filter(0,0,1,1,1);
			$macl->addtable( BAB_FMNOTIFY_GROUPS_TBL,bab_translate("Who is notified when a new file is uploaded or updated?"));
			$macl->filter(0,0,1,0,1);
			$macl->babecho();
		}
		else 
		{
			$babBody->msgerror = bab_translate("Access denied");
		}
	}
	else 
	{
		$babBody->msgerror = bab_translate("Invalid directory");
	}
}


function setRight()
{
	global $babBody;
	$sPathName = (string) bab_rp('sPathName', '');
	
	if(canSetRight($sPathName))
	{
		require_once $GLOBALS['babInstallPath'] . 'admin/acl.php';
		maclGroups();
	}
	else 
	{
		$babBody->msgerror = bab_translate("Access denied");
	}
}

function fileUnload()
	{
	class temp
		{
		var $message;
		var $close;
		var $redirecturl;

		function temp()
			{
			$oFileManagerEnv =& getEnvObject();
			$this->message = bab_translate("Your file list has been updated");
			$this->close = bab_translate("Close");
			$url = $GLOBALS['babUrlScript']."?tg=fileman&idx=list&id=".$oFileManagerEnv->iId."&gr=".$oFileManagerEnv->sGr."&path=".urlencode($oFileManagerEnv->sPath);
			$this->redirecturl = bab_toHtml($url, BAB_HTML_JS | BAB_HTML_ENTITIES);
			}
		}

	$temp = new temp();
	echo bab_printTemplate($temp,"fileman.html", "fileunload");
	}

function deleteFiles($items)
{
	$oFolderFileSet = new BAB_FolderFileSet();
	$oId =& $oFolderFileSet->aField['iId'];
	$oFolderFileSet->remove($oId->in($items));
}

function restoreFiles($items)
{
	$oFileManagerEnv =& getEnvObject();
	
	$sPathName = $oFileManagerEnv->getCurrentFmPath();
	
//	bab_debug($sPathName);
	
	$oFolderFileSet = new BAB_FolderFileSet();
	$oId =& $oFolderFileSet->aField['iId'];
	
	global $babDB;	
	for($i = 0; $i < count($items); $i++)
	{
		$oFolderFile = $oFolderFileSet->get($oId->in($items[$i]));
		if(!is_null($oFolderFile))
		{
			if(!is_dir($sPathName))
			{
				$rr = explode("/", $sPathName);
				$sPath = $sUploadPath;
				for($k = 0; $k < count($rr); $k++ )
				{
					$sPath .= $rr[$k]."/";
					if(!is_dir($sPath))
					{
						bab_mkdir($sPath, $GLOBALS['babMkdirMode']);
					}
				}
			}
			$oFolderFile->setState('');
			$oFolderFile->save();
		}
	}
}

	
function displayFolderForm()
{
	global $babBody;
	
	$oFileManagerEnv =& getEnvObject();
	
	$babBody->addItemMenu('list', bab_translate("Folders"), $GLOBALS['babUrlScript'] . '?tg=fileman&idx=list&id=' . 
		$oFileManagerEnv->iId . '&gr=' . $oFileManagerEnv->sGr . '&path=' . urlencode($oFileManagerEnv->sPath));
		
	$babBody->addItemMenu('displayFolderForm', bab_translate("Create a folder"), $GLOBALS['babUrlScript'] . 
		'?tg=fileman&idx=displayFolderForm&id=' . $oFileManagerEnv->iId . '&gr=' . $oFileManagerEnv->sGr . 
		'&path=' . urlencode($oFileManagerEnv->sPath));
	
	$babBody->title = bab_translate("Add a new folder");
	
	if($oFileManagerEnv->userIsInCollectiveFolder() || $oFileManagerEnv->userIsInRootFolder())
	{
		if(canCreateFolder($oFileManagerEnv->sRelativePath))
		{
			$oDspFldForm = new DisplayCollectiveFolderForm();
			$babBody->babecho($oDspFldForm->printTemplate());
		}
		else 
		{
			$babBody->msgerror = bab_translate("Access denied");
		}
	}
	else if($oFileManagerEnv->userIsInPersonnalFolder())
	{
		if(canCreateFolder($oFileManagerEnv->sRelativePath))
		{
			$oDspFldForm = new DisplayUserFolderForm();
			$babBody->babecho($oDspFldForm->printTemplate());
		}
		else 
		{
			$babBody->msgerror = bab_translate("Access denied");
		}
	}
}


function displayDeleteFolderConfirm()
{
	global $babBody;
	
	$oFileManagerEnv =& getEnvObject();
	
	if(canCreateFolder($oFileManagerEnv->sRelativePath))
	{
		$sPath		= (string) bab_rp('path', '');
		$sDirName	= (string) bab_rp('sDirName', '');
		$iIdFld		= (int) bab_rp('iIdFolder', 0); 
		
		$oBfp = new BAB_BaseFormProcessing();
		
		$oBfp->set_caption('yes', bab_translate("Yes"));
		$oBfp->set_caption('no', bab_translate("No"));
		$oBfp->set_caption('warning', bab_translate("CAUTION: This will permanently remove this directory and all subdirectories files on it !"));
		$oBfp->set_caption('message', bab_translate("You sure you want to delete this directory ?"));
		$oBfp->set_caption('title', $sDirName);
		
		$oBfp->set_data('sTg', 'fileman');
		$oBfp->set_data('sIdx', 'list');
		$oBfp->set_data('sAction', 'deleteFolder');
		$oBfp->set_data('sDirName', $sDirName);
		$oBfp->set_data('iIdFolder', $iIdFld);
		$oBfp->set_data('iId', $oFileManagerEnv->iId);
		$oBfp->set_data('sPath', $sPath);
		$oBfp->set_data('sPathName', $oFileManagerEnv->sRelativePath);
		$oBfp->set_data('sGr', $oFileManagerEnv->sGr);
		
		$babBody->babecho(bab_printTemplate($oBfp, 'fileman.html', 'warningyesno'));
	}
	else
	{
		$babBody->msgerror = bab_toHtml(bab_translate("Access denied"));
		return;
	}
}

	
function cutFolder()
{
	$oFileManagerEnv =& getEnvObject();
	if($oFileManagerEnv->userIsInRootFolder() || $oFileManagerEnv->userIsInCollectiveFolder())
	{
		cutCollectiveDir();
	}
	else if($oFileManagerEnv->userIsInPersonnalFolder())
	{
		cutUserFolder();
	}
	else 
	{
		$babBody->msgerror = bab_translate("Access denied");
	}
}


function cutCollectiveDir()
{
	global $babBody;
	$oFileManagerEnv =& getEnvObject();
	
	$sDirName = (string) bab_gp('sDirName', '');
	
	if(strlen(trim($sDirName)) > 0)
	{
		if(!canCutFolder($oFileManagerEnv->sRelativePath . $sDirName . '/'))
		{
			$babBody->msgerror = bab_translate("Access denied");
			return;
		}
		
		$sUploadPath = $oFileManagerEnv->getCollectiveRootFmPath();
		$sFullPathName = realpath($oFileManagerEnv->getCollectiveRootFmPath() . $oFileManagerEnv->sRelativePath . $sDirName);
		
		if(!is_dir($sFullPathName))
		{
			$babBody->msgerror = bab_translate("Invalid directory");
			return;
		}
		
		$iIdRootFolder	= $oFileManagerEnv->iId;
		$iIdFolder		= 0;
		$sGroup			= 'Y';
		$sCollective	= 'N';
		$iIdOwner		= $oFileManagerEnv->iIdObject;
		$sCheckSum		= md5($sDirName);
		
		$oFmFolderSet	= new BAB_FmFolderSet();
		$oName			=& $oFmFolderSet->aField['sName'];
		$oRelativePath	=& $oFmFolderSet->aField['sRelativePath'];
		$oIdDgOwner		=& $oFmFolderSet->aField['iIdDgOwner'];
		
		$oCriteria = $oName->in($sDirName);
		$oCriteria = $oCriteria->_and($oRelativePath->in($oFileManagerEnv->sRelativePath));
		$oCriteria = $oCriteria->_and($oIdDgOwner->in(bab_getCurrentUserDelegation()));
		
		$oFmFolder = $oFmFolderSet->get($oCriteria);
		if(!is_null($oFmFolder))
		{
			$iIdFolder		= $oFmFolder->getId();
			$sCollective	= 'Y';
			$iIdOwner		= $oFmFolder->getId();
		}
		
		$oFmFolderCliboard = new BAB_FmFolderCliboard();
		$oFmFolderCliboard->setRootFolderId($iIdRootFolder);
		$oFmFolderCliboard->setFolderId($iIdFolder);
		$oFmFolderCliboard->setGroup($sGroup);
		$oFmFolderCliboard->setCollective($sCollective);
		$oFmFolderCliboard->setOwnerId($iIdOwner);
		$oFmFolderCliboard->setDelegationOwnerId(bab_getCurrentUserDelegation());
		$oFmFolderCliboard->setCheckSum($sCheckSum);
		$oFmFolderCliboard->setName($sDirName);
		$oFmFolderCliboard->setRelativePath($oFileManagerEnv->sRelativePath);
		$oFmFolderCliboard->save();
	}
	else 
	{
		$babBody->msgerror = bab_translate("Access denied");
	}
}


function cutUserFolder()
{
//	bab_debug(__LINE__ . ' ' . basename(__FILE__) . ' ' . __FUNCTION__);

	global $babBody;
	$oFileManagerEnv =& getEnvObject();
	
	$sDirName = (string) bab_gp('sDirName', '');
	
	if(!canCutFolder($oFileManagerEnv->sRelativePath . $sDirName . '/'))
	{
		$babBody->msgerror = bab_translate("Access denied");
		return;
	}
	
	if(strlen(trim($sDirName)) > 0)
	{
		$sUploadPath = $oFileManagerEnv->getRootFmPath();
		$sFullPathName = realpath($sUploadPath . $oFileManagerEnv->sRelativePath . $sDirName);
		
		if(!is_dir($sFullPathName))
		{
			$babBody->msgerror = bab_translate("Invalid directory");
			return;
		}
		
		$iIdRootFolder	= $oFileManagerEnv->iId;
		$iIdFolder		= 0;
		$sGroup			= 'N';
		$sCollective	= 'N';
		$sCheckSum		= md5($sDirName);
		
		$oFmFolderCliboard = new BAB_FmFolderCliboard();
		$oFmFolderCliboard->setRootFolderId($iIdRootFolder);
		$oFmFolderCliboard->setFolderId($iIdFolder);
		$oFmFolderCliboard->setGroup($sGroup);
		$oFmFolderCliboard->setCollective($sCollective);
		$oFmFolderCliboard->setOwnerId($iIdRootFolder);
		$oFmFolderCliboard->setDelegationOwnerId(0);
		$oFmFolderCliboard->setCheckSum($sCheckSum);
		$oFmFolderCliboard->setName($sDirName);
		$oFmFolderCliboard->setRelativePath($oFileManagerEnv->sRelativePath);
		$oFmFolderCliboard->save();
	}
	else 
	{
		$babBody->msgerror = bab_translate("Access denied");
	}
}


function pasteFolder()
{
//	bab_debug(__LINE__ . ' ' . basename(__FILE__) . ' ' . __FUNCTION__);

	global $babBody;
	$oFileManagerEnv =& getEnvObject();
	
	if($oFileManagerEnv->userIsInCollectiveFolder() || $oFileManagerEnv->userIsInRootFolder())
	{
		pasteCollectiveDir();
	}
	else if($oFileManagerEnv->userIsInPersonnalFolder())
	{
		pasteUserFolder();
	}
	else 
	{
		$babBody->msgerror = bab_translate("Access denied");
	}
}


function pasteCollectiveDir()
{
//	bab_debug(__LINE__ . ' ' . basename(__FILE__) . ' ' . __FUNCTION__);

	global $babBody;
	$oFileManagerEnv =& getEnvObject();
	
	$iIdSrcRootFolder		= (int) bab_gp('iIdSrcRootFolder', 0);
	$sSrcPath				= (string) bab_gp('sSrcPath', '');
	$bSrcPathIsCollective	= true;
	$iIdTrgRootFolder		= $oFileManagerEnv->iId;
	$sTrgPath				= (string) bab_gp('path', '');

	$oFmFolder				= null;
	 
	if(canPasteFolder($iIdSrcRootFolder, $sSrcPath, $bSrcPathIsCollective, $iIdTrgRootFolder, $sTrgPath))
	{
		//Nom du répertoire à coller
		$sName = getLastPath($sSrcPath); 
		
		//Emplacement du répertoire à coller
		$sSrcPathRelativePath = addEndSlash(removeLastPath($sSrcPath . '/'));

		$bSrcPathHaveVersioning = false;
		$bTrgPathHaveVersioning = false;
		$bSrcPathCollective		= false;
		
		//Récupération des informations concernant le répertoire source (i.e le répertoire à déplacer)
		{
			$iIdRootFolder	= 0;
			$oSrcFmFolder	= null;
			BAB_FmFolderHelper::getInfoFromCollectivePath($sSrcPath, $iIdRootFolder, $oSrcFmFolder);
			
			$iSrcIdOwner			= $oSrcFmFolder->getId();
			$bSrcPathHaveVersioning = ('Y' === $oSrcFmFolder->getVersioning());
			$bSrcPathCollective		= ((string) $sSrcPath . '/' === (string) $oSrcFmFolder->getRelativePath() . $oSrcFmFolder->getName() . '/');
		}
		
		$oFmFolderSet = new BAB_FmFolderSet();
		if($oFileManagerEnv->userIsInCollectiveFolder())
		{
			//Récupération des informations concernant le répertoire cible (i.e le répertoire dans lequel le source est déplacé)
			$oTrgFmFolder = null;
			BAB_FmFolderHelper::getInfoFromCollectivePath($sTrgPath, $iIdRootFolder, $oTrgFmFolder);
			$iTrgIdOwner = $oTrgFmFolder->getId();
			$bTrgPathHaveVersioning = ('Y' === $oTrgFmFolder->getVersioning());
		}
		else if($oFileManagerEnv->userIsInRootFolder())
		{
			$oIdDgOwner		= $oFmFolderSet->aField['iIdDgOwner'];
			$oName			= $oFmFolderSet->aField['sName'];
			$oRelativePath	= $oFmFolderSet->aField['sRelativePath'];

			$oCriteria = $oIdDgOwner->in(bab_getCurrentUserDelegation());
			$oCriteria = $oCriteria->_and($oName->in($sName));
			$oCriteria = $oCriteria->_and($oRelativePath->in($sSrcPathRelativePath));

			$bSrcPathCollective = true;

//			bab_debug($oFmFolderSet->getSelectQuery($oCriteria));
			$oFmFolder = $oFmFolderSet->get($oCriteria);
			if(!is_null($oFmFolder))
			{
				//Le répertoire à coller est collectif
				
				$bTrgPathHaveVersioning = ('Y' === $oFmFolder->getVersioning());
			}
			else 
			{
				//Le répertoire à coller n'est pas collectif
				//comme on colle dans la racine il faut le faire 
				//devenir un répertoire collectif
				
				$oFmFolder = new BAB_FmFolder();
				$oFmFolder->setName($sName);
				$oFmFolder->setRelativePath('');
				$oFmFolder->setActive('Y');
				$oFmFolder->setApprobationSchemeId(0);
				$oFmFolder->setDelegationOwnerId((int) bab_getCurrentUserDelegation());
				$oFmFolder->setFileNotify('N');
				$oFmFolder->setHide('N');
				$oFmFolder->setAddTags('Y');
				$oFmFolder->setVersioning('N');
				$oFmFolder->setAutoApprobation('N');
			}
		}

		$sUploadPath = BAB_FileManagerEnv::getCollectivePath(bab_getCurrentUserDelegation());
		
		$sFullSrcPath = realpath((string) $sUploadPath . $sSrcPath);
		$sFullTrgPath = realpath((string) $sUploadPath . $sTrgPath);
		
//		bab_debug('sFullSrcPath ==> ' . $sFullSrcPath . ' versioning ' . (($bSrcPathHaveVersioning) ? 'Yes' : 'No') . ' bSrcPathCollective ' . (($bSrcPathCollective) ? 'Yes' : 'No'));
//		bab_debug('sFullTrgPath ==> ' . $sFullTrgPath . ' versioning ' . (($bTrgPathHaveVersioning) ? 'Yes' : 'No'));

//		$sPath = substr($sFullTrgPath, 0, strlen($sFullSrcPath));
//		if($sPath !== $sFullSrcPath)
		{
			$bSrcValid = ((realpath(substr($sFullSrcPath, 0, strlen($sUploadPath))) === (string) realpath($sUploadPath)) && is_readable($sFullSrcPath));
			$bTrgValid = ((realpath(substr($sFullTrgPath, 0, strlen($sUploadPath))) === (string) realpath($sUploadPath)) && is_writable($sFullTrgPath));

//			bab_debug('bSrcValid ' . (($bSrcValid) ? 'Yes' : 'No'));
//			bab_debug('bTrgValid ' . (($bTrgValid) ? 'Yes' : 'No'));
			
			if($bSrcValid && $bTrgValid)
			{
				if(!is_null($oFmFolder))
				{
					if(true !== $oFmFolder->save())
					{
						$babBody->msgerror = bab_translate("Error");
						return;
					}
					$bTrgPathHaveVersioning = false;
					$iTrgIdOwner			= $oFmFolder->getId();
				}
				
				global $babDB, $babBody;
				$oFolderFileSet = new BAB_FolderFileSet();
				$oIdDgOwnerFile =& $oFolderFileSet->aField['iIdDgOwner'];
				$oGroup =& $oFolderFileSet->aField['sGroup'];
				$oPathName =& $oFolderFileSet->aField['sPathName'];
				
				$oFmFolderSet = new BAB_FmFolderSet();
				$oIdDgOwnerFolder =& $oFmFolderSet->aField['iIdDgOwner'];
				$oRelativePath =& $oFmFolderSet->aField['sRelativePath'];
				
				$sLastRelativePath = $sSrcPath . '/';
				$sNewRelativePath = ((strlen(trim($sTrgPath)) > 0) ? 
					$sTrgPath . '/' : '') . getLastPath($sSrcPath) . '/';
					
				if(false === $bSrcPathCollective)
				{
					 if(false === $bTrgPathHaveVersioning)
					 {
						global $babDB;
						
						//Suppression des versions des fichiers pour les répertoires qui ne sont pas contenus dans des 
						//répertoires collectifs
						{
							//Sélection de tous les fichiers qui contiennent dans leurs chemins le répertoire à déplacer
							$oCriteriaFile = $oPathName->like($babDB->db_escape_like($sLastRelativePath) . '%');
							$oCriteriaFile = $oCriteriaFile->_and($oGroup->in('Y'));
							$oCriteriaFile = $oCriteriaFile->_and($oIdDgOwnerFile->in(bab_getCurrentUserDelegation()));
							
							//Sélection des répertoires collectifs
							$oCriteriaFolder = $oRelativePath->like($babDB->db_escape_like($sLastRelativePath) . '%');
							$oCriteriaFolder = $oCriteriaFolder->_and($oIdDgOwnerFolder->in(bab_getCurrentUserDelegation()));
							$oFmFolderSet->select($oCriteriaFolder);
							while(null !== ($oFmFolder = $oFmFolderSet->next()))
							{
								//exclusion des répertoires collectif (on ne touche pas à leurs versions)
								$oCriteriaFile = $oCriteriaFile->_and($oPathName->notLike(
									$babDB->db_escape_like($oFmFolder->getRelativePath() . $oFmFolder->getName() . '/') . '%'));
							}
							$oFolderFileSet->removeVersions($oCriteriaFile);
							
							$oFolderFileSet->select($oCriteriaFile);
							while(null !== ($oFolderFile = $oFolderFileSet->next()))
							{
								$oFolderFile->setMajorVer(1);
								$oFolderFile->setMinorVer(0);
								$oFolderFile->save();
							}
						}
					 }
				}								

				if(BAB_FmFolderSet::move($sUploadPath, $sLastRelativePath, $sNewRelativePath))
				{
					BAB_FolderFileSet::move($sLastRelativePath, $sNewRelativePath, 'Y');
					
					$oFmFolderCliboardSet = new BAB_FmFolderCliboardSet();
					$oFmFolderCliboardSet->deleteEntry($sName, $sSrcPathRelativePath, 'Y');
					$oFmFolderCliboardSet->move($sLastRelativePath, $sNewRelativePath, 'Y');
				}
			}			
		}
	}
	else 
	{
		$babBody->msgerror = bab_translate("Access denied");
	}
}


function pasteUserFolder()
{
//	bab_debug(__LINE__ . ' ' . basename(__FILE__) . ' ' . __FUNCTION__);

	global $babBody;
	$oFileManagerEnv =& getEnvObject();
	
	$iIdSrcRootFolder		= (int) bab_gp('iIdSrcRootFolder', 0);
	$sSrcPath				= (string) bab_gp('sSrcPath', '');
	$bSrcPathIsCollective	= true;
	$iIdTrgRootFolder		= $oFileManagerEnv->iId;
	$sTrgPath				= (string) bab_gp('path', '');
	$oFmFolder				= null;
	$bSrcPathIsCollective	= false;
	
	if(canPasteFolder($iIdSrcRootFolder, $sSrcPath, $bSrcPathIsCollective, $iIdTrgRootFolder, $sTrgPath))
	{
		$sUploadPath = $oFileManagerEnv->getRootFmPath();
		
		$sFullSrcPath = realpath((string) $sUploadPath . $sSrcPath);
		$sFullTrgPath = realpath((string) $sUploadPath . $sTrgPath);
		
//		bab_debug($sFullSrcPath);
//		bab_debug($sFullTrgPath);

		//Nom du répertoire à coller
		$sName = getLastPath($sSrcPath); 
		
		//Emplacement du répertoire à coller
		$sSrcPathRelativePath = addEndSlash(removeLastPath($sSrcPath . '/'));
		
		if($sFullSrcPath === realpath((string) $sFullTrgPath . '/' . getLastPath($sSrcPath)))
		{
			$oFmFolderCliboardSet = new BAB_FmFolderCliboardSet();
			$oFmFolderCliboardSet->deleteEntry($sName, $sSrcPathRelativePath, 'N');
		}
		else 
		{
//			$sPath = substr($sFullTrgPath, 0, strlen($sFullSrcPath));
//			if($sPath !== $sFullSrcPath)
			{
				$bSrcValid = ((realpath(substr($sFullSrcPath, 0, strlen($sUploadPath))) === (string) realpath($sUploadPath)) && is_readable($sFullSrcPath));
				$bTrgValid = ((realpath(substr($sFullTrgPath, 0, strlen($sUploadPath))) === (string) realpath($sUploadPath)) && is_writable($sFullTrgPath));

//				bab_debug('bSrcValid ' . (($bSrcValid) ? 'Yes' : 'No'));
//				bab_debug('bTrgValid ' . (($bTrgValid) ? 'Yes' : 'No'));
			
				if($bSrcValid && $bTrgValid)
				{
					$sLastRelativePath = $sSrcPath . '/';
					$sNewRelativePath = ((strlen(trim($sTrgPath)) > 0) ? 
						$sTrgPath . '/' : '') . getLastPath($sSrcPath) . '/';
					
					$sSrc = removeEndSlah($sUploadPath . $sLastRelativePath);
					$sTrg = removeEndSlah($sUploadPath . $sNewRelativePath);
					
//					bab_debug($sSrc);
//					bab_debug($sTrg);

					if(rename($sSrc, $sTrg))
					{
						$oFmFolderCliboardSet = new BAB_FmFolderCliboardSet();
						$oFmFolderCliboardSet->deleteEntry($sName, $sSrcPathRelativePath, 'N');
						$oFmFolderCliboardSet->move($sLastRelativePath, $sNewRelativePath, 'N');
						
						
						global $babBody, $babDB, $BAB_SESS_USERID;
						// update database files
						$oFolderFileSet = new BAB_FolderFileSet();
						$oPathName		=& $oFolderFileSet->aField['sPathName'];
						$oIdDgOwner		=& $oFolderFileSet->aField['iIdDgOwner'];
						$oGroup			=& $oFolderFileSet->aField['sGroup'];
						$oIdOwner		=& $oFolderFileSet->aField['iIdOwner'];

						$oCriteria = $oPathName->like($babDB->db_escape_like($sLastRelativePath) . '%');
						$oCriteria = $oCriteria->_and($oGroup->in('N'));
						$oCriteria = $oCriteria->_and($oIdDgOwner->in(0));
						$oCriteria = $oCriteria->_and($oIdOwner->in($BAB_SESS_USERID));
						
						$oFolderFileSet->select($oCriteria);
						$iL = strlen($sLastRelativePath);
						while(null !== ($oFolderFile = $oFolderFileSet->next()))
						{
							$opath = $oFolderFile->getPathName();
							$oFolderFile->setPathName($sNewRelativePath.substr($opath, $iL ));
							$oFolderFile->save();
						}
					}
				}
			}
		}
	}
}

	
function createFolder()
{
	global $babBody;

	$oFileManagerEnv =& getEnvObject();
	
	if($oFileManagerEnv->userIsInCollectiveFolder() || $oFileManagerEnv->userIsInRootFolder())
	{
		createFolderForCollectiveDir();
	}
	else if($oFileManagerEnv->userIsInPersonnalFolder())
	{
		createFolderForUserDir();
	}
	else 
	{
		$babBody->msgerror = bab_translate("Access denied");
	}
}


function createFolderForCollectiveDir()
{
	global $babBody;
	
	$oFileManagerEnv =& getEnvObject();
	if(canCreateFolder($oFileManagerEnv->sRelativePath))
	{	
		$sDirName = (string) bab_pp('sDirName', '');
		if(strlen(trim($sDirName)) > 0)
		{
			$sType					= (string) bab_pp('sType', 'collective');
			$sActive				= (string) bab_pp('sActive', 'Y');
			$iIdApprobationScheme	= (int) bab_pp('iIdApprobationScheme', 0);
			$sAutoApprobation		= (string) bab_pp('sAutoApprobation', 'N');
			$sNotification			= (string) bab_pp('sNotification', 'N');
			$sVersioning			= (string) bab_pp('sVersioning', 'N');
			$sDisplay				= (string) bab_pp('sDisplay', 'N');
			$sAddTags				= (string) bab_pp('sAddTags', 'Y');
			$sPath					= (string) bab_pp('path', '');
			$sPathName				= (string) '';
			$iIdFolder				= (int) $oFileManagerEnv->iId;
			
			$sRelativePath = '';
			$oFmFolder = BAB_FmFolderHelper::getFmFolderById($iIdFolder);
			if(!is_null($oFmFolder) || $oFileManagerEnv->userIsInRootFolder())
			{
				$sRelativePath	= $oFileManagerEnv->sRelativePath;
				$sUploadPath	= BAB_FileManagerEnv::getCollectivePath(bab_getCurrentUserDelegation());
				$sDirName		= replaceInvalidFolderNameChar($sDirName);
				$sFullPathName	= $sUploadPath . $sRelativePath . $sDirName;

//				bab_debug('sFullPathName ==> ' .  $sFullPathName);
//				bab_debug('sRelativePath ==> ' . $sRelativePath);

				if(BAB_FmFolderHelper::createDirectory($sFullPathName))
				{
					if('collective' === $sType || $oFileManagerEnv->userIsInRootFolder())
					{
						$oFmFolder = new BAB_FmFolder();
						$oFmFolder->setActive($sActive);
						$oFmFolder->setApprobationSchemeId($iIdApprobationScheme);
						$oFmFolder->setAutoApprobation($sAutoApprobation);
						$oFmFolder->setDelegationOwnerId(bab_getCurrentUserDelegation());
						$oFmFolder->setFileNotify($sNotification);
						$oFmFolder->setHide((($sDisplay === 'Y') ? 'N' : 'Y'));
						$oFmFolder->setName($sDirName);
						$oFmFolder->setAddTags($sAddTags);
						$oFmFolder->setRelativePath($sRelativePath);
						$oFmFolder->setVersioning($sVersioning);
						$oFmFolder->setAutoApprobation($sAutoApprobation);
						if(false === $oFmFolder->save())
						{
							rmdir($sFullPathName);
						}
					}
				}
			}
		}
		else 
		{
			$babBody->msgerror = bab_translate("Access denied");
		}
	}	
	else 
	{
		$babBody->msgerror = bab_translate("Access denied");
	}
}


function createFolderForUserDir()
{
	global $babBody;
	
	$oFileManagerEnv =& getEnvObject();
	
//	bab_debug(__LINE__ . ' ' . basename(__FILE__) . ' ' . __FUNCTION__ . ' sRelativePath ==> ' . $oFileManagerEnv->sRelativePath);
	
	$oFileManagerEnv =& getEnvObject();
	
	if(canCreateFolder($oFileManagerEnv->sRelativePath))
	{
		$sDirName = (string) bab_pp('sDirName', '');
		if(strlen(trim($sDirName)) > 0)
		{
//			bab_debug('sFullPathName ==> ' .  $sFullPathName);
//			bab_debug('sRelativePath ==> ' . $oFileManagerEnv->sRelativePath);

			$sUploadPath	= $oFileManagerEnv->getCurrentFmPath();
			$sDirName		= replaceInvalidFolderNameChar($sDirName);
			$sFullPathName	= $sUploadPath . $sDirName;
			BAB_FmFolderHelper::createDirectory($sFullPathName);
		}
		else 
		{
			$babBody->msgerror = bab_translate("Access denied");
		}
	}
	else 
	{
		$babBody->msgerror = bab_translate("Access denied");
	}
}


function editFolder()
{
	global $babBody;

	$oFileManagerEnv =& getEnvObject();
	
	if($oFileManagerEnv->userIsInCollectiveFolder() || $oFileManagerEnv->userIsInRootFolder())
	{
		editFolderForCollectiveDir();
	}
	else if($oFileManagerEnv->userIsInPersonnalFolder())
	{
		editFolderForUserDir();
	}
	else 
	{
		$babBody->msgerror = bab_translate("Access denied");
	}
	
}


function editFolderForCollectiveDir()
{
	global $babBody, $babDB;
	
	$oFileManagerEnv =& getEnvObject();
	
//	bab_debug(__LINE__ . ' ' . basename(__FILE__) . ' ' . __FUNCTION__ . ' sRelativePath ==> ' . $oFileManagerEnv->sRelativePath);

	if(canCreateFolder($oFileManagerEnv->sRelativePath))
	{	
//bab_debug('Rajouter un test qui permet d\'être que c\'est répertoire collectif ou pas');
		$sDirName = (string) bab_pp('sDirName', '');
		if(strlen(trim($sDirName)) > 0)
		{
			$sType				= (string) bab_pp('sType', 'collective');
			$iIdFld				= (int) bab_pp('iIdFolder', 0); 
			$bFolderRenamed		= false;
			$bChangeFileIdOwner = false;
			$sRelativePath		= $oFileManagerEnv->sRelativePath;
			
			$oFmFolder = BAB_FmFolderHelper::getFmFolderById($iIdFld);
			if(!is_null($oFmFolder))
			{
				$bFolderRenamed	= ($sDirName !== $oFmFolder->getName()) ? true : false;
				$sOldDirName	= $oFmFolder->getName();
				$sRelativePath	= $oFmFolder->getRelativePath();
				
				//collectiveToSimple
				if('simple' === $sType)
				{
					//changer les iIdOwner
					//supprimer les droits
					//supprimer les versions de fichiers
					//supprimer les instances de schémas d'approbations
					//supprimer l'entrée dans fmfolders
					$bDbRecordOnly = true;
					$oFmFolderSet = new BAB_FmFolderSet();
					$oFmFolderSet->delete($oFmFolder, $bDbRecordOnly);
					$oFmFolder = null;
				}
			}
			else 
			{
				$sOldDirName	= (string) bab_pp('sOldDirName', '');
				$bFolderRenamed	= ($sDirName !== $sOldDirName) ? true : false;
				
				//simpleToCollective
				if('collective' === $sType)
				{
					//changer les iIdOwner
					//créer l'entrée dans fmfolders
					$bChangeFileIdOwner = true;
					$oFmFolder = new BAB_FmFolder();
				}
			}
			
			$sRootFmPath = $oFileManagerEnv->getCollectiveRootFmPath();
			/*
			bab_debug('sRootFmPath ==> ' . $sRootFmPath);
			bab_debug('sRelativePath ==> ' . $sRelativePath);
			bab_debug('sOldDirName ==> ' . $sOldDirName);
			bab_debug('sDirName ==> ' . $sDirName);
			//*/

			if($bFolderRenamed)
			{
				if(strlen(trim($sOldDirName)) > 0)
				{
					$sLocalDirName = replaceInvalidFolderNameChar($sDirName);
					$bSuccess = BAB_FmFolderSet::rename($sRootFmPath, $sRelativePath, $sOldDirName, $sLocalDirName);
					if(false !== $bSuccess)
					{
						$sDirName = $sLocalDirName;
						BAB_FolderFileSet::renameFolder($sRelativePath . $sOldDirName . '/', $sLocalDirName, 'Y');
						BAB_FmFolderCliboardSet::rename($sRelativePath, $sOldDirName, $sLocalDirName, 'Y');
					}
					else
					{
						$sDirName = $sOldDirName;
					}
				}
				else 
				{
					bab_debug(__FUNCTION__ . ' ERROR invalid sOldDirName');
				}
			}
		
			if(!is_null($oFmFolder))
			{
				$sActive				= (string) bab_pp('sActive', 'Y');
				$iIdApprobationScheme	= (int) bab_pp('iIdApprobationScheme', 0);
				$sAutoApprobation		= (string) bab_pp('sAutoApprobation', 'N');
				$sNotification			= (string) bab_pp('sNotification', 'N');
				$sVersioning			= (string) bab_pp('sVersioning', 'N');
				$sDisplay				= (string) bab_pp('sDisplay', 'N');
				$sAddTags				= (string) bab_pp('sAddTags', 'Y');

				$iIdOwner				= 0;
				//simpleToCollective
				if('collective' === $sType)
				{
					$oFirstCollectiveParent = BAB_FmFolderSet::getFirstCollectiveFolder($sRelativePath);
					
					if(!is_null($oFirstCollectiveParent))
					{		
						$iIdOwner = (int) $oFirstCollectiveParent->getId();
					}
				}
				
				$oFmFolder->setName($sDirName);
				$oFmFolder->setActive($sActive);
				$oFmFolder->setApprobationSchemeId($iIdApprobationScheme);
				$oFmFolder->setDelegationOwnerId(bab_getCurrentUserDelegation());
				$oFmFolder->setFileNotify($sNotification);
				$oFmFolder->setHide((('Y' === $sDisplay) ? 'N' : 'Y'));
				$oFmFolder->setRelativePath($sRelativePath);
				$oFmFolder->setAddTags($sAddTags);
				$oFmFolder->setVersioning($sVersioning);
				$oFmFolder->setAutoApprobation($sAutoApprobation);
				
				$bRedirect = false;
				
				if(true === $oFmFolder->save() && 0 !== $iIdOwner)
				{
					//To rebuild sitemap
					$bRedirect = true;
					
					require_once $GLOBALS['babInstallPath'] . 'admin/acl.php';
					
					aclDuplicateRights(BAB_FMUPLOAD_GROUPS_TBL, $iIdOwner, BAB_FMUPLOAD_GROUPS_TBL, $oFmFolder->getId());
					aclDuplicateRights(BAB_FMDOWNLOAD_GROUPS_TBL, $iIdOwner, BAB_FMDOWNLOAD_GROUPS_TBL, $oFmFolder->getId());
					aclDuplicateRights(BAB_FMUPDATE_GROUPS_TBL, $iIdOwner, BAB_FMUPDATE_GROUPS_TBL, $oFmFolder->getId());
					aclDuplicateRights(BAB_FMMANAGERS_GROUPS_TBL, $iIdOwner, BAB_FMMANAGERS_GROUPS_TBL, $oFmFolder->getId());
					aclDuplicateRights(BAB_FMNOTIFY_GROUPS_TBL, $iIdOwner, BAB_FMNOTIFY_GROUPS_TBL, $oFmFolder->getId());
				}
				
				if($bChangeFileIdOwner)
				{
					$oFirstFmFolder = BAB_FmFolderSet::getFirstCollectiveFolder($sRelativePath);
					$oFolderFileSet = new BAB_FolderFileSet();
					$sPathName = $oFmFolder->getRelativePath() . $oFmFolder->getName() . '/';
					$oFolderFileSet->setOwnerId($sPathName, $oFirstFmFolder->getId(), $oFmFolder->getId());
					
					$soFmFolderCliboardSet = new BAB_FmFolderCliboardSet();
					$soFmFolderCliboardSet->setOwnerId($sPathName, $oFirstFmFolder->getId(), $oFmFolder->getId());
				}
				
				//bab_siteMap::build();

				if(true === $bRedirect)
				{
					$sUrl = $GLOBALS['babUrlScript'] . '?tg=fileman&idx=list&id=' . $oFileManagerEnv->iId . 
						'&gr=' . $oFileManagerEnv->sGr . '&path=' . urlencode($oFileManagerEnv->sPath);
					
					header('Location: ' . $sUrl);
				}
			}
		}
		else 
		{
			$babBody->msgerror = bab_translate("Access denied");
		}
	}
	else 
	{
		$babBody->msgerror = bab_translate("Access denied");
	}
}


function editFolderForUserDir()
{
	global $babBody;
	
	$oFileManagerEnv =& getEnvObject();
	$sRelativePath = $oFileManagerEnv->sRelativePath;
	
//	if(canEdit($sRelativePath))
	if(canCreateFolder($oFileManagerEnv->sRelativePath))
	{
		$sDirName = (string) bab_pp('sDirName', '');
		$sOldDirName = (string) bab_pp('sOldDirName', '');

		if(strlen(trim($sDirName)) > 0 && strlen(trim($sOldDirName)) > 0)
		{
			$sPathName = $sRelativePath . $sOldDirName . '/';
			$sRootFmPath = $oFileManagerEnv->getRootFmPath();
			
//			bab_debug('sRootFmPath ==> ' . $sRootFmPath);
//			bab_debug('sRelativePath ==> ' . $sRelativePath);
//			bab_debug('sOldDirName ==> ' . $sOldDirName);
//			bab_debug('sDirName ==> ' . $sDirName);
//			bab_debug('sPathName ==> ' . $sPathName);
			$bFolderRenamed	= ($sDirName !== $sOldDirName) ? true : false;
			
			if($bFolderRenamed)
			{
				$sDirName = replaceInvalidFolderNameChar($sDirName);
				if(BAB_FmFolderHelper::renameDirectory($sRootFmPath, $sRelativePath, $sOldDirName, $sDirName))
				{
					BAB_FolderFileSet::renameFolder($sPathName, $sDirName, 'N');
					BAB_FmFolderCliboardSet::rename($sRelativePath, $sOldDirName, $sDirName, 'N');
				}
			}
		}
		else 
		{
			$babBody->addError(bab_translate("Access denied"));
		}
	}
	else 
	{
		$babBody->addError(bab_translate("Access denied"));
	}
}


function deleteFolder()
{
	global $babBody;

	$oFileManagerEnv =& getEnvObject();
	
	if($oFileManagerEnv->userIsInCollectiveFolder() || $oFileManagerEnv->userIsInRootFolder())
	{
		deleteFolderForCollectiveDir();
	}
	else if($oFileManagerEnv->userIsInPersonnalFolder())
	{
		deleteFolderForUserDir();
	}
	else 
	{
		$babBody->msgerror = bab_translate("Access denied");
	}
}


function deleteFolderForCollectiveDir()
{
	global $babBody, $babDB;
	$oFileManagerEnv =& getEnvObject();
	
	$sDirName = (string) bab_pp('sDirName', '');
	if(strlen(trim($sDirName)) > 0 && canCreateFolder($oFileManagerEnv->sRelativePath))
	{
		$iIdFld	= (int) bab_pp('iIdFolder', 0); 
		if(0 !== $iIdFld)
		{
			require_once $GLOBALS['babInstallPath'] . 'utilit/delincl.php';
			bab_deleteFolder($iIdFld);
		}
		else 
		{
			$bDbRecordOnly = false;
			$oFmFolderSet = new BAB_FmFolderSet();
			$oRelativePath =& $oFmFolderSet->aField['sRelativePath'];
			$oIdDgOwner =& $oFmFolderSet->aField['iIdDgOwner'];
			$oName =& $oFmFolderSet->aField['sName'];
			
			$oCriteria = $oIdDgOwner->in(bab_getCurrentUserDelegation());
			$oCriteria = $oCriteria->_and($oRelativePath->like($babDB->db_escape_like($oFileManagerEnv->sRelativePath . $sDirName . '/') . '%'));
			//bab_debug($oFmFolderSet->getSelectQuery($oCriteria));
			$oFmFolderSet->remove($oCriteria, $bDbRecordOnly);
		
			/*	
			
			$oFolderFileSet = new BAB_FolderFileSet();
			$oPathName =& $oFolderFileSet->aField['sPathName'];
			$oCriteria = $oPathName->like($babDB->db_escape_like($sPathName) . '%');
			$oFolderFileSet->remove($oCriteria);
			//*/
			
			$sUploadPath = $oFileManagerEnv->getRootFmPath();
			$sPathName = $oFileManagerEnv->sRelativePath . $sDirName . '/';
			$sFullPathName = $sUploadPath . $sPathName;
			$oFmFolderSet->removeDir($sFullPathName);
			
			$oFmFolderCliboardSet = new BAB_FmFolderCliboardSet();
			$oFmFolderCliboardSet->deleteFolder($sDirName, $oFileManagerEnv->sRelativePath, 'Y');
			//*/
		}						
	}
	else 
	{
		$babBody->msgerror = bab_translate("Access denied");
	}
}


function deleteFolderForUserDir()
{
	global $babBody;
	
	$oFileManagerEnv =& getEnvObject();
	
	if(userHavePersonnalStorage() && canCreateFolder($oFileManagerEnv->sRelativePath))
	{
		$sDirName = (string) bab_pp('sDirName', '');
		if(strlen(trim($sDirName)) > 0)
		{
			$sCurrentFmPath = $oFileManagerEnv->getCurrentFmPath();
			$sUplaodPath = $oFileManagerEnv->getRootFmPath();
			
			global $babDB;
		
			$sPathName = $oFileManagerEnv->sRelativePath . '/' . $sDirName . '/';
			$sFullPathName = $sCurrentFmPath . $sPathName;
			
			$oFolderFileSet = new BAB_FolderFileSet();
			$oPathName =& $oFolderFileSet->aField['sPathName'];
			$oFolderFileSet->remove($oPathName->like($babDB->db_escape_like($sPathName) . '%'));
			
			$oFmFolderSet = new BAB_FmFolderSet();
			$oFmFolderSet->removeDir($sFullPathName);
			
			$oFmFolderCliboardSet = new BAB_FmFolderCliboardSet();
			$oFmFolderCliboardSet->deleteFolder($sDirName, $oFileManagerEnv->sRelativePath, 'N');
		}
	}
	else 
	{
		$babBody->msgerror = bab_translate("Access denied");
	}
}


function changeDelegation()
{
	$aVisibleDelegation = bab_getUserFmVisibleDelegations();
	$iDelegation = (int) bab_pp('iDelegation', 0);
	
	if(array_key_exists($iDelegation, $aVisibleDelegation))
	{
		bab_setCurrentUserDelegation($iDelegation);
	}
}


/* main */
initEnvObject();

$oFileManagerEnv =& getEnvObject();
if(false === $oFileManagerEnv->accessValid())
{
	$babBody->addError(bab_translate("Access denied"));
	return;	
}

$idx = bab_rp('idx','list');

$sAction = isset($_POST['sAction']) ? $_POST['sAction'] : 
	(isset($_GET['sAction']) ? $_GET['sAction'] :  
	(isset($_POST['setRight']) ? 'setRight' : '???')
	);


switch($sAction)
{
	case 'createFolder':
		createFolder();
		break;
		
	case 'editFolder':
		if(!isset($_POST['sDeleteFolder']))
		{
			editFolder();
		}
		else 
		{
			$idx = 'displayDeleteFolderConfirm';
		}
		break;
		
	case 'cutFolder':
		cutFolder();
		break;

	case 'pasteFolder':
		pasteFolder();
		break;

	case 'deleteFolder':
		deleteFolder();
		break;
	
	case 'deleteRestoreFile':
		if(!empty($_REQUEST['delete'])) 
		{
			deleteFiles(bab_rp('items'));
		}
		else 
		{
			restoreFiles(bab_rp('items'));
		}
		break;

	case 'setRight':
		setRight();
		break;

	case 'saveFile':
		$aFiles = array();
		foreach($_FILES as $sFieldname => $file) 
		{
			$aFiles[] = bab_fmFile::upload($sFieldname);
		}
	
		$bSuccess = saveFile($aFiles, $oFileManagerEnv->iId, $oFileManagerEnv->sGr,
				$oFileManagerEnv->sPath, bab_pp('description'), bab_pp('keywords'), 
				bab_pp('readonly'));
		if(false === $bSuccess)
		{
			$idx = "displayAddFileForm";
		}
		break;
		
	case 'updateFile':
		$bSuccess = saveUpdateFile(bab_pp('idf'), bab_fmFile::upload('uploadf'), 
			bab_pp('fname'), bab_pp('description'), bab_pp('keywords'), 
			bab_pp('readonly'), bab_pp('confirm'), bab_pp('bnotify'), 
			isset($_POST['description']));
		if(false === $bSuccess)
		{
			$idx = 'viewFile';
		}
		else
		{
			$idx = 'unload';
		}
		break;
		
	case 'getFile':
		getFile();
		break;

	case 'cutFile':
		cutFile();
		break;
		
	case 'pasteFile':
		pasteFile();
		break;
		
	case 'delFile':
		delFile();
		break;
		
	case 'changeDelegation':
		changeDelegation();
		break;
}
//*/


switch($idx)
{
	case 'displayFolderForm':
		displayFolderForm();
		break;
		
	case 'displayDeleteFolderConfirm':
		displayDeleteFolderConfirm();
		break;
		
	case 'unload':
		fileUnload();
		exit;
		break;

	case 'viewFile':
		viewFile();
		exit;
		break;

	case 'displayRightForm':
		displayRightForm();
		break;
		
	case 'displayAddFileForm':
		displayAddFileForm();
		break;

	case 'trash':
		listTrashFiles();
		break;

	case 'disk':
		showDiskSpace();
		break;
		
	default:
	case 'list':
		listFiles();
		break;
}
$babBody->setCurrentItemMenu($idx);
?>