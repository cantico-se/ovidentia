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

class cal_monthCls extends cal_wmdbaseCls
	{

	var $iso_time1;
	var $iso_time2;
 
	function __construct($idx, $calids, $date)
		{
		global $babBody, $babMonths;
		parent::__construct("calmonth", $idx, $calids, $date);
		$this->w = 0;
		$dispdays = explode(',', bab_getICalendars()->dispdays);
		$time = mktime(0,0,0,$this->month,1,$this->year);
		$this->monthname = bab_toHtml($babMonths[date("n", $time)]."  ".$this->year);
		$this->totaldays = date("t", $time);
		$b = date("w", $time) - bab_getICalendars()->startday;
		if( $b < 0)
			$b += 7;

		for( $i = 0; $i < 7; $i++ )
			{
			$a = $i + bab_getICalendars()->startday;
			if( $a > 6)
				$a -=  7;
			if( in_array($a, $dispdays ))
				{
				$this->workdays[] = $a;
				$this->dworkdays[$a] = $i - $b +1;
				}
			}

		$this->bshowonlydaysofmonth = bab_getICalendars()->bshowonlydaysofmonth ==  'Y';
		
		$time1 = mktime( 0,0,0, $this->month, $this->dworkdays[$this->workdays[0]], $this->year);
		$time2 = $time1 + 41*24*3600;

		$this->iso_time1 = sprintf("%s-%02s-%02s 00:00:00", date("Y", $time1), date("n", $time1), date("j", $time1));
		$this->iso_time2 = sprintf("%04s-%02s-%02s 23:59:59", date("Y", $time2), date("n", $time2), date("j", $time2));

		
		$this->eventlisturl = bab_toHtml( $GLOBALS['babUrlScript']."?tg=calendar&idx=eventlist&calid=".$this->currentidcals."&from=".date('Y,n,j',$time1)."&to=".date('Y,n,j',$time2)."");

		$this->xindex = 0;
		$this->cindex = 0;
		$this->evtidx = 0;
		
		$time1 = mktime( 0,0,0, $this->month, $this->dworkdays[$this->workdays[count($this->workdays)-1]], $this->year);
		if( date('n', $time1) != $this->month)
			{
				$this->w_start = 1;
			}
		else
			{
				$this->w_start = 0;
			}
		$this->w = $this->w_start;
		
		$time1 = mktime( 0,0,0, $this->month, (7 * 5) + $this->dworkdays[$this->workdays[0]], $this->year);
		if( date('n', $time1) != $this->month)
			{
				$this->w_end = 5;
			}
		else
			{
				$this->w_end = 6;
			}
		
		}

	function prepare_events() {

		$this->mcals = new bab_mcalendars($this->iso_time1, $this->iso_time2, $this->idcals);
		}

	function prepare_free_events() {
		$this->whObj = bab_mcalendars::create_events($this->iso_time1, $this->iso_time2, $this->idcals);
		}

	function getnextdayname()
		{
		global $babBody, $babDays;
		static $i = 0;
		if( $i < count($this->workdays))
			{
			$this->dayname = bab_toHtml($babDays[$this->workdays[$i]]);
			$i++;
			return true;
			}
		else
			{
			$i = 0;
			return false;
			}
		}
	
	function getnextweek()
		{
		if( $this->w < $this->w_end)
			{
			$this->w++;
			return true;
			}
		else
			{
			$this->w = $this->w_start;
			return false;
			}
		}

	function getnextday()
		{
		global $babBody;
		static $d = 0;
		if( $d < count($this->workdays))
			{
			$this->mday = (7 * ($this->w-1)) + $this->dworkdays[$this->workdays[$d]];
			$mktime = mktime(0,0,0,$this->month, $this->mday,$this->year);
			if( $this->bshowonlydaysofmonth && $this->month != date("n", $mktime))
			{
				$this->bdayofcurmonth = false;
			}
			else
			{
				$this->bdayofcurmonth = true;
			}
			
			if( $this->mday <= 0 || $this->mday > $this->totaldays)
				{
				$this->currentmonth = 0;
				$this->daynumbername = date("d/m", $mktime);
				}
			else
				{
				$this->currentmonth = 1;
				$this->daynumbername = date("d", $mktime);
				}

			
			$dday = date("j", $mktime);
			$this->week = bab_toHtml(bab_translate("Week").' '.date('W', $mktime));
			$this->cdate = sprintf("%04s-%02s-%02s", date("Y", $mktime), date("n", $mktime), date("j", $mktime));
			if( date("j", $mktime) == date("j") && date("n", $mktime) == date("n") && date("Y", $mktime) ==  date("Y"))
				{
				$this->currentday = 1;
				}
			else
				{
				$this->currentday = 0;
				}
			
			$this->daynumberurl = bab_toHtml($this->commonurl."&date=".date("Y", $mktime).",".date("n", $mktime).",".$dday);
			$this->dayviewurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=calday&calid=".implode(',',$this->idcals)."&date=".date("Y", $mktime).",".date("n", $mktime).",".$dday);
			$this->dayfreeviewurl = $this->dayviewurl."&amp;idx=free";
			$this->neweventurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=event&idx=newevent&date=".date("Y", $mktime).",".date("n", $mktime).",".$dday."&calid=".implode(',',$this->idcals)."&view=viewm");
			$d++;
			return true;
			}
		else
			{
			$d = 0;
			return false;
			}
		}
		
	public function getnextcollection()
		{
		if( $this->xindex < count($this->collections))
			{
			$calendarId = $this->collections[$this->xindex];
			
			$this->evtarr = array();
			$this->mcals->getEvents($calendarId, $this->cdate." 00:00:00", $this->cdate." 23:59:59", $this->evtarr);
			$this->countevent = count($this->evtarr);
			$this->xindex++;
			return true;
			}
		else
			{
			$this->xindex = 0;
			return false;
			}
		}

	function getnextcal()
		{
		if( $this->cindex < count($this->idcals))
			{
			$calendarId = $this->idcals[$this->cindex];
			$calname = $this->mcals->getCalendarName($calendarId);
			$this->fullname = bab_toHtml($calname);
			$this->fullnameten = $this->calstr($calname,BAB_CAL_NAME_LENGTH);
			$this->evtarr = array();
			$this->mcals->getEvents($calendarId, $this->cdate." 00:00:00", $this->cdate." 23:59:59", $this->evtarr);
			$this->countevent = count($this->evtarr);
			$this->cindex++;
			return true;
			}
		else
			{
			$this->cindex = 0;
			return false;
			}
		}

	function getnextevent()
		{
		global $babBody, $babDB;
		static $i = 0;
		if( $i < $this->countevent)
			{
			$this->evtidx++;
			$this->createCommonEventVars($this->evtarr[$i]);
			
			
			$eventstart = $this->evtarr[$i]->ts_begin;
			$mktime = mktime(0,0,0,$this->month, $this->mday,$this->year);
			
			if( date("j", $eventstart) == date("j", $mktime) 
			&& date("n", $eventstart) == date("n", $mktime) 
			&& date("Y", $eventstart) == date("Y", $mktime))
				{
				$this->firstday = true;
				}
			else
				{
				$this->firstday = false;
				}
			
			$i++;
			return true;
			}
		else
			{
			$i= 0;
			return false;
			}
		}


	function getfreeevent()
		{
		global $babBody;
		$arr = array();
		if( bab_mcalendars::getNextFreeEvent($this->whObj, $this->cdate." 00:00:00", $this->cdate." 23:59:00", $arr))
			{
			$this->free = 0 == $arr[2];
			$time0 = bab_mktime($arr[0]);
			$time1 = bab_mktime($arr[1]);
			$this->starttime = bab_time($time0);
			$this->startdate = bab_shortDate($time0, false);
			$this->endtime = bab_time($time1);
			$this->enddate = bab_shortDate($time1, false);
			$this->addeventurl = bab_toHtml( $GLOBALS['babUrlScript']."?tg=event&idx=newevent&date=".$this->currentdate."&calid=".implode(',',$this->idcals)."&view=viewm&date0=".$time0."&date1=".$time1);
			return true;
			}
		else
			{
			$this->cindex++;
			return false;
			}
		}
	}	


