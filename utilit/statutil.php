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

define("BAB_STAT_MAX_ROWS", 20);


class summaryBaseCls
{
	function summaryBaseCls()
	{
		$this->sorttxt = bab_translate("Sort");
		$this->prevpagetxt = bab_translate("Previous");
		$this->nextpagetxt = bab_translate("Next");
		$this->toppagetxt = bab_translate("Start list");
		$this->bottompagetxt = bab_translate("End list");
		$this->hitstxt = bab_translate("Hits");
		$this->currentdate = date("Y,n,j");
	}

	function isNumeric($col)
	{
		return false;
	}

	function compare($a, $b)
		{
		$r = 0;
		if( $this->isNumeric($this->sortcol))
			{
			if( $a[$this->sortcol]  < $b[$this->sortcol] )
				{
				$r = -1;
				}
			elseif( $a[$this->sortcol]  > $b[$this->sortcol] )
				{
				$r = 1;
				}
			else
				{
				$r = 0;
				}
			}
		else
			{
			$r = bab_compare(
				mb_strtolower($a[$this->sortcol]), 
				mb_strtolower($b[$this->sortcol]));
			}

		if ($this->sortord == "desc")
			{
			$r = $r * -1;
			}
		return $r;
		}

}


class summaryDetailBaseCls
{
	function summaryDetailBaseCls($year, $month, $day, $idx, $item)
	{
		global $babMonths;

		$this->hitstxt = bab_translate("Hits");
		$this->exporttxt = bab_translate("Export");
		$time = mktime( 0,0,0, $month, $day, $year);
		$this->daydate = bab_longDate( $time , false);
		$this->monthdate = $babMonths[$month]." ".$year;
		$this->yeardate = $year;
		
		$this->exporturl = $GLOBALS['babUrlScript']."?tg=stat&idx=".$idx."&item=".$item."&date=".$year.",".$month.",".$day."&export=1";

		$time = mktime( 0,0,0, (int)($month)+1, 0, $year);
		$this->nbdays = date("j", $time);

		$time = mktime( 0,0,0, $month, (int)($day)+1, $year);
		$this->nextdayurl = $GLOBALS['babUrlScript']."?tg=stat&idx=".$idx."&item=".$item."&date=".date("Y", $time).",".date("n", $time).",".date("j", $time);
		$time = mktime( 0,0,0, $month, (int)($day)-1, $year);
		$this->prevdayurl = $GLOBALS['babUrlScript']."?tg=stat&idx=".$idx."&item=".$item."&date=".date("Y", $time).",".date("n", $time).",".date("j", $time);

		$time = mktime( 0,0,0, (int)($month)+1, $day, $year);
		$this->nextmonthurl = $GLOBALS['babUrlScript']."?tg=stat&idx=".$idx."&item=".$item."&date=".date("Y", $time).",".date("n", $time).",".date("j", $time);
		$time = mktime( 0,0,0, (int)($month)-1, $day, $year);
		$this->prevmonthurl = $GLOBALS['babUrlScript']."?tg=stat&idx=".$idx."&item=".$item."&date=".date("Y", $time).",".date("n", $time).",".date("j", $time);

		$time = mktime( 0,0,0, $month, $day, (int)($year)+1);
		$this->nextyearurl = $GLOBALS['babUrlScript']."?tg=stat&idx=".$idx."&item=".$item."&date=".date("Y", $time).",".date("n", $time).",".date("j", $time);
		$time = mktime( 0,0,0, $month, $day, (int)($year)-1);
		$this->prevyearurl = $GLOBALS['babUrlScript']."?tg=stat&idx=".$idx."&item=".$item."&date=".date("Y", $time).",".date("n", $time).",".date("j", $time);

	}

	function getnextday()
		{
		static $i = 0;
		if( $i < $this->nbdays)
			{
			if( isset($this->dayinfo[$i+1]))
				{
				$this->hits = $this->dayinfo[$i+1];
				$this->height=($this->hits*100)/$this->maxdayhits;
				}
			else
				{
				$this->hits = 0;
				$this->height=0;
				}
			$this->day = sprintf("%02s", $i+1);
			$i++;
			return true;
			}
		else
			{
			$i = 0;
			return false;
			}
		}

	function getnextmonth()
		{
		global $babShortMonths;
		static $i = 0;
		if( $i < 12)
			{
			if( isset($this->monthinfo[$i+1]))
				{
				$this->hits = $this->monthinfo[$i+1];
				$this->height=(int)(($this->hits*100)/$this->maxmonthhits);
				}
			else
				{
				$this->hits = 0;
				$this->height=0;
				}
			$this->month = sprintf("%02s", $i+1);
			$this->monthname = $babShortMonths[$i+1];
			$i++;
			return true;
			}
		else
			{
			$i = 0;
			return false;
			}
		}

	function getnexthour()
		{
		static $i = 0;
		if( $i < 24)
			{
			if( isset($this->hourinfo[$i]))
				{
				$this->hits = $this->hourinfo[$i];
				$this->height=(int)(($this->hits*100)/$this->maxhourhits);
				}
			else
				{
				$this->hits = 0;
				$this->height=0;
				}
			$this->hour = $i;
			$i++;
			return true;
			}
		else
			{
			$i = 0;
			return false;
			}
		}

}

?>