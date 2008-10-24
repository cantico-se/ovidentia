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
include_once $babInstallPath."utilit/orgincl.php";
include_once $babInstallPath."utilit/treeincl.php";

function addOrgChartEntity($ocid, $oeid, $nameval, $descriptionval)
	{
	global $babLittleBody;
	class temp
		{
		var $name;
		var $description;
		var $ocid;
		var $oeid;
		var $nameval;
		var $descriptionval;
		var $add;
		var $parent;
		var $child;
		var $nextchild;
		var $previouschild;
		var $relation;

		function temp($ocid, $oeid, $nameval, $descriptionval)
			{
			global $babDB, $ocinfo, $babBody;
			$this->ocid = $ocid;
			$this->oeid = $oeid;
			$this->name = bab_translate("Entity");
			$this->description = bab_translate("Description");
			$this->parent = bab_translate("Parent entity");
			$this->add = bab_translate("Add");
			$this->relation = bab_translate("Relation");
			$this->child = bab_translate("Child");
			$this->previouschild = bab_translate("Previous sibling");
			$this->nextchild = bab_translate("Next sibling");
			$this->nameval = $nameval == ""? "": $nameval;
			$this->descriptionval = $descriptionval == ""? "": $descriptionval;
			if( $ocinfo['isprimary'] == 'Y' && $ocinfo['id_group'] == 1)
				{
				$this->nonetxt = "--- ".bab_translate("None")." ---";
				$this->newgrouptxt = "--- ".bab_translate("New group")." ---";
				$this->grouptxt = bab_translate("Group");
				include_once $GLOBALS['babInstallPath']."utilit/grptreeincl.php";

				$tree = new bab_grptree();
				$this->allgroups = $tree->getGroups(BAB_REGISTERED_GROUP, '%s '.chr(160).' '.chr(160).' ');
				}
			}

		function getnextgroup()
			{
			global $babDB;
			if( list(,$this->arr) = each($this->allgroups))
				{
				$this->groupname = bab_toHtml($this->arr['name']);
				$this->grpid = bab_toHtml($this->arr['id']);
				return true;
				}
			else
				return false;

			}
		}

	$temp = new temp($ocid, $oeid, $nameval, $descriptionval);
	$babLittleBody->babecho(bab_printTemplate($temp,"flbchart.html", "ocecreate"));
	}



function modifyOrgChartEntity($ocid, $eid)
{
	global $babLittleBody;

	class ModifyOrgChartEntity_Template
		{
		var $name;
		var $description;
		var $types;
		var $ocid;
		var $nameval;
		var $descriptionval;
		var $add;
		var $parent;
		var $delete;
		var $entityTypes;

		function ModifyOrgChartEntity_Template($ocid, $eid)
		{
			global $babDB;
			$this->ocid = $ocid;
			$this->oeid = $eid;
			$this->name = bab_translate("Name");
			$this->description = bab_translate("Description");
			$this->type = bab_translate("Entity type");
			$this->parent = bab_translate("Parent entity");
			$this->add = bab_translate("Update");
			$this->delete = bab_translate("Delete");
			$res = $babDB->db_query("select * from ".BAB_OC_ENTITIES_TBL." where id='".$eid."'");
			if( !$res || $babDB->db_num_rows($res) == 0 )
			{
				Header("Location: ". $GLOBALS['babUrlScript']."?tg=flbchart&idx=adde&ocid=".$ocid);
				exit;
			}
			$arr = $babDB->db_fetch_array($res);
			$this->nameval = $arr['name'];
			$this->descriptionval = $arr['description'];
			if( $arr['id_group'] != 0 )
			{
				$this->groupname = bab_getGroupName($arr['id_group']);
				$this->grouptxt = bab_translate("Associated group");
			}
			else
			{
				$this->groupname = false;
			}

			require_once $GLOBALS['babInstallPath']."utilit/ocapi.php";
			$this->ocEntityTypes = bab_OCGetOrgChartEntityTypes($ocid);
			
			$this->entityTypes = bab_OCGetEntityTypes($eid);
			$this->selectedEntityTypes = array();
			while ($entityType = $babDB->db_fetch_assoc($this->entityTypes)) {
				$this->selectedEntityTypes[$entityType['id']] = $entityType['id'];
			}
			
		}

		function entityTypes()
		{
			global $babDB;
			if ($entityType = $babDB->db_fetch_assoc($this->ocEntityTypes)) {
				$this->entity_type_id = $entityType['id'];
				$this->entity_type_name = $entityType['name'];
				$this->entity_type_description= $entityType['description'];
				$this->entity_type_selected = isset($this->selectedEntityTypes[$entityType['id']]);
				return true;
			}
			return false;
		}
	}

	$temp = new ModifyOrgChartEntity_Template($ocid, $eid);
	$babLittleBody->babecho(bab_printTemplate($temp, 'flbchart.html', 'ocemodify'));
}




function deleteOrgChartEntity($ocid, $eid)
	{
	global $babLittleBody;
	class temp
		{
		var $thisentity;
		var $entityandchild;
		var $ocid;
		var $entitychild;
		var $add;
		var $yes;
		var $no;
		var $removegrouptxt;

		function temp($ocid, $eid)
			{
			global $ocinfo;
			$this->ocid = $ocid;
			$this->oeid = $eid;
			$this->thisentity = bab_translate("Only entity");
			$this->entityandchild = bab_translate("Entity and children");
			$this->entitychild = bab_translate("Only children");
			$this->add = bab_translate("Delete");
			if( $ocinfo['isprimary'] == 'Y' && $ocinfo['id_group'] == 1)
				{
				$this->bdel = false; /* false for now  */
				$this->yes = bab_translate("Yes");
				$this->no = bab_translate("No");
				$this->removegrouptxt = bab_translate("Remove groups attached to entities ?");
				}
			else
				{
				$this->bdel = false;
				}
			}
		}

	$temp = new temp($ocid, $eid);
	$babLittleBody->babecho(bab_printTemplate($temp,"flbchart.html", "ocedelete"));
	}

