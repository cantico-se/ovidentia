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
require_once dirname(__FILE__).'/addonapi.php';
require_once dirname(__FILE__).'/eventaddon.php';
require_once dirname(__FILE__).'/addoninfos.class.php';


/**
 * Manage addons informations for multiples addons
 */
class bab_addonsInfos {

	private $indexById 			= array();
	private $indexByName 		= array();
	private $fullIndexById		= array();
	private $fullIndexByName	= array();

	private static $instance = null;


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
		
		$standard = new bab_AddonStandardLocation($title);
		if (file_exists($standard->getIniFilePath())) {
		    $ini->inifileGeneral($standard->getIniFilePath());
		} else {
		    $core = new bab_AddonInCoreLocation($title);
		    $ini->inifileGeneral($core->getIniFilePath());
		}
		$arr_ini = $ini->inifile;

		$access_control = isset($arr_ini['addon_access_control']) ? (int) $arr_ini['addon_access_control'] : 1;
	
		$access = false;
		if (0 === $access_control || bab_isAccessValid('bab_addons_groups', $id_addon)) {
			if($ini->getVersion()) {
				if ($ini->getVersion() == $version) {
					$access = true;
				}
				else {
					$babDB->db_query("UPDATE bab_addons SET installed='N' WHERE id='".$babDB->db_escape_string($id_addon)."'");
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
				$name = mb_strtolower($arr['title']);
					
				$this->indexById[$arr['id']] = $arr;
				$this->indexByName[$name] 	 = $arr;
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
	
			$res = $babDB->db_query("select * from bab_addons");
			while( $arr = $babDB->db_fetch_array($res)) {
			    
			    $name = mb_strtolower($arr['title']);
					
				$this->fullIndexById[$arr['id']] = $arr;
				$this->fullIndexByName[$name] 	 = $arr;
			}
		}
		
		
		return !empty($this->fullIndexById);
	}
	
	
	private static function getInstance()
	{
		return bab_getInstance('bab_addonsInfos');
	}



	/**
	 * Get available addons indexed by id
	 * since $babBody->babaddons is deprecated, this method has the same result
	 * @return array
	 */
	public static function getRows() {
	
		$obj = self::getInstance();
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
		/*@var $obj bab_addonsInfos */
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
			
			$obj = new bab_addonInfos();
			if (false !== $obj->setAddonName($row['title'], false)) {
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
		
		$obj = bab_getInstance('bab_addonsInfos');
		
		$obj->indexById 		= array();
		$obj->indexByName 		= array();
		$obj->fullIndexById 	= array();
		$obj->fullIndexByName 	= array();
	}
	
	
	
	/**
	 * Get addon id by name (case insensitive)
	 *
	 * @param	string	$name
	 * @param	boolean	$access_rights
	 *
	 * @return int|false
	 */
	public static function getAddonIdByName($name, $access_rights = true) {
		
		require_once dirname(__FILE__).'/addonapi.php';
		
		$obj = bab_getInstance('bab_addonsInfos');
		$obj->createFullIndex();
		
		$name = mb_strtolower($name);
		
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
	public static function insertMissingAddonsInTable()
	{

		foreach(bab_AddonInCoreLocation::getList() as $addonName) {
		    self::insertAddon($addonName);
		}

		foreach(bab_AddonStandardLocation::getList() as $addonName) {
		    self::insertAddon($addonName);
		}
	}
	
	
	private static function insertAddon($title)
	{
	    global $babDB;
	    $res = $babDB->db_query("SELECT * FROM ".BAB_ADDONS_TBL."
	            where title='".$babDB->db_escape_string($title)."' ORDER BY title ASC");
	    if( $res && $babDB->db_num_rows($res) < 1)
	    {
	        $babDB->db_query("
	                INSERT INTO ".BAB_ADDONS_TBL." (title, enabled)
	                VALUES ('".$babDB->db_escape_string($title)."', 'Y')
	                ");
	    }
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
 * Set or unset addons context variables
 * @param	int	| null	$id_addon
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
		
		trigger_error('Failed to load addon row for id:'.$id_addon);
		return false;
	}
	
	$GLOBALS['babAddonFolder'] = $arr['title'];
	$GLOBALS['babAddonTarget'] = 'addon/'.$id_addon;
	$GLOBALS['babAddonUrl'] = $GLOBALS['babUrl'].bab_getSelf().'?tg=addon/'.$id_addon.'/';
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
			$addonpath = $GLOBALS['babInstallPath'].'addons/'.$row['title'];
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
						
						if (is_string($args[$k]))
						{
							$args[$k] = "'".$args[$k]."'";
						}
						
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
		$addonpath = $GLOBALS['babInstallPath'].'addons/'.$row['title'];
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





/**
 * Path corespondance for addon location "in core"
 * @see bab_AddonInCoreLocation
 * 
 * @return array
 */
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
