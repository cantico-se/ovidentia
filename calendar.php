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
				$res = $babDB->db_query("select ceo.* from ".BAB_CAL_EVENTS_OWNERS_TBL." ceo where ceo.id_event='".$evtid."'");
				$this->arrinfo = array();
				while( $arr = $babDB->db_fetch_array($res))
					{
					if( bab_isCalendarAccessValid($arr['id_cal']))
						{
						$this->arrinfo[] = array('idcal' => $arr['id_cal'], 'status' => $arr['status']);
						}
					}
				$this->count = count($this->arrinfo);
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
				$this->fullname = $babBody->icalendars->getCalendarName($this->arrinfo[$i]['idcal']);
				$this->status = $this->statusdef[$this->arrinfo[$i]['status']];
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
					$this->begindatetxt = bab_translate("Begin date");
					$this->enddatetxt = bab_translate("End date");
					$this->titletxt = bab_translate("Title");
					$this->desctxt = bab_translate("Description");
					$this->cattxt = bab_translate("Category");
					$this->begindate = bab_longDate(bab_mktime($arr['start_date']));
					$this->enddate = bab_longDate(bab_mktime($arr['end_date']));
					$this->title= $arr['title'];
					$this->description = $arr['description'];
					if( $arr['id_cat'] != 0 )
						{
						list($this->category) = $babDb->db_fetch_row($babDB->db_query("select name from ".BAB_CALCATEGORIES_TBL." where id='".$arr['id_cat']."'"));
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

switch($idx)
	{
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