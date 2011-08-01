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

/**
* @internal SEC1 PR 27/02/2007 FULL
*/

include_once "base.php";
require_once dirname(__FILE__).'/utilit/registerglobals.php';
include_once $babInstallPath."utilit/vacincl.php";



function entities($u_entities, $template)
{
global $babBody;

	class temp
		{
		var $altbg = true;

		function temp($entities)
			{
			
			$this->all_manager = false;
			$this->entities = array();
			while (list(,$arr) = each($entities))
				{
				if (!isset($arr['comanager'])) {
					$this->all_manager = true;
				}

				if (!isset($this->entities[$arr['id']])) {
					$this->entities[$arr['id']] = $arr;
				}
				$arr2 = bab_OCGetChildsEntities($arr['id']);
				for ($i = 0 ; $i < count($arr2) ; $i++) {
					if (isset($arr['comanager'])) {
						$arr2[$i]['comanager'] = 1;
					}

					if (!isset($this->entities[$arr2[$i]['id']])) {
						$this->entities[$arr2[$i]['id']] = $arr2[$i];
					}
				}
			}
			
			if (count($this->entities) > 0)
				$this->entities = & $this->_array_sort($this->entities, 'name');

			$this->t_name = bab_translate('Name');
			$this->t_description = bab_translate('Description');
			$this->t_members = bab_translate('Members');
			$this->t_calendar = bab_translate('Planning');
			$this->t_requests = bab_translate('Requests');
			$this->t_planning = bab_translate('Planning acces');
			$this->t_comanager = bab_translate('Co-managers');
			}

		
		function _array_sort($array, $key)
			{
			   foreach($array as $k => $val) {
				   $sort_values[$k] = $val[$key];
			   }

			   bab_sort::natcasesort($sort_values);
			   reset($sort_values);

				foreach($sort_values as $k => $val) {
					 $sorted_arr[] = $array[$k];
			   }

			   return $sorted_arr;
			}

		function getnext()
			{
			if (list(,$this->arr) = each($this->entities))
				{
				$this->manager 				= !isset($this->arr['comanager']);
				$this->altbg 				= !$this->altbg;
				$this->arr['name'] 			= bab_toHtml($this->arr['name']);
				$this->arr['description'] 	= bab_toHtml($this->arr['description']);
				return true;
				}
			else
				return false;
			}
		}

	
	
	switch ($template)
	{
	case 'planning':
		global $babDB;
		$res =$babDB->db_query("SELECT id_entity FROM ".BAB_VAC_PLANNING_TBL." WHERE id_user='".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."'");
		$entities = array();
		while ($arr = $babDB->db_fetch_assoc($res))
			{
			$ent = bab_OCGetEntity($arr['id_entity']);
			$entities[] = array(
							'id' => $arr['id_entity'],
							'name' => $ent['name'],
							'description' => $ent['description']
							);
			}

		$entities = array_intersect($entities, $u_entities);
		break;
	default:
		$entities = $u_entities['superior'];
		break;
	}

	$temp = new temp($entities);
	$babBody->babecho(bab_printTemplate($temp, "vacchart.html", $template));
	
}


