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

$GLOBALS['addons_files_location'] = 
array('loc_in' => array("addons",
				"lang/addons",
				"skins/ovidentia/templates/addons",
				"skins/ovidentia/ovml/addons",
				"skins/ovidentia/images/addons"),			
	'loc_out' => array("programs",
				"langfiles",
				"skins/ovidentia/templates",
				"skins/ovidentia/ovml",
				"skins/ovidentia/images"));

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
		var $altbg = true;

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
				$this->altbg = !$this->altbg;
				$this->arr = $this->db->db_fetch_array($this->res);
				$this->title = $this->arr['title'];
				$this->url = $GLOBALS['babUrlScript']."?tg=addons&idx=mod&item=".$this->arr['id'];
				$this->viewurl = $GLOBALS['babUrlScript']."?tg=addons&idx=view&item=".$this->arr['id'];
				$this->exporturl = $GLOBALS['babUrlScript']."?tg=addons&idx=export&item=".$this->arr['id'];
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
							{
							$GLOBALS['babAddonFolder'] = $this->arr['title'];
							$GLOBALS['babAddonPhpPath'] = $GLOBALS['babInstallPath']."addons/". $this->arr['title']."/";
							$GLOBALS['babAddonHtmlPath'] = "addons/". $this->arr['title']."/";
							require_once( $GLOBALS['babAddonsPath'].$this->arr['title']."/init.php" );
							}
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
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=addons&idx=list");
	exit;
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
				$GLOBALS['babAddonFolder'] = $row['title'];
				$GLOBALS['babAddonPhpPath'] = $GLOBALS['babInstallPath']."addons/".$row['title']."/";
				$GLOBALS['babAddonHtmlPath'] = "addons/".$row['title']."/";
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

function export($id)
	{
	function rd($d)
		{
		$res = array();
		$d = substr($d,-1) != '/' ? $d.'/' : $d;
		if (is_dir($d))
			{
			$handle=opendir($d);
			while ($file = readdir($handle)) 
				{
				if ($file != "." && $file != "..") 
					{
					if (is_dir($d.$file)) $res = array_merge($res, rd($d.$file));
					elseif (is_file($d.$file)) $res[] = $d.$file;
					}
				}
			closedir($handle);
			}
		return $res;
		}
		
	class addon_txt
		{
		function addon_txt($arr)
			{
			$this->arr_ini = $arr;
			$this->year = date('Y');
			$this->date = date('d/m/Y');
			}
		}
	
	$db = $GLOBALS['babDB'];
	$res = $db->db_query("select * from ".BAB_ADDONS_TBL." where id='".$id."'");
	$row = $db->db_fetch_array($res);
	
	if (is_dir($GLOBALS['babAddonsPath'].$row['title']) && is_file($GLOBALS['babAddonsPath'].$row['title']."/addonini.php"))
		{
		$arr_ini = @parse_ini_file( $GLOBALS['babAddonsPath'].$row['title']."/addonini.php");
		if(!empty($arr_ini['version']))
			$version = str_replace('.','-',$arr_ini['version']);
			
		if (!empty($arr_ini['db_prefix']))
			{
			$res = $db->db_query("SHOW TABLES LIKE '".$arr_ini['db_prefix']."%'");
			$arr_ini['tbllist'] = '';
			while(list($tbl) = $db->db_fetch_array($res))
				$arr_ini['tbllist'] .= ($arr_ini['tbllist'] != '') ? ','.$tbl : $tbl;
			}
			
		if (!empty($arr_ini['description']) && empty($arr_ini['longdesc']))
			$arr_ini['longdesc'] = $arr_ini['description'];

		$arr_ini['title'] = $row['title'];
		$arr_to_init = array('title','description','version','db_prefix','ov_version','author','longdesc','tbllist');
		
		foreach ($arr_to_init as $field)
			$arr_ini[$field] = isset($arr_ini[$field]) ? $arr_ini[$field] : '';
		
		$temp = new addon_txt($arr_ini);
		$addon_txt = bab_printTemplate($temp, "addons.html", "addon_txt");
		$addarr[] = array('description.html',$addon_txt);
		}
	
	$loc_in = $GLOBALS['addons_files_location']['loc_in'];
	$loc_out = $GLOBALS['addons_files_location']['loc_out'];
			
	include $GLOBALS['babInstallPath']."utilit/zip.lib.php";
	$zip = new Zip;
	$res = array();
	foreach ($loc_in as $k => $path)
		{
		$res = rd($GLOBALS['babInstallPath'].$path.'/'.$row['title']);
		$len = strlen($GLOBALS['babInstallPath'].$path.'/'.$row['title']);
		foreach ($res as $file)
			{
			if (is_file($file))
				{
				$rec_into = $loc_out[$k].substr($file,$len);
				$fp=fopen($file,"r");
				$contents = fread ($fp, filesize($file));
				fclose($fp);			
				$addarr[] = array($rec_into,$contents);
				}
			}
		}
	$zip->Add($addarr,0);
	header("Content-Type:application/zip");
	header("Content-Disposition: attachment; filename=".$row['title'].'-'.$version.".zip");
	die($zip->get_file());
	}


