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

function bab_getCalendarType($idcal)
{
	$db = $GLOBALS['babDB'];
	$query = "select type from ".BAB_CALENDAR_TBL." where id='$idcal'";
	$res = $db->db_query($query);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		return $arr['type'];
		}
	else
		{
		return 0;
		}
}

function bab_getCalendarOwner($idcal)
{
	$db = $GLOBALS['babDB'];
	$query = "select owner from ".BAB_CALENDAR_TBL." where id='$idcal'";
	$res = $db->db_query($query);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		return $arr['owner'];
		}
	else
		{
		return 0;
		}
}

function bab_getCalendarOwnerName($idcal, $type)
{
	$ret = "";
	$db = $GLOBALS['babDB'];

	$res = $db->db_query("select type, owner from ".BAB_CALENDAR_TBL." where id='$idcal'");
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		if( $arr['type'] == BAB_CAL_USER_TYPE)
			{
			return bab_getUserName( $arr['owner']);
			}
		else if( $arr['type'] == BAB_CAL_PUB_TYPE)
			{
			$arr = $db->db_fetch_array($db->db_query("select name from ".BAB_CAL_PUBLIC_TBL." where id='".$arr['owner']."'"));
			return $arr['name'];
			}
		else if( $arr['type'] == BAB_CAL_RES_TYPE)
			{
			$arr = $db->db_fetch_array($db->db_query("select name from ".BAB_CAL_RESOURCES_TBL." where id='".$arr['owner']."'"));
			return $arr['name'];
			}
		}

	return $ret;
}

function bab_isCalendarAccessValid($calid)
	{
	global $babBody;
	$db = $GLOBALS['babDB'];
	$ret = array();
	$babBody->icalendars->initializeCalendars();

	$calid = explode(',', $calid);

	if( $babBody->icalendars->id_percal != 0 && in_array($babBody->icalendars->id_percal, $calid))
		{
		$ret[] = $babBody->icalendars->id_percal;
		}

	if( count($babBody->icalendars->usercal) > 0 )
		{
		reset($babBody->icalendars->usercal);
		while( $row=each($babBody->icalendars->usercal) ) 
			{
			if( in_array($row[0], $calid))
				{
				$ret[] = $row[0];
				}
			}
		}

	if( count($babBody->icalendars->pubcal) > 0 )
		{
		reset($babBody->icalendars->pubcal);
		while( $row=each($babBody->icalendars->pubcal) ) 
			{
			if( in_array($row[0], $calid))
				{
				$ret[] = $row[0];
				}
			}
		}
		
	if( count($babBody->icalendars->rescal) > 0 )
		{
		reset($babBody->icalendars->rescal);
		while( $row=each($babBody->icalendars->rescal) ) 
			{
			if( in_array($row[0], $calid))
				{
				$ret[] = $row[0];
				}
			}
		}

	if( count($ret) > 0 )
		{
		$result = implode(',', $ret);
		}
	else
		{
		$result = false;
		}
	return $result;
	}


function getAvailableUsersCalendars($bwrite = false)
{
	global $babBody, $BAB_SESS_USERID,$BAB_SESS_USER;
	$babBody->icalendars->initializeCalendars();

	$tab = array();

	if( $babBody->icalendars->id_percal != 0 )
		{
		$tab[] = array('idcal' => $babBody->icalendars->id_percal, 'name' => $GLOBALS['BAB_SESS_USER']);
		}

	if( count($babBody->icalendars->usercal) > 0 )
		{
		reset($babBody->icalendars->usercal);
		while( $row=each($babBody->icalendars->usercal) ) 
			{
			if( $bwrite )
				{
				if( $row[1]['access'] == BAB_CAL_ACCESS_UPDATE || $row[1]['access'] == BAB_CAL_ACCESS_FULL )
					{
					$tab[] = array('idcal' => $row[0], 'name' => $row[1]['name']);
					}
				}
			else
				{
				$tab[] =  array('idcal' => $row[0], 'name' => $row[1]['name']);
				}
			}
		}

	return $tab;
}	


function getAvailableGroupsCalendars($bwrite = false)
{
	global $babBody;
	$babBody->icalendars->initializeCalendars();
	$tab = array();

	if( count($babBody->icalendars->pubcal) > 0 )
		{
		reset($babBody->icalendars->pubcal);
		while( $row=each($babBody->icalendars->pubcal) ) 
			{
			if( $bwrite )
				{
				if( $row[1]['manager'])
					{
					$tab[] = array('idcal' => $row[0], 'name' => $row[1]['name']);
					}
				}
			else
				{
				$tab[] =  array('idcal' => $row[0], 'name' => $row[1]['name']);
				}
			}
		}

	return $tab;
}


