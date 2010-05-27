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
* @internal SEC1 NA 08/12/2006 FULL
*/
require_once 'base.php';
require_once dirname(__FILE__) . '/artapi.php';



/**
 * Helper class that contain the path
 * used in publication according to
 * the delegation identifier
 *
 */
class bab_PublicationPathsEnv
{
	private $sUploadPath			= null;
	private $sRootImgPath			= null;
	private $sCategoriesImgPath		= null;
	private $sTopicsImgPath			= null;
	private $sArticlesImgPath		= null;
	private $sDraftsArticlesImgPath	= null;
	private $sTempPath				= null;
	private $iIdDelegation			= null;
	private $aError					= array();

	
	public function __construct()
	{
		
	}
	
	/**
	 * Set up all the path
	 *
	 * @param int $iIdDelegation The delegation identifier
	 * 
	 * @return bool	True on success, false on error. To get the error call the method getError()
	 */
	public function setEnv($iIdDelegation)
	{
		require_once dirname(__FILE__) . '/pathUtil.class.php';
		require_once dirname(__FILE__) . '/fileincl.php';
		
		$this->iIdDelegation	= (int) $iIdDelegation;
		$this->sUploadPath		= BAB_PathUtil::addEndSlash(BAB_PathUtil::sanitize($GLOBALS['babUploadPath']));
		
		if(!$this->checkDirAccess($this->sUploadPath))
		{
			return false;
		}		
		
		$aPath = array(
			'root'			=> 'articles',
			'categories'	=> 'articles/DG' . $this->iIdDelegation . '/categoriesImg',
			'topics'		=> 'articles/DG' . $this->iIdDelegation . '/topicsImg',
			'articles'		=> 'articles/DG' . $this->iIdDelegation . '/articlesImg',
			'draftArticles'	=> 'articles/draftsImg',
			'temp'			=> 'articles/temp',
		);
		
		foreach($aPath as $sKey => $sRelativePath)
		{
			BAB_FmFolderHelper::makeDirectory($this->sUploadPath, $sRelativePath);
		}
		
		$this->sRootImgPath				= $this->sUploadPath . $aPath['root'] . '/';
		$this->sCategoriesImgPath		= $this->sUploadPath . $aPath['categories'] . '/';
		$this->sTopicsImgPath			= $this->sUploadPath . $aPath['topics'] . '/';
		$this->sArticlesImgPath			= $this->sUploadPath . $aPath['articles'] . '/';
		$this->sDraftsArticlesImgPath	= $this->sUploadPath . $aPath['draftArticles'] . '/';
		$this->sTempPath				= $this->sUploadPath . $aPath['temp'] . '/';
		
		$this->checkDirAccess($this->sRootImgPath);
		$this->checkDirAccess($this->sCategoriesImgPath);
		$this->checkDirAccess($this->sTopicsImgPath);
		$this->checkDirAccess($this->sArticlesImgPath);
		$this->checkDirAccess($this->sDraftsArticlesImgPath);
		$this->checkDirAccess($this->sTempPath);
		
		return (0 === count($this->aError));
	}
	
	/**
	 * Returns the path to the image(s) associated with category, 
	 * the path is based on the identifier of delegation.
	 * The path is terminated with a '/'. 
	 *
	 * @param int $iIdCategory The identifier of the category to which the path must be returned 
	 * 
	 * @return string The path to the image of the category
	 */
	public function getCategoryImgPath($iIdCategory)
	{
		return $this->sCategoriesImgPath . $iIdCategory . '/';
	}
	
	/**
	 * Returns the path to the image(s) associated with topic, 
	 * the path is based on the identifier of delegation.
	 * The path is terminated with a '/'. 
	 *
	 * @param int $iIdTopic The identifier of the topic to which the path must be returned 
	 * 
	 * @return string The path to the image of the topic
	 */
	public function getTopicImgPath($iIdTopic)
	{
		return $this->sTopicsImgPath . $iIdTopic . '/';
	}
	
	/**
	 * Returns the path to the image(s) associated with article, 
	 * the path is based on the identifier of delegation.
	 * The path is terminated with a '/'. 
	 *
	 * @param int $iIdArticle The identifier of the article to which the path must be returned 
	 * 
	 * @return string The path to the image of the article
	 */
	public function getArticleImgPath($iIdArticle)
	{
		return $this->sArticlesImgPath . $iIdArticle . '/';
	}
	
	/**
	 * Returns the path to the image(s) associated with article, 
	 * the path is based on the identifier of delegation.
	 * The path is terminated with a '/'. 
	 * 
	 * @param int $iIdDraft The identifier of the draft article to which the path must be returned
	 * 
	 * @return string The path to the draft image of the article
	 */
	public function getDraftArticleImgPath($iIdDraft)
	{
		return $this->sDraftsArticlesImgPath . $iIdDraft . '/';
	}
	
	
	/**
	 * Returns the temp path used by the publication 
	 * The path is terminated with a '/'. 
	 *
	 * @return string The temp path used by the publication
	 */
	public function getTempPath()
	{
		return $this->sTempPath;
	}
	
	public function getUploadPath()
	{
		return $this->sUploadPath;
	}
	
	/**
	 * Return a value that indicate if the directory is accessible.
	 * To be accessible the $sFullPathName must be a directory,
	 * must be writable, must be readable.
	 *
	 * @param string $sFullPathName The full path name of the directory
	 * 
	 * @return bool	True on success, false on error. To get the error call the method getError()
	 */
	public function checkDirAccess($sFullPathName)
	{
		$Success	= true;
		$aSearch	= array('%directory%');
		$aReplace	= array($sFullPathName);
		
		if(!is_dir($sFullPathName))
		{
			$this->addError(str_replace($aSearch, $aReplace, bab_translate("The directory %directory% does not exits")));
			return false;			
		}
		
		if(!is_writable($sFullPathName))
		{
			$this->addError(str_replace($aSearch, $aReplace, bab_translate("The directory %directory% is not writeable")));
			$Success = false;
		}
		
		if(!is_readable($sFullPathName))
		{
			$this->addError(str_replace($aSearch, $aReplace, bab_translate("The directory %directory% is not readable")));
			$Success = false;
		}
		
		return $Success;
	}
	
	/**
	 * Add an error
	 *
	 * @param string $sMessage The error message
	 */
	private function addError($sMessage)
	{
		$this->aError[] = $sMessage;
	}
	
	/**
	 * Return a value that indicate if there is error
	 *
	 * @return bool True if there is error, false otherwise
	 */
	public function haveError()
	{
		return (0 !== count($this->aError));
	}
	
	/**
	 * Return an array of error string
	 *
	 * @return Array The array of error string
	 */
	public function getError()
	{
		return $this->aError;
	}
}


/**
 * Helper class to upload image to
 * category, topic, article
 *
 */
class bab_PublicationImageUploader
{
	private $aError	= array();
	
	public function __construct()
	{
		
	}
	
	/**
	 * Upload an image to a category, this function does not
	 * test the validity of the category identifier.
	 * The identifier is used to determine the upload path
	 *
	 * @param int $iIdDelegation	The delegation identifier
	 * @param int $iIdCategory		The category identifier
	 * @param int $sKeyOfPhpFile	The index of the $_FILES
	 * 
	 * @return string|false			The full path name of the uploaded image on success, false otherwise.
	 * 								To get the error call the method getError().
	 */
	public function uploadCategoryImage($iIdDelegation, $iIdCategory, $sKeyOfPhpFile)
	{
		$sFunctionName = 'getCategoryImgPath';
		return $this->uploadImage($iIdDelegation, $iIdCategory, $sKeyOfPhpFile, $sFunctionName);
	}
	
	/**
	 * Upload an image to a topic, this function does not
	 * test the validity of the topic identifier.
	 * The identifier is used to determine the upload path
	 * 
	 * @param int $iIdDelegation	The delegation identifier
	 * @param int $iIdTopic			The topic identifier
	 * @param int $sKeyOfPhpFile	The index of the $_FILES
	 * 
	 * @return string|false			The full path name of the uploaded image on success, false otherwise.
	 * 								To get the error call the method getError().
	 */
	public function uploadTopicImage($iIdDelegation, $iIdTopic, $sKeyOfPhpFile)
	{
		$sFunctionName = 'getTopicImgPath';
		return $this->uploadImage($iIdDelegation, $iIdTopic, $sKeyOfPhpFile, $sFunctionName);
	}
	
	/**
	 * Upload an image to an article, this function does not
	 * test the validity of the article identifier.
	 * The identifier is used to determine the upload path
	 * 
	 * @param int $iIdDelegation	The delegation identifier
	 * @param int $iIdArticle		The article identifier
	 * @param int $sKeyOfPhpFile	The index of the $_FILES
	 * 
	 * @return string|false			The full path name of the uploaded image on success, false otherwise.
	 * 								To get the error call the method getError().
	 */
	/*
	public function uploadArticleImage($iIdDelegation, $iIdArticle, $sKeyOfPhpFile)
	{
		$sFunctionName = 'getArticleImgPath';
		return $this->uploadImage($iIdDelegation, $iIdArticle, $sKeyOfPhpFile, $sFunctionName);
	}
	//*/
	
	/**
	 * Upload an image to an draft article, this function does not
	 * test the validity of the article identifier.
	 * The identifier is used to determine the upload path
	 * 
	 * @param int $iIdDraft			The identifier of the draft article
	 * @param int $sKeyOfPhpFile	The index of the $_FILES
	 * 
	 * @return string|false			The full path name of the uploaded image on success, false otherwise.
	 * 								To get the error call the method getError().
	 */
	public function uploadDraftArticleImage($iIdDraft, $sKeyOfPhpFile)
	{
		$sFunctionName	= 'getDraftArticleImgPath';
		$iIdDelegation	= 0;
		return $this->uploadImage($iIdDelegation, $iIdDraft, $sKeyOfPhpFile, $sFunctionName);
	}
	
