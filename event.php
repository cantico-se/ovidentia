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
include_once $babInstallPath."utilit/mcalincl.php";
include_once $babInstallPath."utilit/uiutil.php";
include_once $babInstallPath."utilit/evtincl.php";

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


class bab_event
	{
	function bab_event()

		{
		$this->db = & $GLOBALS['babDB'];

		$this->curdate = !empty($_REQUEST['date']) ? $_REQUEST['date'] : date('Y').",".date('m').",".date('d');

		list($this->curyear,$this->curmonth,$this->curday) = explode(',', $this->curdate);

		$this->curview = !empty($_REQUEST['view']) ? $_REQUEST['view'] : 'viewm';
		$this->calid = isset($_REQUEST['calid']) ? $_REQUEST['calid']  : $_POST['curcalids'];

		$this->datebegintxt = bab_translate("Begin date");
		$this->dateendtxt = bab_translate("Until date");
		$this->private = bab_translate("Private");
		$this->type = bab_translate("Type");
		$this->daytype = bab_translate("All day");
		$this->addvac = bab_translate("Add Event");
		$this->starttime = bab_translate("starttime");
		$this->endtime = bab_translate("endtime");
		$this->daystext = bab_translate("Days");
		$this->or = bab_translate("Or");
		$this->everyday = bab_translate("Everyday");
		$this->title = bab_translate("Title");
		$this->description = bab_translate("Description");
		$this->category = bab_translate("Category");
		$this->usrcalendarstxt = bab_translate("Users calendars");
		$this->grpcalendarstxt = bab_translate("Groups calendars");
		$this->rescalendarstxt = bab_translate("Resources calendars");

		$this->t_repeat = bab_translate("Repeat");
		$this->t_norepeat = bab_translate("No repeat");
		$this->t_daily = bab_translate("Daily");
		$this->t_weekly = bab_translate("Weekly");
		$this->t_monthly = bab_translate("Monthly");
		$this->t_yearly = bab_translate("Yearly");
		$this->t_all_the = bab_translate("Every");
		$this->t_years = bab_translate("years");
		$this->t_months = bab_translate("months");
		$this->t_weeks = bab_translate("weeks");
		$this->t_days = bab_translate("days");

		$this->t_sun = substr(bab_translate("Sunday"),0,3);
		$this->t_mon = substr(bab_translate("Monday"),0,3);
		$this->t_tue = substr(bab_translate("Tuesday"),0,3);
		$this->t_wen = substr(bab_translate("Wednesday"),0,3);
		$this->t_thu = substr(bab_translate("Thursday"),0,3);
		$this->t_fri = substr(bab_translate("Friday"),0,3);
		$this->t_sat = substr(bab_translate("Saturday"),0,3);

		$this->t_color = bab_translate("Color");
		$this->t_bprivate = bab_translate("Private");
		$this->t_block = bab_translate("Lock");
		$this->t_bfree = bab_translate("Free");
		$this->t_yes = bab_translate("Yes");
		$this->t_no = bab_translate("No");
		$this->t_modify = bab_translate("Modify the event");
		$this->t_test_conflicts = bab_translate("Test conflicts");

		$this->repeat_dateendtxt = bab_translate("Periodicity end date");

		$this->ymin = 2;
		$this->ymax = 5;

		$this->icalendar = &$GLOBALS['babBody']->icalendars;
		$this->icalendar->initializeCalendars();

		$this->rescat = $this->db->db_query("SELECT * FROM ".BAB_CAL_CATEGORIES_TBL." ORDER BY name");
		}



	function urlDate($callback,$month,$year)
		{
		return $GLOBALS['babUrlScript']."?tg=month&callback=".$callback."&ymin=".$this->ymin."&ymax=".$this->ymax."&month=".$month."&year=".$year;
		}


	function getnextcat()
		{
		$this->cat = $this->db->db_fetch_array($this->rescat);
		$this->selected = isset($_POST['category']) && $_POST['category'] == $this->cat['id'] ? 'selected' : '';
		return $this->cat;
		}

	
	}





