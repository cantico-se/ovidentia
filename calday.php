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
include_once $babInstallPath."utilit/calincl.php";
include_once $babInstallPath."utilit/mcalincl.php";

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
		$time2 = $time1 + 41*24*3600;
		$this->mcals = & new bab_mcalendars(sprintf("%s-%02s-%02s 00:00:00", date("Y", $time1), date("n", $time1), date("j", $time1)), sprintf("%04s-%02s-%02s 23:59:59", date("Y", $time2), date("n", $time2), date("j", $time2)), $this->idcals);
		$this->cdate = sprintf("%04s-%02s-%02s", date("Y", $time1), date("n", $time1), date("j", $time1));
		$this->dayname = bab_longDate($time1, false);
		$this->week = bab_translate("week").' '.date('W',$time1);

		$this->eventlisturl = $GLOBALS['babUrlScript']."?tg=calendar&idx=eventlist&calid=".$this->currentidcals."&from=".date('Y,n,j',$time1)."&to=".date('Y,n,j',$time2)."";

		$this->alternate = false;
		$this->cindex = 0;


		$this->harray = array();
		for( $i = 0; $i < count($this->idcals); $i++ )
			{
			$this->mcals->getHtmlArea($this->idcals[$i], $this->cdate." 00:00:00", $this->cdate." 23:59:59", $this->harray[$i]);
			}
		$this->bfirstevents = array();
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
				$this->hoururl = $GLOBALS['babUrlScript']."?tg=event&idx=newevent&date=".$this->year.",".$this->month.",".$this->day."&calid=".implode(',',$this->idcals)."&view=viewd&st=".mktime($curhour/60,$curhour%60,0,$this->month,$this->day,$this->year);
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
			$this->fullname = htmlentities($calname);
			$this->fullnameten = $this->calstr($calname,BAB_CAL_NAME_LENGTH);
			$this->cols = count($this->harray[$this->cindex]);
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
		if( $this->icols < $this->cols || ($this->cols == 0 && $this->icols == 0))
			{
			$i = 0;
			$this->bevent = false;
			if (isset($this->harray[$this->cindex-1][$this->icols]))
				{
				while( $i < count($this->harray[$this->cindex-1][$this->icols]))
					{
					$arr = & $this->harray[$this->cindex-1][$this->icols][$i];
					if( $arr['end_date'] > $this->startdt && $arr['start_date'] < $this->enddt )
						{
						$iarr = $babBody->icalendars->getCalendarInfo($arr['id_cal']);
						$this->updateAccess($arr, $iarr);
						if( !isset($this->bfirstevents[$this->cindex-1][$arr['id']]) )
							{
							$this->first=1;
							$this->bfirstevents[$this->cindex-1][$arr['id']] = 1;
							}
						else
							{
							$this->first=0;
							}
						$this->bevent = true;
						$this->idcal = $arr['id_cal'];
						$this->status = $arr['status'];
						if( $arr['id_cat'] == 0 )
							{
							$this->category = '';
							}
						else
							{
							$this->category = $this->mcals->getCategoryName($arr['id_cat']);
							}
		
						$this->bgcolor = $babBody->icalendars->usebgcolor == 'Y' ? (empty($arr['color']) ? ($arr['id_cat'] != 0? $this->mcals->getCategoryColor($arr['id_cat']):''): $arr['color']) : 'fff';
						$this->idevent = $arr['id'];
						$time = bab_mktime($arr['start_date']);
						$this->starttime = bab_time($time);
						$this->startdate = bab_shortDate($time, false);
						$time = bab_mktime($arr['end_date']);
						$this->endtime = bab_time($time);
						$this->enddate = bab_shortDate($time, false);
						$this->id_creator = $arr['id_creator'];
						if( $this->id_creator != 0 )
							{
							$this->creatorname = bab_getUserName($this->id_creator); 
							}
						$this->hash = $arr['hash'];
						$this->bprivate = $arr['bprivate'];
						$this->block = $arr['block'];
						$this->bfree = $arr['bfree'];
						if( !$this->allow_viewtitle  )
							{
							$this->title = bab_translate("Private");
							$this->titleten = $this->title;
							$this->description = "";
							}
						else
							{
							$this->title = $arr['title'];
							$this->titleten = $this->calstr($arr['title']);
							$this->description = bab_replace($arr['description']);
							}

						$this->nbowners = $arr['nbowners'];

						if( $this->allow_modify )
							{
							$this->titletenurl = $GLOBALS['babUrlScript']."?tg=event&idx=modevent&evtid=".$arr['id']."&calid=".$arr['id_cal']."&cci=".$this->currentidcals."&view=viewd&date=".$this->currentdate;
							}
						elseif( $this->allow_view )
							{
							$this->titletenurl = $GLOBALS['babUrlScript']."?tg=calendar&idx=vevent&evtid=".$arr['id']."&idcal=".$arr['id_cal'];
							}
						else
							{
							$this->titletenurl = "";
							}
						$this->attendeesurl = $GLOBALS['babUrlScript']."?tg=calendar&idx=attendees&evtid=".$arr['id']."&idcal=".$arr['id_cal'];
						$this->vieweventurl = $GLOBALS['babUrlScript']."?tg=calendar&idx=vevent&evtid=".$arr['id']."&idcal=".$arr['id_cal'];
						break;
						}
					$i++;
					}
				}
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
		if( $this->mcals->getNextFreeEvent($this->startdt, $this->enddt, $arr))
			{
			if( !isset($this->bfirstevents[$arr[0]]) )
				{
				$this->first=1;
				$this->bfirstevents[$arr[0]] = 1;
				}
			$this->free = $arr[2] == 0;
			$time0 = bab_mktime($arr[0]);
			$this->starttime = bab_time($time0);
			$this->startdate = bab_shortDate($time0, false);
			$time1 = bab_mktime($arr[1]);
			$this->endtime = bab_time($time1);
			$this->enddate = bab_shortDate($time1, false);
			$this->addeventurl = $GLOBALS['babUrlScript']."?tg=event&idx=newevent&date=".$this->currentdate."&calid=".implode(',',$this->idcals)."&view=viewm&date0=".$time0."&date1=".$time1;
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
	$temp->printout("calday.html", "calday");
}


function cal_day_free($calids, $date, $starttime)
{
	global $babBody;

	$temp = new cal_dayCls("free", $calids, $date, $starttime);
	$temp->printout("calday.html", "calfreeday");
}

function searchAvailability($calid, $date, $date0, $date1, $gap)
{
	if( empty($date0) || empty($date1))
	{
		$rr = explode(',', $date);
		$time = 

		$date0 = date("Y,n,j", mktime(0,0,0, $rr[1], 1, $rr[0]));
		$date1 = date("Y,n,j", mktime(0,0,0, (int)($rr[1])+1, 0, $rr[0]));
	}
	cal_searchAvailability("calday", $calid, $date, $date0, $date1, $gap);
}

/* main */
if(!isset($idx))
	{
	$idx='viewd';
	}

if( !isset($start)) { $start='';}

if( empty($date))
	{
	$date = Date("Y,n,j");
	}

if( !isset($calid) )
	$calid = bab_getCalendarId($BAB_SESS_USERID, 1);


switch($idx)
	{
	case "unload":
		include_once $babInstallPath."utilit/uiutil.php";
		$popupmessage = bab_translate("Done");
		popupUnload($popupmessage, $GLOBALS['babUrlScript']."?tg=calday&idx=free&calid=".$calid."&date=".$date);
		exit;
		break;
	case "rfree":
		include_once $babInstallPath."utilit/uiutil.php";
		$babBodyPopup = new babBodyPopup();
		$babBodyPopup->title = bab_translate("Search free events");
		if( !isset($gap)) { $gap = 0;}
		if( !isset($date0)) { $date0 = "";}
		if( !isset($date1)) { $date1 = "";}
		searchAvailability($calid, $date, $date0, $date1, $gap);
		printBabBodyPopup();
		exit;
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
			cal_day_free($calid, $date, $start);
			$babBody->addItemMenu("view", $babBody->title, $GLOBALS['babUrlScript']."?tg=calday&calid=".$calid."&date=".$date);
			$babBody->addItemMenu("free", bab_translate("Availability"), $GLOBALS['babUrlScript']."?tg=calday&idx=free&calid=".$calid."&date=".$date);
			$babBody->addItemMenu("options", bab_translate("Options"), $GLOBALS['babUrlScript']."?tg=calopt&idx=options&urla=".urlencode($GLOBALS['babUrlScript']."?tg=calday&calid=".$calid."&date=".$date));
			}
		break;
	case "viewd":
		$idx = 'view'; /* no break */
	case "view":
	default:
		$calid = bab_isCalendarAccessValid($calid);
		if( !$calid )
			{
			$babBody->title = bab_translate("Acces denied");
			}
		else
			{
			$babBody->title = bab_translate("Calendar");
			cal_day($calid, $date, $start);
			$babBody->addItemMenu("view", $babBody->title, $GLOBALS['babUrlScript']."?tg=calday&calid=".$calid."&date=".$date);
			$babBody->addItemMenu("free", bab_translate("Availability"), $GLOBALS['babUrlScript']."?tg=calday&idx=free&calid=".$calid."&date=".$date);
			$babBody->addItemMenu("options", bab_translate("Options"), $GLOBALS['babUrlScript']."?tg=calopt&idx=options&urla=".urlencode($GLOBALS['babUrlScript']."?tg=calday&calid=".$calid."&date=".$date));
			}
		break;
	}
$babBody->setCurrentItemMenu($idx);
?>