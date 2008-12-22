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



function bab_recursive_cp($wf, $wto){ 
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
					if (!copy($fl,$flto)) {
						return sprintf(bab_translate("Error : can't copy the file %s to the directory %s"), basename($fl), dirname($flto) );
					}
				}
		   }
	   }

	return true;
  }



function bab_cpaddons($from, $to, &$message)
	{
	



	function create($path)
	{
	$el = explode("/",$path);
	$memo = '';
	foreach ($el as $rep)
		{
		if (!is_dir($memo.$rep)) { 
			if (!bab_mkdir($memo.$rep)) {
				return 	sprintf(bab_translate("Error : can't create directory : %s"), $memo.$rep);
			}
		}
		$memo = $memo.$rep."/";
		}
	return true;
	}

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
		$creation = create($to.$path);

		if (true !== $creation) {
			$message = $creation;
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
		ereg($var."[[:space:]]*=[[:space:]]*\"([^\"]*)\"", $txt, $match);
		if ($match[1] != $value)
			{
			$out = ereg_replace($var."[[:space:]]*=[[:space:]]*\"".preg_quote($match[1],"/")."\"", $var." = \"".$value."\"", $txt);
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
 * @return boolean
 */
function bab_upgrade($core_dir, &$ret)
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
		$reg = "babVersion[[:space:]]*=[[:space:]]*\"([^\"]*)\"";
		$res = ereg($reg, $txt, $match);

		$reg = "babVersion[[:space:]]*=[[:space:]]*\"".$match[1]."\"";
		$out = ereg_replace($reg, "babVersion = \"".$version."\"", $txt);
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
				$ret = bab_translate("This version can't be installed because of the missing requirement").' '.$req['description'].' '.$req['required'];
				return false;
			}
		}
	}

	list($bab_ver_major, $bab_ver_minor, $bab_ver_build) = explode('.',$ini->getVersion());
	
	if( $ver_from == $ini->getVersion() )
		{
			$ret = bab_translate("You site is already up to date");
			return false;
		}

	include_once $core_dir."upgrade.php";
	if (true === ovidentia_upgrade($ver_from, $ini->getVersion())) {
	
		putIniDbKey('ver_major', $bab_ver_major);
		putIniDbKey('ver_minor', $bab_ver_minor);
		putIniDbKey('ver_build', $bab_ver_build);
	
		putVersion($bab_ver_major.".".$bab_ver_minor);
		
		if (false == $ver_from) {
			$ret .= sprintf(
				bab_translate("You site has been completly installed in version %s"), 
				$bab_ver_major.'.'.$bab_ver_minor.'.'.$bab_ver_build
			)." \n";

		} else {
			$ret .= bab_translate("You site has been updated")." \n";
			$ret .= bab_translate("From").' '. $ver_from. ' ';
			$ret .= bab_translate("to").' '. $bab_ver_major.'.'.$bab_ver_minor.'.'.$bab_ver_build;
			
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
	
	
		$GLOBALS['babJs'] = $GLOBALS['babInstallPath']."scripts/ovidentia.js";
		$GLOBALS['babCssPath'] = bab_getCssUrl();
		
		$babDummy = new babDummy();
		
		$GLOBALS['babCss'] = bab_printTemplate($babDummy, "config.html", "babCss");
		$GLOBALS['babMeta'] = bab_printTemplate($babDummy, "config.html", "babMeta");
	
		$GLOBALS['babSkinPath'] = $GLOBALS['babInstallPath']."skins/ovidentia/";

		$babBody->setTitle(bab_translate('Ovidentia prerequisit verification'));
		$babBody->addError(bab_translate('One or more prerequisites are not fullfilled, you must fix them before continuing to Ovidentia homepage'));
		$babBody->babPopup($ini->getRequirementsHtml());
		
		exit;
	}


	require_once $GLOBALS['babInstallPath'].'utilit/addonsincl.php';
	$sInstallDir = dirname($_SERVER['SCRIPT_FILENAME']).'/install/addons';
	if(is_dir($sInstallDir))
	{
		$aAddonsFilePath	= bab_getAddonsFilePath();
		$aAddonList			= bab_getAddonListFromInstall($sInstallDir);
		
		if(0 < count($aAddonList))
		{
			$aLocIn	 = $aAddonsFilePath['loc_in'];
			$aLocOut = $aAddonsFilePath['loc_out'];
			
			if(count($aLocIn) == count($aLocOut))
			{
				foreach($aAddonList as $iKey1 => $sAddonName)
				{

					foreach($aLocIn as $iKey2 => $sPathName)
					{
						$sOldName = $sInstallDir . '/' . $sAddonName . '/' . $aLocOut[$iKey2];
						$sNewName = dirname($_SERVER['SCRIPT_FILENAME']).'/'.$aLocIn[$iKey2] . '/' . $sAddonName;
	
						if (is_dir($sOldName) && !bab_recursive_cp($sOldName, $sNewName)) {
							return false;
						}
					}
				}
			}
		} 
		else 
		{
			bab_debug('aAddonList empty');
		}
	} 
	else 
	{
		bab_debug('missing sInstallDir '.$sInstallDir);
	}




	// add in database the default addons
	bab_addonsInfos::insertMissingAddonsInTable();
	bab_addonsInfos::clear();

	// install database for the default addons
	foreach(bab_addonsInfos::getDbAddonsByName() as $addon) {
		$addon->upgrade();
	}
	
	
	include_once $GLOBALS['babInstallPath'].'install.php';
	
	
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




function bab_getAddonListFromInstall($sInstallDir)
{
	$aAddons = array();
	
	if(is_dir($sInstallDir))
	{
		$oDirIterator = new BabDirectoryFiltered($sInstallDir, 
			BabDirectoryFilter::DOT | BabDirectoryFilter::FILE);
			
		foreach($oDirIterator as $oItem)
		{
			$aAddons[] = $oItem->getFilename();
		}
	}
	return $aAddons;
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
 * Insert informations into message log
 * If the $uid is given, it must be unique for each $addon_name, the function will return false if the uid is allready inserted
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


?>