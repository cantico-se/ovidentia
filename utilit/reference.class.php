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
require_once dirname(__FILE__) . '/criteria.class.php';
require_once dirname(__FILE__) . '/iterator/iterator.php';
require_once dirname(__FILE__) . '/tagApi.php';
require_once dirname(__FILE__) . '/uuid.php';




interface IGuid
{
	public function getGuid(); 
}




interface IReferenceDescription
{
	/**
	 * @return string
	 */
	public function getTitle();

	/**
	 * @return string	HTML
	 */
	public function getDescription();

	/**
	 * @return string
	 */
	public function getType();

	/**
	 * @return string
	 */
	public function getUrl();

	/**
	 * @return bool
	 */
	public function isAccessValid();
}




class bab_ObjectStorage implements Iterator, Countable
{
	private $aStorage	= array();
	private $iKey		= 0;
	private $oObject	= null;
	
	const EOF = -1;
	
	public function __construct()
	{
		
	}
	
	public function attach(IGuid $oObject)
	{
		if(!$this->contains($oObject))
		{
			$this->aStorage[$oObject->getGuid()] = $oObject;
		}
	}
	
	public function detach(IGuid $oObject)
	{
		if($this->contains($oObject))
		{
			unset($this->aStorage[$oObject->getGuid()]);
			$this->rewind();
		}
	}
	
	public function contains(IGuid $oObject)
	{
		return array_key_exists($oObject->getGuid(), $this->aStorage);
	}
 	
 	public function count()
 	{
 		return count($this->aStorage);	
 	}
 	
 	public function current()
 	{
		if(is_null($this->oObject))
		{
			return $this->next();
		}
		return $this->oObject;
 	}
 	
	public function key()   
	{
		return $this->iKey;
	}
 	
	public function next()
	{
		$aDatas = each($this->aStorage);
		if(false !== $aDatas)
		{
			$this->iKey++;
			$this->oObject = $aDatas['value'];
		}
		else 
		{
			$this->oObject	= null;
			$this->iKey		= self::EOF;
		}
	}
	
 	public function rewind()
 	{
 		$this->iKey = 0;
 		reset($this->aStorage);
 	}
 	
 	public function valid()
 	{
 		if(0 !== $this->iKey && self::EOF !== $this->iKey)
		{
			return true;
		}
		else if(0 === $this->iKey)
		{
			$this->next();
			return $this->valid();
		}
		else if(self::EOF === $this->iKey)
		{
			return false;
		}
		return false;
 	}
}




class bab_ReferenceFilter implements IGuid
{
	private $sLocation	= null;
	private $sModule	= null;
	private $sType		= null;
	
	public function __construct($sLocation, $sModule, $sType)
	{
		$this->sLocation	= $sLocation;
		$this->sModule		= $sModule;
		$this->sType		= $sType;
		$this->iGuid		= sprintf('%u', crc32($sLocation . $sModule . $sType));
	}
	
	public function getGuid()
	{
		return $this->iGuid;  
	}
	
	public function getLocation()
	{
		return $this->sLocation;
	}
	
	public function getModule()
	{
		return $this->sModule;
	}
	
	public function getType()
	{
		return $this->sType;
	}
}




class bab_ReferenceFilters extends bab_ObjectStorage
{
	public function __construct()
	{
		parent::__construct();
	}
}




class bab_Reference implements IGuid
{
	private $sReference = null;
	private $sProtocol	= null;
	private $sLocation	= null;
	private $sModule	= null;
	private $sType		= null;
	private $iIdObject	= null;
	
	public function __construct($sReference = null)
	{
		if (null !== $sReference) {
			
			$this->sReference = $sReference;
			
			// create reference from string
			$this->init($sReference);
		}
	}
	
	public function initFromParts($protocol, $location, $module, $type, $idobject)
	{
		$this->sProtocol	= $protocol; 
		$this->sLocation	= $location; 
		$this->sModule		= $module; 
		$this->sType		= $type;
		$this->iIdObject	= $idobject;
	}
	
	public function __toString()
	{
		return $this->sProtocol . '://' . urlencode($this->sLocation) . '/' . urlencode($this->sModule) . '/' . urlencode($this->sType) . '/' . urlencode($this->iIdObject);  
	}
	
	public function getGuid()
	{
		if (null === $this->sReference)
		{
			$this->sReference = $this->__toString();
		}
		return $this->sReference;
	}
	
	public function getProtocol()
	{
		return $this->sProtocol;
	}

	public function getLocation()
	{
		return $this->sLocation;
	}
	
	public function getModule()
	{
		return $this->sModule;
	}
	
	public function getType()
	{
		return $this->sType;
	}
	
