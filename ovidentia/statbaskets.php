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
include_once $babInstallPath.'utilit/statutil.php';
include_once $babInstallPath.'utilit/uiutil.php';


function listUserBaskets()
{
	global $babBody;
	class listUserBasketsCls
		{
		var $altbg = true;

		function listUserBasketsCls()
			{
			global $babDB, $babBody;

			$this->t_baskets_txt = bab_translate("Baskets");
			$this->arrbaskets = array();
			$res = $babDB->db_query("select * from ".BAB_STATS_BASKETS_TBL."");
			while( $arr = $babDB->db_fetch_array($res))
				{
				if( bab_isAccessValid(BAB_STATSBASKETS_GROUPS_TBL,$arr['id']))
					{
					$this->arrbaskets[] = array('name'=>$arr['basket_name'], 'id'=>$arr['id']);
					}
				}
			$this->count = count($this->arrbaskets);
			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$this->altbg = !$this->altbg;
				$this->itemname = bab_toHtml($this->arrbaskets[$i]['name']);
				$this->itemurl = '?tg=stat&idx=basket&idbasket=' . $this->arrbaskets[$i]['id'];
				$i++;
				return true;
				}
			else
				return false;

			}
		}
	
	$temp = new listUserBasketsCls();
	$babBody->babecho(	bab_printTemplate($temp, "statbaskets.html", "list_baskets"));
}
?>