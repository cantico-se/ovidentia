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
 * Represent an iCalendar object
 */
abstract class bab_ICalendarObject
{
	/**
	 * ICal properties
	 * @var array
	 */
	private $properties = array();
	
	
	/**
	 * Store aditional information for the ATTENDEE property
	 * @var array
	 */
	protected $attendees = array();
	
	/**
	 * Store association beetween attendee property and calendar
	 * @var unknown_type
	 */
	private $attendeesCalendars = array();
	
	
	/**
	 * Store aditional information for the RELATED-TO property
	 * @var array
	 */
	protected $relations = array();
	
	
	/**
	 * List of events to call with the commitEvent method
	 * 
	 * @see bab_ICalendarObject::resetEvent()
	 * @see bab_CalendarPeriod::commitEvent()
	 * 
	 * @var array
	 */
	protected $attendeesEvents = array();
	
	
	/**
	 * List of events to call with the commitEvent method
	 * 
	 * @see bab_ICalendarObject::resetEvent()
	 * @see bab_CalendarPeriod::commitEvent()
	 * 
	 * @var array
	 */
	protected $relationsEvents = array();
	
	
	
	abstract public function getName();
	
	
	/**
	 * Escape a string tu use a parameter in a iCalendar property
	 * @param	string	$str
	 * @return string
	 */
	public function escape($str)
	{
		$str = str_replace(';', ' ', $str);
		$str = str_replace(':', ' ', $str);
		return $str;
	}
	
	
	
	/**
	 * define a property with a icalendar property name
	 * 
	 *
	 * @param	string	$icalProperty
	 * @param	mixed	$value
	 * 
	 * @return bab_ICalendarObject
	 */
	public function setProperty($icalProperty, $value) {
		
		if (null === $value)
		{
			return $this;
		}
		
		$propparam = '';
		
		if (false !== $pos = mb_strpos($icalProperty, ';')) {
			$propparam = $icalProperty;
			$icalProperty = mb_substr($icalProperty, 0, $pos);
		}

		$this->properties[$icalProperty][$propparam] = $value;
		return $this;
	}
	
	

	/**
	 * get a property with a icalendar property name
	 *
	 * @param	string	$icalProperty
	 * @return	mixed
	 */
	public function getProperty($icalProperty) {
		if (isset($this->properties[$icalProperty])) {
			
			if (1 === count($this->properties[$icalProperty]) && isset($this->properties[$icalProperty][''])) {
				return $this->properties[$icalProperty][''];
			} else {
				return $this->properties[$icalProperty];
			}
			
			
		} else {
			return '';
		}
	}
	
	
	public function removeProperty($icalProperty)
	{
		if (isset($this->properties[$icalProperty])) {
			unset($this->properties[$icalProperty]);
		}
		
		return $this;
	}
	
	
	private function attendeeKey($role, $partstat, $cn, $rsvp)
	{
		$attendeekey = "ATTENDEE";
		
		if (null !== $role) 	$attendeekey .= ";ROLE=$role";
		if (null !== $partstat) $attendeekey .= ";PARTSTAT=$partstat";
		if (null !== $cn) 		$attendeekey .= ";CN=".$this->escape($cn);
		if (null !== $rsvp) 	$attendeekey .= ";RSVP=$rsvp";
		
		return $attendeekey;
	}
	
