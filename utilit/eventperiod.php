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
require_once $GLOBALS['babInstallPath'].'utilit/eventincl.php';

/**
 * Event fired when a calendar is displayed
 * use it to display periods into the calendar
 * @since 6.1.0
 * @package events
 */
class bab_eventBeforePeriodsCreated extends bab_event {
	
	/**
 	 * @public
	 */
	var $periods;


	/**
 	 * @param 	bab_userWorkingHours 	$obj
	 */
	function bab_eventBeforePeriodsCreated($obj) {
		$this->periods = & $obj;
	}
}



/**
 * Event fired when a period is modified
 * @since 6.1.0
 * @package events
 */
class bab_eventPeriodModified extends bab_event {

	/**
 	 * @public
	 */
	var $begin;
	var $end;
	var $id_user;
	var $types;
	
	/**
	 * if the dates are false, the modification has no boundaries
 	 * @param 	int|false 	$begin		timestamp
	 * @param	int|false	$end		timestamp
	 * @param	int|false	$id_user
	 */
	function bab_eventPeriodModified($begin, $end, $id_user) {
		$this->begin 	= $begin;
		$this->end 		= $end;
		$this->id_user	= $id_user;
		$this->types	= BAB_PERIOD_WORKING | BAB_PERIOD_NONWORKING | BAB_PERIOD_NWDAY | BAB_PERIOD_CALEVENT | BAB_PERIOD_TSKMGR | BAB_PERIOD_VACATION;
	}

}


?>