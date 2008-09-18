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
include_once $babInstallPath."utilit/tree.php";

define("ORG_MAX_REQUESTS_LIST", 100);


function displayChart($ocid, $oeid, $update, $iduser, $disp='')
	{
	global $babBody;
	class temp
		{
		function temp($ocid, $oeid, $update, $iduser, $disp)
			{
			global $babDB, $ocinfo;
			$this->ocid = $ocid;
			$this->update = $update;
			$this->disp = $disp;
			$this->coeid = $oeid;

			$this->roles = bab_translate("Roles");
			$this->delete = bab_translate("Delete");
			$this->startnode = bab_translate("Start");
			$this->closenode = bab_translate("Close");
			$this->opennode = bab_translate("Open");

			$this->babTree  = new bab_arraytree(BAB_OC_TREES_TBL, $ocid, "", $ocinfo['id_first_node']);

			$this->closednodes = array();
			$arr= explode(',', $ocinfo['id_closed_nodes'] );
			for( $i=0; $i < count($arr); $i++ )
				{
				if( $this->babTree->hasChildren($arr[$i]) )
					{
					$this->babTree->removeChilds($arr[$i]);
					$this->closednodes[] = $arr[$i];
					}
				}
			$this->arr = array();
			reset($this->babTree->nodes);
			$this->maxlevel = 0;
			while( $row=each($this->babTree->nodes) ) 
				{
				$this->arr[$row[1]['id']] = $row[1]['lf'];
				if( $row[1]['level'] > $this->maxlevel )
					{
					$this->maxlevel = $row[1]['level'];
					}
				}
			asort($this->arr);
			reset($this->arr);
			$this->arr = array_keys($this->arr);
			if( $update )
				{
				$this->updateurlb = $GLOBALS['babUrlScript']."?tg=flbchart&rf=0&ocid=".$ocid."&oeid=";
				$this->updateurlt = $GLOBALS['babUrlScript']."?tg=fltchart&rf=0&ocid=".$ocid."&oeid=";
				}
			else
				{
				$this->updateurlb = $GLOBALS['babUrlScript']."?tg=fltchart&rf=0&ocid=".$ocid."&oeid=";
				$this->updateurlt = $GLOBALS['babUrlScript']."?tg=fltchart&rf=0&ocid=".$ocid."&oeid=";
				}
			$this->currentoe = $oeid."&iduser=".$iduser;
			$this->maxlevel += 1;
			
			$this->res = $babDB->db_query("select ocet.*, ocet.id as identity, ocut.id_user, det.sn, det.givenname from ".BAB_OC_ENTITIES_TBL." ocet LEFT JOIN ".BAB_OC_TREES_TBL." octt on octt.id=ocet.id_node LEFT JOIN ".BAB_OC_ROLES_TBL." ocrt on ocrt.id_oc=ocet.id_oc and ocrt.id_entity=ocet.id and ocrt.type='1' LEFT JOIN ".BAB_OC_ROLES_USERS_TBL." ocut on ocut.id_role=ocrt.id LEFT JOIN ".BAB_DBDIR_ENTRIES_TBL." det on det.id=ocut.id_user where ocet.id_oc='".$this->ocid."' order by octt.lf asc");
			
			$this->count = $babDB->db_num_rows($this->res);
			$this->javascript = bab_printTemplate($this, "frchart.html", "orgjavascript");
			$this->padarr = array();
			}

		function getnext(&$skip)
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->count)
				{
				$row = $babDB->db_fetch_array($this->res);
				if( isset($row['sn'] ))
					{
					$this->superior = bab_composeUserName($row['givenname'],$row['sn']);
					$this->superiorurl = $GLOBALS['babUrlScript']."?tg=fltchart&idx=detr&ocid=".$this->ocid."&oeid=".$row['identity']."&iduser=".$row['id_user'];
					}
				else
					{
					$this->superior = '';
					}
				if( !in_array($row['id_node'], $this->arr))
					{
					$skip = true;
					$i++;
					return true;
					}
				if ( count($this->padarr) > 0 )
					{ 
					while ($this->babTree->getRightValue($this->padarr[count($this->padarr)-1]) < $this->babTree->getRightValue($row['id_node']))
					   { 
					   array_pop($this->padarr);
					   } 
					} 
				//$this->entity = $row['name']."(".$row['id'].")";
				$this->entity = $row['name'];
				if( !empty($row['description']))
					{
					$this->description = "( ".$row['description']." )";
					}
				else
					{
					$this->description = "";
					}
				$this->oeid = $row['id'];
				$this->padarr[] = $row['id_node'];
				$this->colspan = $this->maxlevel - count($this->padarr) + 1;
				if( $this->babTree->hasChildren($row['id_node']))
					{
					$this->closenodeurl =  $GLOBALS['babUrlScript']."?tg=frchart&idx=closen&disp=".$this->disp."&ocid=".$this->ocid."&oeid=".$this->oeid;
					$this->parent = 1;
					}
				else
					{
					$this->parent = 0;
					}
				if($this->arr[0] == $row['id_node'])
					{
					$this->first = 1;
					if (count($this->arr) == 1)
						{
						$this->leaf = 1;
						}
					else
						{
						$this->leaf = 0;
						}
					}
				else
					{
					$this->first = 0;
					if( $this->babTree->getLastChild($this->babTree->getParentId($row['id_node'])) == $row['id_node'] )
						{
						$this->leaf = 1;
						}
					else
						{
						$this->leaf = 0;
						}
					}

				if( $this->coeid == $this->oeid )
					{
					$this->current = true;
					}
				else
					{
					$this->current = false;
					}

				if( $this->arr[0] == $row['id_node'] && $this->babTree->getParentId($row['id_node']) !=  0 )
					{
					$this->bupbutton = true;
					}
				else
					{
					$this->bupbutton = false;
					}

				if( count($this->closednodes) > 0 && in_array($row['id_node'], $this->closednodes))
					{
					$this->bparent = true;
					$this->opennodeurl =  $GLOBALS['babUrlScript']."?tg=frchart&idx=openn&disp=".$this->disp."&ocid=".$this->ocid."&oeid=".$this->oeid;
					}
				else
					{
					$this->bparent = false;
					}
				$this->listrurl =  $GLOBALS['babUrlScript']."?tg=fltchart&ocid=".$this->ocid."&oeid=".$this->oeid;
				$this->startnodeurl =  $GLOBALS['babUrlScript']."?tg=frchart&disp=".$this->disp."&idx=startn&ocid=".$this->ocid."&oeid=".$this->oeid;
				$this->startupnodeurl =  $GLOBALS['babUrlScript']."?tg=frchart&disp=".$this->disp."&idx=startup&ocid=".$this->ocid."&oeid=".$this->oeid;
				$this->nodecell = bab_printTemplate($this, "frchart.html", "nodecell");
				$i++;
				return true;
				}
			else
				return false;
			}

		function getnextpad()
			{
			global $babDB;
			static $i = 0;
			if( $i < count($this->padarr) -1)
				{
				if($this->babTree->getNextSibling($this->padarr[$i]))
					{
					$this->vert = 1;
					}
				else
					{
					$this->vert = 0;
					}
				$i++;
				return true;
				}
			else
				{
				$i=0;
				return false;
				}
			}
		}

	if( empty($disp))
		{
		$template = "oedirectorylist_disp2";
		$disp ='disp2';
		}
	else
		{
		switch ($disp)
			{
			case "disp2":
				$template = "oedirectorylist_disp2";
				break;
			case "disp3":
				$template = "oedirectorylist_disp2";
				break;
			case "disp1":
			default:
				$template = "oedirectorylist_disp1";
				break;
			}
		}
	$temp = new temp($ocid, $oeid, $update, $iduser, $disp);
	echo bab_printTemplate($temp, "frchart.html", $template);
	}