function newEvent()
	{
	global $babBodyPopup;
	class temp extends bab_event
		{
		var $arrresname = array();
		var $arrresid = array();

		function temp()
			{
			global $babBody;

			$this->bab_event();

			global $babBodyPopup;

			$this->mcals = explode(",", $this->calid);
			$this->repeat = isset($GLOBALS['repeat'])? $GLOBALS['repeat']: 1;
			$this->repeat_cb_checked = isset($_POST['repeat_cb']) ? 'checked' : '';
			$this->titleval = isset($GLOBALS['title'])? $GLOBALS['title']: '';
				
			$this->datebeginurl = $this->urlDate('dateBegin',$this->curmonth,$this->curyear); 
			$this->dateendurl = $this->urlDate('dateEnd',$this->curmonth,$this->curyear);
			$this->repeat_dateend = $this->urlDate('repeat_dateend',$this->curmonth,$this->curyear);
			$this->yearmin = $this->curyear - $this->ymin;

			$this->yearbegin = !isset($GLOBALS['date0'])? $this->curyear: date("Y", (int)$GLOBALS['date0']);
			$this->monthbegin = !isset($GLOBALS['date0'])? $this->curmonth: date("m", (int)$GLOBALS['date0']);
			$this->daybegin = !isset($GLOBALS['date0'])? $this->curday: date("d", (int)$GLOBALS['date0']);
			$this->timebegin = !isset($GLOBALS['date0'])? substr($babBody->icalendars->starttime, 0, 5): date("H:i", (int)$GLOBALS['date0']);

			$this->yearend = !isset($GLOBALS['date1'])? $this->curyear: date("Y", (int)$GLOBALS['date1']);
			$this->monthend = !isset($GLOBALS['date1'])? $this->curmonth: date("m", (int)$GLOBALS['date1']);
			$this->dayend = !isset($GLOBALS['date1'])? $this->curday: date("d", (int)$GLOBALS['date1']);
			$this->timeend = !isset($GLOBALS['date1'])? substr($babBody->icalendars->endtime, 0, 5): date("H:i", (int)$GLOBALS['date1']);

			$this->repeat_yearend = !isset($GLOBALS['repeat_yearend'])? $this->curyear: $GLOBALS['repeat_yearend'];
			$this->repeat_monthend = !isset($GLOBALS['repeat_monthend'])? $this->curmonth: $GLOBALS['repeat_monthend'];
			$this->repeat_dayend = !isset($GLOBALS['repeat_dayend'])? $this->curday: $GLOBALS['repeat_dayend'];


			$this->colorvalue = isset($GLOBALS['color']) ? $GLOBALS['color'] : '' ;

			$descriptionval = isset($GLOBALS['evtdesc'])? $GLOBALS['evtdesc'] : "";
			$this->editor = bab_editor($descriptionval, 'evtdesc', 'vacform',150);

			$this->daytypechecked = $this->icalendar->allday == 'Y' && !isset($_GET['st']) ? "checked"  :'';
			$this->elapstime = $this->icalendar->elapstime;
			$this->ampm = $GLOBALS['babBody']->ampm == 'Y' ? true : false;
			$this->calendars = calendarchoice('vacform');
			$this->totaldays = date("t",mktime(0,0,0,$this->curmonth,$this->curday,$this->curyear));

			$this->daysel = !empty($_GET['st']) ? date('j',$_GET['st']) : $this->daybegin;
			$this->monthsel = !empty($_GET['st']) ? date('n',$_GET['st']) : $this->monthbegin;
			$this->yearsel = !empty($_GET['st']) ? date('Y',$_GET['st']) : $this->yearbegin;
			$this->timesel = !empty($_GET['st']) ? date('H:i',$_GET['st']) : $this->timebegin;

			$this->bprivate = true;
			$this->block = true;
			$this->bfree = true;

			$this->avariability = isset($GLOBALS['avariability']) && is_array($GLOBALS['avariability'])  ? 1 : 0;

			if (isset($_POST) && count($_POST) > 0)
				{
				$this->arr = $_POST;
				$this->daytypechecked = isset($this->arr['daytype']) ? 'checked' : '';
				$this->daysel = $this->arr['daybegin'];
				$this->monthsel = $this->arr['monthbegin'];
				$this->yearsel = $this->arr['yearbegin'];
				$this->timesel = isset($this->arr['timebegin']) ? $this->arr['timebegin'] : $this->timesel;
				$this->colorvalue = $this->arr['color'];

				for ($i = 0 ; $i < 7 ; $i++)
					{
					$this->repeat_wd_checked[$i] = isset($this->arr['repeat_wd']) && in_array($i,$this->arr['repeat_wd']) ? 'checked' : '';
					}
				}
			else
				{
				$this->arr['repeat_n_1'] = '';
				$this->arr['repeat_n_2'] = '';
				$this->arr['repeat_n_3'] = '';
				$this->arr['repeat_n_4'] = '';
				}
			}

		function getnextday()
			{
			static $i = 1, $k=0;
			if( $i <= $this->totaldays)
				{
				$this->dayid = $i;
				if( $this->daysel == $i)
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
				if( $k == 0 )
					{
					$this->daysel = isset($this->arr['dayend']) ? $this->arr['dayend'] : $this->daysel;
					$k++;
					}
				else
					{
					$this->daysel = $this->repeat_dayend;
					}
				return false;
				}
			}

		function getnextmonth()
			{
			global $babMonths;
			static $i = 1, $k = 0;

			if( $i < 13)
				{
				$this->monthid = $i;
				$this->monthname = $babMonths[$i];
				if( $this->monthsel == $i)
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
				if( $k == 0 )
					{
					$this->monthsel = isset($this->arr['monthend']) ? $this->arr['monthend'] : $this->monthsel;
					$k++;
					}
				else
					{
					$this->monthsel = $this->repeat_monthend;
					}
				return false;
				}

			}
		function getnextyear()
			{
			static $i = 0, $k=0;
			if( $i < $this->ymin + $this->ymax + 1)
				{
				$this->yearidval = $this->yearmin + $i;
				$this->yearid = $this->yearidval;
				if( $this->yearsel == $this->yearidval)
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
				if( $k == 0 )
					{
					$this->yearsel = isset($this->arr['yearend']) ? $this->arr['yearend'] : $this->yearsel;
					$k++;
					}
				else
					{
					$this->yearsel = $this->repeat_yearend;
					}
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
				if( $this->timesel == $this->timeval)
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
				$i = 0;
				$this->timesel = isset($this->arr['timeend']) ? $this->arr['timeend'] : $this->timeend;
				return false;
				}
			}

		function getnextavariability()
			{
			if (!isset($GLOBALS['avariability']) || !is_array($GLOBALS['avariability']))
				return false;
			return list(,$this->conflict) = each($GLOBALS['avariability']);
			}
		}

	$temp = new temp();
	$babBodyPopup->babecho(	bab_printTemplate($temp,"event.html", "scripts"));
	$babBodyPopup->babecho(	bab_printTemplate($temp,"event.html", "newevent"));
	}


