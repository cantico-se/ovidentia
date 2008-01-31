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
 * Manage addons informations
 */
class bab_addonsInfos {

	/**
	 * @return array
	 */
	function getRows() {
	
		global $babBody, $babDB;
	
		if (!$babBody->babaddons) {
	
			$res = $babDB->db_query("select * from ".BAB_ADDONS_TBL." where enabled='Y' AND installed='Y'");
			while( $arr = $babDB->db_fetch_array($res)) {
				$arr['access'] = false;
				if (bab_isAccessValid(BAB_ADDONS_GROUPS_TBL, $arr['id']))
					{
					$arr_ini = @parse_ini_file( $GLOBALS['babAddonsPath'].$arr['title'].'/addonini.php');
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
				$babBody->babaddons[$arr['id']] = $arr;
			}
		}
		
		return $babBody->babaddons;
		
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
	 */
	function clear() {
		global $babBody;
	
		$babBody->babaddons = array();
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
	$GLOBALS['babAddonTarget'] = "addon/".$id_addon;
	$GLOBALS['babAddonUrl'] = $GLOBALS['babUrlScript']."?tg=addon/".$id_addon."/";
	$GLOBALS['babAddonPhpPath'] = $GLOBALS['babInstallPath']."addons/".$arr['title']."/";
	$GLOBALS['babAddonHtmlPath'] = "addons/".$arr['title']."/";
	$GLOBALS['babAddonUpload'] = $GLOBALS['babUploadPath']."/addons/".$arr['title']."/";
	
	return true;
}




/**
 * Calls a function defined in init.php for each addon.
 * 
 * For each addon, the string $func will be prefixed by the addon name and an underscore
 * if this function is defined in the addon's init.php, it will be called with
 * all the additional parameters passed to bab_callAddonsFunction.
 
 * @param	string	$func
 */
function bab_callAddonsFunction($func)
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
					for($k=1; $k < sizeof($args); $k++)
						eval ( "\$call .= \"$args[$k],\";");
					$call = substr($call, 0, -1);
					$call .= ')';
					eval ( "\$retval = $call;");
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