function moveOrgChartEntity($ocid, $eid)
	{
	global $babLittleBody;
	class temp
		{
		var $thisentity;
		var $entityandchild;
		var $ocid;
		var $entitychild;
		var $add;
		var $childtxt;
		var $previoussiblingtxt;
		var $nextsiblingtxt;
		var $astxt;
		var $permute;

		function temp($ocid, $eid)
			{
			global $babDB;
			$this->ocid = $ocid;
			$this->oeid = $eid;
			$this->thisentity = bab_translate("Only entity");
			$this->entityandchild = bab_translate("Entity and children");
			$this->add = bab_translate("Move");
			$this->permute = bab_translate("Permute");
			$this->permutewithtxt = bab_translate("Permute with");
			$this->astxt = bab_translate("As");
			$this->childtxt = bab_translate("Child");
			$this->previoussiblingtxt = bab_translate("Previous Sibling");
			$this->nextsiblingtxt = bab_translate("Next Sibling");
			$this->res = $babDB->db_query("select * from ".BAB_OC_ENTITIES_TBL." where id_oc='".$ocid."' and id!='".$eid."' order by name asc");
			$this->count = $babDB->db_num_rows($this->res);
			}

		function getnextparent()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $babDB->db_fetch_array($this->res);
				$this->pid = $arr['id'];
				$this->parententity = $arr['name'];
				$i++;
				return true;
				}
			else
				{
				$i = 0;
				if( $this->count > 0 )
					$babDB->db_data_seek($this->res, 0);
				return false;
				}

			}
		}

	$temp = new temp($ocid, $eid);
	$babLittleBody->babecho(bab_printTemplate($temp,"flbchart.html", "ocemove"));
	}


function addOrgChartRole($ocid, $oeid, $nameval, $descriptionval)
	{
	global $babLittleBody;
	class temp
		{
		var $name;
		var $description;
		var $ocid;
		var $oeid;
		var $nameval;
		var $descriptionval;
		var $add;
		var $yes;
		var $no;

		function temp($ocid, $oeid, $nameval, $descriptionval)
			{
			global $babDB;
			$this->ocid = $ocid;
			$this->oeid = $oeid;
			$this->name = bab_translate("Role");
			$this->description = bab_translate("Description");
			$this->yes = bab_translate("Yes");
			$this->no = bab_translate("No");
			$this->cardinality = bab_translate("Cardinality multiple");
			$this->add = bab_translate("Add");
			$this->nameval = $nameval == ""? "": $nameval;
			$this->descriptionval = $descriptionval == ""? "": $descriptionval;
			}
		}

	$temp = new temp($ocid, $oeid, $nameval, $descriptionval);
	$babLittleBody->babecho(bab_printTemplate($temp,"flbchart.html", "ocrcreate"));
	}

function modifyOrgChartRole($ocid, $oeid, $nameval, $descriptionval, $orid)
	{
	global $babLittleBody;
	class temp
		{
		var $name;
		var $description;
		var $ocid;
		var $oeid;
		var $nameval;
		var $descriptionval;
		var $add;
		var $yes;
		var $no;
		var $type;

		function temp($ocid, $oeid, $nameval, $descriptionval, $orid)
			{
			global $babDB, $orinfo;
			$this->ocid = $ocid;
			$this->oeid = $oeid;
			$this->orid = $orid;
			$this->roletitle = $orinfo['name'];
			$this->name = bab_translate("Role");
			$this->description = bab_translate("Description");
			$this->yes = bab_translate("Yes");
			$this->no = bab_translate("No");
			$this->cardinality = bab_translate("Cardinality multiple");
			$this->add = bab_translate("Modify");
			$this->type = $orinfo['type'];
			if( !isset($nameval) || empty($nameval))
				{
				$this->nameval = $orinfo['name'];
				}
			else
				{
				$this->nameval = $nameval;
				}
			if( !isset($nameval) || empty($descriptionval))
				{
				$this->descriptionval = $orinfo['description'];
				}
			else
				{
				$this->descriptionval = $descriptionval;
				}
			if( $orinfo['cardinality'] == 'Y')
				{
				$this->yselected = "selected";
				$this->nselected = "";
				}
			else
				{
				$this->yselected = "";
				$this->nselected = "selected";
				}
			}
		}

	$temp = new temp($ocid, $oeid, $nameval, $descriptionval, $orid);
	$babLittleBody->babecho(bab_printTemplate($temp,"flbchart.html", "ocrmodify"));
	}

function listOrgChartRoles($ocid, $oeid)
	{
	global $babLittleBody;

	class temp
		{
		var $title;
		var $titlename;
		var $checkall;
		var $uncheckall;
		var $urltitle;

		var $res;
		var $count;

		function temp($ocid, $oeid)
			{
			global $babDB;

			$this->titlename = bab_translate("Collaborators");
			$this->uncheckall = bab_translate("Uncheck all");
			$this->checkall = bab_translate("Check all");
			$this->deletealt = bab_translate("Delete");
			$this->superiornametxt = bab_translate("Superior");
			$this->temporarynametxt = bab_translate("Temporary employee");

			$this->ocid = $ocid;
			$this->oeid = $oeid;

			$res = $babDB->db_query("select * from ".BAB_OC_ROLES_TBL." where id_entity='".$oeid."' and type IN(1,2) order by type asc");
			$arr = $babDB->db_fetch_array($res);
			$this->superiorname = $arr['name'];
			$this->superiordescription = $arr['description'];
			$this->superiorurl = $GLOBALS['babUrlScript']."?tg=flbchart&idx=modr&ocid=".$this->ocid."&oeid=".$this->oeid."&orid=".$arr['id'];

			$arr = $babDB->db_fetch_array($res);
			$this->temporaryname = $arr['name'];
			$this->temporarydescription = $arr['description'];
			$this->temporaryurl = $GLOBALS['babUrlScript']."?tg=flbchart&idx=modr&ocid=".$this->ocid."&oeid=".$this->oeid."&orid=".$arr['id'];


			$this->res = $babDB->db_query("select * from ".BAB_OC_ROLES_TBL." where id_entity='".$oeid."' and type NOT IN (1, 2) order by name asc");
			$this->count = $babDB->db_num_rows($this->res);
	
			}

		function getnext()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $babDB->db_fetch_array($this->res);
				$this->ocfid = $arr['id'];
				$this->title = $arr['name'];
				$this->rtype = $arr['type'];
				$this->description = $arr['description'];
				$this->urltitle = $GLOBALS['babUrlScript']."?tg=flbchart&idx=modr&ocid=".$this->ocid."&oeid=".$this->oeid."&orid=".$arr['id'];
				$i++;
				return true;
				}
			else
				return false;

			}
		
		}

	$temp = new temp($ocid, $oeid);
	$babLittleBody->babecho( bab_printTemplate($temp,"flbchart.html", "functionslist"));
	}

