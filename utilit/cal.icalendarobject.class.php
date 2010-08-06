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
		$this->properties[$icalProperty] = $value;
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
			return $this->properties[$icalProperty];
		} else {
			$this->properties[$icalProperty] = '';
			return $this->properties[$icalProperty];
		}
	}
	
	/**
	 * Set the ATTENDEE property
	 * 
	 * @param	string	$cn				Common name
	 * @param	string	$email
	 * @param	string	$role			CHAIR | REQ-PARTICIPANT | NON-PARTICIPANT | OPT-PARTICIPANT
	 * 									To specify the participation role for the calendar user specified by the property.
	 * @param	string	$partstat		NEEDS-ACTION | TENTATIVE | ACCEPTED | DECLINED | DELEGATED
	 * 									To specify the participation status for the calendar user specified by the property
	 * @param	string	$rsvp			TRUE | FALSE					
	 * 									To specify whether there is an expectation of a favor of a reply from the calendar user specified by the property value.
	 * 
	 * @return unknown_type
	 */
	public function addAttendee($email, $cn=null, $role=null, $partstat=null, $rsvp=null) {
		
		if (!isset($this->properties['ATTENDEE']))
		{
			$this->properties['ATTENDEE'] = array();
		}
		
		$attendeekey = "ATTENDEE";
		
		if (null !== $role) 	$attendeekey .= ";ROLE=$role";
		if (null !== $partstat) $attendeekey .= ";PARTSTAT=$partstat";
		if (null !== $cn) 		$attendeekey .= ";CN=$cn";
		if (null !== $rsvp) 	$attendeekey .= ";RSVP=$rsvp";
		
		$attendeekey .= ':MAILTO:'.$email;
		
		$this->properties['ATTENDEE'][$attendeekey] = array(
			'ROLE'		=> $role,
			'PARTSTAT'	=> $partstat,
			'CN'		=> $cn,
			'email' 	=> $email 
		);
	}
	
	/**
	 * Get the list of attendees
	 * keys of array are the iCalendar representations
	 * @return array	<array>
	 */
	public function getAttendees() {
		
		if (!isset($this->properties['ATTENDEE']))
		{
			return array();
		}
		
		return $this->properties['ATTENDEE'];
	}
}
