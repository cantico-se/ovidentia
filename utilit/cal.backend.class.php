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
	
	/**
	 * Get backend url identifier
	 * @return string
	 */
	public function getUrlIdentifier()
	{
		$classname = get_class($this);
		return substr($classname, 1+ strrpos($classname, '_'));
	}
	

	public function includeEventCalendar()
	{
		require_once dirname(__FILE__).'/cal.eventcalendar.class.php';
	}
	
	
	/**
	 * The backend can be used as a storage backend for the existing calendars (personal only for now)
	 * @return bool
	 */
	public function StorageBackend()
	{
		return true;
	}
	
	
	/**
	 * Create a personal calendar from an ovidentia user
	 * 
	 * @param	int	$id_user		owner of calendar
	 * 
	 * @return bab_EventCalendar
	 */
	public function PersonalCalendar($id_user) 
	{
		throw new Exception('Do not call directly, each backend must have his own calendar implementation');
		return null;
	}
	
	/**
	 * @return bab_EventCalendar
	 */
	public function PublicCalendar() 
	{
		throw new Exception('Do not call directly, each backend must have his own calendar implementation');
		return null;
	}
	
	/**
	 * @return bab_EventCalendar
	 */
	public function ResourceCalendar() 
	{
		throw new Exception('Do not call directly, each backend must have his own calendar implementation');
		return null;
	}
	
	
	
	public function includePeriodCollection()
	{
		require_once dirname(__FILE__).'/cal.periodcollection.class.php';
	}
	
	/**
	 * A collection of vacation periods
	 * 
	 * @param	bab_PersonalCalendar $calendar		for this collection, the calendar is mandatory and must be a personal calendar
	 * 
	 * @return bab_VacationPeriodCollection
	 */
	public function VacationPeriodCollection(bab_PersonalCalendar $calendar)
	{
		$this->includePeriodCollection();
		$collection = new bab_VacationPeriodCollection;
		$collection->setCalendar($calendar);
		return $collection;
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
	 * A collection of events from inbox of a personal calendar
	 * 
	 * @param	bab_PersonalCalendar $calendar		for this collection, the calendar is mandatory and must be a personal calendar
	 * 
	 * @return bab_InboxEventCollection
	 */
	public function InboxEventCollection(bab_PersonalCalendar $calendar)
	{
		$this->includePeriodCollection();
		$collection = new bab_InboxEventCollection;
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
	 * @param	bab_PeriodCollection	$periodCollection		where to search for event, the collection can be populated with the selected event
	 * @param 	string 					$identifier				The UID property of event
	 * @param	string					[$dtstart]				The DTSTART value of the event (this can be usefull if the event is a recurring event, DTSTART will indicate the correct instance)
	 * 
	 * @return bab_CalendarPeriod
	 */
	public function getPeriod(bab_PeriodCollection $periodCollection, $identifier, $dtstart = null)
	{
		$criteria = $this->Criteria()->Calendar($periodCollection->getCalendar())
			->_AND_($this->Criteria()->Uid($identifier));
		
		$res = $this->selectPeriods($criteria);
		
		foreach($res as $period)
		{
			return $period;
		}
		
		return null;
	}
	
	/**
	 * Return an iterator with events corresponding to an UID
	 * in case of a recurring event, all instance of event will be returned beetween the $expandStart and $expandEnd boundaries
	 * if the expandRecurrence parameter is set to false, the iterator will contain the event and the aditional exceptions with RECURENCE-ID property
	 * 
	 * @param 	bab_PeriodCollection	$periodCollection
	 * @param 	string					$identifier				The UID property of event
	 * @param	bool					$expandRecurrence		
	 * @param	BAB_DateTime			$expandStart
	 * @param	BAB_DateTime			$expandEnd
	 * @return iterator <bab_CalendarPeriod>
	 */
	public function getAllPeriods(bab_PeriodCollection $periodCollection, $identifier, $expandRecurrence = true, BAB_DateTime $expandStart = null, BAB_DateTime $expandEnd = null)
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
	 * Deletes the period corresponding to the specified object.
	 * 
	 * @param	bab_CalendarPeriod	$period
	 * 
	 * @return bool
	 */
	public function deletePeriod(bab_CalendarPeriod $period)
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
	 * @param string				$comment	comment given when changing PARTSTAT (optional)
	 * @return bool
	 */
	public function updateAttendeePartstat(bab_CalendarPeriod $period, bab_PersonalCalendar $calendar, $partstat, $comment = '')
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
	
	
	/**
	 * The list of calendars recorded with the sharing access form
	 * to use theses calendars, the user must have a personal calendar or $babBody->babsite['iPersonalCalendarAccess'] == 'Y'
	 * 
	 * @param int		$access_user		in most case, the current user
	 * @param string	$calendartype		optional filter by calendar type
	 * @return array	<int>				array of id_user
	 */
	public function getAccessiblePersonalCalendars($access_user = null, $calendartype = null)
	{
		global $babDB;
		
		if (null == $access_user)
		{
			$access_user = $GLOBALS['BAB_SESS_USERID'];
		}
		
		$query = "
			select 
				ct.owner  

			from ".BAB_CALACCESS_USERS_TBL." cut,
				 ".BAB_CALENDAR_TBL." ct, 
				 ".BAB_USERS_TBL." u  
			where 
				ct.id=cut.id_cal 
				and u.id=ct.owner 
				and cut.id_user='".$babDB->db_escape_string($access_user)."' 
				and ct.actif='Y' 
				and u.disabled='0'
		";
		
		if (null !== $calendartype)
		{
			$query .= ' and cut.caltype='.$babDB->quote($calendartype);
		}
		
		$res = $babDB->db_query($query);
		
		$return = array();

		while( $arr = $babDB->db_fetch_assoc($res))
		{
			$return[$arr['owner']] = $arr['owner'];
		}
		
		return $return;
	}
	
	
	
	
	
	
	
	
	
	/**
	 * Update the relation status after approbation
	 * This method will also remove the X-CTO-WFINSTANCE
	 * 
	 * @param bab_CalendarPeriod 	$period
	 * @param array 				$relation		The relation to update
	 * @param string	 			$status			new status for relation		ACCEPTED | DECLINED	
	 * @return bool
	 */
	public function setRelationStatus(bab_CalendarPeriod $period, Array $relation, $status)
	{
		$reltype = $period->getRelationType($relation['calendar']);
		
		if (null === $reltype)
		{
			throw new Exception(sprintf('the relation beetween event %s and calendar %s does not exists', $period->getProperty('UID'), $relation['calendar']->getName()));
			return false;
		}
		
		$period->addRelation($reltype, $relation['calendar'], $status);
		// the addRelation method handle duplicate entries, this will update relation status and remove flow instance
		
		bab_debug('<h1>$backend->SavePeriod()</h1>'. $period->toHtml(), DBG_TRACE, 'CalendarBackend');
		return $this->savePeriod($period);
	}

}