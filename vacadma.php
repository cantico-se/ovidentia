<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
include_once "base.php";
include_once $babInstallPath."utilit/vacincl.php";

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

			if( $pos[0] == "-" )
				{
				$this->pos = $pos[1];
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
	echo bab_printTemplate($temp, "vacadma.html", "browseusers");
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

			if( sizeof($aaareq) > 0 )
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
				$this->date = bab_printDate($arr['date_entry']);
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


function addVacationRigths($description, $userid, $groupid, $idtype, $nbdays, $dateb, $datee, $vclose)
	{
	global $babBody;
	class temp
		{
		var $usertext;
		var $colltext;
		var $userval;
		var $userid;
		var $collval;
		var $collid;
		var $quantitytxt;
		var $add;
		var $bdel;
		var $delete;
		var $usersbrowurl;
		var $db;
		var $reset;
		var $orand;
		var $days;
		var $desctxt;
		var $periodtxt;
		var $begintxt;
		var $endtxt;
		var $dateburl;
		var $dateeurl;
		var $dateb;
		var $datee;
		var $description;
		var $idtype;
		var $nbdays;
		var $typetxt;
		var $typename;
		var $allcol;
		var $opentxt;
		var $yes;
		var $no;
		var $counttype;
		var $restype;

		var $invalidentry1;
		var $tpsel;
		var $colsel;

		function temp($description, $userid, $collid, $idtype, $nbdays, $dateb, $datee, $vclose)
			{
			$this->typetxt = bab_translate("Type");
			$this->usertext = bab_translate("User");
			$this->colltext = bab_translate("Collection");
			$this->quantitytxt = bab_translate("Quantity");
			$this->reset = bab_translate("Reset");
			$this->delete = bab_translate("Delete");
			$this->orand = bab_translate("Or users having ");
			$this->allcol = bab_translate("All collections");
			$this->days = bab_translate("Day(s)");
			$this->desctxt = bab_translate("Description");
			$this->periodtxt = bab_translate("Period"). " (".bab_translate("dd-mm-yyyy").")";
			$this->begintxt = bab_translate("Begin");
			$this->endtxt = bab_translate("End");
			$this->opentxt = bab_translate("Opened right");
			$this->yes = bab_translate("Yes");
			$this->no = bab_translate("No");
			$this->invalidentry1 = bab_translate("Invalid entry!  Only numbers are accepted or . !");
			$this->usersbrowurl = $GLOBALS['babUrlScript']."?tg=vacadma&idx=browt";

			$this->db = $GLOBALS['babDB'];
			$this->add = bab_translate("Add");
			$this->userid = $userid;
			if( $userid != "" )
				$this->userval = bab_getUserName($userid);
			else
				$this->userval = "";
			$this->bdel = false;
			$this->collid = $collid;
			if( $collid  == "" || $collid  == "-1")
				$this->colsel = 0;
			else if( $collid  == "-1")
				$this->colsel = 1;

			$this->dateb = $dateb;
			$this->datee = $datee;
			$this->description = $description;
			$this->idtype = $idtype;
			$this->tpsel = 0;
			$this->nbdays = $nbdays;

			$this->dateburl = $GLOBALS['babUrlScript']."?tg=month&callback=dateBegin&ymin=0&ymax=3";
			$this->dateeurl = $GLOBALS['babUrlScript']."?tg=month&callback=dateEnd&ymin=0&ymax=3";

			$this->nbdays = $nbdays;

			if( $vclose == "" )
				$vclose = "N";

			if( $vclose == "Y" )
				{
				$this->nselected = "";
				$this->yselected = "selected";
				}
			else
				{
				$this->yselected = "";
				$this->nselected = "selected";
				}

			$this->restype = $this->db->db_query("select * from ".BAB_VAC_TYPES_TBL." order by name asc");
			$this->counttype = $this->db->db_num_rows($this->restype);
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
				if( $this->collid == $this->idcollection)
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
				$this->colres = $this->db->db_query("select ".BAB_VAC_COLLECTIONS_TBL.".* from ".BAB_VAC_COLLECTIONS_TBL." join ".BAB_VAC_COLL_TYPES_TBL." where ".BAB_VAC_COLL_TYPES_TBL.".id_type='".$this->typeid."' and ".BAB_VAC_COLLECTIONS_TBL.".id=".BAB_VAC_COLL_TYPES_TBL.".id_coll");
				$this->countcol = $this->db->db_num_rows($this->colres);
				if( $this->idtype == $this->typeid )
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

		}

	$temp = new temp($description, $userid, $collid, $idtype, $nbdays, $dateb, $datee, $vclose);
	$babBody->babecho(	bab_printTemplate($temp,"vacadma.html", "prightsadd"));
	}

function modifyVacationRigths($idvr, $description, $nbdays, $dateb, $datee, $vclose)
	{
	global $babBody;
	class temp
		{
		var $typetxt;
		var $idcollection;
		var $typename;
		var $selected;
		var $add;
		var $bdel;
		var $delete;
		var $db;
		var $reset;
		var $orand;
		var $days;
		var $desctxt;
		var $periodtxt;
		var $begintxt;
		var $endtxt;
		var $dateburl;
		var $dateeurl;
		var $dateb;
		var $datee;
		var $description;
		var $idtype;
		var $nbdays;
		var $idvr;
		var $daystxt;
		var $invalidentry1;
		var $closetxt;
		var $yes;
		var $no;

		function temp($idvr, $description, $nbdays, $dateb, $datee, $vclose)
			{
			$this->idvr = $idvr;
			$this->typetxt = bab_translate("Type");
			$this->reset = bab_translate("Reset");
			$this->delete = bab_translate("Delete");
			$this->days = bab_translate("Day(s)");
			$this->desctxt = bab_translate("Description");
			$this->periodtxt = bab_translate("Period"). " (".bab_translate("dd-mm-yyyy").")";
			$this->begintxt = bab_translate("Begin");
			$this->endtxt = bab_translate("End");
			$this->daystxt = bab_translate("Quantity");
			$this->closetxt = bab_translate("Close right");
			$this->yes = bab_translate("Yes");
			$this->no = bab_translate("No");
			$this->invalidentry1 = bab_translate("Invalid entry!  Only numbers are accepted or . !");

			$this->db = $GLOBALS['babDB'];
			$this->add = bab_translate("Modify");
			$this->bdel = false;

			list($total) = $this->db->db_fetch_row($this->db->db_query("select count(id) as total from ".BAB_VAC_ENTRIES_ELEM_TBL." where id_entry='".$idvr."'"));
			if( $total == 0 )
				$this->bdel = true;

			$arr = $this->db->db_fetch_array($this->db->db_query("select * from ".BAB_VAC_RIGHTS_TBL." where id='".$idvr."'"));

			if( $dateb == "" )
				{
				$rr = explode('-', $arr['date_begin']);
				$this->dateb = $rr[2]."-".$rr[1]."-".$rr[0];
				}
			else
				$this->dateb = $dateb;

			if( $datee == "" )
				{
				$rr = explode('-', $arr['date_end']);
				$this->datee = $rr[2]."-".$rr[1]."-".$rr[0];
				}
			else
				$this->datee = $datee;
			if( $description == "" )
				$this->description = $arr['description'];
			else
				$this->description = $description;

			$this->idtype = $arr['id_type'];
			if( $nbdays == "" )
				$this->nbdays = $arr['quantity'];
			else
				$this->nbdays = $nbdays;

			if( $vclose == "" )
				{
				$vclose = $arr['active'] == "Y"? "N": "Y";
				}

			if( $vclose == "Y" )
				{
				$this->nselected = "";
				$this->yselected = "selected";
				}
			else
				{
				$this->yselected = "";
				$this->nselected = "selected";
				}
			$this->dateburl = $GLOBALS['babUrlScript']."?tg=month&callback=dateBegin&ymin=0&ymax=3";
			$this->dateeurl = $GLOBALS['babUrlScript']."?tg=month&callback=dateEnd&ymin=0&ymax=3";

			$arr = $this->db->db_fetch_array($this->db->db_query("select name from ".BAB_VAC_TYPES_TBL." where id='".$arr['id_type']."'"));
			$this->typename = $arr['name'];
			}
		}

	$temp = new temp($idvr, $description, $nbdays, $dateb, $datee, $vclose);
	$babBody->babecho(	bab_printTemplate($temp,"vacadma.html", "prightsmod"));
	}

function listVacationRightPersonnelOld($pos, $idvr)
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


		function temp($pos, $idvr)
			{
			$this->allname = bab_translate("All");
			$this->uncheckall = bab_translate("Uncheck all");
			$this->checkall = bab_translate("Check all");
			$this->deletealt = bab_translate("Delete");
			$this->db = $GLOBALS['babDB'];
			$this->idvr = $idvr;

			if( $pos[0] == "-" )
				{
				$this->pos = $pos[1];
				$this->ord = $pos[0];
				$req = "select ".BAB_USERS_TBL.".* from ".BAB_USERS_TBL." join ".BAB_VAC_USERS_RIGHTS_TBL." where ".BAB_USERS_TBL.".id=".BAB_VAC_USERS_RIGHTS_TBL.".id_user and ".BAB_VAC_USERS_RIGHTS_TBL.".id_right='".$idvr."' and ".BAB_USERS_TBL.".lastname like '".$this->pos."%' ";
				$req .= "order by ".BAB_USERS_TBL.".lastname, ".BAB_USERS_TBL.".firstname asc";
				$this->fullname = bab_translate("Lastname"). " " . bab_translate("Firstname");
				$this->fullnameurl = $GLOBALS['babUrlScript']."?tg=vacadma&idx=lvrp&chg=&pos=".$this->ord.$this->pos."&idvr=".$this->idvr;
				}
			else
				{
				$this->pos = $pos;
				$this->ord = "";
				$req = "select ".BAB_USERS_TBL.".* from ".BAB_USERS_TBL." join ".BAB_VAC_USERS_RIGHTS_TBL." where ".BAB_USERS_TBL.".id=".BAB_VAC_USERS_RIGHTS_TBL.".id_user and ".BAB_VAC_USERS_RIGHTS_TBL.".id_right='".$idvr."' and ".BAB_USERS_TBL.".firstname like '".$this->pos."%' ";
				$req .= "order by ".BAB_USERS_TBL.".firstname, ".BAB_USERS_TBL.".lastname asc";
				$this->fullname = bab_translate("Firstname"). " " . bab_translate("Lastname");
				$this->fullnameurl = $GLOBALS['babUrlScript']."?tg=vacadma&idx=lvrp&chg=&pos=".$this->ord.$this->pos."&idvr=".$this->idvr;
				}
			$this->idvr = $idvr;
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
				$this->url = $GLOBALS['babUrlScript']."?tg=vacadma&idx=modp&idp=".$this->arr['id']."&pos=".$this->ord.$this->pos."&idvr=".$this->idvr;
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
				$this->selecturl = $GLOBALS['babUrlScript']."?tg=vacadma&idx=lvrp&pos=".$this->ord.$this->selectname."&idvr=".$this->idvr;

				if( $this->pos == $this->selectname)
					$this->selected = 1;
				else 
					{
					if( $this->ord == "-" )
						{
						$req = "select ".BAB_USERS_TBL.".* from ".BAB_USERS_TBL." join ".BAB_VAC_USERS_RIGHTS_TBL." where ".BAB_USERS_TBL.".id=".BAB_VAC_USERS_RIGHTS_TBL.".id_user and ".BAB_VAC_USERS_RIGHTS_TBL.".id_right='".$this->idvr."' and ".BAB_USERS_TBL.".lastname like '".$this->selectname."%' ";
						}
					else
						{
						$req = "select ".BAB_USERS_TBL.".* from ".BAB_USERS_TBL." join ".BAB_VAC_USERS_RIGHTS_TBL." where ".BAB_USERS_TBL.".id=".BAB_VAC_USERS_RIGHTS_TBL.".id_user and ".BAB_VAC_USERS_RIGHTS_TBL.".id_right='".$this->idvr."' and ".BAB_USERS_TBL.".firstname like '".$this->selectname."%' ";
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
	echo bab_printTemplate($temp, "vacadma.html", "vrpersonnellist");
	return $temp->count;
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


		function temp($pos, $idvr)
			{
			$this->allname = bab_translate("All");
			$this->uncheckall = bab_translate("Uncheck all");
			$this->checkall = bab_translate("Check all");
			$this->deletealt = bab_translate("Delete");
			$this->modify = bab_translate("Modify");
			$this->db = $GLOBALS['babDB'];
			$this->idvr = $idvr;
			list($this->idtype) = $this->db->db_fetch_row($this->db->db_query("select id_type from ".BAB_VAC_RIGHTS_TBL." where id='".$idvr."'")); 

			if( $pos[0] == "-" )
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
			$this->idvr = $idvr;
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
				$this->selected = "";
				$this->nuserid = "";
				$res2 = $this->db->db_query("select id from ".BAB_VAC_COLL_TYPES_TBL." where id_type='".$this->idtype."' and id_coll ='".$this->arr['id_coll']."'");
				if( $res2 && $this->db->db_num_rows($res2) > 0 )
					{
					$this->bview = true;
					}

				if( $this->bview )
				{
					$res2 = $this->db->db_query("select id from ".BAB_VAC_USERS_RIGHTS_TBL." where id_user='".$this->arr['id']."' and id_right ='".$this->idvr."'");
					if( $res2 && $this->db->db_num_rows($res2) > 0 )
						{
						$this->selected = "checked";
						$this->nuserid = $this->arr['id'];
						}
					
					$this->url = $GLOBALS['babUrlScript']."?tg=vacadma&idx=modp&idp=".$this->arr['id']."&pos=".$this->ord.$this->pos."&idvr=".$this->idvr;
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
	echo bab_printTemplate($temp, "vacadma.html", "vrpersonnellist");
	return $temp->count;
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
			$this->db = $GLOBALS['babDB'];

			$row = $this->db->db_fetch_array($this->db->db_query("select * from ".BAB_VAC_RIGHTS_TBL." where id='".$idvr."'"));
			$this->datebegin = bab_strftime(bab_mktime($row['date_begin']), false);
			$this->dateend = bab_strftime(bab_mktime($row['date_end']), false);
			$this->dateentry = bab_strftime(bab_mktime($row['date_entry']), false);
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


function saveVacationRight($description, $userid, $collid, $idtype, $nbdays, $dateb, $datee, $vclose)
	{
	global $babBody, $babDB;

	if( $description == "")
		{
		$babBody->msgerror = bab_translate("You must specify a vacation description") ." !";
		return false;
		}

	if( $userid == "" && $collid == "" )
		{
		$babBody->msgerror = bab_translate("You must specify a user or collection") ." !";
		return false;
		}

	if( $userid != "" )
		{
		list($total) = $babDB->db_fetch_array($babDB->db_query("select count(id) as total from ".BAB_VAC_PERSONNEL_TBL." where id_user='".$userid."'"));
		if( $total == 0 )
			{
			$babBody->msgerror = bab_translate("User does'nt exist") ." !";
			return false;
			}
		}
	else 
		{
		if( $collid != -1 )
			list($total) = $babDB->db_fetch_array($babDB->db_query("select count(id) as total from ".BAB_VAC_PERSONNEL_TBL." where id_coll='".$collid."'"));
		else
			list($total) = $babDB->db_fetch_array($babDB->db_query("select count(id) as total from ".BAB_VAC_PERSONNEL_TBL));

		if( $total == 0 )
			{
			$babBody->msgerror = bab_translate("Users does'nt exist") ." !";
			return false;
			}
		}
	
	if( !is_numeric($nbdays))
		{
		$babBody->msgerror = bab_translate("You must specify a correct number days") ." !";
		return false;
		}

	$adb = explode("-", $dateb);
	if( $adb[0] == "" || $adb[1] == "" || $adb[2] == "" || !checkdate($adb[1],$adb[0],$adb[2]))
		{
		$babBody->msgerror = bab_translate("Invalid begin date") ." !";
		return false;
		}
	
	$ade = explode("-", $datee);
	if( $ade[0] == "" || $ade[1] == "" || $ade[2] == "" || !checkdate($ade[1],$ade[0],$ade[2]))
		{
		$babBody->msgerror = bab_translate("Invalid end date") ." !";
		return false;
		}

	if( $dateb > $datee)
		{
		$babBody->msgerror = bab_translate("Begin date must be less than end date") ." !";
		return false;
		}

	$dateb = sprintf("%04d-%02d-%02d", $adb[2], $adb[1], $adb[0]);
	$datee = sprintf("%04d-%02d-%02d", $ade[2], $ade[1], $ade[0]);

	if( !bab_isMagicQuotesGpcOn())
		{
		$description = addslashes($description);
		}


	$babDB->db_query("insert into ".BAB_VAC_RIGHTS_TBL." (description, id_creditor, id_type, quantity, date_entry, date_begin, date_end, active) values ('".$description."', '".$GLOBALS['BAB_SESS_USERID']."', '".$idtype."', '".$nbdays."', curdate(), '".$dateb."', '".$datee."', '".$vclose."')");
	$id = $babDB->db_insert_id();

	if( $userid != "" )
		{
		$babDB->db_query("insert into ".BAB_VAC_USERS_RIGHTS_TBL." (id_user, id_right) values ('".$userid."', '".$id."')");
		}
	else if( $collid != "" )
		{
		if( $collid != -1)
			$res = $babDB->db_query("select * from ".BAB_VAC_PERSONNEL_TBL." where id_coll='".$collid."'");
		else
			$res = $babDB->db_query("select * from ".BAB_VAC_PERSONNEL_TBL."");

		while( $arr = $babDB->db_fetch_array($res))
			{
			$babDB->db_query("insert into ".BAB_VAC_USERS_RIGHTS_TBL." (id_user, id_right) values ('".$arr['id_user']."', '".$id."')");
			}
		}

	return true;
	}


function updateVacationRight($idvr, $description, $nbdays, $dateb, $datee, $vclose)
	{
	global $babBody, $babDB;

	if( $description == "")
		{
		$babBody->msgerror = bab_translate("You must specify a vacation description") ." !";
		return false;
		}

	if( !is_numeric($nbdays))
		{
		$babBody->msgerror = bab_translate("You must specify a correct number days") ." !";
		return false;
		}

	$adb = explode("-", $dateb);
	if( $adb[0] == "" || $adb[1] == "" || $adb[2] == "" || !checkdate($adb[1],$adb[0],$adb[2]))
		{
		$babBody->msgerror = bab_translate("Invalid begin date") ." !";
		return false;
		}
	
	$ade = explode("-", $datee);
	if( $ade[0] == "" || $ade[1] == "" || $ade[2] == "" || !checkdate($ade[1],$ade[0],$ade[2]))
		{
		$babBody->msgerror = bab_translate("Invalid end date") ." !";
		return false;
		}

	if( $dateb > $datee)
		{
		$babBody->msgerror = bab_translate("Begin date must be less than end date") ." !";
		return false;
		}

	$dateb = sprintf("%04d-%02d-%02d", $adb[2], $adb[1], $adb[0]);
	$datee = sprintf("%04d-%02d-%02d", $ade[2], $ade[1], $ade[0]);

	if( !bab_isMagicQuotesGpcOn())
		{
		$description = addslashes($description);
		}


	$babDB->db_query("update ".BAB_VAC_RIGHTS_TBL." set description='".$description."', id_creditor='".$GLOBALS['BAB_SESS_USERID']."', quantity='".$nbdays."', date_entry=curdate(), date_begin='".$dateb."', date_end='".$datee."', active='".($vclose == "Y"? "N": "Y")."' where id='".$idvr."'");
	return true;
	}

function modifyVacationRightPersonnel($idvr, $userids, $nuserids)
	{
	global $babDB;
	$count = sizeof($userids);

	for( $i = 0; $i < sizeof($nuserids); $i++)
		{
		if( $nuserids[$i] != "" && ( $count == 0 || !in_array($nuserids[$i], $userids)))
			$babDB->db_query("delete from ".BAB_VAC_USERS_RIGHTS_TBL." where id_right='".$idvr."' and id_user='".$nuserids[$i]."'");
		}

	for( $i = 0; $i < $count; $i++)
		{
		if( !in_array($userids[$i], $nuserids) )
			{
			$babDB->db_query("insert into ".BAB_VAC_USERS_RIGHTS_TBL." (id_user, id_right) values ('".$userids[$i]."', '".$idvr."')");
			}
		}
	}

function deleteVacationRight($idvr)
	{
	global $babBody, $babDB;
	list($total) = $babDB->db_fetch_row($babDB->db_query("select count(id) as total from ".BAB_VAC_ENTRIES_ELEM_TBL." where id_entry='".$idvr."'"));
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

if( isset($add) )
	{
	if( $add == "addvr" )
		{
		if(!saveVacationRight($description, $userid, $collid, $idtype, $nbdays, $dateb, $datee, $vclose))
			$idx ='addvr';
		else
			{
			unset($description);
			unset($idtype);
			unset($datee);
			unset($dateb);
			unset($nbdays);
			}
		}
	else if( $add == "modvr" )
		{
		if( isset($submit ))
			{
			if(!updateVacationRight($idvr, $description, $nbdays, $dateb, $datee, $vclose))
				$idx ='modvr';
			else
				{
				unset($description);
				unset($idtype);
				unset($datee);
				unset($dateb);
				unset($nbdays);
				}
			}
		else if( isset($deleteg))
			{
			deleteVacationRight($idvr);
			}
		}
	}

switch($idx)
	{
	case "browt":
		if( !isset($pos)) $pos ="";
		browsePersonnelByType($pos, $cb, $idtype);
		exit;
		break;
	case "viewvr":
		viewVacationRightPersonnel($idvr);
		exit;
		break;

	case "delvru":
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
		$babBody->title = bab_translate("Modify vacation right");
		if( !isset($description)) $description ="";
		if( !isset($datee)) $datee ="";
		if( !isset($dateb)) $dateb ="";
		if( !isset($nbdays)) $nbdays ="";
		if( !isset($vclose)) $vclose ="";
		modifyVacationRigths($idvr, $description, $nbdays, $dateb, $datee, $vclose);
		$babBody->addItemMenu("lvt", bab_translate("Types"), $GLOBALS['babUrlScript']."?tg=vacadm&idx=lvt");
		$babBody->addItemMenu("lcol", bab_translate("Collections"), $GLOBALS['babUrlScript']."?tg=vacadm&idx=lcol");
		$babBody->addItemMenu("lper", bab_translate("Personnel"), $GLOBALS['babUrlScript']."?tg=vacadm&idx=lper");
		$babBody->addItemMenu("lrig", bab_translate("Rights"), $GLOBALS['babUrlScript']."?tg=vacadma&idx=lrig");
		$babBody->addItemMenu("modvr", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=vacadma&idx=modvr");
		$babBody->addItemMenu("lreq", bab_translate("Requests"), $GLOBALS['babUrlScript']."?tg=vacadmb&idx=lreq");
		break;

	case "addvr":
		$babBody->title = bab_translate("Allocate vacation rights");
		if( !isset($description)) $description ="";
		if( !isset($userid)) $userid ="";
		if( !isset($groupid)) $groupid ="";
		if( !isset($datee)) $datee ="";
		if( !isset($dateb)) $dateb ="";
		if( !isset($nbdays)) $nbdays ="";
		if( !isset($vclose)) $vclose ="";
		if( !isset($idtype)) $idtype ="";
		addVacationRigths($description, $userid, $groupid, $idtype, $nbdays, $dateb, $datee, $vclose);
		$babBody->addItemMenu("lvt", bab_translate("Types"), $GLOBALS['babUrlScript']."?tg=vacadm&idx=lvt");
		$babBody->addItemMenu("lcol", bab_translate("Collections"), $GLOBALS['babUrlScript']."?tg=vacadm&idx=lcol");
		$babBody->addItemMenu("lper", bab_translate("Personnel"), $GLOBALS['babUrlScript']."?tg=vacadm&idx=lper");
		$babBody->addItemMenu("lrig", bab_translate("Rights"), $GLOBALS['babUrlScript']."?tg=vacadma&idx=lrig");
		$babBody->addItemMenu("addvr", bab_translate("Add"), $GLOBALS['babUrlScript']."?tg=vacadma&idx=addvr");
		$babBody->addItemMenu("lreq", bab_translate("Requests"), $GLOBALS['babUrlScript']."?tg=vacadmb&idx=lreq");
		break;

	case "lrig":
	default:
		$babBody->title = bab_translate("Vacations rights");
		if( !isset($datee)) $datee ="";
		if( !isset($dateb)) $dateb ="";
		if( !isset($idtype)) $idtype ="";
		if( !isset($idcreditor)) $idcreditor ="";
		if( !isset($active)) $active ="Y";
		if( !isset($pos)) $pos =0;
		listVacationRigths($idtype, $idcreditor, $dateb, $datee, $active, $pos);
		$babBody->addItemMenu("lvt", bab_translate("Types"), $GLOBALS['babUrlScript']."?tg=vacadm&idx=lvt");
		$babBody->addItemMenu("lcol", bab_translate("Collections"), $GLOBALS['babUrlScript']."?tg=vacadm&idx=lcol");
		$babBody->addItemMenu("lper", bab_translate("Personnel"), $GLOBALS['babUrlScript']."?tg=vacadm&idx=lper&pos=".$pos."&idcol=".$idcol."&idsa=".$idsa);
		$babBody->addItemMenu("lrig", bab_translate("Rights"), $GLOBALS['babUrlScript']."?tg=vacadma&idx=lrig");
		$babBody->addItemMenu("addvr", bab_translate("Add"), $GLOBALS['babUrlScript']."?tg=vacadma&idx=addvr");
		$babBody->addItemMenu("lreq", bab_translate("Requests"), $GLOBALS['babUrlScript']."?tg=vacadmb&idx=lreq");
		break;
	}
$babBody->setCurrentItemMenu($idx);
?>