	/**
	 * Upload an image to a publication image folder, 
	 * this function does not test the validity of the $iIdObject.
	 * The $iIdObject is used to determine the upload path
	 * 
	 * @param int 		$iIdDelegation	The delegation identifier
	 * @param int 		$iIdObject		The object identifier (iIdCategory, iIdTopic, iIdArticle)
	 * @param int		$sKeyOfPhpFile	The index of the $_FILES
	 * @param string	$sFunctionName	Depending the nature of the $iIdObject, this variable
	 * 									can be one of ('getCategoryImgPath', 'getTopicImgPath', 'getArticleImgPath')
	 * 
	 * @return string|false				The full path name of the uploaded image on success, false otherwise.
	 * 									To get the error call the method getError().
	 */
	private function uploadImage($iIdDelegation, $iIdObject, $sKeyOfPhpFile, $sFunctionName)
	{
		require_once $GLOBALS['babInstallPath'] . 'utilit/uploadincl.php';
		
		$oFileHandler = $this->uploadFile($sKeyOfPhpFile);
		if(!($oFileHandler instanceof bab_fileHandler))
		{
			return false;
		}
		
		if(true === $this->fileSizeToLarge($oFileHandler))
		{
			return false;
		}
		
		if(false === $this->mimeSupported($oFileHandler))
		{
			return false;
		}
		
		$oPubPathEnv = bab_getInstance('bab_PublicationPathsEnv');
		if(false === $this->setEnv($oPubPathEnv, $iIdDelegation))
		{
			return false;
		}
		
		$sPathName = $oPubPathEnv->$sFunctionName($iIdObject);
		if(!is_dir($sPathName))
		{
			if(false === bab_mkdir($sPathName))
			{
				$this->addError(bab_translate("Can't create directory: ") . $sPathName);
				return false;
			}
		}
		
		if(false === $oPubPathEnv->checkDirAccess($sPathName))
		{
			$this->addErrors($oPubPathEnv->getError());
			return false;
		}

		$sFullPathName = $sPathName . $oFileHandler->filename;
		if(true === $this->isfile($sFullPathName))
		{
			return false;
		}
		
		if(false === $this->importFile($oFileHandler, $sFullPathName))
		{
			return false;
		}
		return $sFullPathName;
	}
	
	/**
	 * Upload an image to the temp path of the publication
	 * 
	 * @param int $iIdDelegation	The delegation identifier
	 * @param int $sKeyOfPhpFile	The index of the $_FILES
	 * 
	 * @return array|false			False on error, an array indexed by two keys:
	 * 								array('sTempName' => tempName, 'sFileName' => fileName);
	 * 								To get the file from the temp path use bab_PublicationPathsEnv. 
	 * 								To get the error call the method getError().
	 */
	public function uploadImageToTemp($iIdDelegation, $sKeyOfPhpFile)
	{
		require_once dirname(__FILE__) . '/uploadincl.php';
		require_once dirname(__FILE__) . '/uuid.php';
		
		$oFileHandler = $this->uploadFile($sKeyOfPhpFile);
		if(!($oFileHandler instanceof bab_fileHandler))
		{
			return false;
		}
		
		if(true === $this->fileSizeToLarge($oFileHandler))
		{
			return false;
		}
		
		if(false === $this->mimeSupported($oFileHandler))
		{
			return false;
		}
		
		$oPubPathEnv = bab_getInstance('bab_PublicationPathsEnv');
		if(false === $this->setEnv($oPubPathEnv, $iIdDelegation))
		{
			return false;
		}
		
		$sFileName		= $oFileHandler->filename;
		$sFullPathName	= $oPubPathEnv->getTempPath() . bab_uuid();
		if(true === $this->isfile($sFullPathName))
		{
			return false;
		}
		
		$sFileExtention = $this->getFileExtention($sFileName);
		if(false !== $sFileExtention)
		{
			$sFullPathName .= $sFileExtention;
		}
		
		if(false === $this->importFile($oFileHandler, $sFullPathName))
		{
			return false;
		}
		return array('sTempName' => basename($sFullPathName), 'sFileName' => $sFileName);
	}
	
	/**
	 * Upload the image to the php temp directory
	 *
	 * @param string $sKeyOfPhpFile		The index of the $_FILES
	 * 
	 * @return bab_fileHandler|false	False on error, a bab_fileHandler object on success.
	 * 									To get the error call the method getError().
	 */
	private function uploadFile($sKeyOfPhpFile)
	{
		$oFileHandler = bab_fileHandler::upload($sKeyOfPhpFile);
		if('' !== (string) $oFileHandler->error)
		{
			$this->addError($oFileHandler->error);
			return false;
		}
		return $oFileHandler;
	}
	
	/**
	 * Return a value that indicate if the uploaded file
	 * exceeds the maximum size allowed.
	 *
	 * @param bab_fileHandler $oFileHandler The object returned by the method uploadFile
	 * 
	 * @return bool							True if the file exceeds, false otherwise
	 * 										To get the error call the method getError().
	 */
	private function fileSizeToLarge(bab_fileHandler $oFileHandler)
	{
		if((int) $GLOBALS['babMaxImgFileSize'] < $oFileHandler->size)
		{
			$aSearch	= array('%filesize%', '%maxsize%');
			$aReplace	= array($oFileHandler->size, $GLOBALS['babMaxImgFileSize']);
			$this->addError(str_replace($aSearch, $aReplace, bab_translate("The file size(%filesize%) exceeds the maximum allowed(%maxsize%)")));
			return true;
		}
		return false;
	}
	
	/**
	 * Return a value that indicate if the image format is supported
	 *
	 * @param bab_fileHandler $oFileHandler	 The object returned by the method uploadFile
	 * 
	 * @return bool							True if the file exceeds, false otherwise
	 * 										To get the error call the method getError().
	 */
	private function mimeSupported(bab_fileHandler $oFileHandler)
	{
		$aSupportedMime = array('image/gif' => 'image/gif', 'image/jpeg' => 'image/jpeg', 'image/png' => 'image/png');
		if(!array_key_exists($oFileHandler->mime, $aSupportedMime))
		{
			$aSearch	= array('%mime%', '%supportedMime%');
			$aReplace	= array($oFileHandler->mime, implode(',', $aSupportedMime));
			$this->addError(str_replace($aSearch, $aReplace, bab_translate("Mime type %mime% is not supported, supported types are %supportedMime%")));
			return false;
		}
		return true;
	}
	
	/**
	 * This function set the publication path environements.
	 * 
	 * @param bab_PublicationPathsEnv	$oPubPathEnv
	 * @param int						$iIdDelegation
	 * 
	 * @return bool						True on success, false on error.
	 * 									To get the error call the method getError().
	 */
	private function setEnv(bab_PublicationPathsEnv $oPubPathEnv, $iIdDelegation)
	{
		if(false === $oPubPathEnv->setEnv($iIdDelegation))
		{
			$this->addErrors($oPubPathEnv->getError());
			return false;
		}
		return true;
	}
	
	/**
	 * Return a value that indicate if a file already exits with this name
	 *
	 * @param string $sFullPathName	The full path name
	 * 
	 * @return bool					True if the file already exists, false othewise.
	 * 								To get the error call the method getError().
	 */
	private function isfile($sFullPathName)
	{
		if(is_file($sFullPathName))
		{
			$this->addError(bab_translate("A file with the same name already exists"));
			return true;
		}
		return false;
	}
	
	/**
	 * This function import an uploaded file to a destination.
	 *
	 * @param bab_fileHandler	$oFileHandler
	 * @param string			$sFullPathName
	 * 
	 * @return bool				True if the file was imported successfully, false otherwise.
	 * 							To get the error call the method getError().
	 */
	private function importFile(bab_fileHandler $oFileHandler, $sFullPathName)
	{
		if(false === $oFileHandler->import($sFullPathName))
		{
			$this->addError(bab_translate("Cannot upload file"));
			return false;
		}
		return true;		
	}
	
	/**
	 * Move a picture category from the temporary directory to the directory of the category.
	 *
	 * @param int $iIdDelegation		Identifier of the delegation
	 * @param int $iIdCategory			Identifier of the category
	 * @param string $sTempImageName	Temporary name of the image
	 * @param string $sImageName		Name of the image
	 * 
	 * @return string|bool The full path name of the moved image, false otherwise
	 */
	public function importCategoryImageFromTemp($iIdDelegation, $iIdCategory, $sTempImageName, $sImageName)
	{
		$sFunctionName = 'getCategoryImgPath';
		return $this->importImageFromTemp($iIdDelegation, $iIdCategory, $sTempImageName, $sImageName, $sFunctionName);
	}
	
	/**
	 * Move a picture topic from the temporary directory to the directory of the topic.
	 *
	 * @param int $iIdDelegation		Identifier of the delegation
	 * @param int $iIdTopic				Identifier of the topic
	 * @param string $sTempImageName	Temporary name of the image
	 * @param string $sImageName		Name of the image
	 * 
	 * @return string|bool The full path name of the moved image, false otherwise
	 */
	public function importTopicImageFromTemp($iIdDelegation, $iIdTopic, $sTempImageName, $sImageName)
	{
		$sFunctionName = 'getTopicImgPath';
		return $this->importImageFromTemp($iIdDelegation, $iIdTopic, $sTempImageName, $sImageName, $sFunctionName);
	}
	
