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
require_once 'base.php';




class BAB_BaseSet extends BAB_MySqlResultIterator
{
	var $aField = array();
	var $sTableName = '';
	var $bUseAlias = true;

	function BAB_BaseSet($sTableName)
	{
		parent::BAB_MySqlResultIterator();
		$this->sTableName = $sTableName;
	}

	function processWhereClause($oCriteria)
	{
		//		require_once $GLOBALS['babInstallPath'] . 'utilit/devtools.php';
		//		bab_debug_print_backtrace();
		if(!is_null($oCriteria))
		{
			$sWhereClause = $oCriteria->toString();
			if(mb_strlen(trim($sWhereClause)) > 0)
			{
				return 'WHERE ' . $sWhereClause;
			}
		}
		return '';
	}

	function processOrder($aOrder)
	{
		$sOrder = '';
		if(count($aOrder) > 0)
		{
			$aValue = array();
			foreach($aOrder as $sKey => $sValue)
			{
				$aValue[] = $this->aField[$sKey]->getName() . ' ' . $sValue;
			}
			$sOrder = 'ORDER BY ' . implode(', ', $aValue);
		}
		return $sOrder;
	}

	function processLimit($aLimit)
	{
		$sLimit = '';
		$iCount = count($aLimit);
		if($iCount >= 1 && $iCount <= 2)
		{
			global $babDB;

			$aValue = array();
			foreach($aLimit as $sValue)
			{
				$aValue[] = $babDB->db_escape_string($sValue);
			}
			$sLimit = 'LIMIT ' . implode(', ', $aValue);
		}
		return $sLimit;
	}

	function save(&$oObject)
	{
		if(count($this->aField) > 0)
		{
			global $babDB;

			$aInto = array();
			$aValue = array();
			$aOnDuplicateKey = array();

			reset($this->aField);
			//Primary key processing
			$aItem = each($this->aField);
			if(false !== $aItem)
			{
				$aInto[] = $aItem['value']->getName();
				$oObject->_get($aItem['key'], $iId);
				$aValue[] = (is_null($iId)) ? '\'\'' : '\'' . $babDB->db_escape_string($iId) . '\'';
			}

			while(false !== ($aItem = each($this->aField)))
			{
				$sColName = $aItem['value']->getName();
				$aInto[] = $sColName;

				$sKey = $aItem['key'];
				$oObject->_get($sKey, $sValue);

				$sValue = '\'' . $babDB->db_escape_string($sValue) . '\'';
				$aValue[] = $sValue;
				$aOnDuplicateKey[] = $sColName . '= ' . $sValue;
			}
			reset($this->aField);

			$sQuery =
			'INSERT INTO ' . $this->sTableName . ' ' .
			'(' . implode(',', $aInto) . ') ' .
			'VALUES ' .
			'(' . implode(',', $aValue) . ') ';
			
//			bab_debug($sQuery);
			$oResult = $babDB->db_queryWem($sQuery);
			if(false !== $oResult)
			{
				$oObject->_get('iId', $iId);
				if(is_null($iId))
				{
					$oObject->_set('iId', $babDB->db_insert_id());
				}
				return true;
			}
			else 
			{
				$sQuery = 
					'UPDATE ' . 
						$this->sTableName . ' ' .
					'SET ' .
						implode(',', $aOnDuplicateKey) .
					'WHERE ' . $this->aField['iId']->getName() . ' =\'' . $iId . '\'';
			
//				bab_debug($sQuery);
				$oResult = $babDB->db_queryWem($sQuery);
				return (false !== $oResult);
			}
			
			//En MySql 3.23 cela ne marche pas
			/*
			$sQuery =
			'INSERT INTO ' . $this->sTableName . ' ' .
			'(' . implode(',', $aInto) . ') ' .
			'VALUES ' .
			'(' . implode(',', $aValue) . ') ' .
			'ON DUPLICATE KEY UPDATE ' .
			implode(',', $aOnDuplicateKey);
			
//			bab_debug($sQuery);
			$oResult = $babDB->db_query($sQuery);
			if(false !== $oResult)
			{
				$oObject->_get('iId', $iId);
				if(is_null($iId))
				{
					$oObject->_set('iId', $babDB->db_insert_id());
				}
				return true;
			}
			return false;
		//*/
		}
	}

	function remove($oCriteria)
	{
		$sWhereClause = $this->processWhereClause($oCriteria);
		if(mb_strlen($sWhereClause) > 0)
		{
			global $babDB;
			$sQuery = 'DELETE FROM ' . $this->sTableName . ' ' . $sWhereClause;
//			bab_debug($sQuery);
			return $babDB->db_query($sQuery);
		}
		return false;
	}


	function getSelectQuery($oCriteria = null, $aOrder = array(), $aLimit = array())
	{
		$sWhereClause = $this->processWhereClause($oCriteria);
		$sOrder = $this->processOrder($aOrder);
		$sLimit = $this->processLimit($aLimit);

		$aField = array();

		if(true === $this->bUseAlias)
		{
			foreach($this->aField as $sKey => $oField)
			{
				$aField[] = $oField->getName() . ' ' . $sKey;
			}
		}
		else
		{
			foreach($this->aField as $sKey => $oField)
			{
				$aField[] = $oField->getName() . ' ';
			}
		}

		$sQuery =
		'SELECT ' .
		implode(', ', $aField) . ' ' .
		'FROM ' .
		$this->sTableName . ' ' .
		$sWhereClause . ' ' . $sOrder . ' ' . $sLimit;

//		bab_debug($sQuery);
		return $sQuery;
	}

	function get($oCriteria = null)
	{
		//		require_once $GLOBALS['babInstallPath'] . 'utilit/devtools.php';
		//		bab_debug_print_backtrace();

		$sQuery = $this->getSelectQuery($oCriteria);

		global $babDB;
		$oResult = $babDB->db_query($sQuery);
		$iNumRows = $babDB->db_num_rows($oResult);
		$iIndex = 0;

		if($iIndex < $iNumRows)
		{
			$this->setMySqlResult($oResult);
			return $this->next();
		}
		return null;
	}

	function select($oCriteria = null, $aOrder = array(), $aLimit = array())
	{
//		require_once $GLOBALS['babInstallPath'] . 'utilit/devtools.php';
//		bab_debug_print_backtrace();

		$sQuery = $this->getSelectQuery($oCriteria, $aOrder, $aLimit);
		
		global $babDB;
		$oResult = $babDB->db_query($sQuery);
		$this->setMySqlResult($oResult);
		return $this;
	}

	function getObject($aDatas)
	{
		$sClass = mb_substr(get_class($this), 0, -3);
		$oOBject = new $sClass();

		foreach($aDatas as $sKey => $sValue)
		{
			$oOBject->_set($sKey, $sValue);
		}
		return $oOBject;
	}
}


class BAB_FmFolderSet extends BAB_BaseSet
{
	function BAB_FmFolderSet()
	{
		parent::BAB_BaseSet(BAB_FM_FOLDERS_TBL);

		$this->aField = array(
			'iId' => new BAB_IntField('`id`'),
			'sName' => new BAB_StringField('`folder`'),
			'sRelativePath' => new BAB_StringField('`sRelativePath`'),
			'iIdApprobationScheme' => new BAB_IntField('`idsa`'),
			'sFileNotify' => new BAB_StringField('`filenotify`'),
			'sActive' => new BAB_IntField('`active`'),
			'sVersioning' => new BAB_StringField('`version`'),
			'iIdDgOwner' => new BAB_IntField('`id_dgowner`'),
			'sHide' => new BAB_StringField('`bhide`'),
			'sAddTags' => new BAB_StringField('`baddtags`'),
			'sAutoApprobation' => new BAB_StringField('`auto_approbation`'),
			'sDownloadsCapping' => new BAB_StringField('`bcap_downloads`'),
			'iMaxDownloads' => new BAB_IntField('`max_downloads`'),
			'sDownloadHistory' => new BAB_StringField('`bdownload_history`')
		);
	}

	function remove($oCriteria, $bDbRecordOnly)
	{
		$this->select($oCriteria);

		while(null !== ($oFmFolder = $this->next()))
		{
			$this->delete($oFmFolder, $bDbRecordOnly);
		}
	}

