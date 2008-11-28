<?php
//-------------------------------------------------------------------------
// OVIDENTIA http://www.ovidentia.org
//
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
include_once 'base.php';
include_once $babInstallPath.'utilit/orgincl.php';



function bab_embeddedContactWithOvml($ocid, $oeid, $userid, $access)
{
	global $babDB;
	global $babLittleBody;

	
	// We check if an ovml file has been specified for the embedded user view has been specified. 
	$sql = 'SELECT ovml_embedded
			FROM '.BAB_ORG_CHARTS_TBL.'
			WHERE id='.$babDB->quote($ocid);
	$arr = $babDB->db_fetch_array($babDB->db_query($sql));


	if (!empty($arr['ovml_embedded'])) {
		
		if (empty($userid)) {
			include_once $GLOBALS['babInstallPath'].'utilit/ocapi.php';
			
			$members = bab_OCselectEntityCollaborators($oeid);
			if ($members && ($member = $babDB->db_fetch_array($members))) {
				$userid = $member['id_dir_entry'];
				$directoryid = $member['id_directory'];
			}
		} else {
			include_once $GLOBALS['babInstallPath'].'utilit/dirincl.php';

			$sql = 'SELECT id_directory
					FROM '.BAB_DBDIR_ENTRIES_TBL.'
					WHERE id='.$babDB->quote($userid);
			$entries = $babDB->db_fetch_array($babDB->db_query($sql));
			$directoryid = $entries['id_directory'];
		}
		if ($directoryid == 0) {
			$sql = 'SELECT id
					FROM '.BAB_DB_DIRECTORIES_TBL.'
					WHERE id_group='.$babDB->quote(BAB_REGISTERED_GROUP);
			$directories = $babDB->db_fetch_array($babDB->db_query($sql));
			$directoryid = $directories['id'];
		}
		
		$args = array(
				'ocid' => $ocid,
				'entityid' => $oeid,
				'userid' => $userid,
				'directoryid' => $directoryid
		);
		
		$babLittleBody->babecho(bab_printOvmlTemplate($arr['ovml_embedded'], $args));
		return $userid;
		
	} else {
		// Here we don't use ovml to display the user info.
		return viewOrgChartRoleDetail($ocid, $oeid, $userid, $access);
	}
}


function bab_popupContactWithOvml($ocid, $oeid, $userid, $access)
{
	global $babDB;

	
	// We check if an ovml file has been specified for the embedded user view has been specified. 
	$sql = 'SELECT ovml_detail
			FROM '.BAB_ORG_CHARTS_TBL.'
			WHERE id='.$babDB->quote($ocid);
	$arr = $babDB->db_fetch_array($babDB->db_query($sql));


		
	if (empty($userid)) {
		include_once $GLOBALS['babInstallPath'].'utilit/ocapi.php';
		
		$members = bab_OCselectEntityCollaborators($oeid);
		if ($members && ($member = $babDB->db_fetch_array($members))) {
			$userid = $member['id_dir_entry'];
			$directoryid = $member['id_directory'];
		}
	} else {
		include_once $GLOBALS['babInstallPath'].'utilit/dirincl.php';

		$sql = 'SELECT id_directory
				FROM '.BAB_DBDIR_ENTRIES_TBL.'
				WHERE id='.$babDB->quote($userid);
		$entries = $babDB->db_fetch_array($babDB->db_query($sql));
		$directoryid = $entries['id_directory'];
	}
	if ($directoryid == 0) {
		$sql = 'SELECT id
				FROM '.BAB_DB_DIRECTORIES_TBL.'
				WHERE id_group='.$babDB->quote(BAB_REGISTERED_GROUP);
		$directories = $babDB->db_fetch_array($babDB->db_query($sql));
		$directoryid = $directories['id'];
	}
	
	$args = array(
			'ocid' => $ocid,
			'entityid' => $oeid,
			'userid' => $userid,
			'directoryid' => $directoryid
	);

	
	if (!empty($arr['ovml_detail'])) {
		echo(bab_printOvmlTemplate($arr['ovml_detail'], $args));
		return $userid;
	} else {
		include_once $GLOBALS['babInstallPath'].'utilit/dirincl.php';
		summaryDbContactWithOvml($args);
	}
}




