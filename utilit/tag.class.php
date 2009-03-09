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

/**
 * 
 * This class represent a tag
 *
 */
class bab_Tag
{
	private $iId		= 0;
	private $sName		= '';
	
	public function __construct()
	{
		
	}
	
	/**
	 * Return the tag identifier
	 * 
	 * @return int The tag identifier
	 */
	public function getId()
	{
		return $this->iId;
	}
	
	/**
	 * Set the tag identifier
	 * 
	 * @param int $iId The tag identifier
	 */
	public function setId($iId)
	{
		$this->iId = $iId;
	}
	
	/**
	 * Return the name of the tag
	 * 
	 * @return strign The name of the tag
	 */
	public function getName()
	{
		return $this->sName;
	}
	
	/**
	 * Set the name of the tag
	 * 
	 * @param string $sName The name of the tag
	 */
	public function setName($sName)
	{
		$this->sName = $sName;
	}
}




/**
 * 
 * This class represent a tag and number of associated refercences
 *
 */
class bab_TagRefCount extends bab_Tag
{
	/**
	 * @var int
	 */
	private $refcount = null;

	/**
	 * Set number of references
	 * @param	int		$refcount
	 */
	public function setRefCount($refcount) {
		$this->refcount = (int) $refcount;
		return $this;
	}

	/**
	 * @return 	int
	 */
	public function getRefCount() {
		return $this->refcount;
	}
}








/**
 * This class is the iterator of the tags 
 *
 */
class bab_TagIterator extends bab_MySqlIterator
{
	/**
	 * 
	 * @param $oDataBaseAdapter The database adapter
	 */
	public function __construct($oDataBaseAdapter = null)
	{
		parent::__construct($oDataBaseAdapter);
	}
	
	/**
	 * This function convert a data to a bab_Tab object 
	 * 
	 * @param array $aDatas The data returned from mySqlFectAssoc
	 * 
	 * @return bab_Tag 
	 */
	public function getObject($aDatas)
	{
		$oTag = new bab_Tag();
		$oTag->setId($aDatas['iId']);
		$oTag->setName($aDatas['sName']);
		return $oTag;
	}
}






/**
 * This class is the iterator of the tags 
 *
 */
class bab_TagRefCountIterator extends bab_TagIterator
{
	
	/**
	 * This function convert a data to a bab_Tab object 
	 * 
	 * @param array $aDatas The data returned from mySqlFectAssoc
	 * 
	 * @return bab_TagRefCount 
	 */
	public function getObject($aDatas)
	{
		$oTag = new bab_TagRefCount();
		$oTag->setId($aDatas['iId']);
		$oTag->setName($aDatas['sName']);
		$oTag->setRefCount($aDatas['iCount']);
		return $oTag;
	}
}
