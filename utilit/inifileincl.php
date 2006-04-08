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
include_once "base.php";

/**
 * Each method in this class is related to a keyword of the inifile
 * the return value for all the methods is the same :
 *		array(
 *			'description'	=> (string)	Interationalized keyword description,
 *			'current'		=> (mixed) the current value
 *			'result'		=> (boolean) requirement fullfiled or not
 *			)
 */
class bab_inifile_requirements {
	
	function require_ov_version($value) {

		$db = &$GLOBALS['babDB'];
		$res = $db->db_query("select foption,fvalue from ".BAB_INI_TBL." where foption IN ('ver_major','ver_minor','ver_build')");

		$coreversion = array();
		while ($arr = $db->db_fetch_assoc($res)) {
			switch ($arr['foption']) {
				case 'ver_major':
					$coreversion[0] = $arr['fvalue'];
					break;
				case 'ver_minor':
					$coreversion[1] = $arr['fvalue'];
					break;
				case 'ver_build':
					$coreversion[2] = $arr['fvalue'];
					break;
			}
		}

		if (count($coreversion) == 3) {
			$ovidentia = $coreversion[0].'.'.$coreversion[1].'.'.$coreversion[2];
		}

		return array(
			'description'	=> bab_translate("Ovidentia version"),
			'current'		=> $ovidentia,
			'result'		=> version_compare($value, $ovidentia, '<=')
		);
	}

	function require_php_version($value) {
		return array(
			'description'	=> bab_translate("Php version"),
			'current'		=> PHP_VERSION,
			'result'		=> version_compare($value, PHP_VERSION, '<=')
		);
	}
}


class bab_inifile {

	/**
	 * Use a ini file in a zip file
	 * @param $zipfile absolute path to the zip file
	 * @param $inifile path to the ini file in the zip archive
	 */
	function getfromzip($zipfile, $inifile) {
		include_once $GLOBALS['babInstallPath']."utilit/zip.lib.php";

		$filename = substr( $inifile,(strrpos( $inifile,'/')+1));

		$zip = new Zip;
		$zipcontents = $zip->get_List($zipfile);
		foreach ($zipcontents as $k => $arr) {
			if ($inifile === $arr['filename']) {
				$inifileindex = $arr['index'];
				break;
			}
		}
		
		$zip->Extract($zipfile, $GLOBALS['babUploadPath'].'/tmp/', $inifileindex, false );
		$this->inifile( $GLOBALS['babUploadPath'].'/tmp/'.$filename);
		unlink($GLOBALS['babUploadPath'].'/tmp/'.$filename);
	}

	function inifile($file) {
		 $arr = parse_ini_file($file, true);
		 $this->inifile = $arr['general'];
		 if (isset($arr['addons'])) {
			$this->addons = $arr['addons'];
		 } else {
			$this->addons = array();
		 }
	}

	function getName() {
		if (isset($this->inifile['name'])) {
			return $this->inifile['name'];
		}
		return false;
	}

	function getDescription() {
		return $this->inifile['description'];
	}

	function getVersion() {
		if (isset($this->inifile['version'])) {
			return $this->inifile['version'];
		}
		return '';
	}

	/**
	 * The list of requirements specified in the ini file
	 * @return array
	 */
	function getRequirements() {
		static $return = array();
		if ($return)
			return $return;

		$requirementsObj = new bab_inifile_requirements();

		foreach($this->inifile as $keyword => $value) {
			$keyword = 'require_'.$keyword;
			if (method_exists ( $requirementsObj, $keyword )) {
				 $arr = $requirementsObj->$keyword($value);
				 $arr['required'] = $value;
				 $return[] = $arr;
			}
		}

		if ($this->addons) {

			$db = &$GLOBALS['babDB'];
			$res = $db->db_query("SELECT title, version FROM ".BAB_ADDONS_TBL." WHERE title IN('".implode("','",array_keys($this->addons))."') AND installed='Y' AND enabled='Y'");
			$installed = array();
			while ($arr = $db->db_fetch_assoc($res)) {
				$installed[$arr['title']] = $arr['version'];
			}
			
			foreach($this->addons as $name => $required) {
				if (isset($installed[$name])) {
					$return[] = array(
						'description'	=> bab_translate('Ovidentia addon').' : '.$name,
						'required'		=> $required,
						'current'		=> $installed[$name],
						'result'		=> version_compare($required, $installed[$name], '<=')
					);
				} else {
					$return[] = array(
						'description'	=> bab_translate('Ovidentia addon').' : '.$name,
						'required'		=> $required,
						'current'		=> bab_translate('Not installed or disabled'),
						'result'		=> false
					);
				}
			}
		}

		return $return;
	}

	/**
	 * Tables in database related to the given ini file
	 * @return array
	 */
	function getTables() {
		$return = array();
		if (!empty($this->inifile['db_prefix'])) {
			$res = $db->db_query("SHOW TABLES LIKE '".$this->inifile['db_prefix']."%'");
			while(list($tbl) = $db->db_fetch_array($res)) {
				$return[] = $tbl;
			}
		}
		return $return;
	}

	/**
	 * Test the validity of the requirements specified in the ini file on the current ovidentia version
	 * @return boolean
	 */
	function isValid() {
		
		$requirements = $this->getRequirements();
		foreach($requirements as $arr) {
			if (false === $arr['result']) {
				return false;
			}
		}
		return true;
	}
}


?>