	function delete($oFmFolder, $bDbRecordOnly)
	{
		if(is_a($oFmFolder, 'BAB_FmFolder'))
		{
			$oFileManagerEnv =& getEnvObject();
			
			require_once $GLOBALS['babInstallPath'].'admin/acl.php';
			aclDelete(BAB_FMUPLOAD_GROUPS_TBL, $oFmFolder->getId());
			aclDelete(BAB_FMDOWNLOAD_GROUPS_TBL, $oFmFolder->getId());
			aclDelete(BAB_FMUPDATE_GROUPS_TBL, $oFmFolder->getId());
			aclDelete(BAB_FMMANAGERS_GROUPS_TBL, $oFmFolder->getId());

			$oFolderFileSet = new BAB_FolderFileSet();
			if(true === $bDbRecordOnly)
			{
				if(mb_strlen(trim($oFmFolder->getRelativePath())) > 0)
				{
					$oFirstFmFolder = BAB_FmFolderSet::getFirstCollectiveFolder($oFmFolder->getRelativePath());
					$sPathName = $oFmFolder->getRelativePath() . $oFmFolder->getName() . '/';
					$oFolderFileSet->setOwnerId($sPathName, $oFmFolder->getId(), $oFirstFmFolder->getId());
					
					$oFmFolderCliboardSet = new BAB_FmFolderCliboardSet();
					$oFmFolderCliboardSet->setOwnerId($sPathName, $oFmFolder->getId(), $oFirstFmFolder->getId());
				}
				
				$sUploadPath = $oFileManagerEnv->getCollectiveRootFmPath();

				$oIdOwner =& $oFolderFileSet->aField['iIdOwner'];
				$oPathName =& $oFolderFileSet->aField['sPathName'];
				$oIdDgOwner =& $oFolderFileSet->aField['iIdDgOwner'];
				
				$oCriteria = $oIdDgOwner->in(bab_getCurrentUserDelegation());
				$oCriteria = $oCriteria->_and($oIdOwner->in($oFirstFmFolder->getId()));
				$oCriteria = $oCriteria->_and($oPathName->in($oFmFolder->getRelativePath() . $oFmFolder->getName() . '/'));

				$oFolderFileSet->select($oCriteria);

				$oFolderFileVersionSet = new BAB_FolderFileVersionSet();
				while(null !== ($oFolderFile = $oFolderFileSet->next()))
				{
					$oIdFile =& $oFolderFileVersionSet->aField['iIdFile'];
					$oFolderFileVersionSet->remove($oIdFile->in($oFolderFile->getId()),
					$sUploadPath . $oFolderFile->getPathName(), $oFolderFile->getName());

					require_once $GLOBALS['babInstallPath'].'utilit/afincl.php';
					deleteFlowInstance($oFolderFile->getFlowApprobationInstanceId());
					$oFolderFile->setMajorVer(1);
					$oFolderFile->setMinorVer(0);
					$oFolderFile->setFlowApprobationInstanceId(0);
					$oFolderFile->setConfirmed('Y');
					$oFolderFile->save();
				}
			}
			else if(false === $bDbRecordOnly)
			{
				global $babDB, $babBody;
				
				$oPathName =& $oFolderFileSet->aField['sPathName'];
				$oIdDgOwner =& $oFolderFileSet->aField['iIdDgOwner'];
				
				$oCriteria = $oIdDgOwner->in(bab_getCurrentUserDelegation());
				$oCriteria = $oCriteria->_and($oPathName->like($babDB->db_escape_like($oFmFolder->getRelativePath() . $oFmFolder->getName() . '/') . '%'));
				$oFolderFileSet->remove($oCriteria);
				
				$sRootFmPath = BAB_FileManagerEnv::getCollectivePath($oFmFolder->getDelegationOwnerId());
				$sFullPathName = $sRootFmPath . $oFmFolder->getRelativePath() . $oFmFolder->getName();
				$this->removeDir($sFullPathName);

				$oFmFolderCliboardSet = new BAB_FmFolderCliboardSet();
				$oFmFolderCliboardSet->deleteFolder($oFmFolder->getName(), $oFmFolder->getRelativePath(), 'Y');
			}

			$oId =& $this->aField['iId'];
			return parent::remove($oId->in($oFmFolder->getId()));
		}
	}
	
	
	function save(&$oFmFolder)
	{
		if(is_a($oFmFolder, 'BAB_FmFolder'))
		{
			return parent::save($oFmFolder);
		}
	}


	//--------------------------------------
	function getFirstCollectiveParentFolder($sRelativePath)
	{
		return BAB_FmFolderSet::getFirstCollectiveFolder(removeLastPath($sRelativePath));
	}

	function getFirstCollectiveFolder($sRelativePath)
	{
		global $babBody;
		
		$aPath = explode('/', $sRelativePath);
		if(is_array($aPath))
		{
			$iLength = count($aPath);
			if($iLength >= 1)
			{
				$bStop		= false;
				$iIndex		= $iLength - 1;
				$bFinded	= false;
				global $babDB;

				$oFmFolderSet = new BAB_FmFolderSet();
				$oRelativePath =& $oFmFolderSet->aField['sRelativePath'];
				$oIdDgOwner =& $oFmFolderSet->aField['iIdDgOwner'];
				$oName =& $oFmFolderSet->aField['sName'];

				do
				{
					$sFolderName = $aPath[$iIndex];
					unset($aPath[$iIndex]);
					$sRelativePath	= implode('/', $aPath);

					if('' !== $sRelativePath)
					{
						$sRelativePath .= '/';
					}

					$oCriteria = $oRelativePath->like($babDB->db_escape_like($sRelativePath));
					$oCriteria = $oCriteria->_and($oName->in($sFolderName));
					$oCriteria = $oCriteria->_and($oIdDgOwner->in(bab_getCurrentUserDelegation()));
					$oFmFolder = $oFmFolderSet->get($oCriteria);
					if(!is_null($oFmFolder))
					{
						return $oFmFolder;
					}

					if($iIndex > 0)
					{
						$iIndex--;
					}
					else
					{
						$bStop = true;
					}
				}
				while(false === $bStop);
			}
		}
		return null;
	}
	
	function getRootCollectiveFolder($sRelativePath)
	{
		global $babBody;
		
		$aPath = explode('/', $sRelativePath);
		if(is_array($aPath))
		{
			$iLength = count($aPath);
			if($iLength >= 1)
			{
				$sName = $aPath[0];

				$oFmFolderSet = new BAB_FmFolderSet();
				$oRelativePath =& $oFmFolderSet->aField['sRelativePath'];
				$oName =& $oFmFolderSet->aField['sName'];
				$oIdDgOwner =& $oFmFolderSet->aField['iIdDgOwner'];

				global $babDB;
				$oCriteria = $oRelativePath->like($babDB->db_escape_like(''));
				$oCriteria = $oCriteria->_and($oName->in($sName));
				$oCriteria = $oCriteria->_and($oIdDgOwner->in(bab_getCurrentUserDelegation()));
				$oFmFolder = $oFmFolderSet->get($oCriteria);

				if(!is_null($oFmFolder))
				{
					return $oFmFolder;
				}
			}
		}
		return null;
	}

