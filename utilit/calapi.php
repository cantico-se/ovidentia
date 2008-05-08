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

/**
* @internal SEC1 PR 20/02/2007 FULL
*/

include_once "base.php";


/**
 * Get information about calendar categories.
 * Returns information for each calendar category in a multi-dimensional array.
 * Each first-level element of the result array is an array with the following structure :
 * - 'id' : category identifier.
 * - 'name' : name of the category.
 * - 'description' : description of the category.
 * - 'color' : background color of the category in hex format (eg. "FF0000").
 * 
 * @return array
 * @access public
 */
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


/**
 * Returns the id of the specified user's personal calendar or 0 on error.
 *
 * @param int $iduser	The user id
 * @return int
 * @access public
 */
function bab_getPersonalCalendar($iduser)
{
	global $babDB;

	$res = $babDB->db_query("select id from ".BAB_CALENDAR_TBL." where owner='".$babDB->db_escape_string($iduser)."' and type='".BAB_CAL_USER_TYPE."'");
	if( $res && $babDB->db_num_rows($res) == 1 )
	{
		$arr = $babDB->db_fetch_array($res);
		return $arr['id'];
	}
	return 0;
}


/**
 * Get all events for a specific calendar and specified dates.
 * Returns detailed information about calendar events in a multi-dimensional array.
 * - 'id_cal' : id of the calendar for which you want to get events. Can be an array of ids.
 * - 'begindate' : start date in ISO format (YYYY-MM-DD)
 * - 'enddate' : end date in ISO format (YYYY-MM-DD)
 * - 'id_category' : (optional) id of a category if you want to fetch only events of this category
 * - 'order' : "asc" for ascending and "desc" for descending.
 *
 * @param array $params
 * @return array
 * @access public
 */
function bab_calGetEvents(&$params)
{
	global $babBody;

	$events = array();

	include_once $GLOBALS['babInstallPath']."utilit/workinghoursincl.php";
	include_once $GLOBALS['babInstallPath']."utilit/dateTime.php";
	
	$whObj = new bab_userWorkingHours(
		BAB_dateTime::fromIsoDateTime($params['begindate']), 
		BAB_dateTime::fromIsoDateTime($params['enddate'])
	);
	

	if( is_array($params['id_cal']) ) {
	
		if (empty($params['id_cal'])) {
			return array();
		}
	
		foreach($params['id_cal'] as $id_cal) {
			$whObj->addCalendar($id_cal);
			$infos = $babBody->icalendars->getCalendarInfo($id_cal);
			$whObj->addIdUser($infos['idowner']);
		}
	} else {
		$whObj->addCalendar($params['id_cal']);
		$infos = $babBody->icalendars->getCalendarInfo($params['id_cal']);
		$whObj->addIdUser($infos['idowner']);
	}
	
	$whObj->createPeriods(BAB_PERIOD_VACATION | BAB_PERIOD_CALEVENT);
	$whObj->orderBoundaries();
	
	while ($event = $whObj->getNextEvent(BAB_PERIOD_VACATION | BAB_PERIOD_CALEVENT)) {
	
		$data = $event->getData();
		
		if (isset($params['id_category'])) {
			if (is_array($params['id_category'])) {
				if (!in_array($data['id_cat'], $params['id_category'])) {
					continue;
				}
			} elseif (((int) $data['id_cat']) !== ((int) $params['id_category'])) {
				continue;
			}
		}
		
		if (isset($data['confirmed']) && false === $data['confirmed']) {
			continue;
		}
		
		$events[] = array(
			'uid'					=> $data['uuid'],
			'id_event' 				=> isset($data['id_event']) ? $data['id_event'] : NULL,		
			'title'					=> $event->getProperty('SUMMARY'),
			'description'			=> $event->getProperty('DESCRIPTION'),
			'location'				=> $event->getProperty('LOCATION'),
			'begindate'				=> $event->getProperty('DTSTART'),
			'enddate'				=> $event->getProperty('DTEND'),
			'quantity'				=> isset($data['quantity']) ? $data['quantity'] : NULL,
			'id_category'			=> isset($data['id_cat']) 	? $data['id_cat'] 	: NULL,
			'name_category' 		=> $event->getProperty('CATEGORIES'),
			'description_category'	=> isset($data['category_description']) ? $data['category_description'] : NULL,
			'id_creator'			=> isset($data['id_creator']) ? $data['id_creator'] : NULL,
			'backgroundcolor'		=> $event->color,
			'private'				=> 'PRIVATE' === $event->getProperty('CLASS'),
			'lock'					=> isset($data['block']) && 'Y' === $data['block'],
			'free'					=> isset($data['bfree']) && 'Y' === $data['bfree'],
			'status'				=> isset($data['status']) ? $data['status'] : NULL,
			'id_calendar'			=> isset($data['id_cal']) ? $data['id_cal'] : NULL
		);
	}
	
	return $events;
	
		
	
}

