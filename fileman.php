<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
include $babInstallPath."utilit/fileincl.php";

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
	global $babBody, $aclfm;

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
	global $babBody, $aclfm;

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
			global $aclfm;
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
			for( $i = 0; $i < count($aclfm['id']); $i++)
				{
				if($aclfm['pu'][$i] == 1 && $aclfm['ma'][$i] == 0)
					$this->arrgrp[] = $aclfm['id'][$i];

				if($aclfm['pu'][$i] == 1 && $aclfm['ma'][$i] == 1)
					{
					$this->arrmgrp[] = $aclfm['id'][$i];
					}
				}
			if( !empty($GLOBALS['BAB_SESS_USERID'] ) && in_array(1, $aclfm['pr'] ))
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
				//$this->allowedspace =  bab_formatSizeFile($GLOBALS['babMaxUserSize'], false)." " . $this->bytes;
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
				//$pathx = bab_getUploadFullPath("N", $GLOBALS['BAB_SESS_USERID']);
				$size = getDirSize($GLOBALS['babUploadPath']);
				$this->diskspace = bab_formatSizeFile($size).$this->kilooctet;
				$this->allowedspace =  bab_formatSizeFile($GLOBALS['babMaxTotalSize']).$this->kilooctet;
				//$this->allowedspace =  bab_formatSizeFile($GLOBALS['babMaxTotalSize'], false)." " . $this->bytes;
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
				//$this->allowedspace =  bab_formatSizeFile($GLOBALS['babMaxGroupSize'], false)." " . $this->bytes;
				$this->remainingspace =  bab_formatSizeFile($GLOBALS['babMaxGroupSize'] - $size).$this->kilooctet;
				$this->groupname = bab_getGroupName($this->arrgrp[$i]);
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
				$this->groupname = bab_getGroupName($this->arrmgrp[$i]);
				$pathx = bab_getUploadFullPath("Y", $this->arrmgrp[$i]);
				$size = getDirSize($pathx);
				$this->diskspace = bab_formatSizeFile($size).$this->kilooctet;
				$this->allowedspace =  bab_formatSizeFile($GLOBALS['babMaxGroupSize']).$this->kilooctet;
				//$this->allowedspace =  bab_formatSizeFile($GLOBALS['babMaxGroupSize'], false)." " . $this->bytes;
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


