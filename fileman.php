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
	var $arrdir = array();
	var $buaf;
	
	var $aGrpFolder = array(); 
	
	var $oRegHlp = null;
	var $oFolderFileSet = null;
	
	var $aCuttedDir = array();
	
	var $sProcessedIdx = '';	
	var $sListFunctionName = '';
	
	var $bParentUrl = false;
	var $sParent = '. .';
	var $bVersion = false;

	var $oFileManagerEnv = null;
	
	var $sRootFolderPath = '';
	/**
	 * Files extracted by readdir
	 */
	var $files_from_dir = array();

	function listFiles($what="list")
	{
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

		$sGr = $this->oFileManagerEnv->sGr;
		$iId = $this->oFileManagerEnv->iId;
		$sPath = $this->oFileManagerEnv->sPath;

		$this->path = $sPath;
		$this->jpath = bab_toHtml($sPath, BAB_HTML_JS);
		$this->id = $iId;
		$this->gr = $sGr;
		$this->countmgrp = 0;
		$this->buaf = false;
		$this->countwf = 0;

//		bab_debug($this->oFileManagerEnv);
		$iPathLength = $this->oFileManagerEnv->iPathLength;
		
		if('Y' === $sGr)
		{
			$this->sRootFolderPath = $this->oFileManagerEnv->sRootFolderPath;
			
			$this->sListFunctionName = 'listCollectiveFolder';
			
			if('list' === $this->sProcessedIdx && $iPathLength !== 0)
			{
				$oFmFolder = $this->oFileManagerEnv->oFmFolder;
				if(!is_null($oFmFolder))
				{			
					if(0 !== $oFmFolder->getApprobationSchemeId())
					{
						$this->buaf = isUserApproverFlow($oFmFolder->getApprobationSchemeId(), $BAB_SESS_USERID);
						if($this->buaf)
						{
							$this->selectWaitingFile($oFmFolder->getRelativePath());
						}
					}
				}
			}
			
			if($iPathLength !== 0)
			{
				$this->bParentUrl = true;
			}
			$this->initCollectiveFolderCuttedDir();
		}
		else if('N' === $sGr)
		{
			if($iPathLength !== 0)
			{
				$this->sRootFolderPath = $this->oFileManagerEnv->sRootFolderPath;
				$this->sListFunctionName = 'listUserFolder';
				$this->bParentUrl = true;
			}
			else 
			{
				$this->sListFunctionName = 'listRootFolders';
			}
			$this->initUserFolderCuttedDir();
		}
		
//		bab_debug(__LINE__ . ' ' . basename(__FILE__) . ' ' . __FUNCTION__ . ' sListFunctionName ==> ' . $this->sListFunctionName);
		
		$sPath = '';
		$aPath = explode('/', $this->path);
		$iCount = count($aPath);
		if($iCount >= 1)
		{
			unset($aPath[$iCount - 1]);
			$sPath = urlencode((string) implode('/', $aPath));
		}
		
		$this->sParentUrl = bab_toHtml($GLOBALS['babUrlScript'] . '?tg=fileman&idx=' . urlencode($this->sProcessedIdx) . '&id=' . $this->id . 
			'&gr=' . $this->gr . '&path=' . $sPath);
	}
	

	function listRootFolders()
	{
		$this->listUserFolder();
		
		$oFmFolderSet = new BAB_FmFolderSet();
		$oRelativePath =& $oFmFolderSet->aField['sRelativePath'];
		$oFmFolderSet->select($oRelativePath->in(''));
		
		$sUrlPath = '';
		while(null !== ($oFmFolder = $oFmFolderSet->next()))
		{
			$this->addCollectiveDirectory($oFmFolder, $oFmFolder->getId(), $sUrlPath);
		}
	}
	
	function listUserFolder()
	{
		$sUploadPath = BAB_FmFolderHelper::getUploadPath();
		$this->walkDirectory($sUploadPath . $this->oFileManagerEnv->sRelativePath, 'simpleDirectoryCallback');
		natcasesort($this->arrdir);
	}
	
	function listCollectiveFolder()
	{
		$sUploadPath = BAB_FmFolderHelper::getUploadPath();
		$this->walkDirectory($sUploadPath . $this->oFileManagerEnv->sRelativePath, 'collectiveDirectoryCallback');
	}
	
	function walkDirectory($sPathName, $sCallbackFunction)
	{
//		bab_debug(__LINE__ . ' ' . basename(__FILE__) . ' ' . __FUNCTION__ . ' sPathName ==> ' . $sPathName);
		
		if(is_dir($sPathName))
		{
			$oDir = dir($sPathName);
			while(false !== $sEntry = $oDir->read()) 
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
//		bab_debug(__LINE__ . ' ' . basename(__FILE__) . ' ' . __FUNCTION__ . ' sPathName ==> ' . $sPathName . ' sEntry ==> ' . $sEntry);
		if(is_dir($sPathName . $sEntry)) 
		{
			$bInClipBoard = $this->oRegHlp->exist($this->oFileManagerEnv->sRelativePath . $sEntry . '/');
		
			if(false === $bInClipBoard || (true == $bInClipBoard && (0 === $this->aCuttedDir[$this->oFileManagerEnv->sRelativePath . $sEntry . '/']['ma'] || false === $this->oFileManagerEnv->oAclFm->haveManagerRight())))
			{
				$this->arrdir[] = $sEntry;
			}
		} 
		else 
		{
			$this->files_from_dir[] = $sEntry;
		}
	}

	
	function collectiveDirectoryCallback($sPathName, $sEntry)
	{
//		bab_debug(__LINE__ . ' ' . basename(__FILE__) . ' ' . __FUNCTION__ . ' sPathName ==> ' . $sPathName);
		
		$sPath = $this->oFileManagerEnv->sRelativePath . $sEntry ;
		$oFmFolderSet = new BAB_FmFolderSet();
		$oName =& $oFmFolderSet->aField['sName'];
		$oRelativePath =& $oFmFolderSet->aField['sRelativePath'];
		
		$oCriteria = $oName->in($sEntry);
		$oCriteria = $oCriteria->_and($oRelativePath->in($this->oFileManagerEnv->sRelativePath));
		
		$oFmFolder = $oFmFolderSet->get($oCriteria);
		if(!is_null($oFmFolder))
		{
			$sUrlPath = $oFmFolder->getRelativePath() . $oFmFolder->getName() . '/';
			$this->addCollectiveDirectory($oFmFolder, $this->id, $sUrlPath);
		}
		else 
		{
			$this->simpleDirectoryCallback($sPathName, $sEntry);
		}
	}
	
	function addCollectiveDirectory($oFmFolder, $iIdRootFolder, $sUrlPath)
	{
		$sPathName = $oFmFolder->getRelativePath() . $oFmFolder->getName() . '/';
		
//		bab_debug(__LINE__ . ' ' . basename(__FILE__) . ' ' . __FUNCTION__ . ' sPathName ==> ' . $sPathName);
		
		$bInClipBoard = $this->oRegHlp->exist($sPathName);
		$bFolderManager = bab_isAccessValid(BAB_FMMANAGERS_GROUPS_TBL, $oFmFolder->getId());

		if(false === $bInClipBoard || (true == $bInClipBoard && (false === $bFolderManager || false === $this->oFileManagerEnv->oAclFm->haveManagerRight())))
		{
			$aItem = array(
				'id' => $oFmFolder->getId(), 
				'ma' => ($bFolderManager) ? 1 : 0, 
				'folder' => $oFmFolder->getName(), 
				'hide' => ('Y' === $oFmFolder->getHide() && false === $bFolderManager) ? true :  false,
				'sUrlPath' => getUrlPath($sUrlPath),
				'iIdUrl' => $iIdRootFolder);
			
//			bab_debug($aItem);		
			$this->aGrpFolder[$sPathName] = $aItem;
		}
	}
		
	
	function initCollectiveFolderCuttedDir()
	{
		$sDirectory = '/bab/fileManager/cuttedFolder/';
		$sRegKey = md5($this->oFileManagerEnv->sRootFolderPath);
		$this->oRegHlp = new BAB_RegitryHelper($sDirectory, $sRegKey);

		$oFmFolderSet = new BAB_FmFolderSet();
		$oName =& $oFmFolderSet->aField['sName'];
		$oRelativePath =& $oFmFolderSet->aField['sRelativePath'];
		
//		bab_debug(__FUNCTION__);
		
		$sUploadPath = BAB_FmFolderHelper::getUploadPath();
		$sFullCurrFolder = realpath($sUploadPath . $this->oFileManagerEnv->sRelativePath);
		
		$oAclFm = $this->oFileManagerEnv->oAclFm;

		$aCuttedDir = $this->oRegHlp->getDatas();
		foreach($aCuttedDir as $sKey => $aValue)
		{
//			bab_debug(__LINE__ . ' ' . basename(__FILE__) . ' ' . __FUNCTION__ . ' sRelativePath ==> ' . $aValue['sRelativePath']);
				
			$sFullCuttedPath = realpath($sUploadPath . $aValue['sRelativePath']);
			$sPath = substr($sFullCurrFolder, 0, strlen($sFullCuttedPath));
			if($sPath !== $sFullCuttedPath)
			{
				$sRelativePath = removeLastPath($aValue['sRelativePath']);
				$sUrlPath = removeFirstPath($aValue['sRelativePath']);

				$iLength = strlen(trim($sUrlPath));
				if($iLength > 0)
				{
					if('/' === $sUrlPath{$iLength - 1})
					{
						$sUrlPath = substr($sUrlPath, 0, -1);
					}
				}

				$oCriteria = $oName->in($aValue['sName']);
				$oCriteria = $oCriteria->_and($oRelativePath->in($sRelativePath . '/'));
				$oFmFolder = $oFmFolderSet->get($oCriteria);
				if(!is_null($oFmFolder))
				{
					$bFolderManager = bab_isAccessValid(BAB_FMMANAGERS_GROUPS_TBL, $oFmFolder->getId());
			
//					bab_debug(__LINE__ . ' ' . basename(__FILE__) . ' ' . __FUNCTION__ . 
//						' sRelativePath ==> ' . $aValue['sRelativePath'] . 
//						' bFolderManager ==> ' . (($bFolderManager) ? 'Yes' : 'No') .
//						' haveManagerRight ==> ' . (($oAclFm->haveManagerRight()) ? 'Yes' : 'No'));
					
					if($bFolderManager && $oAclFm->haveManagerRight())
					{
						$aItem = array(
							'id' => $oFmFolder->getId(), 
							'ma' => $bFolderManager ? 1 : 0, 
							'folder' => $oFmFolder->getName(), 
							'sUrlPath' => $sUrlPath,
							'iIdUrl' => $this->id,
							'iIdOwner' => $oFmFolder->getId());
						
//						bab_debug($aItem);
						$sPathName = $oFmFolder->getRelativePath() . $oFmFolder->getName() . '/';
						$this->aCuttedDir[$sPathName] = $aItem;
					}
				}
				else 
				{
					$oFmFolder		=  null;
					$iIdOwner		= 0;
					$sRelativePath	= '';
					
					BAB_FmFolderHelper::getFileInfoForCollectiveDir($this->id, $sUrlPath, $iIdOwner, $sRelativePath, $oFmFolder);
					$bFolderManager = bab_isAccessValid(BAB_FMMANAGERS_GROUPS_TBL, $iIdOwner);

//					bab_debug(__LINE__ . ' ' . basename(__FILE__) . ' ' . __FUNCTION__ . 
//						' sUrlPath ==> ' . $sUrlPath . ' iIdOwner ==> ' . $iIdOwner);
					
					if($bFolderManager && $oAclFm->haveManagerRight())
					{
						$aItem = array(
							'id' => 0, 
							'ma' => $bFolderManager ? 1 : 0, 
							'folder' => $aValue['sName'], 
							'sUrlPath' => $sUrlPath,
							'iIdUrl' => $this->id,
							'iIdOwner' => $iIdOwner);
						$this->aCuttedDir[$sRelativePath] = $aItem;
					}
				}
			}
		}
	}		
	
	
	function initUserFolderCuttedDir()
	{
		$sDirectory = '/bab/fileManager/cuttedFolder/';
		$sRegKey = md5($this->oFileManagerEnv->sRootFolderPath);
		$this->oRegHlp = new BAB_RegitryHelper($sDirectory, $sRegKey);

		$oFmFolderSet = new BAB_FmFolderSet();
		$oName =& $oFmFolderSet->aField['sName'];
		$oRelativePath =& $oFmFolderSet->aField['sRelativePath'];
		
//		bab_debug(__FUNCTION__);
		
		$sUploadPath = BAB_FmFolderHelper::getUploadPath();
		$sFullCurrFolder = realpath($sUploadPath . $this->oFileManagerEnv->sRelativePath);
		
		$oAclFm = $this->oFileManagerEnv->oAclFm;

		$aCuttedDir = $this->oRegHlp->getDatas();
		foreach($aCuttedDir as $sKey => $aValue)
		{
//			bab_debug(__LINE__ . ' ' . basename(__FILE__) . ' ' . __FUNCTION__ . ' sRelativePath ==> ' . $aValue['sRelativePath']);
				
			$sFullCuttedPath = realpath($sUploadPath . $aValue['sRelativePath']);
			$sPath = substr($sFullCurrFolder, 0, strlen($sFullCuttedPath));
			
			if($sPath !== $sFullCuttedPath && 0 !== $this->oFileManagerEnv->iPathLength)
			{
				$sRelativePath = removeLastPath($aValue['sRelativePath']);
				$sUrlPath = removeFirstPath($aValue['sRelativePath']);
				
				$iLength = strlen(trim($sUrlPath));
				if($iLength > 0)
				{
					if('/' === $sUrlPath{$iLength - 1})
					{
						$sUrlPath = substr($sUrlPath, 0, -1);
					}
				}
					
				if($oAclFm->haveManagerRight())
				{
					$aItem = array(
						'id' => 0, 
						'ma' => 1, 
						'folder' => $aValue['sName'], 
						'sUrlPath' => $sUrlPath,
						'iIdUrl' => $this->id,
						'iIdOwner' => $this->id);
					$this->aCuttedDir[$sRelativePath . '/' . $aValue['sName'] . '/'] = $aItem;
				}
			}
		}
	}

	
	function selectWaitingFile()
	{
		$aWaitingAppInstanceId = bab_getWaitingIdSAInstance($GLOBALS['BAB_SESS_USERID']);
		if(count($aWaitingAppInstanceId) > 0)
		{
			$this->oFolderFileSet->bUseAlias = false;
			$oIdOwner =& $this->oFolderFileSet->aField['iIdOwner'];
			$oGroup =& $this->oFolderFileSet->aField['sGroup'];
			$oState =& $this->oFolderFileSet->aField['sState'];
			$oPathName =& $this->oFolderFileSet->aField['sPathName'];
			$oConfirmed =& $this->oFolderFileSet->aField['sConfirmed'];
			$oIdFlowApprobationInstance = $this->oFolderFileSet->aField['iIdFlowApprobationInstance'];
			
			$oCriteria = $oIdOwner->in($this->oFileManagerEnv->iIdObject);
			$oCriteria = $oCriteria->_and($oGroup->in('Y'));
			$oCriteria = $oCriteria->_and($oState->in(''));
			$oCriteria = $oCriteria->_and($oPathName->in($this->oFileManagerEnv->sRelativePath));
			$oCriteria = $oCriteria->_and($oConfirmed->in('N'));
			$oCriteria = $oCriteria->_and($oIdFlowApprobationInstance->in($aWaitingAppInstanceId));
			
//			bab_debug(__LINE__ . ' ' . basename(__FILE__) . ' ' . __FUNCTION__);
			$this->oFolderFileSet->select($oCriteria);
			$this->reswf = $this->oFolderFileSet->_oResult;
			$this->countwf = $this->oFolderFileSet->count();
			$this->oFolderFileSet->bUseAlias = true;
		}
		else
		{
			$this->countwf = 0;
		}
	}
		
	function prepare() 
	{
		$this->oFolderFileSet->bUseAlias = false;
		$oIdOwner = $this->oFolderFileSet->aField['iIdOwner'];
		$oGroup = $this->oFolderFileSet->aField['sGroup'];
		$oState = $this->oFolderFileSet->aField['sState'];
		$oPathName = $this->oFolderFileSet->aField['sPathName'];
		$oConfirmed = $this->oFolderFileSet->aField['sConfirmed'];
		
		$oCriteria = $oIdOwner->in($this->oFileManagerEnv->iIdObject);
		$oCriteria = $oCriteria->_and($oGroup->in($this->gr));
		$oCriteria = $oCriteria->_and($oState->in(''));
		$oCriteria = $oCriteria->_and($oPathName->in($this->oFileManagerEnv->sRelativePath));
		$oCriteria = $oCriteria->_and($oConfirmed->in('Y'));
//		bab_debug(__LINE__ . ' ' . basename(__FILE__) . ' ' . __FUNCTION__);
//		bab_debug($this->oFolderFileSet->getSelectQuery($oCriteria));
		$this->oFolderFileSet->select($oCriteria);
		$this->res = $this->oFolderFileSet->_oResult;
		$this->count = $this->oFolderFileSet->count();
//		bab_debug(__LINE__ . ' ' . basename(__FILE__) . ' ' . __FUNCTION__ . ' iCount ==> ' . $this->count);
		$this->oFolderFileSet->bUseAlias = true;
	}


	/** 
	 * if there is file not presents in database, add and recreate $this->res
	 */
	function autoadd_files() 
	{
		global $babDB;
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
			
			$oFolderFile = new BAB_FolderFile();
			foreach($this->files_from_dir as $dir_file) 
			{
				$oCriteria = $oIdOwner->in($this->oFileManagerEnv->iIdObject);
				$oCriteria = $oCriteria->_and($oPathName->in($this->oFileManagerEnv->sRelativePath));
				$oCriteria = $oCriteria->_and($oGroup->in($this->gr));
				$oCriteria = $oCriteria->_and($oName->in($dir_file));
				
//				bab_debug(__LINE__ . ' ' . basename(__FILE__) . ' ' . __FUNCTION__);
				
				$this->oFolderFileSet->select($oCriteria);
				
				if(0 === $this->oFolderFileSet->count())
				{
//					bab_debug(__LINE__ . ' ' . basename(__FILE__) . ' ' . __FUNCTION__);
					
					$oFolderFile->setName($dir_file);
					$oFolderFile->setPathName($this->oFileManagerEnv->sRelativePath);
					$oFolderFile->setOwnerId($this->oFileManagerEnv->iIdObject);
					$oFolderFile->setGroup($this->gr);
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
		
		$sAction 	= (string) bab_gp('sAction', '');
		$sDirName	= (string) bab_gp('sDirName', '');
		$iIdFolder 	= (int) bab_gp('iIdFolder', 0);
		
		$this->set_data('sIdx', 'processFolderCommand');
		$this->set_data('sAction', $sAction);
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
		
		if('createFolder' === $sAction)
		{
			$this->handleCreation();
		}
		else if('editFolder' === $sAction)
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
		$this->set_data('isCollective', false);
		$this->set_data('isActive', true);
		$this->set_data('isAutoApprobation', false);
		$this->set_data('isFileNotify', false);
		$this->set_data('isVersioning', false);
		$this->set_data('isShow', true);
		$this->set_data('sChecked', 'checked');
	}
	
	function handleEdition()
	{
		$this->set_data('isCollective', false);
		$this->set_data('isActive', true);
		$this->set_data('isAutoApprobation', false);
		$this->set_data('isFileNotify', false);
		$this->set_data('isVersioning', false);
		$this->set_data('isShow', true);
		$this->set_data('sChecked', 'checked');

		$this->get_data('iId', $iId);
		$this->get_data('sPath', $sPath);
		$this->get_data('sDirName', $sDirName);
		$this->set_data('sOldDirName', $sDirName);
		$this->get_data('iIdFolder', $iIdFolder);
		
		$oFmFolder = $oFmFolder = BAB_FmFolderHelper::getFmFolderById($iIdFolder);
		if(!is_null($oFmFolder))
		{
			$this->iApprobationSchemeId = $oFmFolder->getApprobationSchemeId();
			$this->set_data('isCollective', true);
			$this->set_data('isActive', ('Y' === $oFmFolder->getActive()) ? true : false);
			$this->set_data('isAutoApprobation', ('Y' === $oFmFolder->getAutoApprobation()) ? true : false);
			$this->set_data('isFileNotify', ('Y' === $oFmFolder->getFileNotify()) ? true : false);
			$this->set_data('isVersioning', ('Y' === $oFmFolder->getVersioning()) ? true : false);
			$this->set_data('isShow', ('Y' === $oFmFolder->getHide()) ? false : true);
			$this->set_data('iIdFolder', $oFmFolder->getId());
			$this->set_data('sOldDirName', $oFmFolder->getName());
			$this->set_data('sChecked', '');
		}

		$oFileManagerEnv =& getEnvObject();
		$this->set_data('bDelete', $oFileManagerEnv->oAclFm->haveManagerRight());
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


function listTrashFiles($id, $gr, $path)
{
	global $babBody;

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

		function temp($id, $gr, $path)
		{
			global $babDB;
			$this->id = $id;
			$this->gr = $gr;
			$this->sPath = $path;
			$this->bytes = bab_translate("bytes");
			$this->delete = bab_translate("Delete");
			$this->restore = bab_translate("Restore");
			$this->nametxt = bab_translate("Name");
			$this->sizetxt = bab_translate("Size");
			$this->modifiedtxt = bab_translate("Modified");
			$this->postedtxt = bab_translate("Posted by");
			$this->checkall = bab_translate("Check all");
			$this->uncheckall = bab_translate("Uncheck all");
			$this->fullpath = bab_getUploadFullPath($gr, $id);
			$this->selectTrashFile($this->sPath);
				
			$iIdOwner = $this->id;
			if('N' === $this->gr)
			{
				$oFileManagerEnv =& getEnvObject();
				$this->sRelativePath = $oFileManagerEnv->sRelativePath;
			}
			else 
			{
				$oFmFolder = null;
				BAB_FmFolderHelper::getFileInfoForCollectiveDir($this->id, $this->sPath, $iIdOwner, $this->sRelativePath, $oFmFolder);
			}
			
			$this->selectTrashFile($this->sRelativePath);
		}

		function selectTrashFile($sPathName)
		{
			global $babDB;
			$this->oFolderFileSet = new BAB_FolderFileSet();
			$oState =& $this->oFolderFileSet->aField['sState'];
			$oPathName =& $this->oFolderFileSet->aField['sPathName'];
			
			$oCriteria = $oState->in('D');
			$oCriteria = $oCriteria->_and($oPathName->like($babDB->db_escape_like($sPathName)));
			
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
					$sUploadPath = BAB_FmFolderHelper::getUploadPath();
					
					if(file_exists($sUploadPath . $this->sRelativePath . '/' . $oFolderFile->getName()))
					{
						$fstat = stat($sUploadPath . $this->sRelativePath . '/' . $oFolderFile->getName());
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

	$temp = new temp($id, $gr, $path);
	$babBody->babecho(bab_printTemplate($temp,"fileman.html", "trashfiles"));
}

function showDiskSpace($id, $gr, $path)
	{
	global $babBody;

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

		function temp($id, $gr, $path)
			{
			global $babBody;
			$this->id = $id;
			$this->gr = $gr;
			$this->grouptxt = bab_translate("Name");
			$this->diskspacetxt = bab_translate("Used");
			$this->allowedspacetxt = bab_translate("Allowed");
			$this->remainingspacetxt = bab_translate("Remaining");
			$this->cancel = bab_translate("Close");
			$this->bytes = bab_translate("bytes");
			$this->kilooctet = " ".bab_translate("Kb");
			$this->babCss = bab_printTemplate($this,"config.html", "babCss");

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
					$this->arrgrp[] = 	$oFmFolder->getId();
				}
			}
				
			$oFileManagerEnv =& getEnvObject();
			if(!empty($GLOBALS['BAB_SESS_USERID']) && $oFileManagerEnv->oAclFm->userHaveStorage())
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
				$pathx = bab_getUploadFullPath("N", $GLOBALS['BAB_SESS_USERID']);
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
				$size = getDirSize($GLOBALS['babUploadPath']);
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

		function getnextgrp()
			{
			static $i = 0;
			if( $i < $this->countgrp)
				{
				$pathx = bab_getUploadFullPath("Y", $this->arrgrp[$i]);
				$size = getDirSize($pathx);
				
				$this->diskspace = bab_toHtml(bab_formatSizeFile($size).$this->kilooctet);
				$this->allowedspace =  bab_toHtml(bab_formatSizeFile($GLOBALS['babMaxGroupSize']).$this->kilooctet);
				$this->remainingspace =  bab_toHtml(bab_formatSizeFile($GLOBALS['babMaxGroupSize'] - $size).$this->kilooctet);
				$this->groupname = '';
				$oFmFolder = BAB_FmFolderHelper::getFmFolderById($this->arrgrp[$i]);
				if(!is_null($oFmFolder))
				{
					$this->groupname = $oFmFolder->getName();
				}
					
				$i++;
				return true;
				}
			else
				return false;
			}

		function getnextmgrp()
			{
			static $i = 0;
			if( $i < $this->countmgrp)
				{
				$this->groupname = '';
				$oFmFolder = BAB_FmFolderHelper::getFmFolderById($this->arrmgrp[$i]);
				if(!is_null($oFmFolder))
				{
					$this->groupname = $oFmFolder->getName();
				}
					
				$pathx = bab_getUploadFullPath("Y", $this->arrmgrp[$i]);
				$size = getDirSize($pathx);
				$this->diskspace = bab_toHtml(bab_formatSizeFile($size).$this->kilooctet);
				$this->allowedspace =  bab_toHtml(bab_formatSizeFile($GLOBALS['babMaxGroupSize']).$this->kilooctet);
				$this->remainingspace =  bab_toHtml(bab_formatSizeFile($GLOBALS['babMaxGroupSize'] - $size).$this->kilooctet);
				$i++;
				return true;
				}
			else
				return false;
			}

		}

	$temp = new temp($id, $gr, $path);
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
        var $bdel;
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
		
		var $altfilelog;
		var $altfilelock;
		var $altfileunlock;
		var $altfilewrite;
		var $altbg = false;

		var $bManager = false;
		var $bDownload = false;
		var $bUpdate = false;

		function temp()
		{
			global $BAB_SESS_USERID, $babDB;
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

			$this->rooturl = bab_toHtml($GLOBALS['babUrlScript']."?tg=fileman&idx=list");
			$this->refreshurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=fileman&idx=list&id=".$this->id."&gr=".$this->gr."&path=".urlencode($this->path));
			$this->urldiskspace = bab_toHtml($GLOBALS['babUrlScript']."?tg=fileman&idx=disk&id=".$this->id."&gr=".$this->gr."&path=".urlencode($this->path));
			
			$this->sAddFolderFormUrl = bab_toHtml($GLOBALS['babUrlScript']."?tg=fileman&idx=displayFolderForm&sAction=createFolder&id=".$this->id."&gr=".$this->gr."&path=".urlencode($this->path));

			$this->sCutFolderUrl = '#'; 
			$this->bCutFolderUrl = false;

			$this->upfolderimg = bab_printTemplate($this, "config.html", "parentfolder");
			$this->usrfolderimg = bab_printTemplate($this, "config.html", "userfolder");
			$this->grpfolderimg = bab_printTemplate($this, "config.html", "groupfolder");
			$this->manfolderimg = bab_printTemplate($this, "config.html", "managerfolder");

			$this->bdel = false;
			
			$this->bManager = $this->oFileManagerEnv->oAclFm->haveManagerRight();
			if(false === $this->bManager)
			{
				$this->bDownload = $this->oFileManagerEnv->oAclFm->haveDownloadRight(); 
				$this->bUpdate = $this->oFileManagerEnv->oAclFm->haveUpdateRight();  
			}
			else 
			{
				$this->bDownload = true;
				$this->bUpdate = true;
			}
			
			$this->bVersion = (!is_null($this->oFileManagerEnv->oFmFolder) && 'Y' === $this->oFileManagerEnv->oFmFolder->getVersioning());
			
			if($this->oFileManagerEnv->oAclFm->haveDownloadRight())
			{
				$this->oFolderFileSet->bUseAlias = false;
				$oState = $this->oFolderFileSet->aField['sState'];
				$oPathName = $this->oFolderFileSet->aField['sPathName'];
				
				global $babDB;
				$oCriteria = $oPathName->like($babDB->db_escape_like($this->oFileManagerEnv->sRootFolderPath) . '%');
				$oCriteria = $oCriteria->_and($oState->in('X'));
				
				$this->oFolderFileSet->select($oCriteria);
				$this->xres = $this->oFolderFileSet->_oResult;
				$this->xcount = $this->oFolderFileSet->count();
				$this->oFolderFileSet->bUseAlias = true;

				if(!empty($path) && count($this->arrdir) <= 1 && $this->count == 0)
				{
					$this->bdel = true;
				}
			}
			else
			{
				$this->xcount = 0;
			}
		}

		function getnextdir()
		{
			static $i = 0;
			if($i < count($this->arrdir))
			{
				$this->altbg			= !$this->altbg;
				$this->name				= bab_toHtml($this->arrdir[$i]);
				$this->bFolderFormUrl	= false;
				$this->bCutFolderUrl	= false;
				static $aExcludedDir	= array('.', '..', '. .');
				
				$sEncodedName	= urlencode($this->name);
				$sEncodedPath	= urlencode($this->path);
				$sPath			= $sEncodedPath;
				
				if(!in_array($this->name, $aExcludedDir))	
				{	
					$this->bFolderFormUrl	= $this->oFileManagerEnv->oAclFm->haveManagerRight();
					$this->bCutFolderUrl	= ('N' === $this->oFileManagerEnv->sGr && $this->oFileManagerEnv->iPathLength !== 0 || 'Y' === $this->oFileManagerEnv->sGr && $this->oFileManagerEnv->oAclFm->haveManagerRight());
//					$this->bCutFolderUrl	= $this->oFileManagerEnv->oAclFm->haveManagerRight();
					
					$this->sFolderFormUrl = bab_toHtml($GLOBALS['babUrlScript'] . '?tg=fileman&idx=displayFolderForm&sAction=editFolder&id=' . $this->id . 
						'&gr=' . $this->gr . '&path=' . $sEncodedPath . '&sDirName=' . $sEncodedName);
					
					$this->sCutFolderUrl = bab_toHtml($GLOBALS['babUrlScript'] . '?tg=fileman&idx=cutFolder&id=' . $this->id . 
						'&gr=' . $this->gr . '&path=' . $sEncodedPath . '&sDirName=' . $sEncodedName);
				
					$sPath .= $this->oFileManagerEnv->sEndSlash . $sEncodedName;
				}
				$this->url = bab_toHtml($GLOBALS['babUrlScript'] . '?tg=fileman&idx=' . urlencode($this->sProcessedIdx) . '&id=' . $this->id . 
					'&gr=' . $this->gr . '&path=' . $sPath);
				
				$i++;
				return true;
			}
			else
			{
				return false;
			}
		}
		
		function getnextgrpdir(&$skip)
		{
			$aItem = each($this->aGrpFolder);
			if(false !== $aItem)
			{
//				bab_debug($aItem);
				$sRelativePath			= $aItem['key'];
				$aItem					= $aItem['value'];
				$bHaveManagerRight		= (1 == $aItem['ma']);
				$iIdRootFolder			= $aItem['iIdUrl'];
				$iIdFolder				= $aItem['id'];
				$this->bRightUrl		= $bHaveManagerRight;
				$this->bCutFolderUrl	= ($bHaveManagerRight && 'Y' === $this->oFileManagerEnv->sGr && false === $this->oRegHlp->exist($sRelativePath));
				$this->bFolderFormUrl	= $bHaveManagerRight;
				$sEncodedPath			= urlencode($this->path);
				$sEncodedName			= urlencode($aItem['folder']);
				$sUrlEncodedPath		= urlencode($aItem['sUrlPath']);
				
				$this->sRightUrl = bab_toHtml($GLOBALS['babUrlScript'] . '?tg=fileman&idx=displayRightForm&id=' . $iIdRootFolder . 
					'&gr=Y&path=' . $sEncodedPath . '&iIdFolder=' . $iIdFolder);

				$this->sFolderFormUrl = bab_toHtml($GLOBALS['babUrlScript'] . '?tg=fileman&idx=displayFolderForm&sAction=editFolder&id=' . $iIdRootFolder . 
					'&gr=Y&path=' . $sEncodedPath . '&sDirName=' . $sEncodedName . '&iIdFolder=' . $iIdFolder);
					
				$this->sCutFolderUrl = bab_toHtml($GLOBALS['babUrlScript'] . '?tg=fileman&idx=cutFolder&id=' . $iIdRootFolder . 
					'&gr=Y&path=' . $sEncodedPath . '&sDirName=' . $sEncodedName);

				$this->altbg = !$this->altbg;
				$this->name = $aItem['folder'];
				
				$this->url = bab_toHtml($GLOBALS['babUrlScript'] . '?tg=fileman&idx=list&id=' . $iIdRootFolder . '&gr=Y&path=' . $sUrlEncodedPath);
				
				$this->ma = $aItem['ma'];
				return true;
			}
			return false;
		}

		function getNextCuttedFolder()
		{
			$aItem = each($this->aCuttedDir);
			if(false !== $aItem)
			{
//				bab_debug($aItem);
				
				$aItem 						= $aItem['value'];
				$this->name					= $aItem['folder'];
				$iIdFolder					= $aItem['id'];
				$iIdRootFolder				= $aItem['iIdUrl'];
				$sEncodedName				= urlencode($this->name);
				$sEncodedPath				= urlencode($aItem['sUrlPath']);
				$this->bRightUrl			= (1 === $aItem['ma'] && 'Y' === $this->gr && 0 !== (int) $aItem['id']);
				$this->bFolderFormUrl		= (1 === $aItem['ma']);
				$this->bCutFolderUrl		= false;
				$this->bCollectiveFolder	= ((int) $aItem['id'] === (int) $aItem['iIdOwner']);
				$this->ma 					= (1 === $aItem['ma']);
				
				$this->url = bab_toHtml($GLOBALS['babUrlScript'] . '?tg=fileman&idx=list&id=' . $iIdRootFolder . '&gr=' . $this->gr . 
					'&path=' . $sEncodedPath);
				
				$this->sRightUrl = bab_toHtml($GLOBALS['babUrlScript'] . '?tg=fileman&idx=displayRightForm&id=' . $iIdRootFolder . 
					'&gr=' . $this->gr . '&path=' . urlencode($this->path) . '&iIdFolder=' . $iIdFolder);
					
				$this->sFolderFormUrl = bab_toHtml($GLOBALS['babUrlScript'] . '?tg=fileman&idx=displayFolderForm&sAction=editFolder&id=' . $iIdRootFolder . 
					'&gr=' . $this->gr . '&path=' . urlencode($this->path) . '&sDirName=' . $sEncodedName . '&iIdFolder=' . $iIdFolder);
				
				$this->pasteurl = bab_toHtml($GLOBALS['babUrlScript'] . '?tg=fileman&idx=pasteFolder&id=' . $iIdRootFolder . 
					'&gr=' . $this->gr . '&path=' . urlencode($this->path) . '&sSrcPath=' . $sEncodedPath);
				
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
			
			$sFullPathName = BAB_FmFolderHelper::getUploadPath() . $this->oFileManagerEnv->sRelativePath . $arr['name'];
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
			static $i = 0;
			if( $i < $this->count)
				{
				$this->altbg = !$this->altbg;
				$arr = $babDB->db_fetch_array($this->res);
				$this->bconfirmed = 0;
				$this->updateFileInfo($arr);
				$this->description = bab_toHTML($arr['description']);
				$ufile = urlencode($arr['name']);
				$upath = urlencode($this->path);
				$this->url = bab_toHtml($GLOBALS['babUrlScript']."?tg=fileman&idx=upd&id=".$this->id."&gr=".$this->gr."&path=".$upath."&file=".$ufile);
				$this->viewurl = bab_toHtml( $GLOBALS['babUrlScript']."?tg=fileman&idx=viewfile&idf=".$arr['id']."&id=".$this->id."&gr=".$this->gr."&path=".$upath."&file=".$ufile);
				$this->urlget = bab_toHtml($GLOBALS['babUrlScript']."?tg=fileman&idx=get&id=".$this->id."&gr=".$this->gr."&path=".$upath."&file=".$ufile);
				$this->cuturl = bab_toHtml($GLOBALS['babUrlScript']."?tg=fileman&idx=cut&id=".$this->id."&gr=".$this->gr."&path=".$upath."&file=".$ufile);
				$this->delurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=fileman&idx=del&id=".$this->id."&gr=".$this->gr."&path=".$upath."&file=".$ufile);
				if( $this->bVersion )
					{
					$this->lastversion = bab_toHtml($arr['ver_major'].".".$arr['ver_minor']);
					$this->ovfhisturl = bab_toHtml($GLOBALS['babUrlScript']."?tg=filever&idx=hist&id=".$this->id."&gr=".$this->gr."&path=".$upath."&idf=".$arr['id']);
					$this->ovfversurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=filever&idx=lvers&id=".$this->id."&gr=".$this->gr."&path=".$upath."&idf=".$arr['id']);
				
					$this->bfvwait = false;
					$this->blockauth = false;
					if( $arr['edit'] )
						{
						$this->block = true;
						list($lockauthor, $idfvai) = $babDB->db_fetch_array($babDB->db_query("select author, idfai from ".BAB_FM_FILESVER_TBL." where id='".$babDB->db_escape_string($arr['edit'])."'"));
						if( $idfvai == 0 && $lockauthor == $GLOBALS['BAB_SESS_USERID'])
							$this->blockauth = true;

						if( $idfvai != 0 && $this->buaf )
							{
							$this->bfvwait = true;
							$this->bupdate = true;
							}
						$this->ovfurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=filever&idx=unlock&id=".$this->id."&gr=".$this->gr."&path=".$upath."&idf=".$arr['id']);
						if( $this->bfvwait )
							$this->ovfcommiturl = bab_toHtml($GLOBALS['babUrlScript']."?tg=filever&idx=conf&id=".$this->id."&gr=".$this->gr."&path=".$upath."&idf=".$arr['id']);
						else
							$this->ovfcommiturl = bab_toHtml($GLOBALS['babUrlScript']."?tg=filever&idx=commit&id=".$this->id."&gr=".$this->gr."&path=".$upath."&idf=".$arr['id']);
						}
					else
						{
						$this->block = false;
						$this->ovfurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=filever&idx=lock&id=".$this->id."&gr=".$this->gr."&path=".$upath."&idf=".$arr['id']);
						}
					}
				$i++;
				return true;
				}
			else
				return false;
			}

		function getnextwfile()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->countwf)
				{
				$this->altbg = !$this->altbg;
				$arr = $babDB->db_fetch_array($this->reswf);
				$this->bconfirmed = 1;
				$this->updateFileInfo($arr);
				$this->description = bab_toHTML($arr['description']);
				$ufile = urlencode($arr['name']);
				$upath = urlencode($this->path);
				$this->url = bab_toHtml($GLOBALS['babUrlScript']."?tg=fileman&idx=upd&id=".$this->id."&gr=".$this->gr."&path=".$upath."&file=".$ufile);
				$this->viewurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=fileman&idx=viewfile&idf=".$arr['id']."&id=".$this->id."&gr=".$this->gr."&path=".$upath."&file=".$ufile);
				$this->urlget = bab_toHtml($GLOBALS['babUrlScript']."?tg=fileman&idx=get&id=".$this->id."&gr=".$this->gr."&path=".$upath."&file=".$ufile);
				$this->cuturl = bab_toHtml($GLOBALS['babUrlScript']."?tg=fileman&idx=cut&id=".$this->id."&gr=".$this->gr."&path=".$upath."&file=".$ufile);				
				$this->delurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=fileman&idx=del&id=".$this->id."&gr=".$this->gr."&path=".$upath."&file=".$ufile);				
				$i++;
				return true;
				}
			else
				return false;
			}


		function getnextxfile()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->xcount)
				{
				$this->altbg = !$this->altbg;
				$arr = $babDB->db_fetch_array($this->xres);
				$this->bconfirmed = 0;
				$this->updateFileInfo($arr);
				$this->description = bab_toHTML($arr['description']);
				$ufile = urlencode($arr['name']);
				
				$upath = urlencode((string) substr($arr['path'], strlen($this->oFileManagerEnv->sRootFolderPath), -1));
				$this->url = bab_toHtml($GLOBALS['babUrlScript']."?tg=fileman&idx=upd&id=".$this->id."&gr=".$this->gr."&path=".$upath."&file=".$ufile);
				$this->urlget = bab_toHtml($GLOBALS['babUrlScript']."?tg=fileman&idx=get&id=".$this->id."&gr=".$this->gr."&path=".$upath."&file=".$ufile);
				$this->pasteurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=fileman&idx=paste&id=".$this->id."&gr=".$this->gr."&path=".$upath."&file=".$ufile."&tp=".$this->path);				
				$i++;
				return true;
				}
			else
				return false;
			}

		}

	$oFileManagerEnv =& getEnvObject();
	if( $oFileManagerEnv->iIdObject != 0 )
		{
		$pathx = bab_getUploadFullPath($oFileManagerEnv->sGr, $oFileManagerEnv->iIdObject);
		if( substr($pathx, -1) == "/" )
			$pathx = substr($pathx, 0, -1);
		if(!is_dir($pathx) && !bab_mkdir($pathx, $GLOBALS['babMkdirMode'])) {
			$babBody->msgerror = bab_translate("Can't create directory: ").$pathx;
			}
	}


	$temp = new temp();
	$babBody->title = bab_translate("File manager");
	$babBody->addItemMenu("list", bab_translate("Folders"), $GLOBALS['babUrlScript']."?tg=fileman&idx=list&id=".$oFileManagerEnv->iId."&gr=".$oFileManagerEnv->sGr."&path=".urlencode($oFileManagerEnv->sPath));
	
//	if('Y' === $oFileManagerEnv->sGr || ('N' === $oFileManagerEnv->sGr && 0 !== $oFileManagerEnv->iPathLength))
	{
		if($oFileManagerEnv->oAclFm->haveUploadRight()) 
		{
			$babBody->addItemMenu("add", bab_translate("Upload"), $GLOBALS['babUrlScript']."?tg=fileman&idx=add&id=".$oFileManagerEnv->iId."&gr=".$oFileManagerEnv->sGr."&path=".urlencode($oFileManagerEnv->sPath));
		}
		if($oFileManagerEnv->oAclFm->haveManagerRight()) 
		{
			$babBody->addItemMenu("trash", bab_translate("Trash"), $GLOBALS['babUrlScript']."?tg=fileman&idx=trash&id=".$oFileManagerEnv->iId."&gr=".$oFileManagerEnv->sGr."&path=".urlencode($oFileManagerEnv->sPath));
		}
	}

	if(0 !== $oFileManagerEnv->iId && $oFileManagerEnv->sGr == "Y")
	{
		$GLOBALS['babWebStat']->addFolder($oFileManagerEnv->iId);
	}
	
	$babBody->babecho(bab_printTemplate($temp,"fileman.html", "fileslist"));
	return $temp->count;
}


