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

function bab_getNonWorkingDayTypes($with_date = false)
{
	$arr[101] = $with_date ? bab_translate("Day") : bab_translate("Non-working day");
	$arr[102] = $with_date ? bab_translate("Repeat yearly") : bab_translate("Non-working day");

	$arr[1] = bab_translate("Easter");
	$arr[2] = bab_translate("Ascencion");
	$arr[3] = bab_translate("Pentecost");

	return $arr;
}


function bab_getNonWorkingDays($year)
{
	$db = & $GLOBALS['babDB'];
	$return = array();
	$id_site = & $GLOBALS['babBody']->babsite['id'];
	
	$res = $db->db_query("SELECT nw_day,nw_type FROM ".BAB_SITES_NONWORKING_DAYS_TBL." WHERE id_site='".$id_site."' AND YEAR(nw_day) = '".$year."'");

	if ($db->db_num_rows($res) > 0)
		{
		while( list($day,$type) = $db->db_fetch_array($res) )
			{
			$return[$day] = bab_translate($type);
			}
		}
	else
		{
		$t_type = bab_getNonWorkingDayTypes();
		$DAY = 86400;

		$res = $db->db_query("SELECT nw_day,nw_type FROM ".BAB_SITES_NONWORKING_CONFIG_TBL." WHERE id_site='".$id_site."'");

		if ($db->db_num_rows($res) == 0)
			return array();

		while( list($day,$type) = $db->db_fetch_array($res) )
			{
			$r_date = false;

			switch ($type)
				{
				case 101:
					list($d,$m,$y) = explode('-',$day);
					if ($y == $year)
						{
						$r_date = sprintf("%04s-%02s-%02s", $y, $m, $d);
						}
					break;

				case 102:
					list($d,$m) = explode('-',$day);
					$r_date = sprintf("%04s-%02s-%02s", $year, $m, $d);
					break;

				case 1:
					$r_date = date("Y-m-d", easter_date($year) + $DAY*1);
					break;

				case 2:
					$r_date = date("Y-m-d", easter_date($year) + $DAY*39);
					break;

				case 3:
					$r_date = date("Y-m-d", easter_date($year) + $DAY*50);
					break;
				}

			if ($r_date)
				{
				$return[$r_date] = bab_translate($t_type[$type]);

				$db->db_query("INSERT INTO ".BAB_SITES_NONWORKING_DAYS_TBL." (id_site,nw_day,nw_type) VALUES ('".$id_site."', '".$r_date."', '".$t_type[$type]."')");
				}
			}
		}

	return $return;
}


function bab_emptyNonWorkingDays($id_site = false)
	{
	if (!$id_site) $id_site = & $GLOBALS['babBody']->babsite['id'];
	$db = & $GLOBALS['babDB'];

	$db->db_query("DELETE FROM ".BAB_SITES_NONWORKING_DAYS_TBL." WHERE id_site='".$id_site."'");
	}

?>