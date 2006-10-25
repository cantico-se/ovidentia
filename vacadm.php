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

function addVacationType($vtid, $what, $tname, $description, $quantity, $tcolor, $cbalance)
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

		function temp($vtid, $what, $tname, $description, $quantity, $tcolor, $cbalance)
			{
			global $babDB;
			$this->what = $what;
			$this->vtid = $vtid;
			$this->name = bab_translate("Name");
			$this->description = bab_translate("Description");
			$this->quantity = bab_translate("Quantity");
			$this->colortxt = bab_translate("Color");
			$this->balancetxt = bab_translate("Accept negative balance");
			$this->yestxt = bab_translate("Yes");
			$this->notxt = bab_translate("No");
			$this->selctorurl = $GLOBALS['babUrlScript']."?tg=selectcolor&idx=popup&callback=setColor";
			$this->tcolor = $tcolor;

			$this->invalidentry1 = bab_translate("Invalid entry!  Only numbers are accepted and . !");

			if( $what == "modvt" && empty($tname))
				{
				$arr = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_VAC_TYPES_TBL." where id=".$babDB->quote($vtid)));
				$this->tnameval = $arr['name'];
				$this->descriptionval = $arr['description'];
				$this->quantityval = $arr['quantity'];
				$this->tcolorval = $arr['color'];
				if( $arr['cbalance'] == 'Y')
					{
					$this->yselected = 'selected';
					$this->nselected = '';
					}
				else
					{
					$this->yselected = '';
					$this->nselected = 'selected';
					}
				}
			else
				{
				$this->tnameval = $tname;
				$this->descriptionval = $description;
				$this->quantityval = $quantity;
				$this->tcolorval = $tcolor;
				if( $cbalance == 'N')
					{
					$this->nselected = 'selected';
					$this->yselected = '';
					}
				else
					{
					$this->nselected = '';
					$this->yselected = 'selected';
					}
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
	$temp = new temp($vtid, $what, $tname, $description, $quantity, $tcolor, $cbalance);
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
			$this->colortxt = bab_translate("Color");
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
				$this->tcolor = $arr['color'];
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

function addVacationCollection($vcid, $what, $tname, $description, $vtypeids, $category)
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
		function temp($vcid, $what, $tname, $description, $vtypeids, $category)
			{
			global $babDB;
			$this->name = bab_translate("Name");
			$this->description = bab_translate("Description");
			$this->vactypes = bab_translate("Vacations types");
			$this->category = bab_translate("Category to use in calendar");
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
				$arr = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_VAC_COLLECTIONS_TBL." where id=".$babDB->quote($vcid)));
				$this->tnameval = $arr['name'];
				$this->descriptionval = $arr['description'];
				$this->categoryval = $arr['id_cat'];
				$res = $babDB->db_query("select * from ".BAB_VAC_COLL_TYPES_TBL." where id_coll=".$babDB->quote($vcid));
				while( $arr = $babDB->db_fetch_array($res))
					$this->vtids[] = $arr['id_type'];
				}
			else
				{
				$this->vtids = $vtypeids;
				$this->tnameval = $tname;
				$this->descriptionval = $description;
				$this->categoryval = $category;
				}

			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_VAC_TYPES_TBL." order by name asc";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);

			include_once $GLOBALS['babInstallPath']."utilit/calapi.php";
			$this->categs = bab_calGetCategories();

			$this->catcount = count($this->categs);
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

		function getnextcat()
			{
			static $i = 0;
			if( $i < $this->catcount)
				{
				$this->categid = $this->categs[$i]['id'];
				$this->categname = $this->categs[$i]['name'];
				if( $this->categid == $this->categoryval )
					{
					$this->selected = 'selected';
					}
				else
					{
					$this->selected = '';
					}
				$i++;
				return true;
				}
			else
				return false;

			}
		}

	$temp = new temp($vcid, $what, $tname, $description, $vtypeids, $category);
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

		var $altbg = true;

		function temp($pos, $idcol, $idsa)
			{
			$this->allname = bab_translate("All");
			$this->uncheckall = bab_translate("Uncheck all");
			$this->checkall = bab_translate("Check all");
			$this->filteron = bab_translate("Filter on");
			$this->collection = bab_translate("Collection");
			$this->appschema = bab_translate("Approbation schema");
			$this->addpersonnel = bab_translate("Add");
			$this->g_addpersonnel = bab_translate("Add/Modify by group");
			$this->deletealt = bab_translate("Delete");
			$this->altlrbu = bab_translate("Rights");
			$this->altcal = bab_translate("Calendar");
			$this->altvunew = bab_translate("Request");
			$this->t_view_calendar = bab_translate("View calendars");
			$this->addpurl = $GLOBALS['babUrlScript']."?tg=vacadm&idx=addp&pos=".$pos."&idcol=".$idcol."&idsa=".$idsa;
			$this->addgurl = $GLOBALS['babUrlScript']."?tg=vacadm&idx=addg&pos=".$pos."&idcol=".$idcol."&idsa=".$idsa;

			$this->t_lastname = bab_translate("Lastname");
			$this->t_firstname = bab_translate("Firstname");

			$this->db = & $GLOBALS['babDB'];

			$this->idcol = $idcol;
			$this->idsa = $idsa;

			$this->pos = $pos;

			$req = "SELECT  
					u.id, 
					u.lastname, 
					u.firstname, 
					p.id_sa, 
					p.id_coll 
				FROM 
					".BAB_USERS_TBL." u 
					join ".BAB_VAC_PERSONNEL_TBL." p 
				WHERE 
					u.id=p.id_user and 
					u.lastname like '".$this->db->db_escape_string($this->pos)."%' 
				";


			if( !empty($idcol))
				$req .= " and p.id_coll='".$this->db->db_escape_string($idcol)."' ";

			if( !empty($idsa))
				$req .= " and p.id_sa='".$this->db->db_escape_string($idsa)."' ";


			$req .= "order by u.lastname, u.firstname asc";
			
			$this->fullnameurl = $GLOBALS['babUrlScript']."?tg=vacadm&idx=lper&chg=&pos=".$this->pos."&idcol=".$this->idcol."&idsa=".$this->idsa;
			

			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);

			if( empty($this->pos))
				$this->allselected = 1;
			else
				$this->allselected = 0;
			$this->allurl = $GLOBALS['babUrlScript']."?tg=vacadm&idx=lper&pos=&idcol=".$this->idcol."&idsa=".$this->idsa;

			$this->sares = $this->db->db_query("select * from ".BAB_FLOW_APPROVERS_TBL." order by name asc");
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
				$this->altbg = !$this->altbg;
				$this->arr = $this->db->db_fetch_array($this->res);
				$this->url = $GLOBALS['babUrlScript']."?tg=vacadm&idx=modp&idp=".$this->arr['id']."&pos=".$this->pos;
				
				$this->firstname = bab_toHtml($this->arr['firstname']);
				$this->lastname = bab_toHtml($this->arr['lastname']);
				
					
				$this->userid = $this->arr['id'];
				$this->lrbuurl = $GLOBALS['babUrlScript']."?tg=vacadm&idx=lrbu&idu=".$this->userid;
				$this->calurl = $GLOBALS['babUrlScript']."?tg=vacuser&idx=cal&idu=".$this->userid;
				$this->vunewurl = $GLOBALS['babUrlScript']."?tg=vacuser&idx=period&rfrom=1&id_user=".$this->userid;
				$arr = $this->db->db_fetch_array($this->db->db_query("select name from ".BAB_VAC_COLLECTIONS_TBL." where id='".$this->db->db_escape_string($this->arr['id_coll'])."'"));
				$this->collname = $arr['name'];
				$arr = $this->db->db_fetch_array($this->db->db_query("select name from ".BAB_FLOW_APPROVERS_TBL." where id='".$this->db->db_escape_string($this->arr['id_sa'])."'"));
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
				$this->selecturl = $GLOBALS['babUrlScript']."?tg=vacadm&idx=lper&pos=".$this->selectname."&idcol=".$this->idcol."&idsa=".$this->idsa;

				if( $this->pos == $this->selectname)
					$this->selected = 1;
				else 
					{

					$req = "select u.id from ".BAB_USERS_TBL." u join ".BAB_VAC_PERSONNEL_TBL." p where u.id=p.id_user and u.lastname like '".$this->selectname."%'";

					if( !empty($this->idcol))
						$req .= " and p.id_coll='".$this->db->db_escape_string($this->idcol)."'";
					if( !empty($this->idsa))
						$req .= " and p.id_sa='".$this->db->db_escape_string($this->idsa)."'";

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




function addGroupVacationPersonnel()
	{
	global $babBody;
	class temp
		{

		function temp()
			{
			$this->grouptext = bab_translate("Group");
			$this->collection = bab_translate("Collection");
			$this->appschema = bab_translate("Approbation schema");
			$this->groupsbrowurl = $GLOBALS['babUrlScript']."?tg=vacadm&idx=browg&cb=";
			$this->t_add = bab_translate("Add");
			$this->t_modify = bab_translate("Modify");
			$this->t_record = bab_translate("Record");
			$this->t_add_modify = bab_translate("Add or modify users by group");
			$this->t_modify_alert = bab_translate("Users with waiting requests will not be modified");

			$this->db = & $GLOBALS['babDB'];

			$this->idsa = $_REQUEST['idsa'];
			$this->idcol = isset($_REQUEST['idcol']) ? $_REQUEST['idcol'] : '';


			$this->groupval = "";
			$this->groupid = "";

			$this->sares = $this->db->db_query("select * from ".BAB_FLOW_APPROVERS_TBL." order by name asc");
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
				{
				$j = 0;
				if ($this->countsa > 0)
					$this->db->db_data_seek($this->sares,0);
				return false;
				}
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

		function printhtml()
			{
			$GLOBALS['babBody']->babecho(	bab_printTemplate($this,"vacadm.html", "grouppersonnelcreate"));
			}
		}

	$temp = new temp();
	$temp->printhtml();
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
				$req = "select * from ".BAB_USERS_TBL." where id='".$this->db->db_escape_string($userids[$i])."'";
				$res = $db->db_query($req);
				if( $db->db_num_rows($res) > 0)
					{
					$arr = $db->db_fetch_array($res);
					$this->title .= "<br>". bab_composeUserName($arr['firstname'], $arr['lastname']);
					$items .= $arr['id'];
					}
				if( $i < count($userids) -1)
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


function admmenu()
	{
	global $babBody;

	class tempa
		{

		function tempa()
			{
			$this->menu = array(
							$GLOBALS['babUrlScript']."?tg=vacadm&idx=lvt" => bab_translate("Types"), 
							$GLOBALS['babUrlScript']."?tg=vacadm&idx=lcol" => bab_translate("Collections"),
							$GLOBALS['babUrlScript']."?tg=vacadm&idx=lper" => bab_translate("Personnel"), 
							$GLOBALS['babUrlScript']."?tg=vacadma&idx=lrig" => bab_translate("Rights"),
							$GLOBALS['babUrlScript']."?tg=vacadma&idx=rgroup" => bab_translate("Rights groups"),
							$GLOBALS['babUrlScript']."?tg=vacadmb&idx=lreq" => bab_translate("Requests"),
							$GLOBALS['babUrlScript']."?tg=vacadma&idx=copy" => bab_translate("Rights renewal by years")
							);
			}

		function getnext()
			{
			if (list($url, $text) = each($this->menu)) {
				$this->url	= bab_toHtml($url); 
				$this->text	= bab_toHtml($text);
				return true;
			}
			return false;
			}
		}

	$tempa = new tempa();
	$babBody->babecho(	bab_printTemplate($tempa,"vacadm.html", "menu"));
	}


function saveVacationType($tname, $description, $quantity, $tcolor, $cbalance, $maxdays=0, $mindays=0, $default=0)
	{
	global $babBody;
	if( empty($tname)) {
		$babBody->msgerror = bab_translate("ERROR: You must provide a name")." !";
		return false;
		}

	$db = $GLOBALS['babDB'];

	$req = "select id from ".BAB_VAC_TYPES_TBL." where name='".$db->db_escape_string($tname)."'";
	$res = $db->db_query($req);
	if( $res && $db->db_num_rows($res) > 0 )
		{
		$babBody->msgerror = bab_translate("This vacation type already exists") ." !";
		return false;
		}
	
	$req = "insert into ".BAB_VAC_TYPES_TBL." ( name, description, quantity, maxdays, mindays, defaultdays, color, cbalance)";
	$req .= " values (
	'".$db->db_escape_string($tname)."', 
	'" .$db->db_escape_string($description). "', 
		'" .$db->db_escape_string($quantity). "',
		'" .$db->db_escape_string($maxdays). "',
		'" .$db->db_escape_string($mindays). "',
		'" .$db->db_escape_string($default). "',
		'" .$db->db_escape_string($tcolor). "',
		'" .$db->db_escape_string($cbalance). "'
	)";
	$res = $db->db_query($req);
	return true;
	}

function updateVacationType($vtid, $tname, $description, $quantity, $tcolor, $cbalance, $maxdays=0, $mindays=0, $default=0)
	{
	global $babBody;
	if( empty($tname))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a name")." !";
		return false;
		}

	$db = $GLOBALS['babDB'];

	$req = "SELECT id from ".BAB_VAC_TYPES_TBL." WHERE name='".$db->db_escape_string($tname)."' AND id!='".$db->db_escape_string($vtid)."'";
	$res = $db->db_query($req);
	if( $res && $db->db_num_rows($res) > 0 )
		{
		$babBody->msgerror = bab_translate("This vacation type already exists") ." !";
		return false;
		}
	
	$req = "UPDATE ".BAB_VAC_TYPES_TBL." 
		SET 
			name='".$db->db_escape_string($tname)."', 
			description='".$db->db_escape_string($description)."', 
			quantity='".$db->db_escape_string($quantity)."', 
			maxdays='".$db->db_escape_string($maxdays)."', 
			mindays='".$db->db_escape_string($mindays)."', 
			defaultdays='".$db->db_escape_string($default)."', 
			color='".$db->db_escape_string($tcolor)."', 
			cbalance='".$db->db_escape_string($cbalance)."' 
		WHERE  
			id='".$db->db_escape_string($vtid)."'";
	$res = $db->db_query($req);
	return true;
	}

function deleteVacationType($vtid)
	{
	global $babBody, $babDB;
	$bdel = true;

	list($total) = $babDB->db_fetch_array($babDB->db_query("select count(id) as total from ".BAB_VAC_COLL_TYPES_TBL." where id_type='".$this->db->db_escape_string($vtid)."'"));
	if( $total > 0 )
		{
		$bdel = false;	
		}
	else 
		{
		list($total) = $babDB->db_fetch_array($babDB->db_query("select count(id) as total from ".BAB_VAC_ENTRIES_ELEM_TBL." where id_type='".$this->db->db_escape_string($vtid)."'"));
		if( $total > 0 )
			$bdel = false;
		else
			{
			list($total) = $babDB->db_fetch_array($babDB->db_query("select count(id) as total from ".BAB_VAC_RIGHTS_TBL." where id_type='".$this->db->db_escape_string($vtid)."'"));
			if( $total > 0 )
				$bdel = false;
			}
		}

	if( $bdel )
		{
		$babDB->db_query("delete from ".BAB_VAC_TYPES_TBL." where id='".$this->db->db_escape_string($vtid)."'");
		$babDB->db_query("delete from ".BAB_VAC_COLL_TYPES_TBL." where id_type='".$this->db->db_escape_string($vtid)."'");
		}
	else
		$babBody->msgerror = bab_translate("This vacation type is used and can't be deleted") ." !";
	}

function saveVacationCollection($tname, $description, $vtypeids, $category)
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

	$db = $GLOBALS['babDB'];

	$req = "select id from ".BAB_VAC_COLLECTIONS_TBL." where name='".$db->db_escape_string($tname)."'";
	$res = $db->db_query($req);
	if( $res && $db->db_num_rows($res) > 0 )
		{
		$babBody->msgerror = bab_translate("This collection already exists") ." !";
		return false;
		}
	
	$req = "insert into ".BAB_VAC_COLLECTIONS_TBL." ( name, description, id_cat )";
	$req .= " values ('".$db->db_escape_string($tname)."', '" .$db->db_escape_string($description)."', '" .$db->db_escape_string($category). "')";
	$res = $db->db_query($req);
	$id = $db->db_insert_id();
	for( $i=0; $i < count($vtypeids); $i++)
		{
		$db->db_query("insert into ".BAB_VAC_COLL_TYPES_TBL." (id_coll, id_type) values ('".$db->db_escape_string($id)."', '".$db->db_escape_string($vtypeids[$i])."')");
		}
	return true;
	}

