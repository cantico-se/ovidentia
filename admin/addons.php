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
require_once dirname(__FILE__).'/../utilit/registerglobals.php';
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
		$this->t_configure = bab_translate("Configuration");
		$this->t_install = bab_translate("Install");
		$this->chosetheme = bab_translate("Use this theme");
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

			/*@var $addon bab_addonInfos */

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

			$confurl = $addon->getConfigurationUrl();
			if (null !== $confurl && $addon->isAccessValid()) {
				$this->configurationurl	= bab_toHtml($confurl);
			} else {
				$this->configurationurl	= false;
			}

			if ($addon->isUpgradable()) {
				$this->upgradeurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=addons&idx=upgrade&item=".$addon->getId());
			} else {
				$this->upgradeurl = false;
			}



			if ('THEME' === $addon->getAddonType() && $addon->isAccessValid() && $GLOBALS['babSkin'] !== $addon->getName())
			{
				$this->chosethemeurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=addons&idx=chosetheme&item=".$addon->getId());
			} else {
				$this->chosethemeurl = false;
			}

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

	$addonpath = $GLOBALS['babInstallPath'].'addons/'.$name;
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

	bab_siteMap::clearAll();

	Header("Location: ". $GLOBALS['babUrlScript']."?tg=addons&idx=list");
	exit;
}



/**
 * Display upgrade page with iframe
 * @param	int	$id
 */
function addon_display_upgrade($id) {
	require_once $GLOBALS['babInstallPath'].'utilit/install.class.php';
	require_once $GLOBALS['babInstallPath'].'utilit/urlincl.php';

	$row = bab_addonsInfos::getDbRow($id);
	$addon = bab_getAddonInfosInstance($row['title']);

	if (!$addon->isValid()) {
		bab_display_addon_requirements();
		return;
	}
	

	$t_upgrade = sprintf(bab_translate('Upgrade of addon %s from version %s to version %s'),
		$addon->getName(),
		$addon->getDbVersion(),
		$addon->getIniVersion()
	);


	$t_continue = bab_translate('Back to list');

	$url = bab_url::request();
	$url = bab_url::mod($url, 'tg', 'addons');
	$url = bab_url::mod($url, 'idx', 'call_upgrade');

	$frameurl = bab_url::mod($url, 'item', $id);


	$type = $addon->getAddonType();

	switch($type) {
		case 'EXTENSION':
			$url = bab_url::mod($url, 'idx', 'list');
			break;

		case 'THEME':
			$url = bab_url::mod($url, 'idx', 'theme');
			break;

		case 'LIBRARY':
			$url = bab_url::mod($url, 'idx', 'library');
			break;
	}


	$listurl = bab_url::mod($url, 'item', $id);

	bab_installWindow::getPage($t_upgrade, $frameurl, $t_continue, $listurl);
}






/**
 * Upgrade frame
 * Database upgrade for one addon
 */
