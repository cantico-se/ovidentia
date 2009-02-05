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
require_once dirname(__FILE__) . '/fileincl.php';




class bab_FileInfo extends SplFileInfo
{
	public function __construct($sFilename)
	{
		parent::__construct($sFilename);
	}
}


class bab_DirectoryFilter
{
	const DOT	= 1;
	const FILE	= 2;
	const DIR	= 4;
}


abstract class bab_FilteredDirectoryIterator extends FilterIterator
{
	protected $iFilterBits		= 0;
	protected $sRelativePath	= null; 
	protected $iIdObject		= 0;
	
	public function __construct($sFullPathName)
    {
        parent::__construct(new DirectoryIterator($sFullPathName));
    }

    
    public function setFilter($iBit)
    {
		$this->iFilterBits |= $iBit;		
    }

    
    public function accept()
    {
    	$oIterator = $this->getInnerIterator();
    	
    	if($this->bitActivated(bab_DirectoryFilter::DOT) && $oIterator->isDot())
    	{
    		return false;
    	}
    	
    	if($oIterator->isFile())
    	{
	    	if($this->bitActivated(bab_DirectoryFilter::FILE))
	    	{
	    		return false;
	    	}
	    	
	    	return $this->acceptFile($oIterator);
    	}
    	
    	if($oIterator->isDir())
    	{
	    	if($this->bitActivated(bab_DirectoryFilter::DIR))
	    	{
	    		return false;
	    	}
	    	
	    	return $this->acceptDir($oIterator);
	    }
    	return true;
    }
    
    
    public function setRelativePath($sRelativePath)
    {
    	$this->sRelativePath = BAB_PathUtil::addEndSlash(BAB_PathUtil::sanitize($sRelativePath));
    }
    
    
    public function setObjectId($iIdObject)
    {
    	$this->iIdObject = (int) $iIdObject;
    }
    
    
    private function bitActivated($iBit)
    {
    	return ($this->iFilterBits & $iBit);
    }
    
    
    abstract protected function acceptFile($oIterator);
    
    
    abstract protected function acceptDir($oIterator);
}


class bab_CollectiveDirIterator extends bab_FilteredDirectoryIterator
{
	private $oFolderSet			= null;
	private $oNameField			= null;
	private $oRelativePathField	= null;
	private $oIdDgOwnerField	= null;
	private $oFolder			= null;
    
	private $aPathCache			= array();
	
	public function __construct($sFullPathName)
    {
        parent::__construct($sFullPathName);
        
        $this->oFolderSet			= bab_getInstance('BAB_FmFolderSet');
		$this->oNameField			= $this->oFolderSet->aField['sName'];
		$this->oRelativePathField	= $this->oFolderSet->aField['sRelativePath'];
		$this->oIdDgOwnerField		= $this->oFolderSet->aField['iIdDgOwner'];
    }
    
    
    protected function acceptFile($oIterator)
    {
    	return true;
    }
    
    
    protected function acceptDir($oIterator)
    {
    	if(strtolower(BAB_FVERSION_FOLDER) == strtolower($oIterator->getFilename()))
    	{
    		return false;
    	}
    	
    	$bSuccess = false; 
		$iIdOldDelegation = bab_getCurrentUserDelegation();
 		
		bab_setCurrentUserDelegation($this->iIdObject);
		
		$this->oFolder = $this->getCollectiveFolder($oIterator->getFilename(), $this->sRelativePath); 
    	if(!($this->oFolder instanceof BAB_FmFolder))
		{
			$this->oFolder = $this->getFirstCollectiveParentFolder(BAB_PathUtil::removeEndSlashes($this->sRelativePath));
		}

		if($this->oFolder instanceof BAB_FmFolder)
		{
			$sRelativePath	= $this->sRelativePath . $oIterator->getFilename() . '/';
			$bCanManage		= canManage($sRelativePath);
			$bCanBrowse		= canBrowse($sRelativePath);
			
			if($bCanManage || canUpload($sRelativePath) || canUpdate($sRelativePath) || ($bCanBrowse && 'N' === $this->oFolder->getHide()))
			{
				$bSuccess = true;
			}
		}
		
		bab_setCurrentUserDelegation($iIdOldDelegation);
		return $bSuccess;
    }
    
    
    public function current()
    {
    	return new bab_FileInfo(parent::current()->getPathname());
    }
    
    
    private function getCollectiveFolder($sFolderName, $sRelativePath)
    {
		$oCriteria = $this->oNameField->in($sFolderName);
		$oCriteria = $oCriteria->_and($this->oRelativePathField->in($sRelativePath));
		$oCriteria = $oCriteria->_and($this->oIdDgOwnerField->in($this->iIdObject));
		//bab_debug($this->oFolderSet->getSelectQuery($oCriteria));
		return $this->oFolderSet->get($oCriteria);
    }

    
	private function getFirstCollectiveParentFolder($sRelativePath)
	{
		if(!array_key_exists($sRelativePath, $this->aPathCache))
		{	
			$this->aPathCache[$sRelativePath] = null;
			
			$oFolder = BAB_FmFolderSet::getFirstCollectiveFolder($sRelativePath);
			if(!is_null($oFolder))
			{
				$this->aPathCache[$sRelativePath] = $oFolder;
			}
		}
		return $this->aPathCache[$sRelativePath];
	}
}

