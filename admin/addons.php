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







class bab_addons_list
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

	function bab_addons_list()
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
		$this->t_access = bab_translate("Access");
		$this->view = bab_translate("Rights");
		$this->versiontxt = bab_translate("Version");
		$this->t_delete = bab_translate("Delete");
		$this->t_historic = bab_translate("Historic");
		$this->t_download = bab_translate("Download");
		$this->confirmdelete = bab_toHtml(bab_translate("Are you sure you want to delete this add-on ?"), BAB_HTML_JS);
		
		bab_addonsInfos::insertMissingAddonsInTable();
		bab_addonsInfos::clear();
		
		$this->res = $this->getRes();
	}
		
	function getRes() {

		$return = array();
		foreach(bab_addonsInfos::getDbAddonsByName() as $name => $addon) {
			if ($this->display($addon)) {
				$return[$name] = $addon;
			}
		}

		bab_sort::ksort($return, bab_sort::CASE_INSENSITIVE);
		return $return;
	}
	
	
	
	function display($addon) {

		if (!$addon) {
			return false;
		}
	
		$type = $addon->getAddonType();
		return 'EXTENSION' === $type;
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
			$this->deleteurl		= bab_toHtml($GLOBALS['babUrlScript']."?tg=addons&idx=del&item=".$addon->getId());

			$addon->updateInstallStatus();
			
			$this->id_addon 		= $addon->getId();
			
			$this->catchecked 		= $addon->isDisabled();
			$this->access_control 	= $addon->hasAccessControl();
			$this->delete 			= $addon->isDeletable();
			$this->addversion 		= bab_toHtml($addon->getDbVersion());
			$this->description 		= bab_toHtml($addon->getDescription(), BAB_HTML_ALL);
			$this->iconpath			= bab_toHtml($addon->getIconPath());
			
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
	

class bab_addons_list_library extends bab_addons_list {

	function bab_addons_list_library() {
		parent::bab_addons_list();
		
		$this->display_in_form = false;
		
	}

	function display($addon) {
		return 'LIBRARY' === $addon->getAddonType();
	}
}



class bab_addons_list_theme extends bab_addons_list {

	function bab_addons_list_theme() {
		parent::bab_addons_list();
		
		$this->display_in_form = false;
		
	}

	function display($addon) {
		return 'THEME' === $addon->getAddonType();
	}
}











function goto_list($addon) {

	
	$type = $addon->getAddonType();
	
	switch($type) {
		case 'EXTENSION':
			header('location:'.$GLOBALS['babUrlScript'].'?tg=addons&idx=list');
			break;
		
		case 'THEME':
			header('location:'.$GLOBALS['babUrlScript'].'?tg=addons&idx=theme');
			break;
		
		case 'LIBRARY':
			header('location:'.$GLOBALS['babUrlScript'].'?tg=addons&idx=library');
			break;
	}

	exit;
}


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
	$temp = new bab_addons_list();
	$babBody->babecho(bab_printTemplate($temp, "addons.html", "addonslist"));
	
	}
	
	
function libraryList()
	{
	global $babBody;
	
	$temp = new bab_addons_list_library();
	$babBody->babecho(bab_printTemplate($temp, "addons.html", "addonslist"));
	
	}