function addon_call_upgrade($id) {

	require_once $GLOBALS['babInstallPath'].'utilit/install.class.php';

	$row = bab_addonsInfos::getDbRow($id);
	$addon = bab_getAddonInfosInstance($row['title']);

	if (!$addon->isValid()) {
		die();
	}

	$frame = new bab_installWindow;
	$frame->setStartMessage(bab_translate('Install start'));
	$frame->setStopMessage(
		bab_translate('The addon upgrade was successful'),
		bab_translate('An error occurred during the addon upgrade')
	);

	$frame->startInstall(array($addon, 'upgrade'));
	die();
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
		$handle = opendir($d);

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








class bab_addonPackage
{
	/**
	 *
	 * @var bab_addonInfos
	 */
	protected $addon;

	/**
	 *
	 * @var bool
	 */
	private $multiple = false;

	private $tmpfile;
	private $zip;

	public function __construct($multiple, bab_addonInfos $addon)
	{
		$this->multiple = $multiple;

		if (!file_exists($GLOBALS['babUploadPath']))
		{
			throw new ErrorException('The upload directory is not accessible : '.$GLOBALS['babUploadPath']);
		}

		if (!file_exists($GLOBALS['babUploadPath'].'/tmp'))
		{
			throw new ErrorException('The temporary folder is not accessible');
		}


		bab_setTimeLimit(0);

		$this->zip = bab_functionality::get('Archive/Zip');

		$version = str_replace('.','-',$addon->getIniVersion());
		$ext = $multiple ? '.pkg.zip' : '.zip';
		$this->tmpfile = $GLOBALS['babUploadPath'].'/tmp/'.$addon->getName().'-'.$version.$ext;

		try {
			$this->zip->open($this->tmpfile);
		} catch (Exception $e) {
			$babBody->addError($e->getMessage());
		}

		if ($this->multiple) {
			$this->addAllAddons($addon);
		} else {
			$this->addAddon($addon);
		}
	}


	private function addAllAddons(bab_addonInfos $addon)
	{
		$general = array();
		$general['name'] = $addon->getName();

		$dependencies = $addon->getPackageDependencies();
		foreach($dependencies as $addonname) {
			$dependence = bab_getAddonInfosInstance($addonname);
			if (false !== $dependence)
			{
				$this->addAddon($dependence);
				$ini = $dependence->getIni();
			}
		}


		$general['package_collection'] = implode(', ', $dependencies);
		$general['encoding'] = bab_Charset::getIso();
		$general['mysql_character_set_database'] = 'latin1,utf8';


		$content = '; <?php/*'."\n";
		$content .= "[general]\n";

		foreach($general as $key => $value) {
			$content .= "$key=\"$value\"\n";
		}

		$content .= ";*/ ?>";

		$tmpini = $GLOBALS['babUploadPath'].'/tmp/addons.ini';

		file_put_contents($tmpini, $content);

		$this->zip->addFile($tmpini, 'install/addons/addons.ini');
	}




	private function addAddon(bab_addonInfos $addon)
	{
		$addons_files_location = bab_getAddonsFilePath();
		$loc_in = $addons_files_location['loc_in'];
		$loc_out = $addons_files_location['loc_out'];

		if (!callSingleAddonFunction($addon->getId(), $addon->getName(), 'onPackageAddon'))
		{
			return;
		}

		$res = array();
		foreach ($loc_in as $k => $path)
		{
			$path = realpath('.').'/'.$path.'/'.$addon->getName();
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
					if ($this->multiple) {
						$rec_into = 'install/addons/'.$addon->getName().'/'.$rec_into;
					}
					$this->zip->addFile($file, $rec_into);
				}
			}
		}

	}


	public function download()
	{
		global $babBody;
		$this->zip->close();

		if (!file_exists($this->tmpfile)) {
			$babBody->addError(bab_translate('Error in zip creation'));
			return;
		}


		header("Content-Type:application/zip");
		header("Content-Disposition: attachment; filename=".basename($this->tmpfile));
		echo file_get_contents($this->tmpfile);

		@unlink($this->tmpfile);
		exit;
	}
}





/**
 * Get a zip archive for one addon
 * @param	int	$id
 */
function export($id)
	{

		$row = bab_addonsInfos::getDbRow($id);
		$addon = bab_getAddonInfosInstance($row['title']);

		$package = new bab_addonPackage(false, $addon);

		$package->download();
	}



/**
 * Get a zip archive for one addon
 * @param	int	$id
 */
