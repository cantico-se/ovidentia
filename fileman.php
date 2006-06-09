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
include_once "base.php";
include_once $babInstallPath."utilit/fileincl.php";

function notifyApprovers($id, $fid)
	{
	global $babBody, $babDB;

	$arr = $babDB->db_fetch_array($babDB->db_query("select idsa, auto_approbation from ".BAB_FM_FOLDERS_TBL." where id='".$fid."'"));

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
		$babDB->db_query("update ".BAB_FILES_TBL." set confirmed='Y' where id='".$id."'");
		return true;
		}
	elseif(!empty($idfai))
		{
		$babDB->db_query("update ".BAB_FILES_TBL." set idfai='".$idfai."' where id='".$id."'");
		$nfusers = getWaitingApproversFlowInstance($idfai, true);
		if( count($nfusers))
			{
			notifyFileApprovers($id, $nfusers, bab_translate("A new file is waiting for you"));
			}
		$babBody->msgerror = bab_translate("Your file is waiting for approval");
		}
	
	return false;
	}

function deleteFile($idf, $name, $path)
	{
	global $babDB;

	if( is_dir($path.BAB_FVERSION_FOLDER."/"))
		{
		$res = $babDB->db_query("select * from ".BAB_FM_FILESVER_TBL." where id_file='".$idf."'");
		while($arr = $babDB->db_fetch_array($res))
			{
			unlink($path.BAB_FVERSION_FOLDER."/".$arr['ver_major'].",".$arr['ver_minor'].",".$name);
			}
		}
	$babDB->db_query("delete from ".BAB_FM_FILESVER_TBL." where id_file='".$idf."'");
	$babDB->db_query("delete from ".BAB_FM_FILESLOG_TBL." where id_file='".$idf."'");
	$babDB->db_query("delete from ".BAB_FM_FIELDSVAL_TBL." where id_file='".$idf."'");
	$babDB->db_query("delete from ".BAB_FILES_TBL." where id='".$idf."'");
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

	function listFiles($id, $gr, $path, $bmanager, $what ="list")
		{
		global $babBody, $BAB_SESS_USERID;
		include_once $GLOBALS['babInstallPath']."utilit/afincl.php";
		$this->fullpath = bab_getUploadFullPath($gr, $id);
		$this->path = $path;
		$this->jpath = str_replace("'", "\'", $path);
		$this->jpath = str_replace('"', "'+String.fromCharCode(34)+'",$this->jpath);
		$this->id = $id;
		$this->gr = $gr;
		$this->countmgrp = 0;
		$this->countgrp = 0;
		$this->buaf = false;

		$this->db = $GLOBALS['babDB'];
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
						$req = "select f.* from ".BAB_FILES_TBL." f where id_owner='".$id."' and bgroup='".$gr."' and state='' and path='".addslashes($path)."' and confirmed='N' and f.idfai IN (".implode(',', $arrschi).")";
						$this->reswf = $this->db->db_query($req);
						$this->countwf = $this->db->db_num_rows($this->reswf);
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

		if( $gr == "Y" || ($gr == "N" && !empty($path)))
			{
			$this->countgrp = 0;
			}
		else 
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
					if (is_dir($this->fullpath.$path."/".$f))
						$this->arrdir[] = $f;
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
					$this->arrudir[] = $GLOBALS['babUrlScript']."?tg=fileman&idx=".$what."&id=".$id."&gr=".$gr."&path=".urlencode($path.($path ==""?"":"/").$f);
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
					array_unshift ($this->arrudir, $GLOBALS['babUrlScript']."?tg=fileman&idx=".$what."&id=".$id."&gr=".$gr."&path=".urlencode($p));
					}
				else
					{
					$this->arrdir[] = ". .";
					$this->arrudir[] = $GLOBALS['babUrlScript']."?tg=fileman&idx=".$what."&id=".$id."&gr=".$gr."&path=".urlencode($p);
					}
				}
			$req = "select * from ".BAB_FILES_TBL." where id_owner='".$id."' and bgroup='".$gr."' and state='' and path='".addslashes($path)."' and confirmed='Y'";
			$req .= " order by name asc";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			}
		else
			$this->count = 0;
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
			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_FILES_TBL." where id_owner='".$id."' and bgroup='".$gr."' and state='D' order by name asc";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			}

		function getnextfile()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $this->db->db_fetch_array($this->res);
				$ext = substr(strrchr($arr['name'], "."), 1);
				if( empty($this->arrext[$ext]))
					$this->arrext[$ext] = bab_printTemplate($this, "config.html", ".".$ext);
				if( empty($this->arrext[$ext]))
					$this->arrext[$ext] = bab_printTemplate($this, "config.html", ".unknown");				
				$this->fileimage = $this->arrext[$ext];
				$this->name = $arr['name'];
				$this->idfile = $arr['id'];
				if( file_exists($this->fullpath.$arr['path']."/".$arr['name']))
					{
					$fstat = stat($this->fullpath.$arr['path']."/".$arr['name']);
					$this->sizef = $fstat[7];
					}
				else
					$this->sizef = "???";

				$this->modified = bab_shortDate(bab_mktime($arr['modified']), true);
				$this->postedby = bab_getUserName($arr['modifiedby'] == 0? $arr['author']: $arr['modifiedby']);
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
				$this->diskspace = bab_formatSizeFile($size).$this->kilooctet;
				$this->allowedspace =  bab_formatSizeFile($GLOBALS['babMaxUserSize']).$this->kilooctet;
				$this->remainingspace =  bab_formatSizeFile($GLOBALS['babMaxUserSize'] - $size).$this->kilooctet;
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
				$this->diskspace = bab_formatSizeFile($size).$this->kilooctet;
				$this->allowedspace =  bab_formatSizeFile($GLOBALS['babMaxTotalSize']).$this->kilooctet;
				$this->remainingspace =  bab_formatSizeFile($GLOBALS['babMaxTotalSize'] - $size).$this->kilooctet;
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
				$this->diskspace = bab_formatSizeFile($size).$this->kilooctet;
				$this->allowedspace =  bab_formatSizeFile($GLOBALS['babMaxGroupSize']).$this->kilooctet;
				$this->remainingspace =  bab_formatSizeFile($GLOBALS['babMaxGroupSize'] - $size).$this->kilooctet;
				$this->groupname = bab_getFolderName($this->arrgrp[$i]);
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
				$this->groupname = bab_getFolderName($this->arrmgrp[$i]);
				$pathx = bab_getUploadFullPath("Y", $this->arrmgrp[$i]);
				$size = getDirSize($pathx);
				$this->diskspace = bab_formatSizeFile($size).$this->kilooctet;
				$this->allowedspace =  bab_formatSizeFile($GLOBALS['babMaxGroupSize']).$this->kilooctet;
				$this->remainingspace =  bab_formatSizeFile($GLOBALS['babMaxGroupSize'] - $size).$this->kilooctet;
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

