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
 *
 * If a method return NULL, the requirement will be ignored
 *
 * The requirements can be called by the installation script
 * all query to database need to be tested, if table not exist
 * the babDB object can be a babDatabase or a bab_dumpToDb
 *
 * @see babDatabase
 * @see bab_dumpToDb
 *
 */
class bab_inifile_requirements {
	
	/**
	 * Required ovidentia version from database or files if not available in database
	 * there is no version in database for new install
	 */
	function require_ov_version($value) {

		if (NULL !== $dbVersion = bab_getDbVersion()) {
			$ovidentia = $dbVersion;
		} else {
			$ini = new bab_inifile();
			$ini->inifile(dirname(dirname(__FILE__)).'/version.inc');
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
		
		$res = $db->db_queryWem("show variables like 'version'");
		
		if (!$res) {
		
			$mysql = '0.0';
		
			return array(
				'description'	=> bab_translate("MySQL version (error, version not found)"),
				'current'		=> $mysql,
				'result'		=> version_compare($value, $mysql, '<=')
			);
		}
		
		
		$arr = $db->db_fetch_assoc($res);
		
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
	   $last = mb_strtolower($val{mb_strlen($val)-1});
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
	
	
	
	function getIniMaxUpload() {
		$upload_max_filesize = bab_inifile_requirements::return_bytes(ini_get('upload_max_filesize'));
		$post_max_size = bab_inifile_requirements::return_bytes(ini_get('post_max_size'));
		return $post_max_size > $upload_max_filesize ? $upload_max_filesize : $post_max_size; 
	}
	
	
	

	function require_upload_max_file_size($value) {
	
		global $babDB;

		$current = bab_inifile_requirements::getIniMaxUpload();
		$current_display = sprintf("%dM",$current/1024/1024);
		$result = $current >= $this->return_bytes($value);
		
		$sitename = isset($GLOBALS['babSiteName']) ? $GLOBALS['babSiteName'] : 'Ovidentia';
		
		// table constants are not available in install script
		$req = "SELECT maxfilesize from bab_sites WHERE name=".$babDB->quote($sitename)."";
		$res = $babDB->db_queryWem($req);
		
		if (!$res) {
			// if table does not exist, ignore requirement
			return null;
		}
		
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
	
	/**
	 * @since 6.7.91
	 */
	function require_functionalities_directory($value) {

		$functionalities = dirname($_SERVER['SCRIPT_FILENAME']).'/functionalities/';
		$status = (is_dir($functionalities) && is_writable($functionalities)) || is_writable(dirname($_SERVER['SCRIPT_FILENAME']));
		
		return array(
			'description'	=> bab_translate("Writable functionalities directory"),
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
	
	
	function require_search_engine($value) {

		$SearchEngine = bab_searchEngineInfos();
		
		if (false === $SearchEngine) {
			$current = bab_translate("Unavailable");
		} else {
			$current = $SearchEngine['name'];
		}

		return array(
			'description'	=> bab_translate("Indexation and search engine"),
			'current'		=> $current,
			'result'		=> $current === $value
		);
	}
	
	
	function require_addons_directory($value) {

		include_once dirname(__FILE__).'/addonsincl.php';
		$folders = bab_getAddonsFilePath();
		
		$status = true;
		foreach($folders['loc_in'] as $folder) {
			if (!is_writable($folder)) {
				$status = false;
			}
		}

		return array(
			'description'	=> sprintf(bab_translate("Writable addons subfolders (%s)"), implode(', ',$folders['loc_in'])),
			'current'		=> $status ? bab_translate("Available") : bab_translate("Unavailable"),
			'result'		=> $status
		);
	}
	
	
	/**
	 * @since 6.7.91
	 */
	function require_headers_not_sent($value) {

		
		$status = headers_sent();

		return array(
			'description'	=> bab_translate("The scripts files must not output data before the end of the script, this error may appear if some of the php files have been modified"),
			'current'		=> $status ? bab_translate("Error") : bab_translate("Success"),
			'result'		=> !$status
		);
	}
	
	
	
	
	
	function require_upload_directory($value) {
	
		global $babDB;
	
		$current =  bab_translate("Unavailable");
		$status = false;
		
		// $babBody->babsite not available in upgrade
		// $ul = $GLOBALS['babBody']->babsite['uploadpath'];
		
		$sitename = isset($GLOBALS['babSiteName']) ? $GLOBALS['babSiteName'] : 'Ovidentia';
		
		
		// table constant are not available in install script
		
		$req = "SELECT uploadpath from bab_sites WHERE name=".$babDB->quote($sitename);
		$res = $babDB->db_queryWem($req);
		
		if (!$res) {
			// if table does not exists, ignore requirement
			return null;
		}
		
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
	
	
	function require_mod_mbstring($value) {
		
		$status = extension_loaded('mbstring');
		return array(
			'description'	=> sprintf(bab_translate("%s php module"),'mbstring'),
			'current'		=> $status ? bab_translate("Available") : bab_translate("Unavailable"),
			'result'		=> $status
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
	
	
	function require_mod_expect($value) {
		
		$status = extension_loaded('expect');
		return array(
			'description'	=> sprintf(bab_translate("%s php module"),'expect'),
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
	
	
	function require_mysql_character_set_database($value) {

		$value = mb_strtolower($value);
		
		global $babDB;
		$res = $babDB->db_queryWem("show variables like 'character_set_database'");
		
		
		$charset = 'Undefined';
		
		if ($arr = $babDB->db_fetch_array($res)) {
			$charset = mb_strtolower($arr[1]);
		} else {
			// undefined on old mysql version
			$charset = 'latin1';
		}
		
		$arr_values = preg_split('/[\s,]+/', $value);

		return array(
			'description'	=> bab_translate("MySQL database charset"),
			'current'		=> $charset,
			'result'		=> in_array($charset, $arr_values)
		);
	}
	
	
	function require_mysql_collation_database($value) {

		$value = mb_strtolower($value);
		
		global $babDB;
		$res = $babDB->db_queryWem("show variables like 'collation_database'");
		
		
		$collation = false;
		
		if ($arr = $babDB->db_fetch_array($res)) {
			$collation = mb_strtolower($arr[1]);
		} else {
			// undefined on old mysql version
			$collation = 'latin1_swedish_ci';
		}
		
		$arr_values = preg_split('/[\s,]+/', $value);

		return array(
			'description'	=> bab_translate("MySQL database collation"),
			'current'		=> $collation,
			'result'		=> in_array($collation, $arr_values)
		);
	}
	
	
	function require_mysql_max_allowed_packet($value) {

		$value = $this->return_bytes($value);
		
		global $babDB;
		$res = $babDB->db_queryWem("show variables like 'max_allowed_packet'");
		
		
		$max_allowed_packet = 'Undefined';
		
		if ($arr = $babDB->db_fetch_array($res)) {
			$max_allowed_packet = (int) $arr[1];
		}

		return array(
			'description'	=> bab_translate("MySQL server variable max_allowed_packet"),
			'current'		=> sprintf("%dM",$max_allowed_packet/1024/1024),
			'result'		=> $value <= $max_allowed_packet
		);
	}
	
	
	function require_mysql_sql_mode($value) {

		
		global $babDB;
		$res = $babDB->db_queryWem("show variables like 'sql_mode'");
		
		if (!$res) {
			return null;
		}
		
		$current = 'Undefined';
		
		if ($arr = $babDB->db_fetch_array($res)) {
			if (!empty($arr[1])) {
				$current = mb_strtolower($arr[1]);
			}
		}

		return array(
			'description'	=> bab_translate("MySQL server variable : sql_mode"),
			'current'		=> bab_translate($current),
			'result'		=> $value === $current
		);
	}
}







class bab_inifile_requirements_html
	{
	var $requirements;
	var $altbg = false;

	function bab_inifile_requirements_html()
		{
		
		$this->t_requirements = bab_translate("Requirements");
		$this->t_recommended = bab_translate("Recommended");
		$this->t_install = bab_translate("Install");
		$this->t_required = bab_translate("Required value");
		$this->t_current = bab_translate("Current value");
		$this->t_addon = bab_translate("Addon");
		$this->t_description = bab_translate("Description");
		$this->t_version = bab_translate("Version");
		$this->t_ok = bab_translate("Ok");
		$this->t_error = bab_translate("Error");

		}



	function getnextreq() {
		if (list(,$arr) = each($this->requirements)) {
			$this->altbg = !$this->altbg;
			$this->description = bab_toHtml($arr['description']);
			$this->recommended = bab_toHtml($arr['recommended']);
			$this->required = bab_toHtml($arr['required']);
			$this->current = bab_toHtml($arr['current']);
			$this->result = $arr['result']; 
			return true;
		}
		return false;
	}
}














class bab_inifile {

	var $addons;
	var $recommendations = array();
	var $functionalities;
	var $customscript = array();
	

	/**
	 * Use a ini file in a zip file
	 * @param $zipfile absolute path to the zip file
	 * @param $inifile path to the ini file in the zip archive
	 */
	function getfromzip($zipfile, $inifile) {
		include_once $GLOBALS['babInstallPath']."utilit/zip.lib.php";
		include_once $GLOBALS['babInstallPath']."utilit/addonsincl.php";

		$addon_paths = bab_getAddonsFilePath();
		$program_path = $addon_paths['loc_out'][0].'/';
		

		$filename = mb_substr( $inifile,(mb_strrpos( $inifile,'/')+1));

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

		
		// si le ini contiens un preinstall script, le chercher dans le même repertoire
		
		if (isset($this->inifile['preinstall_script'])) {
		
			$preinstall_script = $this->inifile['preinstall_script'];
			
			$inifileindex = false;
			
			foreach ($zipcontents as $k => $arr) {
				
				if (0 === mb_strpos($arr['filename'], $program_path)) {
					$archive_filename = mb_substr($arr['filename'], 9);
					
					if ($preinstall_script === $archive_filename) {
						$inifileindex = $arr['index'];
						break;
					}
				}
			}
			
			
			$name = $this->getName();
			
			if (false === $name) {
				$name = basename($zipfile);
			}
			
			
			if ($inifileindex) {
				$zip->Extract($zipfile, $GLOBALS['babUploadPath'].'/tmp/', $inifileindex, false );
				$this->addCustomScript($name, $GLOBALS['babUploadPath'].'/tmp/'.$preinstall_script);
				unlink($GLOBALS['babUploadPath'].'/tmp/'.$preinstall_script);
			}
		}
	}



	function parse($file) {

		if (!file_exists($file) || !is_readable($file)) {
			$this->inifile = array();
			return false;
		}


		if ($arr = parse_ini_file($file, true)) {
			if (isset($arr['general']['encoding'])) {
				// the charset of the ini file

				switch($arr['general']['encoding']) {
					case 'ISO-8859-15':
					case 'UTF-8':
						if (function_exists('bab_getStringAccordingToDataBase')) {
							// for ovidentia core new install, the function is not present but version.inc is ascii only
							$arr = bab_getStringAccordingToDataBase($arr, $arr['general']['encoding']);
						}
						break;

					default:
						trigger_error(bab_sprintf('the encoding specified in file "%s" is not supported, fallback to ISO-8859-15',$file));
						break;
				}
			}

			return $arr;
		}


		return false;
	}






	function inifile($file) {

		 if ($arr = $this->parse($file)) {

			$this->inifile = $arr['general'];

			
			
			
			 
			 $this->addons = array();
			 if (isset($arr['addons'])) {
				$this->addons = $arr['addons'];
			 }
			 
			 $this->recommendations = array();
			 if (isset($arr['recommendations'])) {
				$this->recommendations = $arr['recommendations'];
			 }
			 
			$this->functionalities = array();
			if (isset($arr['functionalities'])) {
				$this->functionalities = $arr['functionalities'];
			}


			if (!isset($this->inifile['mysql_character_set_database']) && !isset($this->recommendations['mysql_character_set_database'])) {
				// if charset test is not here, verify that the database is latin1
				$this->inifile['mysql_character_set_database'] = 'latin1';
			}


			
			if (isset($this->inifile['preinstall_script'])) {
			
				$name = $this->getName();
			
				if (false === $name) {
					$name = basename(dirname($file));
				}
		
				$preinstall_script = $this->inifile['preinstall_script'];
				$this->addCustomScript($name, dirname($file).'/'.$preinstall_script);
			}
			
			 
			return true;
		} else {
			return false;
		}
	}
	
	
	/**
	 * @return boolean
	 */
	function inifileGeneral($file) {
	
		if ($arr = $this->parse($file)) {
			$this->inifile = $arr['general'];
			return true;
		}
		
		return false;
	}
	
	
	
	

	function getName() {
		if (isset($this->inifile['name'])) {
			return $this->inifile['name'];
		}
		return false;
	}

	function getDescription() {

		$lang = 'description.'.$GLOBALS['babLanguage'];
	
		if (isset($this->inifile[$lang])) {
			return $this->inifile[$lang];
		} elseif(isset($this->inifile['description.en'])) {
			return $this->inifile['description.en'];
		}
		
		if (isset($this->inifile['description'])) {
			return $this->inifile['description'];
		}
	
		return '';
	}

	function getVersion() {
	
		if (isset($this->inifile['version'])) {
		
			if (preg_match('/\$Name$/', $this->inifile['version'], $m)) {
				$tag = trim($m[1]);
				
				if (empty($tag)) {
					// ongoing dev
					return '';
				}
				
				$tag = str_replace('version-', '', $tag);
				$version = str_replace('-', '.', $tag);
				
				return $version;
			}

		
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
	 * Add a custom script for requirements
	 * for addons
	 * @param	string	$addonname		used as a static for result storage
	 * @param	string	$filepath
	 *
	 * @return 	boolean
	 */
	function addCustomScript($addonname, $filepath) {
	
		if (!file_exists($filepath)) {
			return false;
		}
		
		static $custom_script_result = array();
		
		if (!isset($custom_script_result[$addonname])) {
			$custom_script_result[$addonname] = include $filepath;
		} 
	
		$arr = $custom_script_result[$addonname];
		
		if (!$arr || !is_array($arr)) {
			trigger_error('preinstall script must return an array');
			return false;
		}
		
		
		foreach($arr as $prerequisit) {
		
			if (
					!isset($prerequisit['description']) 
				|| 	!isset($prerequisit['required']) 
				|| 	!isset($prerequisit['recommended']) 
				||	!isset($prerequisit['current']) 
				||	!isset($prerequisit['result']) 
			) {
				trigger_error('preinstall script must return an array prerequisit and each prerequisit must contains the keys : description, required, recommended, current, result');
				return false;
			}
			
			
			$this->customscript[] = $prerequisit;
		}
		
		return true;
	}
	
	
	
	

	/**
	 * The list of requirements specified in the ini file
	 * @return array
	 */
	function getRequirements() {

		$return = array();

		$requirementsObj = new bab_inifile_requirements();

		foreach($this->inifile as $keyword => $value) {
			$keyword = 'require_'.$keyword;
			if (method_exists ( $requirementsObj, $keyword )) {
				$arr = $requirementsObj->$keyword($value);
				if (is_array($arr)) {
				 	$arr['required'] = bab_translate($value);
				 	$arr['recommended'] = false;
				 	$return[] = $arr;
				}
			}
		}

		

		foreach($this->recommendations as $keyword => $value) {
			$keyword = 'require_'.$keyword;
			if (method_exists ( $requirementsObj, $keyword )) {
				$arr = $requirementsObj->$keyword($value);
				if (is_array($arr)) {
					$arr['required'] = false;
					$arr['recommended'] = bab_translate($value);
					$return[] = $arr;
				}
			}
		}


		
		$return = array_merge($return, $this->getAddonsRequirements());
		
		
		
		if ($this->functionalities) {

			foreach($this->functionalities as $name => $value) {
			
				// value can be "Available" or "Recommended"
			
				$obj = @bab_functionality::get($name);
				
				switch(mb_strtolower($value)) {
					case 'available':
						$required = bab_translate('Available');
						$recommended = false;
						break;
						
					case 'recommended':
						$required = false;
						$recommended = bab_translate('Available');
						break;
				}
			
				if (false === $obj) {
					$return[] = array(
						'description'	=> bab_translate('Functionality').' : '.$name,
						'required'		=> $required,
						'recommended'	=> $recommended,
						'current'		=> bab_translate('Not installed or disabled'),
						'result'		=> false
					);
				} else {
					$return[] = array(
						'description'	=> bab_translate('Functionality').' : '.$name,
						'required'		=> $required,
						'recommended'	=> $recommended,
						'current'		=> bab_translate('Available'),
						'result'		=> true
					);
				}
			}
		}
		
		
		
		
		if ($this->customscript) {
			foreach($this->customscript as $prerequisit) {
				$return[] = $prerequisit;
			}
		}
		
		
		
		
	
		$order = array();
		foreach($return as $key => $value) {
			$order[$key] = $value['description'];
		}



		if (class_exists('bab_sort')) {
			bab_sort::natcasesort($order);
		}

		$return_ordered = array();
		foreach($order as $key => $value) {
			$return_ordered[] = $return[$key];
		}

		return $return_ordered;
	}
	
	
	
	
	
	
	
	/**
	 * @return array
	 */
	function getAddonsRequirements() {
	
		$return = array();
		
		
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
						'name'			=> $name,
						'description'	=> bab_translate('Ovidentia addon').' : '.$name,
						'required'		=> $required,
						'recommended'	=> false,
						'current'		=> $installed[$name],
						'result'		=> version_compare($required, $installed[$name], '<=')
					);
				} else {
					$return[] = array(
						'name'			=> $name,
						'description'	=> bab_translate('Ovidentia addon').' : '.$name,
						'required'		=> $required,
						'recommended'	=> false,
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
			if (false === $arr['result'] && false !== $arr['required']) {
				return false;
			}
		}
		return true;
	}
	
	
	/**
	 * check validity for new installation
	 * execution is out of ovidentia context
	 * this method need to bring all required environement before the validity test
	 *
	 * @param	bab_dumpToDb	$installDB	a database connexion with method db_query, db_fetch_array, db_fetch_assoc, quote, db_queryWem
	 * @param	string			&$error
	 *
	 * @return boolean
	 */
	function isInstallValid($installDB, &$error) {
	
		if (!function_exists('bab_translate')) {
			function bab_translate($str) {
				return $str;
			}
		}
		
		if (!function_exists('bab_getDbVersion')) {
			function bab_getDbVersion() {
				return null;
			}
		}
		
		
		if (!class_exists('babDatabase')) {
			$GLOBALS['babDB'] = $installDB;
		}
		

		
		$requirements = $this->getRequirements();
		foreach($requirements as $arr) {
			if (false === $arr['result'] && false !== $arr['required']) {
				$error = sprintf('error, %s is "%s" but "%s" is required', $arr['description'], $arr['current'], $arr['required']);
				return false;
			}
		}
		return true;
	}
	
	
	
	
	/**
	 * The list of requirements specified in the ini file
	 * @return string
	 */
	function getRequirementsHtml() {

		$temp = new bab_inifile_requirements_html();
		$temp->requirements = $this->getRequirements();
		return bab_printTemplate($temp,"requirements.html");
	}
	
}


