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
include_once "base.php";

/**
* @internal SEC1 PR 20/02/2007 FULL
*/






/**
 * Notify by email form parameters received in event
 * @return unknown_type
 */
function cal_notify(bab_eventCalendarEvent $event, $subject, $body = null)
{
	global $babBody, $babDB, $babAdminEmail;


	$mail = bab_mail();
	if( $mail == false )
		return;



	if( empty($GLOBALS['BAB_SESS_USER']))
		{
		$mail->mailFrom($GLOBALS['babAdminEmail'], $GLOBALS['babAdminName']);
		}
	else
		{
		$mail->mailFrom($GLOBALS['BAB_SESS_EMAIL'], $GLOBALS['BAB_SESS_USER']);
		}

	$mail->mailSubject($subject);

	$period = $event->getPeriod();
	$calendars = $event->getCalendars();


	foreach($calendars as $calendar)
	{
		$calendartitle = $calendar->getName();
		if (((int) $GLOBALS['BAB_SESS_USERID']) === (int) $calendar->getIdUser())
		{
			$calendartitle = bab_translate('Personal calendar');
		}

		if (null === $body)
		{
			$body = $subject;
		}

		$tempc = new clsNotifyEvent(
			$period->getProperty('SUMMARY'),
			$period->getProperty('DESCRIPTION'),
			$period->getProperty('LOCATION'),
			bab_longDate($period->ts_begin),
			bab_longDate($period->ts_end),
			$body,
			$calendartitle
		);

		$tempc->asHtml();
		$message = $mail->mailTemplate(bab_printTemplate($tempc,"mailinfo.html", "newevent"));

		$mail->mailBody($message, "html");

		$tempc->asText();
		$message = bab_printTemplate($tempc,"mailinfo.html", "neweventtxt");
		$mail->mailAltBody($message);

		$arrusers = $event->getUsersToNotifyByCalendar($calendar);

		if(!$arrusers)
		{
			bab_debug('NO USERS for calendar :'.$calendar->getName().' | notification : '.$subject, DBG_TRACE, 'Notification');
			continue;
		}

		bab_debug(count($arrusers).' USER(S) for calendar :'.$calendar->getName().' | notification : '.$subject, DBG_TRACE, 'Notification');

		$mailBCT = 'mail'.$babBody->babsite['mail_fieldaddress'];
		$clearBCT = 'clear'.$babBody->babsite['mail_fieldaddress'];


		$count = 0;
		reset($arrusers);
		while(list($id_user, $arr) = each($arrusers))
		{
		$event->addInformedUser($id_user);
		$mail->$mailBCT($arr['email'], $arr['name']);
		$count++;

		if( $count > $babBody->babsite['mail_maxperpacket'] )
			{
			$mail->send();
			$mail->$clearBCT();
			$mail->clearTo();
			$count = 0;
			}
		}

		if( $count > 0 )
		{
		$mail->send();
		$mail->$clearBCT();
		$mail->clearTo();
		$count = 0;
		}

	}
}








/**
 * Search the main calendar from the posted calendars
 * first public or ressource calendar, calendar of user, first personal calendar
 *
 * @param 	array 	$idcals		list of calendar
 *
 * @return bab_EventCalendar
 */
function bab_getMainCalendar(Array $idcals)
{
	$list = array_flip($idcals);
	$calendars = bab_getICalendars()->getCalendars();
	$oviBackendGroupCalendars = array();
	$otherGroupCalendars = array();
	$personalCalendar = null;
	$otherPersonalCalendars = array();

	foreach($calendars as $calendar) {

		/*@var $calendar bab_EventCalendar */

		$id = $calendar->getUrlIdentifier();

		if (!isset($list[$id])) {
			continue;
		}

		/*
		if ($calendar->isDefaultCalendar()) {
			return $calendar;
		}
		*/

		if ($calendar instanceof bab_PersonalCalendar)
		{
			if ($GLOBALS['BAB_SESS_USERID'] === $calendar->getIdUser()) {
				$personalCalendar = $calendar;
			} else {
				$otherPersonalCalendars[] = $calendar;
			}

			continue;
		}



		if ($calendar->getBackend() instanceof Func_CalendarBackend_Ovi)
		{
			$oviBackendGroupCalendars[] = $calendar;
		} else {
			$otherGroupCalendars[] = $calendar;
		}
	}


	if ($oviBackendGroupCalendars)
	{
		bab_Sort::sortObjects($oviBackendGroupCalendars, 'getName');
		// the first ressource or collective calendar
		return reset($oviBackendGroupCalendars);
	}

	if ($otherGroupCalendars)
	{
		bab_Sort::sortObjects($otherGroupCalendars, 'getName');
		// not used for now
		return reset($otherGroupCalendars);
	}

	if (isset($personalCalendar))
	{
		return $personalCalendar;
	}

	if ($otherPersonalCalendars)
	{
		bab_Sort::sortObjects($otherPersonalCalendars, 'getName');
		return reset($otherPersonalCalendars);
	}


	throw new Exception('No accessible compatible calendar');
	return null;
}










/**
 * Create a period from the arguments posted by event creation form
 *
 * @param	Func_CalendarBackend		$backend
 *
 * @param	array		$args
 *
 *  $args['title']
 *  $args['description']
 *  $args['descriptionformat']
 *	$args['startdate'] 	: array('month', 'day', 'year', 'hours', 'minutes')
 *	$args['enddate'] 	: array('month', 'day', 'year', 'hours', 'minutes')
 *	$args['owner'] 		: id of the owner
 *	$args['rrule'] 		: // BAB_CAL_RECUR_DAILY, ...
 *	$args['until'] 		: array('month', 'day', 'year')
 *	$args['rdays'] 		: repeat days array("SU","MO","TU","WE","TH","FR", "SA")
 *	$args['ndays'] 		: nb days
 *	$args['nweeks'] 	: nb weeks
 *	$args['nmonths'] 	: nb month
 *	$args['color']		: color string
 *	$args['category'] 	: id of the category
 *	$args['private'] 	: if the event is private
 *	$args['lock'] 		: to lock the event
 *	$args['free'] 		: free event
 *	$args['alert'] 		: array('day', 'hour', 'minute', 'email'=>'Y')
 *	$args['selected_calendars'] : array()
 *
 * @param	bab_PeriodCollection $collection
 *
 * @throws ErrorException
 *
 * @return bab_CalendarPeriod
 */
