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

include_once dirname(__FILE__).'/searchbackend.php';




/**
 * MySql backend
 */
class bab_SearchMySqlBackEnd extends bab_SearchBackEnd
{
	private $oDataBaseAdapter 	= null;

	public function setDataBaseAdapter($oDataBaseAdapter)
	{
		$this->oDataBaseAdapter = $oDataBaseAdapter;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getWhereClause(bab_SearchCriteria $criteria) {

		$where = $criteria->tostring($this);

		if (!empty($where)) {
			$where = 'WHERE '.$where;
		}

		return $where;
	}




	private function binaryCriteria(bab_SearchCriteria $oLeftCriteria, $sOperator, bab_SearchCriteria $oRightCriteria)
	{
		return '(' . $oLeftCriteria->toString($this) . ' ' . $sOperator . ' ' . $oRightCriteria->toString($this) . ") \n" ;
	}

	public function getFieldAlias(bab_SearchField $oField)
	{

		if ($oField->getTableAlias()) {
			$return = $oField->getTableAlias().'.'.$oField->getRealName();
		} else {
			$return = $oField->getRealName();
		}
		
		return $return;
	}

	public function andCriteria(bab_SearchCriteria $oLeftCriteria, bab_SearchCriteria $oRightCriteria)
	{
		return $this->binaryCriteria($oLeftCriteria, 'AND', $oRightCriteria);
	}

	
	public function orCriteria(bab_SearchCriteria $oLeftCriteria, bab_SearchCriteria $oRightCriteria)
	{
		return $this->binaryCriteria($oLeftCriteria, 'OR', $oRightCriteria);
	}

	
	public function notCriteria(bab_SearchCriteria $oCriteria)
	{
		return 'NOT (' . $oCriteria->toString($this) . ')';
	}

	
	public function greaterThan(bab_SearchField $oField, $sValue)
	{
		return sprintf('%s > %s', $this->getFieldAlias($oField), $this->oDataBaseAdapter->quote($sValue));
	}

	
	public function greaterThanOrEqual(bab_SearchField $oField, $sValue)
	{
		return sprintf('%s >= %s', $this->getFieldAlias($oField), $this->oDataBaseAdapter->quote($sValue));
	}

	
	public function lessThan(bab_SearchField $oField, $sValue)
	{
		return sprintf('%s < %s', $this->getFieldAlias($oField), $this->oDataBaseAdapter->quote($sValue));
	}

	
	public function lessThanOrEqual(bab_SearchField $oField, $sValue)
	{
		return sprintf('%s <= %s', $this->getFieldAlias($oField), $this->oDataBaseAdapter->quote($sValue));	
	}

	
	public function in(bab_SearchField $oField, $mixedValue)
	{
		return sprintf('%s IN(%s)', $this->getFieldAlias($oField), $this->oDataBaseAdapter->quote($mixedValue));
	}


	public function is(bab_SearchField $oField, $mixedValue)
	{
		return sprintf('%s = %s', $this->getFieldAlias($oField), $this->oDataBaseAdapter->quote($mixedValue));
	}

	
	public function like(bab_SearchField $oField, $sValue)
	{
		return $this->getFieldAlias($oField) . ' LIKE \'' .  $this->oDataBaseAdapter->db_escape_like($sValue) . '\' ';
	}
	
	public function contain(bab_SearchField $oField, $sValue)
	{
		return $this->getFieldAlias($oField) . ' LIKE \'%' .  $this->oDataBaseAdapter->db_escape_like($sValue) . '%\' ';
	}


	public function startWith(bab_SearchField $oField, $sValue)
	{
		return $this->getFieldAlias($oField) . ' LIKE \'' .  $this->oDataBaseAdapter->db_escape_like($sValue) . '%\' ';
	}

	
	public function endWith(bab_SearchField $oField, $sValue)
	{
		return $this->getFieldAlias($oField) . ' LIKE \'%' .  $this->oDataBaseAdapter->db_escape_like($sValue) . '\' ';
	}

}