<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
include $babInstallPath."utilit/tempfile.php";

define("BAB_FILE_TIMEOUT", 600);
define("BAB_IMAGE_MAXSIZE", 30000);
define("BAB_IMAGES_UPLOADDIR", "images/");
define("BAB_IMAGES_UPLOADDIR_TMP", "images/tmp/");
define("BAB_IMAGES_UPLOADDIR_COMMON", "images/common/");
define("BAB_IMAGES_TEMP_TBL", "bab_images_temp");

function getResizedImage($img, $w, $h, $com)
	{
	$type = "";
	if($com)
		$imgf = BAB_IMAGES_UPLOADDIR_COMMON.$img;
	else
		$imgf = BAB_IMAGES_UPLOADDIR_TMP.$img;
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

function listImages()
	{
	class temp
		{

		function temp()
			{
			$this->maximagessize = BAB_IMAGE_MAXSIZE;
			$this->file = bab_translate("File");
			$this->add = bab_translate("Add");
			$this->yes = bab_translate("Yes");
			$this->no = bab_translate("No");
			$this->shared = bab_translate("Shared");
			$this->refresh = bab_translate("Refresh");
			$this->badmin = bab_isUserAdministrator();
			$this->comnum = 0;
			$tf = new babTempFiles(BAB_IMAGES_UPLOADDIR_TMP, BAB_FILE_TIMEOUT);
			$h = opendir(BAB_IMAGES_UPLOADDIR_COMMON);
			while (($f = readdir($h)) != false)
				{
				if ($f != "." and $f != "..") 
					{
					if (is_file(BAB_IMAGES_UPLOADDIR_COMMON.$f))
						{
						$this->arrcfile[] = BAB_IMAGES_UPLOADDIR_COMMON.$f;
						}
					}
				}
			closedir($h);
			$db = $GLOBALS['babDB'];
			$res = $db->db_query("select * from ".BAB_IMAGES_TEMP_TBL." where id_owner='".$GLOBALS['BAB_SESS_USERID']."'");
			if( $res && $db->db_num_rows($res) > 0 )
				{
				if( !is_dir(BAB_IMAGES_UPLOADDIR_TMP))
					mkdir(BAB_IMAGES_UPLOADDIR_TMP, 0700);
				while( $arr = $db->db_fetch_array($res))
					{
					if( is_file(BAB_IMAGES_UPLOADDIR_TMP.$arr['name']))
						$this->arrufile[] = BAB_IMAGES_UPLOADDIR_TMP.$arr['name'];
					else
						$db->db_query("delete from ".BAB_IMAGES_TEMP_TBL." where id='".$arr['id']."'");
					}
				}

			$this->uifiles = 0;
			$this->cifiles = 0;
			$this->gd = extension_loaded('gd');
			$this->refurl = $GLOBALS['babUrlScript']."?tg=images";
			}

		function geturls($filename, $com)
			{
			$this->name = basename($filename);
			$imgsize = getimagesize($filename);
			$this->imgalt = $imgsize[0]." X ".$imgsize[1];
			if( $imgsize[0] > 50 || $imgsize[1] > 50)
				{
				if( $this->gd && ($imgsize[2] == 1 || $imgsize[2] == 2 || $imgsize[2] == 3))
					{
					$this->srcurl = $GLOBALS['babUrlScript']."?tg=images&idx=get&f=".$this->name."&w=50&h=50&com=".$com;
					$this->imgurl = $filename;
					}
				else
					{
					$this->srcurl = $filename;
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
				$this->imgurl = $filename;
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
	$temp = new temp();
	echo bab_printTemplate($temp,"images.html", "imageslist");
	}

$msgerror = "";
function saveImage($file, $size, $tmpfile, $share)
	{
	$nf = "";
	if( !strstr($file, "..") && is_uploaded_file($tmpfile))
		{
		if( !is_dir(BAB_IMAGES_UPLOADDIR_TMP))
			mkdir(BAB_IMAGES_UPLOADDIR_TMP, 0700);
		$tf = new babTempFiles(BAB_IMAGES_UPLOADDIR_TMP, BAB_FILE_TIMEOUT);
		if( !empty($share) && $share == "Y" && bab_isUserAdministrator())
			{
			if( !is_dir(BAB_IMAGES_UPLOADDIR_COMMON))
				mkdir(BAB_IMAGES_UPLOADDIR_COMMON, 0700);
			if( is_file(BAB_IMAGES_UPLOADDIR_COMMON.$file))
				{
				$GLOBALS['msgerror'] = bab_translate("A file with the same name already exists");
				return $nf;
				}
			if( move_uploaded_file($tmpfile, BAB_IMAGES_UPLOADDIR_COMMON.$file))
				$nf = BAB_IMAGES_UPLOADDIR_COMMON.$file;
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

/* main */
if( !isset($idx))
	$idx = "list";

if( isset($Submit))
	{
	saveImage($uploadf_name, $uploadf_size,$uploadf, $share);
	}

switch($idx)
	{
	case "get":
		getResizedImage($f, $w, $h, $com);
		break;
	case "list":
	default:
		listImages();
		break;
	}
?>