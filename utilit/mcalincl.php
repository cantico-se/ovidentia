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

/**
* @internal SEC1 PR 20/02/2007 FULL
*/

include_once 'base.php';

define('BAB_CAL_NAME_LENGTH', 18);
define('BAB_CAL_EVENT_LENGTH', 18);

class bab_mcalendars
{
	var $categories;
	var $freeevents;
	var $idcals = array();
	var $objcals = array();
	var $idxcat = 0;

	public function __construct($startdate, $enddate, $idcals)
		{
		$this->idcals = $idcals;
		for( $i = 0; $i < count($this->idcals); $i++ )
			{
			$this->objcals[$this->idcals[$i]] =new bab_icalendar($startdate, $enddate, $this->idcals[$i]);
			}
		}

	public function getCalendarName($idcal)
		{
		if( isset($this->objcals[$idcal]->cal_name) )
			{
			return $this->objcals[$idcal]->cal_name;
			}
		return "";
		}

	public function getCalendarType($idcal)
		{
		if( isset($this->objcals[$idcal]) )
			{
			return $this->objcals[$idcal]->cal_type;
			}
		return "";
		}

	

	public function getCalendarAccess($idcal)
		{
		if( isset($this->objcals[$idcal]) )
			{
			return $this->objcals[$idcal]->access;
			}
		return "";
		}

	public function getNextEvent($idcal, $startdate, $enddate, &$arr)
		{
		if( isset($this->objcals[$idcal]) )
			{
			return $this->objcals[$idcal]->getNextEvent($startdate, $enddate, $arr);
			}
		else
			{
			return false;
			}
		}

	public function getEvents($idcal, $startdate, $enddate, &$arr)
		{
			
		if( isset($this->objcals[$idcal]) )
			{
			return $this->objcals[$idcal]->getEvents($startdate, $enddate, $arr);
			}
		else
			{
			return 0;
			}
		}


	public function getHtmlArea($idcal, $startdate, $enddate, &$harray)
		{
		if( isset($this->objcals[$idcal]) )
			{
			return $this->objcals[$idcal]->getHtmlArea($startdate, $enddate, $harray);
			}
		else
			{
			return 0;
			}
		}


	public function enumCategories()
		{
		$this->idxcat = 0;
		$this->loadCategories();
		}

	public function getNextCategory(&$arr)
		{
		if( $this->idxcat < count($this->categories))
			{
			$arr = $this->categories[$this->idxcat];
			$this->idxcat++;
			return true;
			}
		else
			{
			$this->idxcat = 0;
			return false;
			}
		}
	
	public function loadCategories()
		{
		global $babDB;
		static $bload = false;
		if( !$bload )
			{
			$res = $babDB->db_query("select * from ".BAB_CAL_CATEGORIES_TBL." order by name");
			while( $arr = $babDB->db_fetch_array($res))
				{
				$this->categories[$arr['id']] = array(
					'name' => $arr['name'], 
					'description' => $arr['description'],
					'bgcolor' => $arr['bgcolor']
					);
				}
			}
		}

	public function getCategoryColor($idcat)
		{
		$this->loadCategories();

		if( isset($this->categories[$idcat]))
			{
			return $this->categories[$idcat]['bgcolor'];
			}
		return "";
		}

	public function getCategoryName($idcat)
		{
		$this->loadCategories();

		if( isset($this->categories[$idcat]))
			{
			return $this->categories[$idcat]['name'];
			}
		return "";
		}

	public function getCategoryDescription($idcat)
		{
		$this->loadCategories();

		if( isset($this->categories[$idcat]))
			{
			return $this->categories[$idcat]['description'];
			}
		return "";
		}
		
		
		
	/**
	 * Get a list of calendars associated to the event
	 * @param	bab_calendarPeriod	$calPeriod
	 * @return 	array
	 */
	public function getEventCalendars(bab_calendarPeriod $calPeriod) {
	
		$cals = array();
		
		$collection = $calPeriod->getCollection();
		
		if (!isset($collection))
		{
			throw new Exception('the period is not linked to a collection');
			return;
		}
		
		/*@var $collection bab_PeriodCollection */
		
		
		$calendar = $collection->getCalendar();
		
		if (!isset($calendar))
		{
			return array();
		}
		
		
		$cals[$calendar->getUrlIdentifier()] = array(
			'name' => $calendar->getName(), 
			'type' => $calendar->getType()
		);

		// ovidentia calendar specific
		
		$arr = $calPeriod->getData();
		
		$allCalendars = bab_getICalendars()->getCalendars();
		
		if (isset($arr['idcal_owners'])) {
			foreach($arr['idcal_owners'] as $urlIdentifier) {	
				
				if (isset($allCalendars[$urlIdentifier])) {
					
					$calendar = $allCalendars[$urlIdentifier];
				
					$cals[$urlIdentifier] = array(
						'name' => $calendar->getName(), 
						'type' => $calendar->getType()
					);
				}
			}
		}
		
		return $cals;
	
	}
		
		
		

