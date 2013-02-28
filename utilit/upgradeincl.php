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
 * @copyright Copyright (c) 2009 by CANTICO ({@link http://www.cantico.fr})
 */
include_once 'base.php';


class BabDirectoryFilter
{
	const DOT	= 1;
	const FILE	= 2;
	const DIR	= 4;
}


class BabDirectoryFiltered extends FilterIterator
{
	protected $iFilterBits = 0;

	public function __construct($sPath, $iFilterBits = 0)
    {
        parent::__construct(new DirectoryIterator($sPath));
        $this->setFilter($iFilterBits);
    }

    public function setFilter($iBit)
    {
		$this->iFilterBits |= $iBit;
    }

    public function accept()
    {
    	$oIterator = $this->getInnerIterator();

    	if($this->bitActivated(BabDirectoryFilter::DOT) && $oIterator->isDot())
    	{
    		return false;
    	}

    	if($this->bitActivated(BabDirectoryFilter::DIR) && $oIterator->isDir())
    	{
    		return false;
    	}

    	if($this->bitActivated(BabDirectoryFilter::FILE) && $oIterator->isFile())
    	{
    		return false;
    	}
    	return true;
    }

    public function bitActivated($iBit)
    {
    	return ($this->iFilterBits & $iBit);
    }
}






function bab_recursive_cp_ls_a($wh){
         if ($handle = opendir($wh)) {
             while (false !== ($file = readdir($handle))) {
				if ($file != "." && $file != ".." ) {
						if(!isset($files)) {
							$files="$file";
						} else {
							$files="$file\n$files";
						}
				   }
              }
               closedir($handle);
         }
		 if (!isset($files))
			return array();
        $arr=explode("\n",$files);
        return $arr;
    }


/**
 * Recursive copy of a folder
 *
 * @param	string	$wf		origin folder
 * @param	string	$wto	destination folder
 *
 * @return	true | string	if the function return a string, this is the error message
 */
function bab_recursive_cp($wf, $wto) {
	  if (!is_dir($wto)) {
		  if (!bab_mkdir($wto)) {
			return sprintf(bab_translate("Error : can't create directory : %s"), $wto);
		  }
		}
	  $arr=bab_recursive_cp_ls_a($wf);
	  foreach ($arr as $fn){
		  if($fn){
			  $fl="$wf/$fn";
			 $flto="$wto/$fn";
			  if(is_dir($fl)) {
					$return = bab_recursive_cp($fl,$flto);
					if (true !== $return) {
						return $return;
					}
				} else {

					// ignore file if broken symlink
					if (function_exists('readlink') && false !== $destination = @readlink($fl)) {
						if (!@file_exists($destination)) {
							continue;
						}
					}

					if (!copy($fl,$flto)) {
						return sprintf(bab_translate("Error : can't copy the file %s to the directory %s"), basename($fl), dirname($flto) );
					}
				}
		   }
	   }

	return true;
  }


/**
 * Copy addons forlders from one core to another
 *
 * @param	string		$from	source core folder
 * @param	string		$to		destination core folder
 *
 * @return boolean
 */
function bab_cpaddons($from, $to, &$message)
{
	require_once dirname(__FILE__).'/path.class.php';


	if (mb_substr($from,-1) != "/") $from.="/";
	if (mb_substr($to,-1) != "/") $to.="/";
	$loc = array(
				"addons",
				"lang/addons",
				"styles/addons",
				"skins/ovidentia/templates/addons",
				"skins/ovidentia/ovml/addons",
				"skins/ovidentia/images/addons"
			);

	foreach ($loc as $path) {
		$pathObj = new bab_Path($to.$path);

		if (!$pathObj->createDir()) {
			$message = sprintf(bab_translate("Error : can't create directory : %s"), $path);
			return false;
		}

		$copy = bab_recursive_cp($from.$path,$to.$path);

		if (true !== $copy) {
			$message = $copy;
			return false;
		}
	}

	return true;
}


function bab_writeConfig($replace)
	{
	global $babBody;
	function replace($txt, $var, $value)
		{
		preg_match('/'.preg_quote($var, '/')."\s*=\s*\"([^\"]*)\"/", $txt, $match);
		if ($match[1] != $value)
			{
			$out = preg_replace('/'.preg_quote($var, '/')."\s*=\s*\"".preg_quote($match[1],"/")."\"/", $var." = \"".$value."\"", $txt);
			if ($out != $txt)
				return $out;
			else
				return false;
			}
		else
			return $txt;
		}

	$file = @fopen('config.php', "r");
	if (!$file)
		{
		$babBody->msgerror = bab_translate('Failed to read config file');
		return false;
		}
	$txt = fread($file, filesize('config.php'));
	fclose($file);

	$config = array('babDBHost','babDBLogin','babDBPasswd','babDBName','babInstallPath','babUrl');

	foreach ($replace as $key => $value)
		{
		$out = replace($txt, $key, $value);
		if (!$out)
			{
			$babBody->msgerror = bab_translate('Config change failed on').' '.$var;
			return false;
			}
		else
			$txt = $out;
		}

	$file = fopen('config.php', "w");
	if (!$file)
		{
		$babBody->msgerror = bab_translate('Failed to write into config file');
		return false;
		}
	fputs($file, $out);
	fclose($file);

	return true;
	}




/**
 * Ovidentia upgrade
 *
 * @param bool	$forceUpgrade		True to force upgrade process even if the database version is up-to-date.
 *
 * @return boolean
 */
function bab_upgrade($core_dir, &$ret, $forceUpgrade = false)
{

	global $babBody;
	$db = $GLOBALS['babDB'];


	function putVersion($version)
	{
		$filename = "config.php";

		clearstatcache();
		$file = @fopen($filename, "r");
		$txt = fread($file, filesize($filename));
		fclose($file);
		$reg = "/babVersion[[:space:]]*=[[:space:]]*\"([^\"]*)\"/";
		$res = preg_match($reg, $txt, $match);

		$reg = "/babVersion[[:space:]]*=[[:space:]]*\"".$match[1]."\"/";
		$out = preg_replace($reg, "babVersion = \"".$version."\"", $txt);
		if (is_writable($filename)) {
			$file = fopen($filename, "w");
			fputs($file, $out);
			fclose($file);
			return $match[1];
		}
		return false;
	}


	function putIniDbKey($key, $value) {
		global $babDB;

		$res = $babDB->db_query("SELECT COUNT(*) FROM ".BAB_INI_TBL." WHERE foption=".$babDB->quote($key));
		list($n) = $babDB->db_fetch_array($res);


		if (0 === (int) $n) {

			$babDB->db_query("INSERT INTO ".BAB_INI_TBL."
					(foption, fvalue)
				VALUES
					(
						'".$babDB->db_escape_string($key)."',
						'".$babDB->db_escape_string($value)."'
					)
			");
		} else {
			$babDB->db_query("update ".BAB_INI_TBL." set
				fvalue='".$babDB->db_escape_string($value)."'
			WHERE foption='".$babDB->db_escape_string($key)."'");
		}
	}


	$ver_from = bab_getDbVersion();

	$ini = new bab_inifile();
	$ini->inifile($core_dir.'version.inc');

	if (!$ini->isValid()) {
		$requirements = $ini->getRequirements();
		foreach($requirements as $req) {
			if (false === $req['result'] && !empty($req['required'])) {

				if (isset($req['error'])) {
					$ret = $req['error'];
				} else {
					$ret = bab_translate("This version can't be installed because of the missing requirement").' '.$req['description'].' '.$req['required'];
				}

				return false;
			}
		}
	}

	$tmparr = explode('.',$ini->getVersion());
	list($bab_ver_major, $bab_ver_minor, $bab_ver_build) = $tmparr;
	if (isset($tmparr[3])) {
		$bab_ver_nightly = $tmparr[3];
	} else {
		$bab_ver_nightly = 0;
	}


	//TODO Should be replaced with a proper function (at this stage bab_isUserAdministrator does not work).
	$isUserAdministrator = isset($_SESSION['bab_groupAccess']['ovgroups'][BAB_ADMINISTRATOR_GROUP]['member']) && 'Y' === $_SESSION['bab_groupAccess']['ovgroups'][BAB_ADMINISTRATOR_GROUP]['member'];

	if (!($forceUpgrade && $isUserAdministrator) && ($ver_from == $ini->getVersion())) {
		$ret = bab_translate('You site is already up to date');
		return false;
	}

	include_once $core_dir.'upgrade.php';
	if (true === ovidentia_upgrade($ver_from, $ini->getVersion())) {

		// the core has been upgraded correctly
		// update addons if necessary using the install/addons.ini file
		bab_upgradeAddonsFromInstall(false, $ini->getVersion());


		putIniDbKey('ver_major', $bab_ver_major);
		putIniDbKey('ver_minor', $bab_ver_minor);
		putIniDbKey('ver_build', $bab_ver_build);
		putIniDbKey('ver_nightly', $bab_ver_nightly);

		putVersion($bab_ver_major.".".$bab_ver_minor);

		if (false == $ver_from) {
			$ret .= sprintf(
				bab_translate("You site has been completly installed in version %s"),
				$bab_ver_major.'.'.$bab_ver_minor.'.'.$bab_ver_build
			)." \n";

		} else {
			$ret .= bab_translate("You site has been updated")." \n";
			$ret .= bab_translate("From").' '. $ver_from. ' ';
			$ret .= bab_translate("to").' '. $bab_ver_major.'.'.$bab_ver_minor.'.'.$bab_ver_build.' ';

			if ($bab_ver_nightly) {
				$ret .= bab_translate("Nightly build").' '. $bab_ver_nightly;
			}
		}

		bab_setUpgradeLogMsg(BAB_ADDON_CORE_NAME, $ret);

		bab_siteMap::clearAll();

		return true;
	}

	foreach($babBody->errors as $error) {
		$ret .= bab_toHtml($error)."\n\n";
	}

	if (!$babBody->errors) {
		$ret .= bab_translate('Error on upgrade');
	}

	return false;
}




/**
 * Install or upgrade addons from the install/addons folder
 * this is processed on new install and after ovidentia upgrade
 *
 * @param	bool 			$install		test the install parameter of addons.ini file
 * @param	null | string	$upgrade		test the upgrade parameter of addons.ini file, contain the version number of ovidentia (after upgrade)
 */
function bab_upgradeAddonsFromInstall($install, $upgrade) {

	require_once $GLOBALS['babInstallPath'].'utilit/addonsincl.php';
	$sInstallDir = realpath('.').'/install/addons';


	if(is_dir($sInstallDir) && file_exists(realpath('.').'/install/addons.ini'))
	{
		$addons = parse_ini_file(realpath('.').'/install/addons.ini', true);


		$aAddonsFilePath	= bab_getAddonsFilePath();

		if(0 < count($addons))
		{
			$aLocIn	 = $aAddonsFilePath['loc_in'];
			$aLocOut = $aAddonsFilePath['loc_out'];

			if(count($aLocIn) == count($aLocOut))
			{
				foreach($addons as $sAddonName => $params)
				{
					if (!isset($params['install']))
					{
						$params['install'] = 0;
					}

					if (!isset($params['upgrade']))
					{
						$params['upgrade'] = 0;
					}

					if (($install && $params['install']) || ($upgrade && $params['upgrade'] && version_compare($upgrade, $params['upgrade'], '>=')))
					{
						if (!is_dir($sInstallDir . '/' . $sAddonName))
						{
							throw new Exception(sprintf('The addon %s found in addons.ini file does not exists in the folder %s',$sAddonName, $sInstallDir));
							return false;
						}

						// if addon already installed verify the version
						// do not use bab_getAddonInfosInstance here because instances are set in cache and new addons will be disabled for this function
						// to prevent this problem, we use a new instance of bab_addonInfos
						$addon = new bab_addonInfos();
						if (false !== $addon->setAddonName($sAddonName, false))
						{
							$inifile = $sInstallDir . '/' . $sAddonName . '/programs/addonini.php';
							if (!is_file($inifile))
							{
								throw new Exception(sprintf('The addon %s found in addons.ini file does not contain an addonini.php file',$sAddonName));
								return false;
							}

							$newaddon = parse_ini_file($inifile, true);

							if (version_compare($newaddon['general']['version'], $addon->getIniVersion(), '<='))
							{
								// ignore this addon if the new version is not superior
								continue;
							}
						}


						foreach($aLocIn as $iKey2 => $sPathName)
						{
							$sOldName = $sInstallDir . '/' . $sAddonName . '/' . $aLocOut[$iKey2];
							$sNewName = realpath('.').'/'.$aLocIn[$iKey2] . '/' . $sAddonName;

							if (is_dir($sOldName) && !bab_recursive_cp($sOldName, $sNewName)) {
								throw new Exception(sprintf('failed to copy addon %s from path %s',$sAddonName, $sOldName));
								return false;
							}
						}
					}
				}



				// add in database the default addons
				bab_addonsInfos::insertMissingAddonsInTable();
				bab_addonsInfos::clear();



				// install database for the default addons
				foreach($addons as $sAddonName => $params) {

					$addon = new bab_addonInfos();
					if ($addon->setAddonName($sAddonName, false))
					{
						if (!$addon->upgrade())
						{
							trigger_error(sprintf('Failed to upgrade addon %s', $sAddonName));
						}
					} else {
						// addons can be only in new installs
						// throw new Exception(sprintf('Addon not found %s', $sAddonName));
						// return false;
					}
				}
			}
		}
		else
		{
			bab_debug('addons.ini is empty');
		}
	}
	else
	{
		bab_debug('missing sInstallDir '.$sInstallDir);
	}


	return true;
}






/**
 * Ovidentia new install
 * if prerequisite are not compatibles, the function will exit the script
 * @return bool
 */
function bab_newInstall() {

	global $babBody, $babDB;
	include_once $GLOBALS['babInstallPath'].'utilit/inifileincl.php';

	$GLOBALS['babLanguage'] = 'en';
	$GLOBALS['babStyle'] = 'ovidentia.css';
	$GLOBALS['babSkin'] = 'ovidentia';

	$ini = new bab_inifile();
	$ini->inifile($GLOBALS['babInstallPath'].'version.inc');

	if (!$ini->isValid()) {

		$GLOBALS['babSkinPath'] = $GLOBALS['babInstallPath']."skins/ovidentia/";

		$babBody->setTitle(bab_translate('Ovidentia prerequisit verification'));
		$babBody->addError(bab_translate('One or more prerequisites are not fullfilled, you must fix them before continuing to Ovidentia homepage'));
		$babBody->babPopup($ini->getRequirementsHtml());

		exit;
	}
	
	
	
	include_once $GLOBALS['babInstallPath'].'install.php';


	if (bab_upgradeAddonsFromInstall(true, null))
	{
		$iniVersion = $ini->getVersion();
		$arr = explode('.', $iniVersion);

		$babDB->db_query("INSERT INTO ".BAB_INI_TBL." (foption, fvalue)

			VALUES
				('ver_major', ".$babDB->quote($arr[0])."),
				('ver_minor', ".$babDB->quote($arr[1])."),
				('ver_build', ".$babDB->quote($arr[2]).")
		");

		return true;

	}

	return false;
}












/**
 * Test if table exists
 * @param	string	$table
 * @since	5.8.2
 * @return 	boolean
 */
function bab_isTable($table) {
	$db = &$GLOBALS['babDB'];

	$arr = $db->db_fetch_array($db->db_query("SHOW TABLES LIKE '".$table."'"));
	return ($arr[0] == $table);
}


/**
 * Test if field exists
 * @param	string	$table
 * @param	string	$field
 * @since	5.8.2
 * @return 	boolean
 */
function bab_isTableField($table, $field) {
	$db = &$GLOBALS['babDB'];

	$arr = $db->db_fetch_array($db->db_query("DESCRIBE ".$table." ".$field));
	return ($arr[0] == $field);
}


/**
 * Test if a key exists on a table
 * @param string $table
 * @param string $keyname
 *
 * @since 7.3.90
 *
 * @return bool
 */
function bab_isKeyExists($table, $keyname) {

	global $babDB;

	$res = $babDB->db_query('SHOW KEYS FROM '.$babDB->backTick($table));
	while ($arr = $babDB->db_fetch_assoc($res)) {
		if (isset($arr['Key_name']) && $keyname === $arr['Key_name']) {
			return true;
		}
	}
	return false;
}




/**
 * Insert informations into message log
 * If the $uid is given, it must be unique for each $addon_name, the function will return false if the uid is already inserted
 * @since	6.3.0
 * @param	string	$addon_name
 * @param	string	$message
 * @param	string	[$uid]
 * @return 	boolean
 */
function bab_setUpgradeLogMsg($addon_name, $message, $uid = '') {

	global $babDB;

	if ('' !== $uid) {
		$res = $babDB->db_query('
			SELECT COUNT(*) FROM '.BAB_UPGRADE_MESSAGES_TBL.'
			WHERE addon_name='.$babDB->quote($addon_name).' AND uid='.$babDB->quote($uid).'
		');

		list($n) = $babDB->db_fetch_array($res);

		if (0 !== (int) $n) {
			return false;
		}
	}

	$babDB->db_query('
		INSERT INTO '.BAB_UPGRADE_MESSAGES_TBL.'
			(addon_name, dt_insert, uid, message)
		VALUES
			('.$babDB->quote($addon_name).', NOW(), '.$babDB->quote($uid).', '.$babDB->quote($message).')
	');

}

/**
 * Get a log message by unique ID
 * Return an array with 2 keys, message as string and dt_insert as iso datetime
 * @since	6.3.0
 * @param	string	$addon_name
 * @param	string	$uid
 * @return false|array
 */
function bab_getUpgradeLogMsg($addon_name, $uid) {

	global $babDB;

	$res = $babDB->db_query('
		SELECT
			message,
			dt_insert
		FROM
			'.BAB_UPGRADE_MESSAGES_TBL.'
		WHERE
			addon_name='.$babDB->quote($addon_name).'
			AND uid='.$babDB->quote($uid)
	);

	if ($arr = $babDB->db_fetch_assoc($res)) {
		return $arr;
	}

	return false;
}
