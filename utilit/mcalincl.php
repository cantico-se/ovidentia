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

define("BAB_CAL_NAME_LENGTH", 20);
define("BAB_CAL_EVENT_LENGTH", 20);

class bab_mcalendars
{
	var $categories;
	var $freeevents;
	var $idcals = array();
	var $objcals = array();
	var $idxcat = 0;

	function bab_mcalendars($startdate, $enddate, $idcals)
		{
		$this->freeevents[] = array($startdate, $enddate, 0);
		$this->idcals = $idcals;
		for( $i = 0; $i < count($this->idcals); $i++ )
			{
			$this->objcals[$this->idcals[$i]] =& new bab_icalendar($startdate, $enddate, $this->idcals[$i]);
			for( $k= 0; $k < count($this->objcals[$this->idcals[$i]]->events); $k++ )
				{
				if( $this->objcals[$this->idcals[$i]]->events[$k]['bfree'] != 'Y' )
					{
					$this->updateFreeEvents($this->objcals[$this->idcals[$i]]->events[$k]['start_date'], $this->objcals[$this->idcals[$i]]->events[$k]['end_date']);
					}
				}
			
			}
		}

	function getCalendarName($idcal)
		{
		if( isset($this->objcals[$idcal]) )
			{
			return $this->objcals[$idcal]->cal_name;
			}
		return "";
		}

	function getCalendarType($idcal)
		{
		if( isset($this->objcals[$idcal]) )
			{
			return $this->objcals[$idcal]->cal_type;
			}
		return "";
		}

	function getCalendarAccess($idcal)
		{
		if( isset($this->objcals[$idcal]) )
			{
			return $this->objcals[$idcal]->access;
			}
		return "";
		}

	function getNextEvent($idcal, $startdate, $enddate, &$arr)
		{
		if( isset($this->objcals[$idcal]) )
			{
			return $this->objcals[$idcal]->getNextEvent($startdate, $enddate, $arr);
			}
		else
			{
			return false;
			}
		}

	function getEvents($idcal, $startdate, $enddate, &$arr)
		{
		if( isset($this->objcals[$idcal]) )
			{
			return $this->objcals[$idcal]->getEvents($startdate, $enddate, $arr);
			}
		else
			{
			return 0;
			}
		}


	function getHtmlArea($idcal, $startdate, $enddate, &$harray)
		{
		if( isset($this->objcals[$idcal]) )
			{
			return $this->objcals[$idcal]->getHtmlArea($startdate, $enddate, $harray);
			}
		else
			{
			return 0;
			}
		}


	function enumCategories()
		{
		$this->idxcat = 0;
		$this->loadCategories();
		}

	function getNextCategory(&$arr)
		{
		if( $this->idxcat < count($this->categories))
			{
			$arr = $this->categories[$this->idxcat];
			$this->idxcat++;
			return true;
			}
		else
			{
			$this->idxcat = 0;
			return false;
			}
		}
	
	function loadCategories()
		{
		global $babDB;
		static $bload = false;
		if( !$bload )
			{
			$res = $babDB->db_query("select * from ".BAB_CAL_CATEGORIES_TBL." order by name");
			while( $arr = $babDB->db_fetch_array($res))
				{
				$this->categories[$arr['id']] = array('name' => $arr['name'], 'description' => $arr['description'],'bgcolor' => $arr['bgcolor']);
				}
			}
		}

	function getCategoryColor($idcat)
		{
		$this->loadCategories();

		if( isset($this->categories[$idcat]))
			{
			return $this->categories[$idcat]['bgcolor'];
			}
		return "";
		}

	function getCategoryName($idcat)
		{
		$this->loadCategories();

		if( isset($this->categories[$idcat]))
			{
			return $this->categories[$idcat]['name'];
			}
		return "";
		}

	function getCategoryDescription($idcat)
		{
		$this->loadCategories();

		if( isset($this->categories[$idcat]))
			{
			return $this->categories[$idcat]['description'];
			}
		return "";
		}


