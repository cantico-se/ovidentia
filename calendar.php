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
				while( $arr = $babDB->db_fetch_array($res))
					{
					if( bab_isCalendarAccessValid($arr['id_cal']))
						{
						$this->arrinfo[] = array('idcal' => $arr['id_cal'], 'status' => $arr['status']);
						if( $babBody->icalendars->id_percal == $arr['id_cal'] )
							{
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
				$arr = $babBody->icalendars->getCalendarInfo($this->arrinfo[$i]['idcal']);
				$this->fullname = $arr['name'];
				$this->bcreator = false;
				if( $GLOBALS['BAB_SESS_USERID'] ==  $this->idcreator )
					{
					$this->countstatus = 0;
					}
				if( $arr['idowner'] ==  $this->idcreator )
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
					$this->cattxt = bab_translate("Category");
					$this->begindate = bab_longDate(bab_mktime($arr['start_date']));
					$this->enddate = bab_longDate(bab_mktime($arr['end_date']));
					if( $arr['bprivate'] ==  'Y' && $GLOBALS['BAB_SESS_USERID']  != $iarr['idowner'])
						{
						$this->title= '';
						$this->description = '';
						}
					else
						{
						$this->title= $arr['title'];
						$this->description = bab_replace($arr['description']);
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

function categoriesList()
{
	global $babBodyPopup;
	class categoriesListCls
		{

		function categoriesListCls()
			{
			global $babBodyPopup, $babBody, $babDB;
			$this->res = $babDB->db_query("select * from ".BAB_CAL_CATEGORIES_TBL."");
			$this->count = $babDB->db_num_rows($this->res);
			}

		function getnextcat()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $babDB->db_fetch_array($this->res);
				if (trim($arr['description']) == '') 
					{
					$arr['description'] = $arr['name'];
					}
				$this->catdesc = $arr['description'];
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

function confirmWaitingEvent($evtid, $idcal)
{
	global $babBodyPopup;
	class confirmWaitingEventCls
		{
		var $arttxt;

		function confirmWaitingEventCls($evtid, $idcal)
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
					$this->updatetxt = bab_translate("Update");
					$this->confirmtxt = bab_translate("Confirm");
					$this->commenttxt = bab_translate("Raison");
					$this->begindatetxt = bab_translate("Begin date");
					$this->enddatetxt = bab_translate("End date");
					$this->titletxt = bab_translate("Title");
					$this->desctxt = bab_translate("Description");
					$this->cattxt = bab_translate("Category");
					$this->yes = bab_translate("Yes");
					$this->no = bab_translate("No");
					$this->begindate = bab_longDate(bab_mktime($arr['start_date']));
					$this->enddate = bab_longDate(bab_mktime($arr['end_date']));
					$this->title= $arr['title'];
					$this->description = bab_replace($arr['description']);
					$this->evtid = $evtid;
					$this->idcal = $idcal;
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

	$temp = new confirmWaitingEventCls($evtid, $idcal);
	$babBodyPopup->babecho(bab_printTemplate($temp, "calendar.html", "confirmevent"));
}


function confirmEvent($evtid, $idcal, $bconfirm, $comment, $bupdrec)
{
	global $babDB, $babBody;
	$arr = $babBody->icalendars->getCalendarInfo($idcal);
	if( $arr['type'] == BAB_CAL_USER_TYPE && $arr['idowner'] ==  $GLOBALS['BAB_SESS_USERID'] )
		{
		list($creator, $hash) = $babDB->db_fetch_row($babDB->db_query("select id_creator, hash from ".BAB_CAL_EVENTS_TBL." where id='".$evtid."'"));

		list($status) = $babDB->db_fetch_row($babDB->db_query("select status from ".BAB_CAL_EVENTS_OWNERS_TBL." where id_event='".$evtid."' and id_cal='".$idcal."'"));
		if( $status == BAB_CAL_STATUS_NONE )
			{
			if( $bconfirm == "Y" )
				{
				$bconfirm = BAB_CAL_STATUS_ACCEPTED;
				}
			else
				{
				$bconfirm = BAB_CAL_STATUS_DECLINED;
				}

			if( !empty($hash) &&  $hash[0] == 'R' && $bupdrec == 1)
				{
				$res = $babDB->db_query("select id from ".BAB_CAL_EVENTS_TBL." where hash='".$hash."'");
				while($arr = $babDB->db_fetch_array($res))
					{
					$arrevtids[] = $arr['id']; 	
					}
				}
			else
				{
				$arrevtids[] = $evtid; 	
				}

			$babDB->db_query("update ".BAB_CAL_EVENTS_OWNERS_TBL." set status='".$bconfirm."' where id_event IN (".implode(',', $arrevtids).") and id_cal='".$idcal."'");
			notifyEventApprobation($evtid, $bconfirm, $comment);
			}
		}
}

/* main */
if( isset($conf) && $conf == "event")
{
	if( !isset($bupdrec)) { $bupdrec = 2; }
	confirmEvent($evtid, $idcal, $bconfirm, $comment, $bupdrec);
}

switch($idx)
	{
	case "unload":
		include_once $babInstallPath."utilit/uiutil.php";
		$popupmessage = bab_translate("Your event has been updated");
		popupUnload($popupmessage, '');
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
	case "confvent":
		include_once $babInstallPath."utilit/uiutil.php";
		$babBodyPopup = new babBodyPopup();
		$babBodyPopup->title = bab_translate("Event approbation");
		confirmWaitingEvent($evtid, $idcal);
		printBabBodyPopup();
		exit;
		break;
	case "vevent":
		include_once $babInstallPath."utilit/uiutil.php";
		$babBodyPopup = new babBodyPopup();
		$babBodyPopup->title = bab_translate("Event Detail");
		displayEventDetail($evtid, $idcal);
		printBabBodyPopup();
		exit;
		break;
	case "attendees":
		include_once $babInstallPath."utilit/uiutil.php";
		$babBodyPopup = new babBodyPopup();
		$babBodyPopup->title = bab_translate("Attendees");
		displayAttendees($evtid, $idcal);
		printBabBodyPopup();
		exit;
		break;
	default:
		break;
	}
$babBody->setCurrentItemMenu($idx);
?>