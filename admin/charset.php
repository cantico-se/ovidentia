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

require_once 'base.php';
require_once dirname(__FILE__) . '/../utilit/baseFormProcessingClass.php';
require_once dirname(__FILE__) . '/../utilit/fileincl.php';
require_once dirname(__FILE__) . '/../utilit/iterator/iterator.php';
require_once dirname(__FILE__) . '/../utilit/addonsincl.php';

//Begin of helpers classes, functions

define('CHARSET_LOG_TBL', 'bab_charset_convert_log');


/**
 * This function convert the name of the directory form
 * an iso charset to an another
 *
 * @param string $sPathName	Full start path name 
 * @param string $sIsoFrom	The type of encoding of the folder name ex: UTF8
 * @param string $sIsoTo	The type of encoding used for the conversion of the string ex: ISO-8859-15
 */
function convertDirectoryNameRecursive($sPathName, $sIsoFrom, $sIsoTo, &$aError)
{
	if(is_dir($sPathName))
	{
		$oDir = dir($sPathName);
		while(false !== ($sEntry = $oDir->read())) 
		{
			if($sEntry == '.' || $sEntry == '..')
			{
				continue;
			}
			else
			{
				$sFullPathName = $sPathName . '/' . $sEntry;
				if(is_dir($sFullPathName)) 
				{
					convertDirectoryNameRecursive($sFullPathName, $sIsoFrom, $sIsoTo, $aError);
				}
			}
		}
		$oDir->close();
		
		$sPathName	= str_replace('\\', '/', $sPathName);
		$sPathName	= removeEndSlashes($sPathName);
		$sOldName	= getLastPath($sPathName);
		$sNewName	= mb_convert_encoding($sOldName, $sIsoTo, $sIsoFrom);
		$sNewPath	= removeLastPath($sPathName);
		$sNewPath	= addEndSlash(removeEndSlashes($sNewPath)) . $sNewName;
		
		$bSuccess	= @rename($sPathName, $sNewPath);
		if(false === $bSuccess)
		{
			$aSearch	= array('%oldDirectory%', '%newDirectory%');
			$aReplace	= array($sPathName, $sNewPath);
			$sMessage	= str_replace($aSearch, $aReplace, bab_translate('The directory %oldDirectory% cannot be renamed to %newDirectory%'));
			$aError[]	= $sMessage;
		}
	}
}


/**
 * This function return a value that indicate if there 
 * is error in the filemanager. This function is called 
 * before the convertion from a charset to an another 
 * 
 * @param string $sUploadPath	Root path of the filemanager
 * 
 * @return array				An empty array if there is no error
 */
function checkFileManagerForError($sUploadPath, $sIsoFrom, $sIsoTo)
{
	$aError			= array();
	$sFmCollectives	= $sUploadPath . 'fileManager/collectives/'; 
	$sFmUsers		= $sUploadPath . 'fileManager/users/'; 
				
	if(!is_dir($sUploadPath))
	{
		$aError[] = bab_translate("The upload path is not a dir");
	}
	else
	{
		$aError = lookupCollectiveFolderInDatabaseForError($sFmCollectives, $sIsoFrom, $sIsoTo);
		$aError = array_merge($aError, lookupCollectiveFileInDatabaseForError($sFmCollectives, $sIsoFrom, $sIsoTo));
		$aError = array_merge($aError, lookupUserFileInDatabaseForError($sFmUsers, $sIsoFrom, $sIsoTo));
		$aError = array_merge($aError, lookupForOrphanCollectiveFileInFileSystem($sFmCollectives, $sIsoFrom, $sIsoTo));
		$aError = array_merge($aError, lookupForOrphanUserFileInFileSystem($sFmUsers, $sIsoFrom, $sIsoTo));
	}
	return $aError;
}


/**
 * This function lookup the collective folder in the database
 * that is not in the file system
 *
 * @param	string $sPathName	The full path of the collective folder
 * @return	array				An empty array on success 
 */
