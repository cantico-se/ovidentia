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


function entities()
{
global $babBody;

	class temp
		{

		function temp()
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

			$entities = bab_OCGetUserEntities($GLOBALS['BAB_SESS_USERID']);

			$this->entities = array();
			while (list(,$arr) = each($entities['superior']))
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

			$this->entities = & _array_sort($this->entities, 'name');

			$this->t_name = bab_translate('Name');
			$this->t_description = bab_translate('Description');
			$this->t_members = bab_translate('Members');
			$this->t_calendar = bab_translate('Calendar');
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

	$temp = new temp();
	$babBody->babecho(bab_printTemplate($temp, "vacchart.html", "entities"));
	
}


function entity_members($ide)
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
			$this->t_calendar = bab_translate('Calendar');
			$this->t_rights = bab_translate('Rights');
			$this->t_asks = bab_translate('Requests');
			$this->t_view_calendar = bab_translate('View calendars');
			
			$this->users = array();

			while (list(,$arr) = each($users))
				{
				if ($arr['id_user'] != $this->superior_id)
					{
					$this->users[$arr['id_user']] = bab_composeUserName($arr['firstname'], $arr['lastname']);
					}
				}
			natcasesort($this->users);
			}

		function getnext()
			{
			if (list($this->id_user,$this->name) = each($this->users))
				{
				return true;
				}
			else
				return false;
			}
		}

	$temp = new temp($ide);
	$babBody->babecho(bab_printTemplate($temp, "vacchart.html", "entity_members"));
	
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

function user_rights($id_user)
{	
	class temp
		{
		function temp($id_user)
			{
			$db = & $GLOBALS['babDB'];
			
			$this->t_description = bab_translate('Name');
			$this->t_date_begin = bab_translate('Begin date');
			$this->t_date_end = bab_translate('End date');
			$this->t_quantity = bab_translate('Quantity');
			$this->t_used = bab_translate('Used');
			$this->t_rule = bab_translate('Rule');
			$this->t_remain = bab_translate('Remain');
			$this->t_yes = bab_translate('Yes');
			$this->t_no = bab_translate('No');

			$this->rights = array();
			

			$res = $db->db_query("select r.*, rules.id id_rule, ur.quantity ur_quantity from ".BAB_VAC_TYPES_TBL." t, ".BAB_VAC_COLL_TYPES_TBL." c, ".BAB_VAC_RIGHTS_TBL." r, ".BAB_VAC_USERS_RIGHTS_TBL." ur, ".BAB_VAC_PERSONNEL_TBL." p LEFT JOIN ".BAB_VAC_RIGHTS_RULES_TBL." rules ON rules.id_right = r.id where t.id = c.id_type and c.id_coll=p.id_coll AND p.id_user='".$id_user."' AND r.active='Y' and ur.id_user='".$id_user."' and ur.id_right=r.id and r.id_type=t.id GROUP BY r.id  ORDER BY r.description");
			
			while ( $arr = $db->db_fetch_array($res) )
				{
				$row = $db->db_fetch_array($db->db_query("select sum(quantity) as total from ".BAB_VAC_ENTRIES_ELEM_TBL." el, ".BAB_VAC_ENTRIES_TBL." e where e.id_user='".$id_user."' and e.status='Y' and el.id_type='".$arr['id']."' and el.id_entry=e.id"));

				$quantity = $arr['ur_quantity'] != '' ? $arr['ur_quantity'] : $arr['quantity'];
				$total = isset($row['total'])? $row['total'] : 0;

				$this->rights[] = array(
										'id' => $arr['id'],
										'description' => $arr['description'],
										'date_begin' => bab_shortdate(bab_mktime($arr['date_begin']),false),
										'date_end' => bab_shortdate(bab_mktime($arr['date_end']),false),
										'quantity' => $quantity,
										'used' => $total,
										'remain' => $quantity - $total,
										'rule' => !empty($arr['id_rule'])
										);
				}

			}

		function getnext()
			{
			return list(,$this->arr) = each($this->rights);
			}

		function printhtml()
			{
			$html = & bab_printTemplate($this,"vacchart.html", "user_rights");

			if (isset($_GET['popup']) && $_GET['popup'] == 1)
				{
				include_once $GLOBALS['babInstallPath']."utilit/uiutil.php";
				$GLOBALS['babBodyPopup'] = new babBodyPopup();
				$GLOBALS['babBodyPopup']->title = $GLOBALS['babBody']->title;
				$GLOBALS['babBodyPopup']->msgerror = $GLOBALS['babBody']->msgerror;
				$GLOBALS['babBodyPopup']->babecho($html);
				printBabBodyPopup();
				die();
				}
			else
				{
				$GLOBALS['babBody']->babecho($html);
				}
			}
		}

	$temp = new temp($id_user);
	$temp->printhtml();
}





// main

$idx = isset($_REQUEST['idx']) ? $_REQUEST['idx'] : '';

$babBody->addItemMenu("entities", bab_translate("Entities"), $GLOBALS['babUrlScript']."?tg=vacchart&idx=entities");

switch($idx)
	{
	case 'entity_members':
		$babBody->title = bab_translate("Entity members");
		$babBody->addItemMenu("entity_members", bab_translate("Entity members"), $GLOBALS['babUrlScript']."?tg=vacchart&idx=entity_members");
		entity_members($_REQUEST['ide']);
		break;

	case 'entity_cal':
		entity_cal($_REQUEST['ide']);
		break;

	case 'rights':
		$babBody->title = bab_getUserName($_GET['id_user']);
		if (bab_IsUserUnderSuperior($_GET['id_user']))
			{
			user_rights($_GET['id_user']);
			}
		else
			{
			$babBody->title = bab_translate("Access denied");
			}
		break;

	case 'asks':
		$babBody->addItemMenu("entity_members", bab_translate("Entity members"), $GLOBALS['babUrlScript']."?tg=vacchart&idx=entity_members&ide=".$_GET['ide']);
		if (bab_IsUserUnderSuperior($_GET['id_user']))
			{
			$babBody->title = bab_translate("Vacation requests list");
			$babBody->addItemMenu("asks", bab_translate("Requests"), $GLOBALS['babUrlScript']."?tg=vacchart&idx=asks");
			listVacationRequests($_GET['id_user']);
			}
		else
			{
			$babBody->title = bab_translate("Access denied");
			}
		break;

	default:
	case 'entities':
		$babBody->title = bab_translate("Entities list");
		entities();
		break;
	}

$babBody->setCurrentItemMenu($idx);

?>