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
/**
* @internal SEC1 NA 26/01/2007 FULL
*/
include_once 'base.php';

include_once $babInstallPath.'utilit/tempfile.php';
include_once $babInstallPath.'utilit/imgincl.php';

function put_text($txt,$limit=12,$limitmot=15)
{
	if (mb_strlen($txt) > $limit)
		$out = mb_substr(strip_tags($txt),0,$limit).'...';
	else
		$out = strip_tags($txt);
	$arr = explode(' ',$out);
	foreach($arr as $key => $mot)
		{
		$arr[$key] = mb_substr($mot,0,$limitmot);
		}
return implode(' ',$arr);
}


function listImages($path='')
	{
	class temp
		{
		var $sContent;
		
		var $linked_images;
		
		function temp($path)
			{
			global $babBody, $babDB;
			$this->sContent			= 'text/html; charset=' . bab_charset::getIso();
			$this->maximagessize	= $babBody->babsite['imgsize'];
			
			if( $this->maximagessize != 0 )
				{
				$this->maxsizetxt = bab_translate("Image size must not exceed").' '.$this->maximagessize. ' '. bab_translate("Kb");
				}
			else
				{
				$this->maxsizetxt = '';
				}
			$this->maximagessize *= 1000 ;
			
			$this->linked_images = (int) bab_rp('linked_images', 0);
			
			/*
			 * on peut uploader des images liees en tant que utilsateur enregistree
			 * on peut uploader des images de la librairie en tant qu'administrateur
			 */
			$this->upload = ($GLOBALS['BAB_SESS_LOGGED'] && $this->linked_images) || bab_isUserAdministrator();
			$this->badmin = bab_isUserAdministrator();
			
			$this->file = bab_translate("File");
			$this->add = bab_translate("Add");
			$this->yes = bab_translate("Yes");
			$this->no = bab_translate("No");
			$this->shared = bab_translate("Shared");
			$this->refresh = bab_translate("Refresh");
			$this->create_folder = bab_translate("Create the folder");
			$this->delete = bab_translate("Delete");
			$this->invalidimg = bab_translate("Invalid image extension");
			$this->aligntxt = bab_translate("Alignment");
			$this->alt = bab_translate("Alt");
			$this->hspacing = bab_translate("Horizontal spacing");
			$this->vspacing = bab_translate("Vertical spacing");
			$this->border = bab_translate("Border");
			$this->invalidentry = bab_translate("You must specify a number");
			
			$this->none = bab_translate("None");
			$this->left = bab_translate("Left");
			$this->right = bab_translate("Right");
			$this->middle = bab_translate("Middle");
			$this->absmiddle = bab_translate("Absolute middle");
			$this->top = bab_translate("top");
			$this->bottom = bab_translate("Bottom");
			$this->center = bab_translate("Center");
			$this->path = $path;
			
			$this->comnum = 0;

			if( !is_dir(BAB_IUD_TMP))
				bab_mkdir(BAB_IUD_TMP, $GLOBALS['babMkdirMode']);
			if( !is_dir(BAB_IUD_COMMON))
				bab_mkdir(BAB_IUD_COMMON, $GLOBALS['babMkdirMode']);
			if( !is_dir(BAB_IUD_ARTICLES))
				bab_mkdir(BAB_IUD_ARTICLES, $GLOBALS['babMkdirMode']);
			$tf = new babTempFiles(BAB_IUD_TMP, BAB_FILE_TIMEOUT);
			$h = opendir(BAB_IUD_COMMON);
			$this->arrcfile = array();
			while (($f = readdir($h)) != false)
				{
				if ($f != "." and $f != "..") 
					{
					if (is_file(BAB_IUD_COMMON.$f) && @getimagesize(BAB_IUD_COMMON.$f))
						{
						$this->arrcfile[] = BAB_IUD_COMMON.$f;
						}
					}
				}
			closedir($h);
			$res = $babDB->db_query("select * from ".BAB_IMAGES_TEMP_TBL." where id_owner='".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."'");
			if( $res && $babDB->db_num_rows($res) > 0 )
				{
				while( $arr = $babDB->db_fetch_array($res))
					{
					if( is_file(BAB_IUD_TMP.$arr['name']))
						$this->arrufile[] = BAB_IUD_TMP.$arr['name'];
					else
						$babDB->db_query("delete from ".BAB_IMAGES_TEMP_TBL." where id='".$babDB->db_escape_string($arr['id'])."'");
					}
				}

			$this->uifiles = 0;
			$this->cifiles = 0;
			$this->refurl = $GLOBALS['babUrlScript']."?tg=images&linked_images=".$this->linked_images;
			$this->list_img_url = $GLOBALS['babUrlScript']."?tg=images&idx=iframe&path=".$this->path;
			$this->list_img_url_prev = $GLOBALS['babUrlScript']."?tg=images&idx=iframe&path=";

			}

		}
		
	global $babBody;
		
	$temp = new temp($path);
	$babBody->babPopup(bab_printTemplate($temp,"images.html", "imageslisteditor"));
	}

