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
 * Ovidentia calendar
 */
abstract class bab_EventCalendar
{
	/**
	 * Calendar UID unique for one reference type
	 * @see bab_EventCalendar::getReferenceType()
	 * @var string
	 */
	protected $uid = null;
	

	/**
	 * Name of calendar
	 * @var string
	 */
	protected $name;
	
	/**
	 * Description of calendar
	 * @var string
	 */
	protected $description;
	
	
	/**
	 * Displayable type for calendar (internationalized)
	 * @var string
	 */
	protected $type;
	
	
	
	/**
	 * ovidentia id user to test access for
	 * @var int
	 */
	protected $access_user = null;
	
	
	
	/**
	 * Optional id user linked to calendar to add the working hours periods
	 * @var int
	 */
	protected $id_user = null;
	
	
	/**
	 * Optional id of workflow sheme
	 */
	protected $idsa = null;
	
	
	/**
	 * Delegation
	 */
	protected $id_dgowner = 0;
	
	
	/**
	 * Return the unique reference of calendar throw all addons
	 * @return bab_Reference
	 */
	public function getReference() 
	{
		return bab_buildReference('calendar', $this->getReferenceType(), $this->uid);
	}
	
	/**
	 * Get Url identifier of calendar, the type and uid part of the reference
	 * the string is unique in all calendar application
	 * @return string
	 */
	public function getUrlIdentifier()
	{
		$type = $this->getReferenceType();
		
		if (empty($this->uid)) {
			throw new Exception('the unique identifier of the calendar is missing');
		}
		
		return "$type/$this->uid";
	}
	
	
	/**
	 * get calendar unique identifier for one reference type
	 * exemple : id from database
	 * @return string
	 */
	public function getUid()
	{
		return $this->uid;
	}
	
	
	/**
	 * Get the type part of the reference
	 * @return unknown_type
	 */
	abstract function getReferenceType();
	
	/**
	 * get name of calendar
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}
	
	
	/**
	 * get description of calendar
	 * @return string
	 */
	public function getDescription()
	{
		return $this->description;
	}
	
	
	/**
	 * get description of calendar
	 * @return string
	 */
	abstract public function getType();
	
	/**
	 * Get Id user linked to calendar, only for personal calendars
	 * calendars with id_user, will can load additional periods collection with the working periods, non-working periods and vacation periods
	 * 
	 * @return int
	 */
	public function getIdUser()
	{
		return $this->id_user;
	}
	
	
	/**
	 * Get approbation sheme ID
	 * @return int
	 */
	public function getApprobationSheme()
	{
		return $this->idsa;
	}
	
	
	
	/**
	 * Get delegation of calendar
	 * @return int
	 */
	public function getDgOwner()
	{
		return $this->id_dgowner;
	}
	
	
	/**
	 * if a calendar return true on this method it will be displayed first, as the default personal calendar for the user
	 * @return bool
	 */
	public function isDefaultCalendar()
	{
		return false;
	}
	
	

	/**
	 * Test if an event can be added on a calendar
	 * @return bool
	 */
	public function canAddEvent() {
		return false;
	}
	
	
	/**
	 * Test if an event can be updated
	 * @param bab_calendarPeriod $event
	 * @return bool
	 */
	public function canUpdateEvent(bab_calendarPeriod $event) {
		return false;
	}
	
	
	/**
	 * Test if an event can be deleted
	 * @param bab_calendarPeriod $event
	 * @return bool
	 */
	public function canDeleteEvent(bab_calendarPeriod $event) {
		return false;
	}
	
	
	/**
	 * Test if this calendar is allowed for an avaiability period search
	 * @return bool
	 */
	public function canSearchAvailabilityPeriod() {
		return true;
	}
	
	/**
	 * Test if the access user can view event details other than the begin and end dates
	 * the event details can be protected by the private property of period
	 * @param bab_calendarPeriod $event
	 * @return bool
	 */
	public function canViewEventDetails(bab_calendarPeriod $event) {
		
		if ($this->access_user == $event->getAuthorId()) {
			return true;
		}
		
		if ('PUBLIC' !== $event->getProperty('CLASS')) {
			
			// Can be PRIVATE or CONFIDENTIAL
			return false;
		}
		
		return true;
	}
	
	
	
	/**
	 * Get default attendee PARTSTAT property value for new attendee associated to an event of this calendar
	 * The calendar as given parameter must return an interger value with the method getIdUser
	 * the return value will be one of the following values from the iCalendar spec :
	 * <ul>
	 * 	<li>NEEDS-ACTION : the event will appear on the attendee calendar and request validation from him (default value)</li>
	 *  <li>ACCEPTED : the event will appear on the attendee calendar</li>
	 *  <li>DECLINED : the event will not appear on the attendee calendar</li>
	 *  <li>TENTATIVE : not supported by ovidentia user interface</li>
	 *  <li>DELEGATED : not supported by ovidentia user interface</li>
	 * </ul>
	 * 
	 * @link http://www.kanzaki.com/docs/ical/partstat.html
	 * 
	 * @see bab_EventCalendar::getIdUser()
	 * 
	 * @param	bab_EventCalendar	$calendar			A calendar with an user associated to it
	 * 
	 * @return 	string
	 */
	public function getDefaultAttendeePARTSTAT(bab_EventCalendar $calendar)
	{
		return 'NEEDS-ACTION';
	}
	
	