	public function getObjectId()
	{
		return $this->iIdObject;
	}
	
	/**
	 * Build a reference
	 * 
	 * @param string $sProtocol
	 * @param string $sLocation
	 * @param string $sModule
	 * @param string $sType
	 * @param mixed $iIdObject
	 * @return bab_Reference
	 */
	public static function makeReference($sProtocol, $sLocation, $sModule, $sType, $iIdObject)
	{
		$reference = new bab_Reference();
		$reference->initFromParts($sProtocol, $sLocation, $sModule, $sType, $iIdObject);
		return $reference;
	}


	/**
	 * Return the reference description for a reference.
	 * 
	 * @param bab_Reference	$oReference	The name of the tag
	 * 
	 * @return IReferenceDescription
	 */
	public static function getReferenceDescription(bab_Reference $oReference)
	{
		$oRefMapStorage		= new bab_StorageMap();
		$oRefDescMapStorage	= new bab_StorageMap();
		
		$sKey = $oReference->getModule() . '.' . $oReference->getType();
		
		$oRefMapStorage->createStorage($sKey);
		$oRefDescMapStorage->createStorage($sKey);
		
		$oRefMapStorage->get($sKey)->attach($oReference);
		
		$oEventReference = new bab_eventReference($oRefMapStorage, $oRefDescMapStorage);
		
		
		
		bab_fireEvent($oEventReference);
		$oIt = $oEventReference->getReferenceDescriptionStorage()->getIterator();
		
		$oIt->rewind();
		if($oIt->valid())
		{
			return $oIt->current();
		}
		
		return null;
	}

	
	private function init($sReference)
	{
		$aBuffer = array();
		if(1 === preg_match('#^(ovidentia)://([^/]*)/([^/]+)/([^/]+)/(.+)$#', $sReference, $aBuffer))
		{
			//bab_debug($aBuffer);
			$this->sProtocol	= $aBuffer[1]; 
			$this->sLocation	= urldecode($aBuffer[2]); 
			$this->sModule		= urldecode($aBuffer[3]); 
			$this->sType		= urldecode($aBuffer[4]);
			$this->iIdObject	= urldecode($aBuffer[5]);
		}
		else
		{
			throw new Exception(bab_translate('The reference is not valid'));
		}
	}
}




class bab_TagReferenceIterator extends bab_MySqlIterator
{
	public function __construct($oDataBaseAdapter = null)
	{
		parent::__construct($oDataBaseAdapter);
	}
	
	public function getObject($aDatas)
	{
		$oReference = new bab_Reference($aDatas['sReference']);
		return $oReference;
	}
}




class bab_AppendIterator extends AppendIterator
{
	public function __construct()
	{
		parent::__construct();
	}
	
	public function count()
	{
		$this->rewind();
		
		$iCount = 0;
		foreach($this as $oObject)
		{
			++$iCount;
		}
		return $iCount;
	}
}




class bab_StorageMap
{
	private $aMap = array();
	
	public function __construct()
	{
		
	}
	
	public function createStorage($sModule)
	{
		if(!$this->contains($sModule))
		{
			$this->aMap[$sModule] = new bab_ObjectStorage();
		}
	}
	
	public function destroyStorage($sModule)
	{
		if($this->contains($sModule))
		{
			unset($this->aMap[$sModule]);
		}
	}
	
	public function contains($sModule)
	{
		return array_key_exists($sModule, $this->aMap);
	}
	
	public function add($sModule, IGuid $oObject)
	{
		if($this->contains($sModule))
		{
			$this->aMap[$sModule]->attach($oObject);
		}
	}
	
	public function remove($sModule, IGuid $oObject)
	{
		if($this->contains($sModule))
		{
			$this->aMap[$sModule]->detach($oObject);
		}
	}
	
	public function get($sModule)
	{
		if($this->contains($sModule))
		{
			return $this->aMap[$sModule];
		}
		return null;
	}
	
	public function getIterator()
	{
	    $oIt = new bab_AppendIterator();
	    foreach($this->aMap as $sModule => $oStorage)
	    {
	        $oIt->append($oStorage);
	    }
	    return $oIt;
	}
}




abstract class bab_ReferenceDescriptionImpl implements IReferenceDescription, IGuid
{
	
	private $oReference = null;
	private $sReference	= null;
	private $parameters = null; 
	
	public function __construct(bab_Reference $oReference)
	{
		$this->oReference = $oReference;
		$this->sReference = $oReference->__toString();
	}
	
	public function getGuid()
	{
		return $this->sReference;
	}

	public function getReference() {
		return $this->oReference;
	}
	