	/**
	 * Create events object
	 * for all calendars
	 * 
	 * @param	string	$startdate	ISO datetime
	 * @param	string	$enddate	ISO datetime
	 * @param	array	$idcals		list of url identifier of calendars
	 * 
	 * @return	bab_UserPeriods
	 */
	public static function create_events($startdate, $enddate, $idcals) {
		include_once $GLOBALS['babInstallPath']."utilit/workinghoursincl.php";
		include_once $GLOBALS['babInstallPath']."utilit/dateTime.php";

		$whObj = new bab_UserPeriods(
			BAB_dateTime::fromIsoDateTime($startdate), 
			BAB_dateTime::fromIsoDateTime($enddate)
		);
		
		$factory = bab_getInstance('bab_PeriodCriteriaFactory');

		$criteria = $factory->Collection(
			array(
				'bab_NonWorkingDaysCollection', 
				'bab_WorkingPeriodCollection', 
				'bab_VacationPeriodCollection', 
				'bab_CalendarEventCollection'
			)
		);
		
		foreach($idcals as $idcal) {
			$calendar = bab_getICalendars()->getEventCalendar($idcal);
			if (!$calendar)
			{
				throw new Exception('Calendar not found for identifier : '.$idcal);
			}
			$criteria = $criteria->_AND_($factory->Calendar($calendar));
		}

		$whObj->createPeriods($criteria);
		$whObj->orderBoundaries();
		
		return $whObj;
	}
	

	/**
	 * $arr = array(
	 *			0 => ISO date
	 *			1 => ISO date
	 *			2 => 1|0
	 *			)
	 *
	 * if $arr[2] == 0, this is a free event
	 *
	 * @param bab_UserPeriods	$whObj		
	 * @param string			$startdate	ISO date
	 * @param string			$startdate	ISO date
	 * @param array				$arr		reference for event
	 * @param int				$gap		minimum event duration in seconds
	 * 
	 * @return bool
	 */
	public static function getNextFreeEvent($whObj, $startdate, $enddate, &$arr, $gap=0)
		{
		
		static $freeevents = array();
		if (!isset($freeevents[$startdate.$enddate])) {
			$freeevents[$startdate.$enddate] = $whObj->getAvailabilityBetween(bab_mktime($startdate), bab_mktime($enddate), $gap);
		}

		if (list(,$event) = each($freeevents[$startdate.$enddate])) {
			$arr = array(
					0 => date('Y-m-d H:i:s',$event->ts_begin),
					1 => date('Y-m-d H:i:s',$event->ts_end),
					2 => 0
				);

			return true;
		}
		
		return false;
	}

}




/**
 * Query a calendar between two dates
 */
class bab_icalendar
{
	
	public $cal_name;
	
	public $access = BAB_CAL_ACCESS_VIEW;
	
	var $whObj;	// working hours object
	
	/**
	 * @var bab_EventCalendar
	 */
	private $calendar;

	/**
	 * @param string	$startdate
	 * @param string	$enddate
	 * @param string	$calid
	 */
	public function __construct($startdate, $enddate, $calid)
		{
		global $babBody, $babDB;

		
		require_once dirname(__FILE__).'/cal.userperiods.class.php';
		include_once $GLOBALS['babInstallPath']."utilit/dateTime.php";

		$this->calendar = bab_getICalendars()->getEventCalendar($calid);
		$this->cal_name = $this->calendar->getName();
		

		$this->whObj = new bab_UserPeriods(
			BAB_dateTime::fromIsoDateTime($startdate), 
			BAB_dateTime::fromIsoDateTime($enddate)
		);
		
		
		if ($this->calendar->canAddEvent()) {
			$this->access = BAB_CAL_ACCESS_FULL;
		} 

		$factory = bab_getInstance('bab_PeriodCriteriaFactory');
		
		$this->whObj->createPeriods(
			$factory->Calendar($this->calendar)
			->_AND_($factory->Collection(array(
				'bab_NonWorkingDaysCollection', 
				'bab_WorkingPeriodCollection', 
				'bab_VacationPeriodCollection', 
				'bab_CalendarEventCollection'
			)))
		);
		
		$this->whObj->orderBoundaries();
	}

	/**
	 * @param	string	$startdate	ISO date time
	 * @param	string	$enddate	ISO date time
	 * @param	object	$calPeriod
	 * @return	boolean
	 */
	public function getNextEvent($startdate, $enddate, &$calPeriod)
		{
		while( $p = & $this->whObj->getNextEvent(array('bab_NonWorkingDaysCollection', 'bab_VacationPeriodCollection', 'bab_CalendarEventCollection')) )
			{
			if (bab_mktime($startdate) < $p->ts_end && bab_mktime($enddate) > $p->ts_begin )
				{
				$calPeriod = $p;
				return true;
				}
			}
			
		
		return false;
		}
	

	/**
	 * @param	string	$startdate	ISO date time
	 * @param	string	$enddate	ISO date time
	 * @param	array	$arr
	 * @return	int
	 */
	public function getEvents($startdate, $enddate, &$arr)
		{
			
		$arr = array();
		$events = $this->whObj->getEventsBetween(bab_mktime($startdate), bab_mktime($enddate), array('bab_NonWorkingDaysCollection', 'bab_VacationPeriodCollection', 'bab_CalendarEventCollection'));

			foreach($events as $event) {
				$collection = $event->getCollection();
				
				$calendar = $collection->getCalendar();
				
				if (!$calendar || $this->calendar->getUrlIdentifier() === $calendar->getUrlIdentifier())
				{
					$arr[] = $event;
				}
			}
			
		return count($arr);
		}