function listFiles($id, $gr, $path, $bmanager)
	{
	global $babBody, $aclfm;

	class temp
		{
		var $db;
		var $res;
		var $count;
		var $name;
		var $path;
		var $fullpath;
		var $id;
		var $gr;
		var $mkdir;
		var $rename;
		var $delete;
		var $directory;
		var $root;
		var $rooturl;
		var $refresh;
		var $refreshurl;
		var $bmanager;
		var $nametxt;
		var $sizetxt;
		var $modifiedtxt;
		var $postedtxt;
		var $postedby;
		var $rootpath;
		var $cuturl;
		var $download;
		var $cuttxt;
		var $paste;
		var $undo;
		var $deltxt;
		var $urldiskspace;
		var $diskspace;
		var $hits;
		var $hitstxt;
		var $arrext = array();

		var $arrdir = array();

		function temp($id, $gr, $path, $bmanager)
			{
			global $BAB_SESS_USERID, $aclfm;
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
			$this->fullpath = bab_getUploadFullPath($gr, $id);
			$this->root = bab_translate("Home folder");
			$this->refresh = bab_translate("Refresh");
			$this->nametxt = bab_translate("Name");
			$this->sizetxt = bab_translate("Size");
			$this->modifiedtxt = bab_translate("Modified");
			$this->postedtxt = bab_translate("Posted by");
			$this->diskspace = bab_translate("Show disk space usage");
			$this->hitstxt = bab_translate("Hits");

			if( !empty($BAB_SESS_USERID))
				$this->rooturl = $GLOBALS['babUrlScript']."?tg=fileman&idx=list&id=".$BAB_SESS_USERID."&gr=N&path=";
			else
				$this->rooturl = $GLOBALS['babUrlScript']."?tg=fileman&idx=list&id=2&gr=Y&path=";
			$this->refreshurl = $GLOBALS['babUrlScript']."?tg=fileman&idx=list&id=".$id."&gr=".$gr."&path=".$path;
			$this->urldiskspace = $GLOBALS['babUrlScript']."?tg=fileman&idx=disk&id=".$id."&gr=".$gr."&path=".$path;

			$this->path = $path;
			$this->id = $id;
			$this->gr = $gr;
			$this->upfolderimg = bab_printTemplate($this, "config.html", "parentfolder");
			$this->usrfolderimg = bab_printTemplate($this, "config.html", "userfolder");
			$this->grpfolderimg = bab_printTemplate($this, "config.html", "groupfolder");
			$this->manfolderimg = bab_printTemplate($this, "config.html", "managerfolder");
			$this->countmgrp = 0;
			$this->countgrp = 0;

			if( $gr == "Y")
				$this->rootpath = bab_getGroupName($id);
			else
				$this->rootpath = "";

			$this->db = $GLOBALS['babDB'];
			$this->bmanager = $bmanager;
			for( $i = 0; $i < count($aclfm['id']); $i++)
				{
				if($aclfm['pu'][$i] == 1 && $aclfm['ma'][$i] == 0)
					$this->arrgrp[] = $aclfm['id'][$i];

				if($aclfm['pu'][$i] == 1 && $aclfm['ma'][$i] == 1)
					{
					$this->arrmgrp[] = $aclfm['id'][$i];
					}
				}

			//if( $gr == "N" && in_array(1, $aclfm['pr']) || $gr == "Y")
			//	{
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
					$this->arrudir[] = $GLOBALS['babUrlScript']."?tg=fileman&idx=list&id=".$id."&gr=".$gr."&path=".$p;
					}

				if( ($gr == "N" && in_array(1, $aclfm['pr']) || $gr == "Y") && is_dir($this->fullpath.$path."/"))
					{
					$h = opendir($this->fullpath.$path."/");
					while (($f = readdir($h)) != false)
						{
						if ($f != "." and $f != "..") 
							{
							if (is_dir($this->fullpath.$path."/".$f))
								{
								$this->arrdir[] = $f;
								$this->arrudir[] = $GLOBALS['babUrlScript']."?tg=fileman&idx=list&id=".$id."&gr=".$gr."&path=".$path.($path ==""?"":"/").$f;
								}
							}
						}
					closedir($h);
					$req = "select * from ".BAB_FILES_TBL." where id_owner='".$id."' and bgroup='".$gr."' and state='' and path='".$path."'";
					if( !$this->bmanager )
						$req .= " and confirmed='Y'";
						$req .= " order by name asc";
					$this->res = $this->db->db_query($req);
					$this->count = $this->db->db_num_rows($this->res);
					}
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
				//}
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
				$this->name = bab_getGroupName($this->arrgrp[$m]);
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
				$this->name = bab_getGroupName($this->arrmgrp[$m]);
				$this->url = $GLOBALS['babUrlScript']."?tg=fileman&idx=list&id=".$this->arrmgrp[$m]."&gr=Y&path=";
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
				$this->url = $GLOBALS['babUrlScript']."?tg=fileman&idx=upd&id=".$this->id."&gr=".$this->gr."&path=".$this->path."&file=".$arr['name'];
				$this->viewurl = $GLOBALS['babUrlScript']."?tg=fileman&idx=viewfile&idf=".$arr['id']."&id=".$this->id."&gr=".$this->gr."&path=".$this->path."&file=".$arr['name'];
				$this->urlget = $GLOBALS['babUrlScript']."?tg=fileman&idx=get&id=".$this->id."&gr=".$this->gr."&path=".$this->path."&file=".$arr['name'];
				$this->cuturl = $GLOBALS['babUrlScript']."?tg=fileman&idx=cut&id=".$this->id."&gr=".$this->gr."&path=".$this->path."&file=".$arr['name'];				
				$this->delurl = $GLOBALS['babUrlScript']."?tg=fileman&idx=del&id=".$this->id."&gr=".$this->gr."&path=".$this->path."&file=".$arr['name'];				
				if( file_exists($this->fullpath.$arr['path']."/".$arr['name']))
					{
					$fstat = stat($this->fullpath.$arr['path']."/".$arr['name']);
					$this->sizef = bab_formatSizeFile($fstat[7])." ".bab_translate("Kb");
					}
				else
					$this->sizef = "???";

				$this->modified = date("d/m/Y H:i", bab_mktime($arr['modified']));
				$this->postedby = bab_getUserName($arr['author']);
				if( $this->bmanager && $arr['confirmed'] == "N")
					$this->bconfirmed = 1;
				else
					$this->bconfirmed = 0;
				$this->hits = $arr['hits'];
				if( $arr['readonly'] == "Y" )
					$this->readonly = "R";
				else
					$this->readonly = "";
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
				$ext = substr(strrchr($arr['name'], "."), 1);
				if( empty($this->arrext[$ext]))
					$this->arrext[$ext] = bab_printTemplate($this, "config.html", ".".$ext);
				if( empty($this->arrext[$ext]))
					$this->arrext[$ext] = bab_printTemplate($this, "config.html", ".unknown");				
				$this->fileimage = $this->arrext[$ext];
				$this->name = $arr['name'];
				$this->url = $GLOBALS['babUrlScript']."?tg=fileman&idx=upd&id=".$this->id."&gr=".$this->gr."&path=".$arr['path']."&file=".$arr['name'];
				$this->urlget = $GLOBALS['babUrlScript']."?tg=fileman&idx=get&id=".$this->id."&gr=".$this->gr."&path=".$arr['path']."&file=".$arr['name'];
				$this->pasteurl = $GLOBALS['babUrlScript']."?tg=fileman&idx=paste&id=".$this->id."&gr=".$this->gr."&path=".$arr['path']."&file=".$arr['name']."&tp=".$this->path;				
				if( file_exists($this->fullpath.$arr['path']."/".$arr['name']))
					{
					$fstat = stat($this->fullpath.$arr['path']."/".$arr['name']);
					$this->sizef = bab_formatSizeFile($fstat[7])." ".bab_translate("Kb");
					}
				else
					$this->sizef = "???";

				$this->modified = date("d/m/Y H:i", bab_mktime($arr['modified']));
				$this->postedby = bab_getUserName($arr['author']);
				if( $this->bmanager && $arr['confirmed'] == "N")
					$this->bconfirmed = 1;
				else
					$this->bconfirmed = 0;
				$i++;
				return true;
				}
			else
				return false;
			}

		}

	$pathx = bab_getUploadFullPath($gr, $id);
	if( !is_dir($pathx))
		{
		mkdir($pathx, 0700);
		}

	$temp = new temp($id, $gr, $path, $bmanager);
	$babBody->babecho(	bab_printTemplate($temp,"fileman.html", "fileslist"));
	return $temp->count;
	}