function usersOrgChartRole($ocid, $oeid, $orid)
	{
	global $babLittleBody;

	class temp
		{
		var $title;
		var $titlename;
		var $checkall;
		var $uncheckall;
		var $urltitle;

		var $res;
		var $count;

		function temp($ocid, $oeid, $orid)
			{
			global $babDB, $orinfo;

			$this->titlename = bab_translate("Add");
			$this->uncheckall = bab_translate("Uncheck all");
			$this->checkall = bab_translate("Check all");
			$this->deletealt = bab_translate("Delete");
			$this->roletitle = $orinfo['name'];
			list($iddir) = $babDB->db_fetch_row($babDB->db_query("select id_directory from ".BAB_ORG_CHARTS_TBL." where id='".$ocid."'"));
			$this->urltitle = $GLOBALS['babUrlScript']."?tg=directory&idx=usdb&id=".$iddir."&cb=";
			$this->ocid = $ocid;
			$this->oeid = $oeid;
			$this->orid = $orid;
			$this->addururl = $GLOBALS['babUrlScript']."?tg=flbchart&idx=addur&ocid=".$ocid."&oeid=".$oeid."&orid=".$orid."&iduser=";

			$this->res = $babDB->db_query("select det.sn, det.givenname, det.id as id_entry, ort.* from ".BAB_OC_ROLES_USERS_TBL." ort left join ".BAB_DBDIR_ENTRIES_TBL." det on det.id=ort.id_user where ort.id_role='".$orid."' order by det.givenname asc");
			$this->count = $babDB->db_num_rows($this->res);
			$this->noadd = false;
			switch($orinfo['type'])
				{
				case '1':
				case '2':
					if( $this->count > 0  && $orinfo['cardinality'] ==  'N')
						{
						$this->noadd = true;
						}
					break;
				default:
					if( $this->count > 0  && $orinfo['cardinality'] ==  'N')
						{
						$this->noadd = true;
						}
					break;
				}
			}

		function getnext()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $babDB->db_fetch_array($this->res);
				$this->title = bab_composeUserName($arr['givenname'],$arr['sn']);
				$this->idru = $arr['id'];
				$i++;
				return true;
				}
			else
				return false;

			}
		
		}

	$temp = new temp($ocid, $oeid, $orid);
	$babLittleBody->babecho( bab_printTemplate($temp,"flbchart.html", "roleuserslist"));
	}

function viewOrgChartRoleUpdate($ocid, $oeid, $iduser)
	{
	global $babLittleBody;

	class temp
		{

		function temp($ocid, $oeid, $iduser)
			{
			global $babDB;
			$this->ocid = $ocid;
			$this->oeid = $oeid;
			$this->iduser = $iduser;
			$this->updatename = bab_translate("Update");
			$this->altbg = false;

			$this->username = bab_getDbUserName($iduser);
			$this->res = $babDB->db_query("select ocrt.*, ocet.name as e_name, ocet.id as e_id from ".BAB_OC_ROLES_TBL." ocrt left join ".BAB_OC_ENTITIES_TBL." ocet on ocrt.id_entity=ocet.id where ocrt.id_oc='".$ocid."'");

			while( $row = $babDB->db_fetch_array($this->res) )
				{
				if( !isset($this->entities[$row['e_id']]))
					{
					$this->entities[$row['e_id']] = array('name' => $row['e_name']);
					}
				$this->entities[$row['e_id']]['roles'][] = array($row['id'], $row['name'], $row['cardinality']);
				}
			$this->count = count($this->entities);

			$res = $babDB->db_query("select ocrut.* from ".BAB_OC_ROLES_USERS_TBL." ocrut left join ".BAB_OC_ROLES_TBL." ocrt on ocrut.id_role=ocrt.id where ocrt.id_oc='".$ocid."' and ocrut.id_user='".$iduser."'");
			while( $row = $babDB->db_fetch_array($res) )
				{
				$this->userroles[] = $row['id_role'];
				}

			if( count($this->userroles) > 0 )
				{
				$this->userrolesinput =implode(',', $this->userroles);
				}
			else
				{
				$this->userrolesinput ='';
				}

			$res = $babDB->db_query("select ocrt.*, ocet.name as e_name, ocet.id as e_id, count(ocrut.id) as total from ".BAB_OC_ROLES_TBL." ocrt left join ".BAB_OC_ROLES_USERS_TBL." ocrut on ocrut.id_role=ocrt.id left join ".BAB_OC_ENTITIES_TBL." ocet on ocrt.id_entity=ocet.id where ocrt.id_oc='".$ocid."' group by ocrut.id_role");
			while( $row = $babDB->db_fetch_array($res) )
				{
				$this->rcountusers[$row['id']] = $row['total'];
				}
			}

		function getnextentity()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = each($this->entities);
				$this->entity = $arr[1]['name'];
				$this->roles = $arr[1]['roles'];
				$this->countroles = count($this->roles);
				$this->altbg = !$this->altbg;
				$i++;
				return true;
				}
			else
				{
				$i = 0;
				return false;
				}

			}

		function getnextrole(&$skip)
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->countroles)
				{
				$this->roleid = $this->roles[$i][0];
				$this->role = $this->roles[$i][1];
				if( count($this->userroles) > 0  && in_array($this->roleid, $this->userroles))
					{
					$this->rchecked = "checked";
					}
				else
					{
					if( count($this->rcountusers) > 0   && isset($this->rcountusers[$this->roleid]) && $this->rcountusers[$this->roleid] > 0 && $this->roles[$i][2] == 'N')
						{
						$skip =true;
						$i++;
						return true;
						}
					$this->rchecked = "";
					}
				$i++;
				return true;
				}
			else
				{
				$i = 0;
				return false;
				}

			}
		}

	if( !bab_isAccessValid(BAB_OCUPDATE_GROUPS_TBL, $ocid))
		{
		Header("Location: ". $GLOBALS['babUrlScript']."?tg=fltchart&idx=more&ocid=".$ocid."&oeid=".$oeid."&iduser=".$iduser);
		exit;
		}
	$temp = new temp($ocid, $oeid, $iduser);
	$babLittleBody->babecho( bab_printTemplate($temp,"flbchart.html", "userupdate"));
	}


