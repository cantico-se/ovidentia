<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
include_once "base.php";
include_once $babInstallPath."utilit/afincl.php";
include_once $babInstallPath."utilit/mailincl.php";
include_once $babInstallPath."utilit/vacincl.php";

function addVacationType($vtid, $what, $tname, $description, $quantity)
	{
	global $babBody, $babDB;
	class temp
		{
		var $name;
		var $description;
		var $quantity;
		var $tnameval;
		var $descriptionval;
		var $quantityval;
		var $vtid;
		var $what;

		var $invalidentry1;

		var $add;

		function temp($vtid, $what, $tname, $description, $quantity)
			{
			global $babDB;
			$this->what = $what;
			$this->vtid = $vtid;
			$this->name = bab_translate("Name");
			$this->description = bab_translate("Description");
			$this->quantity = bab_translate("Quantity");

			$this->invalidentry1 = bab_translate("Invalid entry!  Only numbers are accepted and . !");

			if( $what == "modvt" && empty($tname))
				{
				$arr = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_VAC_TYPES_TBL." where id='".$vtid."'"));
				$this->tnameval = $arr['name'];
				$this->descriptionval = $arr['description'];
				$this->quantityval = $arr['quantity'];
				}
			else
				{
				$this->tnameval = $tname;
				$this->descriptionval = $description;
				$this->quantityval = $quantity;
				}

			if( $what == "modvt" )
				{
				$this->bdel = true;
				$this->del = bab_translate("Delete");
				$this->add = bab_translate("Modify");
				}
			else
				{
				$this->bdel = false;
				$this->add = bab_translate("Add");
				}
			}
		}

	list($count) = $babDB->db_fetch_row($babDB->db_query("select count(*) as total from ".BAB_VAC_TYPES_TBL));
	$temp = new temp($vtid, $what, $tname, $description, $quantity);
	$babBody->babecho(	bab_printTemplate($temp,"vacadm.html", "vtypecreate"));
	return $count;
	}

function listVacationCollections()
	{
	global $babBody;

	class temp
		{
		var $nametxt;
		var $urlname;
		var $url;
		var $descriptiontxt;
		var $description;
				
		var $arr = array();
		var $db;
		var $count;
		var $res;

		function temp()
			{
			$this->nametxt = bab_translate("Name");
			$this->descriptiontxt = bab_translate("Description");
			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_VAC_COLLECTIONS_TBL." order by name asc";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $this->db->db_fetch_array($this->res);
				$this->url = $GLOBALS['babUrlScript']."?tg=vacadm&idx=modvc&id=".$arr['id'];
				$this->urlname = $arr['name'];
				$this->description = $arr['description'];
				$i++;
				return true;
				}
			else
				return false;

			}
		}

	$temp = new temp();
	$babBody->babecho(	bab_printTemplate($temp, "vacadm.html", "vcollist"));
	return $temp->count;

	}

function listVacationTypes()
	{
	global $babBody;

	class temp
		{
		var $nametxt;
		var $urlname;
		var $url;
		var $descriptiontxt;
		var $quantitytxt;
		var $description;
		var $quantity;
		var $altaddvr;
				
		var $arr = array();
		var $db;
		var $count;
		var $res;

		function temp()
			{
			$this->nametxt = bab_translate("Name");
			$this->descriptiontxt = bab_translate("Description");
			$this->quantitytxt = bab_translate("Quantity");
			$this->altaddvr = bab_translate("Allocate vacation rights");
			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_VAC_TYPES_TBL." order by name asc";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $this->db->db_fetch_array($this->res);
				$this->url = $GLOBALS['babUrlScript']."?tg=vacadm&idx=modvt&id=".$arr['id'];
				$this->urlname = $arr['name'];
				$this->description = $arr['description'];
				$this->quantity = $arr['quantity'];
				$this->addurl = $GLOBALS['babUrlScript']."?tg=vacadma&idx=addvr&idtype=".$arr['id'];
				$i++;
				return true;
				}
			else
				return false;

			}
		}

	$temp = new temp();
	$babBody->babecho(	bab_printTemplate($temp, "vacadm.html", "vtypelist"));
	return $temp->count;

	}

function addVacationCollection($vcid, $what, $tname, $description, $vtypeids)
	{
	global $babBody;
	class temp
		{
		var $name;
		var $description;
		var $vactypes;
		var $tnameval;
		var $descriptionval;
		var $add;
		var $vtypename;
		var $vtcheck;
		var $vcid;
		var $vtids = array();

		var $arr = array();
		var $db;
		var $count;
		var $res;
		function temp($vcid, $what, $tname, $description, $vtypeids)
			{
			global $babDB;
			$this->name = bab_translate("Name");
			$this->description = bab_translate("Description");
			$this->vactypes = bab_translate("Vacations types");
			$this->vcid = $vcid;

			if( $what == "modvc")
				{
				$this->bdel = true;
				$this->del = bab_translate("Delete");
				$this->add = bab_translate("Modify");
				}
			else
				{
				$this->bdel = false;
				$this->add = bab_translate("Add");
				}

			if( $what == "modvc" && empty($tname))
				{
				$arr = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_VAC_COLLECTIONS_TBL." where id='".$vcid."'"));
				$this->tnameval = $arr['name'];
				$this->descriptionval = $arr['description'];
				$res = $babDB->db_query("select * from ".BAB_VAC_COLL_TYPES_TBL." where id_coll='".$vcid."'");
				while( $arr = $babDB->db_fetch_array($res))
					$this->vtids[] = $arr['id_type'];
				}
			else
				{
				$this->vtids = $vtypeids;
				$this->tnameval = $tname;
				$this->descriptionval = $description;
				}

			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_VAC_TYPES_TBL." order by name asc";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $this->db->db_fetch_array($this->res);
				$this->vtypename = $arr['name'];
				$this->vtid = $arr['id'];
				if( count($this->vtids) > 0  && in_array($arr['id'], $this->vtids))
					$this->vtcheck = "checked";
				else
					$this->vtcheck = "";
				$i++;
				return true;
				}
			else
				return false;

			}
		}

	$temp = new temp($vcid, $what, $tname, $description, $vtypeids);
	$babBody->babecho(	bab_printTemplate($temp,"vacadm.html", "vcolcreate"));
	}