function iframe($path="")
	{
	class temp
		{
		var $sContent;
		
		function temp($path)
			{
			global $babBody, $babDB;

			$this->maximagessize	= $babBody->babsite['imgsize'];
			$this->del				= bab_translate("Delete");

			
			if( mb_substr($path, -1) == "/" ) {
				$path = mb_substr($path, 0, -1);
			}

			$this->prevpath = '';

			if ($path != '') {
				$this->prevpath = mb_substr( $path,0, mb_strrpos($path,"/") );
				if( mb_substr($path, -1) != "/" ) {
					$path .="/";
				}
			}

			$this->path = $path;
			
			$this->badmin = bab_isUserAdministrator();
			$this->comnum = 0;
			
			$this->msg_delfile = bab_translate("WARNING!: If you delete this file, the articles containing the picture will be corrupted. Do really whant to delete this file")."?";
			$this->msg_deltree = bab_translate("WARNING!: If you delete this folder, the articles containing the a picture from this folder will be corrupted. Do really whant to delete this directory")."?";
			$this->msg_renamefile = bab_translate("WARNING!: If you rename this file, the articles containing the picture will be corrupted. Do really whant to rename this file")."?";
			$this->msg_renametree = bab_translate("WARNING!: If you rename this folder, the articles containing the a picture from this folder will be corrupted. Do really whant to rename this directory")."?";

			if( !is_dir(BAB_IUD_TMP))
				bab_mkdir(BAB_IUD_TMP, $GLOBALS['babMkdirMode']);
			if( !is_dir(BAB_IUD_COMMON))
				bab_mkdir(BAB_IUD_COMMON, $GLOBALS['babMkdirMode']);
			if( !is_dir(BAB_IUD_ARTICLES))
				bab_mkdir(BAB_IUD_ARTICLES, $GLOBALS['babMkdirMode']);
			$tf = new babTempFiles(BAB_IUD_TMP, BAB_FILE_TIMEOUT);
			$h = opendir(BAB_IUD_COMMON.$path);
			$this->arrcfile = array();
			while (($f = readdir($h)) != false)
				{
				if ($f != "." and $f != "..") 
					{
					if (is_file(BAB_IUD_COMMON.$path.$f) && @getimagesize(BAB_IUD_COMMON.$path.$f))
						{
						$this->arrcfile[] = BAB_IUD_COMMON.$path.$f;
						}
					}
				}
			closedir($h);
			/* Alphabetical sorting of the names of files */
			bab_sort::natcasesort($this->arrcfile);
			$this->arrcfile = array_values($this->arrcfile);
			$res = $babDB->db_query("select * from ".BAB_IMAGES_TEMP_TBL." where id_owner='".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."'");
			if( $res && $babDB->db_num_rows($res) > 0 )
				{
				while( $arr = $babDB->db_fetch_array($res))
					{
					if( is_file(BAB_IUD_TMP.$arr['name']))
						$this->arrufile[] = BAB_IUD_TMP.$arr['name'];
					else
						$babDB->db_query("delete from ".BAB_IMAGES_TEMP_TBL." where id='".$babDB->db_escape_string($arr['id'])."'");
					}
				}

			$this->uifiles = 0;
			$this->cifiles = 0;
			$this->gdi = extension_loaded('gd');
			$this->countsubdir = 0;
			$reper = opendir(BAB_IUD_COMMON.$path);
			$this->subdir = array();
			while($dir = readdir($reper) ) {
				if (($dir != ".") && ($dir != "..") && is_dir(BAB_IUD_COMMON.$path.$dir) ) {
					$this->subdir[] = $dir ;
					$this->countsubdir++ ; 
				}
			}
			/* Alphabetical sorting of the names of subfolders */
			bab_sort::natcasesort($this->subdir);
			$this->subdir = array_values($this->subdir);
		}

		function geturls($filename)
			{
			$this->name = basename($filename);
			$imgsize = getimagesize($filename);
			$this->imgalt = $imgsize[0]." X ".$imgsize[1];
			$this->imgurl = $GLOBALS['babUrl'].dirname($filename).'/'.rawurlencode(basename($filename));
			if( $imgsize[0] > 50 || $imgsize[1] > 50)
				{
				if( !$this->gdi || ($imgsize[2] == 1 && !(imagetypes() & IMG_GIF)) || ($imgsize[2] == 2 && !(imagetypes() & IMG_JPG)) || ($imgsize[2] == 3 && !(imagetypes() & IMG_PNG)) )
					$this->gd = false;
				else
					$this->gd = $this->gdi;

				if( $this->gd && ($imgsize[2] == 1 || $imgsize[2] == 2 || $imgsize[2] == 3))
					{

					$this->srcurl = $GLOBALS['babUrlScript']."?tg=images&idx=get&f=".$filename."&h=50";
					}
				else
					{
					$this->srcurl = $GLOBALS['babUrl'].$filename;
					$ratio = $imgsize[0] / $imgsize[1];
					if( $ratio >= 1 )
						{
						$this->imgwidth = "50";
						$this->imgheight = ceil((50 * $imgsize[1])/$imgsize[0]);
						}
					else if( $ratio < 1 )
						{
						$this->imgheight = "50";
						$this->imgwidth = ceil((50 * $imgsize[0])/$imgsize[1]);
						}
					else
						{
						$this->imgwidth = "50";
						$this->imgheight = "50";
						}
					}
				}
			else
				{
				$this->srcurl = $filename;
				$this->imgwidth = $imgsize[0];
				$this->imgheight = $imgsize[1];
				}
			}


		function getnextcfile()
			{
			static $i = 0;
			if( $this->cifiles < count($this->arrcfile))
				{
				$this->geturls($this->arrcfile[$this->cifiles]);
				$this->imgname = basename($this->arrcfile[$this->cifiles]);
				$this->imgname_txt = put_text($this->imgname);
				$this->delurl = $GLOBALS['babUrlScript']."?tg=images&idx=del&com=1&f=".$this->imgname."&path=".$this->path;
				$this->rename_popup_url = $GLOBALS['babUrlScript']."?tg=images&idx=rename_popup&path=".$this->path."&old_name=".$this->imgname;
				$this->cifiles++;
				$i++;
				return true;
				}
			else
				{
				$i = 0;
				return false;
				}
			}

		function getnextufile()
			{
			static $i = 0;
			if (!isset($this->arrufile)) $this->arrufile = array();
			if( $this->uifiles < count($this->arrufile))
				{
				$this->geturls($this->arrufile[$this->uifiles]);
				$this->imgname = basename($this->arrufile[$this->uifiles]);
				$this->imgname_txt = put_text($this->imgname);
				$this->delurl = $GLOBALS['babUrlScript']."?tg=images&idx=del&com=0&f=".$this->imgname."&path=".$this->path;
				$this->uifiles++;
				$i++;
				return true;
				}
			else
				{
				$i = 0;
				return false;
				}
			}
		
		function getnextsubdir()
			{
			static $i = 0;
			if( $i < $this->countsubdir)
				{
				$this->subdirname = $this->subdir[$i];
				$this->subdirname_txt = put_text($this->subdirname);
				$this->delurl = $GLOBALS['babUrlScript']."?tg=images&idx=deltree&path=".$this->path.$this->subdir[$i];
				$this->subdirurl = $GLOBALS['babUrlScript']."?tg=images&idx=iframe&path=".$this->path.$this->subdir[$i];
				$this->rename_popup_url = $GLOBALS['babUrlScript']."?tg=images&idx=rename_popup&path=".$this->path."&old_name=".$this->subdirname;
				$i++;
				return true;
				}
			else
				{
				$i = 0;
				return false;
				}
			}
		}
		
	global $babBody;
		
	$temp = new temp($path);
	$babBody->babPopup(bab_printTemplate($temp,"images.html", "imageslist"));
	}