	/**
	 * Recusively deletes a folder.
	 *
	 * @param string $sFullPathName
	 * @static
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
							BAB_FmFolderSet::removeDir($sFullPathName . '/' . $sName);
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
	
	function removeSimpleCollectiveFolder($sRelativePath)
	{
		require_once $GLOBALS['babInstallPath'].'utilit/pathUtil.class.php';
			
		//1 Chercher tous les repertoires collectifs
		//2 Supprimer les repertoires collectifs
		//3 Lister le contenu du repertoire a supprimer
		//4 Pour chaque repertoire rappeler la fonction deleteSimpleCollectiveFolder
		//5 Supprimer le repertoire
		
		global $babBody, $babDB;
	
		$oFileManagerEnv	=& getEnvObject();
		$sUplaodPath		= BAB_PathUtil::addEndSlash(BAB_PathUtil::sanitize($oFileManagerEnv->getRootFmPath()));
	
		$bDbRecordOnly	= false;
		$oFmFolderSet	= new BAB_FmFolderSet();
		$oRelativePath	=& $oFmFolderSet->aField['sRelativePath'];
		$oIdDgOwner		=& $oFmFolderSet->aField['iIdDgOwner'];
		$oName			=& $oFmFolderSet->aField['sName'];
		
		$sRelativePath = BAB_PathUtil::addEndSlash(BAB_PathUtil::sanitize($sRelativePath));
		
		$oCriteria = $oIdDgOwner->in(bab_getCurrentUserDelegation());
		$oCriteria = $oCriteria->_and($oRelativePath->like($babDB->db_escape_like($sRelativePath)));
	
		$oFmFolderSet->select($oCriteria);
		if($oFmFolderSet->count() > 0)
		{
			while( null !== ($oFolder = $oFmFolderSet->next()) )
			{
				require_once $GLOBALS['babInstallPath'] . 'utilit/delincl.php';
				bab_deleteFolder($oFolder->getId());
			}
		}
	
		$sFullPathName = $sUplaodPath . $sRelativePath;
	
		if(is_dir($sFullPathName))
		{
			$oFolderFileSet = new BAB_FolderFileSet();
			$oName =& $oFolderFileSet->aField['sName'];
			$oPathName =& $oFolderFileSet->aField['sPathName'];
			$oIdDgOwnerFile =& $oFolderFileSet->aField['iIdDgOwner'];
	
			$oDir = dir($sFullPathName);
			while(false !== ($sEntry = $oDir->read())) 
			{
				if($sEntry == '.' || $sEntry == '..')
				{
					continue;
				}
				else
				{
					$sFullPathName = $sUplaodPath . $sRelativePath . $sEntry;
	
					if(is_dir($sFullPathName)) 
					{
						$this->removeSimpleCollectiveFolder($sRelativePath . $sEntry . '/');	
					}
					else if(is_file($sFullPathName))
					{
						$oCriteria = $oName->in($sEntry);
						$oCriteria = $oCriteria->_and($oPathName->in($sRelativePath));
						$oCriteria = $oCriteria->_and($oIdDgOwnerFile->in(bab_getCurrentUserDelegation()));
	
						$oFolderFileSet->remove($oCriteria);
					}
				}
			}
			$oDir->close();
			rmdir($sUplaodPath . $sRelativePath);
			
			$sName			= getLastPath($sRelativePath);
			$sRelativePath	= removeLastPath($sRelativePath); 
			if('' != $sRelativePath)
			{
				$sRelativePath = BAB_PathUtil::addEndSlash($sRelativePath); 
			}
				
			$oFmFolderCliboardSet = bab_getInstance('BAB_FmFolderCliboardSet');
			$oFmFolderCliboardSet->deleteFolder($sName, $sRelativePath, 'Y');
		}
	}
		
	function rename($sUploadPath, $sRelativePath, $sOldName, $sNewName)
	{
		if(BAB_FmFolderHelper::renameDirectory($sUploadPath, $sRelativePath, $sOldName, $sNewName))
		{
			global $babBody, $babBody;
			
			$sOldRelativePath = $sRelativePath . $sOldName . '/';
			$sNewRelativePath = $sRelativePath . $sNewName . '/';

			global $babDB;
			$oFmFolderSet = new BAB_FmFolderSet();
			$oRelativePath =& $oFmFolderSet->aField['sRelativePath'];
			$oIdDgOwner =& $oFmFolderSet->aField['iIdDgOwner'];
			
			$oCriteria = $oIdDgOwner->in(bab_getCurrentUserDelegation());
			$oCriteria = $oRelativePath->like($babDB->db_escape_like($sOldRelativePath) . '%');
			
			$oFmFolderSet = $oFmFolderSet->select($oCriteria);
			while(null !== ($oFmFolder = $oFmFolderSet->next()))
			{
				$sRelPath = $sNewRelativePath . mb_substr($oFmFolder->getRelativePath(), mb_strlen($sOldRelativePath));
				$oFmFolder->setRelativePath($sRelPath);
				$oFmFolder->save();
			}
			return true;
		}
		return false;
	}
	
	
	function move($sUploadPath, $sOldRelativePath, $sNewRelativePath)
	{
		$sSrc = removeEndSlah($sUploadPath . $sOldRelativePath);
		$sTrg = removeEndSlah($sUploadPath . $sNewRelativePath);
		
		if(rename($sSrc, $sTrg))
		{
			global $babBody, $babDB;
			$oFmFolderSet = new BAB_FmFolderSet();
			
			$oIdDgOwner		=& $oFmFolderSet->aField['iIdDgOwner'];
			$oName			=& $oFmFolderSet->aField['sName'];
			$oRelativePath	=& $oFmFolderSet->aField['sRelativePath'];

			//1 changer le repertoire
			$sName = getLastPath($sOldRelativePath);
			$sRelativePath = removeLastPath($sOldRelativePath);
			$sRelativePath .= (mb_strlen(trim($sRelativePath)) !== 0 ) ? '/' : '';
			
			$oCriteria = $oIdDgOwner->in(bab_getCurrentUserDelegation());
			$oCriteria = $oCriteria->_and($oName->in($sName));
			$oCriteria = $oCriteria->_and($oRelativePath->in($sRelativePath));
			
			$oFmFolder = $oFmFolderSet->get($oCriteria);
			if(!is_null($oFmFolder))
			{
				$sNewRelPath = removeLastPath($sNewRelativePath);
				$sNewRelPath .= (mb_strlen(trim($sNewRelPath)) !== 0 ) ? '/' : '';
				$oFmFolder->setRelativePath($sNewRelPath);
				$oFmFolder->save();
			}
			
			//2 changer les sous repertoires
			$oCriteria = $oRelativePath->like($babDB->db_escape_like($sOldRelativePath) . '%');
			$oCriteria = $oCriteria->_and($oIdDgOwner->in($babBody->currentAdmGroup));
			
			$oFmFolderSet->select($oCriteria);
			while(null !== ($oFmFolder = $oFmFolderSet->next()))
			{
				$sNewRelativePath = $sNewRelativePath . mb_substr($oFmFolder->getRelativePath(), mb_strlen($sOldRelativePath));
				$oFmFolder->setRelativePath($sNewRelativePath);
				$oFmFolder->save();
			}
			return true;
		}
		return false;
	}
}


class BAB_FmFolderCliboardSet extends BAB_BaseSet
{
	function BAB_FmFolderCliboardSet()
	{
		parent::BAB_BaseSet(BAB_FM_FOLDERS_CLIPBOARD_TBL);

		$this->aField = array(
			'iId' => new BAB_IntField('`iId`'),
			'iIdDgOwner' => new BAB_IntField('`iIdDgOwner`'),
			'iIdRootFolder' => new BAB_IntField('`iIdRootFolder`'),
			'iIdFolder' => new BAB_IntField('`iIdFolder`'),
			'sName' => new BAB_StringField('`sName`'),
			'sRelativePath' => new BAB_StringField('`sRelativePath`'),
			'sGroup' => new BAB_StringField('`sGroup`'),
			'sCollective' => new BAB_StringField('`sCollective`'),
			'iIdOwner' => new BAB_IntField('`iIdOwner`'),
			'sCheckSum' => new BAB_IntField('`sCheckSum`')
		);
	}
	

	function rename($sRelativePath, $sOldName, $sNewName, $sGr)
	{
		$iOffset = 2;
		
		global $babBody, $babDB, $BAB_SESS_USERID;
		
		$oFileManagerEnv =& getEnvObject();
		
		$oFmFolderCliboardSet = new BAB_FmFolderCliboardSet();
		$oRelativePath	=& $oFmFolderCliboardSet->aField['sRelativePath'];
		$oIdDgOwner		=& $oFmFolderCliboardSet->aField['iIdDgOwner'];
		$oGroup			=& $oFmFolderCliboardSet->aField['sGroup'];
		$oIdOwner		=& $oFmFolderCliboardSet->aField['iIdOwner'];
		$oName			=& $oFmFolderCliboardSet->aField['sName'];
		
		$oCriteria = $oRelativePath->like($babDB->db_escape_like($sRelativePath . $sOldName . '/') . '%');
		
		$oCriteria = $oCriteria->_and($oGroup->in($sGr));
		if('N' === $sGr)
		{
			$oCriteria = $oCriteria->_and($oIdOwner->in($BAB_SESS_USERID));
			$oCriteria = $oCriteria->_and($oIdDgOwner->in(0));
		}
		else 
		{
			$oCriteria = $oCriteria->_and($oIdDgOwner->in(bab_getCurrentUserDelegation()));
		}
		
		$oFmFolderCliboardSet->select($oCriteria);

		$sRelPath = $sRelativePath . $sOldName . '/';
		$iLength = mb_strlen(trim($sRelPath));
		
		while(null !== ($oFmFolderCliboard = $oFmFolderCliboardSet->next()))
		{
			$sBegin = mb_substr($oFmFolderCliboard->getRelativePath(), 0, $iLength);
			$sEnd = (string) mb_substr($oFmFolderCliboard->getRelativePath(), mb_strlen($sBegin), mb_strlen($oFmFolderCliboard->getRelativePath()));

			$aPath = explode('/', $sBegin);
			if(is_array($aPath))
			{
				$sNewRelativePath = '';

				$iCount = count($aPath);
				if($iCount >= $iOffset)
				{
					$aPath[$iCount - $iOffset] = $sNewName;
					$sNewRelativePath = implode('/', $aPath) . $sEnd;
				}

				$oFmFolderCliboard->setRelativePath($sNewRelativePath);
				$oFmFolderCliboard->save();
			}
		}
	}
	
	function setOwnerId($sPathName, $iOldIdOwner, $iNewIdOwner)
	{
		global $babBody, $babDB;
		$oRelativePath =& $this->aField['sRelativePath'];
		$oIdOwner =& $this->aField['iIdOwner'];

		$oCriteria = $oRelativePath->like($babDB->db_escape_like($sPathName) . '%');
		$oCriteria = $oCriteria->_and($oIdOwner->in($iOldIdOwner));
		$this->select($oCriteria);

		while(null !== ($oFmFolderCliboard = $this->next()))
		{
			$oFmFolderCliboard->setOwnerId($iNewIdOwner);
			$oFmFolderCliboard->save();
		}
	}
	
	function deleteEntry($sName, $sRelativePath, $sGroup)
	{
		$oIdDgOwner		=& $this->aField['iIdDgOwner'];
		$oGroup 		=& $this->aField['sGroup'];
		$oName 			=& $this->aField['sName'];
		$oRelativePath	=& $this->aField['sRelativePath'];
		
		$iDelegation = ('Y' === $sGroup) ? bab_getCurrentUserDelegation() : 0;
		
		global $babBody;
		$oCriteria = $oIdDgOwner->in($iDelegation);
		$oCriteria = $oCriteria->_and($oGroup->in($sGroup));
		$oCriteria = $oCriteria->_and($oName->in($sName));
		$oCriteria = $oCriteria->_and($oRelativePath->in($sRelativePath));
		
		$this->remove($oCriteria);
	}
	
	function deleteFolder($sName, $sRelativePath, $sGroup)
	{
		$oIdDgOwner =& $this->aField['iIdDgOwner'];
		$oIdOwner =& $this->aField['iIdOwner'];
		$oName =& $this->aField['sName'];
		$oRelativePath =& $this->aField['sRelativePath'];
		$oGroup =& $this->aField['sGroup'];
		
		$iDelegation = ('Y' === $sGroup) ? bab_getCurrentUserDelegation() : 0;
		
		global $babBody, $babDB;
		$oCriteria = $oIdDgOwner->in($iDelegation);
		$oCriteria = $oCriteria->_and($oRelativePath->like($babDB->db_escape_like($sRelativePath . $sName . '/') . '%'));
		$oCriteria = $oCriteria->_and($oGroup->in('Y'));
		$this->remove($oCriteria);
		
		$oCriteria = $oCriteria->_and($oName->in($sName));
		$oCriteria = $oIdDgOwner->in($iDelegation);
		$oCriteria = $oCriteria->_and($oRelativePath->like($babDB->db_escape_like($sRelativePath)));
		$oCriteria = $oCriteria->_and($oGroup->in('Y'));
		$this->remove($oCriteria);
	}
	
	function move($sOldRelativePath, $sNewRelativePath, $sGr)
	{
		$oIdDgOwner		=& $this->aField['iIdDgOwner'];
		$oIdOwner		=& $this->aField['iIdOwner'];
		$oGroup 		=& $this->aField['sGroup'];
		$oName 			=& $this->aField['sName'];
		$oRelativePath	=& $this->aField['sRelativePath'];
		
		global $babBody, $babDB, $BAB_SESS_USERID;
		
		$oCriteria = $oRelativePath->like($babDB->db_escape_like($sOldRelativePath) . '%');
		$oCriteria = $oCriteria->_and($oGroup->in($sGr));
		if('N' === $sGr)
		{
			$oCriteria = $oCriteria->_and($oIdOwner->in($BAB_SESS_USERID));
			$oCriteria = $oCriteria->_and($oIdDgOwner->in(0));
		}
		else 
		{
			$oCriteria = $oCriteria->_and($oIdDgOwner->in(bab_getCurrentUserDelegation()));
		}
		
		$aProcessedPath = array();
		$iIdRootFolder = 0;
		$this->select($oCriteria);
		while(null !== ($oFmFolderCliboard = $this->next()))
		{
			$sOldRelPath = $oFmFolderCliboard->getRelativePath();
			$sNewRelPath = $sNewRelativePath . mb_substr($sOldRelPath, mb_strlen($sOldRelativePath));
			
			if(false === array_key_exists($sNewRelPath, $aProcessedPath))
			{
				if('Y' === $sGr)
				{
					$_oFmFolder = null;
					BAB_FmFolderHelper::getInfoFromCollectivePath($sNewRelPath, $iIdRootFolder, $_oFmFolder);							
					$iIdOwner = $_oFmFolder->getId();
				}
				else 
				{
					$iIdOwner = $BAB_SESS_USERID;
				}
				$aProcessedPath[$sNewRelPath] = array('iIdRootFolder' => $iIdRootFolder, 'iIdOwner' => $iIdOwner);
			}
			$oFmFolderCliboard->setRelativePath($sNewRelPath);
			$oFmFolderCliboard->setOwnerId($aProcessedPath[$sNewRelPath]['iIdOwner']);
			$oFmFolderCliboard->setRootFolderId($aProcessedPath[$sNewRelPath]['iIdRootFolder']);
			$this->save($oFmFolderCliboard);
		}
	}
}

class BAB_FolderFileSet extends BAB_BaseSet
{
	function BAB_FolderFileSet()
	{
		parent::BAB_BaseSet(BAB_FILES_TBL);

		$this->aField = array(
			'iId' => new BAB_IntField('`id`'),
			'sName' => new BAB_StringField('`name`'),
			'sDescription' => new BAB_StringField('`description`'),
			'sPathName' => new BAB_StringField('`path`'),
			'iIdOwner' => new BAB_IntField('`id_owner`'),
			'sGroup' => new BAB_StringField('`bgroup`'),
			'iIdLink' => new BAB_IntField('`link`'),
			'sReadOnly' => new BAB_StringField('`readonly`'),
			'sState' => new BAB_StringField('`state`'),
			'sCreation' => new BAB_StringField('`created`'),
			'iIdAuthor' => new BAB_IntField('`author`'),
			'sModified' => new BAB_StringField('`modified`'),
			'iIdModifier' => new BAB_IntField('`modifiedby`'),
			'sConfirmed' => new BAB_StringField('`confirmed`'),
			'iHits' => new BAB_IntField('`hits`'),
			'iMaxDownloads' => new BAB_IntField('`max_downloads`'),
			'iDownloads' => new BAB_IntField('`downloads`'),
			'iIdFlowApprobationInstance' => new BAB_IntField('`idfai`'),
			'iIdFolderFileVersion' => new BAB_IntField('`edit`'),
			'iVerMajor' => new BAB_IntField('`ver_major`'),
			'iVerMinor' => new BAB_IntField('`ver_minor`'),
			'sVerComment' => new BAB_StringField('`ver_comment`'),
			'iIndexStatus' => new BAB_IntField('`index_status`'),
			'iIdDgOwner' => new BAB_IntField('`iIdDgOwner`')
		);
	}

	/**
	 * Loads a file using its id.
	 * 
	 * @param int $iFileId
	 * @return BAB_FolderFile
	 */
	function getById($iFileId)
	{
		$oFolderFileSet = new BAB_FolderFileSet();
		$oId = $oFolderFileSet->aField['iId'];
		$oCriteria = $oId->in($iFileId);
		$file = $oFolderFileSet->get($oCriteria);

		return $file;
	}

