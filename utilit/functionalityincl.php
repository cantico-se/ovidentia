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
require_once dirname(__FILE__).'/eventincl.php';

/**
 * Prefix for all bab_Functionality classes.
 * @since 6.6.92
 */
define('BAB_FUNCTIONALITY_CLASS_PREFIX', 'Func_');


/**
 * manage the functionality tree
 * register and unregister functionalities
 * @since 6.6.90
 */
class bab_functionalities {

	var $treeRootPath;
	var $filename;
	var $original;
	var $rootDirName;
	
	var $treeLinks = array();
	

	/**
	 * Constructor
	 *
	 * @return bab_functionalities
	 * @access	public
	 */
	function bab_functionalities() {
		$this->filename = BAB_FUNCTIONALITY_LINK_FILENAME;
		$this->original = BAB_FUNCTIONALITY_LINK_ORIGINAL_FILENAME;
		$this->rootDirName = BAB_FUNCTIONALITY_ROOT_DIRNAME;
		$this->treeRootPath = bab_functionality::getRootPath().'/';
		
		if (!is_dir($this->treeRootPath)) {
			bab_mkdir($this->treeRootPath);
			$this->onInsertNode($this->treeRootPath);
		}
	}
	
	
	/**
	 * on insert file or directory
	 * @access	private
	 * @param	string	$location
	 */
	function onInsertNode($location) {
		if (is_dir($location)) {
			chmod($location, 0770);
		} else {
			chmod($location, 0660);
		}
	}
	
	
	
	
	/**
	 * Convert an path to a string useable in an include statement
	 * @access	private
	 * @param	string	$filePath
	 * @return 	string
	 */
	function getIncludeFilePath($filePath) {
	
		// replace \ with / for windows platform
		$installPath = str_replace('\\','/',$GLOBALS['babInstallPath']);
		$includePath = str_replace('\\','/',$filePath);
		
		// include in relative path if file is under install path
		if (false !== mb_strpos($includePath, $installPath)) {
			$pos = mb_strlen($includePath) - mb_strpos( strrev($includePath) , strrev($installPath)) - mb_strlen($installPath);
			$includePath = mb_substr($includePath, $pos);
		}
		
		return str_replace($GLOBALS['babInstallPath'], '\'.$GLOBALS[\'babInstallPath\'].\'', $includePath);
	}
	
	
	
	
	
	
	
	
	/**
	 * Record link into tree
	 * the directory must be present
	 * @access	private
	 * @param	string	$path			destination path to record the link
	 * @param	string	$funcpath		path to functionality
	 * @param	string	$include_file	file to include before calling this functionality
	 * @param	string	$linkfilename	Link file name
	 * @return 	boolean
	 */
	function recordLink($path, $funcpath, $include_file, $linkfilename) {
	
		if (file_exists($this->treeRootPath.$path.'/'.$linkfilename)) {
			return false;
		}
		
		if ('' === $path) {
			return false;
		}
	
		$classname = bab_Functionalities::getClassname($funcpath);
		$content = '<?php if (class_exists(\''.$classname.'\',false) || false !== include_once \''.$this->getIncludeFilePath($include_file).'\') { return \''.$classname.'\'; } else { return false; } ?>';
		if ($handle = fopen($this->treeRootPath.$path.'/'.$linkfilename, 'w')) {
			
			if (false !== fwrite($handle, $content)) {
				fclose($handle);
				$this->onInsertNode($this->treeRootPath.$path.'/'.$linkfilename);
				return true;
			} else {
				return false;
			}
		}

		return false;
	}


	/**
	 * Record link into tree, target is a link
	 *
	 * @param	string		$path 		destination path to record the link
	 * @param	string		$funcpath	path to functionality
	 * @access private
	 * @return boolean
	 */
	function recordLinkToLink($path, $funcpath) {
		
		if (file_exists($this->treeRootPath.$path.'/'.$this->filename)) {
			return false;
		}

		if ('' === $path) {
			return false;
		}

		$content = '<?php include_once \'' . $this->getIncludeFilePath($this->rootDirName.'/'.$path.'/'.$this->original) . '\'; return include \'' . $this->getIncludeFilePath($this->rootDirName.'/'.$funcpath.'/'.$this->filename) . '\'; ?>';

		if ($handle = fopen($this->treeRootPath.$path.'/'.$this->filename, 'w')) {
			
			if (false !== fwrite($handle, $content)) {
				fclose($handle);
				$this->onInsertNode($this->treeRootPath.$path.'/'.$this->filename);
				return true;
			} else {
				return false;
			}
		}

		return false;
	}



