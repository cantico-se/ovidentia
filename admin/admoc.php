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
include_once $babInstallPath."admin/acl.php";

define("ORG_MAX_REQUESTS_LIST", 100);

function bab_getOrgChartName($id)
{
	$db = $GLOBALS['babDB'];
	$query = "select name from ".BAB_ORG_CHARTS_TBL." where id='".$id."'";
	$res = $db->db_query($query);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		return $arr['name'];
		}
	else
		{
		return "";
		}
}

function modifyOrgChart($id)
	{
	global $babBody;

	class temp
		{
		var $name;
		var $description;
		var $update;
		var $delete;

		var $db;
		var $arr = array();
		var $res;

		var $nameval;
		var $descval;
		var $id;

		function temp($id)
			{
			$this->id = $id;
			$this->name = bab_translate("Name");
			$this->description = bab_translate("Description");
			$this->update = bab_translate("Update");
			$this->t_duplicatetxt = bab_translate("Duplicate this chart as");
			$this->t_name = bab_translate("New name");
			$this->duplicate = bab_translate("Duplicate");
			$this->delete = bab_translate("Delete");
			$this->db = $GLOBALS['babDB'];
			$this->res = $this->db->db_query("select * from ".BAB_ORG_CHARTS_TBL." where id='".$id."'");
			$this->arr = $this->db->db_fetch_array($this->res);
			if( $this->arr['isprimary'] == 'Y' )
				{
				$this->bdelete = false;
				}
			else
				{
				$this->bdelete = true;
				}
			$this->nameval = $this->arr['name'];
			$this->descval = $this->arr['description'];
			}
		}

	$temp = new temp($id);
	$babBody->babecho(	bab_printTemplate($temp,"admocs.html", "ocmodify"));
	}

function deleteOrgChart($id)
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

		function temp($id)
			{
			$this->message = bab_translate("Are you sure you want to delete this organization chart");
			$this->title = bab_getOrgChartName($id);
			$this->warning = bab_translate("WARNING: This operation will delete the chart and all composants"). "!";
			$this->urlyes = $GLOBALS['babUrlScript']."?tg=admoc&idx=delete&item=".$id."&action=Yes";
			$this->yes = bab_translate("Yes");
			$this->urlno = $GLOBALS['babUrlScript']."?tg=admoc&idx=modify&item=".$id;
			$this->no = bab_translate("No");
			}
		}

	$temp = new temp($id);
	$babBody->babecho(	bab_printTemplate($temp,"warning.html", "warningyesno"));
	}

