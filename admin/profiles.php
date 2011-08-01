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
include_once 'base.php';
require_once dirname(__FILE__).'/../utilit/registerglobals.php';
include_once $babInstallPath.'utilit/grptreeincl.php';

function profileCreate($pname, $pdesc, $grpids, $cinscription, $cmultiple, $crequired)
	{
	global $babBody;
	class temp
		{
		var $nametxt;
		var $descriptiontxt;
		var $name;
		var $description;

		function temp($pname, $pdesc, $grpids, $cinscription, $cmultiple, $crequired)
			{
			global $babBody, $babDB;
			$this->nametxt = bab_translate("Name");
			$this->descriptiontxt = bab_translate("Description");
			$this->grpnametxt = bab_translate("Groups");
			$this->grpdesctxt = bab_translate("Description");
			$this->add = bab_translate("Add");
			$this->delete = bab_translate("Delete");
			$this->uncheckall = bab_translate("Uncheck all");
			$this->checkall = bab_translate("Check all");
			$this->inscriptiontxt = bab_translate("Add in inscription form");
			$this->multiplicitytxt = bab_translate("Let user choose multiple groups");
			$this->requiredtxt = bab_translate("Required");
			$this->no = bab_translate("No");
			$this->yes = bab_translate("Yes");
			$tree = new bab_grptree();
			$this->groups = $tree->getGroups(BAB_REGISTERED_GROUP);
			$this->altbg = true;

			$this->bdel = false;
			$this->profid = '';
			$this->what = 'addp';
			$this->pname = $pname;
			$this->pdesc = $pdesc;
			if( $cinscription == 'Y')
				{
				$this->cinscheck = 'checked';
				}
			else
				{
				$this->cinscheck = '';
				}
			if( $cmultiple == 'Y')
				{
				$this->cmulcheck = 'checked';
				}
			else
				{
				$this->cmulcheck = '';
				}
			if( $crequired == 'Y')
				{
				$this->creqcheck = 'checked';
				}
			else
				{
				$this->creqcheck = '';
				}
			$this->grpids = $grpids;
			}

		function getnext()
			{
			global $babDB;

			if( list(,$arr) = each($this->groups) )
				{
				$this->altbg = !$this->altbg;
				$this->grpid = $arr['id'];
				$this->grpname = $arr['name'];
				$this->grpdesc = $arr['description'];
				if( count($this->grpids) > 0  && in_array( $arr['id'],$this->grpids))
					{
					$this->grpcheck = 'checked';
					}
				else
					{
					$this->grpcheck = '';
					}
				return true;
				}
			return false;
			}
		}

	$temp = new temp($pname, $pdesc, $grpids, $cinscription, $cmultiple, $crequired);
	$babBody->babecho(	bab_printTemplate($temp,"profiles.html", "profilecreate"));
	}


