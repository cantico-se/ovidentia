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

include_once $GLOBALS['babInstallPath'].'utilit/gdiincl.php';

function getFmImage($idf, $w, $h)
{

    $imgf = $GLOBALS['babInstallPath'].'skins/ovidentia/images/imgget.png';
    $mime = 'image/png';
    $fsize = filesize($imgf);
    header("Content-Type: $mime"."\n");
    header("Content-Length: ". $fsize."\n");
    header("Content-transfert-encoding: binary"."\n");
    $fp=fopen($imgf,"rb");
    print fread($fp,$fsize);
    fclose($fp);
    die;

    /*global $babDB;
    include_once $GLOBALS['babInstallPath'].'utilit/fileincl.php';

    $access = fm_getFileAccess($idf);

    if (!$access['bdownload'])
    {
        die('Access denied');
    }

    return bab_getResizedImage($access['oFolderFile']->getFullPathname(), $w, $h);*/

}


/* main */

$idx = bab_rp('idx', 'get');

switch($idx)
    {
    case "get":
    default:
        $idf = bab_rp('idf');
        $w = bab_rp('w');
        $h = bab_rp('h');
        getFmImage($idf, $w, $h);
        break;
    }
