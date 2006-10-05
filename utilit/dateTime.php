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

define('BAB_DATETIME_YEAR', 0);
define('BAB_DATETIME_MONTH', 1);
define('BAB_DATETIME_DAY', 2);
define('BAB_DATETIME_HOUR', 3);
define('BAB_DATETIME_MINUTE', 4);
define('BAB_DATETIME_SECOND', 5);


class BAB_DateTime
{
	var $_iYear		= 0;
	var $_iMonth 	= 0;
	var $_iDay		= 0;
	var $_iHours	= 0;
	var $_iMinutes	= 0;
	var $_iSeconds	= 0;
	var $_aDate		= null;
	
	
	function BAB_DateTime($iYear, $iMonth, $iDay, $iHours = 0, $iMinutes = 0, $iSeconds = 0)
	{
		$this->init($iYear, $iMonth, $iDay, $iHours, $iMinutes, $iSeconds);
	}
	
	function init($iYear, $iMonth, $iDay, $iHours = 0, $iMinutes = 0, $iSeconds = 0)
	{
		$this->_aDate = getdate(mktime($iHours, $iMinutes, $iSeconds, $iMonth, $iDay, $iYear));

		$this->_iYear		= $this->_aDate['year'];
		$this->_iMonth		= $this->_aDate['mon'];
		$this->_iDay		= $this->_aDate['mday'];
		$this->_iHours		= $this->_aDate['hours'];
		$this->_iMinutes	= $this->_aDate['minutes'];
		$this->_iSeconds	= $this->_aDate['seconds'];		
	}
	
	function fromTimeStamp($iTimeStamp)
	{
		$aDate = getdate($iTimeStamp);
		
		return new BAB_DateTime($aDate['year'], $aDate['mon'], $aDate['mday'], 
			$aDate['hours'], $aDate['minutes'], $aDate['seconds']);
	}
	
	function fromIsoDateTime($sIsoDateTime)
	{
		$aDate = getdate(strtotime($sIsoDateTime));
		
		return new BAB_DateTime($aDate['year'], $aDate['mon'], $aDate['mday'], 
			$aDate['hours'], $aDate['minutes'], $aDate['seconds']);
	}
	
	function now()
	{
		return BAB_DateTime::fromIsoDateTime(date("Y-m-d H:i:s"));
	}
	
	function getIsoDateTime()
	{
		return date("Y-m-d H:i:s", mktime($this->_iHours, $this->_iMinutes, 
			$this->_iSeconds, $this->_iMonth, $this->_iDay, $this->_iYear));
	}
	
	function getIsoDate()
	{
		return date("Y-m-d", mktime($this->_iHours, $this->_iMinutes, 
			$this->_iSeconds, $this->_iMonth, $this->_iDay, $this->_iYear));
	}
	
	function getYear()
	{
		return $this->_iYear;
	}
	
	function getMonth()
	{
		return $this->_iMonth;
	}
	
	function getDayOfMonth()
	{
		return $this->_aDate['mday'];
	}
	
	function getDayOfYear()
	{
		return $this->_aDate['yday'];
	}

	function getDayOfWeek()
	{
		return $this->_aDate['wday'];
	}
    
	function getTimeStamp()  
	{
		if(!is_null($this->_aDate) && isset($this->_aDate[0]))
		{
			return $this->_aDate[0];
		}
		return 0;
	}
	 
    /**
     * Returns week of the year, first Sunday is first day of first week
     *
     * @param int    $day     the day of the month, default is current local day
     * @param int    $month   the month, default is current local month
     * @param int    $year    the year in four digit format, default is current local year
     *
     * @return int  the number of the week in the year
     *
     * @access public
     * @static
     */
    function getWeekOfYear($day = 0, $month = 0, $year = 0)
    {
        if (empty($year)) {
            $year = strftime('%Y', time());
        }
        if (empty($month)) {
            $month = strftime('%m', time());
        }
        if (empty($day)) {
            $day = strftime('%d', time());
        }
        $iso    = BAB_DateTime::gregorianToISO($day, $month, $year);
        $parts  = explode('-', $iso);
        $week_number = intval($parts[1]);
        return $week_number;
    }

    function add($iNbUnits, $iUnitType = BAB_DATETIME_DAY)
	{
		switch($iUnitType)
		{
			case BAB_DATETIME_YEAR:
				$this->init(($this->_iYear + $iNbUnits), $this->_iMonth, $this->_iDay, $this->_iHours, $this->_iMinutes, $this->_iSeconds);
				break;
			case BAB_DATETIME_MONTH:
				$this->init($this->_iYear, ($this->_iMonth + $iNbUnits), $this->_iDay, $this->_iHours, $this->_iMinutes, $this->_iSeconds);
				break;
			case BAB_DATETIME_DAY:
				$this->init($this->_iYear, $this->_iMonth, ($this->_iDay + $iNbUnits), $this->_iHours, $this->_iMinutes, $this->_iSeconds);
				break;
			case BAB_DATETIME_HOUR:
				$this->init($this->_iYear, $this->_iMonth, $this->_iDay, ($iNbUnits + $this->_iHours), $this->_iMinutes, $this->_iSeconds);
				break;
			case BAB_DATETIME_MINUTE:
				$this->init($this->_iYear, $this->_iMonth, $this->_iDay, $this->_iHours, ($iNbUnits + $this->_iMinutes), $this->_iSeconds);
				break;
			case BAB_DATETIME_SECOND:
				$this->init($this->_iYear, $this->_iMonth, $this->_iDay, $this->_iHours, $this->_iMinutes, ($iNbUnits + $this->_iSeconds));
				break;
		}
	}

