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
 * @param int or array $ids	Id of the category
* @return array
 * @access public
 */
function bab_calGetCategories($ids = null)
{
	global $babDB;

	$categ = array();
	if( $ids !== null )
	{
		if( !is_array($ids))
		{
			$ids = array($ids);
		}
		$res = $babDB->db_query("SELECT * FROM ".BAB_CAL_CATEGORIES_TBL." where id = ".$babDB->quote($ids)." ORDER BY name");
	}
	else
	{
		$res = $babDB->db_query("SELECT * FROM ".BAB_CAL_CATEGORIES_TBL." ORDER BY name");
	}
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
	return (int) bab_getICalendars()->getPersonalCalendarUid($iduser);
}


/**
 * Get all events for a specific calendar and specified dates.
 * Returns detailed information about calendar events in a multi-dimensional array.
 *
 * - 'id_cal' 			: id of the calendar for which you want to get events. Can be an array of ids.
 * - 'begindate' 		: start date in ISO format (YYYY-MM-DD)
 * - 'enddate' 			: end date in ISO format (YYYY-MM-DD)
 * - 'id_category' 		: (optional) id of a category if you want to fetch only events of this category
 * - 'order' 			: "asc" for ascending and "desc" for descending.
 * - 'access_control'	: boolean, if access_control is true, the access to calendars is validated before returning the events, the default is TRUE
 *
 * @param array $params
 * @return array
 *
 * @access public
 * 
 * @deprecated
 *
 */
