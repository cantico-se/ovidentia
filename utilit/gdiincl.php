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
					  if (function_exists('gd_info'))
						{
						$arr = gd_info();
						preg_match('/\d/', $arr['GD Version'], $match);
						if ( $match[0] >= 2) return true;
						}

					  return false;
					}


				function gdVersion($user_ver = 0)
					{
					   static $gd_ver = 0;
					   // Just accept the specified setting if it's 1.
					   if ($user_ver == 1) { $gd_ver = 1; return 1; }
					   // Use the static variable if function was called previously.
					   if ($user_ver !=2 && $gd_ver > 0 ) { return $gd_ver; }
					   // Use the gd_info() function if possible.
					   if (function_exists('gd_info')) {
						   $ver_info = gd_info();
						   preg_match('/\d/', $ver_info['GD Version'], $match);
						   $gd_ver = $match[0];
						   return $match[0];
					   }
					   // If phpinfo() is disabled use a specified / fail-safe choice...
					   if (preg_match('/phpinfo/', ini_get('disable_functions'))) {
						   if ($user_ver == 2) {
							   $gd_ver = 2;
							   return 2;
						   } else {
							   $gd_ver = 1;
							   return 1;
						   }
					   }
					   // ...otherwise use phpinfo().
					   ob_start();
					   phpinfo(8);
					   $info = ob_get_contents();
					   ob_end_clean();
					   
					   $info = mb_strtolower($info);
					   $iOffset = mb_strpos($info, 'gd version');
					   if(false !== $iOffset)
					   {
					   		$info = mb_substr($info, $iOffset);	
						   preg_match('/\d/', $info, $match);
						   $gd_ver = $match[0];
						   return $match[0];
					   }
					} // End gdVersion()

				if (gdVersion() >= 2)
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
			$mime = bab_getFileMimeType($imgf);
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