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



class bab_InstallRepository {

	private $list_url;

	private $files = null;

	public function __construct()
	{
		$registry = bab_getRegistry();
		$registry->changeDirectory('/bab/install_repository/');
		$this->list_url = $registry->getValue('list_url');
	}

	/**
	 * Test if the repository exists
	 * @return bool
	 */
	public function exists()
	{
		if (isset($this->list_url))
		{
			return true;
		}

		return false;
	}


    /**
     * @throws Exception
     * @return array
     */
    private function getRows()
    {
        if (null === $this->files) {
            // load list

            $this->files = array();

            if (null !== $this->list_url) {
                $json = file_get_contents($this->list_url);
                if (false === $json) {
                    throw new Exception(sprintf('Failed to download the configured url %s', $this->list_url));
                }

                // warning, json_decode need php 5.2
                $modules = json_decode($json);

                foreach ($modules as $name => $variations) {

                    $name = bab_getStringAccordingToDataBase($name, 'UTF-8');

                    foreach ($variations as $data) {
                        $description = bab_getStringAccordingToDataBase($data->description, 'UTF-8');

                        $installRepositoryFile = new bab_InstallRepositoryFile($name, $data->relativePath, $data->version, $description, $data->dependencies);
                        if (isset($data->icon)) {
                            $installRepositoryFile->icon = $data->icon;
                        }
                        if (isset($data->image)) {
                            $installRepositoryFile->image = $data->image;
                        }
                        $this->files[$name][$data->version] = $installRepositoryFile;
                    }
                }
            }
        }

        return $this->files;
    }

	/**
	 * Get lastest version for each file
	 * @return bab_InstallRepositoryFile[]
	 */
	public function getFiles()
	{
		$arr = $this->getRows();
		$return = array();

		foreach($arr as $name => $d)
		{
			$return[] = $this->getLastest($name);
		}

		bab_Sort::sortObjects($return);

		return $return;
	}



	/**
	 * Get a specific version
	 * @param string $name	Name of addon (filename without version and extension)
	 * @param string $version
	 * @return bab_InstallRepositoryFile
	 */
	public function getFile($name, $version)
	{
		$arr = $this->getRows();

		if (!isset($arr[$name][$version]))
		{
			return null;
		}

		return $arr[$name][$version];
	}


	/**
	 *
	 * @param string $name
	 * @return NULL|multitype:string
	 */
	public function getAvailableVersions($name)
	{
		$arr = $this->getRows();

		if (!isset($arr[$name]))
		{
			return null;
		}

		uksort($arr[$name] , 'version_compare');

		return array_keys($arr[$name]);
	}



	/**
	 * Get lastest file (higher version)
	 * @param string $name
	 * @return bab_InstallRepositoryFile
	 */
	public function getLastest($name)
	{
		$arr = $this->getRows();

		if (!isset($arr[$name]))
		{
			return null;
		}

		uksort($arr[$name] , 'version_compare');

		return end($arr[$name]);
	}

	/**
	 *
	 * @return strin
	 */
	public function getRootUrl()
	{
		$registry = bab_getRegistry();
		$registry->changeDirectory('/bab/install_repository/');
		return $registry->getValue('root_url');
	}
}



class bab_InstallRepositoryFile
{
    public $name;
    public $filepath;
    public $version;
    public $description;
    public $dependencies = array();

    public $icon = null;
    public $image = null;



    public function __construct($name, $filepath, $version, $description, $dependencies)
    {
        $this->name = $name;
        $this->filepath = $filepath;
        $this->version = $version;
        $this->description = $description;

        foreach ($dependencies as $addonname => $d) {
            $m = null;
            if (!empty($d) && preg_match('/([<>]*=)([\w\d\.]+)/', $d, $m)) {
                $operator = $m[1];
                $version = $m[2];

                $this->dependencies[$addonname] = array($operator, $version);
            }
        }
    }

