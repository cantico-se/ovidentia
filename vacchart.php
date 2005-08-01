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
include_once $babInstallPath."utilit/vacincl.php";



function entities($template)
{
global $babBody;

	class temp
		{

		function temp($entities)
			{
			function _array_sort($array, $key)
				{
				   foreach($array as $k => $val) {
					   $sort_values[$k] = $val[$key];
				   }

				   natcasesort($sort_values);
				   reset($sort_values);

				    foreach($sort_values as $k => $val) {
						 $sorted_arr[] = $array[$k];
				   }

				   return $sorted_arr;
				}

			$this->entities = array();
			while (list(,$arr) = each($entities))
				{
				$arr2 = bab_OCGetChildsEntities($arr['id']);
				for ($i = 0 ; $i < count($arr2) ; $i++)
					{
					if (!isset($this->entities[$arr2[$i]['id']]))
					$this->entities[$arr2[$i]['id']] = $arr2[$i];
					}
				if (!isset($this->entities[$arr['id']]))
					$this->entities[$arr['id']] = $arr;
				}
			
			if (count($this->entities) > 0)
				$this->entities = & _array_sort($this->entities, 'name');

			$this->t_name = bab_translate('Name');
			$this->t_description = bab_translate('Description');
			$this->t_members = bab_translate('Members');
			$this->t_calendar = bab_translate('Planning');
			$this->t_requests = bab_translate('Requests');
			$this->t_planning = bab_translate('Planning acces');
			}

		function getnext()
			{
			if (list(,$this->arr) = each($this->entities))
				{
				return true;
				}
			else
				return false;
			}
		}

	
	$u_entities = bab_OCGetUserEntities($GLOBALS['BAB_SESS_USERID']);
	
	switch ($template)
	{
	case 'planning':
		$db = & $GLOBALS['babDB'];
		$res =$db->db_query("SELECT id_entity FROM ".BAB_VAC_PLANNING_TBL." WHERE id_user='".$GLOBALS['BAB_SESS_USERID']."'");
		$entities = array();
		while ($arr = $db->db_fetch_assoc($res))
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
		function temp($ide)
			{
			$this->ide = $ide;
			$users = bab_OCGetCollaborators($ide);
			$superior = bab_OCGetSuperior($ide);
			$this->superior_id = 0;
			if ($superior !== 0 )
				{
				$this->superior_id = $superior['id_user'];
				$this->superior_name = bab_composeUserName($superior['firstname'], $superior['lastname']);
				}
			$this->t_name = bab_translate('Name');
			$this->t_calendar = bab_translate('Planning');
			$this->t_rights = bab_translate('Rights');
			$this->t_asks = bab_translate('Requests');
			$this->t_view_calendar = bab_translate('View calendars');
			$this->t_collection = bab_translate('Collection');
			$this->t_schema = bab_translate('Approbation schema');
			
			$this->users = array();
			$this->b_rights = $this->superior_id != $GLOBALS['BAB_SESS_USERID'];

			while (list(,$arr) = each($users))
				{
				if ($arr['id_user'] != $this->superior_id)
					{
					$this->users[$arr['id_user']] = bab_composeUserName($arr['firstname'], $arr['lastname']);
					}
				}
			natcasesort($this->users);

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

				$db = & $GLOBALS['babDB'];
				$req = "SELECT p.id_user,c.name coll,f.name sa FROM ".BAB_VAC_PERSONNEL_TBL." p LEFT JOIN ".BAB_VAC_COLLECTIONS_TBL." c ON c.id=p.id_coll LEFT JOIN ".BAB_FLOW_APPROVERS_TBL." f ON f.id=p.id_sa WHERE p.id_user IN(".implode(',',$tmp).")";
				$res = $db->db_query($req);
				while ($arr = $db->db_fetch_array($res))
					{
					$this->more[$arr['id_user']] = array( $arr['coll'], $arr['sa'] );
					}
				}

			$this->s_collection = '';
			$this->s_schema = '';
			if ($superior !== 0 && isset($this->more[$this->superior_id]))
				{
				list($this->s_collection, $this->s_schema ) = $this->more[$this->superior_id] ;
				}
			}

		function getnext()
			{
			if (list($this->id_user,$this->name) = each($this->users))
				{
				$this->b_rights = $this->id_user != $GLOBALS['BAB_SESS_USERID'];
				$this->collection = isset($this->more[$this->id_user][0]) ? $this->more[$this->id_user][0] : '';
				$this->schema = isset($this->more[$this->id_user][1]) ? $this->more[$this->id_user][1] : '';
				
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
	global $babBody;
	class temp
		{
		function temp($ide)
			{
			$this->ide = $ide;
			$this->users = entity_users($ide);
			$this->t_members = bab_translate("Entity members");
			$this->t_record = bab_translate("Record");

			$e =  bab_OCGetEntity($this->ide);

			$GLOBALS['babBody']->title = bab_translate("Planning acces").' : '.$e['name'];

			$db = &$GLOBALS['babDB'];
			$res = $db->db_query("SELECT id_user FROM ".BAB_VAC_PLANNING_TBL." WHERE id_entity='".$this->ide."'");
			$this->selected_users = array();
			while (list($id) = $db->db_fetch_array($res))
				{
				$this->selected_users[$id] = 1;
				}
			}

		function getnext()
			{
			if (list(,$this->id_user) = each($this->users))
				{
				$this->name = bab_getUserName($this->id_user);
				$this->checked = isset($this->selected_users[$this->id_user]);
				return true;
				}
			else
				return false;
			}
		}

	$temp = new temp($ide);
	$babBody->babecho(bab_printTemplate($temp, "vacchart.html", "entity_planning"));
}


function savePlanning($ide, $userids)
{
	$db = &$GLOBALS['babDB'];
	$db->db_query("DELETE FROM ".BAB_VAC_PLANNING_TBL." WHERE id_entity = '".$ide."'");

	foreach ($userids as $uid)
	{
		$db->db_query("INSERT INTO ".BAB_VAC_PLANNING_TBL." (id_user, id_entity) VALUES ('".$uid."','".$ide."')");
	}
	
	return true;
}


// main
$userentities = & bab_OCGetUserEntities($GLOBALS['BAB_SESS_USERID']);
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

		case 'planning':
			$userids = isset($_POST['userids']) ? $_POST['userids'] : array();
			if(!savePlanning($_POST['ide'], $userids))
				{
				$idx ='entity_planning';
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
		if ($entities_access > 0 || bab_isPlanningAccessValid())
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
		
		if ($entities_access > 0 && !empty($_GET['ide']))
			entity_planning($_GET['ide']);
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
			entities('planning');
			}
		break;

	default:
	case 'entities':
		if ($entities_access > 0)
			{
			$babBody->addItemMenu("entities", bab_translate("Delegate management"), $GLOBALS['babUrlScript']."?tg=vacchart&idx=entities");

			$babBody->title = bab_translate("Entities list");
			entities('entities');
			}
		break;
	}

$babBody->setCurrentItemMenu($idx);

?>