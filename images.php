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
require_once dirname(__FILE__).'/utilit/registerglobals.php';
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

function getResizedImage($img, $w, $h, $com)
	{
	$type = '';
	$imgf = $img;
	if( file_exists($imgf))
		{
		$imgsize = @getimagesize($imgf);
		if( $imgsize )
			{
			switch($imgsize[2])
				{
				case '2':
					$type = 'jpeg';
					$tmp = imagecreatefromjpeg($imgf);
					break;
				case '1':
					$type = 'gif';
					$tmp = imagecreatefromgif($imgf);
					break;
				case '3':
					$type = 'png';
					$tmp = imagecreatefrompng($imgf);
					break;
				default:
					break;
				}
			}
		}

	if( isset($tmp) )
		{
		$wtmp = imagesx($tmp);
		$htmp = imagesy($tmp);
		if( $w == '' )
			$w = $wtmp;
		if( $h == '' )
			$h = $htmp;
		if( $wtmp > $w )
			{
			$wimg = $w;
			$himg = (real)( ((real)(($wimg/$wtmp)*100) * $wtmp)/100);          
			}  
		else if ($htmp > $h)  
			{  
			$himg = $h;  
			$wimg = (real)( ((real)(($himg/$htmp)*100) * $wtmp)/100);  
			}  
		else  
			{  
			$himg = $h;  
			$wimg = $w;  
			}
		$out = imagecreate($wimg, $himg);
		imagecopyresized($out, $tmp, 0, 0, 0, 0, $wimg, $himg, $imgsize[0], $imgsize[1]);
		imagedestroy($tmp);
			
		switch($imgsize[2])
			{
			case '2':
				header('Content-type: image/jpeg');
				imagejpeg($out);
				break;
			case '1':
				header('Content-type: image/gif');
				imagegif($out);
				break;
			case '3':
				header('Content-type: image/png');
				imagepng($out);
				break;
			}
		}
	}

function listImages($editor,$path='')
	{
	class temp
		{
		var $sContent;
		
		function temp($editor,$path)
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
			$this->msgerror = bab_toHtml($GLOBALS['msgerror']);
			$this->maximagessize *= 1000 ;
			$this->file = bab_translate("File");
			$this->add = bab_translate("Add");
			$this->yes = bab_translate("Yes");
			$this->no = bab_translate("No");
			$this->shared = bab_translate("Shared");
			$this->refresh = bab_translate("Refresh");
			$this->create = bab_translate("Create");
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
			$this->badmin = bab_isUserAdministrator();
			$this->comnum = 0;
			$this->editor = $editor;
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
			$this->refurl = $GLOBALS['babUrlScript']."?tg=images&editor=".$this->editor;
			$this->list_img_url = $GLOBALS['babUrlScript']."?tg=images&idx=iframe&editor=".$this->editor."&path=".$this->path;
			$this->list_img_url_prev = $GLOBALS['babUrlScript']."?tg=images&idx=iframe&editor=".$this->editor."&path=";

			}

		}
	$temp = new temp($editor,$path);
	echo bab_printTemplate($temp,"images.html", "imageslisteditor");
	}

function iframe($editor,$path="")
	{
	class temp
		{
		var $sContent;
		
		function temp($editor,$path)
			{
			global $babBody, $babDB;

			$this->maximagessize	= $babBody->babsite['imgsize'];
			$this->msgerror			= bab_toHtml($GLOBALS['msgerror']);
			$this->sContent			= 'text/html; charset=' . bab_charset::getIso();
			$this->del				= bab_translate("Delete");
			$this->editor			= $editor;
			
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

		function geturls($filename, $com)
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

					$this->srcurl = $GLOBALS['babUrlScript']."?tg=images&idx=get&f=".$filename."&w=50&h=50&com=".$com;
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
				$this->geturls($this->arrcfile[$this->cifiles], 1);
				$this->imgname = basename($this->arrcfile[$this->cifiles]);
				$this->imgname_txt = put_text($this->imgname);
				$this->delurl = $GLOBALS['babUrlScript']."?tg=images&idx=del&com=1&f=".$this->imgname."&editor=".$this->editor."&path=".$this->path;
				$this->rename_popup_url = $GLOBALS['babUrlScript']."?tg=images&idx=rename_popup&editor=".$this->editor."&path=".$this->path."&old_name=".$this->imgname;
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
				$this->geturls($this->arrufile[$this->uifiles], 0);
				$this->imgname = basename($this->arrufile[$this->uifiles]);
				$this->imgname_txt = put_text($this->imgname);
				$this->delurl = $GLOBALS['babUrlScript']."?tg=images&idx=del&com=0&f=".$this->imgname."&editor=".$this->editor."&path=".$this->path;
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
				$this->delurl = $GLOBALS['babUrlScript']."?tg=images&idx=deltree&editor=".$this->editor."&path=".$this->path.$this->subdir[$i];
				$this->subdirurl = $GLOBALS['babUrlScript']."?tg=images&idx=iframe&editor=".$this->editor."&path=".$this->path.$this->subdir[$i];
				$this->rename_popup_url = $GLOBALS['babUrlScript']."?tg=images&idx=rename_popup&editor=".$this->editor."&path=".$this->path."&old_name=".$this->subdirname;
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
	$temp = new temp($editor,$path);
	echo bab_printTemplate($temp,"images.html", "imageslist");
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
			$this->sContent	= 'text/html; charset=' . bab_charset::getIso();
			}
		}
	$temp = new temp($old_name,$path);
	echo bab_printTemplate($temp,"images.html", "rename_popup");
	}



