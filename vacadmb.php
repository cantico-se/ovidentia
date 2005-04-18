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
include_once $babInstallPath."utilit/vacincl.php";


function listVacationPersonnel($pos, $cb)
	{
	global $babBody;
	class temp
		{
		var $fullname;
		var $urlname;
		var $url;
				
		var $fullnameval;

		var $arr = array();
		var $db;
		var $count;
		var $res;

		var $pos;
		var $selected;
		var $allselected;
		var $allurl;
		var $allname;

		function temp($pos, $cb)
			{
			$this->cb = $cb;
			$this->allname = bab_translate("All");
			$this->db = $GLOBALS['babDB'];

			if( strlen($pos) > 0 && $pos[0] == "-" )
				{
				$this->pos = strlen($pos)>1? $pos[1]: '';
				$this->ord = $pos[0];
				$req = "select ".BAB_USERS_TBL.".* from ".BAB_USERS_TBL." join ".BAB_VAC_PERSONNEL_TBL." where ".BAB_USERS_TBL.".id=".BAB_VAC_PERSONNEL_TBL.".id_user and ".BAB_USERS_TBL.".lastname like '".$this->pos."%' ";
				$req .= "order by ".BAB_USERS_TBL.".lastname, ".BAB_USERS_TBL.".firstname asc";
				$this->fullname = bab_translate("Lastname"). " " . bab_translate("Firstname");
				$this->fullnameurl = $GLOBALS['babUrlScript']."?tg=vacadmb&idx=browlp&chg=&pos=".$this->ord.$this->pos."&cb=".$this->cb;
				}
			else
				{
				$this->pos = $pos;
				$this->ord = "";
				$req = "select ".BAB_USERS_TBL.".* from ".BAB_USERS_TBL." join ".BAB_VAC_PERSONNEL_TBL." where ".BAB_USERS_TBL.".id=".BAB_VAC_PERSONNEL_TBL.".id_user and ".BAB_USERS_TBL.".firstname like '".$this->pos."%' ";
				$req .= "order by ".BAB_USERS_TBL.".firstname, ".BAB_USERS_TBL.".lastname asc";
				$this->fullname = bab_translate("Firstname"). " " . bab_translate("Lastname");
				$this->fullnameurl = $GLOBALS['babUrlScript']."?tg=vacadmb&idx=browlp&chg=&pos=".$this->ord.$this->pos."&cb=".$this->cb;
				}

			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);

			if( empty($this->pos))
				$this->allselected = 1;
			else
				$this->allselected = 0;
			$this->allurl = $GLOBALS['babUrlScript']."?tg=vacadmb&idx=browlp&pos="."&cb=".$this->cb;
			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$this->arr = $this->db->db_fetch_array($this->res);
				$this->firstlast = bab_composeUserName($this->arr['firstname'],$this->arr['lastname']);
				$this->firstlast = str_replace("'", "\'", $this->firstlast);
				$this->firstlast = str_replace('"', "'+String.fromCharCode(34)+'",$this->firstlast);
				if( $this->ord == "-" )
					$this->urlname = bab_composeUserName($this->arr['lastname'],$this->arr['firstname']);
				else
					$this->urlname = bab_composeUserName($this->arr['firstname'],$this->arr['lastname']);

				$this->userid = $this->arr['id'];
				$i++;
				return true;
				}
			else
				return false;

			}

		function getnextselect()
			{
			static $k = 0;
			static $t = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
			if( $k < 26)
				{
				$this->selectname = substr($t, $k, 1);
				$this->selecturl = $GLOBALS['babUrlScript']."?tg=vacadmb&idx=browlp&pos=".$this->ord.$this->selectname."&cb=".$this->cb;

				if( $this->pos == $this->selectname)
					$this->selected = 1;
				else 
					{
					if( $this->ord == "-" )
						{
						$req = "select ".BAB_USERS_TBL.".id from ".BAB_USERS_TBL." join ".BAB_VAC_PERSONNEL_TBL." where ".BAB_USERS_TBL.".id=".BAB_VAC_PERSONNEL_TBL.".id_user and ".BAB_USERS_TBL.".lastname like '".$this->selectname."%'";
						}
					else
						{
						$req = "select ".BAB_USERS_TBL.".id from ".BAB_USERS_TBL." join ".BAB_VAC_PERSONNEL_TBL." where ".BAB_USERS_TBL.".id=".BAB_VAC_PERSONNEL_TBL.".id_user and ".BAB_USERS_TBL.".firstname like '".$this->selectname."%'";
						}
					$res = $this->db->db_query($req);
					if( $this->db->db_num_rows($res) > 0 )
						$this->selected = 0;
					else
						$this->selected = 1;
					}
				$k++;
				return true;
				}
			else
				return false;

			}
		}

	$temp = new temp($pos, $cb);
	echo bab_printTemplate($temp, "vacadmb.html", "vpersonnellist");
	return $temp->count;
	}


