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

function browseGroups($cb)
	{
	global $babBody;
	class temp
		{
		var $fullname;
		var $urlname;
		var $url;
				
		var $fullnameval;

		var $arr = array();
		var $db;
		var $count;
		var $res;

		var $groupid;
		var $groupname;
		var $jgroupname;

		function temp($cb)
			{
			global $babBody;
			$this->db = $GLOBALS['babDB'];
			$this->cb = $cb;

			$this->fullname = bab_translate("Group");
			$this->res = $this->db->db_query("select * from ".BAB_GROUPS_TBL." where id!='2' and id_dgowner='".$babBody->currentAdmGroup."' order by name asc");
			$this->count = $this->db->db_num_rows($this->res);
			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $this->db->db_fetch_array($this->res);
				$this->groupid = $arr['id'];
				if( $this->arr['id'] < 3 )
					{
					$this->groupname = bab_getGroupName($arr['id']);
					}
				else
					{
					$this->groupname = $arr['name'];
					}
				$this->jgroupname = str_replace("'", "\'", $this->groupname);
				$this->jgroupname = str_replace('"', "'+String.fromCharCode(34)+'",$this->jgroupname);				
				$i++;
				return true;
				}
			else
				return false;

			}

		}

	$temp = new temp($cb);
	echo bab_printTemplate($temp, "groups.html", "browsegroups");
	}
?>