function listVacationPersonnel($pos, $idcol, $idsa)
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
		var $checkall;
		var $uncheckall;

		var $filteron;
		var $idcollection;
		var $idsapp;
		var $collname;
		var $saname;

		var $addpurl;
		var $addpersonnel;
		var $deletealt;
		var $altlrbu;
		var $lrbuurl;
		var $calurl;
		var $altcal;

		function temp($pos, $idcol, $idsa)
			{
			$this->allname = bab_translate("All");
			$this->uncheckall = bab_translate("Uncheck all");
			$this->checkall = bab_translate("Check all");
			$this->filteron = bab_translate("Filter on");
			$this->collection = bab_translate("Collection");
			$this->appschema = bab_translate("Approbation schema");
			$this->addpersonnel = bab_translate("Add");
			$this->deletealt = bab_translate("Delete");
			$this->altlrbu = bab_translate("Rights");
			$this->altcal = bab_translate("Calendar");
			$this->addpurl = $GLOBALS['babUrlScript']."?tg=vacadm&idx=addp&pos=".$pos."&idcol=".$idcol."&idsa=".$idsa;
			$this->db = $GLOBALS['babDB'];

			if( $pos[0] == "-" )
				{
				$this->pos = $pos[1];
				$this->ord = $pos[0];
				$req = "select ".BAB_USERS_TBL.".*, ".BAB_VAC_PERSONNEL_TBL.".id_sa, ".BAB_VAC_PERSONNEL_TBL.".id_coll from ".BAB_USERS_TBL." join ".BAB_VAC_PERSONNEL_TBL." where ".BAB_USERS_TBL.".id=".BAB_VAC_PERSONNEL_TBL.".id_user and ".BAB_USERS_TBL.".lastname like '".$this->pos."%' ";
				if( !empty($idcol))
					$req .= "and ".BAB_VAC_PERSONNEL_TBL.".id_coll='".$idcol."'";
				if( !empty($idsa))
					$req .= "and ".BAB_VAC_PERSONNEL_TBL.".id_sa='".$idsa."'";
				$req .= "order by ".BAB_USERS_TBL.".lastname, ".BAB_USERS_TBL.".firstname asc";
				$this->fullname = bab_translate("Lastname"). " " . bab_translate("Firstname");
				$this->fullnameurl = $GLOBALS['babUrlScript']."?tg=vacadm&idx=lper&chg=&pos=".$this->ord.$this->pos."&idcol=".$this->idcol."&idsa=".$this->idsa;
				}
			else
				{
				$this->pos = $pos;
				$this->ord = "";
				$req = "select ".BAB_USERS_TBL.".*, ".BAB_VAC_PERSONNEL_TBL.".id_sa, ".BAB_VAC_PERSONNEL_TBL.".id_coll from ".BAB_USERS_TBL." join ".BAB_VAC_PERSONNEL_TBL." where ".BAB_USERS_TBL.".id=".BAB_VAC_PERSONNEL_TBL.".id_user and ".BAB_USERS_TBL.".firstname like '".$this->pos."%' ";
				if( !empty($idcol))
					$req .= "and ".BAB_VAC_PERSONNEL_TBL.".id_coll='".$idcol."'";
				if( !empty($idsa))
					$req .= "and ".BAB_VAC_PERSONNEL_TBL.".id_sa='".$idsa."'";
				$req .= "order by ".BAB_USERS_TBL.".firstname, ".BAB_USERS_TBL.".lastname asc";
				$this->fullname = bab_translate("Firstname"). " " . bab_translate("Lastname");
				$this->fullnameurl = $GLOBALS['babUrlScript']."?tg=vacadm&idx=lper&chg=&pos=".$this->ord.$this->pos."&idcol=".$this->idcol."&idsa=".$this->idsa;
				}

			$this->idcol = $idcol;
			$this->idsa = $idsa;
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);

			if( empty($this->pos))
				$this->allselected = 1;
			else
				$this->allselected = 0;
			$this->allurl = $GLOBALS['babUrlScript']."?tg=vacadm&idx=lper&pos=&idcol=".$this->idcol."&idsa=".$this->idsa;

			$this->sares = $this->db->db_query("select * from ".BAB_FLOW_APPROVERS_TBL."");
			if( !$this->sares )
				$this->countsa = 0;
			else
				$this->countsa = $this->db->db_num_rows($this->sares);

			$this->colres = $this->db->db_query("select * from ".BAB_VAC_COLLECTIONS_TBL." order by name asc");
			$this->countcol = $this->db->db_num_rows($this->colres);
			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$this->arr = $this->db->db_fetch_array($this->res);
				$this->url = $GLOBALS['babUrlScript']."?tg=vacadm&idx=modp&idp=".$this->arr['id']."&pos=".$this->ord.$this->pos;
				if( $this->ord == "-" )
					$this->urlname = bab_composeUserName($this->arr['lastname'],$this->arr['firstname']);
				else
					$this->urlname = bab_composeUserName($this->arr['firstname'],$this->arr['lastname']);

				$this->userid = $this->arr['id'];
				$this->lrbuurl = $GLOBALS['babUrlScript']."?tg=vacadm&idx=lrbu&idu=".$this->userid;
				$this->calurl = $GLOBALS['babUrlScript']."?tg=vacadm&idx=cal&idu=".$this->userid;
				$arr = $this->db->db_fetch_array($this->db->db_query("select name from ".BAB_VAC_COLLECTIONS_TBL." where id='".$this->arr['id_coll']."'"));
				$this->collname = $arr['name'];
				$arr = $this->db->db_fetch_array($this->db->db_query("select name from ".BAB_FLOW_APPROVERS_TBL." where id='".$this->arr['id_sa']."'"));
				$this->saname = $arr['name'];
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
				$this->selecturl = $GLOBALS['babUrlScript']."?tg=vacadm&idx=lper&pos=".$this->ord.$this->selectname."&idcol=".$this->idcol."&idsa=".$this->idsa;

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
					if( !empty($this->idcol))
						$req .= "and ".BAB_VAC_PERSONNEL_TBL.".id_coll='".$this->idcol."'";
					if( !empty($this->idsa))
						$req .= "and ".BAB_VAC_PERSONNEL_TBL.".id_sa='".$this->idsa."'";
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


		function getnextsa()
			{
			static $j= 0;
			if( $j < $this->countsa )
				{
				$arr = $this->db->db_fetch_array($this->sares);
				$this->saname = $arr['name'];
				$this->idsapp = $arr['id'];
				if( $this->idsa == $this->idsapp )
					$this->selected = "selected";
				else
					$this->selected = "";
				$j++;
				return true;
				}
			else
				return false;
			}

		function getnextcol()
			{
			static $j= 0;
			if( $j < $this->countcol )
				{
				$arr = $this->db->db_fetch_array($this->colres);
				$this->collname = $arr['name'];
				$this->idcollection = $arr['id'];
				if( $this->idcol == $this->idcollection )
					$this->selected = "selected";
				else
					$this->selected = "";
				$j++;
				return true;
				}
			else
				return false;
			}
		}

	$temp = new temp($pos, $idcol, $idsa);
	$babBody->babecho(	bab_printTemplate($temp, "vacadm.html", "vpersonnellist"));
	return $temp->count;
	}