function bab_createCalendarPeriod(Func_CalendarBackend $backend, $args, bab_PeriodCollection $collection)
{

	require_once $GLOBALS['babInstallPath'].'utilit/dateTime.php';
	$idcals = $args['selected_calendars'];


	if ($args['evtid']) {
		$calendar = bab_getICalendars()->getEventCalendar($args['calid']);

		$period = $backend->getPeriod($backend->CalendarEventCollection($calendar), $args['evtid'], $args['dtstart']);
		bab_debug('<h1>$backend->getPeriod()</h1>'. $period->toHtml(), DBG_TRACE, 'CalendarBackend');

		$begin 	= bab_event_posted::getDateTime($args['startdate'], $period->ts_begin);
		$end 	= bab_event_posted::getDateTime($args['enddate'], $period->ts_end);

		$oldcollection = $period->getCollection();

	} else {

		$begin 	= bab_event_posted::getDateTime($args['startdate']);
		$end 	= bab_event_posted::getDateTime($args['enddate']);

		// create empty period
		$period = $backend->CalendarPeriod($begin->getTimeStamp(), $end->getTimeStamp());
	}

	$collection->addPeriod($period);
	if (isset($oldcollection))
	{
		$collection->hash = $oldcollection->hash;
	}

	if ($args['evtid']) {
		$period->setProperty('UID', $args['evtid']);
	}

	$critical_change = false;
	if ($period->ts_begin !== $begin->getTimeStamp() || $period->ts_end !== $end->getTimeStamp() || $period->getProperty('LOCATION') !== $args['location'])
	{
		$critical_change = true;
	}

	$period->setDates($begin, $end);

	$period->setProperty('SUMMARY', $args['title']);
	$period->setProperty('DESCRIPTION', trim(bab_unhtmlentities(strip_tags($args['description'])))); // Text version of description within ICAL property
	$period->setProperty('LOCATION', $args['location']);

	if ($args['private']) {
		$period->setProperty('CLASS', 'PRIVATE');
	}

	$cat = bab_getCalendarCategory($args['category']);
	if ($cat) {
		$period->setProperty('CATEGORIES', $cat['name']);
	} else {
		if ($args['color'])
		{
			$period->setProperty('X-CTO-COLOR', $args['color']);
		}
	}

	// organizer is set directly from libcadav ?
	// $period->setProperty('ORGANIZER;CN='. , 'MAILTO:'.);

	// time transparency (free : yes|no)

	if ($args['free'])
	{
		$period->setProperty('TRANSP', 'TRANSPARENT');

	} else {
		$period->setProperty('TRANSP', 'OPAQUE');
	}


	$calendar = $collection->getCalendar();
	$period->removeAttendees();
	$oldrelations = $period->getRelations();

	// Attendee
	// add additional calendars as attendee property
	$calendars = bab_getICalendars()->getCalendars();


	foreach($args['selected_calendars'] as $idcal)
	{
		if (isset($calendars[$idcal]))
		{
			$attendee = $calendars[$idcal];
			$id_user = $attendee->getIdUser();
			$urlid = $attendee->getUrlIdentifier();

			if ($id_user)
			{
				$partstat = $attendee->getDefaultAttendeePARTSTAT();

				if ($calendar->getUrlIdentifier() === $attendee->getUrlIdentifier()) {

					// set as parent
					$period->addRelation('PARENT', $attendee);
					if (isset($oldrelations[$urlid]))
					{
						unset($oldrelations[$urlid]);
					}

				}

				$period->addAttendee($attendee, 'REQ-PARTICIPANT', $partstat);

			} else {

				// $attendee is not a user
				// create approbation instance if necessary

				$idfai = null;
				$status = null;

				if (isset($oldrelations[$urlid]))
				{
					$idfai = $oldrelations[$urlid]['X-CTO-WFINSTANCE'];
					$status = $oldrelations[$urlid]['X-CTO-STATUS'];
				}

				if ($critical_change && ('ACCEPTED' === $status || 'DECLINED' === $status))
				{
					$status = null;
				}


				if (!$idfai && ('NEEDS-ACTION' === $status || null === $status))
				{
					if($idsa = $attendee->getApprobationSheme())
					{
						include_once $GLOBALS['babInstallPath']."utilit/afincl.php";
						$idfai = makeFlowInstance($idsa, "cal-".$attendee->getUid().'-'.$period->getProperty('DTSTART').'-'.uniqid());
						$status = 'NEEDS-ACTION';
					}
				}

				if ($calendar->getUrlIdentifier() === $attendee->getUrlIdentifier()) {

					$period->addRelation('PARENT', $attendee, $status, $idfai);

				} else {

					$period->addRelation('CHILD', $attendee, $status, $idfai);
				}


				if (isset($oldrelations[$urlid]))
				{
					unset($oldrelations[$urlid]);
				}
			}
		}
	}

	// remove old relation, not updated
	foreach($oldrelations as $relation)
	{
		$period->removeRelation($relation['calendar']);
	}



	// recur rule

	$rrule = array();


	if( isset($args['rrule']) )
	{
		$duration = $end->getTimeStamp() - $begin->getTimeStamp();

		switch( $args['rrule'] )
		{
		case BAB_CAL_RECUR_WEEKLY:
			if( $duration > 24 * 3600 * 7 * $args['nweeks'])
			{
				throw new ErrorException(bab_translate("The duration of the event must be shorter than how frequently it occurs"));
				return false;
			}

			$rrule[]= 'INTERVAL='.$args['nweeks'];

			if( !isset($args['rdays']) )
			{
				// no week day specified, reapeat event every week
				$rrule[]= 'FREQ=WEEKLY';
			}
			else
			{
				$rrule[]= 'FREQ=WEEKLY';
				// BYDAY : add list of weekday    = "SU" / "MO" / "TU" / "WE" / "TH" / "FR" / "SA"
				$rrule[] = 'BYDAY='.implode(',', $args['rdays']);
			}

			break;


		case BAB_CAL_RECUR_MONTHLY:
			if( $duration > 24*3600*28*$args['nmonths'])
				{
				throw new ErrorException(bab_translate("The duration of the event must be shorter than how frequently it occurs"));
				return false;
				}

			$rrule[]= 'INTERVAL='.$args['nmonths'];
			$rrule[]= 'FREQ=MONTHLY';
			break;

		case BAB_CAL_RECUR_YEARLY: /* yearly */

			if( $duration > 24*3600*365*$args['nyears'])
				{
				throw new ErrorException(bab_translate("The duration of the event must be shorter than how frequently it occurs"));
				return false;
				}
			$rrule[]= 'INTERVAL='.$args['nyears'];
			$rrule[]= 'FREQ=YEARLY';
			break;

		case BAB_CAL_RECUR_DAILY: /* daily */
			if( $duration > 24*3600*$args['ndays'] )
				{
				throw new ErrorException(bab_translate("The duration of the event must be shorter than how frequently it occurs"));
				return false;
				}
			$rrule[]= 'INTERVAL='.$args['ndays'];
			$rrule[]= 'FREQ=DAILY';
			break;
		}
	}


	if (isset($args['until']))
	{

		$until = bab_event_posted::getDateTime($args['until']);
		$until->add(1, BAB_DATETIME_DAY);

		if( $until->getTimeStamp() < $end->getTimeStamp()) {
			throw new ErrorException(bab_translate("Repeat date must be older than end date"));
			return false;
		}


		$rrule[] = 'UNTIL='.$until->getICal(true);
	}

	$period->setProperty('RRULE', implode(';',$rrule));


	// VALARM

	if (isset($args['alert'])) {

		/*
		 * keys are :
		 *
		 * day
		 * hour
		 * minute
		 * email
		 */

		$day = $args['alert']['day'];
		$hour = $args['alert']['hour'];
		$minute = $args['alert']['minute'];



		$alarm = $backend->CalendarAlarm();
		$period->setAlarm($alarm);

		bab_setAlarmProperties($alarm, $period, $day, $hour, $minute, 'Y' === $args['alert']['email']);


	}



	// data not in iCal standard (ovidentia specific)

	$data = array(
		'description'			=> $args['description'],
		'description_format'	=> $args['descriptionformat'],
		'block'					=> $args['lock'] ? 'Y' : 'N'
	);

	$period->setData($data);


	return $period;
}






/**
 * Set alarm properties from ovidentia infos (databse or post query)
 * @param	bab_CalendarAlarm 	$alarm
 * @param	bab_CalendarPeriod 	$period
 * @param	int					$day
 * @param	int					$hour
 * @param	int					$minute
 * @param	bool				$email
 * @return unknown_type
 */
function bab_setAlarmProperties(bab_CalendarAlarm $alarm, bab_CalendarPeriod $period, $day, $hour, $minute, $email)
{
	$duration = '-P';

	if ($day || $hour || $minute)
	{
		if ($day)
		{
			$duration .= $day.'D';
		}

		if ($hour || $minute)
		{
			$duration .= 'T'.$hour.'H'.$minute.'M';
		}
	} else {
		// default 1 minute
		$duration = '-P1M';
	}

	$alarm->setProperty('TRIGGER', $duration);
	$alarm->setProperty('SUMMARY', $period->getProperty('SUMMARY'));
	$alarm->setProperty('DESCRIPTION', $period->getProperty('DESCRIPTION'));

	if ($email) {
		$alarm->setProperty('ACTION', 'EMAIL');

	} else {
		$alarm->setProperty('ACTION', 'DISPLAY');
	}
}








/**
 * Approbation on public calendars and resource calendars
 *
 * @param	string	$uid			event UID
 * @param	string	$idcal			calendar url identifier
 * @param	string	$relationcal	calendar url identifier of relation
 * @param	int		$status			approver status				BAB_CAL_STATUS_ACCEPTED | BAB_CAL_STATUS_DECLINED
 * @return unknown_type
 */
