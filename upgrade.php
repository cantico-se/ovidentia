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


/**
 * Recursively deletes a filesystem directory.
 *
 * @param string	$sFullPathName
 */
function removeDir($sFullPathName)
{
	if(is_dir($sFullPathName))
	{
		$oHandle = opendir($sFullPathName);
		if(false !== $oHandle)
		{
			while($sName = readdir($oHandle))
			{
				if('.' !== $sName && '..' !== $sName)
				{
					if(is_dir($sFullPathName . '/' . $sName))
					{
						removeDir($sFullPathName . '/' . $sName);
					}
					else if(file_exists($sFullPathName . '/' . $sName))
					{
						@unlink($sFullPathName . '/' . $sName);
					}
				}
			}
			closedir($oHandle);
			@rmdir($sFullPathName);
		}
	}
}


function getUploadPathFromDataBase()	
{
	$babDB = &$GLOBALS['babDB'];
	
	$aData = $babDB->db_fetch_array($babDB->db_query('SELECT uploadpath FROM ' . BAB_SITES_TBL . ' WHERE name= ' . $babDB->quote($GLOBALS['babSiteName'])));
	if(false != $aData)
	{
		$sUploadPath = (string) $aData['uploadpath'];
		$sUploadPath = realpath($sUploadPath);
		$iLength = strlen(trim($sUploadPath));
		if($iLength && '/' !== $sUploadPath{$iLength - 1} && '\\' !== $sUploadPath{$iLength - 1})
		{
			$sUploadPath .= '/';
			return $sUploadPath;
		}
		return $sUploadPath;
	}
}	

function createFmDirectories($sUploadPath)
{
	$sCollectiveUploadPath = $sUploadPath . 'fileManager/collectives/';
	$sUserUploadPath = $sUploadPath . 'fileManager/users/';
	
	global $babBody;
	
	if(!is_writable($sUploadPath))
	{
		$babBody->addError('The directory ' . $sUploadPath . ' is not writable');
		return false;
	}
	
	
	if(!is_dir($sUploadPath . 'fileManager'))
	{
		$bCollDirCreated = false;
		$bUserDirCreated = false;
		if(@mkdir($sUploadPath . 'fileManager', 0777))
		{
			$bCollDirCreated = @mkdir($sUploadPath . 'fileManager/collectives', 0777);
			if(false === $bCollDirCreated)
			{
				$babBody->addError('The directory: ' . $sUploadPath . 'fileManager/collectives have not been created');
			}
			
			$bUserDirCreated = @mkdir($sUploadPath . 'fileManager/users', 0777);
			if(false === $bCollDirCreated)
			{
				$babBody->addError('The directory: ' . $sUploadPath . 'fileManager/users have not been created');
			}
		}
		else
		{
			$babBody->addError('The directory: ' . $sUploadPath . 'fileManager have not been created');
		}
		return ($bCollDirCreated && $bUserDirCreated);
	}
	else 
	{
		$babBody->addError('The upgrade of the file manager have not been made because the directory ' . 
			$sUploadPath . 'fileManager already exist');
	}
	return false;
}

/**
 * Upgrade the file manager
 *
 */
function fmUpgrade()
{
	$babDB = &$GLOBALS['babDB'];
	$sUploadPath = getUploadPathFromDataBase();
	$sCollectiveUploadPath = $sUploadPath . 'fileManager/collectives/';

	global $babBody;

	if(is_dir($sUploadPath))
	{
		if(true === createFmDirectories($sUploadPath))
		{
			$sQuery = 
				'SELECT 
					`id` iId,
					`folder` sName,
					`sRelativePath` sRelativePath,
					`id_dgowner` iIdDgOwner
				FROM ' .
					BAB_FM_FOLDERS_TBL;

			$oResult = $babDB->db_query($sQuery);
			if(false !== $oResult)
			{
				$aDatas = array();
				while(false !== ($aDatas = $babDB->db_fetch_assoc($oResult)))
				{
					$sOldPath = $sUploadPath . 'G' . $aDatas['iId'];
					
					//Dans les anciennes versions tant que l'on avait pas accédé au répertoire
					//il n'était pas créé
					if(!is_dir($sOldPath))
					{
						if(false === @mkdir($sOldPath, 0777))
						{
							$babBody->addError('The directory: ' . $sOldPath . ' have not been created');
							return false;
						}
					}
					
					if(is_dir($sOldPath))
					{
						$sDelegationId	= (string) $aDatas['iIdDgOwner'];
						$sNewPath		= $sCollectiveUploadPath . 'DG' . $sDelegationId;
						
						if(!is_dir($sNewPath))
						{
							if(false === @mkdir($sNewPath, 0777))
							{
								$babBody->addError('The directory: ' . $sNewPath . ' have not been created');
								return false;
							}
						}
						
						if(is_dir($sNewPath))
						{
							$sFolderName = $aDatas['sName'];
							$sFolderName = processDirName($sNewPath, $sFolderName);
							$sNewPath .= '/' .  $sFolderName;

							if(true === @rename($sOldPath, $sNewPath))
							{
								$sQuery = 
									'UPDATE ' . 
										BAB_FM_FOLDERS_TBL . '
									SET 
										`folder` = \'' . $babDB->db_escape_string($sFolderName) . '\' 
									WHERE 
										`id` = \'' . $babDB->db_escape_string($aDatas['iId']) . '\'';
								
								$babDB->db_query($sQuery);
								
								updateFolderFilePathName($aDatas['iIdDgOwner'], $aDatas['iId'], 'Y', $sFolderName);
							}
							else 
							{
								$babBody->addError('The directory : ' . $sOldPath . ' have not been renamed to ' . $sNewPath);
								return false;
							}
						}
					}
				}
			}

			updateUsersFolderFilePathName($sUploadPath);
			return true;
		}
	}
	else 
	{
		$babBody->addError('The upload path: ' . $sUploadPath . ' is not valid');
	}
	return false;
}

function updateFolderFilePathName($iIdDgOwner, $iIdOwner, $sGroup, $sDirName)
{
	$babDB = &$GLOBALS['babDB'];
	
	$sQuery = 
		'SELECT 
			`id` iId,
			`path` sPathName
		FROM ' .
			BAB_FILES_TBL . '
		WHERE 
			`id_owner` = \'' . $babDB->db_escape_string($iIdOwner) . '\' AND 
			`bgroup` = \'' . $babDB->db_escape_string($sGroup) . '\'';

	$oResult = $babDB->db_query($sQuery);
	if(false !== $oResult)
	{
		$aDatas = array();
		while(false !== ($aDatas = $babDB->db_fetch_assoc($oResult)))
		{
			$sPathName = $sDirName . '/' . $aDatas['sPathName'];
			if(strlen(trim($aDatas['sPathName'])) > 0)
			{
				$sPathName .= '/';
			}
			
			$sQuery = 
				'UPDATE ' . 
					BAB_FILES_TBL . '
				SET 
					`path` = \'' . $babDB->db_escape_string($sPathName) . '\', 
					`iIdDgOwner` = \'' . $babDB->db_escape_string($iIdDgOwner) . '\' 
				WHERE 
					`id` = \'' . $babDB->db_escape_string($aDatas['iId']) . '\'';
			
			$babDB->db_query($sQuery);
		}		
	}
	return true;
}

function updateUsersFolderFilePathName($sUploadPath)
{
	global $babBody;
	$babDB = &$GLOBALS['babDB'];

	$sQuery = 
		'SELECT 
			`id` iId,
			`path` sPathName
		FROM ' .
			BAB_FILES_TBL . '
		WHERE 
			`bgroup` = \'' . $babDB->db_escape_string('N') . '\'';
	
	$oResult = $babDB->db_query($sQuery);
	if(false !== $oResult)
	{
		$aBuffer = array();
		while(false !== ($aDatas = $babDB->db_fetch_assoc($oResult)))
		{
			$sPathName = $aDatas['sPathName'];
			
			$iLength = strlen($sPathName);
			
			if($iLength > 0)
			{
				$sPathName = str_replace('\\', '/', $sPathName);
				if('/' !== $sPathName{$iLength - 1})
				{
					$sPathName .= '/';
				}
			}

			$sQuery = 
				'UPDATE ' . 
					BAB_FILES_TBL . '
				SET 
					`path` = \'' . $babDB->db_escape_string($sPathName) . '\', 
					`iIdDgOwner` = \'' . $babDB->db_escape_string(0) . '\' 
				WHERE 
					`id` = \'' . $babDB->db_escape_string($aDatas['iId']) . '\'';
				
			$babDB->db_query($sQuery);
		}
	}		
	
	$aBuffer = array();
	
	$oDir = dir($sUploadPath);
	while(false !== ($sEntry = $oDir->read())) 
	{
		// Skip pointers
		if($sEntry == '.' || $sEntry == '..') 
		{
			continue;
		}
		else if(is_dir($sUploadPath . $sEntry))
		{
			if(preg_match('/(U\d+)/', $sEntry, $aBuffer))
			{
				$sOldPath = $sUploadPath . $aBuffer[1];
				if(is_dir(realpath($sOldPath)))
				{
					$sUserUploadPath = $sUploadPath . 'fileManager/users/';
					$sNewPath = $sUserUploadPath . $aBuffer[1];
					if(!is_dir($sNewPath))
					{
						if(false === @rename(realpath($sOldPath), $sNewPath))
						{
							$babBody->addError('The directory: ' . $sOldPath . ' have not been renamed to ' . $sNewPath);
							return false;
						}
					}
				}
			}
		}
	}
	$oDir->close();
	return true;
}


function updateFmFromPreviousUpgrade()
{
	$babDB = &$GLOBALS['babDB'];
	$sUploadPath = getUploadPathFromDataBase();
	$sCollectiveUploadPath 	= $sUploadPath . 'fileManager/collectives/';

	global $babBody;
	
	if(is_dir($sUploadPath))
	{
		if(true === createFmDirectories($sUploadPath))
		{
			//Collective folders processing
			
			$sQuery = 
				'SELECT 
					`id` iId,
					`folder` sName,
					`id_dgowner` iIdDgOwner
				FROM ' .
					BAB_FM_FOLDERS_TBL . '
				WHERE 
					`sRelativePath` = \'' . $babDB->db_escape_string('') . '\'';
		
			$oResult = $babDB->db_query($sQuery);
			if(false !== $oResult)
			{
				$aDatas = array();
				while(false !== ($aDatas = $babDB->db_fetch_assoc($oResult)))
				{
					$sOldPath = $sUploadPath . $aDatas['sName'];
					if(is_dir($sOldPath))
					{
						$sDelegationId	= $aDatas['iIdDgOwner'];
						$sNewPath		= $sCollectiveUploadPath . 'DG' . $sDelegationId;
						
						if(!is_dir($sNewPath))
						{
							if(false === @mkdir($sNewPath, 0777))
							{
								$babBody->addError('The directory: ' . $sNewPath . ' have not been created');
								return false;
							}
						}
						
						if(is_dir($sNewPath))
						{
							$sNewPath .= '/' .  $aDatas['sName'];
							if(false === @rename($sOldPath, $sNewPath))
							{
								$babBody->addError('The directory: ' . $sOldPath . ' have not been renamed to ' . $sNewPath);
								return false;
							}
						}						
					}					
				}
			}
			
			$sQuery = 
				'SELECT 
					`id` iId,
					`id_dgowner` iIdDgOwner
				FROM ' .
					BAB_FM_FOLDERS_TBL;
		
			$oResult = $babDB->db_query($sQuery);
			if(false !== $oResult)
			{
				$aDatas = array();
				while(false !== ($aDatas = $babDB->db_fetch_assoc($oResult)))
				{
					$sQuery = 
						'UPDATE ' . 
							BAB_FILES_TBL . '
						SET 
							`iIdDgOwner` = \'' . $babDB->db_escape_string($aDatas['iIdDgOwner']) . '\' 
						WHERE 
							`id_owner` = \'' . $babDB->db_escape_string($aDatas['iId']) . '\'';
					
					$babDB->db_query($sQuery);
				}
			}
			
			
			//Personnal folders processing
			updateUsersFolderFilePathName($sUploadPath);			
		}
	}	
	else 
	{
		$babBody->addError('The upload path: ' . $sUploadPath . ' is not valid');
		return false;
	}
	return true;
}


function processDirName($sUploadPath, $sDirName)
{
	if(isset($GLOBALS['babFileNameTranslation']))
	{
		$sDirName = strtr($sDirName, $GLOBALS['babFileNameTranslation']);
	}

	static $aTranslation = array('\\' => '_', '/' => '_', ':' => '_', '*' => '_', '?' => '_', '<' => '_', '>' => '_', '|' => '_');

	$sDirName = strtr($sDirName, $aTranslation);
	
	$iIdx = 0;
	
	$sTempDirName = $sDirName;
	while(is_dir($sUploadPath . $sTempDirName))
	{
		$sTempDirName = $sDirName . ((string) $iIdx);
		$iIdx++;
	}
	return $sTempDirName;
}


function __walkDirectoryRecursive($sPathName, $sCallbackFunction)
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
					if($sEntry != 'OVF')
					{
						__walkDirectoryRecursive($sFullPathName, $sCallbackFunction);	
					}
					else 
					{
						$sCallbackFunction($sFullPathName);
					}
				}
			}
		}
		$oDir->close();
	}
}


function __renameFmFileVersion($sPathName)
{
	if(is_dir($sPathName))
	{
		$oDir = dir($sPathName);
		while(false !== ($sEntry = $oDir->read())) 
		{
			$sFullPathName = $sPathName . '/' . $sEntry;

			if($sEntry == '.' || $sEntry == '..' || is_dir($sFullPathName)) 
			{
				continue;
			}
			else 
			{

				$iLength = strlen($sEntry);

				if(3 <= $iLength && '.' === (string) $sEntry{1})
				{
					$sFirst	= substr($sEntry, 0, 1);
					$sEnd	= substr($sEntry, 2);
					
					if(false !== $sFirst && false !== $sEnd)
					{
						$sVersionName = $sFirst . ',' . $sEnd;
						
						$sSrc = $sFullPathName;
						$sTrg = $sPathName . '/' . $sVersionName;
						if(file_exists($sSrc) && !file_exists($sTrg))
						{
							rename($sSrc, $sTrg);
						}
					}
				}
			}
		}
		$oDir->close();
	}
}


function __renameFmFilesVersions()
{
	$sUploadPath = getUploadPathFromDataBase();
	$sCollectiveUploadPath 	= $sUploadPath . 'fileManager/collectives';
	
	$sCallbackFunction = '__renameFmFileVersion';
	 __walkDirectoryRecursive($sCollectiveUploadPath, $sCallbackFunction);
}

