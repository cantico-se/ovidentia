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
include_once $babInstallPath."utilit/uploadincl.php";

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
			global $babDB, $babBody;
			
			$fm_file = fm_getFileAccess($idf);
			$oFolderFile =& $fm_file['oFolderFile'];
			$oFmFolder =& $fm_file['oFmFolder'];
			
			$this->idf = $idf;
			$this->what = $idx;
			$this->warningmsg = '';
			$this->bwarning = false;
			$this->bunlocklock = false;
			
			if(!is_null($oFolderFile) && !is_null($oFmFolder))
			{
				if(0 !== $oFolderFile->getFolderFileVersionId() && $idx == 'lock')
				{
					$this->bunlocklock = true;
					$this->close = bab_translate("Close");
					$this->warningmsg = bab_translate("This file is already locked");
					return;
				}
	
				if(0 === $oFolderFile->getFolderFileVersionId() && $idx == 'unlock')
				{
					$this->bunlocklock = true;
					$this->close = bab_translate("Close");
					$this->warningmsg = bab_translate("This file is not locked");
					return;
				}
	
				if($idx == 'lock')
				{
					$this->lock = bab_translate("Edit file");
				}
				else
				{
					$this->lock = bab_translate("Unedit file");
					
					$oFolderFileVersionSet = new BAB_FolderFileVersionSet();
	
					$oId =& $oFolderFileVersionSet->aField['iId'];
					$oFolderFileVersion = $oFolderFileVersionSet->get($oId->in($oFolderFile->getFolderFileVersionId()));
					if(!is_null($oFolderFileVersion))
					{
						if(0 !== $oFolderFileVersion->getFlowApprobationInstanceId())
						{
							$this->bwarning = true;
							$this->warningmsg = bab_translate("Warning! A new version of this file is waiting to be validate. If you unlock this file, this version will be deleted!");
						}
					}
				}
	
				$this->foldertxt = bab_translate("Folder");
				$this->pathtxt = bab_translate("Path");
				$this->commenttxt = bab_translate("Comment");
	
				$babBody->setTitle($oFolderFile->getName());
				$this->pathname = bab_toHtml("/".$oFolderFile->getPathName());
				if('Y' === $oFolderFile->getGroup())
				{
					$this->foldername = bab_toHtml($oFmFolder->getName());
				}
				else
				{
					$this->foldername = '';
				}
			}
		}
	}

	$temp = new temp($idf, $idx);
	$babBody->babpopup(bab_printTemplate($temp, "filever.html", "lockfile"));
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
			global $babBody, $babDB;
			
			$fm_file = fm_getFileAccess($idf);
			$oFolderFile =& $fm_file['oFolderFile'];

			if(!is_null($oFolderFile))
			{
				if(0 === $oFolderFile->getFolderFileVersionId() || $fm_file['lockauthor'] != $GLOBALS['BAB_SESS_USERID'])
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
				$babBody->setTitle($oFolderFile->getName() . ' ' . $oFolderFile->getMajorVer() . '.' . $oFolderFile->getMinorVer());
			}
		}
	}

	$temp = new temp($idf);
	$babBody->babpopup(bab_printTemplate($temp, "filever.html", "commitfile"));
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
			global $babDB,$babBody;
			
			$fm_file = fm_getFileAccess($idf);
			$oFolderFile =& $fm_file['oFolderFile'];

			if(!is_null($oFolderFile))
			{
				$oFolderFileVersionSet = new BAB_FolderFileVersionSet();
				$oId =& $oFolderFileVersionSet->aField['iId'];
				$oFolderFileVersion = $oFolderFileVersionSet->get($oId->in($oFolderFile->getFolderFileVersionId()));
				if(!is_null($oFolderFileVersion))
				{
					$this->commenttxt = bab_translate("Comment");
					$this->confirm = bab_translate("Confirm file");
					$this->confirmtxt = bab_translate("Confirm");
					$this->no = bab_translate("No");
					$this->yes = bab_translate("Yes");
		
					$this->idf = bab_toHtml($idf);
					$this->filename = $oFolderFile->getName() . ' ' . $oFolderFileVersion->getMajorVer() . "." . $oFolderFileVersion->getMinorVer();
					$babBody->setTitle($this->filename);
					$this->comment = bab_toHtml($oFolderFileVersion->getComment());
					$this->urlget = bab_toHtml($GLOBALS['babUrlScript'] . '?tg=filever&idx=get&idf=' . 
						$idf . '&vmaj=' . $oFolderFileVersion->getMajorVer() . '&vmin=' . $oFolderFileVersion->getMinorVer());
				}
			}
		}
	}

	$temp = new temp($idf);
	$babBody->babpopup(bab_printTemplate($temp, "filever.html", "confirmfile"));
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
			global $babDB;
			
			$fm_file = fm_getFileAccess($idf);
			$oFolderFile =& $fm_file['oFolderFile'];

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
			
			if(!is_null($oFolderFile))
			{
				if(bab_isAccessValid(BAB_FMMANAGERS_GROUPS_TBL, $oFolderFile->getId()))
				{
					$this->bmanager = true;
					$this->cleantxt = bab_translate("Clean log");
					$this->datetxt2 = bab_translate("Date")." ( ".bab_translate("dd-mm-yyyy")." )";
					$this->cleanmsg = bab_translate("Clean all log entries before a given date");
				}
				else
				{
					$this->bmanager = false;
				}
	
				$oFolderFileLogSet = new BAB_FolderFileLogSet();
				$oIdFile =& $oFolderFileLogSet->aField['iIdFile'];
				$oFolderFileLogSet->select($oIdFile->in($idf));
				$iCount = $oFolderFileLogSet->count();
				
				if($iCount > BAB_FM_MAXLOGS)
				{
					if($pos > 0)
					{
						$this->topurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=filever&idx=hist&idf=".$idf."&pos=0");
						$this->topname = "&lt;&lt;";
					}
	
					$next = $pos - BAB_FM_MAXLOGS;
					if($next >= 0)
					{
						$this->prevurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=filever&idx=hist&idf=".$idf."&pos=".$next);
						$this->prevname = "&lt;";
					}
	
					$next = $pos + BAB_FM_MAXLOGS;
					if($next < $iCount)
					{
						$this->nexturl = bab_toHtml($GLOBALS['babUrlScript']."?tg=filever&idx=hist&idf=".$idf."&pos=".$next);
						$this->nextname = "&gt;";
						if($next + BAB_FM_MAXLOGS < $iCount)
						{
							$bottom = $iCount - BAB_FM_MAXLOGS;
						}
						else
						{
							$bottom = $next;
						}
						$this->bottomurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=filever&idx=hist&idf=".$idf."&pos=".$bottom);
						$this->bottomname = "&gt;&gt;";
					}
				}
				
				$aLimit = array();
				if($iCount > BAB_FM_MAXLOGS)
				{
					$aLimit = array($pos, BAB_FM_MAXLOGS);
				}
				
				$oFolderFileLogSet->bUseAlias = false;
				$oFolderFileLogSet->select($oIdFile->in($idf), array('sCreationDate' => 'DESC'), $aLimit);

				$this->res = $oFolderFileLogSet->_oResult;
				$this->count = $oFolderFileLogSet->count();
				$oFolderFileLogSet->bUseAlias = true;
				
				$GLOBALS['babBody']->setTitle($oFolderFile->getName());
			}
		}

		function getnextlog()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				global $babDB;
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

		var $oFolderFile = null;
		
		function temp($idf, $pos)
		{
			global $babDB;
			
			
			$fm_file = fm_getFileAccess($idf);
			$this->oFolderFile =& $fm_file['oFolderFile'];
			$oFmFolder =& $fm_file['oFmFolder'];

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

			if(!is_null($this->oFolderFile) && !is_null($oFmFolder))
			{
				if(bab_isAccessValid(BAB_FMMANAGERS_GROUPS_TBL, $oFmFolder->getId()))
				{
					$this->bmanager = true;
				}
				else
				{
					$this->bmanager = false;
				}
				
				$this->idf = $idf;
				$oFolderFileVersionSet = new BAB_FolderFileVersionSet();
				$oIdFile =& $oFolderFileVersionSet->aField['iIdFile'];
				$oIdFlowApprobationInstance =& $oFolderFileVersionSet->aField['iIdFlowApprobationInstance'];
				$oConfirmed =& $oFolderFileVersionSet->aField['sConfirmed'];
				
				$oCriteria = $oIdFile->in($idf);
				$oCriteria = $oCriteria->_and($oIdFlowApprobationInstance->in(0));
				$oCriteria = $oCriteria->_and($oConfirmed->in('Y'));
				
				$oFolderFileVersionSet->select($oCriteria);
				
				$iCount = $oFolderFileVersionSet->count();
	
				if($iCount > BAB_FM_MAXLOGS)
				{
					if($pos > 0)
					{
						$this->topurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=filever&idx=lvers&idf=".$idf."&pos=0");
						$this->topname = "&lt;&lt;";
					}
	
					$next = $pos - BAB_FM_MAXLOGS;
					if($next >= 0)
					{
						$this->prevurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=filever&idx=lvers&idf=".$idf."&pos=".$next);
						$this->prevname = "&lt;";
					}
	
					$next = $pos + BAB_FM_MAXLOGS;
					if($next < $iCount)
					{
						$this->nexturl = bab_toHtml($GLOBALS['babUrlScript']."?tg=filever&idx=lvers&idf=".$idf."&pos=".$next);
						$this->nextname = "&gt;";
						if($next + BAB_FM_MAXLOGS < $iCount)
						{
							$bottom = $iCount - BAB_FM_MAXLOGS;
						}
						else
						{
							$bottom = $next;
						}
						$this->bottomurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=filever&idx=lvers&idf=".$idf."&pos=".$bottom);
						$this->bottomname = "&gt;&gt;";
					}
				}
				
				$aLimit = array();
				if($iCount > BAB_FM_MAXLOGS)
				{
					$aLimit = array($pos, BAB_FM_MAXLOGS);
				}
				
				$oFolderFileVersionSet->bUseAlias = false;
				$oFolderFileVersionSet->select($oCriteria, array('sCreationDate' => 'DESC'), $aLimit);
	
				$GLOBALS['babBody']->setTitle($this->oFolderFile->getName());
				$this->res = $oFolderFileVersionSet->_oResult;
				$this->count = $oFolderFileVersionSet->count();
	
				if($engine = bab_searchEngineInfos()) 
				{
					$this->index = true;
				} 
				else
				{
					$this->index = false;
				}
			}
		}

		function getlastvers()
		{
			static $i = 0;
			
			if(0 === $i)
			{
				++$i;
				
				global $babDB;
				
				$sDate		= '';
				$iIdUser	= 0;
				if(0 != $this->oFolderFile->getModifierId())
				{
					$sDate		= $this->oFolderFile->getModifiedDate();
					$iIdUser	= $this->oFolderFile->getModifierId();
				}
				else
				{
					$sDate		= $this->oFolderFile->getCreationDate();
					$iIdUser	= $this->oFolderFile->getAuthorId();
				}
				
				$time = bab_mktime($sDate);
				$this->date = bab_toHtml(bab_strftime($time, false));
				$this->hour = bab_toHtml(bab_time($time));
				$this->author = bab_toHtml(bab_getUserName($iIdUser));
				$this->comment = bab_toHtml($this->oFolderFile->getCommentVer());
				$this->version = bab_toHtml($this->oFolderFile->getMajorVer().".".$this->oFolderFile->getMinorVer());
				
				$sUrlGet = $GLOBALS['babUrlScript'] . '?tg=fileman&id=' . urlencode($this->oFolderFile->getOwnerId()) . '&gr=' . 
					urlencode($this->oFolderFile->getGroup()) . '&path=' . urlencode($this->oFolderFile->getPathName()) .
					'&idf=' . urlencode($this->oFolderFile->getId()) . '&file=' . urlencode($this->oFolderFile->getName()) .
					'&sAction=getFile';
				
				$this->geturl = bab_toHtml($sUrlGet);
				$this->index_status = bab_toHtml(bab_getIndexStatusLabel($this->oFolderFile->getStatusIndex()));
				$i++;
				return true;
			}
			else
			{
				return false;
			}
		}
		
		function getnextvers()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				global $babDB;
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
	global $babBody, $babDB;
	
	$fm_file = fm_getFileAccess($idf);
	$oFolderFile =& $fm_file['oFolderFile'];
	
	if(!is_null($oFolderFile))
	{
		$inl = bab_rp('inl', false);
		if(false === $inl) 
		{
			$inl = bab_getFileContentDisposition() == 1? 1: ''; 
		}
	
		$oFileManagerEnv =& getEnvObject();
		$mime = bab_getFileMimeType($oFolderFile->getName());

		$fullpath = BAB_FileManagerEnv::getCollectivePath($oFolderFile->getDelegationOwnerId()) . $oFolderFile->getPathName();

		$fullpath .= BAB_FVERSION_FOLDER."/".$vmajor.",".$vminor.",".$oFolderFile->getName();
		$fsize = filesize($fullpath);
		
		bab_setTimeLimit(3600);
		
		if(strtolower(bab_browserAgent()) == "msie")
		{
			header('Cache-Control: public');
		}
		
		if($inl == "1")
		{
			header("Content-Disposition: inline; filename=\"".$oFolderFile->getName()."\""."\n");
		}
		else
		{
			header("Content-Disposition: attachment; filename=\"".$oFolderFile->getName()."\""."\n");
		}
		
		
		
		header("Content-Type: $mime"."\n");
		header("Content-Length: ". $fsize."\n");
		header("Content-transfert-encoding: binary"."\n");
	
		$fp=fopen($fullpath,"rb");
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
			$fm_file = fm_getFileAccess($idf);
			$oFmFolder =& $fm_file['oFmFolder'];
			$oFolderFile =& $fm_file['oFolderFile'];
			
			if(!is_null($oFolderFile) && !is_null($oFmFolder))
			{
				$sPathName = $oFolderFile->getPathName();	
				$iLength = (int) strlen(trim($sPathName));
				if(0 !== $iLength)
				{
					$sPathName = substr($sPathName, 0, -1);
				}
				
				$iIdUrl = $oFmFolder->getId();
				if(strlen($oFmFolder->getRelativePath()) > 0)
				{
					$oRootFmFolder = BAB_FmFolderSet::getRootCollectiveFolder($oFmFolder->getRelativePath());
					if(!is_null($oRootFmFolder))
					{
						$iIdUrl = $oRootFmFolder->getId();
					}
				}
							
				$url = $GLOBALS['babUrlScript']."?tg=fileman&idx=list&id=".$iIdUrl."&gr=".$oFolderFile->getGroup()."&path=".urlencode($sPathName);
				
//				echo 'sUrl ==> ' . $url;
			}
			else 
			{
				$url = $GLOBALS['babUrlScript'].'?tg=fileman';
			}
			
			$this->message = bab_translate("Your file list has been updated");
			$this->close = bab_translate("Close");
			$this->redirecturl = bab_toHtml($url);
		}
	}

	$temp = new temp($idf);
	echo bab_printTemplate($temp,"filever.html", "fileunload");
}



