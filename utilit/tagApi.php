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
 * @copyright Copyright (c) 2006 by CANTICO ({@link http://www.cantico.fr})
 */
require_once 'base.php';
require_once dirname(__FILE__) . '/tag.class.php';
require_once dirname(__FILE__) . '/reference.class.php';
require_once dirname(__FILE__) . '/eventReference.php';



/**
 * This class allow tag management
 */
class bab_TagMgr
{
	private $bManageThesaurus	= false;
	private $oIterator			= null;
	
	public function __construct()
	{
		$this->bManageThesaurus = bab_isAccessValid(BAB_TAGSMAN_GROUPS_TBL, 1);
		$this->oIterator = new bab_TagIterator();
	}

	/**
	 * Create a tag. 
	 * If the caller do not have manager right so this function return false.
	 * If a tag with the same name already exit so this function return false.
	 * 
	 * @param 	string 	$sName 			The name of the tag
	 * @param	bool	$testManager	to disable manager access rights test
	 * 
	 * @return bab_Tag|false The new created tag on success, false otherwise
	 */
	public function create($sName, $testManager = true)
	{
		if($testManager && false === $this->haveManagerRight())
		{
			return false;
		}
		
		if(true === $this->exist($sName))
		{
			return false;
		}
		
		global $babDB;
	
		$sQuery = 
			'INSERT INTO ' . BAB_TAGS_TBL . '
				(`id`, `tag_name`) 
			VALUES
				(\'\', ' . $babDB->quote($sName) . ')'; 
	
		//bab_debug($sQuery);
		$oResult = $babDB->db_query($sQuery);
		if(false !== $oResult)
		{
			$oTag = new bab_Tag();
			$oTag->setId($babDB->db_insert_id());
			$oTag->setName($sName);
			return $oTag;
		}
		return false;
	}

	/**
	 * Allow to update the name of tag.
	 * If the caller do not have manager right so this function return false.
	 * 
	 * @param int 		$iId	The tag identifier for wich the new name must be set
	 * @param string	$sName	The new name of the tag
	 * 
	 * @return bool	True on success, false otherwise.
	 */
	public function update($iId, $sName)
	{
		if(false === $this->haveManagerRight())
		{
			return false;
		}
		
		if(true === $this->exist($sName, $iId))
		{
			return false;
		}
		
		global $babDB;
	
		$sQuery = 
			'UPDATE ' . 
				BAB_TAGS_TBL . '
			SET 
				`tag_name` = ' . $babDB->quote($sName) . '
			WHERE 
				`id` = ' . $babDB->db_escape_string($iId);
	
		//bab_debug($sQuery);
		return $babDB->db_query($sQuery);
	}

	/**
	 * Delete a tag
	 * 
	 * @param int $iId The identifier of the tag to delete
	 * 
	 * @return bool True on success, false otherwise
	 */
	public function delete($iId)
	{
		if(false === $this->haveManagerRight())
		{
			return false;
		}
		
		$oTag = $this->getById($iId);
		if($oTag instanceof bab_Tag)
		{
			$oReferenceMgr = bab_getInstance('bab_ReferenceMgr');
			$oReferenceMgr->removeByTag($oTag->getName());
		}
		
		global $babDB;
		$sQuery = 'DELETE FROM ' . BAB_TAGS_TBL . ' where id = ' . $babDB->quote($iId);
	
		//bab_debug($sQuery);
		return $babDB->db_query($sQuery);
	}

	/**
	 * Return a tag.
	 * 
	 * @param string $sName The name of the tag to return.
	 * 
	 * @return bab_Tag|null A bab_Tag on success, null otherwise.
	 */
	public function getByName($sName)
	{
		return $this->get((string) $sName);
	}

	/**
	 * Return a tag.
	 * 
	 * @param int $iId The identifier of the tag to return.
	 * 
	 * @return bab_Tag|null A bab_Tag on success, null otherwise.
	 */
	public function getById($iId)
	{
		return $this->get((int) $iId);
	}

	/**
	 * Return a bab_TagIterator.
	 * 
	 * @param array $aId Array of tag identifier
	 * 
	 * @return bab_TagIterator
	 */
	public function getByIds($aId)
	{
		$this->initIterator();
		
		$oId = new BAB_Field('id');
		$this->oIterator->setCriteria($oId->in($aId));
		
		return clone $this->oIterator;
	}
	
	/**
	 * Select all tags, return a bab_TagIterator
	 * 
	 * @return bab_TagIterator
	 */
	public function select()
	{
		$sQuery = 
			'SELECT 
				id iId,  
				tag_name sName 
			FROM ' .
				BAB_TAGS_TBL;
	
		$oIterator = new bab_TagIterator();
		$oIterator->setQuery($sQuery);
		return $oIterator;
	}


	/**
	 * Select all tags with refcount
	 * 
	 * @return bab_TagRefCountIterator
	 */
	public function selectRefCount()
	{
		$sQuery = 
			'SELECT 
				t.id iId,  
				t.tag_name sName,
				COUNT(r.reference) iCount 
			FROM ' .
				BAB_TAGS_TBL . ' t,
				bab_tags_references r 
			WHERE 
				r.id_tag = t.id 

			GROUP BY t.id 
			ORDER BY iCount DESC
		';
	
		$oIterator = new bab_TagRefCountIterator();
		$oIterator->setQuery($sQuery);
		return $oIterator;
	}





	
	/**
	 * Return a value that indicate if a tag exit.
	 * 
	 * @param string	$sName	The name of the tag
	 * @param int		$iId	The identifier of the tag. Optional parameter.
	 * 
	 * @return bool True on success, false otherwise.
	 */
	public function exist($sName, $iId = 0)
	{
		global $babDB;
		
		$this->initIterator();
		
		$oName = new BAB_Field('tag_name');
		$oCriteria = $oName->like($babDB->db_escape_like($sName));
		
		if(0 < (int) $iId)
		{
			$oId = new BAB_Field('id');
			$oCriteria = $oCriteria->_and($oId->notIn($iId));
		}
		
		$this->oIterator->setCriteria($oCriteria);
		return (1 === $this->oIterator->count());
	}
	
	/**
	 * Return a value that indicate if the connected user
	 * have management right.
	 * 
	 * @return bool True if the caller have management right, false otherwise.
	 */
	private function haveManagerRight()
	{
		return $this->bManageThesaurus;
	}
	
	/**
	 * Get a tagByName or tagById
	 * 
	 * @param int|string $mixedValue	If this param is a int so it must be a tag identifier.
	 * 									It this param is a string it must be a tag name.
	 * 
	 * @return bab_Tag|null A bab_Tag on success, null otherwise.
	 */
	private function get($mixedValue)
	{
		global $babDB;
		$this->initIterator();
		
		if(is_int($mixedValue))
		{
			$oId = new BAB_Field('id');
			$this->oIterator->setCriteria($oId->in($mixedValue));
		}
		else
		{
			$oName = new BAB_Field('tag_name');
			$this->oIterator->setCriteria($oName->like($babDB->db_escape_like($mixedValue)));
		}
		
		$this->oIterator->next();
		if($this->oIterator->valid())
		{
			return $this->oIterator->current();
		}
		return null;
	}
	
	/**
	 * Return a bab_tagIterator that select all the tag.
	 * To filter criteria can be used on the returned bab_tagIterator.
	 * 
	 * @return bab_tagIterator
	 */
	private function initIterator()
	{
		$sQuery =  
			'SELECT 
				id iId,  
				tag_name sName 
			FROM ' .
				BAB_TAGS_TBL;
		
		$this->oIterator->clear();
		$this->oIterator->setQuery($sQuery);
	}
}



/**
 * Class for management of tag and referenec (pivot table)
 *
 */
class bab_ReferenceMgr
{
	private $oIterator = null;
	
	public function __construct()
	{
		$this->oIterator = new bab_TagReferenceIterator();	
	}
	
	/**
	 * Return all the references for a tag
	 * 
	 * @param string 				$sTagName	Name of the tag
	 * @param bab_ReferenceFilters	$oFilter	Optional, reference filter
	 * 
	 * @return bab_TagReferenceIterator
	 */
	public function get($sTagName, bab_ReferenceFilters $oFilter = null)
	{
		$oTagMgr = bab_getInstance('bab_TagMgr');
		$oTag = $oTagMgr->getByName($sTagName);
		if(!($oTag instanceof bab_Tag))
		{
			return false;
		}

		$oId = new BAB_Field('id_tag');
		$oCriteria = $oId->in($oTag->getId());

		if($oFilter instanceof bab_ReferenceFilters)
		{
			$oRegExpCriteria = $this->getRegExpressionCriteria($oFilter);
			$oCriteria = $oCriteria->_and($oRegExpCriteria);
		}
		
		$this->initIterator();
		$this->oIterator->setCriteria($oCriteria);
		return clone $this->oIterator;
	}
	
	/**
	 * Return a tags for a reference
	 * 
	 * @param bab_Reference $oReference The reference for witch the tags must be returned
	 * 
	 * @return bab_TagIterator
	 */
	public function getTagsByReference(bab_Reference $oReference)
	{
		global $babDB;
		
		$sQuery =  
			'SELECT 
			 	id iId,
			 	id_tag iIdTag,
			 	reference sReference
			FROM 
				bab_tags_references 
			WHERE 
				reference = ' . $babDB->quote($oReference->__tostring());
		
		$aTagId		= array();
		$oResult	= $babDB->db_query($sQuery);
		
		if(false !== $oResult)
		{
			$iNumRows = $babDB->db_num_rows($oResult);
			if($iNumRows > 0)
			{
				$iIndex	= 0;
				
				while($iIndex < $iNumRows && null !== ($aDatas = $babDB->db_fetch_assoc($oResult)))
				{
					$iIndex++;
					$aTagId[] = $aDatas['iIdTag'];
				}
			}
		}
		
		$oTagMgr = bab_getInstance('bab_TagMgr');
		return $oTagMgr->getByIds($aTagId);
	}
	
	/**
	 * Associate a tag to a reference
	 * 
	 * @param string | bab_Tag		$tag			Name of the tag to associate
	 * @param bab_Reference 		$oReference		The reference to associate
	 * 
	 * @return bool True on success, false otherwise.
	 */
	public function add($tag, bab_Reference $oReference)
	{
		if (!($tag instanceof bab_Tag))
		{
			$oTagMgr	= bab_getInstance('bab_TagMgr');
			$tag		= $oTagMgr->getByName($tag);
			if(!($tag instanceof bab_Tag))
			{
				return false;
			}
		}

		global $babDB;
	
		$sQuery = 
			'INSERT INTO bab_tags_references
				(`id_tag`, `reference`) 
			VALUES
				(' . $babDB->quote($tag->getId()) . ',' . $babDB->quote($oReference->__tostring()) . ')'; 
	
		$oResult = $babDB->db_query($sQuery);
		if(false !== $oResult)
		{
			return true;
		}
		return false;
	}
	
	/**
	 * Dissociate a tag to a reference
	 * 
	 * @param string		$sTagName	The tag to dissociate
	 * @param bab_Reference	$oReference	The reference to dissociate
	 * 
	 * @return bool True on sucess, false otherwise
	 */
	public function remove($sTagName, bab_Reference $oReference)
	{
		$oTagMgr	= bab_getInstance('bab_TagMgr');
		$oTag		= $oTagMgr->getByName($sTagName);
		if(!($oTag instanceof bab_Tag))
		{
			return false;
		}
		
		global $babDB;
		$aWhereClauseItem = array();
		$aWhereClauseItem[] = 'id_tag = ' . $babDB->quote($oTag->getId());
		$aWhereClauseItem[] = 'reference = ' . $babDB->quote($oReference->__tostring());
	
		return $this->executeDeleteQuery($aWhereClauseItem);
	}
	
	/**
	 * Remove all the reference associated to a tag
	 * 
	 * @param string $sTagName The name of the tag
	 * 
	 * @return bool True on success, false otherwise
	 */
	public function removeByTag($sTagName)
	{
		$oTagMgr	= bab_getInstance('bab_TagMgr');
		$oTag		= $oTagMgr->getByName($sTagName);
		if(!($oTag instanceof bab_Tag))
		{
			return false;
		}
		
		global $babDB;
		$aWhereClauseItem = array();
		$aWhereClauseItem[] = 'id_tag = ' . $babDB->quote($oTag->getId());
	
		return $this->executeDeleteQuery($aWhereClauseItem);
	}
	
	/**
	 * Remove all association to a reference
	 * 
	 * @param bab_Reference $oReference The reference
	 * 
	 * @return bool True on success, false otherwise.
	 */
	public function removeByReference(bab_Reference $oReference)
	{
		global $babDB;
		$aWhereClauseItem = array();
		$aWhereClauseItem[] = 'reference = ' . $babDB->quote($oReference->__tostring());
	
		return $this->executeDeleteQuery($aWhereClauseItem);
	}
	
	/**
	 * Return all the reference description for a tag.
	 * 
	 * @param string 				$sTagName	The name of the tag
	 * @param bab_ReferenceFilters	$oFilter	Filter
	 * 
	 * @return bab_StorageMap An iterator that old all the reference description
	 */
	public function getReferencesDescriptions($sTagName, bab_ReferenceFilters $oFilter = null)
	{
		$oIterator = $this->get($sTagName, $oFilter);
		if(!($oIterator instanceof bab_TagReferenceIterator))
		{
			return false;
		}

		$aKey				= array();
		$oRefMapStorage		= new bab_StorageMap();
		$oRefDescMapStorage	= new bab_StorageMap();
		
		foreach($oIterator as $oReference)
		{
			$sKey = $oReference->getModule() . '.' . $oReference->getType();
			
			if(!array_key_exists($sKey, $aKey))
			{
				$oRefMapStorage->createStorage($sKey);
				$oRefDescMapStorage->createStorage($sKey);
				
				$aKey[$sKey] = $sKey;
			}
			$oRefMapStorage->get($sKey)->attach($oReference);
		}
		
		$oEventReference = new bab_eventReference($oRefMapStorage, $oRefDescMapStorage);
		bab_fireEvent($oEventReference);
		return $oEventReference->getReferenceDescriptionStorage()->getIterator();
	}
	
	
	
	
	//----------------------------------------------------
	private function initIterator()
	{
		$sQuery =  
			'SELECT 
			 	id iId,
			 	id_tag iIdTag,
			 	reference sReference
			FROM 
				bab_tags_references';
		
		$this->oIterator->clear();
		$this->oIterator->setQuery($sQuery);
	}
	
	private function getRegExpressionCriteria(bab_ReferenceFilters $oFilters)
	{
		$oReference		= new BAB_Field('reference');
		$oRegExpCrit	= null;
		
		$oFilters->rewind();
		
		if($oFilters->valid())
		{
			$oFilter = $oFilters->current();
			$oRegExpCrit = $oReference->regExp($this->getRegExpression($oFilter));
			$oFilters->next();
		}
		
		while($oFilters->valid())
		{
			$oFilter = $oFilters->current();
			$oRegExpCrit = $oRegExpCrit->_or($oReference->regExp($this->getRegExpression($oFilter)));
			$oFilters->next();
		}
		return $oRegExpCrit;
	}
	
	private function getRegExpression(bab_ReferenceFilter $oFilter)
	{
		$sLocation	= '.*';
		$sModule	= '.*';
		$sType		= '.*';
		
		if(0 !== mb_strlen($oFilter->getLocation()))
		{
			$sLocation = $oFilter->getLocation();
		}
		
		if(0 !== mb_strlen($oFilter->getModule()))
		{
			$sModule = $oFilter->getModule();
		}
		
		if(0 !== mb_strlen($oFilter->getType()))
		{
			$sType = $oFilter->getType();
		}
		
		return $sExpr = '^(ovidentia)://(' . $sLocation . ')/(' . $sModule . ')/(' . $sType . ')/(.*)$';
	}
	
	private function executeDeleteQuery($aWhereClauseItem)
	{
		global $babDB;
		
		$sQuery = 
			'DELETE FROM 
				bab_tags_references 
			WHERE ' .
				implode(' AND ', $aWhereClauseItem);
	
		//bab_debug($sQuery);
		return $babDB->db_query($sQuery);
	}
}

