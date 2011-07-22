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

/**
* @internal SEC1 PR 20/02/2007 FULL
*/

include_once 'base.php';
include_once $babInstallPath.'utilit/calincl.php';
include_once $babInstallPath.'utilit/evtincl.php';







class displayAttendeesCls
	{
	var $altbg = true;
	var $fullnametxt;
	var $diskspacetxt;
	var $kilooctet;
	var $arrinfo;
	var $fullname;
	var $diskspace;

	private $period;

	public function __construct($evtid, $dtstart, $idcal)
		{
		global $babBody, $babDB;
		$this->access = false;
		$this->evtid = $evtid;
		$this->dtstart = $dtstart;
		$this->idcal = $idcal;

		$calendar = bab_getICalendars()->getEventCalendar($idcal);
		if (!$calendar)
		{
			$babBody->addError(bab_translate("Access denied"));
			return;
		}

		$this->access = true;

		$this->invitationstatus = bab_translate("Status of my invitation to the appointment");
		$this->attendeestxt = bab_translate("Attendee");
		$this->publicstxt = bab_translate("Public calendar");
		$this->resourcestxt = bab_translate("Resource");

		$this->statusdef = array(
			'NEEDS-ACTION'	=> bab_translate("Waiting for approbation"),
			'ACCEPTED' => bab_translate("Accepted"),
			'DECLINED' => bab_translate("Declined")
		);
		$this->responsetxt = bab_translate("Response");
		$this->statustxt = bab_translate("Status");

		$this->t_accept = bab_translate("Accept");
		$this->t_reject = bab_translate("Reject");

		$backend = $calendar->getBackend();
		$this->period = $backend->getPeriod($backend->CalendarEventCollection($calendar), $evtid, $dtstart);
		bab_debug('<h1>$backend->getPeriod()</h1>'. $this->period->toHtml(), DBG_TRACE, 'CalendarBackend');

		if (!$this->period)
		{
			throw new Exception('Event not found backend='.get_class($backend).' UID='.$evtid.' DTSTART='.$dtstart);
		}

		$this->attendees = $this->period->getAllAttendees();


		$this->publics = array();
		$this->resources = array();

		$relations = $this->period->getRelations();
		foreach($relations as $relation)
		{
			// only relations with calendars from ovidentia backend displayed

			if ($relation['calendar'] instanceof bab_OviPublicCalendar)
			{
				$this->publics[] = $relation;
			}

			if ($relation['calendar'] instanceof bab_OviResourceCalendar)
			{
				$this->resources[] = $relation;
			}
		}




		$this->statusarray = array();


		foreach($this->attendees as $attendee)
		{
			if (isset($attendee['calendar']))
			{
				$user = (int) $attendee['calendar']->getIdUser();
				if ($user === (int) $GLOBALS['BAB_SESS_USERID'])
				{
					switch($attendee['PARTSTAT'])
					{
						case 'NEEDS-ACTION':
							$this->statusarray = array('ACCEPTED','DECLINED');
							break;
						case 'ACCEPTED':
							$this->statusarray = array('DECLINED');
							break;
						case 'DECLINED':
							$this->statusarray = array('ACCEPTED');
							break;
					}

					break;
				}
			}
		}

		reset($this->attendees);


		$this->countstatus = count($this->statusarray);
		if( $this->countstatus )
			{
			$this->updatetxt = bab_translate("Update");
			$this->confirmtxt = bab_translate("Confirm");
			$this->commenttxt = bab_translate("Raison");
			$this->accepttxt = bab_translate("Accept");
			$this->declinetxt = bab_translate("Decline");

			$collection = $this->period->getCollection();

			if( !empty($collection->hash) || $this->period->getProperty('RRULE'))
				{
				$this->repetitivetxt = bab_translate("This is recurring event. Do you want to update this occurence or series?");
				$this->all = bab_translate("All");
				$this->thisone = bab_translate("This occurence");
				$this->brepetitive = true;
				}
			else
				{
				$this->brepetitive = false;
				}
			}

		}

	public function getnextattendee()
		{
		if( list(,$arr) = each($this->attendees))
			{
			$this->altbg = !$this->altbg;
			$this->fullname = isset($arr['CN']) ? $arr['CN'] : $arr['email'];

			$this->external = false;
			if (!isset($arr['calendar']))
				{
				$this->external = true;
				}

			if (isset($this->statusdef[$arr['PARTSTAT']]))
			{
				$this->status = $this->statusdef[$arr['PARTSTAT']];
			} else {
				$this->status = '';
			}
			return true;
			}

		return false;
		}



	/**
	 * @param 	bab_EventCalendar 	$calendar
	 * @param	string				$status
	 * @return string
	 */
	private function getStatus(bab_EventCalendar $calendar, $status)
	{
		if (!$calendar->getApprobationSheme())
		{
			return bab_translate('No validation');
		}

		switch($status)
		{
			case 'NEEDS-ACTION':
				return bab_translate('Waiting for approbation');
				break;
			case 'ACCEPTED':
				return bab_translate('Accepted');
				break;
			case 'DECLINED':
				return bab_translate('Declined');
				break;
		}

		return '';
	}

	/**
	 *
	 * @param int $idfai
	 * @return bool
	 */
	private function isApprover($idfai)
	{
		if (!$idfai)
		{
			return false;
		}

		$arrschi = bab_getWaitingIdSAInstance($GLOBALS['BAB_SESS_USERID']);
		bab_debug($arrschi);

		if (in_array($idfai, $arrschi))
		{
			return true;
		}

		return false;
	}


	private function approbUrl(bab_EventCalendar $calendar, $status)
	{
		require_once dirname(__FILE__).'/utilit/urlincl.php';
		$url = bab_url::get_request('tg', 'idx', 'evtid', 'idcal');
		$url->idx = 'approb';
		$url->relation = $calendar->getUrlIdentifier();
		$url->approbstatus = $status;

		return $url->toString();
	}


	public function getnextpublic()
	{
		if( list(,$relation) = each($this->publics))
			{
			$calendar = $relation['calendar'];
			$this->altbg = !$this->altbg;
			$this->name = $calendar->getName();
			$this->status = $this->getStatus($calendar, $relation['X-CTO-STATUS']);
			$this->approver = $this->isApprover($relation['X-CTO-WFINSTANCE']);

			$this->accepturl = $this->approbUrl($calendar, BAB_CAL_STATUS_ACCEPTED);
			$this->rejecturl = $this->approbUrl($calendar, BAB_CAL_STATUS_DECLINED);

			return true;
			}

		return false;
	}


	public function getnextresource()
	{
		if( list(,$relation) = each($this->resources))
			{
			$calendar = $relation['calendar'];
			$this->altbg = !$this->altbg;
			$this->name = $calendar->getName();
			$this->status = $this->getStatus($calendar, $relation['X-CTO-STATUS']);
			$this->approver = $this->isApprover($relation['X-CTO-WFINSTANCE']);

			$this->accepturl = $this->approbUrl($calendar, BAB_CAL_STATUS_ACCEPTED);
			$this->rejecturl = $this->approbUrl($calendar, BAB_CAL_STATUS_DECLINED);

			return true;
			}

		return false;
	}


	public function getnextstatus()
		{
		global $babBody;
		static $i = 0;
		if( $i < $this->countstatus)
			{
			$this->statusname = $this->statusdef[$this->statusarray[$i]];
			$this->statusval = $this->statusarray[$i];

			$i++;
			return true;
			}
		else
			{
			return false;
			}
		}

	public function getHtml()
		{
		return bab_printTemplate($this, "calendar.html", "listattendees");
		}
	}