function confirmFile($idf, $bconfirm)
{
	global $babBody, $babDB;

	$fm_file = fm_getFileAccess($idf);
	$oFmFolder =& $fm_file['oFmFolder'];
	$oFolderFile =& $fm_file['oFolderFile'];
	$lockauthor = $fm_file['lockauthor'];
			
	if(!is_null($oFolderFile) && !is_null($oFmFolder))
	{
		include_once $GLOBALS['babInstallPath']."utilit/afincl.php";

		$oFolderFileVersionSet = new BAB_FolderFileVersionSet();
		$oId =& $oFolderFileVersionSet->aField['iId'];
		$oFolderFileVersion = $oFolderFileVersionSet->get($oId->in($oFolderFile->getFolderFileVersionId()));
		
		if(!is_null($oFolderFileVersion))
		{
			$arrschi = bab_getWaitingIdSAInstance($GLOBALS['BAB_SESS_USERID']);
			if(count($arrschi) > 0  && in_array($oFolderFileVersion->getFlowApprobationInstanceId(), $arrschi))
			{
				$res = updateFlowInstance($oFolderFileVersion->getFlowApprobationInstanceId(), $GLOBALS['BAB_SESS_USERID'], $bconfirm == "Y"? true: false);
				switch($res)
				{
					case 0:
						$oFileManagerEnv =& getEnvObject();
						$sUploadPath = $oFileManagerEnv->getCollectiveFolderPath();
						$sFullPathName = $sUploadPath .$oFolderFile->getPathName() . BAB_FVERSION_FOLDER . '/' . 
							$oFolderFileVersion->getMajorVer() . ',' . $oFolderFileVersion->getMinorVer() . ',' . $oFolderFile->getName();	
				
						$oFolderFile->setFolderFileVersionId(0);
						$oFolderFile->save();
						$oId =& $oFolderFileVersionSet->aField['iId'];
						$oFolderFileVersionSet->remove($oId->in($oFolderFileVersion->getId()), $sUploadPath .$oFolderFile->getPathName(), $oFolderFile->getName());
						
						$oFolderFileLog = new BAB_FolderFileLog();
						$oFolderFileLog->setIdFile($idf);
						$oFolderFileLog->setCreationDate(date("Y-m-d H:i:s"));
						$oFolderFileLog->setAuthorId($GLOBALS['BAB_SESS_USERID']);
						$oFolderFileLog->setAction(BAB_FACTION_COMMIT);
						$oFolderFileLog->setComment(bab_translate("Refused by ").$GLOBALS['BAB_SESS_USER']);
						$oFolderFileLog->setVersion($oFolderFileVersion->getMajorVer() . '.' . $oFolderFileVersion->getMinorVer());
						$oFolderFileLog->save();
						
						deleteFlowInstance($oFolderFileVersion->getFlowApprobationInstanceId());
						
						notifyFileAuthor(bab_translate("Your new file version has been refused"), 
							$oFolderFileVersion->getMajorVer() . '.' . $oFolderFileVersion->getMinorVer(),
							$oFolderFileVersion->getAuthorId(), $oFolderFile->getName());
						// notify user
						break;
					case 1:
						deleteFlowInstance($oFolderFileVersion->getFlowApprobationInstanceId());
						acceptFileVersion($oFolderFile, $oFolderFileVersion, $oFmFolder->getFileNotify());
						break;
					default:
						$nfusers = getWaitingApproversFlowInstance($oFolderFileVersion->getFlowApprobationInstanceId(), true);
						if(count($nfusers) > 0 )
						{
							notifyFileApprovers($oFolderFileVersion->getIdFile(), $nfusers, bab_translate("A new version file is waiting for you"));
						}
						break;
				}
			}
		}
	}
}

