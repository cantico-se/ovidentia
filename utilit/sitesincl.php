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

/**
 * @return string
 */
function getSiteName($id)
	{
	$db = $GLOBALS['babDB'];
	$query = "select * from ".BAB_SITES_TBL." where id='$id'";
	$res = $db->db_query($query);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		return $arr['name'];
		}
	else
		{
		return "";
		}
	}






/**
 * @return	array
 */
function bab_getSitesConfigurationMenus() {

	$menu = array(
		1 => bab_translate('Site configuration'),
		2 => bab_translate('Mail configuration'),
		3 => bab_translate('User options and login configuration'),
		4 => bab_translate('File upload configuration'),
		5 => bab_translate('Date format configuration'),
		6 => bab_translate('Calendar configuration'),
		13 => bab_translate('Working days and non-working day configuration'),
		7 => bab_translate('Home page managers'),
		8 => bab_translate('Authentification configuration'),
		9 => bab_translate('Inscription configuration'),
		10=> bab_translate('WYSIWYG editor configuration')
	);

	if (bab_searchEngineInfos())
		{
		$menu[11] = bab_translate('Search engine configuration');
		}
	$menu[12] = bab_translate('Web services');

	return $menu;
}




/**
 * List of sites
 * @return ressource
 */
function bab_getSitesRes() {
	global $babDB;
	$req = "select * from ".BAB_SITES_TBL." ORDER BY name";
	return $babDB->db_query($req);
}