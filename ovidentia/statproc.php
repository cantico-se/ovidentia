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
* @internal SEC1 NA 11/12/2006 FULL
*/

require_once dirname(__FILE__).'/utilit/statprocincl.php';

/* main */

bab_requireSaveMethod();

// deprecated: use LibTimer instead to update stats

$idx = bab_rp('idx');
$statlimit = bab_rp('statlimit', OVSTAT_LIMIT);
$statrows = bab_rp('statrows', OVSTAT_ROWS);

$babStatRefs = array();

switch($idx)
	{
	case "maj":
		$statecho = false;
		break;

	default:
		$statecho = true;
		break;
	}

bab_setTimeLimit(0);

bab_stat_process($statrows, $statlimit, $statecho);

if( $statecho )
{
	exit;
}