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
 * Manage addons informations for multiples addons
 */
class bab_addonsInfos {

	private $indexById 			= array();
	private $indexByName 		= array();
	private $fullIndexById		= array();
	private $fullIndexByName	= array();



	/**
	 * Get accessible status and update table if necessary
	 * @param	int		$id_addon
	 * @param	string	$title		Addon name
	 * @param	string	$version	Database addon version
	 * @param	string	$installed	Y|N
	 * @param	string	$enabled	Y|N
	 * @return boolean
	 */
	private static function isAccessible($id_addon, $title, $version, $installed, $enabled) {

		if ('Y' !== $installed || 'Y' !== $enabled) {
			return false;
		}


		include_once $GLOBALS['babInstallPath'].'utilit/inifileincl.php';
		global $babDB;
		
		$ini = new bab_inifile();
		$ini->inifileGeneral($GLOBALS['babAddonsPath'].$title.'/addonini.php');
		$arr_ini = $ini->inifile;

		$access_control = isset($arr_ini['addon_access_control']) ? (int) $arr_ini['addon_access_control'] : 1;
	
		$access = false;
		if (0 === $access_control || bab_isAccessValid(BAB_ADDONS_GROUPS_TBL, $id_addon)) {
			if($ini->getVersion()) {
				if ($ini->getVersion() == $version) {
					$access = true;
				}
				else {
					$babDB->db_query("UPDATE ".BAB_ADDONS_TBL." SET installed='N' WHERE id='".$babDB->db_escape_string($id_addon)."'");
				}
			}
		}
		return $access;
	}



	/**
	 * Create indexes with access rights verification
	 * @return boolean
	 */
	private function createIndex() {
		
	
		if (!$this->indexById || !$this->indexByName) {
		
			global $babDB;
	
			$res = $babDB->db_query("select * from ".BAB_ADDONS_TBL." where enabled='Y' AND installed='Y'");
			while( $arr = $babDB->db_fetch_assoc($res)) {

				$arr['access'] = self::isAccessible($arr['id'], $arr['title'], $arr['version'],'Y', 'Y');
					
				$this->indexById[$arr['id']] 		= $arr;
				$this->indexByName[$arr['title']] 	= $arr;
			}
		}
		
		
		return !empty($this->indexById);
	}
	
	
	
	/**
	 * Create full index of addons with disabled and not installed addons
	 * @return boolean
	 */
	private function createFullIndex() {
	
		if (!$this->fullIndexById || !$this->fullIndexByName) {
		
			global $babDB;
	
			$res = $babDB->db_query("select * from ".BAB_ADDONS_TBL."");
			while( $arr = $babDB->db_fetch_array($res)) {
					
				$this->fullIndexById[$arr['id']] 		= $arr;
				$this->fullIndexByName[$arr['title']] 	= $arr;
			}
		}
		
		
		return !empty($this->fullIndexById);
	}
	
	



	/**
	 * Get available addons indexed by id
	 * since $babBody->babaddons is deprecated, this method has the same result
	 * @return array
	 */
	public static function getRows() {
	
		$obj = bab_getInstance('bab_addonsInfos');
		$obj->createIndex();
		
		return $obj->indexById;
	}
	
	
	/**
	 * Get addon row from installed and enabled addons list
	 * @param	int	$id_addon
	 * @return	false|array
	 */
	public static function getRow($id_addon) {
		
		$arr = bab_addonsInfos::getDbRows();
		
		if (!isset($arr[$id_addon])) {
			return false;
		}
		
		if (!self::isAccessible($id_addon, $arr[$id_addon]['title'], $arr[$id_addon]['version'], $arr[$id_addon]['installed'], $arr[$id_addon]['enabled'])) {
			return false;
		}

		$arr[$id_addon]['access'] = true;
		
		return $arr[$id_addon];
	}
	
	
	/**
	 * Get all addons indexed by id
	 * @return array
	 */
	public static function getDbRows() {
	
		$obj = bab_getInstance('bab_addonsInfos');
		$obj->createFullIndex();
		
		return $obj->fullIndexById;
	}
	
	
	/**
	 * Get all addons rows indexed by name
	 * @return array
	 */
	public static function getDbRowsByName() {
		$obj = bab_getInstance('bab_addonsInfos');
		$obj->createFullIndex();
		
		return $obj->fullIndexByName;
	}

