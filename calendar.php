<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
function isCalUpdate($mcals)
{
global $BAB_SESS_USERID;
$db = $GLOBALS['babDB'];
for($i = 0; $i < count($mcals); $i++)
	{
	$res = $db->db_query("select * from ".BAB_CALENDAR_TBL." where id='".$mcals[$i]."'");
	$arr = $db->db_fetch_array($res);
	switch($arr['type'])
		{
		case 1:
			if( $arr['owner'] == $BAB_SESS_USERID)
			{
				return 1;
			}
			else
				{
				$req = "select * from ".BAB_CALACCESS_USERS_TBL." where id_cal='".$arr['id']."' and id_user='".$BAB_SESS_USERID."'";
				$res = $db->db_query($req);
				if( $res && $db->db_num_rows($res) > 0)
					{
					$row = $db->db_fetch_array($res);
					if($row['bwrite'] == "1" || $row['bwrite'] == "2")
						return 1;
					}
				}
			break;
		case 2:
			if( bab_isUserGroupManager($arr['owner']))
				return 1;
			break;
		case 3:
			return 1;
			break;
		default:
			break;	
		}
	}
	return 0;
}
function getAvailableUsersCalendars()
{
	global $BAB_SESS_USERID,$BAB_SESS_USER;
	$tab = array();
	$rr = array();
	$rr['name'] = $BAB_SESS_USER;
	$rr['idcal'] = bab_getCalendarId($BAB_SESS_USERID, 1);
	array_push($tab, $rr);

	$db = $GLOBALS['babDB'];
	$req = "select * from ".BAB_CALACCESS_USERS_TBL." where id_user='".$BAB_SESS_USERID."'";
	$res = $db->db_query($req);
	while($row = $db->db_fetch_array($res))
	{
		$rr['name'] = bab_getCalendarOwnerName($row['id_cal'], 1);
		$rr['idcal'] = $row['id_cal'];
		array_push($tab, $rr);
	}
	return $tab;
}	


function getAvailableGroupsCalendars()
{
	global $BAB_SESS_USERID,$BAB_SESS_USER;
	$tab = array();
	$rr = array();

	$db = $GLOBALS['babDB'];
	$req = "select ".BAB_GROUPS_TBL.".name, ".BAB_GROUPS_TBL.".id from ".BAB_GROUPS_TBL." join ".BAB_USERS_GROUPS_TBL." where id_object='".$BAB_SESS_USERID."' and ".BAB_GROUPS_TBL.".id=".BAB_USERS_GROUPS_TBL.".id_group";
	$resgroups = $db->db_query($req);
	while($arr = $db->db_fetch_array($resgroups))
	{
		$res = $db->db_query("select * from ".BAB_CALENDAR_TBL." where owner='".$arr['id']."' and type='2' and actif='Y'");
		while( $arr2 = $db->db_fetch_array($res))
		{
			$rr['name'] = $arr['name'];
			$rr['idcal'] = $arr2['id'];
			array_push($tab, $rr);
		}

	}

	return $tab;
}


function getAvailableResourcesCalendars()
{
	global $BAB_SESS_USERID,$BAB_SESS_USER;
	$tab = array();
	$rr = array();

	$db = $GLOBALS['babDB'];

	$req = "select ".BAB_GROUPS_TBL.".id from ".BAB_GROUPS_TBL." join ".BAB_USERS_GROUPS_TBL." where id_object='".$BAB_SESS_USERID."' and ".BAB_GROUPS_TBL.".id=".BAB_USERS_GROUPS_TBL.".id_group";
	$resgroups = $db->db_query($req);

	$req = "select * from ".BAB_RESOURCESCAL_TBL." where id_group='1'";
	while($arr = $db->db_fetch_array($resgroups))
	{
		$req .= " or id_group='".$arr['id']."'"; 
	}
	$res = $db->db_query($req);
	while($arr = $db->db_fetch_array($res))
	{
		$rr['name'] = $arr['name'];
		$rr['idcal'] = bab_getCalendarId($arr['id'], 3);
		array_push($tab, $rr);
	}
	return $tab;
}


function getEventsResult($calid, $day, $month, $year)
{
	$db = $GLOBALS['babDB'];
	$mktime = mktime(0,0,0,$month, $day,$year);
	$daymin = sprintf("%04d-%02d-%02d", date("Y", $mktime), Date("n", $mktime), Date("j", $mktime));
	$daymax = sprintf("%04d-%02d-%02d", date("Y", $mktime), Date("n", $mktime), Date("j", $mktime));
	$req = "select * from ".BAB_CAL_EVENTS_TBL." where id_cal='".$calid."' and ('$daymin' between start_date and end_date or '$daymax' between start_date and end_date";
	$req .= " or start_date between '$daymin' and '$daymax' or end_date between '$daymin' and '$daymax') order by start_date, start_time asc";
	return $db->db_query($req);
}


