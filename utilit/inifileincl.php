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
	
	/**
	 * Required ovidentia version from database or files if not available in database
	 * there is no version in database for new install
	 */
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
		} else {
			include_once $GLOBALS['babInstallPath'].'utilit/inifileincl.php';
			$ini = new bab_inifile();
			$ini->inifile($GLOBALS['babInstallPath'].'version.inc');
			$ovidentia = $ini->getVersion();
		}

		return array(
			'description'	=> bab_translate("Ovidentia version for upgrade"),
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

	function require_mysql_version($value) {

		$db = &$GLOBALS['babDB'];
		$arr = $db->db_fetch_assoc($db->db_query("show variables like 'version'"));
		
		$mysql = 'Undefined';
		
		if (preg_match('/([0-9\.]+)/', $arr['Value'], $matches)) {
			$mysql = $matches[1];
		}

		return array(
			'description'	=> bab_translate("MySQL version"),
			'current'		=> $mysql,
			'result'		=> version_compare($value, $mysql, '<=')
		);
	}

	function return_bytes($val) {
	   $val = trim($val);
	   $last = strtolower($val{strlen($val)-1});
	   switch($last) {
			// The 'G' modifier is available since PHP 5.1.0
			case 'g':
				$val *= 1024;
			case 'm':
				$val *= 1024;
			case 'k':
				$val *= 1024;
		}
		return $val;
	}

	function require_upload_max_file_size($value) {
	
		global $babDB;

		$upload_max_filesize = $this->return_bytes(ini_get('upload_max_filesize'));
		$post_max_size = $this->return_bytes(ini_get('post_max_size'));

		$current = $post_max_size > $upload_max_filesize ? $upload_max_filesize : $post_max_size;
		$current_display = sprintf("%dM",$current/1024/1024);
		$result = $current >= $this->return_bytes($value);
		
		$req = "SELECT maxfilesize from ".BAB_SITES_TBL." WHERE name='".$babDB->db_escape_string($GLOBALS['babSiteName'])."'";
		$res = $babDB->db_query($req);
		$babsite = $babDB->db_fetch_assoc($res);

		if (isset($babsite['maxfilesize'])) {
			$ov_upload = ((int) $babsite['maxfilesize'])*1024*1024;

			if ($current < $ov_upload) {
				$current_display = sprintf(bab_translate("You must configure ovidentia with a limit smaller or equal to the server limit : %s"),$current_display);
				$result = false;
			}
		}

		return array(
			'description'	=> bab_translate("Maximum upload file size"),
			'current'		=> $current_display,
			'result'		=> $result
		);
	}

	function require_images_directory($value) {

		$images = dirname($_SERVER['SCRIPT_FILENAME']).'/images/';
		$status = is_dir($images) && is_writable($images);
		
		return array(
			'description'	=> bab_translate("Images directory for articles"),
			'current'		=> $status ? bab_translate("Available") : bab_translate("Unavailable"),
			'result'		=> $status
		);
	}

	function require_versions_directory($value) {

		$core = dirname($_SERVER['SCRIPT_FILENAME']).'/';
		$status = is_writable($core);

		return array(
			'description'	=> bab_translate("Writable directory for upgrades"),
			'current'		=> $status ? bab_translate("Available") : bab_translate("Unavailable"),
			'result'		=> $status
		);
	}

	function require_lang_directory($value) {

		$core = dirname($_SERVER['SCRIPT_FILENAME']).'/lang/';
		$status = is_writable($core);

		return array(
			'description'	=> bab_translate("Writable lang directory"),
			'current'		=> $status ? bab_translate("Available") : bab_translate("Unavailable"),
			'result'		=> $status
		);
	}
	
	function require_upload_directory($value) {
	
		global $babDB;
	
		$current =  bab_translate("Unavailable");
		$status = false;
		
		// $babBody->babsite not available in upgrade
		// $ul = $GLOBALS['babBody']->babsite['uploadpath'];
		
		$req = "SELECT uploadpath from ".BAB_SITES_TBL." WHERE name='".$babDB->db_escape_string($GLOBALS['babSiteName'])."'";
		$res = $babDB->db_query($req);
		$babsite = $babDB->db_fetch_assoc($res);
		$ul = $babsite['uploadpath'];
		

		if (is_writable($ul)) {
			$current = bab_translate("The directory is writable but this is not the full pathname");

			if (preg_match('/^(\/|[a-zA-Z]{1}\:\\\\)/', $ul)) {
				$current = bab_translate("The addons directory is not writable");
				
				$addons = $ul.'/addons';
				if (!is_dir($addons)) {
					bab_mkdir($addons);
				}
				
				if (is_writable($addons)) {
					$current = bab_translate("Available");
					$status = true;
				}
			}
		}

		return array(
			'description'	=> bab_translate("Writable upload directory, absolute path"),
			'current'		=> $current,
			'result'		=> $status
		);
	}
	
	
	function require_register_globals($value) {
	
		return array(
			'description'	=> 'register_globals (php.ini)',
			'current'		=> ini_get('register_globals') ? 'On' : 'Off',
			'result'		=> !ini_get('register_globals')
		);
	}
	

	function require_mod_imap($value) {
		
		$status = extension_loaded('imap');
		return array(
			'description'	=> sprintf(bab_translate("%s php module"),'imap'),
			'current'		=> $status ? bab_translate("Available") : bab_translate("Unavailable"),
			'result'		=> $status
		);
	}

	function require_mod_xml($value) {
		
		$status = extension_loaded('xml');
		return array(
			'description'	=> sprintf(bab_translate("%s php module"),'xml'),
			'current'		=> $status ? bab_translate("Available") : bab_translate("Unavailable"),
			'result'		=> $status
		);
	}
	
	function require_mod_soap($value) {
		
		$status = extension_loaded('soap');
		return array(
			'description'	=> sprintf(bab_translate("%s php module"),'xml'),
			'current'		=> $status ? bab_translate("Available") : bab_translate("Unavailable"),
			'result'		=> $status
		);
	}
	
	function require_mod_gettext($value) {
		
		$status = extension_loaded('gettext');
		return array(
			'description'	=> sprintf(bab_translate("%s php module"),'gettext'),
			'current'		=> $status ? bab_translate("Available") : bab_translate("Unavailable"),
			'result'		=> $status
		);
	}

	function require_mod_calendar($value) {
		
		$status = extension_loaded('calendar');
		return array(
			'description'	=> sprintf(bab_translate("%s php module"),'calendar'),
			'current'		=> $status ? bab_translate("Available") : bab_translate("Unavailable"),
			'result'		=> $status
		);
	}

	function require_mod_ldap($value) {
		
		$status = extension_loaded('ldap');
		return array(
			'description'	=> sprintf(bab_translate("%s php module"),'ldap'),
			'current'		=> $status ? bab_translate("Available") : bab_translate("Unavailable"),
			'result'		=> $status
		);
	}

	function require_mod_curl($value) {
		
		$status = extension_loaded('curl');
		return array(
			'description'	=> sprintf(bab_translate("%s php module"),'curl'),
			'current'		=> $status ? bab_translate("Available") : bab_translate("Unavailable"),
			'result'		=> $status
		);
	}


	function require_mod_pdf($value) {
		
		$status = extension_loaded('pdf');
		return array(
			'description'	=> sprintf(bab_translate("%s php module"),'pdf'),
			'current'		=> $status ? bab_translate("Available") : bab_translate("Unavailable"),
			'result'		=> $status
		);
	}


	function require_mod_mysql($value) {
		
		$status = extension_loaded('mysql');
		return array(
			'description'	=> sprintf(bab_translate("%s php module"),'mysql'),
			'current'		=> $status ? bab_translate("Available") : bab_translate("Unavailable"),
			'result'		=> $status
		);
	}


	function require_mod_ftp($value) {
		
		$status = extension_loaded('ftp');
		return array(
			'description'	=> sprintf(bab_translate("%s php module"),'ftp'),
			'current'		=> $status ? bab_translate("Available") : bab_translate("Unavailable"),
			'result'		=> $status
		);
	}

	function require_mod_zlib($value) {
		
		$status = extension_loaded('zlib');
		return array(
			'description'	=> sprintf(bab_translate("%s php module"),'zlib'),
			'current'		=> $status ? bab_translate("Available") : bab_translate("Unavailable"),
			'result'		=> $status
		);
	}

	function require_mod_mcrypt($value) {
		
		$status = extension_loaded('mcrypt');
		return array(
			'description'	=> sprintf(bab_translate("%s php module"),'mcrypt'),
			'current'		=> $status ? bab_translate("Available") : bab_translate("Unavailable"),
			'result'		=> $status
		);
	}


	function require_mod_gd2($value) {
		
		$status = false;
		
		if (extension_loaded('gd') && function_exists('gd_info')) {
		   $ver_info = gd_info();
		   preg_match('/\d/', $ver_info['GD Version'], $match);
		   $status = 2 == $match[0];
	   }

		return array(
			'description'	=> sprintf(bab_translate("%s php module"),'gd (version 2)'),
			'current'		=> $status ? bab_translate("Available") : bab_translate("Unavailable"),
			'result'		=> $status
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
		 if ($arr = parse_ini_file($file, true)) {
			 $this->inifile = $arr['general'];
			 $this->addons = array();
			 if (isset($arr['addons'])) {
				$this->addons = $arr['addons'];
			 }
			 $this->recommendations = array();
			 if (isset($arr['recommendations'])) {
				$this->recommendations = $arr['recommendations'];
			 }
			 return true;
		 } else {
			return false;
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
	 * @param	string	$version (x.y.z)
	 * @return boolean
	 */
	function is_upgrade_allowed($version) {
		if (!isset($this->inifile['forbidden_upgrades'])) {
			return true;
		}
		
		$forbidden = explode(',',$this->inifile['forbidden_upgrades']);
		foreach($forbidden as $fn) {
			if ($version === trim($fn)) {
				return false;
			}
		}
		
		return true;
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
				 $arr['required'] = bab_translate($value);
				 $arr['recommended'] = false;
				 $return[] = $arr;
			}
		}

		

		foreach($this->recommendations as $keyword => $value) {
			$keyword = 'require_'.$keyword;
			if (method_exists ( $requirementsObj, $keyword )) {
				 $arr = $requirementsObj->$keyword($value);
				 $arr['required'] = false;
				 $arr['recommended'] = bab_translate($value);
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
						'recommended'	=> false,
						'current'		=> $installed[$name],
						'result'		=> version_compare($required, $installed[$name], '<=')
					);
				} else {
					$return[] = array(
						'description'	=> bab_translate('Ovidentia addon').' : '.$name,
						'required'		=> $required,
						'recommended'	=> false,
						'current'		=> bab_translate('Not installed or disabled'),
						'result'		=> false
					);
				}
			}
		}
	
		$order = array();
		foreach($return as $key => $value) {
			$order[$key] = $value['description'];
		}

		natcasesort($order);

		$return_ordered = array();
		foreach($order as $key => $value) {
			$return_ordered[] = $return[$key];
		}

		return $return_ordered;
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
			if (false === $arr['result'] && false !== $arr['required']) {
				return false;
			}
		}
		return true;
	}
}


?>