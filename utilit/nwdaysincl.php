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


if (!function_exists('easter_date'))
{

	function easter_date($Year) {
	  
		 $G = $Year % 19;
		 $C = (int)($Year / 100);
		 $H = (int)($C - (int)($C / 4) - (int)((8*$C+13) / 25) + 19*$G + 15) % 30;
		 $I = (int)$H - (int)($H / 28)*(1 - (int)($H / 28)*(int)(29 / ($H + 1))*((int)(21 - $G) / 11));
		 $J = ($Year + (int)($Year/4) + $I + 2 - $C + (int)($C/4)) % 7;
		 $L = $I - $J;
		 $m = 3 + (int)(($L + 40) / 44);
		 $d = $L + 28 - 31 * ((int)($m / 4));
		 $y = $Year;
		 $E = mktime(0,0,0, $m, $d, $y);

		 return $E;
	   } 
}


function bab_getNonWorkingDayTypes($with_date = false)
{
	$arr[101] = $with_date ? bab_translate("Day") : bab_translate("Non-working day");
	$arr[102] = $with_date ? bab_translate("Repeat yearly") : bab_translate("Non-working day");

	$arr[1] = bab_translate("Easter");
	$arr[2] = bab_translate("Ascension");
	$arr[3] = bab_translate("Pentecost");

	return $arr;
}


function bab_setNonWorkingDays($year)
{
	$return = array();
	$db = & $GLOBALS['babDB'];
	$t_type = bab_getNonWorkingDayTypes();
	$DAY = 86400;
	$id_site = & $GLOBALS['babBody']->babsite['id'];

	$res = $db->db_query("SELECT nw_day,nw_type,nw_text FROM ".BAB_SITES_NONWORKING_CONFIG_TBL." WHERE id_site='".$id_site."'");

	if ($db->db_num_rows($res) == 0)
		return array();

	while( list($day,$type,$text) = $db->db_fetch_array($res) )
		{
		$r_date = false;

		switch ($type)
			{
			case 101:
				list($d,$m,$y) = explode('-',$day);
				if ($y == $year)
					{
					$r_date = sprintf("%04s-%02s-%02s", $y, $m, $d);
					$nw_type = empty($text) ? 'Non-working day' : $text;
					}
				break;

			case 102:
				list($d,$m) = explode('-',$day);
				$r_date = sprintf("%04s-%02s-%02s", $year, $m, $d);
				$nw_type = empty($text) ? 'Non-working day' : $text;
				break;

			case 1:
				$r_date = date("Y-m-d", easter_date($year) + $DAY*1);
				$nw_type = 'Easter';
				break;

			case 2:
				$r_date = date("Y-m-d", easter_date($year) + $DAY*39);
				$nw_type = 'Ascension';
				break;

			case 3:
				$r_date = date("Y-m-d", easter_date($year) + $DAY*50);
				$nw_type = 'Pentecost';
				break;
			}

		if ($r_date)
			{
			$return[$r_date] = bab_translate($nw_type);

			$db->db_query("INSERT INTO ".BAB_SITES_NONWORKING_DAYS_TBL." (id_site,nw_day,nw_type) VALUES ('".$id_site."', '".$r_date."', '".$db->db_escape_string($nw_type)."')");
			}
		}
	return $return;
}


function bab_getNonWorkingDays($year)
{
	$db = & $GLOBALS['babDB'];
	
	$id_site = & $GLOBALS['babBody']->babsite['id'];
	
	$res = $db->db_query("SELECT nw_day,nw_type FROM ".BAB_SITES_NONWORKING_DAYS_TBL." WHERE id_site='".$id_site."' AND YEAR(nw_day) = '".$year."'");

	if ($db->db_num_rows($res) > 0)
		{
		$return = array();
		while( list($day,$type) = $db->db_fetch_array($res) )
			{
			$return[$day] = bab_translate($type);
			}
		}
	else
		{
		$return = bab_setNonWorkingDays($year);
		}

	return $return;
}


function bab_getNonWorkingDaysBetween($from, $to)
{
	include_once $GLOBALS['babInstallPath']."utilit/nwdaysincl.php";

	if (is_int($from))
		{
		$y_from   = date('Y',$from);
		$y_to     = date('Y',$to);
		$date_col = 'UNIX_TIMESTAMP(nw_day) nw_day';
		$from     = date('Y-m-d',$from);
		$to       = date('Y-m-d',$to);
		}
	else
		{
		list($y_from) = explode('-',$from);
		list($y_to)   = explode('-',$to);
		$date_col     = 'nw_day';
		}

	$db = & $GLOBALS['babDB'];
	$id_site = & $GLOBALS['babBody']->babsite['id'];
	$result = array();

	for($year = $y_from; $year<= $y_to; $year++)
		{
		$res = $db->db_query("SELECT * FROM ".BAB_SITES_NONWORKING_DAYS_TBL." WHERE id_site='".$id_site."' AND YEAR(nw_day) = '".$year."'");
		if ($db->db_num_rows($res) == 0)
			{
			bab_setNonWorkingDays($year);
			}
		}

	$res = $db->db_query("SELECT ".$date_col.", nw_type FROM ".BAB_SITES_NONWORKING_DAYS_TBL." WHERE id_site='".$id_site."' AND nw_day BETWEEN '".$from."' AND '".$to."' ORDER BY nw_day");
	while ($arr = $db->db_fetch_assoc($res))
		{
		$result[$arr['nw_day']] = bab_translate($arr['nw_type']);
		}

	return $result;
}


function bab_emptyNonWorkingDays($id_site = false)
	{
	if (!$id_site) $id_site = & $GLOBALS['babBody']->babsite['id'];
	$db = & $GLOBALS['babDB'];

	$db->db_query("DELETE FROM ".BAB_SITES_NONWORKING_DAYS_TBL." WHERE id_site='".$id_site."'");
	}

?>