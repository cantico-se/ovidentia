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

bab_functionality::includefile('CalendarBackend');


class Func_CalendarBackend_Ovi extends Func_CalendarBackend
{
	public function getDescription()
	{
		return bab_translate('Ovidentia calendar');
	}
	
	
	/**
	 * Create a personal calendar from an ovidentia user
	 * 
	 * @param	int	$id_user		owner of calendar
	 * 
	 * @return bab_OviPersonalCalendar
	 */
	public function PersonalCalendar($id_user)
	{
		$this->includeEventCalendar();
		$calendar = new bab_OviPersonalCalendar;
		if (!$calendar->initFromUser($id_user))
		{
			// this calendar does not exists or has been disabled
			return null;
		}
		
		return $calendar;
	}
	
	/**
	 * @return bab_OviPublicCalendar
	 */
	public function PublicCalendar()
	{
		$this->includeEventCalendar();
		return new bab_OviPublicCalendar;
	}
	
	/**
	 * @return bab_OviResourceCalendar
	 */
	public function ResourceCalendar()
	{
		$this->includeEventCalendar();
		return new bab_OviResourceCalendar;
	}
	
	
	
	/**
	 * Creates or updates a calendar event.
	 * if the period have a UID property, the event will be modified or if the UID property is empty, the event will be created
	 * 
	 * @param	bab_CalendarPeriod		$period
	 * @param	string					$method		iCalendar Transport-Independent Interoperability Protocol (iTIP) (RFC 5546)
	 * 												PUBLISH | REQUEST | REPLY | ADD | CANCEL | REFRESH | COUNTER | DECLINECOUNTER
	 * 
	 * @return bool
	 */
	public function savePeriod(bab_CalendarPeriod $period, $method = null)
	{
		// ovidentia backend does not support method CANCEL, so we delete if the event is canceled
		
		if ('CANCEL' === $method)
		{
			return $this->deletePeriod($period);
		}
		
		
		require_once dirname(__FILE__).'/cal.ovievent.class.php';
		
		$collection = $period->getCollection();
		
		if ($collection->hash)
		{
			foreach($collection as $period)
			{
				if (!bab_cal_OviEventUpdate::save($period))
				{
					return false;	
				}
			}
			return true;
		
		} else {
			return bab_cal_OviEventUpdate::save($period);
		}
	}
	
	
	
	/**
	 * Returns the period corresponding to the specified identifier
	 * this is necessary for all events with a link
	 * 
	 * @TODO other collections ?
	 * 
	 * @param	bab_PeriodCollection	$periodCollection		where to search for event
	 * @param 	string 					$identifier				The UID property of event
	 * @param	string					[$dtstart]				The DTSTART value of the event (this can be usefull if the event is a recurring event, DTSTART will indicate the correct instance)
	 *  
	 * @return bab_CalendarPeriod
	 */
	public function getPeriod(bab_PeriodCollection $periodCollection, $identifier, $dtstart = null)
	{
		if ($periodCollection instanceof bab_CalendarEventCollection) 
		{
			require_once dirname(__FILE__).'/cal.ovievent.class.php';
			$oviEvents = new bab_cal_OviEventSelect;
			return $oviEvents->getFromUid($identifier);
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
		$period = $this->getPeriod($periodCollection, $identifier);
		return array($period);
	}
	
	
	
	
	
	
	/**
	 * Select periods from criteria
	 * the bab_PeriodCriteriaCollection and bab_PeriodCriteriaCalendar are mandatory
	 * 
	 * @param bab_PeriodCriteria $criteria
	 * 
	 * @return bab_UserPeriods <bab_CalendarPeriod>		(iterator)
	 */
	public function selectPeriods(bab_PeriodCriteria $criteria)
	{
		require_once dirname(__FILE__).'/cal.userperiods.class.php';
		require_once dirname(__FILE__).'/cal.ovievent.class.php';
		
		$userperiods = new bab_UserPeriods;
		$userperiods->setCriteria($criteria);
		$userperiods->processCriteria($criteria);
		
		$oviEvents = new bab_cal_OviEventSelect;
		$oviEvents->processQuery($userperiods);
		
		$userperiods->orderBoundaries();
		return $userperiods;
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
		$collection = $period->getCollection();

		if ($collection instanceof bab_CalendarEventCollection) 
		{
			require_once dirname(__FILE__).'/cal.ovievent.class.php';
			$oviEvents = new bab_cal_OviEventSelect;
			
			if ($collection->hash)
			{
				foreach($collection as $period)
				{
					if (!$oviEvents->deleteFromUid($period->getProperty('UID')))
					{
						return false;	
					}
				}
				return true;
			
			} else {
				return $oviEvents->deleteFromUid($period->getProperty('UID'));
			}
		}
		
		return null;
	}
	
	
	
	
	/**
	 * Update an attendee PARTSTAT value of a calendar event
	 * a user can modifiy his participation status without modifing the full event, before triggering this method, the access right will be checked with the
	 * canUpdateAttendeePARTSTAT method of the calendar
	 * 
	 * @see bab_EventCalendar::canUpdateAttendeePARTSTAT()
	 * 
	 * @param bab_CalendarPeriod 	$period		the event
	 * @param bab_PersonalCalendar 	$calendar	the personal calendar used as an attendee
	 * @param string 				$partstat	ACCEPTED | DECLINED
	 * @param string				$comment	comment given when changing PARTSTAT (optional)
	 * @return bool
	 */
	public function updateAttendeePartstat(bab_CalendarPeriod $period, bab_PersonalCalendar $calendar, $partstat, $comment = '')
	{
		global $babDB;
		
		if ('DECLINED' === $partstat)
		{
			$all_attendees_declined = true;
			foreach($period->getAttendees() as $attendee) {
				if ($attendee['calendar']->getUrlIdentifier() !== $calendar->getUrlIdentifier() && 'DECLINED' !== $attendee['PARTSTAT'])
				{
					$all_attendees_declined = false;
				}
			}
			
			if ($all_attendees_declined)
			{
				require_once dirname(__FILE__).'/cal.ovievent.class.php';
				$oviEvents = new bab_cal_OviEventSelect;
				return $oviEvents->deleteFromUid($period->getProperty('UID'));
			}
		}
		
		
		switch($partstat)
		{
			case 'ACCEPTED':
				$status = BAB_CAL_STATUS_ACCEPTED;
				break;
				
			case 'DECLINED':
				$status = BAB_CAL_STATUS_DECLINED;
				break;
				
			default:
				$status = BAB_CAL_STATUS_NONE;
				break;
		}
		
		$res = $babDB->db_query('
			SELECT 
				id 
			FROM 
				'.BAB_CAL_EVENTS_TBL.' 
			WHERE 
				uuid='.$babDB->quote($period->getProperty('UID')).'
		');
		
		$arr = $babDB->db_fetch_assoc($res);
		
		if (!$arr)
		{
			
			throw new Exception('event not found '.$period->getProperty('UID'));
		}
		
		
		$babDB->db_query("
			update ".BAB_CAL_EVENTS_OWNERS_TBL." 
				set status=".$babDB->quote($status)." 
			where 
				id_event=".$babDB->quote($arr['id'])." 
				and id_cal=".$babDB->quote($calendar->getUid())
		);
		
	}
	
	
	
	
}