	/**
	 * Get backend to use for this calendar
	 * 
	 * @return Func_CalendarBackend
	 */
	abstract public function getBackend();
}






/**
 * Calendars stored in the core database tables of ovidentia
 */
abstract class bab_OviEventCalendar extends bab_EventCalendar 
{
	
	/**
	 * Initilization from database informations
	 * 
	 * @param	int		$access_user	id of user to test access for
	 * @param	Array	$data			calendar infos from table
	 */
	public function init($access_user, Array $data)
	{
		$this->access_user 	= $access_user;
		
		$this->uid		 	= $data['idcal'];
		$this->name 		= $data['name'];
		$this->description 	= $data['description'];	
		
		if (isset($data['idsa'])) {
			$this->idsa		= $data['idsa'];
		}
		
		if (isset($data['id_dgowner'])) {
			$this->id_dgowner = $data['id_dgowner'];
		}
	}

	
	
	/**
	 * Get backend to use for this calendar
	 * 
	 * @return Func_CalendarBackend_Ovi
	 */
	public function getBackend()
	{
		return bab_functionality::get('CalendarBackend/Ovi');
	}
	
}


/**
 * Personal calendar
 */
class bab_PersonalCalendar extends bab_OviEventCalendar 
{
	/**
	 * Access level for calendar sharing of the access_user
	 * 
	 * BAB_CAL_ACCESS_NONE
	 * BAB_CAL_ACCESS_VIEW
	 * BAB_CAL_ACCESS_UPDATE
	 * BAB_CAL_ACCESS_FULL
	 * BAB_CAL_ACCESS_SHARED_UPDATE
	 *  
	 * @var int
	 */
	private $sharing_access = BAB_CAL_ACCESS_NONE;
	
	/**
	 * @param	int		$access_user	id of user to test access for
	 * @param	Array	$data			calendar infos from table
	 */
	public function init($access_user, Array $data)
	{
		parent::init($access_user, $data);
		$this->id_user 			= $data['idowner'];
		$this->sharing_access	= $data['access'];
	}
	
	public function getType() 
	{
		return bab_translate('Personal calendar');
	}
	
	/**
	 * Get the type part of the reference
	 * @return unknown_type
	 */
	public function getReferenceType()
	{
		return 'personal';
	}
	
	
	/**
	 * Test if an event can be added on a calendar
	 * @return bool
	 */
	public function canAddEvent() 
	{
		switch($this->sharing_access) {
			case BAB_CAL_ACCESS_SHARED_UPDATE:
			case BAB_CAL_ACCESS_UPDATE:
			case BAB_CAL_ACCESS_FULL:
				return true;
		}
		
		return false;
	}
	
	
	/**
	 * Test if the event has been created by a member of the same "shared access" group
	 * @param bab_calendarPeriod $event
	 * @return bool
	 */
	private function isSharedAccess(bab_calendarPeriod $event)
	{
		if ($this->sharing_access != BAB_CAL_ACCESS_SHARED_UPDATE)
		{
			return false;
		}

		// shared access on calendar for access user

		$author = $event->getAuthorId();
		
		if (!isset($author))
		{
			return false;
		}
		
		
		if ($this->access_user == $author) 
		{
			// i am the author
			return true;
		}
		
		global $babDB;
		
		$res = $babDB->db_query('
			SELECT * FROM '.BAB_CALACCESS_USERS_TBL.' 
			WHERE 
				id_cal='.$babDB->quote($this->getUid()).' 
				AND id_user='.$babDB->quote($author).'
				AND bwrite='.$babDB->quote(BAB_CAL_ACCESS_SHARED_UPDATE).' 
			');
		
		
		if (0 !== $babDB->db_num_rows($res))
		{
			// shared access on calendar for author
			return true;
		}
		
		return false;
	}
	
	
	/**
	 * Test if an event can be updated
	 * @param bab_calendarPeriod $event
	 * @return bool
	 */
	public function canUpdateEvent(bab_calendarPeriod $event) 
	{
		
		if ($this->access_user == $event->getAuthorId()) {
			// i am the author
			return true;
		}
		
		if ($event->isLocked()) {
			return false;
		}
		
		
		switch($this->sharing_access) {
			
			case BAB_CAL_ACCESS_UPDATE:
				return ($this->access_user == $event->getAuthorId());
				
			case BAB_CAL_ACCESS_SHARED_UPDATE:
				return $this->isSharedAccess($event);
				
			case BAB_CAL_ACCESS_FULL:
				return true;
				
			
		}
		
		return false;
	}
	
	
	/**
	 * Test if an event can be deleted
	 * @param bab_calendarPeriod $event
	 * @return bool
	 */
	public function canDeleteEvent(bab_calendarPeriod $event) 
	{
		return $this->canUpdateEvent($event);
	}
	
	
	
	/**
	 * Get default attendee PARTSTAT property value for new attendee associated to an event of this calendar
	 * The calendar as given parameter must return an interger value with the method getIdUser
	 * the return value will be one of the following values from the iCalendar spec :
	 * <ul>
	 * 	<li>NEEDS-ACTION : the event will appear on the attendee calendar and request validation from him (default value)</li>
	 *  <li>ACCEPTED : the event will appear on the attendee calendar</li>
	 * </ul>
	 * if the user is the attendee or if the user have full access, the attendee is considered accepted
	 * 
	 * @link http://www.kanzaki.com/docs/ical/partstat.html
	 * 
	 * @see bab_EventCalendar::getIdUser()
	 * 
	 * @param	bab_EventCalendar	$calendar			A calendar with an user associated to it
	 * 
	 * @return 	string
	 */
	public function getDefaultAttendeePARTSTAT(bab_EventCalendar $calendar)
	{
		if ($this->access_user == $calendar->getIdUser())
		{
			// I add myself as attendee on an event
			return 'ACCEPTED';
		}
		
		switch($this->sharing_access) {

			case BAB_CAL_ACCESS_FULL:
				// i have full access on the calendar where the event is
				return 'ACCEPTED';
		}
		
		return 'NEEDS-ACTION';
	}
	
	
	
	

}


/**
 * Public calendar
 */
class bab_PublicCalendar extends bab_OviEventCalendar 
{
	public function getType() 
	{
		return bab_translate('Public calendar');
	}
	
