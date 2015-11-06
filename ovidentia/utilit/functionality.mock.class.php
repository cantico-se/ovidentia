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

/**
 * functionality class to use in tests
 * create a bab_functionality class with a extend of this mock class
 */
abstract class bab_functionalityMock
{
    public static function includefile($path)
    {
        switch($path) {
            case 'PwdComplexity':
            case 'PwdComplexity/DefaultPortal':
                require_once $GLOBALS['babInstallPath'].'utilit/pwdcomplexity.class.php';
                return 'PwdComplexity_DefaultPortal';
                
        }
        
        throw new Exception('Unexpected functionnality '.$path);
    }
    
    public static function includeOriginal($path)
    {
        return self::includefile($path);
    }
    
    
    /**
     * Include original file for all functionality of the dir name (without the basename)
     * from top to bottom
     *
     * @since 8.3.91
     *
     * @param string $path
     * @return bool
     */
    protected static function includeOriginalDirname($path)
    {
        return true;
    }
    
    
    /**
     * Returns the specified functionality object without the default inherithed object.
     *
     * If $singleton is set to true, the functionality object will be instanciated as
     * a singleton, i.e. there will be at most one instance of the functionality
     * at a given time.
     *
     * @since 7.8.90
     *
     * @param string 	$path
     * @param bool 		$singleton
     *
     * @return bab_functionality
     */
    public static function getOriginal($path, $singleton = true)
    {
        self::includeOriginalDirname($path);
        $classname = self::includeOriginal($path);
        if (!$classname) {
            return false;
        }
        if ($singleton) {
            return bab_getInstance($classname);
        }
        return new $classname();
    }
    
    
    
    /**
     * Returns the specified functionality object.
     *
     * If $singleton is set to true, the functionality object will be instanciated as
     * a singleton, i.e. there will be at most one instance of the functionality
     * at a given time.
     *
     * @param	string	$path		The functionality path.
     * @param	bool	$singleton	Whether the functionality should be instanciated as singleton (default true).
     * @return	bab_functionality	The functionality object or false on error.
     */
    public static function get($path, $singleton = true)
    {
        self::includeOriginalDirname($path);
        $classname = self::includefile($path);
    
        if (!$classname) {
            return false;
        }
    
        if ($singleton) {
            return bab_getInstance($classname);
        }
    
        return new $classname();
    }

    
    
    /**
     * Default method to create in inherited functionalities
     * @access protected
     * @return string
     */
    public function getDescription() {
        return '';
    }
    
    
    /**
     * Get path to functionality at this node which is the current path or a reference to a childnode
     * @return string
     */
    public function getPath() {
        require_once $GLOBALS['babInstallPath'].'utilit/functionalityincl.php';
        return bab_Functionalities::getPath(get_class($this));
    }
}