/*
 * For collective folder.
 */
class bab_Directory
{
	private $sUploadPath		= null;
	private $sRootFmPath		= null;
	private $sRelativePath		= null;
	private $iIdObject			= 0;
	private $sPathName			= null;
	
	
	public function __construct()
	{
		
	}
	
	public function getUploadPath()
	{
		return $this->sUploadPath;
	}
	
	public function getRootFmPath()
	{
		return $this->sRootFmPath;
	}
	
	public function getRelativePath()
	{
		return $this->sRelativePath;
	}
	
	public function getPathName()
	{
		return $this->sPathName;
	}
	
	public function getEntries($sPathName, $iFilter)
	{
		if(!$this->processPathName($sPathName, $this->sPathName))
		{
			bab_debug('Path ==> ' . $sPathName . ' is not valid');
			return false;
		}
		
		if(!$this->setEnv($sPathName))
		{
			bab_debug('Path ==> ' . $sPathName . ' is not initialized');
			return false;
		}
		
		if(!$this->accessValid())
		{
			bab_debug('Path ==> ' . $sPathName . ' is not valid');
			return false;
		}
		
		//$this->displayInfo();
		
		$iFilter |= bab_DirectoryFilter::DOT;
		$oBabDirIt = new bab_CollectiveDirIterator($this->getRootFmPath() . $this->getPathName());
		$oBabDirIt->setFilter($iFilter);
		$oBabDirIt->setRelativePath($this->sRelativePath);
		$oBabDirIt->setObjectId($this->iIdObject);
		return $oBabDirIt; 
	}
	
	public function createSubdirectory($sPathName)
	{
		if(!$this->processPathName($sPathName, $this->sPathName))
		{
			bab_debug('Path ==> ' . $sPathName . ' is not valid');
			return false;
		}
		
		if(!$this->setEnv($sPathName))
		{
			bab_debug('Path ==> ' . $sPathName . ' is not initialized');
			return false;
		}
		
		if(!$this->accessValid())
		{
			bab_debug('Path ==> ' . $sPathName . ' is not valid');
			return false;
		}
			
		$oFmEnv	= bab_getInstance('BAB_FileManagerEnv');
		if(!canCreateFolder($oFmEnv->sRelativePath))
		{
			bab_debug('Error');
			return false;
		}
		
		if(!$this->isPathNameCreatable())
		{
			bab_debug('Error pathName not creatable');
			return false;
		}
		
		$sFullPathName = $this->getRootFmPath() . $this->getPathName();
		if(!BAB_FmFolderHelper::createDirectory($sFullPathName))
		{
			bab_debug('Error');
			return false;
		}
		
		return true;
	}
	
	public function deleteSubdirectory($sPathName)
	{
		if(!$this->processPathName($sPathName, $this->sPathName))
		{
			bab_debug('Path ==> ' . $sPathName . ' is not valid');
			return false;
		}
		
		if(!$this->setEnv($sPathName))
		{
			bab_debug('Path ==> ' . $sPathName . ' is not initialized');
			return false;
		}
	
		if(!$this->accessValid())
		{
			bab_debug('Path ==> ' . $sPathName . ' access not valid');
			return false;
		}
				
		$oFmEnv	= bab_getInstance('BAB_FileManagerEnv');
		if(!canCreateFolder($oFmEnv->sRelativePath))
		{
			bab_debug('Error !!!!!');
			return false;
		}
		
		$sFullPathName = $this->getRootFmPath() . $this->getPathName();
		if(!is_dir($sFullPathName))
		{
			bab_debug("Please give a valid folder name " . $sFullPathName);
			return false;
		}

		//Just to be sure that the folder DGx is not deleted
		if($sFullPathName == $this->getRootFmPath())
		{
			bab_debug("The folder is not deletable " . $sFullPathName);
			return false;
		}
		
		$sPathName		= $this->getPathName($sPathName);
		$sName			= getLastPath($sPathName);
		$sRelativePath	= removeLastPath($sPathName); 
		if('' != $sRelativePath)
		{
			$sRelativePath = BAB_PathUtil::addEndSlash($sRelativePath); 
		}
		
		/*
		$this->displayInfo();
				
		bab_debug(
			'sName         ==> ' . $sName . "\n" . 
			'sRelativePath ==> ' . $sRelativePath
		);
		//*/

		$oFolderSet			= bab_getInstance('BAB_FmFolderSet');
		$oNameField			= $oFolderSet->aField['sName'];
		$oRelativePathField	= $oFolderSet->aField['sRelativePath'];
		$oIdDgOwnerField	= $oFolderSet->aField['iIdDgOwner'];
		
		$oCriteria	= $oNameField->in($sName);
		$oCriteria	= $oCriteria->_and($oRelativePathField->in($sRelativePath));
		$oCriteria	= $oCriteria->_and($oIdDgOwnerField->in($this->iIdObject));
		$oFolder	= $oFolderSet->get($oCriteria);
		
		if($oFolder instanceof BAB_FmFolder)
		{
			require_once $GLOBALS['babInstallPath'] . 'utilit/delincl.php';
			bab_deleteFolder($oFolder->getId());
		}
		else
		{
			$oFolderSet->removeSimpleCollectiveFolder($sRelativePath . $sName . '/');
		}
//		$this->displayInfo();
	}
	
