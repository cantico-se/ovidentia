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
 * Archive toolkit container
 */
class Func_Archive extends bab_functionality {
	
	public function getDescription() {
		return bab_translate('Archive manager');
	}
}





/**
 * Interface required for a zip functionality
 */
class Func_Archive_Zip extends Func_Archive {
	
	/**
	 * charset of files names added in archive
	 * @see Func_Archive_Zip::setCharset()
	 * @var string | null
	 */
	protected $charset = null;
	

	public function getDescription() {
		return bab_translate('Zip archive manager');
	}

	/**
	 * Open file, if the file does not exists it will be overwriten
	 * @return bool
	 */
	public function open($filename) {}

	/**
	 * @return bool
	 */
	public function close() {}

	/**
	 * Add file to zip
	 * set charset of files names with the setCharset method, by default, the charset is deducted from the browser OS of the user, aproximatively with the user agent string
	 * 
	 * @param	string	$filename		The path to the file to add. 
	 * @param	string	$localname		File in zip archive, according to ovidentia database charset
	 * 
	 */
	public function addFile($filename, $localname) {}


	/**
	 * Extract all files of archive to destination
	 * This function, maintains/forces the directory structure within the ZIP file.
	 * @param	string	$destination		full path
	 */
	public function extractTo($destination) {}
	
	
	/**
	 * Set charset of new filenames added in archive
	 * to accept different charset, the iconv function is needed
	 * @param string | null $charset
	 * @return Func_Archive_Zip
	 */
	public function setCharset($charset)
	{
		$this->charset = $charset;
		return $this;
	}
	
	
	/**
	 * Encode filename before adding into zip archive
	 * 
	 * @param	string	$filename
	 * @return string
	 */
	protected function encode($filename)
	{
		if (function_exists('iconv')) {
			
			if (null === $this->charset) 
			{
				bab_locale();
				
				if ('windows' === bab_browserOS()) {
					$this->charset = 'IBM437';
				} else {
					$this->charset = 'ASCII';
				}
			}
			
			$filename = iconv(bab_charset::getDatabase(), $this->charset.'//TRANSLIT', $filename);

		} else {
			$filename = bab_removeDiacritics($filename);
		}
		
		return $filename;
	}
}




/**
 * zip toolkit based on zlib
 * Slow but compliant
 */
class Func_Archive_Zip_Zlib extends Func_Archive_Zip {

	private $zip 			= null;
	private $filename 		= null;
	private $add	 		= array();

	public function __construct() {
		include_once dirname(__FILE__)."/zip.lib.php";
		$this->zip = new Zip;
	}

	public function getDescription() {
		return bab_translate('Zip archive manager with the zlib php extension');
	}

	public function open($filename) {
		$this->filename = $filename;
		return true;
	}

	/**
	 * Commit added files
	 */
	public function close() {

		$result = true;

		if ($this->add) {
			// write files to archive
			$this->zip->Add($this->add,1);

			// record to file
			$result = file_put_contents($this->filename, $this->zip->get_file());
		}

		return $result;
	}


	public function addFile($filename, $localname) {
		$this->add[] = array($this->encode($localname), file_get_contents($filename));
	}

	
	public function extractTo($destination) {
		$this->zip->Extract($this->filename, $destination);
	}
}








/**
 * zip toolkit based on php ZipArchive from php ZIP extension
 * close an reopen zip file when limit execeded
 */
class Func_Archive_Zip_ZipArchive extends Func_Archive_Zip {

	private $zip 				= null;
	private $opened_filename 	= null;
	private $add_file_limit 	= 128;
	
	public function __construct()
	{
		if (class_exists('ZipArchive')) {
			$this->zip = new ZipArchive;
		} else {
			throw new Exception(bab_translate('The php zip extension is not available'));
		}
	}



	/**
	 * @return string
	 */
	public function getDescription()
	{
		return bab_translate('Zip archive manager with the zip php extension');
	}


	
	/**
	 * Opens a zip archive file.
	 * 
	 * @param string $filename The full pathname of the this archive to open (or create).
	 * @param string $mode     The optional opening mode ('r' or 'w'), by default it will be 'r' if filename
	 *                         is the name of a readable file, 'w' otherwise.
	 *
	 * @return bool | int      True on success.
	 */
	public function open($filename, $mode = null)
	{
		if (!isset($this->zip)) {
			throw new Exception(bab_translate('Trying to open an uninitialized ZipArchive.'));
		}
		$this->opened_filename = $filename;
		
		if (!isset($mode)) {
			if (is_readable($filename)) {
				$mode = 'r';
			} else {
				$mode = 'w';
			}
		}
		switch ($mode) {
			case 'r':
				return $this->zip->open($filename);
			case 'w':
				return $this->zip->open($filename, ZIPARCHIVE::OVERWRITE | ZIPARCHIVE::CREATE);
		}
	}


	/**
	 * Closes and open the zip archive.
	 * 
	 * This is needed by addFile() to avoid the following problem:
	 * " When adding a file to your zip, the file is opened and stays open.
	 *   When adding over 1024 files (depending on your open files limit)
	 *   the server stops adding files, resulting in a status 11 in your zip Archive.
	 *   There is no warning when exceeding this open files limit with addFile."
	 *  	-- Comment by aartdebruijn at gmail dot com on php.net
	 * 
	 * @return bool | int
	 */
	private function reopen()
	{
		if (null === $this->opened_filename) {
			return null;
		}

		$filename = $this->opened_filename;

		if ( !$this->close() ) {
            return false;
        }
		
		$this->opened_filename = $filename;
		$this->add_file_limit = 128;
		return $this->zip->open($filename);
	}


	/**
	 * Commit added files
	 */
	public function close()
	{
		if (!isset($this->zip)) {
			throw new Exception(bab_translate('Trying to close an uninitialized ZipArchive.'));
		}
		$this->opened_filename = null;
		$this->add_file_limit = 128;
		return $this->zip->close();
	}


	/**
	 * Adds the specified file to the archive from a given path. 
	 * @param string $filename  The full path to the file to add.
	 * @param string $localname The relative pathname of the file in the archive. 
	 */
	public function addFile($filename, $localname)
	{
		if (!isset($this->zip)) {
			throw new Exception(bab_translate('Trying to add a file to an uninitialized ZipArchive.'));
		}

		if ($this->add_file_limit <= 0) {
			$this->reopen();
		}

		$this->zip->addFile($filename, $this->encode($localname));
		$this->add_file_limit--;
	}

	
	/**
	 * 
	 * @param string $destination The full pathanme of the folder where the archive should be extracted.
	 * 
	 * @return bool True on success, false on failure.
	 */
	public function extractTo($destination)
	{
		if (!isset($this->zip)) {
			throw new Exception(bab_translate('Trying to extract from an uninitialized ZipArchive.'));
		}
		$result = $this->zip->extractTo($destination);
		return $result;
	}
}
