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
include_once $babInstallPath."utilit/uiutil.php";

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

		list($this->curyear,$this->curmonth,$this->curday) = !empty($_REQUEST['date']) ? explode(',',$_REQUEST['date']) : array(date('Y'),date('m'),date('d'));

		$this->curview = !empty($_REQUEST['curview']) ? $_REQUEST['curview'] : 'viewm';

		$this->calid = & $_REQUEST['calid'];

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
		$this->repeat = bab_translate("Repeat");
		$this->everyday = bab_translate("Everyday");
		$this->title = bab_translate("Title");
		$this->description = bab_translate("Description");
		$this->category = bab_translate("Category");
		$this->usrcalendarstxt = bab_translate("Users calendars");
		$this->grpcalendarstxt = bab_translate("Groups calendars");
		$this->rescalendarstxt = bab_translate("Resources calendars");

		$this->t_norepeat = bab_translate("No repeat");
		$this->t_daily = bab_translate("Daily");
		$this->t_weekly = bab_translate("Weekly");
		$this->t_monthly = bab_translate("Monthly");
		$this->t_yearly = bab_translate("Yearly");
		$this->t_all_the = bab_translate("Tout les");
		$this->t_years = bab_translate("years");
		$this->t_months = bab_translate("months");
		$this->t_weeks = bab_translate("weeks");
		$this->t_days = bab_translate("days");

		$this->t_sun = bab_translate("Sunday");
		$this->t_mon = bab_translate("Monday");
		$this->t_tue = bab_translate("Tuesday");
		$this->t_wen = bab_translate("Wednesday");
		$this->t_thu = bab_translate("Thursday");
		$this->t_fri = bab_translate("Friday");
		$this->t_sat = bab_translate("Saturday");

		$this->t_color = bab_translate("Color");
		$this->t_bprivate = bab_translate("Private");
		$this->t_block = bab_translate("Lock");
		$this->t_bfree = bab_translate("Free");
		$this->t_yes = bab_translate("Yes");
		$this->t_no = bab_translate("No");

		$this->repeat_dateendtxt = bab_translate("Periodicity end date");

		$this->ymin = 2;
		$this->ymax = 5;

		$this->icalendar = &$GLOBALS['babBody']->icalendars;
		$this->icalendar->initializeCalendars();

		$this->rescat = $this->db->db_query("SELECT * FROM ".BAB_CAL_CATEGORIES_TBL." ");
		}



	function urlDate($callback,$month,$year)
		{
		return $GLOBALS['babUrlScript']."?tg=month&callback=".$callback."&ymin=".$this->ymin."&ymax=".$this->ymax."&month=".$month."&year=".$year;
		}


	function getnextcat()
		{
		return $this->cat = $this->db->db_fetch_array($this->rescat);
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

			$this->bab_event();


			global $babBodyPopup;
			if( empty($_REQUEST['st']))


				$this->st = "08:00";
			else
				$this->st = $_REQUEST['st'];


			$this->mcals = explode(",", $this->calid);
			$this->caltype = bab_getCalendarType($this->mcals[0]);

			$this->repeat = isset($_POST['repeat'])? $_POST['repeat']: 0;
			$this->titleval = isset($_POST['title'])? $_POST['title']: '';
			
			$babBodyPopup->title = bab_translate("New calendar event");
			
			$this->yearbegin = $this->curyear;
			$this->yearmin = $this->curyear - $this->ymin;
			$this->daybegin = $this->curday;
			$this->monthbegin = $this->curmonth;
			$this->datebegin = $this->urlDate('dateBegin',$this->curmonth,$this->curyear); 
			$this->dateend = $this->urlDate('dateEnd',$this->curmonth,$this->curyear);

			$this->colorvalue = isset($_POST['color']) ? $_POST['color'] : 'FFFFFF' ;

			$descriptionval = isset($_POST['$description'])? $_POST['$description'] : "";
			$this->editor = bab_editor($descriptionval, 'evtdesc', 'vacform',150);

			$this->daytypechecked = $this->icalendar->allday == 'Y' ? "checked"  :'';
			$this->elapstime = $this->icalendar->elapstime;
			$this->ampm = $GLOBALS['babBody']->ampm == 'Y' ? true : false;

			
			
			$this->calendars = calendarchoice('vacform');


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
			if( $i <= date("t",mktime(0,0,0,$this->curmonth,$this->curday,$this->curyear)))
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


		}

	$temp = new temp();
	$babBodyPopup->babecho(	bab_printTemplate($temp,"event.html", "scripts"));
	$babBodyPopup->babecho(	bab_printTemplate($temp,"event.html", "newevent"));
	}