function getAvailableResourcesCalendars($bwrite = false)
{
	global $babBody, $BAB_SESS_USERID,$BAB_SESS_USER;
	$babBody->icalendars->initializeCalendars();
	$tab = array();

	if( count($babBody->icalendars->rescal) > 0 )
		{
		reset($babBody->icalendars->rescal);
		while( $row=each($babBody->icalendars->rescal) ) 
			{
			if( $bwrite )
				{
				if( $row[1]['manager'])
					{
					$tab[] = array('idcal' => $row[0], 'name' => $row[1]['name']);
					}
				}
			elseif( $row[1]['view'] || $row[1]['manager'])
				{
				$tab[] =  array('idcal' => $row[0], 'name' => $row[1]['name']);
				}
			}
		}

	return $tab;
}


function createEvent($idcals, $title, $description, $startdate, $enddate, $category, $color, $status, $hash='')
{
	global $babDB;

	if( bab_isMagicQuotesGpcOn())
		{
		$title = stripslashes($title);
		$description = stripslashes($description);
		}

	$babDB->db_query("insert into ".BAB_CAL_EVENTS_TBL." ( title, description, start_date, end_date, id_cat, id_creator, color, hash) values ('".addslashes($title)."', '".addslashes($description)."', '".date('Y-m-d H:i:s',$startdate)."', '".date('Y-m-d H:i:s',$enddate)."', '".$category."', '".$GLOBALS['BAB_SESS_USERID']."', '".$color."', '".$hash."')");
	
	$id_event = $babDB->db_insert_id();

	foreach($idcals as $id_cal)
		{
		$babDB->db_query("INSERT INTO ".BAB_CAL_EVENTS_OWNERS_TBL." (id_event,id_cal, status) VALUES ('".$id_event."','".$id_cal."', '".$status."')");
		}
}


class bab_icalendars
{
	var $id_percal = 0; // personal calendar
	var $usercal = array(); // other users personal calendars
	var $pubcal = array(); // public calendars
	var $rescal = array(); // resources calendars
	var $busercal = false; // personnal calendar
	var $bpubcal = false; // public calendar
	var $brescal = false; // resource calendar

	function bab_icalendars()
	{
		global $babBody, $babDB;

		$pcalendar = false;
		$this->startday = 1;
		$this->allday = 'Y';
		$this->usebgcolor = 'Y';
		$this->elapstime = 60;
		$this->defaultview = BAB_CAL_VIEW_MONTH;
		$this->starttime = "08:00:00";
		$this->endtime = "18:00:00";
		$this->workdays = "1,2,3,4,5";
		$this->user_calendarids = '';

		if( !empty($GLOBALS['BAB_SESS_USERID']))
		{
		reset($babBody->ovgroups);
		while( $row=each($babBody->ovgroups) ) 
			{ 
			if( $row[1]['member'] == 'Y' && $row[1]['pcalendar'] == 'Y')
				{
				$pcalendar = true;
				}
			}

		if( $pcalendar )
			{
			$res = $babDB->db_query("select id from ".BAB_CALENDAR_TBL." where owner='".$GLOBALS['BAB_SESS_USERID']."' and actif='Y' and type='1'");
			if( $res && $babDB->db_num_rows($res) >  0)
				{
				$arr = $babDB->db_fetch_array($res);
				$this->id_percal = $arr['id'];
				}		
			}

		$res = $babDB->db_query("select * from ".BAB_CAL_USER_OPTIONS_TBL." where id_user='".$GLOBALS['BAB_SESS_USERID']."'");
		if( $res && $babDB->db_num_rows($res) >  0)
			{
			$arr = $babDB->db_fetch_array($res);
			$this->startday = $arr['startday'];
			$this->allday = $arr['allday'];
			$this->usebgcolor = $arr['usebgcolor'];
			$this->elapstime = $arr['elapstime'];
			$this->defaultview = $arr['defaultview'];
			$this->starttime = $arr['start_time'];
			$this->endtime = $arr['end_time'];
			$this->workdays = $arr['work_days'];
			$this->user_calendarids = $arr['user_calendarids'];
			}
		}
		if( empty($this->user_calendarids) && $this->id_percal != 0)
			{
			$this->user_calendarids = $this->id_percal;
			}
	}

