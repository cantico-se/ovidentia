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

/**
 * RRULE iCalendar property methods
 */
class bab_CalendarRRULE
{	
	
	
	
	/**
	 * Expand period to collection with the RRULE and return collection
	 * @param bab_CalendarPeriod $period
	 * @return bab_PeriodCollection
	 */
	public static function getCollection(bab_CalendarPeriod $period)
	{
		$manager = bab_getInstance('bab_CalendarRRULE');
		/*@var $manager bab_CalendarRRULE */
		
		$manager->applyRrule($period);
		
		return $period->getCollection();
	}
	
	
	
	
	
	/**
	 * Create all periods into collection for a recurring event
	 * return the hash to use for serie if there is at least one period generated or null if no recurring rule
	 * this method implement only the recurring rules builable from the ovidentia user interface
	 * 
	 * @param	bab_CalendarPeriod 	$period
	 * 
	 * @return string | null	hash
	 */
	public function applyRrule(bab_CalendarPeriod $period)
	{

		$rrule = $period->getProperty('RRULE');
		
		if (empty($rrule))
		{
			return null;
		}
		
		// before saving a period, the submited period must be set in a new collection 
		
		$collection = $period->getCollection();
		if (!$collection)
		{
			throw new Exception('missing collection in the new event');
			return null;
		}
		
		if ($collection->hash)
		{
			return $collection->hash;
		}
		
		
		if (1 !== $collection->count())
		{
			throw new Exception('Error, the number of periods in collection is incorrect, RRULE is propably allready applied');
			return null;
		}
		
		
		
		require_once dirname(__FILE__).'/dateTime.php';
		
		
		// create default UNTIL param because ovidentia does not support infinite recurring
		
		$UNTIL = BAB_DateTime::fromTimeStamp($period->ts_begin);
		$UNTIL->add(5, BAB_DATETIME_YEAR);
		
		// day of week default values (used in weekly recurring rule)
		$BYDAY = array();
		
		// default interval
		$INTERVAL = 1;
		
		$params = explode(';', $rrule);
		
		foreach($params as $pair)
		{
			$param = explode('=', $pair);
			switch($param[0])
			{
				case 'UNTIL':
					$UNTIL = BAB_DateTime::fromICal($param[1]);
					break;
					
				case 'BYDAY':
					$BYDAY = explode(',', $param[1]);
					break;
					
				case 'FREQ':
					$FREQ = $param[1];
					break;
					
				case 'INTERVAL':
					$INTERVAL = (int) $param[1];
					break;
			} 
		}
		
		
		if (!isset($FREQ))
		{
			throw new Exception('No FREQ parameter in the RRULE iCalendar Property :'.$rrule);
			return null;
		}
		
		
		switch($FREQ) 
		{
			case 'DAILY':
				$this->applyRruleGeneric($period, BAB_DATETIME_DAY, $INTERVAL, $UNTIL);
				break;
				
			case 'WEEKLY':
				$this->applyRruleWeekly($period, $BYDAY, $INTERVAL, $UNTIL);
				break;
				
			case 'MONTHLY':
				$this->applyRruleGeneric($period, BAB_DATETIME_MONTH, $INTERVAL, $UNTIL);
				break;
				
			case 'YEARLY':
				$this->applyRruleGeneric($period, BAB_DATETIME_YEAR, $INTERVAL, $UNTIL);
				break;
		}
		
		
		// create the new hash for collection
		$collection->hash = "R_".md5(uniqid(rand(),1));
		
		return $collection->hash;
	}
	
	
	/**
	 * Apply RRULE for DAILY, MONTHLY, YEARLY
	 * 
	 * @param	bab_CalendarPeriod 	$period
	 * @param 	int 				$freq
	 * @param 	int 				$interval
	 * @param	BAB_DateTime 		$until
	 * 
	 * @return unknown_type
	 */
	private function applyRruleGeneric(bab_CalendarPeriod $period, $freq, $interval, BAB_DateTime $until)
	{
		$collection = $period->getCollection();
		$created = clone $period;
		
		while($created->ts_end < $until->getTimeStamp())
		{
			$begin 	= BAB_DateTime::fromTimeStamp($created->ts_begin);
			$end 	= BAB_DateTime::fromTimeStamp($created->ts_end);

			$begin->add($interval, $freq);
			$end->add($interval, $freq);
			
			if ($end->getTimeStamp() > $until->getTimeStamp())
			{
				break;
			}
			
			$created->setDates($begin, $end);
			$collection->addPeriod($created);
			
			$created = clone $created;
		}
		
	}
	
	
	
	
	
	/**
	 * Apply RRULE for WEEKLY
	 * 
	 * @param	bab_CalendarPeriod 	$period
	 * @param	array				$byday
	 * @param 	int 				$interval
	 * @param	BAB_DateTime 		$until
	 * 
	 * @return unknown_type
	 */
	private function applyRruleWeekly(bab_CalendarPeriod $period, $byday, $interval, BAB_DateTime $until)
	{
		if (empty($byday))
		{
			$byday = array($this->dayOfWeek($period->ts_begin));
		}
		
		$flipped_days = array_flip($byday);
		
		
		$collection = $period->getCollection();
		$created = clone $period;
		
		while($created->ts_end < $until->getTimeStamp())
		{
			$begin 	= BAB_DateTime::fromTimeStamp($created->ts_begin);
			$end 	= BAB_DateTime::fromTimeStamp($created->ts_end);

			$begin->add(1, BAB_DATETIME_DAY);
			$end->add(1, BAB_DATETIME_DAY);
			
			$day = $this->dayOfWeek($created->ts_begin);
			
			
			if ($end->getTimeStamp() > $until->getTimeStamp())
			{
				break;
			}
			
			$created->setDates($begin, $end);
			if (isset($flipped_days[$day]))
			{
				$collection->addPeriod($created);
			}
			
			$created = clone $created;
		}
	}
	
	
	/**
	 * Day of week in ICal format
	 * @param	int		$timestamp
	 * @return string
	 */
	private function dayOfWeek($timestamp)
	{
		return strtoupper(substr(date('l', $timestamp), 0,2));
	}
	
	
	
}


