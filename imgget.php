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

function getResizedImage($imgf, $w, $h)
	{
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
		if( $w == "" )
			$w = $wtmp;
		if( $h == "" )
			$h = $htmp;
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

function getFmImage($idf, $w, $h)
	{
	global $babDB;
	include_once $GLOBALS['babInstallPath']."utilit/fileincl.php";

	$res = $babDB->db_query("select * from ".BAB_FILES_TBL." where id='".$idf."'");
	if( $res && $babDB->db_num_rows($res) > 0 )
		{
		$arr = $babDB->db_fetch_array($res);
		$fullpath = bab_getUploadFullPath($arr['bgroup'], $arr['id_owner']);
		if( !empty($arr['path']))
			$fullpath .= $arr['path']."/";
		$fullpath .= $arr['name'];
		return getResizedImage($fullpath, $w, $h);
		}
	}


/* main */
if( !isset($idx))
	$idx = "get";

switch($idx)
	{
	case "get":
	default:
		if( !isset($w)) $w = "";
		if( !isset($h)) $h = "";
		getFmImage($idf, $w, $h);
		break;
	}
?>