	/**
	 * @access	private
	 * @param	array $func_path
	 * @return 	array
	 */
	function getChildren($func_path) {

		$func_path = trim($func_path, '/ ');

		$children = array();
		if ($dh = opendir($this->treeRootPath.$func_path)) {
			while (($file = readdir($dh)) !== false) {
				if (is_dir($this->treeRootPath.$func_path.'/'.$file) && '.' !== $file && '..' !== $file) {
					$children[] = $file;
				}
			}
			closedir($dh);
		}

		bab_sort::sort($children, bab_sort::CASE_INSENSITIVE);
		return $children;
	}


	/**
	 * @access	private
	 * @param	array $func_path
	 * @return 	int
	 */
	function nbChildren($func_path) {
		return count($this->getChildren($func_path));
	}


	/**
	 * Get CRC 32 for function
	 * @access	private
	 * @param	string	$func_path
	 * @param	string	$filename
	 * @return 	int
	 */
	function getCrc($func_path, $filename) {
		$path = trim($func_path,'/ ');
		$link = $this->treeRootPath.$func_path.'/'.$filename;
		$file = file($link);

		if (!isset($file[0])) {
			return 0;
		}

		return abs(crc32($file[0]));
	}


	/** 
	 * @access	public
	 * @param	array $func_path
	 * @return 	false|string
	 */
	function getParentPath($func_path) {
		$arr = explode('/', $func_path);

		if (2 > count($arr)) {
			return false;
		}

		array_pop($arr);
		return implode('/', $arr);
	}


	/**
	 * Force copy to parent manually
	 * @access	public
	 * @param	array $func_path
	 * @return 	boolean
	 */
	function copyToParent($func_path) {
		$parent_path = $this->getParentPath($func_path);

		if (false === $parent_path) {
			return false;
		}

		if (!unlink($this->treeRootPath.$parent_path.'/'.$this->filename)) {
			return false;
		}

		if (!$this->recordLinkToLink($parent_path, $func_path)) {
			return false;
		}

		return true;
	}



	/**
	 * Delete or replace with first child
	 * @access	private
	 * @param	string	$func_path
	 * @return 	boolean
	 */
	function deleteOrReplaceWithFirstChild($func_path) {

		$children = $this->getChildren($func_path);

		$current_dir = opendir($this->treeRootPath.$func_path);
		if ($current_dir !== false) {
			while($entryname = readdir($current_dir)){
				if (is_file($this->treeRootPath.$func_path.'/'.$entryname)) {
					if (false === unlink($this->treeRootPath.$func_path.'/'.$entryname)) {
						return false;
					}
				}
			}
			closedir($current_dir);
	
			if (0 === count($children)) {
				return rmdir($this->treeRootPath.$func_path);
			} else {
				return $this->recordLinkToLink($func_path, $func_path.'/'.$children[0]);
			}
		}
	}


	/**
	 * Get functionality object with original link
	 * @access public
	 * @static
	 * @param	string	$path
	 * @return false|object
	 */
	function getOriginal($path) {

		if ($classname = @include $this->treeRootPath.$path.'/'.$this->original) {
			return new $classname();
		}

		return false;
	}


	/**
	 * Tests if $parentPath is a correct parent path for $childPath.
	 *
	 * @param	string	$parentPath		path to existing functionality
	 * @param	string	$childPath		path to functionality not created
	 * @param	string	$include_file	file to include for childPath object
	 *
	 * @return boolean
	 */
	function compare($parentPath, $childPath, $include_file) {

		$parent = $this->getOriginal($parentPath);
		if (false === $parent) {
			bab_debug(sprintf('bab_functionalities::compare() : the path "%s" does not exists', $parentPath));
			return false;
		}

		if (!include_once $include_file) {
			trigger_error(sprintf('The include file %s does not exist', $include_file));
			return false;
		}

		$childClassname = bab_Functionalities::getClassname($childPath);
		$child = new $childClassname();

		if (!is_subclass_of($child, get_class($parent))) {
			return false;
		}

		return true;
	}



