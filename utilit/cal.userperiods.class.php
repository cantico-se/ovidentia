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


require_once dirname(__FILE__).'/cal.criteria.class.php';


/**
 * Manage working and non-working hours
 * browse periods with working hours and non-working days
 */
class bab_UserPeriods implements Countable, seekableIterator {

	/**
	 * @var BAB_DateTime
	 */
	public $begin;


	/**
	 * @var BAB_DateTime
	 */
	public $end;


	/**
	 * @var array	<bab_PeriodCollection>
	 */
	public $periodCollection = null;


	/**
	 * @var array
	 */
	public $icalProperties = null;


	/**
	 * @var array	<bab_EventCalendar>
	 */
	public $calendars = null;

	/**
	 * @var int
	 */
	public $id_delegation = null;

	/**
	 * @var string
	 */
	public $hash = null;


	/**
	 * Array of UID to select
	 * @var array	<string>
	 */
	public $uid_criteria = null;


	/**
	 *
	 * @var array
	 */
	private $periods;

	/**
	 *
	 * @var array
	 */
	private $boundaries;

	/**
	 *
	 * @var array
	 */
	private $sibling;



	/**
	 * used in the getNextEvent method
	 * @see bab_UserPeriods::getNextEvent
	 */
	private $gn_events = NULL;


	/**
	 * @var bab_PeriodCriteria
	 */
	private $criteria;



	/**
	 *
	 * @var int
	 */
	private $iter_horizontal;

	/**
	 *
	 * @var int
	 */
	private $iter_vertical;

	/**
	 *
	 * @var array
	 */
	private $iter_boundary;

	/**
	 * @var bab_CalendarPeriod
	 */
	private $iter_value;

	/**
	 *
	 * @var bool
	 */
	private $iter_status;

	/**
	 *
	 * @var int
	 */
	private $iter_key;


	/**
	 * cache for requested calendars
	 * @see self::isRequestedCalendar()
	 * @var array
	 */
	private $requested_calendars = null;


	/**
	 *
	 * @var int
	 */
	private $countperiod = 0;


	/**
	 * List of vacation requests provided by request, used to prevent vacation duplicate
	 * @var unknown_type
	 */
	private $vacationindex = array();



	/**
	 * Working hours object on period
	 * for the current user
	 * the begin and end dates can be set using criteria
	 *
	 * @param BAB_DateTime	$begin
	 * @param BAB_DateTime	$end
	 *
	 */
	public function __construct(BAB_DateTime $begin = null, BAB_DateTime $end = null) {

		$this->begin		= $begin;
		$this->end			= $end;
		$this->periods		= array();
		$this->boundaries	= array();
		$this->sibling		= array();

	}






	/**
	 * Add a filter by period collection
	 * @param array $periodCollection	<bab_PeriodCollection>
	 * @return bab_eventCollectPeriodsBeforeDisplay
	 */
	public function filterByPeriodCollection(array $periodCollection) {
		$this->periodCollection = $periodCollection;
		return $this;
	}

	/**
	 * Add a filter by classname of event collection
	 * @param bab_PeriodCollection $periodCollection
	 * @return bab_eventCollectPeriodsBeforeDisplay
	 */
	public function addFilterByPeriodCollection(bab_PeriodCollection $periodCollection) {
		if (!isset($this->periodCollection)) {
			$this->periodCollection = array();
		}

		$this->periodCollection[] = $periodCollection;
		return $this;
	}



	/**
	 *
	 * @param bab_PeriodCollection | string $collection
	 * @return bool
	 */
	public function isPeriodCollection($collection) {
		if (null === $this->periodCollection)
		{
			return true;
		}

		foreach($this->periodCollection as $periodCollection) {
			if ($collection === $periodCollection || ($collection instanceof $periodCollection)) {
				return true;
			}
		}

		return false;
	}



	/**
	 *
	 * @param array $calendars	array of bab_EventCalendar
	 * @return bab_eventCollectPeriodsBeforeDisplay
	 */
	public function filterByCalendar(array $calendars) {
		$this->calendars = $calendars;
		return $this;
	}


	public function filterByDelegation($id_delegation) {
		$this->id_delegation = $id_delegation;
		return $this;
	}