	/**
	 * Set the ATTENDEE property by id_user, usable for non accessible calendars
	 *
	 * @param	int						$id_user		Ovidentia user
	 * @param	string					$role			CHAIR | REQ-PARTICIPANT | NON-PARTICIPANT | OPT-PARTICIPANT
	 * 													To specify the participation role for the calendar user specified by the property.
	 * @param	string					$partstat		NEEDS-ACTION | TENTATIVE | ACCEPTED | DECLINED | DELEGATED
	 * 													To specify the participation status for the calendar user specified by the property
	 * @param	string					$rsvp			TRUE | FALSE
	 * 													To specify whether there is an expectation of a favor of a reply from the calendar user specified by the property value.
	 *
	 * @return unknown_type
	 */
	public function addAttendeeByUserId($id_user, $role=null, $partstat=null, $rsvp=null)
	{
		if (!isset($this->properties['ATTENDEE']))
		{
			$this->properties['ATTENDEE'] = array();
		}
		
		$cn = bab_getUserName($id_user);
		$email = bab_getUserEmail($id_user);
		
		if (empty($email))
		{
			bab_debug('Attendee ignored for iduser='.$id_user);
			
			require_once dirname(__FILE__).'/devtools.php';
			bab_debug_print_backtrace();
			return;
		}
		
		if (empty($cn))
		{
			$cn = null;
		}
		
		$attendeekey = $this->attendeeKey($role, $partstat, $cn, $rsvp);
		$this->properties['ATTENDEE'][$attendeekey] = 'MAILTO:'.$email;
	}
	
	
	/**
	 * Set the ATTENDEE property
	 * 
	 * @param	bab_PersonalCalendar	$calendar		Personnal calendar of attendee
	 * @param	string					$role			CHAIR | REQ-PARTICIPANT | NON-PARTICIPANT | OPT-PARTICIPANT
	 * 													To specify the participation role for the calendar user specified by the property.
	 * @param	string					$partstat		NEEDS-ACTION | TENTATIVE | ACCEPTED | DECLINED | DELEGATED
	 * 													To specify the participation status for the calendar user specified by the property
	 * @param	string					$rsvp			TRUE | FALSE					
	 * 													To specify whether there is an expectation of a favor of a reply from the calendar user specified by the property value.
	 * 
	 * @return unknown_type
	 */
	public function addAttendee(bab_PersonalCalendar $calendar, $role=null, $partstat=null, $rsvp=null) {
		
		if (!isset($this->properties['ATTENDEE']))
		{
			$this->properties['ATTENDEE'] = array();
		}
		
		$id_user = $calendar->getIdUser();
		
		if (!$id_user)
		{
			throw new Exception('This is not a personnal calendar');
		}
		
		$cn = bab_getUserName($id_user);
		$email = bab_getUserEmail($id_user);
		
		$attendeekey = $this->attendeeKey($role, $partstat, $cn, $rsvp);
		$insertkey = $cn.' '.$email;
		$urlIdentifier = $calendar->getUrlIdentifier();
		
		$this->attendeesCalendars[$insertkey] = $calendar;
		
		
		if (!isset($this->attendees[$urlIdentifier]))
		{
			$attendee = array(
					'ROLE'		=> $role,
					'PARTSTAT'	=> $partstat,
					'CN'		=> $cn,
					'RSVP'		=> $rsvp,
					'email'		=> $email,
					'calendar' 	=> $calendar,
					'key'		=> $insertkey
			);
			
			$AttendeeBackend = new bab_CalAttendeeBackend($attendee, $this);
			$attendee['AttendeeBackend'] = $AttendeeBackend;
			
			$this->attendees[$urlIdentifier] = $attendee;
			
			$this->properties['ATTENDEE'][$attendeekey] = 'MAILTO:'.$email;
			
			
			if (($this instanceof bab_CalendarPeriod)  && !isset($this->attendeesEvents[$urlIdentifier]))
			{
				// do not trigger in case of a VALARM
				$this->attendeesEvents[$urlIdentifier] = 'onAddAttendee';
			}
		}
		else
		{
			if (isset($this->properties['ATTENDEE'][$attendeekey]) && $this->properties['ATTENDEE'][$attendeekey] === 'MAILTO:'.$email)
			{
				// nothing changed
				return;
			}
			
			$old = $this->attendees[$urlIdentifier];
			$oldattendeekey = $this->attendeeKey($old['ROLE'], $old['PARTSTAT'], $old['CN'], $old['RSVP']);
			
			unset($this->properties['ATTENDEE'][$oldattendeekey]);
			
			$attendee = array(
				'ROLE'		=> $role,
				'PARTSTAT'	=> $partstat,
				'CN'		=> $cn,
				'RSVP'		=> $rsvp,
				'email'		=> $email,
				'calendar' 	=> $calendar
			);
			
			$AttendeeBackend = new bab_CalAttendeeBackend($attendee, $this);
			$attendee['AttendeeBackend'] = $AttendeeBackend;
			
			$this->attendees[$urlIdentifier] = $attendee;
			
			$this->properties['ATTENDEE'][$attendeekey] = 'MAILTO:'.$email;
			
			if (($this instanceof bab_CalendarPeriod) && !isset($this->attendeesEvents[$urlIdentifier]))
			{
				$this->attendeesEvents[$urlIdentifier] = 'onUpdateAttendee';
			}
		}
	}
	