function deleteFileVersions($idf, $versions)
{
	global $babBody, $babDB;
	
	$fm_file = fm_getFileAccess($idf);
	$oFolderFile =& $fm_file['oFolderFile'];
			
	if(!is_null($oFolderFile))
	{
		$count = count($versions);
		if($count > 0)
		{
			$oFolderFileVersionSet = new BAB_FolderFileVersionSet();
			$oIdFile =& $oFolderFileVersionSet->aField['iIdFile'];
			$oVerMajor =& $oFolderFileVersionSet->aField['iVerMajor'];
			$oVerMinor =& $oFolderFileVersionSet->aField['iVerMinor'];
			
			$oFileManagerEnv =& getEnvObject();
			$sUploadPath = $oFileManagerEnv->getCollectiveFolderPath();
			
			for($i = 0; $i < $count; $i++ )
			{
				$aVersion	= explode(".", $versions[$i]);
				$iVerMajor	= (int) $aVersion[0];
				$iVerMinor	= (int) $aVersion[1];
				$oCriteria	= $oVerMajor->in($iVerMajor);
				$oCriteria	= $oVerMinor->in($iVerMinor);
				$oCriteria	= $oCriteria->_and($oIdFile->in($idf));
				$oFolderFileVersionSet->remove($oCriteria, $sUploadPath . $oFolderFile->getPathName(), $oFolderFile->getName());
				
				$oFolderFileLog = new BAB_FolderFileLog();
				$oFolderFileLog->setIdFile($idf);
				$oFolderFileLog->setCreationDate(date("Y-m-d H:i:s"));
				$oFolderFileLog->setAuthorId($GLOBALS['BAB_SESS_USERID']);
				$oFolderFileLog->setAction(BAB_FACTION_OTHER);
				$oFolderFileLog->setComment(bab_translate("File deleted"));
				$oFolderFileLog->setVersion($iVerMajor . '.' . $iVerMinor);
				$oFolderFileLog->save();
			}
		}
	}
}

