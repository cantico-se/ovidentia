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

include_once 'base.php';










/**
 * Main class for all criterions of a calendar request
 */
abstract class bab_PeriodCriteria
{
	
	/**
	 * @var bab_CalendarPeriodCriteria
	 */
	private $subcriteria = array();
	
	
	/**
	 * Join another criteria
	 * @param	bab_PeriodCriteria $criteria	
	 * @return bab_CalendarPeriodCriteria
	 */
	public function _AND_(bab_PeriodCriteria $criteria)
	{
		$this->subcriteria[] = $criteria;
		return $this;
	}
	
	/**
	 * 
	 * @return array
	 */
	public function getCriterions()
	{
		return $this->subcriteria;
	}
	
	/**
	 * Add criteria to userperiod the query object
	 * @param bab_UserPeriods $userperiods
	 * @return unknown_type
	 */
	public function process(bab_UserPeriods $userperiods)
	{
		throw Exception('Not implemented');
	}
}


/**
 * Criteria on calendar
 */
class bab_PeriodCriteriaCalendar extends bab_PeriodCriteria
{
	private $calendar = array();
	
	public function __construct($calendar = null)
	{
		if (null !== $calendar) {
			if (is_array($calendar)) {
				$this->calendar = $calendar;
			} else {
				$this->calendar[] = $calendar;
			}
		}
	}
	
	public function addCalendar(bab_EventCalendar $calendar)
	{
		$this->calendar[] = $calendar;
	}
	
	/**
	 * Add criteria
	 * @param bab_UserPeriods $userperiods
	 * @return unknown_type
	 */
	public function process(bab_UserPeriods $userperiods)
	{
		$userperiods->filterByCalendar($this->calendar);
	}
}


/**
 * Criteria on collection
 * create a filter by type or if the collection contains a hash property, filter by hash
 */
class bab_PeriodCriteriaCollection extends bab_PeriodCriteria
{
	private $collection = array();
	
	public function __construct($collection = null)
	{
		if (null !== $collection) {
			if (is_array($collection)) {
				$this->collection = $collection;
			} else {
				$this->collection[] = $collection;
			}
		}
	}
	
	public function addCollection(bab_PeriodCollection $collection)
	{
		$this->collection[] = $collection;
	}
	
	/**
	 * Add criteria
	 * @param bab_UserPeriods $userperiods
	 * @return unknown_type
	 */
	public function process(bab_UserPeriods $userperiods)
	{
		$userperiods->filterByPeriodCollection($this->collection);
	}
}


/**
 * Criteria on iCal property
 * 
 * @todo Only the CATEGORY property is supported by Ovi backend
 */
class bab_PeriodCritieraProperty extends bab_PeriodCriteria
{
	private $property;
	private $value = array();
	private $contain;
	
	/**
	 * 
	 * @param string $property
	 * @param mixed $value
	 * @return unknown_type
	 */
	public function __construct($property, $value = null, $contain = false)
	{
		if (null !== $value) {
			if (is_array($value)) {
				$this->value = $value;
			} else {
				$this->value[] = $value;
			}
		}
		
		$this->property = $property;
		$this->contain = $contain;
	}
	
	public function addValue($value)
	{
		$this->value[] = $value;
	}
	
	/**
	 * Add criteria
	 * @param bab_UserPeriods $userperiods
	 * @return unknown_type
	 */
	public function process(bab_UserPeriods $userperiods)
	{
		$userperiods->filterByICalProperty($this->property, $this->value, $this->contain);
	}
}











/**
 * Criteria on collection HASH
 * Used to link each others a collection events witch can be updated all at once 
 * 
 * 
 * @see bab_CalendarEventCollection::$hash
 * 
 */
class bab_PeriodCritieraHash extends bab_PeriodCriteria
{
	private $hash;
	
	/**
	 * 
	 * @param string $hash
	 * @return unknown_type
	 */
	public function __construct($hash)
	{
		$this->hash = $hash;
	}

	
	/**
	 * Add criteria
	 * @param bab_UserPeriods $userperiods
	 * @return unknown_type
	 */
	public function process(bab_UserPeriods $userperiods)
	{
		$userperiods->hash = $this->hash;
	}
}




abstract class bab_PeriodCriteriaDate extends bab_PeriodCriteria
{
	/**
	 * 
	 * @var BAB_DateTime
	 */
	protected $date;
	
	
	public function __construct(BAB_DateTime $date)
	{
		$this->date = $date;
	}
}






/**
 * filter by end date
 * 
 */
class bab_PeriodCritieraBeginDateLessThanOrEqual extends bab_PeriodCriteriaDate
{
	public function process(bab_UserPeriods $userperiods)
	{
		$userperiods->end = $this->date;
	}
}


/**
 * filter by begin date
 */
class bab_PeriodCritieraEndDateGreaterThanOrEqual extends bab_PeriodCriteriaDate
{
	public function process(bab_UserPeriods $userperiods)
	{
		$userperiods->begin = $this->date;
	}
}











/**
 * @see Func_CalendarBackend::Criteria()
 */
class bab_PeriodCriteriaFactory 
{
	/**
	 * 
	 * @param Array | bab_EventCalendar $calendar
	 * @return unknown_type
	 */
	public function Calendar($calendar = null)
	{
		return new bab_PeriodCriteriaCalendar($calendar);
	}
	
	
	/**
	 * 
	 * @param array | bab_PeriodCollection $collection			array of collection classname or collections instances | instance or classname of a collection
	 * @return bab_PeriodCriteriaCollection
	 */
	public function Collection($collection = null)
	{
		return new bab_PeriodCriteriaCollection($collection);
	}
	
	/**
	 * 
	 * @param 	string 			$property
	 * @param 	string | array 	$value
	 * @param	bool			$contain		if false, search for exact value, if true search if the property contain the string or array
	 * @return bab_PeriodCritieraProperty
	 */
	public function Property($property, $value, $contain = false)
	{
		return new bab_PeriodCritieraProperty($property, $value, $contain);
	}
	
	/**
	 * Create a hash criteria
	 * @param string $hash
	 * @return bab_PeriodCritieraHash
	 */
	public function Hash($hash)
	{
		return new bab_PeriodCritieraHash($hash);
	}
	
	
	public function Begin(BAB_DateTime $date)
	{
		return new bab_PeriodCritieraEndDateGreaterThanOrEqual($date);
	}
	
	public function End(BAB_DateTime $date)
	{
		return new bab_PeriodCritieraBeginDateLessThanOrEqual($date);
	}
}