$msgerror = '';
function saveImage($file, $size, $tmpfile, $share,$path="")
	{
	global $babDB;
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
		$GLOBALS['msgerror'] = bab_translate("Cannot upload file");
		return $nf;
		}

	
	if ($path != "") 
		{
		$path.="/";
		}

	if( is_uploaded_file($tmpfile) )
		{
		$tf = new babTempFiles(BAB_IUD_TMP, BAB_FILE_TIMEOUT);
		if( !empty($share) && $share == 'Y' && bab_isUserAdministrator())
			{
			if( is_file(BAB_IUD_COMMON.$path.$file))
				{
				$GLOBALS['msgerror'] = bab_translate("A file with the same name already exists");
				return $nf;
				}
			if( move_uploaded_file($tmpfile, BAB_IUD_COMMON.$path.trim(accentRemover($file))))
				$nf = BAB_IUD_COMMON.$path.$file;
			}
		else if( !empty($GLOBALS['BAB_SESS_USERID']))
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
		$GLOBALS['msgerror'] = bab_translate("Cannot upload file");
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
  $current_dir = opendir($dir);
  while($entryname = readdir($current_dir)){
     if(is_dir("$dir/$entryname") and ($entryname != "." and $entryname!="..")){
       deldir("${dir}/${entryname}");
     }elseif($entryname != "." and $entryname!=".."){
        unlink("${dir}/${entryname}");
     }
  }
  closedir($current_dir);
  rmdir(${dir});
}


/* main */
$idx = bab_rp('idx', 'list');
$editor = bab_rp('editor', 'none');
$path = bab_rp('path', '');

if( false !== mb_strpos($path, '..'))
	{
	$path = '';
	}


if( '' != ($addf = bab_pp('addf')))
{
if( $addf == 'add')
	{
	saveImage($_FILES['uploadf']['name'], $_FILES['uploadf']['size'],$_FILES['uploadf']['tmp_name'], bab_pp('share'),bab_pp('path'));
	}
}

if ( '' != ($directory = bab_pp('directory')) && bab_isUserAdministrator() )
	{
	if ( mb_substr($path, -1) != "/" ) $p = $path."/";
	else $p = $path;
	if (!is_dir(BAB_IUD_COMMON.$p.$directory))
		bab_mkdir(BAB_IUD_COMMON.$p.$directory,$GLOBALS['babMkdirMode']);
	else
		$GLOBALS['msgerror'] = bab_translate("A folder with the same name already exists");
	}

$old_name = bab_rp('old_name', '');
$new_name = bab_rp('new_name', '');
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
			$GLOBALS['msgerror'] = bab_translate("A folder with the same name already exists");
		}
	elseif (is_file(BAB_IUD_COMMON.$p.$old_name))
		{
		if (!is_file(BAB_IUD_COMMON.$p.$new_name))
			rename(BAB_IUD_COMMON.$p.$old_name,BAB_IUD_COMMON.$p.$new_name);
		else
			$GLOBALS['msgerror'] = bab_translate("A file with the same name already exists");
		}
	}

if (!isset($GLOBALS['msgerror']))
	$GLOBALS['msgerror'] = '';


switch($idx)
	{
	case 'get':
		$w = bab_gp('w');
		$h = bab_gp('h', 50);
		getResizedImage(bab_gp('f'), $w, $h, bab_gp('com'));
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
		iframe($editor,$path);
		break;
	case 'list':
	default:
		listImages($editor,$path);
		break;
	}
?>