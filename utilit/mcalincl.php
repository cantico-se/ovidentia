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
			return $this->objcals[$idcal]->getNextEvent($startdate, $enddate, &$arr);
			}
		else
			{
			return false;
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
			$res = $babDB->db_query("select ceo.*, ce.* from ".BAB_CAL_EVENTS_OWNERS_TBL." ceo left join ".BAB_CAL_EVENTS_TBL." ce on ceo.id_event=ce.id where ceo.id_cal='".$calid."' and ce.start_date <= '".$enddate."' and  ce.end_date >= '".$startdate."' order by ce.start_date asc");
			//echo "select ceo.*, ce.* from ".BAB_CAL_EVENTS_OWNERS_TBL." ceo left join ".BAB_CAL_EVENTS_TBL." ce on ceo.id_event=ce.id where ceo.id_cal='".$calid."' and ce.start_date <= '".$enddate."' and  ce.end_date >= '".$startdate."' order by ce.start_date asc";
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
}

class cal_wmdbaseCls
{
	function cal_wmdbaseCls($tg, $idx, $calids, $date)
	{
		$this->idcals = explode(",", $calids);
		$rr = explode(',', $date);
		$this->year = $rr[0];
		$this->month = $rr[1];
		$this->day = $rr[2];

		$this->commonurl = $GLOBALS['babUrlScript']."?tg=".$tg."&idx=".$idx."&calid=".$calids;

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
				$this->dayurl = $GLOBALS['babUrlScript']."?tg=calday&calid=".$calids."&date=".$date;
				$this->weekurl = $GLOBALS['babUrlScript']."?tg=calweek&calid=".$calids."&date=".$date;
				break;
			case "calday":
				$this->monthurl = $GLOBALS['babUrlScript']."?tg=calmonth&calid=".$calids."&date=".$date;
				$this->dayurl = "";
				$this->weekurl = $GLOBALS['babUrlScript']."?tg=calweek&calid=".$calids."&date=".$date;
				break;
			case "calweek":
				$this->monthurl = $GLOBALS['babUrlScript']."?tg=calmonth&calid=".$calids."&date=".$date;
				$this->dayurl = $GLOBALS['babUrlScript']."?tg=calday&calid=".$calids."&date=".$date;
				$this->weekurl = "";
				break;
		}


		$this->monthurlname = bab_translate("Month");
		$this->weekurlname = bab_translate("Week");
		$this->dayurlname = bab_translate("Day");
		$this->gotodayname = bab_translate("Go to Today");
		$this->attendeestxt = bab_translate("Attendees");
		$this->t_calendarchoice = bab_translate('Calendars');

		$backurl = urlencode(urlencode($GLOBALS['babUrlScript']."?tg=".$tg."&date=".$date."&calid="));
		$this->calendarchoiceurl = $GLOBALS['babUrlScript']."?tg=calopt&idx=pop_calendarchoice&calid=".$calids."&date=".$date."&backurl=".$backurl;

	}
}
?>