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
function getDirectoryName($id, $table)
	{
	$db = $GLOBALS['babDB'];
	$query = "select name from ".$table." where id='".$id."'";
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

function UBrowseDbDirectory($id, $pos, $xf, $cb)
{
	global $babBody;

	class temp
		{
		var $count;

		function temp($id, $pos, $xf, $cb)
			{
			$this->allname = bab_translate("All");
			$this->id = $id;
			$this->pos = $pos;
			$this->badd = false;
			$this->xf = $xf;
			$this->cb=$cb;
			if( !empty($pos) && $pos[0] == "-" )
				{
				$this->pos = strlen($pos) > 1? $pos[1]: '';
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
			$this->allurl = $GLOBALS['babUrlScript']."?tg=directory&idx=usdb&id=".$id."&pos=".($this->ord == "-"? "":$this->ord)."&xf=".$this->xf."&cb=".$this->cb;
			$this->count = 0;
			$this->db = $GLOBALS['babDB'];
			$arr = $this->db->db_fetch_array($this->db->db_query("select id_group from ".BAB_DB_DIRECTORIES_TBL." where id='".$id."'"));
			if(bab_isAccessValid(BAB_DBDIRVIEW_GROUPS_TBL, $id))
				{
				$this->idgroup = $arr['id_group'];
				$this->rescol = $this->db->db_query("select id_field from ".BAB_DBDIR_FIELDSEXTRA_TBL." where id_directory='".($this->idgroup != 0? 0: $this->id)."' and ordering!='0' order by ordering asc");
				$this->countcol = $this->db->db_num_rows($this->rescol);
				}
			else
				{
				$this->countcol = 0;
				$this->count = 0;
				}
			$this->altbg = false;
			}

		function getnextcol()
			{
			static $i = 0;
			static $tmp = array();
			if( $i < $this->countcol)
				{
				$arr = $this->db->db_fetch_array($this->rescol);
				$arr = $this->db->db_fetch_array($this->db->db_query("select name, description from ".BAB_DBDIR_FIELDS_TBL." where id='".$arr['id_field']."'"));
				$this->coltxt = bab_translate($arr['description']);
				$this->colurl = $GLOBALS['babUrlScript']."?tg=directory&idx=usdb&id=".$this->id."&pos=".$this->ord.$this->pos."&xf=".$arr['name']."&cb=".$this->cb;
				$tmp[] = $arr['name'];
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
					for( $i=0; $i < count($tmp); $i++)
						$tmp[$i] = BAB_DBDIR_ENTRIES_TBL.".".$tmp[$i];
					if( !in_array('email', $tmp))
						$tmp[] = 'email';
					if( !in_array('givenname', $tmp))
						$tmp[] = 'givenname';
					if( !in_array('sn', $tmp))
						$tmp[] = 'sn';
					$this->select = implode($tmp, ",");
					if( $this->idgroup > 1 )
						{
						$req = "select ".$this->select." from ".BAB_DBDIR_ENTRIES_TBL." join ".BAB_USERS_GROUPS_TBL." where ".BAB_USERS_GROUPS_TBL.".id_group='".$this->idgroup."' and ".BAB_USERS_GROUPS_TBL.".id_object=".BAB_DBDIR_ENTRIES_TBL.".id_user and ".BAB_DBDIR_ENTRIES_TBL.".".$this->xf." like '".$this->pos."%' and ".BAB_DBDIR_ENTRIES_TBL.".id_directory='".($this->idgroup != 0? 0: $this->id)."' order by ".$this->xf." ";
						}
					else
						{
						$req = "select ".$this->select." from ".BAB_DBDIR_ENTRIES_TBL." where ".BAB_DBDIR_ENTRIES_TBL.".".$this->xf." like '".$this->pos."%' and ".BAB_DBDIR_ENTRIES_TBL.".id_directory='".($this->idgroup != 0? 0: $this->id)."' order by ".$this->xf." ";
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
				$this->selecturl = $GLOBALS['babUrlScript']."?tg=directory&idx=usdb&id=".$this->id."&pos=".($this->ord == "-"? "":$this->ord).$this->selectname."&xf=".$this->xf."&cb=".$this->cb;
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

	$temp = new temp($id, $pos, $xf, $cb);
	echo bab_printTemplate($temp, "directory.html", "uadbrowse");
}

class bab_viewDirectoryUser
{

function bab_viewDirectoryUser($id)
	{
	$this->db = $GLOBALS['babDB'];
	
	$res = $this->db->db_query("select *, LENGTH(photo_data) as plen from ".BAB_DBDIR_ENTRIES_TBL." where id='".$id."'");
	$this->showph = false;
	$this->count = 0;
	$this->access = false;
	if( $res && $this->db->db_num_rows($res) > 0)
		{
		$this->arr = $this->db->db_fetch_array($res);
		if( $this->arr['id_directory'] == 0 )
			{
			$res = $this->db->db_query("select id, id_group from ".BAB_DB_DIRECTORIES_TBL." where id_group != '0'");
			while( $row = $this->db->db_fetch_array($res))
				{
				list($bdir) = $this->db->db_fetch_array($this->db->db_query("select directory from ".BAB_GROUPS_TBL." where id='".$row['id_group']."'"));
				if( $bdir == 'Y' && bab_isAccessValid(BAB_DBDIRVIEW_GROUPS_TBL, $row['id']))
					{
					if( $row['id_group'] == 1 && $GLOBALS['BAB_SESS_USERID'] != "" )
						{
						$this->access = true;
						break;
						}
					$res2 = $this->db->db_query("select id from ".BAB_USERS_GROUPS_TBL." where id_object='".$this->arr['id_user']."' and id_group='".$row['id_group']."'");
					if( $res2 && $this->db->db_num_rows($res2) > 0 )
						{
						$this->access = true;
						break;
						}
					}

				}
			}
		else if( bab_isAccessValid(BAB_DBDIRVIEW_GROUPS_TBL, $this->arr['id_directory']))
			{
			$this->access = true;
			}

		if( $this->access )
			{
			$this->name = $this->arr['givenname']. " ". $this->arr['sn'];
			if( $this->arr['plen'] > 0 )
				{
				$this->showph = true;
				}

			$this->urlimg = $GLOBALS['babUrlScript']."?tg=directory&idx=getimg&id=".$this->arr['id_directory']."&idu=".$id;
			$this->res = $this->db->db_query("select * from ".BAB_DBDIR_FIELDS_TBL." where name !='jpegphoto'");

			if( $this->res && $this->db->db_num_rows($this->res) > 0)
				{
				$this->count = $this->db->db_num_rows($this->res);
				}
			}
		}
	else
		{
		$this->name = "";
		$this->urlimg = "";
		}
	}

}


function summaryDbContact($id, $idu, $update=true)
{
	global $babBody;

	class temp
		{

		function temp($id, $idu, $update)
			{
			$this->db = $GLOBALS['babDB'];
			list($idgroup, $allowuu) = $this->db->db_fetch_array($this->db->db_query("select id_group, user_update from ".BAB_DB_DIRECTORIES_TBL." where id='".$id."'"));

			$this->res = $this->db->db_query("select * from ".BAB_DBDIR_FIELDS_TBL." where name !='jpegphoto'");
			if( $this->res && $this->db->db_num_rows($this->res) > 0)
				{
				$this->count = $this->db->db_num_rows($this->res);
				}
			else
				{
				$this->count = 0;
				}
			$res = $this->db->db_query("select *, LENGTH(photo_data) as plen from ".BAB_DBDIR_ENTRIES_TBL." where id_directory='".($idgroup != 0? 0: $id)."' and id='".$idu."'");
			$this->showph = false;
			if( $res && $this->db->db_num_rows($res) > 0)
				{
				$this->arr = $this->db->db_fetch_array($res);
				$this->name = stripslashes($this->arr['givenname']). " ". stripslashes($this->arr['sn']);
				if( $this->arr['plen'] > 0 )
					$this->showph = true;

				$this->urlimg = $GLOBALS['babUrlScript']."?tg=directory&idx=getimg&id=".$id."&idu=".$idu;

				$this->del = bab_isAccessValid(BAB_DBDIRADD_GROUPS_TBL, $id);
				if( $idgroup == 0 )
					{
					$allowuu = "N";
					}

				$this->modify = bab_isAccessValid(BAB_DBDIRUPDATE_GROUPS_TBL, $id);

				if( $this->modify == false && $allowuu == "Y" && $this->arr['id_user'] == $GLOBALS['BAB_SESS_USERID'] )
					{
					$this->modify = true;
					}

				if( $this->modify )
					{
					$this->modifytxt = bab_translate("Modify");
					$this->modifyurl = $GLOBALS['babUrlScript']."?tg=directory&idx=dbmod&id=".$id."&idu=".$idu;
					}

				if( $this->del )
					{
					$this->deltxt = bab_translate("Delete");
					$this->delurl = $GLOBALS['babUrlScript']."?tg=directory&idx=deldbc&id=".$id."&idu=".$idu;
					}

				$this->idu = $idu;
				$this->arrorgid = array();
				$this->resorg = $this->db->db_query("SELECT distinct oct.name, oct.id, oct.id_directory from ".BAB_ORG_CHARTS_TBL." oct left join ".BAB_OC_ROLES_TBL." ocrt on oct.id=ocrt.id_oc left join ".BAB_OC_ROLES_USERS_TBL." ocrut on ocrt.id=ocrut.id_role where ocrut.id_user='".$idu."'");
				while( $rr = $this->db->db_fetch_array($this->resorg))
					{
					if( bab_isAccessValid(BAB_OCVIEW_GROUPS_TBL, $rr['id']))
						{
						$this->arrorgid[] = array($rr['id'], $rr['name']);
						}
					}
				$this->orgcount = count($this->arrorgid);
				if( $this->orgcount > 0 )
					{
					$this->vieworg = bab_translate("View this organizational chart");
					$this->vieworgurl = $GLOBALS['babUrlScript']."?tg=chart&ocid=";
					}
				}
			else
				{
				$this->name = "";
				$this->urlimg = "";
				}

			if( !$update )
				{
				$this->modify = false;
				$this->del = false;
				}
			}
		
		function getnextfield()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $this->db->db_fetch_array($this->res);
				$this->fieldn = bab_translate($arr['description']);
				$this->fieldv = stripslashes($this->arr[$arr['name']]);
				if( strlen($this->arr[$arr['name']]) > 0 )
					$this->bfieldv = true;
				else
					$this->bfieldv = false;
				$i++;
				return true;
				}
			else
				return false;
			}

		function getnextorg()
			{
			static $i = 0;
			if( $i < $this->orgcount)
				{
				$this->orgid = $this->arrorgid[$i][0];
				$this->orgn = $this->arrorgid[$i][1];
				$res = $this->db->db_query("SELECT  ocrt.id_entity FROM ".BAB_OC_ROLES_TBL." ocrt LEFT JOIN ".BAB_OC_ROLES_USERS_TBL." ocrut ON ocrt.id = ocrut.id_role WHERE ocrut.id_user='".$this->idu."' and ocrt.id_oc='".$this->orgid."' and ocrut.isprimary='Y' ");
				if( $res && $this->db->db_num_rows($res) > 0 )
					{
					$arr = $this->db->db_fetch_array($res);
					$this->oeid = $arr['id_entity'];
					}
				else
					{
					$this->oeid = 0;
					}
				$i++;
				return true;
				}
			else
				return false;
			}
		}

	$temp = new temp($id, $idu, $update);
	echo bab_printTemplate($temp, "directory.html", "summarydbcontact");
}

?>