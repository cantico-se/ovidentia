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

function calendarGroups()
	{
	global $babBody;

	class temp
		{
		var $name;
		var $updategroups;
		var $all;
		var $none;
		var $listgroups;

		var $arr = array();
		var $what = array();
		var $db;
		var $id;
		var $count;
		var $res;
		var $groups;
		var $arrgroups = array();
		var $select;
		var $users;

		function temp()
			{
			global $babBody;
			$this->name = bab_translate("Groups Names");
			$this->updategroups = bab_translate("Update Groups");
			$this->none = bab_translate("None");
			$this->all = bab_translate("All");
			$this->users = bab_translate("Registered Users");
			$this->listgroups = bab_translate("Groups List");
			$this->db = $GLOBALS['babDB'];
			
			$req = "select distinct c.* from ".BAB_CALENDAR_TBL." c, ".BAB_GROUPS_TBL." g where c.owner!='2' and c.type='2' and ( c.owner='".$babBody->currentAdmGroup."' or (g.id_dgowner='".$babBody->currentAdmGroup."' and c.owner=g.id)) order by c.owner asc";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			}

		function getnextgroup()
			{
			static $i = 0;
			
			if( $i < $this->count)
				{
				$this->arrgroups = $this->db->db_fetch_array($this->res);
				if($this->arrgroups['owner'] == 1)
					{
					$this->arrgroups['name'] = /*$this->users*/bab_translate("Registered users");
					}
				else
					$this->arrgroups['name'] = bab_getGroupName($this->arrgroups['owner']);

				if($this->arrgroups['actif'] == "Y")
					{
					$this->arrgroups['select'] = "selected";
					}
				else
					{
					$this->arrgroups['select'] = "";
					}
				$i++;
				return true;
				}
			else
				{
				return false;
				}

			}
		}
	$temp = new temp();
	$babBody->babecho(	bab_printTemplate($temp, "admcals.html", "calendargroups"));
	}

function groupsUpdate($groups, $what)
	{
	global $babBody;

	$db = $GLOBALS['babDB'];

	$res = $db->db_query("select distinct c.id from ".BAB_CALENDAR_TBL." c, ".BAB_GROUPS_TBL." g where c.owner!='2' and c.type='2' and ( c.owner='".$babBody->currentAdmGroup."' or (g.id_dgowner='".$babBody->currentAdmGroup."' and c.owner=g.id))");
	while( $arr = $db->db_fetch_array($res))
		{
		$db->db_query("update ".BAB_CALENDAR_TBL." set actif='N' where id='".$arr['id']."'");
		}

	if( $what == "") // listgroups
		{
		$cnt = count($groups);
		if( $cnt > 0)
			{
			for( $i = 0; $i < $cnt; $i++)
				{
				$req = "update ".BAB_CALENDAR_TBL." set actif='Y' where id='".$groups[$i]."' and type='2'";
				$res = $db->db_query($req);
				}
			}
		}
	}

/* main */
if( !$babBody->isSuperAdmin && $babBody->currentDGGroup['calendars'] != 'Y')
{
	$babBody->msgerror = bab_translate("Access denied");
	return;
}

if( !isset($idx))
	$idx = "groups";

if( isset($calgroups) && $calgroups == "update")
	groupsUpdate($groups, $what);

switch($idx)
	{
	default:
	case "groups":
		calendarGroups();
		$babBody->title = bab_translate("Groups List");
		$babBody->addItemMenu("groups", bab_translate("Groups"), $GLOBALS['babUrlScript']."?tg=admcals&idx=groups");
		$babBody->addItemMenu("listcat", bab_translate("Categories"), $GLOBALS['babUrlScript']."?tg=confcals&idx=listcat&userid=0");
		$babBody->addItemMenu("resources", bab_translate("Resources"), $GLOBALS['babUrlScript']."?tg=confcals&idx=listres&userid=0");
		break;
	}

$babBody->setCurrentItemMenu($idx);

?>