function cal_month($calids, $date)
{
	global $babBody;

	$temp = new cal_monthCls("view", $calids, $date);
	$temp->prepare_events();
	$temp->printout("calmonth.html", "calmonth");
}


function cal_month_free($calids, $date)
{
	global $babBody;

	$temp = new cal_monthCls("free", $calids, $date);
	$temp->prepare_free_events();
	$temp->printout("calmonth.html", "calfreemonth");
}

function searchAvailability($calid, $date, $date0, $date1, $gap, $bopt)
{
	if( empty($date0) || empty($date1))
	{
		$rr = explode(',', $date);

		$date0 = date("Y,n,j", mktime(0,0,0, $rr[1], 1, $rr[0]));
		$date1 = date("Y,n,j", mktime(0,0,0, (int)($rr[1])+1, 0, $rr[0]));
	}
	cal_searchAvailability("calmonth", $calid, $date, $date0, $date1, $gap, $bopt);
}


/* main */

$calid =bab_rp('calid',bab_getICalendars()->getUserCalendars());
$idx = bab_rp('idx', 'view');
$date = bab_rp('date', date("Y,n,j"));






switch($idx)
	{
	case "unload":
		include_once $babInstallPath."utilit/uiutil.php";
		$popupmessage = bab_translate("Done");
		popupUnload($popupmessage, $GLOBALS['babUrlScript']."?tg=calmonth&idx=free&calid=".$calid."&date=".$date);
		exit;
		break;
	case "rfree":
		$babBody->setTitle(bab_translate("Search free events"));
		$babBody->addItemMenu("view", bab_translate("Calendar"), $GLOBALS['babUrlScript']."?tg=calmonth&calid=".$calid."&date=".$date);
		$babBody->addItemMenu("free", bab_translate("Availability"), $GLOBALS['babUrlScript']."?tg=calmonth&idx=free&calid=".$calid."&date=".$date);
		$babBody->addItemMenu("rfree", bab_translate("Search"), $GLOBALS['babUrlScript']."?tg=calmonth&idx=rfree&calid=".$calid."&date=".$date);

		searchAvailability(
			$calid, 
			$date, 
			bab_rp('date0'), 
			bab_rp('date1'), 
			bab_rp('gap',0), 
			bab_rp('bopt','Y')
		);

		break;

	case "free":
		$calid = bab_isCalendarAccessValid($calid);
		if( !$calid )
			{
			$babBody->setTitle(bab_translate("Access denied"));
			}
		else
			{
			$babBody->setTitle(bab_translate("Calendar"));
			cal_month_free($calid, $date);
			$babBody->addItemMenu("view", $babBody->title, $GLOBALS['babUrlScript']."?tg=calmonth&calid=".$calid."&date=".$date);
			$babBody->addItemMenu("free", bab_translate("Availability"), $GLOBALS['babUrlScript']."?tg=calmonth&idx=free&calid=".$calid."&date=".$date);
			$babBody->addItemMenu("rfree", bab_translate("Search"), $GLOBALS['babUrlScript']."?tg=calmonth&idx=rfree&calid=".$calid."&date=".$date);
			if ($GLOBALS['BAB_SESS_LOGGED']) {
				$urla = "?tg=calmonth&calid=".$calid."&date=".$date;
				$babBody->addItemMenu("options", bab_translate("Options"), $GLOBALS['babUrlScript']."?tg=calopt&idx=options&urla=".urlencode($urla));
				}
			}
		break;
	case "viewm":
		$idx = 'view'; /* no break */
	case "view":
	default:
		$calid = bab_isCalendarAccessValid($calid);
		if (!$calid )
			{
			$calid = bab_getDefaultCalendarId();
			}
		
		if( !$calid )
			{
			$babBody->setTitle(bab_translate("Access denied"));
			}
		else
			{
			$babBody->setTitle(bab_getCalendarTitle($calid));
			cal_month($calid, $date);
			$babBody->addItemMenu("view", bab_translate('Calendar'), $GLOBALS['babUrlScript']."?tg=calmonth&calid=".$calid."&date=".$date);
			$babBody->addItemMenu("free", bab_translate("Availability"), $GLOBALS['babUrlScript']."?tg=calmonth&idx=free&calid=".$calid."&date=".$date);
			if ($GLOBALS['BAB_SESS_LOGGED'])
				$babBody->addItemMenu("options", bab_translate("Options"), $GLOBALS['babUrlScript']."?tg=calopt&idx=options&urla=".urlencode("?tg=calmonth&calid=".$calid."&date=".$date));
			}
		break;
	}
$babBody->setCurrentItemMenu($idx);
bab_siteMap::setPosition('bab','UserCal');
?>
