<?php

function getAvailableCalendars()
{
	global $BAB_SESS_USERID,$BAB_SESS_USER;
	$tab = array();
	$rr = array();
	$rr[name] = $BAB_SESS_USER;
	$rr[idcal] = getCalendarId($BAB_SESS_USERID, 1);
	array_push($tab, $rr);

	$db = new db_mysql();
	$req = "select * from users_groups join groups where id_object=$BAB_SESS_USERID and groups.id=users_groups.id_group";
	$resgroups = $db->db_query($req);
	if( $resgroups )
		{
		$countgroups = $db->db_num_rows($resgroups); 
		}

	$req = "select * from resourcescal where id_group='1'";
	if( $countgroups > 0)
		{
		for( $i = 0; $i < $countgroups; $i++)
			{
			$arr = $db->db_fetch_array($resgroups);
			$rr[name] = $arr[name];
			$rr[idcal] = getCalendarId($arr[id], 2);
			array_push($tab, $rr);
			$req .= " or id_group='".$arr[id]."'"; 
			}
		$db->db_data_seek($resgroups, 0);
		}
	$resres = $db->db_query($req);
	$countres = $db->db_num_rows($resres);
	for( $i = 0; $i < $countres; $i++)
		{
		$arr = $db->db_fetch_array($resres);
		$rr[name] = $arr[name];
		$rr[idcal] = getCalendarId($arr[id], 3);
		array_push($tab, $rr);
		$req .= " or id_group='".$arr[id]."'"; 
		}
	return $tab;
}


function getEventsResult($calid, $day, $month, $year)
{
	$db = new db_mysql();
	$mktime = mktime(0,0,0,$month, $day,$year);
	$daymin = sprintf("%04d-%02d-%02d", date("Y", $mktime), Date("n", $mktime), Date("j", $mktime));
	$daymax = sprintf("%04d-%02d-%02d", date("Y", $mktime), Date("n", $mktime), Date("j", $mktime));
	$req = "select * from cal_events where id_cal='".$calid."' and ('$daymin' between start_date and end_date or '$daymax' between start_date and end_date";
	$req .= " or start_date between '$daymin' and '$daymax' or end_date between '$daymin' and '$daymax')";
	return $this->resevent = $db->db_query($req);
}