function listVacationRequestsb($idstatus, $userid, $dateb, $datee, $vpos)
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

		var $usersbrowurl;
		var $datetxt;
		var $filteron;
		var $usertxt;
		var $begintxt;
		var $endtxt;
		var $userval;
		var $userid;
		var $dateb;
		var $datee;
		var $dateburl;
		var $dateeurl;
		var $topurl;
		var $bottomurl;
		var $nexturl;
		var $prevurl;
		var $topname;
		var $bottomname;
		var $nextname;
		var $prevname;
		var $pos;

		var $resettxt;

		var $entryid;
		var $alttxt;

		function temp($idstatus, $userid, $dateb, $datee, $vpos)
			{
			$this->uncheckall = bab_translate("Uncheck all");
			$this->checkall = bab_translate("Check all");
			$this->nametxt = bab_translate("Fullname");
			$this->begindatetxt = bab_translate("Begin date");
			$this->enddatetxt = bab_translate("End date");
			$this->quantitytxt = bab_translate("Quantity");
			$this->statustxt = bab_translate("Status");
			$this->datetxt = bab_translate("Date")." ( ".bab_translate("dd-mm-yyyy")." )";
			$this->filteron = bab_translate("Filter on");
			$this->usertxt = bab_translate("User");
			$this->begintxt = bab_translate("Begin");
			$this->endtxt = bab_translate("End");
			$this->resettxt = bab_translate("Reset");
			$this->alttxt = bab_translate("Modify");
			$this->t_edit = bab_translate("Modification");
			$this->topurl = "";
			$this->bottomurl = "";
			$this->nexturl = "";
			$this->prevurl = "";
			$this->topname = "";
			$this->bottomname = "";
			$this->nextname = "";
			$this->prevname = "";
			$this->db = & $GLOBALS['babDB'];
			$this->statarr = array(bab_translate(""), bab_translate("Accepted"), bab_translate("Refused"));
			$this->usersbrowurl = $GLOBALS['babUrlScript']."?tg=vacadmb&idx=browlp&cb=";
			$this->statarr = array(bab_translate("Waiting"), bab_translate("Accepted"), bab_translate("Refused"));
			$this->dateb = $dateb;
			$this->datee = $datee;
			$this->idstatus = $idstatus;
			$this->userid = $userid;
			$this->pos = $vpos;
			$this->userval = $userid != ""? bab_getUserName($userid): "";
			$aaareq = array();
			$req = "".BAB_VAC_ENTRIES_TBL;
			if( $idstatus != "" || $userid != "" || $dateb != "" || $datee != "")
				{
				$req .= " where ";

				if( $idstatus != "")
					{
					switch($idstatus)
						{
						case 0:
							$aaareq[] = "status=''"; break;
						case 1:
							$aaareq[] = "status='Y'"; break;
						case 2:
							$aaareq[] = "status='N'"; break;
						}
					}

				if( $userid != "")
					{
					$aaareq[] = "id_user='".$userid."'";
					}

				if( $dateb != "" )
					{
					$ar = explode("-", $dateb);
					$dateb = $ar[2]."-".$ar[1]."-".$ar[0];
					}

				if( $datee != "" )
					{
					$ar = explode("-", $datee);
					$datee = $ar[2]."-".$ar[1]."-".$ar[0];
					}

				if( $dateb != "" )
					{
					$aaareq[] = "date_begin >= '".$dateb."'";
					}
				if( $datee != "" )
					{
					$aaareq[] = "date_end <= '".$datee."'";
					}
				}

			if( sizeof($aaareq) > 0 )
				{
				if( sizeof($aaareq) > 1 )
					$req .= implode(' and ', $aaareq);
				else
					$req .= $aaareq[0];
				}
			$req .= " order by date desc";

			list($total) = $this->db->db_fetch_row($this->db->db_query("select count(*) as total from ".$req));
			if( $total > VAC_MAX_REQUESTS_LIST )
				{
				$urltmp = $GLOBALS['babUrlScript']."?tg=vacadmb&idx=lreq&idstatus=".$this->idstatus."&userid=".$this->userid."&dateb=".$this->dateb."&datee=".$this->datee."&vpos=";

				if( $vpos > 0)
					{
					$this->topurl = $urltmp."0";
					$this->topname = "&lt;&lt;";
					}

				$next = $vpos - VAC_MAX_REQUESTS_LIST;
				if( $next >= 0)
					{
					$this->prevurl = $urltmp.$next;
					$this->prevname = "&lt;";
					}

				$next = $vpos + VAC_MAX_REQUESTS_LIST;
				if( $next < $total)
					{
					$this->nexturl = $urltmp.$next;
					$this->nextname = "&gt;";
					if( $next + VAC_MAX_REQUESTS_LIST < $total)
						{
						$bottom = $total - VAC_MAX_REQUESTS_LIST;
						}
					else
						$bottom = $next;
					$this->bottomurl = $urltmp.$bottom;
					$this->bottomname = "&gt;&gt;";
					}
				}


			if( $total > VAC_MAX_REQUESTS_LIST)
				{
				$req .= " limit ".$vpos.",".VAC_MAX_REQUESTS_LIST;
				}

			$this->res = $this->db->db_query("select * from ".$req);
			$this->count = $this->db->db_num_rows($this->res);

			$this->dateburl = $GLOBALS['babUrlScript']."?tg=month&callback=dateBegin&ymin=0&ymax=3";
			$this->dateeurl = $GLOBALS['babUrlScript']."?tg=month&callback=dateEnd&ymin=0&ymax=3";
			}

		function getnext()
			{
			global $babDayType;
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $this->db->db_fetch_array($this->res);
				$this->url = $GLOBALS['babUrlScript']."?tg=vacadmb&idx=morvw&id=".$arr['id'];
				$this->editurl = $GLOBALS['babUrlScript']."?tg=vacadmb&idx=edvr&id=".$arr['id'];
				list($this->quantity) = $this->db->db_fetch_row($this->db->db_query("select sum(quantity) from ".BAB_VAC_ENTRIES_ELEM_TBL." where id_entry ='".$arr['id']."'"));
				$this->urlname = bab_getUserName($arr['id_user']);
				$this->begindate = bab_strftime(bab_mktime($arr['date_begin']." 00:00:00"), false);
				if( $arr['day_begin'] != 1)
					$this->begindate .= " ". $babDayType[$arr['day_begin']];
				$this->enddate = bab_strftime(bab_mktime($arr['date_end']." 00:00:00"), false);
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

		function getnextstatus()
			{
			static $i = 0;
			if( $i < count($this->statarr))
				{
				$this->statusid = $i;
				$this->statusname = $this->statarr[$i];
				if( $this->idstatus != "" && $i == $this->idstatus )
					$this->selected = "selected";
				else
					$this->selected = "";
				$i++;
				return true;
				}
			else
				return false;
			}
		}

	$temp = new temp($idstatus, $userid, $dateb, $datee, $vpos);
	$babBody->babecho(	bab_printTemplate($temp, "vacadmb.html", "vrequestslist"));
	return $temp->count;
}

