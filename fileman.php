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
include $babInstallPath."utilit/fileincl.php";

function notifyApprovers($id, $fid)
	{
	global $babDB;
	include_once $GLOBALS['babInstallPath']."utilit/afincl.php";

	$arr = $babDB->db_fetch_array($babDB->db_query("select idsa from ".BAB_FM_FOLDERS_TBL." where id='".$fid."'"));
	if( $arr['idsa'] == 0 )
		{
		$babDB->db_query("update ".BAB_FILES_TBL." set confirmed='Y' where id='".$id."'");
		return true;
		}

	$idfai = makeFlowInstance($arr['idsa'], "fil-".$id);
	$babDB->db_query("update ".BAB_FILES_TBL." set idfai='".$idfai."' where id='".$id."'");
	$nfusers = getWaitingApproversFlowInstance($idfai, true);
	notifyFileApprovers($id, $nfusers);
	return false;
	}

function deleteFile($idf)
	{
	global $babDB;
	$babDB->db_query("delete from ".BAB_FM_FIELDSVAL_TBL." where id_file='".$idf."'");
	$db->db_query("delete from ".BAB_FILES_TBL." where id='".$idf."'");
	}

class listFiles
	{
	var $db;
	var $res;
	var $count;
	var $fullpath;
	var $id;
	var $gr;

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

		$this->db = $GLOBALS['babDB'];
		$this->bmanager = $bmanager;
		$this->countwf = 0;
		for( $i = 0; $i < count($babBody->aclfm['id']); $i++)
			{
			if( $babBody->aclfm['ma'][$i] == 1 )
				$this->arrmgrp[] = $babBody->aclfm['id'][$i];
			else
				$this->arrgrp[] = $babBody->aclfm['id'][$i];

			if( $babBody->aclfm['id'][$i] == $id )
				{
				$this->bdownload = $babBody->aclfm['down'][$i];

				if( $what == "list" && $gr == "Y" && $babBody->aclfm['idsa'][$i] != 0 && isUserApproverFlow($babBody->aclfm['idsa'][$i], $BAB_SESS_USERID) )
					{
					$req = "select ".BAB_FILES_TBL.".* from ".BAB_FILES_TBL." join ".BAB_FAR_INSTANCES_TBL." where id_owner='".$id."' and bgroup='".$gr."' and state='' and path='".addslashes($path)."' and confirmed='N' and ".BAB_FAR_INSTANCES_TBL.".idschi=".BAB_FILES_TBL.".idfai and ".BAB_FAR_INSTANCES_TBL.".iduser='".$BAB_SESS_USERID."' and ".BAB_FAR_INSTANCES_TBL.".result='' and  ".BAB_FAR_INSTANCES_TBL.".notified='Y'";
					$this->reswf = $this->db->db_query($req);
					$this->countwf = $this->db->db_num_rows($this->reswf);
					}
				}
			}

		if( !$this->bdownload )
			$this->bdownload = $bmanager? true: false;

		if( $gr == "Y" || ($gr == "N" && !empty($path)))
			{
			$this->countmgrp = 0;
			$this->countgrp = 0;
			}
		else 
			{
			$this->countmgrp = count($this->arrmgrp);
			$this->countgrp = count($this->arrgrp);
			}

		if( !empty($path))
			{
			$i = strrpos($path, "/");
			if( !$i )
				$p = "";
			else
				$p = substr( $path, 0, $i);
			$this->arrdir[] = ". .";
			$this->arrudir[] = $GLOBALS['babUrlScript']."?tg=fileman&idx=".$what."&id=".$id."&gr=".$gr."&path=".$p;
			}

		if( $id != 0  && is_dir($this->fullpath.$path."/"))
			{
			$h = opendir($this->fullpath.$path."/");
			while (($f = readdir($h)) != false)
				{
				if ($f != "." and $f != "..") 
					{
					if (is_dir($this->fullpath.$path."/".$f))
						{
						$this->arrdir[] = $f;
						$this->arrudir[] = $GLOBALS['babUrlScript']."?tg=fileman&idx=".$what."&id=".$id."&gr=".$gr."&path=".$path.($path ==""?"":"/").$f;
						}
					}
				}
			closedir($h);
			$req = "select * from ".BAB_FILES_TBL." where id_owner='".$id."' and bgroup='".$gr."' and state='' and path='".addslashes($path)."' and confirmed='Y'";
			$req .= " order by name asc";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			}
		else
			$this->count = 0;
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

				$this->modified = date("d/m/Y H:i", bab_mktime($arr['modified']));
				$this->postedby = bab_getUserName($arr['author']);
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
				$this->groupname = bab_translate("Personnal Folder");
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
			$this->upfolderimg = bab_printTemplate($this, "config.html", "parentfolder");
			$this->usrfolderimg = bab_printTemplate($this, "config.html", "userfolder");
			$this->grpfolderimg = bab_printTemplate($this, "config.html", "groupfolder");
			$this->manfolderimg = bab_printTemplate($this, "config.html", "managerfolder");
			if( $gr == "Y")
				$this->rootpath = bab_getFolderName($id);
			else
				$this->rootpath = "";
			$this->rooturl = $GLOBALS['babUrlScript']."?tg=fileman&idx=brow&id=".$BAB_SESS_USERID."&gr=N&path=&editor=".$this->editor;
			$this->refreshurl = $GLOBALS['babUrlScript']."?tg=fileman&idx=brow&id=".$id."&gr=".$gr."&path=".$path."&editor=".$this->editor;
			}

		function getnextdir()
			{
			static $i = 0;
			if( $i < count($this->arrdir))
				{
				$this->name = $this->arrdir[$i];
				$this->url = $this->arrudir[$i]."&editor=".$this->editor;
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
				$this->name = bab_getFolderName($this->arrgrp[$m]);
				$this->url = $GLOBALS['babUrlScript']."?tg=fileman&idx=brow&id=".$this->arrgrp[$m]."&gr=Y&path=&editor=".$this->editor;
				$m++;
				return true;
				}
			else
				return false;
			}

		function getnextmgrpdir()
			{
			static $m = 0;
			if( $m < $this->countmgrp)
				{
				$this->name = bab_getFolderName($this->arrmgrp[$m]);
				$this->url = $GLOBALS['babUrlScript']."?tg=fileman&idx=brow&id=".$this->arrmgrp[$m]."&gr=Y&path=&editor=".$this->editor;
				$m++;
				return true;
				}
			else
				return false;
			}

		function getnextfile()
			{
			static $i = 0;
			if( $i < $this->count)
				{
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

		function temp($id, $gr, $path, $bmanager)
			{
			global $BAB_SESS_USERID;
			$this->listFiles($id, $gr, $path, $bmanager);
			$this->bytes = bab_translate("bytes");
			$this->mkdir = bab_translate("Create");
			$this->rename = bab_translate("Rename");
			$this->delete = bab_translate("Delete directory");
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
			$this->refreshurl = $GLOBALS['babUrlScript']."?tg=fileman&idx=list&id=".$id."&gr=".$gr."&path=".$path;
			$this->urldiskspace = $GLOBALS['babUrlScript']."?tg=fileman&idx=disk&id=".$id."&gr=".$gr."&path=".$this->jpath;

			$this->upfolderimg = bab_printTemplate($this, "config.html", "parentfolder");
			$this->usrfolderimg = bab_printTemplate($this, "config.html", "userfolder");
			$this->grpfolderimg = bab_printTemplate($this, "config.html", "groupfolder");
			$this->manfolderimg = bab_printTemplate($this, "config.html", "managerfolder");

			if( $gr == "Y")
				$this->rootpath = bab_getFolderName($id);
			else
				$this->rootpath = "";
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

		function getnextgrpdir()
			{
			static $m = 0;
			if( $m < $this->countgrp)
				{
				$this->name = bab_getFolderName($this->arrgrp[$m]);
				$this->url = $GLOBALS['babUrlScript']."?tg=fileman&idx=list&id=".$this->arrgrp[$m]."&gr=Y&path=";
				$m++;
				return true;
				}
			else
				return false;
			}

		function getnextmgrpdir()
			{
			static $m = 0;
			if( $m < $this->countmgrp)
				{
				$this->name = bab_getFolderName($this->arrmgrp[$m]);
				$this->url = $GLOBALS['babUrlScript']."?tg=fileman&idx=list&id=".$this->arrmgrp[$m]."&gr=Y&path=";
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

			$this->modified = date("d/m/Y H:i", bab_mktime($arr['modified']));
			$this->postedby = bab_getUserName($arr['author']);
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
		if(!is_dir($pathx) && !mkdir($pathx, 0700))
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

function notifyFileApprovers($id, $users)
	{
	global $babBody, $BAB_SESS_USER, $BAB_SESS_EMAIL, $babAdminEmail, $babInstallPath;
    include $babInstallPath."utilit/mailincl.php";

	class tempa
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


		function tempa($id)
			{
            global $babDB, $BAB_SESS_USER, $BAB_SESS_EMAIL;
			$arr = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_FILES_TBL." where id='".$id."'"));
            $this->filename = $arr['name'];
            $this->message = bab_translate("A new file is waiting for you");
            $this->from = bab_translate("Author");
            $this->path = bab_translate("Path");
            $this->file = bab_translate("File");
            $this->group = bab_translate("Folder");
            $this->pathname = $arr['path'] == ""? "/": $arr['path'];
            $this->groupname = bab_getFolderName($arr['id_owner']);
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
    $mail = bab_mail();
	if( $mail == false )
		return;
	
	for( $i=0; $i < count($users); $i++)
		$mail->mailTo(bab_getUserEmail($users[$i]), bab_getUserName($users[$i]));
    $mail->mailFrom($babAdminEmail, bab_translate("Ovidentia Administrator"));
    $mail->mailSubject(bab_translate("New waiting file"));

	$tempa = new tempa($id);
	$message = bab_printTemplate($tempa,"mailinfo.html", "filewait");
    $mail->mailBody($message, "html");

	$message = bab_printTemplate($tempa,"mailinfo.html", "filewaittxt");
    $mail->mailAltBody($message);

	$mail->send();
	}

function notifyMembers($file, $path, $idgrp, $bnew)
	{
	global $babBody, $BAB_SESS_USER, $BAB_SESS_EMAIL, $babAdminEmail, $babInstallPath;
    include $babInstallPath."utilit/mailincl.php";

	class tempb
		{
		var $filename;
		var $message;
        var $author;
        var $path;
        var $pathname;
        var $file;
        var $site;
        var $date;
        var $dateval;
		var $group;
		var $groupname;


		function tempb($file, $path, $idgrp, $bnew)
			{
            $this->filename = $file;
			if( $bnew )
	            $this->message = bab_translate("A new file has been uploaded");
			else
	            $this->message = bab_translate("File has been updated");

            $this->path = bab_translate("Path");
            $this->file = bab_translate("File");
            $this->group = bab_translate("Folder");
            $this->pathname = $path == ""? "/": $path;
            $this->groupname = bab_getFolderName($idgrp);
            $this->site = bab_translate("Web site");
            $this->date = bab_translate("Date");
            $this->dateval = bab_strftime(mktime());
			}
		}

	$mail = bab_mail();
	if( $mail == false )
		return;

	$mail->mailTo($babAdminEmail, bab_translate("Ovidentia Administrator"));
	$mail->mailFrom($babAdminEmail, bab_translate("Ovidentia Administrator"));

	if( $bnew )
		$mail->mailSubject(bab_translate("New file"));
	else
		$mail->mailSubject(bab_translate("File has been updated"));

	$tempa = new tempb($file, $path, $idgrp, $bnew);
	$message = bab_printTemplate($tempa,"mailinfo.html", "fileuploaded");

	$messagetxt = bab_printTemplate($tempa,"mailinfo.html", "fileuploadedtxt");

	$db = $GLOBALS['babDB'];
	$res = $db->db_query("select id_group from ".BAB_FMDOWNLOAD_GROUPS_TBL." where  id_object='".$idgrp."'");
	if( $res && $db->db_num_rows($res) > 0 )
		{
		while( $row = $db->db_fetch_array($res))
			{

			switch($row['id_group'])
				{
				case 0:
				case 1:
					$res2 = $db->db_query("select id, email, firstname, lastname from ".BAB_USERS_TBL." where is_confirmed='1' and disabled='0'");
					break;
				case 2:
					return;
				default:
					$res2 = $db->db_query("select ".BAB_USERS_TBL.".id, ".BAB_USERS_TBL.".email, ".BAB_USERS_TBL.".firstname, ".BAB_USERS_TBL.".lastname from ".BAB_USERS_TBL." join ".BAB_USERS_GROUPS_TBL." where is_confirmed='1' and disabled='0' and ".BAB_USERS_GROUPS_TBL.".id_group='".$row['id_group']."' and ".BAB_USERS_GROUPS_TBL.".id_object=".BAB_USERS_TBL.".id");
					break;
				}

			if( $res2 && $db->db_num_rows($res2) > 0 )
				{
				$count = 0;
				while(($arr = $db->db_fetch_array($res2)))
					{
					$mail->mailBcc($arr['email'], bab_composeUserName($arr['firstname'],$arr['lastname']));
					$count++;
					if( $count == 25 )
						{
						$mail->mailBody($message, "html");
						$mail->mailAltBody($messagetxt);
						$mail->send();
						$mail->clearBcc();
						$count = 0;
						}
					}

				if( $count > 0 )
					{
					$mail->mailBody($message, "html");
					$mail->mailAltBody($messagetxt);
					$mail->send();
					}
				}
			}
		}
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

	$name = $filename;
	$mqgo = bab_isMagicQuotesGpcOn();
	if( !$mqgo)
		{
		$name = addslashes($filename);
		$description = addslashes($description);
		$keywords = addslashes($keywords);
		}

	$db = $GLOBALS['babDB'];
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
		}

	if( $bexist)
		{
		$req = "update ".BAB_FILES_TBL." set description='".$description."', keywords='".$keywords."', readonly='".$readonly."', confirmed='".$confirmed."', modified=now(), hits='0', modifiedby='".$idcreator."', state='' where id='".$arr['id']."'";
		$db->db_query($req);
		$idf = $arr['id'];
		}
	else
		{
		$req = "insert into ".BAB_FILES_TBL." (name, description, keywords, path, id_owner, bgroup, link, readonly, state, created, author, modified, modifiedby, confirmed) values ";
		$req .= "('" .$name. "', '" . $description. "', '" . $keywords. "', '" . addslashes($path). "', '" . $id. "', '" . $gr. "', '0', '" . $readonly. "', '', now(), '" . $idcreator. "', now(), '" . $idcreator. "', '". $confirmed."')";
		$db->db_query($req);
		$idf = $db->db_insert_id(); 
		}

	if( $gr == 'Y')
		{
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
			notifyMembers($filename, $path, $id, true);
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
						$res = $db->db_query("select ".BAB_FILES_TBL.".id from ".BAB_FILES_TBL." join ".BAB_FAR_INSTANCES_TBL." where ".BAB_FILES_TBL.".id='".$arr['id']."' and ".BAB_FAR_INSTANCES_TBL.".idschi=".BAB_FILES_TBL.".idfai and ".BAB_FAR_INSTANCES_TBL.".iduser='".$BAB_SESS_USERID."' and ".BAB_FAR_INSTANCES_TBL.".result='' and  ".BAB_FAR_INSTANCES_TBL.".notified='Y'");
						if( $res && $db->db_num_rows($res) > 0 )
							break;
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
				$frename = true;
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
			$tmp[] = "name='".$name."'";
			}
		else
			$osfname = $arr['name'];

		if( !empty($readonly))
			$tmp[] = "readonly='".$readonly."'";
		if( !empty($newfolder))
			{
			$pathxnew = bab_getUploadFullPath($arr['bgroup'], $newfolder);

			if( rename( $pathx.$osfname, $pathxnew.$osfname))
				{
				$db->db_query("delete from ".BAB_FM_FIELDSVAL_TBL." where id_file='".$idf."'");
				$tmp[] = "id_owner='".$newfolder."'";
				$tmp[] = "path=''";
				$arr['id_owner'] = $newfolder;
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

		if( empty($bnotify))
			{
			$rr = $db->db_fetch_array($db->db_query("select filenotify from ".BAB_FM_FOLDERS_TBL." where id='".$arr['id_owner']."'"));
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
						deleteFile($arr['id']);
						unlink($pathx.$arr['name']);
						break;
					case 1:
						deleteFlowInstance($arr['idfai']);
						$db->db_query("update ".BAB_FILES_TBL." set confirmed='Y', idfai='0' where id = '".$arr['id']."'");
						if( $bnotify == "Y")
							{
							notifyMembers($arr['name'], $arr['path'], $arr['id_owner'], true);
							}
						break;
					default:
						$nfusers = getWaitingApproversFlowInstance($arr['idfai'], true);
						if( count($nfusers) > 0 )
							notifyFileApprovers($arr['id'], $nfusers);
						break;
					}
				}
			else if( $bnotify == "Y" && $bmodified)
				notifyMembers($arr['name'], $arr['path'], $arr['id_owner'], false);
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
		mkdir($pathx, 0700);
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
				deleteFile($arr['id']);
			}

		if( $pos = strrpos($path, "/"))
			$uppath = substr($path, 0, $pos);
		else
			$uppath = "";
		$GLOBALS['path'] = $uppath;

		if(!@rmdir($pathx.$path))
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

	$mime = "application/octet-stream";
	if ($ext = strrchr($file,"."))
		{
		$ext = substr($ext,1);
		$db = $GLOBALS['babDB'];
		$res = $db->db_query("select * from ".BAB_MIME_TYPES_TBL." where ext='".$ext."'");
		if( $res && $db->db_num_rows($res) > 0)
			{
			$arr = $db->db_fetch_array($res);
			$mime = $arr['mimetype'];
			}
		}
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
	print fread($fp,$fsize);
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
	global $babBody;

	if( !$bmanager)
		{
		$babBody->msgerror = bab_translate("Access denied");
		return false;
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
		$req = "update ".BAB_FILES_TBL." set state='', path='".addslashes($tp)."' where id_owner='".$id."' and bgroup='".$gr."' and path='".addslashes($path)."' and name='".addslashes($file)."'";
		$res = $db->db_query($req);
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

		function temp($idf, $arr, $bmanager, $access, $bconfirm, $bupdate, $bdownload)
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
				$this->keywords = bab_translate("Keywords");
				$this->notify = bab_translate("Notify members group");

				$this->id = $arr['id_owner'];
				$this->gr = $arr['bgroup'];
				$this->path = $arr['path'];
				$this->file = $arr['name'];
				$this->title = $arr['name'];
				$this->descval = $arr['description'];
				$this->keysval = $arr['keywords'];
				$this->descvalhtml = htmlentities($arr['description']);
				$this->keysvalhtml = htmlentities($arr['keywords']);

				$this->fsizetxt = bab_translate("Size");
				$fstat = stat(bab_getUploadFullPath($arr['bgroup'], $arr['id_owner']).$arr['path']."/".$arr['name']);
				$this->fsize = bab_formatSizeFile($fstat[7])." ".bab_translate("Kb")." ( ".bab_formatSizeFile($fstat[7], false) ." ".bab_translate("Bytes") ." )";
				
				$this->fmodifiedtxt = bab_translate("Modified");
				$this->fmodified = date("d/m/Y H:i", bab_mktime($arr['modified']));
				$this->fmodifiedbytxt = bab_translate("Modified by");
				$this->fmodifiedby = bab_getUserName($arr['modifiedby']);
				$this->fcreatedtxt = bab_translate("Created");
				$this->fcreated = date("d/m/Y H:i", bab_mktime($arr['created']));
				$this->fpostedbytxt = bab_translate("Posted by");
				$this->fpostedby = bab_getUserName($arr['author']);

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
				if( $arr['bgroup'] == "Y" && $this->bupdate)
					{
					$rr = $db->db_fetch_array($db->db_query("select filenotify from ".BAB_FM_FOLDERS_TBL." where id='".$arr['id_owner']."'"));
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

					$this->movetofolder = bab_translate("Move to folder");
					$this->resfm = $db->db_query("select id, folder from ".BAB_FM_FOLDERS_TBL." where manager='".$GLOBALS['BAB_SESS_USERID']."' and id !='".$arr['id_owner']."'");
					$this->countfm = $db->db_num_rows($this->resfm);
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
				}
			else
				{
				$this->title = bab_translate("Access denied");
				}
			}

		function getnextfm()
			{
			global $babDB;
			static $i=0;
			if( $i < $this->countfm )
				{
				$arr = $babDB->db_fetch_array($this->resfm);
				$this->folder = $arr['folder'];
				$this->folderid = $arr['id'];
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

		}
	echo $babBody->msgerror;
	$access = false;
	$bmanager = false;
	$bconfirm = false;
	$bupdate = false;
	$bdownload = false;
	$db = $GLOBALS['babDB'];
	$req = "select * from ".BAB_FILES_TBL." where id='".$idf."'";
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
				$res = $db->db_query("select ".BAB_FILES_TBL.".id from ".BAB_FILES_TBL." join ".BAB_FAR_INSTANCES_TBL." where ".BAB_FILES_TBL.".id='".$arr['id']."' and ".BAB_FAR_INSTANCES_TBL.".idschi=".BAB_FILES_TBL.".idfai and ".BAB_FAR_INSTANCES_TBL.".iduser='".$BAB_SESS_USERID."' and ".BAB_FAR_INSTANCES_TBL.".result='' and  ".BAB_FAR_INSTANCES_TBL.".notified='Y'");
				if( $res && $db->db_num_rows($res) > 0 )
					$bconfirm = true;
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
			}
		}

	$temp = new temp($idf, $arr, $bmanager, $access, $bconfirm, $bupdate, $bdownload);
	echo bab_printTemplate($temp,"fileman.html", "viewfile");
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
					deleteFile($items[$i]);
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
		$db->db_query("update ".BAB_FILES_TBL." set state='' where id='".$items[$i]."'");
		}
	}
	
/* main */
$upload = false;
$bmanager = false;
$access = false;
bab_fileManagerAccessLevel();
if( count($babBody->aclfm['id']) == 0 && !$babBody->ustorage )
{
	$babBody->msgerror = bab_translate("Access denied");
	return;
}

if(!isset($idx))
	{
	$idx = "list";
	}

if(!isset($path))
	{
	$path = "";
	}
else
	{
	if( strstr($path, ".."))
		{
		$babBody->msgerror = bab_translate("Access denied");
		return;
		}

	if( bab_isMagicQuotesGpcOn())
		$path = stripslashes($path);
	}


if( !empty($BAB_SESS_USERID) && $babBody->ustorage)
	{
	if(!isset($id))
		{
		$id = $BAB_SESS_USERID;
		}
	if(!isset($gr))
		{
		$gr = "N";
		}
	}
else
	{
	if(!isset($id))
		{
		$id = 0;
		}
	if(!isset($gr))
		{
		$gr = "N";
		}
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

if( isset($addf))
	{
	if( $addf == "add" )
		{
		if( !saveFile($id, $gr, $path, $uploadf_name, $uploadf_size,$uploadf , $description, $keywords, $readonly))
			$idx = "add";
		}
	}

if( isset($updf) && $updf == "upd")
	{
	if( isset($description ))
		$descup = true;
	else
		$descup = false;
	if( !saveUpdateFile($idf, $uploadf_name, $uploadf_size,$uploadf, $fname, $description, $keywords, $readonly, $confirm, $bnotify, $newfolder, $descup))
		$idx = "viewfile";
	else
		$idx = "unload";
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
	$editor = none;
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
		getFile($file, $id, $gr, $path, $inl);
		exit;
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
		$babBody->title = bab_translate("Upload file to")." /".$path. " ( ".bab_formatSizeFile($GLOBALS['babMaxFileSize'])." ".bab_translate("Kb") . " ".bab_translate("Max")." )";
		$upath = urlencode($path);
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
		break;
	}
$babBody->setCurrentItemMenu($idx);
?>