	/**
	 * 
	 *
	 * @param	string	$startdate	ISO date time
	 * @param	string	$enddate	ISO date time
	 * @param	array	&$harray
	 *
	 * @return int
	 */
	public function getHtmlArea($startdate, $enddate, &$harray)
		{
		
		$calPeriod = NULL;
		$harray = array();
		
		while( $this->getNextEvent($startdate, $enddate, $calPeriod))
			{
			
			$done = false;
			for( $k = 0; $k < count($harray); $k++ )
				{
				
				$append = true;
				for( $m = 0; $m < count($harray[$k]); $m++ )
					{
					if( 
						$harray[$k][$m]->getProperty('DTEND') > $calPeriod->getProperty('DTSTART') && 
						$harray[$k][$m]->getProperty('DTSTART') < $calPeriod->getProperty('DTEND') )
						{
						$append = false;
						break;
						}
					}

				if( $append )
					{
					$done = true;
				
					$harray[$k][] = $calPeriod;
					}
				}
			if( !$done)
				{
				$harray[][] = $calPeriod;
				}
			}

		return count($harray);
		}
}






class cal_wmdbaseCls
{
	/**
	 * Array of reference parts used in url ex "type/id"
	 * @var array	<string>
	 */
	public $idcals;
	
	
	public function __construct($tg, $idx, $calids, $date)
	{
		global $babBody;

		$this->currentview = 'viewm';
		$this->currentidcals = $calids;
		$this->currentdate = $date;
		$this->idcals = explode(",", $calids);
		$rr = explode(',', $date);
		if (3 === count($rr)) {
			$this->year = (int) $rr[0];
			$this->month = (int) $rr[1];
			$this->day = (int) $rr[2];
		} else {
			$this->year = (int) date('Y');
			$this->month = (int) date('n');
			$this->day = (int) date('j');
		}
		$this->print = isset($_GET['print']) && $_GET['print'] == 1;
		$this->multical = count($this->idcals) > 1;

		$this->allow_create = false;
		$this->allow_modify = false;
		$this->allow_view = false;
		$this->allow_viewtitle = true;
		$this->updateCreateAccess();


		$this->commonurl = $GLOBALS['babUrlScript']."?tg=".$tg."&idx=".$idx."&calid=".$this->currentidcals;
		
		$bprint = bab_rp('print',false);
		if (false !== $bprint) {
			$this->commonurl .= '&print=1';
		}

		$time = mktime( 0,0,0, $this->month-1, $this->day, $this->year);
		$tmp = mktime( 0,0,0, $this->month-1, 1, $this->year);
		$m = date("t", $tmp) < $this->day ? date("n", $tmp) : date("n", $time);
		$j = date("t", $tmp) < $this->day ? date("t", $tmp) : date("j", $time);
		$this->previousmonthurl = bab_toHtml($this->commonurl."&date=".date("Y", $time).",".$m.",".$j);

		$time = mktime( 0,0,0, $this->month+1, $this->day, $this->year);
		$tmp = mktime( 0,0,0, $this->month+1, 1, $this->year);
		$m = date("t", $tmp) < $this->day ? date("n", $tmp) : date("n", $time);
		$j = date("t", $tmp) < $this->day ? date("t", $tmp) : date("j", $time);
		$this->nextmonthurl = bab_toHtml($this->commonurl."&date=".date("Y", $time).",".$m.",".$j);

		$time = mktime( 0,0,0, $this->month, $this->day, $this->year-1);
		$this->previousyearurl = bab_toHtml($this->commonurl."&date=".date("Y", $time).",".date("n", $time).",".date("j", $time));

		$time = mktime( 0,0,0, $this->month, $this->day, $this->year+1);
		$this->nextyearurl = bab_toHtml($this->commonurl."&date=".date("Y", $time).",".date("n", $time).",".date("j", $time));

		$time = mktime( 0,0,0, $this->month, $this->day -7, $this->year);
		$this->previousweekurl = bab_toHtml($this->commonurl."&date=".date("Y", $time).",".date("n", $time).",".date("j", $time));

		$time = mktime( 0,0,0, $this->month, $this->day +7, $this->year);
		$this->nextweekurl = bab_toHtml($this->commonurl."&date=".date("Y", $time).",".date("n", $time).",".date("j", $time));

		$time = mktime( 0,0,0, $this->month, $this->day -1, $this->year);
		$this->previousdayurl = bab_toHtml($this->commonurl."&date=".date("Y", $time).",".date("n", $time).",".date("j", $time));

		$time = mktime( 0,0,0, $this->month, $this->day +1, $this->year);
		$this->nextdayurl = bab_toHtml($this->commonurl."&date=".date("Y", $time).",".date("n", $time).",".date("j", $time));

		$this->gotodateurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=month&year=".date('Y')."&month=".date('n')."&callback=gotodate");

		switch($tg)
		{
			case "calmonth":
				$this->monthurl = "";
				$this->dayurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=calday&idx=".$idx."&calid=".$this->currentidcals."&date=".$date);
				$this->weekurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=calweek&idx=".$idx."&calid=".$this->currentidcals."&date=".$date);
				$this->currentview = 'viewm';
				if (false !== $bprint) {
					$this->weekurl .= '&print=1';
					$this->dayurl .= '&print=1';
				}
				break;
			case "calday":
				$this->monthurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=calmonth&idx=".$idx."&calid=".$this->currentidcals."&date=".$date);
				$this->dayurl = "";
				$this->weekurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=calweek&idx=".$idx."&calid=".$this->currentidcals."&date=".$date);
				$this->currentview = 'viewd';
				if (false !== $bprint) {
					$this->weekurl .= '&print=1';
					$this->monthurl .= '&print=1';
				}
				break;
			case "calweek":
				$this->monthurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=calmonth&idx=".$idx."&calid=".$this->currentidcals."&date=".$date);
				$this->dayurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=calday&idx=".$idx."&calid=".$this->currentidcals."&date=".$date);
				$this->weekurl = "";
				$this->currentview = 'viewq';
				if (false !== $bprint) {
					$this->dayurl .= '&print=1';
					$this->monthurl .= '&print=1';
				}
				break;
		}

		$this->monthurlname = bab_translate("Month");
		$this->weekurlname = bab_translate("Week");
		$this->dayurlname = bab_translate("Day");
		$this->gotodatename = bab_translate("Go to date");
		$this->attendeestxt = bab_translate("Attendees");
		$this->statustxt = bab_translate("Waiting event");
		$this->notestxt = bab_translate("Notes");
		$this->t_calendarchoice = bab_translate("Calendars");
		$this->t_date_from = bab_translate("date_from");
		$this->t_date_to = bab_translate("date_to");
		$this->t_category = bab_translate("Category");
		$this->t_prev_day = bab_translate("Previous day");
		$this->t_prev_week = bab_translate("Previous week");
		$this->t_prev_month = bab_translate("Previous month");
		$this->t_prev_year = bab_translate("Previous year");
		$this->t_next_year = bab_translate("Next year");
		$this->t_next_month = bab_translate("Next month");
		$this->t_next_week = bab_translate("Next week");
		$this->t_next_day = bab_translate("Next day");
		$this->t_new_event = bab_translate("New event");
		$this->t_day_view = bab_translate("Day view");
		$this->t_creator = bab_translate("Author");
		$this->t_print_friendly = bab_translate("Print Friendly");
		$this->t_print = bab_translate("Print");
		$this->t_view_event = bab_translate("View event");
		$this->t_modify_event = bab_translate("Modify event");
		$this->t_search = bab_translate("Search");
		$this->t_eventlist = bab_translate("Detailed sight");
		$this->t_catlist = bab_translate("Categories");
		$this->t_note = bab_translate("Personal note");
		$this->t_location = bab_translate("Location");
		$this->t_alert = bab_translate("Reminder");
		$this->t_notifier = bab_translate("Open notifier");


		$backurl = "?tg=".$tg."&date=".$date."&calid=";
		$this->calendarchoiceurl = bab_toHtml( $GLOBALS['babUrlScript']."?tg=calopt&idx=pop_calendarchoice&calid=".$this->currentidcals."&date=".$date."&backurl=".urlencode($backurl));
		$this->searcheventurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=".$tg."&idx=rfree&date=".$date."&calid=".$this->currentidcals);
		$this->calnotifierurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=calnotif&idx=popup");
		$this->printurl = bab_toHtml($this->commonurl.'&date='.$date.'&print=1');
	}