function addFile($id, $gr, $path, $description, $keywords)
	{
	global $babBody, $aclfm, $BAB_SESS_USERID;

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

		function temp($id, $gr, $path, $description, $keywords)
			{
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
			}
		}

	$access = false;
	if( $gr == "N" && !empty($BAB_SESS_USERID))
		{
		if( in_array(1, $aclfm['pr']) )
			{
			$access = true;
			}
		}

	if( $gr == "Y" && !empty($BAB_SESS_USERID))
		{
		for( $i = 0; $i < count($aclfm['id']); $i++)
			{
			if( $aclfm['id'][$i] == $id && $aclfm['pu'][$i] == 1)
				{
				$access = true;
				break;
				}
			}

		if( !$access && ( $id == 1 || $id == 2) && bab_isUserAdministrator())
			$access = true;

		if( !$access && !empty($BAB_SESS_USERID) && bab_isUserGroupManager($id))
			$access = true;
		}

	if( !$access )
		{
		$babBody->msgerror = bab_translate("Access denied");
		return;
		}

	$temp = new temp($id, $gr, $path, $description, $keywords);
	$babBody->babecho(	bab_printTemplate($temp,"fileman.html", "addfile"));
	}

function notifyApprover($grpname, $file, $path, $approveremail)
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


		function tempa($grpname, $file, $path)
			{
            global $BAB_SESS_USER, $BAB_SESS_EMAIL;
            $this->filename = $file;
            $this->message = bab_translate("A new file is waiting for you");
            $this->from = bab_translate("Author");
            $this->path = bab_translate("Path");
            $this->file = bab_translate("File");
            $this->group = bab_translate("Group");
            $this->pathname = $path == ""? "/": $path;
            $this->groupname = $grpname;
            $this->site = bab_translate("Web site");
            $this->date = bab_translate("Date");
            $this->dateval = bab_strftime(mktime());
            if( !empty($BAB_SESS_USER))
                $this->author = $BAB_SESS_USER;
            else
                $this->author = bab_translate("Unknown user");

            if( !empty($BAB_SESS_EMAIL))
                $this->authoremail = $BAB_SESS_EMAIL;
            else
                $this->authoremail = "";
			}
		}
    $mail = bab_mail();
	if( $mail == false )
		return;
	
    $mail->mailTo($approveremail);
    $mail->mailFrom($babAdminEmail, "Ovidentia Administrator");
    $mail->mailSubject(bab_translate("New waiting file"));

	$tempa = new tempa($grpname, $file, $path);
	$message = bab_printTemplate($tempa,"mailinfo.html", "filewait");
    $mail->mailBody($message, "html");

	$message = bab_printTemplate($tempa,"mailinfo.html", "filewaittxt");
    $mail->mailAltBody($message);

	$mail->send();
	}