function addVacationPersonnel($idcol, $idsa, $iduser)
	{
	global $babBody;
	class temp
		{
		var $usertext;
		var $grouptext;
		var $userval;
		var $userid;
		var $groupval;
		var $groupid;
		var $collection;
		var $idcollection;
		var $collname;
		var $appschema;
		var $idsapp;
		var $saname;
		var $selected;
		var $add;
		var $bdel;
		var $delete;
		var $groupsbrowurl;
		var $usersbrowurl;
		var $db;
		var $orand;
		var $reset;
		var $what;

		function temp($idcol, $idsa, $iduser)
			{
			$this->usertext = bab_translate("User");
			$this->grouptext = bab_translate("Group");
			$this->collection = bab_translate("Collection");
			$this->appschema = bab_translate("Approbation schema");
			$this->reset = bab_translate("Reset");
			$this->orand = bab_translate("And / Or");
			$this->delete = bab_translate("Delete");
			$this->usersbrowurl = $GLOBALS['babUrlScript']."?tg=users&idx=brow&cb=";
			$this->groupsbrowurl = $GLOBALS['babUrlScript']."?tg=groups&idx=brow&cb=";

			$this->db = $GLOBALS['babDB'];
			if( !empty($iduser))
				{
				$this->add = bab_translate("Modify");
				$arr = $this->db->db_fetch_array($this->db->db_query("select * from ".BAB_VAC_PERSONNEL_TBL." where id_user='".$iduser."'"));
				$this->userval = bab_getUserName($iduser);
				$this->userid = $iduser;
				$this->bdel = true;
				$this->what = "modp";
				$this->idcol = $arr['id_coll'];
				$this->idsa = $arr['id_sa'];
				}
			else
				{
				$this->add = bab_translate("Add");
				$this->idcol = $idcol;
				$this->idsa = $idsa;
				$this->userval = "";
				$this->userid = "";
				$this->bdel = false;
				$this->what = "addp";
				}

			$this->groupval = "";
			$this->groupid = "";

			$this->sares = $this->db->db_query("select * from ".BAB_FLOW_APPROVERS_TBL."");
			if( !$this->sares )
				$this->countsa = 0;
			else
				$this->countsa = $this->db->db_num_rows($this->sares);

			$this->colres = $this->db->db_query("select * from ".BAB_VAC_COLLECTIONS_TBL." order by name asc");
			$this->countcol = $this->db->db_num_rows($this->colres);
			}
		
		function getnextsa()
			{
			static $j= 0;
			if( $j < $this->countsa )
				{
				$arr = $this->db->db_fetch_array($this->sares);
				$this->saname = $arr['name'];
				$this->idsapp = $arr['id'];
				$this->idsapp = $arr['id'];
				if( $this->idsa == $this->idsapp )
					$this->selected = "selected";
				else
					$this->selected = "";
				$j++;
				return true;
				}
			else
				return false;
			}

		function getnextcol()
			{
			static $j= 0;
			if( $j < $this->countcol )
				{
				$arr = $this->db->db_fetch_array($this->colres);
				$this->collname = $arr['name'];
				$this->idcollection = $arr['id'];
				if( $this->idcol == $this->idcollection )
					$this->selected = "selected";
				else
					$this->selected = "";
				$j++;
				return true;
				}
			else
				return false;
			}
		}

	$temp = new temp($idcol, $idsa, $iduser);
	$babBody->babecho(	bab_printTemplate($temp,"vacadm.html", "personnelcreate"));
	}


function deleteVacationPersonnel($pos, $idcol, $idsa, $userids)
	{
	global $babBody, $idx;

	class tempa
		{
		var $warning;
		var $message;
		var $title;
		var $urlyes;
		var $urlno;
		var $yes;
		var $no;

		function tempa($pos, $idcol, $idsa, $userids)
			{
			global $BAB_SESS_USERID;
			$this->message = bab_translate("Are you sure you want to remove those users");
			$this->title = "";
			$items = "";
			$db = $GLOBALS['babDB'];
			for($i = 0; $i < count($userids); $i++)
				{
				$req = "select * from ".BAB_USERS_TBL." where id='".$userids[$i]."'";
				$res = $db->db_query($req);
				if( $db->db_num_rows($res) > 0)
					{
					$arr = $db->db_fetch_array($res);
					$this->title .= "<br>". bab_composeUserName($arr['firstname'], $arr['lastname']);
					$items .= $arr['id'];
					}
				if( $i < count($item) -1)
					$items .= ",";
				}
			$this->warning = bab_translate("WARNING: This operation will remove users and their references"). "!";
			$this->urlyes = $GLOBALS['babUrlScript']."?tg=vacadm&idx=lper&pos=".$pos."&idcol=".$idcol."&idsa=".$idsa."&action=Yes&items=".$items;
			$this->yes = bab_translate("Yes");
			$this->urlno = $GLOBALS['babUrlScript']."?tg=vacadm&idx=lper&pos=".$pos."&idcol=".$idcol."&idsa=".$idsa;
			$this->no = bab_translate("No");
			}
		}

	if( count($userids) <= 0)
		{
		$babBody->msgerror = bab_translate("Please select at least one item");
		listVacationPersonnel($pos, $idcol, $idsa);
		$idx = "lper";
		return;
		}
	$tempa = new tempa($pos, $idcol, $idsa, $userids);
	$babBody->babecho(	bab_printTemplate($tempa,"warning.html", "warningyesno"));
	}

