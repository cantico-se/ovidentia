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
$babDBHost = "localhost"; /* MySql database server */
$babDBLogin = ""; /* MySql database login */
$babDBPasswd = ""; /* MySql database password */
$babDBName ="ovidentia"; /* MySql database name */
$babInstallPath = "ovidentia/"; /* relatif path to ovidentia distribution */
$babSlogan = "Ovidentia: enterprise portal"; /* your slogan */
$babSiteName = "Ovidentia"; /* your site name */
$babUrl = "http://yourdomain/"; /* url to access to your site */
$babVersion = "5.0"; /* current version */
$babMaxFileSize = 1000000; /* Max size ( bytes ) file allowed*/
$babMaxUserSize = 2000000; /* Capacity storage ( bytes ) allowed by user*/
$babMaxGroupSize = 5000000; /* Capacity storage ( bytes ) allowed by group */
$babMaxTotalSize = 100000000; /* Capacity storage ( bytes ) allowed for a site */
$babUploadPath = "/uploads-directory"; /* where to upload files ( c:\\path-to\\upload-directory for Windows )*/
$babFileNameTranslation = array("&" => "_","\"" => "_","'" => "_","'" => "_",";"=>"_","~"=>"-"); /* translation characters for files names.*/
?>