	/**
	 * Set additional parameters for the reference description
	 * @param array $arr
	 * @return unknown_type
	 */
	public function setParameters(Array $arr)
	{
		$this->parameters = $arr;
	}
	
	public function getParameters()
	{
		return $this->parameters;
	}
}



/**
 * File manager reference description
 */
class bab_FileReferenceDescription extends bab_ReferenceDescriptionImpl
{
	private $oFile = null;


	public function getType()
	{
		return bab_translate('File');
	}

	/**
	 * @return string
	 */
	public function getTitle() {
		return $this->getFile()->getName();
	}

	/**
	 * @return string	HTML
	 */
	public function getDescription() {
		return $this->getFile()->getDescription();
	}

	/**
	 * @return string
	 */
	public function getUrl() {
		return $this->getFile()->getDownloadUrl();
	}

	/**
	 * @return BAB_FolderFile
	 */
	private function getFile() {
		
		if (null === $this->oFile) {
			
			$oFolderFileSet = bab_getInstance('BAB_FolderFileSet');
			$oId			= $oFolderFileSet->aField['iId'];
			$this->oFile	= $oFolderFileSet->get($oId->in($this->getReference()->getObjectId()));

			if (!($this->oFile instanceOf BAB_FolderFile)) {
				throw new Exception('invalid BAB_FolderFile object');
			}
		}
		
		return $this->oFile;
	}

	/**
	 * @return bool
	 */
	public function isAccessValid() 
	{
		require_once dirname(__FILE__).'/fileincl.php';
		return bab_FmFileCanDownload($this->getReference()->getObjectId());
	}
}








/**
 * File manager reference description
 */
class bab_FolderReferenceDescription extends bab_ReferenceDescriptionImpl
{
	private $folderInfos = null;
	

	public function getType()
	{
		return bab_translate('Folder');
	}

	/**
	 * @return string
	 */
	public function getTitle() {
		return $this->getReference()->getObjectId();
	}

	/**
	 * @return string	HTML
	 */
	public function getDescription() {
		return '';
	}
	
	/**
	 * @return array id_delegation, path
	 */
	protected function getPath() {
		$fullpath = $this->getReference()->getObjectId();
		$arr = explode('/', $fullpath);
		
		$id_delegation = 0;
		
		if (preg_match('/^DG(\d+)$/', $arr['0'], $m)) {
			$id_delegation = (int) $m[1];
			array_shift($arr);
		}
		
		$path = implode('/', $arr);
		
		return array($id_delegation, $path);
	}

	/**
	 * @return string
	 */
	public function getUrl() {
		
		list($id_delegation, $path) = $this->getPath();
		
		list($iIdRootFolder, $oFmFolder) = $this->getFolder();
		
		return '?tg=fileman&idx=list&id='.$iIdRootFolder.'&gr=Y&path='.urlencode($path);
	}
	
	/**
	 * @return array $iIdRootFolder, $oFmFolder
	 */
	protected function getFolder() {
		
		if (null === $this->folderInfos)
		{
			require_once dirname(__FILE__).'/fmset.class.php';
			require_once dirname(__FILE__).'/fileincl.php';
			
			list($id_delegation, $path) = $this->getPath();
			BAB_FmFolderHelper::getInfoFromCollectivePath($path, $iIdRootFolder, $oFmFolder, false, $id_delegation);
			
			$this->folderInfos = array($iIdRootFolder, $oFmFolder);
		}
		
		return $this->folderInfos;
	}


	/**
	 * 
	 * @return bool
	 */
	public function isAccessValid() 
	{
		
		list($iIdRootFolder, $oFmFolder) = $this->getFolder();
		
		if (null === $oFmFolder)
		{
			list($id_delegation, $path) = $this->getPath();
			bab_debug("Folder not found from path : $path (delegation : $id_delegation)");
			return false;
		}
		
		
		/*@var $oFmFolder BAB_FmFolder */
		
		if (bab_isAccessValid(BAB_FMDOWNLOAD_GROUPS_TBL, $oFmFolder->getId()))
		{
			return true;
		}
		
		if (bab_isAccessValid(BAB_FMUPDATE_GROUPS_TBL, $oFmFolder->getId()))
		{
			return true;
		}
		
		if (bab_isAccessValid(BAB_FMUPLOAD_GROUPS_TBL, $oFmFolder->getId()))
		{
			return true;
		}
		
		return bab_isAccessValid(BAB_FMMANAGERS_GROUPS_TBL, $oFmFolder->getId());
	}
}








/**
 * File manager reference description
 */
class bab_PersonnalFolderReferenceDescription extends bab_ReferenceDescriptionImpl
{

	public function getType()
	{
		return bab_translate('Personnal folder');
	}

