<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
include $babInstallPath."admin/acl.php";

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

function addonsList()
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

		function temp()
			{
			$this->name = bab_translate("Name");
			$this->desctxt = bab_translate("Description");
			$this->disabled = bab_translate("Disabled");
			$this->uncheckall = bab_translate("Uncheck all");
			$this->checkall = bab_translate("Check all");
			$this->update = bab_translate("Update");
			$this->view = bab_translate("View");
			$this->versiontxt = bab_translate("Version");
			$this->db = $GLOBALS['babDB'];

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
				$arr_ini = @parse_ini_file( $GLOBALS['babAddonsPath'].$this->arr['title']."/addonini.php", true );
				$this->addversion = "";
				$this->description = "";
				if( !empty($arr_ini['general']['version']))
					$this->addversion = $arr_ini['general']['version'];
				if( !empty($arr_ini['general']['description']))
					$this->description = $arr_ini['general']['description'];
				$i++;
				return true;
				}
			else
				return false;

			}
		}

	$temp = new temp();
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

/* main */
if( !isset($idx))
	$idx = "list";

if( isset($update))
	{
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
		$babBody->addItemMenu("list", bab_translate("List"), $GLOBALS['babUrlScript']."?tg=addons&idx=list");
		$babBody->addItemMenu("view", bab_translate("Access"), $GLOBALS['babUrlScript']."?tg=addons&idx=view&item=".$item);
		break;

	case "list":
	default:
		addonsList();
		$babBody->title = bab_translate("Add-ons list");
		$babBody->addItemMenu("list", bab_translate("List"), $GLOBALS['babUrlScript']."?tg=addons&idx=list");
		break;
	}

$babBody->setCurrentItemMenu($idx);

?>