function saveOrgChartEntity($ocid, $name, $description, $oeid, $hsel, $grpid)
	{
	global $babBody, $babDB, $babLittleBody, $ocinfo, $oeinfo;

	if( empty($name))
		{
		$babLittleBody->msgerror = bab_translate("ERROR: You must provide a name")." !";
		return false;
		}

	$babTree = new bab_dbtree(BAB_OC_TREES_TBL, $ocid);
	
	if( !isset($oeid) || $oeid == 0 )
		{
		$idnode = $babTree->add();		
		}
	else
		{
		switch($hsel)
			{
			case 1: /* previous sibling */
				$idnode = $babTree->add(0, $oeinfo['id_node'], false);
				break;
			case 2: /* next sibling */
				$idnode = $babTree->add(0, $oeinfo['id_node']);
				break;
			case 0: /* child */
			default:
				$idnode = $babTree->add($oeinfo['id_node']);
				break;
			}
		}

	if( $idnode != 0)
		{
		if( empty($grpid))
			{
			$grpid = 'none';
			}
		switch($grpid)
			{
			case 'new':
			if( $ocinfo['isprimary'] == 'Y' && $ocinfo['id_group'] == 1)
				{
				include_once $GLOBALS['babInstallPath']."utilit/grpincl.php";
				$idgroup = bab_addGroup($name, $description, 0, 0);
				}
				break;
			case 'none':
				$idgroup = 0;
				break;
			default:
				$idgroup = $grpid;
				break;
			}



		$query = "INSERT into ".BAB_OC_ENTITIES_TBL." 
			(name, description, id_oc, id_node, id_group) 
		values 
			(
				'" .$babDB->db_escape_string($name). "', 
				'" .$babDB->db_escape_string($description). "', 
				'" .$babDB->db_escape_string($ocid)."', 
				'" .$babDB->db_escape_string($idnode)."', 
				'" .$babDB->db_escape_string($idgroup)."'
			)
		";

		$babDB->db_query($query);
		$id = $babDB->db_insert_id();

		if( $grpid != 'none' )
			{
			$babDB->db_query("update ".BAB_GROUPS_TBL." set id_ocentity='".$id."' where id='".$idgroup."'");
			}
		$req = "insert into ".BAB_OC_ROLES_TBL." (name, description, id_oc, id_entity, type, cardinality) values ('" .bab_translate("Superior"). "', '', '".$ocid."', '".$id."', '1', 'N')";
		$babDB->db_query($req);
		$req = "insert into ".BAB_OC_ROLES_TBL." (name, description, id_oc, id_entity, type, cardinality) values ('" .bab_translate("Temporary employee"). "', '', '".$ocid."', '".$id."', '2', 'N')";
		$babDB->db_query($req);
		$req = "insert into ".BAB_OC_ROLES_TBL." (name, description, id_oc, id_entity, type, cardinality) values ('" .bab_translate("Members"). "', '', '".$ocid."', '".$id."', '3', 'Y')";
		$babDB->db_query($req);
		$idrole = $babDB->db_insert_id();
		if( $grpid != 'none' && $grpid !='new' )
			{
			if( $ocinfo['isprimary'] == 'Y' && $ocinfo['id_group'] == 1)
				{
				$res = $babDB->db_query("select det.id from ".BAB_DBDIR_ENTRIES_TBL." det left join ".BAB_USERS_GROUPS_TBL." ugt on det.id_user=ugt.id_object where ugt.id_group='".$grpid."' and det.id_directory='0'");
				while($arr = $babDB->db_fetch_array($res))
					{
					$res2 = $babDB->db_query("select ocrut.id from  ".BAB_OC_ROLES_USERS_TBL." ocrut left join ".BAB_OC_ROLES_TBL." ocrt on ocrut.id_role=ocrt.id where ocrt.id_oc='".$ocid."' and  ocrut.id_user='".$arr['id']."' and ocrut.isprimary='Y'");
					if( $res2 && $babDB->db_num_rows($res2) > 0 )
					{
						$isprimary = 'N';
					}
					else
					{
						$isprimary = 'Y';
					}

					$babDB->db_query("insert into ".BAB_OC_ROLES_USERS_TBL." (id_role, id_user, isprimary) values ('".$idrole."','".$arr['id']."','".$isprimary."')");
					}
				}
			}
		Header("Location: ". $GLOBALS['babUrlScript']."?tg=flbchart&rf=1&idx=mode&ocid=".$ocid."&oeid=".$id);
		return true;
		}
	else
		{
		return false;
		}
	}

