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
require_once dirname(__FILE__) . '/fileincl.php';


/**
 * An extension of the SplFileInfo corresponding to ovidentia's filemanager files.
 * Standard isWritable and isReadable file are mapped to corresponding ACL.
 *
 * For now only work for collective folders.
 * 
 * Does *NOT* work for personal folders.
 * 
 * @since 7.0.0
 */
class bab_FileInfo extends SplFileInfo
{

	/**
	 * @var BAB_FmFolderFile
	 */
	private $fmFile = null;

	/**
	 * @var string
	 */
	private $fmPathname = null;


	/**
	 * Returns the number of files (directory or regular files) contained in the 
	 * this file (the file is not a directory 0 is returned).
	 * The number does not include the '.' and '..' directories.
	 * 
	 * @return int
	 */
	public function getItemCount()
	{
		if (!$this->isDir()) {
			return 0;
		}
		
		$oIt = new DirectoryIterator($this->getPathname());
		$iCount = 0;
		foreach ($oIt as $oFile) {
			$iCount++;
		}
		return $iCount - 2; // We do not count '.' and '..'.
	}

	/**
	 * Returns the FM pathname i.e. "DG0/Folder1/SubFolder1/file.txt".
	 *
	 * @return string
	 */
	public function getFmPathname()
	{
		if (!isset($this->fmPathname)) {
			$collectivePath = BAB_FmFolderHelper::getUploadPath() . BAB_FileManagerEnv::relativeFmCollectivePath;
			$aBuffer = 0;

			$fmPathname = substr($this->getPathname(), strlen($collectivePath));

			$this->fmPathname = BAB_PathUtil::sanitize($fmPathname);
		}
		return $this->fmPathname;
	}


	/**
	 * Returns the corresponding BAB_FmFolder object.
	 *
	 * You should preferably use bab_FileInfo::getFmFile() which is cached.
	 * @see bab_FileInfo::getFmFile()
	 *
	 * @return BAB_FmFolder
	 */
	private function getFmFolder()
	{
		$fmPathname = $this->getFmPathname();
		list($delegation) = explode('/', $fmPathname);
		$iIdDelegation = (int)substr($delegation, strlen(BAB_FileManagerEnv::delegationPrefix));

		$oFmFolderSet		= bab_getInstance('BAB_FmFolderSet');

		$oNameField			= $oFolderFileSet->aField['sName'];
		$oRelativePathField = $oFolderFileSet->aField['sRelativePath'];
		$oIdDgOwnerField	= $oFolderFileSet->aField['iIdDgOwner'];

		$oCriteria = $oNameField->in($this->getFilename());
		$oCriteria = $oCriteria->_and($oRelativePathField->in(dirname($this->getFmPathname() . '/')));
		$oCriteria = $oCriteria->_and($oIdDgOwnerField->in($iIdDelegation));

		return $oFmFolderSet->get($oCriteria);
	}


	/**
	 * Returns the corresponding bab_FolderFile object
	 *
	 * You should preferably use bab_FileInfo::getFmFile() which is cached.
	 * @see bab_FileInfo::getFmFile()
	 *
	 * @return BAB_FolderFile
	 */
	private function getFolderFile()
	{
		$fmPathname = $this->getFmPathname();
		list($delegation) = explode('/', $fmPathname);
		$iIdDelegation = (int)substr($delegation, strlen(BAB_FileManagerEnv::delegationPrefix));

		$oFolderFileSet		= bab_getInstance('BAB_FolderFileSet');

		$oNameField			= $oFolderFileSet->aField['sName'];
		$oPathName			= $oFolderFileSet->aField['sPathName'];
		$oIdDgOwnerField	= $oFolderFileSet->aField['iIdDgOwner'];
		$oGroup				= $oFolderFileSet->aField['sGroup'];

		$oCriteria = $oNameField->in($this->getFilename());
		$oCriteria = $oCriteria->_and($oPathName->in(dirname($this->getFmPathname() . '/')));
		$oCriteria = $oCriteria->_and($oIdDgOwnerField->in($iIdDelegation));

		return $oFolderFileSet->get($oCriteria);
	}


	/**
	 * Returns the corresponding BAB_FmFolderFile object.
	 * 
	 * @return BAB_FmFolderFile
	 */
	protected function getFmFile()
	{
		if (!isset($this->fmFile)) {
			if ($this->isDir()) {
				$this->fmFile = $this->getFmFolder();
			} else {
				$this->fmFile = $this->getFolderFile();
			}
		}
		return $this->fmFile;
	}


	/**
	 * @param open_mode[optional]
	 * @param use_include_path[optional]
	 * @param context[optional]
	 * 
	 * @return SplFileObject
	 */
	public function openFile($mode = 'r', $use_include_path = false)
	{
		if ($mode === 'r'
				&& $this->isReadable()) {
			return parent::openFile($mode, $use_include_path);
		}
		if (($mode === 'r+'
				|| $mode === 'w' || $mode === 'w+'
				|| $mode === 'a' || $mode === 'a+')
				&& $this->isWritable()) {
			return parent::openFile($mode, $use_include_path);
		}
		throw new RuntimeException();
	}

	/**
	 * Checks whether the user has write access on the file.
	 * 
	 * For a plain file, it means that the user can modify or delete the file.
	 * For a folder it means that the user can create files in the folder.
	 * 
	 * @return bool
	 */
	public function isWritable()
	{
		$isWritable = parent::isWritable();
		if ($isWritable) {
			$pathname = removeFirstPath($this->getFmPathname());
			$path = dirname($pathname);
			if ($this->isDir()) {
				$isWritable = ($isWritable && (canManage($path) || canUpload($path)));
			} else {
				$isWritable = ($isWritable && canManage($path));
			}
		}
		return $isWritable;
	}


	/**
	 * Checks whether the user has read access on the file.
	 * 
	 * For a plain file, it means that the user can download the file.
	 * For a folder it means that the user can browse files contained by the folder.
	 * 
	 * @return bool
	 */
	public function isReadable()
	{
		$isReadable = parent::isReadable();
		if ($isReadable) {
			$pathname = removeFirstPath($this->getFmPathname());
			$path = dirname($pathname);
			if ($this->isDir()) {
				$isReadable = ($isReadable && canBrowse($path));
			} else {
				$isReadable = ($isReadable&& (canManage($path) || canDownload($path)));
			}
		}
		return $isReadable;
	}


	/**
	 * Checks whether the file is versioned.
	 * 
	 * @return bool
	 */
	public function isVersioned()
	{
		if ($this->isDir()) {
			return false;
		}
		$fmFile = $this->getFmFile();
		return ($fmFile->getMajorVer() > 1 || $fmFile->getMinorVer() > 0);
	}


	/**
	 * Returns the version of the file.
	 * Version is '1.0' for not versioned files.
	 * 
	 * @return string			The version in the form '2.3'.
	 */
	public function getVersion()
	{
		if ($this->isDir()) {
			return false;
		}
		return $fmFile->getMajorVer() . '.' . $fmFile->getMinorVer();
	}
}




class bab_DirectorySortFlag
{
	/**
	 * Do not sort
	 * @var int
	 */
	const UNSORTED		=   0;
	/**
	 * Sort by name
	 * @var int
	 */
	const NAME			=   1;
	/**
	 * Sort by modification time.
	 * @var int
	 */
	const TIME			=   2;
	/**
	 * Sort by file size.
	 * @var int
	 */
	const SIZE			=   4;
	/**
	 * Sort by file mimetype.
	 * @var int
	 */
	const MIMETYPE		=   8;
	/**
	 * Sort directories first.
	 * @var int
	 */
	const DIRS_FIRST	=  16;
	/**
	 * Sort directories last.
	 * @var int
	 */
	const DIRS_LAST		=  32;
	/**
	 * Reverse sort order.
	 * @var int
	 */
	const REVERSED		=  64;
	/**
	 * Sort case-insensitively.
	 * @var int
	 */
	const IGNORE_CASE	= 128;	
}


class bab_DirectoryFilter
{
	/**
	 * Files '.' and '..'.
	 * @var int
	 */
	const DOT		= 1;
	/**
	 * Filter files
	 * @var int
	 */
	const FILE		= 2;
	/**
	 * Filter dirs
	 * @var int
	 */
	const DIR		= 4;
	/**
	 * Filter hidden files
	 * TODO Not functional yet
	 * @var int
	 */
	const HIDDEN	= 8;
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
 * This class is for collective folder.
 * 
 */
class bab_Directory
{
	private $sUploadPath	= null;
	private $sRootFmPath	= null;
	private $sRelativePath	= null;
	private $iIdDelegation	= 0;
	private $sPathName		= null;
	
	private $aError			= array();
	
