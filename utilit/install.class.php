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
 * Install files in Ovidentia addons or distribution upgrade by zip archive or folder
 *
 */
class bab_ZipInstall {

	private $archive = null;

	private $folderpath = null;


	/**
	 * Set a zip archive file path
	 * @param	string	$archive
	 * @return	bab_ZipInstall
	 */
	public function setArchive($archive) {
		$this->archive = $archive;
		return $this;
	}

	/**
	 * Set the install source from an allready unziped folder
	 * @param	string	$folderpath
	 */
	public function setFolder($folderpath) {
		$this->archive = null;
		$this->folderpath = $folderpath;
	}

	/**
	 * Get folder path of install source
	 * if the source is a zip archive, this method will return a folder path with the temporary extracted files of the archive
	 * @return string
	 */
	public function getFolder() {
		if (null === $this->folderpath) {
			$this->folderpath = $this->temporaryExtractArchive();
		}

		return $this->folderpath;
	}


	/**
	 * Extract the archive into a temporary folder
	 * @return string full path to a temporary folder
	 */
	private function temporaryExtractArchive() {

		if (null === $this->archive) {
			return null;
		}

		$temp = $GLOBALS['babUploadPath'].'/tmp';

		if (!is_dir($temp)) {
			bab_mkdir($temp);
		}

		$temp.= '/'.__CLASS__.session_id();

		if (is_dir($temp)) {
			include_once dirname(__FILE__).'/delincl.php';
			$error = '';
			bab_deldir($temp, $error);
		}

		bab_mkdir($temp);

		$zip = bab_functionality::get('Archive/Zip');
		$zip->open($this->archive);
		$zip->extractTo($temp);

		return $temp;
	}
}




