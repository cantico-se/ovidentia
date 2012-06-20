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

		$create = true;
		if ($uid)
		{
			$create = false;

			// modification
			try {
				$id_event = $manager->getEventByUid($uid);
			} catch(Exception $e)
			{
				//throw new Exception('the event have an UID but does not exists in table');
				$create = true;
			}
		}



		if (!$create)
		{
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
		$private = !$period->isPublic() ? 'Y' : 'N';
		$free = 'TRANSPARENT' === $period->getProperty('TRANSP') ? 'Y' : 'N';
		$block = $data['block'] ? 'Y' : 'N';

		$cat = bab_getCalendarCategory($period->getProperty('CATEGORIES'));

		$req = "UPDATE ".BAB_CAL_EVENTS_TBL."
		SET
			title				=".$babDB->quote($period->getProperty('SUMMARY')).",
			description			=".$babDB->quote($data['description']).",
			description_format	=".$babDB->quote($data['description_format']).",
			location			=".$babDB->quote($period->getProperty('LOCATION')).",
			id_cat				=".$babDB->quote((int) $cat['id']).",
			color				=".$babDB->quote($period->getProperty('X-CTO-COLOR')).",
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


			$this->applyAttendees($period, $id_event);
			$this->applyRelations($period, $id_event);
			$this->cleanupCalendars($period, $id_event);
		}


		// update reminder by VALARM


		$this->applyAlarm($period, $id_event);

		return true;
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
		if (!$period->isPublic()) {
			$private = 'Y';
		}

		$free = 'N';
		if ('TRANSPARENT' === $period->getProperty('TRANSP')) {
			$free = 'Y';
		}

		$data = $period->getData();

		// if no UID set a new UID to insert
		if (!$period->getProperty('UID'))
		{
			$period->setProperty('UID', bab_uuid());
		}

		$block = 'N';

		if (isset($data['block']))
		{
			$block = $data['block'];
		}




		$hash = (string) $hash;

		$babDB->db_query("
			insert into ".BAB_CAL_EVENTS_TBL."
				(
					title,
					description,
					description_format,
					location,
					start_date,
					end_date,
					id_cat,
					id_creator,
					color,
					bprivate,
					block,
					bfree,
					hash,
					date_modification,
					id_modifiedby,
					uuid,
					parent_calendar
				)

			values (
				".$babDB->quote($period->getProperty('SUMMARY')).",
				".$babDB->quote($data['description']).",
				".$babDB->quote($data['description_format']).",
				".$babDB->quote($period->getProperty('LOCATION')).",
				".$babDB->quote(BAB_DateTime::fromICal($period->getProperty('DTSTART'))->getIsoDateTime()).",
				".$babDB->quote(BAB_DateTime::fromICal($period->getProperty('DTEND'))->getIsoDateTime()).",
				".$babDB->quote($category).",
				".$babDB->quote($id_owner).",
				".$babDB->quote($period->getProperty('X-CTO-COLOR')).",
				".$babDB->quote($private).",
				".$babDB->quote($block).",
				".$babDB->quote($free).",
				".$babDB->quote($hash).",
				now(),
				".$babDB->quote($id_owner).",
				".$babDB->quote($period->getProperty('UID')).",
				".$babDB->quote($calendar->getUrlIdentifier())."
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
						bemail=".$babDB->quote($email)."
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


			if (($calendar instanceof bab_PersonalCalendar))
			{
				$id_user = $calendar->getIdUser();

				switch($attendee['PARTSTAT'])
				{
					case 'ACCEPTED':
						$status = BAB_CAL_STATUS_ACCEPTED;
						break;

					case 'DECLINED':
						$status = BAB_CAL_STATUS_DECLINED;
						break;

					default:
					case 'NEEDS-ACTION':
						$status = BAB_CAL_STATUS_NONE;
						break;
				}



				$id_calendar = $calendar->getUid();

				if (isset($associated[$id_calendar]))
				{
					if ($status !== $associated[$id_calendar]['status'])
					{
						$this->updateCalendarStatus($id_event, $calendar, $status, 0);
					}
				}
				else
				{
					$this->addCalendar($period, $id_event, $calendar, $attendee['PARTSTAT'], 0);
					$return[$id_calendar] = $id_calendar;
				}
			}
		}

		return $return;
	}







	/**
	 * Attach public and resource calendars from relations, work also for event modification
	 * @param bab_CalendarPeriod $period
	 * @param int $id_event
	 * @return unknown_type
	 */
	private function applyRelations(bab_CalendarPeriod $period, $id_event)
	{
		$return = array();
		$associated = $this->getAssociatedCalendars($id_event);
		$calendars = $period->getRelations();

		foreach($calendars as $relation)
		{
			$calendar = $relation['calendar'];
			if (($calendar instanceof bab_OviPublicCalendar) || ($calendar instanceof bab_OviResourceCalendar))
			{
				$id_calendar = $calendar->getUid();

				if (isset($associated[$id_calendar]))
				{
					if (!isset($relation['X-CTO-STATUS']))
					{
						bab_debug('Missing the X-CTO-STATUS attribute in the RELATED-TO of calendar '.$calendar->getUrlIdentifier());
					}

					switch($relation['X-CTO-STATUS'])
					{
						case 'ACCEPTED':
							$status = BAB_CAL_STATUS_ACCEPTED;
							$wfinstance = 0;
							break;

						case 'DECLINED':
							$status = BAB_CAL_STATUS_DECLINED;
							$wfinstance = 0;
							break;

						case 'NEEDS-ACTION':
							$status = BAB_CAL_STATUS_NONE;
							$wfinstance = (int) $relation['X-CTO-WFINSTANCE'];
							break;
					}


					if (isset($status) && ($status !== $associated[$id_calendar]['status'] || $wfinstance !== $associated[$id_calendar]['idfai']))
					{
						$this->updateCalendarStatus($id_event, $calendar, $status, $wfinstance);
					}
				}
				else
				{
					$this->addCalendar($period, $id_event, $calendar, $relation['X-CTO-STATUS'], $relation['X-CTO-WFINSTANCE']);
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
			SELECT id_cal, status, idfai FROM '.BAB_CAL_EVENTS_OWNERS_TBL.' WHERE id_event='.$babDB->quote($id_event).'
		');

		$associated = array();
		while ($arr = $babDB->db_fetch_assoc($res)) {
			$associated[$arr['id_cal']] = array(
					'status' => (int) $arr['status'],
					'idfai' => (int) $arr['idfai']
			);
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
	 * @param string				$status			NEEDS-ACTION | ACCEPTED | DECLINED
	 * @param int					$wfinstance
	 *
	 * @return array
	 */
	private function addCalendar(bab_CalendarPeriod $period, $id_event, bab_EventCalendar $calendar, $status, $wfinstance)
	{
		require_once dirname(__FILE__).'/evtincl.php';
		require_once dirname(__FILE__).'/calincl.php';
		global $babDB;


		$backend = $calendar->getBackend()->getUrlIdentifier();
		$caltype = $calendar->getReferenceType();
		$id_calendar = $calendar->getUid();


		switch($status)
		{
			case 'NEEDS-ACTION':
				$status = BAB_CAL_STATUS_NONE;
				break;

			case 'ACCEPTED':
				$status = BAB_CAL_STATUS_ACCEPTED;
				break;

			case 'DECLINED':
				$status = BAB_CAL_STATUS_DECLINED;
				break;
		}

		$query = "
			INSERT INTO ".BAB_CAL_EVENTS_OWNERS_TBL."
				(
					id_event,
					calendar_backend,
					caltype,
					id_cal,
					status,
					idfai
				)
			VALUES
				(
					'".$babDB->db_escape_string((int) $id_event)."',
					'".$babDB->db_escape_string($backend)."',
					'".$babDB->db_escape_string($caltype)."',
					'".$babDB->db_escape_string((int) $id_calendar)."',
					'".$babDB->db_escape_string((int) $status)."',
					'".$babDB->db_escape_string((int) $wfinstance)."'
				)
		";
		$babDB->db_query($query);



	}


	/**
	 * Update calendar status into event
	 *
	 * @param 	int 				$id_event
	 * @param 	bab_EventCalendar	$calendar
	 * @param 	int 				$status
	 * @param	int					$wfinstance
	 * @return unknown_type
	 */
	private function updateCalendarStatus($id_event,bab_EventCalendar $calendar, $status, $wfinstance)
	{
		global $babDB;

		$caltype = $calendar->getReferenceType();
		$id_calendar = $calendar->getUid();
		$backend = $calendar->getBackend()->getUrlIdentifier();

		$babDB->db_query("
			UPDATE ".BAB_CAL_EVENTS_OWNERS_TBL."
				SET
					status = ".$babDB->quote($status).",
					idfai = ".$babDB->quote($wfinstance)."
			WHERE
				id_event=".$babDB->quote($id_event)."
				AND calendar_backend=".$babDB->quote($backend)."
				AND caltype=".$babDB->quote($caltype)."
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


		$begin = BAB_DateTime::fromIsoDateTime($arr['start_date']);
		$end = BAB_DateTime::fromIsoDateTime($arr['end_date']);

		$event = new bab_calendarPeriod();
		$event->setDates($begin, $end);
		$collection->addPeriod($event);

		include_once $GLOBALS['babInstallPath']."utilit/editorincl.php";
		$editor = new bab_contentEditor('bab_calendar_event');
		$editor->setContent($arr['description']);
		$editor->setFormat($arr['description_format']);
		$arr['description']	= $editor->getHtml();


		if (!$arr['uuid'])
		{
			throw new Exception('Invalid event, no UID property');
			return null;
		}


		$event->setProperty('UID'													, $arr['uuid']);
		$event->setProperty('SUMMARY'												, $arr['title']);
		$event->setProperty('DESCRIPTION'											, trim(bab_unhtmlentities(strip_tags($arr['description']))));
		$event->setProperty('LOCATION'												, $arr['location']);
		$event->setProperty('CATEGORIES'											, $arr['category']);
		$event->setProperty('X-CTO-COLOR'											, $arr['color']);
		$event->setProperty('ORGANIZER;CN='.bab_getUserName($arr['id_creator'])		, 'MAILTO:'.bab_getUserEmail($arr['id_creator']));


		if ('Y' == $arr['bprivate']) {
			$event->setProperty('CLASS'	, 'PRIVATE');
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
				o.id_cal,
				o.idfai,
				o.status,
				o.caltype,
				c.owner,
				c.type
			FROM
				".BAB_CAL_EVENTS_OWNERS_TBL." o,
				bab_calendar c
			WHERE
				o.id_event ='".$babDB->db_escape_string($arr['id'])."'
				AND c.id=o.id_cal
			");

		while( $arr2 = $babDB->db_fetch_array($resco)) {


			switch($arr2['status'])
			{
				case BAB_CAL_STATUS_NONE:
					$partstat = 'NEEDS-ACTION';
					break;
				case BAB_CAL_STATUS_ACCEPTED:
					$partstat = 'ACCEPTED';
					break;
				case BAB_CAL_STATUS_DECLINED:
					$partstat = 'DECLINED';
					break;
			}

			$idcal = $arr2['caltype'].'/'.$arr2['id_cal'];
			$calendar = bab_getICalendars()->getEventCalendar($idcal);

			if (!isset($calendar))
			{
				switch($arr2['type'])
				{
					case BAB_CAL_USER_TYPE:
						$event->addAttendeeByUserId($arr2['owner'], 'REQ-PARTICIPANT', $partstat);
						break;
					case BAB_CAL_PUB_TYPE:
						//TODO : inaccessible public calendar are not visible
						break;
					case BAB_CAL_RES_TYPE:
						//TODO : inaccessible ressource calendar are not visible
						break;
				}


				continue;
			}


			if (empty($arr['parent_calendar']))
			{
				// hack to always have a PARENT calendar
				$arr['parent_calendar'] = $calendar->getUrlIdentifier();
			}






			if ($calendar instanceof bab_PersonalCalendar) {

				if ($calendar->getUrlIdentifier() === $arr['parent_calendar']) {

					// set as main calendar in collection
					$collection->setCalendar($calendar);

					// main calendar
					$event->addRelation('PARENT', $calendar);

				}



				$event->addAttendee($calendar, 'REQ-PARTICIPANT', $partstat);

				if (isset($alarm))
				{
					$alarm->addAttendee($calendar);
				}

			} else {

				$idfai = (int) $arr2['idfai'];
				if (0 === $idfai) {
					$idfai = null;
				}
				$status = $partstat;


				if ($calendar->getUrlIdentifier() === $arr['parent_calendar']) {
					// main calendar
					$event->addRelation('PARENT', $calendar, $status, $idfai);

					// set as main calendar in collection
					$collection->setCalendar($calendar);

				} else {
					$event->addRelation('CHILD', $calendar, $status, $idfai);
				}
			}
		}




		// add VALARM infos

		if (isset($alarm))
		{
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

				ce.*,
				ca.name category,
				er.id_event alert,
				en.note
			FROM
				".BAB_CAL_EVENTS_OWNERS_TBL." ceo,
				".BAB_CAL_EVENTS_TBL." ce
				LEFT JOIN ".BAB_CAL_CATEGORIES_TBL." ca ON ca.id = ce.id_cat
				LEFT JOIN ".BAB_CAL_EVENTS_REMINDERS_TBL." er ON er.id_event=ce.id AND er.id_user=".$babDB->quote($GLOBALS['BAB_SESS_USERID'])."
				LEFT JOIN ".BAB_CAL_EVENTS_NOTES_TBL." en ON en.id_event=ce.id AND en.id_user=".$babDB->quote($GLOBALS['BAB_SESS_USERID'])."

			WHERE
				ceo.id_event=ce.id
				AND ".$where."
			GROUP BY
				ce.uuid
			ORDER BY
				ce.start_date asc
		";

		// bab_debug($query, DBG_TRACE, 'query');

		return $query;
	}





	/**
	 * set calendar events into userperiods object
	 *
	 *
	 * @param bab_UserPeriods				$user_periods		query result set
	 * @param array							$calendars
	 * @param array							$ical				ical properties to use as filter
	 * @param string						$hash
	 * @param array							$uid_criteria		list of UID to select
	 */
	private function setEventsPeriods(bab_UserPeriods $user_periods, $calendars = null, $ical = null, $hash = null, $uid_criteria = null) {

		global $babDB;

		$begin 	= $user_periods->begin;
		$end 	= $user_periods->end;

		$backend = bab_functionality::get('CalendarBackend/Ovi');

		$id_calendars = array();
		$collections = array();
		$selected_calendars = array();


		if (null === $calendars)
		{
			$calendars = array();
			foreach(bab_getICalendars()->getCalendars() as $calendar)
			{
				if ($calendar->getBackend() instanceof Func_CalendarBackend_Ovi)
				{
					$calendars[] = $calendar;
				}
			}

		}


		foreach($calendars as $calendar) {

			if ($user_periods->id_delegation && !$calendar->visibleInDelegation($user_periods->id_delegation))
			{
				continue;
			}

			$id = $calendar->getUid();
			$id_calendars[] = $id;
			$selected_calendars[] = $calendar->getUrlIdentifier();
		}


		if (!$id_calendars)
		{
			bab_debug('Request without calendar');
			return;
		}


		$where = " (ce.parent_calendar IN(".$babDB->quote($selected_calendars).") OR ceo.id_cal IN(".$babDB->quote($id_calendars)."))";

		$where .= " AND ceo.status != '".BAB_CAL_STATUS_DECLINED."'
		";

		if (isset($end)) {
			$where .= " AND ce.start_date <= '".$babDB->db_escape_string($end->getIsoDateTime())."'
			";
		}

		if (isset($begin)) {
			$where .= " AND ce.end_date >= '".$babDB->db_escape_string($begin->getIsoDateTime())."'
			";
		}

		if (NULL !== $ical) {

			$properties = array();
			foreach($ical as $property => $arr)
			{
				if ($propsearch = $this->processPropertyToSql($property, $arr['values'], $arr['contain']))
				{
					$properties[] = $propsearch;
				}
			}

			$where .= " AND (".implode(' OR ', $properties).')';
		}

		if (null !== $hash) {
			$where .= " AND ce.hash =".$babDB->quote($hash)."
			";
		}

		if (null !== $uid_criteria) {
			$where .= " AND ce.uuid IN(".$babDB->quote($uid_criteria).")
			";
		}




		$query = $this->getQuery($where);
		$res = $babDB->db_query($query);

		while( $arr = $babDB->db_fetch_assoc($res))
		{
			if (!empty($arr['hash'])) {
				if (!isset($hashcollections[$arr['hash']]))
				{
					$hashcollections[$arr['hash']] = $backend->CalendarEventCollection($calendar);
				}

				$collection = $hashcollections[$arr['hash']];
			} else {
				if (!isset($collections[$arr['parent_calendar']]))
				{
					// need access right on the calendar :
					$parent_calendar = bab_getICalendars()->getEventCalendar($arr['parent_calendar']);

					if ($parent_calendar)
					{
						$collections[$arr['parent_calendar']] = $backend->CalendarEventCollection($parent_calendar);
					} else {
						// No parent calendar or no access to parent calendar
						$collections[$arr['parent_calendar']] = new bab_CalendarEventCollection;
					}
				}
				$collection = $collections[$arr['parent_calendar']];
			}

			$event = $this->createCalendarPeriod($arr, $collection);
			$event->resetEvent();

			if ($event)
			{
				$user_periods->addPeriod($event);
			}
		}

	}




	/**
	 *
	 * @param string $property
	 * @param array $values
	 * @param bool $contain
	 * @return string
	 */
	private function processPropertyToSql($property, Array $values, $contain)
	{
		global $babDB;


		if ('X-CTO-VACATION' === $property)
		{
			// return empty result if a vacatation is requested
			// because vacation are not stored into calendar event collection within ovi backend
			return '1=0';
		}


		$colname = null;

		switch($property)
		{
			case 'SUMMARY': 			$colname = 'ce.title'; 			break;
			case 'LOCATION':			$colname = 'ce.location';		break;
			case 'DESCRIPTION':			$colname = 'ce.description';	break;
			case 'CATEGORIES':			$colname = 'ca.name';			break;
		}

		if (!isset($colname))
		{
			// unsupported property ignored
			return '';
		}

		$or = array();
		foreach($values as $value)
		{
			$search = $babDB->db_escape_like($value);
			if ($contain)
			{
				$search = '%'.$search.'%';
			}

			$or[] = $colname." LIKE '".$search."'";
		}

		if (!$or)
		{
			return '';
		}


		return '('.implode(' OR ', $or).')';

	}




	/**
	 * For each personal calendars in query, get the UID from inbox by backend,
	 * for each backend query the list of event and set it into userPeriods
	 * @param bab_UserPeriods $user_periods
	 * @return unknown_type
	 */
	private function setInboxPeriods(bab_UserPeriods $user_periods)
	{
		global $babDB;

		$calendars = $user_periods->calendars;
		$current_criteria = $user_periods->getCriteria();
		$factory = new bab_PeriodCriteriaFactory;

		// set the calendar criteria to all visible calendars

		$criteria = $factory->Calendar(bab_getICalendars()->getCalendars())->_AND_($factory->Collection('bab_CalendarEventCollection'));

		// add other criteria

		$all = $current_criteria->getAllCriterions();
		foreach($all as $criterion)
		{
			switch(true)
			{
				case $criterion instanceof bab_PeriodCriteriaCalendar:
				case $criterion instanceof bab_PeriodCriteriaCollection:
					// ignore
					break;

				default:
					$criteria = $criteria->_AND_($criterion);
					break;
			}
		}


		// get the inbox


		$users = array();
		foreach($calendars as $calendar)
		{
			if ($calendar instanceof bab_PersonalCalendar)
			{
				$users[] = $calendar->getIdUser();
			}
		}

		$queries = array();

		$dateBoundariesConditions = array();
		if (isset($user_periods->end)) {
			$dateBoundariesConditions[] = 'start_date <='.$babDB->quote($user_periods->end->getIsoDateTime());
		}
		if (isset($user_periods->begin)) {
			$dateBoundariesConditions[] = 'end_date >='.$babDB->quote($user_periods->begin->getIsoDateTime());
		}

		$dateCondition = 'start_date =' . $babDB->quote('0000-00-00 00:00:00');
		if (!empty($dateBoundaries)) {
			$dateCondition .= ' OR (' . implode(' AND ', $dateBoundaries) . ')';
		}


		$query = 'SELECT *
			FROM
				bab_cal_inbox
			WHERE

				id_user IN(' . $babDB->quote($users) . ')
				AND
				(
				' . $dateCondition . '
				)

		';

		$res = $babDB->db_query($query);
		while ($arr = $babDB->db_fetch_assoc($res))
		{
			$queries[$arr['calendar_backend']][$arr['uid']] = $arr;
		}

		foreach($queries as $calendarBackend => $uid_list)
		{
			$calendars = array();
			foreach($uid_list as $uid => $arr)
			{
				if ($arr['parent_calendar'])
				{
					$calendars[$uid] = $arr['parent_calendar'];
				}
			}



			$inbox_criteria = clone $criteria;

			// add the UID criteria

			$uid_criterion = $factory->Uid(array_keys($uid_list));
			$uid_criterion->setCalendars($calendars);
			$inbox_criteria->_AND_($uid_criterion);


			$backend = @bab_functionality::get('CalendarBackend/'.$calendarBackend);
			if (false === $backend)
			{
				bab_debug(sprintf('Error, loading of %d events from %s failed, the backend is not accessible', count($uid_list),'CalendarBackend/'.$calendarBackend), DBG_ERROR);
				continue;
			}

			$periods = $backend->selectPeriods($inbox_criteria);

			$success = false;

			foreach($periods as $p)
			{
				$success = true;

				$arr = $uid_list[$p->getProperty('UID')];
				unset($uid_list[$p->getProperty('UID')]);

				/*@var $p bab_CalendarPeriod */
				if ($this->addInboxPeriod($uid_list, $p))
				{
					$user_periods->addPeriod($p);
				}

				$parent_calendar = '';
				if ($collection = $p->getCollection())
				{
					if ($calendar = $collection->getCalendar())
					{
						$parent_calendar = $calendar->getUrlIdentifier();
					}
				}


				if ('0000-00-00 00:00:00' === $arr['start_date'] || ('' === $arr['parent_calendar'] && $parent_calendar))
				{
					$babDB->db_query('UPDATE bab_cal_inbox SET
							start_date='.$babDB->quote(date('Y-m-d H:i:s',$p->ts_begin)).',
							end_date='.$babDB->quote(date('Y-m-d H:i:s',$p->ts_end)).',
							parent_calendar='.$babDB->quote($parent_calendar).',
							lastupdate='.$babDB->quote(date('Y-m-d H:i:s')).'
						WHERE
							uid ='.$babDB->quote($p->getProperty('UID')).'
							AND calendar_backend='.$babDB->quote($calendarBackend)
					);

				}
			}

			if (!empty($uid_list))
			{
				bab_debug('Items in inbox but not processed : <br />'.print_r($uid_list, true));
			}


			if (!empty($uid_list) && $success)
			{
				$babDB->db_query('DELETE FROM bab_cal_inbox WHERE
					uid IN('.$babDB->quote(array_keys($uid_list)).')
					AND calendar_backend='.$babDB->quote($calendarBackend).'
				');
			}

		}
	}




	/**
	 *
	 * @param bab_CalendarPeriod $p
	 * @return bool
	 */
	private function addInboxPeriod(Array $uid_list, bab_CalendarPeriod $p)
	{

		/*@var $p bab_CalendarPeriod */
		$found_uid = $p->getProperty('UID');
		if (!isset($uid_list[$found_uid]))
		{
			return false;
		}


		// once the event is accepted on a caldav event, the event is copied in the calendar
		// in ovidentia backend, the event remain in the inbox


		$id_user = $uid_list[$found_uid]['id_user'];
		$reftype = bab_getICalendars()->getUserReferenceType($id_user);
		$id = bab_getICalendars()->getPersonalCalendarUid($id_user);
		$usercal = bab_getICalendars()->getEventCalendar("$reftype/$id");
		$targetBackend = $usercal->getBackend();

		if ($targetBackend instanceof Func_CalendarBackend_Ovi)
		{
			return true;

		} else {


			// Add only periods in a waiting state
			foreach($p->getAttendees() as $urlidentifier => $attendee)
			{
				if ($urlidentifier === $usercal->getUrlIdentifier() && 'NEEDS-ACTION' === $attendee['PARTSTAT'])
				{
					return true;
				}
			}


			/*
			foreach($p->getRelations() as $urlidentifier => $relation)
			{
				if ($urlidentifier === $usercal->getUrlIdentifier() && 'NEEDS-ACTION' === $relation['X-CTO-STATUS'])
				{
					return true;
				}
			}
			*/

		}


		return false;
	}




	/**
	 * Select one event by ical property UID
	 *
	 * @param	string $uid
	 * @return bab_CalendarPeriod
	 */
	public function getFromUid($uid)
	{


		// use a cache for mutiple calls on same UID

		static $cache = array();


		if (!isset($cache[$uid]))
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

			require_once dirname(__FILE__).'/cal.periodcollection.class.php';
			$collection = new bab_CalendarEventCollection;

			$period = $this->createCalendarPeriod($arr, $collection);
			$period->resetEvent();

			$cache[$uid] = $period;
		}

		return $cache[$uid];
	}



	/**
	 *
	 * @param string $uid
	 * @return bool
	 */
	public function deleteFromUid($uid, $ts_begin)
	{
		global $babDB;


		$query = $this->getQuery("
			ce.uuid = ".$babDB->quote($uid)."
			AND ce.start_date = ".$babDB->quote(date('Y-m-d H:i:s', $ts_begin))."
		");

		$res = $babDB->db_query($query);
		$arr = $babDB->db_fetch_assoc($res);

		if (!$arr)
		{
			return false;
		}

		include_once $GLOBALS['babInstallPath'].'utilit/afincl.php';
		$id_event = $arr['id'];

		$babDB->db_query("delete from bab_cal_inbox where calendar_backend='Ovi' AND uid='".$babDB->db_escape_string($uid)."'");
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

		$tsk_collection = $backend->TaskCollection();
		$wp_collection 	= $backend->WorkingPeriodCollection();
		$nwp_collection = $backend->NonWorkingPeriodCollection();


		if ($userperiods->isPeriodCollection('bab_VacationPeriodCollection') && $users) {
			include_once $GLOBALS['babInstallPath']."utilit/vacincl.php";
			bab_vac_setVacationPeriods($userperiods, $users);
		}

		if ($userperiods->isPeriodCollection('bab_CalendarEventCollection')) {
			$this->setEventsPeriods($userperiods, $userperiods->calendars, $userperiods->icalProperties, $hash, $userperiods->uid_criteria);
		}

		if ($userperiods->isPeriodCollection('bab_InboxEventCollection')) {
			$this->setInboxPeriods($userperiods);
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
		$endts = $end->getTimeStamp();
		$begints = $begin->getTimeStamp();
		$working = $userperiods->isPeriodCollection($wp_collection);
		$nworking = $userperiods->isPeriodCollection($nwp_collection);
		$previous_end = NULL;

		if ($users) {

			// the +86400 is usefull for vacations where the $begin date start as 12H and the next date is the next day

			while ($loop->getTimeStamp() < ($endts + 86400)) {

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
							if ($nworking && $beginDate->getTimeStamp() > $previous_end->getTimeStamp()) {

								$p = new bab_calendarPeriod;
								$p->setDates($previous_end, $beginDate);
								$p->setProperty('SUMMARY', bab_translate('Non-working period'));
								$p->setData(array('id_user' => $id_user));

								$nwp_collection->addPeriod($p);
								$userperiods->addPeriod($p);
							}

							$p = new bab_calendarPeriod;
							$p->setDates($beginDate, $endDate);
							$p->setProperty('SUMMARY', bab_translate('Working period'));
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



		if ($nworking)
		{
			if (!isset($previous_end))
			{
				$previous_end = $begin;
			}

			// add final non-working period
			if ($endts > $previous_end->getTimeStamp()) {

				$p = new bab_calendarPeriod;
				$p->setDates($previous_end, $end);
				$p->setProperty('SUMMARY'		, bab_translate('Non-working period'));
				$p->setData(array('id_user' => $id_user));

				$nwp_collection->addPeriod($p);
				$userperiods->addPeriod($p);
			}
		}
	}
}
