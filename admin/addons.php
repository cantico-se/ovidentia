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
include $babInstallPath."admin/acl.php";

function compare_versions($ver1,$ver2) // return true if ver2 >ver1
{
$tmp1 = explode(" ",$ver1);
$tab1 = explode(".",$tmp1[0]);
$tmp2 = explode(" ",$ver2);
$tab2 = explode(".",$tmp2[0]);
if ( count($tab1) >= count($tab2) )
	{
	foreach( $tab1 as $key => $value )
		if ( is_numeric($tab2[$key]) && is_numeric($value) && $tab2[$key] > $value ) return true;
	}
else
	{
	foreach( $tab2 as $key => $value )
		if ( is_numeric($tab1[$key]) && is_numeric($value) && $tab1[$key] > $value ) return true;
	}
return false;
}

function getAddonName($id)
	{
	$db = $GLOBALS['babDB'];
	$query = "select title from ".BAB_ADDONS_TBL." where id='$id'";
	$res = $db->db_query($query);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		return $arr['title'];
		}
	else
		{
		return "";
		}
	}

function addonsList($upgradeall)
	{
	global $babBody;
	class temp
		{
		var $name;
		var $url;
		var $desctxt;
				
		var $arr = array();
		var $db;
		var $count;
		var $res;
		var $catchecked;
		var $disabled;
		var $checkall;
		var $uncheckall;
		var $update;
		var $view;
		var $viewurl;

		function temp($upgradeall)
			{
			$this->name = bab_translate("Name");
			$this->desctxt = bab_translate("Description");
			$this->upgradetxt = bab_translate("Upgrade");
			$this->disabled = bab_translate("Disabled");
			$this->uncheckall = bab_translate("Uncheck all");
			$this->checkall = bab_translate("Check all");
			$this->update = bab_translate("Update");
			$this->view = bab_translate("View");
			$this->versiontxt = bab_translate("Version");
			$this->db = $GLOBALS['babDB'];
			$this->upgradeall = $upgradeall;
			$this->upgradeallurl = $GLOBALS['babUrlScript']."?tg=addons&idx=list&upgradeall=1";

			$h = opendir($GLOBALS['babAddonsPath']);
			while (($f = readdir($h)) != false)
				{
				if ($f != "." and $f != "..") 
					{
					if (is_dir($GLOBALS['babAddonsPath'].$f) && is_file($GLOBALS['babAddonsPath'].$f."/init.php"))
						{
						$res = $this->db->db_query("select * from ".BAB_ADDONS_TBL." where title='".$f."'");
						if( $res && $this->db->db_num_rows($res) < 1)
							{
							$this->db->db_query("insert into ".BAB_ADDONS_TBL." (title, enabled) values ('".$f."', 'Y')");
							}
						}
					}
				}
			closedir($h);

			$res = $this->db->db_query("select * from ".BAB_ADDONS_TBL."");
			while($row = $this->db->db_fetch_array($res))
				{
				if (!is_dir($GLOBALS['babAddonsPath'].$row['title']) || !is_file($GLOBALS['babAddonsPath'].$row['title']."/init.php"))
					{
					$this->db->db_query("delete from ".BAB_ADDONS_TBL." where id='".$row['id']."'");
					$this->db->db_query("delete from ".BAB_ADDONS_GROUPS_TBL." where id_object='".$row['id']."'");
					$this->db->db_query("delete from ".BAB_SECTIONS_ORDER_TBL." where id_section='".$row['id']."' and type='4'");
					$this->db->db_query("delete from ".BAB_SECTIONS_STATES_TBL." where id_section='".$row['id']."' and type='4'");
					}
				}
			$this->res = $this->db->db_query("select * from ".BAB_ADDONS_TBL."");
			$this->count = $this->db->db_num_rows($this->res);
			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$this->arr = $this->db->db_fetch_array($this->res);
				$this->title = $this->arr['title'];
				$this->url = $GLOBALS['babUrlScript']."?tg=addons&idx=mod&item=".$this->arr['id'];
				$this->viewurl = $GLOBALS['babUrlScript']."?tg=addons&idx=view&item=".$this->arr['id'];
				if( $this->arr['enabled'] == "N")
					$this->catchecked = "checked";
				else
					$this->catchecked = "";
				$arr_ini = @parse_ini_file( $GLOBALS['babAddonsPath'].$this->arr['title']."/addonini.php");
				$this->addversion = "";
				$this->description = "";
				$this->upgradeurl = false;
				if( !empty($arr_ini['version']))
					{
					$this->addversion = $this->arr['version'];
					if ( compare_versions($this->arr['version'],$arr_ini['version']) || $this->arr['version'] == "" )
						{
						$func_name = $this->arr['title']."_upgrade";
						if (is_file($GLOBALS['babAddonsPath'].$this->arr['title']."/init.php"))
							require_once( $GLOBALS['babAddonsPath'].$this->arr['title']."/init.php" );
						if ( $this->upgradeall )
							{
							if ((function_exists($func_name) && $func_name($this->arr['version'],$arr_ini['version'])) || !function_exists($func_name))
								{
								$req = "update ".BAB_ADDONS_TBL." set version='".$arr_ini['version']."' where id='".$this->arr['id']."'";
								$this->db->db_query($req);
								$this->addversion = $arr_ini['version'];
								}
							else
								$this->upgradeurl = $GLOBALS['babUrlScript']."?tg=addons&idx=upgrade&item=".$this->arr['id'];
							}
						elseif (!function_exists($func_name))
							{
							$req = "update ".BAB_ADDONS_TBL." set version='".$arr_ini['version']."' where id='".$this->arr['id']."'";
							$this->db->db_query($req);
							$this->addversion = $arr_ini['version'];
							}
						else
							$this->upgradeurl = $GLOBALS['babUrlScript']."?tg=addons&idx=upgrade&item=".$this->arr['id'];
						}
					
					}
				if( !empty($arr_ini['description']))
					$this->description = $arr_ini['description'];
				$i++;
				return true;
				}
			else
				return false;

			}
		}

	$temp = new temp($upgradeall);
	$babBody->babecho(	bab_printTemplate($temp, "addons.html", "addonslist"));
	}