class displayApprobCalendarCls
{


	public function __construct($evtid, $idcal, $relation)
	{
		global $babBody, $babDB;

		$this->evtid = $evtid;
		$this->idcal = $idcal;
		$this->relation = $relation;

		$calendar = bab_getICalendars()->getEventCalendar($idcal);
		$backend = $calendar->getBackend();
		$period = $backend->getPeriod($backend->CalendarEventCollection($calendar), $evtid);

		$this->commenttxt = bab_translate("Reason");
		$this->updatetxt = bab_translate("Update");
		$this->approbstatus = bab_toHtml(sprintf(bab_translate("Approbation for %s"), $calendar->getName()));


		$this->statusarray = array(
			BAB_CAL_STATUS_DECLINED => bab_translate('Reject'),
			BAB_CAL_STATUS_ACCEPTED => bab_translate('Accept')
		);



		$collection = $period->getCollection();

		if( !empty($collection->hash))
			{
			$this->repetitivetxt = bab_translate("This is recurring event. Do you want to update this occurence or series?");
			$this->all = bab_translate("All");
			$this->thisone = bab_translate("This occurence");
			$this->brepetitive = true;
			}
		else
			{
			$this->brepetitive = false;
			}

	}


	public function getnextstatus()
	{
		global $babBody;

		if( list($val, $name) = each($this->statusarray))
			{
			$this->statusname = bab_toHtml($name);
			$this->statusval = $val;
			$this->selected = $val === (int) bab_rp('approbstatus');

			return true;
			}

		return false;
	}


	public function getHtml()
	{
		return bab_printTemplate($this, "calendar.html", "approbcalendar");
	}
}













class displayEventDetailCls
{

