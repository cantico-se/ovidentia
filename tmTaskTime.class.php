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
include "base.php";


/*
 * @return BAB_TM_TaskTimeManager
 */
function &getTaskTimeManager()
{
	if(!array_key_exists('babTmTaskTimeManager', $GLOBALS))
	{
		$GLOBALS['babTmTaskTimeManager'] = new BAB_TM_TaskTimeManager();
	}
	return $GLOBALS['babTmTaskTimeManager'];
}


class BAB_TM_TaskTimeManager
{
	var $m_aCache	= array();
	var $m_oToday	= null;
	
	var $m_sTodayIsoDate = null;
	var $m_oTodayIsoDateTime = null;
	
	var $m_iWorkedHoursPerDay = 24;
	
	function BAB_TM_TaskTimeManager()
	{
		$this->m_sTodayIsoDate = date("Y-m-d");
		$this->m_oTodayIsoDateTime = BAB_DateTime::fromIsoDateTime(date("Y-m-d H:i:s"));
	}
	
	function getTask($aTask)
	{
		$iIdTask	= (int) $aTask['iIdTask'];
		$iClass		= (int) $aTask['iClass'];
		$fDuration	= (float) $aTask['iDuration'];
		
		if(!array_key_exists($iIdTask, $this->m_aCache))
		{
			$oTaskTime = null;
			if(BAB_TM_TASK === $iClass)
			{
				if(0 < $fDuration)
				{
					$oTaskTime = new BAB_TM_TaskTimeDuration();
				}
				else
				{
					$oTaskTime = new BAB_TM_TaskTimeDate();
				}
			}
			else
			{
				$oTaskTime = new BAB_TaskTimeToDoCheckPoint();	
			}
			
			if(!is_null($oTaskTime))
			{
				$oTaskTime->init($aTask);
				$this->m_aCache[$iIdTask] = $oTaskTime;
			}
		}
		return $this->m_aCache[$iIdTask];
	}
	
	function getTodayIsoDateString()
	{
		return $this->m_sTodayIsoDate;
	}
	
	function getTodayIsoDateTime()
	{
		return $this->m_oTodayIsoDateTime;
	}
	
	function getWorkedHoursPerDay()
	{
		return $this->m_iWorkedHoursPerDay;
	}
}


class BAB_TM_TaskTime
{
	var $m_oPlannedStartDate			= null;
	var $m_oPlannedEndDate				= null;
	var $m_oStartDate					= null;
	var $m_oEndDate						= null;
	var $m_iIdTask						= 0;
	
	//Depend on task duration unit (86400 for a day, 3600 for an hour)
	var $m_iNbSeconds					= 0;
	var $m_iEffectiveDurationInSeconds	= 0;
	var	$m_iDurationUnit				= -1;
	var	$m_fDuration					= 0.00;
	var $m_iCompletion					= 0;
	var $m_iLinkType					= -1;
	
	function BAB_TM_TaskTime()
	{
	
	}
	
	function init($aTask)
	{
	
	}
	
	function getStartDate()
	{
		return ((!is_null($this->m_oStartDate)) ? $this->m_oStartDate : $this->m_oPlannedStartDate);
	}
	
	function getEndDate()
	{
		return ((!is_null($this->m_oEndDate)) ? $this->m_oEndDate : $this->m_oPlannedEndDate);
	}
	
	function cloneStartDate()
	{
		return ((!is_null($this->m_oStartDate)) ? $this->m_oStartDate->cloneDate() : $this->m_oPlannedStartDate->cloneDate());
	}
	
	function cloneEndDate()
	{
		return ((!is_null($this->m_oEndDate)) ? $this->m_oEndDate->cloneDate() : $this->m_oPlannedEndDate->cloneDate());
	}
	
	function getStartDateTimeStamp()
	{
		return ((!is_null($this->m_oStartDate)) ? $this->m_oStartDate->getTimeStamp() : $this->m_oPlannedStartDate->getTimeStamp());
	}
	
	function getEndDateTimeStamp()
	{
		return ((!is_null($this->m_oEndDate)) ? $this->m_oEndDate->getTimeStamp() : $this->m_oPlannedEndDate->getTimeStamp());
	}
	
