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
include_once $babInstallPath."utilit/afincl.php";
include_once $babInstallPath."utilit/mailincl.php";
include_once $babInstallPath."utilit/vacincl.php";

define("VAC_MAX_REQUESTS_LIST", 20);

function requestVacation($daybegin, $monthbegin, $yearbegin,$dayend, $monthend, $yearend, $halfdaybegin, $halfdayend)
	{
	global $babBody;
	class temp
		{
		

		function temp($daybegin, $monthbegin, $yearbegin,$dayend, $monthend, $yearend, $halfdaybegin, $halfdayend)
			{
			global $babBody;
			$this->datebegintxt = bab_translate("Begin date");
			$this->dateendtxt = bab_translate("End date");
			$this->vactype = bab_translate("Vacation type");
			$this->addvac = bab_translate("Request vacation");
			$this->remark = bab_translate("Remarks");
			$this->nbdaystxt = bab_translate("Quantity");
			$this->invaliddate = bab_translate("ERROR: End date must be older");
			$this->invaliddate = str_replace("'", "\'", $this->invaliddate);
			$this->invaliddate = str_replace('"', "'+String.fromCharCode(34)+'",$this->invaliddate);
			$this->invaliddate2 = bab_translate("Total days does'nt fit between dates");
			$this->invaliddate2 = str_replace("'", "\'", $this->invaliddate2);
			$this->invaliddate2 = str_replace('"', "'+String.fromCharCode(34)+'",$this->invaliddate2);
			$this->invalidentry = bab_translate("Invalid entry!  Only numbers are accepted or . !");
			$this->invalidentry = str_replace("'", "\'", $this->invalidentry);
			$this->invalidentry = str_replace('"', "'+String.fromCharCode(34)+'",$this->invalidentry);
			$this->invalidentry1 = bab_translate("Invalid entry");
			$this->invalidentry2 = bab_translate("Days must be multiple of 0.5");
			$this->invalidentry3 = bab_translate("The number of days exceed the total allowed");
			$this->totaltxt = bab_translate("Total");
			$this->balancetxt = bab_translate("Balance");
			$this->calendar = bab_translate("Planning");
			$this->totalval = 0;
			$this->maxallowed = 0;
			$this->db = & $GLOBALS['babDB'];

			$yearbegin = date("Y") + $yearbegin - 1;
			$yearend = date("Y") + $yearend - 1;

			$this->begin = $yearbegin.'-'.$monthbegin.'-'.$daybegin;
			$this->end = $yearend.'-'.$monthend.'-'.$dayend;
			$this->halfdaybegin = $halfdaybegin;
			$this->halfdayend = $halfdayend;

			$this->rights = bab_getRightsOnPeriod($this->begin, $this->end);

			$begin = mktime(0, 0, 0, $monthbegin, $daybegin, $yearbegin );
			$end = mktime(0, 0, 0, $monthend, $dayend, $yearend);

			$this->datebegin = bab_longdate($begin,false);
			$this->dateend = bab_longdate($end,false);

			$this->remarks = isset($_POST['remarks']) ? stripslashes($_POST['remarks']) : '';
			$this->calurl = $GLOBALS['babUrlScript']."?tg=vacuser&idx=cal&idu=".$GLOBALS['BAB_SESS_USERID']."&popup=1";
			}


		function getnextright()
			{

			if (list(,$this->right) = each($this->rights))
				{
				$this->nbdays = isset($_POST['nbdays'.$this->right['id']]) ? $_POST['nbdays'.$this->right['id']] : 0;
				$this->totalval += $this->nbdays;
				return true;
				}
			else
				return false;

			}

		}

	$temp = new temp($daybegin, $monthbegin, $yearbegin,$dayend, $monthend, $yearend, $halfdaybegin, $halfdayend);
	$babBody->babecho(	bab_printTemplate($temp,"vacuser.html", "newvacation"));
	}