function listOrgChartRoles($ocid, $oeid, $iduser)
	{
	global $babLittleBody;

	class temp
		{
		var $title;
		var $titlename;
		var $urltitle;

		var $res;
		var $count;

		function temp($ocid, $oeid, $iduser)
			{
			global $babDB, $babBody;

			$this->superiortxt = bab_translate("Superior");
			$this->temporarytxt = bab_translate("Temporary employee");
			$this->collaboratortxt = bab_translate("Collaborators");
			$this->ocid = $ocid;
			$this->oeid = $oeid;
			$this->iduser = $iduser;
			list($idrole, $rolename) = $babDB->db_fetch_row($babDB->db_query("select id, name from ".BAB_OC_ROLES_TBL." where id_entity='".$oeid."' and id_oc='".$this->ocid."' and type='1'"));
			$res = $babDB->db_query("select det.sn, det.givenname, det.id as id_entry, ort.* from ".BAB_OC_ROLES_USERS_TBL." ort left join ".BAB_DBDIR_ENTRIES_TBL." det on det.id=ort.id_user where ort.id_role='".$idrole."'");
			$this->bsuperior = false;
			if( $res && $babDB->db_num_rows($res) > 0 )
				{
				$arr = $babDB->db_fetch_array($res);
				$this->superiorentity = $rolename ;
				if( $arr['sn'])
					{
					$this->superiortitle = bab_composeUserName($arr['givenname'],$arr['sn']) ;
					$this->superiorurl = $GLOBALS['babUrlScript']."?tg=fltchart&idx=detr&ocid=".$ocid."&oeid=".$oeid."&iduser=".$arr['id_entry'];
					if( $arr['id_entry'] == $iduser )
						{
						$this->bsuperior = true;
						}
					}
				else
					{
					$this->superiortitle = "";
					}
				}
			else
				{
				$this->superiortitle = "";
				}
			list($idrole, $rolename) = $babDB->db_fetch_row($babDB->db_query("select id, name from ".BAB_OC_ROLES_TBL." where id_entity='".$oeid."' and id_oc='".$this->ocid."' and type='2'"));
			$res = $babDB->db_query("select det.sn, det.givenname, det.id as id_entry, ort.* from ".BAB_OC_ROLES_USERS_TBL." ort left join ".BAB_DBDIR_ENTRIES_TBL." det on det.id=ort.id_user where ort.id_role='".$idrole."'");
			$this->btemporary = false;
			if( $res && $babDB->db_num_rows($res) > 0 )
				{
				$arr = $babDB->db_fetch_array($res);
				$this->temporaryentity = $rolename ;
				if( $arr['sn'])
					{
					$this->temporarytitle = bab_composeUserName($arr['givenname'],$arr['sn']) ;
					$this->temporaryurl = $GLOBALS['babUrlScript']."?tg=fltchart&idx=detr&ocid=".$ocid."&oeid=".$oeid."&iduser=".$arr['id_entry'];
					if( $arr['id_entry'] == $iduser )
						{
						$this->btemporary = true;
						}
					}
				else
					{
					$this->temporarytitle = "";
					}
				}
			else
				{
				$this->temporarytitle = "";
				}

			$this->resroles = $babDB->db_query("select id, name from ".BAB_OC_ROLES_TBL." where id_entity='".$oeid."' and type NOT IN (1,2)");
			$this->countroles = $babDB->db_num_rows($this->resroles);
			$this->altbg = false;
			if( $babBody->nameorder[0] == 'F' )
				{
				$this->orderby = "order by det.givenname asc";
				}
			else
				{
				$this->orderby = "order by det.sn asc";
				}
			}

		function getnextrole()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->countroles)
				{
				$arr = $babDB->db_fetch_array($this->resroles);
				$this->collaboratorentity = $arr['name'];
				$this->altbg = !$this->altbg;
				$this->res = $babDB->db_query("select det.sn, det.givenname, det.id as id_entry, ort.* from ".BAB_OC_ROLES_USERS_TBL." ort left join ".BAB_DBDIR_ENTRIES_TBL." det on det.id=ort.id_user where ort.id_role='".$arr['id']."' ".$this->orderby);
				$this->count = $babDB->db_num_rows($this->res);
				$i++;
				return true;
				}
			else
				{
				$i = 0;
				return false;
				}

			}

		function getnext()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $babDB->db_fetch_array($this->res);
				$this->buser = false;
				if( $arr['sn'] )
					{
					$this->collaboratortitle = bab_composeUserName($arr['givenname'],$arr['sn']);
					if( $arr['id_entry'] == $this->iduser )
						{
						$this->buser = true;
						}
					}
				else
					{
					$this->collaboratortitle = "";
					}
				$this->collaboratorurl = $GLOBALS['babUrlScript']."?tg=fltchart&idx=detr&ocid=".$this->ocid."&oeid=".$this->oeid."&iduser=".$arr['id_entry'];
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

	$temp = new temp($ocid, $oeid, $iduser);
	$babLittleBody->babecho( bab_printTemplate($temp,"fltchart.html", "roleslist"));
	}