function profileModify($idprof,$pname, $pdesc, $grpids, $cinscription, $cmultiple, $crequired)
	{
	global $babBody;
	class temp
		{
		var $nametxt;
		var $descriptiontxt;
		var $name;
		var $description;

		function temp($idprof,$pname, $pdesc, $grpids, $cinscription, $cmultiple, $crequired)
			{
			global $babBody, $babDB;
			$this->nametxt = bab_translate("Name");
			$this->descriptiontxt = bab_translate("Description");
			$this->grpnametxt = bab_translate("Groups");
			$this->grpdesctxt = bab_translate("Description");
			$this->add = bab_translate("Add");
			$this->delete = bab_translate("Delete");
			$this->uncheckall = bab_translate("Uncheck all");
			$this->checkall = bab_translate("Check all");
			$this->inscriptiontxt = bab_translate("Add in inscription form");
			$this->multiplicitytxt = bab_translate("Let user choose multiple groups");
			$this->requiredtxt = bab_translate("Required");
			$this->no = bab_translate("No");
			$this->yes = bab_translate("Yes");

			$res = $babDB->db_query("select * from ".BAB_PROFILES_TBL." where id ='".$babDB->db_escape_string($idprof)."'");
			$arr = $babDB->db_fetch_array($res);
			
			$tree = new bab_grptree();
			$this->groups = $tree->getGroups(BAB_REGISTERED_GROUP);
			
			$this->altbg = true;
			$this->bdel = true;
			$this->profid = $idprof;
			$this->what = 'modp';
			
			if( empty($pname)) 
				{
				$this->pname = $arr['name'];
				}
			else
				{
				$this->pname = $pname;
				}
			if( empty($pdesc)) 
				{
				$this->pdesc = $arr['description'];
				}
			else
				{
				$this->pdesc = $pdesc;
				}

			if( count($grpids) ==  0 )
				{
				$this->grpids = array();
				$res = $babDB->db_query("select * from ".BAB_PROFILES_GROUPSSET_TBL." where id_object ='".$babDB->db_escape_string($idprof)."'");
				while( $rr = $babDB->db_fetch_array($res))
					{
					$this->grpids[] = $rr['id_group'];
					}
				}
			else
				{
				$this->grpids = $grpids;
				}

			if( empty($cinscription)) 
				{
				$cinscription = $arr['inscription'];
				}

			if( empty($cmultiple)) 
				{
				$cmultiple = $arr['multiplicity'];
				}

			if( empty($crequired)) 
				{
				$crequired = $arr['required'];
				}

			if( $cinscription == 'Y')
				{
				$this->cinscheck = 'checked';
				}
			else
				{
				$this->cinscheck = '';
				}

			if( $cmultiple == 'Y')
				{
				$this->cmulcheck = 'checked';
				}
			else
				{
				$this->cmulcheck = '';
				}
			if( $crequired == 'Y')
				{
				$this->creqcheck = 'checked';
				}
			else
				{
				$this->creqcheck = '';
				}
			}

		function getnext()
			{
			global $babDB;
			
			if( list(,$arr) = each($this->groups))
				{
				$this->altbg = !$this->altbg;
				$this->grpid = $arr['id'];
				$this->grpname = $arr['name'];
				$this->grpdesc = $arr['description'];
				if( count($this->grpids) > 0  && in_array( $arr['id'],$this->grpids))
					{
					$this->grpcheck = 'checked';
					}
				else
					{
					$this->grpcheck = '';
					}
				return true;
				}
			return false;
			}
		}

	$temp = new temp($idprof,$pname, $pdesc, $grpids, $cinscription, $cmultiple, $crequired);
	$babBody->babecho(	bab_printTemplate($temp, "profiles.html", "profilecreate"));
	}

function profilesList()
	{
	global $babBody;
	class temp
		{
		var $name;
		var $description;

		function temp()
			{
			global $babBody, $babDB;
			$this->nametxt = bab_translate("Name");
			$this->descriptiontxt = bab_translate("Description");
			$this->inscriptiontxt = bab_translate("Inscription");
			$this->rightstxt = bab_translate("Rights");
			$this->yestxt = bab_translate("Yes");
			$this->notxt = bab_translate("No");
			$this->res = $babDB->db_query("select * from ".BAB_PROFILES_TBL." where id_dgowner='".$babDB->db_escape_string($babBody->currentAdmGroup)."' order by name asc");
			$this->count = $babDB->db_num_rows($this->res);
			}

		function getnext()
			{
			global $babDB;
			static $i = 0;	
			if( $i < $this->count)
				{
				$arr = $babDB->db_fetch_array($this->res);
				$this->url = $GLOBALS['babUrlScript']."?tg=profiles&idx=pmod&idprof=".$arr['id'];
				$this->urlname = $arr['name'];
				$this->descval = $arr['description'];
				$this->inscval = $arr['inscription'] ==  'Y'? $this->yestxt: $this->notxt;
				if( $arr['inscription'] == 'N' )
					{
					$this->rightsurl = $GLOBALS['babUrlScript']."?tg=profiles&idx=pacl&idprof=".$arr['id'];
					$this->brights = true;
					}
				else
					{
					$this->brights = false;
					}
				$i++;
				return true;
				}
			return false;
			}
		}

	$temp = new temp();
	$babBody->babecho(	bab_printTemplate($temp,"profiles.html", "profileslist"));
	}


