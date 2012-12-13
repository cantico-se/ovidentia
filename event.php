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
require_once dirname(__FILE__).'/utilit/registerglobals.php';
include_once $babInstallPath.'utilit/calincl.php';
include_once $babInstallPath.'utilit/mcalincl.php';
include_once $babInstallPath.'utilit/uiutil.php';
include_once $babInstallPath.'utilit/evtincl.php';




class bab_cal_event
	{
	function bab_cal_event()

		{
		global $babBody, $babDB;

		$this->curdate = !empty($_REQUEST['date']) ? $_REQUEST['date'] : date('Y').",".date('m').",".date('d');

		list($this->curyear,$this->curmonth,$this->curday) = explode(',', $this->curdate);

		$this->curview = !empty($_REQUEST['view']) ? $_REQUEST['view'] : 'viewm';

		if (bab_pp('curcalids')) {
			$this->calid = bab_pp('curcalids');
		} else {
			$this->calid = bab_rp('calid');
		}

		$this->datebegintxt = bab_translate("Begin date");
		$this->dateendtxt = bab_translate("Until date");
		$this->private = bab_translate("Private");
		$this->type = bab_translate("Type");
		$this->daytype = bab_translate("All day");
		$this->addvac = bab_translate("Add Event");
		$this->starttime = bab_translate("starttime");
		$this->endtime = bab_translate("endtime");
		$this->daystext = bab_translate("Days");
		$this->or = bab_translate("Or");
		$this->everyday = bab_translate("Everyday");
		$this->title = bab_translate("Event title");
		$this->description = bab_translate("Description");
		$this->location = bab_translate("Event location");
		$this->category = bab_translate("Category");
		$this->usrcalendarstxt = bab_translate("Users calendars");
		$this->grpcalendarstxt = bab_translate("Groups calendars");
		$this->rescalendarstxt = bab_translate("Resources calendars");

		$this->t_repeat = bab_translate("Repeat");
		$this->t_norepeat = bab_translate("No repeat");
		$this->t_daily = bab_translate("Daily");
		$this->t_weekly = bab_translate("Weekly");
		$this->t_monthly = bab_translate("Monthly");
		$this->t_yearly = bab_translate("Yearly");
		$this->t_all_the = bab_translate("Every");
		$this->t_years = bab_translate("years");
		$this->t_months = bab_translate("months");
		$this->t_weeks = bab_translate("weeks");
		$this->t_days = bab_translate("days");

		$this->t_sun = mb_substr(bab_translate("Sunday"),0,3);
		$this->t_mon = mb_substr(bab_translate("Monday"),0,3);
		$this->t_tue = mb_substr(bab_translate("Tuesday"),0,3);
		$this->t_wen = mb_substr(bab_translate("Wednesday"),0,3);
		$this->t_thu = mb_substr(bab_translate("Thursday"),0,3);
		$this->t_fri = mb_substr(bab_translate("Friday"),0,3);
		$this->t_sat = mb_substr(bab_translate("Saturday"),0,3);

		$this->t_color = bab_translate("Color");
		$this->t_remove_color = bab_translate("Remove color");
		$this->t_bprivate = bab_translate("Private");
		$this->t_block = bab_translate("Lock");
		$this->t_bfree = bab_translate("Free");
		$this->t_yes = bab_translate("Yes");
		$this->t_no = bab_translate("No");
		$this->t_modify = bab_translate("Modify the event");
		$this->t_test_conflicts = bab_translate("Test conflicts");

		$this->repeat_dateendtxt = bab_translate("Periodicity end date");
		$this->t_remind_me = bab_translate("Remind me");
		$this->t_before_event = bab_translate("before the event");

		$this->ymin = 2;
		$this->ymax = 5;

		$this->icalendar = bab_getICalendars();
		$this->icalendar->initializeCalendars();



		$this->rescat = $babDB->db_query("SELECT * FROM ".BAB_CAL_CATEGORIES_TBL." ORDER BY name");
		}



	function urlDate($callback,$month,$year)
		{
		return bab_toHtml( $GLOBALS['babUrlScript']."?tg=month&callback=".$callback."&ymin=".$this->ymin."&ymax=".$this->ymax."&month=".$month."&year=".$year);
		}


	function getnextcat()
		{
		global $babDB;
		if ($this->cat = $babDB->db_fetch_array($this->rescat)) {
			$this->cat['name'] = bab_toHtml($this->cat['name']);
			$this->cat['bgcolor'] = bab_toHtml($this->cat['bgcolor']);
			$this->selected = isset($_POST['category']) && $_POST['category'] == $this->cat['id'] ? 'selected' : '';
			return true;
			}

		return false;
		}


	}


