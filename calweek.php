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

class cal_weekCls extends cal_wmdbaseCls
	{

	function cal_weekCls($idx, $calids, $date)
		{
		global $babBody, $babMonths;
		$this->cal_wmdbaseCls("calweek", $idx, $calids, $date);

		$this->w = 0;

		$workdays = explode(',', $babBody->icalendars->workdays);
		$time = mktime(0,0,0,$this->month,$this->day,$this->year);
		$this->monthname = $babMonths[date("n", $time)]."  ".$this->year;
		$this->totaldays = date("t", $time);

		$this->elapstime = $babBody->icalendars->elapstime;
		list($this->startwtime, , ) = sscanf($babBody->icalendars->starttime, "%d:%d:%d");
		list($this->endwtime, , ) = sscanf($babBody->icalendars->endtime, "%d:%d:%d");
		$this->maxidx = ($this->endwtime - $this->startwtime ) * (60/$this->elapstime) +1;

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
				$this->dworkdays[$a] = $this->day - $b + $i;
				}
			}

		$time1 = mktime( 0,0,0, $this->month, $this->dworkdays[$this->workdays[0]], $this->year);
		$time2 = $time1 + 41*24*3600;

		$this->cdate = sprintf("%04s-%02s-%02s", date("Y", $time1), date("n", $time1), date("j", $time1));

		$this->mcals = & new bab_mcalendars(sprintf("%s-%02s-%02s 00:00:00", date("Y", $time1), date("n", $time1), date("j", $time1)), sprintf("%04s-%02s-%02s 23:59:59", date("Y", $time2), date("n", $time2), date("j", $time2)), $this->idcals);

		$this->cindex = 0;
		$this->h_start = '00:00';
		$this->h_end = '00:00';
		$this->bfirstevents = array();
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
			$this->week = bab_translate('Week').' '.date('W',$mktime);
			$this->daynumbername = $dday;
			$this->cdate = sprintf("%04s-%02s-%02s", date("Y", $mktime), date("n", $mktime), date("j", $mktime));
			if( $dday == date("j", mktime()) && $this->month == date("n", mktime()) && $this->year ==  date("Y", mktime()))
				{
				$this->currentday = 1;
				}
			else
				{
				$this->currentday = 0;
				}
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
				$this->hoururl = $GLOBALS['babUrlScript']."?tg=event&idx=newevent&date=".$this->year.",".$this->month.",".$this->day."&calid=".implode(',',$this->idcals)."&view=viewd&st=".$this->h_start;
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
		global $babBody;
		static $d = 0;
		if( $d < count($this->workdays))
			{
			
			$this->mday = $this->dworkdays[$this->workdays[$d]];
			$mktime = mktime(0,0,0,$this->month, $this->mday,$this->year);
			$dday = date("j", $mktime);
			$this->cdate = sprintf("%04s-%02s-%02s", date("Y", $mktime), date("n", $mktime), date("j", $mktime));
			if( $dday == date("j", mktime()) && $this->month == date("n", mktime()) && $this->year ==  date("Y", mktime()))
				{
				$this->currentday = 1;
				}
			else
				{
				$this->currentday = 0;
				}
			
			$this->daynumberurl = $this->commonurl."&date=".date("Y", $mktime).",".date("n", $mktime).",".$dday;
			$this->neweventurl = $GLOBALS['babUrlScript']."?tg=event&idx=newevent&date=".date("Y", $mktime).",".date("n", $mktime).",".$dday."&calid=".implode(',',$this->idcals)."&view=viewm";
			$this->harray = array();
			$this->hcols[0] = 0;
			for( $i = 0; $i < count($this->idcals); $i++ )
				{
				$this->mcals->getHtmlArea($this->idcals[$i], $this->cdate." 00:00:00", $this->cdate." 23:59:59", $this->harray[$i]);
				if (!isset($this->hcols[$i])) $this->hcols[$i] = 0;
				foreach($this->harray[$i] as $arr)
					{
					$this->hcols[$i] += count($arr);
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

	function getnextcal()
		{
		if( $this->cindex < count($this->idcals))
			{
			$calname = $this->mcals->getCalendarName($this->idcals[$this->cindex]);
			$this->fullname = htmlentities($calname);
			$this->abbrev = htmlentities(substr($calname, 0, BAB_CAL_NAME_LENGTH));
			$this->cols = count($this->harray[$this->cindex]);
			$this->nbCalEvents = isset($this->harray[$this->cindex][0]) ? count($this->harray[$this->cindex][0]) : 0;
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
		if( $this->icols < $this->cols  || ($this->cols == 0 && $this->icols == 0))
			{
			$i = 0;
			$this->bevent = false;
			if (isset($this->harray[$this->cindex-1][$this->icols]))
				{
				while( $i < count($this->harray[$this->cindex-1][$this->icols]))
					{
					$arr = & $this->harray[$this->cindex-1][$this->icols][$i];
					$iarr = $babBody->icalendars->getCalendarInfo($arr['id_cal']);
					$this->updateAccess($arr, $iarr);
					if( $arr['end_date'] > $this->startdt && $arr['start_date'] < $this->enddt )
						{
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
							$this->title = "xxxxxxxxxx";
							$this->titleten = "xxxxxxxxxx";
							$this->description = "";
							}
						else
							{
							$this->title = $arr['title'];
							$this->titleten = htmlentities(substr($arr['title'], 0, BAB_CAL_EVENT_LENGTH))."...";
							$this->description = $arr['description'];
							}
						$this->nbowners = $arr['nbowners'];

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

	function getnextevent()
		{
		global $babBody;
		static $i =0;
		if( $i < count($this->harray[$this->cindex-1][$this->icols-1]))
			{
			$arr = & $this->harray[$this->cindex-1][$this->icols-1][$i];
			if( $arr['end_date'] > $this->startdt && $arr['start_date'] < $this->enddt )
				{
				$ts1 = bab_mktime($arr['start_date']);
				$ts2 = bab_mktime($this->startdt);
				$ts3 = bab_mktime($this->startdt)+($this->elapstime*60);
				if ($ts1 >= $ts2 && $ts1 <= $ts3)
					$this->first=1;
				else
					$this->first=0;
				$this->bevent = true;
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
				$this->title = $this->startdate." ".$this->starttime. " - ".$this->enddate." ".$this->endtime." ".$arr['title'];
				$this->titleten = htmlentities(substr($arr['title'], 0, BAB_CAL_EVENT_LENGTH));
				$this->nbowners = $arr['nbowners'];
				$this->attendeesurl = $GLOBALS['babUrlScript']."?tg=calendar&idx=attendees&evtid=".$arr['id']."&idcal=".$arr['id_cal'];
				$this->vieweventurl = $GLOBALS['babUrlScript']."?tg=calendar&idx=vevent&evtid=".$arr['id']."&idcal=".$arr['id_cal'];
				}
			else
				{
				$this->bevent = false;
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

	function geteventOld()
		{
		global $babBody;
		$arr = array();
		if( $this->mcals->getNextEvent($this->idcals[$this->cindex], $this->cdate." ".$this->h_start.":00", $this->cdate." ".$this->h_end.":59", $arr))
			{
			$this->idcal = $arr['id_cal'];
			$this->status = $arr['status'];
			$this->bgcolor = $arr['color'];
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
			$this->titleten = htmlentities(substr($arr['title'], 0, BAB_CAL_EVENT_LENGTH));
			return true;
			}
		else
			{
			$this->cindex++;
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


function cal_week($calids, $date)
{
	global $babBody;

	$temp = new cal_weekCls("viewq", $calids, $date);
	$babBody->babecho(bab_printTemplate($temp,"calweek.html", "calweek"));
}


function cal_week_free($calids, $date)
{
	global $babBody;

	$temp = new cal_weekCls("free", $calids, $date);
	$babBody->babecho(bab_printTemplate($temp,"calweek.html", "calfreeweek"));
}

/* main */
if(!isset($idx))
	{
	$idx='viewq';
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
			cal_week_free($calid, $date);
			$babBody->addItemMenu("viewm", $babBody->title, $GLOBALS['babUrlScript']."?tg=calweek&calid=".$calid."&date=".$date);
			$babBody->addItemMenu("free", bab_translate("Availability"), $GLOBALS['babUrlScript']."?tg=calweek&idx=free&calid=".$calid."&date=".$date);
			}
		break;
	case "viewq":
	default:
		$calid = bab_isCalendarAccessValid($calid);
		if( !$calid )
			{
			$babBody->title = bab_translate("Acces denied");
			}
		else
			{
			$babBody->title = bab_translate("Calendar");
			cal_week($calid, $date);
			$babBody->addItemMenu("viewq", $babBody->title, $GLOBALS['babUrlScript']."?tg=calweek&calid=".$calid."&date=".$date);
			$babBody->addItemMenu("free", bab_translate("Availability"), $GLOBALS['babUrlScript']."?tg=calweek&idx=free&calid=".$calid."&date=".$date);
			}
		break;
	}
$babBody->setCurrentItemMenu($idx);
?>