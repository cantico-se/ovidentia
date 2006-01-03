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
	global $babBody;

	class temp
		{
		var $fullnametxt;
		var $fullname;
		var $checkall;
		var $uncheckall;
		var $delete;
		var $usersbrowurl;
		var $adduser;
		var $userid;

		var $arr = array();
		var $count;
		var $res;

		function temp()
			{
			global $babDB;
			$this->fullnametxt = bab_translate("Vacation managers");
			$this->delete = bab_translate("Delete");
			$this->uncheckall = bab_translate("Uncheck all");
			$this->checkall = bab_translate("Check all");
			$this->adduser = bab_translate("Add");
			$this->managertext = bab_translate("New manager");
			$this->managerval = "";
			$this->managerid = "";
			$this->usersbrowurl = $GLOBALS['babUrlScript']."?tg=users&idx=brow&cb=";
			$this->res = $babDB->db_query("select * from ".BAB_VAC_MANAGERS_TBL."");
			$this->count = $babDB->db_num_rows($this->res);
			}

		function getnext()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $babDB->db_fetch_array($this->res);
				$this->fullname = bab_getUserName($arr['id_user']);
				$this->userid = $arr['id_user'];
				$i++;
				return true;
				}
			else
				return false;

			}
		}

	$temp = new temp();
	$babBody->babecho(	bab_printTemplate($temp, "admvacs.html", "managerslist"));
	return $temp->count;
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





function addVacationManager($managerid)
{
	global $babBody, $babDB;
	$res = $babDB->db_query("select id from ".BAB_VAC_MANAGERS_TBL." where id_user='".$managerid."'");
	if( $res && $babDB->db_num_rows($res) > 0 )
	{
		$babBody->msgerror = bab_translate("User is already in the list!");
		return;
	}

	$babDB->db_query("insert into ".BAB_VAC_MANAGERS_TBL." (id_user) values ('".$managerid."')");
}

function delVacationManagers($managers)
{
	global $babBody, $babDB;

	for( $i=0; $i < count($managers); $i++)
	{
		$babDB->db_query("delete from  ".BAB_VAC_MANAGERS_TBL." where id_user='".$managers[$i]."'"); 
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