function updateVacationCollection($vcid, $tname, $description, $vtypeids, $category)
	{
	global $babBody;
	if( empty($tname))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a name")." !";
		return false;
		}

	$db = $GLOBALS['babDB'];

	$req = "select id from ".BAB_VAC_COLLECTIONS_TBL." where name='".$db->db_escape_string($tname)."' and id!='".$db->db_escape_string($vcid)."'";
	$res = $db->db_query($req);
	if( $res && $db->db_num_rows($res) > 0 )
		{
		$babBody->msgerror = bab_translate("This collection already exists") ." !";
		return false;
		}
	
	list($oldcateg) = $db->db_fetch_row($db->db_query("select id_cat from ".BAB_VAC_COLLECTIONS_TBL." where id='".$db->db_escape_string($vcid)."'"));

	$res = $db->db_query("update ".BAB_VAC_COLLECTIONS_TBL." set name='".$db->db_escape_string($tname)."', description='".$db->db_escape_string($description)."', id_cat='".$db->db_escape_string($category)."' where id='".$db->db_escape_string($vcid)."'");

	if( count($vtypeids) > 0 )
		{
		$vtexist = array();
		$res = $db->db_query("select * from ".BAB_VAC_COLL_TYPES_TBL." where id_coll='".$this->db->db_escape_string($vcid)."'");
		while( $arr = $db->db_fetch_array($res))
			{
			if( !in_array($arr['id_type'], $vtypeids ))
				{
				$db->db_query("delete from ".BAB_VAC_COLL_TYPES_TBL." where id='".$this->db->db_escape_string($arr['id'])."'");
				}
			else
				$vtexist[] = $arr['id_type'];
			}

		$nbexist = count($vtexist);
		for( $i=0; $i < count($vtypeids); $i++)
			{
			if( $nbexist == 0 || ($nbexist > 0 && !in_array($vtypeids[$i], $vtexist)))
				$db->db_query("insert into ".BAB_VAC_COLL_TYPES_TBL." (id_coll, id_type) values ('".$this->db->db_escape_string($vcid)."', '".$this->db->db_escape_string($vtypeids[$i])."')");
			}
	
		}
	else
		{
		$db->db_query("delete from ".BAB_VAC_COLL_TYPES_TBL." where id_coll='".$this->db->db_escape_string($vcid)."'");
		}

	if( $oldcateg != $category)
		{
		$res = $db->db_query("select vet.id from ".BAB_VAC_ENTRIES_TBL." vet left join ".BAB_VAC_PERSONNEL_TBL." vpt on vpt.id_user=vet.id_user where vpt.id_coll='".$this->db->db_escape_string($vcid)."'");
		while( $arr = $db->db_fetch_array($res))
			{
			$db->db_query("update ".BAB_CAL_EVENTS_TBL." set id_cat='".$this->db->db_escape_string($category)."' where hash='V_".$this->db->db_escape_string($arr['id'])."'");
			}
		}

	return true;
	}