	function getTaskPredecessor($aTask)
	{
		$iIdPredecessor	= (int) $aTask['iIdPredecessorTask'];
		$aPredecessors	= array();
		
		//Si la tâche à un prédécesseur et qu'elle est liée avec celui-ci
		if(0 !== $iIdPredecessor)
		{
			$bHavePredecessor	= true;
			$aPredecessors		= array();
			$aPredecessor		= array();
			
			while($bHavePredecessor)
			{
				if(true === bab_getTaskForGantt($iIdPredecessor, $aPredecessor))
				{
					$aPredecessors[$iIdPredecessor] = $aPredecessor;
					$iIdPredecessor	= (int) $aPredecessor['iIdPredecessorTask'];
					$bHavePredecessor = (0 !== $iIdPredecessor);
				}
				else
				{
					$bHavePredecessor = false;
				}
			}
			$aPredecessors = array_reverse($aPredecessors, true);
		}
		return $aPredecessors;
	}
	
/*
	function computeEndDate($sIsoStartDate, $fDuration, $iDurationUnit, &$oEndDate)
	{
		require_once($GLOBALS['babInstallPath'] . 'tmCalendar.php');
		require_once($GLOBALS['babInstallPath'] . 'utilit/nwdaysincl.php');
	
		$oTaskTimeManager	=& getTaskTimeManager();
		$oTmCalendar		= bab_tskmgr_getCalendar();
		
//		bab_debug($oTmCalendar);

		$iOneHourInSeconds		= 3600;
//		$iHoursWorkedPerDay		= 7;
//		$iHoursWorkedPerDay		= 24;
		$iOneDayOfWorkInSeconds	= $oTaskTimeManager->getWorkedHoursPerDay() * $iOneHourInSeconds;
		
		
//		$iWorkingTimeToFoundInSeconds = $fDuration * $iOneDayOfWorkInSeconds;
		$iWorkingTimeToFoundInSeconds = 0;
		if(BAB_TM_DAY == $iDurationUnit)
		{
			$iWorkingTimeToFoundInSeconds = $fDuration * $iOneDayOfWorkInSeconds;
		}
		else if(BAB_TM_HOUR == $iDurationUnit)
		{
			$iWorkingTimeToFoundInSeconds = $fDuration * $iOneHourInSeconds;
		}
		
	
		//Que faire si la tâche démarre avant ce qui est spécifié dans le calendrier ?
	 	$oLoopStartDate = BAB_dateTime::fromIsoDateTime($sIsoStartDate);
	 	$oLoopEndDate = $oLoopStartDate->cloneDate();
	
	 	bab_debug('Before loop working time to found ' . ($iWorkingTimeToFoundInSeconds / 86400));
		
		$iWorkedSeconds				= 0;
		$iRemainingSeconds			= $iWorkingTimeToFoundInSeconds;
		$iPeriodDurationInSeconds	= 0;
		$oStartPeriodDateTime 		= null;
		$oEndPeriodDateTime 		= null;
		
		$aNWD = bab_getNonWorkingDays($oLoopStartDate->getYear());
		
//		$aNWD['2008-07-16'] = 1;
//		$aNWD['2008-07-17'] = 1;
		
//		bab_debug($aNWD);
		//Tant que l'on à pas atteint la durée
	 	while($iWorkedSeconds < $iWorkingTimeToFoundInSeconds)
	 	{
	 		$oLoopEndDate->init($oLoopStartDate->getYear(), $oLoopStartDate->getMonth(), $oLoopStartDate->getDayOfMonth(), 23, 59, 59);
	 		
	 		//bab_debug($oLoopStartDate->getIsoDateTime() . ' ' . $oLoopEndDate->getIsoDateTime());
	
	 		if(!array_key_exists($oLoopStartDate->getIsoDate(), $aNWD))
	 		{
	 			$iPeriodDurationInSeconds	= 0;
	 			
				$aPeriod = $oTmCalendar->getPeriod($oLoopStartDate->getDayOfWeek()); 	
				
				foreach($aPeriod as $iKey => $oCalendarPeriod)
				{
					if($iRemainingSeconds > 0)
					{
						$oStartPeriodDateTime = new BAB_DateTime($oLoopStartDate->getYear(),	$oLoopStartDate->getMonth(),
							$oLoopStartDate->getDayOfMonth(), $oCalendarPeriod->iStartHour, $oCalendarPeriod->iStartMinut,
							00);			
						
						$oEndPeriodDateTime = new BAB_DateTime($oLoopStartDate->getYear(),	$oLoopStartDate->getMonth(),
							$oLoopStartDate->getDayOfMonth(), $oCalendarPeriod->iEndHour, $oCalendarPeriod->iEndMinut,
							59);			
						
						if($oLoopEndDate->getTimeStamp() > $oStartPeriodDateTime->getTimeStamp() && $oLoopStartDate->getTimeStamp() < $oEndPeriodDateTime->getTimeStamp())
						{
							//bab_debug('Intersection FOUND ' . $oLoopStartDate->getIsoDateTime() . ' ' . $oLoopEndDate->getIsoDateTime());	
		
							if($oLoopStartDate->getTimeStamp() > $oStartPeriodDateTime->getTimeStamp())
							{
								$oStartPeriodDateTime = $oLoopStartDate;
							}
							
							if($oLoopEndDate->getTimeStamp() < $oEndPeriodDateTime->getTimeStamp())
							{
								$oEndPeriodDateTime = $oLoopEndDate;
							}
							
							$iPeriodDurationInSeconds = $oEndPeriodDateTime->getTimeStamp() - $oStartPeriodDateTime->getTimeStamp();
							
							if($iPeriodDurationInSeconds > $iRemainingSeconds)
							{
								$iPeriodDurationInSeconds = $iRemainingSeconds;
							}
							
							$iRemainingSeconds -= $iPeriodDurationInSeconds;
							$iWorkedSeconds += $iPeriodDurationInSeconds;
		
							//bab_debug(
							//	' oStart ==> ' . $oStartPeriodDateTime->getIsoDateTime() .
							//	' oEnd ==> ' . $oEndPeriodDateTime->getIsoDateTime() .
							//	' iWorkedSeconds ==> ' . $iPeriodDurationInSeconds .
							//	' iWorkedSecondsInHour ==> ' . ($iPeriodDurationInSeconds / 3600));
						}
					}
				}
			}
			else
			{
//				bab_debug('NWD DETECTED ==> ' . $oLoopStartDate->getIsoDate());	
			}
			
	 		$oLoopStartDate = $oLoopEndDate->cloneDate();
	 		$oLoopStartDate->add(1, BAB_DATETIME_SECOND);
	 	}
	 	
	 	$oEndDate	= $oStartPeriodDateTime->cloneDate();
	 	$oEndDate->add($iPeriodDurationInSeconds, BAB_DATETIME_SECOND);
	 	
		bab_debug('WorkedTime => ' . $iWorkedSeconds . ' in hours ==> ' . sprintf('%.02f', ($iWorkedSeconds / 3600)));
		bab_debug('Apres boucle ' . $oEndDate->getIsoDateTime());
	}
//*/	
	
//*
	function computeEndDate($sIsoStartDate, $fDuration, $iDurationUnit, &$oEndDate)
	{
		require_once $GLOBALS['babInstallPath'] . 'utilit/nwdaysincl.php';
		require_once $GLOBALS['babInstallPath'] . 'utilit/calapi.php';
		
		$sWorkingDays = '';
		$iIdUser = 0; //configuration du site
		bab_calGetWorkingDays($iIdUser, $sWorkingDays);
		$aWorkingDays = array_flip(explode(',', $sWorkingDays));
		
		$fRemain = 0;
		
		if($iDurationUnit === BAB_TM_DAY)
		{
			//Arrondi au superieur donc on va boucler une fois de trop si le reste est > à zéro
			$iDuration	= (int) ceil($fDuration);
			$fRemain	= ($fDuration - (int) $fDuration);			
		}
		else
		{
			$iDuration = (int) 1;
		}
		
		$oStartDate			= BAB_DateTime::fromIsoDateTime($sIsoStartDate);
		$oEndDate			= BAB_DateTime::fromIsoDateTime($sIsoStartDate);
		$iWorkingDaysCount	= count($aWorkingDays);

		do
		{
			$aNWD = bab_getNonWorkingDaysBetween($oStartDate->getTimeStamp(), $oEndDate->getTimeStamp());
			if(isset($aWorkingDays[$oEndDate->getDayOfWeek()]) && 0 == count($aNWD))
			{
				$oEndDate->add(1);
			}
			else 
			{
				$oEndDate->add(2);
			}
			$iDuration--;	
		}
		while(0 < $iDuration && $iWorkingDaysCount > 0);
		
		if($iDurationUnit === BAB_TM_DAY)
		{
			if($fRemain > 0)
			{
				//86400 nombre de secondes dans une journée
				//On retranche une journée car à cause du floor qui arrondi au 
				//superieure on a bouclé une fois de trop
				$oEndDate->add((86400 * $fRemain) - 86400, BAB_DATETIME_SECOND);

				if(!isset($aWorkingDays[$oEndDate->getDayOfWeek()]))
				{
					//echo 'Ce jour n\'est pas un jour travaillé ==> ' . $oEndDate->getIsoDateTime() . '<br />';
					
					$iNbDays = 0;
					BAB_TM_TaskTime::getGapToFirstWorkingDay($oEndDate, $aWorkingDays, BAB_DATETIME_DAY, $iNbDays);
					$oEndDate->add($iNbDays, BAB_DATETIME_DAY);
				}
			}
		}
		else 
		{
			//3600 nombre de secondes dans une heure
			$oEndDate->add(3600 * $fDuration, BAB_DATETIME_SECOND);
				
			if(!isset($aWorkingDays[$oEndDate->getDayOfWeek()]))
			{
				//echo 'Ce jour n\'est pas un jour travaillé ==> ' . $oEndDate->getIsoDateTime() . '<br />';
				
				$iNbHours = 0;
				BAB_TM_TaskTime::getGapToFirstWorkingDay($oEndDate, $aWorkingDays, BAB_DATETIME_HOUR, $iNbHours);
				$oEndDate->add($iNbHours, BAB_DATETIME_HOUR);
			}
		}
//		echo __FUNCTION__ . ' sStartDate ==> ' . $sIsoStartDate . ' sEndDate ==> ' . $oEndDate->getIsoDateTime();
	}
	