class orgtemp
	{
	function orgtemp(&$obj, $id)
		{
		global $babDB;
		$this->obj = $obj;
		$this->id = $id;
		$this->ocid = $this->obj->ocid;
		$this->update = $this->obj->update;
		$this->entity = $this->obj->babTree->nodes[$id]['datas']['name'];
		$this->roles = $this->obj->roles;
		$this->delete = $this->obj->delete;
		$this->startnode = $this->obj->startnode;
		$this->closenode = $this->obj->closenode;
		$this->opennode = $this->obj->opennode;
		$this->updateurlb = $this->obj->updateurlb;
		$this->updateurlt = $this->obj->updateurlt;
		$this->currentoe = $this->obj->currentoe;
		$this->coeid = $this->obj->coeid;

		if( !empty($this->obj->babTree->nodes[$id]['datas']['description']))
			{
			$this->description = "( ".$this->obj->babTree->nodes[$id]['datas']['description']." )";
			}
		else
			{
			$this->description = "";
			}

		if( isset($this->obj->babTree->nodes[$id]['datas']['sn'] ))
			{
			$this->superior = bab_composeUserName($this->obj->babTree->nodes[$id]['datas']['givenname'],$this->obj->babTree->nodes[$id]['datas']['sn']);
			$this->superiorurl = $GLOBALS['babUrlScript']."?tg=fltchart&idx=detr&ocid=".$this->ocid."&oeid=".$this->obj->babTree->nodes[$id]['datas']['identity']."&iduser=".$this->obj->babTree->nodes[$id]['datas']['id_user'];
			}
		else
			{
			$this->superior = '';
			}

		$this->oeid = $this->obj->babTree->nodes[$id]['datas']['id'];
		$fid = $this->obj->babTree->getFirstChild($id);
		$this->childs = array();
		if( $fid )
			{
			$this->childs[] = $fid;
			while( $fid = $this->obj->babTree->getNextSibling($fid))
				{
				$this->childs[] = $fid;
				}
			}
		$this->count = count($this->childs);
		if( $this->obj->babTree->hasChildren($id))
			{
			$this->closenodeurl =  $GLOBALS['babUrlScript']."?tg=frchart&disp=disp3&idx=closen&ocid=".$this->ocid."&oeid=".$this->oeid;
			$this->parent = 1;
			$this->leaf = 0;
			}
		else
			{
			$this->parent = 0;
			$this->leaf = 1;
			}

		if( $this->obj->babTree->rootid == $id && $this->obj->babTree->getParentId($id) !=  0 )
			{
			$this->bupbutton = true;
			}
		else
			{
			$this->bupbutton = false;
			}

		if( $this->obj->babTree->rootid == $id )
			{
			$this->first = 1;
			}
		else
			{
			$this->first = 0;
			}

		if( $this->obj->babTree->getPreviousSibling($id))
			{
			$this->firstchild = false;
			}
		else
			{
			$this->firstchild = true;
			}
		if( $this->obj->babTree->getNextSibling($id))
			{
			$this->lastchild = false;
			}
		else
			{
			$this->lastchild = true;
			}
		if( count($this->obj->closednodes) > 0 && in_array($id, $this->obj->closednodes))
			{
			$this->bparent = true;
			$this->opennodeurl =  $GLOBALS['babUrlScript']."?tg=frchart&disp=disp3&idx=openn&ocid=".$this->ocid."&oeid=".$this->oeid;
			}
		else
			{
			$this->bparent = false;
			}
		if( $this->coeid == $this->oeid )
			{
			$this->current = true;
			}
		else
			{
			$this->current = false;
			}
		$this->listrurl =  $GLOBALS['babUrlScript']."?tg=fltchart&ocid=".$this->ocid."&oeid=".$this->oeid;
		$this->startnodeurl =  $GLOBALS['babUrlScript']."?tg=frchart&disp=disp3&idx=startn&ocid=".$this->ocid."&oeid=".$this->oeid;
		$this->startupnodeurl =  $GLOBALS['babUrlScript']."?tg=frchart&disp=disp3&idx=startup&ocid=".$this->ocid."&oeid=".$this->oeid;
		$this->nodecell = bab_printTemplate($this, "frchart.html", "nodecell");
		$this->index = 0;
		}

	function getnextchild()
		{
		global $babDB;
		if( $this->index < $this->count)
			{
			$this->child = printChartNode($this->obj, $this->childs[$this->index]);
			$this->index++;
			return true;
			}
		else
			{
			return false;
			}
		}
	}

