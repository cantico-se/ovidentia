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
include_once $babInstallPath."utilit/ocapi.php";
include_once $babInstallPath."utilit/vacincl.php";



function entities()
{
global $babBody;

	class temp
		{

		function temp()
			{
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
			$users = bab_OCGetCollaborators($ide);
			$superior = bab_OCGetSuperior($ide);
			$this->superior_id = 0;
			if ($superior !== 0 )
				{
				$this->superior_id = $superior['id_user'];
				$this->superior_name = bab_composeUserName($superior['firstname'], $superior['lastname']);
				}
			$this->t_name = bab_translate('Name');

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

	if (!isset($tmp[$superior['id_user']]))
		$tmp[$superior['id_user']] = $superior['id_user'];
	
	if (count($tmp) > 0)
		viewVacationCalendar(array_keys($tmp));
	else
		die('error, no collaborators');
}


// main

$idx = isset($_REQUEST['idx']) ? $_REQUEST['idx'] : '';

$babBody->addItemMenu("lper", bab_translate("Personnel"), $GLOBALS['babUrlScript']."?tg=vacadm&idx=lper");
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

	default:
	case 'entities':
		$babBody->title = bab_translate("Entities list");
		entities();
		break;
	}

$babBody->setCurrentItemMenu($idx);

?>