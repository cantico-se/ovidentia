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
include_once $babInstallPath.'utilit/gdiincl.php';

function getFmImage($idf, $w, $h)
	{
	global $babDB;
	include_once $GLOBALS['babInstallPath']."utilit/fileincl.php";

	$res = $babDB->db_query("select * from ".BAB_FILES_TBL." where id='".$babDB->db_escape_string($idf)."'");
	if( $res && $babDB->db_num_rows($res) > 0 )
		{
		$arr = $babDB->db_fetch_array($res);
		$fullpath = bab_getUploadFullPath($arr['bgroup'], $arr['id_owner']);
		if( !empty($arr['path']))
			$fullpath .= $arr['path']."/";

		return bab_getResizedImage($fullpath.$arr['name'], $w, $h);
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