function disableAddons($addons)
	{
	$db = $GLOBALS['babDB'];
	$req = "select id from ".BAB_ADDONS_TBL."";
	$res = $db->db_query($req);
	while( $row = $db->db_fetch_array($res))
		{
		if( count($addons) > 0 && in_array($row['id'], $addons))
			$enabled = "N";
		else
			$enabled = "Y";

		$req = "update ".BAB_ADDONS_TBL." set enabled='".$enabled."' where id='".$row['id']."'";
		$db->db_query($req);
		}
	}

function upgrade($id)
	{
	$db = $GLOBALS['babDB'];
	$res = $db->db_query("select * from ".BAB_ADDONS_TBL." where id='".$id."'");
	$row = $db->db_fetch_array($res);

	if (is_dir($GLOBALS['babAddonsPath'].$row['title']) && is_file($GLOBALS['babAddonsPath'].$row['title']."/init.php") && is_file($GLOBALS['babAddonsPath'].$row['title']."/addonini.php"))
		{
		$arr_ini = @parse_ini_file( $GLOBALS['babAddonsPath'].$row['title']."/addonini.php");

		if( !empty($arr_ini['version']))
			{
			$func_name = $row['title']."_upgrade";
			if ( compare_versions($row['version'],$arr_ini['version']) || $row['version'] == "" )
				{
				require_once( $GLOBALS['babAddonsPath'].$row['title']."/init.php" );
				if ((function_exists($func_name) && $func_name($row['version'],$arr_ini['version'])) || !function_exists($func_name))
					{
					$req = "update ".BAB_ADDONS_TBL." set version='".$arr_ini['version']."' where id='".$id."'";
					$db->db_query($req);
					return true;
					}
				else
					return false;
				}
			}
		}
	}

/* main */
if( !isset($idx))
	$idx = "list";

if( isset($update))
	{
	if( !isset($addons))
		$addons = array();
	if( $update == "disable" )
		disableAddons($addons);
	}

if( isset($acladd))
	{
	aclUpdate($table, $item, $groups, $what);
	}

switch($idx)
	{
	case "view":
		$babBody->title = bab_translate("Access to Add-on")." :".getAddonName($item);
		aclGroups("addons", "list", BAB_ADDONS_GROUPS_TBL, $item, "acladd");
		$babBody->addItemMenu("list", bab_translate("Add-ons"), $GLOBALS['babUrlScript']."?tg=addons&idx=list");
		$babBody->addItemMenu("view", bab_translate("Access"), $GLOBALS['babUrlScript']."?tg=addons&idx=view&item=".$item);
		break;
	
	case "upgrade":
		upgrade($item);

	case "list":
	default:
		addonsList($upgradeall);
		$babBody->title = bab_translate("Add-ons list");
		$babBody->addItemMenu("list", bab_translate("Add-ons"), $GLOBALS['babUrlScript']."?tg=addons&idx=list");
		break;
	}

$babBody->setCurrentItemMenu($idx);

?>