function browseFiles($id, $gr, $path, $bmanager, $editor)
	{
	global $babBody;

	class temp extends listFiles
		{
		var $arrext = array();
		var $upfolderimg;
		var $usrfolderimg;
		var $grpfolderimg;
		var $editor;
		var $desctxt;
		var $root;
		var $refresh;
		var $manfolderimg;
		var $rootpath;
		var $rooturl;
		var $refreshurl;
		var $name;
		var $url;
		var $jname;
		var $description;
		var $idf;
		var $close;
		var $altbg = true;

		function temp($id, $gr, $path, $bmanager, $editor)
			{
			global $BAB_SESS_USERID;
			$this->editor = $editor;
			$this->desctxt = bab_translate("Description");
			$this->root = bab_translate("Home folder");
			$this->refresh = bab_translate("Refresh");
			$this->nametxt = bab_translate("Name");
			$this->close = bab_translate("Close");
			$this->listFiles($id, $gr, $path, $bmanager, "brow");

			if( $gr == "Y")
				$this->rootpath = bab_getFolderName($id);
			else
				$this->rootpath = "";
			$this->rooturl = $GLOBALS['babUrlScript']."?tg=fileman&idx=brow&id=".$BAB_SESS_USERID."&gr=N&path=&editor=".$this->editor;
			$this->refreshurl = $GLOBALS['babUrlScript']."?tg=fileman&idx=brow&id=".$id."&gr=".$gr."&path=".urlencode($path)."&editor=".$this->editor;
			$this->id = $id;
			}

		function getnextdir()
			{
			static $i = 0;
			if( $i < count($this->arrdir))
				{
				$this->altbg = !$this->altbg;
				$this->name = $this->arrdir[$i];
				$this->url = $this->arrudir[$i]."&editor=".$this->editor;
				$this->folderpath = empty($this->path) ? urlencode($this->name) : urlencode($this->path.'/'.$this->name);
				$this->folderid = $this->id;
				$i++;
				return true;
				}
			else
				return false;
			}

		function getnextgrpdir()
			{
			static $m = 0;
			if( $m < $this->countgrp)
				{
				$this->altbg = !$this->altbg;
				$this->name = $this->arrgrp['folder'][$m];
				$this->folderid = $this->arrgrp['id'][$m];
				$this->folderpath = '';
				$this->url = $GLOBALS['babUrlScript']."?tg=fileman&idx=brow&id=".$this->arrgrp['id'][$m]."&gr=Y&path=&editor=".$this->editor;
				$this->ma = $this->arrgrp['ma'][$m];
				$m++;
				return true;
				}
			else
				{
				$this->folderid = false;
				return false;
				}
			}

		function getnextfile()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$this->altbg = !$this->altbg;
				$arr = $this->db->db_fetch_array($this->res);
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
				$this->jname = str_replace("'", "\'", $arr['name']);
				$this->jname = str_replace('"', "'+String.fromCharCode(34)+'",$this->jname);
				$this->description = $arr['description'];
				$this->idf = $arr['id'];
				$i++;
				return true;
				}
			else
				return false;
			}
		}

	$temp = new temp($id, $gr, $path, $bmanager, $editor);
	echo bab_printTemplate($temp,"fileman.html", "browsefiles");
	}

