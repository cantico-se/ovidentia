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
	global $body, $aclfm;

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
			$this->bytes = babTranslate("bytes");
			$this->delete = babTranslate("Delete");
			$this->restore = babTranslate("Restore");
			$this->nametxt = babTranslate("Name");
			$this->sizetxt = babTranslate("Size");
			$this->modifiedtxt = babTranslate("Modified");
			$this->postedtxt = babTranslate("Posted by");
			$this->fullpath = getFullPath($gr, $id);
			$this->db = new db_mysql();
			$req = "select * from files where id_owner='".$id."' and bgroup='".$gr."' and state='D'";
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
					$this->arrext[$ext] = babPrintTemplate($this, "config.html", ".".$ext);
				if( empty($this->arrext[$ext]))
					$this->arrext[$ext] = babPrintTemplate($this, "config.html", ".unknown");				
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
				$this->postedby = getUserName($arr['author']);
				$i++;
				return true;
				}
			else
				return false;
			}

		}

	$temp = new temp($id, $gr);
	$body->babecho(	babPrintTemplate($temp,"fileman.html", "trashfiles"));
	}

function showDiskSpace($id, $gr, $path)
	{
	global $body, $aclfm;

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
			$this->grouptxt = babTranslate("Name");
			$this->diskspacetxt = babTranslate("Used");
			$this->allowedspacetxt = babTranslate("Allowed");
			$this->remainingspacetxt = babTranslate("Remaining");
			$this->cancel = babTranslate("Close");
			$this->bytes = babTranslate("bytes");
			$this->kilooctet = " ".babTranslate("Kb");
			$this->babCss = babPrintTemplate($this,"config.html", "babCss");
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
			if( !empty($GLOBALS['BAB_SESS_USERID'] ) && isUserAdministrator() )
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
				$pathx = getFullPath("N", $GLOBALS['BAB_SESS_USERID']);
				$size = getDirSize($pathx);
				$this->diskspace = formatSize($size).$this->kilooctet;
				$this->allowedspace =  formatSize($GLOBALS['babMaxUserSize']).$this->kilooctet;
				//$this->allowedspace =  formatSize($GLOBALS['babMaxUserSize'], false)." " . $this->bytes;
				$this->remainingspace =  formatSize($GLOBALS['babMaxUserSize'] - $size).$this->kilooctet;
				$this->groupname = babTranslate("Personnal Folder");
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
				//$pathx = getFullPath("N", $GLOBALS['BAB_SESS_USERID']);
				$size = getDirSize($GLOBALS['babUploadPath']);
				$this->diskspace = formatSize($size).$this->kilooctet;
				$this->allowedspace =  formatSize($GLOBALS['babMaxTotalSize']).$this->kilooctet;
				//$this->allowedspace =  formatSize($GLOBALS['babMaxTotalSize'], false)." " . $this->bytes;
				$this->remainingspace =  formatSize($GLOBALS['babMaxTotalSize'] - $size).$this->kilooctet;
				$this->groupname = babTranslate("Global space");
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
				$pathx = getFullPath("Y", $this->arrgrp[$i]);
				$size = getDirSize($pathx);
				$this->diskspace = formatSize($size).$this->kilooctet;
				$this->allowedspace =  formatSize($GLOBALS['babMaxGroupSize']).$this->kilooctet;
				//$this->allowedspace =  formatSize($GLOBALS['babMaxGroupSize'], false)." " . $this->bytes;
				$this->remainingspace =  formatSize($GLOBALS['babMaxGroupSize'] - $size).$this->kilooctet;
				$this->groupname = getGroupName($this->arrgrp[$i]);
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
				$this->groupname = getGroupName($this->arrmgrp[$i]);
				$pathx = getFullPath("Y", $this->arrmgrp[$i]);
				$size = getDirSize($pathx);
				$this->diskspace = formatSize($size).$this->kilooctet;
				$this->allowedspace =  formatSize($GLOBALS['babMaxGroupSize']).$this->kilooctet;
				//$this->allowedspace =  formatSize($GLOBALS['babMaxGroupSize'], false)." " . $this->bytes;
				$this->remainingspace =  formatSize($GLOBALS['babMaxGroupSize'] - $size).$this->kilooctet;
				$i++;
				return true;
				}
			else
				return false;
			}

		}

	$temp = new temp($id, $gr, $path);
	echo babPrintTemplate($temp,"fileman.html", "diskspace");
	exit;
	}