	const RIGHT_DEPOSIT				= 1;
	const RIGHT_DEPOSIT_AND_CHILD	= 2;
	const RIGHT_DOWNLOAD			= 4;
	const RIGHT_DOWNLOAD_AND_CHILD	= 8;
	const RIGHT_UPDATE				= 16;
	const RIGHT_UPDATE_AND_CHILD	= 32;
	const RIGHT_MANAGE				= 64;
	const RIGHT_MANAGE_AND_CHILD	= 128;
	const RIGHT_NOTIFY				= 256;
	const RIGHT_NOTIFY_AND_CHILD	= 512;


	
	/**
	 * This function set right on a collective folder.
	 * If the folder is not in the database the function
	 * do nothing
	 *
	 * @param string	$sPathName  The path of the folder (ex: DG0:/Developpement/2/)
	 * @param int		$iBits		Field of bits to implement the rights
	 * @param int		$iIdGroup	The group identifier to which the rights apply
	 * 
	 * @return bool					True on success, false otherwise
	 */
	public function setRight($sPathName, $iBits, $iIdGroup)
	{
		$this->resetError();
		
		if(!$this->processPathName($sPathName, $this->sPathName))
		{
			return false;
		}
		
		if(!$this->setEnv($sPathName))
		{
			return false;
		}
		
		if(!$this->accessValid())
		{
			return false;
		}
		
		if(!canManage($this->sPathName))
		{
			$this->aError[] = bab_translate("Access denied");
			return false;
		}
		
		if($iIdGroup > BAB_ACL_GROUP_TREE)
		{
			$this->aError[]	= bab_translate('The specified group identifier is not valid.');
			return false;
		}
		

		require_once dirname(__FILE__) . '/grpincl.php';

		
		if(false === bab_isGroup($iIdGroup))
		{
			$this->aError[]	= bab_translate('The specified group identifier do not represent a group.');
			return false;
		}

		
		$sRelativePath	= '';
		$sName			= (string) getFirstPath($this->sPathName);
		if($sName . '/' !== (string) $this->sPathName)
		{
			$sRelativePath = (string) removeFirstPath($this->sPathName);
		}

		$oFolderSet			= bab_getInstance('BAB_FmFolderSet');
		$oNameField			= $oFolderSet->aField['sName'];
		$oRelativePathField	= $oFolderSet->aField['sRelativePath'];
		$oIdDgOwnerField	= $oFolderSet->aField['iIdDgOwner'];
		
		$oCriteria	= $oNameField->in($sName);
		$oCriteria	= $oCriteria->_and($oRelativePathField->in($sRelativePath));
		$oCriteria	= $oCriteria->_and($oIdDgOwnerField->in($this->getDelegationId()));
		$oFolder	= $oFolderSet->get($oCriteria);

		//bab_debug($oFolderSet->getSelectQuery($oCriteria));
		
		if(!($oFolder instanceof BAB_FmFolder))
		{
			$aSearch		= array('%name%', '%path%');
			$aReplace		= array($sName, $sRelativePath);
			$sMessage		= str_replace($aSearch, $aReplace, bab_translate("The folder named %name% with path %path% is not collective."));
			$this->aError[]	= $sMessage;
			return false;
		}
		
		require_once dirname(__FILE__) . '/../admin/acl.php';
		
		static $aRightsChild = null;
		if(!isset($aRightsChild))
		{
			$aRightsChild = array(
				bab_Directory::RIGHT_DEPOSIT_AND_CHILD => BAB_FMUPLOAD_GROUPS_TBL,
				bab_Directory::RIGHT_DOWNLOAD_AND_CHILD => BAB_FMDOWNLOAD_GROUPS_TBL,
				bab_Directory::RIGHT_UPDATE_AND_CHILD => BAB_FMUPDATE_GROUPS_TBL,
				bab_Directory::RIGHT_MANAGE_AND_CHILD => BAB_FMMANAGERS_GROUPS_TBL,
				bab_Directory::RIGHT_NOTIFY_AND_CHILD => BAB_FMNOTIFY_GROUPS_TBL
			);
		}
				
		foreach($aRights as $iBit => $sTable)
		{
			if($iBits & $iBit)
			{
				aclAdd($sTable, $iIdGroup, $oFolder->getId());
			}
		}
		
		static $aRights = null;
		if(!isset($aRights))
		{
			$aRights = array(
				bab_Directory::RIGHT_DEPOSIT => BAB_FMUPLOAD_GROUPS_TBL,
				bab_Directory::RIGHT_DOWNLOAD => BAB_FMDOWNLOAD_GROUPS_TBL,
				bab_Directory::RIGHT_UPDATE => BAB_FMUPDATE_GROUPS_TBL,
				bab_Directory::RIGHT_MANAGE => BAB_FMMANAGERS_GROUPS_TBL,
				bab_Directory::RIGHT_NOTIFY => BAB_FMNOTIFY_GROUPS_TBL,
			);
		}

		foreach($aRightsChild as $iBit => $sTable)
		{
			if($iBits & $iBit)
			{
				aclAdd($sTable, $iIdGroup + BAB_ACL_GROUP_TREE, $oFolder->getId());
			}
		}
	}

	/**
	 * This function unset right on a collective folder.
	 * If the folder is not in the database the function
	 * do nothing
	 *
	 * @param string	$sPathName  The path of the folder (ex: DG0:/Developpement/2/)
	 * @param int		$iBits		Field of bits to implement the rights
	 * @param int		$iIdGroup	The group identifier to which the rights apply
	 * 
	 * @return bool					True on success, false otherwise
	 */
	public function unsetRight($sPathName, $iBits, $iIdGroup)
	{
		$this->resetError();
		
		if(!$this->processPathName($sPathName, $this->sPathName))
		{
			return false;
		}
		
		if(!$this->setEnv($sPathName))
		{
			return false;
		}
		
		if(!$this->accessValid())
		{
			return false;
		}
		
		if(!canManage($this->sPathName))
		{
			$this->aError[] = bab_translate("Access denied");
			return false;
		}
		
		if($iIdGroup > BAB_ACL_GROUP_TREE)
		{
			$this->aError[]	= bab_translate('The specified group identifier is not valid.');
			return false;
		}
		
		require_once dirname(__FILE__) . '/grpincl.php';
		
		if(false === bab_isGroup($iIdGroup))
		{
			$this->aError[]	= bab_translate('The specified group identifier do not represent a group.');
			return false;
		}
		
		$sRelativePath	= '';
		$sName			= (string) getFirstPath($this->sPathName);
		if($sName . '/' !== (string) $this->sPathName)
		{
			$sRelativePath = (string) removeFirstPath($this->sPathName);
		}

		$oFolderSet			= bab_getInstance('BAB_FmFolderSet');
		$oNameField			= $oFolderSet->aField['sName'];
		$oRelativePathField	= $oFolderSet->aField['sRelativePath'];
		$oIdDgOwnerField	= $oFolderSet->aField['iIdDgOwner'];
		
		$oCriteria	= $oNameField->in($sName);
		$oCriteria	= $oCriteria->_and($oRelativePathField->in($sRelativePath));
		$oCriteria	= $oCriteria->_and($oIdDgOwnerField->in($this->getDelegationId()));
		$oFolder	= $oFolderSet->get($oCriteria);

		//bab_debug($oFolderSet->getSelectQuery($oCriteria));
		
		if(!($oFolder instanceof BAB_FmFolder))
		{
			$aSearch		= array('%name%', '%path%');
			$aReplace		= array($sName, $sRelativePath);
			$sMessage		= str_replace($aSearch, $aReplace, bab_translate("The folder named %name% with path %path% is not collective."));
			$this->aError[]	= $sMessage;
			return false;
		}
		
		require_once dirname(__FILE__) . '/../admin/acl.php';

		static $aRights = null;
		
		if(!isset($aRights))
		{
			$aRights = array(
				bab_Directory::RIGHT_DEPOSIT => BAB_FMUPLOAD_GROUPS_TBL,
				bab_Directory::RIGHT_DOWNLOAD => BAB_FMDOWNLOAD_GROUPS_TBL,
				bab_Directory::RIGHT_UPDATE => BAB_FMUPDATE_GROUPS_TBL,
				bab_Directory::RIGHT_MANAGE => BAB_FMMANAGERS_GROUPS_TBL,
				bab_Directory::RIGHT_NOTIFY => BAB_FMNOTIFY_GROUPS_TBL,
				bab_Directory::RIGHT_DEPOSIT_AND_CHILD => BAB_FMUPLOAD_GROUPS_TBL,
				bab_Directory::RIGHT_DOWNLOAD_AND_CHILD => BAB_FMDOWNLOAD_GROUPS_TBL,
				bab_Directory::RIGHT_UPDATE_AND_CHILD => BAB_FMUPDATE_GROUPS_TBL,
				bab_Directory::RIGHT_MANAGE_AND_CHILD => BAB_FMMANAGERS_GROUPS_TBL,
				bab_Directory::RIGHT_NOTIFY_AND_CHILD => BAB_FMNOTIFY_GROUPS_TBL
			);
		}

		foreach($aRights as $iBit => $sTable)
		{
			if($iBits & $iBit)
			{
				aclDelete($sTable, $oFolder->getId());
			}
		}
	}
	