function viewCalendarByUser($id, $month, $year, $period = false)
	{
	global $babBody;

	class temp
		{
		var $entries = array();
		var $fullname;
		var $vacwaitingtxt;
		var $vacapprovedtxt;
		var $print;
		var $close;


		function temp($id, $month, $year, $period)
			{
			global $babMonths, $babDB;
			$this->month = $month;
			$this->year = $year;
			$this->iduser = $id;
			$this->period = $period;
			$this->fullname = bab_getUserName($id);
			$this->vacwaitingtxt = bab_translate("Waiting vacation request");
			$this->vacapprovedtxt = bab_translate("Approved vacation request");
			$this->print = bab_translate("Print");
			$this->close = bab_translate("Close");

			$this->t_previousmonth = bab_translate("Previous month");
			$this->t_previousyear = bab_translate("Previous year");
			$this->t_nextmonth = bab_translate("Next month");
			$this->t_nextyear = bab_translate("Next year");

			$this->t_nonworking = bab_translate("Non-working day");
			$this->t_weekend = bab_translate("Week-end");

			$urltmp = $GLOBALS['babUrlScript']."?tg=vacuser&idx=".$_REQUEST['idx']."&idu=".$this->iduser;
			if (!empty($_REQUEST['popup']))
				{
				$urltmp .= '&popup=1';
				$this->popup = true;
				}
			$this->previousmonth = $urltmp."&month=".date("n", mktime( 0,0,0, $month-1, 1, $year));
			$this->previousmonth .= "&year=".date("Y", mktime( 0,0,0, $month-1, 1, $year));
			$this->nextmonth = $urltmp."&month=". date("n", mktime( 0,0,0, $month+1, 1, $year));
			$this->nextmonth .= "&year=". date("Y", mktime( 0,0,0, $month+1, 1, $year));

			$this->previousyear = $urltmp."&month=".date("n", mktime( 0,0,0, $month, 1, $year-1));
			$this->previousyear .= "&year=".date("Y", mktime( 0,0,0, $month, 1, $year-1));
			$this->nextyear = $urltmp."&month=". date("n", mktime( 0,0,0, $month, 1, $year+1));
			$this->nextyear .= "&year=". date("Y", mktime( 0,0,0, $month, 1, $year+1));

			if( $month != 1 )
				{
				$dateb = $year."-".$month."-01";
				$datee = ($year+1)."-".date("n", mktime( 0,0,0, $month + 11, 1, $year))."-01";
				$this->yearname = ($year)."-".($year+1);
				}
			else
				{
				$dateb = $year."-01-01";
				$datee = $year."-12-01";
				$this->yearname = $year;
				}

			$res = $babDB->db_query("select * from ".BAB_VAC_ENTRIES_TBL." where id_user='".$this->iduser."' and status!='N' and (date_end >= '".$dateb."' or date_begin <='".$datee."')");
			while( $row = $babDB->db_fetch_array($res))
				{
				$this->entries[] = array('id'=> $row['id'], 'db'=> $row['date_begin'], 'de'=> $row['date_end'], 'st' => $row['status']);
				}

			$this->workdays = & explode(',',$GLOBALS['babBody']->icalendars->workdays);

			include_once $GLOBALS['babInstallPath']."utilit/nwdaysincl.php";
			$this->nonWorkingDays = bab_getNonWorkingDays($year);
			$this->nonWorkingDays = array_merge($this->nonWorkingDays, bab_getNonWorkingDays($year+1));

			}

		function getdayname()
			{
			global $babDays;
			static $i = 1;
			if( $i <= 31)
				{
				$this->dayname = sprintf('%02d',$i);
				$i++;
				return true;
				}
			else
				return false;
			}

		function getmonth()
			{
			static $i = 0;
			if( $i < 12)
				{
				$this->curyear = date("Y", mktime( 0,0,0, $this->month + $i, 1, $this->year));
				$this->curmonth = date("n", mktime( 0,0,0, $this->month + $i, 1, $this->year));
				$this->monthname = $GLOBALS['babShortMonths'][$this->curmonth];
				$this->totaldays = date("t", mktime(0,0,0,$this->month + $i,1,$this->year));
				$i++;
				return true;
				}
			else
				return false;
			}

		function getday()
			{
			static $d = 1;
			static $total = 0;
			if( $d <= 31)
				{
				if( $d <= $this->totaldays )
					{
					$this->daynumbername = $d;
					$curdate = mktime(0,0,0,$this->curmonth,$d,$this->curyear);
					$dayweek = date("w", $curdate);
					$this->titledate = bab_longdate($curdate,false);
					$this->date = sprintf("%04d-%02d-%02d", $this->curyear, $this->curmonth, $d);
					$this->weekend = !in_array($dayweek, $this->workdays);
					$this->nonworking = isset($this->nonWorkingDays[$this->date]);
					$this->bvac = false;
					$this->bwait = false;
					for( $k=0; $k < count($this->entries); $k++)
						{
						if( $this->date >= $this->entries[$k]['db'] && $this->date <= $this->entries[$k]['de'] )
							{
							if( $this->entries[$k]['st'] == "")
								$this->bwait = true;
							else
								$this->bvac = true;
							break;
							}
						}
					$this->noday = false;
					}
				else
					{
					$this->noday = true;
					$this->daynumbername = "";
					}
				$d++;
				return true;
				}
			else
				{
				$d = 1;
				return false;
				}
			}

		function printhtml()
			{
			$html = & bab_printTemplate($this,"vacuser.html", "calendarbyuser");

			if (isset($_REQUEST['popup']) && $_REQUEST['popup'] == 1)
				{
				include_once $GLOBALS['babInstallPath']."utilit/uiutil.php";
				$GLOBALS['babBodyPopup'] = new babBodyPopup();
				$GLOBALS['babBodyPopup']->title = $GLOBALS['babBody']->title;
				$GLOBALS['babBodyPopup']->msgerror = $GLOBALS['babBody']->msgerror;
				$GLOBALS['babBodyPopup']->babecho($html);
				printBabBodyPopup();
				die();
				}
			else
				{
				$GLOBALS['babBody']->babecho($html);
				}
			}

		}

	$temp = new temp($id, $month, $year, $period);
	$temp->printhtml();
	}