	public function renameSubDirectory($sSrcPathName, $sTrgPathName)
	{
		$sSrcPathName = BAB_PathUtil::addEndSlash(BAB_PathUtil::sanitize($sSrcPathName));
		$sTrgPathName = BAB_PathUtil::addEndSlash(BAB_PathUtil::sanitize($sTrgPathName));
		
		$sSanitizedTrgPathName = '';
		if(!$this->processPathName($sTrgPathName, $sSanitizedTrgPathName))
		{
			bab_debug('Path ==> ' . $sTrgPathName . ' is not valid');
			return false;
		}
		
		$sSanitizedSrcPathName = '';
		if(!$this->processPathName($sSrcPathName, $sSanitizedSrcPathName))
		{
			bab_debug('Path ==> ' . $sSrcPathName . ' is not valid');
			return false;
		}
		
		$sSrcName		= (string) getLastPath($sSanitizedSrcPathName);
		$sSrcPath		= (string) removeLastPath($sSanitizedSrcPathName);
$sSrcPath		= (string) addEndSlash($sSrcPath);
		$sTrgName		= (string) getLastPath($sSanitizedTrgPathName);
		$sTrgPath		= (string) removeLastPath($sSanitizedTrgPathName);
$sTrgPath		= (string) addEndSlash($sTrgPath);
		
		$aBuffer		= array();
		
		if(0 === mb_strlen($sSrcName))
		{
			bab_debug('sSrcName ==> ' . $sSrcName . ' is not valid');
			return false;
		}
		
		if(0 === mb_strlen($sTrgName))
		{
			bab_debug('sTrgName ==> ' . $sTrgName . ' is not valid');
			return false;
		}
		
		if(1 !== preg_match('#^DG(\d+)(/)#', $sTrgPathName, $aBuffer))
		{
			bab_debug('Path ==> ' . $sTrgPathName . ' is not valid');
			return false;
		}
		
		$iIdTrgDelegation	= (int) $aBuffer[1];
		$sFullTrgPathName	= BAB_FileManagerEnv::getCollectivePath($iIdTrgDelegation);
		$sFullTrgPathName	.= 	$sSanitizedTrgPathName;
		
		if(1 !== preg_match('#^DG(\d+)(/)#', $sSrcPathName, $aBuffer))
		{
			bab_debug('Path ==> ' . $sSrcPathName . ' is not valid');
			return false;
		}
		
		$iIdSrcDelegation = (int) $aBuffer[1];
		
		if($iIdTrgDelegation !== $iIdSrcDelegation)
		{
			bab_debug('$iIdTrgDelegation !== $iIdSrcDelegation');
			return false;
		}
		
		$oDirRenContext = new bab_directoryRenameContext();
	
		$oDirRenContext->setSrcPathName($sSrcPathName);
		$oDirRenContext->setSanitizedSrcPathName($sSanitizedSrcPathName);
		$oDirRenContext->setSrcName($sSrcName);
		$oDirRenContext->setSrcPath($sSrcPath);
		$oDirRenContext->setSrcDelegationId($iIdSrcDelegation);
		
		$oDirRenContext->setTrgDelegationId($iIdTrgDelegation);
		
		/*
		bab_debug(
			'sSrcName         ==> ' . $sSrcName . "\n" .
			'sSrcPath         ==> ' . $sSrcPath . "\n" .
			'sTrgName         ==> ' . $sTrgName . "\n" .
			'sTrgPath         ==> ' . $sTrgPath . "\n" .
			'sFullTrgPathName ==> ' . $sFullTrgPathName
		);		
		//*/

		if(!file_exists($sFullTrgPathName))
		{
			if($sSrcPath === $sTrgPath)
			{
				$oDirRenContext->setTrgPathName($sTrgPathName);
				$oDirRenContext->setSanitizedTrgPathName($sSanitizedTrgPathName);
				$oDirRenContext->setTrgName($sTrgName);
				$oDirRenContext->setTrgPath($sTrgPath);
				
				$this->renameDirectory($oDirRenContext);
			}
			else
			{
				$sPath = canonizePath(removeLastPath($sSanitizedTrgPathName));
				$oDirRenContext->setTrgPathName(canonizePath('DG' . $iIdTrgDelegation . '/' . $sPath . $sSrcName));
				$oDirRenContext->setSanitizedTrgPathName($sPath . $sSrcName . '/');
				$oDirRenContext->setTrgName($sSrcName);
				$oDirRenContext->setTrgPath($sPath);
				
				$this->moveDirectory($oDirRenContext);
				if($sSrcName !== $sTrgName)
				{
					$sSrcPathName = 'DG' . $iIdTrgDelegation . '/' . $sPath . $sSrcName;
					$sTrgPathName = 'DG' . $iIdTrgDelegation . '/' . $sPath . $sTrgName;

					/*
					bab_debug(
						'sSrcPathName ==> ' . $sSrcPathName . "\n" .
						'sTrgPathName ==> ' . $sTrgPathName
					);
					//*/
					
					$this->renameSubDirectory($sSrcPathName, $sTrgPathName);
				}
			}
		}
		else
		{
			if(is_dir($sFullTrgPathName))
			{
				$oDirRenContext->setTrgPathName($sTrgPathName);
				$oDirRenContext->setSanitizedTrgPathName($sSanitizedTrgPathName);
				$oDirRenContext->setTrgName($sTrgName);
				$oDirRenContext->setTrgPath($sTrgPath);
				
				$this->moveDirectory($oDirRenContext);
			}
			else
			{
				bab_debug('Looser lamer !!!');
				return false;
			}
		}
	}
	