function listFiles($id, $gr, $path, $bmanager)
	{
	global $body, $aclfm;

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
			$this->bytes = babTranslate("bytes");
			$this->mkdir = babTranslate("Create");
			$this->rename = babTranslate("Rename");
			$this->delete = babTranslate("Delete directory");
			$this->directory = babTranslate("Directory");
			$this->download = babTranslate("Download");
			$this->cuttxt = babTranslate("Cut");
			$this->paste = babTranslate("Paste");
			$this->undo = babTranslate("Undo");
			$this->deltxt = babTranslate("Delete");
			$this->fullpath = getFullPath($gr, $id);
			$this->root = babTranslate("Home folder");
			$this->refresh = babTranslate("Refresh");
			$this->nametxt = babTranslate("Name");
			$this->sizetxt = babTranslate("Size");
			$this->modifiedtxt = babTranslate("Modified");
			$this->postedtxt = babTranslate("Posted by");
			$this->diskspace = babTranslate("Show disk space usage");
			$this->hitstxt = babTranslate("Hits");

			if( !empty($BAB_SESS_USERID))
				$this->rooturl = $GLOBALS['babUrl']."index.php?tg=fileman&idx=list&id=".$BAB_SESS_USERID."&gr=N&path=";
			else
				$this->rooturl = $GLOBALS['babUrl']."index.php?tg=fileman&idx=list&id=2&gr=Y&path=";
			$this->refreshurl = $GLOBALS['babUrl']."index.php?tg=fileman&idx=list&id=".$id."&gr=".$gr."&path=".$path;
			$this->urldiskspace = "javascript:Start('".$GLOBALS['babUrl']."index.php?tg=fileman&idx=disk&id=".$id."&gr=".$gr."&path=".$path."')";

			$this->path = $path;
			$this->id = $id;
			$this->gr = $gr;
			$this->upfolderimg = babPrintTemplate($this, "config.html", "parentfolder");
			$this->usrfolderimg = babPrintTemplate($this, "config.html", "userfolder");
			$this->grpfolderimg = babPrintTemplate($this, "config.html", "groupfolder");
			$this->manfolderimg = babPrintTemplate($this, "config.html", "managerfolder");
			$this->countmgrp = 0;
			$this->countgrp = 0;

			if( $gr == "Y")
				$this->rootpath = getGroupName($id);
			else
				$this->rootpath = "";

			$this->db = new db_mysql();
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
					$this->arrudir[] = $GLOBALS['babUrl']."index.php?tg=fileman&idx=list&id=".$id."&gr=".$gr."&path=".$p;
					}

				if( $gr == "N" && in_array(1, $aclfm['pr']) || $gr == "Y" )
					{
					$h = opendir($this->fullpath.$path."/");
					while (($f = readdir($h)) != false)
						{
						if ($f != "." and $f != "..") 
							{
							if (is_dir($this->fullpath.$path."/".$f))
								{
								$this->arrdir[] = $f;
								$this->arrudir[] = $GLOBALS['babUrl']."index.php?tg=fileman&idx=list&id=".$id."&gr=".$gr."&path=".$path.($path ==""?"":"/").$f;
								}
							}
						}
					closedir($h);
					$req = "select * from files where id_owner='".$id."' and bgroup='".$gr."' and state='' and path='".$path."'";
					if( !$this->bmanager )
						$req .= "and confirmed='Y'";
					$this->res = $this->db->db_query($req);
					$this->count = $this->db->db_num_rows($this->res);
					}
				$this->bdel = false;
				if( $this->bmanager )
					{
					$req = "select * from files where id_owner='".$id."' and bgroup='".$gr."' and state='X'";
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
				$this->name = getGroupName($this->arrgrp[$m]);
				$this->url = $GLOBALS['babUrl']."index.php?tg=fileman&idx=list&id=".$this->arrgrp[$m]."&gr=Y&path=";
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
				$this->name = getGroupName($this->arrmgrp[$m]);
				$this->url = $GLOBALS['babUrl']."index.php?tg=fileman&idx=list&id=".$this->arrmgrp[$m]."&gr=Y&path=";
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
					$this->arrext[$ext] = babPrintTemplate($this, "config.html", ".".$ext);
					if( empty($this->arrext[$ext]))
						$this->arrext[$ext] = babPrintTemplate($this, "config.html", ".unknown");						
					$this->fileimage = $this->arrext[$ext];
					}
				else if( empty($ext))
					{
					$this->fileimage = babPrintTemplate($this, "config.html", ".unknown");				
					}
				else
					$this->fileimage = $this->arrext[$ext];
				$this->name = $arr['name'];
				$this->url = $GLOBALS['babUrl']."index.php?tg=fileman&idx=upd&id=".$this->id."&gr=".$this->gr."&path=".$this->path."&file=".$arr['name'];
				$this->urlget = $GLOBALS['babUrl']."index.php?tg=fileman&idx=get&id=".$this->id."&gr=".$this->gr."&path=".$this->path."&file=".$arr['name'];
				$this->cuturl = $GLOBALS['babUrl']."index.php?tg=fileman&idx=cut&id=".$this->id."&gr=".$this->gr."&path=".$this->path."&file=".$arr['name'];				
				$this->delurl = $GLOBALS['babUrl']."index.php?tg=fileman&idx=del&id=".$this->id."&gr=".$this->gr."&path=".$this->path."&file=".$arr['name'];				
				if( file_exists($this->fullpath.$arr['path']."/".$arr['name']))
					{
					$fstat = stat($this->fullpath.$arr['path']."/".$arr['name']);
					$this->sizef = formatSize($fstat[7])." ".babTranslate("Kb");
					}
				else
					$this->sizef = "???";

				$this->modified = date("d/m/Y H:i", bab_mktime($arr['modified']));
				$this->postedby = getUserName($arr['author']);
				if( $this->bmanager && $arr['confirmed'] == "N")
					$this->bconfirmed = 1;
				else
					$this->bconfirmed = 0;
				$this->hits = $arr['hits'];
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
					$this->arrext[$ext] = babPrintTemplate($this, "config.html", ".".$ext);
				if( empty($this->arrext[$ext]))
					$this->arrext[$ext] = babPrintTemplate($this, "config.html", ".unknown");				
				$this->fileimage = $this->arrext[$ext];
				$this->name = $arr['name'];
				$this->url = $GLOBALS['babUrl']."index.php?tg=fileman&idx=upd&id=".$this->id."&gr=".$this->gr."&path=".$arr['path']."&file=".$arr['name'];
				$this->urlget = $GLOBALS['babUrl']."index.php?tg=fileman&idx=get&id=".$this->id."&gr=".$this->gr."&path=".$arr['path']."&file=".$arr['name'];
				$this->pasteurl = $GLOBALS['babUrl']."index.php?tg=fileman&idx=paste&id=".$this->id."&gr=".$this->gr."&path=".$arr['path']."&file=".$arr['name']."&tp=".$this->path;				
				if( file_exists($this->fullpath.$arr['path']."/".$arr['name']))
					{
					$fstat = stat($this->fullpath.$arr['path']."/".$arr['name']);
					$this->sizef = formatSize($fstat[7])." ".babTranslate("Kb");
					}
				else
					$this->sizef = "???";

				$this->modified = date("d/m/Y H:i", bab_mktime($arr['modified']));
				$this->postedby = getUserName($arr['author']);
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

	$pathx = getFullPath($gr, $id);
	if( !is_dir($pathx))
		{
		mkdir($pathx, 0700);
		}

	$temp = new temp($id, $gr, $path, $bmanager);
	$body->babecho(	babPrintTemplate($temp,"fileman.html", "fileslist"));
	return $temp->count;
	}

