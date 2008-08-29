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

	
	function getWorkingSecondsBetween($oStartDate, $oEndDate)
	{
		require_once($GLOBALS['babInstallPath'] . 'tmCalendar.php');
		require_once($GLOBALS['babInstallPath'] . 'utilit/nwdaysincl.php');
	
		$oTaskTimeManager	=& getTaskTimeManager();
		$oTmCalendar		= bab_tskmgr_getCalendar();

		$oLoopStartDate			= $oStartDate->cloneDate();
		$oLoopEndDate			= $oEndDate->cloneDate();
		$oStartPeriodDateTime	= $oStartDate->cloneDate();			
		$oEndPeriodDateTime		= $oEndDate->cloneDate();			
		
	 	$iYear				= $oLoopStartDate->getYear();
	 	$aNWD				= bab_getNonWorkingDays($iYear);
		$iWorkedSeconds		= 0;
	 	
		while($oLoopStartDate->getTimeStamp() < $oLoopEndDate->getTimeStamp())
	 	{
			if($oLoopStartDate->getYear() > $iYear)
	 		{
	 			$aNWD = bab_getNonWorkingDays($iYear);
	 			$iYear = $oLoopStartDate->getYear();
	 		}
	 		
	 		if(!array_key_exists($oLoopStartDate->getIsoDate(), $aNWD))
	 		{
				$aPeriod = $oTmCalendar->getPeriod($oLoopStartDate->getDayOfWeek());
				
				foreach($aPeriod as $iKey => $oCalendarPeriod)
				{
					$iDayToAdd = 0;
					//Si la start période est supérieure ou égale à la end période
					//alors il faut ajouter 1 jour car c'est le lendemain
					if(!(BAB_TM_PERIOD_BEFORE === $oCalendarPeriod->compare()))
					{
						$iDayToAdd = 1;
					}

					$oStartPeriodDateTime->init($oLoopStartDate->getYear(), $oLoopStartDate->getMonth(),
						$oLoopStartDate->getDayOfMonth(), $oCalendarPeriod->getStartHour(), 
						$oCalendarPeriod->getStartMinut(), $oCalendarPeriod->getStartSecond());			
					
					$oEndPeriodDateTime->init($oLoopStartDate->getYear(), $oLoopStartDate->getMonth(),
						$oLoopStartDate->getDayOfMonth() + $iDayToAdd, $oCalendarPeriod->getEndHour(), 
						$oCalendarPeriod->getEndMinut(), $oCalendarPeriod->getEndSecond());			
					
					if($oLoopStartDate->getTimeStamp() > $oStartPeriodDateTime->getTimeStamp())
					{
						$oStartPeriodDateTime = $oLoopStartDate->cloneDate();
					}
					
					if($oEndPeriodDateTime->getTimeStamp() > $oLoopEndDate->getTimeStamp())
					{
						$oEndPeriodDateTime = $oLoopEndDate->cloneDate();
					}
					
					$iPeriodDurationInSeconds = $oEndPeriodDateTime->getTimeStamp() - $oStartPeriodDateTime->getTimeStamp();
					$iWorkedSeconds += $iPeriodDurationInSeconds;

					/*					
					bab_debug(
						__METHOD__ . ' ' . basename(__FILE__) . '(' . __LINE__ . ') ' . "\n" .
						'sStartDate ' . bab_shortDate($oStartPeriodDateTime->getTimeStamp()) . ' ' . "\n" . 
						'sEndDate   ' . bab_shortDate($oEndPeriodDateTime->getTimeStamp()) . ' ' . "\n" . 
						'           ' . sprintf('%.02f', ($iWorkedSeconds / 86400)) . ' in day(s) ' . "\n" .
						'           ' . sprintf('%.02f', ($iWorkedSeconds / 3600)) . ' in hours(s) ' . "\n" .
						'           ' . $iWorkedSeconds . ' in second(s) ');
					//*/
				}
	 		}

			$oLoopStartDate->init($oLoopStartDate->getYear(), $oLoopStartDate->getMonth(), $oLoopStartDate->getDayOfMonth() + 1, 0, 0, 0);
	 	}
	 	return $iWorkedSeconds;
	}

	
	function computeEndDate($sIsoStartDate, $fDuration, $iDurationUnit, &$oEndDate)
	{
		require_once($GLOBALS['babInstallPath'] . 'tmCalendar.php');
		require_once($GLOBALS['babInstallPath'] . 'utilit/nwdaysincl.php');

		$oTaskTimeManager	=& getTaskTimeManager();
		$oTmCalendar		= bab_tskmgr_getCalendar();
//		bab_debug($oTmCalendar);

		$iOneHourInSeconds				= 3600;
		$iOneDayInSeconds				= 86400;
		$iOneDayOfWorkInSeconds			= $oTaskTimeManager->getWorkedHoursPerDay() * $iOneHourInSeconds;
		$iWorkingTimeToFoundInSeconds	= 0;

		if(BAB_TM_DAY == $iDurationUnit)
		{
			$iWorkingTimeToFoundInSeconds = $fDuration * $iOneDayOfWorkInSeconds;
		}
		else if(BAB_TM_HOUR == $iDurationUnit)
		{
			$iWorkingTimeToFoundInSeconds = $fDuration * $iOneHourInSeconds;
		}
		
	 	$oLoopDate = BAB_dateTime::fromIsoDateTime($sIsoStartDate);
	
$oStartPeriodDateTime = $oLoopDate->cloneDate();			
$oEndPeriodDateTime = $oLoopDate->cloneDate();			
$oEndDate = $oLoopDate->cloneDate();

	 	/*
	 	bab_debug(
			__METHOD__ . ' ' . basename(__FILE__) . '(' . __LINE__ . ') ' . "\n" .
	 		'Before loop working time to found ' . "\n" . 
			'oStart ' . $oLoopDate->getIsoDateTime() . "\n" .
			sprintf('%.02f', ($iWorkingTimeToFoundInSeconds / $iOneDayInSeconds)) . ' in day(s) ' . "\n" .
	 		sprintf('%.02f', ($iWorkingTimeToFoundInSeconds / $iOneHourInSeconds)) . ' in hours(s) ' . "\n" .
	 		$iWorkingTimeToFoundInSeconds . ' in second(s) ');
	 	//*/

		$iRemainingSeconds			= $iWorkingTimeToFoundInSeconds;
		$iPeriodDurationInSeconds	= 0;
		$iWorkedSeconds				= 0;

		$iYear = $oLoopDate->getYear();
		$aNWD = bab_getNonWorkingDays($oLoopDate->getYear());
		
		//Tant que l'on à pas atteint la durée
	 	while($iWorkedSeconds < $iWorkingTimeToFoundInSeconds && $iRemainingSeconds > 0)
	 	{
	 		if($oLoopDate->getYear() > $iYear)
	 		{
	 			$aNWD	= bab_getNonWorkingDays($iYear);
	 			$iYear	= $oLoopDate->getYear();
	 		}
	 		
	 		if(!array_key_exists($oLoopDate->getIsoDate(), $aNWD))
	 		{
	 			$iPeriodDurationInSeconds = 0;
	 			
				$aPeriod = $oTmCalendar->getPeriod($oLoopDate->getDayOfWeek()); 	
				
				foreach($aPeriod as $iKey => $oCalendarPeriod)
				{
					if($iRemainingSeconds > 0 && $iWorkedSeconds < $iWorkingTimeToFoundInSeconds)
					{
						$iDayToAdd = 0;
						//Si la start période est supérieure ou égale à la end période
						//alors il faut ajouter 1 jour car c'est le lendemain
						if(!(BAB_TM_PERIOD_BEFORE === $oCalendarPeriod->compare()))
						{
							$iDayToAdd = 1;
						}

						$oStartPeriodDateTime = new BAB_DateTime($oLoopDate->getYear(), $oLoopDate->getMonth(),
							$oLoopDate->getDayOfMonth(), $oCalendarPeriod->getStartHour(), 
							$oCalendarPeriod->getStartMinut(), $oCalendarPeriod->getStartSecond());			
						
						$oEndPeriodDateTime = new BAB_DateTime($oLoopDate->getYear(), $oLoopDate->getMonth(),
							$oLoopDate->getDayOfMonth() + $iDayToAdd, $oCalendarPeriod->getEndHour(), 
							$oCalendarPeriod->getEndMinut(), $oCalendarPeriod->getEndSecond());			
						
						if($oLoopDate->getTimeStamp() > $oStartPeriodDateTime->getTimeStamp())
						{
							$oStartPeriodDateTime = $oLoopDate;
						}

						$iPeriodDurationInSeconds = $oEndPeriodDateTime->getTimeStamp() - $oStartPeriodDateTime->getTimeStamp();
						
						if($iPeriodDurationInSeconds >= $iRemainingSeconds)
						{
							$iPeriodDurationInSeconds = $iRemainingSeconds;

							$oEndDate = $oStartPeriodDateTime->cloneDate();
							$oEndDate->add($iPeriodDurationInSeconds, BAB_DATETIME_SECOND);
						}
						
						$iRemainingSeconds		-= $iPeriodDurationInSeconds;
						$iWorkedSeconds			+= $iPeriodDurationInSeconds;

						/*
						bab_debug(
							__METHOD__ . ' ' . basename(__FILE__) . '(' . __LINE__ . ') ' . "\n" .
							'oStart            ' . $oStartPeriodDateTime->getIsoDateTime() . "\n" .
							'oEnd              ' . $oEndPeriodDateTime->getIsoDateTime() . "\n" .
							'iRemainingSeconds ' . $iRemainingSeconds . "\n" .
							'iWorkedSeconds    ' . sprintf('%.02f', ($iWorkedSeconds / 86400)) . ' in day(s) ' . "\n" .
							'                  ' . sprintf('%.02f', ($iWorkedSeconds / 3600)) . ' in hours(s) ' . "\n" .
							'                  ' . $iWorkedSeconds . ' in second(s) '
							);
						//*/
					}
				}
			}
			else
			{
//				bab_debug('NWD DETECTED ==> ' . $oLoopDate->getIsoDate());	
			}

			$oLoopDate->init($oLoopDate->getYear(), $oLoopDate->getMonth(), $oLoopDate->getDayOfMonth() + 1, 0, 0, 0);
	 	}

	 	/*
	 	bab_debug(
			__METHOD__ . ' ' . basename(__FILE__) . '(' . __LINE__ . ') ' . "\n" .
	 		'After loop working time found ' . "\n" . 
	 		sprintf('%.02f', ($iWorkedSeconds / 86400)) . ' in day(s) ' . "\n" .
	 		sprintf('%.02f', ($iWorkedSeconds / 3600)) . ' in hours(s) ' . "\n" .
	 		$iWorkedSeconds . ' in second(s) ');
	 	//*/

	 	/*
		$oStartDate = BAB_DateTime::fromIsoDateTime($sIsoStartDate);
		$iWorkSec2 = BAB_TM_TaskTime::getWorkingSecondsBetween($oStartDate, $oEndDate);
		bab_debug(
			__METHOD__ . ' ' . basename(__FILE__) . '(' . __LINE__ . ') ' . "\n" .
			'sStartDate ' . $oStartDate->getIsoDateTime() . ' ' . "\n" . 
			'sEndDate   ' . $oEndDate->getIsoDateTime() . ' ' . "\n" . 
			'           ' . sprintf('%.02f', ($iWorkSec2 / 86400)) . ' in day(s) ' . "\n" .
			'           ' . sprintf('%.02f', ($iWorkSec2 / 3600)) . ' in hours(s) ' . "\n" .
			'           ' . $iWorkSec2 . ' in second(s) ' . "\n" .
			'Test calculation (' . (($iWorkingTimeToFoundInSeconds == $iWorkSec2) ? 'SUCCESS' : 'ERROR') . ')');

//		bab_debug('Apres boucle sStartDate ==> ' . $sIsoStartDate);
//		bab_debug('Apres boucle sEndDate   ==> ' . $oEndDate->getIsoDateTime());
		//*/
	}