	public function __construct($evtid, $dtstart, $idcal)
	{
		require_once $GLOBALS['babInstallPath'].'utilit/dateTime.php';
		global  $babBody, $babDB;
		$this->access = false;

		$calendar = bab_getICalendars()->getEventCalendar($idcal);

		if (!$calendar) {
			$babBody->addError(bab_translate("Access denied to the calendar"));
			return;
		}

		$backend = $calendar->getBackend();
		$calendarPeriod = $backend->getPeriod($backend->CalendarEventCollection($calendar), $evtid, $dtstart);

		if (!$calendarPeriod) {
			$babBody->addError(bab_translate("There is no additional informations for this event"));
			return;
		}

		bab_debug($calendarPeriod->toHtml());

		$this->access = true;
		$this->idcal = $idcal;

		$this->begindatetxt = bab_translate("Begin date");
		$this->enddatetxt = bab_translate("End date");
		$this->titletxt = bab_translate("Title");
		$this->desctxt = bab_translate("Description");
		$this->locationtxt = bab_translate("Location");
		$this->cattxt = bab_translate("Category");
		$this->t_organizer = bab_translate("Organized by");

		$this->begindate = bab_toHtml(bab_longDate($calendarPeriod->ts_begin));
		$this->enddate = bab_toHtml(bab_longDate($calendarPeriod->ts_end));

		$this->t_option = '';
		$this->properties = bab_toHtml(bab_getPropertiesString($calendarPeriod, $this->t_option));
		$this->organizer = '';

		if (!$calendar->canViewEventDetails($calendarPeriod)) {
			$this->title= '';
			$this->description = '';
			$this->location = '';
			$this->category = '';
		} else {
			$this->title = bab_toHtml($calendarPeriod->getProperty('SUMMARY'));
			$this->description	= bab_toHtml($calendarPeriod->getProperty('DESCRIPTION'));

			if ($organizer = $calendarPeriod->getOrganizer()) {
				if (isset($organizer['name'])) {
					$this->organizer = bab_toHtml($organizer['name']);
				} else {
					$this->organizer = bab_toHtml($organizer['email']);
				}
			}


			$data = $calendarPeriod->getData();

			// display html from WYSIWYG if any :

			if (isset($data['description']) && isset($data['description_format']) && 'html' === $data['description_format']) {
				include_once $GLOBALS['babInstallPath']."utilit/editorincl.php";
				$editor = new bab_contentEditor('bab_calendar_event');
				$editor->setContent($data['description']);
				$editor->setFormat($data['description_format']);

				$this->description = $editor->getHtml();
			}

			$this->location= bab_toHtml($calendarPeriod->getProperty('LOCATION'));
			$this->category = bab_toHtml($calendarPeriod->getProperty('CATEGORIES'));
		}


		$this->bshowupadetinfo = false;
		if (bab_getICalendars()->show_update_info === 'Y') {
			$this->bshowupadetinfo = true;
			$this->modifiedontxt = bab_translate("Created/Updated on");
			$this->bytxt = bab_translate("By");
			if ($calendarPeriod->getProperty('LAST-MODIFIED') !== '') {
				$this->updatedate = bab_toHtml(bab_shortDate(BAB_DateTime::fromICal($calendarPeriod->getProperty('LAST-MODIFIED'))->getTimeStamp(), true));
			} else {
				$this->updatedate = '';
			}

			$data = $calendarPeriod->getData();

			$this->updateauthor = false;
			if (isset($data['id_modifiedby'])) {
					$this->updateauthor = bab_toHtml(bab_getUserName($data['id_modifiedby']));
			}
		}
	}


	public function getHtml()
	{
		return bab_printTemplate($this, "calendar.html", "eventdetail");
	}
}







class displayEventNotesCls
	{

	function displayEventNotesCls($evtid, $idcal)
		{
		global $babBody, $babDB;
		$this->access = false;

		$calendar = bab_getICalendars()->getEventCalendar($idcal);

		if (!($calendar instanceof bab_OviEventCalendar))
		{
			return;
		}

		if (!$calendar)
		{
			$babBody->addError(bab_translate("Access denied to the calendar"));
			return;
		}

		$backend = $calendar->getBackend();
		$calendarPeriod = $backend->getPeriod($backend->CalendarEventCollection($calendar), $evtid);

		if (!$calendarPeriod)
		{
			$babBody->addError(bab_translate("There is no additional informations for this event"));
			return;
		}


		$this->access = true;
		$this->idcal = $idcal;
		$this->evtid = $evtid;
		$this->notetxt = bab_translate("Personal notes");
		$this->updatetxt = bab_translate("Update");

		$data = $calendarPeriod->getData();
		$this->noteval = isset($data['note']) ? bab_toHtml((string) $data['note']) : '';



		$collection = $calendarPeriod->getCollection();

		if( !empty($collection->hash))
			{
			$this->all = bab_translate("All");
			$this->thisone = bab_translate("This occurence");
			$this->brecevt = true;
			}
		else
			{
			$this->brecevt = false;
			}
		}

	public function getHtml()
		{
			if (!$this->access)
			{
				return '';
			}

		return bab_printTemplate($this, "calendar.html", "eventnotes");
		}
	}