	function updateFreeEvents($startdate, $enddate)
		{
		if( count($this->freeevents) > 0 )
			{
			if( $startdate > $enddate )
				{
				$tmp = $startdate;
				$startdate = $enddate;
				$enddate = $tmp;
				}

			$tmparr = array();

			for( $i =0; $i < count($this->freeevents); $i++ )
				{
				if( $this->freeevents[$i][2] == 0 )
					{
					if( $startdate > $this->freeevents[$i][0] && $enddate < $this->freeevents[$i][1])
						{
						$tmparr[] = array($this->freeevents[$i][0], $startdate, 0);
						$tmparr[] = array($startdate, $enddate, 1);
						$tmparr[] = array($enddate, $this->freeevents[$i][1], 0);		
						}
					elseif( $startdate > $this->freeevents[$i][0] && $startdate < $this->freeevents[$i][1] )
						{
						$tmparr[] = array($this->freeevents[$i][0], $startdate, 0);
						$tmparr[] = array($startdate, $this->freeevents[$i][1], 1);
						}
					elseif(  $enddate > $this->freeevents[$i][0] && $enddate < $this->freeevents[$i][1] )
						{
						$tmparr[] = array($this->freeevents[$i][0], $enddate, 1);		
						$tmparr[] = array($enddate, $this->freeevents[$i][1], 0);		
						}
					elseif( $startdate > $this->freeevents[$i][1] || $enddate < $this->freeevents[$i][0] )
						{
						$tmparr[] = array($this->freeevents[$i][0], $this->freeevents[$i][1], 0);		
						}
					else
						{
						$tmparr[] = array($this->freeevents[$i][0], $this->freeevents[$i][1], $this->freeevents[$i][2]);		
						}
					}
				else
					{
					$tmparr[] = $this->freeevents[$i];
					}

				}
			$this->freeevents = $tmparr;
			}
		}