function modifyEvent($idcal, $evtid, $cci, $view, $date)
	{
	global $babBody,$babBodyPopup;
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

		function temp($idcal, $evtid, $cci, $view, $date)
			{
			global $babBody, $BAB_SESS_USERID, $babBodyPopup;

			$this->delete = bab_translate("Delete");
			$this->t_color = bab_translate("Color");
			$this->t_bprivate = bab_translate("Private");
			$this->t_block = bab_translate("Lock");
			$this->t_bfree = bab_translate("Free");
			$this->t_yes = bab_translate("Yes");
			$this->t_no = bab_translate("No");
			$this->t_modify = bab_translate("Modify the event");
			$this->t_test_conflicts = bab_translate("Test conflicts");
			$this->db = $GLOBALS['babDB'];
			$this->calid = $idcal;
			$this->evtid = $evtid;
			$this->bmodif = false;
			$this->ccids = $cci;
			$this->curview = $view;
			$this->curdate = $date;
			$iarr = $babBody->icalendars->getCalendarInfo($this->calid);
			switch( $iarr['type'] )
				{
				case BAB_CAL_USER_TYPE:
					if( $iarr['idowner'] ==  $GLOBALS['BAB_SESS_USERID'] || $iarr['access'] != BAB_CAL_ACCESS_VIEW )
						{
						$this->bmodif = true;
						}
					break;
				case BAB_CAL_PUB_TYPE:
				case BAB_CAL_RES_TYPE:
					if( $iarr['manager'] )
						{
						$this->bmodif = true;
						}
					break;
				}
			$babBodyPopup->title = bab_translate("Calendar"). ":  ". bab_getCalendarOwnerName($this->calid, $iarr['type']);
			
			
				$res = $this->db->db_query("select * from ".BAB_CAL_EVENTS_TBL." where id='".$evtid."'");
				$this->evtarr = $this->db->db_fetch_array($res);
			

			if( !empty($this->evtarr['hash']) && $this->evtarr['hash'][0] == 'R')
				{
				$this->brecevt = true;
				$this->updaterec = bab_translate("This is recurring event. Do you want to update this occurence or series?");
				$this->all = bab_translate("All");
				$this->thisone = bab_translate("This occurence");
				}
			else
				{
				$this->brecevt = false;
				}
			$this->evtarr['description'] = bab_replace($this->evtarr['description']);
			$this->ymin = 2;
			$this->ymax = 5;
			if (isset($_POST) && count($_POST) > 0)
				{
				$this->evtarr = $_POST;
				$this->evtarr['id_cat'] = $_POST['category'];
				$this->evtarr['description'] = $_POST['evtdesc'];

				$this->yearbegin = $this->evtarr['yearbegin'];
				$this->daybegin =$this->evtarr['daybegin'];
				$this->monthbegin = $this->evtarr['monthbegin'];
				$this->yearend = $this->evtarr['yearend'];
				$this->dayend = $this->evtarr['dayend'];
				$this->monthend = $this->evtarr['monthend'];
				$this->timebegin = $this->evtarr['timebegin'];
				$this->timeend = $this->evtarr['timeend'];

				}
			else
				{
				$this->yearbegin = substr($this->evtarr['start_date'], 0,4 );
				$this->daybegin = substr($this->evtarr['start_date'], 8, 2);
				$this->monthbegin = substr($this->evtarr['start_date'], 5, 2);
				$this->yearend = substr($this->evtarr['end_date'], 0,4 );
				$this->dayend = substr($this->evtarr['end_date'], 8, 2);
				$this->monthend = substr($this->evtarr['end_date'], 5, 2);
				$this->timebegin = substr($this->evtarr['start_date'], 11, 5);
				$this->timeend = substr($this->evtarr['end_date'], 11, 5);
				}

			$this->yearmin = $this->yearbegin - $this->ymin;

			$this->nbdaysbegin = date("t", mktime(0,0,0, $this->monthbegin, $this->daybegin,$this->yearbegin));
			$this->nbdaysend = date("t", mktime(0,0,0, $this->monthend, $this->dayend,$this->yearend));

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
			$this->descurl = $GLOBALS['babUrlScript']."?tg=event&idx=updesc&calid=".$this->calid."&evtid=".$evtid;

			

			$this->editor = bab_editor($this->evtarr['description'], 'evtdesc', 'vacform',150);
			$this->elapstime = $babBody->icalendars->elapstime;
			$this->ampm = $babBody->ampm;
			$this->colorvalue = isset($_POST['color']) ? $_POST['color'] : $this->evtarr['color'] ;
			$this->avariability = isset($GLOBALS['avariability']) && is_array($GLOBALS['avariability'])  ? 1 : 0;

			

			$this->rescat = $this->db->db_query("select * from ".BAB_CAL_CATEGORIES_TBL."");
			$this->rescount = $this->db->db_num_rows($this->rescat);
			}

		function getnextcat()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->rescount)
				{
				$arr = $babDB->db_fetch_array($this->rescat);
				$this->catid = $arr['id'];
				$this->catname = $arr['name'];
				if( $this->evtarr['id_cat'] == $this->catid )
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
				return false;
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

		function getnextavariability()
			{
			if (!isset($GLOBALS['avariability']) || !is_array($GLOBALS['avariability']))
				return false;
			return list(,$this->conflict) = each($GLOBALS['avariability']);
			}

		}

	$temp = new temp($idcal, $evtid, $cci, $view, $date);
	$babBodyPopup->babecho(	bab_printTemplate($temp,"event.html", "scripts"));
	$babBodyPopup->babecho(	bab_printTemplate($temp,"event.html", "modifyevent"));
	}