//*/

	function computeRemainingDates(&$oRemainStartDate, &$oRemainEndDate)
	{
		if(100 == $this->m_iCompletion)
		{
			$oRemainStartDate = $this->cloneStartDate();			
			$oRemainEndDate = $this->cloneEndDate();

			/*
			bab_debug(
				__METHOD__ . ' ' . basename(__FILE__) . '(' . __LINE__ . ') ' . "\n" .
				'iIdTask           ' . $this->m_iIdTask . ' ' . "\n" . 
				'                  ' . ' ENDED TASK DETECTED ' . "\n" . 
				'sPlannedStartDate ' . bab_shortDate($this->m_oPlannedStartDate->getTimeStamp()) . ' ' . "\n" . 
				'sPlannedEndDate   ' . bab_shortDate($this->m_oPlannedEndDate->getTimeStamp()) . ' ' . "\n" . 
				'sStartDate        ' . bab_shortDate($this->m_oStartDate->getTimeStamp()) . ' ' . "\n" . 
				'sEndDate          ' . bab_shortDate($this->m_oEndDate->getTimeStamp()) . ' ' . "\n" . 

				'sStartDateRet     ' . bab_shortDate($oRemainStartDate->getTimeStamp()) . ' ' . "\n" . 
				'sEndDateRet       ' . bab_shortDate($oRemainEndDate->getTimeStamp()) . ' '
			);
			//*/
			return;

		}

		$oTaskTimeManager	=& getTaskTimeManager();
		$oTodayDate			= $oTaskTimeManager->getTodayIsoDateTime();
		$oRemainEndDate		= null;
		$oRemainStartDate	= null;

		$iIsEqual	= 0;
		$iIsBefore	= -1;
		$iIsAfter	= 1;
		
		if($iIsAfter == BAB_DateTime::compare($this->getStartDate(), $oTodayDate))
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
		
		$iRemainPercent = 100 - $this->m_iCompletion;

		$oStart = null;
		$oEnd = null;
		if(!is_null($this->m_oEndDate))
		{
			$oStart = $this->m_oStartDate;
			$oEnd = $this->m_oEndDate;
		}
		else
		{
			$oStart = $this->m_oPlannedStartDate;
			$oEnd = $this->m_oPlannedEndDate;
		}
		
		//Du début de la tâche jusqu'a fin du taux de completion. (barre noir).
		//Ce calcul ne tient pas compte des jours non travaillés
		$iTaskTimeStamp	= $oEnd->getTimeStamp() - $oStart->getTimeStamp();
		$iDoneTimeStamp = ($iTaskTimeStamp / 100) * $this->m_iCompletion;
		$oDoneStart		= $this->getStartDate()->cloneDate();
		$oDoneEnd		= $this->getStartDate()->cloneDate();
		$oDoneEnd->add($iDoneTimeStamp, BAB_DATETIME_SECOND);

		//Fin de completion jusqu'a la fin de la tâche. (barre blanche).
		//Ce calcul ne tient pas compte des jours non travaillés
		$iToDoTimeStamp	= ($iTaskTimeStamp / 100) * $iRemainPercent;
		$oToDoStart		= $oDoneEnd->cloneDate();
		$oToDoEnd		= $oDoneEnd->cloneDate();
		$oToDoEnd->add($iToDoTimeStamp, BAB_DATETIME_SECOND);

		//Entre la fin de completion et ce qui reste à faire, prise du nombres de secondes
		//ce calcul tient compte des jours non travaillés car à partir de la date du jour
		//il va falloir rajouter ce qui reste à faire et ceci en tennat compte des jours non travaillés
		$iWorkSec1 = BAB_TM_TaskTime::getWorkingSecondsBetween($oToDoStart, $oToDoEnd);
		$fRemainDuration = ($iWorkSec1 / $this->m_iNbSeconds);
		
		//A partir de la date du jour, calcul de ce qui reste à faire en tenant compte des jours non travaillés (barre rose)
		BAB_TM_TaskTime::computeEndDate($oRemainStartDate->getIsoDateTime(), $fRemainDuration, $this->m_iDurationUnit, $oRemainEndDate);
		
		/*
		bab_debug(
			__METHOD__ . ' ' . basename(__FILE__) . '(' . __LINE__ . ') ' . "\n" .
			'iIdTask    ' . $this->m_iIdTask . ' ' . "\n" .
			'sDoneStart ' . $oDoneStart->getIsoDateTime() . ' ' . "\n" .
			'sDoneEnd   ' . $oDoneEnd->getIsoDateTime() . ' ' . "\n" .
			'sToDoStart ' . $oToDoStart->getIsoDateTime() . ' ' . "\n" .
			'sToDoEnd   ' . $oToDoEnd->getIsoDateTime() . ' ' . "\n" .
	 		'           ' . sprintf('%.02f', ($iWorkSec1 / 86400)) . ' in day(s) ' . "\n" .
	 		'           ' . sprintf('%.02f', ($iWorkSec1 / 3600)) . ' in hours(s) ' . "\n" .
			'           ' . $iWorkSec1 . ' in second(s) '
	 	);

		$iWorkSec2 = BAB_TM_TaskTime::getWorkingSecondsBetween($oRemainStartDate, $oRemainEndDate);
		bab_debug(
			__METHOD__ . ' ' . basename(__FILE__) . '(' . __LINE__ . ') ' . "\n" .
			'iIdTask          ' . $this->m_iIdTask . ' ' . "\n" .
			'sRemainStartDate ' . $oRemainStartDate->getIsoDateTime() . ' ' . "\n" .
			'sRemainEndDate   ' . $oRemainEndDate->getIsoDateTime() . ' ' . "\n" .
	 		'                 ' . sprintf('%.02f', ($iWorkSec2 / 86400)) . ' in day(s) ' . "\n" .
	 		'                 ' . sprintf('%.02f', ($iWorkSec2 / 3600)) . ' in hours(s) ' . "\n" .
			'                 ' . $iWorkSec2 . ' in second(s) ' . "\n" .
	 		'                 ' . 'Test calculation (' . (($iWorkSec1 == $iWorkSec2) ? 'SUCCESS' : 'ERROR') . ')');
		//*/
	}
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
			if('0000-00-00 00:00:00' !== $sIsoEndDate)
			{
				$this->m_oEndDate = BAB_DateTime::fromIsoDateTime($sIsoEndDate);
				$this->m_iEffectiveDurationInSeconds = $this->m_oEndDate->getTimeStamp() - $this->m_oStartDate->getTimeStamp();
			}
			else
			{
				//A ce stade la date de début réel est renseignée mais pas la date de fin
				//il faut donc calculer en incluant les non workings days ainsi que les 
				//plages de travail la date de fin théorique

				//Récupére le temp en seconde entre deux dates, tient compte des jours travaillés
				$iWorkingSeconds = BAB_TM_TaskTime::getWorkingSecondsBetween($this->m_oPlannedStartDate, $this->m_oPlannedEndDate);
				
				/*
				$iWorkSec1 = $iWorkingSeconds;
				bab_debug(
					__METHOD__ . ' ' . basename(__FILE__) . '(' . __LINE__ . ') ' . "\n" .
	 				'iIdTask ' . $this->m_iIdTask . ' ' . "\n" . 
					'sStartDate ==> ' . bab_shortDate($this->m_oPlannedStartDate->getTimeStamp()) . ' ' . "\n" . 
					'sEndDate ==> ' . bab_shortDate($this->m_oPlannedEndDate->getTimeStamp()) . ' ' . "\n" . 
					sprintf('%.02f', ($iWorkSec1 / 86400)) . ' in day(s) ' . "\n" .
			 		sprintf('%.02f', ($iWorkSec1 / 3600)) . ' in hours(s) ' . "\n" .
			 		$iWorkSec1 . ' in second(s) ');
				//*/
				
				//Recalcule la date de fin théorique en fonction des jours travaillés
			 	$fDuration = $iWorkingSeconds / $this->m_iNbSeconds;
				BAB_TM_TaskTime::computeEndDate($sIsoStartDate, $fDuration, $this->m_iDurationUnit, $this->m_oEndDate);	
	
				$this->m_iEffectiveDurationInSeconds = $this->m_oEndDate->getTimeStamp() - $this->m_oStartDate->getTimeStamp();

				/*
				//Si le calcul est bon normalement les deux temps (bab_debug en commentaire) doivent être égaux
				$iWorkingSeconds = BAB_TM_TaskTime::getWorkingSecondsBetween($this->m_oStartDate, $this->m_oEndDate);
				
				$iWorkSec2 = $iWorkingSeconds;
				bab_debug(
					__METHOD__ . ' ' . basename(__FILE__) . '(' . __LINE__ . ') ' . "\n" .
					'iIdTask ' . $this->m_iIdTask . ' ' . "\n" . 
					'sStartDate ==> ' . bab_shortDate($this->m_oStartDate->getTimeStamp()) . ' ' . "\n" . 
					'sEndDate ==> ' . bab_shortDate($this->m_oEndDate->getTimeStamp()) . ' ' . "\n" . 
					sprintf('%.02f', ($iWorkSec2 / 86400)) . ' in day(s) ' . "\n" .
			 		sprintf('%.02f', ($iWorkSec2 / 3600)) . ' in hours(s) ' . "\n" .
			 		$iWorkSec2 . ' in second(s) ' . "\n" .
			 		'Test calculation (' . (($iWorkSec1 == $iWorkSec2) ? 'SUCCESS' : 'ERROR') . ')');
				//*/
			}
		}
		
		$iIdPredecessor	= (int) $aTask['iIdPredecessorTask'];

		//Si la tâche à un prédécesseur et qu'elle est liée avec celui-ci
		if(0 !== $iIdPredecessor && BAB_TM_END_TO_START === $this->m_iLinkType)
		{	
			$oRemainEndDate		= null;	
			$oRemainStartDate	= null;
			
			$oTaskTimeManager	=& getTaskTimeManager();
			$aPredecessors		= BAB_TM_TaskTime::getTaskPredecessor($aTask);
			$oGanttTask			= null;
			 
			foreach($aPredecessors as $iIdPredecessor => $aPredecessor)
			{
				$oGanttTask = $oTaskTimeManager->getTask($aPredecessor);
			}
			
			$oGanttTask->computeRemainingDates($oRemainStartDate, $oRemainEndDate);
	
			$iDurationInSeconds = $this->m_oPlannedEndDate->getTimeStamp() - $this->m_oPlannedStartDate->getTimeStamp();
			$this->m_oPlannedStartDate = $oRemainEndDate->cloneDate();
			$fDuration = ($iDurationInSeconds / $this->m_iNbSeconds);

			/*
			bab_debug(
				__METHOD__ . ' ' . basename(__FILE__) . '(' . __LINE__ . ') ' . "\n" .
				'iIdTask    ' . $this->m_iIdTask . ' ' . "\n" . 
				'pred rem sStartDate ' . bab_shortDate($oRemainStartDate->getTimeStamp()) . ' ' . "\n" . 
				'pred rem sEndDate   ' . bab_shortDate($oRemainEndDate->getTimeStamp()) . "\n" .
			 	'           ' . sprintf('%.02f', ($iDurationInSeconds / 86400)) . ' in day(s) ' . "\n" .
			 	'           ' . sprintf('%.02f', ($iDurationInSeconds / 3600)) . ' in hours(s) ' . "\n" .
				'           ' . $iDurationInSeconds . ' in second(s) ');
			//*/
			BAB_TM_TaskTime::computeEndDate($this->m_oPlannedStartDate->getIsoDateTime(), $fDuration, $this->m_iDurationUnit, $this->m_oPlannedEndDate);

			/*
			bab_debug(
				__METHOD__ . ' ' . basename(__FILE__) . '(' . __LINE__ . ') ' . "\n" .
				'iIdTask    ' . $this->m_iIdTask . ' ' . "\n" . 
				'pred rem sStartDate ' . bab_shortDate($oRemainStartDate->getTimeStamp()) . ' ' . "\n" . 
				'pred rem sEndDate   ' . bab_shortDate($oRemainEndDate->getTimeStamp()) . "\n" .
			 	'           ' . sprintf('%.02f', ($iDurationInSeconds / 86400)) . ' in day(s) ' . "\n" .
			 	'           ' . sprintf('%.02f', ($iDurationInSeconds / 3600)) . ' in hours(s) ' . "\n" .
				'           ' . $iDurationInSeconds . ' in second(s) ');
			//*/
		}
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

		$this->m_oPlannedEndDate = $oEffectiveEndDate->cloneDate();
		
		if('0000-00-00 00:00:00' !== $sIsoStartDate)
		{
			$this->m_oStartDate = BAB_DateTime::fromIsoDateTime($sIsoStartDate);
		
			//Avant on pouvait renseigner la date de fin avec un taux de completion < 100
			if('0000-00-00 00:00:00' !== $sIsoEndDate)
			{
				$this->m_oEndDate = BAB_DateTime::fromIsoDateTime($sIsoEndDate);
				$this->m_iEffectiveDurationInSeconds = $this->m_oEndDate->getTimeStamp() - $this->m_oStartDate->getTimeStamp();
			}
			else
			{
				BAB_TM_TaskTime::computeEndDate($this->m_oStartDate->getIsoDateTime(), $this->m_fDuration, $this->m_iDurationUnit, $this->m_oEndDate);
				$this->m_iEffectiveDurationInSeconds = $this->m_oEndDate->getTimeStamp() - $this->m_oStartDate->getTimeStamp();
			}
		}
