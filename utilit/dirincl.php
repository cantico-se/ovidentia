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
define("ADDON_DIRECTORIES_TBL", "ad_directories");
define("ADDON_FIELDS_TBL", "ad_fields");
define("ADDON_DIRECTORIES_FIELDS_TBL", "ad_directories_fields");
define("ADDON_DBENTRIES_TBL", "ad_dbentries");
define("ADDON_DIRVIEW_GROUPS_TBL", "ad_dirview_groups");
define("ADDON_DIRUPDATE_GROUPS_TBL", "ad_dirupdate_groups");
define("ADDON_DIRADD_GROUPS_TBL", "ad_diradd_groups");

function getDirectoryName($id, $table)
	{
	$db = $GLOBALS['babDB'];
	$query = "select name from ".$table." where id='".$id."'";
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

function ad_translate($str)
	{
		return bab_translate($str, $GLOBALS['babAddonFolder']);
	}
?>