function deleteEvent()
	{
	global $babBody,$babBodyPopup;
	
	class deleteEventCls extends bab_event
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

		function deleteEventCls()
			{
			if( isset($_POST['bupdrec']) && $_POST['bupdrec'] == "1" )
				{
				$bupd = 1;
				$this->message = bab_translate("This is a reccuring event.Are you sure you want to delete this event and all occurrences");
				$this->warning = bab_translate("WARNING: This operation will delete all occurrences permanently"). "!";
				}
			else
				{
				$bupd = 2;
				$this->message = bab_translate("Are you sure you want to delete this event");
				$this->warning = bab_translate("WARNING: This operation will delete event permanently"). "!";
				}
			$this->title = bab_getCalendarEventTitle($_POST['evtid']);
			$this->urlyes = $GLOBALS['babUrlScript']."?tg=event&date=".$_POST['date']."&calid=".$_POST['calid']."&evtid=".$_POST['evtid']."&action=yes&view=".$_POST['view']."&bupdrec=".$bupd."&curcalids=".$_POST['curcalids'];
			$this->yes = bab_translate("Yes");
			$this->urlno = $GLOBALS['babUrlScript']."?tg=event&idx=unload&action=no&calid=".$_POST['calid']."&view=";
			$this->no = bab_translate("No");
			}
		}

	$temp = new deleteEventCls();
	$babBodyPopup->babecho(	bab_printTemplate($temp,"warning.html", "warningyesno"));
	}

function post_string($key)
{
if( !bab_isMagicQuotesGpcOn())
	return mysql_escape_string($_POST[$key]);
else
	return $_POST[$key];
}