function updateOrgChartEntity($ocid, $name, $description, $oeid, $entityTypes = array())
	{
	global $babBody, $babDB, $babLittleBody, $oeinfo;

	if( empty($name))
		{
		$babLittleBody->msgerror = bab_translate("ERROR: You must provide a name")." !";
		return false;
		}

	$babDB->db_query("UPDATE ".BAB_OC_ENTITIES_TBL." set 
		name='".$babDB->db_escape_string($name)."', 
		description='".$babDB->db_escape_string($description)."' 
		WHERE id='".$oeid."'
	");
	
	

	$sql = 'DELETE FROM ' . BAB_OC_ENTITIES_ENTITY_TYPES_TBL . ' WHERE id_entity = ' . $babDB->quote($oeid);
	$babDB->db_query($sql);

	foreach ($entityTypes as $entityTypeId) {
		$sql = 'INSERT INTO ' . BAB_OC_ENTITIES_ENTITY_TYPES_TBL . '(id_entity, id_entity_type) VALUES (' . $babDB->quote($oeid) . ',' . $babDB->quote($entityTypeId) . ')';
		$babDB->db_query($sql);
	}


	Header("Location: ". $GLOBALS['babUrlScript']."?tg=flbchart&rf=1&idx=mode&ocid=".$ocid."&oeid=".$oeid);
	}

function removeOrgChartEntity($ids, $all)
{
	global $babDB, $ocinfo;

	$oeids = is_array($ids)? implode(',', $ids) : $ids;
	$res = $babDB->db_query("select id from ".BAB_OC_ROLES_TBL." where id_entity IN (".$oeids.")");
	while( $arr = $babDB->db_fetch_array($res))
	{
		$row[] = $arr['id'];
	}
	if( count($row) > 0 )
		{
		$babDB->db_query("delete from ".BAB_OC_ROLES_USERS_TBL." where id_role IN (".implode(',', $row).")");
		}
	$babDB->db_query("delete from ".BAB_OC_ROLES_TBL." where id_entity IN (".$oeids.")");

	if( !empty($all) && $ocinfo['isprimary'] == 'Y' && $ocinfo['id_group'] == 1)
	{
	include_once $GLOBALS['babInstallPath']."utilit/grpincl.php";
	$res = $babDB->db_query("select id_group from ".BAB_OC_ENTITIES_TBL." where id IN (".$oeids.") AND id_group>'0'");
	$all = 'N'; /* Forced to No for moment. DON'T CHANGE THIS LINE */
	while( $arr = $babDB->db_fetch_array($res))
		{
		$babDB->db_query("update ".BAB_GROUPS_TBL." set id_ocentity='0' where id='".$arr['id_group']."'");
		}
	}
	$babDB->db_query("delete from ".BAB_OC_ENTITIES_TBL." where id IN (".$oeids.")");
	$babDB->db_query("delete from ".BAB_VAC_PLANNING_TBL." where id_entity IN (".$oeids.")");
}

function confirmDeleteOrgChartEntity($ocid, $oeid, $what)
	{
	global $babBody, $babDB, $babLittleBody, $oeinfo;
	$all = 'N'; /* Forced to No for moment. DON'T CHANGE THIS LINE */

	list($idnode) = $babDB->db_fetch_row($babDB->db_query("select id_node from ".BAB_OC_ENTITIES_TBL." where id='".$oeid."'"));

	$babTree = new bab_dbtree(BAB_OC_TREES_TBL, $ocid);
	switch($what)
		{
		case 2:
			/* only children */
			$row = $babTree->getChilds($idnode);
			if( $row )
			{
				for($i=0; $i< count($row); $i++ )
					{
						$arr = $babTree->getChilds($row[$i]['id'], 1);
						if( $arr )
						{
							$rr = array();
							for($j=0; $j< count($arr); $j++ )
								{
								$rr[] = $arr[$j]['id'];
								}

							$rr[] = $row[$i]['id'];

							if( $babTree->removeTree($row[$i]['id']))
								{
								$res = $babDB->db_query("select id from ".BAB_OC_ENTITIES_TBL." where id_node IN (".implode(',', $rr).")");
								while( $arr = $babDB->db_fetch_array($res))
								{
									$arroe[] = $arr['id'];
								}
								removeOrgChartEntity($arroe, $all);
								}
						}
						elseif( $babTree->remove($row[$i]['id']) )
						{
							list($idoe) = $babDB->db_fetch_row($babDB->db_query("select id from ".BAB_OC_ENTITIES_TBL." where id_node='".$row[$i]['id']."'"));
							removeOrgChartEntity($idoe, $all);
						}
					}

			}
			break;
		case 1:
			/* entity and children */
			$arr = $babTree->getChilds($idnode, 1);
			if( $arr )
			{
				$rr = array();
				for($i=0; $i< count($arr); $i++ )
					{
					$rr[] = $arr[$i]['id'];
					}

				$rr[] = $idnode;
				if( $babTree->removeTree($idnode) )
					{
					$res = $babDB->db_query("select id from ".BAB_OC_ENTITIES_TBL." where id_node IN (".implode(',', $rr).")");
					while( $arr = $babDB->db_fetch_array($res))
					{
						$arroe[] = $arr['id'];
					}
					removeOrgChartEntity($arroe, $all);
					}
			}
			elseif( $babTree->remove($idnode) )
			{
				removeOrgChartEntity($oeid, $all);
			}
			break;
		case 0: /* only entity */
		default:
			if( $babTree->remove($idnode) )
			{
				removeOrgChartEntity($oeid, $all);
			}
			break;
		}
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=flbchart&rf=1&ocid=".$ocid);
	}

function confirmPermuteOrgChartEntity($ocid, $oeid, $permid)
{
	global $babBody, $babDB, $babLittleBody, $oeinfo;

	if( $oeid == $permid )
	{
		return true;
	}
	
	$res = $babDB->db_query("select id_node from ".BAB_OC_ENTITIES_TBL." where id='".$oeid."' and id_oc='".$ocid."'");
	if( $res && $babDB->db_num_rows($res) == 1)
	{
		$arr = $babDB->db_fetch_array($res);
		$res = $babDB->db_query("select id_node from ".BAB_OC_ENTITIES_TBL." where id='".$permid."' and id_oc='".$ocid."'");
		if( $res && $babDB->db_num_rows($res) == 1)
		{
			$row = $babDB->db_fetch_array($res);
			$babDB->db_query("update ".BAB_OC_ENTITIES_TBL." set id_node='".$row['id_node']."' where id='".$oeid."'");
			$babDB->db_query("update ".BAB_OC_ENTITIES_TBL." set id_node='".$arr['id_node']."' where id='".$permid."'");
			Header("Location: ". $GLOBALS['babUrlScript']."?tg=flbchart&rf=1&ocid=".$ocid);
			return true;
		}
		else
		{
			return false;
		}
	}
	else
	{
		return false;
	}
}

function confirmMoveOrgChartEntity($ocid, $oeid, $what, $pid, $as)
	{
	global $babBody, $babDB, $babLittleBody, $oeinfo;

	list($idnode) = $babDB->db_fetch_row($babDB->db_query("select id_node from ".BAB_OC_ENTITIES_TBL." where id='".$oeid."'"));
	list($pid) = $babDB->db_fetch_row($babDB->db_query("select id_node from ".BAB_OC_ENTITIES_TBL." where id='".$pid."'"));

	$babTree = new bab_dbtree(BAB_OC_TREES_TBL, $ocid);
	switch($what)
		{
		case 1: /* entity and children */
			if( $as == 1 )
			{
				$babTree->moveTree($idnode, 0, $pid, false);
			}
			else if ( $as == 2 )
			{
				$babTree->moveTree($idnode, 0, $pid);
			}
			else
			{
			$babTree->moveTree($idnode, $pid);
			}
			break;
		case 0: /* only entity */
		default:
			if( $as == 1 ) /* as previous sibling */
			{
				$babTree->move($idnode, 0, $pid, false);
			}
			else if ( $as == 2 ) /* as next sibling */
			{
				$babTree->move($idnode, 0, $pid);
			}
			else /* as child */
			{
			$babTree->move($idnode, $pid);
			}
			break;
		}
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=flbchart&rf=1&ocid=".$ocid);
	}

function saveOrgChartRole($ocid, $name, $description, $oeid, $cardinality)
	{
	global $babBody, $babDB, $babLittleBody, $oeinfo;

	if( empty($name))
		{
		$babLittleBody->msgerror = bab_translate("ERROR: You must provide a name")." !";
		return false;
		}

	
	$req = "INSERT INTO ".BAB_OC_ROLES_TBL." (name, description, id_oc, id_entity, type, cardinality) 
	VALUES  
	(
		'" .$babDB->db_escape_string($name). "',
		'" . $babDB->db_escape_string($description). "', 
		'".$babDB->db_escape_string($ocid)."',
		'".$babDB->db_escape_string($oeid)."',
		'0',
		'".$babDB->db_escape_string($cardinality)."'
	)";

	$babDB->db_query($req);
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=flbchart&idx=listr&ocid=".$ocid."&oeid=".$oeid);
	return true;
	}

function updateOrgChartRole($ocid, $name, $description, $oeid, $orid, $cardinality)
	{
	global $babBody, $babDB, $babLittleBody, $oeinfo, $orinfo;

	if( empty($name))
		{
		$babLittleBody->msgerror = bab_translate("ERROR: You must provide a name")." !";
		return false;
		}

	if( !empty($cardinality) && $orinfo['cardinality'] != $cardinality )
		{
		$arr = $babDB->db_fetch_array($babDB->db_query("select count(id) as total from ".BAB_OC_ROLES_USERS_TBL." where id_role='".$babDB->db_escape_string($orid)."'"));
		if( $arr['total'] > 1 && $cardinality == 'N')
			{
			$babLittleBody->msgerror = bab_translate("ERROR: More than one user are associated with this role")." !";
			return false;
			}
		}

	
	$req = "UPDATE ".BAB_OC_ROLES_TBL." set 
	name='".$babDB->db_escape_string($name)."', 
	description='".$babDB->db_escape_string($description)."'
	";
	if( !empty($cardinality))
		{
		$req .= ", cardinality='".$babDB->db_escape_string($cardinality)."'";
		}
	$req .= " where id='".$babDB->db_escape_string($orid)."'";
	$babDB->db_query($req);
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=flbchart&idx=listr&ocid=".$ocid."&oeid=".$oeid."&ltf=1");
	return true;
	}

function addUserOrgChartRole($ocid, $oeid, $orid, $iduser)
{
	global $babBody, $babDB, $babLittleBody, $ocinfo, $oeinfo;

	$res = $babDB->db_query("select * from  ".BAB_OC_ROLES_USERS_TBL." where id_role='".$orid."' and  id_user='".$iduser."'");
	if( $res && $babDB->db_num_rows($res) > 0 )
	{
		$babLittleBody->msgerror = bab_translate("User already exist!");
		return;
	}

	$res = $babDB->db_query("select ocrut.id from  ".BAB_OC_ROLES_USERS_TBL." ocrut left join ".BAB_OC_ROLES_TBL." ocrt on ocrut.id_role=ocrt.id where ocrt.id_oc='".$ocid."' and  ocrut.id_user='".$iduser."' and ocrut.isprimary='Y'");
	if( $res && $babDB->db_num_rows($res) > 0 )
	{
		$isprimary = 'N';
	}
	else
	{
		$isprimary = 'Y';
	}

	if( $ocinfo['isprimary'] == 'Y' && $ocinfo['id_group'] == 1 && $oeinfo['id_group'] != 0)
	{
		list($idouser) = $babDB->db_fetch_row($babDB->db_query("select id_user from ".BAB_DBDIR_ENTRIES_TBL." where id='".$iduser."'"));
		bab_addUserToGroup($idouser, $oeinfo['id_group'], false);
	}

	$req = "insert into ".BAB_OC_ROLES_USERS_TBL." (id_role, id_user, isprimary) values ('".$orid."','".$iduser."','".$isprimary."')";
	$babDB->db_query($req);
}

function delUserOrgChartRole($ocid, $oeid, $ocfid)
{
	global $babBody, $babDB, $babLittleBody, $ocinfo, $oeinfo;
	for($i= 0; $i < count($ocfid); $i++ )
	{
	list($idduser, $isprimary) = $babDB->db_fetch_row($babDB->db_query("select id_user, isprimary from ".BAB_OC_ROLES_USERS_TBL." where id='".$ocfid[$i]."'"));
	$babDB->db_query("delete from ".BAB_OC_ROLES_USERS_TBL." where id='".$ocfid[$i]."'");
	$babDB->db_query("delete from ".BAB_VAC_PLANNING_TBL." where id_user='".$idduser."'");

	if( $isprimary == 'Y' )
		{		
		$res = $babDB->db_query("select ocrut.id from  ".BAB_OC_ROLES_USERS_TBL." ocrut left join ".BAB_OC_ROLES_TBL." ocrt on ocrut.id_role=ocrt.id where ocrt.id_oc='".$ocid."' and  ocrut.id_user='".$idduser."'");
		if( $res && $babDB->db_num_rows($res) > 0 )
			{
			$k = 0; 
			while( $arr = $babDB->db_fetch_array($res))
				{
				if( $k == 0 ) //user must have a primary role, use the first
					{
					$babDB->db_query("update ".BAB_OC_ROLES_USERS_TBL." set isprimary='Y' where id='".$arr['id']."'");
					}
				else
					{
					$babDB->db_query("update ".BAB_OC_ROLES_USERS_TBL." set isprimary='N' where id='".$arr['id']."'");
					}
				$k++;
				}
			}
		}

	if( $ocinfo['isprimary'] == 'Y' && $ocinfo['id_group'] == 1 && $oeinfo['id_group'] != 0)
		{
		list($total) = $babDB->db_fetch_row($babDB->db_query("select count(orut.id) as total from ".BAB_OC_ROLES_USERS_TBL." orut left join ".BAB_OC_ROLES_TBL." ort on ort.id=orut.id_role left join ".BAB_OC_ENTITIES_TBL." oct on oct.id=ort.id_entity where orut.id_user='".$idduser."' and ort.id_entity='".$oeid."'"));
		if( !$total )
			{
			list($iduser) = $babDB->db_fetch_row($babDB->db_query("select id_user from ".BAB_DBDIR_ENTRIES_TBL." where id='".$idduser."'"));
			$res = $babDB->db_query("delete from ".BAB_USERS_GROUPS_TBL." where id_group='".$oeinfo['id_group']."' and id_object='".$iduser."'");
			}
		}
	}
}

function delOrgChartRoles($ocid, $oeid, $ocfid)
{
	global $babBody, $babDB, $babLittleBody;
	for($i= 0; $i < count($ocfid); $i++ )
	{
	$res1 = $babDB->db_query("select * from ".BAB_OC_ROLES_TBL." where id='".$ocfid[$i]."'");
	if( $res1 && $babDB->db_num_rows($res1) > 0 )
		{
		$row = $babDB->db_fetch_array($res1);
		if( $row['type'] == 0 )
			{
			$babDB->db_query("delete from ".BAB_OC_ROLES_TBL." where id='".$ocfid[$i]."'");
			$res = $babDB->db_query("select id from ".BAB_OC_ROLES_USERS_TBL." where id_role='".$ocfid[$i]."'");
			while( $arr = $babDB->db_fetch_array($res))
				{
				delUserOrgChartRole($ocid, $oeid, array($arr['id']));
				}
			}
		}
	}
}

function updateOrgChartRoleUser($ocid, $oeid, $iduser, $ruid, $userroles)
{
	global $babDB;

	if( bab_isAccessValid(BAB_OCUPDATE_GROUPS_TBL, $ocid))
	{
		$arr = explode(',', $userroles);
		if( count($ruid) > 0 )
		{
			for( $i = 0; $i < count($ruid); $i++ )
			{
			if( count($arr) == 0  || !in_array($ruid[$i], $arr))
				{
				addUserOrgChartRole($ocid, $oeid, $ruid[$i], $iduser);
				}
			}
		}

		if( count($arr) > 0 )
		{
			for( $i = 0; $i < count($arr); $i++ )
			{
			if( count($ruid) == 0  || !in_array($arr[$i], $ruid))
				{
				list($idrole) = $babDB->db_fetch_row($babDB->db_query("select id from ".BAB_OC_ROLES_USERS_TBL." where id_role='".$arr[$i]."' and id_user='".$iduser."'"));
				delUserOrgChartRole($ocid, $oeid, array($idrole));
				}
			}
		}
	}

	list($total) = $babDB->db_fetch_row($babDB->db_query("select count(orut.id) as total from ".BAB_OC_ROLES_USERS_TBL." orut left join ".BAB_OC_ROLES_TBL." ort on ort.id=orut.id_role left join ".BAB_OC_ENTITIES_TBL." oct on oct.id=ort.id_entity where orut.id_user='".$iduser."' and ort.id_entity='".$oeid."'"));
	if($total)
	{
		Header("Location: ". $GLOBALS['babUrlScript']."?tg=fltchart&idx=more&ocid=".$ocid."&oeid=".$oeid."&iduser=".$iduser."&rf=1&ltf=1");
	}
	else
	{
	list($iduser) = $babDB->db_fetch_row($babDB->db_query("select orut.id_user from ".BAB_OC_ROLES_USERS_TBL." orut left join ".BAB_OC_ROLES_TBL." ort on ort.id=orut.id_role left join ".BAB_OC_ENTITIES_TBL." oct on oct.id=ort.id_entity where ort.id_entity='".$oeid."' limit 0,1"));
	if( $iduser)
		{
		Header("Location: ". $GLOBALS['babUrlScript']."?tg=fltchart&idx=detr&ocid=".$ocid."&oeid=".$oeid."&iduser=".$iduser."&rf=1&ltf=1");
		}
	else
		{
		Header("Location: ". $GLOBALS['babUrlScript']."?tg=fltchart&idx=detr&ocid=".$ocid."&oeid=".$oeid."&iduser=&rf=1&ltf=1");
		}
	}
}

/* main */
$babLittleBody = new babLittleBody();
$babLittleBody->frrefresh = isset($rf)? $rf: false;
$babLittleBody->fltrefresh = isset($ltf)? $ltf: false;
$access = false;
if( bab_isAccessValid(BAB_OCUPDATE_GROUPS_TBL, $ocid))
{
	$ocinfo = $babDB->db_fetch_array($babDB->db_query("select oct.*, ddt.name as dir_name, ddt.id_group from ".BAB_ORG_CHARTS_TBL." oct LEFT JOIN ".BAB_DB_DIRECTORIES_TBL." ddt on oct.id_directory=ddt.id where oct.id='".$ocid."'"));
	if( $ocinfo['edit'] == 'Y' && $ocinfo['edit_author'] == $BAB_SESS_USERID)
	{
		$access = true;
	}
}

if( !$access)
{
	$babLittleBody->msgerror = bab_translate("Access denied");
	return;
}

if( isset($oeid) && $oeid != 0)
{
$oeinfo = $babDB->db_fetch_array($babDB->db_query("select oet.*, ctt.id_parent from ".BAB_OC_ENTITIES_TBL." oet left join ".BAB_OC_TREES_TBL." ctt on ctt.id=oet.id_node where oet.id='".$oeid."'"));
}
else
{
	$oeid = 0;
}
chart_session_oeid($ocid);

if( isset($orid) && $orid != 0)
{
$orinfo = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_OC_ROLES_TBL." where id='".$orid."'"));
}
else
{
	$orid = 0;
}

