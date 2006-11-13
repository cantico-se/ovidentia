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
include_once 'base.php';
function browseSa($cb)
	{
	global $babBody;
	class temp
		{
		function temp($cb)
			{
			global $babDB;
			$this->cb = $cb;
			$this->name = bab_translate("Name");
			$this->description = bab_translate("Description");

			$this->sares = $babDB->db_query("select id, name, description from ".BAB_FLOW_APPROVERS_TBL." order by name asc");
			if( !$this->sares )
				$this->sacount = 0;
			else
				$this->sacount = $babDB->db_num_rows($this->sares);
			}

		function getnext()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->sacount)
				{
				$arr = $babDB->db_fetch_array($this->sares);
				$this->sanameval = $arr['name'];
				$this->descval = $arr['description'];
				$this->saname = str_replace("'", "\'", $arr['name']);
				$this->saname = str_replace('"', "'+String.fromCharCode(34)+'",$this->saname);
				$this->said = $arr['id'];
				$i++;
				return true;
				}
			else
				{
				return false;
				}
			}

		}

	$temp = new temp($cb);
	echo bab_printTemplate($temp, "lsa.html", "browsesa");
	}

/* main */
if( !isset($idx)) { $idx = 0; }
switch($idx)
	{
	default:
	case "brow":
		browseSa($cb);
		exit;
		break;
	}
?>