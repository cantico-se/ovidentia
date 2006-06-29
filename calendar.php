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
include_once $babInstallPath."utilit/calincl.php";
include_once $babInstallPath."utilit/evtincl.php";
 
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
				$this->fullnametxt = bab_translate("Attendee");
				$this->statusdef = array(BAB_CAL_STATUS_ACCEPTED => bab_translate("Accepted"), BAB_CAL_STATUS_NONE => "", BAB_CAL_STATUS_DECLINED => bab_translate("Declined"));
				$this->statustxt = bab_translate("Response");
				list($this->idcreator, $this->hash) = $babDB->db_fetch_row($babDB->db_query("select id_creator, hash from ".BAB_CAL_EVENTS_TBL." where id='".$evtid."'"));
				$res = $babDB->db_query("select ceo.* from ".BAB_CAL_EVENTS_OWNERS_TBL." ceo where ceo.id_event='".$evtid."'");
				$this->arrinfo = array();
				$this->statusarray = array();
				$arrschi = bab_getWaitingIdSAInstance($GLOBALS['BAB_SESS_USERID']);
				while( $arr = $babDB->db_fetch_array($res))
					{
					if( bab_isCalendarAccessValid($arr['id_cal']))
						{
						$icalinfo = $babBody->icalendars->getCalendarInfo($arr['id_cal']);
						$this->arrinfo[] = array('name' => $icalinfo['name'],'idcal' => $arr['id_cal'], 'idowner' => $icalinfo['idowner'],'status' => $arr['status']);
						if( $idcal == $arr['id_cal'] )
							{
							switch($icalinfo['type'])
								{
								case BAB_CAL_USER_TYPE:
									if( $icalinfo['access'] == BAB_CAL_ACCESS_FULL)
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
			if( $i < $this->count)
				{
				$this->altbg = $this->altbg ? false : true;
				$this->fullname = $this->arrinfo[$i]['name'];
				$this->bcreator = false;
				if( $this->arrinfo[$i]['idowner'] ==  $this->idcreator )
					{
					$this->bcreator = true;
					}
				$this->status = $this->statusdef[$this->arrinfo[$i]['status']];
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
				$res = $babDB->db_query("select * from ".BAB_CAL_EVENTS_TBL." where id='".$evtid."'");
				if( $res && $babDB->db_num_rows($res) == 1)
					{
					$this->access = true;
					$arr = $babDB->db_fetch_array($res);
					$iarr = $babBody->icalendars->getCalendarInfo($idcal);
					$this->begindatetxt = bab_translate("Begin date");
					$this->enddatetxt = bab_translate("End date");
					$this->titletxt = bab_translate("Title");
					$this->desctxt = bab_translate("Description");
					$this->locationtxt = bab_translate("Location");
					$this->cattxt = bab_translate("Category");
					$this->begindate = bab_longDate(bab_mktime($arr['start_date']));
					$this->enddate = bab_longDate(bab_mktime($arr['end_date']));

					$this->t_option = ''; 
					$this->properties = getPropertiesString($arr, $this->t_option);

					if( $arr['bprivate'] ==  'Y' && $GLOBALS['BAB_SESS_USERID']  != $iarr['idowner'])
						{
						$this->title= '';
						$this->description = '';
						$this->location = '';
						}
					else
						{
						$this->title= $arr['title'];
						$this->description = bab_replace($arr['description']);
						$this->location= $arr['location'];
						}
					if( $arr['id_cat'] != 0 )
						{
						list($this->category) = $babDB->db_fetch_row($babDB->db_query("select name from ".BAB_CAL_CATEGORIES_TBL." where id='".$arr['id_cat']."'"));
						}
					else
						{
						$this->category = "";
						}
					}
				else
					{
					$babBodyPopup->msgerror = bab_translate("Access denied");
					}
				}
				else
					{
					$babBodyPopup->msgerror = bab_translate("Access denied");
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
				$this->notetxt = bab_translate("Personal notes");
				$this->updatetxt = bab_translate("Update");
				$res = $babDB->db_query("select note from ".BAB_CAL_EVENTS_NOTES_TBL." where id_user='".$GLOBALS['BAB_SESS_USERID']."' and id_event='".$evtid."'");
				if( $res && $babDB->db_num_rows($res) > 0 )
					{
					$arr = $babDB->db_fetch_array($res);
					$this->noteval = htmlentities($arr['note']);
					}
				else
					{
					$this->noteval = '';
					}
				list($hash) = $babDB->db_fetch_row($babDB->db_query("select hash from ".BAB_CAL_EVENTS_TBL." where id='".$evtid."'"));
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
				$this->alerttxt = bab_translate("Reminder");
				$this->updatetxt = bab_translate("Update");
				$res = $babDB->db_query("select * from ".BAB_CAL_EVENTS_REMINDERS_TBL." where id_user='".$GLOBALS['BAB_SESS_USERID']."' and id_event='".$evtid."'");
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

				list($hash) = $babDB->db_fetch_row($babDB->db_query("select hash from ".BAB_CAL_EVENTS_TBL." where id='".$evtid."'"));
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
				$this->name = $arr['name'];
				$this->description = $arr['description'];
				$this->bgcolor = $arr['bgcolor'];
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

			$last_ts = 0;

			foreach ($idcals as $idcal)
				{
				while ($this->mcals->getNextEvent($idcal, $this->from, $this->to, $arr))
					{
					if (!isset($this->resevent[$arr['id_event']]))
						{
						$this->resevent[$arr['id_event']] = array();
						$this->resevent[$arr['id_event']]['cals'] = array();
						}

					$evt = & $this->resevent[$arr['id_event']];
					switch($this->mcals->getCalendarType($arr['id_cal']))
						{
						case 1:
							$type = bab_translate('User');
							break;
						case 2:
							$type = bab_translate('Public');
							break;
						case 3:
							$type = bab_translate('Resource');
							break;
						}
					$evt['cals'][$arr['id_cal']] = array('name' => $this->mcals->getCalendarName($arr['id_cal']), 'type' => $type);
					$evt['title'] = $arr['title'];
					$evt['description'] = $arr['description'];
					$ts = bab_mktime($arr['end_date']);
					if ($ts <= time() && $last_ts < $ts)
						{
						$last_ts = $ts;
						$this->last_id = $arr['id_event'];
						}
					$evt['start_date'] = bab_longDate(bab_mktime($arr['start_date']));
					$evt['end_date'] = bab_longDate($ts);
					$evt['categoryname'] = !empty($this->mcals->categories[$arr['id_cat']]) ? $this->mcals->categories[$arr['id_cat']]['name'] : '';
					$evt['categorydescription'] = !empty($this->mcals->categories[$arr['id_cat']]) ? $this->mcals->categories[$arr['id_cat']]['description'] : '';
					$evt['color'] = !empty($this->mcals->categories[$arr['id_cat']]) ? $this->mcals->categories[$arr['id_cat']]['bgcolor'] : $arr['color'];
					$evt['creator'] = $arr['id_creator'] != $GLOBALS['BAB_SESS_USERID'] ? bab_getUserName($arr['id_creator']) : '';
					$evt['private'] = $arr['id_creator'] != $GLOBALS['BAB_SESS_USERID'] && (isset($arr['bprivate']) && $arr['bprivate'] == 'Y');
					$evt['nbowners'] = $arr['nbowners']+1;
					$evt['t_option'] = ''; 
					$evt['properties'] = getPropertiesString($arr, $evt['t_option']);

					$sortvalue[$arr['id_event']] = $arr['start_date'];
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

		function getnextevent()
			{
			return list($this->idevent,$this->evt) = each($this->resevent);
			}

		function getnextcalendar()
			{
			return list($this->id,$this->calendar) = each($this->evt['cals']);
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

		$note = $babDB->db_escape_string($note);

		$evtidarr = array();

		if( $bupdrec == 1 )
		{
			list($hash) = $babDB->db_fetch_row($babDB->db_query("select hash from ".BAB_CAL_EVENTS_TBL." where id='".$evtid."'"));
			if( !empty($hash) &&  $hash[0] == 'R')
				{
				$res = $babDB->db_query("select id from ".BAB_CAL_EVENTS_TBL." where hash='".$hash."'");
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

		$res = $babDB->db_query("select id_event from ".BAB_CAL_EVENTS_NOTES_TBL." where id_event in (".implode(',', $evtidarr).") and id_user='".$GLOBALS['BAB_SESS_USERID']."'");
		while( $arr = $babDB->db_fetch_array($res))
		{
			$updevtarr[$arr['id_event']] = 1;
		}

		for( $i=0; $i < count($evtidarr); $i++ )
		{

		if( isset($updevtarr[$evtidarr[$i]] ) )
			{
			$babDB->db_query("update ".BAB_CAL_EVENTS_NOTES_TBL." set note='".$note."'  where id_event='".$evtidarr[$i]."' and id_user='".$GLOBALS['BAB_SESS_USERID']."'");
			}
		else
			{
			$babDB->db_query("insert into ".BAB_CAL_EVENTS_NOTES_TBL." ( id_event, id_user, note ) values ('".$evtidarr[$i]."', '".$GLOBALS['BAB_SESS_USERID']."', '".$note."')");
			}
		}
	}
}

function updateEventAlert($evtid, $creminder, $day, $hour, $minute, $remail, $bupdrec)
{
	global $babDB;
	if( !empty($GLOBALS['BAB_SESS_USERID']) )
	{
		if( $creminder == 'Y')
		{
			$res= $babDB->db_query("select id_event from ".BAB_CAL_EVENTS_REMINDERS_TBL." where id_event='".$evtid."' and id_user='".$GLOBALS['BAB_SESS_USERID']."'");
			if( $res && $babDB->db_num_rows($res) > 0 )
			{
				$babDB->db_query("update ".BAB_CAL_EVENTS_REMINDERS_TBL." set day='".$day."', hour='".$hour."', minute='".$minute."', bemail='".$remail."', processed='N' where id_event='".$evtid."' and id_user='".$GLOBALS['BAB_SESS_USERID']."'");
			}
			else
			{
				$babDB->db_query("insert into ".BAB_CAL_EVENTS_REMINDERS_TBL." (id_event, id_user, day, hour, minute, bemail) values ('".$evtid."', '".$GLOBALS['BAB_SESS_USERID']."', '".$day."', '".$hour."', '".$minute."', '".$remail."')");
			}
		}
		else
		{
			$babDB->db_query("delete from ".BAB_CAL_EVENTS_REMINDERS_TBL." where id_event='".$evtid."' and id_user='".$GLOBALS['BAB_SESS_USERID']."'");
		}
	}
}


/* main */
if( isset($conf) )
{
	if( $conf == "event" )
		{
		if( !isset($bupdrec)) { $bupdrec = 2; }
		confirmEvent($evtid, $idcal, $bconfirm, $comment, $bupdrec);
		$reload = true;
		}
	elseif( $conf == "note" )
		{
		if( !isset($bupdrec)) { $bupdrec = 2;}
		updateEventNotes($evtid, $note, $bupdrec);
		$reload = true;
		}
	elseif( $conf == "alert" )
		{
		if( !isset($bupdrec)) { $bupdrec = 2;}
		if( !isset($creminder)) { $creminder = 'N';}
		if( !isset($day)) { $day = '';}
		if( !isset($hour)) { $hour = '';}
		if( !isset($minute)) { $minute = '';}
		if( !isset($remail)) { $remail = 'N';}
		updateEventAlert($evtid, $creminder, $day, $hour, $minute, $remail, $bupdrec);
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
		displayEventDetail($evtid, $idcal);
		if (!empty($GLOBALS['BAB_SESS_USERID']))
		{
			displayEventNotes($evtid, $idcal);
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
		displayEventDetail($evtid, $idcal);
		if ($idx == "attendees")
			{
			$babBodyPopup->title = bab_translate("Attendees");
			displayAttendees($evtid, $idcal);
			}
		if ($idx == "veventupd" && !empty($GLOBALS['BAB_SESS_USERID']))
			{
			displayEventNotes($evtid, $idcal);
			displayEventAlert($evtid, $idcal);
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