	/**
	 * This function return the last error(s) 
	 *
	 * @return array
	 */
	public function getError()
	{
		return $this->aError;
	}
	
	/**
	 * This function reset the last error(s)
	 *
	 */
	public function resetError()
	{
		$this->aError = array();
	}
	
	/**
	 * This function return the upload path
	 *
	 * @return string
	 */
	public function getUploadPath()
	{
		return $this->sUploadPath;
	}
	
	/**
	 * This function return the root upload path.
	 * If the upload path is d:/Temp/Upload/ and the delegation is 0
	 * so the root fm path will be d:/Temp/Upload/fileManager/collectives/DG0/
	 * 
	 * @return string
	 */
	public function getRootFmPath()
	{
		return $this->sRootFmPath;
	}
	
	/**
	 * This function return the real relative path.
	 * If the current path is d:/Temp/Upload/fileManager/collectives/DG0/Developpement/1/1.1/1.1.1/
	 * so the relative path will be Developpement/1/1.1/1.1.1/ if the folder 1.1.1 exists
	 * and Developpement/1/1.1/ if the folder 1.1.1 does not exists and so on
	 * 
	 * @return string
	 */
	public function getRelativePath()
	{
		return $this->sRelativePath;
	}
	
	/**
	 * This function return the path name.
	 * If the current path is d:/Temp/Upload/fileManager/collectives/DG0/Developpement/1/1.1/1.1.1/
	 * so the path name will be Developpement/1/1.1/1.1.1/
	 * 
	 * @return string
	 */
	public function getPathName()
	{
		return $this->sPathName;
	}
	
	/**
	 * This function return the delegation identifier
	 *
	 * @return int
	 */
	public function getDelegationId()
	{
		return $this->iIdDelegation;
	}
	
	/**
	 * This function return the content of a folder
	 *
	 * @param string	$sPathName	The path of the folder (ex: DG0/Developpement/2/)
	 * @param int		$iExcludeFilter	(bab_DirectoryFilter value)
	 * 
	 * @return bab_CollectiveDirIterator
	 */
	public function getEntries($sPathName, $iExcludeFilter)
	{
		if(!$this->processPathName($sPathName, $this->sPathName))
		{
			return false;
		}
		
		if(!$this->setEnv($sPathName))
		{
			return false;
		}
		
		if(!$this->accessValid())
		{
			return false;
		}
		
		$iExcludeFilter |= bab_DirectoryFilter::DOT;
		$oBabDirIt = new bab_CollectiveDirIterator($this->getRootFmPath() . $this->getPathName());
		$oBabDirIt->setFilter($iExcludeFilter);
		$oBabDirIt->setRelativePath($this->sRelativePath);
		$oBabDirIt->setObjectId($this->getDelegationId());
		return $oBabDirIt; 
	}
	
	/**
	 * This function create a sub directory
	 *
	 * @param string $sPathName (ex: DG0/Developpement/1/1.1/)
	 * 
	 * @return bool
	 */
	public function createSubdirectory($sPathName)
	{
		$this->resetError();
		
		if(!$this->processPathName($sPathName, $this->sPathName))
		{
			return false;
		}
		
		if(!$this->setEnv($sPathName))
		{
			return false;
		}
		
		if(!$this->accessValid())
		{
			return false;
		}
			
		$oFmEnv	= &getEnvObject();
		if(!canCreateFolder($oFmEnv->sRelativePath))
		{
			$this->aError[] = bab_translate("Access denied");
			return false;
		}
		
		if(!$this->isPathNameCreatable())
		{
			$aSearch		= array('%path%');
			$aReplace		= array($sPathName);
			$sMessage		= str_replace($aSearch, $aReplace, bab_translate("The pathName %name% is not creatable."));
			$this->aError[]	= $sMessage;
			return false;
		}
		
		$sFullPathName = $this->getRootFmPath() . $this->getPathName();
		if(!BAB_FmFolderHelper::createDirectory($sFullPathName))
		{
			$this->aError[]	= bab_translate("Error in folder creation process.");
			return false;
		}

		if($oFmEnv->userIsInRootFolder())
		{
			$oFmFolder = new BAB_FmFolder();
			$oFmFolder->setActive('Y');
			$oFmFolder->setApprobationSchemeId(0);
			$oFmFolder->setAutoApprobation('N');
			$oFmFolder->setDelegationOwnerId($this->getDelegationId());
			$oFmFolder->setFileNotify('N');
			$oFmFolder->setHide('N');
			$oFmFolder->setName(removeEndSlashes($this->getPathName()));
			$oFmFolder->setAddTags('Y');
			$oFmFolder->setRelativePath('');
			$oFmFolder->setVersioning('N');
			$oFmFolder->setAutoApprobation('N');
			if(false === $oFmFolder->save())
			{
				rmdir($sFullPathName);
				$this->aError[]	= bab_translate("The folder can not be created in the database.");
				return false;
			}
		}
		return true;
	}
	
	/**
	 * This function delete a sub directory
	 *
	 * @param string $sPathName (ex: DG0/Developpement/1/1.1/)
	 * 
	 * @return bool
	 */
	public function deleteSubdirectory($sPathName)
	{
		$this->resetError();
		
		if(!$this->processPathName($sPathName, $this->sPathName))
		{
			return false;
		}
		
		if(!$this->setEnv($sPathName))
		{
			return false;
		}
	
		if(!$this->accessValid())
		{
			return false;
		}
				
		$oFmEnv	= &getEnvObject();
		if(!canCreateFolder($oFmEnv->sRelativePath))
		{
			$this->aError[] = bab_translate("Access denied");
			return false;
		}
		
		$sFullPathName = $this->getRootFmPath() . $this->getPathName();
		if(!is_dir($sFullPathName))
		{
			$aSearch		= array('%folder%');
			$aReplace		= array($sPathName);
			$sMessage		= str_replace($aSearch, $aReplace, bab_translate("The folder %folder% does not exist."));
			$this->aError[]	= $sMessage;
			return false;
		}

		//Just to be sure that the folder DGx is not deleted
		if($sFullPathName == $this->getRootFmPath())
		{
			$aSearch		= array('%folder%');
			$aReplace		= array($sPathName);
			$sMessage		= str_replace($aSearch, $aReplace, bab_translate("The folder %folder% is not deletable."));
			$this->aError[]	= $sMessage;
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
		$oCriteria	= $oCriteria->_and($oIdDgOwnerField->in($this->getDelegationId()));
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
		
		return (!is_dir($sFullPathName));
	}

	/**
	 * This function rename a sub directory
	 *
	 * @param string $sSrcPathName (ex: DG0/Developpement/1/1.1/)
	 * @param string $sTrgPathName (ex: DG0/Developpement/1/1.2/)
	 * 
	 * @return bool
	 */
	public function renameSubDirectory($sSrcPathName, $sTrgPathName)
	{
		$this->resetError();
		
		$sSrcPathName = BAB_PathUtil::addEndSlash(BAB_PathUtil::sanitize($sSrcPathName));
		$sTrgPathName = BAB_PathUtil::addEndSlash(BAB_PathUtil::sanitize($sTrgPathName));
		
		$sSanitizedTrgPathName = '';
		if(!$this->processPathName($sTrgPathName, $sSanitizedTrgPathName))
		{
			return false;
		}
		
		$sSanitizedSrcPathName = '';
		if(!$this->processPathName($sSrcPathName, $sSanitizedSrcPathName))
		{
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
			$this->aError[]	= bab_translate("The source folder name is empty.");
			return false;
		}
		
		if(0 === mb_strlen($sTrgName))
		{
			$this->aError[]	= bab_translate("The target folder name is empty.");
			return false;
		}
		
		if(1 !== preg_match('#^DG(\d+)(/)#', $sTrgPathName, $aBuffer))
		{
			$aSearch		= array('%path%');
			$aReplace		= array($sTrgPathName);
			$sMessage		= str_replace($aSearch, $aReplace, bab_translate("The pathName %path% is not valid, it should start with DGx/."));
			$this->aError[]	= $sMessage;
			return false;
		}
		
		$iIdTrgDelegation	= (int) $aBuffer[1];
		$sFullTrgPathName	= BAB_FileManagerEnv::getCollectivePath($iIdTrgDelegation);
		$sFullTrgPathName	.= 	$sSanitizedTrgPathName;
		
		if(1 !== preg_match('#^DG(\d+)(/)#', $sSrcPathName, $aBuffer))
		{
			$aSearch		= array('%path%');
			$aReplace		= array($sSrcPathName);
			$sMessage		= str_replace($aSearch, $aReplace, bab_translate("The pathName %path% is not valid, it should start with DGx/."));
			$this->aError[]	= $sMessage;
			return false;
		}
		
		$iIdSrcDelegation = (int) $aBuffer[1];
		
		if($iIdTrgDelegation !== $iIdSrcDelegation)
		{
			$this->aError[]	= bab_translate("The source and the target folders are not not in the same delegation.");
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
				
				return $this->renameDirectory($oDirRenContext);
			}
			else
			{
				$sPath = canonizePath(removeLastPath($sSanitizedTrgPathName));
				$oDirRenContext->setTrgPathName(canonizePath('DG' . $iIdTrgDelegation . '/' . $sPath . $sSrcName));
				$oDirRenContext->setSanitizedTrgPathName($sPath . $sSrcName . '/');
				$oDirRenContext->setTrgName($sSrcName);
				$oDirRenContext->setTrgPath($sPath);
				
				if($this->moveDirectory($oDirRenContext))
				{
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
						
						return $this->renameSubDirectory($sSrcPathName, $sTrgPathName);
					}
					return true;
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
				
				return $this->moveDirectory($oDirRenContext);
			}
		}
		return false;
	}
	
