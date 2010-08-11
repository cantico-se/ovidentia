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
class bab_cal_OviEventUpdate
{	
	
	private $RRULE = false;
	
	
	/**
	 * Save of update a calendar period
	 * @param bab_CalendarPeriod $period
	 * 
	 * @throws Exception
	 * 
	 * @return unknown_type
	 */
	public static function save(bab_CalendarPeriod $period)
	{
		$manager = bab_getInstance('bab_cal_OviEventUpdate');
		/*@var $manager bab_cal_OviEventUpdate */
		
		$uid = $period->getProperty('UID');
		
		if ($uid)
		{
			// modification
			$id_event = $manager->getEventByUid($uid);
			// TODO remove the third parameter
			$manager->updateEvent($id_event, $period);
		}
		else
		{
			require_once dirname(__FILE__).'/cal.rrule.class.php';
			$RRULE = bab_getInstance('bab_CalendarRRULE');
			
			
			// creation
			$hash = $RRULE->applyRrule($period);

			$manager->insertCollection($period, $hash);
		}
		
		return true;
	}
	
	
	/**
	 * Expand period to collection with the RRULE and return collection
	 * @param bab_CalendarPeriod $period
	 * @return bab_PeriodCollection
	 */
	public static function getCollection(bab_CalendarPeriod $period)
	{
		$manager = bab_getInstance('bab_cal_OviEventUpdate');
		/*@var $manager bab_cal_OviEventUpdate */
		
		$manager->applyRrule($period);
		
		return $period->getCollection();
	}
	
	
	
	
	/**
	 * Get event by UID property
	 * 
	 * @throws Exception
	 * 
	 * @param string $uid
	 * @return int
	 */
	private function getEventByUid($uid)
	{
		global $babDB;
		
		$res = $babDB->db_query('SELECT id FROM '.BAB_CAL_EVENTS_TBL.' WHERE uuid='.$babDB->quote($uid));
		
		if (0 === $babDB->db_num_rows($res))
		{
			throw new Exception('The event does not exists in ovidentia database');
		}
		
		if (1 !== $babDB->db_num_rows($res))
		{
			throw new Exception('there are more than one event for this UID');
		}
		
		$arr = $babDB->db_fetch_assoc($res);
		
		return (int) $arr['id'];
	}
	
	
	
	
	