function addFile($id, $gr, $path, $description, $keywords)
	{
	global $body, $aclfm, $BAB_SESS_USERID;

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
			$this->name = babTranslate("Name");
			$this->description = babTranslate("Description");
			$this->keywords = babTranslate("Keywords");
			$this->add = babTranslate("Add");
			$this->attribute = babTranslate("Read only");
			$this->yes = babTranslate("Yes");
			$this->no = babTranslate("No");
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

		if( !$access && ( $id == 1 || $id == 2) && isUserAdministrator())
			$access = true;

		if( !$access && !empty($BAB_SESS_USERID) && isUserGroupManager($id))
			$access = true;
		}

	if( !$access )
		{
		$body->msgerror = babTranslate("Access denied");
		return;
		}

	$temp = new temp($id, $gr, $path, $description, $keywords);
	$body->babecho(	babPrintTemplate($temp,"fileman.html", "addfile"));
	}

function notifyApprover($grpname, $file, $path, $approveremail)
	{
	global $body, $BAB_SESS_USER, $BAB_SESS_EMAIL, $babAdminEmail, $babInstallPath;
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
            $this->message = babTranslate("A new file is waiting for you");
            $this->from = babTranslate("Author");
            $this->path = babTranslate("Path");
            $this->file = babTranslate("File");
            $this->group = babTranslate("Group");
            $this->pathname = $path == ""? "/": $path;
            $this->groupname = $grpname;
            $this->site = babTranslate("Web site");
            $this->date = babTranslate("Date");
            $this->dateval = bab_strftime(mktime());
            if( !empty($BAB_SESS_USER))
                $this->author = $BAB_SESS_USER;
            else
                $this->author = babTranslate("Unknown user");

            if( !empty($BAB_SESS_EMAIL))
                $this->authoremail = $BAB_SESS_EMAIL;
            else
                $this->authoremail = "";
			}
		}
	
	$tempa = new tempa($grpname, $file, $path);
	$message = babPrintTemplate($tempa,"mailinfo.html", "filewait");

    $mail = new babMail();
    $mail->mailTo($approveremail);
    $mail->mailFrom($babAdminEmail, "Ovidentia Administrator");
    $mail->mailSubject(babTranslate("New waiting file"));
    $mail->mailBody($message, "html");
    $mail->send();
	}