function newEvent()
{
	global $babBody;
	class temp extends bab_cal_event
	{
		var $arrresname = array();
		var $arrresid = array();

		function temp()
		{
			global $babBody, $babDB;

			$this->bab_cal_event();

			global $babBodyPopup;

			$this->t_event_owner = bab_translate("Event owner");

			$this->mcals = explode(",", $this->calid);
			$this->repeat = isset($GLOBALS['repeat'])? $GLOBALS['repeat']: 1;
			$this->repeat_cb_checked = isset($_POST['repeat_cb']) ? 'checked' : '';

			$this->datebeginurl = $this->urlDate('dateBegin',$this->curmonth,$this->curyear);
			$this->dateendurl = $this->urlDate('dateEnd',$this->curmonth,$this->curyear);
			$this->repeat_dateend = $this->urlDate('repeat_dateend',$this->curmonth,$this->curyear);
			$this->yearmin = $this->curyear - $this->ymin;



			if (isset($_REQUEST['date0']) && isset($_REQUEST['date1'])) {
				$date0 = (int) bab_rp('date0', time());
				$date1 = (int) bab_rp('date1', time());

			} else {

				$date = $this->curyear.'-'.$this->curmonth.'-'.$this->curday;

				$date0 = bab_mktime($date.' '.bab_getICalendars()->starttime);
				$endtime = bab_getICalendars()->endtime > bab_getICalendars()->starttime ? bab_getICalendars()->endtime : '23:00:00';
				$date1 = bab_mktime($date.' '.$endtime);
			}



			$this->yearbegin = date("Y", $date0);
			$this->monthbegin = date("m", $date0);
			$this->daybegin = date("d", $date0);

			$this->yearend = date("Y", $date1);
			$this->monthend = date("m", $date1);
			$this->dayend = date("d", $date1);

			$this->timebegin = date("H:i", $date0);
			$this->timeend = date("H:i", $date1);


			$this->repeat_yearend 	= !isset($_REQUEST['repeat_yearend']) 	? $this->curyear	: $_REQUEST['repeat_yearend'];
			$this->repeat_monthend 	= !isset($_REQUEST['repeat_monthend']) 	? $this->curmonth	: $_REQUEST['repeat_monthend'];
			$this->repeat_dayend 	= !isset($_REQUEST['repeat_dayend']) 	? $this->curday		: $_REQUEST['repeat_dayend'];


			$this->colorvalue = bab_rp('color');

			$this->usebgcolor = false;
			if (bab_getICalendars()->usebgcolor == 'Y') {
				$this->usebgcolor = true;
			}


			include_once $GLOBALS['babInstallPath']."utilit/editorincl.php";

			$editor = new bab_contentEditor('bab_calendar_event');
			$editor->setContent($editor->getContent());
			$editor->setParameters(array('height' => 150));
			$this->editor = $editor->getEditor();


			$this->daytypechecked = $this->icalendar->allday == 'Y' ? "checked"  :'';
			$this->elapstime = $this->icalendar->elapstime;
			$this->ampm = 'Y' == $GLOBALS['babBody']->ampm;
			$this->calendars = calendarchoice('vacform');
			$this->totaldays = date("t", mktime(0,0,0,$this->curmonth,$this->curday,$this->curyear));

			$this->daysel = !empty($_GET['st']) ? date('j',$_GET['st']) : $this->daybegin;
			$this->monthsel = !empty($_GET['st']) ? date('n',$_GET['st']) : $this->monthbegin;
			$this->yearsel = !empty($_GET['st']) ? date('Y',$_GET['st']) : $this->yearbegin;
			$this->timesel = !empty($_GET['st']) ? date('H:i',$_GET['st']) : $this->timebegin;

			$this->bprivate = true;
			$this->block = true;
			$this->bfree = true;

			$this->avariability_message = bab_translate("The event is in conflict with a calendar");

			$this->days = array(0, 1, 2, 3, 5, 6, 7, 8, 10, 11, 12);
			$this->hours = array(0, 1, 2, 3, 5, 6, 7, 8, 10, 11, 12);
			$this->minutes = array(0, 5, 10, 15, 30, 45);
			$this->alerttxt = bab_translate("Reminder");

			$this->groupe_notiftxt = bab_translate("Send the notification");

			if( isset($GLOBALS['babEmailReminder']) &&  $GLOBALS['babEmailReminder']){
				$this->remailtxt = bab_translate("Use email reminder");
			}else{
				$this->remailtxt = "";
			}

			$this->arr['repeat_n_1'] = '';
			$this->arr['repeat_n_2'] = '';
			$this->arr['repeat_n_3'] = '';
			$this->arr['repeat_n_4'] = '';

			if (isset($_POST) && count($_POST) > 0){
				foreach($_POST as $k => $v){
					$this->arr[$k] = bab_pp($k);
				}

				$this->arr['title'] = bab_toHtml($this->arr['title']);
				$this->arr['location'] = bab_toHtml($this->arr['location']);
				
				if(isset($this->arr['domain'])){
					foreach($this->arr['domain'] as $id => $domains){
						foreach($domains as $domain){
							$this->arr['domain'][$id][$domain] = $domain;
						}
					}
				}

				$this->daytypechecked = isset($this->arr['daytype']) ? 'checked' : '';
				$this->daysel = $this->arr['daybegin'];
				$this->monthsel = $this->arr['monthbegin'];
				$this->yearsel = $this->arr['yearbegin'];
				$this->timesel = isset($this->arr['timebegin']) ? $this->arr['timebegin'] : $this->timesel;
				$this->colorvalue = isset($this->arr['color']) ? $this->arr['color'] : '';


				$this->rcheckedval = isset($this->arr['creminder']) ? 'checked' : '';
				$this->rmcheckedval = isset($this->arr['remail']) ? 'checked' : '';
				$this->arralert['day'] = isset($this->arr['rday']) ? $this->arr['rday'] : '';
				$this->arralert['hour'] = isset($this->arr['rhour']) ? $this->arr['rhour'] : '';
				$this->arralert['minute'] = isset($this->arr['rminute']) ? $this->arr['rminute'] : '';
			}else{
				$this->arr['title'] = '';
				$this->arr['location'] = '';
				$this->arr['repeat_n_1'] = '';
				$this->arr['repeat_n_2'] = '';
				$this->arr['repeat_n_3'] = '';
				$this->arr['repeat_n_4'] = '';
				$this->rcheckedval = '';
				$this->rmcheckedval = '';
				$this->arralert['day'] = '';
				$this->arralert['hour'] = '';
				$this->arralert['minute'] = '';
				$this->arr['event_owner'] = 0;
				$this->arr['bprivate'] = 'N';
				$this->arr['block'] = 'N';
				$this->arr['bfree'] = 'N';
			}


			foreach (array('SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA') as $i){
				$this->repeat_wd_checked[$i] = isset($this->arr['repeat_wd']) && in_array($i,$this->arr['repeat_wd']) ? 'checked' : '';
			}

			$this->availability_msg_list = bab_event_posted::availabilityConflictsStore('MSG');
			$this->display_availability_message = 0 < count($this->availability_msg_list);
			$this->availability_mandatory = bab_event_posted::availabilityIsMandatory(bab_rp('selected_calendars', array()));

			$this->t_availability_mandatory = bab_translate("One of the selected calendars require availability to create this event");

			$registry = bab_getRegistryInstance();
			$registry->changeDirectory('/bab/calendar/');
			$this->notify = $registry->getValue('notify', true);
			

			$res = $babDB->db_query("select * FROM ".BAB_CAL_DOMAINS_TBL." ORDER BY `order` ASC, name ASC");
			$this->domaines = array(0 => array());
			$this->nbdomain = 0;
			while($res && $arr = $babDB->db_fetch_assoc($res)){
				$this->domaines[$arr['id_parent']][] = array('name' => $arr['name'], 'id' => $arr['id']);
			}
			foreach($this->domaines[0] as $domaine){
				if(isset($this->domaines[$domaine['id']]) && count($this->domaines[$domaine['id']]) > 0){
					$this->nbdomain++;
				}
			}
			
			$J = bab_jQuery();
			$babBody->addStyleSheet($J->getStyleSheetUrl());
			$this->selected_text = bab_translate('# selected');
			$this->select_text = bab_translate('Select options');
		}

		function getnextdomain()
		{
			while($dom = array_shift($this->domaines[0])){
				if(isset($this->domaines[$dom['id']]) && count($this->domaines[$dom['id']]) > 0){
					$this->currentDom = $dom['id'];
					$this->domainname = $dom['name'];
					return true;
				}
			}
			return false;
		}

		function getnextdomainvalue()
		{
			if($dom = array_shift($this->domaines[$this->currentDom])){
				$this->domvalueid = $dom['id'];
				$this->domvaluename = $dom['name'];
				$this->domvalueselected = '';
				if(isset($this->arr['domain']) && isset($this->arr['domain'][$this->currentDom]) && isset($this->arr['domain'][$this->currentDom][$dom['id']])){
					$this->domvalueselected = 'selected="selected"';
				}
				return true;
			}
			return false;
		}
		
		function getnextday()
			{
			static $i = 1, $k=0;
			if( $i <= $this->totaldays)
				{
				$this->dayid = $i;
				if( $this->daysel == $i)
					{
					$this->selected = "selected";
					}
				else
					$this->selected = "";

				$i++;
				return true;
				}
			else
				{
				$i = 1;
				if( $k == 0 )
					{
					$this->daysel =bab_pp('dayend', $this->dayend);
					$k++;
					}
				else
					{
					$this->daysel = $this->repeat_dayend;
					}
				return false;
				}
			}

		function getnextmonth()
			{
			static $i = 1, $k = 0;

			if( $i < 13)
				{
				$this->monthid = $i;
				$this->monthname = bab_toHtml(bab_DateStrings::getMonth($i));
				if( $this->monthsel == $i)
					{
					$this->selected = "selected";
					}
				else
					$this->selected = "";

				$i++;
				return true;
				}
			else
				{
				$i = 1;
				if( $k == 0 )
					{
					$this->monthsel = isset($this->arr['monthend']) ? $this->arr['monthend'] : $this->monthsel;
					$k++;
					}
				else
					{
					$this->monthsel = $this->repeat_monthend;
					}
				return false;
				}

			}
		function getnextyear()
			{
			static $i = 0, $k=0;
			if( $i < $this->ymin + $this->ymax + 1)
				{
				$this->yearidval = $this->yearmin + $i;
				$this->yearid = $this->yearidval;
				if( $this->yearsel == $this->yearidval)
					{
					$this->selected = "selected";
					}
				else
					$this->selected = "";
				$i++;
				return true;
				}
			else
				{
				$i = 0;
				if( $k == 0 )
					{
					$this->yearsel = isset($this->arr['yearend']) ? $this->arr['yearend'] : $this->yearsel;
					$k++;
					}
				else
					{
					$this->yearsel = $this->repeat_yearend;
					}
				return false;
				}
			}

		function getnexttime()
			{
			static $i = 0;

			if( $i < 1440/$this->elapstime)
				{
				$this->timeval = sprintf("%02d:%02d", ($i*$this->elapstime)/60, ($i*$this->elapstime)%60);
				if( $this->ampm )
					$this->time = bab_toAmPm($this->timeval);
				else
					$this->time = $this->timeval;
				if( $this->timesel == $this->timeval)
					{
					$this->selected = "selected";
					}
				else
					{
					$this->selected = "";
					}
				$i++;
				return true;
				}
			else
				{
				$i = 0;
				$this->timesel = isset($this->arr['timeend']) ? $this->arr['timeend'] : $this->timeend;
				return false;
				}
			}

		function getnextavariability()
			{
			return list(,$this->conflict) = each($this->availability_msg_list);
			}

		function getnextreminderday()
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

		function getnextreminderhour()
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

		function getnextreminderminute()
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

		}

	$temp = new temp();
	$babBody->addStyleSheet('calendar.css');
	$babBody->babecho(bab_printTemplate($temp,"event.html", "scripts"));
	$babBody->babecho(bab_printTemplate($temp,"jquery-ui-multiselect-widget.html", "script"));
	$babBody->babpopup(bab_printTemplate($temp,"event.html", "newevent"));
	}