function browseRoles($ocid, $oeid, $role, $type, $cb, $vpos, $echo=1)
	{
	global $babBody;
	class temp
		{
		function temp($ocid, $oeid, $role, $type, $cb, $vpos, $echo)
			{
			global $babBody, $babDB;
			$this->ocid = $ocid;
			$this->cb = $cb;
			$this->oeid = $oeid;
			$this->role = $role;
			$this->echo = $echo;
			$this->type = $type;
			$this->vpos = $vpos;
			list($this->orgname) = $babDB->db_fetch_row($babDB->db_query("select name from ".BAB_ORG_CHARTS_TBL." where id='".$this->ocid."'")); 
			
			$this->entitytxt = bab_translate("Entity");
			$this->roletxt = bab_translate("Role");
			$this->usernametxt = bab_translate("Fullname");
			$this->provided = bab_translate("Provided role");
			$this->notprovided = bab_translate("Vacant role");
			$this->lmrolename = bab_translate("Immediat superior");
			$this->topurl = "";
			$this->bottomurl = "";
			$this->nexturl = "";
			$this->prevurl = "";
			$this->topname = "";
			$this->bottomname = "";
			$this->nextname = "";
			$this->prevname = "";


			$req = BAB_OC_ROLES_TBL." ocrt LEFT  JOIN ".BAB_OC_ROLES_USERS_TBL." ocrut ON ocrt.id = ocrut.id_role LEFT  JOIN ".BAB_OC_ENTITIES_TBL." ocet ON ocet.id = ocrt.id_entity LEFT  JOIN ".BAB_DBDIR_ENTRIES_TBL." det ON  ocrut.id_user = det.id where ocet.id_oc='".$this->ocid."'";
			if( $type != "" )
				{
				$req .= " and ocrt.type IN (".$type.")";
				}

			switch($role )
				{
				case '1': /* used */
					$req .= " and ocrut.id_user is not null";
					$this->oneroles ="selected";
					$this->tworoles ="";
					break;
				case '2': /* not used */
					$req .= " and ocrut.id_user is null";
					$this->tworoles ="selected";
					$this->oneroles ="";
					break;
				case '0': /* all */
				default:
					$this->tworoles ="";
					$this->oneroles ="";
					break;
				}
			
			if( $oeid )
				{
				$req .= " and ocet.id='".$oeid."'";
				}

			list($total) = $babDB->db_fetch_row($babDB->db_query("select count(ocrt.id) as total from ".$req));
			if( $total > ORG_MAX_REQUESTS_LIST )
				{
				$urltmp = $GLOBALS['babUrlScript']."?tg=admoc&idx=browr&ocid=".$this->ocid."&eid=".$this->oeid."&type=".$this->type."&role=".$this->role."&echo=".$this->echo."&vpos=";

				if( $vpos > 0)
					{
					$this->topurl = $urltmp."0"."&cb=".$this->cb;
					$this->topname = "&lt;&lt;";
					}

				$next = $vpos - ORG_MAX_REQUESTS_LIST;
				if( $next >= 0)
					{
					$this->prevurl = $urltmp.$next."&cb=".$this->cb;
					$this->prevname = "&lt;";
					}

				$next = $vpos + ORG_MAX_REQUESTS_LIST;
				if( $next < $total)
					{
					$this->nexturl = $urltmp.$next."&cb=".$this->cb;
					$this->nextname = "&gt;";
					if( $next + ORG_MAX_REQUESTS_LIST < $total)
						{
						$bottom = $total - ORG_MAX_REQUESTS_LIST;
						}
					else
						$bottom = $next;
					$this->bottomurl = $urltmp.$bottom."&cb=".$this->cb;
					$this->bottomname = "&gt;&gt;";
					}
				}


			$req .= " order by ocrt.name asc";
			if( $total > ORG_MAX_REQUESTS_LIST)
				{
				$req .= " limit ".$vpos.",".ORG_MAX_REQUESTS_LIST;
				}

			$this->res = $babDB->db_query("select ocrt.name AS r_name, ocrt.id as id_role, det.sn, det.givenname, det.id as iduser, ocet.name as e_name, ocet.id as id_entity from ".$req);
			$this->count = $babDB->db_num_rows($this->res);

			$this->entres = $babDB->db_query("select id, name from ".BAB_OC_ENTITIES_TBL." where id_oc='".$this->ocid."' order by name asc");
			$this->entcount = $babDB->db_num_rows($this->entres);
			$this->altbg = false;
			}

		function getnextrow()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $babDB->db_fetch_array($this->res);
				$this->altbg = !$this->altbg;
				$this->entityname = $arr['e_name'];
				$this->jentity = str_replace("'", "\'", $arr['e_name']);
				$this->jentity = str_replace('"', "'+String.fromCharCode(34)+'",$this->jentity);

				$this->rolename = $arr['r_name'];
				$this->jrole = str_replace("'", "\'", $arr['r_name']);
				$this->jrole = str_replace('"', "'+String.fromCharCode(34)+'",$this->jrole);
				$this->roleid = $arr['id_role'];
				if( isset($arr['givenname']) )
					{
					$this->username = bab_composeUserName($arr['sn'], $arr['givenname']);
					}
				else
					{
					$this->username = false;
					}
				$i++;
				return true;
				}
			else
				return false;

			}

		function getnextentity()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->entcount)
				{
				$arr = $babDB->db_fetch_array($this->entres);
				$this->entityid = $arr['id'];
				$this->entityname = $arr['name'];
				if( $this->oeid == $this->entityid )
					{
					$this->selected = "selected";
					}
				else
					{
					$this->selected = "";
					}
				$i++;
				return true;
				}
			else
				return false;

			}
		
		}

	$temp = new temp($ocid, $oeid, $role, $type, $cb, $vpos, $echo);
	if( $echo )
		{
		echo bab_printTemplate($temp, "admocs.html", "browseroles");
		}
	else
		{
		return bab_printTemplate($temp, "admocs.html", "browseroles");
		}
	}


