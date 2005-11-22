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
include_once $babInstallPath."utilit/vacincl.php";

define("VAC_MAX_RIGHTS_LIST", 20);


function addFixedVacation($id_user, $id_right, $datebegin , $dateend, $halfdaybegin, $halfdayend, $remarks, $total)
{
	global $babBody, $babDB;

	$babDB->db_query("insert into ".BAB_VAC_ENTRIES_TBL." (id_user, date_begin, date_end, day_begin, day_end, comment, date, idfai, status) values  ('" .$id_user. "', '" . $datebegin. "', '" . $dateend. "', '" . $halfdaybegin. "', '" . $halfdayend. "', '".$remarks."', curdate(), '0', 'Y')");
	$identry = $babDB->db_insert_id();
	$babDB->db_query("insert into ".BAB_VAC_ENTRIES_ELEM_TBL." (id_entry, id_type, quantity) values  ('" .$identry. "', '" .$id_right. "', '" . $total. "')");
	$idcal = bab_getCalendarId($id_user, 1);
	if( $idcal != 0 )
		{
		list($idcat) = $babDB->db_fetch_row($babDB->db_query("select vct.id_cat from ".BAB_VAC_COLLECTIONS_TBL." vct left join ".BAB_VAC_PERSONNEL_TBL." vpt on vpt.id_coll=vct.id left join ".BAB_VAC_ENTRIES_TBL." vet on vet.id_user=vpt.id_user where vet.id='".$identry."'"));

		$tbegin = $halfdaybegin == 3? '12:00:00': '00:00:00';
		$tend = $halfdayend == 2? '12:00:00': '23:59:59';
		$req = "insert into ".BAB_CAL_EVENTS_TBL." ( title, id_cat, start_date, end_date, id_creator, hash) values ";
		$req .= "('".bab_translate("Vacation")."', '".$idcat."', '".$datebegin." ".$tbegin."', '".$dateend." ".$tend."', '0', 'V_".$identry."')";
		$babDB->db_query($req);
		$id_event = $babDB->db_insert_id();
		$babDB->db_query("INSERT INTO ".BAB_CAL_EVENTS_OWNERS_TBL." (id_event,id_cal, status) VALUES ('".$id_event."','".$idcal."', '".BAB_CAL_STATUS_ACCEPTED."')");
		}
}

function updateFixedVacation($id_user, $id_right, $datebegin , $dateend, $halfdaybegin, $halfdayend, $total)
{
	global $babBody, $babDB;

	$tbegin = $halfdaybegin == 3? '12:00:00': '00:00:00';
	$tend = $halfdayend == 2? '12:00:00': '23:59:59';

	$res = $babDB->db_query("select vet.id as entry, veet.id as entryelem from ".BAB_VAC_ENTRIES_ELEM_TBL." veet left join ".BAB_VAC_ENTRIES_TBL." vet on veet.id_entry=vet.id where veet.id_type='".$id_right."' and vet.id_user='".$id_user."'");
	while( $arr = $babDB->db_fetch_array($res))
	{
		$babDB->db_query("update ".BAB_VAC_ENTRIES_TBL." set date_begin='".$datebegin."', date_end='".$dateend."', day_begin='".$halfdaybegin."', day_end='".$halfdayend."' where id='".$arr['entry']."'");
		$babDB->db_query("update ".BAB_VAC_ENTRIES_ELEM_TBL." set quantity='".$total."' where id='".$arr['entryelem']."'");
		$babDB->db_query("update ".BAB_CAL_EVENTS_TBL." set start_date='".$datebegin." ".$tbegin."', end_date='".$dateend." ".$tend."' where hash='V_".$arr['entry']."'");	
	}

}

function removeFixedVacation($id_entry)
{
	global $babBody, $babDB;
	
	$babDB->db_query("delete from ".BAB_VAC_ENTRIES_TBL." where id='".$id_entry."'");
	$babDB->db_query("delete from ".BAB_VAC_ENTRIES_ELEM_TBL." where id_entry='".$id_entry."'");
	$res = $babDB->db_query("select id from ".BAB_CAL_EVENTS_TBL." where hash='V_".$id_entry."'");
	while( $arr = $babDB->db_fetch_array($res) )
		{
		$babDB->db_query("delete from ".BAB_CAL_EVENTS_OWNERS_TBL." where id_event='".$arr['id']."'");
		$babDB->db_query("delete from ".BAB_CAL_EVENTS_NOTES_TBL." where id_event='".$arr['id']."'");
		$babDB->db_query("delete from ".BAB_CAL_EVENTS_REMINDERS_TBL." where id_event='".$arr['id']."'");
		}
	$babDB->db_query("delete from ".BAB_CAL_EVENTS_TBL." where hash='V_".$id_entry."'");
}

