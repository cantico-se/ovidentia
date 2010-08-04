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
 * Manage working and non-working hours
 * browse periods with working hours and non-working days
 */
class bab_UserPeriods {

	/**
	 * @var BAB_DateTime
	 */
	public $begin;
	
	
	/**
	 * @var BAB_DateTime
	 */
	public $end;
	
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
	 * List of allowed instance name of PeriodCollection objects
	 * @var array
	 */
	private $options;

	/**
	 * @private
	 */
	private $gn_events = NULL;

	/**
	 * category filter for calendar events
	 * @access public
	 * array|int|NULL
	 */
	public $category = NULL; 
	
	
	/**
	 * List of id_user after event initialization
	 * @var array
	 */
	private $id_users;
	
	

	/**
	 * Working hours object on period
	 * for the current user
	 *
	 * @param BAB_DateTime	$begin
	 * @param BAB_DateTime	$end
	 *
	 */
	public function __construct(BAB_DateTime $begin, BAB_DateTime $end) {		
		$db = $GLOBALS['babDB'];
		
		$this->begin		= $begin;
		$this->end			= $end;
		$this->periods		= array();
		$this->boundaries	= array();
		$this->sibling		= array();

	}


	
	/**
	 * Get all periods by event
	 * Filter by period collection is mandatory
	 * Filter by calendar is mandatory
	 * 
	 * @see bab_eventBeforePeriodsCreated
	 * 
	 * 
	 * @param array $options	list of periodCollection class name and calendar instances to filter
	 * @return unknown_type
	 */
	public function createPeriods(Array $options) {
		
		
		require_once $GLOBALS['babInstallPath'].'utilit/eventperiod.php';
		
		// collect events

		$event = new bab_eventBeforePeriodsCreated($this);
		
		
		foreach($options as $option) {
			if (is_string($option)) {
				$event->addFilterByPeriodCollection($option);
				
			} elseif ($option instanceof bab_EventCalendar) {
				$event->addFilterByCalendar($option);
			}
		}
		
		
		bab_fireEvent($event);

		$this->id_users = $event->getUsers();
	}