function printChartNode(&$obj, $id)
{
	if( count($obj->babTree->nodes) > 0 )
		{
		$temp = new orgtemp($obj, $id);
		return bab_printTemplate($temp, "frchart.html", "display_node");
		}
	else
		{
		return "";
		}
}

function displayChartTree($ocid, $oeid, $iduser, $adminMode)
{
	global $babBody;
	$orgChart = new bab_OvidentiaOrgChart('orgChart_' . $ocid, $ocid, $oeid, $iduser, 0, $adminMode);

	// 
	$registry = bab_getRegistryInstance();
	$registry->changeDirectory('/bab/orgchart/' . $ocid);

	$verticalThreshold = $registry->getValue('vertical_threshold');
	if (!isset($verticalThreshold)) {
		if (isset($GLOBALS['babChartVerticalThreshold'])) {
			$verticalThreshold = $GLOBALS['babChartVerticalThreshold'];
		} else {
			$verticalThreshold = 3;
		}
	}
	$orgChart->setVerticalThreshold($verticalThreshold);

	$openNodes = $registry->getValue('open_nodes');
	if (!is_array($openNodes)) {
		$openNodes = array();
	}
	$orgChart->setOpenNodes($openNodes);

	$openMembers = $registry->getValue('open_members');
	if (!is_array($openMembers)) {
		$openMembers = array();
	}
	$orgChart->setOpenMembers($openMembers);

	$zoomFactor = (float)$registry->getValue('zoom_factor');
	$orgChart->setZoomFactor($zoomFactor);

	$babBody->title = '';
	$babBody->babpopup($orgChart->printTemplate());
}


//TODO REMOVE
function displayChartTree2($ocid, $oeid, $update, $iduser)
	{
	global $babBody;
	class temp
		{
		function temp($ocid, $oeid, $update, $iduser)
			{
			global $babDB, $ocinfo;
			$this->ocid = $ocid;
			$this->update = $update;
			$this->roles = bab_translate("Roles");
			$this->delete = bab_translate("Delete");
			$this->startnode = bab_translate("Start");
			$this->closenode = bab_translate("Close");
			$this->opennode = bab_translate("Open");
			$this->closednodes = array();
			$this->coeid = $oeid;


			$this->babTree  = new bab_arraytree(BAB_OC_TREES_TBL, $ocid, "", $ocinfo['id_first_node']);

			$arr= explode(',', $ocinfo['id_closed_nodes'] );
			for( $i=0; $i < count($arr); $i++ )
				{
				if( $this->babTree->hasChildren($arr[$i]) )
					{
					$this->babTree->removeChilds($arr[$i]);
					$this->closednodes[] = $arr[$i];
					}
				}

			if( $update )
				{
				$this->updateurlb = $GLOBALS['babUrlScript']."?tg=flbchart&rf=0&ocid=".$ocid."&oeid=";
				$this->updateurlt = $GLOBALS['babUrlScript']."?tg=fltchart&rf=0&ocid=".$ocid."&oeid=";
				}
			else
				{
				$this->updateurlb = $GLOBALS['babUrlScript']."?tg=fltchart&rf=0&ocid=".$ocid."&oeid=";
				$this->updateurlt = $GLOBALS['babUrlScript']."?tg=fltchart&rf=0&ocid=".$ocid."&oeid=";
				}
			$this->currentoe = $oeid."&iduser=".$iduser;
			
			$this->res = $babDB->db_query("select ocet.*, ocet.id as identity, ocut.id_user, det.sn, det.givenname from ".BAB_OC_ENTITIES_TBL." ocet LEFT JOIN ".BAB_OC_TREES_TBL." octt on octt.id=ocet.id_node LEFT JOIN ".BAB_OC_ROLES_TBL." ocrt on ocrt.id_oc=ocet.id_oc and ocrt.id_entity=ocet.id and ocrt.type='1' LEFT JOIN ".BAB_OC_ROLES_USERS_TBL." ocut on ocut.id_role=ocrt.id LEFT JOIN ".BAB_DBDIR_ENTRIES_TBL." det on det.id=ocut.id_user where ocet.id_oc='".$this->ocid."' order by octt.lf asc");

			while($row = $babDB->db_fetch_array($this->res))
				{
				if( isset($this->babTree->nodes[$row['id_node']]))
					{
					$this->babTree->nodes[$row['id_node']]['datas'] = $row;
					}
				}
			$this->javascript = bab_printTemplate($this, "frchart.html", "orgjavascript");
			$this->content =  printChartNode($this, $this->babTree->rootid);
			}

		}

	$temp = new temp($ocid, $oeid, $update, $iduser);
	echo bab_printTemplate($temp, "frchart.html", "oedirectorylist_disp3");
	}


