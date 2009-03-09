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






/**
 * Abstract object used for a realm or a field 
 * Implement methods for a testable object, inherited class will have methods to get criteria
 *
 * @see bab_SearchRealm
 * @see bab_SearchField
 *
 * @package search
 */
abstract class bab_SearchTestable {


	
	/**
	 * Get the realm name, this string will be used in http requests
	 * @return 	string
	 */
	abstract public function getName();


	/**
	 * Get title of the real to display in a search form
	 * @return	string
	 */
	abstract public function getDescription();




	/**
	 * Return a greater than criterion
	 * 
	 * @param mixed	$mixedValue		One value or an array of values.
	 * 
	 * @return bab_SearchGreaterThanCriterion
	 */
	public function greaterThan($mixedValue)
	{
		return new bab_SearchGreaterThanCriterion($this, $mixedValue);
	}

	
	/**
	 * Return a greater than or equal criterion
	 * 
	 * @param mixed	$mixedValue		One value or an array of values.
	 * 
	 * @return bab_SearchGreaterThanOrEqualCriterion
	 */
	public function greaterThanOrEqual($mixedValue)
	{
		return new bab_SearchGreaterThanOrEqualCriterion($this, $mixedValue);
	}

	
	/**
	 * Return a less than criterion
	 * 
	 * @param mixed	$mixedValue		One value or an array of values.
	 * 
	 * @return bab_SearchLessThanCriterion
	 */
	public function lessThan($mixedValue)
	{
		return new bab_SearchLessThanCriterion($this, $mixedValue);
	}

	
	/**
	 * Return a less than or equal criterion
	 * 
	 * @param mixed	$mixedValue		One value or an array of values.
	 * 
	 * @return bab_SearchLessThanOrEqualCriterion
	 */
	public function lessThanOrEqual($mixedValue)
	{
		return new bab_SearchLessThanOrEqualCriterion($this, $mixedValue);
	}


	/**
	 * Returns an equal criterion "name = 'Samuel'" 
	 * 
	 * @param mixed	$mixedValue		A scalar value.
	 * 
	 * @return bab_SearchIsCriterion
	 */
	public function is($mixedValue)
	{
		require_once dirname(__FILE__).'/searchcriterion.php';
		return new bab_SearchIsCriterion($this, $mixedValue);
	}


	/**
	 * Return an in criterion 
	 * 
	 * @param mixed	$mixedValue...		One or more mixed value or an array of values.
	 * 
	 * @return bab_SearchInCriterion
	 */
	public function in(/* $mixedValue1, $mixedValue2, ... */)
	{
		$aArgList = func_get_args();
		$iNumArgs = func_num_args();
		if(0 < $iNumArgs && is_array($aArgList[0]))
		{
			$aArgList = $aArgList[0];		
		}
		require_once dirname(__FILE__).'/searchcriterion.php';
		return new bab_SearchInCriterion($this, $aArgList);
	}





	/**
	 * Return a like criterion
	 * 
	 * @param string	$sValue
	 * 
	 * @return bab_SearchLikeCriterion
	 */
	public function like($sValue)
	{
		assert('is_string($sValue); /* Parameter $sValue must be a string. */');
		return new bab_SearchLikeCriterion($this, $sValue);
	}



	/**
	 * Return an end with criterion
	 * 
	 * @param string	$sValue
	 * 
	 * @return bab_SearchContainCriterion
	 */
	public function contain($sValue)
	{
		assert('is_string($sValue); /* Parameter $sValue must be a string. */');
		return new bab_SearchContainCriterion($this, $sValue);
	}





	/**
	 * Return a start with criterion
	 * 
	 * @param string	$sValue
	 * 
	 * @return bab_SearchStartWithCriterion
	 */
	public function startWith($sValue)
	{
		assert('is_string($sValue); /* Parameter $sValue must be a string. */');
		return new bab_SearchStartWithCriterion($this, $sValue);
	}


	/**
	 * Return an end with criterion
	 * 
	 * @param string	$sValue
	 * 
	 * @return bab_SearchEndWithCriterion
	 */
	public function endWith($sValue)
	{
		assert('is_string($sValue); /* Parameter $sValue must be a string. */');
		return new bab_SearchEndWithCriterion($this, $sValue);
	}
}











////////////////////////////////////////////////////////////////////////////


















abstract class bab_SearchBinaryCriteria extends bab_SearchCriteria
{
	protected $oLeftCriteria	= null;
	protected $oRightCriteria	= null;
	
