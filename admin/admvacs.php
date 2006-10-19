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

function listVacationManagers()
{
	include_once $GLOBALS['babInstallPath'].'utilit/selectusers.php';
	$db = $GLOBALS['babDB'];
	$obj = new bab_selectusers();
	$res = $db->db_query("select id_user from ".BAB_VAC_MANAGERS_TBL);
	while (list($id) = $db->db_fetch_array($res))
		{
		$obj->addUser($id);
		}
	$obj->setRecordCallback('recordVacationManager');
	$GLOBALS['babBody']->babecho($obj->getHtml());

}



function vacationOptions()
{
	global $babBody;

	class temp
		{
		

		function temp()
			{
			$this->db = &$GLOBALS['babDB'];

			$this->t_yes = bab_translate("Yes");
			$this->t_no = bab_translate("No");
			$this->t_chart_superiors_create_request = bab_translate("Allow managers to create vacation requests for users in chart");

			$req = "SELECT * FROM ".BAB_VAC_OPTIONS_TBL."";
			$this->arr = $this->db->db_fetch_assoc($this->db->db_query($req));

			$this->t_record = bab_translate("Record");
			}
		}

	$temp = new temp();
	$babBody->babecho(	bab_printTemplate($temp, "admvacs.html", "options"));
}





function recordVacationManager($userids, $params)
{
	
	$db = $GLOBALS['babDB'];
	$db->db_query("DELETE FROM ".BAB_VAC_MANAGERS_TBL."");
	foreach($userids as $id) {
		$db->db_query("INSERT into ".BAB_VAC_MANAGERS_TBL." (id_user) values (".$db->quote($id).")");
	}
}



function record_options() {
	$db = &$GLOBALS['babDB'];

	list($n) = $db->db_fetch_array($db->db_query("SELECT COUNT(*) FROM ".BAB_VAC_OPTIONS_TBL.""));
	if ($n > 0) {

		$db->db_query("UPDATE ".BAB_VAC_OPTIONS_TBL." SET chart_superiors_create_request='".$_POST['chart_superiors_create_request']."'");
	} else {
		$db->db_query("INSERT INTO ".BAB_VAC_OPTIONS_TBL." ( chart_superiors_create_request) VALUES ('".$_POST['chart_superiors_create_request']."')");
	}
}


/* main */
if( !$babBody->isSuperAdmin )
	{
	$babBody->title = bab_translate("Access denied");
	exit;
	}

if(!isset($idx))
	{
	$idx = "list";
	}

if( isset($add) && $add == 'addm' )
	{
	addVacationManager($managerid);
	}
else if( isset($del) && $del == 'delm' )
	{
	delVacationManagers($managers);
	}

if (isset($_POST['action'])) {
	switch($_POST['action']) {
		case 'options':
			record_options();
			break;
		}
	}

$babBody->addItemMenu("list", bab_translate("Managers"), $GLOBALS['babUrlScript']."?tg=admvacs&idx=list");
$babBody->addItemMenu('options', bab_translate("Options"), $GLOBALS['babUrlScript']."?tg=admvacs&idx=options");


switch($idx)
	{
	case 'options':
		$babBody->title = bab_translate("List of vacations managers");
		vacationOptions();
		break;
	
	
	default:
	case "list":
		$babBody->title = bab_translate("List of vacations managers");
		listVacationManagers();
		break;
	}
$babBody->setCurrentItemMenu($idx);
?>