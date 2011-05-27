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
 * @param string $str
 * @return string
 */
function bab_Path_encodeBase64($str)
{
	$dotPosition = mb_strrpos($str, '.');
   	if (false !== $dotPosition) {
    	$root = mb_substr($str, 0, $dotPosition);
        $ext = mb_substr($str, $dotPosition);
	        if (preg_match('/[a-zA-Z0-9]+/', $ext)) {
        	return bab_Path::BASE64 . base64_encode($root) . $ext;
        }
    }
    $str = base64_encode($str);
	return bab_Path::BASE64 . $str;
}

/**
 * @param string $str
 * @return string
 */
function bab_Path_encodeImap8Bit($str)
{
	$encodedStr = imap_8bit($str);
		// imap_8bit add line break every 76 chars, we don't need that for files names.
	$aLines = explode('=' . chr(13) . chr(10), $encodedStr);
	$encodedStr = implode('', $aLines);
	if ($encodedStr === $str) {
		return $str;
	}
	return bab_Path::QPRINT . $encodedStr;
}

/**
 * @param string $str
 * @return string
 */
function bab_Path_encodeQuotedPrintable($str)
{
	$encodedStr = quoted_printable_encode($str);
	if ($encodedStr === $str) {
		return $str;
	}
	return bab_Path::QPRINT.$encodedStr;
}


/**
 * Path object
 *
 */
class bab_Path implements SeekableIterator, Countable {

	const ISDIR		= 'isDir';
	const BASENAME	= 'getBasename';
	const ATIME		= 'getATime';
	const CTIME		= 'getCTime';
	const MTIME		= 'getMTime';


	/**
	 * Path elements
	 * @var array
	 */
	private $allElements = array();

	/**
	 *
	 * @var bool
	 */
	private $absolute = null;

	/**
	 * Iterator items
	 * @var array
	 */
	private $content = null;


	/**
	 * Iterator items sort params (optional)
	 * @see bab_Path::orderAsc()
	 * @see bab_Path::orderDesc()
	 *
	 * @var array
	 */
	private $contentSortParam = array();

	/**
	 * Iterator key
	 * @var int
	 */
	private $key = 0;


	const PREFIX_LENGTH = 6;
	const BASE64 	= 'BASE64';
	const QPRINT 	= 'QPRINT';
	const NONE		= '______';

	static private $encodingFunction = null;


	/**
	 * @param string $str
	 * @return string
	 */
	public static function decode($str)
	{
		// prefix is ascii on filesystem, mb_string is not needed
		$prefix = substr($str, 0, self::PREFIX_LENGTH);
		$value = substr($str, self::PREFIX_LENGTH);

		switch ($prefix) {
			case self::BASE64:

				$iPos = mb_strrpos($value, '.');
				if (false !== $iPos)
			    {
			    	$root = base64_decode(mb_substr($value, 0, $iPos));
			        $ext = mb_substr($value,$iPos);
			        return $root.$ext;

			    } else {
					return base64_decode($value);
			    }

			case self::QPRINT:
				return quoted_printable_decode($value);

			case self::NONE:
				return $value;
		}

		return $str;
	}



	/**
	 * @param string $str
	 * @return string
	 */
	public static function encode($str)
	{
		if (!isset(self::$encodingFunction)) {
			// autodetect best encoding method
			if (function_exists('quoted_printable_encode')) {
				self::$encodingFunction = 'bab_Path_encodeQuotedPrintable';
			} else if (function_exists('imap_8bit')) {
				self::$encodingFunction = 'bab_Path_encodeImap8Bit';
			} else {
				self::$encodingFunction = 'bab_Path_encodeBase64';
			}
		}

		$encodingFunction = self::$encodingFunction;
		$str = $encodingFunction($str);

		return $str;
	}



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