class displayEventAlertCls
	{

	function displayEventAlertCls($evtid, $dtstart, $idcal)
		{
		global  $babBody, $babDB;
		$this->access = false;
		$calendar = bab_getICalendars()->getEventCalendar($idcal);

		if (!$calendar)
		{
			$babBody->addError(bab_translate("Access denied to the calendar"));
			return;
		}

		$backend = $calendar->getBackend();
		$calendarPeriod = $backend->getPeriod($backend->CalendarEventCollection($calendar), $evtid, $dtstart);

		if (!$calendarPeriod)
		{
			$babBody->addError(bab_translate("There is no additional informations for this event"));
			return;
		}

		$this->access = true;

		$this->rcheckedval = '';
		$alarm = $calendarPeriod->getAlarm();
		if (isset($alarm))
		{
			foreach($alarm->getAttendees() as $attendee)
			{
				$id_user = $attendee['calendar']->getIdUser();
				if ($id_user && $id_user == $GLOBALS['BAB_SESS_USERID'])
				{
					$this->rcheckedval = 'checked';
					break;
				}
			}
		}

		$this->idcal = $idcal;
		$this->evtid = $evtid;
		$this->dtstart = $dtstart;
		$this->alerttxt = bab_translate("Reminder");
		$this->updatetxt = bab_translate("Update");

		if ($this->rcheckedval)
		{
			$this->arralert = array();
			$this->rmcheckedval = '';

			$action = $alarm->getProperty('ACTION');
			$trigger = $alarm->getProperty('TRIGGER');

			if (0 === mb_strpos($trigger, '-P') && preg_match_all('/(?P<value>\d+)(?P<type>[DHM]{1})/', $trigger, $m, PREG_SET_ORDER)) {

				foreach($m as $trigger)
				{
					$val = $trigger['value'];
					switch($trigger['type'])
					{
						case 'D': $this->arralert['day'] 	= (int) $val; 	break;
						case 'H': $this->arralert['hour'] 	= (int) $val;	break;
						case 'M': $this->arralert['minute'] = (int) $val;	break;
					}
				}


				if ('EMAIL' == $action)
				{
					$this->rmcheckedval = 'checked';
				}

			}


		} else {
			$this->arralert = array();
		}


		$collection = $calendarPeriod->getCollection();
		if( !empty($collection->hash) || $calendarPeriod->getProperty('RRULE'))
			{
			$this->all = bab_translate("All");
			$this->thisone = bab_translate("This occurence");
			$this->brecevt = true;
			}
		else
			{
			$this->brecevt = false;
			}

		$this->days = array(0, 1, 2, 3, 5, 6, 7, 8, 10, 11, 12);
		$this->hours = array(0, 1, 2, 3, 5, 6, 7, 8, 10, 11, 12);
		$this->minutes = array(0, 5, 10, 15, 30, 45);


		if( isset($GLOBALS['babEmailReminder']) && $GLOBALS['babEmailReminder'])
			{
			$this->remailtxt = bab_translate("Use email reminder");
			}
		else
			{
			$this->remailtxt = "";
			}

		}


	function getnextday()
		{
		static $i=0;
		if( $i < count($this->days))
			{
			$this->dval = $this->days[$i];
			$this->dname = $this->dval." ";
			if( $i < 2 )
				{
				$this->dname .= bab_translate("day");
				}
			else
				{
				$this->dname .= bab_translate("days");
				}
			if( isset($this->arralert['day']) && $this->dval == $this->arralert['day'])
				{
				$this->dselected = 'selected';
				}
			else
				{
				$this->dselected = '';
				}
			$i++;
			return true;
			}
		else
			{
			$i = 0;
			return false;
			}
		}

	function getnexthour()
		{
		static $i=0;
		if( $i < count($this->hours))
			{
			$this->hval = $this->hours[$i];
			$this->hname = $this->hval." ";
			if( $i < 2 )
				{
				$this->hname .= bab_translate("hour");
				}
			else
				{
				$this->hname .= bab_translate("hours");
				}
			$i++;
			if( isset($this->arralert['hour']) && $this->hval == $this->arralert['hour'])
				{
				$this->hselected = 'selected';
				}
			else
				{
				$this->hselected = '';
				}
			return true;
			}
		else
			{
			$i = 0;
			return false;
			}
		}

	function getnextminute()
		{
		static $i=0;
		if( $i < count($this->minutes))
			{
			$this->mval = $this->minutes[$i];
			$this->mname = $this->mval." ";
			if( $i == 0 )
				{
				$this->mname .= bab_translate("minute");
				}
			else
				{
				$this->mname .= bab_translate("minutes");
				}
			if( isset($this->arralert['minute']) && $this->mval == $this->arralert['minute'])
				{
				$this->mselected = 'selected';
				}
			else
				{
				$this->mselected = '';
				}
			$i++;
			return true;
			}
		else
			{
			$i = 0;
			return false;
			}
		}


		public function getHtml()
		{
			return bab_printTemplate($this, "calendar.html", "eventalert");
		}
	}













