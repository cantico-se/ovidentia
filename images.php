<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
include $babInstallPath."utilit/tempfile.php";

function getResizedImage($img, $w, $h)
	{
	$type = "";
	$imgf = $GLOBALS['babInstallPath']."images/".$img;
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
			$this->imagemaxsize = "25000";
			$this->file = bab_translate("File");
			$this->add = bab_translate("Add");
			$this->refresh = bab_translate("Refresh");
			$tf = new babTempFiles($GLOBALS['babInstallPath']."images/", 600);
			$h = opendir($GLOBALS['babInstallPath']."images/");
			while (($f = readdir($h)) != false)
				{
				if ($f != "." and $f != "..") 
					{
					if (is_file($GLOBALS['babInstallPath']."images/".$f))
						{
						$this->arrfile[] = $f;
						$this->arrufile[] = $GLOBALS['babInstallPath']."images/".$f;
						}
					}
				}
			closedir($h);
			$this->ifiles = 0;
			$this->gd = extension_loaded('gd');
			$this->refurl = $GLOBALS['babUrlScript']."?tg=images";
			}

		function getnextfiles()
			{
			if( $this->ifiles < count($this->arrfile))
				{
				return true;
				}
			else
				return false;
			}

		function getnextfile()
			{
			static $i = 0;
			if( $i < 5 && $this->ifiles < count($this->arrfile))
				{
				$this->name = $this->arrfile[$this->ifiles];
				$imgsize = getimagesize($GLOBALS['babInstallPath']."images/".$this->arrfile[$this->ifiles]);
				$this->imgalt = $imgsize[0]." X ".$imgsize[1];
				if( $imgsize[0] > 50 && $imgsize[1] > 50)
					{
					if( $this->gd && ($imgsize[2] == 1 || $imgsize[2] == 2 || $imgsize[2] == 3))
						{
						$this->srcurl = $GLOBALS['babUrlScript']."?tg=images&idx=get&f=".$this->arrfile[$this->ifiles]."&w=50&h=50";
						$this->imgurl = $this->arrufile[$this->ifiles];
						}
					else
						{
						$this->srcurl = $this->arrufile[$this->ifiles];
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
					$this->srcurl = $this->arrufile[$this->ifiles];
					$this->imgurl = $this->arrufile[$this->ifiles];
					$this->imgwidth = $imgsize[0];
					$this->imgheight = $imgsize[1];
					}
				$this->ifiles++;
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

function saveImage($file, $size, $tmpfile)
	{
	$tf = new babTempFiles($GLOBALS['babInstallPath']."images/", 600);
	$nf = $tf->tempfile($tmpfile, $file);
	if( empty($nf))
		{
		$babBody->msgerror = bab_translate("Cannot upload file");
		return;
		}
	}

/* main */
if( !isset($idx))
	$idx = "list";

if( isset($Submit))
	{
	saveImage($uploadf_name, $uploadf_size,$uploadf);
	}

switch($idx)
	{
	case "get":
		getResizedImage($f, $w, $h);
		break;
	case "list":
	default:
		listImages();
		break;
	}
?>