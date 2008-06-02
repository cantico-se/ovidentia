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
include_once $babInstallPath.'utilit/calincl.php';
include_once $babInstallPath.'utilit/evtincl.php';
 
function displayAttendees($evtid, $idcal)
{
	global $babBodyPopup;
	class displayAttendeesCls
		{
		var $altbg = true;
		var $fullnametxt;
		var $diskspacetxt;
		var $kilooctet;
		var $arrinfo;
		var $fullname;
		var $diskspace;

		function displayAttendeesCls($evtid, $idcal)
			{
			global $babBodyPopup, $babBody, $babDB;
			$this->access = false;
			$this->evtid = $evtid;
			if( bab_isCalendarAccessValid($idcal))
				{
				$this->access = true;
					$this->idcal = $idcal;
				$this->fullnametxt = bab_translate("Attendee");
				$this->statusdef = array(BAB_CAL_STATUS_ACCEPTED => bab_translate("Accepted"), BAB_CAL_STATUS_NONE => "", BAB_CAL_STATUS_DECLINED => bab_translate("Declined"));
				$this->statustxt = bab_translate("Response");
				list($this->idcreator, $this->hash) = $babDB->db_fetch_row($babDB->db_query("
				select id_creator, hash from ".BAB_CAL_EVENTS_TBL." where id='".$babDB->db_escape_string($evtid)."'
				"));
				$res = $babDB->db_query("select ceo.* from ".BAB_CAL_EVENTS_OWNERS_TBL." ceo where ceo.id_event='".$babDB->db_escape_string($evtid)."'");
				$this->arrinfo = array();
				$this->statusarray = array();
				$arrschi = bab_getWaitingIdSAInstance($GLOBALS['BAB_SESS_USERID']);
				while( $arr = $babDB->db_fetch_array($res))
					{
					$icalinfo = $babBody->icalendars->getCalendarInfo($arr['id_cal']);
					if( $icalinfo === false)
					{
						$tmp = bab_getCalendarOwnerAndType($arr['id_cal']);
					
						$icalinfo['type'] = $tmp['type'];
						$icalinfo['name'] = bab_getCalendarOwnerName($arr['id_cal']);
						$icalinfo['idowner'] = $tmp['owner'];
						$icalinfo['access'] = '';
					}
					
					$key = strtolower($icalinfo['name'].$arr['id_cal']);
					
					$this->arrinfo[$key] = array('name' => $icalinfo['name'],'idcal' => $arr['id_cal'], 'idowner' => $icalinfo['idowner'],'status' => $arr['status']);
					if( $idcal == $arr['id_cal'] )
						{
						switch($icalinfo['type'])
							{
							case BAB_CAL_USER_TYPE:
								if( $icalinfo['access'] == BAB_CAL_ACCESS_FULL || $icalinfo['access'] == BAB_CAL_ACCESS_SHARED_FULL)
									{
									$this->idcal = $arr['id_cal'];
									switch($arr['status'] )
										{
										case BAB_CAL_STATUS_NONE:
											$this->statusarray = array(BAB_CAL_STATUS_ACCEPTED,BAB_CAL_STATUS_DECLINED);
											break;
										case BAB_CAL_STATUS_ACCEPTED:
											$this->statusarray = array(BAB_CAL_STATUS_DECLINED);
											break;
										case BAB_CAL_STATUS_DECLINED:
											$this->statusarray = array(BAB_CAL_STATUS_ACCEPTED);
											break;
										}
									}
								break;
							case BAB_CAL_PUB_TYPE:
							case BAB_CAL_RES_TYPE:
								if( $arr['status'] == BAB_CAL_STATUS_NONE && $arr['idfai'] != 0 && count($arrschi) > 0 && in_array($arr['idfai'], $arrschi))
									{
									$this->statusarray = array(BAB_CAL_STATUS_ACCEPTED,BAB_CAL_STATUS_DECLINED);
									}
								break;
							}
						}
					}
				$this->count = count($this->arrinfo);
				$this->countstatus = count($this->statusarray);
				if( $this->countstatus )
					{
					$this->updatetxt = bab_translate("Update");
					$this->confirmtxt = bab_translate("Confirm");
					$this->commenttxt = bab_translate("Raison");
					$this->accepttxt = bab_translate("Accept");
					$this->declinetxt = bab_translate("Decline");
					if( !empty($this->hash) && $this->hash[0] == 'R')
						{
						$this->repetitivetxt = bab_translate("This is recurring event. Do you want to update this occurence or series?");
						$this->all = bab_translate("All");
						$this->thisone = bab_translate("This occurence");
						$this->brepetitive = true;
						}
					else
						{
						$this->brepetitive = false;
						}
					}
					
				ksort($this->arrinfo);
					
				}
			else
				{
				$babBodyPopup->msgerror = bab_translate("Access denied");
				}
			}

		function getnextuser()
			{
			global $babBody;
			static $i = 0;
			if( list(,$arr) = each($this->arrinfo))
				{
				$this->altbg = $this->altbg ? false : true;
				$this->fullname = $arr['name'];
				$this->bcreator = false;
				if( $arr['idowner'] ==  $this->idcreator )
					{
					$this->bcreator = true;
					}
				$this->status = $this->statusdef[$arr['status']];
				$i++;
				return true;
				}
			else
				{
				return false;
				}
			}

		function getnextstatus()
			{
			global $babBody;
			static $i = 0;
			if( $i < $this->countstatus)
				{
				$this->statusname = $this->statusdef[$this->statusarray[$i]];
				switch($this->statusarray[$i])
					{
					case BAB_CAL_STATUS_ACCEPTED:
						$this->statusval = "Y";
						break;
					case BAB_CAL_STATUS_DECLINED:
						$this->statusval = "N";
						break;
					}
				$i++;
				return true;
				}
			else
				{
				return false;
				}
			}
		}
	$temp = new displayAttendeesCls($evtid, $idcal);
	$babBodyPopup->babecho(bab_printTemplate($temp, "calendar.html", "listattendees"));
}

function getPropertiesString(&$arr, &$t_option)
	{
	$el = array('bprivate' => bab_translate('Private'),'block' => bab_translate('Locked'),'bfree' => bab_translate('Free'));
	foreach ($el as $k => $v)
		{
		if ($arr[$k] != 'Y')
			unset($el[$k]);
		}
	$t_option = count($el) > 1 ? bab_translate("Options") : bab_translate("Option"); 
	if (count($el) > 0)
		return implode(', ',$el);
	else
		return '';
	}


function getPropertiesStringObj(&$calPeriod, &$t_option)
		{
		$el = array();

		if ('PUBLIC' !== $calPeriod->getProperty('CLASS')) {
			$el[] = bab_translate('Private');
		}

		$arr = $calPeriod->getData();

		if (isset($arr['block']) && 'Y' == $arr['block']) {
			$el[] = bab_translate('Locked');
		}

		if (isset($arr['bfree']) && 'Y' == $arr['bfree']) {
			$el[] = bab_translate('Free');
		}

		$t_option = count($el) > 1 ? bab_translate("Options") : bab_translate("Option"); 
		if (count($el) > 0)
			return implode(', ',$el);
		else
			return '';
		}





function displayEventDetail($evtid, $idcal)
{
	global $babBodyPopup;
	class displayEventDetailCls
		{

		function displayEventDetailCls($evtid, $idcal)
			{
			global $babBodyPopup, $babBody, $babDB;
			$this->access = false;
			if( bab_isCalendarAccessValid($idcal))
				{
				$res = $babDB->db_query("select * from ".BAB_CAL_EVENTS_TBL." where id='".$babDB->db_escape_string($evtid)."'");
				if( $res && $babDB->db_num_rows($res) == 1)
					{
					$this->access = true;
					$this->idcal = $idcal;
					$arr = $babDB->db_fetch_array($res);
					$iarr = $babBody->icalendars->getCalendarInfo($idcal);
					$this->begindatetxt = bab_translate("Begin date");
					$this->enddatetxt = bab_translate("End date");
					$this->titletxt = bab_translate("Title");
					$this->desctxt = bab_translate("Description");
					$this->locationtxt = bab_translate("Location");
					$this->cattxt = bab_translate("Category");
					$this->begindate = bab_toHtml(bab_longDate(bab_mktime($arr['start_date'])));
					$this->enddate = bab_toHtml(bab_longDate(bab_mktime($arr['end_date'])));

					$this->t_option = ''; 
					$this->properties = bab_toHtml(getPropertiesString($arr, $this->t_option));

					if( $arr['bprivate'] ==  'Y' && $GLOBALS['BAB_SESS_USERID']  != $iarr['idowner'])
						{
						$this->title= '';
						$this->description = '';
						$this->location = '';
						}
					else
						{
						$this->title= bab_toHtml($arr['title']);
						include_once $GLOBALS['babInstallPath']."utilit/editorincl.php";
						$editor = new bab_contentEditor('bab_calendar_event');
						$editor->setContent($arr['description']);
						$this->description = $editor->getHtml();
				
						$this->location= bab_toHtml($arr['location']);
						}
					if( $arr['id_cat'] != 0 )
						{
						list($this->category) = $babDB->db_fetch_row($babDB->db_query("select name from ".BAB_CAL_CATEGORIES_TBL." where id='".$babDB->db_escape_string($arr['id_cat'])."'"));
						
						$this->category = bab_toHtml($this->category);
						}
					else
						{
						$this->category = "";
						}

					list($bshowui) = $babDB->db_fetch_array($babDB->db_query("select show_update_info from ".BAB_CAL_USER_OPTIONS_TBL." where id_user='".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."'"));
					if( empty($bshowui))
						{
						$bshowui = $babBody->babsite['show_update_info'];
						}

					$this->bshowupadetinfo = false;
					if( $bshowui == 'Y' && $arr['id_modifiedby'] )
						{
						$this->bshowupadetinfo = true;
						$this->modifiedontxt = bab_translate("Created/Updated on");
						$this->bytxt = bab_translate("By");
						$this->updatedate = bab_toHtml(bab_shortDate(bab_mktime($arr['date_modification']), true));
						$this->updateauthor = bab_toHtml(bab_getUserName($arr['id_modifiedby']));
						}

					}
				else
					{
					$babBodyPopup->msgerror = bab_translate("There is no additional informations for this event");
					}
				}
				else
					{
					$babBodyPopup->msgerror = bab_translate("Access denied to the calendar");
					}
			}
		}

	$temp = new displayEventDetailCls($evtid, $idcal);
	$babBodyPopup->babecho(bab_printTemplate($temp, "calendar.html", "eventdetail"));
}


function displayEventNotes($evtid, $idcal)
{
	global $babBodyPopup;
	class displayEventNotesCls
		{

		function displayEventNotesCls($evtid, $idcal)
			{
			global $babBodyPopup, $babBody, $babDB;
			$this->access = false;
			if( bab_isCalendarAccessValid($idcal))
				{
				$this->access = true;
				$this->idcal = $idcal;
				$this->evtid = $evtid;
				$this->notetxt = bab_translate("Personal notes");
				$this->updatetxt = bab_translate("Update");
				$res = $babDB->db_query("select note from ".BAB_CAL_EVENTS_NOTES_TBL." where id_user='".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."' and id_event='".$babDB->db_escape_string($evtid)."'");
				if( $res && $babDB->db_num_rows($res) > 0 )
					{
					$arr = $babDB->db_fetch_array($res);
					$this->noteval = bab_toHtml($arr['note']);
					}
				else
					{
					$this->noteval = '';
					}
				list($hash) = $babDB->db_fetch_row($babDB->db_query("select hash from ".BAB_CAL_EVENTS_TBL." where id='".$babDB->db_escape_string($evtid)."'"));
				if( !empty($hash) && $hash[0] == 'R')
					{
					$this->all = bab_translate("All");
					$this->thisone = bab_translate("This occurence");
					$this->brecevt = true;
					}
				else
					{
					$this->brecevt = false;
					}
				}
			}
		}

	$temp = new displayEventNotesCls($evtid, $idcal);
	$babBodyPopup->babecho(bab_printTemplate($temp, "calendar.html", "eventnotes"));
}


function displayEventAlert($evtid, $idcal)
{
	global $babBodyPopup;
	class displayEventAlertCls
		{

		function displayEventAlertCls($evtid, $idcal)
			{
			global $babBodyPopup, $babBody, $babDB;
			$this->access = false;
			if( bab_isCalendarAccessValid($idcal))
				{
				$this->access = true;
				$this->idcal = $idcal;
				$this->evtid = $evtid;
				$this->alerttxt = bab_translate("Reminder");
				$this->updatetxt = bab_translate("Update");
				$res = $babDB->db_query("select * from ".BAB_CAL_EVENTS_REMINDERS_TBL." where id_user='".$GLOBALS['BAB_SESS_USERID']."' and id_event='".$babDB->db_escape_string($evtid)."'");
				if( $res && $babDB->db_num_rows($res) > 0 )
					{
					$this->arralert = $babDB->db_fetch_array($res);
					$this->rcheckedval = 'checked';
					}
				else
					{
					$this->arralert = array();
					$this->rcheckedval = '';
					}

				list($hash) = $babDB->db_fetch_row($babDB->db_query("select hash from ".BAB_CAL_EVENTS_TBL." where id='".$babDB->db_escape_string($evtid)."'"));
				if( !empty($hash) && $hash[0] == 'R')
					{
					$this->all = bab_translate("All");
					$this->thisone = bab_translate("This occurence");
					$this->brecevt = true;
					}
				else
					{
					$this->brecevt = false;
					}
				$this->days = array(0, 1, 2, 3, 5, 6, 7, 8, 10, 11, 12);
				$this->hours = array(0, 1, 2, 3, 5, 6, 7, 8, 10, 11, 12);
				$this->minutes = array(0, 5, 10, 15, 30, 45);
				$this->rmcheckedval = '';
				if( isset($GLOBALS['babEmailReminder']) &&  $GLOBALS['babEmailReminder'])
					{
					$this->remailtxt = bab_translate("Use email reminder");
					if( isset($this->arralert['bemail']) && $this->arralert['bemail'] ==  'Y' )
						{
						$this->rmcheckedval = 'checked';
						}
					}
				else
					{
					$this->remailtxt = "";
					}
				}
			}


		function getnextday()
			{
			static $i=0;
			if( $i < count($this->days))
				{
				$this->dval = $this->days[$i];
				$this->dname = $this->dval." ";
				if( $i < 2 )
					{
					$this->dname .= bab_translate("day");
					}
				else
					{
					$this->dname .= bab_translate("days");
					}
				if( isset($this->arralert['day']) && $this->dval == $this->arralert['day'])
					{
					$this->dselected = 'selected';
					}
				else
					{
					$this->dselected = '';
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

		function getnexthour()
			{
			static $i=0;
			if( $i < count($this->hours))
				{
				$this->hval = $this->hours[$i];
				$this->hname = $this->hval." ";
				if( $i < 2 )
					{
					$this->hname .= bab_translate("hour");
					}
				else
					{
					$this->hname .= bab_translate("hours");
					}
				$i++;
				if( isset($this->arralert['hour']) && $this->hval == $this->arralert['hour'])
					{
					$this->hselected = 'selected';
					}
				else
					{
					$this->hselected = '';
					}
				return true;
				}
			else
				{
				$i = 0;
				return false;
				}
			}

		function getnextminute()
			{
			static $i=0;
			if( $i < count($this->minutes))
				{
				$this->mval = $this->minutes[$i];
				$this->mname = $this->mval." ";
				if( $i == 0 )
					{
					$this->mname .= bab_translate("minute");
					}
				else
					{
					$this->mname .= bab_translate("minutes");
					}
				if( isset($this->arralert['minute']) && $this->mval == $this->arralert['minute'])
					{
					$this->mselected = 'selected';
					}
				else
					{
					$this->mselected = '';
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
		}

	$temp = new displayEventAlertCls($evtid, $idcal);
	$babBodyPopup->babecho(bab_printTemplate($temp, "calendar.html", "eventalert"));
}

function categoriesList()
{
	global $babBodyPopup;
	class categoriesListCls
		{

		function categoriesListCls()
			{
			global $babBodyPopup, $babBody, $babDB;
			$this->res = $babDB->db_query("select * from ".BAB_CAL_CATEGORIES_TBL." ORDER BY name,description");
			$this->count = $babDB->db_num_rows($this->res);

			$this->t_name = bab_translate('Name');
			$this->t_description = bab_translate('Description');
			$this->t_close = bab_translate('Close');
			}

		function getnextcat()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $babDB->db_fetch_array($this->res);
				$this->name = bab_toHtml($arr['name']);
				$this->description = bab_toHtml($arr['description']);
				$this->bgcolor = bab_toHtml($arr['bgcolor']);
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

	$temp = new categoriesListCls();
	$babBodyPopup->babecho(bab_printTemplate($temp, "calendar.html", "categorieslist"));
}

function eventlist($from, $to, $idcals)
{
include_once $GLOBALS['babInstallPath']."utilit/calincl.php";
include_once $GLOBALS['babInstallPath']."utilit/mcalincl.php";
include_once $GLOBALS['babInstallPath']."utilit/uiutil.php";

	class eventlist
		{
		function eventlist($from, $to, $idcals)
			{
			list($fyear,$fmonth,$fday) = explode(',', $from);
			list($tyear,$tmonth,$tday) = explode(',', $to);

			$idcals = explode(',',$idcals);

			$this->from = sprintf("%04s-%02s-%02s 00:00:00", $fyear, $fmonth, $fday);
			$this->to = sprintf("%04s-%02s-%02s 00:00:00", $tyear, $tmonth, $tday);

			$this->mcals = & new bab_mcalendars($this->from, $this->to, $idcals);
			$this->mcals->loadCategories();
			$this->resevent = array();

			$this->t_print = bab_translate('Print');
			$this->t_private = bab_translate('Private');
			$this->t_from = bab_translate('Par');
			$this->t_category = bab_translate('Category');
			$this->t_show_hide = bab_translate('Show / hide finished events');
			$this->t_location = bab_translate('Location');
			$this->t_notetxt = bab_translate("Personal notes");

			$last_ts = 0;

			foreach ($idcals as $idcal)
				{
				while ($this->mcals->getNextEvent($idcal, $this->from, $this->to, $calPeriod))
					{
					$arr = $calPeriod->getData();
					$arr['color'] = $calPeriod->getColor();

					$xCtoPuid = $calPeriod->getProperty('X-CTO-PUID');
					
					if (!isset($this->resevent[$xCtoPuid]))
						{
						$this->resevent[$xCtoPuid] = array();
						$this->resevent[$xCtoPuid]['cals'] = array();
						}

					$evt = & $this->resevent[$xCtoPuid];

					$evt['cals'] = $this->mcals->getEventCalendars($calPeriod);

					$evt['title'] = $calPeriod->getProperty('SUMMARY');
					$evt['description'] = $calPeriod->getProperty('DESCRIPTION');
					$ts = bab_mktime($calPeriod->getProperty('DTEND'));
					if ($ts <= time() && $last_ts < $ts)
						{
						$last_ts = $ts;
						$this->last_id = $xCtoPuid;
						}
					$evt['start_date'] = bab_toHtml(bab_longDate(bab_mktime($calPeriod->getProperty('DTSTART'))));
					$evt['end_date'] = bab_toHtml(bab_longDate($ts));
					$evt['categoryname'] = !empty($this->mcals->categories[$arr['id_cat']]) ? bab_toHtml($this->mcals->categories[$arr['id_cat']]['name']) : '';
					$evt['categorydescription'] = !empty($this->mcals->categories[$arr['id_cat']]) ? bab_toHtml($this->mcals->categories[$arr['id_cat']]['description']) : '';
					
					if (!empty($this->mcals->categories[$arr['id_cat']])) {
						$evt['color'] = bab_toHtml($this->mcals->categories[$arr['id_cat']]['bgcolor']);
					} elseif (!empty($arr['color'])) {
						$evt['color'] = bab_toHtml($arr['color']);
					} else {
						$evt['color'] = 'fff';
					}
					
					$evt['creator'] = isset($arr['id_creator']) && $arr['id_creator'] != $GLOBALS['BAB_SESS_USERID'] ? bab_toHtml(bab_getUserName($arr['id_creator'])) : '';
					$evt['private'] = isset($arr['id_creator']) && $arr['id_creator'] != $GLOBALS['BAB_SESS_USERID'] && 'PUBLIC' !== $calPeriod->getProperty('CLASS');
					$evt['nbowners'] = isset($arr['nbowners']) ? $arr['nbowners']+1 : 1;
					$evt['t_option'] = ''; 
					$evt['properties'] = bab_toHtml(getPropertiesStringObj($calPeriod, $evt['t_option']));


					$evt['location']=bab_toHtml($calPeriod->getProperty('LOCATION'));
					global $babDB;
					$res_note = $babDB->db_query("select note from ".BAB_CAL_EVENTS_NOTES_TBL." where id_user='".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."' and id_event='".$babDB->db_escape_string($arr['id'])."'");
					if( $res_note && $babDB->db_num_rows($res_note) > 0 )
						{
						$arr_notes = $babDB->db_fetch_array($res_note);
						$evt['notes'] = bab_toHtml($arr_notes['note'], BAB_HTML_ALL);
						}
					else
						{
						$evt['notes'] = '';
						}

					$sortvalue[$xCtoPuid] = $calPeriod->getProperty('DTSTART');
					}
				}
			
			if (isset($sortvalue))
				{

				asort($sortvalue);
				reset($sortvalue);

				while (list ($arr_key, $arr_val) = each ($sortvalue)) {
						 $new_array[$arr_key] = $this->resevent[$arr_key];
						}

				$this->resevent = $new_array;
				}

			}

		


		function getnextevent() {
			if (list($this->idevent,$this->evt) = each($this->resevent)) {
				return true;
			}
			
			return false;
		}

		function getnextcalendar() {
			if (list($this->id,$this->calendar) = each($this->evt['cals'])) {
				$this->calendar['type'] = bab_toHtml($this->calendar['type']);
				$this->calendar['name'] = bab_toHtml($this->calendar['name']);
				return true;
			}
			
			return false;
		}

		function printout()
			{
			$GLOBALS['babBodyPopup'] = new babBodyPopup();
			$GLOBALS['babBodyPopup']->title = bab_translate("Detailed sight");
			$GLOBALS['babBodyPopup']->babecho(bab_printTemplate($this, "calendar.html", "eventlist"));
			printBabBodyPopup();
			die();
			}
		}

	$temp = new eventlist($from, $to, $idcals);
	$temp->printout();

}

function updateEventNotes($evtid, $note, $bupdrec)
{
	global $babDB;
	if( !empty($GLOBALS['BAB_SESS_USERID']) )
	{

		$evtidarr = array();

		if( $bupdrec == 1 )
		{
			list($hash) = $babDB->db_fetch_row($babDB->db_query("select hash from ".BAB_CAL_EVENTS_TBL." where id='".$babDB->db_escape_string($evtid)."'"));
			if( !empty($hash) &&  $hash[0] == 'R')
				{
				$res = $babDB->db_query("select id from ".BAB_CAL_EVENTS_TBL." where hash='".$babDB->db_escape_string($hash)."'");
				while( $arr = $babDB->db_fetch_array($res))
					{
					$evtidarr[] = $arr['id'];
					}
				}
		}

		if( count($evtidarr) == 0 )
			{
			$evtidarr[] = $evtid;
			}

		$updevtarr = array();

		$res = $babDB->db_query("select id_event from ".BAB_CAL_EVENTS_NOTES_TBL." where id_event in(".$babDB->quote($evtidarr).") and id_user='".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."'");
		while( $arr = $babDB->db_fetch_array($res))
		{
			$updevtarr[$arr['id_event']] = 1;
		}

		for( $i=0; $i < count($evtidarr); $i++ )
		{

		if( isset($updevtarr[$evtidarr[$i]] ) )
			{
			$babDB->db_query("update ".BAB_CAL_EVENTS_NOTES_TBL." set note='".$babDB->db_escape_string($note)."'  where id_event='".$babDB->db_escape_string($evtidarr[$i])."' and id_user='".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."'");
			}
		else
			{
			$babDB->db_query("insert into ".BAB_CAL_EVENTS_NOTES_TBL." ( id_event, id_user, note ) values ('".$babDB->db_escape_string($evtidarr[$i])."', '".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."', '".$babDB->db_escape_string($note)."')");
			}
		}
	}
}

function updateEventAlert()
{

	$evtid = bab_rp('evtid');
	$bupdrec = bab_rp('bupdrec', 2);
	$creminder = bab_rp('creminder', 'N');
	$day = bab_rp('day');
	$hour = bab_rp('hour');
	$minute = bab_rp('minute');
	$remail = bab_rp('remail');

	global $babDB;
	if( !empty($GLOBALS['BAB_SESS_USERID']) )
	{
		if( $creminder == 'Y')
		{
			$res= $babDB->db_query("select id_event from ".BAB_CAL_EVENTS_REMINDERS_TBL." where id_event='".$babDB->db_escape_string($evtid)."' and id_user='".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."'");
			if( $res && $babDB->db_num_rows($res) > 0 )
			{
				$babDB->db_query("update ".BAB_CAL_EVENTS_REMINDERS_TBL." set day='".$babDB->db_escape_string($day)."', hour='".$babDB->db_escape_string($hour)."', minute='".$babDB->db_escape_string($minute)."', bemail='".$babDB->db_escape_string($remail)."', processed='N' where id_event='".$babDB->db_escape_string($evtid)."' and id_user='".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."'");
			}
			else
			{
				$babDB->db_query("insert into ".BAB_CAL_EVENTS_REMINDERS_TBL." (id_event, id_user, day, hour, minute, bemail) values ('".$babDB->db_escape_string($evtid)."', '".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."', '".$babDB->db_escape_string($day)."', '".$babDB->db_escape_string($hour)."', '".$babDB->db_escape_string($minute)."', '".$babDB->db_escape_string($remail)."')");
			}
		}
		else
		{
			$babDB->db_query("delete from ".BAB_CAL_EVENTS_REMINDERS_TBL." where id_event='".$babDB->db_escape_string($evtid)."' and id_user='".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."'");
		}
	}
}


/* main */

$idx = bab_rp('idx');
if( isset($_REQUEST['conf']) )
{
	$conf = $_REQUEST['conf'];
	
	if( $conf == "event" )
		{
		confirmEvent(
			bab_rp('evtid'), 
			bab_rp('idcal'), 
			bab_rp('bconfirm'), 
			bab_rp('comment'), 
			bab_rp('bupdrec', 2)
		);
		$reload = true;
		}
	elseif( $conf == "note" )
		{
		updateEventNotes(
			bab_rp('evtid'), 
			bab_rp('note'), 
			bab_rp('bupdrec', 2)
		);
		$reload = true;
		}
	elseif( $conf == "alert" )
		{
		updateEventAlert();
		$reload = true;
		}
}

switch($idx)
	{
	case "unload":
		include_once $babInstallPath."utilit/uiutil.php";
		$popupmessage = bab_translate("Your event has been updated");
		if( !isset($reload)) { $reload = false; }
		popupUnload($popupmessage, '', $reload);
		exit;
		break;

	case "evtnote":
		include_once $babInstallPath."utilit/uiutil.php";
		$babBodyPopup = new babBodyPopup();
		$babBodyPopup->title = bab_translate("Personal notes");
		displayEventDetail(
			bab_rp('evtid'),
			bab_rp('idcal')
		);
		if (!empty($GLOBALS['BAB_SESS_USERID']))
		{
			displayEventNotes(
				bab_rp('evtid'),
				bab_rp('idcal')
			);
		}
		printBabBodyPopup();
		exit;
		break;

	case "viewc":
		include_once $babInstallPath."utilit/uiutil.php";
		$babBodyPopup = new babBodyPopup();
		$babBodyPopup->title = bab_translate("Categories");
		categoriesList();
		printBabBodyPopup();
		exit;
		break;

	case "veventupd":
	case "vevent":
	case "attendees":
		include_once $babInstallPath."utilit/uiutil.php";
		$babBodyPopup = new babBodyPopup();
		$babBodyPopup->title = bab_translate("Event Detail");
		displayEventDetail(
			bab_rp('evtid'),
			bab_rp('idcal')
		);
		if ($idx == "attendees")
			{
			$babBodyPopup->title = bab_translate("Attendees");
			displayAttendees(
				bab_rp('evtid'),
				bab_rp('idcal')
			);
			}
		if ($idx == "veventupd" && !empty($GLOBALS['BAB_SESS_USERID']))
			{
			displayEventNotes(
				bab_rp('evtid'), 
				bab_rp('idcal')
			);
			displayEventAlert(
				bab_rp('evtid'), 
				bab_rp('idcal')
			);
			}
		printBabBodyPopup();
		exit;
		break;
	case 'eventlist':
		eventlist($_GET['from'],$_GET['to'],$_GET['calid']);
		break;
	default:
		break;
	}
$babBody->setCurrentItemMenu($idx);
?>
