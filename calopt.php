<?php
/************************************************************************
 * Ovidentia                                                            *
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ************************************************************************
 * This program is free software; you can redistribute it and/or modify *
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
			$this->userstxt = bab_translate("Users");
			$this->textinfo = bab_translate("Enter user name. ( You can enter multiple users separated by comma )");
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

function addAccessUsers( $users, $calid, $baccess, $del )
{

	$db = $GLOBALS['babDB'];
	$arr = explode(",", $users);

	for( $i = 0; $i < count($arr); $i++)
		{
		$iduser = bab_getUserId($arr[$i]);
		if( $iduser > 0)
			{
			$req = "select * from ".BAB_CALACCESS_USERS_TBL." where id_cal='".$calid."' and id_user='".$iduser."'";
			$res = $db->db_query($req);
			if( $res && $db->db_num_rows($res) > 0)
				{
				$rr = $db->db_fetch_array($res);
				if( $del )
					$req = "delete from ".BAB_CALACCESS_USERS_TBL." where id='".$rr['id']."'";
				else
					$req = "update ".BAB_CALACCESS_USERS_TBL." set id_user='".$iduser."', bwrite='".$baccess."' where id='".$rr['id']."'";
				$res = $db->db_query($req);
				}
			else if($del == false)
				{
				$req = "insert into ".BAB_CALACCESS_USERS_TBL." (id_cal, id_user, bwrite) values ('".$calid."', '".$iduser."', '".$baccess."')";
				$res = $db->db_query($req);
				}
			}
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
		var $viewcateg;
		var $usebgcolor;

		var $modify;
		var $yes;
		var $no;

		function temp($calid)
			{
			global $BAB_SESS_USERID;
			$this->calid = $calid;
			$this->startday = bab_translate("First day of week");
			$this->allday = bab_translate("On create new event, check")." ". bab_translate("All day");
			$this->viewcateg = bab_translate("View calendar categories");
			$this->usebgcolor = bab_translate("Use bacground color for events");
			$this->modify = bab_translate("Modify");
			$this->yes = bab_translate("Yes");
			$this->no = bab_translate("No");
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
		}

	$temp = new temp($calid);
	$babBody->babecho(	bab_printTemplate($temp, "calopt.html", "caloptions"));
	}

function updateCalOptions($startday, $allday, $viewcat, $usebgcolor)
	{
	global $BAB_SESS_USERID;
	$db = $GLOBALS['babDB'];
	$req = "select * from ".BAB_CALOPTIONS_TBL." where id_user='".$BAB_SESS_USERID."'";
	$res = $db->db_query($req);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$req = "update ".BAB_CALOPTIONS_TBL." set startday='$startday', allday='$allday', viewcat='$viewcat', usebgcolor='$usebgcolor' where id_user='$BAB_SESS_USERID'";
		}
	else
		{
		$req = "insert into ".BAB_CALOPTIONS_TBL." ( id_user, startday, allday, viewcat, usebgcolor) values ";
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
}

if( isset($modify) && $modify == "options")
	{
	updateCalOptions($startday, $allday, $viewcat, $usebgcolor);
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
