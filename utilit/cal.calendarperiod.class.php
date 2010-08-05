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
 * Period object
 * 
 */
class bab_CalendarPeriod {

	/**
	 * Timestamp begin date
	 * @var int
	 */
	public 	$ts_begin;	

	/**
	 * Timestamp end date
	 * @var int
	 */
	public 	$ts_end;
	
	/**
	 * Can be set manually if an event is "free" to not interfere in availability search
	 * @var bool
	 */
	public	$available;
	
	/**
	 * Non-iCal data
	 * @var mixed
	 */
	private $data;
	
	/**
	 * ICal properties
	 * @var array
	 */
	private $properties;
	

	/**
	 * HTML Color of period
     * color is not defined in ical interface
     * @var string
	 */
	private $color;

	
	/**
	 * collection associated to period
	 * @var bab_PeriodCollection
	 */
	private $periodCollection;
	
	

	/**
	 * @param 	int						$begin		timestamp
	 * @param 	int						$end		timestamp
	 *
	 */
	public function __construct($begin, $end) {

		$this->ts_begin 	= $begin;
		$this->ts_end		= $end;
	}
	
	
	public function setCollection($collection)
	{
		$this->collection 	= $collection;
	}
	
	/**
	 * Get period collection
	 * @return bab_PeriodCollection
	 */
	public function getCollection()
	{
		return $this->collection;
	}
	
	
	/**
	 * Get identifier used in url to identify a period
	 * for modification of a period, the read only event will not need url identifier, so attachment to eventCalendar object is only required for modifiable events
	 * 
	 * @return string
	 */
	public function getUrlIdentifier()
	{
		$uid = $this->getProperty('UID');
		
		if (empty($uid)) {
			throw new Exception('The UID property of period is missing, the url identifier cannot be generated');
		}
		
		return $uid;
	}
	

	
	/**
	 * define a property with a icalendar property name
	 * the value is not compliant with the icalendar format
	 * Dates are defined as ISO datetime
	 *
	 * @param	string	$icalProperty
	 * @param	mixed	$value
	 */
	public function setProperty($icalProperty, $value) {
		$this->properties[$icalProperty] = $value;
	}

	/**
	 * get a property with a icalendar property name
	 *
	 * @param	string	$icalProperty
	 * @return	mixed
	 */
	public function getProperty($icalProperty) {
		if (isset($this->properties[$icalProperty])) {
			return $this->properties[$icalProperty];
		} else {
			$this->properties[$icalProperty] = '';
			return $this->properties[$icalProperty];
		}
	}


	/**
	 * @return mixed
	 */
	public function getData() {
		return $this->data;
	}

	/**
	 * @param mixed $data
	 */
	public function setData($data) {
		$this->data = $data;
	}
	
	/**
	 * 
	 * @param string $color
	 * @return bab_calendarPeriod
	 */
	public function setColor($color)
	{
		$this->color = $color;
		return $this;
	}
	
	
	/**
	 * 
	 * @return string
	 */
	public function getColor()
	{
		return $this->color;
	}
	
	

	/**
	 * get duration beetwen the two dates
	 * @return int (seconds)
	 */
	public function getDuration() {
		return ($this->ts_end - $this->ts_begin);
	}
	
	
	
	
	
	/**
	 * Add duration to timestamp
	 * @param	int		&$timestamp
	 * @param	int		$duration		seconds
	 */
	private function add(&$timestamp, $duration) {
		$timestamp = mktime(
			date('G',$timestamp),
			(int) date('i',$timestamp), 
			($duration + ((int) date('s',$timestamp))), 
			date('n',$timestamp), 
			date('d',$timestamp), 
			date('Y',$timestamp)
			);
	}


	/**
	 * Split period into sub-periods
	 * sub-period are generated from 00:00:00 the first day of the main period
	 * only sub-period overlapped with the main period are returned
	 * 
	 * @param int		$duration   	Duration of subperiods in seconds
	 * 
	 * @return bab_PeriodCollection		same class instance of the main period collection
	 */
	public function split($duration) {

		
		$classname = get_class($this->collection);
		$return = new $classname;
		
		// first day
		$start = bab_mktime(date('Y-m-d',$this->ts_begin));


		// ignore periods before begin date
		while ($start < ($this->ts_begin - $duration)) {
			$this->add($start, $duration);
		}

		// first period
		$this->add($start, $duration);

		if ($start < $this->ts_end) {
			$endDate = $start;
		} else {
			$endDate = $this->ts_end;
			if ($this->ts_begin < $endDate) {
				$p = new bab_calendarPeriod($this->ts_begin, $endDate);
				$p->properties = $this->properties;
				$p->setData($this->data);
				$p->setColor($this->color);
				$return->addPeriod($p);
			}
			return $return; // 1 period 
		}

		if ($this->ts_begin < $endDate) {
			$p = new bab_calendarPeriod($this->ts_begin, $endDate);
			$p->properties = $this->properties;
			$p->setData($this->data);
			$p->setColor($this->color);
			$return->addPeriod($p);
		}

		while ($start < ($this->ts_end - $duration)) {
			
			$beginDate = $start;
			$this->add($start, $duration);
			$p = new bab_calendarPeriod($beginDate, $start);
			$p->properties = $this->properties;
			$p->setData($this->data);
			$p->setColor($this->color);
			$return->addPeriod($p);
		}

		// add last period
		if ($start < $this->ts_end) {
			$p = new bab_calendarPeriod($start, $this->ts_end);
			$p->properties = $this->properties;
			$p->setData($this->data);
			$p->setColor($this->color);
			$return->addPeriod($p);
		}

		return $return;
	}
	
	
	/**
	 * @return boolean
	 */
	public function isAvailable() {

	
		if (isset($this->available)) {
			return $this->available;
		}
		
		return false;
	}
	
	/**
	 * Get author of event
	 * @return int
	 */
	public function getAuthorId()
	{
		$data = $this->getData();
		if (isset($data['id_user'])) {
			return (int) $data['id_user'];
		}
		
		return null;
	}

}

