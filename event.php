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
include_once $babInstallPath.'utilit/mcalincl.php';
include_once $babInstallPath.'utilit/uiutil.php';
include_once $babInstallPath.'utilit/evtincl.php';

function bab_getCalendarEventTitle($evtid)
{
	global $babDB;
	$query = "select title from ".BAB_CAL_EVENTS_TBL." where id='".$babDB->db_escape_string($evtid)."'";
	$res = $babDB->db_query($query);
	if( $res && $babDB->db_num_rows($res) > 0)
		{
		$arr = $babDB->db_fetch_array($res);
		return $arr['title'];
		}
	else
		{
		return "";
		}
}


class bab_cal_event
	{
	function bab_cal_event()

		{
		global $babBody, $babDB;

		$this->curdate = !empty($_REQUEST['date']) ? $_REQUEST['date'] : date('Y').",".date('m').",".date('d');

		list($this->curyear,$this->curmonth,$this->curday) = explode(',', $this->curdate);

		$this->curview = !empty($_REQUEST['view']) ? $_REQUEST['view'] : 'viewm';
		$this->calid = isset($_REQUEST['calid']) ? $_REQUEST['calid']  : $_POST['curcalids'];

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
		$this->title = bab_translate("Title");
		$this->description = bab_translate("Description");
		$this->location = bab_translate("Location");
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

		$this->t_sun = substr(bab_translate("Sunday"),0,3);
		$this->t_mon = substr(bab_translate("Monday"),0,3);
		$this->t_tue = substr(bab_translate("Tuesday"),0,3);
		$this->t_wen = substr(bab_translate("Wednesday"),0,3);
		$this->t_thu = substr(bab_translate("Thursday"),0,3);
		$this->t_fri = substr(bab_translate("Friday"),0,3);
		$this->t_sat = substr(bab_translate("Saturday"),0,3);

		$this->t_color = bab_translate("Color");
		$this->t_bprivate = bab_translate("Private");
		$this->t_block = bab_translate("Lock");
		$this->t_bfree = bab_translate("Free");
		$this->t_yes = bab_translate("Yes");
		$this->t_no = bab_translate("No");
		$this->t_modify = bab_translate("Modify the event");
		$this->t_test_conflicts = bab_translate("Test conflicts");

		$this->repeat_dateendtxt = bab_translate("Periodicity end date");

		$this->ymin = 2;
		$this->ymax = 5;

		$this->icalendar = $babBody->icalendars;
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
			$this->selected = isset($_POST['category']) && $_POST['category'] == $this->cat['id'] ? 'selected' : '';
			return true;
			} 
			
		return false;
		}

	
	}