	/**
	 * Get the list of attendees with a user in ovidentia database
	 * keys of array are the iCalendar representations
	 * @return array	<array>
	 */
	public function getAttendees() {
		
		if (!isset($this->attendees))
		{
			return array();
		}
		
		return $this->attendees;
	}
	
	
	/**
	 * Get all attendees, ovidentia attendees and the unreconized attendees defined by property only
	 * with at least the email
	 * @return array
	 */
	public function getAllAttendees() {
		$return = array();
		$attendees = $this->getProperty('ATTENDEE');
		
		if (empty($attendees))
		{
			return array();
		}
		
		foreach($attendees as $params => $value)
		{
			$parameters = explode(';', $params);
			array_shift($parameters);

			$role = null;
			$partstat = null;
			$cn = null;
			$rsvp = null;
			$email = null;
			$calendar = null;
			
			foreach ($parameters as $parameter) {
				list($paramName, $paramValue) = explode('=', $parameter);
				switch ($paramName) {
					case 'ROLE':
						$role = $paramValue;
						break;
					case 'PARTSTAT':
						$partstat = $paramValue;
						break;
					case 'RSVP':
						$rsvp = $paramValue;
						break;
					case 'CN':
						$cn = $paramValue;
						break;
				}
			}
			
			if (mb_strpos(strtoupper($value), 'MAILTO:') !== false) {
				list(, $email) = explode(':', $value);
			}
			
			$key = $cn.' '.$email;
			
			if (isset($this->attendeesCalendars[$key]))
			{
				$calendar = $this->attendeesCalendars[$key];
			}
			
			if ($email)
			{
				$attendee = array(
					'ROLE'				=> $role,
					'PARTSTAT'			=> $partstat,
					'CN'				=> $cn,
					'RSVP'				=> $rsvp,
					'email'				=> $email,
					'calendar'			=> $calendar
				);
				
				$AttendeeBackend = new bab_CalAttendeeBackend($attendee, $this);
				$attendee['AttendeeBackend'] = $AttendeeBackend;
				
				$return[] = $attendee;
			}
		}
		
		
		
		return $return;
	}
	
	
	
	/**
	 * Remove attendees from object
	 * @return bab_ICalendarObject
	 */
	public function removeAttendees() {
		unset($this->properties['ATTENDEE']);
		$this->attendees = array();
		$this->attendeesCalendars = array();
		return $this;
	}
	
	
	/**
	 * Reset the list of attendees modifications
	 * @return bab_ICalendarObject
	 */
	public function resetEvent()
	{
		$this->attendeesEvents = array();
		return $this;
	}
	
	
	
	
	
	/**
	 * Add a RELATED-TO iCalendar property on VEVENT
	 * or update workflow status
	 * 
	 * @param	string				$reltype		PARENT | CHILD | SIBLING
	 * @param	bab_EventCalendar	$calendar
	 * @param	string				$status			X-CTO-STATUS		acceptation status for related ovidentia calendar	NEEDS-ACTION | ACCEPTED | DECLINED
	 * @param	int					$wfInstance		X-CTO-WFINSTANCE	workflow sheme instance for the related calendar
	 * 
	 * @return bab_CalendarPeriod
	 */
	public function addRelation($reltype, bab_EventCalendar $calendar, $status = null, $wfInstance = null) 
	{
		// only one parent
		
		if (!isset($this->relations[$reltype]) || 'PARENT' === $reltype)
		{
			$this->relations[$reltype] = array();
		}
		
		if (!isset($this->properties['RELATED-TO']))
		{
			$this->properties['RELATED-TO'] = array();
		}
		
		$urlIdentifier = $calendar->getUrlIdentifier();
		$value = "RELATED-TO;RELTYPE=$reltype";
		if (null !== $status)
		{
			$value .=";X-CTO-STATUS=".$status;
		}
		
		if (null !== $wfInstance)
		{
			$value .=";X-CTO-WFINSTANCE=".$wfInstance;
		}
		
		$value .= ":".$calendar->getReference()->__toString();
		
		$this->removeRelatedToByCalendar($calendar);
		
		
		if (isset($this->relations[$reltype][$urlIdentifier]))
		{
			// update
			$method = 'onUpdateRelation';
			
		} else {
			// new relation
			$method = 'onAddRelation';
		}
		
		
		$this->relations[$reltype][$urlIdentifier] = array(
			'reltype'			=> $reltype,
			'calendar' 			=> $calendar,
			'X-CTO-STATUS'		=> $status,
			'X-CTO-WFINSTANCE'	=> $wfInstance
		);

		
		$this->properties['RELATED-TO'][] = $value;
		
		if (($this instanceof bab_CalendarPeriod)  && !isset($this->relationsEvents[$urlIdentifier]))
		{
			// do not trigger in case of a VALARM
			$this->relationsEvents[$urlIdentifier] = $method;
		}
		
		return $this;
	}
	