	private function renameDirectory(bab_directoryRenameContext $oDirRenContext)
	{
		$this->sPathName = $oDirRenContext->getSanitizedSrcPathName();
		
		if(!$this->setEnv($oDirRenContext->getSrcPathName()))
		{
			bab_debug('Path ==> ' . $oDirRenContext->getSrcPathName() . ' is not initialized');
			return false;
		}
		
		if(!$this->accessValid())
		{
			bab_debug('Path ==> ' . $oDirRenContext->getSrcPathName() . ' is not valid');
			return false;
		}
		
		if(canCreateFolder($this->getRelativePath()))
		{
			$sSanitizedSrcPathName	= (string) $oDirRenContext->getSanitizedSrcPathName();
			$sSanitizedTrgPathName	= (string) $oDirRenContext->getSanitizedTrgPathName();
			$sSrcName				= (string) $oDirRenContext->getSrcName();
			$sSrcPath				= (string) $oDirRenContext->getSrcPath();
			$sTrgName				= (string) $oDirRenContext->getTrgName();
			$sTrgPath				= (string) $oDirRenContext->getTrgPath();
			$sRelativePath			= (string) addEndSlash($sSrcPath);

			$bSuccess = BAB_FmFolderSet::rename($this->getRootFmPath(), $sRelativePath, $sSrcName, $sTrgName);
			if(false !== $bSuccess)
			{
				BAB_FolderFileSet::renameFolder($sRelativePath . $sSrcName . '/', $sTrgName, 'Y');
				BAB_FmFolderCliboardSet::rename($sRelativePath, $sSrcName, $sTrgName, 'Y');
				
				$oFolderSet			= bab_getInstance('BAB_FmFolderSet');
				$oNameField			= $oFolderSet->aField['sName'];
				$oRelativePathField	= $oFolderSet->aField['sRelativePath'];
				$oIdDgOwnerField	= $oFolderSet->aField['iIdDgOwner'];
				
				$oCriteria	= $oNameField->in($sSrcName);
				$oCriteria	= $oCriteria->_and($oRelativePathField->in($sRelativePath));
				$oCriteria	= $oCriteria->_and($oIdDgOwnerField->in($this->iIdObject));
				$oFolder	= $oFolderSet->get($oCriteria);
				
				if($oFolder instanceof BAB_FmFolder)
				{
					$oFolderSet->setName($sTrgName);
					$oFolderSet->save();
				}
			}
		}
		else
		{
			bab_debug('Looser lamer !!!');
		}
	}
	