	/**
	 * Get all addons objects indexed by name
	 * @return array	of bab_addonInfos
	 * @see bab_addonInfos
	 */
	public static function getDbAddonsByName() {
		$return = array();
		foreach(self::getDbRows() as $row) {
			if ($obj = bab_getAddonInfosInstance($row['title'])) {
				$return[$row['title']] = $obj;
			}
		}

		return $return;
	}

	
	/**
	 * Get addon row if exist, from all addons in table
	 * @param	int	$id_addon
	 * @return	false|array
	 */
	public static function getDbRow($id_addon) {
	
		$arr = bab_addonsInfos::getDbRows();
		
		if (!isset($arr[$id_addon])) {
			return false;
		}
		
		return $arr[$id_addon];
	}
	
	
	
	/**
	 * Clear cache for addons
	 */
	public static function clear() {
		global $babBody;
	
		$babBody->babaddons = array();
		
		$obj = bab_getInstance('bab_addonsInfos');
		
		$obj->indexById 		= array();
		$obj->indexByName 		= array();
		$obj->fullIndexById 	= array();
		$obj->fullIndexByName 	= array();
	}
	
	
	
	/**
	 * Get addon id by name
	 *
	 * @param	string	$name
	 * @param	boolean	$access_rights
	 *
	 * @return int|false
	 */
	public static function getAddonIdByName($name, $access_rights = true) {
		
		
		$obj = bab_getInstance('bab_addonsInfos');
		$obj->createFullIndex();
		
		if (!isset($obj->fullIndexByName[$name])) {
			return false;
		}

		$arr = $obj->fullIndexByName[$name];

		if ($access_rights && !bab_addonsInfos::isAccessible($arr['id'], $arr['title'], $arr['version'], $arr['installed'], $arr['enabled'])) {
			return false;
		}
		
		return (int) $arr['id'];
	}
	
	
	
	
	
	
	/**
	 * Browse addons folder and add missing addons to bab_addons
	 */
	public static function insertMissingAddonsInTable() {
	
		global $babDB;
	
		$h = opendir($GLOBALS['babAddonsPath']);
		while (($f = readdir($h)) != false)
			{
			if ($f != "." and $f != "..") 
				{
				if (is_dir($GLOBALS['babAddonsPath'].$f) && is_file($GLOBALS['babAddonsPath'].$f."/init.php"))
					{
					$res = $babDB->db_query("SELECT * FROM ".BAB_ADDONS_TBL." 
					where title='".$babDB->db_escape_string($f)."' ORDER BY title ASC");
					if( $res && $babDB->db_num_rows($res) < 1)
						{
							$babDB->db_query("
							INSERT INTO ".BAB_ADDONS_TBL." (title, enabled) 
							VALUES ('".$babDB->db_escape_string($f)."', 'Y')
							");
						}
					}
				}
			}
		closedir($h);
	}
	
	
	
}







/**
 * Manage addon informations for one addon
 * @since 6.6.93
 */
class bab_addonInfos {
	
	/**
	 * @access private
	 */
	var $id_addon;
	
	/**
	 * @access private
	 */
	var $addonname;
	
	/**
	 * @access private
	 */
	var $ini = null;


	/**
	 * Set addon Name
	 * This function verifiy if the addon is accessible
	 * define $this->id_addon and $this->addonname
	 * @see bab_getAddonInfosInstance() this method is used for the creation of the instance with acces_rights=false
	 *
	 * @param	string	$addonname
	 * @param	boolean	$access_rights	: access rights verification on addon
	 * @return boolean
	 */
	public function setAddonName($addonname, $access_rights = true) {
		
		$id_addon = bab_addonsInfos::getAddonIdByName($addonname, $access_rights);
		
		if (false === $id_addon) {
			return false;
		}
		
		if ($access_rights) {
			if (!bab_isAddonAccessValid($id_addon)) {
				return false;
			}
		}
	
		$this->id_addon = $id_addon;
		$this->addonname = $addonname;
			
		return true;
	}
	
	
	
	/**
	 * Get the addon name
	 * a replacement for $babAddonFolder
	 * @return string
	 */
	public function getName() {
		return $this->addonname;
	}
	
	/**
	 * get the addon ID
	 * @return int
	 */
	public function getId() {
		return (int) $this->id_addon;
	}
	
	
	/**
	 * a replacement for $babAddonTarget
	 * @return string
	 */
	public function getTarget() {
		return "addon/".$this->id_addon;
	}
	
	/**
	 * a replacement for $babAddonUrl
	 * @return string
	 */
	public function getUrl() {
		return $GLOBALS['babUrlScript'].'?tg=addon%2F'.$this->id_addon.'%2F';
	}

	/**
	 * 
	 * a replacement for $babAddonHtmlPath
	 * @return string
	 */
	public function getRelativePath() {
		return 'addons/'.$this->addonname.'/';
	}
	
	/**
	 * a replacement for $babAddonPhpPath
	 * @return string
	 */
	public function getPhpPath() {
		return $GLOBALS['babInstallPath'].$this->getRelativePath();
	}
	
	/**
	 * Get the addon upload path
	 * a replacement for $babAddonUpload
	 * @return string
	 */
	public function getUploadPath() {
		return $GLOBALS['babUploadPath'].'/'.$this->getRelativePath();
	}
	
	/**
	 * Get path to template directory
	 * @return string
	 */
	public function getTemplatePath() {
		return $GLOBALS['babInstallPath'].'skins/ovidentia/templates/'.$this->getRelativePath();
	}
	
	
	/**
	 * Get path to images directory
	 * @return string
	 */
	public function getImagesPath() {
		return $GLOBALS['babInstallPath'].'skins/ovidentia/images/'.$this->getRelativePath();
	}
	
	
	/**
	 * Get path to ovml directory
	 * @return string
	 */
	public function getOvmlPath() {
		return $GLOBALS['babInstallPath'].'skins/ovidentia/ovml/'.$this->getRelativePath();
	}
	
	
	/**
	 * Get path to css stylesheets directory
	 * @return string
	 */
	public function getStylePath() {
		return $GLOBALS['babInstallPath'].'styles/'.$this->getRelativePath();
	}
	
	/**
	 * Get path to translation files directory
	 * @return string
	 */
	public function getLangPath() {
		return $GLOBALS['babInstallPath'].'lang/'.$this->getRelativePath();
	}
	
	
	/**
	 * get INI object, general section only
	 * @return bab_inifile
	 */
	public function getIni() {
		if (null === $this->ini) {
			include_once $GLOBALS['babInstallPath'].'utilit/inifileincl.php';
			$this->ini = new bab_inifile();
			$inifile = $this->getPhpPath().'addonini.php';
			
			if (!is_readable($inifile)) {
				throw new Exception(sprintf('Error, the file %s must be readable', $inifile));
			}
			
			if (!$this->ini->inifileGeneral($inifile)) {
				throw new Exception(sprintf('Error, the file %s is missing or has syntax errors', $inifile));
			}
		}
		
		return $this->ini;
	}
	
	
	/**
	 * Check validity of addon INI file requirements
	 * @return boolean
	 */
	public function isValid() {
		include_once $GLOBALS['babInstallPath'].'utilit/inifileincl.php';
		$ini = new bab_inifile();
		$ini->inifile($this->getPhpPath().'addonini.php');
		return $ini->isValid();
	}
	

	/**
	 * Get configuration url or null if no configuration page defined
	 * @return string
	 */
	public function getConfigurationUrl() {

		$ini = $this->getIni();

		if (!isset($ini->inifile['configuration_page'])) {
			return null;
		}

		return $this->getUrl().$ini->inifile['configuration_page'];
	}

	
	
	/**
	 * addon has global access control 
	 * @return boolean
	 */
	public function hasAccessControl() {
		$ini = $this->getIni();

		if (!$ini->fileExists()) {
			return false;
		}


		return !isset($ini->inifile['addon_access_control']) || 
			(isset($ini->inifile['addon_access_control']) && 1 === (int) $ini->inifile['addon_access_control']);
	}
	
	
	
	
	
	/**
	 * Get the type of addon.
	 * The addon type can be EXTENSION | LIBRARY | THEME
	 * 
	 * @return string
	 */
	public function getAddonType() {

		try {
			$ini = $this->getIni();
		} catch(Exception $e) {
			return 'EXTENSION';
		}

		if (!$ini->fileExists()) {
			return 'EXTENSION';
		}


		if (isset($ini->inifile['addon_type'])) {
			return $ini->inifile['addon_type'];
		}
	
		if (is_dir('skins/'.$this->getName())) {
			return 'THEME';
		}
	
		if ($this->hasAccessControl()) {
			return 'EXTENSION';
		} else {
			return 'LIBRARY';
		}
	}
	
	
	
	/**
	 * addon is deletable by administrator
	 * @return boolean
	 */
	public function isDeletable() {
		try {
			$ini = $this->getIni();
		} catch (Exception $e) {
			return true;
		}
		return !$ini->fileExists() || (isset($ini->inifile['delete']) && 1 === (int) $ini->inifile['delete']);
	}
	
	/**
	 * Test if addon is accessible
	 * if access control, and addons access rights verification return false, addon is not accessible
	 * if addons is disabled, the addons is not accessible
	 * if addon is not installed, addon is not accessible
	 * @return boolean
	 */
	public function isAccessValid() {
		if (bab_isAddonAccessValid($this->id_addon)) {
			return true;
		}
		
		return false;
	}
	
	/**
	 * is addon installed by administrator
	 * @return boolean
	 */
	public function isInstalled() {
		$arr = bab_addonsInfos::getDbRow($this->id_addon);
		return 'Y' === $arr['installed'];
	}
	
	
	/**
	 * is addon disabled by administrator
	 * @return boolean
	 */
	public function isDisabled() {
	
		$arr = bab_addonsInfos::getDbRow($this->id_addon);
		return 'N' === $arr['enabled'];
	}
	
	/**
	 * Disable addon
	 * @return bab_addonInfos
	 */
	public function disable() {
		global $babDB;
		$babDB->db_query("UPDATE ".BAB_ADDONS_TBL." set enabled='N' WHERE id=".$babDB->quote($this->id_addon));
		bab_addonsInfos::clear();
		return $this;
	}
	
	
	/**
	 * Enable addon
	 * @return bab_addonInfos
	 */
	public function enable() {
		global $babDB;
		$babDB->db_query("UPDATE ".BAB_ADDONS_TBL." set enabled='Y' WHERE id=".$babDB->quote($this->id_addon));
		bab_addonsInfos::clear();
		return $this;
	}
	
	
	/**
	 * Get version from ini file
	 * @return string
	 */
	public function getIniVersion() {
	
		$ini = $this->getIni();
		return $ini->getVersion();
	}
	
	
	/**
	 * Get description from ini file
	 * @return string
	 */
	public function getDescription() {
		
		$ini = $this->getIni();

		if (false === $ini->fileExists()) {
			return bab_translate('Error, the files of addon are missing, please delete the addon or restore the orginal addon folders');
		}

		return $ini->getDescription();
	}
	
	
	
	
	
	
	
	/**
	 * get version from database
	 * @return string
	 */
	public function getDbVersion() {
		$arr = bab_addonsInfos::getDbRow($this->id_addon);
		return $arr['version'];
	}
	
	/**
	 * Test if the addon need an upgrade of the database
	 * @return bool
	 */
	public function isUpgradable() {

		$ini = $this->getIni();

		if (!$ini->fileExists()) {
			return false;
		}
	
		$vini 	= $this->getIniVersion();
		$vdb 	= $this->getDbVersion();
	
		if ( empty($vdb) || 0 !== version_compare($vdb,$vini) || !$this->isInstalled()) {
			return true;
		}
		
		return false;
	}
	
	
	/**
	 * Verify addon installation status
	 * after addon files has been modified, this method update the table with new installation status
	 * @return boolean
	 */
	public function updateInstallStatus() {
	
		if (!$this->isUpgradable()) {
			return false;
		}
	
		global $babDB;

		
		
		if (!is_file($this->getPhpPath().'init.php')) {
			$babDB->db_query("UPDATE ".BAB_ADDONS_TBL." set installed='N' WHERE id=".$babDB->quote($this->id_addon));
			bab_addonsInfos::clear();
			return false;	
		}
		
		
		
		if (!bab_setAddonGlobals($this->id_addon)) {
			return false;
		}
		
		
		require_once( $this->getPhpPath().'init.php' );
		$func_name = $this->getName().'_upgrade';
		
		if (!function_exists($func_name)) {
			
			$this->setDbVersion($this->getIniVersion());
			
		} else {
			if ($this->isInstalled()) {
				$babDB->db_query("UPDATE ".BAB_ADDONS_TBL." set installed='N' WHERE id=".$babDB->quote($this->id_addon));
				bab_addonsInfos::clear();
			}
		}
		
		return true;
	}
	
	
	
	
	/**
	 * 
	 * @return	boolean
	 */
	private function setDbVersion($version) {
	
		global $babDB;
	
		$res = $babDB->db_query("
			UPDATE ".BAB_ADDONS_TBL." 
			SET 
				version=".$babDB->quote($version).",
				installed='Y' 
			WHERE 
				id=".$babDB->quote($this->id_addon)."
		");
		
		if (0 !== $babDB->db_affected_rows($res)) {
			bab_addonsInfos::clear();
			return true;
		}
		
		return false;
	}
	

	/**
	 * Get the list of tables associated to addon
	 * from db_prefix in addon ini file
	 * @return array
	 */
	public function getTablesNames() {
		
		global $babDB;
		$ini = $this->getIni();
		
		$tbllist = array();
		
		if (
			!empty($ini->inifile['db_prefix']) 
			&& mb_strlen($ini->inifile['db_prefix']) >= 3 
			&& mb_substr($ini->inifile['db_prefix'],0,3) != 'bab') {
			
			$res = $babDB->db_query("SHOW TABLES LIKE '".$babDB->db_escape_like($ini->inifile['db_prefix'])."%'");
			while(list($tbl) = $babDB->db_fetch_array($res)) {
				$tbllist[] = $tbl;
			}
		}
		
		return $tbllist;
	}
	
	
	
	/**
	 * Return the image path 
	 * a 200x150px png, jpg or gif image, representation of the addon
	 * @return string|null
	 */
	public function getImagePath() {
		$ini = $this->getIni();
		
		if (!isset($ini->inifile['image'])) {
			return null;
		}
		
		$imgpath = $this->getImagesPath().$ini->inifile['image'];
		
		if (!is_file($imgpath)) {
			return null;
		}
		
		
		return $imgpath;
	}
	

	
	/**
	 * Return the icon path 
	 * a 48x48px png, jpg or gif image, representation of the addon
	 * @return string|null
	 */
	public function getIconPath() {
		$ini = $this->getIni();
		
		switch ($this->getAddonType()) {
			case 'THEME':
				$default = $GLOBALS['babSkinPath'].'images/48x48/apps/addon-theme.png';
				break;
			case 'LIBRARY':
				$default = $GLOBALS['babSkinPath'].'images/48x48/apps/addon-library.png';
				break;
			case 'EXTENSION':
			default:
				$default = $GLOBALS['babSkinPath'].'images/48x48/apps/addon-extension.png';
				break;
		}
//		$default = $GLOBALS['babSkinPath'].'images/48x48/apps/addon-default.png';
		
		if (!isset($ini->inifile['icon'])) {
			return $default;
		}
		
		$imgpath = $this->getImagesPath().$ini->inifile['icon'];
		
		if (!is_file($imgpath)) {
			return $default;
		}
		
		
		return $imgpath;
	}
	
	
	/**
	 * Call upgrade function of addon
	 * @return boolean
	 */
	public function upgrade() {
		
		include_once $GLOBALS['babInstallPath'].'utilit/upgradeincl.php';
		
		if (!is_file($this->getPhpPath().'init.php')) {
			return false;	
		}
		
		
		if (!bab_setAddonGlobals($this->id_addon)) {
			return false;
		}
		
		$func_name = $this->getName().'_upgrade';
		require_once( $this->getPhpPath().'init.php');
		
		global $babDB;
		
		$vini 	= $this->getIniVersion();
		$vdb 	= $this->getDbVersion();
		
		if ((function_exists($func_name) && $func_name($vdb, $vini)) || !function_exists($func_name))
			{

			if ($this->setDbVersion($vini)) {

				if (empty($vdb)) {
					$from_version = '0.0';
				} else {
					$from_version = $vdb;
				}
				bab_setUpgradeLogMsg($this->getName(), sprintf('The addon has been updated from %s to %s', $from_version, $vini));
				
				// clear sitemap for addons without access rights management
				bab_siteMap::clearAll();
				return true;
			}
			
			if ($vdb === $vini) {
				return true;
			}
		}
			
		return false;
	}







	/**
	 * remove obsolete lines in tables
	 * @return bool
	 */
	private function deleteInTables() {
		global $babDB;
		include_once $GLOBALS['babInstallPath']."admin/acl.php";

		$babDB->db_query("delete from ".BAB_ADDONS_TBL." where id='".$babDB->db_escape_string($this->getId())."'");
		aclDelete(BAB_ADDONS_GROUPS_TBL, $this->getId());
		$babDB->db_query("delete from ".BAB_SECTIONS_ORDER_TBL." where id_section='".$babDB->db_escape_string($this->getId())."' and type='4'");
		$babDB->db_query("delete from ".BAB_SECTIONS_STATES_TBL." where id_section='".$babDB->db_escape_string($this->getId())."' and type='4'");

		return true;
	}









	
	
	/**
	 * Remove addon
	 * @param	string	&$msgerror
	 * @return boolean
	 */
	public function delete(&$msgerror) {
	
		global $babDB;
		include_once dirname(__FILE__).'/delincl.php';
		
		if (false === $this->isDeletable()) {
			$msgerror = bab_translate('This addon is not deletable');
			return false;
		}
		
		if ($this->getDependences()) {
			$msgerror = bab_translate('This addon has dependences from other addons');
			return false;
		}

		$ini = $this->getIni();

		if (!$ini->fileExists()) {
			return $this->deleteInTables();
		}
	
	
		if (!callSingleAddonFunction($this->getId(), $this->getName(), 'onDeleteAddon')) {
			$msgerror = $babBody->msgerror;
			return false;
		}
			
		// if addon return true, the addon is uninstalled in the table.
		$babDB->db_query("UPDATE ".BAB_ADDONS_TBL." SET installed='N' where id=".$babDB->quote($this->getId()));
		
		
		$tbllist = $this->getTablesNames();
		$addons_files_location = bab_getAddonsFilePath();
		
		$loc_in = $addons_files_location['loc_in'];	
		
		foreach($loc_in as $path) {
			if (is_dir($path.'/'.$this->getName())) {
				if (false === bab_deldir($path.'/'.$this->getName(), $msgerror)) {
					return false;
				}
			}
		}
			
		if (count($tbllist) > 0) {
			foreach($tbllist as $tbl) {
				$babDB->db_query("DROP TABLE ".$babDB->backTick($tbl));
			}
		}
		
		// si la suppression des fichiers c'est bien passee, supprimer rellement
		return $this->deleteInTables();
	}
	
	
	
	/**
	 * list of addons used by the current addon
	 * @return	array	in the key, the name of the addon, in the value a boolean for dependency satisfaction status
	 */
	public function getDependencies() {
		$ini = new bab_inifile();
		$ini->inifile($this->getPhpPath().'addonini.php');
		$addons = $ini->getAddonsRequirements();
		$return = array();
		foreach($addons as $arr) {
			$return[$arr['name']] = $arr['result'];
		}
		
		return $return;
	}
	
	
	
	/**
	 * list of addons that use the current addon
	 * @return	array	in the key, the name of the addon, in the value a boolean for dependency satisfaction status
	 */
	public function getDependences() {
		$return = array();
		foreach(bab_addonsInfos::getDbRows() as $arr) {
			$addon = bab_getAddonInfosInstance($arr['title']);
			foreach($addon->getDependencies() as $addonname => $satisfaction) {
				if ($addonname === $this->getName()) {
					$return[$addon->getName()] = $satisfaction;
				}
			}
		}
		
		return $return;
	}
	
	/**
	 * get all dependencies for addon
	 * @param	bab_OrphanRootNode	$root
	 * @param	string				$parent
	 * @return bool
	 */
	private function getRecursiveDependencies(bab_OrphanRootNode $root, $nodeId = 'root', $parent = null)
	{
		include_once $GLOBALS['babInstallPath'].'utilit/treebase.php';
		
		$node = $root->createNode($this, $nodeId);
			
		if (null === $node) {
			return false;
		}
		
		$root->appendChild($node, $parent);
		
		$dependencies = $this->getDependencies();
		foreach($dependencies as $addonname => $status)
		{
			if ($addon = bab_getAddonInfosInstance($addonname))
			{
				$childNodeId = $nodeId.'-'.$addon->getId();
				$addon->getRecursiveDependencies($root, $childNodeId, $nodeId);
			} 
			else
			{
				throw new Exception('missing addon '.$addonname);
				return false;
			}
		}
		
		return true;
	}
	
	
	private function browseRecursiveDependencies(&$stack, bab_Node $node)
	{
		$addon = $node->getData();
		
		if ($node->hasChildNodes())
		{
			$child = $node->firstChild();
			do {
				$this->browseRecursiveDependencies($stack, $child);
			} while($child = $child->nextSibling());
		}
			
		if ($addon) {
			$stack[$addon->getName()] = $addon->getName();
		}
	}
	
	
	/**
	 * Get all dependencies for addons sorted in install order
	 * the value and key in array is the addon name
	 * 
	 * @return array
	 */
	public function getSortedDependencies()
	{
		$stack = array();
		$root = new bab_OrphanRootNode;
		if ($this->getRecursiveDependencies($root)) 
		{
			$this->browseRecursiveDependencies($stack, $root);
			return $stack;
		}
		
		return array();
	}
	


	/**
	 * Test if the addon is compatible with the specified charset
	 * @param	string	$isoCharset
	 * @boolean
	 */
	public function isCharsetCompatible($isoCharset) {
		$ini = $this->getIni();
		$compatibles = array('latin1');
		if (isset($ini->inifile['mysql_character_set_database'])) {
			$compatibles = explode(',',$ini->inifile['mysql_character_set_database']);
		}

		

		foreach($compatibles as $addoncharset) {
			if ($isoCharset === bab_charset::getIsoCharsetFromDataBaseCharset(trim($addoncharset))) {
				return true;
			}
		}

		return false; 
	}
}







/**
 * Test access rights for addon
 * @param	int		$id_addon
 * @return boolean
 */
function bab_isAddonAccessValid($id_addon) {
	
	$arr = bab_addonsInfos::getRow($id_addon);
	
	if (!$arr) {
		// trigger_error(sprintf('No addon id %d',$addonid));
		return false;
	}
	
	if (false === $arr['access']) {
		return false;
	}
	
	return true;
}




/**
 * Set addons context variables
 * @param	int		$id_addon
 * @return boolean
 */
function bab_setAddonGlobals($id_addon) {
	
	if (null === $id_addon) {
		
		if (isset($GLOBALS['babAddonFolder'])) unset($GLOBALS['babAddonFolder']);
		if (isset($GLOBALS['babAddonTarget'])) unset($GLOBALS['babAddonTarget']);
		if (isset($GLOBALS['babAddonUrl'])) unset($GLOBALS['babAddonUrl']);
		if (isset($GLOBALS['babAddonPhpPath'])) unset($GLOBALS['babAddonPhpPath']);
		if (isset($GLOBALS['babAddonHtmlPath'])) unset($GLOBALS['babAddonHtmlPath']);
		if (isset($GLOBALS['babAddonUpload'])) unset($GLOBALS['babAddonUpload']);
		
		return true;
	}
	
	$arr = bab_addonsInfos::getDbRow($id_addon); 
	
	if (!$arr) {
		
		
		
		return false;
	}
	
	$GLOBALS['babAddonFolder'] = $arr['title'];
	$GLOBALS['babAddonTarget'] = 'addon/'.$id_addon;
	$GLOBALS['babAddonUrl'] = $GLOBALS['babUrlScript'].'?tg=addon/'.$id_addon.'/';
	$GLOBALS['babAddonPhpPath'] = $GLOBALS['babInstallPath'].'addons/'.$arr['title'].'/';
	$GLOBALS['babAddonHtmlPath'] = 'addons/'.$arr['title'].'/';

	if (isset($GLOBALS['babUploadPath'])) {
		$GLOBALS['babAddonUpload'] = $GLOBALS['babUploadPath'].'/addons/'.$arr['title'].'/';

	} else {

		// in some cases, babUploadPath is not defined
		
		global $babDB;
		
		$req="SELECT uploadpath from ".BAB_SITES_TBL." where name='".$babDB->db_escape_string($GLOBALS['babSiteName'])."'";
		$res = $babDB->db_query($req);
		$row = $babDB->db_fetch_assoc($res);

		$GLOBALS['babAddonUpload'] = $row['uploadpath'].'/addons/'.$arr['title'].'/';
	}
	
	return true;
}




/**
 * Calls a function defined in init.php for each addon.
 * 
 * For each addon, the string $func will be prefixed by the addon name and an underscore
 * if this function is defined in the addon's init.php, it will be called with
 * all the additional parameters passed to bab_callAddonsFunction.
 
 * @param	string	$func
 * @return 	array
 */
function bab_callAddonsFunction($func)
{
	$results = array();
	
	
	$oldBabAddonFolder = isset($GLOBALS['babAddonFolder'])? $GLOBALS['babAddonFolder']: '';
	$oldBabAddonTarget = isset($GLOBALS['babAddonTarget'])? $GLOBALS['babAddonTarget']: '';
	$oldBabAddonUrl =  isset($GLOBALS['babAddonUrl'])? $GLOBALS['babAddonUrl']: '';
	$oldBabAddonPhpPath =  isset($GLOBALS['babAddonPhpPath'])? $GLOBALS['babAddonPhpPath']: '';
	$oldBabAddonHtmlPath =  isset($GLOBALS['babAddonHtmlPath'])? $GLOBALS['babAddonHtmlPath']: '';
	$oldBabAddonUpload =  isset($GLOBALS['babAddonUpload'])? $GLOBALS['babAddonUpload']: '';

	
	$addons = bab_addonsInfos::getRows();
	
	foreach($addons as $key => $row)
		{ 
		if($row['access'])
			{
			$addonpath = $GLOBALS['babAddonsPath'].$row['title'];
			if( is_file($addonpath.'/init.php' ))
				{
				bab_setAddonGlobals($row['id']);
				
				require_once( $addonpath.'/init.php' );
				$call = $row['title'].'_'.$func;
				if( !empty($call)  && function_exists($call) )
					{
					$args = func_get_args();
					$call .= '(';
					for($k=1; $k < sizeof($args); $k++) {
						eval ( "\$call .= \"$args[$k],\";");
					}
					
					if (',' === mb_substr($call, -1)) {
						$call = mb_substr($call, 0, -1);
					}
					$call .= ')';
					
					eval ( "\$retval = $call;");
					
						$results[$row['id']] = array(
							'addon_name' => $row['title'],
							'return_value' => $retval
						);
					}
				}
			}
		}

	$GLOBALS['babAddonFolder'] = $oldBabAddonFolder;
	$GLOBALS['babAddonTarget'] = $oldBabAddonTarget;
	$GLOBALS['babAddonUrl'] = $oldBabAddonUrl;
	$GLOBALS['babAddonPhpPath'] = $oldBabAddonPhpPath;
	$GLOBALS['babAddonHtmlPath'] = $oldBabAddonHtmlPath;
	$GLOBALS['babAddonUpload'] = $oldBabAddonUpload;
	
	
	return $results;
}




/**
 * Call addon function
 * @param	string	$func
 * @param	array	$args
 */
function bab_callAddonsFunctionArray($func, $args)
{
	$oldBabAddonFolder = isset($GLOBALS['babAddonFolder'])? $GLOBALS['babAddonFolder']: '';
	$oldBabAddonTarget = isset($GLOBALS['babAddonTarget'])? $GLOBALS['babAddonTarget']: '';
	$oldBabAddonUrl =  isset($GLOBALS['babAddonUrl'])? $GLOBALS['babAddonUrl']: '';
	$oldBabAddonPhpPath =  isset($GLOBALS['babAddonPhpPath'])? $GLOBALS['babAddonPhpPath']: '';
	$oldBabAddonHtmlPath =  isset($GLOBALS['babAddonHtmlPath'])? $GLOBALS['babAddonHtmlPath']: '';
	$oldBabAddonUpload =  isset($GLOBALS['babAddonUpload'])? $GLOBALS['babAddonUpload']: '';

	$addons = bab_addonsInfos::getRows();
	
	foreach($addons as $key => $row)
		{ 
		$addonpath = $GLOBALS['babAddonsPath'].$row['title'];
		if( is_file($addonpath.'/init.php' ))
			{
			bab_setAddonGlobals($row['id']);
			require_once( $addonpath.'/init.php' );
			$call = $row['title'].'_'.$func;
			if( function_exists($call) )
				{
				$call($args);
				}
			}
		}

	$GLOBALS['babAddonFolder'] = $oldBabAddonFolder;
	$GLOBALS['babAddonTarget'] = $oldBabAddonTarget;
	$GLOBALS['babAddonUrl'] = $oldBabAddonUrl;
	$GLOBALS['babAddonPhpPath'] = $oldBabAddonPhpPath;
	$GLOBALS['babAddonHtmlPath'] = $oldBabAddonHtmlPath;
	$GLOBALS['babAddonUpload'] = $oldBabAddonUpload;
}






function bab_getAddonsFilePath() {
	
	
	return array(
	
	'loc_in' => array(
				$GLOBALS['babInstallPath'].'addons',
				$GLOBALS['babInstallPath'].'lang/addons',
				$GLOBALS['babInstallPath'].'styles/addons',
				$GLOBALS['babInstallPath'].'skins/ovidentia/templates/addons',
				$GLOBALS['babInstallPath'].'skins/ovidentia/ovml/addons',
				$GLOBALS['babInstallPath'].'skins/ovidentia/images/addons',
				'skins'
				),	

	'loc_out' => array(
				"programs",
				"langfiles",
				"styles",
				"skins/ovidentia/templates",
				"skins/ovidentia/ovml",
				"skins/ovidentia/images",
				'theme'
				)
	);

}


?>