function updateOrgChart($id, $name, $description)
	{
	global $babBody;
	if( empty($name))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a name")." !!";
		return;
		}

	$db = $GLOBALS['babDB'];

	$query = "update ".BAB_ORG_CHARTS_TBL." set 
		name='".$db->db_escape_string($name)."', 
		description='".$db->db_escape_string($description)."' 
		where id = '$id'";
	$db->db_query($query);

	Header("Location: ". $GLOBALS['babUrlScript']."?tg=admocs&idx=list");
	}

function duplicateOrgChart($id, $name, $description)
	{
	global $babBody, $babDB;
	if( empty($name))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a name")." !!";
		return;
		}

	$res = $babDB->db_query("select * from ".BAB_ORG_CHARTS_TBL." where id='".$babDB->db_escape_string($id)."' and id_dgowner='".$babDB->db_escape_string($babBody->currentAdmGroup)."'");
	if( !$res || $babDB->db_num_rows($res) == 0)
		{
		$babBody->msgerror = bab_translate("Unknown organization chart")." !!";
		return;
		}
	
	$arr = $babDB->db_fetch_array($res);
	$idocsrc = $arr['id'];

	$query = "insert into ".BAB_ORG_CHARTS_TBL." (name, description, id_directory, type, id_dgowner) values ('" .$babDB->db_escape_string($name). "', '" . $babDB->db_escape_string($description). "', '" . $babDB->db_escape_string($arr['id_directory']). "', '" . $babDB->db_escape_string($arr['type']). "', '" . $babDB->db_escape_string($babBody->currentAdmGroup). "')";
	$babDB->db_query($query);
	$idnewoc = $babDB->db_insert_id();

	$res = $babDB->db_query("select * from ".BAB_OC_TREES_TBL." where id_user='".$babDB->db_escape_string($idocsrc)."' order by lf asc");
	$parents = array();
	$entities = array();
	$parents[0] = 0; 
	while( $arr = $babDB->db_fetch_array($res))
		{
		$babDB->db_query("insert into ".BAB_OC_TREES_TBL." (lf, lr, id_parent, info_user, id_user) values ('".$babDB->db_escape_string($arr['lf'])."', '".$babDB->db_escape_string($arr['lr'])."', '".$babDB->db_escape_string($parents[$arr['id_parent']])."', '".$babDB->db_escape_string($arr['info_user'])."', '".$idnewoc."')");

		$idnewnode = $babDB->db_insert_id();
		$parents[$arr['id']] = $idnewnode; 

		$rs = $babDB->db_query("select * from ".BAB_OC_ENTITIES_TBL." where id_oc='".$babDB->db_escape_string($idocsrc)."' and id_node='".$babDB->db_escape_string($arr['id'])."'");
		while( $rr = $babDB->db_fetch_array($rs)) // only one record
			{
			$babDB->db_query("insert into ".BAB_OC_ENTITIES_TBL." (name, description, id_node, e_note, id_oc) values ('".$babDB->db_escape_string($rr['name'])."', '".$babDB->db_escape_string($rr['description'])."', '".$babDB->db_escape_string($idnewnode)."', '".$babDB->db_escape_string($rr['e_note'])."', '".$idnewoc."')");
			$entities[$rr['id']] = $babDB->db_insert_id();
			}
		}


	$res = $babDB->db_query("select * from ".BAB_OC_ROLES_TBL." where id_oc='".$babDB->db_escape_string($idocsrc)."'");
	while( $arr = $babDB->db_fetch_array($res))
		{
		$babDB->db_query("insert into ".BAB_OC_ROLES_TBL." (name, description, id_entity, type, cardinality, id_oc) values ('".$babDB->db_escape_string($arr['name'])."', '".$babDB->db_escape_string($arr['description'])."', '".$babDB->db_escape_string($entities[$arr['id_entity']])."', '".$babDB->db_escape_string($arr['type'])."', '".$babDB->db_escape_string($arr['cardinality'])."', '".$idnewoc."')");
		$idnewrole = $babDB->db_insert_id();
		$rs = $babDB->db_query("select * from ".BAB_OC_ROLES_USERS_TBL." where id_role='".$babDB->db_escape_string($arr['id'])."'");
		while( $rr = $babDB->db_fetch_array($rs))
			{
			$babDB->db_query("insert into ".BAB_OC_ROLES_USERS_TBL." (id_role, id_user, isprimary) values ('".$babDB->db_escape_string($idnewrole)."', '".$babDB->db_escape_string($rr['id_user'])."', '".$babDB->db_escape_string($rr['isprimary'])."')");
			}
		}

	Header("Location: ". $GLOBALS['babUrlScript']."?tg=admocs&idx=list");
	}