function addEvent(&$message)
	{
	global $babBody;
	
	if( empty($_POST['title']))
		{
		$message = bab_translate("You must provide a title")." !!";
		return false;
		}

	if( !isset($GLOBALS['calid']) || count($GLOBALS['calid']) == 0 )
		{
		$message = bab_translate("You must select at least one calendar type")." !!";
		return false;
		}

	$description = post_string('evtdesc');
	$title = post_string('title');
		
	$category = empty($_POST['category']) ? '0' : $_POST['category'];
	$color = empty($_POST['color']) ? '' : $_POST['color'];

	$yearbegin = $_POST['yearbegin'];
	$monthbegin = $_POST['monthbegin'];
	$daybegin = $_POST['daybegin'];
	$timebegin = isset($_POST['timebegin']) ? $_POST['timebegin'] : $babBody->icalendars->starttime;
	$yearend = $_POST['yearend'];
	$monthend = $_POST['monthend'];
	$dayend = $_POST['dayend'];
	$timeend = isset($_POST['timeend']) ? $_POST['timeend'] : $babBody->icalendars->endtime;
	$bprivate = isset($_POST['bprivate']) ? $_POST['bprivate'] : 'N';
	$block = isset($_POST['block']) ? $_POST['block'] : 'N';
	$bfree = isset($_POST['bfree']) ? $_POST['bfree'] : 'N';


	$tb = explode(':',$timebegin);
	$te = explode(':',$timeend);

	$begin = mktime( $tb[0],$tb[1],0,$monthbegin, $daybegin, $yearbegin );
	$end = mktime( $te[0],$te[1],0,$monthend, $dayend, $yearend );
	$repeatdate = mktime( 23,59,59,$_POST['repeat_monthend'], $_POST['repeat_dayend'], $_POST['repeat_yearend'] );

	if( $begin > $end)
		{
		$message = bab_translate("End date must be older")." !";
		return false;
		}

	$arrnotify = array();

	if( isset($_POST['repeat_cb']) && $_POST['repeat_cb'] != 0)
		{
		$hash = "R_".md5(uniqid(rand(),1));
		$duration = $end - $begin;
		switch($_POST['repeat'] )
			{
			case '2': /* weekly */
				if( empty($_POST['repeat_n_2']))
					{
					$_POST['repeat_n_2'] = 1;
					}

				$rtime = 24*3600*7*$_POST['repeat_n_2'];

				if( $duration > $rtime)
					{
					$message = bab_translate("The duration of the event must be shorter than how frequently it occurs")." !";
					return false;					
					}

				if( !isset($_POST['repeat_wd']) )
					{
					$day = $daybegin;
					$time = mktime( $tb[0],$tb[1],0,$monthbegin, $day, $yearbegin );
					do
						{
						$arrf = createEvent(explode(',', $GLOBALS['calid']), $title, $description, $time, $time+$duration, $category, $color, $bprivate, $block, $bfree, $hash);
						$arrnotify = array_unique(array_merge($arrnotify, $arrf));
						$day += 7;
						$time = mktime( $tb[0],$tb[1],0,$monthbegin, $day, $yearbegin );
						}
					while( $time < $repeatdate );
					}
				else
					{
					if( $duration > 24*3600 )
						{
						$message = bab_translate("The duration of the event must be shorter than how frequently it occurs")." !";
						return false;					
						}

					for( $i = 0; $i < count($_POST['repeat_wd']); $i++ )
						{
						$delta = $_POST['repeat_wd'][$i] - Date("w", $begin);
						if( $delta < 0 )
							{
							$delta = 7 - Abs($delta);
							}

						$day = $daybegin+$delta;
						$time = mktime( $tb[0],$tb[1],0,$monthbegin, $day, $yearbegin );
						do
							{
							$arrf = createEvent(explode(',', $GLOBALS['calid']), $title, $description, $time, $time+$duration, $category, $color, $bprivate, $block, $bfree, $hash);
							$day += 7;					
							$arrnotify = array_unique(array_merge($arrnotify, $arrf));
							$time = mktime( $tb[0],$tb[1],0,$monthbegin, $day, $yearbegin );
							}
						while( $time < $repeatdate );
						}
					}

				break;
			case '3': /* monthly */
				if( empty($_POST['repeat_n_3']))
					{
					$_POST['repeat_n_3'] = 1;
					}
				if( $duration > 24*3600*28*$_POST['repeat_n_3'])
					{
					$message = bab_translate("The duration of the event must be shorter than how frequently it occurs")." !";
					return false;					
					}
				$time = $begin;
				do
					{
					$arrf = createEvent(explode(',', $GLOBALS['calid']), $title, $description, $time, $time+$duration, $category, $color, $bprivate, $block, $bfree, $hash);
					$time = mktime( $tb[0],$tb[1],0,date("m", $time)+1, date("j", $time), date("Y", $time) );
					$arrnotify = array_unique(array_merge($arrnotify, $arrf));
					}
				while( $time < $repeatdate );
				break;
			case '4': /* yearly */
				if( empty($_POST['repeat_n_4']))
					{
					$_POST['repeat_n_4'] = 1;
					}
				if( $duration > 24*3600*365*$_POST['repeat_n_4'])
					{
					$message = bab_translate("The duration of the event must be shorter than how frequently it occurs")." !";
					return false;					
					}
				$time = $begin;
				do
					{
					$arrf = createEvent(explode(',', $GLOBALS['calid']), $title, $description, $time, $time+$duration, $category, $color, $bprivate, $block, $bfree, $hash);
					$time = mktime( $tb[0],$tb[1],0,date("m", $time), date("j", $time), date("Y", $time)+1 );
					$arrnotify = array_unique(array_merge($arrnotify, $arrf));
					}
				while( $time < $repeatdate );
				break;
			case '1': /* daily */
			default:
				if( empty($_POST['repeat_n_1']))
					{
					$_POST['repeat_n_1'] = 1;
					}
				$rtime = 24*3600*$_POST['repeat_n_1'];

				if( $duration > $rtime )
					{
					$message = bab_translate("The duration of the event must be shorter than how frequently it occurs")." !";
					return false;
					}

				$day = $daybegin;
				$time = mktime( $tb[0],$tb[1],0,$monthbegin, $day, $yearbegin );
				do
					{
					$arrf = createEvent(explode(',', $GLOBALS['calid']), $title, $description, $time, $time+$duration, $category, $color, $bprivate, $block, $bfree, $hash);
					$day += $_POST['repeat_n_1'];
					$arrnotify = array_unique(array_merge($arrnotify, $arrf));
					$time = mktime( $tb[0],$tb[1],0,$monthbegin, $day, $yearbegin );
					}
				while( $time < $repeatdate );
				break;
			}

		}
	else
		{
		$arrnotify = createEvent(explode(',', $GLOBALS['calid']), $title, $description, $begin, $end, $category, $color, $bprivate, $block, $bfree, '');
		}

	if( count($arrnotify) > 0 )
		{
		$arrusr = array();
		$arrres = array();
		$arrpub = array();
		for( $i = 0; $i < count($arrnotify); $i++ )
			{
			$arr = $babBody->icalendars->getCalendarInfo($arrnotify[$i]);
			switch($arr['type'])
				{
				case BAB_CAL_USER_TYPE:
					if( $arr['idowner'] != $GLOBALS['BAB_SESS_USERID'] )
						{
						$arrusr[] = $arrnotify[$i];
						}
					break;
				case BAB_CAL_PUB_TYPE:
					$arrres[] = $arrnotify[$i];
					break;
				case BAB_CAL_RES_TYPE:
					$arrpub[] = $arrnotify[$i];
					break;
				}
			}

		$startdate = bab_longDate($begin);
		$enddate = bab_longDate($end);
		if( count($arrusr) > 0 )
			{
			notifyPersonalEvent($title, $description, $startdate, $enddate, $arrusr);
			}
		if( count($arrres) > 0 )
			{
			notifyResourceEvent($title, $description, $startdate, $enddate, $arrres);
			}
		if( count($arrpub) > 0 )
			{
			notifyPublicEvent($title, $description, $startdate, $enddate, $arrpub);
			}
		}
	return true;	
	}

