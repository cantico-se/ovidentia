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


/**
* @internal SEC1 PR 18/04/2007 FULL
*/


function listVacationManagers()
{
	include_once $GLOBALS['babInstallPath'].'utilit/selectusers.php';
	global $babDB;
	$obj = new bab_selectusers();
	$res = $babDB->db_query("select id_user from ".BAB_VAC_MANAGERS_TBL);
	while (list($id) = $babDB->db_fetch_array($res))
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
			global $babDB;

			$this->t_yes = bab_translate("Yes");
			$this->t_no = bab_translate("No");
			$this->t_chart_superiors_create_request = bab_translate("Allow managers to create vacation requests for users in chart");

			$req = "SELECT * FROM ".BAB_VAC_OPTIONS_TBL."";
			$this->arr = $babDB->db_fetch_assoc($babDB->db_query($req));

			$this->t_record = bab_translate("Record");
			}
		}

	$temp = new temp();
	$babBody->babecho(	bab_printTemplate($temp, "admvacs.html", "options"));
}





function recordVacationManager($userids, $params)
{
	
	global $babDB;
	$babDB->db_query("DELETE FROM ".BAB_VAC_MANAGERS_TBL."");
	foreach($userids as $id) {
		$babDB->db_query("INSERT into ".BAB_VAC_MANAGERS_TBL." (id_user) values (".$babDB->quote($id).")");
	}
	
	bab_siteMap::clearAll();
}



function record_options() {
	global $babDB;

	list($n) = $babDB->db_fetch_array($babDB->db_query("SELECT COUNT(*) FROM ".BAB_VAC_OPTIONS_TBL.""));
	if ($n > 0) {

		$babDB->db_query("UPDATE ".BAB_VAC_OPTIONS_TBL." SET chart_superiors_create_request=".$babDB->quote($_POST['chart_superiors_create_request'])."");
	} else {
		$babDB->db_query("INSERT INTO ".BAB_VAC_OPTIONS_TBL." ( chart_superiors_create_request) VALUES (".$babDB->quote($_POST['chart_superiors_create_request']).")");
	}
}


/* main */
if( !$babBody->isSuperAdmin )
	{
	$babBody->title = bab_translate("Access denied");
	exit;
	}


$idx = bab_rp('idx', 'list');


if( bab_pp('add') == 'addm' )
	{
	addVacationManager(bab_pp('managerid'));
	}
else if( bab_pp('del') == 'delm' )
	{
	delVacationManagers(bab_pp('managers'));
	}

if (bab_pp('action')) {
	switch(bab_pp('action')) {
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