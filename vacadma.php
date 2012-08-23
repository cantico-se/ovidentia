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
include_once $babInstallPath."utilit/vacfixedincl.php";

define("VAC_MAX_RIGHTS_LIST", 20);



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
		var $count;
		var $res;

		var $pos;

		var $userid;

		var $nickname;

		function temp($pos, $cb, $idtype)
			{
			$this->allname = bab_translate("All");
			$this->nickname = bab_translate("Login ID");
			global $babDB;
			$this->cb = $cb;
			$this->idtype = $idtype;

			if( mb_strlen($pos) > 0 && $pos[0] == "-" )
				{
				$this->pos = mb_strlen($pos)>1? $pos[1]: '';
				$this->ord = $pos[0];
				$req = "select * from ".BAB_USERS_TBL." where lastname like '".$babDB->db_escape_string($this->pos)."%' order by lastname, firstname asc";
				$this->fullname = bab_translate("Lastname"). " " . bab_translate("Firstname");
				$this->fullnameurl = $GLOBALS['babUrlScript']."?tg=vacadma&idx=browt&chg=&pos=".$this->pos."&idtype=".$this->idtype."&cb=".$this->cb;
				}
			else
				{
				$this->pos = $pos;
				$this->ord = "";
				$req = "select * from ".BAB_USERS_TBL." where firstname like '".$babDB->db_escape_string($this->pos)."%' order by firstname, lastname asc";
				$this->fullname = bab_translate("Firstname"). " " . bab_translate("Lastname");
				$this->fullnameurl = $GLOBALS['babUrlScript']."?tg=vacadma&idx=browt&chg=&pos=-".$this->pos."&idtype=".$this->idtype."&cb=".$this->cb;
				}


			$this->res = $babDB->db_query($req);
			$this->count = $babDB->db_num_rows($this->res);

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
				global $babDB;
				$this->arr = $babDB->db_fetch_array($this->res);
				$this->bview = false;



				$res = $babDB->db_query("select id_coll from ".BAB_VAC_PERSONNEL_TBL." where id_user='".$babDB->db_escape_string($this->arr['id'])."'");
				if( $this->idtype != "" )
					{
					while( $arr = $babDB->db_fetch_array($res))
						{
						$res2 = $babDB->db_query("select id from ".BAB_VAC_COLL_TYPES_TBL." where id_type='".$babDB->db_escape_string($this->idtype)."' and id_coll ='".$babDB->db_escape_string($arr['id_coll'])."'");
						if( $res2 && $babDB->db_num_rows($res2) > 0 )
							{

							$this->bview = true;
							break;
							}
						}
					}
				else if( $res && $babDB->db_num_rows($res) > 0 )
					$this->bview = true;

				if( $this->bview )
					{
					$this->firstlast = bab_composeUserName($this->arr['firstname'],$this->arr['lastname']);
					$this->firstlast = bab_toHtml($this->firstlast, BAB_HTML_JS);
					if( $this->ord == "-" )
						$this->urlname = bab_toHtml(bab_composeUserName($this->arr['lastname'],$this->arr['firstname']));
					else
						$this->urlname = bab_toHtml(bab_composeUserName($this->arr['firstname'],$this->arr['lastname']));
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
			global $BAB_SESS_USERID, $babDB;
			static $k = 0;
			static $t = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
			if( $k < 26)
				{
				$this->selectname = mb_substr($t, $k, 1);
				$this->selecturl = $GLOBALS['babUrlScript']."?tg=vacadma&idx=browt&pos=".$this->ord.$this->selectname."&idtype=".$this->idtype."&cb=".$this->cb;

				if( $this->pos == $this->selectname)
					$this->selected = 1;
				else
					{
					if( $this->ord == "-" )
						$req = "select ".BAB_USERS_TBL.".id from ".BAB_USERS_TBL." join ".BAB_VAC_PERSONNEL_TBL." where lastname like '".$babDB->db_escape_string($this->selectname)."%' and ".BAB_USERS_TBL.".id = ".BAB_VAC_PERSONNEL_TBL.".id_user";
					else
						$req = "select ".BAB_USERS_TBL.".id from ".BAB_USERS_TBL." join ".BAB_VAC_PERSONNEL_TBL." where firstname like '".$babDB->db_escape_string($this->selectname)."%' and ".BAB_USERS_TBL.".id = ".BAB_VAC_PERSONNEL_TBL.".id_user";
					$res = $babDB->db_query($req);
					if( $babDB->db_num_rows($res) > 0 )
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


	$babBody->babPopup(bab_printTemplate($temp, "vacadma.html", "browseusers"));


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
		var $altbg = true;

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
			$this->statustxt = bab_translate("Active");
			$this->activeyes = bab_translate("Opened rights");
			$this->activeno = bab_translate("Closed rights");
			$this->closedtxt = bab_translate("Vac. closed");
			$this->openedtxt = bab_translate("Vac. opened");
			$this->alttxt = bab_translate("Modify");
			$this->t_edit = bab_translate("Modification");
			$this->t_first_page = bab_translate("First page");
			$this->t_previous_page = bab_translate("Previous page");
			$this->t_next_page = bab_translate("Next page");
			$this->t_last_page = bab_translate("Last page");
			$this->t_available = bab_translate("Availability");
			$this->topurl = "";
			$this->bottomurl = "";
			$this->nexturl = "";
			$this->prevurl = "";
			$this->yselected = "";
			$this->nselected = "";
			$this->t_position = '';
			global $babDB;

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

			$req = "".BAB_VAC_RIGHTS_TBL." r LEFT JOIN ".BAB_VAC_TYPES_TBL." t ON t.id=r.id_type ";
			if( $idtype != "" || $idcreditor != "" || $dateb != "" || $datee != ""|| $active != "")
				{
				$req .= " where ";

				if( $idtype != "")
					$aaareq[] = "r.id_type='".$babDB->db_escape_string($idtype)."'";

				if( $active != "")
					$aaareq[] = "r.active='".$babDB->db_escape_string($active)."'";

				if( $idcreditor != "")
					{
					$aaareq[] = "r.id_creditor='".$babDB->db_escape_string($idcreditor)."'";
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
					$aaareq[] = "( r.date_entry between '".$babDB->db_escape_string($dateb)."' and '".$babDB->db_escape_string($datee)."')";
					}
				else if( $dateb == "" && $datee != "" )
					{
					$aaareq[] = "r.date_entry <= '".$babDB->db_escape_string($datee)."'";
					}
				else if ($dateb != "" )
					{
					$aaareq[] = "r.date_entry >= '".$babDB->db_escape_string($dateb)."'";
					}
				}

			if( isset($aaareq) && sizeof($aaareq) > 0 )
				{
				if( sizeof($aaareq) > 1 )
					$req .= implode(' and ', $aaareq);
				else
					$req .= $aaareq[0];
				}
			$req .= " order by r.date_entry desc";

			list($total) = $babDB->db_fetch_row($babDB->db_query("select count(*) as total from ".$req));

			if( $total > VAC_MAX_RIGHTS_LIST )
				{

				$page_number = 1 + ($pos / VAC_MAX_RIGHTS_LIST);
				$page_total = 1 + ($total / VAC_MAX_RIGHTS_LIST);
				$this->t_position = sprintf(bab_translate("Page %d/%d"), $page_number,$page_total);


				$tmpurl = $GLOBALS['babUrlScript']."?tg=vacadma&idx=lrig&idtype=".$this->idtype."&idcreditor=".$this->idcreditor."&dateb=".$this->dateb."&datee=".$this->datee."&active=".$this->active."&pos=";
				if( $pos > 0)
					{
					$this->topurl = $tmpurl."0";
					}

				$next = $pos - VAC_MAX_RIGHTS_LIST;
				if( $next >= 0)
					{
					$this->prevurl = $tmpurl.$next;
					}

				$next = $pos + VAC_MAX_RIGHTS_LIST;
				if( $next < $total)
					{
					$this->nexturl = $tmpurl.$next;
					if( $next + VAC_MAX_RIGHTS_LIST < $total)
						{
						$bottom = $total - VAC_MAX_RIGHTS_LIST;
						}
					else
						$bottom = $next;
					$this->bottomurl = $tmpurl.$bottom;
					}
				}


			if( $total > VAC_MAX_RIGHTS_LIST)
				{
				$req .= " limit ".$pos.",".VAC_MAX_RIGHTS_LIST;
				}
			$this->res = $babDB->db_query("select r.*, t.name type from ".$req);
			$this->count = $babDB->db_num_rows($this->res);
			$this->addurl = $GLOBALS['babUrlScript']."?tg=vacadma&idx=addvr";

			$this->restype = $babDB->db_query("select * from ".BAB_VAC_TYPES_TBL." order by name asc");
			$this->counttype = $babDB->db_num_rows($this->restype);

			$this->resc= $babDB->db_query("select distinct id_creditor from ".BAB_VAC_RIGHTS_TBL."");
			$this->countc = $babDB->db_num_rows($this->resc);

			$this->dateburl = $GLOBALS['babUrlScript']."?tg=month&callback=dateBegin&ymin=0&ymax=3";
			$this->dateeurl = $GLOBALS['babUrlScript']."?tg=month&callback=dateEnd&ymin=0&ymax=3";

			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				global $babDB;
				$this->altbg = !$this->altbg;
				$arr = $babDB->db_fetch_array($this->res);

				$this->vrurl		= bab_toHtml($GLOBALS['babUrlScript']."?tg=vacadma&idx=modvr&idvr=".$arr['id']);
				$this->vrviewurl	= bab_toHtml($GLOBALS['babUrlScript']."?tg=vacadma&idx=viewvr&idvr=".$arr['id']);
				$this->typename		= bab_toHtml($arr['type']);
				$this->description	= bab_toHtml($arr['description']);
				$this->quantity		= bab_toHtml(bab_vac_quantity($arr['quantity'], $arr['quantity_unit']));
				$this->creditor		= bab_toHtml(bab_getUserName($arr['id_creditor']));
				$this->date			= bab_toHtml(bab_shortDate(bab_mktime($arr['date_entry']), false));
				$this->bclose		= $arr['active'] == "N";

				$available = true;
				if ('0000-00-00' !== $arr['date_begin_valid'] && $arr['date_begin_valid'] > date('Y-m-d')) {
					$available = false;
				}

				if ('0000-00-00' !== $arr['date_end_valid'] && $arr['date_end_valid'] < date('Y-m-d')) {
					$available = false;
				}

				// vacations with fixed dates are not available when the dates are gone
				if( $arr['date_end_fixed'] != '0000-00-00' && $arr['date_end_fixed'] < date('Y-m-d')){
					$available= false;
				}

				$this->available	= $available ? bab_translate('Available') : '';

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
				global $babDB;
				$arr = $babDB->db_fetch_array($this->restype);
				$this->typename = bab_toHtml($arr['name']);
				$this->typeid = bab_toHtml($arr['id']);
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
				global $babDB;
				$arr = $babDB->db_fetch_array($this->resc);
				$this->creditorname = bab_toHtml(bab_getUserName($arr['id_creditor']));
				$this->creditorid = bab_toHtml($arr['id_creditor']);
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



/**
 * Add modify vacation right
 * @param	int	$id
 */
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
			$this->t_quantity = bab_translate("Quantity for this right");
			$this->t_reset = bab_translate("Reset");
			$this->t_delete = bab_translate("Delete");
			$this->t_orand = bab_translate("Or users having ");
			$this->t_allcol = bab_translate("All collections");
			$this->t_allpers = bab_translate("All users");
			$this->t_days = bab_translate("Day(s)");
			$this->t_hours = bab_translate("Hour(s)");
			$this->t_description = bab_translate("Name of the right");
			$this->t_period = bab_translate("Right period"). " (".bab_translate("dd-mm-yyyy").")";
			$this->t_date_begin = $this->t_period_start = bab_translate("Begin");
			$this->t_date_end = $this->t_period_end = bab_translate("End");
			$this->t_active = bab_translate("Active");
			$this->t_cbalance = bab_translate("Accept negative balance");
			$this->t_use_rules = bab_translate("Use rules");
			$this->t_trigger_nbdays_min = bab_translate("Minimum number of days");
			$this->t_trigger_nbdays_max = bab_translate("Maximum number of days");
			$this->t_period_rule = bab_translate("in this period");
			$this->t_always = bab_translate("Always");
			$this->t_all_period = bab_translate("On all right period");

			$this->t_inperiod = bab_translate("In rule period");
			$this->t_outperiod = bab_translate("Out of rule period");

			$this->t_inperiod_js = bab_toHtml(bab_translate("In rule period"), BAB_HTML_JS);
			$this->t_outperiod_js = bab_toHtml(bab_translate("Out of rule period"), BAB_HTML_JS);

			$this->t_outperiod2 = bab_translate("Out of rule period and in right period");
			$this->t_right_inperiod = bab_translate("The right is available if the vacation request is");
			$this->t_record = bab_translate("Record");
			$this->t_trigger_type = bab_translate("Allow rule with type");
			$this->t_all = bab_translate("All");
			$this->t_periodvalid = bab_translate("Retention period");
			$this->t_periodvalid_help1 = bab_translate("The right is available if the request is in the period");
			$this->t_periodvalid_help2 = bab_translate("if empty, the right will be available with others conditions");
			$this->t_right_type = bab_translate("Nature of the right");
			$this->t_no_distribution = bab_translate("Distribution on request");

			$this->t_datebegintxt = bab_translate("Begin date");
			$this->t_dateendtxt = bab_translate("End date");
			$this->invaliddate = bab_translate("ERROR: End date must be older");
			$this->invalidentry2 = bab_translate("Days must be multiple of 0.5");
			$this->invalidentry3 = bab_translate("The number of days exceed the total allowed");
			$this->t_rules = bab_translate("Rules");
			$this->t_trigger = bab_translate("Right assignement in function of requested days");
			$this->t_trigger_nbdays = bab_translate("The right is displayed if the user has requested");
			$this->t_between = bab_translate("between");
			$this->t_and = bab_translate("and");

			$this->t_at_least = bab_translate("at least");
			$this->t_but_less_than = bab_translate("but less than");

			$this->t_vacation_type = bab_translate("vacation of type");
			$this->t_zoneapplication = bab_translate("Zone of application of the rule");
			$this->t_validoverlap = bab_translate("Allow overlap between the request period and the test periods");
			$this->t_inperiod_strict = bab_translate("in zone of application");
			$this->t_inperiod_or_overlap = bab_translate("in or overlap zone of application");

			$this->t_assignment = bab_translate("Personnel assignement :");
			$this->t_assignment_type = bab_translate("Assignement type");
			$this->t_by_user = bab_translate("By user");
			$this->t_by_coll = bab_translate("By collection");
			$this->t_by_group = bab_translate("By groups");
			$this->t_id_groups = bab_translate("Groups");

			$this->t_trigger_p1 = bab_translate("First period");
			$this->t_trigger_p2 = bab_translate("Second period");
			$this->t_trigger_overlap = bab_translate("Use vacation requests witch overlap the test period");

			$this->t_id_rgroup = bab_translate("Right group");
			$this->yes = bab_translate("Yes");
			$this->no = bab_translate("No");
			$this->t_add_period = bab_translate("Add a test period");
			$this->invalidentry1 = bab_toHtml(bab_translate("Invalid entry!  Only numbers are accepted or . !"),BAB_HTML_JS);
			$this->invalidtotal = bab_toHtml(bab_translate("Total days does'nt fit between dates"),BAB_HTML_JS);


			$this->usersbrowurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=vacadma&idx=browt");
			global $babDB;
			$el_to_init = array(
				'idvr',
				'id_creditor',
				'date_begin',
				'date_end',
				'quantity',
				'id_type',
				'description',
				'active',
				'cbalance',
				'period_start',
				'period_end',
				'trigger_nbdays_min',
				'trigger_nbdays_max',
				'trigger_p1_begin',
				'trigger_p1_end',
				'trigger_p2_begin',
				'trigger_p2_end',
				'right_inperiod',
				'righttype',
				'trigger_type',
				'date_begin_valid',
				'date_end_valid',
				'validoverlap',
				'id_rgroup'
			);

			$dates_to_init = array(
				'date_begin', 			// date de debut de la periode theorique du droit
				'date_end',
				'date_begin_valid', 	// Disponibilite en fonction de la date de saisie de la demande de conges
				'date_end_valid',
				'trigger_p1_begin', 	// Attribution du droit en fonction des jours demandes et valides
				'trigger_p1_end',
				'trigger_p2_begin', 	// Attribution du droit en fonction des jours demandes et valides
				'trigger_p2_end'
			);


			$this->arr['righttype'] = '0';
			if (isset($_POST) && count($_POST) > 0)
				{
				$this->arr = $_POST;
				}
			elseif ($id)
				{
				$this->arr = $babDB->db_fetch_array($babDB->db_query(
					"SELECT
						t1.*,
						t2.id righttype,
						t2.trigger_nbdays_min,
						t2.trigger_nbdays_max,
						t2.trigger_p1_begin,
						t2.trigger_p1_end,
						t2.trigger_p2_begin,
						t2.trigger_p2_end,
						t2.trigger_type,
						t2.trigger_overlap,
						t2.validoverlap,
						t3.name type
					FROM
						".BAB_VAC_RIGHTS_TBL." t1
					LEFT JOIN
						".BAB_VAC_RIGHTS_RULES_TBL." t2
					ON t2.id_right=t1.id
					LEFT JOIN
						".BAB_VAC_TYPES_TBL." t3
					ON t3.id = t1.id_type
					WHERE t1.id='".$babDB->db_escape_string($id)."'"));

				$this->collid = "";

				if( $this->arr['date_begin_fixed'] != '0000-00-00 00:00:00')
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
				$default = array('id_type' => $_GET['idtype'], 'active' => 'Y');
			else
				$default = array('active' => 'Y');

			foreach($el_to_init as $field)
				{
				if ( !isset($this->arr[$field]) )
					$this->arr[$field] = isset($default[$field]) ? $default[$field] : '';
				}

			$this->arr['type'] = isset($this->arr['type']) ? bab_toHtml($this->arr['type']) : '';

			if( $this->arr['id_creditor'] != "" )
				$this->arr['id_creditorDisplay'] = bab_toHtml(bab_getUserName($this->arr['id_creditor']));
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

			$this->restype = $babDB->db_query("select * from ".BAB_VAC_TYPES_TBL." order by name asc");
			$this->counttype = $babDB->db_num_rows($this->restype);


			$this->year = isset($_POST['year']) ? $_POST['year'] : date('Y');

			if (!empty($id) && !isset($_POST['daybeginfx']) && $this->arr['date_begin_fixed'] != '0000-00-00 00:00:00')
				{
				list($this->yearbegin, $this->monthbegin, $this->daybegin, $this->hourbegin) = $this->getDateArr($this->arr['date_begin_fixed']);
				list($this->yearend, $this->monthend, $this->dayend, $this->hourend) = $this->getDateArr($this->arr['date_end_fixed']);
				}
			elseif (isset($_POST['daybeginfx']))
				{
				$this->daybegin = $_POST['daybeginfx'];
				$this->dayend = $_POST['dayendfx'];
				$this->monthbegin = $_POST['monthbeginfx'];
				$this->monthend = $_POST['monthendfx'];
				$this->yearbegin = $this->year+ $_POST['yearbeginfx']-1;
				$this->yearend = $this->year+ $_POST['yearendfx']-1;
				$this->hourbegin = bab_pp('hourbegin');
				$this->hourend = bab_pp('hourend');
				}
			else
				{
				$this->daybegin = date("j");
				$this->dayend = date("j");
				$this->monthbegin = date("n");
				$this->monthend = date("n");
				$this->yearbegin = $this->year;
				$this->yearend = $this->year;
				$this->hourbegin = '00:00:00';
				$this->hourend = '23:59:59';
				}

			$this->hoursel = $this->hourbegin;
			$this->daysel = $this->daybegin;
			$this->monthsel = $this->monthbegin;
			$this->yearsel = $this->yearbegin -$this->year +1;
			$this->nbmonthdays = date( "j", mktime(0, 0, 0, $this->monthsel + 1, 1, $this->yearsel) - 1 );

			if( $id )
				{
				if( $this->arr['righttype'] == '2' )
					$this->rightypes = array(2 => bab_translate("Fixed dates"));
				else
					$this->rightypes = array(1 => bab_translate("Default"));
				}
			else
				{
				$this->rightypes = array( 1 => bab_translate("Default"), 2 => bab_translate("Fixed dates"));
				}


			$this->hours = bab_vac_hoursList();
				
				
			$this->res_rgroup = $babDB->db_query("SELECT * FROM ".BAB_VAC_RGROUPS_TBL."");


			$this->res_inperiod = $babDB->db_query('
				SELECT * FROM '.BAB_VAC_RIGHTS_INPERIOD_TBL.'
				WHERE id_right='.$babDB->quote($this->arr['idvr']).' ORDER BY period_start'
			);

			}
			
			
		private function getDateArr($datetime)
		{
			if (preg_match('/^([0-9]{4})-([0-9]{2})-([0-9]{2}) ([0-9]{2}:[0-9]{2}:[0-9]{2})$/', $datetime, $m))
			{
				return array($m[1], $m[2], $m[3], $m[4]);
			}
			return array('0000', '00', '00', '00:00:00');
		}

		public function getnextrighttype()
			{
			if (list($valid,$valname) = each($this->rightypes))
				{
				$this->righttypeid = bab_toHtml($valid);
				$this->righttypeval = bab_toHtml($valname);
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

		public function getnextcol()
			{
			static $j= 0;
			if( $j < $this->countcol )
				{
				global $babDB;

				$arr = $babDB->db_fetch_array($this->colres);
				$this->collval 		= bab_toHtml($arr['name'], BAB_HTML_JS);
				$this->idcollection = bab_toHtml($arr['id']);
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

		public function getnexttype()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->counttype)
				{
				$this->iindex = $i;
				$arr = $babDB->db_fetch_array($this->restype);
				$this->typename = bab_toHtml($arr['name']);
				$this->typeid = bab_toHtml($arr['id']);
				if( $arr['cbalance'] == 'Y' )
					{
					$this->tcbalance = 0;
					}
				else
					{
					$this->tcbalance = 1;
					}
				$this->colres = $babDB->db_query("
				select ".BAB_VAC_COLLECTIONS_TBL.".* from ".BAB_VAC_COLLECTIONS_TBL." join ".BAB_VAC_COLL_TYPES_TBL." where ".BAB_VAC_COLL_TYPES_TBL.".id_type='".$babDB->db_escape_string($this->typeid)."' and ".BAB_VAC_COLLECTIONS_TBL.".id=".BAB_VAC_COLL_TYPES_TBL.".id_coll
				");

				$this->countcol = $babDB->db_num_rows($this->colres);

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
					$babDB->db_data_seek($this->restype, 0 );
				$i = 0;
				return false;
				}

			}
		public function getnextday()
			{
			static $i = 1;

			if( $i <= $this->nbmonthdays)
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
				$this->nbmonthdays = date( "j", mktime(0, 0, 0, $this->monthend + 1, 1, $this->yearend) - 1 );;
				$this->daysel = $this->dayend;
				$i = 1;
				return false;
				}

			}

		public function getnextmonth()
			{
			static $i = 1;

			if( $i < 13)
				{
				$this->monthid = $i;
				$this->t_monthname = bab_toHtml(bab_DateStrings::getMonth($i));
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
		public function getnextyear()
			{
			static $i = -10;
			if( $i < 10)
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
				$i = -10;
				return false;
				}

			}
			
		public function getnexthour()
		{
			

			static $i = 0;

			if(list($value, $option) = each($this->hours))
				{
				$this->option = bab_toHtml($option);
				$this->value = bab_toHtml($value);
				
				if( $this->hoursel == $value )
					$this->selected = "selected";
				else
					$this->selected = "";
				$i++;
				return true;
				}
			
				reset($this->hours);
				$this->hoursel = $this->hourend;
				return false;
			

		}


		function getnextrgroup() {
				global $babDB;

				if ($arr = $babDB->db_fetch_assoc($this->res_rgroup)) {
					$this->rg_id	= bab_toHtml($arr['id']);
					$this->rg_name	= bab_toHtml($arr['name']);
					$this->selected	= $arr['id'] == $this->arr['id_rgroup'];
					return true;
				}

				return false;
			}






		function getnextinperiod() {
				global $babDB;
				static $neverempty = true;
				static $i = 0;
				$n = $babDB->db_num_rows($this->res_inperiod);

				if (0 === $n && $neverempty) {
					$this->right_inperiod = 1;
					$this->period_start = '';
					$this->period_end = '';
					$neverempty = false;
					$i++;
					return true;
				}


				if ($arr = $babDB->db_fetch_assoc($this->res_inperiod)) {

					$this->right_inperiod = $arr['right_inperiod'];

					list($y,$m,$d) = explode('-',$arr['period_start']);
					$this->period_start = $d.'-'.$m.'-'.$y;

					list($y,$m,$d) = explode('-',$arr['period_end']);
					$this->period_end = $d.'-'.$m.'-'.$y;

					if (0 < $i) {
						$this->removebutton = true;
					}

					$i++;
					return true;
				}

				return false;
			}





		}

	$temp = new temp($id);

	$babBody->addJavascriptFile($GLOBALS['babInstallPath'].'scripts/bab_dialog.js');
	$babBody->addStyleSheet('vac_rights.css');
	$babBody->babecho(bab_printTemplate($temp,"vacadma.html", "rightsedit"));
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

			global $babDB;
			$this->idvr = $idvr;
			list($this->idtype) = $babDB->db_fetch_row($babDB->db_query("select id_type from ".BAB_VAC_RIGHTS_TBL." where id='".$babDB->db_escape_string($idvr)."'"));

			if( isset($pos[0]) && $pos[0] == "-" )
				{
				$this->pos = $pos[1];
				$this->ord = $pos[0];
				$req = "select ".BAB_USERS_TBL.".*, ".BAB_VAC_PERSONNEL_TBL.".id_coll from ".BAB_USERS_TBL." join ".BAB_VAC_PERSONNEL_TBL." where ".BAB_USERS_TBL.".id = ".BAB_VAC_PERSONNEL_TBL.".id_user and lastname like '".$babDB->db_escape_string($this->pos)."%' order by lastname, firstname asc";
				$this->fullname = bab_toHtml(bab_translate("Lastname"). " " . bab_translate("Firstname"));

				$this->fullnameurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=vacadma&idx=lvrp&chg=&pos=".$this->ord.$this->pos."&idvr=".$this->idvr);
				}
			else
				{
				$this->pos = $pos;
				$this->ord = "";
				$req = "select ".BAB_USERS_TBL.".*, ".BAB_VAC_PERSONNEL_TBL.".id_coll from ".BAB_USERS_TBL." join ".BAB_VAC_PERSONNEL_TBL." where ".BAB_USERS_TBL.".id = ".BAB_VAC_PERSONNEL_TBL.".id_user and firstname like '".$babDB->db_escape_string($this->pos)."%' order by firstname, firstname asc";
				$this->fullname = bab_toHtml(bab_translate("Firstname"). " " . bab_translate("Lastname"));
				$this->fullnameurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=vacadma&idx=lvrp&chg=&pos=".$this->ord.$this->pos."&idvr=".$this->idvr);
				}
			$this->res = $babDB->db_query($req);
			$this->count = $babDB->db_num_rows($this->res);

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
				global $babDB;
				$this->arr = $babDB->db_fetch_array($this->res);
				$this->bview = false;
				$this->used = 0;
				$this->selected = "";
				$this->nuserid = "";
				$this->bview = true;
				$this->altbg = !$this->altbg;

				$res2 = $babDB->db_query("select id, quantity from ".BAB_VAC_USERS_RIGHTS_TBL." where id_user='".$babDB->db_escape_string($this->arr['id'])."' AND id_right ='".$babDB->db_escape_string($this->idvr)."'");
				if( $res2 && $babDB->db_num_rows($res2) > 0 )
					{
					$arr = $babDB->db_fetch_array($res2);
					$this->selected = "checked";
					$this->nuserid = $this->arr['id'];

					$res3 = $babDB->db_query("select SUM(e2.quantity) used from ".BAB_VAC_ENTRIES_ELEM_TBL." e2, ".BAB_VAC_ENTRIES_TBL." e1 WHERE  e2.id_right='".$babDB->db_escape_string($this->idvr)."' AND e2.id_entry = e1.id AND e1.id_user='".$babDB->db_escape_string($this->arr['id'])."' AND e1.status='Y'");

					$arr3 = $babDB->db_fetch_array($res3);
					if (isset($arr3['used']))
						$this->used = $arr3['used'];
					}

				$this->url = bab_toHtml($GLOBALS['babUrlScript']."?tg=vacadma&idx=modp&idp=".$this->arr['id']."&pos=".$this->ord.$this->pos."&idvr=".$this->idvr);
				if( $this->ord == "-" )
					$this->urlname = bab_toHtml(bab_composeUserName($this->arr['lastname'],$this->arr['firstname']));
				else
					$this->urlname = bab_toHtml(bab_composeUserName($this->arr['firstname'],$this->arr['lastname']));

				if( isset($arr['quantity']) && $arr['quantity'] != '' )
					$this->quantity = $arr['quantity'];
				else
				{
					list($this->quantity) = $babDB->db_fetch_row($babDB->db_query("select quantity from ".BAB_VAC_RIGHTS_TBL." where id='".$babDB->db_escape_string($this->idvr)."'"));
				}

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
				global $babDB;
				$this->selectname = mb_substr($t, $k, 1);
				$this->selecturl = bab_toHtml($GLOBALS['babUrlScript']."?tg=vacadma&idx=lvrp&pos=".$this->ord.$this->selectname."&idvr=".$this->idvr);

				if( $this->pos == $this->selectname)
					$this->selected = 1;
				else
					{
					if( $this->ord == "-" )
						{
						$req = "select ".BAB_USERS_TBL.".id from ".BAB_USERS_TBL." join ".BAB_VAC_PERSONNEL_TBL." where ".BAB_USERS_TBL.".id=".BAB_VAC_PERSONNEL_TBL.".id_user and ".BAB_USERS_TBL.".lastname like '".$babDB->db_escape_string($this->selectname)."%' ";
						}
					else
						{
						$req = "select ".BAB_USERS_TBL.".id from ".BAB_USERS_TBL." join ".BAB_VAC_PERSONNEL_TBL." where ".BAB_USERS_TBL.".id=".BAB_VAC_PERSONNEL_TBL.".id_user and ".BAB_USERS_TBL.".firstname like '".$babDB->db_escape_string($this->selectname)."%' ";
						}
					$res = $babDB->db_query($req);
					if( $babDB->db_num_rows($res) > 0 )
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
			$this->datebegintxt = bab_translate("Begin date");
			$this->dateendtxt = bab_translate("End date");
			$this->dateentrytxt = bab_translate("Entry date");
			$this->quantitytxt = bab_translate("Quantity");
			$this->typetxt = bab_translate("Vacation type");
			$this->creditortxt = bab_translate("Author");
			$this->statustxt = bab_translate("Status");
			$this->validperiodtxt = bab_translate("Retention period");
			$this->t_from = bab_translate("date_from");
			$this->t_to = bab_translate("date_to");
			global $babDB;

			$row = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_VAC_RIGHTS_TBL." where id='".$babDB->db_escape_string($idvr)."'"));
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
			$GLOBALS['babBody']->title = $row['description'];
			$this->creditor = bab_getUserName($row['id_creditor']);
			$this->quantity = $row['quantity'];
			$this->status = $row['active'] == "Y"? bab_translate("Right opened"): bab_translate("Right closed");
			list($this->type) = $babDB->db_fetch_row($babDB->db_query("select name from ".BAB_VAC_TYPES_TBL." where id='".$babDB->db_escape_string($row['id_type'])."'"));
			}

		}

	$temp = new temp($idvr);
	$babBody->babPopup(bab_printTemplate($temp, "vacadma.html", "viewvacright"));
	}



function rgrouplist() {

	global $babBody;
	class temp
		{
		var $altbg = true;

		function temp()
			{
			$this->t_name = bab_translate('Name');
			$this->t_edit = bab_translate('Edit');
			$this->t_rights = bab_translate('Rights');
			global $babDB;
			$this->res = $babDB->db_query("SELECT * FROM ".BAB_VAC_RGROUPS_TBL."");
			}

		function getnext()
			{
			global $babDB;
			if ($arr = $babDB->db_fetch_assoc($this->res)) {
				$this->altbg		= !$this->altbg;
				$this->name			= bab_toHtml($arr['name']);
				$this->id_rgroup	= bab_toHtml($arr['id']);


				$this->rgroup = $babDB->db_query("SELECT description FROM ".BAB_VAC_RIGHTS_TBL." WHERE id_rgroup=".$babDB->quote($arr['id']));

				return true;
			}
			return false;
		}

		function getnextright() {
			global $babDB;
			if ($arr = $babDB->db_fetch_assoc($this->rgroup)) {
				$this->description = bab_toHtml($arr['description']);
				return true;
			}
			return false;
		}

	}

	$temp = new temp();
	$babBody->babecho(bab_printTemplate($temp, "vacadma.html", "rgrouplist"));
}



function rgroupmod() {

	global $babBody;
	class temp
		{
		function temp()
			{
			$this->t_name = bab_translate('Name');
			$this->t_record = bab_translate('Record');
			$this->t_delete = bab_translate('Delete');
			global $babDB;
			$this->id_rgroup = bab_rp('id_rgroup');
			if ($this->id_rgroup) {
				$res = $babDB->db_query("SELECT * FROM ".BAB_VAC_RGROUPS_TBL." WHERE id=".$babDB->quote($this->id_rgroup));
				$arr = $babDB->db_fetch_assoc($res);
				$this->name = bab_toHtml($arr['name']);
			} else {
				$this->name = '';
			}
			}
		}

	$temp = new temp();
	$babBody->babecho(bab_printTemplate($temp, "vacadma.html", "rgroupmod"));
}



function updateVacationRight()
	{
	global $babBody, $babDB;

	$post = $_POST;

	if( empty($post['description']))
		{
		$babBody->msgerror = bab_translate("You must specify a vacation description");
		return false;
		}

	if( !is_numeric($post['quantity']))
		{
		$babBody->msgerror = bab_translate("You must specify a correct quantity");
		return false;
		}

	if( 0 > (int) $post['quantity'])
		{
		$babBody->msgerror = bab_translate("You must specify a correct quantity");
		return false;
		}
		
	if (0 !== (int) $post['id_rgroup'])
	{
		$res = $babDB->db_query("SELECT quantity_unit FROM bab_vac_rights WHERE id_rgroup=".$babDB->quote($post['id_rgroup']));
		if ($right_from_same_group = $babDB->db_fetch_assoc($res))
		{
			if ($right_from_same_group['quantity_unit'] != $post['quantity_unit'])
			{
				$babBody->msgerror = bab_translate("You must use a right group with only rights of same quantity unit");
				return false;
			}
		}
	}


	if (isset($post['id_type']))
		{
		$res = $babDB->db_query("SELECT cbalance FROM ".BAB_VAC_TYPES_TBL." WHERE id='".$babDB->db_escape_string($post['id_type'])."'");
		}
	elseif(isset($post['idvr']))
		{
		$res = $babDB->db_query("SELECT t.cbalance FROM ".BAB_VAC_TYPES_TBL." t, ".BAB_VAC_RIGHTS_TBL." r WHERE t.id=r.id_type AND r.id='".$babDB->db_escape_string($post['idvr'])."'");
		}

	if (list($cbalance) = $babDB->db_fetch_array($res))
		{
		if ($cbalance == 'N' && $_POST['cbalance'] != 'N')
			{
			$babBody->msgerror = bab_translate("Negative balance are not allowed with this vacation type");
			return false;
			}
		}


	if( empty($post['idvr']) && empty($post['id_creditor']) && empty($post['collid']) && empty($post['id_groups']) )
		{
		$babBody->msgerror = bab_translate("You must specify a user, collection or groups");
		return false;
		}

	$dates_to_init = array(
			'date_begin'		=> 1,
			'date_end'			=> 1,
			'date_begin_valid'	=> 0,
			'date_end_valid'	=> 0,
			'trigger_p1_begin'	=> 0,
			'trigger_p1_end'	=> 0,
			'trigger_p2_begin'	=> 0,
			'trigger_p2_end'	=> 0
		);

    if( $post['righttype'] == '2')
        {
        $post['date_begin_fixed'] = sprintf("%04d-%02d-%02d %s",($post['yearbeginfx'] + $post['year'] - 1 ), $post['monthbeginfx'],  $post['daybeginfx'] , $post['hourbeginfx']);
        $post['date_end_fixed'] = sprintf("%04d-%02d-%02d %s", ($post['yearendfx']+ $post['year'] - 1 ), $post['monthendfx'], $post['dayendfx'], $post['hourendfx']);
        
	        if ('0000-00-00 00:00:00' === $post['date_begin_fixed'] || '0000-00-00 00:00:00' === $post['date_end_fixed'])
	        {
	        	$babBody->msgerror = bab_translate("Invalid date");
	        	return false;
	        }
        
        }
    else
        {
        $post['date_begin_fixed'] = '0000-00-00 00:00:00';
        $post['date_end_fixed'] = '0000-00-00 00:00:00';
        }

	foreach ($dates_to_init as $date => $required)
		{
		$arr = explode("-", $post[$date]);
		if ($required && (count($arr) != 3 || !checkdate($arr[1],$arr[0],$arr[2])))
			{
			$babBody->msgerror = bab_translate("Invalid date");
			return false;
			}

		if (count($arr) == 3)
			$post[$date] = sprintf("%04d-%02d-%02d", $arr[2], $arr[1], $arr[0]);
		else
			$post[$date] = '';
		}

	if( $post['date_begin'] > $post['date_end'] || $post['date_begin_valid'] > $post['date_end_valid'])
		{
		$babBody->msgerror = bab_translate("Begin date must be less than end date");
		return false;
		}

	if( $post['righttype'] == '2' && $post['date_begin_fixed'] > $post['date_end_fixed'] )
		{
		$babBody->msgerror = bab_translate("Begin date must be less than end date");
		return false;
		}


	$handlefx = 0;
	if (!empty($post['idvr']))
		{
		list($dbfx) = $babDB->db_fetch_row($babDB->db_query("select date_begin_fixed from ".BAB_VAC_RIGHTS_TBL." where id='".$babDB->db_escape_string($post['idvr'])."'"));

		if( $dbfx != '0000-00-00 00:00:00' && $post['righttype'] != '2')
			{
			// on ne doit pas passer ici, normallement
			$babBody->msgerror = bab_translate("Something is wrong");
			return false;
			}

		if( $post['righttype'] == '2')
			{
			$res = $babDB->db_query("select * from ".BAB_VAC_RIGHTS_TBL." where id='".$babDB->db_escape_string($post['idvr'])."'");
			if( $res && $babDB->db_num_rows($res) > 0 )
				{
				$arrright = $babDB->db_fetch_array($res);

				$rightusers = array();
				$res = $babDB->db_query("select id_user from ".BAB_VAC_USERS_RIGHTS_TBL." where id_right='".$babDB->db_escape_string($post['idvr'])."'");
				while( $arr = $babDB->db_fetch_array($res))
					{
					$rightusers[$arr['id_user']] = 1;
					}

				$entriesusers = array();
				$res = $babDB->db_query("select vet.*, veet.quantity from ".BAB_VAC_ENTRIES_ELEM_TBL." veet left join ".BAB_VAC_ENTRIES_TBL." vet on veet.id_entry=vet.id where veet.id_right='".$babDB->db_escape_string($post['idvr'])."'");
				while( $arr = $babDB->db_fetch_array($res))
					{
					$entriesusers[$arr['id_user']] = $arr['id'];
					if( !isset($rightusers[$arr['id_user']]) )
						{
						removeFixedVacation( $arr['id']);
						$arrnotif[] = $arr['id_user'];
						// remove vacation and notify
						notifyOnVacationChange($arrnotif, $arr['quantity'], $arr['date_begin'], $arr['date_end'], VAC_FIX_DELETE);
						}
					}

				if( $arrright['date_begin_fixed'] != $post['date_begin_fixed']
					|| $arrright['date_end_fixed'] != $post['date_end_fixed'])
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
						updateFixedVacation($ukey, $post['idvr'], $post['date_begin_fixed'] , $post['date_end_fixed'], $post['quantity']);
						$uupd[] = $ukey;
						// update
						}
					elseif( !isset($entriesusers[$ukey]))
						{
						// add
						addFixedVacation($ukey, $post['idvr'], $post['date_begin_fixed'] , $post['date_end_fixed'], '', $post['quantity']);
						$uadd[] = $ukey;
						}
					}

				if( count($uupd)> 0 )
					{
					notifyOnVacationChange($uupd, $post['quantity'], $post['date_begin_fixed'],  $post['date_end_fixed'], VAC_FIX_UPDATE);
					}
				if( count($uadd)> 0 )
					{
					notifyOnVacationChange($uadd, $post['quantity'], $post['date_begin_fixed'],  $post['date_end_fixed'],  VAC_FIX_ADD);
					}
				}
			}

			
		if (!isset($post['active'])) {
			$post['active'] = 'N';
		}

		$babDB->db_query("

			UPDATE ".BAB_VAC_RIGHTS_TBL."
				set
				description=".$babDB->quote($post['description']).",
				id_creditor=".$babDB->quote($GLOBALS['BAB_SESS_USERID']).",
				quantity=".$babDB->quote($post['quantity']).",
				quantity_unit=".$babDB->quote($post['quantity_unit']).",
				date_entry=curdate(),
				date_begin=".$babDB->quote($post['date_begin']).",
				date_end=".$babDB->quote($post['date_end']).",
				active=".$babDB->quote($post['active']).",
				cbalance=".$babDB->quote($post['cbalance']).",
				date_begin_valid=".$babDB->quote($post['date_begin_valid']).",
				date_end_valid=".$babDB->quote($post['date_end_valid']).",
				date_begin_fixed=".$babDB->quote($post['date_begin_fixed']).",
				date_end_fixed=".$babDB->quote($post['date_end_fixed']).",
				no_distribution=".$babDB->quote($post['no_distribution']).",
				id_rgroup=".$babDB->quote($post['id_rgroup'])."
			WHERE id=".$babDB->quote($post['idvr'])."

			");

		$id = $post['idvr'];
		}
	else
		{

		$idusers = array();



		if( (1 === (int) $post['assignment_type']) && $post['id_creditor'] != "" ) // user
			{
			$idusers[] = $post['id_creditor'];
			}

		elseif (2 === (int) $post['assignment_type'] && $post['collid'] != "") // collection
			{
			if ( $post['collid'] == -2)
				{
				$res = $babDB->db_query("SELECT p.* from ".BAB_VAC_PERSONNEL_TBL." p, ".BAB_VAC_COLLECTIONS_TBL." c, ".BAB_VAC_COLL_TYPES_TBL." t WHERE t.id_type=".$babDB->quote($post['id_type'])." and c.id = t.id_coll AND p.id_coll=c.id");
				}
			elseif( $post['collid'] != -1)
				{
				$res = $babDB->db_query("select * from ".BAB_VAC_PERSONNEL_TBL." where id_coll=".$babDB->quote($post['collid']));
				}
			else
				$res = $babDB->db_query("select * from ".BAB_VAC_PERSONNEL_TBL."");

			while( $arr = $babDB->db_fetch_array($res))
				{
				$idusers[] = $arr['id_user'];
				}
			}

		elseif ((3 === (int) $post['assignment_type']) && isset($post['id_groups'])) {

			$arr = bab_getGroupsMembers($post['id_groups']);
			foreach($arr as $u) {
				$res = $babDB->db_query("select COUNT(*) from ".BAB_VAC_PERSONNEL_TBL." where id_user=".$babDB->quote($u['id']));
				list($n) = $babDB->db_fetch_array($res);
				if ($n > 0) {
					$idusers[] = $u['id'];
				}
			}
		}

		if( 0 === count($idusers) ) {
			$babBody->msgerror = bab_translate("The personnel assignement is empty");
			return false;
		}

		if (!isset($post['active'])) {
			$post['active'] = 'N';
		}


		$babDB->db_query("

			INSERT into ".BAB_VAC_RIGHTS_TBL."
				(
				description,
				id_creditor,
				id_type,
				quantity,
				quantity_unit,
				date_entry,
				date_begin,
				date_end,
				active,
				cbalance,
				date_begin_valid,
				date_end_valid,
				date_begin_fixed,
				date_end_fixed,
				no_distribution,
				id_rgroup
				)
			values
				(
				".$babDB->quote($post['description']).",
				".$babDB->quote($GLOBALS['BAB_SESS_USERID']).",
				".$babDB->quote($post['id_type']).",
				".$babDB->quote($post['quantity']).",
				".$babDB->quote($post['quantity_unit']).",
				curdate(),
				".$babDB->quote($post['date_begin']).",
				".$babDB->quote($post['date_end']).",
				".$babDB->quote($post['active']).",
				".$babDB->quote($post['cbalance']).",
				".$babDB->quote($post['date_begin_valid']).",
				".$babDB->quote($post['date_end_valid']).",
				".$babDB->quote($post['date_begin_fixed']).",
				".$babDB->quote($post['date_end_fixed']).",
				".$babDB->quote($post['no_distribution']).",
				".$babDB->quote($post['id_rgroup'])."
				)
			");

		$id = $babDB->db_insert_id();



		// insert id_user
		foreach($idusers as $id_user) {
			$babDB->db_query("INSERT INTO ".BAB_VAC_USERS_RIGHTS_TBL." (id_user, id_right) values (".$babDB->quote($id_user).", ".$babDB->quote($id).")");
		}



		$nbidusers = count($idusers);
		if('2' == $post['righttype'] && $nbidusers > 0) // fixed dates
			{
			for($k=0; $k < $nbidusers; $k++ )
				{
				addFixedVacation($idusers[$k], $id, $post['date_begin_fixed'] , $post['date_end_fixed'], '', $post['quantity']);
				}
			notifyOnVacationChange($idusers, $post['quantity'], $post['date_begin_fixed'],  $post['date_end_fixed'], VAC_FIX_ADD);
			}
		}


		if ($post['righttype'] != '1')
			{
			$babDB->db_query("DELETE FROM ".BAB_VAC_RIGHTS_RULES_TBL." WHERE id_right=".$babDB->quote($id));
			$babDB->db_query("DELETE FROM ".BAB_VAC_RIGHTS_INPERIOD_TBL." WHERE id_right=".$babDB->quote($id));
			}
		else // rules
			{
			$validoverlap = isset($post['validoverlap']) ? 1 : 0;
			$trigger_type = isset($post['trigger_type']) ? $post['trigger_type'] : 0;
			$trigger_overlap = isset($post['trigger_overlap']) ? 1 : 0;


			$res = $babDB->db_query("SELECT id FROM ".BAB_VAC_RIGHTS_RULES_TBL." WHERE id_right=".$babDB->quote($id));
			if ($babDB->db_num_rows($res) > 0)
				{
				list($id_rule) = $babDB->db_fetch_array($res);
				$babDB->db_query("
						UPDATE ".BAB_VAC_RIGHTS_RULES_TBL."
						SET
							validoverlap		=".$babDB->quote($validoverlap).",
							trigger_nbdays_min	=".$babDB->quote((int) $post['trigger_nbdays_min']).",
							trigger_nbdays_max	=".$babDB->quote((int) $post['trigger_nbdays_max']).",
							trigger_type		=".$babDB->quote($trigger_type).",
							trigger_p1_begin	=".$babDB->quote($post['trigger_p1_begin']).",
							trigger_p1_end		=".$babDB->quote($post['trigger_p1_end']).",
							trigger_p2_begin	=".$babDB->quote($post['trigger_p2_begin']).",
							trigger_p2_end		=".$babDB->quote($post['trigger_p2_end']).",
							trigger_overlap		=".$babDB->quote($trigger_overlap)."
						WHERE
							id=".$babDB->quote($id_rule)."
						");
				}
			else
				{
				$babDB->db_query("
						INSERT INTO ".BAB_VAC_RIGHTS_RULES_TBL."
						(
							id_right,
							validoverlap,
							trigger_nbdays_min,
							trigger_nbdays_max,
							trigger_type,
							trigger_p1_begin,
							trigger_p1_end,
							trigger_p2_begin,
							trigger_p2_end,
							trigger_overlap
							)
						VALUES
							(
							".$babDB->quote($id).",
							".$babDB->quote($validoverlap).",
							".$babDB->quote((int) $post['trigger_nbdays_min']).",
							".$babDB->quote((int) $post['trigger_nbdays_max']).",
							".$babDB->quote($trigger_type).",
							".$babDB->quote($post['trigger_p1_begin']).",
							".$babDB->quote($post['trigger_p1_end']).",
							".$babDB->quote($post['trigger_p2_begin']).",
							".$babDB->quote($post['trigger_p2_end']).",
							".$babDB->quote($trigger_overlap)."
							)
						");
				}


			$babDB->db_query('DELETE FROM '.BAB_VAC_RIGHTS_INPERIOD_TBL.' WHERE id_right='.$babDB->quote($id));


			$right_inperiod = bab_pp('right_inperiod', array());
			$period_start = bab_pp('period_start', array());
			$period_end = bab_pp('period_end', array());

			foreach($right_inperiod as $key => $value) {

				if( !empty($period_start[$key]) && !empty($period_end[$key]))
				{
				$arr = explode("-", $period_start[$key]);
				$period_start_date = sprintf("%04d-%02d-%02d", $arr[2], $arr[1], $arr[0]);

				$arr = explode("-", $period_end[$key]);
				$period_end_date = sprintf("%04d-%02d-%02d", $arr[2], $arr[1], $arr[0]);


				$babDB->db_query('
					INSERT INTO '.BAB_VAC_RIGHTS_INPERIOD_TBL.'
						(
							id_right,
							period_start,
							period_end,
							right_inperiod
						)

					VALUES
						(
							'.$babDB->quote($id).',
							'.$babDB->quote($period_start_date).',
							'.$babDB->quote($period_end_date).',
							'.$babDB->quote($value).'
						)
				');
			}
			}

		}

		return true;
	}

function modifyVacationRightPersonnel($idvr, $userids, $nuserids)
	{
	global $babDB;
	$count = sizeof($userids);

	$arrright = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_VAC_RIGHTS_TBL." where id=".$babDB->quote($idvr)));

	for( $i = 0; $i < sizeof($nuserids); $i++)
		{
		if( $nuserids[$i] != "" && ( $count == 0 || !in_array($nuserids[$i], $userids)))
			{
			if( $arrright['date_begin_fixed'] != '0000-00-00' )
				{
				$res = $babDB->db_query("select vet.*, veet.quantity from ".BAB_VAC_ENTRIES_ELEM_TBL." veet left join ".BAB_VAC_ENTRIES_TBL." vet on veet.id_entry=vet.id where veet.id_right=".$babDB->quote($idvr)." and vet.id_user=".$babDB->quote($nuserids[$i]));
				$arr = $babDB->db_fetch_array($res);
				removeFixedVacation( $arr['id']);
				$arrnotif[] = $arr['id_user'];
				notifyOnVacationChange($arrnotif, $arr['quantity'], $arr['date_begin'], $arr['date_end'], VAC_FIX_DELETE);
				}

			$babDB->db_query("delete from ".BAB_VAC_USERS_RIGHTS_TBL." where id_right=".$babDB->quote($idvr)." and id_user=".$babDB->quote($nuserids[$i]));
			}
		}

	for( $i = 0; $i < $count; $i++)
		{
		if( !in_array($userids[$i], $nuserids) )
			{
			$babDB->db_query("insert into ".BAB_VAC_USERS_RIGHTS_TBL." (id_user, id_right) values (".$babDB->quote($userids[$i]).", ".$babDB->quote($idvr).")");
			if( $arrright['date_begin_fixed'] != '0000-00-00' )
				{
				addFixedVacation($userids[$i], $idvr, $arrright['date_begin_fixed'] , $arrright['date_end_fixed'], '', $arrright['quantity']);
				$arrnotif[] = $userids[$i];
				notifyOnVacationChange($arrnotif, $arrright['quantity'], $arrright['date_begin_fixed'], $arrright['date_end_fixed'],  VAC_FIX_ADD);
				}
			}
		}
	}


function deleteVacationRightConf($idvr) {

	global $babBody;
	class temp
		{
		var $yes;
		var $no;
		var $invalidentry1;
		var $tpsel;
		var $colsel;

		function temp($idvr)
			{
			$this->idvr = $idvr;
			$this->t_alert = bab_translate("Some vacation requests are linked to this right, if you delete the right, the vacation requests will be deleted with it");

			$this->t_request = bab_translate("Last request with this right");
			$this->t_confirm = bab_translate("Confirm");

			global $babDB;
			$this->res = $babDB->db_query(
				"SELECT
					UNIX_TIMESTAMP(e.date_begin) date_begin
				FROM
					".BAB_VAC_ENTRIES_ELEM_TBL." ee,
					".BAB_VAC_ENTRIES_TBL." e
				WHERE
					ee.id_right=".$babDB->quote($idvr)."
					AND e.id = ee.id_entry

				ORDER BY e.date_begin DESC"
				);

			$arr = $babDB->db_fetch_assoc($this->res);
			$nb_requests = $babDB->db_num_rows($this->res);
			$this->request = bab_toHtml(bab_vac_longDate($arr['date_begin']));
			if (1 == $nb_requests) {
				$this->t_nb_requests = bab_toHtml(bab_translate("one request will be deleted"));
			} else {
				$this->t_nb_requests = bab_toHtml(sprintf(bab_translate("%d requests will be deleted"),$nb_requests));
			}
		}
	}

	$temp = new temp($idvr);
	$babBody->babecho(bab_printTemplate($temp,"vacadma.html", "rightsdelete"));
	$babBody->title = bab_translate("Delete vacation right");
	$babBody->addItemMenu("delvr", bab_translate("Delete"), $GLOBALS['babUrlScript']."?tg=vacadma&idx=delvr");
}






function rightcopy() {

	global $babBody;
	class temp
		{

		function temp() {
			$this->t_year_from = bab_translate("Year");
			$this->t_year_to = bab_translate("Create rights for year");
			$this->t_record = bab_translate("Record");

			global $babDB;

			$this->resyear = $babDB->db_query("SELECT YEAR(date_begin) year FROM ".BAB_VAC_RIGHTS_TBL." GROUP BY year ORDER BY year DESC");

			$this->year_to = isset($_POST['year_to']) ? $_POST['year_to'] : '';
		}

		function getnextyear() {
			global $babDB;
			if ($arr = $babDB->db_fetch_assoc($this->resyear)) {
				$this->year = bab_toHtml($arr['year']);
				if (empty($this->year_to)) {
					$this->year_to = $this->year;
				}
				$this->selected = isset($_POST['year_from']) && $_POST['year_from'] == $arr['year'];
				return true;
			}
			return false;
		}

		function year_to() {
			$selected_year = isset($_POST['year_from']) ? $_POST['year_from'] : $this->year_to;
			if (!isset($_POST['year_to'])) {
				$this->year_to++;
			}
			global $babDB;

			$this->resrights = $babDB->db_query("
				SELECT
					r.id,
					r.description
				FROM
					".BAB_VAC_RIGHTS_TBL." r
				WHERE
					 YEAR(r.date_begin) = ".$babDB->quote($selected_year)."
				GROUP BY r.id
				");

			return false;
		}

		function getnextright() {
			global $babDB;
			if ($arr = $babDB->db_fetch_assoc($this->resrights)) {
				$this->right_description	= bab_toHtml($arr['description']);
				$this->id_right				= bab_toHtml($arr['id']);
				$this->checked				= (isset($_POST['rights']) && isset($_POST['rights'][$arr['id']])) || empty($_POST);
				return true;
			}
			return false;
		}
	}



	class temp2 {

		var $messages = array();

		function temp2() {

			include_once $GLOBALS['babInstallPath']."utilit/dateTime.php";

			global $babDB;
			$this->increment = ((int) $_POST['year_to']) - ((int) $_POST['year_from']);

			$this->nb_right_insert = 0;


			// create rights

			if (isset($_POST['rights'])) {
				foreach($_POST['rights'] as $id_right) {
					$row = $this->get_row($id_right);
					if (true === $this->transform_row($row)) {
						$this->insert_row($row);
					}
				}
			}


			$this->addMessage( sprintf( bab_translate("%d rights has been inserted"), $this->nb_right_insert) );
		}


		function addMessage($str) {
			$this->messages[] = $str;
		}

		function getnextmessage() {
			return list(,$this->message) = each($this->messages);
		}


		function get_row($id_right) {
			global $babDB;
			$res = $babDB->db_query("SELECT * FROM ".BAB_VAC_RIGHTS_TBL." WHERE id=".$babDB->quote($id_right));
			return $babDB->db_fetch_assoc($res);
		}


		function increment_ISO($date) {
			if ('0000-00-00' === $date) {
				return $date;
			}
			
			if ('0000-00-00 00:00:00' === $date) {
				return $date;
			}
			
			$obj = BAB_DateTime::fromIsoDateTime($date);
			$obj->add($this->increment,BAB_DATETIME_YEAR);
			
			if (10 == mb_strlen($date)) {
				return $obj->getIsoDate();
			} else {
				return $obj->getIsoDateTime();
			}
		}


		function transform_row(&$row) {

			global $babDB;

			$row['id_creditor']			= $GLOBALS['BAB_SESS_USERID'];

			$row['date_entry']			= date('Y-m-d');
			$row['date_begin']			= $this->increment_ISO($row['date_begin']);
			$row['date_end']			= $this->increment_ISO($row['date_end']);
			$row['date_begin_valid']	= $this->increment_ISO($row['date_begin_valid']);
			$row['date_end_valid']		= $this->increment_ISO($row['date_end_valid']);
			$row['date_end_fixed']		= $this->increment_ISO($row['date_end_fixed']);
			$row['date_begin_fixed']	= $this->increment_ISO($row['date_begin_fixed']);


			$row['description'] = preg_replace_callback("/\d{4}/",
				create_function(
				   '$matches',
				   'return $matches[0] + '.$this->increment.';'
				 ),
				$row['description']
			);

			$res = $babDB->db_query("
			SELECT COUNT(*) FROM ".BAB_VAC_RIGHTS_TBL."
			WHERE
				description=".$babDB->quote($row['description'])."
				AND date_begin=".$babDB->quote($row['date_begin'])."
				AND date_end=".$babDB->quote($row['date_end'])."
			");

			list($n) = $babDB->db_fetch_array($res);

			if ($n > 0) {
				$this->addMessage(sprintf(bab_translate("The right %s already exists"), $row['description']));
				return false;
			}

			return true;
		}



		function insert_row($row) {

			global $babDB;

			$old_id_right = $row['id'];

			unset($row['id']);

			foreach($row as $key => $value) {
				$row[$key] = $babDB->db_escape_string($value);
			}

			$babDB->db_query("INSERT INTO ".BAB_VAC_RIGHTS_TBL." (".implode(',',array_keys($row)).") VALUES (".$babDB->quote($row).")");

			$this->nb_right_insert++;

			$new_id_right = $babDB->db_insert_id();


			$res = $babDB->db_query("SELECT * FROM ".BAB_VAC_RIGHTS_RULES_TBL." WHERE id_right=".$babDB->quote($old_id_right));
			if ($rule = $babDB->db_fetch_assoc($res)) {
				unset($rule['id']);
				$rule['id_right'] = $new_id_right;

				$rule['trigger_p1_begin']	= $this->increment_ISO($rule['trigger_p1_begin']);
				$rule['trigger_p1_end']		= $this->increment_ISO($rule['trigger_p1_end']);

				$rule['trigger_p2_begin']	= $this->increment_ISO($rule['trigger_p2_begin']);
				$rule['trigger_p2_end']		= $this->increment_ISO($rule['trigger_p2_end']);

				$babDB->db_query("INSERT INTO ".BAB_VAC_RIGHTS_RULES_TBL." (".implode(',',array_keys($rule)).") VALUES (".$babDB->quote($rule).")");
			}


			$res = $babDB->db_query("SELECT * FROM ".BAB_VAC_RIGHTS_INPERIOD_TBL." WHERE id_right=".$babDB->quote($old_id_right));
			if ($inperiod = $babDB->db_fetch_assoc($res)) {
				unset($inperiod['id']);
				$inperiod['id_right'] = $new_id_right;

				$inperiod['period_start']	= $this->increment_ISO($inperiod['period_start']);
				$inperiod['period_end']		= $this->increment_ISO($inperiod['period_end']);

				$babDB->db_query("INSERT INTO ".BAB_VAC_RIGHTS_INPERIOD_TBL." (".implode(',',array_keys($inperiod)).") VALUES (".$babDB->quote($inperiod).")");
			}


			$res = $babDB->db_query("SELECT
				t2.id_user,
				t2.quantity
			FROM
					".BAB_VAC_USERS_RIGHTS_TBL." t2
					WHERE t2.id_right=".$babDB->quote($old_id_right));

			while ($arr = $babDB->db_fetch_assoc($res)) {
				$babDB->db_query("INSERT INTO ".BAB_VAC_USERS_RIGHTS_TBL." (id_user, id_right) VALUES (".$babDB->quote($arr['id_user']).",".$babDB->quote($new_id_right).")");
			}

		}
	}


	if (isset($_POST['copy_rights'])) {
		$temp = new temp2();
		$babBody->babecho(bab_printTemplate($temp,"vacadma.html", "rightcopy2"));

	} else {
		$temp = new temp();
		$babBody->babecho(bab_printTemplate($temp,"vacadma.html", "rightcopy"));
	}
}




/**
 * Delete vacation right and request associated to the vacation right
 * @param int $idvr
 * @return bool
 */
function deleteVacationRight($idvr)
	{
	global $babBody, $babDB;
	list($total) = $babDB->db_fetch_row($babDB->db_query("select count(id) as total from ".BAB_VAC_ENTRIES_ELEM_TBL." where id_right=".$babDB->quote($idvr)));
	if( $total == 0 || bab_rp('confirmed') )
		{
		$babDB->db_query("DELETE FROM ".BAB_VAC_RIGHTS_TBL." WHERE id=".$babDB->quote($idvr));
		$babDB->db_query("DELETE FROM ".BAB_VAC_USERS_RIGHTS_TBL." WHERE id_right=".$babDB->quote($idvr));
		$babDB->db_query("DELETE FROM ".BAB_VAC_RIGHTS_RULES_TBL." WHERE id_right=".$babDB->quote($idvr));

		$res = $babDB->db_query("SELECT id_entry FROM ".BAB_VAC_ENTRIES_ELEM_TBL." WHERE id_right=".$babDB->quote($idvr));
		while ($arr = $babDB->db_fetch_assoc($res)) {
			bab_vac_delete_request($arr['id_entry']);
		}
		return true;
		}

	return false;
	}


function modRgroup() {

	global $babDB;

	$name = bab_rp('name');
	if (!empty($name)) {
		global $babDB;
		$id = bab_rp('id_rgroup');
		if (empty($id)) {
			$babDB->db_query("INSERT INTO ".BAB_VAC_RGROUPS_TBL." (name) VALUES (".$babDB->quote($name).")");
		} else {
			$babDB->db_query("UPDATE ".BAB_VAC_RGROUPS_TBL." SET name=".$babDB->quote($name)." WHERE id=".$babDB->quote($id));
		}
		return true;
	}
	return false;
}


function deleteRgroup() {
	global $babDB;
	$id = bab_rp('id_rgroup');
	if (!empty($id)) {
		$babDB->db_query("DELETE FROM ".BAB_VAC_RGROUPS_TBL." WHERE id=".$babDB->quote($id));
	}
}


/* main */
$acclevel = bab_vacationsAccess();
if( !isset($acclevel['manager']) || $acclevel['manager'] != true)
	{
	$babBody->msgerror = bab_translate("Access denied");
	return;
	}
	
$idx = bab_rp('idx', "lrig");


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
				if (!deleteVacationRight($_POST['idvr'])) {
					$idx = 'delvr';
					}
				}
			break;

		case 'delete':
			if (!deleteVacationRight($_POST['idvr'])) {
				$idx = 'delvr';
				}
			break;

		case 'rgroupmod':
			if (isset($_POST['rgroup_delete'])) {
				deleteRgroup();

			} else if (!modRgroup()) {
				$idx = 'rgroupmod';
			}
			break;

		}

	}



switch($idx)
	{
	case "browt":
		$babBody->title = bab_translate("Personnel associated with type");
		browsePersonnelByType(bab_rp('pos'),  bab_rp('cb'), bab_rp('idtype'));
		exit;
		break;
	case "viewvr":
		viewVacationRightPersonnel(bab_rp('idvr'));
		exit;
		break;

	case 'delvr':
		deleteVacationRightConf($_POST['idvr']);
		break;

	case "delvru":
		$userids = bab_rp('userids', array());
		modifyVacationRightPersonnel(bab_rp('idvr'), $userids, bab_rp('nuserids'));
		/* no break; */
	case "lvrp":
		
		$pos = bab_rp('pos');
		
		if(null !== bab_rp('chg', null))
		{
			if( $pos[0] == "-")
				$pos = $pos[1];
			else
				$pos = "-" .$pos;
		}
		listVacationRightPersonnel($pos, bab_rp('idvr'));
		exit;
		break;

	case "modvr":

		addModifyVacationRigths(bab_rp('idvr'));

		$babBody->title = bab_translate("Modify vacation right");
		$babBody->addItemMenu("vacuser", bab_translate("Vacations"), $GLOBALS['babUrlScript']."?tg=vacuser");
		$babBody->addItemMenu("menu", bab_translate("Management"), $GLOBALS['babUrlScript']."?tg=vacadm&idx=menu");
		$babBody->addItemMenu("lrig", bab_translate("Rights"), $GLOBALS['babUrlScript']."?tg=vacadma&idx=lrig");
		$babBody->addItemMenu("modvr", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=vacadma&idx=modvr");
		break;

	case "addvr":

		addModifyVacationRigths();

		$babBody->title = bab_translate("Allocate vacation rights");
		$babBody->addItemMenu("vacuser", bab_translate("Vacations"), $GLOBALS['babUrlScript']."?tg=vacuser");
		$babBody->addItemMenu("menu", bab_translate("Management"), $GLOBALS['babUrlScript']."?tg=vacadm&idx=menu");
		$babBody->addItemMenu("lrig", bab_translate("Rights"), $GLOBALS['babUrlScript']."?tg=vacadma&idx=lrig");
		$babBody->addItemMenu("addvr", bab_translate("Add"), $GLOBALS['babUrlScript']."?tg=vacadma&idx=addvr");
		break;

	case 'rgroup':
		$babBody->title = bab_translate("Rights groups");
		$babBody->addItemMenu("vacuser", bab_translate("Vacations"), $GLOBALS['babUrlScript']."?tg=vacuser");
		$babBody->addItemMenu("menu", bab_translate("Management"), $GLOBALS['babUrlScript']."?tg=vacadm&idx=menu");
		$babBody->addItemMenu("rgroup", bab_translate("Rights groups"), $GLOBALS['babUrlScript']."?tg=vacadma&idx=rgroup");
		$babBody->addItemMenu("rgroupmod", bab_translate("Add"), $GLOBALS['babUrlScript']."?tg=vacadma&idx=rgroupmod");
		rgrouplist();
		break;

	case 'rgroupmod':
		$babBody->title = bab_translate("Right group");
		$babBody->addItemMenu("vacuser", bab_translate("Vacations"), $GLOBALS['babUrlScript']."?tg=vacuser");
		$babBody->addItemMenu("menu", bab_translate("Management"), $GLOBALS['babUrlScript']."?tg=vacadm&idx=menu");
		$babBody->addItemMenu("rgroupmod", bab_translate("Add"), $GLOBALS['babUrlScript']."?tg=vacadma&idx=rgroupmod");
		rgroupmod();
		break;


	case 'copy':
		$babBody->title = bab_translate("Rights renewal by years");
		$babBody->addItemMenu("vacuser", bab_translate("Vacations"), $GLOBALS['babUrlScript']."?tg=vacuser");
		$babBody->addItemMenu("menu", bab_translate("Management"), $GLOBALS['babUrlScript']."?tg=vacadm&idx=menu");
		$babBody->addItemMenu('copy', bab_translate("Rights renewal"), $GLOBALS['babUrlScript']."?tg=vacadma&idx=rgroupmod");
		rightcopy();
		break;

	case "lrig":
	default:
		$babBody->title = bab_translate("Vacations rights");

		$datee			= bab_rp('datee');
		$dateb			= bab_rp('dateb');
		$idtype			= bab_rp('idtype');
		$idcreditor		= bab_rp('idcreditor');
		$pos			= bab_rp('pos',0);
		$active			= bab_rp('active','Y');

		listVacationRigths($idtype, $idcreditor, $dateb, $datee, $active, $pos);
		$babBody->addItemMenu("vacuser", bab_translate("Vacations"), $GLOBALS['babUrlScript']."?tg=vacuser");
		$babBody->addItemMenu("menu", bab_translate("Management"), $GLOBALS['babUrlScript']."?tg=vacadm&idx=menu");
		$babBody->addItemMenu("lrig", bab_translate("Rights"), $GLOBALS['babUrlScript']."?tg=vacadma&idx=lrig");
		$babBody->addItemMenu("addvr", bab_translate("Add"), $GLOBALS['babUrlScript']."?tg=vacadma&idx=addvr");
		break;
	}
$babBody->setCurrentItemMenu($idx);
bab_siteMap::setPosition('bab','UserVac');
?>