function browsePersonnelByType($pos, $cb, $idtype)
	{
	global $babBody;
	class temp
		{
		var $fullname;
		var $urlname;
		var $url;
		var $email;
		var $status;
		var $idtype;
				
		var $fullnameval;
		var $emailval;

		var $arr = array();
		var $db;
		var $count;
		var $res;

		var $pos;

		var $userid;

		var $nickname;

		function temp($pos, $cb, $idtype)
			{
			$this->allname = bab_translate("All");
			$this->nickname = bab_translate("Nickname");
			$this->db = $GLOBALS['babDB'];
			$this->cb = $cb;
			$this->idtype = $idtype;

			if( strlen($pos) > 0 && $pos[0] == "-" )
				{
				$this->pos = strlen($pos)>1? $pos[1]: '';
				$this->ord = $pos[0];
				$req = "select * from ".BAB_USERS_TBL." where lastname like '".$this->pos."%' order by lastname, firstname asc";
				$this->fullname = bab_translate("Lastname"). " " . bab_translate("Firstname");
				$this->fullnameurl = $GLOBALS['babUrlScript']."?tg=vacadma&idx=browt&chg=&pos=".$this->pos."&idtype=".$this->idtype."&cb=".$this->cb;
				}
			else
				{
				$this->pos = $pos;
				$this->ord = "";
				$req = "select * from ".BAB_USERS_TBL." where firstname like '".$this->pos."%' order by firstname, lastname asc";
				$this->fullname = bab_translate("Firstname"). " " . bab_translate("Lastname");
				$this->fullnameurl = $GLOBALS['babUrlScript']."?tg=vacadma&idx=browt&chg=&pos=-".$this->pos."&idtype=".$this->idtype."&cb=".$this->cb;
				}
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);

			if( empty($this->pos))
				$this->allselected = 1;
			else
				$this->allselected = 0;
			$this->allurl = $GLOBALS['babUrlScript']."?tg=vacadma&idx=browt&pos=&idtype=".$this->idtype."&cb=".$this->cb;
			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$this->arr = $this->db->db_fetch_array($this->res);
				$this->bview = false;
				$res = $this->db->db_query("select id_coll from ".BAB_VAC_PERSONNEL_TBL." where id_user='".$this->arr['id']."'");
				if( $this->idtype != "" )
					{
					while( $arr = $this->db->db_fetch_array($res))
						{
						$res2 = $this->db->db_query("select id from ".BAB_VAC_COLL_TYPES_TBL." where id_type='".$this->idtype."' and id_coll ='".$arr['id_coll']."'");
						if( $res2 && $this->db->db_num_rows($res2) > 0 )
							{
							$this->bview = true;
							break;
							}
						}
					}
				else if( $res && $this->db->db_num_rows($res) > 0 )
					$this->bview = true;

				if( $this->bview )
					{
					$this->firstlast = bab_composeUserName($this->arr['firstname'],$this->arr['lastname']);
					$this->firstlast = str_replace("'", "\'", $this->firstlast);
					$this->firstlast = str_replace('"', "'+String.fromCharCode(34)+'",$this->firstlast);
					if( $this->ord == "-" )
						$this->urlname = bab_composeUserName($this->arr['lastname'],$this->arr['firstname']);
					else
						$this->urlname = bab_composeUserName($this->arr['firstname'],$this->arr['lastname']);
					$this->userid = $this->arr['id'];
					}
				$i++;
				return true;
				}
			else
				return false;

			}

		function getnextselect()
			{
			global $BAB_SESS_USERID;
			static $k = 0;
			static $t = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
			if( $k < 26)
				{
				$this->selectname = substr($t, $k, 1);
				$this->selecturl = $GLOBALS['babUrlScript']."?tg=vacadma&idx=browt&pos=".$this->ord.$this->selectname."&idtype=".$this->idtype."&cb=".$this->cb;

				if( $this->pos == $this->selectname)
					$this->selected = 1;
				else 
					{
					if( $this->ord == "-" )
						$req = "select ".BAB_USERS_TBL.".id from ".BAB_USERS_TBL." join ".BAB_VAC_PERSONNEL_TBL." where lastname like '".$this->selectname."%' and ".BAB_USERS_TBL.".id = ".BAB_VAC_PERSONNEL_TBL.".id_user";
					else
						$req = "select ".BAB_USERS_TBL.".id from ".BAB_USERS_TBL." join ".BAB_VAC_PERSONNEL_TBL." where firstname like '".$this->selectname."%' and ".BAB_USERS_TBL.".id = ".BAB_VAC_PERSONNEL_TBL.".id_user";
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

	$temp = new temp($pos, $cb, $idtype);

	include_once $GLOBALS['babInstallPath']."utilit/uiutil.php";
	$GLOBALS['babBodyPopup'] = new babBodyPopup();
	$GLOBALS['babBodyPopup']->title = $GLOBALS['babBody']->title;
	$GLOBALS['babBodyPopup']->msgerror = $GLOBALS['babBody']->msgerror;
	$GLOBALS['babBodyPopup']->babecho(bab_printTemplate($temp, "vacadma.html", "browseusers"));
	printBabBodyPopup();
	die();

	
	}

function listVacationRigths($idtype, $idcreditor, $dateb, $datee, $active, $pos)
{
	global $babBody;

	class temp
		{
		var $typetxt;
		var $desctxt;
		var $quantitytxt;
		var $creditortxt;
		var $datetxt;
		var $date2txt;
		var $vrurl;
		var $vrviewurl;
		var $description;
				
		var $typename;
		var $quantity;
		var $creditor;
		var $date;
		var $addtxt;
		var $addurl;
		var $filteron;
		var $statustxt;
		var $activeyes;
		var $activeno;
		var $yselected;
		var $nselected;

		var $urllistp;
		var $altlistp;
		var $selected;

		var $begintxt;
		var $endtxt;

		var $arr = array();
		var $db;
		var $count;
		var $res;
		var $topurl;
		var $bottomurl;
		var $nexturl;
		var $prevurl;
		var $topname;
		var $bottomname;
		var $nextname;
		var $prevname;
		var $pos;
		var $bclose;
		var $closedtxt;
		var $openedtxt;
		var $statusval;
		var $alttxt;

		function temp($idtype, $idcreditor, $dateb, $datee, $active, $pos)
			{
			$this->desctxt = bab_translate("Description");
			$this->typetxt = bab_translate("Type");
			$this->nametxt = bab_translate("Name");
			$this->quantitytxt = bab_translate("Quantity");
			$this->creditortxt = bab_translate("Author");
			$this->datetxt = bab_translate("Entry date");
			$this->date2txt = bab_translate("Entry date ( dd-mm-yyyy )");
			$this->addtxt = bab_translate("Allocate vacation rights");
			$this->filteron = bab_translate("Filter on");
			$this->begintxt = bab_translate("Begin");
			$this->endtxt = bab_translate("End");
			$this->altlistp = bab_translate("Beneficiaries");
			$this->statustxt = bab_translate("Status");
			$this->activeyes = bab_translate("Opened rights");
			$this->activeno = bab_translate("Closed rights");
			$this->closedtxt = bab_translate("Vac. closed");
			$this->openedtxt = bab_translate("Vac. opened");
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
			$this->yselected = "";
			$this->nselected = "";
			$this->db = $GLOBALS['babDB'];
				
			$this->dateb = $dateb;
			$this->datee = $datee;
			$this->idtype = $idtype;
			$this->idcreditor = $idcreditor;
			$this->active = $active;
			$this->pos = $pos;
			if( $this->active == "Y")
				$this->yselected = "selected";
			else if( $this->active == "N")
				$this->nselected = "selected";

			$req = "".BAB_VAC_RIGHTS_TBL;
			if( $idtype != "" || $idcreditor != "" || $dateb != "" || $datee != ""|| $active != "")
				{
				$req .= " where ";

				if( $idtype != "")
					$aaareq[] = "id_type='".$idtype."'";

				if( $active != "")
					$aaareq[] = "active='".$active."'";

				if( $idcreditor != "")
					{
					$aaareq[] = "id_creditor='".$idcreditor."'";
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

				if( $dateb != "" && $datee != "")
					{
					$aaareq[] = "( date_entry between '".$dateb."' and '".$datee."')";
					}
				else if( $dateb == "" && $datee != "" )
					{
					$aaareq[] = "date_entry <= '".$datee."'";
					}
				else if ($dateb != "" )
					{
					$aaareq[] = "date_entry >= '".$dateb."'";
					}
				}

			if( isset($aaareq) && sizeof($aaareq) > 0 )
				{
				if( sizeof($aaareq) > 1 )
					$req .= implode(' and ', $aaareq);
				else
					$req .= $aaareq[0];
				}
			$req .= " order by date_entry desc";

			list($total) = $this->db->db_fetch_row($this->db->db_query("select count(*) as total from ".$req));

			if( $total > VAC_MAX_RIGHTS_LIST )
				{
				$tmpurl = $GLOBALS['babUrlScript']."?tg=vacadma&idx=lrig&idtype=".$this->idtype."&idcreditor=".$this->idcreditor."&dateb=".$this->dateb."&datee=".$this->datee."&active=".$this->active."&pos=";
				if( $pos > 0)
					{
					$this->topurl = $tmpurl."0";
					$this->topname = "&lt;&lt;";
					}

				$next = $pos - VAC_MAX_RIGHTS_LIST;
				if( $next >= 0)
					{
					$this->prevurl = $tmpurl.$next;
					$this->prevname = "&lt;";
					}

				$next = $pos + VAC_MAX_RIGHTS_LIST;
				if( $next < $total)
					{
					$this->nexturl = $tmpurl.$next;
					$this->nextname = "&gt;";
					if( $next + VAC_MAX_RIGHTS_LIST < $total)
						{
						$bottom = $total - VAC_MAX_RIGHTS_LIST;
						}
					else
						$bottom = $next;
					$this->bottomurl = $tmpurl.$bottom;
					$this->bottomname = "&gt;&gt;";
					}
				}


			if( $total > VAC_MAX_RIGHTS_LIST)
				{
				$req .= " limit ".$pos.",".VAC_MAX_RIGHTS_LIST;
				}
			$this->res = $this->db->db_query("select * from ".$req);
			$this->count = $this->db->db_num_rows($this->res);
			$this->addurl = $GLOBALS['babUrlScript']."?tg=vacadma&idx=addvr";

			$this->restype = $this->db->db_query("select * from ".BAB_VAC_TYPES_TBL." order by name asc");
			$this->counttype = $this->db->db_num_rows($this->restype);

			$this->resc= $this->db->db_query("select distinct id_creditor from ".BAB_VAC_RIGHTS_TBL."");
			$this->countc = $this->db->db_num_rows($this->resc);

			$this->dateburl = $GLOBALS['babUrlScript']."?tg=month&callback=dateBegin&ymin=0&ymax=3";
			$this->dateeurl = $GLOBALS['babUrlScript']."?tg=month&callback=dateEnd&ymin=0&ymax=3";

			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $this->db->db_fetch_array($this->res);
				$rr = $this->db->db_fetch_array($this->db->db_query("select name from ".BAB_VAC_TYPES_TBL." where id='".$arr['id_type']."'"));
				$this->vrurl = $GLOBALS['babUrlScript']."?tg=vacadma&idx=modvr&idvr=".$arr['id'];
				$this->vrviewurl = $GLOBALS['babUrlScript']."?tg=vacadma&idx=viewvr&idvr=".$arr['id'];
				$this->typename = $rr['name'];
				$this->description = $arr['description'];
				$this->quantity = $arr['quantity'];
				$this->creditor = bab_getUserName($arr['id_creditor']);
				$this->date = bab_shortDate(bab_mktime($arr['date_entry']." 00:00:00"), false);
				$this->bclose = $arr['active'] == "N"? true: false;
				if( $this->bclose )
					$this->statusval = $this->closedtxt;
				else
					$this->statusval = $this->openedtxt;
				$this->urllistp = $GLOBALS['babUrlScript']."?tg=vacadma&idx=lvrp&idvr=".$arr['id'];
				$i++;
				return true;
				}
			else
				return false;

			}

		function getnexttype()
			{
			static $i = 0;
			if( $i < $this->counttype)
				{
				$arr = $this->db->db_fetch_array($this->restype);
				$this->typename = $arr['name'];
				$this->typeid = $arr['id'];
				if( $this->idtype == $this->typeid )
					$this->selected = "selected";
				else
					$this->selected ="";
				$i++;
				return true;
				}
			else
				{
				return false;
				}

			}

		function getnextcreditor()
			{
			static $i = 0;
			if( $i < $this->countc)
				{
				$arr = $this->db->db_fetch_array($this->resc);
				$this->creditorname = bab_getUserName($arr['id_creditor']);
				$this->creditorid = $arr['id_creditor'];
				if( $this->idcreditor == $this->creditorid )
					$this->selected = "selected";
				else
					$this->selected ="";
				$i++;
				return true;
				}
			else
				return false;

			}
		}

	$temp = new temp($idtype, $idcreditor, $dateb, $datee, $active, $pos);
	$babBody->babecho(	bab_printTemplate($temp, "vacadma.html", "vrightslist"));
	return $temp->count;

}


function addModifyVacationRigths($id = false)
	{
	global $babBody;
	class temp
		{
		var $yes;
		var $no;
		var $invalidentry1;
		var $tpsel;
		var $colsel;

		function temp($id)
			{
			$this->t_id_type = bab_translate("Type");
			$this->t_id_creditor = bab_translate("User");
			$this->t_collid = bab_translate("Collection");
			$this->t_quantity = bab_translate("Quantity");
			$this->t_reset = bab_translate("Reset");
			$this->t_delete = bab_translate("Delete");
			$this->t_orand = bab_translate("Or users having ");
			$this->t_allcol = bab_translate("All collections");
			$this->t_allpers = bab_translate("All users");
			$this->t_days = bab_translate("Day(s)");
			$this->t_description = bab_translate("Description");
			$this->t_period = bab_translate("Period"). " (".bab_translate("dd-mm-yyyy").")";
			$this->t_date_begin = $this->t_period_start = bab_translate("Begin");
			$this->t_date_end = $this->t_period_end = bab_translate("End");
			$this->t_active = bab_translate("Opened right");
			$this->t_cbalance = bab_translate("Accept negative balance");
			$this->t_use_rules = bab_translate("Use rules");
			$this->t_trigger_nbdays_min = bab_translate("Minimum number of days");
			$this->t_trigger_nbdays_max = bab_translate("Maximum number of days");
			$this->t_period_rule = bab_translate("Rule period"). " (".bab_translate("dd-mm-yyyy").")";
			$this->t_trigger_inperiod = bab_translate("Allow rule");
			$this->t_always = bab_translate("Always");
			$this->t_all_period = bab_translate("On all period");
			$this->t_inperiod = bab_translate("In period");
			$this->t_outperiod = bab_translate("Out of period");
			$this->t_right_inperiod = bab_translate("Apply right");
			$this->t_record = bab_translate("Record");
			$this->t_trigger_type = bab_translate("Allow rule with type");
			$this->t_all = bab_translate("All");
			$this->t_periodvalid = bab_translate("Retention period"). " (".bab_translate("dd-mm-yyyy").")";
			$this->t_right_type = bab_translate("Right type");

			$this->t_datebegintxt = bab_translate("Begin date");
			$this->t_dateendtxt = bab_translate("End date");
			$this->invaliddate = bab_translate("ERROR: End date must be older");
			$this->invalidentry2 = bab_translate("Days must be multiple of 0.5");
			$this->invalidentry3 = bab_translate("The number of days exceed the total allowed");
		

			$this->yes = bab_translate("Yes");
			$this->no = bab_translate("No");
			$this->invalidentry1 = bab_translate("Invalid entry!  Only numbers are accepted or . !");
			$this->invalidentry1 = str_replace("'", "\'", $this->invalidentry1);
			$this->invalidentry1 = str_replace('"', "'+String.fromCharCode(34)+'",$this->invalidentry1);
			$this->invalidtotal = bab_translate("Total days does'nt fit between dates");
			$this->invalidtotal = str_replace("'", "\'", $this->invalidtotal);
			$this->invalidtotal = str_replace('"', "'+String.fromCharCode(34)+'",$this->invalidtotal);

			$this->usersbrowurl = $GLOBALS['babUrlScript']."?tg=vacadma&idx=browt";
			$this->db = & $GLOBALS['babDB'];
			$el_to_init = array('idvr', 'id_creditor', 'date_begin', 'date_end', 'quantity', 'id_type', 'description', 'active', 'cbalance','period_start', 'period_end', 'trigger_nbdays_min', 'trigger_nbdays_max', 'trigger_inperiod', 'right_inperiod', 'righttype', 'trigger_type', 'date_begin_valid', 'date_end_valid');

			$dates_to_init = array('date_begin', 'date_end', 'period_start','period_end', 'date_begin_valid', 'date_end_valid');
			
			
			$this->arr['righttype'] = '0';
			if (isset($_POST) && count($_POST) > 0)
				{
				$this->arr = $_POST;
				}
			elseif ($id)
				{
				$this->arr = $this->db->db_fetch_array($this->db->db_query(
					"SELECT 
						t1.*,
						t2.id righttype,
						t2.period_start, 
						t2.period_end, 
						t2.trigger_nbdays_min, 
						t2.trigger_nbdays_max, 
						t2.trigger_inperiod,
						t2.trigger_type,
						t2.right_inperiod, 
						t3.name type 
					FROM 
						".BAB_VAC_RIGHTS_TBL." t1
					LEFT JOIN 
						".BAB_VAC_RIGHTS_RULES_TBL." t2 
					ON t2.id_right=t1.id 
					LEFT JOIN 
						".BAB_VAC_TYPES_TBL." t3 
					ON t3.id = t1.id_type 
					WHERE t1.id='".$id."'"));

				$this->collid = "";

				if( $this->arr['date_begin_fixed'] != '0000-00-00')
					{
					$this->arr['righttype'] =  2;
					}
				elseif( isset($this->arr['righttype']))
					{
					$this->arr['righttype'] =  1;
					}
				else
					{
					$this->arr['righttype'] =  0;
					}
				foreach($dates_to_init as $field)
					{
					if (!empty($this->arr[$field]) && $this->arr[$field] != '0000-00-00')
						{
						list($y,$m,$d) = explode('-',$this->arr[$field]);
						$this->arr[$field] = $d.'-'.$m.'-'.$y;
						}
					else
						{
						$this->arr[$field] = '';
						}
					}
				$this->arr['idvr'] = $id;
				}
			
			if (isset($_GET['idtype']))
				$default = array('id_type' => $_GET['idtype']);
			else
				$default = array();
			
			foreach($el_to_init as $field)
				{
				if ( !isset($this->arr[$field]) )
					$this->arr[$field] = isset($default[$field]) ? $default[$field] : '';
				}
			
			if( $this->arr['id_creditor'] != "" )
				$this->arr['id_creditorDisplay'] = bab_getUserName($this->arr['id_creditor']);
			else
				$this->arr['id_creditorDisplay'] = "";
			$this->bdel = false;
			
			if( isset($this->arr['collid']) && $this->arr['collid'] == '' )
				$this->colsel = 0;
			else if( isset($this->arr['collid']) && $this->arr['collid'] == -1)
				$this->colsel = 1;
			else
				{
				$this->colsel = 0;
				}

			$this->tpsel = 0;
			
			$this->restype = $this->db->db_query("select * from ".BAB_VAC_TYPES_TBL." order by name asc");
			$this->counttype = $this->db->db_num_rows($this->restype);


			$this->year = isset($_POST['year']) ? $_POST['year'] : date('Y');

			if (!empty($id) && !isset($_POST['daybeginfx']) && $this->arr['date_begin_fixed'] != '0000-00-00')
				{
				list($this->yearbegin, $this->monthbegin, $this->daybegin) = explode('-',$this->arr['date_begin_fixed']);
				list($this->yearend, $this->monthend, $this->dayend) = explode('-',$this->arr['date_end_fixed']);
				$this->halfdaybegin = $this->arr['day_begin_fixed'];
				$this->halfdayend = $this->arr['day_end_fixed'];
				}
			elseif (isset($_POST['daybeginfx']))
				{
				$this->daybegin = $_POST['daybeginfx'];
				$this->dayend = $_POST['dayendfx'];
				$this->monthbegin = $_POST['monthbeginfx'];
				$this->monthend = $_POST['monthendfx'];
				$this->yearbegin = $this->year+ $_POST['yearbeginfx']-1;
				$this->yearend = $this->year+ $_POST['yearendfx']-1;
				$this->halfdaybegin = $_POST['halfdaybeginfx'];
				$this->halfdayend = $_POST['halfdayendfx'];
				}
			else
				{
				$this->daybegin = date("j");
				$this->dayend = date("j");
				$this->monthbegin = date("n");
				$this->monthend = date("n");
				$this->yearbegin = $this->year;
				$this->yearend = $this->year;
				$this->halfdaybegin = 1;
				$this->halfdayend = 1;
				}

			$this->halfdaysel = $this->halfdaybegin;
			$this->daysel = $this->daybegin;
			$this->monthsel = $this->monthbegin;
			$this->yearsel = $this->yearbegin -$this->year +1;
			
			if( $id )
				{
				if( $this->arr['righttype'] == '2' )
					$this->rightypes = array(2=>bab_translate("Fixed dates"));
				else
					$this->rightypes = array(0=>"", 1=>bab_translate("Use rules"));
				}
			else
				{
				$this->rightypes = array(0=>"", 1=>bab_translate("Use rules"), 2=>bab_translate("Fixed dates"));
				}

			}

		function getnextrighttype()
			{
			if (list($valid,$valname) = each($this->rightypes))
				{
				$this->righttypeid = $valid;
				$this->righttypeval = $valname;
				if( $this->righttypeid == $this->arr['righttype'])
    				{
				    $this->selected = 'selected';
                   }
                else
                   {
                    $this->selected = '';
                   }
				return true;
				}
			else
				{
				return false;
				}
			}
		
		function getnextcol()
			{
			static $j= 0;
			if( $j < $this->countcol )
				{
				$arr = $this->db->db_fetch_array($this->colres);
				$this->collval = str_replace("'", "\'", $arr['name']);
				$this->collval = str_replace('"', "'+String.fromCharCode(34)+'",$this->collval);
				$this->idcollection = $arr['id'];
				if( isset($this->arr['collid']) && $this->arr['collid'] == $this->idcollection)
					$this->colsel = $j+1;
				$j++;
				return true;
				}
			else
				{
				$j = 0;
				return false;
				}
			}

		function getnexttype()
			{
			static $i = 0;
			if( $i < $this->counttype)
				{
				$this->iindex = $i;
				$arr = $this->db->db_fetch_array($this->restype);
				$this->typename = $arr['name'];
				$this->typeid = $arr['id'];
				if( $arr['cbalance'] == 'Y' )
					{
					$this->tcbalance = 0;
					}
				else
					{
					$this->tcbalance = 1;
					}
				$this->colres = $this->db->db_query("select ".BAB_VAC_COLLECTIONS_TBL.".* from ".BAB_VAC_COLLECTIONS_TBL." join ".BAB_VAC_COLL_TYPES_TBL." where ".BAB_VAC_COLL_TYPES_TBL.".id_type='".$this->typeid."' and ".BAB_VAC_COLLECTIONS_TBL.".id=".BAB_VAC_COLL_TYPES_TBL.".id_coll");
				$this->countcol = $this->db->db_num_rows($this->colres);

				if( $this->arr['id_type'] == $this->typeid )
					{
					$this->tpsel = $i;
					$this->selected = "selected";
					}
				else
					$this->selected ="";
				$i++;
				return true;
				}
			else
				{
				if( $this->counttype > 0 )
					$this->db->db_data_seek($this->restype, 0 );
				$i = 0;
				return false;
				}

			}
		function getnextday()
			{
			static $i = 1;

			if( $i <= date("t"))
				{
				$this->t_dayid = $i;
				if( (int)($this->daysel) == $this->t_dayid )
					$this->selected = "selected";
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
				$this->t_monthname = $babMonths[$i];
				if( (int)($this->monthsel) == $this->monthid )
					$this->selected = "selected";
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
				$this->t_yearidval = $this->year + $i;
				if( (int)($this->yearsel) == $this->yearid )
					$this->selected = "selected";
				else
					$this->selected = "";
				$i++;
				return true;
				}
			else
				{
    			$this->yearsel = $this->yearend -$this->year +1;
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
				$this->t_halfname = $babDayType[$i];
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

	$temp = new temp($id);
	$babBody->babecho(	bab_printTemplate($temp,"vacadma.html", "rightsedit"));
	}

function listVacationRightPersonnel($pos, $idvr)
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
		var $idvr;

		var $pos;
		var $selected;
		var $allselected;
		var $allurl;
		var $allname;
		var $checkall;
		var $uncheckall;
		var $deletealt;
		var $modify;
		var $quantitytxt;
		var $quantity;
		var $altbg = true;


		function temp($pos, $idvr)
			{
			$this->allname = bab_translate("All");
			$this->uncheckall = bab_translate("Uncheck all");
			$this->checkall = bab_translate("Check all");
			$this->deletealt = bab_translate("Delete");
			$this->modify = bab_translate("Modify");
			$this->quantitytxt = bab_translate("Quantity");
			$this->t_used = bab_translate("Used");
			$this->t_close = bab_translate("Close");

			$this->db = $GLOBALS['babDB'];
			$this->idvr = $idvr;
			list($this->idtype) = $this->db->db_fetch_row($this->db->db_query("select id_type from ".BAB_VAC_RIGHTS_TBL." where id='".$idvr."'")); 

			if( isset($pos[0]) && $pos[0] == "-" )
				{
				$this->pos = $pos[1];
				$this->ord = $pos[0];
				$req = "select ".BAB_USERS_TBL.".*, ".BAB_VAC_PERSONNEL_TBL.".id_coll from ".BAB_USERS_TBL." join ".BAB_VAC_PERSONNEL_TBL." where ".BAB_USERS_TBL.".id = ".BAB_VAC_PERSONNEL_TBL.".id_user and lastname like '".$this->pos."%' order by lastname, firstname asc";
				$this->fullname = bab_translate("Lastname"). " " . bab_translate("Firstname");

				$this->fullnameurl = $GLOBALS['babUrlScript']."?tg=vacadma&idx=lvrp&chg=&pos=".$this->ord.$this->pos."&idvr=".$this->idvr;
				}
			else
				{
				$this->pos = $pos;
				$this->ord = "";
				$req = "select ".BAB_USERS_TBL.".*, ".BAB_VAC_PERSONNEL_TBL.".id_coll from ".BAB_USERS_TBL." join ".BAB_VAC_PERSONNEL_TBL." where ".BAB_USERS_TBL.".id = ".BAB_VAC_PERSONNEL_TBL.".id_user and firstname like '".$this->pos."%' order by firstname, firstname asc";
				$this->fullname = bab_translate("Firstname"). " " . bab_translate("Lastname");
				$this->fullnameurl = $GLOBALS['babUrlScript']."?tg=vacadma&idx=lvrp&chg=&pos=".$this->ord.$this->pos."&idvr=".$this->idvr;
				}
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);

			if( empty($this->pos))
				$this->allselected = 1;
			else
				$this->allselected = 0;
			$this->allurl = $GLOBALS['babUrlScript']."?tg=vacadma&idx=lvrp&pos=&idvr=".$this->idvr;
			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$this->arr = $this->db->db_fetch_array($this->res);
				$this->bview = false;
				$this->used = 0;
				$this->selected = "";
				$this->nuserid = "";
				$res2 = $this->db->db_query("select id from ".BAB_VAC_COLL_TYPES_TBL." where id_type='".$this->idtype."' and id_coll ='".$this->arr['id_coll']."'");
				if( $res2 && $this->db->db_num_rows($res2) > 0 )
					{
					$this->bview = true;
					}

				if( $this->bview )
				{
					$this->altbg = !$this->altbg;

					$res2 = $this->db->db_query("select id, quantity from ".BAB_VAC_USERS_RIGHTS_TBL." where id_user='".$this->arr['id']."' and id_right ='".$this->idvr."'");
					if( $res2 && $this->db->db_num_rows($res2) > 0 )
						{
						$arr = $this->db->db_fetch_array($res2);
						$this->selected = "checked";
						$this->nuserid = $this->arr['id'];

						$res3 = $this->db->db_query("select SUM(e2.quantity) used from ".BAB_VAC_ENTRIES_ELEM_TBL." e2, ".BAB_VAC_ENTRIES_TBL." e1 WHERE  e2.id_type='".$this->idvr."' AND e2.id_entry = e1.id AND e1.id_user='".$this->arr['id']."' AND e1.status='Y'");

						$arr3 = $this->db->db_fetch_array($res3);
						if (isset($arr3['used']))
							$this->used = $arr3['used'];
						}
					
					$this->url = $GLOBALS['babUrlScript']."?tg=vacadma&idx=modp&idp=".$this->arr['id']."&pos=".$this->ord.$this->pos."&idvr=".$this->idvr;
					if( $this->ord == "-" )
						$this->urlname = bab_composeUserName($this->arr['lastname'],$this->arr['firstname']);
					else
						$this->urlname = bab_composeUserName($this->arr['firstname'],$this->arr['lastname']);
	
					if( isset($arr['quantity']) && $arr['quantity'] != '' )
						$this->quantity = $arr['quantity'];
					else
					{
						list($this->quantity) = $this->db->db_fetch_row($this->db->db_query("select quantity from ".BAB_VAC_RIGHTS_TBL." where id='".$this->idvr."'"));
					}
		
					$this->userid = $this->arr['id'];
				}
				
				

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
				$this->selecturl = $GLOBALS['babUrlScript']."?tg=vacadma&idx=lvrp&pos=".$this->ord.$this->selectname."&idvr=".$this->idvr;

				if( $this->pos == $this->selectname)
					$this->selected = 1;
				else 
					{
					if( $this->ord == "-" )
						{
						$req = "select ".BAB_USERS_TBL.".id from ".BAB_USERS_TBL." join ".BAB_VAC_PERSONNEL_TBL." where ".BAB_USERS_TBL.".id=".BAB_VAC_PERSONNEL_TBL.".id_user and ".BAB_USERS_TBL.".lastname like '".$this->selectname."%' ";
						}
					else
						{
						$req = "select ".BAB_USERS_TBL.".id from ".BAB_USERS_TBL." join ".BAB_VAC_PERSONNEL_TBL." where ".BAB_USERS_TBL.".id=".BAB_VAC_PERSONNEL_TBL.".id_user and ".BAB_USERS_TBL.".firstname like '".$this->selectname."%' ";
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

	$temp = new temp($pos, $idvr);

	include_once $GLOBALS['babInstallPath']."utilit/uiutil.php";
	$GLOBALS['babBodyPopup'] = new babBodyPopup();
	$GLOBALS['babBodyPopup']->title = $GLOBALS['babBody']->title;
	$GLOBALS['babBodyPopup']->msgerror = $GLOBALS['babBody']->msgerror;
	$GLOBALS['babBodyPopup']->babecho(bab_printTemplate($temp, "vacadma.html", "vrpersonnellist"));
	printBabBodyPopup();
	}

function viewVacationRightPersonnel($idvr)
{
	global $babBody;

	class temp
		{
		var $datebegintxt;
		var $datebegin;
		var $dateendtxt;
		var $dateend;
		var $typetxt;
		var $type;
		var $description;
		var $quantitytxt;
		var $quantity;
		var $creditortxt;
		var $creditor;
		var $dateentrytxt;
		var $dateentry;
		var $statustxt;
		var $status;
				
		function temp($idvr)
			{
			global $babDayType;
			$this->datebegintxt = bab_translate("Begin date");
			$this->dateendtxt = bab_translate("End date");
			$this->dateentrytxt = bab_translate("Entry date");
			$this->quantitytxt = bab_translate("Quantity");
			$this->typetxt = bab_translate("Vacation type");
			$this->creditortxt = bab_translate("Author");
			$this->statustxt = bab_translate("Status");
			$this->validperiodtxt = bab_translate("Retention period");
			$this->db = $GLOBALS['babDB'];

			$row = $this->db->db_fetch_array($this->db->db_query("select * from ".BAB_VAC_RIGHTS_TBL." where id='".$idvr."'"));
			$this->datebegin = bab_strftime(bab_mktime($row['date_begin']." 00:00:00"), false);
			$this->dateend = bab_strftime(bab_mktime($row['date_end']." 00:00:00"), false);
			$this->dateentry = bab_strftime(bab_mktime($row['date_entry']." 00:00:00"), false);
			if( $row['date_begin_valid'] != '0000-00-00' && $row['date_end_valid'] != '0000-00-00' )
				{
				$this->bvalidperiod = true;
				$this->datebeginvalid = bab_strftime(bab_mktime($row['date_begin_valid']." 00:00:00"), false);
				$this->dateendvalid = bab_strftime(bab_mktime($row['date_end_valid']." 00:00:00"), false);
				}
			else
				{
				$this->bvalidperiod = false;
				}
			$this->description = $row['description'];
			$this->creditor = bab_getUserName($row['id_creditor']);
			$this->quantity = $row['quantity'];
			$this->status = $row['active'] == "Y"? bab_translate("Right opened"): bab_translate("Right closed");
			list($this->type) = $this->db->db_fetch_row($this->db->db_query("select name from ".BAB_VAC_TYPES_TBL." where id='".$row['id_type']."'"));
			}

		}

	$temp = new temp($idvr);
	echo bab_printTemplate($temp, "vacadma.html", "viewvacright");
	}



function updateVacationRight()
	{
	global $babBody, $babDB;

	$post = $_POST;

	if( empty($post['description']))
		{
		$babBody->msgerror = bab_translate("You must specify a vacation description") ." !";
		return false;
		}

	if( !is_numeric($post['quantity']))
		{
		$babBody->msgerror = bab_translate("You must specify a correct number days") ." !";
		return false;
		}


	if (isset($post['id_type']))
		{
		$res = $babDB->db_query("SELECT cbalance FROM ".BAB_VAC_TYPES_TBL." WHERE id='".$post['id_type']."'");
		}
	elseif(isset($post['idvr']))
		{
		$res = $babDB->db_query("SELECT t.cbalance FROM ".BAB_VAC_TYPES_TBL." t, ".BAB_VAC_RIGHTS_TBL." r WHERE t.id=r.id_type AND r.id='".$post['idvr']."'");
		}

	if (list($cbalance) = $babDB->db_fetch_array($res))
		{
		if ($cbalance == 'N' && $_POST['cbalance'] != 'N')
			{
			$babBody->msgerror = bab_translate("Negative balance are not allowed with this vacation type") ." !";
			return false;
			}
		}

	if( empty($post['idvr']) && empty($post['id_creditor']) && empty($post['collid']) )
		{
		$babBody->msgerror = bab_translate("You must specify a user or collection") ." !";
		return false;
		}

	$dates_to_init = array('date_begin' => 1, 'date_end' =>1, 'period_start' => 0,'period_end' => 0, 'date_begin_valid' => 0,'date_end_valid' => 0);
	
    if( $post['righttype'] == '2')
        {
        $post['date_begin_fixed'] = sprintf("%02d-%02d-%04d", $post['daybeginfx'], $post['monthbeginfx'], ($post['yearbeginfx'] + $post['year'] - 1 ));
        $post['date_end_fixed'] = sprintf("%02d-%02d-%04d", $post['dayendfx'], $post['monthendfx'], ($post['yearendfx']+ $post['year'] - 1 ));
        $dates_to_init['date_begin_fixed'] = 1;
        $dates_to_init['date_end_fixed'] = 1;
        }
    else
        {
        $post['date_begin_fixed'] = '0000-00-00';
        $post['date_end_fixed'] = '0000-00-00';
        }

	foreach ($dates_to_init as $date => $required)
		{
		$arr = explode("-", $post[$date]);
		if ($required && (count($arr) != 3 || !checkdate($arr[1],$arr[0],$arr[2])))
			{
			$babBody->msgerror = bab_translate("Invalid date") ." !";
			return false;
			}
			
		if (count($arr) == 3)
			$post[$date] = sprintf("%04d-%02d-%02d", $arr[2], $arr[1], $arr[0]);
		else
			$post[$date] = '';
		}

	if( $post['date_begin'] > $post['date_end'] || $post['period_start'] > $post['period_end'] || $post['date_begin_valid'] > $post['date_end_valid'])
		{
		$babBody->msgerror = bab_translate("Begin date must be less than end date") ." !";
		return false;
		}

	if( $post['righttype'] == '2' && $post['date_begin_fixed'] > $post['date_end_fixed'] )
		{
		$babBody->msgerror = bab_translate("Begin date must be less than end date") ." !";
		return false;
		}

	if( $post['active'] == 'Y' )
		{
		if( !empty($post['date_begin_valid']) && $post['date_begin_valid'] != '0000-00-00' && (bab_mktime($post['date_begin_valid']." 00:00:00") > mktime()))
			{
			$post['active'] = 'N';
			}

		if( !empty($post['date_end_valid']) && $post['date_end_valid'] != '0000-00-00' && (bab_mktime($post['date_end_valid']." 23:59:59") < mktime()))
			{
			$post['active'] = 'N';
			}
		}

	if( !bab_isMagicQuotesGpcOn())
		{
		$post['description'] = $babDB->db_escape_string($post['description']);
  		}


	$handlefx = 0;
	if (!empty($post['idvr']))
		{
		list($dbfx) = $babDB->db_fetch_row($babDB->db_query("select date_begin_fixed from ".BAB_VAC_RIGHTS_TBL." where id='".$post['idvr']."'"));

		if( $dbfx != '0000-00-00' && $post['righttype'] != '2')
			{
			// on ne doit pas passer ici, normallement
			$babBody->msgerror = bab_translate("Ooops, something is wrong") ." !";
			return false;
			}

		if( $post['righttype'] != '2')
			{
			$post['date_begin_fixed'] = '0000-00-00';
			$post['date_end_fixed'] = '0000-00-00';
			$post['day_begin_fixed'] = '0';
			$post['day_end_fixed'] = '0';
			}
		else
			{
			$res = $babDB->db_query("select * from ".BAB_VAC_RIGHTS_TBL." where id='".$post['idvr']."'");
			if( $res && $babDB->db_num_rows($res) > 0 )
				{
				$arrright = $babDB->db_fetch_array($res);

				$rightusers = array();
				$res = $babDB->db_query("select id_user from ".BAB_VAC_USERS_RIGHTS_TBL." where id_right='".$post['idvr']."'");
				while( $arr = $babDB->db_fetch_array($res))
					{
					$rightusers[$arr['id_user']] = 1;
					}

				$entriesusers = array();
				$res = $babDB->db_query("select vet.*, veet.quantity from ".BAB_VAC_ENTRIES_ELEM_TBL." veet left join ".BAB_VAC_ENTRIES_TBL." vet on veet.id_entry=vet.id where veet.id_type='".$post['idvr']."'");
				while( $arr = $babDB->db_fetch_array($res))
					{
					$entriesusers[$arr['id_user']] = $arr['id'];
					if( !isset($rightusers[$arr['id_user']]) )
						{
						removeFixedVacation( $arr['id']);
						$arrnotif[] = $arr['id_user'];
						// remove vacation and notify
						notifyOnVacationChange($arrnotif, $arr['quantity'], $arr['date_begin'], $arr['day_begin'], $arr['date_end'], $arr['day_end'], VAC_FIX_DELETE);
						}
					}

				if( $arrright['date_begin_fixed'] != $post['date_begin_fixed']
					|| $arrright['date_end_fixed'] != $post['date_end_fixed']
					|| $arrright['day_begin_fixed'] != $post['halfdaybeginfx']
					|| $arrright['day_end_fixed'] != $post['halfdayendfx'] )
					{
					$updatevac = true;
					}
				else
					{
					$updatevac = false;
					}
				
				$uupd = array(); 
				$uadd = array(); 
				foreach($rightusers as $ukey => $uval )
					{
					if( isset($entriesusers[$ukey]) && $updatevac )
						{
						updateFixedVacation($ukey, $post['idvr'], $post['date_begin_fixed'] , $post['date_end_fixed'], $post['halfdaybeginfx'], $post['halfdayendfx'], $post['quantity']);
						$uupd[] = $ukey;
						// update
						}
					elseif( !isset($entriesusers[$ukey]))
						{
						// add 
						addFixedVacation($ukey, $post['idvr'], $post['date_begin_fixed'] , $post['date_end_fixed'], $post['halfdaybeginfx'], $post['halfdayendfx'], '', $post['quantity']);
						$uadd[] = $ukey;
						}
					}

				if( count($uupd)> 0 )
					{
					notifyOnVacationChange($uupd, $post['quantity'], $post['date_begin_fixed'], $post['halfdaybeginfx'], $post['date_end_fixed'], $post['halfdayendfx'], VAC_FIX_UPDATE);
					}
				if( count($uadd)> 0 )
					{
					notifyOnVacationChange($uadd, $post['quantity'], $post['date_begin_fixed'], $post['halfdaybeginfx'], $post['date_end_fixed'], $post['halfdayendfx'], VAC_FIX_ADD);
					}
				}
			}

		$babDB->db_query("UPDATE ".BAB_VAC_RIGHTS_TBL." set description='".$post['description']."', id_creditor='".$GLOBALS['BAB_SESS_USERID']."', quantity='".$post['quantity']."', date_entry=curdate(), date_begin='".$post['date_begin']."', date_end='".$post['date_end']."', active='".$post['active']."', cbalance='".$post['cbalance']."', date_begin_valid='".$post['date_begin_valid']."', date_end_valid='".$post['date_end_valid']."', date_begin_fixed='".$post['date_begin_fixed']."', date_end_fixed='".$post['date_end_fixed']."', day_begin_fixed='".$post['halfdaybeginfx']."', day_end_fixed='".$post['halfdayendfx']."' where id='".$post['idvr']."'");

		$id = $post['idvr'];
		}
	else
		{
		if( $post['id_creditor'] != "" )
			{
			list($total) = $babDB->db_fetch_array($babDB->db_query("select count(id) as total from ".BAB_VAC_PERSONNEL_TBL." where id_user='".$post['id_creditor']."'"));
			if( $total == 0 )
				{
				$babBody->msgerror = bab_translate("User does'nt exist") ." !";
				return false;
				}
			}
		else 
			{
			if ( $post['collid'] == -2 ) {
				list($total) = $babDB->db_fetch_array($babDB->db_query("SELECT count(p.id) as total from ".BAB_VAC_PERSONNEL_TBL." p, ".BAB_VAC_COLLECTIONS_TBL." c, ".BAB_VAC_COLL_TYPES_TBL." t WHERE t.id_type='".$post['id_type']."' and c.id = t.id_coll AND p.id_coll=c.id"));
				}
			elseif( $post['collid'] == -1 ) {
				list($total) = $babDB->db_fetch_array($babDB->db_query("select count(id) as total from ".BAB_VAC_PERSONNEL_TBL));
				}
			else
				{
				list($total) = $babDB->db_fetch_array($babDB->db_query("select count(id) as total from ".BAB_VAC_PERSONNEL_TBL." where id_coll='".$post['collid']."'"));
				}
			}

    
		$babDB->db_query("insert into ".BAB_VAC_RIGHTS_TBL." (description, id_creditor, id_type, quantity, date_entry, date_begin, date_end, active, cbalance, date_begin_valid, date_end_valid, date_begin_fixed, date_end_fixed, day_begin_fixed, day_end_fixed) values ('".$post['description']."', '".$GLOBALS['BAB_SESS_USERID']."', '".$post['id_type']."', '".$post['quantity']."', curdate(), '".$post['date_begin']."', '".$post['date_end']."', '".$post['active']."', '".$post['cbalance']."', '".$post['date_begin_valid']."', '".$post['date_end_valid']."', '".$post['date_begin_fixed']."', '".$post['date_end_fixed']."', '".$post['halfdaybeginfx']."', '".$post['halfdayendfx']."')");

		$id = $babDB->db_insert_id();

		$idusers = array();
		if( $post['id_creditor'] != "" )
			{
			$babDB->db_query("insert into ".BAB_VAC_USERS_RIGHTS_TBL." (id_user, id_right) values ('".$post['id_creditor']."', '".$id."')");
			$idusers[] = $post['id_creditor'];
			}
		else if( $post['collid'] != "" )
			{
			if ( $post['collid'] != -2)
				{
				$res = $babDB->db_fetch_array($babDB->db_query("SELECT p.* from ".BAB_VAC_PERSONNEL_TBL." p, ".BAB_VAC_COLLECTIONS_TBL." c, ".BAB_VAC_COLL_TYPES_TBL." t WHERE t.id_type='".$post['id_type']."' and c.id = t.id_coll AND p.id_coll=c.id"));
				}
			elseif( $post['collid'] != -1)
				{
				$res = $babDB->db_query("select * from ".BAB_VAC_PERSONNEL_TBL." where id_coll='".$post['collid']."'");
				}
			else
				$res = $babDB->db_query("select * from ".BAB_VAC_PERSONNEL_TBL."");

			while( $arr = $babDB->db_fetch_array($res))
				{
				$babDB->db_query("insert into ".BAB_VAC_USERS_RIGHTS_TBL." (id_user, id_right) values ('".$arr['id_user']."', '".$id."')");
				$idusers[] = $arr['id_user'];
				}
			}

		$nbidusers = count($idusers);
		if( $post['righttype'] == '2' && $nbidusers > 0) // fixed dates
			{
			for($k=0; $k < $nbidusers; $k++ )
				{
				addFixedVacation($idusers[$k], $id, $post['date_begin_fixed'] , $post['date_end_fixed'], $post['halfdaybeginfx'], $post['halfdayendfx'], '', $post['quantity']);
				}
			notifyOnVacationChange($idusers, $post['quantity'], $post['date_begin_fixed'], $post['halfdaybeginfx'], $post['date_end_fixed'], $post['halfdayendfx'], VAC_FIX_ADD);
			}
		}


		if ($post['righttype'] != '1')
			{
			$babDB->db_query("DELETE FROM ".BAB_VAC_RIGHTS_RULES_TBL." WHERE id_right='".$id."'");
			}
		else // rules
			{
			$res = $babDB->db_query("SELECT id FROM ".BAB_VAC_RIGHTS_RULES_TBL." WHERE id_right='".$id."'");
			if ($babDB->db_num_rows($res) > 0)
				{
				list($id_rule) = $babDB->db_fetch_array($res);
				$babDB->db_query("
						UPDATE ".BAB_VAC_RIGHTS_RULES_TBL." 
						SET 
							period_start='".$post['period_start']."', 
							period_end='".$post['period_end']."', 
							trigger_nbdays_min='".$post['trigger_nbdays_min']."',  trigger_nbdays_max='".$post['trigger_nbdays_max']."', 
							trigger_inperiod='".$post['trigger_inperiod']."', 
							trigger_type='".$post['trigger_type']."', 
							right_inperiod='".$post['right_inperiod']."' 
						WHERE 
							id='".$id_rule."'
						");
				}
			else
				{
				$babDB->db_query("
						INSERT INTO ".BAB_VAC_RIGHTS_RULES_TBL."
						( id_right, 
							period_start, 
							period_end, 
							trigger_nbdays_min, 
							trigger_nbdays_max, 
							trigger_inperiod, 
							trigger_type,
							right_inperiod ) 
						VALUES 
							( '".$id."',
							'".$post['period_start']."', 
							'".$post['period_end']."', 
							'".$post['trigger_nbdays_min']."', 
							'".$post['trigger_nbdays_max']."', 
							'".$post['trigger_inperiod']."', 
							'".$post['trigger_type']."', 
							'".$post['right_inperiod']."' )
							");
				}
			}

		return true;
	}

function modifyVacationRightPersonnel($idvr, $userids, $nuserids)
	{
	global $babDB;
	$count = sizeof($userids);

	$arrright = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_VAC_RIGHTS_TBL." where id='".$idvr."'"));

	for( $i = 0; $i < sizeof($nuserids); $i++)
		{
		if( $nuserids[$i] != "" && ( $count == 0 || !in_array($nuserids[$i], $userids)))
			{
			if( $arrright['date_begin_fixed'] != '0000-00-00' )
				{
				$res = $babDB->db_query("select vet.*, veet.quantity from ".BAB_VAC_ENTRIES_ELEM_TBL." veet left join ".BAB_VAC_ENTRIES_TBL." vet on veet.id_entry=vet.id where veet.id_type='".$idvr."' and vet.id_user='".$nuserids[$i]."'");
				$arr = $babDB->db_fetch_array($res);
				removeFixedVacation( $arr['id']);
				$arrnotif[] = $arr['id_user'];
				notifyOnVacationChange($arrnotif, $arr['quantity'], $arr['date_begin'], $arr['day_begin'], $arr['date_end'], $arr['day_end'], VAC_FIX_DELETE);
				}

			$babDB->db_query("delete from ".BAB_VAC_USERS_RIGHTS_TBL." where id_right='".$idvr."' and id_user='".$nuserids[$i]."'");
			}
		}

	for( $i = 0; $i < $count; $i++)
		{
		if( !in_array($userids[$i], $nuserids) )
			{
			$babDB->db_query("insert into ".BAB_VAC_USERS_RIGHTS_TBL." (id_user, id_right) values ('".$userids[$i]."', '".$idvr."')");
			if( $arrright['date_begin_fixed'] != '0000-00-00' )
				{
				addFixedVacation($userids[$i], $idvr, $arrright['date_begin_fixed'] , $arrright['date_end_fixed'], $arrright['day_begin_fixed'], $arrright['day_end_fixed'], '', $arrright['quantity']);
				$arrnotif[] = $userids[$i];
				notifyOnVacationChange($arrnotif, $arrright['quantity'], $arrright['date_begin_fixed'], $arrright['day_begin_fixed'], $arrright['date_end_fixed'], $arrright['day_end_fixed'], VAC_FIX_ADD);
				}
			}
		}
	}

function deleteVacationRight($idvr)
	{
	global $babBody, $babDB;
	list($total) = $babDB->db_fetch_row($babDB->db_query("select count(id) as total from ".BAB_VAC_ENTRIES_ELEM_TBL." where id_type='".$idvr."'"));
	if( $total > 0 )
		{
		$babBody->msgerror = bab_translate("Can't delete this vacation right. It's used elsewhere");
		return;
		}
	else
		$babDB->db_query("delete from ".BAB_VAC_RIGHTS_TBL." where id='".$idvr."'");
	}

/* main */
$acclevel = bab_vacationsAccess();
if( !isset($acclevel['manager']) || $acclevel['manager'] != true)
	{
	$babBody->msgerror = bab_translate("Access denied");
	return;
	}

if( !isset($idx))
	$idx = "lrig";

if( isset($_POST['action']) )
	{
	switch ($_POST['action'])
		{
		case 'rightsedit':
			if( isset($_POST['submit'] ))
				{
				if(!updateVacationRight())
					$idx ='modvr';

				}
			else if( isset($_POST['deleteg']))
				{
				deleteVacationRight($_POST['idvr']);
				}
			break;
		}

	}

$babBody->addItemMenu("vacuser", bab_translate("Vacations"), $GLOBALS['babUrlScript']."?tg=vacuser");
$babBody->addItemMenu("menu", bab_translate("Management"), $GLOBALS['babUrlScript']."?tg=vacadm&idx=menu");

switch($idx)
	{
	case "browt":
		if( !isset($pos)) $pos ="";
		$babBody->title = bab_translate("Personnel associated with type");
		browsePersonnelByType($pos, $cb, $idtype);
		exit;
		break;
	case "viewvr":
		viewVacationRightPersonnel($idvr);
		exit;
		break;

	case "delvru":
		if (!isset($userids))
			$userids = array();
		modifyVacationRightPersonnel($idvr, $userids, $nuserids);
		/* no break; */
	case "lvrp":
		if( !isset($pos)) $pos ="";
		if( isset($chg))
		{
			if( $pos[0] == "-")
				$pos = $pos[1];
			else
				$pos = "-" .$pos;
		}
		listVacationRightPersonnel($pos, $idvr);
		exit;
		break;

	case "modvr":
		
		addModifyVacationRigths($_REQUEST['idvr']);

		$babBody->title = bab_translate("Modify vacation right");
		$babBody->addItemMenu("lrig", bab_translate("Rights"), $GLOBALS['babUrlScript']."?tg=vacadma&idx=lrig");
		$babBody->addItemMenu("modvr", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=vacadma&idx=modvr");
		break;

	case "addvr":
		
		addModifyVacationRigths();

		$babBody->title = bab_translate("Allocate vacation rights");
		$babBody->addItemMenu("lrig", bab_translate("Rights"), $GLOBALS['babUrlScript']."?tg=vacadma&idx=lrig");
		$babBody->addItemMenu("addvr", bab_translate("Add"), $GLOBALS['babUrlScript']."?tg=vacadma&idx=addvr");
		break;

	case "lrig":
	default:
		$babBody->title = bab_translate("Vacations rights");

		if( !isset($datee)) $datee ="";
		if( !isset($dateb)) $dateb ="";
		if( !isset($idtype)) $idtype ="";
		if( !isset($idcreditor)) $idcreditor ="";
		if( !isset($active)) $active ="Y";
		if( !isset($pos) || $pos == '' ) $pos =0;
		if( !isset($idcol)) $idcol ="";
		if( !isset($idsa)) $idsa ="";

		listVacationRigths($idtype, $idcreditor, $dateb, $datee, $active, $pos);
		$babBody->addItemMenu("lrig", bab_translate("Rights"), $GLOBALS['babUrlScript']."?tg=vacadma&idx=lrig");
		$babBody->addItemMenu("addvr", bab_translate("Add"), $GLOBALS['babUrlScript']."?tg=vacadma&idx=addvr");
		break;
	}
$babBody->setCurrentItemMenu($idx);
?>