function updateEvent(&$message)
{
	global $babBody;
	
	if( empty($_POST['title']))
		{
		$message = bab_translate("You must provide a title")." !!";
		return false;
		}

	if( !bab_isMagicQuotesGpcOn())
		{
		$title = addslashes($_POST['title']);
		$description = addslashes($_POST['evtdesc']);
		}
	else
		{
		$title = $_POST['title'];
		$description = $_POST['evtdesc'];
		}
		
	$db = $GLOBALS['babDB'];

	if( empty($_POST['category']))
		$catid = 0;
	else
		$catid = $_POST['category'];

	$evtinfo = $db->db_fetch_array($db->db_query("select hash, start_date, end_date from ".BAB_CAL_EVENTS_TBL." where id='".$_POST['evtid']."'"));

	$arrupdate = array();
	$bnotify = false;

	if( !empty($evtinfo['hash']) &&  $evtinfo['hash'][0] == 'R' && isset($_POST['bupdrec']) && $_POST['bupdrec'] == "1" )
	{
		$res = $db->db_query("select * from ".BAB_CAL_EVENTS_TBL." where hash='".$evtinfo['hash']."'");
		while( $arr = $db->db_fetch_array($res))
		{
			$rr = explode(" ", $arr['start_date']);
			$rr0 = explode("-", $rr[0]);
			$rr1 = explode(":", $rr[1]);
			$startdate = sprintf("%04d-%02d-%02d %s:00", $rr0[0], $rr0[1], $rr0[2], $_POST['timebegin']);

			$rr = explode(" ", $arr['end_date']);
			$rr0 = explode("-", $rr[0]);
			$rr1 = explode(":", $rr[1]);
			$enddate = sprintf("%04d-%02d-%02d %s:00", $rr0[0], $rr0[1], $rr0[2], $_POST['timeend']);

			if( bab_mktime($startdate) > bab_mktime($enddate) )
				{
				$message = bab_translate("End date must be older")." !!";
				return false;
				}

			$arrupdate[$arr['id']] = array('start'=>$startdate, 'end' => $enddate);
			if( $evtinfo['start_date'] != $startdate || $evtinfo['end_date'] != $enddate )
			{
				$bnotify = true;
			}
		}

	}
	else
	{

		$startdate = sprintf("%04d-%02d-%02d %s:00", $_POST['yearbegin'], $_POST['monthbegin'], $_POST['daybegin'], $_POST['timebegin']);
		$enddate = sprintf("%04d-%02d-%02d %s:00", $_POST['yearend'], $_POST['monthend'], $_POST['dayend'], $_POST['timeend']);

		$arrupdate[$_POST['evtid']] = array('start'=>$startdate, 'end' => $enddate);

		if( $evtinfo['start_date'] != $startdate || $evtinfo['end_date'] != $enddate )
		{
			$bnotify = true;
		}
	}

	reset($arrupdate);
	$req = "update ".BAB_CAL_EVENTS_TBL." set title='".$title."', description='".$description."', id_cat='".$catid."', color='".$_POST['color']."', bprivate='".$_POST['bprivate']."', block='".$_POST['block']."', bfree='".$_POST['bfree']."'";
	foreach($arrupdate as $key => $val)
	{
		$db->db_query($req.", start_date='".$val['start']."', end_date='".$val['end']."' where id='".$key."'" );
	}

	if( $bnotify )
	{
		notifyEventUpdate($_POST['evtid'], false);
	}
	return true;
}