function period()
	{
	class ptemp
		{


		function ptemp()
			{
			global $babBody;
			$this->datebegin = $GLOBALS['babUrlScript']."?tg=month&callback=dateBegin&ymin=0&ymax=2";
			$this->datebegintxt = bab_translate("Begin date");
			$this->dateend = $GLOBALS['babUrlScript']."?tg=month&callback=dateEnd&ymin=0&ymax=2";
			$this->dateendtxt = bab_translate("End date");
			$this->vactype = bab_translate("Vacation type");
			$this->addvac = bab_translate("Request vacation");
			$this->remark = bab_translate("Remarks");
			$this->nbdaystxt = bab_translate("Quantity");
			$this->invaliddate = bab_translate("ERROR: End date must be older");
			$this->invaliddate = str_replace("'", "\'", $this->invaliddate);
			$this->invaliddate = str_replace('"', "'+String.fromCharCode(34)+'",$this->invaliddate);
			$this->invaliddate2 = bab_translate("Total days does'nt fit between dates");
			$this->invaliddate2 = str_replace("'", "\'", $this->invaliddate2);
			$this->invaliddate2 = str_replace('"', "'+String.fromCharCode(34)+'",$this->invaliddate2);
			$this->invalidentry = bab_translate("Invalid entry!  Only numbers are accepted or . !");
			$this->invalidentry = str_replace("'", "\'", $this->invalidentry);
			$this->invalidentry = str_replace('"', "'+String.fromCharCode(34)+'",$this->invalidentry);
			$this->invalidentry1 = bab_translate("Invalid entry");
			$this->invalidentry2 = bab_translate("Days must be multiple of 0.5");
			$this->invalidentry3 = bab_translate("The number of days exceed the total allowed");
			$this->totaltxt = bab_translate("Total");
			$this->balancetxt = bab_translate("Balance");
			$this->calendar = bab_translate("Planning");
			$this->totalval = 0;
			$this->maxallowed = 0;
			$this->db = $GLOBALS['babDB'];

			if( empty($_REQUEST['daybegin']))
				$this->daybegin = date("j");
			else
				$this->daybegin = $_REQUEST['daybegin'];
			$this->daysel = $this->daybegin;

			if( empty($_REQUEST['dayend']) )
				$this->dayend = date("j");
			else
				$this->dayend = $_REQUEST['dayend'];

			if( empty($_REQUEST['monthbegin']) )
				$this->monthbegin = date("n");
			else
				$this->monthbegin = $_REQUEST['monthbegin'];
			$this->monthsel = $this->monthbegin;

			if( empty($_REQUEST['monthend']) )
				$this->monthend = date("n");
			else
				$this->monthend = $_REQUEST['monthend'];

			if( empty($_REQUEST['yearbegin']) )
				$this->yearbegin = date("Y");
			else
				$this->yearbegin = date("Y")+ $_REQUEST['yearbegin']-1;

			$this->yearsel = $this->yearbegin - date("Y") + 1;

			if( empty($_REQUEST['yearend']))
				$this->yearend = date("Y");
			else
				$this->yearend = date("Y")+ $_REQUEST['yearend']-1;

			if( empty($_REQUEST['halfdaybegin']) )
				$this->halfdaybegin = 1;
			else
				$this->halfdaybegin = $_REQUEST['halfdaybegin'];
			$this->halfdaysel = $this->halfdaybegin;

			if( empty($_REQUEST['halfdayend']) )
				$this->halfdayend = 1;
			else
				$this->halfdayend = $_REQUEST['halfdayend'];


			$this->calurl = $GLOBALS['babUrlScript']."?tg=vacuser&idx=cal&idu=".$GLOBALS['BAB_SESS_USERID'];
			}


		


		function getnextday()
			{
			static $i = 1;

			if( $i <= date("t"))
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
				$this->daysel = $this->dayend;
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
				$this->monthsel = $this->monthend;
				$i = 1;
				return false;
				}

			}
		function getnextyear()
			{
			static $i = 0;
			if( $i < 3)
				{
				$this->yearid = $i+1;
				$this->yearidval = date("Y") + $i;
				if( $this->yearsel == $this->yearid )
					$this->selected = "selected";
				else
					$this->selected = "";
				$i++;
				return true;
				}
			else
				{
				$this->yearsel = $this->yearend - date("Y") + 1;
				$i = 0;
				return false;
				}

			}
		function getnexthalf()
			{
			global $babDayType;
			static $i = 1;
			static $count = 4;
			if( $i < $count)
				{
				$this->halfname = $babDayType[$i];
				$this->halfid = $i;
				if( $this->halfdaysel == $this->halfid )
					$this->selected = "selected";
				else
					$this->selected = "";
				$i++;
				return true;
				}
			else
				{
				$i = 1;
				$this->halfdaysel = $this->halfdayend;
				return false;
				}

			}

		}
		$temp = new ptemp();
		$GLOBALS['babBody']->babecho(bab_printTemplate($temp, "vacuser.html", "period"));
	}

