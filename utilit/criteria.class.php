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