function addFile($id, $gr, $path, $description, $keywords)
{
	global $babBody, $BAB_SESS_USERID;

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

		function temp($id, $gr, $path, $description, $keywords)
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
			$this->id = $id;
			$this->path = bab_toHtml($path);
			$this->gr = $gr;
			$this->maxfilesize = $GLOBALS['babMaxFileSize'];
			$this->descval = isset($description[0]) ? bab_toHtml($description[0]) : "";
			$this->keysval = isset($keywords[0]) ? bab_toHtml($keywords[0]) : "";
			if($gr == 'Y')
			{
				$this->res = $babDB->db_query("select * from ".BAB_FM_FIELDS_TBL." where id_folder='".$babDB->db_escape_string($id)."'");
				$this->count = $babDB->db_num_rows($this->res);
			}
			else
			{
				$this->count = 0;
			}
			$babBody->addJavascriptFile($GLOBALS['babScriptPath']."prototype/prototype.js");
			$babBody->addJavascriptFile($GLOBALS['babScriptPath']."scriptaculous/scriptaculous.js");
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

	$oFileManagerEnv =& getEnvObject();
	if(!$oFileManagerEnv->oAclFm->haveUploadRight())
	{
		$babBody->msgerror = bab_translate("Access denied");
		return;
	}

	$temp = new temp($id, $gr, $path, $description, $keywords);
	$babBody->babecho(	bab_printTemplate($temp,"fileman.html", "addfile"));
}