	/**
	 * Add a calendar to the list of displayable calendars
	 * @param bab_EventCalendar $calendar
	 * @return bab_eventCollectPeriodsBeforeDisplay
	 */
	public function addFilterByCalendar(bab_EventCalendar $calendar) {
		if (!isset($this->calendars)) {
			$this->calendars = array();
		}

		$this->calendars[] = $calendar;
		return $this;
	}






	/**
	 * Add a filter by iCal property (ex. CATEGORIES)
	 * @param 	string 	$property		iCal property name
	 * @param 	array 	$values			list of allowed exact values for this property
	 * @param	bool	$contain
	 * @return bab_eventCollectPeriodsBeforeDisplay
	 */
	public function filterByICalProperty($property, Array $values, $contain)
	{
		$this->icalProperties[$property] = array(
			'values' => $values,
			'contain' => $contain
		);
		return $this;
	}


	/**
	 * Get users id of calendars
	 * @return array
	 */
	public function getUsers()
	{
		if (!isset($this->calendars)) {
			return array();
		}

		$return = array();
		foreach($this->calendars as $calendar) {
			$iduser = $calendar->getIdUser();
			if ($iduser) {
				$return[$iduser] = $iduser;
			}
		}

		return $return;
	}


	/**
	 *
	 * @return bab_PeriodCriteria
	 */
	public function getCriteria()
	{
		return $this->criteria;
	}




	/**
	 * Get all periods by event
	 * Filter by period collection is mandatory
	 * Filter by calendar is mandatory
	 *
	 * @see bab_eventBeforePeriodsCreated
	 *
	 *
	 * @param bab_PeriodCriteria $criteria	criteria of query (calendar, periodCollection, property)
	 * @return unknown_type
	 */
	public function createPeriods(bab_PeriodCriteria $criteria) {


		require_once $GLOBALS['babInstallPath'].'utilit/eventperiod.php';


		$this->setCriteria($criteria);

		// collect events

		$event = new bab_eventBeforePeriodsCreated($this);
		$this->processCriteria($this->criteria);

		$start = microtime(true);

		bab_fireEvent($event);

		$duration = microtime(true) - $start;
		bab_debug(sprintf("createPeriods bab_fireEvent  : %s s", round($duration,3)), DBG_TRACE, 'Statistics');

	}



	public function setCriteria(bab_PeriodCriteria $criteria)
	{
		$this->criteria = $criteria;
	}


	/**
	 * Process criteria to userperiods object
	 *
	 * @return unknown_type
	 */
	public function processCriteria(bab_PeriodCriteria $criteria)
	{

		// add criteria to event
		$criteria->process($this);

		foreach($criteria->getCriterions() as $criteria) {
			$this->processCriteria($criteria);
		}
	}


	/**
	 * Order boundaries by date
	 * and index siblings
	 */
	public function orderBoundaries() {

		$this->vacationindex = array();

		// order by date
		ksort($this->boundaries);

		$previous = NULL;
		foreach($this->boundaries as $ts => $arr) {
			if (NULL !== $previous) {
				$this->sibling[$previous] = $ts;
			}
			$previous = $ts;
		}

		foreach($this->periods as $key => $p) {
			$ts = $p->ts_begin;
			if (isset($this->boundaries[$ts])) {
				$current_boundary = $ts;
				while ($current_boundary < $p->ts_end) {
					// add period on overlapped boudaries
					$this->boundaries[$current_boundary][] = $this->periods[$key];
					if (!isset($this->sibling[$current_boundary])) {
						break;
					}
					$current_boundary = $this->sibling[$current_boundary];
				}
			}
		}

		$this->rewind();
	}



	/**
	 * Add a period
	 * @param bab_calendarPeriod $p
	 * @return unknown_type
	 */
	public function addPeriod(bab_calendarPeriod $p) {

		if (!$p->ts_begin)
		{
			require_once dirname(__FILE__).'/devtools.php';

			bab_debug("Missing begin date on period\n".print_r($p->getProperties(), true));
			bab_debug_print_backtrace();
			return;
		}

		// ignore duplicated vacations

		if ('' !== $id = $p->getProperty('X-CTO-VACATION'))
		{
			if (isset($this->vacationindex[$id]))
			{
				$currentcollection = $this->periods[$this->vacationindex[$id]]->getCollection();
				$newcollection = $p->getCollection();

				// give priority to the vacationPeriod Collection

				if (($currentcollection instanceof bab_CalendarEventCollection) && ($newcollection instanceof bab_VacationPeriodCollection))
				{
					$pos = $this->vacationindex[$id];
					$this->periods[$pos] = $p;
				}
			}
			else
			{
				$this->countperiod++;
				$pos = $this->addPeriodToBoundaries($p);
				$this->vacationindex[$id] = $pos;
			}

			return;
		}

		$this->countperiod++;
		$pos = $this->addPeriodToBoundaries($p);
	}