function modifyEvent($idcal, $collection, $evtid, $dtstart, $cci, $view, $date)
	{
	global $babBody,$babDB;
	class temp
		{
		var $datebegin;
		var $dateend;
		var $vactype;
		var $addvac;

		var $daybegin;
		var $daybeginid;
		var $monthbegin;
		var $monthbeginid;

		var $yearbegin;

		var $res;
		var $count;

		var $arrresname = array();
		var $arrresid = array();
		var $evtid;
		var $bcategory;

		var $curday;
		var $curmonth;
		var $curyear;
		var $curview;
		var $descurl;
		var $delete;
		var $brecevt;
		var $updaterec;

		var $iRule;
		var $sRuleCaption;
		var $sRuleSelected;
		var $aRule = array();
		var $sCopyCaption;

		public $dtstart;

		function temp(bab_EventCalendar $calendar, $cci, $view, $date, bab_CalendarPeriod $event)
		{
			global $babBody, $babDB, $BAB_SESS_USERID, $babBodyPopup;

			$this->delete = bab_translate("Delete");
			$this->t_color = bab_translate("Color");
			$this->t_remove_color = bab_translate("Remove color");
			$this->t_bprivate = bab_translate("Private");
			$this->t_block = bab_translate("Lock");
			$this->t_bfree = bab_translate("Free");
			$this->t_yes = bab_translate("Yes");
			$this->t_no = bab_translate("No");
			$this->t_modify = bab_translate("Modify the event");
			$this->t_test_conflicts = bab_translate("Test conflicts");
			$this->t_event_owner = bab_translate("Event owner");
			$this->calid = $calendar->getUrlIdentifier();
			$this->evtid = $event->getProperty('UID');
			$this->ccids = $cci;
			$this->curview = $view;
			$this->curdate = $date;
			$this->dtstart = $event->getProperty('DTSTART');

			$this->groupe_notiftxt = bab_translate("Send the notification");

			$this->bupdrec = bab_rp('bupdrec', 2);

			$this->sCopyCaption = bab_translate("Copy event");

			$this->aRule = array(
				BAB_CAL_EVT_ALL 		=> bab_translate("All"),
				BAB_CAL_EVT_CURRENT 	=> bab_translate("This occurence"),
				BAB_CAL_EVT_PREVIOUS 	=> bab_translate("This occurence and all previous occurences"),
				BAB_CAL_EVT_NEXT 		=> bab_translate("This occurence and all next occurences")
			);


			$selected_calendars = array();

			foreach($event->getCalendars() as $calendar)
			{
				$selected_calendars[] = $calendar->getUrlIdentifier();
			}

			$this->calendars = calendarchoice('vacform', $selected_calendars, $event);

			$data = $event->getData();

			$cat = bab_getCalendarCategory($event->getProperty('CATEGORIES'));

			$this->evtarr = array(

				'title' 				=> $event->getProperty('SUMMARY'),
				'description' 			=> isset($data['description']) ? $data['description'] : $event->getProperty('DESCRIPTION'),
				'description_format' 	=> isset($data['description_format']) ? $data['description_format'] : 'text',
				'location' 				=> $event->getProperty('LOCATION'),
				'start_date' 			=> date('Y-m-d H:i:s', $event->ts_begin),
				'end_date' 				=> date('Y-m-d H:i:s', $event->ts_end),
				'id_cat' 				=> $cat['id'],
				'color' 				=> $event->getColor(),
				'bprivate' 				=> $event->isPublic() ? 'N' : 'Y',
				'block' 				=> isset($data['block']) ? $data['block'] : '',
				'bfree' 				=> 'TRANSPARENT' === $event->getProperty('TRANSP') ? 'Y' : 'N'
			);

			$this->bdelete = $calendar->canDeleteEvent($event);


			$collection = $event->getCollection();

			if (!empty($collection->hash) || $event->getProperty('RRULE')) {
				$this->brecevt = true;
				$this->updaterec = bab_translate("This is recurring event. Do you want to update this occurence or series?");
			} else {
				$this->brecevt = false;
			}



			$this->bshowupadetinfo = false;
			if (bab_getICalendars()->show_update_info == 'Y') {
				$this->bshowupadetinfo = true;
				$this->modifiedontxt = bab_translate("Created/Updated on");
				$this->bytxt = bab_translate("By");
				if ($event->getProperty('LAST-MODIFIED') !== '') {
					$this->updatedate = bab_toHtml(bab_shortDate(BAB_DateTime::fromICal($event->getProperty('LAST-MODIFIED'))->getTimeStamp(), true));
				} else {
					$this->updatedate = '';
				}

				$this->updateauthor = false;
				if (isset($data['id_modifiedby']))
				{
					$this->updateauthor = bab_toHtml(bab_getUserName($data['id_modifiedby']));
				}
			}


			$this->usebgcolor = false;
			if (bab_getICalendars()->usebgcolor == 'Y') {
				$this->usebgcolor = true;
			}



			$this->ymin = 2;
			$this->ymax = 5;
			if (isset($_POST) && count($_POST) > 0) {
				foreach($_POST as $k => $v) {
					$this->evtarr[$k] = bab_pp($k);
				}
				$this->evtarr['id_cat'] = bab_pp('category');
			}

			if (isset($this->evtarr['yearbegin'])) {
				$this->yearbegin = $this->evtarr['yearbegin'];
			} else {
				$this->yearbegin = mb_substr($this->evtarr['start_date'], 0,4 );
			}

			if (isset($this->evtarr['daybegin'])) {
				$this->daybegin = $this->evtarr['daybegin'];
			} else {
				$this->daybegin = mb_substr($this->evtarr['start_date'], 8, 2);
			}

			if (isset($this->evtarr['monthbegin'])) {
				$this->monthbegin = $this->evtarr['monthbegin'];
			} else {
				$this->monthbegin = mb_substr($this->evtarr['start_date'], 5, 2);
			}

			if (isset($this->evtarr['yearend'])) {
				$this->yearend = $this->evtarr['yearend'];
			} else {
				$this->yearend = mb_substr($this->evtarr['end_date'], 0,4 );
			}

			if (isset($this->evtarr['dayend'])) {
				$this->dayend = $this->evtarr['dayend'];
			} else {
				$this->dayend = mb_substr($this->evtarr['end_date'], 8, 2);
			}

			if (isset($this->evtarr['monthend'])) {
				$this->monthend = $this->evtarr['monthend'];
			} else {
				$this->monthend = mb_substr($this->evtarr['end_date'], 5, 2);
			}

			if (isset($this->evtarr['timebegin'])) {
				$this->timebegin = $this->evtarr['timebegin'];
			} else {
				$this->timebegin = mb_substr($this->evtarr['start_date'], 11, 5);
			}

			if (isset($this->evtarr['timeend'])) {
				$this->timeend = $this->evtarr['timeend'];
			} else {
				$this->timeend = mb_substr($this->evtarr['end_date'], 11, 5);
			}


			$tmp = explode(':',$this->timebegin);
			$this->minbegin = $tmp[0]*60+$tmp[1];

			$tmp = explode(':',$this->timeend);
			$this->minend = $tmp[0]*60+$tmp[1];

			$this->evtarr['title'] = bab_toHtml($this->evtarr['title']);
			$this->evtarr['location'] = bab_toHtml($this->evtarr['location']);

			$this->yearmin = $this->yearbegin - $this->ymin;

			$this->nbdaysbegin = date("t", mktime(0,0,0, $this->monthbegin, $this->daybegin,$this->yearbegin));
			$this->nbdaysend = date("t", mktime(0,0,0, $this->monthend, $this->dayend,$this->yearend));

			$this->datebegin = bab_toHtml( $GLOBALS['babUrlScript']."?tg=month&callback=dateBegin&ymin=".$this->ymin."&ymax=".$this->ymax."&month=".$this->monthbegin."&year=".$this->yearbegin);
			$this->datebegintxt = bab_translate("Begin date");
			$this->dateend = bab_toHtml( $GLOBALS['babUrlScript']."?tg=month&callback=dateEnd&ymin=".$this->ymin."&ymax=".$this->ymax."&month=".$this->monthend."&year=".$this->yearend);
			$this->dateendtxt = bab_translate("End date");
			$this->modify = bab_translate("Update Event");
			$this->starttime = bab_translate("starttime");
			$this->endtime = bab_translate("endtime");
			$this->title = bab_translate("Event title");
			$this->description = bab_translate("Description");
			$this->location = bab_translate("Event location");
			$this->category = bab_translate("Category");
			$this->descurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=event&idx=updesc&calid=".$this->calid."&evtid=".$this->evtid);


			include_once $GLOBALS['babInstallPath']."utilit/editorincl.php";

			$editor = new bab_contentEditor('bab_calendar_event');


			$tmp = $editor->getContent();
			if ($tmp == '') {
				$editor->setContent($this->evtarr['description']);
				$editor->setFormat($this->evtarr['description_format']);
			}


			$editor->setParameters(array('height' => 150));
			$this->editor = $editor->getEditor();

			$this->elapstime = bab_getICalendars()->elapstime;
			$this->ampm = $babBody->ampm;
			$this->colorvalue = isset($_POST['color']) ? $_POST['color'] : $this->evtarr['color'] ;
			$this->avariability_message = bab_translate("The event is in conflict with a calendar");

			$this->rescat = $babDB->db_query("select * from ".BAB_CAL_CATEGORIES_TBL." ORDER BY name");
			$this->rescount = $babDB->db_num_rows($this->rescat);

			$this->availability_msg_list = bab_event_posted::availabilityConflictsStore('MSG');
			$this->display_availability_message = 0 < count($this->availability_msg_list);

			$this->availability_mandatory = bab_event_posted::availabilityIsMandatory(bab_rp('selected_calendars', array()));

			$this->t_availability_mandatory = bab_translate("One of the selected calendars require availability to modify this event");

			$registry = bab_getRegistryInstance();
			$registry->changeDirectory('/bab/calendar/');
			
			$this->selectedDomain = array();
			if($XDOMAIN = $event->getProperty('X-CTO-DOMAIN')){
				$selectedDomain = explode(',', $XDOMAIN);
				foreach($selectedDomain as $val){
					$this->selectedDomain[$val] = $val;
				}
			}
			$postDomains = bab_rp('domain');
			if($postDomains && is_array($postDomains)){
				$this->selectedDomain = array();
				foreach($postDomains as $postDomain){
					foreach($postDomain as $val){
						$this->selectedDomain[$val] = $val;
					}
				}
			}
			
			$this->notify = $registry->getValue('notify', true);
			$res = $babDB->db_query("select * FROM ".BAB_CAL_DOMAINS_TBL." ORDER BY `order` ASC, name ASC");
			$this->domaines = array(0 => array());
			$this->nbdomain = 0;
			while($res && $arr = $babDB->db_fetch_assoc($res)){
				$this->domaines[$arr['id_parent']][] = array('name' => $arr['name'], 'id' => $arr['id']);
			}
			foreach($this->domaines[0] as $domaine){
				if(isset($this->domaines[$domaine['id']]) && count($this->domaines[$domaine['id']]) > 0){
					$this->nbdomain++;
				}
			}
			
			$J = bab_jQuery();
			$babBody->addStyleSheet($J->getStyleSheetUrl());
			$this->selected_text = bab_translate('# selected');
			$this->select_text = bab_translate('Select options');
		}

		function getnextdomain()
		{
			while($dom = array_shift($this->domaines[0])){
				if(isset($this->domaines[$dom['id']]) && count($this->domaines[$dom['id']]) > 0){
					$this->currentDom = $dom['id'];
					$this->domainname = $dom['name'];
					return true;
				}
			}
			return false;
		}

		function getnextdomainvalue()
		{
			if($dom = array_shift($this->domaines[$this->currentDom])){
				$this->domvalueid = $dom['id'];
				$this->domvaluename = $dom['name'];
				$this->domvalueselected = '';
				if(isset($this->selectedDomain[$dom['id']])){
					$this->domvalueselected = 'selected="selected"';
				}
				return true;
			}
			return false;
		}

		function getNextRule()
		{
			$this->iRule			= '';
			$this->sRuleCaption		= '';
			$this->sRuleSelected	= '';

			$aDatas = each($this->aRule);
			if(false !== $aDatas)
			{
				$this->iRule		= bab_toHtml($aDatas['key']);
				$this->sRuleCaption	= bab_toHtml($aDatas['value']);

				if((int) $this->bupdrec === (int) $aDatas['key'])
				{
					$this->sRuleSelected = 'selected="selected"';
				}

				return true;
			}
			return false;
		}

		function getnextcat()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->rescount)
				{
				$arr = $babDB->db_fetch_array($this->rescat);
				$this->catid = $arr['id'];
				$this->catname = bab_toHtml($arr['name']);
				$this->bgcolor = bab_toHtml($arr['bgcolor']);
				if( $this->evtarr['id_cat'] == $this->catid )
					{
					$this->selected = "selected";
					}
				else
					{
					$this->selected = "";
					}
				$i++;
				return true;
				}
			else
				{
				return false;
				}
			}

		function getnextday()
			{
			static $i = 1;
			static $tr = 0;
			if( $tr == 0)
				$nbdays = $this->nbdaysbegin;
			else
				$nbdays = $this->nbdaysend;
			if( $i <= $nbdays)
				{
				$this->dayid = $i;
				if( $tr == 0 && $this->daybegin == $i)
					{
					$this->selected = "selected";
					}
				else if( $tr == 1 && $this->dayend == $i)
					{
					$this->selected = "selected";
					}
				else
					$this->selected = "";

				$i++;
				return true;
				}
			else
				{
				$i = 1;
				$tr = 1;
				return false;
				}

			}

		function getnextmonth()
			{
			static $i = 1;
			static $tr = 0;

			if( $i < 13)
				{
				$this->monthid = $i;
				$this->monthname = bab_toHtml(bab_DateStrings::getMonth($i));
				if( $tr == 0 && $this->monthbegin == $i)
					{
					$this->selected = "selected";
					}
				else if( $tr == 1 && $this->monthend == $i)
					{
					$this->selected = "selected";
					}
				else
					$this->selected = "";

				$i++;
				return true;
				}
			else
				{
				$tr = 1;
				$i = 1;
				return false;
				}

			}
		function getnextyear()
			{
			static $i = 0;
			static $tr = 0;
			if( $i < $this->ymin + $this->ymax + 1)
				{
				//$this->yearid = $i+1;
				$this->yearidval = $this->yearmin + $i;
				$this->yearid = $this->yearidval;
				if( $tr == 0 && $this->yearbegin == $this->yearidval)
					{
					$this->selected = "selected";
					}
				else if( $tr == 1 && $this->yearend == $this->yearidval)
					{
					$this->selected = "selected";
					}
				else
					$this->selected = "";
				$i++;
				return true;
				}
			else
				{
				$i = 0;
				$tr = 1;
				return false;
				}

			}

		function getnexttime()
			{

			static $i = 0;
			static $tr = 0;

			if( $i < 1440/$this->elapstime)
				{
				$min = $i*$this->elapstime;
				$this->timeval = sprintf("%02d:%02d", $min/60, $min%60);

				$previous = $min - ($this->elapstime/2);
				$next = $min + ($this->elapstime/2);

				if( $this->ampm )
					$this->time = bab_toAmPm($this->timeval);
				else
					$this->time = $this->timeval;

				if( $tr == 0 &&  $next >= $this->minbegin && $this->minbegin > $previous)
					$this->selected = "selected";
				else if( $tr == 1 &&  $next > $this->minend && $this->minend >= $previous)
					$this->selected = "selected";
				else
					$this->selected = "";

				$i++;
				return true;
				}
			else
				{
				$i = 0;
				$tr = 1;
				return false;
				}

			}

		function getnextavariability()
			{
			return list(,$this->conflict) = each($this->availability_msg_list);
			}

		}


	$calendar = bab_getICalendars()->getEventCalendar($idcal);
	if (!isset($calendar))
	{
		throw new Exception('Access denied to calendar '.$idcal);
	}




	$backend = $calendar->getBackend();

	$collection = $backend->CalendarEventCollection($calendar);

	$event = $backend->getPeriod($collection, $evtid, $dtstart);

	if (!($event instanceof bab_CalendarPeriod))
	{
		bab_debug('Error, the event '.$evtid.' cannot be reached with the backend '.get_class($backend));
		$babBody->addError(bab_translate('The requested event could not be found or the calendar is not accessible'));
		$babBody->babpopup('');
	}


	if (!$calendar->canUpdateEvent($event))
	{
		$babBody->addError(bab_translate('Access denied to event modification'));
		$babBody->babpopup('');
	}


	$temp = new temp($calendar, $cci, $view, $date, $event);
	$babBody->addStyleSheet('calendar.css');
	$babBody->babecho(bab_printTemplate($temp,"event.html", "scripts"));
	$babBody->babecho(bab_printTemplate($temp,"jquery-ui-multiselect-widget.html", "script"));
	$babBody->babpopup(bab_printTemplate($temp,"event.html", "modifyevent"));
	}