/*
function saveFile($id, $gr, $path, $filename, $size, $tmp, $description, $keywords, $readonly)
	{
	global $babBody, $BAB_SESS_USERID, $aclfm;
	$access = false;
	$bmanager = false;
	$access = false;
	$confirmed = "N";

	if( $gr == "N" && !empty($BAB_SESS_USERID))
		{
		if( in_array(1, $aclfm['pr']) )
			{
			$access = true;
			$confirmed = "Y";
			}
		}

	if( $gr == "Y" && !empty($BAB_SESS_USERID))
		{
		for( $i = 0; $i < count($aclfm['id']); $i++)
			{
			if( $aclfm['id'][$i] == $id && $aclfm['pu'][$i] == 1)
				{
				$access = true;
				break;
				}
			}

		if( !$access && ( $id == 1 || $id == 2) && bab_isUserAdministrator())
			{
			$confirmed = "Y";
			$access = true;
			}

		if( !$access && !empty($BAB_SESS_USERID) && bab_isUserGroupManager($id))
			{
			$confirmed = "Y";
			$access = true;
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

	if( isset($GLOBALS['babFileNameTranslation']))

	$filename = strtr($filename, $GLOBALS['babFileNameTranslation']);
	$name = $filename;
	if( !bab_isMagicQuotesGpcOn())
		{
		$name = addslashes($filename);
		$description = addslashes($description);
		$keywords = addslashes($keywords);
		}

	$update = false;
	$db = $GLOBALS['babDB'];
	if( file_exists($pathx.$filename))
		{
		$res = $db->db_query("select * from ".BAB_FILES_TBL." where id_owner='".$id."' and bgroup='".$gr."' and name='".$name."' and path='".$path."'");
		if( $res && $db->db_num_rows($res) > 0)
			{
			$arr = $db->db_fetch_array($res);
			if( $arr['readonly'] == "Y")
				{
				$babBody->msgerror = bab_translate("This file is read only");
				return false;
				}
			else
				$update = true;
			}
		}

	if( !get_cfg_var('safe_mode'))
		set_time_limit(0);
	if( !move_uploaded_file($tmp, $pathx.$filename))
		{
		$babBody->msgerror = bab_translate("The file could not be uploaded");
		return false;
		}
	
	if( empty($BAB_SESS_USERID))
		$idcreator = 0;
	else
		$idcreator = $BAB_SESS_USERID;


	if( $confirmed == 'N' && $gr == "Y" )
		{
		$rr = $db->db_fetch_array($db->db_query("select moderate from ".BAB_GROUPS_TBL." where id='".$id."'"));
		if( $rr['moderate'] == "N")
			$confirmed = "Y";
		}

	if( $update )
		{
		$req = "update ".BAB_FILES_TBL." set description='".$description."', keywords='".$keywords."', readonly='".$readonly."', confirmed='".$confirmed."', modified=now(), modifiedby='".$idcreator."', state='' where id='".$arr['id']."'";
		}
	else
		{
		$req = "insert into ".BAB_FILES_TBL." (name, description, keywords, path, id_owner, bgroup, link, readonly, state, created, author, modified, modifiedby, confirmed) values ";
		$req .= "('" .$name. "', '" . $description. "', '" . $keywords. "', '" . $path. "', '" . $id. "', '" . $gr. "', '0', '" . $readonly. "', '', now(), '" . $idcreator. "', now(), '" . $idcreator. "', '". $confirmed."')";
		}
	$res = $db->db_query($req);

	if( $confirmed == "N" )
		{
		$res = $db->db_query("select * from ".BAB_GROUPS_TBL." where id='".$id."'");
		if( $res && $db->db_num_rows($res) > 0)
			{
			$arr = $db->db_fetch_array($res);
			if( $arr['manager'] != 0)
				{
				$res = $db->db_query("select * from ".BAB_USERS_TBL." where id='".$arr['manager']."'");
				if( $res && $db->db_num_rows($res) > 0)
					{
					$arr2 = $db->db_fetch_array($res);
					notifyApprover($arr['name'], $filename, $path, $arr2['email']);
					}
				}
			}
		}

	return true;
	}
*/