	/**
	 * Add one period to boundaries
	 * @param bab_calendarPeriod $p
	 * @return unknown_type
	 */
	private function addPeriodToBoundaries(bab_calendarPeriod $p)
	{
		$pos = count($this->periods);
		$this->periods[$pos] = $p;

		$ts = $p->ts_begin;
		if (!isset($this->boundaries[$ts])) {
			$this->boundaries[$ts] = array();
		}

		$ts = $p->ts_end;
		if (!isset($this->boundaries[$ts])) {
			$this->boundaries[$ts] = array();
		}

		return $pos;
	}




	/**
	 * Test if a calendar has been requested
	 * @param bab_EventCalendar $calendar
	 * @return bool
	 */
	private function isRequestedCalendar(bab_EventCalendar $calendar)
	{
		if (null === $this->requested_calendars)
		{
			foreach($this->calendars as $req)
			{
				$this->requested_calendars[$req->getUrlIdentifier()] = 1;
			}
		}

		return isset($this->requested_calendars[$calendar->getUrlIdentifier()]);
	}




	/**
	 *
	 */
	private function getBeginDate() {
		prev($this->boundaries);
		$ts = key($this->boundaries);
		next($this->boundaries);
		return $ts;
	}

	/**
	 *
	 */
	private function getEndDate() {
		return key($this->boundaries)-1;
	}



	/**
	 * Get next period
	 */
	public function getNextPeriod() {
		static $call = NULL;

		if (NULL === $call) {
			$call = 1;
			reset($this->boundaries);
		}


		if (list(,$events) = each($this->boundaries)) {
			return $events;
		}

		$call = NULL;
		return false;
	}


	/**
	 * HTML view of boundaries
	 * @return unknown_type
	 */
	public function debugBoundaries()
	{
		$html = '<table border="1">';
		$html .= '<thead>';
		foreach($this->boundaries as $h => $arr)
		{
			$html .= '<td>'.bab_toHtml(bab_shortDate($h)).'</td>';
		}

		$html .= '</thead>';

		$html .= '<tbody>';
		foreach($this->boundaries as $h => $arr)
		{
			$html .= '<td>';
			foreach($arr as $v => $event)
			{
				$html .= '<p>'.bab_toHtml($event->getProperty('SUMMARY')).'</p>';
			}
			$html .= '</td>';
		}
		$html .= '</tbody>';
		$html .= '</table>';

		return $html;
	}


 	public function rewind()
    {
        reset($this->boundaries);
        $this->iter_boundary = null;
        $this->iter_key = -1;
        $this->next();
    }

    /**
     * Period
     * @return bab_CalendarPeriod
     */
    public function current()
    {
        return $this->iter_value;
    }

    /**
     * period position
     * @return int
     */
    public function key()
    {
    	return $this->iter_key;
    }

    /**
	 * Next
	 * boundaries are ordered by ts_begin, the iterator will browse only event instance attached to the first boundary in chronological order
     */
    public function next()
    {
    	if (!isset($this->iter_boundary))
    	{
    		if (!list($this->iter_horizontal, $this->iter_boundary) = each($this->boundaries))
    		{
    			$this->iter_status = false;
    			return;
    		}
    	}


      	if (!list($this->iter_vertical, $this->iter_value) = each($this->iter_boundary))
      	{
      		$this->iter_boundary = null;
      		$this->next();
      		return;
      	}

      	if ($this->iter_horizontal !== $this->iter_value->ts_begin)
      	{
      		// this is not the first boundary of event
      		$this->next();
      		return;
      	}

      	$this->iter_status = true;
      	$this->iter_key++;
    }

    /**
	 * @return bool
     */
    public function valid()
    {
        return $this->iter_status;
    }

    /**
     * Number of periods
	 * @return int
     */
	public function count()
	{
		return $this->countperiod;
	}

