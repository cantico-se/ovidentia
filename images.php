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
include $babInstallPath."utilit/tempfile.php";
include $babInstallPath."utilit/imgincl.php";

function bab_mkdir($path, $mode)
{
	if( substr($path, -1) == "/" )
		$path = substr($path, 0, -1);
	mkdir($path, $mode);
}

function getResizedImage($img, $w, $h, $com)
	{
	$type = "";
	if($com)
		$imgf = BAB_IUD_COMMON.$img;
	else
		$imgf = BAB_IUD_TMP.$img;
	if( file_exists($imgf))
		{
		$imgsize = @getimagesize($imgf);
		if( $imgsize )
			{
			switch($imgsize[2])
				{
				case '2':
					$type = "jpeg";
					break;
				case '1':
					$type = "gif";
					break;
				case '3':
					$type = "png";
					break;
				default:
					break;
				}
			}
		}

	if( !empty($type))
		{
		switch($imgsize[2])
			{
			case '2':
				$tmp = imagecreatefromjpeg($imgf);
				break;
			case '1':
				$tmp = imagecreatefromgif($imgf);
				break;
			case '3':
				$tmp = imagecreatefrompng($imgf);
				break;
			default:
				$tmp = 0;
				break;
			}
		}

	if( $tmp )
		{
		$wtmp = imagesx($tmp);
		$htmp = imagesy($tmp);
		if( $wtmp > $htmp )
			{
			$wimg = $w;
			$himg = (real)( ((real)(($wimg/$wtmp)*100) * $wtmp)/100);          
			}  
		else if ($htmp > $wtmp)  
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

function listImages($editor)
	{
	class temp
		{

		function temp($editor)
			{
			$db = $GLOBALS['babDB'];

			$req="select * from ".BAB_SITES_TBL." where name='".addslashes($GLOBALS['babSiteName'])."'";
			$res=$db->db_query($req);

			if( $res && $db->db_num_rows($res) > 0 )
				{
				$arr = $db->db_fetch_array($res);
				$this->maximagessize = $arr['imgsize'];
				}
			else
				$this->maximagessize = 25;
			$this->maxsizetxt = bab_translate("Image size must not exceed")." ".$this->maximagessize. " ". bab_translate("Kb");
			$this->maximagessize *= 1000 ;
			$this->file = bab_translate("File");
			$this->add = bab_translate("Add");
			$this->yes = bab_translate("Yes");
			$this->no = bab_translate("No");
			$this->shared = bab_translate("Shared");
			$this->refresh = bab_translate("Refresh");
			$this->delete = bab_translate("Delete");
			$this->invalidimg = bab_translate("Invalid image extension");
			$this->aligntxt = bab_translate("Alignment");
			$this->alt = bab_translate("Alt");
			$this->hspacing = bab_translate("Horizontal spacing");
			$this->vspacing = bab_translate("Vertical spacing");
			$this->border = bab_translate("Border");
			$this->invalidentry = bab_translate("You must specify a number");

			$this->badmin = bab_isUserAdministrator();
			$this->comnum = 0;
			$this->editor = $editor;
			if( !is_dir(BAB_IUD_TMP))
				bab_mkdir(BAB_IUD_TMP, 0700);
			if( !is_dir(BAB_IUD_COMMON))
				bab_mkdir(BAB_IUD_COMMON, 0700);
			if( !is_dir(BAB_IUD_ARTICLES))
				bab_mkdir(BAB_IUD_ARTICLES, 0700);
			$tf = new babTempFiles(BAB_IUD_TMP, BAB_FILE_TIMEOUT);
			$h = opendir(BAB_IUD_COMMON);
			while (($f = readdir($h)) != false)
				{
				if ($f != "." and $f != "..") 
					{
					if (is_file(BAB_IUD_COMMON.$f))
						{
						$this->arrcfile[] = BAB_IUD_COMMON.$f;
						}
					}
				}
			closedir($h);
			$db = $GLOBALS['babDB'];
			$res = $db->db_query("select * from ".BAB_IMAGES_TEMP_TBL." where id_owner='".$GLOBALS['BAB_SESS_USERID']."'");
			if( $res && $db->db_num_rows($res) > 0 )
				{
				while( $arr = $db->db_fetch_array($res))
					{
					if( is_file(BAB_IUD_TMP.$arr['name']))
						$this->arrufile[] = BAB_IUD_TMP.$arr['name'];
					else
						$db->db_query("delete from ".BAB_IMAGES_TEMP_TBL." where id='".$arr['id']."'");
					}
				}

			$this->uifiles = 0;
			$this->cifiles = 0;
			$this->gdi = extension_loaded('gd');
			$this->refurl = $GLOBALS['babUrlScript']."?tg=images&editor=".$this->editor;
			}

		function geturls($filename, $com)
			{
			$this->name = basename($filename);
			$imgsize = getimagesize($filename);
			$this->imgalt = $imgsize[0]." X ".$imgsize[1];
			$this->imgurl = $GLOBALS['babUrl'].$filename;
			if( $imgsize[0] > 50 || $imgsize[1] > 50)
				{
				if( !$this->gdi || ($imgsize[2] == 1 && !(imagetypes() & IMG_GIF)) || ($imgsize[2] == 2 && !(imagetypes() & IMG_JPG)) || ($imgsize[2] == 3 && !(imagetypes() & IMG_PNG)) )
					$this->gd = false;
				else
					$this->gd = $this->gdi;

				if( $this->gd && ($imgsize[2] == 1 || $imgsize[2] == 2 || $imgsize[2] == 3))
					{
					$this->srcurl = $GLOBALS['babUrlScript']."?tg=images&idx=get&f=".$this->name."&w=50&h=50&com=".$com;
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
		function getnextcomfiles()
			{
			if( $this->cifiles < count($this->arrcfile))
				{
				return true;
				}
			else
				return false;
			}

		function getnextcfile()
			{
			static $i = 0;
			if( $i < 5 && $this->cifiles < count($this->arrcfile))
				{
				$this->geturls($this->arrcfile[$this->cifiles], 1);
				$this->imgname = basename($this->arrcfile[$this->cifiles]);
				$this->delurl = $GLOBALS['babUrlScript']."?tg=images&idx=del&com=1&f=".$this->imgname."&editor=".$this->editor;
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

		function getnextfiles()
			{
			if( $this->uifiles < count($this->arrufile))
				{
				return true;
				}
			else
				return false;
			}

		function getnextufile()
			{
			static $i = 0;
			if( $i < 5 && $this->uifiles < count($this->arrufile))
				{
				$this->geturls($this->arrufile[$this->uifiles], 0);
				$this->imgname = basename($this->arrufile[$this->uifiles]);
				$this->delurl = $GLOBALS['babUrlScript']."?tg=images&idx=del&com=0&f=".$this->imgname."&editor=".$this->editor;
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
		}
	$temp = new temp($editor);
	echo bab_printTemplate($temp,"images.html", "imageslisteditor");
	}


$msgerror = "";
function saveImage($file, $size, $tmpfile, $share)
	{
	$nf = "";
	if( !strstr($file, "..") && is_uploaded_file($tmpfile))
		{
		$tf = new babTempFiles(BAB_IUD_TMP, BAB_FILE_TIMEOUT);
		if( !empty($share) && $share == "Y" && bab_isUserAdministrator())
			{
			if( is_file(BAB_IUD_COMMON.$file))
				{
				$GLOBALS['msgerror'] = bab_translate("A file with the same name already exists");
				return $nf;
				}
			if( move_uploaded_file($tmpfile, BAB_IUD_COMMON.$file))
				$nf = BAB_IUD_COMMON.$file;
			}
		else if( !empty($GLOBALS['BAB_SESS_USERID']))
			{
			$nf = $tf->tempfile($tmpfile, $file);
			if( !empty($nf))
				{
				$db = $GLOBALS['babDB'];
				$db->db_query("insert into ".BAB_IMAGES_TEMP_TBL." (name, id_owner) values ('".basename($nf)."', '".$GLOBALS['BAB_SESS_USERID']."')");
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
	switch($com)
		{
		case 1:
			if (bab_isUserAdministrator() && is_file(BAB_IUD_COMMON.$f))
				@unlink(BAB_IUD_COMMON.$f);
			break;
		case 0:
			$db = $GLOBALS['babDB'];
			$res = $db->db_query("select * from ".BAB_IMAGES_TEMP_TBL." where id_owner='".$GLOBALS['BAB_SESS_USERID']."' and name='".$f."'");
			if( $res && $db->db_num_rows($res) == 1 )
				{
				$db->db_query("delete from ".BAB_IMAGES_TEMP_TBL." where id_owner='".$GLOBALS['BAB_SESS_USERID']."' and name='".$f."'");
				@unlink(BAB_IUD_TMP.$f);
				}
			break;
		}
	}

/* main */
if( !isset($idx))
	$idx = "list";

if( !isset($editor))
	$editor = "none";

if( isset($addf) && $addf == "add")
	{
	saveImage($uploadf_name, $uploadf_size,$uploadf, $share);
	}

switch($idx)
	{
	case "get":
		getResizedImage($f, $w, $h, $com);
		break;
	case "del":
		delImage($com, $f);
		/* no break */
	case "list":
	default:
		listImages($editor);
		break;
	}
?>