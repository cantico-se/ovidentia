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
include_once $babInstallPath.'utilit/fileincl.php';

define('BAB_FM_MAXLOGS'	, 10);

function showLockUnlockFile($idf, $idx)
{
	global $babBody;

	class temp
		{
		var $filename;
		var $foldertxt;
		var $foldername;
		var $pathtxt;
		var $pathname;
		var $commenttxt;
		var $lock;
		var $idf;
		var $what;
		var $bunlocklock;
		var $close;

		function temp($idf, $idx)
			{
			global $babDB, $arrfile, $arrfold;

			$this->idf = $idf;
			$this->what = $idx;
			$this->warningmsg = '';
			$this->bwarning = false;
			$this->bunlocklock = false;
			if( $arrfile['edit'] != 0 && $idx == 'lock' )
				{
				$this->bunlocklock = true;
				$this->close = bab_translate("Close");
				$this->warningmsg = bab_translate("This file is already locked");
				return;
				}

			if( $arrfile['edit'] == 0 && $idx == 'unlock' )
				{
				$this->bunlocklock = true;
				$this->close = bab_translate("Close");
				$this->warningmsg = bab_translate("This file is not locked");
				return;
				}

			if( $idx == 'lock')
				{
				$this->lock = bab_translate("Edit file");
				}
			else
				{
				$this->lock = bab_translate("Unedit file");
				list($idfai) = $babDB->db_fetch_array($babDB->db_query("select idfai from ".BAB_FM_FILESVER_TBL." where id='".$babDB->db_escape_string($arrfile['edit'])."'"));

				if( $idfai != 0 )
					{
					$this->bwarning = true;
					$this->warningmsg = bab_translate("Warning! A new version of this file is waiting to be validate. If you unlock this file, this version will be deleted!");
					}
				}

			$this->foldertxt = bab_translate("Folder");
			$this->pathtxt = bab_translate("Path");
			$this->commenttxt = bab_translate("Comment");

			$this->filename = bab_toHtml($arrfile['name']);
			$this->pathname = bab_toHtml("/".$arrfile['path']);
			if( $arrfile['bgroup'] == 'Y')
				$this->foldername = bab_toHtml($arrfold['folder']);
			else
				$this->foldername = '';
			}

		}

	$temp = new temp($idf, $idx);
	echo bab_printTemplate($temp, "filever.html", "lockfile");
}

function showCommitFile($idf)
{
	global $babBody;

	class temp
		{
		var $filename;
		var $filetxt;
		var $commenttxt;
		var $commit;
		var $idf;
		var $versiontxt;
		var $fileversion;
		var $no;
		var $yes;
		var $bunlocklock;
		var $close;
		var $warningmsg;

		function temp($idf)
			{
			global $babDB, $arrfile, $arrfold, $lockauthor;

			if( $arrfile['edit'] == 0 || $lockauthor != $GLOBALS['BAB_SESS_USERID'])
				{
				$this->bunlocklock = true;
				$this->close = bab_translate("Close");
				$this->warningmsg = bab_translate("This file is not locked");
				return;
				}
			$this->filetxt = bab_translate("File");
			$this->commenttxt = bab_translate("Comment");
			$this->commit = bab_translate("Commit file");
			$this->versiontxt = bab_translate("New major version?");
			$this->no = bab_translate("No");
			$this->yes = bab_translate("Yes");

			$this->idf = bab_toHtml($idf);
			$this->filename = bab_toHtml($arrfile['name']);
			$this->fileversion = bab_toHtml($arrfile['ver_major'].".".$arrfile['ver_minor']);
			}

		}

	$temp = new temp($idf);
	echo bab_printTemplate($temp, "filever.html", "commitfile");
}

function showConfirmFile($idf)
{
	global $babBody;

	class temp
		{
		var $filename;
		var $fileversion;
		var $commenttxt;
		var $comment;
		var $idf;
		var $confirmtxt;
		var $no;
		var $yes;
		var $confirm;
		var $urlget;

		function temp($idf)
			{
			global $babDB, $arrfile, $arrfold;
			$arr = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_FM_FILESVER_TBL." where id='".$babDB->db_escape_string($arrfile['edit'])."'"));

			$this->commenttxt = bab_translate("Comment");
			$this->confirm = bab_translate("Confirm file");
			$this->confirmtxt = bab_translate("Confirm");
			$this->no = bab_translate("No");
			$this->yes = bab_translate("Yes");

			$this->idf = bab_toHtml($idf);
			$this->filename = bab_toHtml($arrfile['name']);
			$this->fileversion = bab_toHtml($arr['ver_major'].".".$arr['ver_minor']);
			$this->comment = bab_toHtml($arr['comment']);
			$this->urlget = bab_toHtml($GLOBALS['babUrlScript']."?tg=filever&idx=get&idf=".$idf."&vmaj=".$arr['ver_major']."&vmin=".$arr['ver_minor']);
			}

		}

	$temp = new temp($idf);
	echo bab_printTemplate($temp, "filever.html", "confirmfile");
}

