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



/**
 * Skin object
 *
 */
class bab_skin {

	const SKINS_PATH = 'skins/';
	const STYLES_PATH = 'styles/';
	const OVML_PATH = 'ovml/';
	private static $skins = array();
	private $skinname = null;
	
	/**
	 * Get a skin with or without access rights verification
	 * @param   string $skinname
	 * @param	boolean	$access_verification
	 * @return bab_skin, NULL
	 */
	public static function get($skinname, $access_verification = true) {
		$return = NULL;
		foreach(self::getAllSkins() as $skin) {
			if ((false === $access_verification || $skin->isAccessValid()) && $skin->skinname === $skinname) {
				$return = $skin;
			}
		}
		
		return $return;
	}
	
	/**
	 * Apply a skin to the current page (no modification in database)
	 * @param   string $skinname
	 * @param   string $stylesname
	 */
	public static function applyOnCurrentPage($skinname, $stylesname) {
		global $babSkin, $babStyle, $babCssPath, $babOvmlPath;
		$babSkin = $skinname;
		$babStyle = $stylesname;
		$babCssPath = self::SKINS_PATH . $babSkin . '/' . self::STYLES_PATH . $babStyle;
		$babOvmlPath = self::SKINS_PATH . $babSkin . '/' . self::OVML_PATH;
	}
	
	/**
	 * Get the list of available skins with or without access rights verification
	 * @param	boolean	$access_verification
	 * @return array
	 */
	public static function getList($access_verification = true) {
		$return = array();
		foreach(self::getAllSkins() as $skin) {
			
			if (false === $access_verification || $skin->isAccessValid()) {
				$return[] = $skin;
			}
		}

		return $return;
	}



	/**
	 * Return all skins without access right verification based on skins folder
	 * This method return an array with at least one element
	 * @return array
	 */
	private static function getAllSkins() {

		if (self::$skins) {
			return self::$skins;
		}


		if (!is_dir(self::SKINS_PATH)) {
			self::$skins[] = 'ovidentia';
			return self::$skins;
		}


		$h = opendir(self::SKINS_PATH); 
		while ( $file = readdir($h))
			{ 
			if ($file != "." && $file != "..")
				{
				if( is_dir("skins/".$file))
					{
					self::$skins[] = new bab_skin($file); 
					}
				} 
			}
		closedir($h);


		if (empty(self::$skins)) {
			self::$skins[] = new bab_skin('ovidentia');
		}
		
		return self::$skins;
	}


	/**
	 * Return all ignored skins
	 * @return array
	 */
	public static function getNotAccessibles() {
		$all = self::getAllSkins();
		$accessibles = self::getList();

		return array_diff($all, $accessibles);
	}



	/**
	 * Get a working skin or ovidentia default if no skin available
	 * @return bab_skin 
	 */
	public static function getDefaultSkin() {
		
		if (isset($GLOBALS['babSiteName'])) {
			
			global $babDB;
			
			$res = $babDB->db_query('SELECT skin FROM bab_sites WHERE name='.$babDB->quote($GLOBALS['babSiteName']));
			if ($arr = $babDB->db_fetch_assoc($res)) {
		
				// if site skin is accessible use it
				if (null !== $skin = self::get($arr['skin'])) {
					return $skin;
				}
			}
		}
		
		// if ovidentia is accessible use it
		if (null !== $skin = self::get('ovidentia')) {
			return $skin;
		}

		$accessibles = self::getList();

		// if no accessibles skins, use ovidentia anyway
		if (empty($accessibles)) {
			return new bab_skin('ovidentia');
		}

		// use the first accessible skin
		return reset($accessibles);
	}




	public function __construct($skinname) {
		$this->skinname = $skinname;
	}


	/**
	 * test access rights on skin, 
	 * if the skin is an addon, access rights of addon are checked
	 *
	 * @return boolean
	 */
	public function isAccessValid() {
		
		if (!file_exists('skins/'.$this->skinname)) {
			return false;
		}
		
		
		$charset = bab_charset::getDatabase();
		$addon = bab_getAddonInfosInstance($this->skinname);

		if (false === $addon) {
			if ('latin1' === $charset) {
				return true;
			} else {
				bab_debug(bab_sprintf('The skin "%s" is not accessible, since ovidentia is in UTF-8, all skins must be embeded in addons',$this->skinname));
				return false;
			}
		}
		
		try {

			if ('THEME' === $addon->getAddonType()) {
				return $addon->isValid() && $addon->isAccessValid();
			}
			
		} catch(Exception $e) {
			bab_debug(bab_sprintf("Skin %s is not accessible\n ", $this->skinname).$e->getMessage());
		}

		return false;
	}

	/**
	 * Get skin name (folder name)
	 * @return string
	 */
	public function getName() {
		return $this->skinname;
	}


	/**
	 * Get skin description
	 * @return string
	 */
	public function getDescription() {
		$addon = bab_getAddonInfosInstance($this->skinname);

		if (false === $addon) {
			return '';
		}
		
		return $addon->getDescription();
	}



	private function getStylesFromPath($path) {

		$arrstyles = array();

		if( is_dir($path))
			{
			$h = opendir($path); 
			while ( $file = readdir($h))
				{ 
				if ($file != '.' && $file != '..')
					{
					if( is_file($path.$file))
						{
						$iOffset = mb_strpos($file, '.');
						if(false !== $iOffset)
							{
							if( mb_strtolower(mb_substr($file, $iOffset+1)) == 'css' )
								{
								$arrstyles[$file] = $file;
							}
						}
					}
				} 
			}
			closedir($h);
		}

		return $arrstyles;
	}


	/**
	 * Get list of css styles
	 * @return array
	 */
	public function getStyles() {
		$arrstyles = array();
		
		$arrstyles += $this->getStylesFromPath(self::SKINS_PATH.$this->skinname.'/'.self::STYLES_PATH);
		$arrstyles += $this->getStylesFromPath($GLOBALS['babInstallPath'].self::SKINS_PATH.$this->skinname.'/'.self::STYLES_PATH);

		return $arrstyles;
	}


	public function __tostring() {
		return $this->getName();
	}
}