function rename_popup($old_name,$path)
	{
	class temp
		{
		var $sContent;
		function temp($old_name,$path)
			{
			$this->path		= $path;
			$this->old_name = $old_name;
			$this->rename	= bab_translate('Rename');
			}
		}
	$temp = new temp($old_name,$path);
	echo bab_printTemplate($temp,"images.html", "rename_popup");
	}




function saveImage($file, $size, $tmpfile)
	{
	global $babDB, $babBody;
	
	$share = bab_pp('share', 'Y'); // si share n'est pas defini c'est que seul les images partagees sont autorisees
	$path = bab_pp('path');
	
	
	$nf = '';
	$bOk = true;

	if( false !== mb_strpos($path, '..') || false !== mb_strpos($file, '..'))
		{
		$bOk = false;
		}

	$filearr = explode('.',$file);
	$ext = mb_strtolower(end($filearr));

	switch($ext)
		{
		case 'jpg':
		case 'jpeg':
		case 'png':
		case 'gif':
			break;
		default:
			$bOk = false;
			break;
		}
	


	if( !$bOk )
		{
		$babBody->addError(bab_translate("Cannot upload file"));
		return $nf;
		}

	
	if ($path != "") 
		{
		$path.="/";
		}

	if( is_uploaded_file($tmpfile) )
		{
		$tf = new babTempFiles(BAB_IUD_TMP, BAB_FILE_TIMEOUT);
		if($share == 'Y' && bab_isUserAdministrator())
			{
			if( is_file(BAB_IUD_COMMON.$path.$file))
				{
				$babBody->addError(bab_translate("A file with the same name already exists"));
				return $nf;
				}
			if( move_uploaded_file($tmpfile, BAB_IUD_COMMON.$path.trim(accentRemover($file))))
				$nf = BAB_IUD_COMMON.$path.$file;
			}
		else if(1 === (int) bab_pp('linked_images') && !empty($GLOBALS['BAB_SESS_USERID']))
			{
			$nf = $tf->tempfile($tmpfile, $file);
			if( !empty($nf))
				{
				$babDB->db_query("insert into ".BAB_IMAGES_TEMP_TBL." (name, id_owner) values ('".$babDB->db_escape_string(basename($nf))."', '".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."')");
				}
			}
		}
	
	if( empty($nf))
		{
		$babBody->addError(bab_translate("Cannot upload file"));
		}
	return $nf;
	}

