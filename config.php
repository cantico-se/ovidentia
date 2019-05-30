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
$babDBHost = "mysql"; /* MySql database server */
$babDBLogin = "root"; /* MySql database login */
$babDBPasswd = "secret"; /* MySql database password */
$babDBName ="ovidentia"; /* MySql database name */
$babInstallPath = "ovidentia/"; /* relatif path to ovidentia distribution */
$babSiteName = "Ovidentia"; /* your site name */
// $babUrl = "http://yourdomain/"; /* url to access to your site */
$babVersion = "6.0"; /* current version */
$babFileNameTranslation = array('\\' => '_', '/' => '_', ':' => '_', '*' => '_', '?' => '_', '<' => '_', '>' => '_', '|' => '_', "&" => "_","\"" => "_","'" => "_",";"=>"_","~"=>"-","+"=>""); /* translation characters for files names.*/

/*
 * Errors PHP
 * To show the errors PHP on the site, remove comments on the following lines.
 * It is advised not to show the errors in production.
 */
ini_set('display_errors', 1);
error_reporting(E_ALL & ~E_STRICT & ~E_WARNING & ~E_DEPRECATED & ~E_NOTICE);

/**
 * Allow administrators to upload addons and versions or download database
 */
define('BAB_SYSTEM_ACCESS', true);