	function remove($oCriteria)
	{
		$oFileManagerEnv =& getEnvObject();
		
		$sUploadPath = $oFileManagerEnv->getRootFmPath();

		$this->select($oCriteria);

		$aIdFile = array();

		$oFolderFileVersionSet = new BAB_FolderFileVersionSet();
		$oId =& $oFolderFileVersionSet->aField['iIdFile'];

		while(null !== ($oFolderFile = $this->next()))
		{
			$sFullPathName = $sUploadPath . $oFolderFile->getPathName() . $oFolderFile->getName();
			if(file_exists($sFullPathName))
			{
				unlink($sFullPathName);
			}

			$oFolderFileVersionSet->remove($oId->in($oFolderFile->getId()),
			$sUploadPath . $oFolderFile->getPathName(), $oFolderFile->getName());

			if(0 !== $oFolderFile->getFlowApprobationInstanceId())
			{
				include_once $GLOBALS['babInstallPath']."utilit/afincl.php";
				deleteFlowInstance($oFolderFile->getFlowApprobationInstanceId());
			}

			$aIdFile[] = $oFolderFile->getId();
		}

		$oFolderFileLogSet = new BAB_FolderFileLogSet();
		$oId =& $oFolderFileLogSet->aField['iIdFile'];
		$oFolderFileLogSet->remove($oId->in($aIdFile));

		$oFolderFileFieldValueSet = new BAB_FolderFileFieldValueSet();
		$oId =& $oFolderFileFieldValueSet->aField['iIdFile'];
		$oFolderFileFieldValueSet->remove($oId->in($aIdFile));

		parent::remove($oCriteria);
	}

	function removeVersions($oCriteria)
	{
		$oFileManagerEnv = getEnvObject();
		$sUploadPath = $oFileManagerEnv->getCollectiveRootFmPath();
		
		$this->select($oCriteria);
			
		$oFolderFileLogSet = new BAB_FolderFileLogSet();
		$oIdVersion =& $oFolderFileLogSet->aField['iIdFile'];

		$oFolderFileVersionSet = new BAB_FolderFileVersionSet();
		$oId =& $oFolderFileVersionSet->aField['iIdFile'];
		
		while(null !== ($oFolderFile = $this->next()))
		{
			$oFolderFileVersionSet->remove($oId->in($oFolderFile->getId()),
				$sUploadPath . $oFolderFile->getPathName(), $oFolderFile->getName());

			if(0 !== $oFolderFile->getFlowApprobationInstanceId())
			{
				include_once $GLOBALS['babInstallPath']."utilit/afincl.php";
				deleteFlowInstance($oFolderFile->getFlowApprobationInstanceId());
			}
			
			$oFolderFileLogSet->remove($oIdVersion->in($oFolderFile->getId()));
		}
	}
	
	function setOwnerId($sPathName, $iOldIdOwner, $iNewIdOwner)
	{
		$oPathName =& $this->aField['sPathName'];
		$oIdOwner =& $this->aField['iIdOwner'];

		global $babDB;
		$oCriteria = $oPathName->like($babDB->db_escape_like($sPathName) . '%');
		$oCriteria = $oCriteria->_and($oIdOwner->in($iOldIdOwner));
		$this->select($oCriteria);

		while(null !== ($oFolderFile = $this->next()))
		{
			$oFolderFile->setOwnerId($iNewIdOwner);
			$oFolderFile->save();
		}
	}