	private function moveDirectory(bab_directoryRenameContext $oDirRenContext)
	{
		$this->sPathName = $oDirRenContext->getSanitizedTrgPathName();
		
		if(!$this->setEnv($oDirRenContext->getTrgPathName()))
		{
			bab_debug('Path ==> ' . $oDirRenContext->getTrgPathName() . ' is not initialized');
			return false;
		}
		
		if(!$this->accessValid())
		{
			bab_debug('Path ==> ' . $oDirRenContext->getTrgPathName() . ' is not valid');
			return false;
		}
		
		$sSanitizedSrcPathName	= $oDirRenContext->getSanitizedSrcPathName();
		$sSanitizedTrgPathName	= $oDirRenContext->getSanitizedTrgPathName();
		
		$oFmEnv					= bab_getInstance('BAB_FileManagerEnv');
		$iIdSrcRootFolder		= 0;
		$sSrcPath				= BAB_PathUtil::removeEndSlashes($sSanitizedSrcPathName);
		$bSrcPathIsCollective	= true;
		$iIdTrgRootFolder		= 0;
		$sTrgPath				= BAB_PathUtil::removeEndSlashes($oDirRenContext->getTrgPath());
		
		$oSrcRootFolder = BAB_FmFolderSet::getRootCollectiveFolder($sSanitizedSrcPathName);
		$oTrgRootFolder = BAB_FmFolderSet::getRootCollectiveFolder($oDirRenContext->getTrgPath());

		$iIdSrcRootFolder = $oSrcRootFolder->getId();
		$iIdTrgRootFolder = $oTrgRootFolder->getId();
		
		$oFmFolder = null;

		if(canPasteFolder($iIdSrcRootFolder, $sSrcPath, $bSrcPathIsCollective, $iIdTrgRootFolder, $sTrgPath))
		{
			//Nom du répertoire à coller
			$sName = getLastPath($sSrcPath); 
			
			//Emplacement du répertoire à coller
			$sSrcPathRelativePath = addEndSlash(removeLastPath($sSrcPath . '/'));
	
			$bSrcPathHaveVersioning = false;
			$bTrgPathHaveVersioning = false;
			$bSrcPathCollective		= false;
			
			//Récupération des informations concernant le répertoire source (i.e le répertoire à déplacer)
			{
				$oSrcFmFolder			= BAB_FmFolderSet::getFirstCollectiveFolder($sSanitizedSrcPathName);
				$iSrcIdOwner			= $oSrcFmFolder->getId();
				$bSrcPathHaveVersioning = ('Y' === $oSrcFmFolder->getVersioning());
				$bSrcPathCollective		= ((string) $sSrcPath . '/' === (string) $oSrcFmFolder->getRelativePath() . $oSrcFmFolder->getName() . '/');
			}
			
			$oFolderSet	= bab_getInstance('BAB_FmFolderSet');
			if($oFmEnv->userIsInCollectiveFolder())
			{
				//Récupération des informations concernant le répertoire cible (i.e le répertoire dans lequel le source est déplacé)
				$oTrgFmFolder = BAB_FmFolderSet::getFirstCollectiveFolder($this->getPathName());
				$iTrgIdOwner = $oTrgFmFolder->getId();
				$bTrgPathHaveVersioning = ('Y' === $oTrgFmFolder->getVersioning());
			}
			else if($oFmEnv->userIsInRootFolder())
			{
				$oIdDgOwner		= $oFolderSet->aField['iIdDgOwner'];
				$oName			= $oFolderSet->aField['sName'];
				$oRelativePath	= $oFolderSet->aField['sRelativePath'];
	
				$oCriteria = $oIdDgOwner->in($this->iIdObject);
				$oCriteria = $oCriteria->_and($oName->in($sName));
				$oCriteria = $oCriteria->_and($oRelativePath->in($sSrcPathRelativePath));
	
				$bSrcPathCollective = true;
	
				//bab_debug($oFolderSet->getSelectQuery($oCriteria));
				$oFmFolder = $oFolderSet->get($oCriteria);
				if(!is_null($oFmFolder))
				{
					//Le répertoire à coller est collectif
					$bTrgPathHaveVersioning = ('Y' === $oFmFolder->getVersioning());
				}
				else 
				{
					//Le répertoire à coller n'est pas collectif
					//comme on colle dans la racine il faut le faire 
					//devenir un répertoire collectif
					
					$oFmFolder = bab_getInstance('BAB_FmFolder');
					$oFmFolder->setName($sName);
					$oFmFolder->setRelativePath('');
					$oFmFolder->setActive('Y');
					$oFmFolder->setApprobationSchemeId(0);
					$oFmFolder->setDelegationOwnerId($this->iIdObject);
					$oFmFolder->setFileNotify('N');
					$oFmFolder->setHide('N');
					$oFmFolder->setAddTags('Y');
					$oFmFolder->setVersioning('N');
					$oFmFolder->setAutoApprobation('N');
				}
			}
	
			$sUploadPath = BAB_FileManagerEnv::getCollectivePath($this->iIdObject);
			
			$sFullSrcPath = realpath((string) $sUploadPath . $sSrcPath);
			$sFullTrgPath = realpath((string) $sUploadPath . $sTrgPath);
			
			//bab_debug('sFullSrcPath ==> ' . $sFullSrcPath . ' versioning ' . (($bSrcPathHaveVersioning) ? 'Yes' : 'No') . ' bSrcPathCollective ' . (($bSrcPathCollective) ? 'Yes' : 'No'));
			//bab_debug('sFullTrgPath ==> ' . $sFullTrgPath . ' versioning ' . (($bTrgPathHaveVersioning) ? 'Yes' : 'No'));
	
			//$sPath = mb_substr($sFullTrgPath, 0, mb_strlen($sFullSrcPath));
			//if($sPath !== $sFullSrcPath)
			{
				$bSrcValid = ((realpath(mb_substr($sFullSrcPath, 0, mb_strlen(realpath($sUploadPath)))) === (string) realpath($sUploadPath)) && is_readable($sFullSrcPath));
				$bTrgValid = ((realpath(mb_substr($sFullTrgPath, 0, mb_strlen(realpath($sUploadPath)))) === (string) realpath($sUploadPath)) && is_writable($sFullTrgPath));
				
				//bab_debug('bSrcValid ' . (($bSrcValid) ? 'Yes' : 'No'));
				//bab_debug('bTrgValid ' . (($bTrgValid) ? 'Yes' : 'No'));
				
				if($bSrcValid && $bTrgValid)
				{
					if(!is_null($oFmFolder))
					{
						if(true !== $oFmFolder->save())
						{
							$babBody->msgerror = bab_translate("Error");
							return;
						}
						$bTrgPathHaveVersioning = false;
						$iTrgIdOwner			= $oFmFolder->getId();
					}
					
					global $babDB, $babBody;
					$oFolderFileSet	= bab_getInstance('BAB_FolderFileSet');
					$oIdDgOwnerFile	= $oFolderFileSet->aField['iIdDgOwner'];
					$oGroup			= $oFolderFileSet->aField['sGroup'];
					$oPathName		= $oFolderFileSet->aField['sPathName'];
					
					$oFolderSet			= bab_getInstance('BAB_FmFolderSet');
					$oIdDgOwnerFolder	= $oFolderSet->aField['iIdDgOwner'];
					$oRelativePath		= $oFolderSet->aField['sRelativePath'];
					
					$sLastRelativePath = $sSrcPath . '/';
					$sNewRelativePath = ((mb_strlen(trim($sTrgPath)) > 0) ? 
						$sTrgPath . '/' : '') . getLastPath($sSrcPath) . '/';
						
					if(false === $bSrcPathCollective)
					{
						 if(false === $bTrgPathHaveVersioning)
						 {
							global $babDB;
							
							//Suppression des versions des fichiers pour les répertoires qui ne sont pas contenus dans des 
							//répertoires collectifs
							{
								//Sélection de tous les fichiers qui contiennent dans leurs chemins le répertoire à déplacer
								$oCriteriaFile = $oPathName->like($babDB->db_escape_like($sLastRelativePath) . '%');
								$oCriteriaFile = $oCriteriaFile->_and($oGroup->in('Y'));
								$oCriteriaFile = $oCriteriaFile->_and($oIdDgOwnerFile->in($this->iIdObject));
								
								//Sélection des répertoires collectifs
								$oCriteriaFolder = $oRelativePath->like($babDB->db_escape_like($sLastRelativePath) . '%');
								$oCriteriaFolder = $oCriteriaFolder->_and($oIdDgOwnerFolder->in($this->iIdObject));
								$oFolderSet->select($oCriteriaFolder);
								while(null !== ($oFmFolder = $oFolderSet->next()))
								{
									//exclusion des répertoires collectif (on ne touche pas à leurs versions)
									$oCriteriaFile = $oCriteriaFile->_and($oPathName->notLike(
										$babDB->db_escape_like($oFmFolder->getRelativePath() . $oFmFolder->getName() . '/') . '%'));
								}
								$oFolderFileSet->removeVersions($oCriteriaFile);
								
								$oFolderFileSet->select($oCriteriaFile);
								while(null !== ($oFolderFile = $oFolderFileSet->next()))
								{
									$oFolderFile->setMajorVer(1);
									$oFolderFile->setMinorVer(0);
									$oFolderFile->save();
								}
							}
						 }
					}								
	
					if(BAB_FmFolderSet::move($sUploadPath, $sLastRelativePath, $sNewRelativePath))
					{
						BAB_FolderFileSet::move($sLastRelativePath, $sNewRelativePath, 'Y');
						
						$oFmFolderCliboardSet = bab_getInstance('BAB_FmFolderCliboardSet');
						$oFmFolderCliboardSet->deleteEntry($sName, $sSrcPathRelativePath, 'Y');
						$oFmFolderCliboardSet->move($sLastRelativePath, $sNewRelativePath, 'Y');
					}
				}			
			}
			
			//$this->displayInfo();
		}
		else
		{
			//bab_debug('Looser lamer !!!');
		}
	}
	