function entity_members($ide, $template)
{
	global $babBody;

	class temp
		{
		var $altbg = true;

		function temp($ide)
			{
			$this->ide = $ide;
			$users = bab_OCGetCollaborators($ide);
			$superior = bab_OCGetSuperior($ide);
			$this->superior_id = 0;
			if ($superior !== 0 )
				{
				$this->superior_id = $superior['id_user'];
				$this->superior_name = bab_toHtml($superior['lastname'].' '.$superior['firstname']);
				}
			$this->b_rights = $this->superior_id != $GLOBALS['BAB_SESS_USERID'];
			
			// si co-gestionnaire de cette entité, pas de droit sur le suppérieur

			if (bab_isAccessibleEntityAsCoManager($this->ide)) {
				$this->b_rights = false;
			}
			
			$this->t_name = bab_translate('Name');
			$this->t_calendar = bab_translate('Planning');
			$this->t_rights = bab_translate('Rights');
			$this->t_asks = bab_translate('Requests');
			$this->t_view_calendar = bab_translate('View calendars');
			$this->t_collection = bab_translate('Collection');
			$this->t_schema = bab_translate('Approbation schema');
			$this->t_request = bab_translate('Request');
			$this->t_viewrights = bab_translate('Balance');
			$this->checkall = bab_translate('Check all');
			$this->uncheckall = bab_translate('Uncheck all');

			$this->requests = bab_getVacationOption('chart_superiors_create_request');
			
			$this->users = array();
			
			while (list(,$arr) = each($users))
				{
				if ($arr['id_user'] != $this->superior_id)
					{
					$this->users[$arr['id_user']] = $arr['lastname'].' '.$arr['firstname'];
					}
				}
			bab_sort::natcasesort($this->users);

			if (count($this->users) > 0)
				{
				$tmp = array_keys($this->users);
				$tmp[] = $this->superior_id;
				}
			elseif (!empty($this->superior_id))
				{
				$tmp = array($this->superior_id);
				}
			else
				$tmp = array();


			if (count($tmp) > 0)
				{
				$this->more = array();

				global $babDB;
				$req = "SELECT p.id_user,c.name coll,f.name sa FROM ".BAB_VAC_PERSONNEL_TBL." p LEFT JOIN ".BAB_VAC_COLLECTIONS_TBL." c ON c.id=p.id_coll LEFT JOIN ".BAB_FLOW_APPROVERS_TBL." f ON f.id=p.id_sa WHERE p.id_user IN(".$babDB->quote($tmp).")";
				$res = $babDB->db_query($req);
				while ($arr = $babDB->db_fetch_array($res))
					{
					$this->more[$arr['id_user']] = array( $arr['coll'], $arr['sa'] );
					}
				}

			$this->s_collection = '';
			$this->s_schema = '';
			if ($superior !== 0 && isset($this->more[$this->superior_id]))
				{
				list($this->s_collection, $this->s_schema ) = $this->more[$this->superior_id] ;
				$this->s_collection = bab_toHtml($this->s_collection);
				$this->s_schema = bab_toHtml($this->s_schema);
				}
			}

		function getnext()
			{
			if (list($this->id_user,$this->name) = each($this->users))
				{
				$this->altbg = !$this->altbg;
				$this->b_rights = $this->id_user != $GLOBALS['BAB_SESS_USERID'];
				$this->collection = isset($this->more[$this->id_user][0]) ? bab_toHtml($this->more[$this->id_user][0]) : '';
				$this->schema = isset($this->more[$this->id_user][1]) ? bab_toHtml($this->more[$this->id_user][1]) : '';
				$this->name = bab_toHtml($this->name);
				
				return true;
				}
			else
				return false;
			}
		}

	$temp = new temp($ide);
	$babBody->babecho(bab_printTemplate($temp, "vacchart.html", $template));
	
}


function entity_cal($ide )
{
	
	global $babDB, $babBody;

	$users = bab_OCGetCollaborators($ide);
	$superior = bab_OCGetSuperior($ide);

	$tmp = array();
	foreach ($users as $user)
		{
		$tmp[$user['id_user']] = $user['id_user'];
		}

	if (!isset($tmp[$superior['id_user']]) && !empty($superior['id_user']))
		$tmp[$superior['id_user']] = $superior['id_user'];


	viewVacationCalendar(array_keys($tmp));

}

function entity_users($ide)
{
	$users = bab_OCGetCollaborators($ide);
	$superior = bab_OCGetSuperior($ide);

	$tmp = array();
	foreach ($users as $user)
		{
		$tmp[$user['id_user']] = $user['id_user'];
		}

	if (!isset($tmp[$superior['id_user']]) && !empty($superior['id_user']))
		$tmp[$superior['id_user']] = $superior['id_user'];

	return array_keys($tmp);
}

function entity_requests($ide )
{
	listVacationRequests(entity_users($ide));
}



function entity_planning($ide)
{
	$e =  bab_OCGetEntity($ide);
	$GLOBALS['babBody']->setTitle(bab_translate("Planning acces").' : '.$e['name']);

	include_once $GLOBALS['babInstallPath'].'utilit/selectusers.php';
	global $babBody, $babDB;
	$obj = new bab_selectusers();
	$obj->addVar('ide', $ide);
	$res = $babDB->db_query("SELECT id_user FROM ".BAB_VAC_PLANNING_TBL." WHERE id_entity=".$babDB->quote($ide));
	while (list($id) = $babDB->db_fetch_array($res))
		{
		$obj->addUser($id);
		}
	$obj->setRecordCallback('savePlanning');
	$babBody->babecho($obj->getHtml());
}