	/**
	 * @return string
	 */
	public function getTitle() {
		return $this->getReference()->getObjectId();
	}

	/**
	 * @return string	HTML
	 */
	public function getDescription() {
		return '';
	}

	/**
	 * @return string
	 */
	public function getUrl() {
		return '?tg=fileman&idx=list&gr=N&path='.urlencode($this->getReference()->getObjectId());
	}


	/**
	 * 
	 * @return bool
	 */
	public function isAccessValid() 
	{
		if (!$GLOBALS['BAB_SESS_LOGGED'])
		{
			return false;
		}
		
		require_once dirname(__FILE__).'/fileincl.php';
		
		$folder = BAB_FileManagerEnv::getFmRealPersonalPath().'U'.$GLOBALS['BAB_SESS_USERID'].'/'.$this->getReference()->getObjectId();
		
		if(userHavePersonnalStorage() && is_dir($folder))
		{
			return true;
		}
		return false;
	}
}










class bab_ArticleReferenceDescription extends bab_ReferenceDescriptionImpl
{

	/**
	 * @return string
	 */
	public function getTitle() {
		$arr = $this->getArticle();
		return $arr['title'];
	}

	/**
	 * @return string	HTML
	 */
	public function getDescription() {
		$arr = $this->getArticle();
		return $arr['head'];
	}

	/**
	 * @return string
	 */
	public function getUrl() {
		$arr = $this->getArticle();
		
		$parameters = (array) $this->getParameters();
		
		if (isset($parameters['popup']))
		{
			return $GLOBALS['babUrlScript'] . '?tg=articles&idx=viewa&article='.$arr['id'].'&topics='.$arr['id_topic'];
		}
		
		// the topic parameter is not required but present for compatibility with old skins
		return $GLOBALS['babUrlScript'] . '?tg=articles&idx=More&article='.$arr['id'].'&topic='.$arr['id_topic'];
	}


	public function getType()
	{
		return bab_translate('Article');
	}

	public function isAccessValid() 
	{
		$arr = $this->getArticle();
		return bab_isAccessValid(BAB_TOPICSVIEW_GROUPS_TBL, $arr['id_topic']);
	}

	protected function getObjArray()
	{
		require_once dirname(__FILE__) . '/artapi.php';
		return bab_getArticleArray($this->getReference()->getObjectId());
	}

	/**
	 * @return array
	 */
	protected function getArticle()
	{
		
		$arr = $this->getObjArray();

		if (!$arr) {
			throw new Exception('No article for the reference '.$this->getReference());
		}

		return $arr;
	}
}




class bab_DraftArticleReferenceDescription extends bab_ArticleReferenceDescription
{
	/**
	 * @return string
	 */
	public function getUrl() 
	{
		// $arr = $this->getArticle();
		// return $GLOBALS['babUrlScript'] . '?tg=artedit&idx=preview&idart='.$arr['id'];
		return $GLOBALS['babUrlScript'] . '?tg=artedit';
	}


	public function getType()
	{
		return bab_translate('Article draft');
	}

	public function isAccessValid() 
	{
		$arr = $this->getArticle();
		return $GLOBALS['BAB_SESS_LOGGED'] && (((int) $arr['id_author']) === ((int) $GLOBALS['BAB_SESS_USERID']));
	}

	protected function getObjArray()
	{
		require_once dirname(__FILE__) . '/artapi.php';
		return bab_getDraftArticleArray($this->getReference()->getObjectId());
	}
}







/**
 * Ovml file reference description
 * ovidentia:///ovml/file/example.html
 */
class bab_OvmlFileReferenceDescription extends bab_ReferenceDescriptionImpl
{
	public function getType()
	{
		return bab_translate('OVML file');
	}

	/**
	 * @return string
	 */
	public function getTitle() {
		return $this->getReference()->getObjectId();
	}

	/**
	 * @return string	HTML
	 */
	public function getDescription() {
		$parameters = (array) $this->getParameters();
		return bab_printOvmlTemplate($this->getReference()->getObjectId(), $parameters);
	}

	/**
	 * @return string
	 */
	public function getUrl() {
		return '?tg=oml&file='.urlencode($this->getReference()->getObjectId());
	}

	/**
	 * @throws Exception if not a valid file
	 * @return bool
	 */
	public function isAccessValid() 
	{
		global $babOvmlPath;
		
		$filepath = $babOvmlPath.$this->getReference()->getObjectId();
		
		if (!file_exists($filepath))
		{
			throw new Exception(sprintf(bab_translate('The ovml file %s does not exists'), $this->getReference()->getObjectId()));
		}
		
		return true;
	}
}

