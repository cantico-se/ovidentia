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
 * Swish-e backend
 */
class bab_SearchSwishBackEnd extends bab_SearchBackEnd
{


	/**
	 * Escape a string for swish-e
	 * @param	string	$value
	 * @return string
	 */
	private function quote($value) {
		$value = preg_replace_callback(
			"/\s(OR|NOT|AND|or|not|and)\s/", 
			create_function('$v','return \' "\'.$v[1].\'" \';'), 
			$value
		);

		$value = trim($value);

		if (preg_match('/\s+/', $value)) {
			$value = '"'.$value.'"';
		}

		return $value;
	}


	private function binaryCriteria(bab_SearchCriteria $oLeftCriteria, $sOperator, bab_SearchCriteria $oRightCriteria)
	{
		return '(' . $oLeftCriteria->toString($this) . ' ' . $sOperator . ' ' . $oRightCriteria->toString($this) . ") " ;
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
	
	public function in(bab_SearchField $oField, $mixedValue)
	{
		if (empty($mixedValue)) {
			return '';
		}

		if (!is_array($mixedValue)) {
			return $this->contain($oField, $mixedValue);
		}

		foreach($mixedValue as $k => $value) {
			$mixedValue[$k] = $this->contain($oField, $value);
		}


		return '('.implode(' OR ', $mixedValue).')';
	}

	
	public function contain(bab_SearchField $oField, $sValue)
	{
		return $this->quote($sValue);
	}

}