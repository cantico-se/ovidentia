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


require_once dirname(__FILE__).'/path.class.php';

/**
 * @package inifile
 *
 * Each method in this class is related to a keyword of the inifile
 * the return value for all the methods is the same :
 *		array(
 *			'description'	=> (string)	Interationalized keyword description,
 *			'current'		=> (mixed) the current value
 *			'result'		=> (boolean) requirement fullfiled or not
 * 			'error'			=> (string) optional error message, may be null or unset
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
			'result'		=> self::multicompare($ovidentia, $value)
		);
	}

	function require_php_version($value) {

		$error = null;
		$status = version_compare($value, PHP_VERSION, '<=');

		if (!$status) {
			$error = str_replace('%version%', $value, bab_translate('Your version of PHP is too low, version %version% is required'));
		}

		return array(
			'description'	=> bab_translate("Php version"),
			'current'		=> PHP_VERSION,
			'result'		=> $status,
			'error'			=> $error
		);


	}

	function require_mysql_version($value) {


		$db = &$GLOBALS['babDB'];
		$error = null;

		$res = $db->db_queryWem("show variables like 'version'");

		if (!$res) {

			$mysql = '0.0';
			$status = false;
			$error = bab_translate('The mysql version could not be found');
		} else {


			$arr = $db->db_fetch_assoc($res);

			$mysql = 'Undefined';

			if (preg_match('/([0-9\.]+)/', $arr['Value'], $matches)) {
				$mysql = $matches[1];
			}

			$status = version_compare($value, $mysql, '<=');
			if (!$status) {
				$error = str_replace('%version%', $value, bab_translate('Your version of MySQL is too low, version %version% is required'));
			}
		}


		return array(
			'description'	=> bab_translate("MySQL version"),
			'current'		=> $mysql,
			'result'		=> $status,
			'error'			=> $error
		);
	}



	function require_mysql_granted_privileges($value) {


		$db = &$GLOBALS['babDB'];
		$error = null;

		$res = $db->db_queryWem("SHOW GRANTS");

		$required_privileges = preg_split('/\s*,\s*/', $value);
		$current = array();

		if (!$res) {

			$current = bab_translate('No access to granted privileges');
			$status = true;


		} else {

			while ($arr = $db->db_fetch_array($res))
			{
				if (preg_match('/^GRANT\s([A-Z\s,]+)\sON/', $arr[0], $m))
				{
					foreach(preg_split('/\s*,\s*/', strtoupper($m[1])) as $privilege)
					{
						$current[$privilege] = $privilege;
					}
				}
			}


			$status = isset($current['ALL PRIVILEGES']) || isset($current['USAGE']);

			if (!$status)
			{
				$status = true;
				foreach($required_privileges as $privilege)
				{
					if (!isset($current[$privilege]))
					{
						$error = sprintf(bab_translate('Missing the %s mysql privilege'), $privilege);
						$status = false;
						break;
					}
				}
			}

			$current = implode(', ', $current);
		}



		return array(
			'description'	=> bab_translate("MySQL granted privileges"),
			'current'		=> $current,
			'result'		=> $status,
			'error'			=> $error
		);
	}





	public static function return_bytes($val) {
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



	public static function getIniMaxUpload() {
		$upload_max_filesize = bab_inifile_requirements::return_bytes(ini_get('upload_max_filesize'));
		$post_max_size = bab_inifile_requirements::return_bytes(ini_get('post_max_size'));
		return $post_max_size > $upload_max_filesize ? $upload_max_filesize : $post_max_size;
	}




	function require_upload_max_file_size($value) {

		global $babDB;
		$error = null;

		$current = bab_inifile_requirements::getIniMaxUpload();
		$current_display = sprintf("%dM",$current/1024/1024);
		$result = $current >= self::return_bytes($value);

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
				$result = false;
			}
		}

		if (!$result) {
			$error = sprintf(bab_translate("You must configure ovidentia with a limit smaller or equal to the server limit : %s"),$current_display);
		}



		return array(
			'description'	=> bab_translate("Maximum upload file size"),
			'current'		=> $current_display,
			'result'		=> $result,
			'error' 		=> $error
		);
	}

	function require_images_directory($value) {

		$images = new bab_Path(realpath('.'), 'images');
		$error = null;

		try {
			$status = $images->isFolderWriteable();
		} catch(Exception $e) {
			$status = false;
			$error = $e->getMessage();
		}

		return array(
			'description'	=> bab_translate("Images directory for articles"),
			'current'		=> $status ? bab_translate("Available") : bab_translate("Unavailable"),
			'result'		=> $status,
			'error'			=> $error
		);
	}

	/**
	 * @since 6.7.91
	 */
	function require_functionalities_directory($value) {

		$functionalities = new bab_Path(realpath('.'), 'functionalities');
		$root = new bab_Path(realpath('.'));
		$error = null;

		try {
			$status = $functionalities->isFolderWriteable() || $root->isFolderWriteable();
		} catch(Exception $e) {
			$status = false;
			$error = $e->getMessage();
		}



		return array(
			'description'	=> bab_translate("Writable functionalities directory"),
			'current'		=> $status ? bab_translate("Available") : bab_translate("Unavailable"),
			'result'		=> $status,
			'error'			=> $error
		);
	}




	function require_versions_directory($value) {

		$core = new bab_Path(realpath('.'));
		$error = null;

		try {
			$status = $core->isFolderWriteable($core);
		} catch(Exception $e) {
			$status = false;
			$error = $e->getMessage();
		}

		return array(
			'description'	=> bab_translate("Writable directory for upgrades"),
			'current'		=> $status ? bab_translate("Available") : bab_translate("Unavailable"),
			'result'		=> $status,
			'error'			=> $error
		);
	}

	function require_lang_directory($value) {

		$core = new bab_Path(realpath('.'),'lang');
		$error = null;

		try {
			$status = $core->isFolderWriteable();
		} catch(Exception $e) {
			$status = false;
			$error = $e->getMessage();
		}

		return array(
			'description'	=> bab_translate("Writable lang directory"),
			'current'		=> $status ? bab_translate("Available") : bab_translate("Unavailable"),
			'result'		=> $status,
			'error'			=> $error
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

	/**
	 * addons folders tests, disabled in CVS mode
	 * @param string $value
	 * @return array
	 */
	function require_addons_directory($value) {

		$loc_in = array(
			$GLOBALS['babInstallPath'].'addons',
			$GLOBALS['babInstallPath'].'lang/addons',
			$GLOBALS['babInstallPath'].'styles/addons',
			$GLOBALS['babInstallPath'].'skins/ovidentia/templates/addons',
			$GLOBALS['babInstallPath'].'skins/ovidentia/ovml/addons',
			$GLOBALS['babInstallPath'].'skins/ovidentia/images/addons',
			'skins'
		);
		

		$error = null;
		$status = true;
		foreach($loc_in as $folder) {

			$folder = new bab_Path(realpath('.'), $folder);

			$cvsfolder = clone $folder;
			$cvsfolder->push('CVS');

			if (file_exists($cvsfolder->toString())) {

				break;

			} else {

				try {
					$status = $folder->isFolderWriteable();
				} catch(Exception $e) {
					$status = false;
					$error = $e->getMessage();
					break;
				}
			}
		}

		return array(
			'description'	=> sprintf(bab_translate("Writable addons subfolders (%s)"), implode(', ',$loc_in)),
			'current'		=> $status ? bab_translate("Available") : bab_translate("Unavailable"),
			'result'		=> $status,
			'error'			=> $error
		);
	}


	/**
	 * @since 6.7.91
	 */
	function require_headers_not_sent($value) {


		$status = !defined('BAB_INSTALL_SCRIPT_BEGIN') && headers_sent();

		return array(
			'description'	=> bab_translate("The scripts files must not output data before the end of the script, this error may appear if some of the php files have been modified"),
			'current'		=> $status ? bab_translate("Error") : bab_translate("Success"),
			'result'		=> !$status
		);
	}





	function require_upload_directory($value) {

		global $babDB;


		$status = false;
		$error = null;

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
		$ul = new bab_Path($babsite['uploadpath']);

		try {
			$status = $ul->isFolderWriteable();
		} catch(Exception $e) {
			$status = false;
			$error = $e->getMessage();
		}


		if ($status) {

			if ($ul->isAbsolute()) {

				$addons = new bab_Path($ul->toString(), 'addons');
				if (!is_dir($addons->toString())) {
					bab_mkdir($addons->toString());
				}

				try {
					$status = $addons->isFolderWriteable();
				} catch(Exception $e) {
					$status = false;
					$error = $e->getMessage();
				}

			} else {

				$status = false;
				$error = bab_translate("The directory is writable but is not an absolute pathname");
			}
		}

		return array(
			'description'	=> bab_translate("Writable upload directory, absolute path"),
			'current'		=> $status ? bab_translate("Available") : bab_translate("Unavailable"),
			'result'		=> $status,
			'error'			=> $error
		);
	}


	function require_memory_limit($value) {

		$required = self::return_bytes($value);
		$current = self::return_bytes(ini_get('memory_limit'));

		return array(
			'description'	=> 'memory_limit (php.ini)',
			'current'		=> ini_get('memory_limit'),
			'result'		=> $current >= $required
		);
	}


	function require_register_globals($value) {

		return array(
			'description'	=> 'register_globals (php.ini)',
			'current'		=> ini_get('register_globals') ? 'On' : 'Off',
			'result'		=> !ini_get('register_globals')
		);
	}

	function require_magic_quotes_gpc($value) {

		return array(
			'description'	=> 'magic_quotes_gpc (php.ini)',
			'current'		=> get_magic_quotes_gpc() ? 'On' : 'Off',
			'result'		=> !get_magic_quotes_gpc()
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

	function require_mod_iconv($value) {

		$status = extension_loaded('iconv');
		return array(
			'description'	=> sprintf(bab_translate("%s php module"),'iconv'),
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

	function require_mod_dom($value) {

		$status = extension_loaded('dom');
		return array(
			'description'	=> sprintf(bab_translate("%s php module"),'dom'),
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
	
	
	
	function require_mod_mysqli($value) {
	
		$status = extension_loaded('mysqli');
		return array(
				'description'	=> sprintf(bab_translate("%s php module"),'mysqli'),
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

	function require_mod_rewrite($value) {

		$status = function_exists('apache_get_modules') && in_array('mod_rewrite', apache_get_modules());
		return array(
			'description'	=> bab_translate("mod_rewrite apache module"),
			'current'		=> $status ? bab_translate("Available") : bab_translate("Unavailable"),
			'result'		=> $status
		);
	}


	function require_mysql_character_set_database($value) {

		$value = mb_strtolower($value);
		$error = null;

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
		$status = in_array($charset, $arr_values);

		if (!$status) {
			$error = str_replace('%charsets%', $value, bab_translate('The charset of the database must be one of the following charsets : %charsets%'));
		}




		return array(
			'description'	=> bab_translate("MySQL database charset"),
			'current'		=> $charset,
			'result'		=> $status,
			'error'			=> $error
		);
	}


	function require_mysql_collation_database($value) {

		$value = mb_strtolower($value);
		$error = null;

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


		$status = in_array($collation, $arr_values);

		if (!$status) {
			$error = str_replace('%charsets%', $value, bab_translate('The charset of the database must be one of the following charsets : %charsets%'));
		}

		return array(
			'description'	=> bab_translate("MySQL database collation"),
			'current'		=> $collation,
			'result'		=> $status,
			'error'			=> $error
		);
	}


	function require_mysql_max_allowed_packet($value) {

		$value = self::return_bytes($value);
		$error = null;

		global $babDB;
		$res = $babDB->db_queryWem("show variables like 'max_allowed_packet'");


		$max_allowed_packet = 'Undefined';

		if ($arr = $babDB->db_fetch_array($res)) {
			$max_allowed_packet = (int) $arr[1];
		}

		$status = $value <= $max_allowed_packet;

		if (!$status) {
			$error = str_replace('%val%', $value, bab_translate('The max_allowed_packet variable must be greater than %val%'));
		}

		return array(
			'description'	=> bab_translate("MySQL server variable max_allowed_packet"),
			'current'		=> sprintf("%dM",$max_allowed_packet/1024/1024),
			'result'		=> $value <= $max_allowed_packet,
			'error'			=> $error
		);
	}


	function require_mysql_sql_mode($value) {


		global $babDB;
		$error = null;
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

		$status = $value === $current;


		if ('Undefined' === $value) {
			$value_display = bab_translate('Undefined');
		}

		if (!$status) {
			$error = str_replace('%val%', $value_display, bab_translate('The variable sql_mode must be : %val%'));
		}

		if ('Undefined' === $current) {
			$current = bab_translate('Undefined');
		}


		return array(
			'description'	=> bab_translate("MySQL server variable : sql_mode"),
			'current'		=> $current,
			'result'		=> $status,
			'error'			=> $error
		);
	}



	function require_file_is_writable($value) {

		$path = dirname(__FILE__) . '/../' . $value;
		$path = realpath($path);
		
		if (false === $path)
		{
			// try with SCRIPT_FILENAME
			
			$path = dirname($_SERVER['SCRIPT_FILENAME']).'/'.$GLOBALS['babInstallPath'].$value;
		}

		$isWritable = is_writable($path);

		return array(
				'description'	=> sprintf(bab_translate('File %s is writable'), $path),
				'display_value' => bab_translate('Writable'),
				'current'		=> $isWritable ? bab_translate('Writable') : bab_translate('Not writable'),
				'result'		=> $isWritable === true
		);
	}
	
	
	
	
	function require_site_sitemap_node($value) {
		
		require_once dirname(__FILE__).'/settings.class.php';
		$error = null;
		
		$rootNode = bab_sitemap::getFromSite();
		
		
		$settings = bab_getInstance('bab_Settings');
		/*@var $settings bab_Settings */
		
		$site = $settings->getSiteSettings();
		
		
		$found = array();
		$required_id = preg_split('/\s*,\s*/', $value);
		
		foreach($required_id as $nodeId)
		{
			if(isset($rootNode)) {
				$node = $rootNode->getNodeById($nodeId);
			} else {
				$node = null;
			}
			
			if (null === $node)
			{
				$error = sprintf(bab_translate('Node %s not found in site sitemap %s'), $nodeId, $site['sitemap']);
			} else {
			
				$found[] = $nodeId;
			}
		}
		
		return array(
				'description'	=> sprintf(bab_translate("Node ID in the site sitemap (%s)"), $site['sitemap']),
				'display_value' => $value,
				'current'		=> count($found) > 0 ? (count($required_id) === 1 ? bab_translate('The node exists') : implode(', ', $found)) : bab_translate('Node not found'),
				'result'		=> count($found) === count($required_id),
				'error'			=> $error
		);
	}
	
	



	/**
	 * Multi rule version compare for ini file rules (addons, ov_version ...)
	 * @param string $installed
	 * @param string $required
	 * @return bool
	 */
	public static function multicompare($installed, $required)
	{
		$result = true;

		$list = explode(',',$required);
		foreach($list as $r)
		{
			$r = trim($r);
			$operator = '>=';
			if (preg_match('/^(>=|<=|>|<|=)([0-9\.]+)$/', $r, $m)) {
				$operator = $m[1];
				$r = $m[2];
			}

			if (false === version_compare($installed, $r, $operator))
			{
				$result = false;
			}
		}

		return $result;
	}
}






/**
 * @package inifile
 */
class bab_inifile_requirements_html
	{
	public $propose_upgrades = false;
	public $requirements;
	public $altbg = false;

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
		
		
	private function getRequirement($arr)
	{
		$this->upgradeurl = false;
		
		
		if ($this->propose_upgrades && isset($arr['name']) && function_exists('bab_getAddonInfosInstance')) {
			$addon = bab_getAddonInfosInstance($arr['name']);
		
			if ($addon && $addon->isUpgradable()) {
				$this->upgradeurl = $GLOBALS['babUrlScript'].'?tg=addons&idx=upgrade&item='.$addon->getId();
			}
		}
		
		$this->altbg = !$this->altbg;
		$this->description = bab_toHtml($arr['description']);
		$this->recommended = bab_toHtml($arr['recommended']);
		$this->required = bab_toHtml($arr['required']);
		$this->current = bab_toHtml($arr['current']);
		$this->result = $arr['result'];
		
		if (isset($arr['error'])) {
			$this->error = bab_toHtml($arr['error']);
		} else {
			$this->error = null;
		}
		
	}

	
	


	function getnextreq() {
		
		if (!isset($this->requirements['required']))
		{
			return false;
		}
		
		if (list(,$arr) = each($this->requirements['required'])) {

			$this->getRequirement($arr);
			return true;
		}
		return false;
	}
	
	function getnextrec() {
		
		if (!isset($this->requirements['recommended']))
		{
			return false;
		}
		
		if (list(,$arr) = each($this->requirements['recommended'])) {
	
			$this->getRequirement($arr);
			return true;
		}
		return false;
	}
}













/**
 * @package inifile
 */
class bab_inifile {

	var $addons;
	var $recommendations = array();
	var $functionalities;
	var $customscript = array();

	public $inifile;
	
	
	/**
	 * @return string
	 */
	private function getTmpPath()
	{
		require_once dirname(__FILE__).'/settings.class.php';
		$settings = bab_getInstance('bab_Settings');
		/*@var $settings bab_Settings */
	
		$site = $settings->getSiteSettings();
	
		return $site['uploadpath'].'/tmp';
	}
	

	/**
	 * Use a ini file in a zip file
	 * @param string $zipfile absolute path to the zip file
	 * @param string $inifile path to the ini file in the zip archive
	 */
	public function getfromzip($zipfile, $inifile) {

		if (empty($zipfile)) {
			throw new Exception(bab_translate('The archive does not exists'));
			return false;
		}

		include_once dirname(__FILE__)."/addonsincl.php";
		require_once dirname(__FILE__).'/session.class.php';
		$session = bab_getInstance('bab_Session');
		/*@var $session bab_Session */

		$addon_paths = bab_getAddonsFilePath();
		$program_path = $addon_paths['loc_out'][0].'/';

		$zip = bab_functionality::get('Archive/Zip');
		/*@var $zip Func_Archive_Zip  */
		
		$zip->open($zipfile);
		
		$tmp_extract = $this->getTmpPath().'/'.$session->getId().'_'.basename($zipfile);
		$zip->extractTo($tmp_extract);
		
		if (!file_exists($tmp_extract.'/'.$inifile)) {
			throw new Exception(bab_sprintf(bab_translate('The file %s could not be found in archive'), $inifile));
			return false;
		}
		
		$this->inifile($tmp_extract.'/'.$inifile);

		

		// si le ini contiens un preinstall script, le chercher dans le meme repertoire

		if (isset($this->inifile['preinstall_script'])) {

			$preinstall_script = dirname($tmp_extract.'/'.$inifile).$this->inifile['preinstall_script'];
			if (file_exists($preinstall_script))
			{
				$this->addCustomScript($name, $this->getTmpPath().'/'.$preinstall_script);
			}
		}
		
		require_once dirname(__FILE__).'/delincl.php';
		bab_deldir($tmp_extract, $msgerror);
	}



	public function parse($file) {

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
	 * @deprecated use ->inifile() instead to have a full object
	 * @return boolean
	 */
	function inifileGeneral($file) {

		if ($arr = $this->parse($file)) {
			$this->inifile = $arr['general'];
			return true;
		}

		return false;
	}


	function fileExists() {
		return !empty($this->inifile);
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

		if (!is_array($arr)) {
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
	 * Get the requierments classified in mutiple sub array
	 * mandatory, optional
	 * 
	 * @return array
	 */
	public function getRequirementsCategories()
	{
		$arr = $this->getRequirements();
		
		$optional = array();
		$mandatory = array();
		
		foreach($arr as $req)
		{
			if (isset($req['required']) && false !== $req['required'])
			{
				$mandatory[] = $req;
			} else {
				$optional[] = $req;
			}
		}
		
		
		return array(
			'required'	=> $mandatory,
			'recommended' => $optional
		);
	}
	
	
	
	private function getUnsortedRequirements()
	{
		$return = array();
		
		$requirementsObj = new bab_inifile_requirements();
		
		foreach($this->inifile as $keyword => $value) {
			$keyword = 'require_'.$keyword;
			if (method_exists ( $requirementsObj, $keyword )) {
				$arr = $requirementsObj->$keyword($value);
				if (is_array($arr)) {
					if (isset($arr['display_value'])) {
						$arr['required'] = bab_translate($arr['display_value']);
					} else {
						$arr['required'] = bab_translate($value);
					}
					$arr['required'] = bab_translate($value);
					$arr['recommended'] = false;
					$return[] = $arr;
				}
			}
		}
		
		
		
		foreach($this->recommendations as $keyword => $value) {
			$keyword = 'require_'.$keyword;
			//bab_debug($keyword);
			if (method_exists ( $requirementsObj, $keyword )) {
				//bab_debug("exists");
				$arr = $requirementsObj->$keyword($value);
				if (is_array($arr)) {
					$arr['required'] = false;
					if (isset($arr['display_value'])) {
						$arr['recommended'] = bab_translate($arr['display_value']);
					} else {
						$arr['recommended'] = bab_translate($value);
					}
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
							'description'	=> bab_translate('Library').' : '.$name,
							'required'		=> $required,
							'recommended'	=> $recommended,
							'current'		=> bab_translate('Not installed or disabled'),
							'result'		=> false
					);
				} else {
					$return[] = array(
							'description'	=> bab_translate('Library').' : '.$name,
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
		
		
		return $return;
		
	}




	/**
	 * The list of requirements specified in the ini file
	 * @return array
	 */
	function getRequirements() {

		
		$return = $this->getUnsortedRequirements();
		
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
	 * Addons requirements
	 *
	 *
	 *
	 * @return array
	 */
	function getAddonsRequirements() {

		$return = array();


		if ($this->addons) {

			$db = &$GLOBALS['babDB'];
			$res = $db->db_query("SELECT title, version FROM ".BAB_ADDONS_TBL." WHERE title IN('".implode("','",array_keys($this->addons))."') AND installed='Y' AND enabled='Y'");
			$installed = array();
			while ($arr = $db->db_fetch_assoc($res)) {
				$installed[mb_strtolower($arr['title'])] = $arr['version'];
			}

			foreach($this->addons as $name => $required) {

			    $key = mb_strtolower($name);

				if (isset($installed[$key])) {

					$return[] = array(
						'name'			=> $name,
						'description'	=> bab_translate('Ovidentia addon').' : '.$name,
						'required'		=> $required,
						'recommended'	=> false,
						'current'		=> $installed[$key],
						'result'		=> bab_inifile_requirements::multicompare($installed[$key], $required)
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
	function getTables()
	{
	    global $babDB;

		$return = array();
		if (!empty($this->inifile['db_prefix'])) {
			$res = $babDB->db_query("SHOW TABLES LIKE '" . $babDB->db_escape_like($this->inifile['db_prefix']) . "%'");
			while(list($tbl) = $babDB->db_fetch_array($res)) {
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

		if (!$this->fileExists())
		{
			return false;
		}		
		
		$requirements = $this->getUnsortedRequirements();
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

				if (isset($arr['error'])) {
					$error = $arr['error'];
				} else {
					$error = sprintf('error, %s is "%s" but "%s" is required', $arr['description'], $arr['current'], $arr['required']);
				}

				return false;
			}
		}
		return true;
	}




	/**
	 * The list of requirements specified in the ini file
	 *
	 * @param	bool	$propose_upgrades		propose addon upgrades link if possible
	 *
	 * @return string
	 */
	function getRequirementsHtml($propose_upgrades = false) {

		$temp = new bab_inifile_requirements_html();
		$temp->propose_upgrades = $propose_upgrades;
		$temp->requirements = $this->getRequirementsCategories();
		$temp->list_required = !empty($temp->requirements['required']);
		$temp->list_recommended = !empty($temp->requirements['recommended']);
		
		return bab_printTemplate($temp,"requirements.html");
	}

}




/**
 * Ini file for addon
 * @package inifile
 */
class bab_AddonIniFile extends bab_inifile {

}





/**
 * Ini file for collections of addons
 * addons.ini
 * @package inifile
 */
class bab_AddonCollectionIniFile extends bab_inifile {


	/**
	 * Get package collection of subfolders to install
	 * @return array
	 */
	function getPackageCollection() {

		if (!isset($this->inifile['package_collection'])) {
			return null;
		}

		$collection = $this->inifile['package_collection'];
		$return = array();

		foreach(explode(',', $collection) as $folder) {

			$value = trim($folder);

			if (!empty($value)) {
				$return[] = $value;
			}
		}

		return $return;
	}

}









/**
 * Ini file for distibutions versions
 * version.inc
 * @package inifile
 */
class bab_CoreIniFile extends bab_inifile {

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


}


