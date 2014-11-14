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
include 'base.php';




class BAB_TM_Calendar
{
	var $aPeriod = array();	
	
	function BAB_TM_Calendar()
	{
		$this->aPeriod = array(0 => array(), 1 => array(), 2 => array(), 
			3 => array(), 4 => array(), 5 => array(), 6 => array());
	}
	
	function addPeriod($iWeekDay, $oCalendarPeriod)
	{
		if($iWeekDay >= 0 && $iWeekDay <= 6)
		{
			$this->aPeriod[$iWeekDay][] = $oCalendarPeriod;
		}
	}
	
	function getPeriod($iWeekDay)
	{
		if(array_key_exists($iWeekDay, $this->aPeriod))
		{
			return $this->aPeriod[$iWeekDay];
		}
		
		return array();
	}
}


define('BAB_TM_PERIOD_EQUAL', '0');
define('BAB_TM_PERIOD_BEFORE', '-1');
define('BAB_TM_PERIOD_AFTER', '1');

class BAB_TM_CalendarPeriod
{
	var $iStartHour		= 0;
	var $iStartMinut	= 0;
	var $iStartSecond	= 0;
	var $iEndHour		= 0;
	var $iEndMinut		= 0;
	var $iEndSecond		= 0;
	
	function BAB_TM_CalendarPeriod($iStartHour, $iStartMinut, $iStartSecond, $iEndHour, $iEndMinut, $iEndSecond)
	{
		$this->iStartHour	= (int) $iStartHour;
		$this->iStartMinut	= (int) $iStartMinut;
		$this->iStartSecond	= (int) $iStartSecond;
		$this->iEndHour		= (int) $iEndHour;
		$this->iEndMinut	= (int) $iEndMinut;
		$this->iEndSecond	= (int) $iEndSecond;
	}

	function getStartHour()
	{
		return $this->iStartHour;	
	}

	function getStartMinut()
	{
		return $this->iStartMinut;	
	}

	function getStartSecond()
	{
		return $this->iStartSecond;	
	}


	function getEndHour()
	{
		return $this->iEndHour;	
	}

	function getEndMinut()
	{
		return $this->iEndMinut;	
	}

	function getEndSecond()
	{
		return $this->iEndSecond;	
	}

	function compare()
	{
		if($this->getStartHour() < $this->getEndHour())
		{
			return BAB_TM_PERIOD_BEFORE;
		}

        if($this->getStartHour() > $this->getEndHour())
		{
			return BAB_TM_PERIOD_AFTER;
		}

        if($this->getStartMinut() < $this->getEndMinut())
		{
			return BAB_TM_PERIOD_BEFORE;
		}

        if($this->getStartMinut() > $this->getEndMinut())
		{
			return BAB_TM_PERIOD_AFTER;
		}

        if($this->getStartSecond() < $this->getEndSecond())
		{
			return BAB_TM_PERIOD_BEFORE;
		}

        if($this->getStartSecond() > $this->getEndSecond())
		{
			return BAB_TM_PERIOD_AFTER;
		}

        return BAB_TM_PERIOD_EQUAL;
	}
}



/*
 * Interval semie ouvert, début inclus et fin exclus 
 */
function bab_tskmgr_getCalendar()
{
	$oTmCalendar = new BAB_TM_Calendar();
/*	
	//Lundi
	$oTmCalendar->addPeriod(1, new BAB_TM_CalendarPeriod(9, 0, 12, 0));
	$oTmCalendar->addPeriod(1, new BAB_TM_CalendarPeriod(13, 0, 17, 0));
	
	//Mardi
	$oTmCalendar->addPeriod(2, new BAB_TM_CalendarPeriod(9, 0, 12, 0));
	$oTmCalendar->addPeriod(2, new BAB_TM_CalendarPeriod(13, 0, 17, 0));
	
	//Mercredi
	$oTmCalendar->addPeriod(3, new BAB_TM_CalendarPeriod(9, 0, 12, 0));
	$oTmCalendar->addPeriod(3, new BAB_TM_CalendarPeriod(13, 0, 17, 0));
	
	//Jeudi
	$oTmCalendar->addPeriod(4, new BAB_TM_CalendarPeriod(9, 0, 12, 0));
	$oTmCalendar->addPeriod(4, new BAB_TM_CalendarPeriod(13, 0, 17, 0));
	
	//Vendredi
	$oTmCalendar->addPeriod(5, new BAB_TM_CalendarPeriod(9, 0, 12, 0));
	$oTmCalendar->addPeriod(5, new BAB_TM_CalendarPeriod(13, 0, 17, 0));
//*/

/*	
	//Lundi
	$oTmCalendar->addPeriod(1, new BAB_TM_CalendarPeriod(9, 0, 12, 0));
	$oTmCalendar->addPeriod(1, new BAB_TM_CalendarPeriod(13, 0, 18, 0));
	
	//Mardi
	$oTmCalendar->addPeriod(2, new BAB_TM_CalendarPeriod(9, 0, 12, 0));
	$oTmCalendar->addPeriod(2, new BAB_TM_CalendarPeriod(13, 0, 18, 0));
	
	//Mercredi
	$oTmCalendar->addPeriod(3, new BAB_TM_CalendarPeriod(9, 0, 12, 0));
	$oTmCalendar->addPeriod(3, new BAB_TM_CalendarPeriod(13, 0, 18, 0));
	
	//Jeudi
	$oTmCalendar->addPeriod(4, new BAB_TM_CalendarPeriod(9, 0, 12, 0));
	$oTmCalendar->addPeriod(4, new BAB_TM_CalendarPeriod(13, 0, 18, 0));
	
	//Vendredi
	$oTmCalendar->addPeriod(5, new BAB_TM_CalendarPeriod(9, 0, 12, 0));
	$oTmCalendar->addPeriod(5, new BAB_TM_CalendarPeriod(13, 0, 17, 0));
//*/

//*
//	$oTmCalendar->addPeriod(0, new BAB_TM_CalendarPeriod(0, 0, 0, 0, 0, 0));
//	$oTmCalendar->addPeriod(6, new BAB_TM_CalendarPeriod(0, 0, 0, 0, 0, 0));
	
	//Lundi
	$oTmCalendar->addPeriod(1, new BAB_TM_CalendarPeriod(0, 0, 0, 0, 0, 0));
	
	//Mardi
	$oTmCalendar->addPeriod(2, new BAB_TM_CalendarPeriod(0, 0, 0, 0, 0, 0));
	
	//Mercredi
	$oTmCalendar->addPeriod(3, new BAB_TM_CalendarPeriod(0, 0, 0, 0, 0, 0));
	
	//Jeudi
	$oTmCalendar->addPeriod(4, new BAB_TM_CalendarPeriod(0, 0, 0, 0, 0, 0));
	
	//Vendredi
	$oTmCalendar->addPeriod(5, new BAB_TM_CalendarPeriod(0, 0, 0, 0, 0, 0));
//*/	
	return $oTmCalendar;
}
?>