function entity_comanager($ide) {
	$e =  bab_OCGetEntity($ide);
	$GLOBALS['babBody']->setTitle(bab_translate("Co-managers").' : '.$e['name']);

	include_once $GLOBALS['babInstallPath'].'utilit/selectusers.php';
	global $babBody, $babDB;
	$obj = new bab_selectusers();
	$obj->addVar('ide', $ide);
	$res = $babDB->db_query("SELECT id_user FROM ".BAB_VAC_COMANAGER_TBL." WHERE id_entity=".$babDB->quote($ide));
	while (list($id) = $babDB->db_fetch_array($res))
		{
		$obj->addUser($id);
		}
	$obj->setRecordCallback('saveCoManager');
	$babBody->babecho($obj->getHtml());

}


function viewVacUserDetails($ide, $id_user) {

	global $babBody;

	class temp
		{
		function temp($ide, $id_user)
			{
			$this->ide = $ide;
			$this->id_user = $id_user;
			$this->b_rights = $id_user != $GLOBALS['BAB_SESS_USERID'];

			$this->t_modify		= bab_translate("Modify");
			$this->t_collection = bab_translate("Collection");
			$this->t_schema		= bab_translate("Schema");
			$this->collection	= '';
			$this->schema		= '';

			global $babDB;
			$req = "SELECT c.name coll,f.name sa FROM ".BAB_VAC_PERSONNEL_TBL." p LEFT JOIN ".BAB_VAC_COLLECTIONS_TBL." c ON c.id=p.id_coll LEFT JOIN ".BAB_FLOW_APPROVERS_TBL." f ON f.id=p.id_sa WHERE p.id_user=".$babDB->quote($this->id_user);

			$res = $babDB->db_query($req);
			$arr = $babDB->db_fetch_assoc($res);

			$this->collection	= bab_toHtml($arr['coll']);
			$this->schema		= bab_toHtml($arr['sa']);

			}
		}

	$temp = new temp($ide, $id_user);
	$babBody->babecho(bab_printTemplate($temp, "vacchart.html", 'user_details'));

}




function savePlanning($userids, $params)
{
	$ide = $params['ide'];
	global $babDB;
	$babDB->db_query("DELETE FROM ".BAB_VAC_PLANNING_TBL." WHERE id_entity = ".$babDB->quote($ide));

	foreach ($userids as $uid)
	{
		$babDB->db_query("INSERT INTO ".BAB_VAC_PLANNING_TBL." (id_user, id_entity) VALUES ('".$babDB->db_escape_string($uid)."','".$babDB->db_escape_string($ide)."')");
	}
	
	header('location:'.$GLOBALS['babUrlScript']."?tg=vacchart&idx=entities");
	exit;
}


function saveCoManager($userids, $params) {

	$ide = $params['ide'];
	global $babDB;
	$babDB->db_query("DELETE FROM ".BAB_VAC_COMANAGER_TBL." WHERE id_entity = ".$babDB->quote($ide));

	foreach ($userids as $uid)
	{
		$babDB->db_query("INSERT INTO ".BAB_VAC_COMANAGER_TBL." (id_user, id_entity) VALUES ('".$babDB->db_escape_string($uid)."','".$babDB->db_escape_string($ide)."')");
	}
	
	header('location:'.$GLOBALS['babUrlScript']."?tg=vacchart&idx=entities");
	exit;
}


// main
$userentities = & bab_OCGetUserEntities($GLOBALS['BAB_SESS_USERID']);
bab_addCoManagerEntities($userentities, $GLOBALS['BAB_SESS_USERID']);
$entities_access = count($userentities['superior']);



$idx = isset($_REQUEST['idx']) ? $_REQUEST['idx'] : '';