function lookupCollectiveFolderInDatabaseForError($sPathName, $sIsoFrom, $sIsoTo)
{
	$aError			= array();
	$sErrorString	= bab_translate('The directory %directory% exists on the database but not on the file system');
	$oFmFolderSet	= bab_getInstance('BAB_FmFolderSet');
	
	$oFmFolderSet->select();
	
	while(null !== ($oFmFolder = $oFmFolderSet->next()))
	{
		$sFullPathName = $sPathName . 'DG' . $oFmFolder->getDelegationOwnerId() . '/' . $oFmFolder->getRelativePath() . $oFmFolder->getName();
		if(!is_dir($sFullPathName))
		{
			$aError[] = str_replace('%directory%', $sFullPathName, $sErrorString);
		}
		else
		{
			$sName = mb_convert_encoding($oFmFolder->getName(), $sIsoTo, $sIsoFrom);
			if(false === isStringSupportedByFileSystem($sName))
			{
				$aSearch	= array('%oldDirectory%', '%newDirectory%');
				$aReplace	= array($sFullPathName, $sPathName . 'DG' . $oFmFolder->getDelegationOwnerId() . '/' . $oFmFolder->getRelativePath() . $sName);
				$aError[]	= str_replace($aSearch, $aReplace, 
					bab_translate("The directory %oldDirectory% may not be converted to %newDirectory% because some characters are not supported by the file system"));				
			}
		}
	}
	return $aError;
}


/**
 * This function lookup the collective file in the database
 * that is not in the file system
 * 
 * @param	string	$sPathName	The full path of the collective folder
 * @return	array				An empty array on success				
 */
function lookupCollectiveFileInDatabaseForError($sPathName, $sIsoFrom, $sIsoTo)
{
	$aError			= array();
	$sErrorString	= bab_translate('The file %file% exists on the database but not on the file system');
	$oFolderFileSet	= bab_getInstance('BAB_FolderFileSet');
	$oGroup			= $oFolderFileSet->aField['sGroup'];
	
	$oFolderFileSet->select($oGroup->in('Y'));
	while(null !== ($oFolderFile = $oFolderFileSet->next()))
	{
		$sFullPathName = $sPathName . 'DG' . $oFolderFile->getDelegationOwnerId() . '/' . $oFolderFile->getPathName() . $oFolderFile->getName();
		if(!is_file($sFullPathName))
		{
			$aError[] = str_replace('%file%', $sFullPathName, $sErrorString);
		}
		else
		{
			$sName = mb_convert_encoding($oFolderFile->getName(), $sIsoTo, $sIsoFrom);
			if(false === isStringSupportedByFileSystem($sName))
			{
				$aSearch	= array('%oldFile%', '%newFile%');
				$aReplace	= array($sFullPathName, $sPathName . 'DG' . $oFolderFile->getDelegationOwnerId() . '/' . $oFolderFile->getPathName() . $sName);
				$aError[]	= str_replace($aSearch, $aReplace, 
					bab_translate("The file %oldFile% may not be converted to %newFile% because some characters are not supported by the file system"));				
			}
		}
	}
	return $aError;
}


/**
 * This function lookup the user file in the database
 * that is not in the file system
 * 
 * @param	string $sPathName	The full path of the user folder
 * @return	array				An empty array on success 
 */
function lookupUserFileInDatabaseForError($sPathName, $sIsoFrom, $sIsoTo)
{
	$aError			= array();
	$sErrorString	= bab_translate('The file %file% exists on the database but not on the file system');
	$oFolderFileSet	= bab_getInstance('BAB_FolderFileSet');
	$oGroup			= $oFolderFileSet->aField['sGroup'];
	
	$oFolderFileSet->select($oGroup->in('N'));
	while(null !== ($oFolderFile = $oFolderFileSet->next()))
	{
		$sFullPathName = $sPathName . 'U' . $oFolderFile->getOwnerId() . '/' . $oFolderFile->getPathName() . $oFolderFile->getName();
		if(!is_file($sFullPathName))
		{
			$aError[] = str_replace('%file%', $sFullPathName, $sErrorString);
		}
		else
		{
			$sName = mb_convert_encoding($oFolderFile->getName(), $sIsoTo, $sIsoFrom);
			if(false === isStringSupportedByFileSystem($sName))
			{
				$aSearch	= array('%oldFile%', '%newFile%');
				$aReplace	= array($sFullPathName, $sPathName . 'DG' . $oFolderFile->getDelegationOwnerId() . '/' . $oFolderFile->getPathName() . $sName);
				$aError[]	= str_replace($aSearch, $aReplace, 
					bab_translate("The file %oldFile% may not be converted to %newFile% because some characters are not supported by the file system"));				
			}
		}
	}
	return $aError;
}


