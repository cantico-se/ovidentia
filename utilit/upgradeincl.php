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

function bab_cpaddons($from, $to, &$message)
	{
	function ls_a($wh){
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
	function cp($wf, $wto){ 
		  if (!is_dir($wto)) { 
			  if (!bab_mkdir($wto)) {
				return sprintf(bab_translate("Error : can't create directory : %s"), $wto);
			  }
			}
		  $arr=ls_a($wf);
		  foreach ($arr as $fn){
			  if($fn){
				  $fl="$wf/$fn";
				 $flto="$wto/$fn";
				  if(is_dir($fl)) {
						$return = cp($fl,$flto);
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

	if (substr($from,-1) != "/") $from.="/";
	if (substr($to,-1) != "/") $to.="/";
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

		$copy = cp($from.$path,$to.$path);

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

	


	$rr = $db->db_fetch_array($db->db_query("select fvalue from ".BAB_INI_TBL." where foption='ver_major'"));
	$dbver[] = $rr['fvalue'];
	$rr = $db->db_fetch_array($db->db_query("select fvalue from ".BAB_INI_TBL." where foption='ver_minor'"));
	$dbver[] = $rr['fvalue'];
	$rr = $db->db_fetch_array($db->db_query("select fvalue from ".BAB_INI_TBL." where foption='ver_build'"));
	$dbver[] = $rr['fvalue'];

	$ver_from = $dbver[0].'.'.$dbver[1].'.'.$dbver[2];

	$ini = new bab_inifile();
	$ini->inifile($core_dir.'version.inc');

	if (!$ini->isValid()) {
		$requirements = $ini->getRequirements();
		foreach($requirements as $req) {
			if (false === $req['result']) {
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
	
		$db->db_query("update ".BAB_INI_TBL." set fvalue='".$db->db_escape_string($bab_ver_major)."' where foption='ver_major'");
		$db->db_query("update ".BAB_INI_TBL." set fvalue='".$db->db_escape_string($bab_ver_minor)."' where foption='ver_minor'");
		$db->db_query("update ".BAB_INI_TBL." set fvalue='".$db->db_escape_string($bab_ver_build)."' where foption='ver_build'");
	
		putVersion($bab_ver_major.".".$bab_ver_minor);
		$ret .= bab_translate("You site has been updated")." \n";
		$ret .= bab_translate("From").' '. $dbver[0].'.'.$dbver[1].'.'.$dbver[2]. ' ';
		$ret .= bab_translate("to").' '. $bab_ver_major.'.'.$bab_ver_minor.'.'.$bab_ver_build;
		
		bab_setUpgradeLogMsg(BAB_ADDON_CORE_NAME, $ret);
		
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