function vedUnload()
	{
	class temp
		{
		var $message;
		var $close;
		var $redirecturl;

		function temp()
			{
			$this->message = bab_translate("Vacation entry has been updated");
			$this->close = bab_translate("Close");
			$this->redirecturl = $GLOBALS['babUrlScript']."?tg=vacuser&idx=lval";
			}
		}

	$temp = new temp();
	echo bab_printTemplate($temp,"vacuser.html", "vedunload");
	}

function addNewVacation($begin,$end, $halfdaybegin, $halfdayend, $remarks, $total)
{
	global $babBody, $babDB;
	$nbdays = array();


	$row = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_VAC_PERSONNEL_TBL." where id_user='".$GLOBALS['BAB_SESS_USERID']."'"));

	$res = $babDB->db_query("select ".BAB_VAC_RIGHTS_TBL.".id from ".BAB_VAC_RIGHTS_TBL." join ".BAB_VAC_COLL_TYPES_TBL." where ".BAB_VAC_RIGHTS_TBL.".id_type = ".BAB_VAC_COLL_TYPES_TBL.".id_type and ".BAB_VAC_COLL_TYPES_TBL.".id_coll='".$row['id_coll']."'");

	$ntotal = 0;
	while( $arr = $babDB->db_fetch_array($res))
	{
		$tmp = 'nbdays'.$arr['id'];
		if( isset($GLOBALS[$tmp]))
		{
			$nbd = $_POST[$tmp];
			if( !is_numeric($nbd) || $nbd < 0 )
				{
				$babBody->msgerror = bab_translate("You must specify a correct number days") ." !";
				return false;
				}
			
			if( $nbd > 0 )
			{
				$nbdays['id'][] = $arr['id'];
				$nbdays['val'][] = $nbd;
				$ntotal += $nbd;
			}
		}
	}

	if( $ntotal <= 0 || $ntotal != $total)
		{
		$babBody->msgerror = bab_translate("Incorrect total number of days") ." !";
		return false;
		}


	if( $begin > $end || ( $begin == $end && $halfdaybegin != $halfdayend ))
		{
		$babBody->msgerror = bab_translate("ERROR: End date must be older")." !";
		return false;
		}

	if( !bab_isMagicQuotesGpcOn())
		{
		$remarks = addslashes($remarks);
		}

	$babDB->db_query("insert into ".BAB_VAC_ENTRIES_TBL." (id_user, date_begin, date_end, day_begin, day_end, comment, date, idfai) values  ('" .$GLOBALS['BAB_SESS_USERID']. "', '" . $begin. "', '" . $end. "', '" . $halfdaybegin. "', '" . $halfdayend. "', '" . $remarks. "', curdate(), '0')");
	$id = $babDB->db_insert_id();

	for( $i = 0; $i < count($nbdays['id']); $i++)
		{
		$babDB->db_query("insert into ".BAB_VAC_ENTRIES_ELEM_TBL." (id_entry, id_type, quantity) values  ('" .$id. "', '" .$nbdays['id'][$i]. "', '" . $nbdays['val'][$i]. "')");
		}
	
	$idfai = makeFlowInstance($row['id_sa'], "vac-".$id);
	$babDB->db_query("update ".BAB_VAC_ENTRIES_TBL." set idfai='".$idfai."' where id='".$id."'");
	$nfusers = getWaitingApproversFlowInstance($idfai, true);
	notifyVacationApprovers($id, $nfusers);
	return true;
}