function profileDelete($idprof)
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

		function temp($idprof)
			{
			global $babDB;
			$this->message = bab_translate("Are you sure you want to delete this profile");
			list($this->title) = $babDB->db_fetch_row($babDB->db_query("select name from ".BAB_PROFILES_TBL." where id='".$babDB->db_escape_string($idprof)."'"));
			$this->warning = bab_translate("WARNING: This operation will delete the profile with all references"). "!";
			$this->urlyes = $GLOBALS['babUrlScript']."?tg=profiles&idx=pdel&idprof=".$idprof."&action=Yes";
			$this->yes = bab_translate("Yes");
			$this->urlno = $GLOBALS['babUrlScript']."?tg=profiles&idx=pmod&idprof=".$idprof;
			$this->no = bab_translate("No");
			}
		}

	$temp = new temp($idprof);
	$babBody->babecho( bab_printTemplate($temp,"warning.html", "warningyesno"));
	}


function saveProfile($pname, $pdesc, $grpids, $cinscription, $cmultiple, $crequired)
	{
	global $babBody, $babDB;

	if( empty($pname))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a name !!");
		return false;
		}

	if( count($grpids) == 0)
		{
		$babBody->msgerror = bab_translate("ERROR: You must add at least one group !!");
		return false;
		}

	$res = $babDB->db_query("select name from ".BAB_PROFILES_TBL." where name='".$babDB->db_escape_string($pname)."'");
	if( $babDB->db_num_rows($res) > 0)
		{
		$babBody->msgerror = bab_translate("This profile already exists");
		return false;
		}
	else
		{
		if( $cinscription == 'Y')
			{
			$inscription = 'Y';
			}
		else
			{
			$inscription = 'N';
			}
		if( $cmultiple == 'Y')
			{
			$multiplicity = 'Y';
			}
		else
			{
			$multiplicity = 'N';
			}
		if( $crequired == 'Y')
			{
			$required = 'Y';
			}
		else
			{
			$required = 'N';
			}
		$babDB->db_query("insert into ".BAB_PROFILES_TBL." (name, description, multiplicity, inscription, required, id_dgowner) 
		VALUES 
			(
			'" . $babDB->db_escape_string($pname). "',
			'" . $babDB->db_escape_string($pdesc). "',
			'" . $babDB->db_escape_string($multiplicity)."',
			'" . $babDB->db_escape_string($inscription)."', 
			'" . $babDB->db_escape_string($required). "',
			'" . $babDB->db_escape_string($babBody->currentAdmGroup)."'
			)
		");
		$id = $babDB->db_insert_id();
		for( $i = 0; $i < count($grpids); $i++ )
			{
			$babDB->db_query("insert into ".BAB_PROFILES_GROUPSSET_TBL." (id_object, id_group) VALUES ('" .$babDB->db_escape_string($id). "', '".$babDB->db_escape_string($grpids[$i])."')");
			}

		if( $inscription == 'Y' )
			{
			$babDB->db_query("insert into ".BAB_PROFILES_GROUPS_TBL." (id_object, id_group) values ('". $babDB->db_escape_string($id). "', '1')");
			}
		return true;
		}

	}

function updateProfile($idprof, $pname, $pdesc, $grpids, $cinscription, $cmultiple, $crequired)
	{
	global $babBody, $babDB;

	if( empty($pname))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a name !!");
		return false;
		}

	if( count($grpids) == 0)
		{
		$babBody->msgerror = bab_translate("ERROR: You must add at least one group !!");
		return false;
		}

	$res = $babDB->db_query("select name from ".BAB_PROFILES_TBL." where name='".$babDB->db_escape_string($pname)."' and id !='".$babDB->db_escape_string($idprof)."'");
	if( $babDB->db_num_rows($res) > 0)
		{
		$babBody->msgerror = bab_translate("This profile already exists");
		return false;
		}
	else
		{
		if( $cinscription == 'Y')
			{
			$inscription = 'Y';
			}
		else
			{
			$inscription = 'N';
			}
		if( $cmultiple == 'Y')
			{
			$multiplicity = 'Y';
			}
		else
			{
			$multiplicity = 'N';
			}
		if( $crequired == 'Y')
			{
			$required = 'Y';
			}
		else
			{
			$required = 'N';
			}
		$babDB->db_query("update ".BAB_PROFILES_TBL." set 
		name='" .$babDB->db_escape_string($pname). "', 
		description='" . $babDB->db_escape_string($pdesc). "', 
		multiplicity='".$babDB->db_escape_string($multiplicity)."', 
		inscription='" . $babDB->db_escape_string($inscription)."', 
		required='" . $babDB->db_escape_string($required). "', 
		id_dgowner='".$babDB->db_escape_string($babBody->currentAdmGroup)."' 
		where id='".$babDB->db_escape_string($idprof)."'");

		$babDB->db_query("delete from ".BAB_PROFILES_GROUPSSET_TBL." where id_object='".$babDB->db_escape_string($idprof)."'");
		for( $i = 0; $i < count($grpids); $i++ )
			{
			$babDB->db_query("insert into ".BAB_PROFILES_GROUPSSET_TBL." (id_object, id_group) VALUES ('" .$babDB->db_escape_string($idprof). "', '".$babDB->db_escape_string($grpids[$i])."')");
			}
		if( $inscription == 'Y' )
			{
			include_once $GLOBALS['babInstallPath']."admin/acl.php";
			aclDelete(BAB_PROFILES_GROUPS_TBL, $idprof);
			$babDB->db_query("insert into ".BAB_PROFILES_GROUPS_TBL." (id_object, id_group) values ('". $babDB->db_escape_string($idprof). "', '1')");
			}
		return true;
		}

	}

