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
include $babInstallPath."utilit/orgincl.php";
include $babInstallPath."utilit/treeincl.php";

define("ORG_MAX_REQUESTS_LIST", 100);

function displayChart($ocid, $oeid, $update, $disp='')
	{
	global $babBody;
	class temp
		{
		function temp($ocid, $oeid, $update, $disp)
			{
			global $babDB, $ocinfo;
			$this->ocid = $ocid;
			$this->update = $update;
			$this->disp = $disp;

			$this->roles = bab_translate("Roles");
			$this->delete = bab_translate("Delete");
			$this->startnode = bab_translate("Start");
			$this->closenode = bab_translate("Close");

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
			$this->currentoe = $oeid;
			$this->maxlevel += 1;
			//$this->res = $babDB->db_query("select ocet.* from ".BAB_OC_ENTITIES_TBL." ocet LEFT JOIN ".BAB_OC_TREES_TBL." octt on octt.id=ocet.id_node where ocet.id_oc='".$this->ocid."' order by octt.lf asc");
			
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
					if( $this->currentoe == 0 )
						{
						$this->currentoe = $this->oeid;
						}
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

				if( $this->currentoe == $this->oeid )
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
	$temp = new temp($ocid, $oeid, $update, $disp);
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
		$this->updateurlb = $this->obj->updateurlb;
		$this->updateurlt = $this->obj->updateurlt;
		$this->currentoe = $this->obj->currentoe;

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
			if( $this->currentoe == 0 )
				{
				$this->currentoe = $this->oeid;
				}
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
		if( $this->currentoe == $this->oeid )
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
		$temp = new orgtemp(&$obj, $id);
		return bab_printTemplate($temp, "frchart.html", "display_node");
		}
	else
		{
		return "";
		}
}


function displayChartTree($ocid, $oeid, $update)
	{
	global $babBody;
	class temp
		{
		function temp($ocid, $oeid, $update)
			{
			global $babDB, $ocinfo;
			$this->ocid = $ocid;
			$this->update = $update;
			$this->roles = bab_translate("Roles");
			$this->delete = bab_translate("Delete");
			$this->startnode = bab_translate("Start");
			$this->closenode = bab_translate("Close");
			$this->closednodes = array();


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
			$this->currentoe = $oeid;
			/* lire uniquement à partir du root XXXXXXXXXX*/
			//$this->res = $babDB->db_query("select ocet.* from ".BAB_OC_ENTITIES_TBL." ocet LEFT JOIN ".BAB_OC_TREES_TBL." octt on octt.id=ocet.id_node where ocet.id_oc='".$this->ocid."' order by octt.lf asc");
			
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

	$temp = new temp($ocid, $oeid, $update);
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
			$this->chart_disp5_title = bab_translate("Directory");
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
			if( $pos[0] == "-" )
				{
				$this->pos = $pos[1];
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
				$this->rescol = $this->db->db_query("select dft.name, dft.description from ".BAB_DBDIR_FIELDSEXTRA_TBL." dfxt left join ".BAB_DBDIR_FIELDS_TBL." dft on dfxt.id_field=dft.id where id_directory='".($this->idgroup != 0? 0: $this->id)."' and ordering!='0' order by ordering asc");
				while( $row = $this->db->db_fetch_array($this->rescol))
					{
					$this->arrcols[] = array('det.'.$row['name'], $row['description'], 1);
					}
				$this->arrcols[] = array('ocet.name as e_name', 'Entity', 0);
				$this->arrcols[] = array('ocrt.name as r_name', 'Role', 0);
				$this->countcol = count($this->arrcols);
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
				$qs = addslashes($q);
				$this->like = "(det.cn like '%".$qs."%'".
							" or det.sn like '%".$qs."%'".
							" or det.mn like '%".$qs."%'".
							" or det.givenname like '%".$qs."%'".
							" or det.email like '%".$qs."%'".
							" or det.btel like '%".$qs."%'".
							" or det.htel like '%".$qs."%'".
							" or det.mobile like '%".$qs."%'".
							" or det.bfax like '%".$qs."%'".
							" or det.title like '%".$qs."%'".
							" or det.departmentnumber like '%".$qs."%'".
							" or det.organisationname like '%".$qs."%'".
							" or det.bstreetaddress like '%".$qs."%'".
							" or det.bcity like '%".$qs."%'".
							" or det.bpostalcode like '%".$qs."%'".
							" or det.bstate like '%".$qs."%'".
							" or det.bcountry like '%".$qs."%'".
							" or det.hstreetaddress like '%".$qs."%'".
							" or det.hcity like '%".$qs."%'".
							" or det.hpostalcode like '%".$qs."%'".
							" or det.hstate like '%".$qs."%'".
							" or det.hcountry like '%".$qs."%'".
							" or det.user1 like '%".$qs."%'".
							" or det.user2 like '%".$qs."%'".
							" or det.user3 like '%".$qs."%'".
							" or ocet.name like '%".$qs."%'".
							" or ocrt.name like '%".$qs."%')";
				}

			}

		function getnextcol()
			{
			static $i = 0;
			static $tmp = array();
			if( $i < $this->countcol)
				{
				$arr = $this->arrcols[$i];
				$this->coltxt = bab_translate($arr[1]);
				if( $arr[2] )
					{
					$this->colurl = $GLOBALS['babUrlScript']."?tg=frchart&disp=disp5&ocid=".$this->ocid."&oeid=".$this->oeid."&pos=".$this->ord."&xf=".$arr[0]."&q=".urlencode($this->q);
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
					$tmp[] = "det.id";
					if( $this->xf == "" )
						$this->xf = $tmp[0];
					if( !in_array('det.email', $tmp))
						$tmp[] = 'det.email';
					if( !in_array('det.givenname', $tmp))
						$tmp[] = 'det.givenname';
					if( !in_array('det.sn', $tmp))
						$tmp[] = 'det.sn';

					$tmp[] = 'ocet.id as id_entity';

					$this->select = implode($tmp, ",");

					if( $this->idgroup > 1 )
						{
						$req = "select ".$this->select." from ".BAB_DBDIR_ENTRIES_TBL." det left join ".BAB_USERS_GROUPS_TBL." ugt on ugt.id_object=det.id_user left join ".BAB_OC_ROLES_USERS_TBL." ocrut on ocrut.id_user=det.id left join ".BAB_OC_ROLES_TBL." ocrt on ocrut.id_role=ocrt.id and ocrt.id_oc='".$this->ocid."' left join ".BAB_OC_ENTITIES_TBL." ocet on ocet.id=ocrt.id_entity where ugt.id_group='".$this->idgroup."' and det.id_directory='0' and ".$this->xf." like '".$this->pos."%'";
						if( !empty($this->like))
							{
							$req .= " and ".$this->like." ";
							}
						$req .= " order by ".$this->xf." ";
						}
					else
						{
						$req = "select ".$this->select." from ".BAB_DBDIR_ENTRIES_TBL." det left join ".BAB_OC_ROLES_USERS_TBL." ocrut on ocrut.id_user=det.id left join ".BAB_OC_ROLES_TBL." ocrt on ocrut.id_role=ocrt.id and ocrt.id_oc='".$this->ocid."' left join ".BAB_OC_ENTITIES_TBL." ocet on ocet.id=ocrt.id_entity where det.id_directory='".($this->idgroup != 0? 0: $this->iddir)."' and ".$this->xf." like '".$this->pos."%'";
						if( !empty($this->like))
							{
							$req .= " and ".$this->like." ";
							}
						$req .= " order by ".$this->xf." ";
						}

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
				if( isset($this->arrf['id_entity']))
					{
					$this->uoeid = $this->arrf['id_entity'];
					}
				else
					{
					$this->uoeid = 0;
					}
				if( $i == 0 )
					{
					$this->cuserid = $this->arrf['id']? $this->arrf['id']: 0;
					$this->cuoeid = isset($this->arrf['id_entity'])?$this->arrf['id_entity']: 0;
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



function browseRoles($ocid, $oeid, $role, $type, $vpos)
	{
	global $babBody;
	class temp
		{
		function temp($ocid, $oeid, $role, $type, $vpos)
			{
			global $babBody, $babDB;
			$this->ocid = $ocid;
			$this->oeid = $oeid;
			$this->role = $role;
			$this->type = $type;
			$this->vpos = $vpos;

			$this->entitytxt = bab_translate("Entity");
			$this->roletxt = bab_translate("Role");
			$this->usernametxt = bab_translate("Fullname");
			$this->provided = bab_translate("Provided role");
			$this->notprovided = bab_translate("Vacant role");
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
				$urltmp = $GLOBALS['babUrlScript']."?tg=frchart&idx=disp5&ocid=".$this->ocid."&eid=".$this->oeid."&type=".$this->type."&role=".$this->role."&echo=".$this->echo."&vpos=";

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

	$temp = new temp($ocid, $oeid, $role, $type, $vpos);
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
$ocinfo['id_closed_nodes'] = isset($GLOBALS['BAB_SESS_CHARTCN-'.$ocid])? $GLOBALS['BAB_SESS_CHARTCN-'.$ocid]: '';
$ocinfo['id_first_node'] = isset($GLOBALS['BAB_SESS_CHARTRN-'.$ocid])?$GLOBALS['BAB_SESS_CHARTRN-'.$ocid]:0;
}
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

$sess = "BAB_SESS_CHARTDISP-".$ocid;
if (isset($disp))
{
session_register("BAB_SESS_CHARTDISP-".$ocid);
$$sess = $disp;
}
elseif( isset($$sess))
{
	$disp = $$sess;
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
		switch($disp)
		{
			case "disp4":
				if( !isset($role)) $role =0;
				if( !isset($vpos)) $vpos =0;
				if( !isset($type)) $type ='';
				if( !isset($eid)) $eid =0;
				browseRoles($ocid, $eid, $role, $type, $vpos);
				break;
			case "disp5":
				if( !isset($pos )){	$pos = "A"; }
				if( !isset($q )){	$q = ""; }
				if( !isset($xf )){	$xf = ""; }
				displayUsersList($ocid, $oeid, $update, $pos, $xf, $q);
				break;
			case "disp3":
				displayChartTree($ocid, $oeid, $update);
				break;
			default:
				displayChart($ocid, $oeid, $update,$disp);
				break;
		}
		break;
	}
exit;
?>