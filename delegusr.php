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
/**
* @internal SEC1 NA 05/12/2006 FULL
*/

include_once 'base.php';

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
			$this->delegat = array();

			if( $babBody->isSuperAdmin )
				{
				$this->delegat[0] = bab_translate("All site");
				}

			$res = $babDB->db_query("SELECT id,name FROM ".BAB_DG_GROUPS_TBL." WHERE id IN(".$babDB->quote($babBody->dgAdmGroups).")");
			while ($arr = $babDB->db_fetch_assoc($res))
				{
				$this->delegat[$arr['id']] = $arr['name'];
				}

			
			}

		function getnext()
			{
			global $babBody;	
			if( list($this->grpdgid,$this->grpdgname ) = each($this->delegat))
				{
				if( $this->grpdgid == $babBody->currentDGGroup['id'] )
					$this->selected = "selected";
				else
					$this->selected = "";
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

	$babBody->currentDGGroup = $babDB->db_fetch_array($babDB->db_query("select dg.*, g.lf, g.lr from ".BAB_DG_GROUPS_TBL." dg, ".BAB_GROUPS_TBL." g where g.id=dg.id_group and dg.id='".$babDB->db_escape_string($grpdg)."'"));
	
	if ($grpdg > 0 && isset($babBody->currentDGGroup['id_group']))
		{
		$babBody->currentAdmGroup = &$babBody->currentDGGroup['id'];
		}
	elseif ($grpdg == 0)
		{
		$babBody->currentAdmGroup = 0;
		}
	else
		{
		trigger_error('no group in delegation');
		}

	$babDB->db_query("update ".BAB_USERS_LOG_TBL." set id_dg='".$babDB->db_escape_string($grpdg)."' where sessid='".session_id()."'");
	
	bab_siteMap::clear();
	
	header('Location: '. $GLOBALS['babUrlScript'].'?tg=delegusr');
		
}

/* main */
if( count($babBody->dgAdmGroups) < 1)
	{
	$babBody->title = bab_translate("Access denied");
	return;
	}
	
$idx = bab_rp('idx', 'chgdg');

if('moddg' == bab_rp('mod'))
{
	updateAdmGroup(bab_rp('grpdg'));
}

switch($idx)
	{
	case "chgdg":
	default:
		$babBody->title = bab_translate("Change administration");
		changeAdmGroup();
		break;
	}

$babBody->setCurrentItemMenu($idx);
?>