function confirmDeleteProfile($idprof)
{
	global $babBody, $babDB;

	$babDB->db_query("delete from ".BAB_PROFILES_TBL." where id='".$babDB->db_escape_string($idprof)."'");
	$babDB->db_query("delete from ".BAB_PROFILES_GROUPSSET_TBL." where id_object='".$babDB->db_escape_string($idprof)."'");
	include_once $GLOBALS['babInstallPath'].'admin/acl.php';
	aclDelete(BAB_PROFILES_GROUPS_TBL, $idprof);
}

/* main */
if( !$babBody->isSuperAdmin /*&& $babBody->currentDGGroup['profiles'] != 'Y'*/)
{
	$babBody->msgerror = bab_translate("Access denied");
	return;
}

if( !isset($idx)){$idx = "plist";}

if( isset($add))
{
	if( $add == 'addp' )
	{
		if( !isset($pname)){$pname = "";}
		if( !isset($pdesc)){$pdesc = "";}
		if( !isset($grpids)){$grpids = array();}
		if( !isset($cinscription)){$cinscription = "";}
		if( !isset($cmultiple)){$cmultiple = "";}
		if( !isset($crequired)){$crequired = "";}
		if(!saveProfile($pname, $pdesc, $grpids, $cinscription, $cmultiple, $crequired))
		{
			$idx = 'padd';
		}
	}
	elseif( $add == 'modp' )
	{
		if( isset($deletep))
		{
			$idx = 'pdel';
		}
		else
		{
			if( !isset($pname)){$pname = "";}
			if( !isset($pdesc)){$pdesc = "";}
			if( !isset($grpids)){$grpids = array();}
			if( !isset($cinscription)){$cinscription = "";}
			if( !isset($cmultiple)){$cmultiple = "";}
			if( !isset($crequired)){$crequired = "";}
			if(!updateProfile($idprof, $pname, $pdesc, $grpids, $cinscription, $cmultiple, $crequired))
			{
				$idx = 'pmod';
			}
		}
	}
}
elseif( isset($action) && $action == 'Yes' )
{
	confirmDeleteProfile($idprof);
	$idx = 'plist';
}
elseif( isset($aclview) )
{
	include_once $babInstallPath."admin/acl.php";
	maclGroups();
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=profiles&idx=plist");
	exit;
}