function listVacationRequests($pos)
{
	global $babBody;

	class temp
		{
		var $nametxt;
		var $urlname;
		var $url;
		var $editurl;
		var $begindatetxt;
		var $enddatetxt;
		var $quantitytxt;
		var $statustxt;
		var $begindate;
		var $enddate;
		var $quantity;
		var $status;
				
		var $arr = array();
		var $db;
		var $count;
		var $res;

		var $statarr;
		var $total;
		var $checkall;
		var $uncheckall;

		var $topurl;
		var $bottomurl;
		var $nexturl;
		var $prevurl;
		var $topname;
		var $bottomname;
		var $nextname;
		var $prevname;
		var $pos;

		var $entryid;

		function temp($pos)
			{
			$this->uncheckall = bab_translate("Uncheck all");
			$this->checkall = bab_translate("Check all");
			$this->nametxt = bab_translate("Fullname");
			$this->begindatetxt = bab_translate("Begin date");
			$this->enddatetxt = bab_translate("End date");
			$this->quantitytxt = bab_translate("Quantity");
			$this->statustxt = bab_translate("Status");
			$this->calendar = bab_translate("Planning");
			$this->calurl = $GLOBALS['babUrlScript']."?tg=vacuser&idx=cal&idu=".$GLOBALS['BAB_SESS_USERID']."&popup=1";
			$this->topurl = "";
			$this->bottomurl = "";
			$this->nexturl = "";
			$this->prevurl = "";
			$this->topname = "";
			$this->bottomname = "";
			$this->nextname = "";
			$this->prevname = "";
			$this->pos = $pos;
			$this->db = $GLOBALS['babDB'];
			$req = "".BAB_VAC_ENTRIES_TBL." where id_user='".$GLOBALS['BAB_SESS_USERID']."'";

			list($total) = $this->db->db_fetch_row($this->db->db_query("select count(*) as total from ".$req));
			if( $total > VAC_MAX_REQUESTS_LIST )
				{
				$tmpurl = $GLOBALS['babUrlScript']."?tg=vacuser&idx=lvreq&pos=";
				if( $pos > 0)
					{
					$this->topurl = $tmpurl."0";
					$this->topname = "&lt;&lt;";
					}

				$next = $pos - VAC_MAX_REQUESTS_LIST;
				if( $next >= 0)
					{
					$this->prevurl = $tmpurl.$next;
					$this->prevname = "&lt;";
					}

				$next = $pos + VAC_MAX_REQUESTS_LIST;
				if( $next < $total)
					{
					$this->nexturl = $tmpurl.$next;
					$this->nextname = "&gt;";
					if( $next + VAC_MAX_REQUESTS_LIST < $total)
						{
						$bottom = $total - VAC_MAX_REQUESTS_LIST;
						}
					else
						$bottom = $next;
					$this->bottomurl = $tmpurl.$bottom;
					$this->bottomname = "&gt;&gt;";
					}
				}

			$req .= " order by date desc";
			if( $total > VAC_MAX_REQUESTS_LIST)
				{
				$req .= " limit ".$pos.",".VAC_MAX_REQUESTS_LIST;
				}
			$this->res = $this->db->db_query("select * from ".$req);
			$this->count = $this->db->db_num_rows($this->res);
			$this->statarr = array(bab_translate("Waiting"), bab_translate("Accepted"), bab_translate("Refused"));
			}

		function getnext()
			{
			global $babDayType;
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $this->db->db_fetch_array($this->res);
				$this->url = $GLOBALS['babUrlScript']."?tg=vacuser&idx=morve&id=".$arr['id'];
				list($this->quantity) = $this->db->db_fetch_row($this->db->db_query("select sum(quantity) from ".BAB_VAC_ENTRIES_ELEM_TBL." where id_entry ='".$arr['id']."'"));
				$this->urlname = bab_getUserName($arr['id_user']);
				$this->begindate = bab_shortDate(bab_mktime($arr['date_begin']." 00:00:00"), false);
				if( $arr['day_begin'] != 1)
					$this->begindate .= " ". $babDayType[$arr['day_begin']];
				$this->enddate = bab_shortDate(bab_mktime($arr['date_end']." 00:00:00"), false);
				if( $arr['day_begin'] != 1)
					$this->enddate .= " ". $babDayType[$arr['day_end']];
				switch($arr['status'])
					{
					case 'Y':
						$this->status = $this->statarr[1];
						break;
					case 'N':
						$this->status = $this->statarr[2];
						break;
					default:
						$this->status = $this->statarr[0];
						break;
					}
				$i++;
				return true;
				}
			else
				return false;

			}
		}

	$temp = new temp($pos);
	$babBody->babecho(	bab_printTemplate($temp, "vacuser.html", "vrequestslist"));
	return $temp->count;
}