	/**
	 * Seek to position
	 */
	public function seek($index)
	{
		$this->rewind();

		if (0 === $index)
		{
			return;
		}

		while ($this->iter_status && $index !== $this->key()) {
	        $this->next();
	    }
	}



	/**
	 * Browse periods filtered by event collection
	 * @param array $filter : events collections to get
	 *
	 * @return	object
	 */
	public function getNextEvent(array $filter = null) {

		if (NULL === $this->gn_events) {
			$this->gn_events = $this->getEventsBetween($this->begin->getTimeStamp(), $this->end->getTimeStamp(), $filter);

		}

		if (list(,$event) = each($this->gn_events)) {
			return $event;
		}

		reset($this->gn_events);
		return false;
	}



	/**
	 * set availability status for one event
	 *
	 * @param	bab_CalendarPeriod		$period
	 * @param	boolean					$available
	 *
	 * @return  bool					return false on failure
	 */
	public function setAvailability($period, $available) {

		$return = false;
		foreach($this->boundaries as $tskey => $boundary)
		{
			foreach($boundary as $key => &$tmp_evt) {
				if ($tmp_evt->getProperty('UID') === $period->getProperty('UID')) {
					$this->boundaries[$tskey][$key]->available = $available;
					$return = true;
				}
			}
		}
		return $return;
	}


	/**
	 *
	 *
	 * @param	int			$start		timestamp
	 * @param	int			$end		timestamp
	 * @param	array		$filter		: events collections to get
	 * @param	bool		$includeCancelled	Bool
	 *
	 * @return	multitype:bab_CalendarPeriod	An array of bab_CalendarPeriod
	 */
	public function getEventsBetween($start, $end, array $filter = null, $includeCancelled = true)
	{
		reset($this->boundaries);
		$r = array();
		$duplicates_index = array();


		foreach ($this->boundaries as $ts => $events) {

			if ($ts > $end) {
				break;
			}

			foreach ($this->boundaries[$ts] as $event) {

				/*@var $event bab_CalendarPeriod */

				if ((!$includeCancelled) && ('CANCELLED' === $event->getProperty('STATUS'))) {
					// Ignore cancelled events
					continue;
				}
				
				$calendar_uid = '';
				if ($collection = $event->getCollection())
				{
					if ($calendar = $collection->getCalendar())
					{
						$calendar_uid = $calendar->getUrlIdentifier();
					}
				}

				$uid = $event->getProperty('UID');

				if ('' === $uid) {
					// bab_debug('event ignored because the is no UID property ('.$event->getProperty('SUMMARY').')', DBG_ERROR, 'alert');
					continue;
				}
				
				
				$u_key = $calendar_uid.'/'.$uid;
				$d_key = md5($event->ts_begin . $event->ts_end. $event->getProperty('SUMMARY').$event->getProperty('LOCATION').$event->getProperty('CATEGORIES').$event->getProperty('DESCRIPTION'));
				
				
				if (isset($duplicates_index[$d_key]))
				{
					// T7736 dedoublonner les evenements sur le meme agenda
					if ($duplicates_index[$d_key] >= $event->getProperty('DTSTAMP'))
					{
						// a duplicate allready added
						// bab_debug('Ignore duplicate event '.$uid.' on calendar '.$calendar_uid);
						continue;
					}
				}

				
				$duplicates_index[$d_key] = $event->getProperty('DTSTAMP');


				if ($event->ts_end > $start && $event->ts_begin < $end) {

					if (null !== $filter && !isset($r[$u_key])) {

						$collection = $event->getCollection();

						$accepted = false;
						foreach ($filter as $allowedcollection) {
							if ($collection instanceof $allowedcollection) {
								$accepted = true;
								break;
							}
						}

						if (!$accepted) {
							// bab_debug('Event ingored because not in allowed collection list : '.$event->toHtml());
							continue;
						}

					}
	
					// permettre d'avoir le meme UID dans deux agendas differents
					// ne pas permettre d'avoir le meme UID dans le meme agenda
					$r[$u_key] = $event;
				}
			}
		}
		
		return $r;
	}






