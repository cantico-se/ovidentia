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
		$this->colspan ="";
		$this->elapstime = $babBody->icalendars->elapstime;
		list($this->startwtime, , ) = sscanf($babBody->icalendars->starttime, "%d:%d:%d");
		list($this->endwtime, , ) = sscanf($babBody->icalendars->endtime, "%d:%d:%d");
		$this->maxidx = ($this->endwtime - $this->startwtime ) * (60/$this->elapstime) +1;

		$time1 = mktime( 0,0,0, $this->month, $this->day, $this->year);
		$time2 = $time1 + 41*24*3600;
		$this->mcals = & new bab_mcalendars(sprintf("%s-%02s-%02s 00:00:00", date("Y", $time1), date("n", $time1), date("j", $time1)), sprintf("%04s-%02s-%02s 23:59:59", date("Y", $time2), date("n", $time2), date("j", $time2)), $this->idcals);
		$this->cdate = sprintf("%04s-%02s-%02s", date("Y", $time1), date("n", $time1), date("j", $time1));
		$this->dayname = bab_longDate($time1, false);

		$this->alternate = false;
		$this->cindex = 0;
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
				if( $babBody->ampm)
					{
					$h = explode(" ", bab_toAmPm($st));
					$hh = explode(":", $h[0]);
					$this->hour = sprintf("%02d<sup>%s</sup>%02d", $hh[0], $h[1], $hh[1]);
					if( $i == 0 )
						$this->hour = sprintf("%02d<sup>%s", $hh[0], $h[1]);
					else if( $hh[0] == "12" && $hh[1] == "00")
						$this->hour = sprintf("%02d<sup>%s", $hh[0], $h[1]);
					else if( $hh[1] != "00")
						$this->hour = sprintf("__<sup>%02d</sup>", $hh[1]);
					else
						$this->hour = sprintf("%02d<sup>%02d</sup>", $hh[0], $hh[1]);
					}
				else
					{
					if( $curhour%60 == 0)
						$this->hour = sprintf("%02d<sup>%02d</sup>", $curhour/60, $curhour%60);
					else
						$this->hour = sprintf("__<sup>%02d</sup>", $curhour%60);
					}
				$this->hoururl = $GLOBALS['babUrlScript']."?tg=event&idx=newevent&day=".$this->day."&month=".$this->month. "&year=".$this->year."&calid=".$this->idcals."&view=viewd&st=".$st;
				if( $i % 2)
					{
					$this->altbgcolor = true;
					}
				else
					{
					$this->altbgcolor = false;
					}
				$this->startdt = $this->cdate." ".$st.":00";
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
			$this->fullnameten = htmlentities(substr($calname, 0, 16));
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
		global $babBody;
		$arr = array();
		if( $this->mcals->getNextEvent($this->idcals[$this->cindex-1], $this->startdt, $this->enddt, $arr))
			{
			$this->idcal = $arr['id_cal'];
			$this->status = $arr['status'];
			if( $arr['id_cat'] == 0 )
				{
				$this->bgcolor = $arr['color'];
				}
			else
				{
				$this->bgcolor = $this->mcals->getCategoryColor($arr['id_cat']);
				}
			$this->idevent = $arr['id'];
			$time = bab_mktime($arr['start_date']);
			$this->starttime = bab_time($time);
			$this->startdate = bab_shortDate($time, false);
			$time = bab_mktime($arr['end_date']);
			$this->endtime = bab_time($time);
			$this->enddate = bab_shortDate($time, false);
			$this->id_cat = $arr['id_cat'];
			$this->id_creator = $arr['id_creator'];
			$this->hash = $arr['hash'];
			$this->bprivate = $arr['bprivate'];
			$this->block = $arr['block'];
			$this->bfree = $arr['bfree'];
			$this->description = $arr['description'];
			$this->title = $this->startdate." ".$this->starttime. "-".$this->enddate." ".$this->endtime." ".$arr['title'];
			$this->titleten = htmlentities(substr($arr['title'], 0, 10));
			$this->nbowners = $arr['nbowners'];
			$this->attendeesurl = $GLOBALS['babUrlScript']."?tg=calendar&idx=attendees&evtid=".$arr['id']."&idcal=".$arr['id_cal'];
			$this->vieweventurl = $GLOBALS['babUrlScript']."?tg=calendar&idx=vevent&evtid=".$arr['id']."&idcal=".$arr['id_cal'];
			return true;
			}
		else
			{
			return false;
			}
		}

	function getfreeevent()
		{
		global $babBody;
		$arr = array();
		if( $this->mcals->getNextFreeEvent($this->cdate, 0, $arr))
			{
			if( $arr[2] == 0 )
				{
				$this->bgcolor = "00FF00";
				}
			else
				{
				$this->bgcolor = "0000FF";
				}
			//$this->bgcolor = $this->icals[$this->cindex]->getCategoryColor($arr['id_cat']);
			$time = bab_mktime($arr[0]);
			$this->starttime = bab_time($time);
			$this->startdate = bab_shortDate($time, false);
			$time = bab_mktime($arr[1]);
			$this->endtime = bab_time($time);
			$this->enddate = bab_shortDate($time, false);
			$this->title = $this->startdate." ".$this->starttime. "<br>".$this->enddate." ".$this->endtime;
			$this->titleten = "";
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

	$temp = new cal_dayCls("viewq", $calids, $date, $starttime);
	$babBody->babecho(bab_printTemplate($temp,"calday.html", "calday"));
}


function cal_day_free($calids, $date, $starttime)
{
	global $babBody;

	$temp = new cal_dayCls("free", $calids, $date, $starttime);
	$babBody->babecho(bab_printTemplate($temp,"calday.html", "calfreeday"));
}

/* main */
if(!isset($idx))
	{
	$idx='viewd';
	}

if( !isset($start)) { $start='';}

if( empty($date))
	{
	$date = Date("Y").",".Date("n").",".Date("j");
	}

if( !isset($calid) )
	$calid = bab_getCalendarId($BAB_SESS_USERID, 1);


switch($idx)
	{
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
			$babBody->addItemMenu("viewd", $babBody->title, $GLOBALS['babUrlScript']."?tg=calday&calid=".$calid."&date=".$date);
			$babBody->addItemMenu("free", bab_translate("Availability"), $GLOBALS['babUrlScript']."?tg=calday&idx=free&calid=".$calid."&date=".$date);
			}
		break;
	case "viewd":
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
			$babBody->addItemMenu("viewd", $babBody->title, $GLOBALS['babUrlScript']."?tg=calday&calid=".$calid."&date=".$date);
			$babBody->addItemMenu("free", bab_translate("Availability"), $GLOBALS['babUrlScript']."?tg=calday&idx=free&calid=".$calid."&date=".$date);
			}
		break;
	}
$babBody->setCurrentItemMenu($idx);
?>