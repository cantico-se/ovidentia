<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/


function accessCalendar($calid)
{
	global $body;
	
	class temp
		{
		var $userstxt;
		var $textinfo;
		var $calid;
		var $addusers;
		var $useraccess;
		var $fullname;
		var $accessname;
		var $yesno;
		var $delusers;
		var $fullnameval;

		var $db;
		var $res;
		var $count;
		var $arr = array();

		function temp($calid)
			{
			$this->db = new db_mysql();
			$this->calid = $calid;
			$this->userstxt = babTranslate("Users");
			$this->textinfo = babTranslate("Enter user name. ( You can enter multiple users separated by comma )");
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
				$this->fullnameval = composeName($this->arr[firstname], $this->arr[lastname]);
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
	$body->babecho(	babPrintTemplate($temp,"calopt.html", "access"));
}

function addAccessUsers( $users, $calid, $baccess, $del )
{

	$db = new db_mysql();
	$arr = explode(",", $users);

	if( $baccess == "y")
		$acc = "Y";
	else
		$acc = "N";

	for( $i = 0; $i < count($arr); $i++)
		{
		$iduser = getUserId($arr[$i]);
		if( $iduser > 0)
			{
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


function calendarOptions($calid)
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

		function temp($calid)
			{
			global $BAB_SESS_USERID;
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

	$temp = new temp($calid);
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


if( isset($accessuser) && $accessuser == "add")
{
	if( !empty($del))
		$del = true;
	else
		$del = false;
	addAccessUsers($users, $idcal, $baccess, $del);
	//Header("Location: index.php?tg=calendar&idx=".$idx."&calid=".$calendar."&day=".$day."&month=".$month."&year=".$year."&start=".$start);
}

if( isset($modify) && $modify == "options")
	{
	updateCalOptions($startday, $allday, $viewcat, $usebgcolor);
	//Header("Location: index.php?tg=calendar&idx=".$view."&day=".$day."&month=".$month."&year=".$year."&start=".$start. "&calid=".$calid);
	}

switch($idx)
	{
	case "access":
		$body->title = babTranslate("Calendar Options");
		$idcal = getCalendarid($BAB_SESS_USERID, 1);
		if( (getCalendarId(1, 2) != 0  || getCalendarId(getPrimaryGroupId($BAB_SESS_USERID), 2) != 0) && $idcal != 0 )
		{
			accessCalendar($idcal);
			$body->addItemMenu("options", babTranslate("Options"), $GLOBALS[babUrl]."index.php?tg=calopt&idx=options");
			$body->addItemMenu("access", babTranslate("Access"), $GLOBALS[babUrl]."index.php?tg=options&idx=access&idcal=".$idcal);
			if( isUserGroupManager())
				{
				$body->addItemMenu("listcat", babTranslate("Categories"), $GLOBALS[babUrl]."index.php?tg=confcals&idx=listcat&userid=$BAB_SESS_USERID");
				$body->addItemMenu("resources", babTranslate("Resources"), $GLOBALS[babUrl]."index.php?tg=confcals&idx=listres&userid=$BAB_SESS_USERID");
				}
			//$body->addItemMenu("newevent", babTranslate("Add Event"), $GLOBALS[babUrl]."index.php?tg=event&idx=newevent&calendarid=0");
		}
		break;
	default:
	case "options":
		$body->title = babTranslate("Calendar Options");
		$idcal = getCalendarid($BAB_SESS_USERID, 1);
		if( (getCalendarId(1, 2) != 0  || getCalendarId(getPrimaryGroupId($BAB_SESS_USERID), 2) != 0) && $idcal != 0 )
		{
			calendarOptions($calid);
			$body->addItemMenu("options", babTranslate("Options"), $GLOBALS[babUrl]."index.php?tg=calopt&idx=options");
			$body->addItemMenu("access", babTranslate("Access"), $GLOBALS[babUrl]."index.php?tg=calopt&idx=access&idcal=".$idcal);
			if( isUserGroupManager())
				{
				$body->addItemMenu("listcat", babTranslate("Categories"), $GLOBALS[babUrl]."index.php?tg=confcals&idx=listcat&userid=$BAB_SESS_USERID");
				$body->addItemMenu("resources", babTranslate("Resources"), $GLOBALS[babUrl]."index.php?tg=confcals&idx=listres&userid=$BAB_SESS_USERID");
				}
			//$body->addItemMenu("newevent", babTranslate("Add Event"), $GLOBALS[babUrl]."index.php?tg=event&idx=newevent&calendarid=0");
		}
		break;
	}
$body->setCurrentItemMenu($idx);

?>