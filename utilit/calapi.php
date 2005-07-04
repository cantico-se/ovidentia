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

function bab_calGetCategories()
{
	global $babDB;

	$categ = array();

	$res = $babDB->db_query("SELECT * FROM ".BAB_CAL_CATEGORIES_TBL." ORDER BY name");
	while($arr = $babDB->db_fetch_array($res))
	{
		$categ[] = array('id' => $arr['id'], 'name' => $arr['name'], 'description' => $arr['description'], 'color' => $arr['bgcolor']);
	}

	return $categ;
}


function bab_getPersonalCalendar($iduser)
{
	global $babDB;

	$res = $babDB->db_query("select id from ".BAB_CALENDAR_TBL." where owner='".$iduser."' and type='".BAB_CAL_USER_TYPE."'");
	if( $res && $babDB->db_num_rows($res) == 1 )
	{
		$arr = $babDB->db_fetch_array($res);
		return $arr['id'];
	}
	return 0;
}


/* params: id_cal, begindate, enddate, id_category, order=asc/desc */ 
function bab_calGetEvents(&$params)
{
	global $babDB;

	$events = array();

	if( is_array($params['id_cal']) )
		{
		$cals = count($params['id_cal']) > 0 ? implode(',', $params['id_cal']): '';
		}
	else
		{
		$cals = $params['id_cal'];
		}

	if( empty($cals))
		{
		return $events;
		}

	$req = "select ce.*, cc.name as cat_name, cc.description as cat_desc, cc.bgcolor as cat_color, ceo.status as status, ceo.id_cal as id_calendar from ".BAB_CAL_EVENTS_TBL." ce left join ".BAB_CAL_EVENTS_OWNERS_TBL." ceo on ceo.id_event=ce.id left join ".BAB_CAL_CATEGORIES_TBL." cc on cc.id=ce.id_cat where ceo.id_cal='".$cals."' and ceo.status != '".BAB_CAL_STATUS_DECLINED."' and ce.start_date < '".$params['enddate']."' and  ce.end_date > '".$params['begindate']."'";

	if( isset($params['id_category']))
		{
		if( is_array($params['id_category']) && count($params['id_category']) > 0 )
			{
			$req.= " and ce.id_cat in (".implode(',', $params['id_category']).")";
			}
		else
			{
			$req.= " and ce.id_cat='".$params['id_category']."'";
			}
		}

	$req .= " order by ce.start_date ".$params['order'];

	$res = $babDB->db_query($req);
	while( $arr = $babDB->db_fetch_array($res))
	{
		if( !empty($arr['hash']) && $arr['hash'][0] == 'V' )
			{
			list($quantity) = $babDB->db_fetch_row($babDB->db_query("select sum(quantity) from ".BAB_VAC_ENTRIES_ELEM_TBL." where id_entry ='".substr($arr['hash'], 2)."'"));
			}
		else
			{
			$quantity = 0;
			}

		$tmp = array();
		$tmp['id_event'] = $arr['id'];
		$tmp['title'] = $arr['title'];
		$tmp['description'] = $arr['description'];
		$tmp['location'] = $arr['location'];
		$tmp['begindate'] = $arr['start_date'];
		$tmp['enddate'] = $arr['end_date'];
		if( $quantity)
			{
			$tmp['quantity'] = $quantity;
			}
		$tmp['id_category'] = $arr['id_cat'];
		$tmp['name_category'] = $arr['cat_name'];
		$tmp['description_category'] = $arr['cat_desc'];
		$tmp['id_creator'] = $arr['id_creator'];
		$tmp['backgroundcolor'] = $arr['color'] != '' ? $arr['color']: $arr['cat_color'];
		$tmp['private'] = $arr['bprivate'] ==  'Y'? true: false;
		$tmp['lock'] = $arr['block'] ==  'Y'? true: false;
		$tmp['free'] = $arr['bfree'] ==  'Y'? true: false;
		$tmp['status'] = $arr['status'];
		$tmp['id_calendar'] = $arr['id_calendar'];
		$events[] = $tmp;
	}

	return $events;
}


function bab_getResourceCalendars()
{
	global $babBody, $babDB;
	$rescals = array();

	$res = $babDB->db_query("select cpt.*, ct.id as idcal from ".BAB_CAL_RESOURCES_TBL." cpt left join ".BAB_CALENDAR_TBL." ct on ct.owner=cpt.id where ct.actif='Y' and  ct.type='".BAB_CAL_RES_TYPE."' and id_dgowner='".$babBody->currentAdmGroup."' ORDER BY cpt.name");
	while( $arr = $babDB->db_fetch_array($res))
		{
		$tmp = array();
		$tmp['id'] = $arr['idcal'];
		$tmp['name'] = $arr['name'];
		$tmp['description'] = $arr['description'];
		if( bab_isAccessValid(BAB_CAL_RES_MAN_GROUPS_TBL, $arr['idcal']) || bab_isAccessValid(BAB_CAL_RES_ADD_GROUPS_TBL, $arr['idcal']))
			{
			$tmp['rights']['add'] = true;
			}
		else
			{
			$tmp['rights']['add'] = false;
			}
		if( bab_isAccessValid(BAB_CAL_RES_VIEW_GROUPS_TBL, $arr['idcal']))
			{
			$tmp['rights']['view'] = true;
			}
		else
			{
			$tmp['rights']['view'] = false;
			}
		$rescals[] = $tmp;
		}

	return $rescals;
}

function bab_getPublicCalendars()
{
	global $babBody, $babDB;
	$rescals = array();

	$res = $babDB->db_query("select cpt.*, ct.id as idcal from ".BAB_CAL_PUBLIC_TBL." cpt left join ".BAB_CALENDAR_TBL." ct on ct.owner=cpt.id where ct.actif='Y' and ct.type='".BAB_CAL_PUB_TYPE."' and id_dgowner='".$babBody->currentAdmGroup."' ORDER BY cpt.name");
		{
		$tmp = array();
		$tmp['id'] = $arr['idcal'];
		$tmp['name'] = $arr['name'];
		$tmp['description'] = $arr['description'];
		if( bab_isAccessValid(BAB_CAL_PUB_MAN_GROUPS_TBL, $arr['idcal']))
			{
			$tmp['rights']['add'] = true;
			}
		else
			{
			$tmp['rights']['add'] = false;
			}
		if( bab_isAccessValid(BAB_CAL_PUB_VIEW_GROUPS_TBL, $arr['idcal']))
			{
			$tmp['rights']['view'] = true;
			}
		else
			{
			$tmp['rights']['view'] = false;
			}
		$rescals[] = $tmp;
		}

	return $rescals;
}

/* idcals array, $date0 and $date1 sql dates, $gap in seconds, $bopt='Y' if you want to use user's options */
function bab_getFreeEvents($idcals, $sdate, $edate, $gap, $bopt)
{
	include_once $GLOBALS['babInstallPath'].'utilit/mcalincl.php';
	return cal_getFreeEvents($idcals, $sdate, $edate, $gap, $bopt);
}


function bab_newEvent($idcals, $args, &$msgerror)
{
	include_once $GLOBALS['babInstallPath'].'utilit/evtincl.php';
	return bab_createEvent($idcals,$args, $msgerror);
}
?>