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
include_once $babInstallPath."utilit/mcalincl.php";

function browseUsers($pos, $cb, $idcal)
	{
	global $babBody;
	class temp
		{
		var $fullname;
		var $urlname;
		var $url;
		var $email;
		var $status;
		var $checkall;
		var $uncheckall;
				
		var $fullnameval;
		var $emailval;

		var $arr = array();
		var $db;
		var $count;
		var $res;

		var $pos;

		var $userid;

		var $nickname;

		function temp($pos, $cb, $idcal)
			{
			global $babBody;
			$this->uncheckall = bab_translate("Uncheck all");
			$this->checkall = bab_translate("Check all");
			$this->allname = bab_translate("All");
			$this->nickname = bab_translate("Nickname");
			$this->vaccname0 = bab_translate("Consultation");
			$this->vaccname1 = bab_translate("Creation and modification");
			$this->vaccname2 = bab_translate("Total access");
			$this->useraccess = bab_translate("Access");
			$this->addusers = bab_translate("Update access");
			$this->db = $GLOBALS['babDB'];
			$this->cb = $cb;
			$this->calid = $idcal;
			if( !bab_isUserAdministrator())
				{
				$req = "select ".BAB_GROUPS_TBL.".id from ".BAB_GROUPS_TBL." join ".BAB_USERS_GROUPS_TBL." where id_object='".$GLOBALS['BAB_SESS_USERID']."' and ".BAB_GROUPS_TBL.".id=".BAB_USERS_GROUPS_TBL.".id_group";
				$resgroups = $this->db->db_query($req);

				$reqa = "select distinct ".BAB_USERS_TBL.".id, ".BAB_USERS_TBL.".firstname, ".BAB_USERS_TBL.".lastname, ".BAB_USERS_TBL.".nickname from ".BAB_USERS_TBL." join ".BAB_USERS_GROUPS_TBL." where is_confirmed ='1' and disabled='0'";
				if( $this->db->db_num_rows($resgroups) > 0 )
					{
					$arr = $this->db->db_fetch_array($resgroups);
					$reqa .= " and ( ".BAB_USERS_GROUPS_TBL.".id_group='".$arr['id']."'";
					while($arr = $this->db->db_fetch_array($resgroups))
						{
						$reqa .= " or ".BAB_USERS_GROUPS_TBL.".id_group='".$arr['id']."'"; 
						}
					$reqa .= ") and ".BAB_USERS_GROUPS_TBL.".id_object=".BAB_USERS_TBL.".id";
					}
				}
			else
				$reqa = "select * from ".BAB_USERS_TBL." where is_confirmed ='1' and disabled='0'";

			switch ($babBody->nameorder[0]) {
				case "F":
					$this->namesearch = "firstname";
					$this->namesearch2 = "lastname";
				break;
				case "L":
				default:
					$this->namesearch = "lastname";
					$this->namesearch2 = "firstname";
				break; }

			if( strlen($pos) > 0 && $pos[0] == "-"  )
				{
				$this->pos = strlen($pos)>1? $pos[1]: '';
				$this->ord = $pos[0];
				$reqa .= " and ".$this->namesearch2." like '".$this->pos."%' order by ".$this->namesearch2.", ".$this->namesearch." asc";
				$this->fullname = bab_composeUserName(bab_translate("Lastname"),bab_translate("Firstname"));
				$this->fullnameurl = $GLOBALS['babUrlScript']."?tg=calopt&idx=brow&pos=".$this->pos."&cb=".$this->cb;
				}
			else
				{
				$this->pos = $pos;
				$this->ord = "";
				$reqa .= " and ".$this->namesearch." like '".$this->pos."%' order by ".$this->namesearch.", ".$this->namesearch2." asc";
				$this->fullname = bab_composeUserName(bab_translate("Firstname"), bab_translate("Lastname"));
				$this->fullnameurl = $GLOBALS['babUrlScript']."?tg=calopt&idx=brow&pos=-".$this->pos."&cb=".$this->cb;
				}
			$this->res = $this->db->db_query($reqa);
			$this->count = $this->db->db_num_rows($this->res);

			if( empty($this->pos))
				$this->allselected = 1;
			else
				$this->allselected = 0;
			$this->allurl = $GLOBALS['babUrlScript']."?tg=calopt&idx=brow&pos=&cb=".$this->cb;
			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$this->arr = $this->db->db_fetch_array($this->res);
				$this->url = $GLOBALS['babUrlScript']."?tg=user&idx=Modify&item=".$this->arr['id']."&pos=".$this->ord.$this->pos."&cb=".$this->cb;
				$this->firstlast = bab_composeUserName($this->arr['firstname'],$this->arr['lastname']);
				$this->firstlast = str_replace("'", "\'", $this->firstlast);
				$this->firstlast = str_replace('"', "'+String.fromCharCode(34)+'",$this->firstlast);
				if( $this->ord == "-" )
					$this->urlname = bab_composeUserName($this->arr['lastname'],$this->arr['firstname']);
				else
					$this->urlname = bab_composeUserName($this->arr['firstname'],$this->arr['lastname']);
				$this->userid = $this->arr['id'];
				$this->nicknameval = $this->arr['nickname'];
				$i++;
				return true;
				}
			else
				return false;

			}

		function getnextselect()
			{
			global $BAB_SESS_USERID;
			static $k = 0;
			static $t = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
			if( $k < 26)
				{
				$this->selectname = substr($t, $k, 1);
				$this->selecturl = $GLOBALS['babUrlScript']."?tg=calopt&idx=brow&pos=".$this->ord.$this->selectname."&cb=".$this->cb;

				if( $this->pos == $this->selectname)
					$this->selected = 1;
				else 
					{
					if( $this->ord == "-" )
						$req = "select * from ".BAB_USERS_TBL." where ".$this->namesearch2." like '".$this->selectname."%'";
					else
						$req = "select * from ".BAB_USERS_TBL." where ".$this->namesearch." like '".$this->selectname."%'";
					$res = $this->db->db_query($req);
					if( $this->db->db_num_rows($res) > 0 )
						$this->selected = 0;
					else
						$this->selected = 1;
					}
				$k++;
				return true;
				}
			else
				return false;

			}
		}

	$temp = new temp($pos, $cb, $idcal);
	echo bab_printTemplate($temp, "calopt.html", "browseusers");
	}