function viewVacationRequestDetail($id)
	{
	global $babBody;

	class temp
		{
		var $datebegintxt;
		var $datebegin;
		var $halfnamebegin;
		var $dateendtxt;
		var $dateend;
		var $halfnameend;
		var $nbdaystxt;
		var $typename;
		var $nbdays;
		var $totaltxt;
		var $totalval;
		var $confirm;
		var $refuse;
		var $fullname;
		var $commenttxt;
		var $comment;
		var $remarktxt;
		var $remark;
				
		var $arr = array();
		var $db;
		var $count;
		var $res;
		var $veid;
		var $wusers = array();

		var $statustxt;
		var $status;


		function temp($id)
			{
			global $babDayType;
			$this->datebegintxt = bab_translate("Begin date");
			$this->dateendtxt = bab_translate("End date");
			$this->nbdaystxt = bab_translate("Quantities");
			$this->totaltxt = bab_translate("Total");
			$this->statustxt = bab_translate("Status");
			$this->commenttxt = bab_translate("Description");
			$this->remarktxt = bab_translate("Comment");
			$this->db = $GLOBALS['babDB'];
			$row = $this->db->db_fetch_array($this->db->db_query("select * from ".BAB_VAC_ENTRIES_TBL." where id='".$id."'"));
			$this->datebegin = bab_strftime(bab_mktime($row['date_begin']." 00:00:00"), false);
			$this->halfnamebegin = $babDayType[$row['day_begin']];
			$this->dateend = bab_strftime(bab_mktime($row['date_end']." 00:00:00"), false);
			$this->halfnameend = $babDayType[$row['day_end']];
			$this->fullname = bab_getUserName($row['id_user']);
			$this->statarr = array(bab_translate("Waiting to be valiadte by"), bab_translate("Accepted"), bab_translate("Refused"));
			$this->comment = nl2br($row['comment']);
			$this->remark = nl2br($row['comment2']);
			switch($row['status'])
				{
				case 'Y':
					$this->status = $this->statarr[1];
					break;
				case 'N':
					$this->status = $this->statarr[2];
					break;
				default:
					$this->status = $this->statarr[0];
					$this->wusers = getWaitingApproversFlowInstance($row['idfai'] , false);
					break;
				}

			$req = "select * from ".BAB_VAC_ENTRIES_ELEM_TBL." where id_entry='".$id."'";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			$this->totalval = 0;
			$this->veid = $id;
			}

		function getnexttype()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $this->db->db_fetch_array($this->res);
				list($this->typename) = $this->db->db_fetch_row($this->db->db_query("select description from ".BAB_VAC_RIGHTS_TBL." where id ='".$arr['id_type']."'"));
				$this->nbdays = $arr['quantity'];
				$this->totalval += $this->nbdays;
				$i++;
				return true;
				}
			else
				return false;

			}

		function getnextuser()
			{
			static $i = 0;
			if( $i < count($this->wusers))
				{
				$this->fullname = bab_getUserName($this->wusers[$i]);
				$i++;
				return true;
				}
			else
				return false;

			}
		}

	$temp = new temp($id);
	echo bab_printTemplate($temp, "vacuser.html", "ventrydetail");
	return $temp->count;
	}

