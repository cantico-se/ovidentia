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
	private $attendees = array();
	
	/**
	 * Store association beetween attendee property and calendar
	 * @var unknown_type
	 */
	private $attendeesCalendars = array();
	
	
	/**
	 * Store aditional information for the RELATED-TO property
	 * @var array
	 */
	private $relations = array();
	
	
	/**
	 * List of events to call with the commitAttendeeEvent method
	 * 
	 * @see bab_ICalendarObject::resetAttendeeEvent()
	 * @see bab_ICalendarObject::commitAttendeeEvent()
	 * 
	 * @var array
	 */
	private $attendeesEvents = array();
	
	
	
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
	 * the value is not compliant with the icalendar format
	 * Dates are defined as ISO datetime
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
			
			
			$this->attendees[$urlIdentifier] = array(
				'ROLE'		=> $role,
				'PARTSTAT'	=> $partstat,
				'CN'		=> $cn,
				'RSVP'		=> $rsvp,
				'email'		=> $email,
				'calendar' 	=> $calendar,
				'key'		=> $insertkey
			);
			
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
			
			
			$this->attendees[$urlIdentifier] = array(
				'ROLE'		=> $role,
				'PARTSTAT'	=> $partstat,
				'CN'		=> $cn,
				'RSVP'		=> $rsvp,
				'email'		=> $email,
				'calendar' 	=> $calendar
			);
			
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
	 * @return array
	 */
	public function getAllAttendees() {
		$return = array();
		$attendees = (array) $this->getProperty('ATTENDEE');
		
		
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
			
			
			$return[] = array(
				'ROLE'		=> $role,
				'PARTSTAT'	=> $partstat,
				'CN'		=> $cn,
				'RSVP'		=> $rsvp,
				'email'		=> $email,
				'calendar'	=> $calendar
			);
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
	public function resetAttendeeEvent()
	{
		$this->attendeesEvents = array();
		return $this;
	}
	
	/**
	 * call each modified or added attendees 
	 * if attendee is the parent, to not call events
	 * 
	 * @return bab_ICalendarObject
	 */
	public function commitAttendeeEvent()
	{	
		$parent = reset($this->relations['PARENT']);
		
		foreach($this->attendeesEvents as $urlidentifier => $method)
		{
			if (isset($parent) && $urlidentifier === $parent->getUrlIdentifier())
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
		
		return $this;
	}
	
	
	
	/**
	 * Add a RELATED-TO iCalendar property on VEVENT
	 * 
	 * @param	string				$reltype	PARENT | CHILD | SIBLING
	 * @param	bab_EventCalendar	$calendar
	 * 
	 * @return bab_CalendarPeriod
	 */
	public function addRelation($reltype, bab_EventCalendar $calendar) 
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
		$value = "RELATED-TO;RELTYPE=$reltype:".$calendar->getReference()->__toString();

		if (!isset($this->relations[$reltype][$urlIdentifier]))
		{
			$this->relations[$reltype][$urlIdentifier] = $calendar;
			$this->properties['RELATED-TO'][] = $value;
		} 

		return $this;
	}
	
	/**
	 * Get RELATED-TO iCalendar property on VEVENT
	 * 
	 * @param string	$reltype		PARENT | CHILD | SIBLING
	 * @return array	<bab_EventCalendar>
	 */
	public function getRelations($reltype) 
	{
		if (!isset($this->relations[$reltype]))
		{
			return array();
		}
		
		return $this->relations[$reltype];
	}
	
	
	public function removeRelations()
	{
		$this->relations = null;
		unset($this->properties['RELATED-TO']);
	}
	
	
	
	/**
	 * Get all calendars stored as attendees and relations
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
				$return[$calendar->getUrlIdentifier()] = $calendar;
			}
		}
		
		if (isset($this->relations))
		{
			foreach($this->relations as $reltype => $arr)
			{
				foreach($arr as $calendar)
				{
					$return[$calendar->getUrlIdentifier()] = $calendar;
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
}