function confirmApprobEvent($uid, $idcal, $relationcal, $status, $comment)
{
	global $babDB;
	include_once $GLOBALS['babInstallPath']."utilit/afincl.php";


	$calendar = bab_getICalendars()->getEventCalendar($idcal);
	
	if (null === $calendar)
	{
		// the main calendar is not accessible, but the user must approve an event in it
		$calendar = bab_getICalendars()->getEventCalendar($relationcal);
	
		if (null === $calendar)
		{
			$babBody->addError(bab_translate('The calendar is not accessible'));
			return false;
		}
	}
	
	$backend = $calendar->getBackend();
	$period = $backend->getPeriod($backend->CalendarEventCollection($calendar), $uid);

	if (!$period)
	{
		throw new Exception('Event not found');
		return false;
	}

	// search for relation
	$relations = $period->getRelations();
	if (isset($relations[$relationcal]))
	{
		$relation = $relations[$relationcal];
	}


	if (!$relation)
	{
		throw new Exception('Access denied to this approbation, relation not found with calendar '.$relationcal);
		return false;
	}


	$arrschi = bab_getWaitingIdSAInstance($GLOBALS['BAB_SESS_USERID']);

	if (!in_array($relation['X-CTO-WFINSTANCE'], $arrschi))
	{
		throw new Exception(sprintf('Access denied to this approbation, X-CTO-WFINSTANCE = %s not found in list of instance', $relation['X-CTO-WFINSTANCE']));
		return false;
	}

	$ret = updateFlowInstance($relation['X-CTO-WFINSTANCE'], $GLOBALS['BAB_SESS_USERID'], (BAB_CAL_STATUS_ACCEPTED == $status));

	switch($ret)
	{
	case 0:

		if ($backend->setRelationStatus($period, $relation, 'DECLINED'))
		{
			deleteFlowInstance($relation['X-CTO-WFINSTANCE']);

			notifyEventApprobation(
				$period,
				$status,
				$comment,
				$relation['calendar']->getName(),
				sprintf(bab_translate('A calendar in relation with your appointement has been rejected by %s'), $GLOBALS['BAB_SESS_USER'])
			);
		}

		break;



	case 1:

		if ($backend->setRelationStatus($period, $relation, 'ACCEPTED'))
		{
			deleteFlowInstance($relation['X-CTO-WFINSTANCE']);

			notifyEventApprobation(
				$period,
				$status,
				$comment,
				$relation['calendar']->getName(),
				sprintf(bab_translate('A calendar in relation with your appointement has been approved by %s'), $GLOBALS['BAB_SESS_USER'])
			);



			if ($relation['calendar'] instanceof bab_ResourceCalendar) {
				notifyResourceEvent(
					$period->getProperty('SUMMARY'),
					$period->getProperty('DESCRIPTION'),
					$period->getProperty('LOCATION'),
					bab_longDate($period->ts_begin),
					bab_longDate($period->ts_end),
					$relation['calendar'],
					bab_translate('The following resource has been validated')
				);
			} else {
				notifyPublicEvent(
					$period->getProperty('SUMMARY'),
					$period->getProperty('DESCRIPTION'),
					$period->getProperty('LOCATION'),
					bab_longDate($period->ts_begin),
					bab_longDate($period->ts_end),
					$relation['calendar'],
					bab_translate('The following appointment has been validated')
				);
			}
		}


		break;



	default:
		$nfusers = getWaitingApproversFlowInstance($relation['X-CTO-WFINSTANCE'], true);
		if( count($nfusers) > 0 )
			{
			notifyEventApprovers($period, $nfusers, $relation['calendar']);
			}
		break;
	}


	return true;
}









/**
 * Confirmation of attendees
 *
 *
 * @param string 	$evtid		event UID
 * @param string	$dtstart
 * @param string 	$idcal		calendar url identifier (main calendar of event)
 * @param string 	$partstat	New partstat value
 * @param string 	$comment
 * @param int 		$bupdrec
 * @return bool
 */
function confirmEvent($evtid, $dtstart, $idcal, $partstat, $comment, $bupdrec)
{
	global $babDB, $babBody;
	$calendar = bab_getICalendars()->getEventCalendar($idcal);
	
	if (!$calendar)
	{
		// the main calendar of event is not accessible
		
		// there is probably a copy in the user personal calendar or in the inbox
		$calendar = bab_getICalendars()->getPersonalCalendar();
		
		if (!$calendar)
		{
			throw new Exception('This is not a personal calendar');
			return false;
		}
	}

	
	
	
	
	$backend = $calendar->getBackend();
	$calendarPeriod = $backend->getPeriod($backend->CalendarEventCollection($calendar), $evtid, $dtstart);
	
	// do not continue if there is no calendarPeriod or if event is CANCELLED
	
	if (null === $calendarPeriod)
	{
		$babBody->addError(bab_translate('This event does not exists'));
		return false;
	}
	
	
	if ('CANCELLED' === $calendarPeriod->getProperty('STATUS'))
	{
		$babBody->addError(bab_translate('This event has been cancelled'));
		return false;
	}
	
	
	$collection = $calendarPeriod->getCollection();

	bab_addHashEventsToCollection($collection, $calendarPeriod, $bupdrec);


	$attendeeCalendar = null;
	$attendees = $calendarPeriod->getAttendees();
	foreach($attendees as $attendee)
	{
		$user = (int) $attendee['calendar']->getIdUser();
		if ($user === (int) $GLOBALS['BAB_SESS_USERID'])
		{
			$attendeeCalendar = $attendee['calendar'];
			if (!$attendee['calendar']->canUpdateAttendeePARTSTAT($calendarPeriod, $attendee['ROLE'], $attendee['PARTSTAT'], $partstat))
			{
				return false;
			}
		}
	}
	
	if (null === $attendeeCalendar)
	{
		throw new Exception('Calendar for partstat modification not found in event');
		return false;
	}

	
	foreach($attendees as $attendee)
	{
		// set period in a new collection linked to the attendee calendar
		
		$backend = $attendee['calendar']->getBackend();
		$collection = $backend->CalendarEventCollection($attendee['calendar']);
		$collection->addPeriod($calendarPeriod);
		
		try {
			$backend->updateAttendeePartstat($calendarPeriod, $attendeeCalendar, $partstat, $comment);
		} catch(Exception $e)
		{
			// ignore missing event in backend
			bab_debug($e->getMessage());
		}
	}
	
	
	return true;
}





class clsNotifyEvent {

	/**
	 * @private
	 */
	var $vars = array();

	/**
	 * @public
	 */
	var $title;
	var $description;
	var $location;
	var $startdate;
	var $enddate;
	var $descriptiontxt;
	var $locationtxt;
	var $titletxt;
	var $startdatetxt;
	var $enddatetxt;
	var $message;
	var $calendar;

	public function __construct($title, $description, $location, $startdate, $enddate, $message, $calendar)
	{
		$this->message 				= $message;
		$this->calendar 			= $calendar;

		$this->vars['title'] 		= $title;
		$this->vars['description'] 	= $description;
		$this->vars['startdate'] 	= $startdate;
		$this->vars['enddate'] 		= $enddate;
		$this->vars['message'] 		= $message;
		$this->vars['location'] 	= $location;
	}

	public function asText() {
		$this->title = $this->vars['title'];
		$this->description = strip_tags(bab_toHtml($this->vars['description'], BAB_HTML_REPLACE_MAIL));
		$this->startdate = $this->vars['startdate'];
		$this->enddate = $this->vars['enddate'];
		$this->message = $this->vars['message'];
		$this->location = strip_tags(bab_toHtml($this->vars['location'], BAB_HTML_REPLACE_MAIL));

		$this->descriptiontxt = bab_translate("Description");
		$this->titletxt = bab_translate("Title");
		$this->startdatetxt = bab_translate("Begin date");
		$this->enddatetxt = bab_translate("End date");
		$this->calendartxt = bab_translate("Calendar");
		$this->locationtxt = bab_translate("Location");
	}

	public function asHtml() {
		$this->title = bab_toHtml($this->vars['title']);
		$this->description = bab_toHtml($this->vars['description'], BAB_HTML_REPLACE_MAIL);
		$this->startdate = bab_toHtml($this->vars['startdate']);
		$this->enddate = bab_toHtml($this->vars['enddate']);
		$this->message = bab_toHtml($this->vars['message']);
		$this->location = bab_toHtml($this->vars['location'], BAB_HTML_REPLACE_MAIL);

		$this->descriptiontxt = bab_translate("Description");
		$this->titletxt = bab_translate("Title");
		$this->startdatetxt = bab_translate("Begin date");
		$this->enddatetxt = bab_translate("End date");
		$this->calendartxt = bab_translate("Calendar");
		$this->locationtxt = bab_translate("Location");
	}
}







function notifyPublicEvent($title, $description, $location, $startdate, $enddate, bab_EventCalendar $calendar, $message)
	{
	global $babBody, $babDB, $babAdminEmail;



	$mail = bab_mail();
	if( $mail == false )
		return;
	$mailBCT = 'mail'.$babBody->babsite['mail_fieldaddress'];
	$clearBCT = 'clear'.$babBody->babsite['mail_fieldaddress'];

	if( empty($GLOBALS['BAB_SESS_USER']))
		{
		$mail->mailFrom($GLOBALS['babAdminEmail'], $GLOBALS['babAdminName']);
		}
	else
		{
		$mail->mailFrom($GLOBALS['BAB_SESS_EMAIL'], $GLOBALS['BAB_SESS_USER']);
		}
	$tempc = new clsNotifyEvent($title, $description, $location, $startdate, $enddate, $message, '');

	$arrusers = array();

	$tempc->calendar = $calendar->getName();
	$mail->mailSubject($message);



	$tempc->asHtml();
	$message = $mail->mailTemplate(bab_printTemplate($tempc,"mailinfo.html", "newevent"));
	$mail->mailBody($message, "html");

	$tempc->asText();
	$message = bab_printTemplate($tempc,"mailinfo.html", "neweventtxt");
	$mail->mailAltBody($message);

	$arrusers = cal_usersToNotiy($calendar, BAB_CAL_PUB_TYPE, 0);


	if( $arrusers )
		{
		$count = 0;
		reset($arrusers);
		while(list(,$arr) = each($arrusers))
			{
			$mail->$mailBCT($arr['email'], $arr['name']);
			$count++;

			if( $count > $babBody->babsite['mail_maxperpacket'] )
				{
				$mail->send();
				$mail->$clearBCT();
				$mail->clearTo();
				$count = 0;
				}
			}

		if( $count > 0 )
			{
			$mail->send();
			$mail->$clearBCT();
			$mail->clearTo();
			$count = 0;
			}
		}


	}