function displayFrtFrame($ocid, $oeid, $update)
{

	class temp
		{

		function temp($ocid, $oeid, $update)
			{
			global $ocinfo;
			$this->javascript = bab_printTemplate($this, "frchart.html", "orgjavascript");
			$this->charttitle = $ocinfo['name'];
			$this->chart_disp1_title = bab_translate("Text view");
			$this->chart_disp2_title = bab_translate("Vertical view");
			$this->chart_disp3_title = bab_translate("Horizontal view");
			$this->chart_disp4_title = bab_translate("Roles");
			$this->chart_disp5_title = bab_translate("Directories");
			$this->left_title = bab_translate("Show / hide left pane"); 
			$this->updatefrurl = $GLOBALS['babUrlScript']."?tg=frchart&ocid=".$ocid;
			}

		}
	$temp = new temp($ocid, $oeid, $update);
	die(bab_printTemplate($temp,"frchart.html", "frtframe"));
}


function displayUsersList($ocid, $oeid, $update, $pos, $xf, $q)
{
	global $babBody;

	class temp
		{
		var $count;

		function temp($ocid, $oeid, $update, $pos, $xf, $q)
			{
			global $ocinfo;
			$this->allname = bab_translate("All");
			$this->search = bab_translate("Search");
			$this->ocid = $ocid;
			$this->oeid = $oeid;
			$this->pos = $pos;
			$this->xf = $xf;
			$this->q = $q;
			$this->iddir = $ocinfo['id_directory'];
			$this->altbg = false;
			if( strlen($pos) > 0 && $pos[0] == "-" )
				{
				$this->pos = strlen($pos)>1? $pos[1]: '';
				$this->ord = "";
				}
			else
				{
				$this->pos = $pos;
				$this->ord = "-";
				}

			if( empty($pos))
				$this->allselected = 1;
			else
				$this->allselected = 0;

			$this->allurl = $GLOBALS['babUrlScript']."?tg=frchart&disp=disp5&ocid=".$ocid."&oeid=".$oeid."&pos=".($this->ord == "-"? "":$this->ord)."&xf=".$this->xf."&q=".urlencode($this->q);
			$this->count = 0;
			$this->db = $GLOBALS['babDB'];
			$arr = $this->db->db_fetch_array($this->db->db_query("select id_group from ".BAB_DB_DIRECTORIES_TBL." where id='".$this->iddir."'"));
			if(bab_isAccessValid(BAB_DBDIRVIEW_GROUPS_TBL, $this->iddir))
				{
				$this->idgroup = $arr['id_group'];

				$dbdirfields = array();
				$dbdirxfields = array();
				$rescol = $this->db->db_query("select * from ".BAB_DBDIR_FIELDSEXTRA_TBL." where id_directory='".($this->idgroup != 0? 0: $this->iddir)."' and ordering!='0' order by ordering asc");
				while( $row = $this->db->db_fetch_array($rescol))
					{
					if( $row['id_field'] < BAB_DBDIR_MAX_COMMON_FIELDS )
						{
						$rr = $this->db->db_fetch_array($this->db->db_query("select name, description from ".BAB_DBDIR_FIELDS_TBL." where id='".$row['id_field']."'"));
						if( $rr['name'] != 'jpegphoto')
							{
							$this->arrcols[] = array($rr['name'], translateDirectoryField($rr['description']), 1);
							$dbdirfields[] = $rr['name'];
							$this->select[] = 'e.'.$rr['name'];
							}
						}
					else
						{
						$rr = $this->db->db_fetch_array($this->db->db_query("select * from ".BAB_DBDIR_FIELDS_DIRECTORY_TBL." where id='".($row['id_field'] - BAB_DBDIR_MAX_COMMON_FIELDS)."'"));
						$this->arrcols[] = array("babdirf".$row['id'], translateDirectoryField($rr['name']), 1);
						$dbdirxfields[] = "babdirf".$row['id'];

						$leftjoin[] = 'LEFT JOIN '.BAB_DBDIR_ENTRIES_EXTRA_TBL.' lj'.$row['id']." ON lj".$row['id'].".id_fieldx='".$row['id']."' AND e.id=lj".$row['id'].".id_entry";
						$this->select[] = "lj".$row['id'].'.field_value '."babdirf".$row['id']."";
						}					
					}
				$this->arrcols[] = array('e_name', bab_translate('Entity'), 0);
				$this->arrcols[] = array('r_name', bab_translate('Role'), 0);
				$this->countcol = count($this->arrcols);


				$leftjoin[] = "left join ".BAB_OC_ROLES_USERS_TBL." ocrut on ocrut.id_user=e.id left join ".BAB_OC_ROLES_TBL." ocrt on ocrut.id_role=ocrt.id and ocrt.id_oc='".$this->ocid."' left join ".BAB_OC_ENTITIES_TBL." ocet on ocet.id=ocrt.id_entity"; 
				$this->select[] = 'ocet.name as e_name';
				$this->select[] = 'ocrt.name as r_name';
				$this->select[] = 'ocet.id as id_entity';
				$dbdirfields[] = 'id';
				$this->select[] = 'e.id';
				if( !in_array('e.email', $this->select))
					$this->select[] = 'e.email';
				if( !in_array('e.givenname', $this->select))
					$this->select[] = 'e.givenname';
				if( !in_array('e.sn', $this->select))
					$this->select[] = 'e.sn';

				if( $this->idgroup > 1 )
					{
					$req = " ".BAB_USERS_TBL." u2,
							".BAB_USERS_GROUPS_TBL." u,
							".BAB_DBDIR_ENTRIES_TBL." e 
								".implode(' ',$leftjoin)." 
								WHERE u.id_group='".$this->idgroup."' 
								AND u2.id=e.id_user 
								AND u2.disabled='0' 
								AND u.id_object=e.id_user 
								AND e.id_directory='0'";
					}
				elseif (1 == $this->idgroup) {
					$req = " ".BAB_USERS_TBL." u,
					".BAB_DBDIR_ENTRIES_TBL." e 
					".implode(' ',$leftjoin)." 
					WHERE 
						u.id=e.id_user 
						AND u.disabled='0' 
						AND e.id_directory='0'";
					}
				else
					{
					$req = " ".BAB_DBDIR_ENTRIES_TBL." e ".implode(' ',$leftjoin)." WHERE e.id_directory='".$this->iddir ."'";
					}

				$this->request = "select ".implode(',', $this->select)." from ".$req;
				}
			else
				{
				$this->countcol = 0;
				$this->count = 0;
				}

			$this->updateurlb = $GLOBALS['babUrlScript']."?tg=fltchart&ocid=".$ocid."&oeid=";
			$this->updateurlt = $GLOBALS['babUrlScript']."?tg=fltchart&ocid=".$ocid."&oeid=";
			$this->javascript = bab_printTemplate($this, "frchart.html", "orgjavascript");
			$this->cuserid = 0;
			$this->cuoeid = 0;

			$this->like = '';
			if (!empty($q))
				{
				$tmplike = array();
				$qs = addslashes($q);
				for( $k = 0; $k < count($dbdirfields); $k++ )
					{
					$tmplike[] = 'e.'.$dbdirfields[$k]." like '%".$qs."%'";
					}

				for( $k = 0; $k < count($dbdirxfields); $k++ )
					{
					$tmpid = substr($dbdirxfields[$k], strlen("babdirf"));
					$tmplike[] = "lj".$tmpid.".field_value like '%".$qs."%'";
					}
				
				if( count($tmplike) > 0 )
					{
					$this->like = "(".implode(' or ', $tmplike).")";
					}
				}

			}

		function getnextcol()
			{
			static $i = 0;
			static $tmp = array();
			if( $i < $this->countcol)
				{
				$arr = $this->arrcols[$i];
				$this->coltxt = $arr[1];
				if( $arr[2] )
					{
					$this->colurl = $GLOBALS['babUrlScript']."?tg=frchart&disp=disp5&ocid=".$this->ocid."&oeid=".$this->oeid."&pos=".$this->ord.$this->pos."&xf=".$arr[0]."&q=".urlencode($this->q);
					}
				else
					{
					$this->colurl = false;
					}
				$tmp[] = $arr[0];
				$i++;
				return true;
				}
			else
				{
				if( count($tmp) > 0 )
					{
					$tmp[] = "id";
					if( $this->xf == "" )
						$this->xf = $tmp[0];

					$req = $this->request." and e.".$this->xf." like '".$this->pos."%'";

					if( !empty($this->like))
						{
						$req .= " and ".$this->like." ";
						}
					$req .= " order by ".$this->xf." ";

					if( $this->ord == "-" )
						{
						$req .= "asc";
						}
					else
						{
						$req .= "desc";
						}

					$this->res = $this->db->db_query($req);
					$this->count = $this->db->db_num_rows($this->res);
					}
				else
					$this->count = 0;

				return false;
				}
			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$this->altbg = $this->altbg ? false : true;
				$this->arrf = $this->db->db_fetch_array($this->res);
				$this->userid = $this->arrf['id'];
				$this->mailaddr = isset($this->arrf['email']) ? $this->arrf['email'] : false;
				if( isset($this->arrf['id_entity']))
					{
					$this->uoeid = $this->arrf['id_entity'];
					}
				else
					{
					$this->uoeid = 0;
					}
				if( empty($this->cuoeid) && isset($this->arrf['id_entity']))
					{
					$this->cuserid = $this->arrf['id'];
					$this->cuoeid = $this->arrf['id_entity'];
					}
				$this->firstlast = bab_composeUserName($this->arrf['givenname'],$this->arrf['sn']);
				$this->firstlast = str_replace("'", "\'", $this->firstlast);
				$this->firstlast = str_replace('"', "'+String.fromCharCode(34)+'",$this->firstlast);
				$i++;
				return true;
				}
			else
				{
				return false;
				}
			}

		function getnextcolval()
			{
			static $i = 0;
			if( $i < $this->countcol)
				{
				$this->bmail = $this->mailaddr == $this->arrf[$i];
				$this->coltxt = stripslashes(bab_translate($this->arrf[$i]));
				$i++;
				return true;
				}
			else
				{
				$i = 0;
				return false;
				}
			}

		function getnextselect()
			{
			static $k = 0;
			static $t = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
			if( $k < 26)
				{
				$this->selectname = substr($t, $k, 1);
				$this->selecturl = $GLOBALS['babUrlScript']."?tg=frchart&disp=disp5&ocid=".$this->ocid."&oeid=".$this->oeid."&pos=".($this->ord == "-"? "":$this->ord).$this->selectname."&xf=".$this->xf."&q=".urlencode($this->q);
				if( $this->pos == $this->selectname)
					$this->selected = 1;
				else
					$this->selected = 0;
				$k++;
				return true;
				}
			else
				return false;

			}
		}

	$temp = new temp($ocid, $oeid, $update, $pos, $xf, $q);
	echo bab_printTemplate($temp, "frchart.html", "oedirectorylist_disp4");
}



