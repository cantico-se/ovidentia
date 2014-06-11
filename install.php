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


/**
 * This script is included once in case of a new install
 * after the prerequisites tests
 */



require_once $GLOBALS['babInstallPath'].'utilit/functionalityincl.php';
$functionalities = new bab_functionalities();
$functionalities->register('Icons'						, $GLOBALS['babInstallPath'].'utilit/icons.php');
$functionalities->register('Icons/Default'				, $GLOBALS['babInstallPath'].'utilit/icons.php');
$functionalities->register('Archive'					, $GLOBALS['babInstallPath'].'utilit/archiveincl.php');
$functionalities->register('Archive/Zip'				, $GLOBALS['babInstallPath'].'utilit/archiveincl.php');
$functionalities->register('Archive/Zip/ZipArchive'		, $GLOBALS['babInstallPath'].'utilit/archiveincl.php');
$functionalities->register('Archive/Zip/Zlib'			, $GLOBALS['babInstallPath'].'utilit/archiveincl.php');
$functionalities->register('CalendarBackend'			, $GLOBALS['babInstallPath'].'utilit/cal.backend.class.php');
$functionalities->register('CalendarBackend/Ovi'		, $GLOBALS['babInstallPath'].'utilit/cal.backend.ovi.class.php');
$functionalities->register('UserEditor'					, $GLOBALS['babInstallPath'].'utilit/usereditor.php');
$functionalities->register('SitemapDynamicNode'			, $GLOBALS['babInstallPath'].'utilit/sitemap_dynamicnode.php');
$functionalities->register('SitemapDynamicNode/Topic'	, $GLOBALS['babInstallPath'].'utilit/sitemap_dyntopic.php');
$functionalities->register('WorkingHours'				, $GLOBALS['babInstallPath'].'utilit/workinghoursincl.php');
$functionalities->register('WorkingHours/Ovidentia'		, $GLOBALS['babInstallPath'].'utilit/workinghoursincl.php');


$func_to_register = $functionalities->parseFile(dirname(__FILE__).'/utilit/omlincl.php');
foreach($func_to_register as $path) {
	$functionalities->register($path	, $GLOBALS['babInstallPath'].'utilit/omlincl.php');
}

$func_to_register = $functionalities->parseFile(dirname(__FILE__).'/utilit/ovmlChart.php');
foreach($func_to_register as $path) {
	$functionalities->register($path	, $GLOBALS['babInstallPath'].'utilit/ovmlChart.php');
}

$func_to_register = $functionalities->parseFile(dirname(__FILE__).'/utilit/ovmldeleg.php');
foreach($func_to_register as $path) {
	$functionalities->register($path	, $GLOBALS['babInstallPath'].'utilit/ovmldeleg.php');
}

$func_to_register = $functionalities->parseFile(dirname(__FILE__).'/utilit/ovmldir.php');
foreach($func_to_register as $path) {
	$functionalities->register($path	, $GLOBALS['babInstallPath'].'utilit/ovmldir.php');
}

$func_to_register = $functionalities->parseFile(dirname(__FILE__).'/utilit/ovmltm.php');
foreach($func_to_register as $path) {
	$functionalities->register($path	, $GLOBALS['babInstallPath'].'utilit/ovmltm.php');
}


$func_to_register = $functionalities->parseFile(dirname(__FILE__).'/utilit/ovmlsitemap.php');
foreach($func_to_register as $path) {
	$functionalities->register($path	, $GLOBALS['babInstallPath'].'utilit/ovmlsitemap.php');
}