function test_period()
{
global $babBody;
$db = & $GLOBALS['babDB'];

if (!isset($_POST['daybegin']) || 
	!isset($_POST['monthbegin']) ||
	!isset($_POST['yearbegin']) || 
	!isset($_POST['halfdaybegin']) ||
	!isset($_POST['dayend']) || 
	!isset($_POST['monthend']) ||
	!isset($_POST['yearend']) || 
	!isset($_POST['halfdayend'])
	)
	{
	$babBody->msgerror = bab_translate("Error");
	return false;
	}

	$yearbegin = date("Y") + $_POST['yearbegin'] - 1;
	$yearend = date("Y") + $_POST['yearend'] - 1;

	$begin = mktime( 0,0,0,$_POST['monthbegin'], $_POST['daybegin'], $yearbegin);
	$end = mktime( 0,0,0,$_POST['monthend'], $_POST['dayend'], $yearend);

	if( $begin > $end || ( $begin == $end && $_POST['halfdaybegin'] != $_POST['halfdayend'] ))
		{
		$babBody->msgerror = bab_translate("ERROR: End date must be older")." !";
		return false;
		}

	$date_begin = sprintf("%04d-%02d-%02d", $yearbegin, $_POST['monthbegin'], $_POST['daybegin']);
	$date_end = sprintf("%04d-%02d-%02d", $yearend, $_POST['monthend'], $_POST['dayend']);

	$res = $db->db_query("SELECT * FROM ".BAB_VAC_ENTRIES_TBL." WHERE id_user='".$GLOBALS['BAB_SESS_USERID']."' AND ((date_begin BETWEEN '".$date_begin."' AND '".$date_end."' ) OR ( date_end BETWEEN '".$date_begin."' AND '".$date_end."'))");

	if ($db->db_num_rows($res) > 0)
		{
		$babBody->msgerror = bab_translate("ERROR: a request is allready defined on this period");
		return false;
		}

return true;
}

/* main */
$acclevel = bab_vacationsAccess();