function deleteEvent()
	{
	global $babBody,$babBodyPopup;

	class deleteEventCls extends bab_cal_event
		{
		var $warning;
		var $message;
		var $title;
		var $urlyes;
		var $urlno;
		var $yes;
		var $no;
		var $topics;
		var $article;

		function deleteEventCls()
			{
			$iReccurenceRule = (int) isset($_POST['bupdrec']) ? $_POST['bupdrec'] : 2;

			switch($iReccurenceRule)
			{
				case 0://Default value for not recurring event.
					$this->message = bab_translate("Are you sure you want to delete this event");
					$this->warning = bab_translate("WARNING: This operation will delete event permanently"). "!";
					$iReccurenceRule = 1;
					break;

				case 1://All
					$this->message = bab_translate("This is a reccuring event.Are you sure you want to delete this event and all occurrences");
					$this->warning = bab_translate("WARNING: This operation will delete all occurrences permanently"). "!";
					break;

				case 3://This event and previous
					$this->message = bab_translate("This is a reccuring event.Are you sure you want to delete this event and all the previous");
					$this->warning = bab_translate("WARNING: This operation will delete event permanently"). "!";
					break;

				case 4://This event and next
					$this->message = bab_translate("This is a reccuring event.Are you sure you want to delete this event and all the next");
					$this->warning = bab_translate("WARNING: This operation will delete event permanently"). "!";
					break;

				default:
				case 2: //This event
					$this->message = bab_translate("Are you sure you want to delete this event");
					$this->warning = bab_translate("WARNING: This operation will delete event permanently"). "!";
					break;
			}

			$calendar = bab_getIcalendars()->getEventCalendar($_POST['calid']);
			$backend = $calendar->getBackend();
			$period = $backend->getPeriod($backend->CalendarEventCollection($calendar), bab_pp('evtid'), bab_pp('dtstart', null));

			$this->title = $period->getProperty('SUMMARY');
			$this->urlyes = bab_toHtml( $GLOBALS['babUrlScript']."?tg=event&date=".$_POST['date']."&calid=".$_POST['calid']."&evtid=".bab_pp('evtid')."&dtstart=".bab_pp('dtstart')."&action=yes&view=".$_POST['view']."&bupdrec=".$iReccurenceRule."&curcalids=".$_POST['curcalids'].'&notify='.bab_pp('groupe-notif'));
			$this->yes = bab_translate("Yes");
			$this->urlno = bab_toHtml($GLOBALS['babUrlScript']."?tg=event&idx=unload&action=no&calid=".$_POST['calid']."&view=");
			$this->no = bab_translate("No");
			}
		}

	$temp = new deleteEventCls();
	$babBodyPopup->babecho(	bab_printTemplate($temp,"warning.html", "warningyesno"));
	}





