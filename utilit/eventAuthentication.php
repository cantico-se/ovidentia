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
require_once 'base.php';
require_once $GLOBALS['babInstallPath'].'utilit/eventincl.php';



class bab_eventAuthBase extends bab_event
{
	var $bStopPropagation = false;
	
	function bab_eventAuthBase()
	{
		
	}
	
	function setStopPropagation($bStopPropagation)
	{
		$this->bStopPropagation = (bool) $bStopPropagation;
	}
	
	function haveBeenStopped()
	{
		return $this->bStopPropagation;
	}
}


class bab_eventLogin extends bab_eventAuthBase
{
	var $bSignedOn = false;
	
	function bab_eventLogin()
	{
		parent::bab_eventAuthBase();
	}
	
	function setSignedOn($bSignedOn)
	{
		$this->bSignedOn = (bool) $bSignedOn;
	}
	
	function haveSignedOn()
	{
		return $this->bSignedOn;
	}
}


class bab_eventLogout extends bab_eventAuthBase
{
	function bab_eventAuthenticate()
	{
		parent::bab_eventAuthBase();
	}
}




function bab_onEventLogin(&$oEvent)
{
	if(false === $oEvent->haveBeenStopped())
	{
		require_once $GLOBALS['babInstallPath'] . 'utilit/loginIncl.php';
		bab_login($oEvent);
	}
}

function bab_onEventLogout(&$oEvent)
{
	if(false === $oEvent->haveBeenStopped())
	{
		require_once $GLOBALS['babInstallPath'] . 'utilit/loginIncl.php';
		bab_logout($oEvent);
	}
}
?>