switch($idx)
	{
	case "pacl":
		include_once $babInstallPath."admin/acl.php";
		if( $babBody->isSuperAdmin || $babBody->currentDGGroup['groups'] == 'Y')
		{
			$macl = new macl("profiles", "plist", $idprof, "aclview");
			$macl->addtable( BAB_PROFILES_GROUPS_TBL, bab_translate("Who can use this profile?"));
			$macl->filter(0,0,1,0,1);
			$macl->babecho();
			$babBody->title = bab_translate("Access profile");
			$babBody->addItemMenu("List", bab_translate("Groups"), $GLOBALS['babUrlScript']."?tg=groups&idx=List");
			$babBody->addItemMenu("plist", bab_translate("Profiles"), $GLOBALS['babUrlScript']."?tg=profiles&idx=plist");
			$babBody->addItemMenu("pacl", bab_translate("Rights"), $GLOBALS['babUrlScript']."?tg=profiles&idx=pacl&idprof=".$idprof);
			$babBody->addItemMenu("padd", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=profiles&idx=padd");
		}
		else
		{
			$babBody->msgerror = bab_translate("Access denied");
		}
		break;
	case "pdel":
		if( $babBody->isSuperAdmin || $babBody->currentDGGroup['groups'] == 'Y')
		{
			profileDelete($idprof);
			$babBody->title = bab_translate("Delete profile");
			$babBody->addItemMenu("List", bab_translate("Groups"), $GLOBALS['babUrlScript']."?tg=groups&idx=List");
			$babBody->addItemMenu("plist", bab_translate("Profiles"), $GLOBALS['babUrlScript']."?tg=profiles&idx=plist");
			$babBody->addItemMenu("pmod", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=profiles&idx=pmod&idprof=".$idprof);
			$babBody->addItemMenu("pdel", bab_translate("Delete"), $GLOBALS['babUrlScript']."?tg=profiles&idx=pdel&idprof=".$idprof);
			$babBody->addItemMenu("padd", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=profiles&idx=padd");
		}
		else
		{
			$babBody->msgerror = bab_translate("Access denied");
		}
		break;
	case "pmod":
		if( $babBody->isSuperAdmin || $babBody->currentDGGroup['groups'] == 'Y')
		{
			if( !isset($pname)){$pname = "";}
			if( !isset($pdesc)){$pdesc = "";}
			if( !isset($grpids)){$grpids = array();}
			if( !isset($cinscription)){$cinscription = "";}
			if( !isset($cmultiple)){$cmultiple = "";}
			if( !isset($crequired)){$crequired = "";}
			profileModify($idprof, $pname, $pdesc, $grpids, $cinscription, $cmultiple, $crequired);
			$babBody->title = bab_translate("Create a new profile");
			$babBody->addItemMenu("List", bab_translate("Groups"), $GLOBALS['babUrlScript']."?tg=groups&idx=List");
			$babBody->addItemMenu("plist", bab_translate("Profiles"), $GLOBALS['babUrlScript']."?tg=profiles&idx=plist");
			$babBody->addItemMenu("pmod", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=profiles&idx=pmod");
			$babBody->addItemMenu("padd", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=profiles&idx=padd");
		}
		else
		{
			$babBody->msgerror = bab_translate("Access denied");
		}
		break;
	case "padd":
		if( $babBody->isSuperAdmin || $babBody->currentDGGroup['groups'] == 'Y')
		{
			if( !isset($pname)){$pname = "";}
			if( !isset($pdesc)){$pdesc = "";}
			if( !isset($grpids)){$grpids = array();}
			if( !isset($cinscription)){$cinscription = "";}
			if( !isset($cmultiple)){$cmultiple = "";}
			if( !isset($crequired)){$crequired = "";}
			profileCreate($pname, $pdesc, $grpids, $cinscription, $cmultiple, $crequired);
			$babBody->title = bab_translate("Create a new profile");
			$babBody->addItemMenu("List", bab_translate("Groups"), $GLOBALS['babUrlScript']."?tg=groups&idx=List");
			$babBody->addItemMenu("plist", bab_translate("Profiles"), $GLOBALS['babUrlScript']."?tg=profiles&idx=plist");
			$babBody->addItemMenu("padd", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=profiles&idx=padd");
		}
		else
		{
			$babBody->msgerror = bab_translate("Access denied");
		}
		break;
	case "plist":
	default:
		if( $babBody->isSuperAdmin || $babBody->currentDGGroup['groups'] == 'Y')
		{
			profilesList();
			$babBody->title = bab_translate("Profiles list");
			$babBody->addItemMenu("List", bab_translate("Groups"), $GLOBALS['babUrlScript']."?tg=groups&idx=List");
			$babBody->addItemMenu("plist", bab_translate("Profiles"), $GLOBALS['babUrlScript']."?tg=groups&idx=plist");
			$babBody->addItemMenu("padd", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=profiles&idx=padd");
		}
		else
		{
			$babBody->msgerror = bab_translate("Access denied");
		}
		break;
	}

$babBody->setCurrentItemMenu($idx);
?>