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
include_once $GLOBALS['babInstallPath'].'utilit/fileincl.php';
include_once $GLOBALS['babInstallPath'].'utilit/uploadincl.php';
include_once $GLOBALS['babInstallPath'].'utilit/indexincl.php';
require_once $GLOBALS['babInstallPath'].'utilit/baseFormProcessingClass.php';


function deleteFile($idf, $name, $path)
{
	global $babDB;

	if(is_dir($path.BAB_FVERSION_FOLDER."/"))
	{
		$res = $babDB->db_query("select * from ".BAB_FM_FILESVER_TBL." where id_file='".$babDB->db_escape_string($idf)."'");
		while($arr = $babDB->db_fetch_array($res))
		{
			if(file_exists($path.BAB_FVERSION_FOLDER."/".$arr['ver_major'].",".$arr['ver_minor'].",".$name))
			{
				unlink($path.BAB_FVERSION_FOLDER."/".$arr['ver_major'].",".$arr['ver_minor'].",".$name);
			}
		}
	}
	$babDB->db_query("delete from ".BAB_FM_FILESVER_TBL." where id_file='".$babDB->db_escape_string($idf)."'");
	$babDB->db_query("delete from ".BAB_FM_FILESLOG_TBL." where id_file='".$babDB->db_escape_string($idf)."'");
	$babDB->db_query("delete from ".BAB_FM_FIELDSVAL_TBL." where id_file='".$babDB->db_escape_string($idf)."'");
	$babDB->db_query("delete from ".BAB_FILES_TBL." where id='".$babDB->db_escape_string($idf)."'");
}

