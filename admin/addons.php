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
include_once $babInstallPath."admin/acl.php";
include_once $GLOBALS['babInstallPath'].'utilit/addonsincl.php';
include_once $GLOBALS['babInstallPath'].'utilit/inifileincl.php';




function getAddonName($id)
	{
	if( $row = bab_addonsInfos::getDbRow($id))
		{
		return $row['title'];
		}
	else
		{
		return "";
		}
	}


function callSingleAddonFunction($id,$name,$func)
{
	
	$addonpath = $GLOBALS['babAddonsPath'].$name;
	if( is_file($addonpath."/init.php" ))
		{
		bab_setAddonGlobals($id);
		
		require_once( $addonpath."/init.php" );
		$call = $name."_".$func;
		if( function_exists($call) )
			{
			return $call();
			}
		}
	return true;
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
		var $altbg = true;

		function temp()
			{
			include_once $GLOBALS['babInstallPath'].'utilit/addonsincl.php';
			
			$this->display_in_form = true;
			$this->title = false;

			$this->name = bab_translate("Name");
			$this->desctxt = bab_translate("Description");
			$this->upgradetxt = bab_translate("Upgrade");
			$this->disabled = bab_translate("Disabled");
			$this->uncheckall = bab_translate("Uncheck all");
			$this->checkall = bab_translate("Check all");
			$this->update = bab_translate("Update");
			$this->view = bab_translate("Rights");
			$this->versiontxt = bab_translate("Version");
			$this->t_delete = bab_translate("Delete");
			$this->t_historic = bab_translate("Historic");
			$this->t_download = bab_translate("Download");
			$this->confirmdelete = bab_toHtml(bab_translate("Are you sure you want to delete this add-on ?"), BAB_HTML_JS);
			
			bab_addonsInfos::insertMissingAddonsInTable();
			bab_addonsInfos::deleteObsoleteAddonsInTable();
			bab_addonsInfos::clear();
			
			$this->res = $this->getRes();
		}
			
		function getRes() {
			$res = bab_addonsInfos::getDbRowsByName();
			$return = array();
			foreach($res as $name => $row) {
				$addon = bab_getAddonInfosInstance($name);
				if ($this->display($addon)) {
					$return[$name] = $addon;
				}
			}
			
			uksort($return, 'strcasecmp');
			
			return $return;
		}
		
		
		
		function display($addon) {
			return $addon->hasAccessControl();
		}
		
		
			

		function getnext()
			{
			
			if( list(,$addon) = each($this->res))
				{
				$this->altbg = !$this->altbg;
				
				
				
				$this->title 			= bab_toHtml($addon->getName());
				$this->requrl 			= bab_toHtml($GLOBALS['babUrlScript']."?tg=addons&idx=requirements&item=".$addon->getId());
				$this->viewurl 			= bab_toHtml($GLOBALS['babUrlScript']."?tg=addons&idx=view&item=".$addon->getId());
				$this->exporturl 		= bab_toHtml($GLOBALS['babUrlScript']."?tg=addons&idx=export&item=".$addon->getId());
				

				$addon->updateInstallStatus();
				
				$this->id_addon 		= $addon->getId();
				
				$this->catchecked 		= $addon->isDisabled();
				$this->access_control 	= $addon->hasAccessControl();
				$this->delete 			= $addon->isDeletable();
				$this->addversion 		= $addon->getDbVersion();
				$this->description 		= $addon->getDescription();
				
				if ($addon->isUpgradable()) {
					$this->upgradeurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=addons&idx=upgrade&item=".$addon->getId());
				} else {
					$this->upgradeurl = false;
				}


				if (is_file($addon->getPhpPath()."history.txt"))
					$this->history = bab_toHtml($GLOBALS['babUrlScript']."?tg=addons&idx=history&item=".$addon->getId());
				else
					$this->history = false;

				return true;
				}
			
			return false;
			}
		}
		

	class temp2 extends temp {
	
		function temp2() {
			parent::temp();
			
			$this->display_in_form = false;
			
			$this->title = bab_translate('Shared Libraries');
		}
	
		function display($addon) {
			return false === $addon->hasAccessControl();
		}
	}


	$temp = new temp();
	$babBody->babecho(bab_printTemplate($temp, "addons.html", "addonslist"));
	
	$temp2 = new temp2();
	$babBody->babecho(bab_printTemplate($temp2, "addons.html", "addonslist"));
	
	}