function viewOrgChartRoleMore($ocid, $oeid, $iduser, $update)
	{
	global $babLittleBody;

	class temp
		{

		function temp($ocid, $oeid, $iduser, $update)
			{
			global $babDB;
			$this->ocid = $ocid;
			$this->oeid = $oeid;
			$this->iduser = $iduser;
			$this->primaryrole = bab_translate("Principal role");
			$this->altbg = false;

			if( $update)
				{
				$this->update = true;
				$this->updatename = bab_translate("Update");
				$this->updateurl = $GLOBALS['babUrlScript']."?tg=fltchart&idx=updr&ocid=".$this->ocid."&oeid=".$this->oeid."&iduser=".$this->iduser;
				}
			else
				{
				$this->update = false;
				}
			$this->username = bab_getDbUserName($iduser);
			$this->res = $babDB->db_query("select ocet.*, ocrt.name as r_name, ocrut.id as id_ru, ocrut.isprimary as isprimary from ".BAB_OC_ROLES_USERS_TBL." ocrut left join ".BAB_OC_ROLES_TBL." ocrt on ocrut.id_role=ocrt.id LEFT JOIN ".BAB_OC_ENTITIES_TBL." ocet on ocrt.id_entity=ocet.id where ocrut.id_user='".$iduser."' and ocrt.id_oc='".$this->ocid."' ");
			$this->entities = array();
			while( $row = $babDB->db_fetch_array($this->res) )
				{
				if( !isset($this->entities[$row['id']]))
					{
					$this->entities[$row['id']] = array('name' => $row['name']);
					}
				$this->entities[$row['id']]['roles'][] = array($row['id_ru'], $row['r_name'], $row['isprimary']);
				}
			$this->count = count($this->entities);
			if( $this->count > 0 )
				{
				reset($this->entities);
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

		function getnextrole()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->countroles)
				{
				$this->roleid = $this->roles[$i][0];
				$this->role = $this->roles[$i][1];
				if( $this->roles[$i][2] == 'Y')
					{
					$this->rchecked = 'checked';
					}
				else
					{
					$this->rchecked = '';
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

	$temp = new temp($ocid, $oeid, $iduser, $update);
	$babLittleBody->babecho( bab_printTemplate($temp,"fltchart.html", "usermore"));
	}


function viewOrgChartRoleDetail($ocid, $oeid, $iduser, $access)
	{
	global $babLittleBody;
	include_once $GLOBALS['babInstallPath']."utilit/dirincl.php";
	include_once $GLOBALS['babInstallPath'].'utilit/ocapi.php';
	
	class temp extends bab_viewDirectoryUser
		{

		function temp($ocid, $oeid, $iduser, $access)
			{
			global $babDB;
			$this->access = $access;

			if( empty($iduser))
			{
				//$members = bab_selectEntityMembers($ocid, $oeid);
				$members = bab_OCselectEntityCollaborators($oeid);
				if ($members && ($member = $babDB->db_fetch_array($members))) {
					$iduser = $member['id_dir_entry'];
				}
			}
			if( !empty($iduser))
				{
				$this->bab_viewDirectoryUser($iduser);
				}
			else
				{
				$this->access = false;
				}
			$this->iduser = $iduser;
			$this->altbg = false;
			if( !$this->access )
				{
				$this->showph = true;
				$this->urlimg = $GLOBALS['babSkinPath']."/images/nophoto.jpg";
				}
			}

		function getnextfield()
			{
			static $i = 0;
			if( $i < count($this->fields))
				{
				$this->fieldn = $this->fields[$i]['name'];
				$this->fieldv = $this->fields[$i]['value'];
				$this->mailto = isset($this->fields[$i]['email']) ? $this->fields[$i]['email'] : false;
				if( strlen($this->fieldv) > 0 )
					$this->bfieldv = true;
				else
					$this->bfieldv = false;
				$this->altbg = !$this->altbg;
				$i++;
				return true;
				}
			else
				{
				return false;
				}
			}
		}

	$temp = new temp($ocid, $oeid, $iduser, $access);
	$babLittleBody->babecho( bab_printTemplate($temp,"fltchart.html", "userdetail"));
	return $temp->iduser;
	}


function updateOrgChartPrimaryRoleUser($ocid, $oeid, $iduser, $prole)
{
	global $babDB;

	if( bab_isAccessValid(BAB_OCUPDATE_GROUPS_TBL, $ocid))
	{
		$res = $babDB->db_query("select ocrut.id from  ".BAB_OC_ROLES_USERS_TBL." ocrut left join ".BAB_OC_ROLES_TBL." ocrt on ocrut.id_role=ocrt.id where ocrt.id_oc='".$ocid."' and  ocrut.id_user='".$iduser."' and ocrut.isprimary='Y'");
		if( $res && $babDB->db_num_rows($res) > 0 )
		{
			while($row = $babDB->db_fetch_array($res))
			{
				$babDB->db_query("update ".BAB_OC_ROLES_USERS_TBL." set isprimary='N' where id='".$row['id']."'");
			}
		}
	
	$babDB->db_query("update ".BAB_OC_ROLES_USERS_TBL." set isprimary='Y' where id='".$prole."'");
	}

	Header("Location: ". $GLOBALS['babUrlScript']."?tg=fltchart&idx=more&ocid=".$ocid."&oeid=".$oeid."&iduser=".$iduser);
}





/* main */
$babLittleBody = new babLittleBody();
$babLittleBody->frrefresh = isset($rf)?$rf: false;
$access = false;
$update = false;

if( bab_isAccessValid(BAB_OCUPDATE_GROUPS_TBL, $ocid))
{
	$ocinfo = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_ORG_CHARTS_TBL." where id='".$ocid."'"));
	if( $ocinfo['edit'] == 'Y' && $ocinfo['edit_author'] == $BAB_SESS_USERID)
	{
		$update = true;
	}
	$access = true;
}
elseif( bab_isAccessValid(BAB_OCVIEW_GROUPS_TBL, $ocid) )
{
	$access = true;
}



if( !$access )
{
	echo bab_translate("Access denied");
	return;
}

if( $oeid )
{
$oeinfo = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_OC_ENTITIES_TBL." where id='".$oeid."'"));
chart_session_oeid($ocid);
}