	function initializePublicCalendars()
	{
		global $babDB;
		$this->bpubcal = true;

		$res = $babDB->db_query("select cpt.*, ct.id as idcal, ct.owner from ".BAB_CAL_PUBLIC_TBL." cpt left join ".BAB_CALENDAR_TBL." ct on ct.owner=cpt.id where ct.type='".BAB_CAL_PUB_TYPE."' and ct.actif='Y'");
		while( $arr = $babDB->db_fetch_array($res))
			{
			$bgroup = bab_isAccessValid(BAB_CAL_PUB_GRP_GROUPS_TBL, $arr['idcal']);
			$bview = bab_isAccessValid(BAB_CAL_PUB_VIEW_GROUPS_TBL, $arr['idcal']);
			$bman = bab_isAccessValid(BAB_CAL_PUB_MAN_GROUPS_TBL, $arr['idcal']);

			if ($bgroup || $bview || $bman)
				{
				$this->pubcal[$arr['idcal']]['name'] = $arr['name'];
				$this->pubcal[$arr['idcal']]['description'] = $arr['description'];
				$this->pubcal[$arr['idcal']]['type'] = BAB_CAL_PUB_TYPE;
				$this->pubcal[$arr['idcal']]['idowner'] = $arr['owner'];
				
				$this->pubcal[$arr['idcal']]['group'] = $bgroup;
				$this->pubcal[$arr['idcal']]['view'] = $bview;
				$this->pubcal[$arr['idcal']]['manager'] = $bman;
				}

			}
		if( empty($this->user_calendarids) && count($this->pubcal) > 0)
			{
			$keys = array_keys($this->pubcal);
			$this->user_calendarids = $keys[0];
			}
	}

	function initializeResourceCalendars()
	{
		global $babDB;
		$this->brescal = true;

		$res = $babDB->db_query("select crt.*, ct.id as idcal, ct.owner from ".BAB_CAL_RESOURCES_TBL." crt left join ".BAB_CALENDAR_TBL." ct on ct.owner=crt.id where ct.type='".BAB_CAL_RES_TYPE."' and ct.actif='Y'");
		while( $arr = $babDB->db_fetch_array($res))
		{

			$bgroup = bab_isAccessValid(BAB_CAL_RES_GRP_GROUPS_TBL, $arr['idcal']);
			$bview = bab_isAccessValid(BAB_CAL_RES_VIEW_GROUPS_TBL, $arr['idcal']);
			$bman = bab_isAccessValid(BAB_CAL_RES_MAN_GROUPS_TBL, $arr['idcal']);

			if ($bgroup || $bview || $bman)
				{
				$this->rescal[$arr['idcal']]['name'] = $arr['name'];
				$this->rescal[$arr['idcal']]['description'] = $arr['description'];
				$this->rescal[$arr['idcal']]['type'] = BAB_CAL_RES_TYPE;
				$this->rescal[$arr['idcal']]['idowner'] = $arr['owner'];

				$this->rescal[$arr['idcal']]['group'] = $bgroup;
				$this->rescal[$arr['idcal']]['view'] = $bview;
				$this->rescal[$arr['idcal']]['manager'] = $bman;
				}

		}
		if( empty($this->user_calendarids) && count($this->rescal) > 0)
			{
			$keys = array_keys($this->rescal);
			$this->user_calendarids = $keys[0];
			}
	}

	function initializeUserCalendars()
	{
		global $babDB;
		$this->busercal = true;

		$res = $babDB->db_query("select cut.*, ct.owner from ".BAB_CALACCESS_USERS_TBL." cut left join ".BAB_CALENDAR_TBL." ct on ct.id=cut.id_cal where id_user='".$GLOBALS['BAB_SESS_USERID']."' and ct.actif='Y'");

		while( $arr = $babDB->db_fetch_array($res))
		{
			$this->usercal[$arr['id_cal']]['name'] = bab_getUserName($arr['owner']);
			$this->usercal[$arr['id_cal']]['description'] = '';
			$this->usercal[$arr['id_cal']]['type'] = BAB_CAL_USER_TYPE;
			$this->usercal[$arr['id_cal']]['idowner'] = $arr['owner'];
			$this->usercal[$arr['id_cal']]['access'] = $arr['bwrite'];
		}
		if( empty($this->user_calendarids) && count($this->usercal) > 0)
			{
			$keys = array_keys($this->usercal);
			$this->user_calendarids = $keys[0];
			}
	}

	function calendarAccess()
	{
		if( $this->id_percal != 0 )
		{
			return true;
		}

		if( !$this->bpubcal )
		{
			$this->initializePublicCalendars();
		}

		if( count($this->pubcal) > 0 )
		{
			return true;
		}

		if( !$this->brescal )
		{
			$this->initializeResourceCalendars();
		}

		if( count($this->rescal) > 0 )
		{
			return true;
		}

		if( !$this->busercal )
		{
			$this->initializeUserCalendars();
		}

		if( count($this->usrcal) > 0 )
		{
			return true;
		}

	}

