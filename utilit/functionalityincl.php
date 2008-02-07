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
 * manage the functionality tree
 * register and unregister functionalities
 * @since 6.6.90
 */
class bab_functionalities {

	var $treeRootPath;
	var $filename;
	var $rootDirName;
	
	var $treeLinks = array();
	
	
	/**
	 * Constructor
	 * @access	public
	 */
	function bab_functionalities() {
		$this->filename = BAB_FUNCTIONALITY_LINK_FILENAME;
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
		chmod($location, 0777);
	}
	
	
	
	
	
	
	/**
	 * Record link into tree
	 * the directory must be present
	 * @access	private
	 * @param	string	$path			path to the link
	 * @param	string	$funcpath		path to functionality
	 * @param	string	$include_file	file to include before calling this functionality
	 * @return 	boolean
	 */
	function recordLink($path, $funcpath, $include_file) {
	
		if (file_exists($this->treeRootPath.$path.'/'.$this->filename)) {
			return false;
		}
		
		if ('' === $path) {
			return false;
		}
	
		// replace core directory with a variable
		$include_file = str_replace($GLOBALS['babInstallPath'], '\'.$GLOBALS[\'babInstallPath\'].\'', $include_file);
		$classname = str_replace('/','_',$funcpath);
		$content = '<?php if (false === @include_once \''.$include_file.'\') { return false; } else { return \''.$classname.'\'; } ?>';
		
		if ($handle = fopen($this->treeRootPath.$path.'/'.$this->filename, 'w')) {
			
			if (false !== fwrite($handle, $content)) {
				fclose($handle);
				return true;
			}
			
			fclose($handle);
			$this->onInsertNode($this->treeRootPath.$path.'/'.$this->filename);
		}

		return false;
	}
	

	
	/**
	 * @access	private
	 * @param	array $func_path
	 * @return 	array
	 */
	function getChilds($func_path) {
	
		$func_path = trim($func_path, '/ ');

		$childs = array();
		if ($dh = opendir($this->treeRootPath.$func_path)) {
			while (($file = readdir($dh)) !== false) {
				if (is_dir($this->treeRootPath.$func_path.'/'.$file) && '.' !== $file && '..' !== $file) {
					$childs[] = $file;
				}
			}
			closedir($dh);
		}
		
		sort($childs);
		return $childs;
	}
	
	
	/**
	 * @access	private
	 * @param	array $func_path
	 * @return 	int
	 */
	function nbChilds($func_path) {
		return count($this->getChilds($func_path));
	}
	
	
	/**
	 * Get CRC 32 for function
	 * @access	private
	 * @param	array $func_path
	 * @return 	int
	 */
	function getCrc($func_path) {
		$path = trim($func_path,'/ ');
		$link = $this->treeRootPath.$func_path.'/'.$this->filename;
		$file = file($link);
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
		
		if (!copy($this->treeRootPath.$func_path.'/'.$this->filename, $this->treeRootPath.$parent_path.'/'.$this->filename)) {
			return false;
		}
		
		return true;
	}
	
	
		
	/**
	 * Delete or replace with first child recursively from bottom to top
	 * while functionality has same crc
	 * @access	private
	 * @param	int		$crc		crc to delete or replace
	 * @param	string	$func_path	
	 */
	function deleteOrReplaceWithFirstChild($crc, $func_path) {
		
		if ($crc !== $this->getCrc($func_path)) {
			return;
		}
		
		$childs = $this->getChilds($func_path);
		unlink($this->treeRootPath.$func_path.'/'.$this->filename);
		
		if (0 === count($childs)) {
			rmdir($this->treeRootPath.$func_path);
		} else {
			copy($this->treeRootPath.$func_path.'/'.$childs[0], $this->treeRootPath.$func_path.'/'.$this->filename);
		}

		$parent = $this->getParentPath($func_path);
		if ($parent) {
			$this->deleteOrReplaceWithFirstChild($crc, $parent);
		}
	}
	
	
	/**
	 * test if $path1 is a correct parent path for $path2
	 * return true if $path2 contain methods from $path1
	 * @return boolean
	 */
	function compare($path1, $path2) {
		$parent = bab_functionality::get($path1);
		$child = bab_functionality::get($path2);
		
		if (!$parent || !$child) {
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
	 * duplicate registration link into parent directories will nothing is registered
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
		
		/*
		if ($parent = $this->getParentPath($func_path)) {
			if (!$this->compare($parent, $func_path)) {
				trigger_error(sprintf('The functionality %s does not implement interface from parent functionality %s', $parent, $func_path));
				return false;
			}
		}
		*/
		
		
		
		// link upgrade
		if (is_dir($this->treeRootPath.$func_path)) {
			if (file_exists($this->treeRootPath.$func_path.'/'.$this->filename)) {
				unlink($this->treeRootPath.$func_path.'/'.$this->filename);
			}
			$this->recordLink($func_path, $func_path, $include_file);
			return true;
		}
	
		
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
		
		
		do {
			$path = implode('/', $arr);
		} while ($this->recordLink($path, $func_path, $include_file) && null !== array_pop($arr));
		
		return true;
	}
	
	

	
	
	
	
	
	
	/**
	 * Unregister functionality
	 * Remove link in this directory
	 * if link is present in parent functionality, delete or replace with another
	 *
	 * @access 	public
	 * @param	string	$func_path
	 * @return boolean
	 */
	function unregister($func_path) {
	
		$func_path = trim($func_path,'/ ');
		$link = $func_path.'/'.$this->filename;
		
		if (!file_exists($this->treeRootPath.$link)) {
			return false;
		}
		
		$crc = $this->getCrc($func_path);
		$this->deleteOrReplaceWithFirstChild($crc, $func_path);

		return true;
	}
	
	
	/**
	 * Verify tree
	 * remove dead links
	 * @param	string	[$path]
	 */
	function cleanTree($path = '') {
	
		$childs = $this->getChilds($path);
		foreach ($childs as $child) {
			$this->cleanTree($path.$child.'/');
			$file = $this->treeRootPath.$path.$child.'/'.$this->filename;
			if (true !== (include_once $file)) {
				// la destionation du lien n'existe pas
				$this->unregister($path.$child);
			}
		}
	
	}
}


?>