	function getGapToFirstWorkingDay($oFromDate, $aWorkingDays, $iDurationUnit, &$iNbDays)
	{
		$oStartDate	= $oFromDate->cloneDate();
		
		$iNbDays = 0;
		if(is_array($aWorkingDays) && count($aWorkingDays) > 0)
		{
			$bFound = false;
			
			do 
			{
				if(isset($aWorkingDays[$oStartDate->getDayOfWeek()]))
				{
					$bFound = true;
				}
				else
				{
					$iNbDays++;
					$oStartDate->add(1, $iDurationUnit);
				}
			}
			while(false === $bFound);
		}
	}
//*/
}

class BAB_TaskTimeToDoCheckPoint extends BAB_TM_TaskTime
{
	function BAB_TaskTimeToDoCheckPoint()
	{
		parent::BAB_TM_TaskTime();
	}

	function init($aTask)
	{
		$sIsoPlannedStartDate	= (string) $aTask['plannedStartDate'];
		$sIsoPlannedEndDate		= (string) $aTask['plannedEndDate'];
		$sIsoStartDate			= (string) $aTask['startDate'];
		$sIsoEndDate			= (string) $aTask['endDate'];
		$this->m_iIdTask		= (int) $aTask['iIdTask'];

		$this->m_oPlannedStartDate	= BAB_DateTime::fromIsoDateTime($sIsoPlannedStartDate);
		$this->m_oPlannedEndDate	= BAB_DateTime::fromIsoDateTime($sIsoPlannedEndDate);
		$this->m_oStartDate			= BAB_DateTime::fromIsoDateTime($sIsoStartDate);
		$this->m_oEndDate			= BAB_DateTime::fromIsoDateTime($sIsoEndDate);
	}