/**
 * This function lookup the collective file in the file system
 * that is not in the database
 * 
 * @param	string $sPathName	The full path of the collective folder
 * @return	array				An empty array on success 
 */
function lookupForOrphanCollectiveFileInFileSystem($sPathName)
{
	$aError			= array();
	$sErrorString	= bab_translate('The file %file% exists on the file system but not on the database');
	$oFolderFileSet	= bab_getInstance('BAB_FolderFileSet');	
	$oIdDelegation	= $oFolderFileSet->aField['iIdDgOwner'];
	$oPathName		= $oFolderFileSet->aField['sPathName'];
	$oName			= $oFolderFileSet->aField['sName'];
	$oGroup			= $oFolderFileSet->aField['sGroup'];
		
	$oDirIterator = new RecursiveIteratorIterator(new RecursiveDirIterator($sPathName), true);
	foreach($oDirIterator as $oIterator)
	{
		if($oIterator->isFile())
		{
			if('OVF' === mb_substr($oIterator->getPath(), -3))
			{
				continue;
			}
			
			$sRelativePath	= str_replace('\\', '/', mb_substr($oIterator->getPath(), mb_strlen($sPathName)));
			$sDgPath		= getFirstPath($sRelativePath);
			
			$aBuffer = array();
			if(preg_match('/DG(\d+)/', $sDgPath, $aBuffer))
			{
				$iIdDelegation = (int) $aBuffer[1];
				$sRelPath = removeFirstPath($sRelativePath);
				if($sRelPath != $sRelativePath)
				{
					$sRelPath = addEndSlash($sRelPath);
				}
				else
				{
					$sRelPath = '';
				}
				
				$oCriteria = $oGroup->in('Y');
				$oCriteria = $oCriteria->_and($oIdDelegation->in($iIdDelegation));
				$oCriteria = $oCriteria->_and($oPathName->in($sRelPath));
				$oCriteria = $oCriteria->_and($oName->in($oIterator->getFilename()));
				
				$oFile = $oFolderFileSet->get($oCriteria);
				
				//bab_debug($oFolderFileSet->getSelectQuery($oCriteria));
				
				if(is_null($oFile))
				{
					$aError[] = str_replace('%file%', $oIterator->getPathname(), $sErrorString);
				}
			}
		}
	}
	return $aError;
}


/**
 * This function lookup the user file in the file system
 * that is not in the database
 *  
 * @param	string $sPathName	The full path of the collective folder
 * @return	array				An empty array on success 
 */
function lookupForOrphanUserFileInFileSystem($sPathName)
{
	$aError			= array();
	$sErrorString	= bab_translate('The file %file% exists on the file system but not on the database');
	$oFolderFileSet	= bab_getInstance('BAB_FolderFileSet');	
	$oIdDelegation	= $oFolderFileSet->aField['iIdDgOwner'];
	$oPathName		= $oFolderFileSet->aField['sPathName'];
	$oName			= $oFolderFileSet->aField['sName'];
	$oGroup			= $oFolderFileSet->aField['sGroup'];
	$oIdOwner		= $oFolderFileSet->aField['iIdOwner'];
		
	$oDirIterator = new RecursiveIteratorIterator(new RecursiveDirIterator($sPathName), true);
	foreach($oDirIterator as $oIterator)
	{
		if($oIterator->isFile())
		{
			$sRelativePath	= str_replace('\\', '/', mb_substr($oIterator->getPath(), mb_strlen($sPathName)));
			$sUserPath		= getFirstPath($sRelativePath);
			
			$aBuffer = array();
			if(preg_match('/U(\d+)/', $sUserPath, $aBuffer))
			{
				$iIdUser = (int) $aBuffer[1];
				$sRelPath = removeFirstPath($sRelativePath);
				if($sRelPath != $sRelativePath)
				{
					$sRelPath = addEndSlash($sRelPath);
				}
				else
				{
					$sRelPath = '';
				}
				
				$oCriteria = $oGroup->in('N');
				$oCriteria = $oCriteria->_and($oIdOwner->in($iIdUser));
				$oCriteria = $oCriteria->_and($oPathName->in($sRelPath));
				$oCriteria = $oCriteria->_and($oName->in($oIterator->getFilename()));
				
				$oFile = $oFolderFileSet->get($oCriteria);
				
				//bab_debug($oFolderFileSet->getSelectQuery($oCriteria));
				
				if(is_null($oFile))
				{
					$aError[] = str_replace('%file%', $oIterator->getPathname(), $sErrorString);
				}
			}
		}
	}
	return $aError;
}


