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


function statPreferences()
{
	global $babBody;
	
	class statPreferencesCls
		{
		var $updatetxt;
		var $separator;
		var $other;
		var $comma;
		var $tab;

		function statPreferencesCls()
			{
			global $babDB;
			$res = $babDB->db_query("select separatorchar from ".BAB_STATS_PREFERENCES_TBL." where id_user='".$GLOBALS['BAB_SESS_USERID']."'");
			if( $res && $babDB->db_num_rows($res) > 0 )
				{
				$arr = $babDB->db_fetch_array($res);
				$arr['separator'] = $arr['separatorchar'];
				}
			else
				{
				$babDB->db_query("insert into ".BAB_STATS_PREFERENCES_TBL." (id_user, time_interval, begin_date, end_date, separatorchar) values ('".$GLOBALS['BAB_SESS_USERID']."', '0', '', '', '".ord(",")."')");
				$arr['separator'] = ",";
				}

			$this->selected_1 = '';
			$this->selected_2 = '';
			$this->selected_3 = '';
			$this->separvalue = '';

			switch($arr['separator'] )
				{
				case 44:
					$this->selected_1 = 'selected';
					break;
				case 9:
					$this->selected_2 = 'selected';
					break;
				default:
					$this->selected_0 = 'selected';
					$this->separvalue = chr($arr['separator']);
					break;
				}

			$this->updatetxt = bab_translate("Update");
			$this->separator = bab_translate("Field separator");
			$this->other = bab_translate("Other");
			$this->comma = bab_translate("Comma");
			$this->tab = bab_translate("Tab");
			}
		}

	$temp = new statPreferencesCls();
	$babBody->babecho(	bab_printTemplate($temp,"statconf.html", "preferences"));
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

function updateStatPreferences($wsepar, $separ)
{
	global $babDB;

	switch($wsepar)
		{
		case "1":
			$separ = ord(",");
			break;
		case "2":
			$separ = 9;
			break;
		default:
			if( empty($separ))
				$separ = ord(",");
			else
				$separ = ord($separ);
			break;
		}

	$babDB->db_query("update ".BAB_STATS_PREFERENCES_TBL." set separatorchar='".$separ."' where id_user='".$GLOBALS['BAB_SESS_USERID']."'");

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
	elseif( $action == 'apref' )
	{
		updateStatPreferences($wsepar, $separ);
		Header("Location: ". $GLOBALS['babUrlScript']."?tg=stat");
		exit;
	}
}

switch($idx)
	{
	case "pref":
		$babBody->title = bab_translate("Preferences");
		$babBody->addItemMenu("stat", bab_translate("Statistics"), $GLOBALS['babUrlScript']."?tg=stat");
		$babBody->addItemMenu("pages", bab_translate("Pages"), $GLOBALS['babUrlScript']."?tg=statconf&idx=pages");
		$babBody->addItemMenu("pref", bab_translate("Preferences"), $GLOBALS['babUrlScript']."?tg=statconf&idx=pref");
		$babBody->addItemMenu("maj", bab_translate("Update"), $GLOBALS['babUrlScript']."?tg=statconf&idx=maj&statrows=12000");
		statPreferences();
		break;

	case "pages":
		$babBody->title = bab_translate("Pages");
		$babBody->addItemMenu("stat", bab_translate("Statistics"), $GLOBALS['babUrlScript']."?tg=stat");
		$babBody->addItemMenu("pages", bab_translate("Pages"), $GLOBALS['babUrlScript']."?tg=statconf&idx=pages");
		$babBody->addItemMenu("pref", bab_translate("Preferences"), $GLOBALS['babUrlScript']."?tg=statconf&idx=pref");
		$babBody->addItemMenu("maj", bab_translate("Update"), $GLOBALS['babUrlScript']."?tg=statconf&idx=maj&statrows=12000");
		if( !isset($url)) { $url = ""; }
		if( !isset($desc)) { $desc = ""; }
		statPages($url, $desc);
		break;

	case "maj":
		$babBody->title = bab_translate("Update");
		$babBody->addItemMenu("stat", bab_translate("Statistics"), $GLOBALS['babUrlScript']."?tg=stat");
		$babBody->addItemMenu("pages", bab_translate("Pages"), $GLOBALS['babUrlScript']."?tg=statconf&idx=pages");
		$babBody->addItemMenu("pref", bab_translate("Preferences"), $GLOBALS['babUrlScript']."?tg=statconf&idx=pref");
		$babBody->addItemMenu("maj", bab_translate("Update"), $GLOBALS['babUrlScript']."?tg=statconf&idx=maj&statrows=12000");
		include_once $babInstallPath."utilit/statproc.php";
		break;

	default:
	case "conf":
		$babBody->addItemMenu("stat", bab_translate("Statistics"), $GLOBALS['babUrlScript']."?tg=stat");
		$babBody->addItemMenu("pages", bab_translate("Pages"), $GLOBALS['babUrlScript']."?tg=statconf&idx=pages");
		$babBody->addItemMenu("pref", bab_translate("Preferences"), $GLOBALS['babUrlScript']."?tg=statconf&idx=pref");
		$babBody->addItemMenu("maj", bab_translate("Update"), $GLOBALS['babUrlScript']."?tg=statconf&idx=maj&statrows=12000");
		break;
	}

$babBody->setCurrentItemMenu($idx);
?>