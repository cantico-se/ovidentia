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
	$req = "select * from calaccess_users where id_user='".$BAB_SESS_USERID."'";
	$res = $db->db_query($req);
	while($row = $db->db_fetch_array($res))
	{
		$rr[name] = getCalendarOwnerName($row[id_cal], 1);
		$rr[idcal] = $row[id_cal];
		array_push($tab, $rr);
	}


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
			if( $rr[idcal] != 0)
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
	$req .= " or start_date between '$daymin' and '$daymax' or end_date between '$daymin' and '$daymax') order by start_date, start_time asc";
	return $this->resevent = $db->db_query($req);
}

function calendarMonth($calid, $day, $month, $year, $caltype, $owner, $bmanager)
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
		var $new;
		var $maxevent;
		var $plus;
		var $babCalendarStartDay;
		var $babCalendarUsebgColor;
	
		function temp($calid, $day, $month, $year, $caltype, $owner, $bmanager)
			{
			global $BAB_SESS_USERID, $babMonths;
			$this->db = new db_mysql();
			$this->view = "viewm";
			$this->w = 0;
			$this->nbevent = 0;
			$this->totaldays = date("t", mktime(0,0,0,$month,1,$year));
			$this->day = $day;
			$this->month = $month;
			$this->year = $year;
			$this->calid = $calid;
			$this->caltype = $caltype;
			$this->viewthis = babTranslate("View this calendar");
			$this->new = babTranslate("New");
			$this->maxevent = 6;
			$this->plus = "";
			$req = "select * from caloptions where id_user='".$BAB_SESS_USERID."'";
			$res = $this->db->db_query($req);
			$this->babCalendarStartDay = 0;
			$this->babCalendarUsebgColor = "Y";
			if( $res && $this->db->db_num_rows($res) > 0)
				{
				$arr = $this->db->db_fetch_array($res);
				$this->babCalendarStartDay = $arr[startday];
				$this->babCalendarUsebgColor = $arr[usebgcolor];
				}

			switch($caltype)
				{
				case 1:
					if( $owner == $BAB_SESS_USERID)
						$this->bowner = 1;
					else
						{
						$this->bowner = 0;
						$req = "select * from calaccess_users where id_cal='".$calid."' and id_user='".$BAB_SESS_USERID."'";
						$res = $this->db->db_query($req);
						if( $res && $this->db->db_num_rows($res) > 0)
							{
							$row = $this->db->db_fetch_array($res);
							if($row[bwrite] == "Y")
								$this->bowner = 1;
							}
						}
					$grpid = getPrimaryGroupId($owner);
					if( isUserGroupManager($grpid))
						$this->bmanager = 1;
					else
						$this->bmanager = 0;
					break;
				case 2:
					if( isUserGroupManager($owner))
						$this->bowner = 1;
					else
						$this->bowner = 0;
					$this->bmanager = 0;
					break;
				case 3:
					$this->bowner = 1;
					$this->bmanager = 0;
					break;
				default:
					$this->bowner = 0;
					$this->bmanager = 0;
					break;	
				}

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
				$this->plus = "";
				$this->currentmonth = 1;
				$this->currentday = 0;
				$this->nbevent = 0;
				$this->countevent = 0;
				$this->countgrpevent = 0;

				$b = $this->firstday - $this->babCalendarStartDay;
				if( $b < 0)
					$b += 7;

				$day = (7 * ($this->w-1)) + $d - $b +1 ;
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
				if( $this->countevent > $this->maxevent)
					{
					$this->nbevent = $this->maxevent;
					}
				else
					$this->nbevent = $this->countevent;
				if( $this->caltype == 1)
					{
					$idcal = getCalendarId(getPrimaryGroupId($BAB_SESS_USERID), 2);
					$this->resgrpevent = getEventsResult($idcal, $day, $this->month, $this->year);
					$this->countgrpevent = $this->db->db_num_rows($this->resgrpevent);
					//$this->nbevent += $this->countgrpevent;
					}
				if( $this->countgrpevent + $this->countevent > $this->maxevent)
					$this->plus = "+++";
				else
					$this->plus = "";

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
			if( $k < $this->nbevent)
				{
				$this->notempty = 1;
				$this->bgcolor = "";
				$this->title = "";
				$this->titleten = "&nbsp;";
				$arr = $this->db->db_fetch_array($this->resevent);
				$this->title = substr($arr[start_time], 0 ,5). " " . substr($arr[end_time], 0 ,5). " " .$arr[title];
				$this->titleten = substr($arr[start_time], 0 ,5). " ". substr($arr[title], 0, 20) ;
				$this->titletenurl = $GLOBALS[babUrl]."index.php?tg=event&idx=modify&day=".$day."&month=".$month."&year=".$year. "&calid=".$this->calid. "&evtid=".$arr[id];
				if( $this->babCalendarUsebgColor == "Y")
					{
					$req = "select * from categoriescal where id='".$arr[id_cat]."'";
					$res = $this->db->db_query($req);
					if( $res && $this->db->db_num_rows($res) > 0)
						{
						$arr = $this->db->db_fetch_array($res);
						$this->bgcolor = $arr[bgcolor];
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

		function getgroupevent()
			{
			static $k=0;
			if( $k < $this->maxevent - $this->nbevent)
				{
				$this->bgcolor = "";
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
					if( $this->babCalendarUsebgColor == "Y")
						{
						$req = "select * from categoriescal where id='".$arr[id_cat]."'";
						$res = $this->db->db_query($req);
						if( $res && $this->db->db_num_rows($res) > 0)
							{
							$arr = $this->db->db_fetch_array($res);
							$this->bgcolor = $arr[bgcolor];
							}
						}
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

	$temp = new temp($calid, $day, $month, $year, $caltype, $owner, $bmanager);
	$body->babecho(	babPrintTemplate($temp,"calendar.html", "calmonth"));
	return $temp->count;

}


function calendarWeek($calid, $day, $month, $year, $caltype, $owner, $bmanager)
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
		var $babCalendarStartDay;
		var $babCalendarUsebgColor;

		
		function temp($calid, $day, $month, $year, $caltype, $owner, $bmanager)
			{
			$this->db = new db_mysql();
			$this->view = "viewq";
			$this->calid = $calid;
			$this->caltype = $caltype;
			$this->month = $month;
			$this->year = $year;
			$this->day = $day;
			$req = "select * from caloptions where id_user='".$BAB_SESS_USERID."'";
			$res = $this->db->db_query($req);
			$this->babCalendarStartDay = 0;
			$this->babCalendarUsebgColor = "Y";
			if( $res && $this->db->db_num_rows($res) > 0)
				{
				$arr = $this->db->db_fetch_array($res);
				$this->babCalendarStartDay = $arr[startday];
				$this->babCalendarUsebgColor = $arr[usebgcolor];
				}
			switch($caltype)
				{
				case 1:
					if( $owner == $BAB_SESS_USERID)
						$this->bowner = 1;
					else
						{
						$this->bowner = 0;
						$req = "select * from calaccess_users where id_cal='".$calid."' and id_user='".$BAB_SESS_USERID."'";
						$res = $this->db->db_query($req);
						if( $res && $this->db->db_num_rows($res) > 0)
							{
							$row = $this->db->db_fetch_array($res);
							if($row[bwrite] == "Y")
								$this->bowner = 1;
							}
						}
					$grpid = getPrimaryGroupId($owner);
					if( isUserGroupManager($grpid))
						$this->bmanager = 1;
					else
						$this->bmanager = 0;
					break;
				case 2:
					if( isUserGroupManager($owner))
						$this->bowner = 1;
					else
						$this->bowner = 0;
					$this->bmanager = 0;
					break;
				case 3:
					$this->bowner = 1;
					$this->bmanager = 0;
					break;
				default:
					$this->bowner = 0;
					$this->bmanager = 0;
					break;	
				}
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
			$this->new = babTranslate("New");
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
				$a = $this->curday - $this->babCalendarStartDay;
				if( $a < 0)
					$a += 7;
				$day = $this->day - $a + $i;
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
				if( $babCalendarUsebgColor == "Y")
					{
					$req = "select * from categoriescal where id='".$arr[id_cat]."'";
					$res = $this->db->db_query($req);
					if( $res && $this->db->db_num_rows($res) > 0)
						{
						$arr = $this->db->db_fetch_array($res);
						$this->bgcolor = $arr[bgcolor];
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
				if( $babCalendarUsebgColor == "Y")
					{
					$req = "select * from categoriescal where id='".$arr[id_cat]."'";
					$res = $this->db->db_query($req);
					if( $res && $this->db->db_num_rows($res) > 0)
						{
						$arr = $this->db->db_fetch_array($res);
						$this->bgcolor = $arr[bgcolor];
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

	$temp = new temp($calid, $day, $month, $year, $caltype, $owner, $bmanager);
	$body->babecho(	babPrintTemplate($temp,"calendar.html", "calweek"));
	return $temp->count;

}

function calendarDay($calid, $day, $month, $year, $starttime, $caltype, $owner, $bmanager)
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
		var $babCalendarUsebgColor;
	
		function temp($calid, $day, $month, $year, $starttime, $caltype, $owner, $bmanager)
			{
			global $BAB_SESS_USERID;
			$this->db = new db_mysql();
			$this->view = "viewd";
			$this->colspan ="";
			$this->firsttime ="";
			$this->calid = $calid;
			$this->caltype = $caltype;
			$req = "select * from caloptions where id_user='".$BAB_SESS_USERID."'";
			$res = $this->db->db_query($req);
			$this->babCalendarUsebgColor = "Y";
			if( $res && $this->db->db_num_rows($res) > 0)
				{
				$arr = $this->db->db_fetch_array($res);
				$this->babCalendarUsebgColor = $arr[usebgcolor];
				}
			switch($caltype)
				{
				case 1:
					if( $owner == $BAB_SESS_USERID)
						$this->bowner = 1;
					else
						{
						$this->bowner = 0;
						$req = "select * from calaccess_users where id_cal='".$calid."' and id_user='".$BAB_SESS_USERID."'";
						$res = $this->db->db_query($req);
						if( $res && $this->db->db_num_rows($res) > 0)
							{
							$row = $this->db->db_fetch_array($res);
							if($row[bwrite] == "Y")
								$this->bowner = 1;
							}
						}
					$grpid = getPrimaryGroupId($owner);
					if( isUserGroupManager($grpid))
						$this->bmanager = 1;
					else
						$this->bmanager = 0;
					break;
				case 2:
					if( isUserGroupManager($owner))
						$this->bowner = 1;
					else
						$this->bowner = 0;
					$this->bmanager = 0;
					break;
				case 3:
					$this->bowner = 1;
					$this->bmanager = 0;
					break;
				default:
					$this->bowner = 0;
					$this->bmanager = 0;
					break;	
				}
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
				$this->bgcolor = "white";
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

					if( $babCalendarUsebgColor == "Y")
						{
						$req = "select * from categoriescal where id='".$arr[id_cat]."'";
						$res = $this->db->db_query($req);
						if( $res && $this->db->db_num_rows($res) > 0)
							{
							$arr = $this->db->db_fetch_array($res);
							$this->bgcolor = $arr[bgcolor];
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
		
		function getnextgrpevent()
			{
			static $k = 0;
			static $tab = array();
			if( $k < $this->countgrpevent)
				{
				$this->bgcolor = "white";
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
					if( $babCalendarUsebgColor == "Y")
						{
						$req = "select * from categoriescal where id='".$arr[id_cat]."'";
						$res = $this->db->db_query($req);
						if( $res && $this->db->db_num_rows($res) > 0)
							{
							$arr = $this->db->db_fetch_array($res);
							$this->bgcolor = $arr[bgcolor];
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


	$temp = new temp($calid, $day, $month, $year, $starttime, $caltype, $owner, $bmanager);
	$body->babecho(	babPrintTemplate($temp,"calendar.html", "calday"));
	return $temp->count;

}

function accessCalendar($view, $day, $month, $year, $start, $calid)
{
	global $body;
	
	class temp
		{
		var $email;
		var $textinfo;
		var $view;
		var $day;
		var $month;
		var $year;
		var $start;
		var $calid;
		var $addusers;
		var $useraccess;
		var $fullname;
		var $accessname;
		var $yesno;
		var $delusers;

		var $db;
		var $res;
		var $count;
		var $arr = array();

		function temp($view, $day, $month, $year, $start, $calid)
			{
			$this->db = new db_mysql();
			$this->view = $view;
			$this->day = $day;
			$this->month = $month;
			$this->year = $year;
			$this->start = $start;
			$this->calid = $calid;
			$this->email = babTranslate("Email");
			$this->textinfo = babTranslate("Enter user email. ( You can enter multiple emails separated by space )");
			$this->addusers = babTranslate("Update access");
			$this->useraccess = babTranslate("User can update my calendar");
			$this->fullname = babTranslate("Fullname");
			$this->accessname = babTranslate("Update");
			$this->delusers = babTranslate("Delete users");
			$req = "select * from calaccess_users where id_cal='".$calid."'";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			}

		function getnext()
			{
			static $k=0;
			if( $k < $this->count)
				{
				$arr = $this->db->db_fetch_array($this->res);
				$req = "select * from users where id='".$arr[id_user]."'";
				$res = $this->db->db_query($req);
				$this->arr = $this->db->db_fetch_array($res);
				if( $arr[bwrite] == "Y")
					$this->yesno = babTranslate("Yes");
				else
					$this->yesno = babTranslate("No");
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

	$temp = new temp($view, $day, $month, $year, $start, $calid);
	$body->babecho(	babPrintTemplate($temp,"calendar.html", "access"));
}

function categoriesList($calid)
	{
	global $body, $BAB_SESS_USERID;
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
			$this->description = babTranslate("Categories");
			$this->calid = $calendarid;
			$this->caltype = getCalendarType($calendarid);
			$this->db = new db_mysql();
			switch( $this->caltype)
				{
				case 1: // user
					$req = "select * from users_groups join groups where id_object=$BAB_SESS_USERID and groups.id=users_groups.id_group";
					$resgroups = $this->db->db_query($req);
					if( $resgroups )
						{
						$countgroups = $this->db->db_num_rows($resgroups); 
						}

					$req2 = "select * from categoriescal where id_group='1'";
					if( $countgroups > 0)
						{
						for( $i = 0; $i < $countgroups; $i++)
							{
							$arr = $this->db->db_fetch_array($resgroups);
							$req2 .= " or id_group='".$arr[id]."'"; 
							}
						$this->db->db_data_seek($resgroups, 0);
						}
					$this->rescat = $this->db->db_query($req2);
					$this->countcat = $this->db->db_num_rows($this->rescat); 
					break;
				case 2: // group
					$req = "select * from calendar where id='".$calendarid."'";
					$res = $this->db->db_query($req);
					$arr = $this->db->db_fetch_array($res);
					$req = "select * from categoriescal where id_group='1' or id_group='".$arr[owner]."'";
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

	$db = new db_mysql();
	$req = "select * from caloptions where id_user='".$BAB_SESS_USERID."'";
	$res = $db->db_query($req);
	$arr = $db->db_fetch_array($res);
	if( $arr[viewcat] == "Y")
		{
		$temp = new temp2($calid);
		$body->babecho(	babPrintTemplate($temp, "calendar.html", "categorieslist"));
		}
	}

function addAccessUsers( $emails, $calid, $baccess, $del )
{
	$db = new db_mysql();
	$arr = explode(" ", $emails);

	if( $baccess == "y")
		$acc = "Y";
	else
		$acc = "N";

	for( $i = 0; $i < count($arr); $i++)
		{
		$req = "select * from users where email='".trim($arr[$i])."'";
		$res = $db->db_query($req);
		if( $res && $db->db_num_rows($res) > 0)
			{
			$rr = $db->db_fetch_array($res);
			$iduser = $rr[id];
			$req = "select * from calaccess_users where id_cal='".$calid."' and id_user='".$iduser."'";
			$res = $db->db_query($req);
			if( $res && $db->db_num_rows($res) > 0)
				{
				$rr = $db->db_fetch_array($res);
				if( $del )
					$req = "delete from calaccess_users where id='".$rr[id]."'";
				else
					$req = "update calaccess_users set id_user='".$iduser."', bwrite='".$acc."' where id='".$rr[id]."'";
				$res = $db->db_query($req);
				}
			else if($del == false)
				{
				$req = "insert into calaccess_users (id_cal, id_user, bwrite) values ('".$calid."', '".$iduser."', '".$acc."')";
				$res = $db->db_query($req);
				}
			}
		}

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

if( isset($accessuser) && $accessuser == "add")
{
	if( !empty($del))
		$del = true;
	else
		$del = false;
	addAccessUsers($emails, $calendar, $baccess, $del);
	Header("Location: index.php?tg=calendar&idx=".$idx."&calid=".$calendar."&day=".$day."&month=".$month."&year=".$year."&start=".$start);
}

$caltype = getCalendarType($calid);
$owner = getCalendarOwner($calid);
$bmanager = isUserGroupManager();

if( empty($month))
	$month = Date("n");

if( empty($year))
	$year = Date("Y");

if( empty($day))
	$day = Date("j");

switch($idx)
	{

	case "access":
		accessCalendar($view, $day, $month, $year, $start, $calid);
		//$body->title = "Access to". " ". getCalendarOwnerName($calid, $caltype). " " .babTranslate("Calendar");
		$body->title = babTranslate("Access to Calendar");
		$body->addItemMenu($view, babTranslate("Calendar"), $GLOBALS[babUrl]."index.php?tg=calendar&idx=".$view."&day=".$day."&month=".$month."&year=".$year."&start=".$start. "&calid=".$calid);
		$body->addItemMenu("access", babTranslate("Access"), $GLOBALS[babUrl]."index.php?tg=calendar&idx=access&day=".$day."&month=".$month."&year=".$year."&start=".$start."&calid=".$calid);
		if( $bmanager)
			{
			$body->addItemMenu("listcat", babTranslate("Categories"), $GLOBALS[babUrl]."index.php?tg=confcals&idx=listcat&userid=$BAB_SESS_USERID");
			$body->addItemMenu("resources", babTranslate("Resources"), $GLOBALS[babUrl]."index.php?tg=confcals&idx=listres&userid=$BAB_SESS_USERID");
			}
		break;
	case "viewd":
		$body->title = "";
		calendarDay($calid, $day, $month, $year, $start, $caltype, $owner, $bmanager);
		categoriesList($calid);
		$body->addItemMenu("options", babTranslate("Options"), $GLOBALS[babUrl]."index.php?tg=calopt&idx=options&day=".$day."&month=".$month."&year=".$year."&start=".$start."&calid=".$calid."&view=viewd");
		if( $caltype == 1)
			$body->addItemMenu("access", babTranslate("Access"), $GLOBALS[babUrl]."index.php?tg=calendar&idx=access&day=".$day."&month=".$month."&year=".$year."&start=".$start."&calid=".$calid."&view=viewd");
		if( $bmanager)
			{
			$body->addItemMenu("listcat", babTranslate("Categories"), $GLOBALS[babUrl]."index.php?tg=confcals&idx=listcat&userid=$BAB_SESS_USERID");
			$body->addItemMenu("resources", babTranslate("Resources"), $GLOBALS[babUrl]."index.php?tg=confcals&idx=listres&userid=$BAB_SESS_USERID");
			}

		break;
	case "viewq":
		$body->title = "";
		calendarWeek($calid, $day, $month, $year, $caltype, $owner, $bmanager);
		categoriesList($calid);
		$body->addItemMenu("options", babTranslate("Options"), $GLOBALS[babUrl]."index.php?tg=calopt&idx=options&day=".$day."&month=".$month."&year=".$year."&calid=".$calid."&view=viewq");
		if( $caltype == 1)
			$body->addItemMenu("access", babTranslate("Access"), $GLOBALS[babUrl]."index.php?tg=calendar&idx=access&day=".$day."&month=".$month."&year=".$year."&calid=".$calid."&view=viewq");
		if( $bmanager)
			{
			$body->addItemMenu("listcat", babTranslate("Categories"), $GLOBALS[babUrl]."index.php?tg=confcals&idx=listcat&userid=$BAB_SESS_USERID");
			$body->addItemMenu("resources", babTranslate("Resources"), $GLOBALS[babUrl]."index.php?tg=confcals&idx=listres&userid=$BAB_SESS_USERID");
			}
		break;
	default:
	case "viewm":
		$body->title = "";
		calendarMonth($calid, $day, $month, $year, $caltype, $owner, $bmanager);
		categoriesList($calid);
		$body->addItemMenu("options", babTranslate("Options"), $GLOBALS[babUrl]."index.php?tg=calopt&idx=options&day=".$day."&month=".$month."&year=".$year."&calid=".$calid."&view=viewm");
		if( $caltype == 1)
			$body->addItemMenu("access", babTranslate("Access"), $GLOBALS[babUrl]."index.php?tg=calendar&idx=access&day=".$day."&month=".$month."&year=".$year."&calid=".$calid."&view=viewm");
		if( $bmanager)
			{
			$body->addItemMenu("listcat", babTranslate("Categories"), $GLOBALS[babUrl]."index.php?tg=confcals&idx=listcat&userid=$BAB_SESS_USERID");
			$body->addItemMenu("resources", babTranslate("Resources"), $GLOBALS[babUrl]."index.php?tg=confcals&idx=listres&userid=$BAB_SESS_USERID");
			}
		break;
	}
$body->setCurrentItemMenu($idx);

?>