if( count($acclevel) == 0)
	{
	$babBody->msgerror = bab_translate("Access denied");
	return;
	}

if( !isset($idx))
	$idx = "lvreq";

if (isset($_POST['action']))
{
switch ($_POST['action'])
	{
	case 'period':
		if (!test_period())
			$idx = 'period';
		break;

	case 'vacation_request':
		if(!addNewVacation($begin, $end, $halfdaybegin, $halfdayend, $remarks, $total))
		$idx = "vunew";
	else
		{
		Header("Location: ". $GLOBALS['babUrlScript']."?tg=vacuser&idx=lvreq");
		exit;
		}

	}
}



$res = $babDB->db_query("select ".BAB_VAC_RIGHTS_TBL.".* from ".BAB_VAC_RIGHTS_TBL." join ".BAB_VAC_USERS_RIGHTS_TBL." where ".BAB_VAC_RIGHTS_TBL.".active='Y' and ".BAB_VAC_USERS_RIGHTS_TBL.".id_user='".$GLOBALS['BAB_SESS_USERID']."' and ".BAB_VAC_USERS_RIGHTS_TBL.".id_right=".BAB_VAC_RIGHTS_TBL.".id");
$countt = $babDB->db_num_rows($res);

switch($idx)
	{
	case "cal":
		if( !isset($month))
			$month = Date("n");

		if( !isset($year))
			$year = Date("Y");

		viewCalendarByUser($idu, $month, $year);
		break;

	case "unload":
		vedUnload();
		exit;
		break;

	case "morve":
		viewVacationRequestDetail($id);
		exit;
		break;

	case "period":
		$babBody->addItemMenu("period", bab_translate("Request"), $GLOBALS['babUrlScript']."?tg=vacuser&idx=period");
		$babBody->addItemMenu("lvreq", bab_translate("Requests"), $GLOBALS['babUrlScript']."?tg=vacuser&idx=lvreq");

		$babBody->title = bab_translate("Request vacation");
		
		if( !isset($month))
			$month = Date("n");

		if( !isset($year))
			$year = Date("Y");

		viewCalendarByUser($GLOBALS['BAB_SESS_USERID'], $month, $year, true);
		period();
		break;

	case "vunew":
		$babBody->title = bab_translate("Request vacation");
		if( $acclevel['user'] == true )
			{
			requestVacation($_POST['daybegin'], $_POST['monthbegin'], $_POST['yearbegin'],$_POST['dayend'], $_POST['monthend'], $_POST['yearend'], $_POST['halfdaybegin'], $_POST['halfdayend']);
			if( $countt > 0 )
				$babBody->addItemMenu("vunew", bab_translate("Request"), $GLOBALS['babUrlScript']."?tg=vacuser&idx=vunew");
			$babBody->addItemMenu("lvreq", bab_translate("Requests"), $GLOBALS['babUrlScript']."?tg=vacuser&idx=lvreq");
			}
		else
			{
			$idx = "lvt";
			}
		if( $acclevel['manager'] == true)
			$babBody->addItemMenu("list", bab_translate("Management"), $GLOBALS['babUrlScript']."?tg=vacadm&idx=lvt");
		break;

	case "lvreq":
	default:
		$babBody->title = bab_translate("Vacation requests list");
		if( isset($acclevel['user']) && $acclevel['user'] == true )
			{
			if( !isset($pos)) $pos = 0;
			listVacationRequests($pos);
			if( $countt > 0 )
				$babBody->addItemMenu("period", bab_translate("Request"), $GLOBALS['babUrlScript']."?tg=vacuser&idx=period");
			$babBody->addItemMenu("lvreq", bab_translate("Requests"), $GLOBALS['babUrlScript']."?tg=vacuser&idx=lvreq");
			}
		else
			{
			$idx = "lvt";
			}
		if( isset($acclevel['manager']) && $acclevel['manager'] == true)
			$babBody->addItemMenu("list", bab_translate("Management"), $GLOBALS['babUrlScript']."?tg=vacadm&idx=lvt");
		break;
	}
$babBody->setCurrentItemMenu($idx);

?>
