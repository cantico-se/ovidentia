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

class cal_dayCls extends cal_wmdbaseCls
	{

	function cal_dayCls($idx, $calids, $date, $starttime)
		{
		global $babBody, $babMonths;
		$this->cal_wmdbaseCls("calday", $idx, $calids, $date);

		$this->w = 0;
		$this->elapstime = $babBody->icalendars->elapstime;
		list($this->startwtime, , ) = sscanf($babBody->icalendars->starttime, "%d:%d:%d");
		list($this->endwtime, , ) = sscanf($babBody->icalendars->endtime, "%d:%d:%d");
		$this->maxidx = ($this->endwtime - $this->startwtime ) * (60/$this->elapstime) +1;

		$time1 = mktime( 0,0,0, $this->month, $this->day, $this->year);
		$time2 = $time1 + 24*3600;
		
		$this->cdate = sprintf("%04s-%02s-%02s", date("Y", $time1), date("n", $time1), date("j", $time1));
		$this->dayname = bab_toHtml(bab_longDate($time1, false));
		$this->week = bab_toHtml(bab_translate("week").' '.date('W',$time1));

		$this->iso_time1 = sprintf("%s-%02s-%02s 00:00:00", date("Y", $time1), date("n", $time1), date("j", $time1));
		$this->iso_time2 = sprintf("%04s-%02s-%02s 23:59:59", date("Y", $time2), date("n", $time2), date("j", $time2));

		$this->eventlisturl = bab_toHtml( $GLOBALS['babUrlScript']."?tg=calendar&idx=eventlist&calid=".$this->currentidcals."&from=".date('Y,n,j',$time1)."&to=".date('Y,n,j',$time2));
		

		$this->alternate = false;
		$this->cindex = 0;


		
		$this->bfirstevents = array();
		}


		function prepare_events() {
			$this->mcals = & new bab_mcalendars($this->iso_time1, $this->iso_time2, $this->idcals);
			$this->harray = array();
			for( $i = 0; $i < count($this->idcals); $i++ )
				{
				$this->mcals->getHtmlArea($this->idcals[$i], $this->cdate." 00:00:00", $this->cdate." 23:59:59", $this->harray[$i]);
				}
				
			
		}

		function prepare_free_events() {
			$this->prepare_events();
			$this->whObj = bab_mcalendars::create_events($this->iso_time1, $this->iso_time2, $this->idcals);
		}

		function getnexthour()
			{
			global $babBody;
			static $i = 0;
			if( $i < $this->maxidx)
				{
				$idxhour = $i;
				$curhour = $this->startwtime * 60 + $i * $this->elapstime;
				$st = sprintf("%02d:%02d", $curhour/60, $curhour%60);
				$this->h_start = sprintf("%02d:%02d", $curhour/60, $curhour%60);
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
				$this->hoururl = bab_toHtml( $GLOBALS['babUrlScript']."?tg=event&idx=newevent&date=".$this->year.",".$this->month.",".$this->day."&calid=".implode(',',$this->idcals)."&view=viewd&st=".mktime($curhour/60,$curhour%60,0,$this->month,$this->day,$this->year));
				if( $i % 2)
					{
					$this->altbgcolor = true;
					}
				else
					{
					$this->altbgcolor = false;
					}
				$this->startdt = $this->cdate." ".$this->h_start.":00";
				$this->enddt = $this->cdate." ".sprintf("%02d:%02d", ($curhour+$this->elapstime)/60, ($curhour+$this->elapstime)%60).":00";
				$i++;
				return true;
				}
			else
				{
				$i = 0;
				return false;
				}
			}


	function getnextcal()
		{
		if( $this->cindex < count($this->idcals))
			{
			$calname = $this->mcals->getCalendarName($this->idcals[$this->cindex]);
			$this->fullname = bab_toHtml($calname);
			$this->fullnameten = $this->calstr($calname,BAB_CAL_NAME_LENGTH);
			$this->cols = count($this->harray[$this->cindex]);
			
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
			if (isset($this->harray[$this->cindex-1][$this->icols]))
				{
				while( $i < count($this->harray[$this->cindex-1][$this->icols]))
					{
					$calPeriod = & $this->harray[$this->cindex-1][$this->icols][$i];

					if( $calPeriod->getProperty('DTEND') > $this->startdt && 
						$calPeriod->getProperty('DTSTART') < $this->enddt )
						{
						$this->createCommonEventVars($calPeriod);
						if( !isset($this->bfirstevents[$this->cindex-1][$this->idevent]) )
							{
							$this->first=1;
							$this->bfirstevents[$this->cindex-1][$this->idevent] = 1;
							}
						else
							{
							$this->first=0;
							}
						$this->bevent = true;
						
						}
					$i++;
					}
				}

			$this->md5 = 'm'.md5($this->cindex.$this->h_start.$this->icols);
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
			if( !isset($this->bfirstevents[$arr[0]]) )
				{
				$this->first=1;
				$this->bfirstevents[$arr[0]] = 1;
				}
			$this->free = $arr[2] == 0;

			$time0 = bab_mktime($arr[0]);
			$time1 = bab_mktime($arr[1]);
			$this->starttime = bab_toHtml(bab_time($time0));
			$this->startdate = bab_toHtml(bab_shortDate($time0, false));
			$this->endtime = bab_toHtml(bab_time($time1));
			$this->enddate = bab_toHtml(bab_shortDate($time1, false));
			$this->addeventurl = bab_toHtml( $GLOBALS['babUrlScript']."?tg=event&idx=newevent&date=".$this->currentdate."&calid=".implode(',',$this->idcals)."&view=viewm&date0=".$time0."&date1=".$time1.'&st='.bab_mktime($this->startdt));

			$this->md5 = 'm'.md5($this->cindex.$this->h_start);
			return true;
			}
		else
			{
			$this->cindex++;
			return false;
			}
		}
	}	


