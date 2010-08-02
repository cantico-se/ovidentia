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
	private $calendars = array();
	
	
	public function addCalendar(bab_EventCalendar $calendar)
	{
		$this->calendars[] = $calendar;
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
	 * Add a filter by instance of event collection 
	 * @param array $periodCollectionClassNames
	 * @return bab_eventCollectPeriodsBeforeDisplay
	 */
	public function filterByPeriodCollection(array $periodCollectionClassNames) {
		$this->periodCollectionClassNames = $periodCollectionClassNames;
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
	 * @return unknown_type
	 */
	public function filterByCalendar(array $calendars) {
		$this->calendars = $calendars;
	}
	
	/**
	 * Add a calendar to the list of displayable calendars
	 * @param bab_EventCalendar $calendar
	 * @return unknown_type
	 */
	public function addFilterByCalendar(bab_EventCalendar $calendar) {
		if (!isset($this->calendars)) {
			$this->calendars = array();
		}
		
		$this->calendars[] = $calendar;
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
function bab_onCollectPeriodsBeforeDisplay(bab_eventBeforePeriodsCreated $event)
{
	$wp_collection = new bab_WorkingPeriodCollection;
	$nwp_collection = new bab_NonWorkingPeriodCollection;
	
	
	
	$begindate = $event->getBeginDate();
	$enddate = $event->getEndDate();
	
	$loop = $begindate->cloneDate();
	$endts = $enddate->getTimeStamp() + 86400;
	$begints = $begindate->getTimeStamp();
	$working = $event->isPeriodCollection($wp_collection);
	$nworking = $event->isPeriodCollection($nwp_collection);
	$previous_end = NULL;

	while ($loop->getTimeStamp() < $endts) {
		
		if ($working && $this->id_users) {
			foreach($this->id_users as $id_user) {
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
						$previous_end = $this->begin; // reference
					}

					// add non-working period between 2 working period and at the begining
					if ($nworking && $beginDate->getTimeStamp() > $previous_end->getTimeStamp()) {

						$p = new bab_calendarPeriod($previous_end, $beginDate);
						$p->setProperty('SUMMARY'		, bab_translate('Non-working period'));
						$p->setProperty('DTSTART'		, $previous_end->getIsoDateTime());
						$p->setProperty('DTEND'			, $beginDate->getIsoDateTime());
						$p->setData(array('id_user' => $id_user));
						
						$nwp_collection->addPeriod($p);
					}

					$p = new bab_calendarPeriod($beginDate, $endDate);

					$p->setProperty('SUMMARY'		, bab_translate('Working period'));
					$p->setProperty('DTSTART'		, $beginDate->getIsoDateTime());
					$p->setProperty('DTEND'			, $endDate->getIsoDateTime());
					$p->setData(array('id_user' => $id_user));
					
					$wp_collection->addPeriod($p);

					$previous_end = $endDate; // the begin date of the non-working period will be a reference to the enddate of the working period
				}
			}
		}
		$loop->add(1, BAB_DATETIME_DAY);
	}

	// add final non-working period
	if ($nworking && $this->end->getTimeStamp() > $previous_end->getTimeStamp()) {

		$p = new bab_calendarPeriod($previous_end, $this->end);
		$p->setProperty('SUMMARY'		, bab_translate('Non-working period'));
		$p->setProperty('DTSTART'		, $previous_end->getIsoDateTime());
		$p->setProperty('DTEND'			, $this->end->getIsoDateTime());
		$p->setData(array('id_user' => $id_user));
		
		$nwp_collection->addPeriod($p);
	}
	
}


/**
 * Core function registered to collect calendars
 * @param bab_eventCollectCalendarsBeforeDisplay $event
 * @return unknown_type
 */
function bab_onCollectCalendarsBeforeDisplay(bab_eventCollectCalendarsBeforeDisplay $event)
{
	
}