function listRightsByUser($id)
	{
	global $babBody;

	class temp
		{
		var $nametxt;
		var $urlname;
		var $url;
		var $descriptiontxt;
		var $description;
		var $consumedtxt;
		var $consumed;
		var $fullname;
		var $titletxt;
				
		var $arr = array();
		var $db;
		var $count;
		var $res;

		var $iduser;
		var $idcoll;
		var $bview;

		function temp($id)
			{
			$this->iduser = $id;
			$this->desctxt = bab_translate("Description");
			$this->consumedtxt = bab_translate("Consumed");
			$this->datebtxt = bab_translate("Begin date");
			$this->dateetxt = bab_translate("End date");
			$this->quantitytxt = bab_translate("Quantity");
			$this->datetxt = bab_translate("Entry date");
			$this->titletxt = bab_translate("Vacation rights of:");
			$this->fullname = bab_getUserName($id);
			$this->db = $GLOBALS['babDB'];
			$this->res = $this->db->db_query("select * from ".BAB_VAC_USERS_RIGHTS_TBL." where id_user='".$id."' order by id desc");
			$this->count = $this->db->db_num_rows($this->res);
			list($this->idcoll) = $this->db->db_fetch_row($this->db->db_query("select id_coll from ".BAB_VAC_PERSONNEL_TBL." where id_user='".$id."'"));
			}

		function getnextright()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $this->db->db_fetch_array($this->res);
				$row = $this->db->db_fetch_array($this->db->db_query("select * from ".BAB_VAC_RIGHTS_TBL." where id='".$arr['id_right']."'"));
				$res = $this->db->db_query("select id from ".BAB_VAC_COLL_TYPES_TBL." where id_coll='".$this->idcoll."' and id_type='".$row['id_type']."'");
				$this->bview = false;
				if( $res && $this->db->db_num_rows($res) > 0 )
					{
					$this->description = $row['description'];
					$this->quantity = $row['quantity'];
					$this->date = bab_printDate($row['date_entry']);
					$this->dateb = bab_printDate($row['date_begin']);
					$this->datee = bab_printDate($row['date_end']);
					$arr = $this->db->db_fetch_array($this->db->db_query("select sum(quantity) as total from ".BAB_VAC_ENTRIES_ELEM_TBL." join ".BAB_VAC_ENTRIES_TBL." where ".BAB_VAC_ENTRIES_TBL.".id_user='".$this->iduser."' and ".BAB_VAC_ENTRIES_TBL.".status='Y' and ".BAB_VAC_ENTRIES_ELEM_TBL.".id_type='".$row['id']."' and ".BAB_VAC_ENTRIES_ELEM_TBL.".id_entry=".BAB_VAC_ENTRIES_TBL.".id"));
					$this->consumed = isset($arr['total'])? $arr['total'] : 0;
					$this->bview = true;
					}
				$i++;
				return true;
				}
			else
				return false;

			}
		}

	$temp = new temp($id);
	echo bab_printTemplate($temp, "vacadm.html", "rlistbyuser");
	}

function viewCalendarByUser($id, $month, $year)
	{
	global $babBody;

	class temp
		{
		var $entries = array();
		var $fullname;
		var $vacwaitingtxt;
		var $vacapprovedtxt;
		var $print;


		function temp($id, $month, $year)
			{
			global $babMonths, $babDB;
			$this->month = $month;
			$this->year = $year;
			$this->iduser = $id;
			$this->fullname = bab_getUserName($id);
			$this->vacwaitingtxt = bab_translate("Waiting vacation request");
			$this->vacapprovedtxt = bab_translate("Approved vacation request");
			$this->print = bab_translate("Print");

			$urltmp = $GLOBALS['babUrlScript']."?tg=vacadm&idx=cal&idu=".$this->iduser;
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
			}

		function getdayname()
			{
			global $babDays;
			static $i = 1;
			if( $i <= 31)
				{
				$this->dayname = $i;
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
				$this->monthname = $GLOBALS['babMonths'][$this->curmonth];
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
					$dayweek = date("w", mktime(0,0,0,$this->curmonth,$d,$this->curyear));
					if( $dayweek == 0 || $dayweek == 6)
						$this->weekend = true;
					else
						$this->weekend = false;
					$this->bvac = false;
					$this->bwait = false;
					$day = sprintf("%04d-%02d-%02d", $this->curyear, $this->curmonth, $d);
					for( $k=0; $k < count($this->entries); $k++)
						{
						if( $day >= $this->entries[$k]['db'] && $day <= $this->entries[$k]['de'] )
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

		}

	$temp = new temp($id, $month, $year);
	echo bab_printTemplate($temp, "vacadm.html", "calendarbyuser");
	}

function saveVacationType($tname, $description, $quantity, $maxdays=0, $mindays=0, $default=0)
	{
	global $babBody;
	if( empty($tname))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a name")." !";
		return false;
		}

	if( !bab_isMagicQuotesGpcOn())
		{
		$tname = addslashes($tname);
		$description = addslashes($description);
		}

	$db = $GLOBALS['babDB'];

	$req = "select id from ".BAB_VAC_TYPES_TBL." where name='".$tname."'";
	$res = $db->db_query($req);
	if( $res && $db->db_num_rows($res) > 0 )
		{
		$babBody->msgerror = bab_translate("This vacation type already exists") ." !";
		return false;
		}
	
	$req = "insert into ".BAB_VAC_TYPES_TBL." ( name, description, quantity, maxdays, mindays, defaultdays)";
	$req .= " values ('".$tname."', '" .$description. "', '" .$quantity. "', '" .$maxdays. "', '" .$mindays. "', '" .$default. "')";
	$res = $db->db_query($req);
	return true;
	}

function updateVacationType($vtid, $tname, $description, $quantity, $maxdays=0, $mindays=0, $default=0)
	{
	global $babBody;
	if( empty($tname))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a name")." !";
		return false;
		}

	if( !bab_isMagicQuotesGpcOn())
		{
		$tname = addslashes($tname);
		$description = addslashes($description);
		}

	$db = $GLOBALS['babDB'];

	$req = "select id from ".BAB_VAC_TYPES_TBL." where name='".$tname."' and id!='".$vtid."'";
	$res = $db->db_query($req);
	if( $res && $db->db_num_rows($res) > 0 )
		{
		$babBody->msgerror = bab_translate("This vacation type already exists") ." !";
		return false;
		}
	
	$req = "update ".BAB_VAC_TYPES_TBL." set name='".$tname."', description='".$description."', quantity='".$quantity."', maxdays='".$maxdays."', mindays='".$mindays."', defaultdays='".$default."' where id='".$vtid."'";
	$res = $db->db_query($req);
	return true;
	}

function deleteVacationType($vtid)
	{
	global $babBody, $babDB;
	$bdel = true;

	list($total) = $babDB->db_fetch_array($babDB->db_query("select count(id) as total from ".BAB_VAC_COLL_TYPES_TBL." where id_type='".$vtid."'"));
	if( $total > 0 )
		{
		$bdel = false;	
		}
	else 
		{
		list($total) = $babDB->db_fetch_array($babDB->db_query("select count(id) as total from ".BAB_VAC_ENTRIES_ELEM_TYPES_TBL." where id_type='".$vtid."'"));
		if( $total > 0 )
			$bdel = false;
		else
			{
			list($total) = $babDB->db_fetch_array($babDB->db_query("select count(id) as total from ".BAB_VAC_RIGHTS_TYPES_TBL." where id_type='".$vtid."'"));
			if( $total > 0 )
				$bdel = false;
			}
		}

	if( $bdel )
		{
		$babDB->db_query("delete from ".BAB_VAC_TYPES_TBL." where id='".$vtid."'");
		$babDB->db_query("delete from ".BAB_VAC_COLL_TYPES_TBL." where id_type='".$vtid."'");
		}
	else
		$babBody->msgerror = bab_translate("This vacation type is used and can't be deleted") ." !";
	}

function saveVacationCollection($tname, $description, $vtypeids)
	{
	global $babBody;
	if( empty($tname))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a name")." !";
		return false;
		}

	if( count($vtypeids) == 0)
		{
		$babBody->msgerror = bab_translate("ERROR: You must check at least one vacation type")." !";
		return false;
		}

	if( !bab_isMagicQuotesGpcOn())
		{
		$tname = addslashes($tname);
		$description = addslashes($description);
		}

	$db = $GLOBALS['babDB'];

	$req = "select id from ".BAB_VAC_COLLECTIONS_TBL." where name='".$tname."'";
	$res = $db->db_query($req);
	if( $res && $db->db_num_rows($res) > 0 )
		{
		$babBody->msgerror = bab_translate("This collection already exists") ." !";
		return false;
		}
	
	$req = "insert into ".BAB_VAC_COLLECTIONS_TBL." ( name, description )";
	$req .= " values ('".$tname."', '" .$description. "')";
	$res = $db->db_query($req);
	$id = $db->db_insert_id();
	for( $i=0; $i < count($vtypeids); $i++)
		{
		$db->db_query("insert into ".BAB_VAC_COLL_TYPES_TBL." (id_coll, id_type) values ('".$id."', '".$vtypeids[$i]."')");
		}
	return true;
	}