	/**
	 * Update event
	 * 
	 * 
	 * @param	int					$id_event						Event currently in database
	 * @param	bab_CalendarPeriod	$period							The posted period with same UID
	 * 
	 * 																if multiple events are linked with a hash
	 * 
	 * @throws ErrorException
	 * @return unknown_type
	 */
	private function updateEvent($id_event, bab_CalendarPeriod $period)
	{
		require_once dirname(__FILE__).'/evtincl.php';
		global $babBody, $babDB;
		
		$evtinfo = $babDB->db_fetch_assoc($babDB->db_query("
			SELECT hash, start_date, end_date from ".BAB_CAL_EVENTS_TBL." where id='".$babDB->db_escape_string($id_event)."'
		"));
	
		$arrupdate = array();

		if( $period->ts_begin >= $period->ts_end )
			{
			throw new ErrorException(bab_translate("End date must be older"));
			return false;
			}
			
		$startdate 	= date('Y-m-d H:i:s', $period->ts_begin);
		$enddate 	= date('Y-m-d H:i:s', $period->ts_end);


		$arrupdate[$id_event] = array('start'=> $startdate, 'end' => $enddate);
		$min = $startdate;
		$max = $enddate;
	
		reset($arrupdate);
		
		
		$data = $period->getData();
		$private = 'PUBLIC' !== $period->getProperty('CLASS') ? 'Y' : 'N';
		$free = 'TRANSPARENT' === $period->getProperty('TRANSP') ? 'Y' : 'N';
		
		$req = "UPDATE ".BAB_CAL_EVENTS_TBL." 
		SET 
			title				=".$babDB->quote($period->getProperty('SUMMARY')).", 
			description			=".$babDB->quote($data['description']).", 
			description_format	=".$babDB->quote($data['description_format']).", 
			location			=".$babDB->quote($period->getProperty('LOCATION')).", 
			id_cat				=".$babDB->quote((int) bab_getCalendarCategory($period->getProperty('CATEGORIES'))).", 
			color				=".$babDB->quote($period->getColor()).", 
			bprivate			=".$babDB->quote($private).", 
			block				=".$babDB->quote($data['block']).", 
			bfree				=".$babDB->quote($free).",
			date_modification	=now(),
			id_modifiedby		=".$babDB->quote($GLOBALS['BAB_SESS_USERID'])."
		";
		

			
		$modification = false;
		
		foreach($arrupdate as $key => $val)
		{
			$query = $req.", start_date=".$babDB->quote($val['start']).", end_date=".$babDB->quote($val['end'])." where id=".$babDB->quote($key)."";
			$babDB->db_query($query);
			
			if (0 !== $babDB->db_affected_rows())
			{
				$modification = true;
			}
			
			
			$min = $val['start'] < $min ? $val['start'] : $min;
			$max = $val['end']	 > $max ? $val['end'] 	: $max;

			
			$exclude = $this->applyAttendees($period, $id_event);
			$exclude += $this->applyRelations($period, $id_event);
			$this->cleanupCalendars($period, $id_event);
		}
		
		
		// update reminder by VALARM
		
		
		$this->applyAlarm($period, $id_event);
	
		
		if ($modification)
		{

			// the exlude array contain new calendar in event
			// they do not need a notification
		
			notifyEventUpdate($id_event, false, $exclude);
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

				
				foreach($alarm->getAttendees() as $attendee)
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
		
		
		$res = $babDB->db_query('SELECT id_event FROM '.BAB_CAL_EVENTS_REMINDERS_TBL.' WHERE id_event='.$babDB->quote($id_event).' AND id_user='.$babDB->quote($id_user));
		if ($arr = $babDB->db_fetch_assoc($res))
		{
			
			$babDB->db_query("
				UPDATE ".BAB_CAL_EVENTS_REMINDERS_TBL." 
					SET
						day=".$babDB->quote($day).", 
						hour=".$babDB->quote($hour).",  
						minute=".$babDB->quote($minute).",  
						bemail=".$babDB->quote($email).",  
				WHERE 
					id_event=".$babDB->quote($id_event)." 
					AND id_user = ".$babDB->quote($id_user)." 
			");
			
			
		} else {
		
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
	}
	
	

	
	
	
	/**
	 * Attach personal calendars from attendees, work also for event modification
	 * @param bab_CalendarPeriod $period
	 * @param int $id_event
	 * @return array			list of added calendars
	 */
	private function applyAttendees(bab_CalendarPeriod $period, $id_event)
	{
		$return = array();
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
					$this->addCalendar($period, $id_event, $calendar, $status);
					$return[$id_calendar] = $id_calendar;
				} 
			}
		}
		
		return $return;
	}
	
	
	
	
	
	
	
	/**
	 * Attach public and ressource calendars from relations, work also for event modification
	 * @param bab_CalendarPeriod $period
	 * @param int $id_event
	 * @return unknown_type
	 */
	private function applyRelations(bab_CalendarPeriod $period, $id_event)
	{
		$return = array();
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
					$this->addCalendar($period, $id_event, $calendar, $status);
					$return[$id_calendar] = $id_calendar;
				} 
			}
		}
		
		return $return;
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
	 * Remove orphans calendars links from database
	 * 
	 * @param	bab_CalendarPeriod	$period
	 * @param	int					$id_event
	 * 
	 * @return unknown_type
	 */
	private function cleanupCalendars(bab_CalendarPeriod $period, $id_event)
	{
		global $babDB;
		
		$arrperiod = array();
		$arrcals = $period->getCalendars();
		foreach($arrcals as $calendar)
		{
			$arrperiod[] = $calendar->getUid();
		}
		
		$babDB->db_query("delete from ".BAB_CAL_EVENTS_OWNERS_TBL." where id_event=".$babDB->quote($id_event)." AND id_cal NOT IN(".$babDB->quote($arrperiod).")");
		
		if( count($arrcals) == 0 )
		{
			$babDB->db_query("delete from ".BAB_CAL_EVENTS_TBL." where id=".$babDB->quote($id_event));
		}
	}
	
	
	
	
	
	
	
