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
include_once 'base.php';





/**
 * Manage addons informations for multiples addons
 */
class bab_addonsInfos {

	var $indexById 			= array();
	var $indexByName 		= array();
	var $fullIndexById		= array();
	var $fullIndexByName	= array();

	/**
	 * Create indexes with access rights verification
	 * @return boolean
	 */
	function createIndex() {
		
	
		if (!$this->indexById || !$this->indexByName) {
		
			include_once $GLOBALS['babInstallPath'].'utilit/inifileincl.php';
			global $babDB;
	
			$res = $babDB->db_query("select * from ".BAB_ADDONS_TBL." where enabled='Y' AND installed='Y'");
			while( $arr = $babDB->db_fetch_array($res)) {
				
				$ini = new bab_inifile();
				$ini->inifileGeneral($GLOBALS['babAddonsPath'].$arr['title'].'/addonini.php');
				$arr_ini = $ini->inifile;

				$access_control = isset($arr_ini['addon_access_control']) ? (int) $arr_ini['addon_access_control'] : 1;
			
				$arr['access'] = false;
				if (0 === $access_control || bab_isAccessValid(BAB_ADDONS_GROUPS_TBL, $arr['id']))
					{
					if($ini->getVersion())
						{
						if ($ini->getVersion() == $arr['version']) {
							$arr['access'] = true;
							}
						else {
							$babDB->db_query("UPDATE ".BAB_ADDONS_TBL." SET installed='N' WHERE id='".$babDB->db_escape_string($arr['id'])."'");
							}
						}
					}
					
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
	function createFullIndex() {
	
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
	 * @static
	 * @return array
	 */
	function getRows() {
	
		$obj = bab_getInstance('bab_addonsInfos');
		$obj->createIndex();
		
		return $obj->indexById;
	}
	
	
	/**
	 * Get addon row from installed and enabled addons list
	 * @static
	 * @param	int	$id_addon
	 * @return	false|array
	 */
	function getRow($id_addon) {
		

		$arr = bab_addonsInfos::getRows();

		if (!isset($arr[$id_addon])) {
			// include_once $GLOBALS['babInstallPath'].'utilit/devtools.php';
			// bab_debug_print_backtrace();
			// trigger_error(sprintf('No addon id %d',$id_addon));
			return false;
		}
		
		return $arr[$id_addon];
	}
	
	
	/**
	 * Get all addons indexed by id
	 * @static
	 * @return array
	 */
	function getDbRows() {
	
		$obj = bab_getInstance('bab_addonsInfos');
		$obj->createFullIndex();
		
		return $obj->fullIndexById;
	}
	
	
	/**
	 * Get all addons indexed by name
	 * @static
	 * @return array
	 */
	function getDbRowsByName() {
		$obj = bab_getInstance('bab_addonsInfos');
		$obj->createFullIndex();
		
		return $obj->fullIndexByName;
	}

	
	/**
	 * Get addon row if exist, from all addons in table
	 * @static
	 * @param	int	$id_addon
	 * @return	false|array
	 */
	function getDbRow($id_addon) {
	
		$arr = bab_addonsInfos::getDbRows();
		
		if (!isset($arr[$id_addon])) {
			return false;
		}
		
		return $arr[$id_addon];
	}
	
	
	
	/**
	 * Clear cache for addons
	 * @static
	 */
	function clear() {
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
	 * @static
	 *
	 * @param	string	$name
	 * @param	boolean	$access_rights
	 *
	 * @return int|false
	 */
	function getAddonIdByName($name, $access_rights = true) {
		
		
		$obj = bab_getInstance('bab_addonsInfos');
		
		if ($access_rights) {
			$obj->createIndex();
			
			if (!isset($obj->indexByName[$name])) {
				return false;
			}
			
			return (int) $obj->indexByName[$name]['id'];
		} else {
		
			$obj->createFullIndex();
			
			if (!isset($obj->fullIndexByName[$name])) {
				return false;
			}
			
			return (int) $obj->fullIndexByName[$name]['id'];
		
		}
	}
	
	
	
	
	
	
	/**
	 * Browse addons folder and add missing addons to bab_addons
	 * @static
	 */
	function insertMissingAddonsInTable() {
	
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
	
	
	/**
	 * Browse addons table and remove obsolete lines
	 * @static
	 */
	function deleteObsoleteAddonsInTable() {
		global $babDB;
		include_once $GLOBALS['babInstallPath']."admin/acl.php";

		$res = $babDB->db_query("select * from ".BAB_ADDONS_TBL."");
		
		while($row = $babDB->db_fetch_array($res)) {
		
			if (!is_dir($GLOBALS['babAddonsPath'].$row['title']) || !is_file($GLOBALS['babAddonsPath'].$row['title']."/init.php")) {
				
				$babDB->db_query("delete from ".BAB_ADDONS_TBL." where id='".$babDB->db_escape_string($row['id'])."'");
				aclDelete(BAB_ADDONS_GROUPS_TBL, $row['id']);
				$babDB->db_query("delete from ".BAB_SECTIONS_ORDER_TBL." where id_section='".$babDB->db_escape_string($row['id'])."' and type='4'");
				$babDB->db_query("delete from ".BAB_SECTIONS_STATES_TBL." where id_section='".$babDB->db_escape_string($row['id'])."' and type='4'");
			}
		}
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
	function setAddonName($addonname, $access_rights = true) {
		
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
	function getName() {
		return $this->addonname;
	}
	
	/**
	 * get the addon ID
	 * @return int
	 */
	function getId() {
		return (int) $this->id_addon;
	}
	
	
	/**
	 * a replacement for $babAddonTarget
	 * @return string
	 */
	function getTarget() {
		return "addon/".$this->id_addon;
	}
	
	/**
	 * a replacement for $babAddonUrl
	 * @return string
	 */
	function getUrl() {
		return $GLOBALS['babUrlScript'].'?tg=addon%2F'.$this->id_addon.'%2F';
	}

	/**
	 * 
	 * a replacement for $babAddonHtmlPath
	 * @return string
	 */
	function getRelativePath() {
		return 'addons/'.$this->addonname.'/';
	}
	
	/**
	 * a replacement for $babAddonPhpPath
	 * @return string
	 */
	function getPhpPath() {
		return $GLOBALS['babInstallPath'].$this->getRelativePath();
	}
	
	/**
	 * Get the addon upload path
	 * a replacement for $babAddonUpload
	 * @return string
	 */
	function getUploadPath() {
		return $GLOBALS['babUploadPath'].'/'.$this->getRelativePath();
	}
	
	/**
	 * Get path to template directory
	 * @return string
	 */
	function getTemplatePath() {
		return $GLOBALS['babInstallPath'].'skins/ovidentia/templates/'.$this->getRelativePath();
	}
	
	
	/**
	 * Get path to images directory
	 * @return string
	 */
	function getImagesPath() {
		return $GLOBALS['babInstallPath'].'skins/ovidentia/images/'.$this->getRelativePath();
	}
	
	
	/**
	 * Get path to ovml directory
	 * @return string
	 */
	function getOvmlPath() {
		return $GLOBALS['babInstallPath'].'skins/ovidentia/ovml/'.$this->getRelativePath();
	}
	
	
	/**
	 * Get path to css stylesheets directory
	 * @return string
	 */
	function getStylePath() {
		return $GLOBALS['babInstallPath'].'styles/'.$this->getRelativePath();
	}
	
	
	/**
	 * get INI object, general section only
	 * @access private
	 */
	function getIni() {
		if (null === $this->ini) {
	
			$this->ini = new bab_inifile();
			$this->ini->inifileGeneral($this->getPhpPath().'addonini.php');
		}
		
		return $this->ini;
	}
	
	
	/**
	 * Check validity of addon INI file requirements
	 * @return boolean
	 */
	function isValid() {
		include_once $GLOBALS['babInstallPath'].'utilit/inifileincl.php';
		$ini = new bab_inifile();
		$ini->inifile($this->getPhpPath().'addonini.php');
		return $ini->isValid();
	}
	
	
	
	/**
	 * addon has global access control 
	 * @return boolean
	 */
	function hasAccessControl() {
		$ini = $this->getIni();
		return !isset($ini->inifile['addon_access_control']) || 
			(isset($ini->inifile['addon_access_control']) && 1 === (int) $ini->inifile['addon_access_control']);
	}
	
	
	
	
	
	/**
	 * Get the type of addon.
	 * The addon type can be EXTENSION | LIBRARY | THEME
	 * 
	 * @return string
	 */
	function getAddonType() {
	
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
	function isDeletable() {
		$ini = $this->getIni();
		return isset($ini->inifile['delete']) && 1 === (int) $ini->inifile['delete'];
	}
	
	/**
	 * Test if addon is accessible
	 * if access control, and addons access rights verification return false, addon is not accessible
	 * if addons is disabled, the addons is not accessible
	 * if addon is not installed, addon is not accessible
	 * @return boolean
	 */
	function isAccessValid() {
		if (bab_isAddonAccessValid($this->id_addon)) {
			return true;
		}
		
		return false;
	}
	
	/**
	 * is addon installed by administrator
	 * @return boolean
	 */
	function isInstalled() {
		$arr = bab_addonsInfos::getDbRow($this->id_addon);
		return 'Y' === $arr['installed'];
	}
	
	
	/**
	 * is addon disabled by administrator
	 * @return boolean
	 */
	function isDisabled() {
	
		$arr = bab_addonsInfos::getDbRow($this->id_addon);
		return 'N' === $arr['enabled'];
	}
	
	/**
	 * Disable addon
	 * @return bab_addonInfos
	 */
	function disable() {
		global $babDB;
		$babDB->db_query("UPDATE ".BAB_ADDONS_TBL." set enabled='N' WHERE id=".$babDB->quote($this->id_addon));
		bab_addonsInfos::clear();
		return $this;
	}
	
	
	/**
	 * Enable addon
	 * @return bab_addonInfos
	 */
	function enable() {
		global $babDB;
		$babDB->db_query("UPDATE ".BAB_ADDONS_TBL." set enabled='Y' WHERE id=".$babDB->quote($this->id_addon));
		bab_addonsInfos::clear();
		return $this;
	}
	
	
	/**
	 * Get version from ini file
	 * @return string
	 */
	function getIniVersion() {
	
		$ini = $this->getIni();
		return $ini->getVersion();
	}
	
	
	/**
	 * Get description from ini file
	 * @return string
	 */
	function getDescription() {
		
		$ini = $this->getIni();
		return $ini->getDescription();
	}
	
	
	
	
	
	
	
	/**
	 * get version from database
	 * @return string
	 */
	function getDbVersion() {
		$arr = bab_addonsInfos::getDbRow($this->id_addon);
		return $arr['version'];
	}
	
	/**
	 * Test if the addon need an upgrade of the database
	 */
	function isUpgradable() {
	
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
	function updateInstallStatus() {
	
		if (!$this->isUpgradable()) {
			return false;
		}
	
		global $babDB;

		
		
		if (!is_file($this->getPhpPath().'init.php')) {
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
	 * @access private
	 * @return	boolean
	 */
	function setDbVersion($version) {
	
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
	function getTablesNames() {
		
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
	function getImagePath() {
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
	function getIconPath() {
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
	 * Return the image path 
	 * a 200x150px png, jpg or gif image, representation of the addon
	 * @return string|null
	 */
	function getImagePath() {
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
	function getIconPath() {
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
	function upgrade() {

		if (!$this->isValid()) {
			return false;
		}
		
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
	 * Remove addon
	 * @param	string	&$msgerror
	 * @return boolean
	 */
	function delete(&$msgerror) {
	
		global $babDB;
	
		
		if (false === $this->isDeletable()) {
			$msgerror = bab_translate('This addon is not deletable');
			return false;
		}
		
		if ($this->getDependences()) {
			$msgerror = bab_translate('This addon has dependences from other addons');
			return false;
		}
	
	
		if (!callSingleAddonFunction($this->getId(), $this->getName(), 'onDeleteAddon')) {
			$msgerror = $babBody->msgerror;
			return false;
		}
			
		// if addon return true, the addon is uninstalled in the table.
		$babDB->db_query("UPDATE ".BAB_ADDONS_TBL." SET installed='N' where id=".$babDB->quote($this->getId()));
		
		
		$tbllist = $this->getTablesNames();

		
		if (!function_exists('bab_deldir')) {
			
			function bab_deldir($dir, &$msgerror) {
				$current_dir = opendir($dir);
				while($entryname = readdir($current_dir)){
					if(is_dir("$dir/$entryname") and ($entryname != "." and $entryname!="..")){
						if (false === bab_deldir($dir.'/'.$entryname, $msgerror)) {
							return false;
						}
					} elseif ($entryname != "." and $entryname!="..") {
						if (false === unlink($dir.'/'.$entryname)) {
							$msgerror = sprintf(bab_translate('The addon file is not deletable : %s'), $dir.'/'.$entryname);
							return false;
						}
					}
				}
				closedir($current_dir);
				rmdir($dir);
				return true;
			}
		}
		
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
		
		return true;
	}
	
	
	
	/**
	 * list of addons used by the current addon
	 * @return	array	in the key, the name of the addon, in the value a boolean for dependency satisfaction status
	 */
	function getDependencies() {
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
	function getDependences() {
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
	
	$arr = bab_addonsInfos::getDbRow($id_addon); 
	
	if (!$arr) {
		return false;
	}
	
	$GLOBALS['babAddonFolder'] = $arr['title'];
	$GLOBALS['babAddonTarget'] = 'addon/'.$id_addon;
	$GLOBALS['babAddonUrl'] = $GLOBALS['babUrlScript'].'?tg=addon/'.$id_addon.'/';
	$GLOBALS['babAddonPhpPath'] = $GLOBALS['babInstallPath'].'addons/'.$arr['title'].'/';
	$GLOBALS['babAddonHtmlPath'] = 'addons/'.$arr['title'].'/';
	$GLOBALS['babAddonUpload'] = $GLOBALS['babUploadPath'].'/addons/'.$arr['title'].'/';
	
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