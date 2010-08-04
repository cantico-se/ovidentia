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

require_once dirname(__FILE__).'/cal.calendarperiod.class.php';

/**
 * Collection of periods
 * the list of period can be linked to a calendar (for exemple a personal calendar)
 * or the list of period can be directly linked to the userPeriods object (for exemple, the working hours or non working days)
 * 
 */
abstract class bab_PeriodCollection
{
	/**
	 * Optional calendar attached to collection
	 * @var bab_EventCalendar
	 */
	private $calendar = null;
	
	
	/**
	 * @var bab_UserPeriods
	 */
	//private $periods = null;
	
	
	/**
	 * 
	 * @var array
	 */
	private $events = array();
	
	
	/**
	 * Add event to collection
	 * @return bab_EventCollection
	 */
	public function addPeriod(bab_calendarPeriod $event)
	{
		$event->setCollection($this);
		$this->events[] = $event;
		return $this;
	}
	
	/**
	 * Set relation to calendar
	 * @param bab_EventCalendar $calendar
	 * @return bab_PeriodCollection
	 */
	public function setCalendar(bab_EventCalendar $calendar)
	{
		$this->calendar = $calendar;
		return $this;
	}
	
	/**
	 * Get related calendar
	 * @return bab_EventCalendar
	 */
	public function getCalendar()
	{
		return $this->calendar;
	}
}













/**
 * List of vacation periods
 */
class bab_VacationPeriodCollection extends bab_PeriodCollection { }
	
/**
 * List of accessible events in ovidentia calendar
 */
class bab_CalendarEventCollection extends bab_PeriodCollection { }

/**
 * List of tasks from task manager
 */
class bab_TaskCollection extends bab_PeriodCollection { }

/**
 * List of working periods (computed from working hours)
 */
class bab_WorkingPeriodCollection extends bab_PeriodCollection { }

/**
 * List of non-working periods (computed from working hours)
 */
class bab_NonWorkingPeriodCollection extends bab_PeriodCollection { }

/**
 * List of non-working days
 */
class bab_NonWorkingDaysCollection extends bab_PeriodCollection { }