	/**
	 * Move a picture from the temporary directory to the directory of the $iIdObject.
	 *
	 * @param int $iIdDelegation		Identifier of the delegation
	 * @param int $iIdObject			Identifier of the object (category, topic)
	 * @param string $sTempImageName	Temporary name of the image
	 * @param string $sImageName		Name of the image
	 * 
	 * @return string|bool The full path name of the moved image, false otherwise
	 */
	private function importImageFromTemp($iIdDelegation, $iIdObject, $sTempImageName, $sImageName, $sFunctionName)
	{
		require_once dirname(__FILE__) . '/uploadincl.php';
		
		$oPubPathEnv = bab_getInstance('bab_PublicationPathsEnv');
		if(false === $this->setEnv($oPubPathEnv, $iIdDelegation))
		{
			return false;
		}
		
		$sPathName = $oPubPathEnv->$sFunctionName($iIdObject);
		if(!is_dir($sPathName))
		{
			if(false === bab_mkdir($sPathName))
			{
				$this->addError(bab_translate("Can't create directory: ") . $sPathName);
				return false;
			}
		}
		
		if(false === $oPubPathEnv->checkDirAccess($sPathName))
		{
			$this->addErrors($oPubPathEnv->getError());
			return false;
		}
		
		$sFullPathName = $oPubPathEnv->getTempPath() . $sTempImageName;
		/*
		if(true === $this->isfile($sFullPathName))
		{
			return false;
		}
		//*/
		
		$oFileHandler = bab_fileHandler::move($sFullPathName); 
		if(!($oFileHandler instanceof bab_fileHandler))
		{
			return false;
		}
		
		$sFullPathName = $sPathName . $sImageName;
		if(false === $this->importFile($oFileHandler, $sFullPathName))
		{
			return false;
		}
		
		return $sFullPathName;
	}
	
	/**
	 * Move an article picture from the draft directory to the directory of the article.
	 * 
	 * @param int $iIdDelegation	Delegation identifier
	 * @param int $iIdDraft			Draft article identifier
	 * @param int $iIdArticle		Article identifier
	 * 
	 * @return string|bool The full path name of the moved image, false otherwise
	 */
	public function importDraftArticleImageToArticleImage($iIdDelegation, $iIdDraft, $iIdArticle)
	{
		require_once dirname(__FILE__) . '/uploadincl.php';
		
		$aImageInfo = bab_getImageDraftArticle($iIdDraft);
		if(false !== $aImageInfo)
		{
			$oPubPathEnv = bab_getInstance('bab_PublicationPathsEnv');
			if(false === $this->setEnv($oPubPathEnv, $iIdDelegation))
			{
				return false;
			}
			
			$sArticlePathName = $oPubPathEnv->getArticleImgPath($iIdArticle);
			if(!is_dir($sArticlePathName))
			{
				if(false === bab_mkdir($sArticlePathName))
				{
					$this->addError(bab_translate("Can't create directory: ") . $sArticlePathName);
					return false;
				}
			}
			
			$sDraftArticlePathName = $oPubPathEnv->getDraftArticleImgPath($iIdDraft);
			if(!is_dir($sDraftArticlePathName))
			{
					$this->addError(bab_translate("This folder does not exists") . ':' . $sArticlePathName);
					return false;
			}
			
			if(false === $oPubPathEnv->checkDirAccess($sArticlePathName))
			{
				$this->addErrors($oPubPathEnv->getError());
				return false;
			}
			
			if(false === $oPubPathEnv->checkDirAccess($sDraftArticlePathName))
			{
				$this->addErrors($oPubPathEnv->getError());
				return false;
			}
			$oFileHandler = bab_fileHandler::move($sDraftArticlePathName . $aImageInfo['name']); 
			if(!($oFileHandler instanceof bab_fileHandler))
			{
				return false;
			}
			
			if(!$this->mimeSupported($oFileHandler))
			{
				@unlink($sDraftArticlePathName . $aImageInfo['name']);
				return false;
			}
			
			$sFullPathName = $sArticlePathName . $aImageInfo['name'];
			if(false === $this->importFile($oFileHandler, $sFullPathName))
			{
				return false;
			}
			return $sFullPathName;
		}
		return false;
	}
	
	
	/**
	 * Copy an article picture from the article directory to the draft directory of the article.
	 * 
	 * @param int $iIdDelegation	Delegation identifier
	 * @param int $iIdArticle		Article identifier
	 * @param int $iIdDraft			Draft article identifier
	 *  
	 * @return string|bool The full path name of the copied image, false otherwise
	 */
	public function copyArticleImageToDraftArticle($iIdDelegation, $iIdArticle, $iIdDraft)
	{
		require_once dirname(__FILE__) . '/uploadincl.php';
		
		$aImageInfo = bab_getImageArticle($iIdArticle);
		if(false !== $aImageInfo)
		{
			$oPubPathEnv = bab_getInstance('bab_PublicationPathsEnv');
			if(false === $this->setEnv($oPubPathEnv, $iIdDelegation))
			{
				return false;
			}
			
			$sArticlePathName = $oPubPathEnv->getArticleImgPath($iIdArticle);
			if(!is_dir($sArticlePathName))
			{
				$this->addError(bab_translate("This folder does not exists") . ':' . $sArticlePathName);
			}
			
			$sDraftArticlePathName = $oPubPathEnv->getDraftArticleImgPath($iIdDraft);
			if(!is_dir($sDraftArticlePathName))
			{
				if(false === bab_mkdir($sDraftArticlePathName))
				{
					$this->addError(bab_translate("Can't create directory: ") . $sDraftArticlePathName);
					return false;
				}
			}
			
			if(false === $oPubPathEnv->checkDirAccess($sArticlePathName))
			{
				$this->addErrors($oPubPathEnv->getError());
				return false;
			}
			
			if(false === $oPubPathEnv->checkDirAccess($sDraftArticlePathName))
			{
				$this->addErrors($oPubPathEnv->getError());
				return false;
			}
			
			$oFileHandler = bab_fileHandler::copy($sArticlePathName . $aImageInfo['name']); 
			if(!($oFileHandler instanceof bab_fileHandler))
			{
				return false;
			}
			
			$sFullPathName = $sDraftArticlePathName . $aImageInfo['name'];
			if(false === $this->importFile($oFileHandler, $sFullPathName))
			{
				return false;
			}
			return $sFullPathName;
		}
		return false;
	}
	
	/**
	 * Return the file extention of a filename
	 *
	 * @param string $sFileName
	 * 
	 * @return string|bool The file extention on success, false on error
	 */
	private function getFileExtention($sFileName)
	{
		$iOffset = mb_strpos($sFileName, '.');
		if(false === $iOffset)
		{
			return false;
		}

		return mb_strtolower(mb_substr($sFileName, $iOffset));
	}
	
	/**
	 * Add an error
	 *
	 * @param string $sMessage The error message
	 */
	private function addError($sMessage)
	{
		$this->aError[] = $sMessage;
	}
	
	/**
	 * Add an array of error
	 *
	 * @param string $aError The array of error message
	 */
	private function addErrors($aError)
	{
		$this->aError = array_merge($this->aError, $aError);
	}

	/**
	 * Delete the out dated image from the temporary directory
	 *
	 * @param int $iNbSeconds The delay in seconds
	 */
	public static function deleteOutDatedTempImage($iNbSeconds)
	{
		$iIdDelegation	= 0;
		$oEnvObj		= bab_getInstance('bab_PublicationPathsEnv');
		
		$oEnvObj->setEnv($iIdDelegation);
		$sPath = $oEnvObj->getTempPath();
		
		require_once dirname(__FILE__) . '/dateTime.php';
		
		$oEndDate = BAB_DateTime::now();
		$oEndDate->add(-$iNbSeconds, BAB_DATETIME_SECOND);
		
		if(is_dir($sPath))
		{
			$oDirIterator = new DirectoryIterator($sPath);
			foreach($oDirIterator as $oItem)
			{
				if($oItem->isFile())
				{
					$oFileDate = BAB_DateTime::fromTimeStamp(filectime($oItem->getPathname()));
					$iIsEqual	= 0;
					$iIsBefore	= -1;
					$iIsAfter	= 1;
					
					//Supprimer tous les fichiers qui ont �t� cr��s avant $oEndDate
					if($iIsBefore == BAB_DateTime::compare($oFileDate, $oEndDate))
					{
						@unlink($oItem->getPathname());
					}
				}
			}
		}
	}
	
	/**
	 * Return a value that indicate if there is error
	 *
	 * @return bool True if there is error, false otherwise
	 */
	public function haveError()
	{
		return (0 !== count($this->aError));
	}
	
	/**
	 * Return an array of error string
	 *
	 * @return Array The array of error string
	 */
	public function getError()
	{
		return $this->aError;
	}
}


function bab_deleteDraftFiles($idart)
{
	global $babDB;
	$fullpath = bab_getUploadDraftsPath();
	$res = $babDB->db_query("select * from ".BAB_ART_DRAFTS_FILES_TBL." where id_draft='".$babDB->db_escape_string($idart)."'");
	while( $arr = $babDB->db_fetch_array($res))
		{
		unlink($fullpath.$arr['id_draft'].",".$arr['name']);
		}

	$babDB->db_query("delete from ".BAB_ART_DRAFTS_FILES_TBL." where id_draft='".$babDB->db_escape_string($idart)."'");
}

function bab_deleteArticleFiles($idart)
{
	global $babDB;
	$fullpath = bab_getUploadArticlesPath();
	$res = $babDB->db_query("select * from ".BAB_ART_FILES_TBL." where id_article='".$babDB->db_escape_string($idart)."'");
	while( $arr = $babDB->db_fetch_array($res))
		{
		unlink($fullpath.$arr['id_article'].",".$arr['name']);
		}

	$babDB->db_query("delete from ".BAB_ART_FILES_TBL." where id_article='".$babDB->db_escape_string($idart)."'");
}

function bab_getUploadDraftsPath()
{
	if( mb_substr($GLOBALS['babUploadPath'], -1) == "/" )
		{
		$path = $GLOBALS['babUploadPath'];
		}
	else
		{
		$path = $GLOBALS['babUploadPath']."/";
		}

	$path = $path."drafts/";

	if(!is_dir($path) && !bab_mkdir($path, $GLOBALS['babMkdirMode']))
		{
		return false;
		}
	return $path;
}