/**
 * Create a new event (save the posted form)
 * @param string &$message
 * @return bool
 */
function addEvent(&$message)
	{
	global $babBody;

	$posted = new bab_event_posted();
	$posted->createArgsData();

	if (!$posted->isValid($message))
	{

		return false;
	}

	// if period is available
	if ($posted->availabilityCheckAllEvents($message)) {
		return $posted->save($message);
	}


	// if availability message displayed and the event is submited
	if (isset($_POST['availability_displayed']) && !isset($_POST['test_conflicts'])) {

		// if availability is NOT mandatory
		if (!bab_event_posted::availabilityIsMandatory($posted->args['selected_calendars'])) {
			return $posted->save($message);
		}
	}

	return false;
}


/**
 * Update an event (save the posted form)
 * @param string &$message
 * @return bool
 */
function updateEvent(&$message)
{
	$posted = new bab_event_posted();
	$posted->createArgsData();
	if (!$posted->isValid($message))
	{
		return false;
	}

	// if period is available
	if ($posted->availabilityCheckAllEvents($message)) {
		return $posted->save($message);
	}


	// if availability message displayed and the event is submited
	if (isset($_POST['availability_displayed']) && !isset($_POST['test_conflicts'])) {

		// if availability is NOT mandatory
		if (!bab_event_posted::availabilityIsMandatory($posted->args['selected_calendars'])) {
			return $posted->save($message);
		}
	}

	return false;
}