function themeList()
	{
	global $babBody;
	
	$temp = new bab_addons_list_theme();
	$babBody->babecho(bab_printTemplate($temp, "addons.html", "addonslist"));
	
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



/**
 * Database upgrade for one addon
 * @param	int	$id
 */
function upgrade($id) {
	global $babDB;
	
	$row = bab_addonsInfos::getDbRow($id);
	$addon = bab_getAddonInfosInstance($row['title']);
	
	if (!$addon->isValid()) {
		bab_display_addon_requirements();
	}
	
	if (!$addon->upgrade()) {
		global $babBody;
		$babBody->addError(bab_translate('There is an error in addon upgrade'));
		return false;
	}
	
	goto_list($addon);
}





/**
 * Get the list of files to export in a particular folder
 * @param	string	$d		folder
 * @return	array
 */
function bab_addon_export_rd($d) {
	$res = array();
	$d = mb_substr($d,-1) != '/' ? $d.'/' : $d;
	if (is_dir($d)) {

		$subdir = array();
		$handle=opendir($d);

		if (!$handle) {
			return false;
		}

		while ($file = readdir($handle)) {
			if ($file != "." && $file != "..") {
				if (is_dir($d.$file) && 'CVS' != $file && '.CVS' != $file ) {
					$subdir[] = $d.$file;
				} elseif (is_file($d.$file)) {
					$res[] = $d.$file;
				}
			}
		}
		closedir($handle);

		foreach($subdir as $directory) {
			$res = array_merge($res, bab_addon_export_rd($directory));
		}
	}
	return $res;
}










/**
 * Get a zip archive for one addon
 * @param	int	$id
 */
function export($id)
	{
	global $babBody;
	bab_setTimeLimit(0);

	$row = bab_addonsInfos::getDbRow($id);

	if (!callSingleAddonFunction($row['id'], $row['title'], 'onPackageAddon'))
		{
		return;
		}

		
	$addons_files_location = bab_getAddonsFilePath();
	
	$loc_in = $addons_files_location['loc_in'];
	$loc_out = $addons_files_location['loc_out'];
			

	$zip = bab_functionality::get('Archive/Zip');

	$version = str_replace('.','-',$row['version']);
	$tmpfile = $GLOBALS['babUploadPath'].'/tmp/'.$row['title'].'-'.$version.'.zip';

	try {
		$zip->open($tmpfile);
	} catch (Exception $e) {
		$babBody->addError($e->getMessage());
		return;
	}

	$res = array();
	foreach ($loc_in as $k => $path)
		{
		$path = realpath('.').'/'.$path.'/'.$row['title'];
		$res = bab_addon_export_rd($path);

		if (false === $res) {
			die(sprintf(bab_translate('Error reading directory %s'), $path));
			return;
		}

		$len = mb_strlen($path);

		foreach ($res as $file)
			{
			if (is_file($file))
				{
				$rec_into = $loc_out[$k].mb_substr($file, $len);
				$zip->addFile($file, $rec_into);
				}
			}
		}
	$zip->close();

	if (!file_exists($tmpfile)) {
		$babBody->addError(bab_translate('Error in zip creation'));
		return;
	}


	header("Content-Type:application/zip");
	header("Content-Disposition: attachment; filename=".basename($tmpfile));
	echo file_get_contents($tmpfile);

	@unlink($tmpfile);
	exit;
	}
	
	
	
	
	
	
	
	

	
	
	
	
	
	
/**
 * Delete addon
 * @param	int	$id
 */
function bab_AddonDel($id)
	{
	
	$row = bab_addonsInfos::getDbRow($id);
	$addon = bab_getAddonInfosInstance($row['title']);
	
	if (false === $addon->delete($msgerror)) {
		global $babBody;
		$babBody->addError($msgerror);
		
		bab_display_addon_requirements();
		return;
	}

	goto_list($addon);
}



/**
 * Upload form for new package
 */
function upload()
{
	global $babBody;
	class temp
		{
		function temp()
			{
			$this->t_wait = bab_translate("Loading, please wait...");
			$this->t_button = bab_translate("Upload");
			}
		}

	$temp = new temp();
	$babBody->babecho(	bab_printTemplate($temp, "addons.html", "upload"));
}

/**
 * Upload file to tmp folder
 * @return string	temporary file path to addon package
 */
function upload_tmpfile() {
	global $babBody;
	include_once $GLOBALS['babInstallPath'].'utilit/uploadincl.php';

	$upload = bab_fileHandler::upload('uploadf');

	if ($upload->error) {
		$babBody->addError($upload->error);
		return false;
	}

	$tmpfile = $upload->importTemporary();

	if (false === $tmpfile) {
		$babBody->addError(bab_translate('Unexpected error, the archive could not be created in temporary folder'));
		return false;
	}

	return $tmpfile;
}






/**
 * Display requirement for an addon or for a new package to install
 */
function bab_display_addon_requirements()
{
	include_once $GLOBALS['babInstallPath'].'utilit/install.class.php';
	include_once $GLOBALS['babInstallPath'].'utilit/addonsincl.php';
	global $babBody;
	class temp {
	
		var $altbg = false;
	
		function temp()
			{
			global $babBody;
			$this->installed = false;
			
			$ini = new bab_inifile();
			if (isset($_FILES['uploadf'])) {
				// display requirements from temporary package into installation process
				$this->item = '';

				$ul = upload_tmpfile();
				
				$name 			= mb_substr( $ul,(mb_strrpos( $ul,'/')+2+strlen(session_id())));
				$filename 		= mb_substr( $ul,(mb_strrpos( $ul,'/')+1));
				$this->tmpfile 	= bab_toHtml($filename);
				$this->action 	= 'import';

				$install = new bab_InstallSource;
				$install->setArchive($ul);
				$ini = $install->getIni();

				$this->imagepath = false;
				
				$this->t_addon = bab_translate("Archive");
				$this->t_install = bab_translate("Install");
				$babBody->setTitle(bab_translate("Requirements to install the new archive"));


				$description = $ini->getDescription();

			} elseif (isset($_GET['item'])) {

				// display requirements of currently installed addon
				$babBody->setTitle(bab_translate("Addon requirements"));
				$this->t_addon = bab_translate("Addon");

				$this->item = (int) bab_rp('item');

				$row = bab_addonsInfos::getDbRow($this->item);
				$addon = bab_getAddonInfosInstance($row['title']);
				$this->installed = $addon->isInstalled();
				$this->dependences = $addon->getDependences();

				$ini->inifile($addon->getPhpPath()."addonini.php");
				$this->tmpfile = '';
				$this->action = 'upgrade';
				$this->t_install = bab_translate("Upgrade");
				
				$name = $addon->getName();
				$description = $addon->getDescription();

				$this->imagepath = bab_toHtml($addon->getImagePath());
				if ($addon->isDeletable()) {
					$this->deleteurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=addons&idx=del&item=".$addon->getId());
				}
				
				if (is_file($addon->getPhpPath()."history.txt")) {
					$this->historyurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=addons&idx=history&item=".$addon->getId());
				}		

				if ($ini->fileExists()) {
					$this->exporturl = bab_toHtml($GLOBALS['babUrlScript']."?tg=addons&idx=export&item=".$addon->getId());
				}
			}

			$this->name = bab_toHtml($name);
			$this->adescription = bab_toHtml($description);
			$this->version = bab_toHtml($ini->getVersion());
			
			$this->requirementsHtml = $ini->getRequirementsHtml();
			
			$this->t_requirements = bab_translate("Requirements");
			$this->t_recommended = bab_translate("Recommended");
			$this->t_dependencies = bab_translate("Dependencies");
			$this->t_required = bab_translate("Required value");
			$this->t_current = bab_translate("Current value");
			
			$this->t_description = bab_translate("Description");
			$this->t_version = bab_translate("Version");
			$this->t_ok = bab_translate("Ok");
			$this->t_error = bab_translate("Error");
			$this->t_delete = bab_translate("Delete");
			$this->t_history = bab_translate("Historic");
			$this->t_export = bab_translate("Download");
			$this->confirmdelete = bab_toHtml(bab_translate("Are you sure you want to delete this add-on ?"));
			
			$this->call_upgrade = true;
			$this->t_call_upgrade = bab_translate("Launch addon installation program");

			$this->allok = $ini->fileExists() && $ini->isValid();
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
 * Install package of any type
 */
function import()
	{
	include_once $GLOBALS['babInstallPath'].'utilit/install.class.php';
	bab_setTimeLimit(1200);
	
	global $babBody, $babDB;

	if( !empty($_POST['tmpfile'])) {
		$ul = $GLOBALS['babUploadPath'].'/tmp/'.$_POST['tmpfile'];
		
		if (!is_file($ul)) {
			$babBody->addError(bab_translate('The file is missing'));
			return false;
		}
		
		$install = new bab_InstallSource;
		$install->setArchive($ul);
		$ini = $install->getIni();

		if (!$ini) {
			$babBody->addError(bab_translate("This file is not a well formated Ovidentia package"));
			return false;
		}

		if (false === $ini->isValid()) {
			$babBody->addError(bab_translate('The package is not valid'));
			unlink($ul);
			return false;
		}
		
		$install->install($ini);
		unlink($ul);
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

			$arr = bab_addonsInfos::getDbRow($item);
			$addon = bab_getAddonInfosInstance($arr['title']);
			
			if ($addon)
				{
				$encoding = 'ISO-8859-15';
				$ini = $addon->getIni();
				if (isset($ini->inifile['encoding'])) {
					$encoding = $ini->inifile['encoding'];
				}

				$this->history = bab_getStringAccordingToDataBase(implode('',file($addon->getPhpPath().'history.txt')), $encoding);
				$this->history = bab_toHtml($this->history, BAB_HTML_ALL);
				}
			else
				$this->history = '';
			}
		}

	$temp = new temp($item);
	$babBody->babpopup(bab_printTemplate($temp, "addons.html", "history"));
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
				$iPos = mb_strpos($labelpath,'/');
				if (false !== $iPos) {
					$labelpath = mb_substr($labelpath,$iPos+1);
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
			
			
			
			
			if (0 < mb_substr_count($funcpath, '/')) {
			
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

	$babBody->setTitle(bab_translate('Libraries administration'));
	$babBody->babecho($tree->printTemplate());
}
	




function viewVersion()
	{
	require_once $GLOBALS['babInstallPath'] . 'utilit/toolbar.class.php';
	global $babBody;

	class ViewVersionTpl
		{
		var $urlphpinfo;
		var $phpinfo;
		var $srcversiontxt;
		var $baseversiontxt;
		var $srcversion;
		var $baseversion;
		var $phpversiontxt;
		var $phpversion;

		var $altbg = true;

		function __construct()
			{
			include_once $GLOBALS['babInstallPath'].'utilit/inifileincl.php';
			$this->srcversiontxt = bab_translate("Ovidentia version");
			$this->phpversiontxt = bab_translate("Php version");
			$this->phpversion = phpversion();
			$this->baseversiontxt = bab_translate("Database server version");
			$db = $GLOBALS['babDB'];
			$arr = $db->db_fetch_array($db->db_query("show variables like 'version'"));
			$this->baseversion = $arr['Value'];
			$this->urlphpinfo = $GLOBALS['babUrlScript']."?tg=sites&idx=phpinfo";
			$this->phpinfo = "phpinfo";
			$this->currentyear = date("Y");
			

			$ini = new bab_inifile();
			$ini->inifile($GLOBALS['babInstallPath'].'version.inc');

			$this->srcversion = $ini->getVersion();
			$this->dbversion = bab_getDbVersion();

			$this->requirementsHtml = $ini->getRequirementsHtml();

			$this->t_requirements = bab_translate("Requirements");
			$this->t_recommended = bab_translate("Recommended");
			$this->t_install = bab_translate("Install");
			$this->t_required = bab_translate("Required value");
			$this->t_current = bab_translate("Current value");
			$this->t_addon = bab_translate("Addon");
			$this->t_description = bab_translate("Description");
			$this->t_version = bab_translate("Version");
			$this->t_ok = bab_translate("Ok");
			$this->t_error = bab_translate("Error");
			$this->t_name = bab_translate("Name");
			$this->t_version_directories = bab_translate("List of version directories");
			$this->t_current_core = bab_translate("Current core");
			$this->t_not_used = bab_translate("Not used");


			$basedir = realpath('.').'/';
			$dh = opendir($basedir);
			
			$this->dirs = array();
			
			while (($file = readdir($dh)) !== false) {
				if (is_dir($basedir.$file) && file_exists($basedir.$file.'/version.inc')) {
					$this->dirs[] = $file;
				} 
			}
			
			bab_sort::natcasesort($this->dirs);

			}

		function set_message()
		{
			if( $this->srcversion != $this->dbversion ) {
				$GLOBALS['babBody']->msgerror = bab_translate("The database is not up-to-date");

				$this->message = sprintf(bab_translate("The database has not been updated since version %s"),$this->dbversion);
				$this->upgrade = bab_translate("Update database");
			}
		}



		function getnextdir() {
			if (list(,$file) = each($this->dirs)) {
				
				$this->altbg = !$this->altbg;
				$this->name = $file;
				$this->current_core = $file.'/' === $GLOBALS['babInstallPath'];
				
				return true;
			}
			return false;
		}

	}

	$temp = new ViewVersionTpl();
	$temp->message = bab_toHtml(bab_rp('message'), BAB_HTML_ALL);
	$temp->set_message();

	$oToolbar = new BAB_Toolbar();

	$sImgPath = $GLOBALS['babInstallPath'] . 'skins/ovidentia/images/Puces/';
	
	$oToolbar->addToolbarItem(
		new BAB_ToolbarItem(bab_translate("Ovidentia upgrade"), $GLOBALS['babUrlScript'].'?tg=addons&idx=zipupgrade', 
			$sImgPath . 'package_settings.png', '', '', '')
	);

	$babBody->addStyleSheet('toolbar.css');
	$babBody->babEcho($oToolbar->printTemplate());
	$babBody->babEcho(bab_printTemplate($temp, 'sites.html', 'versions'));
}






function bab_addonUploadToolbar($message, $func = null) {
	require_once $GLOBALS['babInstallPath'] . 'utilit/toolbar.class.php';
	global $babBody;

	$oToolbar = new BAB_Toolbar();

	$sImgPath = $GLOBALS['babInstallPath'] . 'skins/ovidentia/images/Puces/';
	
	$oToolbar->addToolbarItem(
		new BAB_ToolbarItem($message, $GLOBALS['babUrlScript'].'?tg=addons&idx=upload', 
			$sImgPath . 'package_settings.png', '', '', '')
	);

	if (null !== $func) {
		$oToolbar->addToolbarItem(
			new BAB_ToolbarItem(bab_translate("Libraries administration"), $GLOBALS['babUrlScript'].'?tg=addons&idx=functionalities', 
				$sImgPath . 'folder.gif', '', '', '')
		);
	}

	$babBody->addStyleSheet('toolbar.css');
	$babBody->babEcho($oToolbar->printTemplate());
}



	
	
	
function display_addons_menu() {
	global $babBody;
	
	$babBody->addItemMenu("version", bab_translate('Version'), $GLOBALS['babUrlScript']."?tg=addons&idx=version");
	$babBody->addItemMenu("list", bab_translate('Add-ons'), $GLOBALS['babUrlScript']."?tg=addons&idx=list");
	$babBody->addItemMenu("theme", bab_translate('Skins'), $GLOBALS['babUrlScript']."?tg=addons&idx=theme");
	$babBody->addItemMenu("library", bab_translate('Shared Libraries'), $GLOBALS['babUrlScript']."?tg=addons&idx=library");
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
	case 'version':
		display_addons_menu();
		$babBody->title = bab_translate("Ovidentia info");
		viewVersion();
		break;

	case 'zipupgrade':
		display_addons_menu();
		$babBody->setTitle(bab_translate("Upgrade"));
		upload();
		$idx = 'version';
		break;

	case 'zipupgrade_message':
		zipupgrade_message();
		break;


	case "view":
		$babBody->title = bab_translate("Access to Add-on")." ".getAddonName($item);
		aclGroups("addons", "list", BAB_ADDONS_GROUPS_TBL, $item, "acladd");
		display_addons_menu();
		$babBody->addItemMenu("view", bab_translate("Access"), $GLOBALS['babUrlScript']."?tg=addons&idx=view&item=".$item);
		break;

	case "upload":
		display_addons_menu();
		$babBody->addItemMenu("upload", bab_translate("Upload"), $GLOBALS['babUrlScript']."?tg=addons&idx=upload");
		$babBody->title = bab_translate("Upload");
		upload();
		break;

	case 'requirements':
		display_addons_menu();
		$babBody->addItemMenu("requirements", bab_translate("Requirements"), $GLOBALS['babUrlScript']."?tg=addons&idx=requirements");
		bab_display_addon_requirements();
		break;

	case "history":
		history($_GET['item']);
		break;
		
	case 'functionalities':
		display_addons_menu();
		bab_addonUploadToolbar(bab_translate('Upload a new library'));
		functionalities();
		$idx = 'library';
		break;

	case "upgrade":
		upgrade($_GET['item']);
		$babBody->addItemMenu("upgrade", bab_translate("Upgrade"), $GLOBALS['babUrlScript']."?tg=addons&idx=upgrade");
		$babBody->setTitle(bab_translate("Add-ons installation"));
		break;
		
	case "del":
		$babBody->setTitle(bab_translate('Delete addon'));
		display_addons_menu();
		$babBody->addItemMenu("del", bab_translate("Delete"), $GLOBALS['babUrlScript']."?tg=addons&idx=del");
		bab_AddonDel($item);
		break;

	case "export":
		export($item);
		break;
		
	case 'library':
		$babBody->title = bab_translate('Shared Libraries');
		display_addons_menu();
		bab_addonUploadToolbar(bab_translate('Upload a new library'), true);
		libraryList();
		
		
		break;

		
	case 'theme':
		$babBody->title = bab_translate('Skins');
		display_addons_menu();
		bab_addonUploadToolbar(bab_translate('Upload a new skin'));
	
		themeList();

		break;


	case "list":
	default:
		$babBody->title = bab_translate("Add-ons list");
		
		display_addons_menu();
		bab_addonUploadToolbar(bab_translate('Upload a new add-on'));

		addonsList();
		break;
	}
$babBody->setCurrentItemMenu($idx);