function bab_getUploadArticlesPath()
{
	if( mb_substr($GLOBALS['babUploadPath'], -1) == "/" )
		{
		$path = $GLOBALS['babUploadPath'];
		}
	else
		{
		$path = $GLOBALS['babUploadPath']."/";
		}

	$path = $path."articles/";

	if(!is_dir($path) && !bab_mkdir($path, $GLOBALS['babMkdirMode']))
		{
		return false;
		}
	return $path;
}


function notifyArticleDraftApprovers($id, $users)
	{
	global $babDB, $babBody, $BAB_SESS_USER, $BAB_SESS_EMAIL, $babAdminEmail, $babInstallPath;

	if(!class_exists("tempa"))
		{
		class tempa
			{
			var $articletitle;
			var $message;
			var $from;
			var $author;
			var $category;
			var $categoryname;
			var $title;
			var $site;
			var $sitename;
			var $date;
			var $dateval;


			function tempa($id)
				{
				global $babDB, $BAB_SESS_USER, $BAB_SESS_EMAIL, $babSiteName;
				$arr = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_ART_DRAFTS_TBL." where id='".$babDB->db_escape_string($id)."'"));
				$this->articletitle = $arr['title'];
				$this->articleurl = $GLOBALS['babUrlScript']."?tg=login&cmd=detect&referer=".urlencode("?tg=approb&idx=all");
				$this->message = bab_translate("A new article is waiting for you");
				$this->from = bab_translate("Author");
				$this->category = bab_translate("Topic");
				$this->title = bab_translate("Title");
				$this->categoryname = viewCategoriesHierarchy_txt($arr['id_topic']);
				$this->site = bab_translate("Web site");
				$this->sitename = $babSiteName;
				$this->date = bab_translate("Date");
				$timestamp = mktime();
				$this->dateval = bab_strftime($timestamp);
				if( !empty($arr['id_author']) && $arr['id_author'] != 0)
					{
					$this->author = bab_getUserName($arr['id_author']);
					$this->authoremail = bab_getUserEmail($arr['id_author']);
					}
				else
					{
					$this->author = bab_translate("Unknown user");
					$this->authoremail = "";
					}
				/* template variables used in customized file mailinfo.html */
				$this->babtpl_topicname = $this->categoryname;
				$this->babtpl_authorname = $this->author;
				$this->babtpl_articledatetime = $this->dateval;
				$this->babtpl_articledate = bab_strftime($timestamp, false);
				$this->babtpl_articletime = date('HH:mm', $timestamp);
				$this->babtpl_articletitle = $this->articletitle;
				}
			}
		}

	$mail = bab_mail();
	if( $mail == false )
		return;
	$mailBCT = 'mail'.$babBody->babsite['mail_fieldaddress'];

	if( count($users) > 0 )
		{
		$sql = "select distinct email from ".BAB_USERS_TBL." where id IN (".$babDB->quote($users).")";
		$result=$babDB->db_query($sql);
		while( $arr = $babDB->db_fetch_array($result))
			{
			$mail->$mailBCT($arr['email']);
			}
		}
	$mail->mailFrom($babAdminEmail, $GLOBALS['babAdminName']);
	$tempa = new tempa($id);
	$subject = bab_printTemplate($tempa,'mailinfo.html', 'articlewait_subject');
	if( empty($subject) )
		$mail->mailSubject(bab_translate("New waiting article"));
	else
		$mail->mailSubject($subject);

	$message = $mail->mailTemplate(bab_printTemplate($tempa,"mailinfo.html", "articlewait"));
	$mail->mailBody($message, "html");

	$message = bab_printTemplate($tempa,"mailinfo.html", "articlewaittxt");
	$mail->mailAltBody($message);

	$mail->send();
	}

function notifyArticleDraftAuthor($idart, $what)
	{
	global $babBody, $babDB, $babAdminEmail;

	if(!class_exists("clsNotifyArticleDraftAuthor"))
		{
		class clsNotifyArticleDraftAuthor
			{
			var $titlename;
			var $about;
			var $from;
			var $author;
			var $category;
			var $categoryname;
			var $title;
			var $site;
			var $sitename;
			var $date;
			var $dateval;


			function clsNotifyArticleDraftAuthor($id, $what, $title, $topic, $authorname)
				{
				global $BAB_SESS_USER, $BAB_SESS_EMAIL, $babSiteName;
				$this->titlename = $title;
				$this->articleurl = $GLOBALS['babUrlScript']."?tg=login&cmd=detect&referer=".urlencode("?tg=approb&idx=all");
				if( $what == 0 )
					{
					$this->about = bab_translate("Your article has been refused");
					}
				else
					{
					$this->about = bab_translate("Your article has been accepted");
					}
				$this->message = "";
				$this->from = bab_translate("Author");
				$this->category = bab_translate("Topic");
				$this->title = bab_translate("Title");
				$this->categoryname = viewCategoriesHierarchy_txt($topic);
				$this->site = bab_translate("Web site");
				$this->sitename = $babSiteName;
				$this->date = bab_translate("Date");
				$timestamp = mktime();
				$this->dateval = bab_strftime($timestamp);
				$this->author = $authorname;

				/* template variables used in customized file mailinfo.html */
				$this->babtpl_topicname = $this->categoryname;
				$this->babtpl_authorname = $this->author;
				$this->babtpl_articledatetime = $this->dateval;
				$this->babtpl_articledate = bab_strftime($timestamp, false);
				$this->babtpl_articletime = date('HH:mm', $timestamp);
				$this->babtpl_articletitle = $this->title;
				
				}
			}
		}
	
    $mail = bab_mail();
	if( $mail == false )
		return;

	$arr = $babDB->db_fetch_array($babDB->db_query("select title, id_topic, id_author from ".BAB_ART_DRAFTS_TBL." where id='".$babDB->db_escape_string($idart)."'"));

	if( !empty($arr['id_author']) && $arr['id_author'] != 0)
		{
		$authorname = bab_getUserName($arr['id_author']);
		$authoremail = bab_getUserEmail($arr['id_author']);
		$mail->mailTo($authoremail, $authorname);
		$mail->mailFrom($GLOBALS['babAdminEmail'], $GLOBALS['babAdminName']);

		$tempc = new clsNotifyArticleDraftAuthor($idart, $what, $arr['title'], $arr['id_topic'], $authorname);
		$message = $mail->mailTemplate(bab_printTemplate($tempc,"mailinfo.html", "confirmarticle"));
		if( $what == 0 )
		{
			$subject = bab_printTemplate($tempc,'mailinfo.html', 'confirmarticle_refused_subject');
		}
		else
		{
			$subject = bab_printTemplate($tempc,'mailinfo.html', 'confirmarticle_accepted_subject');
		}
		if( empty($subject) )
			$mail->mailSubject($tempc->about);
		else
			$mail->mailSubject($subject);
		$mail->mailBody($message, "html");

		$message = bab_printTemplate($tempc,"mailinfo.html", "confirmarticletxt");
		$mail->mailAltBody($message);
		$mail->send();
		}
	}


function notifyArticleHomePage($top, $title, $homepage0, $homepage1)
	{
	global $babBody, $babDB, $BAB_SESS_USER, $BAB_SESS_EMAIL, $babAdminEmail, $babInstallPath;

	if(!class_exists("notifyArticleHomePageCls"))
		{
		class notifyArticleHomePageCls
			{
			var $articletitle;
			var $message;
			var $from;
			var $author;
			var $category;
			var $categoryname;
			var $title;
			var $site;
			var $sitename;
			var $date;
			var $dateval;


			function notifyArticleHomePageCls($top, $title, $homepage0, $homepage1)
				{
				global $BAB_SESS_USER, $BAB_SESS_EMAIL, $babSiteName;
				$this->articletitle = $title;
				$this->message = bab_translate("A new article is proposed for home page(s)"). ": ";
				if( $homepage1 == "1" )
					$this->message .= bab_translate("Registered users");
				$this->message .= " - ";
				if( $homepage0 == "2" )
					$this->message .= bab_translate("Unregistered users");

				$this->from = bab_translate("Author");
				$this->category = bab_translate("Topic");
				$this->title = bab_translate("Title");
				$this->categoryname = $top;
				$this->site = bab_translate("Web site");
				$this->sitename = $babSiteName;
				$this->date = bab_translate("Date");
				$this->dateval = bab_longDate(time());
				if( !empty($BAB_SESS_USER))
					$this->author = $BAB_SESS_USER;
				else
					$this->author = bab_translate("Unknown user");

				if( !empty($BAB_SESS_EMAIL))
					$this->authoremail = $BAB_SESS_EMAIL;
				else
					$this->authoremail = "";
				}
			}
		}
    $mail = bab_mail();
	if( $mail == false )
		return;
	$mailBCT = 'mail'.$babBody->babsite['mail_fieldaddress'];
	$clearBCT = 'clear'.$babBody->babsite['mail_fieldaddress'];

	$mail->mailFrom($babAdminEmail, $GLOBALS['babAdminName']);
	$mail->mailSubject(bab_translate("New article for home page"));

	$tempa = new notifyArticleHomePageCls($top, $title, $homepage0, $homepage1);
	$message = $mail->mailTemplate(bab_printTemplate($tempa,"mailinfo.html", "articlehomepage"));
	$mail->mailBody($message, "html");

	$message = bab_printTemplate($tempa,"mailinfo.html", "articlehomepagetxt");
	$mail->mailAltBody($message);


	include_once $GLOBALS['babInstallPath'].'admin/acl.php';
	$arrusers = aclGetAccessUsers(BAB_SITES_HPMAN_GROUPS_TBL, $babBody->babsite['id']);
	
	$alreadySendUser = array();
	
	if( $arrusers ){
		$count = 0;
		while(list(,$arr) = each($arrusers)){
			if(!ISSET($alreadySendUser[$arr['email']])){
				$mail->$mailBCT($arr['email'], $arr['name']);
				$count++;

				if( $count > $babBody->babsite['mail_maxperpacket'] ){
					$mail->send();
					$mail->$clearBCT();
					$mail->clearTo();
					$count = 0;
				}
				$alreadySendUser[$arr['email']] = true;
			}
		}

		if( $count > 0 ){
			$mail->send();
			$mail->$clearBCT();
			$mail->clearTo();
			$count = 0;
		}
	}	
}


