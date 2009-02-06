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

/**
* @internal SEC1 PR 20/02/2007 FULL
*/

include_once 'base.php';
include_once $babInstallPath.'utilit/mcalincl.php';


function accessCalendar($calid, $urla)
{
	global $babBody, $babDB;
	
	include_once $GLOBALS['babInstallPath'].'utilit/selectcalendarusers.php';
	$obj = new bab_selectCalendarUsers();
	$obj->addVar('urla', $urla);
	$obj->addVar('calid', $calid);
	
	$obj->addVar('sCleanSessVar', (null == bab_rp('sCleanSessVar', null) ? 'Y' : 'N'));
	
	$obj->setRecordCallback('addAccessUsers');
	$obj->setRecordLabel(bab_translate('Update'));
	
	$sAction = bab_rp('act', null);
	if(is_null($sAction))
	{
		$sQuery = 
			'SELECT 
				cut.id_user, 
				cut.bwrite, 
				ut.firstname, 
				ut.lastname  
			FROM ' . 
				BAB_CALACCESS_USERS_TBL . ' cut 
			LEFT JOIN ' . 
				BAB_USERS_TBL . ' ut on ut.id = cut.id_user 
			WHERE 
				cut.id_cal = ' . $babDB->quote($calid);
	
		$oResult = $babDB->db_query($sQuery);

		while(false !== ($aDatas = $babDB->db_fetch_assoc($oResult)))
		{
			$obj->addUser($aDatas['id_user'], $aDatas['bwrite']);
		}
	}
	
	$babBody->addStyleSheet('calopt.css');
	$babBody->addJavascriptFile($GLOBALS['babScriptPath'].'prototype/prototype.js');
	$babBody->addJavascriptFile($GLOBALS['babScriptPath'].'proto.menu.js');
	$babBody->addJavascriptFile($GLOBALS['babScriptPath'].'ovidentia.js');
	$babBody->addStyleSheet('proto.menu.css');
	$babBody->babecho($obj->getHtml()); 
}