	/**
	 * Registers the specified functionality path into the functionality tree.
	 * Duplicate registration link into parent directories while nothing is registered.
	 *
	 * @access	public
	 * @param	string	$func_path		path to functionality
	 * @param	string	$include_file	file to include before calling this functionality
	 * @return  boolean
	 */
	function register($func_path, $include_file) {

		// verify parent functionality
		$parent_original = $this->treeRootPath.'/'.dirname($func_path).'/'.$this->original;
		
		if ('.' !== dirname($func_path) && !file_exists($parent_original)) {
			trigger_error(sprintf('The functionality "%s" cannot be registered because parent functionality does not exists', $func_path));
			return false;
		}

		if (!file_exists($include_file)) {
			trigger_error(sprintf('The registered file "%s" for functionality "%s" cannot be included', $include_file, $func_path));
			return false;
		}
		include_once $include_file;
		
		$func_path = trim($func_path,'/ ');

		if (false !== mb_strpos($func_path, '_')) {
			trigger_error('$func_path must not contain _');
			return false;
		}

		// verify interface


		if ($parent = $this->getParentPath($func_path)) {
			if (false !== @bab_functionality::get($parent) && !$this->compare($parent, $func_path, $include_file)) {
				trigger_error(sprintf('The functionality %s does not implement interface from parent functionality %s', $func_path, $parent));
				return false;
			}
		}

		// create directory if not exists
		$arr = explode('/',$func_path);
		$path = $this->treeRootPath;
		foreach($arr as $directory) {
			$path .= $directory.'/';

			if (!is_dir($path)) {
				if (!bab_mkdir($path)) {
					trigger_error(sprintf('Cannot create folder "%s"', $path));
					return false;
				}
				if (!is_writable($path)) {
					trigger_error(sprintf('Cannot create writable folder "%s"', $path));
					return false;
				}
				$this->onInsertNode($path);
			}
		}


		// link upgrade
		if (is_dir($this->treeRootPath.$func_path)) {

			if (file_exists($this->treeRootPath.$func_path.'/'.$this->original)) {
				unlink($this->treeRootPath.$func_path.'/'.$this->original);
			}

			if (false === $this->recordLink($func_path, $func_path, $include_file, $this->original)) {
				return false;
			}

			if (!file_exists($this->treeRootPath.$func_path.'/'.$this->filename)) {
				if (false === $this->recordLink($func_path, $func_path, $include_file, $this->filename)) {
					return false;
				}
			}
			
		}

		$this->normalizeNodeWithChild($this->getParentPath($func_path), $func_path);

		$event = new bab_eventFunctionalityRegistered($func_path);
		bab_fireEvent($event);
		
		return true;
	}

	/**
	 * find Func classes in php file with a preg_match_all
	 * @since 7.2.90
	 * 
	 * @param	string	$file	full absolute path to php file
	 * @return array
	 */
	public function parseFile($file)
	{
		$return = array();
		$contents = file_get_contents($file);
		
		if (preg_match_all('/class\s+Func_([_\w]+)\s+extends\s+/', $contents, $matches)) {
			foreach($matches[1] as $func) {
				$arr = explode('_', $func);
				$return[] = implode('/', $arr);
			}
		}
		
		return $return;
	}
	
	

	/**
	 * Registers the specified functionality class into the functionality tree.
	 * Similar to bab_Functionalities::register but uses classname instead of functionality path.
	 * @see bab_Functionalities::register
	 *
	 * @access	public
	 * @param	string	$classname		Name of functionality class
	 * @param	string	$include_file	File to include before calling this functionality
	 * @return  boolean
	 * @since 6.6.92
	 */
	function registerClass($classname, $include_file)
	{
		return $this->register(bab_Functionalities::getPath($classname), $include_file);
	}


	/**
	 * Test link validity
	 * @param	string	$funcPath
	 *
	 * @return boolean
	 */
	function isValidLinks($funcPath) {
		return (file_exists($this->treeRootPath.$funcPath.'/'.$this->filename) && file_exists($this->treeRootPath.$funcPath.'/'.$this->original));
	}