	function computeRemainingDates(&$oRemainStartDate, &$oRemainEndDate)
	{
		$oRemainStartDate	= $this->m_oPlannedStartDate->cloneDate();
		$oRemainEndDate		= $this->m_oPlannedEndDate->cloneDate();
	}
}


class BAB_TM_TaskTimeDate extends BAB_TM_TaskTime
{
	function BAB_TM_TaskTimeDate()
	{
		parent::BAB_TM_TaskTime();
	}

	function init($aTask)
	{
		$sIsoPlannedStartDate	= (string) $aTask['plannedStartDate'];
		$sIsoPlannedEndDate		= (string) $aTask['plannedEndDate'];
		$sIsoStartDate			= (string) $aTask['startDate'];
		$sIsoEndDate			= (string) $aTask['endDate'];
		$this->m_iLinkType		= (int) $aTask['iLinkType'];
		$this->m_iCompletion	= (int) $aTask['iCompletion'];
		$this->m_iIdTask		= (int) $aTask['iIdTask'];
		
		$this->m_iNbSeconds						= 86400;
		$this->m_iDurationUnit					= BAB_TM_DAY;
		
		$this->m_oPlannedStartDate				= BAB_DateTime::fromIsoDateTime($sIsoPlannedStartDate);
		$this->m_oPlannedEndDate				= BAB_DateTime::fromIsoDateTime($sIsoPlannedEndDate);
		$this->m_iEffectiveDurationInSeconds	= $this->m_oPlannedEndDate->getTimeStamp() - $this->m_oPlannedStartDate->getTimeStamp();
		
		if('0000-00-00 00:00:00' !== $sIsoStartDate)
		{
			$this->m_oStartDate = BAB_DateTime::fromIsoDateTime($sIsoStartDate);
		
			//Avant on pouvait renseigner la date de fin avec un taux de completion < 100
//			if(100 === $this->m_iCompletion && '0000-00-00 00:00:00' !== $sIsoEndDate)
			if(100 === $this->m_iCompletion || '0000-00-00 00:00:00' !== $sIsoEndDate)
			{
				$this->m_oEndDate = BAB_DateTime::fromIsoDateTime($sIsoEndDate);
			}
			else
			{
				$this->m_oEndDate = $this->m_oStartDate->cloneDate();
				$this->m_oEndDate->add($this->m_oPlannedEndDate->getTimeStamp() - $this->m_oPlannedStartDate->getTimeStamp(), BAB_DATETIME_SECOND);
			}
		}
		
		$oTaskTimeManager	=& getTaskTimeManager();
		$aPredecessors		= BAB_TM_TaskTime::getTaskPredecessor($aTask);
		$oGanttTask			= null;
		 
		foreach($aPredecessors as $iIdPredecessor => $aPredecessor)
		{
			$oGanttTask = $oTaskTimeManager->getTask($aPredecessor);
		}
		
		$oRemainEndDate	= null;	
		$oRemainStartDate = null;
		
		$iIdPredecessor	= (int) $aTask['iIdPredecessorTask'];

		//Si la tâche à un prédécesseur et qu'elle est liée avec celui-ci
		if(0 !== $iIdPredecessor && BAB_TM_END_TO_START === $this->m_iLinkType)
		{	
			$oGanttTask->computeRemainingDates($oRemainStartDate, $oRemainEndDate);
	
			$iDurationInSeconds = $this->m_oPlannedEndDate->getTimeStamp() - $this->m_oPlannedStartDate->getTimeStamp();
			$this->m_oPlannedStartDate = $oRemainEndDate->cloneDate();
			$this->m_oPlannedEndDate = $this->m_oPlannedStartDate->cloneDate();
			$this->m_oPlannedEndDate->add($iDurationInSeconds, BAB_DATETIME_SECOND);
		}
	}