	function renameFolder($sRelativePath, $sNewName, $sGr)
	{
		$iOffset = 2; //pour le slash a la fin
		
		global $babBody, $babDB, $BAB_SESS_USERID;
		
		$oFolderFileSet	= new BAB_FolderFileSet();
		$oPathName		=& $oFolderFileSet->aField['sPathName'];
		$oIdDgOwner		=& $oFolderFileSet->aField['iIdDgOwner'];
		$oGroup			=& $oFolderFileSet->aField['sGroup'];
		$oIdOwner		=& $oFolderFileSet->aField['iIdOwner'];
		
		$oCriteria = $oPathName->like($babDB->db_escape_like($sRelativePath) . '%');
		$oCriteria = $oCriteria->_and($oIdDgOwner->in( (('Y' === $sGr) ? bab_getCurrentUserDelegation() : 0) ));
		$oCriteria = $oCriteria->_and($oGroup->in($sGr));
		if('N' === $sGr)
		{
			$oCriteria = $oCriteria->_and($oIdOwner->in($BAB_SESS_USERID));
		}
		
		$oFolderFileSet->select($oCriteria);

		while(null !== ($oFolderFile = $oFolderFileSet->next()))
		{
			$sBegin = mb_substr($oFolderFile->getPathName(), 0, mb_strlen($sRelativePath));
			$sEnd = (string) mb_substr($oFolderFile->getPathName(), mb_strlen($sRelativePath), mb_strlen($oFolderFile->getPathName()));

			$aPath = explode('/', $sBegin);
			if(is_array($aPath))
			{
				$sNewPathName = '';

				$iCount = count($aPath);
				if($iCount >= $iOffset)
				{
					$aPath[$iCount - $iOffset] = $sNewName;
					$sNewPathName = implode('/', $aPath) . $sEnd;
				}

				$oFolderFile->setPathName($sNewPathName);
				$oFolderFile->save();
			}
		}
	}
	
	
	function move($sOldRelativePath, $sNewRelativePath, $sGr)
	{
		global $babBody, $babDB;
		
		$oFolderFileSet	= new BAB_FolderFileSet();
		$oPathName		=& $oFolderFileSet->aField['sPathName'];
		$oIdDgOwner		=& $oFolderFileSet->aField['iIdDgOwner'];
		$oGroup			=& $oFolderFileSet->aField['sGroup'];

		$oCriteria = $oPathName->like($babDB->db_escape_like($sOldRelativePath) . '%');
		$oCriteria = $oCriteria->_and($oGroup->in($sGr));
		$oCriteria = $oCriteria->_and($oIdDgOwner->in(bab_getCurrentUserDelegation()));

		$aProcessedPath = array();
		$oFolderFileSet->select($oCriteria);
		while(null !== ($oFolderFile = $oFolderFileSet->next()))
		{
			$sOldPathName = $oFolderFile->getPathName();
			$sNewPathName = $sNewRelativePath . mb_substr($sOldPathName, mb_strlen($sOldRelativePath));
			
			if(false === array_key_exists($sNewPathName, $aProcessedPath))
			{
				$iIdRootFolder = 0;
				$_oFmFolder = null;
				BAB_FmFolderHelper::getInfoFromCollectivePath($sNewPathName, $iIdRootFolder, $_oFmFolder);							
				$iIdOwner = $_oFmFolder->getId();
				
				$aProcessedPath[$sNewPathName] = $iIdOwner;
			}
			$oFolderFile->setPathName($sNewPathName);
			$oFolderFile->setOwnerId($aProcessedPath[$sNewPathName]);
			$oFolderFile->save();
		}
	}
}


class BAB_FolderFileVersionSet extends BAB_BaseSet
{
	function BAB_FolderFileVersionSet()
	{
		parent::BAB_BaseSet(BAB_FM_FILESVER_TBL);

		$this->aField = array(
			'iId' => new BAB_IntField('`id`'),
			'iIdFile' => new BAB_IntField('`id_file`'),
			'sCreationDate' => new BAB_StringField('`date`'),
			'iIdAuthor' => new BAB_IntField('`author`'),
			'iVerMajor' => new BAB_IntField('`ver_major`'),
			'iVerMinor' => new BAB_IntField('`ver_minor`'),
			'sComment' => new BAB_StringField('`comment`'),
			'iIdFlowApprobationInstance' => new BAB_IntField('`idfai`'),
			'sConfirmed' => new BAB_StringField('`confirmed`'),
			'iIndexStatus' => new BAB_IntField('`index_status`')
		);
	}

	function remove($oCriteria, $sPathName, $sFileName)
	{
		$this->select($oCriteria);
		while(null !== ($oFolderFileVersion = $this->next()))
		{
			$sFullPathName = $sPathName . BAB_FVERSION_FOLDER . '/' . $oFolderFileVersion->getMajorVer() .
			',' . $oFolderFileVersion->getMinorVer() . ',' . $sFileName;

			if(file_exists($sFullPathName))
			{
				unlink($sFullPathName);
			}

			if(0 !== $oFolderFileVersion->getFlowApprobationInstanceId())
			{
				include_once $GLOBALS['babInstallPath']."utilit/afincl.php";
				deleteFlowInstance($oFolderFileVersion->getFlowApprobationInstanceId());
			}
		}
		parent::remove($oCriteria);
	}
}

class BAB_FolderFileLogSet extends BAB_BaseSet
{
	function BAB_FolderFileLogSet()
	{
		parent::BAB_BaseSet(BAB_FM_FILESLOG_TBL);

		$this->aField = array(
			'iId' => new BAB_IntField('`id`'),
			'iIdFile' => new BAB_IntField('`id_file`'),
			'sCreationDate' => new BAB_StringField('`date`'),
			'iIdAuthor' => new BAB_IntField('`author`'),
			'iAction' => new BAB_IntField('`action`'),
			'sComment' => new BAB_StringField('`comment`'),
			'sVersion' => new BAB_StringField('`version`')
		);
	}
}

class BAB_FolderFileFieldValueSet extends BAB_BaseSet
{
	function BAB_FolderFileFieldValueSet()
	{
		parent::BAB_BaseSet(BAB_FM_FIELDSVAL_TBL);

		$this->aField = array(
			'iId' => new BAB_IntField('`id`'),
			'iIdField' => new BAB_IntField('`id_field`'),
			'iIdFile' => new BAB_IntField('`id_file`'),
			'sValue' => new BAB_StringField('`fvalue`')
		);
	}
}

























class BAB_DbRecord
{
	var $aDatas = null;

	function BAB_DbRecord()
	{

	}

	function _iGet($sName)
	{
		$iValue = 0;
		$this->_get($sName, $iValue);
		return (int) $iValue;
	}

	function _sGet($sName)
	{
		$sValue = '';
		$this->_get($sName, $sValue);
		return (string) $sValue;
	}

	function _get($sName, &$sValue)
	{
		if(array_key_exists($sName, $this->aDatas))
		{
			$sValue = $this->aDatas[$sName];
			return true;
		}
		return false;
	}

	function _set($sName, $sValue)
	{
		$this->aDatas[$sName] = $sValue;
		return true;
	}
}


/**
 * Base class for Folders and Files (BAB_FmFolder and BAB_FolderFile).
 */
class BAB_FmFolderFile extends BAB_DbRecord
{
	function BAB_FmFolderFile() {
		
	}
}

class BAB_FmFolder extends BAB_FmFolderFile
{
	function BAB_FmFolder()
	{
		parent::BAB_FmFolderFile();
	}



	function setId($iId)
	{
		$this->_set('iId', $iId);
	}

	function getId()
	{
		return $this->_iGet('iId');
	}



	function setName($sName)
	{
		$this->_set('sName', $sName);
	}

	function getName()
	{
		return $this->_sGet('sName');
	}



	function setRelativePath($sRelativePath)
	{
		BAB_FmFolderHelper::sanitizePathname($sPathname);
		$this->_set('sRelativePath', $sRelativePath);
	}

	function getRelativePath()
	{
		return $this->_sGet('sRelativePath');
	}



	function setApprobationSchemeId($iId)
	{
		$this->_set('iIdApprobationScheme', $iId);
	}

	function getApprobationSchemeId()
	{
		return $this->_iGet('iIdApprobationScheme');
	}



	function setFileNotify($sFileNotify)
	{
		$this->_set('sFileNotify', $sFileNotify);
	}

	function getFileNotify()
	{
		return $this->_sGet('sFileNotify');
	}



	function setActive($sActive)
	{
		$this->_set('sActive', $sActive);
	}

	function getActive()
	{
		return $this->_sGet('sActive');
	}



	function setState($sState)
	{
		$this->_set('sState', $sState);
	}

	function getState()
	{
		return $this->_sGet('sState');
	}



	function setVersioning($sVersioning)
	{
		$this->_set('sVersioning', $sVersioning);
	}

	function getVersioning()
	{
		return $this->_sGet('sVersioning');
	}



	function setDelegationOwnerId($iId)
	{
		$this->_set('iIdDgOwner', $iId);
	}

	function getDelegationOwnerId()
	{
		return $this->_iGet('iIdDgOwner');
	}



	function setHide($sHide)
	{
		$this->_set('sHide', $sHide);
	}

	function getHide()
	{
		return $this->_sGet('sHide');
	}



	function setAddTags($sAddTags)
	{
		$this->_set('sAddTags', $sAddTags);
	}

	function getAddTags()
	{
		return $this->_sGet('sAddTags');
	}



	/**
	 * Activates or deactivates the download capping for this folder.
	 * If capping is activated, the maximum number of downloads is set through setMaxDownloads
	 * 
	 * @see setMaxDownloads
	 * @param string	$sMaximumDownloads	'Y' to activate download capping for this folder, 'N' otherwise.
	 */
	function setDownloadsCapping($sDownloadsCapping)
	{
		$this->_set('sDownloadsCapping', $sDownloadsCapping);
	}

	/**
	 * Returns the download capping status for this folder.
	 * 
	 * @return string 	'Y' if download capping is activated for this folder, 'N' otherwise.
	 */
	function getDownloadsCapping()
	{
		return $this->_sGet('sDownloadsCapping');
	}

	/**
	 * Setss the default maximum number of downloads for this folder.
	 * 
	 * @param int	$iMaxDownloads	The default maximum number of downloads for this folder. 
	 */
	function setMaxDownloads($iMaxDownloads)
	{
		$this->_set('iMaxDownloads', $iMaxDownloads);
	}