/**
 * This function return the upload path
 *
 * @return string The upload path
 */
function getUploadPathFromDataBase()	
{
	global $babDB;
	
	$aData = $babDB->db_fetch_array($babDB->db_query('SELECT uploadpath FROM ' . BAB_SITES_TBL . ' WHERE name= ' . $babDB->quote($GLOBALS['babSiteName'])));
	if(false != $aData)
	{
		$sUploadPath	= (string) $aData['uploadpath'];
		$sUploadPath	= realpath($sUploadPath);
		$sUploadPath	= str_replace('\\', '/', $sUploadPath);
		$iLength		= mb_strlen($sUploadPath);
		$sLastChar		= mb_substr($sUploadPath, -1);
		
		if($iLength && '/' !== $sLastChar)
		{
			$sUploadPath .= '/';
			return $sUploadPath;
		}
		return $sUploadPath;
	}
}	




/**
 * This function convert the database to latin1
 *
 * @param string $sCharset		Character set
 *
 * @param string $sDataBaseName Name of the database, if null the database
 * 								name will be retreived from the global variable
 * 
 * @return array				Empty array on success,
 */	
function convertDataBaseToCharset($sCharset, $sDataBaseName = null)
{
	$aError = array();
	
	if(!isset($sDataBaseName))
	{
		$sDataBaseName = $GLOBALS['babDBName'];	
	}

	$sCollate	= '';
	$aCharset	= array('utf8' => 'utf8_general_ci', 'latin1' => 'latin1_swedish_ci');
	
	if(!array_key_exists($sCharset, $aCharset))
	{
		$aSearch	= array('%unsupportedCharset%', '%supportedCharset%');
		$aReplace	= array($sCharset, 'utf8, latin1');
		$sMessage	= str_replace($aSearch, $aReplace, bab_translate('The database could not be converted in %unsupportedCharset% because this character set is not valid. The good values are %supportedCharset%'));
		$aError[]	= $sMessage;
		return $aError;
	}
	
	$sCollate = $aCharset[$sCharset];
	
	global $babDB;

	$sToCharset		= $sCharset;
	$sFromCharset	= ($sToCharset == 'utf8') ? 'latin1' : 'utf8';
	$aSearch		= array('%fromCharset%', '%toCharset%');
	$aReplace		= array($sFromCharset, $sToCharset);
	
	$sQuery			= 'SHOW TABLES FROM ' . $sDataBaseName;
	$oResult		= $babDB->db_query($sQuery);
	
	if(false === $oResult)
	{
		$sMessage	= str_replace($aSearch, $aReplace, bab_translate('The database could not be converted from %fromCharset% to %toCharset% because the command SHOW TABLES has failed'));
		$aError[]	= $sMessage;
		return $aError;
	}
	
	while(false !== ($aData = $babDB->db_fetch_array($oResult)))
	{
		$sQuery = 'ALTER TABLE ' . $aData[0] . ' CONVERT TO CHARACTER SET DEFAULT';
		if(false === $babDB->db_query($sQuery))
		{
			$aSearch[]	= '%table%';
			$aReplace[]	= $aData[0];
			$sMessage	= str_replace($aSearch, $aReplace, bab_translate('The database could not be converted from %fromCharset% to %toCharset% because the command ALTER TABLE on table %table% has failed'));
			$aError[]	= $sMessage;
			return $aError;
		}
	}		
	
	$sQuery = 'ALTER DATABASE ' . $sDataBaseName . ' CHARACTER SET ' . $sCharset . ' DEFAULT CHARACTER SET ' . $sCharset . ' COLLATE ' . $sCollate . ' DEFAULT COLLATE ' . $sCollate;
	if(false === $babDB->db_query($sQuery))
	{
		$sMessage	= str_replace($aSearch, $aReplace, bab_translate('The database could not be converted from %fromCharset% to %toCharset% because the command ALTER DATABASE on database %database% has failed'));
		$aError[]	= $sMessage;
		return $aError;
	}
	
	return $aError;
}


