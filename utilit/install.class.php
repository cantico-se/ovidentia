<?php
//-------------------------------------------------------------------------
// OVIDENTIA http://www.ovidentia.org
// Ovidentia is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2, or (at your option)
// any later version.
//
// This program is distributed in the hope that it will be useful, but
// WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
// See the GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307,
// USA.
//-------------------------------------------------------------------------
/**
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 * @copyright Copyright (c) 2008 by CANTICO ({@link http://www.cantico.fr})
 */
include_once 'base.php';





/**
 * Install files in Ovidentia addons or distribution upgrade by zip archive or folder
 *
 */
class bab_InstallSource {

	private $archive = null;
	private $folderpath = null;


	/**
	 * Set a zip archive file path
	 * @param	string	$archive
	 * @return	bab_ZipInstall
	 */
	public function setArchive($archive) {
		$this->archive = $archive;
		return $this;
	}

	/**
	 * Set the install source from an allready unziped folder
	 * @param	string	$folderpath
	 */
	public function setFolder($folderpath) {
		$this->archive = null;
		$this->folderpath = $folderpath;
	}

	/**
	 * Get folder path of install source
	 * if the source is a zip archive, this method will return a folder path with the temporary extracted files of the archive
	 * @return string
	 */
	public function getFolder() {
		if (null === $this->folderpath) {
			$this->folderpath = $this->temporaryExtractArchive();
		}

		return $this->folderpath;
	}

	/**
	 * Remove folder install source if exists
	 * @return bool
	 */
	private function deleteFolder() {

		if (null === $this->folderpath) {
			return true;
		}


		global $babBody;
		include_once dirname(__FILE__).'/delincl.php';
		$error = '';
		if (bab_deldir($this->folderpath, $error)) {
			return true;
		}

		$babBody->addError($error);
		return false;
	}


	


	/**
	 * Extract the archive into a temporary folder
	 * @return string full path to a temporary folder
	 */
	private function temporaryExtractArchive() {

		global $babBody;

		if (null === $this->archive) {
			return null;
		}

		$temp = $GLOBALS['babUploadPath'].'/tmp';

		if (!is_dir($temp)) {
			bab_mkdir($temp);
		}

		$temp.= '/'.__CLASS__.session_id();

		if (is_dir($temp)) {
			include_once dirname(__FILE__).'/delincl.php';
			$error = '';
			if (!bab_deldir($temp, $error)) {
				$babBody->addError($error);
				return null;
			}
		}

		bab_mkdir($temp);

		$zip = bab_functionality::get('Archive/Zip');

		try {
			$zip->open($this->archive);
		} catch (Exception $e) {
			$babBody->addError($e->getMessage());
			return null;
		}


		$zip->extractTo($temp);

		return $temp;
	}

	/**
	 * Get the ini file as an object
	 * @param	string	$classname
	 * @param	string	$iniRelativePath
	 * @return bab_inifile
	 */
	private function getIniObject($classname, $iniRelativePath) {

		include_once $GLOBALS['babInstallPath'].'utilit/inifileincl.php';
		$ini = new $classname();

		if (null !== $this->folderpath) {

			if (!file_exists($this->folderpath.'/'.$iniRelativePath)) {
				return false;
			}

			// archive allready unziped
			$ini->inifile($this->folderpath.'/'.$iniRelativePath);
			return $ini;
		}
		
		if (null !== $this->archive) {
			// archive exist
			try{
				$ini->getfromzip($this->archive, $iniRelativePath);
				return $ini;

			} catch(Exception $e) {
				return false;
			}
		}

		return false;
	}



	/**
	 * Test if the folder/archive is an addon and return bab_inifile object
	 * @return bab_inifile | false
	 */
	private function getAddonIni() {
		return $this->getIniObject('bab_AddonIniFile', 'programs/addonini.php');
	}

	/**
	 * Test if the folder/archive is a collection of addons and return bab_inifile object
	 * @return bab_inifile | false
	 */
	private function getAddonCollectionIni() {
		return $this->getIniObject('bab_AddonCollectionIniFile', 'install/addons/addons.ini');
	}

	/**
	 * Test if the folder/archive is a core distribution version and return bab_inifile object
	 * @return bab_inifile | false
	 */
	private function getCoreIni() {
		return $this->getIniObject('bab_CoreIniFile', 'ovidentia/version.inc');
	}


	/**
	 * Get Ini file of folder or archive
	 * @return	bab_inifile
	 */
	public function getIni() {
		
		foreach(array('getAddonIni', 'getAddonCollectionIni', 'getCoreIni') as $method) {
			$ini = $this->$method();

			if ($ini instanceOf bab_inifile) {
				return $ini;
			}
		}

		throw new Exception(bab_translate('The package is not reconized as an Ovidentia package'));
		return false;
	}



	/**
	 * Install the package or folder in Ovidentia
	 * 
	 * @param	bab_inifile 	$ini
	 * @return	bool
	 */
	public function install(bab_inifile $ini) {
		if ($ini instanceOf bab_AddonIniFile) {

			if (!$this->fixAddonsFolders()) {
				return false;
			}

			return $this->installAddon($ini);
		}

		if ($ini instanceOf bab_AddonCollectionIniFile) {
			return $this->installAddonCollection($ini);
		}

		if ($ini instanceOf bab_CoreIniFile) {
			return $this->installCore($ini);
		}
	}