function saveFile($id, $gr, $path, $filename, $size, $tmp, $description, $keywords, $readonly)
	{
	global $body, $BAB_SESS_USERID, $aclfm;
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

		if( !$access && ( $id == 1 || $id == 2) && isUserAdministrator())
			{
			$confirmed = "Y";
			$access = true;
			}

		if( !$access && !empty($BAB_SESS_USERID) && isUserGroupManager($id))
			{
			$confirmed = "Y";
			$access = true;
			}
		}


	if( !$access )
		{
		$body->msgerror = babTranslate("Access denied");
		return;
		}

	if( empty($filename) || $filename == "none")
		{
		$body->msgerror = babTranslate("Please select a file to upload");
		return false;
		}

	if( $size > $GLOBALS['babMaxFileSize'])
		{
		$body->msgerror = babTranslate("The file was greater than the maximum allowed") ." :". $GLOBALS['babMaxFileSize'];
		return false;
		}
	$totalsize = getDirSize($GLOBALS['babUploadPath']);
	if( $size + $totalsize > $GLOBALS['babMaxTotalSize'])
		{
		$body->msgerror = babTranslate("There is not enough free space");
		return false;
		}
	$pathx = getFullPath($gr, $id);

	$totalsize = getDirSize($pathx);
	if( $size + $totalsize > ($gr == "Y"? $GLOBALS['babMaxGroupSize']: $GLOBALS['babMaxUserSize']))
		{
		$body->msgerror = babTranslate("There is not enough free space");
		return false;
		}

	if( substr($path, -1) == "/")
		$pathx .= substr($path, 0 , -1);
	else if( !empty($path))
		$pathx .= $path."/";	

	if( isset($GLOBALS['babFileNameTranslation']))
		$filename = strtr($filename, $GLOBALS['babFileNameTranslation']);

	if( file_exists($pathx.$filename))
		{
		$body->msgerror = babTranslate("A file with the same name already exists");
		return false;
		}

	set_time_limit(0);
	if( !move_uploaded_file($tmp, $pathx.$filename))
		{
		$body->msgerror = babTranslate("The file could not be uploaded");
		return false;
		}
	
	if( empty($BAB_SESS_USERID))
		$idcreator = 0;
	else
		$idcreator = $BAB_SESS_USERID;


	$db = new db_mysql();
	$req = "insert into files (name, description, keywords, path, id_owner, bgroup, link, readonly, state, created, author, modified, modifiedby, confirmed) values ";
	$req .= "('" .$filename. "', '" . $description. "', '" . $keywords. "', '" . $path. "', '" . $id. "', '" . $gr. "', '0', '" . $readonly. "', '', now(), '" . $idcreator. "', now(), '" . $idcreator. "', '". $confirmed."')";
	$res = $db->db_query($req);

	if( $confirmed == "N" )
		{
		$res = $db->db_query("select * from groups where id='".$id."'");
		if( $res && $db->db_num_rows($res) > 0)
			{
			$arr = $db->db_fetch_array($res);
			if( $arr['manager'] != 0)
				{
				$res = $db->db_query("select * from users where id='".$arr['manager']."'");
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

function saveUpdateFile($idf, $file, $id, $gr, $path, $name, $description, $keywords, $readonly, $confirm)
	{
	global $body, $BAB_SESS_USERID, $aclfm;

	$access = false;
	$bmanager = false;

	if( $gr == "N" )
		{
		if( in_array(1, $aclfm['pr']) )
			{
			$db = new db_mysql();
			$req = "select * from files where id_owner='".$id."' and bgroup='".$gr."' and state='' and path='".$path."' and name='".$file."'";
			$res = $db->db_query($req);
			if( $res && $db->db_num_rows($res) > 0 )
				{
				$arr = $db->db_fetch_array($res);
				$access = true;
				}
			}
		}

	if( $gr == "Y" && !empty($BAB_SESS_USERID) )
		{
		if( (($id == 2 || $id ==1) && isUserAdministrator()) || isUserGroupManager($id))
			{
			$db = new db_mysql();
			$req = "select * from files where id_owner='".$id."' and bgroup='".$gr."' and state='' and path='".$path."' and name='".$file."'";
			$res = $db->db_query($req);
			if( $res && $db->db_num_rows($res) > 0 )
				{
				$arr = $db->db_fetch_array($res);
				$bmanager = true;
				$access = true;
				}
			}
		}

	if( !$access )
		{
		$body->msgerror = babTranslate("Access denied");
		return;
		}

	if( empty($name))
		{
		$body->msgerror = babTranslate("Please give a valid file name");
		return false;
		}

	$pathx = getFullPath($gr, $id);

	if( !empty($path))
		$pathx .= $path."/";	

	if( isset($GLOBALS['babFileNameTranslation']))
		$name = strtr($name, $GLOBALS['babFileNameTranslation']);

	if( strcmp($file, $name))
		{
		if( !file_exists($pathx.$file))
			{
			$body->msgerror = babTranslate("File does'nt exist");
			return false;
			}
		if( !rename($pathx.$file, $pathx.$name))
			{
			$body->msgerror = babTranslate("The file could not be renamed");
			return false;
			}
		}
	
	$db = new db_mysql();
	$req = "update files set name='".$name."', description='".$description."', keywords='".$keywords."', readonly='".$readonly."', confirmed='".$confirm."' where id='".$idf."' and id_owner='".$id."' and bgroup='".$gr."' and path='".$path."' and name='".$file."'";
	$res = $db->db_query($req);
	return true;
	}

function createDirectory($dirname, $id, $gr, $path)
	{
	global $body, $BAB_SESS_USERID, $aclfm;
	if( $gr == "N" && $BAB_SESS_USERID == $id && in_array(1, $aclfm['pr']) || $gr == "Y" && ((($id == 2 || $id ==1) && isUserAdministrator()) || isUserGroupManager($id)))
		;
	else
		{
		$body->msgerror = babTranslate("You don't have permission to create directory");
		return false;
		}

	if( empty($dirname))
		{
		$body->msgerror = babTranslate("Please give a valid directory name");
		return false;
		}

	if( isset($GLOBALS['babFileNameTranslation']))
		$dirname = strtr($dirname, $GLOBALS['babFileNameTranslation']);

	$pathx = getFullPath($gr, $id).$path."/".$dirname;

	if( is_dir($pathx))
		{
		$body->msgerror = babTranslate("This folder already exists");
		return false;
		}
	else
		{
		mkdir($pathx, 0700);
		}
	}

function renameDirectory($dirname, $id, $gr, $path)
	{
	global $body, $BAB_SESS_USERID, $aclfm;
	if( empty($path))
		return false;

	if( $gr == "N" && $BAB_SESS_USERID == $id && in_array(1, $aclfm['pr']) || $gr == "Y" && ((($id == 2 || $id ==1) && isUserAdministrator()) || isUserGroupManager($id)))
		;
	else
		{
		$body->msgerror = babTranslate("You don't have permission to rename directory");
		return false;
		}

	if( empty($dirname))
		{
		$body->msgerror = babTranslate("Please give a valid directory name");
		return false;
		}

	$pathx = getFullPath($gr, $id);

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
		$body->msgerror = babTranslate("This folder already exists");
		return false;
		}
	else
		{
		if(rename($pathx.$uppath.$oldname, $pathx.$uppath.$dirname))
			{
			$len = strlen($path);
			$db = new db_mysql();
			$req = "select * from files where id_owner='".$id."' and bgroup='".$gr."'";
			$res = $db->db_query($req);
			while( $arr = $db->db_fetch_array($res))
				{
				if( substr($arr['path'], 0, $len) == $path )
					{
					$req = "update files set path='".str_replace($path, $uppath.$dirname, $arr['path'])."' where id='".$arr['id']."'";
					$db->db_query($req);
					}
				}
			$GLOBALS['path'] = $uppath.$dirname;
			}
		else
			{
			$body->msgerror = babTranslate("Cannot rename directory");
			return false;
			}
		}
	}

function removeDirectory($id, $gr, $path)
	{
	global $body, $BAB_SESS_USERID, $aclfm;
	if( empty($path))
		return false;

	if( $gr == "N" && $BAB_SESS_USERID == $id && in_array(1, $aclfm['pr']) || $gr == "Y" && ((($id == 2 || $id ==1) && isUserAdministrator()) || isUserGroupManager($id)))
		;
	else
		{
		$body->msgerror = babTranslate("You don't have permission to remove directory");
		return false;
		}

	$pathx = getFullPath($gr, $id);

	if( is_dir($pathx.$path))
		{
		$db = new db_mysql();
		$req = "select * from files where id_owner='".$id."' and bgroup='".$gr."' and path='".$path."'";
		$res = $db->db_query($req);
		while( $arr = $db->db_fetch_array($res))
			{
			if( @unlink($pathx.$path."/".$arr['name']))
				$db->db_query("delete from files where id='".$arr['id']."'");
			}

		if( $pos = strrpos($path, "/"))
			$uppath = substr($path, 0, $pos);
		else
			$uppath = "";
		$GLOBALS['path'] = $uppath;

		if(!@rmdir($pathx.$path))
			{
			$body->msgerror = babTranslate("Cannot remove directory");
			return false;
			}
		}
	}

function getFile( $file, $id, $gr, $path)
	{
	global $body, $BAB_SESS_USERID, $aclfm;
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

	$db = new db_mysql();
	if( $access )
		{
		$req = "select * from files where id_owner='".$id."' and bgroup='".$gr."' and path='".$path."' and name='".$file."'";
		$res = $db->db_query($req);
		if( $res && $db->db_num_rows($res) > 0 )
			{
			$arr = $db->db_fetch_array($res);
			$db->db_query("update files set hits='".($arr['hits'] + 1)."' where id='".$arr['id']."'");
			$access = true;
			}
		else
			$access = false;
		}

	if( !$access )
		{
		$body->msgerror = babTranslate("Access denied");
		return;
		}

	$mime = "application/octet-stream";
	if ($ext = strrchr($file,"."))
		{
		$ext = substr($ext,1);
		$db = new db_mysql();
		$res = $db->db_query("select * from mime_types where ext='".$ext."'");
		if( $res && $db->db_num_rows($res) > 0)
			{
			$arr = $db->db_fetch_array($res);
			$mime = $arr['mimetype'];
			}
		}
	$fullpath = getFullPath($gr, $id);
	if( !empty($path))
		$fullpath .= $path."/";

	$fullpath .= $file;
	$fsize = filesize($fullpath);
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
	global $body;

	if( !$bmanager)
		{
		$body->msgerror = babTranslate("Access denied");
		return false;
		}
	$db = new db_mysql();
	$req = "update files set state='X' where id_owner='".$id."' and bgroup='".$gr."' and state='' and path='".$path."' and name='".$file."'";
	$res = $db->db_query($req);
	return true;
	}

function delFile( $file, $id, $gr, $path, $bmanager)
	{
	global $body;

	if( !$bmanager)
		{
		$body->msgerror = babTranslate("Access denied");
		return false;
		}
	$db = new db_mysql();
	$req = "update files set state='D' where id_owner='".$id."' and bgroup='".$gr."' and state='' and path='".$path."' and name='".$file."'";
	$res = $db->db_query($req);
	return true;
	}

function pasteFile( $file, $id, $gr, $path, $tp, $bmanager)
	{
	global $body;

	if( !$bmanager)
		{
		$body->msgerror = babTranslate("Access denied");
		return false;
		}

	$pathx = getFullPath($gr, $id);
	if( file_exists($pathx.$tp."/".$file))
		{
		if( $path == $tp )
			{
			$db = new db_mysql();
			$req = "update files set state='' where id_owner='".$id."' and bgroup='".$gr."' and path='".$path."' and name='".$file."'";
			$res = $db->db_query($req);
			return true;
			}
		$body->msgerror = babTranslate("A file with the same name already exists");
		return false;
		}

	if( rename( $pathx.$path."/".$file, $pathx.$tp."/".$file))
		{
		$db = new db_mysql();
		$req = "update files set state='', path='".$tp."' where id_owner='".$id."' and bgroup='".$gr."' and path='".$path."' and name='".$file."'";
		$res = $db->db_query($req);
		return true;
		}
	else
		{
		$body->msgerror = babTranslate("Cannot paste file");
		return false;
		}
	}

function updateFile( $file, $id, $gr, $path, $aclfm)
	{
	global $body, $BAB_SESS_USERID, $aclfm;
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
		var $sizef;

		function temp($file, $id, $gr, $path, $arr, $bmanager)
			{
			$this->name = babTranslate("Name");
			$this->description = babTranslate("Description");
			$this->keywords = babTranslate("Keywords");
			$this->add = babTranslate("Update");
			$this->attribute = babTranslate("Read only");
			$this->yes = babTranslate("Yes");
			$this->no = babTranslate("No");
			$this->confirm = babTranslate("Confirm");
			$this->id = $id;
			$this->file = $file;
			$this->path = $path;
			$this->gr = $gr;
			$this->bmanager = $bmanager;
			$this->descval = $arr['description'];
			$this->keysval = $arr['keywords'];
			$this->nameval = $arr['name'];
			$this->idf = $arr['id'];
			$fstat = stat(getFullPath($gr, $id).$arr['path']."/".$arr['name']);
			$this->sizef = formatSize($fstat[7])." ".babTranslate("Kb")." ( ".formatSize($fstat[7], false) ." ".babTranslate("Bytes") ." )";
			if( $arr['readonly'] == "Y")
				{
				$this->yesselected = "selected";
				$this->noselected = "";
				}
			else
				{
				$this->noselected = "selected";
				$this->yesselected = "";
				}
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
			}
		}

	$access = false;
	$bmanager = false;

	if( $gr == "N" )
		{
		if( in_array(1, $aclfm['pr']) )
			{
			$db = new db_mysql();
			$req = "select * from files where id_owner='".$id."' and bgroup='".$gr."' and path='".$path."' and name='".$file."'";
			$res = $db->db_query($req);
			if( $res && $db->db_num_rows($res) > 0 )
				{
				$arr = $db->db_fetch_array($res);
				$access = true;
				}
			}
		}

	if( $gr == "Y" && !empty($BAB_SESS_USERID) )
		{
		if( (($id == 2 || $id ==1) && isUserAdministrator()) || isUserGroupManager($id))
			{
			$db = new db_mysql();
			$req = "select * from files where id_owner='".$id."' and bgroup='".$gr."' and path='".$path."' and name='".$file."'";
			$res = $db->db_query($req);
			if( $res && $db->db_num_rows($res) > 0 )
				{
				$arr = $db->db_fetch_array($res);
				$bmanager = true;
				$access = true;
				}
			}
		}

	if( !$access )
		{
		$body->msgerror = babTranslate("Access denied");
		return;
		}

	$temp = new temp($file, $id, $gr, $path, $arr, $bmanager);
	$body->babecho(	babPrintTemplate($temp,"fileman.html", "updatefile"));
	}

function deleteFiles($items, $gr, $id)
	{
	$db = new db_mysql();	
	$pathx = getFullPath($gr, $id);
	for( $i = 0; $i < count($items); $i++)
		{
		$res = $db->db_query("select * from files where id='".$items[$i]."'");
		if( $res && $db->db_num_rows($res) > 0 )
			{
			$arr = $db->db_fetch_array($res);
			if( file_exists($pathx.$arr['path']."/".$arr['name']))
				{
				if( unlink($pathx.$arr['path']."/".$arr['name']))
					{
					$db->db_query("delete from files where id='".$items[$i]."' or link='".$items[$i]."'");
					}
				}
			}
		}
	}

function restoreFiles($items)
	{
	$db = new db_mysql();	
	for( $i = 0; $i < count($items); $i++)
		{
		$db->db_query("update files set state='' where id='".$items[$i]."'");
		}
	}
	
/* main */
$aclfm = fileManagerAccessLevel();
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
					if( isUserAdministrator() )
						{
						$bmanager = true;
						$upload = true;
						}
					}
				else
					{
					$bmanager = isUserGroupManager($id);
					$upload = true;
					}
				}
			}
		}
	}

