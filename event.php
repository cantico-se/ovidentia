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
include $babInstallPath."utilit/calincl.php";

function bab_getCalendarEventTitle($evtid)
{
	$db = $GLOBALS['babDB'];
	$query = "select title from ".BAB_CAL_EVENTS_TBL." where id='$evtid'";
	$res = $db->db_query($query);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		return $arr['title'];
		}
	else
		{
		return "";
		}
}


function getAvailableUsersCalendars()
{
	global $BAB_SESS_USERID,$BAB_SESS_USER;
	$tab = array();
	$rr = array();
	$rr['name'] = $BAB_SESS_USER;
	$rr['idcal'] = bab_getCalendarId($BAB_SESS_USERID, 1);
	array_push($tab, $rr);

	$db = $GLOBALS['babDB'];
	$req = "select * from ".BAB_CALACCESS_USERS_TBL." where id_user='".$BAB_SESS_USERID."'";
	$res = $db->db_query($req);
	while($row = $db->db_fetch_array($res))
	{
		if($row['bwrite'] == "1" || $row['bwrite'] == "2")
		{
			$rr['name'] = bab_getCalendarOwnerName($row['id_cal'], 1);
			$rr['idcal'] = $row['id_cal'];
			array_push($tab, $rr);
		}
	}
	return $tab;
}	


function getAvailableGroupsCalendars()
{
	global $BAB_SESS_USERID,$BAB_SESS_USER;
	$tab = array();
	$rr = array();

	$db = $GLOBALS['babDB'];
	$req = "select ".BAB_GROUPS_TBL.".name, ".BAB_GROUPS_TBL.".id from ".BAB_GROUPS_TBL." join ".BAB_USERS_GROUPS_TBL." where id_object='".$BAB_SESS_USERID."' and ".BAB_GROUPS_TBL.".id=".BAB_USERS_GROUPS_TBL.".id_group";
	$resgroups = $db->db_query($req);
	while($arr = $db->db_fetch_array($resgroups))
	{
		if(bab_isUserGroupManager($arr['id']))
		{
			$res = $db->db_query("select * from ".BAB_CALENDAR_TBL." where owner='".$arr['id']."' and type='2' and actif='Y'");
			while( $arr2 = $db->db_fetch_array($res))
			{
				$rr['name'] = $arr['name'];
				$rr['idcal'] = $arr2['id'];
				array_push($tab, $rr);
			}
		}

	}

	return $tab;
}


function getAvailableResourcesCalendars()
{
	global $BAB_SESS_USERID,$BAB_SESS_USER;
	$tab = array();
	$rr = array();

	$db = $GLOBALS['babDB'];

	$req = "select ".BAB_GROUPS_TBL.".id from ".BAB_GROUPS_TBL." join ".BAB_USERS_GROUPS_TBL." where id_object='".$BAB_SESS_USERID."' and ".BAB_GROUPS_TBL.".id=".BAB_USERS_GROUPS_TBL.".id_group";
	$resgroups = $db->db_query($req);

	$req = "select * from ".BAB_RESOURCESCAL_TBL." where id_group='1'";
	while($arr = $db->db_fetch_array($resgroups))
	{
		$req .= " or id_group='".$arr['id']."'"; 
	}
	$res = $db->db_query($req);
	while($arr = $db->db_fetch_array($res))
	{
		$rr['name'] = $arr['name'];
		$rr['idcal'] = bab_getCalendarId($arr['id'], 3);
		array_push($tab, $rr);
	}

	return $tab;
}

