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

class cal_monthCls  extends cal_wmdbaseCls
	{
 
	function cal_monthCls($idx, $calids, $date)
		{
		global $babBody, $babMonths;

		$this->cal_wmdbaseCls("calmonth", $idx, $calids, $date);
		
		$this->w = 0;

		$workdays = explode(',', $babBody->icalendars->workdays);
		$time = mktime(0,0,0,$this->month,1,$this->year);
		$this->monthname = $babMonths[date("n", $time)]."  ".$this->year;
		$this->totaldays = date("t", $time);
		$b = date("w", $time) - $babBody->icalendars->startday;
		if( $b < 0)
			$b += 7;

		for( $i = 0; $i < 7; $i++ )
			{
			$a = $i + $babBody->icalendars->startday;
			if( $a > 6)
				$a -=  7;
			if( in_array($a, $workdays ))
				{
				$this->workdays[] = $a;
				$this->dworkdays[$a] = $i - $b +1;
				}
			}

		$time1 = mktime( 0,0,0, $this->month, $this->dworkdays[$this->workdays[0]], $this->year);
		$time2 = $time1 + 41*24*3600;
		$this->mcals = & new bab_mcalendars(sprintf("%s-%02s-%02s 00:00:00", date("Y", $time1), date("n", $time1), date("j", $time1)), sprintf("%04s-%02s-%02s 23:59:59", date("Y", $time2), date("n", $time2), date("j", $time2)), $this->idcals);

		$this->cindex = 0;

		}

	function getnextdayname()
		{
		global $babBody, $babDays;
		static $i = 0;
		if( $i < count($this->workdays))
			{
			$this->dayname = $babDays[$this->workdays[$i]];
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
		if( $this->w < 6)
			{
			$this->w++;
			return true;
			}
		else
			{
			$this->w = 0;
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
			$this->week = bab_translate("Week").' '.date('W', $mktime);
			$this->cdate = sprintf("%04s-%02s-%02s", date("Y", $mktime), date("n", $mktime), date("j", $mktime));
			if( $dday == date("j", mktime()) && $this->month == date("n", mktime()) && $this->year ==  date("Y", mktime()))
				{
				$this->currentday = 1;
				}
			else
				{
				$this->currentday = 0;
				}
			$this->daynumbername = $dday;
			$this->daynumberurl = $this->commonurl."&date=".date("Y", $mktime).",".date("n", $mktime).",".$dday;
			$this->dayviewurl = $GLOBALS['babUrlScript']."?tg=calday&calid=".implode(',',$this->idcals)."&date=".date("Y", $mktime).",".date("n", $mktime).",".$dday;
			$this->dayfreeviewurl = $this->dayviewurl."&idx=free";
			$this->neweventurl = $GLOBALS['babUrlScript']."?tg=event&idx=newevent&date=".date("Y", $mktime).",".date("n", $mktime).",".$dday."&calid=".implode(',',$this->idcals)."&view=viewm";
			$d++;
			return true;
			}
		else
			{
			$d = 0;
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
			$this->evtarr = array();
			$this->mcals->getEvents($this->idcals[$this->cindex], $this->cdate." 00:00:00", $this->cdate." 23:59:59", $this->evtarr);
			$this->countevent = count($this->evtarr);
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
		static $i = 0;
		if( $i < $this->countevent)
			{
			$arr = $this->evtarr[$i];
			$this->idcal = $arr['id_cal'];
			$this->status = $arr['status'];
			$iarr = $babBody->icalendars->getCalendarInfo($arr['id_cal']);
			$this->updateAccess($arr, $iarr);
			if( $arr['id_cat'] == 0 )
				{
				$this->category = '';
				}
			else
				{
				$this->category = $this->mcals->getCategoryName($arr['id_cat']);
				}

			$this->bgcolor = $babBody->icalendars->usebgcolor == 'Y' ? ( empty($arr['color']) ? ($arr['id_cat'] != 0 ? $this->mcals->getCategoryColor($arr['id_cat']):''): $arr['color']) : 'ffff';
			$this->idevent = $arr['id'];
			$time = bab_mktime($arr['start_date']);
			$this->starttime = bab_time($time);
			$this->startdate = bab_shortDate($time, false);
			$time = bab_mktime($arr['end_date']);
			$this->endtime = bab_time($time);
			$this->enddate = bab_shortDate($time, false);
			$this->id_cat = $arr['id_cat'];
			$this->id_creator = $arr['id_creator'];
			if( $this->id_creator != 0 )
				{
				$this->creatorname = bab_getUserName($this->id_creator); 
				}
			$this->hash = $arr['hash'];
			$this->bprivate = $arr['bprivate'];
			$this->block = $arr['block'];
			$this->bfree = $arr['bfree'];
			$this->nbowners = $arr['nbowners'];
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
			if( $this->allow_modify )
				{
				$this->titletenurl = $GLOBALS['babUrlScript']."?tg=event&idx=modevent&evtid=".$arr['id']."&calid=".$arr['id_cal']."&cci=".$this->currentidcals."&view=viewm&date=".$this->currentdate;
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
			$i++;
			return true;
			}
		else
			{
			$i= 0;
			$this->cindex++;
			return false;
			}
		}


	function getfreeevent()
		{
		global $babBody;
		$arr = array();
		if( $this->mcals->getNextFreeEvent($this->cdate." 00:00:00", $this->cdate." 23:59:00", $arr))
			{
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


function cal_month($calids, $date)
{
	global $babBody;

	$temp = new cal_monthCls("viewm", $calids, $date);
	$temp->printout("calmonth.html", "calmonth");
}


function cal_month_free($calids, $date)
{
	global $babBody;

	$temp = new cal_monthCls("free", $calids, $date);
	$temp->printout("calmonth.html", "calfreemonth");
}

/* main */

$calid = isset($_GET['calid']) ? $_GET['calid'] : $babBody->icalendars->user_calendarids;

if(!isset($idx))
	{
	$idx='viewm';
	}

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
			cal_month_free($calid, $date);
			$babBody->addItemMenu("viewm", $babBody->title, $GLOBALS['babUrlScript']."?tg=calmonth&calid=".$calid."&date=".$date);
			$babBody->addItemMenu("free", bab_translate("Availability"), $GLOBALS['babUrlScript']."?tg=calmonth&idx=free&calid=".$calid."&date=".$date);
			}
		break;
	case "viewm":
	default:
		$calid = bab_isCalendarAccessValid($calid);
		if( !$calid )
			{
			$babBody->title = bab_translate("Acces denied");
			}
		else
			{
			$babBody->title = bab_translate("Calendar");
			cal_month($calid, $date);
			$babBody->addItemMenu("viewm", $babBody->title, $GLOBALS['babUrlScript']."?tg=calmonth&calid=".$calid."&date=".$date);
			$babBody->addItemMenu("free", bab_translate("Availability"), $GLOBALS['babUrlScript']."?tg=calmonth&idx=free&calid=".$calid."&date=".$date);
			}
		break;
	}
$babBody->setCurrentItemMenu($idx);
?>