function browseRoles($ocid, $oeid, $role, $swhat, $word, $type, $vpos, $update)
	{
	global $babBody;
	class temp
		{
		function temp($ocid, $oeid, $role, $swhat, $word, $type, $vpos, $update)
			{
			global $babBody, $babDB;
			$this->ocid = $ocid;
			$this->oeid = $oeid;
			$this->role = $role;
			$this->type = $type;
			$this->vpos = $vpos;
			$this->update = $update;

			$this->entitytxt = bab_translate("Entity");
			$this->roletxt = bab_translate("Role");
			$this->usernametxt = bab_translate("Fullname");
			$this->provided = bab_translate("Provided role");
			$this->notprovided = bab_translate("Vacant role");
			$this->searchtxt = bab_translate("Search");
			$this->alltxt = bab_translate("Roles and entities");
			$this->all2txt = bab_translate("All");
			$this->restricttxt = bab_translate("Restrict to");
			$this->intxt = bab_translate("In");
			$this->messageemptytxt = bab_translate("Search result empty");
			$this->topurl = "";
			$this->bottomurl = "";
			$this->nexturl = "";
			$this->prevurl = "";
			$this->topname = "";
			$this->bottomname = "";
			$this->nextname = "";
			$this->prevname = "";

			if( !$this->update )
				{
				$role = 1;
				}

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

			$this->wordval = stripslashes($word);
			switch($swhat)
				{
				case 1:
					$this->sentities = 'selected';
					$this->sfunctions = '';
					if( !empty($this->wordval))
					{
						$req .= " and ocet.name like '%".addslashes($this->wordval)."%'";
					}
					break;
				case 2:
					$this->sentities = '';
					$this->sfunctions = 'selected';
					if( !empty($this->wordval))
					{
						$req .= " and ocrt.name like '%".addslashes($this->wordval)."%'";
					}
					break;
				default:
					$this->sentities = '';
					$this->sfunctions = '';
					if( !empty($this->wordval))
					{
						$req .= " and ( ocet.name like '%".addslashes($this->wordval)."%' or ocrt.name like '%".addslashes($this->wordval)."%')";
					}
					break;
				}

			list($total) = $babDB->db_fetch_row($babDB->db_query("select count(ocrt.id) as total from ".$req));
			if( $total > ORG_MAX_REQUESTS_LIST )
				{
				$urltmp = $GLOBALS['babUrlScript']."?tg=frchart&disp=disp4&ocid=".$this->ocid."&eid=".$this->oeid."&type=".$this->type."&role=".$this->role."&vpos=";

				if( $vpos > 0)
					{
					$this->topurl = $urltmp."0";
					$this->topname = "&lt;&lt;";
					}

				$next = $vpos - ORG_MAX_REQUESTS_LIST;
				if( $next >= 0)
					{
					$this->prevurl = $urltmp.$next;
					$this->prevname = "&lt;";
					}

				$next = $vpos + ORG_MAX_REQUESTS_LIST;
				if( $next < $total)
					{
					$this->nexturl = $urltmp.$next;
					$this->nextname = "&gt;";
					if( $next + ORG_MAX_REQUESTS_LIST < $total)
						{
						$bottom = $total - ORG_MAX_REQUESTS_LIST;
						}
					else
						$bottom = $next;
					$this->bottomurl = $urltmp.$bottom;
					$this->bottomname = "&gt;&gt;";
					}
				}


			$req .= " order by ocrt.name asc";
			if( $total > ORG_MAX_REQUESTS_LIST)
				{
				$req .= " limit ".$vpos.",".ORG_MAX_REQUESTS_LIST;
				}

			$this->res = $babDB->db_query("select ocrt.name AS r_name, ocrt.id as id_role, det.id as iduser, det.sn, det.givenname, det.id as iduser, ocet.name as e_name, ocet.id as id_entity from ".$req);
			$this->count = $babDB->db_num_rows($this->res);

			$this->entres = $babDB->db_query("select id, name from ".BAB_OC_ENTITIES_TBL." where id_oc='".$this->ocid."' order by name asc");
			$this->entcount = $babDB->db_num_rows($this->entres);

			$this->updateurlb = $GLOBALS['babUrlScript']."?tg=fltchart&ocid=".$ocid."&oeid=";
			$this->updateurlt = $GLOBALS['babUrlScript']."?tg=fltchart&ocid=".$ocid."&oeid=";
			$this->javascript = bab_printTemplate($this, "frchart.html", "orgjavascript");
			$this->cuserid = 0;
			$this->cuoeid = 0;
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
				$this->entityid = $arr['id_entity'];
				$this->jentity = str_replace("'", "\'", $arr['e_name']);
				$this->jentity = str_replace('"', "'+String.fromCharCode(34)+'",$this->jentity);

				$this->rolename = $arr['r_name'];
				$this->jrole = str_replace("'", "\'", $arr['r_name']);
				$this->jrole = str_replace('"', "'+String.fromCharCode(34)+'",$this->jrole);
				$this->roleid = $arr['id_role'];
				if( isset($arr['iduser']) && $arr['iduser'] )
					{
					$this->userid = $arr['iduser'];
					$this->username = bab_composeUserName($arr['sn'], $arr['givenname']);
					if( !$this->cuserid )
						{
						$this->cuserid = $this->userid;
						$this->cuoeid = $this->entityid;
						}
					}
				else
					{
					$this->username = '';
					$this->userid = 0;
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

	$temp = new temp($ocid, $oeid, $role, $swhat, $word, $type, $vpos, $update);
	echo bab_printTemplate($temp, "frchart.html", "browseroles");
	}


function changeRootNode($ocid, $oeid)
{
	global $babDB, $ocinfo,$update;
	$arr = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_OC_ENTITIES_TBL." where id='".$oeid."'"));
	if ($update)
		{
		$babDB->db_query("update ".BAB_ORG_CHARTS_TBL." set id_first_node='".$arr['id_node']."' where id='".$ocid."'");
		}
	chart_session_rootnode($ocid, $arr['id_node']);
	$ocinfo['id_first_node'] = $arr['id_node'];
}

function changeUpRootNode($ocid, $oeid)
{
	global $babDB, $ocinfo,$update;
	$arr = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_OC_ENTITIES_TBL." where id='".$oeid."'"));
	
	$babTree = new bab_dbtree(BAB_OC_TREES_TBL, $ocid);
	$nodeinfo = $babTree->getNodeInfo($arr['id_node']);

	if ($update)
		{
		$babDB->db_query("update ".BAB_ORG_CHARTS_TBL." set id_first_node='".$nodeinfo['id_parent']."' where id='".$ocid."'");
		}
	chart_session_rootnode($ocid, $nodeinfo['id_parent']);	
	$ocinfo['id_first_node'] = $nodeinfo['id_parent'];
}