function calendarForm($calid, $day, $month, $year, $view)
{
	global $babBody;

	class tempform
		{
		function tempform($calid, $day, $month, $year, $view)
			{
			$this->usrcalendarstxt = bab_translate("Users calendars");
			$this->grpcalendarstxt = bab_translate("Groups calendars");
			$this->rescalendarstxt = bab_translate("Resources calendars");
			$this->mcals = explode(",", $calid);
			$this->usrcalendars = getAvailableUsersCalendars();
			$this->grpcalendars = getAvailableGroupsCalendars();
			$this->rescalendars = getAvailableResourcesCalendars();
			$this->maxcals = max(count($this->usrcalendars), count($this->grpcalendars), count($this->rescalendars));
			$this->viewthis = bab_translate("View those calendars");
			$this->viewcurl = $GLOBALS['babUrlScript']."?tg=calendar&idx=viewc&calid=".$this->mcals[0];
			$this->viewctxt = bab_translate("View categories");
			$this->day = $day;
			$this->month = $month;
			$this->year = $year;
			$this->view = $view;
			}

		function getnextrow()
			{
			static $i = 0;
			if( $i < $this->maxcals)
				{
				$this->busrcal = false;
				$this->brescal = false;
				$this->bgrpcal = false;
				if( $i < count($this->usrcalendars))
					{
					$this->usrcalname = $this->usrcalendars[$i]['name'];
					$this->usrcalid = $this->usrcalendars[$i]['idcal'];
					if( count($this->mcals) > 0 && in_array($this->usrcalid, $this->mcals))
						$this->usrsel = "checked";
					else
						$this->usrsel = "";
					$this->busrcal = true;
					}
				if( $i < count($this->grpcalendars))
					{
					$this->grpcalname = $this->grpcalendars[$i]['name'];
					$this->grpcalid = $this->grpcalendars[$i]['idcal'];
					if( count($this->mcals) > 0 && in_array($this->grpcalid, $this->mcals))
						$this->grpsel = "checked";
					else
						$this->grpsel = "";
					$this->bgrpcal = true;
					}
				if( $i < count($this->rescalendars))
					{
					$this->rescalname = $this->rescalendars[$i]['name'];
					$this->rescalid = $this->rescalendars[$i]['idcal'];
					if( count($this->mcals) > 0 && in_array($this->rescalid, $this->mcals))
						$this->ressel = "checked";
					else
						$this->ressel = "";
					$this->brescal = true;
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

		}

	$temp = new tempform($calid, $day, $month, $year, $view);
	$babBody->babecho(bab_printTemplate($temp,"calendar.html", "calform"));
	
}

function calendarMonth($calid, $day, $month, $year)
{
	global $babBody;

	class temp
		{
		var $w;
		var $totaldays;
		var $day;
		var $daynumberurl;
		var $daynumbername;
		var $dayname;
		var $month;
		var $year;
		var $previuousyear;
		var $previuousmonth;
		var $nextyear;
		var $nextmonth;
		var $monthname;
		var $monthurl;
		var $weekurl;
		var $dayurl;
		var $gotodayname;
		var $gotodayurl;
		var $calid;

		var $db;
		var $nbevent;
		var $new;
		var $maxevent;
		var $plus;
		var $babCalendarStartDay;
		var $babCalendarUsebgColor;
	
		function temp($calid, $day, $month, $year)
			{
			global $BAB_SESS_USERID, $babMonths;
			$this->db = $GLOBALS['babDB'];
			$this->mcals = explode(",", $calid);
			$this->view = "viewm";
			$this->w = 0;
			$this->nbevent = 0;
			$this->totaldays = date("t", mktime(0,0,0,$month,1,$year));
			$this->day = $day;
			$this->month = $month;
			$this->year = $year;
			$this->calid = $calid;
			$this->new = bab_translate("New");
			$this->maxevent = 6;
			$this->plus = "";
			$req = "select * from ".BAB_CALOPTIONS_TBL." where id_user='".$BAB_SESS_USERID."'";
			$res = $this->db->db_query($req);
			$this->ampm = false;
			$this->babCalendarStartDay = 0;
			$this->babCalendarUsebgColor = "Y";
			if( $res && $this->db->db_num_rows($res) > 0)
				{
				$arr = $this->db->db_fetch_array($res);
				$this->babCalendarStartDay = $arr['startday'];
				$this->babCalendarUsebgColor = $arr['usebgcolor'];
				if( $arr['ampm'] == "Y")
					$this->ampm = true;
				}

			$this->bowner = isCalUpdate($this->mcals);

			$this->previousmonth = $GLOBALS['babUrlScript']."?tg=calendar&idx=viewm&day=".$day;
			$this->previousmonth .= "&month=".date("n", mktime( 0,0,0, $month-1, 1, $year));
			$this->previousmonth .= "&year=".date("Y", mktime( 0,0,0, $month-1, 1, $year)). "&calid=".$this->calid;
			$this->nextmonth = $GLOBALS['babUrlScript']."?tg=calendar&idx=viewm&day=".$day;
			$this->nextmonth .= "&month=". date("n", mktime( 0,0,0, $month+1, 1, $year));
			$this->nextmonth .= "&year=". date("Y", mktime( 0,0,0, $month+1, 1, $year)). "&calid=".$this->calid;

			$this->previousyear = $GLOBALS['babUrlScript']."?tg=calendar&idx=viewm&day=".$day;
			$this->previousyear .= "&month=".date("n", mktime( 0,0,0, $month, 1, $year-1));
			$this->previousyear .= "&year=".date("Y", mktime( 0,0,0, $month, 1, $year-1)). "&calid=".$this->calid;
			$this->nextyear = $GLOBALS['babUrlScript']."?tg=calendar&idx=viewm&day=".$day;
			$this->nextyear .= "&month=". date("n", mktime( 0,0,0, $month, 1, $year+1));
			$this->nextyear .= "&year=". date("Y", mktime( 0,0,0, $month, 1, $year+1)). "&calid=".$this->calid;

			$this->monthurl = "";
			$this->weekurl = $GLOBALS['babUrlScript']."?tg=calendar&idx=viewq&day=".$day."&month=".$month."&year=".$year. "&calid=".$this->calid;
			$this->dayurl = $GLOBALS['babUrlScript']."?tg=calendar&idx=viewd&day=".$day."&month=".$month."&year=".$year. "&calid=".$this->calid;

			$this->monthurlname = bab_translate("Month");
			$this->weekurlname = bab_translate("Week");
			$this->dayurlname = bab_translate("Day");
			$this->gotodayname = bab_translate("Go to Today");
			$this->gotodayurl = $GLOBALS['babUrlScript']."?tg=calendar&idx=viewm&day=".date("j")."&month=".date("n")."&year=".date("Y"). "&calid=".$this->calid;

			$this->monthname = $babMonths[date("n", mktime( 0,0,0, $month, 1, $year))]. "  ". $year;
			$this->firstday = date("w", mktime(0,0,0,$this->month,1,$this->year));
			}

		function getdayname()
			{
			global $babDays;
			static $i = 0;
			if( $i < 7)
				{
				$a = $i + $this->babCalendarStartDay;
				if( $a > 6)
					$a -=  7;
				$this->dayname = $babDays[$a];
				$i++;
				return true;
				}
			else
				return false;
			}

		function getweek()
			{
			if( $this->w < 6)
				{
				$this->w++;
				return true;
				}
			else
				{
				return false;
				}
			}

		function getday()
			{
			global $BAB_SESS_USERID;
			static $d = 0;
			static $total = 0;
			if( $d < 7)
				{
				$this->currentmonth = 1;
				$this->currentday = 0;
				$this->nbevent = 0;
				$this->countevent = 0;
				$this->countgrpevent = 0;

				$b = $this->firstday - $this->babCalendarStartDay;
				if( $b < 0)
					$b += 7;

				$this->mday = (7 * ($this->w-1)) + $d - $b +1 ;
				if( $this->mday <= 0 || $this->mday > $this->totaldays)
					$this->currentmonth = 0;
				$mktime = mktime(0,0,0,$this->month, $this->mday,$this->year);
				$dday = date("j", $mktime);
				if( $dday == date("j", mktime()) && $this->month == date("n", mktime()) && $this->year ==  date("Y", mktime()))
					{
					$this->currentday = 1;
					}
				$this->daynumbername = $dday;
				$this->daynumberurl = $GLOBALS['babUrlScript']."?tg=calendar&idx=viewd&day=".$dday."&month=".date("n", $mktime). "&year=".date("Y", $mktime). "&calid=".$this->calid;
				$this->neweventurl = $GLOBALS['babUrlScript']."?tg=event&idx=newevent&day=".$dday."&month=".date("n", $mktime). "&year=".date("Y", $mktime)."&calid=".$this->calid."&view=viewm";
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
			static $k=0;
			if( $k < count($this->mcals))
				{
				$this->fullname = htmlentities(bab_getCalendarOwnerName($this->mcals[$k], 0));
				$this->fullnameten = htmlentities(substr($this->fullname, 0, 16));
				//$this->fullnameten = htmlentities($this->fullname);
				$this->resevent = getEventsResult($this->mcals[$k], $this->mday, $this->month, $this->year);
				$this->countevent = $this->db->db_num_rows($this->resevent);
				$k++;
				return true;
				}
			else
				{
				$k = 0;
				return false;
				}
			}

		function getevent()
			{
			static $k=0;
			if( $k < $this->countevent)
				{
				$this->bgcolor = "";
				$this->title = "";
				$this->titleten = "";
				$this->hourten = "";
				$arr = $this->db->db_fetch_array($this->resevent);
				if( $this->ampm )
					$this->title = htmlentities(bab_toAmPm(substr($arr['start_time'], 0 ,5)). " " . bab_toAmPm(substr($arr['end_time'], 0 ,5)). " " .$arr['title']);
				else
					$this->title = htmlentities(substr($arr['start_time'], 0 ,5). " " . substr($arr['end_time'], 0 ,5). " " .$arr['title']);
				if( $this->ampm )
					$this->hourten = htmlentities(bab_toAmPm(substr($arr['start_time'], 0 ,5)));
				else
					$this->hourten = htmlentities(substr($arr['start_time'], 0 ,5));
				$this->titleten = htmlentities(substr($arr['title'], 0, 10)) ;
				$this->titletenurl = $GLOBALS['babUrlScript']."?tg=event&idx=modify&day=".$this->day."&month=".$this->month."&year=".$this->year. "&calid=".$arr['id_cal']."&evtid=".$arr['id']. "&view=viewm";
				if( $this->babCalendarUsebgColor == "Y")
					{
					$req = "select * from ".BAB_CATEGORIESCAL_TBL." where id='".$arr['id_cat']."'";
					$res = $this->db->db_query($req);
					if( $res && $this->db->db_num_rows($res) > 0)
						{
						$arr = $this->db->db_fetch_array($res);
						$this->bgcolor = $arr['bgcolor'];
						}
					}
				$k++;
				return true;
				}
			else
				{
				$k = 0;
				return false;
				}
			}

		}

	$temp = new temp($calid, $day, $month, $year);
	$babBody->babecho(	bab_printTemplate($temp,"calendar.html", "calmonth"));
	calendarForm($calid, $day, $month, $year, "viewm");
	return $temp->count;

}


function calendarWeek($calid, $day, $month, $year, $caltype, $owner)
{
	global $babBody;

	class temp
		{
		var $w;
		var $totaldays;
		var $day;
		var $dayname;
		var $month;
		var $year;
		var $curday;
		var $previuousyear;
		var $previuousmonth;
		var $previuousweek;
		var $nextmonth;
		var $nextyear;
		var $nextweek;
		var $monthname;
		var $monthurl;
		var $weekurl;
		var $dayurl;
		var $gotodayname;
		var $gotodayurl;
		var $calid;
		var $caltype;
		var $nbevent;
		var $neweventurl;

		var $db;
		var $babCalendarStartDay;
		var $babCalendarUsebgColor;

		
		function temp($calid, $day, $month, $year, $caltype, $owner)
			{
			global $BAB_SESS_USERID;
			$this->mcals = explode(",", $calid);
			$this->db = $GLOBALS['babDB'];
			$this->view = "viewq";
			$this->calid = $calid;
			$this->month = $month;
			$this->year = $year;
			$this->day = $day;
			$req = "select * from ".BAB_CALOPTIONS_TBL." where id_user='".$BAB_SESS_USERID."'";
			$res = $this->db->db_query($req);
			$this->babCalendarStartDay = 0;
			$this->babCalendarUsebgColor = "Y";
			$this->ampm = false;
			if( $res && $this->db->db_num_rows($res) > 0)
				{
				$arr = $this->db->db_fetch_array($res);
				$this->babCalendarStartDay = $arr['startday'];
				$this->babCalendarUsebgColor = $arr['usebgcolor'];
				if( $arr['ampm'] == "Y")
					$this->ampm = true;
				}
			$this->bowner = isCalUpdate($this->mcals);
			$this->curday = date("w", mktime(0,0,0,$month, $day, $year));
			$d = $day - 7;
			if( $d == 0)
				$d = -1;
			$this->previousweek = $GLOBALS['babUrlScript']."?tg=calendar&idx=viewq&day=".date("j", mktime( 0,0,0, $month, $d, $year));
			$this->previousweek .= "&month=".date("n", mktime( 0,0,0, $month, $d, $year));
			$this->previousweek .= "&year=".date("Y", mktime( 0,0,0, $month, $d, $year)). "&calid=".$this->calid;
			$d = $day + 7;
			$this->nextweek = $GLOBALS['babUrlScript']."?tg=calendar&idx=viewq&day=".date("j", mktime( 0,0,0, $month, $d, $year));
			$this->nextweek .= "&month=". date("n", mktime( 0,0,0, $month, $d, $year));
			$this->nextweek .= "&year=". date("Y", mktime( 0,0,0, $month, $d, $year)). "&calid=".$this->calid;

			$this->previousyear = $GLOBALS['babUrlScript']."?tg=calendar&idx=viewq&day=".date("j", mktime( 0,0,0, $month, $day, $year-1));
			$this->previousyear .= "&month=".date("n", mktime( 0,0,0, $month, $day, $year-1));
			$this->previousyear .= "&year=".date("Y", mktime( 0,0,0, $month, $day, $year-1)). "&calid=".$this->calid;
			$this->nextyear = $GLOBALS['babUrlScript']."?tg=calendar&idx=viewq&day=".date("j", mktime( 0,0,0, $month, $day, $year+1));
			$this->nextyear .= "&month=". date("n", mktime( 0,0,0, $month, $day, $year+1));
			$this->nextyear .= "&year=". date("Y", mktime( 0,0,0, $month, $day, $year+1)). "&calid=".$this->calid;

			$this->previousmonth = $GLOBALS['babUrlScript']."?tg=calendar&idx=viewq&day=".date("j", mktime( 0,0,0, $month-1, $day, $year));
			$this->previousmonth .= "&month=".date("n", mktime( 0,0,0, $month-1, $day, $year));
			$this->previousmonth .= "&year=".date("Y", mktime( 0,0,0, $month-1, $day, $year)). "&calid=".$this->calid;
			$this->nextmonth = $GLOBALS['babUrlScript']."?tg=calendar&idx=viewq&day=".date("j", mktime( 0,0,0, $month+1, $day, $year));
			$this->nextmonth .= "&month=". date("n", mktime( 0,0,0, $month+1, $day, $year));
			$this->nextmonth .= "&year=". date("Y", mktime( 0,0,0, $month+1, $day, $year)). "&calid=".$this->calid;

			$this->monthurl = $GLOBALS['babUrlScript']."?tg=calendar&idx=viewm&day=".$day."&month=".$month."&year=".$year. "&calid=".$this->calid;
			$this->weekurl = $GLOBALS['babUrlScript']."?tg=calendar&idx=viewq&day=".$day."&month=".$month."&year=".$year. "&calid=".$this->calid;
			$this->dayurl = $GLOBALS['babUrlScript']."?tg=calendar&idx=viewd&day=".$day."&month=".$month."&year=".$year. "&calid=".$this->calid;
			$this->monthurlname = bab_translate("Month");
			$this->weekurlname = bab_translate("Week");
			$this->dayurlname = bab_translate("Day");
			$this->previous = bab_translate("Previous");
			$this->next = bab_translate("Next");
			$this->new = bab_translate("New");
			$this->gotodayname = bab_translate("Go to Today");
			$this->gotodayurl = $GLOBALS['babUrlScript']."?tg=calendar&idx=viewq&day=".date("j")."&month=".date("n")."&year=".date("Y"). "&calid=".$this->calid;
			}

		function getday()
			{
			global $BAB_SESS_USERID;
			static $i = 0;
			if( $i < 7)
				{
				$this->currentday = 0;
				$this->nbevent = 0;
				$a = $this->curday - $this->babCalendarStartDay;
				if( $a < 0)
					$a += 7;
				$this->mday = $this->day - $a + $i;
				if( $day == date("j", mktime()) && $this->month == date("n", mktime()) && $this->year ==  date("Y", mktime()))
					{
					$this->currentday = 1;
					}
				$this->dayname = bab_strftime(mktime( 0,0,0, $this->month, $this->mday, $this->year), false);
				$this->neweventurl = $GLOBALS['babUrlScript']."?tg=event&idx=newevent&day=".$this->mday."&month=".$this->month. "&year=".$this->year."&calid=".$this->calid."&view=viewq";
				$i++;
				return true;
				}
			else
				return false;
			}

		function getnextcal()
			{
			static $k=0;
			if( $k < count($this->mcals))
				{
				$this->fullname = htmlentities(bab_getCalendarOwnerName($this->mcals[$k], 0));
				$this->fullnameten = htmlentities(substr($this->fullname, 0, 10));
				$this->resevent = getEventsResult($this->mcals[$k], $this->mday, $this->month, $this->year);
				$this->countevent = $this->db->db_num_rows($this->resevent);
				$k++;
				return true;
				}
			else
				{
				$k = 0;
				return false;
				}
			}

		function getevent()
			{
			static $k=0;
			if( $k < $this->countevent)
				{
				$this->bgcolor = "";
				$this->title = "";
				$arr = $this->db->db_fetch_array($this->resevent);
				if( $this->ampm )
					{
					$this->title = htmlentities(bab_toAmPm(substr($arr['start_time'], 0 ,5)). " " . bab_toAmPm(substr($arr['end_time'], 0 ,5)). " " .$arr['title']);
					}
				else
					{
					$this->title = htmlentities(substr($arr['start_time'], 0 ,5). " " . substr($arr['end_time'], 0 ,5). " " .$arr['title']);
					}
				$this->titletenurl = $GLOBALS['babUrlScript']."?tg=event&idx=modify&day=".$this->day."&month=".$this->month."&year=".$this->year. "&calid=".$arr['id_cal']. "&evtid=".$arr['id']. "&view=viewq";
				if( $this->babCalendarUsebgColor == "Y")
					{
					$req = "select * from ".BAB_CATEGORIESCAL_TBL." where id='".$arr['id_cat']."'";
					$res = $this->db->db_query($req);
					if( $res && $this->db->db_num_rows($res) > 0)
						{
						$arr = $this->db->db_fetch_array($res);
						$this->bgcolor = $arr['bgcolor'];
						}
					}
				$k++;
				return true;
				}
			else
				{
				$k = 0;
				return false;
				}
			}

		}

	$temp = new temp($calid, $day, $month, $year, $caltype, $owner);
	$babBody->babecho(	bab_printTemplate($temp,"calendar.html", "calweek"));
	calendarForm($calid, $day, $month, $year, "viewq");
	return $temp->count;

}

function calendarDay($calid, $day, $month, $year, $starttime)
{
	global $babBody;

	class temp
		{
		var $w;
		var $totaldays;
		var $day;
		var $dayname;
		var $month;
		var $year;
		var $curday;
		var $previuousyear;
		var $previuousmonth;
		var $previuousday;
		var $nextmonth;
		var $nextyear;
		var $nextday;
		var $monthname;
		var $monthurl;
		var $weekurl;
		var $dayurl;
		var $gotodayname;
		var $gotodayurl;
		var $calid;
		var $caltype;
		var $nbevent;
		var $db;
		var $curhour;
		var $colspan;
		var $babCalendarUsebgColor;
		var $prevdayurl;
		var $nextdayurl;
		var $prevdaytxt;
		var $nextdaytxt;
	
		function temp($calid, $day, $month, $year, $starttime)
			{
			global $BAB_SESS_USERID;
			$this->prevdaytxt = bab_translate("Previous day");
			$this->nextdaytxt = bab_translate("Next day");
			$this->prevtimetxt = bab_translate("Previous time");
			$this->nexttimetxt = bab_translate("Next time");
			$this->mcals = explode(",", $calid);
			$this->db = $GLOBALS['babDB'];
			$this->colspan ="";
			$this->firsttime ="";
			$this->calid = $calid;
			$req = "select * from ".BAB_CALOPTIONS_TBL." where id_user='".$BAB_SESS_USERID."'";
			$res = $this->db->db_query($req);
			$this->elapstime = 30;
			$this->ampm = false;
			$this->babCalendarUsebgColor = "Y";
			if( $res && $this->db->db_num_rows($res) > 0)
				{
				$arr = $this->db->db_fetch_array($res);
				$this->babCalendarUsebgColor = $arr['usebgcolor'];
				if( isset($arr['elapstime'] ) && $arr['elapstime'] != "" )
					$this->elapstime = $arr['elapstime'];
				if( $arr['ampm'] == "Y")
					$this->ampm = true;
				}

			$this->bowner = isCalUpdate($this->mcals);
			$this->prevdayurl = $GLOBALS['babUrlScript']."?tg=calendar&idx=viewd&day=".date("j", mktime( 0,0,0, $month, $day-1, $year));
			$this->prevdayurl .= "&month=".date("n", mktime( 0,0,0, $month, $day-1, $year));
			$this->prevdayurl .= "&year=".date("Y", mktime( 0,0,0, $month, $day-1, $year)). "&calid=".$this->calid."&start=".$starttime;
			$this->nextdayurl = $GLOBALS['babUrlScript']."?tg=calendar&idx=viewd&day=".date("j", mktime( 0,0,0, $month, $day+1, $year));
			$this->nextdayurl .= "&month=".date("n", mktime( 0,0,0, $month, $day+1, $year));
			$this->nextdayurl .= "&year=".date("Y", mktime( 0,0,0, $month, $day+1, $year)). "&calid=".$this->calid."&start=".$starttime;
			if( $starttime == 1)
				{
				$this->prevtimeurl = $GLOBALS['babUrlScript']."?tg=calendar&idx=viewd&day=".date("j", mktime( 0,0,0, $month, $day-1, $year));
				$this->prevtimeurl .= "&month=".date("n", mktime( 0,0,0, $month, $day-1, $year));
				$this->prevtimeurl .= "&year=".date("Y", mktime( 0,0,0, $month, $day-1, $year)). "&calid=".$this->calid."&start=3";
				$this->nexttimeurl = $GLOBALS['babUrlScript']."?tg=calendar&idx=viewd&day=".$day."&month=".$month."&year=".$year."&calid=".$this->calid."&start=2";
				$this->starttime = 0;
				$this->maxidx = 8*(60/$this->elapstime);
				}
			else if( $starttime == 3)
				{
				$this->nexttimeurl = $GLOBALS['babUrlScript']."?tg=calendar&idx=viewd&day=".date("j", mktime( 0,0,0, $month, $day+1, $year));
				$this->nexttimeurl .= "&month=".date("n", mktime( 0,0,0, $month, $day+1, $year));
				$this->nexttimeurl .= "&year=".date("Y", mktime( 0,0,0, $month, $day+1, $year)). "&calid=".$this->calid."&start=1";
				$this->prevtimeurl = $GLOBALS['babUrlScript']."?tg=calendar&idx=viewd&day=".$day."&month=".$month."&year=".$year."&calid=".$this->calid."&start=2";
				$this->starttime = 19;
				$this->maxidx = 5*(60/$this->elapstime);
				}
			else
				{
				$this->starttime = 8;
				$this->prevtimeurl = $GLOBALS['babUrlScript']."?tg=calendar&idx=viewd&day=".$day."&month=".$month."&year=".$year."&calid=".$this->calid."&start=1";
				$this->nexttimeurl = $GLOBALS['babUrlScript']."?tg=calendar&idx=viewd&day=".$day."&month=".$month."&year=".$year."&calid=".$this->calid."&start=3";
				$this->maxidx = 11*(60/$this->elapstime);
			}

			$this->month = $month;
			$this->year = $year;
			$this->day = $day;
			$this->firsttime = $starttime;
			$this->curhour = $starttime;
			$this->dayname = bab_strftime(mktime( 0,0,0, $this->month, $this->day, $this->year), false);
			$d = $day - 1;
			$this->previousday = $GLOBALS['babUrlScript']."?tg=calendar&idx=viewd&day=".date("j", mktime( 0,0,0, $month, $d, $year));
			$this->previousday .= "&month=".date("n", mktime( 0,0,0, $month, $d, $year));
			$this->previousday .= "&year=".date("Y", mktime( 0,0,0, $month, $d, $year)). "&calid=".$this->calid;
			$d = $day + 1;
			$this->nextday = $GLOBALS['babUrlScript']."?tg=calendar&idx=viewd&day=".date("j", mktime( 0,0,0, $month, $d, $year));
			$this->nextday .= "&month=". date("n", mktime( 0,0,0, $month, $d, $year));
			$this->nextday .= "&year=". date("Y", mktime( 0,0,0, $month, $d, $year)). "&calid=".$this->calid;

			$this->previousmonth = $GLOBALS['babUrlScript']."?tg=calendar&idx=viewd&day=".date("j", mktime( 0,0,0, $month-1, $day, $year));
			$this->previousmonth .= "&month=".date("n", mktime( 0,0,0, $month-1, $day, $year));
			$this->previousmonth .= "&year=".date("Y", mktime( 0,0,0, $month-1, $day, $year)). "&calid=".$this->calid;
			$this->nextmonth = $GLOBALS['babUrlScript']."?tg=calendar&idx=viewd&day=".date("j", mktime( 0,0,0, $month+1, $day, $year));
			$this->nextmonth .= "&month=". date("n", mktime( 0,0,0, $month+1, $day, $year));
			$this->nextmonth .= "&year=". date("Y", mktime( 0,0,0, $month+1, $day, $year)). "&calid=".$this->calid;

			$this->previousyear = $GLOBALS['babUrlScript']."?tg=calendar&idx=viewd&day=".date("j", mktime( 0,0,0, $month, $day, $year-1));
			$this->previousyear .= "&month=".date("n", mktime( 0,0,0, $month, $day, $year-1));
			$this->previousyear .= "&year=".date("Y", mktime( 0,0,0, $month, $day, $year-1)). "&calid=".$this->calid;
			$this->nextyear = $GLOBALS['babUrlScript']."?tg=calendar&idx=viewd&day=".date("j", mktime( 0,0,0, $month, $day, $year+1));
			$this->nextyear .= "&month=". date("n", mktime( 0,0,0, $month, $day, $year+1));
			$this->nextyear .= "&year=". date("Y", mktime( 0,0,0, $month, $day, $year+1)). "&calid=".$this->calid;

			$this->monthurl = $GLOBALS['babUrlScript']."?tg=calendar&idx=viewm&day=".$day."&month=".$month."&year=".$year. "&calid=".$this->calid;
			$this->weekurl = $GLOBALS['babUrlScript']."?tg=calendar&idx=viewq&day=".$day."&month=".$month."&year=".$year. "&calid=".$this->calid;
			$this->dayurl = $GLOBALS['babUrlScript']."?tg=calendar&idx=viewd&day=".$day."&month=".$month."&year=".$year. "&calid=".$this->calid;
			$this->monthurlname = bab_translate("Month");
			$this->weekurlname = bab_translate("Week");
			$this->dayurlname = bab_translate("Day");
			$this->previous = bab_translate("Previous");
			$this->next = bab_translate("Next");
			$this->gotodayname = bab_translate("Go to Today");
			$this->gotodayurl = $GLOBALS['babUrlScript']."?tg=calendar&idx=viewd&day=".date("j")."&month=".date("n")."&year=".date("Y"). "&calid=".$this->calid;
			$this->alternate = false;
			$this->evtarr = array();
			}

		function getnexthour()
			{
			static $i = 0;
			if( $i < $this->maxidx)
				{
				$this->idxhour = $i;
				$this->curhour = $this->starttime * 60 + $i * $this->elapstime;
				$st = sprintf("%02d:%02d", $this->curhour/60, $this->curhour%60);
				if( $this->ampm)
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
					if( $this->curhour%60 == 0)
						$this->hour = sprintf("%02d<sup>%02d</sup>", $this->curhour/60, $this->curhour%60);
					else
						$this->hour = sprintf("__<sup>%02d</sup>", $this->curhour%60);
					}
				$this->hoururl = $GLOBALS['babUrlScript']."?tg=event&idx=newevent&day=".$this->day."&month=".$this->month. "&year=".$this->year."&calid=".$this->calid."&view=viewd&st=".$st;
				if( $i % 2)
					{
					$this->altbgcolor = true;
					}
				else
					{
					$this->altbgcolor = false;
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

		function getnextcalname()
			{
			static $k=0;
			if( $k < count($this->mcals))
				{
				$this->fullname = htmlentities(bab_getCalendarOwnerName($this->mcals[$k], 0));
				$this->fullnameten = htmlentities(substr($this->fullname, 0, 10));
				$resevent = getEventsResult($this->mcals[$k], $this->day, $this->month, $this->year);
				$tab = array();
				for($i =0; $i < $this->maxidx; $i++)
					$tab[$i] = 0;
				$tabevents = array();
				$tabevents[0] = $tab;
				while( $arr = $this->db->db_fetch_array($resevent) )
					{
					$tab = array();
					for($i =0; $i < $this->maxidx; $i++)
						{
						$hourmin = $this->starttime * 60 + $i * $this->elapstime;
						$hourmax = $hourmin  + $this->elapstime;
						$a = substr($arr['start_time'], 0,2) * 60 + substr($arr['start_time'], 3,2);
						$b = substr($arr['end_time'], 0,2) * 60 + substr($arr['end_time'], 3,2);
						if( $b < $hourmin || $a >= $hourmax)
							$tab[$i] = 0;
						else
							$tab[$i] = $arr['id'];
						}

					if( count($tabevents) == 0 )
						$tabevents[0] = $tab;
					else
						{
						$new = false;
						for( $j = 0; $j < count($tabevents); $j++)
							{
							for($n=0; $n < count($tabevents[$j]); $n++)
								if( $tab[$n] != 0 && $tabevents[$j][$n] !=0)
									{
									break;
									}
							if( $n >= count($tabevents[$j]) )
								{
								$new = true;
								break;
								}
							}
						
						if( !$new )
							{
							$tabevents[count($tabevents)] = $tab;
							}
						else
							{
							for($n=0; $n < count($tab); $n++)
								if( $tab[$n] != 0 )
									$tabevents[$j][$n] = $tab[$n]; 
							}
						}
					}
				$this->evtarr[$k] = $tabevents;
				$this->colspan = count($tabevents);
				$k++;
				$this->alternate = !$this->alternate;
				return true;
				}
			else
				{
				$k = 0;
				$this->alternate = false;
				return false;
				}
			}

		function getnextcal()
			{
			static $k=0;
			if( $k < count($this->mcals))
				{
				$this->idxmcals = $k;
				$this->alternate = !$this->alternate;
				$k++;
				return true;
				}
			else
				{
				$k = 0;
				$this->alternate = false;
				return false;
				}
			}

		function getnextevent()
			{
			static $k = 0;
			if( $k < count($this->evtarr[$this->idxmcals]))
				{
				$this->bgcolor = "white";
				$this->notempty = 0;
				if( $this->evtarr[$this->idxmcals][$k][$this->idxhour] == 0)
					{
					$this->bgcolor = "";
					$this->titleten = "";
					$this->title = "";
					$this->notempty = 0;
					}
				else
					{
					$this->notempty = 1;
					$arr = $this->db->db_fetch_array($this->db->db_query("select * from ".BAB_CAL_EVENTS_TBL." where id='".$this->evtarr[$this->idxmcals][$k][$this->idxhour]."'"));

					if( $this->ampm)
						$this->title = htmlentities(bab_toAmPm(substr($arr['start_time'], 0 ,5)). " " . bab_toAmPm(substr($arr['end_time'], 0 ,5)). " " .$arr['title']);
					else
						$this->title = htmlentities(substr($arr['start_time'], 0 ,5). " " . substr($arr['end_time'], 0 ,5). " " .$arr['title']);
					$this->titleten = htmlentities($arr['title']);
					$this->titletenurl = $GLOBALS['babUrlScript']."?tg=event&idx=modify&day=".$this->day."&month=".$this->month."&year=".$this->year. "&calid=".$arr['id_cal']. "&evtid=".$arr['id']. "&view=viewd";

					if( $this->babCalendarUsebgColor == "Y")
						{
						$req = "select * from ".BAB_CATEGORIESCAL_TBL." where id='".$arr['id_cat']."'";
						$res = $this->db->db_query($req);
						if( $res && $this->db->db_num_rows($res) > 0)
							{
							$arr = $this->db->db_fetch_array($res);
							$this->bgcolor = $arr['bgcolor'];
							}
						}
					}
				$k++;
				return true;
				}
			else
				{
				$k = 0;
				return false;
				}
			}

		}


	$temp = new temp($calid, $day, $month, $year, $starttime);
	$babBody->babecho(	bab_printTemplate($temp,"calendar.html", "calday"));
	calendarForm($calid, $day, $month, $year, "viewd");
	return $temp->count;

}


function categoriesList($calid)
	{
	global $babBody, $BAB_SESS_USERID;
	class temp2
		{
		var $description;
		var $bgcolor;
				
		var $db;
		var $arr = array();
		var $countcal;
		var $rescat;

		function temp2($calendarid)
			{
			global $BAB_SESS_USERID;
			$this->description = bab_translate("Categories");
			$this->calid = $calendarid;
			$this->caltype = bab_getCalendarType($calendarid);
			$this->db = $GLOBALS['babDB'];
			switch( $this->caltype)
				{
				case 1: // user
					$req = "select * from ".BAB_USERS_GROUPS_TBL." join ".BAB_GROUPS_TBL." where id_object=$BAB_SESS_USERID and ".BAB_GROUPS_TBL.".id=".BAB_USERS_GROUPS_TBL.".id_group";
					$resgroups = $this->db->db_query($req);
					if( $resgroups )
						{
						$countgroups = $this->db->db_num_rows($resgroups); 
						}

					$req2 = "select * from ".BAB_CATEGORIESCAL_TBL." where id_group='1'";
					if( $countgroups > 0)
						{
						for( $i = 0; $i < $countgroups; $i++)
							{
							$arr = $this->db->db_fetch_array($resgroups);
							$req2 .= " or id_group='".$arr['id']."'"; 
							}
						$this->db->db_data_seek($resgroups, 0);
						}
					$this->rescat = $this->db->db_query($req2);
					$this->countcat = $this->db->db_num_rows($this->rescat); 
					break;
				case 2: // group
					$req = "select * from ".BAB_CALENDAR_TBL." where id='".$calendarid."'";
					$res = $this->db->db_query($req);
					$arr = $this->db->db_fetch_array($res);
					$req = "select * from ".BAB_CATEGORIESCAL_TBL." where id_group='1' or id_group='".$arr['owner']."'";
					$this->rescat = $this->db->db_query($req);
					$this->countcat = $this->db->db_num_rows($this->rescat); 
					break;
				case 3: // resource
				default:
					$this->bcategory = 0;
					$this->countcat = 0;
					break;
				}

			}
			
		function getnextcat()
			{
			static $i = 0;
			if( $i < $this->countcat)
				{
				$this->arr = $this->db->db_fetch_array($this->rescat);
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

	$temp = new temp2($calid);
	return bab_printTemplate($temp, "calendar.html", "categorieslist");
	}

/* main */
if(!isset($idx))
	{
	$idx = "viewm";
	}

if( isset($viewcal) && $viewcal == "view")
{
	if( !isset($usrcals))
		$usrcals = array();
	if( !isset($grpcals))
		$grpcals = array();
	if( !isset($rescals))
		$rescals = array();
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=calendar&idx=".$idx."&calid=".implode(",", array_merge($usrcals, $grpcals, $rescals))."&day=".$day."&month=".$month."&year=".$year."&start=".$start);
}

if( empty($month))
	$month = Date("n");

if( empty($year))
	$year = Date("Y");

if( empty($day))
	$day = Date("j");

if( !isset($calid) )
	$calid = bab_getCalendarId($BAB_SESS_USERID, 1);

switch($idx)
	{

	case "viewc":
		echo categoriesList($calid);
		exit;
	case "viewd":
		if( !bab_isCalendarAccessValid($calid) )
			$babBody->title = bab_translate("Acces denied");
		else
			{
			$babBody->title = bab_translate("Calendar");
			calendarDay($calid, $day, $month, $year, $start);
			}
		break;
	case "viewq":
		if( !bab_isCalendarAccessValid($calid) )
			$babBody->title = bab_translate("Acces denied");
		else
			{
			$babBody->title = bab_translate("Calendar");
			calendarWeek($calid, $day, $month, $year, bab_getCalendarType($calid), bab_getCalendarOwner($calid));
			}
		break;
	default:
	case "viewm":
		if( !bab_isCalendarAccessValid($calid) )
			$babBody->title = bab_translate("Acces denied");
		else
			{
			$babBody->title = bab_translate("Calendar");
			calendarMonth($calid, $day, $month, $year, bab_getCalendarType($calid), bab_getCalendarOwner($calid));
			}
		break;
	}
$babBody->setCurrentItemMenu($idx);
?>