    /**
     * @throws Exception
     * @return string
     */
    private static function getRootUrl()
    {
        static $rootUrl = null;
        if (!isset($rootUrl)) {
            $registry = bab_getRegistry();
            $registry->changeDirectory('/bab/install_repository/');
            $rootUrl = $registry->getValue('root_url');
            if (!isset($rootUrl)) {
                throw new Exception('Missing configuration for root_url');
            }
        }
        return $rootUrl;
    }


    /**
     * @return string|null
     */
    public function getIconUrl()
    {
        if (isset($this->icon)) {
            return self::getRootUrl() . $this->icon;
        }
        return null;
    }


    /**
     * @return string|null
     */
    public function getImageUrl()
    {
        if (isset($this->image)) {
            return self::getRootUrl() . $this->image;
        }
        return null;
    }


    /**
     * @return string
     */
    public function getUrl()
    {
        return self::getRootUrl() . $this->filepath;
    }


	/**
	 * @return string
	 */
	public function getFileName()
	{
		return basename($this->filepath);
	}


	/**
	 * Download the file and install package
	 *
	 * @throws Exception
	 *
	 * @return bool
	 */
	public function install($updateProgess = false)
	{

		$tmpfile = $this->downloadTmpFile($updateProgess);

		$install = new bab_InstallSource;
		$install->setArchive($tmpfile);
		$ini = $install->getIni();

		if (!$ini->isValid()) {
		    bab_installWindow::message(
	           bab_toHtml(
	               sprintf(bab_translate('Package %s is not valid, please check dependencies'), $install->getArchive())
	           )
	        );

		    return false;
		}

		if ($install->install($ini)) {
			if (!unlink($install->getArchive())) {
				bab_installWindow::message(
				    bab_toHtml(
				        sprintf(bab_translate('Failed to delete the temporary package %s'), $install->getArchive())
				    )
				);
			}
		}

		return true;
	}

	/**
	 * @return string
	 */
	private function getTmpPath()
	{
		require_once dirname(__FILE__).'/settings.class.php';
		$settings = bab_getInstance('bab_Settings');
		/*@var $settings bab_Settings */
		return $settings->getUploadPath().'/tmp';
	}


	/**
	 * Create a temporary local copy of the archive
	 *
	 * @throws Exception
	 *
	 * @param	bool	$updateProgess 	Set to TRUE to update the progress bar
	 * @return string	Full path to downloaded temporary file
	 */
	public function downloadTmpFile($updateProgess = false)
	{

		$url = $this->getUrl();
		if (!$rfp = fopen($url, 'r')) {
			throw new Exception(sprintf(bab_translate("Failed to open URL (%s)"), $url));
		}

		$filename = $this->getFileName();

		$tmpfile = $this-> getTmpPath().'/'.$filename;
		if (!$wfp = fopen($tmpfile, 'w')) {
			throw new Exception(sprintf(bab_translate("Failed to write temporary file (%s)"), $tmpfile));
		}

		if ($updateProgess)
		{
			$progress = new bab_installProgressBar;
			$progress->setTitle(sprintf(bab_translate('Download %s'), $filename));
		} else {
			$progress = null;
		}

		$this->downloadProgress($rfp, $wfp, $progress);

		return $tmpfile;
	}


	/**
	 * Download the file
	 * @param	ressource 				$rfp		file pointer resource, readable source file
	 * @param	ressource 				$wfp 		file pointer resource, writable destination
	 * @param	bab_installProgressBar	$progress	Optional progess bar
	 *
	 */
	private function downloadProgress($rfp, $wfp, bab_installProgressBar $progress = null)
	{

		$packetsize = 2048;


		if (isset($progress))
		{
			$readlength = 0;
			$totallength = $this->getLength($rfp);
		}

		while (!feof($rfp)) {
			$data = fread($rfp, $packetsize);
			fwrite($wfp, $data);

			if (isset($progress))
			{
				$readlength += strlen($data);
				$p = (($readlength * 100) / $totallength);
				$progress->setProgression($p);
			}
		}

		if (isset($progress))
		{
			$progress->setProgression(100);
		}
	}