	/**
	 * Returns the default maximum number of downloads for this folder.
	 * 
	 * @return int		The default maximum number of downloads for this folder. 
	 */
	function getMaxDownloads()
	{
		return $this->_sGet('iMaxDownloads');
	}

	/**
	 * Activates or deactivates the download history for this folder.
	 * 
	 * @param string	$sDownloadHistory	'Y' to activate download history for this folder, 'N' otherwise.
	 */
	function setDownloadHistory($sDownloadHistory)
	{
		$this->_set('sDownloadHistory', $sDownloadHistory);
	}

	/**
	 * Returns the download history activation status for this folder.
	 * 
	 * @return string	'Y' if download history is activated for this folder, 'N' otherwise.
	 */
	function getDownloadHistory()
	{
		return $this->_sGet('sDownloadHistory');
	}



	function setAutoApprobation($sAutoApprobation)
	{
		$this->_set('sAutoApprobation', $sAutoApprobation);
	}

	function getAutoApprobation()
	{
		return $this->_sGet('sAutoApprobation');
	}

	function save()
	{
		$oFmFolderSet = new BAB_FmFolderSet();
		return $oFmFolderSet->save($this);
	}
}


class BAB_FmFolderCliboard extends BAB_DbRecord
{
	function BAB_FmFolderCliboard()
	{
		parent::BAB_DbRecord();
	}

	function setId($iId)
	{
		$this->_set('iId', $iId);
	}

	function getId()
	{
		return $this->_iGet('iId');
	}

	function setDelegationOwnerId($iId)
	{
		$this->_set('iIdDgOwner', $iId);
	}

	function getDelegationOwnerId()
	{
		return $this->_iGet('iIdDgOwner');
	}

	function setRootFolderId($iId)
	{
		$this->_set('iIdRootFolder', $iId);
	}

	function getRootFolderId()
	{
		return $this->_iGet('iIdRootFolder');
	}

	function setFolderId($iId)
	{
		$this->_set('iIdFolder', $iId);
	}

	function getFolderId()
	{
		return $this->_iGet('iIdFolder');
	}

	function setName($sName)
	{
		$this->_set('sName', $sName);
	}

	function getName()
	{
		return $this->_sGet('sName');
	}

	function setRelativePath($sRelativePath)
	{
		BAB_FmFolderHelper::sanitizePathname($sPathname);
		$this->_set('sRelativePath', $sRelativePath);
	}

	function getRelativePath()
	{
		return $this->_sGet('sRelativePath');
	}

	function setGroup($sGroup)
	{
		$this->_set('sGroup', $sGroup);
	}

	function getGroup()
	{
		return $this->_sGet('sGroup');
	}

	function setCollective($sCollective)
	{
		$this->_set('sCollective', $sCollective);
	}

	function getCollective()
	{
		return $this->_sGet('sCollective');
	}

	function setOwnerId($iIdOwner)
	{
		$this->_set('iIdOwner', $iIdOwner);
	}

	function getOwnerId()
	{
		return $this->_iGet('iIdOwner');
	}

	function setCheckSum($sCheckSum)
	{
		$this->_set('sCheckSum', $sCheckSum);
	}

	function getCheckSum()
	{
		return $this->_sGet('sCheckSum');
	}


	function save()
	{
		$oFmFolderCliboardSet = new BAB_FmFolderCliboardSet();
		return $oFmFolderCliboardSet->save($this);
	}
}


/**
 * Corresponds to a file
 *
 */
class BAB_FolderFile extends BAB_FmFolderFile
{
//	function BAB_FolderFile()
//	{
//		parent::BAB_FmFolderFile();
//	}

	/**
	 * Set the file identifier
	 *
	 * @param int $iId The file identifier
	 */
	function setId($iId)
	{
		$this->_set('iId', $iId);
	}

	/**
	 * Get the file identifier
	 *
	 * @return int The file identifier
	 */
	function getId()
	{
		return $this->_iGet('iId');
	}

	/**
	 * Set the filename
	 *
	 * @param string $sName The filename
	 */
	function setName($sName)
	{
		$this->_set('sName', $sName);
	}

	/**
	 * Get the filename
	 *
	 * @return string The filename
	 */
	function getName()
	{
		return $this->_sGet('sName');
	}

	/**
	 * Set the file description
	 *
	 * @param string $sDescription The file description
	 */
	function setDescription($sDescription)
	{
		$this->_set('sDescription', $sDescription);
	}

	/**
	 * Get the file description
	 *
	 * @return string The file description
	 */
	function getDescription()
	{
		return $this->_sGet('sDescription');
	}

	/**
	 * Set the relative pathname of the file, the pathname must not begin with a slash
	 * and must ending with a slash i.e(F1/F1.1/F1.1.1/)
	 *
	 * @param string $sPathName The relative pathname of the file
	 */
	function setPathName($sPathName)
	{
		BAB_FmFolderHelper::sanitizePathname($sPathname);
		$this->_set('sPathName', $sPathName);
	}

	/**
	 * Get the relative pathname of the file
	 *
	 * @return string The relative pathname of the file
	 */
	function getPathName()
	{
		return $this->_sGet('sPathName');
	}

	/**
	 * Set the first parent collective path identifier that the file belong to
	 *
	 * @param int $iIdOwner The first parent collective path identifier that the file belong to
	 */
	function setOwnerId($iIdOwner)
	{
		$this->_set('iIdOwner', $iIdOwner);
	}

	/**
	 * Get the first parent collective path identifier that the file belong to
	 *
	 * @return int The first parent collective path identifier that the file belong to
	 */
	function getOwnerId()
	{
		return $this->_iGet('iIdOwner');
	}

	/**
	 * Set if the file is a personnal file or a file manager file
	 * 'Y' for a file manager.
	 * 'N' for a personnal file.
	 *
	 * @param string $sGroup 'Y' if the file is a file manager file. 'N' if the file is a personnal file
	 */
	function setGroup($sGroup)
	{
		$this->_set('sGroup', $sGroup);
	}

	/**
	 * Get if the file is a personnal file or a file manager file
	 *
	 * @return string 'Y' if the file is a file manager file. 'N' if the file is a personnal file
	 */
	function getGroup()
	{
		return $this->_sGet('sGroup');
	}

	function setLinkId($iIdLink)
	{
		$this->_set('iIdLink', $iIdLink);
	}

	function getLinkId()
	{
		return $this->_iGet('iIdLink');
	}


	/**
	 * Set the read only status of the file
	 * 'Y' if the file is read only
	 * 'N' if the file is not read only
	 * 
	 * @param string $sReadOnly The read only flag of the file
	 */
	function setReadOnly($sReadOnly)
	{
		$this->_set('sReadOnly', $sReadOnly);
	}

	/**
	 * Get the read only status of the file
	 * 'Y' if the file is read only
	 * 'N' if the file is not read only
	 * 
	 * @return string 'Y' if the file is read only. 'N' if the file is not read only
	 */
	function getReadOnly()
	{
		return $this->_sGet('sReadOnly');
	}

	function setState($sState)
	{
		$this->_set('sState', $sState);
	}

	function getState()
	{
		return $this->_sGet('sState');
	}

	/**
	 * Set the creation date of the file in ISO format
	 *
	 * @param string $sCreation ISO datetime
	 */
	function setCreationDate($sCreation)
	{
		$this->_set('sCreation', $sCreation);
	}

	/**
	 * Set the creation date of the file in ISO
	 * 
	 * @return string The ISO datetime
	 */
	function getCreationDate()
	{
		return $this->_sGet('sCreation');
	}

	/**
	 * Set the author identifier of the file
	 *
	 * @param int $iIdAuthor Identifier of the author
	 */
	function setAuthorId($iIdAuthor)
	{
		$this->_set('iIdAuthor', $iIdAuthor);
	}

	/**
	 * Get the identifier of the file author
	 *
	 * @return int The identifier of the file author
	 */
	function getAuthorId()
	{
		return $this->_iGet('iIdAuthor');
	}

	/**
	 * Set the modified date of the file in ISO format
	 *
	 * @param string $sModified The modified date of the file in ISO format
	 */
	function setModifiedDate($sModified)
	{
		$this->_set('sModified', $sModified);
	}

	/**
	 * Get the modified date of the file in ISO format
	 *
	 * @return string  The modified date of the file in ISO format
	 */
	function getModifiedDate()
	{
		return $this->_sGet('sModified');
	}

	/**
	 * Set the user identifier of the file modifier 
	 *
	 * @param int $iIdModifier The user identifier of the file modifier
	 */
	function setModifierId($iIdModifier)
	{
		$this->_set('iIdModifier', $iIdModifier);
	}

	/**
	 * Get the user identifier of the file modifier 
	 *
	 * @return int The user identifier of the file modifier
	 */
	function getModifierId()
	{
		return $this->_iGet('iIdModifier');
	}

	/**
	 * Set the file approbation status
	 *
	 * @param string $sConfirmed The file approbation status. 'Y' the file is approuved. 'N' the is waiting for approbation
	 */
	function setConfirmed($sConfirmed)
	{
		$this->_set('sConfirmed', $sConfirmed);
	}

	/**
	 * Get the file approbation status
	 *
	 * @return string The file approbation status. 'Y' the file is approuved. 'N' the is waiting for approbation
	 */
	function getConfirmed()
	{
		return $this->_sGet('sConfirmed');
	}

	/**
	 * Set the hits number of the file
	 *
	 * @param int $iHits The hit number
	 */
	function setHits($iHits)
	{
		$this->_set('iHits', $iHits);
	}