	function computeRemainingDates(&$oRemainStartDate, &$oRemainEndDate)
	{
		$oTaskTimeManager	=& getTaskTimeManager();
		
		$oTodayDate			= $oTaskTimeManager->getTodayIsoDateTime();
		$oRemainEndDate		= null;
		$oRemainStartDate	= null;

		$iDoneDurationInSeconds = $this->m_iEffectiveDurationInSeconds - (($this->m_iCompletion * $this->m_iEffectiveDurationInSeconds) / 100);

		$iIsEqual	= 0;
		$iIsBefore	= -1;
		$iIsAfter	= 1;
		
		if(100 == $this->m_iCompletion || $iIsAfter == BAB_DateTime::compare($this->getStartDate(), $oTodayDate))
		{
			//echo ("La date de début est supérieure à la date de fin");
			$oRemainStartDate = $this->cloneStartDate();
		}
		else 
		{
			//echo ("La date de début est inferieur à la date de fin");
			$oRemainStartDate = $oTodayDate->cloneDate();
		}
		$oRemainEndDate = $oRemainStartDate->cloneDate();
		$oRemainEndDate->add($iDoneDurationInSeconds, BAB_DATETIME_SECOND);
	}
}


class BAB_TM_TaskTimeDuration extends BAB_TM_TaskTime
{
	function BAB_TM_TaskTimeDuration()
	{
		parent::BAB_TM_TaskTime();
	}

