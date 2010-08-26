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
	 * In most case, this is the currently logged user BAB_SESS_USERID
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
		
		if (!$event->isPublic()) {
			
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
	 * @return 	string
	 */
	public function getDefaultAttendeePARTSTAT()
	{
		return 'NEEDS-ACTION';
	}
	
	
	/**
	 * Test if the access user can update this calendar PARTSTAT property on event
	 * with this default method, all users can modifiy their own PARTSTAT on every events, with one of the two values allowed by the ovidentia user interface
	 * 
	 * @param 	bab_CalendarPeriod 	$period
	 * @param	string				$role			Attendee ROLE value				CHAIR | REQ-PARTICIPANT | OPT-PARTICIPANT
	 * @param	string				$old_value		Attendee PARTSTAT actual value
	 * @param	string				$new_value		Attendee PARTSTAT new value
	 * 
	 * @return bool
	 */
	public function canUpdateAttendeePARTSTAT(bab_CalendarPeriod $period, $role, $old_value, $new_value)
	{
		$id_user = $this->getIdUser();
		
		if (!$id_user)
		{
			return false;
		}
		
		if ($this->access_user != $id_user)
		{
			return false;
		}
		
		if ('ACCEPTED' !== $new_value && 'DECLINED' !== $new_value)
		{
			return false;
		}

		return true;
	}
	


	/**
	 * Get backend to use for this calendar
	 * 
	 * @return Func_CalendarBackend
	 */
	abstract public function getBackend();
	
	
	/**
	 * Grant access of user to this calendar
	 * 
	 * @param int $accessType		BAB_CAL_ACCESS_VIEW | BAB_CAL_ACCESS_UPDATE | BAB_CAL_ACCESS_FULL | BAB_CAL_ACCESS_SHARED_UPDATE
	 * @param int $user				id user
	 * @return unknown_type
	 */
	public function grantAccess($accessType, $user)
	{
		global $babDB;
		
		$res = $babDB->db_query('
			SELECT 
				id_user 
			FROM '.BAB_CALACCESS_USERS_TBL.' 
			WHERE 
				bwrite='.$babDB->quote($accessType).' 
				AND caltype='.$babDB->quote($this->getReferenceType()).' 
				AND id_cal='.$babDB->quote($this->getUid()).'
				AND id_user='.$babDB->quote($user)
		);
		
		if (0 !== $babDB->db_num_rows($res))
		{
			// access allready granted
			return;
		}
		
		$babDB->db_query('INSERT INTO '.BAB_CALACCESS_USERS_TBL.' 
			(caltype, id_cal, bwrite, id_user) 
		VALUES 
			('.$babDB->quote($this->getReferenceType()).', 
			'.$babDB->quote($this->getUid()).', 
			'.$babDB->quote($accessType).', 
			'.$babDB->quote($user).')
		');
	}
	
	
	/**
	 * Revoke access of user to this calendar
	 * 
	 * @param int 			$accessType		BAB_CAL_ACCESS_VIEW | BAB_CAL_ACCESS_UPDATE | BAB_CAL_ACCESS_FULL | BAB_CAL_ACCESS_SHARED_UPDATE
	 * @param array | int 	$user			id user	or a list of id user
	 * @return unknown_type
	 */
	public function revokeAccess($accessType, $user)
	{
		global $babDB;
		
		$sQuery = 'DELETE FROM ' . BAB_CALACCESS_USERS_TBL . ' WHERE 
			caltype='.$babDB->quote($this->getReferenceType()).'
			AND id_cal = ' . $babDB->quote($this->getUid()) . ' 
			AND bwrite = ' . $babDB->quote($accessType).' 
			AND id_user IN('.$babDB->quote($user).')';
		
		$babDB->db_query($sQuery);
	}
	
	
	/**
	 * Get the list of user with access granted for the specified access type
	 * 
	 * @param int $accessType		BAB_CAL_ACCESS_VIEW | BAB_CAL_ACCESS_UPDATE | BAB_CAL_ACCESS_FULL | BAB_CAL_ACCESS_SHARED_UPDATE	
	 * @return array				<int> id users
	 */
	public function getAccessGrantedUsers($accessType)
	{
		global $babDB;
		
		$res = $babDB->db_query('
			SELECT 
				id_user 
			FROM '.BAB_CALACCESS_USERS_TBL.' 
			WHERE 
				bwrite='.$babDB->quote($accessType).' 
				AND caltype='.$babDB->quote($this->getReferenceType()).' 
				AND id_cal='.$babDB->quote($this->getUid())
		);
		
		$return = array();
		while ($arr = $babDB->db_fetch_assoc($res))
		{
			$id_user = (int) $arr['id_user'];
			$return[$id_user] = $id_user;
		}
		
		return $return;
	}
	
	
	
	/**
	 * Get the sharring access given to access user by the owner of a calendar
	 * @param bab_PersonalCalendar $calendar
	 * @return int		BAB_CAL_ACCESS_VIEW | BAB_CAL_ACCESS_UPDATE | BAB_CAL_ACCESS_FULL | BAB_CAL_ACCESS_SHARED_UPDATE | BAB_CAL_ACCESS_NONE
	 */
	protected function getSharingAccessForCalendar(bab_PersonalCalendar $calendar)
	{
		global $babDB;
		
		$res = $babDB->db_query('
			SELECT 
				bwrite 
			FROM '.BAB_CALACCESS_USERS_TBL.' 
			WHERE 
				AND caltype='.$babDB->quote($calendar->getReferenceType()).' 
				AND id_cal='.$babDB->quote($calendar->getUid()).'
				AND id_user='.$babDB->quote($this->access_user)
		);
		
		
		if ($arr = $babDB->db_fetch_assoc($res))
		{
			return (int) $arr['bwrite'];
		}
		
		return BAB_CAL_ACCESS_NONE;
	}
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
class bab_OviPersonalCalendar extends bab_OviEventCalendar implements bab_PersonalCalendar
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
	
	
	
	public function getSharingAccess()
	{
		return $this->sharing_access;
	}
	
	
	
	/**
	 * Create personal calendar from id user
	 * 
	 * @param 	int $id_user		owner of calendar
	 * @param	int	$access_user	User to test access rights for
	 * @return bool
	 */
	public function initFromUser($id_user, $access_user = null)
	{
		global $babDB;
		
		if (null === $access_user)
		{
			$access_user = $GLOBALS['BAB_SESS_USERID'];
		}
		
		
		if ($access_user === $id_user)
		{
			$data = self::getUserCalendarData($id_user, BAB_CAL_ACCESS_FULL);
			
			if (!$data)
			{
				return false;
			}
			
			$this->init($access_user, $data);
			return true;
		}
		
		
		
		$query = "
			select 
				cut.id_cal,
				cut.bwrite, 
				u.firstname,
				u.lastname 
	
			from ".BAB_CALACCESS_USERS_TBL." cut 
				,".BAB_CALENDAR_TBL." ct
				,".BAB_USERS_TBL." u
			where 
				ct.id=cut.id_cal 
				and u.id=ct.owner 
				and ct.actif='Y' 
				and disabled='0'
				and u.id=".$babDB->quote($id_user)."
				and cut.id_user=".$babDB->quote($access_user);
		 
	
		$res = $babDB->db_query($query);
		
		
		if ($arr = $babDB->db_fetch_assoc($res))
		{
			// the calendar is accessible throw calendar sharing
			
		
			$data = array(
			
				'idcal' 		=> $arr['id_cal'],
				'name' 			=> bab_composeUserName($arr['firstname'], $arr['lastname']),
				'description' 	=> '',
				'idowner' 		=> $id_user,
				'access'		=> (int) $arr['bwrite']
			
			);
			
			$this->init($access_user, $data);
			
			return true;
		}
	
		
		// the calendar is not accessible
		
		
		$data = self::getUserCalendarData($id_user, BAB_CAL_ACCESS_NONE);
		
		if (!$data)
		{
			return false;
		}
		
		$this->init($access_user, $data);
		return true;
	}
	
	
	
	
	private static function getUserCalendarData($id_user, $access)
	{
		global $babDB;
		
	
		$res = $babDB->db_query("select id from ".BAB_CALENDAR_TBL." where owner='".$babDB->db_escape_string($id_user)."' and actif='Y' and type='1'");
		if( $res && $babDB->db_num_rows($res) >  0)
		{
			$arr = $babDB->db_fetch_assoc($res);
			
			$data = array(
				'idcal'			=> $arr['id'],
				'idowner'		=> $id_user,
				'name' 			=> bab_getUserName($id_user), 
				'description' 	=> '',  
				'access' 		=> $access
			);
			
			return $data;
		}
		
		return null;
	}
	
	
	
	
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
		switch($this->getSharingAccess()) {
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
		if ($this->getSharingAccess() != BAB_CAL_ACCESS_SHARED_UPDATE)
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
		$collection = $event->getCollection();
		
		if ($collection instanceof bab_ReadOnlyCollection) {
			return false;
		}
		
		
		if ($this->access_user == $event->getAuthorId()) {
			// i am the author
			return true;
		}
		
		if ($event->isLocked()) {
			return false;
		}
		
		
		switch($this->getSharingAccess()) {
			
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
	 * 
	 * @return 	string
	 */
	public function getDefaultAttendeePARTSTAT()
	{
		if ($this->access_user == $this->getIdUser())
		{
			// I add myself as attendee on an event
			return 'ACCEPTED';
		}
		
		switch($this->getSharingAccess()) {

			case BAB_CAL_ACCESS_FULL:
				// i have full access on the attendee calendar where the event is
				return 'ACCEPTED';
		}

		return 'NEEDS-ACTION';
	}
	
	
	
	

}


/**
 * Public calendar
 */
class bab_OviPublicCalendar extends bab_OviEventCalendar implements bab_PublicCalendar
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
 * Resource calendar
 */
class bab_OviResourceCalendar extends bab_OviEventCalendar implements bab_ResourceCalendar
{
	public function getType() 
	{
		return bab_translate('Resource calendar');
	}

	/**
	 * Get the type part of the reference
	 * @return unknown_type
	 */
	public function getReferenceType()
	{
		return 'resource';
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




/**
 * Identify a personal calendar
 * the getIdUser method must return an integer
 * and the getType method should return the same string as the bab_OviPersonalCalendar::getType() method
 */
interface bab_PersonalCalendar {
		
	/**
	 * Access level for calendar sharing of the access_user
	 * the method must return one of the following constants :
	 * <ul>
	 * 	<li>BAB_CAL_ACCESS_NONE</li>
	 * 	<li>BAB_CAL_ACCESS_VIEW</li>
	 * 	<li>BAB_CAL_ACCESS_UPDATE</li>
	 * 	<li>BAB_CAL_ACCESS_FULL</li>
	 * 	<li>BAB_CAL_ACCESS_SHARED_UPDATE</li>
	 * </ul>
	 * 
	 * The personal calendar of the access user must return BAB_CAL_ACCESS_FULL
	 * A personal calendar not in any sharing groups of the access user sharing informations must return BAB_CAL_ACCESS_NONE
	 * 
	 * Sharing informations are always recored in ovidentia core table
	 * @see bab_EventCalendar::getSharingAccessForCalendar()
	 * 
	 * @return int
	 */
	public function getSharingAccess();
}


/**
 * Identify a public calendar
 * the getIdUser method must return a null value
 * and the getType method should return the same string as the bab_OviPublicCalendar::getType() method
 */
interface bab_PublicCalendar {
		
	
}


/**
 * Identify a ressource calendar
 * the getIdUser method must return a null value
 * and the getType method should return the same string as the bab_OviResourceCalendar::getType() method
 */
interface bab_ResourceCalendar {
		
	
}