	//Private tools function
	private function initRelativePath()
	{
		$aPathItem = explode('/', $this->sRelativePath);
		if(!is_array($aPathItem))
		{
			return;
		}
		
		$iCount = count($aPathItem);
		$iIndex = 0;
		
		while($iIndex < $iCount && !is_dir($this->sRootFmPath . $this->sRelativePath))
		{
			$iIndex++;
			$this->sRelativePath = BAB_PathUtil::addEndSlash(removeLastPath($this->sRelativePath));
		}
		
		$sRealPath = realpath($this->sRootFmPath . $this->sRelativePath);
		if(false === $sRealPath)
		{
			$this->sRelativePath = '';
			return;
		}
		
		$sRealPath = BAB_PathUtil::addEndSlash(str_replace('\\', '/', $sRealPath));
		
		$this->sRelativePath = mb_substr($sRealPath, mb_strlen($this->sRootFmPath));
	}
	
	private function setEnv($sPathName)
	{
		$iIdDelegation		= 0;
		$bSuccess			= false;	
		$iIdOldDelegation	= bab_getCurrentUserDelegation();
		$oFmEnv				= bab_getInstance('BAB_FileManagerEnv');
		
		$sPathName = BAB_PathUtil::addEndSlash(BAB_PathUtil::sanitize($sPathName));
		
		$aBuffer = array();
		if(preg_match('#^DG(\d+)(/)#', $sPathName, $aBuffer))
		{
			$iIdDelegation			= (int) $aBuffer[1];
			$sPath					= 'DG' . $iIdDelegation . '/';
			$this->iIdObject		= $iIdDelegation; 
			
			$this->sRelativePath	= mb_substr($sPathName, mb_strlen($sPath));
			$this->sUploadPath		= BAB_FmFolderHelper::getUploadPath();
			$this->sRootFmPath		= BAB_FileManagerEnv::getCollectivePath($iIdDelegation);
			
			$this->initRelativePath();
			
			if('' != $this->sRelativePath)
			{
				$sFolderName		= getFirstPath($this->sRelativePath);
				$oFolderSet			= bab_getInstance('BAB_FmFolderSet');
				$oNameField			= $oFolderSet->aField['sName'];
				$oRelativePathField	= $oFolderSet->aField['sRelativePath'];
				$oIdDgOwnerField	= $oFolderSet->aField['iIdDgOwner'];
				
				$oCriteria	= $oNameField->in($sFolderName);
				$oCriteria	= $oCriteria->_and($oRelativePathField->in(''));
				$oCriteria	= $oCriteria->_and($oIdDgOwnerField->in($iIdDelegation));
				$oFolder	= $oFolderSet->get($oCriteria);
				
				if($oFolder instanceof BAB_FmFolder)
				{
					$oFmEnv->sGr		= 'Y';					
					$oFmEnv->sPath		= BAB_PathUtil::removeEndSlashes($this->sRelativePath);		
					$oFmEnv->iIdObject	= $oFolder->getId();
					$bSuccess			= true;			
				}
			}
			else
			{//Si '' == $this->sRelativePath on ne fait rien 
				$oFmEnv->sGr		= '';					
				$oFmEnv->sPath		= '';		
				$oFmEnv->iIdObject	= 0;
				$bSuccess = true;
			}
		}
		else
		{
			//error	
			bab_debug('ERROR ==> ' . $sPathName);
		}
		
		if($bSuccess)
		{
			bab_setCurrentUserDelegation($iIdDelegation);
			$oFmEnv->init();
			bab_setCurrentUserDelegation($iIdOldDelegation);
		}
		return $bSuccess;
	}
	
