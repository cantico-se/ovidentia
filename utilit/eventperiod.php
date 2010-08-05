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
 * Event used to collect accessibles calendars for user to display on interface
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
	
	/**
	 * get personal calendar of access user or null if annonymous and no personal calendar
	 * @return bab_PersonalCalendar
	 */
	public function getPersonalCalendar()
	{
		return $this->calendar_collection->getPersonalCalendar();
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
	 * @var array	<string>
	 */
	private $periodCollectionClassNames = null;
	
	
	/**
	 * @var array
	 */
	private $icalProperties = null;
	
	
	/**
	 * @var array	<bab_EventCalendar>
	 */
	private $calendars = null;


	/**
 	 * 
	 */
	public function __construct(bab_UserPeriods $periods) {
		$this->periods = $periods;
	}
	
	/**
	 * Add a filter by classname of event collection 
	 * @param array $periodCollectionClassNames <string>
	 * @return bab_eventCollectPeriodsBeforeDisplay
	 */
	public function filterByPeriodCollection(array $periodCollectionClassNames) {
		$this->periodCollectionClassNames = $periodCollectionClassNames;
		return $this;
	}
	
	/**
	 * Add a filter by classname of event collection
	 * @param string $periodCollectionClassName
	 * @return bab_eventCollectPeriodsBeforeDisplay
	 */
	public function addFilterByPeriodCollection($periodCollectionClassName) {
		if (!isset($this->periodCollectionClassNames)) {
			$this->periodCollectionClassNames = array();
		}
		
		$this->periodCollectionClassNames[] = $periodCollectionClassName;
		return $this;
	}
	
	/**
	 * 
	 * @param bab_PeriodCollection $collection
	 * @return bool
	 */
	public function isPeriodCollection(bab_PeriodCollection $collection) {
		if (null === $this->periodCollectionClassNames)
		{
			return true;
		}
		
		foreach($this->periodCollectionClassNames as $classname) {
			if ($collection instanceof $classname) {
				return true;
			}
		}
		
		return false;
	}
	
	
	/**
	 * 
	 * @param array $calendars	array of bab_EventCalendar
	 * @return bab_eventCollectPeriodsBeforeDisplay
	 */
	public function filterByCalendar(array $calendars) {
		$this->calendars = $calendars;
		return $this;
	}
	
	/**
	 * Add a calendar to the list of displayable calendars
	 * @param bab_EventCalendar $calendar
	 * @return bab_eventCollectPeriodsBeforeDisplay
	 */
	public function addFilterByCalendar(bab_EventCalendar $calendar) {
		if (!isset($this->calendars)) {
			$this->calendars = array();
		}
		
		$this->calendars[] = $calendar;
		return $this;
	}
	
	
	/**
	 * Get calendars where to apply a filter
	 * @return unknown_type
	 */
	public function getCalendars() {
		return $this->calendars;
	}
	
	
	
	/**
	 * Add a filter by iCal property (ex. CATEGORY)
	 * @param string $property		iCal property name
	 * @param array $values			list of allowed exact values for this property
	 * @return bab_eventCollectPeriodsBeforeDisplay
	 */
	public function filterByICalProperty($property, Array $values)
	{
		$this->icalProperties[$property] = $values;
		return $this;
	}
	
	/**
	 * Get the iCal properties where to apply a filter
	 * if the method return null, no filter
	 * @return array
	 */
	public function getICalProperties()
	{
		return $this->icalProperties;
	}
	
	
	/**
	 * 
	 * @return BAB_DateTime
	 */
	public function getBeginDate()
	{
		return $this->periods->begin;
	}
	
	/**
	 * 
	 * @return BAB_DateTime
	 */
	public function getEndDate()
	{
		return $this->periods->end;
	}
	
	/**
	 * Get users id of calendars
	 * @return array
	 */
	public function getUsers()
	{
		if (!isset($this->calendars)) {
			return array();
		}
		
		$return = array();
		foreach($this->calendars as $calendar) {
			$iduser = $calendar->getIdUser();
			if ($iduser) {
				$return[$iduser] = $iduser;
			}
		}
		
		return $return;
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
	require_once dirname(__FILE__).'/cal.periodcollection.class.php';
	require_once dirname(__FILE__).'/workinghoursincl.php';
	
	$calendars = $event->getCalendars();
	$users = $event->getUsers();
	$begin = $event->getBeginDate();
	$end = $event->getEndDate();
	
	$vac_collection	= new bab_VacationPeriodCollection;
	$evt_collection = new bab_CalendarEventCollection;
	$tsk_collection = new bab_TaskCollection;
	$wp_collection 	= new bab_WorkingPeriodCollection;
	$nwp_collection = new bab_NonWorkingPeriodCollection;
	
	
	if ($event->isPeriodCollection($vac_collection) && $users) {
		include_once $GLOBALS['babInstallPath']."utilit/vacincl.php";
		bab_vac_setVacationPeriods($vac_collection, $event->periods, $users, $begin, $end);
	}

	if ($event->isPeriodCollection($evt_collection)) {
		include_once $GLOBALS['babInstallPath']."utilit/calincl.php";
		
		$ical = $event->getICalProperties();
		$categories = null;
		if (isset($ical['CATEGORY'])) {
			$categories = $ical['CATEGORY'];
		}
		
		$oviEvents = new bab_cal_OviCalendarEvents;
		$oviEvents->setEventsPeriods($event->periods, $calendars, $begin, $end, $categories); 
	}

	if ($event->isPeriodCollection($tsk_collection) && $users) {
		include_once $GLOBALS['babInstallPath']."utilit/tmdefines.php";
		include_once $GLOBALS['babInstallPath']."utilit/tmIncl.php";
		bab_tskmgr_setPeriods($tsk_collection, $event->periods, $users, $begin, $end);
	}



	
	$loop = $begin->cloneDate();
	$endts = $end->getTimeStamp() + 86400;
	$begints = $begin->getTimeStamp();
	$working = $event->isPeriodCollection($wp_collection);
	$nworking = $event->isPeriodCollection($nwp_collection);
	$previous_end = NULL;

	if ($users) {
		while ($loop->getTimeStamp() < $endts) {
			
			if ($working) {
				foreach($users as $id_user) {
					$arr = bab_getWHours($id_user, $loop->getDayOfWeek());
					
					
	
					foreach($arr as $h) {
						$startHour	= explode(':', $h['startHour']);
						$endHour	= explode(':', $h['endHour']);
						
						$beginDate = new BAB_DateTime(
							$loop->getYear(),
							$loop->getMonth(),
							$loop->getDayOfMonth(),
							$startHour[0],
							$startHour[1],
							$startHour[2]
							);
	
						$endDate = new BAB_DateTime(
							$loop->getYear(),
							$loop->getMonth(),
							$loop->getDayOfMonth(),
							$endHour[0], 
							$endHour[1], 
							$endHour[2]
							);
	
						if ($nworking && NULL == $previous_end) {
							$previous_end = $begin; // reference
						}
	
						// add non-working period between 2 working period and at the begining
						if ($nworking && $begints > $previous_end->getTimeStamp()) {
	
							$p = new bab_calendarPeriod($previous_end, $begints);
							$p->setProperty('SUMMARY'		, bab_translate('Non-working period'));
							$p->setProperty('DTSTART'		, $previous_end->getIsoDateTime());
							$p->setProperty('DTEND'			, $begints);
							$p->setData(array('id_user' => $id_user));
							
							$nwp_collection->addPeriod($p);
							$event->periods->addPeriod($p);
						}
	
						$p = new bab_calendarPeriod($begin->getTimeStamp(), $end->getTimeStamp());
	
						$p->setProperty('SUMMARY'		, bab_translate('Working period'));
						$p->setProperty('DTSTART'		, $begin->getIsoDateTime());
						$p->setProperty('DTEND'			, $end->getIsoDateTime());
						$p->setData(array('id_user' => $id_user));
						$p->available = true;
						
						$wp_collection->addPeriod($p);
						$event->periods->addPeriod($p);
	
						$previous_end = $endDate; // the begin date of the non-working period will be a reference to the enddate of the working period
					}
				}
			}
			$loop->add(1, BAB_DATETIME_DAY);
		}
	}

	// add final non-working period
	if ($nworking && $end->getTimeStamp() > $previous_end->getTimeStamp()) {

		$p = new bab_calendarPeriod($previous_end->getTimeStamp(), $end->getTimeStamp());
		$p->setProperty('SUMMARY'		, bab_translate('Non-working period'));
		$p->setProperty('DTSTART'		, $previous_end->getIsoDateTime());
		$p->setProperty('DTEND'			, $end->getIsoDateTime());
		$p->setData(array('id_user' => $id_user));
		
		$nwp_collection->addPeriod($p);
		$event->periods->addPeriod($p);
	}
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
	
	$backend = bab_functionality::get('CalendarBackend/Ovi');
	/*@var $backend Func_CalendarBackend_Ovi */
	
	// public calendars
	
	$visible_public_cal = bab_getAccessibleObjects(BAB_CAL_PUB_VIEW_GROUPS_TBL, $event->getAccessUser());
	
	$res = $babDB->db_query("
		select cpt.*, ct.id as idcal
		from 
			".BAB_CAL_PUBLIC_TBL." cpt 
				left join ".BAB_CALENDAR_TBL." ct on ct.owner=cpt.id 
		where 
			ct.type='".BAB_CAL_PUB_TYPE."' 
			AND ct.actif='Y' 
			AND ct.id IN(".$babDB->quote($visible_public_cal).")
	");
	
	while( $arr = $babDB->db_fetch_assoc($res))
	{	
		$calendar = $backend->PublicCalendar();
		$calendar->init($event->getAccessUser(), $arr);
		$event->addCalendar($calendar);
	}
	
	
	
	// ressource calendars
	
	$visible_ressource_cal = bab_getAccessibleObjects(BAB_CAL_RES_VIEW_GROUPS_TBL, $event->getAccessUser());
	
	$res = $babDB->db_query("
		select crt.*, ct.id as idcal 
		from 
			".BAB_CAL_RESOURCES_TBL." crt 
				left join ".BAB_CALENDAR_TBL." ct on ct.owner=crt.id 
		where 
			ct.type='".BAB_CAL_RES_TYPE."' 
			and ct.actif='Y' 
			AND ct.id IN(".$babDB->quote($visible_ressource_cal).")
	");
	
	while($arr = $babDB->db_fetch_assoc($res))
	{
		$calendar = $backend->RessourceCalendar();
		$calendar->init($event->getAccessUser(), $arr);
		$event->addCalendar($calendar);
	}
	
	// personal calendars
	
	$personal_calendar = $event->getPersonalCalendar();
	
	if ($personal_calendar)
	{
		$event->addCalendar($personal_calendar);

		if($babBody->babsite['iPersonalCalendarAccess'] == 'Y')
		{
			
			$query = "
				select 
					cut.*, 
					ct.owner, 
					u.firstname,
					u.lastname 
	
				from ".BAB_CALACCESS_USERS_TBL." cut 
					left join ".BAB_CALENDAR_TBL." ct on ct.id=cut.id_cal 
					left join ".BAB_USERS_TBL." u on u.id=ct.owner 
				where 
					id_user='".$babDB->db_escape_string($event->getAccessUser())."' and ct.actif='Y' and disabled='0'
			";
			$res = $babDB->db_query($query);
	
			while( $arr = $babDB->db_fetch_assoc($res))
			{
				$data = array(
				
					'idcal' 		=> $arr['id_cal'],
					'name' 			=> bab_composeUserName($arr['firstname'], $arr['lastname']),
					'description' 	=> '',
					'idowner' 		=> $arr['owner'],
					'access' 		=> $arr['bwrite']
				
				);
				
				$calendar = $backend->PersonalCalendar();
				$calendar->init($event->getAccessUser(), $data);
				$event->addCalendar($calendar);
			}
		}
	}
}