	/**
	 * Get the hits number of the file
	 *
	 * @return int The hit number of the file
	 */
	function getHits()
	{
		return $this->_iGet('iHits');
	}

	/**
	 * Set the number of downloads for the file
	 *
	 * @param int $iDownloads The number of downloads
	 */
	function setDownloads($iDownloads)
	{
		$this->_set('iDownloads', $iDownloads);
	}

	/**
	 * Get the number of downloads of the file
	 *
	 * @return int The number of downloads of the file
	 */
	function getDownloads()
	{
		return $this->_iGet('iDownloads');
	}

	/**
	 * Set the maximum number of downloads for the file
	 *
	 * @param int $iMaxDownloads The maximum number of downloads
	 */
	function setMaxDownloads($iMaxDownloads)
	{
		$this->_set('iMaxDownloads', $iMaxDownloads);
	}

	/**
	 * Get the maximum number of downloads of the file
	 *
	 * @return int The maximum number of downloads of the file
	 */
	function getMaxDownloads()
	{
		return $this->_iGet('iMaxDownloads');
	}

	
	/**
	 * Checks if the file maximum download number has been reached.
	 * 
	 * @return bool
	 */
	function downloadLimitReached()
	{
		if ($this->getMaxDownloads() == 0) {
			return false;
		}

		$filePathname = $this->getPathName();
		$firstCollectiveFolder = BAB_FmFolderSet::getFirstCollectiveFolder($filePathname);

		// Checks that downloads capping is active on the file's owner folder.
		if ($firstCollectiveFolder->getDownloadsCapping() == 'Y'
				&& $this->getMaxDownloads() <= $this->getDownloads()) {
			return true;
		}

		return false;		
	}


	/**
	 * Set the identifier of the approbation scheme
	 *
	 * @param int $iIdFlowApprobationInstance The identifier of the approbation scheme
	 */
	function setFlowApprobationInstanceId($iIdFlowApprobationInstance)
	{
		$this->_set('iIdFlowApprobationInstance', $iIdFlowApprobationInstance);
	}

	/**
	 * Get the identifier of the approbation scheme
	 *
	 * @return int The identifier of the approbation scheme
	 */
	function getFlowApprobationInstanceId()
	{
		return $this->_iGet('iIdFlowApprobationInstance');
	}

	/**
	 * Set the identifier of the file version.
	 * 
	 * @see BAB_FolderFile::getFolderFileVersionId
	 * 
	 * @param int $iIdFolderFileVersion The identifier of the file version
	 */
	function setFolderFileVersionId($iIdFolderFileVersion)
	{
		$this->_set('iIdFolderFileVersion', $iIdFolderFileVersion);
	}

	/**
	 * Get the identifier of the file version
	 *
	 * If the file is locked (by the user) the returned value
	 * is the id of the file version table record (bab_fm_filesver).
	 * 
	 * If the file is not locked getFolderFileVersionId returns 0
	 *
	 * @return int		The identifier of the file version record or 0. 
	 */
	function getFolderFileVersionId()
	{
		return $this->_iGet('iIdFolderFileVersion');
	}

	/**
	 * Set the major version of the file
	 *
	 * @param int $iVerMajor The major version of the file
	 */
	function setMajorVer($iVerMajor)
	{
		$this->_set('iVerMajor', $iVerMajor);
	}

	/**
	 * Get the major version of the file
	 *
	 * @return int The major version of the file
	 */
	function getMajorVer()
	{
		return $this->_iGet('iVerMajor');
	}

	/**
	 * Set the minor version of the file
	 *
	 * @param int $iVerMinor The minor version of the file
	 */
	function setMinorVer($iVerMinor)
	{
		$this->_set('iVerMinor', $iVerMinor);
	}

	/**
	 * Get the minor version of the file
	 *
	 * @return int The minor version of the file
	 */
	function getMinorVer()
	{
		return $this->_iGet('iVerMinor');
	}

	/**
	 * Set the comment of the file
	 *
	 * @param string $sVerComment The comment of the file
	 */
	function setCommentVer($sVerComment)
	{
		$this->_set('sVerComment', $sVerComment);
	}

	/**
	 * Get the comment of the file
	 *
	 * @return string The comment of the file
	 */
	function getCommentVer()
	{
		return $this->_sGet('sVerComment');
	}

	/**
	 * Set the status index of the file
	 *
	 * @param int $iIndexStatus The status index of the file
	 */
	function setStatusIndex($iIndexStatus)
	{
		$this->_set('iIndexStatus', $iIndexStatus);
	}

	/**
	 * Get the status index of the file
	 *
	 * @return int The status index of the file
	 */
	function getStatusIndex()
	{
		return $this->_iGet('iIndexStatus');
	}

	/**
	 * Set the delegation identifier of the file
	 *
	 * @param int $iId The delagation identifier
	 */
	function setDelegationOwnerId($iId)
	{
		$this->_set('iIdDgOwner', $iId);
	}

	/**
	 * Get the delegation identifier of the file
	 *
	 * @return int The delagation identifier 
	 */
	function getDelegationOwnerId()
	{
		return $this->_iGet('iIdDgOwner');
	}

	/**
	 * Save the file
	 *
	 */
	function save()
	{
		$oFolderFileSet = new BAB_FolderFileSet();
		$oFolderFileSet->save($this);
	}
	
	/**
	 * Returns the full pathname of the file
	 * 
	 * @return string
	 */
	function getFullPathname()
	{
		$sFmPath = BAB_FileManagerEnv::getCollectivePath($this->getDelegationOwnerId());
		return $sFmPath . $this->getPathName() . $this->getName();
	}

	/**
	 * Get root folder
	 * @param	string	$sRelativePathName 		relative path without delegation folder
	 * @param	int		$iIdDelegation
	 *
	 * @return BAB_FmFolder
	 */
	public static function getRootFolder($sRelativePathName, $iIdDelegation)
	{
		$sRootFldName		= getFirstPath($sRelativePathName);
		$oFolderSet			= bab_getInstance('BAB_FmFolderSet');
		$oNameField			= $oFolderSet->aField['sName'];
		$oRelativePathField	= $oFolderSet->aField['sRelativePath'];
		$oIdDgOwnerField	= $oFolderSet->aField['iIdDgOwner'];

		$oCriteria = $oNameField->in($sRootFldName);
		$oCriteria = $oCriteria->_and($oRelativePathField->in(''));
		$oCriteria = $oCriteria->_and($oIdDgOwnerField->in($iIdDelegation));
		
		return $oFolderSet->get($oCriteria);
	}


	/**
	 * Get download url
	 * @return string
	 */
	public function getDownloadUrl() 
	{
		$oFolder = self::getRootFolder($this->getPathName(), $this->getDelegationOwnerId());

		if(!($oFolder instanceof BAB_FmFolder))
		{
			return null;
		}

		return $GLOBALS['babUrlScript'] . '?tg=fileman&id=' . $oFolder->getId() . '&gr=' . $this->getGroup() . '&path=' . urlencode(removeEndSlashes($this->getPathName())).'&sAction=getFile&idf='.$this->getId();
	}
}


class BAB_FolderFileVersion extends BAB_DbRecord
{
	function BAB_FolderFileVersion()
	{
		parent::BAB_DbRecord();
	}

	function setId($iId)
	{
		$this->_set('iId', $iId);
	}

	function getId()
	{
		return $this->_iGet('iId');
	}

	function setIdFile($iId)
	{
		$this->_set('iIdFile', $iId);
	}

	function getIdFile()
	{
		return $this->_iGet('iIdFile');
	}

	function setCreationDate($sDate)
	{
		$this->_set('sCreationDate', $sDate);
	}

	function getCreationDate()
	{
		return $this->_sGet('sCreationDate');
	}

	function setAuthorId($iIdAuthor)
	{
		$this->_set('iIdAuthor', $iIdAuthor);
	}

	function getAuthorId()
	{
		return $this->_iGet('iIdAuthor');
	}

	function setMajorVer($iVerMajor)
	{
		$this->_set('iVerMajor', $iVerMajor);
	}

	function getMajorVer()
	{
		return $this->_iGet('iVerMajor');
	}

	function setMinorVer($iVerMinor)
	{
		$this->_set('iVerMinor', $iVerMinor);
	}

	function getMinorVer()
	{
		return $this->_iGet('iVerMinor');
	}

	function setComment($sComment)
	{
		$this->_set('sComment', $sComment);
	}

	function getComment()
	{
		return $this->_sGet('sComment');
	}

	function setFlowApprobationInstanceId($iIdFlowApprobationInstance)
	{
		$this->_set('iIdFlowApprobationInstance', $iIdFlowApprobationInstance);
	}

	function getFlowApprobationInstanceId()
	{
		return $this->_iGet('iIdFlowApprobationInstance');
	}

	function setConfirmed($sConfirmed)
	{
		$this->_set('sConfirmed', $sConfirmed);
	}

	function getConfirmed()
	{
		return $this->_sGet('sConfirmed');
	}

	function setStatusIndex($iIndexStatus)
	{
		$this->_set('iIndexStatus', $iIndexStatus);
	}

	function getStatusIndex()
	{
		return $this->_iGet('iIndexStatus');
	}

	function save()
	{
		$oFolderFileVersionSet = new BAB_FolderFileVersionSet();
		$oFolderFileVersionSet->save($this);
	}
}