function newEvent()
	{
	global $babBodyPopup;
	class temp extends bab_cal_event
		{
		var $arrresname = array();
		var $arrresid = array();

		function temp()
			{
			global $babBody;

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
			
				$date0 = bab_mktime($date.' '.$babBody->icalendars->starttime);
				$endtime = $babBody->icalendars->endtime > $babBody->icalendars->starttime ? $babBody->icalendars->endtime : '23:00:00';
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

			
			include_once $GLOBALS['babInstallPath']."utilit/editorincl.php";
			
			$editor = new bab_contentEditor('bab_calendar_event');
			$editor->setContent($editor->getContent());
			$editor->setFormat('html');
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

			$this->avariability = isset($GLOBALS['avariability']) && is_array($GLOBALS['avariability'])  ? 1 : 0;
			$this->avariability_message = &$GLOBALS['avariability_message'];
			
			$this->days = array(0, 1, 2, 3, 5, 6, 7, 8, 10, 11, 12);
			$this->hours = array(0, 1, 2, 3, 5, 6, 7, 8, 10, 11, 12);
			$this->minutes = array(0, 5, 10, 15, 30, 45);
			$this->alerttxt = bab_translate("Reminder");
			if( isset($GLOBALS['babEmailReminder']) &&  $GLOBALS['babEmailReminder'])
				{
				$this->remailtxt = bab_translate("Use email reminder");
				}
			else
				{
				$this->remailtxt = "";
				}

			if (isset($_POST) && count($_POST) > 0)
				{
				foreach($_POST as $k => $v)
					{
					$this->arr[$k] = bab_pp($k);
					}

				$this->arr['title'] = bab_toHtml($this->arr['title']);
				$this->arr['location'] = bab_toHtml($this->arr['location']);

				$this->daytypechecked = isset($this->arr['daytype']) ? 'checked' : '';
				$this->daysel = $this->arr['daybegin'];
				$this->monthsel = $this->arr['monthbegin'];
				$this->yearsel = $this->arr['yearbegin'];
				$this->timesel = isset($this->arr['timebegin']) ? $this->arr['timebegin'] : $this->timesel;
				$this->colorvalue = $this->arr['color'];

				
				$this->rcheckedval = isset($this->arr['creminder']) ? 'checked' : '';
				$this->rmcheckedval = isset($this->arr['remail']) ? 'checked' : '';
				$this->arralert['day'] = isset($this->arr['rday']) ? $this->arr['rday'] : '';
				$this->arralert['hour'] = isset($this->arr['rhour']) ? $this->arr['rhour'] : '';
				$this->arralert['minute'] = isset($this->arr['rminute']) ? $this->arr['rminute'] : '';
				}
			else
				{
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
				
				
			for ($i = 0 ; $i < 7 ; $i++)
				{
				$this->repeat_wd_checked[$i] = isset($this->arr['repeat_wd']) && in_array($i,$this->arr['repeat_wd']) ? 'checked' : '';
				}

			
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
			global $babMonths;
			static $i = 1, $k = 0;

			if( $i < 13)
				{
				$this->monthid = $i;
				$this->monthname = bab_toHtml($babMonths[$i]);
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
			if (!isset($GLOBALS['avariability']) || !is_array($GLOBALS['avariability']))
				return false;
			return list(,$this->conflict) = each($GLOBALS['avariability']);
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
	$babBodyPopup->babecho(	bab_printTemplate($temp,"event.html", "scripts"));
	$babBodyPopup->babecho(	bab_printTemplate($temp,"event.html", "newevent"));
	}


function modifyEvent($idcal, $evtid, $cci, $view, $date)
	{
	global $babBody,$babDB, $babBodyPopup;
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
		var $all;
		var $thisone;
		var $updaterec;

		function temp($idcal, $evtid, $cci, $view, $date, $res)
			{
			global $babBody, $babDB, $BAB_SESS_USERID, $babBodyPopup;

			$this->delete = bab_translate("Delete");
			$this->t_color = bab_translate("Color");
			$this->t_bprivate = bab_translate("Private");
			$this->t_block = bab_translate("Lock");
			$this->t_bfree = bab_translate("Free");
			$this->t_yes = bab_translate("Yes");
			$this->t_no = bab_translate("No");
			$this->t_modify = bab_translate("Modify the event");
			$this->t_test_conflicts = bab_translate("Test conflicts");
			$this->t_event_owner = bab_translate("Event owner");
			$this->calid = $idcal;
			$this->evtid = $evtid;
			$this->bmodif = false;
			$this->ccids = $cci;
			$this->curview = $view;
			$this->curdate = $date;
			
			$resown = $babDB->db_query('
				SELECT id_cal FROM '.BAB_CAL_EVENTS_OWNERS_TBL.' WHERE id_event='.$babDB->quote($this->evtid).'
			');
			
			$selected_calendars = array();
			while ($arr = $babDB->db_fetch_assoc($resown)) {
				$selected_calendars[] = $arr['id_cal'];
			}
			
			$this->calendars = calendarchoice('vacform', $selected_calendars);
			
			$this->evtarr = $babDB->db_fetch_array($res);
			
			$iarr = $babBody->icalendars->getCalendarInfo($this->calid);
			switch( $iarr['type'] )
				{
				case BAB_CAL_USER_TYPE:
					if( $iarr['idowner'] ==  $GLOBALS['BAB_SESS_USERID'] || $iarr['access'] != BAB_CAL_ACCESS_VIEW )
						{
						$this->bmodif = true;
						}
					break;
				case BAB_CAL_PUB_TYPE:
					if( $iarr['manager'] )
						{
						$this->bmodif = true;
						}
					break;
				case BAB_CAL_RES_TYPE:
					if( $iarr['manager'] || ($this->evtarr['id_creator'] ==  $GLOBALS['BAB_SESS_USERID'] && $iarr['upd']))
						{
						$this->bmodif = true;
						}
					break;
				}
			$babBodyPopup->title = bab_toHtml(bab_translate("Calendar"). ":  ". bab_getCalendarOwnerName($this->calid, $iarr['type']));

			if( !empty($this->evtarr['hash']) && $this->evtarr['hash'][0] == 'R')
				{
				$this->brecevt = true;
				$this->updaterec = bab_translate("This is recurring event. Do you want to update this occurence or series?");
				$this->all = bab_translate("All");
				$this->thisone = bab_translate("This occurence");
				}
			else
				{
				$this->brecevt = false;
				}

			list($bshowui) = $babDB->db_fetch_array($babDB->db_query("select show_update_info from ".BAB_CAL_USER_OPTIONS_TBL." where id_user='".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."'"));
			if( empty($bshowui))
				{
				$bshowui = $babBody->babsite['show_update_info'];
				}

			$this->bshowupadetinfo = false;
			if( $bshowui == 'Y' && $this->evtarr['id_modifiedby'] )
				{
				$this->bshowupadetinfo = true;
				$this->modifiedontxt = bab_translate("Created/Updated on");
				$this->bytxt = bab_translate("By");
				$this->updatedate = bab_toHtml(bab_shortDate(bab_mktime($this->evtarr['date_modification']), true));
				$this->updateauthor = bab_toHtml(bab_getUserName($this->evtarr['id_modifiedby']));
				}
				
			include_once $GLOBALS['babInstallPath']."utilit/editorincl.php";

			$editor = new bab_contentEditor('bab_calendar_event');
			$editor->setContent($this->evtarr['description']);
			$this->evtarr['description'] = $editor->getHtml();

			$this->ymin = 2;
			$this->ymax = 5;
			if (isset($_POST) && count($_POST) > 0)
				{
				foreach($_POST as $k => $v)
					{
					$this->evtarr[$k] = bab_pp($k);
					}
				$this->evtarr['id_cat'] = $_POST['category'];

				$this->yearbegin = $this->evtarr['yearbegin'];
				$this->daybegin =$this->evtarr['daybegin'];
				$this->monthbegin = $this->evtarr['monthbegin'];
				$this->yearend = $this->evtarr['yearend'];
				$this->dayend = $this->evtarr['dayend'];
				$this->monthend = $this->evtarr['monthend'];
				$this->timebegin = $this->evtarr['timebegin'];
				$this->timeend = $this->evtarr['timeend'];

				}
			else
				{
				$this->yearbegin = substr($this->evtarr['start_date'], 0,4 );
				$this->daybegin = substr($this->evtarr['start_date'], 8, 2);
				$this->monthbegin = substr($this->evtarr['start_date'], 5, 2);
				$this->yearend = substr($this->evtarr['end_date'], 0,4 );
				$this->dayend = substr($this->evtarr['end_date'], 8, 2);
				$this->monthend = substr($this->evtarr['end_date'], 5, 2);
				$this->timebegin = substr($this->evtarr['start_date'], 11, 5);
				$this->timeend = substr($this->evtarr['end_date'], 11, 5);
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
			$this->title = bab_translate("Title");
			$this->description = bab_translate("Description");
			$this->location = bab_translate("Location");
			$this->category = bab_translate("Category");
			$this->descurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=event&idx=updesc&calid=".$this->calid."&evtid=".$evtid);

			include_once $GLOBALS['babInstallPath']."utilit/editorincl.php";
			
			
			
			$editor = new bab_contentEditor('bab_calendar_event');
			
			$tmp = $editor->getContent();
			if (!empty($tmp)) {
				$editor->setContent($tmp);
			} else {
				$editor->setContent($this->evtarr['description']);
			}
			
			
			$editor->setFormat('html');
			$editor->setParameters(array('height' => 150));
			$this->editor = $editor->getEditor();

			$this->elapstime = $babBody->icalendars->elapstime;
			$this->ampm = $babBody->ampm;
			$this->colorvalue = isset($_POST['color']) ? $_POST['color'] : $this->evtarr['color'] ;
			$this->avariability = isset($GLOBALS['avariability']) && is_array($GLOBALS['avariability'])  ? 1 : 0;
			$this->avariability_message = &$GLOBALS['avariability_message'];
			

			$this->rescat = $babDB->db_query("select * from ".BAB_CAL_CATEGORIES_TBL." ORDER BY name");
			$this->rescount = $babDB->db_num_rows($this->rescat);
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
			global $babMonths;
			static $i = 1;
			static $tr = 0;

			if( $i < 13)
				{
				$this->monthid = $i;
				$this->monthname = bab_toHtml($babMonths[$i]);
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
			if (!isset($GLOBALS['avariability']) || !is_array($GLOBALS['avariability']))
				return false;
			return list(,$this->conflict) = each($GLOBALS['avariability']);
			}

		}

	$res = $babDB->db_query("select * from ".BAB_CAL_EVENTS_TBL." where id='".$babDB->db_escape_string($evtid)."'");
	if( !$res || $babDB->db_num_rows($res) == 0 )
		{
		$babBodyPopup->msgerror = bab_translate("Access denied");
		return false;
		}
	
	$temp = new temp($idcal, $evtid, $cci, $view, $date, $res);
	$babBodyPopup->babecho(	bab_printTemplate($temp,"event.html", "scripts"));
	$babBodyPopup->babecho(	bab_printTemplate($temp,"event.html", "modifyevent"));
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
			if( isset($_POST['bupdrec']) && $_POST['bupdrec'] == "1" )
				{
				$bupd = 1;
				$this->message = bab_translate("This is a reccuring event.Are you sure you want to delete this event and all occurrences");
				$this->warning = bab_translate("WARNING: This operation will delete all occurrences permanently"). "!";
				}
			else
				{
				$bupd = 2;
				$this->message = bab_translate("Are you sure you want to delete this event");
				$this->warning = bab_translate("WARNING: This operation will delete event permanently"). "!";
				}
			$this->title = bab_getCalendarEventTitle($_POST['evtid']);
			$this->urlyes = bab_toHtml( $GLOBALS['babUrlScript']."?tg=event&date=".$_POST['date']."&calid=".$_POST['calid']."&evtid=".$_POST['evtid']."&action=yes&view=".$_POST['view']."&bupdrec=".$bupd."&curcalids=".$_POST['curcalids']);
			$this->yes = bab_translate("Yes");
			$this->urlno = bab_toHtml($GLOBALS['babUrlScript']."?tg=event&idx=unload&action=no&calid=".$_POST['calid']."&view=");
			$this->no = bab_translate("No");
			}
		}

	$temp = new deleteEventCls();
	$babBodyPopup->babecho(	bab_printTemplate($temp,"warning.html", "warningyesno"));
	}
	
	
	



function addEvent(&$message)
	{
	global $babBody, $babDB;
	
	if( empty($_POST['title']))
		{
		$message = bab_translate("You must provide a title")." !!";
		return false;
		}

	if( !isset($GLOBALS['calid']) || count($GLOBALS['calid']) == 0 )
		{
		$message = bab_translate("You must select at least one calendar type")." !!";
		return false;
		}

	$args = array();

	if( !empty($GLOBALS['BAB_SESS_USERID']) && isset($_POST['creminder']) && $_POST['creminder'] == 'Y')
		{
		$args['alert']['day'] = $_POST['rday'];
		$args['alert']['hour'] = $_POST['rhour'];
		$args['alert']['minute'] = $_POST['rminute'];
		$args['alert']['email'] = isset($_POST['remail'])? $_POST['remail']: 'N';
		}
	
	include_once $GLOBALS['babInstallPath']."utilit/editorincl.php";
			
	$editor = new bab_contentEditor('bab_calendar_event');
	$args['description'] = $editor->getContent();
	
	$args['title'] = bab_pp('title');
	$args['location'] = bab_pp('location');
		
	$args['category'] = empty($_POST['category']) ? '0' : $_POST['category'];
	$args['color'] = empty($_POST['color']) ? '' : $_POST['color'];

	$args['startdate']['year'] = $_POST['yearbegin'];
	$args['startdate']['month'] = $_POST['monthbegin'];
	$args['startdate']['day'] = $_POST['daybegin'];
	if (isset($_POST['timebegin'])) {
		$timebegin = $_POST['timebegin'];
	} else {
		$timebegin = $babBody->icalendars->starttime;
	}
	$tb = explode(':',$timebegin);
	$args['startdate']['hours'] = $tb[0];
	$args['startdate']['minutes'] = $tb[1];

	$args['enddate']['year'] = $_POST['yearend'];
	$args['enddate']['month'] = $_POST['monthend'];
	$args['enddate']['day'] = $_POST['dayend'];
	if (isset($_POST['timeend'])) {
		$timeend = $_POST['timeend'];
	} else {
		if ($babBody->icalendars->endtime > $timebegin) {
			$timeend = $babBody->icalendars->endtime;
		} else {
			$timeend = '23:59:59';
		}
	}
	
	$tb = explode(':',$timeend);
	$args['enddate']['hours'] = $tb[0];
	$args['enddate']['minutes'] = $tb[1];


	if( isset($_POST['bprivate']) && $_POST['bprivate'] ==  'Y' )
		{
		$args['private'] = true;
		}
	else
		{
		$args['private'] = false;
		}

	if( isset($_POST['block']) && $_POST['block'] ==  'Y' )
		{
		$args['lock'] = true;
		}
	else
		{
		$args['lock'] = false;
		}

	if( isset($_POST['bfree']) && $_POST['bfree'] ==  'Y' )
		{
		$args['free'] = true;
		}
	else
		{
		$args['free'] = false;
		}

	$id_owner = $GLOBALS['BAB_SESS_USERID'];

	if (isset($_POST['event_owner']) && isset($babBody->icalendars->usercal[$_POST['event_owner']]) )
		{
		$arr = $babDB->db_fetch_array($babDB->db_query("SELECT owner FROM ".BAB_CALENDAR_TBL." WHERE id='".$babDB->db_escape_string($_POST['event_owner'])."'"));
		$id_owner = isset($arr['owner']) ? $arr['owner'] : $GLOBALS['BAB_SESS_USERID'];
		}
	$args['owner'] = $id_owner;

	$begin = mktime( $args['startdate']['hours'],$args['startdate']['minutes'],0,$args['startdate']['month'], $args['startdate']['day'], $args['startdate']['year'] );
	$end = mktime( $args['enddate']['hours'],$args['enddate']['minutes'],0,$args['enddate']['month'], $args['enddate']['day'], $args['enddate']['year'] );
	$repeatdate = mktime( 23,59,59,$_POST['repeat_monthend'], $_POST['repeat_dayend'], $_POST['repeat_yearend'] );

	if( $begin >= $end)
		{
		$message = bab_translate("End date must be older")." !";
		return false;
		}
	


	if( isset($_POST['repeat_cb']) && $_POST['repeat_cb'] != 0)
		{
		
			
		if( $repeatdate < $end)
			{
			$message = bab_translate("Repeat date must be older than end date");
			return false;
			}
		
		$args['until'] = array('year'=>$_POST['repeat_yearend'], 'month'=>$_POST['repeat_monthend'], 'day'=>$_POST['repeat_dayend']);
		switch($_POST['repeat'] )
			{
			case BAB_CAL_RECUR_WEEKLY: /* weekly */
				$args['rrule'] = BAB_CAL_RECUR_WEEKLY;
				if( empty($_POST['repeat_n_2']))
					{
					$_POST['repeat_n_2'] = 1;
					}

				$args['nweeks'] = $_POST['repeat_n_2'];

				if( isset($_POST['repeat_wd']) )
					{
					$args['rdays'] = $_POST['repeat_wd'];
					}

				break;
			case BAB_CAL_RECUR_MONTHLY: /* monthly */
				$args['rrule'] = BAB_CAL_RECUR_MONTHLY;
				if( empty($_POST['repeat_n_3']))
					{
					$_POST['repeat_n_3'] = 1;
					}

				$args['nmonths'] = $_POST['repeat_n_3'];
				break;
			case BAB_CAL_RECUR_YEARLY: /* yearly */
				$args['rrule'] = BAB_CAL_RECUR_YEARLY;
				if( empty($_POST['repeat_n_4']))
					{
					$_POST['repeat_n_4'] = 1;
					}
				$args['nyears'] = $_POST['repeat_n_4'];
				break;
			case BAB_CAL_RECUR_DAILY: /* daily */
			default:
				$args['rrule'] = BAB_CAL_RECUR_DAILY;
				if( empty($_POST['repeat_n_1']))
					{
					$_POST['repeat_n_1'] = 1;
					}

				$args['ndays'] = $_POST['repeat_n_1'];
				$rtime = 24*3600*$_POST['repeat_n_1'];
				break;
			}

		}
		
	return bab_createEvent($_POST['selected_calendars'], $args, $message);
	}



function updateEvent(&$message)
{
	global $babBody, $babDB;
	
	if( empty($_POST['title']))
		{
		$message = bab_translate("You must provide a title")." !!";
		return false;
		}

	
	$title = bab_pp('title');
	$location = bab_pp('location');
	
	
	include_once $GLOBALS['babInstallPath']."utilit/editorincl.php";
			
	$editor = new bab_contentEditor('bab_calendar_event');
	$description = $editor->getContent();

	if( empty($_POST['category']))
		$catid = 0;
	else
		$catid = $_POST['category'];

	$evtinfo = $babDB->db_fetch_array($babDB->db_query("select hash, start_date, end_date from ".BAB_CAL_EVENTS_TBL." where id='".$babDB->db_escape_string($_POST['evtid'])."'"));

	$arrupdate = array();

	if( !empty($evtinfo['hash']) &&  $evtinfo['hash'][0] == 'R' && isset($_POST['bupdrec']) && $_POST['bupdrec'] == "1" )
	{
		$res = $babDB->db_query("select * from ".BAB_CAL_EVENTS_TBL." where hash='".$babDB->db_escape_string($evtinfo['hash'])."'");
		while( $arr = $babDB->db_fetch_array($res))
		{
			$rr = explode(" ", $arr['start_date']);
			$rr0 = explode("-", $rr[0]);
			$rr1 = explode(":", $rr[1]);
			$startdate = sprintf("%04d-%02d-%02d %s:00", $rr0[0], $rr0[1], $rr0[2], $_POST['timebegin']);

			$rr = explode(" ", $arr['end_date']);
			$rr0 = explode("-", $rr[0]);
			$rr1 = explode(":", $rr[1]);
			$enddate = sprintf("%04d-%02d-%02d %s:00", $rr0[0], $rr0[1], $rr0[2], $_POST['timeend']);

			if( bab_mktime($startdate) >= bab_mktime($enddate) )
				{
				$message = bab_translate("End date must be older")." !!";
				return false;
				}

			$arrupdate[$arr['id']] = array('start'=>$startdate, 'end' => $enddate);
			$min = $startdate;
			$max = $enddate;
		}

	}
	else
	{

		$startdate = sprintf("%04d-%02d-%02d %s:00", $_POST['yearbegin'], $_POST['monthbegin'], $_POST['daybegin'], $_POST['timebegin']);
		$enddate = sprintf("%04d-%02d-%02d %s:00", $_POST['yearend'], $_POST['monthend'], $_POST['dayend'], $_POST['timeend']);

		if( bab_mktime($startdate) >= bab_mktime($enddate) )
			{
			$message = bab_translate("End date must be older")." !!";
			return false;
			}

		$arrupdate[$_POST['evtid']] = array('start'=>$startdate, 'end' => $enddate);
		$min = $startdate;
		$max = $enddate;
	}

	reset($arrupdate);
	$req = "UPDATE ".BAB_CAL_EVENTS_TBL." 
	SET 
		title			=".$babDB->quote($title).", 
		description		=".$babDB->quote($description).", 
		location		=".$babDB->quote($location).", 
		id_cat			=".$babDB->quote($catid).", 
		color			=".$babDB->quote($_POST['color']).", 
		bprivate		=".$babDB->quote($_POST['bprivate']).", 
		block			=".$babDB->quote($_POST['block']).", 
		bfree			=".$babDB->quote($_POST['bfree']).",
		date_modification=now(),
		id_modifiedby	=".$babDB->quote($GLOBALS['BAB_SESS_USERID'])."
	";
	
	if (isset($_POST['event_owner']) && !empty($_POST['event_owner']) )
		{
		$arr = $babDB->db_fetch_array($babDB->db_query("SELECT owner FROM ".BAB_CALENDAR_TBL." WHERE id='".$babDB->db_escape_string($_POST['event_owner'])."'"));
		if( isset($arr['owner']))
			{
			$req .= ", id_creator=".$babDB->quote($arr['owner'])."";
			}
		}
	
	
	foreach($arrupdate as $key => $val)
	{
		$babDB->db_query($req.", start_date=".$babDB->quote($val['start']).", end_date=".$babDB->quote($val['end'])." where id=".$babDB->quote($key)."" );
		
		$min = $val['start'] < $min ? $val['start'] : $min;
		$max = $val['end']	 > $max ? $val['end'] 	: $max;
	}
	
	
	$exclude = array();
	bab_updateSelectedCalendars(
		bab_pp('evtid'), 
		bab_pp('selected_calendars'),
		$exclude
	);
	
	
	include_once $GLOBALS['babInstallPath'].'utilit/eventperiod.php';
	$event = new bab_eventPeriodModified(bab_mktime($min), bab_mktime($max), false);
	$event->types = BAB_PERIOD_CALEVENT;
	bab_fireEvent($event);

	notifyEventUpdate(bab_pp('evtid'), false, $exclude);
	return true;
}

function confirmDeleteEvent()
{
	global $babBody, $babDB;
	include_once $GLOBALS['babInstallPath'].'utilit/afincl.php';
	
	$res = $babDB->db_query("select * from ".BAB_CAL_EVENTS_TBL." where id='".$babDB->db_escape_string($GLOBALS['evtid'])."'");
	$event = $babDB->db_fetch_array($res);
	
	$calendars = array();
	
	$res = $babDB->db_query('SELECT * FROM '.BAB_CAL_EVENTS_OWNERS_TBL.' WHERE id_event='.$babDB->quote($GLOBALS['evtid']));
	while ($row = $babDB->db_fetch_assoc($res)) {
		$calendars[$row['id_cal']] = $row['id_cal'];
	}
	
	if( $GLOBALS['bupdrec'] == "1" )
		{
		$date_min = '9999-99-99 99:99:99';
		$date_max = '0000-00-00 00:00:00';
	
		
		if( $event['hash'] != "" && $event['hash'][0] == 'R')
			{
			$res = $babDB->db_query("select id, start_date, end_date from ".BAB_CAL_EVENTS_TBL." where hash='".$babDB->db_escape_string($event['hash'])."'");
			while( $arr = $babDB->db_fetch_array($res) )
				{
				$date_min = $arr['start_date'] < $date_min 	? $arr['start_date'] 	: $date_min;
				$date_max = $arr['end_date'] > $date_max	? $arr['end_date'] 		: $date_max;
				
				$babDB->db_query("delete from ".BAB_CAL_EVENTS_TBL." where id='".$babDB->db_escape_string($arr['id'])."'");
				$res2 = $babDB->db_query("select idfai from ".BAB_CAL_EVENTS_OWNERS_TBL." where id_event='".$babDB->db_escape_string($arr['id'])."'");
				while( $rr = $babDB->db_fetch_array($res2) )
					{
					if( $rr['idfai'] != 0 )
						{
						deleteFlowInstance($rr['idfai']);
						}
					}
				$babDB->db_query("delete from ".BAB_CAL_EVENTS_OWNERS_TBL." where id_event='".$babDB->db_escape_string($arr['id'])."'");
				$babDB->db_query("delete from ".BAB_CAL_EVENTS_NOTES_TBL." where id_event='".$babDB->db_escape_string($arr['id'])."'");
				$babDB->db_query("delete from ".BAB_CAL_EVENTS_REMINDERS_TBL." where id_event='".$babDB->db_escape_string($arr['id'])."'");
				}
			}
		}
	else
		{
		$date_min = $event['start_date'];
		$date_max = $event['end_date'];
		
		$babDB->db_query("delete from ".BAB_CAL_EVENTS_TBL." where id='".$babDB->db_escape_string($GLOBALS['evtid'])."'");
		$res2 = $babDB->db_query("select idfai from ".BAB_CAL_EVENTS_OWNERS_TBL." where id_event='".$babDB->db_escape_string($GLOBALS['evtid'])."'");
		while( $rr = $babDB->db_fetch_array($res2) )
			{
			if( $rr['idfai'] != 0 )
				{
				deleteFlowInstance($rr['idfai']);
				}
			}
		$babDB->db_query("delete from ".BAB_CAL_EVENTS_OWNERS_TBL." where id_event='".$babDB->db_escape_string($GLOBALS['evtid'])."'");
		$babDB->db_query("delete from ".BAB_CAL_EVENTS_NOTES_TBL." where id_event='".$babDB->db_escape_string($GLOBALS['evtid'])."'");
		$babDB->db_query("delete from ".BAB_CAL_EVENTS_REMINDERS_TBL." where id_event='".$babDB->db_escape_string($GLOBALS['evtid'])."'");
		}
		
		
	foreach($calendars as $id_cal) {
		$cal = $babBody->icalendars->getCalendarInfo($id_cal);
			
		cal_notify(
			$event['title'], 
			$event['description'], 
			$event['location'], 
			$startdate = bab_longDate(bab_mktime($event['start_date'])),
			$enddate = bab_longDate(bab_mktime($event['end_date'])),
			$id_cal, 
			$cal['type'], 
			$cal['idowner'],
			bab_translate("An appointement has been removed")
			);
	}
		
	include_once $GLOBALS['babInstallPath'].'utilit/eventperiod.php';
	$event = new bab_eventPeriodModified(bab_mktime($date_min), bab_mktime($date_max), false);
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


function eventAvariabilityCheck(&$avariability_message)
	{
	global $babDB, $babBody;
	if( !isset($_POST['monthbegin']))
		{
		return true; /* to rmove php warnings. This function must be rewrited */
		}

	if (isset($_POST['test_conflicts']))
		$_POST['avariability'] = 0;

	if (!isset($_POST['avariability']) || $_POST['avariability'] == 1 || (isset($_POST['bfree']) && $_POST['bfree'] == 'Y' ))
		{
		$GLOBALS['avariability'] = 0;
		return true;
		}
	$calid = explode(',',$GLOBALS['calid']);

	$bfree = isset($_POST['bfree']) ? $_POST['bfree'] : 'N';

	$timebegin = isset($_POST['timebegin']) ? $_POST['timebegin'] : $babBody->icalendars->starttime;
	$timeend = isset($_POST['timeend']) ? $_POST['timeend'] : $babBody->icalendars->endtime;
	
	$tb = explode(':',$timebegin);
	$te = explode(':',$timeend);

	$begin = mktime( $tb[0],$tb[1],0,$_POST['monthbegin'], $_POST['daybegin'], $_POST['yearbegin'] );
	$end = mktime( $te[0],$te[1],0,$_POST['monthend'], $_POST['dayend'], $_POST['yearend'] );

	$begin_day = mktime( 0,0,0,$_POST['monthbegin'], $_POST['daybegin'], $_POST['yearbegin'] );

	$sdate = sprintf("%04s-%02s-%02s %02s:%02s:%02s", date('Y',$begin), date('m',$begin), date('d',$begin), date('H',$begin), date('i',$begin), date('s',$begin));
	$edate = sprintf("%04s-%02s-%02s %02s:%02s:%02s",  date('Y',$end), date('m',$end), date('d',$end),date('H',$end),date('i',$end), date('s',$end));


	// working hours test


	bab_debug("periode teste : $sdate, $edate");
	
	$whObj = bab_mcalendars::create_events($sdate, $edate, $calid);
	
	while ($event = $whObj->getNextEvent(BAB_PERIOD_CALEVENT)) {
		$data = $event->getData();
		
		if (isset($_POST['evtid'])) {
			if ((int) $data['id_event'] === (int) $_POST['evtid']) {
				// considérer l'evenement modifie comme disponible
				$whObj->setAvailability($event, true);
			}
		}
	}

	$availability = NULL;
	$arr = $whObj->getAvailability($availability);
	
	// debug
	foreach($arr as $obj) {
		bab_debug('periode dispo : '.bab_shortDate($obj->ts_begin).' '.bab_shortDate($obj->ts_end));
	}
	
	if (false === $availability) {
		$avariability_message = bab_translate("The event is in conflict with a calendar");
	} 


	// events tests
	$mcals = & new bab_mcalendars($sdate, $edate, $calid);
	$GLOBALS['avariability'] = array();
	while ($cal = current($mcals->objcals)) 
		{
		$arr = array();
		$cal->getEvents($sdate, $edate, $arr);
		foreach ($arr as $calPeriod)
			{
			$event = $calPeriod->getData();

			if (isset($event['bfree']) && $event['bfree'] !='Y' && (!isset($_POST['evtid']) || $_POST['evtid'] != $event['id_event']))
				{
				global $babBody;
				$title = bab_translate("Private");
				if( 
					(
						'PUBLIC' !== $calPeriod->getProperty('CLASS') 
						&& $event['id_cal'] == $babBody->icalendars->id_percal
					) 
					|| 'PUBLIC' === $calPeriod->getProperty('CLASS'))
				{
					$title = $calPeriod->getProperty('SUMMARY');
				}

				$GLOBALS['avariability'][] = $cal->cal_name.' '.bab_translate("on the event").' : '. $title .' ('.bab_shortDate(bab_mktime($calPeriod->getProperty('DTSTART')),false).')';

				}
			}
		next($mcals->objcals);
		}

	

	if ($avariability_message || (is_array($GLOBALS['avariability']) && count($GLOBALS['avariability']) > 0 ))
		{
		return false;
		}
	else
		{
		
		$GLOBALS['avariability'] = 0;
		return true;
		}
	}

/* main */
$idx = bab_rp('idx','newevent');
if (isset($_POST['selected_calendars'])) {
	$calid = implode(',', $_POST['selected_calendars']);
} else {
	$calid = bab_rp('calid');
}

$calid = bab_isCalendarAccessValid($calid);
if( !$calid )
	{
	echo bab_translate("Access denied");
	exit;
	}

$avariability_message = '';

if (isset($_REQUEST['action']))
	{
	switch($_REQUEST['action'])
		{
		case 'yes':
			confirmDeleteEvent();
			$idx="unload";
			break;

		case 'addevent':
			$message = '';
			if (eventAvariabilityCheck($avariability_message) && addEvent($message))
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
				if (eventAvariabilityCheck($avariability_message) && updateEvent($message))
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
		popupUnload($popupmessage, $refreshurl);
		exit;
		break;

	case "modevent":
		if( !isset($message)) { $message = '';}
		$babBodyPopup = new babBodyPopup();
		$babBodyPopup->msgerror = $message;
		modifyEvent($calid, bab_rp('evtid'), bab_rp('cci'), bab_rp('view'), bab_rp('date'));
		printBabBodyPopup();
		exit;
		break;

	case "newevent":
		if( !isset($message)) { $message = '';}
		$babBodyPopup = new babBodyPopup();
		$babBodyPopup->title = bab_translate("New calendar event");
		$babBodyPopup->msgerror = $message;
		newEvent();
		printBabBodyPopup();
		exit;
		break;

	default:
		break;
	}
?>