	/**
	 * Get RELATED-TO iCalendar property on VEVENT
	 * 
	 * @param string	$reltype		PARENT | CHILD | SIBLING
	 * @return array	<array>
	 */
	public function getRelations($reltype = null) 
	{
		if (null === $reltype && isset($this->relations))
		{
			$relations = array();
			foreach($this->relations as $arr)
			{
				if (isset($arr))
				{
					$relations = array_merge($relations, $arr);
				}
			}
			
			return $relations;
		}
		
		
		if (!isset($this->relations[$reltype]))
		{
			return array();
		}
		
		return $this->relations[$reltype];
	}
	
	/**
	 * Remove all relations
	 * @return unknown_type
	 */
	public function removeRelations()
	{
		foreach($this->relations as $arr)
		{
			if (isset($arr['X-CTO-WFINSTANCE']) && $arr['X-CTO-WFINSTANCE'])	
			{
				throw new Exception(sprintf('The relation with calendar %s could not be removed because the is a workflow instance', $arr['calendar']->getName()));
				return;
			}
		}
		
		
		$this->relations = null;
		unset($this->properties['RELATED-TO']);
		
		return $this;
	}
	
	/**
	 * Remove one relation
	 * @param bab_EventCalendar $calendar
	 * @return unknown_type
	 */
	public function removeRelation(bab_EventCalendar $calendar)
	{
		$id = $calendar->getUrlIdentifier();
		
		if (isset($this->relations['PARENT'][$id]))
		{
			unset($this->relations['PARENT'][$id]);
		} 
		
		if (isset($this->relations['CHILD'][$id]))
		{
			unset($this->relations['CHILD'][$id]);
		}
		
		$this->removeRelatedToByCalendar($calendar);
		
		return $this;
	}
	
	
	
	/**
	 * Search the RELTYPE parameter for a RELATED-TO property
	 * @param bab_EventCalendar $calendar
	 * @return string | null
	 */
	public function getRelationType(bab_EventCalendar $calendar)
	{
		foreach($this->relations as $reltype => $arr)
		{
			foreach($arr as $id => $relation)
			{
				if ($id === $calendar->getUrlIdentifier())
				{
					return $reltype;
				}
			}
		}
		
		return null;
	}
	
	
	
	
	/**
	 * Remove the relation property
	 * @param bab_EventCalendar $calendar
	 * @return unknown_type
	 */
	private function removeRelatedToByCalendar(bab_EventCalendar $calendar)
	{
		if (isset($this->properties['RELATED-TO']))
		{
			foreach($this->properties['RELATED-TO'] as $key => $property)
			{
				$pos = mb_strpos($property, ':');
				$value = mb_substr($property, 1+$pos);
				
				if ($value === $calendar->getReference()->__toString())
				{
					unset($this->properties['RELATED-TO'][$key]);
				} 
			}
		}
	}
	
	
	
	/**
	 * Get all calendars stored as attendees and relations
	 * keys are calendar url indentifier
	 * @return array	<bab_EventCalendar>
	 */
	public function getCalendars()
	{
		$return = array();
		
		if (isset($this->attendees))
		{
			foreach($this->attendees as $arr)
			{
				$calendar = $arr['calendar'];
				if (isset($calendar))
				{
					$return[$calendar->getUrlIdentifier()] = $calendar;
				}
			}
		}
		
		if (isset($this->relations))
		{
			foreach($this->relations as $reltype => $arr)
			{
				foreach($arr as $relation)
				{
					$calendar = $relation['calendar'];
					if (isset($calendar))
					{
						$return[$calendar->getUrlIdentifier()] = $calendar;
					}
				}
			}
		}
		
		return $return;
	}
	
	
	
	/**
	 * Get all properties
	 * @return array
	 */
	public function getProperties()
	{
		$return = array();
		foreach($this->properties as $property => $dummy)
		{
			$value = $this->getProperty($property);
			
			if (is_array($value))
			{
				foreach($value as $k => $v) {
					if (is_numeric($k)) {
						$return[] = $v;
					} else {
						$return[] = $k.':'.$v;
					}
				}
			} else {
				$return[] = $property.':'.$value;
			}
		}
		
		return $return;
	}
	