function closeNode($ocid, $oeid)
{
	global $babDB, $ocinfo,$update;
	$babTree  = new bab_arraytree(BAB_OC_TREES_TBL, $ocid, "", $ocinfo['id_first_node']);
	$arr = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_OC_ENTITIES_TBL." where id='".$oeid."'"));
	$rr = array();
	if( $babTree->hasChildren($arr['id_node']))
	{
		if( !empty($ocinfo['id_closed_nodes'] ))
		{
		$rr = explode(',', $ocinfo['id_closed_nodes'] );
		}
		if( count($rr) == 0 || !in_array($arr['id_node'],$rr))
		{
			$rr[] = $arr['id_node'];
		}
	}
	if( count($rr) > 0 )
	{
	asort($rr);
	$closednodes = implode(',', $rr);
	if ($update)
		{
		$babDB->db_query("update ".BAB_ORG_CHARTS_TBL." set id_closed_nodes='".$closednodes."' where id='".$ocid."'");
		}
	chart_session_closednodes($ocid, $closednodes);
	$ocinfo['id_closed_nodes'] = $closednodes;
	}
}

function openNode($ocid, $oeid)
{
	global $babDB, $ocinfo,$update;
	$babTree  = new bab_arraytree(BAB_OC_TREES_TBL, $ocid, "", $ocinfo['id_first_node']);
	$arr = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_OC_ENTITIES_TBL." where id='".$oeid."'"));
	$rr = array();
	$childs = $babTree->getChilds($arr['id_node']);
	if( count($childs) > 0)
	{
		if( !empty($ocinfo['id_closed_nodes'] ))
		{
		$rr = explode(',', $ocinfo['id_closed_nodes'] );
		}
		for($i=0; $i <count($childs); $i++)
		{
			if( count($rr) == 0 || !in_array($childs[$i],$rr))
			{
				$rr[] = $childs[$i];
			}
		}
	}
	if( count($rr) > 0 )
	{
		$rr2 = array();
		for($i=0; $i <count($rr); $i++)
		{
			if( $arr['id_node'] != $rr[$i])
			{
				$rr2[] = $rr[$i];
			}
		}
	}

	if( count($rr2) > 0 )
	{
	asort($rr2);
	$closednodes = implode(',', $rr2);
	if ($update)
		{
		$babDB->db_query("update ".BAB_ORG_CHARTS_TBL." set id_closed_nodes='".$closednodes."' where id='".$ocid."'");
		}
	chart_session_closednodes($ocid, $closednodes);
	$ocinfo['id_closed_nodes'] = $closednodes;
	}
}