function notifyArticleGroupMembers($topicname, $topics, $title, $author, $what, $restriction, $articleid)
	{
	global $babBody, $BAB_SESS_USER, $BAB_SESS_EMAIL, $babAdminEmail, $babInstallPath;

	if(!class_exists("tempcc"))
		{
		class tempcc
			{
			var $message;
			var $from;
			var $author;
			var $about;
			var $title;
			var $titlename;
			var $site;
			var $sitename;
			var $date;
			var $dateval;


			function tempcc($topicname, $title, $author, $msg,$topics, $articleid)
				{
				global $BAB_SESS_USER, $BAB_SESS_EMAIL, $babSiteName;
				$this->topic = bab_translate("Topic");
				$this->topicname = $topicname;
				$this->title = bab_translate("Title");
				$this->authorname = $author;
				$this->author = bab_translate("Author");
				$this->titlename = $title;
				$this->site = bab_translate("Web site");
				$this->sitename = $babSiteName;
				$this->date = bab_translate("Date");
				$timestamp = mktime();
				$this->dateval = bab_strftime($timestamp);
				$this->message = $msg;
				$this->linkurl = $GLOBALS['babUrlScript']."?tg=login&cmd=detect&referer=".urlencode("?tg=articles&topics=".$topics);
				$this->linkname = viewCategoriesHierarchy_txt($topics);
				
				/* template variables used in customized file mailinfo.html */
				$this->babtpl_topicname = $this->topicname;
				$this->babtpl_authorname = $this->authorname;
				$this->babtpl_articledatetime = $this->dateval;
				$this->babtpl_articledate = bab_strftime($timestamp, false);
				$this->babtpl_articletime = date('HH:mm', $timestamp);
				$this->babtpl_articletitle = $this->titlename;
				$this->babtpl_articleid = $articleid;
				$this->babtpl_articletopicid = $topics;
				}
			}
		}	
    $mail = bab_mail();
	if( $mail == false )
		return;
	$mailBCT = 'mail'.$babBody->babsite['mail_fieldaddress'];
	$clearBCT = 'clear'.$babBody->babsite['mail_fieldaddress'];

	if( $what == 'mod' )
	{
		$msg = bab_translate("An article has been modified");
	}
	else
	{
		$msg = bab_translate("An article has been published");
	}
	$tempc = new tempcc($topicname, $title, $author, $msg,$topics, $articleid);

	$subject = '';
	if( $what == 'mod' )
	{
		$subject = bab_printTemplate($tempc,'mailinfo.html', 'notifyarticle_update_subject');
	}
	else
	{
		$subject = bab_printTemplate($tempc,'mailinfo.html', 'notifyarticle_new_subject');
	}
	
	if( empty($subject) )
		$mail->mailSubject($msg);
	else
		$mail->mailSubject($subject);
    $mail->mailFrom($babAdminEmail, $GLOBALS['babAdminName']);

	$message = $mail->mailTemplate(bab_printTemplate($tempc,"mailinfo.html", "notifyarticle"));

	$messagetxt = bab_printTemplate($tempc,"mailinfo.html", "notifyarticletxt");

	$mail->mailBody($message, "html");
	$mail->mailAltBody($messagetxt);

	$sep = ',';
	if( !empty($restriction))
	{
		if( strchr($restriction, ","))
			$sep = ',';
		else
			$sep = '&';
		$arrres = explode($sep, $restriction);			
	}


	include_once $babInstallPath."admin/acl.php";
	$users = aclGetAccessUsers(BAB_TOPICSVIEW_GROUPS_TBL, $topics);
	
	$alreadySendUser = array();

	$arrusers = array();
	$count = 0;
	foreach($users as $id => $arr){
		if( count($arrusers) == 0 || !in_array($id, $arrusers)){
			$arrusers[] = $id;
			if( !empty($restriction)){
				$add = bab_articleAccessByRestriction($restriction, $id);
			}else{
				$add = true;
			}
			if( $add ){
				if(!ISSET($alreadySendUser[$arr['email']])){
					$mail->$mailBCT($arr['email'], $arr['name']);
					$count++;
					$alreadySendUser[$arr['email']] = true;
				}
			}
		}

		if( $count > $babBody->babsite['mail_maxperpacket'] ){
			$mail->send();
			$mail->$clearBCT();
			$mail->clearTo();
			$count = 0;
		}

	}

	if( $count > 0 ){
		$mail->send();
		$mail->$clearBCT();
		$mail->clearTo();
		$count = 0;
	}
}		


function notifyCommentApprovers($idcom, $nfusers)
	{
	global $babDB, $babBody, $BAB_SESS_USER, $BAB_SESS_EMAIL, $babAdminEmail, $babInstallPath;

	if(!class_exists("tempa"))
		{
		class tempca
			{
			var $article;
			var $articlename;
			var $message;
			var $from;
			var $author;
			var $category;
			var $categoryname;
			var $subject;
			var $subjectname;
			var $title;
			var $site;
			var $sitename;
			var $date;
			var $dateval;

			function tempca($idcom)
				{
				global $babDB, $BAB_SESS_USER, $BAB_SESS_EMAIL, $babSiteName;
				$arr = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_COMMENTS_TBL." where id='".$babDB->db_escape_string($idcom)."'"));

				$this->message = bab_translate("A new comment is waiting for you");
				$this->from = bab_translate("Author");
				$this->subject = bab_translate("Subject");
				$this->subjectname = $arr['subject'];
				$this->subjecturl = $GLOBALS['babUrlScript']."?tg=login&cmd=detect&referer=".urlencode("?tg=waiting&idx=WaitingC&topics=".$arr['id_topic']."&article=".$arr['id_article']);
				$this->article = bab_translate("Article");
				$this->articlename = bab_getArticleTitle($arr['id_article']);
				$this->category = bab_translate("Topic");
				$this->categoryname = viewCategoriesHierarchy_txt($arr['id_topic']);
				$this->site = bab_translate("Web site");
				$this->sitename = $babSiteName;
				$this->date = bab_translate("Date");
				$timestamp = mktime();
				$this->dateval = bab_strftime($timestamp);
				if( !empty($BAB_SESS_USER))
					$this->author = $BAB_SESS_USER;
				else
					$this->author = bab_translate("Unknown user");

				if( !empty($BAB_SESS_EMAIL))
					$this->authoremail = $BAB_SESS_EMAIL;
				else
					$this->authoremail = "";
					
				/* template variables used in customized file mailinfo.html */
				$this->babtpl_topicname = $this->categoryname;
				$this->babtpl_authorname = $this->author;
				$this->babtpl_articledatetime = $this->dateval;
				$this->babtpl_articledate = bab_strftime($timestamp, false);
				$this->babtpl_articletime = date('HH:mm', $timestamp);
				$this->babtpl_articletitle = $this->articlename;
				$this->babtpl_commentsubject = $this->subjectname;
				}
			}
		
		$mail = bab_mail();
		if( $mail == false )
			return;

		$mailBCT = 'mail'.$babBody->babsite['mail_fieldaddress'];
		$clearBCT = 'clear'.$babBody->babsite['mail_fieldaddress'];

		if( count($nfusers) > 0 )
			{
			$sql = "select distinct email from ".BAB_USERS_TBL." where id IN (".$babDB->quote($nfusers).")";
			$result=$babDB->db_query($sql);
			while( $arr = $babDB->db_fetch_array($result))
				{
				$mail->$mailBCT($arr['email']);
				}
			}
		$mail->mailFrom($babAdminEmail, $GLOBALS['babAdminName']);
		$tempa = new tempca($idcom);
		
		$subject = bab_printTemplate($tempa,'mailinfo.html', 'commentwait_subject');
		if( empty($subject) )
			$mail->mailSubject(bab_translate("New waiting comment"));
		else
			$mail->mailSubject($subject);
			
		$message = $mail->mailTemplate(bab_printTemplate($tempa,"mailinfo.html", "commentwait"));
		$mail->mailBody($message, "html");

		$message = bab_printTemplate($tempa,"mailinfo.html", "commentwaittxt");
		$mail->mailAltBody($message);
		$mail->send();
		}
	}

function notifyCommentAuthor($subject, $msg, $idfrom, $to)
	{
	global $babBody, $BAB_SESS_USER, $BAB_SESS_EMAIL, $babAdminEmail, $babInstallPath;

	class tempa
		{
		var $message;
        var $from;
        var $author;
        var $about;
        var $site;
        var $sitename;
        var $date;
        var $dateval;


		function tempa($subject, $msg, $from, $to)
			{
            global $BAB_SESS_USER, $BAB_SESS_EMAIL, $babSiteName;
            $this->about = bab_translate("About your comment");
            $this->site = bab_translate("Web site");
            $this->sitename = $babSiteName;
            $this->date = bab_translate("Date");
            $this->dateval = bab_strftime(mktime());
            $this->message = $msg;
			}
		}
	
    $mail = bab_mail();
	if( $mail == false )
		return;

	$mail->mailTo($to);
    $mail->mailFrom(bab_getUserEmail($idfrom), bab_getUserName($idfrom));
    $mail->mailSubject($subject);

	$tempa = new tempa($subject, $msg, $idfrom, $to);
	$message = $mail->mailTemplate(bab_printTemplate($tempa,"mailinfo.html", "confirmcomment"));
    $mail->mailBody($message, "html");

	$message = bab_printTemplate($tempa,"mailinfo.html", "confirmcommenttxt");
    $mail->mailAltBody($message);

	$mail->send();
	}
	
