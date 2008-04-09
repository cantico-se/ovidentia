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
	 * @access	public
	 */
	function bab_functionalities() {
		$this->filename = BAB_FUNCTIONALITY_LINK_FILENAME;
		$this->original = BAB_FUNCTIONALITY_LINK_ORIGINAL_FILENAME;
		$this->rootDirName = BAB_FUNCTIONALITY_ROOT_DIRNAME;
		$this->treeRootPath = dirname($_SERVER['SCRIPT_FILENAME']).'/'.$this->rootDirName.'/';
		
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
			chmod($location, 0777);
		} else {
			chmod($location, 0666);
		}
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
	
		// replace core directory with a variable
		$include_file = str_replace($GLOBALS['babInstallPath'], '\'.$GLOBALS[\'babInstallPath\'].\'', $include_file);
		$classname = str_replace('/','_',$funcpath);
		$content = '<?php if (false === include_once \''.$include_file.'\') { return false; } else { return \''.$classname.'\'; } ?>';
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
	 * @private
	 *
	 * @param	string		$path 		destination path to record the link
	 * @param	string		$funcpath	path to functionality
	 *
	 * @return boolean
	 */
	function recordLinkToLink($path, $funcpath) {
		
		if (file_exists($this->treeRootPath.$path.'/'.$this->filename)) {
			return false;
		}
		
		if ('' === $path) {
			return false;
		}
		
		$content = '<?php return include \''.$this->rootDirName.'/'.$funcpath.'/'.$this->filename.'\';  ?>';
		
		
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
		
		sort($children);
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
		while($entryname = readdir($current_dir)){
			if (is_file($this->treeRootPath.$func_path.'/'.$entryname)) {
				if (false === unlink($this->treeRootPath.$func_path.'/'.$entryname)) {
					return false;
				}
			}
		}
		
		
		if (0 === count($children)) {
			return rmdir($this->treeRootPath.$func_path);
		} else {
			return $this->recordLinkToLink($func_path, $func_path.'/'.$children[0]);
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
	 * test if $path1 is a correct parent path for $path2
	 * return true if $path2 contain methods from $path1
	 *
	 * @param	string	$path1			path to existing functionality
	 * @param	string	$path2			path to functionality not created
	 * @param	string	$include_file	file to include for path2 object
	 *
	 * @return boolean
	 */
	function compare($path1, $path2, $include_file) {
		
		$parent = $this->getOriginal($path1);
		if (false === $parent) {
			bab_debug(sprintf('bab_functionalities::compare() : the path "%s" does not exists', $path1));
			return false;
		}
		
		if (!include_once $include_file) {
			return false;
		}
		
		$classname = str_replace('/', '_', $path2);
		$child = new $classname();
		
		if (false === $child) {
			return false;
		}
		
		$parent_methods = $parent->getCallableMethods();
		$child_methods = $child->getCallableMethods();
		
		$intersect = array_intersect($parent_methods, $child_methods);
		if ($intersect != $parent_methods) {
			return false;
		}
		
		return true;
	}
	
	
	
	/**
	 * Register functionality into functionality tree
	 * duplicate registration link into parent directories while nothing is registered
	 *
	 * @access	public
	 * @param	string	$func_path		path to functionality
	 * @param	string	$include_file	file to include before calling this functionality
	 * @return  boolean
	 */
	function register($func_path, $include_file) {
	
		$func_path = trim($func_path,'/ ');

		if (false !== strpos($func_path, '_')) {
			trigger_error('$func_path must not contain _');
			return false;
		}

		// verify interface


		if ($parent = $this->getParentPath($func_path)) {
			if (false !== bab_functionality::get($parent) && !$this->compare($parent, $func_path, $include_file)) {
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

			if (file_exists($this->treeRootPath.$func_path.'/'.$this->filename)) {
				unlink($this->treeRootPath.$func_path.'/'.$this->filename);
			}

			if (false === $this->recordLink($func_path, $func_path, $include_file, $this->filename)) {
				return false;
			}
		}

		$this->normalizeNodeWithChild($this->getParentPath($func_path), $func_path);

		$event = new bab_eventFunctionalityRegistered($func_path);
		bab_fireEvent($event);
		
		return true;
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
}


/**
 * A functionality has been registered
 * 
 * @package events
 * @since 6.6.93
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
 * @since 6.6.93
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