/**
 * Delete event
 * @param 	int		$calid
 * @param 	string	$bupdrec
 * @param	int		$notify
 * @return unknown_type
 */
function confirmDeleteEvent($calid, $bupdrec, $notify)
{
	$evtid = bab_rp('evtid');
	$dtstart = bab_rp('dtstart');
	$calendar = bab_getICalendars()->getEventCalendar($calid);

	if (!isset($calendar))
	{
		throw new Exception('Missing calendar');
	}

	$backend = $calendar->getBackend();
	/*@var $backend Func_CalendarBackend */

	$calendarPeriod = $backend->getPeriod($backend->CalendarEventCollection($calendar), $evtid, $dtstart);

	if (!isset($calendarPeriod))
	{
		throw new Exception('Event not found');
	}
	
	
	$calendarPeriod->setProperty('STATUS', 'CANCELLED');

	$collection = $calendarPeriod->getCollection();
	bab_addHashEventsToCollection($collection, $calendarPeriod, $bupdrec);



	$date_min = $calendarPeriod->ts_begin;
	$date_max = $calendarPeriod->ts_end;


	
	foreach ($collection as $period) {
		if ($period->ts_begin < $date_min) 	{ $date_min = $period->ts_begin; 	}
		if ($period->ts_end > $date_max) 	{ $date_max = $period->ts_end; 		}
	}


	bab_debug('<h1>$backend->SavePeriod()</h1>'. $calendarPeriod->toHtml(), DBG_TRACE, 'CalendarBackend');
	
	try {

		$calendarPeriod->cancelFromAllCalendars();
		
	} catch(ErrorException $e)
	{
		// get backend specific errors
		bab_debug($e->getMessage());
		return false;
	}
	

	include_once $GLOBALS['babInstallPath'].'utilit/eventperiod.php';

	if ($notify)
	{
		$notifyEvent = new bab_eventAfterEventDelete;
		$notifyEvent->setPeriod($calendarPeriod);


		foreach($calendarPeriod->getCalendars() as $calendar) {
			$notifyEvent->addCalendar($calendar);
		}

		bab_fireEvent($notifyEvent);
	}

	$event = new bab_eventPeriodModified($date_min, $date_max, false);
	$event->types = BAB_PERIOD_CALEVENT;
	bab_fireEvent($event);
}