	/**
	 * Length of file from url
	 * @param	ressource $fp
	 * @return number
	 */
	private function getLength($fp)
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
	 * @return bool
	 */
	public function isInstalled()
	{
		if ('ovidentia' === $this->name)
		{
			return true;
		}

		if (false === bab_getAddonInfosInstance($this->name))
		{
			return false;
		}

		return true;
	}


	/**
	 *
	 * @return bool
	 */
	public function isUpgradable()
	{
		$current_version = $this->getCurrentVersion();
		if (!isset($current_version))
		{
			return false;
		}
		return version_compare($this->version, $current_version, '>');
	}

	/**
	 * Get current version from ini file
	 * @return string|null
	 */
	public function getCurrentVersion()
	{
		if ('ovidentia' === $this->name)
		{
			return bab_getIniVersion();
		}

		$addon = bab_getAddonInfosInstance($this->name);

		if (false === $addon)
		{
			return null;
		}

		return $addon->getIniVersion();
	}


	public function __toString()
	{
		return $this->name;
	}
}




/**
 * Install files in Ovidentia addons or distribution upgrade by zip archive or folder
 *
 */
class bab_InstallSource {

	private $archive = null;
	private $folderpath = null;


	/**
	 * @return string
	 */
	private function getTmpPath()
	{
		require_once dirname(__FILE__).'/settings.class.php';
		$settings = bab_getInstance('bab_Settings');
		/*@var $settings bab_Settings */
		return $settings->getUploadPath().'/tmp';
	}



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
	 * @return string | null
	 */
	public function getArchive() {
		return $this->archive;
	}

