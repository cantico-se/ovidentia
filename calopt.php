<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/


function accessCalendar($calid)
{
	global $babBody;
	
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

		var $vaccname0;
		var $vaccname1;
		var $vaccname2;

		var $db;
		var $res;
		var $count;
		var $arr = array();

		function temp($calid)
			{
			$this->db = $GLOBALS['babDB'];
			$this->calid = $calid;
			$this->userstxt = bab_translate("User");
			$this->textinfo = bab_translate("Add user");
			$this->addusers = bab_translate("Update access");
			$this->useraccess = bab_translate("Access");
			$this->fullname = bab_translate("Fullname");
			$this->accessname = bab_translate("Access");
			$this->delusers = bab_translate("Delete users");
			$this->vaccname0 = bab_translate("Consultation");
			$this->vaccname1 = bab_translate("Creation and modification");
			$this->vaccname2 = bab_translate("Total access");
			$req = "select * from ".BAB_CALACCESS_USERS_TBL." where id_cal='".$calid."'";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			$this->usersbrowurl = $GLOBALS['babUrlScript']."?tg=lusers&idx=brow&cb=";
			}

		function getnext()
			{
			static $k=0;
			if( $k < $this->count)
				{
				$arr = $this->db->db_fetch_array($this->res);
				$req = "select * from ".BAB_USERS_TBL." where id='".$arr['id_user']."'";
				$res = $this->db->db_query($req);
				$this->arr = $this->db->db_fetch_array($res);
				$this->fullnameval = bab_composeUserName($this->arr['firstname'], $this->arr['lastname']);
				$this->userid = $arr['id_user'];
				switch( $arr['bwrite'])
					{
					case 1:
						$this->yesno = $this->vaccname1;
						break;
					case 2:
						$this->yesno = $this->vaccname2;
						break;
					default:
						$this->yesno = $this->vaccname0;
						break;
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

	$temp = new temp($calid);
	$babBody->babecho(	bab_printTemplate($temp,"calopt.html", "access"));
}

function addAccessUsers( $userid, $calid, $baccess)
{

	$db = $GLOBALS['babDB'];
	$req = "select * from ".BAB_CALACCESS_USERS_TBL." where id_cal='".$calid."' and id_user='".$userid."'";
	$res = $db->db_query($req);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$rr = $db->db_fetch_array($res);
		$req = "update ".BAB_CALACCESS_USERS_TBL." set id_user='".$userid."', bwrite='".$baccess."' where id='".$rr['id']."'";
		$res = $db->db_query($req);
		}
	else
		{
		$req = "insert into ".BAB_CALACCESS_USERS_TBL." (id_cal, id_user, bwrite) values ('".$calid."', '".$userid."', '".$baccess."')";
		$res = $db->db_query($req);
		}

}

function delAccessUsers( $users, $calid)
{

	$db = $GLOBALS['babDB'];

	for( $i = 0; $i < count($users); $i++)
		{
		$db->db_query("delete from ".BAB_CALACCESS_USERS_TBL." where id_cal='".$calid."' and id_user='".$users[$i]."'");
		}

}

function calendarOptions($calid)
	{
	global $babBody;

	class temp
		{
		var $startday;
		var $dayid;
		var $dayname;
		var $allday;
		var $ampm;
		var $usebgcolor;
		var $elapstime;
		var $etval;
		var $etselected;
		var $minutes;

		var $modify;
		var $yes;
		var $no;

		function temp($calid)
			{
			global $BAB_SESS_USERID;
			$this->calid = $calid;
			$this->startday = bab_translate("First day of week");
			$this->allday = bab_translate("On create new event, check")." ". bab_translate("All day");
			$this->ampm = bab_translate("Use AM PM");
			$this->usebgcolor = bab_translate("Use bacground color for events");
			$this->modify = bab_translate("Modify");
			$this->yes = bab_translate("Yes");
			$this->no = bab_translate("No");
			$this->elapstime = bab_translate("Echelle du temps");
			$this->minutes = bab_translate("Minutes");
			$db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_CALOPTIONS_TBL." where id_user='".$BAB_SESS_USERID."'";
			$res = $db->db_query($req);
			$this->arr = $db->db_fetch_array($res);
			}

		function getnextday()
			{
			global $babDays;

			static $i = 0;
			if( $i < 7 )
				{
				if( $i == $this->arr['startday'])
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
		function getnextet()
			{
			static $i = 0;
			if( $i < 5 )
				{
				switch($i)
					{
					case 0:
						$this->etval = 5;
						break;
					case 1:
						$this->etval = 10;
						break;
					case 2:
						$this->etval = 15;
						break;
					case 3:
						$this->etval = 30;
						break;
					case 4:
						$this->etval = 60;
						break;
					}

				if( $this->etval == $this->arr['elapstime'])
					$this->etselected = "selected";
				else
					$this->etselected = "";
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
	$babBody->babecho(	bab_printTemplate($temp, "calopt.html", "caloptions"));
	}

function updateCalOptions($startday, $allday, $ampm, $usebgcolor, $elapstime)
	{
	global $BAB_SESS_USERID;
	$db = $GLOBALS['babDB'];
	$req = "select * from ".BAB_CALOPTIONS_TBL." where id_user='".$BAB_SESS_USERID."'";
	$res = $db->db_query($req);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$req = "update ".BAB_CALOPTIONS_TBL." set startday='".$startday."', allday='".$allday."', ampm='".$ampm."', usebgcolor='".$usebgcolor."', elapstime='".$elapstime."' where id_user='".$BAB_SESS_USERID."'";
		}
	else
		{
		$req = "insert into ".BAB_CALOPTIONS_TBL." ( id_user, startday, allday, ampm, usebgcolor, elapstime) values ";
		$req .= "('".$BAB_SESS_USERID."', '".$startday."', '".$allday."', '".$ampm."', '".$usebgcolor."', '".$elapstime."')";
		}
	$res = $db->db_query($req);

	}

/* main */
if(!isset($idx))
	{
	$idx = "options";
	}


if( isset($accessadd) )
{
	addAccessUsers($userid, $idcal, $baccess);
}
if( isset($accessdel) )
{
	delAccessUsers($users, $idcal);
}
if( isset($modify) && $modify == "options")
	{
	updateCalOptions($startday, $allday, $ampm, $usebgcolor, $elapstime);
	}

switch($idx)
	{
	case "access":
		$babBody->title = bab_translate("Calendar Options");
		$idcal = bab_getCalendarId($BAB_SESS_USERID, 1);
		if( (bab_getCalendarId(1, 2) != 0  || bab_getCalendarId(bab_getPrimaryGroupId($BAB_SESS_USERID), 2) != 0) && $idcal != 0 )
		{
			accessCalendar($idcal);
			$babBody->addItemMenu("options", bab_translate("Options"), $GLOBALS['babUrlScript']."?tg=calopt&idx=options");
			$babBody->addItemMenu("access", bab_translate("Access"), $GLOBALS['babUrlScript']."?tg=options&idx=access&idcal=".$idcal);
			if( bab_isUserGroupManager())
				{
				$babBody->addItemMenu("listcat", bab_translate("Categories"), $GLOBALS['babUrlScript']."?tg=confcals&idx=listcat&userid=$BAB_SESS_USERID");
				$babBody->addItemMenu("resources", bab_translate("Resources"), $GLOBALS['babUrlScript']."?tg=confcals&idx=listres&userid=$BAB_SESS_USERID");
				}
		}
		break;
	default:
	case "options":
		$babBody->title = bab_translate("Calendar Options");
		$idcal = bab_getCalendarId($BAB_SESS_USERID, 1);
		if( (bab_getCalendarId(1, 2) != 0  || bab_getCalendarId(bab_getPrimaryGroupId($BAB_SESS_USERID), 2) != 0) && $idcal != 0 )
		{
			calendarOptions($calid);
			$babBody->addItemMenu("options", bab_translate("Options"), $GLOBALS['babUrlScript']."?tg=calopt&idx=options");
			$babBody->addItemMenu("access", bab_translate("Access"), $GLOBALS['babUrlScript']."?tg=calopt&idx=access&idcal=".$idcal);
			if( bab_isUserGroupManager())
				{
				$babBody->addItemMenu("listcat", bab_translate("Categories"), $GLOBALS['babUrlScript']."?tg=confcals&idx=listcat&userid=$BAB_SESS_USERID");
				$babBody->addItemMenu("resources", bab_translate("Resources"), $GLOBALS['babUrlScript']."?tg=confcals&idx=listres&userid=$BAB_SESS_USERID");
				}
		}
		break;
	}
$babBody->setCurrentItemMenu($idx);

?>