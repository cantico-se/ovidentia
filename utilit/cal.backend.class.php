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
 * Calendar backend
 */
class Func_CalendarBackend extends bab_functionality
{
	public function getDescription()
	{
		return bab_translate('Calendar backend');
	}
	

	public function includeEventCalendar()
	{
		require_once dirname(__FILE__).'/cal.eventcalendar.class.php';
	}
	
	/**
	 * @return bab_EventCalendar
	 */
	public function PersonalCalendar() {
		throw new Exception('Do not call directly, each backend must have his own calendar implementation');
		return null;
	}
	
	/**
	 * @return bab_EventCalendar
	 */
	public function PublicCalendar() {
		throw new Exception('Do not call directly, each backend must have his own calendar implementation');
		return null;
	}
	
	/**
	 * @return bab_EventCalendar
	 */
	public function ResourceCalendar() {
		throw new Exception('Do not call directly, each backend must have his own calendar implementation');
		return null;
	}
	
	
	public function includePeriodCollection()
	{
		require_once dirname(__FILE__).'/cal.periodcollection.class.php';
	}
	
	/**
	 * A collection of vacation periods
	 * @return bab_VacationPeriodCollection
	 */
	public function VacationPeriodCollection()
	{
		$this->includePeriodCollection();
		return new bab_VacationPeriodCollection;
	}
	
	
	/**
	 * A collection of events
	 * 
	 * @param	bab_EventCalendar $calendar			for this collection, the calendar is mandatory
	 * 
	 * @return bab_CalendarEventCollection
	 */
	public function CalendarEventCollection(bab_EventCalendar $calendar)
	{
		$this->includePeriodCollection();
		$collection = new bab_CalendarEventCollection;
		$collection->setCalendar($calendar);
		return $collection;
	}
	
	/**
	 * A collection of tasks 
	 * (exemple task manager from ovidentia)
	 * @return bab_TaskCollection
	 */
	public function TaskCollection()
	{
		$this->includePeriodCollection();
		return new bab_TaskCollection;
	}
	
	
	/**
	 * A collection of working periods computed from the working hours parameter of a user
	 * @return bab_WorkingPeriodCollection
	 */
	public function WorkingPeriodCollection()
	{
		$this->includePeriodCollection();
		return new bab_WorkingPeriodCollection;
	}
	
	
	/**
	 * A collection of non-working periods computed from the working hours parameter of a user
	 * @return bab_NonWorkingPeriodCollection
	 */
	public function NonWorkingPeriodCollection()
	{
		$this->includePeriodCollection();
		return new bab_NonWorkingPeriodCollection;
	}
	
	
	/**
	 * A collection of non-working days set by administrator
	 * @return bab_NonWorkingDaysCollection
	 */
	public function NonWorkingDaysCollection()
	{
		$this->includePeriodCollection();
		return new bab_NonWorkingDaysCollection;
	}
	
	
	
	public function includeCalendarPeriod()
	{
		require_once dirname(__FILE__).'/cal.calendarperiod.class.php';
	}
	
	/**
	 * Create new calendar period
	 * VEVENT object item
	 * 
	 * @param int $begin	Timestamp
	 * @param int $end		Timestamp
	 * 
	 * @return bab_CalendarPeriod
	 */
	public function CalendarPeriod($begin, $end)
	{
		$this->includeCalendarPeriod();
		return new bab_CalendarPeriod($begin, $end);
	}
	
	
	public function includeCalendarAlarm()
	{
		require_once dirname(__FILE__).'/cal.calendarperiod.class.php';
	}
	
	/**
	 * Create new calendar alarm
	 * VALARM object item, store rules for reminder on event
	 * @see bab_CalendarPeriod::setAlarm()
	 * 
	 * @return bab_CalendarAlarm
	 */
	public function CalendarAlarm()
	{
		$this->includeCalendarAlarm();
		return new bab_CalendarAlarm();
	}
	
	
	
	/**
	 * Access to period criteria objects
	 * @return bab_PeriodCriteriaFactory
	 */
	public function Criteria()
	{
		require_once dirname(__FILE__).'/cal.userperiods.class.php';
		return bab_getInstance('bab_PeriodCriteriaFactory');
	}

	
	
	
	/**
	 * Creates or updates a calendar event.
	 * if the period have a UID property, the event will be modified or if the UID property is empty, the event will be created
	 * 
	 * @param	bab_CalendarPeriod	$period
	 * 
	 * @return bool
	 */
	public function savePeriod(bab_CalendarPeriod $period)
	{
		throw new Exception('not implemented');
		return false;
	}
	
	/**
	 * Returns the period corresponding to the specified identifier
	 * this is necessary for all events with a link
	 * 
	 * @param	bab_PeriodCollection	$periodCollection		where to search for event
	 * @param 	string 					$identifier				The UID property of event
	 * @param	string					[$dtstart]				The DTSTART value of the event (this can be usefull if the event is a recurring event, DTSTART will indicate the correct instance)
	 * 
	 * @return bab_CalendarPeriod
	 */
	public function getPeriod(bab_PeriodCollection $periodCollection, $identifier, $dtstart = null)
	{
		throw new Exception('not implemented');
	}
	
	/**
	 * Select periods from criteria
	 * the bab_PeriodCriteriaCollection and bab_PeriodCriteriaCalendar are mandatory
	 * 
	 * 
	 * @param bab_PeriodCriteria $criteria
	 * 
	 * @return iterator <bab_CalendarPeriod>
	 */
	public function selectPeriods(bab_PeriodCriteria $criteria)
	{
		throw new Exception('not implemented');
	}
	
	
	
	/**
	 * Deletes the period corresponding to the specified identifier.
	 * 
	 * @param	bab_PeriodCollection	$periodCollection		where to search for event
	 * @param 	string 					$identifier				The UID property of event
	 * @param	string					[$dtstart]				The DTSTART value of the event (this can be usefull if the event is a recurring event, DTSTART will indicate the correct instance)
	 * 
	 * @return bool
	 */
	public function deletePeriod(bab_PeriodCollection $periodCollection, $identifier, $dtstart = null)
	{
		throw new Exception('not implemented');
	}
	
	
	
	/**
	 * Update an attendee PARTSTAT value of a calendar event
	 * a user can modifiy his participation status without modifing the full event, before triggering this method, the access right will be checked with the
	 * canUpdateAttendeePARTSTAT method of the calendar
	 * 
	 * @see bab_EventCalendar::canUpdateAttendeePARTSTAT()
	 * 
	 * @param bab_CalendarPeriod 	$period		the event
	 * @param bab_EventCalendar 	$calendar	the personal calendar used as an attendee
	 * @param string 				$partstat	ACCEPTED | DECLINED
	 * @return bool
	 */
	public function updateAttendeePartstat(bab_CalendarPeriod $period, bab_EventCalendar $calendar, $partstat)
	{
		throw new Exception('not implemented');
	}
	
	
	
	
	
	/**
	 * Get url off an option page for the backend, the page will be displayed in a popup window accessible for each users
	 * from the calendar options
	 * 
	 * @return string
	 */
	public function getOptionsUrl()
	{
		return null;
	}

}