/**
 * Get users to notify for a calendar, do not notify a person twice in the same refresh
 *
 *
 * @param	bab_EventCalendar | int		$calendar
 * @param	int							$cal_type
 * @param 	int 						$id_owner
 * @param	int							$id_creator		notify creator of event
 * @return 	array
 */
function cal_usersToNotiy($calendar, $cal_type, $id_owner, $id_creator = null) {

	include_once $GLOBALS['babInstallPath']."admin/acl.php";

	if ($calendar instanceof bab_EventCalendar)
	{
		$id_cal = $calendar->getUid();
	} else {
		$id_cal = $calendar;
	}

	global $babDB;
	$arrusers = array();

	switch($cal_type)
		{
		case BAB_CAL_USER_TYPE:
			if( !isset($arrusers[$id_owner]))
				{
				$arrusers[$id_owner] = array(
						'name' => bab_getUserName($id_owner),
						'email' => bab_getUserEmail($id_owner)
					);
				}
			break;

		case BAB_CAL_PUB_TYPE:
			$arr = aclGetAccessUsers(BAB_CAL_PUB_GRP_GROUPS_TBL, $id_cal);
			$arrusers = array_merge($arrusers, $arr);
			break;

		case BAB_CAL_RES_TYPE:
			$arr = aclGetAccessUsers(BAB_CAL_RES_GRP_GROUPS_TBL, $id_cal);
			$arrusers = array_merge($arrusers, $arr);
			break;
		}

	if (isset($GLOBALS['BAB_SESS_USERID'])) {
		unset($arrusers[$GLOBALS['BAB_SESS_USERID']]);
	}

	if (null !== $id_creator && !isset($arrusers[$id_creator])) {
		$arrusers[$id_creator] = array(
			'name' => bab_getUserName($id_creator),
			'email' => bab_getUserEmail($id_creator)
		);
	}

	static $sent = NULL;

	if (NULL === $sent) {
		$sent = $arrusers;
	} else {

		foreach($arrusers as $id_user => $arr) {
			if (isset($sent[$id_user])) {
				unset($arrusers[$id_user]);
			} else {
				$sent[$id_user] = $arr;
			}
		}
	}

	return $arrusers;
}







function notifyResourceEvent($title, $description, $location, $startdate, $enddate, bab_EventCalendar $calendar, $message)
	{
	global $babBody, $babDB, $babAdminEmail;


	$mail = bab_mail();
	if( $mail == false )
		return;
	$mailBCT = 'mail'.$babBody->babsite['mail_fieldaddress'];
	$clearBCT = 'clear'.$babBody->babsite['mail_fieldaddress'];

	if( empty($GLOBALS['BAB_SESS_USER']))
		{
		$mail->mailFrom($GLOBALS['babAdminEmail'], $GLOBALS['babAdminName']);
		}
	else
		{
		$mail->mailFrom($GLOBALS['BAB_SESS_EMAIL'], $GLOBALS['BAB_SESS_USER']);
		}
	$tempc = new clsNotifyEvent($title, $description, $location, $startdate, $enddate, $message, $calendar->getName());

	$mail->mailSubject($message);

	$tempc->asHtml();
	$message = $mail->mailTemplate(bab_printTemplate($tempc,"mailinfo.html", "newevent"));
	$mail->mailBody($message, "html");

	$tempc->asText();
	$message = bab_printTemplate($tempc,"mailinfo.html", "neweventtxt");
	$mail->mailAltBody($message);

	$arrusers = cal_usersToNotiy($calendar, BAB_CAL_RES_TYPE, 0);


	if( $arrusers )
		{
		$count = 0;
		reset($arrusers);
		while(list(,$arr) = each($arrusers))
			{
			$mail->$mailBCT($arr['email'], $arr['name']);
			$count++;

			if( $count > $babBody->babsite['mail_maxperpacket'] )
				{
				$mail->send();
				$mail->$clearBCT();
				$mail->clearTo();
				$count = 0;
				}
			}

		if( $count > 0 )
			{
			$mail->send();
			$mail->$clearBCT();
			$mail->clearTo();
			$count = 0;
			}
		}

	}



/**
 * Notify creator of event if the ORGANIZER property exists
 *
 * @param bab_CalendarPeriod 	$period
 * @param int 					$bconfirm					BAB_CAL_STATUS_ACCEPTED | BAB_CAL_STATUS_DECLINED
 * @param string 				$raison
 * @param string 				$calname
 * @param string 				$subject
 * @return unknown_type
 */
function notifyEventApprobation(bab_CalendarPeriod $period, $bconfirm, $raison, $calname, $subject = null)
	{
	global $babBody, $babDB, $babAdminEmail;

	if(!class_exists("clsNotifyEventApprobation"))
		{
		class clsNotifyEventApprobation extends clsNotifyEvent
			{
			var $calendar;

			function clsNotifyEventApprobation($period, $raison, $calname)
				{
				$this->calendar = $calname;

				$this->vars['title'] 		= $period->getProperty('SUMMARY');
				$this->vars['description'] 	= $period->getProperty('DESCRIPTION');
				$this->vars['startdate'] 	= bab_longDate($period->ts_begin);
				$this->vars['enddate'] 		= bab_longDate($period->ts_end);
				$this->vars['message'] 		= $raison;
				$this->vars['location'] 	= $period->getProperty('LOCATION');
				}
			}
		}


	$mail = bab_mail();
	if( $mail == false )
		return;

	$organizer = $period->getOrganizer();

	if (!$organizer || $organizer['email'] === bab_getUserEmail($GLOBALS['BAB_SESS_USERID']))
	{
		return;
	}


	if (isset($organizer['name']))
	{
		$mail->mailTo($organizer['email'], $organizer['name']);
	} else {
		$mail->mailTo($organizer['email']);
	}


	$mail->mailFrom($GLOBALS['BAB_SESS_EMAIL'], $GLOBALS['BAB_SESS_USER']);

	$tempc = new clsNotifyEventApprobation($period, $raison, $calname);


	if (null === $subject)
	{
		if( $bconfirm == BAB_CAL_STATUS_ACCEPTED)
		{
		$subject = bab_translate("Appointement accepted by ");
		}
		else
		{
		$subject = bab_translate("Appointement declined by ");
		}

		$subject .= $GLOBALS['BAB_SESS_USER'];
	}


	$mail->mailSubject($subject);

	$tempc->asHtml();
	$message = $mail->mailTemplate(bab_printTemplate($tempc,"mailinfo.html", "newevent"));
	$mail->mailBody($message, "html");

	$tempc->asText();
	$message = bab_printTemplate($tempc,"mailinfo.html", "neweventtxt");
	$mail->mailAltBody($message);

	$mail->send();
	}



class clsnotifyEventUpdate extends clsNotifyEvent
	{
	var $calendar;

	function clsnotifyEventUpdate(&$evtinfo)
		{
		$this->calendar = '';

		$this->vars['title'] 		= $evtinfo['title'];
		$this->vars['description'] 	= $evtinfo['description'];
		$this->vars['startdate'] 	= bab_longDate(bab_mktime($evtinfo['start_date']));
		$this->vars['enddate'] 		= bab_longDate(bab_mktime($evtinfo['end_date']));
		$this->vars['message'] 		= '';
		$this->vars['location'] 	= $evtinfo['location'];
		}
	}



/**
 * Notifications of event modification
 *
 * @param int 		$evtid
 * @param bool 		$bdelete		if true notify a delete message
 * @param array 	$exclude		List of calendars added to event in the same action of the modification,
 * 									the recipients of these calendars will have their own mails elswere, they do not need
 * 									the update notification because for us this is a new event
 * @return null
 */