function cal_day($calids, $date, $starttime)
{
	global $babBody;

	$temp = new cal_dayCls("view", $calids, $date, $starttime);
	$temp->prepare_events();
	$temp->printout("calday.html", "calday");
}


function cal_day_free($calids, $date, $starttime)
{
	global $babBody;

	$temp = new cal_dayCls("free", $calids, $date, $starttime);
	$temp->prepare_free_events();
	$temp->printout("calday.html", "calfreeday");
}

function searchAvailability($calid, $date, $date0, $date1, $gap, $bopt)
{
	if( empty($date0) || empty($date1))
	{
		$rr = explode(',', $date);
		$time = 

		$date0 = date("Y,n,j", mktime(0,0,0, $rr[1], 1, $rr[0]));
		$date1 = date("Y,n,j", mktime(0,0,0, (int)($rr[1])+1, 0, $rr[0]));
	}
	cal_searchAvailability("calday", $calid, $date, $date0, $date1, $gap, $bopt);
}

/* main */

$idx = bab_rp('idx','view');
$date = bab_rp('date', date("Y,n,j"));
$calid =bab_rp('calid',$babBody->icalendars->user_calendarids);


switch($idx)
	{
	case "unload":
		include_once $babInstallPath."utilit/uiutil.php";
		$popupmessage = bab_translate("Done");
		popupUnload($popupmessage, $GLOBALS['babUrlScript']."?tg=calday&idx=free&calid=".$calid."&date=".$date);
		exit;
		break;
	case "rfree":
		$babBody->title = bab_translate("Search free events");
		$babBody->addItemMenu("view", bab_translate("Calendar"), $GLOBALS['babUrlScript']."?tg=calday&calid=".$calid."&date=".$date);
		$babBody->addItemMenu("free", bab_translate("Availability"), $GLOBALS['babUrlScript']."?tg=calday&idx=free&calid=".$calid."&date=".$date);
		$babBody->addItemMenu("rfree", bab_translate("Search"), $GLOBALS['babUrlScript']."?tg=calday&idx=rfree&calid=".$calid."&date=".$date);	

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
			$babBody->title = bab_translate("Acces denied");
			}
		else
			{
			$babBody->title = bab_translate("Calendar");
			cal_day_free($calid, $date, bab_rp('start'));
			$babBody->addItemMenu("view", $babBody->title, $GLOBALS['babUrlScript']."?tg=calday&calid=".$calid."&date=".$date);
			$babBody->addItemMenu("free", bab_translate("Availability"), $GLOBALS['babUrlScript']."?tg=calday&idx=free&calid=".$calid."&date=".$date);
			$babBody->addItemMenu("rfree", bab_translate("Search"), $GLOBALS['babUrlScript']."?tg=calday&idx=rfree&calid=".$calid."&date=".$date);
			if ($GLOBALS['BAB_SESS_LOGGED']) {
				$babBody->addItemMenu("options", bab_translate("Options"), $GLOBALS['babUrlScript']."?tg=calopt&idx=options&urla=".urlencode($GLOBALS['babUrlScript']."?tg=calday&calid=".$calid."&date=".$date));
				}
			}
		break;
	case "viewd":
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
			$babBody->title = bab_translate("Acces denied");
			}
		else
			{
			$babBody->setTitle(bab_getCalendarTitle($calid));
			cal_day($calid, $date, bab_rp('start'));
			$babBody->addItemMenu("view", bab_translate('Calendar'), $GLOBALS['babUrlScript']."?tg=calday&calid=".$calid."&date=".$date);
			$babBody->addItemMenu("free", bab_translate("Availability"), $GLOBALS['babUrlScript']."?tg=calday&idx=free&calid=".$calid."&date=".$date);
			if ($GLOBALS['BAB_SESS_LOGGED']) {
				$babBody->addItemMenu("options", bab_translate("Options"), $GLOBALS['babUrlScript']."?tg=calopt&idx=options&urla=".urlencode($GLOBALS['babUrlScript']."?tg=calday&calid=".$calid."&date=".$date));
				}
			}
		break;
	}
$babBody->setCurrentItemMenu($idx);
?>