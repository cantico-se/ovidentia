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
 * Ovidentia calendar backend event manipulation
 * create or update an event from a bab_CalendarPeriod object
 */
class bab_ovi_event
{	
	
	/**
	 * Save of update a calendar period
	 * @param bab_CalendarPeriod $period
	 * @return unknown_type
	 */
	public static function save(bab_CalendarPeriod $period)
	{
		$manager = new bab_ovi_event;
		
		$uid = $period->getProperty('UID');
		
		if ($uid)
		{
			// modification
			throw new Exception('TODO');
		}
		else
		{
			// creation
			$hash = $manager->applyRrule($period);
			$manager->insertCollection($period, $hash);
		}
		
		return true;
	}
	
	
	
	
	
	/**
	 * Create all periods into collection for a recurring event
	 * return the hash to use for serie if there is at least one period generated or null if no recurring rule
	 * this method implement only the recurring rules builable from the ovidentia user interface
	 * 
	 * @param	bab_CalendarPeriod 	$period
	 * 
	 * @return string | null	hash
	 */
	private function applyRrule(bab_CalendarPeriod $period)
	{
		$rrule = $period->getProperty('RRULE');
		
		if (empty($rrule))
		{
			return null;
		}
		
		// before saving a period, the submited period must be set in a new collection 
		
		$collection = $period->getCollection();
		if (!$collection)
		{
			throw new Exception('missing collection in the new event');
			return null;
		}
		
		if (1 !== $collection->count())
		{
			throw new Exception('Error, the number of periods in collection is incorrect, RRULE is propably allready applied');
			return null;
		}
		
		
		
		require dirname(__FILE__).'/dateTime.php';
		
		
		// create default UNTIL param because ovidentia does not support infinite recurring
		
		$UNTIL = BAB_DateTime::fromTimeStamp($period->ts_begin);
		$UNTIL->add(5, BAB_DATETIME_YEAR);
		
		// day of week default values (used in weekly recurring rule)
		$BYDAY = array();
		
		// default interval
		$INTERVAL = 1;
		
		$params = explode(';', $rrule);
		
		foreach($params as $pair)
		{
			$param = explode('=', $pair);
			switch($param[0])
			{
				case 'UNTIL':
					$UNTIL = BAB_DateTime::fromICal($param[1]);
					break;
					
				case 'BYDAY':
					$BYDAY = explode(',', $param[1]);
					break;
					
				case 'FREQ':
					$FREQ = $param[1];
					break;
					
				case 'INTERVAL':
					$INTERVAL = (int) $param[1];
					break;
			} 
		}
		
		
		if (!isset($FREQ))
		{
			throw new Exception('No FREQ parameter in the RRULE iCalendar Property');
			return null;
		}
		
		
		switch($FREQ) 
		{
			case 'DAILY':
				$this->applyRruleGeneric($period, BAB_DATETIME_DAY, $INTERVAL);
				break;
				
			case 'WEEKLY':
				$this->applyRruleWeekly($period, $BYDAY, $INTERVAL);
				break;
				
			case 'MONTHLY':
				$this->applyRruleGeneric($period, BAB_DATETIME_MONTH, $INTERVAL);
				break;
				
			case 'YEARLY':
				$this->applyRruleGeneric($period, BAB_DATETIME_YEAR, $INTERVAL);
				break;
		}
		
	}
	
	
	/**
	 * Apply RRULE for DAILY, MONTHLY, YEARLY
	 * 
	 * @param	bab_CalendarPeriod 	$period
	 * @param 	int 				$freq
	 * @param 	int 				$interval
	 * 
	 * @return unknown_type
	 */
	private function applyRruleGeneric(bab_CalendarPeriod $period, $freq, $interval)
	{
		$collection = $period->getCollection();
		$created = clone $period;
		
		while($created->ts_end < $UNTIL->getTimeStamp())
		{
			$begin 	= BAB_DateTime::fromTimeStamp($created->ts_begin);
			$end 	= BAB_DateTime::fromTimeStamp($created->ts_end);

			$begin->add($interval, $freq);
			$end->add($interval, $freq);
			
			if ($end->getTimeStamp() > $UNTIL->getTimeStamp())
			{
				break;
			}
			
			$created->setDates($begin, $end);
			$collection->addPeriod($created);
			
			$created = clone $created;
		}
		
	}
	
	
	
	
	