	/**
	 * Copy files for addons
	 * @param	bab_AddonIniFile $ini
	 * @see bab_getAddonsFilePath()
	 * @return 	bool
	 */
	private function installAddon(bab_AddonIniFile $ini) {
		include_once dirname(__FILE__).'/upgradeincl.php';
		include_once dirname(__FILE__).'/addonsincl.php';

		global $babBody, $babDB;

		$addon_name = $ini->getName();

		if (empty($addon_name)) {
			$babBody->addError(bab_translate('The name of the addon is missing in the addonini file'));
			return false;
		}

		$babDB->db_query("UPDATE ".BAB_ADDONS_TBL." SET installed='N' WHERE title=".$babDB->quote($addon_name));

		$path 	= $this->getFolder().'/';
		$map 	= bab_getAddonsFilePath();

		// browse source path
		foreach ($map['loc_out'] as $key => $source) {

			if (is_dir($path.$source)) {
				$destination = $map['loc_in'][$key].'/';

				if (true !== $result = bab_recursive_cp($path.$source, $destination.$ini->getName())) {
					$babBody->addError($result);
					return false;
				}
			}
		}

		bab_addonsInfos::insertMissingAddonsInTable();
		bab_addonsInfos::clear();
		
		$addon = bab_getAddonInfosInstance($addon_name);
		if ($addon) {
			if (!$addon->upgrade()) {
				$babBody->addError(bab_sprintf(bab_translate('Upgrade of addon %s failed'), $ini->getName()));
				return false;
			}
		}

		return true;
	}




	/**
	 * Fix addons folders
	 * @see bab_getAddonsFilePath()
	 */
	private function fixAddonsFolders() {

		global $babBody;

		$map = bab_getAddonsFilePath();
		
		foreach ($map['loc_in'] as $directory) {
			if (!is_dir($directory)) {
				if (!bab_mkdir($directory, 0777)) {
					$babBody->addError(bab_sprintf(bab_translate('can\'t create directory %s'), $directory));
					return false;
				}
			}
				
			if (!is_writable($directory)) {
				$babBody->addError(bab_sprintf(bab_translate('The directory %s is not writable'), $directory));
				return false;
			}
		}

		return true;
	}




	/**
	 * Install multiple addons
	 * @param	bab_AddonCollectionIniFile $ini
	 * @return	bool
	 */
	private function installAddonCollection(bab_AddonCollectionIniFile $ini) {

		$collection = $ini->getPackageCollection();

		if (null === $collection) {
			$babBody->addError(bab_translate('The package_collection key is missing in the ini file'));
			return false;
		}


		if (false === $ini->isValid()) {
			$babBody->addError(bab_translate('Requirements are not fullfilled'));
			$babBody->babEcho($ini->getRequirementsHtml());
			return false;
		}


		$path = $this->getFolder().'/install/addons/';

		foreach($collection as $folder) {

			$install = new bab_InstallSource;
			$install->setFolder($path.$folder);
			$ini = $install->getIni();
			if (!$install->install($ini)) {
				return false;
			}
		}

		return true;
	}



	/**
	 * Install a Ovidentia upgrade
	 * Unzip the core folder too ovidentia root folder
	 * @param	bab_CoreIniFile $ini
	 * @return	bool
	 */
	private function installCore(bab_CoreIniFile $ini) {

		include_once dirname(__FILE__).'/upgradeincl.php';
		global $babBody;

		$path 	= $this->getFolder().'/';
		$map 	= bab_getAddonsFilePath();
		$core 	= 'ovidentia';

		$destination = realpath('.');

		if (!is_writable($destination)) {
			$babBody->addError(bab_sprintf(bab_translate('The path %s is not writable'), $destination));
			return false;
		}


		if (!is_dir($path.$core)) {
			$babBody->addError(bab_sprintf(bab_translate('The core directory is missing (%s)'), $path.$core));
			return false;
		}

		$version = explode('.', $ini->getVersion());
		$destination .= '/ovidentia-'.implode('-', $version);

		// stop if the folder allready exists

		if (is_dir($destination)) {
			$babBody->addError(bab_sprintf(bab_translate('The folder %s allready exists'), $destination));
			return false;
		}

		if (false === $ini->isValid()) {
			$babBody->addError(bab_translate('The version is not valid, requirements are not fullfilled'));
			$babBody->babEcho($ini->getRequirementsHtml());
			return false;
		}


		$zipversion = $ini->getVersion();

		$current_version_ini = new bab_CoreIniFile();
		$current_version_ini->inifile($GLOBALS['babInstallPath'].'version.inc');
		$current_version = $current_version_ini->getVersion();


		if ( 1 !== version_compare($zipversion, $current_version)) {
			$babBody->addError(bab_translate("The installed version is newer than the package"));
			return false;
		}
		
		
		if (false === $current_version_ini->is_upgrade_allowed($zipversion)) {
			$babBody->addError(bab_translate("The installed version is not compliant with this package, the upgrade within theses two versions has been disabled"));
			return false;
		}


		if (true !== $result = bab_recursive_cp($path.$core, $destination)) {
			$babBody->addError($result);

			if (is_dir($destination)) {
				$msgerror = '';
				include_once dirname(__FILE__).'/delincl.php';
				bab_deldir($destination, $msgerror);
			}

			return false;
		}


		// copy addons from old core
		
		if (!bab_cpaddons($GLOBALS['babInstallPath'], $destination, $babBody->msgerror)) {
			return false;
		}
		

		// Change config

		if (!bab_writeConfig(array('babInstallPath' => basename($destination).'/'))) {
			return false;
		}

		// redirect to upgrade page

		header('location:'.$GLOBALS['babUrlScript'].'?tg=version&idx=upgrade');
		exit;
	}



	/**
	 * Remove temporary folder if exists
	 */
	function __destruct() {
		$this->deleteFolder();
    }
}