function updateVacationCollection($vcid, $tname, $description, $vtypeids)
	{
	global $babBody;
	if( empty($tname))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a name")." !";
		return false;
		}

	if( !bab_isMagicQuotesGpcOn())
		{
		$tname = addslashes($tname);
		$description = addslashes($description);
		}

	$db = $GLOBALS['babDB'];

	$req = "select id from ".BAB_VAC_COLLECTIONS_TBL." where name='".$tname."' and id!='".$vcid."'";
	$res = $db->db_query($req);
	if( $res && $db->db_num_rows($res) > 0 )
		{
		$babBody->msgerror = bab_translate("This collection already exists") ." !";
		return false;
		}
	
	$req = "update ".BAB_VAC_COLLECTIONS_TBL." set name='".$tname."', description='".$description."' where id='".$vcid."'";
	$res = $db->db_query($req);

	if( count($vtypeids) > 0 )
		{
		$vtexist = array();
		$res = $db->db_query("select * from ".BAB_VAC_COLL_TYPES_TBL." where id_coll='".$vcid."'");
		while( $arr = $db->db_fetch_array($res))
			{
			if( !in_array($arr['id_type'], $vtypeids ))
				{
				$db->db_query("delete from ".BAB_VAC_COLL_TYPES_TBL." where id='".$arr['id']."'");
				}
			else
				$vtexist[] = $arr['id'];
			}

		for( $i=0; $i < count($vtypeids); $i++)
			{
			if( count($vtexist) > 0 && !in_array($vtypeids[$i], $vtexist))
				$db->db_query("insert into ".BAB_VAC_COLL_TYPES_TBL." (id_coll, id_type) values ('".$vcid."', '".$vtypeids[$i]."')");
			}
	
		}
	else
		$db->db_query("delete from ".BAB_VAC_COLL_TYPES_TBL." where id_coll='".$vcid."'");
	return true;
	}

function saveVacationPersonnel($userid, $groupid, $idcol, $idsa)
	{
	global $babBody, $babDB;
	if( empty($userid) && empty($groupid) )
		{
		$babBody->msgerror = bab_translate("You must specify a user or group") ." !";
		return false;
		}

	if( !isset($idcol) || empty($idcol) )
		{
		$babBody->msgerror = bab_translate("You must specify a vacation collection") ." !";
		return false;
		}

	if( !isset($idsa) || empty($idsa) )
		{
		$babBody->msgerror = bab_translate("You must specify approbation schema") ." !";
		return false;
		}

	if( !empty($userid))
		{
		$res = $babDB->db_query("select id from ".BAB_VAC_PERSONNEL_TBL." where id_user='".$userid."'");
		if( $res && $babDB->db_num_rows($res) > 0 )
			{
			$babBody->msgerror = bab_translate("This user already exist in personnel list") ." !";
			return false;
			}
		$babDB->db_query("insert into ".BAB_VAC_PERSONNEL_TBL." ( id_user, id_coll, id_sa) values ('".$userid."','".$idcol."','".$idsa."')");
		}

	if( !empty($groupid))
		{
		if( $groupid == 1 )
			$res = $babDB->db_query("select id as id_user from ".BAB_USERS_TBL." where is_confirmed='1'");
		else
			$res = $babDB->db_query("select id_object as id_user from ".BAB_USERS_GROUPS_TBL." where id_group='".$groupid."'");

		while( $arr = $babDB->db_fetch_array($res))
			{
			$res2 = $babDB->db_query("select id from ".BAB_VAC_PERSONNEL_TBL." where id_user='".$arr['id_user']."'");
			if( $res2 && $babDB->db_num_rows($res2) > 0 )
				{
				continue;
				}
			else
				$babDB->db_query("insert into ".BAB_VAC_PERSONNEL_TBL." ( id_user, id_coll, id_sa) values ('".$arr['id_user']."','".$idcol."','".$idsa."')");
			}
		}
	
	return true;
	}

function updateVacationUser($userid, $idsa)
{
	global $babDB;

	$res = $babDB->db_query("select * from ".BAB_VAC_ENTRIES_TBL." where id_user='".$userid."' and status=''");
	while( $row = $babDB->db_fetch_array($res))
		{
		if( $row['idfai'] != 0 )
			deleteFlowInstance($row['idfai']);
		$idfai = makeFlowInstance($idsa, "vac-".$row['id']);
		$babDB->db_query("update ".BAB_VAC_ENTRIES_TBL." set idfai='".$idfai."' where id='".$row['id']."'");
		$nfusers = getWaitingApproversFlowInstance($idfai, true);
		notifyVacationApprovers($row['id'], $nfusers);
		}
}

