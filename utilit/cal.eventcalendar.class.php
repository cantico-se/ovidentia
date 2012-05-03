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
	 * @var int
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
	protected $id_dgowner = null;




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
	 * @example id from database
	 * @return int
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
	 * Set the user to test access rights for
	 * @param	int		$access_user
	 * @return bab_EventCalendar
	 */
	public function setAccessUser($access_user)
	{
		$this->access_user = $access_user;
		return $this;
	}


	/**
	 * Get approbation sheme ID if any or null if the calendar do not support approbation
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
	 * Get delegation name or null if no delegation
	 * @return string
	 */
	public function getDelegationName()
	{
		if (!$this->id_dgowner)
		{
			return null;
		}
		
		require_once dirname(__FILE__).'/delegincl.php';
		$arr = bab_getDelegationById($this->id_dgowner);
		
		if (count($arr) != 1)
		{
			return null;
		}
		
		$d = reset($arr);
		
		return $d['name'];
	}
	

	/**
	 * Test if the calendar is visisble in a delegation
	 * @param	int		$id_delegation
	 * @return bool
	 */
	public function visibleInDelegation($id_delegation)
	{
		if ($this->id_dgowner == $id_delegation)
		{
			return true;
		}

		return false;
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
	 * Can view calendar, check the active flag only
	 * this is necessary only for personnal calendar
	 * @return bool
	 */
	public function canView() {

		if ($this instanceOf bab_PersonalCalendar)
		{
			global $babDB;

			$res = $babDB->db_query('SELECT actif FROM '.BAB_CALENDAR_TBL.' WHERE id='.$babDB->quote($this->getUid()));
			while ($arr = $babDB->db_fetch_assoc($res))
			{
				return ('Y' === $arr['actif']);
			}
		}

		return true;
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

		$author = $event->getAuthorId();

		if (null === $author) {
			// no author, consider the calendar owner as the event author
			$author = $this->getIdUser();
		}


		if ($this->access_user == $author) {
			return true;
		}

		if (!$event->isPublic()) {

			// Can be PRIVATE or CONFIDENTIAL
			return false;
		}


		// if in a waiting state
		if (!$event->WfInstanceAccess($this->access_user))
		{
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
	 * @param	string				$old_value		Attendee PARTSTAT current value
	 * @param	string				$new_value		Attendee PARTSTAT new value
	 *
	 * @return bool
	 */
	public function canUpdateAttendeePARTSTAT(bab_CalendarPeriod $period, $role, $old_value, $new_value)
	{
		$id_user = (int) $this->getIdUser();

		if (!$id_user)
		{
			return false;
		}

		if ($id_user !== (int) $this->access_user)
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
			// access already granted
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
				 caltype='.$babDB->quote($calendar->getReferenceType()).'
				AND id_cal='.$babDB->quote($calendar->getUid()).'
				AND id_user='.$babDB->quote($this->access_user)
		);


		if ($arr = $babDB->db_fetch_assoc($res))
		{
			return (int) $arr['bwrite'];
		}

		return BAB_CAL_ACCESS_NONE;
	}

	/**
	 * Test if the event has been created by a member of the same "shared access", shared groups are used only on personal calendar
	 * @param bab_PersonalCalendar $calendar
	 * @return bool
	 */
	protected function isSharedAccessForCalendar(bab_PersonalCalendar $calendar, bab_calendarPeriod $event)
	{
		if ($calendar->getSharingAccess() != BAB_CAL_ACCESS_SHARED_UPDATE)
		{
			return false;
		}

		// shared access on calendar for access user

		$author = $event->getAuthorId();

		if (!isset($author))
		{
			return false;
		}


		if (((int) $this->access_user) === (int) $author)
		{
			// i am the author
			return true;
		}

		global $babDB;

		$res = $babDB->db_query('
			SELECT * FROM '.BAB_CALACCESS_USERS_TBL.'
			WHERE
				id_cal='.$babDB->quote($calendar->getUid()).'
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
	 * Display an event in to a calendar placeholder UI element on page
	 * this method is called on the main calendar of event only
	 *
	 * the default behaviour is to display an event on the main calendar placeholder and the placeholders of the attendees or relations or organizer using status or partstat parameter
	 *
	 * @param	bab_EventCalendar	$calendar		calendar of placeholder
	 * @param	bab_CalendarPeriod	$event			Event to display
	 *
	 * @return bool
	 */
	public function displayEventInCalendarUi(bab_EventCalendar $calendar, bab_CalendarPeriod $event)
	{
		$mainCalendar = $this->getUrlIdentifier();
		$placeholderCalendar = $calendar->getUrlIdentifier();


		if ($calendar instanceof bab_PersonalCalendar)
		{
			$attendees = $event->getAttendees();
			foreach($attendees as $attendee)
			{
				if ($attendee['AttendeeBackend']->getRealPartstat() !== 'DECLINED' && $attendee['calendar']->getUrlIdentifier() === $placeholderCalendar)
				{
					return true;
				}
			}

			if (!isset($attendees[$mainCalendar]) && $mainCalendar === $placeholderCalendar)
			{
				// the main calendar of event is not in attendees
				return true;
			}

		}
		else
		{
			$relations = $event->getRelations();
			foreach($relations as $relation)
			{
				if ($relation['X-CTO-STATUS'] !== 'DECLINED' && $relation['calendar']->getUrlIdentifier() === $calendar->getUrlIdentifier())
				{
					return true;
				}
			}


			if (!isset($relations[$mainCalendar]) && $mainCalendar === $placeholderCalendar)
			{
				// the main calendar of event is not in relations
				return true;
			}
		}

		return false;
	}




	/**
	 * Add calendar event to ovidentia inbox
	 * @param bab_CalendarPeriod $event
	 * @return unknown_type
	 */
	public function addToOviInbox(bab_CalendarPeriod $event)
	{
		global $babDB;

		$collection = $event->getCollection();
		$calendar = $collection->getCalendar();
		$eventBackend = $calendar->getBackend();

		$res = $babDB->db_query('SELECT * FROM bab_cal_inbox WHERE id_user='.$babDB->quote($this->getIdUser()).' AND uid='.$babDB->quote($event->getProperty('UID')));
		if ($babDB->db_num_rows($res))
		{
			return;
		}

		if (!$collection->hash)
		{
			// regular event

			$babDB->db_query('
				INSERT INTO bab_cal_inbox
					(id_user, calendar_backend, uid, parent_calendar)
				VALUES
					(
						'.$babDB->quote($this->getIdUser()).',
						'.$babDB->quote($eventBackend->getUrlIdentifier()).',
						'.$babDB->quote($event->getProperty('UID')).',
						'.$babDB->quote($calendar->getUrlIdentifier()).'
					)
			');

		} else {

			// recurring event with hash, insert all collection

			foreach($collection as $event)
			{
				$babDB->db_query('
					INSERT INTO bab_cal_inbox
						(id_user, calendar_backend, uid, parent_calendar)
					VALUES
						(
							'.$babDB->quote($this->getIdUser()).',
							'.$babDB->quote($eventBackend->getUrlIdentifier()).',
							'.$babDB->quote($event->getProperty('UID')).',
							'.$babDB->quote($calendar->getUrlIdentifier()).'
						)
				');
			}
		}
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

		if (!empty($data['idsa'])) {
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

		$id_calendar = bab_getICalendars()->getPersonalCalendarUid($id_user);

		if( null !== $id_calendar)
		{
			$data = array(
				'idcal'			=> $id_calendar,
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
	 * Test if the calendar is visisble in a delegation
	 * the personal calendar is visisble if the user is a member of the delegation
	 * @param	int		$id_delegation
	 * @return bool
	 */
	public function visibleInDelegation($id_delegation)
	{
		require_once dirname(__FILE__).'/delegincl.php';

		if (bab_isUserInDelegation($id_delegation, $this->getIdUser()))
		{
			return true;
		}

		return false;
	}


	/**
	 * Test if an event can be added on a calendar
	 * @return bool
	 */
	public function canAddEvent()
	{
		if (((int) $this->access_user) === (int) $this->getIdUser()) {
			// i am the author
			return true;
		}


		switch($this->getSharingAccess()) {
			case BAB_CAL_ACCESS_SHARED_UPDATE:
			case BAB_CAL_ACCESS_UPDATE:
			case BAB_CAL_ACCESS_FULL:
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

		$author = $event->getAuthorId();
		if (null === $author)
		{
			bab_debug('Missing author ID for event '.$event->getUrlIdentifier());
			return false;
		}


		if (((int) $this->access_user) == $author) {
			// i am the author
			return true;
		}

		if ($event->isLocked()) {
			return false;
		}


		switch($this->getSharingAccess()) {

			case BAB_CAL_ACCESS_UPDATE:
				if (((int) $this->access_user) === $author)
				{
					return true;
				}
				break;

			case BAB_CAL_ACCESS_SHARED_UPDATE:
				if ($this->isSharedAccessForCalendar($this, $event))
				{
					return true;
				}
				break;

			case BAB_CAL_ACCESS_FULL:
				return true;
		}

		// if the access is given by one of the attendees or one of the relation, return true
		// specific beahviour for ovidentia events, in caldav, access is given only with the calendar

		$main = $event->getCollection()->getCalendar();

		if ($this === $main)
		{
			// if we are on the main calendar of event
			foreach($event->getCalendars() as $calendar)
			{
				if ($calendar !== $this && $calendar->canUpdateEvent($event))
				{
					return true;
				}
			}
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

	




	/**
	 * Triggered when the calendar has been added as an attendee on $event
	 * @param bab_CalendarPeriod $event
	 * @return unknown_type
	 */
	public function onAddAttendee(bab_CalendarPeriod $event)
	{
		bab_debug($this->getName().' '.__FUNCTION__);

		$collection = $event->getCollection();
		$calendar = $collection->getCalendar();

		if ($calendar !== $this)
		{
			$this->addToOviInbox($event);
		}

		$this->updateEventAttendee($event);
	}

	/**
	 * Triggered when the calendar has been updated as an attendee on $event
	 * @param bab_CalendarPeriod $event
	 * @return unknown_type
	 */
	public function onUpdateAttendee(bab_CalendarPeriod $event)
	{

		$this->updateEventAttendee($event);
	}









	/**
	 * Update the ovidentia backend copy of an event
	 *
	 * @param bab_CalendarPeriod $event		the event from another backend
	 * @return unknown_type
	 */
	private function updateEventAttendee(bab_CalendarPeriod $event)
	{
		global $babDB;

		$res = $babDB->db_query('SELECT id FROM '.BAB_CAL_EVENTS_TBL.' WHERE uuid='.$babDB->quote($event->getProperty('UID')));
		$arr = $babDB->db_fetch_assoc($res);

		if (!$arr)
		{
			bab_debug('event not found with uid='.$event->getProperty('UID'));
			return;
		}

		$event_id = (int) $arr['id'];

		// if the main event is in ovidentia calendar but the attendee is caldav
		// updating the partstat of the attendee will trigger this method

		$attendees = $event->getAttendees();


		$urlidentifier = $this->getUrlIdentifier();
		if (isset($attendees[$urlidentifier]))
		{
			$attendee = $attendees[$urlidentifier];
			switch($attendee['PARTSTAT'])
			{
				case 'ACCEPTED':
					$this->updateEventStatus($event_id, BAB_CAL_STATUS_ACCEPTED);
					break;

				case 'DECLINED':
					$this->updateEventStatus($event_id, BAB_CAL_STATUS_DECLINED);
					break;
			}
		}
	}



	/**
	 *
	 * @param int $event_id
	 * @param int $status
	 * @return unknown_type
	 */
	private function updateEventStatus($event_id, $status)
	{
		global $babDB;

		$babDB->db_query('update '.BAB_CAL_EVENTS_OWNERS_TBL.' set status='.$babDB->quote($status).' WHERE
			id_event='.$babDB->quote($event_id).'
			AND id_cal='.$this->getUid()
		);

	}
}





abstract class bab_OviRelationCalendar extends bab_OviEventCalendar
{
	/**
	 * Create a copy of the event into the relation calendar
	 * this method is to use only with event stored in external backend to add a copy of the event into ovi backend
	 * for events with multiple relations, the copy will be added only once.
	 * @param bab_CalendarPeriod $event
	 * @return unknown_type
	 */
	private function addEventCopy(bab_CalendarPeriod $event)
	{
		// ovi backend
		$backend = $this->getBackend();

		$relationEvent = clone $event;

		// the new event must have the relation calendar as parent calendar

		// create a new collection into the relation calendar
		$collection = $backend->CalendarEventCollection($this);
		$collection->addPeriod($relationEvent);

		// save a copy of the event into the relation calendar
		// if the event already exists, it will be updated
		$backend->savePeriod($relationEvent);
	}


	/**
	 * Triggered when the calendar has been added as a relation on $event
	 * @param bab_CalendarPeriod $event
	 * @return unknown_type
	 */
	public function onAddRelation(bab_CalendarPeriod $event)
	{
		bab_debug($this->getName().' '.__FUNCTION__);

		$collection = $event->getCollection();
		$calendar = $collection->getCalendar();
		$backend = $calendar->getBackend();

		if ($calendar !== $this && !($backend instanceof Func_CalendarBackend_Ovi))
		{
			$this->addEventCopy($event);
		}
	}

	/**
	 * Triggered when the calendar has been updated as a relation on $event
	 * @param bab_CalendarPeriod $event
	 * @return unknown_type
	 */
	public function onUpdateRelation(bab_CalendarPeriod $event)
	{
		// nothing to do
	}

}









/**
 * Public calendar
 */
class bab_OviPublicCalendar extends bab_OviRelationCalendar implements bab_PublicCalendar
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
		
		return bab_isAccessValid(BAB_CAL_PUB_MAN_GROUPS_TBL, $this->uid, $this->access_user);
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

		if (null !== $event->getWfInstance($this))
		{
			// prevent modification if there is an ongoing approbation instance on event
			return false;
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
class bab_OviResourceCalendar extends bab_OviRelationCalendar implements bab_ResourceCalendar
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
			return bab_isAccessValid(BAB_CAL_RES_UPD_GROUPS_TBL, $this->uid, $this->access_user);
		}

		
		if (null !== $event->getWfInstance($this))
		{
			// prevent modification if there is an ongoing approbation instance on event
			return false;
		}
		
		return bab_isAccessValid(BAB_CAL_RES_MAN_GROUPS_TBL, $this->uid, $this->access_user);
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



	/**
	 * Triggered when the calendar has been added as an attendee on $event
	 * @param bab_CalendarPeriod $event
	 * @return unknown_type
	 */
	public function onAddAttendee(bab_CalendarPeriod $event);

	/**
	 * Triggered when the calendar has been updated as an attendee on $event
	 * @param bab_CalendarPeriod $event
	 * @return unknown_type
	 */
	public function onUpdateAttendee(bab_CalendarPeriod $event);


}


/**
 * Identify a public calendar
 * the getIdUser method must return a null value
 * and the getType method should return the same string as the bab_OviPublicCalendar::getType() method
 */
interface bab_PublicCalendar {

	/**
	 * Triggered when the calendar has been added as a relation on $event
	 * @param bab_CalendarPeriod $event
	 * @return unknown_type
	 */
	public function onAddRelation(bab_CalendarPeriod $event);

	/**
	 * Triggered when the calendar has been updated as a relation on $event
	 * @param bab_CalendarPeriod $event
	 * @return unknown_type
	 */
	public function onUpdateRelation(bab_CalendarPeriod $event);
}


/**
 * Identify a resource calendar
 * the getIdUser method must return a null value
 * and the getType method should return the same string as the bab_OviResourceCalendar::getType() method
 */
interface bab_ResourceCalendar {

	/**
	 * Triggered when the calendar has been added as a relation on $event
	 * @param bab_CalendarPeriod $event
	 * @return unknown_type
	 */
	public function onAddRelation(bab_CalendarPeriod $event);

	/**
	 * Triggered when the calendar has been updated as a relation on $event
	 * @param bab_CalendarPeriod $event
	 * @return unknown_type
	 */
	public function onUpdateRelation(bab_CalendarPeriod $event);
}