function displayAttendees($evtid, $dtstart, $idcal)
{
	global $babBody;


	$details = new displayEventDetailCls($evtid, $dtstart, $idcal);
	$attendees = new displayAttendeesCls($evtid, $dtstart, $idcal);

	$babBody->babPopup($details->getHtml().$attendees->getHtml());
}



function displayEventDetail($evtid, $dtstart, $idcal)
{
	global $babBody;


	$details = new displayEventDetailCls($evtid, $dtstart, $idcal);

	$babBody->babPopup($details->getHtml());
}



function displayEventDetailUpd($evtid, $dtstart, $idcal)
{
	global $babBody;


	$details = new displayEventDetailCls($evtid, $dtstart, $idcal);
	$notes = new displayEventNotesCls($evtid, $idcal);
	$alert = new displayEventAlertCls($evtid, $dtstart, $idcal);

	$babBody->babPopup($details->getHtml().$notes->getHtml().$alert->getHtml());
}


/**
 * Approbation page for one public or resource calendar link to an event (recurring or not)
 * @return unknown_type
 */
function approbCalendar($evtid, $dtstart, $idcal, $relation)
{
	require_once dirname(__FILE__).'/utilit/urlincl.php';
	if (isset($_POST['approbstatus']))
	{
		$relation = bab_pp('relation');
		$status = (int) bab_pp('approbstatus');

		confirmApprobEvent($evtid, $idcal, $relation, $status, bab_pp('comment'));

		$url = bab_url::get_request('tg');
		$url->idx = 'unload';
		$url->reload = '1';

		$url->location();
	}

	global $babBody;
	$details = new displayEventDetailCls($evtid, $dtstart, $idcal);
	$approb = new displayApprobCalendarCls($evtid, $idcal, $relation);

	$babBody->babPopup($details->getHtml().$approb->getHtml());
}



function categoriesList()
{
	global $babBody;
	class categoriesListCls
		{

		function categoriesListCls()
			{
			global $babBody, $babBody, $babDB;
			$this->res = $babDB->db_query("select * from ".BAB_CAL_CATEGORIES_TBL." ORDER BY name,description");
			$this->count = $babDB->db_num_rows($this->res);

			$this->t_name = bab_translate('Name');
			$this->t_description = bab_translate('Description');
			$this->t_close = bab_translate('Close');
			}

		function getnextcat()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $babDB->db_fetch_array($this->res);
				$this->name = bab_toHtml($arr['name']);
				$this->description = bab_toHtml($arr['description']);
				$this->bgcolor = bab_toHtml($arr['bgcolor']);
				$i++;
				return true;
				}
			else
				{
				$i = 0;
				return false;
				}

			}
		}

	$temp = new categoriesListCls();
	$babBody->babPopup(bab_printTemplate($temp, "calendar.html", "categorieslist"));
}

