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
include_once "base.php";


class bab_calendar
{
var $sContent;

function bab_calendar($month, $year, $callback, $ymin, $ymax)
	{
	global $babBody;
	
	$ymin = (int) $ymin;
	$ymax = (int) $ymax;
	$this->year = (int) $year;
	$this->month = (int) $month;
	$this->callback = bab_toHtml($callback, BAB_HTML_JS);
	$this->ymin = $year - $ymin - 1;
	$this->ymax = $year + $ymax;
	$this->value = $this->ymin;
	$this->sContent	= 'text/html; charset=' . bab_charset::getIso();
	
	reset($GLOBALS['babMonths']);
	reset($GLOBALS['babDays']);

	$this->t_previous_month = bab_translate("Previous month");
	$this->t_next_month = bab_translate("Next month");
	$this->t_today = bab_translate("Today");

	$this->current_month = date('n');
	$this->current_year = date('Y');


	$this->startday = isset(bab_getICalendars()->startday) ? bab_getICalendars()->startday : 1;
	}


function getnextyear()
	{
	if ($this->value < $this->ymax)
		{
		
		$this->value++;
		$this->selected = $this->value == $this->year ? 'selected' : '';
		return true;
		}
	else
		{
		return false;
		}

	}

function getnextmonth()
	{
	$this->text = current($GLOBALS['babMonths']);
	if ($this->text)
		{
		$this->num = key($GLOBALS['babMonths']);
		$this->index = $this->num - 1;
		next($GLOBALS['babMonths']);
		$this->selected = $this->num == $this->month ? 'selected' : '';
		return true;
		}
	else
		{
		reset($GLOBALS['babMonths']);
		return false;
		}
	
	}

function getnextwday()
	{
	static $i = 0;
	
	if ($i < 7)
		{
		$index = $this->startday + $i < 7 ? $this->startday + $i : $this->startday + $i -7;
		$this->text = mb_substr($GLOBALS['babDays'][$index],0,3);
		$i++;
		return true;
		}
	else
		{
		return false;
		}
	}
	
}


if (!isset($_GET['callback']))
	die('missing callback');

$month = isset($_GET['month']) ? $_GET['month'] : date('m');
$year = isset($_GET['year']) ? $_GET['year'] : date('Y');
$ymin = isset($_GET['ymin']) ? $_GET['ymin'] : 33; // minor than 1 Jan 1970 is risky
$ymax = isset($_GET['ymax']) ? $_GET['ymax'] : 60;

$m = new bab_calendar($month, $year, $_GET['callback'], $ymin, $ymax);
echo bab_printTemplate($m,"month.html");

?>