	/**
	 * Find available periods on all processed periods of the query object
	 * @return 	bab_availabilityReply
	 */
	public function getAvailability() {

		reset($this->boundaries);
		$previous = NULL;
		$availabilityReply = new bab_availabilityReply();
		$collection = new bab_AvailablePeriodCollection;

		$test_begin = $this->begin->getTimeStamp();
		$test_end = $this->end->getTimeStamp();

		$global_users = $this->getUsers();

		// si pas d'agenda utilisateur
		if (!$global_users) {

			return $this->getNoUsersAvailablity();
		}

		// si agenda utilisateur

		return $this->getUsersAvailability($global_users);
	}



	/**
	 * Find available periods on all processed periods if there are no personal calendars into the requested calendars
	 * @return bab_availabilityReply
	 */
	private function getNoUsersAvailablity()
	{
		reset($this->boundaries);
		$previous = NULL;
		$availabilityReply = new bab_availabilityReply;
		$collection = new bab_AvailablePeriodCollection;

		$test_begin = $this->begin->getTimeStamp();
		$test_end = $this->end->getTimeStamp();


		foreach($this->boundaries as $ts => $events) {

			$nb_unAvailable = 0;
			foreach($events as $event) {
				if ($event->ts_end > $test_begin && $event->ts_begin < $test_end) {
					if (!$event->isTransparent()) {

						$nb_unAvailable++;
						$availabilityReply->conflicts_events[] = $event;

					}
				}
			}


			if ($nb_unAvailable > 0 && NULL === $previous) {
				// autoriser la creation d'une nouvelle periode a partir de $test_begin
				$previous = $test_begin;
			}


			if (0 === $nb_unAvailable) {
				// autoriser la creation d'une nouvelle periode a partir de $ts
				$previous = $ts;
			}


			if ($nb_unAvailable > 0 && false !== $previous && $previous < $ts) {

				$period = new bab_calendarPeriod($previous, $ts);
				$collection->addPeriod($period);
				$availabilityReply->available_periods[$previous.'.'.$ts] = $period;

				// tant que les boundaries sont unavailable, interdire la creation de nouvelles periodes
				$previous = false;
			}
		}

		// si $previous est encore = a NULL, il n'y a aucun evenements qui genere de la non disponibilite
		if (NULL === $previous) {

			$availabilityReply->status = true;

			// autoriser la creation d'une nouvelle periode a partir de $test_begin
			$previous = $test_begin;

		}



		if (false !== $previous && $previous < $test_end) {

			$period = new bab_calendarPeriod($previous, $test_end);
			$collection->addPeriod($period);
			$availabilityReply->available_periods[$previous.'.'.$test_end] = $period;

		}

		return $availabilityReply;

	}





