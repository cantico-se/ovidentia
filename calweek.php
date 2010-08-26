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

include_once "base.php";
include_once $babInstallPath."utilit/calincl.php";
include_once $babInstallPath."utilit/mcalincl.php";

class cal_weekCls extends cal_wmdbaseCls
	{

	function __construct($idx, $calids, $date)
		{
		global $babBody, $babMonths;
		parent::__construct("calweek", $idx, $calids, $date);

		$this->w = 0;

		$dispdays = explode(',', bab_getICalendars()->dispdays);
		$time = mktime(0,0,0,$this->month,$this->day,$this->year);
		$this->monthname = $babMonths[date("n", $time)]."  ".$this->year;
		$this->totaldays = date("t", $time);

		$this->elapstime = bab_getICalendars()->elapstime;
		list($this->startwtime, , ) = sscanf(bab_getICalendars()->starttime, "%d:%d:%d");
		list($this->endwtime, , ) = sscanf(bab_getICalendars()->endtime, "%d:%d:%d");
		$this->maxidx = ($this->endwtime - $this->startwtime ) * (60/$this->elapstime) +1;

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
				$this->dworkdays[$a] = $this->day - $b + $i;
				}
			}

		$mktime1 = mktime(0,0,0,$this->month, $this->dworkdays[$this->workdays[0]],$this->year);
		$firstmonth = date("n", $mktime1);
		$mktime2 = mktime(0,0,0,$this->month, $this->dworkdays[$this->workdays[count($this->workdays)-1]],$this->year);
		$lastmont = date("n", $mktime2);
		if($firstmonth != $lastmont)
		{
			$this->monthname = $babMonths[$firstmonth].' '.date("Y", $mktime1)." / ".$babMonths[$lastmont].' '.date("Y", $mktime2);
		}
		
		$time1 = mktime( 0,0,0, $this->month, $this->dworkdays[$this->workdays[0]], $this->year);
		$time2 = $time1 + 7*24*3600;

		$this->cdate = sprintf("%04s-%02s-%02s", date("Y", $time1), date("n", $time1), date("j", $time1));
		$this->urldate = sprintf("%d,%d,%d", date("Y", $time1), date("n", $time1), date("j", $time1));

		$this->iso_time1 = sprintf("%04s-%02s-%02s 00:00:00", date("Y", $time1), date("n", $time1), date("j", $time1));
		$this->iso_time2 = sprintf("%04s-%02s-%02s 23:59:59", date("Y", $time2), date("n", $time2), date("j", $time2));

		$this->eventlisturl = bab_toHtml( $GLOBALS['babUrlScript']."?tg=calendar&idx=eventlist&calid=".$this->currentidcals."&from=".date('Y,n,j',$time1)."&to=".date('Y,n,j',$time2)."");

		$this->cindex = 0;
		$this->h_start = '00:00';
		$this->h_end = '00:00';
		$this->bfirstevents = array();
		$this->evtindex = 0;
		}


	function prepare_events() {
		$this->mcals = new bab_mcalendars($this->iso_time1, $this->iso_time2, $this->idcals);
		}

	function prepare_free_events() {
		$this->mcals = new bab_mcalendars($this->iso_time1, $this->iso_time2, $this->idcals);
		$this->whObj = bab_mcalendars::create_events($this->iso_time1, $this->iso_time2, $this->idcals);
		}

	function getnextdayname()
		{
		global $babBody, $babDays;
		static $i = 0;
		if( $i < count($this->workdays))
			{
			$this->mday = $this->dworkdays[$this->workdays[$i]];
			if( $this->mday <= 0 || $this->mday > $this->totaldays)
				{
				$this->currentmonth = 0;
				}
			else
				{
				$this->currentmonth = 1;
				}

			$mktime = mktime(0,0,0,$this->month, $this->mday,$this->year);
			$dday = date("j", $mktime);
			$this->week = bab_translate("Week").' '.date('W',$mktime);
			$this->daynumbername = bab_toHtml($dday);

			$this->cdate = sprintf("%04s-%02s-%02s", date("Y", $mktime), date("n", $mktime), $dday);

			
			if( $dday == date("j", mktime()) && $this->month == date("n", mktime()) && $this->year ==  date("Y", mktime()))
				{
				$this->currentday = 1;
				}
			else
				{
				$this->currentday = 0;
				}
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

	function getnexthour()
			{
			global $babBody;
			static $i = 0;
			if( $i < $this->maxidx)
				{
				$idxhour = $i;
				$curhour = $this->startwtime * 60 + $i * $this->elapstime;
				$endhour = $this->startwtime * 60 + ($i+1) * $this->elapstime;

				$this->h_start = sprintf("%02d:%02d", $curhour/60, $curhour%60);
				$this->h_end = sprintf("%02d:%02d", $endhour/60, $endhour%60);
				if( $babBody->ampm)
					{
					$h = explode(" ", bab_toAmPm($this->h_start));
					$hh = explode(":", $h[0]);
					$this->hour = sprintf("%02d<sup>%s</sup>%02d", $hh[0], $h[1], $hh[1]);
					if( $i == 0 )
						$this->hour = sprintf("%02d<sup>%s", $hh[0], $h[1]);
					else if( $hh[0] == "12" && $hh[1] == "00")
						$this->hour = sprintf("%02d<sup>%s", $hh[0], $h[1]);
					else
						$this->hour = sprintf("%02d<sup>%02d</sup>", $hh[0], $hh[1]);
					}
				else
					{
					$this->hour = sprintf("%02d<sup>%02d</sup>", $curhour/60, $curhour%60);
					}
				
				$this->hoururl = bab_toHtml( $GLOBALS['babUrlScript']."?tg=event&idx=newevent&date=".$this->urldate."&calid=".implode(',',$this->idcals)."&view=viewq&st=".mktime($curhour/60,$curhour%60,0,$this->month,$this->mday,$this->year));
				if( $i % 2)
					{
					$this->altbgcolor = true;
					}
				else
					{
					$this->altbgcolor = false;
					}
				
				$this->startdt = $this->cdate." ".$this->h_start.":00";
				$this->enddt = $this->cdate." ".$this->h_end.":00";
				$i++;
				return true;
				}
			else
				{
				$this->h_start = '00:00';
				$this->h_end = '00:00';
				$i = 0;
				return false;
				}
			}
	
	function getnextday()
		{
		global $babDays;
		static $d = 0;
		if( $d < count($this->workdays))
			{
			
			$this->mday = $this->dworkdays[$this->workdays[$d]];
			$this->dayname = $babDays[$this->workdays[$d]];
			$mktime = mktime(0,0,0,$this->month, $this->mday,$this->year);
			$dday = date("j", $mktime);
			$this->cdate = sprintf("%04s-%02s-%02s", date("Y", $mktime), date("n", $mktime), $dday);
			$this->urldate = sprintf("%d,%d,%d", date("Y", $mktime), date("n", $mktime), $dday);
			
			if( $dday == date("j", mktime()) && $this->month == date("n", mktime()) && $this->year ==  date("Y", mktime()))
				{
				$this->currentday = 1;
				}
			else
				{
				$this->currentday = 0;
				}
			$this->currday = date("j", $mktime);
			$this->daynumberurl = bab_toHtml($this->commonurl."&date=".date("Y", $mktime).",".date("n", $mktime).",".$dday);
			$this->neweventurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=event&idx=newevent&date=".date("Y", $mktime).",".date("n", $mktime).",".$dday."&calid=".implode(',',$this->idcals)."&view=viewq");
			
			$this->harray = array();
			
			$this->mcals->getHtmlArea('bab_NonWorkingDaysCollection', $this->cdate." 00:00:00", $this->cdate." 23:59:59", $this->harray['bab_NonWorkingDaysCollection']);
			$this->hcols[0] = 0;
			foreach($this->idcals as $calendarId )
				{
				$this->mcals->getHtmlArea($calendarId, $this->cdate." 00:00:00", $this->cdate." 23:59:59", $this->harray[$calendarId]);
				if (!isset($this->hcols[$calendarId])) $this->hcols[$calendarId] = 0;
				foreach($this->harray[$calendarId] as $arr)
					{
					$this->hcols[$calendarId] += count($arr);
					}
				}
			$d++;
			return true;
			}
		else
			{
			$d = 0;
			return false;
			}
		}
		
	function getnextcollection()
		{
		
		if(list(,$this->calendarId) = each($this->collections))
			{
			$this->nbCalEvents = isset($this->harray[$this->calendarId][0]) ? count($this->harray[$this->calendarId][0]) : 0;
			$this->cols = count($this->harray[$this->calendarId]);
			
			if ($this->cols)
			{
				$this->cindex++;
			}
			
			$this->icols = 0;
			return true;
			}
		
		reset($this->collections);
		reset($this->idcals);
		return false;
			
		}

	function getnextcal()
		{
		
		if(list(,$this->calendarId) = each($this->idcals))
			{
			$calname = $this->mcals->getCalendarName($this->calendarId);
			$this->fullname = bab_toHtml($calname);
			$this->abbrev = $this->calstr($calname,BAB_CAL_NAME_LENGTH);
			$this->nbCalEvents = isset($this->harray[$this->calendarId][0]) ? count($this->harray[$this->calendarId][0]) : 0;
			$this->cols = count($this->harray[$this->calendarId]);
			
			
			if (0 == $this->cols) {
				$this->cols = 1;
			}
			
			$this->cindex++;
			$this->icols = 0;
			
			
			
			return true;
			}
		else
			{
			$this->cindex = 0;
			reset($this->idcals);
			return false;
			}
		}

	function getnexteventcol()
		{
		global $babBody;
		if( $this->icols < $this->cols)
			{
			$i = 0;
			
			$this->bevent = false;
			
			if (isset($this->harray[$this->calendarId][$this->icols]))
				{
				while( $i < count($this->harray[$this->calendarId][$this->icols]))
					{
					$calPeriod = & $this->harray[$this->calendarId][$this->icols][$i];
					
					if( 
						date('Y-m-d H:i:s', $calPeriod->ts_end) > $this->startdt && 
						date('Y-m-d H:i:s', $calPeriod->ts_begin) < $this->enddt )
						{
						$this->createCommonEventVars($calPeriod);
						if( !isset($this->bfirstevents[$this->calendarId][$calPeriod->getProperty('UID')]) )
							{
							$this->first=1;
							$this->bfirstevents[$this->calendarId][$calPeriod->getProperty('UID')] = 1;
							}
						else
							{
							$this->first=0;
							}
						$this->bevent = true;
						$this->evtindex++;
						
						}
					$i++;
					}
				}
				
			$this->md5 = 'm'.md5($this->calendarId.$this->dayname.$this->currday.$this->h_start.$this->icols);
			$this->icols++;
			return true;
			}
		else
			{
			$this->icols = 0;
			return false;
			}
		}

	function getfreeevent()
		{
		global $babBody;
		$arr = array();
		$this->first=0;
		if( bab_mcalendars::getNextFreeEvent($this->whObj, $this->startdt, $this->enddt, $arr))
			{
			if( !isset($this->bfirstevents[$arr[0].$this->cdate]) )
				{
				$this->first=1;
				$this->bfirstevents[$arr[0].$this->cdate] = 1;
				}
			$this->free = $arr[2] == 0;
			
			$time0 = bab_mktime($arr[0]);
			$time1 = bab_mktime($arr[1]);
			$this->starttime = bab_toHtml(bab_time($time0));
			$this->startdate = bab_toHtml(bab_shortDate($time0, false));
			$this->endtime = bab_toHtml(bab_time($time1));
			$this->enddate = bab_toHtml(bab_shortDate($time1, false));

			$this->addeventurl = bab_toHtml( $GLOBALS['babUrlScript']."?tg=event&idx=newevent&date=".$this->currentdate."&calid=".implode(',',$this->idcals)."&view=viewq&date0=".$time0."&date1=".$time1."&st=".bab_mktime($this->startdt));

			$this->md5 = 'm'.md5($this->dayname.$this->currday.$this->h_start);
			return true;
			}
		else
			{
			$this->cindex++;
			return false;
			}
		}
	}	


function cal_week($calids, $date)
{
	global $babBody;

	$temp = new cal_weekCls("view", $calids, $date);
	$temp->prepare_events();
	$temp->printout("calweek.html", "calweek");
}


function cal_week_free($calids, $date)
{
	global $babBody;

	$temp = new cal_weekCls("free", $calids, $date);
	$temp->prepare_free_events();
	$temp->printout("calweek.html", "calfreeweek");
}

function searchAvailability($calid, $date, $date0, $date1, $gap, $bopt)
{
	if( empty($date0) || empty($date1))
	{
		$rr = explode(',', $date);

		$date0 = date("Y,n,j", mktime(0,0,0, $rr[1], 1, $rr[0]));
		$date1 = date("Y,n,j", mktime(0,0,0, (int)($rr[1])+1, 0, $rr[0]));
	}
	cal_searchAvailability("calweek", $calid, $date, $date0, $date1, $gap, $bopt);
}

/* main */

$idx = bab_rp('idx','view');
$date = bab_rp('date',date('Y,n,j'));
$calid =bab_rp('calid',bab_getICalendars()->getUserCalendars());



switch($idx)
	{
	case "unload":
		include_once $babInstallPath."utilit/uiutil.php";
		popupUnload(bab_translate("Done"), $GLOBALS['babUrlScript']."?tg=calweek&idx=free&calid=".$calid."&date=".$date);
		exit;
		break;
	case "rfree":
		
		$babBody->title = bab_translate("Search free events");
		$babBody->addItemMenu("view", bab_translate("Calendar"), $GLOBALS['babUrlScript']."?tg=calweek&calid=".$calid."&date=".$date);
		$babBody->addItemMenu("free", bab_translate("Availability"), $GLOBALS['babUrlScript']."?tg=calweek&idx=free&calid=".$calid."&date=".$date);
		$babBody->addItemMenu("rfree", bab_translate("Search"), $GLOBALS['babUrlScript']."?tg=calweek&idx=rfree&calid=".$calid."&date=".$date);		

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
			cal_week_free($calid, $date);
			$babBody->addItemMenu("view", $babBody->title, $GLOBALS['babUrlScript']."?tg=calweek&calid=".$calid."&date=".$date);
			$babBody->addItemMenu("free", bab_translate("Availability"), $GLOBALS['babUrlScript']."?tg=calweek&idx=free&calid=".$calid."&date=".$date);
			$babBody->addItemMenu("rfree", bab_translate("Search"), $GLOBALS['babUrlScript']."?tg=calweek&idx=rfree&calid=".$calid."&date=".$date);
			if ($GLOBALS['BAB_SESS_LOGGED']) {
				$urla = "?tg=calweek&calid=".$calid."&date=".$date;
				$babBody->addItemMenu("options", bab_translate("Options"), $GLOBALS['babUrlScript']."?tg=calopt&idx=options&urla=".urlencode($urla));
				}
			}
		break;
	case "viewq":
		$idx = 'view'; /* no break */
	case "view":
	default:
		$calid = bab_isCalendarAccessValid($calid);

		if (!$calid )
			{
			$calid = bab_getCalendarId($BAB_SESS_USERID, 1);
			}

		if( !$calid )
			{
			$babBody->title = bab_translate("Access denied");
			}
		else
			{
			$babBody->setTitle(bab_getCalendarTitle($calid));
			cal_week($calid, $date);
			$babBody->addItemMenu("view", bab_translate('Calendar'), $GLOBALS['babUrlScript']."?tg=calweek&calid=".$calid."&date=".$date);
			$babBody->addItemMenu("free", bab_translate("Availability"), $GLOBALS['babUrlScript']."?tg=calweek&idx=free&calid=".$calid."&date=".$date);
			if ($GLOBALS['BAB_SESS_LOGGED']) {
				$urla = "?tg=calweek&calid=".$calid."&date=".$date;
				$babBody->addItemMenu("options", bab_translate("Options"), $GLOBALS['babUrlScript']."?tg=calopt&idx=options&urla=".urlencode($urla));
				}
			}
		break;
	}
$babBody->setCurrentItemMenu($idx);
bab_siteMap::setPosition('bab','UserCal');
?>
