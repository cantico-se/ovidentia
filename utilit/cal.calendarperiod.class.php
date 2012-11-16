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
	private $data = array();

	
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
	 * If the same event is displayed more than once in the same page, contain the calendar associated for visualisation
	 * @var string
	 */
	private $uiCalendar = null;
	
	
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
		
		$this->setBeginDate($begin);
		$this->setEndDate($end); 
		
		return $this;
	}
	
	/**
	 * @param BAB_DateTime $begin
	 * @param bab_CalendarPeriod
	 */
	public function setBeginDate(BAB_DateTime $begin) {
		
		$this->ts_begin = $begin->getTimeStamp();
		$this->setProperty('DTSTART', $begin->getICal());
		
		return $this;
	}
	
	
	/**
	 * @param BAB_DateTime $end
	 * @param bab_CalendarPeriod
	 */
	public function setEndDate(BAB_DateTime $end) {
	
		$this->ts_end = $end->getTimeStamp();
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
	
	public function setUiCalendar(bab_EventCalendar $calendar)
	{
		$this->uiCalendar = $calendar;
	}
	
	public function getUiCalendar()
	{
		return $this->uiCalendar;
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
	public function setData(Array $data) {
		$this->data = array_merge($this->data, $data);
	}
	
	
	/**
	 * call each modified or added attendees 
	 * if attendee is the parent, do not call events
	 * 
	 * @return bab_ICalendarObject
	 */
	public function commitEvent()
	{	
		$collection = $this->getCollection();
		if (isset($collection))
		{
			$calendar = $collection->getCalendar();
		}
		else
		{
			$calendar = null;
		}

		// commit attendees
		
		foreach($this->attendeesEvents as $urlidentifier => $method)
		{
			if (isset($calendar) && $urlidentifier === $calendar->getUrlIdentifier())
			{
				continue;
			}
			
			if (isset($this->attendees[$urlidentifier]))
			{
				$calendar = $this->attendees[$urlidentifier]['calendar'];
				$calendar->$method($this);
			}
		}
		
		$this->attendeesEvents = array();
		
		// commit relations
		
		foreach($this->relationsEvents as $urlidentifier => $method)
		{
			if (isset($calendar) && $urlidentifier === $calendar->getUrlIdentifier())
			{
				continue;
			}
			
			// test for each RELTYPE : PARENT | CHILD | SIBLING
			
			foreach(array('PARENT', 'CHILD', 'SIBLING') as $reltype)
			{
				if (isset($this->relations[$reltype][$urlidentifier]))
				{
					$calendar = $this->relations[$reltype][$urlidentifier]['calendar'];
					if ($calendar instanceof bab_OviRelationCalendar)
					{
						$calendar->$method($this);
					}
				}
			}
		}
		
		return $this;
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
				//$p->ts_end = $endDate;
				$p->setEndDate(BAB_DateTime::fromTimeStamp($endDate));
				$return->addPeriod($p);
			}
			return $return; // 1 period 
		}

		if ($this->ts_begin < $endDate) {
			$p = clone $this;
			//$p->ts_end = $endDate;
			$p->setEndDate(BAB_DateTime::fromTimeStamp($endDate));
			$return->addPeriod($p);
		}

		while ($start < ($this->ts_end - $duration)) {
			
			$beginDate = $start;
			$this->add($start, $duration);
			$p = clone $this;
			//$p->ts_begin = $beginDate;
			$p->setBeginDate(BAB_DateTime::fromTimeStamp($beginDate));
			//$p->ts_end = $start;
			$p->setEndDate(BAB_DateTime::fromTimeStamp($start));
			$return->addPeriod($p);
		}

		// add last period
		if ($start < $this->ts_end) {
			$p = clone $this;
			//$p->ts_begin = $start;
			$p->setBeginDate(BAB_DateTime::fromTimeStamp($start));
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
	
	
	
	
	
	
	/**
	 * 
	 * @return bool
	 */
	public function save()
	{
		$collection = $this->getCollection();
		$calendar = $collection->getCalendar();
		$backend = $calendar->getBackend();
		return $backend->savePeriod($this);
	}
	
	
	/**
	 * 
	 * @return bool
	 */
	public function delete()
	{
		$collection = $this->getCollection();
		$calendar = $collection->getCalendar();
		$backend = $calendar->getBackend();
		return $backend->deletePeriod($this);
	}

	
	
	
	/**
	 * get HTML string for object
	 * displayable in bab_debug
	 * @return string
	 */
	public function toHtml()
	{
		$html = ''; 
		
		$collection = $this->getCollection();
		if ($collection) 
		{
			$html .= '<p>Collection : '.get_class($collection).'</p>';
			$calendar = $collection->getCalendar();
			
			if ($calendar)
			{
				$html .= sprintf('<p>Calendar : %s, %s</p>', $calendar->getName(), $calendar->getUrlIdentifier());
			}
		}
		
		
		$html .= '<table class="itterable">';
		foreach($this->getProperties() as $property)
		{
			if (preg_match('/(?P<name>[^:^;]+)(?P<parameters>[^:]*):(?P<value>.+)/', $property, $m))
			{
				$paramlist = '';
				
				if (!empty($m['parameters']))
				{
					$paramlist = array();
					$parameters = explode(';', $m['parameters']);
					foreach($parameters as $parameter) {
						if ($parameter)
						{
							list($key, $val) = explode('=', $parameter);
							$paramlist[] = sprintf('<span style="color:green">%s</span>=<span style="color:blue">%s</span>',$key, $val);
						}
					}
					$paramlist = implode(';', $paramlist);
				}
				
				$html .= '<tr>';
				$html .= '<th>'.bab_toHtml($m['name']).'</th>';
				$html .= '<td>'.bab_toHtml($m['value']).'</td>';
				$html .= '<td>'.$paramlist.'</td>';
				$html .= '</tr>';
			}
		}
		$html .= '</table>';
		
		return $html;
	}
	
	
	
	/**
	 * Get organizer informations
	 * @return Array
	 */
	function getOrganizer()
	{
		$organizer = $this->getProperty('ORGANIZER');
		
		if (!$organizer)
		{
			return null;
		}
		
		if (is_array($organizer))
		{
			foreach($organizer as $params => $value)
			{
				
				list(,$email) = explode(':', $value);
				$name = null;
				$arrparams = explode(';', $params);
				array_shift($arrparams);
				
				foreach($arrparams as $param)
				{
					if ($param)
					{
						list($key, $paramvalue) = explode('=', $param);
						if ($key === 'CN') {
							$name = trim($paramvalue, '" ');
							break;
						}
					}
				}
				
				return array(
					'name' => $name,
					'email' => $email
				);
			}
			
		} else {
			
			list(,$email) = explode(':', $organizer);
			
			return array(
				'name' => null,
				'email' => $email
			);
		}
	
		return null;
	}
	
	
	/**
	 * test if a user can view the event with a worflow instance
	 * if event need approbation for the user, this method return true
	 * if event need approbation from another the method return false
	 * if event do not need approbation the method return true
	 * 
	 * @param	int		$id_user
	 * 
	 * @return bool
	 */
	public function WfInstanceAccess($id_user = null)
	{
		if (null === $id_user)
		{
			$id_user = $GLOBALS['BAB_SESS_USERID'];
		}
		
		$relations = $this->getRelations();
		foreach($relations as $relation)
		{
			if ($relation['X-CTO-WFINSTANCE'])
			{
				$user_instances = array();

				if ($id_user)
				{
					require_once dirname(__FILE__).'/wfincl.php';
					$user_instances = bab_WFGetWaitingInstances($id_user);
				}

				if (in_array($relation['X-CTO-WFINSTANCE'], $user_instances))
				{
					// the user is an approbator, he can view event details
					return true;
				}

				// but other users are not allowed if there is an ongoing instance
				return false;
			}
		}
		
		return true;
	}
	
	
	
	/**
	 * cancel the events in all backends
	 * @throws ErrorException backend specific errors
	 * @return bool
	 */
	public function cancelFromAllBackends()
	{
		$result = true;
		
		$currentCollection = $this->getCollection();
		
		$calendars = $this->getCalendars();
		$backends = array();
		foreach($calendars as $calendar)
		{
			$backend = $calendar->getBackend();
			/*@var $backend Func_CalendarBackend */
			
			$urlidentifier = $backend->getUrlIdentifier();
			
			if (!isset($backends[$urlidentifier]))
			{
				$backends[$urlidentifier] = $backend;

				$this->setCollection($backend->CalendarEventCollection($calendar));
				
				if (false === $backend->savePeriod($this, 'CANCEL'))
				{
					$result = false;
				}
			}
		}
		
		$this->setCollection($currentCollection);
		
		return $result;
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