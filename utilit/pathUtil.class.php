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
include 'base.php';



class BAB_PathUtil
{
	function sanitize($sPath)
	{
		$sPath = str_replace('\\', '/', $sPath);
		
		$sFirstChar = substr($sPath, 0, 1);

		$sDrive = '';
		if(0 !== preg_match("/(^[a-zA-z0-9]){1}(\:){1}(\/){1}.*$/", $sPath, $aMatch))
		{
			$sDrive	= $aMatch[1] . $aMatch[2] . $aMatch[3];
			$sPath	= substr($sPath, strlen($sDrive));
		}
		
		$sPath	= BAB_PathUtil::removeEndSlashes($sPath);
		$aPaths	= explode('/', $sPath);

		if(is_array($aPaths) && count($aPaths) > 0)
		{
			$aGoodPathItem = array();
			foreach($aPaths as $iKey => $sPathItem)
			{
				if(strlen(trim($sPathItem)) !== 0)
				{
					$aGoodPathItem[] = BAB_PathUtil::sanitizePathItem($sPathItem);
				}
			}

			$sPathname = $sDrive . (($sFirstChar == '/') ? '/' : '') . implode('/', $aGoodPathItem);
			return $sPathname;
		}
		
		return $sPath;
	}
	
	function addEndSlash($sPath)
	{
		if(is_string($sPath))
		{
			$iLength = strlen(trim($sPath));
			if($iLength > 0)
			{
				$sLastChar = substr($sPath, -1);
				if($sLastChar !== '/' && $sLastChar !== '\\')
				{
					$sPath .= '/';
				}
			}
		}
		return $sPath;
	}
	
	function removeEndSlah($sPath)
	{
		if(is_string($sPath))
		{
			$iLength = strlen(trim($sPath));
			if($iLength > 0)
			{
				$sLastChar = substr($sPath, -1);
				if($sLastChar === '/' || $sLastChar === '\\')
				{
					return substr($sPath, 0, -1);
				}
			}
		}
		return $sPath;
	}
	
	function haveEndSlash($sPath)
	{
		$iLength = strlen(trim($sPath));
		if($iLength > 0)
		{
			$sLastChar = substr($sPath, -1);
			return ($sLastChar === '/' || $sLastChar === '\\');
		}
		return false;	
	}
	
	function removeEndSlashes($sPath)
	{
		while(BAB_PathUtil::haveEndSlash($sPath))
		{
			$sPath = BAB_PathUtil::removeEndSlah($sPath);
		}
		return $sPath;
	}
	
	function sanitizePathItem($sPathItem)
	{
		if(is_string($sPathItem) && strlen(trim($sPathItem)) > 0)
		{
			if(isset($GLOBALS['babFileNameTranslation']))
			{
				$sPathItem = strtr($sPathItem, $GLOBALS['babFileNameTranslation']);
			}
			
			static $aTranslation = array('\\' => '_', '/' => '_', ':' => '_', '*' => '_', '?' => '_', '<' => '_', '>' => '_', '|' => '_', '"' => '_');
			$sPathItem = strtr($sPathItem, $aTranslation);
		}
		return $sPathItem;
	}
}
?>