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
class babMonthX
{
var $currentMonth;
var $currentYear;
var $ymin;
var $ymax;
var $callback;
var $curmonth;
var $curyear;
var $day3;

var $days;
var $daynumber;
var $now;
var $w;

var $nextmonth;
var $nextyear;
var $prevmonth;
var $prevyear;

var $babCss;
var $today;

function babMonthX($month = "", $year = "", $callback = "")
	{

	$this->babCss = bab_printTemplate($this,"config.html", "babCss");
	if(empty($month))
		$this->currentMonth = Date("n");
	else
		{
		$this->currentMonth = $month;
		}
	$this->callback = $callback;
	
	if(empty($year))
		{
		$this->currentYear = Date("Y");
		}
	else
		{
		$this->currentYear = $year;
		}

	$this->ymin = 1;
	$this->ymax = 8;
	$this->nextmonth = "";
	$this->nextyear = "";
	$this->prevmonth = "";
	$this->prevyear = "";
	$this->today = "";
	}

function setMaxYear( $delta )
	{
	$this->ymax = $delta;
	}

function setMinYear( $delta )
	{
	$this->ymin = $delta;
	}

function printout()
	{
	global $babMonths;
	$this->curmonth = $babMonths[date("n", mktime(0,0,0,$this->currentMonth,1,$this->currentYear))];
	$this->curyear = $this->currentYear;
	$this->days = date("t", mktime(0,0,0,$this->currentMonth,1,$this->currentYear));
	$this->daynumber = date("w", mktime(0,0,0,$this->currentMonth,1,$this->currentYear));
	$this->now = date("j");
	$this->w = 0;
	$todaymonth = date("n");
	$todayyear = date("Y"); 
	if( $todayyear >= $this->currentYear - $this->ymin && $todayyear <= $this->currentYear + $this->ymax )
		{
		$this->today = "<a href=\"".$GLOBALS['babUrlScript']."?tg=month&callback=".$this->callback."&ymin=";
		$this->today .= ($todayyear - $this->currentYear + $this->ymin)."&ymax=".($this->currentYear + $this->ymax - $todayyear)."&month=".$todaymonth."&year=".($todayyear)."\">".bab_translate("Go to Today")."</a>";
		}

	if( $this->currentYear > $this->currentYear - $this->ymin)
		$this->prevyear = "<a href=\"".$GLOBALS['babUrlScript']."?tg=month&callback=".$this->callback."&ymin=".($this->ymin-1)."&ymax=".($this->ymax+1)."&month=".$this->currentMonth."&year=".($this->currentYear-1)."\"><<</a>";
	else
		$this->prevyear = "&nbsp;";

	if( $this->currentMonth != 1 || $this->currentYear > $this->currentYear - $this->ymin)
		{
		if( $this->currentMonth == 1)
			{
			$this->prevmonth = "<a href=\"".$GLOBALS['babUrlScript']."?tg=month&callback=".$this->callback."&ymin=".($this->ymin-1)."&ymax=".($this->ymax+1)."&month=";
			$this->prevmonth .= "12&year=".($this->currentYear-1);
			}
		else
			{
			$this->prevmonth = "<a href=\"".$GLOBALS['babUrlScript']."?tg=month&callback=".$this->callback."&ymin=".$this->ymin."&ymax=".$this->ymax."&month=";
			$this->prevmonth .= ($this->currentMonth - 1)."&year=".$this->currentYear;
			}
		$this->prevmonth .= "\"><</a>";
		}
	else
		$this->prevmonth = "&nbsp;";

	if( $this->currentMonth != 12 || $this->currentYear < $this->currentYear + $this->ymax)
		{
		if( $this->currentMonth == 12)
			{
			$this->nextmonth = "<a href=\"".$GLOBALS['babUrlScript']."?tg=month&callback=".$this->callback."&ymin=".($this->ymin+1)."&ymax=".($this->ymax-1)."&month=";
			$this->nextmonth .= "1&year=".($this->currentYear+1);
			}
		else
			{
			$this->nextmonth = "<a href=\"".$GLOBALS['babUrlScript']."?tg=month&callback=".$this->callback."&ymin=".$this->ymin."&ymax=".$this->ymax."&month=";
			$this->nextmonth .= ($this->currentMonth+1)."&year=".$this->currentYear;
			}
		$this->nextmonth .= "\">></a>";
		}
	else
		$this->nextmonth = "&nbsp;";


	if( $this->currentYear < $this->currentYear + $this->ymax)
		$this->nextyear = "<a href=\"".$GLOBALS['babUrlScript']."?tg=month&callback=".$this->callback."&ymin=".($this->ymin+1)."&ymax=".($this->ymax-1)."&month=".$this->currentMonth."&year=".($this->currentYear+1)."\">>></a>";
	else
		$this->nextyear = "&nbsp;";


	echo bab_printTemplate($this,"month.html", "old");
	}

