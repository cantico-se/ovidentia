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
	public function RessourceCalendar() {
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
	 * @return bab_CalendarEventCollection
	 */
	public function CalendarEventCollection()
	{
		$this->includePeriodCollection();
		return new bab_CalendarEventCollection;
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
	 * 
	 * @param int $begin	Timestamp
	 * @param int $end		Timestamp
	 * 
	 * @return bab_CalendarPeriod
	 */
	public function CalendarPeriod($begin, $end)
	{
		$this->includeCalendarPeriod();
		return new bab_CalendarPeriod();
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
	 */
	public function savePeriod(bab_CalendarPeriod $period)
	{
		throw new Exception('not implemented');
	}
	
	/**
	 * Returns the period corresponding to the specified identifier
	 * this is necessary for all events with a link
	 * 
	 * @param	bab_PeriodCollection	$periodCollection		where to search for event
	 * @param 	string 					$identifier				The UID property of event
	 * 
	 * @return bab_CalendarPeriod
	 */
	public function getPeriod(bab_PeriodCollection $periodCollection, $identifier)
	{
		throw new Exception('not implemented');
	}
	
	/**
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
	 * 
	 * @return bool
	 */
	public function deletePeriod(bab_PeriodCollection $periodCollection, $identifier)
	{
		throw new Exception('not implemented');
	}
	
	
	/**
	 * @param unknown_type $calendar
	 * @param unknown_type $accessType
	 * @param unknown_type $user
	 */
	public function grantAccess($calendar, $accessType, $user)
	{
		
	}
	
	
	/**
	 * @param unknown_type $calendar
	 * @param unknown_type $accessType
	 * @param unknown_type $user
	 */
	public function revokeAccess($calendar, $accessType, $user)
	{
		
	}
	
}