/* main */
$update = false;
$ocinfo = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_ORG_CHARTS_TBL." where id='".$ocid."'"));
if( bab_isAccessValid(BAB_OCUPDATE_GROUPS_TBL, $ocid))
{
	if( $ocinfo['edit'] == 'Y' && $ocinfo['edit_author'] == $BAB_SESS_USERID)
	{
		$update = true;
	}
}


if( !$update && !bab_isAccessValid(BAB_OCVIEW_GROUPS_TBL, $ocid))
{
	echo bab_translate("Access denied");
	return;
}
if (!$update)
{
$ocinfo['id_closed_nodes'] = isset($_SESSION['BAB_SESS_CHARTCN-'.$ocid])? $_SESSION['BAB_SESS_CHARTCN-'.$ocid]: '';
$ocinfo['id_first_node'] = isset($_SESSION['BAB_SESS_CHARTRN-'.$ocid])?$_SESSION['BAB_SESS_CHARTRN-'.$ocid]:0;
}
$oeid = !isset($oeid)? $_SESSION['BAB_SESS_CHARTOEID-'.$ocid] :$oeid;


if(!isset($idx))
{
	$idx = "list";
}

if(!isset($disp))
{
	$disp = "disp1";
}
if( $idx == "startn" )
{
	changeRootNode($ocid, $oeid);
	$idx = "list";
}
else if ( $idx == "startup" )
{
	changeUpRootNode($ocid, $oeid);
	$idx = "list";
}
else if ( $idx == "closen" )
{
	closeNode($ocid, $oeid);
	$idx = "list";
}
else if ( $idx == "openn" )
{
	openNode($ocid, $oeid);
	$idx = "list";
}
else if ($idx == 'save_state')
{
	if (bab_isAccessValid(BAB_OCUPDATE_GROUPS_TBL, $ocid)) {
		// Only for user with update rights.
		// Here we store the tree state (open entities(nodes)/open members/zoom factor).
		$openNodes = explode(',', bab_rp('open_nodes', ''));
		$openMembers = explode(',', bab_rp('open_members', ''));
		$zoomFactor = bab_rp('zoom_factor', 1.0);
		$registry = bab_getRegistryInstance();

		$registry->changeDirectory('/bab/orgchart/' . $ocid);
		$registry->setKeyValue('open_nodes', $openNodes);
		$registry->setKeyValue('open_members', $openMembers);
		$registry->setKeyValue('zoom_factor', $zoomFactor);
	}
	$idx = 'list';
}