	/**
     * Compares two dates
     *
     * Compares two dates.  Suitable for use
     * in sorting functions.
     *
     * @access public
     * @param object BAB_DateTime $d1 the first date
     * @param object BAB_DateTime $d2 the second date
     * @return int 0 if the dates are equal, -1 if d1 is before d2, 1 if d1 is after d2
     */
    function compare($d1, $d2)
    {
        $iDays1 = BAB_DateTime::dateToDays($d1->_iDay, $d1->_iMonth, $d1->_iYear);
        $iDays2 = BAB_DateTime::dateToDays($d2->_iDay, $d2->_iMonth, $d2->_iYear);
        if ($iDays1 < $iDays2) return -1;
        if ($iDays1 > $iDays2) return 1;
        if ($d1->_iHours < $d2->_iHours) return -1;
        if ($d1->_iHours > $d2->_iHours) return 1;
        if ($d1->_iMinutes < $d2->_iMinutes) return -1;
        if ($d1->_iMinutes > $d2->_iMinutes) return 1;
        if ($d1->_iSeconds < $d2->_iSeconds) return -1;
        if ($d1->_iSeconds > $d2->_iSeconds) return 1;
        return 0;
    }

    
    /**
     * Returns number of days between two given dates
     *
     * @param int    $day1    the day of the month
     * @param int    $month1  the month
     * @param int    $year1   the year.  Use the complete year instead of the
     *                         abbreviated version.  E.g. use 2005, not 05.
     *                         Do not add leading 0's for years prior to 1000.
     * @param int    $day2    the day of the month
     * @param int    $month2  the month
     * @param int    $year2   the year.  Use the complete year instead of the
     *                         abbreviated version.  E.g. use 2005, not 05.
     *                         Do not add leading 0's for years prior to 1000.
     *
     * @return int  the absolute number of days between the two dates.
     *               If an error occurs, -1 is returned.
     *
     * @access public
     * @static
     */
    function dateDiff($day1, $month1, $year1, $day2, $month2, $year2)
    {
        if (!BAB_DateTime::isValidDate($day1, $month1, $year1)) {
            return -1;
        }
        if (!BAB_DateTime::isValidDate($day2, $month2, $year2)) {
            return -1;
        }
        return abs((BAB_DateTime::dateToDays($day1, $month1, $year1)
                   - BAB_DateTime::dateToDays($day2, $month2, $year2)));
    }

    /**
     * Converts a date to number of days since a distant unspecified epoch
     *
     * @param int    $iDay     the day of the month
     * @param int    $iMonth   the month
     * @param int    $iYear    the year.  Use the complete year instead of the
     *                         abbreviated version.  E.g. use 2005, not 05.
     *                         Do not add leading 0's for years prior to 1000.
     *
     * @return integer  the number of days since the Date_Calc epoch
     *
     * @access public
     * @static
     */
    function dateToDays($iDay, $iMonth, $iYear)
    {
        $iCentury = (int)substr($iYear, 0, 2);
        $iYear = (int)substr($iYear, 2, 2);
        if($iMonth > 2) 
        {
            $iMonth -= 3;
        }
        else
        {
            $iMonth += 9;
            if($iYear)
            {
                $iYear--;
            }
            else
            {
                $iYear = 99;
                $iCentury --;
            }
        }

        return (floor((146097 * $iCentury) / 4 ) +
                floor((1461 * $iYear) / 4 ) +
                floor((153 * $iMonth + 2) / 5 ) +
                $iDay + 1721119);
    }
    
