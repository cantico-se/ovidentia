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


switch($idx)
	{
	default:
	case "list":
		$babBody->title = bab_translate("List of vacations managers");
		listVacationManagers();
		$babBody->addItemMenu("list", bab_translate("Managers"), $GLOBALS['babUrlScript']."?tg=admvacs&idx=list");
		break;
	}
$babBody->setCurrentItemMenu($idx);
?>