function newEvent($calendarid, $day, $month, $year, $view, $title, $description, $st)
	{
	global $babBody;
	class temp
		{
		var $datebegin;
		var $dateend;
		var $vactype;
		var $addvac;

		var $daybegin;
		var $daybeginid;
		var $monthbegin;
		var $monthbeginid;

		var $yearbegin;

		var $db;
		var $res;
		var $count;

		var $arrresname = array();
		var $arrresid = array();
		var $bcategory;
		var $daytypechecked;

		var $curday;
		var $curmonth;
		var $curyear;
		var $curview;
		var $msie;

		var $titleval;
		var $descriptionval;

		function temp($calendarid, $day, $month, $year, $view, $title, $description, $st)
			{
			global $BAB_SESS_USERID, $babBody;
			if( empty($st))
				$this->st = "08:00";
			else
				$this->st = $st;
			$this->curday = $day;
			$this->curmonth = $month;
			$this->curyear = $year;
			$this->curview = $view;
			$this->calid = $calendarid;
			$this->mcals = explode(",", $this->calid);
			$this->caltype = bab_getCalendarType($this->mcals[0]);
			$this->titleval = isset($title)? $title: "";
			$this->descriptionval = isset($description)? $description: "";
			$babBody->title = bab_translate("New calendar event");
			$this->ymin = 2;
			$this->ymax = 5;
			$this->yearbegin = $year;
			$this->yearmin = $year - $this->ymin;
			$this->daybegin = $day;
			$this->monthbegin = $month;
			$this->datebegin = $GLOBALS['babUrlScript']."?tg=month&callback=dateBegin&ymin=".$this->ymin."&ymax=".$this->ymax."&month=".$month."&year=".$year;
			$this->datebegintxt = bab_translate("Begin date");
			$this->dateend = $GLOBALS['babUrlScript']."?tg=month&callback=dateEnd&ymin=".$this->ymin."&ymax=".$this->ymax."&month=".$month."&year=".$year;
			$this->dateendtxt = bab_translate("Until date");
			$this->private = bab_translate("Private");
			$this->type = bab_translate("Type");

			$this->daytype = bab_translate("All day");
			$this->addvac = bab_translate("Add Event");
			$this->starttime = bab_translate("starttime");
			$this->endtime = bab_translate("endtime");
			$this->daystext = bab_translate("Days");
			$this->or = bab_translate("Or");
			$this->repeat = bab_translate("Repeat");
			$this->everyday = bab_translate("Everyday");
			$this->title = bab_translate("Title");
			$this->description = bab_translate("Description");
			$this->category = bab_translate("Category");
			$this->usrcalendarstxt = bab_translate("Users calendars");
			$this->grpcalendarstxt = bab_translate("Groups calendars");
			$this->rescalendarstxt = bab_translate("Resources calendars");
			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_CALOPTIONS_TBL." where id_user='".$BAB_SESS_USERID."'";
			$res = $this->db->db_query($req);
			$this->daytypechecked = "";
			$this->elapstime = 30;
			$this->ampm = false;
			if( $res && $this->db->db_num_rows($res))
				{
				$arr = $this->db->db_fetch_array($res);
				if( $arr['allday'] == "Y")
					$this->daytypechecked = "checked";
				if( isset($arr['elapstime'] ) && $arr['elapstime'] != "" )
					$this->elapstime = $arr['elapstime'];
				if( $arr['ampm'] == "Y")
					$this->ampm = true;
				}

			$this->catarr = array();
			for($i=0; $i < count($this->mcals); $i++)
				{
				$arrcat = array();
				switch(bab_getCalendarType($this->mcals[$i]))
					{
					case 1:
						$req = "select * from ".BAB_USERS_GROUPS_TBL." join ".BAB_GROUPS_TBL." where id_object='".$BAB_SESS_USERID."' and ".BAB_GROUPS_TBL.".id=".BAB_USERS_GROUPS_TBL.".id_group";
						$res = $this->db->db_query($req);
						$req = "select * from ".BAB_CATEGORIESCAL_TBL." where id_group='1'";
						while( $arr = $this->db->db_fetch_array($res) )
						{
							$req .= " or id_group='".$arr['id_group']."'"; 
						}
						$res = $this->db->db_query($req);
						while( $arr = $this->db->db_fetch_array($res) )
						{
							$arrcat[] = $arr['id'];
						}
						break;
					case 2:
						$req = "select * from ".BAB_CALENDAR_TBL." where id='".$this->mcals[$i]."'";
						$res = $this->db->db_query($req);
						$arr = $this->db->db_fetch_array($res);
						$req = "select * from ".BAB_CATEGORIESCAL_TBL." where id_group='1' or id_group='".$arr['owner']."'";
						$res = $this->db->db_query($req);
						while( $arr = $this->db->db_fetch_array($res) )
						{
							$arrcat[] = $arr['id'];
						}
						break;
					case 3:
						$req = "select * from ".BAB_CATEGORIESCAL_TBL." where id_group='1'";
						$res = $this->db->db_query($req);
						while( $arr = $this->db->db_fetch_array($res) )
						{
							$arrcat[] = $arr['id'];
						}
						break;
					}
				if( $i > 0)
					$this->catarr = array_intersect($this->catarr, $arrcat);
				else
					$this->catarr = array_merge($this->catarr,$arrcat);
				}

			if( count($this->catarr) > 0)
				$this->bcategory = 1;
			else
				$this->bcategory = 0;
			
			if(( strtolower(bab_browserAgent()) == "msie") and (bab_browserOS() == "windows"))
				$this->msie = 1;
			else
				$this->msie = 0;

			$this->usrcalendars = getAvailableUsersCalendars();
			$this->grpcalendars = getAvailableGroupsCalendars();
			$this->rescalendars = getAvailableResourcesCalendars();
			$this->maxcals = max(count($this->usrcalendars), count($this->grpcalendars), count($this->rescalendars));
			}

		function getnextdays()
			{
			global $babDays;

			static $i = 0;
			if( $i < 7 )
				{
				$this->days = $i;
				$this->daysname = $babDays[$i];
				if( 1 == $i)
					{
					$this->selected = "selected";
					}
				else
					$this->selected = "";
				
				$i++;
				return true;
				}
			else
				{
				$i = 0;
				return false;
				}

			}


		function getnextday()
			{
			static $i = 1;
			if( $i <= date("t"))
				{
				$this->dayid = $i;
				if( $this->daybegin == $i)
					{
					$this->selected = "selected";
					}
				else
					$this->selected = "";
				
				$i++;
				return true;
				}
			else
				{
				$i = 1;
				return false;
				}

			}

		function getnextmonth()
			{
			global $babMonths;
			static $i = 1;

			if( $i < 13)
				{
				$this->monthid = $i;
				$this->monthname = $babMonths[$i];
				if( $this->monthbegin == $i)
					{
					$this->selected = "selected";
					}
				else
					$this->selected = "";

				$i++;
				return true;
				}
			else
				{
				$i = 1;
				return false;
				}

			}
		function getnextyear()
			{
			static $i = 0;
			if( $i < $this->ymin + $this->ymax + 1)
				{
				$this->yearidval = $this->yearmin + $i;
				$this->yearid = $this->yearidval;
				if( $this->yearbegin == $this->yearidval)
					{
					$this->selected = "selected";
					}
				else
					$this->selected = "";
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

			if( $i < 1440/$this->elapstime)
				{
				$this->timeval = sprintf("%02d:%02d", ($i*$this->elapstime)/60, ($i*$this->elapstime)%60);
				if( $this->ampm )
					$this->time = bab_toAmPm($this->timeval);
				else
					$this->time = $this->timeval;
				if( $this->st == $this->timeval)
					$this->selected = "selected";
				else
					$this->selected = "";
				$i++;
				return true;
				}
			else
				{
				$i = 0;
				return false;
				}

			}

		function getnextcat()
			{
			static $i = 0;
			if( $i < count($this->catarr))
				{
				$arr = each($this->catarr);
				$arr = $this->db->db_fetch_array($this->db->db_query("select * from ".BAB_CATEGORIESCAL_TBL." where id='".$arr['value']."'"));
				$this->catid = $arr['id'];
				$this->catname = $arr['name'];
				$i++;
				return true;
				}
			else
				{
				$i = 0;
				return false;
				}

			}

		function getnextrow()
			{
			static $i = 0;
			if( $i < $this->maxcals)
				{
				$this->busrcal = false;
				$this->brescal = false;
				$this->bgrpcal = false;
				if( $i < count($this->usrcalendars))
					{
					$this->usrcalname = $this->usrcalendars[$i]['name'];
					$this->usrcalid = $this->usrcalendars[$i]['idcal'];
					if( count($this->mcals) > 0 && in_array($this->usrcalid, $this->mcals))
						$this->usrsel = "checked";
					else
						$this->usrsel = "";
					$this->busrcal = true;
					}
				if( $i < count($this->grpcalendars))
					{
					$this->grpcalname = $this->grpcalendars[$i]['name'];
					$this->grpcalid = $this->grpcalendars[$i]['idcal'];
					if( count($this->mcals) > 0 && in_array($this->grpcalid, $this->mcals))
						$this->grpsel = "checked";
					else
						$this->grpsel = "";
					$this->bgrpcal = true;
					}
				if( $i < count($this->rescalendars))
					{
					$this->rescalname = $this->rescalendars[$i]['name'];
					$this->rescalid = $this->rescalendars[$i]['idcal'];
					if( count($this->mcals) > 0 && in_array($this->rescalid, $this->mcals))
						$this->ressel = "checked";
					else
						$this->ressel = "";
					$this->brescal = true;
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

	$temp = new temp($calendarid, $day, $month, $year, $view, $title, $description, $st);
	$babBody->babecho(	bab_printTemplate($temp,"event.html", "scripts"));
	$babBody->babecho(	bab_printTemplate($temp,"event.html", "newevent"));
	}


function modifyEvent($calendarid, $evtid, $day, $month, $year, $view, $bmodif)
	{
	global $babBody;
	class temp
		{
		var $datebegin;
		var $dateend;
		var $vactype;
		var $addvac;

		var $daybegin;
		var $daybeginid;
		var $monthbegin;
		var $monthbeginid;

		var $yearbegin;

		var $db;
		var $res;
		var $count;

		var $arrresname = array();
		var $arrresid = array();
		var $evtid;
		var $bcategory;

		var $curday;
		var $curmonth;
		var $curyear;
		var $curview;
		var $descurl;
		var $delete;
		var $brecevt;
		var $all;
		var $thisone;
		var $updaterec;

		function temp($calendarid, $evtid, $day, $month, $year, $view, $bmodif)
			{
			global $BAB_SESS_USERID, $babBody;

			$this->delete = bab_translate("Delete");
			$this->db = $GLOBALS['babDB'];
			$this->calid = $calendarid;
			$this->evtid = $evtid;
			$this->bmodif = $bmodif;
			$this->caltype = bab_getCalendarType($calendarid);
			$babBody->title = bab_translate("Calendar"). "  ". bab_getCalendarOwnerName($this->calid, $this->caltype);

			$res = $this->db->db_query("select * from ".BAB_CAL_EVENTS_TBL." where id='$evtid'");
			$this->evtarr = $this->db->db_fetch_array($res);
			if( $this->evtarr['hash'] != "" && $this->evtarr['hash'][0] == 'R')
				{
				$this->brecevt = true;
				$this->updaterec = bab_translate("This is recurring event. Do you want to update this occurence or series?");
				$this->updaterec .= " ".bab_translate("If you choose to update all occurrences, dates are not be updated !");
				$this->all = bab_translate("All");
				$this->thisone = bab_translate("This occurence");
				}
			else
				$this->brecevt = false;
			$this->evtarr['description'] = bab_replace($this->evtarr['description']);
			$this->ymin = 2;
			$this->ymax = 5;
			$this->yearbegin = substr($this->evtarr['start_date'], 0,4 );
			$this->yearmin = $this->yearbegin - $this->ymin;
			$this->daybegin = substr($this->evtarr['start_date'], 8, 2);
			$this->monthbegin = substr($this->evtarr['start_date'], 5, 2);
			$this->nbdaysbegin = date("t", mktime(0,0,0, $this->monthbegin, $this->daybegin,$this->yearbegin));
			$this->yearend = substr($this->evtarr['end_date'], 0,4 );
			$this->dayend = substr($this->evtarr['end_date'], 8, 2);
			$this->monthend = substr($this->evtarr['end_date'], 5, 2);
			$this->nbdaysend = date("t", mktime(0,0,0, $this->monthend, $this->dayend,$this->yearend));
			$this->timebegin = substr($this->evtarr['start_time'], 0, 5);
			$this->timeend = substr($this->evtarr['end_time'], 0, 5);
			$this->datebegin = $GLOBALS['babUrlScript']."?tg=month&callback=dateBegin&ymin=".$this->ymin."&ymax=".$this->ymax."&month=".$this->monthbegin."&year=".$this->yearbegin;
			$this->datebegintxt = bab_translate("Begin date");
			$this->dateend = $GLOBALS['babUrlScript']."?tg=month&callback=dateEnd&ymin=".$this->ymin."&ymax=".$this->ymax."&month=".$this->monthend."&year=".$this->yearend;
			$this->dateendtxt = bab_translate("End date");
			$this->modify = bab_translate("Update Event");
			$this->starttime = bab_translate("starttime");
			$this->endtime = bab_translate("endtime");
			$this->title = bab_translate("Title");
			$this->description = bab_translate("Description");
			$this->category = bab_translate("Category");
			$this->descurl = $GLOBALS['babUrlScript']."?tg=event&idx=updesc&calid=".$calendarid."&evtid=".$evtid;
			$this->curday = $this->daybegin;
			$this->curmonth = $this->monthbegin;
			$this->curyear = $this->yearbegin;
			$this->curview = $view;

			switch( $this->caltype)
				{
				case 1: // user
					$this->bcategory = 1;
					$req = "select * from ".BAB_USERS_GROUPS_TBL." join ".BAB_GROUPS_TBL." where id_object=$BAB_SESS_USERID and ".BAB_GROUPS_TBL.".id=".BAB_USERS_GROUPS_TBL.".id_group";
					$this->resgroups = $this->db->db_query($req);
					if( $this->resgroups )
						{
						$this->countgroups = $this->db->db_num_rows($this->resgroups); 
						}

					$req2 = "select * from ".BAB_CATEGORIESCAL_TBL." where id_group='1'";
					if( $this->countgroups > 0)
						{
						for( $i = 0; $i < $this->countgroups; $i++)
							{
							$arr = $this->db->db_fetch_array($this->resgroups);
							$req2 .= " or id_group='".$arr['id']."'"; 
							}
						$this->db->db_data_seek($this->resgroups, 0);
						}
					$this->rescat = $this->db->db_query($req2);
					$this->countcat = $this->db->db_num_rows($this->rescat); 
					break;
				case 2: // group
					$this->bcategory = 1;
					$req = "select * from ".BAB_CALENDAR_TBL." where id='".$calendarid."'";
					$res = $this->db->db_query($req);
					$arr = $this->db->db_fetch_array($res);
					$req = "select * from ".BAB_CATEGORIESCAL_TBL." where id_group='1' or id_group='".$arr['owner']."'";
					$this->rescat = $this->db->db_query($req);
					$this->countcat = $this->db->db_num_rows($this->rescat); 
					break;
				case 3: // resource
				default:
					$this->bcategory = 0;
					$this->countgroups = 0;
					$this->countcat = 0;
					break;
				}

			if( $this->caltype == 1 )
				{
				$req = "select * from ".BAB_USERS_GROUPS_TBL." join ".BAB_GROUPS_TBL." where id_object=$BAB_SESS_USERID and ".BAB_GROUPS_TBL.".id=".BAB_USERS_GROUPS_TBL.".id_group";
				$this->resgroups = $this->db->db_query($req);
				if( $this->resgroups )
					{
					$this->countgroups = $this->db->db_num_rows($this->resgroups); 
					}

				$req2 = "select * from ".BAB_CATEGORIESCAL_TBL." where id_group='1'";
				if( $this->countgroups > 0)
					{
					for( $i = 0; $i < $this->countgroups; $i++)
						{
						$arr = $this->db->db_fetch_array($this->resgroups);
						$req2 .= " or id_group='".$arr['id']."'"; 
						}
					$this->db->db_data_seek($this->resgroups, 0);
					}
				$this->rescat = $this->db->db_query($req2);
				$this->countcat = $this->db->db_num_rows($this->rescat); 
				}
			else
				$this->countgroups = 0;

			$res = $this->db->db_query("select * from ".BAB_CALOPTIONS_TBL." where id_user='".$BAB_SESS_USERID."'");
			$this->elapstime = 30;
			$this->ampm = false;
			if( $res && $this->db->db_num_rows($res))
				{
				$arr = $this->db->db_fetch_array($res);
				if( isset($arr['elapstime'] ) && $arr['elapstime'] != "" )
					$this->elapstime = $arr['elapstime'];
				if( $arr['ampm'] == "Y")
					$this->ampm = true;
				}
			}

		function getnextday()
			{
			static $i = 1;
			static $tr = 0;
			if( $tr == 0)
				$nbdays = $this->nbdaysbegin;
			else
				$nbdays = $this->nbdaysend;
			if( $i <= $nbdays)
				{
				$this->dayid = $i;
				if( $tr == 0 && $this->daybegin == $i)
					{
					$this->selected = "selected";
					}
				else if( $tr == 1 && $this->dayend == $i)
					{
					$this->selected = "selected";
					}
				else
					$this->selected = "";
				
				$i++;
				return true;
				}
			else
				{
				$i = 1;
				$tr = 1;
				return false;
				}

			}

		function getnextmonth()
			{
			global $babMonths;
			static $i = 1;
			static $tr = 0;

			if( $i < 13)
				{
				$this->monthid = $i;
				$this->monthname = $babMonths[$i];
				if( $tr == 0 && $this->monthbegin == $i)
					{
					$this->selected = "selected";
					}
				else if( $tr == 1 && $this->monthend == $i)
					{
					$this->selected = "selected";
					}
				else
					$this->selected = "";

				$i++;
				return true;
				}
			else
				{
				$tr = 1;
				$i = 1;
				return false;
				}

			}
		function getnextyear()
			{
			static $i = 0;
			static $tr = 0;
			if( $i < $this->ymin + $this->ymax + 1)
				{
				//$this->yearid = $i+1;
				$this->yearidval = $this->yearmin + $i;
				$this->yearid = $this->yearidval;
				if( $tr == 0 && $this->yearbegin == $this->yearidval)
					{
					$this->selected = "selected";
					}
				else if( $tr == 1 && $this->yearend == $this->yearidval)
					{
					$this->selected = "selected";
					}
				else
					$this->selected = "";
				$i++;
				return true;
				}
			else
				{
				$i = 0;
				$tr = 1;
				return false;
				}

			}

		function getnexttime()
			{

			static $i = 0;
			static $tr = 0;

			if( $i < 1440/$this->elapstime)
				{
				$this->timeval = sprintf("%02d:%02d", ($i*$this->elapstime)/60, ($i*$this->elapstime)%60);
				if( $this->ampm )
					$this->time = bab_toAmPm($this->timeval);
				else
					$this->time = $this->timeval;
				if( $tr == 0 &&  $this->timeval == $this->timebegin)
					$this->selected = "selected";
				else if( $tr == 1 &&  $this->timeval == $this->timeend)
					$this->selected = "selected";
				else
					$this->selected = "";
				$i++;
				return true;
				}
			else
				{
				$i = 0;
				$tr = 1;
				return false;
				}

			}

		function getnextcat()
			{
			static $i = 0;
			if( $i < $this->countcat)
				{
				$arr = $this->db->db_fetch_array($this->rescat);
				$this->catid = $arr['id'];
				$this->catname = $arr['name'];
				if( $this->evtarr['id_cat'] == $arr['id'])
					$this->selected = "selected";
				else
					$this->selected = "";

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

	$temp = new temp($calendarid, $evtid, $day, $month, $year, $view, $bmodif);
	$babBody->babecho(	bab_printTemplate($temp,"event.html", "scripts"));
	$babBody->babecho(	bab_printTemplate($temp,"event.html", "modifyevent"));
	}

function deleteEvent($calid, $evtid, $day, $month, $year, $view, $bupdrec)
	{
	global $babBody;
	
	class temp
		{
		var $warning;
		var $message;
		var $title;
		var $urlyes;
		var $urlno;
		var $yes;
		var $no;
		var $topics;
		var $article;

		function temp($calid, $evtid, $day, $month, $year, $view, $bupdrec)
			{
			if( $bupdrec == "1" )
				{
				$this->message = bab_translate("This is a reccuring event.Are you sure you want to delete this event and all occurrences");
				$this->warning = bab_translate("WARNING: This operation will delete all occurrences permanently"). "!";
				}
			else
				{
				$this->message = bab_translate("Are you sure you want to delete this event");
				$this->warning = bab_translate("WARNING: This operation will delete event permanently"). "!";
				}
			$this->title = bab_getCalendarEventTitle($evtid);
			$this->urlyes = $GLOBALS['babUrlScript']."?tg=event&idx=viewm&day=".$day."&month=".$month."&year=".$year. "&calid=".$calid."&evtid=".$evtid."&action=Yes&view=".$view."&bupdrec=".$bupdrec;
			$this->yes = bab_translate("Yes");
			$this->urlno = $GLOBALS['babUrlScript']."?tg=event&idx=modify&day=".$day."&month=".$month."&year=".$year. "&calid=".$calid."&evtid=".$evtid."&view=".$view;
			$this->no = bab_translate("No");
			}
		}

	$temp = new temp($calid, $evtid, $day, $month, $year, $view, $bupdrec);
	$babBody->babecho(	bab_printTemplate($temp,"warning.html", "warningyesno"));
	}

function viewEvent($calid, $evtid)
	{
	global $babBody;
	
	class temp
		{
		var $title;
		var $titlename;
		var $startdatename;
		var $startdate;
		var $enddatename;
		var $enddate;
		var $descriptionname;
		var $description;

		function temp($calid, $evtid)
			{
			$this->titlename = bab_translate("Title");
			$this->startdatename = bab_translate("Begin date");
			$this->enddatename = bab_translate("End date");
			$this->descriptionname = bab_translate("Description");
			$db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_CAL_EVENTS_TBL." where id='".$evtid."'";
			$res = $db->db_query($req);
			$arr = $db->db_fetch_array($res);
			$this->title = $arr['title'];
			$this->description = bab_replace($arr['description']);
			$this->startdate = bab_strftime(bab_mktime($arr['start_date']), false) . " " . substr($arr['start_time'], 0 ,5);
			$this->enddate = bab_strftime(bab_mktime($arr['end_date']), false) . " " . substr($arr['end_time'], 0 ,5);
			}
		}

	$temp = new temp($calid, $evtid);
	$babBody->babecho(	bab_printTemplate($temp,"event.html", "viewevent"));
	}

function editDescription($calid, $evtid)
	{
	global $babBody;

	class temp
		{
		var $evtdesc;
		var $modify;

		var $db;
		var $arr = array();
		var $res;
		var $msie;
		var $brecevt;
		var $all;
		var $thisone;
		var $updaterec;

		function temp($calid, $evtid)
			{
			$this->evtdesc = bab_translate("Description");
			$this->modify = bab_translate("Update");
			$this->babCss = bab_printTemplate($this,"config.html", "babCss");
			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_CAL_EVENTS_TBL." where id='".$evtid."'";
			$this->res = $this->db->db_query($req);
			$this->arr = $this->db->db_fetch_array($this->res);
			if(( strtolower(bab_browserAgent()) == "msie") and (bab_browserOS() == "windows"))
				$this->msie = 1;
			else
				$this->msie = 0;	
			if( $this->arr['hash'] != "" && $this->arr['hash'][0] == 'R')
				{
				$this->brecevt = true;
				$this->updaterec = bab_translate("This is recurring event. Do you want to update this ocuurence or series?");
				$this->all = bab_translate("All");
				$this->thisone = bab_translate("This occurence");
				}
			else
				$this->brecevt = false;
			}
		}

	$temp = new temp($calid, $evtid);
	echo bab_printTemplate($temp,"event.html", "descmodify");
	}

function eventUnload()
	{
	class temp
		{
		var $babCss;
		var $message;
		var $close;
		var $url;
		var $bliste;

		function temp()
			{
			$this->babCss = bab_printTemplate($this,"config.html", "babCss");
			$this->message = bab_translate("Your event has been updated");
			}
		}

	$temp = new temp();
	echo bab_printTemplate($temp,"event.html", "eventunload");
	}

function insertEvent($tabcals, $title, $description, $startdate, $starttime, $enddate, $endtime, $catid, $md5)
	{
	$db = $GLOBALS['babDB'];
	for( $k=0; $k < count($tabcals); $k++)
		{
		$arr = $db->db_fetch_array($db->db_query("select * from ".BAB_CALENDAR_TBL." where id='".$tabcals[$k]."'"));
		$rr = $db->db_fetch_array($db->db_query("select * from ".BAB_CATEGORIESCAL_TBL." where id='".$catid."'"));
		switch ($arr['type'])
			{
			case 1:
				if( $arr['owner'] == $GLOBALS['BAB_SESS_USERID'])
					$creator = 0;
				else
					$creator = $GLOBALS['BAB_SESS_USERID'];
				if( $rr['id_group'] != 1 )
					{
					$res = $db->db_query("select * from ".BAB_USERS_GROUPS_TBL." where id_object='".$arr['owner']."' and id_group='".$rr['id_group']."'");
					if( !$res || $db->db_num_rows($res) != 1)
						$catid = 0;
					}

				break;
			case 2:
				$creator = 0;
				if( $rr['id_group'] != 1 )
					{
					$res = $db->db_query("select * from ".BAB_CATEGORIESCAL_TBL." where id_group='".$arr['owner']."' and id='".$catid."'");
					if( !$res || $db->db_num_rows($res) != 1)
						$catid = 0;
					}
				break;
			case 3:
			default:
				$creator = $GLOBALS['BAB_SESS_USERID'];
				if( $rr['id_group'] != 1 )
					{
					$catid = 0;
					}
				break;
			}
		$req = "insert into ".BAB_CAL_EVENTS_TBL." ( id_cal, title, description, start_date, start_time, end_date, end_time, id_cat, id_creator, hash) values ";
		$req .= "('".$tabcals[$k]."', '".$title."', '".$description."', '".$startdate."', '".$starttime."', '".$enddate."', '".$endtime."', '".$catid."', '".$creator."', '".$md5."')";
		$db->db_query($req);
		}
	}

function addEvent($calid, $daybegin, $monthbegin, $yearbegin, $daytype, $timebegin, $timeend, $repeat, $days, $dayend, $monthend, $yearend, $title, $description, $category, $usrcals, $grpcals, $rescals)
{
	global $babBody;
	
	if( empty($title))
		{
		$babBody->msgerror = bab_translate("You must provide a title")." !!";
		return false;
		}

	if( count($usrcals) == 0 && count($grpcals) == 0 && count($rescals) == 0 )
		{
		$babBody->msgerror = bab_translate("You must select at least one calendar type")." !!";
		return false;
		}

	if( !bab_isMagicQuotesGpcOn())
		{
		$description = addslashes($description);
		$title = addslashes($title);
		}
		
	if( empty($category))
		$catid = 0;
	else
		$catid = $category;

	$tabcals = array_merge($usrcals, $grpcals, $rescals);
	if( $repeat == "y")
	{
		$begin = mktime( 0,0,0,$monthbegin, $daybegin, $yearbegin );
		$end = mktime( 0,0,0,$monthend, $dayend, $yearend );

		if( $begin > $end || ( $daytype != "y" && $timebegin > $timeend))
			{
			$babBody->msgerror = bab_translate("End date must be older")." !!";
			return false;
			}

		for( $i = 0; $i < 7; $i++)
			{
			$tab[$i] = 0;
			}

		if( count($days) > 0 )
			{
			for( $i = 0; $i < count($days); $i++)
				{
				$tab[$days[$i]] = 1;
				}
/*
			for( $i = 6; $i >= 0; $i--)
				{
				if($i > 0 && $tab[$i] != 0 && $tab[$i-1] != 0)
					{
					$tab[$i-1] = $tab[$i-1] + $tab[$i];
					$tab[$i] = 0;
					}
				}
*/
			$md5 = "R_".md5(uniqid(rand(),1));
			for( $i=0; $i < 7; $i++)
				{
				if( $tab[$i] != 0 )
					{
					$delta = $i - Date("w", $begin);
					if( $delta < 0)
						$delta = 7 - Abs($delta);


					$nextday = $daybegin + $delta;
					$nextmont = $monthbegin;
					$nextyear = $yearbegin;
					while( $end > mktime( 0,0,0, $nextmont, $nextday+$tab[$i]-1, $nextyear ))
						{
						if( $daytype == "y")
							{
							$mktime = mktime( 0,0,0, $nextmont, $nextday, $nextyear );
							$startdate = sprintf("%04d-%02d-%02d", Date("Y", $mktime), Date("n", $mktime), Date("j", $mktime));
							$starttime = "00:00:00";
							$mktime = mktime( 0,0,0, $nextmont, $nextday+$tab[$i]-1, $nextyear );
							$enddate = sprintf("%04d-%02d-%02d", Date("Y", $mktime), Date("n", $mktime), Date("j", $mktime));
							$endtime = "23:59:59";
							}
						else
							{
							$mktime = mktime( 0,0,0, $nextmont, $nextday, $nextyear );
							$startdate = sprintf("%04d-%02d-%02d", Date("Y", $mktime), Date("n", $mktime), Date("j", $mktime));
							$starttime = sprintf("%s:00", $timebegin);
							$mktime = mktime( 0,0,0, $nextmont, $nextday+$tab[$i]-1, $nextyear );
							$enddate = sprintf("%04d-%02d-%02d", Date("Y", $mktime), Date("n", $mktime), Date("j", $mktime));
							$endtime = sprintf("%s:00", $timeend);
							}
						insertEvent($tabcals, $title, $description, $startdate, $starttime, $enddate, $endtime, $catid, $md5);
						$nextday += 7;
						}
					}
				}

			}
		else
			{
			if( $daytype == "y")
				{
				$startdate = sprintf("%04d-%02d-%02d", $yearbegin, $monthbegin, $daybegin);
				$starttime = "00:00:00";
				$enddate = sprintf("%04d-%02d-%02d", $yearend, $monthend, $dayend);
				$endtime = "23:59:59";
				}
			else
				{
				$startdate = sprintf("%04d-%02d-%02d", $yearbegin, $monthbegin, $daybegin);
				$starttime = sprintf("%s:00", $timebegin);
				$enddate = sprintf("%04d-%02d-%02d", $yearend, $monthend, $dayend);
				$endtime = sprintf("%s:00", $timeend);
				}
			insertEvent($tabcals, $title, $description, $startdate, $starttime, $enddate, $endtime, $catid, "");
			}

	}
	else
	{
	$begin = mktime( 0,0,0,$monthbegin, $daybegin, $yearbegin );
	$end = mktime( 0,0,0,$monthend, $dayend, $yearend );

	if( $begin > $end || ( $daytype != "y" && $begin == $end && $timebegin > $timeend))
		{
		$babBody->msgerror = bab_translate("End date must be older")." !!";
		return false;
		}

	if( $daytype == "y")
		{
		$startdate = sprintf("%04d-%02d-%02d", $yearbegin, $monthbegin, $daybegin);
		$starttime = "00:00:00";
		$enddate = sprintf("%04d-%02d-%02d", $yearend, $monthend, $dayend);
		$endtime = "23:59:59";
		}
	else
		{
		$startdate = sprintf("%04d-%02d-%02d", $yearbegin, $monthbegin, $daybegin);
		$starttime = sprintf("%s:00", $timebegin);
		$enddate = sprintf("%04d-%02d-%02d", $yearend, $monthend, $dayend);
		$endtime = sprintf("%s:00", $timeend);
		}
	insertEvent($tabcals, $title, $description, $startdate, $starttime, $enddate, $endtime, $catid, "");
	}
	return true;	
}

function updateEvent($calid, $daybegin, $monthbegin, $yearbegin, $evtid, $timebegin, $timeend, $dayend, $monthend, $yearend, $title, $description, $category, $bupdrec)
{
	global $babBody;
	
	if( empty($title))
		{
		$babBody->msgerror = bab_translate("You must provide a title")." !!";
		return;
		}

	if( !bab_isMagicQuotesGpcOn())
		{
		$description = addslashes($description);
		$title = addslashes($title);
		}
		
	$db = $GLOBALS['babDB'];

	if( empty($category))
		$catid = 0;
	else
		$catid = $category;

	if( $bupdrec == "1" )
	{
		$res = $db->db_query("select hash from ".BAB_CAL_EVENTS_TBL." where id='".$evtid."'");
		$arr = $db->db_fetch_array($res);
		$req = "update ".BAB_CAL_EVENTS_TBL." set title='".$title."', id_cat='".$catid."' where id_cal='".$calid."' and hash='".$arr['hash']."'";
		$db->db_query($req);
	}
	else
	{
		$begin = mktime( 0,0,0,$monthbegin, $daybegin, $yearbegin );
		$end = mktime( 0,0,0,$monthend, $dayend, $yearend );

		if( $begin > $end )
			{
			$babBody->msgerror = bab_translate("End date must be older")." !!";
			return;
			}

		$startdate = sprintf("%04d-%02d-%02d", $yearbegin, $monthbegin, $daybegin);
		$starttime = sprintf("%s:00", $timebegin);
		$enddate = sprintf("%04d-%02d-%02d", $yearend, $monthend, $dayend);
		$endtime = sprintf("%s:00", $timeend);

		$req = "update ".BAB_CAL_EVENTS_TBL." set title='".$title."', start_date='".$startdate."', start_time='".$starttime."', end_date='".$enddate."', end_time='".$endtime."', id_cat='".$catid."' where id='".$evtid."'";
		$db->db_query($req);
	}

}

function confirmDeleteEvent($calid, $evtid, $bupdrec)
{
	$db = $GLOBALS['babDB'];
	if( $bupdrec == "1" )
		{
		$res = $db->db_query("select hash from ".BAB_CAL_EVENTS_TBL." where id='".$evtid."'");
		$arr = $db->db_fetch_array($res);
		$db->db_query("delete from ".BAB_CAL_EVENTS_TBL." where id_cal='".$calid."' and hash='".$arr['hash']."'");
		}
	else
		$db->db_query("delete from ".BAB_CAL_EVENTS_TBL." where id='$evtid'");

}

function updateDescription($calid, $evtid, $content, $bupdrec)
{
	$db = $GLOBALS['babDB'];
	
	if( !bab_isMagicQuotesGpcOn())
		{
		$content = addslashes($content);
		}

	if( $bupdrec == "1" )
		{
		$res = $db->db_query("select hash from ".BAB_CAL_EVENTS_TBL." where id='".$evtid."'");
		$arr = $db->db_fetch_array($res);
		$db->db_query("update ".BAB_CAL_EVENTS_TBL." set description='".$content."' where id_cal='".$calid."' and hash='".$arr['hash']."'");
		}
	else
		$db->db_query("update ".BAB_CAL_EVENTS_TBL." set description='".$content."' where id='".$evtid."'");
}

function isUpdateEvent($calid, $evtid)
{
global $babDB;
$bmodif = 0;

list($hash) = $babDB->db_fetch_row($babDB->db_query("select hash from ".BAB_CAL_EVENTS_TBL." where id ='".$evtid."'"));
if( substr($hash, 0, 2) == "V_")
	return $bmodif;

$caltype = bab_getCalendarType($calid);
$owner = bab_getCalendarOwner($calid);
switch($caltype)
	{
	case 1:
		if( $owner == $GLOBALS['BAB_SESS_USERID'])
			$bmodif = 1;
		else
			{
			$res = $babDB->db_query("select bwrite from ".BAB_CALACCESS_USERS_TBL." where id_cal='".$calid."' and id_user='".$GLOBALS['BAB_SESS_USERID']."'");
			if( $res && $babDB->db_num_rows($res) > 0 )
				{
				$arr = $babDB->db_fetch_array($res);
				if( $arr['bwrite'] == 2 )
					$bmodif = 1;
				else
					{
					$arr = $babDB->db_fetch_array($babDB->db_query("select id_creator from ".BAB_CAL_EVENTS_TBL." where id='".$evtid."'"));
					if( $arr['id_creator'] == $GLOBALS['BAB_SESS_USERID'] )
						$bmodif = 1;
					}
				}
			}
		break;
	case 2:
		if( bab_isUserGroupManager($owner))
			$bmodif = 1;
		else
			$bmodif = 0;
		break;
	case 3:
		$bmodif = 1;
		break;
	default:
		$bmodif = 0;
		break;	
	}
return $bmodif;
}
/* main */
if( !isset($idx))
	$idx = "newevent";

if( !bab_isCalendarAccessValid($calid) )
	{
	$babBody->title = bab_translate("Access denied");
	$idx = "";
	}
else
	{

	if( isset($action) && $action == "Yes")
		{
		confirmDeleteEvent($calid, $evtid, $bupdrec);
		Header("Location: ". $GLOBALS['babUrlScript']."?tg=calendar&idx=".$view."&calid=".$calid."&day=".$curday."&month=".$curmonth."&year=".$curyear);
		}

	if( isset($update) && $update == "desc")
		{
		updateDescription($calid, $evtid, $content, $bupdrec);
		$idx = "unload";
		}

	if( isset($modifyevent) && $modifyevent == "modify")
		{
		if( isset($Submit))
			{
			updateEvent($calid, $daybegin, $monthbegin, $yearbegin, $evtid, $timebegin, $timeend, $dayend, $monthend, $yearend, $title, $evtdesc, $category, $bupdrec);
			Header("Location: ". $GLOBALS['babUrlScript']."?tg=calendar&idx=".$view."&calid=".$calid."&day=".$curday."&month=".$curmonth."&year=".$curyear);
			}
		else if( isset($evtdel))
			{
			$month = $curmonth;
			$day = $curday;
			$year = $curyear;
			$idx = "delete";
			}
		}

	if( isset($addevent) && $addevent == "add")
		{
		if( !isset($usrcals))
			$usrcals = array();
		if( !isset($grpcals))
			$grpcals = array();
		if( !isset($rescals))
			$rescals = array();
		if( !addEvent($calid, $daybegin, $monthbegin, $yearbegin, $daytype, $timebegin, $timeend, $repeat, $days, $dayend, $monthend, $yearend, $title, $evtdesc, $category, $usrcals, $grpcals, $rescals))
			{
			$day = $daybegin;
			$month = $monthbegin;
			$year = $yearbegin;
			$view = $view;
			$st = $timebegin;
			$idx = "newevent";
			$mcals = implode(",", array_merge($usrcals, $grpcals, $rescals));
			}
		else
			Header("Location: ". $GLOBALS['babUrlScript']."?tg=calendar&idx=".$view."&calid=".$calid."&day=".$curday."&month=".$curmonth."&year=".$curyear);
		}

	}

switch($idx)
	{
	case "unload":
		eventUnload();
		exit;
		break;

	case "updesc":
		editDescription($calid, $evtid);
		exit;
		break;

	case "delete":
		$babBody->title = bab_translate("Delete calendar event");
		deleteEvent($calid, $evtid, $curday, $curmonth, $curyear, $view, $bupdrec);
		if( bab_isUserGroupManager())
			{
			$babBody->addItemMenu("listcat", bab_translate("Categories"), $GLOBALS['babUrlScript']."?tg=confcals&idx=listcat&userid=$BAB_SESS_USERID");
			$babBody->addItemMenu("resources", bab_translate("Resources"), $GLOBALS['babUrlScript']."?tg=confcals&idx=listres&userid=$BAB_SESS_USERID");
			}
		$babBody->addItemMenu("calendar", bab_translate("Calendar"), $GLOBALS['babUrlScript']."?tg=calendar&idx=".$view."&day=".$day."&month=".$month."&year=".$year. "&calid=".$calid);
		$babBody->addItemMenu("delete", bab_translate("Delete"), $GLOBALS['babUrlScript']."?tg=event&idx=delete&day=".$day."&month=".$month."&year=".$year. "&calid=".$calid. "&evtid=".$evtid);
		break;
		break;

	case "modify":
		$bmodif = isUpdateEvent($calid, $evtid);
		if( $bmodif )
			modifyEvent($calid, $evtid, $day, $month, $year, $view, $bmodif);
		else
			viewEvent($calid, $evtid, $day, $month, $year, $view);

		if( bab_isUserGroupManager())
			{
			$babBody->addItemMenu("listcat", bab_translate("Categories"), $GLOBALS['babUrlScript']."?tg=confcals&idx=listcat&userid=$BAB_SESS_USERID");
			$babBody->addItemMenu("resources", bab_translate("Resources"), $GLOBALS['babUrlScript']."?tg=confcals&idx=listres&userid=$BAB_SESS_USERID");
			}
		$babBody->addItemMenu("calendar", bab_translate("Calendar"), $GLOBALS['babUrlScript']."?tg=calendar&idx=".$view."&day=".$day."&month=".$month."&year=".$year. "&calid=".$calid);
		break;

	case "newevent":
		newEvent($calid, $day, $month, $year, $view, $title, $evtdesc, $st);
		if( bab_isUserGroupManager())
			{
			$babBody->addItemMenu("listcat", bab_translate("Categories"), $GLOBALS['babUrlScript']."?tg=confcals&idx=listcat&userid=$BAB_SESS_USERID");
			$babBody->addItemMenu("resources", bab_translate("Resources"), $GLOBALS['babUrlScript']."?tg=confcals&idx=listres&userid=$BAB_SESS_USERID");
			}
		$babBody->addItemMenu("calendar", bab_translate("Calendar"), $GLOBALS['babUrlScript']."?tg=calendar&idx=".$view."&day=".$day."&month=".$month."&year=".$year. "&calid=".$calid);
		break;

	default:
		break;
	}
$babBody->setCurrentItemMenu($idx);

?>