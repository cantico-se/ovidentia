<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/

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

var $today;

function babMonthX($month = "", $year = "", $callback = "")
	{

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
		$this->today = "<a href=\"".$GLOBALS['babUrl']."index.php?tg=month&callback=".$this->callback."&ymin=";
		$this->today .= ($todayyear - $this->currentYear + $this->ymin)."&ymax=".($this->currentYear + $this->ymax - $todayyear)."&month=".$todaymonth."&year=".($todayyear)."\">today</a>";
		}

	if( $this->currentYear > $this->currentYear - $this->ymin)
		$this->prevyear = "<a href=\"".$GLOBALS['babUrl']."index.php?tg=month&callback=".$this->callback."&ymin=".($this->ymin-1)."&ymax=".($this->ymax+1)."&month=".$this->currentMonth."&year=".($this->currentYear-1)."\"><<</a>";
	else
		$this->prevyear = "&nbsp;";

	if( $this->currentMonth != 1 || $this->currentYear > $this->currentYear - $this->ymin)
		{
		if( $this->currentMonth == 1)
			{
			$this->prevmonth = "<a href=\"".$GLOBALS['babUrl']."index.php?tg=month&callback=".$this->callback."&ymin=".($this->ymin-1)."&ymax=".($this->ymax+1)."&month=";
			$this->prevmonth .= "12&year=".($this->currentYear-1);
			}
		else
			{
			$this->prevmonth = "<a href=\"".$GLOBALS['babUrl']."index.php?tg=month&callback=".$this->callback."&ymin=".$this->ymin."&ymax=".$this->ymax."&month=";
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
			$this->nextmonth = "<a href=\"".$GLOBALS['babUrl']."index.php?tg=month&callback=".$this->callback."&ymin=".($this->ymin+1)."&ymax=".($this->ymax-1)."&month=";
			$this->nextmonth .= "1&year=".($this->currentYear+1);
			}
		else
			{
			$this->nextmonth = "<a href=\"".$GLOBALS['babUrl']."index.php?tg=month&callback=".$this->callback."&ymin=".$this->ymin."&ymax=".$this->ymax."&month=";
			$this->nextmonth .= ($this->currentMonth+1)."&year=".$this->currentYear;
			}
		$this->nextmonth .= "\">></a>";
		}
	else
		$this->nextmonth = "&nbsp;";


	if( $this->currentYear < $this->currentYear + $this->ymax)
		$this->nextyear = "<a href=\"".$GLOBALS['babUrl']."index.php?tg=month&callback=".$this->callback."&ymin=".($this->ymin+1)."&ymax=".($this->ymax-1)."&month=".$this->currentMonth."&year=".($this->currentYear+1)."\">>></a>";
	else
		$this->nextyear = "&nbsp;";


	echo babPrintTemplate($this,"month.html", "");
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
			$this->bgcolor = "";

			if( $this->w == 1 &&  $d < $this->daynumber)
				{
				$this->day = "&nbsp;";
				}
			else
				{
				$total++;

				if( $total > $this->days)
					return false;
				if( $total == $this->now && date("n", mktime(0,0,0,$this->currentMonth,1,$this->currentYear)) == date("n") && $this->currentYear == date("Y"))
					{
					$this->bgcolor = "bgcolor=\"white\"";
					$this->dayurl = "\"#\" onclick=_\"self.opener.".$this->callback."('".$total."','".$this->currentMonth."','".$this->currentYear."');window.close();\"";
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
?>