	/**
	 * @param	string	$verifyPath		path to verifiy : if link is the same as original, link it to child
	 * @param	string	$childPath
	 *
	 * @return boolean
	 */
	function normalizeNodeWithChild($verifyPath, $childPath) {
		
		if ($this->isValidLinks($verifyPath)) {
			if ($this->getCrc($verifyPath, $this->filename) === $this->getCrc($verifyPath, $this->original)) {
			
				unlink($this->treeRootPath.$verifyPath.'/'.$this->filename);
			
				$this->recordLinkToLink($verifyPath, $childPath);
				
				$parentPath = $this->getParentPath($verifyPath);
				$this->normalizeNodeWithChild($parentPath, $verifyPath);
			}
		}
	}



	/**
	 * Unregister a functionality.
	 * If the functionality is not registered, this method return true
	 * Removes the link in this directory
	 * If link is present in parent functionality, delete or replace with another
	 *
	 * @param	string	$func_path		The path identifying the functionality
	 * @return boolean
	 * @access 	public
	 */
	function unregister($func_path) {

		if (!file_exists($this->treeRootPath.$func_path)) {
			return true;
		}

		if (!$this->deleteOrReplaceWithFirstChild($func_path)) {
			return false;
		}
		$event = new bab_eventFunctionalityUnregistered($func_path);
		bab_fireEvent($event);
		return true;
	}
	
	
	/**
	 * Verify tree.
	 * Remove dead links
	 * @param	string	[$path]
	 */
	function cleanTree($path = '') {

		$children = $this->getChildren($path);
		foreach ($children as $child) {
			$this->cleanTree($path.$child.'/');
			$file = $this->treeRootPath.$path.$child.'/'.$this->filename;
			if (true !== (include_once $file)) {
				// la destionation du lien n'existe pas
				$this->unregister($path.$child);
			}
		}
	}

	/**
	 * Returns the sanitized functionality path.
	 * 
	 * This method removes non-allowed characters (only alphanumeric characters and '/' are allowed).
	 * 
	 * @param string $sPath		The functionality path to sanitize.
	 * @return string			The sanitized functionality path.
	 * @since 6.6.92
	 * @static
	 */
	function sanitize($sPath) {
		$aPattern = array('#[^0-9a-zA-Z/]#i', '#/+#i');
		$aReplacement = array('', '/');
		return trim(preg_replace($aPattern, $aReplacement, $sPath), '/');
	}

	/**
	 * Returns the classname corresponding to a functionality path.
	 * 
	 * The returned classname is computed from the path and does not mean that
	 * the class or the path actually exist.  
	 *
	 * @param string $path		The functionality path.
	 * @return string
	 * @since 6.6.92
	 * @static
	 */
	function getClassname($path) {
		return BAB_FUNCTIONALITY_CLASS_PREFIX . str_replace('/', '_', $path);
	}

	/**
	 * Returns the path corresponding to a functionality classname.
	 *
	 * The returned path is computed from the classname and does not mean that
	 * the class or the path actually exist.  
	 * 
	 * @param string $classname	The functionality classname.
	 * @return string
	 * @since 6.6.92
	 * @static
	 */
	function getPath($classname) {
		return str_replace('_', '/', mb_substr($classname, mb_strlen(BAB_FUNCTIONALITY_CLASS_PREFIX)));
	}
}


/**
 * A functionality has been registered
 * 
 * @package events
 * @since 6.6.92
 */
class bab_eventFunctionalityRegistered extends bab_event
{
	/**
	 * The path identifying the functionality
	 *
	 * @access private
	 * @var string
	 */
	var $functionalityPath;

	/**
	 * @param string	$functionalityPath		The path identifying the functionality
	 * @return bab_eventFunctionalityRegistered
	 */
	function bab_eventFunctionalityRegistered($functionalityPath)
	{
		$this->functionalityPath = $functionalityPath;
	}
}


/**
 * A functionality has been unregistered
 * 
 * @package events
 * @since 6.6.92
 */
class bab_eventFunctionalityUnregistered extends bab_event
{
	/**
	 * The path identifying the functionality
	 *
	 * @access private
	 * @var string
	 */
	var $functionalityPath;

	/**
	 * @param string	$functionalityPath		The path identifying the functionality
	 * @return bab_eventFunctionalityUnregistered
	 */
	function bab_eventFunctionalityUnregistered($functionalityPath)
	{
		$this->functionalityPath = $functionalityPath;
	}
}
