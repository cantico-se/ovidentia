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
include_once 'base.php';
include_once $babInstallPath.'utilit/mcalincl.php';


function accessCalendar($calid, $urla)
{
	global $babBody;
	
	class temp
		{
		function temp($calid, $urla)
			{
			global $babDB;
			$this->calid = $calid;
			$this->urla = $urla;
			$this->fullname = bab_translate("Fullname");
			$this->access0txt = bab_translate("Consultation");
			$this->access1txt = bab_translate("Creation and modification");
			$this->access11txt = bab_translate("Shared creation and modification");
			$this->access2txt = bab_translate("Full access");
			$this->access22txt = bab_translate("Shared full access");
			$this->deletetxt = bab_translate("Delete");
			$this->upduserstxt = bab_translate("Update access");
			$this->usertxt = bab_translate("Add user");
			$this->addtxt = bab_translate("Add");
			$req = "select cut.id_user, cut.bwrite, ut.firstname, ut.lastname from ".BAB_CALACCESS_USERS_TBL." cut left join ".BAB_USERS_TBL." ut on ut.id=cut.id_user where cut.id_cal='".$babDB->db_escape_string($calid)."'";

			$res = $babDB->db_query($req);

			$this->arrusers = array();
			while( $arr = $babDB->db_fetch_array($res))
				{
				$this->arrusers[] = array('user'=>bab_composeUserName($arr['firstname'], $arr['lastname']), 'id'=>$arr['id_user'], 'access'=>$arr['bwrite']);
				}
			usort($this->arrusers, array($this, 'compare'));
			$this->count = count($this->arrusers);

			}

		function compare($a, $b)
			{
			return strnatcmp($a['user'],$b['user']);
			}

		function getnext()
			{
			static $k=0;
			if( $k < $this->count)
				{
				$this->fullnameval = $this->arrusers[$k]['user'];
				$this->userid = $this->arrusers[$k]['id'];
				$this->cheched0 = '';
				$this->cheched1 = '';
				$this->cheched11 = '';
				$this->cheched2 = '';
				$this->cheched21 = '';
				switch( $this->arrusers[$k]['access'])
					{
					case 1:
						$this->cheched1 = 'checked';
						break;
					case 2:
						$this->cheched2 = 'checked';
						break;
					case 3:
						$this->cheched11 = 'checked';
						break;
					case 4:
						$this->cheched21 = 'checked';
						break;
					default:
						$this->cheched0 = 'checked';
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

	$temp = new temp($calid, $urla);
	$babBody->babecho(	bab_printTemplate($temp,"calopt.html", "access"));
}

function addAccessUsers( $nuserid, $calid, $urla)
{

	global $babDB;
	if( !empty($nuserid) && $nuserid != $GLOBALS['BAB_SESS_USERID'])
		{
		$req = "select * from ".BAB_CALACCESS_USERS_TBL." where id_cal='".$babDB->db_escape_string($calid)."' and id_user='".$babDB->db_escape_string($nuserid)."'";
		$res = $babDB->db_query($req);
		if( !$res || $babDB->db_num_rows($res) == 0)
			{
			$req = "insert into ".BAB_CALACCESS_USERS_TBL." (id_cal, id_user, bwrite) values ('".$babDB->db_escape_string($calid)."', '".$babDB->db_escape_string($nuserid)."', '0')";
			$res = $babDB->db_query($req);
			}
		}
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=calopt&idx=access&urla=".urlencode($urla));
	exit;
}

function updateAccessUsers( $users, $calid, $urla)
{

	global $babDB;
	$res = $babDB->db_query("select * from ".BAB_CALACCESS_USERS_TBL." where id_cal='".$babDB->db_escape_string($calid)."'");
	while( $arr = $babDB->db_fetch_array($res))
	{
		if( count($users) > 0 && in_array($arr['id_user'], $users))
		{
			$babDB->db_query("delete from ".BAB_CALACCESS_USERS_TBL." where id_cal='".$babDB->db_escape_string($calid)."' and id_user='".$babDB->db_escape_string($arr['id_user'])."'");
		}
		else
		{
			$opt = 'acc_'.$arr['id_user'];
			if( isset($GLOBALS[$opt]) )
			{
				$babDB->db_query("update ".BAB_CALACCESS_USERS_TBL." set bwrite='".$babDB->db_escape_string($GLOBALS[$opt])."' where id_cal='".$babDB->db_escape_string($calid)."' and id_user='".$babDB->db_escape_string($arr['id_user'])."'");
			}
		}

	}
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=calopt&idx=access&urla=".urlencode($urla));
	exit;
}





function cal_half_working_days($day) {
	include_once $GLOBALS['babInstallPath']."utilit/workinghoursincl.php";

	$arr = bab_getWHours($GLOBALS['BAB_SESS_USERID'], $day);
	$result = array(
		'am' => false,
		'pm' => false
	);

	foreach($arr as $p) {
		list($startHour)	= explode(':',$p['startHour']);
		list($endHour)		= explode(':',$p['endHour']);

		if ($startHour < 12) {
			$result['am'] = true;
		}

		if ($endHour > 12) {
			$result['pm'] = true;
		}
	}

	return $result;
}




function calendarOptions($calid, $urla)
	{
	global $babBody;

	class temp
		{
		function temp($calid, $urla)
			{
			global $babBody, $babDB, $BAB_SESS_USERID;
			$this->calid = $calid;
			$this->urla = $urla;
			$this->calweekdisptxt = bab_translate("Days to display");
			$this->calweekworktxt = bab_translate("Working days");
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
			$this->t_am = bab_translate("Morning");
			$this->t_pm = bab_translate("Afternoon");
			$this->elapstime = bab_translate("Time scale");
			$this->minutes = bab_translate("Minutes");
			$this->defaultview = bab_translate("Calendar default view");
			$this->calweekwork = 'Y' == $GLOBALS['babBody']->babsite['user_workdays'];
			$req = "select * from ".BAB_CAL_USER_OPTIONS_TBL." where id_user='".$babDB->db_escape_string($BAB_SESS_USERID)."'";
			$res = $babDB->db_query($req);
			$this->arr = $babDB->db_fetch_array($res);
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

			if( empty($this->arr['dispdays']))
				{
				$this->arr['dispdays'] = "1,2,3,4,5";
				}

			$this->dispdays = explode(',', $this->arr['dispdays']);
			$this->sttime = $this->arr['start_time'];
			}

		function getnextshortday()
			{
			global $babDays;

			static $i = 0;
			if( $i < 7 )
				{
				$this->disp_selected = in_array($i, $this->dispdays) ? "checked" : "";

				$arr = cal_half_working_days($i);
				$this->work_am = $arr['am'];
				$this->work_pm = $arr['pm'];

				$this->dayid = $i;
				$this->shortday = $babDays[$i];
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

	$temp = new temp($calid, $urla);
	$babBody->babecho(	bab_printTemplate($temp, "calopt.html", "caloptions"));

	//$babBody->addStyleSheet('calopt.css');
	//$babBody->babecho(bab_printTemplate($temp, "calopt.html", "caloptions2"));
	}


function pop_calendarchoice()
	{
	global $babBodyPopup;
	class temp
		{
		function temp()
			{
			
			$this->backurl = str_replace('|','&',$_GET['backurl']);
			$this->calendars = calendarchoice('calendarchoice');
			$this->t_record = bab_translate("Record");
			$this->t_view = bab_translate("View");
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

function updateCalOptions($startday, $starttime, $endtime, $allday, $usebgcolor, $elapstime, $defaultview)
	{
	global $babDB, $BAB_SESS_USERID;

	$dispdays = isset($_POST['dispdays']) ? $_POST['dispdays'] : array();
	$dispdays = ( count($dispdays) > 0 ) ? implode(',', $dispdays) : "1,2,3,4,5" ;

	if( $starttime > $endtime )
		{
		$tmp = $starttime;
		$starttime = $endtime;
		$endtime = $tmp;
		}

	$req = "select * from ".BAB_CAL_USER_OPTIONS_TBL." where id_user='".$babDB->db_escape_string($BAB_SESS_USERID)."'";
	$res = $babDB->db_query($req);
	if( $res && $babDB->db_num_rows($res) > 0)
		{
		$req = "UPDATE ".BAB_CAL_USER_OPTIONS_TBL." SET 
			startday	=".$babDB->quote($startday).", 
			allday		=".$babDB->quote($allday).", 
			start_time	=".$babDB->quote($starttime).", 
			end_time	=".$babDB->quote($endtime).", 
			usebgcolor	=".$babDB->quote($usebgcolor).", 
			elapstime	=".$babDB->quote($elapstime).", 
			defaultview	=".$babDB->quote($defaultview).", 
			dispdays	=".$babDB->quote($dispdays).", 
			week_numbers='Y' 
		WHERE 
			id_user=".$babDB->quote($BAB_SESS_USERID)."
			";
		}
	else
		{
		$req = "insert into ".BAB_CAL_USER_OPTIONS_TBL." 
			( 
				id_user, 
				startday, 
				allday, 
				start_time, 
				end_time, 
				usebgcolor, 
				elapstime, 
				defaultview, 
				dispdays, 
				week_numbers
			) 
		VALUES ";

		$req .= "(
			".$babDB->quote($BAB_SESS_USERID).", 
			".$babDB->quote($startday).",
			".$babDB->quote($allday).",
			".$babDB->quote($starttime).",
			".$babDB->quote($endtime).",
			".$babDB->quote($usebgcolor).",
			".$babDB->quote($elapstime).",
			".$babDB->quote($defaultview).",
			".$babDB->quote($dispdays).",
			'Y')
		";
		}
	$res = $babDB->db_query($req);


	function setUserPeriod($day, $ampm, $startHour, $endHour, $insert) {
		global $babDB;
		$op = 'am' == $ampm ? 'startHour <=' : 'endHour >';
		$babDB->db_query("

			DELETE FROM ".BAB_WORKING_HOURS_TBL." 
			WHERE 
				idUser=".$babDB->quote($GLOBALS['BAB_SESS_USERID'])." AND 
				weekDay=".$babDB->quote($day)." AND 
				".$op." '12:00:00' 
		");


		if ($insert) {

			$babDB->db_query("
				INSERT INTO ".BAB_WORKING_HOURS_TBL." 
					(weekDay, idUser, startHour, endHour) 
					VALUES 
					(".$babDB->quote($day).",
					".$babDB->quote($GLOBALS['BAB_SESS_USERID']).",
					".$babDB->quote($startHour).", 
					".$babDB->quote($endHour).") 
				");
		}
	}



	$am_startHour	= '00:00:00';
	$am_endHour		= '12:00:00';

	$pm_startHour	= '12:00:00';
	$pm_endHour		= '24:00:00';


	list($tmp) = explode(':', $starttime);
	if ($tmp < 12) {
		$am_startHour = $starttime;
	}

	list($tmp) = explode(':', $endtime);
	if ($tmp > 12) {
		$pm_endHour = $endtime;
	}
	

	$res = $babDB->db_query("
				SELECT COUNT(*) FROM ".BAB_WORKING_HOURS_TBL." 
					WHERE idUser=".$babDB->quote($GLOBALS['BAB_SESS_USERID'])." 
				");

	list($user_nb_rows) = $babDB->db_fetch_array($res);


	$change = false; 
	
	for ($i = 0 ; $i < 7 ; $i++) {
		$arr = cal_half_working_days($i);
		
		$am = isset($_POST['work'][$i]['am']);
		$pm = isset($_POST['work'][$i]['pm']);

		if ($arr['am'] != $am || 0 == $user_nb_rows) {
			setUserPeriod($i, 'am', $am_startHour, $am_endHour, $am);
			$change = true;
		}

		if ($arr['pm'] != $pm || 0 == $user_nb_rows) {
			setUserPeriod($i, 'pm', $pm_startHour, $pm_endHour, $pm);
			$change = true;
		}
	}

	
	include_once $GLOBALS['babInstallPath'].'utilit/vacincl.php';
	bab_vac_clearUserCalendar();
	


	header('location:'.$GLOBALS['babUrlScript']."?tg=calopt&idx=options");
	exit;
}

/* main */
if(!isset($idx))
	{
	$idx = "options";
	}

if(!isset($urla))
	{
	$urla = "";
	}

if( isset($add) && $add == "addu" && $idcal == bab_getCalendarId($BAB_SESS_USERID, 1))
{
	addAccessUsers($nuserid, $idcal, $urla);
}elseif( isset($update) && $update == "access" && $idcal == bab_getCalendarId($BAB_SESS_USERID, 1))
{
	if( !isset($users)) { $users = array();}
	updateAccessUsers($users, $idcal, $urla);
}elseif( isset($modify) && $modify == "options" && $BAB_SESS_USERID != '')
	{
	updateCalOptions($_POST['startday'], $_POST['starttime'], $_POST['endtime'], $_POST['allday'], $_POST['usebgcolor'], $_POST['elapstime'], $_POST['defaultview'] );
	}

$babBody->addItemMenu("global", bab_translate("Options"), $GLOBALS['babUrlScript']."?tg=options&idx=global");

switch($idx)
	{

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
		if( $babBody->icalendars->id_percal != 0 )
		{
			if (!isset($idcal))
				$idcal = $babBody->icalendars->id_percal;
			
			accessCalendar($idcal, $urla);
			$babBody->addItemMenu("options", bab_translate("Calendar Options"), $GLOBALS['babUrlScript']."?tg=calopt&idx=options&urla=".urlencode($urla));
			$babBody->addItemMenu("access", bab_translate("Calendar access"), $GLOBALS['babUrlScript']."?tg=options&idx=access&idcal=".$idcal);
			if( isset($urla) && !empty($urla) )
				{
				$babBody->addItemMenu("cal", bab_translate("Calendar"), urldecode($urla));
				}
		}
		else
			$babBody->title = bab_translate("Access denied");
		break;
	default:
	case "options":
		$babBody->title = bab_translate("Calendar and Vacations Options");
		$idcal = $babBody->icalendars->id_percal;

		calendarOptions($idcal, $urla);

		
		$babBody->addItemMenu("options", bab_translate("Calendar Options"), $GLOBALS['babUrlScript']."?tg=calopt&idx=options");

		if( $idcal != 0 )
			{
			$babBody->addItemMenu("access", bab_translate("Calendar access"), $GLOBALS['babUrlScript']."?tg=calopt&idx=access&idcal=".$idcal."&urla=".urlencode($urla));	
			}

		if( isset($urla) && !empty($urla) && $babBody->icalendars->calendarAccess() )
			{
			$babBody->addItemMenu("cal", bab_translate("Calendar"), urldecode($urla));
			}
		break;
	}

$babBody->setCurrentItemMenu($idx);
?>