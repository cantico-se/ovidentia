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


class BAB_Field
{
	var $sName = null;
	var $sAlias = null;
	
	function BAB_Field($sName, $sAlias = null)
	{
		$this->sName = $sName;
		$this->sAlias = $sAlias;
	}
}

class BAB_IntField extends BAB_Field
{
	function BAB_IntField($sName, $sAlias = null)
	{
		parent::BAB_Field($sName, $sAlias);
	}
}

class BAB_StringField extends BAB_Field
{
	function BAB_IntField($sName, $sAlias = null)
	{
		parent::BAB_Field($sName, $sAlias);
	}
}


class BAB_SimpleCriterion
{
	var $sSqlOperator = null;
	var $sName = null;
	var $sValue = null;
	
	function BAB_SimpleCriterion($sName, $sSqlOperator, $sValue)
	{
		$this->sSqlOperator = $sSqlOperator;
		$this->sName = $sName;
		$this->sValue = $sValue;
	}
	
	function toString()
	{
		global $babDB;
		return $this->sName . ' ' . $this->sSqlOperator . ' ' . $babDB->quote($this->sValue);
	}
}


class BAB_InCriterion
{
	var $sName = null;
	var $aValue = null;
	
	function BAB_InCriterion($sName, $aValue)
	{
		$this->sName = $sName;
		$this->aValue = $aValue;
	}
	
	function toString()
	{
		global $babDB;
		return sprintf('%s IN(%s)', $this->sName, $babDB->quote($this->aValue));
	}
}


class BAB_NotInCriterion
{
	var $sName = null;
	var $aValue = null;
	
	function BAB_NotInCriterion($sName, $aValue)
	{
		$this->sName = $sName;
		$this->aValue = $aValue;
	}
	
	function toString()
	{
		global $babDB;
		return sprintf('%s NOT IN(%s)', $this->sName, $babDB->quote($this->aValue));
	}
}


class BAB_LikeCriterionBase
{
	var $sName = null;
	var $sValue = null;
	
	//mode
	// 0 ==> sValue
	// 1 ==> sValue%
	// 2 ==> %sValue
	// 3 ==> %sValue%
	var $iMode = null;
	
	var $sLike = null;
	
	function BAB_LikeCriterionBase($sName, $sValue, $iMode)
	{
		$this->sName = $sName;
		$this->sValue = $sValue;
		$this->iMode = $iMode;
	}
	
	function toString()
	{
		global $babDB;
		switch($this->iMode)
		{
			// 0 ==> XXX
			case 0:
				return $this->sName . ' ' . $this->sLike . ' \'' .  $babDB->db_escape_like($this->sValue) . '\' ';
			// 1 ==> XXX%
			case 1:
				return $this->sName . ' ' . $this->sLike . ' \'' .  $babDB->db_escape_like($this->sValue) . '%\' ';
			// 2 ==> %XXX
			case 2:
				return $this->sName . ' ' . $this->sLike . ' \'%' .  $babDB->db_escape_like($this->sValue) . '\' ';
			// 3 ==> %XXX%
			case 3:
				return $this->sName . ' ' . $this->sLike . ' \'%' .  $babDB->db_escape_like($this->sValue) . '%\' ';
		}
		return '';
	}
}


class BAB_LikeCriterion extends BAB_LikeCriterionBase
{
	function BAB_LikeCriterion($sName, $sValue, $iMode)
	{
		parent::BAB_LikeCriterionBase($sName, $sValue, $iMode);
		$this->sLike = 'LIKE';
	}
}

class BAB_NotLikeCriterion extends BAB_LikeCriterionBase
{
	function BAB_NotLikeCriterion($sName, $sValue, $iMode)
	{
		parent::BAB_LikeCriterionBase($sName, $sValue, $iMode);
		$this->sLike = 'NOT LIKE';
	}
}


class BinaryCriterion
{
	var $aCriterion = array();
	
	function BinaryCriterion()
	{
		
	}
}

class BAB_And extends BinaryCriterion
{
	var $sOperator = 'AND';
	
	function BAB_And()
	{
		
	}

	function _and()
	{
		$aArgList = func_get_args();
		if(is_array($aArgList))
		{
			$iCount = count($aArgList);
			if($iCount > 1)
			{
				$this->aCriterion[] = $aArgList;
			}
			else if($iCount === 1)
			{
				$this->aCriterion[] = $aArgList[0];
			}
		}
		return $this;
	}
	
	function processCriterion($aCriterion)
	{
		$sString = '';
		if(count($aCriterion) > 0)
		{
			$aCrit = array();
			foreach($aCriterion as $value)
			{
				if(is_array($value))
				{
					if(count($value) > 0)
					{
						$aCrit[] = '(' . $this->processCriterion($value) . ')';
					}
				}
				else 
				{
					$aCrit[] = $value->toString();
				}
			}
			$sString = implode(' ' . $this->sOperator . ' ', $aCrit);
		}
		return $sString;
	}
	
	function toString()
	{
		return $this->processCriterion($this->aCriterion);
	}
}

class BAB_Criteria
{
	var $aColumnMap = array();
	var $aCriterion = array();

	function BAB_Criteria(&$aColumnMap, &$aCriterion)
	{
		$this->aColumnMap =& $aColumnMap;
		$this->aCriterion =& $aCriterion;
	}
	
	function processCriterion()
	{
		$aWhereClause = array();
		
		foreach($this->aCriterion as $oCriterion)
		{
			if(array_key_exists($oCriterion->sName, $this->aColumnMap))
			{
				$oCriterion->sName = $this->aColumnMap[$oCriterion->sName][0];
			}
			
			$aWhereClause[] = $oCriterion->toString();
		}
		
		$sWhereClause = '';
		if(count($aWhereClause) > 0)
		{
			$sWhereClause = ' WHERE ' . implode(' AND ', $aWhereClause);
		}
		
		return $sWhereClause;
	}
}
?>