function saveFile($id, $gr, $path, $filename, $size, $tmp, $description, $keywords, $readonly)
	{
	global $babBody, $BAB_SESS_USERID, $aclfm;
	$access = false;
	$bmanager = false;
	$access = false;
	$confirmed = "N";

	if( $gr == "N" && !empty($BAB_SESS_USERID))
		{
		if( in_array(1, $aclfm['pr']) )
			{
			$access = true;
			$confirmed = "Y";
			}
		}

	if( $gr == "Y" && !empty($BAB_SESS_USERID))
		{
		for( $i = 0; $i < count($aclfm['id']); $i++)
			{
			if( $aclfm['id'][$i] == $id && $aclfm['pu'][$i] == 1)
				{
				$access = true;
				break;
				}
			}

		if( !$access && ( $id == 1 || $id == 2) && bab_isUserAdministrator())
			{
			$confirmed = "Y";
			$access = true;
			}

		if( !$access && !empty($BAB_SESS_USERID) && bab_isUserGroupManager($id))
			{
			$confirmed = "Y";
			$access = true;
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

	if( isset($GLOBALS['babFileNameTranslation']))
		$filename = strtr($filename, $GLOBALS['babFileNameTranslation']);

	$name = $filename;
	if( !bab_isMagicQuotesGpcOn())
		{
		$name = addslashes($filename);
		$description = addslashes($description);
		$keywords = addslashes($keywords);
		}

	$db = $GLOBALS['babDB'];
	$bexist = false;
	if( file_exists($pathx.$filename))
		{
		$res = $db->db_query("select * from ".BAB_FILES_TBL." where id_owner='".$id."' and bgroup='".$gr."' and name='".$name."' and path='".$path."'");
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
	if( !move_uploaded_file($tmp, $pathx.$filename))
		{
		$babBody->msgerror = bab_translate("The file could not be uploaded");
		return false;
		}
	
	if( empty($BAB_SESS_USERID))
		$idcreator = 0;
	else
		$idcreator = $BAB_SESS_USERID;

	if( $bexist)
		{
		$req = "update ".BAB_FILES_TBL." set description='".$description."', keywords='".$keywords."', readonly='".$readonly."', confirmed='".$confirmed."', modified=now(), hits='0', modifiedby='".$idcreator."', state='' where id='".$arr['id']."'";
		}
	else
		{
		$req = "insert into ".BAB_FILES_TBL." (name, description, keywords, path, id_owner, bgroup, link, readonly, state, created, author, modified, modifiedby, confirmed) values ";
		$req .= "('" .$name. "', '" . $description. "', '" . $keywords. "', '" . $path. "', '" . $id. "', '" . $gr. "', '0', '" . $readonly. "', '', now(), '" . $idcreator. "', now(), '" . $idcreator. "', '". $confirmed."')";
		}

	$res = $db->db_query($req);

	if( $confirmed == "N" )
		{
		$res = $db->db_query("select * from ".BAB_GROUPS_TBL." where id='".$id."'");
		if( $res && $db->db_num_rows($res) > 0)
			{
			$arr = $db->db_fetch_array($res);
			if( $arr['manager'] != 0)
				{
				$res = $db->db_query("select * from ".BAB_USERS_TBL." where id='".$arr['manager']."'");
				if( $res && $db->db_num_rows($res) > 0)
					{
					$arr2 = $db->db_fetch_array($res);
					notifyApprover($arr['name'], $filename, $path, $arr2['email']);
					}
				}
			}
		}

	return true;
	}

function saveUpdateFile($idf, $uploadf_name, $uploadf_size,$uploadf, $fname, $description, $keywords, $readonly, $confirm)
	{
	global $babBody, $BAB_SESS_USERID;
	$db = $GLOBALS['babDB'];

	$res = $db->db_query("select * from ".BAB_FILES_TBL." where id='".$idf."'");
	if( $res && $db->db_num_rows($res))
		{
		$arr = $db->db_fetch_array($res);
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

		$frename = false;
		if( !empty($fname) && strcmp($arr['name'], $fname))
			{
			if( isset($GLOBALS['babFileNameTranslation']))
				$fname = strtr($fname, $GLOBALS['babFileNameTranslation']);
			if( rename($pathx.$arr['name'], $pathx.$fname))
				$frename = true;
			}

		if( !bab_isMagicQuotesGpcOn())
			{
			$fname = addslashes($fname);
			$description = addslashes($description);
			$keywords = addslashes($keywords);
			}

		if( empty($BAB_SESS_USERID))
			$idcreator = 0;
		else
			$idcreator = $BAB_SESS_USERID;

		$req = "update ".BAB_FILES_TBL." set description='".$description."', keywords='".$keywords."'";
		if( $bmodified)
			$req .= ", modified=now(), modifiedby='".$idcreator."'";
		if( $frename)
			$req .= ", name='".$fname."'";
		if( !empty($readonly))
			$req .= ", readonly='".$readonly."'";
		if( !empty($confirm))
			$req .= ", confirmed='".$confirm."'";
		$req .= " where id='".$idf."'";
		$res = $db->db_query($req);
		return true;
		}
	}

function createDirectory($dirname, $id, $gr, $path)
	{
	global $babBody, $BAB_SESS_USERID, $aclfm;
	if( $gr == "N" && $BAB_SESS_USERID == $id && in_array(1, $aclfm['pr']) || $gr == "Y" && ((($id == 2 || $id ==1) && bab_isUserAdministrator()) || bab_isUserGroupManager($id)))
		;
	else
		{
		$babBody->msgerror = bab_translate("You don't have permission to create directory");
		return false;
		}

	if( empty($dirname))
		{
		$babBody->msgerror = bab_translate("Please give a valid directory name");
		return false;
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

	if( $gr == "N" && $BAB_SESS_USERID == $id && in_array(1, $aclfm['pr']) || $gr == "Y" && ((($id == 2 || $id ==1) && bab_isUserAdministrator()) || bab_isUserGroupManager($id)))
		;
	else
		{
		$babBody->msgerror = bab_translate("You don't have permission to rename directory");
		return false;
		}

	if( empty($dirname))
		{
		$babBody->msgerror = bab_translate("Please give a valid directory name");
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
			$db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_FILES_TBL." where id_owner='".$id."' and bgroup='".$gr."'";
			$res = $db->db_query($req);
			while( $arr = $db->db_fetch_array($res))
				{
				if( substr($arr['path'], 0, $len) == $path )
					{
					$req = "update ".BAB_FILES_TBL." set path='".str_replace($path, $uppath.$dirname, $arr['path'])."' where id='".$arr['id']."'";
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

	if( $gr == "N" && $BAB_SESS_USERID == $id && in_array(1, $aclfm['pr']) || $gr == "Y" && ((($id == 2 || $id ==1) && bab_isUserAdministrator()) || bab_isUserGroupManager($id)))
		;
	else
		{
		$babBody->msgerror = bab_translate("You don't have permission to remove directory");
		return false;
		}

	$pathx = bab_getUploadFullPath($gr, $id);

	if( is_dir($pathx.$path))
		{
		$db = $GLOBALS['babDB'];
		$req = "select * from ".BAB_FILES_TBL." where id_owner='".$id."' and bgroup='".$gr."' and path='".$path."'";
		$res = $db->db_query($req);
		while( $arr = $db->db_fetch_array($res))
			{
			if( @unlink($pathx.$path."/".$arr['name']))
				$db->db_query("delete from ".BAB_FILES_TBL." where id='".$arr['id']."'");
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
	global $babBody, $BAB_SESS_USERID, $aclfm;
	$access = false;

	if( $gr == "N")
		{
		if( !in_array(1, $aclfm['pr']))
			$access = false;
		else
			$access = true;
		}

	if( $gr == "Y" )
		{
		for( $i = 0; $i < count($aclfm['id']); $i++)
			if( $aclfm['id'][$i] == $id )
			{
				$access = true;
				break;
			}
		}

	$db = $GLOBALS['babDB'];
	if( $access )
		{
		$req = "select * from ".BAB_FILES_TBL." where id_owner='".$id."' and bgroup='".$gr."' and path='".$path."' and name='".$file."'";
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
		$babBody->msgerror = bab_translate("Access denied");
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
	$req = "update ".BAB_FILES_TBL." set state='X' where id_owner='".$id."' and bgroup='".$gr."' and state='' and path='".$path."' and name='".$file."'";
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
	$req = "update ".BAB_FILES_TBL." set state='D' where id_owner='".$id."' and bgroup='".$gr."' and state='' and path='".$path."' and name='".$file."'";
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

	$pathx = bab_getUploadFullPath($gr, $id);
	if( file_exists($pathx.$tp."/".$file))
		{
		if( $path == $tp )
			{
			$db = $GLOBALS['babDB'];
			$req = "update ".BAB_FILES_TBL." set state='' where id_owner='".$id."' and bgroup='".$gr."' and path='".$path."' and name='".$file."'";
			$res = $db->db_query($req);
			return true;
			}
		$babBody->msgerror = bab_translate("A file with the same name already exists");
		return false;
		}

	if( rename( $pathx.$path."/".$file, $pathx.$tp."/".$file))
		{
		$db = $GLOBALS['babDB'];
		$req = "update ".BAB_FILES_TBL." set state='', path='".$tp."' where id_owner='".$id."' and bgroup='".$gr."' and path='".$path."' and name='".$file."'";
		$res = $db->db_query($req);
		return true;
		}
	else
		{
		$babBody->msgerror = bab_translate("Cannot paste file");
		return false;
		}
	}

function viewFile( $idf, $aclfm)
	{
	global $babBody, $BAB_SESS_USERID, $aclfm;
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

		function temp($idf, $arr, $bmanager, $access, $bconfirm)
			{
			$this->access = $access;
			if( $access)
				{
				$this->bmanager = $bmanager;
				$this->bconfirm = $bconfirm;
				$this->idf = $idf;

				$this->description = bab_translate("Description");
				$this->keywords = bab_translate("Keywords");

				$this->id = $arr['id_owner'];
				$this->gr = $arr['bgroup'];
				$this->path = $arr['path'];
				$this->file = $arr['name'];
				$this->title = $arr['name'];
				$this->descval = $arr['description'];
				$this->keysval = $arr['keywords'];

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

				$this->geturl = $GLOBALS['babUrlScript']."?tg=fileman&idx=get&id=".$arr['id_owner']."&gr=".$arr['bgroup']."&path=".$arr['path']."&file=".$arr['name'];
				$this->download = bab_translate("Download");

				$this->file = bab_translate("File");
				$this->name = bab_translate("Name");
				$this->nameval = $arr['name'];
				$this->attribute = bab_translate("Read only");
				$this->bupdate = false;
				if( $arr['readonly'] == "Y")
					{
					$this->yesselected = "selected";
					$this->noselected = "";
					}
				else
					{
					$this->bupdate = true;
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
				}
			else
				{
				$this->title = bab_translate("Access denied");
				}
			}
		}

	$access = false;
	$bmanager = false;
	$bconfirm = false;
	$db = $GLOBALS['babDB'];
	$req = "select * from ".BAB_FILES_TBL." where id='".$idf."'";
	$res = $db->db_query($req);
	if( $res && $db->db_num_rows($res) > 0 )
		{
		$arr = $db->db_fetch_array($res);
		if( $arr['bgroup'] == "N" )
			{
			if( in_array(1, $aclfm['pr']) && $BAB_SESS_USERID == $arr['id_owner'])
				{
				$access = true;
				$bmanager = true;
				}
			}

		if( $arr['bgroup'] == "Y" && !empty($BAB_SESS_USERID) )
			{
			if( (($arr['id_owner'] == 2 || $arr['id_owner'] ==1) && bab_isUserAdministrator()) || bab_isUserGroupManager($arr['id_owner']))
				{
				$bconfirm = true;
				$bmanager = true;
				}
			$access = true;
			}
		}


	$temp = new temp($idf, $arr, $bmanager, $access, $bconfirm);
	echo bab_printTemplate($temp,"fileman.html", "viewfile");
	}

function fileUnload($id, $gr, $path)
	{
	class temp
		{
		var $message;
		var $close;

		function temp($id, $gr, $path)
			{
			$this->message = bab_translate("Your file list has been updated");
			$this->close = bab_translate("Close");
			$this->url = $GLOBALS['babUrlScript']."?tg=fileman&idx=list&id=".$id."&gr=".$gr."&path=".$path;
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
					$db->db_query("delete from ".BAB_FILES_TBL." where id='".$items[$i]."' or link='".$items[$i]."'");
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
$aclfm = bab_fileManagerAccessLevel();
if(!isset($idx))
	{
	$idx = "list";
	}

if(!isset($path))
	{
	$path = "";
	}

if(!isset($gr))
	{
	$gr = "N";
	}

if( !empty($BAB_SESS_USERID))
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
	$id = 2;
	$gr = "Y";
	}

$upload = false;
$bmanager = false;
$access = false;
if( $gr == "N" && !empty($BAB_SESS_USERID) && $BAB_SESS_USERID == $id )
	{
	if( in_array(1, $aclfm['pr']) )
		{
		$upload = true;
		$bmanager = true;
		$access = true;
		}
	if( !$access && count($aclfm['pu']) > 0)
		$access = true;

	}

if( $gr == "Y")
	{
	for( $i = 0; $i < count($aclfm['id']); $i++)
		{
		if( $aclfm['id'][$i] == $id && $aclfm['pu'][$i] == 1)
			{
			$access = true;
			if( !empty( $BAB_SESS_USERID))
				{
				if( $id == 2 || $id ==1)
					{
					if( bab_isUserAdministrator() )
						{
						$bmanager = true;
						$upload = true;
						}
					}
				else
					{
					$bmanager = bab_isUserGroupManager($id);
					$upload = true;
					}
				}
			}
		}
	}

if( !$access)
	{
		$babBody->msgerror = bab_translate("Access denied");
		return;
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
	//saveUpdateFile($idf, $file, $id, $gr, $path, $name, $description, $keywords, $readonly, $confirm);
	if( !saveUpdateFile($idf, $uploadf_name, $uploadf_size,$uploadf, $fname, $description, $keywords, $readonly, $confirm))
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
		$path = $tp;
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

switch($idx)
	{
	case "unload":
		fileUnload($id, $gr, $path);
		exit;
		break;

	case "viewfile":
		viewFile($idf, $aclfm);
		exit;
		break;

	case "get":
		getFile($file, $id, $gr, $path, $inl);
		exit;
		break;

	case "trash":
		$babBody->title = bab_translate("Trash");
		listTrashFiles($id, $gr);
		$babBody->addItemMenu("list", bab_translate("List"), $GLOBALS['babUrlScript']."?tg=fileman&idx=list&id=".$id."&gr=".$gr."&path=".$path);
		if( $upload)
			$babBody->addItemMenu("add", bab_translate("Upload"), $GLOBALS['babUrlScript']."?tg=fileman&idx=add&id=".$id."&gr=".$gr."&path=".$path);
		if( $bmanager)
			$babBody->addItemMenu("trash", bab_translate("Trash"), $GLOBALS['babUrlScript']."?tg=fileman&idx=trash&id=".$id."&gr=".$gr."&path=".$path);
		break;

	case "add":
		$babBody->title = bab_translate("Upload file to")." /".$path. " ( ".bab_formatSizeFile($GLOBALS['babMaxFileSize'])." ".bab_translate("Kb") . " ".bab_translate("Max")." )";
		addFile($id, $gr, $path, $description, $keywords);
		$babBody->addItemMenu("list", bab_translate("List"), $GLOBALS['babUrlScript']."?tg=fileman&idx=list&id=".$id."&gr=".$gr."&path=".$path);
		if( $upload)
			$babBody->addItemMenu("add", bab_translate("Upload"), $GLOBALS['babUrlScript']."?tg=fileman&idx=add&id=".$id."&gr=".$gr."&path=".$path);
		if( $bmanager)
			$babBody->addItemMenu("trash", bab_translate("Trash"), $GLOBALS['babUrlScript']."?tg=fileman&idx=trash&id=".$id."&gr=".$gr."&path=".$path);
		break;

	case "disk":
		$babBody->title = bab_translate("File manager");
		showDiskSpace($id, $gr, $path);
		$babBody->addItemMenu("list", bab_translate("List"), $GLOBALS['babUrlScript']."?tg=fileman&idx=list&id=".$id."&gr=".$gr."&path=".$path);
		if( $upload)
			$babBody->addItemMenu("add", bab_translate("Upload"), $GLOBALS['babUrlScript']."?tg=fileman&idx=add&id=".$id."&gr=".$gr."&path=".$path);
		if( $bmanager)
			$babBody->addItemMenu("trash", bab_translate("Trash"), $GLOBALS['babUrlScript']."?tg=fileman&idx=trash&id=".$id."&gr=".$gr."&path=".$path);
		break;

	case "cut":
		cutFile($file, $id, $gr, $path, $bmanager);
		/* no break */
	default:
	case "list":
		$babBody->title = bab_translate("File manager");
		$babBody->addItemMenu("list", bab_translate("List"), $GLOBALS['babUrlScript']."?tg=fileman&idx=list&id=".$id."&gr=".$gr."&path=".$path);
		if( $upload)
			$babBody->addItemMenu("add", bab_translate("Upload"), $GLOBALS['babUrlScript']."?tg=fileman&idx=add&id=".$id."&gr=".$gr."&path=".$path);
		if( $bmanager)
			$babBody->addItemMenu("trash", bab_translate("Trash"), $GLOBALS['babUrlScript']."?tg=fileman&idx=trash&id=".$id."&gr=".$gr."&path=".$path);
		listFiles($id, $gr, $path, $bmanager);
		break;
	}
$babBody->setCurrentItemMenu($idx);
?>