function cleanFileLog($idf, $date)
{
	global $babBody, $babDB;

	$ar = explode("-", $date);
	if(count($ar) != 3 || !is_numeric($ar[0]) || !is_numeric($ar[1]) || !is_numeric($ar[2]))
	{
		return;
	}

	$dateb = sprintf("%04d-%02d-%02d 00:00:00", $ar[2], $ar[1], $ar[0]);
	$babDB->db_query("delete from ".BAB_FM_FILESLOG_TBL." where id_file='".$babDB->db_escape_string($idf)."' and date <='".$babDB->db_escape_string($dateb)."'");
}




/* main */
$bupdate = false;
$bdownload = false;
$idx = bab_rp('idx','denied');

$oFmFolder = null;
$oFolderFile = null;
$lockauthor = 0;



if(isset($_REQUEST['idf']))
{
	$idf = (int) $_REQUEST['idf'];
	$fm_file = fm_getFileAccess($idf);
	$oFmFolder =& $fm_file['oFmFolder'];
	$oFolderFile =& $fm_file['oFolderFile'];

//bab_debug($fm_file);
//bab_debug($oFmFolder);
//bab_debug($oFolderFile);
	
	if(!is_null($oFolderFile) && !is_null($oFmFolder))
	{
		if(isset($_POST['afile']) && $fm_file['bupdate'] == true)
		{
			switch($_POST['afile'])
			{
				case 'lock':
					fm_lockFile($idf, $_POST['comment']); 
					break;
					
				case 'unlock':
					fm_unlockFile($idf, $_POST['comment']);
					break;
					
				case 'commit':
					if(false === fm_commitFile($idf, $_POST['comment'], $_POST['vermajor'], bab_fmFile::upload('uploadf'))) 
					{
							$idx = 'commit';
					}
					break;
					 
				case 'delv':
					if(bab_isAccessValid(BAB_FMMANAGERS_GROUPS_TBL, $oFmFolder->getId())) 
					{
						deleteFileVersions($idf, bab_pp('versions', array())); 
					}
					break;
					
				case 'cleanlog':
					if(bab_isAccessValid(BAB_FMMANAGERS_GROUPS_TBL, $oFmFolder->getId())) 
					{
						cleanFileLog($idf, bab_pp('date'));
					}
			}
	
			if($_POST['afile'] == 'confirm')
			{
				confirmFile($idf, $bconfirm); 
			}
		}
	}
}
else 
{
	$idx = 'denied';
}

switch($idx)
	{
	case "commit":
		showCommitFile(bab_rp('idf'));
		exit;
		break;

	case "hist":
		if( $fm_file['bupdate'] )
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
		if( $fm_file['bupdate'] || $fm_file['bdownload'] )
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
		fileUnload(bab_rp('idf'));
		exit;
		break;

	case 'lock':
		if( $fm_file['bupdate'])
			{
			showLockUnlockFile(bab_rp('idf'), $idx);
			exit;
			}
		else
			$babBody->msgerror = bab_translate("Access denied");
		break;
	
	case 'unlock':
		if( $fm_file['bupdate'])
			{
			showLockUnlockFile(bab_rp('idf'), $idx);
			exit;
			}
		else
			$babBody->msgerror = bab_translate("Access denied");
		break;

	case 'conf':
		include_once $GLOBALS['babInstallPath']."utilit/afincl.php";
		if( isUserApproverFlow($oFmFolder->getApprobationSchemeId(), $BAB_SESS_USERID) )
		{
			showConfirmFile(bab_rp('idf'));
			exit;
		}
		else
			$babBody->msgerror = bab_translate("Access denied");
		break;

	case "get":
		if( $fm_file['bdownload'] )
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