function modifyEvent($calendarid, $evtid, $view, $bmodif)
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

		function temp($calendarid, $evtid, $view, $bmodif)
			{
			global $BAB_SESS_USERID, $babBodyPopup;

			$this->delete = bab_translate("Delete");
			$this->db = $GLOBALS['babDB'];
			$this->calid = $calendarid;
			$this->evtid = $evtid;
			$this->bmodif = $bmodif;
			$this->caltype = bab_getCalendarType($calendarid);
			$babBodyPopup->title = bab_translate("Calendar"). "  ". bab_getCalendarOwnerName($this->calid, $this->caltype);

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

		}

	$temp = new temp($calendarid, $evtid, $view, $bmodif);
	$babBodyPopup->babecho(	bab_printTemplate($temp,"event.html", "scripts"));
	$babBodyPopup->babecho(	bab_printTemplate($temp,"event.html", "modifyevent"));
	}

function deleteEvent($evtid, $bupdrec)
	{
	global $babBody;
	
	class temp extends bab_event
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

		function temp($evtid, $bupdrec)
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

	$temp = new temp($evtid, $bupdrec);
	$babBody->babecho(	bab_printTemplate($temp,"warning.html", "warningyesno"));
	}

function viewEvent($evtid)
	{
	global $babBody;
	
	class temp extends bab_event
		{
		var $title;
		var $titlename;
		var $startdatename;
		var $startdate;
		var $enddatename;
		var $enddate;
		var $descriptionname;
		var $description;

		function temp($evtid)
			{
			$this->bab_event();




			$req = "select * from ".BAB_CAL_EVENTS_TBL." where id='".$evtid."'";
			$res = $this->db->db_query($req);
			$arr = $this->db->db_fetch_array($res);
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

function insertEvent($tabcals, $title, $description, $startdate,  $enddate, $catid, $color, $md5)
	{
	$db = $GLOBALS['babDB'];

	list($categorycolor) = $db->db_fetch_array($db->db_query("select bgcolor from ".BAB_CAL_CATEGORIES_TBL." where id='".$catid."'"));

	if (!empty($categorycolor))
		$color = $categorycolor;
	elseif (strlen($color) != 6)
		$color = 'FFFFFF';

	$req = "insert into ".BAB_CAL_EVENTS_TBL." ( title, description, start_date, end_date, id_cat, id_creator, color, hash) values ('".$title."', '".$description."', '".date('Y-m-d H:i:s',$startdate)."', '".date('Y-m-d H:i:s',$enddate)."', '".$catid."', '".$GLOBALS['BAB_SESS_USERID']."', '".$color."', '".$md5."')";
	
	$db->db_query($req);

	$id_event = $db->db_insert_id();

	foreach($tabcals as $id_cal)
		{
		$db->db_query("INSERT INTO ".BAB_CAL_EVENTS_OWNERS_TBL." (id_event,id_cal) VALUES ('".$id_event."','".$id_cal."')");
		}
	}


function post_string($key)
{
if( !bab_isMagicQuotesGpcOn())
	return mysql_escape_string($_POST[$key]);
else
	return $_POST[$key];
}

function addEvent()
	{
	global $babBody;
	
	if( empty($_POST['title']))
		{
		$babBody->msgerror = bab_translate("You must provide a title")." !!";
		return false;
		}

	if( !isset($_POST['selected_calendars']) || count($_POST['selected_calendars']) == 0 )
		{
		$babBody->msgerror = bab_translate("You must select at least one calendar type")." !!";
		return false;
		}

	$description = post_string('evtdesc');
	$title = post_string('title');
		
	$category = empty($_POST['category']) ? '0' : $_POST['category'];

	$yearbegin = $_POST['yearbegin'];
	$monthbegin = $_POST['monthbegin'];
	$daybegin = $_POST['daybegin'];
	$timebegin = isset($_POST['timebegin']) ? $_POST['timebegin'] : '00:00';
	$yearend = $_POST['yearend'];
	$monthend = $_POST['monthend'];
	$dayend = $_POST['dayend'];
	$timeend = isset($_POST['timeend']) ? $_POST['timeend'] : '23:59';


	$tb = explode(':',$timebegin);
	$te = explode(':',$timeend);

	$begin = mktime( $tb[0],$tb[1],0,$monthbegin, $daybegin, $yearbegin );
	$end = mktime( $te[0],$te[1],0,$monthend, $dayend, $yearend );

	if( $begin > $end)
		{
		$babBody->msgerror = bab_translate("End date must be older")." !";
		return false;
		}



	
	if( $_POST['repeat'] == "y")
		{

		for( $i = 0; $i < 7; $i++)
			{
			$tab[$i] = 0;
			}

		for( $i = 0; $i < count($days); $i++)
			{
			$tab[$days[$i]] = 1;
			}

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
					die('répétition a faire');
					insertEvent($_POST['selected_calendars'], $title, $description, $startdate, $enddate, $category, $_POST['color'], $md5);
					$nextday += 7;
					}
				}
			}
		}
	else
		{
		insertEvent($_POST['selected_calendars'], $title, $description, $begin, $end, $category, $_POST['color'], "");
		}
	return true;	
	}