function showHistoricFile($idf, $pos)
{
	
	global $babBody;

	class temp
		{
		var $filename;
		var $titletxt;
		var $datetxt;
		var $authortxt;
		var $actiontxt;
		var $commenttxt;
		var $hourtxt;
		var $date;
		var $hour;
		var $author;
		var $action;
		var $comment;
		var $versiontxt;
		var $version;
		var $bmanager;
		var $cleantxt;
		var $cleanmsg;

		var $topname;
		var $topurl;
		var $prevname;
		var $prevurl;
		var $nextname;
		var $nexturl;
		var $bottomname;
		var $bottomurl;
		var $altbg = true;

		function temp($idf, $pos)
			{
			global $babDB, $arrfile, $arrfold;

			$this->topurl = "";
			$this->bottomurl = "";
			$this->nexturl = "";
			$this->prevurl = "";
			$this->topname = "";
			$this->bottomname = "";
			$this->nextname = "";
			$this->prevname = "";
			$this->titletxt = bab_translate("File");
			$this->datetxt = bab_translate("Date");
			$this->hourtxt = bab_translate("Hour");
			$this->commenttxt = bab_translate("Comment");
			$this->authortxt = bab_translate("Author");
			$this->actiontxt = bab_translate("Action");
			$this->versiontxt = bab_translate("Version");
			$this->idf = $idf;
			

			if(bab_isAccessValid(BAB_FMMANAGERS_GROUPS_TBL, $arrfold['id']))
				{
				$this->bmanager = true;
				$this->cleantxt = bab_translate("Clean log");
				$this->datetxt2 = bab_translate("Date")." ( ".bab_translate("dd-mm-yyyy")." )";
				$this->cleanmsg = bab_translate("Clean all log entries before a given date");
				}
			else
				$this->bmanager = false;

			$res = $babDB->db_query("select count(*) as total from ".BAB_FM_FILESLOG_TBL." where id_file='".$babDB->db_escape_string($idf)."'");
			$row = $babDB->db_fetch_array($res);
			$total = $row["total"];

			if( $total > BAB_FM_MAXLOGS)
				{
				if( $pos > 0)
					{
					$this->topurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=filever&idx=hist&idf=".$idf."&pos=0");
					$this->topname = "&lt;&lt;";
					}

				$next = $pos - BAB_FM_MAXLOGS;
				if( $next >= 0)
					{
					$this->prevurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=filever&idx=hist&idf=".$idf."&pos=".$next);
					$this->prevname = "&lt;";
					}

				$next = $pos + BAB_FM_MAXLOGS;
				if( $next < $total)
					{
					$this->nexturl = bab_toHtml($GLOBALS['babUrlScript']."?tg=filever&idx=hist&idf=".$idf."&pos=".$next);
					$this->nextname = "&gt;";
					if( $next + BAB_FM_MAXLOGS < $total)
						{
						$bottom = $total - BAB_FM_MAXLOGS;
						}
					else
						$bottom = $next;
					$this->bottomurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=filever&idx=hist&idf=".$idf."&pos=".$bottom);
					$this->bottomname = "&gt;&gt;";
					}
				}

			$req = "select * FROM ".BAB_FM_FILESLOG_TBL." WHERE id_file='".$babDB->db_escape_string($idf)."' order by date desc";
			if( $total > BAB_FM_MAXLOGS)
				{
				$req .= " limit ".$babDB->db_escape_string($pos).",".BAB_FM_MAXLOGS;
				}
			$this->filename = $arrfile['name'];
			$this->res = $babDB->db_query($req);
			$this->count = $babDB->db_num_rows($this->res);


			
			}

		function getnextlog()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				global $babDB, $arrfile, $babFileActions;
				$arr = $babDB->db_fetch_array($this->res);
				$this->altbg = !$this->altbg;
				$time = bab_mktime($arr['date']);
				$this->date = bab_toHtml(bab_strftime($time, false));
				$this->hour = bab_toHtml(bab_time($time));
				$this->author = bab_toHtml(bab_getUserName($arr['author']));
				$this->comment = bab_toHtml($arr['comment']);
				$this->action = bab_toHtml($arr['action']);
				$this->version = bab_toHtml($arr['version']);
				
				$i++;
				return true;
				}
			else
				return false;
			}

		}

	

	$temp = new temp($idf, $pos);
	$babBody->babpopup(bab_printTemplate($temp, "filever.html", "filehistoric"));

}