if(!isset($idx))
	{
	$idx = "adde";
	}

if( isset($addoce) )
{
	switch($addoce)
	{
		case "addoce":
			if( !isset($grpid)) { $grpid ='';}
			if( !saveOrgChartEntity($ocid, $fname, $description, $oeid, $hsel, $grpid))
			{
			$idx = "adde";
			}
			break;

		case "modoce":
				$entityTypes = array_keys(bab_rp('entity_type', array()));
				if( !updateOrgChartEntity($ocid, $fname, $description, $oeid, $entityTypes))
				{
				$idx = "mode";
				}
			break;
	}
}
else if( isset($addocr) )
{
	switch($addocr)
	{
		case "addocr":
			if( !saveOrgChartRole($ocid, $fname, $description, $oeid, $cardinality))
			{
			$idx = "addr";
			}
			break;

	}
}
else if( isset($modocr) )
{
	switch($modocr)
	{
		case "modocr":
			if( !isset($cardinality)) {$cardinality='';}
			if( !updateOrgChartRole($ocid, $fname, $description, $oeid, $orid, $cardinality))
			{
			$idx = "modr";
			}
			break;

	}
}
else if( isset($deloce) )
{
	switch($deloce)
	{
		case "deloce":
			if( !confirmDeleteOrgChartEntity($ocid, $oeid, $what))
			{
			$idx = "delr";
			}
			break;

	}
}
else if( isset($movoce) )
{
	switch($movoce)
	{
		case "movoce":
			if( !confirmMoveOrgChartEntity($ocid, $oeid, $what, $parentid, $as))
			{
			$idx = "move";
			}
			break;
		case "peroce":
			if( !confirmPermuteOrgChartEntity($ocid, $oeid, $permid))
			{
			$idx = "move";
			}
			break;

	}
}else if( isset($updru) && $updru == "updru" )
{
	if( !isset($ruid)) { $ruid = array();}
	updateOrgChartRoleUser($ocid, $oeid, $iduser, $ruid, $userroles);
}


