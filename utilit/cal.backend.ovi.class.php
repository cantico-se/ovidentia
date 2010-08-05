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
	 * Returns the period corresponding to the specified identifier
	 * this is necessary for all events with a link
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
			require_once dirname(__FILE__).'/calincl.php';
			$oviEvents = new bab_cal_OviCalendarEvents;
			return $oviEvents->getFromUid($identifier);
		}
		
		return null;
	}
	
}