function bab_calGetEvents(&$params)
{
	trigger_error('Deprecated');
	
	return array();
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

	$res = $babDB->db_query("select cpt.*, ct.id as idcal from ".BAB_CAL_RESOURCES_TBL." cpt left join ".BAB_CALENDAR_TBL." ct on ct.owner=cpt.id where ct.actif='Y' and  ct.type='".BAB_CAL_RES_TYPE."' and id_dgowner='".$babDB->db_escape_string(bab_getCurrentAdmGroup())."' ORDER BY cpt.name");
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

	$res = $babDB->db_query("select cpt.*, ct.id as idcal from ".BAB_CAL_PUBLIC_TBL." cpt left join ".BAB_CALENDAR_TBL." ct on ct.owner=cpt.id where ct.actif='Y' and ct.type='".BAB_CAL_PUB_TYPE."' and id_dgowner='".$babDB->db_escape_string(bab_getCurrentAdmGroup())."' ORDER BY cpt.name");
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
 * Create a new event or updates an existing one.
 * In case of an update $updateMethod must be specified.
 * 
 * @param	array	$idcals
 * @param	array	$args
 * array(
 * 	'evtid' =>
 * 	'calid' =>
 * 	'dtstart' =>
 * 	'title' =>
 * 	'location' =>
 *	'category' =>
 *	'color' =>
 *	'yearbegin' =>
 *	'monthbegin' =>
 *	'daybegin' =>
 *	'timebegin' =>
 *	'yearend' =>
 *	'monthend' =>
 *	'dayend' =>
 *	'timeend' =>
 *	'bprivate' =>
 *	'block' =>
 *	'bfree' =>
 *	'event_owner' =>
 *	'repeat_cb' =>
 *	'repeat_yearend' =>
 *	'repeat_monthend' =>
 *	'repeat_dayend' =>
 *	'repeat' =>
 *	'repeat_n_1' =>
 *	'repeat_n_2' =>
 *	'repeat_n_3' =>
 *	'repeat_n_4' =>
 *	'repeat_wd' =>
 *	'creminder' =>
 * 	'rday' =>		reminder day of month
 * 	'rhour' =>		reminder hour
 * 	'rminute' =>	reminder minute
 * 	'remail' =>
 * 	'selected_calendars' => array()
 * 	'description' => 
 * 	'descriptionformat' =>
 * )
 * @param	string	&$msgerror		empty string
 * @param	int		$updateMethod				BAB_CAL_EVT_ALL | BAB_CAL_EVT_CURRENT | BAB_CAL_EVT_PREVIOUS | BAB_CAL_EVT_NEXT
 * 
 * @return  bool		True in case of success, false if the event could not be created/updated.
 * 						In this case $msgerror will contain a translated error message.
 */
function bab_saveEvent($idcals, $args, &$msgerror, $updateMethod = null)
{
	include_once $GLOBALS['babInstallPath'].'utilit/evtincl.php';

	$posted = new bab_event_posted();
	
	$args['selected_calendars'] = $idcals;
	$posted->createArgsData($args);
	if (!$posted->isValid($msgerror)) {
		return false;
	}

	// if period is available
	if ($posted->availabilityCheckAllEvents($msgerror)) {
		return $posted->save($msgerror);
	}
	
	
	// if availability message displayed and the event is submited
	if (isset($args['availability_displayed']) && !isset($args['test_conflicts'])) {
		
		// if availability is NOT mandatory
		if (!bab_event_posted::availabilityIsMandatory($posted->args['selected_calendars'])) {
			return $posted->save($msgerror);
		}
	}

	return false;
}


/**
 * Create new event
 * 
 * @param	array	$idcals
 * @param	array	$args
 * @param	string	&$msgerror		empty string
 * 
 * @deprecated		You should probably use @see bab_saveEvent instead.
 */	
function bab_newEvent($idcals, $args, &$msgerror)
{
	$args['selected_calendars'] = $idcals;

	include_once $GLOBALS['babInstallPath'].'utilit/evtincl.php';
	include_once $GLOBALS['babInstallPath'].'utilit/cal.ovievent.php';
	
	$calendar = bab_getMainCalendar($idcals);

	$backend = $calendar->getBackend();
	
	$collection = $backend->CalendarEventCollection($calendar);
	
	$period = bab_createCalendarPeriod($backend, $args, $collection);
	
	
	
	if ($backend->savePeriod($period))
	{
		$period->commitEvent();
		return true;
	}
	
	return false;
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

function day_push(&$arr, $iIdUser, $day) {
	$tmp = bab_getWHours($iIdUser, $day);
	if ($tmp) {
		$arr[] = $day;
	}
}

/**
 * @param int		$iIdUser
 * @param string	&$sWorkingDays
 */
function bab_calGetWorkingDays($iIdUser, &$sWorkingDays)
{
	include_once $GLOBALS['babInstallPath']."utilit/workinghoursincl.php";

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







/**
 * change personnal calendar backend without verification
 * return true if backend changed, false if allready set
 * 
 * @param	int						$id_user
 * @param	Func_CalendarBackend	$new_backend
 * 
 * @return bool
 */
function bab_setPersonnalCalendarBackend($id_user, Func_CalendarBackend $new_backend)
{
	require_once dirname(__FILE__).'/install.class.php';
	global $babDB;
	
	$old_calendar = bab_getICalendars()->getPersonalCalendar();
	
	if (!($old_calendar instanceof bab_PersonalCalendar))
	{
		throw new Exception('Personal calendar not available on old backend');
	}
	
	$new_calendar = $new_backend->PersonalCalendar($id_user);
	$calendar_backend = $new_calendar->getBackend()->getUrlIdentifier();
	
	if ($calendar_backend === $old_calendar->getUrlIdentifier())
	{
		return false;
	}


	bab_installWindow::message(bab_translate('Update events where i am an attendee'));

	// update all events with links to this personal calendar

	$babDB->db_query('UPDATE '.BAB_CAL_EVENTS_OWNERS_TBL."
		SET
			calendar_backend=".$babDB->quote($calendar_backend).",
			caltype=".$babDB->quote($new_calendar->getReferenceType())."
		where

			calendar_backend=".$babDB->quote(bab_getICalendars()->calendar_backend)."
			AND caltype=".$babDB->quote($old_calendar->getReferenceType())."
			AND id_cal=".$babDB->quote($old_calendar->getUid())."
	");

	bab_installWindow::message(bab_translate('Update my calendar sharing access'));


	// update all sharing access

	$babDB->db_query('UPDATE '.BAB_CALACCESS_USERS_TBL."
		SET
			caltype=".$babDB->quote($new_calendar->getReferenceType())."
		where
			caltype=".$babDB->quote($old_calendar->getReferenceType())."
			AND id_cal=".$babDB->quote($old_calendar->getUid())."
			");


	$babDB->db_query('UPDATE '.BAB_CAL_USER_OPTIONS_TBL."
		SET calendar_backend=".$babDB->quote($calendar_backend)."
		where id_user=".$babDB->quote($id_user)
	);
	
	
	return true;
}

