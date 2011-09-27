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


include_once 'base.php';



function getImage()
{
	
	require_once $GLOBALS['babInstallPath'].'/utilit/artincl.php';
	require_once $GLOBALS['babInstallPath'].'/utilit/gdiincl.php';

	$iWidth			= (int) bab_rp('iWidth', 0);
	$iHeight		= (int) bab_rp('iHeight', 0);
	$iIdDeleg	= (int) bab_rp('iIdDeleg', 0);

	$oEnvObj		= bab_getInstance('bab_PublicationPathsEnv');

	$sPath = '';
	if(0 !== $iIdDeleg)
	{
		$uploadPath = new bab_Path($GLOBALS['babUploadPath'],'delegation','image','DG'.$iIdDeleg);
		if($uploadPath->isDir()){
			foreach($uploadPath as $file){
				if(is_file($file->tostring())){
					$sPath = $file->tostring();
				}
			}
		}
	}

	if($sPath == ''){
		return '';
	}
	if(bab_gp('realFile','') == 1){
		header('Content-type: ' . bab_getFileMimeType($sPath, $subtype));
		readfile($sPath);
		die;
	}

	$oImageResize = new bab_ImageResize();
	$oImageResize->resizeImageAuto($sPath, $iWidth, $iHeight);
}

$idx = bab_rp('idx', 'getImage');
switch($idx)
{
	case "getImage":
	default:
		getImage();
		break;
}