	/**
	 * Order boundaries by date
	 * and index siblings
	 */
	public function orderBoundaries() {
		// order by date
		bab_sort::ksort($this->boundaries);

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
					$this->boundaries[$current_boundary][] = & $this->periods[$key];
					if (!isset($this->sibling[$current_boundary])) {
						break;
					}
					$current_boundary = $this->sibling[$current_boundary];
				}
			}
		}
	}



	/**
	 * Add a period
	 * @param bab_calendarPeriod $p
	 * @return unknown_type
	 */
	public function addPeriod(bab_calendarPeriod $p) {

		$this->periods[] = $p;

		$ts = $p->ts_begin;
		if (!isset($this->boundaries[$ts])) {
			$this->boundaries[$ts] = array();
		}

		$ts = $p->ts_end;
		if (!isset($this->boundaries[$ts])) {
			$this->boundaries[$ts] = array();
		}
	}

	/**
	 * @private
	 */
	public function getBeginDate() {
		prev($this->boundaries);
		$ts = key($this->boundaries);
		next($this->boundaries);
		return $ts; 
	}

	/**
	 * @private
	 */
	public function getEndDate() {
		return key($this->boundaries)-1;
	}

	/**
	 * @private
	 */
	public function createUsersPeriods($begin, $end, $type) {
		$arr = array();
		foreach($this->id_users as $id_user) {
			$p = new bab_calendarPeriod($begin, $end, $type);
			$xCtoPuid = & $p->getProperty('X-CTO-PUID');
			$xCtoPuid .= '.'.$id_user;
			$arr[] = $p;
		}

		return $arr;
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
	 *
	 * @param array $filter : events collections to get
	 *
	 * @return	object
	 */
	public function getNextEvent(array $filter) {

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
	 * @param	bab_calendarPeriod		$event
	 * @param	boolean					$available
	 */
	public function setAvailability($event, $available) {
		$boundary = $this->boundaries[$event->ts_begin];
		foreach($boundary as $key => $tmp_evt) {
			if ($tmp_evt->getProperty('X-CTO-PUID') === $event->getProperty('X-CTO-PUID')) {
				$this->boundaries[$event->ts_begin][$key]->available = $available;
			}
		}
	}


	/**
	 * 
	 *
	 * @param	int			$start		timestamp
	 * @param	int			$end		timestamp
	 * @param	array		$filter		: events collections to get
	 * @return	array
	 */
	public function getEventsBetween($start, $end, array $filter = null) {
		reset($this->boundaries);

		$r = array();

		foreach($this->boundaries as $ts => $events) {
			if ($ts > $end) {
				break;
			}
				
			foreach($this->boundaries[$ts] as $event) {
				
				/*@var $event bab_CalendarPeriod */
				
				if ($event->ts_end > $start && $event->ts_begin < $end) {
					
					if (null !== $filter)
					{
						$collection = $event->getCollection();
						$accepted = true;
						foreach($filter as $allowedcollection)
						{
							if (!($collection instanceof $allowedcollection))
							{
								$accepted = false;
								break;
							}
						}
						
						if (!$accepted)
						{
							continue;
						}
					
					}
					
					$r[$event->getProperty('X-CTO-PUID')] = $event;
				}
			}
		}
		return $r;
	}
	
	
	

	

	/**
	 * Find available periods on all processed period of object
	 * @return 	bab_availabilityReply
	 */
	public function getAvailability() {
		
		reset($this->boundaries);
		$previous = NULL;
		$availabilityReply = new bab_availabilityReply();
		
		$test_begin = $this->begin->getTimeStamp();
		$test_end = $this->end->getTimeStamp();

		// si pas d'agenda utilisateur
		if (!$this->id_users) {
		
			foreach($this->boundaries as $ts => $events) {
				
				$nb_unAvailable = 0;
				foreach($events as $event) {
					if ($event->ts_end > $test_begin && $event->ts_begin < $test_end) {
						if (!$event->isAvailable()) {

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
					// autoriser la creation d'une nouvelle periode � partir de $ts
					$previous = $ts;
				}
				
				
				if ($nb_unAvailable > 0 && false !== $previous && $previous < $ts) {
					
					$availabilityReply->available_periods[$previous.'.'.$ts] = new bab_calendarPeriod($previous, $ts, BAB_PERIOD_NONWORKING);
					
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
				$availabilityReply->available_periods[$previous.'.'.$test_end] = new bab_calendarPeriod($previous, $test_end, BAB_PERIOD_NONWORKING);

			}
	
			return $availabilityReply;

		}
		
		
		
		// si agenda utilisateur
		
		foreach($this->boundaries as $ts => $events) {

			// toutes les personnes disponibles sur le boundary
			if ($this->id_users) {
				$users_non_available = $this->id_users;
			} else {
				$users_non_available = array();
			}
			$working_period = false;

			

			// supprimer les utilisateurs pas dispo de la liste pour le boundary

			foreach($events as $event) {

				if ($event->ts_end > $test_begin && $event->ts_begin < $test_end) {
					
					$data = $event->getData();
					
					if (isset($data['id_user'])) {
						// periode de dispo utilisateur
						$id_users = array($data['id_user']);
						$working_period = true;
						
					} elseif (!empty($data['iduser_owners'])) {
						// evenement, liste des utilisateurs associes
						$id_users = $data['iduser_owners'];
						
					} else {
						// autres : (ex jours feries, agenda de ressource) considerer l'utilisateur courrant comme associe a l'evenement
						$id_users = array($GLOBALS['BAB_SESS_USERID']);
					}
					
					
					
					if ($event->isAvailable()) {
						// l'evenement est dispo, retirer les utilisateurs de l'evenement de la liste des utilisateurs non dispo du boundary
						foreach($id_users as $id_user) {
							if (isset($users_non_available[$id_user]) 
											&& true !== $users_non_available[$id_user]) {
											
								unset($users_non_available[$id_user]);
							}
						}
						
					} else {
						// l'evenement n'est pas dispo, ajouter les utilisateurs de l'evenement dans la liste des utilisateurs non dispo du boundary
						
						foreach($id_users as $id_user) {
							$users_non_available[$id_user] = true;
						}
						
						$availabilityReply->conflicts_events[] = $event;
					}
				}
			}
			
			// boolean : le boundary est disponible pour tout le monde
			$boundary_free_for_all = 0 === count($users_non_available) && $working_period;

			// bab_debug($ts.' -- '.bab_shortDate($ts).' --- '.$previous.' -->  count users_non_available : '.count($users_non_available));

			if (!$boundary_free_for_all && NULL !== $previous) {

				// au moins 1 evenement pas dispo dans le boundary, fermer la periode de dispo
			
				$tmp_begin 	= $previous < $test_begin ? $test_begin : $previous; 
				$tmp_end 	= $ts > $test_end ? $test_end : $ts; 
			
				if (!isset($availabilityReply->available_periods[$tmp_begin.'.'.$tmp_end]) && $tmp_begin != $tmp_end) {
					$availabilityReply->available_periods[$tmp_begin.'.'.$tmp_end] = new bab_calendarPeriod($tmp_begin, $tmp_end, BAB_PERIOD_NONWORKING);
				}
				$previous = NULL;
				// bab_debug('non-free '.bab_shortDate($ts));
			}


			if ($boundary_free_for_all && NULL === $previous) {
				// tout les utilisateurs sont dispo sur tout les evenements du boundary, d�marrer la periode de dispo
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
	 * @var bool
	 */
	public $status = NULL;
	
	/**
	 * @var array
	 */
	public $available_periods = array();
	
	/**
	 * Events in conflict
	 * @var array
	 */
	public $conflicts_events = array();
}