function showVersionHistoricFile($idf, $pos)
{
	global $babBody;

	class temp
		{
		var $filename;
		var $titletxt;
		var $datetxt;
		var $authortxt;
		var $actiontxt;
		var $commenttxt;
		var $hourtxt;
		var $date;
		var $hour;
		var $author;
		var $action;
		var $comment;
		var $versiontxt;
		var $version;
		var $geturl;
		var $idf;
		var $deletealt;
		var $bmanager;

		var $topname;
		var $topurl;
		var $prevname;
		var $prevurl;
		var $nextname;
		var $nexturl;
		var $bottomname;
		var $bottomurl;

		function temp($idf, $pos)
			{
			global $babDB, $arrfile, $arrfold;

			$this->topurl = "";
			$this->bottomurl = "";
			$this->nexturl = "";
			$this->prevurl = "";
			$this->topname = "";
			$this->bottomname = "";
			$this->nextname = "";
			$this->prevname = "";
			$this->titletxt = bab_translate("File");
			$this->datetxt = bab_translate("Date");
			$this->hourtxt = bab_translate("Hour");
			$this->commenttxt = bab_translate("Comment");
			$this->authortxt = bab_translate("Author");
			$this->actiontxt = bab_translate("Action");
			$this->versiontxt = bab_translate("Version");
			$this->deletealt = bab_translate("Delete");
			$this->t_index = bab_translate("Indexation");

			if(bab_isAccessValid(BAB_FMMANAGERS_GROUPS_TBL, $arrfold['id']))
				$this->bmanager = true;
			else
				$this->bmanager = false;
			$this->idf = $idf;
			$res = $babDB->db_query("select count(*) as total from ".BAB_FM_FILESVER_TBL." where id_file='".$babDB->db_escape_string($idf)."' and idfai='0' and confirmed='Y'");
			$row = $babDB->db_fetch_array($res);
			$total = $row["total"];

			if( $total > BAB_FM_MAXLOGS)
				{
				if( $pos > 0)
					{
					$this->topurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=filever&idx=lvers&idf=".$idf."&pos=0");
					$this->topname = "&lt;&lt;";
					}

				$next = $pos - BAB_FM_MAXLOGS;
				if( $next >= 0)
					{
					$this->prevurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=filever&idx=lvers&idf=".$idf."&pos=".$next);
					$this->prevname = "&lt;";
					}

				$next = $pos + BAB_FM_MAXLOGS;
				if( $next < $total)
					{
					$this->nexturl = bab_toHtml($GLOBALS['babUrlScript']."?tg=filever&idx=lvers&idf=".$idf."&pos=".$next);
					$this->nextname = "&gt;";
					if( $next + BAB_FM_MAXLOGS < $total)
						{
						$bottom = $total - BAB_FM_MAXLOGS;
						}
					else
						$bottom = $next;
					$this->bottomurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=filever&idx=lvers&idf=".$idf."&pos=".$bottom);
					$this->bottomname = "&gt;&gt;";
					}
				}

			$req = "select * from ".BAB_FM_FILESVER_TBL." where id_file='".$babDB->db_escape_string($idf)."' and idfai='0' and confirmed='Y' order by date desc";
			if( $total > BAB_FM_MAXLOGS)
				{
				$req .= " limit ".$babDB->db_escape_string($pos).",".BAB_FM_MAXLOGS;
				}
			$GLOBALS['babBody']->setTitle($arrfile['name']);
			$this->res = $babDB->db_query($req);
			$this->count = $babDB->db_num_rows($this->res);

			if ($engine = bab_searchEngineInfos()) {
				$this->index = true;
				} else {
				$this->index = false;
				}
			}

		function getnextvers()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				global $babDB, $arrfile, $babFileActions;
				$arr = $babDB->db_fetch_array($this->res);
				$time = bab_mktime($arr['date']);
				$this->date = bab_toHtml(bab_strftime($time, false));
				$this->hour = bab_toHtml(bab_time($time));
				$this->author = bab_toHtml(bab_getUserName($arr['author']));
				$this->comment = bab_toHtml($arr['comment']);
				$this->version = bab_toHtml($arr['ver_major'].".".$arr['ver_minor']);
				$this->geturl = bab_toHtml( $GLOBALS['babUrlScript']."?tg=filever&idx=get&idf=".$this->idf."&vmaj=".$arr['ver_major']."&vmin=".$arr['ver_minor']);
				$this->index_status = bab_toHtml(bab_getIndexStatusLabel($arr['index_status']));
				$i++;
				return true;
				}
			else
				return false;
			}

		}

	$temp = new temp($idf, $pos);
	$babBody->babpopup(bab_printTemplate($temp, "filever.html", "filevershistoric"));
}