function updateVacationPersonnel($userid, $groupid, $idcol, $idsa)
	{
	global $babBody, $babDB;

	if( empty($userid) && empty($groupid) )
		{
		$babBody->msgerror = bab_translate("You must specify a user or group") ." !";
		return false;
		}

	if( !empty($userid))
		{
		$res = $babDB->db_query("select id from ".BAB_VAC_PERSONNEL_TBL." where id_user='".$userid."'");

		if( $res && $babDB->db_num_rows($res) > 0 )
			{
			$arr = $babDB->db_fetch_array($res);

			$babDB->db_query("update ".BAB_VAC_PERSONNEL_TBL." set id_coll='".$idcol."', id_sa='".$idsa."' where id_user='".$userid."'");

			if( $arr['id_sa'] != $idsa )
				{
				updateVacationUser($userid, $idsa);
				}

			}
		else
			{
			$babBody->msgerror = bab_translate("This user does'nt exist in personnel list") ." !";
			return false;
			}
		}

	if( !empty($groupid))
		{
		if( $groupid == 1 )
			{
			$res = $babDB->db_query("select id_user, id_sa from ".BAB_VAC_PERSONNEL_TBL."");
			while( $arr = $babDB->db_fetch_array($res))
				{
				if( $arr['id_sa'] != $idsa )
					{
					updateVacationUser($arr['id_user'], $idsa);
					}
				}
			$babDB->db_query("update ".BAB_VAC_PERSONNEL_TBL." set id_coll='".$idcol."', id_sa='".$idsa."'");
			}
		else
			{
			$res = $babDB->db_query("select id_object as id_user from ".BAB_USERS_GROUPS_TBL." where id_group='".$groupid."'");

			while( $arr = $babDB->db_fetch_array($res))
				{
				$row = $babDB->db_fetch_array($babDB->db_query("select id_sa from ".BAB_VAC_PERSONNEL_TBL." where id_user='".$arr['id_user']."'"));
				if( $row['id_sa'] != $idsa )
					{
					updateVacationUser($arr['id_user'], $idsa);
					}
				$babDB->db_query("update ".BAB_VAC_PERSONNEL_TBL." set id_coll='".$idcol."', id_sa='".$idsa."' where id_user='".$arr['id_user']."'");
				}
			}
		}
	
	return true;
	}

function deleteVacationCollection($vcid)
	{
	global $babDB;
	$bdel = true;

	list($total) = $babDB->db_fetch_array($babDB->db_query("select count(id) as total from ".BAB_VAC_PERSONNEL_TBL." where id_coll='".$vcid."'"));
	if( $total > 0 )
		{
		$bdel = false;	
		}

	if( $bdel )
		{
		$babDB->db_query("delete from ".BAB_VAC_COLLECTIONS_TBL." where id='".$vcid."'");
		$babDB->db_query("delete from ".BAB_VAC_COLL_TYPES_TBL." where id_coll='".$vcid."'");
		}
	else
		$babBody->msgerror = bab_translate("This vacation collection is used and can't be deleted") ." !";
	}

function confirmDeletePersonnel($items)
	{
	global $babDB;
	$arr = explode(",", $items);
	$cnt = count($arr);
	for($i = 0; $i < $cnt; $i++)
		{
		$res = $babDB->db_query("select id from ".BAB_VAC_ENTRIES_TBL." where id_user='".$arr[$i]."'");
		while( $row = $babDB->db_fetch_array($res))
			{
			$babDB->db_query("delete from ".BAB_VAC_ENTRIES_ELEM_TBL." where id_entry='".$row['id']."'");
			}
		$babDB->db_query("delete from ".BAB_VAC_ENTRIES_TBL." where id_user='".$row['id']."'");

		$res = $babDB->db_query("select id_right from ".BAB_VAC_USERS_RIGHTS_TBL." where id_user='".$arr[$i]."'");
		while( $row = $babDB->db_fetch_array($res))
			{
			$babDB->db_query("delete from ".BAB_VAC_USERS_RIGHTS_TBL." where id_user='".$arr[$i]."' and id_right='".$row['id_right']."'");
			list($total) = $babDB->db_fetch_array($babDB->db_query("select count(id) from ".BAB_VAC_USERS_RIGHTS_TBL." where id_right='".$row['id_right']."'"));
			if( $total == 0 )
				$babDB->db_query("delete from ".BAB_ENTRIES_TBL." where id='".$row['id_right']."'");
			}
		$babDB->db_query("delete from ".BAB_VAC_PERSONNEL_TBL." where id_user='".$arr[$i]."'");
		}
	}

/* main */
$acclevel = bab_vacationsAccess();
if( !isset($acclevel['manager']) || $acclevel['manager'] != true)
	{
	$babBody->msgerror = bab_translate("Access denied");
	return;
	}

if( !isset($idx))
	$idx = "lvt";

if( isset($add) )
	{
	if( $add == "addvt" )
		{
		if(!saveVacationType($tname, $description, $quantity))
			$idx ='addvt';
		}
	else if( $add == "modvt")
		{
		if( isset($bdel))
			deleteVacationType($vtid);
		else if(!updateVacationType($vtid, $tname, $description, $quantity))
			$idx ='addvt';
		}
	else if( $add == "addvc")
		{
		if(!saveVacationCollection($tname, $description, $vtypeids))
			$idx ='addvc';
		}
	else if( $add == "modvc")
		{
		if( isset($bdel))
			deleteVacationCollection($vcid);
		else if(!updateVacationCollection($vcid, $tname, $description, $vtypeids))
			$idx ='addvc';
		}
	else if( $add == "addp")
		{
		if(!saveVacationPersonnel($userid, $groupid, $idcol, $idsa))
			$idx ='addp';
		}
	else if( $add == "modp")
		{
		if(!updateVacationPersonnel($userid, $groupid, $idcol, $idsa))
			{
			$idp = $userid;
			$idx ='modp';
			}
		}
	}
else if( isset($action) && $action == "Yes")
	{
	confirmDeletePersonnel($items);
	$idx = "lper";
	}