function confirmDeleteOrgChart($id)
	{
	include_once $GLOBALS['babInstallPath']."utilit/delincl.php";
	bab_deleteOrgChart($id);
	//Header("Location: ". $GLOBALS['babUrlScript']."?tg=admocs");
	}

/* main */
if( !$babBody->isSuperAdmin && $babBody->currentDGGroup['orgchart'] != 'Y')
{
	$babBody->msgerror = bab_translate("Access denied");
	return;
}

$idx = bab_rp('idx', 'modify');

if( '' != ($update = bab_pp('update')))
	{
    switch ($update)
        {
        case 'updateoc':
			if( isset($submit))
				updateOrgChart($item, $fname, $description);
			else if( isset($bdelete))
				{
				$idx = "delete";
				}
			break;
		case 'duplicateoc':
			$ocnname = bab_pp('ocnname', '');
			$ocndesc = bab_pp('ocndesc', '');
			duplicateOrgChart($item, $ocnname, $ocndesc);
			break;
		}
	}


if( isset($aclview))
	{
	maclGroups();
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=admocs&idx=list");
	}

if( isset($action) && $action == "Yes")
	{
	confirmDeleteOrgChart($item);
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=admocs&idx=list");
	exit;
	}

switch($idx)
	{
	case "browr":
		if( !isset($role)) $role =0;
		if( !isset($vpos)) $vpos =0;
		if( !isset($echo)) $echo =1;
		if( !isset($type)) $type ='0,1,3';
		if( !isset($eid)) $eid =0;
		browseRoles($ocid, $eid, $role, $type, $cb, $vpos, $echo);
		exit;
		break;

	case "ocrights":
		$babBody->title = bab_getOrgChartName($item) . ": ".bab_translate("List of groups");

		$macl = new macl("admoc", "modify", $item, "aclview");
        $macl->addtable( BAB_OCVIEW_GROUPS_TBL,bab_translate("View"));
		$macl->addtable( BAB_OCUPDATE_GROUPS_TBL,bab_translate("Update"));
		$macl->filter(0,0,1,0,1);
        $macl->babecho();

		$babBody->addItemMenu("list", bab_translate("Charts"), $GLOBALS['babUrlScript']."?tg=admocs&idx=list");
		$babBody->addItemMenu("modify", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=admoc&idx=addoc&item=".$item);
		$babBody->addItemMenu("ocrights", bab_translate("Rights"), $GLOBALS['babUrlScript']."?tg=admoc&idx=ocrights&item=".$item);
		break;

	case "ocupdate":
		$babBody->title = bab_getOrgChartName($item) . ": ".bab_translate("List of groups");
		aclGroups("admoc", "modify", BAB_OCUPDATE_GROUPS_TBL, $item, "aclview");
		$babBody->addItemMenu("list", bab_translate("Charts"), $GLOBALS['babUrlScript']."?tg=admocs&idx=list");
		$babBody->addItemMenu("modify", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=admoc&idx=addoc&item=".$item);
		$babBody->addItemMenu("ocrights", bab_translate("Rights"), $GLOBALS['babUrlScript']."?tg=admoc&idx=ocrights&item=".$item);
		break;

	case "delete":
		$babBody->title = bab_translate("Delete organization chart");
		deleteOrgChart($item);
		$babBody->addItemMenu("list", bab_translate("Charts"), $GLOBALS['babUrlScript']."?tg=admocs&idx=list");
		$babBody->addItemMenu("modify", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=admoc&idx=addoc&item=".$item);
		$babBody->addItemMenu("ocrights", bab_translate("Rights"), $GLOBALS['babUrlScript']."?tg=admoc&idx=ocrights&item=".$item);
		break;

	default:
	case "modify":
		$babBody->title = bab_translate("Modify an organization chart");
		modifyOrgChart($item);
		$babBody->addItemMenu("list", bab_translate("Charts"), $GLOBALS['babUrlScript']."?tg=admocs&idx=list");
		$babBody->addItemMenu("modify", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=admoc&idx=addoc&item=".$item);
		$babBody->addItemMenu("ocrights", bab_translate("Rights"), $GLOBALS['babUrlScript']."?tg=admoc&idx=ocrights&item=".$item);
		break;
	}
$babBody->setCurrentItemMenu($idx);
?>