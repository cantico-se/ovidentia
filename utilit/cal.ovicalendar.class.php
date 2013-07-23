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
 * Select public calendars
 * 
 * @param	int		$access_user		id user for accesss rights texting in calendar
 * @param	array	$calendars			array of id_calendar
 * 
 * @return array
 */
function bab_cal_getPublicCalendars($access_user, $calendars = null)
{
	global $babDB;
	
	$backend = bab_functionality::get('CalendarBackend/Ovi');
	/*@var $backend Func_CalendarBackend_Ovi */
	
	$query = "
		select cpt.*, ct.id as idcal
		from 
			".BAB_CAL_PUBLIC_TBL." cpt 
				left join ".BAB_CALENDAR_TBL." ct on ct.owner=cpt.id 
		where 
			ct.type='".BAB_CAL_PUB_TYPE."' 
			AND ct.actif='Y' 
	";
	
	if (null === $calendars)
	{
		$visible_public_cal = bab_getAccessibleObjects(BAB_CAL_PUB_VIEW_GROUPS_TBL, $access_user);
		$query .= " AND ct.id IN(".$babDB->quote($visible_public_cal).")";
	} else 
	{
		$query .= " AND ct.id IN(".$babDB->quote($calendars).")";
	}
	
	$res = $babDB->db_query($query);
	
	$return = array();
	while( $arr = $babDB->db_fetch_assoc($res))
	{	
		$calendar = $backend->PublicCalendar();
		$calendar->init($access_user, $arr);
		$return[] = $calendar;
	}
	
	return $return;
}
	



	
/**
 * Select resource calendars
 * 
 * @param	int		$access_user		id user for accesss rights texting in calendar
 * @param	array	$calendars			array of id_calendar
 * 
 * @return array
 */
function bab_cal_getResourceCalendars($access_user, $calendars = null)
{
	global $babDB;
	
	$backend = bab_functionality::get('CalendarBackend/Ovi');
	/*@var $backend Func_CalendarBackend_Ovi */
	
	

	$query = "
		select crt.*, ct.id as idcal 
		from 
			".BAB_CAL_RESOURCES_TBL." crt 
				left join ".BAB_CALENDAR_TBL." ct on ct.owner=crt.id 
		where 
			ct.type='".BAB_CAL_RES_TYPE."' 
			and ct.actif='Y' 
	";
	
	if (null === $calendars)
	{
		$visible_resource_cal = bab_getAccessibleObjects(BAB_CAL_RES_VIEW_GROUPS_TBL, $access_user);
		$query .= " AND ct.id IN(".$babDB->quote($visible_resource_cal).")";
	} else 
	{
		$query .= " AND ct.id IN(".$babDB->quote($calendars).")";
	}
	
	$res = $babDB->db_query($query);
	$return = array();
	
	while($arr = $babDB->db_fetch_assoc($res))
	{
		$calendar = $backend->ResourceCalendar();
		$calendar->init($access_user, $arr);
		$return[] = $calendar;
	}
	
	
	return $return;
}

	
	

/**
 * Select personal calendars
 * 
 * @param	int		$access_user		id user for access rights testing in calendar
 * @param	array	$calendars			array of id_calendar
 * 
 * @return array
 */
function bab_cal_getPersonalCalendars($access_user, $calendars = null)
{
	require_once dirname(__FILE__).'/cal.eventcalendar.class.php';
	require_once dirname(__FILE__).'/userinfosincl.php';
	
	global $babDB;	

	$query = "
		select 
			ct.owner id_user, 
			o.calendar_backend  

		from ".BAB_CALENDAR_TBL." ct
			,".BAB_USERS_TBL." u
			,bab_cal_user_options o 
		where 
			u.id=ct.owner 
			and ct.actif='Y' 
			and ".bab_userInfos::queryAllowedUsers('u')."  
			AND u.id=o.id_user 
	";
	$res = $babDB->db_query($query);
	
	if (null === $calendars)
	{
		$query .= " ct.owner=".$babDB->db_quote($access_user);
	} else 
	{
		$query .= " AND ct.id IN(".$babDB->quote($calendars).")";
	}

	$res = $babDB->db_query($query);
	
	$return = array();
	while( $arr = $babDB->db_fetch_assoc($res))
	{
		$id_user = (int) $arr['id_user'];
		$backendName = empty($arr['calendar_backend']) ? 'Ovi' : $arr['calendar_backend'];
		
		$backend = bab_functionality::get('CalendarBackend/'.$backendName);
		/*@var $backend Func_CalendarBackend */ 
		
		
		if (!$backend)
		{
			continue;
		}

		$calendar = $backend->PersonalCalendar($id_user);
		
		if (!$calendar)
		{
			continue;
		}
		
		$return[] = $calendar;
	}

	return $return;
}