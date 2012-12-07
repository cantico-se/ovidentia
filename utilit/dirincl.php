<?php
//-------------------------------------------------------------------------
// OVIDENTIA http://www.ovidentia.org
// Ovidentia is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2, or (at your option)
// any later version.
//
// This program is distributed in the hope that it will be useful, but
// WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
// See the GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307,
// USA.
//-------------------------------------------------------------------------
/**
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 * @copyright Copyright (c) 2008 by CANTICO ({@link http://www.cantico.fr})
 */
/**
* @internal SEC1 NA 12/12/2006 FULL
*/
include_once 'base.php';


$babLdapServerTypes = array(BAB_LDAP_SERVER_OL => "OPENLDAP", BAB_LDAP_SERVER_AD => "ACTIVE DIRECTORY");

function getDirectoryName($id, $table)
	{
	global $babDB;
	$query = "select name from ".$table." where id='".$babDB->db_escape_string($id)."'";
	$res = $babDB->db_query($query);
	if( $res && $babDB->db_num_rows($res) > 0)
		{
		$arr = $babDB->db_fetch_array($res);
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
		if( mb_substr($txt, 0, 3) == "DF-" )
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
		var $sContent;
		
		function temp($id, $pos, $xf, $cb)
			{
			global $babDB;
			$this->allname	= bab_translate("All");
			$this->id		= bab_toHtml($id);
			$this->pos		= $pos;
			$this->badd		= false;
			$this->xf		= bab_toHtml($xf);
			$this->cb		= bab_toHtml($cb);
			$this->sContent	= 'text/html; charset=' . bab_charset::getIso();
			
			if( !empty($pos) && $pos[0] == "-" )
				{
				$this->pos = mb_strlen($pos) > 1? bab_toHtml($pos[1]): '';
				$this->ord = '';
				}
			else
				{
				$this->pos = bab_toHtml($pos);
				$this->ord = '-';
				}

			if( empty($pos))
				$this->allselected = 1;
			else
				$this->allselected = 0;
			$this->allurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=directory&idx=usdb&id=".$id."&pos=".($this->ord == "-"? "":$this->ord)."&xf=".$this->xf."&cb=".urlencode($cb));
			$this->count = 0;
			$arr = $babDB->db_fetch_array($babDB->db_query("select id_group from ".BAB_DB_DIRECTORIES_TBL." where id='".$babDB->db_escape_string($id)."'"));
			if(bab_isAccessValid(BAB_DBDIRVIEW_GROUPS_TBL, $id))
				{
				$this->idgroup = $arr['id_group'];
				$this->rescol = $babDB->db_query("select id, id_field from ".BAB_DBDIR_FIELDSEXTRA_TBL." where id_directory='".($this->idgroup != 0? 0: $babDB->db_escape_string($this->id))."' and ordering!='0' order by ordering asc");
				$this->countcol = $babDB->db_num_rows($this->rescol);
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
			global $babDB;
			static $i = 0;
			static $tmp = array();
			static $sqlf = array();
			static $leftjoin = array();
			if( $i < $this->countcol)
				{
				$arr = $babDB->db_fetch_array($this->rescol);
				if( $arr['id_field'] < BAB_DBDIR_MAX_COMMON_FIELDS )
					{
					$rr = $babDB->db_fetch_array($babDB->db_query("select name, description from ".BAB_DBDIR_FIELDS_TBL." where id='".$babDB->db_escape_string($arr['id_field'])."'"));
					$this->coltxt = bab_toHtml(translateDirectoryField($rr['description']));
					$filedname = $rr['name'];
					$tmp[] = $filedname;
					$this->select[] = 'e.'.$filedname;
					}
				else
					{
					$rr = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_DBDIR_FIELDS_DIRECTORY_TBL." where id='".$babDB->db_escape_string(($arr['id_field'] - BAB_DBDIR_MAX_COMMON_FIELDS))."'"));
					$this->coltxt = bab_toHtml(translateDirectoryField($rr['name']));
					$filedname = "babdirf".$arr['id'];
					$sqlf[] = $filedname;

					$leftjoin[] = 'LEFT JOIN '.BAB_DBDIR_ENTRIES_EXTRA_TBL.' lj'.$arr['id']." ON lj".$arr['id'].".id_fieldx='".$arr['id']."' AND e.id=lj".$arr['id'].".id_entry";
					$this->select[] = "lj".$arr['id'].'.field_value '."babdirf".$arr['id']."";
					}

				$this->colurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=directory&idx=usdb&id=".$this->id."&pos=".$this->ord.$this->pos."&xf=".$filedname."&cb=".urlencode($this->cb));
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
						$req = " ".BAB_DBDIR_ENTRIES_TBL." e ".implode(' ',$leftjoin).",
								".BAB_USERS_GROUPS_TBL." u  
									WHERE u.id_group='".$babDB->db_escape_string($this->idgroup)."' 
									AND u.id_object=e.id_user 
									AND e.id_directory='0'";
						}
					else
						{
						$req = " ".BAB_DBDIR_ENTRIES_TBL." e ".implode(' ',$leftjoin)." WHERE e.id_directory='".(1 == $this->idgroup ? 0 : $babDB->db_escape_string($this->id))."'";
						}

					$this->select[] = 'e.id';
					if( !in_array('email', $this->select))
						$this->select[] = 'e.email';

					if (!empty($this->pos) && false === mb_strpos($this->xf, 'babdirf'))
						$like = " AND `".$babDB->db_escape_string($this->xf)."` LIKE '".$babDB->db_escape_string($this->pos)."%'";
					elseif (0 === mb_strpos($this->xf, 'babdirf'))
						{
						$idfield = mb_substr($this->xf,7);
						$like = " AND lj".$idfield.".field_value LIKE '".$babDB->db_escape_string($this->pos)."%'";
						}
					else
						$like = '';

					$req = "select ".implode(',', $this->select)." from ".$req." ".$like." order by `".$babDB->db_escape_string($this->xf)."` ";
					if( $this->ord == "-" )
						{
						$req .= "asc";
						}
					else
						{
						$req .= "desc";
						}					
					

					$this->res = $babDB->db_query($req);				
					$this->count = $babDB->db_num_rows($this->res);
					}
				else
					$this->count = 0;

				return false;
				}
			}

		function getnext()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->count)
				{
				$this->altbg = $this->altbg ? false : true;
				$this->arrf = $babDB->db_fetch_array($this->res);
				$this->userid = bab_toHtml($this->arrf['id']);
				$this->firstlast = bab_composeUserName($this->arrf['givenname'], $this->arrf['sn']);
				$this->firstlast = bab_toHtml($this->firstlast, BAB_HTML_JS | BAB_HTML_ENTITIES);

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
				$this->coltxt = bab_toHtml(stripslashes(bab_translate($this->arrf[$i])));
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
				$this->selectname = mb_substr($t, $k, 1);
				$this->selecturl = bab_toHtml($GLOBALS['babUrlScript']."?tg=directory&idx=usdb&id=".$this->id."&pos=".($this->ord == "-"? "":$this->ord).$this->selectname."&xf=".$this->xf."&cb=".urlencode($this->cb));
				if( $this->pos == $this->selectname)
					{
					$this->selected = 1;
					}
				else
					{
					$this->selected = 0;
					}
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

/**
 * Return an array containing id of delegate groupe.
 * @param string 	$all 		If you want child groupe.
 * @param int 		$delegId 	Specify a delegation id.
 * @return array
 */
function bab_getDelegateGroupe($all = false, $delegId = false)
{
	global $babDB;
	
	$sql = "select id_group from ".BAB_DG_GROUPS_TBL." where id_group!='0'";
	if($delegId !== false){
		$sql .= " AND id = " . $babDB->quote($delegId);
	}
	
	$res = $babDB->db_query($sql);
	$id_group = array();
	while($arr = $babDB->db_fetch_array($res)){
		$id_group[$arr['id_group']] = $arr['id_group'];
		if($all){
			$subGroup = bab_getGroups($arr['id_group']);
			foreach($subGroup['id'] as $val){
				$id_group[$val] = $val;
			}
		}
	}
	return $id_group;
}


/**
 * Checks if the photo field is disabled.
 * 
 * @param int	$directoryId
 * @return bool
 */
function isPhotoDisabled($directoryId)
{
	global $babDB;

	list($groupId) = $babDB->db_fetch_array($babDB->db_query('SELECT id_group FROM '.BAB_DB_DIRECTORIES_TBL.' WHERE id=' . $babDB->quote($directoryId)));
	
	$directoryId = $groupId != 0 ? 0 : $directoryId;

	$fields = $babDB->db_query('SELECT e.disabled FROM '.BAB_DBDIR_FIELDSEXTRA_TBL.' e LEFT JOIN ' . BAB_DBDIR_FIELDS_TBL . ' f ON f.id=e.id_field WHERE f.name=\'jpegphoto\' AND e.id_directory=' . $babDB->quote($directoryId));
	$photoField = $babDB->db_fetch_assoc($fields);
	
	if ($photoField && $photoField['disabled'] == 'N') {
		return false;
	} 
	return true;
}


class bab_viewDirectoryUser
{

function bab_viewDirectoryUser($id)
	{
	global $babDB;
	
	$res = $babDB->db_query("select *, LENGTH(photo_data) as plen from ".BAB_DBDIR_ENTRIES_TBL." where id='".$babDB->db_escape_string($id)."'");
	$this->showph = false;
	$this->fields = array();
	$this->access = false;
	if( $res && $babDB->db_num_rows($res) > 0)
		{
		$arr = $babDB->db_fetch_array($res);
		$res = $babDB->db_query("select * from ".BAB_DBDIR_ENTRIES_EXTRA_TBL." where id_entry='".$babDB->db_escape_string($id)."'");
		while( $rr = $babDB->db_fetch_array($res))
			{
			$arr['babdirf'.$rr['id_fieldx']] = $rr['field_value'];
			}

		if( $arr['id_directory'] == 0 )
			{
			$res = $babDB->db_query("select id, id_group from ".BAB_DB_DIRECTORIES_TBL." where id_group != '0'");
			while( $row = $babDB->db_fetch_array($res))
				{
				list($bdir) = $babDB->db_fetch_array($babDB->db_query("select directory from ".BAB_GROUPS_TBL." where id='".$babDB->db_escape_string($row['id_group'])."'"));
				
				if( $bdir == 'Y' && bab_isAccessValid(BAB_DBDIRVIEW_GROUPS_TBL, $row['id']))
					{
					if( $row['id_group'] == 1)
						{
						$this->access = true;
						break;
						}
					$res2 = $babDB->db_query("select id from ".BAB_USERS_GROUPS_TBL." where id_object='".$babDB->db_escape_string($arr['id_user'])."' and id_group='".$babDB->db_escape_string($row['id_group'])."'");
					if( $res2 && $babDB->db_num_rows($res2) > 0 )
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
			$this->name = bab_toHtml($arr['givenname']). " ". bab_toHtml($arr['sn']);
			if( $arr['plen'] > 0 && !isPhotoDisabled($arr['id_directory']))
				{
				$this->showph = true;
				}

				
			$photo = new bab_dirEntryPhoto($id);
			$this->urlimg = bab_toHtml($photo->getUrl());

			$res = $babDB->db_query("select * from ".BAB_DBDIR_FIELDSEXTRA_TBL." where id_directory='".$babDB->db_escape_string($arr['id_directory'])."' AND disabled='N' order by list_ordering asc");
			while( $row = $babDB->db_fetch_array($res))
				{
				if( $row['id_field'] < BAB_DBDIR_MAX_COMMON_FIELDS )
					{
					$rr = $babDB->db_fetch_array($babDB->db_query("select name, description from ".BAB_DBDIR_FIELDS_TBL." where id='".$babDB->db_escape_string($row['id_field'])."'"));
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
					$rr = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_DBDIR_FIELDS_DIRECTORY_TBL." where id='".$babDB->db_escape_string(($row['id_field'] - BAB_DBDIR_MAX_COMMON_FIELDS))."'"));
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
			global $babDB;
			if (!bab_isAccessValid(BAB_DBDIRVIEW_GROUPS_TBL,$id))
				{
				die( bab_translate('Access denied') );
				}
			$this->t_print = bab_translate("Print");
			$this->t_delconf = bab_translate("Do you really want to delete the contact ?");

			list($idgroup, $bshowui) = $babDB->db_fetch_array($babDB->db_query("
				select id_group, show_update_info from ".BAB_DB_DIRECTORIES_TBL." where id='".$babDB->db_escape_string($id)."'
			"));
			
			list($allowuu) = $babDB->db_fetch_array($babDB->db_query("
				select user_update from ".BAB_DB_DIRECTORIES_TBL." where id_group=".$babDB->quote(BAB_REGISTERED_GROUP)."
			"));

			$this->res = $babDB->db_query("select * from ".BAB_DBDIR_FIELDSEXTRA_TBL." where id_directory='".($idgroup != 0? 0: $babDB->db_escape_string($id))."' AND disabled='N' order by list_ordering asc");
			if( $this->res && $babDB->db_num_rows($this->res) > 0)
				{
				$this->count = $babDB->db_num_rows($this->res);
				}
			else
				{
				$this->count = 0;
				}
			
			$res = $babDB->db_query("select *, LENGTH(photo_data) as plen from ".BAB_DBDIR_ENTRIES_TBL." where id_directory='".($idgroup != 0? 0: $babDB->db_escape_string($id))."' and id='".$babDB->db_escape_string($idu)."'");
			$this->showph = false;
			if( $res && $babDB->db_num_rows($res) > 0 )
				{
				$this->arr = $babDB->db_fetch_array($res);
				$res = $babDB->db_query("select * from ".BAB_DBDIR_ENTRIES_EXTRA_TBL." where id_entry='".$babDB->db_escape_string($idu)."'");
				while( $arr = $babDB->db_fetch_array($res))
					{
					$this->arr['babdirf'.$arr['id_fieldx']] = $arr['field_value'];
					}
				
				$this->name = stripslashes($this->arr['givenname']). " ". stripslashes($this->arr['sn']);
				$this->name = bab_toHtml($this->name);
				if( $this->arr['plen'] > 0 && !isPhotoDisabled($id) )
					{
					$this->showph = true;
					}

				$photo = new bab_dirEntryPhoto($idu);
				$this->urlimg = bab_toHtml($photo->getUrl());

				$this->unassign = bab_isAccessValid(BAB_DBDIRUNBIND_GROUPS_TBL, $id);
				$this->del = bab_isAccessValid(BAB_DBDIRDEL_GROUPS_TBL, $id);
				if( $idgroup == 0 )
					{
					$allowuu = "N";
					}

				$this->modify = bab_isAccessValid(BAB_DBDIRUPDATE_GROUPS_TBL, $id);
				
				if( $this->modify == false && $allowuu == "Y" && $this->arr['id_user'] == $GLOBALS['BAB_SESS_USERID'] )
					{
					$this->modify = true;
					}
				
				if( $this->modify == false )
					{
					while( $arr = $babDB->db_fetch_array($this->res))
						{
						if( bab_isAccessValid(BAB_DBDIRFIELDUPDATE_GROUPS_TBL, $arr['id']))
							{
							$this->modify = true;
							break;
							}
						}
					$babDB->db_data_seek($this->res, 0);
					}

				if( $this->modify )
					{
					$this->modifytxt = bab_translate("Modify");
					$this->modifyurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=directory&idx=dbmod&id=".$id."&idu=".$idu);
					}

				if( $this->del )
					{
					$this->deltxt = bab_translate("Delete");
					$this->delurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=directory&idx=deldbc&id=".$id."&idu=".$idu);
					}

				if( $this->unassign && $idgroup && $idgroup != BAB_REGISTERED_GROUP)
					{ 
					$this->unassigntxt = bab_translate("Unassign");
					$this->unassignurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=directory&idx=unassign&id=".$id."&idu=".$idu);
					$this->t_unassignconf = bab_translate("Do you really want to unassign this contact from the directory?");
					}
				else
					{
					$this->unassign = false;
					}

				$this->bshowupadetinfo = false;
				if( $bshowui == 'Y' && $this->arr['id_modifiedby'])
					{
					$this->bshowupadetinfo = true;
					$this->modifiedontxt = bab_translate("Update on");
					$this->bytxt = bab_translate("By");
					$this->updatedate = bab_toHtml(bab_shortDate(bab_mktime($this->arr['date_modification']), true));
					$this->updateauthor = bab_toHtml(bab_getUserName($this->arr['id_modifiedby']));
					}

				$this->idu = bab_toHtml($idu);
				$this->arrorgid = array();
				$this->resorg = $babDB->db_query("SELECT distinct oct.name, oct.id, oct.id_directory from ".BAB_ORG_CHARTS_TBL." oct left join ".BAB_OC_ROLES_TBL." ocrt on oct.id=ocrt.id_oc left join ".BAB_OC_ROLES_USERS_TBL." ocrut on ocrt.id=ocrut.id_role where ocrut.id_user='".$babDB->db_escape_string($idu)."'");
				while( $rr = $babDB->db_fetch_array($this->resorg))
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
				$this->name = '';
				$this->urlimg = '';
				}

			if( !$update )
				{
				$this->modify = false;
				$this->del = false;
				}
			}
		
		function getnextfield(&$skip)
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $babDB->db_fetch_array($this->res);
				if( $arr['id_field'] < BAB_DBDIR_MAX_COMMON_FIELDS )
					{
					$rr = $babDB->db_fetch_array($babDB->db_query("select name, description from ".BAB_DBDIR_FIELDS_TBL." where id='".$babDB->db_escape_string($arr['id_field'])."'"));
					$this->fieldn = bab_toHtml(translateDirectoryField($rr['description']));
					$this->fieldv = bab_toHtml($rr['name']);
					}
				else
					{
					$rr = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_DBDIR_FIELDS_DIRECTORY_TBL." where id='".$babDB->db_escape_string(($arr['id_field'] - BAB_DBDIR_MAX_COMMON_FIELDS))."'"));
					$this->fieldn = bab_toHtml(translateDirectoryField($rr['name']));
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
					$this->fieldv = bab_toHtml(stripslashes($this->arr[$this->fieldv]), BAB_HTML_ALL &~ BAB_HTML_P);
					}
				else
					{
					$this->fieldv = '';
					}

				if( mb_strlen($this->fieldv) > 0 )
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
			global $babDB;
			static $i = 0;
			if( $i < $this->orgcount)
				{
				$this->orgid = bab_toHtml($this->arrorgid[$i][0]);
				$this->orgn = bab_toHtml($this->arrorgid[$i][1]);
				$res = $babDB->db_query("SELECT  ocrt.id_entity FROM ".BAB_OC_ROLES_TBL." ocrt LEFT JOIN ".BAB_OC_ROLES_USERS_TBL." ocrut ON ocrt.id = ocrut.id_role WHERE ocrut.id_user='".$babDB->db_escape_string($this->idu)."' and ocrt.id_oc='".$babDB->db_escape_string($this->orgid)."' and ocrut.isprimary='Y' ");
				if( $res && $babDB->db_num_rows($res) > 0 )
					{
					$arr = $babDB->db_fetch_array($res);
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
	$babBody->babPopup(bab_printTemplate($temp, "directory.html", "summarydbcontact"));
}

function summaryDbContactWithOvml($args)
{
	global $babDB;


	if(bab_isAccessValid(BAB_DBDIRVIEW_GROUPS_TBL, $args['directoryid']))
		{
		$arr = $babDB->db_fetch_array($babDB->db_query("select ovml_detail from ".BAB_DB_DIRECTORIES_TBL." where id='".$babDB->db_escape_string($args['directoryid'])."'"));

		if (isset($args['id_user'])) {
			list($args['userid']) = $babDB->db_fetch_array($babDB->db_query("SELECT id FROM ".BAB_DBDIR_ENTRIES_TBL." WHERE id_user='".$babDB->db_escape_string($args['id_user'])."'"));
			}

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




/**
 * Object to retreive photo data or display photo
 * useable from a directory entry
 *
 * @package directories
 * @see getDirEntry()
 * @see	bab_SearchRealmDirectories
 */
class bab_dirEntryPhoto {

	private $id_entry = NULL;
	
	private $photo_data = NULL;
	private $photo_type = null;
	private $last_update = NULL;
	
	private $thumbWidth = null;
	private $thumbHeight = null;
	

	public function __construct($id_entry) {
		$this->id_entry = $id_entry;
	}
	
	
	public function setThumbSize($width, $height)
	{
		$this->thumbWidth = $width;
		$this->thumbHeight = $height;
	}


	/**
	 * Get Url to display image
	 */
	public function getUrl() {
		
		if ($T = @bab_functionality::get('Thumbnailer') && null !== $this->thumbWidth && null !== $this->thumbHeight)
		{
			/*@var $T Func_Thumbnailer */
			
			$data = $this->getData();
			
			if (null === $data || '' === $data)
			{
				$T->setSourceFile($GLOBALS['babSkinPath'].'/images/nophoto.jpg');
			} else {
				$T->setSourceBinary($data, $this->lastUpdate());
			}
			
			return $T->getThumbnail($this->thumbWidth, $this->thumbHeight);
		}
		
		
		return $GLOBALS['babUrlScript']."?tg=directory&idx=getimg&idu=".$this->id_entry;
	}

	public function __tostring() {
		return $this->getUrl();
	}
	

	public function setDataByFile($file) {
		global $babDB;
		if( !is_file($file))
		{
			return false;
		}

		$data = '';
		$fp=fopen($file,"rb");
		if( $fp )
			{
				while (!feof($fp)) {
					$data .= fread($fp,8192);
				}
			}
		fclose($fp);

		$type = bab_getFileMimeType($file);

		$babDB->db_query('
			UPDATE '.BAB_DBDIR_ENTRIES_TBL.' 
			SET photo_data='.$babDB->quote($data).', 
			photo_type='.$babDB->quote($type).' 
			WHERE 
			id='.$babDB->quote($this->id_entry)
			);

		return true;
	}

	public function setData($data, $type) {
		global $babDB;
		$babDB->db_query('
			UPDATE '.BAB_DBDIR_ENTRIES_TBL.' 
			SET photo_data='.$babDB->quote($data).', 
			photo_type='.$babDB->quote($type).' 
			WHERE 
			id='.$babDB->quote($this->id_entry)
			);
		return true;
	}
	
	public function getData() {
		global $babDB;
		
		if (NULL === $this->photo_data) {
			$res = $babDB->db_query('
				SELECT 
					photo_data, 
					photo_type, 
					date_modification 
				FROM 
					'.BAB_DBDIR_ENTRIES_TBL.' 
				WHERE 
					id='.$babDB->quote($this->id_entry)
			);
			
			$arr = $babDB->db_fetch_assoc($res);
			
			$this->photo_data = $arr['photo_data'];
			$this->photo_type = $arr['photo_type'];
			$this->last_update = $arr['date_modification'];
		}
		
		return $this->photo_data;
	}
	


	
	/**
	 * Last photo update date and time
	 * @return string	ISO datetime
	 */
	public function lastUpdate() {
		if (NULL === $this->last_update) {
			$this->getData();
		}
		
		return $this->last_update;
	}
	
	/**
	 * Get photo type
	 * @return string
	 */
	public function getType() {
		if (NULL === $this->photo_type) {
			$this->getData();
		}
		
		return $this->photo_type;
	}
}







/**
 * get a directory entry
 * @see bab_getUserInfos()
 * @see bab_admGetDirEntry()
 * @see bab_getDirEntry()
 *
 * @package directories
 * @return array | false
 */
function getDirEntry($id, $type, $id_directory, $accessCtrl) 
	{
	global $babDB;
	require_once dirname(__FILE__).'/iterator/iterator.php';

	if (BAB_DIR_ENTRY_ID_USER === $type && false === $id) {
		$id = &$GLOBALS['BAB_SESS_USERID'];
		}
		
	if (empty($id)) {
		return false;
		}

	if (NULL !== $id_directory) {
		$test_on_directory = '';
		}

	$accessible_directories = getUserDirectories($accessCtrl);


	switch ($type) {
		case BAB_DIR_ENTRY_ID_USER:
			$id_fieldextra_directory = 0;
			$colname = 'e.id_user';

			if ($id == $GLOBALS['BAB_SESS_USERID']) {
				break; // user can always view his dir entry
			}

			// for others users, acces rights are checked
			$access = false;
			
			foreach ($accessible_directories as $id_dir => $arr) {
				if ($arr['id_group'] == BAB_REGISTERED_GROUP) {
					$access = true;
					$id_directory = $id_dir;
					}
				}
			if (!$access)
				return array();

			break;

		case BAB_DIR_ENTRY_ID:
			$colname = 'e.id';

			if (NULL == $id_directory) {
				list($id_directory) = $babDB->db_fetch_array($babDB->db_query("SELECT id_directory FROM ".BAB_DBDIR_ENTRIES_TBL." WHERE id IN(".$id.")"));
				}
			$id_fieldextra_directory = $id_directory;
			break;

		case BAB_DIR_ENTRY_ID_DIRECTORY:
			$colname = 'e.id_directory';
			if (!isset($accessible_directories[$id]))
				return array();
			$id_directory = $accessible_directories[$id]['entry_id_directory'];
			$id_fieldextra_directory = $id_directory;
			break;

		case BAB_DIR_ENTRY_ID_GROUP:
			$id_fieldextra_directory = 0;	
			$colname = 'e.id_directory';
			$access = false;
			
			foreach ($accessible_directories as $id_dir => $arr) {
				if ($arr['id_group'] == $id) {
					$access = true;
					$id_directory = $id_dir;
					}
				}
			if (!$access)
				return array();
			
			break;

		}


	$res = $babDB->db_query("select 
			e.*, 
			v.field_value default_value_text 
		
	from ".BAB_DBDIR_FIELDSEXTRA_TBL." e 
		LEFT JOIN bab_dbdir_fieldsvalues v ON v.id=e.default_value 
			
	where 
			id_directory='".$babDB->db_escape_string($id_fieldextra_directory)."' 
	AND disabled='N' 
	order by list_ordering asc, v.field_value asc
	");

	$entries = array();
	$leftjoin = array();
	$leftjoin_col = array();
	
	while( $arr = $babDB->db_fetch_assoc($res))
		{
		if( $arr['id_field'] < BAB_DBDIR_MAX_COMMON_FIELDS )
			{
			
			$rr = $babDB->db_fetch_array($babDB->db_query("select description, name from ".BAB_DBDIR_FIELDS_TBL." where id='".$babDB->db_escape_string($arr['id_field'])."'"));
			$fieldname = $rr['name'];
			$entries[$fieldname] = array('name' => translateDirectoryField($rr['description']) , 'value' => '' );
		}
		else
			{
			$fieldname = "babdirf".$arr['id'];
			$rr = $babDB->db_fetch_array($babDB->db_query("select name from ".BAB_DBDIR_FIELDS_DIRECTORY_TBL." where id='".$babDB->db_escape_string(($arr['id_field'] - BAB_DBDIR_MAX_COMMON_FIELDS))."'"));
			$entries[$fieldname] = array('name' => translateDirectoryField($rr['name']) , 'value' => '' );

			$leftjoin[] = ' LEFT JOIN '.BAB_DBDIR_ENTRIES_EXTRA_TBL.' lj'.$arr['id']." ON lj".$arr['id'].".id_fieldx='".$arr['id']."' AND e.id=lj".$arr['id'].".id_entry";

			$leftjoin_col[] ='lj'.$arr['id'].'.field_value babdirf'.$arr['id'];
		}
			
		if (isset($arr['default_value_text']))
		{
			$entries[$fieldname]['default_value_text'] = $arr['default_value_text'];
		}
		
		if ('Y' === $arr['modifiable'] || bab_isAccessValid(BAB_DBDIRFIELDUPDATE_GROUPS_TBL, $arr['id']))
		{
			$entries[$fieldname]['modifiable'] = true;
		}
		
		if ('Y' === $arr['required'])
		{
			$entries[$fieldname]['required'] = true;
		}
		
		if ('Y' === $arr['multilignes'])
		{
			$entries[$fieldname]['multilignes'] = true;
		}
		
		if ('Y' === $arr['multi_values'])
		{
			$options = new bab_QueryIterator;
			$options->setQuery('SELECT * FROM bab_dbdir_fieldsvalues WHERE id_fieldextra='.$babDB->quote($arr['id']).' ORDER BY field_value ASC');
			
			$entries[$fieldname]['multi_values'] = array(
				'default_value' => (int) $arr['default_value'],
				'options' => $options
			);
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

	if (isset($test_on_directory)) {
		$test_on_directory = "AND id_directory='".$babDB->db_escape_string($id_directory)."'";
		} else {
		$test_on_directory = '';
		}
		

	$res = $babDB->db_query("
	
				SELECT  
					e.id,
					e.cn,
					e.sn,
					e.mn,
					e.givenname,
					e.email,
					e.btel,
					e.mobile,
					e.htel,
					e.bfax,
					e.title,
					e.departmentnumber,
					e.organisationname,
					e.bstreetaddress,
					e.bcity,
					e.bpostalcode,
					e.bstate,
					e.bcountry,
					e.hstreetaddress,
					e.hcity,
					e.hpostalcode,
					e.hstate,
					e.hcountry,
					e.user1,
					e.user2,
					e.user3,
					LENGTH(e.photo_data) photo_data, 
					e.id_user, 
					dis.disabled 
					".$str_leftjoin_col." 
					
				FROM 
					".BAB_DBDIR_ENTRIES_TBL." e 
					LEFT JOIN ".BAB_USERS_TBL." dis ON dis.id = e.id_user  
					".$str_leftjoin." 
				WHERE 
					".$colname." IN(".$babDB->quote($id).") ".$test_on_directory." 

	");


	$return = array();


	while( $arr = $babDB->db_fetch_assoc($res)) {
	
		if ($accessCtrl && 1 == $arr['disabled']) {
			continue;
		}
		
		$return[$arr['id_user']] = $entries;
		$id_user = $arr['id_user'];

		foreach($return[$arr['id_user']] as $name => $field) {
			
		if (isset($arr[$name])) {
			$return[$arr['id_user']][$name]['value'] = $arr[$name];
			}
		elseif ('jpegphoto' == $name && $arr['photo_data'] > 0) {

			$photo = new bab_dirEntryPhoto($arr['id']);

			$return[$arr['id_user']][$name]['value'] = $photo->getUrl();
			$return[$arr['id_user']][$name]['photo'] = $photo;
			}
		}
	}

	return 1 === count($return) ? $return[$id_user] : $return;
}


/**
 * get a list of directories
 * For each directory, you will get an array with keys : 
 * <ul>
 * 	<li>id : the ID in table BAB_DB_DIRECTORIES_TBL</li>
 *  <li>name</li>
 *  <li>description</li>
 *  <li>entry_id_directory : each entry in this directory will contain the value in the id_directory column, > 0 if the directory is not a group directory</li>
 *  <li>id_group : each entry in this directory will contain the value in the id_group column, > 0 if the directory is a group directory</li>
 * </ul>
 * @package directories
 * @param	bool			$accessCtrl		test access rights on directories, right by default
 * @param	int | false		$delegationid	filter the result by delegation
 * @return array							each key of the returned array is an id_directory
 */
function getUserDirectories($accessCtrl = true, $delegationid = false)
	{
	global $babDB;

	$return = array();

	$req = "
	SELECT 
		d.id, 
		d.name, 
		d.description, 
		d.id_group 
	FROM ".BAB_DB_DIRECTORIES_TBL." d 
		LEFT JOIN ".BAB_GROUPS_TBL." g ON g.id=d.id_group AND g.directory='Y' 
		WHERE (d.id_group='0' OR g.id>'0') 
	";

	if (false !== $delegationid) {
		$req .= ' AND d.id_dgowner='.$babDB->quote($delegationid);
	}

	$req .= ' ORDER BY d.name';

	$res = $babDB->db_query($req);

	$return = array();
		
	while( $row = $babDB->db_fetch_assoc($res))
		{
		if(!$accessCtrl || bab_isAccessValid(BAB_DBDIRVIEW_GROUPS_TBL, $row['id']))
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





/**
 * get a list of LDAP directories
 * For each directory, you will get an array with keys : 
 * <ul>
 * 	<li>id : the ID in table BAB_LDAP_DIRECTORIES_TBL</li>
 *  <li>name</li>
 *  <li>description</li>
 * </ul>
 * @package directories
 * @param	bool			$accessCtrl		test access rights on directories, right by default
 * @param	int | false		$delegationid	filter the result by delegation
 * @return array							each key of the returned array is an id_ldap_directory (same as the id key)
 */
function getUserLdapDirectories($accessCtrl = true, $delegationid = false)
	{
	global $babDB;
	
	$req = '
		SELECT id,name,description FROM 
			'.BAB_LDAP_DIRECTORIES_TBL.' 
	';

	if ($delegationid) {
		$req .= ' WHERE id_dgowner='.$babDB->quote($delegationid);
	}

	$req .= ' ORDER BY name';
	$res = $babDB->db_query($req);
	
	$return = array();

	while ($row = $babDB->db_fetch_assoc($res)) {
		if(!$accessCtrl || bab_isAccessValid(BAB_LDAPDIRVIEW_GROUPS_TBL, $row['id']))
			{
			$return[$row['id']] = array(
				'id'					=> $row['id'],
				'name'					=> $row['name'],
				'description'			=> $row['description']
			);
		}
	}

	return $return;
	}



/**
 * Get all directories mixed
 * For each directory, you will get an array with keys : 
 * <ul>
 * 	<li>name : name of the directory</li>
 *  <li>description</li>
 *  <li>url : for directory visualisation</li>
 *  <li>uid : sitemap UID</li>
 * </ul>
 * @package directories
 * @param	bool			$accessCtrl		test access rights on directories, right by default
 * @param	int | false		$delegationid	filter the result by delegation
 * @return array							
 */
function getUserDirectoriesMixed($accessCtrl = true, $delegationid = false) {

	$directories 		= getUserDirectories(true, $delegationid);
	$ldapdirectories 	= getUserLdapDirectories(true, $delegationid);

	if( !$directories && !$ldapdirectories ) {
		return array();
	}

	$dg_prefix = false === $delegationid ? 'bab' : 'babDG'.$delegationid;

	$alldirectories = array();

	foreach($directories as $id_directory => $dir) {
		$alldirectories[] = array(
			'name' 			=> $dir['name'],
			'url'			=> $GLOBALS['babUrlScript'].'?tg=directory&idx=sdbovml&directoryid='.$id_directory,
			'uid' 			=> $dg_prefix.'UserDbDirId'.$id_directory,
			'description' 	=> $dir['description']
		);
	}

	foreach($ldapdirectories as $id_directory => $dir) {
		$alldirectories[] = array(
			'name' 			=> $dir['name'],
			'url'			=> $GLOBALS['babUrlScript'].'?tg=directory&idx=sldap&id='.$id_directory,
			'uid' 			=> $dg_prefix.'UserLdapDirId'.$id_directory,
			'description' 	=> $dir['description']
		);
	}

	bab_sort::asort($alldirectories, 'name', bab_sort::CASE_INSENSITIVE);

	return $alldirectories;
}








/**
 * @see bab_getUserDirEntryLink
 * @package directories
 * @return string | false
 */
function getUserDirEntryLink($id, $type, $id_directory) {

	if (BAB_DIR_ENTRY_ID_USER === $type && false === $id) {
		$id = &$GLOBALS['BAB_SESS_USERID'];
		}

	$accessible_directories = getUserDirectories();


	if (false === $id_directory) {
		foreach($accessible_directories as $iddir => $arr) {
			if (BAB_REGISTERED_GROUP == $arr['id_group']) {
					$id_directory = $iddir;
					break;
				}
			}
		}

	if (!isset($accessible_directories[$id_directory]))
		return false;


	switch ($type) {
		case BAB_DIR_ENTRY_ID_USER:
			return $GLOBALS['babUrlScript']."?tg=directory&idx=ddbovml&directoryid=".$id_directory."&id_user=".$id;

		case BAB_DIR_ENTRY_ID:
			return $GLOBALS['babUrlScript']."?tg=directory&idx=ddbovml&directoryid=".$id_directory."&userid=".$id;
	}
}





/**
 * return an array of directory entries using a search on fields
 * @see bab_searchDirEntriesByField
 *
 * @param	int		[$id_directory]		the id of the directory
 * @param	array	[$likefields]		array of filed/like string ( array('sn' => 'admin', 'email'=> '%cantico.fr', 'babdirf27'=>'123') for example )
 * @param	bool	[$and]				true to use AND operator / false for OR operator
 * @return 	array
 */
function searchDirEntriesByField($id_directory, $likefields, $and = true) {

	include_once dirname(__FILE__).'/searchapi.php';
	$realm = bab_Search::getRealm('bab_SearchRealmDirectories');
	$realm->setAccessRightsVerification(false);
	$realm->setDirectory($id_directory);

	$operator = $and ? '_AND_' : '_OR_';
	$criteria = $realm->getDefaultCriteria();

	// add filters
	foreach($likefields as $fieldname => $filter) {
		$first = mb_substr($filter, 0,1);
		$last = mb_substr($filter, -1);

		if ('%' === $first && '%' === $last) {
			$new_criterion = $realm->$fieldname->contain(mb_substr($filter, 1, -1));

		} else if ('%' === $first) {
			$new_criterion = $realm->$fieldname->endWith(mb_substr($filter, 1));
			
		} else if ('%' === $last) {
			$new_criterion = $realm->$fieldname->startWith(mb_substr($filter,0, -1));

		} else {
			$new_criterion = $realm->$fieldname->like($filter);
		}

		$criteria = $criteria->$operator($new_criterion);
	}

	$return = array();

	foreach($realm->search($criteria) as $record) {
		$return[$record->id] = array();
		foreach($realm->getFields() as $field) {
			if ($field->searchable()) {
				$fieldname = $field->getName();
				$return[$record->id][$fieldname] = array(
					'name' => $field->getDescription(),
					'value' => $record->$fieldname
				);
			}
		}
	}

	return $return;
}











/**
 * Get a list of id field used as default for search result headers
 * @return array
 */
function bab_getDirectorySearchDefaultHeaders() {

	global $babDB;

	list($search_view_fields) = $babDB->db_fetch_array($babDB->db_query("SELECT search_view_fields FROM ".BAB_DBDIR_OPTIONS_TBL.""));

	if (empty($search_view_fields))
		$search_view_fields = '2,4';
		
	return explode(',',$search_view_fields);
}







/**
 * Get fields for multiple directories, return only enabled fields
 * @param	array	$directories
 * @package directories
 * @return array
 */
function bab_getDirectoriesFields($directories) {

	global $babDB;
	$return = array();

	$groups_directories = array();
	$filter = array();
	$all = getUserDirectories(false);

		

	foreach($directories as $id) {
		if (isset($all[$id])) {
			$entry_id_directory = $all[$id]['entry_id_directory'];
			$filter[$entry_id_directory] = $entry_id_directory;

			if (0 === $entry_id_directory) {
				$groups_directories[] = $id;
			}
		}
	}


	$req = "select id, id_field, id_directory from ".BAB_DBDIR_FIELDSEXTRA_TBL." 
			WHERE disabled='N' AND id_directory IN(".$babDB->quote($filter).")";

	$rescol = $babDB->db_query($req);

	while( $row = $babDB->db_fetch_assoc($rescol))
		{

		
		
		$id_directory 	= (int) $row['id_directory'];
		$id_field 		= (int) $row['id_field'];

		// if field exists, just add link to directory

		if (isset($return[$id_field])) {
			if ($id_directory) {
				$return[$id_field]['directories'][] = $id_directory;
			} else {
				$return[$id_field]['directories'] += $groups_directories;
			}
			continue;
		}




		if($id_field < BAB_DBDIR_MAX_COMMON_FIELDS )
			{
			$rr = $babDB->db_fetch_array(
				$babDB->db_query("select name, description from ".BAB_DBDIR_FIELDS_TBL." 
					where id=".$babDB->quote($id_field))
			);
			$return[$id_field] = array(
				'id'			=> $row['id'],
				'name' 			=> $rr['name'],
				'description'	=> translateDirectoryField($rr['description']),
				'table'			=> BAB_DBDIR_ENTRIES_TBL
				);
		}
		else
			{
			$rr = $babDB->db_fetch_array(
				$babDB->db_query("select * from ".BAB_DBDIR_FIELDS_DIRECTORY_TBL." 
					where id=".$babDB->quote($id_field - BAB_DBDIR_MAX_COMMON_FIELDS)."")
			);
			
			$return[$id_field] = array(
				'id'			=> $row['id'],
				'name' 			=> 'babdirf'.$row['id'],
				'description'	=> translateDirectoryField($rr['name']),
				'table'			=> BAB_DBDIR_ENTRIES_EXTRA_TBL
				);
				
		}

		if ($id_directory) {
			$return[$id_field]['directories'] = array(0 => $id_directory);
		} else {
			$return[$id_field]['directories'] = $groups_directories;
		}
	}

	return $return;
}





/**
 * Get search list headers for a directory or default settings
 * @param	int	$id_directory
 * @package directories
 * @return array
 */
function bab_getDirectorySearchHeaders($id_directory = null) {
		
	global $babDB;
	
	$return = array();

	if (null === $id_directory)
		{
		// all directories

		$ids = bab_getDirectorySearchDefaultHeaders();

		$names = array();
		$descriptions = array();
		$rescol = $babDB->db_query("select * from ".BAB_DBDIR_FIELDS_TBL." where id IN(".$babDB->quote($ids).")");
		while( $row3 = $babDB->db_fetch_array($rescol)) {
			$names[$row3['id']] = $row3['name'];
			$descriptions[$row3['id']] = translateDirectoryField($row3['description']);	
		}

		foreach($ids as $id) {
			$return[$names[$id]] = $descriptions[$id];
		}

		return $return;
	}




	// un seul annuaire
	$row = $babDB->db_fetch_array($babDB->db_query("SELECT * FROM ".BAB_DB_DIRECTORIES_TBL." WHERE id=".$babDB->quote($id_directory).""));
	$req = "
		select * 
			from ".BAB_DBDIR_FIELDSEXTRA_TBL." 

		where 
			id_directory='".($row['id_group'] != 0? 0: $row['id'])."' and ordering!='0' 
		order by ordering asc
	";
	$rescol = $babDB->db_query($req);

	while( $row3 = $babDB->db_fetch_array($rescol))
		{
		if( $row3['id_field'] < BAB_DBDIR_MAX_COMMON_FIELDS )
			{
			$rr = $babDB->db_fetch_array(
				$babDB->db_query("select name, description from ".BAB_DBDIR_FIELDS_TBL." where id=".$babDB->quote($row3['id_field']))
			);
			$return[$rr['name']] = translateDirectoryField($rr['description']);
		}
		else
			{
			$rr = $babDB->db_fetch_array(
				$babDB->db_query("select * from ".BAB_DBDIR_FIELDS_DIRECTORY_TBL." where id=".$babDB->quote($row3['id_field'] - BAB_DBDIR_MAX_COMMON_FIELDS)."")
			);
			
			$return["babdirf".$row3['id']] = translateDirectoryField($rr['name']);
		}					
	}

	return $return;
}