function acceptWaitingArticle($idart)
{
	global $babBody, $babDB;

	$res = $babDB->db_query("select adt.*, tt.category as topicname, tt.allow_attachments, tct.id_dgowner, tt.allow_addImg, tt.busetags from ".BAB_ART_DRAFTS_TBL." adt left join ".BAB_TOPICS_TBL." tt on adt.id_topic=tt.id left join ".BAB_TOPICS_CATEGORIES_TBL." tct on tt.id_cat=tct.id  where adt.id='".$babDB->db_escape_string($idart)."'");
	if( $res && $babDB->db_num_rows($res) > 0 )
		{
		include_once $GLOBALS['babInstallPath']."utilit/imgincl.php";
		$arr = $babDB->db_fetch_array($res);

		$iIdDelegation = (int) $arr['id_dgowner'];
		
		if( $arr['id_article'] != 0 )
			{
			$articleid = $arr['id_article'];
			$req = "update ".BAB_ARTICLES_TBL." set id_modifiedby='".$babDB->db_escape_string($arr['id_author'])."', date_archiving='".$babDB->db_escape_string($arr['date_archiving'])."', date_publication='".$babDB->db_escape_string($arr['date_publication'])."', restriction='".$babDB->db_escape_string($arr['restriction'])."', lang='".$babDB->db_escape_string($arr['lang'])."'";
			if( $arr['update_datemodif'] != 'N')
				{
				$req .= ", date_modification=now()";
				}
			$req .= " where id='".$babDB->db_escape_string($articleid)."'";
			$babDB->db_query($req);
			bab_deleteArticleFiles($articleid);
			}
		else
			{
			$req = "insert into ".BAB_ARTICLES_TBL." (id_topic, id_author, date, date_publication, date_archiving, date_modification, restriction, lang) values ";
			$req .= "('" .$babDB->db_escape_string($arr['id_topic']). "', '".$babDB->db_escape_string($arr['id_author']). "', now()";

			if( $arr['date_publication'] == '0000-00-00 00:00:00' )	
				{
				$req .= ", now()";
				}
			else
				{
				$req .= ", '".$babDB->db_escape_string($arr['date_publication'])."'";
				}
			$req .= ", '".$babDB->db_escape_string($arr['date_archiving'])."', now(), '".$babDB->db_escape_string($arr['restriction'])."', '".$babDB->db_escape_string($arr['lang']). "')";
			$babDB->db_query($req);
			$articleid = $babDB->db_insert_id();
			}

		$GLOBALS['babWebStat']->addNewArticle($arr['id_dgowner']);


		$head = imagesUpdateLink($arr['head'], $idart."_draft_", $articleid."_art_" );
		$body = imagesUpdateLink($arr['body'], $idart."_draft_", $articleid."_art_" );

		$req = "update ".BAB_ARTICLES_TBL." set head='".$babDB->db_escape_string($head)."', body='".$babDB->db_escape_string($body)."', title='".$babDB->db_escape_string($arr['title'])."' where id='".$babDB->db_escape_string($articleid)."'";
		$res = $babDB->db_query($req);

	
		{//Image
			$iIdDraft	= $idart;	
			$iIdArticle	= $articleid;
			
			$oPubPathsEnv	= new bab_PublicationPathsEnv();
			$iIdDelegation	= 0; //Dummy value
			if($oPubPathsEnv->setEnv($iIdDelegation))
			{
				$sPathName = $oPubPathsEnv->getArticleImgPath($iIdArticle);
				$aImageInfo = bab_getImageArticle($iIdArticle);
				if(false !== $aImageInfo)
				{
					$sFullPathName = $sPathName . $aImageInfo['name'];
					if(file_exists($sFullPathName))
					{
						@unlink($sFullPathName);
					}
				}
				bab_deleteImageArticle($iIdArticle);
			}
			
			
			if('Y' == $arr['allow_addImg'])
			{
				$oPubImpUpl	= new bab_PublicationImageUploader();
				$sFullPathName = $oPubImpUpl->importDraftArticleImageToArticleImage($iIdDelegation, $iIdDraft, $iIdArticle);
				if(false !== $sFullPathName)
				{
					$aPathParts		= pathinfo($sFullPathName);
					$sName			= $aPathParts['basename'];
					$sPathName		= BAB_PathUtil::addEndSlash($aPathParts['dirname']);
					$sUploadPath	= BAB_PathUtil::addEndSlash(BAB_PathUtil::sanitize($GLOBALS['babUploadPath']));
					$sRelativePath	= mb_substr($sPathName, mb_strlen($sUploadPath), mb_strlen($sFullPathName) - mb_strlen($sName));
					
					bab_addImageToArticle($iIdArticle, $sName, $sRelativePath);
					$aImageInfo = bab_getImageDraftArticle($iIdDraft);
					if(false !== $aImageInfo)
					{
						bab_deleteImageDraftArticle($iIdDraft);
						@rmdir($sPathName);
					}
				}
			}
		}
		
		
		/* move attachements */
		if( $arr['allow_attachments'] ==  'Y' )
			{
			$res = $babDB->db_query("select * from ".BAB_ART_DRAFTS_FILES_TBL." where id_draft='".$babDB->db_escape_string($idart)."'");
			$pathdest = bab_getUploadArticlesPath();
			$pathorg = bab_getUploadDraftsPath();
			$files_to_index = array();
			$files_to_insert = array();

			include_once $GLOBALS['babInstallPath']."utilit/indexincl.php";

			while($rr = $babDB->db_fetch_array($res))
				{
				if( copy($pathorg.$idart.",".$rr['name'], $pathdest.$articleid.",".$rr['name']))
					{
					// index files
					$files_to_index[] = $pathdest.$articleid.",".$rr['name'];

					// inserts
					$files_to_insert[] = array(
							'id_article'	=> $articleid,
							'name'			=> $rr['name'],
							'description'	=> $rr['description'],
							'ordering'	=> $rr['ordering']
						);
					}
				}

			//bab_debug($files_to_index);

			$index_status = bab_indexOnLoadFiles($files_to_index , 'bab_art_files');

			foreach($files_to_insert as $arrf) {
					$babDB->db_query(
					
					"INSERT INTO ".BAB_ART_FILES_TBL." 
						(id_article, name, description, ordering, index_status) 
					VALUES 
						(
						'".$babDB->db_escape_string($arrf['id_article'])."', 
						'".$babDB->db_escape_string($arrf['name'])."', 
						'".$babDB->db_escape_string($arrf['description'])."', 
						'".$babDB->db_escape_string($arrf['ordering'])."', 
						'".$babDB->db_escape_string($index_status)."' 
						)
					");
				}
			}

		require_once dirname(__FILE__) . '/tagApi.php';
		
		$oReferenceMgr = bab_getInstance('bab_ReferenceMgr');
		
		$oReference = bab_Reference::makeReference('ovidentia', '', 'articles', 'article', $articleid);
		$oReferenceMgr->removeByReference($oReference);
		
		$oReferenceDraft = bab_Reference::makeReference('ovidentia', '', 'articles', 'draft', $idart);
		if( $arr['busetags'] ==  'Y' )
			{
			$oIterator = $oReferenceMgr->getTagsByReference($oReferenceDraft);
			$oIterator->orderAsc('tag_name');
			foreach($oIterator as $oTag)
				{
				$oReferenceMgr->add($oTag->getName(), $oReference);
				}
			}
		$oReferenceMgr->removeByReference($oReferenceDraft);
		
		if( $arr['id_author'] == 0 || (($artauthor = bab_getUserName($arr['id_author'])) == ''))
			{
			$artauthor = bab_translate("Anonymous");
			}
		if( $arr['notify_members'] == "Y" && bab_mktime($arr['date_publication']) <= mktime())
			{
			notifyArticleGroupMembers($arr['topicname'], $arr['id_topic'], $arr['title'], $artauthor, 'add', $arr['restriction'], $articleid);
			}

		if( $arr['hpage_private'] == "Y" || $arr['hpage_public'] == "Y" )
			{
			if( $arr['hpage_private'] == "Y")
				{
				$res = $babDB->db_query("insert into ".BAB_HOMEPAGES_TBL." (id_article, id_site, id_group) values ('" .$babDB->db_escape_string($articleid). "', '" . $babDB->db_escape_string($babBody->babsite['id']). "', '1')");
				}

			if( $arr['hpage_public'] == "Y" )
				{
				$res = $babDB->db_query("insert into ".BAB_HOMEPAGES_TBL." (id_article, id_site, id_group) values ('" .$babDB->db_escape_string($articleid). "', '" . $babDB->db_escape_string($babBody->babsite['id']). "', '2')");
				}

			notifyArticleHomePage($arr['topicname'], $arr['title'], ($arr['hpage_public'] == "Y"? 2:0), ($arr['hpage_private'] == "Y"?1:0));
			}
	
		return $articleid;
		}
	else
	{
		return 0;
	}
}


/**
 * 
 * @param string	$title
 * @param string	$head
 * @param string	$body
 * @param string	$lang
 * @param string	$template		The template section name that will be used to fill in the editor if $head and $body are empty.
 * @param string	$headFormat
 * @param string	$bodyFormat
 * @return string
 */
function bab_editArticle($title, $head, $body, $lang, $template, $headFormat = null, $bodyFormat = null)
{
	global $babBody;

	class clsEditArticle
	{
	
		var $title;
		var $head;
		var $body;

		function clsEditArticle($title, $head, $body, $lang, $template, $headFormat, $bodyFormat)
		{
			global $babDB;

			$this->mode = 1;

			$this->t_bab_image = bab_translate("Insert image");
			$this->t_bab_file = bab_translate("Insert file link");
			$this->t_bab_article = bab_translate("Insert article link");
			$this->t_bab_faq = bab_translate("Insert FAQ link");
			$this->t_bab_ovml = bab_translate("Insert OVML file");
			$this->t_bab_contdir = bab_translate("Insert contact link");


			$this->headval = empty($head) ? '' : $head;
			$this->bodyval = empty($body) ? '' : $body;
			$this->titleval = empty($title) ? '' : bab_toHtml($title);
			$this->lang = empty($body) ? $GLOBALS['babLanguage'] : $lang;


			$this->head = bab_translate("Head");
			$this->body = bab_translate("Body");
			$this->title = bab_translate("Title");
			$this->ok = bab_translate("Ok");


			$this->langLabel = bab_translate("Language");
			$this->langFiles = $GLOBALS['babLangFilter']->getLangFiles();
			if (isset($GLOBALS['babApplyLanguageFilter']) && $GLOBALS['babApplyLanguageFilter'] == 'loose') {
				if($lang != '*') {
					$this->langFiles = array();
					$this->langFiles[] = '*';
				}
			}
			$this->countLangFiles = count($this->langFiles);

			include_once $GLOBALS['babInstallPath'] . 'utilit/editorincl.php';
			$editorhead = new bab_contentEditor('bab_article_head');
			$editorbody = new bab_contentEditor('bab_article_body');
			if(is_null($headFormat))
			{
				$headFormat = $editorhead->getFormat();
			}
			if(is_null($bodyFormat))
			{
				$bodyFormat = $editorbody->getFormat();
			}

			if ($template != '' && $this->headval == '' && $this->bodyval == '') {
				
				// We fetch the template corresponding to the correct format (html, text...)
				// of the article head.
				$file = 'articlestemplate.' . $headFormat;
				$filepath = 'skins/' . $GLOBALS['babSkin'] . '/templates/' . $file;
				if (!file_exists($filepath)) {
					$filepath = $GLOBALS['babSkinPath'] . 'templates/'. $file;
					if (!file_exists($filepath)) {
						$filepath = $GLOBALS['babInstallPath'] . 'skins/ovidentia/templates/'. $file;
					}
				}
				if (file_exists($filepath)) {
					require_once dirname(__FILE__) . '/template.php';
					$tp = new bab_Template();
					$this->headval = $tp->_loadTemplate($filepath, 'head_' . $template);
				}

				// We fetch the template corresponding to the correct format (html, text...)
				// of the article body.
				$file = 'articlestemplate.' . $bodyFormat;
				$filepath = 'skins/' . $GLOBALS['babSkin'] . '/templates/' . $file;
				if (!file_exists($filepath)) {
					$filepath = $GLOBALS['babSkinPath'] . 'templates/'. $file;
					if (!file_exists($filepath)) {
						$filepath = $GLOBALS['babInstallPath'] . 'skins/ovidentia/templates/'. $file;
					}
				}
				if (file_exists($filepath)) {
					require_once dirname(__FILE__) . '/template.php';
					$tp = new bab_Template();
					$this->bodyval = $tp->_loadTemplate($filepath, 'body_' . $template);
				}
			}

				
				// l'ordre des appels est important
			$editorhead->setContent($this->headval);
			if (isset($headFormat)) {
				$editorhead->setFormat($headFormat);
			}
			
			$editorbody->setContent($this->bodyval);
			if (isset($bodyFormat)) {
				$editorbody->setFormat($bodyFormat);
			}
			
			$this->editorhead = $editorhead->getEditor();
			$this->editorbody = $editorbody->getEditor();
		}

		function getnextlang()
		{
			static $i = 0;
			if ($i < $this->countLangFiles) {
				$this->langValue = $this->langFiles[$i];
				if ($this->langValue == $this->lang) {
					$this->langSelected = 'selected';
				} else {
					$this->langSelected = '';
				}
				$i++;
				return true;
			}
			return false;
		} // function getnextlang

	} // class temp
	
	$temp = new clsEditArticle($title, $head, $body, $lang, $template, $headFormat, $bodyFormat);
	return bab_printTemplate($temp, 'artincl.html', 'editarticle');
}


function bab_previewArticleDraft($idart, $echo=0)
	{
	global $babBody;

	class clsPreviewArticleDraft
		{
	
		var $titleval;
		var $headval;
		var $bodyvat;

		function clsPreviewArticleDraft($idart)
			{
			global $babDB;
			$this->counf = 0;

			$res = $babDB->db_query("select adt.* from ".BAB_ART_DRAFTS_TBL." adt where adt.id='".$babDB->db_escape_string($idart)."'");
			if( $res && $babDB->db_num_rows($res) > 0 )
				{
				$this->idart = bab_toHtml($idart);
				$this->filesval = bab_translate("Associated documents");
				$this->notesval = bab_translate("Associated comments");
				$arr = $babDB->db_fetch_array($res);
				$this->titleval = bab_toHtml($arr['title']);

				include_once $GLOBALS['babInstallPath']."utilit/editorincl.php";
			
				$editor = new bab_contentEditor('bab_article_body');
				$editor->setContent($arr['body']);
				$editor->setFormat($arr['body_format']);
				$this->bodyval = $editor->getHtml();
				
				$editor = new bab_contentEditor('bab_article_head');
				$editor->setContent($arr['head']);
				$editor->setFormat($arr['head_format']);
				$this->headval = $editor->getHtml();
				
				$this->resf = $babDB->db_query("select * from ".BAB_ART_DRAFTS_FILES_TBL." where id_draft='".$babDB->db_escape_string($idart)."' order by ordering asc");
				$this->countf =  $babDB->db_num_rows($this->resf);

				$this->resn = $babDB->db_query("select * from ".BAB_ART_DRAFTS_NOTES_TBL." where id_draft='".$babDB->db_escape_string($idart)."' order by date_note asc");
				$this->countn =  $babDB->db_num_rows($this->resn);
				$this->altbg = false;
				}
			else
				{
				$this->titleval = '';
				$this->headval = '';
				$this->bodyval = '';
				}
			}

		function getnextfile()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->countf)
				{
				$arr = $babDB->db_fetch_array($this->resf);
				$this->urlfile = bab_toHtml($GLOBALS['babUrlScript']."?tg=artedit&idx=getf&idart=".$this->idart."&idf=".$arr['id']);
				$this->filename = bab_toHtml($arr['name']);
				$i++;
				return true;
				}
			else
				return false;
			}

		function getnextnote()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->countn)
				{
				$arr = $babDB->db_fetch_array($this->resn);
				$this->note = str_replace("\n", "<br>", bab_toHtml($arr['content']));
				$this->author = bab_toHtml(bab_getUserName($arr['id_author']));
				$this->date = bab_toHtml(bab_strftime(bab_mktime($arr['date_note'])));
				$this->altbg = !$this->altbg;
				$i++;
				return true;
				}
			else
				return false;
			}

		}
	
	$temp = new clsPreviewArticleDraft($idart);
	if( $echo )
		{
		echo bab_printTemplate($temp,"artincl.html", "previewarticledraft");
		}
	else
		{
		return bab_printTemplate($temp,"artincl.html", "previewarticledraft");
		}
	}