function confirmDeleteEvent()
{
	include_once $GLOBALS['babInstallPath']."utilit/afincl.php";
	$db = $GLOBALS['babDB'];
	if( $GLOBALS['bupdrec'] == "1" )
		{
		$res = $db->db_query("select hash from ".BAB_CAL_EVENTS_TBL." where id='".$GLOBALS['evtid']."'");
		$arr = $db->db_fetch_array($res);
		if( $arr['hash'] != "" && $arr['hash'][0] == 'R')
			{
			$res = $db->db_query("select id from ".BAB_CAL_EVENTS_TBL." where hash='".$arr['hash']."'");
			while( $arr = $db->db_fetch_array($res) )
				{
				$db->db_query("delete from ".BAB_CAL_EVENTS_TBL." where id='".$arr['id']."'");
				$res2 = $db->db_query("select idfai from ".BAB_CAL_EVENTS_OWNERS_TBL." where id_event='".$arr['id']."'");
				while( $rr = $db->db_fetch_array($res2) )
					{
					if( $rr['idfai'] != 0 )
						{
						deleteFlowInstance($rr['idfai']);
						}
					}
				$db->db_query("delete from ".BAB_CAL_EVENTS_OWNERS_TBL." where id_event='".$arr['id']."'");
				}
			}
		}
	else
		{
		$db->db_query("delete from ".BAB_CAL_EVENTS_TBL." where id='".$GLOBALS['evtid']."'");
		$res2 = $db->db_query("select idfai from ".BAB_CAL_EVENTS_OWNERS_TBL." where id_event='".$GLOBALS['evtid']."'");
		while( $rr = $db->db_fetch_array($res2) )
			{
			if( $rr['idfai'] != 0 )
				{
				deleteFlowInstance($rr['idfai']);
				}
			}
		$db->db_query("delete from ".BAB_CAL_EVENTS_OWNERS_TBL." where id_event='".$GLOBALS['evtid']."'");
		}
}


function calendarquerystring()
	{
	$qs = '&calid='.$_REQUEST['calid'];
	$qs .= '&curday='.$_REQUEST['curday'];
	$qs .= '&curmonth='.$_REQUEST['curmonth'];
	$qs .= '&curyear='.$_REQUEST['curyear'];

	return $qs;
	}


function eventAvariabilityCheck(&$avariability_message)
	{
	if( !isset($_POST['monthbegin']))
		{
		return true; /* to rmove php warnings. This function must be rewrited */
		}

	if (isset($_POST['test_conflicts']))
		$_POST['avariability'] = 0;

	if (!isset($_POST['avariability']) || $_POST['avariability'] == 1 || (isset($_POST['bfree']) && $_POST['bfree'] == 'Y' ))
		{
		$GLOBALS['avariability'] = 0;
		return true;
		}
	$calid = explode(',',$GLOBALS['calid']);

	$bfree = isset($_POST['bfree']) ? $_POST['bfree'] : 'N';

	$timebegin = isset($_POST['timebegin']) ? $_POST['timebegin'] : '00:00';
	$timeend = isset($_POST['timeend']) ? $_POST['timeend'] : '23:59';

	$tb = explode(':',$timebegin);
	$te = explode(':',$timeend);

	$begin = mktime( $tb[0],$tb[1],0,$_POST['monthbegin'], $_POST['daybegin'], $_POST['yearbegin'] );
	$end = mktime( $te[0],$te[1],0,$_POST['monthend'], $_POST['dayend'], $_POST['yearend'] );

	$begin_day = mktime( 0,0,0,$_POST['monthbegin'], $_POST['daybegin'], $_POST['yearbegin'] );

	$db = &$GLOBALS['babDB'];

	$workdays = array();
	$workdays_user = array();

	function time_to_sec($time)
		{
		list($h,$m,$s) = explode(':',$time);
		return $h*3600 + $m*60 + $s;
		}

	function sec_to_time($sec)
		{
		$min = $sec%3600;
		return sprintf("%02s:%02s:%02s", ($sec/3600), ($min/60), ($min%60));
		}

	$starttime_sec = 0;
	$endtime_sec = 3600*24;

	$res = $db->db_query("SELECT c.id,o.workdays, o.start_time, o.end_time FROM ".BAB_CAL_USER_OPTIONS_TBL." o, ".BAB_CALENDAR_TBL." c WHERE c.id IN(".implode(',',$calid).") AND o.id_user = c.owner AND c.type='1'");

	$calopt = array();
	while ($arr = $db->db_fetch_array($res))
		{
		$calopt[$arr['id']] = empty($arr['workdays']) ? explode( ',', $GLOBALS['babBody']->babsite['workdays']) : explode( ',', $arr['workdays'] );
		$workdays = array_merge ($workdays,  $calopt[$arr['id']]);
		
		$s = time_to_sec($arr['start_time']);
		$starttime_sec = $s > $starttime_sec ? $s : $starttime_sec;
		$s = time_to_sec($arr['end_time']);
		$endtime_sec = $s < $endtime_sec ? $s : $endtime_sec;
		}

	$workdays = array_unique($workdays);

	if( count($calopt) > 0 )
		{
		foreach ($calopt as $user)
			{
			foreach ($workdays as $k => $day)
				{
				if (!in_array($day,$user))
					{
					unset($workdays[$k]);
					}
				}
			}
		}

	$starttime = sec_to_time($starttime_sec);
	$endtime = sec_to_time($endtime_sec);

	$nbdays = ($end - $begin)/(24*3600);

	$GLOBALS['avariability'] = array();

	if( count($workdays) > 0)
		{
		for($i = 0; $i < $nbdays; $i++)
			{
			$begin_day = $begin + $i*(24*3600);
			$end_day = $end + $i*(24*3600);
			$day = mktime( 0,0,0,date('n',$begin_day), date('j',$begin_day), date('Y',$begin_day) );

			if (!in_array(date('w',$begin_day),$workdays))
				{
				$GLOBALS['avariability'][] = bab_longDate($begin_day,false);
				$message1 = bab_translate("The event is on a non-working day");
				}

			if ($end_day <= ($day + $starttime_sec) || $begin_day >= ($day + $endtime_sec) )
				{
				$GLOBALS['avariability'][] = bab_longDate($begin_day);
				$message3 = bab_translate("The event isn't on displayed hours for all calendars");
				}
			}
		}

	$begin++;
	$end--;

	$sdate = sprintf("%04s-%02s-%02s %02s:%02s:%02s", date('Y',$begin), date('m',$begin), date('d',$begin), date('H',$begin), date('i',$begin), date('s',$begin));
	$edate = sprintf("%04s-%02s-%02s %02s:%02s:%02s",  date('Y',$end), date('m',$end), date('d',$end),date('H',$end),date('i',$end), date('s',$end));

	$mcals = & new bab_mcalendars($sdate, $edate, $calid);
	while ($cal = current($mcals->objcals)) 
		{
		foreach ($cal->events as $event)
			{
			if ($event['bfree'] !='Y' && (!isset($_POST['evtid']) || $_POST['evtid'] != $event['id_event']))
				{
				$GLOBALS['avariability'][] = $cal->cal_name.' '.bab_translate("on the event").' : '.$event['title'].' ('.bab_shortDate(bab_mktime($event['start_date']),false).')';
				$message2 = bab_translate("The event is in conflict with a calendar");
				}
			}
		next($mcals->objcals);
		}

	if ( count($GLOBALS['avariability']) > 0 )
		{
		$avariability_message = isset($message1) ? $message1 : '';
		$avariability_message .= isset($message2) ? '<br />'.$message2 : '';
		$avariability_message .= isset($message3) ? '<br />'.$message3 : '';
		return false;
		}
	else
		{
		$GLOBALS['avariability'] = 0;
		return true;
		}
	}

