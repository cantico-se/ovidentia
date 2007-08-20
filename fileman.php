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

	if( is_dir($path.BAB_FVERSION_FOLDER."/"))
		{
		$res = $babDB->db_query("select * from ".BAB_FM_FILESVER_TBL." where id_file='".$babDB->db_escape_string($idf)."'");
		while($arr = $babDB->db_fetch_array($res))
			{
			if( file_exists($path.BAB_FVERSION_FOLDER."/".$arr['ver_major'].",".$arr['ver_minor'].",".$name))
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
	
	/**
	 * Files extracted by readdir
	 */
	var $files_from_dir = array();

	function listFiles($id, $gr, $path, $bmanager, $what ="list")
		{
		global $babBody, $babDB, $BAB_SESS_USERID;
		include_once $GLOBALS['babInstallPath']."utilit/afincl.php";
		$this->fullpath = bab_getUploadFullPath($gr, $id);
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
					$arrschi = bab_getWaitingIdSAInstance($GLOBALS['BAB_SESS_USERID']);
					if( count($arrschi) > 0 )
						{
						$req = "
						SELECT 
							f.* 
						FROM 
							".BAB_FILES_TBL." f 
						WHERE 
							id_owner='".$babDB->db_escape_string($id)."' 
							AND bgroup='".$babDB->db_escape_string($gr)."' 
							AND state='' and path='".$babDB->db_escape_string($path)."' 
							AND confirmed='N' 
							AND f.idfai IN (".$babDB->quote($arrschi).")
						";
						$this->reswf = $babDB->db_query($req);
						$this->countwf = $babDB->db_num_rows($this->reswf);
						}
					else
						{
						$this->countwf = 0;
						}
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
		
		
		function prepare() {
			global $babDB;
		
			$req = " 
			SELECT * FROM 
				".BAB_FILES_TBL." 
			WHERE 
				id_owner='".$babDB->db_escape_string($this->id)."' 
				AND bgroup='".$babDB->db_escape_string($this->gr)."' 
				AND state='' 
				AND path='".$babDB->db_escape_string($this->path)."' 
				AND confirmed='Y'
			";
			$req .= ' ORDER BY name asc';
			$this->res = $babDB->db_query($req);
			$this->count = $babDB->db_num_rows($this->res);
		}


		/** 
		 * if there is file not presents in database, add and recreate $this->res
		 */
		function autoadd_files() {
			
			global $babDB;

			if( !isset($GLOBALS['babAutoAddFilesAuthorId']) || empty($GLOBALS['babAutoAddFilesAuthorId']))
				{
				return;
				}
		
			$res = $babDB->db_query('select id from '.BAB_USERS_TBL.' where id='.$babDB->quote($GLOBALS['babAutoAddFilesAuthorId']));
			if( 0 == $babDB->db_num_rows($res) )
				{
					return;
				}

			if ($this->count < count($this->files_from_dir)) {
			
				bab_debug($this->files_from_dir);
				
				foreach($this->files_from_dir as $dir_file) {
				
					$res = $babDB->db_query('
						SELECT id FROM '.BAB_FILES_TBL.' 
						WHERE 
							id_owner = '.$babDB->quote($this->id).' 
							AND path = '.$babDB->quote($this->path).' 
							AND bgroup = '.$babDB->quote($this->gr).' 
							AND name = '.$babDB->quote($dir_file).'
					');
					
					if (0 == $babDB->db_num_rows($res)) {
						$babDB->db_query("
							INSERT INTO ".BAB_FILES_TBL." 
								(
								name, 
								path, 
								id_owner, 
								bgroup, 
								created, 
								author, 
								modified, 
								modifiedby, 
								confirmed
							) 
							VALUES (
								'".$babDB->db_escape_string($dir_file)."',
								'".$babDB->db_escape_string($this->path)."',
								'".$babDB->db_escape_string($this->id)."',
								".$babDB->quote($this->gr).",
								NOW(),
								'".$babDB->db_escape_string($GLOBALS['babAutoAddFilesAuthorId'])."',
								NOW(),
								'".$babDB->db_escape_string($GLOBALS['babAutoAddFilesAuthorId'])."',
								'Y'
							)
						");	
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
	function DisplayCollectiveFolderForm($sGr, $sPath, $iId)
	{
		parent::DisplayFolderFormBase($sGr, $sPath, $iId);
		
		$this->setCaption();
		$this->set_data('sYes', 'Y');
		$this->set_data('sNo', 'N');
		$this->set_data('iNone', 0);
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
		$this->set_data('isColletive', false);
		$this->set_data('isActive', true);
		$this->set_data('isAutoApprobation', false);
		$this->set_data('isFileNotify', false);
		$this->set_data('isVersioning', false);
		$this->set_data('isShow', true);
	}
	
	function handleEdition()
	{
		$this->set_data('isColletive', false);
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
		$oFmFolder = BAB_FmFolderSet::get(array(new BAB_InCriterion('iId', $iId)));
		if(!is_null($oFmFolder))
		{
			$sRelativePath = $oFmFolder->getPathName();
			if(strlen(trim($sPath)) > 0)
			{
				$sRelativePath .=  '/' . $sPath;
			}
		}
		
		$sPathName = $sRelativePath . '/' . $sDirName;

		$aCriterion[] = new BAB_LikeCriterion('sPathName', $sPathName, 0);
		$oFmFolder = BAB_FmFolderSet::get($aCriterion);
		if(!is_null($oFmFolder))
		{
			$this->set_data('isColletive', true);
			$this->set_data('isActive', ('Y' === $oFmFolder->getActive()) ? true : false);
			$this->set_data('isAutoApprobation', ('Y' === $oFmFolder->getAutoApprobation()) ? true : false);
			$this->set_data('isFileNotify', ('Y' === $oFmFolder->getFileNotify()) ? true : false);
			$this->set_data('isVersioning', ('Y' === $oFmFolder->getVersioning()) ? true : false);
			$this->set_data('isShow', ('Y' === $oFmFolder->getHide()) ? false : true);
			$this->set_data('iIdFolder', $oFmFolder->getId());
			$this->set_data('sOldDirName', $oFmFolder->getName());
		}
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


function listTrashFiles($id, $gr)
	{
	global $babBody;

	class temp
		{
		var $db;
		var $count;
		var $res;
		var $arrext = array();
		var $idfile;
		var $delete;
		var $restore;
		var $nametxt;
		var $modifiedtxt;
		var $sizetxt;
		var $postedtxt;


		function temp($id, $gr)
			{
			global $babDB;
			$this->id = $id;
			$this->gr = $gr;
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
			$req = "select * from ".BAB_FILES_TBL." where id_owner='".$babDB->db_escape_string($id)."' and bgroup='".$babDB->db_escape_string($gr)."' and state='D' order by name asc";
			$this->res = $babDB->db_query($req);
			$this->count = $babDB->db_num_rows($this->res);
			}

		function getnextfile()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $babDB->db_fetch_array($this->res);
				$ext = substr(strrchr($arr['name'], "."), 1);
				if( empty($this->arrext[$ext]))
					$this->arrext[$ext] = bab_printTemplate($this, "config.html", ".".$ext);
				if( empty($this->arrext[$ext]))
					$this->arrext[$ext] = bab_printTemplate($this, "config.html", ".unknown");				
				$this->fileimage = $this->arrext[$ext];
				$this->name = bab_toHtml($arr['name']);
				$this->idfile = $arr['id'];
				if( file_exists($this->fullpath.$arr['path']."/".$arr['name']))
					{
					$fstat = stat($this->fullpath.$arr['path']."/".$arr['name']);
					$this->sizef = $fstat[7];
					}
				else
					$this->sizef = "???";

				$this->modified = bab_toHtml(bab_shortDate(bab_mktime($arr['modified']), true));
				$this->postedby = bab_toHtml(bab_getUserName($arr['modifiedby'] == 0? $arr['author']: $arr['modifiedby']));
				$i++;
				return true;
				}
			else
				return false;
			}

		}

	$temp = new temp($id, $gr);
	$babBody->babecho(	bab_printTemplate($temp,"fileman.html", "trashfiles"));
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
				$oFmFolder = BAB_FmFolderSet::get(array(new BAB_InCriterion('iId', $this->arrgrp[$i])));
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
				$oFmFolder = BAB_FmFolderSet::get(array(new BAB_InCriterion('iId', $this->arrmgrp[$i])));
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
				$oFmFolder = BAB_FmFolderSet::get(array(new BAB_InCriterion('iId', $id)));
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
				$req = "select * from ".BAB_FILES_TBL." where id_owner='".$babDB->db_escape_string($id)."' and bgroup='".$babDB->db_escape_string($gr)."' and state='X' order by name asc";
				$this->xres = $babDB->db_query($req);
				$this->xcount = $babDB->db_num_rows($this->xres);
				if( !empty($path) && count($this->arrdir) <= 1 && $this->count == 0 )
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
			if( $i < count($this->arrdir))
				{
				$this->altbg = !$this->altbg;
				$this->name = bab_toHtml($this->arrdir[$i]);
				$this->bFolderFormUrl = false;
				static $aExcludedDir = array('.', '..', '. .');
				if(!in_array($this->name, $aExcludedDir))	
				{	
					
//bab_debug('sPathName ==> ' . $this->path . '/' . $this->name);

					$this->sFolderFormUrl = bab_toHtml($GLOBALS['babUrlScript'] . '?tg=fileman&idx=displayFolderForm&sAction=editFolder&id=' . $this->id . 
						'&gr=' . $this->gr . '&path=' . urlencode($this->path) . '&sDirName=' . urlencode($this->name));
					$this->bFolderFormUrl = true;
				}

				$this->url = bab_toHtml($this->arrudir[$i]);
				$i++;
				return true;
				}
			else
				return false;
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
			
			if( file_exists($this->fullpath.$arr['path']."/".$arr['name']))
				{
				$fstat = stat($this->fullpath.$arr['path']."/".$arr['name']);
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
				$upath = urlencode($arr['path']);
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
			if( $GLOBALS['babMaxFileSize'] < 1000000 )
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
			if( $gr == 'Y' )
				{
				$this->res = $babDB->db_query("select * from ".BAB_FM_FIELDS_TBL." where id_folder='".$babDB->db_escape_string($id)."'");
				$this->count = $babDB->db_num_rows($this->res);
				}
			else
				$this->count = 0;
			}
		

		function getnextfield()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $babDB->db_fetch_array($this->res);
				$this->fieldname = bab_translate($arr['name']);
				$this->field = 'field'.$arr['id'];
				$this->fieldval = bab_toHtml($arr['defaultval']);
				$i++;
				return true;
				}
			else
				return false;
			}


		}

	$access = false;
	if( $gr == "N" && !empty($BAB_SESS_USERID))
		{
		if( $babBody->ustorage ) 
			{
			$access = true;
			}
		}

	if( $gr == "Y" && !empty($BAB_SESS_USERID))
		{
		for( $i = 0; $i < count($babBody->aclfm['id']); $i++)
			{
			if( $babBody->aclfm['id'][$i] == $id && ($babBody->aclfm['uplo'][$i] || $babBody->aclfm['ma'][$i] == 1))
				{
				$access = true;
				break;
				}
			}
		}

	if( !$access )
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

function getFile( $file, $id, $gr, $path)
	{
	global $babBody, $babDB, $BAB_SESS_USERID;
	$access = false;
	
	$inl = bab_rp('inl', false);
	if (false === $inl) {
		$inl = bab_getFileContentDisposition() == 1? 1: '';
	}

	if( $gr == "N" && $babBody->ustorage)
		{
		$access = true;
		}

	if( $gr == "Y" )
		{
		for( $i = 0; $i < count($babBody->aclfm['id']); $i++)
			{
			if( $babBody->aclfm['id'][$i] == $id && ($babBody->aclfm['down'][$i] || $babBody->aclfm['ma'][$i]))
				{
				$access = true;
				break;
				}	
			}
		}

	if( $access )
		{
		$file = stripslashes($file);
		$req = "select * from ".BAB_FILES_TBL." where id_owner='".$babDB->db_escape_string($id)."' and bgroup='".$babDB->db_escape_string($gr)."' and path='".$babDB->db_escape_string($path)."' and name='".$babDB->db_escape_string($file)."'";
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


function cutFile( $file, $id, $gr, $path, $bmanager)
	{
	global $babBody, $babDB;

	if( !$bmanager)
		{
		$babBody->msgerror = bab_translate("Access denied");
		return false;
		}
	$req = "update ".BAB_FILES_TBL." set state='X' where id_owner='".$babDB->db_escape_string($id)."' and bgroup='".$babDB->db_escape_string($gr)."' and state='' and path='".$babDB->db_escape_string($path)."' and name='".$babDB->db_escape_string($file)."'";
	$res = $babDB->db_query($req);
	return true;
	}

function delFile( $file, $id, $gr, $path, $bmanager)
	{
	global $babBody, $babDB;

	if( !$bmanager)
		{
		$babBody->msgerror = bab_translate("Access denied");
		return false;
		}


	$path = $babDB->db_escape_string($path);
	$file = $babDB->db_escape_string($file);
	
	$db = $GLOBALS['babDB'];
	$req = "update ".BAB_FILES_TBL." set state='D' where id_owner='".$babDB->db_escape_string($id)."' and bgroup='".$babDB->db_escape_string($gr)."' and state='' and path='".$babDB->db_escape_string($path)."' and name='".$babDB->db_escape_string($file)."'";
	$res = $babDB->db_query($req);
	return true;
	}

function pasteFile( $file, $id, $gr, $path, $tp, $bmanager)
	{
	global $babBody, $babDB;

	if( !$bmanager)
		{
		$babBody->msgerror = bab_translate("Access denied");
		return false;
		}

	$file = stripslashes($file);
	$tp = stripslashes($tp);
	$pathx = bab_getUploadFullPath($gr, $id);
	if( file_exists($pathx.$tp."/".$file))
		{
		if( $path == $tp )
			{
			$req = "update ".BAB_FILES_TBL." set state='' where id_owner='".$babDB->db_escape_string($id)."' and bgroup='".$babDB->db_escape_string($gr)."' and path='".$babDB->db_escape_string($path)."' and name='".$babDB->db_escape_string($file)."'";
			$res = $babDB->db_query($req);
			return true;
			}
		$babBody->msgerror = bab_translate("A file with the same name already exists");
		return false;
		}

	if( rename( $pathx.$path."/".$file, $pathx.$tp."/".$file))
		{
		$db = $GLOBALS['babDB'];
		list($idf) = $babDB->db_fetch_row($babDB->db_query("select id from ".BAB_FILES_TBL." where id_owner='".$babDB->db_escape_string($id)."' and bgroup='".$babDB->db_escape_string($gr)."' and path='".$babDB->db_escape_string($path)."' and name='".$babDB->db_escape_string($file)."'"));

		$babDB->db_query("update ".BAB_FILES_TBL." set state='', path='".$babDB->db_escape_string($tp)."' where id='".$babDB->db_escape_string($idf)."'");

		if( is_dir($pathx.$path."/".BAB_FVERSION_FOLDER."/"))
			{
			if( !is_dir($pathx.$tp."/".BAB_FVERSION_FOLDER))
				bab_mkdir($pathx.$tp."/".BAB_FVERSION_FOLDER, $GLOBALS['babMkdirMode']);

			$res = $babDB->db_query("select * from ".BAB_FM_FILESVER_TBL." where id_file='".$babDB->db_escape_string($idf)."'");
			while($arr = $babDB->db_fetch_array($res))
				{
				$filename = $arr['ver_major'].",".$arr['ver_minor'].",".$file;
				rename($pathx.$path."/".BAB_FVERSION_FOLDER."/".$filename, $pathx.$tp."/".BAB_FVERSION_FOLDER."/".$filename);
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

function viewFile( $idf)
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

		var $field;
		var $resff;
		var $countff;
		var $fieldval;
		var $fieldid;
		var $fieldvalhtml;

		function temp($idf, $arr, $bmanager, $access, $bconfirm, $bupdate, $bdownload,$bversion)
			{
			global $babDB;
			$this->access = $access;
			if( $access)
				{
				$this->bmanager = $bmanager;
				$this->bconfirm = $bconfirm;
				$this->bupdate = $bupdate;
				$this->bdownload = $bdownload;
				if( $bconfirm || $bmanager || $bupdate)
					$this->bsubmit = true;
				else
					$this->bsubmit = false;
				$this->idf = $idf;

				$this->description = bab_translate("Description");
				$this->t_keywords = bab_translate("Keywords");
				$this->keywords = bab_translate("Keywords (separated by spaces)");
				$this->notify = bab_translate("Notify members group");
				$this->t_yes = bab_translate("Yes");
				$this->t_no = bab_translate("No");
				$this->t_change_all = bab_translate("Change status for all versions");
				$this->tabIndexStatus = array(BAB_INDEX_STATUS_NOINDEX, BAB_INDEX_STATUS_INDEXED, BAB_INDEX_STATUS_TOINDEX);

				$this->id = $arr['id_owner'];
				$this->gr = $arr['bgroup'];
				$this->path = bab_toHtml($arr['path']);
				$this->file = bab_toHtml($arr['name']);
				$GLOBALS['babBody']->setTitle($arr['name'].( ($bversion == 'Y') ? " (".$arr['ver_major'].".".$arr['ver_minor'].")" : "" ));
				$this->descval = $arr['description'];
				$this->keysval = $arr['keywords'];
				$this->descvalhtml = bab_toHtml($arr['description']);
				$this->keysvalhtml = bab_toHtml($arr['keywords']);

				$this->fsizetxt = bab_translate("Size");
				$fullpath = bab_getUploadFullPath($arr['bgroup'], $arr['id_owner'], $arr['path']).$arr['name'];
				
				if (file_exists($fullpath)) {
					$fstat = stat($fullpath);
					$this->fsize = bab_toHtml(bab_formatSizeFile($fstat[7])." ".bab_translate("Kb")." ( ".bab_formatSizeFile($fstat[7], false) ." ".bab_translate("Bytes") ." )");
				
				} else {
					$this->fsize = '???';
				}
				
				
				$this->fmodifiedtxt = bab_translate("Modified");
				$this->fmodified = bab_toHtml(bab_shortDate(bab_mktime($arr['modified']), true));
				$this->fmodifiedbytxt = bab_translate("Modified by");
				$this->fmodifiedby = bab_toHtml(bab_getUserName($arr['modifiedby']));
				$this->fcreatedtxt = bab_translate("Created");
				$this->fcreated = bab_toHtml(bab_shortDate(bab_mktime($arr['created']), true));
				$this->fpostedbytxt = bab_translate("Posted by");
				$this->fpostedby = bab_toHtml(bab_getUserName($arr['modifiedby'] == 0? $arr['author']: $arr['modifiedby']));

				$this->geturl = bab_toHtml($GLOBALS['babUrlScript']."?tg=fileman&idx=get&id=".$arr['id_owner']."&gr=".$arr['bgroup']."&path=".urlencode($arr['path'])."&file=".urlencode($arr['name']));
				$this->download = bab_translate("Download");

				$this->file = bab_translate("File");
				$this->name = bab_translate("Name");
				$this->nameval = bab_toHtml($arr['name']);
				$this->attribute = bab_translate("Read only");
				if( $arr['readonly'] == "Y")
					{
					$this->yesselected = "selected";
					$this->noselected = "";
					if($this->bupdate)
						$this->bupdate = false;
					}
				else
					{
					$this->noselected = "selected";
					$this->yesselected = "";
					}

				$this->confirm = bab_translate("Confirm");
				if( $arr['confirmed'] == "N")
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


				$rr = $babDB->db_fetch_array($babDB->db_query("select filenotify, version from ".BAB_FM_FOLDERS_TBL." where id='".$babDB->db_escape_string($arr['id_owner'])."'"));

				if ('Y' == $rr['version']) {
					$this->versions = true;
				} else {
					$this->versions = false;
				}

				
				if( $arr['bgroup'] == "Y" && $this->bupdate)
					{
					
					if( $rr['filenotify'] == "N" )
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
					$res = $babDB->db_query("select id, folder from ".BAB_FM_FOLDERS_TBL." where id !='".$babDB->db_escape_string($arr['id_owner'])."'");
					while($arrf = $babDB->db_fetch_array($res))
						{
						if( bab_isAccessValid(BAB_FMMANAGERS_GROUPS_TBL, $arrf['id']))
							{
							$this->arrfolders[] = $arrf;
							}
						}
					$this->countfm = count($this->arrfolders);
					}
				else
					$this->countfm = 0;
				
				if( $arr['bgroup'] == 'Y' )
					{
					$this->resff = $babDB->db_query("select * from ".BAB_FM_FIELDS_TBL." where id_folder='".$babDB->db_escape_string($arr['id_owner'])."'");
					$this->countff = $babDB->db_num_rows($this->resff);
					}
				else
					$this->countff = 0;

				// indexation

				

				if (bab_isFileIndex($fullpath) && bab_isUserAdministrator()) {

						$engine = bab_searchEngineInfos();
						
						$this->index = true;
						$this->index_status = $arr['index_status'];
						$this->t_index_status = bab_translate("Index status");

						$this->index_onload = $engine['indexes']['bab_files']['index_onload'];

						if (isset($_POST['index_status'])) {
							// modify status

							$babDB->db_query(
									"UPDATE ".BAB_FILES_TBL." SET index_status='".$babDB->db_escape_string($_POST['index_status'])."' WHERE id='".$babDB->db_escape_string($_POST['idf'])."'"
								);

							$files_to_index = array($fullpath);

							if (isset($_POST['change_all']) && 1 == $_POST['change_all']) {
								// modifiy index status for older versions
								$res = $babDB->db_query("SELECT id, ver_major, ver_minor FROM ".BAB_FM_FILESVER_TBL." WHERE id_file='".$babDB->db_escape_string($_POST['idf'])."'");
								while ($arrfv = $babDB->db_fetch_assoc($res)) {
									
									$babDB->db_query(
										"UPDATE ".BAB_FM_FILESVER_TBL." SET index_status='".$babDB->db_escape_string($_POST['index_status'])."' WHERE id='".$babDB->db_escape_string($arrfv['id'])."'"
									);

									if ($this->index_onload && BAB_INDEX_STATUS_INDEXED == $_POST['index_status']) {
										
										$files_to_index[] = bab_getUploadFullPath($arr['bgroup'], $arr['id_owner'], $arr['path']).BAB_FVERSION_FOLDER.'/'.$arrfv['ver_major'].','.$arrfv['ver_minor'].','.$arr['name'];
									}
								}
							}

							

							if ($this->index_onload && BAB_INDEX_STATUS_INDEXED == $_POST['index_status']) {
								$this->index_status = bab_indexOnLoadFiles($files_to_index , 'bab_files');
								if (BAB_INDEX_STATUS_INDEXED === $this->index_status) {
									foreach($files_to_index as $f) {
										$obj = new bab_indexObject('bab_files');
										$obj->setIdObjectFile($f, $idf, $arr['id_owner']);
									}
								}
							} else {
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
			static $i=0;
			if( $i < $this->countfm )
				{
				$this->folder = bab_toHtml($this->arrfolders[$i]['folder']);
				$this->folderid = $this->arrfolders[$i]['id'];
				$i++;
				return true;
				}
			else
				return false;
			}

		function getnextfield()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->countff)
				{
				$arr = $babDB->db_fetch_array($this->resff);
				$this->field = bab_translate($arr['name']);
				$this->fieldid = 'field'.$arr['id'];
				$this->fieldval = '';
				$this->fieldvalhtml = '';
				$res = $babDB->db_query("select fvalue from ".BAB_FM_FIELDSVAL_TBL." where id_field='".$babDB->db_escape_string($arr['id'])."' and id_file='".$babDB->db_escape_string($this->idf)."'");
				if( $res && $babDB->db_num_rows($res) > 0)
					{
					list($this->fieldval) = $babDB->db_fetch_array($res);
					$this->fieldvalhtml = bab_toHtml($this->fieldval);
					}
				$i++;
				return true;
				}
			else
				{
				if( $this->countff > 0 )
					$babDB->db_data_seek($this->resff, 0 );
				$i = 0;
				return false;
				}
			}


		function getnextistatus() {
			static $m=0;
			if( $m < count($this->tabIndexStatus))
			{
				$this->value = $this->tabIndexStatus[$m];
			//if (list(,$this->value) = each($arr)) {
				$this->disabled=false;
				$this->option = bab_toHtml(bab_getIndexStatusLabel($this->value));
				$this->selected = $this->index_status == $this->value;
				if (BAB_INDEX_STATUS_INDEXED == $this->value && !$this->index_onload) {
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
	$req = "select * from ".BAB_FILES_TBL." where id='".$babDB->db_escape_string($idf)."' and state=''";
	$res = $babDB->db_query($req);
	if( $res && $babDB->db_num_rows($res) > 0 )
		{
		$arr = $babDB->db_fetch_array($res);
		if( $arr['bgroup'] == "N" )
			{
			if( $babBody->ustorage && $BAB_SESS_USERID == $arr['id_owner'])
				{
				$access = true;
				$bmanager = true;
				$bupdate = true;
				$bdownload = true;
				}
			}

		if( $arr['bgroup'] == "Y")
			{
			if( $arr['confirmed'] == "N" )
				{
				$arrschi = bab_getWaitingIdSAInstance($GLOBALS['BAB_SESS_USERID']);
				if( count($arrschi) > 0 && in_array($arr['idfai'], $arrschi))
					{
					$bconfirm = true;
					}
				}

			for( $i = 0; $i < count($babBody->aclfm['id']); $i++ )
				{
				if( $babBody->aclfm['id'][$i] == $arr['id_owner'] )
					{
					$access = true;
                    if($babBody->aclfm['down'][$i])
                        $bdownload = true;

					if( $babBody->aclfm['ma'][$i] == 1 && !empty($BAB_SESS_USERID))
						{
						$bmanager = true;
						$bupdate = true;
						}
					else if( $babBody->aclfm['upda'][$i] )
						{
						$bupdate = true;
						}
					break;
					}
				}

			if( $bconfirm )
				{
				$bupdate = false;
				$bmanager = false;
				}

			list($bversion) = $babDB->db_fetch_row($babDB->db_query("select version from ".BAB_FM_FOLDERS_TBL." where id='".$babDB->db_escape_string($arr['id_owner'])."'"));
			if(  $arr['edit'] != 0 || $bversion ==  'Y')
				{
				$bupdate = false;
				}
			}
		}
	



	$temp = new temp($idf, $arr, $bmanager, $access, $bconfirm, $bupdate, $bdownload,$bversion);
	$babBody->babpopup(bab_printTemplate($temp,"fileman.html", "viewfile"));
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
	global $babDB;	
	$pathx = bab_getUploadFullPath($gr, $id);
	for( $i = 0; $i < count($items); $i++)
		{
		$res = $babDB->db_query("select * from ".BAB_FILES_TBL." where id='".$babDB->db_escape_string($items[$i])."'");
		if( $res && $babDB->db_num_rows($res) > 0 )
			{
			$arr = $babDB->db_fetch_array($res);
			if( file_exists($pathx.$arr['path']."/".$arr['name']))
				{
				unlink($pathx.$arr['path']."/".$arr['name']);
				}
			deleteFile($items[$i], $arr['name'], $pathx.$arr['path']."/");
			}
		}
	}

function restoreFiles($items)
	{
	global $babDB;	
	for( $i = 0; $i < count($items); $i++)
		{
		$arr = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_FILES_TBL." where id='".$babDB->db_escape_string($items[$i])."'"));
		$pathx = bab_getUploadFullPath($arr['bgroup'], $arr['id_owner']);
		if( !is_dir($pathx.$arr['path']."/"))
			{
			$rr = explode("/", $arr['path']);
			$path = $pathx;
			for( $k = 0; $k < count($rr); $k++ )
				{
				$path .= $rr[$k]."/";
				if( !is_dir($path))
					bab_mkdir($path, $GLOBALS['babMkdirMode']);
				}
			}
		$babDB->db_query("update ".BAB_FILES_TBL." set state='' where id='".$babDB->db_escape_string($items[$i])."'");
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
			$sUplaodPath = BAB_FmFolderHelper::getUploadPath() . '/' . BAB_FmFolderHelper::getUserDirUploadPath($iIdUser);
			
			if('createFolder' === $sAction)
			{
				$sPathName = '';
				if(strlen(trim($sPath)) === 0)
				{
					$sPathName = $sUplaodPath . '/' . $sDirName;
				}
				else 
				{
					$sPathName = $sUplaodPath . '/' . $sPath . '/' . $sDirName;
				}
				BAB_FmFolderHelper::createDirectory($sUplaodPath, $sPathName);
			}
			else if('editFolder' === $sAction)
			{
				$sPathName = '';
				if(strlen(trim($sPath)) === 0)
				{
					$sPathName = $sUplaodPath;
				}
				else 
				{
					$sPathName = $sUplaodPath . '/' . $sPath;
				}
				BAB_FmFolderHelper::renameDirectory($sUplaodPath, $sPathName, $sOldDirName, $sDirName);
				
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
		
		$oFile = BAB_FileSet::get(array(new BAB_InCriterion('iId', 5)));
		bab_debug($oFile);
		
		$sRelativePath = '';
		$oFmFolder = BAB_FmFolderSet::get(array(new BAB_InCriterion('iId', $iIdFolder)));
		if(!is_null($oFmFolder))
		{
			$sRelativePath = $oFmFolder->getPathName();
			if(strlen(trim($sPath)) > 0)
			{
				$sRelativePath .=  '/' . $sPath;
			}
			
			if(strlen(trim($sDirName)) > 0)
			{
				$sUploadPath = BAB_FmFolderHelper::getUploadPath();
				
				if('createFolder' === $sAction)
				{
					$sFullPathName = $sUplaodPath . '/' . $sRelativePath . '/' . $sDirName;
					
//					bab_debug('sUplaodPath ==> ' . $sUplaodPath);
//					bab_debug('sFullPathName ==> ' .  $sFullPathName);
					
					if(BAB_FmFolderHelper::createDirectory($sUplaodPath, $sFullPathName))
					{
						if('collective' === $sType)
						{
							$sPathName = $sRelativePath . '/' . $sDirName;
							
							$oFmFolder = new BAB_FmFolder();
							$oFmFolder->setActive($sActive);
							$oFmFolder->setApprobationSchemeId($iIdApprobationScheme);
							$oFmFolder->setAutoApprobation($sAutoApprobation);
							$oFmFolder->setDelegationOwnerId((int) $babBody->currentAdmGroup);
							$oFmFolder->setFileNotify($sNotification);
							$oFmFolder->setHide($sDisplay);
							$oFmFolder->setName($sDirName);
							$oFmFolder->setPathName($sPathName);
							$oFmFolder->setVersioning($sVersioning);
							$oFmFolder->setAutoApprobation($sAutoApprobation);
							if(false === $oFmFolder->save())
							{
								rmdir($sPathName);
							}
						}
					}
				}
				else if('editFolder' === $sAction)
				{
					$iIdFolder					= (int) bab_pp('iIdFolder', 0); 
					$bRename					= false;
					$bWasCollective				= false;
					$bChangeIdOwnerOfChildFiles	= false; 			
					
					$oFmFolder = BAB_FmFolderSet::get(array(new BAB_InCriterion('iId', $iIdFolder)));
					if(!is_null($oFmFolder))
					{
						$bRename		= ($sDirName !== $oFmFolder->getName()) ? true : false;
						$sOldDirName	= $oFmFolder->getName();
						$bWasCollective = true;
					}
					else 
					{
						$sOldDirName	= (string) bab_pp('sOldDirName', '');
						$bRename		= ($sDirName !== $sOldDirName) ? true : false;
					}
					
					if($bRename)
					{
						if(strlen(trim($sOldDirName)) > 0)
						{
							$sPathName = $sUploadPath . '/' . $sRelativePath;
							BAB_FmFolderHelper::updateSubFolderPathName($sUploadPath, $sPathName, $sOldDirName, $sDirName);
							bab_debug('Ne pas oublier les fichiers');
						}
						else 
						{
							bab_debug(__FUNCTION__ . ' ERROR invalid sOldDirName');
						}
					}
		
		
					//Pour les fichiers
					$oParentFolder = BAB_FmFolderHelper::getFirstCollectiveFolder($sRelativePath);
					if(!is_null($oParentFolder))
					{
						bab_debug('Nuclear launch detected');		
						bab_debug($oParentFolder);		
					}
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
		viewFile(bab_rp('idf'));
		exit;
		break;

	case "get":
		getFile($file, $id, $gr, $path);
		break;

	case "trash":
		$babBody->title = bab_translate("Trash");
		listTrashFiles($id, $gr);
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
			$oFmFolder = BAB_FmFolderSet::get(array(new BAB_InCriterion('iId', $id)));
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
//		$babBody->title = bab_translate("File manager");
//		$babBody->addItemMenu("list", bab_translate("Folders"), $GLOBALS['babUrlScript']."?tg=fileman&idx=list&id=".$id."&gr=".$gr."&path=".urlencode($path));
//		if( $upload) {
//			$babBody->addItemMenu("add", bab_translate("Upload"), $GLOBALS['babUrlScript']."?tg=fileman&idx=add&id=".$id."&gr=".$gr."&path=".urlencode($path));
//			}
//		if( $bmanager) {
//			$babBody->addItemMenu("trash", bab_translate("Trash"), $GLOBALS['babUrlScript']."?tg=fileman&idx=trash&id=".$id."&gr=".$gr."&path=".urlencode($path));
//			}
		listFiles($id, $gr, $path, $bmanager, $upload);
//		if( !empty($id) && $gr == "Y")
//			{
//			$GLOBALS['babWebStat']->addFolder($id);
//			}
		break;
	}
$babBody->setCurrentItemMenu($idx);
?>