function upload()
{
	global $babBody;
	class temp
		{
		function temp()
			{
			$this->t_button = bab_translate("Upload");
			}
		}

	$temp = new temp();
	$babBody->babecho(	bab_printTemplate($temp, "addons.html", "upload"));
}

function import()
	{
	if( !empty($_FILES['uploadf']['name']) && $_FILES['uploadf']['type'] == "application/zip")
		{
		if( $_FILES['uploadf']['size'] > $GLOBALS['babMaxFileSize'])
			{
			$babBody->msgerror = bab_translate("The file was greater than the maximum allowed") ." :". $GLOBALS['babMaxFileSize'];
			return false;
			}
		include_once $GLOBALS['babInstallPath']."utilit/fileincl.php";
		$totalsize = getDirSize('addons');
		if( $_FILES['uploadf']['size'] + $totalsize > $GLOBALS['babMaxTotalSize'])
			{
			$babBody->msgerror = bab_translate("There is not enough free space");
			return false;
			}
		if( !get_cfg_var('safe_mode'))
			set_time_limit(0);
		
		include_once $GLOBALS['babInstallPath']."utilit/zip.lib.php";
		$zip = new Zip;
		$zipcontents = $zip->get_List($_FILES['uploadf']['tmp_name']);
		
		$loc_in = $GLOBALS['addons_files_location']['loc_in'];
		$loc_out = $GLOBALS['addons_files_location']['loc_out'];
		
		foreach ($loc_in as $directory)
			{
			if (!is_dir($GLOBALS['babInstallPath'].$directory))
				@mkdir($GLOBALS['babInstallPath'].$directory,0777);
			}
		
		$path_file = array();
		$file_zipid = array();
		
		foreach ($zipcontents as $k => $arr)
			{
			$tmppath = substr($arr['filename'],0,strrpos($arr['filename'],'/'));
			if (!empty($tmppath))
				{
				if (!is_array($path_file[$tmppath])) $path_file[$tmppath] = array();
				foreach ($loc_out as $key => $zippath)
					{
					if ($arr['folder'] == 0 && substr_count($arr['filename'],$zippath))
						{
						$file_zipid[] = array($key,$arr['index'],$k);
						}
					}
				}
			}

		$fn = substr($_FILES['uploadf']['name'],0,strrpos($_FILES['uploadf']['name'],'.'));
		$arr = explode('-',$fn);
		$i = 0;
		while(isset($arr[$i]) && !is_numeric($arr[$i]))
			{
			if (isset($addon_name))
				$addon_name .= '-'.$arr[$i];
			else $addon_name = $arr[$i];
			$i++;
			}
			
		if (empty($addon_name) || count($path_file) == 0) return false;
		
		foreach ($file_zipid as $arr)
			{
			$path = $GLOBALS['babInstallPath'].$loc_in[$arr[0]].'/'.$addon_name;
			$subdir = dirname(substr($zipcontents[$arr[2]]['filename'],strlen($loc_out[$arr[0]])+1));
			$subdir = isset($subdir) && $subdir != '.' ? '/'.$subdir : '';
			$zip->Extract($_FILES['uploadf']['tmp_name'],$path.$subdir,$arr[1],false );
			}
		}
	}

/* main */
if( !$babBody->isSuperAdmin )
{
	$babBody->msgerror = bab_translate("Access denied");
	return;
}

if( !isset($idx))
	$idx = "list";

if( !isset($upgradeall))
	$upgradeall = '';

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
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=addons&idx=list&errormsg=".urlencode($babBody->msgerror));
	}

if (isset($action) && $action == 'import')
	import();

switch($idx)
	{
	case "view":
		$babBody->title = bab_translate("Access to Add-on")." :".getAddonName($item);
		aclGroups("addons", "list", BAB_ADDONS_GROUPS_TBL, $item, "acladd");
		$babBody->addItemMenu("list", bab_translate("Add-ons"), $GLOBALS['babUrlScript']."?tg=addons&idx=list");
		$babBody->addItemMenu("view", bab_translate("Access"), $GLOBALS['babUrlScript']."?tg=addons&idx=view&item=".$item);
		break;

	case "upload":
		$babBody->addItemMenu("list", bab_translate("Add-ons"), $GLOBALS['babUrlScript']."?tg=addons&idx=list");
		$babBody->title = bab_translate("Upload");
		upload();
		break;

	case "export":
		export($item);
		break;
	
	case "upgrade":
		upgrade($item);
		Header("Location: ". $GLOBALS['babUrlScript']."?tg=addons&idx=list&errormsg=".urlencode($babBody->msgerror));
		exit;

	case "list":
	default:
		if (isset($errormsg)) $babBody->msgerror = urldecode($errormsg);
		addonsList($upgradeall);
		$babBody->title = bab_translate("Add-ons list");
		$babBody->addItemMenu("list", bab_translate("Add-ons"), $GLOBALS['babUrlScript']."?tg=addons&idx=list");
		break;
	}
$babBody->addItemMenu("upload", bab_translate("Upload"), $GLOBALS['babUrlScript']."?tg=addons&idx=upload");
$babBody->setCurrentItemMenu($idx);

?>