    /**
     * Converts from Gregorian Year-Month-Day to ISO Year-WeekNumber-WeekDay
     *
     * Uses ISO 8601 definitions.  Algorithm by Rick McCarty, 1999 at
     * http://personal.ecu.edu/mccartyr/ISOwdALG.txt .
     * Transcribed to PHP by Jesus M. Castagnetto.
     *
     * @param int    $day     the day of the month
     * @param int    $month   the month
     * @param int    $year    the year.  Use the complete year instead of the
     *                         abbreviated version.  E.g. use 2005, not 05.
     *                         Do not add leading 0's for years prior to 1000.
     *
     * @return string  the date in ISO Year-WeekNumber-WeekDay format
     *
     * @access public
     * @static
     */
    function gregorianToISO($day, $month, $year) {
        $mnth = array (0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334);
        $y_isleap = isLeapYear($year);
        $y_1_isleap = isLeapYear($year - 1);
        $day_of_year_number = $day + $mnth[$month - 1];
        if ($y_isleap && $month > 2) {
            $day_of_year_number++;
        }
        // find Jan 1 weekday (monday = 1, sunday = 7)
        $yy = ($year - 1) % 100;
        $c = ($year - 1) - $yy;
        $g = $yy + intval($yy / 4);
        $jan1_weekday = 1 + intval((((($c / 100) % 4) * 5) + $g) % 7);
        // weekday for year-month-day
        $h = $day_of_year_number + ($jan1_weekday - 1);
        $weekday = 1 + intval(($h - 1) % 7);
        // find if Y M D falls in YearNumber Y-1, WeekNumber 52 or
        if ($day_of_year_number <= (8 - $jan1_weekday) && $jan1_weekday > 4){
            $yearnumber = $year - 1;
            if ($jan1_weekday == 5 || ($jan1_weekday == 6 && $y_1_isleap)) {
                $weeknumber = 53;
            } else {
                $weeknumber = 52;
            }
        } else {
            $yearnumber = $year;
        }
        // find if Y M D falls in YearNumber Y+1, WeekNumber 1
        if ($yearnumber == $year) {
            if ($y_isleap) {
                $i = 366;
            } else {
                $i = 365;
            }
            if (($i - $day_of_year_number) < (4 - $weekday)) {
                $yearnumber++;
                $weeknumber = 1;
            }
        }
        // find if Y M D falls in YearNumber Y, WeekNumber 1 through 53
        if ($yearnumber == $year) {
            $j = $day_of_year_number + (7 - $weekday) + ($jan1_weekday - 1);
            $weeknumber = intval($j / 7);
            if ($jan1_weekday > 4) {
                $weeknumber--;
            }
        }
        // put it all together
        if ($weeknumber < 10) {
            $weeknumber = '0'.$weeknumber;
        }
        return $yearnumber . '-' . $weeknumber . '-' . $weekday;
    } 
    
    /**
     * Returns true for a leap year, else false
     *
     * @param int    $year    the year.  Use the complete year instead of the
     *                         abbreviated version.  E.g. use 2005, not 05.
     *                         Do not add leading 0's for years prior to 1000.
     *
     * @return boolean
     *
     * @access public
     * @static
     */
    function isLeapYear($year = 0)
    {
        if(empty($year)) {
            $year = strftime('%Y', time());
        }
        if (preg_match('/\D/', $year)) {
            return false;
        }
        if ($year < 1000) {
            return false;
        }
        if ($year < 1582) {
            // pre Gregorio XIII - 1582
            return ($year % 4 == 0);
        } else {
            // post Gregorio XIII - 1582
            return (($year % 4 == 0) && ($year % 100 != 0)) || ($year % 400 == 0);
        }
    }
    
    /**
     * Returns true for valid date, false for invalid date
     *
     * @param int    $day     the day of the month
     * @param int    $month   the month
     * @param int    $year    the year.  Use the complete year instead of the
     *                         abbreviated version.  E.g. use 2005, not 05.
     *                         Do not add leading 0's for years prior to 1000.
     *
     * @return boolean
     *
     * @access public
     * @static
     */
    function isValidDate($day, $month, $year)
    {
        if ($year < 0 || $year > 9999) {
            return false;
        }
        if (!checkdate($month, $day, $year)) {
            return false;
        }
        return true;
    }
    
	/**
	 * Extract the year, the month and the day from a string representing a date.
	 * 
	 * The extraction tries to guess the position of the day, month and year value in the
	 * string according to the short date output format of the function bab_shortDate.
	 * @static
	 * @param string $value
	 * @return BAB_DateTime
	 */
	function fromDateStr($value)
	{
		$tsDate = mktime(0, 0, 0, 12, 31, 2000);
		$strDate = bab_shortDate($tsDate, false);
		$strPattern = $strDate;
		$strPattern = str_replace('31', '([0-9]{1,2})', $strPattern);
		$strPattern = str_replace('12', '([0-9]{1,2})', $strPattern);
		$strPattern = str_replace('2000', '([0-9]{4})', $strPattern);
		$strPattern = str_replace('00', '([0-9]{2})', $strPattern);
		$indexDay = strpos($strDate, '31');
		$indexMonth = strpos($strDate, '12');
		if ($indexMonth === false)
			$indexMonth = strpos($strDate, 'Dec');
		$indexYear = strpos($strDate, '2000');
		if ($indexYear === false)
			$indexYear = strpos($strDate, '00');
		$d = array($indexDay => 1, $indexMonth => 2, $indexYear => 3);
		ksort($d);
		if (preg_match('`' . $strPattern . '`', $value, $matches) < 1)
			return null;
		$day = $matches[current($d)];
		$month = $matches[next($d)];
		$year = $matches[next($d)];
		if ($year < 30)
			$year += 2000;
		elseif ($year < 100)
			$year += 1900;
		return new BAB_DateTime($year, $month, $day);
	}
    
}
?>