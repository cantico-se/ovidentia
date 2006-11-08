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
include "base.php";




function bab_insertWorkingHours($iIdUser, $iWeekDay, $sStartHour, $sEndHour)
{
	global $babDB;

	$query = 
		'INSERT INTO ' . BAB_WORKING_HOURS_TBL . ' ' .
			'(`weekDay`, `idUser`, `startHour`, `endHour`) ' .
		'VALUES ' . 
			'(' . $babDB->quote($iWeekDay) . ', ' . $babDB->quote($iIdUser) . ', ' . $babDB->quote($sStartHour) . ', ' . $babDB->quote($sEndHour) . ')'; 

	//bab_debug($query);
	
	$res = $babDB->db_query($query);
	if(false != $res)
	{
		return $babDB->db_insert_id();
	}
	return false;
}

function bab_deleteAllWorkingHours($iIdUser)
{
	global $babDB;
	$query = 'DELETE FROM '	. BAB_WORKING_HOURS_TBL . ' WHERE idUser = \'' . $iIdUser . '\'';
	$babDB->db_query($query);
}





function bab_createDefaultWorkingHours($iIdUser)
{
	require_once($GLOBALS['babInstallPath']. 'utilit/calapi.php');

	$sWorkingDays = null;
	bab_calGetWorkingDays($iIdUser, $sWorkingDays);
	$aWorkingDays = explode(',', $sWorkingDays);
	
	foreach($aWorkingDays as $key => $iWeekDay)
	{
		bab_insertWorkingHours($iIdUser, $iWeekDay, '09:00', '12:00');
		bab_insertWorkingHours($iIdUser, $iWeekDay, '13:00', '18:00');
	}
}



/**
 * @param int $id_user
 * @param int $weekday
 */
function bab_getWHours($id_user, $weekday, $db_id_user = NULL) {

	static $result = array();
	if (isset($result[$id_user.','.$weekday])) {
		return $result[$id_user.','.$weekday];
	}

	$db = $GLOBALS['babDB'];

	if (NULL === $db_id_user) {
		$db_id_user = $id_user;
	}

	$res = $db->db_query("
		SELECT  
			weekDay,  
			startHour, 
			endHour 
		FROM ".BAB_WORKING_HOURS_TBL." WHERE 
			idUser =".$db->quote($db_id_user)." "
		);

	
	if (0 == $db->db_num_rows($res) && 0 != $id_user) {
		return bab_getWHours($id_user, $weekday, 0);
	}

	for ($i = 0; $i < 7; $i++) {
		if (!isset($result[$id_user.','.$i])) {
			$result[$id_user.','.$i] = array();
		}
	}
	
	while ($arr = $db->db_fetch_assoc($res)) {
		$result[$id_user.','.$arr['weekDay']][] = $arr;
	}


	return $result[$id_user.','.$weekday];
}




/**
 * Period object
 * 
 */
class bab_calendarPeriod {

	var $ts_begin;		// public
	var $ts_end;		// public
	var $type;			// public
	var $data;			// private
	var $properties;	// private

	/**
     * $color is not defined in ical interface
	 */
	var $color;

	/**
	 * @param int		$begin		timestamp
	 * @param int		$end		timestamp
	 * @param int		$type
	 */
	function bab_calendarPeriod($begin, $end, $type) {

		$this->type		= $type;
		$this->ts_begin = $begin;
		$this->ts_end	= $end;

		$this->properties = array(
				'UID'		=> $type.'.'.$begin.'.'.$end,
				'CLASS'		=> 'PUBLIC'
			);
	}

	/**
	 * define a property with a icalendar property name
	 * the value is not compliant with the icalendar format
	 * Dates are defined as ISO datetime
	 *
	 * @param	string	$icalProperty
	 * @param	mixed	$value
	 */
	function setProperty($icalProperty, $value) {
		$this->properties[$icalProperty] = $value;
	}

	/**
	 * get a property with a icalendar property name
	 *
	 * @param	string	$icalProperty
	 * @return	mixed
	 */
	function & getProperty($icalProperty) {
		if (isset($this->properties[$icalProperty])) {
			return $this->properties[$icalProperty];
		} else {
			$this->properties[$icalProperty] = '';
			return $this->properties[$icalProperty];
		}
	}