function getFile( $idf, $vmajor, $vminor )
	{
	global $babBody, $babDB, $babFileActions, $arrfile, $arrfold, $lockauthor;
	
	$inl = bab_rp('inl', false);
	if (false === $inl) {
		$inl = bab_getFileContentDisposition() == 1? 1: ''; 
	}

	$mime = bab_getFileMimeType($arrfile['name']);

	$fullpath = bab_getUploadFullPath($arrfile['bgroup'], $arrfile['id_owner']);
	if( !empty($arrfile['path']))
		$fullpath .= $arrfile['path']."/";

	$fullpath .= BAB_FVERSION_FOLDER."/".$vmajor.",".$vminor.",".$arrfile['name'];
	$fsize = filesize($fullpath);
	if( strtolower(bab_browserAgent()) == "msie")
		header('Cache-Control: public');
	if( $inl == "1" )
		header("Content-Disposition: inline; filename=\"".$arrfile['name']."\""."\n");
	else
		header("Content-Disposition: attachment; filename=\"".$arrfile['name']."\""."\n");
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


function fileUnload($idf)
	{
	class temp
		{
		var $message;
		var $close;
		var $redirecturl;

		function temp($idf)
			{
			global $arrfile;
			$this->message = bab_translate("Your file list has been updated");
			$this->close = bab_translate("Close");
			$this->redirecturl = bab_toHtml( $GLOBALS['babUrlScript']."?tg=fileman&idx=list&id=".$arrfile['id_owner']."&gr=".$arrfile['bgroup']."&path=".urlencode($arrfile['path']));
			}
		}

	$temp = new temp($idf);
	echo bab_printTemplate($temp,"filever.html", "fileunload");
	}


function lockFile($idf, $comment )
{
	global $babBody, $arrfile, $babDB, $babFileActions;

	if( $arrfile['edit'] == 0 && $GLOBALS['BAB_SESS_USERID'] != '' )
		{
		$babDB->db_query("insert into ".BAB_FM_FILESLOG_TBL." ( id_file, date, author, action, comment, version) values ('".$babDB->db_escape_string($idf)."', now(), '".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."', '".BAB_FACTION_EDIT."', '".$babDB->db_escape_string($comment)."', '".$arrfile['ver_major'].".".$babDB->db_escape_string($arrfile['ver_minor'])."')");

		$babDB->db_query("insert into ".BAB_FM_FILESVER_TBL." ( id_file, date, author, ver_major, ver_minor, idfai ) values ('".$babDB->db_escape_string($idf)."', now(), '".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."', '".$babDB->db_escape_string($arrfile['ver_major'])."', '".$babDB->db_escape_string($arrfile['ver_minor'])."', '0')");
		$idfver = $babDB->db_insert_id(); 
		
		$babDB->db_query("update ".BAB_FILES_TBL." set edit='".$babDB->db_escape_string($idfver)."' where id='".$babDB->db_escape_string($idf)."'");
		}

}

function unlockFile($idf, $comment )
{
	global $babBody, $babDB, $babFileActions, $arrfile, $arrfold, $lockauthor;


	if( $arrfile['edit'] != 0 && $GLOBALS['BAB_SESS_USERID'] != '' )
		{
		if( $lockauthor == $GLOBALS['BAB_SESS_USERID'] || bab_isAccessValid(BAB_FMMANAGERS_GROUPS_TBL, $arrfold['id']) )
			{
			$babDB->db_query("insert into ".BAB_FM_FILESLOG_TBL." ( id_file, date, author, action, comment, version) values ('".$babDB->db_escape_string($idf)."', now(), '".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."', '".BAB_FACTION_UNEDIT."', '".$babDB->db_escape_string($comment)."', '".$babDB->db_escape_string($arrfile['ver_major']).".".$babDB->db_escape_string($arrfile['ver_minor'])."')");
			
			$arr = $babDB->db_fetch_array($babDB->db_query("select idfai, ver_major, ver_minor from ".BAB_FM_FILESVER_TBL." where id='".$babDB->db_escape_string($arrfile['edit'])."'"));
			if( $arr['idfai'] != 0 )
				{
				include_once $GLOBALS['babInstallPath']."utilit/afincl.php";
				deleteFlowInstance($arr['idfai']);
				$pathx = bab_getUploadFullPath($arrfile['bgroup'], $arrfile['id_owner']);
				if( substr($arrfile['path'], -1) == "/")
					$pathx .= substr($arrfile['path'], 0 , -1);
				else if( !empty($arrfile['path']))
					$pathx .= $arrfile['path']."/";

				unlink($pathx.BAB_FVERSION_FOLDER."/".$arr['ver_major'].",".$arr['ver_minor'].",".$arrfile['name']);
				}

			$babDB->db_query("delete from ".BAB_FM_FILESVER_TBL." where id='".$babDB->db_escape_string($arrfile['edit'])."'");
			$babDB->db_query("update ".BAB_FILES_TBL." set edit='0' where id='".$babDB->db_escape_string($idf)."'");
			}
		}

}

function commitFile($idf, $comment, $vermajor, $filename, $size, $tmp )
{
	global $babBody, $babDB, $babFileActions, $arrfile, $arrfold, $lockauthor;

	if( $lockauthor != $GLOBALS['BAB_SESS_USERID'])
		{
		$babBody->msgerror = bab_translate("Access denied");
		return false;
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

	$pathx = bab_getUploadFullPath($arrfile['bgroup'], $arrfile['id_owner']);
	$pathy = bab_getUploadFmPath($arrfile['bgroup'], $arrfile['id_owner']);

	$totalsize = getDirSize($pathx);
	if( $size + $totalsize > $GLOBALS['babMaxGroupSize'] )
		{
		$babBody->msgerror = bab_translate("There is not enough free space");
		return false;
		}

	if( substr($arrfile['path'], -1) == "/")
		$pathx .= substr($arrfile['path'], 0 , -1);
	else if( !empty($arrfile['path']))
		$pathx .= $arrfile['path']."/";

	if( !is_dir($pathx.BAB_FVERSION_FOLDER))
		bab_mkdir($pathx.BAB_FVERSION_FOLDER, $GLOBALS['babMkdirMode']);

	if( $vermajor == 'Y' )
		{
		$vmajor = ($arrfile['ver_major'] + 1);
		$vminor = 0;
		}
	else
		{
		$vmajor = $arrfile['ver_major'];
		$vminor = ($arrfile['ver_minor'] + 1);
		}


	if( !get_cfg_var('safe_mode'))
		set_time_limit(0);
	if( !move_uploaded_file($tmp, $pathx.BAB_FVERSION_FOLDER."/".$vmajor.",".$vminor.",".$arrfile['name']))
		{
		$babBody->msgerror = bab_translate("The file could not be uploaded");
		return false;
		}

	if( $arrfold['idsa'] != 0 )
		{
		include_once $GLOBALS['babInstallPath']."utilit/afincl.php";
		if( $arrfold['auto_approbation'] == 'Y' )
			{
			$idfai = makeFlowInstance($arrfold['idsa'], "filv-".$arrfile['edit'], $GLOBALS['BAB_SESS_USERID']);
			}
		else
			{
			$idfai = makeFlowInstance($arrfold['idsa'], "filv-".$arrfile['edit']);
			}
		}

	if( $arrfold['idsa'] == 0 || $idfai === true)
		{
		$babDB->db_query("insert into ".BAB_FM_FILESLOG_TBL." ( id_file, date, author, action, comment, version) values ('".$babDB->db_escape_string($idf)."', now(), '".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."', '".BAB_FACTION_COMMIT."', '".$babDB->db_escape_string($comment)."', '".$babDB->db_escape_string($vmajor).".".$babDB->db_escape_string($vminor)."')");

		copy($pathx.$arrfile['name'], $pathx.BAB_FVERSION_FOLDER."/".$arrfile['ver_major'].",".$arrfile['ver_minor'].",".$arrfile['name']);
		copy($pathx.BAB_FVERSION_FOLDER."/".$vmajor.",".$vminor.",".$arrfile['name'], $pathx.$arrfile['name']);
		unlink($pathx.BAB_FVERSION_FOLDER."/".$vmajor.",".$vminor.",".$arrfile['name']);

		// index

		include_once $GLOBALS['babInstallPath']."utilit/indexincl.php";
		$index_status = bab_indexOnLoadFiles(
			array($pathx.$arrfile['name'],  $pathx.BAB_FVERSION_FOLDER."/".$arrfile['ver_major'].",".$arrfile['ver_minor'].",".$arrfile['name']),
			'bab_files'
		);

		$babDB->db_query("update ".BAB_FILES_TBL." set edit='0', modified=now(), modifiedby='".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."', ver_major='".$babDB->db_escape_string($vmajor)."', ver_minor='".$babDB->db_escape_string($vminor)."', ver_comment='".$babDB->db_escape_string($comment)."', index_status='".$babDB->db_escape_string($index_status)."' where id='".$babDB->db_escape_string($idf)."'");

		

		$babDB->db_query("update ".BAB_FM_FILESVER_TBL." set ver_major='".$babDB->db_escape_string($arrfile['ver_major'])."', ver_minor='".$babDB->db_escape_string($arrfile['ver_minor'])."', comment='".$babDB->db_escape_string($arrfile['ver_comment'])."', idfai='0', confirmed='Y', index_status='".$babDB->db_escape_string($index_status)."' where id='".$babDB->db_escape_string($arrfile['edit'])."'");

		if (BAB_INDEX_STATUS_INDEXED === $index_status) {
			$obj = new bab_indexObject('bab_files');
			$obj->setIdObjectFile($pathy.$arrfile['name'], $idf, $arrfile['id_owner']);
		
			$obj->setIdObjectFile($pathy.BAB_FVERSION_FOLDER."/".$arrfile['ver_major'].",".$arrfile['ver_minor'].",".$arrfile['name'], $idf, $arrfile['id_owner']);
		}

		if( $arrfold['filenotify'] == 'Y' )
			fileNotifyMembers($filename, $arrfile['path'], $arrfile['id_owner'], bab_translate("A new version file has been uploaded"));
		}
	elseif(!empty($idfai))
		{
		$babDB->db_query("insert into ".BAB_FM_FILESLOG_TBL." ( id_file, date, author, action, comment, version) values ('".$babDB->db_escape_string($idf)."', now(), '".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."', '".BAB_FACTION_COMMIT."', '".$babDB->db_escape_string(bab_translate("Waiting to be validate"))."', '".$babDB->db_escape_string($vmajor).".".$babDB->db_escape_string($vminor)."')");

		$babDB->db_query("update ".BAB_FM_FILESVER_TBL." set ver_major='".$babDB->db_escape_string($vmajor)."', ver_minor='".$babDB->db_escape_string($vminor)."', comment='".$babDB->db_escape_string($comment)."', idfai='0' where id='".$babDB->db_escape_string($arrfile['edit'])."'");

		$babDB->db_query("update ".BAB_FM_FILESVER_TBL." set idfai='".$babDB->db_escape_string($idfai)."' where id='".$babDB->db_escape_string($arrfile['edit'])."'");
		$nfusers = getWaitingApproversFlowInstance($idfai, true);
		notifyFileApprovers($arrfile['id'], $nfusers, bab_translate("A new version file is waiting for you"));
		}
}

function confirmFile($idf, $bconfirm )
{
	global $babBody, $babDB, $babFileActions, $arrfile, $arrfold, $lockauthor;

	include_once $GLOBALS['babInstallPath']."utilit/afincl.php";

	$arr = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_FM_FILESVER_TBL." where id='".$babDB->db_escape_string($arrfile['edit'])."'"));
	$arrschi = bab_getWaitingIdSAInstance($GLOBALS['BAB_SESS_USERID']);
	if( count($arrschi) > 0  && in_array($arr['idfai'], $arrschi) )
		{
		$pathx = bab_getUploadFullPath($arrfile['bgroup'], $arrfile['id_owner']);
		if( substr($arrfile['path'], -1) == "/")
			$pathx .= substr($arrfile['path'], 0 , -1);
		else if( !empty($arrfile['path']))
			$pathx .= $arrfile['path']."/";

		$res = updateFlowInstance($arr['idfai'], $GLOBALS['BAB_SESS_USERID'], $bconfirm == "Y"? true: false);
		switch($res)
			{
			case 0:
				unlink($pathx.BAB_FVERSION_FOLDER."/".$arr['ver_major'].",".$arr['ver_minor'].",".$arrfile['name']);
				$babDB->db_query("update ".BAB_FILES_TBL." set edit='0' where id='".$babDB->db_escape_string($idf)."'");
				$babDB->db_query("delete from ".BAB_FM_FILESVER_TBL." where id='".$babDB->db_escape_string($arrfile['edit'])."'");
				$babDB->db_query("insert into ".BAB_FM_FILESLOG_TBL." ( id_file, date, author, action, comment, version) values ('".$babDB->db_escape_string($idf)."', now(), '".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."', '".BAB_FACTION_COMMIT."', '".$babDB->db_escape_string(bab_translate("Refused by ").$GLOBALS['BAB_SESS_USER'])."', '".$babDB->db_escape_string($arr['ver_major'].".".$arr['ver_minor'])."')");
				deleteFlowInstance($arr['idfai']);
				notifyFileAuthor(bab_translate("Your new file version has been refused"),$arr['ver_major'].".".$arr['ver_minor'], $arr['author'], $arrfile['name']);
				// notify user
				break;
			case 1:
				deleteFlowInstance($arr['idfai']);
				acceptFileVersion($arrfile, $arr, $arrfold['filenotify']);
				break;
			default:
				$nfusers = getWaitingApproversFlowInstance($arr['idfai'], true);
				if( count($nfusers) > 0 )
					notifyFileApprovers($arr['id'], $nfusers, bab_translate("A new version file is waiting for you"));
				break;
			}
		}
}

