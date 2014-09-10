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
 * Give methods to find all the addon files
 * 
 */
abstract class bab_AddonLocation
{
    /**
     * @var string
     */
    protected $addonName;
    
    /**
     * @param string $addonName
     */
    public function __construct($addonName)
    {
        $this->addonName = $addonName;
    }
    
    /**
     * get the list of available addon names in this location
     * @return array
     */
    abstract public static function getList();
    
    /**
     * Path to php source files
     * relative to the ovidentia root folder (config.php)
     * with a terminal slash
     * 
     * @return string
     */
    abstract public function getPhpPath();
    
    /**
     * Get path to template directory
     * relative to the ovidentia root folder (config.php)
     * with a terminal slash
     * 
     * @return string
     */
    abstract public function getTemplatePath();
    
    /**
     * Get path to images directory
     * relative to the ovidentia root folder (config.php)
     * with a terminal slash
     * 
     * @return string
     */
    abstract public function getImagesPath();
    
    
    
    /**
     * Get path to ovml directory
     * relative to the ovidentia root folder (config.php)
     * with a terminal slash
     * 
     * @return string
     */
    abstract public function getOvmlPath();
    
    
    
    /**
     * Get path to css stylesheets directory
     * relative to the ovidentia root folder (config.php)
     * with a terminal slash
     * 
     * @return string
     */
    abstract public function getStylePath();
    
    
    /**
     * Get path to translation files directory
     * relative to the ovidentia root folder (config.php)
     * with a terminal slash
     * 
     * @return string
     */
    abstract public function getLangPath();
    
    
    
    
    /**
     * Get path to the version ini file
     * relative to the ovidentia root folder (config.php)
     * 
     * @return string
     */
    abstract public function getIniFilePath();
    
    
    /**
     * Get path to the version ini file in the package, repository or zip archive
     * relative to the addon root folder or archive root folder
     * 
     * @return string
     */
    abstract public function getPackageIniFilePath();
    
    
    /**
     * Get the list of path to delete when the addon is deleted
     * @return array
     */
    abstract public function getDeletePaths();
    
    /**
     * Get path to the addon folder name in the customizables "skins" folder
     * relative to the ovidentia root folder (config.php)
     * with a terminal slash
     * 
     * @return string
     */
    abstract public function getThemePath();
    
    
    /**
     * Get the addon upload path
     * This path is absolute with a terminal slash
     * @return string
     */
    public function getUploadPath() {
    
    
        require_once dirname(__FILE__).'/settings.class.php';
        $settings = bab_getInstance('bab_Settings');
        /*@var $settings bab_Settings */
        $site = $settings->getSiteSettings();
    
        return $site['uploadpath'].'/addons/'.$this->addonName.'/';
    }
}



/**
 * Old historical location interface to addons files
 */
class bab_AddonInCoreLocation extends bab_AddonLocation
{
    /**
     * addon/addon-name/
     * a replacement for $babAddonHtmlPath unsupported by the bab_AddonLocation model class
     * @return string
     */
    protected function getRelativePath()
    {
        return 'addons/'.$this->addonName.'/';
    }
    
    /**
     * List of addons in core
     * @see bab_AddonLocation::getList()
     * 
     * @return array
     */
    public static function getList()
    {
        if (!file_exists($GLOBALS['babInstallPath'].'addons')) {
            return array();
        }
        
        
        $list = array();
        $h = opendir($GLOBALS['babInstallPath'].'addons/');
        while (($f = readdir($h)) != false)
        {
            if ($f != "." and $f != "..")
            {
                if (is_dir($GLOBALS['babInstallPath'].'addons/'.$f) && is_file($GLOBALS['babInstallPath'].'addons/'.$f."/init.php"))
                {
                    $list[] = $f;
                }
            }
        }
        closedir($h);
        return $list;
    }
    

    public function getPhpPath()
    {
        return $GLOBALS['babInstallPath'].$this->getRelativePath();
    }


    public function getTemplatePath()
    {
        return $GLOBALS['babInstallPath'].'skins/ovidentia/templates/'.$this->getRelativePath();
    }
    
    
    public function getImagesPath() 
    {
        return $GLOBALS['babInstallPath'].'skins/ovidentia/images/'.$this->getRelativePath();
    }


    public function getOvmlPath()
    {
        return $GLOBALS['babInstallPath'].'skins/ovidentia/ovml/'.$this->getRelativePath();
    }


    public function getStylePath()
    {
        return $GLOBALS['babInstallPath'].'styles/'.$this->getRelativePath();
    }
    

    public function getLangPath()
    {
        return $GLOBALS['babInstallPath'].'lang/'.$this->getRelativePath();
    }
    

    public function getIniFilePath()
    {
        return $this->getPhpPath().'addonini.php';
    }
    
    public function getThemePath()
    {
        return 'skins/'.$this->addonName.'/';
    }
    
    public function getPackageIniFilePath()
    {
        return 'programs/addonini.php';
    }
    
    
    public function getDeletePaths()
    {
        $addons_files_location = bab_getAddonsFilePath();
        $return = array();
        foreach($addons_files_location['loc_in'] as $path) {
            $return[] = $path.'/'.$this->addonName;
        }
        return $return;
    }
    
}

/**
 * Addon location in /vendor/ovidentia/addon-name
 */
class bab_AddonStandardLocation extends bab_AddonLocation
{
    
    /**
     * Addon name will be lowercase in paths for this location
     * @param string $addonName
     */
    public function __construct($addonName)
    {
        parent::__construct(mb_strtolower($addonName));
    }
    
    
    
    /**
     * In standard location
     * All addon files are under one base folder
     */
    protected function getBasePath()
    {
        return 'vendor/ovidentia/'.$this->addonName.'/';
    }
    
    /**
     * Get list of addons in vendor
     * @see bab_AddonLocation::getList()
     * 
     * @return array
     */
    public static function getList()
    {
        $list = array();
        $h = opendir('vendor/ovidentia');
        while (($f = readdir($h)) != false)
        {
            if ($f != "." and $f != "..")
            {
                if (is_dir('vendor/ovidentia/'.$f) && is_file('vendor/ovidentia/'.$f."/programs/init.php"))
                {
                    $list[] = $f;
                }
            }
        }
        
        return $list;
    }
    
    
    public function getPhpPath()
    {
        // return $this->getBasePath().'src/';
        return $this->getBasePath().'programs/';
    }
    
    
    public function getTemplatePath()
    {
        return $this->getBasePath().'skins/ovidentia/templates/';
    }
    
    
    public function getImagesPath()
    {
        return $this->getBasePath().'skins/ovidentia/images/';
    }
    
    
    public function getOvmlPath()
    {
        return $this->getBasePath().'skins/ovidentia/ovml/';
    }
    
    
    public function getStylePath()
    {
        return $this->getBasePath().'styles/';
    }
    
    
    public function getLangPath()
    {
        return $this->getBasePath().'langfiles/';
    }
    
    
    public function getIniFilePath()
    {
        // return $this->getBasePath().'version.ini';
        return $this->getBasePath().'programs/addonini.php';
    }
    
    public function getPackageIniFilePath()
    {
        // return 'version.ini';
        return 'programs/addonini.php';
    }
    
    public function getThemePath()
    {
        return $this->getBasePath().'theme/';
    }
    
    
    public function getDeletePaths()
    {

        return array(
            $this->getBasePath(),
            $this->getThemePath()
        );
    }
}