	/**
	 * @return mixed
	 */
	function & getData() {
		return $this->data;
	}

	/**
	 * @param mixed $data
	 */
	function setData($data) {
		$this->data = $data;
	}

	/**
	 * get duration beetwen the two dates
	 * @return int (seconds)
	 */
	function getDuration() {
		return ($this->ts_end - $this->ts_begin);
	}

	/**
	 * @param string $begin		ISO date time
	 * @param string $end		ISO date time
	 * @return boolean
	 
	function isOverlappedBy($begin, $end) {

		return (bab_mktime($begin) >= $this->ts_begin && bab_mktime($end) <= $this->ts_end);
	}
	*/

	/**
	 * Add duration to timestamp
	 */
	function add(&$timestamp, $duration) {
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
	 * @param int		$duration (seconds)
	 * @return array
	 */
	function split($duration) {

		$return = array();
		
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
			$p = new bab_calendarPeriod($this->ts_begin, $endDate, $this->type);
			$p->properties = $this->properties;
			$p->setData($this->data);
			$return[] = $p;

			return $return; // 1 period 
		}


		$p = new bab_calendarPeriod($this->ts_begin, $endDate, $this->type);
		$p->properties = $this->properties;
		$p->setData($this->data);
		$return[] = $p;
		

		while ($start < ($this->ts_end - $duration)) {
			
			$beginDate = $start;
			$this->add($start, $duration);

			$p = new bab_calendarPeriod($beginDate, $start, $this->type);
			$p->properties = $this->properties;
			$p->setData($this->data);
			$return[] = $p;
		}

		// add last period
		$p = new bab_calendarPeriod($start, $this->ts_end, $this->type);
		$p->properties = $this->properties;
		$p->setData($this->data);
		$return[] = $p;

		return $return;
	}

}




/**
 * Manage working and non-working hours
 * browse periods with working hours and non-working days
 */
class bab_userWorkingHours {

	var $begin;
	var $end;
	var $periods;
	var $boundaries;
	var $sibling;
	var $options;
	var $id_users;
	var $id_calendars;

	/**
	 * category filter for calendar events
	 * @public
	 * array|int|NULL
	 */
	var $category = NULL; 

	/**
	 * Working hours object on period
	 * for the current user
	 * parameters are instance of BAB_DateTime
	 *
	 * @param array|false	$id_user
	 * @param object		$begin
	 * @param object		$end
	 * @param int			$options
	 *
	 */
	function bab_userWorkingHours($begin, $end) {		
		$db = $GLOBALS['babDB'];
		
		$this->begin		= $begin;
		$this->end			= $end;
		$this->periods		= array();
		$this->boundaries	= array();
		$this->sibling		= array();
		$this->id_users		= false;
		$this->id_calendars	= false;
	}


	function addIdUser($id_user) {
		$this->id_users[$id_user] = $id_user;
	}


	function addCalendar($id_cal) {
		$this->id_calendars[$id_cal] = $id_cal;
	}