function updateVacationPersonnelGroup($groupid, $addmodify,  $idcol, $idsa)
{
	global $babBody, $babDB;

	if( empty($groupid) )
		{
		$babBody->msgerror = bab_translate("You must specify a group") ." !";
		return false;
		}

	if( !in_array($addmodify,array('add','modify')) )
		{
		$babBody->msgerror = bab_translate("error") ." !";
		return false;
		}

	if( empty($idcol) && $addmodify == 'add' )
		{
		$babBody->msgerror = bab_translate("You must specify a vacation collection") ." !";
		return false;
		}

	if( empty($idsa) )
		{
		$babBody->msgerror = bab_translate("You must specify approbation schema") ." !";
		return false;
		}

	if( !empty($groupid) )
		{
		if( $groupid == 1 )
			$res = $babDB->db_query("select id as id_user from ".BAB_USERS_TBL." where is_confirmed='1'");
		else
			$res = $babDB->db_query("select id_object as id_user from ".BAB_USERS_GROUPS_TBL." where id_group='".$this->db->db_escape_string($groupid)."'");

		while( $arr = $babDB->db_fetch_array($res))
			{
			$res2 = $babDB->db_query("select p.id, p.id_sa, e.id we from ".BAB_VAC_PERSONNEL_TBL." p LEFT JOIN ".BAB_VAC_ENTRIES_TBL." e ON e.id_user=p.id_user AND status='' WHERE p.id_user='".$this->db->db_escape_string($arr['id_user'])."' GROUP BY p.id");
			if( $res2 && $babDB->db_num_rows($res2) > 0 )
				{
				$row = $babDB->db_fetch_array($res2);
				if ($addmodify == 'modify' && $row['id_sa'] != $idsa) //  && empty($row['we'])
					{
					if (!empty($row['we']))
						updateVacationUser($arr['id_user'], $idsa);
					$babDB->db_query("update ".BAB_VAC_PERSONNEL_TBL." set id_sa='".$this->db->db_escape_string($idsa)."' where id_user='".$this->db->db_escape_string($arr['id_user'])."'");
					}
				}
			else
				{
				saveVacationPersonnel($arr['id_user'], $idcol, $idsa);
				}
			}
		}
	
	return true;
}