function editVacationRequest($vrid)
{
	global $babBody;
	class temp
		{
		var $datebegin;
		var $dateend;
		var $vactype;
		var $addvac;

		var $daybeginid;
		var $monthbeginid;
		var $nbdaystxt;

		var $remark;

		var $db;
		var $res;
		var $count;
		
		var $daybegin;
		var $monthbegin;
		var $yearbegin;
		var $dayend;
		var $monthend;
		var $yearend;
		var $halfdaybegin;
		var $halfdayend;
		var $nbdays;
		var $remarks;

		var $daysel;
		var $monthsel;
		var $yearsel;
		var $halfdaysel;
		var $totaltxt;
		var $totalval;

		var $invaliddate;
		var $invaliddate2;
		var $invalidentry;
		var $invalidentry1;
		var $invalidentry2;
		var $iduser;
		var $deletetxt;

		function temp($id)
			{
			global $babBody;
			$this->vrid = $id;
			$this->datebegintxt = bab_translate("Begin date");
			$this->dateendtxt = bab_translate("End date");
			$this->vactype = bab_translate("Vacation type");
			$this->addvac = bab_translate("Update");
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
			$this->totaltxt = bab_translate("Total");
			$this->invalidentry1 = bab_translate("Invalid entry");
			$this->invalidentry2 = bab_translate("Days must be multiple of 0.5");
			$this->balancetxt = bab_translate("Balance");
			$this->deletetxt = bab_translate("Delete");
			$this->db = $GLOBALS['babDB'];
			$arr = $this->db->db_fetch_array($this->db->db_query("select * from ".BAB_VAC_ENTRIES_TBL." where id='".$id."'"));
			$this->iduser = $arr['id_user'];

			$rr1 = explode('-', $arr['date_begin']);
			$this->daybegin = $rr1[2];
			$this->daysel = $this->daybegin;

			$rr2 = explode('-', $arr['date_end']);
			$this->dayend = $rr2[2];

			$this->monthbegin = $rr1[1];
			$this->monthsel = $this->monthbegin;

			$this->monthend = $rr2[1];


			$this->halfdaybegin = $arr['day_begin'];
			$this->halfsel = $this->halfdaybegin;

			$this->halfdayend = $arr['day_end'];

			$this->remarks = $arr['comment'];
			

			$ymin = max(0, date("Y") - $rr1[0])+2;
			$ymax = max(0, $rr2[0] - date("Y"))+2;
			$this->datebegin = $GLOBALS['babUrlScript']."?tg=month&callback=dateBegin&ymin=".$ymin."&ymax=".$ymax."";
			$this->dateend = $GLOBALS['babUrlScript']."?tg=month&callback=dateEnd&ymin=".$ymin."&ymax=".$ymax."";
			$this->deltay = $ymax + $ymin + 1;
			$this->startyear = date("Y") - $ymin;

			$this->yearbegin = $this->startyear;
			$this->yearsel = $rr1[0] - $this->startyear + 1;

			$this->yearend = $this->startyear;
			$this->yearendsel = $rr2[0] - $this->startyear + 1;

			$this->res = $this->db->db_query("select * from ".BAB_VAC_ENTRIES_ELEM_TBL." where id_entry='".$id."'");
			$this->count = $this->db->db_num_rows($this->res);
			$this->totalval = 0;
			}


		function getnexttype()
			{
			static $i = 0;

			if( $i < $this->count)
				{
				$arr = $this->db->db_fetch_array($this->res);
				$row = $this->db->db_fetch_array($this->db->db_query("select id, description, quantity from ".BAB_VAC_RIGHTS_TBL." where id='".$arr['id_type']."'"));
				$this->typename = $row['description'];
				//$this->maxdays = $row['maxdays'];
				//$this->quantitydays = $row['quantity'];
				$this->nbdaysname = "nbdays".$arr['id'];
				$this->nbdays = $arr['quantity'];
				$this->totalval += $this->nbdays;

				$row2 = $this->db->db_fetch_array($this->db->db_query("select sum(quantity) as total from ".BAB_VAC_ENTRIES_ELEM_TBL." join ".BAB_VAC_ENTRIES_TBL." where ".BAB_VAC_ENTRIES_TBL.".id_user='".$this->iduser."' and ".BAB_VAC_ENTRIES_TBL.".status!='N' and ".BAB_VAC_ENTRIES_ELEM_TBL.".id_type='".$arr['id_type']."' and ".BAB_VAC_ENTRIES_ELEM_TBL.".id_entry=".BAB_VAC_ENTRIES_TBL.".id"));
				$qdp = isset($row2['total'])? $row2['total'] : 0;

				list($quant) = $this->db->db_fetch_row($this->db->db_query("select quantity from ".BAB_VAC_USERS_RIGHTS_TBL." where id_right='".$arr['id_type']."' and id_user='".$this->iduser."'"));
				if( $quant == '' )
					$quant = $row['quantity'];

				$this->quantitydays = $quant - $qdp;
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
			if( $i < $this->deltay)
				{
				$this->yearid = $i+1;
				$this->yearidval = $this->startyear + $i;
				if( $this->yearsel == $this->yearid )
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
				$this->yearsel = $this->yearendsel;
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
				if( $this->halfsel == $this->halfid )
					$this->selected = "selected";
				else
					$this->selected = "";
				$i++;
				return true;
				}
			else
				{
				$i = 1;
				$this->halfsel = $this->halfdayend;
				return false;
				}

			}

		}

	$temp = new temp($vrid);
	$babBody->babecho( bab_printTemplate($temp,"vacadmb.html", "editvacrequest"));
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
			$this->db = $GLOBALS['babDB'];
			$row = $this->db->db_fetch_array($this->db->db_query("select * from ".BAB_VAC_ENTRIES_TBL." where id='".$id."'"));
			$this->datebegin = bab_strftime(bab_mktime($row['date_begin']." 00:00:00"), false);
			$this->halfnamebegin = $babDayType[$row['day_begin']];
			$this->dateend = bab_strftime(bab_mktime($row['date_end']." 00:00:00"), false);
			$this->halfnameend = $babDayType[$row['day_end']];
			$this->fullname1 = bab_getUserName($row['id_user']);
			$this->statarr = array(bab_translate("Waiting to be valiadte by"), bab_translate("Accepted"), bab_translate("Refused"));
			$this->commenttxt = bab_translate("Description");
			$this->remarktxt = bab_translate("Additional detailed information ");
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
	echo bab_printTemplate($temp, "vacadmb.html", "ventrydetail");
	return $temp->count;
	}

function exportVacationRequests()
	{
	global $babBody, $babDB;
	class temp
		{
		var $datebegintxt;
		var $dateendtxt;
		var $dateformattxt;
		var $vactype;
		var $statustxt;
		var $dateburl;
		var $dateeurl;
		var $statarr;
		var $statusid;
		var $statusname;
		var $separatortxt;
		var $other;
		var $comma;
		var $tab;
		var $export;
		var $sepdectxt;

		function temp()
			{
			global $babDB;
			$this->datebegintxt = bab_translate("Begin date");
			$this->dateendtxt = bab_translate("End date");
			$this->dateformattxt = "( ".bab_translate("dd-mm-yyyy")." )";
			$this->statustxt = bab_translate("Status");
			$this->separatortxt = bab_translate("Separator");
			$this->other = bab_translate("Other");
			$this->comma = bab_translate("Comma");
			$this->tab = bab_translate("Tab");
			$this->export = bab_translate("Export");
			$this->sepdectxt = bab_translate("Decimal separator");

			$this->dateburl = $GLOBALS['babUrlScript']."?tg=month&callback=dateBegin&ymin=0&ymax=3";
			$this->dateeurl = $GLOBALS['babUrlScript']."?tg=month&callback=dateEnd&ymin=0&ymax=3";
			$this->statarr = array(bab_translate("Waiting"), bab_translate("Accepted"), bab_translate("Refused"));
			}

		function getnextstatus()
			{
			static $i = 0;
			if( $i < count($this->statarr))
				{
				$this->statusid = $i;
				$this->statusname = $this->statarr[$i];
				$i++;
				return true;
				}
			else
				return false;
			}
		}

	$temp = new temp();
	$babBody->babecho(bab_printTemplate($temp,"vacadmb.html", "reqexport"));
	}

function deleteVacationRequests($dateb, $userid)
	{
	global $babBody, $babDB;
	class tempa
		{
		var $datetxt;
		var $dateformattxt;
		var $delete;
		var $usertext;
		var $usersbrowurl;
		var $dateburl;

		function tempa($dateb, $userid)
			{
			global $babDB;
			$this->datetxt = bab_translate("End date");
			$this->dateformattxt = "( ".bab_translate("dd-mm-yyyy")." )";
			$this->delete = bab_translate("Delete");
			$this->usertext = bab_translate("User");
			$this->usersbrowurl = $GLOBALS['babUrlScript']."?tg=vacadma&idx=browt&idtype=&cb=";
			$this->dateburl = $GLOBALS['babUrlScript']."?tg=month&callback=dateBegin&ymin=10&ymax=0";
			if( $dateb != "" )
				$this->datebval = $dateb;
			else
				$this->datebval = "";
			if( $userid != "" )
				{
				$this->userval = bab_getUserName($userid);
				$this->userid =$userid;
				}
			else
				{
				$this->userval ="";
				$this->userid ="";
				}
			}
		}

	$temp = new tempa($dateb, $userid);
	$babBody->babecho(bab_printTemplate($temp,"vacadmb.html", "reqdelete"));
	}

function deleteInfoVacationRequests($dateb, $userid)
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

		function temp($dateb, $userid)
			{
			$this->message = bab_translate("Are you sure you want to remove the requests which finish before the following date ").$dateb;
			if( $userid == "" )
				$this->title = bab_getUserName("All users");
			else
				$this->title = bab_getUserName($userid);
			$this->warning = bab_translate("WARNING: This operation will delete vacations requests"). "!";
			$this->urlyes = $GLOBALS['babUrlScript']."?tg=vacadmb&idx=lreq&date=".$dateb."&userid=".$userid."&action=Yes";
			$this->yes = bab_translate("Yes");
			$this->urlno = $GLOBALS['babUrlScript']."?tg=vacadmb&idx=lreq";
			$this->no = bab_translate("No");
			}
		}

	$ret = true;
	if( $dateb == "" )
		{
		$ret = false;
		}

	$ar = explode("-", $dateb);
	if( count($ar) != 3 || !is_numeric($ar[0]) || !is_numeric($ar[1]) || !is_numeric($ar[2]))
		{
		$ret = false;
		}

	if( $ar[0] <= 0 || $ar[1] <= 0 || $ar[2] <= 0)
		{
		$ret = false;
		}

	if( !$ret )
		{
		$babBody->msgerror = bab_translate("You must provide a correct date");
		return false;
		}

	$temp = new temp($dateb, $userid);
	$babBody->babecho( bab_printTemplate($temp,"warning.html", "warningyesno"));
	return true;
	}