if( !$access)
	{
		$body->msgerror = babTranslate("Access denied");
		return;
	}

if( isset($addf) && $addf == "add")
	{
	if( !saveFile($id, $gr, $path, $HTTP_POST_FILES['uploadf']['name'], $HTTP_POST_FILES['uploadf']['size'],$HTTP_POST_FILES['uploadf']['tmp_name'], $description, $keywords, $readonly))
		$idx = "add";	
	}

if( isset($updf) && $updf == "upd")
	{
	saveUpdateFile($idf, $file, $id, $gr, $path, $name, $description, $keywords, $readonly, $confirm);
	$idx = "list";
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
	case "get":
		getFile($file, $id, $gr, $path);
		exit;
		break;

	case "trash":
		$body->title = babTranslate("Trash");
		listTrashFiles($id, $gr);
		$body->addItemMenu("list", babTranslate("List"), $GLOBALS['babUrl']."index.php?tg=fileman&idx=list&id=".$id."&gr=".$gr."&path=".$path);
		if( $upload)
			$body->addItemMenu("add", babTranslate("Upload"), $GLOBALS['babUrl']."index.php?tg=fileman&idx=add&id=".$id."&gr=".$gr."&path=".$path);
		if( $bmanager)
			$body->addItemMenu("trash", babTranslate("Trash"), $GLOBALS['babUrl']."index.php?tg=fileman&idx=trash&id=".$id."&gr=".$gr."&path=".$path);
		break;

	case "upd":
		$body->title = babTranslate("Update file");
		updateFile($file, $id, $gr, $path, $aclfm);
		$body->addItemMenu("list", babTranslate("List"), $GLOBALS['babUrl']."index.php?tg=fileman&idx=list&id=".$id."&gr=".$gr."&path=".$path);
		$body->addItemMenu("upd", babTranslate("Update"), $GLOBALS['babUrl']."index.php?tg=fileman&idx=upd&id=".$id."&gr=".$gr."&path=".$path);
		if( $bmanager)
			$body->addItemMenu("trash", babTranslate("Trash"), $GLOBALS['babUrl']."index.php?tg=fileman&idx=trash&id=".$id."&gr=".$gr."&path=".$path);
		break;
	case "add":
		$body->title = babTranslate("Upload file to")." /".$path. " ( ".formatSize($GLOBALS['babMaxFileSize'])." ".babTranslate("Kb") . " ".babTranslate("Max")." )";
		addFile($id, $gr, $path, $description, $keywords);
		$body->addItemMenu("list", babTranslate("List"), $GLOBALS['babUrl']."index.php?tg=fileman&idx=list&id=".$id."&gr=".$gr."&path=".$path);
		if( $upload)
			$body->addItemMenu("add", babTranslate("Upload"), $GLOBALS['babUrl']."index.php?tg=fileman&idx=add&id=".$id."&gr=".$gr."&path=".$path);
		if( $bmanager)
			$body->addItemMenu("trash", babTranslate("Trash"), $GLOBALS['babUrl']."index.php?tg=fileman&idx=trash&id=".$id."&gr=".$gr."&path=".$path);
		break;

	case "disk":
		showDiskSpace($id, $gr, $path);
		$body->addItemMenu("list", babTranslate("List"), $GLOBALS['babUrl']."index.php?tg=fileman&idx=list&id=".$id."&gr=".$gr."&path=".$path);
		if( $upload)
			$body->addItemMenu("add", babTranslate("Upload"), $GLOBALS['babUrl']."index.php?tg=fileman&idx=add&id=".$id."&gr=".$gr."&path=".$path);
		if( $bmanager)
			$body->addItemMenu("trash", babTranslate("Trash"), $GLOBALS['babUrl']."index.php?tg=fileman&idx=trash&id=".$id."&gr=".$gr."&path=".$path);
		break;

	case "cut":
		cutFile($file, $id, $gr, $path, $bmanager);
		/* no break */
	default:
	case "list":
		$body->title = "";
		$body->addItemMenu("list", babTranslate("List"), $GLOBALS['babUrl']."index.php?tg=fileman&idx=list&id=".$id."&gr=".$gr."&path=".$path);
		if( $upload)
			$body->addItemMenu("add", babTranslate("Upload"), $GLOBALS['babUrl']."index.php?tg=fileman&idx=add&id=".$id."&gr=".$gr."&path=".$path);
		if( $bmanager)
			$body->addItemMenu("trash", babTranslate("Trash"), $GLOBALS['babUrl']."index.php?tg=fileman&idx=trash&id=".$id."&gr=".$gr."&path=".$path);
		listFiles($id, $gr, $path, $bmanager);
		break;
	}
$body->setCurrentItemMenu($idx);
?>
