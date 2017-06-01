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

require_once dirname(__FILE__).'/defines.php';


/**
 * Functionality interface
 * Functionalities are inherited from this object, to instanciate a functionality use the static method
 * @see bab_functionality::get($path)
 * @since 6.6.90
 */
class bab_functionality {


    /**
     * @deprecated Do not remove old constructor while there are functionalities in addons with direct call to bab_functionality::bab_functionality()
     */
    public function bab_functionality() { }


    public static function getRootPath() {
        require_once dirname(__FILE__).'/defines.php';
        return realpath('.').'/'.BAB_FUNCTIONALITY_ROOT_DIRNAME;
    }


    /**
     * Include php file with the functionality class
     * @param string $pathname
     * @return bool
     */
    private static function includeFileIfExists($path, $filename)
    {
        $pathname = self::getRootPath().'/'.$path.'/'.$filename;

        $include_result = false;
        if (file_exists($pathname))  {
            $include_result = include $pathname;
        }

        if (false === $include_result) {
            bab_debug(sprintf('The functionality %s is not available', $path), DBG_ERROR, __CLASS__);
        }

        return $include_result;
    }


    /**
     * Include php file with the functionality class
     * @see bab_functionality::get()
     * @param	string	$path		path to functionality
     * @return string | false		the object class name or false if the file already included or false if the include failed
     */
    public static function includefile($path)
    {
        require_once dirname(__FILE__).'/defines.php';
        return self::includeFileIfExists($path, BAB_FUNCTIONALITY_LINK_FILENAME);
    }


    /**
     * Include original php file with the functionality class
     *
     * @since 7.8.90
     *
     * @param string $path 			path to functionality
     * @return string | false		the object class name or false if the file already included or false if the include failed
     */
    public static function includeOriginal($path)
    {
        $parentPath = dirname($path);
        
        if ('.' !== $parentPath && '/' !== $parentPath && '\\' !== $parentPath && !empty($parentPath)) {
            self::includeOriginal($parentPath);
        }
        
        return self::includeFileIfExists($path, BAB_FUNCTIONALITY_LINK_ORIGINAL_FILENAME);
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
        $path = dirname($path);
        if (empty($path) || '.' === $path) {
            return false;
        }

        $items = explode('/', $path);
        $includePath = '';
        foreach($items as $item) {
            $includePath .= $item;
            if (!self::includeOriginal($includePath)) {
                return false;
            }
            $includePath .= '/';
        }

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
     * get functionalities compatible with the interface
     * @param	string	$path
     * @return array
     */
    public static function getFunctionalities($path) {
        require_once $GLOBALS['babInstallPath'].'utilit/functionalityincl.php';
        $obj = new bab_functionalities();
        return $obj->getChildren($path);
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