	/**
	 * Add a calendar to event
	 * 
	 * @param bab_CalendarPeriod	$period
	 * @param int					$id_event
	 * @param bab_EventCalendar 	$id_calendar
	 * @param int 					$status
	 * 
	 * @return array
	 */
	private function addCalendar(bab_CalendarPeriod $period, $id_event, bab_EventCalendar $calendar, $status)
	{
		require_once dirname(__FILE__).'/evtincl.php';
		require_once dirname(__FILE__).'/calincl.php';
		global $babDB;
		
		$id_calendar = $calendar->getUid();
		if($idsa = $calendar->getApprobationSheme())
		{
			include_once $GLOBALS['babInstallPath']."utilit/afincl.php";
			$idfai = makeFlowInstance($idsa, "cal-".$id_calendar."-".$id_event);
		} 
		else 
		{
			$idfai = 0;
		}
		
		$babDB->db_query("
			INSERT INTO ".BAB_CAL_EVENTS_OWNERS_TBL." 
				(
					id_event,
					id_cal, 
					status,
					idfai
				) 
			VALUES 
				(
					'".$babDB->db_escape_string($id_event)."',
					'".$babDB->db_escape_string($id_calendar)."', 
					'".$babDB->db_escape_string($status)."',
					'".$babDB->db_escape_string($idfai)."'
				)
		");
		
		
		if( $idfai )
		{
			// approbation instance, notify approvers
			
			$nfusers = getWaitingApproversFlowInstance($idfai, true);
			notifyEventApprovers($id_event, $nfusers, $calendar);
		}
		else 
		{
			
			$arr = bab_getCalendarOwnerAndType($id_calendar);

			// if new calendar in event, notify new appointement 
			
			cal_notify(
				$period->getProperty('SUMMARY'), 
				$period->getProperty('DESCRIPTION'), 
				$period->getProperty('LOCATION'), 
				bab_longDate($period->ts_begin), 
				bab_longDate($period->ts_end), 
				$calendar, 
				$arr['type'], 
				$arr['owner'],
				bab_translate("New appointement")
			);
		}
	}
	
	
	/**
	 * Update calendar status into event
	 * 
	 * @param int 	$id_event
	 * @param int	$id_calendar
	 * @param int 	$status
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




























/**
 * Select events in database for CalendarBackend/Ovi
 * process data into regular bab_CalendarPeriod objects
 */
class bab_cal_OviEventSelect
{




	/**
	 * Create a calendar period from a calendar event of database
	 * 
	 * @param	array	$arr
	 * 
	 * @return bab_CalendarEvent
	 */
	private function createCalendarPeriod($arr, bab_PeriodCollection $collection)
	{
		require_once dirname(__FILE__).'/dateTime.php';
		
		
		global $babDB;
		$event = new bab_calendarPeriod(bab_mktime($arr['start_date']), bab_mktime($arr['end_date']));
		$collection->addPeriod($event);
			
		include_once $GLOBALS['babInstallPath']."utilit/editorincl.php";
		$editor = new bab_contentEditor('bab_calendar_event');
		$editor->setContent($arr['description']);
		$editor->setFormat($arr['description_format']);
		$arr['description']	= $editor->getHtml();
	
		$event->setProperty('UID'			, $arr['uuid']);
		$event->setProperty('DTSTART'		, $arr['start_date']);
		$event->setProperty('DTEND'			, $arr['end_date']);
		$event->setProperty('SUMMARY'		, $arr['title']);
		$event->setProperty('DESCRIPTION'	, $arr['description']);
		$event->setProperty('LOCATION'		, $arr['location']);
		$event->setProperty('CATEGORIES'	, $arr['category']);
		
		$color = !empty($arr['bgcolor']) ? $arr['bgcolor'] : $arr['color'];
		$event->setColor($color);
		
		
		if ('Y' == $arr['bprivate']) {
			$event->setProperty('CLASS'	, 'PRIVATE');
		} else {
			$event->setProperty('CLASS'	, 'PUBLIC');
		}
		
		if ('Y' == $arr['bfree']) {
			$event->setProperty('TRANSP'	, 'TRANSPARENT');
		} else {
			$event->setProperty('TRANSP'	, 'OPAQUE');
		}
		
		if (!empty($arr['hash']))
		{
			$collection->hash = $arr['hash'];
		}
		
		$event->setProperty('LAST-MODIFIED', BAB_DateTime::fromIsoDateTime($arr['date_modification'])->getICal(true));
		
	
		
		
	
		if (!empty($arr['alert'])) {
			
			$backend = bab_functionality::get('CalendarBackend');
			$alarm = $backend->CalendarAlarm();
			$event->setAlarm($alarm);
		}
		
		
		
		
	
	
		$resco = $babDB->db_query("
		
			SELECT 
				o.id_cal, c.type, c.owner, o.idfai, o.status    
			FROM 
				".BAB_CAL_EVENTS_OWNERS_TBL." o, 
				".BAB_CALENDAR_TBL." c  
			WHERE 
				o.id_event ='".$babDB->db_escape_string($arr['id'])."' 
				AND c.id = o.id_cal 
			");
	
		while( $arr2 = $babDB->db_fetch_array($resco)) {
		
			switch($arr2['status'])
			{
				case BAB_CAL_STATUS_NONE:
					$partstat = 'NEEDS-ACTION';
					$rsvp = 'TRUE';
					break;	
				case BAB_CAL_STATUS_ACCEPTED:
					$partstat = 'ACCEPTED';
					$rsvp = 'FALSE';
					break;
				case BAB_CAL_STATUS_DECLINED:
					$partstat = 'DECLINED';	
					$rsvp = 'FALSE';
					break;
			}
			
			
			
			
			switch($arr2['type'])
			{
				case BAB_CAL_USER_TYPE:
					$idcal = 'personal/'.$arr2['id_cal'];
					break;
				case BAB_CAL_PUB_TYPE:
					$idcal = 'public/'.$arr2['id_cal'];
					break;
				case BAB_CAL_RES_TYPE:
					$idcal = 'ressource/'.$arr2['id_cal'];
					break;
			}
		
			
			$calendar = bab_getICalendars()->getEventCalendar($idcal);
			
			if (!isset($calendar))
			{
				throw new Exception('This calendar is not accessible '.$arr2['id_cal']);
			}
			
			
			
			
		
			if (BAB_CAL_USER_TYPE === (int) $arr2['type']) {
				if ($arr2['id_cal'] == $arr['id_cal']) {
					// main personal calendar 
					$role = 'CHAIR';
					
					// set as organizer
					$event->setProperty('ORGANIZER;CN='.$calendar->getName(), 'MAILTO:'.bab_getUserEmail($calendar->getIdUser()));
					
					// set as main calendar in collection
					$collection->setCalendar($calendar);
					
				} else {
					$role = 'REQ-PARTICIPANT';
				}
				
				$event->addAttendee($calendar, $role, $partstat, $rsvp);
				
				if (isset($alarm))
				{
					$alarm->addAttendee($calendar);
				}
				
			} else {
				if ($arr2['id_cal'] == $arr['id_cal']) {
					// main calendar 
					$event->addRelation('PARENT', $calendar);
					
					// set as main calendar in collection
					$collection->setCalendar($calendar);
					
				} else {
					$event->addRelation('CHILD', $calendar);
				}
			}
		}
			
		
		
		
		// add VALARM infos
		
		
		$resa = $babDB->db_query('SELECT 
					day, 
					hour, 
					minute, 
					bemail,
					id_user   
					
				FROM '.BAB_CAL_EVENTS_REMINDERS_TBL.'
				
				WHERE id_event = '.$babDB->quote($arr['id']).'	
		');
		
		
		while($reminder = $babDB->db_fetch_assoc($resa))
		{
			$day = (int) $reminder['day'];
			$hour = (int) $reminder['hour'];
			$minute = (int) $reminder['minute'];
			$email = 'Y' === $reminder['bemail'];
			
			require_once dirname(__FILE__).'/evtincl.php';
			bab_setAlarmProperties($alarm, $event, $day, $hour, $minute, $email);
			break;
		}
		
		
		unset($arr['id']);
		unset($arr['id_event']);
		unset($arr['id_cal']);
		unset($arr['id_cat']);
		unset($arr['start_date']);
		unset($arr['end_date']);
		unset($arr['title']);
		unset($arr['location']);
		unset($arr['category']);
		unset($arr['bprivate']);
		unset($arr['bfree']);
		unset($arr['color']);
		unset($arr['bgcolor']);
		unset($arr['uuid']);
		unset($arr['hash']);
		unset($arr['date_modification']);
		unset($arr['alert']);
		
		$event->setData($arr);
		
		
		return $event;
	}
	
	
	/**
	 * 
	 * @param string $where
	 * @return string
	 */
	private function getQuery($where)
	{
		global $babDB;
		
		$query = "
			SELECT 
				ceo.*, 
				ce.*,
				ca.name category,
				ca.bgcolor, 
				er.id_event alert, 
				en.note 
			FROM 
				".BAB_CAL_EVENTS_OWNERS_TBL." ceo 
				LEFT JOIN ".BAB_CAL_EVENTS_TBL." ce ON ceo.id_event=ce.id 
				LEFT JOIN ".BAB_CAL_CATEGORIES_TBL." ca ON ca.id = ce.id_cat 
				LEFT JOIN ".BAB_CAL_EVENTS_REMINDERS_TBL." er ON er.id_event=ce.id AND er.id_user=".$babDB->quote($GLOBALS['BAB_SESS_USERID'])." 
				LEFT JOIN ".BAB_CAL_EVENTS_NOTES_TBL." en ON en.id_event=ce.id AND en.id_user=".$babDB->quote($GLOBALS['BAB_SESS_USERID'])."
	
			WHERE 
				".$where." 
			ORDER BY 
				ce.start_date asc 
		";
		
		return $query;
	}
	
	
	
	
	
	/**
	 * set calendar events into userperiods object
	 * 
	 *  
	 * @param bab_UserPeriods				$user_periods		query result set
	 * @param array							$calendars			<bab_EventCalendar>
	 * @param array|NULL					[$category]
	 */
	public function setEventsPeriods(bab_UserPeriods $user_periods, Array $calendars, $category = NULL, $hash = null) {
	
		global $babDB;
		
		$begin 	= $user_periods->begin;
		$end 	= $user_periods->end;
		
		$backend = bab_functionality::get('CalendarBackend/Ovi');
		
		$id_calendars = array();
		$collections = array();
		
		foreach($calendars as $calendar) {
			
			if ($calendar instanceof bab_OviEventCalendar) {
				$id = $calendar->getUid();
				$id_calendars[] = $id;
				$collections[$id] = $backend->CalendarEventCollection();
				$collections[$id]->setCalendar($calendar);
			}
		}


		$where = " 
			ceo.id_cal			IN(".$babDB->quote($id_calendars).") 
			AND ceo.status		!= '".BAB_CAL_STATUS_DECLINED."' 
		";

		if (isset($end)) {
			$where .= "AND ce.start_date	<= '".$babDB->db_escape_string($end->getIsoDateTime())."' 
			";
		}
		
		if (isset($begin)) {
			$where .= "AND ce.end_date		>= '".$babDB->db_escape_string($begin->getIsoDateTime())."' 
			";
		}
		
		if (NULL !== $category) {
			$where .= "AND ca.name IN(".$babDB->quote($category).") 
			";
		}
		
		if (null !== $hash) {
			$where .= "AND ce.hash =".$babDB->quote($hash)." 
			";
		}
		
		$query = $this->getQuery($where);
		$res = $babDB->db_query($query);
		
		while( $arr = $babDB->db_fetch_assoc($res))
		{
			$event = $this->createCalendarPeriod($arr, $collections[$arr['id_cal']]);
			$user_periods->addPeriod($event);
		}
	
	}


	/**
	 * Select one event by ical property UID
	 * 
	 * @param	string $uid
	 * @return bab_CalendarPeriod
	 */
	public function getFromUid($uid)
	{
		global $babDB;
		
		$query = $this->getQuery(" 
			ce.uuid = ".$babDB->quote($uid)." 
		");
		
		$res = $babDB->db_query($query);
		$arr = $babDB->db_fetch_assoc($res);
		
		if (!$arr)
		{
			return null;
		}
		
		$backend = bab_functionality::get('CalendarBackend/Ovi');
		$collection = $backend->CalendarEventCollection(); 
		
		return $this->createCalendarPeriod($arr, $collection);
	}
	
	
	
	/**
	 * 
	 * @param string $uid
	 * @return bool
	 */
	public function deleteFromUid($uid)
	{
		global $babDB;
		
		
		$query = $this->getQuery(" 
			ce.uuid = ".$babDB->quote($uid)." 
		");
		
		$res = $babDB->db_query($query);
		$arr = $babDB->db_fetch_assoc($res);
		
		if (!$arr)
		{
			return false;
		}
		
		include_once $GLOBALS['babInstallPath'].'utilit/afincl.php';
		$id_event = $arr['id_event'];
		
		
		$babDB->db_query("delete from ".BAB_CAL_EVENTS_TBL." where id='".$babDB->db_escape_string($id_event)."'");
		$res2 = $babDB->db_query("select idfai from ".BAB_CAL_EVENTS_OWNERS_TBL." where id_event='".$babDB->db_escape_string($id_event)."'");
		while( $rr = $babDB->db_fetch_array($res2) )
			{
			if( $rr['idfai'] != 0 )
				{
				deleteFlowInstance($rr['idfai']);
				}
			}
		$babDB->db_query("delete from ".BAB_CAL_EVENTS_OWNERS_TBL." where id_event='".$babDB->db_escape_string($id_event)."'");
		$babDB->db_query("delete from ".BAB_CAL_EVENTS_NOTES_TBL." where id_event='".$babDB->db_escape_string($id_event)."'");
		$babDB->db_query("delete from ".BAB_CAL_EVENTS_REMINDERS_TBL." where id_event='".$babDB->db_escape_string($id_event)."'");
		
		return true;
	}
	
	
	
	/**
	 * add periods from ovidentia backend to the userperiods object from the query informations stored in the userperiods object
	 * 
	 * @param bab_UserPeriods $userperiods
	 * @return unknown_type
	 */
	public function processQuery(bab_UserPeriods $userperiods)
	{
		require_once dirname(__FILE__).'/cal.periodcollection.class.php';
		require_once dirname(__FILE__).'/workinghoursincl.php';
		
		$calendars = $userperiods->calendars;
		$users = $userperiods->getUsers();
		$begin = $userperiods->begin;
		$end = $userperiods->end;
		$hash = $userperiods->hash;
		
		$backend = bab_functionality::get('CalendarBackend/Ovi');
		
		$vac_collection	= $backend->VacationPeriodCollection();
		$evt_collection = $backend->CalendarEventCollection();
		$tsk_collection = $backend->TaskCollection();
		$wp_collection 	= $backend->WorkingPeriodCollection();
		$nwp_collection = $backend->NonWorkingPeriodCollection();
		
		
		if ($userperiods->isPeriodCollection($vac_collection) && $users) {
			include_once $GLOBALS['babInstallPath']."utilit/vacincl.php";
			bab_vac_setVacationPeriods($vac_collection, $userperiods, $users);
		}
	
		if ($userperiods->isPeriodCollection($evt_collection)) {
			include_once $GLOBALS['babInstallPath']."utilit/calincl.php";
			
			$ical = $userperiods->icalProperties;
			$categories = null;
			if (isset($ical['CATEGORIES'])) {
				$categories = $ical['CATEGORIES'];
			}
			
			$this->setEventsPeriods($userperiods, $calendars, $categories, $hash); 
		}
	
		if ($userperiods->isPeriodCollection($tsk_collection) && $users) {
			include_once $GLOBALS['babInstallPath']."utilit/tmdefines.php";
			include_once $GLOBALS['babInstallPath']."utilit/tmIncl.php";
			bab_tskmgr_setPeriods($tsk_collection, $userperiods, $users);
		}
	
	
		if (!isset($begin) || !isset($end))
		{
			return;
		}
		
		$loop = $begin->cloneDate();
		$endts = $end->getTimeStamp() + 86400;
		$begints = $begin->getTimeStamp();
		$working = $userperiods->isPeriodCollection($wp_collection);
		$nworking = $userperiods->isPeriodCollection($nwp_collection);
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
								$userperiods->addPeriod($p);
							}
		
							$p = new bab_calendarPeriod($begin->getTimeStamp(), $end->getTimeStamp());
		
							$p->setProperty('SUMMARY'		, bab_translate('Working period'));
							$p->setProperty('DTSTART'		, $begin->getIsoDateTime());
							$p->setProperty('DTEND'			, $end->getIsoDateTime());
							$p->setData(array('id_user' => $id_user));
							$p->available = true;
							
							$wp_collection->addPeriod($p);
							$userperiods->addPeriod($p);
		
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
			$userperiods->addPeriod($p);
		}
	}
}
