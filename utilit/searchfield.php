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
include_once "base.php";
include_once dirname(__FILE__)."/searchcriterion.php";


/**
 * a search field is an object used in <code>bab_SearchRecord</code> to describe data
 * a search record contain one or more search fields
 * It is also used to build a search criteria
 *
 * @see bab_SearchRecord
 * @see bab_SearchCriteria
 *
 * @package search
 */
class bab_SearchField extends bab_SearchTestable {

	private $sName			= '';
	private $sDescription	= '';

	private $tableAlias		= null;
	private $realName		= null;

	private	$searchable		= true;
	private $virtual		= false;

	/**
	 * @var bab_SearchRealm
	 */
	private $realm 			= null;

	/**
	 * Set search realm associated to this field
	 * @return bab_searchResult
	 */
	public function setRealm($realm) {
		$this->realm = $realm;
	}

	/**
	 * Get search realm associated to this field
	 * @return bab_SearchRealm | null
	 */
	public function getRealm() {
		return $this->realm;
	}


	/**
	 * Set the name of the field
	 *
	 * @param string $sName The name of the field
	 */
	 public function setName($sName)
	 {
		$this->sName = $sName;
		return $this;
	 }

	
	/**
	 * Returns the field name.
	 *
	 * @return string
	 */
	public function getName()
	{
		return $this->sName;
	}

	
	/**
	 * Sets the field textual description.
	 */
	public function setDescription($sDescription)
	{
		assert('is_string($sDescription); /* Parameter $sDescription must be a string. */');
		$this->sDescription = $sDescription;
		return $this;
	}

	
	/**
	 * Returns the field textual description.
	 *
	 * @return string
	 */
	public function getDescription()
	{
		return $this->sDescription;
	}

	/**
	 * @return bab_SearchField
	 */
	public function setRealName($str) {
		$this->realName = $str;
		return $this;
	}

	public function getRealName() {
		if (null !== $this->realName) {
			return $this->realName;
		}

		return $this->getName();
	}


	

	/**
	 * @return bab_SearchField
	 */
	public function setTableAlias($str) {
		$this->tableAlias = $str;
		return $this;
	}

	public function getTableAlias() {
		return $this->tableAlias;
	}


	/**
	 * Get and set searchable status
	 * searchable status is used in queries
	 *	if searchable is set to false the mysql backend will ignore the field in where clauses
	 * @param	bool	$param		optional value to set status
	 * @return bool | bab_SearchField
	 */
	public function searchable($param = null) {

		if (null !== $param) {
			$this->searchable = $param;
			return $this;
		}

		return $this->searchable;
	}



	/**
	 * Get and set virtual status
	 * virtual status is used in queries, if a field is virtual it is also NOT searchable
	 *	if virtual is set to true the mysql query will not try to retrive this field as a mysql table field
	 * @param	bool	$param		optional value to set status
	 * @return bool | bab_SearchField
	 */
	public function virtual($param = null) {

		if (null !== $param) {
			$this->virtual = $param;
			if (true === $param) {
				$this->searchable(false);
			}
			return $this;
		}

		return $this->virtual;
	}
}

