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
require_once $GLOBALS['babInstallPath'].'utilit/eventnotifyincl.php';






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
	
	/**
	 * Add calendar to accessible calendar list
	 * personal calendar is get from backend witout control on the actif flag because the personal calendar can be used in a disabled state by vacation program
	 */
	public function addCalendar(bab_EventCalendar $calendar)
	{
		// do not add calendar if disabled in ovidentia
		if (!$calendar->canView())
		{
			return;
		}
		
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
			$personal_calendar->setAccessUser($access_user);
			$event->addCalendar($personal_calendar);
		}
		
	}
	
	
	if('Ovi' !== $calendar_backend || $personal_calendar || $babBody->babsite['iPersonalCalendarAccess'] == 'Y')
	{
		$arr = $backend->getAccessiblePersonalCalendars($access_user, 'personal');
		
		foreach( $arr as $id_user)
		{
			$calendar = $backend->PersonalCalendar($id_user);
			$calendar->setAccessUser($access_user);
			$event->addCalendar($calendar);
		}
	}
}
















/**
 * Event for actions on calendar events
 * Store additional informations for registered targets
 * each target can add informed users, the next targets will not inform the allready informed recipients
 * 
 * @package events
 * @since 7.4.0
 */
class bab_eventCalendarEvent extends bab_event implements bab_eventNotifyRecipients
{
	private $informed_recipients = array();
	
	/**
	 * @var bab_CalendarPeriod
	 */
	private $period = null;
	
	/**
	 * The list of calendars to notify
	 * @var array
	 */
	private $calendars = array();


	/**
	 * Add a user to informed user list after a user has been informed about the action
	 * @param int $id_user
	 * @return bab_eventFmFile
	 */
	public function addInformedUser($id_user)
	{
		$this->informed_recipients[$id_user] = $id_user;
		return $this;
	}
	
	/**
	 * Set period to notify
	 * @param bab_CalendarPeriod $period
	 * @return bab_eventCalendarEvent
	 */
	public function setPeriod(bab_CalendarPeriod $period)
	{
		$this->period = $period;
		$collection = $period->getCollection();
		
		return $this;
	}
	
	/**
	 * 
	 * @param bab_CalendarPeriod $calendar
	 * @return bab_eventCalendarEvent
	 */
	public function addCalendar(bab_EventCalendar $calendar)
	{
		$this->calendars[] = $calendar;
		return $this;
	}
	
	
	public function getPeriod()
	{
		return $this->period;
	}
	
	/**
	 * 
	 * @return array
	 */
	public function getCalendars()
	{
		return $this->calendars;
	}
	
	
	/**
	 * Get user to notify based on event and access rights
	 * 
	 * @param bab_EventCalendar $calendar
	 * @return array
	 */
	public function getUsersToNotifyByCalendar(bab_EventCalendar $calendar)
	{
		include_once dirname(__FILE__).'/userinfosincl.php';
		include_once dirname(__FILE__).'/../admin/acl.php';
		
		
		switch(true)
		{
		case $calendar instanceof bab_PersonalCalendar:

			$row = bab_userInfos::getRow($calendar->getIdUser());

			$users = array(
				$calendar->getIdUser() => array(
					'name' 		=> bab_composeUserName($row['firstname'], $row['lastname']),
					'firstname'	=> $row['firstname'], 
					'lastname'	=> $row['lastname'],
					'email'		=> $row['email']
				)
			);
			
			break;
			
		case $calendar instanceof bab_PublicCalendar:
			$users = aclGetAccessUsers(BAB_CAL_PUB_GRP_GROUPS_TBL, $calendar->getUid());
			break;
			
		case $calendar instanceof bab_ResourceCalendar:
			$users = aclGetAccessUsers(BAB_CAL_RES_GRP_GROUPS_TBL, $calendar->getUid());
			break;
		}
		
		// add organizer if in ovidentia
		
		if ($organizer = $this->period->getOrganizer())
		{
			// try to match organizer to an ovidentia user
			
			if (isset($organizer['name']))
			{
				$id_user = bab_getUserIdByEmailAndName($organizer['email'], $organizer['name']);
				
				if ($id_user && !isset($users[$id_user]))
				{
					$row = bab_userInfos::getRow($id_user);
			
					$users[$id_user] = array(
						'name' 		=> bab_composeUserName($row['firstname'], $row['lastname']),
						'firstname'	=> $row['firstname'], 
						'lastname'	=> $row['lastname'],
						'email'		=> $row['email']
					);
				}
			}
		}
		
		

		
		if (isset($users[$GLOBALS['BAB_SESS_USERID']]))
		{
			// do not notify current user
			unset($users[$GLOBALS['BAB_SESS_USERID']]);
		}
		
		foreach($users as $id_user => $dummy)
		{
			if (isset($this->informed_recipients[$id_user]))
			{
				unset($users[$id_user]);
			}
		}
		
		return $users;
	}
	
	
	