	/**
	 * This function import a file into the file manager
	 *
	 * @param string $sFullSrcFileName (ex: d:/Temp/Upload/readme.txt)
	 * @param string $sPathName (ex: DG0/Folder/)
	 * 
	 * @return bool
	 */
	public function importFile($sFullSrcFileName, $sPathName)
	{
		require_once dirname(__FILE__) . '/uploadincl.php';

		$this->resetError();
		
		if(!$this->processPathName($sPathName, $this->sPathName))
		{
			return false;
		}
		
		if(0 === mb_strlen($this->sPathName))
		{
			$this->aError[]	= bab_translate("The pathName is empty");
			return false;
		}
		
		if(!$this->setEnv($sPathName))
		{
			return false;
		}
		
		if(!$this->accessValid())
		{
			return false;
		}
			
		$oFmEnv	= &getEnvObject();
		if(!canManage($oFmEnv->sRelativePath) && !canDownload($oFmEnv->sRelativePath))
		{
			$this->aError[]	= bab_translate("Access denied");
			return false;
		}
		
		if(!$this->canImportFile($sFullSrcFileName, $this->getDelegationId(), $this->getPathName()))
		{
			return false;
		}
		
		$oFmFolder = BAB_FmFolderSet::getFirstCollectiveFolder($this->getPathName());
		if(!($oFmFolder instanceof BAB_FmFolder))
		{
			$this->aError[]	= bab_translate("Error: Unable to retrieve the first parent collective folder");
			return false;
		}
		
		$oFileHandler = new bab_fileHandler(BAB_FILEHANDLER_MOVE, $sFullSrcFileName); 
		if(!($oFileHandler instanceof bab_fileHandler))
		{
			$this->aError[]	= bab_translate("Error: Unable to instance bab_fileHandler");
			return false;
		}
		
		/*
		$aPathParts		= pathinfo($sFullSrcFileName);
		$sFileName		= replaceInvalidFolderNameChar($aPathParts['basename']);
		$sPathName		= BAB_PathUtil::addEndSlash($aPathParts['dirname']);
		//*/
		
		//*
		$sFileName		= basename($sFullSrcFileName);
		$sFullPathName	= $this->getRootFmPath() . $this->getPathName() . $sFileName;
		if(false === $oFileHandler->import($sFullPathName))
		{
			$this->aError[]	= bab_translate("Error: Unable to import the file");
			return false;
		}
		//*/
		
		//$this->displayInfo();
		
		require_once dirname(__FILE__) . '/indexincl.php';
		$iIndexStatus = bab_indexOnLoadFiles(array($sFullPathName), 'bab_files');

		$oFolderFile = bab_getInstance('BAB_FolderFile');
		$oFolderFile->setName($sFileName);
		$oFolderFile->setPathName($this->getPathName());
		
		$oFolderFile->setOwnerId($oFmFolder->getId());
		$oFolderFile->setGroup('Y');
		$oFolderFile->setCreationDate(date("Y-m-d H:i:s"));
		$oFolderFile->setAuthorId($GLOBALS['BAB_SESS_USERID']);
		$oFolderFile->setModifiedDate(date("Y-m-d H:i:s"));
		$oFolderFile->setModifierId($GLOBALS['BAB_SESS_USERID']);
		$oFolderFile->setConfirmed('Y');
		
		$oFolderFile->setDescription('');
		$oFolderFile->setLinkId(0);
		$oFolderFile->setReadOnly('N');
		$oFolderFile->setState('');
		$oFolderFile->setHits(0);
		$oFolderFile->setFlowApprobationInstanceId(0);
		$oFolderFile->setFolderFileVersionId(0);
		$oFolderFile->setMajorVer(1);
		$oFolderFile->setMinorVer(0);
		$oFolderFile->setCommentVer('');
		$oFolderFile->setStatusIndex($iIndexStatus);
		$oFolderFile->setDelegationOwnerId($this->getDelegationId());
		
		if(false === $oFolderFile->save())
		{
			$this->aError[]	= bab_translate("Error: Unable to create the file in the database");
			unlink($sFullPathName);
			return false;
		}
		
		$iIdFile = $oFolderFile->getId();
		$oFolderFile->setId(null); //bab_getInstance
		
		$oFolderFileLog = new BAB_FolderFileLog();
		$oFolderFileLog->setIdFile($iIdFile);
		$oFolderFileLog->setCreationDate(date("Y-m-d H:i:s"));
		$oFolderFileLog->setAuthorId($GLOBALS['BAB_SESS_USERID']);
		$oFolderFileLog->setAction(BAB_FACTION_INITIAL_UPLOAD);
		$oFolderFileLog->setComment(bab_translate("Initial upload"));
		$oFolderFileLog->setVersion('1.0');
		$oFolderFileLog->save();
		
		if(BAB_INDEX_STATUS_INDEXED === $iIndexStatus)
		{
			$obj = new bab_indexObject('bab_files');
			$obj->setIdObjectFile($sFullPathName, $iIdFile, $oFmFolder->getId());
		}
		
		if(notifyApprovers($iIdFile, $oFmFolder->getId()) && 'Y' == $oFmFolder->getFileNotify())
		{
			fileNotifyMembers($oFolderFile->getName(), $oFolderFile->getPathName(), 
				$oFmFolder->getId(), bab_translate("A new file has been uploaded"));
		}
		return true;
	}
	
