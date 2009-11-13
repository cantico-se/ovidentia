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
require_once $GLOBALS['babInstallPath'] . 'utilit/iterator/iterator.php';


class BAB_Criteria
{
	function BAB_Criteria()
	{
		
	}
	
	function _or()
	{
//		$iFuncArgCount = func_num_args();
		$aArgList = func_get_args();
		return $this->createCriteria('BAB_Or', $aArgList[0]);
	}
	
	function _and()
	{
//		$iFuncArgCount = func_num_args();
		$aArgList = func_get_args();
		return $this->createCriteria('BAB_And', $aArgList[0]);
	}
	
	function createCriteria($sClassName, $oRightCriteria)
	{
		$oCriteria = new $sClassName();
		$oCriteria->set($this, $oRightCriteria);
		return $oCriteria;
	}
	
	function toString()
	{
		return '';
	}
}


class BAB_BinaryCriteria extends BAB_Criteria
{
	var $oLeftCriteria = null;
	var $oRightCriteria = null;
	var $sOperator = null;
	
	function BAB_BinaryCriteria($sOperator)
	{
		parent::BAB_Criteria();
		$this->sOperator = $sOperator;
	}
	
	function set($oLeftCriteria, $oRightCriteria)
	{
		$this->oLeftCriteria = $oLeftCriteria;
		$this->oRightCriteria = $oRightCriteria;
	}
	
	function toString()
	{
		return '(' . $this->oLeftCriteria->toString() . ' ' . $this->sOperator . ' ' . $this->oRightCriteria->toString() . ')';
	}
}


class BAB_And extends BAB_BinaryCriteria
{
	function BAB_And()
	{
		parent::BAB_BinaryCriteria('AND');
	}
}


class BAB_Or extends BAB_BinaryCriteria
{
	function BAB_Or()
	{
		parent::BAB_BinaryCriteria('OR');
	}
}



class BAB_Criterion extends BAB_Criteria
{
	var $oField = null;
	
	function BAB_Criterion($oField)
	{
		parent::BAB_Criteria();
		$this->oField = $oField;
	}
	
	function in()
	{
		$aArgList = func_get_args();
		return new BAB_InCriterion($aArgList[0], $aArgList[1]);
	}
	
	function notIn()
	{
		$aArgList = func_get_args();
		return new BAB_NotInCriterion($aArgList[0], $aArgList[1]);
	}
	
	function like()
	{
		$aArgList = func_get_args();
		return new BAB_LikeCriterion($aArgList[0], $aArgList[1]);
	}
	
	function contain()
	{
		$aArgList = func_get_args();
		return new BAB_ContainCriterion($aArgList[0], $aArgList[1]);
	}
	
	function regExp()
	{
		$aArgList = func_get_args();
		return new BAB_FilteredCriterion($aArgList[0], $aArgList[1]);
	}
}


class BAB_InCriterion extends BAB_Criterion
{
	var $aValue = null;
	
	function BAB_InCriterion($oField, $aValue)
	{
		parent::BAB_Criterion($oField);
		
		$this->aValue = $aValue;
	}
	
	function toString()
	{
		global $babDB;
		return sprintf('%s IN(%s)', $this->oField->getName(), $babDB->quote($this->aValue));
	}
}

class BAB_NotInCriterion extends BAB_Criterion
{
	var $aValue = null;
	
	function BAB_NotInCriterion($oField, $aValue)
	{
		parent::BAB_Criterion($oField);
		
		$this->aValue = $aValue;
	}
	
	function toString()
	{
		global $babDB;
		return sprintf('%s NOT IN(%s)', $this->oField->getName(), $babDB->quote($this->aValue));
	}
}


class BAB_LikeCriterionBase extends BAB_Criterion
{
	var $sValue = null;
	var $sLike = null;
	
	function BAB_LikeCriterionBase($oField, $sValue)
	{
		parent::BAB_Criterion($oField);
		$this->sValue = $sValue;
	}
	
	/**
	 * In this method, the value is not encoded
	 * the file manager use this tu rename folders, wildcards may be set in the value
	 * the value need a db_escape_string before use for like and not like criterion
	 */ 
	function toString()
	{
		return $this->oField->getName() . ' ' . $this->sLike . ' \'' .  $this->sValue . '\' ';
	}
}


class BAB_LikeCriterion extends BAB_LikeCriterionBase
{
	function BAB_LikeCriterion($oField, $sValue)
	{
		parent::BAB_LikeCriterionBase($oField, $sValue);
		$this->sLike = 'LIKE';
	}
}

class BAB_NotLikeCriterion extends BAB_LikeCriterionBase
{
	function BAB_NotLikeCriterion($oField, $sValue)
	{
		parent::BAB_LikeCriterionBase($oField, $sValue);
		$this->sLike = 'NOT LIKE';
	}
}

class BAB_ContainCriterion extends BAB_LikeCriterionBase
{
	function BAB_ContainCriterion($oField, $sValue)
	{
		parent::BAB_Criterion($oField, $sValue);
		$this->sLike = 'LIKE';
	}
	
	function toString()
	{
		global $babDB;
		return $this->oField->getName() . ' ' . $this->sLike . ' \'%' .  $babDB->db_escape_like($this->sValue) . '%\' ';
	}
}

class BAB_FilteredCriterion extends BAB_LikeCriterionBase
{
	function BAB_ContainCriterion($oField, $sRegExp)
	{
		parent::BAB_LikeCriterionBase($oField, $sRegExp);
	}
	
	function toString()
	{
		global $babDB;
		return $this->oField->getName() . ' REGEXP ' .  $babDB->quote($this->sValue);
	}
}


class BAB_Field
{
	var $sName = null;
	
	function BAB_Field($sName)
	{
		$this->sName = $sName;
	}
	
	function getName()
	{
		return $this->sName;
	}
	
	function in()
	{
		$aArgList = func_get_args();
		return call_user_func_array(array('BAB_Criterion', 'in'), array($this, $aArgList[0]));
	}
	
	function notIn()
	{
		$aArgList = func_get_args();
		return call_user_func_array(array('BAB_NotInCriterion', 'notIn'), array($this, $aArgList[0]));
	}
	
	function like()
	{
		$aArgList = func_get_args();
		return call_user_func_array(array('BAB_LikeCriterion', 'like'), array($this, $aArgList[0]));
	}
	
	function contain()
	{
		$aArgList = func_get_args();
		return call_user_func_array(array('BAB_ContainCriterion', 'contain'), array($this, $aArgList[0]));
	}
	
	function notLike()
	{
		$aArgList = func_get_args();
		return call_user_func_array(array('BAB_NotLikeCriterion', 'notLike'), array($this, $aArgList[0]));
	}
	
	function regExp()
	{
		$aArgList = func_get_args();
		return call_user_func_array(array('BAB_FilteredCriterion', 'regExp'), array($this, $aArgList[0]));
	}
}

class BAB_IntField extends BAB_Field
{
	function BAB_IntField($sName)
	{
		parent::BAB_Field($sName);
	}
}

class BAB_StringField extends BAB_Field
{
	function BAB_StringField($sName)
	{
		parent::BAB_Field($sName);
	}
}
?>