	/**
	 * Get user to notify based on event and access rights
	 * 
	 * @return array
	 *
	 */
	public function getUsersToNotify()
	{
		
		$users = array();
		
		foreach($this->calendars as $calendar)
		{
			$users += $this->getUsersToNotifyByCalendar($calendar);
		}

		return $users;
	}
}


/**
 * After one calendar event has been made visible for a population of users
 * - event creation
 * - new attendee
 * 
 * @package events
 * @since 7.4.0
 */
class bab_eventAfterEventAdd extends bab_eventCalendarEvent 
{
	
}




/**
 * After one calendar has been updated for a population of users
 * - event delete
 * 
 * @package events
 * @since 7.4.0
 */
class bab_eventAfterEventUpdate extends bab_eventCalendarEvent
{
	
}






/**
 * After one calendar has been deleted for a population of users
 * - event delete
 * 
 * 
 * @package events
 * @since 7.4.0
 */
class bab_eventAfterEventDelete extends bab_eventCalendarEvent
{
	
}

/**
 * Attendee validation accepted
 * 
 * @package events
 * @since 7.4.0
 */
class bab_eventAfterEventRelationAdd extends bab_eventCalendarEvent
{
	
}

/**
 * Attendee validation rejected
 * 
 * @package events
 * @since 7.4.0
 */
class bab_eventAfterEventRelationDelete extends bab_eventCalendarEvent
{
	
}





/**
 * bab_eventAfterEventAdd			cal_notify			utilit/evtincl.php 			2027		new relation, new event
 * bab_eventAfterEventDelete		cal_notify			event.php					1236
 * bab_eventAfterEventUpdate		notifyEventUpdate	cal.ovievent.class.php		219			event update, remove attendee
 * 
 * @param bab_eventCalendarEvent $event
 * @return unknown_type
 */
function bab_onCalendarEvent(bab_eventCalendarEvent $event)
{
	
	require_once dirname(__FILE__).'/evtincl.php';
	
	switch(true)
	{
		case $event instanceof bab_eventAfterEventAdd:
			cal_notify($event, bab_translate("New appointement"));
			break;
		
		case $event instanceof bab_eventAfterEventUpdate:
			cal_notify($event, bab_translate("Appointment modifed by ").$GLOBALS['BAB_SESS_USER'], bab_translate("The following appointment has been modified"));
			break;
			
		case $event instanceof bab_eventAfterEventDelete:
			cal_notify($event, bab_translate("An appointement has been removed"));
			break;
		/*	
		case $event instanceof bab_eventAfterEventRelationAdd:
			cal_notify($event, bab_translate("Appointment added by ").$GLOBALS['BAB_SESS_USER'], bab_translate("The following appointment has been added to your calendar"));
			break;
			
		case $event instanceof bab_eventAfterEventRelationDelete:
			cal_notify($event, bab_translate("Appointment canceled by ").$GLOBALS['BAB_SESS_USER'], bab_translate("The following appointment has been canceled"));
			break;
		*/
	}
}