/**
 * Disable of enable addons
 */
function disableAddons($addons) {
	
	$addons = (array) $addons;
	$kaddons = array_flip($addons);
	
	foreach(bab_addonsInfos::getDbRows() as $row) {
	
		$addon = bab_getAddonInfosInstance($row['title']);
	
		if (isset($kaddons[$row['id']])) {
			$addon->disable();
		} else {
			$addon->enable();
		}
	}

	global $babDB;

	
	// if an addon has put some events in vacation cache
	$babDB->db_query("TRUNCATE bab_vac_calendar");
	bab_siteMap::clearAll();
	
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=addons&idx=list");
	exit;
}




function upgrade($id) {
	global $babDB;
	
	$row = bab_addonsInfos::getDbRow($id);
	$addon = bab_getAddonInfosInstance($row['title']);
	
	if (!$addon->isValid()) {
		header("Location: ". $GLOBALS['babUrlScript']."?tg=addons&idx=requirements&item=".$id);
		exit;
	}
	
	$addon->upgrade();
	bab_siteMap::clearAll();
}



function export($id)
	{

	set_time_limit(0);
	
	
	if (!function_exists('bab_addon_export_rd')) {
		function bab_addon_export_rd($d) {
			$res = array();
			$d = substr($d,-1) != '/' ? $d.'/' : $d;
			if (is_dir($d)) {
				$handle=opendir($d);
				while ($file = readdir($handle)) {
					if ($file != "." && $file != "..") {
						if (is_dir($d.$file) && 'CVS' != $file && '.CVS' != $file ) {
							$res = array_merge($res, bab_addon_export_rd($d.$file));
						} elseif (is_file($d.$file)) $res[] = $d.$file;
					}
				}
				closedir($handle);
			}
			return $res;
		}
	}
	
	$row = bab_addonsInfos::getDbRow($id);

	if (!callSingleAddonFunction($row['id'], $row['title'], 'onPackageAddon'))
		{
		return;
		}

		
	$addons_files_location = bab_getAddonsFilePath();
	
	$loc_in = $addons_files_location['loc_in'];
	$loc_out = $addons_files_location['loc_out'];
			
	include_once $GLOBALS['babInstallPath']."utilit/zip.lib.php";
	$zip = new Zip;
	$res = array();
	foreach ($loc_in as $k => $path)
		{
		$res = bab_addon_export_rd($path.'/'.$row['title']);
		$len = strlen($path.'/'.$row['title']);
		foreach ($res as $file)
			{
			if (is_file($file))
				{
				$rec_into = $loc_out[$k].substr($file,$len);
				$size = filesize($file);
				if ($size > 0)
					{
					$fp=fopen($file,"r");
					$contents = fread($fp, $size);
					fclose($fp);
					}
				else
					$contents = '';			
				$addarr[] = array($rec_into,$contents);
				}
			}
		}
	$zip->Add($addarr,1);
	
	
	$version = str_replace('.','-',$row['version']);
	

	header("Content-Type:application/zip");
	header("Content-Disposition: attachment; filename=".$row['title'].'-'.$version.".zip");
	die($zip->get_file());
	}
	
	