	function getNextFreeEvent($date, $gap, &$arr) /* YYYY-MM-DD */
		{
		static $i =0;
		while( $i < count($this->freeevents) )
			{			
			if( $date." 23:59:59" <= $this->freeevents[$i][0] || $date." 00:00:00" >= $this->freeevents[$i][1] )
				{
				$i++;
				}
			else
				{
				if( $gap == 0 || $this->freeevents[$i][2] == 1 || ( $this->freeevents[$i][2] == 0 && (bab_mktime($this->freeevents[$i][1]) - bab_mktime($this->freeevents[$i][0]) >= $gap )))
					{
					break;
					}
				else
					{
					$i++;
					}
				}
			}

		if( $i < count($this->freeevents))
			{
			$arr = $this->freeevents[$i];
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

class bab_icalendar
{
	var $idcalendar = 0;
	var $access = -1;
	var $cal_type;

	function bab_icalendar($startdate, $enddate, $calid)
		{
		global $babBody, $babDB;

		$babBody->icalendars->initializeCalendars();

		$this->cal_type = $babBody->icalendars->getCalendarType($calid);

		if( $this->cal_type !== false )
			{
			$this->cal_name = $babBody->icalendars->getCalendarName($calid);
			$this->idcalendar = $calid;
			if( $calid == $babBody->icalendars->id_percal ) /* user's calendar */
				{
				$this->access = BAB_CAL_ACCESS_FULL;
				}
			else
				{
				switch($this->cal_type)
					{
					case BAB_CAL_USER_TYPE:
						$this->access = $babBody->icalendars->usercal[$calid]['access'];
						break;
					case BAB_CAL_PUB_TYPE:
						if( $babBody->icalendars->pubcal[$calid]['manager'] )
							{
							$this->access = BAB_CAL_ACCESS_FULL;							
							}
						else
							{
							$this->access = BAB_CAL_ACCESS_VIEW;							
							}
						break;
					case BAB_CAL_RES_TYPE:
						if( $babBody->icalendars->rescal[$calid]['manager'] )
							{
							$this->access = BAB_CAL_ACCESS_FULL;							
							}
						else
							{
							$this->access = BAB_CAL_ACCESS_VIEW;							
							}
						break;
					}
				}
			$this->events = array();
			$res = $babDB->db_query("select ceo.*, ce.* from ".BAB_CAL_EVENTS_OWNERS_TBL." ceo left join ".BAB_CAL_EVENTS_TBL." ce on ceo.id_event=ce.id where ceo.id_cal='".$calid."' and ceo.status != '".BAB_CAL_STATUS_DECLINED."' and ce.start_date <= '".$enddate."' and  ce.end_date >= '".$startdate."' order by ce.start_date asc");
			while( $arr = $babDB->db_fetch_array($res))
				{
				list($arr['nbowners']) = $babDB->db_fetch_row($babDB->db_query("select count(ceo.id_cal) from ".BAB_CAL_EVENTS_OWNERS_TBL." ceo where ceo.id_event='".$arr['id']."' and ceo.id_cal != '".$calid."'"));
				$this->events[] = $arr;
				}
			}
		}


	function getNextEvent($startdate, $enddate, &$arr) /* YYYY-MM-DD HH:MM:SS */
		{
		static $i =0;
		while( $i < count($this->events) )
			{			
			if( $enddate <= $this->events[$i]['start_date'] || $startdate >= $this->events[$i]['end_date'] )
				{
				$i++;
				}
			else
				{
				break;
				}
			}

		if( $i < count($this->events))
			{
			$arr = $this->events[$i];
			$i++;
			return true;
			}
		else
			{
			$i = 0;
			return false;
			}
		}

	function getEvents($startdate, $enddate, &$arr) /* YYYY-MM-DD HH:MM:SS */
		{
		$arr = array();
		$i = 0;
		while( $i < count($this->events) )
			{			
			if( $enddate > $this->events[$i]['start_date'] && $startdate < $this->events[$i]['end_date'] )
				{
				$arr[] = $this->events[$i];
				}
			$i++;
			}
		return count($arr);
		}

	function getHtmlArea($startdate, $enddate, &$harray)
		{
		$arr = array();
		$harray = array();
		while( $this->getNextEvent($startdate, $enddate, $arr))
			{
			$done = false;
			for( $k = 0; $k < count($harray); $k++ )
				{
				$append = true;
				for( $m = 0; $m < count($harray[$k]); $m++ )
					{
					if( $harray[$k][$m]['end_date'] > $arr['start_date'] && $harray[$k][$m]['start_date'] < $arr['end_date'] )
						{
						$append = false;
						break;
						}
					}

				if( $append )
					{
					$done = true;
					$harray[$k][] = $arr;
					}
				}
			if( !$done)
				{
				$harray[][] = $arr;
				}
			}
		return count($harray);
		}
}

class cal_wmdbaseCls
{
	function cal_wmdbaseCls($tg, $idx, $calids, $date)
	{
		global $babBody;

		$this->currentidcals = $calids;
		$this->currentdate = $date;
		$this->idcals = explode(",", $calids);
		$rr = explode(',', $date);
		$this->year = $rr[0];
		$this->month = $rr[1];
		$this->day = $rr[2];

		$this->allow_create = false;
		$this->allow_modify = false;
		$this->allow_view = false;
		$this->allow_viewtitle = true;

		$this->commonurl = $GLOBALS['babUrlScript']."?tg=".$tg."&idx=".$idx."&calid=".$this->currentidcals;

		$time = mktime( 0,0,0, $this->month-1, $this->day, $this->year);
		$this->previousmonthurl = $this->commonurl."&date=".date("Y", $time).",".date("n", $time).",".date("j", $time);
		$time = mktime( 0,0,0, $this->month+1, $this->day, $this->year);
		$this->nextmonthurl = $this->commonurl."&date=".date("Y", $time).",".date("n", $time).",".date("j", $time);

		$time = mktime( 0,0,0, $this->month, $this->day, $this->year-1);
		$this->previousyearurl = $this->commonurl."&date=".date("Y", $time).",".date("n", $time).",".date("j", $time);
		$time = mktime( 0,0,0, $this->month, $this->day, $this->year+1);
		$this->nextyearurl = $this->commonurl."&date=".date("Y", $time).",".date("n", $time).",".date("j", $time);

		$time = mktime( 0,0,0, $this->month, $this->day -7, $this->year);
		$this->previousweekurl = $this->commonurl."&date=".date("Y", $time).",".date("n", $time).",".date("j", $time);
		$time = mktime( 0,0,0, $this->month, $this->day +7, $this->year);
		$this->nextweekurl = $this->commonurl."&date=".date("Y", $time).",".date("n", $time).",".date("j", $time);

		$time = mktime( 0,0,0, $this->month, $this->day -1, $this->year);
		$this->previousdayurl = $this->commonurl."&date=".date("Y", $time).",".date("n", $time).",".date("j", $time);
		$time = mktime( 0,0,0, $this->month, $this->day +1, $this->year);
		$this->nextdayurl = $this->commonurl."&date=".date("Y", $time).",".date("n", $time).",".date("j", $time);

		$this->gotodayurl = $this->commonurl."&date=".date("Y").",".date("n").",".date("j"); 

		switch($tg)
		{
			case "calmonth":
				$this->monthurl = "";
				$this->dayurl = $GLOBALS['babUrlScript']."?tg=calday&calid=".$this->currentidcals."&date=".$date;
				$this->weekurl = $GLOBALS['babUrlScript']."?tg=calweek&calid=".$this->currentidcals."&date=".$date;
				break;
			case "calday":
				$this->monthurl = $GLOBALS['babUrlScript']."?tg=calmonth&calid=".$this->currentidcals."&date=".$date;
				$this->dayurl = "";
				$this->weekurl = $GLOBALS['babUrlScript']."?tg=calweek&calid=".$this->currentidcals."&date=".$date;
				break;
			case "calweek":
				$this->monthurl = $GLOBALS['babUrlScript']."?tg=calmonth&calid=".$this->currentidcals."&date=".$date;
				$this->dayurl = $GLOBALS['babUrlScript']."?tg=calday&calid=".$this->currentidcals."&date=".$date;
				$this->weekurl = "";
				break;
		}


		$this->monthurlname = bab_translate("Month");
		$this->weekurlname = bab_translate("Week");
		$this->dayurlname = bab_translate("Day");
		$this->gotodayname = bab_translate("Go to Today");
		$this->attendeestxt = bab_translate("Attendees");
		$this->statustxt = bab_translate("Waiting event");
		$this->t_calendarchoice = bab_translate('Calendars');
		$this->t_date_from = bab_translate('From');
		$this->t_date_to = bab_translate('To');
		$this->t_category = bab_translate('Category');
		$this->t_prev_day = bab_translate('Previous day');
		$this->t_prev_month = bab_translate('Previous month');
		$this->t_prev_year = bab_translate('Previous year');
		$this->t_next_year = bab_translate('Next year');
		$this->t_next_month = bab_translate('Next month');
		$this->t_next_day = bab_translate('Next day');
		$this->t_new_event = bab_translate('New event');
		$this->t_day_view = bab_translate('Day view');
		$this->t_creator = bab_translate('Author');

		$backurl = urlencode(urlencode($GLOBALS['babUrlScript']."?tg=".$tg."&date=".$date."&calid="));
		$this->calendarchoiceurl = $GLOBALS['babUrlScript']."?tg=calopt&idx=pop_calendarchoice&calid=".$this->currentidcals."&date=".$date."&backurl=".$backurl;

	}

	function updateAccess($evtarr, $calinfo)
	{
		$this->allow_view = true;

		switch( $calinfo['type'] )
			{
			case BAB_CAL_USER_TYPE:
				if( $calinfo['idowner'] ==  $GLOBALS['BAB_SESS_USERID'] || $calinfo['access'] == BAB_CAL_ACCESS_FULL || $calinfo['access'] == BAB_CAL_ACCESS_UPDATE )
					{
					$this->allow_create = true;
					if( $calinfo['idowner'] ==  $GLOBALS['BAB_SESS_USERID'] )
						{
						$this->allow_modify = true;
						}
					elseif( $calinfo['access'] == BAB_CAL_ACCESS_FULL && $evtarr['block'] == 'N')
						{
						$this->allow_modify = true;
						}
					elseif( $calinfo['access'] == BAB_CAL_ACCESS_UPDATE && $calinfo['idowner'] !=  $GLOBALS['BAB_SESS_USERID'] && $evtarr['id_creator'] ==  $GLOBALS['BAB_SESS_USERID'])
						{
						$this->allow_modify = true;
						}
					}
				break;
			case BAB_CAL_PUB_TYPE:
			case BAB_CAL_RES_TYPE:
				if( $calinfo['manager'] )
					{
					$this->allow_create = true;
					$this->allow_modify = true;
					}
				break;
			}

		if( $evtarr['bprivate'] == "Y" && $calinfo['type'] ==  BAB_CAL_USER_TYPE && $GLOBALS['BAB_SESS_USERID'] != $calinfo['idowner'] )
			{
			$this->allow_viewtitle = false;
			}
		else
			{
			$this->allow_viewtitle = true;
			}
	
		if( $evtarr['status'] == BAB_CAL_STATUS_NONE )
			{
			$this->bstatus = true;
			if( $calinfo['type'] == BAB_CAL_USER_TYPE && $calinfo['idowner'] ==  $GLOBALS['BAB_SESS_USERID'] )
				{
				$this->bstatuswc = true;
				}
			else
				{
				$this->bstatuswc = false;
				}
			}
		else
			{
			$this->bstatus = false;
			}
	
	}
}


function calendarchoice($formname)
{
class calendarchoice
	{
	function calendarchoice($formname)
		{
		$this->formname = $formname;
		$this->db = $GLOBALS['babDB'];
		$icalendars = &$GLOBALS['babBody']->icalendars;
		$icalendars->initializeCalendars();
		$this->selectedCalendars = !empty($_REQUEST['calid']) ? explode(',',$_REQUEST['calid']) : isset($icalendars->user_calendarids) ? explode(',',$icalendars->user_calendarids) : array();

		$this->usrcalendarstxt = bab_translate('Users');
		$this->grpcalendarstxt = bab_translate('Collectifs');
		$this->rescalendarstxt = bab_translate('Resources');
		$this->t_goright = bab_translate('Push right');
		$this->t_goleft = bab_translate('Push left');

		$this->resuser = $icalendars->usercal;
		$this->respub = $icalendars->pubcal;
		$this->resres = $icalendars->rescal;

		if (!empty($icalendars->id_percal))
			{
			$this->personal = $icalendars->id_percal;
			$this->selected = in_array($icalendars->id_percal, $this->selectedCalendars) ? 'selected' : '';
			}
		}

	function getnextusrcal()
		{
		$out = list($this->id, $name) = each($this->resuser);
		if ($out)
			{
			$this->name = isset($name['name']) ? $name['name'] : '';
			$this->selected = in_array($this->id,$this->selectedCalendars) ? 'selected' : '';
			}
		return $out;
		}

	function getnextpubcal()
		{
		$out = list($this->id, $cal) = each($this->respub);
		if ($out)
			{
			$this->name = $cal['name'];
			$this->selected = in_array($this->id,$this->selectedCalendars) ? 'selected' : '';
			}
		return $out;
		}

	function getnextrescal()
		{
		$out = list($this->id, $cal) = each($this->resres);
		if ($out)
			{
			$this->name = $cal['name'];
			$this->selected = in_array($this->id,$this->selectedCalendars) ? 'selected' : '';
			}
		return $out;
		}

	function printhtml()
		{
		return bab_printTemplate($this,"calendar.html", "calendarchoice");
		}
	}

$temp = new calendarchoice($formname);
return $temp->printhtml();
}


function record_calendarchoice()
{
global $babBody;

$selected = isset($_POST['selected_calendars']) ? $_POST['selected_calendars'] : array();

if ($GLOBALS['BAB_SESS_LOGGED'])
	{
	$babBody->icalendars->user_calendarids = implode(',',$selected);
	
	$db = &$GLOBALS['babDB'];
	list($n) = $db->db_fetch_array($db->db_query("SELECT COUNT(*) FROM ".BAB_CAL_USER_OPTIONS_TBL." WHERE id_user='".$GLOBALS['BAB_SESS_USERID']."'"));
	if ($n > 0)
		{
		$db->db_query("UPDATE ".BAB_CAL_USER_OPTIONS_TBL." SET  user_calendarids='".$babBody->icalendars->user_calendarids."' WHERE id_user='".$GLOBALS['BAB_SESS_USERID']."'");
		}
	else
		{
		$db->db_query("insert into ".BAB_CAL_USER_OPTIONS_TBL." ( id_user, startday, allday, start_time, end_time, usebgcolor, elapstime, defaultview, work_days, week_numbers, user_calendarids) values ('".$GLOBALS['BAB_SESS_USERID']."', '1', 'N', '08:00:00', '18:00:00', 'Y', '30', '0', '1,2,3,4,5', 'N', '".$babBody->icalendars->user_calendarids."')");
		}
	}

}

?>