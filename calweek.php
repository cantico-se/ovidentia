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
		$time2 = $time1 + 7*24*3600;

		$this->cdate = sprintf("%04s-%02s-%02s", date("Y", $time1), date("n", $time1), date("j", $time1));

		$this->mcals = & new bab_mcalendars(sprintf("%s-%02s-%02s 00:00:00", date("Y", $time1), date("n", $time1), date("j", $time1)), sprintf("%04s-%02s-%02s 23:59:59", date("Y", $time2), date("n", $time2), date("j", $time2)), $this->idcals);

		$this->eventlisturl = $GLOBALS['babUrlScript']."?tg=calendar&idx=eventlist&calid=".$this->currentidcals."&from=".date('Y,n,j',$time1)."&to=".date('Y,n,j',$time2)."";

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
			$this->week = bab_translate("Week").' '.date('W',$mktime);
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
					else
						$this->hour = sprintf("%02d<sup>%02d</sup>", $hh[0], $hh[1]);
					}
				else
					{
					$this->hour = sprintf("%02d<sup>%02d</sup>", $curhour/60, $curhour%60);
					}
				$this->hoururl = $GLOBALS['babUrlScript']."?tg=event&idx=newevent&date=".$this->year.",".$this->month.",".$this->day."&calid=".implode(',',$this->idcals)."&view=viewd&st=".mktime($curhour/60,$curhour%60,0,$this->month,$this->mday,$this->year);
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
			$this->abbrev = $this->calstr($calname,BAB_CAL_NAME_LENGTH);
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

						$this->bgcolor = $babBody->icalendars->usebgcolor == 'Y' ? (empty($arr['color']) ? ($arr['id_cat'] != 0? $this->mcals->getCategoryColor($arr['id_cat']):''): $arr['color']) : 'fff';

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
							$this->titletenurl = $GLOBALS['babUrlScript']."?tg=event&idx=modevent&evtid=".$arr['id']."&calid=".$arr['id_cal']."&cci=".$this->currentidcals."&view=viewq&date=".$this->currentdate;
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

	function getfreeevent(&$skip)
		{
		global $babBody;
		$arr = array();
		$this->first=0;
		if( $this->mcals->getNextFreeEvent($this->startdt, $this->enddt, $arr))
			{
			if( !isset($this->bfirstevents[$arr[0].$this->cdate]) )
				{
				$this->first=1;
				$this->bfirstevents[$arr[0].$this->cdate] = 1;
				}
			$this->free = $arr[2] == 0;
			$workdate0 = $this->cdate.' '.$babBody->icalendars->starttime;
			$workdate1 = $this->cdate.' '.$babBody->icalendars->endtime;
			if( $this->free )
				{
				if( $arr[1] <= $workdate0 || $arr[0] >= $workdate1 )
					{
					$skip = true;
					return true;
					}
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
				}
			else
				{
				$startdate = $arr[0];
				$enddate = $arr[1];
				}


			$time0 = bab_mktime($startdate);
			$time1 = bab_mktime($enddate);
			$this->starttime = bab_time($time0);
			$this->startdate = bab_shortDate($time0, false);
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


function cal_week($calids, $date)
{
	global $babBody;

	$temp = new cal_weekCls("view", $calids, $date);
	$temp->printout("calweek.html", "calweek");
}


function cal_week_free($calids, $date)
{
	global $babBody;

	$temp = new cal_weekCls("free", $calids, $date);
	$temp->printout("calweek.html", "calfreeweek");
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
	cal_searchAvailability("calweek", $calid, $date, $date0, $date1, $gap, $bopt);
}

/* main */
if(!isset($idx))
	{
	$idx='view';
	}

if( empty($date))
	{
	$date = Date("Y").",".Date("n").",".Date("j");
	}

if( !isset($calid) )
	$calid = bab_getCalendarId($BAB_SESS_USERID, 1);


switch($idx)
	{
	case "unload":
		include_once $babInstallPath."utilit/uiutil.php";
		$popupmessage = bab_translate("Done");
		popupUnload($popupmessage, $GLOBALS['babUrlScript']."?tg=calweek&idx=free&calid=".$calid."&date=".$date);
		exit;
		break;
	case "rfree":
		include_once $babInstallPath."utilit/uiutil.php";
		$babBodyPopup = new babBodyPopup();
		$babBodyPopup->title = bab_translate("Search free events");
		if( !isset($gap)) { $gap = 0;}
		if( !isset($date0)) { $date0 = "";}
		if( !isset($date1)) { $date1 = "";}
		if( !isset($bopt)) { $bopt = "Y";}
		searchAvailability($calid, $date, $date0, $date1, $gap, $bopt);
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
			cal_week_free($calid, $date);
			$babBody->addItemMenu("view", $babBody->title, $GLOBALS['babUrlScript']."?tg=calweek&calid=".$calid."&date=".$date);
			$babBody->addItemMenu("free", bab_translate("Availability"), $GLOBALS['babUrlScript']."?tg=calweek&idx=free&calid=".$calid."&date=".$date);
			$babBody->addItemMenu("options", bab_translate("Options"), $GLOBALS['babUrlScript']."?tg=calopt&idx=options&urla=".urlencode($GLOBALS['babUrlScript']."?tg=calweek&calid=".$calid."&date=".$date));			}
		break;
	case "viewq":
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
			cal_week($calid, $date);
			$babBody->addItemMenu("view", $babBody->title, $GLOBALS['babUrlScript']."?tg=calweek&calid=".$calid."&date=".$date);
			$babBody->addItemMenu("free", bab_translate("Availability"), $GLOBALS['babUrlScript']."?tg=calweek&idx=free&calid=".$calid."&date=".$date);
			$babBody->addItemMenu("options", bab_translate("Options"), $GLOBALS['babUrlScript']."?tg=calopt&idx=options&urla=".urlencode($GLOBALS['babUrlScript']."?tg=calweek&calid=".$calid."&date=".$date));
			}
		break;
	}
$babBody->setCurrentItemMenu($idx);
?>