function addAccessUsers($aCalUserAccess, $iIdCalendar)
{
	global $BAB_SESS_USERID;
	if((int) $iIdCalendar !== (int) bab_getCalendarId($BAB_SESS_USERID, 1))
	{
		return;
	}

	global $babDB;
	
	foreach($aCalUserAccess as $iAccess => $sArrayName)
	{
		$sQuery = 'DELETE FROM ' . BAB_CALACCESS_USERS_TBL . ' WHERE id_cal = ' . $babDB->quote($iIdCalendar) . ' AND bwrite = ' . $babDB->quote($iAccess);
		//bab_debug($sQuery);
		$babDB->db_query($sQuery);
			
		if(0 !== count($aCalUserAccess[$iAccess]))
		{
			$sQuery = 
				'SELECT ' . 
					'u.id iIdUser ' .
				'FROM ' . 
					BAB_USERS_TBL . ' u ' .
				'WHERE ' . 
					'u.id IN(' . $babDB->quote($aCalUserAccess[$iAccess]) . ') AND ' . 
					'u.disabled=\'0\'';
			//bab_debug($sQuery);
					
			$oResult = $babDB->db_query($sQuery);
			if(false !== $oResult && $babDB->db_num_rows($oResult) > 0)
			{
				while(false !== ($aDatas = $babDB->db_fetch_assoc($oResult)))
				{
					if((int) $aDatas['iIdUser'] === (int) $GLOBALS['BAB_SESS_USERID'])
					{
						continue;
					}
					
					$sQuery = 
						'INSERT INTO ' . BAB_CALACCESS_USERS_TBL . ' ' .
							'(' .
								'`id`, ' .
								'`id_cal`, `id_user`, `bwrite`' .
							') ' .
						'VALUES ' . 
							'(\'\', ' . 
								$babDB->quote($iIdCalendar) . ', ' . 
								$babDB->quote($aDatas['iIdUser']) . ', ' . 
								$babDB->quote($iAccess) . 
							')';
								
					//bab_debug($sQuery);			
					$babDB->db_query($sQuery);
				} 
			}
		}
	}
	Header("Location:".$GLOBALS['babUrlScript'].'?tg=calopt&idx=access&urla='.urlencode(bab_rp('urla')));
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
			var $t_defaultCalAccess		= '';
			var $aCalAccess				= array();
			var $iCalAccess				= -1;
			var $sCalAccess				= '';
			var $sCalAccessSelected		= '';
			var $iSelectedCalAccess		= -1;
			
		function temp($calid, $urla)
			{
			include_once $GLOBALS['babInstallPath']."utilit/workinghoursincl.php";
			global $babBody, $babDB, $BAB_SESS_USERID;
			$this->calid = $calid;
			$this->urla = bab_toHtml($urla);
			$this->calweekdisptxt = bab_translate("Days to display");
			$this->calweekworktxt = bab_translate("Working days");
			$this->t_working_hours = bab_translate("Define working hours");
			$this->t_define_working_halfdays = bab_translate("Working half-days interface");
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
			$this->t_dispday = bab_translate("Display this day in the calendar");
			$this->calweekwork = 'Y' == $GLOBALS['babBody']->babsite['user_workdays'];
			$this->showupdateinfo = bab_translate("Show the date and the author of the updated event");
			$req = "select * from ".BAB_CAL_USER_OPTIONS_TBL." where id_user='".$babDB->db_escape_string($BAB_SESS_USERID)."'";
			$res = $babDB->db_query($req);
			$this->arr = $babDB->db_fetch_assoc($res);
			
			$this->t_defaultCalAccess = bab_translate("Default calendar access for new user");

			$this->aCalAccess = array(
				BAB_CAL_ACCESS_NONE => bab_translate("None"),
				BAB_CAL_ACCESS_VIEW => bab_translate("Consultation"), 
				BAB_CAL_ACCESS_UPDATE => bab_translate("Creation and modification"), 
				BAB_CAL_ACCESS_FULL => bab_translate("Full access"), 
				BAB_CAL_ACCESS_SHARED_UPDATE => bab_translate("Shared creation and modification"),
				BAB_CAL_ACCESS_SHARED_FULL => bab_translate("Shared full access"));
				
			if(is_array($this->arr) && array_key_exists('iDefaultCalendarAccess', $this->arr))
			{
				if(null !== $this->arr['iDefaultCalendarAccess'])
				{
					$this->iSelectedCalAccess = $this->arr['iDefaultCalendarAccess'];
				}
			}
				
			$this->halfday = bab_rp('halfday',1);
			
			$this->arrdv = array(bab_translate("Month"), bab_translate("Week"),bab_translate("Day"));
			$this->arrdvw = array(bab_translate("Columns"), bab_translate("Rows"));
			if( empty($this->arr['start_time']))
				{
				$this->arr['start_time'] = $babBody->babsite['start_time'];
				}
			if( empty($this->arr['end_time']))
				{
				$this->arr['end_time'] = $babBody->babsite['end_time'];
				}
			if( !isset($this->arr['startday']))
				{
				$this->arr['startday'] = $babBody->babsite['startday'];
				}
			if( empty($this->arr['defaultview']))
				{
				$this->arr['defaultview'] = $babBody->babsite['defaultview'];
				}
			if( empty($this->arr['elapstime']))
				{
				$this->arr['elapstime'] = $babBody->babsite['elapstime'];
				}

			if( empty($this->arr['dispdays']))
				{
				$this->arr['dispdays'] = $babBody->babsite['dispdays'];
				}
				
			if( !isset($this->arr['bgcolor']))
				{
				$this->arr['bgcolor'] = $babBody->babsite['usebgcolor'];
				}
				
			if( !isset($this->arr['allday']))
				{
				$this->arr['allday'] = $babBody->babsite['allday'];
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

				$this->dayid = $i;
				$this->shortday = bab_toHtml($babDays[$i]);


				if ($this->halfday) {
				
					$arr = cal_half_working_days($i);
					$this->work_am = $arr['am'];
					$this->work_pm = $arr['pm'];

				} else {
					$arr = bab_getWHours($GLOBALS['BAB_SESS_USERID'], $i);
					$tmp = array();
					foreach($arr as $p) {
						unset($p['weekDay']);
						$tmp[] = implode('-',$p);
					}
					$this->workinghours = implode(',',$tmp);
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

		function getnexttime()
			{
			static $i = 0;
			if( $i < 24 )
				{
				$this->timeid = sprintf("%02s:00:00", $i);
				$this->timeval = mb_substr($this->timeid, 0, 2);
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

		function getNextCalAccess()
			{
				$this->sCalAccessSelected = '';

				$aCalAccessItem = each($this->aCalAccess);
				if(false !== $aCalAccessItem)
				{
					$this->iCalAccess = $aCalAccessItem['key'];
					$this->sCalAccess = $aCalAccessItem['value'];
					
					if($this->iSelectedCalAccess == $this->iCalAccess)
					{
						$this->sCalAccessSelected = 'selected="selected"';
					}			
					//bab_debug($aCalAccessItem);
					return true;
				}
				return false;
			}
		}

	$temp = new temp($calid, $urla);
	
		if ($temp->halfday) {
			$babBody->babecho(	bab_printTemplate($temp, "calopt.html", "caloptions"));
		} else {
	
			$babBody->addStyleSheet('calopt.css');
			$babBody->babecho(bab_printTemplate($temp, "calopt.html", "caloptions2"));
		}
	}


function pop_calendarchoice()
	{
	global $babBodyPopup;
	class temp
		{
		function temp()
			{
			$this->backurl = bab_toHtml($_GET['backurl']);
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
			$this->backurl = bab_toHtml($_POST['backurl'].implode(',',$selected), BAB_HTML_JS);
			$this->message = bab_translate("Successful recording");
			}
		}
	$temp = new temp();
	$babBodyPopup->babecho(	bab_printTemplate($temp,"calopt.html", "unload"));

	}

function updateCalOptions($startday, $starttime, $endtime, $allday, $usebgcolor, $elapstime, $defaultview, $showupdateinfo, $iDefaultCalendarAccess)
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
			show_update_info =".$babDB->quote($showupdateinfo).", 
			iDefaultCalendarAccess =".$babDB->quote($iDefaultCalendarAccess).", 
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
				show_update_info, 
				iDefaultCalendarAccess,
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
			".$babDB->quote($showupdateinfo).",
			".$babDB->quote($iDefaultCalendarAccess).",
			'Y'
			)
		";
		}
	$res = $babDB->db_query($req);


	function setUserPeriod($day, $ampm, $startHour, $endHour, $insert) {
		global $babDB;
		$op = 'am' == $ampm ? 'endHour <=' : 'endHour >';
		$req = "
			DELETE FROM ".BAB_WORKING_HOURS_TBL." 
			WHERE 
				idUser=".$babDB->quote($GLOBALS['BAB_SESS_USERID'])." AND 
				weekDay=".$babDB->quote($day)." AND 
				".$op." '12:00:00' 
		";
		
		bab_debug($req);
		
		$babDB->db_query($req);


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
	
	
	function setHourFormat($hours) {
		$arr = explode(':',$hours);
		$h = $arr[0];
		$m = isset($arr[1]) ? $arr[1] : 0;
		$s = isset($arr[2]) ? $arr[2] : 0;
		
		return sprintf('%02d:%02d:%02d',$h,$m,$s);
	}
	

	if (1 == bab_rp('halfday')) {

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
	} else {
	
		$change = true;
	
		$req = "
			DELETE FROM ".BAB_WORKING_HOURS_TBL." 
			WHERE 
				idUser=".$babDB->quote($GLOBALS['BAB_SESS_USERID'])."
		";
		$babDB->db_query($req);
		
		$workinghours = bab_pp('workinghours');
		foreach($workinghours as $weekDay => $periods) {
			if (!empty($periods)) {
			$arr = explode(',',$periods); 
			
			foreach($arr as $period) {
			
				$tmp = explode('-',$period);
				
				$begin = setHourFormat($tmp[0]);
				$end = setHourFormat($tmp[1]);
			
			
				$babDB->db_query("
					INSERT INTO ".BAB_WORKING_HOURS_TBL." 
						(weekDay, idUser, startHour, endHour) 
					VALUES 
						(".$babDB->quote($weekDay).",
						".$babDB->quote($GLOBALS['BAB_SESS_USERID']).",
						".$babDB->quote($begin).", 
						".$babDB->quote($end).") 
					");
				}
			}
		}
	}

	if ($change) {
		bab_debug('modification');
		include_once $GLOBALS['babInstallPath'].'utilit/eventperiod.php';
		
		$event = new bab_eventPeriodModified(false, false, $GLOBALS['BAB_SESS_USERID']);
		$event->types = BAB_PERIOD_WORKING;
		bab_fireEvent($event);
	}
	
	$url = $GLOBALS['babUrlScript'].'?tg=calopt&idx=options&halfday='.bab_rp('halfday');
	
	
	header('location:'.$url);
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
}elseif( isset($modify) && $modify == "options" && $BAB_SESS_USERID != '')
	{
	updateCalOptions($_POST['startday'], $_POST['starttime'], $_POST['endtime'], $_POST['allday'], $_POST['usebgcolor'], $_POST['elapstime'], $_POST['defaultview'], $_POST['showupdateinfo'], $_POST['iDefaultCalendarAccess']);
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
	
		if (!bab_isUserLogged()) {
			$babBody->addError('Access denied');
			break;
		}
	
		record_calendarchoice();
		include_once $babInstallPath."utilit/uiutil.php";
		$babBodyPopup = new babBodyPopup();
		unload();
		printBabBodyPopup();
		exit;
		break;

	case "access":
	
		if (!bab_isUserLogged()) {
			$babBody->addError('Access denied');
			break;
		}
		
		$babBody->title = bab_translate("Calendar Options");
		if( bab_getICalendars()->id_percal != 0 )
		{
			accessCalendar(bab_getICalendars()->id_percal, bab_rp('urla'));
			
			$babBody->addItemMenu("options", bab_translate("Calendar Options"), $GLOBALS['babUrlScript']."?tg=calopt&idx=options&urla=".urlencode($urla));
			
			$babBody->addItemMenu("access", bab_translate("Calendar access"), $GLOBALS['babUrlScript']."?tg=options&idx=access&idcal=".bab_getICalendars()->id_percal);
			
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
	
		if (!bab_isUserLogged()) {
			$babBody->addError('Access denied');
			break;
		}
	
		$babBody->title = bab_translate("Calendar and Vacations Options");
		$idcal = bab_getICalendars()->id_percal;

		calendarOptions($idcal, $urla);

		
		$babBody->addItemMenu("options", bab_translate("Calendar Options"), $GLOBALS['babUrlScript']."?tg=calopt&idx=options");

		if( $idcal != 0 )
			{
			$babBody->addItemMenu("access", bab_translate("Calendar access"), $GLOBALS['babUrlScript']."?tg=calopt&idx=access&idcal=".$idcal."&urla=".urlencode($urla));	
			}

		if( isset($urla) && !empty($urla) && bab_getICalendars()->calendarAccess() )
			{
			$babBody->addItemMenu("cal", bab_translate("Calendar"), $urla);
			}
		break;
	}

$babBody->setCurrentItemMenu($idx);
?>