			// bab_path can extends SplFileInfo but with php 5.1.2, ovidentia require only 5.1.0
			// parent::__construct($this->toString());
	}


	public function __clone() {
	    $this->content = null;
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
	 *
	 * @return array
	 */
	private function getContent()
	{
		if (!isset($this->content))
		{
			$this->content = array();

			// load contents

			if ($this->isDir())
			{
				$d = dir($this->toString());

				while (false !== ($entry = $d->read())) {
				   if ('.' !== $entry && '..' !== $entry) {
				   		$path = new bab_Path($this->toString());
				   		$this->content[] = $path->push($entry);
				   }
				}
			}

			// apply sort parameters

			if ($this->contentSortParam) {
				usort($this->content, array($this, 'contentSort'));
			}
		}

		return $this->content;
	}

	/**
	 * @see	usort
	 * @param bab_Path $a
	 * @param bab_Path $b
	 * @return int			0,-1,1
	 */
	private function contentSort(bab_Path $a, bab_Path $b)
	{
		foreach($this->contentSortParam as $method => $order) {
			$va = $a->$method();
			$vb = $b->$method();

			if ($va === $vb) {
				continue;
			}

			if (is_string($va)) {
				return strcasecmp($va, $vb);
			}

			if (is_bool($va)) {
				$va = $va ? 1 : 0;
				$vb = $vb ? 1 : 0;
			}

			return ($va < $vb) ? (-1 * $order) : $order;
		}

		return 0;
	}

	/**
	 * Sort result of iterator
	 * @param string	$method		bab_Path::ISDIR | bab_Path::BASENAME | bab_Path::ATIME | bab_Path::CTIME | bab_Path::MTIME
	 * @return bab_Path
	 */
	public function orderAsc($method)
	{
		$this->contentSortParam[$method] = 1;
		return $this;
	}

	/**
	 * Sort result of iterator
	 * @param string	$method		bab_Path::ISDIR | bab_Path::BASENAME | bab_Path::ATIME | bab_Path::CTIME | bab_Path::MTIME
	 * @return bab_Path
	 */
	public function orderDesc($method)
	{
		$this->contentSortParam[$method] = -1;
		return $this;
	}


	/**
	 * @return int
	 */
	public function key()
	{
		return $this->key;
	}

	public function next()
	{
		$this->key++;
	}

	public function rewind()
	{
		$this->key = 0;
	}

	public function seek($position)
	{
		$this->key = $position;
	}

	/**
	 * @return bool
	 */
	public function valid()
	{
		$contents = $this->getContent();

		if (!isset($contents[$this->key]))
		{
			return false;
		}

		return true;
	}

	/**
	 *
	 * @return bab_Path
	 */
	public function current()
    {
    	return $this->content[$this->key];
    }


    public function count()
    {
		$this->getContent();
    	return count($this->content);
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
	 * Test if file is a directory
	 *
	 * @return bool
	 */
	public function isDir()
	{
		return is_dir($this->toString());
	}



	/**
	 * Test if file or directory is writable
	 * @return bool
	 */
	public function isWritable()
	{
		if ($this->isDir())
		{
			return $this->isFolderWriteable();
		} else {
			return is_writable($this->toString());
		}
	}



	/**
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
	 * this function will return the base name of the file
	 * @since 7.3.0
	 * @return string 		the last path element
	 */
	public function getBasename()
	{
		if (0 === count($this->allElements)) {
			return null;
		}

		return $this->allElements[count($this->allElements) -1];
	}


	/**
	 *
	 * @return string
	 */
	public function getRealPath()
	{
		return realpath($this->toString());
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
	 * push a folder or a relative path at the end of the path
	 *
	 * @param	string | bab_Path		$folder
	 *
	 * @return bab_Path
	 */
	public function push($folder) {

		if ($folder instanceOf bab_Path) {

			if ($folder->isAbsolute()) {
				throw new Exception('the path must be relative');
			}

			foreach($folder->allElements as $f) {
				array_push($this->allElements, $f);
			}
		} else {
			array_push($this->allElements, $folder);
		}
		return $this;
	}


	/**
	 * Shift the first folder of the path
	 * @return string | null
	 *
	 */
	public function shift() {
		return array_shift($this->allElements);
	}

	/**
	 * unshift a folder or a path at the begining of the path
	 *
	 * @param	string | bab_Path		$folder
	 *
	 * @return bab_Path
	 */
	public function unshift($folder) {

		if ($folder instanceOf bab_Path) {

			if ($this->isAbsolute()) {
				throw new Exception('the path must be relative');
			}

			foreach($folder->allElements as $f) {
				array_unshift($this->allElements, $f);
			}
		} else {
			array_unshift($this->allElements, $folder);
		}
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