function bab_previewComment($com)
	{
	global $babBody;

	class bab_previewCommentCls
		{
	
		var $content;
		var $arr = array();
		var $count;
		var $res;
		var $close;
		var $title;
		var $sContent;

		function bab_previewCommentCls($com)
			{
			global $babDB;
			$this->close	= bab_translate("Close");
			$req			= "select * from ".BAB_COMMENTS_TBL." where id='".$babDB->db_escape_string($com)."'";
			$this->res		= $babDB->db_query($req);
			$this->arr		= $babDB->db_fetch_assoc($this->res);
			$this->title	= bab_toHtml($this->arr['subject']);
			$this->sContent	= 'text/html; charset=' . bab_charset::getIso();
			$this->article_rating = bab_toHtml($this->arr['article_rating']);
			$this->article_rating_percent = bab_toHtml($this->arr['article_rating'] * 20.0);
			include_once $GLOBALS['babInstallPath']."utilit/editorincl.php";
			
			$editor = new bab_contentEditor('bab_article_comment');
			$editor->setContent($this->arr['message']);
			$editor->setFormat($this->arr['message_format']);
			$this->content = $editor->getHtml();
			}
		}
	
	$temp = new bab_previewCommentCls($com);
	echo bab_printTemplate($temp,"artincl.html", "previewcomment");
	}

function bab_getDocumentArticle( $idf )
	{
	global $babDB, $babBody, $BAB_SESS_USERID;
	$access = false;

	$res = $babDB->db_query("select * from ".BAB_ART_FILES_TBL." where id='".$babDB->db_escape_string($idf)."'");
	if( $res && $babDB->db_num_rows($res) > 0 )
		{
		$access = true;
		}

	if( !$access )
		{
		echo bab_translate("Access denied");
		return;
		}

	$GLOBALS['babWebStat']->addArticleFile($idf);
	$arr = $babDB->db_fetch_array($res);
	$file = stripslashes($arr['name']);

	$fullpath = bab_getUploadArticlesPath();
	

	$fullpath .= $arr['id_article'].",".$file;
	$fsize = filesize($fullpath);
	$mime = bab_getFileMimeType($file);

	if( mb_strtolower(bab_browserAgent()) == "msie")
		header('Cache-Control: public');
	$inl = bab_getFileContentDisposition() == 1? 1: '';
	if( $inl == '1' )
		header("Content-Disposition: inline; filename=\"$file\""."\n");
	else
		header("Content-Disposition: attachment; filename=\"$file\""."\n");
	header("Content-Type: $mime"."\n");
	header("Content-Length: ". $fsize."\n");
	header("Content-transfert-encoding: binary"."\n");
	$fp=fopen($fullpath,"rb");
	while(!feof($fp)) {
		echo fread($fp,4096);
		}
	fclose($fp);
	}

