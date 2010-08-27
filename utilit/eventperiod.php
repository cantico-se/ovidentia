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
require_once $GLOBALS['babInstallPath'].'utilit/eventincl.php';







/**
 * Event used to collect accessible calendars for user to display on interface
 * @package events
 */
class bab_eventCollectCalendarsBeforeDisplay extends bab_event
{

	/**
	 * 
	 * @var bab_icalendars
	 */
	private $calendar_collection;
	
	public function __construct(bab_icalendars $calendar_collection)
	{
		$this->calendar_collection = $calendar_collection;
	}
	
	
	public function addCalendar(bab_EventCalendar $calendar)
	{
		$this->calendar_collection->addCalendar($calendar);
	}
	
	/**
	 * 
	 * @return int
	 */
	public function getAccessUser()
	{
		return $this->calendar_collection->getAccessUser();
	}
	
}







/**
 * Event fired when a calendar is displayed
 * use it to display periods into the calendar
 * all periods will be added to the bab_UserPeriods attached object by the registed functions in respect with the differents filters
 * @since 6.1.0
 * @package events
 */
class bab_eventBeforePeriodsCreated extends bab_event {
	
	/**
 	 * @var bab_UserPeriods
	 */
	public $periods;

	

	/**
 	 * 
	 */
	public function __construct(bab_UserPeriods $periods) {
		$this->periods = $periods;
	}
	
	
	public function getBeginDate()
	{
		return $this->periods->begin;
	}

	public function getEndDate()
	{
		return $this->periods->end;
	}
	
	public function getUsers()
	{
		return $this->periods->getUsers();
	}
	
	/**
	 * Get criteria used for query
	 * @return bab_PeriodCriteria
	 */
	public function getCriteria()
	{
		return $this->periods->getCriteria();
	}
}



/**
 * Event fired when a period is modified
 * @since 6.1.0
 * @package events
 */
class bab_eventPeriodModified extends bab_event {

	/**
 	 * @public
	 */
	var $begin;
	var $end;
	var $id_user;
	var $types;
	
	/**
	 * if the dates are false, the modification has no boundaries
 	 * @param 	int|false 	$begin		timestamp
	 * @param	int|false	$end		timestamp
	 * @param	int|false	$id_user
	 */
	function bab_eventPeriodModified($begin, $end, $id_user) {
		$this->begin 	= $begin;
		$this->end 		= $end;
		$this->id_user	= $id_user;
		$this->types	= BAB_PERIOD_WORKING | BAB_PERIOD_NONWORKING | BAB_PERIOD_NWDAY | BAB_PERIOD_CALEVENT | BAB_PERIOD_TSKMGR | BAB_PERIOD_VACATION;
	}

}









/**
 * Core function registered to collect events
 * @param bab_eventBeforePeriodsCreated $event
 * @return unknown_type
 */
function bab_onBeforePeriodsCreated(bab_eventBeforePeriodsCreated $event)
{
	require_once dirname(__FILE__).'/cal.ovievent.class.php';
	
	$oviEvents = new bab_cal_OviEventSelect;
	$oviEvents->processQuery($event->periods);
}


/**
 * Core function registered to collect calendars
 * add all calendars from ovidentia core to the bab_icalendars collection
 * 
 * @param bab_eventCollectCalendarsBeforeDisplay $event
 * @return unknown_type
 */
function bab_onCollectCalendarsBeforeDisplay(bab_eventCollectCalendarsBeforeDisplay $event)
{
	
	global $babDB, $babBody;
	require_once dirname(__FILE__).'/cal.ovicalendar.class.php';
	
	
	$arr = bab_cal_getPublicCalendars($event->getAccessUser());
	foreach($arr as $calendar)
	{
		$event->addCalendar($calendar);
	}
	
	$arr = bab_cal_getResourceCalendars($event->getAccessUser());
	foreach($arr as $calendar)
	{
		$event->addCalendar($calendar);
	}
	

	$backend = bab_functionality::get('CalendarBackend/Ovi');
	/*@var $backend Func_CalendarBackend_Ovi */

	
	// personal calendars
	
	$personal_calendar = null;
	$access_user = $event->getAccessUser();
	$calendar_backend = bab_getICalendars()->calendar_backend;
	
	if( !empty($access_user) && 'Ovi' === $calendar_backend)
	{
		$personal_calendar = $backend->PersonalCalendar($access_user);
		if ($personal_calendar)
		{
			$event->addCalendar($personal_calendar);
		}
		
	}
	
	
	if('Ovi' !== $calendar_backend || $personal_calendar || $babBody->babsite['iPersonalCalendarAccess'] == 'Y')
	{
		$arr = $backend->getAccessiblePersonalCalendars($access_user, 'personal');
		
		foreach( $arr as $id_user)
		{
			$calendar = $backend->PersonalCalendar($id_user);
			$event->addCalendar($calendar);
		}
	}
}