class listFiles
	{
	var $db;
	var $res;
	var $count;
	var $fullpath;
	var $id;
	var $gr;
	var $path;
	var $jpath;
	var $countmgrp;
	var $countgrp;
	var $bmanager;
	var $countwf;
	var $arrmgrp = array();
	var $bdownload;
	var $reswf;
	var $arrdir = array();
	var $buaf;
	
	var $sPathName = '';
	var $iIdOwner = 0;
	var $oFolderFileSet = null;
	var $sRootFolderPath = '';
	
	/**
	 * Files extracted by readdir
	 */
	var $files_from_dir = array();

	function listFiles($id, $gr, $path, $bmanager, $what ="list")
		{
		global $babBody, $babDB, $BAB_SESS_USERID;
		include_once $GLOBALS['babInstallPath']."utilit/afincl.php";
		$this->fullpath = bab_getUploadFullPath($gr, $id);
		
		$this->initEnv($gr, $id, $path);
		$this->oFolderFileSet = new BAB_FolderFileSet();


		if('N' === $gr)
		{
			$this->sRootFolderPath = 'U' . $id . '/';
		}
		else 
		{
			$oFmFolderSet = new BAB_FmFolderSet();
			$oId =& $oFmFolderSet->aField['iId'];
			
			$oFmFolder = $oFmFolderSet->get($oId->in($id));
			if(!is_null($oFmFolder))
			{
				$this->sRootFolderPath = $oFmFolder->getName() . '/';
			}
		}


		$this->path = $path;
		$this->jpath = bab_toHtml($path, BAB_HTML_JS);
		$this->id = $id;
		$this->gr = $gr;
		$this->countmgrp = 0;
		$this->countgrp = 0;
		$this->buaf = false;

		$this->bmanager = $bmanager;
		$this->countwf = 0;
		$this->bdownload = false;
		for( $i = 0; $i < count($babBody->aclfm['id']); $i++)
			{
			$this->arrgrp['id'][] = $babBody->aclfm['id'][$i];
			$this->arrgrp['ma'][] = $babBody->aclfm['ma'][$i];
			$this->arrgrp['folder'][] = $babBody->aclfm['folder'][$i];
			$this->arrgrp['hide'][] = $babBody->aclfm['hide'][$i];
			if( $babBody->aclfm['id'][$i] == $id )
				{
				$this->bdownload = $babBody->aclfm['down'][$i];

				if( $what == "list" && $gr == "Y" && $babBody->aclfm['idsa'][$i] != 0 && ($this->buaf = isUserApproverFlow($babBody->aclfm['idsa'][$i], $BAB_SESS_USERID)) )
					{
						$this->selectWaitingFile();
					}
				}
			}

		if(!$this->bdownload )
			$this->bdownload = $bmanager? true: false;

		$this->countgrp = 0;
		if( $gr == "Y" || ($gr == "N" && !empty($path)))
			{
			$this->countgrp = 0;
			}
		else if( isset($this->arrgrp['id']))
			{
			$this->countgrp = count($this->arrgrp['id']);
			}

		if( $id != 0  && is_dir($this->fullpath.$path."/"))
			{
			$h = opendir($this->fullpath.$path."/");
			while (($f = readdir($h)) != false)
				{
				if ($f != "." and $f != ".." and $f != BAB_FVERSION_FOLDER) 
					{
					if (is_dir($this->fullpath.$path."/".$f)) {
							$this->arrdir[] = $f;
						} else {
							$this->files_from_dir[] = $f;
						}
					}
				}
			closedir($h);

			if (!isset($this->arrudir))
				$this->arrudir = array();

			if (is_array($this->arrdir))
				{
				natcasesort($this->arrdir);
				$this->arrdir = array_values($this->arrdir);
				reset ($this->arrdir);
				
				foreach ( $this->arrdir as $f )
					{
					$this->arrudir[] = $GLOBALS['babUrlScript']."?tg=fileman&idx=".urlencode($what)."&id=".$id."&gr=".$gr."&path=".urlencode($path.($path ==""?"":"/").$f);
					}
				}

			if( !empty($path))
				{
				$i = strrpos($path, "/");
				if( !$i )
					$p = "";
				else
					$p = substr( $path, 0, $i);
				if (isset($this->arrudir) && is_array($this->arrudir))
					{
					array_unshift ($this->arrdir,". .");
					array_unshift ($this->arrudir, $GLOBALS['babUrlScript']."?tg=fileman&idx=".urlencode($what)."&id=".$id."&gr=".$gr."&path=".urlencode($p));
					}
				else
					{
					$this->arrdir[] = ". .";
					$this->arrudir[] = $GLOBALS['babUrlScript']."?tg=fileman&idx=".urlencode($what)."&id=".$id."&gr=".$gr."&path=".urlencode($p);
					}
				}
			
			$this->prepare();
			$this->autoadd_files();
			}
		else
			{
			$this->count = 0;
			}
			
		
		}

		function initEnv($sGr, $iId, $sPath)
		{
			$this->iIdOwner = $iId;
			
			$sEndSlash = '';
			if(strlen(trim($sPath)) > 0)
			{
				$sEndSlash = '/';
			}
			
			if('Y' === $sGr)
			{
				BAB_FmFolderHelper::getFileInfoForCollectiveDir($iId, $sPath, $this->iIdOwner, $this->sPathName);
			}
			else 
			{
				$this->sPathName = 'U' . $iId . '/' . $sPath . $sEndSlash;
			}
		}
		
		function selectWaitingFile()
		{
			$aWaitingAppInstanceId = bab_getWaitingIdSAInstance($GLOBALS['BAB_SESS_USERID']);
			if(count($aWaitingAppInstanceId) > 0)
			{
				$this->oFolderFileSet->bUseAlias = false;
				$oIdOwner = $this->oFolderFileSet->aField['iIdOwner'];
				$oGroup = $this->oFolderFileSet->aField['sGroup'];
				$oState = $this->oFolderFileSet->aField['sState'];
				$oPathName = $this->oFolderFileSet->aField['sPathName'];
				$oConfirmed = $this->oFolderFileSet->aField['sConfirmed'];
				$oIdApprobationInstance = $this->oFolderFileSet->aField['iIdApprobationInstance'];
				
				$oCriteria = $oIdOwner->in($this->iIdOwner);
				$oCriteria = $oCriteria->_and($oGroup->in($this->gr));
				$oCriteria = $oCriteria->_and($oState->in(''));
				$oCriteria = $oCriteria->_and($oPathName->in($this->sPathName));
				$oCriteria = $oCriteria->_and($oConfirmed->in('N'));
				
				$oCriteria = $oCriteria->_and($oIdApprobationInstance->in($aWaitingAppInstanceId));
				
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
		
		function prepare() {
			$this->oFolderFileSet->bUseAlias = false;
			$oIdOwner = $this->oFolderFileSet->aField['iIdOwner'];
			$oGroup = $this->oFolderFileSet->aField['sGroup'];
			$oState = $this->oFolderFileSet->aField['sState'];
			$oPathName = $this->oFolderFileSet->aField['sPathName'];
			$oConfirmed = $this->oFolderFileSet->aField['sConfirmed'];
			
			$oCriteria = $oIdOwner->in($this->iIdOwner);
			$oCriteria = $oCriteria->_and($oGroup->in($this->gr));
			$oCriteria = $oCriteria->_and($oState->in(''));
			$oCriteria = $oCriteria->_and($oPathName->in($this->sPathName));
			$oCriteria = $oCriteria->_and($oConfirmed->in('Y'));
			
			$this->oFolderFileSet->select($oCriteria);
			$this->res = $this->oFolderFileSet->_oResult;
			$this->count = $this->oFolderFileSet->count();
			$this->oFolderFileSet->bUseAlias = true;
		}


		/** 
		 * if there is file not presents in database, add and recreate $this->res
		 */
		function autoadd_files() 
		{
//			return;
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
					$oCriteria = $oIdOwner->in($this->iIdOwner);
					$oCriteria = $oCriteria->_and($oPathName->in($this->sPathName));
					$oCriteria = $oCriteria->_and($oGroup->in($this->gr));
					$oCriteria = $oCriteria->_and($oName->in($dir_file));
					$this->oFolderFileSet->select($oCriteria);
					
					if(0 === $this->oFolderFileSet->count())
					{
						$oFolderFile->setName($dir_file);
						$oFolderFile->setPathName($this->sPathName);
						$oFolderFile->setOwnerId($this->iIdOwner);
						$oFolderFile->setGroup($this->gr);
						$oFolderFile->setCreationDate(date("Y-m-d H:i:s"));
						$oFolderFile->setAuthorId($GLOBALS['babAutoAddFilesAuthorId']);
						$oFolderFile->setModifiedDate(date("Y-m-d H:i:s"));
						$oFolderFile->setModifierId($GLOBALS['babAutoAddFilesAuthorId']);
						$oFolderFile->setConfirmed('Y');
						
						$oFolderFile->setDescription('');
						$oFolderFile->setKeywords('');
						$oFolderFile->setLinkId(0);
						$oFolderFile->setReadOnly('N');
						$oFolderFile->setState('');
						$oFolderFile->setHits(0);
						$oFolderFile->setApprobationInstanceId(0);
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
	function DisplayFolderFormBase($sGr, $sPath, $iId)
	{
		parent::BAB_BaseFormProcessing();
		
		$sAction 	= (string) bab_gp('sAction', '');
		$sDirName	= (string) bab_gp('sDirName', '');
		
		$this->set_data('sIdx', 'createEditFolder');
		$this->set_data('sAction', $sAction);
		$this->set_data('sTg', 'fileman');
		
		$this->setCaption();
		
		$this->set_data('iId', $iId);
		$this->set_data('sPath', $sPath);
		$this->set_data('sGr', $sGr);
		
		$this->set_data('sDirName', $sDirName);
		$this->set_data('sOldDirName', '');
		$this->set_data('iIdFolder', 0);
		
		$this->set_data('sSimple', 'simple');
		$this->set_data('sCollective', 'collective');
		$this->set_data('sHtmlTable', '');
		
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
	function DisplayUserFolderForm($sGr, $sPath, $iId)
	{
		parent::DisplayFolderFormBase($sGr, $sPath, $iId);
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
	
	function DisplayCollectiveFolderForm($sGr, $sPath, $iId)
	{
		parent::DisplayFolderFormBase($sGr, $sPath, $iId);
		
		$this->setCaption();
		$this->set_data('sYes', 'Y');
		$this->set_data('sNo', 'N');
		$this->set_data('iNone', 0);
		
		$this->set_data('iAppSchemeId', 0);
		$this->set_data('iAppSchemeName', '');
		$this->set_data('sAppSchemeNameSelected', '');
		
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
		
	}
	
	function handleCreation()
	{
		$this->set_data('isCollective', false);
		$this->set_data('isActive', true);
		$this->set_data('isAutoApprobation', false);
		$this->set_data('isFileNotify', false);
		$this->set_data('isVersioning', false);
		$this->set_data('isShow', true);
	}
	
	function handleEdition()
	{
		$this->set_data('isCollective', false);
		$this->set_data('isActive', true);
		$this->set_data('isAutoApprobation', false);
		$this->set_data('isFileNotify', false);
		$this->set_data('isVersioning', false);
		$this->set_data('isShow', true);

		$this->get_data('iId', $iId);
		$this->get_data('sPath', $sPath);
		$this->get_data('sDirName', $sDirName);

		$this->set_data('sOldDirName', $sDirName);

		$sRelativePath = '';
		$oFmFolder = BAB_FmFolderHelper::getFmFolderById($iId);
		if(!is_null($oFmFolder))
		{
			$sRelativePath = $oFmFolder->getName() . '/';
			if(strlen(trim($sPath)) > 0)
			{
				$sRelativePath .=  $sPath . '/';
			}
		}

		global $babDB;
		$oFmFolderSet = new BAB_FmFolderSet();
		$oRelativePath =& $oFmFolderSet->aField['sRelativePath']; 
		$oName =& $oFmFolderSet->aField['sName']; 
		$oCriteria = $oRelativePath->in($babDB->db_escape_like($sRelativePath));
		$oCriteria = $oCriteria->_and($oName->in($sDirName));
		$oFmFolder = $oFmFolderSet->get($oCriteria);
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
		}
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
				$sRelativePath = BAB_FmFolderHelper::getUserDirUploadPath($this->id) . $this->sPath . '/';
			}
			else 
			{
				BAB_FmFolderHelper::getFileInfoForCollectiveDir($this->id, $this->sPath, $iIdOwner, $this->sRelativePath);
			}
			
			$this->sEndSlash = '';
			if(strlen(trim($this->sPath)) > 0)
			{
				$this->sEndSlash = '/';
			}
			
		}

		function selectTrashFile($path)
		{
			$sFolderName = '';
			$oFmFolder = BAB_FmFolderHelper::getFmFolderById($this->id);
			if(!is_null($oFmFolder))
			{
				global $babDB;
				
				$sPathName = $oFmFolder->getName() . '/' . $path . $this->sEndSlash;
			
				$this->oFolderFileSet = new BAB_FolderFileSet();
				$oState =& $this->oFolderFileSet->aField['sState'];
				$oPathName =& $this->oFolderFileSet->aField['sPathName'];
				
				$oCriteria = $oState->in('D');
				$oCriteria = $oCriteria->_and($oPathName->like($babDB->db_escape_like($sPathName)));
				
				$this->oFolderFileSet->select($oCriteria, array('sName' => 'ASC'));
			}
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
			for( $i = 0; $i < count($babBody->aclfm['id']); $i++)
				{
				if( $babBody->aclfm['ma'][$i] == 0)
					$this->arrgrp[] = $babBody->aclfm['id'][$i];

				if( $babBody->aclfm['ma'][$i] == 1)
					{
					$this->arrmgrp[] = $babBody->aclfm['id'][$i];
					}
				}
			if( !empty($GLOBALS['BAB_SESS_USERID'] ) && $babBody->ustorage)
				$this->diskp = 1;
			else
				$this->diskp = 0;
			if( !empty($GLOBALS['BAB_SESS_USERID'] ) && bab_isUserAdministrator() )
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


function listFiles($id, $gr, $path, $bmanager, $upload)
	{
	global $babBody;

	class temp extends listFiles
		{
        var $bytes;
        var $mkdir;
        var $rename;
        var $delete;
        var $directory;
        var $download;
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
        var $rootpath;
        var $bdel;
        var $bmanager;
        var $xres;
        var $xcount;
		var $bversion;
		var $block;
		var $blockauth;
		var $ovfurl;
		var $ovfhisturl;
		var $ovfcommiturl;
		var $bfvwait;

		var $sFolderFormAdd;
		var $sFolderFormEdit;
		var $sFolderFormUrl;
		var $bFolderUrl;
		
		var $sRight;
		var $sRightUrl;
		var $bRightUrl;
		
		var $altfilelog;
		var $altfilelock;
		var $altfileunlock;
		var $altfilewrite;
		var $altbg = false;


		function temp($id, $gr, $path, $bmanager)
			{
			global $BAB_SESS_USERID, $babDB;
			$this->listFiles($id, $gr, $path, $bmanager);
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
            $this->sRight = bab_translate("Right"); 

			$this->rooturl = bab_toHtml($GLOBALS['babUrlScript']."?tg=fileman&idx=list");
			$this->refreshurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=fileman&idx=list&id=".$id."&gr=".$gr."&path=".urlencode($path));
			$this->urldiskspace = bab_toHtml($GLOBALS['babUrlScript']."?tg=fileman&idx=disk&id=".$id."&gr=".$gr."&path=".urlencode($path));
			
			$this->sFolderFormUrl = bab_toHtml($GLOBALS['babUrlScript']."?tg=fileman&idx=displayFolderForm&sAction=createFolder&id=".$id."&gr=".$gr."&path=".urlencode($path));


			$this->upfolderimg = bab_printTemplate($this, "config.html", "parentfolder");
			$this->usrfolderimg = bab_printTemplate($this, "config.html", "userfolder");
			$this->grpfolderimg = bab_printTemplate($this, "config.html", "groupfolder");
			$this->manfolderimg = bab_printTemplate($this, "config.html", "managerfolder");

			if( $gr == "Y")
				{
				$this->rootpath = '';
				$oFmFolder = BAB_FmFolderHelper::getFmFolderById($id);
				if(!is_null($oFmFolder))
				{
					$version = $oFmFolder->getVersioning();
					$this->rootpath = bab_toHtml($oFmFolder->getName());
				}
				$this->bupdate = bab_isAccessValid(BAB_FMUPDATE_GROUPS_TBL, $id);
				if( !$this->bupdate )
					$this->bupdate = $bmanager;
				}
			else
				{
				$this->bupdate = false;
				$version = 'N';
				$this->rootpath = "";
				}
			if( $version == 'Y')
				{
	            $this->altfilelog =  bab_translate("View log");
	            $this->altfilelock =  bab_translate("Edit file");
	            $this->altfileunlock =  bab_translate("Unedit file");
	            $this->altfilewrite =  bab_translate("Commit file");
				$this->bversion = true;
				}
			else
				$this->bversion = false;
			$this->bdel = false;
			if( $this->bmanager )
				{
				$this->oFolderFileSet->bUseAlias = false;
				$oState = $this->oFolderFileSet->aField['sState'];
				$oPathName = $this->oFolderFileSet->aField['sPathName'];
				
				global $babDB;
				$oCriteria = $oPathName->like($babDB->db_escape_like($this->sRootFolderPath) . '%');
				$oCriteria = $oCriteria->_and($oState->in('X'));
				
				$this->oFolderFileSet->select($oCriteria);
				$this->xres = $this->oFolderFileSet->_oResult;
				$this->xcount = $this->oFolderFileSet->count();
				$this->oFolderFileSet->bUseAlias = true;

				if(!empty($path) && count($this->arrdir) <= 1 && $this->count == 0 )
					$this->bdel = true;
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
				$this->altbg = !$this->altbg;
				$this->name = bab_toHtml($this->arrdir[$i]);
				$this->bFolderFormUrl = false;

				$this->sRightUrl = '#';
				$this->bRightUrl = false;

				static $aExcludedDir = array('.', '..', '. .');
				if(!in_array($this->name, $aExcludedDir))	
				{	
					$this->sFolderFormUrl = bab_toHtml($GLOBALS['babUrlScript'] . '?tg=fileman&idx=displayFolderForm&sAction=editFolder&id=' . $this->id . 
						'&gr=' . $this->gr . '&path=' . urlencode($this->path) . '&sDirName=' . urlencode($this->name));
					$this->bFolderFormUrl = true;
					
					$oFmFolder = null;
					if($this->isCollective($this->name, $this->sPathName, $oFmFolder))
					{
						$this->bRightUrl = true;
						$this->sRightUrl = bab_toHtml($GLOBALS['babUrlScript'] . '?tg=fileman&idx=displayRightForm&id=' . $this->id . 
							'&gr=' . $this->gr . '&path=' . urlencode($this->path) . '&iIdFolder=' . $oFmFolder->getId());
					}
				}
				$this->url = bab_toHtml($this->arrudir[$i]);
				$i++;
				return true;
			}
			else
			{
				return false;
			}
		}

		function isCollective($sFolderName, $sRelativePath, &$oFmFolder)
		{
			$oFmFolderSet = new BAB_FmFolderSet();
			$oName =& $oFmFolderSet->aField['sName']; 
			$oRelativePath =& $oFmFolderSet->aField['sRelativePath']; 
			
			$oCriteria = $oName->in($sFolderName);
			$oCriteria = $oCriteria->_and($oRelativePath->in($sRelativePath));
			
			$oFmFolder = $oFmFolderSet->get($oCriteria);
			return (null !== $oFmFolder);
		}
		
		function getnextgrpdir(&$skip)
			{
			static $m = 0;
			if( $m < $this->countgrp)
				{
				if( $this->arrgrp['hide'][$m] )
					{
					$skip = true;
					$m++;
					return true;
					}
					
				$this->altbg = !$this->altbg;
				$this->name = bab_toHtml($this->arrgrp['folder'][$m]);
				$this->url = bab_toHtml($GLOBALS['babUrlScript']."?tg=fileman&idx=list&id=".$this->arrgrp['id'][$m]."&gr=Y&path=");
				$this->ma = $this->arrgrp['ma'][$m];
				$m++;
				return true;
				}
			else
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
			
			$sFullPathName = BAB_FmFolderHelper::getUploadPath() . $this->sPathName . $arr['name'];
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
				$ufile = urlencode($arr['name']);
				$upath = urlencode($this->path);
				$this->url = bab_toHtml($GLOBALS['babUrlScript']."?tg=fileman&idx=upd&id=".$this->id."&gr=".$this->gr."&path=".$upath."&file=".$ufile);
				$this->viewurl = bab_toHtml( $GLOBALS['babUrlScript']."?tg=fileman&idx=viewfile&idf=".$arr['id']."&id=".$this->id."&gr=".$this->gr."&path=".$upath."&file=".$ufile);
				$this->urlget = bab_toHtml($GLOBALS['babUrlScript']."?tg=fileman&idx=get&id=".$this->id."&gr=".$this->gr."&path=".$upath."&file=".$ufile);
				$this->cuturl = bab_toHtml($GLOBALS['babUrlScript']."?tg=fileman&idx=cut&id=".$this->id."&gr=".$this->gr."&path=".$upath."&file=".$ufile);
				$this->delurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=fileman&idx=del&id=".$this->id."&gr=".$this->gr."&path=".$upath."&file=".$ufile);
				if( $this->bversion )
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
				$ufile = urlencode($arr['name']);
				
				$upath = urlencode((string) substr($arr['path'], strlen($this->sRootFolderPath), -1));
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

	if( $id != 0 )
		{
		$pathx = bab_getUploadFullPath($gr, $id);
		if( substr($pathx, -1) == "/" )
			$pathx = substr($pathx, 0, -1);
		if(!is_dir($pathx) && !bab_mkdir($pathx, $GLOBALS['babMkdirMode'])) {
			$babBody->msgerror = bab_translate("Can't create directory: ").$pathx;
			}
	}



	$babBody->title = bab_translate("File manager");
	$babBody->addItemMenu("list", bab_translate("Folders"), $GLOBALS['babUrlScript']."?tg=fileman&idx=list&id=".$id."&gr=".$gr."&path=".urlencode($path));
	if($upload) 
	{
		$babBody->addItemMenu("add", bab_translate("Upload"), $GLOBALS['babUrlScript']."?tg=fileman&idx=add&id=".$id."&gr=".$gr."&path=".urlencode($path));
	}
	if($bmanager) 
	{
		$babBody->addItemMenu("trash", bab_translate("Trash"), $GLOBALS['babUrlScript']."?tg=fileman&idx=trash&id=".$id."&gr=".$gr."&path=".urlencode($path));
	}

	if(!empty($id) && $gr == "Y")
	{
		$GLOBALS['babWebStat']->addFolder($id);
	}

	$temp = new temp($id, $gr, $path, $bmanager);
	$babBody->babecho(	bab_printTemplate($temp,"fileman.html", "fileslist"));
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
			global $babDB;
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
			$this->descval = isset($description) ? bab_toHtml($description) : "";
			$this->keysval = isset($keywords) ? bab_toHtml($keywords) : "";
			if($gr == 'Y')
			{
				$this->res = $babDB->db_query("select * from ".BAB_FM_FIELDS_TBL." where id_folder='".$babDB->db_escape_string($id)."'");
				$this->count = $babDB->db_num_rows($this->res);
			}
			else
			{
				$this->count = 0;
			}
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

	$access = false;
	if($gr == "N" && !empty($BAB_SESS_USERID))
	{
		if($babBody->ustorage) 
		{
			$access = true;
		}
	}

	if($gr == "Y" && !empty($BAB_SESS_USERID))
	{
		for($i = 0; $i < count($babBody->aclfm['id']); $i++)
		{
			if($babBody->aclfm['id'][$i] == $id && ($babBody->aclfm['uplo'][$i] || $babBody->aclfm['ma'][$i] == 1))
			{
				$access = true;
				break;
			}
		}
	}

	if(!$access)
	{
		$babBody->msgerror = bab_translate("Access denied");
		return;
	}

	$temp = new temp($id, $gr, $path, $description, $keywords);
	$babBody->babecho(	bab_printTemplate($temp,"fileman.html", "addfile"));
}



function createDirectory($dirname, $id, $gr, $path)
	{
	global $babBody, $BAB_SESS_USERID;

	if( empty($dirname))
		{
		$babBody->msgerror = bab_translate("Please give a valid directory name");
		return false;
		}

	$dirname = trim($dirname);

	if( false !== strstr($dirname, '..'))
		{
		$babBody->msgerror = bab_translate("Access denied");
		return false;
		}
	
	$bOk = false;
	switch($gr)
		{
		case "N":
			if( $gr == "N" && $BAB_SESS_USERID == $id && $babBody->ustorage )
				$bOk = true;
			break;
		case "Y":
			for( $i = 0; $i < count($babBody->aclfm['id']); $i++ )
			{
			if( $babBody->aclfm['id'][$i] == $id && $babBody->aclfm['ma'][$i] == 1 )
				{
				$bOk = true;
				break;
				}
			}
			break;
		}

	if( !$bOk )
		{
		$babBody->msgerror = bab_translate("You don't have permission to create directory");
		return false;
		}



	if( isset($GLOBALS['babFileNameTranslation']))
		$dirname = strtr($dirname, $GLOBALS['babFileNameTranslation']);

	$pathx = bab_getUploadFullPath($gr, $id, $path).$dirname;

	if( is_dir($pathx))
		{
		$babBody->msgerror = bab_translate("This folder already exists");
		return false;
		}
	else
		{
		bab_mkdir($pathx, $GLOBALS['babMkdirMode']);
		}
		return $bOk;
	}

function renameDirectory($dirname, $id, $gr, $path)
	{
	global $babBody, $babDB, $BAB_SESS_USERID, $aclfm;
	if( empty($path))
		return false;

	if( empty($dirname))
		{
		$babBody->msgerror = bab_translate("Please give a valid directory name");
		return false;
		}

	if( false !== strstr($dirname, '..'))
		{
		$babBody->msgerror = bab_translate("Access denied");
		return false;
		}

	$bOk = false;
	switch($gr)
		{
		case "N":
			if( $gr == "N" && $BAB_SESS_USERID == $id && $babBody->ustorage )
				$bOk = true;
			break;
		case "Y":
			for( $i = 0; $i < count($babBody->aclfm['id']); $i++ )
			{
			if( $babBody->aclfm['id'][$i] == $id && $babBody->aclfm['ma'][$i] == 1 )
				{
				$bOk = true;
				break;
				}
			}
			break;
		}

	if( !$bOk )
		{
		$babBody->msgerror = bab_translate("You don't have permission to rename directory");
		return false;
		}

	$pathx = bab_getUploadFullPath($gr, $id);

	if( $pos = strrpos($path, "/"))
		{
		$oldname = substr($path, -(strlen($path) - $pos - 1));
		$uppath = substr($path, 0, $pos)."/";
		}
	else
		{
		$uppath = "";
		$oldname = $path;
		}

	if( isset($GLOBALS['babFileNameTranslation']))
		$dirname = strtr($dirname, $GLOBALS['babFileNameTranslation']);

	if( is_dir($pathx.$uppath.$dirname))
		{
		$babBody->msgerror = bab_translate("This folder already exists");
		return false;
		}
	else
		{
		if(rename($pathx.$uppath.$oldname, $pathx.$uppath.$dirname))
			{
			$len = strlen($path);
			$req = "select * from ".BAB_FILES_TBL." where id_owner='".$babDB->db_escape_string($id)."' and bgroup='".$babDB->db_escape_string($gr)."'";
			$res = $babDB->db_query($req);
			while( $arr = $babDB->db_fetch_array($res))
				{
				if( substr($arr['path'], 0, $len) == $path )
					{
					$req = "update ".BAB_FILES_TBL." set path='".$babDB->db_escape_string(str_replace($path, $uppath.$dirname, $arr['path']))."' where id='".$babDB->db_escape_string($arr['id'])."'";
					$babDB->db_query($req);
					}
				}
			$GLOBALS['path'] = $uppath.$dirname;
			}
		else
			{
			$babBody->msgerror = bab_translate("Cannot rename directory");
			return false;
			}
		}
	}

function removeDirectory($id, $gr, $path)
	{
	global $babBody, $babDB, $BAB_SESS_USERID, $aclfm;
	if( empty($path))
		return false;

	$bOk = false;
	switch($gr)
		{
		case "N":
			if( $gr == "N" && $BAB_SESS_USERID == $id && $babBody->ustorage )
				$bOk = true;
			break;
		case "Y":
			for( $i = 0; $i < count($babBody->aclfm['id']); $i++ )
			{
			if( $babBody->aclfm['id'][$i] == $id && $babBody->aclfm['ma'][$i] == 1 )
				{
				$bOk = true;
				break;
				}
			}
			break;
		}

	if( !$bOk )
		{
		$babBody->msgerror = bab_translate("You don't have permission to remove directory");
		return false;
		}

	$pathx = bab_getUploadFullPath($gr, $id);

	if( is_dir($pathx.$path))
		{
		$req = "select * from ".BAB_FILES_TBL." where id_owner='".$babDB->db_escape_string($id)."' and bgroup='".$babDB->db_escape_string($gr)."' and path='".$babDB->db_escape_string($path)."'";
		$res = $babDB->db_query($req);
		while( $arr = $babDB->db_fetch_array($res))
			{
			if( @unlink($pathx.$path."/".$arr['name']))
				deleteFile($arr['id'], $arr['name'], $pathx.$path."/");
			}

		if( $pos = strrpos($path, "/"))
			$uppath = substr($path, 0, $pos);
		else
			$uppath = "";
		$GLOBALS['path'] = $uppath;

		$ret = true;
		if( is_dir($pathx.$path."/".BAB_FVERSION_FOLDER."/"))
			{
			if(!@rmdir($pathx.$path."/".BAB_FVERSION_FOLDER."/"))
				$ret = false;
			}

		if($ret && !@rmdir($pathx.$path))
			$ret = false;

		if( $ret == false )
			{
			$babBody->msgerror = bab_translate("Cannot remove directory");
			return false;
			}
		}
	}

function getFile($file, $id, $gr, $path)
	{
	global $babBody, $babDB, $BAB_SESS_USERID;
	$access = false;
	
	$inl = bab_rp('inl', false);
	if(false === $inl) 
	{
		$inl = bab_getFileContentDisposition() == 1 ? 1 : '';
	}

	$iIdOwner = $id;
	$sPathName = $path;
	
	if($gr == "N" && $babBody->ustorage)
	{
		$access = true;
		$sPathName = BAB_FmFolderHelper::getUserDirUploadPath($id) . $path . '/';
	}
	else if($gr == "Y")
	{
		for($i = 0; $i < count($babBody->aclfm['id']); $i++)
		{
			if($babBody->aclfm['id'][$i] == $id && ($babBody->aclfm['down'][$i] || $babBody->aclfm['ma'][$i]))
			{
				$access = true;
				break;
			}	
		}
			
		if(true === $access)
		{
			$access = BAB_FmFolderHelper::getFileInfoForCollectiveDir($id, $path, $iIdOwner, $sPathName);
		}
	}

	
	if( $access )
		{
		$file = stripslashes($file);
		$req = "select * from ".BAB_FILES_TBL." where id_owner='".$babDB->db_escape_string($iIdOwner)."' and bgroup='".$babDB->db_escape_string($gr)."' and path='".$babDB->db_escape_string($sPathName)."' and name='".$babDB->db_escape_string($file)."'";
		$res = $babDB->db_query($req);
		if( $res && $babDB->db_num_rows($res) > 0 )
			{
			$arr = $babDB->db_fetch_array($res);
			if( $arr['state'] == '')
				{
				$babDB->db_query("update ".BAB_FILES_TBL." set hits='".$babDB->db_escape_string(($arr['hits'] + 1))."' where id='".$babDB->db_escape_string($arr['id'])."'");
				$access = true;
				}
			else
				{
//echo 'iIdOwner ==> ' . $iIdOwner . ' sPathName ==> ' . $sPathName;
				$access = false;
				}
			}
		else
			{
			$access = false;
			}
		}

	if( !$access )
		{
		echo bab_translate("Access denied");
		return;
		}

	$GLOBALS['babWebStat']->addFilesManagerFile($arr['id']);
	$mime = bab_getFileMimeType($file);
	$fullpath = bab_getUploadFullPath($gr, $id);
	if( !empty($path))
		$fullpath .= $path."/";

	$fullpath .= $file;
	
	if (file_exists($fullpath)) {
	
		$fsize = filesize($fullpath);
		if( strtolower(bab_browserAgent()) == "msie")
			header('Cache-Control: public');
		if( $inl == "1" )
			header("Content-Disposition: inline; filename=\"$file\""."\n");
		else
			header("Content-Disposition: attachment; filename=\"$file\""."\n");
		header("Content-Type: $mime"."\n");
		header("Content-Length: ". $fsize."\n");
		header("Content-transfert-encoding: binary"."\n");
		$fp=fopen($fullpath,"rb");
		if ($fp) {
			while (!feof($fp)) {
				print fread($fp, 8192);
				}
			fclose($fp);
			exit;
			}
		}
		else {
			$babBody->msgerror = bab_translate("The file is not on the server");
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
		$sRelativePath = BAB_FmFolderHelper::getUserDirUploadPath($id) . $path . '/';
	}
	else 
	{
		BAB_FmFolderHelper::getFileInfoForCollectiveDir($id, $path, $iIdOwner, $sRelativePath);
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
		$sRelativePath = BAB_FmFolderHelper::getUserDirUploadPath($id) . $path . '/';
	}
	else 
	{
		BAB_FmFolderHelper::getFileInfoForCollectiveDir($id, $path, $iIdOwner, $sRelativePath);
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
		$sOldRelativePath = BAB_FmFolderHelper::getUserDirUploadPath($id) . $path . '/';
		$sNewRelativePath = BAB_FmFolderHelper::getUserDirUploadPath($id) . $tp . '/';
	}
	else 
	{
		BAB_FmFolderHelper::getFileInfoForCollectiveDir($id, $path, $iOldIdOwner, $sOldRelativePath);
		BAB_FmFolderHelper::getFileInfoForCollectiveDir($id, $tp, $iNewIdOwner, $sNewRelativePath);
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
			global $babDB;
			$this->access = $access;
			if($access)
			{
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
				$this->path = bab_toHtml($oFmFolder->getRelativePath());
				$this->file = bab_toHtml($oFolderFile->getName());
				$GLOBALS['babBody']->setTitle($oFolderFile->getName() .( ($bversion == 'Y') ? ' (' . $oFolderFile->getMajorVer() . '.' . $oFolderFile->getMinorVer() . ')' : '' ));
				$this->descval = $oFolderFile->getDescription();
				$this->keysval = $oFolderFile->getKeywords();
				$this->descvalhtml = bab_toHtml($oFolderFile->getDescription());
				$this->keysvalhtml = bab_toHtml($oFolderFile->getKeywords());

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


//				$rr = $babDB->db_fetch_array($babDB->db_query("select filenotify, version from ".BAB_FM_FOLDERS_TBL." where id='".$babDB->db_escape_string($arr['id_owner'])."'"));

				if('Y' === $oFmFolder->getVersioning()) 
				{
					$this->versions = true;
				} 
				else
				{
					$this->versions = false;
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
				else
				{
					$this->countff = 0;
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
		if('N' === $oFolderFile->getGroup())
		{
			if($babBody->ustorage && $BAB_SESS_USERID == $oFolderFile->getOwnerId())
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
				if(count($arrschi) > 0 && in_array($oFolderFile->getApprobationInstanceId(), $arrschi))
				{
					$bconfirm = true;
				}
			}

			for($i = 0; $i < count($babBody->aclfm['id']); $i++)
			{
				if($babBody->aclfm['id'][$i] == $oFolderFile->getOwnerId())
				{
					$access = true;
                    if($babBody->aclfm['down'][$i])
                    {
                        $bdownload = true;
                    }

					if($babBody->aclfm['ma'][$i] == 1 && !empty($BAB_SESS_USERID))
					{
						$bmanager = true;
						$bupdate = true;
					}
					else if($babBody->aclfm['upda'][$i])
					{
						$bupdate = true;
					}
					break;
				}
			}
			
			if($bconfirm)
			{
				$bupdate = false;
				$bmanager = false;
			}
			
			$oFmFolderSet = new BAB_FmFolderSet();
			$oId =& $oFmFolderSet->aField['iId'];
			
			$oFmFolder = $oFmFolderSet->get($oId->in($oFolderFile->getOwnerId()));
			if(!is_null($oFmFolder))
			{
				if(0 !== $oFolderFile->getFolderFileVersionId() || $bversion ==  'Y')
				{
					$bupdate = false;
				}
			}
		}
	}

	$temp = new temp($oFmFolder, $oFolderFile, $id, $path, $bmanager, $access, $bconfirm, $bupdate, $bdownload,$bversion);
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
	$babBody->addItemMenu("displayRightForm", bab_translate("Right"), $GLOBALS['babUrlScript']."?tg=fileman&idx=displayRightForm&id=".$id."&gr=".$gr."&path=".urlencode($path) . 
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

function deleteFiles($items, $gr, $id)
{
	$sUploadPath = BAB_FmFolderHelper::getUploadPath();
	
	$oFolderFileSet = new BAB_FolderFileSet();
	$oId =& $oFolderFileSet->aField['iId'];
	
	for($i = 0; $i < count($items); $i++)
	{
		$oFolderFile = $oFolderFileSet->get($oId->in($items[$i]));
		if(!is_null($oFolderFile))
		{
			if(file_exists($sUploadPath . $oFolderFile->getPathName() . $oFolderFile->getName()))
			{
				unlink($sUploadPath . $oFolderFile->getPathName() . $oFolderFile->getName());
			}
		}
		deleteFile($items[$i], $oFolderFile->getName(), $sUploadPath . $oFolderFile->getPathName());
	}
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

	
function displayFolderForm($bCollective, $sPath, $iId)
{
	global $babBody;
	$sGr = (($bCollective) ? 'Y' : 'N');
	
	$babBody->addItemMenu("list", bab_translate("Folders"), $GLOBALS['babUrlScript']."?tg=fileman&idx=list&id=".$iId."&gr=".$sGr."&path=".urlencode($sPath));
	$babBody->addItemMenu('displayFolderForm', bab_translate("Create a folder"), $GLOBALS['babUrlScript']."?tg=fileman&idx=displayFolderForm&id=".$iId."&gr=".$sGr."&path=".urlencode($sPath));
	
	if('Y' === $sGr && BAB_FmFolderHelper::accessValidForCollectiveDir($iId))
	{
		$oDspFldForm = new DisplayCollectiveFolderForm($sGr, $sPath, $iId);
		$babBody->babecho($oDspFldForm->printTemplate());
	}
	else if('N' === $sGr && BAB_FmFolderHelper::accessValidForUserDir($iId))
	{
		$oDspFldForm = new DisplayUserFolderForm($sGr, $sPath, $iId);
		$babBody->babecho($oDspFldForm->printTemplate());
	}
	else 
	{
		$babBody->msgerror = bab_translate("Access denied");
	}
}


function createEditFolder($bManager, $bUpload, $bCollective, $sPath, $iId, &$sIdx)
{
	global $babBody;
	$sGr = (($bCollective) ? 'Y' : 'N');
	
	if('Y' === $sGr)
	{
		createEditFolderForCollectiveDir($iId, $sPath);
		$sIdx = 'list';
		listFiles($iId, $sGr, $sPath, $bManager, $bUpload);
	}
	else if('N' === $sGr)
	{
		createEditFolderForUserDir($iId, $sPath);
		$sIdx = 'list';
		listFiles($iId, $sGr, $sPath, $bManager, $bUpload);
	}
	else 
	{
		$babBody->msgerror = bab_translate("Access denied");
	}
}


function createEditFolderForUserDir($iIdUser, $sPath)
{
	global $babBody;
	
	if(BAB_FmFolderHelper::accessValidForUserDir($iIdUser))
	{
		$sAction		= (string) bab_pp('sAction', '');
		$sType			= (string) bab_pp('sType', '');
		$sDirName		= (string) bab_pp('sDirName', '');
		$sOldDirName	= (string) bab_pp('sOldDirName', '');

		if(strlen(trim($sDirName)) > 0)
		{
			$sUplaodPath = BAB_FmFolderHelper::getUploadPath();
			$sUserDirPath = BAB_FmFolderHelper::getUserDirUploadPath($iIdUser);
			
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
				$sRelativePath = $sUserDirPath;
				if(strlen(trim($sPath)) > 0)
				{
					$sRelativePath .= $sPath;
				}
								
//				bab_debug('sUplaodPath ==> ' . $sUplaodPath);
//				bab_debug('sRelativePath ==> ' . $sRelativePath);
//				bab_debug('sOldDirName ==> ' . $sOldDirName);
//				bab_debug('sDirName ==> ' . $sDirName);

				BAB_FmFolderHelper::renameDirectory($sUplaodPath, $sRelativePath, $sOldDirName, $sDirName);
				
				bab_debug('Ne pas oublier de mettre à jour la table BAB_FMFILES');
			}
			else 
			{
				$babBody->addError(bab_translate("Unhandled action"));
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
	
	if(BAB_FmFolderHelper::accessValidForCollectiveDir($iIdFolder))
	{
		$sAction				= (string) bab_pp('sAction', '');
		$sType					= (string) bab_pp('sType', '');
		$sDirName				= (string) bab_pp('sDirName', '');
		$sActive				= (string) bab_pp('sActive', 'Y');
		$iIdApprobationScheme	= (int) bab_pp('iIdApprobationScheme', 0);
		$sAutoApprobation		= (string) bab_pp('sAutoApprobation', 'N');
		$sNotification			= (string) bab_pp('sNotification', 'N');
		$sVersioning			= (string) bab_pp('sVersioning', 'N');
		$sDisplay				= (string) bab_pp('sDisplay', 'N');
		$sPathName				= (string) '';
		
		$sRelativePath = '';
		$oFmFolder = BAB_FmFolderHelper::getFmFolderById($iIdFolder);
		if(!is_null($oFmFolder))
		{
			$sRelativePath = $oFmFolder->getName() . '/';
			if(strlen(trim($sPath)) > 0)
			{
				$sRelativePath =  $sRelativePath . $sPath . '/';
			}
			
			if(strlen(trim($sDirName)) > 0)
			{
				$sUploadPath = BAB_FmFolderHelper::getUploadPath();
				
				if('createFolder' === $sAction)
				{
					$sFullPathName = $sUploadPath . $sRelativePath . $sDirName;

//					bab_debug('sUploadPath ==> ' . $sUploadPath);
//					bab_debug('sFullPathName ==> ' .  $sFullPathName);
//					bab_debug('sRelativePath ==> ' . $sRelativePath);

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
					$iIdFld						= (int) bab_pp('iIdFolder', 0); 
					$sPathName					= '';			
					
//					$oCommandProcessor			= new BAB_CommandProcessor();
					
					$bFolderRenamed				= false;
					$bIsCollectiveFolder		= false;
					
					//Peut être faudrait-il un objet context
					
					$oFmFolder = BAB_FmFolderHelper::getFmFolderById($iIdFld);
					if(!is_null($oFmFolder))
					{
						$bIsFolderRenamed		= ($sDirName !== $oFmFolder->getName()) ? true : false;
						$sOldDirName			= $oFmFolder->getName();
						$sRelativePath			= $oFmFolder->getRelativePath();
						
						$bIsCollectiveFolder	= true;
						
						
						//collectiveToSimple
						if('simple' === $sType)
						{
							//changer les iIdOwner
							//supprimer les droits
							//supprimer les versions de fichiers
							//supprimer les instances de schémas d'approbations
							
							$sParentPath = $sRelativePath;
							BAB_FolderFileHelper::setIdOwnerToFirstCollective($sParentPath, $iIdFld);


//							aclDeleteGroup(BAB_FMUPLOAD_GROUPS_TBL, $iIdFld);
//							aclDeleteGroup(BAB_FMDOWNLOAD_GROUPS_TBL, $iIdFld);
//							aclDeleteGroup(BAB_FMUPDATE_GROUPS_TBL, $iIdFld);
//							aclDeleteGroup(BAB_FMMANAGERS_GROUPS_TBL, $iIdFld);


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
						}
					}

					
					
//					bab_debug('sUploadPath ==> ' . $sUploadPath);
//					bab_debug('sRelativePath ==> ' . $sRelativePath);
//					bab_debug('sOldDirName ==> ' . $sOldDirName);
//					bab_debug('sDirName ==> ' . $sDirName);

return;
					if($bRename)
					{
						if(strlen(trim($sOldDirName)) > 0)
						{
							/*
							$oCommandProcessor->add(
								new BAB_RenameFolderCommand(
									new BAB_RenameFolderContext($sUploadPath, $sRelativePath, $sOldDirName, $sDirName)));
									
							$oCommandProcessor->add(
								new BAB_RenameFilePathCommand(
									new BAB_RenameFilePathContext($sRelativePath . $sOldDirName . '/', $sDirName)));
							//*/
									
//							BAB_FmFolderHelper::updateSubFolderPathName($sUploadPath, $sRelativePath, $sOldDirName, $sDirName);
//							BAB_FolderFileHelper::renamePath($sRelativePath . $sOldDirName . '/', $sDirName);
							
/*
if(strlen(trim($sRelativePath)) > 0)
{
	bab_debug('Il faut être plus précis');
	
	$oFmFileSet = new BAB_FolderFileSet();
	$oPathName =& $oFmFolderSet->aField['sPathName']; 
	$oFmFileSet->select($oPathName->like($babDB->db_escape_like($sPathName) . '%'));
	while(null !== ($oFile = $oFmFileSet->next()))
	{
		bab_debug($oFile);
	}
}
//*/
							
						}
						else 
						{
							bab_debug(__FUNCTION__ . ' ERROR invalid sOldDirName');
						}
					}
		
					/*
					$oCommandProcessor->execute();
					//*/
					
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
}

					//Pour les fichiers
//					$oParentFolder = BAB_FmFolderHelper::getFirstCollectiveFolder($sRelativePath);
//					if(!is_null($oParentFolder))
//					{
//						bab_debug('Nuclear launch detected');		
//						bab_debug($oParentFolder);		
//					}
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


/* main */

$idx = bab_rp('idx','list');
$path = bab_rp('path');
$gr = bab_rp('gr', 'N');
$editor = bab_rp('editor','none');

$upload = false;
$bmanager = false;
$access = false;
bab_fileManagerAccessLevel();

if((!isset($babBody->aclfm['id']) || count($babBody->aclfm['id']) == 0) && !$babBody->ustorage )
{
	$babBody->msgerror = bab_translate("Access denied");
	if ($idx == "brow") die(bab_translate("Access denied"));
	return;
}


if( false !== strstr($path, '..'))
	{
	$babBody->msgerror = bab_translate("Access denied");
	return;
	}

if( !empty($BAB_SESS_USERID) && $babBody->ustorage)
	{
	$id = bab_rp('id', $BAB_SESS_USERID);
	}
else
	{
	$id = bab_rp('id', 0);
	}




if( $gr == "N" && !empty($BAB_SESS_USERID) && $BAB_SESS_USERID == $id )
	{
	if( $babBody->ustorage )
		{
		$upload = true;
		$bmanager = true;
		}
	}

if( $gr == "Y")
	{
	for( $i = 0; $i < count($babBody->aclfm['id']); $i++)
		{
		if( $babBody->aclfm['id'][$i] == $id )
			{
			if( $babBody->aclfm['ma'][$i] == 1 )
				{
				$bmanager = true;
				$upload = true;
				}

			if( $babBody->aclfm['uplo'][$i] )
				$upload = true;
			break;
			}
		}
	if( $id != 0 && $i >= count($babBody->aclfm['id']))
		{
			$babBody->msgerror = bab_translate("Access denied");
			return;
		}
	}


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

if('mkdir' === bab_pp('mkdir'))
	{
	if( !empty($create)) {
		createDirectory(bab_pp('dirname'), $id, $gr, $path);
		}
	else if(!empty($rename)) {
		renameDirectory(bab_pp('dirname'), $id, $gr, $path);
		}
	else if(!empty($bdel)) {
		removeDirectory($id, $gr, $path);
		}
	}

if( $idx == "paste")
	{
	if( pasteFile(bab_gp('file'), $id, $gr, $path, bab_gp('tp'), $bmanager))
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
		deleteFiles(bab_rp('items'), $gr, $id);
		}
	else {
		restoreFiles(bab_rp('items'));
		}
}


switch($idx)
	{
	case 'displayFolderForm':
		displayFolderForm((($gr == 'N') ? false : true), $path, $id);
		break;
		
	case 'createEditFolder':
		createEditFolder($bmanager, $upload, (($gr == 'N') ? false : true), $path, $id, $idx);
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
		listFiles($id, $gr, $path, $bmanager, $upload);
		break;
	}
$babBody->setCurrentItemMenu($idx);
?>