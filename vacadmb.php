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


/**
* @internal SEC1 PR 28/02/2007 FULL
*/


include_once "base.php";
include_once $babInstallPath."utilit/afincl.php";
include_once $babInstallPath."utilit/vacincl.php";


function listVacationRequestsb()
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
		var $altbg = true;

		function temp()
			{
			
			
			$idstatus = bab_rp('idstatus', '');
			$userid = (int) bab_rp('userid');
			$dateb = bab_rp('dateb');
			$datee = bab_rp('datee');
			$vpos = (int) bab_rp('vpos', 0);
			
			
			include_once $GLOBALS['babInstallPath']."utilit/urlincl.php";
			
			global $babDB;
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
			$this->t_delete = bab_translate("Delete");

			$this->t_first_page = bab_translate("First page");
			$this->t_previous_page = bab_translate("Previous page");
			$this->t_next_page = bab_translate("Next page");
			$this->t_last_page = bab_translate("Last page");

			$this->topurl = "";
			$this->bottomurl = "";
			$this->nexturl = "";
			$this->prevurl = "";

			$this->t_position = '';

			$this->statarr = array('', bab_translate("Accepted"), bab_translate("Refused"));
			$this->statarr = array(bab_translate("Waiting"), bab_translate("Accepted"), bab_translate("Refused"));
			$this->dateb = $dateb;
			$this->datee = $datee;
			$this->idstatus = $idstatus;
			$this->userid = $userid;
			$this->pos = $vpos;
			$this->userval = $userid != "" ? bab_toHtml(bab_getUserName($userid)) : "";
			$aaareq = array();
			
			$req = BAB_VAC_ENTRIES_TBL.' e, '.BAB_USERS_TBL.' u WHERE ';
			
			$aaareq[] = 'u.id=e.id_user';
			
			if( $idstatus != "" || $userid != "" || $dateb != "" || $datee != "")
				{

				if( $idstatus != "")
					{
					switch($idstatus)
						{
						case 0:
							$aaareq[] = "e.status=''"; break;
						case 1:
							$aaareq[] = "e.status='Y'"; break;
						case 2:
							$aaareq[] = "e.status='N'"; break;
						}
					}

				if( $userid != "")
					{
					$aaareq[] = "e.id_user='".$babDB->db_escape_string($userid)."'";
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
					$aaareq[] = "e.date_begin >= '".$babDB->db_escape_string($dateb)."'";
					}
				if( $datee != "" )
					{
					$aaareq[] = "e.date_end <= '".$babDB->db_escape_string($datee)."'";
					}
				}

			if( sizeof($aaareq) > 0 )
				{
				if( sizeof($aaareq) > 1 )
					$req .= implode(' AND ', $aaareq);
				else
					$req .= $aaareq[0];
				}
				
				
			$orderby = bab_rp('orderby', 'begin');
				
			$url = bab_url::request('tg', 'idx', 'idstatus', 'userid', 'dateb', 'datee', 'vpos', 'orderby');
			
			$this->orderby = bab_toHtml($orderby);
				
			$this->orderbyname = bab_url::mod($url, 'orderby', 'name');
			$this->orderbybegin	= bab_url::mod($url, 'orderby', 'begin');
			
			
			switch($orderby) {
			
				case 'begin':
					$req .= " ORDER BY e.date desc, u.lastname, u.firstname";
					break;
					
				case 'name':
					$req .= " ORDER BY u.lastname, u.firstname, e.date desc";
					break;
			}
			
				
				
			

			list($total) = $babDB->db_fetch_row($babDB->db_query("select count(*) as total from ".$req));
			if( $total > VAC_MAX_REQUESTS_LIST )
				{

				$page_number = 1 + ($this->pos / VAC_MAX_REQUESTS_LIST);
				$page_total = 1 + ($total / VAC_MAX_REQUESTS_LIST);
				$this->t_position = bab_toHtml(sprintf(bab_translate("Page %d/%d"), $page_number,$page_total));

				if( $vpos > 0)
					{
					$this->topurl = bab_toHtml(bab_url::mod($url, 'vpos', 0));
					}

				$next = $vpos - VAC_MAX_REQUESTS_LIST;
				if( $next >= 0)
					{
					$this->prevurl = bab_toHtml(bab_url::mod($url, 'vpos', $next));
					}

				$next = $vpos + VAC_MAX_REQUESTS_LIST;
				if( $next < $total)
					{
					$this->nexturl = bab_toHtml(bab_url::mod($url, 'vpos', $next));
					if( $next + VAC_MAX_REQUESTS_LIST < $total)
						{
						$bottom = $total - VAC_MAX_REQUESTS_LIST;
						}
					else
						$bottom = $next;
					$this->bottomurl = bab_toHtml(bab_url::mod($url, 'vpos', $bottom));
					}
				}


			if( $total > VAC_MAX_REQUESTS_LIST)
				{
				$req .= " limit ".$vpos.",".VAC_MAX_REQUESTS_LIST;
				}

			$this->res = $babDB->db_query("select e.*, u.lastname, u.firstname from ".$req);
			$this->count = $babDB->db_num_rows($this->res);
			}

		function getnext()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->count)
				{
				$this->altbg = !$this->altbg;
				$arr = $babDB->db_fetch_array($this->res);
				$this->url = bab_toHtml($GLOBALS['babUrlScript']."?tg=vacadmb&idx=morvw&id=".$arr['id']);
				$this->editurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=vacadmb&idx=edvr&id=".$arr['id']);
				$url = "?tg=vacadmb&idx=lreq";
				$this->urldelete = bab_toHtml($GLOBALS['babUrlScript']."?tg=vacuser&idx=delete&id_entry=".$arr['id']."&from=".urlencode($url));
				list($this->quantity) = $babDB->db_fetch_row($babDB->db_query("select sum(quantity) from ".BAB_VAC_ENTRIES_ELEM_TBL." where id_entry =".$babDB->quote($arr['id'])));
				$this->urlname		= bab_toHtml($arr['lastname'].' '.$arr['firstname']);
				$this->begindate	= bab_toHtml(bab_vac_shortDate(bab_mktime($arr['date_begin'])));
				$this->enddate		= bab_toHtml(bab_vac_shortDate(bab_mktime($arr['date_end'])));

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
				$this->statusname = bab_toHtml($this->statarr[$i]);
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

	$temp = new temp();
	$babBody->addJavascriptFile($GLOBALS['babInstallPath'].'scripts/bab_dialog.js');
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
			global $babBody, $babDB;
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

			$arr = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_VAC_ENTRIES_TBL." where id='".$babDB->db_escape_string($id)."'"));
			$this->iduser = $arr['id_user'];

			
			include_once $GLOBALS['babInstallPath']."utilit/dateTime.php";
			

			$date_begin = BAB_DateTime::fromIsoDateTime($arr['date_begin']);
			$date_end	= BAB_DateTime::fromIsoDateTime($arr['date_end']);
			
			
			$this->daybegin		= $date_begin->getDayOfMonth();
			$this->daysel		= $this->daybegin;

			

			$this->monthbegin	= $date_begin->getMonth();
			$this->monthsel		= $this->monthbegin;
			
			$this->yearbegin 	= $date_begin->getYear();
			$this->yearsel 		= $this->yearbegin;
			$this->timestampbegin	= $date_begin->getTimeStamp();
			$this->timestampsel	= $this->timestampbegin;
			
			
			$this->dayend		= $date_end->getDayOfMonth();

			$this->monthend		= $date_end->getMonth();
			
			$this->yearend 		= $date_end->getYear();
			$this->yearendsel 	= $this->yearend;
			$this->timestampend	= $date_end->getTimeStamp();


			$this->halfdaybegin	= 'am' === date('a', $date_begin->getTimeStamp()) ? 0 : 1;
			$this->halfsel		= $this->halfdaybegin;

			$this->halfdayend	= 'am' === date('a', $date_end->getTimeStamp()) ? 0 : 1;

			$this->remarks		= $arr['comment'];
			
			$this->startyear = $this->yearbegin - 5;

			$this->res = $babDB->db_query("select * from ".BAB_VAC_ENTRIES_ELEM_TBL." where id_entry=".$babDB->quote($id));
			$this->count = $babDB->db_num_rows($this->res);
			$this->totalval = 0;

			$this->dayType = array(bab_translate("Morning"), bab_translate("Afternoon"));
			
			$babBody->addJavascriptFile($GLOBALS['babInstallPath'].'scripts/bab_dialog.js');
			}


		function getnexttype()
			{
			static $i = 0;
			global $babDB;
			if( $i < $this->count)
				{
				$arr = $babDB->db_fetch_array($this->res);
				$row = $babDB->db_fetch_array($babDB->db_query("select id, description, quantity from ".BAB_VAC_RIGHTS_TBL." where id='".$babDB->db_escape_string($arr['id_right'])."'"));
				$this->typename = $row['description'];
				$this->nbdaysname = "nbdays".$arr['id'];
				$this->nbdays = $arr['quantity'];
				$this->totalval += $this->nbdays;

				$row2 = $babDB->db_fetch_array($babDB->db_query("select sum(quantity) as total from ".BAB_VAC_ENTRIES_ELEM_TBL." ee
				join ".BAB_VAC_ENTRIES_TBL." e 
				where e.id_user='".$babDB->db_escape_string($this->iduser)."' 
					and e.status!='N' 
					and ee.id_right='".$babDB->db_escape_string($arr['id_right'])."' 
					and ee.id_entry=e.id"));

				$qdp = isset($row2['total'])? $row2['total'] : 0;

				list($quant) = $babDB->db_fetch_row($babDB->db_query("select quantity from ".BAB_VAC_USERS_RIGHTS_TBL." where id_right='".$babDB->db_escape_string($arr['id_right'])."' and id_user='".$babDB->db_escape_string($this->iduser)."'"));
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

			if( $i <= date('t', $this->timestampsel))
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
				$this->timestampsel = $this->timestampend;
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
			if( $i < 20)
				{
				$this->yearidval = $this->startyear + $i;
				if( $this->yearsel == $this->yearidval )
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
			static $i = 0;
			if( $i < 2)
				{
				$this->halfname = $this->dayType[$i];
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
				$i = 0;
				$this->halfsel = $this->halfdayend;
				return false;
				}

			}

		}

	$temp = new temp($vrid);
	$babBody->babecho( bab_printTemplate($temp,"vacadmb.html", "editvacrequest"));
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
			$this->t_yes = bab_translate("Yes");
			$this->t_no = bab_translate("No");
			$this->t_users_without_requests = bab_translate("Include uers without requests on the period");

			$this->dateburl = $GLOBALS['babUrlScript']."?tg=month&callback=dateBegin&ymin=6&ymax=3";
			$this->dateeurl = $GLOBALS['babUrlScript']."?tg=month&callback=dateEnd&ymin=6&ymax=3";
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
			list($userid) = $babDB->db_fetch_array($babDB->db_query("select id_user from ".BAB_VAC_ENTRIES_TBL." where id='".$babDB->db_escape_string($vrid)."'"));
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

function updateVacationRequest($daybegin, $monthbegin, $yearbegin,$dayend, $monthend, $yearend, $halfdaybegin, $halfdayend, $remarks, $total, $vrid)
{
	global $babBody, $babDB;
	$nbdays = array();

	$res = $babDB->db_query("select * from ".BAB_VAC_ENTRIES_ELEM_TBL." where id_entry='".$babDB->db_escape_string($vrid)."'");

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

	$begin_hour	= 0;
	$begin_min	= 0;
	$begin_sec	= 0;

	$end_hour	= 23;
	$end_min	= 59;
	$end_sec	= 59;

	if (1 == $halfdaybegin) {
		$begin_hour = 12;
		$begin_min	= 0;
		$begin_sec	= 0;
	}
	
	if (0 == $halfdayend) {
		$end_hour	= 11;
		$end_min	= 59;
		$end_sec	= 59;
	}


	$begin = mktime( $begin_hour, $begin_min, $begin_sec, $monthbegin, $daybegin, $yearbegin);
	$end = mktime( $end_hour,$end_min, $end_sec,$monthend, $dayend, $yearend);

	if( $begin >= $end) {
		$babBody->msgerror = bab_translate("ERROR: End date must be older")." !";
		return false;
	}
	
	$res = $babDB->db_query("
		SELECT 
			date_begin,
			date_end,
			id_user 
		FROM ".BAB_VAC_ENTRIES_TBL." 
		WHERE 
			id='".$babDB->db_escape_string($vrid)."'
		");
		
	$old = $babDB->db_fetch_assoc($res);
	
	$old_begin = bab_mktime($old['date_begin']);
	$old_end = bab_mktime($old['date_end']);


	$b = date('Y-m-d H:i:s', $begin);
	$e = date('Y-m-d H:i:s', $end);

	$babDB->db_query("
		update ".BAB_VAC_ENTRIES_TBL." SET 
			date_begin	= '".$babDB->db_escape_string($b)."', 
			date_end	= '".$babDB->db_escape_string($e)."',  
			comment		= '".$babDB->db_escape_string($remarks)."' 
		where 
			id='".$babDB->db_escape_string($vrid)."'
		");


	for( $i = 0; $i < count($nbdays['id']); $i++)
		{
		if( $nbdays['val'][$i] > 0 ) {
			$babDB->db_query("update ".BAB_VAC_ENTRIES_ELEM_TBL." set quantity='".$babDB->db_escape_string($nbdays['val'][$i])."' where id='".$babDB->db_escape_string($nbdays['id'][$i])."'");
		}
		else {
			$babDB->db_query("delete from ".BAB_VAC_ENTRIES_ELEM_TBL." where id='".$babDB->db_escape_string($nbdays['id'][$i])."'");
		}
	}
	
	
	$period_begin	= $old_begin 	< $begin 	? $old_begin 	: $begin;
	$period_end 	= $old_end 		> $end 		? $old_end 		: $end;
	

	include_once $GLOBALS['babInstallPath']."utilit/eventperiod.php";
	$event = new bab_eventPeriodModified($period_begin, $period_end, $old['id_user']);
	$event->types = BAB_PERIOD_VACATION;
	bab_fireEvent($event);
	
	return true;
}

function doExportVacationRequests($dateb, $datee, $idstatus, $wsepar, $separ, $sepdec, $users_without_requests)
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

	$date_begin = '';
	$date_end = '';

	$req = "SELECT 
				e.id, 
				e.id_user, 
				UNIX_TIMESTAMP(e.date_begin) date_begin, 
				UNIX_TIMESTAMP(e.date_end) date_end,
				u.firstname,
				u.lastname,
				e.status 
			FROM 
				".BAB_VAC_ENTRIES_TBL." e,".BAB_USERS_TBL." u";
	if( count($idstatus) < 3 || $dateb != "" || $datee != "")
		{

		if (count($idstatus) < 3)
			{
			$tmp = array();
			if (in_array(0,$idstatus))
				$tmp[] = "e.status=''";
			if (in_array(1,$idstatus))
				$tmp[] = "e.status='Y'";
			if (in_array(2,$idstatus))
				$tmp[] = "e.status='N'";

			$aaareq[] = '('.implode(' OR ', $tmp).')';
			}

		if( $dateb != "" )
			{
			$ar = explode("-", $dateb);
			$dateb = $ar[2]."-".$ar[1]."-".$ar[0];
			$date_begin = bab_shortDate(bab_mktime($dateb), false);
			}

		if( $datee != "" )
			{
			$ar = explode("-", $datee);
			$datee = $ar[2]."-".$ar[1]."-".$ar[0];
			$date_end = bab_shortDate(bab_mktime($datee), false);
			}

		if( $dateb != "" && $datee != "" )
			{
			$aaareq[] = "((e.date_end >= '".$dateb."' AND e.date_begin < '".$dateb."') 
						OR (e.date_begin <= '".$datee."' AND e.date_end > '".$datee."') 
						OR (e.date_end <= '".$datee." 23:59:59' AND e.date_begin >= '".$dateb."'))";
			}
		}

	$aaareq[] = "e.id_user = u.id";

	$req .= " WHERE ";
	if( sizeof($aaareq) > 1 )
		$req .= implode(' and ', $aaareq);
	else
		$req .= $aaareq[0];
	
	

	function arr_csv(&$value)
	{
		$value = str_replace("\n"," ",$value);
		$value = str_replace('"',"'",$value);
		$value = '"'.$value.'"';
	}

	$output = "";
	$line = array();
	$types = array();
	$users_with_requests = array();

	$line[] = bab_translate("lastname");
	$line[] = bab_translate("firstname");
	$line[] = bab_translate("Begin date");
	$line[] = bab_translate("End date");
	$line[] = bab_translate("Status");
	$line[] = bab_translate("Quantity");

	$res = $babDB->db_query("SELECT id,name FROM ".BAB_VAC_TYPES_TBL."");
	while ($arr = $babDB->db_fetch_array($res))
		{
		$line[] = $arr['name'];
		$types[] = $arr['id'];
		}

	array_walk($line, 'arr_csv');
	$output .= implode($separ,$line)."\n";
	
	$req .= " ORDER BY e.date DESC";
	$res = $babDB->db_query($req);

	while( $row = $babDB->db_fetch_array($res))
	{
		$users_with_requests[] = $row['id_user'];
		
		$line = array();
		$line[] = $row['firstname'];
		$line[] = $row['lastname'];
		$line[] = bab_shortDate($row['date_begin'], false);
		$line[] = bab_shortDate($row['date_end'], false);

		switch($row['status'])
			{
			case 'Y':
				$line[] = $statarr[1];
				break;
			case 'N':
				$line[] = $statarr[2];
				break;
			default:
				$line[] = $statarr[0];
				break;
			}

		

		$entry_type = array();
		$sum = 0;
		$res2 = $babDB->db_query("select SUM(e.quantity) quantity,r.id_type from ".BAB_VAC_ENTRIES_ELEM_TBL." e,".BAB_VAC_RIGHTS_TBL." r where e.id_entry='".$babDB->db_escape_string($row['id'])."' AND r.id=e.id_right GROUP BY r.id_type");
		while( $arr = $babDB->db_fetch_array($res2))
		{
			$entry_type[$arr['id_type']] = number_format($arr['quantity'], 1, $sepdec, '');
			$sum += $arr['quantity'];
		}

		$line[] = number_format($sum, 1, $sepdec, '');

		foreach($types as $type)
		{
		if (isset($entry_type[$type]))
			{
			$line[] = $entry_type[$type];
			}
		else $line[] = 0;
		}

	array_walk($line, 'arr_csv');
	$output .= implode($separ,$line)."\n";
	}

	if ($users_without_requests)
		{
		$req = "SELECT u.firstname, u.lastname FROM ".BAB_VAC_PERSONNEL_TBL." p,".BAB_USERS_TBL." u WHERE p.id_user=u.id ";
		if (count($users_with_requests) > 0)
			{
			$req .= " AND p.id_user NOT IN(".$babDB->quote($users_with_requests).")";
			}
		$res = $babDB->db_query($req);

		while ($arr = $babDB->db_fetch_array($res))
			{
			$line = array();
			$line[] = $arr['firstname'];
			$line[] = $arr['lastname'];
			$line[] = $date_begin;
			$line[] = $date_end;
			$line[] = '';
			for ($i = 0; $i <= count($types) ; $i++)
				$line[] = 0;
			
			array_walk($line, 'arr_csv');
			$output .= implode($separ,$line)."\n";
			}
		}

	header("Content-Disposition: attachment; filename=\"".bab_translate("Vacation").".csv\""."\n");
	header("Content-Type: text/plain"."\n");
	header("Content-Length: ". mb_strlen($output)."\n");
	header("Content-transfert-encoding: binary"."\n");
	print $output;
	exit;
}

function doDeleteVacationRequests($date, $userid)
{
	global $babDB;

	$ar = explode("-", $date);
	$dateb = sprintf("%04d-%02d-%02d", $ar[2], $ar[1], $ar[0]);

	$req = "SELECT id, idfai FROM ".BAB_VAC_ENTRIES_TBL." WHERE date_end <= ".$babDB->quote($dateb);
	if( $userid != "" )
		$req .= " and id_user=".$babDB->quote($userid);

	$res = 	$babDB->db_query($req);
	while( $arr = $babDB->db_fetch_array($res))
	{
		if ($arr['idfai'] > 0) {
			deleteFlowInstance($arr['idfai']);
			}
		doDeleteVacationRequest($arr['id']);
	}
}

function doDeleteVacationRequest($vrid)
{
	global $babDB;

	$res = $babDB->db_query("SELECT id_user FROM ".BAB_VAC_ENTRIES_TBL." where id=".$babDB->quote($vrid));
	$arr = $babDB->db_fetch_assoc($res);
	bab_vac_clearUserCalendar($arr['id_user']);

	$babDB->db_query("delete from ".BAB_VAC_ENTRIES_ELEM_TBL." where id_entry='".$babDB->db_escape_string($vrid)."'");
	$babDB->db_query("delete from ".BAB_VAC_ENTRIES_TBL." where id='".$babDB->db_escape_string($vrid)."'");
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
	if(!updateVacationRequest($daybegin, $monthbegin, $yearbegin,$dayend, $monthend, $yearend, $halfdaybegin, $halfdayend, $remarks, $total, $vrid))
		$idx = "vunew";
	}
	else if( isset($bdelete))
	{
		$idx = "delur";
	}
}
else if( isset($_POST['bexport']))
{
	doExportVacationRequests($_POST['dateb'], $_POST['datee'], $_POST['idstatus'], $_POST['wsepar'], $_POST['separ'], $_POST['sepdec'], $_POST['users_without_requests']);
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


		
$pos = bab_rp('pos');
$idcol = bab_rp('idcol');
$idsa = bab_rp('idsa');		

switch($idx)
	{
	case "morvw":
		bab_viewVacationRequestDetail(bab_rp('id'));
		exit;
		break;

	case "edvr":
	default:

		$babBody->title = bab_translate("Edit request vacation");
		editVacationRequest($id);
		$babBody->addItemMenu("lreq", bab_translate("Requests"), $GLOBALS['babUrlScript']."?tg=vacadmb&idx=lreq");
		$babBody->addItemMenu("edvr", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=vacadmb&idx=edvr");
		break;

	case "reqx":

		$babBody->title = bab_translate("Export requests vacations");
		exportVacationRequests();
		$babBody->addItemMenu("lreq", bab_translate("Requests"), $GLOBALS['babUrlScript']."?tg=vacadmb&idx=lreq");
		$babBody->addItemMenu("reqx", bab_translate("Export"), $GLOBALS['babUrlScript']."?tg=vacadmb&idx=reqx");
		break;

	case "dreq":

		$babBody->title = bab_translate("Delete requests vacations");
		deleteVacationRequests(bab_rp('dateb'), bab_rp('userid'));
		$babBody->addItemMenu("lreq", bab_translate("Requests"), $GLOBALS['babUrlScript']."?tg=vacadmb&idx=lreq");
		$babBody->addItemMenu("dreq", bab_translate("Delete"), $GLOBALS['babUrlScript']."?tg=vacadmb&idx=dreq");
		$babBody->addItemMenu("reqx", bab_translate("Export"), $GLOBALS['babUrlScript']."?tg=vacadmb&idx=reqx");
		break;

	case "delur":
		$babBody->title = bab_translate("Delete request vacation");
		deleteVacationRequest(bab_rp('vrid'));
		$babBody->addItemMenu("lreq", bab_translate("Requests"), $GLOBALS['babUrlScript']."?tg=vacadmb&idx=lreq");
		$babBody->addItemMenu("delur", bab_translate("Delete"), $GLOBALS['babUrlScript']."?tg=vacadmb&idx=delur");
		$babBody->addItemMenu("reqx", bab_translate("Export"), $GLOBALS['babUrlScript']."?tg=vacadmb&idx=reqx");
		break;

	case "ddreq":
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
		listVacationRequestsb();
		$babBody->addItemMenu("lreq", bab_translate("Requests"), $GLOBALS['babUrlScript']."?tg=vacadmb&idx=lreq");
		$babBody->addItemMenu("dreq", bab_translate("Delete"), $GLOBALS['babUrlScript']."?tg=vacadmb&idx=dreq");
		$babBody->addItemMenu("reqx", bab_translate("Export"), $GLOBALS['babUrlScript']."?tg=vacadmb&idx=reqx");
		break;
	}
$babBody->setCurrentItemMenu($idx);
?>