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
* @internal SEC1 NA 08/12/2006 FULL
*/
include_once 'base.php';
include_once $GLOBALS['babInstallPath'].'utilit/artapi.php';

class categoriesHierarchy
{

	var $parentscount;
	var $parentname;
	var $parenturl;
	var $burl;
	var $topics;
	var $topictitle;

	function categoriesHierarchy($topics,$cat,$link)
		{
		global $babBody, $babDB;
		$this->link = $link;

		if( $topics != 0 )
			{
			$res = $babDB->db_query("select id_cat, category from ".BAB_TOPICS_TBL." where id='".$babDB->db_escape_string($topics)."'");
			if( $res && $babDB->db_num_rows($res) > 0 )
				{
				$arr = $babDB->db_fetch_array($res);
				$cat = $arr['id_cat'];
				$this->arrparents[] = array($topics, $arr['category']);
				}
			else
				{
				$cat = 0;
				}		
			}
		else
			{
			$topics = 0;
			}

		if( $cat == -1)
			{
			$cat = 0;
			}

		$this->topics = $topics;
		$this->cat = $cat;
		$topcats = $babBody->get_topcats();
		if( isset($topcats[$cat]) )
			{
			$this->arrparents[] = array($cat, $topcats[$cat]['title']);
			while( $topcats[$cat]['parent'] != 0 )
			{
				$this->arrparents[] = array($topcats[$cat]['parent'],$topcats[$topcats[$cat]['parent']]['title']);
				$cat = $topcats[$cat]['parent'];
			}
			}
		$this->arrparents[] = array(0, bab_translate("Top"));

		$this->parentscount = count($this->arrparents);
		$this->arrparents = array_reverse($this->arrparents);
		}

	function getnextparent()
		{
		global $babBody;

		static $i = 0;
		if( $i < $this->parentscount)
			{
			if( $i == ($this->parentscount - 1))
				{
				$this->parenturl = "";
				$this->burl = false;
				}
			else
				{
				$this->burl = true;
				$this->parenturl = bab_toHtml($this->link."&cat=".$this->arrparents[$i][0]);
				}
			$this->parentname = bab_toHtml($this->arrparents[$i][1]);
			$i++;
			return true;
			}
		else
			{ 
			$i = 0;
			return false;
			}
		}
}



function viewCategoriesHierarchy($topics)
	{
	global $babBody;
	class tempvch extends categoriesHierarchy
		{

		function tempvch($topics)
			{
			$this->categoriesHierarchy($topics, -1, $GLOBALS['babUrlScript']."?tg=topusr");
			}
		}

	$temp = new tempvch($topics);
	$babBody->babecho(	bab_printTemplate($temp,"articles.html", "categorieshierarchy"));
	}

class tempvch_txt extends categoriesHierarchy
	{

	function tempvch_txt($topics)
		{
		$this->categoriesHierarchy($topics, -1, $GLOBALS['babUrlScript']."?tg=topusr");
		}
	}

function viewCategoriesHierarchy_txt($topics)
	{
	global $babBody;

	$temp = new tempvch_txt($topics, -1, $GLOBALS['babUrlScript']."?tg=topusr");
	return bab_printTemplate($temp,"articles.html", "categorieshierarchy_txt");
	}
?>