	/**
	 * Must be called after the function setEnv
	 * because the function initPaths initialize
	 * the BAB_FileManagerEnv object
	 *
	 * @return bool
	 */
	private function accessValid()
	{
		$oFmEnv	= bab_getInstance('BAB_FileManagerEnv');
		return $oFmEnv->accessValid();
	}
		
	private function isDot($sName)
	{
		return ('.' === (string) $sName || '..' === (string) $sName);
	}
	
	private function isReservedName($sName)
	{
		return (strtolower(BAB_FVERSION_FOLDER) == strtolower($sName));
	}
	
	private function processPathName($sPathName, &$sSanitizedPathName)
	{
		if(0 === mb_strlen(trim($sPathName)))
		{
			return false;
		}
		
		$aBuffer = array();
		if(false === preg_match('#^DG(\d+)(/)#', $sPathName, $aBuffer))
		{
			return false;
		}
		
		$sPathName			= BAB_PathUtil::addEndSlash($sPathName);
		$sPathName			= str_replace('\\', '/', $sPathName);
		$sPathName			= removeFirstPath($sPathName);//Remove DGx
		$aPaths				= explode('/', $sPathName);
		$sSanitizedPathName	= '';
		
		if(is_array($aPaths) && count($aPaths) > 0)
		{
			$sPath = removeEndSlah($this->sRootFmPath);
			foreach($aPaths as $sPathItem)
			{
				if(0 === mb_strlen(trim($sPathItem)))
				{
					continue;	
				}
				
				$sPathItem = replaceInvalidFolderNameChar($sPathItem);
				
				if($this->isDot($sPathItem))
				{
					return false;
				}
				
				if($this->isReservedName($sPathItem))
				{
					return false;
				}
				
				$sSanitizedPathName .= $sPathItem . '/';
			}
			return true;
		}
		return false;
	}
	