	function initializeCalendars()
	{
		if( !$this->bpubcal )
		{
			$this->initializePublicCalendars();
		}

		if( !$this->brescal )
		{
			$this->initializeResourceCalendars();
		}

		if( !$this->busercal )
		{
			$this->initializeUserCalendars();
		}
	}

	function getCalendarName($idcal)
	{
		if( $idcal == $this->id_percal )
		{
			return $GLOBALS['BAB_SESS_USER'];
		}
		else
		{
			$this->initializeCalendars();
			if( count($this->pubcal) > 0 )
			{
				reset($this->pubcal);
				while( $row=each($this->pubcal) ) 
					{ 
					if( $row[0] == $idcal)
						{
						return $row[1]['name'];
						}
					}
			}
			
			if( count($this->rescal) > 0 )
			{
				reset($this->rescal);
				while( $row=each($this->rescal) ) 
					{ 
					if( $row[0] == $idcal)
						{
						return $row[1]['name'];
						}
					}
			}

			if( count($this->usercal) > 0 )
			{
				reset($this->usercal);
				while( $row=each($this->usercal) ) 
					{ 
					if( $row[0] == $idcal)
						{
						return $row[1]['name'];
						}
					}
			}
		}
	return false;
	}

	function getCalendarType($idcal)
	{
		if( $idcal == $this->id_percal )
		{
			return BAB_CAL_USER_TYPE;
		}
		else
		{
			$this->initializeCalendars();
			if( count($this->pubcal) > 0 )
			{
				reset($this->pubcal);
				while( $row=each($this->pubcal) ) 
					{ 
					if( $row[0] == $idcal)
						{
						return BAB_CAL_PUB_TYPE;
						}
					}
			}
			
			if( count($this->rescal) > 0 )
			{
				reset($this->rescal);
				while( $row=each($this->rescal) ) 
					{ 
					if( $row[0] == $idcal)
						{
						return BAB_CAL_RES_TYPE;
						}
					}
			}

			if( count($this->usercal) > 0 )
			{
				reset($this->usercal);
				while( $row=each($this->usercal) ) 
					{ 
					if( $row[0] == $idcal)
						{
						return BAB_CAL_USER_TYPE;
						}
					}
			}
		}

	return false;
	}

	function getCalendarOwner($idcal)
	{
		if( $idcal == $this->id_percal )
		{
			return $GLOBALS['BAB_SESS_USER'];
		}
		else
		{
			$this->initializeCalendars();
			if( count($this->pubcal) > 0 )
			{
				reset($this->pubcal);
				while( $row=each($this->pubcal) ) 
					{ 
					if( $row[0] == $idcal)
						{
						return $row[1]['owner'];
						}
					}
			}
			
			if( count($this->rescal) > 0 )
			{
				reset($this->rescal);
				while( $row=each($this->rescal) ) 
					{ 
					if( $row[0] == $idcal)
						{
						return $row[1]['owner'];
						}
					}
			}

			if( count($this->usercal) > 0 )
			{
				reset($this->usercal);
				while( $row=each($this->usercal) ) 
					{ 
					if( $row[0] == $idcal)
						{
						return $row[1]['owner'];
						}
					}
			}
		}
	return false;
	}


}


function bab_deleteCalendar($idcal)
{
	global $babDB;

	list($type, $owner) = $babDB->db_fetch_row($babDB->db_query("select type, owner from ".BAB_CALENDAR_TBL." where id='".$idcal."'"));

	switch( $type )
		{
		case BAB_CAL_PUB_TYPE:
			$babDB->db_query("delete from ".BAB_CAL_PUBLIC_TBL." where id='".$owner."'");
			$babDB->db_query("delete from ".BAB_CAL_PUB_MAN_GROUPS_TBL." where id_object='".$owner."'");
			$babDB->db_query("delete from ".BAB_CAL_PUB_GRP_GROUPS_TBL." where id_object='".$owner."'");
			$babDB->db_query("delete from ".BAB_CAL_PUB_VIEW_GROUPS_TBL." where id_object='".$owner."'");
			break;
		case BAB_CAL_RES_TYPE:
			$babDB->db_query("delete from ".BAB_CAL_RESOURCES_TBL." where id='".$owner."'");
			$babDB->db_query("delete from ".BAB_CAL_RES_MAN_GROUPS_TBL." where id_object='".$owner."'");
			$babDB->db_query("delete from ".BAB_CAL_RES_GRP_GROUPS_TBL." where id_object='".$owner."'");
			$babDB->db_query("delete from ".BAB_CAL_RES_VIEW_GROUPS_TBL." where id_object='".$owner."'");
			break;
		case BAB_CAL_USER_TYPE:
			$babDB->db_query("delete from ".BAB_CALACCESS_USERS_TBL." where id_cal='".$idcal."'");	
			$babDB->db_query("delete from ".BAB_CALACCESS_USERS_TBL." where id_user='".$arr['owner']."'");	
			$babDB->db_query("delete from ".BAB_CAL_USER_OPTIONS_TBL." where id_user='".$arr['owner']."'");	
			break;
		}

	$babDB->db_query("delete from ".BAB_CAL_EVENTS_TBL." where id_cal='".$idcal."'");	
	$babDB->db_query("delete from ".BAB_CALENDAR_TBL." where id='".$idcal."'");	
}