	/**
	 * Get workflow instance of a calendar or null if no workflow instance running (confirmed event on the calendar return null)
	 * @param bab_EventCalendar $calendar
	 * @return int | null
	 */
	public function getWfInstance(bab_EventCalendar $calendar)
	{
		if (null === $calendar->getApprobationSheme())
		{
			return null;
		}
		
		$relations = $this->getRelations();
		$identifier = $calendar->getUrlIdentifier();
		if (isset($relations[$identifier]) && !empty($relations[$identifier]['X-CTO-WFINSTANCE']))
		{
			return (int) $relations[$identifier]['X-CTO-WFINSTANCE'];
		}
		
		return null;
	}
	
	
	/**
	 * Parse iCalendar property
	 * @param string $property
	 * @return array
	 */
	public function parseProperty($property)
	{
		
		$o = new bab_ICalendarProperty;
	
	
		if (preg_match('/^([^:^;]+)/', $property, $m))
		{
			$o->name = $m[1];
		}
		
		
		$property = substr($property, strlen($o->name));
		
	
		if (preg_match('/^;(.+)$/', $property, $m))
		{
			$o->parameters = preg_split('/\s*;\s*/', $m[1]);
	
			foreach($o->parameters as $key => $p)
			{
				if (preg_match('/^([^=]+)=(?:([^"][^:]+)|(?:"([^"]+)"))/', $p, $m))
				{
					$pname = $m[1];
					if (isset($m[3]))
					{
						$pvalue = $m[3];
					} else {
						$pvalue = $m[2];
					}
	
					$o->value = substr($p, (1 + strlen($m[0])));
	
					$o->parameters[$key] = array('name' => $pname, 'value' => $pvalue);
				}
			}
	
		} else {
	
			$o->value = substr($property, 1);
		}
	
		return $o;
	}
}




/**
 * Object used to fetch informations from attendee backend
 */
class bab_CalAttendeeBackend
{
	/**
	 * 
	 * @var Array
	 */
	private $attendee;
	
	/**
	 * @var Array
	 */
	private $real_attendee = null;
	
	
	/**
	 * event or alarm
	 * @var bab_ICalendarObject
	 */
	private $period;

	
	
	public function __construct(Array $attendee, bab_ICalendarObject $period)
	{
		$this->attendee = $attendee;
		$this->period = $period;
	}
	
	/**
	 * 
	 */
	private function getRealAttendee()
	{
		if (null === $this->real_attendee)
		{
			if (!isset($this->attendee['calendar']))
			{				
				$this->real_attendee = false;
				return false;
			}
			
			$sourceCollection = $this->period->getCollection();
			
			if (null == $sourceCollection)
			{
				bab_debug(sprintf('Collection not found for event %s (%s), use attendee instead of real attendee', $this->period->getProperty('UID'), $this->period->getProperty('SUMMARY')));
				$this->real_attendee = $this->attendee;
				return $this->real_attendee; 
			}
			
			$sourceCalendar = $sourceCollection->getCalendar();
			
			if (isset($sourceCalendar) && $sourceCalendar->getUrlIdentifier() === $this->attendee['calendar']->getUrlIdentifier())
			{
				$this->real_attendee = $this->attendee;
				return $this->real_attendee;
			}
			
			$backend = $this->attendee['calendar']->getBackend();
			$collection = $backend->CalendarEventCollection($this->attendee['calendar']);
			
			$copy = $backend->getPeriod($collection, $this->period->getProperty('UID'), $this->period->getProperty('DTSTART'));
			
			if (null === $copy)
			{
				// not found, use the partstat from the main event
				$this->real_attendee = false;
				return false;
			}
			
			
			foreach($copy->getAllAttendees() as $arr_copy)
			{
				if ($arr_copy['email'] === $this->attendee['email'] && $arr_copy['CN'] === $this->attendee['CN'])
				{
					$this->real_attendee = $arr_copy;
					break;
				}
			}
		}
		
		return $this->real_attendee;
	}
	
	/**
	 * Query partstat in attendee backend
	 * @return string
	 */
	public function getRealPartstat()
	{
		$real_attendee = $this->getRealAttendee();
			
		if (false === $real_attendee)
		{
			return $this->attendee['PARTSTAT'];
		}
		
		return $real_attendee['PARTSTAT'];
	}
	
	
	
	/**
	 * Test if the calendar is readable
	 * @return bool
	 */
	public function canView()
	{
		if (!isset($this->attendee['calendar']))
		{
			return false;
		}
	
		$calendar = $this->attendee['calendar'];
		/*@var $calendar bab_EventCalendar */
	
	
		// test if calendar is in user list
	
		$cals = bab_getICalendars();
		if (null === $cals->getEventCalendar($calendar->getUrlIdentifier()))
		{
			return false;
		}
	
	
		if (!$calendar->canView())
		{
			return false;
		}
	
		return true;
	}
	
}





class bab_ICalendarProperty 
{
	/**
	 * 
	 * @var string
	 */
	public $name;
	
	/**
	 * 
	 * @var string
	 */
	public $value;
	
	/**
	 * 
	 * @var array
	 */
	public $parameters = array();	
}