function delImage($com, $f)
	{
	global $babDB;

	if( false !== mb_strpos($f, '..'))
		{
		return;
		}

	switch($com)
		{
		case 1:
			if (bab_isUserAdministrator() && is_file(BAB_IUD_COMMON.$f))
				@unlink(BAB_IUD_COMMON.$f);
			break;
		case 0:
			$res = $babDB->db_query("select * from ".BAB_IMAGES_TEMP_TBL." where id_owner='".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."' and name='".$babDB->db_escape_string($f)."'");
			if( $res && $babDB->db_num_rows($res) == 1 )
				{
				$babDB->db_query("delete from ".BAB_IMAGES_TEMP_TBL." where id_owner='".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."' and name='".$babDB->db_escape_string($f)."'");
				@unlink(BAB_IUD_TMP.$f);
				}
			break;
		}
	}

function deldir($dir){

	require_once dirname(__FILE__).'/utilit/urlincl.php';
	$path = new bab_Path($dir);
	
	$path->deleteDir();
}


function rename_item($path, $old_name, $new_name)
{
	global $babBody;
	
	if( false !== mb_strpos($old_name, '..'))
		{
		$old_name = '';
		}
	if( false !== mb_strpos($new_name, '..'))
		{
		$new_name = '';
		}
	if ( $old_name != '' && $new_name != '' && $old_name!=$new_name && bab_isUserAdministrator() )
		{
		if ( mb_substr($path, -1) != "/" ) $p = $path."/";
		else $p = $path;
		if (is_dir(BAB_IUD_COMMON.$p.$old_name))
			{
			if (!is_dir(BAB_IUD_COMMON.$p.$new_name))
				rename(BAB_IUD_COMMON.$p.$old_name,BAB_IUD_COMMON.$p.$new_name);
			else
				$babBody->addError(bab_translate("A folder with the same name already exists"));
			}
		elseif (is_file(BAB_IUD_COMMON.$p.$old_name))
			{
			if (!is_file(BAB_IUD_COMMON.$p.$new_name))
				rename(BAB_IUD_COMMON.$p.$old_name,BAB_IUD_COMMON.$p.$new_name);
			else
				$babBody->addError(bab_translate("A file with the same name already exists"));
			}
		}
}




