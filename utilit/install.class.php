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
	 * Remove folder install source
	 * @return bool
	 */
	public function deleteFolder() {
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
			bab_deldir($temp, $error);
		}

		bab_mkdir($temp);

		$zip = bab_functionality::get('Archive/Zip');
		$zip->open($this->archive);
		$zip->extractTo($temp);

		return $temp;
	}

	/**
	 * @param	string	$classname
	 * @param	string	$iniRelativePath
	 * @return bab_inifile
	 */
	private function getIniObject($classname, $iniRelativePath) {

		include_once $GLOBALS['babInstallPath'].'utilit/inifileincl.php';
		$ini = new $classname();

		if (null !== $this->folderpath) {
			// archive allready unziped
			$ini->inifile($this->folderpath.'/'.$iniRelativePath);
			return $ini;
		}
		
		if (null !== $this->archive) {
			// archive exist
			$ini->getfromzip($this->archive, $iniRelativePath);
			return $ini;
		}

		return false;
	}



	/**
	 * Test if the folder/archive is an addon and return bab_inifile object
	 * @return bab_inifile | false
	 */
	private function getAddonIni() {
		return $this->getIni('bab_AddonIniFile', 'programs/addonini.php');
	}

	/**
	 * Test if the folder/archive is a collection of addons and return bab_inifile object
	 * @return bab_inifile | false
	 */
	private function getAddonCollectionIni() {
		return $this->getIni('bab_AddonCollectionIniFile', 'install/addons/addons.ini');
	}

	/**
	 * Test if the folder/archive is a core distribution version and return bab_inifile object
	 * @return bab_inifile | false
	 */
	private function getCoreIni() {
		return $this->getIni('bab_CoreIniFile', 'ovidentia/version.inc');
	}


	/**
	 * Get Ini file of folder or archive
	 */
	public function getIni() {
		
		foreach(array('getAddonIni', 'getAddonCollectionIni', 'getCoreIni') as $method) {
			$ini = $this->$method();

			if ($ini instanceOf bab_inifile) {
				return $ini;
			}
		}

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
		global $babBody;

		$path 	= $this->getFolder();
		$map 	= bab_getAddonsFilePath();

		// browse source path
		foreach ($map['loc_out'] as $key => $source) {

			$destination = $map['loc_in'][$key].'/';

			if (true !== $result = bab_recursive_cp($path.$source, $destination.$ini->getName())) {
				$babBody->addError($result);
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


		return true;
	}



	/**
	 * Install multiple addons
	 * @param	bab_CoreIniFile $ini
	 * @return	bool
	 */
	private function installCore(bab_CoreIniFile $ini) {


		return true;
	}
}