if (isset($disp))
{
	$_SESSION["BAB_SESS_CHARTDISP-".$ocid] = $disp;
}
elseif( isset($_SESSION["BAB_SESS_CHARTDISP-".$ocid]))
{
	$disp = $_SESSION["BAB_SESS_CHARTDISP-".$ocid];
}



chart_session_oeid($ocid);
switch($idx)
	{
	case "frt":
		displayFrtFrame($ocid, $oeid, $update);
		break;
	default:
	case "list":
		$babBody->title = $ocinfo['name'];
		if( !isset($oeid)) { $oeid = 0;}
		if( !isset($iduser)) { $iduser = 0;}
		switch($disp)
		{
			case "disp4":
				if( !isset($role)) $role =0;
				if( !isset($vpos)) $vpos =0;
				if( !isset($type)) $type ='';
				if( !isset($eid)) $eid =0;
				if( !isset($word)) $word ='';
				if( !isset($swhat)) $swhat =0;
				browseRoles($ocid, $eid, $role, $swhat, $word, $type, $vpos, $update);
				break;
			case "disp5":
				include_once $babInstallPath."utilit/dirincl.php";
				if( isset($submit))
				{
					$pos ='';
				}
				if( !isset($pos )){	$pos = "A"; }
				if( !isset($q )){	$q = ""; }
				if( !isset($xf )){	$xf = ""; }
				displayUsersList($ocid, $oeid, $update, $pos, $xf, $q);
				break;
			case "disp3":
				displayChartTree($ocid, $oeid, $iduser, $update);
				break;
			default:
				displayChart($ocid, $oeid, $update, $iduser, $disp);
				break;
		}
		break;
	}
exit;

?>