function updateEvent($calid, $daybegin, $monthbegin, $yearbegin, $evtid, $timebegin, $timeend, $dayend, $monthend, $yearend, $title, $category, $bupdrec)
{
	global $babBody;
	
	if( empty($title))
		{
		$babBody->msgerror = bab_translate("You must provide a title")." !!";
		return;
		}

	if( !bab_isMagicQuotesGpcOn())
		{
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
global $babBody, $babDB;
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
		if( count($babBody->usergroups) > 0 && in_array($owner, $babBody->usergroups))
		{
			$bmodif = 1;
		}
		elseif( $owner == 1 && bab_isUserAdministrator())
		{
			$bmodif = 1;
		}
		else
		{
			$bmodif = 0;
		}
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
		return bab_printTemplate($this,"event.html", "calendarchoice");
		}
	}

$temp = new calendarchoice($formname);
return $temp->printhtml();
}


function record_calendarchoice()
{
if (isset($_POST['selected_calendars']) && count($_POST['selected_calendars']) > 0)
	{
	$db = &$GLOBALS['babDB'];
	list($n) = $db->db_fetch_array($db->db_query("SELECT COUNT(*) FROM ".BAB_CAL_USER_OPTIONS_TBL." WHERE id_user='".$GLOBALS['BAB_SESS_USERID']."'"));
	if ($n > 0)
		$db->db_query("UPDATE ".BAB_CAL_USER_OPTIONS_TBL." SET  user_calendarids='".implode(',',$_POST['selected_calendars'])."' WHERE id_user='".$GLOBALS['BAB_SESS_USERID']."'");
	else
		$db->db_query("INSERT INTO ".BAB_CAL_USER_OPTIONS_TBL."  (id_user,user_calendarids) VALUES ('".$GLOBALS['BAB_SESS_USERID']."','".implode(',',$_POST['selected_calendars'])."')");
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

/* main */


$idx = isset($_REQUEST['idx']) ? $_REQUEST['idx'] : "newevent";


record_calendarchoice();

$calid = bab_isCalendarAccessValid($calid);
if( !$calid )
	{
	$babBody->title = bab_translate("Access denied");
	exit;
	}

if (isset($_POST['action']))
	switch($_POST['action'])
		{
		case 'yes':
			confirmDeleteEvent($calid, $evtid, $bupdrec);
			Header("Location: ". $GLOBALS['babUrlScript']."?tg=calendar&idx=".$view.calendarquerystring());
			break;

		case 'desc':
			updateDescription($calid, $evtid, $content, $bupdrec);
			$idx = "unload";
			break;

		case 'addevent':
			if (addEvent())
				$idx = "unload";
			break;

		case 'modifyevent':
			if (updateEvent())
				$idx = "unload";
			break;

		case 'delevent':
			deleteEvent($calid, $evtid, $curday, $curmonth, $curyear, $view, $bupdrec);
			break;
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


	case "modify":
		$bmodif = isUpdateEvent($calid, $evtid);
		$babBodyPopup = new babBodyPopup();
		if( $bmodif )
			modifyEvent($calid, $evtid, $view, $bmodif);
		else
			viewEvent($calid, $evtid);

		printBabBodyPopup();
		exit;




		break;

	case "newevent":
		$babBodyPopup = new babBodyPopup();

		newEvent();
		printBabBodyPopup();
		exit;

		break;

	default:
		break;
	}
$babBody->setCurrentItemMenu($idx);

?>