/* main */
$idx = bab_rp('idx', 'list');
$path = bab_rp('path', '');



if( false !== mb_strpos($path, '..'))
	{
	$path = '';
	}


if( '' != ($addf = bab_pp('addf')))
{
if( $addf == 'add')
	{
	saveImage($_FILES['uploadf']['name'], $_FILES['uploadf']['size'],$_FILES['uploadf']['tmp_name']);
	}
}

if ( '' != ($directory = bab_pp('directory')) && bab_isUserAdministrator() )
	{
	if ( mb_substr($path, -1) != "/" ) $p = $path."/";
	else $p = $path;
	if (!is_dir(BAB_IUD_COMMON.$p.$directory))
		bab_mkdir(BAB_IUD_COMMON.$p.$directory,$GLOBALS['babMkdirMode']);
	else
		$babBody->addError(bab_translate("A folder with the same name already exists"));
	}

$old_name = bab_rp('old_name', '');
$new_name = bab_rp('new_name', '');

if ($old_name != '' && $new_name != '')
{
	rename_item($path, $old_name, $new_name);
}



switch($idx)
	{
	case 'get':
		$w = bab_gp('w');
		$h = bab_gp('h', 50);
		require_once dirname(__FILE__).'/utilit/gdiincl.php';
		bab_getResizedImage(bab_gp('f'), $w, $h);
		break;
	case 'rename_popup':
		rename_popup(bab_gp('old_name'),bab_gp('path'));
		break;
	case 'deltree':
		if ($path != '' && bab_isUserAdministrator() ) 
		{
			deldir(BAB_IUD_COMMON.$path);
		}
		$path = mb_substr( $path,0, mb_strpos($path,"/") );
	case 'del':
		$com = bab_gp('com', 0);
		if ($com != 0 ) 
			{
			$p = $path;
			}
		else 
			{
			$p = '';
			}
		$f = bab_gp('f');
		if (!empty($f)) 
			{
			delImage($com, $p.$f);
			}
		/* no break */
	case 'iframe';
		iframe($path);
		break;
	case 'list':
	default:
		listImages($path);
		break;
	}
?>