function deleteFileVersions($idf, $versions )
{
	global $babBody, $babDB, $babFileActions, $arrfile, $arrfold, $lockauthor;

	$count = count($versions);
	if( $count > 0 )
	{
		$fullpath = bab_getUploadFullPath($arrfile['bgroup'], $arrfile['id_owner']);
		if( !empty($arrfile['path']))
			$fullpath .= $arrfile['path']."/";

		$fullpath .= BAB_FVERSION_FOLDER."/";

		for($i = 0; $i < $count; $i++ )
		{
			$r = explode(".", $versions[$i]);
			$res = $babDB->db_query("select id from ".BAB_FM_FILESVER_TBL." where id_file='".$babDB->db_escape_string($idf)."' and ver_major='".$babDB->db_escape_string($r[0])."' and ver_minor='".$babDB->db_escape_string($r[1])."'");

			if( $res && $babDB->db_num_rows($res) == 1 )
			{
				$arr = $babDB->db_fetch_array($res);
				unlink($fullpath.$r[0].",".$r[1].",".$arrfile['name']);
				$babDB->db_query("delete from ".BAB_FM_FILESVER_TBL." where id='".$babDB->db_escape_string($arr['id'])."'");
				$babDB->db_query("insert into ".BAB_FM_FILESLOG_TBL." ( id_file, date, author, action, comment, version) values ('".$babDB->db_escape_string($idf)."', now(), '".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."', '".BAB_FACTION_OTHER."', '".$babDB->db_escape_string(bab_translate("File deleted"))."', '".$babDB->db_escape_string($r[0]).".".$babDB->db_escape_string($r[1])."')");
			}
		}
	}
}