class BAB_FolderFileLog extends BAB_DbRecord
{
	function BAB_FolderFileLog()
	{
		parent::BAB_DbRecord();
	}

	function setId($iId)
	{
		$this->_set('iId', $iId);
	}

	function getId()
	{
		return $this->_iGet('iId');
	}

	function setIdFile($iId)
	{
		$this->_set('iIdFile', $iId);
	}

	function getIdFile()
	{
		return $this->_iGet('iIdFile');
	}

	function setCreationDate($sDate)
	{
		$this->_set('sCreationDate', $sDate);
	}

	function getCreationDate()
	{
		return $this->_sGet('sCreationDate');
	}

	function setAuthorId($iIdAuthor)
	{
		$this->_set('iIdAuthor', $iIdAuthor);
	}

	function getAuthorId()
	{
		return $this->_iGet('iIdAuthor');
	}

	function setAction($iAction)
	{
		$this->_set('iAction', $iAction);
	}

	function getAction()
	{
		return $this->_iGet('iAction');
	}

	function setComment($sComment)
	{
		$this->_set('sComment', $sComment);
	}

	function getComment()
	{
		return $this->_sGet('sComment');
	}

	function setVersion($sVersion)
	{
		$this->_set('sVersion', $sVersion);
	}

	function getVersion()
	{
		return $this->_sGet('sVersion');
	}

	function save()
	{
		$oFolderFileLogSet = new BAB_FolderFileLogSet();
		$oFolderFileLogSet->save($this);
	}
}


class BAB_FolderFileFieldValue extends BAB_DbRecord
{
	function BAB_FolderFileFieldValue()
	{
		parent::BAB_DbRecord();
	}

	function setId($iId)
	{
		$this->_set('iId', $iId);
	}

	function getId()
	{
		return $this->_iGet('iId');
	}

	function setIdFile($iId)
	{
		$this->_set('iIdFile', $iId);
	}

	function getIdFile()
	{
		return $this->_iGet('iIdFile');
	}

	function setIdField($iIdField)
	{
		$this->_set('iIdField', $iIdField);
	}

	function getIdField()
	{
		return $this->_iGet('iIdField');
	}

	function setValue($sValue)
	{
		$this->_set('sValue', $sValue);
	}

	function getValue()
	{
		return $this->_sGet('sValue');
	}

	function save()
	{
		$oFolderFileFieldValueSet = new BAB_FolderFileFieldValueSet();
		$oFolderFileFieldValueSet->save($this);
	}
}



//Il faudra couper cette classe en deux faire une classe de base
//et deux classe d�riv�es. Une pour les repertoire simple et une
//pour les repertoire collectif
class BAB_FmFolderHelper
{
	function BAB_FmFolderHelper()
	{

	}

	function getFmFolderById($iId)
	{
		global $babBody;
		
		$oFmFolderSet = new BAB_FmFolderSet();
		$oId =& $oFmFolderSet->aField['iId'];
		return $oFmFolderSet->get($oId->in($iId));
	}

	function getFileInfoForCollectiveDir($iIdFolder, $sPath, &$iIdOwner, &$sRelativePath, &$oFmFolder)
	{
		$bSuccess = true;

		$oFmFolder = BAB_FmFolderHelper::getFmFolderById($iIdFolder);
		if(!is_null($oFmFolder))
		{
			$iIdOwner = $oFmFolder->getId();
			
			if($oFmFolder->getName() === $sPath || '' === $sPath)
			{
				$sRelativePath = $oFmFolder->getName() . '/';
			}
			else 
			{
				$sRelativePath = $sPath . ((mb_substr($sPath, - 1) !== '/') ? '/' : '');

				$oFmFolder = BAB_FmFolderSet::getFirstCollectiveFolder($sRelativePath);
				if(!is_null($oFmFolder))
				{
					$iIdOwner = $oFmFolder->getId();
				}
				else
				{
					$bSuccess = false;
				}
			}
		}
		else
		{
			$bSuccess = false;
		}
		return $bSuccess;
	}

	function getInfoFromCollectivePath($sPath, &$iIdRootFolder, &$oFmFolder, $bParentPath = false)
	{
		$bSuccess = false;
		
		$oRootFmFolder = BAB_FmFolderSet::getRootCollectiveFolder($sPath);
		if(!is_null($oRootFmFolder))
		{
			$iIdRootFolder = $oRootFmFolder->getId();

			$sRelativePath = canonizePath($sPath);

			$oFmFolder = null;

			if(false === $bParentPath)
			{
				$oFmFolder = BAB_FmFolderSet::getFirstCollectiveFolder($sRelativePath);
			}
			else 
			{
				$oFmFolder = BAB_FmFolderSet::getFirstCollectiveParentFolder($sRelativePath);
			}
				
			if(!is_null($oFmFolder))
			{
				$bSuccess = true;
			}
		}
		return $bSuccess;
	}


	function getUploadPath()
	{
		$sUploadPath = $GLOBALS['babUploadPath'];
		$iLength = mb_strlen(trim($sUploadPath));
		if($iLength > 0)
		{
			return BAB_PathUtil::addEndSlash(BAB_PathUtil::sanitize($sUploadPath));
		}
		return $sUploadPath;
	}

	function createDirectory($sFullPathName)
	{
		global $babBody;
		$bSuccess = true;
		
		bab_debug('(' . $sFullPathName . ')');
		if(mb_strlen(trim($sFullPathName)) > 0 && preg_match('#^(|.*[/\\\\])\.\.(|[/\\\\].*)$#', $sFullPathName) === 0)
		{
			if(!is_dir($sFullPathName))
			{
				$sUploadPath = BAB_FmFolderHelper::getUploadPath();
				$sRelativePath = mb_substr($sFullPathName, mb_strlen($sUploadPath));
				$bSuccess = BAB_FmFolderHelper::makeDirectory($sUploadPath, $sRelativePath);
			}
			else
			{
				$babBody->msgerror = bab_translate("This folder already exists");
				$bSuccess = false;
			}
		}
		else
		{
			$babBody->msgerror = bab_translate("Please give a valid directory name");
			$bSuccess = false;
		}
		return $bSuccess;
	}

	function makeDirectory($sUploadPath, $sRelativePath)
	{
		$aPaths = explode('/', $sRelativePath);
		if(is_array($aPaths) && count($aPaths) > 0)
		{
			$sPath = removeEndSlah($sUploadPath);
			foreach($aPaths as $sPathItem)
			{
				if(mb_strlen(trim($sPathItem)) !== 0)
				{
					$sPathItem = replaceInvalidFolderNameChar($sPathItem);
					
					$sPath .= '/' . $sPathItem;
					if(!is_dir($sPath))
					{
						if(!bab_mkdir($sPath, $GLOBALS['babMkdirMode']))
						{
							return false;
						}
					}
				}
			}
			return true;
		}
		return false;
	}
	
	/**
	 * The $sPathName must be canonized before calling this function
	 */
	function sanitizePathname(&$sPathname)
	{
		$sPathname	= removeEndSlashes($sPathname);
		$aPaths		= explode('/', $sPathname);
		
		if(is_array($aPaths) && count($aPaths) > 0)
		{
			foreach($aPaths as $iKey => $sPathItem)
			{
				if(mb_strlen(trim($sPathItem)) !== 0)
				{
					$aPaths[$iKey] = replaceInvalidFolderNameChar($sPathItem);
				}
			}
			
			$sPathname = implode('/', $aPaths);
			
			return addEndSlash($sPathname);
		}
		
		return $sPathname;
	}
	
	function renameDirectory($sUploadPath, $sRelativePath, $sOldName, $sNewName)
	{
		global $babBody;
		$bSuccess = true;

		$bOldNameValid = (mb_strlen(trim($sOldName)) > 0);
		$bNewNameValid = (mb_strlen(trim($sNewName)) > 0 && ($sNewName !== '..'));
		
		if($bOldNameValid && $bNewNameValid)
		{
			$sOldPathName = '';
			$sNewPathName = '';

			$sUploadPath = canonizePath(realpath($sUploadPath));
			if(mb_strlen(trim($sRelativePath)) > 0)
			{
				$sPathName		= canonizePath(realpath($sUploadPath . $sRelativePath));
				$sOldPathName	= canonizePath(realpath($sPathName . $sOldName));
				$sNewPathName	= $sPathName . $sNewName;
			}
			else
			{
				$sOldPathName	= canonizePath(realpath($sUploadPath . $sOldName));
				$sNewPathName	= $sUploadPath . $sNewName;
			}

			$sUploadPath = realpath($sUploadPath);
			$bOldPathNameValid = (realpath(mb_substr($sOldPathName, 0, mb_strlen($sUploadPath))) === $sUploadPath);

			if($bOldPathNameValid)
			{
				if(is_writable($sOldPathName))
				{
					if(!is_dir($sNewPathName))
					{
						$bSuccess = rename($sOldPathName, $sNewPathName);
					}
					else
					{
						$babBody->msgerror = bab_translate("This folder already exists");
						$bSuccess = false;
					}
				}
				else
				{
					$babBody->msgerror = bab_translate("This folder does not exists");
					$bSuccess = false;
				}
			}
			else
			{
				$babBody->msgerror = bab_translate("Access denied");
				$bSuccess = false;
			}
		}
		else
		{
			$babBody->msgerror = bab_translate("Access denied");
			$bSuccess = false;
		}
		return $bSuccess;
	}
}
