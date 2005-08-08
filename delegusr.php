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

function changeAdmGroup()
	{
	global $babBody;
	class temp
		{
		var $groupname;
		var $selected;
		var $modify;
		var $grpdgname;
		var $grpdgid;
		var $count;
		var $groups = array();

		function temp()
			{
			global $babBody, $babDB;
			$this->groupname = bab_translate("Administration");
			$this->modify = bab_translate("Modify");
			$this->selected = "";
			$this->groups = $babBody->dgAdmGroups;
			$this->count = count($this->groups);

			
			if( $babBody->isSuperAdmin )
				{
				$this->count += 1;
				$this->groups[] = NULL;
				}
			}

		function getnext()
			{
			global $babBody, $babDB;
			static $i = 0;	
			if( $i < $this->count)
				{
				if( $this->groups[$i] == NULL )
					{
					$this->grpdgname = bab_translate("All site");
					$this->grpdgid = 'NULL';
					}
				else
					{
					$this->grpdgname = $babBody->ovgroups[$this->groups[$i]]['dg_group_name'];
					$this->grpdgid = $this->groups[$i];
					}

				if( $this->groups[$i] === $babBody->currentAdmGroup )
					$this->selected = "selected";
				else
					$this->selected = "";
				$i++;
				return true;
				}
			return false;
			}


		}

	$temp = new temp();
	$babBody->babecho(	bab_printTemplate($temp,"delegusr.html", "delegatchange"));
	}

function updateAdmGroup($grpdg)
{
	global $babBody, $babDB;



	
	if ($_POST['grpdg'] === 'NULL')
		{
		$babBody->currentAdmGroup = NULL;
		$babBody->currentDGGroup = array();
		$dbAdmGroup = 'NULL';
		}
	else
		{
		$babBody->currentAdmGroup = $grpdg;
		$babBody->currentDGGroup = $babDB->db_fetch_array($babDB->db_query("select dg.*, g.lf, g.lr from ".BAB_DG_GROUPS_TBL." dg, ".BAB_GROUPS_TBL." g where g.id='".$grpdg."' and dg.id=g.id_dggroup"));
		$dbAdmGroup = "'".$grpdg."'";
		}

	
	$babDB->db_query("update ".BAB_USERS_LOG_TBL." set id_dggroup=".$dbAdmGroup." where sessid='".session_id()."'");
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=delegusr");
}

/* main */
if( count($babBody->dgAdmGroups) < 1)
	{
	$babBody->title = bab_translate("Access denied");
	exit;
	}
	
if( !isset($idx))
	$idx = "chgdg";

if( isset($mod) && $mod == 'moddg')
{
	updateAdmGroup($grpdg);
}

switch($idx)
	{
	case "chgdg":
	default:
		$babBody->title = bab_translate("Change administration");
		changeAdmGroup();
		break;
		break;
	}

$babBody->setCurrentItemMenu($idx);
?>