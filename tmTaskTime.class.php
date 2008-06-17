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
		
			if(100 === $this->m_iCompletion && '0000-00-00 00:00:00' !== $sIsoEndDate)
			{
				$this->m_oEndDate = BAB_DateTime::fromIsoDateTime($sIsoEndDate);
			}
			else
			{
				$this->m_oEndDate = $this->m_oStartDate->cloneDate();
				$this->m_oEndDate->add($this->m_oPlannedEndDate->getTimeStamp() - $this->m_oPlannedStartDate->getTimeStamp(), BAB_DATETIME_SECOND);
			}
		}
		
		$oTaskTimeManager	= getTaskTimeManager();
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
		$oTaskTimeManager		= getTaskTimeManager();
		
		$oTodayDate			= $oTaskTimeManager->getTodayIsoDateTime();
		$oRemainEndDate		= null;
		$oRemainStartDate	= null;

		$iDoneDurationInSeconds = $this->m_iEffectiveDurationInSeconds - (($this->m_iCompletion * $this->m_iEffectiveDurationInSeconds) / 100);
//		$fDurationToAdd	= $iDoneDurationInSeconds / $this->m_iNbSeconds;

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
		$sIsoPlannedStartDate	= (string) $aTask['plannedStartDate'];
		$sIsoPlannedEndDate		= (string) $aTask['plannedEndDate'];
		$sIsoStartDate			= (string) $aTask['startDate'];
		$sIsoEndDate			= (string) $aTask['endDate'];
		$this->m_iLinkType		= (int) $aTask['iLinkType'];
		$this->m_iCompletion	= (int) $aTask['iCompletion'];
		$this->m_iIdTask		= (int) $aTask['iIdTask'];
		$this->m_iDurationUnit	= (int) $aTask['iDurationUnit'];
		$this->m_iNbSeconds		= ((BAB_TM_DAY === $this->m_iDurationUnit) ? 86400 : 3600);
		$this->m_fDuration		= (float) $aTask['iDuration'];
		
		$this->m_oPlannedStartDate = BAB_DateTime::fromIsoDateTime($sIsoPlannedStartDate);
		
		//Calcul la durée effective en fonction des jours non travaillés
		$oEffectiveEndDate = $this->m_oPlannedStartDate->cloneDate();
		BAB_TM_TaskTime::computeEndDate($sIsoPlannedStartDate, $this->m_fDuration, $this->m_iDurationUnit, $oEffectiveEndDate);
		$this->m_iEffectiveDurationInSeconds = $oEffectiveEndDate->getTimeStamp() - $this->m_oPlannedStartDate->getTimeStamp();

		
		if('0000-00-00 00:00:00' !== $sIsoStartDate)
		{
			$this->m_oStartDate = BAB_DateTime::fromIsoDateTime($sIsoStartDate);
		
			if(100 === $this->m_iCompletion && '0000-00-00 00:00:00' !== $sIsoEndDate)
			{
				$this->m_oEndDate = BAB_DateTime::fromIsoDateTime($sIsoEndDate);
			}
			else
			{
				BAB_TM_TaskTime::computeEndDate($this->m_oStartDate->getIsoDateTime(), $this->m_fDuration, $this->m_iDurationUnit, $this->m_oEndDate);
			}
		}
		
		$oTaskTimeManager	= getTaskTimeManager();
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
			$this->m_oPlannedEndDate->add($iDurationInSeconds, BAB_DATETIME_SECOND);
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
		$oTaskTimeManager	= getTaskTimeManager();
		$oTodayDate			= $oTaskTimeManager->getTodayIsoDateTime();
		$oRemainEndDate		= null;
		$oRemainStartDate	= null;

		$oStartDate = $this->cloneStartDate();
		$oEndDate	= $oStartDate->cloneDate();
		
		$oEndDate->add($this->m_fDuration);
		
		$iEffectiveDurationInSeconds = $oEndDate->getTimeStamp() - $oStartDate->getTimeStamp();
		$iDurationInSeconds = $iEffectiveDurationInSeconds - (($this->m_iCompletion * $iEffectiveDurationInSeconds) / 100);
		$fDuration =  $iDurationInSeconds / $this->m_iNbSeconds;
		
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