function cleanFileLog($idf, $date )
{
	global $babBody, $babDB, $babFileActions, $arrfile, $arrfold, $lockauthor;

	$ar = explode("-", $date);
	if( count($ar) != 3 || !is_numeric($ar[0]) || !is_numeric($ar[1]) || !is_numeric($ar[2]))
		return;

	$dateb = sprintf("%04d-%02d-%02d 00:00:00", $ar[2], $ar[1], $ar[0]);
	$babDB->db_query("delete from ".BAB_FM_FILESLOG_TBL." where id_file='".$babDB->db_escape_string($idf)."' and date <='".$babDB->db_escape_string($dateb)."'");
}

/* main */
$bupdate = false;
$bdownload = false;
$idx = bab_rp('idx','denied');

$arrfile = array();
$arrfold = array();
$lockauthor = 0;



if( isset($_REQUEST['idf']) )
{
	$idf = (int) $_REQUEST['idf'];

	$res = $babDB->db_query("select * from ".BAB_FILES_TBL." where id='".$babDB->db_escape_string($idf)."' and state=''");
	if( $res && $babDB->db_num_rows($res) > 0 )
	{
		$arrfile = $babDB->db_fetch_array($res);
		if( $arrfile['bgroup'] == 'Y' && $arrfile['confirmed'] == 'Y')
		{
			$res = $babDB->db_query("select * from ".BAB_FM_FOLDERS_TBL." where id='".$babDB->db_escape_string($arrfile['id_owner'])."'");
			$arrfold = $babDB->db_fetch_array($res);

			if( $arrfold['version'] ==  'Y' )
			{
				if(bab_isAccessValid(BAB_FMMANAGERS_GROUPS_TBL, $arrfold['id']) || bab_isAccessValid(BAB_FMUPDATE_GROUPS_TBL, $arrfile['id_owner']))
				{
					$bupdate = true;
					if( $arrfile['edit'] != 0 )
						list($lockauthor) = $babDB->db_fetch_array($babDB->db_query("select author from ".BAB_FM_FILESVER_TBL." where id='".$babDB->db_escape_string($arrfile['edit'])."'"));
				}

				if(bab_isAccessValid(BAB_FMDOWNLOAD_GROUPS_TBL, $arrfile['id_owner']))
					$bdownload = true;
			}
		}


		if( isset($afile))
		{
			if( $bupdate == true )
			{
				if($_POST['afile'] == 'lock')
					lockFile($idf, $_POST['comment']); 
				else if( $_POST['afile'] == 'unlock')
					unlockFile($idf, $_POST['comment']); 
				else if( $_POST['afile'] == 'commit' )
					commitFile($idf, $_POST['comment'], $_POST['vermajor'], $_FILES['uploadf']['name'], $_FILES['uploadf']['size'], $_FILES['uploadf']['tmp_name'] ); 
				else if( bab_isAccessValid(BAB_FMMANAGERS_GROUPS_TBL, $arrfold['id']) && $_POST['afile'] == 'delv' )
					{
					deleteFileVersions($idf, $versions ); 
					}
				else if( bab_isAccessValid(BAB_FMMANAGERS_GROUPS_TBL, $arrfold['id']) && $_POST['afile'] == 'cleanlog' )
					{
					cleanFileLog($idf, $date ); 
					}
			}

			if( $afile == 'confirm' )
				{
				confirmFile($idf, $bconfirm ); 
				}

		}

	}

}
else
	$idx = 'denied';