function calendarquerystring()
	{
	$qs = '&calid='.$_REQUEST['calid'];
	$qs .= '&curday='.$_REQUEST['curday'];
	$qs .= '&curmonth='.$_REQUEST['curmonth'];
	$qs .= '&curyear='.$_REQUEST['curyear'];

	return $qs;
	}


function eventAvariabilityCheck()
	{
	global $babDB, $babBody;


	if (isset($_POST['bfree']) && $_POST['bfree'] == 'Y' ) {
		// event is free, allways available
		return true;
	}


	$calid = explode(',',$GLOBALS['calid']);
	$bfree = isset($_POST['bfree']) ? $_POST['bfree'] : 'N';



	if( isset($_POST['monthbegin'])) {

		// one event or multiple event in creation


		$timebegin = isset($_POST['timebegin']) ? $_POST['timebegin'] : bab_getICalendars()->starttime;
		$timeend = isset($_POST['timeend']) ? $_POST['timeend'] : bab_getICalendars()->endtime;

		$tb = explode(':',$timebegin);
		$te = explode(':',$timeend);

		$begin = mktime( $tb[0],$tb[1],0,$_POST['monthbegin'], $_POST['daybegin'], $_POST['yearbegin'] );
		$end = mktime( $te[0],$te[1],0,$_POST['monthend'], $_POST['dayend'], $_POST['yearend'] );

		$available_status = bab_event_posted::availabilityCheck($calid, $begin, $end, bab_pp('evtid', false));

	}
	else {
		// multiple events in modification

		$evtid = bab_pp('evtid', false);
		if (empty($evtid)) {
			trigger_error('Unexpected error, missing evtid');
			return false;
		}

		$available_status = true;

		$res = $babDB->db_query('SELECT hash FROM '.BAB_CAL_EVENTS_TBL.' WHERE id='.$babDB->quote($evtid));
		if ($arr = $babDB->db_fetch_assoc($res)) {
			$res = $babDB->db_query('SELECT id, start_date, end_date FROM '.BAB_CAL_EVENTS_TBL.' WHERE hash='.$babDB->quote($arr['hash']));
			while ($arr = $babDB->db_fetch_assoc($res)) {
				$evtid 	= $arr['id'];
				$begin 	= bab_mktime($arr['start_date']);
				$end 	= bab_mktime($arr['end_date']);

				if (false === bab_event_posted::availabilityCheck($calid, $begin, $end, $evtid)) {
					$available_status = false;
				}
			}
		}
	}

	// if period unavailable and availability already displayed and no mandatory calendars on conflicts
	if (
		false === $available_status
		&& isset($_POST['availability_displayed'])
		&& !isset($_POST['test_conflicts'])
		&& !bab_event_posted::availabilityIsMandatory($calid)
		) {

		$available_status = true;
	}

	return $available_status;
}