	function getnextday3()
		{
		global $babDays;
		static $i = 0;
		if( $i < 7)
			{
			$this->day3 = substr($babDays[$i], 0, 3);
			$i++;
			return true;
			}
		else
			return false;
		}

	function getnextweek()
		{
		if( $this->w < 7)
			{
			$this->w++;
			return true;
			}
		else
			{
			return false;
			}
		}

	function getnextday()
		{
		static $d = 0;
		static $total = 0;
		if( $d < 7)
			{
			$this->currentday = false;

			if( $this->w == 1 &&  $d < $this->daynumber)
				{
				$this->day = "&nbsp;";
				$this->dayurl = "\"#\"";
				}
			else
				{
				$total++;

				if( $total > $this->days)
					return false;
				if( $total == $this->now && date("n", mktime(0,0,0,$this->currentMonth,1,$this->currentYear)) == date("n") && $this->currentYear == date("Y"))
					{
					$this->currentday = true;
					$this->dayurl = "\"#\" onclick=\"self.opener.".$this->callback."('".$total."','".$this->currentMonth."','".$this->currentYear."');window.close();\"";
					$this->day = $total;
					}
				else
					{
					$this->dayurl = "\"#\" onclick=\"self.opener.".$this->callback."('".$total."','".$this->currentMonth."','".$this->currentYear."');window.close();\"";
					$this->day = $total;
					}

				}
			if( $total > $this->days)
				{
				return false;
				}
			$d++;
			return true;
			}
		else
			{
			$d = 0;
			return false;
			}
		}
}


class bab_calendar
{


function bab_calendar($month, $year, $callback, $ymin, $ymax)
	{
	$this->year = $year;
	$this->month = $month;
	$this->callback = $callback;
	$this->ymin = $year - $ymin;
	$this->ymax = $year + $ymax;
	$this->value = $this->ymin;
	reset($GLOBALS['babMonths']);
	reset($GLOBALS['babDays']);

	$this->t_previous_month = bab_translate("Previous month");
	$this->t_next_month = bab_translate("Next month");
	$this->t_today = bab_translate("Today");

	$this->current_month = date('n');
	$this->current_year = date('Y');
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
	$this->text = substr(current($GLOBALS['babDays']),0,3);
	if ($this->text)
		{
		next($GLOBALS['babDays']);
		return true;
		}
	else
		{
		reset($GLOBALS['babDays']);
		return false;
		}
	}
	
}

/*
if( !isset($month)) { $month ='';}
if( !isset($year)) { $year ='';}
$m = new babMonthX($month, $year, $callback);
$m->setMaxYear($ymax);
$m->setMinYear($ymin);
$m->printout();

*/

if (!isset($_GET['callback']))
	die('missing callback');

$month = isset($_GET['month']) ? $_GET['month'] : date('m');
$year = isset($_GET['year']) ? $_GET['year'] : date('Y');
$ymin = isset($_GET['ymin']) ? $_GET['ymin'] : 5;
$ymax = isset($_GET['ymax']) ? $_GET['ymax'] : 60;

$m = new bab_calendar($month, $year, $_GET['callback'], $ymin, $ymax);
echo bab_printTemplate($m,"month.html", "new");

?>