	/**
	 * Get access shared users for a personal calendars
	 * @param	int		$id_cal
	 * @param	int		$access_type
	 *		possibles types are :
	 *			<ul>
	 *				<li>BAB_CAL_ACCESS_SHARED_UPDATE</li>
	 *				<li>BAB_CAL_ACCESS_SHARED_FULL</li>
	 *			</ul>
	 *
	 * @return array	keys and values are (int) id_user
	 */
	function getAccessShared($id_cal, $access_type) {
		global $babDB;
		$access_shared = array();
		

		$rs = $babDB->db_query("
			select 
				cut.id_user, bwrite 
			from 
				".BAB_CALACCESS_USERS_TBL." cut 
			where 
				id_cal='".$babDB->db_escape_string($id_cal)."' 
				AND bwrite=".$babDB->quote($access_type)."
		");

		while( $row =  $babDB->db_fetch_assoc($rs))
		{
			$access_shared[$row['id_user']] = $row['id_user'];
		}

		return $access_shared;
	}





	/**
	 * Update access rights for an event and a calendar
	 * 
	 * @param  	bab_CalendarPeriod	$calPeriod		period (event) informations
	 * @param	bab_EventCalendar	$calendar		calendar associated to event
	 * @param	array				&$result		result may contain access rights informations for other calendars on the same event
	 */
	function updateAccessCalendar(bab_CalendarPeriod $calPeriod, bab_EventCalendar $calendar, &$result)
	{
		global $babBody;
		
		$view = 1;
		$modify = (int) $calendar->canUpdateEvent($calPeriod);
		$viewtitle = (int) $calendar->canViewEventDetails($calPeriod);
		
	
		$result['view'][] = $view;
		$result['modify'][] = $modify;
		$result['viewtitle'][] = $viewtitle;
	
	}

	
	/**
	 * Set access rights for one event and one calendar
	 * @param 	bab_CalendarPeriod	$calPeriod		Event infos
	 * @return null
	 */
	function updateAccess(bab_CalendarPeriod $calPeriod)
	{
		global $babBody;
		
		$periodCollection = $calPeriod->getCollection();
		
		if (!$periodCollection)
		{
			throw new Exception('calendar period without collection');
		}
		
		$calendar = $periodCollection->getCalendar();
		
		if (!$calendar)
		{
			// ignore event, no calendar
			return;
		}

		$this->allow_view		= true;
		$this->allow_modify		= true;
		$this->allow_viewtitle	= true;
		$this->bstatus			= false;

		$result['view']			= array();
		$result['modify']		= array();
		$result['viewtitle']	= array();
		$this->updateAccessCalendar($calPeriod, $calendar, $result);

		
		if ($calendar instanceof bab_OviEventCalendar) {
			$evtarr = $calPeriod->getData();
			$calendars = bab_getICalendars();
			
			$linked_calendars = $calPeriod->getRelations('CHILD');
			
			if( $linked_calendars && $result['modify'][0] && $calendar instanceof bab_PersonalCalendar )
			{
				foreach($linked_calendars as $othercal)
				{
					$this->updateAccessCalendar($calPeriod, $othercal, $result);
				}
			}
		}

		if( in_array(0, $result['view']) )
			{
			$this->allow_view = false;
			}

		if( in_array(0, $result['modify']) )
			{
			$this->allow_modify = false;
			}

		if( in_array(0, $result['viewtitle']) )
			{
			$this->allow_viewtitle = false;
			}

		if( $evtarr['status'] == BAB_CAL_STATUS_NONE )
			{
			$this->bstatus = true;
			}
		else
			{
			$this->bstatus = false;
			}
	
	}

	/**
	 * 
	 * @return unknown_type
	 */
	function updateCreateAccess()
	{
		global $babBody;
		foreach ($this->idcals as $cal)
		{
			$calendar = bab_getICalendars()->getEventCalendar($cal);
			if ($calendar && $calendar->canAddEvent()) {
				$this->allow_create = true;
				return;
			}
		}
	}

	function calstr($str,$n = BAB_CAL_EVENT_LENGTH)
		{	
		return bab_toHtml(bab_abbr($str, BAB_ABBR_FULL_WORDS, $n));
		}

	function createCommonEventVars(bab_CalendarPeriod $calPeriod)
		{
			
		$collection = $calPeriod->getCollection();
			
		if (!($collection instanceof bab_CalendarEventCollection)) {
			$this->properties = '';
		} else {
			$el = array();

			if ('PUBLIC' !== $calPeriod->getProperty('CLASS')) {
				$el[] = bab_translate('Private');
			}

			$arr = $calPeriod->getData();

			if ('Y' == $arr['block']) {
				$el[] = bab_translate('Locked');
			}

			if ('TRANSPARENT' == $calPeriod->getProperty('TRANSP')) {
				$el[] = bab_translate('Free');
			}

			$this->t_option = count($el) > 1 ? bab_translate("Options") : bab_translate("Option"); 
			if (count($el) > 0) {
				$this->properties = bab_toHtml(implode(', ',$el));
				}
			else {
				$this->properties = '';
				}
		}
		
		global $babBody;

		$arr = $calPeriod->getData();

		$this->idcal		= 0;
		
		if ($collection && $calendar = $collection->getCalendar()) {
			$this->idcal	= $calendar->getUrlIdentifier();
		}
		
		$collectionparameter = '';
		if ($collection) {
			$collectionparameter = get_class($collection);
		}
		
		$this->status		= isset($arr['status'])		? $arr['status'] 		: 0;
		$this->id_cat		= bab_getCalendarCategory($calPeriod->getProperty('CATEGORIES'));
		$this->id_creator	= isset($arr['id_creator']) ? $arr['id_creator'] 	: 0;
		$this->hash			= isset($arr['hash'])		? $arr['hash'] 			: '';
		$this->balert		= $calPeriod->getAlarm();
		$this->nbowners		= isset($arr['nbowners'])	? $arr['nbowners'] 		: 0;
		$this->idevent		= $calPeriod->getUrlIdentifier();
		$this->bgcolor		= $calPeriod->getColor();
		
		$this->viewurl		= isset($arr['viewurl'])	? $arr['viewurl'] : null;

		if( $this->id_creator != 0 )
			{
			$this->creatorname = bab_toHtml(bab_getUserName($this->id_creator)); 
			}
		
		$this->updateAccess($calPeriod);

		$this->category = bab_toHtml($calPeriod->getProperty('CATEGORIES'));


		if (bab_getICalendars()->usebgcolor == 'Y' && !empty($calPeriod->color)) {
			$this->bgcolor = $calPeriod->color;
		}

		
		$time = bab_mktime($calPeriod->getProperty('DTSTART'));
		$this->starttime = bab_toHtml(bab_time($time));
		$this->startdate = bab_toHtml(bab_shortDate($time, false));
		$time = bab_mktime($calPeriod->getProperty('DTEND'));
		$this->endtime = bab_toHtml(bab_time($time));
		$this->enddate =  bab_toHtml(bab_shortDate($time, false));
		
		
		
		if( !$this->allow_viewtitle  )
			{
			$this->title		= bab_toHtml(bab_translate("Private"));
			$this->titleten		= $this->title;
			$this->description	= "";
			$this->location		= "";
			}
		else
			{
			$this->title		= bab_toHtml($calPeriod->getProperty('SUMMARY'));
			$this->titleten		= $this->calstr($calPeriod->getProperty('SUMMARY'));
			$this->location		= bab_toHtml($calPeriod->getProperty('LOCATION'));
			$this->description	= $calPeriod->getProperty('DESCRIPTION');
			}

		if( $this->allow_modify )
			{
			$this->popup		= true;
			$this->titletenurl	= bab_toHtml($GLOBALS['babUrlScript']."?tg=event&idx=modevent&collection=".$collectionparameter."&evtid=".$this->idevent."&calid=".$this->idcal."&cci=".$this->currentidcals."&view=".$this->currentview."&date=".$this->currentdate);
			}
		elseif( $this->allow_view )
			{
			$this->popup		= true;
			$this->titletenurl	= bab_toHtml($GLOBALS['babUrlScript']."?tg=calendar&idx=veventupd&collection=".$collectionparameter."&evtid=". $this->idevent	."&idcal=".$this->idcal);
			}
		else
			{
			$this->popup		= false;
			$this->titletenurl	= "";
			}
		$this->attendeesurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=calendar&idx=attendees&collection=".$collectionparameter."&evtid=".$this->idevent ."&idcal=".$this->idcal);
		$this->vieweventurl = isset($arr['viewurl']) ? bab_toHtml($arr['viewurl']) : bab_toHtml($GLOBALS['babUrlScript']."?tg=calendar&idx=veventupd&evtid=".$this->idevent ."&idcal=".$this->idcal);
		$this->link = isset($arr['viewinsamewindow'])? $arr['viewinsamewindow']: false;
		$this->bnote = false;
		if( isset($arr['note']) && !empty($arr['note']))
			{
			$this->bnote = true;
			$this->noteval = bab_toHtml($arr['note']);
			}
		}

	function printout($file,$template)
		{
		global $babBody;

		$html = bab_printTemplate($this,$file,$template);

		if ($this->print)
			{
			include_once $GLOBALS['babInstallPath']."utilit/uiutil.php";
			$GLOBALS['babBodyPopup'] = new babBodyPopup();
			$GLOBALS['babBodyPopup']->addStyleSheet('calendar.css');
			$GLOBALS['babBodyPopup']->title = $babBody->title;
			$GLOBALS['babBodyPopup']->msgerror = $babBody->msgerror;
			$GLOBALS['babBodyPopup']->babecho($html);
			printBabBodyPopup();
			die();
			}
		else
			{
			$GLOBALS['babBody']->addStyleSheet('calendar.css');
			$babBody->babecho($html);
			}
		}
}

/**
 * Calendar selection interface
 * @param	string	$formname
 * @param	array	[$selected_calendars]
 * @return 	string
 */
function calendarchoice($formname, $selected_calendars = NULL)
{
class calendarchoice
	{
	private $caltypes = array();
	
	private $calfromtype;
		
		
	var $approb = array();

	function calendarchoice($formname, $selected_calendars)
		{
		global $babBody, $babDB;
		$this->formname = $formname;
		$icalendars = bab_getICalendars();
		
		if (isset($_POST['selected_calendars']))
			{
			$this->selectedCalendars = $_POST['selected_calendars'];
			}
		elseif (NULL !== $selected_calendars) 
			{
			$this->selectedCalendars = $selected_calendars;
			}
		else
			{
			$this->selectedCalendars = !empty($_REQUEST['calid']) ? explode(',',$_REQUEST['calid']) : (isset($icalendars->user_calendarids) ? explode(',',$icalendars->user_calendarids) : array());
			}

		$this->usrcalendarstxt = bab_translate("Users");
		$this->grpcalendarstxt = bab_translate("Publics");
		$this->rescalendarstxt = bab_translate("Resources");
		$this->t_goright = bab_translate("Push right");
		$this->t_goleft = bab_translate("Push left");
		$this->t_calendars1 = bab_translate("Available calendars");
		$this->t_calendars2 = bab_translate("Selected calendars");
		$this->js_calnum = bab_translate("You must select one calendar");
		

		$calendars = $icalendars->getCalendars();
		foreach($calendars as $key => $calendar)
		{
			if ($_REQUEST['tg'] != 'event' || $calendar->canAddEvent())
			{	
				$type = $calendar->getType();
				if (!isset($this->caltypes[$type])) {
					$this->caltypes[$type] = array();
				}
				
				$this->caltypes[$type][$key] = $calendar;
			}
		}


		bab_sort::ksort($this->caltypes, bab_Sort::CASE_INSENSITIVE);

		foreach($this->caltypes as &$arr)
		{
			bab_sort::sortObjects($arr, 'getName', bab_Sort::CASE_INSENSITIVE);
			reset($arr);
		}
		
		reset($this->caltypes);
		
		
	}

	function getnexttype()
		{
		static $i = 0;
		if (list($type, $this->calfromtype) = each($this->caltypes))
			{
			$this->type = bab_toHtml($type);
			$this->number = $i;
			reset($this->calfromtype);
			$i++;
			return true;
			}
		reset($this->caltypes);
		$i = 0;
		return false;
		}

	function getnextcal()
	{
		if (!isset($this->calfromtype))	
		{
			return false;
		}
			
		if (list($key, $calendar) = each($this->calfromtype))
			{
			$this->id = bab_toHtml($key);
			$this->name = bab_toHtml($calendar->getName());
			$this->selected = in_array($key,$this->selectedCalendars);
			
			/*@var $calendar bab_EventCalendar */
			
			$idsa = $calendar->getApprobationSheme();
			if (!empty($idsa)) {
				$this->approb[] = $calendar->getName();
				}
			return true;
			}
			
		$this->calfromtype = null;
		return false;
	}

	

	function getapprob()
		{
		if (count($this->approb) == 1)
			{
			$this->t_approb = bab_toHtml(bab_translate("The calendar").' "'.implode('',$this->approb).'" '.bab_translate("is restricted with approbation, your event will not appear until it has been approved"));
			$this->approb = array();
			return true;
			}

		if (count($this->approb) > 1)
			{
			$this->t_approb = bab_toHtml('"'.implode('", "',$this->approb).'" '.bab_translate("are restricted with approbation, your event will not appear until it has been approved"));
			$this->approb = array();
			return true;
			}
		return false;
		}

	function printhtml()
		{
		return bab_printTemplate($this,"calendar.html", "calendarchoice");
		}
	}

$temp = new calendarchoice($formname, $selected_calendars);
return $temp->printhtml();
}


function record_calendarchoice()
{
global $babBody, $babDB;

$selected = isset($_POST['selected_calendars']) ? $_POST['selected_calendars'] : array();

if ($GLOBALS['BAB_SESS_LOGGED'] && !empty($_POST['database_record']))
	{
	bab_getICalendars()->user_calendarids = implode(',',$selected);
	
	list($n) = $babDB->db_fetch_array($babDB->db_query("SELECT COUNT(*) FROM ".BAB_CAL_USER_OPTIONS_TBL." WHERE id_user='".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."'"));
	if ($n > 0)
		{
		$babDB->db_query("UPDATE ".BAB_CAL_USER_OPTIONS_TBL." SET  user_calendarids='".$babDB->db_escape_string(bab_getICalendars()->user_calendarids)."' WHERE id_user='".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."'");
		}
	else
		{
		$babDB->db_query("insert into ".BAB_CAL_USER_OPTIONS_TBL." ( id_user, startday, allday, start_time, end_time, usebgcolor, elapstime, defaultview, week_numbers, user_calendarids) values ('".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."', '1', 'N', '08:00:00', '18:00:00', 'Y', '30', '0', 'N', '".$babDB->db_escape_string(bab_getICalendars()->user_calendarids)."')");
		}
	}

}

/**
 * @param	array	$idcals
 * @param	string	$date0		ISO date or ISO datetime
 * @param	string	$date1		ISO date or ISO datetime
 * @param	int		$gap		seconds	
 * @param	Y|N		$bopt		if you want to use user's options (deprecated)
 * @return array
 */
function cal_getFreeEvents($idcals, $date0, $date1, $gap, $bopt = 0)
{
	global $babDB;

	$freeevents = array();
	if( !is_array($idcals) || count($idcals) == 0 )
		{
		return $freeevents;
		}


	$sdate = 10 === mb_strlen($date0) ? $date0.' 00:00:00' : $date0;
	$edate = 10 === mb_strlen($date0) ? $date1.' 23:59:59' : $date1;

	$whObj = bab_mcalendars::create_events($sdate, $edate, $idcals);

	
	while(  bab_mcalendars::getNextFreeEvent($whObj, $sdate, $edate, $arr, $gap)) {
		if( 0 === $arr[2] ) {
			$freeevents[] = array(
				bab_mktime($arr[0]), 
				bab_mktime($arr[1])
			);
		}
	}

	return $freeevents;
}

function cal_searchAvailability($tg, $calid, $date, $date0, $date1, $gap, $bopt)
{
	global $babBody;
	class cal_searchAvailabilityCls
		{

		function cal_searchAvailabilityCls($tg, $calid, $date, $date0, $date1, $gap, $bopt)
			{
			global $babBodyPopup, $babBody;
			
			$this->datebegintxt = bab_translate("Begin date")." ".bab_translate("dd-mm-yyyy");
			$this->dateendtxt = bab_translate("Until date")." ".bab_translate("dd-mm-yyyy");
			$this->searchtxt = bab_translate("Search");
			$this->gaptxt = bab_translate("Minimum interval time");
			$this->t_begindate = bab_translate("Begin date");
			$this->t_enddate = bab_translate("End date");
			$this->intervaltxt = bab_translate("Duration");
			$this->yes = bab_translate("Yes");
			$this->no = bab_translate("No");
			$this->optiontxt = bab_translate("Use calendar options");

			$this->bopt = $bopt;
			$this->tg = $tg;
			$this->gap = $gap;
			$this->calid = $calid;
			$this->idcals = explode(",", $calid);
			$this->date = $date;
			$this->date0 = $date0;
			$this->date1 = $date1;
			if( $this->date0 > $this->date1)
				{
				$babBodyPopup->msgerror = bab_translate("End date must be older")." !!";
				}

			$rr = explode(',', $date0);
			$sqdate = sprintf("%s-%02s-%02s", $rr[0], $rr[1], $rr[2]);
			$this->date0val = $rr[2]."-".$rr[1]."-".$rr[0];
			$rr = explode(',', $date1);
			$eqdate = sprintf("%s-%02s-%02s", $rr[0], $rr[1], $rr[2]);
			$this->date1val = $rr[2]."-".$rr[1]."-".$rr[0];

			$this->freeevents = cal_getFreeEvents($this->idcals, $sqdate, $eqdate, $gap, $bopt);

			$this->ymin = 2;
			$this->ymax = 5;
			list($this->curyear,$this->curmonth,$this->curday) = explode(',', $this->date);
			$this->datebeginurl = $GLOBALS['babUrlScript']."?tg=month&callback=dateBegin&ymin=".$this->ymin."&ymax=".$this->ymax."&month=".$this->curmonth."&year=".$this->curyear; 
			$this->dateendurl = $GLOBALS['babUrlScript']."?tg=month&callback=dateEnd&ymin=".$this->ymin."&ymax=".$this->ymax."&month=".$this->curmonth."&year=".$this->curyear;
			
			$this->gaparr = array();
			$this->gaparr[] = array("name" => bab_translate("One hour"), "val" => 3600);
			$this->gaparr[] = array("name" => bab_translate("Two hours"), "val" => 7200);
			$this->gaparr[] = array("name" => bab_translate("Three hours"), "val" => 10800);
			$this->gaparr[] = array("name" => bab_translate("Four hours"), "val" => 14400);
			$this->gaparr[] = array("name" => bab_translate("Five hours"), "val" => 18000);
			$this->gaparr[] = array("name" => bab_translate("Six hours"), "val" => 21600);
			$this->gaparr[] = array("name" => bab_translate("Seven hours"), "val" => 25200);
			$this->gaparr[] = array("name" => bab_translate("Eight hours"), "val" => 28800);
			$this->gaparr[] = array("name" => bab_translate("One day"), "val" => 86400);
			$this->gaparr[] = array("name" => bab_translate("Two days"), "val" => 172800);
			$this->countgap = count($this->gaparr);
			$this->altbg = true;

			$this->daystxt = bab_translate("Days");
			$this->hourstxt = bab_translate("Hours");
			$this->minutestxt = bab_translate("Minutes");


			$this->countfree = count($this->freeevents);
			}


		function getnextfreeevent()
			{
			static $i=0;
			global $babBody;
			if( $i < $this->countfree )
				{
				$this->altbg = !$this->altbg;
				$time0 = $this->freeevents[$i][0];
				$this->startdate = bab_shortDate($time0);
				$time1 = $this->freeevents[$i][1];
				$this->enddate = bab_shortDate($time1);
				
				$this->refurl = $GLOBALS['babUrlScript']."?tg=event&amp;idx=newevent&amp;date=".urlencode(date("Y,n,j", $time1))."&amp;calid=".urlencode(implode(',',$this->idcals))."&amp;&st=".$time0;

				$interval = $time1 - $time0;
				$tmp = (int)($interval / 86400);
				if( $tmp)
					{
					$this->interval = $tmp." ".$this->daystxt;
					}
				else
					{
					$tmp = (int)($interval / 3600);
					if( $tmp )
						{
						$this->interval = $tmp." ".$this->hourstxt;
						}
					else
						{
						$this->interval = (int)($interval / 60)." ".$this->minutestxt;
						}
					}
				$i++;
				return true;
				}
			else
				{
				return false;
				}
			}

		function getnextgap()
			{
			static $i = 0;
			if( $i < $this->countgap)
				{
				$this->gapname = $this->gaparr[$i]['name'];
				$this->gapval = $this->gaparr[$i]['val'];
				if( $this->gap == $this->gapval )
					{
					$this->selected = 'selected';
					}
				else
					{
					$this->selected = '';
					}
				$i++;
				return true;
				}
			else
				{
				return false;
				}

			}
	
		}

	$temp = new cal_searchAvailabilityCls($tg, $calid, $date, $date0, $date1, $gap, $bopt);
	$babBody->babecho(bab_printTemplate($temp, "calendar.html", "searchavailability"));
}

?>