function getFile($file, $id, $gr, $path)
{
	global $babBody, $babDB, $BAB_SESS_USERID;
	
	$inl = bab_rp('inl', false);
	if(false === $inl) 
	{
		$inl = bab_getFileContentDisposition() == 1 ? 1 : '';
	}

	$oFileManagerEnv =& getEnvObject();
	
	if($oFileManagerEnv->oAclFm->haveDownloadRight())
	{
		$sName = stripslashes($file);
		
		$oFolderFileSet = new BAB_FolderFileSet();
				
		$oGroup		= $oFolderFileSet->aField['sGroup'];
		$oIdOwner	= $oFolderFileSet->aField['iIdOwner'];
		$oPathName	= $oFolderFileSet->aField['sPathName'];
		$oName		= $oFolderFileSet->aField['sName'];
		
		$oCriteria = $oGroup->in($oFileManagerEnv->sGr);
		$oCriteria = $oCriteria->_and($oPathName->in($oFileManagerEnv->sRelativePath));
		$oCriteria = $oCriteria->_and($oName->in($sName));
		$oCriteria = $oCriteria->_and($oIdOwner->in($oFileManagerEnv->iIdObject));
		
		$oFolderFile = $oFolderFileSet->get($oCriteria);
		if(!is_null($oFolderFile))
		{
			$oFolderFile->setHits($oFolderFile->getHits() + 1);
			$oFolderFile->save();

			$GLOBALS['babWebStat']->addFilesManagerFile($oFolderFile->getId());
			
			$sUploadPath = BAB_FmFolderHelper::getUploadPath();
			$sFullPathName = $sUploadPath . $oFolderFile->getPathName() . $oFolderFile->getName();
			$mime = bab_getFileMimeType($sFullPathName);
			
			if(file_exists($sFullPathName))
			{
				$fsize = filesize($sFullPathName);
				if(strtolower(bab_browserAgent()) == "msie")
				{
					header('Cache-Control: public');
				}
				
				if($inl == "1")
				{
					header("Content-Disposition: inline; filename=\"$file\""."\n");
				}
				else
				{
					header("Content-Disposition: attachment; filename=\"$file\""."\n");
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
					exit;
				}
			}
			else
			{
				$babBody->msgerror = bab_translate("The file is not on the server");
			}
		}
	}
	else 
	{
		echo bab_translate("Access denied");
		return;
	}
}