function tskMgrFieldOrderUpgrade()
{
	require_once $GLOBALS['babInstallPath'] . 'utilit/upgradeincl.php';
	
	global $babDB;
	if(!bab_isTable(BAB_TSKMGR_TASK_FIELDS_TBL))  
	{
		$babDB->db_query("
			CREATE TABLE `".BAB_TSKMGR_TASK_FIELDS_TBL."` (
			  `iId` int(10) UNSIGNED NOT NULL auto_increment,
			  `sName` VARCHAR (60) NOT NULL,
			  `sLegend` VARCHAR (255) NOT NULL,
			  `iType` SMALLINT( 2 ) NOT NULL,
			  PRIMARY KEY  (`iId`),
			  KEY `sName` (`sName`),
			  KEY `iType` (`iType`)
			)
		");

		$aTaskField = array(		
			array('iId' => 1,	'sName' => 'sProjectSpaceName', 				'sLegend' => 'Name of space project',			'iType' => 0),
			array('iId' => 2,	'sName' => 'sProjectName', 						'sLegend' => 'Project Name',					'iType' => 0),
			array('iId' => 3,	'sName' => 'sTaskNumber', 						'sLegend' => 'Number of the task',				'iType' => 0),
			array('iId' => 4,	'sName' => 'sDescription', 						'sLegend' => 'Description of the task',			'iType' => 0),
			array('iId' => 5,	'sName' => 'sShortDescription', 				'sLegend' => 'Title',							'iType' => 0),
			array('iId' => 6,	'sName' => 'sClass', 							'sLegend' => 'Type', 							'iType' => 0),
			array('iId' => 7,	'sName' => 'sCreatedDate', 						'sLegend' => 'Creation Date', 					'iType' => 0),
			array('iId' => 8,	'sName' => 'sModifiedDate', 					'sLegend' => 'Date Modified', 					'iType' => 0),
			array('iId' => 9,	'sName' => 'iIdUserCreated', 					'sLegend' => 'Author of the establishment', 	'iType' => 0),
			array('iId' => 10,	'sName' => 'iIdUserModified', 					'sLegend' => 'Author of the amendment', 		'iType' => 0),
			array('iId' => 11,	'sName' => 'iCompletion', 						'sLegend' => 'Progress Rate', 					'iType' => 0),
			array('iId' => 12,	'sName' => 'iPriority', 						'sLegend' => 'Priority', 						'iType' => 0),
			array('iId' => 13,	'sName' => 'idOwner', 							'sLegend' => 'Responsible',				 		'iType' => 0),
			array('iId' => 14,	'sName' => 'startDate,plannedStartDate', 		'sLegend' => 'Start Date,Planned', 				'iType' => 0),
			array('iId' => 15,	'sName' => 'endDate,plannedEndDate', 			'sLegend' => 'End Date,Planned', 				'iType' => 0),
			array('iId' => 16,	'sName' => 'iTime,iPlannedTime',				'sLegend' => 'Time,Planned', 					'iType' => 0),
			array('iId' => 17,	'sName' => 'iCost,iPlannedCost',				'sLegend' => 'Cost,Planned', 					'iType' => 0),
			array('iId' => 18,	'sName' => 'iDuration',							'sLegend' => 'Duration of the task', 			'iType' => 0),
			array('iId' => 19,	'sName' => 'sCategoryName',						'sLegend' => 'Category', 						'iType' => 0),
			array('iId' => 20,	'sName' => 'sShortDescription,sProjectName',	'sLegend' => 'Title,Project Name', 			'iType' => 0),
		);		

		foreach($aTaskField as $aTaskFieldItem)
		{
			$sQuery = 
				'INSERT INTO ' . BAB_TSKMGR_TASK_FIELDS_TBL . ' ' .
					'(' .
						'`iId`, `sName`, `sLegend`, `iType` ' .
					') ' .
				'VALUES ' . 
					'(' . 
						$babDB->quote($aTaskFieldItem['iId']) . ', ' . 
						$babDB->quote($aTaskFieldItem['sName']) . ', ' . 
						$babDB->quote($aTaskFieldItem['sLegend']) . ', ' . 
						$babDB->quote($aTaskFieldItem['iType']) . 
					')'; 
					
			$babDB->db_query($sQuery);
		}
		
		$babDB->db_query("
			CREATE TABLE `".BAB_TSKMGR_SELECTED_TASK_FIELDS_TBL."` (
			  `iId` int(10) UNSIGNED NOT NULL auto_increment,
			  `iIdTaskField` int(10) UNSIGNED NOT NULL,
			  `sTableAlias` VARCHAR (5) NOT NULL,
			  `iIdProject` int(10) UNSIGNED NOT NULL,
			  `iPosition` SMALLINT( 2 ) NOT NULL,
			  `iType` SMALLINT( 2 ) NOT NULL,
			  PRIMARY KEY  (`iId`),
			  KEY `iIdTaskField` (`iIdTaskField`),
			  KEY `iIdProject` (`iIdProject`),
			  KEY `iType` (`iType`)
			)
		");
		
		//For my task and personnal task
		$aDefaultField = array(
			array('iIdTaskField' => 20, 'sTableAlias' => '', 'iIdProject' => 0, 'iPosition' => 1, 'iType' => 0),
			array('iIdTaskField' => 6,  'sTableAlias' => '', 'iIdProject' => 0, 'iPosition' => 2, 'iType' => 0),
			array('iIdTaskField' => 14, 'sTableAlias' => '', 'iIdProject' => 0, 'iPosition' => 3, 'iType' => 0),
			array('iIdTaskField' => 15, 'sTableAlias' => '', 'iIdProject' => 0, 'iPosition' => 4, 'iType' => 0),
		);
		
		foreach($aDefaultField as $aDefaultFieldItem)
		{
			$sQuery = 
				'INSERT INTO ' . BAB_TSKMGR_SELECTED_TASK_FIELDS_TBL . ' ' .
					'(' .
						'`iId`, `iIdTaskField`, `sTableAlias`, `iIdProject`,  `iPosition`, `iType` ' .
					') ' .
				'VALUES ' . 
					'(\'\', ' . 
						$babDB->quote($aDefaultFieldItem['iIdTaskField']) . ', ' . 
						$babDB->quote($aDefaultFieldItem['sTableAlias']) . ', ' . 
						$babDB->quote($aDefaultFieldItem['iIdProject']) . ', ' . 
						$babDB->quote($aDefaultFieldItem['iPosition']) . ', ' . 
						$babDB->quote($aDefaultFieldItem['iType']) . 
					')'; 
					
			$babDB->db_query($sQuery);
		}
		
		//For project
		$aDefaultField[0]['iIdTaskField'] = 5;
		$aDefaultField[] = array('iIdTaskField' => 17, 'sTableAlias' => '', 'iIdProject' => 0, 'iPosition' => 5, 'iType' => 0);
		$aDefaultField[] = array('iIdTaskField' => 16, 'sTableAlias' => '', 'iIdProject' => 0, 'iPosition' => 6, 'iType' => 0);
		$aDefaultField[] = array('iIdTaskField' => 13, 'sTableAlias' => '', 'iIdProject' => 0, 'iPosition' => 7, 'iType' => 0);
		
		$sQuery = 
			'SELECT 
				`id` iId
			FROM ' .
				BAB_TSKMGR_PROJECTS_TBL;
	
		$oResultProject = $babDB->db_query($sQuery);
		if(false !== $oResultProject)
		{
			$iNumRows = $babDB->db_num_rows($oResultProject);
			if(0 < $iNumRows)
			{
				$aDatasProject = array();
				while(false !== ($aDatasProject = $babDB->db_fetch_assoc($oResultProject)))
				{
					foreach($aDefaultField as $aDefaultFieldItem)
					{
						$sQuery = 
							'INSERT INTO ' . BAB_TSKMGR_SELECTED_TASK_FIELDS_TBL . ' ' .
								'(' .
									'`iId`, `iIdTaskField`, `sTableAlias`, `iIdProject`,  `iPosition`, `iType` ' .
								') ' .
							'VALUES ' . 
								'(\'\', ' . 
									$babDB->quote($aDefaultFieldItem['iIdTaskField']) . ', ' . 
									$babDB->quote($aDefaultFieldItem['sTableAlias']) . ', ' . 
									$babDB->quote($aDatasProject['iId']) . ', ' . 
									$babDB->quote($aDefaultFieldItem['iPosition']) . ', ' . 
									$babDB->quote($aDefaultFieldItem['iType']) . 
								')'; 
								
						$babDB->db_query($sQuery);
					}
				}
			}
		}
	}
}


function upgrade553to554()
{
$ret = "";
$db = & $GLOBALS['babDB'];

$arr = $db->db_fetch_array($db->db_query("DESCRIBE ".BAB_CAL_EVENTS_TBL." location"));
if ( $arr[0] != 'location' )
	{
	$res = $db->db_query("ALTER TABLE ".BAB_CAL_EVENTS_TBL." ADD location VARCHAR(255) NOT NULL AFTER description");
	if( !$res)
		{
		$ret = "Alteration of <b>".BAB_CAL_EVENTS_TBL."</b> table failed !<br>";
		return $ret;
		}
	}


$arr = $db->db_fetch_array($db->db_query("SHOW TABLES LIKE '".BAB_CAL_EVENTS_NOTES_TBL."'"));
if ( $arr[0] != BAB_CAL_EVENTS_NOTES_TBL )
	{
	$res = $db->db_query("CREATE TABLE ".BAB_CAL_EVENTS_NOTES_TBL." (
					id_event int(10) unsigned NOT NULL default '0',
					id_user int(10) unsigned NOT NULL default '0',
					note text NOT NULL,
					UNIQUE KEY id_event (id_event,id_user)
					)");


	if( !$res)
		{
		$ret = "Creation of <b>".BAB_CAL_EVENTS_NOTES_TBL."</b> table failed !<br>";
		return $ret;
		}
	}

$arr = $db->db_fetch_array($db->db_query("SHOW TABLES LIKE '".BAB_CAL_EVENTS_REMINDERS_TBL."'"));
if ( $arr[0] != BAB_CAL_EVENTS_REMINDERS_TBL )
	{
	$res = $db->db_query("CREATE TABLE ".BAB_CAL_EVENTS_REMINDERS_TBL." (
						  id_event int(11) unsigned NOT NULL default '0',
						  id_user int(11) unsigned NOT NULL default '0',
						  day smallint(3) NOT NULL default '0',
						  hour smallint(2) NOT NULL default '0',
						  minute smallint(2) NOT NULL default '0',
						  bemail enum('N','Y') NOT NULL default 'N',
						  processed enum('N','Y') NOT NULL default 'N',
						  KEY id_event (id_event,id_user)
						)");

	if( !$res)
		{
		$ret = "Creation of <b>".BAB_CAL_EVENTS_REMINDERS_TBL."</b> table failed !<br>";
		return $ret;
		}
	}

$arr = $db->db_fetch_array($db->db_query("SHOW TABLES LIKE '".BAB_FMMANAGERS_GROUPS_TBL."'"));
if ( $arr[0] != BAB_FMMANAGERS_GROUPS_TBL )
	{
	$req = "CREATE TABLE ".BAB_FMMANAGERS_GROUPS_TBL." (";
	$req .= "id int(11) unsigned NOT NULL auto_increment,";
	$req .= "id_object int(11) unsigned NOT NULL default '0',";
	$req .= "id_group int(11) unsigned NOT NULL default '0',";
	$req .= "PRIMARY KEY  (id),";
	$req .= "KEY id_object (id_object),";
	$req .= "KEY id_group (id_group)";
	$req .= ");";

	$res = $db->db_query($req);
	if( !$res)
		{
		$ret = "Creation of <b>".BAB_FMMANAGERS_GROUPS_TBL."</b> table failed !<br>";
		return $ret;
		}

	$res = $db->db_query("select id, manager, id_dgowner from ".BAB_FM_FOLDERS_TBL."");
	$arrusersgroups = array();
	while( $arr = $db->db_fetch_array($res))
		{
		if( $arr['manager'] != 0 )
			{
			if( !isset($arrusersgroups[$arr['manager']])) 
				{
				$res2 = $db->db_query("select firstname, lastname from ".BAB_USERS_TBL." where id='".$arr['manager']."'");
				$rr = $db->db_fetch_array($res2);
				if( $res2 && $db->db_num_rows($res2) > 0 )
					{
					$grpname = "OVFM_".$rr['firstname']."_".$rr['lastname'];
					$description = bab_translate("Folder manager");
					$db->db_query("insert into ".BAB_GROUPS_TBL." (name, description, mail, manager, id_dggroup, notes, contacts, pcalendar, id_dgowner) VALUES ('" .$grpname. "', '" . $description. "', 'N', '0', '".$arr['id_dgowner']."', 'N', 'N', 'N','0')");
					$id = $db->db_insert_id();
					$db->db_query("insert into ".BAB_USERS_GROUPS_TBL." (id_object, id_group) values ('".$arr['manager']."','".$id."')");
					$arrusersgroups[$arr['manager']] = $id;
					}
				}
			if( isset($arrusersgroups[$arr['manager']])) 
				{
				$db->db_query("insert into ".BAB_FMMANAGERS_GROUPS_TBL." (id_object, id_group) values ('".$arr['id']."','".$arrusersgroups[$arr['manager']]."')");
				}
			}
		}

	$db->db_query("ALTER TABLE ".BAB_FM_FOLDERS_TBL." DROP manager");
	}


$arr = $db->db_fetch_array($db->db_query("SHOW TABLES LIKE '".BAB_FAQMANAGERS_GROUPS_TBL."'"));
if ( $arr[0] != BAB_FAQMANAGERS_GROUPS_TBL )
	{
	$res = $db->db_query("
		CREATE TABLE `".BAB_FAQMANAGERS_GROUPS_TBL."` (
			  `id` int(11) unsigned NOT NULL auto_increment,
			  `id_object` int(11) unsigned NOT NULL default '0',
			  `id_group` int(11) unsigned NOT NULL default '0',
			  PRIMARY KEY  (`id`),
			  KEY `id_object` (`id_object`),
			  KEY `id_group` (`id_group`)
			)
		");
	if( !$res)
		{
		$ret = "Creation of <b>".BAB_FAQMANAGERS_GROUPS_TBL."</b> table failed !<br>";
		return $ret;
		}

	$res = $db->db_query("select id, id_manager, id_dgowner from ".BAB_FAQCAT_TBL."");
	$arrusersgroups = array();
	while( $arr = $db->db_fetch_array($res))
		{
		if( $arr['id_manager'] != 0 )
			{
			if( !isset($arrusersgroups[$arr['id_manager']])) 
				{
				$res2 = $db->db_query("select firstname, lastname from ".BAB_USERS_TBL." where id='".$arr['id_manager']."'");
				$rr = $db->db_fetch_array($res2);
				if( $res2 && $db->db_num_rows($res2) > 0 )
					{
					$grpname = "OVFAQ_".$rr['firstname']."_".$rr['lastname'];
					$description = bab_translate("Faq manager");
					$db->db_query("insert into ".BAB_GROUPS_TBL." (name, description, mail, manager, id_dggroup, notes, contacts, pcalendar, id_dgowner) VALUES ('" .$grpname. "', '" . $description. "', 'N', '0', '".$arr['id_dgowner']."', 'N', 'N', 'N','0')");
					$id = $db->db_insert_id();
					$db->db_query("insert into ".BAB_USERS_GROUPS_TBL." (id_object, id_group) values ('".$arr['id_manager']."','".$id."')");
					$arrusersgroups[$arr['id_manager']] = $id;

					}
				}
			if( isset($arrusersgroups[$arr['id_manager']])) 
				{
				$db->db_query("insert into ".BAB_FAQMANAGERS_GROUPS_TBL." (id_object, id_group) values ('".$arr['id']."','".$arrusersgroups[$arr['id_manager']]."')");
				}
			}
		}

	$db->db_query("ALTER TABLE ".BAB_FAQCAT_TBL." DROP id_manager");
	}

$arr = $db->db_fetch_array($db->db_query("DESCRIBE ".BAB_VAC_COLLECTIONS_TBL." id_cat"));
if ( $arr[0] != 'id_cat' )
	{
	$res = $db->db_query("ALTER TABLE ".BAB_VAC_COLLECTIONS_TBL." ADD id_cat INT(11) UNSIGNED NOT NULL");
	if( !$res)
		{
		$ret = "Alteration of <b>".BAB_VAC_COLLECTIONS_TBL."</b> table failed !<br>";
		return $ret;
		}
	$db->db_query("ALTER TABLE ".BAB_VAC_COLLECTIONS_TBL." ADD INDEX ( `id_cat` )");
	}

return $ret;
}

function upgrade554to555()
{
$ret = "";
$db = & $GLOBALS['babDB'];

$arr = $db->db_fetch_array($db->db_query("DESCRIBE ".BAB_VAC_RIGHTS_TBL." date_begin_valid"));
if ( $arr[0] != 'date_begin_valid' )
	{
	$res = $db->db_query("ALTER TABLE ".BAB_VAC_RIGHTS_TBL." ADD date_begin_valid DATE NOT NULL");
	if( !$res)
		{
		$ret = "Alteration of <b>".BAB_VAC_RIGHTS_TBL."</b> table failed !<br>";
		return $ret;
		}
	}

$arr = $db->db_fetch_array($db->db_query("DESCRIBE ".BAB_VAC_RIGHTS_TBL." date_end_valid"));
if ( $arr[0] != 'date_end_valid' )
	{
	$res = $db->db_query("ALTER TABLE ".BAB_VAC_RIGHTS_TBL." ADD date_end_valid DATE NOT NULL");
	if( !$res)
		{
		$ret = "Alteration of <b>".BAB_VAC_RIGHTS_TBL."</b> table failed !<br>";
		return $ret;
		}
	}

$arr = $db->db_fetch_array($db->db_query("DESCRIBE ".BAB_VAC_RIGHTS_TBL." date_end_fixed"));
if ( $arr[0] != 'date_end_fixed' )
	{
	$res = $db->db_query("ALTER TABLE ".BAB_VAC_RIGHTS_TBL." ADD date_end_fixed DATE NOT NULL");
	if( !$res)
		{
		$ret = "Alteration of <b>".BAB_VAC_RIGHTS_TBL."</b> table failed !<br>";
		return $ret;
		}
	}

$arr = $db->db_fetch_array($db->db_query("DESCRIBE ".BAB_VAC_RIGHTS_TBL." date_begin_fixed"));
if ( $arr[0] != 'date_begin_fixed' )
	{
	$res = $db->db_query("ALTER TABLE ".BAB_VAC_RIGHTS_TBL." ADD date_begin_fixed DATE NOT NULL");
	if( !$res)
		{
		$ret = "Alteration of <b>".BAB_VAC_RIGHTS_TBL."</b> table failed !<br>";
		return $ret;
		}
	}

$arr = $db->db_fetch_array($db->db_query("DESCRIBE ".BAB_VAC_RIGHTS_TBL." day_begin_fixed"));
if ( $arr[0] != 'day_begin_fixed' )
	{
	$res = $db->db_query("ALTER TABLE ".BAB_VAC_RIGHTS_TBL." ADD day_begin_fixed tinyint(3) unsigned NOT NULL default '0'");
	if( !$res)
		{
		$ret = "Alteration of <b>".BAB_VAC_RIGHTS_TBL."</b> table failed !<br>";
		return $ret;
		}
	}

$arr = $db->db_fetch_array($db->db_query("DESCRIBE ".BAB_VAC_RIGHTS_TBL." day_end_fixed"));
if ( $arr[0] != 'day_end_fixed' )
	{
	$res = $db->db_query("ALTER TABLE ".BAB_VAC_RIGHTS_TBL." ADD day_end_fixed tinyint(3) unsigned NOT NULL default '0'");
	if( !$res)
		{
		$ret = "Alteration of <b>".BAB_VAC_RIGHTS_TBL."</b> table failed !<br>";
		return $ret;
		}
	}

$db->db_query("ALTER TABLE ".BAB_VAC_RIGHTS_TBL." CHANGE `quantity` `quantity` DECIMAL( 3, 1 ) UNSIGNED DEFAULT '0' NOT NULL"); 
return $ret;
}

function upgrade555to556()
{
$ret = "";
$db = & $GLOBALS['babDB'];


$arr = $db->db_fetch_array($db->db_query("SHOW TABLES LIKE '".BAB_SITES_EDITOR_TBL."'"));
if ( $arr[0] != BAB_SITES_EDITOR_TBL )
	{
	$res = $db->db_query("
		CREATE TABLE `".BAB_SITES_EDITOR_TBL."` (
			  `id` int(10) unsigned NOT NULL auto_increment,
			  `id_site` int(10) unsigned NOT NULL default '0',
			  `use_editor` tinyint(3) unsigned NOT NULL default '1',
			  `filter_html` tinyint(3) unsigned NOT NULL default '0',
			  `tags` text NOT NULL,
			  `attributes` text NOT NULL,
			  `verify_href` tinyint(3) unsigned NOT NULL default '0',
			  `bitstring` varchar(255) NOT NULL default '',
			  PRIMARY KEY  (`id`),
			  KEY `id_site` (`id_site`)
			)
		");

	if( !$res)
		{
		$ret = "Creation of <b>".BAB_SITES_EDITOR_TBL."</b> table failed !<br>";
		return $ret;
		}
	}

return $ret;
}



function upgrade558to559()
{
$ret = "";
$db = & $GLOBALS['babDB'];

$arr = $db->db_fetch_array($db->db_query("SHOW TABLES LIKE '".BAB_CAL_RES_ADD_GROUPS_TBL."'"));
if ( $arr[0] != BAB_CAL_RES_ADD_GROUPS_TBL )
	{
	$req = "CREATE TABLE ".BAB_CAL_RES_ADD_GROUPS_TBL." (";
	$req .= "id int(11) unsigned NOT NULL auto_increment,";
	$req .= "id_object int(11) unsigned NOT NULL default '0',";
	$req .= "id_group int(11) unsigned NOT NULL default '0',";
	$req .= "PRIMARY KEY  (id),";
	$req .= "KEY id_object (id_object),";
	$req .= "KEY id_group (id_group)";
	$req .= ");";

	$res = $db->db_query($req);
	if( !$res)
		{
		$ret = "Creation of <b>".BAB_CAL_RES_ADD_GROUPS_TBL."</b> table failed !<br>";
		return $ret;
		}
	}

return $ret;
}

function upgrade559to560()
{
$ret = "";
$db = & $GLOBALS['babDB'];

return $ret;
}

function upgrade560to561()
{
$ret = "";
$db = & $GLOBALS['babDB'];

return $ret;
}

function upgrade561to562()
{
$ret = "";
$db = & $GLOBALS['babDB'];

return $ret;
}

function upgrade562to563()
{
$ret = "";
$db = & $GLOBALS['babDB'];

$arr = $db->db_fetch_array($db->db_query("SHOW TABLES LIKE '".BAB_VAC_PLANNING_TBL."'"));
if ( $arr[0] != BAB_VAC_PLANNING_TBL )
	{
	$req = "CREATE TABLE `".BAB_VAC_PLANNING_TBL."` (
		  `id_entity` int(10) unsigned NOT NULL default '0',
		  `id_user` int(10) unsigned NOT NULL default '0',
		  KEY `id_user` (`id_user`)
		)";

	$res = $db->db_query($req);
	if( !$res)
		{
		$ret = "Creation of <b>".BAB_VAC_PLANNING_TBL."</b> table failed !<br>";
		return $ret;
		}
	}

return $ret;
}



function upgrade563to564()
{
$objDelegat = array(
	BAB_SECTIONS_TBL, 
	BAB_TOPICS_CATEGORIES_TBL,
	BAB_FLOW_APPROVERS_TBL,
	BAB_FORUMS_TBL,
	BAB_FAQCAT_TBL,
	BAB_FM_FOLDERS_TBL,
	BAB_LDAP_DIRECTORIES_TBL,
	BAB_DB_DIRECTORIES_TBL,
	BAB_ORG_CHARTS_TBL
	);


$ret = "";
$db = & $GLOBALS['babDB'];

$arr = $db->db_fetch_array($db->db_query("DESCRIBE ".BAB_DG_GROUPS_TBL." id_group"));
if ($arr[0] != 'id_group')
	{
	$db->db_query("ALTER TABLE `".BAB_DG_GROUPS_TBL."` ADD `id_group` INT( 10 ) UNSIGNED");
	$db->db_query("ALTER TABLE `".BAB_DG_GROUPS_TBL."` ADD INDEX ( `id_group` )");

	$res = $db->db_query("
		CREATE TABLE `".BAB_DG_ADMIN_TBL."` (
		`id_user` INT UNSIGNED NOT NULL ,
		`id_dg` INT UNSIGNED NOT NULL ,
		INDEX ( `id_user` )
		)");

	if( !$res)
		{
		$ret = "Creation of <b>".BAB_DG_ADMIN_TBL."</b> table failed !<br>";
		return $ret;
		}

	$level3 = array();

	$res = $db->db_query("SELECT id, id_dggroup, name, id_dgowner FROM ".BAB_GROUPS_TBL."");
	while ($arr = $db->db_fetch_array($res))
		{
		if ($arr['id'] > 2 && 0 == $arr['id_dgowner'])
			$level3[$arr['id']] = $arr['name'];

		if ($arr['id_dgowner'] > 0)
			{
			$level4[$arr['id_dgowner']][$arr['id']] = $arr['name'];
			}


		if ($arr['id_dggroup'] > 0)
			{
			$current = $db->db_fetch_array($db->db_query("SELECT id_group FROM ".BAB_DG_GROUPS_TBL." WHERE id='".$arr['id_dggroup']."'"));
			if ($current['id_group'] == 0)
				{
				$db->db_query("UPDATE `".BAB_DG_GROUPS_TBL."` SET id_group='".$arr['id']."' WHERE id='".$arr['id_dggroup']."'");
				$id = $arr['id_dggroup'];
				}
			else
				{
				$db->db_query("INSERT INTO `".BAB_DG_GROUPS_TBL."` (name, description, groups, sections, articles, faqs, forums, calendars, mails, directories, approbations, filemanager, orgchart, id_group) VALUES (
					'".$current['name'].' - '.$arr['name']."', 
					'".$current['description']."',
					'".$current['groups']."',
					'".$current['sections']."', 
					'".$current['articles']."', 
					'".$current['faqs']."', 
					'".$current['forums']."', 
					'".$current['calendars']."', 
					'".$current['mails']."', 
					'".$current['directories']."', 
					'".$current['approbations']."', 
					'".$current['filemanager']."', 
					'".$current['orgchart']."', 
					'".$arr['id']."'
					)");

				$id = $db->db_insert_id();
				}

			$res2 = $db->db_query("SELECT id_object FROM ".BAB_DG_USERS_GROUPS_TBL." WHERE id_group='".$id."'");
			while ($row = $db->db_fetch_array($res2))
				{
				$db->db_query("INSERT INTO ".BAB_DG_ADMIN_TBL." (id_user, id_dg) VALUES ('".$row['id_object']."','".$id."')");
				}

			// $db->db_query("DROP table ".BAB_DG_USERS_GROUPS_TBL."");

			foreach($objDelegat as $table)
				{
				$db->db_query("UPDATE `".$table."` SET id_dgowner='".$id."' WHERE id_dgowner='".$arr['id']."'");
				}
			}
		}

	
	

	$db->db_query("ALTER TABLE `".BAB_GROUPS_TBL."` DROP `id_dggroup`");
	$db->db_query("ALTER TABLE `".BAB_GROUPS_TBL."` DROP `id_dgowner`");
	$db->db_query("ALTER TABLE `".BAB_GROUPS_TBL."` ADD `id_parent` int(10) unsigned default NULL");
	$db->db_query("ALTER TABLE `".BAB_GROUPS_TBL."` ADD `lf` int(10) unsigned NOT NULL default '0'");
	$db->db_query("ALTER TABLE `".BAB_GROUPS_TBL."` ADD `lr` int(10) unsigned NOT NULL default '0'");
	$db->db_query("ALTER TABLE `".BAB_GROUPS_TBL."` ADD `nb_set` int(10) unsigned default NULL");
	$db->db_query("ALTER TABLE `".BAB_GROUPS_TBL."` ADD `nb_groups` int(10) unsigned default NULL");

	$db->db_query("ALTER TABLE `".BAB_GROUPS_TBL."` CHANGE `mail` `mail` enum('N','Y') default NULL");
	$db->db_query("ALTER TABLE `".BAB_GROUPS_TBL."` CHANGE `ustorage` `ustorage` enum('N','Y') default NULL");
	$db->db_query("ALTER TABLE `".BAB_GROUPS_TBL."` CHANGE `notes` `notes` enum('Y','N') default NULL");
	$db->db_query("ALTER TABLE `".BAB_GROUPS_TBL."` CHANGE `contacts` `contacts` enum('Y','N') default NULL");
	$db->db_query("ALTER TABLE `".BAB_GROUPS_TBL."` CHANGE `directory` `directory` enum('N','Y') default NULL");
	$db->db_query("ALTER TABLE `".BAB_GROUPS_TBL."` CHANGE `pcalendar` `pcalendar` enum('Y','N') default NULL");

	$db->db_query("ALTER TABLE `".BAB_GROUPS_TBL."` ADD INDEX ( `id_parent` )");
	$db->db_query("ALTER TABLE `".BAB_GROUPS_TBL."` ADD INDEX ( `lf` )");
	$db->db_query("ALTER TABLE `".BAB_GROUPS_TBL."` ADD INDEX ( `lr` )");

	$db->db_query("ALTER TABLE `bab_groups` CHANGE `id` `id` INT( 11 ) UNSIGNED NOT NULL"); // remove auto_increment


	natcasesort($level3);
	$n = 3;
	foreach($level3 as $id_group => $name)
		{
		$db->db_query("UPDATE `".BAB_GROUPS_TBL."` SET id_parent='".BAB_REGISTERED_GROUP."', lf='".$n."', lr='".($n+1)."', nb_set='0' WHERE id='".$id_group."'");
		$n = 2 + $n;

		if (isset($level4[$id_group]))
			{
			foreach($level4[$id_group] as $id_group2 => $name)
				{
				$db->db_query("UPDATE `".BAB_GROUPS_TBL."` SET id_parent='".$id_group."', lf='".$n."', lr='".($n+1)."', nb_set='0' WHERE id='".$id_group2."'");
				$n = 2 + $n;
				}
			}
		}

	$db->db_query("UPDATE `".BAB_GROUPS_TBL."` SET id_parent='".BAB_ALLUSERS_GROUP."', lf='2', lr='".$n."', nb_set='0' WHERE id='".BAB_REGISTERED_GROUP."'");
	$db->db_query("UPDATE `".BAB_GROUPS_TBL."` SET id_parent='".BAB_ALLUSERS_GROUP."', lf='".($n+1)."', lr='".($n+2)."', nb_set='0' WHERE id='".BAB_UNREGISTERED_GROUP."'");

	$db->db_query("INSERT INTO `".BAB_GROUPS_TBL."` (id, name, id_parent, lf, lr, nb_set) VALUES ('0', 'Ovidentia users',NULL,'1','".($n+3)."', '0')");

	$db->db_query("UPDATE `".BAB_GROUPS_TBL."` SET `name`='Registered users' WHERE `id` ='1'");
	$db->db_query("UPDATE `".BAB_GROUPS_TBL."` SET `name`='Anonymous users' WHERE `id` ='2'");

	$db->db_query("ALTER TABLE `".BAB_USERS_LOG_TBL."` CHANGE `id_dggroup` `id_dg` INT( 11 ) UNSIGNED DEFAULT '0' NOT NULL");
	$db->db_query("ALTER TABLE `".BAB_USERS_LOG_TBL."` ADD `grp_change` tinyint(1) unsigned default NULL");
	}


$arr = $db->db_fetch_array($db->db_query("SHOW TABLES LIKE '".BAB_GROUPS_SET_ASSOC_TBL."'"));
if ( $arr[0] != BAB_GROUPS_SET_ASSOC_TBL )
	{
	$res = $db->db_query("
			CREATE TABLE `".BAB_GROUPS_SET_ASSOC_TBL."` (
		  `id` int(10) unsigned NOT NULL auto_increment,
		  `id_group` int(10) unsigned NOT NULL default '0',
		  `id_set` int(10) unsigned NOT NULL default '0',
		  PRIMARY KEY  (`id`),
		  KEY `id_group` (`id_group`,`id_set`)
		)");

	if( !$res)
		{
		$ret = "Creation of <b>".BAB_GROUPS_SET_ASSOC_TBL."</b> table failed !<br>";
		return $ret;
		}
	}




$arr = $db->db_fetch_array($db->db_query("DESCRIBE ".BAB_SITES_TBL." change_lang"));
if ($arr[0] != 'change_lang')
	{
	$db->db_query("ALTER TABLE `".BAB_SITES_TBL."` ADD `change_lang` ENUM( 'Y', 'N' ) NOT NULL AFTER `change_nickname` ,
				ADD `change_skin` ENUM( 'Y', 'N' ) NOT NULL AFTER `change_lang` ,
				ADD `change_date` ENUM( 'Y', 'N' ) NOT NULL AFTER `change_skin` ,
				ADD `change_unavailability` ENUM( 'Y', 'N' ) NOT NULL AFTER `change_date`
				");

	}


$arr = $db->db_fetch_assoc($db->db_query("DESCRIBE ".BAB_SITES_EDITOR_TBL." id"));
if (strtolower($arr['Extra']) != 'auto_increment')
	{
	$db->db_query("ALTER TABLE `".BAB_SITES_EDITOR_TBL."` CHANGE `id` `id` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT");
	}


return $ret;
}



function upgrade564to565()
{
	
$ret = "";
$db = & $GLOBALS['babDB'];

$arr = $db->db_fetch_array($db->db_query("SHOW TABLES LIKE '".BAB_STATS_PREFERENCES_TBL."'"));
if ( $arr[0] != BAB_STATS_PREFERENCES_TBL )
	{
	$req = "CREATE TABLE `".BAB_STATS_PREFERENCES_TBL."` (
		  id_user int(11) unsigned NOT NULL default '0',
		  time_interval smallint(2) unsigned NOT NULL default '0',
		  begin_date varchar(10) NOT NULL default '',
		  end_date varchar(10) NOT NULL default '',
		  separatorchar tinyint(2) NOT NULL default '0',
		  UNIQUE KEY id_user (id_user)
		)";

	$res = $db->db_query($req);
	if( !$res)
		{
		$ret = "Creation of <b>".BAB_STATS_PREFERENCES_TBL."</b> table failed !<br>";
		return $ret;
		}
	}


return $ret;

}

function upgrade565to566()
{
	
$ret = "";
$db = & $GLOBALS['babDB'];

$arr = $db->db_fetch_array($db->db_query("SHOW TABLES LIKE '".BAB_DBDIR_OPTIONS_TBL."'"));
if ( $arr[0] != BAB_DBDIR_OPTIONS_TBL )
	{
	$req = "CREATE TABLE `".BAB_DBDIR_OPTIONS_TBL."` (
			`search_view_fields` VARCHAR( 255 ) DEFAULT '2,4' NOT NULL
			);";

	$res = $db->db_query($req);
	if( !$res)
		{
		$ret = "Creation of <b>".BAB_DBDIR_OPTIONS_TBL."</b> table failed !<br>";
		return $ret;
		}
	}

$arr = $db->db_fetch_array($db->db_query("DESCRIBE ".BAB_FLOW_APPROVERS_TBL." id_oc"));
if ($arr[0] != 'id_oc')
	{
	$res = $db->db_query("ALTER TABLE `".BAB_FLOW_APPROVERS_TBL."` ADD `id_oc` INT( 10 ) UNSIGNED  NOT NULL default '0'");
	if( !$res)
		{
		$ret = "Alteration of <b>".BAB_FLOW_APPROVERS_TBL."</b> table failed !<br>";
		return $ret;
		}
	}

list($iddir) = $db->db_fetch_row($db->db_query("select id from ".BAB_DB_DIRECTORIES_TBL." where id_group='1'"));
list($ocid) = $db->db_fetch_row($db->db_query("select id from ".BAB_ORG_CHARTS_TBL." where id_directory='".$iddir."' and isprimary='Y'"));
$db->db_query("update ".BAB_FLOW_APPROVERS_TBL." set id_oc='".$ocid."' where satype='1'");


$arr = $db->db_fetch_array($db->db_query("DESCRIBE ".BAB_DB_DIRECTORIES_TBL." ovml_list"));
if ($arr[0] != 'ovml_list')
	{
	$res = $db->db_query("ALTER TABLE `".BAB_DB_DIRECTORIES_TBL."` ADD ovml_list tinytext NOT NULL");
	if( !$res)
		{
		$ret = "Alteration of <b>".BAB_DB_DIRECTORIES_TBL."</b> table failed !<br>";
		return $ret;
		}
	}

$arr = $db->db_fetch_array($db->db_query("DESCRIBE ".BAB_DB_DIRECTORIES_TBL." ovml_detail"));
if ($arr[0] != 'ovml_detail')
	{
	$res = $db->db_query("ALTER TABLE `".BAB_DB_DIRECTORIES_TBL."` ADD ovml_detail tinytext NOT NULL");
	if( !$res)
		{
		$ret = "Alteration of <b>".BAB_DB_DIRECTORIES_TBL."</b> table failed !<br>";
		return $ret;
		}
	}

return $ret;
}




function upgrade566to570()
{
	
$ret = "";
$db = & $GLOBALS['babDB'];

$arr = $db->db_fetch_array($db->db_query("SHOW TABLES LIKE '".BAB_SITES_SWISH_TBL."'"));
if ( $arr[0] != BAB_SITES_SWISH_TBL )
	{
	$res = $db->db_query("
		CREATE TABLE `".BAB_SITES_SWISH_TBL."` (
		`id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
		`id_site` INT UNSIGNED NOT NULL ,
		`swishcmd` VARCHAR( 255 ) NOT NULL ,
		`pdftotext` VARCHAR( 255 ) NOT NULL ,
		`xls2csv` VARCHAR( 255 ) NOT NULL ,
		`catdoc` VARCHAR( 255 ) NOT NULL ,
		`unzip` VARCHAR( 255 ) NOT NULL ,
		PRIMARY KEY ( `id` ) ,
		INDEX ( `id_site` )
		)
		");

	if( !$res)
		{
		$ret = "Creation of <b>".BAB_SITES_SWISH_TBL."</b> table failed !<br>";
		return $ret;
		}
	}


$arr = $db->db_fetch_array($db->db_query("SHOW TABLES LIKE '".BAB_DBDIR_OPTIONS_TBL."'"));
if ( $arr[0] != BAB_DBDIR_OPTIONS_TBL )
	{
	$req = "CREATE TABLE `".BAB_DBDIR_OPTIONS_TBL."` (
			`search_view_fields` VARCHAR( 255 ) DEFAULT '2,4' NOT NULL
			);";

	$res = $db->db_query($req);
	if( !$res)
		{
		$ret = "Creation of <b>".BAB_DBDIR_OPTIONS_TBL."</b> table failed !<br>";
		return $ret;
		}
	}

return $ret;
}


function upgrade570to571()
{
	
$ret = "";
$db = & $GLOBALS['babDB'];

$arr = $db->db_fetch_array($db->db_query("DESCRIBE ".BAB_FM_FOLDERS_TBL." bhide"));
if ($arr[0] != 'bhide')
	{
	$res = $db->db_query("ALTER TABLE `".BAB_FM_FOLDERS_TBL."` ADD bhide ENUM('N','Y') DEFAULT 'N' NOT NULL");
	if( !$res)
		{
		$ret = "Alteration of <b>".BAB_FM_FOLDERS_TBL."</b> table failed !<br>";
		return $ret;
		}
	}

return $ret;
}




function upgrade571to572()
{
	
$ret = "";
$db = & $GLOBALS['babDB'];

$arr = $db->db_fetch_array($db->db_query("DESCRIBE ".BAB_DBDIR_ENTRIES_EXTRA_TBL." field_value"));
if ('text' != strtolower($arr['Type']))
	{
	$res = $db->db_query("ALTER TABLE `".BAB_DBDIR_ENTRIES_EXTRA_TBL."` CHANGE `field_value` `field_value` TEXT NOT NULL ");
	if( !$res)
		{
		$ret = "Alteration of <b>".BAB_DBDIR_ENTRIES_EXTRA_TBL."</b> table failed !<br>";
		return $ret;
		}
	}

$arr = $db->db_fetch_array($db->db_query("DESCRIBE ".BAB_SITES_TBL." ldap_filter"));
if ($arr[0] != 'ldap_filter')
	{
	$res = $db->db_query("ALTER TABLE ".BAB_SITES_TBL." ADD ldap_filter TEXT NOT NULL");
	if( !$res)
		{
		$ret = "Alteration of <b>".BAB_SITES_TBL."</b> table failed !<br>";
		return $ret;
		}

	$res = $db->db_query("select id, authentification from ".BAB_SITES_TBL."");
	while( $arr = $db->db_fetch_array($res))
		{
		switch( $arr['authentification'] )
			{
			case 1: // LDAP
				$filter = "(|(%UID=%NICKNAME))";
				break;
			case 2: // Active Directory
				$filter = "(|(samaccountname=%NICKNAME))";
				break;
			default: // Ovidentia
				$filter = "";
				break;
			}
		$db->db_query("update ".BAB_SITES_TBL." set ldap_filter='".$filter."' where id='".$arr['id']."'");
		}
	}

$arr = $db->db_fetch_array($db->db_query("DESCRIBE ".BAB_SITES_TBL." ldap_admindn"));
if ($arr[0] != 'ldap_admindn')
	{
	$res = $db->db_query("ALTER TABLE ".BAB_SITES_TBL." ADD ldap_admindn TEXT NOT NULL");
	if( !$res)
		{
		$ret = "Alteration of <b>".BAB_SITES_TBL."</b> table failed !<br>";
		return $ret;
		}
	}

$arr = $db->db_fetch_array($db->db_query("DESCRIBE ".BAB_SITES_TBL." ldap_adminpassword"));
if ($arr[0] != 'ldap_adminpassword')
	{
	$res = $db->db_query("ALTER TABLE ".BAB_SITES_TBL." ADD ldap_adminpassword tinyblob NOT NULL");
	if( !$res)
		{
		$ret = "Alteration of <b>".BAB_SITES_TBL."</b> table failed !<br>";
		return $ret;
		}
	}

return $ret;
}




function upgrade572to573()
{	
$ret = "";
$db = & $GLOBALS['babDB'];

$arr = $db->db_fetch_array($db->db_query("DESCRIBE ".BAB_VAC_RIGHTS_RULES_TBL." validoverlap"));
if ('validoverlap' != $arr[0])
	{
	$res = $db->db_query("ALTER TABLE `".BAB_VAC_RIGHTS_RULES_TBL."` ADD `validoverlap` TINYINT( 1 ) UNSIGNED NOT NULL AFTER `period_end`");
	if (!$res) {
		$ret = "Alteration of <b>".BAB_VAC_RIGHTS_RULES_TBL."</b> table failed !<br>";
		return $ret;
		}
	}

return $ret;
}



function upgrade573to574()
{	
$ret = "";
$db = & $GLOBALS['babDB'];

$arr = $db->db_fetch_array($db->db_query("SHOW TABLES LIKE '".BAB_VAC_OPTIONS_TBL."'"));
if ( $arr[0] != BAB_VAC_OPTIONS_TBL )
	{
	$db->db_query("CREATE TABLE `".BAB_VAC_OPTIONS_TBL."` (
	`chart_superiors_create_request` TINYINT( 1 ) UNSIGNED NOT NULL
	)");
	}

$arr = $db->db_fetch_array($db->db_query("DESCRIBE ".BAB_VAC_RIGHTS_TBL." no_distribution"));
if ('no_distribution' != $arr[0])
	{

	$db->db_query("ALTER TABLE `".BAB_VAC_RIGHTS_TBL."` ADD `no_distribution` TINYINT( 1 ) UNSIGNED NOT NULL");
	}

return $ret;
}

function upgrade574to575()
{	
$ret = "";
$db = & $GLOBALS['babDB'];

$arr = $db->db_fetch_array($db->db_query("SHOW TABLES LIKE '".BAB_FORUMSNOTIFY_GROUPS_TBL."'"));
if ( $arr[0] != BAB_FORUMSNOTIFY_GROUPS_TBL )
	{
	$req = "CREATE TABLE ".BAB_FORUMSNOTIFY_GROUPS_TBL." (";
	$req .= "id int(11) unsigned NOT NULL auto_increment,";
	$req .= "id_object int(11) unsigned NOT NULL default '0',";
	$req .= "id_group int(11) unsigned NOT NULL default '0',";
	$req .= "PRIMARY KEY  (id),";
	$req .= "KEY id_object (id_object),";
	$req .= "KEY id_group (id_group)";
	$req .= ");";

	$res = $db->db_query($req);
	if( !$res)
		{
		$ret = "Creation of <b>".BAB_FORUMSNOTIFY_GROUPS_TBL."</b> table failed !<br>";
		return $ret;
		}
	}

return $ret;
}

function upgrade575to576()
{	
$ret = "";
$db = & $GLOBALS['babDB'];

$arr = $db->db_fetch_array($db->db_query("DESCRIBE ".BAB_FORUMS_TBL." nb_recipients"));
if ('nb_recipients' != $arr[0])
	{

	$res = $db->db_query("ALTER TABLE ".BAB_FORUMS_TBL." ADD nb_recipients smallint(2) UNSIGNED NOT NULL");
	if( !$res)
		{
		$ret = "Alteration of <b>".BAB_FORUMS_TBL."</b> table failed !<br>";
		return $ret;
		}
	}

return $ret;
}




function upgrade577to578()
{	
$ret = "";
$db = & $GLOBALS['babDB'];

$arr = $db->db_fetch_array($db->db_query("DESCRIBE ".BAB_DBDIR_ENTRIES_TBL." date_modification"));
if ('date_modification' != $arr[0])
	{

	$res = $db->db_query("ALTER TABLE ".BAB_DBDIR_ENTRIES_TBL." ADD date_modification DATETIME NOT NULL");
	if( !$res)
		{
		$ret = "Alteration of <b>".BAB_DBDIR_ENTRIES_TBL."</b> table failed !<br>";
		return $ret;
		}

	$res = $db->db_query("ALTER TABLE ".BAB_DBDIR_ENTRIES_TBL." ADD id_modifiedby INT( 11 ) UNSIGNED DEFAULT '0' NOT NULL");
	if( !$res)
		{
		$ret = "Alteration of <b>".BAB_DBDIR_ENTRIES_TBL."</b> table failed !<br>";
		return $ret;
		}
	}

$arr = $db->db_fetch_array($db->db_query("SHOW TABLES LIKE '".BAB_DBDIRDEL_GROUPS_TBL."'"));
if ( $arr[0] != BAB_DBDIRDEL_GROUPS_TBL )
	{
	$req = "CREATE TABLE ".BAB_DBDIRDEL_GROUPS_TBL." (";
	$req .= "id int(11) unsigned NOT NULL auto_increment,";
	$req .= "id_object int(11) unsigned NOT NULL default '0',";
	$req .= "id_group int(11) unsigned NOT NULL default '0',";
	$req .= "PRIMARY KEY  (id),";
	$req .= "KEY id_object (id_object),";
	$req .= "KEY id_group (id_group)";
	$req .= ");";

	$res = $db->db_query($req);
	if( !$res)
		{
		$ret = "Creation of <b>".BAB_DBDIRDEL_GROUPS_TBL."</b> table failed !<br>";
		return $ret;
		}

	$db->db_query("insert into ".BAB_DBDIRDEL_GROUPS_TBL." select * from ".BAB_DBDIRADD_GROUPS_TBL."");
	}

$arr = $db->db_fetch_array($db->db_query("SHOW TABLES LIKE '".BAB_DBDIREXPORT_GROUPS_TBL."'"));
if ( $arr[0] != BAB_DBDIREXPORT_GROUPS_TBL )
	{
	$req = "CREATE TABLE ".BAB_DBDIREXPORT_GROUPS_TBL." (";
	$req .= "id int(11) unsigned NOT NULL auto_increment,";
	$req .= "id_object int(11) unsigned NOT NULL default '0',";
	$req .= "id_group int(11) unsigned NOT NULL default '0',";
	$req .= "PRIMARY KEY  (id),";
	$req .= "KEY id_object (id_object),";
	$req .= "KEY id_group (id_group)";
	$req .= ");";

	$res = $db->db_query($req);
	if( !$res)
		{
		$ret = "Creation of <b>".BAB_DBDIREXPORT_GROUPS_TBL."</b> table failed !<br>";
		return $ret;
		}
	
	$db->db_query("insert into ".BAB_DBDIREXPORT_GROUPS_TBL." select * from ".BAB_DBDIRADD_GROUPS_TBL."");
	}

$arr = $db->db_fetch_array($db->db_query("SHOW TABLES LIKE '".BAB_DBDIRIMPORT_GROUPS_TBL."'"));
if ( $arr[0] != BAB_DBDIRIMPORT_GROUPS_TBL )
	{
	$req = "CREATE TABLE ".BAB_DBDIRIMPORT_GROUPS_TBL." (";
	$req .= "id int(11) unsigned NOT NULL auto_increment,";
	$req .= "id_object int(11) unsigned NOT NULL default '0',";
	$req .= "id_group int(11) unsigned NOT NULL default '0',";
	$req .= "PRIMARY KEY  (id),";
	$req .= "KEY id_object (id_object),";
	$req .= "KEY id_group (id_group)";
	$req .= ");";

	$res = $db->db_query($req);
	if( !$res)
		{
		$ret = "Creation of <b>".BAB_DBDIRIMPORT_GROUPS_TBL."</b> table failed !<br>";
		return $ret;
		}
	$db->db_query("insert into ".BAB_DBDIRIMPORT_GROUPS_TBL." select * from ".BAB_DBDIRADD_GROUPS_TBL."");
	}

$arr = $db->db_fetch_array($db->db_query("SHOW TABLES LIKE '".BAB_DBDIRBIND_GROUPS_TBL."'"));
if ( $arr[0] != BAB_DBDIRBIND_GROUPS_TBL )
	{
	$req = "CREATE TABLE ".BAB_DBDIRBIND_GROUPS_TBL." (";
	$req .= "id int(11) unsigned NOT NULL auto_increment,";
	$req .= "id_object int(11) unsigned NOT NULL default '0',";
	$req .= "id_group int(11) unsigned NOT NULL default '0',";
	$req .= "PRIMARY KEY  (id),";
	$req .= "KEY id_object (id_object),";
	$req .= "KEY id_group (id_group)";
	$req .= ");";

	$res = $db->db_query($req);
	if( !$res)
		{
		$ret = "Creation of <b>".BAB_DBDIRBIND_GROUPS_TBL."</b> table failed !<br>";
		return $ret;
		}
	}

$arr = $db->db_fetch_array($db->db_query("SHOW TABLES LIKE '".BAB_DBDIRUNBIND_GROUPS_TBL."'"));
if ( $arr[0] != BAB_DBDIRUNBIND_GROUPS_TBL )
	{
	$req = "CREATE TABLE ".BAB_DBDIRUNBIND_GROUPS_TBL." (";
	$req .= "id int(11) unsigned NOT NULL auto_increment,";
	$req .= "id_object int(11) unsigned NOT NULL default '0',";
	$req .= "id_group int(11) unsigned NOT NULL default '0',";
	$req .= "PRIMARY KEY  (id),";
	$req .= "KEY id_object (id_object),";
	$req .= "KEY id_group (id_group)";
	$req .= ");";

	$res = $db->db_query($req);
	if( !$res)
		{
		$ret = "Creation of <b>".BAB_DBDIRUNBIND_GROUPS_TBL."</b> table failed !<br>";
		return $ret;
		}
	}

$arr = $db->db_fetch_array($db->db_query("SHOW TABLES LIKE '".BAB_DBDIREMPTY_GROUPS_TBL."'"));
if ( $arr[0] != BAB_DBDIREMPTY_GROUPS_TBL )
	{
	$req = "CREATE TABLE ".BAB_DBDIREMPTY_GROUPS_TBL." (";
	$req .= "id int(11) unsigned NOT NULL auto_increment,";
	$req .= "id_object int(11) unsigned NOT NULL default '0',";
	$req .= "id_group int(11) unsigned NOT NULL default '0',";
	$req .= "PRIMARY KEY  (id),";
	$req .= "KEY id_object (id_object),";
	$req .= "KEY id_group (id_group)";
	$req .= ");";

	$res = $db->db_query($req);
	if( !$res)
		{
		$ret = "Creation of <b>".BAB_DBDIREMPTY_GROUPS_TBL."</b> table failed !<br>";
		return $ret;
		}
	$db->db_query("insert into ".BAB_DBDIREMPTY_GROUPS_TBL." select * from ".BAB_DBDIRADD_GROUPS_TBL."");
	}

$arr = $db->db_fetch_array($db->db_query("DESCRIBE ".BAB_DB_DIRECTORIES_TBL." show_update_info"));
if ($arr[0] != 'show_update_info')
	{
	$res = $db->db_query("ALTER TABLE `".BAB_DB_DIRECTORIES_TBL."` ADD show_update_info ENUM('N','Y') DEFAULT 'N' NOT NULL");
	if( !$res)
		{
		$ret = "Alteration of <b>".BAB_DB_DIRECTORIES_TBL."</b> table failed !<br>";
		return $ret;
		}
	}

$arr = $db->db_fetch_array($db->db_query("SHOW TABLES LIKE '".BAB_DBDIR_FIELDSEXPORT_TBL."'"));
if ( $arr[0] != BAB_DBDIR_FIELDSEXPORT_TBL )
	{
	$req = "CREATE TABLE ".BAB_DBDIR_FIELDSEXPORT_TBL." (";
	$req .= "id int(11) unsigned NOT NULL auto_increment,";
	$req .= "id_user int(11) unsigned NOT NULL default '0',";
	$req .= "id_directory int(11) unsigned NOT NULL default '0',";
	$req .= "id_field int(11) unsigned NOT NULL default '0',";
	$req .= "ordering int(11) unsigned NOT NULL default '0',";
	$req .= "PRIMARY KEY  (id),";
	$req .= "KEY id_user (id_user),";
	$req .= "KEY id_directory (id_directory)";
	$req .= ");";

	$res = $db->db_query($req);
	if( !$res)
		{
		$ret = "Creation of <b>".BAB_DBDIR_FIELDSEXPORT_TBL."</b> table failed !<br>";
		return $ret;
		}
	}


$arr = $db->db_fetch_array($db->db_query("SHOW TABLES LIKE '".BAB_DBDIR_CONFIGEXPORT_TBL."'"));
if ( $arr[0] != BAB_DBDIR_CONFIGEXPORT_TBL )
	{
	$req = "CREATE TABLE ".BAB_DBDIR_CONFIGEXPORT_TBL." (";
	$req .= "id int(11) unsigned NOT NULL auto_increment,";
	$req .= "id_user int(11) unsigned NOT NULL default '0',";
	$req .= "id_directory int(11) unsigned NOT NULL default '0',";
	$req .= "separatorchar tinyint(2) NOT NULL default '0',";
	$req .= "PRIMARY KEY  (id),";
	$req .= "KEY id_user (id_user),";
	$req .= "KEY id_directory (id_directory)";
	$req .= ");";

	$res = $db->db_query($req);
	if( !$res)
		{
		$ret = "Creation of <b>".BAB_DBDIR_CONFIGEXPORT_TBL."</b> table failed !<br>";
		return $ret;
		}
	}


$arr = $db->db_fetch_array($db->db_query("DESCRIBE ".BAB_TOPICS_TBL." auto_approbation"));
if ($arr[0] != 'auto_approbation')
	{
	$res = $db->db_query("ALTER TABLE ".BAB_TOPICS_TBL." ADD auto_approbation ENUM('N','Y') DEFAULT 'N' NOT NULL");
	if( !$res)
		{
		$ret = "Alteration of <b>".BAB_TOPICS_TBL."</b> table failed !<br>";
		return $ret;
		}
	}

$arr = $db->db_fetch_array($db->db_query("DESCRIBE ".BAB_FM_FOLDERS_TBL." auto_approbation"));
if ($arr[0] != 'auto_approbation')
	{
	$res = $db->db_query("ALTER TABLE ".BAB_FM_FOLDERS_TBL." ADD auto_approbation ENUM('N','Y') DEFAULT 'N' NOT NULL");
	if( !$res)
		{
		$ret = "Alteration of <b>".BAB_FM_FOLDERS_TBL."</b> table failed !<br>";
		return $ret;
		}
	}

return $ret;
}





function upgrade578to579()
{	
$ret = "";
$db = & $GLOBALS['babDB'];

$arr = $db->db_fetch_array($db->db_query("DESCRIBE ".BAB_USERS_LOG_TBL." schi_change"));
if ($arr[0] != 'schi_change')
	{
	$res = $db->db_query("ALTER TABLE `".BAB_USERS_LOG_TBL."` ADD `schi_change` TINYINT( 1 ) UNSIGNED");
	if( !$res)
		{
		$ret = "Alteration of <b>".BAB_USERS_LOG_TBL."</b> table failed !<br>";
		return $ret;
		}
	}

return $ret;
}



function upgrade580to581()
{	
$ret = "";
$db = & $GLOBALS['babDB'];

$res = $db->db_query("select * from ".BAB_MIME_TYPES_TBL." where ext='sxw'");
if( !$res || $db->db_num_rows($res) == 0 )
	{
	$db->db_query("INSERT INTO ".BAB_MIME_TYPES_TBL." VALUES ('sxw', 'application/vnd.sun.xml.writer')");
	}

$arr = $db->db_fetch_array($db->db_query("DESCRIBE ".BAB_USERS_TBL." db_authentification"));
if ($arr[0] != 'db_authentification')
	{
	$res = $db->db_query("ALTER TABLE `".BAB_USERS_TBL."` ADD db_authentification  ENUM('N','Y') DEFAULT 'N' NOT NULL ");
	if( !$res)
		{
		$ret = "Alteration of <b>".BAB_USERS_TBL."</b> table failed !<br>";
		return $ret;
		}
	}


$arr = $db->db_fetch_array($db->db_query("DESCRIBE ".BAB_DG_GROUPS_TBL." users"));
if ($arr[0] != 'users')
	{
	$res = $db->db_query("ALTER TABLE `".BAB_DG_GROUPS_TBL."` ADD users ENUM( 'N', 'Y' ) DEFAULT 'N'NOT NULL AFTER `description` ");
	if( !$res)
		{
		$ret = "Alteration of <b>".BAB_DG_GROUPS_TBL."</b> table failed !<br>";
		return $ret;
		}
	}

$arr = $db->db_fetch_array($db->db_query("DESCRIBE ".BAB_SITES_TBL." dispdays"));
if ($arr[0] != 'dispdays')
	{
	$res = $db->db_query("ALTER TABLE `".BAB_SITES_TBL."` ADD `dispdays` VARCHAR( 20 ) DEFAULT '1,2,3,4,5' NOT NULL AFTER `workdays` ");
	if( !$res)
		{
		$ret = "Alteration of <b>".BAB_SITES_TBL."</b> table failed !<br>";
		return $ret;
		}
	}

$arr = $db->db_fetch_array($db->db_query("DESCRIBE ".BAB_SITES_TBL." startday"));
if ( $arr[0] != 'startday' )
	{
	$res = $db->db_query("ALTER TABLE ".BAB_SITES_TBL." ADD startday tinyint(4) unsigned NOT NULL default '0' AFTER `dispdays` ");
	if( !$res)
		{
		$ret = "Alteration of <b>".BAB_SITES_TBL."</b> table failed !<br>";
		return $ret;
		}
	}

return $ret;
}



function upgrade581to582()
{	
$ret = "";
$db = & $GLOBALS['babDB'];

$res = $db->db_query("SELECT uploadpath FROM ".BAB_SITES_TBL." WHERE name=".$db->quote($GLOBALS['babSiteName']));
$arr = $db->db_fetch_assoc($res);
$GLOBALS['babUploadPath'] = $arr['uploadpath'];

if (!bab_isTable(BAB_FORUMSFILES_TBL)) {
	
	$db->db_query("
		CREATE TABLE `".BAB_FORUMSFILES_TBL."` (
		`id` INT UNSIGNED NOT NULL auto_increment,
		`id_post` INT UNSIGNED NOT NULL ,
		`name` VARCHAR( 255 ) NOT NULL ,
		`description` TINYTEXT NOT NULL ,
		`index_status` TINYINT UNSIGNED NOT NULL ,
		PRIMARY KEY ( `id` ) ,
		INDEX ( `id_post` )
		)
	");

	// create existing files


	include_once $GLOBALS['babInstallPath']."utilit/forumincl.php";

	$res = $db->db_query("SELECT p.id, t.forum FROM ".BAB_POSTS_TBL." p, ".BAB_THREADS_TBL." t WHERE t.id = p.id_thread");
	while ($arr = $db->db_fetch_assoc($res)) {
		$files = bab_getPostFiles( $arr['forum'], $arr['id'] );

		foreach($files as $file) {
			$name = $file['name'];
			$db->db_query("INSERT INTO ".BAB_FORUMSFILES_TBL." 
				(id_post, name) 
			VALUES 
				('".$db->db_escape_string($arr['id'])."','".$db->db_escape_string($name)."')
			");
		}
	}

}


if (!bab_isTable(BAB_INDEX_FILES_TBL)) {

	$db->db_query("
		CREATE TABLE `".BAB_INDEX_FILES_TBL."` (
		  `id` int(10) unsigned NOT NULL auto_increment,
		  `name` varchar(255) NOT NULL default '',
		  `object` varchar(255) NOT NULL default '',
		  `index_onload` tinyint(1) unsigned NOT NULL default '0',
		  `index_disabled` tinyint(1) unsigned NOT NULL default '0',
		  PRIMARY KEY  (`id`),
		  UNIQUE KEY `name` (`name`),
		  UNIQUE KEY `object` (`object`),
		  KEY `object_2` (`object`)
		) 
	");

	include_once $GLOBALS['babInstallPath']."utilit/searchincl.php";
	
	bab_setIndexObject( 'bab_files', 'File manager', false);
	bab_setIndexObject( 'bab_art_files', 'Articles files', true);
	bab_setIndexObject( 'bab_forumsfiles', 'Forum post files', false);

}



if (!bab_isTable(BAB_REGISTRY_TBL)) {

	$db->db_query("
		CREATE TABLE `".BAB_REGISTRY_TBL."` (
		  `dirkey` varchar(255) NOT NULL default '',
		  `value` text NOT NULL,
		  `value_type` varchar(32) NOT NULL default '',
		  `create_id_user` int(10) unsigned NOT NULL default '0',
		  `update_id_user` int(10) unsigned NOT NULL default '0',
		  `createdate` datetime NOT NULL default '0000-00-00 00:00:00',
		  `lastupdate` datetime NOT NULL default '0000-00-00 00:00:00',
		  PRIMARY KEY  (`dirkey`)
		)
	");

}


if (!bab_isTableField(BAB_FILES_TBL, 'index_status')) {

	$db->db_query("ALTER TABLE `".BAB_FILES_TBL."` ADD `index_status` TINYINT( 1 ) UNSIGNED NOT NULL");
	$db->db_query("ALTER TABLE `".BAB_FILES_TBL."` ADD INDEX ( `index_status` )");

}


if (!bab_isTableField(BAB_FM_FILESVER_TBL, 'index_status')) {

	$db->db_query("ALTER TABLE `".BAB_FM_FILESVER_TBL."` ADD `index_status` TINYINT( 1 ) UNSIGNED NOT NULL");
}


if (!bab_isTableField(BAB_ART_FILES_TBL, 'index_status')) {

	$db->db_query("ALTER TABLE `".BAB_ART_FILES_TBL."` ADD `index_status` TINYINT( 1 ) UNSIGNED NOT NULL");
	$db->db_query("ALTER TABLE `".BAB_ART_FILES_TBL."` ADD INDEX ( `index_status` )");
}
	





$arr = $db->db_fetch_array($db->db_query("DESCRIBE ".BAB_SITES_TBL." browse_users"));
if ( $arr[0] != 'browse_users' )
	{
	$res = $db->db_query("ALTER TABLE ".BAB_SITES_TBL." ADD browse_users ENUM( 'N', 'Y' ) DEFAULT 'N' NOT NULL AFTER `email_password` ");
	if( !$res)
		{
		$ret = "Alteration of <b>".BAB_SITES_TBL."</b> table failed !<br>";
		return $ret;
		}
	}

$arr = $db->db_fetch_array($db->db_query("DESCRIBE ".BAB_STATS_IPAGES_TBL." id_dgowner"));
if ( $arr[0] != 'id_dgowner' )
	{
	$res = $db->db_query("ALTER TABLE ".BAB_STATS_IPAGES_TBL." ADD id_dgowner INT( 11 )  UNSIGNED DEFAULT '0' NOT NULL");
	if( !$res)
		{
		$ret = "Alteration of <b>".BAB_STATS_IPAGES_TBL."</b> table failed !<br>";
		return $ret;
		}
	}


if (!bab_isTable(BAB_STATS_ARTICLES_NEW_TBL)) {

	$db->db_query("
			CREATE TABLE `".BAB_STATS_ARTICLES_NEW_TBL."` (
			  `st_date` date NOT NULL default '0000-00-00',
			  `st_hour` tinyint(3) unsigned NOT NULL default '0',
			  `st_nb_articles` int(11) unsigned NOT NULL default '0',
			  `st_id_dgowner` int(11) unsigned NOT NULL default '0',
			  KEY `st_date` (`st_date`),
			  KEY `st_hour` (`st_hour`),
			  KEY `st_nb_articles` (`st_nb_articles`),
			  KEY `st_id_dgowner` (`st_id_dgowner`)
			)
	");

$res = $db->db_query("select at.date, tct.id_dgowner from ".BAB_ARTICLES_TBL." at left join ".BAB_TOPICS_TBL." tt on at.id_topic=tt.id left join ".BAB_TOPICS_CATEGORIES_TBL." tct on tt.id_cat=tct.id");


$results = array();

while( $arr = $db->db_fetch_array($res))
	{
	$rr = explode(" ", $arr['date']);
	$date = $rr[0];
	$time = $rr[1];
	$rr = explode(":", $time);
	$hour = $rr[0];
	settype($hour, "integer");
	if(!isset($results[$date][$hour][$arr['id_dgowner']]))
		{
		$results[$date][$hour][$arr['id_dgowner']] = 1;
		}
	else
		{
		$results[$date][$hour][$arr['id_dgowner']]++;
		}

	if( $arr['id_dgowner'] != 0 )
		{
		if(!isset($results[$date][$hour][0]))
			{
			$results[$date][$hour][0] = 1;
			}
		else
			{
			$results[$date][$hour][0]++;
			}
		}
	}

	reset($results);
	while( $r1 = each($results) ) 
	{
		reset($r1[1]);
		while( $r2 = each($r1[1]) ) 
		{
			reset($r2[1]);
			while( $r3 = each($r2[1]) ) 
			{
			$db->db_query("insert into ".BAB_STATS_ARTICLES_NEW_TBL." (st_date, st_hour, st_nb_articles, st_id_dgowner) values ('".$r1[0]."','".$r2[0]."','".$r3[1]."', '".$r3[0]."')");
			}
		}
	}

}

if (!bab_isTable(BAB_STATS_FMFILES_NEW_TBL)) {

	$db->db_query("
			CREATE TABLE `".BAB_STATS_FMFILES_NEW_TBL."` (
			  `st_date` date NOT NULL default '0000-00-00',
			  `st_hour` tinyint(3) unsigned NOT NULL default '0',
			  `st_nb_files` int(11) unsigned NOT NULL default '0',
			  `st_id_dgowner` int(11) unsigned NOT NULL default '0',
			  KEY `st_date` (`st_date`),
			  KEY `st_hour` (`st_hour`),
			  KEY `st_nb_files` (`st_nb_files`),
			  KEY `st_id_dgowner` (`st_id_dgowner`)
			)
	");

$res = $db->db_query("select ft.created, fft.id_dgowner from ".BAB_FILES_TBL." ft left join ".BAB_FM_FOLDERS_TBL." fft on ft.id_owner=fft.id where ft.bgroup='Y'");

while( $arr = $db->db_fetch_array($res))
	{
	$rr = explode(" ", $arr['created']);
	$date = $rr[0];
	$time = $rr[1];
	$rr = explode(":", $time);
	$hour = $rr[0];
	settype($hour, "integer");
	if(!isset($results[$date][$hour][$arr['id_dgowner']]))
		{
		$results[$date][$hour][$arr['id_dgowner']] = 1;
		}
	else
		{
		$results[$date][$hour][$arr['id_dgowner']]++;
		}

	if( $arr['id_dgowner'] != 0 )
		{
		if(!isset($results[$date][$hour][0]))
			{
			$results[$date][$hour][0] = 1;
			}
		else
			{
			$results[$date][$hour][0]++;
			}
		}
	}

	reset($results);
	while( $r1 = each($results) ) 
	{
		reset($r1[1]);
		while( $r2 = each($r1[1]) ) 
		{
			reset($r2[1]);
			while( $r3 = each($r2[1]) ) 
			{
			$db->db_query("insert into ".BAB_STATS_FMFILES_NEW_TBL." (st_date, st_hour, st_nb_files, st_id_dgowner) values ('".$r1[0]."','".$r2[0]."','".$r3[1]."', '".$r3[0]."')");
			}
		}
	}

}
return $ret;
}



function upgrade582to583()
{	
	$ret = "";
	$db = & $GLOBALS['babDB'];

	//miss in babinstall.sql version 5.8.2
	if (!bab_isTableField(BAB_STATS_IPAGES_TBL, 'id_dgowner')) {

		$db->db_query("ALTER TABLE `".BAB_STATS_IPAGES_TBL."` ADD id_dgowner INT( 11 )  UNSIGNED DEFAULT '0' NOT NULL");
	}

	if (!bab_isTable(BAB_INDEX_ACCESS_TBL)) {

		$db->db_query("
		
			CREATE TABLE ".BAB_INDEX_ACCESS_TBL." (
			  file_path varchar(255) NOT NULL,
			  id_object int(10) unsigned NOT NULL,
			  id_object_access int(10) unsigned NOT NULL,
			  object varchar(255) NOT NULL,
			  PRIMARY KEY  (file_path),
			  KEY object (object),
			  KEY id_object (id_object)
			)
			
		");

	}

	if (!bab_isTableField(BAB_ART_FILES_TBL, 'index_status')) {

		$db->db_query("ALTER TABLE `".BAB_ART_FILES_TBL."` ADD `index_status` TINYINT( 1 ) UNSIGNED NOT NULL");
		$db->db_query("ALTER TABLE `".BAB_ART_FILES_TBL."` ADD INDEX ( `index_status` )");
	}

if (!bab_isTableField(BAB_LDAP_DIRECTORIES_TBL, 'server_type')) {

		$db->db_query("ALTER TABLE `".BAB_LDAP_DIRECTORIES_TBL."` ADD server_type TINYINT( 1 ) UNSIGNED DEFAULT '0' NOT NULL AFTER `description` ");
	}

}

function upgrade583to584()
{	
	$ret = "";
	$db = & $GLOBALS['babDB'];

	$arr = $db->db_fetch_array($db->db_query('SHOW TABLES LIKE \'' . BAB_TSKMGR_PROJECTS_SPACES_TBL . '\''));
	if($arr[0] != BAB_TSKMGR_PROJECTS_SPACES_TBL)
	{
		$res = $db->db_query("
			CREATE TABLE `" . BAB_TSKMGR_PROJECTS_SPACES_TBL . "` (
				`id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
				`idDelegation` INTEGER UNSIGNED NOT NULL DEFAULT 0,
				`name` VARCHAR(255) NOT NULL default '',
				`description` TEXT NOT NULL default '',
				`created` DATETIME NOT NULL default '0000-00-00 00:00:00',
				`modified` DATETIME NOT NULL default '0000-00-00 00:00:00',
				`idUserCreated` INTEGER UNSIGNED NOT NULL DEFAULT 0,
				`idUserModified` INTEGER UNSIGNED NOT NULL DEFAULT 0,
				`refCount` INTEGER UNSIGNED NOT NULL DEFAULT 0,
				PRIMARY KEY(`id`),
				INDEX `idDelegation`(`idDelegation`)) TYPE=MyISAM
		");
		
		if(false == $res)
		{
			return $res;
		}
	}
	
	$arr = $db->db_fetch_array($db->db_query('SHOW TABLES LIKE \'' . BAB_TSKMGR_PROJECT_CREATOR_GROUPS_TBL . '\''));
	if($arr[0] != BAB_TSKMGR_PROJECT_CREATOR_GROUPS_TBL)
	{
		$res = $db->db_query("
			CREATE TABLE `" . BAB_TSKMGR_PROJECT_CREATOR_GROUPS_TBL . "` (
				`id` int( 11 ) unsigned NOT NULL AUTO_INCREMENT ,
				`id_object` int( 11 ) unsigned NOT NULL default '0',
				`id_group` int( 11 ) unsigned NOT NULL default '0',
				PRIMARY KEY ( `id` ) ,
				KEY `id_object` ( `id_object` ) ,
				KEY `id_group` ( `id_group` )) TYPE=MyISAM
		");
		
		if(false == $res)
		{
			return $res;
		}
	}
	
	$arr = $db->db_fetch_array($db->db_query('SHOW TABLES LIKE \'' . BAB_TSKMGR_PERSONNAL_TASK_CREATOR_GROUPS_TBL . '\''));
	if($arr[0] != BAB_TSKMGR_PERSONNAL_TASK_CREATOR_GROUPS_TBL)
	{
		$res = $db->db_query("
			CREATE TABLE `" . BAB_TSKMGR_PERSONNAL_TASK_CREATOR_GROUPS_TBL . "` (
				`id` int( 11 ) unsigned NOT NULL AUTO_INCREMENT ,
				`id_object` int( 11 ) unsigned NOT NULL default '0',
				`id_group` int( 11 ) unsigned NOT NULL default '0',
				PRIMARY KEY ( `id` ) ,
				KEY `id_object` ( `id_object` ) ,
				KEY `id_group` ( `id_group` )) TYPE=MyISAM
		");
		
		if(false == $res)
		{
			return $res;
		}
	}
	
	$arr = $db->db_fetch_array($db->db_query('SHOW TABLES LIKE \'' . BAB_TSKMGR_DEFAULT_PROJECTS_MANAGERS_GROUPS_TBL . '\''));
	if($arr[0] != BAB_TSKMGR_DEFAULT_PROJECTS_MANAGERS_GROUPS_TBL)
	{
		$res = $db->db_query("
			CREATE TABLE `" . BAB_TSKMGR_DEFAULT_PROJECTS_MANAGERS_GROUPS_TBL . "` (
				`id` int( 11 ) unsigned NOT NULL AUTO_INCREMENT ,
				`id_object` int( 11 ) unsigned NOT NULL default '0',
				`id_group` int( 11 ) unsigned NOT NULL default '0',
				PRIMARY KEY ( `id` ) ,
				KEY `id_object` ( `id_object` ) ,
				KEY `id_group` ( `id_group` )) TYPE=MyISAM
		");
		
		if(false == $res)
		{
			return $res;
		}
	}
	
	$arr = $db->db_fetch_array($db->db_query('SHOW TABLES LIKE \'' . BAB_TSKMGR_DEFAULT_PROJECTS_SUPERVISORS_GROUPS_TBL . '\''));
	if($arr[0] != BAB_TSKMGR_DEFAULT_PROJECTS_SUPERVISORS_GROUPS_TBL)
	{
		$res = $db->db_query("
			CREATE TABLE `" . BAB_TSKMGR_DEFAULT_PROJECTS_SUPERVISORS_GROUPS_TBL . "` (
				`id` int( 11 ) unsigned NOT NULL AUTO_INCREMENT ,
				`id_object` int( 11 ) unsigned NOT NULL default '0',
				`id_group` int( 11 ) unsigned NOT NULL default '0',
				PRIMARY KEY ( `id` ) ,
				KEY `id_object` ( `id_object` ) ,
				KEY `id_group` ( `id_group` )) TYPE=MyISAM
		");
		
		if(false == $res)
		{
			return $res;
		}
	}
	
	$arr = $db->db_fetch_array($db->db_query('SHOW TABLES LIKE \'' . BAB_TSKMGR_DEFAULT_PROJECTS_VISUALIZERS_GROUPS_TBL . '\''));
	if($arr[0] != BAB_TSKMGR_DEFAULT_PROJECTS_VISUALIZERS_GROUPS_TBL)
	{
		$res = $db->db_query("
			CREATE TABLE `" . BAB_TSKMGR_DEFAULT_PROJECTS_VISUALIZERS_GROUPS_TBL . "` (
				`id` int( 11 ) unsigned NOT NULL AUTO_INCREMENT ,
				`id_object` int( 11 ) unsigned NOT NULL default '0',
				`id_group` int( 11 ) unsigned NOT NULL default '0',
				PRIMARY KEY ( `id` ) ,
				KEY `id_object` ( `id_object` ) ,
				KEY `id_group` ( `id_group` )) TYPE=MyISAM
		");
		
		if(false == $res)
		{
			return $res;
		}
	}
	
	$arr = $db->db_fetch_array($db->db_query('SHOW TABLES LIKE \'' . BAB_TSKMGR_DEFAULT_TASK_RESPONSIBLE_GROUPS_TBL . '\''));
	if($arr[0] != BAB_TSKMGR_DEFAULT_TASK_RESPONSIBLE_GROUPS_TBL)
	{
		$res = $db->db_query("
			CREATE TABLE `" . BAB_TSKMGR_DEFAULT_TASK_RESPONSIBLE_GROUPS_TBL . "` (
				`id` int( 11 ) unsigned NOT NULL AUTO_INCREMENT ,
				`id_object` int( 11 ) unsigned NOT NULL default '0',
				`id_group` int( 11 ) unsigned NOT NULL default '0',
				PRIMARY KEY ( `id` ) ,
				KEY `id_object` ( `id_object` ) ,
				KEY `id_group` ( `id_group` )) TYPE=MyISAM
		");
		
		if(false == $res)
		{
			return $res;
		}
	}
	
	$arr = $db->db_fetch_array($db->db_query('SHOW TABLES LIKE \'' . BAB_TSKMGR_DEFAULT_PROJECTS_CONFIGURATION_TBL . '\''));
	if($arr[0] != BAB_TSKMGR_DEFAULT_PROJECTS_CONFIGURATION_TBL)
	{
		$res = $db->db_query("
			CREATE TABLE `" . BAB_TSKMGR_DEFAULT_PROJECTS_CONFIGURATION_TBL . "` (
				`id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
				`idProjectSpace` INTEGER UNSIGNED NOT NULL default '0',
				`tskUpdateByMgr` TINYINT UNSIGNED NOT NULL default '1',
				`endTaskReminder` MEDIUMINT UNSIGNED NOT NULL default '5',
				`tasksNumerotation` TINYINT UNSIGNED NOT NULL default '1',
				`emailNotice` TINYINT UNSIGNED NOT NULL default '1',
				`faqUrl` MEDIUMTEXT NOT NULL default '',
				PRIMARY KEY(`id`, `idProjectSpace`),
				INDEX `idProjectSpace`(`idProjectSpace`)) TYPE=MyISAM
		");
		
		if(false == $res)
		{
			return $res;
		}
	}
	
	$arr = $db->db_fetch_array($db->db_query('SHOW TABLES LIKE \'' . BAB_TSKMGR_SPECIFIC_FIELDS_BASE_CLASS_TBL . '\''));
	if($arr[0] != BAB_TSKMGR_SPECIFIC_FIELDS_BASE_CLASS_TBL)
	{
		$res = $db->db_query("
			CREATE TABLE `" . BAB_TSKMGR_SPECIFIC_FIELDS_BASE_CLASS_TBL . "` (
				`id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
				`name` VARCHAR(255) NOT NULL default '',
				`description` TEXT NOT NULL default '',
				`nature` TINYINT UNSIGNED NOT NULL default '1',
				`active` TINYINT UNSIGNED NOT NULL default '1',
				`refCount` INTEGER UNSIGNED NOT NULL default '0',
				`idProjectSpace` INTEGER UNSIGNED NOT NULL default '0',
				`idProject` INTEGER UNSIGNED NOT NULL default '0',
				`created` DATETIME NOT NULL default '0000-00-00 00:00:00',
				`idUserCreated` INTEGER UNSIGNED NOT NULL default '0',
				PRIMARY KEY(`id`),
				INDEX `name`(`name`),
				INDEX `idProjectSpace`(`idProjectSpace`),
				INDEX `idProject`(`idProject`)) TYPE=MyISAM	
		");
		
		if(false == $res)
		{
			return $res;
		}
	}
	
	$arr = $db->db_fetch_array($db->db_query('SHOW TABLES LIKE \'' . BAB_TSKMGR_SPECIFIC_FIELDS_TEXT_CLASS_TBL . '\''));
	if($arr[0] != BAB_TSKMGR_SPECIFIC_FIELDS_TEXT_CLASS_TBL)
	{
		$res = $db->db_query("
			CREATE TABLE `" . BAB_TSKMGR_SPECIFIC_FIELDS_TEXT_CLASS_TBL . "` (
				`id` INTEGER UNSIGNED NOT NULL,
				`defaultValue` VARCHAR(255) NOT NULL default '',
				`isDefaultValue` TINYINT UNSIGNED NOT NULL default '1',
				PRIMARY KEY(`id`)) TYPE=MyISAM
		");
		
		if(false == $res)
		{
			return $res;
		}
	}
	
	$arr = $db->db_fetch_array($db->db_query('SHOW TABLES LIKE \'' . BAB_TSKMGR_SPECIFIC_FIELDS_AREA_CLASS_TBL . '\''));
	if($arr[0] != BAB_TSKMGR_SPECIFIC_FIELDS_AREA_CLASS_TBL)
	{
		$res = $db->db_query("
			CREATE TABLE `" . BAB_TSKMGR_SPECIFIC_FIELDS_AREA_CLASS_TBL . "` (
				`id` INTEGER UNSIGNED NOT NULL,
				`defaultValue` TEXT NOT NULL default '',
				`isDefaultValue` TINYINT UNSIGNED NOT NULL default '1',
				PRIMARY KEY(`id`)) TYPE=MyISAM
		");
		
		if(false == $res)
		{
			return $res;
		}
	}

	$arr = $db->db_fetch_array($db->db_query('SHOW TABLES LIKE \'' . BAB_TSKMGR_SPECIFIC_FIELDS_RADIO_CLASS_TBL . '\''));
	if($arr[0] != BAB_TSKMGR_SPECIFIC_FIELDS_RADIO_CLASS_TBL)
	{
		$res = $db->db_query("
			CREATE TABLE `" . BAB_TSKMGR_SPECIFIC_FIELDS_RADIO_CLASS_TBL . "` (
				`id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
				`idFldBase` INTEGER UNSIGNED NOT NULL default '0',
				`value` VARCHAR(255) NOT NULL default '',
				`isDefaultValue` TINYINT UNSIGNED NOT NULL default '0',
				`position` TINYINT UNSIGNED NOT NULL default '0',
				PRIMARY KEY(`id`)) TYPE=MyISAM
		");
		
		if(false == $res)
		{
			return $res;
		}
	}
	
	$arr = $db->db_fetch_array($db->db_query('SHOW TABLES LIKE \'' . BAB_TSKMGR_SPECIFIC_FIELDS_INSTANCE_LIST_TBL . '\''));
	if($arr[0] != BAB_TSKMGR_SPECIFIC_FIELDS_INSTANCE_LIST_TBL)
	{
		$res = $db->db_query("
			CREATE TABLE `" . BAB_TSKMGR_SPECIFIC_FIELDS_INSTANCE_LIST_TBL . "` (
				`id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
				`idSpFldClass` INTEGER UNSIGNED NOT NULL default '0',
				`idTask` INTEGER UNSIGNED NOT NULL default '0',
				`value` TEXT NOT NULL default '',
				`position` INTEGER UNSIGNED NOT NULL default '0',
				PRIMARY KEY(`id`, `idSpFldClass`),
				INDEX `idSpFldClass`(`idSpFldClass`)) TYPE=MyISAM
		");
		
		if(false == $res)
		{
			return $res;
		}
	}
	
	$arr = $db->db_fetch_array($db->db_query('SHOW TABLES LIKE \'' . BAB_TSKMGR_CATEGORIES_TBL . '\''));
	if($arr[0] != BAB_TSKMGR_CATEGORIES_TBL)
	{
		$res = $db->db_query("
			CREATE TABLE `" . BAB_TSKMGR_CATEGORIES_TBL . "` (
				`id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
				`idProjectSpace` INTEGER UNSIGNED NOT NULL default '0',
				`idProject` INTEGER UNSIGNED NOT NULL default '0',
				`name` VARCHAR(255) NOT NULL default '',
				`description` TEXT NOT NULL default '',
				`color` VARCHAR(20) NOT NULL default '',
				`refCount` INTEGER UNSIGNED NOT NULL default '0',
				`created` DATETIME NOT NULL default '0000-00-00 00:00:00',
				`idUserCreated` INTEGER UNSIGNED NOT NULL default '0',
				`modified` DATETIME NOT NULL default '0000-00-00 00:00:00',
				`idUserModified` INTEGER UNSIGNED NOT NULL default '0',
				PRIMARY KEY(`id`),
				INDEX `idProjectSpace`(`idProjectSpace`),
				INDEX `idProject`(`idProject`),
				INDEX `name`(`name`),
				INDEX `refCount`(`refCount`)) TYPE=MyISAM
		");
		
		if(false == $res)
		{
			return $res;
		}
	}
	
	
	
	
	
	$arr = $db->db_fetch_array($db->db_query('SHOW TABLES LIKE \'' . BAB_TSKMGR_PROJECTS_MANAGERS_GROUPS_TBL . '\''));
	if($arr[0] != BAB_TSKMGR_PROJECTS_MANAGERS_GROUPS_TBL)
	{
		$res = $db->db_query("
			CREATE TABLE `" . BAB_TSKMGR_PROJECTS_MANAGERS_GROUPS_TBL . "` (
				`id` int( 11 ) unsigned NOT NULL AUTO_INCREMENT ,
				`id_object` int( 11 ) unsigned NOT NULL default '0',
				`id_group` int( 11 ) unsigned NOT NULL default '0',
				PRIMARY KEY ( `id` ) ,
				KEY `id_object` ( `id_object` ) ,
				KEY `id_group` ( `id_group` )) TYPE=MyISAM
		");
		
		if(false == $res)
		{
			return $res;
		}
	}
	
	$arr = $db->db_fetch_array($db->db_query('SHOW TABLES LIKE \'' . BAB_TSKMGR_PROJECTS_SUPERVISORS_GROUPS_TBL . '\''));
	if($arr[0] != BAB_TSKMGR_PROJECTS_SUPERVISORS_GROUPS_TBL)
	{
		$res = $db->db_query("
			CREATE TABLE `" . BAB_TSKMGR_PROJECTS_SUPERVISORS_GROUPS_TBL . "` (
				`id` int( 11 ) unsigned NOT NULL AUTO_INCREMENT ,
				`id_object` int( 11 ) unsigned NOT NULL default '0',
				`id_group` int( 11 ) unsigned NOT NULL default '0',
				PRIMARY KEY ( `id` ) ,
				KEY `id_object` ( `id_object` ) ,
				KEY `id_group` ( `id_group` )) TYPE=MyISAM
		");
		
		if(false == $res)
		{
			return $res;
		}
	}
	
	$arr = $db->db_fetch_array($db->db_query('SHOW TABLES LIKE \'' . BAB_TSKMGR_PROJECTS_VISUALIZERS_GROUPS_TBL . '\''));
	if($arr[0] != BAB_TSKMGR_PROJECTS_VISUALIZERS_GROUPS_TBL)
	{
		$res = $db->db_query("
			CREATE TABLE `" . BAB_TSKMGR_PROJECTS_VISUALIZERS_GROUPS_TBL . "` (
				`id` int( 11 ) unsigned NOT NULL AUTO_INCREMENT ,
				`id_object` int( 11 ) unsigned NOT NULL default '0',
				`id_group` int( 11 ) unsigned NOT NULL default '0',
				PRIMARY KEY ( `id` ) ,
				KEY `id_object` ( `id_object` ) ,
				KEY `id_group` ( `id_group` )) TYPE=MyISAM
		");
		
		if(false == $res)
		{
			return $res;
		}
	}
	
	$arr = $db->db_fetch_array($db->db_query('SHOW TABLES LIKE \'' . BAB_TSKMGR_TASK_RESPONSIBLE_GROUPS_TBL . '\''));
	if($arr[0] != BAB_TSKMGR_TASK_RESPONSIBLE_GROUPS_TBL)
	{
		$res = $db->db_query("
			CREATE TABLE `" . BAB_TSKMGR_TASK_RESPONSIBLE_GROUPS_TBL . "` (
				`id` int( 11 ) unsigned NOT NULL AUTO_INCREMENT ,
				`id_object` int( 11 ) unsigned NOT NULL default '0',
				`id_group` int( 11 ) unsigned NOT NULL default '0',
				PRIMARY KEY ( `id` ) ,
				KEY `id_object` ( `id_object` ) ,
				KEY `id_group` ( `id_group` )) TYPE=MyISAM
		");
		
		if(false == $res)
		{
			return $res;
		}
	}
	
	$arr = $db->db_fetch_array($db->db_query('SHOW TABLES LIKE \'' . BAB_TSKMGR_PROJECTS_CONFIGURATION_TBL . '\''));
	if($arr[0] != BAB_TSKMGR_PROJECTS_CONFIGURATION_TBL)
	{
		$res = $db->db_query("
			CREATE TABLE `" . BAB_TSKMGR_PROJECTS_CONFIGURATION_TBL . "` (
				`id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
				`idProject` INTEGER UNSIGNED NOT NULL default '0',
				`tskUpdateByMgr` TINYINT UNSIGNED NOT NULL default '1',
				`endTaskReminder` MEDIUMINT UNSIGNED NOT NULL default '5',
				`tasksNumerotation` TINYINT UNSIGNED NOT NULL default '1',
				`emailNotice` TINYINT UNSIGNED NOT NULL default '1',
				`faqUrl` MEDIUMTEXT NOT NULL default '',
				PRIMARY KEY(`id`, `idProject`),
				INDEX `idProject`(`idProject`)) TYPE=MyISAM
		");
		
		if(false == $res)
		{
			return $res;
		}
	}
	
	$arr = $db->db_fetch_array($db->db_query('SHOW TABLES LIKE \'' . BAB_TSKMGR_PROJECTS_TBL . '\''));
	if($arr[0] != BAB_TSKMGR_PROJECTS_TBL)
	{
		$res = $db->db_query("
			CREATE TABLE `" . BAB_TSKMGR_PROJECTS_TBL . "` (
				`id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
				`idProjectSpace` INTEGER UNSIGNED NOT NULL default '0',
				`name` VARCHAR(255) NOT NULL default '',
				`description` TEXT NOT NULL default '',
				`created` DATETIME NOT NULL default '0000-00-00 00:00:00',
				`idUserCreated` INTEGER UNSIGNED NOT NULL default '0',
				`modified` DATETIME NOT NULL default '0000-00-00 00:00:00',
				`idUserModified` INTEGER UNSIGNED NOT NULL default '0',
				`isLocked` TINYINT UNSIGNED NOT NULL default '0',
				`state` TINYINT UNSIGNED NOT NULL default '0',
				PRIMARY KEY(`id`),
				INDEX `idProjectSpace`(`idProjectSpace`),
				INDEX `isLocked`(`isLocked`),
				INDEX `state`(`state`)
				) TYPE=MyISAM
		");
		
		if(false == $res)
		{
			return $res;
		}
	}
	
	$arr = $db->db_fetch_array($db->db_query('SHOW TABLES LIKE \'' . BAB_TSKMGR_PROJECTS_COMMENTS_TBL . '\''));
	if($arr[0] != BAB_TSKMGR_PROJECTS_COMMENTS_TBL)
	{
		$res = $db->db_query("
			CREATE TABLE `" . BAB_TSKMGR_PROJECTS_COMMENTS_TBL . "` (
				`id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
				`idProject` INTEGER UNSIGNED NOT NULL default '0',
				`commentary` TEXT NOT NULL default '',
				`created` DATETIME NOT NULL default '0000-00-00 00:00:00',
				`idUserCreated` INTEGER UNSIGNED NOT NULL default '0',
				`modified` DATETIME NOT NULL default '0000-00-00 00:00:00',
				`idUserModified` INTEGER UNSIGNED NOT NULL default '0',
				PRIMARY KEY(`id`),
				INDEX `idProject`(`idProject`)
				) TYPE=MyISAM
		");
		
		if(false == $res)
		{
			return $res;
		}
	}
	
	$arr = $db->db_fetch_array($db->db_query('SHOW TABLES LIKE \'' . BAB_TSKMGR_PROJECTS_REVISIONS_TBL . '\''));
	if($arr[0] != BAB_TSKMGR_PROJECTS_REVISIONS_TBL)
	{
		$res = $db->db_query("
			CREATE TABLE `" . BAB_TSKMGR_PROJECTS_REVISIONS_TBL . "` (
				`id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
				`idProject` INTEGER UNSIGNED NOT NULL default '0',
				`idProjectComment` INTEGER UNSIGNED NOT NULL default '0',
				`majorVersion` INTEGER UNSIGNED NOT NULL default '0',
				`minorVersion` INTEGER UNSIGNED NOT NULL default '0',
				PRIMARY KEY(`id`),
				INDEX `idProject`(`idProject`),
				INDEX `idProjectComment`(`idProjectComment`),
				INDEX `majorVersion`(`majorVersion`),
				INDEX `minorVersion`(`minorVersion`)
				) TYPE=MyISAM
		");
		
		if(false == $res)
		{
			return $res;
		}
	}
	
	$arr = $db->db_fetch_array($db->db_query('SHOW TABLES LIKE \'' . BAB_TSKMGR_TASKS_TBL . '\''));
	if($arr[0] != BAB_TSKMGR_TASKS_TBL)
	{
		$res = $db->db_query("
			CREATE TABLE`" . BAB_TSKMGR_TASKS_TBL . "` (
				`id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
				`idProject` INTEGER UNSIGNED NOT NULL default '0',
				`taskNumber` VARCHAR(9) NOT NULL DEFAULT '0',
				`description` TEXT NOT NULL default '',
				`idCategory` INTEGER UNSIGNED NOT NULL default '0',
				`created` DATETIME NOT NULL default '0000-00-00 00:00:00',
				`modified` DATETIME NOT NULL default '0000-00-00 00:00:00',
				`idUserCreated` INTEGER UNSIGNED NOT NULL default '0',
				`idUserModified` INTEGER UNSIGNED NOT NULL default '0',
				`class` TINYINT UNSIGNED NOT NULL default '0',
				`participationStatus` TINYINT UNSIGNED NOT NULL default '0',
				`isLinked` TINYINT UNSIGNED NOT NULL default '0',
				`idCalEvent` INTEGER UNSIGNED NOT NULL default '0',
				`hashCalEvent` VARCHAR(34) NOT NULL default '0',
				`duration` TINYINT UNSIGNED NOT NULL default '0',
				`majorVersion` INTEGER UNSIGNED NOT NULL default '0',
				`minorVersion` INTEGER UNSIGNED NOT NULL default '0',
				`color` VARCHAR(8) NOT NULL default '',
				`position` INTEGER UNSIGNED NOT NULL default '0',
				`completion` INTEGER UNSIGNED NOT NULL default '0',
				`plannedStartDate` DATETIME NOT NULL default '0000-00-00 00:00:00',
				`plannedEndDate` DATETIME NOT NULL default '0000-00-00 00:00:00',
				`startDate` DATETIME NOT NULL default '0000-00-00 00:00:00',
				`endDate` DATETIME NOT NULL default '0000-00-00 00:00:00',
				`isNotified` TINYINT UNSIGNED NOT NULL default '0',
				PRIMARY KEY(`id`, `idProject`),
				INDEX `idProject`(`idProject`),
				INDEX `majorVersion`(`majorVersion`),
				INDEX `minorVersion`(`minorVersion`)
				) TYPE=MyISAM
		");

		if(false == $res)
		{
			return $res;
		}
	}
	
	$arr = $db->db_fetch_array($db->db_query('SHOW TABLES LIKE \'' . BAB_TSKMGR_LINKED_TASKS_TBL . '\''));
	if($arr[0] != BAB_TSKMGR_LINKED_TASKS_TBL)
	{
		$res = $db->db_query("
			CREATE TABLE`" . BAB_TSKMGR_LINKED_TASKS_TBL . "` (
				`id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
				`idTask` INTEGER UNSIGNED NOT NULL default '0',
				`idPredecessorTask` INTEGER UNSIGNED NOT NULL default '0',
				`linkType` TINYINT UNSIGNED NOT NULL default '0',
				PRIMARY KEY(`id`),
				INDEX `idTask`(`idTask`),
				INDEX `idPredecessorTask`(`idPredecessorTask`)
				) TYPE=MyISAM
		");
		
		if(false == $res)
		{
			return $res;
		}
	}
	
	$arr = $db->db_fetch_array($db->db_query('SHOW TABLES LIKE \'' . BAB_TSKMGR_TASKS_RESPONSIBLES_TBL . '\''));
	if($arr[0] != BAB_TSKMGR_TASKS_RESPONSIBLES_TBL)
	{
		$res = $db->db_query("
			CREATE TABLE`" . BAB_TSKMGR_TASKS_RESPONSIBLES_TBL . "` (
				`id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
				`idTask` INTEGER UNSIGNED NOT NULL default '0',
				`idResponsible` INTEGER UNSIGNED NOT NULL default '0',
				PRIMARY KEY(`id`),
				INDEX `idTask`(`idTask`),
				INDEX `idResponsible`(`idResponsible`)
				) TYPE=MyISAM
		");
		
		if(false == $res)
		{
			return $res;
		}
	}
	
	$arr = $db->db_fetch_array($db->db_query('SHOW TABLES LIKE \'' . BAB_TSKMGR_TASKS_COMMENTS_TBL . '\''));
	if($arr[0] != BAB_TSKMGR_TASKS_COMMENTS_TBL)
	{
		$res = $db->db_query("
			CREATE TABLE `" . BAB_TSKMGR_TASKS_COMMENTS_TBL . "` (
				`id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
				`idTask` INTEGER UNSIGNED NOT NULL default '0',
				`idProject` INTEGER UNSIGNED NOT NULL default '0',
				`commentary` TEXT NOT NULL default '',
				`created` DATETIME NOT NULL default '0000-00-00 00:00:00',
				`idUserCreated` INTEGER UNSIGNED NOT NULL default '0',
				`modified` DATETIME NOT NULL default '0000-00-00 00:00:00',
				`idUserModified` INTEGER UNSIGNED NOT NULL default '0',
				PRIMARY KEY(`id`),
				INDEX `idProject`(`idProject`),
				INDEX `idTask`(`idTask`)
				) TYPE=MyISAM
		");
		
		if(false == $res)
		{
			return $res;
		}
	}
	
	$arr = $db->db_fetch_array($db->db_query('DESCRIBE `' . BAB_DG_GROUPS_TBL . '` taskmanager'));
	if ( $arr[0] != 'taskmanager' )
	{
		$res = $db->db_query('ALTER TABLE `' . BAB_DG_GROUPS_TBL .'` ADD `taskmanager`  enum(\'N\',\'Y\') NOT NULL default \'N\' AFTER `orgchart` ');
		
		if(false == $res)
		{
			return $res;
		}
	}
	
	$arr = $db->db_fetch_array($db->db_query('SHOW TABLES LIKE \'' . BAB_WEEK_DAYS_TBL . '\''));
	if($arr[0] != BAB_WEEK_DAYS_TBL)
	{
		$res = $db->db_query("
			CREATE TABLE `" . BAB_WEEK_DAYS_TBL . "` (
				`id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
				`weekDay` TINYINT UNSIGNED NOT NULL default '0',
				`position` TINYINT UNSIGNED NOT NULL default '0',
				PRIMARY KEY(`id`),
				INDEX `weekDay`(`weekDay`),
				INDEX `position`(`position`)
				) TYPE=MyISAM
		");
		
		if(false == $res)
		{
			return $res;
		}
		
		$db->db_query("insert into " . BAB_WEEK_DAYS_TBL . " (`id`, `weekDay`, `position`) VALUES ('1', '0', '6')");
		$db->db_query("insert into " . BAB_WEEK_DAYS_TBL . " (`id`, `weekDay`, `position`) VALUES ('2', '1', '0')");
		$db->db_query("insert into " . BAB_WEEK_DAYS_TBL . " (`id`, `weekDay`, `position`) VALUES ('3', '2', '1')");
		$db->db_query("insert into " . BAB_WEEK_DAYS_TBL . " (`id`, `weekDay`, `position`) VALUES ('4', '3', '2')");
		$db->db_query("insert into " . BAB_WEEK_DAYS_TBL . " (`id`, `weekDay`, `position`) VALUES ('5', '4', '3')");
		$db->db_query("insert into " . BAB_WEEK_DAYS_TBL . " (`id`, `weekDay`, `position`) VALUES ('6', '5', '4')");
		$db->db_query("insert into " . BAB_WEEK_DAYS_TBL . " (`id`, `weekDay`, `position`) VALUES ('7', '6', '5')");
	}
	
	$arr = $db->db_fetch_array($db->db_query('SHOW TABLES LIKE \'' . BAB_WORKING_HOURS_TBL . '\''));
	if($arr[0] != BAB_WORKING_HOURS_TBL)
	{
		$res = $db->db_query("
			CREATE TABLE `" . BAB_WORKING_HOURS_TBL . "` (
				`id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
				`weekDay` INTEGER UNSIGNED NOT NULL default '0',
				`idUser` INTEGER UNSIGNED NOT NULL default '0',
				`startHour` TIME NOT NULL default '00:00:00',
				`endHour` TIME NOT NULL default '00:00:00',
				PRIMARY KEY(`id`),
				INDEX `startHour`(`startHour`),
				INDEX `endHour`(`endHour`)
				) TYPE=MyISAM
		");
		
		if(false == $res)
		{
			return $res;
		}
		
		//require_once($GLOBALS['babInstallPath'] . 'utilit/workinghoursincl.php');
		//bab_createDefaultWorkingHours(0);

		// sites
		$res = $db->db_query("select workdays from ".BAB_SITES_TBL." WHERE name=".$db->quote($GLOBALS['babSiteName']));
		while( $arr = $db->db_fetch_array($res))
		{
			$awd = explode(',',$arr['workdays']);
			foreach($awd as $d) 
				{
				$db->db_query("INSERT INTO ".BAB_WORKING_HOURS_TBL."( weekDay, idUser,  startHour, endHour) VALUES (".$db->quote($d).",'0', '00:00:00', '24:00:00')");
				}
			
		}

		// users 
		$res = $db->db_query("select id_user, workdays, start_time, end_time from ".BAB_CAL_USER_OPTIONS_TBL."");
		while( $arr = $db->db_fetch_array($res))
		{
			$awd = explode(',',$arr['workdays']);
			foreach($awd as $d) 
				{
				$db->db_query("INSERT INTO ".BAB_WORKING_HOURS_TBL."( weekDay, idUser,  startHour, endHour) VALUES (".$db->quote($d).",'".$arr['id_user']."', '".$arr['start_time']."', '".$arr['end_time']."')");
				}
			
		}

	}
	
	$arr = $db->db_fetch_array($db->db_query('SHOW TABLES LIKE \'' . BAB_TSKMGR_NOTICE_TBL . '\''));
	if($arr[0] != BAB_TSKMGR_NOTICE_TBL)
	{
		$res = $db->db_query("
			CREATE TABLE `" . BAB_TSKMGR_NOTICE_TBL . "` (
				`id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
				`idProjectSpace` INTEGER UNSIGNED NOT NULL default '0',
				`idProject` INTEGER UNSIGNED NOT NULL default '0',
				`profil` INTEGER UNSIGNED NOT NULL default '0',
				`idEvent` INTEGER UNSIGNED NOT NULL default '0',
				PRIMARY KEY(`id`),
				INDEX `idProjectSpace`(`idProjectSpace`),
				INDEX `idProject`(`idProject`),
				INDEX `profil`(`profil`),
				INDEX `idEvent`(`idEvent`)
				) TYPE=MyISAM
		");
		
		if(false == $res)
		{
			return $res;
		}
	}

}


function upgrade584to585()
{	
	$ret = "";
	$db = & $GLOBALS['babDB'];

	if (!bab_isTable(BAB_STATS_BASKETS_TBL)) {

		$db->db_query("
		
				CREATE TABLE ".BAB_STATS_BASKETS_TBL." (
				  id int(11) unsigned NOT NULL auto_increment,
				  basket_name varchar(255) NOT NULL,
				  basket_desc varchar(255) NOT NULL,
				  basket_author int(11) unsigned NOT NULL,
				  basket_datetime datetime NOT NULL,
				  id_dgowner int(11) unsigned NOT NULL,
				  PRIMARY KEY  (id)
				)
			
		");

	}

	if (!bab_isTable(BAB_STATSBASKETS_GROUPS_TBL)) {

		$db->db_query("
		
				CREATE TABLE ".BAB_STATSBASKETS_GROUPS_TBL." (
				  id int(11) unsigned NOT NULL auto_increment,
				  id_object int(11) unsigned NOT NULL default '0',
				  id_group int(11) unsigned NOT NULL default '0',
				  PRIMARY KEY  (id),
				  KEY id_object (id_object),
				  KEY id_group (id_group)
				)
			
		");

	}

	if (!bab_isTable(BAB_STATS_BASKET_CONTENT_TBL)) {

		$db->db_query("
		
				CREATE TABLE ".BAB_STATS_BASKET_CONTENT_TBL." (
				  id int(11) unsigned NOT NULL auto_increment,
				  basket_id int(11) unsigned NOT NULL,
				  bc_description varchar(255) NOT NULL,
				  bc_author int(11) unsigned NOT NULL,
				  bc_datetime datetime NOT NULL,
				  bc_type tinyint(2) unsigned NOT NULL,
				  bc_id int(11) unsigned NOT NULL,
				  PRIMARY KEY  (id),
				  KEY basket_id (basket_id,bc_type)
				)
			
		");

	}

	if (!bab_isTableField(BAB_LDAP_DIRECTORIES_TBL, 'decoding_type')) {

		$db->db_query("ALTER TABLE `".BAB_LDAP_DIRECTORIES_TBL."` ADD decoding_type TINYINT( 1 ) UNSIGNED DEFAULT '0' NOT NULL AFTER `description` ");
		$db->db_query("update ".BAB_LDAP_DIRECTORIES_TBL." set decoding_type='1' where server_type='0'");
	}

	if (!bab_isTableField(BAB_SITES_TBL, 'ldap_decoding_type')) {

		$db->db_query("ALTER TABLE `".BAB_SITES_TBL."` ADD ldap_decoding_type TINYINT( 1 ) UNSIGNED DEFAULT '0' NOT NULL");
		$db->db_query("update ".BAB_SITES_TBL." set ldap_decoding_type='1' where authentification='1'");
	}

}







function upgrade585to586()
{
	$ret = "";
	$db = & $GLOBALS['babDB'];

	if (!bab_isTable(BAB_MAIL_SPOOLER_TBL)) {

		$res = $db->db_query("
		
				CREATE TABLE ".BAB_MAIL_SPOOLER_TBL." (
				  id int(11) unsigned NOT NULL auto_increment,
				  mail_hash varchar(255) NOT NULL,
				  mail_subject varchar(255) NOT NULL,
				  body text NOT NULL,
				  altbody text NOT NULL,
				  format varchar(32) NOT NULL,
				  recipients text NOT NULL,
				  mail_data text NOT NULL,
				  sent_status tinyint(1) unsigned NOT NULL,
				  error_msg varchar(255) NOT NULL,
				  mail_date datetime NOT NULL,
				  PRIMARY KEY  (id),
				  KEY mail_date (mail_date)
				)
			
		");


		if( !$res){
			$ret = "Creation of <b>".BAB_MAIL_SPOOLER_TBL."</b> table failed !<br>";
			return $ret;
		}

	}

	if(!bab_isTable(BAB_TSKMGR_PERSONNAL_TASKS_CONFIGURATION_TBL))
	{
		$res = $db->db_query("
			CREATE TABLE `" . BAB_TSKMGR_PERSONNAL_TASKS_CONFIGURATION_TBL . "` (
				`id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
				`idUser` INTEGER UNSIGNED NOT NULL default '0',
				`endTaskReminder` MEDIUMINT UNSIGNED NOT NULL default '5',
				`tasksNumerotation` TINYINT UNSIGNED NOT NULL default '1',
				`emailNotice` TINYINT UNSIGNED NOT NULL default '1',
				PRIMARY KEY(`id`),
				INDEX `idUser`(`idUser`)) TYPE=MyISAM
		");
		
		if( !$res){
			$ret = "Creation of <b>".BAB_TSKMGR_PERSONNAL_TASKS_CONFIGURATION_TBL."</b> table failed !<br>";
			return $ret;
		}
	}

	if(!bab_isTable(BAB_TSKMGR_TASKS_INFO_TBL))
	{
		$res = $db->db_query("
			CREATE TABLE `" . BAB_TSKMGR_TASKS_INFO_TBL . "` (
				`id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
				`idTask` INTEGER UNSIGNED NOT NULL default '0',
				`idOwner` INTEGER UNSIGNED NOT NULL default '0',
				`isPersonnal` TINYINT UNSIGNED NOT NULL default '0',
				PRIMARY KEY(`id`),
				INDEX `idTask`(`idTask`),
				INDEX `idOwner`(`idOwner`)) TYPE=MyISAM
		");
		
		if( !$res){
			$ret = "Creation of <b>".BAB_TSKMGR_TASKS_INFO_TBL."</b> table failed !<br>";
			return $ret;
		}
	}
	
	$db->db_query("ALTER TABLE `" . BAB_TSKMGR_TASKS_TBL . "` CHANGE `taskNumber` `taskNumber` VARCHAR( 9 ) NOT NULL DEFAULT '0'");
	

	if (bab_isTableField(BAB_TSKMGR_TASKS_TBL, 'idOwner')) {
		$db->db_query("ALTER TABLE `" . BAB_TSKMGR_TASKS_TBL . "` DROP `idOwner`");
	}


	$res = $db->db_query("select * from ".BAB_STATS_IMODULES_TBL." where id='24'");
	if( !$res || $db->db_num_rows($res) == 0 )
	{
	$db->db_query("INSERT INTO ".BAB_STATS_IMODULES_TBL." VALUES (24, 'Task manager')");
	}

	return $ret;
}

function upgrade586to587()
{
	$ret = "";
	$db = & $GLOBALS['babDB'];

	if (!bab_isTableField(BAB_TSKMGR_TASKS_TBL, 'shortDescription')) {
		$res = $db->db_query("ALTER TABLE `" . BAB_TSKMGR_TASKS_TBL . "` ADD `shortDescription` VARCHAR( 255 ) NOT NULL AFTER `description`");

		if( !$res){
			$ret = "Creation of <b>".BAB_TSKMGR_TASKS_TBL.".shortDescription</b> field failed !<br>";
			return $ret;
		}
	}

	return $ret;
}

function upgrade587to588()
{
	$ret = "";
	$db = & $GLOBALS['babDB'];

	if (!bab_isTable(BAB_CAL_RES_UPD_GROUPS_TBL)) {

		$db->db_query("
		
				CREATE TABLE ".BAB_CAL_RES_UPD_GROUPS_TBL." (
				  id int(11) unsigned NOT NULL auto_increment,
				  id_object int(11) unsigned NOT NULL default '0',
				  id_group int(11) unsigned NOT NULL default '0',
				  PRIMARY KEY  (id),
				  KEY id_object (id_object),
				  KEY id_group (id_group)
				)
			
		");

	}

	if (!bab_isTableField(BAB_DG_GROUPS_TBL, 'color')) {

		$db->db_query("ALTER TABLE ".BAB_DG_GROUPS_TBL." ADD `color` VARCHAR( 8 ) DEFAULT '' NOT NULL AFTER `description`");
	}

	return $ret;
}


function upgrade588to589()
{
	$ret = "";
	$db = & $GLOBALS['babDB'];

	if(!bab_isTable(BAB_TSKMGR_TASK_LIST_FILTER_TBL))
	{
		$res = $db->db_query("
			CREATE TABLE `" . BAB_TSKMGR_TASK_LIST_FILTER_TBL . "` (
				`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
				`idUser` INT UNSIGNED NOT NULL,
				`idProject` INT NOT NULL,
				`iTaskClass` INT NOT NULL,
				PRIMARY KEY(`id`),
				INDEX `idUser`(`idUser`)) TYPE=MyISAM
		");
	}

	if (!bab_isTableField(BAB_DG_GROUPS_TBL, 'battach')) {

		$db->db_query("ALTER TABLE ".BAB_DG_GROUPS_TBL." ADD `battach` enum('N','Y') NOT NULL default 'N' AFTER `color`");
	}
	
	return $ret;
}

function upgrade589to600()
{
	$ret = "";
	$db = & $GLOBALS['babDB'];

	if(!bab_isTableField(BAB_TSKMGR_CATEGORIES_TBL, 'bgColor')) 
	{
		$db->db_query("ALTER TABLE ".BAB_TSKMGR_CATEGORIES_TBL." ADD `bgColor` VARCHAR( 20 ) NOT NULL , ADD `idUser` INT( 11 ) UNSIGNED NOT NULL");
	}
	
	if(!bab_isTableField(BAB_TSKMGR_SPECIFIC_FIELDS_BASE_CLASS_TBL, 'idUser')) 
	{
		$db->db_query("ALTER TABLE ".BAB_TSKMGR_SPECIFIC_FIELDS_BASE_CLASS_TBL." ADD `idUser` INT( 11 ) UNSIGNED NOT NULL");
	}

	$res = $db->db_query("select * from ".BAB_STATS_IMODULES_TBL." where id='25'");
	if( !$res || $db->db_num_rows($res) == 0 )
	{
	$db->db_query("INSERT INTO ".BAB_STATS_IMODULES_TBL." VALUES (25, 'Web services')");
	}


	if (!bab_isTable(BAB_SITES_WS_GROUPS_TBL)) {

		$db->db_query("
		
				CREATE TABLE ".BAB_SITES_WS_GROUPS_TBL." (
				  id int(11) unsigned NOT NULL auto_increment,
				  id_object int(11) unsigned NOT NULL default '0',
				  id_group int(11) unsigned NOT NULL default '0',
				  PRIMARY KEY  (id),
				  KEY id_object (id_object),
				  KEY id_group (id_group)
				)
			
		");

	}

	if (!bab_isTable(BAB_SITES_WSOVML_GROUPS_TBL)) {

		$db->db_query("
		
				CREATE TABLE ".BAB_SITES_WSOVML_GROUPS_TBL." (
				  id int(11) unsigned NOT NULL auto_increment,
				  id_object int(11) unsigned NOT NULL default '0',
				  id_group int(11) unsigned NOT NULL default '0',
				  PRIMARY KEY  (id),
				  KEY id_object (id_object),
				  KEY id_group (id_group)
				)
			
		");

	}

	if (!bab_isTable(BAB_SITES_WSFILES_GROUPS_TBL)) {

		$db->db_query("
		
				CREATE TABLE ".BAB_SITES_WSFILES_GROUPS_TBL." (
				  id int(11) unsigned NOT NULL auto_increment,
				  id_object int(11) unsigned NOT NULL default '0',
				  id_group int(11) unsigned NOT NULL default '0',
				  PRIMARY KEY  (id),
				  KEY id_object (id_object),
				  KEY id_group (id_group)
				)
			
		");

	}
	return $ret;
}

function upgrade600to601()
{
	$ret = "";
	$db = & $GLOBALS['babDB'];

	if (!bab_isTableField(BAB_FORUMS_TBL, 'bdisplayemailaddress')) {

		$db->db_query("ALTER TABLE ".BAB_FORUMS_TBL." ADD `bdisplayemailaddress` enum('N','Y') NOT NULL default 'N'");
	}

	if (!bab_isTableField(BAB_FORUMS_TBL, 'bdisplayauhtordetails')) {

		$db->db_query("ALTER TABLE ".BAB_FORUMS_TBL." ADD `bdisplayauhtordetails` enum('N','Y') NOT NULL default 'N'");
	}

	if (!bab_isTableField(BAB_FORUMS_TBL, 'bflatview')) {

		$db->db_query("ALTER TABLE ".BAB_FORUMS_TBL." ADD `bflatview` enum('N','Y') NOT NULL default 'N'");
	}

	if (!bab_isTableField(BAB_POSTS_TBL, 'id_author')) {

		$db->db_query("ALTER TABLE ".BAB_POSTS_TBL." ADD `id_author` INT( 11 )  UNSIGNED DEFAULT '0' NOT NULL AFTER `author`");
	}

	if (!bab_isTableField(BAB_FORUMS_TBL, 'bupdatemoderator')) {

		$db->db_query("ALTER TABLE ".BAB_FORUMS_TBL." ADD `bupdatemoderator` enum('Y','N') NOT NULL default 'Y'");
	}

	if (!bab_isTableField(BAB_FORUMS_TBL, 'bupdateauthor')) {

		$db->db_query("ALTER TABLE ".BAB_FORUMS_TBL." ADD `bupdateauthor` enum('Y','N') NOT NULL default 'N'");
	}

	if (!bab_isTableField(BAB_USERS_LOG_TBL, 'tg')) {

		$db->db_query("ALTER TABLE ".BAB_USERS_LOG_TBL." ADD `tg` VARCHAR( 255 ) NOT NULL");
	}

	if (!bab_isTableField(BAB_POSTS_TBL, 'date_confirm')) {

		$db->db_query("ALTER TABLE ".BAB_POSTS_TBL." ADD `date_confirm` DATETIME NOT NULL");
	}

	$db->db_query("update ".BAB_POSTS_TBL." set date_confirm=date where 1");

	$res = $db->db_query("SELECT pt.id, tt.starter FROM ".BAB_POSTS_TBL." pt LEFT JOIN ".BAB_THREADS_TBL." tt ON tt.id = pt.id_thread WHERE tt.post = pt.id");

	while( $arr = $db->db_fetch_array($res))
	{
		$db->db_query("update ".BAB_POSTS_TBL." set id_author='".$arr['starter']."' where id='".$arr['id']."'");
	}

	if (!bab_isTable(BAB_STATS_CONNECTIONS_TBL))
	{
		$res = $db->db_query("
			CREATE TABLE " . BAB_STATS_CONNECTIONS_TBL . " (
				id_user INT(11) UNSIGNED NOT NULL,
				id_session VARCHAR(255) NOT NULL,
				login_time DATETIME NOT NULL,
				last_action_time DATETIME NOT NULL,
				KEY id_user (id_user),
				KEY id_session (id_session),
				KEY login_time (login_time)	
			)
		");
	}
	
	return $ret;
}






function upgrade601to602()
{
	$ret = "";
	$db = & $GLOBALS['babDB'];


	function change_time($date, $time) {
		$temp = explode(' ',$date);
		return $temp[0].' '.$time;
	}


	if (bab_isTableField(BAB_VAC_ENTRIES_TBL, 'day_begin')) {

		// transformer bab_vac_entries
		// 1 = journée entière
		// 2 = matin
		// 3 = apres-midi

		$db->db_query("ALTER TABLE `".BAB_VAC_ENTRIES_TBL."` CHANGE `date_begin` `date_begin` DATETIME DEFAULT '0000-00-00 00:00:00' NOT NULL");
		$db->db_query("ALTER TABLE `".BAB_VAC_ENTRIES_TBL."` CHANGE `date_end` `date_end` DATETIME DEFAULT '0000-00-00 00:00:00' NOT NULL");

		$res = $db->db_query("SELECT id, date_begin, date_end, day_begin, day_end  FROM `".BAB_VAC_ENTRIES_TBL."`");

		while ($arr = $db->db_fetch_assoc($res)) {
			$time_begin = '00:00:00';
			$time_end	= '23:59:59';

			if (3 == $arr['day_begin']) {
				$time_begin = '12:00:00';
			}
			
			if (2 == $arr['day_end']) {
				$time_end = '11:59:59';
			}

			$arr['date_begin']	= change_time($arr['date_begin'], $time_begin);
			$arr['date_end']	= change_time($arr['date_end']	, $time_end);

			$db->db_query("
				UPDATE `".BAB_VAC_ENTRIES_TBL."` SET 
					date_begin =".$db->quote($arr['date_begin']).",  
					date_end =".$db->quote($arr['date_end'])." 
				WHERE 
					id=".$db->quote($arr['id'])
			);
		}

		$db->db_query("ALTER TABLE `".BAB_VAC_ENTRIES_TBL."` DROP `day_begin`");
		$db->db_query("ALTER TABLE `".BAB_VAC_ENTRIES_TBL."` DROP `day_end`");
	}


	if (bab_isTable('bab_tskmgr_week_days') && !bab_isTable(BAB_WEEK_DAYS_TBL)) {

		$db->db_query("ALTER TABLE `bab_tskmgr_week_days` RENAME `".BAB_WEEK_DAYS_TBL."` ");

	} elseif(!bab_isTable(BAB_WEEK_DAYS_TBL)) {

		$res = $db->db_query("
			CREATE TABLE `" . BAB_WEEK_DAYS_TBL . "` (
				`id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
				`weekDay` TINYINT UNSIGNED NOT NULL default '0',
				`position` TINYINT UNSIGNED NOT NULL default '0',
				PRIMARY KEY(`id`),
				INDEX `weekDay`(`weekDay`),
				INDEX `position`(`position`)
				) TYPE=MyISAM
		");
		
		if(false == $res)
		{
			return "Creation of <b>".BAB_WEEK_DAYS_TBL."</b> failed !<br>";
		}
		
		$db->db_query("insert into " . BAB_WEEK_DAYS_TBL . " (`id`, `weekDay`, `position`) VALUES ('1', '0', '6')");
		$db->db_query("insert into " . BAB_WEEK_DAYS_TBL . " (`id`, `weekDay`, `position`) VALUES ('2', '1', '0')");
		$db->db_query("insert into " . BAB_WEEK_DAYS_TBL . " (`id`, `weekDay`, `position`) VALUES ('3', '2', '1')");
		$db->db_query("insert into " . BAB_WEEK_DAYS_TBL . " (`id`, `weekDay`, `position`) VALUES ('4', '3', '2')");
		$db->db_query("insert into " . BAB_WEEK_DAYS_TBL . " (`id`, `weekDay`, `position`) VALUES ('5', '4', '3')");
		$db->db_query("insert into " . BAB_WEEK_DAYS_TBL . " (`id`, `weekDay`, `position`) VALUES ('6', '5', '4')");
		$db->db_query("insert into " . BAB_WEEK_DAYS_TBL . " (`id`, `weekDay`, `position`) VALUES ('7', '6', '5')");
	}

	if (bab_isTable('bab_tskmgr_working_hours') && !bab_isTable(BAB_WORKING_HOURS_TBL)) {
		$db->db_query("ALTER TABLE `bab_tskmgr_working_hours` RENAME `".BAB_WORKING_HOURS_TBL."` ");
		$db->db_query("ALTER TABLE `bab_sites_nonworking_days` ADD INDEX ( `nw_day` )");
	}


	


	if (!bab_isTable(BAB_VAC_CALENDAR_TBL)) {
		$res = $db->db_query("
		CREATE TABLE `".BAB_VAC_CALENDAR_TBL."` (
		  `id` int(10) unsigned NOT NULL auto_increment,
		  `id_user` int(10) unsigned NOT NULL,
		  `monthkey` mediumint(6) unsigned NOT NULL,
		  `cal_date` date NOT NULL,
		  `ampm` tinyint(1) unsigned NOT NULL,
		  `period_type` tinyint(3) unsigned NOT NULL,
		  `id_entry` int(10) unsigned NOT NULL,
		  `color` varchar(6) NOT NULL,
		  PRIMARY KEY  (`id`),
		  KEY `id_user` (`id_user`,`monthkey`,`cal_date`)
		)
		");

		if(false == $res)
		{
			return "Creation of <b>".BAB_VAC_CALENDAR_TBL."</b> failed !<br>";
		}
	}

	if (bab_isTableField(BAB_VAC_ENTRIES_ELEM_TBL, 'id_type')) {
		$db->db_query("ALTER TABLE `".BAB_VAC_ENTRIES_ELEM_TBL."` CHANGE `id_type` `id_right` int(11) unsigned default NULL");
	}


	if (!bab_isTable(BAB_VAC_RGROUPS_TBL)) {
		$res = $db->db_query("
			CREATE TABLE `".BAB_VAC_RGROUPS_TBL."` (
			`id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
			`name` VARCHAR( 255 ) NOT NULL ,
			PRIMARY KEY ( `id` )
			)
		");

		if(false == $res)
		{
			return "Creation of <b>".BAB_VAC_RGROUPS_TBL."</b> failed !<br>";
		}
	}


	if (!bab_isTableField(BAB_VAC_RIGHTS_TBL, 'id_rgroup')) {
		$db->db_query("ALTER TABLE `bab_vac_rights` ADD `id_rgroup` INT UNSIGNED NOT NULL");
		$db->db_query("ALTER TABLE `bab_vac_rights` ADD INDEX ( `id_rgroup` )");
	}

	$res = $db->db_query("SELECT id FROM ".BAB_CAL_EVENTS_TBL." WHERE hash LIKE 'V_%'");
	while ($arr = $db->db_fetch_assoc($res)) {
		$db->db_query("DELETE FROM ".BAB_CAL_EVENTS_OWNERS_TBL." WHERE id_event=".$db->quote($arr['id']));
		$db->db_query("DELETE FROM ".BAB_CAL_EVENTS_REMINDERS_TBL." WHERE id_event=".$db->quote($arr['id']));
		$db->db_query("DELETE FROM ".BAB_CAL_EVENTS_NOTES_TBL." WHERE id_event=".$db->quote($arr['id']));
		$db->db_query("DELETE FROM ".BAB_CAL_EVENTS_TBL." WHERE id=".$db->quote($arr['id']));
	}


	if (!bab_isTable(BAB_VAC_COMANAGER_TBL)) {
		$res = $db->db_query("
			CREATE TABLE `".BAB_VAC_COMANAGER_TBL."` (
			`id_entity` INT UNSIGNED NOT NULL ,
			`id_user` INT UNSIGNED NOT NULL ,
			PRIMARY KEY ( `id_entity` , `id_user` )
			)
		");

		if(false == $res)
		{
			return "Creation of <b>".BAB_VAC_RGROUPS_TBL."</b> failed !<br>";
		}
	}


	// working days
	
	
	function setUserWd($id_user, $WDStr, $starttime, $endtime) {
		$awd = explode(',',$WDStr);

		$db = &$GLOBALS['babDB'];
		foreach($awd as $d) {
			$db->db_query("INSERT INTO ".BAB_WORKING_HOURS_TBL."( weekDay, idUser,  startHour, endHour) VALUES (".$db->quote($d).','.$db->quote($id_user).", '".$starttime."', '".$endtime."')");
		}
	}
	
	
	if (bab_isTableField(BAB_SITES_TBL, 'workdays')) {
	
		$db->db_query("DELETE FROM ".BAB_WORKING_HOURS_TBL." WHERE idUser='0'");

		$res = $db->db_query("SELECT workdays FROM ".BAB_SITES_TBL." WHERE name=".$db->quote($GLOBALS['babSiteName']));
		$arr = $db->db_fetch_assoc($res);
		setUserWd(0, $arr['workdays'], '00:00:00', '24:00:00');
	}
	
	if (bab_isTableField(BAB_CAL_USER_OPTIONS_TBL, 'workdays')) {
		$db->db_query("DELETE FROM ".BAB_WORKING_HOURS_TBL." WHERE idUser>'0'");
		
		$res = $db->db_query("SELECT id_user, workdays, start_time, end_time FROM ".BAB_CAL_USER_OPTIONS_TBL." WHERE workdays<>".$db->quote($arr['workdays']."  AND workdays<>''"));
		while($arr = $db->db_fetch_assoc($res)) {
			setUserWd($arr['id_user'], $arr['workdays'], $arr['start_time'],$arr['end_time']);
		}
	
		}
	
		if (!bab_isTableField(BAB_COMMENTS_TBL, 'id_author')) {
	
			$db->db_query("ALTER TABLE ".BAB_COMMENTS_TBL." ADD `id_author` INT( 11 )  UNSIGNED DEFAULT '0' NOT NULL AFTER `id_topic`");
		}
	
	return $ret;
}




function upgrade602to603()
{
	$ret = "";
	$db = & $GLOBALS['babDB'];


	if (!bab_isTableField(BAB_VAC_RIGHTS_RULES_TBL, 'trigger_p1_begin')) {

		$db->db_query("ALTER TABLE `".BAB_VAC_RIGHTS_RULES_TBL."` 
			ADD `trigger_p1_begin` DATE NOT NULL ,
			ADD `trigger_p1_end` DATE NOT NULL ,
			ADD `trigger_p2_begin` DATE NOT NULL ,
			ADD `trigger_p2_end` DATE NOT NULL
		");

		/**
		 * remove trigger_inperiod
		 * 
		 *	0 : Sur toute la période du droit
		 *  1 : Dans la période de la règle
		 *  2 : En dehors de la période de la règle et dans la période du droit
		 */

		 $res = $db->db_query("
			SELECT 
				t1.id,
				t1.trigger_inperiod, 
				t1.period_start,
				t1.period_end,
				t2.date_begin,
				t2.date_end 

			FROM 
				".BAB_VAC_RIGHTS_RULES_TBL." t1,
				".BAB_VAC_RIGHTS_TBL." t2 
			WHERE 
				t1.id_right = t2.id 
			");

		while ($arr = $db->db_fetch_assoc($res)) {
			switch($arr['trigger_inperiod']) {
				case 0:
					$trigger_p1_begin	= $arr['date_begin'];
					$trigger_p1_end		= $arr['date_end'];
					$trigger_p2_begin	= '0000-00-00';
					$trigger_p2_end		= '0000-00-00';
					break;

				case 1:
					$trigger_p1_begin	= $arr['period_start'];
					$trigger_p1_end		= $arr['period_end'];
					$trigger_p2_begin	= '0000-00-00';
					$trigger_p2_end		= '0000-00-00';
					break;

				case 2:
					$trigger_p1_begin	= $arr['date_begin'];
					$trigger_p1_end		= $arr['period_start'];
					$trigger_p2_begin	= $arr['period_end'];
					$trigger_p2_end		= $arr['date_end'];
					break;
			}


			$db->db_query("
				UPDATE ".BAB_VAC_RIGHTS_RULES_TBL." 
				SET 
					trigger_p1_begin	=".$db->quote($trigger_p1_begin).", 
					trigger_p1_end		=".$db->quote($trigger_p1_end).", 
					trigger_p2_begin	=".$db->quote($trigger_p2_begin).", 
					trigger_p2_end		=".$db->quote($trigger_p2_end)." 
				WHERE 
					id=".$db->quote($arr['id'])."
				");
		}

		$db->db_query("ALTER TABLE `".BAB_VAC_RIGHTS_RULES_TBL."` DROP `trigger_inperiod`");
		$db->db_query("ALTER TABLE `".BAB_VAC_RIGHTS_RULES_TBL."` ADD `trigger_overlap` TINYINT UNSIGNED NOT NULL");
		
	}

	if (!bab_isTable(BAB_TAGSMAN_GROUPS_TBL)) {

		$db->db_query("
		
				CREATE TABLE ".BAB_TAGSMAN_GROUPS_TBL." (
				  id int(11) unsigned NOT NULL auto_increment,
				  id_object int(11) unsigned NOT NULL default '0',
				  id_group int(11) unsigned NOT NULL default '0',
				  PRIMARY KEY  (id),
				  KEY id_object (id_object),
				  KEY id_group (id_group)
				)
			
		");

	}

	if (!bab_isTable(BAB_TAGS_TBL))
	{
		$res = $db->db_query("
			CREATE TABLE " . BAB_TAGS_TBL . " (
				id int(11) unsigned NOT NULL auto_increment,
				tag_name VARCHAR (255) not null,
				PRIMARY KEY (id),
				KEY tag_name (tag_name)
			)
		");
	}

	if (!bab_isTableField(BAB_TOPICS_TBL, 'busetags')) {
		$db->db_query("ALTER TABLE ".BAB_TOPICS_TBL." ADD busetags ENUM('Y','N') DEFAULT 'Y' NOT NULL");
		$db->db_query("update ".BAB_TOPICS_TBL." set busetags='N'");
	}

	if (!bab_isTable(BAB_ART_DRAFTS_TAGS_TBL)) {

		$db->db_query("
		
				CREATE TABLE ".BAB_ART_DRAFTS_TAGS_TBL." (
				  id_draft int(11) unsigned NOT NULL default '0',
				  id_tag int(11) unsigned NOT NULL default '0',
				  KEY id_draft (id_draft),
				  KEY id_tag (id_tag)
				)
			
		");

	}
	if (!bab_isTable(BAB_ART_TAGS_TBL)) {

		$db->db_query("
		
				CREATE TABLE ".BAB_ART_TAGS_TBL." (
				  id_art int(11) unsigned NOT NULL default '0',
				  id_tag int(11) unsigned NOT NULL default '0',
				  KEY id_art (id_art),
				  KEY id_tag (id_tag)
				)
			
		");

	}

	return $ret;
}



function upgrade604to605()
{
	$ret = "";
	$db = & $GLOBALS['babDB'];

	if (!bab_isTableField(BAB_ART_DRAFTS_FILES_TBL, 'ordering')) {
		$db->db_query("ALTER TABLE ".BAB_ART_DRAFTS_FILES_TBL." ADD ordering smallint(2) UNSIGNED NOT NULL");
		$res = $db->db_query('select distinct id_draft from '.BAB_ART_DRAFTS_FILES_TBL.'');
		while($row = $db->db_fetch_array($res))
			{
			$ord = 0;
			$res2 = $db->db_query("select id from ".BAB_ART_DRAFTS_FILES_TBL." where id_draft='".$row['id_draft']."' order by name asc");
			while($row2 = $db->db_fetch_array($res2))
				{
				$db->db_query("update ".BAB_ART_DRAFTS_FILES_TBL." set ordering='".$ord."' where id='".$row2['id']."'");
				$ord++;
				}
			}
	}

	if (!bab_isTableField(BAB_ART_FILES_TBL, 'ordering')) {
		$db->db_query("ALTER TABLE ".BAB_ART_FILES_TBL." ADD ordering smallint(2) UNSIGNED NOT NULL");
		$res = $db->db_query('select distinct id_article from '.BAB_ART_FILES_TBL.'');
		while($row = $db->db_fetch_array($res))
			{
			$ord = 0;
			$res2 = $db->db_query("select id from ".BAB_ART_FILES_TBL." where id_article='".$row['id_article']."' order by name asc");
			while($row2 = $db->db_fetch_array($res2))
				{
				$db->db_query("update ".BAB_ART_FILES_TBL." set ordering='".$ord."' where id='".$row2['id']."'");
				$ord++;
				}
			}
	}
	return $ret;
}


function upgrade605to606()
{
	$ret = "";
	global $babDB;
	
	$babDB->db_query("TRUNCATE bab_vac_calendar");
	$res = $babDB->db_query("SELECT id FROM ".BAB_INDEX_FILES_TBL."");
	$ids = array();
	while ($arr = $babDB->db_fetch_assoc($res)) {
		$ids[$arr['id']] = 1;
	}
	
	if (!isset($ids[1])) {
		$babDB->db_query("INSERT INTO bab_index_files VALUES (1, 'File manager', 'bab_files', 1, 0)");
	}
	
	if (!isset($ids[2])) {
		$babDB->db_query("INSERT INTO bab_index_files VALUES (2, 'Articles files', 'bab_art_files', 1, 0)");
	}
	
	if (!isset($ids[3])) {
		$babDB->db_query("INSERT INTO bab_index_files VALUES (3, 'Forum post files', 'bab_forumsfiles', 0, 0)");
	}

	return $ret;
}


function upgrade606to610()
{
	global $babDB;
	$ret = "";
	
	$babDB->db_query("TRUNCATE bab_vac_calendar");
	
	if (bab_isTableField(BAB_SITES_TBL, 'workdays')) {
		$babDB->db_query("ALTER TABLE ".BAB_SITES_TBL." DROP workdays");
	}
	
	if (bab_isTableField(BAB_CAL_USER_OPTIONS_TBL, 'workdays')) {
		$babDB->db_query("ALTER TABLE ".BAB_CAL_USER_OPTIONS_TBL." DROP workdays");
	}
	
	if (!bab_isTableField(BAB_USERS_TBL, 'cookie_validity')) {
		$babDB->db_query("ALTER TABLE `".BAB_USERS_TBL."` ADD `cookie_validity` DATETIME NOT NULL  default '0000-00-00 00:00:00', ADD `cookie_id` VARCHAR( 255 ) NOT NULL");
		$babDB->db_query("ALTER TABLE `".BAB_USERS_TBL."` ADD INDEX ( `cookie_id` )");
	} 
	
	$res = $babDB->db_query("SELECT * FROM ".BAB_MIME_TYPES_TBL." WHERE ext='odt'");
	if (0 == $babDB->db_num_rows($res)) {
	
		$babDB->db_query("
		INSERT INTO `".BAB_MIME_TYPES_TBL."` 
			(`ext`, `mimetype`) 
		VALUES 
			('odt', 'application/vnd.oasis.opendocument.text'),
			('ods', 'application/vnd.oasis.opendocument.spreadsheet'),
			('odp', 'application/vnd.oasis.opendocument.presentation'),
			('odc', 'application/vnd.oasis.opendocument.chart'),
			('odf', 'application/vnd.oasis.opendocument.formula'),
			('odb', 'application/vnd.oasis.opendocument.database'),
			('odi', 'application/vnd.oasis.opendocument.image'),
			('odm', 'application/vnd.oasis.opendocument.text-master'),
			('ott', 'application/vnd.oasis.opendocument.text-template'),
			('ots', 'application/vnd.oasis.opendocument.spreadsheet-template'),
			('otp', 'application/vnd.oasis.opendocument.presentation-template'),
			('otg', 'application/vnd.oasis.opendocument.graphics-template')
		");
	}
	
	if (!bab_isTable(BAB_EVENT_LISTENERS_TBL)) {
	
		$babDB->db_query("
		CREATE TABLE `".BAB_EVENT_LISTENERS_TBL."` (
			`id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
			`event_class_name` VARCHAR( 100 ) NOT NULL ,
			`function_name` VARCHAR( 100 ) NOT NULL ,
			`require_file` VARCHAR( 255 ) NOT NULL ,
			`addon_name` VARCHAR( 255 ) NOT NULL ,
			PRIMARY KEY  (`id`),
			UNIQUE KEY `event` (`event_class_name`,`function_name`,`require_file`)
			)"
		);
		
		
		$babDB->db_query("INSERT INTO `".BAB_EVENT_LISTENERS_TBL."` (`id`, `event_class_name`, `function_name`, `require_file`, `addon_name`) VALUES (1, 'bab_eventCreatePeriods', 'bab_NWD_onCreatePeriods', 'utilit/nwdaysincl.php', 'core')");

	}
	
	if (!bab_isTable(BAB_INDEX_SPOOLER_TBL)) {
		$babDB->db_query("
			CREATE TABLE `".BAB_INDEX_SPOOLER_TBL."` (
			`object` VARCHAR( 255 ) NOT NULL ,
			`require_once` VARCHAR( 255 ) NOT NULL ,
			`function` VARCHAR( 255 ) NOT NULL ,
			`function_parameter` LONGTEXT NOT NULL ,
			PRIMARY KEY ( `object` )
			)"
		);
	}


	if (!bab_isTableField(BAB_SITES_TBL, 'elapstime')) {
		$babDB->db_query("ALTER TABLE ".BAB_SITES_TBL." ADD elapstime TINYINT(2) UNSIGNED DEFAULT '30' NOT NULL");
	}
	if (!bab_isTableField(BAB_SITES_TBL, 'defaultview')) {
		$babDB->db_query("ALTER TABLE ".BAB_SITES_TBL." ADD defaultview tinyint(3) UNSIGNED DEFAULT '0' NOT NULL");
	}
	if (!bab_isTableField(BAB_SITES_TBL, 'start_time')) {
		$babDB->db_query("ALTER TABLE ".BAB_SITES_TBL." ADD start_time time NOT NULL DEFAULT '08:00:00'");
	}
	if (!bab_isTableField(BAB_SITES_TBL, 'end_time')) {
		$babDB->db_query("ALTER TABLE ".BAB_SITES_TBL." ADD end_time time NOT NULL DEFAULT '18:00:00'");
	}
	if (!bab_isTableField(BAB_SITES_TBL, 'allday')) {
		$babDB->db_query("ALTER TABLE ".BAB_SITES_TBL." ADD allday enum('Y','N') NOT NULL default 'Y'");
	}
	if (!bab_isTableField(BAB_SITES_TBL, 'usebgcolor')) {
		$babDB->db_query("ALTER TABLE ".BAB_SITES_TBL." ADD usebgcolor enum('Y','N') NOT NULL default 'Y'");
	}

	return $ret;
}




function upgrade610to611()
{
	global $babDB;
	$ret = "";
	
	include_once $GLOBALS['babInstallPath']."utilit/eventincl.php";
	bab_addEventListener('bab_eventPeriodModified', 'bab_vac_onModifyPeriod', 'utilit/vacincl.php');
	
	$babDB->db_query("UPDATE ".BAB_EVENT_LISTENERS_TBL." set event_class_name='bab_eventBeforePeriodsCreated' WHERE event_class_name='bab_eventCreatePeriods'");
	
	bab_addEventListener('bab_eventBeforePeriodsCreated', 'bab_NWD_onCreatePeriods', 'utilit/nwdaysincl.php');

	return $ret;
}



function upgrade612to620()
{
	global $babDB;
	$ret = "";
	
	$babDB->db_query("UPDATE ".BAB_EVENT_LISTENERS_TBL." set event_class_name='bab_eventBeforePeriodsCreated' WHERE event_class_name='bab_eventCreatePeriods'");
	
	include_once $GLOBALS['babInstallPath']."utilit/eventincl.php";
	bab_addEventListener('bab_eventBeforePeriodsCreated', 'bab_NWD_onCreatePeriods', 'utilit/nwdaysincl.php');
	
	return $ret;
}





/**
 * Upgrade function
 * If the function return true, bab_ini will be updated with $version_ini
 * For error messages, use $babBody->addError()
 *
 * @param	string	$version_base	version stored in bab_ini		(from old version)
 * @param	string	$version_ini	version stored in version.inc	(from new version)
 * @return 	boolean
 */
function ovidentia_upgrade($version_base,$version_ini) {

	global $babBody, $babDB;

	
	/**
	 * Old upgrades
	 * from 5.5.3 to 6.2.0
	 */
	
	upgrade553to554();
	upgrade554to555();
	upgrade555to556();
	upgrade558to559();
	upgrade562to563();
	upgrade563to564();
	upgrade564to565();
	upgrade565to566();
	upgrade566to570();
	upgrade570to571();
	upgrade571to572();
	upgrade572to573();
	upgrade573to574();
	upgrade574to575();
	upgrade575to576();
	upgrade577to578();
	upgrade578to579();
	upgrade580to581();
	upgrade581to582();
	upgrade582to583();
	upgrade583to584();
	upgrade584to585();
	upgrade585to586();
	upgrade586to587();
	upgrade587to588();
	upgrade588to589();
	upgrade589to600();
	upgrade600to601();
	upgrade601to602();
	upgrade602to603();
	upgrade604to605();
	upgrade605to606();
	upgrade606to610();
	
	
	if (!bab_isTableField(BAB_EVENT_LISTENERS_TBL, 'priority')) {
		$babDB->db_query("ALTER TABLE ".BAB_EVENT_LISTENERS_TBL." ADD priority INT( 11 )  UNSIGNED DEFAULT '0' NOT NULL");
	}
	
	upgrade610to611();
	upgrade612to620();
	
	
	
	
	/**
	 * Upgrade to 6.3.0
	 */
	 
	
	
	if (!bab_isTable(BAB_UPGRADE_MESSAGES_TBL)) {
		$babDB->db_query('
			CREATE TABLE `'.BAB_UPGRADE_MESSAGES_TBL.'` (
			  `id` int(11) NOT NULL auto_increment,
			  `addon_name` varchar(255) NOT NULL,
			  `dt_insert` datetime NOT NULL,
			  `uid` varchar(255) NOT NULL,
			  `message` text NOT NULL,
			  PRIMARY KEY  (`id`),
			  KEY `uid` (`uid`)
			)
		');
	}
	
	/**
	 * Upgrade to 6.3.1 nothing todo
	 */
	
	/**
	 * Upgrade to 6.4.0
	 */
	 
	 
	
	
	// event registration for htmlarea editor
	include_once $GLOBALS['babInstallPath']."utilit/eventincl.php";
	bab_addEventListener('bab_eventEditorContentToEditor'	, 'htmlarea_onContentToEditor'	, 'utilit/htmlareaincl.php'	, BAB_ADDON_CORE_NAME, 100);
	bab_addEventListener('bab_eventEditorRequestToContent'	, 'htmlarea_onRequestToContent'	, 'utilit/htmlareaincl.php'	, BAB_ADDON_CORE_NAME, 100);
	bab_addEventListener('bab_eventEditorContentToHtml'		, 'htmlarea_onContentToHtml'	, 'utilit/htmlareaincl.php'	, BAB_ADDON_CORE_NAME, 100);
	
	// event registration for editor core implementations
	bab_addEventListener('bab_eventEditors'					, 'bab_onEventEditors'			, 'utilit/editorincl.php');
	
	// event registration for editor core functionalities
	bab_addEventListener('bab_eventEditorFunctions'			, 'bab_onEditorFunctions'		, 'utilit/editorincl.php');
	
	/**
	 * Upgrade to 6.5.0
	 */

	$res = $babDB->db_query("SELECT  distinct weekDay FROM ".BAB_WORKING_HOURS_TBL." WHERE idUser ='0'");
	$wdays = array();

	while( $arr = $babDB->db_fetch_array($res))
	{
		$wdays[] = $arr['weekDay'];
	}

	// users 
	$res = $babDB->db_query("select id_user, start_time, end_time from ".BAB_CAL_USER_OPTIONS_TBL."");
	while( $arr = $babDB->db_fetch_array($res))
	{
		$rs = $babDB->db_query("select id from ".BAB_WORKING_HOURS_TBL." where idUser='".$arr['id_user']."'");
		if( !$rs || $babDB->db_num_rows($rs) == 0 )
		{
			for( $k=0; $k < count($wdays); $k++ )
			{
				$babDB->db_query("INSERT INTO ".BAB_WORKING_HOURS_TBL."( weekDay, idUser,  startHour, endHour) VALUES ('".$wdays[$k]."','".$arr['id_user']."', '".$arr['start_time']."', '".$arr['end_time']."')");
			}
		}
	}
	
	if (!bab_isTable(BAB_DG_ACL_GROUPS_TBL)) 
		{

		$babDB->db_query("
		
				CREATE TABLE ".BAB_DG_ACL_GROUPS_TBL." (
				  id int(11) unsigned NOT NULL auto_increment,
				  id_object int(11) unsigned NOT NULL default '0',
				  id_group int(11) unsigned NOT NULL default '0',
				  PRIMARY KEY  (id),
				  KEY id_object (id_object),
				  KEY id_group (id_group)
				)
			
		");

	
		$resdg = $babDB->db_query("select id, name, description from ".BAB_DG_GROUPS_TBL);

		while( $arrdg = $babDB->db_fetch_array($resdg))
		{
			$babDB->db_query("insert into ".BAB_TOPICS_CATEGORIES_TBL." (title, description, enabled, template, id_dgowner, id_parent, display_tmpl) VALUES ( '" .$babDB->db_escape_string($arrdg['name']). "','" . $babDB->db_escape_string($arrdg['description']). "','Y', '','" . $arrdg['id']. "', 0, '')");
			$idtopcat = $babDB->db_insert_id();

			$res = $babDB->db_query("select max(ordering) from ".BAB_SECTIONS_ORDER_TBL." so, ".BAB_TOPICS_CATEGORIES_TBL." tc where so.position='0' and so.type='3' and tc.id=so.id_section and tc.id_dgowner='".$arrdg['id']."'");
			$arr = $babDB->db_fetch_array($res);
			if( empty($arr[0]))
				{
				$res = $babDB->db_query("select max(ordering) from ".BAB_SECTIONS_ORDER_TBL." so where so.position='0'");
				$arr = $babDB->db_fetch_array($res);
				if( empty($arr[0]))
					$arr[0] = 0;
				}

			$babDB->db_query("update ".BAB_SECTIONS_ORDER_TBL." set ordering=ordering+1 where position='0' and ordering > '".$babDB->db_escape_string($arr[0])."'");
			$babDB->db_query("insert into ".BAB_SECTIONS_ORDER_TBL." (id_section, position, type, ordering) VALUES ('" .$babDB->db_escape_string($idtopcat). "', '0', '3', '" . $babDB->db_escape_string(($arr[0]+1)). "')");

			$restc = $babDB->db_query("select id from ".BAB_TOPICS_CATEGORIES_TBL." where id_dgowner='".$arrdg['id']."' and id!='".$idtopcat."' and id_parent='0'");
			$ord = 1;
			while( $arrtc = $babDB->db_fetch_array($restc))
			{
				$babDB->db_query("delete from ".BAB_TOPCAT_ORDER_TBL." where id_topcat='".$arrtc['id']."' and type='1' and id_parent='0'");
				$babDB->db_query("insert into ".BAB_TOPCAT_ORDER_TBL." (id_topcat, type, ordering, id_parent) VALUES ('" .$babDB->db_escape_string($arrtc['id']). "', '1', '" . $ord. "', '".$babDB->db_escape_string($idtopcat)."')");
				$ord++;
			}
			$babDB->db_query("update ".BAB_TOPICS_CATEGORIES_TBL." set id_parent='".$idtopcat."' where id_dgowner='".$arrdg['id']."' and id!='".$idtopcat."' and id_parent='0'");

			$res = $babDB->db_query("select max(ordering) from ".BAB_TOPCAT_ORDER_TBL." where id_parent='0'");
			$arr = $babDB->db_fetch_array($res);
			if( isset($arr[0]))
				$ord = $arr[0] + 1;
			else
				$ord = 1;
			$babDB->db_query("insert into ".BAB_TOPCAT_ORDER_TBL." (id_topcat, type, ordering, id_parent) VALUES ('" .$babDB->db_escape_string($idtopcat). "', '1', '" . $babDB->db_escape_string($ord). "', '0')");

		}
	}


	if (!bab_isTableField(BAB_FAR_INSTANCES_TBL, 'far_order')) 
		{
		$babDB->db_query("ALTER TABLE ".BAB_FAR_INSTANCES_TBL." ADD far_order INT( 11 )  UNSIGNED DEFAULT '0' NOT NULL");
		$res = $babDB->db_query("select fat.*, fit.iduser, fit.id as fitid from ".BAB_FLOW_APPROVERS_TBL." fat left join ".BAB_FA_INSTANCES_TBL." fit on  fat.id=fit.idsch");
		while( $row = $babDB->db_fetch_array($res))
			{
			$rs = $babDB->db_query("select * from ".BAB_FAR_INSTANCES_TBL." where idschi='".$row['fitid']."'");
			while( $arr = $babDB->db_fetch_array($rs))
				{
				$results[$arr['iduser']] = $arr;
				}
			$babDB->db_query("delete from ".BAB_FAR_INSTANCES_TBL." where idschi='".$row['fitid']."'");

			$tab = explode(",", $row['formula']);
			for( $i= 0; $i < count($tab); $i++)
				{
				$rr = array();
				if( strchr($tab[$i], "&"))
					$op = "&";
				else
					$op = "|";

				$rr = explode($op, $tab[$i]);
				for($j = 0; $j < count($rr); $j++)
					{
					if( isset($results[$rr[$j]]))
						{
						$result = $results[$rr[$j]]['result'];
						$notified = $results[$rr[$j]]['notified'];
						}
					else
						{
						$result = '';
						$notified = 'N';
						}

					$babDB->db_query("insert into ".BAB_FAR_INSTANCES_TBL." (idschi, iduser, result, notified, far_order) VALUES ('".$babDB->db_escape_string($row['fitid'])."', '".$babDB->db_escape_string($rr[$j])."', '".$result."','".$notified."', '".$i."')");
					}
				}
			}

		$babDB->db_query("UPDATE ".BAB_USERS_LOG_TBL." SET schi_change='1'");
		}

	if (!bab_isTableField(BAB_SITES_TBL, 'ldap_userdn')) 
		{
		$babDB->db_query("ALTER TABLE ".BAB_SITES_TBL." ADD ldap_userdn TEXT NOT NULL");
		}

	if (bab_isTableField(BAB_SITES_TBL, 'ldap_password')) 
		{
		$babDB->db_query("ALTER TABLE ".BAB_SITES_TBL." DROP ldap_password");
		}

	if (bab_isTableField(BAB_SITES_TBL, 'ldap_passwordtype')) 
		{
		$babDB->db_query("ALTER TABLE ".BAB_SITES_TBL." DROP ldap_passwordtype");
		}

	if (bab_isTableField(BAB_SITES_TBL, 'ldap_basedn')) 
		{
		$babDB->db_query("ALTER TABLE ".BAB_SITES_TBL." DROP ldap_basedn");
		}

	
	if (!bab_isTable(BAB_FMNOTIFY_GROUPS_TBL)) 
	{

	$babDB->db_query("
	
			CREATE TABLE ".BAB_FMNOTIFY_GROUPS_TBL." (
			  id int(11) unsigned NOT NULL auto_increment,
			  id_object int(11) unsigned NOT NULL default '0',
			  id_group int(11) unsigned NOT NULL default '0',
			  PRIMARY KEY  (id),
			  KEY id_object (id_object),
			  KEY id_group (id_group)
			)
		
			");
	$babDB->db_query("insert into ".BAB_FMNOTIFY_GROUPS_TBL." select * from ".BAB_FMDOWNLOAD_GROUPS_TBL."");

	}

	if (!bab_isTable(BAB_FILES_TAGS_TBL)) {

		$babDB->db_query("
		
				CREATE TABLE ".BAB_FILES_TAGS_TBL." (
				  id_file int(11) unsigned NOT NULL default '0',
				  id_tag int(11) unsigned NOT NULL default '0',
				  KEY id_file (id_file),
				  KEY id_tag (id_tag)
				)
			
		");

		$res = $babDB->db_query("select * from ".BAB_TAGS_TBL."");
		$tags = array();
		while( $arr = $babDB->db_fetch_array($res))
		{
			$tags[$arr['tag_name']] = $arr['id'];
		}
		
		$res = $babDB->db_query("select id, keywords from ".BAB_FILES_TBL."");
		while( $arr = $babDB->db_fetch_array($res))
		{
			$tok = strtok($arr['keywords'], ' ,');
			while($tok !== false )
			{
				$tok = trim($tok);
				if( !empty($tok))
					{
					if( !isset($tags[$tok]))
						{
						$babDB->db_query("insert into ".BAB_TAGS_TBL." (tag_name) values ('".$babDB->db_escape_string($tok)."')");
						$idtag = $babDB->db_insert_id();
						$tags[$tok] = $idtag;
						}
					else
						{
						$idtag = $tags[$tok];
						}
					$babDB->db_query("insert into ".BAB_FILES_TAGS_TBL." (id_file, id_tag) values ('".$arr['id']."', '".$idtag."')");
					}
				$tok = strtok(' ,');
			}
		}

	$babDB->db_query("ALTER TABLE ".BAB_FILES_TBL." DROP keywords");
	}

	if(!bab_isTableField(BAB_TSKMGR_TASK_LIST_FILTER_TBL, 'iTaskCompletion')) 
	{
		$babDB->db_query("ALTER TABLE ".BAB_TSKMGR_TASK_LIST_FILTER_TBL." ADD `iTaskCompletion` INT(11) NOT NULL default '-1'");
	}

	/**
	 * Upgrade to 6.5.91
	 */

	// There was still a few registry information related to the kernel not
	// placed under the /bab/ node.
	$registry = bab_getRegistryInstance();
	
	if ($registry)
	{
		// Registry about orgcharts is now in "/bab/orgchart/"
		$registry->moveDirectory('/orgchart/', '/bab/orgchart/');
	
		// Registry about statistics is now in "/bab/statistics/"
		$registry->moveDirectory('/statistics/', '/bab/statistics/');
	}

	
	/**
	 * Upgrade to 6.5.92
	 */

	 /* this flag allow admin to specify if users can add tags to thesaurus or not */
	if(!bab_isTableField(BAB_FM_FOLDERS_TBL, 'baddtags')) 
	{
		$babDB->db_query("ALTER TABLE ".BAB_FM_FOLDERS_TBL." ADD baddtags ENUM('Y','N') DEFAULT 'Y' NOT NULL");
	}

	if(!bab_isTable(BAB_FM_FOLDERS_CLIPBOARD_TBL))
	{
		$babDB->db_query("
			CREATE TABLE ".BAB_FM_FOLDERS_CLIPBOARD_TBL." (
			  `iId` int(11) unsigned NOT NULL auto_increment,
			  `iIdDgOwner` int(11) unsigned NOT NULL,
			  `iIdRootFolder` int(11) unsigned NOT NULL,
			  `iIdFolder` int(11) unsigned NOT NULL,
			  `sName`  varchar(255) NOT NULL,
			  `sRelativePath` TEXT NOT NULL,
			  `sGroup` ENUM('Y','N') NOT NULL,
			  `sCollective` ENUM('Y','N') NOT NULL,
			  `iIdOwner` int(11) unsigned NOT NULL,
			  `sCheckSum` CHAR( 32 ) NOT NULL,
			  PRIMARY KEY  (`iId`),
			  UNIQUE `sFolder` (`sGroup`, `sCollective`, `sCheckSum`, `iIdOwner`),
			  KEY `iIdDgOwner` (`iIdDgOwner`),
			  KEY `iIdFolder` (`iIdFolder`),
			  KEY `sCollective` (`sCollective`),
			  KEY `iIdOwner` (`iIdOwner`)
			)
		");
		
		$babDB->db_query("ALTER TABLE ". BAB_FILES_TBL." ADD `iIdDgOwner` int(11) unsigned NOT NULL");
	}


	if(!bab_isTableField(BAB_FM_FOLDERS_TBL, 'sRelativePath')) 
	{
		$babDB->db_query("ALTER TABLE ".BAB_FM_FOLDERS_TBL." ADD `sRelativePath` TEXT NOT NULL AFTER `id`");
		if(true === fmUpgrade())
		{
			__renameFmFilesVersions();
			bab_setUpgradeLogMsg(BAB_ADDON_CORE_NAME, 'The file manager upgrade was successfully completed', 'bab660FmUpgradeDone');
		}
		else 
		{
			$babDB->db_query("ALTER TABLE ".BAB_FM_FOLDERS_TBL." DROP `sRelativePath`");
			return false;
		}
	}
	else 
	{
		$ret = bab_getUpgradeLogMsg(BAB_ADDON_CORE_NAME, 'bab660FmUpgradeDone');
		if(false === $ret)
		{
			if(true === updateFmFromPreviousUpgrade())
			{
				__renameFmFilesVersions();
				bab_setUpgradeLogMsg(BAB_ADDON_CORE_NAME, 'The file manager upgrade was successfully completed', 'bab660FmUpgradeDone');
			}
			else 
			{
				return false;
			}
		}
	}


	/**
	 * Upgrade to 6.5.100
	 */
	if (!bab_isTable(BAB_DEF_TOPCATCOM_GROUPS_TBL)) 
	{

	$babDB->db_query("
	
			CREATE TABLE ".BAB_DEF_TOPCATCOM_GROUPS_TBL." (
			  id int(11) unsigned NOT NULL auto_increment,
			  id_object int(11) unsigned NOT NULL default '0',
			  id_group int(11) unsigned NOT NULL default '0',
			  PRIMARY KEY  (id),
			  KEY id_object (id_object),
			  KEY id_group (id_group)
			)
		
			");
	}

	if (!bab_isTable(BAB_DEF_TOPCATMAN_GROUPS_TBL)) 
	{

	$babDB->db_query("
	
			CREATE TABLE ".BAB_DEF_TOPCATMAN_GROUPS_TBL." (
			  id int(11) unsigned NOT NULL auto_increment,
			  id_object int(11) unsigned NOT NULL default '0',
			  id_group int(11) unsigned NOT NULL default '0',
			  PRIMARY KEY  (id),
			  KEY id_object (id_object),
			  KEY id_group (id_group)
			)
		
			");
	}
	
	if (!bab_isTable(BAB_DEF_TOPCATMOD_GROUPS_TBL)) 
	{

	$babDB->db_query("
	
			CREATE TABLE ".BAB_DEF_TOPCATMOD_GROUPS_TBL." (
			  id int(11) unsigned NOT NULL auto_increment,
			  id_object int(11) unsigned NOT NULL default '0',
			  id_group int(11) unsigned NOT NULL default '0',
			  PRIMARY KEY  (id),
			  KEY id_object (id_object),
			  KEY id_group (id_group)
			)
		
			");
	}

	if (!bab_isTable(BAB_DEF_TOPCATSUB_GROUPS_TBL)) 
	{

	$babDB->db_query("
	
			CREATE TABLE ".BAB_DEF_TOPCATSUB_GROUPS_TBL." (
			  id int(11) unsigned NOT NULL auto_increment,
			  id_object int(11) unsigned NOT NULL default '0',
			  id_group int(11) unsigned NOT NULL default '0',
			  PRIMARY KEY  (id),
			  KEY id_object (id_object),
			  KEY id_group (id_group)
			)
		
			");
	}

	if (!bab_isTable(BAB_DEF_TOPCATVIEW_GROUPS_TBL)) 
	{

	$babDB->db_query("
	
			CREATE TABLE ".BAB_DEF_TOPCATVIEW_GROUPS_TBL." (
			  id int(11) unsigned NOT NULL auto_increment,
			  id_object int(11) unsigned NOT NULL default '0',
			  id_group int(11) unsigned NOT NULL default '0',
			  PRIMARY KEY  (id),
			  KEY id_object (id_object),
			  KEY id_group (id_group)
			)
		
			");
	}

	if(!bab_isTableField(BAB_SITES_TBL, 'show_update_info')) 
	{
		$babDB->db_query("ALTER TABLE `".BAB_SITES_TBL."` ADD show_update_info ENUM('N','Y') DEFAULT 'N' NOT NULL");
	}
	if(!bab_isTableField(BAB_CAL_USER_OPTIONS_TBL, 'show_update_info')) 
	{
		$babDB->db_query("ALTER TABLE `".BAB_CAL_USER_OPTIONS_TBL."` ADD show_update_info ENUM('N','Y') DEFAULT 'N' NOT NULL");
	}

	if(!bab_isTableField(BAB_CAL_EVENTS_TBL, 'date_modification')) 
	{
		$babDB->db_query("ALTER TABLE ".BAB_CAL_EVENTS_TBL." ADD date_modification DATETIME DEFAULT '0000-00-00 00:00:00' NOT NULL");
	}

	if(!bab_isTableField(BAB_CAL_EVENTS_TBL, 'id_modifiedby')) 
	{
		$babDB->db_query("ALTER TABLE ".BAB_CAL_EVENTS_TBL." ADD id_modifiedby INT( 11 ) UNSIGNED DEFAULT '0' NOT NULL");
	}
	
	/**
	 * Upgrade to 6.6.90
	 */


	require_once $GLOBALS['babInstallPath'] . 'utilit/eventincl.php';
	
	bab_addEventListener('bab_eventLogin', 'bab_onEventLogin', 
		'utilit/eventAuthentication.php', BAB_ADDON_CORE_NAME, 0);
	
	bab_addEventListener('bab_eventLogout', 'bab_onEventLogout', 
		'utilit/eventAuthentication.php', BAB_ADDON_CORE_NAME, 0);
		
	bab_addEventListener('bab_eventBeforeSiteMapCreated', 'bab_onBeforeSiteMapCreated', 
		'utilit/sitemap_build.php', BAB_ADDON_CORE_NAME, 0);
		
		
		
	if (!bab_isTable(BAB_SITEMAP_TBL))  {
		$babDB->db_query("
			CREATE TABLE ".BAB_SITEMAP_TBL." (
			   `id` int(11) unsigned NOT NULL auto_increment,
			   `id_parent` int(11) unsigned DEFAULT '0' NOT NULL,
			   `lf` int(11) unsigned DEFAULT '0' NOT NULL,
			   `lr` int(11) unsigned DEFAULT '0' NOT NULL,
			   `id_function` varchar(64) NOT NULL,
			   PRIMARY KEY (`id`),
			   KEY `id_parent` (`id_parent`),
			   KEY `id_function` (`id_function`),
			   KEY `lf` (`lf`),
			   KEY `lr` (`lr`)
			)
		");
	}
	
	
	if (!bab_isTable(BAB_SITEMAP_FUNCTION_PROFILE_TBL))  {
		$babDB->db_query("
			CREATE TABLE ".BAB_SITEMAP_FUNCTION_PROFILE_TBL." (
			   `id_function` varchar(64) NOT NULL,
			   `id_profile` int(11) unsigned DEFAULT '0' NOT NULL,
			   PRIMARY KEY (`id_function`, `id_profile`)
			)
		");
	}
	
	
	if (!bab_isTable(BAB_SITEMAP_FUNCTIONS_TBL))  {
		$babDB->db_query("
			CREATE TABLE ".BAB_SITEMAP_FUNCTIONS_TBL." (
			   `id_function` varchar(64) NOT NULL,
			   `url` varchar(255) NOT NULL,
			   `onclick` varchar(255) NOT NULL,
			   `folder` tinyint(1) unsigned NOT NULL default '0',
			   PRIMARY KEY (`id_function`)
			)
		");
	}
	
	
	if (!bab_isTable(BAB_SITEMAP_FUNCTION_LABELS_TBL))  {
		$babDB->db_query("
			CREATE TABLE ".BAB_SITEMAP_FUNCTION_LABELS_TBL." (
			   `id_function` varchar(64) NOT NULL,
			   `lang` varchar(32) NOT NULL,
			   `name` varchar(255) NOT NULL,
			   `description` TEXT NOT NULL,
			   PRIMARY KEY (`id_function`,`lang`)
			)
		");
	}
	
	
	if (!bab_isTable(BAB_SITEMAP_PROFILES_TBL))  {
		$babDB->db_query("
			CREATE TABLE ".BAB_SITEMAP_PROFILES_TBL." (
			   `id` int(11) unsigned NOT NULL auto_increment,
			   `uid_functions` int(11) unsigned NOT NULL,
			   PRIMARY KEY (`id`)
			)
		");
	}
	
	if (!bab_isTableField(BAB_USERS_TBL, 'id_sitemap_profile'))  {
		$babDB->db_query("ALTER TABLE `".BAB_USERS_TBL."` ADD id_sitemap_profile int(11) unsigned NOT NULL");
	}
	

	if(!bab_isTableField(BAB_FAQQR_TBL, 'date_modification')) 
	{
		$babDB->db_query("ALTER TABLE ".BAB_FAQQR_TBL." ADD date_modification DATETIME DEFAULT '0000-00-00 00:00:00' NOT NULL");
	}

	if(!bab_isTableField(BAB_FAQQR_TBL, 'id_modifiedby')) 
	{
		$babDB->db_query("ALTER TABLE ".BAB_FAQQR_TBL." ADD id_modifiedby INT( 11 ) UNSIGNED DEFAULT '0' NOT NULL");
	}
	
	if(!bab_isTableField(BAB_CAL_RESOURCES_TBL, 'availability_lock')) 
	{
		$babDB->db_query("ALTER TABLE ".BAB_CAL_RESOURCES_TBL." ADD `availability_lock` tinyint(1) unsigned default NULL");
	}

	if(!bab_isTableField(BAB_DG_GROUPS_TBL, 'iIdCategory')) 
	{
		$babDB->db_query("ALTER TABLE `".BAB_DG_GROUPS_TBL."` ADD `iIdCategory` TINYINT( 2 ) UNSIGNED NOT NULL DEFAULT '0' AFTER `id_group`");
	}
	
	if (!bab_isTable(BAB_DG_CATEGORIES_TBL))  {
		$babDB->db_query("
			CREATE TABLE ".BAB_DG_CATEGORIES_TBL." (
				`id` TINYINT (2) UNSIGNED not null AUTO_INCREMENT,
				`name` VARCHAR (60) not null,
				`description` VARCHAR (255) not null,
				`bgcolor` VARCHAR (6) not null,
				PRIMARY KEY (`id`)
			)
		");
	}
	
	
	/**
	 * Upgrade to 6.6.92
	 */

	
	require_once $GLOBALS['babInstallPath'] . 'utilit/eventincl.php';
	
	bab_removeEventListener('bab_eventLogin', 'bab_onEventLogin', 'utilit/eventAuthentication.php');
	bab_removeEventListener('bab_eventLogout', 'bab_onEventLogout', 'utilit/eventAuthentication.php');
	
	
	$oResult = $babDB->db_query('DESCRIBE `' . BAB_TSKMGR_TASKS_TBL . '` `duration`');
	if(false !== $oResult)
	{
		$aData = $babDB->db_fetch_array($oResult);
		if(is_array($aData) && array_key_exists('Type', $aData))
		{
			if($aData['Type'] != 'double(10,2) unsigned')
			{
				$babDB->db_query('ALTER TABLE `' . BAB_TSKMGR_TASKS_TBL . '` CHANGE `duration` `duration` DOUBLE( 10, 2 ) UNSIGNED NOT NULL DEFAULT \'0\'');			  
			}
		}
	}
	 
	$oResult = $babDB->db_query('DESCRIBE `' . BAB_TSKMGR_TASKS_TBL . '` `iDurationUnit`');
	if(false !== $oResult)
	{
		$aData = $babDB->db_fetch_array($oResult);
		if(!is_array($aData))
		{
			$babDB->db_query('ALTER TABLE `' . BAB_TSKMGR_TASKS_TBL . '` ADD `iDurationUnit` TINYINT( 2 ) UNSIGNED DEFAULT \'1\' NOT NULL AFTER `duration`');			  
		}
	}
	
	$ret = bab_getUpgradeLogMsg(BAB_ADDON_CORE_NAME, 'babTmTaskManagmentRuleUpgrade');
	if(false === $ret)
	{
		$sQuery = 
			'SELECT ' .
				'id iId, ' . 
				'startDate sStartDate, ' .
				'endDate sEndDate, ' .
				'plannedStartDate sPlannedStartDate, ' .
				'plannedStartDate sPlannedEndDate ' .
			'FROM ' .
				BAB_TSKMGR_TASKS_TBL;
				
		$oResult = $babDB->db_query($sQuery);
		$iNumRows = $babDB->db_num_rows($oResult);
		$iIndex = 0;
		
		while($iIndex < $iNumRows && false !== ($aDatas = $babDB->db_fetch_assoc($oResult)))
		{
			$iIndex++;
			
			//Avant le gestionnaire de projet n'utilisait jamais les dates de début et de fin plannifiée, ce qui fait que si elles sont vides toutes
			//les deux c'est que l'on utilise l'ancien système
			if('0000-00-00 00:00:00' == $aDatas['sPlannedStartDate'] && '0000-00-00 00:00:00' == $aDatas['sPlannedEndDate'])
			{
				$sQuery = 
					'UPDATE ' . 
						BAB_TSKMGR_TASKS_TBL . ' ' .
					'SET ' . ' ' .
						'`plannedStartDate` = \'' . $babDB->db_escape_string($aDatas['sStartDate']) . '\', ' .
						'`plannedEndDate` = \'' . $babDB->db_escape_string($aDatas['sEndDate']) . '\' ' .
					'WHERE ' . 
						'id = \'' . $babDB->db_escape_string($aDatas['iId']) . '\'';
						
				$babDB->db_query($sQuery);
			}
		}
		bab_setUpgradeLogMsg(BAB_ADDON_CORE_NAME, 'Before this upgrade the taskManager use the real start date and the real end date for the planned date', 'babTmTaskManagmentRuleUpgrade');
	}	
	
	
	$oResult = $babDB->db_query('DESCRIBE `' . BAB_TSKMGR_TASKS_TBL . '` `iPlannedTime`');
	if(false !== $oResult)
	{
		$aData = $babDB->db_fetch_array($oResult);
		if(!is_array($aData))
		{
			$babDB->db_query('ALTER TABLE `' . BAB_TSKMGR_TASKS_TBL . '` ADD `iPlannedTime` DOUBLE( 10, 2 ) UNSIGNED NOT NULL DEFAULT \'0\'');			  
		}
	}
	 
	$oResult = $babDB->db_query('DESCRIBE `' . BAB_TSKMGR_TASKS_TBL . '` `iPlannedTimeDurationUnit`');
	if(false !== $oResult)
	{
		$aData = $babDB->db_fetch_array($oResult);
		if(!is_array($aData))
		{
			$babDB->db_query('ALTER TABLE `' . BAB_TSKMGR_TASKS_TBL . '` ADD `iPlannedTimeDurationUnit` TINYINT( 2 ) UNSIGNED DEFAULT \'1\' NOT NULL');			  
		}
	}
	
	$oResult = $babDB->db_query('DESCRIBE `' . BAB_TSKMGR_TASKS_TBL . '` `iTime`');
	if(false !== $oResult)
	{
		$aData = $babDB->db_fetch_array($oResult);
		if(!is_array($aData))
		{
			$babDB->db_query('ALTER TABLE `' . BAB_TSKMGR_TASKS_TBL . '` ADD `iTime` DOUBLE( 10, 2 ) UNSIGNED NOT NULL DEFAULT \'0\'');			  
		}
	}
	 
	$oResult = $babDB->db_query('DESCRIBE `' . BAB_TSKMGR_TASKS_TBL . '` `iTimeDurationUnit`');
	if(false !== $oResult)
	{
		$aData = $babDB->db_fetch_array($oResult);
		if(!is_array($aData))
		{
			$babDB->db_query('ALTER TABLE `' . BAB_TSKMGR_TASKS_TBL . '` ADD `iTimeDurationUnit` TINYINT( 2 ) UNSIGNED DEFAULT \'1\' NOT NULL');			  
		}
	}
	 
	$oResult = $babDB->db_query('DESCRIBE `' . BAB_TSKMGR_TASKS_TBL . '` `iPlannedCost`');
	if(false !== $oResult)
	{
		$aData = $babDB->db_fetch_array($oResult);
		if(!is_array($aData))
		{
			$babDB->db_query('ALTER TABLE `' . BAB_TSKMGR_TASKS_TBL . '` ADD `iPlannedCost` DOUBLE( 10, 2 ) UNSIGNED NOT NULL DEFAULT \'0\'');			  
		}
	}
	 
	$oResult = $babDB->db_query('DESCRIBE `' . BAB_TSKMGR_TASKS_TBL . '` `iCost`');
	if(false !== $oResult)
	{
		$aData = $babDB->db_fetch_array($oResult);
		if(!is_array($aData))
		{
			$babDB->db_query('ALTER TABLE `' . BAB_TSKMGR_TASKS_TBL . '` ADD `iCost` DOUBLE( 10, 2 ) UNSIGNED NOT NULL DEFAULT \'0\'');			  
		}
	}
	
	$babDB->db_query('DROP TABLE `' . BAB_TSKMGR_TASK_LIST_FILTER_TBL . '`');
	 
	$oResult = $babDB->db_query('DESCRIBE `' . BAB_TSKMGR_TASKS_TBL . '` `iPriority`');
	if(false !== $oResult)
	{
		$aData = $babDB->db_fetch_array($oResult);
		if(!is_array($aData))
		{
			$babDB->db_query('ALTER TABLE `' . BAB_TSKMGR_TASKS_TBL . '` ADD `iPriority` TINYINT( 2 ) UNSIGNED NOT NULL DEFAULT \'5\'');			  
		}
	}
	
	
	
	
	
	/**
	 * Upgrade to 6.6.93
	 */
	
	
	 
	$oResult = $babDB->db_query('DESCRIBE `' . BAB_CAL_EVENTS_TBL . '` `uuid`');
	if(false !== $oResult)
	{
		$aData = $babDB->db_fetch_array($oResult);
		if(!is_array($aData))
		{
			$babDB->db_query('ALTER TABLE `' . BAB_CAL_EVENTS_TBL . '` ADD `uuid` varchar(255) NOT NULL');
	
			$sQuery = 
				'SELECT ' .
					'id iId ' . 
				'FROM ' .
					BAB_CAL_EVENTS_TBL;
					
			$oResult = $babDB->db_query($sQuery);
			$iNumRows = $babDB->db_num_rows($oResult);
			$iIndex = 0;
			
			while($iIndex < $iNumRows && false !== ($aDatas = $babDB->db_fetch_assoc($oResult)))
			{
				//Generate a pseudo-random UUID according to RFC 4122
				$sUUID = sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
					mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
					mt_rand( 0, 0x0fff ) | 0x4000,
					mt_rand( 0, 0x3fff ) | 0x8000,
					mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ) );
				
				$iIndex++;
				$sQuery = 
					'UPDATE ' . 
						BAB_CAL_EVENTS_TBL . ' ' .
					'SET ' . ' ' .
						'`uuid` = \'' . $babDB->db_escape_string($sUUID) . '\' ' .
					'WHERE ' . 
						'id = \'' . $babDB->db_escape_string($aDatas['iId']) . '\'';
						
				$babDB->db_query($sQuery);
			}
		}
	}
	

	
	// verify bab_sitemap table
	
	if(!bab_isTableField(BAB_SITEMAP_TBL, 'id_dgowner')) 
	{
		$babDB->db_query("ALTER TABLE `".BAB_SITEMAP_TBL."` ADD `id_dgowner` int(11) unsigned DEFAULT NULL");
		$babDB->db_query("ALTER TABLE `".BAB_SITEMAP_TBL."` ADD INDEX ( `id_dgowner` )");
	}
	
	
	
	
	
	
	
	/**
	 * Upgrade to 6.6.95
	 */
	if (!bab_isTable(BAB_VAC_RIGHTS_INPERIOD_TBL))  {
		$babDB->db_query("
			CREATE TABLE `".BAB_VAC_RIGHTS_INPERIOD_TBL."` (
			  `id` int(10) unsigned NOT NULL auto_increment,
			  `id_right` int(10) unsigned NOT NULL default '0',
			  `period_start` date NOT NULL default '0000-00-00',
			  `period_end` date NOT NULL default '0000-00-00',
			  `right_inperiod` tinyint(4) NOT NULL default '0',
			  PRIMARY KEY  (`id`),
			  KEY `id_right` (`id_right`)
			)
		");
		
		
		if(bab_isTableField(BAB_VAC_RIGHTS_RULES_TBL, 'period_start')) 
		{
			$res = $babDB->db_query('SELECT 
					id_right, 
					period_start, 
					period_end, 
					right_inperiod  
				FROM 
					'.BAB_VAC_RIGHTS_RULES_TBL.' 
					
				WHERE 
					(period_start<>\'0000-00-00\' OR period_end<>\'0000-00-00\')
			');
			
			while ($arr = $babDB->db_fetch_assoc($res)) {
				$babDB->db_query('INSERT INTO '.BAB_VAC_RIGHTS_INPERIOD_TBL.' 
					(id_right, period_start, period_end, right_inperiod) 
				VALUES 
					(
						'.$babDB->quote($arr['id_right']).', 
						'.$babDB->quote($arr['period_start']).',
						'.$babDB->quote($arr['period_end']).',
						'.$babDB->quote($arr['right_inperiod']).'
					)');
			}
			
			
			$babDB->db_query('ALTER TABLE '.BAB_VAC_RIGHTS_RULES_TBL.' DROP period_start');
			$babDB->db_query('ALTER TABLE '.BAB_VAC_RIGHTS_RULES_TBL.' DROP period_end');
			$babDB->db_query('ALTER TABLE '.BAB_VAC_RIGHTS_RULES_TBL.' DROP right_inperiod');
		}
	}
	
	/**
	 * Upgrade to 6.6.96
	 */

	$sQuery = 
		'SELECT 
			`id` iId,
			`created` sCreated,
			`author` iIdAuthor
		FROM ' .
			BAB_FILES_TBL;
	
	$oResultFile = $babDB->db_query($sQuery);
	if(false !== $oResultFile)
	{
		$aFileDatas = array();
		while(false !== ($aFileDatas = $babDB->db_fetch_assoc($oResultFile)))
		{
			$sQuery = 
				'SELECT 
					`action` iAction
				FROM ' .
					BAB_FM_FILESLOG_TBL . ' ' .
				'WHERE ' .
					'id_file = ' . $babDB->quote($aFileDatas['iId']) . ' AND ' . 
					'action = ' .  $babDB->quote(4);
	
			// BAB_FACTION_INITIAL_UPLOAD ==> 4
				
			$oResultFileLog = $babDB->db_query($sQuery);
			if(false !== $oResultFileLog)
			{
				$iNumRows = $babDB->db_num_rows($oResultFileLog);
				if(0 == $iNumRows)
				{
					$sQuery = 
						'INSERT INTO ' . BAB_FM_FILESLOG_TBL . ' ' .
							'(' .
								'`id`, ' .
								'`id_file`, `date`, `author`, ' .
								'`action`, `comment`, `version`' .
							') ' .
						'VALUES ' . 
							'(\'\', ' . 
								$babDB->quote($aFileDatas['iId']) . ', ' . 
								$babDB->quote($aFileDatas['sCreated']) . ', ' . 
								$babDB->quote($aFileDatas['iIdAuthor']) . ', ' . 
								$babDB->quote(4) . ', ' . 
								$babDB->quote(bab_translate("Initial upload")) . ', ' . 
								$babDB->quote('1.0') . 
							')'; 
							
					$babDB->db_query($sQuery);
				}
			}
		}
	}


	/**
	 * Upgrade to 6.6.98
	 */
	
	// The "PortalAuthentication/Ovidentia" functionality has been renamed as "PortalAuthentication/AuthOvidentia".
	// If the old version was present on the system, we remove the "PortalAuthentication" directory so that it will be recreated on next login.
	// If other authentication addons were installed they will have to be reinstalled.
	
	$portalAuthenticationPath = dirname($_SERVER['SCRIPT_FILENAME']).'/functionalities/PortalAuthentication/';
	if (is_dir($portalAuthenticationPath . 'Ovidentia/')) {
		removeDir($portalAuthenticationPath);
	}

	if(!bab_isTableField(BAB_SITES_TBL, 'iDefaultCalendarAccess')) 
	{
		$babDB->db_query('ALTER TABLE `'.BAB_SITES_TBL.'` ADD `iDefaultCalendarAccess` SMALLINT( 2 ) NOT NULL DEFAULT \'-1\' AFTER `show_update_info`');
	}

	if(!bab_isTableField(BAB_CAL_USER_OPTIONS_TBL, 'iDefaultCalendarAccess')) 
	{
		$babDB->db_query('ALTER TABLE `'.BAB_CAL_USER_OPTIONS_TBL.'` ADD `iDefaultCalendarAccess` SMALLINT( 2 ) NULL DEFAULT NULL AFTER `show_update_info`');
	}
	
	if (!bab_isTableField(BAB_SITES_TBL, 'mail_fieldaddress')) {
		$babDB->db_query("ALTER TABLE ".BAB_SITES_TBL." ADD mail_fieldaddress char(3) DEFAULT 'Bcc' NOT NULL");
	}

	if (!bab_isTableField(BAB_SITES_TBL, 'mail_maxperpacket')) {
		$babDB->db_query("ALTER TABLE ".BAB_SITES_TBL." ADD mail_maxperpacket smallint(2) UNSIGNED NOT NULL default 25");
	}
	
	tskMgrFieldOrderUpgrade();
	
	return true;
}
