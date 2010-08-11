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

bab_functionality::includefile('CalendarBackend');


class Func_CalendarBackend_Ovi extends Func_CalendarBackend
{
	public function getDescription()
	{
		return bab_translate('Ovidentia calendar backend');
	}
	
	
	/**
	 * @return bab_PersonalCalendar
	 */
	public function PersonalCalendar()
	{
		$this->includeEventCalendar();
		return new bab_PersonalCalendar;
	}
	
	/**
	 * @return bab_PublicCalendar
	 */
	public function PublicCalendar()
	{
		$this->includeEventCalendar();
		return new bab_PublicCalendar;
	}
	
	/**
	 * @return bab_RessourceCalendar
	 */
	public function RessourceCalendar()
	{
		$this->includeEventCalendar();
		return new bab_RessourceCalendar;
	}
	
	
	
	/**
	 * Creates or updates a calendar event.
	 * if the period have a UID property, the event will be modified or if the UID property is empty, the event will be created
	 * 
	 * @param	bab_CalendarPeriod	$period
	 * 
	 * @return bool
	 */
	public function savePeriod(bab_CalendarPeriod $period)
	{
		require_once dirname(__FILE__).'/cal.ovievent.class.php';
		return bab_cal_OviEventUpdate::save($period);
	}
	
	
	
	/**
	 * Returns the period corresponding to the specified identifier
	 * this is necessary for all events with a link
	 * 
	 * @TODO other collections ?
	 * 
	 * @param	bab_PeriodCollection	$periodCollection		where to search for event
	 * @param 	string 					$identifier				The UID property of event
	 * 
	 * @return bab_CalendarPeriod
	 */
	public function getPeriod(bab_PeriodCollection $periodCollection, $identifier)
	{
		if ($periodCollection instanceof bab_CalendarEventCollection) 
		{
			require_once dirname(__FILE__).'/cal.ovievent.class.php';
			$oviEvents = new bab_cal_OviEventSelect;
			return $oviEvents->getFromUid($identifier);
		}
		
		return null;
	}
	
	
	
	/**
	 * Select periods from criteria
	 * the bab_PeriodCriteriaCollection and bab_PeriodCriteriaCalendar are mandatory
	 * 
	 * @param bab_PeriodCriteria $criteria
	 * 
	 * @return bab_UserPeriods <bab_CalendarPeriod>		(iterator)
	 */
	public function selectPeriods(bab_PeriodCriteria $criteria)
	{
		require_once dirname(__FILE__).'/cal.userperiods.class.php';
		require_once dirname(__FILE__).'/cal.ovievent.class.php';
		
		$userperiods = new bab_UserPeriods;
		$userperiods->processCriteria($criteria);
		
		$oviEvents = new bab_cal_OviEventSelect;
		$oviEvents->processQuery($userperiods);
		
		$userperiods->orderBoundaries();
		
		return $userperiods;
	}
	
	
	
	
	/**
	 * Delete the period corresponding to the specified identifier.
	 * 
	 * @param	bab_PeriodCollection	$periodCollection		where to search for event
	 * @param 	string 					$identifier				The UID property of event
	 * 
	 * @return bool
	 */
	public function deletePeriod(bab_PeriodCollection $periodCollection, $identifier)
	{
		if ($periodCollection instanceof bab_CalendarEventCollection) 
		{
			require_once dirname(__FILE__).'/cal.ovievent.class.php';
			$oviEvents = new bab_cal_OviEventSelect;
			return $oviEvents->deleteFromUid($identifier);
		}
		
		return null;
	}
	
	
	
	/**
	 * Test if the backend support saving more than one calendar per event
	 * @return bool
	 */
	public function canHaveMultipleCalendarPerEvent()
	{
		return true;
	}
	
}