function cutFile($file, $id, $gr, $path, $bmanager)
{
	global $babBody, $babDB;
	if(!$bmanager)
	{
		$babBody->msgerror = bab_translate("Access denied");
		return false;
	}
	
	$iIdOwner = $id;
	$sRelativePath = '';
	
	if('N' === $gr)
	{
		$oFileManagerEnv =& getEnvObject();
		$sRelativePath = $oFileManagerEnv->sRelativePath;
	}
	else 
	{
		$oFmFolder = null;
		BAB_FmFolderHelper::getFileInfoForCollectiveDir($id, $path, $iIdOwner, $sRelativePath, $oFmFolder);
	}
	
	$oFolderFileSet = new BAB_FolderFileSet();
	
	$oIdOwner =& $oFolderFileSet->aField['iIdOwner'];
	$oGroup =& $oFolderFileSet->aField['sGroup'];
	$oState =& $oFolderFileSet->aField['sState'];
	$oPathName =& $oFolderFileSet->aField['sPathName'];
	$oName =& $oFolderFileSet->aField['sName'];
	
	$oCriteria = $oIdOwner->in($iIdOwner);
	$oCriteria = $oCriteria->_and($oGroup->in($gr));
	$oCriteria = $oCriteria->_and($oState->in(''));
	$oCriteria = $oCriteria->_and($oPathName->in($sRelativePath));
	$oCriteria = $oCriteria->_and($oName->in($file));
	
	$oFolderFile = $oFolderFileSet->get($oCriteria);
	if(!is_null($oFolderFile))
	{
//		bab_debug($oFolderFile);
		$oFolderFile->setState('X');
		$oFolderFile->save();
		return true;
	}
	return false;
}

function delFile($file, $id, $gr, $path, $bmanager)
{
	global $babBody, $babDB;

	if(!$bmanager)
	{
		$babBody->msgerror = bab_translate("Access denied");
		return false;
	}

	$iIdOwner = $id;
	$sRelativePath = '';
	
	if('N' === $gr)
	{
//		$sRelativePath = BAB_FmFolderHelper::getUserDirUploadPath($id) . $path . '/';
		$oFileManagerEnv =& getEnvObject();
		$sRelativePath = $oFileManagerEnv->sRelativePath;
	}
	else 
	{
		$oFmFolder = null;
		BAB_FmFolderHelper::getFileInfoForCollectiveDir($id, $path, $iIdOwner, $sRelativePath, $oFmFolder);
	}

	
	$oFolderFileSet = new BAB_FolderFileSet();
	
	$oIdOwner =& $oFolderFileSet->aField['iIdOwner'];
	$oGroup =& $oFolderFileSet->aField['sGroup'];
	$oState =& $oFolderFileSet->aField['sState'];
	$oPathName =& $oFolderFileSet->aField['sPathName'];
	$oName =& $oFolderFileSet->aField['sName'];
	
	$oCriteria = $oIdOwner->in($iIdOwner);
	$oCriteria = $oCriteria->_and($oGroup->in($gr));
	$oCriteria = $oCriteria->_and($oState->in(''));
	$oCriteria = $oCriteria->_and($oPathName->in($sRelativePath));
	$oCriteria = $oCriteria->_and($oName->in($file));
	
	$oFolderFile = $oFolderFileSet->get($oCriteria);
	if(!is_null($oFolderFile))
	{
//		bab_debug($oFolderFile);
		$oFolderFile->setState('D');
		$oFolderFile->save();
		return true;
	}
	return false;
}