function calendarMonth($calid, $day, $month, $year)
{
	global $body;

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
	
		function temp($calid, $day, $month, $year)
			{
			global $babMonths;
			$this->db = new db_mysql();
			$this->view = "viewm";
			$this->w = 0;
			$this->nbevent = 0;
			$this->totaldays = date("t", mktime(0,0,0,$month,1,$year));
			$this->day = $day;
			$this->month = $month;
			$this->year = $year;
			$this->calid = $calid;
			$this->caltype = getCalendarType($calid);
			$this->viewthis = babTranslate("View this calendar");

			$this->previousmonth = $GLOBALS[babUrl]."index.php?tg=calendar&idx=viewm&day=".$day;
			$this->previousmonth .= "&month=".date("n", mktime( 0,0,0, $month-1, 1, $year));
			$this->previousmonth .= "&year=".date("Y", mktime( 0,0,0, $month-1, 1, $year)). "&calid=".$this->calid;
			$this->nextmonth = $GLOBALS[babUrl]."index.php?tg=calendar&idx=viewm&day=".$day;
			$this->nextmonth .= "&month=". date("n", mktime( 0,0,0, $month+1, 1, $year));
			$this->nextmonth .= "&year=". date("Y", mktime( 0,0,0, $month+1, 1, $year)). "&calid=".$this->calid;

			$this->previousyear = $GLOBALS[babUrl]."index.php?tg=calendar&idx=viewm&day=".$day;
			$this->previousyear .= "&month=".date("n", mktime( 0,0,0, $month, 1, $year-1));
			$this->previousyear .= "&year=".date("Y", mktime( 0,0,0, $month, 1, $year-1)). "&calid=".$this->calid;
			$this->nextyear = $GLOBALS[babUrl]."index.php?tg=calendar&idx=viewm&day=".$day;
			$this->nextyear .= "&month=". date("n", mktime( 0,0,0, $month, 1, $year+1));
			$this->nextyear .= "&year=". date("Y", mktime( 0,0,0, $month, 1, $year+1)). "&calid=".$this->calid;

			$this->monthurl = "";
			$this->weekurl = $GLOBALS[babUrl]."index.php?tg=calendar&idx=viewq&day=".$day."&month=".$month."&year=".$year. "&calid=".$this->calid;
			$this->dayurl = $GLOBALS[babUrl]."index.php?tg=calendar&idx=viewd&day=".$day."&month=".$month."&year=".$year. "&calid=".$this->calid;

			$this->monthurlname = babTranslate("Month");
			$this->weekurlname = babTranslate("Week");
			$this->dayurlname = babTranslate("Day");
			$this->gotodayname = babTranslate("Go to Today");
			$this->gotodayurl = $GLOBALS[babUrl]."index.php?tg=calendar&idx=viewm&day=".date("j")."&month=".date("n")."&year=".date("Y"). "&calid=".$this->calid;

			$this->monthname = $babMonths[date("n", mktime( 0,0,0, $month, 1, $year))]. "  ". $year;
			$this->firstday = date("w", mktime(0,0,0,$this->month,1,$this->year));
			$this->calendars = getAvailableCalendars();
			}

		function getdayname()
			{
			global $babMonths, $babDays;
			static $i = 0;
			if( $i < 7)
				{
				$this->dayname = $babDays[$i];
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
				$day = (7 * ($this->w-1)) + $d - $this->firstday +1 ;
				if( $day <= 0 || $day > $this->totaldays)
					$this->currentmonth = 0;
				$mktime = mktime(0,0,0,$this->month, $day,$this->year);
				$dday = date("j", $mktime);
				if( $dday == date("j", mktime()) && $this->month == date("n", mktime()) && $this->year ==  date("Y", mktime()))
					{
					$this->currentday = 1;
					}
				$this->daynumbername = $dday;
				$this->daynumberurl = $GLOBALS[babUrl]."index.php?tg=calendar&idx=viewd&day=".$dday."&month=".date("n", $mktime). "&year=".date("Y", $mktime). "&calid=".$this->calid;
				$this->neweventurl = $GLOBALS[babUrl]."index.php?tg=event&idx=newevent&day=".$dday."&month=".date("n", $mktime). "&year=".date("Y", $mktime)."&calid=".$this->calid."&view=viewm";
				$this->resevent = getEventsResult($this->calid, $day, $this->month, $this->year);
				$this->countevent = $this->db->db_num_rows($this->resevent);
				$this->nbevent += $this->countevent;
				if( $this->caltype == 1)
					{
					$idcal = getCalendarId(getPrimaryGroupId($BAB_SESS_USERID), 2);
					$this->resgrpevent = getEventsResult($idcal, $day, $this->month, $this->year);
					$this->countgrpevent = $this->db->db_num_rows($this->resgrpevent);
					//$this->nbevent += $this->countgrpevent;
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
			static $k=0;
			if( $k < count($this->calendars))
				{
				if( $this->calid == $this->calendars[$k][idcal])
					$this->selected = "selected";
				else
					$this->selected = "";
				$this->vcalid = $this->calendars[$k][idcal];
				$this->vcalname = $this->calendars[$k][name];
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
			$this->notempty = 0;
			if( $k < $this->countevent)
				{
				$this->notempty = 1;
				$this->bgcolor = "";
				$this->title = "";
				$this->titleten = "&nbsp;";
				$arr = $this->db->db_fetch_array($this->resevent);
				$this->title = substr($arr[start_time], 0 ,5). " " . substr($arr[end_time], 0 ,5). " " .$arr[title];
				$this->titleten = substr($arr[start_time], 0 ,5). " ". substr($arr[title], 0, 20) ;
				$this->titletenurl = $GLOBALS[babUrl]."index.php?tg=event&idx=modify&day=".$day."&month=".$month."&year=".$year. "&calid=".$this->calid. "&evtid=".$arr[id];
				$req = "select * from categoriescal where id='".$arr[id_cat]."'";
				$res = $this->db->db_query($req);
				if( $res && $this->db->db_num_rows($res) > 0)
					{
					$arr = $this->db->db_fetch_array($res);
					$this->bgcolor = $arr[bgcolor];
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

		function getgroupevent()
			{
			static $k=0;
			if( $k < 4 - $this->nbevent)
				{
				$this->titleten = "";
				$this->titletenurl = "";
				$this->notempty = 0;
				if( $k < $this->countgrpevent)
					{
					$this->notempty = 1;
					$arr = $this->db->db_fetch_array($this->resgrpevent);
					$this->title = substr($arr[start_time], 0 ,5). " " . substr($arr[end_time], 0 ,5). " " .$arr[title];
					$this->titleten = substr($arr[start_time], 0 ,5). " ". substr($arr[title], 0, 20) ;
					$this->titletenurl = $GLOBALS[babUrl]."index.php?tg=event&idx=modify&day=".$day."&month=".$month."&year=".$year. "&calid=".$arr[id_cal]. "&evtid=".$arr[id];
					$req = "select * from categoriescal where id='".$arr[id_cat]."'";
					$res = $this->db->db_query($req);
					if( $res && $this->db->db_num_rows($res) > 0)
						{
						$arr = $this->db->db_fetch_array($res);
						$this->bgcolor = $arr[bgcolor];
						}
					else
						$this->bgcolor = "";
					}
				else
					{
					$this->bgcolor = "";
					$this->title = "";
					$this->titleten = "&nbsp;";
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


	if( empty($month))
		$month = Date("n");
	
	if( empty($year))
		$year = Date("Y");

	if( empty($day))
		$day = Date("j");

	$temp = new temp($calid, $day, $month, $year);
	$body->babecho(	babPrintTemplate($temp,"calendar.html", "calmonth"));
	return $temp->count;

}


function calendarWeek($calid, $day, $month, $year)
{
	global $body;

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
		
		function temp($calid, $day, $month, $year)
			{
			$this->db = new db_mysql();
			$this->view = "viewq";
			$this->calid = $calid;
			$this->caltype = getCalendarType($calid);
			$this->month = $month;
			$this->year = $year;
			$this->day = $day;
			$this->curday = date("w", mktime(0,0,0,$month, $day, $year));
			$d = $day - 7;
			if( $d == 0)
				$d = -1;
			$this->previousweek = $GLOBALS[babUrl]."index.php?tg=calendar&idx=viewq&day=".date("j", mktime( 0,0,0, $month, $d, $year));
			$this->previousweek .= "&month=".date("n", mktime( 0,0,0, $month, $d, $year));
			$this->previousweek .= "&year=".date("Y", mktime( 0,0,0, $month, $d, $year)). "&calid=".$this->calid;
			$d = $day + 7;
			$this->nextweek = $GLOBALS[babUrl]."index.php?tg=calendar&idx=viewq&day=".date("j", mktime( 0,0,0, $month, $d, $year));
			$this->nextweek .= "&month=". date("n", mktime( 0,0,0, $month, $d, $year));
			$this->nextweek .= "&year=". date("Y", mktime( 0,0,0, $month, $d, $year)). "&calid=".$this->calid;

			$this->previousyear = $GLOBALS[babUrl]."index.php?tg=calendar&idx=viewq&day=".date("j", mktime( 0,0,0, $month, $day, $year-1));
			$this->previousyear .= "&month=".date("n", mktime( 0,0,0, $month, $day, $year-1));
			$this->previousyear .= "&year=".date("Y", mktime( 0,0,0, $month, $day, $year-1)). "&calid=".$this->calid;
			$this->nextyear = $GLOBALS[babUrl]."index.php?tg=calendar&idx=viewq&day=".date("j", mktime( 0,0,0, $month, $day, $year+1));
			$this->nextyear .= "&month=". date("n", mktime( 0,0,0, $month, $day, $year+1));
			$this->nextyear .= "&year=". date("Y", mktime( 0,0,0, $month, $day, $year+1)). "&calid=".$this->calid;

			$this->previousmonth = $GLOBALS[babUrl]."index.php?tg=calendar&idx=viewq&day=".date("j", mktime( 0,0,0, $month-1, $day, $year));
			$this->previousmonth .= "&month=".date("n", mktime( 0,0,0, $month-1, $day, $year));
			$this->previousmonth .= "&year=".date("Y", mktime( 0,0,0, $month-1, $day, $year)). "&calid=".$this->calid;
			$this->nextmonth = $GLOBALS[babUrl]."index.php?tg=calendar&idx=viewq&day=".date("j", mktime( 0,0,0, $month+1, $day, $year));
			$this->nextmonth .= "&month=". date("n", mktime( 0,0,0, $month+1, $day, $year));
			$this->nextmonth .= "&year=". date("Y", mktime( 0,0,0, $month+1, $day, $year)). "&calid=".$this->calid;

			$this->monthurl = $GLOBALS[babUrl]."index.php?tg=calendar&idx=viewm&day=".$day."&month=".$month."&year=".$year. "&calid=".$this->calid;
			$this->weekurl = $GLOBALS[babUrl]."index.php?tg=calendar&idx=viewq&day=".$day."&month=".$month."&year=".$year. "&calid=".$this->calid;
			$this->dayurl = $GLOBALS[babUrl]."index.php?tg=calendar&idx=viewd&day=".$day."&month=".$month."&year=".$year. "&calid=".$this->calid;
			$this->monthurlname = babTranslate("Month");
			$this->weekurlname = babTranslate("Week");
			$this->dayurlname = babTranslate("Day");
			$this->previous = babTranslate("Previous");
			$this->next = babTranslate("Next");
			$this->gotodayname = babTranslate("Go to Today");
			$this->gotodayurl = $GLOBALS[babUrl]."index.php?tg=calendar&idx=viewq&day=".date("j")."&month=".date("n")."&year=".date("Y"). "&calid=".$this->calid;
			$this->calendars = getAvailableCalendars();
			$this->viewthis = babTranslate("View this calendar");


			}

		function getnextcal()
			{
			static $k=0;
			if( $k < count($this->calendars))
				{
				if( $this->calid == $this->calendars[$k][idcal])
					$this->selected = "selected";
				else
					$this->selected = "";
				$this->vcalid = $this->calendars[$k][idcal];
				$this->vcalname = $this->calendars[$k][name];
				$k++;
				return true;
				}
			else
				{
				$k = 0;
				return false;
				}
			}

		function getday()
			{
			global $BAB_SESS_USERID, $babMonths, $babDays;
			static $i = 0;
			if( $i < 7)
				{
				$this->nbevent = 0;
				$day = $this->day - $this->curday + $i;
				$this->dayname = bab_strftime(mktime( 0,0,0, $this->month, $day, $this->year), false);
				$this->resevent = getEventsResult($this->calid, $day, $this->month, $this->year);
				$this->countevent = $this->db->db_num_rows($this->resevent);
				$this->nbevent += $this->countevent;
				if( $this->caltype == 1)
					{
					$idcal = getCalendarId(getPrimaryGroupId($BAB_SESS_USERID), 2);
					$this->resgrpevent = getEventsResult($idcal, $day, $this->month, $this->year);
					$this->countgrpevent = $this->db->db_num_rows($this->resgrpevent);
					//$this->nbevent += $this->countgrpevent;
					}
				
				$this->neweventurl = $GLOBALS[babUrl]."index.php?tg=event&idx=newevent&day=".$day."&month=".$this->month. "&year=".$this->year."&calid=".$this->calid."&view=viewq";
				$i++;
				return true;
				}
			else
				return false;
			}

		function getevent()
			{
			static $k=0;
			if( $k < $this->countevent)
				{
				$this->bgcolor = "";
				$this->title = "";
				$this->titleten = "&nbsp;";
				$arr = $this->db->db_fetch_array($this->resevent);
				$this->title = substr($arr[start_time], 0 ,5). " " . substr($arr[end_time], 0 ,5). " " .$arr[title];
				$this->titleten = $this->title ;
				$this->titletenurl = $GLOBALS[babUrl]."index.php?tg=event&idx=modify&day=".$day."&month=".$month."&year=".$year. "&calid=".$arr[id_cal]. "&evtid=".$arr[id];
				$req = "select * from categoriescal where id='".$arr[id_cat]."'";
				$res = $this->db->db_query($req);
				if( $res && $this->db->db_num_rows($res) > 0)
					{
					$arr = $this->db->db_fetch_array($res);
					$this->bgcolor = $arr[bgcolor];
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

		function getgroupevent()
			{
			static $k=0;
			if( $k < $this->countgrpevent)
				{
				$this->bgcolor = "";
				$this->title = "";
				$this->titleten = "&nbsp;";
				$arr = $this->db->db_fetch_array($this->resgrpevent);
				$this->title = substr($arr[start_time], 0, 5). " " . substr($arr[end_time], 0, 5). " " .$arr[title];
				$this->titleten = $this->title;
				$this->titletenurl = $GLOBALS[babUrl]."index.php?tg=event&idx=modify&day=".$day."&month=".$month."&year=".$year. "&calid=".$arr[id_cal]. "&evtid=".$arr[id];
				$req = "select * from categoriescal where id='".$arr[id_cat]."'";
				$res = $this->db->db_query($req);
				if( $res && $this->db->db_num_rows($res) > 0)
					{
					$arr = $this->db->db_fetch_array($res);
					$this->bgcolor = $arr[bgcolor];
					}
				else
					$this->bgcolor = "";
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


	if( empty($month))
		$month = Date("n");
	
	if( empty($year))
		$year = Date("Y");

	if( empty($day))
		$day = Date("j");

	$temp = new temp($calid, $day, $month, $year);
	$body->babecho(	babPrintTemplate($temp,"calendar.html", "calweek"));
	return $temp->count;

}

function calendarDay($calid, $day, $month, $year, $starttime)
{
	global $body;

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
	
		function temp($calid, $day, $month, $year, $starttime)
			{
			global $BAB_SESS_USERID;
			$this->db = new db_mysql();
			$this->view = "viewd";
			$this->colspan ="";
			$this->firsttime ="";
			$this->calid = $calid;
			$this->caltype = getCalendarType($calid);
			if( $starttime == 1)
				{
				$this->prevtimeurl = $GLOBALS[babUrl]."index.php?tg=calendar&idx=viewd&day=".date("j", mktime( 0,0,0, $month, $day-1, $year));
				$this->prevtimeurl .= "&month=".date("n", mktime( 0,0,0, $month, $day-1, $year));
				$this->prevtimeurl .= "&year=".date("Y", mktime( 0,0,0, $month, $day-1, $year)). "&calid=".$this->calid."&start=3";
				$this->nexttimeurl = $GLOBALS[babUrl]."index.php?tg=calendar&idx=viewd&day=".$day."&month=".$month."&year=".$year."&calid=".$this->calid."&start=2";
				$this->starttime = 0;
				$this->maxidx = 16;
				}
			else if( $starttime == 3)
				{
				$this->nexttimeurl = $GLOBALS[babUrl]."index.php?tg=calendar&idx=viewd&day=".date("j", mktime( 0,0,0, $month, $day+1, $year));
				$this->nexttimeurl .= "&month=".date("n", mktime( 0,0,0, $month, $day+1, $year));
				$this->nexttimeurl .= "&year=".date("Y", mktime( 0,0,0, $month, $day+1, $year)). "&calid=".$this->calid."&start=1";
				$this->prevtimeurl = $GLOBALS[babUrl]."index.php?tg=calendar&idx=viewd&day=".$day."&month=".$month."&year=".$year."&calid=".$this->calid."&start=2";
				$this->starttime = 19;
				$this->maxidx = 10;
				}
			else
				{
				$this->starttime = 8;
				$this->prevtimeurl = $GLOBALS[babUrl]."index.php?tg=calendar&idx=viewd&day=".$day."&month=".$month."&year=".$year."&calid=".$this->calid."&start=1";
				$this->nexttimeurl = $GLOBALS[babUrl]."index.php?tg=calendar&idx=viewd&day=".$day."&month=".$month."&year=".$year."&calid=".$this->calid."&start=3";
				$this->maxidx = 22;
			}

			$this->month = $month;
			$this->year = $year;
			$this->day = $day;
			$this->firsttime = $starttime;
			$this->curhour = $starttime;
			$this->dayname = bab_strftime(mktime( 0,0,0, $this->month, $this->day, $this->year), false);
			$d = $day - 1;
			$this->previousday = $GLOBALS[babUrl]."index.php?tg=calendar&idx=viewd&day=".date("j", mktime( 0,0,0, $month, $d, $year));
			$this->previousday .= "&month=".date("n", mktime( 0,0,0, $month, $d, $year));
			$this->previousday .= "&year=".date("Y", mktime( 0,0,0, $month, $d, $year)). "&calid=".$this->calid;
			$d = $day + 1;
			$this->nextday = $GLOBALS[babUrl]."index.php?tg=calendar&idx=viewd&day=".date("j", mktime( 0,0,0, $month, $d, $year));
			$this->nextday .= "&month=". date("n", mktime( 0,0,0, $month, $d, $year));
			$this->nextday .= "&year=". date("Y", mktime( 0,0,0, $month, $d, $year)). "&calid=".$this->calid;

			$this->previousmonth = $GLOBALS[babUrl]."index.php?tg=calendar&idx=viewd&day=".date("j", mktime( 0,0,0, $month-1, $day, $year));
			$this->previousmonth .= "&month=".date("n", mktime( 0,0,0, $month-1, $day, $year));
			$this->previousmonth .= "&year=".date("Y", mktime( 0,0,0, $month-1, $day, $year)). "&calid=".$this->calid;
			$this->nextmonth = $GLOBALS[babUrl]."index.php?tg=calendar&idx=viewd&day=".date("j", mktime( 0,0,0, $month+1, $day, $year));
			$this->nextmonth .= "&month=". date("n", mktime( 0,0,0, $month+1, $day, $year));
			$this->nextmonth .= "&year=". date("Y", mktime( 0,0,0, $month+1, $day, $year)). "&calid=".$this->calid;

			$this->previousyear = $GLOBALS[babUrl]."index.php?tg=calendar&idx=viewd&day=".date("j", mktime( 0,0,0, $month, $day, $year-1));
			$this->previousyear .= "&month=".date("n", mktime( 0,0,0, $month, $day, $year-1));
			$this->previousyear .= "&year=".date("Y", mktime( 0,0,0, $month, $day, $year-1)). "&calid=".$this->calid;
			$this->nextyear = $GLOBALS[babUrl]."index.php?tg=calendar&idx=viewd&day=".date("j", mktime( 0,0,0, $month, $day, $year+1));
			$this->nextyear .= "&month=". date("n", mktime( 0,0,0, $month, $day, $year+1));
			$this->nextyear .= "&year=". date("Y", mktime( 0,0,0, $month, $day, $year+1)). "&calid=".$this->calid;

			$this->monthurl = $GLOBALS[babUrl]."index.php?tg=calendar&idx=viewm&day=".$day."&month=".$month."&year=".$year. "&calid=".$this->calid;
			$this->weekurl = $GLOBALS[babUrl]."index.php?tg=calendar&idx=viewq&day=".$day."&month=".$month."&year=".$year. "&calid=".$this->calid;
			$this->dayurl = $GLOBALS[babUrl]."index.php?tg=calendar&idx=viewd&day=".$day."&month=".$month."&year=".$year. "&calid=".$this->calid;
			$this->monthurlname = babTranslate("Month");
			$this->weekurlname = babTranslate("Week");
			$this->dayurlname = babTranslate("Day");
			$this->previous = babTranslate("Previous");
			$this->next = babTranslate("Next");
			$this->gotodayname = babTranslate("Go to Today");
			$this->gotodayurl = $GLOBALS[babUrl]."index.php?tg=calendar&idx=viewd&day=".date("j")."&month=".date("n")."&year=".date("Y"). "&calid=".$this->calid;
			$this->resevent = getEventsResult($this->calid, $this->day, $this->month, $this->year);
			$this->countevent = $this->db->db_num_rows($this->resevent);
			$this->nbevent += $this->countevent;
			if( $this->caltype == 1)
				{
				$idgrp = getPrimaryGroupId($BAB_SESS_USERID);
				$this->grpname = getGroupName($idgrp);
				$idcal = getCalendarId($idgrp, 2);
				$this->resgrpevent = getEventsResult($idcal, $this->day, $this->month, $this->year);
				$this->countgrpevent = $this->db->db_num_rows($this->resgrpevent);
				$this->nbevent += $this->countgrpevent;
				}
			$this->colspan = $this->nbevent;
			$this->calendars = getAvailableCalendars();
			$this->viewthis = babTranslate("View this calendar");
			}

		function getnextcal()
			{
			static $k=0;
			if( $k < count($this->calendars))
				{
				if( $this->calid == $this->calendars[$k][idcal])
					$this->selected = "selected";
				else
					$this->selected = "";
				$this->vcalid = $this->calendars[$k][idcal];
				$this->vcalname = $this->calendars[$k][name];
				$k++;
				return true;
				}
			else
				{
				$k = 0;
				return false;
				}
			}

		function getnexthour()
			{
			static $i = 0;
			if( $this->countevent > 0)
				$this->db->db_data_seek($this->resevent,0);
			if( $this->countgrpevent > 0)
				$this->db->db_data_seek($this->resgrpevent,0);
			if( $i < $this->maxidx)
				{
				$this->curhour = $this->starttime * 60 + $i * 30;
				$this->hour = sprintf("%02d:<sup>%02d</sup>", $this->curhour/60, $this->curhour%60);
				$this->hoururl = $GLOBALS[babUrl]."index.php?tg=event&idx=newevent&day=".$this->day."&month=".$this->month. "&year=".$this->year."&calid=".$this->calid."&view=viewd";
				if( $i % 2)
					{
					$this->bgcolor = "white";
					}
				else
					{
					$this->bgcolor = "";
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

		function getnextevent()
			{
			static $k = 0;
			static $tab = array();
			if( $k < $this->countevent)
				{
				$this->notempty = 0;
				$hourmin = $this->curhour;
				$hourmax = $this->curhour  + 30;
				$arr = $this->db->db_fetch_array($this->resevent);
				$a = substr($arr[start_time], 0,2) * 60 + substr($arr[start_time], 3,2);
				$b = substr($arr[end_time], 0,2) * 60 + substr($arr[end_time], 3,2);
				if( $b < $hourmin || $a >= $hourmax)
					{
					$this->bgcolor = "";
					$this->titleten = "&nbsp;";
					$this->notempty = 0;
					//$this->titleten = "&nbsp;".$hourmin.":".$hourmax. "----". $a.":".$b;
					}
				else
					{
					//$this->titleten = "&nbsp;".$hourmin.":".$hourmax. "----". $a.":".$b;
					$this->notempty = 1;
					if( empty($tab[$k]))
						$tab[$k] = substr($arr[start_time], 0 ,5). " " . substr($arr[end_time], 0 ,5). " " .$arr[title];
					else
						{
						$this->notempty = 0;
						$tab[$k] = "&nbsp;";
						}

					$this->titleten = $tab[$k];
					$this->titletenurl = $GLOBALS[babUrl]."index.php?tg=event&idx=modify&day=".$day."&month=".$month."&year=".$year. "&calid=".$arr[id_cal]. "&evtid=".$arr[id];

					$req = "select * from categoriescal where id='".$arr[id_cat]."'";
					$res = $this->db->db_query($req);
					if( $res && $this->db->db_num_rows($res) > 0)
						{
						$arr = $this->db->db_fetch_array($res);
						$this->bgcolor = $arr[bgcolor];
						}
					else
						$this->bgcolor = "white";
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
		
		function getnextgrpevent()
			{
			static $k = 0;
			static $tab = array();
			if( $k < $this->countgrpevent)
				{
				$this->notempty = 0;
				$hourmin = $this->curhour;
				$hourmax = $this->curhour  + 30;
				$arr = $this->db->db_fetch_array($this->resgrpevent);
				$a = substr($arr[start_time], 0,2) * 60 + substr($arr[start_time], 3,2);
				$b = substr($arr[end_time], 0,2) * 60 + substr($arr[end_time], 3,2);
				if( $b < $hourmin || $a >= $hourmax)
					{
					$this->notempty = 0;
					$this->bgcolor = "";
					$this->titleten = "&nbsp;";
					}
				else
					{
					$this->notempty = 1;
					if( empty($tab[$k]))
						$tab[$k] = substr($arr[start_time], 0 ,5). " " . substr($arr[end_time], 0 ,5). " " .$arr[title]." (".$this->grpname.")";
					else
						{
						$this->notempty = 0;
						$tab[$k] = "&nbsp;";
						}

					$this->titletenurl = $GLOBALS[babUrl]."index.php?tg=event&idx=modify&day=".$day."&month=".$month."&year=".$year. "&calid=".$arr[id_cal]. "&evtid=".$arr[id];
					$this->titleten = $tab[$k];
					$req = "select * from categoriescal where id='".$arr[id_cat]."'";
					$res = $this->db->db_query($req);
					if( $res && $this->db->db_num_rows($res) > 0)
						{
						$arr = $this->db->db_fetch_array($res);
						$this->bgcolor = $arr[bgcolor];
						}
					else
						$this->bgcolor = "white";
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


	if( empty($month))
		$month = Date("n");
	
	if( empty($year))
		$year = Date("Y");

	if( empty($day))
		$day = Date("j");

	$temp = new temp($calid, $day, $month, $year, $starttime);
	$body->babecho(	babPrintTemplate($temp,"calendar.html", "calday"));
	return $temp->count;

}

/* main */
if(!isset($idx))
	{
	$idx = "viewm";
	}

if( isset($viewcal) && $viewcal == "view")
{
	Header("Location: index.php?tg=calendar&idx=".$idx."&calid=".$calendar."&day=".$day."&month=".$month."&year=".$year."&start=".$start);
}

switch($idx)
	{
	case "viewd":
		$body->title = "";
		calendarDay($calid, $day, $month, $year, $start);
		if( isUserGroupManager())
			{
			$body->addItemMenu("listcat", babTranslate("Categories"), $GLOBALS[babUrl]."index.php?tg=confcals&idx=listcat&userid=$BAB_SESS_USERID");
			$body->addItemMenu("resources", babTranslate("Resources"), $GLOBALS[babUrl]."index.php?tg=confcals&idx=listres&userid=$BAB_SESS_USERID");
			}
		break;
	case "viewq":
		$body->title = "";
		calendarWeek($calid, $day, $month, $year);
		if( isUserGroupManager())
			{
			$body->addItemMenu("listcat", babTranslate("Categories"), $GLOBALS[babUrl]."index.php?tg=confcals&idx=listcat&userid=$BAB_SESS_USERID");
			$body->addItemMenu("resources", babTranslate("Resources"), $GLOBALS[babUrl]."index.php?tg=confcals&idx=listres&userid=$BAB_SESS_USERID");
			}
		break;
	default:
	case "viewm":
		$body->title = "";
		calendarMonth($calid, $day, $month, $year);
		if( isUserGroupManager())
			{
			$body->addItemMenu("listcat", babTranslate("Categories"), $GLOBALS[babUrl]."index.php?tg=confcals&idx=listcat&userid=$BAB_SESS_USERID");
			$body->addItemMenu("resources", babTranslate("Resources"), $GLOBALS[babUrl]."index.php?tg=confcals&idx=listres&userid=$BAB_SESS_USERID");
			}
		break;
	}
$body->setCurrentItemMenu($idx);

?>