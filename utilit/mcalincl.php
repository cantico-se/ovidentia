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
			if (isset($this->objcals[$this->idcals[$i]]->events))
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
					if( $enddate > $this->freeevents[$i][0] && $startdate < $this->freeevents[$i][1])
						{
						if( $startdate > $this->freeevents[$i][0] && $enddate < $this->freeevents[$i][1])
							{
							$tmparr[] = array($this->freeevents[$i][0], $startdate, 0);
							$tmparr[] = array($startdate, $enddate, 1);
							$tmparr[] = array($enddate, $this->freeevents[$i][1], 0);		
							}
						elseif( $startdate > $this->freeevents[$i][0] )
							{
							$tmparr[] = array($this->freeevents[$i][0], $startdate, 0);
							$tmparr[] = array($startdate, $this->freeevents[$i][1], 1);
							}
						elseif(  $enddate < $this->freeevents[$i][1] )
							{
							$tmparr[] = array($this->freeevents[$i][0], $enddate, 1);		
							$tmparr[] = array($enddate, $this->freeevents[$i][1], 0);		
							}
						else
							{
							$tmparr[] = array($this->freeevents[$i][0], $this->freeevents[$i][1], 1);		
							}
						}
					else
						{
						$tmparr[] = $this->freeevents[$i]; 
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


	function getNextFreeEvent($startdate, $enddate, &$arr, $gap=0)
		{
		static $i =0;
		while( $i < count($this->freeevents) )
			{			
			if( $enddate <= $this->freeevents[$i][0] || $startdate >= $this->freeevents[$i][1] )
				{
				$i++;
				}
			else
				{
				if( $gap != 0 && $this->freeevents[$i][2] == 0)
					{
					$max = bab_mktime($this->freeevents[$i][1] > $enddate ? $enddate: $this->freeevents[$i][1]);
					$min = bab_mktime($this->freeevents[$i][0] < $startdate ? $startdate: $this->freeevents[$i][0]);
					if( $gap <= $max - $min )
						{
						break;
						}
					else
						{
						$i++;
						}
					}
				else
					{
					break;
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
		if (isset($this->events))
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

		if( isset($this->events) && $i < count($this->events))
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
			if( $enddate >= $this->events[$i]['start_date'] && $startdate <= $this->events[$i]['end_date'] )
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
		$this->print = isset($_GET['print']) && $_GET['print'] == 1;
		$this->multical = count($this->idcals) > 1;

		$this->allow_create = false;
		$this->allow_modify = false;
		$this->allow_view = false;
		$this->allow_viewtitle = true;
		$this->updateCreateAccess();


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

		$this->gotodateurl = $GLOBALS['babUrlScript']."?tg=month&year=".$this->year."&month=".$this->month."&callback=gotodate";

		switch($tg)
		{
			case "calmonth":
				$this->monthurl = "";
				$this->dayurl = $GLOBALS['babUrlScript']."?tg=calday&idx=".$idx."&calid=".$this->currentidcals."&date=".$date;
				$this->weekurl = $GLOBALS['babUrlScript']."?tg=calweek&idx=".$idx."&calid=".$this->currentidcals."&date=".$date;
				break;
			case "calday":
				$this->monthurl = $GLOBALS['babUrlScript']."?tg=calmonth&idx=".$idx."&calid=".$this->currentidcals."&date=".$date;
				$this->dayurl = "";
				$this->weekurl = $GLOBALS['babUrlScript']."?tg=calweek&idx=".$idx."&calid=".$this->currentidcals."&date=".$date;
				break;
			case "calweek":
				$this->monthurl = $GLOBALS['babUrlScript']."?tg=calmonth&idx=".$idx."&calid=".$this->currentidcals."&date=".$date;
				$this->dayurl = $GLOBALS['babUrlScript']."?tg=calday&idx=".$idx."&calid=".$this->currentidcals."&date=".$date;
				$this->weekurl = "";
				break;
		}

		$this->monthurlname = bab_translate("Month");
		$this->weekurlname = bab_translate("Week");
		$this->dayurlname = bab_translate("Day");
		$this->gotodatename = bab_translate("Go to date");
		$this->attendeestxt = bab_translate("Attendees");
		$this->statustxt = bab_translate("Waiting event");
		$this->t_calendarchoice = bab_translate("Calendars");
		$this->t_date_from = bab_translate("date_from");
		$this->t_date_to = bab_translate("date_to");
		$this->t_category = bab_translate("Category");
		$this->t_prev_day = bab_translate("Previous day");
		$this->t_prev_week = bab_translate("Previous week");
		$this->t_prev_month = bab_translate("Previous month");
		$this->t_prev_year = bab_translate("Previous year");
		$this->t_next_year = bab_translate("Next year");
		$this->t_next_month = bab_translate("Next month");
		$this->t_next_week = bab_translate("Next week");
		$this->t_next_day = bab_translate("Next day");
		$this->t_new_event = bab_translate("New event");
		$this->t_day_view = bab_translate("Day view");
		$this->t_creator = bab_translate("Author");
		$this->t_print_friendly = bab_translate("Print Friendly");
		$this->t_print = bab_translate("Print");
		$this->t_view_event = bab_translate("View event");
		$this->t_modify_event = bab_translate("Modify event");
		$this->t_search = bab_translate("Search");
		$this->t_eventlist = bab_translate("Detailed sight");

		$backurl = urlencode(urlencode($GLOBALS['babUrlScript']."?tg=".$tg."|date=".$date."|calid="));
		$this->calendarchoiceurl = $GLOBALS['babUrlScript']."?tg=calopt&idx=pop_calendarchoice&calid=".$this->currentidcals."&date=".$date."&backurl=".$backurl;
		$this->searcheventurl = $GLOBALS['babUrlScript']."?tg=".$tg."&idx=rfree&date=".$date."&calid=".$this->currentidcals;

	}

	function updateAccess($evtarr, $calinfo)
	{
		$this->allow_view = true;
		$this->allow_modify = false;
		$this->allow_viewtitle = false;
		$this->bstatuswc = false;
		$this->bstatus = false;

		switch( $calinfo['type'] )
			{
			case BAB_CAL_USER_TYPE:
				if( $calinfo['idowner'] ==  $GLOBALS['BAB_SESS_USERID'] || $calinfo['access'] == BAB_CAL_ACCESS_FULL || $calinfo['access'] == BAB_CAL_ACCESS_UPDATE )
					{
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

	function updateCreateAccess()
		{
		global $babBody;
		foreach ($this->idcals as $cal)
			{
			$calinfo = $babBody->icalendars->getCalendarInfo($cal);
			switch( $calinfo['type'] )
				{
				case BAB_CAL_USER_TYPE:
					if( $calinfo['idowner'] ==  $GLOBALS['BAB_SESS_USERID'] || $calinfo['access'] == BAB_CAL_ACCESS_FULL || $calinfo['access'] == BAB_CAL_ACCESS_UPDATE)
						{
						$this->allow_create = true;
						return;
						}
					break;
				case BAB_CAL_PUB_TYPE:
				case BAB_CAL_RES_TYPE:
					if ($calinfo['manager'])
						{
						$this->allow_create = true;
						return;
						}
				}
			}
		}

	function calstr($str,$n = BAB_CAL_EVENT_LENGTH)
		{
		if (strlen($str) > $n && (!$this->print || $GLOBALS['tg'] == 'calweek'))
			return htmlentities(substr($str, 0, $n))."...";
		else
			return htmlentities($str);
		}

	function printout($file,$template)
		{
		global $babBody;

		$html = & bab_printTemplate($this,$file,$template);

		if ($this->print)
			{
			include_once $GLOBALS['babInstallPath']."utilit/uiutil.php";
			$GLOBALS['babBodyPopup'] = new babBodyPopup();
			$GLOBALS['babBodyPopup']->title = $babBody->title;
			$GLOBALS['babBodyPopup']->msgerror = $babBody->msgerror;
			$GLOBALS['babBodyPopup']->babecho($html);
			printBabBodyPopup();
			die();
			}
		else
			{
			$babBody->babecho($html);
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

		$this->usrcalendarstxt = bab_translate("Users");
		$this->grpcalendarstxt = bab_translate("Publics");
		$this->rescalendarstxt = bab_translate("Resources");
		$this->t_goright = bab_translate("Push right");
		$this->t_goleft = bab_translate("Push left");
		$this->t_calendars1 = bab_translate("Available calendars");
		$this->t_calendars2 = bab_translate("Selected calendars");

		$this->resuser = $icalendars->usercal;
		$this->respub = $icalendars->pubcal;
		$this->resres = $icalendars->rescal;

		if (!empty($icalendars->id_percal))
			{
			$this->resuser[$icalendars->id_percal] = array('name'=>$GLOBALS['BAB_SESS_USER'],'access'=>2);
			}

		$this->resuser_sort = array();
		foreach($this->resuser as $k => $v)
			{
			if ($_GET['tg'] != 'event' || $v['access'] > 0)
				$this->resuser_sort[$k] = $v['name'];
			}
		asort($this->resuser_sort);

		$this->respub_sort = array();
		foreach($this->respub as $k => $v)
			{
			if ($_GET['tg'] != 'event' || $v['manager'] == 1)
				$this->respub_sort[$k] = $v['name'];
			}
		asort($this->respub_sort);

		$this->resres_sort = array();

		foreach($this->resres as $k => $v)
			{
			if ($_GET['tg'] != 'event' || $v['manager'] == 1)
				$this->resres_sort[$k] = $v['name'];
			}
		asort($this->resres_sort);

		}

	function getnextusrcal()
		{
		$out = list($this->id) = each($this->resuser_sort);
		if ($out)
			{
			$this->name = isset($this->resuser[$this->id]['name']) ? $this->resuser[$this->id]['name'] : '';
			$this->selected = in_array($this->id,$this->selectedCalendars) ? 'selected' : '';
			}
		return $out;
		}

	function getnextpubcal()
		{
		$out = list($this->id) = each($this->respub_sort);
		if ($out)
			{
			$this->name = $this->respub[$this->id]['name'];
			$this->selected = in_array($this->id,$this->selectedCalendars) ? 'selected' : '';
			}
		return $out;
		}

	function getnextrescal()
		{
		$out = list($this->id) = each($this->resres_sort);
		if ($out)
			{
			$this->name = $this->resres[$this->id]['name'];
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

if ($GLOBALS['BAB_SESS_LOGGED'] && !empty($_POST['database_record']))
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


function cal_searchAvailability($tg, $calid, $date, $date0, $date1, $gap, $bopt)
{
	global $babBodyPopup;
	class cal_searchAvailabilityCls
		{

		function cal_searchAvailabilityCls($tg, $calid, $date, $date0, $date1, $gap, $bopt)
			{
			global $babBodyPopup, $babBody, $babDB;
			$this->datebegintxt = bab_translate("Begin date")." ".bab_translate("dd-mm-yyyy");
			$this->dateendtxt = bab_translate("Until date")." ".bab_translate("dd-mm-yyyy");
			$this->searchtxt = bab_translate("Search");
			$this->gaptxt = bab_translate("Minimum interval time");
			$this->datestxt = bab_translate("Dates");
			$this->intervaltxt = bab_translate("Duration");
			$this->yes = bab_translate("Yes");
			$this->no = bab_translate("No");
			$this->optiontxt = bab_translate("Use calendar options");

			$this->bopt = $bopt;
			$this->tg = $tg;
			$this->gap = $gap;
			$this->calid = $calid;
			$this->idcals = explode(",", $calid);
			$this->date = $date;
			$this->date0 = $date0;
			$this->date1 = $date1;
			if( $this->date0 > $this->date1)
				{
				$babBodyPopup->msgerror = bab_translate("End date must be older")." !!";
				}

			$rr = explode(',', $date0);
			$this->sdate = sprintf("%s-%02s-%02s 00:00:00", $rr[0], $rr[1], $rr[2]);
			$this->date0val = $rr[2]."-".$rr[1]."-".$rr[0];
			$rr = explode(',', $date1);
			$this->edate = sprintf("%s-%02s-%02s 23:59:00", $rr[0], $rr[1], $rr[2]);
			$this->date1val = $rr[2]."-".$rr[1]."-".$rr[0];
			$this->mcals = & new bab_mcalendars($this->sdate, $this->edate, $this->idcals);


			$this->ymin = 2;
			$this->ymax = 5;
			list($this->curyear,$this->curmonth,$this->curday) = explode(',', $this->date);
			$this->datebeginurl = $GLOBALS['babUrlScript']."?tg=month&callback=dateBegin&ymin=".$this->ymin."&ymax=".$this->ymax."&month=".$this->curmonth."&year=".$this->curyear; 
			$this->dateendurl = $GLOBALS['babUrlScript']."?tg=month&callback=dateEnd&ymin=".$this->ymin."&ymax=".$this->ymax."&month=".$this->curmonth."&year=".$this->curyear;
			
			$this->gaparr = array();
			$this->gaparr[] = array("name" => bab_translate("One hour"), "val" => 3600);
			$this->gaparr[] = array("name" => bab_translate("Two hours"), "val" => 7200);
			$this->gaparr[] = array("name" => bab_translate("Three hours"), "val" => 10800);
			$this->gaparr[] = array("name" => bab_translate("Four hours"), "val" => 14400);
			$this->gaparr[] = array("name" => bab_translate("Five hours"), "val" => 18000);
			$this->gaparr[] = array("name" => bab_translate("Six hours"), "val" => 21600);
			$this->gaparr[] = array("name" => bab_translate("Seven hours"), "val" => 25200);
			$this->gaparr[] = array("name" => bab_translate("Eight hours"), "val" => 28800);

			$this->gaparr[] = array("name" => bab_translate("One day"), "val" => 86400);
			$this->gaparr[] = array("name" => bab_translate("Two days"), "val" => 172800);
			$this->countgap = count($this->gaparr);
			$this->altbg = true;

			$this->daystxt = bab_translate("Days");
			$this->hourstxt = bab_translate("Hours");
			$this->minutestxt = bab_translate("Minutes");

			$this->freeevents = array();
			$workdays = explode(',', $babBody->icalendars->workdays);
			while( $this->mcals->getNextFreeEvent($this->sdate, $this->edate, $arr, $this->gap))
				{
				$this->free = $arr[2] == 0;
				if( $this->free )
					{
					$this->altbg != $this->altbg;
					if( $this->bopt == 'Y')
						{
						$rr = explode(' ', $arr[0]);
						$time0 = bab_mktime($rr[0].' 00:00:00');
						$rr = explode(' ', $arr[1]);
						$time1 = bab_mktime($rr[0].' 23:59:00');

						while( $time0 < $time1 )
							{
							if( count($workdays) == 0 || in_array(date('w', $time0), $workdays))
								{
								$this->cdate = sprintf("%04s-%02s-%02s", date("Y", $time0), date("n", $time0), date("j", $time0));

								$workdate0 = $this->cdate.' '.$babBody->icalendars->starttime;
								$workdate1 = $this->cdate.' '.$babBody->icalendars->endtime;

								if( $arr[1] > $workdate0 && $arr[0] < $workdate1 )
									{
									if( $arr[0] <= $workdate0 )
										{
										$startdate = $workdate0;
										}
									else
										{
										$startdate = $arr[0];
										}

									if( $arr[1] >= $workdate1 )
										{
										$enddate = $workdate1;
										}
									else
										{
										$enddate = $arr[1];
										}
									$stime = bab_mktime($startdate);
									$etime = bab_mktime($enddate);
									if( $gap <= $etime - $stime )
										{
										$this->freeevents[] = array($stime, $etime);
										}
									}
								}
							$time0 += 24*3600;
							}
						}
					else
						{
						$this->freeevents[] = array(bab_mktime($arr[0]), bab_mktime($arr[1]));
						}
					}
				}
			
			$this->countfree = count($this->freeevents);
			}


		function getnextfreeevent()
			{
			static $i=0;
			global $babBody;
			if( $i < $this->countfree )
				{
				$this->altbg != $this->altbg;
				$time0 = $this->freeevents[$i][0];
				$this->starttime = bab_time($time0);
				$this->startdate = bab_shortDate($time0, false);
				$time1 = $this->freeevents[$i][1];
				$this->endtime = bab_time($time1);
				$this->enddate = bab_shortDate($time1, false);
				$this->refurl = $GLOBALS['babUrlScript']."?tg=".$this->tg."&idx=unload&date=".date("Y,n,j", $time1)."&calid=".implode(',',$this->idcals);
				$interval = $time1 - $time0;
				$tmp = (int)($interval / 86400);
				if( $tmp )
					{
					$this->interval = $tmp." ".$this->daystxt;
					}
				else
					{
					$tmp = (int)($interval / 3600);
					if( $tmp )
						{
						$this->interval = $tmp." ".$this->hourstxt;
						}
					else
						{
						$this->interval = (int)($interval / 60)." ".$this->minutestxt;
						}
					}
				$i++;
				return true;
				}
			else
				{
				return false;
				}
			}

		function getnextgap()
			{
			static $i = 0;
			if( $i < $this->countgap)
				{
				$this->gapname = $this->gaparr[$i]['name'];
				$this->gapval = $this->gaparr[$i]['val'];
				if( $this->gap == $this->gapval )
					{
					$this->selected = 'selected';
					}
				else
					{
					$this->selected = '';
					}
				$i++;
				return true;
				}
			else
				{
				return false;
				}

			}
	
		}

	$temp = new cal_searchAvailabilityCls($tg, $calid, $date, $date0, $date1, $gap, $bopt);
	$babBodyPopup->babecho(bab_printTemplate($temp, "calendar.html", "searchavailability"));
}

?>