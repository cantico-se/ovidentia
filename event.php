<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/

function newEvent($calendarid, $day, $month, $year, $view, $title, $description)
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
		var $countres;
		var $bcategory;
		var $daytypechecked;

		var $curday;
		var $curmonth;
		var $curyear;
		var $curview;
		var $msie;

		var $titleval;
		var $descriptionval;

		function temp($calendarid, $day, $month, $year, $view, $title, $description)
			{
			global $BAB_SESS_USERID, $babBody;
			$this->curday = $day;
			$this->curmonth = $month;
			$this->curyear = $year;
			$this->curview = $view;
			$this->calid = $calendarid;
			$this->caltype = bab_getCalendarType($calendarid);
			$this->titleval = isset($title)? $title: "";
			$this->descriptionval = isset($description)? $description: "";
			$babBody->title = bab_translate("Calendar"). "  ". bab_getCalendarOwnerName($this->calid, $this->caltype);
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
			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_CALOPTIONS_TBL." where id_user='".$BAB_SESS_USERID."'";
			$res = $this->db->db_query($req);
			$this->daytypechecked = "";
			if( $res && $this->db->db_num_rows($res))
				{
				$arr = $this->db->db_fetch_array($res);
				if( $arr['allday'] == "Y")
					$this->daytypechecked = "checked";
				}

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

					$req = "select * from ".BAB_RESOURCESCAL_TBL." where id_group='1'";
					$req2 = "select * from ".BAB_CATEGORIESCAL_TBL." where id_group='1'";
					if( $this->countgroups > 0)
						{
						for( $i = 0; $i < $this->countgroups; $i++)
							{
							$arr = $this->db->db_fetch_array($this->resgroups);
							$req .= " or id_group='".$arr['id']."'"; 
							$req2 .= " or id_group='".$arr['id']."'"; 
							}
						$this->db->db_data_seek($this->resgroups, 0);
						}
					$this->resres = $this->db->db_query($req);
					$this->countres = $this->db->db_num_rows($this->resres);

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
					$this->countres = 0;
					$this->countcat = 0;
					break;
				}
			if(( strtolower(bab_browserAgent()) == "msie") and (bab_browserOS() == "windows"))
				$this->msie = 1;
			else
				$this->msie = 0;	