	/**
	 * Find available periods on all processed periods if there is at least one personal calendar
	 * @param array $global_users	the list of users for all query
	 * @return bab_availabilityReply
	 */
	private function getUsersAvailability($global_users)
	{
		reset($this->boundaries);
		$previous = NULL;
		$availabilityReply = new bab_availabilityReply;
		$collection = new bab_AvailablePeriodCollection;

		$test_begin = $this->begin->getTimeStamp();
		$test_end = $this->end->getTimeStamp();


		foreach($this->boundaries as $ts => $events) {


			/**
			 * The users non-available on boundary are initialized from all the users contained in the list of calendars requested by the query
			 * @var array
			 */
			$users_non_available = $global_users;


			$working_period = false;


			// supprimer les utilisateurs pas dispo de la liste pour le boundary

			foreach($events as $event) {

				if ('CANCELLED' === $event->getProperty('STATUS'))
				{
					// ignore canceled events
					continue;
				}

				if ($event->ts_end > $test_begin && $event->ts_begin < $test_end) {

					$id_users = array();

					$collection = $event->getCollection();

					if ($collection instanceof bab_WorkingPeriodCollection)
					{
						$data = $event->getData();
						$id_users = array($data['id_user']);
						$working_period = true;
					}

					if ($collection instanceof bab_VacationPeriodCollection)
					{
						$data = $event->getData();
						$id_users = array($data['id_user']);
					}


					if ($collection instanceof bab_CalendarEventCollection || $collection instanceof bab_InboxEventCollection)
					{
						$attendees = $event->getAttendees();
						if ($attendees)
						{
							foreach($attendees as $attendee)
							{
								$user = $attendee['calendar']->getIdUser();
								if (!$user)
								{
									bab_debug('there is an attendee without associated user on the event :
									'.print_r($event->getProperties(), true));
									continue;
								}


								if (!isset($global_users[$user]))
								{
									// the user is in conflict but not requested as available, ignore
									continue;
								}


								// ignore declined attendees
								if ('DECLINED' === $attendee['AttendeeBackend']->getRealPartstat())
								{
									continue;
								}

								$id_users[] = $user;
							}
						}

						if (!in_array($GLOBALS['BAB_SESS_USERID'], $id_users))
						{
							$id_users[] = $GLOBALS['BAB_SESS_USERID'];
						}
					}

					if ($event->isTransparent() || 0 === count($id_users)) {

						// l'evenement est dispo ou aucun utilisateur sur l'evenement,
						// retirer les utilisateurs de l'evenement de la liste des utilisateurs non dispo du boundary si il y en a
						// l'evenement ne sera pas considere comme un conflit

						foreach($id_users as $id_user) {
							if (isset($users_non_available[$id_user]) && true !== $users_non_available[$id_user]) {

								unset($users_non_available[$id_user]);
							}
						}

					} else {

						// l'evenement n'est pas dispo, ajouter les utilisateurs de l'evenement dans la liste des utilisateurs non dispo du boundary

						if (isset($id_users))
						{
							foreach($id_users as $id_user) {
								$users_non_available[$id_user] = true;
							}
						}

						$availabilityReply->conflicts_events[] = $event;
					}
				}
			}



			// boolean : le boundary est disponible pour tout le monde
			$boundary_free_for_all = 0 === count($users_non_available) && $working_period;

			// bab_debug($ts.' -- '.bab_shortDate($ts).' --- '.$previous.' -->  count users_non_available : '.count($users_non_available).' FFA : '.($boundary_free_for_all ? 'Y' : 'N'));

			if (!$boundary_free_for_all && NULL !== $previous) {

				// au moins 1 evenement pas dispo dans le boundary, fermer la periode de dispo

				$tmp_begin 	= $previous < $test_begin ? $test_begin : $previous;
				$tmp_end 	= $ts > $test_end ? $test_end : $ts;

				if (!isset($availabilityReply->available_periods[$tmp_begin.'.'.$tmp_end]) && $tmp_begin < $tmp_end) {
					$period = new bab_calendarPeriod($tmp_begin, $tmp_end);
					$collection->addPeriod($period);
					$availabilityReply->available_periods[$tmp_begin.'.'.$tmp_end] = $period;
				}
				$previous = NULL;
				// bab_debug('non-free '.bab_shortDate($ts));
			}


			if ($boundary_free_for_all && NULL === $previous) {
				// tout les utilisateurs sont dispo sur tout les evenements du boundary, demarrer la periode de dispo
				$previous = $ts;
				// bab_debug('free '.bab_shortDate($ts));
			}
		}



		if (1 === count($availabilityReply->available_periods) && isset($availabilityReply->available_periods[$test_begin.'.'.$test_end])) {
			$availabilityReply->status = true;
		} else {
			$availabilityReply->status = false;
		}

		return $availabilityReply;
	}







	/**
	 * Get availability periods between two dates with a minimum duration
	 * @param	int		$start	timestamp
	 * @param	int		$end	timestamp
	 * @param	int		$gap	minimum event duration in seconds
	 * @return 	array
	 */
	public function getAvailabilityBetween($start, $end, $gap) {
		static $availability = NULL;

		if (NULL === $availability) {
			$obj = $this->getAvailability();
			$availability = $obj->available_periods;

		} else {
			reset($availability);
		}



		$r = array();

		foreach($availability as $event) {
			if ($event->ts_begin > $end) {
				break;
			}


			if ($event->ts_end > $start && $event->ts_begin < $end && ($event->ts_end - $event->ts_begin) >= $gap) {
				$r[] = $event;
			}
		}

		return $r;
	}
}





/**
 * Response to an availability request
 * @see bab_UserPeriods::getAvailability()
 */
class bab_availabilityReply {

	/**
	 * true if all the request interval is available
	 * @var bool
	 */
	public $status = NULL;

	/**
	 * List of available periods
	 * @var array	<bab_calendarPeriod>
	 */
	public $available_periods = array();

	/**
	 * Events in conflict
	 * @var array	<bab_calendarPeriod>
	 */
	public $conflicts_events = array();
}