function pasteFile($file, $id, $gr, $path, $tp, $bmanager)
{
	global $babBody, $babDB;

	if(!$bmanager)
	{
		$babBody->msgerror = bab_translate("Access denied");
		return false;
	}
	
	$iOldIdOwner		= $id;
	$iNewIdOwner		= $id;
	$sOldRelativePath	= '';
	$sNewRelativePath	= '';
	
	$sUploadPath = BAB_FmFolderHelper::getUploadPath();
	
	$file = stripslashes($file);
	$tp = stripslashes($tp);
	
	if('N' === $gr)
	{
		$oFileManagerEnv =& getEnvObject();

		$sOldEndPath = (strlen(trim($path)) > 0) ? '/' : '';
		$sNewEndPath = (strlen(trim($tp)) > 0) ? '/' : '';
		
		$sOldRelativePath = $oFileManagerEnv->sRootFolderPath . $path . $sOldEndPath;
		$sNewRelativePath = $oFileManagerEnv->sRootFolderPath . $tp . $sNewEndPath;
	}
	else 
	{
		$oFmFolder = null;
		BAB_FmFolderHelper::getFileInfoForCollectiveDir($id, $path, $iOldIdOwner, $sOldRelativePath, $oFmFolder);
		BAB_FmFolderHelper::getFileInfoForCollectiveDir($id, $tp, $iNewIdOwner, $sNewRelativePath, $oFmFolder);
	}

//bab_debug('sFile ==> ' . $file . ' iOldIdOwner ==> ' . $iOldIdOwner . 
//	' sOldRelativePath ==> ' . $sOldRelativePath . ' iNewIdOwner ==> ' . $iNewIdOwner .
//	' sNewRelativePath ==> ' . $sNewRelativePath);	
	
	$oFolderFileSet = new BAB_FolderFileSet();
	$oIdOwner =& $oFolderFileSet->aField['iIdOwner'];
	$oGroup =& $oFolderFileSet->aField['sGroup'];
	$oPathName =& $oFolderFileSet->aField['sPathName'];
	$oName =& $oFolderFileSet->aField['sName'];

	if(file_exists($sUploadPath . $sNewRelativePath . $file))
	{
//		bab_debug('sFpn ==> ' . $sUploadPath . $sNewRelativePath . $file . 'sSrc ==> ' . $path . ' sTrg ==> ' . $tp);
		
		if($path == $tp)
		{
			$oCriteria = $oIdOwner->in($iOldIdOwner);
			$oCriteria = $oCriteria->_and($oGroup->in($gr));
			$oCriteria = $oCriteria->_and($oPathName->in($sOldRelativePath));
			$oCriteria = $oCriteria->_and($oName->in($file));
			
			$oFolderFile = $oFolderFileSet->get($oCriteria);
			if(!is_null($oFolderFile))
			{
//				bab_debug($oFolderFile);
				$oFolderFile->setState('');
				$oFolderFile->save();
				return true;
			}
		}
		$babBody->msgerror = bab_translate("A file with the same name already exists");
		return false;
	}

//bab_debug('sOldFile ==> ' . $sUploadPath . $sOldRelativePath . $file . 
//	' sNewFile ==> ' . $sUploadPath . $sNewRelativePath . $file);	
	
	if(rename($sUploadPath . $sOldRelativePath . $file, $sUploadPath . $sNewRelativePath . $file))
	{
		$oCriteria = $oIdOwner->in($iOldIdOwner);
		$oCriteria = $oCriteria->_and($oGroup->in($gr));
		$oCriteria = $oCriteria->_and($oPathName->in($sOldRelativePath));
		$oCriteria = $oCriteria->_and($oName->in($file));
		
		$oFolderFile = $oFolderFileSet->get($oCriteria);
		if(!is_null($oFolderFile))
		{
			$oFolderFile->setState('');
			$oFolderFile->setOwnerId($iNewIdOwner);
			$oFolderFile->setPathName($sNewRelativePath);
			$oFolderFile->save();
			
			if(is_dir($sUploadPath . $sOldRelativePath . BAB_FVERSION_FOLDER . '/'))
			{
				if(!is_dir($sUploadPath . $sNewRelativePath . BAB_FVERSION_FOLDER . '/'))
				{
					bab_mkdir($sUploadPath . $sNewRelativePath . BAB_FVERSION_FOLDER, $GLOBALS['babMkdirMode']);
				}
			}
			
			$oFolderFileVersionSet = new BAB_FolderFileVersionSet();
			$oIdFile =& $oFolderFileVersionSet->aField['iIdFile'];
			
			$oFolderFileVersionSet->select($oIdFile->in($oFolderFile->getId()));
			while(null !== ($oFolderFileVersion = $oFolderFileVersionSet->next()))
			{
				$sFileName = $oFolderFileVersion->getMajorVer() . '.' . $oFolderFileVersion->getMinorVer() . ',' . $file;
				$sSrc = $sUploadPath . $sOldRelativePath . BAB_FVERSION_FOLDER . '/' . $sFileName;
				$sTrg = $sUploadPath . $sNewRelativePath . BAB_FVERSION_FOLDER . '/' . $sFileName;
				rename($sSrc, $sTrg);
			}
		}
		return true;
	}
	else
	{
		$babBody->msgerror = bab_translate("Cannot paste file");
		return false;
	}
}

