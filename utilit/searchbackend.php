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


class bab_SearchNotImplementedException extends Exception {

}


abstract class bab_SearchBackEnd {

	abstract public function andCriteria(bab_SearchCriteria $oLeftCriteria, bab_SearchCriteria $oRightCriteria);

	abstract public function orCriteria(bab_SearchCriteria $oLeftCriteria, bab_SearchCriteria $oRightCriteria);

	abstract public function notCriteria(bab_SearchCriteria $oCriteria);

	abstract public function contain(bab_SearchField $oField, $sValue);

	
	public function greaterThan(bab_SearchField $oField, $sValue)
	{
		$sError = 'The function ' . __FUNCTION__ . ' is not implemented';
		throw new bab_SearchNotImplementedException($sError);	
	}

	
	public function greaterThanOrEqual(bab_SearchField $oField, $sValue)
	{
		$sError = 'The function ' . __FUNCTION__ . ' is not implemented';
		throw new bab_SearchNotImplementedException($sError);	
	}

	
	public function lessThan(bab_SearchField $oField, $sValue)
	{
		$sError = 'The function ' . __FUNCTION__ . ' is not implemented';
		throw new bab_SearchNotImplementedException($sError);	
	}

	
	public function lessThanOrEqual(bab_SearchField $oField, $sValue)
	{
		$sError = 'The function ' . __FUNCTION__ . ' is not implemented';
		throw new bab_SearchNotImplementedException($sError);	
	}

	
	public function in(bab_SearchField $oField, $mixedValue)
	{
		$sError = 'The function ' . __FUNCTION__ . ' is not implemented';
		throw new bab_SearchNotImplementedException($sError);	
	}


	public function is(bab_SearchField $oField, $mixedValue)
	{
		$sError = 'The function ' . __FUNCTION__ . ' is not implemented';
		throw new bab_SearchNotImplementedException($sError);	
	}


	public function approxEqualTo(bab_SearchField $oField, $mixedValue)
	{
		$sError = 'The function ' . __FUNCTION__ . ' is not implemented';
		throw new bab_SearchNotImplementedException($sError);	
	}

	
	public function like(bab_SearchField $oField, $sValue)
	{
		$sError = 'The function ' . __FUNCTION__ . ' is not implemented';
		throw new bab_SearchNotImplementedException($sError);	
	}

	
	public function startWith(bab_SearchField $oField, $sValue)
	{
		$sError = 'The function ' . __FUNCTION__ . ' is not implemented';
		throw new bab_SearchNotImplementedException($sError);	
	}

	
	public function endWith(bab_SearchField $oField, $sValue)
	{
		$sError = 'The function ' . __FUNCTION__ . ' is not implemented';
		throw new bab_SearchNotImplementedException($sError);	
	}
}