if( isset($_POST['add']) && $entities_access > 0 )
	{
	switch($_POST['add'])
		{
		case 'modrbu':
			if ( bab_IsUserUnderSuperior($_POST['iduser']) )
				{
				updateVacationRightByUser($_POST['iduser'], $_POST['quantities'], $_POST['idrights']);
				}
			break;

		case 'changeuser':
			if (!empty($_POST['idp']))
				{
				if(updateVacationPersonnel($_POST['idp'], $_POST['idsa']))
					{
					$idx ='changeucol';
					}
				else
					{
					$idx ='modp';
					}
				}
			else
				{
				if( !saveVacationPersonnel($_POST['userid'], $_POST['idcol'], $_POST['idsa']))
					{
					$idx ='addp';
					}
				}
			break;
		

		case 'changeucol':
			if (!updateUserColl())
				$idx = $add;
			break;
		}
	}

$babBody->addItemMenu("vacuser", bab_translate("Vacations"), $GLOBALS['babUrlScript']."?tg=vacuser");

switch($idx)
	{
	case 'lper':
		$idx = 'entity_members';
	case 'entity_members':
		$babBody->title = bab_translate("Entity members");
		$babBody->addItemMenu("entities", bab_translate("Delegate management"), $GLOBALS['babUrlScript']."?tg=vacchart&idx=entities");
		$babBody->addItemMenu("entity_members", bab_translate("Entity members"), $GLOBALS['babUrlScript']."?tg=vacchart&idx=entity_members");
		if ($entities_access > 0)
			entity_members($_REQUEST['ide'], 'entity_members');
		else
			{
			$babBody->title = bab_translate("Access denied");
			}
		break;

	case 'planning_members':
		if (bab_isPlanningAccessValid())
			{
			$babBody->title = bab_translate("Entity members");
			$babBody->addItemMenu("planning", bab_translate("Planning"), $GLOBALS['babUrlScript']."?tg=vacchart&idx=planning");
			$babBody->addItemMenu("planning_members", bab_translate("Entity members"), $GLOBALS['babUrlScript']."?tg=vacchart&idx=planning_members");
			entity_members($_REQUEST['ide'], 'planning_members');
			}
		else
			{
			$babBody->title = bab_translate("Access denied");
			}
		break;

	case 'entity_cal':
		$babBody->addItemMenu("entities", bab_translate("Delegate management"), $GLOBALS['babUrlScript']."?tg=vacchart&idx=entities");
		if ($entities_access > 0 || bab_isPlanningAccessValid($_REQUEST['ide']))
			entity_cal($_REQUEST['ide']);
		else
			{
			$babBody->title = bab_translate("Access denied");
			}
		break;

	case 'rights':
		$babBody->addItemMenu("entities", bab_translate("Delegate management"), $GLOBALS['babUrlScript']."?tg=vacchart&idx=entities");
		if (bab_IsUserUnderSuperior($_GET['id_user']) && $_GET['id_user'] != $GLOBALS['BAB_SESS_USERID'])
			{
			listRightsByUser($_GET['id_user']);
			exit;
			}
		else
			{
			$babBody->title = bab_translate("Access denied");
			}
		break;

	case "rlbuul":
		rlistbyuserUnload(bab_translate("Your request has been updated"));
		exit;

	case 'asks':
		$babBody->addItemMenu("entity_members", bab_translate("Entity members"), $GLOBALS['babUrlScript']."?tg=vacchart&idx=entity_members&ide=".$_GET['ide']);
		if (bab_IsUserUnderSuperior($_GET['id_user']))
			{
			$babBody->addItemMenu("entities", bab_translate("Delegate management"), $GLOBALS['babUrlScript']."?tg=vacchart&idx=entities");

			$babBody->title = bab_translate("Vacation requests list");
			$babBody->addItemMenu("asks", bab_translate("Requests"), $GLOBALS['babUrlScript']."?tg=vacchart&idx=asks");
			listVacationRequests($_GET['id_user']);
			}
		else
			{
			$babBody->title = bab_translate("Access denied");
			}
		break;

	case 'entity_requests':

		if ($entities_access > 0)
			{
			$babBody->addItemMenu("entities", bab_translate("Delegate management"), $GLOBALS['babUrlScript']."?tg=vacchart&idx=entities");

			$babBody->addItemMenu("entity_requests", bab_translate("Requests"), $GLOBALS['babUrlScript']."?tg=vacchart&idx=entity_requests");
			$babBody->title = bab_translate("Vacation requests list");
			entity_requests($_GET['ide']);
			}
		else
			{
			$babBody->title = bab_translate("Access denied");
			}
		break;

	case 'entity_planning':
		$babBody->addItemMenu("entities", bab_translate("Delegate management"), $GLOBALS['babUrlScript']."?tg=vacchart&idx=entities");

		$babBody->addItemMenu("entity_planning", bab_translate("Planning accès"), $GLOBALS['babUrlScript']."?tg=vacchart&idx=entity_requests");

		$ide = bab_rp('ide');
		
		if ($entities_access > 0 && !empty($ide))
			entity_planning($ide);
		break;

	case 'comanager':
		$babBody->addItemMenu("entities", bab_translate("Delegate management"), $GLOBALS['babUrlScript']."?tg=vacchart&idx=entities");
		$babBody->addItemMenu("comanager", bab_translate("Co-managers"), $GLOBALS['babUrlScript']."?tg=vacchart&idx=comanager");

		$ide = bab_rp('ide');
		
		if ($entities_access > 0 && !empty($ide))
			entity_comanager($ide);
		break;

	case 'view':
		$babBody->addItemMenu("entities", bab_translate("Delegate management"), $GLOBALS['babUrlScript']."?tg=vacchart&idx=entities");
		$babBody->addItemMenu("entity_members", bab_translate("Entity members"), $GLOBALS['babUrlScript']."?tg=vacchart&idx=entity_members&ide=".$_GET['ide']);
		
		if (bab_IsUserUnderSuperior($_GET['iduser']) && $_GET['iduser'] != $GLOBALS['BAB_SESS_USERID'])
			{
			$babBody->addItemMenu("view", bab_translate("User"), $GLOBALS['babUrlScript']."?tg=vacchart&idx=view&ide=".$_GET['ide']);
			$babBody->title = bab_getUserName($_GET['iduser']);
			viewVacUserDetails($_GET['ide'], $_GET['iduser']);
			}
		else
			{
			$babBody->title = bab_translate("Access denied");
			}
		break;

	case "modp":
		$babBody->addItemMenu("entities", bab_translate("Delegate management"), $GLOBALS['babUrlScript']."?tg=vacchart&idx=entities");

		$babBody->addItemMenu("entity_members", bab_translate("Entity members"), $GLOBALS['babUrlScript']."?tg=vacchart&idx=entity_members&ide=".$_GET['ide']);
		
		if (bab_IsUserUnderSuperior($_REQUEST['iduser']) && $_GET['iduser'] != $GLOBALS['BAB_SESS_USERID'])
			{
			$babBody->addItemMenu("modp", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=vacchart&idx=entity_members&ide=".$_GET['ide']);
			$babBody->title = bab_translate("Modify user");
			addVacationPersonnel($_REQUEST['iduser']);
			}
		else
			{
			$babBody->title = bab_translate("Access denied");
			}
		break;

	case 'changeucol':
		$babBody->addItemMenu("entities", bab_translate("Delegate management"), $GLOBALS['babUrlScript']."?tg=vacchart&idx=entities");

		$babBody->addItemMenu("entity_members", bab_translate("Entity members"), $GLOBALS['babUrlScript']."?tg=vacchart&idx=entity_members&ide=".$_REQUEST['ide']);
		if (bab_IsUserUnderSuperior($_POST['idp']) && $_POST['idp'] != $GLOBALS['BAB_SESS_USERID'])
			{
			$babBody->addItemMenu("changeucol", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=vacchart&idx=changeucol&ide=".$_REQUEST['ide']);
			$babBody->title = bab_translate("Change user collection");
			changeucol( $_POST['idp'], $_POST['idcol'] );
			}
		else
			{
			$babBody->title = bab_translate("Access denied");
			}
		break;

	case 'planning':
		if (bab_isPlanningAccessValid())
			{
			$babBody->addItemMenu("planning", bab_translate("Planning"), $GLOBALS['babUrlScript']."?tg=vacchart&idx=planning");
			$babBody->title = bab_translate("Planning list");
			entities($userentities, 'planning');
			}
		break;

	default:
	case 'entities':
		if ($entities_access > 0)
			{
			$babBody->addItemMenu("entities", bab_translate("Delegate management"), $GLOBALS['babUrlScript']."?tg=vacchart&idx=entities");

			$babBody->title = bab_translate("Entities list");
			entities($userentities, 'entities');
			}
		break;
	}

$babBody->setCurrentItemMenu($idx);

?>