	function createPeriods($options) {
		$this->options = $options;

		if (BAB_PERIOD_NWDAY === ($this->options & BAB_PERIOD_NWDAY) && $this->id_users) {
			include_once $GLOBALS['babInstallPath']."utilit/nwdaysincl.php";
		}

		if (BAB_PERIOD_VACATION === ($this->options & BAB_PERIOD_VACATION) && $this->id_users) {
			include_once $GLOBALS['babInstallPath']."utilit/vacincl.php";
			bab_vac_setVacationPeriods($this, $this->id_users, $this->begin, $this->end);
		}

		if (BAB_PERIOD_CALEVENT === ($this->options & BAB_PERIOD_CALEVENT) && $this->id_calendars) {
			include_once $GLOBALS['babInstallPath']."utilit/calincl.php";
			bab_cal_setEventsPeriods($this, $this->id_calendars, $this->begin, $this->end, $this->category);
		}

		if (BAB_PERIOD_TSKMGR === ($this->options & BAB_PERIOD_TSKMGR) && $this->id_users) {
			include_once $GLOBALS['babInstallPath']."utilit/tmdefines.php";
			include_once $GLOBALS['babInstallPath']."utilit/tmIncl.php";
			bab_tskmgr_setPeriods($this, $this->id_users, $this->begin, $this->end);
		}

		$loop = $this->begin->cloneDate();
		$endts = $this->end->getTimeStamp();
		$begints = $this->begin->getTimeStamp();
		$nworking = (BAB_PERIOD_NONWORKING === ($this->options & BAB_PERIOD_NONWORKING));
		$previous_end = NULL;


		

		while ($loop->getTimeStamp() < $endts) {
			
			if (BAB_PERIOD_NWDAY === ($this->options & BAB_PERIOD_NWDAY) && $this->id_users) {
				
				$nwLabel = bab_getNonWorkingDayLabel($loop);
				if (false !== $nwLabel) {
					$beginDate	= BAB_DateTime::fromIsoDateTime($loop->getIsoDate().' 00:00:00');
					$endDate	= $beginDate->cloneDate();
					$endDate->add(1, BAB_DATETIME_DAY);

					$p = & $this->setUserPeriod(false, $beginDate, $endDate, BAB_PERIOD_NWDAY);
					$p->setProperty('SUMMARY'		,bab_translate('Non-working day2'));
					$p->setProperty('DESCRIPTION'	,$nwLabel);
					$p->setProperty('DTSTART'		,$beginDate->getIsoDateTime());
					$p->setProperty('DTEND'			,$endDate->getIsoDateTime());
				}
			}
			

			if (BAB_PERIOD_WORKING === ($this->options & BAB_PERIOD_WORKING) && $this->id_users) {

				$arr = bab_getWHours($id_user, $loop->getDayOfWeek());

				foreach($arr as $h) {
					$startHour	= explode(':', $h['startHour']);
					$endHour	= explode(':', $h['endHour']);
					
					$beginDate = new BAB_DateTime(
						$loop->getYear(),
						$loop->getMonth(),
						$loop->getDayOfMonth(),
						$startHour[0],
						$startHour[1],
						$startHour[2]
						);

					$endDate = new BAB_DateTime(
						$loop->getYear(),
						$loop->getMonth(),
						$loop->getDayOfMonth(),
						$endHour[0], 
						$endHour[1], 
						$endHour[2]
						);

					if ($nworking && NULL == $previous_end) {
						$previous_end = $this->begin; // reference
					}

					// add non-working period between 2 working period and at the begining
					if ($nworking && $beginDate->getTimeStamp() > $previous_end->getTimeStamp()) {

						$p = & $this->setUserPeriod(false, $previous_end, $beginDate, BAB_PERIOD_NONWORKING);
						$p->setProperty('SUMMARY'		, bab_translate('Non-working period'));
						$p->setProperty('DTSTART'		, $previous_end->getIsoDateTime());
						$p->setProperty('DTEND'			, $beginDate->getIsoDateTime());
					}

					$p = & $this->setUserPeriod(false, $beginDate, $endDate, BAB_PERIOD_WORKING);

					$p->setProperty('SUMMARY'		, bab_translate('Working period'));
					$p->setProperty('DTSTART'		, $beginDate->getIsoDateTime());
					$p->setProperty('DTEND'			, $endDate->getIsoDateTime());

					$previous_end = $endDate; // the begin date of the non-working period will be a reference to the enddate of the working period
				}
			}
			$loop->add(1, BAB_DATETIME_DAY);
		}

		// add final non-working period
		if ($nworking && $this->end->getTimeStamp() > $previous_end->getTimeStamp()) {

			$p = & $this->setUserPeriod(false, $previous_end, $this->end, BAB_PERIOD_NONWORKING);
			$p->setProperty('SUMMARY'		, bab_translate('Non-working period'));
			$p->setProperty('DTSTART'		, $previous_end->getIsoDateTime());
			$p->setProperty('DTEND'			, $this->end->getIsoDateTime());
		}
	}