/* main */
$idx = bab_rp('idx','newevent');
if(is_array($idx))
{
	$idx = each($idx);
	if(false !== $idx)
	{
		$idx = $idx['key'];
	}
}



if (!bab_rp('calid') && isset($_POST['selected_calendars'])) {
	// creation of a new event
	$calendar = bab_getMainCalendar($_POST['selected_calendars']);
	if ($calendar)
	{
		$calid = $calendar->getUrlIdentifier();
	}
} else {
	$calid = bab_rp('calid');
}
$calid = bab_isCalendarAccessValid($calid);

if( !$calid )
	{
	echo bab_translate("Access denied to calendar").' '.$calid;
	exit;
	}



if (isset($_REQUEST['action']))
	{
	switch($_REQUEST['action'])
		{
		case 'yes':
			confirmDeleteEvent($calid, bab_rp('bupdrec'), bab_rp('notify', 1));
			$idx="unload";
			break;

		case 'addevent':
			$message = '';
			if (addEvent($message))
				{
				$idx = "unload";
				}
			else
				{
				$idx = 'newevent';
				}
			break;

		case 'modifyevent':
			if( isset($_POST['Submit']) || isset($_POST['test_conflicts']))
				{
				$message = '';
				if (updateEvent($message))
					{
					$idx = "unload";
					}
				else
					{
					$idx = "modevent";
					$cci = $_POST['curcalids'];
					}
				}
			elseif(isset($_POST['evtdel']))
				{
				$message = '';
				$babBodyPopup = new babBodyPopup();
				$babBodyPopup->msgerror = $message;
				deleteEvent();
				printBabBodyPopup();
				exit;
				}

			break;
		}
	}



switch($idx)
	{
	case "unload":
		include_once $babInstallPath."utilit/uiutil.php";
		if( !isset($popupmessage)) {
			$popupmessage = bab_translate("Your event has been updated");
		}
		switch($view)
		{
			case 'viewd':
				$refreshurl = $GLOBALS['babUrlScript']."?tg=calday&calid=".bab_rp('curcalids')."&date=".bab_rp('date');
				break;
			case 'viewq':
				$refreshurl = $GLOBALS['babUrlScript']."?tg=calweek&calid=".bab_rp('curcalids')."&date=".bab_rp('date');
				break;
			case 'viewm':
				$refreshurl = $GLOBALS['babUrlScript']."?tg=calmonth&calid=".bab_rp('curcalids')."&date=".bab_rp('date');
				break;
			default:
				$popupmessage = "";
				$refreshurl = "";
				break;
		}

		$autoclose = !isset($_COOKIE['bab_debug']) || !isset($GLOBALS['bab_debug_messages']);

		popupUnload($popupmessage, $refreshurl, false, $autoclose);
		break;

	case "modevent":
		if( !isset($message)) { $message = '';}
		$babBody->msgerror = $message;
		modifyEvent($calid, bab_rp('collection'), bab_rp('evtid'), bab_rp('dtstart'), bab_rp('cci'), bab_rp('view'), bab_rp('date'));
		exit;
		break;

	case "newevent":
		if( !isset($message)) { $message = '';}
		$babBody->title = bab_translate("New calendar event");
		$babBody->msgerror = $message;
		newEvent();
		break;

	default:
		break;
	}
