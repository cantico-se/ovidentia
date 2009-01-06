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
	
/**
 * Helper class to resize image
 *
 */
class bab_ImageResize
{
	private $sFullPathName	= null;
	private $sMime			= null;
	private $iRealHeight	= null;
	private $iRealWidth		= null;
	
	public function __construct()
	{
		
	}
	
	private function gdLoaded()
	{
		return extension_loaded('gd');
	}
	
	private function createImageFromType()
	{
		switch($this->sMime)
		{
	        case 'image/gif':
	            if(imagetypes() & IMG_GIF)  
	            {
	                return imageCreateFromGIF($this->sFullPathName);
	            }	            break;
	        case 'image/jpeg':
	            if(imagetypes() & IMG_JPG)  
	            {
	                return imageCreateFromJPEG($this->sFullPathName);
	            }
	            break;
	        case 'image/png':
	            if(imagetypes() & IMG_PNG) 
	            {
	                return imageCreateFromPNG($this->sFullPathName);
	            }
	        default:
	            return null;
    	}
	}
	
	private function chkgd2()
	{
		if(function_exists('gd_info'))
		{
			$aInfo = gd_info();
			preg_match('/\d/', $aInfo['GD Version'], $aMatch);
			if($aMatch[0] >= 2) 
			{
				return true;
			}
		}
		return false;
	}

	
	private function gdVersion($iUserVer = 0)
	{
	   static $iGdVer = 0;
	   // Just accept the specified setting if it's 1.
	   if($iUserVer == 1) 
	   { 
	   		$iGdVer = 1; 
	   		return 1; 
	   }
	   
	   // Use the static variable if function was called previously.
	   if($iUserVer != 2 && $iGdVer > 0) 
	   { 
	   		return $iGdVer; 
	   }
	   
	   // Use the gd_info() function if possible.
	   if(function_exists('gd_info')) 
	   {
		   $aInfo = gd_info();
		   preg_match('/\d/', $aInfo['GD Version'], $aMatch);
		   $iGdVer = $aMatch[0];
		   return $aMatch[0];
	   }
	   
	   // If phpinfo() is disabled use a specified / fail-safe choice...
	   if(preg_match('/phpinfo/', ini_get('disable_functions'))) 
	   {
		   if($iUserVer == 2) 
		   {
			   $iUserVer = 2;
			   return 2;
		   }
		   else
		   {
			   $iUserVer = 1;
			   return 1;
		   }
	   }
	   
	   // ...otherwise use phpinfo().
	   ob_start();
	   phpinfo(8);
	   $aInfo = ob_get_contents();
	   ob_end_clean();
	   $aInfo = stristr($aInfo, 'gd version');
	   preg_match('/\d/', $aInfo, $aMatch);
	   $iGdVer = $aMatch[0];
	   return $aMatch[0];
	} // End gdVersion()

					
	private function outputImage($oOutImgRes)
	{
		switch($this->sMime)
		{
	        case 'image/gif':
				header('Content-type: image/gif');
				imagegif($oOutImgRes);
				break;
	        case 'image/jpeg':
				header('Content-type: image/jpeg');
				imagejpeg($oOutImgRes);
				break;
	        case 'image/png':
				header('Content-type: image/png');
				imagepng($oOutImgRes);
				break;
	        default:
	            echo '';
    	}
	}
	
	public function scale($sFullPathname, $iScale) 
	{
		if(!bab_ImageResize::gdLoaded())
		{
			$this->outputNoResizedImage($sFullPathname);
			return;
		}
		
		if(!$this->setImageInformation($sFullPathname))
		{
			$this->outputNoResizedImage($sFullPathname);
			return;
		}
		
		$oImgRes = $this->createImageFromType();
		if(is_null($oImgRes))
		{
			$this->outputNoResizedImage($sFullPathname);
			return;
		}
		
		$iWidth = $this->iRealWidth * $iScale / 100;
    	$iHeight = $this->iRealHeight * $iScale / 100; 
    	$this->resize($oImgRes, $iWidth, $iHeight);
	}
 
	
	public function resizeImageAuto($sFullPathname, $iWidth, $iHeight)
	{
		if(0 === (int) $iWidth && 0 < (int) $iHeight)
		{
			$this->resizeToHeight($sFullPathname, $iHeight);
		}
		else if(0 < (int) $iWidth && 0 === (int) $iHeight)
		{
			$this->resizeImageToWidth($sFullPathname, $iWidth);
		}
		else
		{
			$this->resizeImage($sFullPathname, $iWidth, $iHeight);
		}
	}
	
