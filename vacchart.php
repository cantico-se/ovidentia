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
			$this->entities = bab_OCGetUserEntities($GLOBALS['BAB_SESS_USERID']);

			$this->t_name = bab_translate('Name');
			$this->t_description = bab_translate('Description');
			$this->t_members = bab_translate('Members');
			$this->t_calendar = bab_translate('Calendar');
			}

		function getnext()
			{
			if (list(,$this->arr) = each($this->entities['superior']))
				{
				$collab = & bab_OCGetCollaborators($this->arr['id']);
				$this->count = count($collab);
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
			$this->users = bab_OCGetCollaborators($ide);
			$this->t_name = bab_translate('Name');
			}

		function getnext()
			{
			if (list(,$this->id_user) = each($this->users))
				{
				$this->name = bab_getUserName($this->id_user);
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
	if (count($users) > 0)
		viewVacationCalendar($users);
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