/**
 * Create a new article draft
 * 
 * @param $idtopic
 * @param $idarticle
 * @return int return the id of the article draft created
 */
function bab_newArticleDraft($idtopic, $idarticle) {
	global $babDB, $BAB_SESS_USERID;
	
	$error = '';
	$id = bab_addArticleDraft(bab_translate("New article"), '', '', $idtopic, $error);
	if ($id === 0) {
		bab_debug("Error in bab_newArticleDraft() function : the function bab_addArticleDraft() can't add an article draft : ".$error);
	}

	$sDatePublication = '0000-00-00 00:00:00'; 
	$oRes = $babDB->db_query("select date_publication from ".BAB_ARTICLES_TBL." where id='".$babDB->db_escape_string($idarticle)."'");
	if ($oRes && $babDB->db_num_rows($oRes) == 1) {
		list($sDatePublication) = $babDB->db_fetch_array($oRes);
	}
	$babDB->db_query("update ".BAB_ART_DRAFTS_TBL." set id_article='".$babDB->db_escape_string($idarticle). "', date_publication='".$babDB->db_escape_string($sDatePublication). "' where id='".$id."'");
	
	if( $idarticle != 0 ) {
		$res = $babDB->db_query("select * from ".BAB_ARTICLES_TBL." where id='".$babDB->db_escape_string($idarticle)."'");
		if ($res && $babDB->db_num_rows($res) == 1 ) {
			$arr = $babDB->db_fetch_array($res);
			$babDB->db_query("update ".BAB_ART_DRAFTS_TBL." set head='".$babDB->db_escape_string($arr['head'])."', body='".$babDB->db_escape_string($arr['body'])."', title='".$babDB->db_escape_string($arr['title'])."', date_archiving='".$babDB->db_escape_string($arr['date_archiving'])."', lang='".$babDB->db_escape_string($arr['lang'])."', restriction='".$babDB->db_escape_string($arr['restriction'])."' where id='".$babDB->db_escape_string($id)."'");

			$res = $babDB->db_query("select * from ".BAB_ART_FILES_TBL." where id_article='".$babDB->db_escape_string($idarticle)."'");
			$pathorg = bab_getUploadArticlesPath();
			$pathdest = bab_getUploadDraftsPath();
			while ($rr = $babDB->db_fetch_array($res)) {
				if ( copy($pathorg.$idarticle.",".$rr['name'], $pathdest.$id.",".$rr['name'])) {
					$babDB->db_query("insert into ".BAB_ART_DRAFTS_FILES_TBL." (id_draft, name, description, ordering) values ('".$id."','".$babDB->db_escape_string($rr['name'])."','".$babDB->db_escape_string($rr['description'])."','".$babDB->db_escape_string($rr['ordering'])."')");
				}
			}	
				
			require_once dirname(__FILE__) . '/tagApi.php';
		
			$oReferenceMgr = bab_getInstance('bab_ReferenceMgr');
		
			$oIterator = $oReferenceMgr->getTagsByReference(bab_Reference::makeReference('ovidentia', '', 'articles', 'article', $idarticle));
			$oIterator->orderAsc('tag_name');
			$oReferenceDraft = bab_Reference::makeReference('ovidentia', '', 'articles', 'draft', $id);
			foreach($oIterator as $oTag) {
				$oReferenceMgr->add($oTag->getName(), $oReferenceDraft);
			}
		}
	}

	return $id;
}







/**
 * Index all articles files
 * @param array $status
 * @return object bab_indexReturn
 */
function indexAllArtFiles($status, $prepare) 
	{
	
	global $babDB;

	$res = $babDB->db_query("
	
		SELECT 
			f.id,
			f.name,
			f.id_article, 
			a.id_topic 

		FROM 
			".BAB_ART_FILES_TBL." f,
			".BAB_ARTICLES_TBL." a 
		WHERE 
			a.id = f.id_article 
			AND f.index_status IN(".$babDB->quote($status).")
		
	");

	
	$files = array();
	$rights = array();
	$fullpath = bab_getUploadArticlesPath();

	$articlepath = 'articles/';

	

	while ($arr = $babDB->db_fetch_assoc($res)) {
		$files[] = $fullpath.$arr['id_article'].",".$arr['name'];
		$rights[$articlepath.$arr['id_article'].",".$arr['name']] = array(
				'id_file'		=> $arr['id'],
				'id_topic'		=> $arr['id_topic']
			);
	}

	if (!$files) {
		$r = new bab_indexReturn;
		$r->addError(bab_translate("No files to index in the articles"));
		$r->result = false;
		return $r;
	}


	include_once $GLOBALS['babInstallPath']."utilit/indexincl.php";
	$obj = new bab_indexObject('bab_art_files');


	$param = array(
			'status' => $status,
			'rights' => $rights
		);

	if (in_array(BAB_INDEX_STATUS_INDEXED, $status)) {
		if ($prepare) {
			return $obj->prepareIndex($files, $GLOBALS['babInstallPath'].'utilit/artincl.php', 'indexAllArtFiles_end', $param );
		} else {
			$r = $obj->resetIndex($files);
		}
	} else {
		$r = $obj->addFilesToIndex($files);
	}

	if (true === $r->result) {
		indexAllArtFiles_end($param);
	}

	return $r;
}


/**
 * 
 */
function indexAllArtFiles_end($param) {
	
	global $babDB;

	$babDB->db_query("
	
		UPDATE ".BAB_ART_FILES_TBL." SET index_status='".BAB_INDEX_STATUS_INDEXED."'
		WHERE 
			index_status IN('".implode("','",$param['status'])."')
		
	");

	include_once $GLOBALS['babInstallPath']."utilit/indexincl.php";
	$obj = new bab_indexObject('bab_art_files');

	foreach($param['rights'] as $f => $arr) {
		$obj->setIdObjectFile($f, $arr['id_file'], $arr['id_topic']);
	}

	return true;
}







/**
 * Create a temporary file for indexation
 *
 * @param	int		$id_topic
 * @param	int		$id_article
 * @param	string	$title
 * @param	string	$head
 * @param	string	$body
 * @param	string	$author
 *
 * @return 	string				path to temporary file
 */
function bab_createArticleFile($id_topic, $id_article, $title, $head, $body, $author) {

	include_once dirname(__FILE__).'/searcharticlesincl.php';

	$path = $GLOBALS['babUploadPath'].'/tmp/';

	if (!is_dir($path)) {
		bab_mkdir($path);
	}

	$filecontent = bab_sprintf('<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
		<html>
			<head>
				<title>%s</title>
				<meta http-equiv="Content-type" content="text/html; charset=%s" />
				<meta name="Author" content="%s" />
			</head>

			<body>
				<h1>%s</h1>
				<p>%s</p>
				<div class="head">
					%s
				</div>
				<hr />
				<div  class="body">
					%s
				</div>
				<p align="right">%s</p>
			</body>
		</html>
	', $title, bab_charset::getISO(), $author, $title, bab_SearchRealmTopic::categoriesHierarchy($id_topic), $head, $body, $author);

	$filename = bab_sprintf('top%d_art%d.html', $id_topic, $id_article);

	if (file_put_contents($path.$filename, $filecontent)) {
		return $path.$filename;
	}

	return false;
}






/**
 * Index all articles
 * @param array $status
 * @return object bab_indexReturn
 */
function indexAllArticles($status, $prepare) 
	{

	
	
	global $babDB;

	$res = $babDB->db_query("
	
		SELECT 
			a.id,
			a.title,
			a.head, 
			a.body,
			a.id_topic, 
			a.id_author 
		FROM 
			".BAB_ARTICLES_TBL." a 
		WHERE 
			a.index_status IN(".$babDB->quote($status).")
		
	");
	
	$files = array();
	$rights = array();


	while ($arr = $babDB->db_fetch_assoc($res)) {
		
		$file = bab_createArticleFile(
			$arr['id_topic'], 
			$arr['id'], 
			$arr['title'], 
			$arr['head'], 
			$arr['body'], 
			bab_getUserName($arr['id_author'])
		);


		$files[] = $file;

		$rights[$file] = array(
				'id_article'	=> $arr['id'],
				'id_topic'		=> $arr['id_topic']
			);
	}

	if (!$files) {
		$r = new bab_indexReturn;
		$r->addError(bab_translate("No files to index in the articles"));
		$r->result = false;
		return $r;
	}


	include_once $GLOBALS['babInstallPath']."utilit/indexincl.php";
	$obj = new bab_indexObject('bab_articles');


	$param = array(
			'status' => $status,
			'rights' => $rights
		);

	if (in_array(BAB_INDEX_STATUS_INDEXED, $status)) {
		if ($prepare) {
			return $obj->prepareIndex($files, $GLOBALS['babInstallPath'].'utilit/artincl.php', 'indexAllArticles_end', $param );
		} else {
			$r = $obj->resetIndex($files);
		}
	} else {
		$r = $obj->addFilesToIndex($files);
	}

	if (true === $r->result) {
		indexAllArticles_end($param);
	}

	return $r;
}


/**
 * clean article indexation temporary files and update indexation status
 */
function indexAllArticles_end($param) {

	global $babDB;

	$babDB->db_query("
	
		UPDATE ".BAB_ARTICLES_TBL." SET index_status='".BAB_INDEX_STATUS_INDEXED."'
		WHERE 
			index_status IN('".implode("','",$param['status'])."')
		
	");

	foreach($param['rights'] as $f => $arr) {
		unlink($f);
	}

	return true;
}