/**
 * Returns information for each resource calendar in a multi-dimensional array.
 * Each first-level element of the result array is an array with the following structure:
 * - ['id']             the calendar identifier.
 * - ['name']           name of the calendar.
 * - ['description']    description of the calendar.
 * - ['rights']['view']	true if the current user is allowed to view this calendar.
 * - ['rights']['add']  true if the current user is allowed to add events to this calendar.
 * 
 * @return array
 * @access public
 */
function bab_getResourceCalendars()
{
	global $babBody, $babDB;
	$rescals = array();

	$res = $babDB->db_query("select cpt.*, ct.id as idcal from ".BAB_CAL_RESOURCES_TBL." cpt left join ".BAB_CALENDAR_TBL." ct on ct.owner=cpt.id where ct.actif='Y' and  ct.type='".BAB_CAL_RES_TYPE."' and id_dgowner='".$babDB->db_escape_string($babBody->currentAdmGroup)."' ORDER BY cpt.name");
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


/**
 * Returns information for each public calendar in a multi-dimensional array.
 * Each first-level element of the result array is an array with the following structure:
 * - ['id']             the calendar identifier.
 * - ['name']           name of the calendar.
 * - ['description']    description of the calendar.
 * - ['rights']['view']	true if the current user is allowed to view this calendar.
 * - ['rights']['add']  true if the current user is allowed to add events to this calendar.
 * 
 * @return array
 * @access public
 */
function bab_getPublicCalendars()
{
	global $babBody, $babDB;
	$rescals = array();

	$res = $babDB->db_query("select cpt.*, ct.id as idcal from ".BAB_CAL_PUBLIC_TBL." cpt left join ".BAB_CALENDAR_TBL." ct on ct.owner=cpt.id where ct.actif='Y' and ct.type='".BAB_CAL_PUB_TYPE."' and id_dgowner='".$babDB->db_escape_string($babBody->currentAdmGroup)."' ORDER BY cpt.name");
	while( $arr = $babDB->db_fetch_array($res))
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


/**
 * Create new event
 * @see bab_createEvent
 * 
 * @param	array	$idcals
 * @param	array	$args
 * @param	string	&$msgerror		empty string
 */
function bab_newEvent($idcals, $args, &$msgerror)
{
	$args['selected_calendars'] = $idcals;

	include_once $GLOBALS['babInstallPath'].'utilit/evtincl.php';
	return bab_createEvent($args, $msgerror);
}

function bab_deleteEventById( $evtid )
{
	include_once $GLOBALS['babInstallPath'].'utilit/evtincl.php';
	return bab_deleteEvent($evtid);
}

function bab_emptyCalendar( $idcal )
{
	global $babDB;
	include_once $GLOBALS['babInstallPath']."utilit/evtincl.php";

	$res = $babDB->db_query("select id_event from ".BAB_CAL_EVENTS_OWNERS_TBL." where id_cal='".$babDB->db_escape_string($idcal)."'");

	while( $rr = $babDB->db_fetch_array($res) )
		{
		bab_deleteEvent($rr['id_event']);
		}

}


/**
 * @param int		$iIdUser
 * @param string	&$sWorkingDays
 */
function bab_calGetWorkingDays($iIdUser, &$sWorkingDays)
{

	include_once $GLOBALS['babInstallPath']."utilit/workinghoursincl.php";

	function day_push(&$arr, $iIdUser, $day) {
		$tmp = bab_getWHours($iIdUser, $day);
		if ($tmp) {
			$arr[] = $day;
		}
	}

	$arr = array();

	day_push($arr, $iIdUser, 0);
	day_push($arr, $iIdUser, 1);
	day_push($arr, $iIdUser, 2);
	day_push($arr, $iIdUser, 3);
	day_push($arr, $iIdUser, 4);
	day_push($arr, $iIdUser, 5);
	day_push($arr, $iIdUser, 6);

	$sWorkingDays = implode(',',$arr);
}

?>