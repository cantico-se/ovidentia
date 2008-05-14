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

	var $indexById 		= array();
	var $indexByName 	= array();

	/**
	 * Create indexes
	 * @return boolean
	 */
	function createIndex() {
		
	
		if (!$this->indexById || !$this->indexByName) {
		
			global $babDB;
	
			$res = $babDB->db_query("select * from ".BAB_ADDONS_TBL." where enabled='Y' AND installed='Y'");
			while( $arr = $babDB->db_fetch_array($res)) {
			
				$arr_ini = @parse_ini_file( $GLOBALS['babAddonsPath'].$arr['title'].'/addonini.php');
				$access_control = isset($arr_ini['addon_access_control']) ? (int) $arr_ini['addon_access_control'] : 1;
			
				$arr['access'] = false;
				if (0 === $access_control || bab_isAccessValid(BAB_ADDONS_GROUPS_TBL, $arr['id']))
					{
					if( !empty($arr_ini['version']))
						{
						if ($arr_ini['version'] == $arr['version']) {
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
	 * Get all addons indexed by id
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
	 * Get addon row if exist
	 * @static
	 * @param	int	$id_addon
	 * @return	false|array
	 */
	function getDbRow($id_addon) {
	
		$arr = bab_addonsInfos::getRows();
		if (isset($arr[$id_addon])) {
		
			return $arr[$id_addon];
		
		} else {
		
			global $babDB;
			
			$res = $babDB->db_query("SELECT * FROM ".BAB_ADDONS_TBL." WHERE id=".$babDB->quote($id_addon));
			$arr = $babDB->db_fetch_assoc($res);
			
			return $arr;
		}
		
		return false;
	}
	
	
	
	/**
	 * Clear cache for addons
	 * @static
	 */
	function clear() {
		global $babBody;
	
		$babBody->babaddons = array();
		
		$obj = bab_getInstance('bab_addonsInfos');
		
		$obj->indexById 	= array();
		$obj->indexByName 	= array();
	}
	
	
	
	/**
	 * Get addon id by name
	 * @static
	 * @return int|false
	 */
	function getAddonIdByName($name) {
		$obj = bab_getInstance('bab_addonsInfos');
		$obj->createIndex();
		
		if (!isset($obj->indexByName[$name])) {
			return false;
		}
		
		return (int) $obj->indexByName[$name]['id'];
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
	 * Set addon Name
	 * This function verifiy if the addon is accessible
	 * define $this->id_addon and $this->addonname
	 * @return boolean
	 */
	function setAddonName($addonname) {
		
		$id_addon = bab_addonsInfos::getAddonIdByName($addonname);
		
		if (false === $id_addon) {
			return false;
		}
			
		if (!bab_isAddonAccessValid($id_addon)) {
			return false;
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
		return $GLOBALS['babUrlScript'].'?tg=addon/'.$this->id_addon.'/';
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
		return $GLOBALS['babInstallPath'].'/'.$this->getRelativePath();
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
		return $GLOBALS['babInstallPath'].'/skins/ovidentia/templates/'.$this->getRelativePath();
	}
	
	
	/**
	 * Get path to images directory
	 * @return string
	 */
	function getImagesPath() {
		return $GLOBALS['babInstallPath'].'/skins/ovidentia/images/'.$this->getRelativePath();
	}
	
	
	/**
	 * Get path to ovml directory
	 * @return string
	 */
	function getOvmlPath() {
		return $GLOBALS['babInstallPath'].'/skins/ovidentia/ovml/'.$this->getRelativePath();
	}
	
	
	/**
	 * Get path to css stylesheets directory
	 * @return string
	 */
	function getStylePath() {
		return $GLOBALS['babInstallPath'].'/styles/'.$this->getRelativePath();
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
					
					if (',' === substr($call, -1)) {
						$call = substr($call, 0, -1);
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