function deleteVacationCollection($vcid)
	{
	global $babDB;
	$bdel = true;

	list($total) = $babDB->db_fetch_array($babDB->db_query("select count(id) as total from ".BAB_VAC_PERSONNEL_TBL." where id_coll='".$this->db->db_escape_string($vcid)."'"));
	if( $total > 0 )
		{
		$bdel = false;	
		}

	if( $bdel )
		{
		$babDB->db_query("delete from ".BAB_VAC_COLLECTIONS_TBL." where id='".$this->db->db_escape_string($vcid)."'");
		$babDB->db_query("delete from ".BAB_VAC_COLL_TYPES_TBL." where id_coll='".$this->db->db_escape_string($vcid)."'");
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

		bab_vac_clearUserCalendar($arr[$i]);

		$res = $babDB->db_query("select id from ".BAB_VAC_ENTRIES_TBL." where id_user='".$this->db->db_escape_string($arr[$i])."'");
		while( $row = $babDB->db_fetch_array($res))
			{
			$babDB->db_query("delete from ".BAB_VAC_ENTRIES_ELEM_TBL." where id_entry='".$this->db->db_escape_string($row['id'])."'");
			}
		$babDB->db_query("delete from ".BAB_VAC_ENTRIES_TBL." where id_user='".$this->db->db_escape_string($arr[$i])."'");

		$res = $babDB->db_query("select id_right from ".BAB_VAC_USERS_RIGHTS_TBL." where id_user='".$this->db->db_escape_string($arr[$i])."'");
		while( $row = $babDB->db_fetch_array($res))
			{
			$babDB->db_query("delete from ".BAB_VAC_USERS_RIGHTS_TBL." where id_user='".$this->db->db_escape_string($arr[$i])."' and id_right='".$this->db->db_escape_string($row['id_right'])."'");
			list($total) = $babDB->db_fetch_array($babDB->db_query("select count(id) from ".BAB_VAC_USERS_RIGHTS_TBL." where id_right='".$this->db->db_escape_string($row['id_right'])."'"));
			if( $total == 0 )
				$babDB->db_query("delete from ".BAB_VAC_ENTRIES_TBL." where id='".$this->db->db_escape_string($row['id_right'])."'");
			}
		$babDB->db_query("delete from ".BAB_VAC_PERSONNEL_TBL." where id_user='".$this->db->db_escape_string($arr[$i])."'");
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
	$idx = "menu";

if( isset($_POST['add']) )
	{
	switch($_POST['add'])
		{
		case 'addvt':
		
			if(!saveVacationType($tname, $description, $quantity, $tcolor, $cbalance))
				$idx ='addvt';

			break;

		case 'modvt':
			if( isset($bdel))
				deleteVacationType($vtid);
			else if(!updateVacationType($vtid, $tname, $description, $quantity, $tcolor, $cbalance))
				$idx ='addvt';

			break;

		case 'addvc':
			if( !isset($vtypeids)) { $vtypeids = array();}
			if(!saveVacationCollection($tname, $description, $vtypeids, $category))
				$idx ='addvc';
			
			break;

		case 'modvc':

			if ( !isset($vtypeids))
				$vtypeids = array();
		
			if( isset($bdel))
				deleteVacationCollection($vcid);
			else if(!updateVacationCollection($vcid, $tname, $description, $vtypeids, $category))
				$idx ='addvc';

			break;


		case 'changeuser':
			$idsa = isset($_POST['idsa']) ? $_POST['idsa'] : 0;
			if (!empty($_POST['idp']))
				{
				if(updateVacationPersonnel($_POST['idp'], $idsa))
					{
					$idx ='changeucol';
					}
				else
					{
					$idx ='modp';
					}
				}
			else
				{
				if(!saveVacationPersonnel($_POST['userid'], $_POST['idcol'], $idsa))
					{
					$idx ='addp';
					}
				}
			break;

		case 'changegroup':
			$idcol = isset($_POST['idcol']) ? $_POST['idcol'] : '';
			$idsa = isset($_POST['idsa']) ? $_POST['idsa'] : 0;
			if (!updateVacationPersonnelGroup($_POST['groupid'], $_POST['addmodify'],  $idcol, $idsa))
				{
				$idx ='addg';
				}
			break;

		case 'changeucol':
			if (!updateUserColl())
				$idx = $add;
			break;

		case 'modrbu':
			updateVacationRightByUser($iduser, $quantities, $idrights);
			break;
		}
	}
else if( isset($action) && $action == "Yes")
	{
	confirmDeletePersonnel($items);
	$idx = "lper";
	}

if( !isset($pos)) $pos ="";
if( !isset($idcol)) $idcol ="";
if( !isset($idsa)) $idsa ="";

$babBody->addItemMenu("vacuser", bab_translate("Vacations"), $GLOBALS['babUrlScript']."?tg=vacuser");
$babBody->addItemMenu("menu", bab_translate("Management"), $GLOBALS['babUrlScript']."?tg=vacadm&idx=menu");

switch($idx)
	{
	case "browu":
		include_once $babInstallPath."utilit/lusersincl.php";
		if( !isset($pos)) { $pos ='';}
		browseUsers($pos, $cb);
		exit;
		break;
	case "browg":
		include_once $babInstallPath."utilit/grpincl.php";
		browseGroups($cb);
		exit;
		break;
	case "rlbuul":
		rlistbyuserUnload(bab_translate("Your request has been updated"));
		exit;

	case "lrbu":
		listRightsByUser($idu);
		exit;
		break;
	case "delu":
		$babBody->title = bab_translate("Delete users");
		deleteVacationPersonnel(
			bab_rp('pos'), 
			bab_rp('idcol'), 
			bab_rp('idsa'), 
			bab_rp('userids',array())
		);
		$babBody->addItemMenu("lper", bab_translate("Personnel"), $GLOBALS['babUrlScript']."?tg=vacadm&idx=lper&pos=".$pos."&idcol=".$idcol."&idsa=".$idsa);
		$babBody->addItemMenu("delu", bab_translate("Delete"), $GLOBALS['babUrlScript']."?tg=vacadm&idx=delu");
		break;

	case "modp":
		$babBody->title = bab_translate("Modify user");

		addVacationPersonnel($_REQUEST['idp']);
		$babBody->addItemMenu("lper", bab_translate("Personnel"), $GLOBALS['babUrlScript']."?tg=vacadm&idx=lper&pos=".$pos);
		$babBody->addItemMenu("modp", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=vacadm&idx=modp");
		break;

	case "addp":
		$babBody->title = bab_translate("Add users");
		addVacationPersonnel();
		$babBody->addItemMenu("lper", bab_translate("Personnel"), $GLOBALS['babUrlScript']."?tg=vacadm&idx=lper&pos=".$pos."&idcol=".$idcol."&idsa=".$idsa);
		$babBody->addItemMenu("addp", bab_translate("Add"), $GLOBALS['babUrlScript']."?tg=vacadm&idx=addp");
		break;

	case 'changeucol':
		$babBody->title = bab_translate("Change user collection");
		$babBody->addItemMenu("lper", bab_translate("Personnel"), $GLOBALS['babUrlScript']."?tg=vacadm&idx=lper");
		$babBody->addItemMenu("changeucol", bab_translate("User collection"), $GLOBALS['babUrlScript']."?tg=vacadm&idx=changeucol");
		changeucol($_POST['idp'],$_POST['idcol']);
		break;

	case "addg":
		$babBody->title = bab_translate("Add/Modify users by group");
		addGroupVacationPersonnel();
		$babBody->addItemMenu("lper", bab_translate("Personnel"), $GLOBALS['babUrlScript']."?tg=vacadm&idx=lper&pos=".$pos."&idcol=".$idcol."&idsa=".$idsa);
		$babBody->addItemMenu("addg", bab_translate("Add/Modify"), $GLOBALS['babUrlScript']."?tg=vacadm&idx=addg");
		break;

	case "lper":
		$babBody->title = bab_translate("Personnel");
		
		if( isset($chg))
		{
			if( strlen($pos) > 0 && $pos[0] == "-" )
				$pos = strlen($pos)>1? $pos[1]: '';
			else
				$pos = "-" .$pos;
		}
		listVacationPersonnel($pos, $idcol, $idsa);
		$babBody->addItemMenu("lper", bab_translate("Personnel"), $GLOBALS['babUrlScript']."?tg=vacadm&idx=lper");
		
		break;

	case "lcol":
		
		$babBody->title = bab_translate("Vacations type's collections");
		listVacationCollections();
		$babBody->addItemMenu("lcol", bab_translate("Collections"), $GLOBALS['babUrlScript']."?tg=vacadm&idx=lcol");
		$babBody->addItemMenu("addvc", bab_translate("Add"), $GLOBALS['babUrlScript']."?tg=vacadm&idx=addvc");
		break;

	case "modvc":
		$babBody->title = bab_translate("Modify vacation type's collection");
		if( !isset($pos)) $pos ="";
		if( !isset($idcol)) $idcol ="";
		if( !isset($idsa)) $idsa ="";
		if( !isset($vcid)) $vcid =$id;
		if( !isset($what)) $what ="modvc";
		if( !isset($tname)) $tname ="";
		if( !isset($description)) $description ="";
		if( !isset($category)) $category =0;
		if( !isset($vtypeids)) $vtypeids =array();
		addVacationCollection($vcid, $what, $tname, $description, $vtypeids, $category);
		$babBody->addItemMenu("lcol", bab_translate("Collections"), $GLOBALS['babUrlScript']."?tg=vacadm&idx=lcol");
		$babBody->addItemMenu("modvc", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=vacadm&idx=modvc");
		$babBody->addItemMenu("addvc", bab_translate("Add"), $GLOBALS['babUrlScript']."?tg=vacadm&idx=addvc");

		break;

	case "addvc":
		$babBody->title = bab_translate("Add vacation type's collection");
		if( !isset($pos)) $pos ="";
		if( !isset($idcol)) $idcol ="";
		if( !isset($idsa)) $idsa ="";
		if( !isset($vcid)) $vcid =isset($id)?$id:"";
		if( !isset($what)) $what ="addvc";
		if( !isset($tname)) $tname ="";
		if( !isset($description)) $description ="";
		if( !isset($category)) $category =0;
		if( !isset($vtypeids)) $vtypeids =array();
		addVacationCollection($vcid, $what, $tname, $description, $vtypeids, $category);
		$babBody->addItemMenu("lcol", bab_translate("Collections"), $GLOBALS['babUrlScript']."?tg=vacadm&idx=lcol");
		$babBody->addItemMenu("addvc", bab_translate("Add"), $GLOBALS['babUrlScript']."?tg=vacadm&idx=addvc");

		break;

	case "modvt":
		$babBody->title = bab_translate("Modify vacation type");
		if( !isset($pos)) $pos ="";
		if( !isset($idcol)) $idcol ="";
		if( !isset($idsa)) $idsa ="";
		if( !isset($vtid)) $vtid =$id;
		if( !isset($what)) $what ="modvt";
		if( !isset($tname)) $tname ="";
		if( !isset($description)) $description ="";
		if( !isset($quantity)) $quantity ="";
		if( !isset($tcolor)) $tcolor = "";
		if( !isset($cbalance)) $cbalance = "";
		addVacationType($vtid, $what, $tname, $description, $quantity, $tcolor, $cbalance);
		$babBody->addItemMenu("lvt", bab_translate("Types"), $GLOBALS['babUrlScript']."?tg=vacadm&idx=lvt");
		$babBody->addItemMenu("modvt", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=vacadm&idx=modvt");
		$babBody->addItemMenu("addvt", bab_translate("Add"), $GLOBALS['babUrlScript']."?tg=vacadm&idx=addvt");
		break;

	case "addvt":
		$babBody->title = bab_translate("Add vacation type");
		if( !isset($pos)) $pos ="";
		if( !isset($idcol)) $idcol ="";
		if( !isset($idsa)) $idsa ="";
		if( !isset($vtid)) $vtid ="";
		if( !isset($what)) $what ="addvt";
		if( !isset($tname)) $tname ="";
		if( !isset($description)) $description ="";
		if( !isset($quantity)) $quantity ="";
		if( !isset($tcolor)) $tcolor = "";
		if( !isset($cbalance)) $cbalance = "";
		$babBody->addItemMenu("lvt", bab_translate("Types"), $GLOBALS['babUrlScript']."?tg=vacadm&idx=lvt");
		$babBody->addItemMenu("addvt", bab_translate("Add"), $GLOBALS['babUrlScript']."?tg=vacadm&idx=addvt");
		addVacationType($vtid, $what, $tname, $description, $quantity, $tcolor, $cbalance);
		
		break;

	case "lvt":
	
		if( !isset($pos)) $pos ="";
		if( !isset($idcol)) $idcol ="";
		if( !isset($idsa)) $idsa ="";
		$babBody->title = bab_translate("Vacations types");
		$babBody->addItemMenu("lvt", bab_translate("Types"), $GLOBALS['babUrlScript']."?tg=vacadm&idx=lvt");
		$babBody->addItemMenu("addvt", bab_translate("Add"), $GLOBALS['babUrlScript']."?tg=vacadm&idx=addvt");
		listVacationTypes();
		
		break;


	case "menu":
	default:
		$babBody->title = bab_translate("Vacations management");
		admmenu();
		break;
	}
$babBody->setCurrentItemMenu($idx);
?>
