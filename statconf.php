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

function statPages($url, $page)
{
	global $babBody;
	
	class temp
		{
		function temp($url, $page)
			{
			global $babDB;

			$this->pagetxt = bab_translate("Page");
			$this->urltxt = bab_translate("Url");
			$this->updatetxt = bab_translate("Update");
			$this->deletetxt = bab_translate("Delete");
			$this->desctxt = bab_translate("Name");
			$this->addtxt = bab_translate("Add");
			$this->res = $babDB->db_query("select * from ".BAB_STATS_IPAGES_TBL." order by id desc");
			$this->count = $babDB->db_num_rows($this->res);
			$this->urlval = $url;
			$this->descval = $page;
			}

		function getnext()
			{
			global $babDB;
			static $k=0;
			if( $k < $this->count)
				{
				$arr = $babDB->db_fetch_array($this->res);
				$this->page = $arr['page_name'];
				$this->url = $arr['page_url'];
				$this->pageid = $arr['id'];
				$k++;
				return true;
				}
			else
				{
				$k = 0;
				return false;
				}
			}
		}

	$temp = new temp($url, $page);
	$babBody->babecho(	bab_printTemplate($temp,"statconf.html", "pages"));
}


function addPage($url, $page )
{
	global $babDB;

	if( empty($url))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide an url !!");
		return false;
		}

	if( empty($page))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a name !!");
		return false;
		}

	if( !strncasecmp($GLOBALS['babUrl'], $url, strlen($GLOBALS['babUrl'])))
	{
		$url = substr($url, strlen($GLOBALS['babUrl']));
	}
	$babDB->db_query("insert into ".BAB_STATS_IPAGES_TBL." (page_name, page_url) values ('".addslashes($page)."','".addslashes($url)."')");
}

function deletePages($pages )
{
	global $babDB;

	for( $i = 0; $i < count($pages); $i++ )
		{
		$babDB->db_query("delete from ".BAB_STATS_IPAGES_TBL." where id='".$pages[$i]."'");
		$babDB->db_query("delete from ".BAB_STATS_PAGES_TBL." where st_page_id='".$pages[$i]."'");
		}
}

/* main */
if( !bab_isAccessValid(BAB_STATSMAN_GROUPS_TBL, 1))
	{
	$babBody->msgerror = bab_translate("Access denied");
	return;
	}

if( !isset($idx)) { $idx = "conf"; }

if( isset($action))
{
	if( $action == 'dpages' )
	{
		deletePages($pages);
	}
	elseif( $action == 'apage' )
	{
		if(addPage($url, $desc))
		{
			$url = '';
			$desc = '';
		}
	}
}

switch($idx)
	{
	case "pages":
		$babBody->title = bab_translate("Pages");
		$babBody->addItemMenu("stat", bab_translate("Statistics"), $GLOBALS['babUrlScript']."?tg=stat");
		$babBody->addItemMenu("pages", bab_translate("Pages"), $GLOBALS['babUrlScript']."?tg=statconf&idx=pages");
		$babBody->addItemMenu("maj", bab_translate("Update"), $GLOBALS['babUrlScript']."?tg=statconf&idx=maj&statrows=12000");
		if( !isset($url)) { $url = ""; }
		if( !isset($desc)) { $desc = ""; }
		statPages($url, $desc);
		break;

	case "maj":
		$babBody->title = bab_translate("Update");
		$babBody->addItemMenu("stat", bab_translate("Statistics"), $GLOBALS['babUrlScript']."?tg=stat");
		$babBody->addItemMenu("pages", bab_translate("Pages"), $GLOBALS['babUrlScript']."?tg=statconf&idx=pages");
		$babBody->addItemMenu("maj", bab_translate("Update"), $GLOBALS['babUrlScript']."?tg=statconf&idx=maj&statrows=12000");
		include_once $babInstallPath."utilit/statproc.php";
		break;

	default:
	case "conf":
		$babBody->addItemMenu("stat", bab_translate("Statistics"), $GLOBALS['babUrlScript']."?tg=stat");
		$babBody->addItemMenu("pages", bab_translate("Pages"), $GLOBALS['babUrlScript']."?tg=statconf&idx=pages");
		$babBody->addItemMenu("maj", bab_translate("Update"), $GLOBALS['babUrlScript']."?tg=statconf&idx=maj&statrows=12000");
		break;
	}

$babBody->setCurrentItemMenu($idx);
?>