	/**
	 * Set the install source from an already unziped folder
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
	 *
	 * @throws Exception
	 *
	 * @return string full path to a temporary folder
	 */
	private function temporaryExtractArchive() {


		global $babBody;

		if (null === $this->archive) {
			return null;
		}

		require_once dirname(__FILE__).'/path.class.php';
		require_once dirname(__FILE__).'/session.class.php';
		$session = bab_getInstance('bab_Session');
		/*@var $session bab_Session */

		$temp = new bab_Path($this->getTmpPath());

		if (!$temp->isDir()) {
			$temp->createDir();
		}

		$temp->push(__CLASS__.'_'.$session->getId());

		if ($temp->isDir()) {
			$temp->deleteDir();
		}

		$temp->createDir();

		chmod($temp->tostring(), 0777);

		$zip = bab_functionality::get('Archive/Zip');
		$zip->open($this->archive);

		$zip->extractTo($temp->tostring());

		return $temp->tostring();
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

			// archive already unziped
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

		$message = bab_translate('The package is not reconized as an Ovidentia package');
		throw new Exception($message);
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


	private function isIncluded($addon, $file)
	{
		global $babBody;

		$target = realpath('./'.$GLOBALS['babInstallPath'].'addons/'.$addon.'/'.$file);

		if (false === $target)
		{
			// if realpath failed, the file does not exist
			return false;
		}

		return in_array($target, get_included_files());

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
		include_once dirname(__FILE__).'/utilit.php';

		global $babDB;

		$babBody = bab_getInstance('babBody');
		/*@var $babBody babBody */

		$addon_name = $ini->getName();

		if (empty($addon_name)) {
			$babBody->addError(bab_translate('The name of the addon is missing in the addonini file'));
			return false;
		}


		if ($this->isIncluded($addon_name, 'init.php'))
		{
			$babBody->addError(bab_translate('The file init.php is allready included for this addon'));
			return false;
		}


		$babDB->db_query("UPDATE ".BAB_ADDONS_TBL." SET installed='N' WHERE title=".$babDB->quote($addon_name));
		$path 	= $this->getFolder().'/';
		//if addon is compatible with vendor
		if ((file_exists($path.'composer.json')) && (file_exists('vendor/ovidentia'))) {
		  $unzipAddon = $this->installToVendorFolder($ini);
		}
		//addon is not compatible with vendor
		else{
		  $unzipAddon = $this->installToAddonsFolder($ini);
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
	 * Copy files for addons in the vendor folder
	 * @param	bab_AddonIniFile $ini
	 * @see bab_getAddonsFilePath()
	 * @return 	bool
	 */
    private function installToVendorFolder(bab_AddonIniFile $ini) {
        include_once dirname(__FILE__).'/upgradeincl.php';
        include_once dirname(__FILE__).'/addonsincl.php';
        include_once dirname(__FILE__).'/utilit.php';

        global $babDB;

        $babBody = bab_getInstance('babBody');
        /*@var $babBody babBody */
        $path 	= $this->getFolder().'/';

                if (true !== $result = bab_recursive_cp($path.$source, 'vendor/ovidentia/'.$ini->getName(), true)) {
                    $babBody->addError($result);
                    return false;
                }
        return true;
    }




    /**
     * Copy files for addons in the addons folder
     * @param	bab_AddonIniFile $ini
     * @see bab_getAddonsFilePath()
     * @return 	bool
     */
    private function installToAddonsFolder(bab_AddonIniFile $ini) {
        include_once dirname(__FILE__).'/upgradeincl.php';
        include_once dirname(__FILE__).'/addonsincl.php';
        include_once dirname(__FILE__).'/utilit.php';

        global $babDB;

        $babBody = bab_getInstance('babBody');
        /*@var $babBody babBody */

        $addon_name = $ini->getName();
        $map = bab_getAddonsFilePath();
        $path 	= $this->getFolder().'/';
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

	    global $babBody;

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

			bab_installWindow::message(sprintf(bab_translate('Install of addon %s'), $folder));

			$install = new bab_InstallSource;
			$install->setFolder($path.$folder);

			try {
				$ini = $install->getIni();
			} catch(Exception $e) {
				bab_installWindow::message($e->getMessage());
				return false;
			}

			if (!$install->install($ini)) {
				bab_installWindow::message(sprintf(bab_translate('Install script failed in addon %s'), $folder));
				return false;
			}
		}

		return true;
	}



	/**
	 * Install a Ovidentia upgrade
	 * Unzip the core folder to ovidentia root folder
	 *
	 * @since 7.3.92 	copy addons to the install/addons folder, addons will be installable if the original version is greater than 7.3.92
	 * 					before this version, the install/addons folder will contain addons from the first package of ovidentia or nothing
	 * 					if the original installation is too old
	 *
	 * @param	bab_CoreIniFile $ini
	 * @return	bool
	 */
	private function installCore(bab_CoreIniFile $ini) {

		include_once dirname(__FILE__).'/upgradeincl.php';
		include_once dirname(__FILE__).'/path.class.php';
		include_once dirname(__FILE__).'/delincl.php';

		global $babBody;

		$path 	= $this->getFolder().'/';
		$map 	= bab_getAddonsFilePath();
		$core 	= 'ovidentia';

		$destination = realpath('.');


		if (!is_writable($destination.'/config.php')) {
			bab_installWindow::message(bab_sprintf(bab_translate('The config.php file is not writable'), $destination));
			return false;
		}

		$destpath = new bab_Path($destination);

		try {
			$destpath->isFolderWriteable();

		} catch(bab_FolderAccessRightsException $e) {
			bab_installWindow::message($e->getMessage());
			return false;
		}


		if (!is_dir($path.$core)) {
			bab_installWindow::message(bab_sprintf(bab_translate('The core directory is missing (%s)'), $path.$core));
			return false;
		}

		$version = explode('.', $ini->getVersion());
		$destination .= '/ovidentia-'.implode('-', $version);

		// stop if the folder already exists

		if (is_dir($destination)) {
			bab_installWindow::message(bab_sprintf(bab_translate('The folder %s already exists'), $destination));
			return false;
		}

		if (false === $ini->isValid()) {
			bab_installWindow::message(bab_translate('The version is not valid, requirements are not fullfilled'));
			bab_installWindow::message($ini->getRequirementsHtml());
			return false;
		}


		// prepare requirement for addons to upgrade

		$install = new bab_Path(realpath('.').'/install');

		// check for the install folder

		if ($install->isDir()) {

			try {
				$install->isFolderWriteable();
			} catch(bab_FolderAccessRightsException $e) {
				bab_installWindow::message($e->getMessage());
				return false;
			}

		} else {
			// try to create the install folder

			if (!$install->createDir())
			{
				bab_installWindow::message(bab_translate('The folder is not writable'));
				return false;
			}
		}

		// remove old addons from install folder if exists

		$msgerror = '';
		if (is_dir($install->toString().'/addons'))
		{
			if (!bab_deldir($install->toString().'/addons', $msgerror))
			{
				bab_installWindow::message($msgerror);
				return false;
			}
		}

		if (is_file($install->toString().'/addons.ini'))
		{
			if (!unlink($install->toString().'/addons.ini'))
			{
				return false;
			}
		}


		$zipversion = $ini->getVersion();

		$current_version_ini = new bab_CoreIniFile();
		$current_version_ini->inifile($GLOBALS['babInstallPath'].'version.inc');
		$current_version = $current_version_ini->getVersion();


		if ( -1 == version_compare($zipversion, $current_version)) {
			bab_installWindow::message(bab_translate("The installed version is newer than the package"));
			return false;
		}


		if (false === $current_version_ini->is_upgrade_allowed($zipversion)) {
			bab_installWindow::message(bab_translate("The installed version is not compliant with this package, the upgrade within theses two versions has been disabled"));
			return false;
		}


		// copy temporary unziped core to the new core folder

		if (true !== $result = bab_recursive_cp($path.$core, $destination)) {
			bab_installWindow::message($result);

			if (is_dir($destination)) {
				$msgerror = '';
				bab_deldir($destination, $msgerror);
			}

			return false;
		}


		// copy addons from old core to new core

		if (!bab_cpaddons($GLOBALS['babInstallPath'], $destination, $babBody->msgerror)) {
			return false;
		}



		// copy temporary unziped addons and the addons.ini to the install/addons folder
		if (true !== $result = bab_recursive_cp($path.'install/addons', $install->toString().'/addons')) {
			bab_installWindow::message($result);
			return false;
		}

		if (!copy($path.'install/addons.ini', $install->toString().'/addons.ini'))
		{
			return false;
		}


		// Change config

		if (!bab_writeConfig(array('babInstallPath' => basename($destination).'/'))) {
			return false;
		}



		// redirect to upgrade page

		$upgrade_page = $GLOBALS['babUrlScript'].'?tg=version&idx=upgrade&iframe=1';

		bab_installWindow::message(sprintf('

			<script type="text/javascript">
				document.location.href = \'%s\'
			</script>

			<a href="%s">Install</a>
		', $upgrade_page, bab_toHtml($upgrade_page)));






		/*
		// call upgrade code
		// force new install path
		$GLOBALS['babInstallPath'] = basename($destination).'/';

		$str = '';
		bab_upgrade(basename($destination).'/', $str);
		if (!empty($str))
		{
			bab_installWindow::message($str);
		}
		*/
	}



	/**
	 * Remove temporary folder if exists
	 */
	function __destruct() {
		$this->deleteFolder();
    }
}









/**
 * template
 */
class bab_installWindowTpl {

	public 	$t_upgrade 	= null;
	public	$t_wait		= null;
	public 	$t_continue = null;
	public 	$frameurl	= null;
	public 	$listurl	= null;

	public function __construct() {

		$this->t_wait = bab_toHtml(bab_translate('Installing, please wait...'), BAB_HTML_JS);
		$this->t_continue = bab_toHtml(bab_translate('Back to list'), BAB_HTML_JS);
	}
}



/**
 * Progress bar
 */
class bab_installProgressBar {

	private static $count = 0;
	private $uid = null;
	private $title = null;

	public function setTitle($title) {
		$this->title = $title;
	}


	/**
	 * @param	int		$percent	number beetween 0 and 100
	 */
	public function setProgression($percent) {
		if (null === $this->uid) {
			$this->draw();
		}

		bab_installWindow::message(sprintf('<script type="text/javascript">document.getElementById(\'%s\').style.width = \'%d%%\'</script>', $this->uid, $percent));
	}

	private function draw() {
		$this->uid = __CLASS__.'_'.self::$count;
		self::$count++;

		$html = '
			<div style="margin:.5em; padding:.3em; border:#ccc 1px solid; background:#eee; text-align:center; width:80%">';

		if (null !== $this->title) {
			$html .= '<div><small>'.bab_toHtml($this->title).'</small></div>';
		}

		$html .= '
				<div style="width:100%; height:10px; border:#999 1px solid; background:white; ">
					<div style="width:0%;height:10px;background:lightblue;" id="'.$this->uid.'"></div>
				</div>
			</div>
		';

		bab_installWindow::message($html);
	}

}







/**
 * Frame used to install or upgrade
 * @since 7.1.90
 */
class bab_installWindow {

	private $startMessage 	= null;
	private $successMessage = null;
	private $failureMessage	= null;


	/**
	 * Get the page with installation process
	 * @param	string	$title			Iframe title
	 * @param	string	$frameurl		The url to iframe content (a page with a call to startInstall method)
	 * @param	string	$nextpagetitle	button label
	 * @param	string	$nextpageurl	action for button when installation finished
	 */
	public static function getPage($title, $frameurl, $nextpagetitle, $nextpageurl) {

		global $babBody;

		$page = new bab_installWindowTpl();

		$page->t_upgrade 	= bab_toHtml($title);
		$page->frameurl 	= bab_toHtml($frameurl);
		$page->t_continue 	= bab_toHtml($nextpagetitle	, BAB_HTML_JS);
		$page->listurl	 	= bab_toHtml($nextpageurl	, BAB_HTML_JS);

		$babBody->babecho(bab_printTemplate($page, "addons.html", "upgrade"));

	}



	public function setStartMessage($message) {
		$this->startMessage = $message;
	}

	public function setStopMessage($message1, $message2) {
		$this->successMessage = $message1;
		$this->failureMessage = $message2;
	}

	/**
	 * The method will display a frame and call the callback
	 * Output buffering is disabled to allow install messages throw the message static method
	 * @param	mixed	$callback		array or string			the function must return a boolean
	 */
	public function startInstall($callback) {

		require_once dirname(__FILE__).'/utilit.php';
		$babBody = bab_getInstance('babBody');

		if (function_exists('apache_setenv')) {
			@apache_setenv('no-gzip'			, 1);
		}
		@ini_set('zlib.output_compression'	, 0);
		@ini_set('implicit_flush'			, 1);
		for ($i = 0; $i < ob_get_level(); $i++) { ob_end_flush(); }
		ob_implicit_flush(1);

		echo '<html><head></head><body style="background:#fff;">'."\n";
		define('BAB_INSTALL_SCRIPT_BEGIN', 1);

		if (null === $this->startMessage) {
			$this->startMessage = bab_translate('Install start');
		}
		self::message($this->startMessage);

		$result = call_user_func($callback);

		if ($babBody->msgerror) {
			self::message(bab_toHtml($babBody->msgerror, BAB_HTML_ALL));
		}

		if ($result) {
			if (null === $this->successMessage) {
				$this->successMessage = bab_translate('The install is successfull');
			}
			self::message($this->successMessage);
		} else {
			if (null === $this->failureMessage) {
				$this->failureMessage = bab_translate('There is an error in install');
			}
			self::message($this->failureMessage);
		}

		// javascript need an item to know this is the end
		echo '<br id="BAB_ADDON_INSTALL_END" />'."\n";
		echo '</body></html>';
	}



	/**
	 * This function echo a message in displayed install log
	 * This function is usable in addons
	 * @see bab_setUpgradeLogMsg
	 *
	 * @param	string	$html
	 */
	public static function message($html) {
		if (defined('BAB_INSTALL_SCRIPT_BEGIN')) {
			echo '<div class="bab_install_message">'.$html.'</div>'."\n";
		}

		if (defined('BAB_INSTALL_TEXT_UTF8')) { // output install message to console
			echo bab_convertStringFromDatabase(bab_unhtmlentities(strip_tags($html)), 'UTF-8')."\n";
		}
	}
}