function del($id)
	{
	global $babBody;
	$db = $GLOBALS['babDB'];
	$row = bab_addonsInfos::getDbRow($id);

	if (!callSingleAddonFunction($row['id'], $row['title'], 'onDeleteAddon'))
		{
		return;
		}
		
	// if addon return true, the addon is uninstalled in the table.
	$db->db_query("UPDATE ".BAB_ADDONS_TBL." SET installed='N' where id='".$db->db_escape_string($id)."'");
	
	

	if (is_dir($GLOBALS['babAddonsPath'].$row['title']) && is_file($GLOBALS['babAddonsPath'].$row['title']."/addonini.php"))
		{
		$arr_ini = @parse_ini_file( $GLOBALS['babAddonsPath'].$row['title']."/addonini.php");

		if (isset($arr_ini['delete']) && $arr_ini['delete'] == 1 )
			{
			$tbllist = array();
			if (!empty($arr_ini['db_prefix']) && strlen($arr_ini['db_prefix']) >= 3 && substr($arr_ini['db_prefix'],0,3) != 'bab')
				{
				$res = $db->db_query("SHOW TABLES LIKE '".$db->db_escape_like($arr_ini['db_prefix'])."%'");
				while(list($tbl) = $db->db_fetch_array($res))
					$tbllist[] = $tbl;
				}
				
			function deldir($dir)
				{
				global $babBody;
				
				
				$current_dir = opendir($dir);
				while($entryname = readdir($current_dir)){
					if(is_dir("$dir/$entryname") and ($entryname != "." and $entryname!="..")){
					   	if (false === deldir($dir.'/'.$entryname)) {
							return false;
						}
					 } elseif ($entryname != "." and $entryname!="..") {
					   	if (false === unlink($dir.'/'.$entryname)) {
					   		$babBody->addError(sprintf(bab_translate('delete does not work on file %s'), $dir.'/'.$entryname));
							return false;
						}
					}
				}
				closedir($current_dir);
				rmdir($dir);
				return true;
			}
				
			
			$addons_files_location = bab_getAddonsFilePath();
			
			$loc_in = $addons_files_location['loc_in'];	
			
			foreach ($loc_in as $path)
				{
				if (is_dir($path.'/'.$row['title'])) {
					if (false === deldir($path.'/'.$row['title'])) {

						$babBody->addError(bab_translate('The addon files are not deleteable'));
						return false;
						}
					}
				}
				
			if (count($tbllist) > 0)
				{
				foreach($tbllist as $tbl)
					{
					$db->db_query("DROP TABLE ".$tbl."");
					}
				}
			}
		}
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


function upload_tmpfile() {
	if( !empty($_FILES['uploadf']['name'])) {
		if( $_FILES['uploadf']['size'] > $GLOBALS['babMaxFileSize']) {
			$babBody->msgerror = bab_translate("The file was greater than the maximum allowed") ." :". $GLOBALS['babMaxFileSize'];
			return false;
		}
		include_once $GLOBALS['babInstallPath']."utilit/fileincl.php";
		$totalsize = getDirSize('addons');
		if( $_FILES['uploadf']['size'] + $totalsize > $GLOBALS['babMaxTotalSize']) {
			$babBody->msgerror = bab_translate("There is not enough free space");
			return false;
		}

		if( !get_cfg_var('safe_mode')) {
			set_time_limit(0);
		}

		if (!is_dir($GLOBALS['babUploadPath'].'/tmp/')) {
			bab_mkdir($GLOBALS['babUploadPath'].'/tmp/',$GLOBALS['babMkdirMode']);
		}

		$ul = $GLOBALS['babUploadPath'].'/tmp/'.$_FILES['uploadf']['name'];
		if (move_uploaded_file($_FILES['uploadf']['tmp_name'],$ul))
			return $ul;
	}

	return false;
}


function test_requirements()
{
	include_once $GLOBALS['babInstallPath'].'utilit/inifileincl.php';
	include_once $GLOBALS['babInstallPath'].'utilit/addonsincl.php';
	global $babBody;
	class temp {
		function temp()
			{
			$this->item = bab_rp('item');
			$this->installed = false;
			
			$ini = new bab_inifile();
			if (isset($_FILES['uploadf'])) {
				$ul = upload_tmpfile();

				if (false === $ul) {
					$GLOBALS['babBody']->msgerror = bab_translate("Upload error");
				}

				$name = $filename = substr( $ul,(strrpos( $ul,'/')+1));
				$this->tmpfile = bab_toHtml($filename);
				$this->action = 'import';
				$ini->getfromzip($ul, 'programs/addonini.php');
				
				$this->t_install = bab_translate("Install");

			} elseif (isset($_GET['item'])) {

				$row = bab_addonsInfos::getDbRow($_GET['item']);
				$addon = bab_getAddonInfosInstance($row['title']);
				$this->installed = $addon->isInstalled();
				$this->dependences = $addon->getDependences();
				
				
				if (!is_file($addon->getPhpPath()."addonini.php"))
					return;
				$ini->inifile($addon->getPhpPath()."addonini.php");
				$this->tmpfile = '';
				$this->action = 'upgrade';
				
				$this->t_install = bab_translate("Upgrade");
				
				$name = $addon->getName();
				
				
			}

			$this->name = bab_toHtml($name);
			$this->adescription = bab_toHtml($ini->getDescription());
			$this->version = bab_toHtml($ini->getVersion());
			
			$this->requirements = $ini->getRequirements();

			$this->t_requirements = bab_translate("Requirements");
			$this->t_recommended = bab_translate("Recommended");
			$this->t_dependencies = bab_translate("Dependencies");
			$this->t_required = bab_translate("Required value");
			$this->t_current = bab_translate("Current value");
			$this->t_addon = bab_translate("Addon");
			$this->t_description = bab_translate("Description");
			$this->t_version = bab_translate("Version");
			$this->t_ok = bab_translate("Ok");
			$this->t_error = bab_translate("Error");
			
			$this->call_upgrade = isset($_COOKIE['bab_debug']);
			$this->t_call_upgrade = bab_translate("Launch addon installation program");

			$this->allok = $ini->isValid();
		}

		function getnextreq() {
			if (list(,$arr) = each($this->requirements)) {
				$this->description = bab_toHtml($arr['description']);
				$this->recommended = bab_toHtml($arr['recommended']);
				$this->required = bab_toHtml($arr['required']);
				$this->current = bab_toHtml($arr['current']);
				$this->result = $arr['result']; 
				return true;
			}
			return false;
		}
		
		
		function getnextdependence() {
			if (!isset($this->dependences)) {
				return false;
			}
			
			if (list($name, $status) = each($this->dependences)) {
				$this->addonname = bab_toHtml($name);
				if ($addon = bab_getAddonInfosInstance($name)) {
					$this->addonurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=addons&idx=requirements&item=".$addon->getId());
				}
				
				return true;
			}
			
			return false;
		}
		
	}

	$temp = new temp();
	$babBody->babecho(	bab_printTemplate($temp, "addons.html", "requirements"));
}

/**
 * Unzip temporary file
 */
function import()
	{
	if( !get_cfg_var('safe_mode')) {
		set_time_limit(0);
	}
	
	global $babBody;
	

	if( !empty($_POST['tmpfile']))
		{

		$ul = $GLOBALS['babUploadPath'].'/tmp/'.$_POST['tmpfile'];

		if (!is_file($ul))
			return false;

		

		include_once $GLOBALS['babInstallPath'].'utilit/inifileincl.php';
		$ini = new bab_inifile();
		$ini->getfromzip($ul, 'programs/addonini.php');
		
		if (false === $ini->isValid()) {
			return false;
		}


		$addon_name = $ini->getName();

		if (false === $addon_name) {

			$fn = substr($_POST['tmpfile'],0,strrpos($_POST['tmpfile'],'.'));
			$arr = explode('-',$fn);
			$i = 0;
			while(isset($arr[$i]) && !is_numeric($arr[$i]))
				{
				if (isset($addon_name) && false != $addon_name)
					$addon_name .= '-'.$arr[$i];
				else $addon_name = $arr[$i];
				$i++;
				}
		}

		$db = $GLOBALS['babDB'];
		$db->db_query("UPDATE ".BAB_ADDONS_TBL." SET installed='N' WHERE title=".$db->quote($addon_name));

		include_once $GLOBALS['babInstallPath']."utilit/zip.lib.php";
		$zip = new Zip;
		$zipcontents = $zip->get_List($ul);
		
		$addons_files_location = bab_getAddonsFilePath();
		
		$loc_in = $addons_files_location['loc_in'];
		$loc_out = $addons_files_location['loc_out'];
		
		foreach ($loc_in as $directory) {
			if (!is_dir($directory)) {
				if (!bab_mkdir($directory, 0777)) {
					return false;
				}
			}
				
			if (!is_writable($directory)) {
				$babBody->addError(sprintf(bab_translate('The directory %s is not writable'), $directory));
				return false;
			}
		}
		
		$path_file = array();
		$file_zipid = array();
		
		foreach ($zipcontents as $k => $arr)
			{
			$tmppath = substr($arr['filename'],0,strrpos($arr['filename'],'/'));
			if (!empty($tmppath))
				{
				if (!isset($path_file[$tmppath])) $path_file[$tmppath] = array();
				foreach ($loc_out as $key => $zippath)
					{
					if ($arr['folder'] == 0 && substr_count($arr['filename'],$zippath))
						{
						$file_zipid[] = array($key,$arr['index'],$k);
						}
					}
				}
			}

		
			
		if (empty($addon_name) || count($path_file) == 0) 
			return false;


		function create_directory($str) {
			$path = explode('/',$str);
			$created = '';
			foreach($path as $d) {
			
				if (!empty($created)) {
					$created .= '/';
				}
			
				$created.= $d;
				if (!is_dir($created)) {
					if (!bab_mkdir($created, 0777)) {
						return false;
					}
				}
			}
		}
		
		foreach ($file_zipid as $arr)
			{
			$path = $loc_in[$arr[0]].'/'.$addon_name;
			$subdir = dirname(substr($zipcontents[$arr[2]]['filename'],strlen($loc_out[$arr[0]])+1));
			$subdir = isset($subdir) && $subdir != '.' ? '/'.$subdir : '';
			create_directory($path.$subdir);
			$zip->Extract($ul,$path.$subdir,$arr[1],false );
			}

		@unlink($ul);
		
		bab_addonsInfos::insertMissingAddonsInTable();
		bab_addonsInfos::clear();

		$addon = bab_getAddonInfosInstance($addon_name);
		$addon->upgrade();
		}
	}


function history($item)
	{
	global $babBody;
	class temp
		{
		function temp($item)
			{
			$this->t_title = bab_translate("Historic");
			$this->t_close = bab_translate("Close");

			$db = &$GLOBALS['babDB'];
			list($title) = $db->db_fetch_array($db->db_query("SELECT title FROM ".BAB_ADDONS_TBL." where id='".$item."'"));
			if (!empty($title))
				{
				$this->history = implode('',file($GLOBALS['babAddonsPath'].$title.'/history.txt'));
				$this->history = bab_toHtml($this->history, BAB_HTML_ALL);
				}
			else
				$this->history = '';
			}
		}

	$temp = new temp($item);
	$babBody->babpopup(bab_printTemplate($temp, "addons.html", "history"));
	}
	
	
/**
 * test if functionalities are installed
 * @return boolean
 */
function haveFunctionalities() {
	require_once $GLOBALS['babInstallPath'] . 'utilit/functionalityincl.php';
	$func = new bab_functionalities();
	$childs = $func->getChildren('');
	
	return 0 !== count($childs);
}
	
	
function functionalities() {
	require_once $GLOBALS['babInstallPath'] . 'utilit/tree.php';
	require_once $GLOBALS['babInstallPath'] . 'utilit/functionalityincl.php';
	require_once $GLOBALS['babInstallPath'] . 'utilit/urlincl.php';
	
	
	
	$func = new bab_functionalities();
	
	
	if ($uppath = bab_gp('uppath', false)) {
	
		$func->copyToParent($uppath);
	
		header('location:'.bab_url::request('tg', 'idx'));
		exit;
	}
	
	
	
	
	
	$tree = new bab_TreeView('bab_functionalities');

	$root = & $tree->createElement( 'R', 'directory', bab_translate('All functionalities'), '', '');
	$root->setIcon($GLOBALS['babSkinPath'] . 'images/nodetypes/category.png');					
	$tree->appendElement($root, NULL);


	function buid_nodeLevel(&$tree, $node, $id, $path) {
		$func = new bab_functionalities();
		$childs = $func->getChildren($path);
		
		$i = 1;
		foreach ($childs as $dir) {
		
			$funcpath = trim($path.'/'.$dir, '/');
			$obj = bab_functionality::get($funcpath);
			
			if (false !== $obj) {
				$original = $func->getOriginal($funcpath);
			
				$labelpath = $obj->getPath();
				if (false !== strpos($labelpath,'/')) {
					$labelpath = substr(strrchr($labelpath,'/'),1);
				}
				
				if ($labelpath !== $dir) {
					$labelpath = $dir . ' ('.$labelpath.')';
				}
				
				$description = $labelpath.' : '.$original->getDescription();
			} else {
				$description = $dir;
			}
			
			
			
			$element = & $tree->createElement( $id.'.'.$i, 'directory', $description, '', '');
		
		
			
			
			
			
			
			if (false === buid_nodeLevel($tree, $element, $id.'.'.$i, $funcpath)) {
				$element->setIcon($GLOBALS['babSkinPath'] . 'images/nodetypes/topic.png');
			
			} else {
				$element->setIcon($GLOBALS['babSkinPath'] . 'images/nodetypes/folder.png');
			}
			
			
			
			
			if (0 < substr_count($funcpath, '/')) {
			
				$parent_path = $func->getParentPath($funcpath);
				$parent_obj = bab_functionality::get($parent_path);
				
				if (false !== $obj && false !== $parent_obj && $parent_obj->getPath() !== $obj->getPath()) {

					$element->addAction('moveup',
									bab_translate('Move Up'),
									$GLOBALS['babSkinPath'] . 'images/Puces/go-up.png',
									$GLOBALS['babUrlScript'].'?tg=addons&idx=functionalities&uppath='.urlencode($funcpath),
									'');
				
				} 
				
			}
			
			
			
			
			$tree->appendElement($element, $id);
			
			$i++;
		}
		
		return 0 < count($childs);

	}

	buid_nodeLevel($tree, $root, 'R' , '');

	global $babBody;

	$babBody->setTitle(bab_translate('Functionalities'));
	$babBody->babecho($tree->printTemplate());
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
	maclGroups();
	$babDB->db_query("TRUNCATE bab_vac_calendar");
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=addons&idx=list&errormsg=".urlencode($babBody->msgerror));
	}

if (isset($_POST['action'])) {
	switch($_POST['action']) {
		case 'import':
			import();
			break;

		case 'upgrade':
			upgrade($_POST['item']);
			break;
	}
}

switch($idx)
	{
	case "view":
		$babBody->title = bab_translate("Access to Add-on")." ".getAddonName($item);
		aclGroups("addons", "list", BAB_ADDONS_GROUPS_TBL, $item, "acladd");
		$babBody->addItemMenu("list", bab_translate("Add-ons"), $GLOBALS['babUrlScript']."?tg=addons&idx=list");
		$babBody->addItemMenu("view", bab_translate("Access"), $GLOBALS['babUrlScript']."?tg=addons&idx=view&item=".$item);
		break;

	case "upload":
		$babBody->addItemMenu("list", bab_translate("Add-ons"), $GLOBALS['babUrlScript']."?tg=addons&idx=list");
		$babBody->addItemMenu("upload", bab_translate("Upload"), $GLOBALS['babUrlScript']."?tg=addons&idx=upload");
		$babBody->title = bab_translate("Upload");
		upload();
		break;

	case 'requirements':
		$babBody->addItemMenu("list", bab_translate("Add-ons"), $GLOBALS['babUrlScript']."?tg=addons&idx=list");
		$babBody->addItemMenu("requirements", bab_translate("Install"), $GLOBALS['babUrlScript']."?tg=addons&idx=requirements");
		$babBody->title = bab_translate("Install an addon");
		test_requirements();
		break;

	case "history":
		history($_GET['item']);
		break;
		
	case 'functionalities':
		$babBody->addItemMenu("list", bab_translate("Add-ons"), $GLOBALS['babUrlScript']."?tg=addons&idx=list");
		$babBody->addItemMenu("functionalities", bab_translate("Functionalities"), $GLOBALS['babUrlScript']."?tg=addons&idx=functionalities");
		functionalities();
		break;

	case "upgrade":
		upgrade($_GET['item']);

		if (!headers_sent()) {
			Header("Location: ". $GLOBALS['babUrlScript']."?tg=addons&idx=list&errormsg=".urlencode($babBody->msgerror));
			exit;
		}
		break;
		
	case "del":
		del($item);

	case "export":
		if ($idx == 'export') 
			export($item);
		$idx = 'list';

	case "list":
	default:
		if (isset($errormsg)) $babBody->msgerror = urldecode($errormsg);
		addonsList();
		$babBody->title = bab_translate("Add-ons list");
		$babBody->addItemMenu("list", bab_translate("Add-ons"), $GLOBALS['babUrlScript']."?tg=addons&idx=list");
		$babBody->addItemMenu("upload", bab_translate("Upload"), $GLOBALS['babUrlScript']."?tg=addons&idx=upload");
		
		if (haveFunctionalities()) {
			$babBody->addItemMenu("functionalities", bab_translate("Functionalities"), $GLOBALS['babUrlScript']."?tg=addons&idx=functionalities");
		}
		
		break;
	}
$babBody->setCurrentItemMenu($idx);

?>