function deleteVacationRequest($vrid)
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

		function temp($vrid)
			{
			global $babDB;
			list($userid) = $babDB->db_fetch_array($babDB->db_query("select id_user from ".BAB_VAC_ENTRIES_TBL." where id='".$vrid."'"));
			$this->message = bab_translate("Are you sure you want to remove this request");
			$this->title = bab_getUserName($userid);
			$this->warning = bab_translate("WARNING: This operation will delete vacation request"). "!";
			$this->urlyes = $GLOBALS['babUrlScript']."?tg=vacadmb&idx=lreq&vrid=".$vrid."&action2=Yes";
			$this->yes = bab_translate("Yes");
			$this->urlno = $GLOBALS['babUrlScript']."?tg=vacadmb&idx=lreq";
			$this->no = bab_translate("No");
			}
		}

	$temp = new temp($vrid);
	$babBody->babecho( bab_printTemplate($temp,"warning.html", "warningyesno"));
	return true;
	}

function updateVacationRequest($daybegin, $monthbegin, $yearbegin,$dayend, $monthend, $yearend, $halfdaybegin, $halfdayend, $remarks, $total, $vrid, $startyear)
{
	global $babBody, $babDB;
	$nbdays = array();

	$row = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_VAC_PERSONNEL_TBL." where id_user='".$GLOBALS['BAB_SESS_USERID']."'"));

	$res = $babDB->db_query("select * from ".BAB_VAC_ENTRIES_ELEM_TBL." where id_entry='".$vrid."'");

	$ntotal = 0;
	while( $arr = $babDB->db_fetch_array($res))
	{
		if( isset($GLOBALS['nbdays'.$arr['id']]))
		{
			$nbd = $GLOBALS['nbdays'.$arr['id']];
			if( !is_numeric($nbd) || $nbd < 0 )
				{
				$babBody->msgerror = bab_translate("You must specify a correct number days") ." !";
				return false;
				}
			
			if( $nbd >= 0 )
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

	$begin = mktime( 0,0,0,$monthbegin, $daybegin, $startyear + $yearbegin - 1);
	$end = mktime( 0,0,0,$monthend, $dayend, $startyear + $yearend - 1);

	if( $begin > $end || ( $begin == $end && $halfdaybegin != $halfdayend ))
		{
		$babBody->msgerror = bab_translate("ERROR: End date must be older")." !";
		return false;
		}

	if( !bab_isMagicQuotesGpcOn())
		{
		$remarks = addslashes($remarks);
		}

	$b = sprintf("%04d-%02d-%02d", $startyear + $yearbegin - 1, $monthbegin, $daybegin);
	$e = sprintf("%04d-%02d-%02d", $startyear + $yearend - 1, $monthend, $dayend);

	$babDB->db_query("update ".BAB_VAC_ENTRIES_TBL." set date_begin='".$b."', date_end='".$e."', day_begin='".$halfdaybegin."', day_end='".$halfdayend."', comment='".$remarks."' where id='".$vrid."'");

	$res = $babDB->db_query("UPDATE ".BAB_CAL_EVENTS_TBL." SET start_date='".$b." 00:00:00', end_date='".$e." 23:59:59' where hash='V_".$vrid."'");


	for( $i = 0; $i < count($nbdays['id']); $i++)
		{
		if( $nbdays['val'][$i] > 0 )
			$babDB->db_query("update ".BAB_VAC_ENTRIES_ELEM_TBL." set quantity='".$nbdays['val'][$i]."' where id='".$nbdays['id'][$i]."'");
		else
			$babDB->db_query("delete from ".BAB_VAC_ENTRIES_ELEM_TBL." where id='".$nbdays['id'][$i]."'");
		}
	
	return true;
}

function doExportVacationRequests($dateb, $datee, $idstatus, $wsepar, $separ, $sepdec)
{
	global $babDB;
	$statarr = array(bab_translate("Waiting"), bab_translate("Accepted"), bab_translate("Refused"));

	switch($wsepar)
		{
		case "1":
			$separ = ",";
			break;
		case "2":
			$separ = "\t";
			break;
		default:
			if( empty($separ))
				$separ = ",";
			break;
		}

	$req = "select * from ".BAB_VAC_ENTRIES_TBL;
	if( count($idstatus) < 3 || $dateb != "" || $datee != "")
		{


		if( count($idstatus) < 3)
			{
			switch($idstatus)
				{
				case 0:
					$aaareq[] = "status=''"; break;
				case 1:
					$aaareq[] = "status='Y'"; break;
				case 2:
					$aaareq[] = "status='N'"; break;
				}
			}

		if( $dateb != "" )
			{
			$ar = explode("-", $dateb);
			$dateb = $ar[2]."-".$ar[1]."-".$ar[0];
			}

		if( $datee != "" )
			{
			$ar = explode("-", $datee);
			$datee = $ar[2]."-".$ar[1]."-".$ar[0];
			}

		if( $dateb != "" )
			{
			$aaareq[] = "date_begin >= '".$dateb."'";
			}

		if( $datee != "" )
			{
			$aaareq[] = "date_end <= '".$datee."'";
			}
		}

	if( sizeof($aaareq) > 0 )
		{
		$req .= " where ";
		if( sizeof($aaareq) > 1 )
			$req .= implode(' and ', $aaareq);
		else
			$req .= $aaareq[0];
		}
	$req .= " order by date desc";
	$res = $babDB->db_query($req);

	$output = "";
	$output .= bab_translate("Fullname").$separ;
	$output .= bab_translate("Begin date").$separ;
	$output .= bab_translate("End date").$separ;
	$output .= bab_translate("Vacation type").$separ;
	$output .= bab_translate("Quantity").$separ;
	$output .= bab_translate("Status")."\n";

	while( $row = $babDB->db_fetch_array($res))
	{
		$fullname = bab_getUserName($row['id_user']);
		$datb = bab_shortDate(bab_mktime($row['date_begin']." 00:00:00"), false);
		$date = bab_shortDate(bab_mktime($row['date_end']." 00:00:00"), false);


		$res2 = $babDB->db_query("select * from ".BAB_VAC_ENTRIES_ELEM_TBL." where id_entry='".$row['id']."'");
		while( $arr = $babDB->db_fetch_array($res2))
		{
			$output .= $fullname.$separ;
			$output .= $datb.$separ;
			$output .= $date.$separ;
			list($type) = $babDB->db_fetch_array($babDB->db_query("select description from ".BAB_VAC_RIGHTS_TBL." where id='".$arr['id_entry']."'"));
			$output .= $type.$separ;
			$output .= number_format($arr['quantity'], 1, $sepdec, '').$separ;
			switch($row['status'])
				{
				case 'Y':
					$status = $statarr[1];
					break;
				case 'N':
					$status = $statarr[2];
					break;
				default:
					$status = $statarr[0];
					break;
				}
			$output .= $status."\n";
		}
	}

	header("Content-Disposition: attachment; filename=\"".bab_translate("Vacation").".csv\""."\n");
	header("Content-Type: text/plain"."\n");
	header("Content-Length: ". strlen($output)."\n");
	header("Content-transfert-encoding: binary"."\n");
	print $output;
	exit;
}

function doDeleteVacationRequests($date, $userid)
{
	global $babDB;

	$ar = explode("-", $date);
	$dateb = sprintf("%04d-%02d-%02d", $ar[2], $ar[1], $ar[0]);

	$req = "select id from ".BAB_VAC_ENTRIES_TBL." where date_end <='".$dateb."'";
	if( $userid != "" )
		$req .= " and id_user='".$userid."'";

	$res = 	$babDB->db_query($req);
	while( $arr = $babDB->db_fetch_array($res))
	{
		doDeleteVacationRequest($arr['id']);
	}
}

function doDeleteVacationRequest($vrid)
{
	global $babDB;

	$res = $babDB->db_query("select id from ".BAB_CAL_EVENTS_TBL." where hash='V_".$vrid."'");
	while( $arr = $babDB->db_fetch_array($res))
		{
		$babDB->db_query("delete from ".BAB_CAL_EVENTS_TBL." where id='".$arr['id']."'");
		$babDB->db_query("delete from ".BAB_CAL_EVENTS_NOTES_TBL." where id_event='".$arr['id']."'");
		$babDB->db_query("delete from ".BAB_CAL_EVENTS_REMINDERS_TBL." where id_event='".$arr['id']."'");
		}
	$babDB->db_query("delete from ".BAB_CAL_EVENTS_TBL." where hash='V_".$vrid."'");
	$babDB->db_query("delete from ".BAB_VAC_ENTRIES_ELEM_TBL." where id_entry='".$vrid."'");
	$babDB->db_query("delete from ".BAB_VAC_ENTRIES_TBL." where id='".$vrid."'");
}

/* main */
$acclevel = bab_vacationsAccess();
if( !isset($acclevel['manager']) || $acclevel['manager'] != true)
	{
	$babBody->msgerror = bab_translate("Access denied");
	return;
	}

if( !isset($idx))
	$idx = "lreq";

if( isset($add) && $add == "modvr")
{
	if( isset($Submit))
	{
	if(!updateVacationRequest($daybegin, $monthbegin, $yearbegin,$dayend, $monthend, $yearend, $halfdaybegin, $halfdayend, $remarks, $total, $vrid, $styear))
		$idx = "vunew";
	}
	else if( isset($bdelete))
	{
		$idx = "delur";
	}
}
else if( isset($bexport))
{
	doExportVacationRequests($dateb, $datee, $idstatus, $wsepar, $separ, $sepdec);
}
else if( isset($action) && $action == "Yes")
	{
	doDeleteVacationRequests($date, $userid);
	}
else if( isset($action2) && $action2 == "Yes")
	{
	doDeleteVacationRequest($vrid);
	}

$babBody->addItemMenu("vacuser", bab_translate("Vacations"), $GLOBALS['babUrlScript']."?tg=vacuser");
$babBody->addItemMenu("menu", bab_translate("Management"), $GLOBALS['babUrlScript']."?tg=vacadm&idx=menu");

switch($idx)
	{
	case "morvw":
		viewVacationRequestDetail($id);
		exit;
		break;

	case "browlp":
		if( !isset($pos)) $pos ="";
		if( isset($chg))
		{
			if( strlen($pos) > 0 && $pos[0] == "-" )
				$pos = strlen($pos)>1? $pos[1]: '';
			else
				$pos = "-" .$pos;
		}
		listVacationPersonnel($pos, $cb);
		exit;
		break;

	case "edvr":
	default:
		if( !isset($pos)) $pos ="";
		if( !isset($idcol)) $idcol ="";
		if( !isset($idsa)) $idsa ="";
		$babBody->title = bab_translate("Edit request vacation");
		editVacationRequest($id);
		$babBody->addItemMenu("lreq", bab_translate("Requests"), $GLOBALS['babUrlScript']."?tg=vacadmb&idx=lreq");
		$babBody->addItemMenu("edvr", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=vacadmb&idx=edvr");
		break;

	case "reqx":
		if( !isset($pos)) $pos ="";
		if( !isset($idcol)) $idcol ="";
		if( !isset($idsa)) $idsa ="";
		$babBody->title = bab_translate("Export requests vacations");
		exportVacationRequests();
		$babBody->addItemMenu("lreq", bab_translate("Requests"), $GLOBALS['babUrlScript']."?tg=vacadmb&idx=lreq");
		$babBody->addItemMenu("reqx", bab_translate("Export"), $GLOBALS['babUrlScript']."?tg=vacadmb&idx=reqx");
		break;

	case "dreq":
		if( !isset($pos)) $pos ="";
		if( !isset($idcol)) $idcol ="";
		if( !isset($idsa)) $idsa ="";
		$babBody->title = bab_translate("Delete requests vacations");
		if( !isset($dateb)) $dateb ="";
		if( !isset($userid)) $userid ="";
		deleteVacationRequests($dateb, $userid);
		$babBody->addItemMenu("lreq", bab_translate("Requests"), $GLOBALS['babUrlScript']."?tg=vacadmb&idx=lreq");
		$babBody->addItemMenu("dreq", bab_translate("Delete"), $GLOBALS['babUrlScript']."?tg=vacadmb&idx=dreq");
		$babBody->addItemMenu("reqx", bab_translate("Export"), $GLOBALS['babUrlScript']."?tg=vacadmb&idx=reqx");
		break;

	case "delur":
		if( !isset($pos)) $pos ="";
		if( !isset($idcol)) $idcol ="";
		if( !isset($idsa)) $idsa ="";
		$babBody->title = bab_translate("Delete request vacation");
		deleteVacationRequest($vrid);
		$babBody->addItemMenu("lreq", bab_translate("Requests"), $GLOBALS['babUrlScript']."?tg=vacadmb&idx=lreq");
		$babBody->addItemMenu("delur", bab_translate("Delete"), $GLOBALS['babUrlScript']."?tg=vacadmb&idx=delur");
		$babBody->addItemMenu("reqx", bab_translate("Export"), $GLOBALS['babUrlScript']."?tg=vacadmb&idx=reqx");
		break;

	case "ddreq":
		if( !isset($pos)) $pos ="";
		if( !isset($idcol)) $idcol ="";
		if( !isset($idsa)) $idsa ="";
		$babBody->title = bab_translate("Delete requests vacations");
		if( !deleteInfoVacationRequests($dateb, $userid))
			deleteVacationRequests($dateb, $userid);
		$babBody->addItemMenu("lreq", bab_translate("Requests"), $GLOBALS['babUrlScript']."?tg=vacadmb&idx=lreq");
		$babBody->addItemMenu("ddreq", bab_translate("Delete"), $GLOBALS['babUrlScript']."?tg=vacadmb&idx=ddreq");
		$babBody->addItemMenu("reqx", bab_translate("Export"), $GLOBALS['babUrlScript']."?tg=vacadmb&idx=reqx");
		break;

	case "lreq":
	default:
		$babBody->title = bab_translate("Requests vacations list");
		if( !isset($pos)) $pos ="";
		if( !isset($idcol)) $idcol ="";
		if( !isset($idsa)) $idsa ="";
		if( !isset($datee)) $datee ="";
		if( !isset($dateb)) $dateb ="";
		if( !isset($idstatus)) $idstatus ="";
		if( !isset($userid)) $userid ="";
		if( !isset($vpos)) $vpos =0;
		listVacationRequestsb($idstatus, $userid, $dateb, $datee, $vpos);
		$babBody->addItemMenu("lreq", bab_translate("Requests"), $GLOBALS['babUrlScript']."?tg=vacadmb&idx=lreq");
		$babBody->addItemMenu("dreq", bab_translate("Delete"), $GLOBALS['babUrlScript']."?tg=vacadmb&idx=dreq");
		$babBody->addItemMenu("reqx", bab_translate("Export"), $GLOBALS['babUrlScript']."?tg=vacadmb&idx=reqx");
		break;
	}
$babBody->setCurrentItemMenu($idx);
?>
