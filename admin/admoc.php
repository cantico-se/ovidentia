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
include $babInstallPath."admin/acl.php";

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

		function temp($id)
			{
			$this->name = bab_translate("Name");
			$this->description = bab_translate("Description");
			$this->update = bab_translate("Update");
			$this->delete = bab_translate("Delete");
			$this->db = $GLOBALS['babDB'];
			$this->res = $this->db->db_query("select * from ".BAB_ORG_CHARTS_TBL." where id='".$id."'");
			$this->arr = $this->db->db_fetch_array($this->res);
			if( $this->arr['id'] == 1 )
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
				if( $arr['givenname'] )
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

	if( !bab_isMagicQuotesGpcOn())
		{
		$name = addslashes($name);
		$description = addslashes($description);
		}

	$query = "update ".BAB_FORUMS_TBL." set name='".$name."', description='".$description."' where id = '$id'";
	$db->db_query($query);
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=admocs&idx=list");
	}

function confirmDeleteOrgChart($id)
	{
	include_once $GLOBALS['babInstallPath']."utilit/delincl.php";
	bab_deleteOrgChart($id);
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=admocs");
	}

/* main */
if( !$babBody->isSuperAdmin && $babBody->currentDGGroup['orgchart'] != 'Y')
{
	$babBody->msgerror = bab_translate("Access denied");
	return;
}

if(!isset($idx))
	{
	$idx = "modify";
	}

if( isset($update) && $update == "updateoc")
	{
	if( isset($submit))
		updateForum($item, $fname, $description);
	else if( isset($bdelete))
		{
		$idx = "delete";
		}
	}

if( isset($aclview))
	{
	aclUpdate($table, $item, $groups, $what);
	if( $table == BAB_OCVIEW_GROUPS_TBL )
		{
		Header("Location: ". $GLOBALS['babUrlScript']."?tg=admoc&idx=ocupdate&item=".$item);
		}
	else
		{
		Header("Location: ". $GLOBALS['babUrlScript']."?tg=admocs&idx=list");
		}
	exit;
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
		if( !isset($type)) $type ='0,1';
		if( !isset($eid)) $eid =0;
		browseRoles($ocid, $eid, $role, $type, $cb, $vpos, $echo);
		exit;
		break;

	case "ocview":
		$babBody->title = bab_getOrgChartName($item) . ": ".bab_translate("List of groups");
		aclGroups("admoc", "modify", BAB_OCVIEW_GROUPS_TBL, $item, "aclview");
		$babBody->addItemMenu("list", bab_translate("Charts"), $GLOBALS['babUrlScript']."?tg=admocs&idx=list");
		$babBody->addItemMenu("modify", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=admoc&idx=addoc&item=".$item);
		$babBody->addItemMenu("ocview", bab_translate("View"), $GLOBALS['babUrlScript']."?tg=admoc&idx=ocview&item=".$item);
		$babBody->addItemMenu("ocupdate", bab_translate("Update"), $GLOBALS['babUrlScript']."?tg=admoc&idx=ocupdate&item=".$item);
		break;

	case "ocupdate":
		$babBody->title = bab_getOrgChartName($item) . ": ".bab_translate("List of groups");
		aclGroups("admoc", "modify", BAB_OCUPDATE_GROUPS_TBL, $item, "aclview");
		$babBody->addItemMenu("list", bab_translate("Charts"), $GLOBALS['babUrlScript']."?tg=admocs&idx=list");
		$babBody->addItemMenu("modify", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=admoc&idx=addoc&item=".$item);
		$babBody->addItemMenu("ocview", bab_translate("View"), $GLOBALS['babUrlScript']."?tg=admoc&idx=ocview&item=".$item);
		$babBody->addItemMenu("ocupdate", bab_translate("Update"), $GLOBALS['babUrlScript']."?tg=admoc&idx=ocupdate&item=".$item);
		break;

	case "delete":
		$babBody->title = bab_translate("Delete organization chart");
		deleteOrgChart($item);
		$babBody->addItemMenu("list", bab_translate("Charts"), $GLOBALS['babUrlScript']."?tg=admocs&idx=list");
		$babBody->addItemMenu("modify", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=admoc&idx=addoc&item=".$item);
		$babBody->addItemMenu("ocview", bab_translate("View"), $GLOBALS['babUrlScript']."?tg=admoc&idx=ocview&item=".$item);
		$babBody->addItemMenu("ocupdate", bab_translate("Update"), $GLOBALS['babUrlScript']."?tg=admoc&idx=ocupdate&item=".$item);
		break;

	default:
	case "modify":
		$babBody->title = bab_translate("Modify a forum");
		modifyOrgChart($item);
		$babBody->addItemMenu("list", bab_translate("Charts"), $GLOBALS['babUrlScript']."?tg=admocs&idx=list");
		$babBody->addItemMenu("modify", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=admoc&idx=addoc&item=".$item);
		$babBody->addItemMenu("ocview", bab_translate("View"), $GLOBALS['babUrlScript']."?tg=admoc&idx=ocview&item=".$item);
		$babBody->addItemMenu("ocupdate", bab_translate("Update"), $GLOBALS['babUrlScript']."?tg=admoc&idx=ocupdate&item=".$item);
		break;
	}
$babBody->setCurrentItemMenu($idx);

?>