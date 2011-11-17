<?php
//-------------------------------------------------------------------------
// OVIDENTIA http://www.ovidentia.org
//
// Ovidentia is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2, or (at your option)
// any later version.
// 
// This program is distributed in the hope that it will be useful, but
// WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
// See the GNU General Public License for more details.
// 
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307,
// USA.
//-------------------------------------------------------------------------
/**
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 * @copyright Copyright (c) 2006 by CANTICO ({@link http://www.cantico.fr})
 */
include_once 'base.php';
require_once dirname(__FILE__).'/utilit/registerglobals.php';
include_once $babInstallPath.'utilit/gdiincl.php';

function getFmImage($idf, $w, $h)
{
	global $babDB;
	include_once $GLOBALS['babInstallPath'].'utilit/fileincl.php';
	
	$access = fm_getFileAccess($idf);
	
	if (!$access['bdownload'])
	{
		die('Access denied');
	}

	return bab_getResizedImage($access['oFolderFile']->getFullPathname(), $w, $h);
	
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