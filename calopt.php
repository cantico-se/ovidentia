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
require_once dirname(__FILE__).'/utilit/registerglobals.php';
include_once $babInstallPath.'utilit/mcalincl.php';


function accessCalendar($calendar, $urla)
{
	global $babBody, $babDB;
	
	
	include_once $GLOBALS['babInstallPath'].'utilit/selectcalendarusers.php';
	$obj = new bab_selectCalendarUsers();
	$obj->addVar('urla', $urla);
	$obj->addVar('calid', $calendar->getUid());
	
	$obj->addVar('sCleanSessVar', (null == bab_rp('sCleanSessVar', null) ? 'Y' : 'N'));
	
	$obj->setRecordCallback('addAccessUsers');
	$obj->setRecordLabel(bab_translate('Update'));
	
	$sAction = bab_rp('act', null);
	if(is_null($sAction))
	{
		foreach(array(BAB_CAL_ACCESS_VIEW, BAB_CAL_ACCESS_UPDATE, BAB_CAL_ACCESS_SHARED_UPDATE, BAB_CAL_ACCESS_FULL) as $accessType)
		{
			$arr = $calendar->getAccessGrantedUsers($accessType);
			foreach($arr as $id_user)
			{
				$obj->addUser($id_user, $accessType);
			}
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
	
	$personal = bab_getICalendars()->getPersonalCalendar();
	if((int) $iIdCalendar !== (int) $personal->getUid())
	{
		return;
	}

	global $babDB;
	
	foreach($aCalUserAccess as $iAccess => $sArrayName)
	{
		$selected = $personal->getAccessGrantedUsers($iAccess);
		$personal->revokeAccess($iAccess, $selected);
			
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
					
					$personal->grantAccess($iAccess, $aDatas['iIdUser']);
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



/**
 * 
 * @param int $calid
 * @param string $urla
 * @return unknown_type
 */
function calendarOptions($urla)
	{
	require_once dirname(__FILE__).'/utilit/dateTime.php';
	global $babBody;
	


	class temp
		{
			var $t_defaultCalAccess		= '';
			var $aCalAccess				= array();
			var $iCalAccess				= -1;
			var $sCalAccess				= '';
			var $sCalAccessSelected		= '';
			var $iSelectedCalAccess		= -1;
			
		function temp($urla)
			{
			include_once $GLOBALS['babInstallPath']."utilit/workinghoursincl.php";
			global $babBody, $babDB, $BAB_SESS_USERID;
			
			$this->urla = bab_toHtml($urla);
			$this->calweekdisptxt = bab_translate("Days to display");
			$this->calweekworktxt = bab_translate("Working days");
			$this->t_working_hours = bab_translate("Define working hours");
			$this->t_define_working_halfdays = bab_translate("Working half-days interface");
			$this->caloptionstxt = bab_translate("Calendar options");
			$this->startdaytxt = bab_translate("First day of week");
			$this->starttimetxt = bab_translate("Start time");
			$this->endtimetxt = bab_translate("End time");
			$this->allday = bab_translate("On create new event, check all day");
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
			$this->showonlydaysmonthinfo = bab_translate("In month view, display only the days of current month");
			$this->t_calendar_backend = bab_translate('Personal calendar type');
			$this->t_options = bab_translate('Options');
			$req = "select * from ".BAB_CAL_USER_OPTIONS_TBL." where id_user='".$babDB->db_escape_string($BAB_SESS_USERID)."'";
			$res = $babDB->db_query($req);
			$this->arr = $babDB->db_fetch_assoc($res);
			
			$this->t_defaultCalAccess = bab_translate("Default calendar access for new user");

			$this->aCalAccess = array(
				BAB_CAL_ACCESS_NONE => bab_translate("None"),
				BAB_CAL_ACCESS_VIEW => bab_translate("Consultation"), 
				BAB_CAL_ACCESS_UPDATE => bab_translate("Creation and modification"), 
				BAB_CAL_ACCESS_SHARED_UPDATE => bab_translate("Shared creation and modification"),
				BAB_CAL_ACCESS_FULL => bab_translate("Full access") 
			);
				
			if(is_array($this->arr) && array_key_exists('iDefaultCalendarAccess', $this->arr))
			{
				if(null !== $this->arr['iDefaultCalendarAccess'])
				{
					$this->iSelectedCalAccess = $this->arr['iDefaultCalendarAccess'];
				}
			}
			
			
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
			
			$arr = bab_functionality::getFunctionalities('CalendarBackend');
			$this->allbackends = array();
			
			foreach($arr as $backend)
			{
				$obj = @bab_functionality::get('CalendarBackend/'.$backend);
				if ($obj && $obj->StorageBackend())
				{
					$this->allbackends[] = $backend;
				}
			}

			
			if (2 > count($this->allbackends) || null === bab_getICalendars()->getPersonalCalendar())
				{
					$this->allbackends = array();
				}
			}

		function getnextshortday()
			{
			static $i = 0;
			if( $i < 7 )
				{
				$this->disp_selected = in_array($i, $this->dispdays) ? "checked" : "";

				$this->dayid = $i;
				$this->shortday = bab_toHtml(bab_DateStrings::getDay($i));

				$arr = bab_getWHours($GLOBALS['BAB_SESS_USERID'], $i);
				$tmp = array();
				foreach($arr as $p) {
					unset($p['weekDay']);
					$tmp[] = implode('-',$p);
				}
				$this->workinghours = implode(',',$tmp);

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
			static $i = 0;
			if( $i < 7 )
				{
				if( $i == $this->arr['startday'])
					$this->selected = "selected";
				else
					$this->selected = "";
				$this->dayid = $i;
				$this->dayname = bab_DateStrings::getDay($i);		
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
			
			
		function getNextBackend()
			{
				if (list(,$func) = each($this->allbackends))
				{
					$this->name = bab_toHtml($func);
					$backend = @bab_functionality::get('CalendarBackend/'.$func);
					if ($backend)
					{
						$this->description = bab_toHtml($backend->getDescription());
						$this->optionsurl = bab_toHtml((string) $backend->getOptionsUrl());
					} else {
						$this->description = bab_toHtml($func);
						$this->optionsurl = false;
					}
					$this->selected = $func === bab_getICalendars()->calendar_backend;
					return true;
				}
				
				return false;
			}
		}

	$temp = new temp($urla);
	

		$babBody->addStyleSheet('calopt.css');
		$babBody->babecho(bab_printTemplate($temp, "calopt.html", "caloptions"));

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

function updateCalOptions($startday, $starttime, $endtime, $allday, $usebgcolor, $elapstime, $defaultview, $showupdateinfo, $iDefaultCalendarAccess, $showonlydaysmonthinfo)
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
			show_onlydays_of_month =".$babDB->quote($showonlydaysmonthinfo).", 
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
				show_onlydays_of_month,
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
			".$babDB->quote($showonlydaysmonthinfo).",
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
	


	$change = true;

	$req = "
		DELETE FROM ".BAB_WORKING_HOURS_TBL." 
		WHERE 
			idUser=".$babDB->quote($GLOBALS['BAB_SESS_USERID'])."
	";
	$babDB->db_query($req);
	
	$workinghours = bab_pp('workinghours', array());
	/* $workinghours : array with periods selectionned
	array(7) {
	  [0]=>
	  string(0) ""
	  [1]=>
	  string(4) "8-18"
	  [2]=>
	  string(4) "8-18"
	  [3]=>
	  string(4) "8-18"
	  [4]=>
	  string(4) "8-18"
	  [5]=>
	  string(4) "8-18"
	  [6]=>
	  string(0) ""
	}
	*/
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


	if ($change) {
		include_once $GLOBALS['babInstallPath'].'utilit/eventperiod.php';
		
		$event = new bab_eventPeriodModified(false, false, $GLOBALS['BAB_SESS_USERID']);
		$event->types = BAB_PERIOD_WORKING;
		bab_fireEvent($event);
	}
	
	
}




class bab_changeCalendarBackendCls
{
	public function __construct(Func_CalendarBackend $old_backend, Func_CalendarBackend $new_backend, $calendar_backend)
	{
		$this->urla = bab_toHtml(bab_rp('urla'));
		$this->calendar_backend = bab_toHtml($calendar_backend);
		
		$this->t_intro = sprintf(
			bab_translate('Change my personal calendar from %s to %s'), 
			$old_backend->getDescription(),
			$new_backend->getDescription()
		);
		
		$this->t_option_copy_source = bab_translate('Copy the events to my new calendar');
		$this->t_option_delete_destination = bab_translate('Delete the existing events in my new calendar');
		$this->t_start_copy_from = bab_translate('Start the copy of events from');
		
		$this->t_submit = bab_translate('Save');
	}
	
	
	public function getnextstart()
	{
		static $values = null;
		
		if (null === $values)
		{
			$values = array();
			
			require_once dirname(__FILE__).'/utilit/dateTime.php';
			$loop = new BAB_DateTime(date('Y'), 1, 1);
			
			// if we are in january, the first proposition will be on the previous year
			
			if (1 === (int) date('n'))
			{
				$loop->less(1, BAB_DATETIME_YEAR);
			}
			
			for ($i = 0; $i < 5; $i++)
			{
				$values[$loop->getIsoDate()] = bab_longDate($loop->getTimestamp(), false);
				$loop->less(1, BAB_DATETIME_YEAR);
			}
			
			$values[''] = bab_translate('First event');
		}
		
		if ($arr = each($values))
		{
			$this->value = bab_toHtml($arr[0]);
			$this->option = bab_toHtml($arr[1]);
			return true;
		}
		
		return false;	
	}
}



/**
 * 
 * @param string $calendar_backend		new backend name
 * @return unknown_type
 */
function bab_changeCalendarBackend($calendar_backend)
{
	global $babBody;
	
	$old_backend = @bab_functionality::get('CalendarBackend/'.bab_getICalendars()->calendar_backend);
	$new_backend = @bab_functionality::get('CalendarBackend/'.$calendar_backend);
	
	if (!$old_backend || !$new_backend)
	{
		$babBody->addError(bab_translate('Configuration error, missing calendar backend'));
		return false;
	}
	
	// test validity of new backend
	if (!$new_backend->checkCalendar($GLOBALS['BAB_SESS_USERID']))
	{
		$babBody->addError(bab_translate('Configuration error, invalid account for new calendar'));
		return false;
	}
	
	$display = new bab_changeCalendarBackendCls($old_backend, $new_backend, $calendar_backend);
	$babBody->babEcho(bab_printTemplate($display, "calopt.html", "calendarBackend"));
	
	return true;
}



/**
 * 
 * 
 * @param 	string 	$calendar_backend		new backend name
 * @param	int		$copy_source
 * @param	int		$delete_destination
 * 
 * @return unknown_type
 */
function bab_changeCalendarBackendConfirm($calendar_backend, $copy_source, $delete_destination, $start_copy_from)
{
	require_once dirname(__FILE__).'/utilit/install.class.php';
	
	bab_installWindow::getPage(
		bab_translate('Move my personal calendar'),
		$GLOBALS['babUrlScript']."?tg=calopt&idx=changeCalendarBackendFrame&calendar_backend=$calendar_backend&copy_source=$copy_source&delete_destination=$delete_destination&start_copy_from=$start_copy_from&urla=".urlencode(bab_rp('urla')),
		bab_translate('Next'),
		$GLOBALS['babUrlScript']."?tg=calopt&idx=options&urla=".urlencode(bab_rp('urla'))
	);
	
}


/**
 * Iframe content for calendar backend progress
 * @param 	string		 	$calendar_backend
 * @param 	int 			$copy_source
 * @param 	int 			$delete_destination
 * @param	string			$start_copy_from
 * @return unknown_type
 */
function bab_changeCalendarBackendFrame($calendar_backend, $copy_source, $delete_destination, $start_copy_from)
{
	require_once dirname(__FILE__).'/utilit/install.class.php';
	
	$changeCalendar = new bab_changeCalendarBackend($calendar_backend, $copy_source, $delete_destination, $start_copy_from);
	
	$old_backend = bab_functionality::get('CalendarBackend/'.bab_getICalendars()->calendar_backend);
	$new_backend = bab_functionality::get('CalendarBackend/'.$calendar_backend);
	
	$window = new bab_installWindow;
	
	
	$window->setStartMessage(sprintf(bab_translate('Move my personal calendar from %s to %s'), $old_backend->getDescription(), $new_backend->getDescription()));
	$window->setStopMessage(bab_translate('Your calendar has been moved'), bab_translate('Moving your calendar has failed'));
	
	$window->startInstall(array($changeCalendar, 'process'));
	
	exit;
}
	
	
	
class bab_changeCalendarBackend
{
	private $calendar_backend;
	private $copy_source;
	private $delete_destination;
	private $start_copy_from;
	
	public function __construct($calendar_backend, $copy_source, $delete_destination, $start_copy_from)
	{
		$this->calendar_backend = $calendar_backend;
		$this->copy_source = $copy_source;
		$this->delete_destination = $delete_destination;
		$this->start_copy_from = $start_copy_from;
	}
	
	/**
	 * Callback for install process
	 * @return unknown_type
	 */
	public function process()
	{
		global $babDB;
		
		
		$calendar_backend 	= $this->calendar_backend;
		$copy_source 		= $this->copy_source;
		$delete_destination = $this->delete_destination;
		
		
		// my personal calendar on old backend
		
		$old_calendar = bab_getICalendars()->getPersonalCalendar();
		
		if (!($old_calendar instanceof bab_PersonalCalendar))
		{
			bab_installWindow::message(bab_translate('Personal calendar not available on old backend'));
			return false;
		}
		
		if (bab_getICalendars()->calendar_backend === $calendar_backend)
		{
			bab_installWindow::message(bab_translate('Source and destination are the same'));
			return false;
		}
		
		
		$old_backend = bab_functionality::get('CalendarBackend/'.bab_getICalendars()->calendar_backend);
		$new_backend = bab_functionality::get('CalendarBackend/'.$calendar_backend);
		
		/**@var $old_backend Func_CalendarBackend */
		/**@var $new_backend Func_CalendarBackend */
		
		$factory = $new_backend->Criteria();
		
		// the new calendar
		
		$new_calendar = $new_backend->PersonalCalendar($GLOBALS['BAB_SESS_USERID']);
		
		if (!($new_calendar instanceof bab_PersonalCalendar))
		{
			bab_installWindow::message(bab_translate('Personal calendar not available on new backend'));
			return false;
		}
		
		
		// the select can take a large amount of memory
			
		ini_set('memory_limit', '300M');
		
		
		if ($delete_destination)
		{
			bab_setTimeLimit(30); // 30 seconds to initialize the delete
			
			// delete events in destination calendar backend
			
			$progress = new bab_installProgressBar;
			$progress->setTitle(bab_translate('Remove all events from the new calendar'));
			
			$criteria = $factory->Calendar($new_calendar);
			$criteria = $criteria->_AND_($factory->Collection(array('bab_CalendarEventCollection'))); 
			
			
			
			$events = $new_backend->selectPeriods($criteria);
			
			if ($events instanceof iterator)
			{
				$total = $events->count();
			} else {
				$total = count($events);
			}
			
			if (0 === $total)
			{
				bab_installWindow::message(bab_translate('You requested to delete the events in the destination calendars but the program failed to retreive any event, it is probably that your calendar is too large to handle for the program, you will have to empty the calendar manually or use another calendar'));
				return false;
			}
			
			bab_installWindow::message(sprintf(bab_translate('%d events found in the new calendar'), $total));
			
			$i = 0;
			foreach($events as $event)
			{
				bab_setTimeLimit(10); // 10 seconds for each events
				$new_backend->deletePeriod($event);
				$i++;
				
				$percent = ($i * 100) / $total;
				$progress->setProgression(round($percent));
			}
			
			$progress->setProgression(100);
			
		}
		
		
		if ($copy_source)
		{
			bab_setTimeLimit(300); // 300 seconds to initialize the copy
			
			
			if ($old_backend instanceof Func_CalendarBackend_Ovi)
			{
				bab_installWindow::message(bab_translate('Set waiting events to accepted'));
				
				// all event of calendar should be accepted or rejected
				$babDB->db_query('UPDATE '.BAB_CAL_EVENTS_OWNERS_TBL." 
					SET 
						status=".$babDB->quote(BAB_CAL_STATUS_ACCEPTED)."
					where 
						status=".$babDB->quote(BAB_CAL_STATUS_NONE)."
						AND calendar_backend=".$babDB->quote(bab_getICalendars()->calendar_backend)." 
						AND caltype=".$babDB->quote($old_calendar->getReferenceType())."
						AND id_cal=".$babDB->quote($old_calendar->getUid())." 
				");
			}
			
			
			$progress = new bab_installProgressBar;
			$progress->setTitle(bab_translate('Copy all events from old calendar'));
			
			
			$criteria = $factory->Calendar($old_calendar);
			$criteria = $criteria->_AND_($factory->Collection(array('bab_CalendarEventCollection', 'bab_VacationPeriodCollection', 'bab_InboxEventCollection'))); 
			
			if ('' !== $this->start_copy_from)
			{
				require_once dirname(__FILE__).'/utilit/dateTime.php';
				$begin = BAB_DateTime::fromIsoDateTime($this->start_copy_from.' 00:00:00');
				$criteria = $criteria->_AND_($factory->Begin($begin));
			}
			
			$events = $old_backend->selectPeriods($criteria);
			
			if ($events instanceof iterator)
			{
				$total = $events->count();
			} else {
				$total = count($events);
			}

			
			$vacationCollection = $old_backend->CalendarEventCollection($new_calendar);
			
			$i = 0;
			foreach($events as $event)
			{
				bab_setTimeLimit(10); // 10 seconds for each events
				
				$collection = $event->getCollection();
				if (($collection instanceof bab_CalendarEventCollection) || ($collection instanceof bab_InboxEventCollection))
				{
					$collection->setCalendar($new_calendar);
					
				} elseif($collection instanceof bab_VacationPeriodCollection) {
					
					$vacationCollection->addPeriod($event);
					
				} else {
					throw new Exception('Unsupported collection');
				}
				
				$event->removeProperty('UID');
				$event->removeProperty('RRULE');
				$new_backend->savePeriod($event);
				$event->commitEvent();
				
				$i++;
				
				$percent = ($i * 100) / $total;
				$progress->setProgression(round($percent));
			}
			
			$progress->setProgression(100);
		}
		
		bab_setTimeLimit(10); // 10 seconds to end process
		

		
		bab_installWindow::message(bab_translate('Update events where i am an attendee'));
		
		// update all events with links to this personal calendar
		
		$babDB->db_query('UPDATE '.BAB_CAL_EVENTS_OWNERS_TBL." 
			SET 
				calendar_backend=".$babDB->quote($calendar_backend).", 
				caltype=".$babDB->quote($new_calendar->getReferenceType())."
			where 
				
				calendar_backend=".$babDB->quote(bab_getICalendars()->calendar_backend)." 
				AND caltype=".$babDB->quote($old_calendar->getReferenceType())."
				AND id_cal=".$babDB->quote($old_calendar->getUid())." 
				
		");
		
		
		bab_installWindow::message(bab_translate('Update my calendar sharing access'));
		
		
		// update all sharing access
		
		$babDB->db_query('UPDATE '.BAB_CALACCESS_USERS_TBL." 
			SET 
				caltype=".$babDB->quote($new_calendar->getReferenceType())."
			where 
				caltype=".$babDB->quote($old_calendar->getReferenceType())."
				AND id_cal=".$babDB->quote($old_calendar->getUid())." 
		");
		
		
		$babDB->db_query('UPDATE '.BAB_CAL_USER_OPTIONS_TBL." 
			SET calendar_backend=".$babDB->quote($calendar_backend)." 
			where id_user=".$babDB->quote($GLOBALS['BAB_SESS_USERID'])
		);
		
		return true;
	}
}




function bab_updateCalOptions()
{
	updateCalOptions($_POST['startday'], $_POST['starttime'], $_POST['endtime'], $_POST['allday'], $_POST['usebgcolor'], $_POST['elapstime'], $_POST['defaultview'], $_POST['showupdateinfo'], $_POST['iDefaultCalendarAccess'], $_POST['showonlydaysmonthinfo']);
	
	if (bab_pp('calendar_backend') && bab_getICalendars()->calendar_backend !== bab_pp('calendar_backend')) 
	{
		return bab_changeCalendarBackend(bab_pp('calendar_backend'));
	}
	
	$url = $GLOBALS['babUrlScript'].'?tg=calopt&idx=options';
	header('location:'.$url);
	exit;
}




/* main */

$idx = bab_rp('idx', 'options');
$urla = bab_rp('urla');



	
if( isset($modify) && $modify == "options" && $BAB_SESS_USERID != '')
{
	if (bab_updateCalOptions())
	{
		return;
	}
}

if (bab_pp('calendar_backend') && bab_pp('confirm') && bab_isUserLogged())
{
	bab_changeCalendarBackendConfirm(bab_pp('calendar_backend'), (int) bab_pp('copy_source'), (int) bab_pp('delete_destination'), bab_pp('start_copy_from'));
	return;
}

$babBody->addItemMenu("global", bab_translate("Options"), $GLOBALS['babUrlScript']."?tg=options&idx=global");

$personalCalendar = bab_getICalendars()->getPersonalCalendar();

switch($idx)
	{
	case 'changeCalendarBackendFrame':
		bab_changeCalendarBackendFrame(bab_rp('calendar_backend'), (int) bab_rp('copy_source'), (int) bab_rp('delete_destination'), bab_rp('start_copy_from'));
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
	
		if (!bab_isUserLogged()) {
			$babBody->addError('Access denied');
			break;
		}
		
		$babBody->title = bab_translate("Calendar Options");
		
		$babBody->addItemMenu("options", bab_translate("Calendar Options"), $GLOBALS['babUrlScript']."?tg=calopt&idx=options&urla=".urlencode($urla));
		
		if( $personalCalendar instanceof bab_EventCalendar )
		{
			accessCalendar($personalCalendar, bab_rp('urla'));
			
			$babBody->addItemMenu("access", bab_translate("Calendar access"), $GLOBALS['babUrlScript']."?tg=options&idx=access&idcal=".$personalCalendar->getUid());
			
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
		
		calendarOptions($urla);

		$babBody->addItemMenu("options", bab_translate("Calendar Options"), $GLOBALS['babUrlScript']."?tg=calopt&idx=options");

		if( $personalCalendar instanceof bab_EventCalendar )
			{
			$babBody->addItemMenu("access", bab_translate("Calendar access"), $GLOBALS['babUrlScript']."?tg=calopt&idx=access&idcal=".$personalCalendar->getUid()."&urla=".urlencode($urla));	
			}

		if( isset($urla) && !empty($urla) && bab_getICalendars()->calendarAccess() )
			{
			$babBody->addItemMenu("cal", bab_translate("Calendar"), $urla);
			}
		break;
	}

$babBody->setCurrentItemMenu($idx);
?>