	/**
	 * This function rename a file
	 *
	 * @param string $sSrcPathName	The source pathName (ex: DG0/Developpement/readme.txt)
	 * @param string $sTrgPathName	The target pathName (ex: DG0/Developpement/readme1.txt)
	 * 
	 * @return bool
	 */
	public function renameFile($sSrcPathName, $sTrgPathName)
	{
		$this->resetError();
		
		//Recuperation des noms de fichiers
		$sSrcName = (string) getLastPath($sSrcPathName);
		$sTrgName = (string) getLastPath($sTrgPathName);
		
		//Recuperation des chemins sans les noms de fichiers
		$sSrcPathName = (string) addEndSlash(removeLastPath($sSrcPathName));
		$sTrgPathName = (string) addEndSlash(removeLastPath($sTrgPathName));
		
		$sSrcPathName = BAB_PathUtil::sanitize($sSrcPathName);
		$sTrgPathName = BAB_PathUtil::sanitize($sTrgPathName);

		/*
		bab_debug(
			'sSrcPathName ==> ' . $sSrcPathName . "\n" .
			'sTrgPathName ==> ' . $sTrgPathName
		);
		//*/
		
		$sSanitizedTrgPathName = '';
		if(!$this->processPathName($sTrgPathName, $sSanitizedTrgPathName))
		{
			return false;
		}
		
		$sSanitizedSrcPathName = '';
		if(!$this->processPathName($sSrcPathName, $sSanitizedSrcPathName))
		{
			return false;
		}
		
		if(0 === mb_strlen($sSrcName))
		{
			$this->aError[]	= bab_translate("The source file name is empty.");
			return false;
		}
		
		if(0 === mb_strlen($sTrgName))
		{
			$this->aError[]	= bab_translate("The target file name is empty.");
			return false;
		}
		
		$aBuffer = array();		
		if(1 !== preg_match('#^DG(\d+)(/)#', $sTrgPathName, $aBuffer))
		{
			$aSearch		= array('%path%');
			$aReplace		= array($sTrgPathName);
			$sMessage		= str_replace($aSearch, $aReplace, bab_translate("The pathName %path% is not valid, it should start with DGx/."));
			$this->aError[]	= $sMessage;
			return false;
		}
		
		$iIdTrgDelegation = (int) $aBuffer[1];
		
		if(1 !== preg_match('#^DG(\d+)(/)#', $sSrcPathName, $aBuffer))
		{
			$aSearch		= array('%path%');
			$aReplace		= array($sSrcPathName);
			$sMessage		= str_replace($aSearch, $aReplace, bab_translate("The pathName %path% is not valid, it should start with DGx/."));
			$this->aError[]	= $sMessage;
			return false;
		}
		
		$iIdSrcDelegation = (int) $aBuffer[1];
		
		if($iIdTrgDelegation !== $iIdSrcDelegation)
		{
			$this->aError[]	= bab_translate("The source and the target file are not not in the same delegation.");
			return false;
		}
		
		$sSrcPath = $sSanitizedSrcPathName;
		$sTrgPath = $sSanitizedTrgPathName;
		
		$oDirRenContext = new bab_directoryRenameContext();
	
		$oDirRenContext->setSrcPathName($sSrcPathName);
		$oDirRenContext->setSanitizedSrcPathName($sSanitizedSrcPathName);
		$oDirRenContext->setSrcName($sSrcName);
		$oDirRenContext->setSrcPath($sSrcPath);
		$oDirRenContext->setSrcDelegationId($iIdSrcDelegation);
		
		$oDirRenContext->setTrgDelegationId($iIdTrgDelegation);
		
		/*
		bab_debug(
			'sSrcPathName     ==> ' . $sSrcPathName . "\n" .
			'sTrgPathName     ==> ' . $sTrgPathName . "\n" .
			'sSrcName         ==> ' . $sSrcName . "\n" .
			'sSrcPath         ==> ' . $sSrcPath . "\n" .
			'sTrgName         ==> ' . $sTrgName . "\n" .
			'sTrgPath         ==> ' . $sTrgPath
		);		
		//*/
		
		//$this->displayInfo();
		
		if($sSrcPath === $sTrgPath)
		{
			if($sSrcName !== $sTrgName)
			{
				$oDirRenContext->setTrgPathName($sTrgPathName);
				$oDirRenContext->setSanitizedTrgPathName($sSanitizedTrgPathName);
				$oDirRenContext->setTrgName($sTrgName);
				$oDirRenContext->setTrgPath($sTrgPath);
				
				//bab_debug('RenameFile');
				//bab_debug($oDirRenContext);
				
				return $this->fileRename($oDirRenContext);
			}
		}
		else
		{
			$oDirRenContext->setTrgPathName(canonizePath('DG' . $iIdTrgDelegation . '/' . $sSanitizedTrgPathName) . $sSrcName);
			$oDirRenContext->setSanitizedTrgPathName($sSanitizedTrgPathName);
			$oDirRenContext->setTrgName($sSrcName);
			$oDirRenContext->setTrgPath($sSanitizedTrgPathName);
			
			//bab_debug('MoveFile');
			//bab_debug($oDirRenContext);
			
			if($this->fileMove($oDirRenContext))
			{
				if($sSrcName !== $sTrgName)
				{
					$oDirRenContext->setTrgPathName($sTrgPathName);
					$oDirRenContext->setSanitizedTrgPathName($sSanitizedTrgPathName);
					$oDirRenContext->setTrgName($sTrgName);
					$oDirRenContext->setTrgPath($sTrgPath);
					
					$sSrcPathName = 'DG' . $iIdTrgDelegation . '/' . $sSanitizedTrgPathName . $sSrcName;
					$sTrgPathName = 'DG' . $iIdTrgDelegation . '/' . $sSanitizedTrgPathName . $sTrgName;
	
					/*
					bab_debug(
						'sSrcPathName ==> ' . $sSrcPathName . "\n" .
						'sTrgPathName ==> ' . $sTrgPathName
					);
					//*/
						
					return $this->renameFile($sSrcPathName, $sTrgPathName);
				}
				return true;
			}
		}
		return false;
	}
	
	/**
	 * This function delete a file
	 *
	 * @param string $sPathName	The pathName (ex: DG0/D�veloppement/readme.txt)
	 * 
	 * @return bool
	 */
	public function deleteFile($sPathName)
	{
		$this->resetError();
		
		//Recuperation du nom du fichier
		$sFileName = (string) getLastPath($sPathName);
		
		//Recuperation du chemin sans le nom de fichier
		$sPathName = (string) addEndSlash(removeLastPath($sPathName));
		$sPathName = BAB_PathUtil::sanitize($sPathName);
		
		if(!$this->processPathName($sPathName, $this->sPathName))
		{
			return false;
		}
		
		if(!$this->setEnv($sPathName))
		{
			return false;
		}
		
		if(!$this->accessValid())
		{
			return false;
		}
		
		$sFullPathName = (string) $this->getRootFmPath() . $this->sPathName . $sFileName;
		$oSrcFile = new SplFileInfo($sFullPathName);
		
		if(!$oSrcFile->isFile())
		{
			$aSearch		= array('%fileName%');
			$aReplace		= array($sFullPathName);
			$sMessage		= str_replace($aSearch, $aReplace, bab_translate("The file %fileName% does not exist."));
			$this->aError[]	= $sMessage;
			return false;
		}
		
		$oFmEnv			= &getEnvObject();
		$oFolderFileSet	= bab_getInstance('BAB_FolderFileSet');
		$oName			= $oFolderFileSet->aField['sName'];
		$oPathName		= $oFolderFileSet->aField['sPathName'];
		$oGroup			= $oFolderFileSet->aField['sGroup'];
		$oIdOwner		= $oFolderFileSet->aField['iIdOwner'];
		$oIdDgOwner		= $oFolderFileSet->aField['iIdDgOwner'];
		
		$oCriteria		= $oName->in($sFileName);
		$oCriteria		= $oCriteria->_and($oPathName->in($this->sPathName));
		$oCriteria		= $oCriteria->_and($oGroup->in('Y'));
		$oCriteria		= $oCriteria->_and($oIdOwner->in($oFmEnv->oFmFolder->getId()));
		$oCriteria		= $oCriteria->_and($oIdDgOwner->in($this->getDelegationId()));

		//bab_debug($oFolderFileSet->getSelectQuery($oCriteria));
		
		$oFolderFile = $oFolderFileSet->get($oCriteria);
		if(!($oFolderFile instanceof BAB_FolderFile))
		{
			$this->aError[]	= bab_translate("Error: cannot get the file from the database");
			return false;
		}
		
		if(!bab_isAccessValid(BAB_FMMANAGERS_GROUPS_TBL, $oFolderFile->getOwnerId()))
		{
			$this->aError[]	= bab_translate("Access denied");
			return false;
		}
		
		$oFolderFileSet = new BAB_FolderFileSet();
		$oId = $oFolderFileSet->aField['iId'];
		$oFolderFileSet->remove($oId->in($oFolderFile->getId()));
		return true;
	}
	
	//Private tools function
	private function renameDirectory(bab_directoryRenameContext $oDirRenContext)
	{
		$this->sPathName = $oDirRenContext->getSanitizedSrcPathName();
		
		if(!$this->setEnv($oDirRenContext->getSrcPathName()))
		{
			return false;
		}
		
		if(!$this->accessValid())
		{
			return false;
		}
		
		if(!canCreateFolder($this->getRelativePath()))
		{
			$this->aError[]	= bab_translate("Access denied");
			return false;
		}
		
		$sSanitizedSrcPathName	= (string) $oDirRenContext->getSanitizedSrcPathName();
		$sSanitizedTrgPathName	= (string) $oDirRenContext->getSanitizedTrgPathName();
		$sSrcName				= (string) $oDirRenContext->getSrcName();
		$sSrcPath				= (string) $oDirRenContext->getSrcPath();
		$sTrgName				= (string) $oDirRenContext->getTrgName();
		$sTrgPath				= (string) $oDirRenContext->getTrgPath();
		$sRelativePath			= (string) addEndSlash($sSrcPath);

		$bSuccess = BAB_FmFolderSet::rename($this->getRootFmPath(), $sRelativePath, $sSrcName, $sTrgName);
		if(false === $bSuccess)
		{
			$this->aError[]	= bab_translate("Error in the rename directory process.");
			return false;
		}
		
		BAB_FolderFileSet::renameFolder($sRelativePath . $sSrcName . '/', $sTrgName, 'Y');
		BAB_FmFolderCliboardSet::rename($sRelativePath, $sSrcName, $sTrgName, 'Y');
		
		$oFolderSet			= bab_getInstance('BAB_FmFolderSet');
		$oNameField			= $oFolderSet->aField['sName'];
		$oRelativePathField	= $oFolderSet->aField['sRelativePath'];
		$oIdDgOwnerField	= $oFolderSet->aField['iIdDgOwner'];
		
		$oCriteria	= $oNameField->in($sSrcName);
		$oCriteria	= $oCriteria->_and($oRelativePathField->in($sRelativePath));
		$oCriteria	= $oCriteria->_and($oIdDgOwnerField->in($this->getDelegationId()));
		$oFolder	= $oFolderSet->get($oCriteria);
		
		if($oFolder instanceof BAB_FmFolder)
		{
			$oFolderSet->setName($sTrgName);
			$oFolderSet->save();
		}
		return true;
	}
	
