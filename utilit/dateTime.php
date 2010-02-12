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
	
	
	/**
	 * @param int $iYear
	 * @param int $iMonth
	 * @param int $iDay
	 * @param int $iHours
	 * @param int $iMinutes
	 * @param int $iSeconds
	 * @return BAB_DateTime
     * @access public
	 */
	public function __construct($iYear, $iMonth, $iDay, $iHours = 0, $iMinutes = 0, $iSeconds = 0)
	{
		$this->init($iYear, $iMonth, $iDay, $iHours, $iMinutes, $iSeconds);
	}
	
	public function init($iYear, $iMonth, $iDay, $iHours = 0, $iMinutes = 0, $iSeconds = 0)
	{
		$this->_aDate = getdate(mktime($iHours, $iMinutes, $iSeconds, $iMonth, $iDay, $iYear));

		$this->_iYear		= $this->_aDate['year'];
		$this->_iMonth		= $this->_aDate['mon'];
		$this->_iDay		= $this->_aDate['mday'];
		$this->_iHours		= $this->_aDate['hours'];
		$this->_iMinutes	= $this->_aDate['minutes'];
		$this->_iSeconds	= $this->_aDate['seconds'];		
	}
	
	/**
	 * Creates a new BAB_DateTime from a unix timespamp.
	 *
	 * @param int $iTimeStamp	A unix timestamp
	 * @return BAB_DateTime
     * 
	 */
	public static function fromTimeStamp($iTimeStamp)
	{
		$aDate = getdate($iTimeStamp);
		
		return new BAB_DateTime($aDate['year'], $aDate['mon'], $aDate['mday'], 
			$aDate['hours'], $aDate['minutes'], $aDate['seconds']);
	}
	
	/**
	 * Creates a new BAB_DateTime from an iso-formatted datetime string.
	 *
	 * @param string $sIsoDateTime	Iso-formatted datetime string (eg. '2006-12-25 17:35:17')
	 * @return BAB_DateTime
     * 
	 */
	public static function fromIsoDateTime($sIsoDateTime)
	{
		$aDate = getdate(strtotime($sIsoDateTime));
		
		return new BAB_DateTime($aDate['year'], $aDate['mon'], $aDate['mday'], 
			$aDate['hours'], $aDate['minutes'], $aDate['seconds']);
	}
	
	/**
	 * Returns a new BAB_DateTime corresponding to the present date and time.
	 *
	 * @return BAB_DateTime
     * 
	 */
	public static function now()
	{
		return BAB_DateTime::fromIsoDateTime(date("Y-m-d H:i:s"));
	}
	
	/**
	 * Returns an iso-formatted datetime string (YYYY-MM-DD HH:MM:SS) corresponding to the BAB_DateTime.
	 *
	 * @return string
     * 
	 */
	public function getIsoDateTime()
	{
		return date("Y-m-d H:i:s", mktime($this->_iHours, $this->_iMinutes, 
			$this->_iSeconds, $this->_iMonth, $this->_iDay, $this->_iYear));
	}
	
	/**
	 * Returns an iso-formatted date string (YYYY-MM-DD) corresponding to the BAB_DateTime.
	 *
	 * @return string
     * 
	 */
	public function getIsoDate()
	{
		return date("Y-m-d", mktime($this->_iHours, $this->_iMinutes, 
			$this->_iSeconds, $this->_iMonth, $this->_iDay, $this->_iYear));
	}
	
	/**
	 * @return int
     * 
	 */
	public function getYear()
	{
		return $this->_iYear;
	}
	
	/**
	 * @return int
     * 
	 */
	public function getMonth()
	{
		return $this->_iMonth;
	}
	
	/**
	 * @return int
     * 
	 */
	public function getDayOfMonth()
	{
		return $this->_aDate['mday'];
	}
	
	/**
	 * @return int
     * 
	 */
	public function getDayOfYear()
	{
		return $this->_aDate['yday'];
	}

	/**
	 * @return int
     * 
	 */
	public function getDayOfWeek()
	{
		return $this->_aDate['wday'];
	}

	/**
	 * @return int
     * 
	 */
	public function getHour() 
	{
		return $this->_iHours;
	}

	/**
	 * @return int
     * 
	 */
	public function getMinute() 
	{
		return $this->_iMinutes;
	}

	/**
	 * @return int
     * 
	 */
	public function getSecond() 
	{
		return $this->_iSeconds;
	}

	/**
	 * Elapsed time in the current day
	 * @return int (seconds)
     * 
	 */
	public function getDayTime()
	{
		return $this->_iSeconds + (60*$this->_iMinutes) + (3600*$this->_iHours);
	}
    
	/**
	 * Returns a unix timestamp corresponding to the BAB_DateTime.
	 *
	 * @return int
     * 
	 */
	public function getTimeStamp()  
	{
		if (!is_null($this->_aDate) && isset($this->_aDate[0])) {
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
     * 
     */
    public static function getWeekOfYear($day = 0, $month = 0, $year = 0)
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

    /**
     * Adds a number of units to the datetime.
     * 
     * @param int $iNbUnits		The number of units to add
     * @param int $iUnitType	The type of units to add, can be one of:
     * 							- BAB_DATETIME_YEAR
     * 							- BAB_DATETIME_MONTH
     * 							- BAB_DATETIME_DAY
     * 							- BAB_DATETIME_HOUR
     * 							- BAB_DATETIME_MINUTE
     * 							- BAB_DATETIME_SECOND
     * @access public
     */
    public function add($iNbUnits, $iUnitType = BAB_DATETIME_DAY)
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


	public function less($iNbUnits, $iUnitType = BAB_DATETIME_DAY)
	{
		switch($iUnitType)
		{
			case BAB_DATETIME_YEAR:
				$this->init(($this->_iYear - $iNbUnits), $this->_iMonth, $this->_iDay, $this->_iHours, $this->_iMinutes, $this->_iSeconds);
				break;
			case BAB_DATETIME_MONTH:
				$this->init($this->_iYear, ($this->_iMonth - $iNbUnits), $this->_iDay, $this->_iHours, $this->_iMinutes, $this->_iSeconds);
				break;
			case BAB_DATETIME_DAY:
				$this->init($this->_iYear, $this->_iMonth, ($this->_iDay - $iNbUnits), $this->_iHours, $this->_iMinutes, $this->_iSeconds);
				break;
			case BAB_DATETIME_HOUR:
				$this->init($this->_iYear, $this->_iMonth, $this->_iDay, ($this->_iHours - $iNbUnits), $this->_iMinutes, $this->_iSeconds);
				break;
			case BAB_DATETIME_MINUTE:
				$this->init($this->_iYear, $this->_iMonth, $this->_iDay, $this->_iHours, ($this->_iMinutes - $iNbUnits), $this->_iSeconds);
				break;
			case BAB_DATETIME_SECOND:
				$this->init($this->_iYear, $this->_iMonth, $this->_iDay, $this->_iHours, $this->_iMinutes, ($this->_iSeconds - $iNbUnits));
				break;
		}
	}


	/**
     * Compares two dates
     *
     * Compares two dates.  Suitable for use in sorting functions.
     *
     * 
     * @param object BAB_DateTime $d1 the first date
     * @param object BAB_DateTime $d2 the second date
     * @return int 0 if the dates are equal, -1 if d1 is before d2, 1 if d1 is after d2
     * 
     */
    public static function compare($d1, $d2)
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
     * Returns the number of days between two given dates
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
     */
    public static function dateDiff($day1, $month1, $year1, $day2, $month2, $year2)
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
	 * Returns number of days between two given dates
	 * @param	string	ISO date
	 * @param	string	ISO date
	 * @return int  the absolute number of days between the two dates.
     *               If an error occurs, -1 is returned.
	 */
	public static function dateDiffIso($date1, $date2) {

		list($year1, $month1, $day1) = explode('-',$date1);
		list($year2, $month2, $day2) = explode('-',$date2);

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
     * 
     */
    public static function dateToDays($iDay, $iMonth, $iYear)
    {
        $iCentury = (int)mb_substr($iYear, 0, 2);
        $iYear = (int)mb_substr($iYear, 2, 2);
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
     */
    public static function gregorianToISO($day, $month, $year) {
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
     */
    public static function isLeapYear($year = 0)
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
     */
    public static function isValidDate($day, $month, $year)
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
	 * @param string $value
	 * @return BAB_DateTime
	 */
	public static function fromDateStr($value)
	{
		$tsDate = mktime(0, 0, 0, 11, 30, 2000);
		$strDate = bab_shortDate($tsDate, false);
		$strPattern = $strDate;
		$strPattern = str_replace('30', '([0-9]{1,2})', $strPattern);
		$strPattern = str_replace('11', '([0-9]{1,2})', $strPattern);
		$strPattern = str_replace('Nov', '([0-9]{1,2})', $strPattern);
		$strPattern = str_replace('2000', '([0-9]{4})', $strPattern);
		$strPattern = str_replace('00', '([0-9]{2})', $strPattern);
		$indexDay = mb_strpos($strDate, '30');
		$indexMonth = mb_strpos($strDate, '11');
		if($indexMonth === false)
		{
			$indexMonth = mb_strpos($strDate, 'D');
		}
		$indexYear = mb_strpos($strDate, '2000');
		if($indexYear === false)
		{
			$indexYear = mb_strpos($strDate, '00');
		}
		$d = array($indexDay => 1, $indexMonth => 2, $indexYear => 3);
		bab_sort::ksort($d);

		if (preg_match('`' . $strPattern . '`', $value, $matches) < 1)
		{
			return null;
		}

		$day	= $matches[current($d)];
		$month	= $matches[next($d)];
		$year	= $matches[next($d)];
		
		if($year < 30)
		{
			$year += 2000;
		}
		elseif ($year < 100)
		{
			$year += 1900;
		}
		return new BAB_DateTime($year, $month, $day);
	}
    
	/**
	 * 
	 * @param string $sDate
	 * @return BAB_DateTime
	 */
	public static function fromUserInput($sDate)
	{
		$aMatch = array();
		if (0 !== preg_match("#([0-9]{1,2})[-/]([0-9]{1,2})[-/]([0-9]{4})#", $sDate, $aMatch))
		{
			$iYear	= (int) $aMatch[3];
			$iMonth	= (int) $aMatch[2];
			$iDay	= (int) $aMatch[1];
			
			return new BAB_DateTime($iYear, $iMonth, $iDay);
		}
		return null;
	}
	

	/**
	 * Intersection of two periods
	 * 
	 * All attributes must be ISO date OR ISO datetime
	 * 
	 * @param	string		$p1_begin
	 * @param	string		$p1_end
	 * @param	string		$p2_begin
	 * @param	string		$p2_end
	 * @return	array|false
	 * 
	 */
	public static function periodIntersect($p1_begin, $p1_end, $p2_begin, $p2_end) {
		if ($p1_begin >= $p2_end || $p1_end <= $p2_begin) {
			return false;
		}

		$begin = $p1_begin;

		if ($p1_begin < $p2_begin) {
			$begin = $p2_begin;
		}

		$end = $p2_end;

		if ($p1_end < $p2_end) {
			$end = $p1_end;
		}

		return array(
			'begin' => $begin, 
			'end'	=> $end	
		);
	}


	/**
	 * Creates a copy
	 * 
	 * @return BAB_DateTime
	 * 
	 */
	public function cloneDate() {

		return new BAB_DateTime(
			$this->_iYear,
			$this->_iMonth,
			$this->_iDay,
			$this->_iHours,
			$this->_iMinutes,
			$this->_iSeconds
			);	
	}
}




class BAB_DateTimeUtil
{

	/**
	 * 
	 * @param string $sStartIsoDate
	 * @param string $sEndIsoDate
	 * @return int
	 */
	public static function getNumberOfWorkingDays($sStartIsoDate, $sEndIsoDate)
	{
		$iNWorkingDays = 0;

		$oStartDate = BAB_DateTime::fromIsoDateTime($sStartIsoDate);
		$oEndDate = BAB_DateTime::fromIsoDateTime($sEndIsoDate);

		{
			$iNWorkingDays = BAB_DateTimeUtil::getNoWorkingDaysBetween($sStartIsoDate, $sEndIsoDate);

			$iNbDays = BAB_DateTime::dateDiff($oStartDate->_iDay, $oStartDate->_iMonth, $oStartDate->_iYear, 
				$oEndDate->_iDay, $oEndDate->_iMonth, $oEndDate->_iYear);

			$iNbNoWDaysInWeekend = 2;
			$iNbDaysInWeek = 7;

			$iNbOfWeek = (int) ($iNbDays / $iNbDaysInWeek);
			$iRemainDays = $iNbDays % $iNbDaysInWeek;

			$iNWorkingDays += $iNbOfWeek * $iNbNoWDaysInWeekend;

			if($iRemainDays > 0)
			{
				$oEndDate->less($iRemainDays);
				$iWDay = $oEndDate->_aDate['wday'];

				$iSunday = 0;
				$iSaturday = 6;
				$iNbWeekendDays = 0;
				$iIdx = 0;

				while($iIdx < $iRemainDays)
				{
					if((int) $iWDay == $iSunday || (int) $iWDay == $iSaturday)
					{
						$iNWorkingDays++;
					}

					//$iSaturday is the last week day
					if($iWDay == $iSaturday)
					{
						$iWDay = $iSunday;
					}
					else
					{
						$iWDay++;
					}
					$iIdx++;
				}
			}
			return ($iNbDays - $iNWorkingDays); 
		}
		return $iNWorkingDays;
	}

	/**
	 * 
	 * @param string $sStartIsoDate
	 * @param string $sEndIsoDate
	 * @return int
	 */
	public static function getNoWorkingDaysBetween($sStartIsoDate, $sEndIsoDate)
	{
		require_once $GLOBALS['babInstallPath'] . 'utilit/nwdaysincl.php';
		
		$aNoWorkingDays = bab_getNonWorkingDaysBetween($sStartIsoDate, $sEndIsoDate);
		if(is_array($aNoWorkingDays))
		{
			$iSize = count($aNoWorkingDays);

			$iSunday = 0;
			$iSaturday = 6;
			$iNbWeekendDays = 0;

			foreach($aNoWorkingDays as $sIsoDateTime => $Label)
			{
				//bab_debug($sIsoDateTime);
				$aDate = getdate(strtotime($sIsoDateTime));
				if((int) $aDate['wday'] == $iSunday || (int) $aDate['wday'] == $iSaturday)
				{
					$iNbWeekendDays++;
				}
			}

			assert('$iSize >= $iNbWeekendDays');
			return ($iSize - $iNbWeekendDays);
		}
		return 0;
	}





	/**
	 * Date display in relatives forms, for dates older than now
	 * @param	string	$datetime	ISO datetime
	 * @return string
	 */
	public static function relativePastDate($datetime) {
		$ts = bab_mktime($datetime);
		$sec = (time() - $ts);
		
		if ($sec > 0 && $sec < 3600) {
			if ($sec < 60) {
				return bab_sprintf(bab_translate('%d seconds ago'), $sec);
			} else {
				$minutes = (int) round($sec/60);
				if (1 === $minutes) {
					$str = bab_translate('%d minute ago');
				} else {
					$str = bab_translate('%d minutes ago');
				}
				return bab_sprintf($str, $minutes);
			}
		}
		
		
		if (date('Ymd', $ts) == date('Ymd')) {
			return bab_sprintf(bab_translate('Today at %s'), date('H:i',$ts));
		}
		
		$yesterday = mktime(0, 0, 0, date('n'), (date('j') - 1), date('Y'));
		if (date('Ymd', $ts) == date('Ymd', $yesterday)) {
			return bab_sprintf(bab_translate('Yesterday at %s'), date('H:i',$ts));
		}
		
		return bab_shortDate($ts, false);
	}


	/**
	 * Date display in relatives forms, for dates newer than now
	 * @param	string	$datetime	ISO datetime
	 * @return string
	 */
	public static function relativeFutureDate($datetime) {
		$ts = bab_mktime($datetime);
		$sec = ($ts - time());
		
		if ($sec > 0 && $sec < 3600) {
			if ($sec < 60) {
				return bab_sprintf(bab_translate('in %d seconds'), $sec);
			} else {
				$minutes = (int) round($sec/60);
				if (1 === $minutes) {
					$str = bab_translate('in %d minute');
				} else {
					$str = bab_translate('in %d minutes');
				}
				return bab_sprintf($str, $minutes);
			}
		}
		
		
		if (date('Ymd', $ts) == date('Ymd')) {
			return bab_sprintf(bab_translate('Today at %s'), date('H:i',$ts));
		}
		
		$towmorrow = mktime(0, 0, 0, date('n'), (date('j') + 1), date('Y'));
		if (date('Ymd', $ts) == date('Ymd', $towmorrow)) {
			return bab_sprintf(bab_translate('Towmorrow at %s'), date('H:i',$ts));
		}
		
		return bab_shortDate($ts, false);
	}

}