	/**
	 * Apply RRULE for WEEKLY
	 * 
	 * @param	bab_CalendarPeriod 	$period
	 * @param	array				$byday
	 * @param 	int 				$interval
	 * 
	 * @return unknown_type
	 */
	private function applyRruleWeekly(bab_CalendarPeriod $period, $byday, $interval)
	{
		if (empty($byday))
		{
			$byday = array($this->dayOfWeek($period->ts_begin));
		}
		
		$flipped_days = array_flip($byday);
		
		
		$collection = $period->getCollection();
		$created = clone $period;
		
		while($created->ts_end < $UNTIL->getTimeStamp())
		{
			$begin 	= BAB_DateTime::fromTimeStamp($created->ts_begin);
			$end 	= BAB_DateTime::fromTimeStamp($created->ts_end);

			$begin->add(1, BAB_DATETIME_DAY);
			$end->add(1, BAB_DATETIME_DAY);
			
			$day = $this->dayOfWeek($begin->ts_begin);
			
			if (!isset($flipped_days[$day]))
			{
				continue;
			}
			
			
			if ($end->getTimeStamp() > $UNTIL->getTimeStamp())
			{
				break;
			}
			
			$created->setDates($begin, $end);
			$collection->addPeriod($created);
			
			$created = clone $created;
		}
	}
	
	
	/**
	 * Day of week in ICal format
	 * @param	int		$timestamp
	 * @return string
	 */
	private function dayOfWeek($timestamp)
	{
		return strtoupper(substr(date('l', $timestamp), 0,2));
	}
	
	
	
	/**
	 * Insert all events in collection
	 * The collection will have multiple events if a recurring rule is applied on event
	 * 
	 * @param	bab_CalendarPeriod	$period
	 * @param	string				$hash
	 */
	private function insertCollection(bab_CalendarPeriod $period, $hash = null)
	{
		
		$collection = $period->getCollection();
		foreach($collection as $event)
		{
			$id_event = $this->insertPeriod($event, $hash);
			
			// insert alarm
			$this->applyAlarm($event, $id_event);
			
			// attach attendees and relations
			$this->applyAttendees($event, $id_event);
			$this->applyRelations($event, $id_event);
		}
	}
	
	
	
	
	
