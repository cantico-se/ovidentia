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
* @internal SEC1 NA 18/12/2006 FULL
*/
include_once 'base.php';
require_once dirname(__FILE__).'/utilit/registerglobals.php';

function browseSa($cb)
	{
	global $babBody;
	class temp
		{
		var $sContent;
		
		function temp($cb)
			{
			global $babDB;
			
			$this->sContent		= 'text/html; charset=' . bab_charset::getIso();
			$this->cb			= $cb;
			$this->name			= bab_translate("Name");
			$this->description	= bab_translate("Description");
			$this->sares		= $babDB->db_query("select id, name, description from ".BAB_FLOW_APPROVERS_TBL." order by name asc");
			
			if( !$this->sares )
				{
				$this->sacount = 0;
				}
			else
				{
				$this->sacount = $babDB->db_num_rows($this->sares);
				}
			}

		function getnext()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->sacount)
				{
				$arr = $babDB->db_fetch_array($this->sares);
				$this->sanameval = bab_toHtml($arr['name']);
				$this->descval = bab_toHtml($arr['description']);
				$this->saname = bab_toHtml($arr['name'], BAB_HTML_JS);
				$this->said = bab_toHtml($arr['id']);
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
$idx = bab_rp('idx');
$cb = bab_rp('cb');
switch($idx)
	{
	default:
	case "brow":
		browseSa($cb);
		exit;
		break;
	}
?>