switch($idx)
	{
	case "updu":
		$babLittleBody->title = '';
		$babLittleBody->addItemMenu("detr", bab_translate("Detail"), $GLOBALS['babUrlScript']."?tg=fltchart&idx=detr&ocid=".$ocid."&oeid=".$oeid."&iduser=".$iduser);
		$babLittleBody->addItemMenu("more", bab_translate("Roles"), $GLOBALS['babUrlScript']."?tg=fltchart&idx=more&ocid=".$ocid."&oeid=".$oeid."&iduser=".$iduser);
		$babLittleBody->addItemMenu("updu", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=flbchart&idx=updu&ocid=".$ocid."&oeid=".$oeid."&iduser=".$iduser);
		$babLittleBody->setCurrentItemMenu($idx);
		viewOrgChartRoleUpdate($ocid, $oeid, $iduser);
		break;
	case "delocf":
		$ocfid = bab_rp('ocfid', array());
		delOrgChartRoles($ocid, $oeid, $ocfid);
		Header("Location: ". $GLOBALS['babUrlScript']."?tg=flbchart&idx=listr&ocid=".$ocid."&oeid=".$oeid."&ltf=1");
		break;
	case "delocu":
		delUserOrgChartRole($ocid, $oeid, $ocfid);
		Header("Location: ". $GLOBALS['babUrlScript']."?tg=flbchart&idx=users&ocid=".$ocid."&oeid=".$oeid."&orid=".$orid."&rf=1&ltf=1");
		break;
	case "addur":
		addUserOrgChartRole($ocid, $oeid, $orid, $iduser);
		Header("Location: ". $GLOBALS['babUrlScript']."?tg=flbchart&idx=users&ocid=".$ocid."&oeid=".$oeid."&orid=".$orid."&rf=1&ltf=1");
		/* no break */
	case "users":
		$babLittleBody->title = isset($oeinfo['name'])? $oeinfo['name']:'';
		$babLittleBody->addItemMenu("listr", bab_translate("Roles"), $GLOBALS['babUrlScript']."?tg=flbchart&idx=listr&ocid=".$ocid."&oeid=".$oeid);
		$babLittleBody->addItemMenu("modr", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=flbchart&idx=modr&ocid=".$ocid."&oeid=".$oeid."&orid=".$orid);
		$babLittleBody->addItemMenu("users", bab_translate("Users"), $GLOBALS['babUrlScript']."?tg=flbchart&idx=users&ocid=".$ocid."&oeid=".$oeid."&orid=".$orid);
		$babLittleBody->setCurrentItemMenu($idx);
		usersOrgChartRole($ocid, $oeid, $orid);
		break;
	case "modr":
		$babLittleBody->title = isset($oeinfo['name'])? $oeinfo['name']:'';
		$babLittleBody->addItemMenu("listr", bab_translate("Roles"), $GLOBALS['babUrlScript']."?tg=flbchart&idx=listr&ocid=".$ocid."&oeid=".$oeid);
		$babLittleBody->addItemMenu("modr", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=flbchart&idx=modr&ocid=".$ocid."&oeid=".$oeid."&orid=".$orid);
		$babLittleBody->addItemMenu("users", bab_translate("Users"), $GLOBALS['babUrlScript']."?tg=flbchart&idx=users&ocid=".$ocid."&oeid=".$oeid."&orid=".$orid);
		$babLittleBody->setCurrentItemMenu($idx);
		if(!isset($fname)) { $fname ='';}  
		if(!isset($description)) { $description ='';}  
		modifyOrgChartRole($ocid, $oeid, $fname, $description, $orid);
		break;
	case "addr":
		$babLittleBody->title = isset($oeinfo['name'])? $oeinfo['name']:'';
		$babLittleBody->addItemMenu("listr", bab_translate("Roles"), $GLOBALS['babUrlScript']."?tg=flbchart&idx=listr&ocid=".$ocid."&oeid=".$oeid);
		$babLittleBody->addItemMenu("addr", bab_translate("Add"), $GLOBALS['babUrlScript']."?tg=flbchart&idx=addr&ocid=".$ocid."&oeid=".$oeid);
		$babLittleBody->setCurrentItemMenu($idx);
		if(!isset($fname)) { $fname ='';}  
		if(!isset($description)) { $description ='';}  
		addOrgChartRole($ocid, $oeid, $fname, $description);
		break;
	case "listr":
		$babLittleBody->title = isset($oeinfo['name'])? $oeinfo['name']:'';
		$babLittleBody->addItemMenu("listr", bab_translate("Roles"), $GLOBALS['babUrlScript']."?tg=flbchart&idx=listr&ocid=".$ocid."&oeid=".$oeid);
		$babLittleBody->addItemMenu("addr", bab_translate("Add"), $GLOBALS['babUrlScript']."?tg=flbchart&idx=addr&ocid=".$ocid."&oeid=".$oeid);
		$babLittleBody->setCurrentItemMenu($idx);
		listOrgChartRoles($ocid, $oeid);
		break;
	case "move":
		$babLittleBody->title = isset($oeinfo['name'])? $oeinfo['name']:'';
		if( $oeid != 0 )
			{
			$babLittleBody->addItemMenu("mode", bab_translate("Entity"), $GLOBALS['babUrlScript']."?tg=flbchart&idx=mode&ocid=".$ocid."&oeid=".$oeid);
			$babLittleBody->addItemMenu("move", bab_translate("Move"), $GLOBALS['babUrlScript']."?tg=flbchart&idx=move&ocid=".$ocid."&oeid=".$oeid);
			}
		$babLittleBody->addItemMenu("adde", bab_translate("Add"), $GLOBALS['babUrlScript']."?tg=flbchart&idx=adde&ocid=".$ocid."&oeid=".$oeid);
		$babLittleBody->setCurrentItemMenu($idx);
		moveOrgChartEntity($ocid, $oeid);
		break;
	case "dele":
		$babLittleBody->title = isset($oeinfo['name'])? $oeinfo['name']:'';
		$babLittleBody->addItemMenu("dele", bab_translate("Delete"), $GLOBALS['babUrlScript']."?tg=flbchart&idx=dele&ocid=".$ocid."&oeid=".$oeid);
		$babLittleBody->setCurrentItemMenu($idx);
		deleteOrgChartEntity($ocid, $oeid);
		break;
	case "mode":
		$babLittleBody->title = isset($oeinfo['name'])? $oeinfo['name']:'';
		if( $oeid != 0 )
			{
			$babLittleBody->addItemMenu("mode", bab_translate("Entity"), $GLOBALS['babUrlScript']."?tg=flbchart&idx=mode&ocid=".$ocid."&oeid=".$oeid);
			if( isset($oeinfo['id_parent']) && $oeinfo['id_parent'] != 0 )
				{
				$babLittleBody->addItemMenu("move", bab_translate("Move"), $GLOBALS['babUrlScript']."?tg=flbchart&idx=move&ocid=".$ocid."&oeid=".$oeid);
				}
			}
		$babLittleBody->addItemMenu("adde", bab_translate("Add"), $GLOBALS['babUrlScript']."?tg=flbchart&idx=adde&ocid=".$ocid."&oeid=".$oeid);
		$babLittleBody->setCurrentItemMenu($idx);
		modifyOrgChartEntity($ocid, $oeid);
		break;
	case "adde":
	default:
		$babLittleBody->title = isset($oeinfo['name'])? $oeinfo['name']:'';
		if( $oeid != 0 )
			{
			$babLittleBody->addItemMenu("mode", bab_translate("Entity"), $GLOBALS['babUrlScript']."?tg=flbchart&idx=mode&ocid=".$ocid."&oeid=".$oeid);
			if( isset($oeinfo['id_parent']) && $oeinfo['id_parent'] != 0 )
				{
				$babLittleBody->addItemMenu("move", bab_translate("Move"), $GLOBALS['babUrlScript']."?tg=flbchart&idx=move&ocid=".$ocid."&oeid=".$oeid);
				}
			}
		$babLittleBody->addItemMenu("adde", bab_translate("Add"), $GLOBALS['babUrlScript']."?tg=flbchart&idx=adde&ocid=".$ocid."&oeid=".$oeid);
		$babLittleBody->setCurrentItemMenu($idx);
		if( !isset($fname)) { $fname ='';}
		if( !isset($description)) { $description ='';}
		addOrgChartEntity($ocid, $oeid, $fname, $description);
		break;
	}
printFlbChartPage();
exit;
?>