	function init($aTask)
	{
		$oTaskTimeManager		=& getTaskTimeManager();
		
		$sIsoPlannedStartDate	= (string) $aTask['plannedStartDate'];
		$sIsoPlannedEndDate		= (string) $aTask['plannedEndDate'];
		$sIsoStartDate			= (string) $aTask['startDate'];
		$sIsoEndDate			= (string) $aTask['endDate'];
		$this->m_iLinkType		= (int) $aTask['iLinkType'];
		$this->m_iCompletion	= (int) $aTask['iCompletion'];
		$this->m_iIdTask		= (int) $aTask['iIdTask'];
		$this->m_iDurationUnit	= (int) $aTask['iDurationUnit'];
		$this->m_iNbSeconds		= ((BAB_TM_DAY === $this->m_iDurationUnit) ? (3600 * $oTaskTimeManager->getWorkedHoursPerDay()) : 3600);
		$this->m_fDuration		= (float) $aTask['iDuration'];
		
		$this->m_oPlannedStartDate = BAB_DateTime::fromIsoDateTime($sIsoPlannedStartDate);
		
		//Calcul la durée effective en fonction des jours non travaillés
		$oEffectiveEndDate = $this->m_oPlannedStartDate->cloneDate();
		BAB_TM_TaskTime::computeEndDate($sIsoPlannedStartDate, $this->m_fDuration, $this->m_iDurationUnit, $oEffectiveEndDate);
		$this->m_iEffectiveDurationInSeconds = $oEffectiveEndDate->getTimeStamp() - $this->m_oPlannedStartDate->getTimeStamp();

		
		if('0000-00-00 00:00:00' !== $sIsoStartDate)
		{
			$this->m_oStartDate = BAB_DateTime::fromIsoDateTime($sIsoStartDate);
		
			//Avant on pouvait renseigner la date de fin avec un taux de completion < 100
//			if(100 === $this->m_iCompletion && '0000-00-00 00:00:00' !== $sIsoEndDate)
			if(100 === $this->m_iCompletion || '0000-00-00 00:00:00' !== $sIsoEndDate)
			{
				$this->m_oEndDate = BAB_DateTime::fromIsoDateTime($sIsoEndDate);
			}
			else
			{
				BAB_TM_TaskTime::computeEndDate($this->m_oStartDate->getIsoDateTime(), $this->m_fDuration, $this->m_iDurationUnit, $this->m_oEndDate);
			}
		}
		
		$oTaskTimeManager	=& getTaskTimeManager();
		$aPredecessors		= BAB_TM_TaskTime::getTaskPredecessor($aTask);
		$oGanttTask			= null;
		$iIdPredecessor		= (int) $aTask['iIdPredecessorTask'];
		
		//Si la tâche à un prédécesseur et qu'elle est liée avec celui-ci
		if(0 !== $iIdPredecessor && BAB_TM_END_TO_START === $this->m_iLinkType)
		{
			$oRemainEndDate	= null;	
			$oRemainStartDate = null;
			$oGanttTask = $oTaskTimeManager->getTask($aPredecessors[$aTask['iIdPredecessorTask']]);

			$oGanttTask->computeRemainingDates($oRemainStartDate, $oRemainEndDate); // Appelé une fois de trop
		
			$iDurationInSeconds = $this->m_iEffectiveDurationInSeconds;
			
			$sIsoPlannedEndDate	= (string) $aTask['plannedEndDate'];
			$this->m_oPlannedStartDate = $oRemainEndDate->cloneDate();
			$this->m_oPlannedEndDate = BAB_DateTime::fromIsoDateTime($sIsoPlannedEndDate);
			
			$this->m_oPlannedEndDate = $this->m_oPlannedStartDate->cloneDate();
			BAB_TM_TaskTime::computeEndDate($this->m_oPlannedEndDate->getIsoDateTime(), $this->m_fDuration, $this->m_iDurationUnit, $this->m_oPlannedEndDate);
		}
		else if(0 !== $iIdPredecessor && BAB_TM_START_TO_START === $this->m_iLinkType)
		{
			$oRemainEndDate	= null;	
			$oRemainStartDate = null;
			$oGanttTask = $oTaskTimeManager->getTask($aPredecessors[$aTask['iIdPredecessorTask']]);

			$this->m_oPlannedStartDate = $oGanttTask->getStartDate();
			$this->m_oPlannedEndDate = $this->m_oPlannedStartDate->cloneDate();
			BAB_TM_TaskTime::computeEndDate($this->m_oPlannedEndDate->getIsoDateTime(), $this->m_fDuration, $this->m_iDurationUnit, $this->m_oPlannedEndDate);
		}
		else 
		{
			$sIsoPlannedEndDate	= (string) $aTask['plannedEndDate'];
			if('0000-00-00 00:00:00' !== $sIsoPlannedEndDate)
			{
				$this->m_oPlannedEndDate = BAB_DateTime::fromIsoDateTime($sIsoPlannedEndDate);
			}
			else
			{
				BAB_TM_TaskTime::computeEndDate($sIsoPlannedStartDate, $this->m_fDuration, $this->m_iDurationUnit, $this->m_oPlannedEndDate);
			}
		}
	}