/*
$babBody->script = <<<EOD
EOD;
*/
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
				//$this->yearid = $i+1;
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
/*
		function getnexttime()
			{

			static $i = -12;
			static $mn = 0;

			if( $i < 12)
				{
				if( $i < 1)
					$this->time = sprintf("%02d:%02d PM", Abs($i), $mn);
				else
					$this->time = sprintf("%02d:%02d AM", $i, $mn);
				if( $mn == 0)
					{
					$mn = 30;
					}
				else
					{
					$mn = 0;
					$i++;
					}
				return true;
				}
			else
				{
				$i = -12;
				return false;
				}

			}

*/
		function getnexttime()
			{

			static $i = 0;
			static $mn = 0;

			if( $i < 24)
				{
				$this->time = sprintf("%02d:%02d", $i, $mn);
				if( $i == 8 && $mn == 0)
					$this->selected = "selected";
				else
					$this->selected = "";
				if( $mn == 0)
					{
					$mn = 30;
					}
				else
					{
					$mn = 0;
					$i++;
					}
				return true;
				}
			else
				{
				$i = 0;
				$mn = 0;
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
				$i++;
				return true;
				}
			else
				{
				$i = 0;
				return false;
				}

			}

		function getnextgroup()
			{
			static $i = 0;
			if( $i < $this->countgroups)
				{
				$arr = $this->db->db_fetch_array($this->resgroups);
				$this->groupname = $arr['name'];
				$this->groupcalid = bab_getCalendarId($arr['id'], 2);
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

	$temp = new temp($calendarid, $day, $month, $year, $view, $title, $description);
	$babBody->babecho(	bab_printTemplate($temp,"event.html", "newevent"));
	}


function modifyEvent($calendarid, $evtid, $day, $month, $year, $view)
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
		var $countres;
		var $evtid;
		var $bcategory;

		var $curday;
		var $curmonth;
		var $curyear;
		var $curview;
		var $descurl;

		function temp($calendarid, $evtid, $day, $month, $year, $view)
			{
			global $BAB_SESS_USERID, $babBody;

			$this->db = $GLOBALS['babDB'];
			$this->calid = $calendarid;
			$this->evtid = $evtid;
			$this->caltype = bab_getCalendarType($calendarid);
			$babBody->title = bab_translate("Calendar"). "  ". bab_getCalendarOwnerName($this->calid, $this->caltype);

			$req = "select * from ".BAB_CAL_EVENTS_TBL." where id='$evtid'";
			$res = $this->db->db_query($req);
			$this->evtarr = $this->db->db_fetch_array($res);
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
					$this->countres = 0;
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
/*
$babBody->script = <<<EOD
EOD;
*/
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
/*
		function getnexttime()
			{

			static $i = -12;
			static $mn = 0;

			if( $i < 12)
				{
				if( $i < 1)
					$this->time = sprintf("%02d:%02d PM", Abs($i), $mn);
				else
					$this->time = sprintf("%02d:%02d AM", $i, $mn);
				if( $mn == 0)
					{
					$mn = 30;
					}
				else
					{
					$mn = 0;
					$i++;
					}
				return true;
				}
			else
				{
				$i = -12;
				return false;
				}

			}

*/
		function getnexttime()
			{

			static $i = 0;
			static $mn = 0;
			static $tr = 0;

			if( $i < 24)
				{
				$this->time = sprintf("%02d:%02d", $i, $mn);
				if( $tr == 0 &&  $this->time == $this->timebegin)
					$this->selected = "selected";
				else if( $tr == 1 &&  $this->time == $this->timeend)
					$this->selected = "selected";
				else
					$this->selected = "";
				if( $mn == 0)
					{
					$mn = 30;
					}
				else
					{
					$mn = 0;
					$i++;
					}
				return true;
				}
			else
				{
				$i = 0;
				$mn = 0;
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

	$temp = new temp($calendarid, $evtid, $day, $month, $year, $view);
	$babBody->babecho(	bab_printTemplate($temp,"event.html", "modifyevent"));
	}

function deleteEvent($calid, $evtid, $day, $month, $year, $view)
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

		function temp($calid, $evtid, $day, $month, $year, $view)
			{
			$this->message = bab_translate("Are you sure you want to delete this event");
			$this->title = bab_getCalendarEventTitle($evtid);
			$this->warning = bab_translate("WARNING: This operation will delete event permanently"). "!";
			$this->urlyes = $GLOBALS['babUrlScript']."?tg=event&idx=viewm&day=".$day."&month=".$month."&year=".$year. "&calid=".$calid."&evtid=".$evtid."&action=Yes&view=".$view;
			$this->yes = bab_translate("Yes");
			$this->urlno = $GLOBALS['babUrlScript']."?tg=event&idx=modify&day=".$day."&month=".$month."&year=".$year. "&calid=".$calid."&evtid=".$evtid."&view=".$view;
			$this->no = bab_translate("No");
			}
		}

	$temp = new temp($calid, $evtid, $day, $month, $year, $view);
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

function addEvent($calid, $daybegin, $monthbegin, $yearbegin, $daytype, $timebegin, $timeend, $repeat, $days, $dayend, $monthend, $yearend, $title, 	$description, $category)
{
	global $babBody;
	
	if( empty($title))
		{
		$babBody->msgerror = bab_translate("You must provide a title")." !!";
		return false;
		}

	if(!get_cfg_var("magic_quotes_gpc"))
		{
		$description = addslashes($description);
		$title = addslashes($title);
		}
		
	$db = $GLOBALS['babDB'];


	if( empty($category))
		$catid = 0;
	else
		$catid = $category;

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

			for( $i = 6; $i >= 0; $i--)
				{
				if($i > 0 && $tab[$i] != 0 && $tab[$i-1] != 0)
					{
					$tab[$i-1] = $tab[$i-1] + $tab[$i];
					$tab[$i] = 0;
					}
				}
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

						$req = "insert into ".BAB_CAL_EVENTS_TBL." ( id_cal, title, description, start_date, start_time, end_date, end_time, id_cat) values ";
						$req .= "('".$calid."', '".$title."', '".$description."', '".$startdate."', '".$starttime."', '".$enddate."', '".$endtime."', '".$catid."')";
						$db->db_query($req);
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
			$req = "insert into ".BAB_CAL_EVENTS_TBL." ( id_cal, title, description, start_date, start_time, end_date, end_time, id_cat) values ";
			$req .= "('".$calid."', '".$title."', '".$description."', '".$startdate."', '".$starttime."', '".$enddate."', '".$endtime."', '".$catid."')";
			$db->db_query($req);
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
	$req = "insert into ".BAB_CAL_EVENTS_TBL." ( id_cal, title, description, start_date, start_time, end_date, end_time, id_cat) values ";
	$req .= "('".$calid."', '".$title."', '".$description."', '".$startdate."', '".$starttime."', '".$enddate."', '".$endtime."', '".$catid."')";
	$db->db_query($req);
	return true;
	}

	
}

function updateEvent($calid, $daybegin, $monthbegin, $yearbegin, $evtid, $timebegin, $timeend, $dayend, $monthend, $yearend, $title, $description, $category)
{
	global $babBody;
	
	if( empty($title))
		{
		$babBody->msgerror = bab_translate("You must provide a title")." !!";
		return;
		}

	if(!get_cfg_var("magic_quotes_gpc"))
		{
		$description = addslashes($description);
		$title = addslashes($title);
		}
		
	$db = $GLOBALS['babDB'];

	if( empty($category))
		$catid = 0;
	else
		$catid = $category;

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

	$req = "update ".BAB_CAL_EVENTS_TBL." set title='$title', start_date='$startdate', start_time='$starttime', end_date='$enddate', end_time='$endtime', id_cat='$catid' where id='$evtid'";
	$db->db_query($req);

}

function confirmDeleteEvent($calid, $evtid)
{
	$db = $GLOBALS['babDB'];
	$req = "delete from ".BAB_CAL_EVENTS_TBL." where id='$evtid'";
	$res = $db->db_query($req);	
}

function updateDescription($calid, $evtid, $content)
{
	$db = $GLOBALS['babDB'];
	$db->db_query("update ".BAB_CAL_EVENTS_TBL." set description='".$content."' where id='".$evtid."'");
}

/* main */
if( !isset($idx))
	$idx = "newevent";


if( isset($action) && $action == "Yes")
	{
	confirmDeleteEvent($calid, $evtid);
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=calendar&idx=".$curview."&calid=".$calid."&day=".$curday."&month=".$curmonth."&year=".$curyear);
	}

if( isset($update) && $update == "desc")
	{
	updateDescription($calid, $evtid, $content);
	$idx = "unload";
	}

if( isset($modifyevent) && $modifyevent == "modify")
	{
	updateEvent($calid, $daybegin, $monthbegin, $yearbegin, $evtid, $timebegin, $timeend, $dayend, $monthend, $yearend, $title, $description, $category);
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=calendar&idx=".$curview."&calid=".$calid."&day=".$curday."&month=".$curmonth."&year=".$curyear);
	}

if( isset($addevent) && $addevent == "add")
	{
	if( !addEvent($calid, $daybegin, $monthbegin, $yearbegin, $daytype, $timebegin, $timeend, $repeat, $days, $dayend, $monthend, $yearend, $title, $description, $category))
		{
		$day = $daybegin;
		$month = $monthbegin;
		$year = $yearbegin;
		$view = $curview;
		$idx = "newevent";
		}
	else
		Header("Location: ". $GLOBALS['babUrlScript']."?tg=calendar&idx=".$curview."&calid=".$calid."&day=".$curday."&month=".$curmonth."&year=".$curyear);
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
		deleteEvent($calid, $evtid, $day, $month, $year, $view);
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
		$caltype = bab_getCalendarType($calid);
		$owner = bab_getCalendarOwner($calid);
		$bmanager = bab_isUserGroupManager();
		switch($caltype)
			{
			case 1:
				if( $owner == $BAB_SESS_USERID)
					$bmodif = 1;
				else
					$bmodif = 0;
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
		if( $bmodif )
			modifyEvent($calid, $evtid, $day, $month, $year, $view);
		else
			viewEvent($calid, $evtid, $day, $month, $year, $view);

		if( bab_isUserGroupManager())
			{
			$babBody->addItemMenu("listcat", bab_translate("Categories"), $GLOBALS['babUrlScript']."?tg=confcals&idx=listcat&userid=$BAB_SESS_USERID");
			$babBody->addItemMenu("resources", bab_translate("Resources"), $GLOBALS['babUrlScript']."?tg=confcals&idx=listres&userid=$BAB_SESS_USERID");
			}
		$babBody->addItemMenu("calendar", bab_translate("Calendar"), $GLOBALS['babUrlScript']."?tg=calendar&idx=".$view."&day=".$day."&month=".$month."&year=".$year. "&calid=".$calid);
		if( $bmodif )
			$babBody->addItemMenu("delete", bab_translate("Delete"), $GLOBALS['babUrlScript']."?tg=event&idx=delete&day=".$day."&month=".$month."&year=".$year. "&calid=".$calid. "&evtid=".$evtid."&view=".$view);
		break;

	case "newevent":
	default:
		newEvent($calid, $day, $month, $year, $view, $title, $description);
		if( bab_isUserGroupManager())
			{
			$babBody->addItemMenu("listcat", bab_translate("Categories"), $GLOBALS['babUrlScript']."?tg=confcals&idx=listcat&userid=$BAB_SESS_USERID");
			$babBody->addItemMenu("resources", bab_translate("Resources"), $GLOBALS['babUrlScript']."?tg=confcals&idx=listres&userid=$BAB_SESS_USERID");
			}
		$babBody->addItemMenu("calendar", bab_translate("Calendar"), $GLOBALS['babUrlScript']."?tg=calendar&idx=".$view."&day=".$day."&month=".$month."&year=".$year. "&calid=".$calid);
		break;
	}
$babBody->setCurrentItemMenu($idx);

?>