	/**
	 * Get the type part of the refernce
	 * @return unknown_type
	 */
	public function getReferenceType()
	{
		return 'public';
	}
	
	
	/**
	 * Test if an event can be added on a calendar
	 * @return bool
	 */
	public function canAddEvent() {
		return bab_isAccessValid(BAB_CAL_PUB_GRP_GROUPS_TBL, $this->uid, $this->access_user) 
			|| bab_isAccessValid(BAB_CAL_PUB_MAN_GROUPS_TBL, $this->uid, $this->access_user);
	}
	
	
	/**
	 * Test if an event can be updated
	 * @param bab_calendarPeriod $event
	 * @return bool
	 */
	public function canUpdateEvent(bab_calendarPeriod $event) {
		
		if ($this->access_user == $event->getAuthorId()) {
			return true;
		}
		
		return bab_isAccessValid(BAB_CAL_PUB_MAN_GROUPS_TBL, $this->uid, $this->access_user);
	}
	
	
	/**
	 * Test if an event can be deleted
	 * @param bab_calendarPeriod $event
	 * @return bool
	 */
	public function canDeleteEvent(bab_calendarPeriod $event) {
		
		if ($this->access_user == $event->getAuthorId()) {
			return true;
		}
		
		return bab_isAccessValid(BAB_CAL_PUB_MAN_GROUPS_TBL, $this->uid, $this->access_user);
	}
	
	
	

}


/**
 * Ressource calendar
 */
class bab_RessourceCalendar extends bab_OviEventCalendar 
{
	public function getType() 
	{
		return bab_translate('Ressource calendar');
	}

	/**
	 * Get the type part of the refernce
	 * @return unknown_type
	 */
	public function getReferenceType()
	{
		return 'ressource';
	}
	
	
	/**
	 * Test if an event can be added on a calendar
	 * @return bool
	 */
	public function canAddEvent() {
		return bab_isAccessValid(BAB_CAL_RES_ADD_GROUPS_TBL, $this->uid, $this->access_user)
			|| bab_isAccessValid(BAB_CAL_RES_MAN_GROUPS_TBL, $this->uid, $this->access_user);
	}
	
	
	/**
	 * Test if an event can be updated
	 * @param bab_calendarPeriod $event
	 * @return bool
	 */
	public function canUpdateEvent(bab_calendarPeriod $event) {
		
		if ($this->access_user == $event->getAuthorId()) {
			return true;
		}
		
		return bab_isAccessValid(BAB_CAL_RES_MAN_GROUPS_TBL, $this->uid, $this->access_user)
			|| bab_isAccessValid(BAB_CAL_RES_UPD_GROUPS_TBL, $this->uid, $this->access_user);
	}
	
	
	/**
	 * Test if an event can be deleted
	 * @param bab_calendarPeriod $event
	 * @return bool
	 */
	public function canDeleteEvent(bab_calendarPeriod $event) {
		
		if ($this->access_user == $event->getAuthorId()) {
			return true;
		}
		
		return bab_isAccessValid(BAB_CAL_RES_MAN_GROUPS_TBL, $this->uid, $this->access_user);
	}
	
	
}