function createCharsetConvertLogTable()
{
	require_once dirname(__FILE__) . '/../utilit/upgradeincl.php';
	
	if(!bab_isTable(CHARSET_LOG_TBL)) 
	{
		global $babDB;
		$babDB->db_query('
			CREATE TABLE `' . CHARSET_LOG_TBL . '` (
			  `iId` int(10) unsigned NOT NULL auto_increment,
			  `sMessage` text NOT NULL,
			  PRIMARY KEY  (`iId`)
			) 
		');
	}
}

function logToCharsetTable($sMessage)
{
	global $babDB;
	$sQuery = 
		'INSERT INTO ' . CHARSET_LOG_TBL . 
			'(' .
				'`iId`, ' .
				'`sMessage`' .
			') ' .
		'VALUES ' . 
			'(\'\', ' . 
				$babDB->quote($sMessage) . 
			')'; 
			
	return $babDB->db_query($sQuery);
}

function emptyCharsetLogTable()
{
	global $babDB;
	$sQuery = 'TRUNCATE TABLE `' . CHARSET_LOG_TBL . '`';
	return $babDB->db_query($sQuery);
}

function selectMessageFromCharsetTable()
{
	global $babDB;
	$sQuery = 
		'SELECT 
			`sMessage`
		FROM ' .
			CHARSET_LOG_TBL;
			
	return $babDB->db_query($sQuery);
}


class RecursiveDirIterator extends FilterIterator implements RecursiveIterator
{
    public function __construct($sPath) 
    {
        parent::__construct(new DirectoryIterator($sPath));
    }

    public function accept()
    {
    	if($this->getInnerIterator()->isDot())
    	{
    		return false;
    	}
    	return true;
    }
    
    public function hasChildren() 
    {
        return is_dir($this->getInnerIterator()->getPathname());
    }
    
    public function getChildren() 
    {
    	return new RecursiveDirIterator($this->getInnerIterator()->getPathname());
    }
} 


class DisplayMessage extends BAB_BaseFormProcessing
{
	private $oIterator = null;
	
	public function __construct($oIterator, $sTitle)
	{
		parent::BAB_BaseFormProcessing();

		if (is_array($oIterator) && !is_object($oIterator)) {
			$oIterator = new ArrayIterator($oIterator);
		}
		
		$this->oIterator = $oIterator;
		$this->set_data('sTitle', $sTitle);
	}
	
	public function __destruct()
	{
		
	}
	
	public function getNextMessage()
	{
		static $iIndex = 0;
		$this->set_data('sMessage', '');
		
		if(0 == $iIndex && false !== $this->oIterator->valid())
		{
			$iIndex++;
			$this->set_data('sMessage', $this->oIterator->current());
			$this->oIterator->next();
			return true;
		}
		return false;
	}
	
	public function getNextMessageItem()
	{
		$this->set_data('sMessageItem', '');
		
		if(false !== $this->oIterator->valid())
		{
			$this->set_data('sMessageItem', $this->oIterator->current());
			$this->oIterator->next();
			return true;
		}
		return false;
	}
	
	public function printTemplate()
	{
		global $babBody;
		 
		$this->raw_2_html(BAB_RAW_2_HTML_DATA);
	
		return bab_printTemplate($this, 'charset.html', 'displayMessage');	
	}


	public function display() 
	{
		global $babBody;
		$babBody->addStyleSheet('charset.css');
		$babBody->babecho($this->printTemplate());	
	}
}

class MessageIterator extends BAB_MySqlResultIterator
{
	public function __construct()
	{
		
	}
	
	public function getObject($aDatas)
	{
		return $aDatas['sMessage'];
	}
}

//End of helper classes, functions






function displayWarning()
{
	global $babBody;
	
	if(bab_isUserAdministrator())
	{
		$sFromCharset	= bab_charset::getDatabase();
		$sToCharset		= ($sFromCharset == 'utf8') ? 'latin1' : 'utf8';
		$aSearch		= array('%fromCharset%', '%toCharset%');
		$aReplace		= array($sFromCharset, $sToCharset);
		$sMessage		= str_replace($aSearch, $aReplace, bab_translate('Your site is in %fromCharset% and it will be converted to %toCharset%'));
		
		$oForm = new BAB_BaseFormProcessing();
		$oForm->set_caption('sTitle', bab_translate('Warning'));
		$oForm->set_caption('sWarningMsg', bab_translate('Before continuing, it is strongly advised to make a copy of the database and directory storage'));
		$oForm->set_caption('sWarningMsg2', bab_translate('When switching from utf8 to latin1, some characters may be lost'));
		$oForm->set_caption('sConvertFileSystem', bab_translate('Convert the file system'));
		$oForm->set_caption('sMessage', $sMessage);
		$oForm->set_caption('sClickHere', bab_translate('Continue'));
		
		$oForm->set_data('iDisplayWarningMsg2', ('utf8' == $sFromCharset));
		$oForm->set_data('sFromCharset', $sFromCharset);
		$oForm->set_data('sToCharset', $sToCharset);
	
		$oForm->raw_2_html(BAB_RAW_2_HTML_DATA);
		$oForm->raw_2_html(BAB_RAW_2_HTML_CAPTION);
		
		$babBody->addStyleSheet('charset.css');
		
		$babBody->babecho(bab_printTemplate($oForm, 'charset.html', 'displayWarning'));	
	}
	else
	{
		$babBody->addError(bab_translate("Access denied"));
	}
}


function convertSite()
{
	global $babBody;

	if(bab_isUserAdministrator())
	{
		$sFromCharset		= (string) bab_charset::getDatabase();
		$sToCharset			= ($sFromCharset == 'utf8') ? 'latin1' : 'utf8';
		$iConvertFileSystem	= (int) bab_gp('convertFileSystem', 0);
		$sUploadPath		= (string) getUploadPathFromDataBase();
		$aSearch			= array('%convertFrom%', '%convertTo%');
		$aReplace			= array($sFromCharset, $sToCharset);
		$sMessage			= str_replace($aSearch, $aReplace, bab_translate('The site may not be converted %convertFrom% in %convertTo% because of errors below'));
		
		if(bab_gp('sFromCharset') == $sFromCharset && bab_gp('sToCharset') == $sToCharset)
		{
			// verify addons
			$addons = bab_addonsInfos::getDbRows();
			$addons_errors = array(bab_translate('Addons not compatibles'));
			foreach($addons as $row) {
				$addon = bab_getAddonInfosInstance($row['title']);
				if (!$addon->isCharsetCompatible(bab_charset::getIsoCharsetFromDataBaseCharset($sToCharset))) {
					$aSearch			= array('%convertTo%'	, '%addonName%');
					$aReplace			= array($sToCharset		, $row['title']);
					$addons_errors[] 	= str_replace($aSearch	, $aReplace, bab_translate('The site may not be converted to %convertTo% because the addon %addonName% is not compatible with the charset (parameter mysql_character_set_database in ini file)'));
				}
			}

			if (1 < count($addons_errors)) {
				$oForm = new DisplayMessage($addons_errors, bab_translate('Error'));
				$oForm->display();
				return;
			}


			if(1 == $iConvertFileSystem)
			{
				$sIsoFrom	= (string) bab_charset::getIsoCharsetFromDataBaseCharset($sFromCharset); 
				$sIsoTo		= (string) bab_charset::getIsoCharsetFromDataBaseCharset($sToCharset);
				$aError		= checkFileManagerForError($sUploadPath, $sIsoFrom, $sIsoTo);
				
				if(0 != count($aError))
				{
					$oForm = new DisplayMessage(array_merge((array) $sMessage, $aError), bab_translate('Error'));
					$oForm->display();	
					return;
				}
			}

			
			$aError = convertDataBaseToCharset($sToCharset);
			if(0 != count($aError))
			{
				$oForm = new DisplayMessage(array_merge((array) $sMessage, $aError), bab_translate('Error'));
				$oForm->display();	
				return;
			}

			
			if(1 == $iConvertFileSystem)
			{
				$sFromCharset	= $sToCharset;
				$sToCharset		= ($sFromCharset == 'utf8') ? 'latin1' : 'utf8';
				$sUrl			= $GLOBALS['babUrl'] . 'index.php?tg=charset&idx=convertFileManager&sFromCharset=' . 
					urlencode($sFromCharset)  . '&sToCharset=' . urlencode($sToCharset);
				
				header('Location: ' . $sUrl);
				exit;
			}
			
			$aReplace = array($sToCharset, $sFromCharset);
			$sMessage = str_replace($aSearch, $aReplace, bab_translate('The site have been successfully converted from %convertFrom% to %convertTo%'));
			emptyCharsetLogTable();
			logToCharsetTable($sMessage);
			$sUrl = $GLOBALS['babUrl'] . 'index.php?tg=charset&idx=displaySuccessMessage';
			header('Location: ' . $sUrl);
			exit;			
		}
		else
		{
			$aError[] = bab_translate('The charset are not supported');
			$oForm = new DisplayMessage(array_merge((array) $sMessage, $aError), bab_translate('Error'));
			$oForm->display();
		}
	}
	else
	{
		$babBody->addError(bab_translate("Access denied"));
	}
}


function convertFileManager()
{
	global $babBody;

	if(bab_isUserAdministrator())
	{
		$aError			= array();
		$aSearch		= array('%convertFrom%', '%convertTo%');
		$sFromCharset	= (string) bab_gp('sFromCharset');
		$sToCharset		= (string) bab_gp('sToCharset');
		$aReplace		= array($sFromCharset, $sToCharset);
		$sMessage		= str_replace($aSearch, $aReplace, bab_translate('The site may not be converted %convertFrom% in %convertTo% because of errors below'));
		$sUploadPath	= (string) getUploadPathFromDataBase();
		
		$sDbCharset		= (string) bab_charset::getDatabase();
		$sFsCharset		= ($sDbCharset == 'utf8') ? 'latin1' : 'utf8';

		if($sFromCharset != $sDbCharset || $sToCharset != $sFsCharset)
		{
			$aError[] = bab_translate('The charset are not supported');
			$oForm = new DisplayMessage(array_merge((array) $sMessage, $aError), bab_translate('Error'));
			$babBody->addStyleSheet('charset.css');
			$babBody->babecho($oForm->printTemplate());
			return;
		}
		
		$sIsoFrom		= (string) bab_charset::getIsoCharsetFromDataBaseCharset($sFromCharset); 
		$sIsoTo			= (string) bab_charset::getIsoCharsetFromDataBaseCharset($sToCharset);
		$oFolderFileSet	= bab_getInstance('BAB_FolderFileSet');
		
		$oFolderFileSet->select();
	
		//Convertion des noms des fichiers
		while(null !== ($oFolderFile = $oFolderFileSet->next()))
		{
			$sRoot = $sUploadPath;
			
			if('Y' == $oFolderFile->getGroup())
			{
				$sRoot .= 'fileManager/collectives/DG' . $oFolderFile->getDelegationOwnerId() . '/'; 
			}
			else
			{
				$sRoot .= 'fileManager/users/U' . $oFolderFile->getOwnerId() . '/'; 
			}
			
			$sPathName			= mb_convert_encoding($sRoot . $oFolderFile->getPathName(), $sIsoTo, $sIsoFrom);
			$sFileName			= mb_convert_encoding($oFolderFile->getName(), $sIsoTo, $sIsoFrom);
			$sOldFullPathName	= $sPathName . $sFileName;
			
			if(!is_file($sOldFullPathName))
			{
				$sMessage = str_replace('%file%', $sOldFullPathName, bab_translate('The file %file% exists on the database but not on the file system'));
				$aError[] = $sMessage;
				continue;
			}

			if($sFileName != $oFolderFile->getName())
			{
				$sNewFullPathName = $sPathName . $oFolderFile->getName();
				if(is_file($sNewFullPathName))
				{
					$aSearch	= array('%file%', '%charset%');
					$aReplace	= array($sNewFullPathName, $sToCharset);
					$sMessage	= str_replace($aSearch, $aReplace, bab_translate('The name of the file %file% cannot be converted to %charset% because there is another file with the same name'));
					$aError[]	= $sMessage;
					continue;
				}
				else
				{
					$bSuccess = @rename($sOldFullPathName, $sNewFullPathName);
					if(false === $bSuccess)
					{
						$aSearch	= array('%oldFile%', '%newFile%');
						$aReplace	= array($sOldFullPathName, $sNewFullPathName);
						$sMessage	= str_replace($aSearch, $aReplace, bab_translate('The file %oldFile% cannot be renamed to %newFile%'));
						$aError[]	= $sMessage;
					}
					else if('Y' == $oFolderFile->getGroup())
					{
						$oFolderFileVersionSet = new BAB_FolderFileVersionSet();
						$oIdFile = $oFolderFileVersionSet->aField['iIdFile'];
						$oFolderFileVersionSet->select($oIdFile->in($oFolderFile->getId()));
	
						while(null !== ($oFolderFileVersion = $oFolderFileVersionSet->next()))
						{
							$sSrc = $sPathName . BAB_FVERSION_FOLDER . '/' .
								$oFolderFileVersion->getMajorVer() . ',' . $oFolderFileVersion->getMinorVer() .
								',' . $sFileName;
	
							$sTrg = $sPathName . BAB_FVERSION_FOLDER . '/' .
								$oFolderFileVersion->getMajorVer() . ',' . $oFolderFileVersion->getMinorVer() .
								',' . $oFolderFile->getName();
							
							//if(file_exists($sSrc))
							{
								rename($sSrc, $sTrg);
							}
						}
						
					}
				}
			}
		}

		if(0 == count($aError))
		{
			$sIdx = '';
			convertDirectoryNameRecursive(addEndSlash(removeEndSlashes($sUploadPath)) . 'fileManager', $sIsoTo, $sIsoFrom, $aError);
			
			emptyCharsetLogTable();
			if(0 == count($aError))
			{
				$sIdx = 'displaySuccessMessage';
				//La convertion du système de fichier intervient aprés celle de la base de donnée
				//donc il faut invertir car au début de la fonction on inverse
				$aReplace = array($sToCharset, $sFromCharset);
				$sMessage = str_replace($aSearch, $aReplace, bab_translate('The site have been successfully converted from %convertFrom% to %convertTo%'));
				logToCharsetTable($sMessage);
			}
			else
			{
				$sIdx = 'displayErrorMessage';
				$sMessage = bab_translate('The site is not completely converted due to the errors below, please restore your backup. Fix the error and try again.');
				logToCharsetTable($sMessage);
	
				foreach($aError as $sErrorItem)
				{
					logToCharsetTable($sErrorItem);
				}
			}
			
			$sUrl = $GLOBALS['babUrl'] . 'index.php?tg=charset&idx=' . urlencode($sIdx);
			header('Location: ' . $sUrl);
			exit;			
		}
		else
		{
			emptyCharsetLogTable();
			$sMessage = bab_translate('The site is not completely converted due to the errors below, please restore your backup. Fix the error and try again.');
			logToCharsetTable($sMessage);

			foreach($aError as $sErrorItem)
			{
				logToCharsetTable($sErrorItem);
			}
			
			$sUrl = $GLOBALS['babUrl'] . 'index.php?tg=charset&idx=displayErrorMessage';
			header('Location: ' . $sUrl);
			exit;			
		}
	}
	else
	{
		$babBody->addError(bab_translate("Access denied"));
	}
}


function displayErrorMessage()
{
	displayMessage(bab_translate('Error'));
}

function displaySuccessMessage()
{
	displayMessage(bab_translate('Convertion result'));
}


function displayMessage($sTitle)
{
	global $babBody;
	
	$oIterator = new MessageIterator();
	$oIterator->setMySqlResult(selectMessageFromCharsetTable());
	
	$oForm = new DisplayMessage($oIterator, $sTitle);
	
	$babBody->addStyleSheet('charset.css');
	$babBody->babecho($oForm->printTemplate());	
}





$sIdx = (string) bab_gp('idx', 'displayWarning');
bab_setTimeLimit(3600);


createCharsetConvertLogTable();

switch($sIdx)
{
	case 'displayWarning':
		displayWarning();
		break;
		
	case 'convertSite':
		convertSite();
		break;
		
	case 'convertFileManager':
		convertFileManager();
		break;

	/*	
	case 'convertDataBaseToUtf8':
		convertDataBaseToCharset('utf8');
		break;
		
	case 'convertDataBaseToLatin1':
		convertDataBaseToCharset('latin1');
		break;
	//*/
			
	case 'displayErrorMessage':
		displayErrorMessage();
		break;
			
	case 'displaySuccessMessage':
		displaySuccessMessage();
		break;
}

?>