switch($idx)
	{
	case "lrbu":
		listRightsByUser($idu);
		exit;
		break;
	case "cal":
		if( !isset($month))
			$month = Date("n");

		if( !isset($year))
			$year = Date("Y");

		viewCalendarByUser($idu, $month, $year);
		exit;
		break;
	case "delu":
		$babBody->title = bab_translate("Delete users");
		deleteVacationPersonnel($pos, $idcol, $idsa, $userids);
		$babBody->addItemMenu("lvt", bab_translate("Types"), $GLOBALS['babUrlScript']."?tg=vacadm&idx=lvt");
		$babBody->addItemMenu("lcol", bab_translate("Collections"), $GLOBALS['babUrlScript']."?tg=vacadm&idx=lcol");
		$babBody->addItemMenu("lper", bab_translate("Personnel"), $GLOBALS['babUrlScript']."?tg=vacadm&idx=lper&pos=".$pos."&idcol=".$idcol."&idsa=".$idsa);
		$babBody->addItemMenu("delu", bab_translate("Delete"), $GLOBALS['babUrlScript']."?tg=vacadm&idx=delu");
		$babBody->addItemMenu("addd", bab_translate("Rights"), $GLOBALS['babUrlScript']."?tg=vacadma&idx=lrig&pos=".$pos."&idcol=".$idcol."&idsa=".$idsa);
		$babBody->addItemMenu("lreq", bab_translate("Requests"), $GLOBALS['babUrlScript']."?tg=vacadmb&idx=lreq");
		break;

	case "modp":
		$babBody->title = bab_translate("Modify user");
		if( !isset($idp)) $idp ="";
		if( !isset($idcol)) $idcol ="";
		if( !isset($idsa)) $idsa ="";
		addVacationPersonnel($idcol, $idsa, $idp);
		$babBody->addItemMenu("lvt", bab_translate("Types"), $GLOBALS['babUrlScript']."?tg=vacadm&idx=lvt");
		$babBody->addItemMenu("lcol", bab_translate("Collections"), $GLOBALS['babUrlScript']."?tg=vacadm&idx=lcol");
		$babBody->addItemMenu("lper", bab_translate("Personnel"), $GLOBALS['babUrlScript']."?tg=vacadm&idx=lper&pos=".$pos."&idcol=".$idcol."&idsa=".$idsa);
		$babBody->addItemMenu("modp", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=vacadm&idx=modp");
		$babBody->addItemMenu("addd", bab_translate("Rights"), $GLOBALS['babUrlScript']."?tg=vacadma&idx=lrig&pos=".$pos."&idcol=".$idcol."&idsa=".$idsa);
		$babBody->addItemMenu("lreq", bab_translate("Requests"), $GLOBALS['babUrlScript']."?tg=vacadmb&idx=lreq");
		break;

	case "addp":
		$babBody->title = bab_translate("Add users");
		if( !isset($idp)) $idp ="";
		addVacationPersonnel($idcol, $idsa, $idp);
		$babBody->addItemMenu("lvt", bab_translate("Types"), $GLOBALS['babUrlScript']."?tg=vacadm&idx=lvt");
		$babBody->addItemMenu("lcol", bab_translate("Collections"), $GLOBALS['babUrlScript']."?tg=vacadm&idx=lcol");
		$babBody->addItemMenu("lper", bab_translate("Personnel"), $GLOBALS['babUrlScript']."?tg=vacadm&idx=lper&pos=".$pos."&idcol=".$idcol."&idsa=".$idsa);
		$babBody->addItemMenu("addp", bab_translate("Add"), $GLOBALS['babUrlScript']."?tg=vacadm&idx=addp");
		$babBody->addItemMenu("addd", bab_translate("Rights"), $GLOBALS['babUrlScript']."?tg=vacadma&idx=lrig&pos=".$pos."&idcol=".$idcol."&idsa=".$idsa);
		$babBody->addItemMenu("lreq", bab_translate("Requests"), $GLOBALS['babUrlScript']."?tg=vacadmb&idx=lreq");
		break;

	case "lper":
		$babBody->title = bab_translate("Personnel");
		if( !isset($pos)) $pos ="";
		if( !isset($idcol)) $idcol ="";
		if( !isset($idsa)) $idsa ="";
		if( isset($chg))
		{
			if( $pos[0] == "-")
				$pos = $pos[1];
			else
				$pos = "-" .$pos;
		}
		listVacationPersonnel($pos, $idcol, $idsa);
		$babBody->addItemMenu("lvt", bab_translate("Types"), $GLOBALS['babUrlScript']."?tg=vacadm&idx=lvt");
		$babBody->addItemMenu("lcol", bab_translate("Collections"), $GLOBALS['babUrlScript']."?tg=vacadm&idx=lcol");
		$babBody->addItemMenu("lper", bab_translate("Personnel"), $GLOBALS['babUrlScript']."?tg=vacadm&idx=lper");
		$babBody->addItemMenu("addd", bab_translate("Rights"), $GLOBALS['babUrlScript']."?tg=vacadma&idx=lrig&pos=".$pos."&idcol=".$idcol."&idsa=".$idsa);
		$babBody->addItemMenu("lreq", bab_translate("Requests"), $GLOBALS['babUrlScript']."?tg=vacadmb&idx=lreq");
		break;

	case "lcol":
		$babBody->title = bab_translate("Vacations type's collections");
		listVacationCollections();
		$babBody->addItemMenu("lvt", bab_translate("Types"), $GLOBALS['babUrlScript']."?tg=vacadm&idx=lvt");
		$babBody->addItemMenu("lcol", bab_translate("Collections"), $GLOBALS['babUrlScript']."?tg=vacadm&idx=lcol");
		$babBody->addItemMenu("addvc", bab_translate("Add"), $GLOBALS['babUrlScript']."?tg=vacadm&idx=addvc");
		$babBody->addItemMenu("lper", bab_translate("Personnel"), $GLOBALS['babUrlScript']."?tg=vacadm&idx=lper");
		$babBody->addItemMenu("addd", bab_translate("Rights"), $GLOBALS['babUrlScript']."?tg=vacadma&idx=lrig&pos=".$pos."&idcol=".$idcol."&idsa=".$idsa);
		$babBody->addItemMenu("lreq", bab_translate("Requests"), $GLOBALS['babUrlScript']."?tg=vacadmb&idx=lreq");
		break;

	case "modvc":
		$babBody->title = bab_translate("Modify vacation type's collection");
		if( !isset($vcid)) $vcid =$id;
		if( !isset($what)) $what ="modvc";
		if( !isset($tname)) $tname ="";
		if( !isset($description)) $description ="";
		if( !isset($vtypeids)) $vtypeids =array();
		addVacationCollection($vcid, $what, $tname, $description, $vtypeids);
		$babBody->addItemMenu("lvt", bab_translate("Types"), $GLOBALS['babUrlScript']."?tg=vacadm&idx=lvt");
		$babBody->addItemMenu("lcol", bab_translate("Collections"), $GLOBALS['babUrlScript']."?tg=vacadm&idx=lcol");
		$babBody->addItemMenu("modvc", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=vacadm&idx=modvc");
		$babBody->addItemMenu("addvc", bab_translate("Add"), $GLOBALS['babUrlScript']."?tg=vacadm&idx=addvc");
		$babBody->addItemMenu("lper", bab_translate("Personnel"), $GLOBALS['babUrlScript']."?tg=vacadm&idx=lper");
		$babBody->addItemMenu("addd", bab_translate("Rights"), $GLOBALS['babUrlScript']."?tg=vacadma&idx=lrig&pos=".$pos."&idcol=".$idcol."&idsa=".$idsa);
		$babBody->addItemMenu("lreq", bab_translate("Requests"), $GLOBALS['babUrlScript']."?tg=vacadmb&idx=lreq");
		break;

	case "addvc":
		$babBody->title = bab_translate("Add vacation type's collection");
		if( !isset($vcid)) $vcid =$id;
		if( !isset($what)) $what ="addvc";
		if( !isset($tname)) $tname ="";
		if( !isset($description)) $description ="";
		if( !isset($vtypeids)) $vtypeids =array();
		addVacationCollection($vcid, $what, $tname, $description, $vtypeids);
		$babBody->addItemMenu("lvt", bab_translate("Types"), $GLOBALS['babUrlScript']."?tg=vacadm&idx=lvt");
		$babBody->addItemMenu("lcol", bab_translate("Collections"), $GLOBALS['babUrlScript']."?tg=vacadm&idx=lcol");
		$babBody->addItemMenu("addvc", bab_translate("Add"), $GLOBALS['babUrlScript']."?tg=vacadm&idx=addvc");
		$babBody->addItemMenu("lper", bab_translate("Personnel"), $GLOBALS['babUrlScript']."?tg=vacadm&idx=lper");
		$babBody->addItemMenu("addd", bab_translate("Rights"), $GLOBALS['babUrlScript']."?tg=vacadma&idx=lrig&pos=".$pos."&idcol=".$idcol."&idsa=".$idsa);
		$babBody->addItemMenu("lreq", bab_translate("Requests"), $GLOBALS['babUrlScript']."?tg=vacadmb&idx=lreq");
		break;

	case "modvt":
		$babBody->title = bab_translate("Modify vacation type");
		if( !isset($vtid)) $vtid =$id;
		if( !isset($what)) $what ="modvt";
		if( !isset($tname)) $tname ="";
		if( !isset($description)) $description ="";
		if( !isset($quantity)) $quantity ="";
		if( !isset($maxdays)) $maxdays ="";
		if( !isset($mindays)) $mindays ="";
		if( !isset($defdays)) $defdays ="";
		addVacationType($vtid, $what, $tname, $description, $quantity, $maxdays, $mindays, $defdays);
		$babBody->addItemMenu("lvt", bab_translate("Types"), $GLOBALS['babUrlScript']."?tg=vacadm&idx=lvt");
		$babBody->addItemMenu("modvt", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=vacadm&idx=modvt");
		$babBody->addItemMenu("addvt", bab_translate("Add"), $GLOBALS['babUrlScript']."?tg=vacadm&idx=addvt");
		$babBody->addItemMenu("lcol", bab_translate("Collections"), $GLOBALS['babUrlScript']."?tg=vacadm&idx=lcol");
		$babBody->addItemMenu("lper", bab_translate("Personnel"), $GLOBALS['babUrlScript']."?tg=vacadm&idx=lper");
		$babBody->addItemMenu("addd", bab_translate("Rights"), $GLOBALS['babUrlScript']."?tg=vacadma&idx=lrig&pos=".$pos."&idcol=".$idcol."&idsa=".$idsa);
		$babBody->addItemMenu("lreq", bab_translate("Requests"), $GLOBALS['babUrlScript']."?tg=vacadmb&idx=lreq");
		break;

	case "addvt":
		$babBody->title = bab_translate("Add vacation type");
		if( !isset($vtdid)) $vtdid ="";
		if( !isset($what)) $what ="addvt";
		if( !isset($tname)) $tname ="";
		if( !isset($description)) $description ="";
		if( !isset($quantity)) $quantity ="";
		$babBody->addItemMenu("lvt", bab_translate("Types"), $GLOBALS['babUrlScript']."?tg=vacadm&idx=lvt");
		$babBody->addItemMenu("addvt", bab_translate("Add"), $GLOBALS['babUrlScript']."?tg=vacadm&idx=addvt");
		if( addVacationType($vtid, $what, $tname, $description, $quantity) != 0 )
		{
			$babBody->addItemMenu("lcol", bab_translate("Collections"), $GLOBALS['babUrlScript']."?tg=vacadm&idx=lcol");
			$babBody->addItemMenu("lper", bab_translate("Personnel"), $GLOBALS['babUrlScript']."?tg=vacadm&idx=lper");
			$babBody->addItemMenu("addd", bab_translate("Rights"), $GLOBALS['babUrlScript']."?tg=vacadma&idx=lrig&pos=".$pos."&idcol=".$idcol."&idsa=".$idsa);
			$babBody->addItemMenu("lreq", bab_translate("Requests"), $GLOBALS['babUrlScript']."?tg=vacadmb&idx=lreq");
		}
		break;

	case "lvt":
	default:
		$babBody->title = bab_translate("Vacations types");
		$babBody->addItemMenu("lvt", bab_translate("Types"), $GLOBALS['babUrlScript']."?tg=vacadm&idx=lvt");
		$babBody->addItemMenu("addvt", bab_translate("Add"), $GLOBALS['babUrlScript']."?tg=vacadm&idx=addvt");
		if( listVacationTypes() != 0 )
		{
			$babBody->addItemMenu("lcol", bab_translate("Collections"), $GLOBALS['babUrlScript']."?tg=vacadm&idx=lcol");
			$babBody->addItemMenu("lper", bab_translate("Personnel"), $GLOBALS['babUrlScript']."?tg=vacadm&idx=lper");
			$babBody->addItemMenu("addd", bab_translate("Rights"), $GLOBALS['babUrlScript']."?tg=vacadma&idx=lrig&pos=".$pos."&idcol=".$idcol."&idsa=".$idsa);
			$babBody->addItemMenu("lreq", bab_translate("Requests"), $GLOBALS['babUrlScript']."?tg=vacadmb&idx=lreq");
		}
		break;
	}
$babBody->setCurrentItemMenu($idx);
?>