	private function moveDirectory(bab_directoryRenameContext $oDirRenContext)
	{
		$this->sPathName = $oDirRenContext->getSanitizedTrgPathName();
		
		if(!$this->setEnv($oDirRenContext->getTrgPathName()))
		{
			return false;
		}
		
		if(!$this->accessValid())
		{
			return false;
		}
		
		$sSanitizedSrcPathName	= $oDirRenContext->getSanitizedSrcPathName();
		$sSanitizedTrgPathName	= $oDirRenContext->getSanitizedTrgPathName();
		
		$oFmEnv					= &getEnvObject();
		$iIdSrcRootFolder		= 0;
		$sSrcPath				= BAB_PathUtil::removeEndSlashes($sSanitizedSrcPathName);
		$bSrcPathIsCollective	= true;
		$iIdTrgRootFolder		= 0;
		$sTrgPath				= BAB_PathUtil::removeEndSlashes($oDirRenContext->getTrgPath());
		
		$oSrcRootFolder = BAB_FmFolderSet::getRootCollectiveFolder($sSanitizedSrcPathName);
		$oTrgRootFolder = BAB_FmFolderSet::getRootCollectiveFolder($oDirRenContext->getTrgPath());

		if($oSrcRootFolder instanceof BAB_FmFolder)
		{
			$iIdSrcRootFolder = $oSrcRootFolder->getId();
		}
		
		if($oTrgRootFolder instanceof BAB_FmFolder)
		{
			$iIdTrgRootFolder = $oTrgRootFolder->getId();
		}
		
		$oFmFolder = null;

		if(canPasteFolder($iIdSrcRootFolder, $sSrcPath, $bSrcPathIsCollective, $iIdTrgRootFolder, $sTrgPath))
		{
			//Nom du repertoire a coller
			$sName = getLastPath($sSrcPath); 
			
			//Emplacement du repertoire a coller
			$sSrcPathRelativePath = addEndSlash(removeLastPath($sSrcPath . '/'));
	
			$bSrcPathHaveVersioning = false;
			$bTrgPathHaveVersioning = false;
			$bSrcPathCollective		= false;
			
			//Recuperation des informations concernant le repertoire source (i.e le repertoire a deplacer)
			{
				$oSrcFmFolder			= BAB_FmFolderSet::getFirstCollectiveFolder($sSanitizedSrcPathName);
				$iSrcIdOwner			= $oSrcFmFolder->getId();
				$bSrcPathHaveVersioning = ('Y' === $oSrcFmFolder->getVersioning());
				$bSrcPathCollective		= ((string) $sSrcPath . '/' === (string) $oSrcFmFolder->getRelativePath() . $oSrcFmFolder->getName() . '/');
			}
			
			$oFolderSet	= bab_getInstance('BAB_FmFolderSet');
			if($oFmEnv->userIsInCollectiveFolder())
			{
				//Recuperation des informations concernant le repertoire cible (i.e le repertoire dans lequel le source est deplace)
				$oTrgFmFolder = BAB_FmFolderSet::getFirstCollectiveFolder($this->getPathName());
				$iTrgIdOwner = $oTrgFmFolder->getId();
				$bTrgPathHaveVersioning = ('Y' === $oTrgFmFolder->getVersioning());
			}
			else if($oFmEnv->userIsInRootFolder())
			{
				$oIdDgOwner		= $oFolderSet->aField['iIdDgOwner'];
				$oName			= $oFolderSet->aField['sName'];
				$oRelativePath	= $oFolderSet->aField['sRelativePath'];
	
				$oCriteria = $oIdDgOwner->in($this->getDelegationId());
				$oCriteria = $oCriteria->_and($oName->in($sName));
				$oCriteria = $oCriteria->_and($oRelativePath->in($sSrcPathRelativePath));
	
				$bSrcPathCollective = true;
	
				//bab_debug($oFolderSet->getSelectQuery($oCriteria));
				$oFmFolder = $oFolderSet->get($oCriteria);
				if(!is_null($oFmFolder))
				{
					//Le repertoire a coller est collectif
					$bTrgPathHaveVersioning = ('Y' === $oFmFolder->getVersioning());
				}
				else 
				{
					//Le repertoire a coller n'est pas collectif
					//comme on colle dans la racine il faut le faire 
					//devenir un repertoire collectif
					
					$oFmFolder = bab_getInstance('BAB_FmFolder');
					$oFmFolder->setName($sName);
					$oFmFolder->setRelativePath('');
					$oFmFolder->setActive('Y');
					$oFmFolder->setApprobationSchemeId(0);
					$oFmFolder->setDelegationOwnerId($this->getDelegationId());
					$oFmFolder->setFileNotify('N');
					$oFmFolder->setHide('N');
					$oFmFolder->setAddTags('Y');
					$oFmFolder->setVersioning('N');
					$oFmFolder->setAutoApprobation('N');
				}
			}
	
			$sUploadPath = BAB_FileManagerEnv::getCollectivePath($this->getDelegationId());
			
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
							
							//Suppression des versions des fichiers pour les repertoires qui ne sont pas contenus dans des 
							//repertoires collectifs
							{
								//Selection de tous les fichiers qui contiennent dans leurs chemins le repertoire a deplacer
								$oCriteriaFile = $oPathName->like($babDB->db_escape_like($sLastRelativePath) . '%');
								$oCriteriaFile = $oCriteriaFile->_and($oGroup->in('Y'));
								$oCriteriaFile = $oCriteriaFile->_and($oIdDgOwnerFile->in($this->getDelegationId()));
								
								//Selection des repertoires collectifs
								$oCriteriaFolder = $oRelativePath->like($babDB->db_escape_like($sLastRelativePath) . '%');
								$oCriteriaFolder = $oCriteriaFolder->_and($oIdDgOwnerFolder->in($this->getDelegationId()));
								$oFolderSet->select($oCriteriaFolder);
								while(null !== ($oFmFolder = $oFolderSet->next()))
								{
									//exclusion des repertoires collectif (on ne touche pas a leurs versions)
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
			
			return true;
			//$this->displayInfo();
		}
		return false;
	}

	/**
	 * This function return a value that indicate if a file can be imported to
	 * a collective folder of the file manager.
	 *
	 * @param string	$sFullPathName		The full path name of the source file (ex: d:/Temp/Upload/readme.txt). 
	 * @param string	$sRelativePath		The relative destination path in the file manager.
	 * 										If the full path is d:/Temp/Upload/fileManager/collectives/DG0/Folder/
	 * 										so the relative path is Folder/
	 * @param int		$iIdDelegation		The delegation identifier.
	 * 
	 * @return bool							True on success, false on error. To get the error call the function getError() of this class
	 */
	private function canImportFile($sFullPathName, $iIdDelegation, $sRelativePath)
	{
		$oFileInfo	= new SplFileInfo($sFullPathName);
		$sPathName	= BAB_FileManagerEnv::getCollectivePath($iIdDelegation);
		$oDestPath	= new SplFileInfo($sPathName . $sRelativePath);
		
		if(!$this->sourceFileExist($oFileInfo))
		{
			return false;
		}
		
		$sBaseName = basename($oFileInfo->getPathname());
		$oFile = new SplFileInfo(addEndSlash($oDestPath->getPath()) . $sBaseName);
		
		$this->destinationFolderExist($oDestPath);
		$this->destinationFileExist($oFile);
		
		$this->nameReserved($oFileInfo);
		$this->nameSupportedByFileSystem($oFileInfo);
		
		$iFileSize = $oFileInfo->getSize();
		$this->sizeExceedFmLimit($iFileSize, $sBaseName);
		
		$sName				= getFirstPath($sRelativePath);
		$sRootFolderPath	= $sPathName . $sName;
		$iFolderSize		= getDirSize($sRootFolderPath);
		$iTotalSize			= $iFolderSize + $iFileSize; 
		$this->sizeExceedFolderLimit($iTotalSize, $sBaseName);
		
		return (0 === count($this->getError()));
	}
	
	private function fileRename(bab_directoryRenameContext $oDirRenContext)
	{
		$this->sPathName = $oDirRenContext->getSanitizedSrcPathName();
		
		if(!$this->setEnv($oDirRenContext->getSrcPathName()))
		{
			return false;
		}
		
		if(!$this->accessValid())
		{
			return false;
		}
		
		$oFmEnv			= &getEnvObject();
		$oFolderFileSet	= bab_getInstance('BAB_FolderFileSet');
		$oName			= $oFolderFileSet->aField['sName'];
		$oPathName		= $oFolderFileSet->aField['sPathName'];
		$oGroup			= $oFolderFileSet->aField['sGroup'];
		$oIdOwner		= $oFolderFileSet->aField['iIdOwner'];
		$oIdDgOwner		= $oFolderFileSet->aField['iIdDgOwner'];
		
		$oCriteria		= $oName->in($oDirRenContext->getSrcName());
		$oCriteria		= $oCriteria->_and($oPathName->in($this->sPathName));
		$oCriteria		= $oCriteria->_and($oGroup->in('Y'));
		$oCriteria		= $oCriteria->_and($oIdOwner->in($oFmEnv->oFmFolder->getId()));
		$oCriteria		= $oCriteria->_and($oIdDgOwner->in($this->getDelegationId()));

		//bab_debug($oFolderFileSet->getSelectQuery($oCriteria));
		
		$oFolderFile = $oFolderFileSet->get($oCriteria);
		if(!($oFolderFile instanceof BAB_FolderFile))
		{
			$this->aError[]	= bab_translate("Error: cannot get the file from the database");
			return false;
		}
		
		$bManager	= bab_isAccessValid(BAB_FMMANAGERS_GROUPS_TBL, $oFolderFile->getOwnerId());
		$bUpdate	= bab_isAccessValid(BAB_FMUPDATE_GROUPS_TBL, $oFolderFile->getOwnerId());
		if(!($bManager || $bUpdate))
		{
			$aSchi = bab_getWaitingIdSAInstance($GLOBALS['BAB_SESS_USERID']);
			if(!(count($aSchi) > 0 && in_array($oFolderFile->getFlowApprobationInstanceId(), $aSchi)))
			{
				$this->aError[]	= bab_translate("Access denied");
				return false;
			}
		}
		
		$sTrgName	= replaceInvalidFolderNameChar($oDirRenContext->getTrgName());
		$sPathName	= BAB_FileManagerEnv::getCollectivePath($this->getDelegationId());
		$oSrcFile	= new SplFileInfo($sPathName . $oDirRenContext->getSanitizedSrcPathName() . $oDirRenContext->getSrcName());
		$oTrgFile	= new SplFileInfo($sPathName . $oDirRenContext->getSanitizedTrgPathName() . $sTrgName);
		$oDestPath	= new SplFileInfo($sPathName . $oDirRenContext->getSanitizedTrgPathName());
		
		$this->sourceFileExist($oSrcFile);
		$this->destinationFileExist($oTrgFile);
		$this->destinationFolderExist($oDestPath);
		$this->nameReserved($oTrgFile);
		$this->nameSupportedByFileSystem($oTrgFile);
		
		if(0 < count($this->getError()))
		{
			return false;			
		}
		
		$sTrgName			= replaceInvalidFolderNameChar($oDirRenContext->getTrgName());
		$sFullSrcPathName	= $this->getRootFmPath() . $oDirRenContext->getSanitizedSrcPathName() . $oDirRenContext->getSrcName();
		$sFullTrgPathName	= $this->getRootFmPath() . $oDirRenContext->getSanitizedTrgPathName() . $sTrgName;
		
		if(rename($sFullSrcPathName, $sFullTrgPathName))
		{
			$oFolderFile->setName($sTrgName);
			$oFolderFile->save();
			
			if(is_dir($this->getRootFmPath() . $oDirRenContext->getSanitizedTrgPathName() . BAB_FVERSION_FOLDER . '/'))
			{
				$oFolderFileVersionSet = new BAB_FolderFileVersionSet();
				$oIdFile = $oFolderFileVersionSet->aField['iIdFile'];
				$oFolderFileVersionSet->select($oIdFile->in($oFolderFile->getId()));

				while(null !== ($oFolderFileVersion = $oFolderFileVersionSet->next()))
				{
					$sSrc = $this->getRootFmPath() . $oDirRenContext->getSanitizedTrgPathName() . BAB_FVERSION_FOLDER . '/' .
					$oFolderFileVersion->getMajorVer() . ',' . $oFolderFileVersion->getMinorVer() .
					',' . $oDirRenContext->getSrcName();

					$sTrg = $this->getRootFmPath() . $oDirRenContext->getSanitizedTrgPathName() . BAB_FVERSION_FOLDER . '/' .
					$oFolderFileVersion->getMajorVer() . ',' . $oFolderFileVersion->getMinorVer() .
					',' . $sTrgName;
					
					if(file_exists($sSrc))
					{
						rename($sSrc, $sTrg);
					}
				}
			}
			return true;
		}
		return false;
	}
	
	private function fileMove(bab_directoryRenameContext $oDirRenContext)
	{
		$this->sPathName = $oDirRenContext->getSanitizedTrgPathName();
		
		if(!$this->setEnv($oDirRenContext->getTrgPathName()))
		{
			return false;
		}
		
		if(!$this->accessValid())
		{
			return false;
		}
		
		$sSanitizedSrcPathName	= $oDirRenContext->getSanitizedSrcPathName();
		$sSanitizedTrgPathName	= $oDirRenContext->getSanitizedTrgPathName();
		
		$oFmEnv					= &getEnvObject();
		$iIdSrcRootFolder		= 0;
		$sSrcPath				= BAB_PathUtil::removeEndSlashes($sSanitizedSrcPathName);
		$bSrcPathIsCollective	= true;
		$iIdTrgRootFolder		= 0;
		$sTrgPath				= BAB_PathUtil::removeEndSlashes($oDirRenContext->getTrgPath());
		
		$oSrcRootFolder = BAB_FmFolderSet::getRootCollectiveFolder($sSanitizedSrcPathName);
		$oTrgRootFolder = BAB_FmFolderSet::getRootCollectiveFolder($oDirRenContext->getTrgPath());

		$iIdSrcRootFolder = $oSrcRootFolder->getId();
		$iIdTrgRootFolder = $oTrgRootFolder->getId();
		
		if(canPasteFile($iIdSrcRootFolder, $sSrcPath, $iIdTrgRootFolder, $sTrgPath, $oDirRenContext->getSrcName()))
		{
			$oTrgFmFolder		= BAB_FmFolderSet::getFirstCollectiveFolder($sSanitizedTrgPathName);
			$iTrgIdOwner		= $oTrgFmFolder->getId();
			
			$sFullSrcPathName	= (string) $this->getRootFmPath() . $oDirRenContext->getSanitizedSrcPathName() . $oDirRenContext->getSrcName();
			$sFullTrgPathName	= (string) $this->getRootFmPath() . $oDirRenContext->getSanitizedTrgPathName() . $oDirRenContext->getTrgName();
			
			$oFmEnv			= &getEnvObject();
			$oFolderFileSet	= bab_getInstance('BAB_FolderFileSet');
			$oName			= $oFolderFileSet->aField['sName'];
			$oPathName		= $oFolderFileSet->aField['sPathName'];
			$oGroup			= $oFolderFileSet->aField['sGroup'];
			$oIdOwner		= $oFolderFileSet->aField['iIdOwner'];
			$oIdDgOwner		= $oFolderFileSet->aField['iIdDgOwner'];
			
			$oCriteria		= $oName->in($oDirRenContext->getSrcName());
			$oCriteria		= $oCriteria->_and($oPathName->in($oDirRenContext->getSrcPath()));
			$oCriteria		= $oCriteria->_and($oGroup->in('Y'));
			$oCriteria		= $oCriteria->_and($oIdOwner->in($oFmEnv->oFmFolder->getId()));
			$oCriteria		= $oCriteria->_and($oIdDgOwner->in($this->getDelegationId()));
	
			//bab_debug($oFolderFileSet->getSelectQuery($oCriteria));
			
			$oFolderFile = $oFolderFileSet->get($oCriteria);
			if(!($oFolderFile instanceof BAB_FolderFile))
			{
				$this->aError[]	= bab_translate("Error: cannot get the file from the database");
				return false;
			}
			
			if($sFullSrcPathName === $sFullTrgPathName)
			{
				$oFolderFile->setState('');
				$oFolderFile->save();
				return true;
			}
			
			$oSrcFile	= new SplFileInfo($sFullSrcPathName);
			$oTrgFile	= new SplFileInfo($sFullTrgPathName);
			$sPathName	= BAB_FileManagerEnv::getCollectivePath($this->getDelegationId());
			$oDestPath	= new SplFileInfo($sPathName . $oDirRenContext->getSanitizedTrgPathName());
			
			$this->sourceFileExist($oSrcFile);
			$this->destinationFileExist($oTrgFile);
			$this->destinationFolderExist($oDestPath);
			//$this->nameReserved($oTrgFile);
			//$this->nameSupportedByFileSystem($oTrgFile);
			
			//Si on ne copie pas dans le meme rootFolder
			$sFirstSrcPath = (string) getFirstPath($oDirRenContext->getSrcPath());
			$sFirstTrgPath = (string) getFirstPath($oDirRenContext->getTrgPath());
			
			if($sFirstSrcPath !== $sFirstTrgPath)
			{
				$sBaseName = basename($oSrcFile->getPathname());
				$iFileSize = $oSrcFile->getSize();
				$this->sizeExceedFmLimit($iFileSize, $sBaseName);
				
				$sRootFolderPath	= $sPathName . $sFirstTrgPath;
				$iFolderSize		= getDirSize($sRootFolderPath);
				$iTotalSize			= $iFolderSize + $iFileSize; 
				$this->sizeExceedFolderLimit($iTotalSize, $sBaseName);
			}
			
			if(0 < count($this->getError()))
			{
				return false;			
			}
			
			if(rename($sFullSrcPathName, $sFullTrgPathName))
			{
				$oFolderFile->setState('');
				$oFolderFile->setOwnerId($iTrgIdOwner);
				$oFolderFile->setPathName($oDirRenContext->getSanitizedTrgPathName());
				$oFolderFile->save();
					
				if(is_dir($sPathName . $oDirRenContext->getSanitizedSrcPathName() . BAB_FVERSION_FOLDER . '/'))
				{
					if(!is_dir($sPathName . $oDirRenContext->getSanitizedTrgPathName() . BAB_FVERSION_FOLDER . '/'))
					{
						bab_mkdir($sPathName . $oDirRenContext->getSanitizedTrgPathName() . BAB_FVERSION_FOLDER, $GLOBALS['babMkdirMode']);
					}
				}
				
				$oFolderFileVersionSet = new BAB_FolderFileVersionSet();
				$oIdFile = $oFolderFileVersionSet->aField['iIdFile'];

				$sFn = $oDirRenContext->getSrcName();			
				$oFolderFileVersionSet->select($oIdFile->in($oFolderFile->getId()));
				while(null !== ($oFolderFileVersion = $oFolderFileVersionSet->next()))
				{
					$sFileName = $oFolderFileVersion->getMajorVer() . ',' . $oFolderFileVersion->getMinorVer() . ',' . $oDirRenContext->getSrcName();
					$sSrc = $sPathName . $oDirRenContext->getSanitizedSrcPathName() . BAB_FVERSION_FOLDER . '/' . $sFileName;
					$sTrg = $sPathName . $oDirRenContext->getSanitizedTrgPathName() . BAB_FVERSION_FOLDER . '/' . $sFileName;
					rename($sSrc, $sTrg);
				}
				return true;
			}
		}
		return false;
	}
	
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
		$oFmEnv				= &getEnvObject();
		
		$sPathName = BAB_PathUtil::addEndSlash(BAB_PathUtil::sanitize($sPathName));
		
		$aBuffer = array();
		if(preg_match('#^DG(\d+)(/)#', $sPathName, $aBuffer))
		{
			$iIdDelegation			= (int) $aBuffer[1];
			$sPath					= 'DG' . $iIdDelegation . '/';
			$this->iIdDelegation	= $iIdDelegation; 
			$aVisibleFmDelegation	= bab_getUserFmVisibleDelegations();
			
			if(!array_key_exists($this->iIdDelegation, $aVisibleFmDelegation))
			{
				$this->aError[]	= bab_translate("Access denied");
				return false;
			}
			
			$this->sRelativePath	= mb_substr($sPathName, mb_strlen($sPath));
			$this->sUploadPath		= BAB_FmFolderHelper::getUploadPath();
			$this->sRootFmPath		= BAB_FileManagerEnv::getCollectivePath($iIdDelegation);
			
			$this->initRelativePath();
			
			if('' != $this->sRelativePath)
			{
				$oFolder = BAB_FolderFile::getRootFolder($this->sRelativePath, $iIdDelegation);

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
			$aSearch		= array('%path%');
			$aReplace		= array($sTrgPathName);
			$sMessage		= str_replace($aSearch, $aReplace, bab_translate("The pathName %path% is not valid, it should start with DGx/."));
			$this->aError[]	= $sMessage;
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
		$oFmEnv	= &getEnvObject();
		if(!$oFmEnv->accessValid())
		{
			$this->aError[]	= bab_translate('Access denied');
			return false;
		}
		return true;
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
			$this->aError[]	= bab_translate('The pathName is empty');
			return false;
		}
		
		$aBuffer = array();
		if(false === preg_match('#^DG(\d+)(/)#', $sPathName, $aBuffer))
		{
			$aSearch		= array('%path%');
			$aReplace		= array($sPathName);
			$sMessage		= str_replace($aSearch, $aReplace, bab_translate("The pathName %path% is not valid, it should start with DGx/."));
			$this->aError[]	= $sMessage;
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
					$this->aError[]	= bab_translate('Dot are not allowed in pathName.');
					return false;
				}
				
				if($this->isReservedName($sPathItem))
				{
					$this->aError[]	= bab_translate('The pathName contains a reserved name.');
					return false;
				}
				
				$sSanitizedPathName .= $sPathItem . '/';
			}
			return true;
		}
		
		$aSearch		= array('%path%');
		$aReplace		= array($sPathName);
		$sMessage		= str_replace($aSearch, $aReplace, bab_translate("The pathName %path% is not valid."));
		$this->aError[]	= $sMessage;
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
	
	
	
	//-----------------------------------------
	private function isFile(SplFileInfo $oFileInfo)
	{
		return $oFileInfo->isFile();
	}
	
	private function sourceFileExist(SplFileInfo $oFileInfo)
	{
		if(false === $this->isFile($oFileInfo))
		{
			$aSearch		= array('%fileName%');
			$aReplace		= array(basename($oFileInfo->getPathname()));
			$sMessage		= str_replace($aSearch, $aReplace, bab_translate("The file %fileName% does not exist."));
			$this->aError[]	= $sMessage;
			return false;
		}
		return true;
	}
	
	private function destinationFileExist(SplFileInfo $oFileInfo)
	{
		if(true === $this->isFile($oFileInfo))
		{
			$aSearch		= array('%fileName%', '%folder%');
			$aReplace		= array(basename($oFileInfo->getPathname()), $oFileInfo->getPath());
			$sMessage		= str_replace($aSearch, $aReplace, bab_translate("The file %fileName% already exist in %folder%."));
			$this->aError[]	= $sMessage;
			return true;
		}
		return false;
	}
	
	private function destinationFolderExist(SplFileInfo $oFileInfo)
	{
		if(false === $oFileInfo->isDir())
		{
			$aSearch		= array('%folder%');
			$aReplace		= array($oFileInfo->getPath());
			$sMessage		= str_replace($aSearch, $aReplace, bab_translate("The folder %folder% does not exist."));
			$this->aError[]	= $sMessage;
			return false;
		}
		return true;
	}
	
	private function nameReserved(SplFileInfo $oFileInfo)
	{
		if(mb_strtolower(BAB_FVERSION_FOLDER) == mb_strtolower($oFileInfo->getFilename()))
		{
			$aSearch		= array('%name%');
			$aReplace		= array(basename($oFileInfo->getPathname()));
			$sMessage		= str_replace($aSearch, $aReplace, bab_translate("The name %name% is reserved."));
			$this->aError[]	= $sMessage;
			return true;
		}
		return false;
	}
	
	private function nameSupportedByFileSystem(SplFileInfo $oFileInfo)
	{
		$sFileName = basename($oFileInfo->getPathname());
		if(!isStringSupportedByFileSystem($sFileName))
		{
			$aSearch		= array('%name%');
			$aReplace		= array($sFileName);
			$sMessage		= str_replace($aSearch, $aReplace, bab_translate("The file named %name% contains characters that are not supported by the file system."));
			$this->aError[]	= $sMessage;
			return false;
		}
		return true;
	}
	
	private function sizeExceedFmLimit($iZise, $sBaseName)
	{
		$oFmEnv	= &getEnvObject();
		if($iZise + $oFmEnv->getFMTotalSize() > $GLOBALS['babMaxTotalSize'])
		{
			$aSearch		= array('%name%');
			$aReplace		= array($sBaseName);
			$sMessage		= str_replace($aSearch, $aReplace, bab_translate("The size of the file %name% exceeds the limit set by the file manager."));
			$this->aError[]	= $sMessage;
			return true;
		}
		return false;
	}
	
	private function sizeExceedFolderLimit($iTotalSize, $sBaseName)
	{
		if($iTotalSize > $GLOBALS['babMaxGroupSize'])
		{
			$aSearch		= array('%name%');
			$aReplace		= array($sBaseName);
			$sMessage		= str_replace($aSearch, $aReplace, bab_translate("The size of the file %name% exceeds the limit set for the folder."));
			$this->aError[]	= $sMessage;
			return true;
		}
		return false;
	}
	//-----------------------------------------
	
	private function displayInfo()
	{
		bab_debug(
			'sUploadPath   ==> ' . $this->sUploadPath	. "\n" .
			'sRootFmPath   ==> ' . $this->sRootFmPath	. "\n" .
			'sRelativePath ==> ' . $this->sRelativePath	. "\n" .
			'sPathName     ==> ' . $this->sPathName	. "\n" .
			'iIdObject     ==> ' . $this->getDelegationId()
		);
	}
}



/**
 * Context class for rename operation
 *
 */
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