switch($idx)
	{
	case "commit":
		showCommitFile(bab_rp('idf'));
		exit;
		break;

	case "hist":
		if( $bupdate )
			{
			showHistoricFile(
				bab_rp('idf'), 
				bab_rp('pos',0)
				);
			exit;
			}
		else
			$babBody->msgerror = bab_translate("Access denied");
		break;

	case "lvers":
		if( $bupdate || $bdownload )
			{
			showVersionHistoricFile(
				bab_rp('idf'), 
				bab_rp('pos',0)
				);
			exit;
			}
		else
			$babBody->msgerror = bab_translate("Access denied");
		break;

	case "unload":
		fileUnload(
			bab_rp('idf')
			);
		exit;
		break;

	case 'lock':
		if( $bupdate)
			{
			showLockUnlockFile(bab_rp('idf'), $idx);
			exit;
			}
		else
			$babBody->msgerror = bab_translate("Access denied");
		break;
	
	case 'unlock':
		if( $bupdate)
			{
			showLockUnlockFile(bab_rp('idf'), $idx);
			exit;
			}
		else
			$babBody->msgerror = bab_translate("Access denied");
		break;

	case 'conf':
		include_once $GLOBALS['babInstallPath']."utilit/afincl.php";
		if( isUserApproverFlow($arrfold['idsa'], $BAB_SESS_USERID) )
		{
			showConfirmFile(bab_rp('idf'));
			exit;
		}
		else
			$babBody->msgerror = bab_translate("Access denied");
		break;

	case "get":
		if( $bupdate || $bdownload)
			{
			getFile(
				bab_rp('idf'), 
				bab_rp('vmaj'), 
				bab_rp('vmin')
			);
			exit;
			}
		else
			$babBody->msgerror = bab_translate("Access denied");
		break;

	case 'denied':
	default:
		$babBody->msgerror = bab_translate("Access denied");
		break;
	}
$babBody->setCurrentItemMenu($idx);
?>