function accessCalendar($calid)
{
	global $babBody;
	
	class temp
		{
		function temp($calid)
			{
			$this->db = $GLOBALS['babDB'];
			$this->calid = $calid;
			$this->fullname = bab_translate("Fullname");
			$this->access0txt = bab_translate("Consultation");
			$this->access1txt = bab_translate("Creation and modification");
			$this->access2txt = bab_translate("Full access");
			$this->deletetxt = bab_translate("Delete");
			$this->upduserstxt = bab_translate("Update access");
			$this->usertxt = bab_translate("Add user");
			$this->addtxt = bab_translate("Add");
			$req = "select cut.id_user, cut.bwrite, ut.firstname, ut.lastname from ".BAB_CALACCESS_USERS_TBL." cut left join ".BAB_USERS_TBL." ut on ut.id=cut.id_user where cut.id_cal='".$calid."'";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			$this->usersbrowurl = $GLOBALS['babUrlScript']."?tg=calopt&idx=brow";
			}

		function getnext()
			{
			static $k=0;
			if( $k < $this->count)
				{
				$arr = $this->db->db_fetch_array($this->res);
				$this->fullnameval = bab_composeUserName($arr['firstname'], $arr['lastname']);
				$this->userid = $arr['id_user'];
				switch( $arr['bwrite'])
					{
					case 1:
						$this->cheched0 = "";
						$this->cheched1 = "checked";
						$this->cheched2 = "";
						break;
					case 2:
						$this->cheched0 = "";
						$this->cheched1 = "";
						$this->cheched2 = "checked";
						break;
					default:
						$this->cheched0 = "checked";
						$this->cheched1 = "";
						$this->cheched2 = "";
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

function addAccessUsers( $nuserid, $calid)
{

	$db = $GLOBALS['babDB'];
	if( !empty($nuserid) && $nuserid != $GLOBALS['BAB_SESS_USERID'])
		{
		$req = "select * from ".BAB_CALACCESS_USERS_TBL." where id_cal='".$calid."' and id_user='".$nuserid."'";
		$res = $db->db_query($req);
		if( !$res || $db->db_num_rows($res) == 0)
			{
			$req = "insert into ".BAB_CALACCESS_USERS_TBL." (id_cal, id_user, bwrite) values ('".$calid."', '".$nuserid."', '0')";
			$res = $db->db_query($req);
			}
		}
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=calopt&idx=access");
	exit;
}

function updateAccessUsers( $users, $calid)
{

	$db = $GLOBALS['babDB'];
	$res = $db->db_query("select * from ".BAB_CALACCESS_USERS_TBL." where id_cal='".$calid."'");
	while( $arr = $db->db_fetch_array($res))
	{
		if( count($users) > 0 && in_array($arr['id_user'], $users))
		{
			$db->db_query("delete from ".BAB_CALACCESS_USERS_TBL." where id_cal='".$calid."' and id_user='".$arr['id_user']."'");
		}
		else
		{
			$opt = 'acc_'.$arr['id_user'];
			if( isset($GLOBALS[$opt]) )
			{
				$db->db_query("update ".BAB_CALACCESS_USERS_TBL." set bwrite='".$GLOBALS[$opt]."' where id_cal='".$calid."' and id_user='".$arr['id_user']."'");
			}
		}

	}
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=calopt&idx=access");
	exit;
}

function calendarOptions($calid)
	{
	global $babBody;

	class temp
		{
		function temp($calid)
			{
			global $BAB_SESS_USERID;
			$this->calid = $calid;
			$this->calweekworktxt = bab_translate("Calendar work week");
			$this->caloptionstxt = bab_translate("Calendar options");
			$this->startdaytxt = bab_translate("First day of week");
			$this->starttimetxt = bab_translate("Start time");
			$this->endtimetxt = bab_translate("End time");
			$this->allday = bab_translate("On create new event, check")." ". bab_translate("All day");
			$this->usebgcolor = bab_translate("Use background color for events");
			$this->weeknumberstxt = bab_translate("Show week numbers");
			$this->modify = bab_translate("Modify");
			$this->yes = bab_translate("Yes");
			$this->no = bab_translate("No");
			$this->elapstime = bab_translate("Time scale");
			$this->minutes = bab_translate("Minutes");
			$this->defaultview = bab_translate("Calendar default view");
			$db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_CAL_USER_OPTIONS_TBL." where id_user='".$BAB_SESS_USERID."'";
			$res = $db->db_query($req);
			$this->arr = $db->db_fetch_array($res);
			$this->arrdv = array(bab_translate("Month"), bab_translate("Week"),bab_translate("Day"));
			$this->arrdvw = array(bab_translate("Columns"), bab_translate("Rows"));
			if( empty($this->arr['start_time']))
				{
				$this->arr['start_time'] = "08:00:00";
				}
			if( empty($this->arr['end_time']))
				{
				$this->arr['end_time'] = "18:00:00";
				}
			if( empty($this->arr['startday']))
				{
				$this->arr['startday'] = 3;
				}
			if( empty($this->arr['defaultview']))
				{
				$this->arr['defaultview'] = BAB_CAL_VIEW_MONTH;
				}
			if( empty($this->arr['elapstime']))
				{
				$this->arr['elapstime'] = 60;
				}

			if( empty($this->arr['work_days']))
				{
				$this->arr['work_days'] = "1,2,3,4,5";
				}
			$this->workdays = explode(',', $this->arr['work_days']);
			$this->sttime = $this->arr['start_time'];
			}

		function getnextshortday()
			{
			global $babDays;

			static $i = 0;
			if( $i < 7 )
				{
				if( in_array($i, $this->workdays))
					$this->selected = "checked";
				else
					$this->selected = "";
				$this->dayid = $i;
				$this->shortday = substr($babDays[$i], 0, 3);
				$i++;
				return true;
				}
			else
				{
				$i = 0;
				return false;
				}

			}

		function getnexttime()
			{
			static $i = 0;
			if( $i < 24 )
				{
				$this->timeid = sprintf("%02s:00:00", $i);
				$this->timeval = substr($this->timeid, 0, 2);
				if( $this->timeid == $this->sttime)
					{
					$this->selected = "selected";
					}
				else
					{
					$this->selected = "";
					}
				$i++;
				return true;
				}
			else
				{
				$this->sttime = $this->arr['end_time'];
				$i = 0;
				return false;
				}

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

		function getnextdv()
			{
			static $i = 0;
			if( $i < count($this->arrdv) )
				{
				if( $i == $this->arr['defaultview'])
					$this->dvselected = "selected";
				else
					$this->dvselected = "";
				$this->dvvalid = $i;
				$this->dvval = $this->arrdv[$i];		
				$i++;
				return true;
				}
			else
				{
				$i = 0;
				return false;
				}

			}

		function getnextdvw()
			{
			static $i = 0;
			if( $i < count($this->arrdvw) )
				{
				if( $i == $this->arr['defaultviewweek'])
					$this->dvselected = "selected";
				else
					$this->dvselected = "";
				$this->dvvalid = $i;
				$this->dvval = $this->arrdvw[$i];		
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


function pop_calendarchoice()
	{
	global $babBodyPopup;
	class temp
		{
		function temp()
			{
			$this->backurl = $_GET['backurl'];
			$this->calendars = calendarchoice('calendarchoice');
			$this->t_record = bab_translate("Record");
			}
		}
	$temp = new temp();
	$babBodyPopup->babecho(	bab_printTemplate($temp,"calopt.html", "calendarchoice"));

	}

function unload()
	{
	global $babBodyPopup;
	class temp
		{
		function temp()
			{
			$selected = isset($_POST['selected_calendars']) ? $_POST['selected_calendars'] : array();
			$this->backurl = $_POST['backurl'].implode(',',$selected);
			$this->message = bab_translate("Successful recording");
			}
		}
	$temp = new temp();
	$babBodyPopup->babecho(	bab_printTemplate($temp,"calopt.html", "unload"));

	}

function updateCalOptions($startday, $starttime, $endtime, $allday, $usebgcolor, $elapstime, $defaultview, $workdays, $useweeknb)
	{
	global $BAB_SESS_USERID;
	$db = $GLOBALS['babDB'];

	if( count($workdays) == 0 )
		{
		$workdays = "1,2,3,4,5";
		}
	else
		{
		$workdays = implode(',', $workdays);
		}

	if( $starttime > $endtime )
		{
		$tmp = $starttime;
		$starttime = $endtime;
		$endtime = $tmp;
		}

	$req = "select * from ".BAB_CAL_USER_OPTIONS_TBL." where id_user='".$BAB_SESS_USERID."'";
	$res = $db->db_query($req);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$req = "update ".BAB_CAL_USER_OPTIONS_TBL." set startday='".$startday."', allday='".$allday."', start_time='".$starttime."', end_time='".$endtime."', usebgcolor='".$usebgcolor."', elapstime='".$elapstime."', defaultview='".$defaultview."', work_days='".$workdays."', week_numbers='".$useweeknb."' where id_user='".$BAB_SESS_USERID."'";
		}
	else
		{
		$req = "insert into ".BAB_CAL_USER_OPTIONS_TBL." ( id_user, startday, allday, start_time, end_time, usebgcolor, elapstime, defaultview, work_days, week_numbers) values ";
		$req .= "('".$BAB_SESS_USERID."', '".$startday."', '".$allday."', '".$starttime."', '".$endtime."', '".$usebgcolor."', '".$elapstime."', '".$defaultview."', '".$workdays."', '".$useweeknb."')";
		}
	$res = $db->db_query($req);
	}

/* main */
if(!isset($idx))
	{
	$idx = "options";
	}

if( isset($add) && $add == "addu" && $idcal == bab_getCalendarId($BAB_SESS_USERID, 1))
{
	addAccessUsers($nuserid, $idcal);
}elseif( isset($update) && $update == "access" && $idcal == bab_getCalendarId($BAB_SESS_USERID, 1))
{
	if( !isset($users)) { $users = array();}
	updateAccessUsers($users, $idcal);
}elseif( isset($modify) && $modify == "options" && $BAB_SESS_USERID != '')
	{
	if( !isset($workdays)) { $workdays = array();}
	updateCalOptions($startday, $starttime, $endtime, $allday, $usebgcolor, $elapstime, $defaultview, $workdays, $useweeknb);
	}

switch($idx)
	{
	case "brow":
		$idcal = bab_getCalendarId($BAB_SESS_USERID, 1);
		if( $idcal != 0 )
			{
			if( !isset($pos)) { $pos = '';}
			if( !isset($cb)) { $cb = '';}
			browseUsers($pos, $cb, $idcal);
			}
		exit;
		break;

	case "pop_calendarchoice":
		include_once $babInstallPath."utilit/uiutil.php";
		$babBodyPopup = new babBodyPopup();
		pop_calendarchoice();
		printBabBodyPopup();
		exit;
		break;

	case "unload":
		record_calendarchoice();
		include_once $babInstallPath."utilit/uiutil.php";
		$babBodyPopup = new babBodyPopup();
		unload();
		printBabBodyPopup();
		exit;
		break;

	case "access":
		$babBody->title = bab_translate("Calendar Options");
		$idcal = bab_getCalendarId($BAB_SESS_USERID, 1);
		if( $idcal != 0 )
		{
			accessCalendar($idcal);
			$babBody->addItemMenu("options", bab_translate("Options"), $GLOBALS['babUrlScript']."?tg=calopt&idx=options");
			$babBody->addItemMenu("access", bab_translate("Access"), $GLOBALS['babUrlScript']."?tg=options&idx=access&idcal=".$idcal);
		}
		else
			$babBody->title = bab_translate("Access denied");
		break;
	default:
	case "options":
		$babBody->title = bab_translate("Calendar Options");
		$idcal = bab_getCalendarId($BAB_SESS_USERID, 1);
		if( $idcal != 0 || $babBody->calaccess || bab_calendarAccess() != 0 )
		{
			calendarOptions($idcal);
			$babBody->addItemMenu("options", bab_translate("Options"), $GLOBALS['babUrlScript']."?tg=calopt&idx=options");
			if( $idcal != 0 )
				$babBody->addItemMenu("access", bab_translate("Access"), $GLOBALS['babUrlScript']."?tg=calopt&idx=access&idcal=".$idcal);
		}
		break;
	}
$babBody->setCurrentItemMenu($idx);
?>