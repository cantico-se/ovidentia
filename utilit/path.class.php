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
require_once 'base.php';



/**
 * Path object
 */
class bab_Path {


	private $allElements = array();
	private $absolute = null;


	/**
	 * Concats all strings to form a path.
	 * Eg. new bab_Path('a/b', 'c', 'd/e/', 'f/') => 'a/b/c/d/e/f'
	 *
	 * @param       string  $subPath...             On or more sub paths.
	 * @return      string
	 */
	public function __construct($subPath)
	{
			$subPaths = func_get_args();
			$this->concatPaths($subPaths);
	}



	/**
	 * Concats all strings in the array $subPaths to form a path.
	 * @see spaces_path
	 *
	 * @param array $subPaths       An array of string
	 */
	private function concatPaths($subPaths)
	{
		$this->absolute = self::isAbsolutePath($subPaths[0]);

		$this->allElements = array();
		foreach ($subPaths as $subPath) {
			$elements = explode('/', $subPath);
			foreach ($elements as $element) {
				if ($element === '..' && count($allElements) > 0) {
					array_pop($this->allElements);
				} elseif ($element !== '.' && $element !== '..' && $element !== '') {
					array_push($this->allElements, $element);
				}
			}
		}	
	}


	/**
	 * Checks whether the path is an absolute path.
	 * On Windows something like C:/example/path or C:\example\path or C:/example\path
	 * On unix something like /example/path
	 * 
	 * see bab_Path::isAbsolute
	 * @param string	$path
	 * @return bool
	 */
	public static function isAbsolutePath($path)
	{

		if (DIRECTORY_SEPARATOR === '\\') {
			$path = str_replace(DIRECTORY_SEPARATOR, '/', $path);
			$regexp = '#^[a-zA-Z]\:/#';
		} else {
			$regexp = '#^/#';
		}

		if (0 !== preg_match($regexp, $path)) {
			return true;
		}
		return false;
	}


	/**
	 * Checks whether the path is an absolute path.
	 * On Windows something like C:/example/path or C:\example\path or C:/example\path
	 * On unix something like /example/path
	 * 
	 * @see bab_Path::isAbsolutePath
	 * @return bool
	 */
	public function isAbsolute()
	{
		return self::isAbsolutePath($this->toString());
	}


	/**
	 * Return the path as a string.
	 * 
	 * @return string
	 */
	public function tostring() {

		$path = implode('/', $this->allElements);
		if ($this->absolute && DIRECTORY_SEPARATOR === '/') {
			$path = '/' . $path;
		}
		return $path;
	}




	/**
	 * Tests if the path is accessible to create, update, delete files.
	 * Additional tests are made for Windows IIS.
	 *
	 * Test if a folder is accessible to create, update, delete files
	 * aditionals tests are made for Windows IIS
	 * 
	 * @throw bab_FolderAccessRightsException | Exception
	 * 
	 * @return	bool
	 */
	public function isFolderWriteable() {

		$dir = $this->tostring();

		if (!@file_exists($dir)) {
			throw new Exception(sprintf(bab_translate('The folder %s does not exists'), $dir));
			return false;
		}

		if (!@is_dir($dir)) {
			throw new Exception(bab_translate('test can only be done on a directory'));
			return false;
		}

		if (DIRECTORY_SEPARATOR === '/') {
			if (!is_writable($dir)) {
				$message = bab_translate('The folder %s is not writable, please check the access rights');
				throw new bab_FolderAccessRightsException(sprintf($message, $dir));
				return false;
			}

			return true;
		}


		// Windows specifics tests

		if (!is_writable($dir)) {
			$message = bab_translate('The folder %s is not writable, it is probably in read-only mode, please check the access rights');
			throw new bab_FolderAccessRightsException(sprintf($message, $dir));
			return false;
		}


		$filename = $dir.'/_________Ovidentia_test_file';

		if (file_exists($filename)) {
			if (false === @unlink($filename)) {

				$mtime = date('Y-m-d H:i:s', filemtime($filename));
				$message = bab_translate('The folder %s has been writable in the past (%s) but the current access rights will not allow a delete operation');

				throw new bab_FolderAccessRightsException(sprintf($message, $dir, $mtime));
				return false;
			}

		} else if (false === @touch($filename)) {
			$message = bab_translate('The folder %s is not writable, the file creation test failed, please check the access rights');
			throw new bab_FolderAccessRightsException(sprintf($message, $dir));
			return false;
		}

		// the file has been created correctly

		if (false === @unlink($filename)) {
			$message = bab_translate('The folder %s is not writable, files can be created but not deleted, please check the access rights');
			throw new bab_FolderAccessRightsException(sprintf($message, $dir));
			return false;
		}


		// the file has been deleted

		$folder_to_test = $dir.'/_________Ovidentia_test_folder';

		if (false === @mkdir($folder_to_test)) {
			$message = bab_translate('The folder %s is not writable, the folder creation test failed, please check the access rights');
			throw new bab_FolderAccessRightsException(sprintf($message, $dir));
			return false;
		}

		if (false === @rmdir($folder_to_test)) {
			$message = bab_translate('The folder %s is not writable, folders can be created but not deleted, please check the access rights');
			throw new bab_FolderAccessRightsException(sprintf($message, $dir));
			return false;
		}

		return true;
	}
	
	
	
	
	/**
	 * pop the last folder of the path
	 * @return string | null
	 * 
	 */ 
	public function pop() {
		
		return array_pop($this->allElements);
	}
	
	/**
	 * push a folder at the end of the path
	 * 
	 * @param	string		$folder
	 * 
	 * @return bab_Path
	 */ 
	public function push($folder) {
		
		array_push($this->allElements, $folder);
		
		return $this;
	}
	
	
	
	
	
	/**
	 * Create the folder if not exists
	 * @return boolean
	 * 
	 */ 
	public function createDir() {
		
		if (@is_dir($this->tostring())) {
			// the folder allready exists
			return true;
		}

		$removed = array();
		
		do {
			
			$pop = $this->pop();
			
			if (!$pop) {
				break;
			}
			
			$removed[] = $pop;
			
			try {
				$accessible = true;
				$this->isFolderWriteable();
			} catch(Exception $e) {
				$accessible = false;
			}
			
		} while(!$accessible);
		
		
		while ($folder = array_pop($removed)) {
			$this->push($folder);
			if (!bab_mkdir($this->tostring())) {
				return false;
			}
		}
		
		return true;
	}
	
	
	
	/**
	 * Delete the directory recusively
	 * 
	 * @throw bab_FolderAccessRightsException
	 * 
	 * @return bool
	 */
	public function deleteDir() {
		include_once dirname(__FILE__).'/delincl.php';
		
		$msgerror = '';
		$result = @bab_delDir($this->toString(), $msgerror);
		
		if (false === $result) {
			throw new bab_FolderAccessRightsException($msgerror);
		}
		
		return $result;
	}
}





class bab_FolderAccessRightsException extends Exception {

}