function notifyEventUpdate($evtid, $bdelete, $exclude)
	{
	global $babBody, $babDB, $babAdminEmail;


	$mail = bab_mail();
	if( $mail == false )
		return;

	$mailBCT = 'mail'.$babBody->babsite['mail_fieldaddress'];
	$clearBCT = 'clear'.$babBody->babsite['mail_fieldaddress'];

	$evtinfo=$babDB->db_fetch_array($babDB->db_query("select cet.* from ".BAB_CAL_EVENTS_TBL." cet where cet.id='".$babDB->db_escape_string($evtid)."'"));


	$mail->mailFrom($GLOBALS['BAB_SESS_EMAIL'], $GLOBALS['BAB_SESS_USER']);

	$tempc =new clsnotifyEventUpdate($evtinfo);

	if( $bdelete )
		{
		$subject = bab_translate("Appointment canceled by ");
		$tempc->message = bab_translate("The following appointment has been canceled");
		}
	else
		{
		$subject = bab_translate("Appointment modifed by ");
		$tempc->message = bab_translate("The following appointment has been modified");
		}

	$subject .= $GLOBALS['BAB_SESS_USER'];
	$mail->mailSubject($subject);

	$res = $babDB->db_query("
		SELECT
			ceot.*,
			ct.type,
			ct.owner
		FROM
			".BAB_CAL_EVENTS_OWNERS_TBL." ceot
			left join ".BAB_CALENDAR_TBL." ct on ct.id=ceot.id_cal
		WHERE
			ceot.id_event='".$babDB->db_escape_string($evtid)."'
			AND status IN('".BAB_CAL_STATUS_ACCEPTED."', '".BAB_CAL_STATUS_NONE."')
		");

	while( $arr = $babDB->db_fetch_array($res) )
		{
		$arrusers = cal_usersToNotiy($arr['id_cal'], $arr['type'], $arr['owner'], $evtinfo['id_creator']);

		if($arrusers && !isset($arr['id_cal'], $exclude))
			{
			$calinfo = bab_getICalendars()->getCalendarInfo($arr['id_cal']);
			$tempc->calendar = $calinfo['name'];
			$tempc->asHtml();
			$message = $mail->mailTemplate(bab_printTemplate($tempc,"mailinfo.html", "newevent"));
			$mail->mailBody($message, "html");

			$tempc->asText();
			$message = bab_printTemplate($tempc,"mailinfo.html", "neweventtxt");
			$mail->mailAltBody($message);

			$count = 0;
			reset($arrusers);
			while(list(,$row) = each($arrusers))
				{
				$mail->$mailBCT($row['email'], $row['name']);
				$count++;

				if( $count > $babBody->babsite['mail_maxperpacket'] )
					{
					$mail->send();
					$mail->$clearBCT();
					$mail->clearTo();
					$count = 0;
					}

				}

			if( $count > 0 )
				{
				$mail->send();
				$mail->$clearBCT();
				$mail->clearTo();
				$count = 0;
				}
			}


		}
	}


function notifyEventApprovers(bab_CalendarPeriod $period, $users, bab_EventCalendar $calendar)
	{
	global $babDB, $babBody, $babAdminEmail;

	if(!class_exists("notifyEventApproversCls"))
		{
		class notifyEventApproversCls
			{
			var $articletitle;
			var $message;
			var $from;
			var $author;
			var $category;
			var $categoryname;
			var $title;
			var $site;
			var $sitename;
			var $date;
			var $dateval;

			var $tmp_title;
			var $tmp_desc;
			var $tmp_calendar;


			function notifyEventApproversCls(bab_CalendarPeriod $period, bab_EventCalendar $calendar)
				{
				global $babDB;

				$this->message = bab_translate("A new event has been scheduled");

				$this->tmp_desc = $period->getProperty('DESCRIPTION');
				$this->descriptiontxt = bab_translate("Description");
				$this->locationtxt = bab_translate("Location");
				$this->startdate = bab_longDate($period->ts_begin);
				$this->startdatetxt = bab_translate("Begin date");
				$this->enddate = bab_longDate($period->ts_end);
				$this->enddatetxt = bab_translate("End date");
				$this->titletxt = bab_translate("Title");
				$this->tmp_title = $period->getProperty('SUMMARY');
				$this->tmp_location = $period->getProperty('LOCATION');
				if( $calendar instanceof bab_PublicCalendar ) {
					$this->calendartxt = bab_translate("Public calendar");
				} elseif ($calendar instanceof bab_ResourceCalendar) {
					$this->calendartxt = bab_translate("Resource calendar");
				} else {
					$this->calendartxt = bab_translate("calendar");
				}

				$this->tmp_calendar = $calendar->getName();
				}

			function asHtml() {
				$this->title = bab_toHtml($this->tmp_title);
				$this->location = bab_toHtml($this->tmp_location);
				$this->description = bab_toHtml($this->tmp_desc, BAB_HTML_REPLACE_MAIL);
				$this->calendar = bab_toHtml($this->tmp_calendar);
				}

			function asText() {
				$this->title = $this->tmp_title;
				$this->location = $this->tmp_location;
				$this->description = strip_tags(bab_toHtml($this->tmp_desc, BAB_HTML_REPLACE_MAIL));
				$this->calendar = $this->tmp_calendar;
				}
			}
		}

	$mail = bab_mail();
	if( $mail == false )
		return;
	$mailBCT = 'mail'.$babBody->babsite['mail_fieldaddress'];

	if( count($users) > 0 )
		{
		$sql = "select email from ".BAB_USERS_TBL." where id IN (".$babDB->quote($users).")";
		$result=$babDB->db_query($sql);
		while( $arr = $babDB->db_fetch_array($result))
			{
			$mail->$mailBCT($arr['email']);
			}
		}
	$mail->mailFrom($babAdminEmail, $GLOBALS['babAdminName']);
	$mail->mailSubject(bab_translate("New waiting event"));

	$tempa = new notifyEventApproversCls($period, $calendar);
	$tempa->asHtml();
	$message = $mail->mailTemplate(bab_printTemplate($tempa,"mailinfo.html", "eventwait"));
	$mail->mailBody($message, "html");

	$tempa->asText();
	$message = bab_printTemplate($tempa,"mailinfo.html", "eventwaittxt");
	$mail->mailAltBody($message);

	$mail->send();
	}


function bab_deleteEvent($idevent)
{
	global $babDB;
	include_once $GLOBALS['babInstallPath']."utilit/afincl.php";

	$babDB->db_query("delete from ".BAB_CAL_EVENTS_TBL." where id='".$babDB->db_escape_string($idevent)."'");
	$res2 = $babDB->db_query("select idfai from ".BAB_CAL_EVENTS_OWNERS_TBL." where id_event='".$babDB->db_escape_string($idevent)."'");
	while( $rr = $babDB->db_fetch_array($res2) )
		{
		if( $rr['idfai'] != 0 )
			{
			deleteFlowInstance($rr['idfai']);
			}
		}
	$babDB->db_query("delete from ".BAB_CAL_EVENTS_OWNERS_TBL." where id_event=".$babDB->quote($idevent));
	$babDB->db_query("delete from ".BAB_CAL_EVENTS_NOTES_TBL." where id_event=".$babDB->quote($idevent));
	$babDB->db_query("delete from ".BAB_CAL_EVENTS_REMINDERS_TBL." where id_event=".$babDB->quote($idevent));
}





/**
 * search for availability lock in an array of calendars
 * if one calendar require availability, the function return true
 * @param	array	$calendars
 * @return boolean
 */
function bab_event_availabilityMandatory($calendars) {
	global $babDB;

	$calid_arr = array();
	foreach($calendars as $calendar)
	{
		list($type, $id) = explode('/', $calendar);

		if ('resource' === $type)
		{
			$calid_arr[] = (int) $id;
		}
	}

	$res = $babDB->db_query('
		SELECT
			COUNT(*)
		FROM
			'.BAB_CAL_RESOURCES_TBL.' r,
			'.BAB_CALENDAR_TBL.' c
		WHERE
			r.id = c.owner
			AND c.type=\''.BAB_CAL_RES_TYPE.'\'
			AND c.id IN('.$babDB->quote($calid_arr).')
			AND r.availability_lock=\'1\'
	');

	list($n) = $babDB->db_fetch_row($res);

	return 0 !== (int) $n;
}




class bab_event_posted {

	/**
	 * @var array
	 */
	public $args = array();





	/**
	 *
	 * @var bab_CalendarPeriod
	 */
	private $calendarPeriod;



	/**
	 *
	 * @var Func_CalendarBackend
	 */
	private $backend;



	/**
	 * Get dateTime object from date as array with keys
	 * <ul>
	 *	<li>year</li>
	 *	<li>month<li>
	 *	<li>day</li>
	 *	<li>hours (optional)</li>
	 *	<li>minutes (optional)</li>
	 * <ul>
	 *
	 *
	 * @param	array	$arr
	 *
	 * @param	int		$default_ts default timestamp value to use if values of date are not set
	 *
	 * @return 	BAB_DateTime
	 */
	public static function getDateTime($arr, $default_ts = null) {

		require_once dirname(__FILE__).'/dateTime.php';

		if (!isset($default_ts) && (!isset($arr['year']) || !isset($arr['month']) || !isset($arr['day']))) {
			return null;
		}

		if (!isset($arr['year'])) {
			$arr['year'] = date('Y', $default_ts);
		}

		if (!isset($arr['month'])) {
			$arr['month'] = date('m', $default_ts);
		}

		if (!isset($arr['day'])) {
			$arr['day'] = date('d', $default_ts);
		}

		if (!isset($arr['hours'])) {
			$arr['hours'] = 0;
		}

		if (!isset($arr['minutes'])) {
			$arr['minutes'] = 0;
		}

		return new BAB_DateTime($arr['year'], $arr['month'], $arr['day'], $arr['hours'],$arr['minutes']);
	}


	/**
	 * Backend to use for saving the event
	 * @return Func_CalendarBackend
	 */
	public function getBackend()
	{

		if (!isset($this->backend))
		{

			// find the main calendar of event
			$calendar = bab_getMainCalendar($this->args['selected_calendars']);
			$this->backend = $calendar->getBackend();
		}

		return $this->backend;
	}



	/**
	 * Populate $this->args from POST data
	 *
	 * @param array	$data		The data to use to populate $this->args, if not specified or null, _POST will be used instead.
	 *
	 * array(
	 * 	'evtid' =>
	 * 	'calid' =>
	 * 	'dtstart' =>
	 * 	'title' =>
	 * 	'location' =>
	 *	'category' =>
	 *	'color' =>
	 *	'yearbegin' =>
	 *	'monthbegin' =>
	 *	'daybegin' =>
	 *	'timebegin' =>
	 *	'yearend' =>
	 *	'monthend' =>
	 *	'dayend' =>
	 *	'timeend' =>
	 *	'bprivate' =>
	 *	'block' =>
	 *	'bfree' =>
	 *	'groupe-notif' =>
	 *	'event_owner' =>
	 *	'repeat_cb' =>
	 *	'repeat_yearend' =>
	 *	'repeat_monthend' =>
	 *	'repeat_dayend' =>
	 *	'repeat' =>
	 *	'repeat_n_1' =>
	 *	'repeat_n_2' =>
	 *	'repeat_n_3' =>
	 *	'repeat_n_4' =>
	 *	'repeat_wd' =>
	 *	'creminder' =>
	 * 	'rday' =>		reminder day of month
	 * 	'rhour' =>		reminder hour
	 * 	'rminute' =>	reminder minute
	 * 	'remail' =>
	 * 	'selected_calendars' => array()
	 * 	'description' =>
	 * 	'descriptionformat' =>
	 * )
	 */
	public function createArgsData($data = null)
	{
		global $babBody, $babDB;

		if (!isset($data)) {
			$data =& $_POST;
			include_once $GLOBALS['babInstallPath'].'utilit/editorincl.php';

			$editor = new bab_contentEditor('bab_calendar_event');
			$this->args['description'] = $editor->getContent();
			$this->args['descriptionformat'] = $editor->getFormat();
		} else {
			$this->args['description'] = isset($data['description']) ? $data['description'] : '';
			$this->args['descriptionformat'] = isset($data['descriptionformat']) ? $data['descriptionformat'] : 'html';
		}

		if (isset($data['evtid'])) {
			$this->args['evtid'] = $data['evtid'];
		} else {
			$this->args['evtid'] = '';
		}
		if (isset($data['calid'])) {
			$this->args['calid'] = $data['calid'];
		} else {
			$this->args['calid'] = '';
		}

		$this->args['dtstart'] = isset($data['dtstart']) ? $data['dtstart'] : null;


		if (isset($data['selected_calendars'])) {
			$this->args['selected_calendars'] = $data['selected_calendars'];
		}


		if( !empty($GLOBALS['BAB_SESS_USERID']) && isset($data['creminder']) && $data['creminder'] == 'Y')
			{
			$this->args['alert']['day'] = $data['rday'];
			$this->args['alert']['hour'] = $data['rhour'];
			$this->args['alert']['minute'] = $data['rminute'];
			$this->args['alert']['email'] = isset($data['remail'])? $data['remail']: 'N';
			}




		$this->args['title'] = isset($data['title']) ? $data['title'] : '';
		$this->args['location'] = isset($data['location']) ? $data['location'] : '';

		$this->args['category'] = empty($data['category']) ? '0' : $data['category'];
		$this->args['color'] = empty($data['color']) ? '' : $data['color'];

		$this->args['startdate']['year'] = isset($data['yearbegin']) ? $data['yearbegin'] : null;
		$this->args['startdate']['month'] = isset($data['monthbegin']) ? $data['monthbegin'] : null;
		$this->args['startdate']['day'] = isset($data['daybegin']) ? $data['daybegin'] : null;

		if (isset($data['timebegin'])) {
			$timebegin = $data['timebegin'];
		} else {
			$timebegin = bab_getICalendars()->starttime;
		}

		$tb = explode(':',$timebegin);
		$this->args['startdate']['hours'] = $tb[0];
		$this->args['startdate']['minutes'] = $tb[1];

		$this->args['enddate']['year'] = isset($data['yearend']) ? $data['yearend'] : null;
		$this->args['enddate']['month'] = isset($data['monthend']) ? $data['monthend'] : null;
		$this->args['enddate']['day'] = isset($data['dayend']) ? $data['dayend'] : null;

		if (isset($data['timeend'])) {
			$timeend = $data['timeend'];
		} else {
			if (bab_getICalendars()->endtime > $timebegin) {
				$timeend = bab_getICalendars()->endtime;
			} else {
				$timeend = '23:59:59';
			}
		}

		$tb = explode(':',$timeend);
		$this->args['enddate']['hours'] = $tb[0];
		$this->args['enddate']['minutes'] = $tb[1];


		if( isset($data['bprivate']) && $data['bprivate'] ==  'Y' )
			{
			$this->args['private'] = true;
			}
		else
			{
			$this->args['private'] = false;
			}

		if( isset($data['block']) && $data['block'] ==  'Y' )
			{
			$this->args['lock'] = true;
			}
		else
			{
			$this->args['lock'] = false;
			}

		if( isset($data['bfree']) && $data['bfree'] ==  'Y' )
			{
			$this->args['free'] = true;
			}
		else
			{
			$this->args['free'] = false;
			}

		if( $data['groupe-notif'] ==  '1' )
			{
			$this->args['groupe-notif'] = true;
			}
		else
			{
			$this->args['groupe-notif'] = false;
			}

		$id_owner = $GLOBALS['BAB_SESS_USERID'];

		if (isset($data['event_owner']) && isset(bab_getICalendars()->usercal[$data['event_owner']]) )
			{
			$arr = $babDB->db_fetch_array(
				$babDB->db_query("
					SELECT
						owner

					FROM ".BAB_CALENDAR_TBL."
						WHERE
						id='".$babDB->db_escape_string($data['event_owner'])."'
					"
				)
			);
			$id_owner = isset($arr['owner']) ? $arr['owner'] : $GLOBALS['BAB_SESS_USERID'];
			}
		$this->args['owner'] = $id_owner;




		if (isset($data['repeat_cb']) && $data['repeat_cb'] != 0) {


			$this->args['until'] = array(
				'year'	=> (int) $data['repeat_yearend'],
				'month'	=> (int) $data['repeat_monthend'],
				'day'	=> (int) $data['repeat_dayend']
			);

			$data['repeat'] = isset($data['repeat']) ? $data['repeat'] : '';
			switch ($data['repeat']) {

				case BAB_CAL_RECUR_WEEKLY: /* weekly */
					$this->args['rrule'] = BAB_CAL_RECUR_WEEKLY;
					if( empty($data['repeat_n_2']))
						{
						$data['repeat_n_2'] = 1;
						}

					$this->args['nweeks'] = (int) $data['repeat_n_2'];

					if( isset($data['repeat_wd']) )
						{
						$this->args['rdays'] = $data['repeat_wd'];
						}

					break;

				case BAB_CAL_RECUR_MONTHLY: /* monthly */
					$this->args['rrule'] = BAB_CAL_RECUR_MONTHLY;
					if( empty($data['repeat_n_3']))
						{
						$data['repeat_n_3'] = 1;
						}

					$this->args['nmonths'] = (int) $data['repeat_n_3'];
					break;

				case BAB_CAL_RECUR_YEARLY: /* yearly */
					$this->args['rrule'] = BAB_CAL_RECUR_YEARLY;
					if( empty($data['repeat_n_4']))
						{
						$data['repeat_n_4'] = 1;
						}
					$this->args['nyears'] = (int) $data['repeat_n_4'];
					break;

				case BAB_CAL_RECUR_DAILY: /* daily */
				default:
					$this->args['rrule'] = BAB_CAL_RECUR_DAILY;
					if( empty($data['repeat_n_1']))
						{
						$data['repeat_n_1'] = 1;
						}

					$this->args['ndays'] = (int) $data['repeat_n_1'];
					break;
			}
		}
	}





	/**
	 * Test validity of the args array
	 * @param	string	&$msgerror
	 * @return unknown_type
	 */
	public function isValid(&$msgerror)
	{

		require_once dirname(__FILE__).'/calincl.php';


		if( empty($this->args['title']))
			{
			$msgerror = bab_translate("You must provide a title");
			return false;
		}







		if (!empty($this->args['evtid'])) {

			$calendar = bab_getICalendars()->getEventCalendar($this->args['calid']);

			if (!$calendar)
			{
				throw new Exception('Missing calendar '.$this->args['calid']);
			}

			$backend = $calendar->getBackend();

			$period = $backend->getPeriod($backend->CalendarEventCollection($calendar), $this->args['evtid']);

			$begin 	= bab_event_posted::getDateTime($this->args['startdate'], $period->ts_begin);
			$end 	= bab_event_posted::getDateTime($this->args['enddate'], $period->ts_end);

		} else {

			$begin 	= bab_event_posted::getDateTime($this->args['startdate']);
			$end 	= bab_event_posted::getDateTime($this->args['enddate']);
		}



		if (isset($this->args['until'])) {
			$repeatdate = bab_event_posted::getDateTime($this->args['until']);
			$repeatdate->add(1, BAB_DATETIME_DAY);

			if( $repeatdate->getTimeStamp() < $end->getTimeStamp()) {
				$msgerror = bab_translate("Repeat date must be older than end date");
				return false;
			}
		}



		if( $begin->getTimeStamp() >= $end->getTimeStamp())
			{
			$msgerror = bab_translate("End date must be older");
			return false;
			}


		$idcals = $this->args['selected_calendars'];

		if(0 === count($idcals))
			{
			$msgerror = bab_translate("You must select at least one calendar type");
			return false;
		}


		// verification of attendees

		foreach($idcals as $urlidentifier)
		{
			$calendar = bab_getICalendars()->getEventCalendar($urlidentifier);
			if ($calendar instanceof bab_PersonalCalendar)
			{
				$id_user = $calendar->getIdUser();
				$cn = bab_getUserName($id_user);
				$email = bab_getUserEmail($id_user);

				if (null === bab_getUserIdByEmailAndName($email, $cn))
				{
					$msgerror = sprintf(bab_translate("%s cannot be added as an attendee on event because his email address is not unique in the list of users"), $cn);
					return false;
				}
			}
		}


		return true;
	}






	/**
	 * @throws ErrorException
	 * 
	 * 
	 * @param	bool	$delete		allow delete of event if calid not in selected calendars (event moved from one calendar to another)
	 * 								do not delete existing event in availability check
	 * 
	 * @return bab_CalendarPeriod
	 */
	private function getCalendarPeriod($delete = true)
	{
		if (!isset($this->calendarPeriod))
		{
			if (!empty($this->args['evtid'])) {

				// calid is the calendar where the event is recorded
				// if calid is not in the list of selected calendars, the event must be canceled and a new calendar must be chosen to record the event
				
				$allowed = array_flip($this->args['selected_calendars']);
				$calendar = bab_getICalendars()->getEventCalendar($this->args['calid']);
				
				if (isset($allowed[$this->args['calid']]))
				{
					
					
					if (!$calendar)
					{
						throw new Exception('Missing calendar '.$this->args['calid']);
					}
				} else if ($delete) {
					
					// delete event
				
					$backend = $calendar->getBackend();
					$collection = $backend->CalendarEventCollection($calendar);
					
					$period = bab_createCalendarPeriod($backend, $this->args, $collection);
					
					$period->setProperty('STATUS', 'CANCELLED');
					$backend->savePeriod($period, 'CANCEL');
				
					
					$this->args['evtid'] = null;
				} 
			} 

			
			
			
			if (empty($this->args['evtid']))
			{
				$calendar = bab_getMainCalendar($this->args['selected_calendars']);
			}	
			
			
			$backend = $calendar->getBackend();
			$collection = $backend->CalendarEventCollection($calendar);
			
			
			$calendarPeriod = bab_createCalendarPeriod($backend, $this->args, $collection);
			
			if (empty($this->args['evtid'])) {
				
				// creation
				
				$calendarPeriod->setProperty(
					'ORGANIZER;CN='.$calendarPeriod->escape($GLOBALS['BAB_SESS_USER']), 
					'MAILTO:'.bab_getUserEmail($GLOBALS['BAB_SESS_USERID'])
				);
			}
			
			
			if (!$delete)
			{
				// do not save in cache if delete not allowed 
				return $calendarPeriod;
			}
			
			$this->calendarPeriod = $calendarPeriod;
		}

		return $this->calendarPeriod;
	}



	/**
	 * Save event
	 *
	 * @param	string &$message
	 *
	 * @return bool
	 */
	public function save(&$message, $updateMethod = null) {

		try {
			$calendarPeriod = $this->getCalendarPeriod();
		} catch(ErrorException $e) {
			$message = $e->getMessage();
			return false;
		}

		// call backend to save calendar period

		$collection = $calendarPeriod->getCollection();
		$calendar = $collection->getCalendar();
		$backend = $calendar->getBackend();

		if (!isset($updateMethod)) {
			$updateMethod = (int) bab_pp('bupdrec');
		}

		bab_addHashEventsToCollection($collection, $calendarPeriod, $updateMethod, $this->args['dtstart']);

		$oldrelations = array();
		$oldcalendars = array();
		$uid = $calendarPeriod->getProperty('UID');

		if ($uid)
		{
			$oldevent = $backend->getPeriod($backend->CalendarEventCollection($calendar), $uid);

			if (!$oldevent)
			{
				throw new Exception('event not found UID='.$uid);
			}

			if (!$calendar->canUpdateEvent($oldevent))
			{
				$message = bab_translate(sprintf('Modification of this event on calendar %s is not allowed', $calendar->getName()));
				return false;
			}

			$oldrelations = $oldevent->getRelations();
			$oldcalendars = $oldevent->getCalendars();
		}

		if (!$uid && !$calendar->canAddEvent())
		{
			$message = bab_translate(sprintf('Creation of an event on calendar %s is not allowed', $calendar->getName()));
			return false;
		}

		bab_debug('<h1>$backend->SavePeriod()</h1>'. $calendarPeriod->toHtml(), DBG_TRACE, 'CalendarBackend');
		$backend->savePeriod($calendarPeriod);
		$calendarPeriod->commitEvent();


		$this->notifyRelationsApprobation($calendarPeriod, $oldrelations);

		if($this->args['groupe-notif']){
			$this->notifyUpdate($calendarPeriod, $oldcalendars);
		}


		$min = $calendarPeriod->ts_begin;
		$max = $calendarPeriod->ts_end;

		foreach($collection as $period)
		{
			if ($min > $period->ts_begin) 	{ $min = $period->ts_begin; }
			if ($max < $period->ts_end) 	{ $max = $period->ts_end; 	}
		}


		include_once $GLOBALS['babInstallPath'].'utilit/eventperiod.php';
		$event = new bab_eventPeriodModified($min, $max, false);
		$event->types = BAB_PERIOD_CALEVENT;
		bab_fireEvent($event);


		return true;
	}


	/**
	 * For new relations added to event, notify approvers
	 *
	 * @param	bab_CalendarPeriod	$calendarPeriod
	 * @param	Array				$oldrelations
	 *
	 * @return unknown_type
	 */
	private function notifyRelationsApprobation(bab_CalendarPeriod $calendarPeriod, Array $oldrelations)
	{
		require_once dirname(__FILE__).'/eventperiod.php';
		require_once dirname(__FILE__).'/afincl.php';

		$newrelations = $calendarPeriod->getRelations();


		foreach($newrelations as $urlidentifier => $relation)
		{
			if (isset($oldrelations[$urlidentifier]))
			{
				// already notified, ongoing instance
				continue;
			}

			$wfinstance = $relation['X-CTO-WFINSTANCE'];

			if( $wfinstance )
			{
				// approbation instance, notify approvers

				$nfusers = getWaitingApproversFlowInstance($wfinstance, true);
				notifyEventApprovers($calendarPeriod, $nfusers, $relation['calendar']);
			}

		}
	}



	/**
	 * For modifications made in event, notify the relations and organizer
	 *
	 * @param	bab_CalendarPeriod	$calendarPeriod
	 * @param	Array				$oldcalendars
	 *
	 * @return unknown_type
	 */
	private function notifyUpdate(bab_CalendarPeriod $calendarPeriod, Array $oldcalendars)
	{
		require_once dirname(__FILE__).'/eventperiod.php';

		// notify remaining calendars of an update
		$update = new bab_eventAfterEventUpdate;
		$update->setPeriod($calendarPeriod);
		$updatevalid = false;


		// notify the new added calendars of the new appointement
		$added = new bab_eventAfterEventAdd;
		$added->setPeriod($calendarPeriod);
		$addedvalid = false;


		// notify the removed calendars
		$removed = new bab_eventAfterEventDelete;
		$removed->setPeriod($calendarPeriod);
		$removedvalid = false;


		foreach($calendarPeriod->getCalendars() as $calendar)
		{
			$identifier = $calendar->getUrlIdentifier();

			if (isset($oldcalendars[$identifier]))
			{
				$update->addCalendar($calendar);
				$updatevalid = true;
				unset($oldcalendars[$identifier]);
			}
			else
			{
				$added->addCalendar($calendar);
				$addedvalid = true;
			}
		}

		// old calendars no more in event

		foreach($oldcalendars as $calendar)
		{
			$removed->addCalendar($calendar);
			$removedvalid = true;
		}



		if ($updatevalid)	{	bab_fireEvent($update);		}
		if ($addedvalid)	{	bab_fireEvent($added);		}
		if ($removedvalid)	{	bab_fireEvent($removed);	}

	}


	/**
	 * Verify form data and
	 * test availability on all events
	 *
	 * @param	string	&$message
	 * @return boolean
	 */
	public function availabilityCheckAllEvents(&$message) {

		require_once dirname(__FILE__).'/cal.rrule.class.php';

		try {
			$calendarPeriod = $this->getCalendarPeriod(false);
		} catch(ErrorException $e) {
			$message = $e->getMessage();
			return false;
		}

		// expend event to collection

		$calendars = $calendarPeriod->getCalendars();
		$collection = bab_CalendarRRULE::getCollection($calendarPeriod);

		$result = true;
		foreach($collection as $period)
		{
			if (!bab_event_posted::availabilityCheck(array_keys($calendars), $period))
			{
				$result = false;
			}
		}

		return $result;
	}



	/**
	 * Test availability on period
	 * On conflicts, this function fill the list of conflicts
	 * @see 	bab_event_posted::availabilityConflictsStore()
	 * @static
	 *
	 * @param	array				$calid
	 * @param	bab_CalendarPeriod	$period
	 *
	 * @return boolean			true : period available	/ false : period unavailable
	 */
	public static function availabilityCheck($calid, bab_CalendarPeriod $period) {

		require_once $GLOBALS['babInstallPath'] . 'utilit/mcalincl.php';
		if ($period->isTransparent())
		{
			return true;
		}

		$availability_msg_list = array();
		$availability_conflicts_calendars = array();

		$sdate = date('Y-m-d H:i:s', $period->ts_begin);
		$edate = date('Y-m-d H:i:s', $period->ts_end);


		// working hours test

		$whObj = bab_mcalendars::create_events($sdate, $edate, $calid);
		if ($period->getProperty('UID'))
		{
			if (!$whObj->setAvailability($period, true))
			{
				// Event not found within boundaries
				// it is possible when a new calendar is added to event
			}
		}


		$AvaReply = $whObj->getAvailability();


		$mcals = new bab_mcalendars($sdate, $edate, $calid);
		foreach($AvaReply->conflicts_events as $calPeriod) {


			if (!$period->getProperty('UID') || $period->getProperty('UID') != $calPeriod->getProperty('UID'))
				{

				$title = bab_translate("Private");
				$collection = $calPeriod->getCollection();
				$calendar = null;
				if ($collection instanceof bab_CalendarEventCollection) {

					$calendar = $collection->getCalendar();

					if(isset($calendar) && $calendar->canViewEventDetails($calPeriod))
					{
						$title = $calPeriod->getProperty('SUMMARY');
					}

					$calendar_labels = array();
					$cals = $mcals->getEventCalendars($calPeriod);
					foreach($cals as $id_cal => $arr) {
						$availability_conflicts_calendars[] = $id_cal;
						$calendar_labels[] = $arr['name'];
					}

					$availability_msg_list[$calPeriod->getProperty('UID')] = implode(', ', $calendar_labels).' '.bab_translate("on the event").' : '. $title .' ('.bab_shortDate($calPeriod->ts_begin,false).')';
				}
			}
		}





		if (false === $AvaReply->status && count($availability_msg_list) === 0) {

			if (0 < count($AvaReply->available_periods)) {

				// si il y a une periode dispo, l'afficher
				reset($AvaReply->available_periods);
				$calPeriod = current($AvaReply->available_periods);
				$availability_msg_list[] = sprintf(
					bab_translate('There is a conflict with working hours, the next available period is : %s to %s'),
					bab_shortDate($calPeriod->ts_begin),
					bab_time($calPeriod->ts_end)
				);

			} else {

				$availability_msg_list[] = bab_translate("There is a conflict with working hours of the selected personnal calendars");

			}
		}

		if (count($availability_msg_list) > 0)
			{
			bab_event_posted::availabilityConflictsStore('MSG', $availability_msg_list);
			bab_event_posted::availabilityConflictsStore('CAL', $availability_conflicts_calendars);

			return false;
			}
		else
			{
			return true;
			}
	}



	/**
	 * Register an array of message for availability check
	 * if called without parameters, this method return the list
	 *
	 * @static
	 * @param	string	$object_key		( 'MSG' | 'CAL' )
	 * @param	array	[$arr]
	 * @param	array
	 */
	function availabilityConflictsStore($object_key, $arr = NULL) {

		static $memory = array();

		if (!isset($memory[$object_key])) {
			$memory[$object_key] = array();
		}

		if (NULL !== $arr) {
			$memory[$object_key] += $arr;
		}

		return $memory[$object_key];
	}





	/**
	 * Test if availablity is mandatory after the availablity test
	 * this method use the calendars in conflicts list
	 * @see bab_event_posted::availabilityConflictsStore()
	 *
	 * @param	array	$calid 		Calendars of the request
	 *
	 * @return boolean
	 */
	public static function availabilityIsMandatory($calid) {

		$calendars = bab_event_posted::availabilityConflictsStore('CAL');
		$calendars = array_unique($calendars);

		return bab_event_availabilityMandatory($calendars) && bab_event_availabilityMandatory($calid);
	}
}








/**
 * Add hash events to collection
 *
 * @param	bab_CalendarEventCollection		$collection			A collection of calendar events with a hash and one event
 * @param	bab_CalendarPeriod				$calendarPeriod		the modified period
 * @param	int								$method				BAB_CAL_EVT_ALL | BAB_CAL_EVT_CURRENT | BAB_CAL_EVT_PREVIOUS | BAB_CAL_EVT_NEXT
 * @param	string							$dtstart			DTSTART of original event to use for event modification
 *
 * @return unknown_type
 */
function bab_addHashEventsToCollection(bab_CalendarEventCollection $collection, bab_CalendarPeriod $calendarPeriod, $method, $dtstart = null)
{
	require_once dirname(__FILE__).'/dateTime.php';

	$method = (int) $method;

	if (null === $dtstart)
	{
		$dtstart = $calendarPeriod->getProperty('DTSTART');
	}


	switch($method)
	{
		case BAB_CAL_EVT_ALL:
			// no RECURRENCE-ID mean all instances of event
			break;
		case BAB_CAL_EVT_CURRENT:
			$calendarPeriod->setProperty('RECURRENCE-ID;VALUE=DATE-TIME', $dtstart);
			return;
		case BAB_CAL_EVT_PREVIOUS:
			$calendarPeriod->setProperty('RECURRENCE-ID;RANGE=THISANDPRIOR', $dtstart);
			break;
		case BAB_CAL_EVT_NEXT:
			$calendarPeriod->setProperty('RECURRENCE-ID;RANGE=THISANDFUTURE', $dtstart);
			break;
	}


	if (!$collection->hash)
	{
		return;
	}

	$calendar = $collection->getCalendar();
	$backend = $calendar->getBackend();

	$C = $backend->Criteria();


	$criteria = $C->Hash($collection->hash)
		->_AND_($C->Collection(get_class($collection)))
		->_AND_($C->Calendar($calendar));


	if (BAB_CAL_EVT_PREVIOUS === $method)
	{
		$criteria->_AND_($C->End(BAB_DateTime::fromTimeStamp($calendarPeriod->ts_begin)));
	}

	if (BAB_CAL_EVT_NEXT === $method)
	{
		$criteria->_AND_($C->Begin(BAB_DateTime::fromTimeStamp($calendarPeriod->ts_end)));
	}

	$userperiods = $backend->selectPeriods($criteria);



	$ref_begin = BAB_DateTime::fromTimeStamp($calendarPeriod->ts_begin);
	$ref_end = BAB_DateTime::fromTimeStamp($calendarPeriod->ts_end);

	$bh = $ref_begin->getHour();
	$bm = $ref_begin->getMinute();
	$bs = $ref_begin->getSecond();

	$eh = $ref_end->getHour();
	$em = $ref_end->getMinute();
	$es = $ref_end->getSecond();

	foreach($userperiods as $key => $period)
	{
		if ($period->getProperty('UID') !== $calendarPeriod->getProperty('UID'))
		{
			$updatePeriod = clone $calendarPeriod;
			$updatePeriod->setProperty('UID', $period->getProperty('UID'));

			$begin = BAB_DateTime::fromTimeStamp($period->ts_begin)->setTime($bh, $bm, $bs);
			$end   = BAB_DateTime::fromTimeStamp($period->ts_end)  ->setTime($eh, $em, $es);

			$updatePeriod->setDates($begin, $end);

			$collection->addPeriod($updatePeriod);
		}
	}
}








function bab_getPropertiesString(&$calPeriod, &$t_option)
{
	$el = array();

	if (!$calPeriod->isPublic()) {
		$el[] = bab_translate('Private');
	}

	$arr = $calPeriod->getData();

	if (isset($arr['block']) && 'Y' == $arr['block']) {
		$el[] = bab_translate('Locked');
	}

	if ('TRANSPARENT' === $calPeriod->getProperty('TRANSP')) {
		$el[] = bab_translate('Free');
	}

	$t_option = count($el) > 1 ? bab_translate("Options") : bab_translate("Option");
	if (count($el) > 0)
		return implode(', ',$el);
	else
		return '';
}