function viewFile($idf, $id, $path)
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

		function temp($oFmFolder, $oFolderFile, $id, $path, $bmanager, $access, $bconfirm, $bupdate, $bdownload, $bversion)
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
				$this->keywords = bab_translate("Keywords (separated by spaces)");
				$this->notify = bab_translate("Notify members group");
				$this->t_yes = bab_translate("Yes");
				$this->t_no = bab_translate("No");
				$this->t_change_all = bab_translate("Change status for all versions");
				$this->tabIndexStatus = array(BAB_INDEX_STATUS_NOINDEX, BAB_INDEX_STATUS_INDEXED, BAB_INDEX_STATUS_TOINDEX);

				$this->id = $oFolderFile->getOwnerId();
				$this->gr = $oFolderFile->getGroup();
				$this->path = bab_toHtml($path);
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
				
				$fullpath = BAB_FmFolderHelper::getUploadPath() . $oFolderFile->getPathName() . $oFolderFile->getName();
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

				$this->geturl = bab_toHtml($GLOBALS['babUrlScript']."?tg=fileman&idx=get&id=".$id."&gr=".$oFolderFile->getGroup()."&path=".urlencode($path)."&file=".urlencode($oFolderFile->getName()));
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
	
						$this->arrfolders = array();
						$this->movetofolder = bab_translate("Move to folder");
						
						$this->oFmFolderSet = new BAB_FmFolderSet();
						$oId =& $this->oFmFolderSet->aField['iId'];
				
						$this->oFmFolderSet->select($oId->notIn($oFolderFile->getOwnerId()));
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
										
										$files_to_index[] = $sFullPathNane = BAB_FmFolderHelper::getUploadPath() . $oFolderFile->getPathName() . BAB_FVERSION_FOLDER.'/'.$arrfv['ver_major'].','.$arrfv['ver_minor'].','.$oFolderFile->getName();
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
				}
			else
			{
				$GLOBALS['babBody']->title = bab_translate("Access denied");
			}
			$babBody->addJavascriptFile($GLOBALS['babScriptPath']."prototype/prototype.js");
			$babBody->addJavascriptFile($GLOBALS['babScriptPath']."scriptaculous/scriptaculous.js");
			$babBody->addStyleSheet('ajax.css');
		}

		function getnextfm()
		{
			if(!is_null($this->oFmFolderSet) && $this->oFmFolderSet->count() > 0)
			{
				$oFmFolder = $this->oFmFolderSet->next();
				if(!is_null($oFmFolder))
				{
					$this->folder = bab_toHtml($oFmFolder->getName());
					$this->folderid = $oFmFolder->getId();
					return true;
				}
			}
			return false;
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
	

	$oFolderFileSet = new BAB_FolderFileSet();
	$oId =& $oFolderFileSet->aField['iId'];
	$oState =& $oFolderFileSet->aField['sState'];

	$oCriteria = $oId->in($idf);
	$oCriteria = $oCriteria->_and($oState->in(''));
	
	$oFolderFile = $oFolderFileSet->get($oCriteria);

	if(!is_null($oFolderFile))
	{
		$oFileManagerEnv =& getEnvObject();
		
		if('N' === $oFolderFile->getGroup())
		{
			if($oFileManagerEnv->oAclFm->userHaveStorage() && $BAB_SESS_USERID == $oFolderFile->getOwnerId())
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
			
			$access = (!is_null($oFileManagerEnv->oFmFolder));
			$bdownload = $oFileManagerEnv->oAclFm->haveDownloadRight();
			$bmanager = $oFileManagerEnv->oAclFm->haveManagerRight();
			$bupdate = $oFileManagerEnv->oAclFm->haveUpdateRight();
			
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
	
	$temp = new temp($oFileManagerEnv->oFmFolder, $oFolderFile, $id, $path, $bmanager, $access, $bconfirm, $bupdate, $bdownload,$bversion);
	$babBody->babpopup(bab_printTemplate($temp,"fileman.html", "viewfile"));
}

function displayRightForm($bmanager, $upload, $path, $id, $gr)
{
	global $babBody;
	
	$iIdFolder = (int) bab_gp('iIdFolder', 0);
	
	$sFolderName = '';
	$oFmFolder = BAB_FmFolderHelper::getFmFolderById($iIdFolder);
	if(!is_null($oFmFolder))
	{
		$sFolderName = $oFmFolder->getName();
	}

	require_once $GLOBALS['babInstallPath'] . 'admin/acl.php';
	
	$babBody->addItemMenu("list", bab_translate("Folders"), $GLOBALS['babUrlScript']."?tg=fileman&idx=list&id=".$id."&gr=".$gr."&path=".urlencode($path));
	if($upload) 
	{
		$babBody->addItemMenu("add", bab_translate("Upload"), $GLOBALS['babUrlScript']."?tg=fileman&idx=add&id=".$id."&gr=".$gr."&path=".urlencode($path));
	}
	if($bmanager) 
	{
		$babBody->addItemMenu("trash", bab_translate("Trash"), $GLOBALS['babUrlScript']."?tg=fileman&idx=trash&id=".$id."&gr=".$gr."&path=".urlencode($path));
	}
	$babBody->addItemMenu("displayRightForm", bab_translate("Rights"), $GLOBALS['babUrlScript']."?tg=fileman&idx=displayRightForm&id=".$id."&gr=".$gr."&path=".urlencode($path) . 
		'&iIdFolder=' . $iIdFolder);
	
	$babBody->title = bab_translate("Rights of directory") . ' ' . $sFolderName;
	$macl = new macl("fileman", "setRight", $iIdFolder, "aclview");
	
	$macl->set_hidden_field('path', $path);
	$macl->set_hidden_field('id', $id);
	$macl->set_hidden_field('gr', $gr);
	$macl->set_hidden_field('iIdFolder', $iIdFolder);
	
	$macl->addtable( BAB_FMUPLOAD_GROUPS_TBL,bab_translate("Upload"));
	$macl->addtable( BAB_FMDOWNLOAD_GROUPS_TBL,bab_translate("Download"));
	$macl->addtable( BAB_FMUPDATE_GROUPS_TBL,bab_translate("Update"));
	$macl->addtable( BAB_FMMANAGERS_GROUPS_TBL,bab_translate("Manage"));
	$macl->filter(0,0,1,1,1);
	$macl->addtable( BAB_FMNOTIFY_GROUPS_TBL,bab_translate("Who is notified when a new file is uploaded or updated?"));
	$macl->babecho();
	
}


function setRight($bmanager, $upload, $id, $gr, $path, &$idx)
{
	require_once $GLOBALS['babInstallPath'] . 'admin/acl.php';
	maclGroups();
	
	$idx = 'list';
	listFiles($id, $gr, $path, $bmanager, $upload);
}

function fileUnload($id, $gr, $path)
	{
	class temp
		{
		var $message;
		var $close;
		var $redirecturl;

		function temp($id, $gr, $path)
			{
			$this->message = bab_translate("Your file list has been updated");
			$this->close = bab_translate("Close");
			$url = $GLOBALS['babUrlScript']."?tg=fileman&idx=list&id=".$id."&gr=".$gr."&path=".urlencode($path);
			$this->redirecturl = bab_toHtml($url, BAB_HTML_JS | BAB_HTML_ENTITIES);
			}
		}

	$temp = new temp($id, $gr, $path);
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
	$sUploadPath = BAB_FmFolderHelper::getUploadPath();
	
	$oFolderFileSet = new BAB_FolderFileSet();
	$oId =& $oFolderFileSet->aField['iId'];
	
	global $babDB;	
	for($i = 0; $i < count($items); $i++)
	{
		$oFolderFile = $oFolderFileSet->get($oId->in($items[$i]));
		if(!is_null($oFolderFile))
		{
			if(!is_dir($sUploadPath . $oFolderFile->getPathName()))
			{
				$rr = explode("/", $oFolderFile->getPathName());
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
	
	$babBody->addItemMenu("list", bab_translate("Folders"), $GLOBALS['babUrlScript'] . '?tg=fileman&idx=list&id=' . 
		$oFileManagerEnv->iId . '&gr=' . $oFileManagerEnv->sGr . '&path=' . urlencode($oFileManagerEnv->sPath));
	$babBody->addItemMenu('displayFolderForm', bab_translate("Create a folder"), $GLOBALS['babUrlScript'] . 
		'?tg=fileman&idx=displayFolderForm&id=' . $oFileManagerEnv->iId . '&gr=' . $oFileManagerEnv->sGr . 
		'&path=' . urlencode($oFileManagerEnv->sPath));
	
	if($oFileManagerEnv->oAclFm->haveManagerRight())
	{
		if('Y' ===$oFileManagerEnv->sGr)
		{
			$oDspFldForm = new DisplayCollectiveFolderForm();
			$babBody->babecho($oDspFldForm->printTemplate());
		}
		else if('N' === $oFileManagerEnv->sGr)
		{
			$oDspFldForm = new DisplayUserFolderForm();
			$babBody->babecho($oDspFldForm->printTemplate());
		}
	}
	else 
	{
		$babBody->msgerror = bab_translate("Access denied");
	}
}


function processFolderCommand(&$sIdx)
{
	global $babBody;

	$oFileManagerEnv =& getEnvObject();
	
	$iId = $oFileManagerEnv->iId; 
	$sGr = $oFileManagerEnv->sGr; 
	$sPath = $oFileManagerEnv->sPath;
	
	if('Y' === $sGr)
	{
		createEditFolderForCollectiveDir($iId, $sPath);
		$sIdx = 'list';
		listFiles($iId, $sGr, $sPath);
	}
	else if('N' === $sGr)
	{
		createEditFolderForUserDir($iId, $sPath);
		$sIdx = 'list';
		listFiles($iId, $sGr, $sPath);
	}
	else 
	{
		$babBody->msgerror = bab_translate("Access denied");
	}
}


function createEditFolderForUserDir($iIdUser, $sPath)
{
	global $babBody;
	
	$oFileManagerEnv =& getEnvObject();
	if($oFileManagerEnv->oAclFm->userHaveStorage())
	{
		$sDirName = (string) bab_pp('sDirName', '');
		
		if(strlen(trim($sDirName)) > 0)
		{
			$sUplaodPath = BAB_FmFolderHelper::getUploadPath();
			$sUserDirPath = BAB_FmFolderHelper::getUserDirUploadPath($iIdUser);
			
			if(isset($_POST['sCreateEditFolder']))
			{
				$sAction		= (string) bab_pp('sAction', '');
				$sType			= (string) bab_pp('sType', '');
				$sOldDirName	= (string) bab_pp('sOldDirName', '');
				
				if('createFolder' === $sAction)
				{
					$sFullPathName = $sUplaodPath . $sUserDirPath;
					if(strlen(trim($sPath)) === 0)
					{
						$sFullPathName .= $sDirName;
					}
					else 
					{
						$sFullPathName .= $sPath . '/' . $sDirName;
					}
					
					BAB_FmFolderHelper::createDirectory($sUplaodPath, $sFullPathName);
				}
				else if('editFolder' === $sAction)
				{
					$sPathName = '';
					$sRelativePath = $sUserDirPath;
					if(strlen(trim($sPath)) > 0)
					{
						$sRelativePath .= $sPath;
						$sPathName = $sRelativePath . '/' . $sOldDirName;
					}
					else 
					{
						$sPathName = $sRelativePath . $sOldDirName;
					}

//					bab_debug('sUplaodPath ==> ' . $sUplaodPath);
//					bab_debug('sPath ==> ' . $sPath);
//					bab_debug('sRelativePath ==> ' . $sRelativePath);
//					bab_debug('sOldDirName ==> ' . $sOldDirName);
//					bab_debug('sDirName ==> ' . $sDirName);
	
					if(BAB_FmFolderHelper::renameDirectory($sUplaodPath, $sRelativePath, $sOldDirName, $sDirName))
					{
						BAB_RegitryHelper::update($sRelativePath . '/', $sOldDirName, $sDirName);
						
		//				bab_debug('sPathName ==> ' . $sPathName . ' sDirName ==> ' . $sDirName);
						$bCollective = false;
						BAB_FolderFileSet::setPathName($sPathName, $sDirName, $bCollective);
					}
				}
				else 
				{
					$babBody->addError(bab_translate("Unhandled action"));
				}
			}
			else if(isset($_POST['sDeleteFolder']))
			{
				global $babDB;
//				bab_debug('delete folder ==> ' . $sUplaodPath . $sUserDirPath . $sDirName);
			
				$sPathName = $sUserDirPath . $sDirName . '/';
				$sFullPathName = $sUplaodPath . $sPathName;
				
				$oFolderFileSet = new BAB_FolderFileSet();
				$oPathName =& $oFolderFileSet->aField['sPathName'];
				$oFolderFileSet->remove($oPathName->like($babDB->db_escape_like($sPathName) . '%'));
				
				$oFmFolderSet = new BAB_FmFolderSet();
				$oFmFolderSet->removeDir($sFullPathName);
			}
		}
		else 
		{
			$babBody->addError(bab_translate("Please give a valid directory name"));
		}
	}
	else 
	{
		if('createFolder' === $sAction)
		{
			$babBody->addError(bab_translate("You don't have permission to create directory"));
		}
		else 
		{
			$babBody->addError(bab_translate("You don't have permission to rename directory"));
		}
	}
}

function createEditFolderForCollectiveDir($iIdFolder, $sPath)
{
	global $babBody;
	
	$sAction = (string) bab_pp('sAction', '');
	$oFileManagerEnv =& getEnvObject();
	if($oFileManagerEnv->oAclFm->haveManagerRight())
	{
		$sDirName = (string) bab_pp('sDirName', '');
		
		if(strlen(trim($sDirName)) > 0)
		{
			if(isset($_POST['sCreateEditFolder']))
			{
				$sType					= (string) bab_pp('sType', 'collective');
				$sActive				= (string) bab_pp('sActive', 'Y');
				$iIdApprobationScheme	= (int) bab_pp('iIdApprobationScheme', 0);
				$sAutoApprobation		= (string) bab_pp('sAutoApprobation', 'N');
				$sNotification			= (string) bab_pp('sNotification', 'N');
				$sVersioning			= (string) bab_pp('sVersioning', 'N');
				$sDisplay				= (string) bab_pp('sDisplay', 'N');
				$sPathName				= (string) '';
				
				$sRelativePath = '';
				$oFmFolder = BAB_FmFolderHelper::getFmFolderById($iIdFolder);
//				$oFmFolder = $oFileManagerEnv->oFmFolder;
				if(!is_null($oFmFolder))
				{
					$sRelativePath = $oFmFolder->getName() . '/';
					if(strlen(trim($sPath)) > 0)
					{
						$sRelativePath =  $sRelativePath . $sPath . '/';
					}
					
					$sUploadPath = BAB_FmFolderHelper::getUploadPath();
					
					if('createFolder' === $sAction)
					{
						$sFullPathName = $sUploadPath . $sRelativePath . $sDirName;
	
//						bab_debug('sUploadPath ==> ' . $sUploadPath);
//						bab_debug('sFullPathName ==> ' .  $sFullPathName);
//						bab_debug('sRelativePath ==> ' . $sRelativePath);
//return;
						if(BAB_FmFolderHelper::createDirectory($sUploadPath, $sFullPathName))
						{
							if('collective' === $sType)
							{
								$oFmFolder = new BAB_FmFolder();
								$oFmFolder->setActive($sActive);
								$oFmFolder->setApprobationSchemeId($iIdApprobationScheme);
								$oFmFolder->setAutoApprobation($sAutoApprobation);
								$oFmFolder->setDelegationOwnerId((int) $babBody->currentAdmGroup);
								$oFmFolder->setFileNotify($sNotification);
								$oFmFolder->setHide($sDisplay);
								$oFmFolder->setName($sDirName);
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
					else if('editFolder' === $sAction)
					{
						$iIdFld				= (int) bab_pp('iIdFolder', 0); 
						$sPathName			= '';			
						$bFolderRenamed		= false;
						$bChangeFileIdOwner = false;
						
						$oFmFolder = $oFmFolder = BAB_FmFolderHelper::getFmFolderById($iIdFld);
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
						
	//					bab_debug('sUploadPath ==> ' . $sUploadPath);
	//					bab_debug('sRelativePath ==> ' . $sRelativePath);
	//					bab_debug('sOldDirName ==> ' . $sOldDirName);
	//					bab_debug('sDirName ==> ' . $sDirName);
	
						if($bFolderRenamed)
						{
							if(strlen(trim($sOldDirName)) > 0)
							{
								BAB_FmFolderHelper::updateSubFolderPathName($sUploadPath, $sRelativePath, $sOldDirName, $sDirName);
				
								$bCollective = true;
								$oFolderFileSet = new BAB_FolderFileSet();
								$oFolderFileSet->setPathName($sRelativePath . $sOldDirName . '/', $sDirName, $bCollective);
								
							}
							else 
							{
								bab_debug(__FUNCTION__ . ' ERROR invalid sOldDirName');
							}
						}
						
						if(!is_null($oFmFolder))
						{
							$oFmFolder->setName($sDirName);
							$oFmFolder->setActive($sActive);
							$oFmFolder->setApprobationSchemeId($iIdApprobationScheme);
							$oFmFolder->setAutoApprobation($sAutoApprobation);
							$oFmFolder->setDelegationOwnerId((int) $babBody->currentAdmGroup);
							$oFmFolder->setFileNotify($sNotification);
							$oFmFolder->setHide($sDisplay);
							$oFmFolder->setName($sDirName);
							$oFmFolder->setRelativePath($sRelativePath);
							$oFmFolder->setVersioning($sVersioning);
							$oFmFolder->setAutoApprobation($sAutoApprobation);
							$oFmFolder->save();
	
	//bab_debug('iIdFolder ==> ' . $oFmFolder->getId());
	
							if($bChangeFileIdOwner)
							{
								$oFirstFmFolder = BAB_FmFolderSet::getFirstCollectiveFolder($sRelativePath);
								
	//bab_debug('sRelativePath ==> ' . $sRelativePath . ' iIdOldFolder ==> ' . $oFirstFmFolder->getId());							
	
								$oFolderFileSet = new BAB_FolderFileSet();
								$sPathName = $oFmFolder->getRelativePath() . $oFmFolder->getName() . '/';
								$oFolderFileSet->setOwnerId($sPathName, $oFirstFmFolder->getId(), $oFmFolder->getId());
							}
						}
					}
				}
			}
			else if(isset($_POST['sDeleteFolder']))
			{
				$iIdFld	= (int) bab_pp('iIdFolder', 0); 
				if(0 !== $iIdFld)
				{
					require_once $GLOBALS['babInstallPath'] . 'utilit/delincl.php';
					bab_deleteFolder($iIdFld);
				}
				else 
				{
					global $babDB;
					$sUploadPath = BAB_FmFolderHelper::getUploadPath();
					$sPathName = $oFileManagerEnv->sRelativePath . $sDirName . '/';
					$sFullPathName = $sUploadPath . $sPathName;
					
					$oFolderFileSet = new BAB_FolderFileSet();
					$oPathName =& $oFolderFileSet->aField['sPathName'];
					$oFolderFileSet->remove($oPathName->like($babDB->db_escape_like($sPathName) . '%'));
					
					$oFmFolderSet = new BAB_FmFolderSet();
					$oFmFolderSet->removeDir($sFullPathName);
				}
			}			
		}
	}
	else 
	{
		if('createFolder' === $sAction)
		{
			$babBody->addError(bab_translate("You don't have permission to create directory"));
		}
		else 
		{
			$babBody->addError(bab_translate("You don't have permission to rename directory"));
		}
	}
}

	
function cutFolder($bCollective, $sPath, $iIdRootFolder, &$sIdx)
{
//	bab_debug(__LINE__ . ' ' . basename(__FILE__) . ' ' . __FUNCTION__);
	
	global $babBody;
	$sGr = (($bCollective) ? 'Y' : 'N');
	
	if('Y' === $sGr)
	{
		cutCollectiveDir((int) $iIdRootFolder, $sPath);
		$sIdx = 'list';
		listFiles($iIdRootFolder, $sGr, $sPath);
	}
	else if('N' === $sGr)
	{
		$oFileManagerEnv =& getEnvObject();
		if($oFileManagerEnv->oAclFm->haveManagerRight())		
		{
			cutUserFolder((int) $iIdRootFolder, $sPath);
		}
		$sIdx = 'list';
		listFiles($iIdRootFolder, $sGr, $sPath);
	}
	else 
	{
		$babBody->msgerror = bab_translate("Access denied");
	}
}


function cutCollectiveDir($iIdRootFolder, $sPath)
{
//	bab_debug(__LINE__ . ' ' . basename(__FILE__) . ' ' . __FUNCTION__);
	
	global $babBody;
	
	$sDirName = (string) bab_gp('sDirName', '');
	if(strlen(trim($sDirName)) > 0)
	{
		$iIdOwner		= 0;
		$sRelativePath	= '';
		$bAccessValid	= false;
		$sUploadPath	= BAB_FmFolderHelper::getUploadPath();
		
		$oFmFolderSet = new BAB_FmFolderSet();
		$oId =& $oFmFolderSet->aField['iId'];
		$oFmFolder = $oFmFolderSet->get($oId->in($iIdRootFolder));
		
		if(!is_null($oFmFolder))
		{
			$sRootFolder = $oFmFolder->getName() . '/';
			
			if(is_dir($sUploadPath . $sRootFolder . $sPath))
			{
				$sPathName = $sPath;
				if(strlen(trim($sPath)) > 0)
				{
					$sPathName .= '/' . $sDirName . '/';
				}
				else 
				{
					$sPathName .= $sDirName . '/';
				}
				
				if($sPathName !== $sRootFolder)
				{	
					$oFmFolder = null;
					if(BAB_FmFolderHelper::getFileInfoForCollectiveDir($iIdRootFolder, $sPathName, $iIdOwner, $sRelativePath, $oFmFolder))
					{
//						bab_debug(__LINE__ . ' ' . basename(__FILE__) . ' ' . __FUNCTION__ . 
//							' iIdOwner ==> ' . $iIdOwner . ' sRelativePath ==> ' . $sRelativePath .
//							' sPathName ==> ' .  $sPathName .
//							' sFullPathName ==> ' . $sUploadPath . $sRelativePath);
							
						if(false === bab_isAccessValid(BAB_FMMANAGERS_GROUPS_TBL, $iIdOwner))
						{
							$bAccessValid = bab_isAccessValid(BAB_FMMANAGERS_GROUPS_TBL, $iIdRootFolder);
						}
						else 
						{
							$bAccessValid = true;
						}
						
//						bab_debug(__LINE__ . ' ' . basename(__FILE__) . ' ' . __FUNCTION__ . 
//							' bAccessValid ==> ' . (($bAccessValid) ? 'YES' : 'NO') . 
//							' sDirName ==> ' . $sDirName . ' iIdRootFolder ==> ' . $iIdRootFolder .
//							' iIdOwner ==> ' . $iIdOwner);
							
						if(true === $bAccessValid)
						{
							$sRegKey	= md5($sRootFolder);
							$sDirectory = '/bab/fileManager/cuttedFolder/';
							
							$oRegHlp = new BAB_RegitryHelper($sDirectory, $sRegKey);
							
							$oRegHlp->addItem(array('sRelativePath' => $sRelativePath, 'sName' => $sDirName));
							$oRegHlp->save();
							
//							bab_debug($oRegHlp->getDatas());
						}
					}
				}
				else 
				{
					$babBody->msgerror = bab_translate("The root folder cannot be cutted");
				}
			}
		}
	}
	else 
	{
		$babBody->msgerror = bab_translate("Access denied");
	}
}


function cutUserFolder($iIdRootFolder, $sPath)
{
//	bab_debug(__LINE__ . ' ' . basename(__FILE__) . ' ' . __FUNCTION__);
	
	global $babBody;
	
	$sDirName = (string) bab_gp('sDirName', '');
	if(strlen(trim($sDirName)) > 0)
	{
//		$sUploadPath	= BAB_FmFolderHelper::getUploadPath();

		$oFileManagerEnv =& getEnvObject();

		$sRegKey	= md5($oFileManagerEnv->sRootFolderPath);
		$sDirectory = '/bab/fileManager/cuttedFolder/';
		
		$oRegHlp = new BAB_RegitryHelper($sDirectory, $sRegKey);
		
		$oRegHlp->addItem(array('sRelativePath' => $oFileManagerEnv->sRelativePath . $sDirName . '/', 'sName' => $sDirName));
		$oRegHlp->save();
	}	
}


function pasteFolder($bCollective, $sPath, $iIdRootFolder, &$sIdx)
{
//	bab_debug(__LINE__ . ' ' . basename(__FILE__) . ' ' . __FUNCTION__);

	global $babBody;
	$sGr = (($bCollective) ? 'Y' : 'N');
	
	if('Y' === $sGr)
	{
		pasteCollectiveDir($iIdRootFolder);
		$sIdx = 'list';
		listFiles($iIdRootFolder, $sGr, $sPath);
	}
	else if('N' === $sGr)
	{
		$oFileManagerEnv =& getEnvObject();
		if($oFileManagerEnv->oAclFm->haveManagerRight())		
		{
			pasteUserFolder((int) $iIdRootFolder, $sPath);
		}
		$sIdx = 'list';
		listFiles($iIdRootFolder, $sGr, $sPath);
	}
	else 
	{
		$babBody->msgerror = bab_translate("Access denied");
	}
}


function pasteCollectiveDir($iIdRootFolder)
{
	global $babBody;
	$sSrcPath = (string) bab_gp('sSrcPath', '');
	$sTrgPath = (string) bab_gp('path', '');
	
	$sUploadPath = BAB_FmFolderHelper::getUploadPath();
	
	$bSrcPathHaveVersioning = false;
	$bTrgPathHaveVersioning = false;
	$bSrcPathCollective		= false;
	
	$oFmFolderSet = new BAB_FmFolderSet();
	
//	bab_debug(__LINE__ . ' ' . basename(__FILE__) . ' ' . __FUNCTION__);
	
	$iSrcPathLength = strlen(trim($sSrcPath));
	if($iSrcPathLength > 0)
	{
		$oRootFmFolder = BAB_FmFolderHelper::getFmFolderById($iIdRootFolder);
		if(!is_null($oRootFmFolder))
		{
			$iSrcIdOwner = 0;
			$iTrgIdOwner = 0;
			$sSrcPathName = '';
			$sTrgPathName = '';
			
			$oSrcFmFolder = null;
			$oTrgFmFolder = null;
			BAB_FmFolderHelper::getFileInfoForCollectiveDir($iIdRootFolder, $sSrcPath, $iSrcIdOwner, $sSrcPathName, $oSrcFmFolder);
			BAB_FmFolderHelper::getFileInfoForCollectiveDir($iIdRootFolder, $sTrgPath, $iTrgIdOwner, $sTrgPathName, $oTrgFmFolder);

			$bTrgPathHaveVersioning = ('Y' === $oTrgFmFolder->getVersioning());
			$bSrcPathHaveVersioning = ('Y' === $oSrcFmFolder->getVersioning());
			$bSrcPathCollective		= ((string) $oRootFmFolder->getName() . '/' . $sSrcPath . '/' === (string) $oSrcFmFolder->getRelativePath() . $oSrcFmFolder->getName() . '/');

			$sRelativeSrcPath = $oRootFmFolder->getName() . '/' . $sSrcPath . (($iSrcPathLength > 0) ? '/' : '');
			$sRelativeTrgPath = $oRootFmFolder->getName() . '/' . $sTrgPath . ((strlen(trim($sTrgPath)) > 0) ? '/' : '');
			
			$sFullSrcPath = realpath((string) $sUploadPath . $sRelativeSrcPath);
			$sFullTrgPath = realpath((string) $sUploadPath . $sRelativeTrgPath);
			
			$sRegKey	= md5($oRootFmFolder->getName() . '/');
			$sDirectory = '/bab/fileManager/cuttedFolder/';
			$oRegHlp	= new BAB_RegitryHelper($sDirectory, $sRegKey);
			
			if($sFullSrcPath === (string) realpath($sFullTrgPath . '/' . getLastPath($sSrcPath) . '/'))
			{
				$oRegHlp->removeItem($sSrcPathName);
				$oRegHlp->save();
			}
			else 
			{
				//Supprimer les versions
				//Déplacer les répertoires
				//Mise à jour de la base de données (folder et fichier)
				//Recalculer les nouveaux iIdOwner des fichiers
				//Mise à jour des répertoires coupés
				
				$sPath = substr($sFullTrgPath, 0, strlen($sFullSrcPath));
				if($sPath !== $sFullSrcPath)
				{
					if(bab_isAccessValid(BAB_FMMANAGERS_GROUPS_TBL, $iSrcIdOwner))
					{
						if(bab_isAccessValid(BAB_FMMANAGERS_GROUPS_TBL, $iTrgIdOwner))
						{
							$bSrcValid = ((realpath(substr($sFullSrcPath, 0, strlen($sUploadPath))) === (string) realpath($sUploadPath)) && is_readable($sFullSrcPath));
							$bTrgValid = ((realpath(substr($sFullTrgPath, 0, strlen($sUploadPath))) === (string) realpath($sUploadPath)) && is_writable($sFullTrgPath));

							if($bSrcValid && $bTrgValid)
							{
								$sLastRelativePath = $sRelativeSrcPath;
								$sNewRelativePath = $sRelativeTrgPath . getLastPath($sRelativeSrcPath) . '/';
								
//								bab_debug('sLastRelativePath ==> ' . $sLastRelativePath . ' sNewRelativePath ==> ' . $sNewRelativePath);
							
								global $babDB;
								$oFolderFileSet = new BAB_FolderFileSet();
								$oPathName =& $oFolderFileSet->aField['sPathName'];
								
								$oFmFolderSet = new BAB_FmFolderSet();
								$oRelativePath =& $oFmFolderSet->aField['sRelativePath'];
										
								$aCollectiveFolder = array();
								
								if(false === $bSrcPathCollective)
								{
									 if(false === $bTrgPathHaveVersioning)
									 {
										global $babDB;
										
									 	//Suppression des versions pour les répertoires qui ne sont pas contenus dans des 
									 	//répertoires collectifs
									 	{
											$oCriteria = $oPathName->like($babDB->db_escape_like($sSrcPathName) . '%');
											$oFmFolderSet->select($oRelativePath->like($babDB->db_escape_like($sLastRelativePath) . '%'));
											while(null !== ($oFmFolder = $oFmFolderSet->next()))
											{
												$sNewFolderRelPath = $sNewRelativePath . substr($oFmFolder->getRelativePath(), strlen($sLastRelativePath));
												$oCriteria = $oCriteria->_and($oPathName->notLike(
													$babDB->db_escape_like($oFmFolder->getRelativePath() . $oFmFolder->getName() . '/') . '%'));		
												$oFmFolder->setRelativePath($sNewFolderRelPath);
												$aCollectiveFolder[] = $oFmFolder;
											}
											$oFolderFileSet->removeVersions($oCriteria);
											
											bab_debug($oFolderFileSet->getSelectQuery($oCriteria));
											$oFolderFileSet->select($oCriteria);
											while(null !== ($oFolderFile = $oFolderFileSet->next()))
											{
												$oFolderFile->setMajorVer(1);
												$oFolderFile->setMinorVer(0);
//												$oFolderFile->setFlowApprobationInstanceId(0);
//												$oFolderFile->setConfirmed('Y');
												$oFolderFile->save();
											}
									 	}
									 }
								}								

								if(rename($sFullSrcPath, $sFullTrgPath . '/' . getLastPath($sSrcPath) . '/'))
								{
									$sName = getLastPath($sSrcPath);

									$oRegHlp->removeItem($sLastRelativePath);
									$aDatas = array();
									foreach($oRegHlp->aDatas as $sKey => $aItem)
									{
										$sString = substr($sKey, 0, strlen($sLastRelativePath));
										if($sString === $sLastRelativePath)
										{
											$sNewRelPath = $sNewRelativePath . substr($sKey, strlen($sLastRelativePath));
											$aItem['sRelativePath'] = $sNewRelPath;
											$aDatas[$sNewRelPath] = $aItem;
										}
										else 
										{
											$aDatas[$sKey] = $aItem;
										}
									}
									$oRegHlp->aDatas = $aDatas;
									$oRegHlp->save();
									
//									bab_debug($oRegHlp->aDatas);
									
									$sOldRelativePath = substr($sLastRelativePath, 0, - (strlen($sName) + 1));
//									bab_debug('sName ==> ' . $sName . ' sOldRelativePath ==> ' . $sOldRelativePath);

									//Mise à jour des répertoires en base
									{									
										$oName =& $oFmFolderSet->aField['sName'];
										$oCriteria = $oRelativePath->in($sOldRelativePath);
										$oCriteria = $oCriteria->_and($oName->in($sName));
										$_oFmFolder = $oFmFolderSet->get($oCriteria);
										if(!is_null($_oFmFolder))
										{
											$_oFmFolder->setRelativePath($sNewRelativePath);
											$aCollectiveFolder[] = $_oFmFolder;
										}
//										bab_debug($aCollectiveFolder);
										
										foreach($aCollectiveFolder as $_oFmFolder)
										{
											$_oFmFolder->save();
										}
									}

									$aProcessedPath = array();
									$oFolderFileSet->select($oPathName->like($babDB->db_escape_like($sLastRelativePath) . '%'));
									while(null !== ($oFolderFile = $oFolderFileSet->next()))
									{
										$sOldPathName = $oFolderFile->getPathName();
										$sNewPathName = $sNewRelativePath . substr($sOldPathName, strlen($sLastRelativePath));
										
										if(false === array_key_exists($sNewPathName, $aProcessedPath))
										{
											$sUrlPath = substr($sNewPathName, strlen($oRootFmFolder->getName() . '/'), -1);
	//										bab_debug('sUrlPath ==> ' . $sUrlPath);
											$iIdOwner = 0;
											$sRelativePath = '';
											$_oFmFolder = null;
											BAB_FmFolderHelper::getFileInfoForCollectiveDir($iIdRootFolder, $sUrlPath, $iIdOwner, $sRelativePath, $_oFmFolder);
											$aProcessedPath[$sNewPathName] = $iIdOwner;
										}
										$oFolderFile->setPathName($sNewPathName);
										$oFolderFile->setOwnerId($aProcessedPath[$sNewPathName]);
										$oFolderFile->save();
										
//										bab_debug('sOldPathName ==> ' . $sOldPathName . ' sNewPathName ==> ' . $sNewPathName . ' sFileName ==> ' . $oFolderFile->getName());
										bab_debug('iIdFile ==> ' . $oFolderFile->getId() . ' iIdOwner ==> ' . $oFolderFile->getOwnerId());
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
			}
//			bab_debug('sFullSrcPath ==> ' . $sFullSrcPath . ' versioning ' . (($bSrcPathHaveVersioning) ? 'Yes' : 'No') . ' bSrcPathCollective ' . (($bSrcPathCollective) ? 'Yes' : 'No'));
//			bab_debug('sFullTrgPath ==> ' . $sFullTrgPath . ' versioning ' . (($bTrgPathHaveVersioning) ? 'Yes' : 'No'));
		}
	}
}


function pasteUserFolder($iIdRootFolder, $sPath)
{
//	bab_debug(__LINE__ . ' ' . basename(__FILE__) . ' ' . __FUNCTION__);

	global $babBody;
	$sSrcPath = (string) bab_gp('sSrcPath', '');
	$sTrgPath = (string) bab_gp('path', '');
	
	$sUploadPath = BAB_FmFolderHelper::getUploadPath();
	
	$iSrcPathLength = strlen(trim($sSrcPath));
	if($iSrcPathLength > 0)
	{
		$oFileManagerEnv =& getEnvObject();
		
		$sRelativeSrcPath = $oFileManagerEnv->sRootFolderPath . $sSrcPath . (($iSrcPathLength > 0) ? '/' : '');
		$sRelativeTrgPath = $oFileManagerEnv->sRootFolderPath . $sTrgPath . ((strlen(trim($sTrgPath)) > 0) ? '/' : '');
			
		$sFullSrcPath = realpath((string) $sUploadPath . $sRelativeSrcPath);
		$sFullTrgPath = realpath((string) $sUploadPath . $sRelativeTrgPath);
		
//		bab_debug($sFullSrcPath);
//		bab_debug($sFullTrgPath);
		
		$sRegKey	= md5($oFileManagerEnv->sRootFolderPath);
		$sDirectory = '/bab/fileManager/cuttedFolder/';
		$oRegHlp	= new BAB_RegitryHelper($sDirectory, $sRegKey);
		
		if($sFullSrcPath === (string) realpath($sFullTrgPath . '/' . getLastPath($sSrcPath) . '/'))
		{
			$sSrcPathName = $oFileManagerEnv->sRelativePath . getLastPath($sSrcPath) . '/';
			$oRegHlp->removeItem($sSrcPathName);
			$oRegHlp->save();
		}
		else 
		{
			$sPath = substr($sFullTrgPath, 0, strlen($sFullSrcPath));
			if($sPath !== $sFullSrcPath)
			{
				$bSrcValid = ((realpath(substr($sFullSrcPath, 0, strlen($sUploadPath))) === (string) realpath($sUploadPath)) && is_readable($sFullSrcPath));
				$bTrgValid = ((realpath(substr($sFullTrgPath, 0, strlen($sUploadPath))) === (string) realpath($sUploadPath)) && is_writable($sFullTrgPath));
				
				if($bSrcValid && $bTrgValid)
				{
					$sLastRelativePath = $sRelativeSrcPath;
					$sNewRelativePath = $sRelativeTrgPath . getLastPath($sRelativeSrcPath) . '/';
					
//					bab_debug('sLastRelativePath ==> ' . $sLastRelativePath . ' sNewRelativePath ==> ' . $sNewRelativePath);
					
					if(rename($sFullSrcPath, $sFullTrgPath . '/' . getLastPath($sSrcPath) . '/'))
					{
						$sName = getLastPath($sSrcPath);

						$oRegHlp->removeItem($sLastRelativePath);
						$aDatas = array();
						foreach($oRegHlp->aDatas as $sKey => $aItem)
						{
							$sString = substr($sKey, 0, strlen($sLastRelativePath));
							if($sString === $sLastRelativePath)
							{
								$sNewRelPath = $sNewRelativePath . substr($sKey, strlen($sLastRelativePath));
								$aItem['sRelativePath'] = $sNewRelPath;
								$aDatas[$sNewRelPath] = $aItem;
							}
							else 
							{
								$aDatas[$sKey] = $aItem;
							}
						}
						$oRegHlp->aDatas = $aDatas;
						$oRegHlp->save();
					}
				}
			}
		}
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
$path = $oFileManagerEnv->sPath;
$gr = $oFileManagerEnv->sGr;
$id = $oFileManagerEnv->iId;
$bmanager = $oFileManagerEnv->oAclFm->haveManagerRight();
$upload = $oFileManagerEnv->oAclFm->haveUploadRight();


if( "add" === bab_pp('addf') )
	{
	
	$arr_obj = array();
	foreach($_FILES as $fieldname => $file) {
		$arr_obj[] = bab_fmFile::upload($fieldname);
	}
	
	if(!saveFile(
			$arr_obj,
			$id, 
			$gr, 
			$path, 
			bab_pp('description'), 
			bab_pp('keywords'), 
			bab_pp('readonly')
			)
		) {

		$idx = "add";
	}
}

if( 'upd' === bab_pp('updf'))
	{
	if( isset($_POST['description']))
		$descup = true;
	else
		$descup = false;

	
	if( !saveUpdateFile(
			bab_pp('idf'), 
			bab_fmFile::upload('uploadf'), 
			bab_pp('fname'), 
			bab_pp('description'), 
			bab_pp('keywords'), 
			bab_pp('readonly'), 
			bab_pp('confirm'), 
			bab_pp('bnotify'), 
			bab_pp('newfolder'), 
			$descup
			)
		) {
		$idx = 'viewfile';
		}
	else
		{
		$idx = 'unload';
		}
	}

if( $idx == "paste")
	{
	if(pasteFile(bab_gp('file'), $id, $gr, $path, bab_gp('tp'), $bmanager))
		{
		$path = bab_gp('tp');
		}
	$idx = "list";
	}

if( $idx == "del")
	{
	delFile(bab_rp('file'), $id, $gr, $path, $bmanager);
	$idx = "list";
	}

if( 'update' === bab_rp('cdel') )
{
	if( !empty($_REQUEST['delete'])) {
		deleteFiles(bab_rp('items'));
		}
	else {
		restoreFiles(bab_rp('items'));
		}
}


switch($idx)
	{
	case 'displayFolderForm':
		displayFolderForm();
		break;
		
	case 'processFolderCommand':
		processFolderCommand($idx);
		break;

	case 'cutFolder':
		cutFolder((($gr == 'N') ? false : true), $path, $id, $idx);
		break;

	case 'pasteFolder':
		pasteFolder((($gr == 'N') ? false : true), $path, $id, $idx);
		break;
		
	case "unload":
		fileUnload($id, $gr, $path);
		exit;
		break;

	case "viewfile":
		viewFile(bab_rp('idf'), $id, $path);
		exit;
		break;

	case 'displayRightForm':
		displayRightForm($bmanager, $upload, $path, $id, $gr);
		break;

	case 'setRight':
		setRight($bmanager, $upload, $id, $gr, $path, $idx);
		break;
		
	case "get":
		getFile($file, $id, $gr, $path);
		break;

	case "trash":
		$babBody->title = bab_translate("Trash");
		listTrashFiles($id, $gr, $path);
		$babBody->addItemMenu("list", bab_translate("Folders"), $GLOBALS['babUrlScript']."?tg=fileman&idx=list&id=".$id."&gr=".$gr."&path=".urlencode($path));
		if( $upload) {
			$babBody->addItemMenu("add", bab_translate("Upload"), $GLOBALS['babUrlScript']."?tg=fileman&idx=add&id=".$id."&gr=".$gr."&path=".urlencode($path));
			}
		if( $bmanager) {
			$babBody->addItemMenu("trash", bab_translate("Trash"), $GLOBALS['babUrlScript']."?tg=fileman&idx=trash&id=".$id."&gr=".$gr."&path=".urlencode($path));
			}
		break;

	case "add":
		$babBody->title = bab_translate("Upload file to")." ";
		if( $gr == 'Y' )
		{
			$oFmFolder = BAB_FmFolderHelper::getFmFolderById($id);
			if(!is_null($oFmFolder))
			{
				$babBody->title .= $oFmFolder->getName();
			}
		}
		$babBody->title .= "/".$path;

		addFile($id, $gr, $path, bab_pp('description'), bab_pp('keywords'));
		$babBody->addItemMenu("list", bab_translate("Folders"), $GLOBALS['babUrlScript']."?tg=fileman&idx=list&id=".$id."&gr=".$gr."&path=".urlencode($path));
		if( $upload) {
			$babBody->addItemMenu("add", bab_translate("Upload"), $GLOBALS['babUrlScript']."?tg=fileman&idx=add&id=".$id."&gr=".$gr."&path=".urlencode($path));
			}
		if( $bmanager) {
			$babBody->addItemMenu("trash", bab_translate("Trash"), $GLOBALS['babUrlScript']."?tg=fileman&idx=trash&id=".$id."&gr=".$gr."&path=".urlencode($path));
			}
		break;

	case "disk":
		$babBody->title = bab_translate("File manager");
		showDiskSpace($id, $gr, $path);
		$babBody->addItemMenu("list", bab_translate("Folders"), $GLOBALS['babUrlScript']."?tg=fileman&idx=list&id=".$id."&gr=".$gr."&path=".urlencode($path));
		if( $upload)  {
			$babBody->addItemMenu("add", bab_translate("Upload"), $GLOBALS['babUrlScript']."?tg=fileman&idx=add&id=".$id."&gr=".$gr."&path=".urlencode($path));
			}
		if( $bmanager) {
			$babBody->addItemMenu("trash", bab_translate("Trash"), $GLOBALS['babUrlScript']."?tg=fileman&idx=trash&id=".$id."&gr=".$gr."&path=".urlencode($path));
			}
		break;

	case "cut":
		cutFile( bab_gp('file'), $id, $gr, $path, $bmanager);
		/* no break */
	default:
	case "list":
		listFiles();
		break;
	}
$babBody->setCurrentItemMenu($idx);
?>