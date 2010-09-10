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
require_once dirname(__FILE__).'/cal.icalendarobject.class.php';

/**
 * Period object
 * Represent a VEVENT iCalendar component
 */
class bab_CalendarPeriod extends bab_ICalendarObject {

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
	 * this parameter is only used by the program, to apply the user preference for a free or busy event period, the period transparency "TRANSP" iCalendar parameter is used
	 * the available public property will not be saved with the event
	 * 
	 * @var bool
	 */
	public	$available;
	
	/**
	 * Non-iCal data
	 * @var mixed
	 */
	private $data;

	
	/**
	 * collection associated to period
	 * @var bab_PeriodCollection
	 */
	private $periodCollection;
	
	
	/**
	 * VALAM associated to VEVENT
	 * @var bab_CalendarAlarm
	 */
	private $alarm;

	
		
	
	
	/**
	 * If the same event is displayed more than once in the same page
	 * @var string
	 */
	private $uiIdentifier = null;
	
	
	
	

	/**
	 * The timestamp in constructor parameters will not initialize the DTSTART and DTEND iCalendar properties
	 * but for periods with no need of iCalendar propoerties, this is more efficient (availability calculation, working hours...)
	 * 
	 * @see bab_CalendarPeriod::setDates()
	 * 
	 * @param 	int						$begin		timestamp
	 * @param 	int						$end		timestamp
	 *
	 */
	public function __construct($begin = 0, $end = 0) {

		$this->ts_begin 	= $begin;
		$this->ts_end		= $end;
	}
	
	/**
	 * 
	 * @return string
	 */
	public function getName()
	{
		return 'VEVENT';
	}
	
	/**
	 * Initialize dates of period, this method will initialize the DTSTART and DTEND properties
	 * 
	 * @param BAB_DateTime $begin
	 * @param BAB_DateTime $end
	 * @return bab_CalendarPeriod
	 */
	public function setDates(BAB_DateTime $begin, BAB_DateTime $end) {
		
		$this->ts_begin = $begin->getTimeStamp();
		$this->ts_end = $end->getTimeStamp();
		
		$this->setProperty('DTSTART', $begin->getICal());
		$this->setProperty('DTEND', $end->getICal());
		
		return $this;
	}
	
	
	/**
	 * Link period to collection
	 * @param bab_PeriodCollection $collection
	 * @return unknown_type
	 */
	public function setCollection(bab_PeriodCollection $collection)
	{
		$this->collection = $collection;
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
	

	
	public function setUiIdentifier($uid)
	{
		$this->uiIdentifier = $uid;
	}
	
	/**
	 * UID to use for UI action (tooltip)
	 * @return unknown_type
	 */
	public function getUiIdentifier()
	{
		if (null !== $this->uiIdentifier)
		{
			return $this->uiIdentifier;
		}	
		
		return $this->getProperty('UID');
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
	 * @return bab_CalendarPeriod
	 */
	public function setAlarm(bab_CalendarAlarm $alarm) {
		$this->alarm = $alarm;
		return $this;
	}
	
	/**
	 * 
	 * @return bab_CalendarAlarm | null
	 */
	public function getAlarm() {
		return $this->alarm;
	}
	
	
	
	
	
	/**
	 * Define a color associated to event, this will be used only if there is no category associated to event
	 * @param string $color
	 * @return bab_calendarPeriod
	 */
	public function setColor($color)
	{
		$this->setProperty('X-CTO-COLOR', $color);
		return $this;
	}
	
	
	/**
	 * 
	 * @return string
	 */
	public function getColor()
	{
		return $this->getProperty('X-CTO-COLOR');
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
	 * work only with timestamps
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
				$p = clone $this;
				$p->ts_end = $endDate;
				$return->addPeriod($p);
			}
			return $return; // 1 period 
		}

		if ($this->ts_begin < $endDate) {
			$p = clone $this;
			$p->ts_end = $endDate;
			$return->addPeriod($p);
		}

		while ($start < ($this->ts_end - $duration)) {
			
			$beginDate = $start;
			$this->add($start, $duration);
			$p = clone $this;
			$p->ts_begin = $beginDate;
			$p->ts_end = $start;
			$return->addPeriod($p);
		}

		// add last period
		if ($start < $this->ts_end) {
			$p = clone $this;
			$p->ts_begin = $start;
			$return->addPeriod($p);
		}

		return $return;
	}
	
	/**
	 * Test the CLASS iCalendar property
	 * @return bool
	 */
	public function isPublic() {
		$class = $this->getProperty('CLASS');
		
		if ('' === $class || 'PUBLIC' === $class)
		{
			return true;
		}
		
		return false;
	}
	
	
	/**
	 * Test if availability of event has been overloaded by program or if the event is transparent
	 * ex : during availability search, the event will be ignored
	 * @return boolean
	 */
	public function isTransparent() {

		if ('TRANSPARENT' === $this->getProperty('TRANSP'))
		{
			return true;
		}
		
	
		if (isset($this->available)) {
			return $this->available;
		}
		
		return false;
	}
	
	/**
	 * Get author of event
	 * @return int	id_user
	 */
	public function getAuthorId()
	{
		$data = $this->getData();
		if (isset($data['id_creator'])) {
			return (int) $data['id_creator'];
		}
		
		return null;
	}
	
	
	/**
	 * the locked attribute remove modification rights for other person than the event author
	 * return true if the locked attribute is set
	 * @return bool
	 */
	public function isLocked()
	{
		$data = $this->getData();
		if (isset($data['block'])) {
			return 'Y' === $data['block'];
		}
		
		return false;
	}

}



/**
 * 
 */
class bab_CalendarAlarm extends bab_ICalendarObject {
	
	public function getName() {
		return 'VALARM';
	}
}