function exportall($id)
	{
		$row = bab_addonsInfos::getDbRow($id);
		$addon = bab_getAddonInfosInstance($row['title']);

		$package = new bab_addonPackage(true, $addon);

		$package->download();
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






function bab_search_addon_upgrade()
{
	require_once $GLOBALS['babInstallPath'].'utilit/install.class.php';
	
	$W = bab_Widgets();
	$page = $W->BabPage();
	$form = $W->Form(null, $W->VBoxLayout()->setVerticalSpacing(1, 'em'));
	
	$form->setName('dlfile');
	$form->setHiddenValue('tg', bab_rp('tg'));
	$form->setHiddenValue('idx', 'import');
	$form->addClass('widget-bordered');
	$form->addClass('BabLoginMenuBackground');
	$form->addClass('widget-centered');
	$form->setCanvasOptions($form->Options()->width(50, 'em'));
	
	$form->addItem($W->Title(bab_translate('Update to version'))->colon());
	
	$repository = bab_getInstance('bab_InstallRepository');
	/*@var $repository bab_InstallRepository */
	
	$id_addon = bab_rp('item');
	$row = bab_addonsInfos::getDbRow($id_addon);
	
	$page->setTitle(sprintf(bab_translate('Available upgrade(s) for addon %s'), $row['title']));
	$versions = $repository->getAvailableVersions($row['title']);
	
	
	if (null === $versions)
	{
		$page->addError(sprintf(bab_translate('There are no upgrade available for the addon %s'), $row['title']));
		$page->displayHtml();
		return;
	}
	
	$versions = array_reverse($versions);
	
	$form->setHiddenValue('dlfile[name]', $row['title']);
	$addon = bab_getAddonInfosInstance($row['title']);
	
	$radioSet = $W->RadioSet()->setName('version');
	$form->addItem($radioSet);
	
	$c = 0;
	foreach($versions as $version)
	{
		if (version_compare($version, $addon->getDbVersion(), '>'))
		{
			$radioSet->addOption($version, sprintf(bab_translate('Version %s'), $version));
			$c++;
		}
	}
	
	$radioSet->setValue($versions[0]);
	
	if ($c > 0)
	{
		$page->addItem($form);
	} else {
		$page->addError(sprintf(bab_translate('There are no upgrade available for the addon %s'), $row['title']));
	}
	
	
	$form->addItem($W->SubmitButton()->setLabel(bab_translate('Next')));
	
	
	$page->displayHtml();
}






/**
 * Display requirement for an addon or for a new package to install
 */
function bab_display_addon_requirements()
{
	include_once $GLOBALS['babInstallPath'].'utilit/install.class.php';
	include_once $GLOBALS['babInstallPath'].'utilit/addonsincl.php';
	global $babBody;


	/**
	 * template
	 */
	class temp {


		/**
		 * @var bool
		 * If null, the interface is not displayed
		 * if true, dependencies are fullfiled
		 * if false, conflict
		 */
		public $allok = null;


		public $altbg = false;

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

				try {
					$ini = $install->getIni();
				} catch(Exception $e) {
					$babBody->addError($e->getMessage());
					return;
				}

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
				try {
					$description = $addon->getDescription();
				} catch(Exception $e) {
					$description = '';
				}

				$this->imagepath = bab_toHtml($addon->getImagePath());
				if ($addon->isDeletable()) {
					$this->deleteurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=addons&idx=del&item=".$addon->getId());
				}

				if (is_file($addon->getPhpPath()."history.txt")) {
					$this->historyurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=addons&idx=history&item=".$addon->getId());
				}

				if ($ini->fileExists()) {
					$this->exporturl = bab_toHtml($GLOBALS['babUrlScript']."?tg=addons&idx=export&item=".$addon->getId());
					if ($addon->getDependencies())
					{
						$this->exportpkgurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=addons&idx=exportall&item=".$addon->getId());
					}
					
					$repository = bab_getInstance('bab_InstallRepository');
					if ($repository->exists())
					{
						$this->searchupgradeurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=addons&idx=searchupgrade&item=".$addon->getId());
					}
				}
				
				
			}

			$this->name = bab_toHtml($name);
			$this->adescription = bab_toHtml($description);
			$this->version = bab_toHtml($ini->getVersion());

			$this->requirementsHtml = $ini->getRequirementsHtml(true);

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
			$this->t_exportpkg = bab_translate("Download with dependencies");
			$this->confirmdelete = bab_toHtml(bab_translate("Are you sure you want to delete this add-on ?"));
			$this->t_searchupgrade = bab_translate("Search upgrades");

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
	if (null !== $temp->allok) {
		$babBody->babecho(bab_printTemplate($temp, "addons.html", "requirements"));
	}
}

/**
 * Unzip temporary file
 * Install package of any type
 */
class bab_import_package {
	
	/**
	 * Get install source from temporay uploaded file or null if no uploaded file
	 * 
	 * @return bab_InstallSource
	 */
	private static function getInstallSource() {

		include_once $GLOBALS['babInstallPath'].'utilit/install.class.php';

		if(bab_rp('tmpfile')) {
			$ul = $GLOBALS['babUploadPath'].'/tmp/'.bab_rp('tmpfile');

			if (is_file($ul)) {
				$install = new bab_InstallSource;
				$install->setArchive($ul);
				return $install;
			}
		}

		return null;
	}


	private static function getIni(bab_InstallSource $install) {
		$ini = $install->getIni();

		if (!$ini) {
			throw new Exception(bab_translate("This file is not a well formated Ovidentia package"));
		}

		return $ini;
	}


	/**
	 * Process iframe content on install
	 * unzip and install package
	 * 
	 * @return bool
	 */
	public static function install() {

		bab_setTimeLimit(1200);
		
		// first, process downloads
		
		$dlfiles = self::installDlFiles();
		
		// second, install temporary uploaded files
		
		$tmpfile = self::installTmpFile();
		
		return $dlfiles && $tmpfile;
	}
	
	
	/**
	 * Download and install a list of files
	 * @return bool
	 */
	private static function installDlFiles()
	{
		$dlfile = bab_rp('dlfile', null);
		
		if (!isset($dlfile)||empty($dlfile))
		{
			return false;
		}
		
		$repository = bab_getInstance('bab_InstallRepository');
		/*@var $repository bab_InstallRepository */
		
		foreach($dlfile as $filename)
		{
			$url = $repository->getLastest($filename)->getUrl();
			if (!$rfp = fopen($url, 'r')) {
				bab_installWindow::message(sprintf(bab_translate("Failed to open URL (%s)"), $url));
				continue;
			}
			
			$tmpfile = $GLOBALS['babUploadPath'].'/tmp/'.$filename;
			if (!$wfp = fopen($tmpfile, 'w')) {
				bab_installWindow::message(sprintf(bab_translate("Failed to write temporary file (%s)"), $tmpfile));
				continue;
			}
			
			self::downloadProgress($rfp, $wfp, $filename);
			
			$install = new bab_InstallSource;
			$install->setArchive($tmpfile);
			
			try {
				$ini = self::getIni($install);
			} catch(Exception $e) {
				bab_installWindow::message($e->getMessage());
				return false;
			}
			
			if ($install->install($ini)) {
				if (!unlink($install->getArchive())) {
					bab_installWindow::message(sprintf(bab_translate('Failed to delete the temporary package %s'), $install->getArchive()));
				}
			}
		}
		
		return true;
	}
	
	/**
	 *
	 */
	private static function downloadProgress($rfp, $wfp, $filename)
	{
		$progress = new bab_installProgressBar;
		$progress->setTitle(sprintf(bab_translate('Download %s'), $filename));
		
		
		$packetsize = 2048;
		$readlength = 0;
		$totallength = self::getLength($rfp);
		
		while (!feof($rfp)) {
			$data = fread($rfp, $packetsize);
			fwrite($wfp, $data);
			$readlength += strlen($data);
			
			$p = (($readlength * 100) / $totallength);
			
			$progress->setProgression($p);
		}
		
		$progress->setProgression(100);
	}
	
	
	/**
	 * Length of file from url
	 * @param	ressource $fp
	 * @return number
	 */
	private static function getLength($fp)
	{
		$length = 1;
		$meta = stream_get_meta_data($fp);
		foreach($meta['wrapper_data'] as $header)
		{
			$h = explode(':', $header);
			if ($h[0] === 'Content-Length')
			{
				$length = (int) trim($h[1]);
			}
		}
		
		return $length;
	}
	
	
	
	/**
	 * Install tmp file if available
	 * @return bool
	 */
	private static function installTmpFile()
	{
		$install = self::getInstallSource();
		if (isset($install))
		{
			try {
				$ini = self::getIni($install);
			} catch(Exception $e) {
				bab_installWindow::message($e->getMessage());
				return false;
			}
		
			if ($install->install($ini)) {
				if (unlink($install->getArchive())) {
					return true;
				} else {
					bab_installWindow::message(sprintf(bab_translate('Failed to delete the temporary package %s'), $install->getArchive()));
				}
			}
		}
		
		return false;
	}
	
	

	/**
	 * Display page for installation
	 *
	 */
	public static function page() {

		try {
			$install = self::getInstallSource();
			if (isset($install))
			{
				$ini = self::getIni($install);
			}

		} catch(Exception $e) {
			global $babBody;
			$babBody->addError($e->getMessage());
			return false;
		}


		require_once $GLOBALS['babInstallPath'].'utilit/urlincl.php';

		$t_upgrade = bab_translate('Installation of the package');


		$url = bab_url::request();
		$url = bab_url::mod($url, 'tg', 'addons');
		$frameurl = bab_url::mod($url, 'idx', 'import_frame');


		$frameurl = bab_url::mod($frameurl, 'tmpfile', bab_rp('tmpfile')); // temporary file name from upload
		$frameurl = bab_url::mod($frameurl, 'dlfile', bab_rp('dlfile')); // file name to download, must be an array

		if (isset($ini) && ($ini instanceOf bab_CoreIniFile)) {
			$t_continue = bab_translate('Home');
			$continueurl = bab_url::request();
		} else {
			$t_continue = bab_translate('Back to list');
			$continueurl = bab_url::mod($url, 'idx', 'list');
		}

		bab_installWindow::getPage($t_upgrade, $frameurl, $t_continue, $continueurl);
	}

	public static function frame() {
		require_once $GLOBALS['babInstallPath'].'utilit/install.class.php';

		$frame = new bab_installWindow;
		$frame->startInstall(array('bab_import_package', 'install'));
		die();
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

		header('location:'.bab_url::request('tg', 'idx', 'failsafe'));
		exit;
	}


	if ($remove = bab_gp('remove', false))
	{
		$func->unregister($remove);

		header('location:'.bab_url::request('tg', 'idx', 'failsafe'));
		exit;
	}

	


	$tree = new bab_TreeView('bab_functionalities');

	$root = & $tree->createElement( 'R', 'directory', bab_translate('All functionalities'), '', '');
	$root->setIcon($GLOBALS['babSkinPath'] . 'images/nodetypes/category.png');
	$tree->appendElement($root, NULL);


	function buid_nodeLevel(&$tree, $node, $id, $path) {
		$func = new bab_functionalities();
		$childs = $func->getChildren($path);
		
		$failsafe = (bool) bab_gp('failsafe', true);

		$i = 1;
		foreach ($childs as $dir) {

			$funcpath = trim($path.'/'.$dir, '/');
			
			$obj = false;
			if (!$failsafe)
			{
				$obj = @bab_functionality::get($funcpath);
			}

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

				$element = & $tree->createElement( $id.'.'.$i, 'directory', $description, '', '');
			} else {
				if ($failsafe)
				{
					$description = $dir;
				} else {
					$description = $dir . ' : '.bab_translate('Missing target');
				}
				
				$element = & $tree->createElement( $id.'.'.$i, 'directory', $description, '', '');

				$url = bab_url::get_request('tg', 'idx', 'failsafe');
				$url->remove = $funcpath;

				$element->addAction('delete',
					bab_translate('Remove link'),
					$GLOBALS['babSkinPath'] . 'images/Puces/delete.png',
					$url->toString(),
					'');
			}











			if (false === buid_nodeLevel($tree, $element, $id.'.'.$i, $funcpath)) {
				$element->setIcon($GLOBALS['babSkinPath'] . 'images/nodetypes/topic.png');

			} else {
				$element->setIcon($GLOBALS['babSkinPath'] . 'images/nodetypes/folder.png');
			}




			if (!$failsafe && 0 < mb_substr_count($funcpath, '/')) {

				$parent_path = $func->getParentPath($funcpath);
				$parent_obj = bab_functionality::get($parent_path);

				// quand $parent_obj est false c'est que l'enfant selectionne par defaut n'existe plus,
				// ici on permet de definir un enfant a partir de ceux disponibles

				if ((false !== $obj && false !== $parent_obj && $parent_obj->getPath() !== $obj->getPath()) || (false === $parent_obj)) {
					
					$url = bab_url::get_request('tg', 'idx', 'failsafe');
					$url->uppath = $funcpath;

					$element->addAction('moveup',
									bab_translate('Move Up'),
									$GLOBALS['babSkinPath'] . 'images/Puces/go-up.png',
									$url->toString(),
									'');

				}

			}
			
			
			if ($failsafe && 0 < mb_substr_count($funcpath, '/')) {
				
				$url = bab_url::get_request('tg', 'idx', 'failsafe');
				$url->uppath = $funcpath;
				
				$element->addAction('moveup',
						bab_translate('Move Up'),
						$GLOBALS['babSkinPath'] . 'images/Puces/go-up.png',
						$url->toString(),
						'');
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
			$this->basedirtxt = bab_translate("Web server base directory");
			$db = $GLOBALS['babDB'];
			$arr = $db->db_fetch_array($db->db_query("show variables like 'version'"));
			$this->baseversion = $arr['Value'];
			$this->urlphpinfo = $GLOBALS['babUrlScript']."?tg=sites&idx=phpinfo";
			$this->phpinfo = "phpinfo";
			$this->currentyear = date("Y");
			$this->basedir = realpath('');


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
			$this->t_modified = bab_translate('Modified files');


			$basedir = realpath('.').'/';
			$dh = opendir($basedir);

			$this->dirs = array();

			if ($dh)
			{
				while (($file = readdir($dh)) !== false) {
					if ($file !== '.' && $file !== '..'
					 && is_dir($basedir.$file) && file_exists($basedir.$file.'/version.inc')) {
						$this->dirs[] = $file;
					}
				}
			}

			bab_sort::natcasesort($this->dirs);

			$this->md5file = file_exists(realpath(dirname(__FILE__).'/../md5_file'));
			$this->modifiedurl = $GLOBALS['babUrlScript']."?tg=addons&idx=modified";
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
		new BAB_ToolbarItem( bab_translate('Ovidentia upgrade'), $GLOBALS['babUrlScript'].'?tg=addons&idx=zipupgrade',
			$sImgPath . 'package_settings.png', '', '', '')
	);

	$babBody->addStyleSheet('toolbar.css');
	$babBody->babEcho($oToolbar->printTemplate());
	$babBody->babEcho(bab_printTemplate($temp, 'sites.html', 'versions'));
}


/**
 * get list of differences
 * @param string $path
 *
 * @return string
 */
function bab_getMd5($file)
{
	if (!file_exists($file))
	{
		return array();
	}



	$arr = file($file);
	$result = array();

	$root = realpath(dirname(__FILE__).'/..');

	foreach ($arr as $line) {

		$md5 = substr($line, 0, 32);
		$path = trim(substr($line, 33));

		$test = trim($root.$path);

		if (file_exists($test))
		{

			if ($md5 !== md5_file($test))
			{
				$result[] = $path;
			}
		}
	}

	return $result;
}

function viewModified()
{
	global $babBody;

	$infos = '';
	$list = bab_getMd5(realpath(dirname(__FILE__).'/../md5_file'));
	foreach($list as $file)
	{
		$infos .= $file."\n";
	}

	$babBody->babPopup(bab_toHtml($infos, BAB_HTML_ALL));
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
			new BAB_ToolbarItem(bab_translate("Libraries administration"), $GLOBALS['babUrlScript'].'?tg=addons&idx=functionalities&failsafe=0',
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


function chosetheme() {

	require_once dirname(__FILE__).'/../utilit/urlincl.php';
	require_once dirname(__FILE__).'/../utilit/skinincl.php';
	global $babDB, $babBody;

	$row = bab_addonsInfos::getDbRow(bab_rp('item'));

	$skin = new bab_skin($row['title']);
	if (!$skin->isAccessValid())
	{
		$babBody->addError(bab_translate('This theme is not accessible or the prerequisites are not fulfilled'));
		return;
	}

	$arr = $skin->getStyles();

	$babDB->db_query('UPDATE bab_sites SET skin='.$babDB->quote($skin->getName()).', style='.$babDB->quote(reset($arr)).'  WHERE name='.$babDB->quote($GLOBALS['babSiteName']));


	$url = bab_url::get_request_gp();
	$url->idx = 'theme';
	$url->location();
}



/* main */

bab_requireCredential();


if( !bab_isUserAdministrator() )
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
	}

if (isset($_POST['action'])) {
	switch($_POST['action']) {

		case 'upgrade':
			addon_display_upgrade($_POST['item']);
			break;
	}
}

switch($idx)
	{
	case 'version':
		display_addons_menu();
		$babBody->title = bab_translate("Ovidentia informations");
		viewVersion();
		break;

	case 'modified':
		$babBody->setTitle(bab_translate("List of modified files"));
		viewModified();
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
		
	case 'searchupgrade':
		display_addons_menu();
		$babBody->addItemMenu("searchupgrade", bab_translate("Search upgrade"), $GLOBALS['babUrlScript']."?tg=addons&idx=searchupgrade");
		bab_search_addon_upgrade();
		break;

	case 'import':
		display_addons_menu();
		$babBody->addItemMenu("import", bab_translate("Install"), $GLOBALS['babUrlScript']."?tg=addons&idx=import");
		bab_import_package::page();
		break;

	case 'import_frame':
		bab_import_package::frame();
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
		display_addons_menu();
		addon_display_upgrade(bab_rp('item', null));
		$babBody->addItemMenu("upgrade", bab_translate("Upgrade"), $GLOBALS['babUrlScript']."?tg=addons&idx=upgrade");
		$babBody->setTitle(bab_translate("Add-ons installation"));
		break;

	case 'call_upgrade':
		addon_call_upgrade($_GET['item']);
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

	case "exportall":
		exportall($item);
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

	case 'chosetheme':
		chosetheme();
		break;


	case "list":
	default:
		$babBody->setTitle(bab_translate("Add-ons list"));

		display_addons_menu();
		bab_addonUploadToolbar(bab_translate('Upload a new add-on'));

		addonsList();
		break;
	}
$babBody->setCurrentItemMenu($idx);
bab_siteMap::setPosition('bab', 'AdminInstall');