	private function isPathNameCreatable()
	{
		if(0 === mb_strlen(trim($this->sPathName)))
		{
			return false;
		}
		
		$sPathName	= '';
		$aPaths		= explode('/', $this->sPathName);
		
		if(is_array($aPaths) && count($aPaths) > 0)
		{
			$sPath = removeEndSlah($this->sRootFmPath);
			foreach($aPaths as $sPathItem)
			{
				if(0 === mb_strlen(trim($sPathItem)))
				{
					continue;	
				}
				
				$sPathName	.= $sPathItem . '/';
				$sPath		.= '/' . $sPathItem;
				
				if(!is_dir($sPath))
				{
					if(!isStringSupportedByFileSystem($sPathItem))
					{
						return false;
					}
				}
			}
			return (0 !== mb_strlen($sPathName));
		}
		return false;
	}
	
	private function displayInfo()
	{
		bab_debug(
			'sUploadPath   ==> ' . $this->sUploadPath	. "\n" .
			'sRootFmPath   ==> ' . $this->sRootFmPath	. "\n" .
			'sRelativePath ==> ' . $this->sRelativePath	. "\n" .
			'sPathName     ==> ' . $this->sPathName	. "\n" .
			'iIdObject     ==> ' . $this->iIdObject
		);
	}
}




class bab_directoryRenameContext
{
	private $sTrgPathName			= null;
	private $sSanitizedTrgPathName	= null;
	private $sTrgName				= null;
	private $sTrgPath				= null;
	private $iIdTrgDelegation		= null;
	
	private $sSrcPathName			= null;
	private $sSanitizedSrcPathName	= null;
	private $sSrcName				= null;
	private $sSrcPath				= null;
	private $iIdSrcDelegation		= null;
	
	public function __construct()
	{
		
	}


	public function getTrgPathName()
	{
		return $this->sTrgPathName;	
	}
	
	public function setTrgPathName($sTrgPathName)
	{
		$this->sTrgPathName = $sTrgPathName;
	}
	
	public function getSanitizedTrgPathName()
	{
		return $this->sSanitizedTrgPathName;	
	}
	
	public function setSanitizedTrgPathName($sSanitizedTrgPathName)
	{
		$this->sSanitizedTrgPathName = $sSanitizedTrgPathName;
	}
	
	public function getTrgName()
	{
		return $this->sTrgName;	
	}
	
	public function setTrgName($sTrgName)
	{
		$this->sTrgName = $sTrgName;
	}
	
	public function getTrgPath()
	{
		return $this->sTrgPath;	
	}
	
	public function setTrgPath($sTrgPath)
	{
		$this->sTrgPath = $sTrgPath;
	}
	
	public function getTrgDelegationId()
	{
		return $this->iIdTrgDelegation;	
	}
	
	public function setTrgDelegationId($iIdTrgDelegation)
	{
		$this->iIdTrgDelegation = $iIdTrgDelegation;
	}

	public function getSrcPathName()
	{
		return $this->sSrcPathName;	
	}
	
	public function setSrcPathName($sSrcPathName)
	{
		$this->sSrcPathName = $sSrcPathName;
	}
	
	public function getSanitizedSrcPathName()
	{
		return $this->sSanitizedSrcPathName;	
	}
	
	public function setSanitizedSrcPathName($sSanitizedSrcPathName)
	{
		$this->sSanitizedSrcPathName = $sSanitizedSrcPathName;
	}
	
	public function getSrcName()
	{
		return $this->sSrcName;	
	}
	
	public function setSrcName($sSrcName)
	{
		$this->sSrcName = $sSrcName;
	}
	
	public function getSrcPath()
	{
		return $this->sSrcPath;	
	}
	
	public function setSrcPath($sSrcPath)
	{
		$this->sSrcPath = $sSrcPath;
	}
	
	public function getSrcDelegationId()
	{
		return $this->iIdSrcDelegation;	
	}
	
	public function setSrcDelegationId($iIdSrcDelegation)
	{
		$this->iIdSrcDelegation = $iIdSrcDelegation;
	}
}
?>