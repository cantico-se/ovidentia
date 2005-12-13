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

function translateDirectoryField($field)
	{
		$txt = bab_translate("DF-".$field);
		if( substr($txt, 0, 3) == "DF-" )
		{
			return bab_translate($field);
		}
		return $txt;
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
				$this->rescol = $this->db->db_query("select id, id_field from ".BAB_DBDIR_FIELDSEXTRA_TBL." where id_directory='".($this->idgroup != 0? 0: $this->id)."' and ordering!='0' order by ordering asc");
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
			static $sqlf = array();
			static $leftjoin = array();
			if( $i < $this->countcol)
				{
				$arr = $this->db->db_fetch_array($this->rescol);
				if( $arr['id_field'] < BAB_DBDIR_MAX_COMMON_FIELDS )
					{
					$rr = $this->db->db_fetch_array($this->db->db_query("select name, description from ".BAB_DBDIR_FIELDS_TBL." where id='".$arr['id_field']."'"));
					$this->coltxt = translateDirectoryField($rr['description']);
					$filedname = $rr['name'];
					$tmp[] = $filedname;
					$this->select[] = 'e.'.$filedname;
					}
				else
					{
					$rr = $this->db->db_fetch_array($this->db->db_query("select * from ".BAB_DBDIR_FIELDS_DIRECTORY_TBL." where id='".($arr['id_field'] - BAB_DBDIR_MAX_COMMON_FIELDS)."'"));
					$this->coltxt = translateDirectoryField($rr['name']);
					$filedname = "babdirf".$arr['id'];
					$sqlf[] = $filedname;

					$leftjoin[] = 'LEFT JOIN '.BAB_DBDIR_ENTRIES_EXTRA_TBL.' lj'.$arr['id']." ON lj".$arr['id'].".id_fieldx='".$arr['id']."' AND e.id=lj".$arr['id'].".id_entry";
					$this->select[] = "lj".$arr['id'].'.field_value '."babdirf".$arr['id']."";
					}

				$this->colurl = $GLOBALS['babUrlScript']."?tg=directory&idx=usdb&id=".$this->id."&pos=".$this->ord.$this->pos."&xf=".$filedname."&cb=".$this->cb;
				$i++;
				return true;
				}
			else
				{
				if( count($tmp) > 0 || count($sqlf) > 0)
					{
					$tmp[] = "id";
					if( $this->xf == "" )
						{
						$this->xf = $tmp[0];
						}

					if( $this->idgroup > 1 )
						{
						$req = " ".BAB_DBDIR_ENTRIES_TBL." e,
								".BAB_USERS_GROUPS_TBL." u ".implode(' ',$leftjoin)." 
									WHERE u.id_group='".$this->idgroup."' 
									AND u.id_object=e.id_user 
									AND e.id_directory='0'";
						}
					else
						{
						$req = " ".BAB_DBDIR_ENTRIES_TBL." e ".implode(' ',$leftjoin)." WHERE e.id_directory='".(1 == $this->idgroup ? 0 : $this->id )."'";
						}

					$this->select[] = 'e.id';
					if( !in_array('email', $this->select))
						$this->select[] = 'e.email';

					if (!empty($this->pos) && false === strpos($this->xf, 'babdirf'))
						$like = " AND `".$this->xf."` LIKE '".$this->pos."%'";
					elseif (0 === strpos($this->xf, 'babdirf'))
						{
						$idfield = substr($this->xf,7);
						$like = " AND lj".$idfield.".field_value LIKE '".$this->pos."%'";
						}
					else
						$like = '';

					$req = "select ".implode(',', $this->select)." from ".$req." ".$like." order by `".$this->xf."` ";
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
	$this->fields = array();
	$this->access = false;
	if( $res && $this->db->db_num_rows($res) > 0)
		{
		$arr = $this->db->db_fetch_array($res);
		$res = $this->db->db_query("select * from ".BAB_DBDIR_ENTRIES_EXTRA_TBL." where id_entry='".$id."'");
		while( $rr = $this->db->db_fetch_array($res))
			{
			$arr['babdirf'.$rr['id_fieldx']] = $rr['field_value'];
			}

		if( $arr['id_directory'] == 0 )
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
					$res2 = $this->db->db_query("select id from ".BAB_USERS_GROUPS_TBL." where id_object='".$arr['id_user']."' and id_group='".$row['id_group']."'");
					if( $res2 && $this->db->db_num_rows($res2) > 0 )
						{
						$this->access = true;
						break;
						}
					}

				}
			}
		else if( bab_isAccessValid(BAB_DBDIRVIEW_GROUPS_TBL, $arr['id_directory']))
			{
			$this->access = true;
			}

		if( $this->access )
			{
			$this->name = $arr['givenname']. " ". $arr['sn'];
			if( $arr['plen'] > 0 )
				{
				$this->showph = true;
				}


			$this->urlimg = $GLOBALS['babUrlScript']."?tg=directory&idx=getimg&id=".$arr['id_directory']."&idu=".$id;

			$res = $this->db->db_query("select * from ".BAB_DBDIR_FIELDSEXTRA_TBL." where id_directory='".$arr['id_directory']."' order by list_ordering asc");
			while( $row = $this->db->db_fetch_array($res))
				{
				if( $row['id_field'] < BAB_DBDIR_MAX_COMMON_FIELDS )
					{
					$rr = $this->db->db_fetch_array($this->db->db_query("select name, description from ".BAB_DBDIR_FIELDS_TBL." where id='".$row['id_field']."'"));
					if( $rr['name'] != 'jpegphoto' )
						{
						if ('email' == $rr['name'])
							{
							$this->fields[] = array('name' => translateDirectoryField($rr['description']), 'value' => stripslashes($arr[$rr['name']]), 'email' => true);
							}
						else
							{
							$this->fields[] = array('name' => translateDirectoryField($rr['description']), 'value' => stripslashes($arr[$rr['name']]));
							}
						
						}
					}
				else
					{
					$rr = $this->db->db_fetch_array($this->db->db_query("select * from ".BAB_DBDIR_FIELDS_DIRECTORY_TBL." where id='".($row['id_field'] - BAB_DBDIR_MAX_COMMON_FIELDS)."'"));
					$this->fields[] = array('name' => translateDirectoryField($rr['name']), 'value' => isset($arr["babdirf".$row['id']]) ? stripslashes($arr["babdirf".$row['id']]): '');
					}
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
			if (!bab_isAccessValid(BAB_DBDIRVIEW_GROUPS_TBL,$id))
				{
				die( bab_translate('Access denied') );
				}
			$this->t_print = bab_translate("Print");
			$this->t_delconf = bab_translate("Do you really want to delete the contact ?");

			$this->db = &$GLOBALS['babDB'];
			list($idgroup, $allowuu) = $this->db->db_fetch_array($this->db->db_query("select id_group, user_update from ".BAB_DB_DIRECTORIES_TBL." where id='".$id."'"));

			$this->res = $this->db->db_query("select * from ".BAB_DBDIR_FIELDSEXTRA_TBL." where id_directory='".($idgroup != 0? 0: $id)."' AND disabled='N' order by list_ordering asc");
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
			if( $res && $this->db->db_num_rows($res) > 0 )
				{
				$this->arr = $this->db->db_fetch_array($res);
				$res = $this->db->db_query("select * from ".BAB_DBDIR_ENTRIES_EXTRA_TBL." where id_entry='".$idu."'");
				while( $arr = $this->db->db_fetch_array($res))
					{
					$this->arr['babdirf'.$arr['id_fieldx']] = $arr['field_value'];
					}
				
				$this->name = stripslashes($this->arr['givenname']). " ". stripslashes($this->arr['sn']);
				if( $this->arr['plen'] > 0 )
					{
					$this->showph = true;
					}

				
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
		
		function getnextfield(&$skip)
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $this->db->db_fetch_array($this->res);
				if( $arr['id_field'] < BAB_DBDIR_MAX_COMMON_FIELDS )
					{
					$rr = $this->db->db_fetch_array($this->db->db_query("select name, description from ".BAB_DBDIR_FIELDS_TBL." where id='".$arr['id_field']."'"));
					$this->fieldn = translateDirectoryField($rr['description']);
					$this->fieldv = $rr['name'];
					}
				else
					{
					$rr = $this->db->db_fetch_array($this->db->db_query("select * from ".BAB_DBDIR_FIELDS_DIRECTORY_TBL." where id='".($arr['id_field'] - BAB_DBDIR_MAX_COMMON_FIELDS)."'"));
					$this->fieldn = translateDirectoryField($rr['name']);
					$this->fieldv = "babdirf".$arr['id'];
					}

				if( $this->fieldv == 'jpegphoto' )
					{
					$skip = true;
					$i++;
					return true;
					}

				if( isset($this->arr[$this->fieldv]) )
					{
					$this->fieldv = nl2br(stripslashes($this->arr[$this->fieldv]));
					}
				else
					{
					$this->fieldv = '';
					}

				if( strlen($this->fieldv) > 0 )
					{
					$this->bfieldv = true;
					}
				else
					{
					$this->bfieldv = false;
					}
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

function summaryDbContactWithOvml($args)
{
	global $babDB;


	if(bab_isAccessValid(BAB_DBDIRVIEW_GROUPS_TBL, $args['directoryid']))
		{
		$arr = $babDB->db_fetch_array($babDB->db_query("select ovml_detail from ".BAB_DB_DIRECTORIES_TBL." where id='".$args['directoryid']."'"));

		if( !empty($arr['ovml_detail']))
			{
			echo bab_printOvmlTemplate( $arr['ovml_detail'], $args );
			}
		else
			{
			summaryDbContact($args['directoryid'], $args['userid']);
			}
		}
}



function getDirEntry($id, $type) {
	$babDB = &$GLOBALS['babDB'];

	

	$accessible_directories = getUserDirectories();


	switch ($type) {
		case BAB_DIR_ENTRY_ID_USER:
			$id_directory = 0;	
			$colname = 'id_user';
			if (is_array($id))
				$id = implode("','",$id);
			break;

		case BAB_DIR_ENTRY_ID:
			$colname = 'id';
			if (is_array($id))
				$id = implode("','",$id);
			break;

		case BAB_DIR_ENTRY_ID_DIRECTORY:
			$colname = 'id_directory';
			if (!isset($accessible_directories[$id]))
				return array();
			$id_directory = $accessible_directories[$id]['entry_id_directory'];
			break;

		case BAB_DIR_ENTRY_ID_GROUP:
			$colname = 'id_directory';
			$access = false;
			
			foreach ($accessible_directories as $id_dir => $arr) {
				if ($arr['id_group'] == $id) {
					$access = true;
					}
				}
			if (!$access)
				return array();
			$id_directory = 0;
			break;

		}


	$res = $babDB->db_query("select * from ".BAB_DBDIR_FIELDSEXTRA_TBL." where id_directory='".$id_directory."' AND disabled='N' order by list_ordering asc");

	$entries = array();
	$leftjoin = array();
	$leftjoin_col = array();
	
	while( $arr = $babDB->db_fetch_assoc($res))
		{
		if( $arr['id_field'] < BAB_DBDIR_MAX_COMMON_FIELDS )
			{
			$rr = $babDB->db_fetch_array($babDB->db_query("select description, name from ".BAB_DBDIR_FIELDS_TBL." where id='".$arr['id_field']."'"));
			$entries[$rr['name']] = array('name' => translateDirectoryField($rr['description']) , 'value' => '' );
			}
		else
			{
			$rr = $babDB->db_fetch_array($babDB->db_query("select name from ".BAB_DBDIR_FIELDS_DIRECTORY_TBL." where id='".($arr['id_field'] - BAB_DBDIR_MAX_COMMON_FIELDS)."'"));
			$entries["babdirf".$arr['id']] = array('name' => translateDirectoryField($rr['name']) , 'value' => '' );

			$leftjoin[] = ' LEFT JOIN '.BAB_DBDIR_ENTRIES_EXTRA_TBL.' lj'.$arr['id']." ON lj".$arr['id'].".id_fieldx='".$arr['id']."' AND e.id=lj".$arr['id'].".id_entry";

			$leftjoin_col[] ='lj'.$arr['id'].'.field_value babdirf'.$arr['id'];
			}
		}

	

	if (BAB_DIR_ENTRY_ID_DIRECTORY === $type || BAB_DIR_ENTRY_ID_GROUP == $type ) {
		
		return $entries;
		}

	
	$str_leftjoin = '';
	$str_leftjoin_col = '';
	
	if (count($leftjoin_col) > 0) {
		$str_leftjoin = implode(' ',$leftjoin);
		$str_leftjoin_col = ', '.implode(', ',$leftjoin_col);
		}

	$res = $babDB->db_query("
	
				SELECT  
					e.id,	
					cn,
					sn,
					mn,
					givenname,
					email,
					btel,
					mobile,
					htel,
					bfax,
					title,
					departmentnumber,
					organisationname,
					bstreetaddress,
					bcity,
					bpostalcode,
					bstate,
					bcountry,
					hstreetaddress,
					hcity,
					hpostalcode,
					hstate,
					hcountry,
					user1,
					user2,
					user3,
					LENGTH(photo_data) photo_data, 
					id_user 
					".$str_leftjoin_col."
				FROM 
					".BAB_DBDIR_ENTRIES_TBL." e 
					".$str_leftjoin." 
				WHERE 
					".$colname." IN('".$id."')

	");


	$return = array();


	while( $arr = $babDB->db_fetch_assoc($res))
		{
		$return[$arr['id_user']] = $entries;
		$id_user = $arr['id_user'];

		foreach($return[$arr['id_user']] as $name => $field) {
			
			 if (isset($arr[$name])) {
				$return[$arr['id_user']][$name]['value'] = stripslashes($arr[$name]);
				}
			 elseif ('jpegphoto' == $name && $arr['photo_data'] > 0) {
				$return[$arr['id_user']][$name]['value'] = $GLOBALS['babUrlScript']."?tg=directory&idx=getimg&id=0&idu=".$arr['id'];
				}
			}
		}

	return 1 === count($return) ? $return[$id_user] : $return;
}


function getUserDirectories() {
	$db = &$GLOBALS['babDB'];

	$return = array();
	
	$res = $db->db_query("SELECT d.id, d.name, d.description, d.id_group FROM ".BAB_DB_DIRECTORIES_TBL." d 
	LEFT JOIN ".BAB_GROUPS_TBL." g ON g.id=d.id_group AND g.directory='Y' WHERE (d.id_group='0' OR g.id>'0') ORDER BY name");
	while( $row = $db->db_fetch_array($res))
		{
		if(bab_isAccessValid(BAB_DBDIRVIEW_GROUPS_TBL, $row['id']))
			{
			$return[$row['id']] = array(
					'id'					=> $row['id'],
					'name'					=> $row['name'],
					'description'			=> $row['description'],
					'entry_id_directory'	=> $row['id_group'] > 0 ? 0 : $row['id'],
					'id_group'				=> $row['id_group']
				);
			}
		}
	return $return;
	}



?>