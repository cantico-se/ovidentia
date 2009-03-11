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

	public function getDescription() {
		return bab_translate('Zip archive manager');
	}

	/**
	 * Open file, if the file does not exists it will be overwriten
	 * @return bool
	 */
	public function open($filename) {

	}

	/**
	 * @return bool
	 */
	public function close() {

	}

	/**
	 * Add file to zip
	 * @param	string	$filename		The path to the file to add. 
	 * @param	string	$localname		File in zip archive
	 * 
	 */
	public function addFile($filename, $localname) {

	}


	/**
	 * Extract all files of archive to destination
	 * This function, maintains/forces the directory structure within the ZIP file.
	 * @param	string	$destination		full path
	 */
	public function extractTo($destination) {
		
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
		if ($this->add) {
			// write files to archive
			$this->zip->Add($this->add,1);
		}

		// record to file
		return file_put_contents($this->filename, $this->zip->get_file());
	}


	public function addFile($filename, $localname) {
		$this->add[] = array($localname, file_get_contents($filename));
	}

	
	public function extractTo($destination) {
		$this->zip->Extract($this->filename, $destination);
	}
}








/**
 * zip toolkit based on php ZipArchive from php ZIP extension
 */
class Func_Archive_Zip_ZipArchive extends Func_Archive_Zip {

	private $zip = null;
	
	public function __construct() {
		$this->zip = new ZipArchive;
	}

	public function getDescription() {
		return bab_translate('Zip archive manager with the zip php extension');
	}

	public function open($filename) {
		return $this->zip->open($filename, ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE);
	}

	/**
	 * Commit added files
	 */
	public function close() {
		return $this->zip->close();
	}


	public function addFile($filename, $localname) {
		$this->zip->addFile($filename, $localname);
	}

	
	public function extractTo($destination) {
		$this->zip->extractTo($destination);
	}
}