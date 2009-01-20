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
		return $this->oFolderSet->get($oCriteria);
    }

    
	private function getFirstCollectiveParentFolder($sRelativePath)
	{
		$sRelPath = $sRelativePath;
		if(!array_key_exists($sRelPath, $this->aPathCache))
		{	
			$this->aPathCache[$sRelPath] = null;
			
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
	
					do
					{
						$sFolderName = $aPath[$iIndex];
						unset($aPath[$iIndex]);
						$sRelativePath	= implode('/', $aPath);
	
						if('' !== $sRelativePath)
						{
							$sRelativePath .= '/';
						}
						
						$oCriteria	= $this->oNameField->in($sFolderName);
						$oCriteria	= $oCriteria->_and($this->oRelativePathField->like($babDB->db_escape_like($sRelativePath)));
						$oCriteria	= $oCriteria->_and($this->oIdDgOwnerField->in($this->iIdObject));
						$oFolder	= $this->oFolderSet->get($oCriteria);
						if(!is_null($oFolder))
						{
							$this->aPathCache[$sRelPath] = $oFolder; 
							$bStop = true;
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
		}
		return $this->aPathCache[$sRelPath];
	}
}


class bab_Directory
{
	private $sUploadPath	= null;
	private $sRootFmPath	= null;
	private $sRelativePath	= null;
	private $sFullPath		= null;
	private $iIdObject		= 0;
	
	public function __construct()
	{
		
	}
	

	public function getEntries($sPathName, $iFilter)
	{
		if(!$this->initPaths($sPathName))
		{
			bab_debug('Path ==> ' . $sPathName . ' is not initialized');
			return false;
		}
		
		if(!$this->pathValid())
		{
			bab_debug('Path ==> ' . $sPathName . ' is not valid');
			return false;
		}
		
		$iFilter |= bab_DirectoryFilter::DOT;
		$oBabDirIt = new bab_CollectiveDirIterator($this->sFullPath);
		$oBabDirIt->setFilter($iFilter);
		$oBabDirIt->setRelativePath($this->sRelativePath);
		$oBabDirIt->setObjectId($this->iIdObject);
		return $oBabDirIt; 
	}
	
	
	//Private tools function
	private function initPaths($sPathName)
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
			$this->sFullPath		= BAB_PathUtil::addEndSlash(BAB_PathUtil::sanitize($this->sRootFmPath . $this->sRelativePath));
			
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
		/*
		else if(preg_match('#^U(\d+)(/)#', $sPathName, $aBuffer))
		{
			$iIdUser = (int) $aBuffer[1];
			if($iIdUser > 0)
			{
				$sPath					= 'U' . $iIdUser . '/'; 
				$this->iIdObject		= $iIdUser; 
				$this->sRelativePath	= mb_substr($sPathName, mb_strlen($sPath));
				$this->sUploadPath		= BAB_FmFolderHelper::getUploadPath();
				$this->sRootFmPath		= $this->getPersonnalPath($iIdUser);
				$this->sFullPath		= BAB_PathUtil::addEndSlash(BAB_PathUtil::sanitize($this->sRootFmPath . $this->sRelativePath));
				return true;
			}
			else
			{
				//error	
				bab_debug('ERROR ==> ' . $sPathName);
			}
		}
		//*/
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
	 * Must be called after the function initPaths
	 * because the function initPaths initialize
	 * the BAB_FileManagerEnv object
	 *
	 * @return bool
	 */
	private function pathValid()
	{
		$oFmEnv	= bab_getInstance('BAB_FileManagerEnv');
		return $oFmEnv->accessValid();
	}
}
?>