function calendarchoice($formname)
{
class calendarchoice
	{
	function calendarchoice($formname)
		{
		$this->formname = $formname;
		$this->db = $GLOBALS['babDB'];
		$icalendars = &$GLOBALS['babBody']->icalendars;
		$icalendars->initializeCalendars();
		$this->selectedCalendars = isset($icalendars->user_calendarids) ? explode(',',$icalendars->user_calendarids) : array();

		$this->usrcalendarstxt = bab_translate('Users');
		$this->grpcalendarstxt = bab_translate('Collectifs');
		$this->rescalendarstxt = bab_translate('Resources');
		$this->t_goright = bab_translate('Push right');
		$this->t_goleft = bab_translate('Push left');

		$this->resuser = $icalendars->usercal;
		$this->respub = $icalendars->pubcal;
		$this->resres = $icalendars->rescal;

		if (!empty($icalendars->id_percal))
			{
			$this->personal = $icalendars->id_percal;
			$this->selected = in_array($icalendars->id_percal, $this->selectedCalendars) ? 'selected' : '';
			}
		}

	function getnextusrcal()
		{
		$out = list($this->id, $name) = each($this->resuser);
		if ($out)
			{
			$this->name = isset($name['name']) ? $name['name'] : '';
			$this->selected = in_array($this->id,$this->selectedCalendars) ? 'selected' : '';
			}
		return $out;
		}

	function getnextpubcal()
		{
		$out = list($this->id, $cal) = each($this->respub);
		if ($out)
			{
			$this->name = $cal['name'];
			$this->selected = in_array($this->id,$this->selectedCalendars) ? 'selected' : '';
			}
		return $out;
		}

	function getnextrescal()
		{
		$out = list($this->id, $cal) = each($this->resres);
		if ($out)
			{
			$this->name = $cal['name'];
			$this->selected = in_array($this->id,$this->selectedCalendars) ? 'selected' : '';
			}
		return $out;
		}

	function printhtml()
		{
		return bab_printTemplate($this,"calendar.html", "calendarchoice");
		}
	}

$temp = new calendarchoice($formname);
return $temp->printhtml();
}


function record_calendarchoice()
{
global $babBody;

if (isset($_POST['selected_calendars']) && count($_POST['selected_calendars']) > 0 && $GLOBALS['BAB_SESS_LOGGED'])
	{
	$babBody->icalendars->user_calendarids = implode(',',$_POST['selected_calendars']);
	
	$db = &$GLOBALS['babDB'];
	list($n) = $db->db_fetch_array($db->db_query("SELECT COUNT(*) FROM ".BAB_CAL_USER_OPTIONS_TBL." WHERE id_user='".$GLOBALS['BAB_SESS_USERID']."'"));
	if ($n > 0)
		{
		$db->db_query("UPDATE ".BAB_CAL_USER_OPTIONS_TBL." SET  user_calendarids='".$babBody->icalendars->user_calendarids."' WHERE id_user='".$GLOBALS['BAB_SESS_USERID']."'");
		}
	else
		{
		$db->db_query("insert into ".BAB_CAL_USER_OPTIONS_TBL." ( id_user, startday, allday, start_time, end_time, usebgcolor, elapstime, defaultview, work_days, week_numbers, user_calendarids) values ('".$GLOBALS['BAB_SESS_USERID']."', '1', 'N', '08:00:00', '18:00:00', 'Y', '30', '0', '1,2,3,4,5', 'N', '".$babBody->icalendars->user_calendarids."')");
		}
	}

}
?>
