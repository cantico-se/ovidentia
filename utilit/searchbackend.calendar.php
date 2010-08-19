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
require_once dirname(__FILE__).'/dateTime.php';
require_once dirname(__FILE__).'/cal.userperiods.class.php';
require_once dirname(__FILE__).'/cal.criteria.class.php';




/**
 * Calendar backend
 * search calendar events throw the ovidentia calendar API
 */
class bab_SearchCalendarBackEnd extends bab_SearchBackEnd
{
	private $factory = null;

	/**
	 * (non-PHPdoc)
	 * @see utilit/bab_SearchBackEnd#andCriteria($oLeftCriteria, $oRightCriteria)
	 * 
	 * 
	 * @return bab_PeriodCriteria
	 */
	public function andCriteria(bab_SearchCriteria $oLeftCriteria, bab_SearchCriteria $oRightCriteria)
	{
		$left = $oLeftCriteria->toString($this);
		
		if (!($left instanceof bab_PeriodCriteria))
		{
			throw new Exception('transformation failed');
		}
		
		$right = $oRightCriteria->toString($this);
		
		if (!($right instanceof bab_PeriodCriteria))
		{
			throw new Exception('transformation failed');
		}
		
		return $left->_AND_($right);
	}

	
	public function orCriteria(bab_SearchCriteria $oLeftCriteria, bab_SearchCriteria $oRightCriteria)
	{
		return $this->andCriteria($oLeftCriteria, $oRightCriteria);
	}

	
	public function notCriteria(bab_SearchCriteria $oCriteria)
	{
		throw new bab_SearchNotImplementedException(__FUNCTION__.' Not implemented');
	}
	
	/**
	 * 
	 * @return bab_PeriodCriteriaFactory
	 */
	private function Factory()
	{
		if (null === $this->factory)
		{
			require_once dirname(__FILE__).'/cal.criteria.class.php';
			$this->factory = new bab_PeriodCriteriaFactory;
		}
		
		return $this->factory; 
	}
	
	
	/**
	 * (non-PHPdoc)
	 * @see utilit/bab_SearchBackEnd#in($oField, $mixedValue)
	 * 
	 * 
	 * @return bab_PeriodCriteria | null
	 */
	public function in(bab_SearchField $oField, $mixedValue)
	{
		if (empty($mixedValue)) {
			return null;
		}

		switch($oField->getName())
		{
			case 'calendar':
				return $this->inCalendar($mixedValue);
				break;
				
			case 'collection':
				return $this->Factory()->Collection($mixedValue);
				break;
		};
		
		
		return null;
	}
	
	
	public function is(bab_SearchField $oField, $mixedValue)
	{
		return $this->in($oField, array($mixedValue));
	}
	
	
	private function inCalendar(Array $mixedValue)
	{
		$values = array();
		foreach($mixedValue as $urlidentifier)
		{
			$calendar = bab_getICalendars()->getEventCalendar($urlidentifier);
			if ($calendar)
			{
				$values[] = $calendar;
			}
		}
		
		return $this->Factory()->Calendar($values);
	}

	
	public function contain(bab_SearchField $oField, $sValue)
	{
		return $this->Factory()->Property(strtoupper($oField->getName()), $sValue, true);
	}

	
	public function getEventsIterator(bab_SearchCriteria $searchcriteria)
	{
		$calendarCriteria = $searchcriteria->tostring($this);
		$periods = new bab_UserPeriods;
		$periods->createPeriods($calendarCriteria);
		$periods->orderBoundaries();
		
		return $periods;
	}
	
	
	
	
	public function greaterThanOrEqual(bab_SearchField $oField, $sValue)
	{
		if ('end_date' == $oField->getName())
		{
			return $this->Factory()->Begin(BAB_DateTime::fromIsoDateTime($sValue));
		}
		
		parent::greaterThanOrEqual($oField, $sValue);
	}
	
	
	public function lessThanOrEqual(bab_SearchField $oField, $sValue)
	{
		if ('start_date' == $oField->getName())
		{
			return $this->Factory()->End(BAB_DateTime::fromIsoDateTime($sValue));
		}
		
		parent::lessThanOrEqual($oField, $sValue);
	}
}