if( !isset($idx) || empty($idx)) { $idx = "listr"; }

if( isset($updr) && $updr == "updr" && $update)
{
	updateOrgChartPrimaryRoleUser($ocid, $oeid, $iduser, $prole);
}

if (!isset($iduser))
	$iduser = 0;

switch($idx)
	{
	case "updu":
		if( !$update )
		{
		Header("Location: ". $GLOBALS['babUrlScript']."?tg=fltchart&idx=listr&ocid=".$ocid."&oeid=".$oeid);
		exit;
		}
		$babLittleBody->title = '';
		$babLittleBody->addItemMenu("detr", bab_translate("Detail"), $GLOBALS['babUrlScript']."?tg=fltchart&idx=detr&ocid=".$ocid."&oeid=".$oeid."&iduser=".$iduser);
		$babLittleBody->addItemMenu("more", bab_translate("Roles"), $GLOBALS['babUrlScript']."?tg=fltchart&idx=more&ocid=".$ocid."&oeid=".$oeid."&iduser=".$iduser);
		$babLittleBody->addItemMenu("updu", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=fltchart&idx=updu&ocid=".$ocid."&oeid=".$oeid."&iduser=".$iduser);
		$babLittleBody->setCurrentItemMenu($idx);
		viewOrgChartRoleUpdate($ocid, $oeid, $iduser);
		break;

	case "detr":
		$babLittleBody->title = '';
		$babLittleBody->addItemMenu("detr", bab_translate("Detail"), $GLOBALS['babUrlScript']."?tg=fltchart&idx=detr&ocid=".$ocid."&oeid=".$oeid."&iduser=".$iduser);
		$iduser = bab_embeddedContactWithOvml($ocid, $oeid, $iduser, $access);
		if( $access && $oeid)
			{
			$babLittleBody->addItemMenu("more", bab_translate("Roles"), $GLOBALS['babUrlScript']."?tg=fltchart&idx=more&ocid=".$ocid."&oeid=".$oeid."&iduser=".$iduser);
			}
		$babLittleBody->setCurrentItemMenu($idx);
		break;

	case "detrpopup":
		$iduser = bab_popupContactWithOvml($ocid, $oeid, $iduser, $access);
		break;
		
	case "more":
		$babLittleBody->title = '';
		$babLittleBody->addItemMenu("detr", bab_translate("Detail"), $GLOBALS['babUrlScript']."?tg=fltchart&idx=detr&ocid=".$ocid."&oeid=".$oeid."&iduser=".$iduser);
		if( $oeid )
		{
			$babLittleBody->addItemMenu("more", bab_translate("Roles"), $GLOBALS['babUrlScript']."?tg=fltchart&idx=more&ocid=".$ocid."&oeid=".$oeid);
		}
		if( $update )
		{
			$babLittleBody->addItemMenu("updu", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=flbchart&idx=updu&ocid=".$ocid."&oeid=".$oeid."&iduser=".$iduser);
		}
		$babLittleBody->setCurrentItemMenu($idx);
		viewOrgChartRoleMore($ocid, $oeid, $iduser, $update);
		break;

	case "listr":
	default:
		$babLittleBody->title = isset($oeinfo['name'])? $oeinfo['name']: '';
		$babLittleBody->addItemMenu("listr", bab_translate("Roles"), $GLOBALS['babUrlScript']."?tg=fltchart&idx=listr&ocid=".$ocid."&oeid=".$oeid);
		$babLittleBody->setCurrentItemMenu($idx);
		if( $oeid )
		{
		if( !isset($iduser)) { $iduser = 0;}
		listOrgChartRoles($ocid, $oeid, $iduser);
		}
		break;
	}
printFlbChartPage();
exit;
