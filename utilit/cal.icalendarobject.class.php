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
		
		$attendeekey = "ATTENDEE";
		
		if (null !== $role) 	$attendeekey .= ";ROLE=$role";
		if (null !== $partstat) $attendeekey .= ";PARTSTAT=$partstat";
		if (null !== $cn) 		$attendeekey .= ";CN=$cn";
		if (null !== $rsvp) 	$attendeekey .= ";RSVP=$rsvp";
		
		$attendeekey .= ':MAILTO:'.$email;
		
		$urlIdentifier = $calendar->getUrlIdentifier();
		
		if (!isset($this->attendees[$urlIdentifier]))
		{
			$pos = count($this->properties['ATTENDEE']);
			
			$this->attendees[$urlIdentifier] = array(
				'ROLE'		=> $role,
				'PARTSTAT'	=> $partstat,
				'CN'		=> $cn,
				'RSVP'		=> $rsvp,
				'email'		=> $email,
				'calendar' 	=> $calendar,
				'pos'		=> $pos
			);
			
			$this->properties['ATTENDEE'][$pos] = $attendeekey;
			
			if (($this instanceof bab_CalendarPeriod)  && !isset($this->attendeesEvents[$urlIdentifier]))
			{
				// do not trigger in case of a VALARM
				$this->attendeesEvents[$urlIdentifier] = 'onAddAttendee';
			}
		}
		else
		{
			$pos = $this->attendees[$urlIdentifier]['pos'];
			
			if ($this->properties['ATTENDEE'][$pos] === $attendeekey)
			{
				// nothing changed
				return;
			}
			
			
			$this->attendees[$urlIdentifier] = array(
				'ROLE'		=> $role,
				'PARTSTAT'	=> $partstat,
				'CN'		=> $cn,
				'RSVP'		=> $rsvp,
				'email'		=> $email,
				'calendar' 	=> $calendar,
				'pos'		=> $pos
			);
			
			$this->properties['ATTENDEE'][$pos] = $attendeekey;
			
			if (($this instanceof bab_CalendarPeriod) && !isset($this->attendeesEvents[$urlIdentifier]))
			{
				$this->attendeesEvents[$urlIdentifier] = 'onUpdateAttendee';
			}
		}
	}
	
	/**
	 * Get the list of attendees
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
	
	
	
	public function removeAttendees() {
		unset($this->properties['ATTENDEE']);
		return $this->attendees = null;
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
