<?php

function calendarOptions($view, $day, $month, $year, $start, $calid)
	{
	global $body;

	class temp
		{
		var $startday;
		var $dayid;
		var $dayname;
		var $allday;
		var $viewcateg;
		var $usebgcolor;

		var $modify;
		var $yes;
		var $no;

		function temp($view, $day, $month, $year, $start, $calid)
			{
			global $BAB_SESS_USERID;
			$this->view = $view;
			$this->day = $day;
			$this->month = $month;
			$this->year = $year;
			$this->start = $start;
			$this->calid = $calid;
			$this->startday = babTranslate("First day of week");
			$this->allday = babTranslate("On create new event, check")." ". babTranslate("All day");
			$this->viewcateg = babTranslate("View calendar categories");
			$this->usebgcolor = babTranslate("Use bacground color for events");
			$this->modify = babTranslate("Modify");
			$this->yes = babTranslate("Yes");
			$this->no = babTranslate("No");
			$db = new db_mysql();
			$req = "select * from caloptions where id_user='".$BAB_SESS_USERID."'";
			$res = $db->db_query($req);
			$this->arr = $db->db_fetch_array($res);
			}

		function getnextday()
			{
			global $babDays;

			static $i = 0;
			if( $i < 7 )
				{
				if( $i == $this->arr[startday])
					$this->selected = "selected";
				else
					$this->selected = "";
				$this->dayid = $i;
				$this->dayname = $babDays[$i];		
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

	$temp = new temp($view, $day, $month, $year, $start, $calid);
	$body->babecho(	babPrintTemplate($temp, "calopt.html", "caloptions"));
	}

function updateCalOptions($startday, $allday, $viewcat, $usebgcolor)
	{
	global $BAB_SESS_USERID;
	$db = new db_mysql();
	$req = "select * from caloptions where id_user='".$BAB_SESS_USERID."'";
	$res = $db->db_query($req);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$req = "update caloptions set startday='$startday', allday='$allday', viewcat='$viewcat', usebgcolor='$usebgcolor' where id_user='$BAB_SESS_USERID'";
		}
	else
		{
		$req = "insert into caloptions ( id_user, startday, allday, viewcat, usebgcolor) values ";
		$req .= "('".$BAB_SESS_USERID."', '".$startday."', '".$allday."', '".$viewcat."', '".$usebgcolor."')";
		}
	$res = $db->db_query($req);

	}
/* main */
if(!isset($idx))
	{
	$idx = "options";
	}

if( isset($modify) && $modify == "options")
	{
	updateCalOptions($startday, $allday, $viewcat, $usebgcolor);
	Header("Location: index.php?tg=calendar&idx=".$view."&day=".$day."&month=".$month."&year=".$year."&start=".$start. "&calid=".$calid);
	}

switch($idx)
	{
	default:
	case "options":
		$body->title = babTranslate("Calendar Options");
		$idcal = getCalendarid($BAB_SESS_USERID, 1);
		if( (getCalendarId(1, 2) != 0  || getCalendarId(getPrimaryGroupId($BAB_SESS_USERID), 2) != 0) && $idcal != 0 )
		{
			calendarOptions($view, $day, $month, $year, $start, $calid);
			$body->addItemMenu($view, babTranslate("Calendar"), $GLOBALS[babUrl]."index.php?tg=calendar&idx=".$view."&day=".$day."&month=".$month."&year=".$year."&start=".$start. "&calid=".$calid);
			$body->addItemMenu("options", babTranslate("Options"), $GLOBALS[babUrl]."index.php?tg=calopt&idx=options&day=".$day."&month=".$month."&year=".$year."&start=".$start."&calid=".$calid."&view=viewd");
			//$body->addItemMenu("newevent", babTranslate("Add Event"), $GLOBALS[babUrl]."index.php?tg=event&idx=newevent&calendarid=0");
		}
		break;
	}
$body->setCurrentItemMenu($idx);

?>