	function computeRemainingDates(&$oRemainStartDate, &$oRemainEndDate)
	{
		$oTaskTimeManager	=& getTaskTimeManager();
		$oTodayDate			= $oTaskTimeManager->getTodayIsoDateTime();
		$oRemainEndDate		= null;
		$oRemainStartDate	= null;

		$oStartDate = $this->cloneStartDate();
		$oEndDate	= $oStartDate->cloneDate();
		
		$oEndDate->add( ($this->m_fDuration * $this->m_iNbSeconds) , BAB_DATETIME_SECOND);

		$iEffectiveDurationInSeconds	= $oEndDate->getTimeStamp() - $oStartDate->getTimeStamp();
		$iDurationInSeconds				= $iEffectiveDurationInSeconds - (($this->m_iCompletion * $iEffectiveDurationInSeconds) / 100);
		$fDuration						= $iDurationInSeconds / $this->m_iNbSeconds;

		//Si iDurationInSeconds = 0 c'est que la tache est terminé (il y a une date de fin)
		if(0 === $iDurationInSeconds)
		{
$oRemainStartDate = $this->cloneStartDate();			
$oRemainEndDate = $this->cloneEndDate();
return;			
		}
		
		$iIsEqual	= 0;
		$iIsBefore	= -1;
		$iIsAfter	= 1;
		
		if(100 == $this->m_iCompletion || $iIsAfter == BAB_DateTime::compare($oStartDate, $oTodayDate))
		{
			//$babBody->addError(gPrd_translate("La date de début est supérieure à la date de fin"));
			$oRemainStartDate = $oStartDate;
		}
		else 
		{
			$oRemainStartDate = $oTodayDate->cloneDate();
		}
		
		BAB_TM_TaskTime::computeEndDate($oRemainStartDate->getIsoDateTime(), $fDuration, $this->m_iDurationUnit, $oRemainEndDate);
	}
}
?>