function listFiles($id, $gr, $path, $bmanager)
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

		var $altfilelog;
		var $altfilelock;
		var $altfileunlock;
		var $altfilewrite;


		function temp($id, $gr, $path, $bmanager)
			{
			global $BAB_SESS_USERID;
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

			$this->rooturl = $GLOBALS['babUrlScript']."?tg=fileman&idx=list";
			$this->refreshurl = $GLOBALS['babUrlScript']."?tg=fileman&idx=list&id=".$id."&gr=".$gr."&path=".urlencode($path);
			$this->urldiskspace = $GLOBALS['babUrlScript']."?tg=fileman&idx=disk&id=".$id."&gr=".$gr."&path=".$this->jpath;

			$this->upfolderimg = bab_printTemplate($this, "config.html", "parentfolder");
			$this->usrfolderimg = bab_printTemplate($this, "config.html", "userfolder");
			$this->grpfolderimg = bab_printTemplate($this, "config.html", "groupfolder");
			$this->manfolderimg = bab_printTemplate($this, "config.html", "managerfolder");

			if( $gr == "Y")
				{
				list($version) = $this->db->db_fetch_array($this->db->db_query("select version from ".BAB_FM_FOLDERS_TBL." where id='".$id."'"));
				$this->rootpath = bab_getFolderName($id);
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
				$req = "select * from ".BAB_FILES_TBL." where id_owner='".$id."' and bgroup='".$gr."' and state='X' order by name asc";
				$this->xres = $this->db->db_query($req);
				$this->xcount = $this->db->db_num_rows($this->xres);
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
				$this->name = $this->arrdir[$i];
				$this->url = $this->arrudir[$i];
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
				$this->name = $this->arrgrp['folder'][$m];
				$this->url = $GLOBALS['babUrlScript']."?tg=fileman&idx=list&id=".$this->arrgrp['id'][$m]."&gr=Y&path=";
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
				$this->sizef = bab_formatSizeFile($fstat[7])." ".bab_translate("Kb");
				}
			else
				$this->sizef = "???";

			$this->modified = bab_shortDate(bab_mktime($arr['modified']), true);
			$this->postedby = bab_getUserName($arr['modifiedby'] == 0? $arr['author']: $arr['modifiedby']);
			$this->hits = $arr['hits'];
			if( $arr['readonly'] == "Y" )
				$this->readonly = "R";
			else
				$this->readonly = "";
			}

		function getnextfile()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $this->db->db_fetch_array($this->res);
				$this->bconfirmed = 0;
				$this->updateFileInfo($arr);
				$ufile = urlencode($arr['name']);
				$upath = urlencode($this->path);
				$this->url = $GLOBALS['babUrlScript']."?tg=fileman&idx=upd&id=".$this->id."&gr=".$this->gr."&path=".$upath."&file=".$ufile;
				$jfname = str_replace("'", "\'", $arr['name']);
				$jfname = str_replace('"', "'+String.fromCharCode(34)+'",$jfname);
				$this->viewurl = $GLOBALS['babUrlScript']."?tg=fileman&idx=viewfile&idf=".$arr['id']."&id=".$this->id."&gr=".$this->gr."&path=".urlencode($this->jpath)."&file=".urlencode($jfname);
				$this->urlget = $GLOBALS['babUrlScript']."?tg=fileman&idx=get&id=".$this->id."&gr=".$this->gr."&path=".$upath."&file=".$ufile;
				$this->cuturl = $GLOBALS['babUrlScript']."?tg=fileman&idx=cut&id=".$this->id."&gr=".$this->gr."&path=".$upath."&file=".$ufile;
				$this->delurl = $GLOBALS['babUrlScript']."?tg=fileman&idx=del&id=".$this->id."&gr=".$this->gr."&path=".$upath."&file=".$ufile;
				if( $this->bversion )
					{
					$this->lastversion = $arr['ver_major'].".".$arr['ver_minor'];
					$this->ovfhisturl = $GLOBALS['babUrlScript']."?tg=filever&idx=hist&id=".$this->id."&gr=".$this->gr."&path=".$upath."&idf=".$arr['id'];

					$this->ovfversurl = $GLOBALS['babUrlScript']."?tg=filever&idx=lvers&id=".$this->id."&gr=".$this->gr."&path=".$upath."&idf=".$arr['id'];
				
					$this->bfvwait = false;
					$this->blockauth = false;
					if( $arr['edit'] )
						{
						$this->block = true;
						list($lockauthor, $idfvai) = $this->db->db_fetch_array($this->db->db_query("select author, idfai from ".BAB_FM_FILESVER_TBL." where id='".$arr['edit']."'"));
						if( $idfvai == 0 && $lockauthor == $GLOBALS['BAB_SESS_USERID'])
							$this->blockauth = true;

						if( $idfvai != 0 && $this->buaf )
							{
							$this->bfvwait = true;
							$this->bupdate = true;
							}
						$this->ovfurl = $GLOBALS['babUrlScript']."?tg=filever&idx=unlock&id=".$this->id."&gr=".$this->gr."&path=".$upath."&idf=".$arr['id'];
						if( $this->bfvwait )
							$this->ovfcommiturl = $GLOBALS['babUrlScript']."?tg=filever&idx=conf&id=".$this->id."&gr=".$this->gr."&path=".$upath."&idf=".$arr['id'];
						else
							$this->ovfcommiturl = $GLOBALS['babUrlScript']."?tg=filever&idx=commit&id=".$this->id."&gr=".$this->gr."&path=".$upath."&idf=".$arr['id'];
						}
					else
						{
						$this->block = false;
						$this->ovfurl = $GLOBALS['babUrlScript']."?tg=filever&idx=lock&id=".$this->id."&gr=".$this->gr."&path=".$upath."&idf=".$arr['id'];
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
			static $i = 0;
			if( $i < $this->countwf)
				{
				$arr = $this->db->db_fetch_array($this->reswf);
				$this->bconfirmed = 1;
				$this->updateFileInfo($arr);
				$ufile = urlencode($arr['name']);
				$upath = urlencode($this->path);
				$this->url = $GLOBALS['babUrlScript']."?tg=fileman&idx=upd&id=".$this->id."&gr=".$this->gr."&path=".$upath."&file=".$ufile;
				$jfname = str_replace("'", "\'", $arr['name']);
				$jfname = str_replace('"', "'+String.fromCharCode(34)+'",$jfname);
				$this->viewurl = $GLOBALS['babUrlScript']."?tg=fileman&idx=viewfile&idf=".$arr['id']."&id=".$this->id."&gr=".$this->gr."&path=".urlencode($this->jpath)."&file=".urlencode($jfname);
				$this->urlget = $GLOBALS['babUrlScript']."?tg=fileman&idx=get&id=".$this->id."&gr=".$this->gr."&path=".$upath."&file=".$ufile;
				$this->cuturl = $GLOBALS['babUrlScript']."?tg=fileman&idx=cut&id=".$this->id."&gr=".$this->gr."&path=".$upath."&file=".$ufile;				
				$this->delurl = $GLOBALS['babUrlScript']."?tg=fileman&idx=del&id=".$this->id."&gr=".$this->gr."&path=".$upath."&file=".$ufile;				
				$i++;
				return true;
				}
			else
				return false;
			}


		function getnextxfile()
			{
			static $i = 0;
			if( $i < $this->xcount)
				{
				$arr = $this->db->db_fetch_array($this->xres);
				$this->bconfirmed = 0;
				$this->updateFileInfo($arr);
				$ufile = urlencode($arr['name']);
				$upath = urlencode($arr['path']);
				$this->url = $GLOBALS['babUrlScript']."?tg=fileman&idx=upd&id=".$this->id."&gr=".$this->gr."&path=".$upath."&file=".$ufile;
				$this->urlget = $GLOBALS['babUrlScript']."?tg=fileman&idx=get&id=".$this->id."&gr=".$this->gr."&path=".$upath."&file=".$ufile;
				$this->pasteurl = $GLOBALS['babUrlScript']."?tg=fileman&idx=paste&id=".$this->id."&gr=".$this->gr."&path=".$upath."&file=".$ufile."&tp=".$this->path;				
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
		if(!is_dir($pathx) && !bab_mkdir($pathx, $GLOBALS['babMkdirMode']))
			$babBody->msgerror = bab_translate("Can't create directory: ").$pathx;
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
			if( $GLOBALS['babMaxFileSize'] < 1000000 )
				{
				$this->maxsize =  bab_formatSizeFile($GLOBALS['babMaxFileSize'])." ".bab_translate("Kb");
				}
			else
				{
				$this->maxsize =  floor($GLOBALS['babMaxFileSize'] / 1000000 )." ".bab_translate("Mb");
				}
			$this->id = $id;
			$this->path = $path;
			$this->gr = $gr;
			$this->maxfilesize = $GLOBALS['babMaxFileSize'];
			$this->descval = isset($description)? $description: "";
			$this->keysval = isset($keywords)? $keywords: "";
			if( $gr == 'Y' )
				{
				$this->res = $babDB->db_query("select * from ".BAB_FM_FIELDS_TBL." where id_folder='".$id."'");
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
				$this->fieldval = htmlentities($arr['defaultval']);
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


function saveFile($id, $gr, $path, $filename, $size, $tmp, $description, $keywords, $readonly)
	{
	global $babBody, $BAB_SESS_USERID;
	$access = false;
	$bmanager = false;
	$access = false;
	$confirmed = "N";

	if( $gr == "N" && !empty($BAB_SESS_USERID))
		{
		if( $babBody->ustorage )
			{
			$access = true;
			$confirmed = "Y";
			}
		}

	if( $gr == "Y" && !empty($BAB_SESS_USERID))
		{
		for( $i = 0; $i < count($babBody->aclfm['id']); $i++)
			{
			if( $babBody->aclfm['id'][$i] == $id && ( $babBody->aclfm['uplo'][$i] || $babBody->aclfm['ma'][$i] == 1))
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

	if( empty($filename) || $filename == "none")
		{
		$babBody->msgerror = bab_translate("Please select a file to upload");
		return false;
		}
	else 
		$filename = trim($filename);

	if( $size > $GLOBALS['babMaxFileSize'])
		{
		$babBody->msgerror = bab_translate("The file was greater than the maximum allowed") ." :". $GLOBALS['babMaxFileSize'];
		return false;
		}
	$totalsize = getDirSize($GLOBALS['babUploadPath']);
	if( $size + $totalsize > $GLOBALS['babMaxTotalSize'])
		{
		$babBody->msgerror = bab_translate("There is not enough free space");
		return false;
		}
	$pathx = bab_getUploadFullPath($gr, $id);

	$totalsize = getDirSize($pathx);
	if( $size + $totalsize > ($gr == "Y"? $GLOBALS['babMaxGroupSize']: $GLOBALS['babMaxUserSize']))
		{
		$babBody->msgerror = bab_translate("There is not enough free space");
		return false;
		}

	if( substr($path, -1) == "/")
		$pathx .= substr($path, 0 , -1);
	else if( !empty($path))
		$pathx .= $path."/";	

	$osfname = $filename;
	if( bab_isMagicQuotesGpcOn())
		$osfname = stripslashes($osfname);

	if( isset($GLOBALS['babFileNameTranslation']))
		$osfname = strtr($osfname, $GLOBALS['babFileNameTranslation']);

	$db = $GLOBALS['babDB'];
	$name = $db->db_escape_string($osfname);

	$mqgo = bab_isMagicQuotesGpcOn();
	if( !$mqgo)
		{
		$description = $db->db_escape_string($description);
		$keywords = $db->db_escape_string($keywords);
		}

	$bexist = false;
	if( file_exists($pathx.$osfname))
		{
		$res = $db->db_query("select * from ".BAB_FILES_TBL." where id_owner='".$id."' and bgroup='".$gr."' and name='".$name."' and path='".addslashes($path)."'");
		if( $res && $db->db_num_rows($res) > 0)
			{
			$arr = $db->db_fetch_array($res);
			if( $arr['state'] == "D")
				{
				$bexist = true;
				}
			}

		if( $bexist == false)
			{
			$babBody->msgerror = bab_translate("A file with the same name already exists");
			return false;
			}
		}

	if( !get_cfg_var('safe_mode'))
		set_time_limit(0);
	if( !move_uploaded_file($tmp, $pathx.$osfname))
		{
		$babBody->msgerror = bab_translate("The file could not be uploaded");
		return false;
		}

	
	
	if( empty($BAB_SESS_USERID))
		$idcreator = 0;
	else
		$idcreator = $BAB_SESS_USERID;

	$bnotify = false;
	if( $gr == "Y" )
		{
		$rr = $db->db_fetch_array($db->db_query("select filenotify from ".BAB_FM_FOLDERS_TBL." where id='".$id."'"));
		if( $rr['filenotify'] == "Y" )
			$bnotify = true;

		if( $bexist )
			{
			if( is_dir($pathx.BAB_FVERSION_FOLDER."/"))
				{
				$res = $db->db_query("select * from ".BAB_FM_FILESVER_TBL." where id_file='".$arr['id']."'");
				while($rr = $db->db_fetch_array($res))
					{
					unlink($pathx.BAB_FVERSION_FOLDER."/".$rr['ver_major'].",".$rr['ver_minor'].",".$osfname);
					}
				}
			$db->db_query("delete from ".BAB_FM_FILESVER_TBL." where id_file='".$arr['id']."'");
			$db->db_query("delete from ".BAB_FM_FILESLOG_TBL." where id_file='".$arr['id']."'");
			$db->db_query("delete from ".BAB_FM_FIELDSVAL_TBL." where id_file='".$arr['id']."'");
			}
		
		}

	include_once $GLOBALS['babInstallPath']."utilit/indexincl.php";
	$index_status = bab_indexOnLoadFiles(array($pathx.$osfname), 'bab_files');

	if( $bexist)
		{
		$req = "update ".BAB_FILES_TBL." set description='".$description."', keywords='".$keywords."', readonly='".$readonly."', confirmed='".$confirmed."', modified=now(), hits='0', modifiedby='".$idcreator."', state='', index_status='".$index_status."' where id='".$arr['id']."'";
		$db->db_query($req);
		$idf = $arr['id'];
		}
	else
		{
		$req = "insert into ".BAB_FILES_TBL." (name, description, keywords, path, id_owner, bgroup, link, readonly, state, created, author, modified, modifiedby, confirmed, index_status) values ";
		$req .= "('" .$name. "', '" . $description. "', '" . $keywords. "', '" .addslashes($path). "', '" . $id. "', '" . $gr. "', '0', '" . $readonly. "', '', now(), '" . $idcreator. "', now(), '" . $idcreator. "', '". $confirmed."', '".$index_status."')";
		$db->db_query($req);
		$idf = $db->db_insert_id(); 
		}

	if (BAB_INDEX_STATUS_INDEXED === $index_status) {
		$obj = new bab_indexObject('bab_files');
		$obj->setIdObjectFile($pathx.$osfname, $idf, $id);
	}

	if( $gr == 'Y')
		{
		if( $confirmed == "Y" )
			{
			$GLOBALS['babWebStat']->addNewFile($babBody->currentAdmGroup);
			}

		$res = $db->db_query("select id from ".BAB_FM_FIELDS_TBL." where id_folder='".$id."'");
		while($arr = $db->db_fetch_array($res))
			{
			$fd = 'field'.$arr['id'];
			if( isset($GLOBALS[$fd]) )
				{
				if( !$mqgo)
					{
					$fval = addslashes($GLOBALS[$fd]);
					}
				else
					$fval = $GLOBALS[$fd];
				$res2 = $db->db_query("select id from ".BAB_FM_FIELDSVAL_TBL." where id_file='".$idf."' and id_field='".$arr['id']."'");
				if( $res2 && $db->db_num_rows($res2) > 0)
					{
					$arr2 = $db->db_fetch_array($res2);
					$db->db_query("update ".BAB_FM_FIELDSVAL_TBL." set fvalue='".$fval."' where id='".$arr2['id']."'");
					}
				else
					{
					$db->db_query("insert into ".BAB_FM_FIELDSVAL_TBL." set fvalue='".$fval."', id_file='".$idf."', id_field='".$arr['id']."'");
					}
				}
			}
		}

	if( $gr == "Y" && $confirmed == "N" )
		{
		if( notifyApprovers($idf, $id) && $bnotify)
			fileNotifyMembers($osfname, $path, $id, bab_translate("A new file has been uploaded"));
		}

	return true;
	}

function saveUpdateFile($idf, $uploadf_name, $uploadf_size,$uploadf, $fname, $description, $keywords, $readonly, $confirm, $bnotify, $newfolder, $descup)
	{
	global $babBody, $BAB_SESS_USERID;
	$db = $GLOBALS['babDB'];

	$res = $db->db_query("select * from ".BAB_FILES_TBL." where id='".$idf."'");
	if( $res && $db->db_num_rows($res))
		{
		$arr = $db->db_fetch_array($res);
		if( $arr['bgroup'] == "Y" )
			{
			for( $i = 0; $i < count($babBody->aclfm['id']); $i++)
				{
				if( $babBody->aclfm['id'][$i] == $arr['id_owner'] )
					{
					if ( $babBody->aclfm['upda'][$i] || $babBody->aclfm['ma'][$i] == 1)
						{
						break;
						}
					else
						{
						$arrschi = bab_getWaitingIdSAInstance($GLOBALS['BAB_SESS_USERID']);
						if( count($arrschi) > 0 && in_array($arr['idfai'], $arrschi))
							{
							break;
							}
						else
							{
								$babBody->msgerror = bab_translate("Access denied");
								return false;
							}
						}
					}
				}
			}

		$pathx = bab_getUploadFullPath($arr['bgroup'], $arr['id_owner']);
		if( substr($arr['path'], -1) == "/")
			$pathx .= substr($arr['path'], 0 , -1);
		else if( !empty($arr['path']))
			$pathx .= $arr['path']."/";	

		if( !file_exists($pathx.$arr['name']))
			{
			$babBody->msgerror = bab_translate("File does'nt exist");
			return false;
			}

		$bmodified = false;
		if( !empty($uploadf_name) && $uploadf_name != "none")
			{
			if( $size > $GLOBALS['babMaxFileSize'])
				{
				$babBody->msgerror = bab_translate("The file was greater than the maximum allowed") ." :". $GLOBALS['babMaxFileSize'];
				return false;
				}
			$totalsize = getDirSize($GLOBALS['babUploadPath']);
			if( $size + $totalsize > $GLOBALS['babMaxTotalSize'])
				{
				$babBody->msgerror = bab_translate("There is not enough free space");
				return false;
				}

			$totalsize = getDirSize($pathx);
			if( $size + $totalsize > ($arr['bgroup'] == "Y"? $GLOBALS['babMaxGroupSize']: $GLOBALS['babMaxUserSize']))
				{
				$babBody->msgerror = bab_translate("There is not enough free space");
				return false;
				}

			if( isset($GLOBALS['babFileNameTranslation']))
				$uploadf_name = strtr($uploadf_name, $GLOBALS['babFileNameTranslation']);

			if( !get_cfg_var('safe_mode'))
				set_time_limit(0);
			if( !move_uploaded_file($uploadf, $pathx.$arr['name']))
				{
				$babBody->msgerror = bab_translate("The file could not be uploaded");
				return false;
				}
			$bmodified = true;
			}

		$fname = trim($fname);
		$frename = false;
		$osfname = $fname;
		if( bab_isMagicQuotesGpcOn())
			$osfname = stripslashes($osfname);
		if( !empty($fname) && strcmp($arr['name'], $osfname))
			{
			if( isset($GLOBALS['babFileNameTranslation']))
				$osfname = strtr($osfname, $GLOBALS['babFileNameTranslation']);
			if( rename($pathx.$arr['name'], $pathx.$osfname))
				{
				$frename = true;
				if( is_dir($pathx.BAB_FVERSION_FOLDER."/"))
					{
					$res = $db->db_query("select * from ".BAB_FM_FILESVER_TBL." where id_file='".$idf."'");
					while($rr = $db->db_fetch_array($res))
						{
						$filename = $rr['ver_major'].",".$rr['ver_minor'].",".$osfname;
						rename($pathx.BAB_FVERSION_FOLDER."/".$rr['ver_major'].",".$rr['ver_minor'].",".$arr['name'], $pathx.BAB_FVERSION_FOLDER."/".$rr['ver_major'].",".$rr['ver_minor'].",".$osfname);
						}
					}
				}
			}

		$mqgo = bab_isMagicQuotesGpcOn();
		if( !$mqgo )
			{
			$fname = addslashes($fname);
			$description = addslashes($description);
			$keywords = addslashes($keywords);
			}

		if( empty($BAB_SESS_USERID))
			$idcreator = 0;
		else
			$idcreator = $BAB_SESS_USERID;
	
		$tmp = array();
		if( $descup )
			{
			$tmp[] = "description='".$description."'";
			$tmp[] = "keywords='".$keywords."'";
			}
		if( $bmodified)
			{
			$tmp[] = "modified=now()";
			$tmp[] = "modifiedby='".$idcreator."'";
			}
		if( $frename)
			{
			$tmp[] = "name='".$fname."'";
			}
		else
			$osfname = $arr['name'];

		if( !empty($readonly))
			$tmp[] = "readonly='".$readonly."'";
		if( !empty($newfolder))
			{
			$pathxnew = bab_getUploadFullPath($arr['bgroup'], $newfolder);
			if(!is_dir($pathxnew))
				{
				bab_mkdir($pathxnew, $GLOBALS['babMkdirMode']);
				}

			if( rename( $pathx.$osfname, $pathxnew.$osfname))
				{
				$db->db_query("delete from ".BAB_FM_FIELDSVAL_TBL." where id_file='".$idf."'");
				$tmp[] = "id_owner='".$newfolder."'";
				$tmp[] = "path=''";
				$arr['id_owner'] = $newfolder;

				if( is_dir($pathx.BAB_FVERSION_FOLDER."/"))
					{
					if( !is_dir($pathxnew.BAB_FVERSION_FOLDER."/"))
						bab_mkdir($pathxnew.BAB_FVERSION_FOLDER, $GLOBALS['babMkdirMode']);

					$res = $db->db_query("select * from ".BAB_FM_FILESVER_TBL." where id_file='".$idf."'");
					while($rr = $db->db_fetch_array($res))
						{
						$filename = $rr['ver_major'].",".$rr['ver_minor'].",".$osfname;
						rename($pathx.BAB_FVERSION_FOLDER."/".$filename, $pathxnew.BAB_FVERSION_FOLDER."/".$filename);
						}
					}

				}
			}

		if( count($tmp) > 0 )
			$db->db_query("update ".BAB_FILES_TBL." set ".implode(", ", $tmp)." where id='".$idf."'");

		if( $arr['bgroup'] == 'Y')
			{
			$res = $db->db_query("select id from ".BAB_FM_FIELDS_TBL." where id_folder='".$arr['id_owner']."'");
			while($arrf = $db->db_fetch_array($res))
				{
				$fd = 'field'.$arrf['id'];
				if( isset($GLOBALS[$fd]) )
					{
					if( !$mqgo)
						{
						$fval = addslashes($GLOBALS[$fd]);
						}
					else
						$fval = $GLOBALS[$fd];
					$res2 = $db->db_query("select id from ".BAB_FM_FIELDSVAL_TBL." where id_file='".$idf."' and id_field='".$arrf['id']."'");
					if( $res2 && $db->db_num_rows($res2) > 0)
						{
						$arr2 = $db->db_fetch_array($res2);
						$db->db_query("update ".BAB_FM_FIELDSVAL_TBL." set fvalue='".$fval."' where id='".$arr2['id']."'");
						}
					else
						{
						$db->db_query("insert into ".BAB_FM_FIELDSVAL_TBL." set fvalue='".$fval."', id_file='".$idf."', id_field='".$arrf['id']."'");
						}
					}
				}
			}

		$rr = $db->db_fetch_array($db->db_query("select filenotify, id_dgowner from ".BAB_FM_FOLDERS_TBL." where id='".$arr['id_owner']."'"));
		if( empty($bnotify))
			{
			$bnotify = $rr['filenotify'];
			}
		if( $arr['bgroup'] == "Y" )
			{
			if( $arr['confirmed'] == "N")
				{
				include_once $GLOBALS['babInstallPath']."utilit/afincl.php";
				$res = updateFlowInstance($arr['idfai'], $GLOBALS['BAB_SESS_USERID'], $confirm == "Y"? true: false);
				switch($res)
					{
					case 0:
						deleteFile($arr['id'], $arr['name'], $pathx);
						unlink($pathx.$arr['name']);
						notifyFileAuthor(bab_translate("Your file has been refused"),"", $arr['author'], $arr['name']);
						break;
					case 1:
						deleteFlowInstance($arr['idfai']);
						$db->db_query("update ".BAB_FILES_TBL." set confirmed='Y', idfai='0' where id = '".$arr['id']."'");
						if( $confirmed == "Y" )
							{
							$GLOBALS['babWebStat']->addNewFile($rr['id_dgowner']);
							}
						notifyFileAuthor(bab_translate("Your file has been accepted"),"", $arr['author'], $arr['name']);
						if( $bnotify == "Y")
							{
							fileNotifyMembers($arr['name'], $arr['path'], $arr['id_owner'], bab_translate("A new file has been uploaded"));
							}
						break;
					default:
						$nfusers = getWaitingApproversFlowInstance($arr['idfai'], true);
						if( count($nfusers) > 0 )
							notifyFileApprovers($arr['id'], $nfusers, bab_translate("A new file is waiting for you"));
						break;
					}
				}
			else if( $bnotify == "Y" && $bmodified)
				fileNotifyMembers($arr['name'], $arr['path'], $arr['id_owner'], bab_translate("File has been updated"));
			}
		return true;
		}
	}

function createDirectory($dirname, $id, $gr, $path)
	{
	global $babBody, $BAB_SESS_USERID;
	
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

	if( empty($dirname))
		{
		$babBody->msgerror = bab_translate("Please give a valid directory name");
		return false;
		}

	$dirname = trim($dirname);

	if( bab_isMagicQuotesGpcOn())
		{
		$dirname = stripslashes($dirname);
		}

	if( isset($GLOBALS['babFileNameTranslation']))
		$dirname = strtr($dirname, $GLOBALS['babFileNameTranslation']);

	$pathx = bab_getUploadFullPath($gr, $id).$path."/".$dirname;

	if( is_dir($pathx))
		{
		$babBody->msgerror = bab_translate("This folder already exists");
		return false;
		}
	else
		{
		bab_mkdir($pathx, $GLOBALS['babMkdirMode']);
		}
	}

function renameDirectory($dirname, $id, $gr, $path)
	{
	global $babBody, $BAB_SESS_USERID, $aclfm;
	if( empty($path))
		return false;

	if( empty($dirname))
		{
		$babBody->msgerror = bab_translate("Please give a valid directory name");
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


	if( bab_isMagicQuotesGpcOn())
		{
		$dirname = stripslashes($dirname);
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
			$db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_FILES_TBL." where id_owner='".$id."' and bgroup='".$gr."'";
			$res = $db->db_query($req);
			while( $arr = $db->db_fetch_array($res))
				{
				if( substr($arr['path'], 0, $len) == $path )
					{
					$req = "update ".BAB_FILES_TBL." set path='".addslashes(str_replace($path, $uppath.$dirname, $arr['path']))."' where id='".$arr['id']."'";
					$db->db_query($req);
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
	global $babBody, $BAB_SESS_USERID, $aclfm;
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
		$db = $GLOBALS['babDB'];
		$req = "select * from ".BAB_FILES_TBL." where id_owner='".$id."' and bgroup='".$gr."' and path='".addslashes($path)."'";
		$res = $db->db_query($req);
		while( $arr = $db->db_fetch_array($res))
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

function getFile( $file, $id, $gr, $path, $inl)
	{
	global $babBody, $BAB_SESS_USERID;
	$access = false;

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
		$db = $GLOBALS['babDB'];
		$req = "select * from ".BAB_FILES_TBL." where id_owner='".$id."' and bgroup='".$gr."' and path='".addslashes($path)."' and name='".addslashes($file)."'";
		$res = $db->db_query($req);
		if( $res && $db->db_num_rows($res) > 0 )
			{
			$arr = $db->db_fetch_array($res);
			$db->db_query("update ".BAB_FILES_TBL." set hits='".($arr['hits'] + 1)."' where id='".$arr['id']."'");
			$access = true;
			}
		else
			$access = false;
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
	while (!feof($fp)) {
		print fread($fp, 8192);
		}
	fclose($fp);
	}


function cutFile( $file, $id, $gr, $path, $bmanager)
	{
	global $babBody;

	if( !$bmanager)
		{
		$babBody->msgerror = bab_translate("Access denied");
		return false;
		}
	$db = $GLOBALS['babDB'];
	$req = "update ".BAB_FILES_TBL." set state='X' where id_owner='".$id."' and bgroup='".$gr."' and state='' and path='".addslashes($path)."' and name='".$file."'";
	$res = $db->db_query($req);
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

	if (!bab_isMagicQuotesGpcOn())
		{
		$path = $babDB->db_escape_string($path);
		$file = $babDB->db_escape_string($file);
		}
	
	$db = $GLOBALS['babDB'];
	$req = "update ".BAB_FILES_TBL." set state='D' where id_owner='".$id."' and bgroup='".$gr."' and state='' and path='".addslashes($path)."' and name='".$file."'";
	$res = $db->db_query($req);
	return true;
	}

function pasteFile( $file, $id, $gr, $path, $tp, $bmanager)
	{
	global $babBody;

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
			$db = $GLOBALS['babDB'];
			$req = "update ".BAB_FILES_TBL." set state='' where id_owner='".$id."' and bgroup='".$gr."' and path='".addslashes($path)."' and name='".addslashes($file)."'";
			$res = $db->db_query($req);
			return true;
			}
		$babBody->msgerror = bab_translate("A file with the same name already exists");
		return false;
		}

	if( rename( $pathx.$path."/".$file, $pathx.$tp."/".$file))
		{
		$db = $GLOBALS['babDB'];
		list($idf) = $db->db_fetch_row($db->db_query("select id from ".BAB_FILES_TBL." where id_owner='".$id."' and bgroup='".$gr."' and path='".addslashes($path)."' and name='".addslashes($file)."'"));

		$db->db_query("update ".BAB_FILES_TBL." set state='', path='".addslashes($tp)."' where id='".$idf."'");

		if( is_dir($pathx.$path."/".BAB_FVERSION_FOLDER."/"))
			{
			if( !is_dir($pathx.$tp."/".BAB_FVERSION_FOLDER))
				bab_mkdir($pathx.$tp."/".BAB_FVERSION_FOLDER, $GLOBALS['babMkdirMode']);

			$res = $db->db_query("select * from ".BAB_FM_FILESVER_TBL." where id_file='".$idf."'");
			while($arr = $db->db_fetch_array($res))
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

				$this->id = $arr['id_owner'];
				$this->gr = $arr['bgroup'];
				$this->path = $arr['path'];
				$this->file = $arr['name'];
				$GLOBALS['babBody']->title = $arr['name'].( ($bversion == 'Y') ? " (".$arr['ver_major'].".".$arr['ver_minor'].")" : "" );
				$this->descval = $arr['description'];
				$this->keysval = $arr['keywords'];
				$this->descvalhtml = htmlentities($arr['description']);
				$this->keysvalhtml = htmlentities($arr['keywords']);

				$this->fsizetxt = bab_translate("Size");
				$fullpath = bab_getUploadFullPath($arr['bgroup'], $arr['id_owner']).$arr['path']."/".$arr['name'];
				$fstat = stat($fullpath);
				$this->fsize = bab_formatSizeFile($fstat[7])." ".bab_translate("Kb")." ( ".bab_formatSizeFile($fstat[7], false) ." ".bab_translate("Bytes") ." )";
				
				$this->fmodifiedtxt = bab_translate("Modified");
				$this->fmodified = bab_shortDate(bab_mktime($arr['modified']), true);
				$this->fmodifiedbytxt = bab_translate("Modified by");
				$this->fmodifiedby = bab_getUserName($arr['modifiedby']);
				$this->fcreatedtxt = bab_translate("Created");
				$this->fcreated = bab_shortDate(bab_mktime($arr['created']), true);
				$this->fpostedbytxt = bab_translate("Posted by");
				$this->fpostedby = bab_getUserName($arr['modifiedby'] == 0? $arr['author']: $arr['modifiedby']);

				$this->geturl = $GLOBALS['babUrlScript']."?tg=fileman&idx=get&id=".$arr['id_owner']."&gr=".$arr['bgroup']."&path=".urlencode($arr['path'])."&file=".urlencode($arr['name']);
				$this->download = bab_translate("Download");

				$this->file = bab_translate("File");
				$this->name = bab_translate("Name");
				$this->nameval = $arr['name'];
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
				if( $arr['confirmed'] == "Y")
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


				$db = $GLOBALS['babDB'];
				$rr = $db->db_fetch_array($db->db_query("select filenotify, version from ".BAB_FM_FOLDERS_TBL." where id='".$arr['id_owner']."'"));

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
					$res = $db->db_query("select id, folder from ".BAB_FM_FOLDERS_TBL." where id !='".$arr['id_owner']."'");
					while($arrf = $db->db_fetch_array($res))
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
					$this->resff = $db->db_query("select * from ".BAB_FM_FIELDS_TBL." where id_folder='".$arr['id_owner']."'");
					$this->countff = $db->db_num_rows($this->resff);
					}
				else
					$this->countff = 0;

				// indexation

				if ($engine = bab_searchEngineInfos()) {
						
						$this->index = true;
						$this->index_status = $arr['index_status'];
						$this->t_index_status = bab_translate("Index status");

						$this->index_onload = $engine['indexes']['bab_files']['index_onload'];

						if (isset($_POST['index_status'])) {
							// modify status

							$db->db_query(
									"UPDATE ".BAB_FILES_TBL." SET index_status='".$db->db_escape_string($_POST['index_status'])."' WHERE id='".$_POST['idf']."'"
								);

							$files_to_index = array($fullpath);

							if (isset($_POST['change_all']) && 1 == $_POST['change_all']) {
								// modifiy index status for older versions
								$res = $db->db_query("SELECT id, ver_major, ver_minor FROM ".BAB_FM_FILESVER_TBL." WHERE id_file='".$db->db_escape_string($_POST['idf'])."'");
								while ($arrfv = $db->db_fetch_assoc($res)) {
									
									$db->db_query(
										"UPDATE ".BAB_FM_FILESVER_TBL." SET index_status='".$db->db_escape_string($_POST['index_status'])."' WHERE id='".$arrfv['id']."'"
									);

									if ($this->index_onload && BAB_INDEX_STATUS_INDEXED == $_POST['index_status']) {
										
										$files_to_index[] = bab_getUploadFullPath($arr['bgroup'], $arr['id_owner']).$arr['path']."/OVF/".$arrfv['ver_major'].','.$arrfv['ver_minor'].','.$arr['name'];
									}
								}
							}

							

							if ($this->index_onload && BAB_INDEX_STATUS_INDEXED == $_POST['index_status']) {
								$this->index_status = bab_indexOnLoadFiles($files_to_index , 'bab_files');
								if (BAB_INDEX_STATUS_INDEXED === $this->index_status) {
									foreach($files_to_index as $f) {
										$obj = new bab_indexObject($object);
										$obj->setIdObjectFile($f, $idf, $id);
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
			global $babDB;
			static $i=0;
			if( $i < $this->countfm )
				{
				$this->folder = $this->arrfolders[$i]['folder'];
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
				$res = $babDB->db_query("select fvalue from ".BAB_FM_FIELDSVAL_TBL." where id_field='".$arr['id']."' and id_file='".$this->idf."'");
				if( $res && $babDB->db_num_rows($res) > 0)
					{
					list($this->fieldval) = $babDB->db_fetch_array($res);
					$this->fieldvalhtml = htmlentities($this->fieldval);
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
			static $arr = array(BAB_INDEX_STATUS_NOINDEX, BAB_INDEX_STATUS_INDEXED, BAB_INDEX_STATUS_TOINDEX);
			if (list(,$this->value) = each($arr)) {
				$this->disabled=false;
				$this->option = bab_toHtml(bab_getIndexStatusLabel($this->value));
				$this->selected = $this->index_status == $this->value;
				if (BAB_INDEX_STATUS_INDEXED == $this->value && !$this->index_onload) {
					$this->disabled=true;
				}
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
	$db = $GLOBALS['babDB'];
	$req = "select * from ".BAB_FILES_TBL." where id='".$idf."' and state=''";
	$res = $db->db_query($req);
	if( $res && $db->db_num_rows($res) > 0 )
		{
		$arr = $db->db_fetch_array($res);
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

			list($bversion) = $db->db_fetch_row($db->db_query("select version from ".BAB_FM_FOLDERS_TBL." where id='".$arr['id_owner']."'"));
			if(  $arr['edit'] != 0 || $bversion ==  'Y')
				{
				$bupdate = false;
				}
			}
		}
	


	include_once $GLOBALS['babInstallPath']."utilit/uiutil.php";
	$GLOBALS['babBodyPopup'] = new babBodyPopup();
	$GLOBALS['babBodyPopup']->title = & $babBody->title;
	$GLOBALS['babBodyPopup']->msgerror = & $babBody->msgerror;

	$temp = new temp($idf, $arr, $bmanager, $access, $bconfirm, $bupdate, $bdownload,$bversion);

	$GLOBALS['babBodyPopup']->babecho(bab_printTemplate($temp,"fileman.html", "viewfile"));
	printBabBodyPopup();
	die();
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
			$path = str_replace("'", "\'", $path);
			$path = str_replace('"', "'+String.fromCharCode(34)+'",$path);
			$this->redirecturl = $GLOBALS['babUrlScript']."?tg=fileman&idx=list&id=".$id."&gr=".$gr."&path=".urlencode($path);
			}
		}

	$temp = new temp($id, $gr, $path);
	echo bab_printTemplate($temp,"fileman.html", "fileunload");
	}

function deleteFiles($items, $gr, $id)
	{
	$db = $GLOBALS['babDB'];	
	$pathx = bab_getUploadFullPath($gr, $id);
	for( $i = 0; $i < count($items); $i++)
		{
		$res = $db->db_query("select * from ".BAB_FILES_TBL." where id='".$items[$i]."'");
		if( $res && $db->db_num_rows($res) > 0 )
			{
			$arr = $db->db_fetch_array($res);
			if( file_exists($pathx.$arr['path']."/".$arr['name']))
				{
				if( unlink($pathx.$arr['path']."/".$arr['name']))
					{
					deleteFile($items[$i], $arr['name'], $pathx.$arr['path']."/");
					}

				}
			}
		}
	}

function restoreFiles($items)
	{
	$db = $GLOBALS['babDB'];	
	for( $i = 0; $i < count($items); $i++)
		{
		$arr = $db->db_fetch_array($db->db_query("select * from ".BAB_FILES_TBL." where id='".$items[$i]."'"));
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
		$db->db_query("update ".BAB_FILES_TBL." set state='' where id='".$items[$i]."'");
		}
	}

function autoFile($id_dir,$path)
	{
	$path = urldecode($path);
	$db = & $GLOBALS['babDB'];


	class FolderListing {
	   var $newarr = array();

	   function loop($stack) {
		   if(count($stack) > 0) {
			   $arr = array();
			   foreach($stack as $key => $value) {
				   array_push($this->newarr, $stack[$key]);
				   if ($dir = @opendir($stack[$key])) {
					   while (($file = readdir($dir)) !== false) {
						   if (($file != '.') && ($file != '..')) {
							   array_push($arr, $stack[$key].'/'.$file);
						   }
					   }
				   }
				   @closedir($dir);
			   }
			   $this->loop($arr);
		   } else {
			   $sorted = sort($this->newarr);
			   return($sorted);
		   }
	   }
	}

	$start = new FolderListing;
	$full = $GLOBALS['babUploadPath'].'/G'.$id_dir.'/';
	$base = array($full.trim($path,'/'));
	$start->loop($base);

	$filelist = array();
	$res = $db->db_query("SELECT CONCAT(path,'/',name) FROM ".BAB_FILES_TBL." WHERE id_owner='".$id_dir."'");
	while (list($name) = $db->db_fetch_array($res))
		{
		$filelist[trim($name,'/')] = 1;
		}

	header("content-type:text/plain");

	$i = 0;

	foreach($start->newarr as $value) {

		if (is_file($value))
			{
			$filepath = trim(substr($value,strlen($full)),'/');

			if (!isset($filelist[$filepath]))
				{

				$path = dirname($filepath);
				if ($path == '.')
					$path = '';
				
				$db->db_query("INSERT INTO ".BAB_FILES_TBL." (name, path, id_owner, bgroup, created, author, modified, modifiedby, confirmed) VALUES ('".basename($value)."','".$path."','".$id_dir."','Y',NOW(),'".$GLOBALS['BAB_SESS_USERID']."', NOW(),'".$GLOBALS['BAB_SESS_USERID']."','Y' )");

				echo $db->db_insert_id().', '.basename($value)."\n";

				$i++;
				}
			}
		} 

	die('inserted files : '.$i);
	}

	
/* main */


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


$idx = isset($_REQUEST['idx']) ? $_REQUEST['idx'] : "list";

if(!isset($_REQUEST['path']))
	{
	$path = "";
	}
else
	{
	if( strstr($_REQUEST['path'], ".."))
		{
		$babBody->msgerror = bab_translate("Access denied");
		return;
		}

	if( bab_isMagicQuotesGpcOn())
		$path = stripslashes($_REQUEST['path']);

	$path = urldecode($_REQUEST['path']);
	}


if( !empty($BAB_SESS_USERID) && $babBody->ustorage)
	{
	$id = isset($_REQUEST['id']) ? $_REQUEST['id'] : $BAB_SESS_USERID;
	}
else
	{
	$id = isset($_REQUEST['id']) ? $_REQUEST['id'] : 0;
	}

$gr = isset($_REQUEST['gr']) ? $_REQUEST['gr'] :  "N";


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

if( isset($_POST['addf'])) {
	if( $_POST['addf'] == "add" )
		{
		if( !saveFile(
				$id, 
				$gr, 
				$path, 
				$_FILES['uploadf']['name'], 
				$_FILES['uploadf']['size'],
				$_FILES['uploadf']['tmp_name'] , 
				$_POST['description'], 
				$_POST['keywords'], 
				$_POST['readonly'])
			) {

			$idx = "add";
		}
	}
}

if( isset($updf) && $updf == "upd")
	{
	if( isset($description ))
		$descup = true;
	else
		$descup = false;
	if( !isset($uploadf_name)) { $uploadf_name = '';}
	if( !isset($uploadf_size)) { $uploadf_size = 0;}
	if( !isset($uploadf)) { $uploadf = '';}
	if( !isset($fname)) { $fname = '';}
	if( !isset($description)) { $description = '';}
	if( !isset($keywords)) { $keywords = '';}
	if( !isset($readonly)) { $readonly = '';}
	if( !isset($confirm)) { $confirm = '';}
	if( !isset($bnotify)) { $bnotify = '';}
	if( !isset($newfolder)) { $newfolder = '';}
	if( !isset($descup)) { $descup = '';}
	if( !saveUpdateFile($idf, $uploadf_name, $uploadf_size,$uploadf, $fname, $description, $keywords, $readonly, $confirm, $bnotify, $newfolder, $descup))
		$idx = "viewfile";
	else
		{
		$idx = "unload";
		}
	}

if( isset($mkdir) && $mkdir == "mkdir")
	{
	if( !empty($create))
		createDirectory($dirname, $id, $gr, $path);
	else if( !empty($rename))
		renameDirectory($dirname, $id, $gr, $path);
	else if( !empty($bdel))
		removeDirectory($id, $gr, $path);
	}

if( $idx == "paste")
	{
	if( pasteFile($file, $id, $gr, $path, $tp, $bmanager))
		{
		$path = $tp;
		if( bab_isMagicQuotesGpcOn())
			$path = stripslashes($path);
		}
	$idx = "list";
	}

if( $idx == "del")
	{
	delFile($file, $id, $gr, $path, $bmanager);
	$idx = "list";
	}

if( isset($cdel) && $cdel == "update")
{
	if( !empty($delete))
		deleteFiles($items, $gr, $id);
	else
		restoreFiles($items);
}

if(!isset($editor))
	$editor = 'none';
switch($idx)
	{
	case "brow":
		browseFiles($id, $gr, $path, $bmanager, $editor);
		exit;
		break;

	case "unload":
		fileUnload($id, $gr, $path);
		exit;
		break;

	case "viewfile":
		viewFile($idf);
		exit;
		break;

	case "get":
		if(!isset($inl)) { $inl ='';}
		getFile($file, $id, $gr, $path, $inl);
		exit;
		break;

	case "auto":
		autoFile($_GET['id'],$_GET['path']);
		break;

	case "trash":
		$babBody->title = bab_translate("Trash");
		listTrashFiles($id, $gr);
		$upath = urlencode($path);
		$babBody->addItemMenu("list", bab_translate("Folders"), $GLOBALS['babUrlScript']."?tg=fileman&idx=list&id=".$id."&gr=".$gr."&path=".$upath);
		if( $upload)
			$babBody->addItemMenu("add", bab_translate("Upload"), $GLOBALS['babUrlScript']."?tg=fileman&idx=add&id=".$id."&gr=".$gr."&path=".$upath);
		if( $bmanager)
			$babBody->addItemMenu("trash", bab_translate("Trash"), $GLOBALS['babUrlScript']."?tg=fileman&idx=trash&id=".$id."&gr=".$gr."&path=".$upath);
		break;

	case "add":
		$babBody->title = bab_translate("Upload file to")." ";
		if( $gr == 'Y' )
			{
			$babBody->title .= bab_getFolderName($id);
			}
		$babBody->title .= "/".$path;
		$upath = urlencode($path);
		if (!isset($description)) $description='';
		if (!isset($keywords)) $keywords='';
		addFile($id, $gr, $path, $description, $keywords);
		$babBody->addItemMenu("list", bab_translate("Folders"), $GLOBALS['babUrlScript']."?tg=fileman&idx=list&id=".$id."&gr=".$gr."&path=".$upath);
		if( $upload)
			$babBody->addItemMenu("add", bab_translate("Upload"), $GLOBALS['babUrlScript']."?tg=fileman&idx=add&id=".$id."&gr=".$gr."&path=".$upath);
		if( $bmanager)
			$babBody->addItemMenu("trash", bab_translate("Trash"), $GLOBALS['babUrlScript']."?tg=fileman&idx=trash&id=".$id."&gr=".$gr."&path=".$upath);
		break;

	case "disk":
		$babBody->title = bab_translate("File manager");
		$upath = urlencode($path);
		showDiskSpace($id, $gr, $path);
		$babBody->addItemMenu("list", bab_translate("Folders"), $GLOBALS['babUrlScript']."?tg=fileman&idx=list&id=".$id."&gr=".$gr."&path=".$upath);
		if( $upload)
			$babBody->addItemMenu("add", bab_translate("Upload"), $GLOBALS['babUrlScript']."?tg=fileman&idx=add&id=".$id."&gr=".$gr."&path=".$upath);
		if( $bmanager)
			$babBody->addItemMenu("trash", bab_translate("Trash"), $GLOBALS['babUrlScript']."?tg=fileman&idx=trash&id=".$id."&gr=".$gr."&path=".$upath);
		break;

	case "cut":
		cutFile($file, $id, $gr, $path, $bmanager);
		/* no break */
	default:
	case "list":
		$babBody->title = bab_translate("File manager");
		$upath = urlencode($path);
		$babBody->addItemMenu("list", bab_translate("Folders"), $GLOBALS['babUrlScript']."?tg=fileman&idx=list&id=".$id."&gr=".$gr."&path=".$upath);
		if( $upload)
			$babBody->addItemMenu("add", bab_translate("Upload"), $GLOBALS['babUrlScript']."?tg=fileman&idx=add&id=".$id."&gr=".$gr."&path=".$upath);
		if( $bmanager)
			$babBody->addItemMenu("trash", bab_translate("Trash"), $GLOBALS['babUrlScript']."?tg=fileman&idx=trash&id=".$id."&gr=".$gr."&path=".$upath);
		listFiles($id, $gr, $path, $bmanager);
		if( !empty($id) && $gr == "Y")
			{
			$GLOBALS['babWebStat']->addFolder($id);
			}
		break;
	}
$babBody->setCurrentItemMenu($idx);
?>