/* main */
$idx = isset($_REQUEST['idx']) ? $_REQUEST['idx'] : "newevent";
$calid = isset($_POST['selected_calendars'])? implode(',', $_POST['selected_calendars']): $calid;

$calid = bab_isCalendarAccessValid($calid);
if( !$calid )
	{
	echo bab_translate("Access denied");
	exit;
	}

$avariability_message = '';

if (isset($_REQUEST['action']))
	{
	switch($_REQUEST['action'])
		{
		case 'yes':
			confirmDeleteEvent();
			$idx="unload";
			break;

		case 'addevent':
			$message = '';
			if (eventAvariabilityCheck($avariability_message) && addEvent($message))
				{
				$idx = "unload";
				}
			else
				{
				$idx = 'newevent';
				}
			break;

		case 'modifyevent':
			if( isset($_POST['Submit']) || isset($_POST['test_conflicts']))
				{
				$message = '';
				if (eventAvariabilityCheck($avariability_message) && updateEvent($message))
					{
					$idx = "unload";
					}
				else
					{
					$idx = "modevent";
					$cci = $_POST['curcalids'];
					}
				}
			elseif(isset($_POST['evtdel']))
				{
				$message = '';
				$babBodyPopup = new babBodyPopup();
				$babBodyPopup->msgerror = $message;
				deleteEvent();
				printBabBodyPopup();
				exit;
				}
			
			break;
		}
	}



switch($idx)
	{
	case "unload":
		exit;
		include_once $babInstallPath."utilit/uiutil.php";
		if( !isset($popupmessage)) { $popupmessage = bab_translate("Your event has been updated");}
		switch($view)
		{
			case 'viewd':
				$refreshurl = $GLOBALS['babUrlScript']."?tg=calday&calid=".$curcalids."&date=".$date;
				break;
			case 'viewq':
				$refreshurl = $GLOBALS['babUrlScript']."?tg=calweek&calid=".$curcalids."&date=".$date;
				break;
			case 'viewm':
				$refreshurl = $GLOBALS['babUrlScript']."?tg=calmonth&calid=".$curcalids."&date=".$date;
				break;
			default:
				$popupmessage = "";
				$refreshurl = "";
				break;
		}
		popupUnload($popupmessage, $refreshurl);
		exit;
		break;

	case "modevent":
		if( !isset($message)) { $message = '';}
		$babBodyPopup = new babBodyPopup();
		$babBodyPopup->msgerror = $message;
		modifyEvent($calid, $evtid, $cci, $view, $date);
		printBabBodyPopup();
		exit;
		break;

	case "newevent":
		if( !isset($message)) { $message = '';}
		$babBodyPopup = new babBodyPopup();
		$babBodyPopup->title = bab_translate("New calendar event");
		$babBodyPopup->msgerror = $message;
		newEvent();
		printBabBodyPopup();
		exit;
		break;

	default:
		break;
	}
?>