	/**
	 * Insert one period into calendar (no recur rule management)
	 * Insert optional alarm
	 * 
	 * @param	bab_CalendarPeriod	$period
	 * @param	string				$hash		unique string for events generated in same serie
	 * @return int
	 */
	private function insertPeriod(bab_CalendarPeriod $period, $hash = null)
	{
		global $babBody, $babDB;
	
		require_once $GLOBALS['babInstallPath'].'utilit/uuid.php';
		require_once $GLOBALS['babInstallPath'].'utilit/dateTime.php';
		
		$collection = $period->getCollection();
		
		if (!$collection)
		{
			throw new Exception('Missing period collection');
		}
		
		$calendar = $collection->getCalendar();
		
		if (!$calendar)
		{
			throw new Exception('Missing calendar');
		}
		
		if ($period->getProperty('UID'))
		{
			throw new Exception('Event allready inserted');
		}
		
		
		$id_owner = $GLOBALS['BAB_SESS_USERID'];
		
		
		
		// search category id from name
		
		$category = 0;
		$cat = bab_getCalendarCategory($period->getProperty('CATEGORIES'));
		if ($cat)
		{
			$category = $cat['id'];
		}
		
		// Private Y|N
		
		$private = 'N';
		if ('PUBLIC' !== $period->getProperty('CLASS')) {
			$private = 'Y';
		}
		
		$free = 'N';
		if ('TRANSPARENT' === $period->getProperty('TRANSP')) {
			$free = 'Y';
		}
		
		$data = $period->getData();
		
		// set a new UID to insert
		
		$period->setProperty('UID', bab_uuid());
		
		
		$hash = (string) $hash;
	
		$babDB->db_query("
			insert into ".BAB_CAL_EVENTS_TBL." 
				( title, description, description_format, location, start_date, end_date, id_cat, id_creator, color, bprivate, block, bfree, hash, date_modification, id_modifiedby, uuid) 
			
			values (
				".$babDB->quote($period->getProperty('SUMMARY')).", 
				".$babDB->quote($data['description']).",
				".$babDB->quote($data['description_format']).",  
				".$babDB->quote($period->getProperty('LOCATION')).", 
				".$babDB->quote(BAB_DateTime::fromICal($period->getProperty('DTSTART'))->getIsoDateTime()).", 
				".$babDB->quote(BAB_DateTime::fromICal($period->getProperty('DTEND'))->getIsoDateTime()).", 
				".$babDB->quote($category).", 
				".$babDB->quote($id_owner).", 
				".$babDB->quote($period->getColor()).", 
				".$babDB->quote($private).", 
				".$babDB->quote($data['block']).", 
				".$babDB->quote($free).", 
				".$babDB->quote($hash).",
				now(),
				".$babDB->quote($id_owner).",
				".$babDB->quote($period->getProperty('UID'))."
			)
		");
		
		
		
		$id_event = $babDB->db_insert_id();
		
		
		

		return $id_event;
	}
	
	
	
	
	
	
	
	private function applyAlarm(bab_CalendarPeriod $period, $id_event)
	{
		if ($alarm = $period->getAlarm())
		{
			$day = 0;
			$hour = 0;
			$minute = 0;
			
			
			$action = $alarm->getProperty('ACTION');
			$trigger = $alarm->getProperty('TRIGGER');
			
			if (0 === mb_strpos($trigger, '-P') && preg_match_all('/(?P<value>\d+)(?P<type>[DHM]{1})/', $trigger, $m, PREG_SET_ORDER)) {
				
				foreach($m as $trigger)
				{
					$val = $trigger['value'];
					switch($trigger['type'])
					{
						case 'D': $day = (int) $val; 	break;
						case 'H': $hour = (int) $val;	break;
						case 'M': $minute = (int) $val;	break;
					}
				}
				
				
				foreach($alam->getAttendees() as $attendee)
				{
					$id_user = $attendee['calendar']->getIdUser();
					
					
					switch($action)
					{
						case 'EMAIL':
							$this->createEventAlert($id_event, $day, $hour, $minute, true, $id_user);
							break;
							
						case 'DISPLAY':
							$this->createEventAlert($id_event, $day, $hour, $minute, false, $id_user);
							break;
					}
				}
			}
			
		}
	}
	
	
	
	
	
		
	
	/**
	 * Create alert for ovidentia events
	 * 
	 * @param	int		$id_event
	 * @param	int		$day
	 * @param	int		$hour
	 * @param	int		$minute
	 * @param	bool	$email
	 * @param	int		$id_user		
	 * 
	 * @return unknown_type
	 */
	private function createEventAlert($id_event, $day, $hour, $minute, $email, $id_user)
	{
		global $babDB;
		
		if (empty($GLOBALS['BAB_SESS_USERID']))
		{
			return;
		}
		
		$email = $email ? 'Y' : 'N';
		
		
		$babDB->db_query("
			INSERT INTO ".BAB_CAL_EVENTS_REMINDERS_TBL." 
				(
					id_event, 
					id_user, 
					day, 
					hour, 
					minute, 
					bemail 
				) 
			VALUES 
				(
					'".$babDB->db_escape_string($id_event)."', 
					'".$babDB->db_escape_string($id_user)."', 
					'".$babDB->db_escape_string($day)."', 
					'".$babDB->db_escape_string($hour)."', 
					'".$babDB->db_escape_string($minute)."', 
					'".$babDB->db_escape_string($email)."'
				)
		");
	}
	
	

	
	
	
	/**
	 * Attach personal calendars from attendees, work also for event modification
	 * @param bab_CalendarPeriod $period
	 * @param int $id_event
	 * @return unknown_type
	 */
	private function applyAttendees(bab_CalendarPeriod $period, $id_event)
	{
		$associated = $this->getAssociatedCalendars($id_event);
		
		foreach($period->getAttendees() as $attendee)
		{
			$calendar = $attendee['calendar'];
			$id_user = $calendar->getIdUser();
			
			if (($calendar instanceof bab_OviEventCalendar) && $id_user)
			{
				$status = 'TRUE' === $attendee['RSVP'] ? BAB_CAL_STATUS_NONE : BAB_CAL_STATUS_ACCEPTED;
				$id_calendar = $calendar->getUid();
				
				if (isset($associated[$id_calendar]))
				{
					if ($status !== $associated[$id_calendar])
					{
						$this->updateCalendarStatus($id_event, $id_calendar, $status);
					}
				}
				else
				{
					$this->addCalendar($id_event, $id_calendar, $status);
				} 
			}
		}
	}
	
	
	
	
	
	
	
	/**
	 * Attach public and ressource calendars from relations, work also for event modification
	 * @param bab_CalendarPeriod $period
	 * @param int $id_event
	 * @return unknown_type
	 */
	private function applyRelations(bab_CalendarPeriod $period, $id_event)
	{
		$associated = $this->getAssociatedCalendars($id_event);
		$calendars = array_merge($period->getRelations('PARENT'), $period->getRelations('CHILD'));
		
		foreach($calendars as $calendar)
		{			
			if ($calendar instanceof bab_OviEventCalendar)
			{
				$status = BAB_CAL_STATUS_ACCEPTED;
				$id_calendar = $calendar->getUid();
				
				if (isset($associated[$id_calendar]))
				{
					if ($status !== $associated[$id_calendar])
					{
						$this->updateCalendarStatus($id_event, $id_calendar, $status);
					}
				}
				else
				{
					$this->addCalendar($id_event, $id_calendar, $status);
				} 
			}
		}
	}
	
	
	
	
	
	
	
	
	
	
	
	
	/**
	 * 
	 * @param int $id_event
	 * @return array
	 */
	private function getAssociatedCalendars($id_event)
	{
		global $babDB;
		
		$associated = array();
		$res = $babDB->db_query('
			SELECT id_cal, status FROM '.BAB_CAL_EVENTS_OWNERS_TBL.' WHERE id_event='.$babDB->quote($id_event).'
		');
		
		$associated = array();
		while ($arr = $babDB->db_fetch_assoc($res)) {
			$associated[$arr['id_cal']] = $arr['status'];
		}
		
		return $associated;
	}
	
	
	
	
	/**
	 * 
	 * @param unknown_type $id_event
	 * @param unknown_type $id_calendar
	 * @param unknown_type $status
	 * @return unknown_type
	 */
	private function addCalendar($id_event, $id_calendar, $status)
	{
		global $babDB;
		
		$babDB->db_query("
			INSERT INTO ".BAB_CAL_EVENTS_OWNERS_TBL." 
				(
					id_event,
					id_cal, 
					status
				) 
			VALUES 
				(
					'".$babDB->db_escape_string($id_event)."',
					'".$babDB->db_escape_string($id_calendar)."', 
					'".$babDB->db_escape_string($status)."'
				)
		");
	}
	
	
	/**
	 * Update status
	 * @param unknown_type $id_event
	 * @param unknown_type $id_calendar
	 * @param unknown_type $status
	 * @return unknown_type
	 */
	private function updateCalendarStatus($id_event, $id_calendar, $status)
	{
		global $babDB;
		
		$babDB->db_query("
			UPDATE ".BAB_CAL_EVENTS_OWNERS_TBL." 
				SET 
					status = ".$babDB->quote($status)." 
					
			WHERE 
				id_event=".$babDB->quote($id_event)." 
				AND id_cal=".$babDB->quote($id_calendar)."
		");
	}
}