	public function resizeImageToWidth($sFullPathname, $iWidth)
	{
		if(!bab_ImageResize::gdLoaded())
		{
			$this->outputNoResizedImage($sFullPathname);
			return;
		}
		
		if(!$this->setImageInformation($sFullPathname))
		{
			$this->outputNoResizedImage($sFullPathname);
			return;
		}
		
		$oImgRes = $this->createImageFromType();
		if(is_null($oImgRes))
		{
			$this->outputNoResizedImage($sFullPathname);
			return;
		}
		
		$iRatio		= $iWidth / $this->iRealWidth;
     	$iHeight	= $this->iRealHeight * $iRatio;
      	$this->resize($oImgRes, $iWidth, $iHeight);
	}
	
	public function resizeToHeight($sFullPathname, $iHeight) 
	{
		if(!bab_ImageResize::gdLoaded())
		{
			$this->outputNoResizedImage($sFullPathname);
			return;
		}
		
		if(!$this->setImageInformation($sFullPathname))
		{
			$this->outputNoResizedImage($sFullPathname);
			return;
		}
		
		$oImgRes = $this->createImageFromType();
		if(is_null($oImgRes))
		{
			$this->outputNoResizedImage($sFullPathname);
			return;
		}
		
		$iRatio	= $iHeight / $this->iRealHeight;
      	$iWidth	= $this->iRealWidth * $iRatio;
      	$this->resize($oImgRes, $iWidth, $iHeight);
	}
	
	public function resizeImage($sFullPathname, $iWidth, $iHeight)
	{
		if(!bab_ImageResize::gdLoaded())
		{
			$this->outputNoResizedImage($sFullPathname);
			return;
		}
		
		if(!$this->setImageInformation($sFullPathname))
		{
			$this->outputNoResizedImage($sFullPathname);
			return;
		}
		
		$oImgRes = $this->createImageFromType();
		if(is_null($oImgRes))
		{
			$this->outputNoResizedImage($sFullPathname);
			return;
		}
		
		if($this->iRealHeight > $this->iRealWidth)
		{
			$iRatio = ($iHeight / $this->iRealHeight);
		}
		else 
		{
			$iRatio = ($iWidth / $this->iRealWidth);
		}
		
		$iHeight	= $iRatio * $this->iRealHeight;
		$iWidth		= $iRatio * $this->iRealWidth;
		$this->resize($oImgRes, $iWidth, $iHeight);
	}
	
	private function resize($oImgRes, $iWidth, $iHeight)
	{
		if($this->gdVersion() >= 2)
		{
			$oOutImgRes = ImageCreateTrueColor($iWidth, $iHeight);
			imagecopyresampled($oOutImgRes, $oImgRes, 0, 0, 0, 0, $iWidth, $iHeight, $this->iRealWidth, $this->iRealHeight);
		}
		else
		{
			$oOutImgRes = ImageCreate($iWidth, $iHeight);
			imagecopyresized($oOutImgRes, $oImgRes, 0, 0, 0, 0, $iWidth, $iHeight, $this->iRealWidth, $this->iRealHeight);
		}
		
		imagedestroy($oImgRes);
		$this->outputImage($oOutImgRes);
	}
	
	private function setImageInformation($sFullPathname)
	{
		if(!is_file($sFullPathname))
		{
			return false;
		}
		
		if(!is_readable($sFullPathname))
		{
			return false;
		}

		$aImgInfo = getimagesize($sFullPathname);
		if(!is_array($aImgInfo))
		{
			return false;
		}

		$aSupportedMime = array('image/gif' => 'image/gif', 'image/jpeg' => 'image/jpeg', 'image/png' => 'image/png');
		if(!array_key_exists($aImgInfo['mime'], $aSupportedMime))
		{
			return false;
		}
		
		$this->sFullPathName	= $sFullPathname;
		$this->sMime			= $aImgInfo['mime'];
		$this->iRealWidth		= $aImgInfo[0];
		$this->iRealHeight		= $aImgInfo[1];
		return true;
	}
	
	private function outputNoResizedImage($sFullPathName)
	{
		if(!is_file($sFullPathName))
		{
			echo '';
			return;
		}
		
		if(!is_readable($sFullPathName))
		{
			echo '';
			return;
		}
		
		$sMime = bab_getFileMimeType($sFullPathName);
		$iSize = filesize($sFullPathName);
		header("Content-Type: $sMime" . "\n");
		header("Content-Length: ". $iSize . "\n");
		header("Content-transfert-encoding: binary"."\n");
		$fp = fopen($this->sFullPathName, "rb");
		print fread($fp, $iSize);
		fclose($fp);	
	}
}
	
?>