	public function __construct(bab_SearchCriteria $oLeftCriteria, bab_SearchCriteria $oRightCriteria)
	{
		
		$this->oLeftCriteria	= $oLeftCriteria;
		$this->oRightCriteria	= $oRightCriteria;
	}
}


class bab_SearchInvariant extends bab_SearchCriteria
{
	public function _OR_(bab_SearchCriteria $oCriteria)
	{
		return $oCriteria;
	}

	public function _AND_(bab_SearchCriteria $oCriteria)
	{
		return $oCriteria;
	}
}


class bab_SearchAnd extends bab_SearchBinaryCriteria
{
	public function __construct(bab_SearchCriteria $oLeftCriteria, bab_SearchCriteria $oRightCriteria)
	{
		parent::__construct($oLeftCriteria, $oRightCriteria);
	}

	public function toString(bab_SearchBackEnd $oBackEnd)
	{
		return $oBackEnd->andCriteria($this->oLeftCriteria, $this->oRightCriteria);
	}
}


class bab_SearchOr extends bab_SearchBinaryCriteria
{
	public function __construct(bab_SearchCriteria $oLeftCriteria, bab_SearchCriteria $oRightCriteria)
	{
		parent::__construct($oLeftCriteria, $oRightCriteria);
	}

	public function toString(bab_SearchBackEnd $oBackEnd)
	{
		return $oBackEnd->orCriteria($this->oLeftCriteria, $this->oRightCriteria);
	}
}


class bab_SearchNot extends bab_SearchCriteria
{
	private $oCriteria = null;
	
	public function __construct(bab_SearchCriteria $oCriteria)
	{
		$this->oCriteria = $oCriteria;
	}

	public function toString(bab_SearchBackEnd $oBackEnd)
	{
		return $oBackEnd->notCriteria($this->oCriteria);
	}
}



abstract class bab_SearchCriterionBase extends bab_SearchCriteria
{
	protected $oTestable = null;
	
	public function __construct(bab_SearchTestable $oTestable)
	{
		$this->oTestable = $oTestable;
	}

	/**
	 * Get fields from testable object
	 * @return array
	 */
	public function getFields() 
	{
		if ($this->oTestable instanceOf bab_SearchField) {
			return array($this->oTestable);
		}

		if ($this->oTestable instanceOf bab_SearchRealm) {
			return $this->oTestable->getFields();
		}

		throw new Exception('cannot get fields from testable object');
	}
}

abstract class bab_SearchCriterion extends bab_SearchCriterionBase
{
	protected $mixedValue = null;
	
	public function __construct(bab_SearchTestable $oTestable, $mixedValue)
	{
		parent::__construct($oTestable);
		$this->mixedValue = $mixedValue;
	}

	/**
	 * 
	 * @return string
	 */
	public function toString(bab_SearchBackEnd $oBackEnd) 
	{
		$tool = substr(get_class($this), strlen('bab_Search'), -1 * strlen('Criterion'));
		$fields = $this->getFields();

		if (1 === count($fields)) {
			return $oBackEnd->$tool(reset($fields), $this->mixedValue);
		}

		$crit = new bab_SearchInvariant;
		
		foreach($fields as $field) {
			if ($field->searchable()) {
				$crit = $crit->_OR_($field->$tool($this->mixedValue));
			}
		}

		return $crit->toString($oBackEnd);
	}
}


class bab_SearchGreaterThanCriterion 			extends bab_SearchCriterion{}
class bab_SearchGreaterThanOrEqualCriterion 	extends bab_SearchCriterion{}
class bab_SearchLessThanCriterion 				extends bab_SearchCriterion{}
class bab_SearchLessThanOrEqualCriterion 		extends bab_SearchCriterion{}
class bab_SearchIsCriterion 					extends bab_SearchCriterion{}
class bab_SearchApproxEqualToCriterion 			extends bab_SearchCriterion{}
class bab_SearchNotCriterion 					extends bab_SearchCriterion{}
class bab_SearchInCriterion 					extends bab_SearchCriterion{}
class bab_SearchLikeCriterion 					extends bab_SearchCriterion{}
class bab_SearchContainCriterion 				extends bab_SearchCriterion{}
class bab_SearchStartWithCriterion 				extends bab_SearchCriterion{}
class bab_SearchEndWithCriterion 				extends bab_SearchCriterion{}