/*
bab_debug(
	__METHOD__ . ' ' . basename(__FILE__) . '(' . __LINE__ . ') ' . "\n" .
	'iIdTask           ' . $this->m_iIdTask . ' ' . "\n" . 
	'                  ' . ' ENDED TASK DETECTED ' . "\n" . 
	'sPlannedStartDate ' . bab_shortDate($this->m_oPlannedStartDate->getTimeStamp()) . ' ' . "\n" . 
	'sPlannedEndDate   ' . bab_shortDate($this->m_oPlannedEndDate->getTimeStamp()) . ' ' . "\n" . 
	'sStartDate        ' . bab_shortDate($this->m_oStartDate->getTimeStamp()) . ' ' . "\n" . 
	'sEndDate          ' . bab_shortDate($this->m_oEndDate->getTimeStamp())
);
//*/

		$oTaskTimeManager	=& getTaskTimeManager();
		$aPredecessors		= BAB_TM_TaskTime::getTaskPredecessor($aTask);
		$oGanttTask			= null;
		$iIdPredecessor		= (int) $aTask['iIdPredecessorTask'];

		//Si la tâche à un prédécesseur et qu'elle est liée avec celui-ci
		if(0 !== $iIdPredecessor && BAB_TM_END_TO_START === $this->m_iLinkType)
		{
			$oRemainEndDate		= null;
			$oRemainStartDate	= null;
			$oGanttTask			= $oTaskTimeManager->getTask($aPredecessors[$aTask['iIdPredecessorTask']]);

			// Appelé une fois de trop pas sur
			$oGanttTask->computeRemainingDates($oRemainStartDate, $oRemainEndDate);
			$this->m_oPlannedStartDate	= $oRemainEndDate->cloneDate();
			$this->m_oPlannedEndDate	= null;
			BAB_TM_TaskTime::computeEndDate($this->m_oPlannedStartDate->getIsoDateTime(), $this->m_fDuration, $this->m_iDurationUnit, $this->m_oPlannedEndDate);

			/*
			$iWorkSec = BAB_TM_TaskTime::getWorkingSecondsBetween($this->m_oPlannedStartDate, $this->m_oPlannedEndDate);
			bab_debug(
				__METHOD__ . ' ' . basename(__FILE__) . '(' . __LINE__ . ') ' . "\n" .
				'iIdTask           ' . $this->m_iIdTask . ' ' . "\n" . 
				
				'fDuration         ' . $this->m_fDuration . ' ' . "\n" . 

				'sPlannedStartDate ' . bab_shortDate($this->m_oPlannedStartDate->getTimeStamp()) . ' ' . "\n" . 
				'sPlannedEndDate   ' . bab_shortDate($this->m_oPlannedEndDate->getTimeStamp()) . ' ' . "\n" . 

				'                  ' . sprintf('%.02f', ($iWorkSec / 86400)) . ' in day(s) ' . "\n" .
				'                  ' . sprintf('%.02f', ($iWorkSec / 3600)) . ' in hours(s) ' . "\n" .
				'                  ' . $iWorkSec . ' in second(s) ' . "\n" .

				'iIdPredecessor    ' . $oGanttTask->m_iIdTask . ' ' . "\n" . 
				'pred sStartDate   ' . bab_shortDate($oRemainStartDate->getTimeStamp()) . ' ' . "\n" . 
				'pred sEndDate     ' . bab_shortDate($oRemainEndDate->getTimeStamp())
			);
			//*/
		}
		else if(0 !== $iIdPredecessor && BAB_TM_START_TO_START === $this->m_iLinkType)
		{
			$oRemainEndDate		= null;	
			$oRemainStartDate	= null;
			$oGanttTask			= $oTaskTimeManager->getTask($aPredecessors[$aTask['iIdPredecessorTask']]);

			$this->m_oPlannedStartDate	= $oGanttTask->getStartDate();
			$this->m_oPlannedEndDate	= null;
			BAB_TM_TaskTime::computeEndDate($this->m_oPlannedStartDate->getIsoDateTime(), $this->m_fDuration, $this->m_iDurationUnit, $this->m_oPlannedEndDate);
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
}
?>