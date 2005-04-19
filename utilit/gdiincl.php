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


function bab_getResizedImage($imgf, $w, $h)
	{

	if( file_exists($imgf))
		{
		$gdi = extension_loaded('gd');

		$imgsize = @getimagesize($imgf);

		if( !$gdi || ($imgsize[2] == 1 && !(imagetypes() & IMG_GIF)) || ($imgsize[2] == 2 && !(imagetypes() & IMG_JPG)) || ($imgsize[2] == 3 && !(imagetypes() & IMG_PNG)) )
			$gdi = false;

		if( $gdi )
			{
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

				if( $w == "" )
					$w = $wtmp;
				if( $h == "" )
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
					$wimg = (real)( ((real)(($himg/$htmp)*100) * $wtmp)/100);  
					}
				
				function chkgd2(){
					  $testGD = get_extension_funcs("gd"); // Grab function list
					  if (!$testGD){ echo "GD not even installed."; exit; }
					  if (in_array ("imagegd2",$testGD)) $gd_version = "<2"; // Check
					  if ($gd_version == "<2") return false; else return true;
					}

				if (chkgd2())
					{
					$out = ImageCreateTrueColor($wimg, $himg);
					imagecopyresampled($out, $tmp, 0, 0, 0, 0, $wimg, $himg, $imgsize[0], $imgsize[1]);
					}
				else
					{
					$out = ImageCreate($wimg, $himg);
					imagecopyresized($out, $tmp, 0, 0, 0, 0, $wimg, $himg, $imgsize[0], $imgsize[1]);
					}
				
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
		else
			{
			$mime = "application/octet-stream";
			if ($ext = strrchr($imgf,"."))
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
			$fsize = filesize($imgf);
			header("Content-Type: $mime"."\n");
			header("Content-Length: ". $fsize."\n");
			header("Content-transfert-encoding: binary"."\n");
			$fp=fopen($imgf,"rb");
			print fread($fp,$fsize);
			fclose($fp);	
			}
		}
	}
?>