function eventlist($from, $to, $idcals)
{
include_once $GLOBALS['babInstallPath']."utilit/calincl.php";
include_once $GLOBALS['babInstallPath']."utilit/mcalincl.php";
include_once $GLOBALS['babInstallPath']."utilit/uiutil.php";

	class eventlist
		{
		function eventlist($from, $to, $idcals)
			{
			list($fyear,$fmonth,$fday) = explode(',', $from);
			list($tyear,$tmonth,$tday) = explode(',', $to);

			$idcals = explode(',',$idcals);

			$this->from = sprintf("%04s-%02s-%02s 00:00:00", $fyear, $fmonth, $fday);
			$this->to = sprintf("%04s-%02s-%02s 00:00:00", $tyear, $tmonth, $tday);

			$this->mcals = new bab_mcalendars($this->from, $this->to, $idcals);
			$this->mcals->loadCategories();

			$this->resevent = array();

			$this->t_print = bab_translate('Print');
			$this->t_private = bab_translate('Private');
			$this->t_from = bab_translate('Par');
			$this->t_category = bab_translate('Category');
			$this->t_show_hide = bab_translate('Show / hide finished events');
			$this->t_location = bab_translate('Location');
			$this->t_notetxt = bab_translate("Personal notes");

			$last_ts = 0;

			foreach ($idcals as $idcal)
				{
				//$this->mcals->getNextEvent return the event to the variable $calPeriod
				while ($this->mcals->getNextEvent($idcal, $this->from, $this->to, $calPeriod))
					{
					/* $calPeriod : object bab_calendarPeriod : see file workinghoursincl.php */
					$arr = $calPeriod->getData(); /* $calPeriod->data can be NULL (non working days) */
					$arr['color'] = $calPeriod->getProperty('X-CTO-COLOR');

					$xCtoPuid = $calPeriod->getProperty('UID');

					if (!isset($this->resevent[$xCtoPuid]))
						{
						$this->resevent[$xCtoPuid] = array();
						$this->resevent[$xCtoPuid]['cals'] = array();
						}

					$evt = & $this->resevent[$xCtoPuid];

					$evt['cals'] = $this->mcals->getEventCalendars($calPeriod);

					$evt['title'] = $calPeriod->getProperty('SUMMARY');
					$evt['description'] = $calPeriod->getProperty('DESCRIPTION');
					$ts = $calPeriod->ts_end;
					if ($ts <= time() && $last_ts < $ts)
						{
						$last_ts = $ts;
						$this->last_id = $xCtoPuid;
						}
					$evt['start_date'] = bab_toHtml(bab_longDate($calPeriod->ts_begin));
					$evt['end_date'] = bab_toHtml(bab_longDate($ts));
					$evt['categoryname'] = '';
					$evt['categorydescription'] = '';
					if (isset($arr['id_cat'])) {
						$evt['categoryname'] = !empty($this->mcals->categories[$arr['id_cat']]) ? bab_toHtml($this->mcals->categories[$arr['id_cat']]['name']) : '';
						$evt['categorydescription'] = !empty($this->mcals->categories[$arr['id_cat']]) ? bab_toHtml($this->mcals->categories[$arr['id_cat']]['description']) : '';
					}

					if (isset($arr['id_cat']) && !empty($this->mcals->categories[$arr['id_cat']])) {
						$evt['color'] = bab_toHtml($this->mcals->categories[$arr['id_cat']]['bgcolor']);
					} elseif (!empty($arr['color'])) {
						$evt['color'] = bab_toHtml($arr['color']);
					} else {
						$evt['color'] = 'fff';
					}

					$calendar = $calPeriod->getCollection()->getCalendar();

					$evt['creator'] = isset($arr['id_creator']) && $arr['id_creator'] != $GLOBALS['BAB_SESS_USERID'] ? bab_toHtml(bab_getUserName($arr['id_creator'])) : '';

					$evt['nbowners'] = isset($arr['nbowners']) ? $arr['nbowners']+1 : 1;
					$evt['t_option'] = '';
					$evt['properties'] = bab_toHtml(bab_getPropertiesString($calPeriod, $evt['t_option']));


					$evt['location']=bab_toHtml($calPeriod->getProperty('LOCATION'));
					global $babDB;
					$evt['notes'] = ''; /* Annotations personnelles */


					if ($calendar instanceOf bab_OviEventCalendar)
					{
						$evt['private'] = !$calendar->canViewEventDetails($calPeriod);

						if (isset($arr['id'])) {
							$res_note = $babDB->db_query("
								select note from
									".BAB_CAL_EVENTS_NOTES_TBL." n,
									".BAB_CAL_EVENTS_TBL." e
								where
									n.id_user='".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."'
									and n.id_event=e.id
									AND e.uuid = ".$babDB->quote($calPeriod->getProperty('UID'))."
							");

							if( $res_note && $babDB->db_num_rows($res_note) > 0 ) {
								$arr_notes = $babDB->db_fetch_array($res_note);
								$evt['notes'] = bab_toHtml($arr_notes['note'], BAB_HTML_ALL);
							}
						}
					}

					$sortvalue[$xCtoPuid] = $calPeriod->getProperty('DTSTART');
					}
				}

			if (isset($sortvalue))
				{

				bab_sort::asort($sortvalue);
				reset($sortvalue);

				while (list ($arr_key, $arr_val) = each ($sortvalue)) {
						 $new_array[$arr_key] = $this->resevent[$arr_key];
						}

				$this->resevent = $new_array;
				}

			}




		function getnextevent() {
			if (list($this->idevent,$this->evt) = each($this->resevent)) {
				return true;
			}

			return false;
		}

		function getnextcalendar() {
			if (list($this->id,$this->calendar) = each($this->evt['cals'])) {
				$this->calendar['type'] = bab_toHtml($this->calendar['type']);
				$this->calendar['name'] = bab_toHtml($this->calendar['name']);
				return true;
			}

			return false;
		}

		function printout()
			{
			$GLOBALS['babBodyPopup'] = new babBodyPopup();
			$GLOBALS['babBodyPopup']->title = bab_translate("Detailed sight");
			$GLOBALS['babBodyPopup']->babecho(bab_printTemplate($this, "calendar.html", "eventlist"));
			printBabBodyPopup();
			die();
			}
		}

	$temp = new eventlist($from, $to, $idcals);
	$temp->printout();

}



/**
 * Update event note only for event of the Ovidentia calendar backend
 * @param string $evtid
 * @param string $note
 * @param int $bupdrec
 * @return unknown_type
 */
function updateEventNotes($evtid, $note, $bupdrec)
{
	global $babDB;
	if( !empty($GLOBALS['BAB_SESS_USERID']) )
	{

		$evtidarr = array();

		list($id_event, $hash) = $babDB->db_fetch_row($babDB->db_query("select id, hash from ".BAB_CAL_EVENTS_TBL." where uuid='".$babDB->db_escape_string($evtid)."'"));

		if( $bupdrec == BAB_CAL_EVT_ALL &&  !empty($hash) )
		{
			$res = $babDB->db_query("select id from ".BAB_CAL_EVENTS_TBL." where hash='".$babDB->db_escape_string($hash)."'");
			while( $arr = $babDB->db_fetch_array($res))
				{
				$evtidarr[] = $arr['id'];
				}
		}

		if( count($evtidarr) == 0 )
			{
			$evtidarr[] = $id_event;
			}

		$updevtarr = array();

		$res = $babDB->db_query("select id_event from ".BAB_CAL_EVENTS_NOTES_TBL." where id_event in(".$babDB->quote($evtidarr).") and id_user='".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."'");
		while( $arr = $babDB->db_fetch_array($res))
		{
			$updevtarr[$arr['id_event']] = 1;
		}

		for( $i=0; $i < count($evtidarr); $i++ )
		{

		if( isset($updevtarr[$evtidarr[$i]] ) )
			{
			$babDB->db_query("update ".BAB_CAL_EVENTS_NOTES_TBL." set note='".$babDB->db_escape_string($note)."'  where id_event='".$babDB->db_escape_string($evtidarr[$i])."' and id_user='".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."'");
			}
		else
			{
			$babDB->db_query("insert into ".BAB_CAL_EVENTS_NOTES_TBL." ( id_event, id_user, note ) values ('".$babDB->db_escape_string($evtidarr[$i])."', '".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."', '".$babDB->db_escape_string($note)."')");
			}
		}
	}
}


/**
 * @deprecated Old function
 * @return unknown_type
 */
function updateEventAlert_OLD()
{

	$evtid = bab_rp('evtid');
	$bupdrec = bab_rp('bupdrec', 2);
	$creminder = bab_rp('creminder', 'N');
	$day = bab_rp('day');
	$hour = bab_rp('hour');
	$minute = bab_rp('minute');
	$remail = bab_rp('remail');

	global $babDB;
	if( !empty($GLOBALS['BAB_SESS_USERID']) )
	{
		if( $creminder == 'Y')
		{
			$res= $babDB->db_query("select id_event from ".BAB_CAL_EVENTS_REMINDERS_TBL." where id_event='".$babDB->db_escape_string($evtid)."' and id_user='".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."'");
			if( $res && $babDB->db_num_rows($res) > 0 )
			{
				$babDB->db_query("update ".BAB_CAL_EVENTS_REMINDERS_TBL." set day='".$babDB->db_escape_string($day)."', hour='".$babDB->db_escape_string($hour)."', minute='".$babDB->db_escape_string($minute)."', bemail='".$babDB->db_escape_string($remail)."', processed='N' where id_event='".$babDB->db_escape_string($evtid)."' and id_user='".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."'");
			}
			else
			{
				$babDB->db_query("insert into ".BAB_CAL_EVENTS_REMINDERS_TBL." (id_event, id_user, day, hour, minute, bemail) values ('".$babDB->db_escape_string($evtid)."', '".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."', '".$babDB->db_escape_string($day)."', '".$babDB->db_escape_string($hour)."', '".$babDB->db_escape_string($minute)."', '".$babDB->db_escape_string($remail)."')");
			}
		}
		else
		{
			$babDB->db_query("delete from ".BAB_CAL_EVENTS_REMINDERS_TBL." where id_event='".$babDB->db_escape_string($evtid)."' and id_user='".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."'");
		}
	}
}












/**
 * Save VALARM of event
 * @return unknown_type
 */
function updateEventAlert()
{
	$idcal = bab_rp('idcal');
	$evtid = bab_rp('evtid');
	$bupdrec = bab_rp('bupdrec', 2);
	$creminder = bab_rp('creminder', 'N');
	$day = (int) bab_rp('day');
	$hour = (int) bab_rp('hour');
	$minute = (int) bab_rp('minute');
	$remail = bab_rp('remail');


	if( empty($GLOBALS['BAB_SESS_USERID']) ||  $creminder != 'Y' )
	{
		return;
	}

	$calendar = bab_getICalendars()->getEventCalendar($idcal);

	if (!$calendar)
	{
		$babBody->addError(bab_translate("Access denied to the calendar"));
		return;
	}

	$backend = $calendar->getBackend();
	$calendarPeriod = $backend->getPeriod($backend->CalendarEventCollection($calendar), $evtid);

	if (!$calendarPeriod)
	{
		$babBody->addError(bab_translate("There is no additional informations for this event"));
		return;
	}

	$alarm = $calendarPeriod->getAlarm();
	if (!isset($alam))
	{
		$alarm = $backend->CalendarAlarm();

		$personal = bab_getICalendars()->getPersonalCalendar();

		if (!$personal)
		{
			throw new Exception('No personnal calendar');
		}

		$alarm->addAttendee($personal);
		$calendarPeriod->setAlarm($alarm);
	}

	bab_setAlarmProperties($alarm, $calendarPeriod, $day, $hour, $minute, 'Y' === $remail);



	// save event
	bab_debug('<h1>$backend->SavePeriod()</h1>'. $calendarPeriod->toHtml(), DBG_TRACE, 'CalendarBackend');
	$backend->savePeriod($calendarPeriod);
	$calendarPeriod->commitEvent();
}











function bab_gotoCalendarView() {

	global $babBody;

	if( bab_getICalendars()->calendarAccess()) {
		$babBody->calaccess = true;
		switch(bab_getICalendars()->defaultview)
			{
			case BAB_CAL_VIEW_DAY: $view='calday';	break;
			case BAB_CAL_VIEW_WEEK: $view='calweek'; break;
			default: $view='calmonth'; break;
		}

		header('location:'.$GLOBALS['babUrlScript']."?tg=".$view);
		exit;
	}
}









/* main */

$idx = bab_rp('idx');
if( isset($_REQUEST['conf']) )
{
	$conf = $_REQUEST['conf'];

	if( $conf == "event" )
		{
		confirmEvent(
			bab_rp('evtid'),
			bab_rp('dtstart'),
			bab_rp('idcal'),
			bab_rp('partstat'),
			bab_rp('comment'),
			bab_rp('bupdrec', BAB_CAL_EVT_CURRENT)
		);
		$reload = true;
		}
	elseif( $conf == "note" )
		{
		updateEventNotes(
			bab_rp('evtid'),
			bab_rp('note'),
			bab_rp('bupdrec', BAB_CAL_EVT_CURRENT)
		);
		$reload = true;
		}
	elseif( $conf == "alert" )
		{
		updateEventAlert();
		$reload = true;
		}
}

switch($idx)
	{
	case "unload":
		include_once $babInstallPath."utilit/uiutil.php";
		$popupmessage = bab_translate("Your event has been updated");
		if( !isset($reload)) { $reload = false; }
		$autoclose = !isset($_COOKIE['bab_debug']) || !isset($GLOBALS['bab_debug_messages']);
		popupUnload($popupmessage, '', $reload, $autoclose);
		exit;
		break;



	case "viewc":
		$babBody->setTitle(bab_translate("Categories"));
		categoriesList();
		break;


	case "vevent":

		$babBody->setTitle(bab_translate("Event Detail"));
		displayEventDetail(
			bab_rp('evtid'),
			bab_rp('dtstart'),
			bab_rp('idcal')
		);
		break;

	case "veventupd":

		$babBody->setTitle(bab_translate("Event Detail"));
		displayEventDetailUpd(
			bab_rp('evtid'),
			bab_rp('dtstart'),
			bab_rp('idcal')
		);
		break;

	case "attendees":

		$babBody->setTitle(bab_translate("Attendees"));
		displayAttendees(
			bab_rp('evtid'),
			bab_rp('dtstart'),
			bab_rp('idcal')
		);
		break;


	case 'approb':
		$babBody->setTitle(bab_translate("Approbation"));
		approbCalendar(
			bab_rp('evtid'),
			bab_rp('dtstart'),
			bab_rp('idcal'),
			bab_rp('relation')
		);
		break;


	case 'eventlist':
		eventlist($_GET['from'],$_GET['to'],$_GET['calid']);
		break;
	default:
		bab_gotoCalendarView();
		break;
	}
$babBody->setCurrentItemMenu($idx);