	/**
	 * Order boundaries by date
	 * and index siblings
	 */
	function orderBoundaries() {
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
	 * @param	int|false	$id_user
	 * @param	object		$beginDate
	 * @param	object		$endDate
	 * @param	int			$type
	 * @return	object
	 */
	function & setUserPeriod($id_user, $beginDate, $endDate, $type) {
		
		$p = & new bab_calendarPeriod($beginDate->getTimeStamp(), $endDate->getTimeStamp(), $type);
		if (false !== $id_user) {
			$uid = & $p->getProperty('UID');
			$uid .= '.'.$id_user;
		}
		
		$this->addPeriod($p);
		return $p;
	}

	
	function addPeriod(&$p) {
		$this->periods[] = &$p;

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
	function getBeginDate() {
		prev($this->boundaries);
		$ts = key($this->boundaries);
		next($this->boundaries);
		return $ts; 
	}

	/**
	 * @private
	 */
	function getEndDate() {
		return key($this->boundaries)-1;
	}

	/**
	 * @private
	 */
	function createUsersPeriods($begin, $end, $type) {
		$arr = array();
		foreach($this->id_users as $id_user) {
			$p = new bab_calendarPeriod($begin, $end, $type);
			$uid = & $p->getProperty('UID');
			$uid .= '.'.$id_user;
			$arr[] = $p;
		}

		return $arr;
	}

	/**
	 * Get next period
	 */
	function getNextPeriod() {
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
	 * @param int $filter : events types to get
	 *
	 * @return	object
	 */
	function getNextEvent($filter) {
		static $events = NULL;

		if (NULL === $events) {
			$events = & $this->getEventsBetween($this->begin->getTimeStamp(), $this->end->getTimeStamp(), $filter);
		}

		if (list(,$event) = each($events)) {
			return $event;
		}
		$events = NULL;
		return false;
	}


	/**
	 * 
	 *
	 * @param	int		$start		timestamp
	 * @param	int		$end		timestamp
	 * @param	int		$filter		: events types to get
	 * @return	array
	 */
	function getEventsBetween($start, $end, $filter) {
		reset($this->boundaries);

		$r = array();

		foreach($this->boundaries as $ts => $events) {
			if ($ts > $end) {
				break;
			}
				
			foreach($this->boundaries[$ts] as $event) {
				if ($event->ts_end > $start && $event->ts_begin < $end && $event->type === ($filter & $event->type)) {
					$r[$event->getProperty('UID')] = $event;
				}
			}
		}
		return $r;
	}

	/**
	 * @param	int		$available		: available events types
	 * @param	int		$nonavailable	: non-available events types
	 */
	function getAvailability($available, $nonavailable) {
		reset($this->boundaries);
		$previous = NULL;
		$periods = array();

		foreach($this->boundaries as $ts => $events) {

			$current = NULL;
			
			foreach($events as $event) {
				if ($event->type === ($available & $event->type) && 0 !== $current) {
					$current = 1;
				}

				if ($event->type === ($nonavailable & $event->type)) {
					$current = 0;
				}
			}

			

			if (1 !== $current && NULL !== $previous) {
				if (!isset($periods[$previous.'.'.$ts])) {
					$periods[$previous.'.'.$ts] = new bab_calendarPeriod($previous, $ts, BAB_PERIOD_NONWORKING);
				}
				$previous = NULL;
			}

			if (1 === $current && NULL === $previous) {
				$previous = $ts;
			}
		}

		return $periods;
	}


	/**
	 * @param	int		$start	timestamp
	 * @param	int		$end	timestamp
	 * @param	int		$gap	minimum event duration in seconds
	 */
	function getAvailabilityBetween($start, $end, $gap) {
		static $availability = NULL;

		if (NULL === $availability) {
			$availability = $this->getAvailability(BAB_PERIOD_WORKING, 
				BAB_PERIOD_NWDAY | BAB_PERIOD_VACATION | BAB_PERIOD_CALEVENT);
			
		} else {
			reset($availability);
		}

		

		$r = array();

		foreach($availability as $event) {
			if ($event->ts_begin > $end) {
				break;
			}
				

			if ($event->ts_end > $start && $event->ts_begin < $end && ($event->ts_end - $event->ts_begin) > $gap) {
				$r[] = $event;
			}
		}
		
		return $r;
	}
}


?>