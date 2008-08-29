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
require_once($babInstallPath . 'utilit/dateTime.php');
require_once($babInstallPath . 'tmTaskTime.class.php');


class BAB_TM_GanttLegend
{
	var $aLegend		= null;
	
	var $sTitle			= '';
	var $sCaption		= '';
	var $sClassName		= '';
	
	function BAB_TM_GanttLegend()
	{
		$this->aLegend = array(
			array('sTitle' => bab_translate("Plannified load"), 'sCaption' => bab_translate("Plannified load"), 'sClassName' => 'ganttPlannified'),
			array('sTitle' => bab_translate("Real load"), 'sCaption' => bab_translate("Real load"), 'sClassName' => 'ganttReal'),
			array('sTitle' => bab_translate("Completion rate"), 'sCaption' => bab_translate("Completion rate"), 'sClassName' => 'ganttCompletion'),
			array('sTitle' => bab_translate("To achieve"), 'sCaption' => bab_translate("To achieve"), 'sClassName' => 'ganttRemaining'),
			array('sTitle' => bab_translate("Effective load"), 'sCaption' => bab_translate("Effective load"), 'sClassName' => 'ganttEffectiveLoad'),
		);
	}

	function getNextLegendItem() 
	{
		$aItem = each($this->aLegend);
		if(false !== $aItem)
		{
			$this->sTitle		= $aItem['value']['sTitle'];
			$this->sCaption		= $aItem['value']['sCaption'];
			$this->sClassName	= $aItem['value']['sClassName'];
			return true;
		}
		return false;
	}
	
	function getHtml()
	{
		return bab_printTemplate($this, 'tmUser.html', 'ganttLegend');
	}
}

function initFilter()
{
	$iIdProject	= (int) bab_rp('iIdProject', -1);
	$sKey		= (0 === $iIdProject) ? 'tskMgrPersonnalFilter' : 'tskMgrProjectFilter';

	$oFilterSessionContext = new BAB_TM_SessionContext($sKey); 
			
	$iTaskClass			= (int) $oFilterSessionContext->get('iTaskClass', -1);
	$iTaskCompletion	= (int) $oFilterSessionContext->get('iTaskCompletion', -1);
	$iIdOwner			= (int) $oFilterSessionContext->get('iIdOwner', 0);
	$iIdProject			= (int) $oFilterSessionContext->get('iIdProject', -1);
	$sStartDate			= (string) $oFilterSessionContext->get('sStartDate', '');			
	$iStartHour			= (int) $oFilterSessionContext->get('iStartHour', 0);
	$iStartMinut		= (int) $oFilterSessionContext->get('iStartMinut', 0);
	$sEndDate			= (string) $oFilterSessionContext->get('sEndDate', '');
	$iEndHour			= (int) $oFilterSessionContext->get('iEndHour', 0);
	$iEndMinut			= (int) $oFilterSessionContext->get('iEndMinut', 0);
	$sPlannedStartDate	= (string) $oFilterSessionContext->get('sPlannedStartDate', '');
	$iPlannedStartHour	= (int) $oFilterSessionContext->get('iPlannedStartHour', 0);
	$iPlannedStartMinut = (int) $oFilterSessionContext->get('iPlannedStartMinut', 0);
	$sPlannedEndDate	= (string) $oFilterSessionContext->get('sPlannedEndDate', '');
	$iPlannedEndHour	= (int) $oFilterSessionContext->get('iPlannedEndHour', 0);
	$iPlannedEndMinut	= (int) $oFilterSessionContext->get('iPlannedEndMinut', 0);
			
	
	
}


class BAB_TM_GanttBase
{
//	var $m_iWidth = '14';
//	var $m_iHeight = '26';

	var $m_iWidth = '18';
	var $m_iHeight = '32';

/*
	var $m_iWidth = '52';
	var $m_iHeight = '60';
//*/

	//m_iWidth = 1 day = 86400 secondes
	//86400 / m_iWidth
	var $m_iOnePxInSecondes;
	
	var $m_iBorderLeft = 0;
	var $m_iBorderRight = 0;
	var $m_iBorderTop = 0;
	var $m_iBorderBottom = 0;

	
	var $m_iGanttHeaderPosX = 0;
	var $m_iGanttHeaderPosY = 0;
	var $m_iGanttHeaderHeight = 0;
	var $m_iGanttHeaderWidth = 0;
	
	var $m_iGanttTasksPosX = 0;
	var $m_iGanttTasksPosY = 0;
	var $m_iGanttTasksHeight = 0;
	var $m_iGanttTasksWidth = 0;
	
	var $m_iGanttTasksListPosX = 0;
	var $m_iGanttTasksListPosY = 0;
	var $m_iGanttTasksListHeight = 0;
	var $m_iGanttTasksListWidth = 0;
	
	var $m_iGanttViewPosX = 0;
	var $m_iGanttViewPosY = 0;
	var $m_iGanttViewHeight = 0;
	var $m_iGanttViewWidth = 0;
	
	var $m_iMonthPosX = 0;
	var $m_iMonthPosY = 0;
	var $m_iMonthWidth = 0;
	var $m_iMonthHeigth = 0;
	var $m_sMonth = '';
	var $m_iCurrMonth = -1;
	
	//Used in getmonth
	var $m_iYear = 0;
	
	var $m_iWeekPosX = 0;
	var $m_iWeekPosY = 0;
	var $m_iWeekHeigth = 0;
	var $m_iWeekWidth = 0;
	var $m_sWeek = '';
	var $m_iWeekNumber = 0;
	var $m_iStartWeekNumber = 0;
	var $m_iEndWeekNumber = 0;
	
	var $m_iDayPosX = 0;
	var $m_iDayPosY = 0;
	var $m_iDayHeigth = 0;
	var $m_iDayWidth = 0;
	var $m_sDay = '';
	var $m_iStartWeekDay = -1;
	var $m_iMonthDay = -1;
	var $m_iCurrDay = -1;
	var $m_iTotalDaysToDisplay = 49;
	var $m_iDisplayedDays = 0;

	var $m_iTaskCaptionWidth = 200;

	var $m_iTaskTitlePosX = 0;
	var $m_iTaskTitlePosY = 0;
	var $m_iTaskTitleHeigth = 0;
	var $m_iTaskTitleWidth = 0;
	var $m_sTaskTitleColor = 'FFF';
	var $m_sTaskTitle = '';
	
	var $m_iTaskInfoPosX = 0;
	var $m_iTaskInfoPosY = 0;
	var $m_iTaskInfoHeigth = 0;
	var $m_iTaskInfoWidth = 0;
	var $m_sTaskInfoBgColor = '787878';
	var $m_sTaskInfoColor = 'FFF';
	var $m_sTaskInfo = '';
	
	var $m_aDisplayedStartDate = array();
	var $m_aDisplayedEndDate = array();
	
	var $m_result = false;
	var $m_iNbResult = 0;
	
	var $m_iTimeStamp;
	
	var $m_iColumnPosX = 0;
	var $m_iColumnPosY = 0;
	var $m_iColumnHeigth = 0;
	var $m_iColumnWidth = 0;
	
	var $m_iRowPosX = 0;
	var $m_iRowPosY = 0;
	var $m_iRowHeigth = 0;
	var $m_iRowWidth = 0;
	
	var $m_sPrevMonth = "";
	var $m_sPrevWeek = "";
	var $m_sNextWeek = "";
	var $m_sNextMonth = "";
	var $m_sGotoDate = "";
	var $m_sToday = "";
	var $m_sPrevMonthUrl = "";
	var $m_sPrevWeekUrl = "";
	var $m_sNextWeekUrl = "";
	var $m_sNextMonthUrl = "";
	var $m_sGotoDateUrl = "";
	var $m_sTodayUrl = "";
	
	
	var $m_iNavPosX = 0;
	var $m_iNavPosY = 0;
	var $m_iNavHeight = 0;
	var $m_iNavWidth = 0;

	var $m_iNextMonthPosX = 0;
	var $m_iNextMonthPosY = 0;
	var $m_iNextMonthHeight = 0;
	var $m_iNextMonthWidth = 0;

	var $m_iNextWeekPosX = 0;
	var $m_iNextWeekPosY = 0;
	var $m_iNextWeekHeight = 0;
	var $m_iNextWeekWidth = 0;

	var $m_iGotoDatePosX = 0;
	var $m_iGotoDatePosY = 0;
	var $m_iGotoDateHeight = 0;
	var $m_iGotoDateWidth = 0;

	var $m_iGotoTodayPosY = 0;
	var $m_iGotoTodayPosX = 0;
	
	var $m_GanttViewParamUrl = '';
	
	var $m_sTitle = '';
	
	var $m_iTodayPosX = null;
	var $m_aToDay = null;
	
	var $m_isToday = false;
	var $m_oTodayLine = null;

	function BAB_TM_GanttBase()
	{
	}
	
	function init($sStartDate, $iStartWeekDay = 1)
	{
		$this->m_sTitle = bab_translate("Gantt view");
		
		$this->m_iOnePxInSecondes = 86400 / $this->m_iWidth;
		
		$this->selectTask($sStartDate, $iStartWeekDay);
		$this->initLayout();
	}
	
	function selectTask($sStartDate, $iStartWeekDay)
	{
		$aFilters = array();

		$isPersonnal = bab_rp('isPersonnal', -1);
		if(-1 != $isPersonnal)
		{
			$aFilters['isPersonnal'] = BAB_TM_YES;
			$this->m_GanttViewParamUrl .= '&isPersonnal=' . $aFilters['isPersonnal'];
		}
		
		$iIdProject = bab_rp('iIdProject', -1);
		if(-1 != $iIdProject)
		{
			$aFilters['iIdProject'] = $iIdProject;
			$this->m_GanttViewParamUrl .= '&iIdProject=' . $aFilters['iIdProject'];
		}
		
		$iIdOwner = bab_rp('iIdOwner', -1);
		if(-1 != $iIdOwner)
		{
			$aFilters['iIdOwner'] = $iIdOwner;
			$this->m_GanttViewParamUrl .= '&iIdOwner=' . $aFilters['iIdOwner'];
		}
		
		$this->initDates($sStartDate, $iStartWeekDay);
		
		global $babDB;
		$this->m_result = $babDB->db_query(bab_selectForGantt($aFilters));
		
		if(false != $this->m_result)	
		{
			$iLinkType = -1;
			$aForGantt	= array();
			$aToProcess	= array();
			
			while(false != $this->m_result && false != ($datas = $babDB->db_fetch_assoc($this->m_result)))
			{
				$bStop = false;

				$this->isTaskDisplayableInGantt($datas, $aForGantt, $aToProcess);
				
				//récupére tous les descendant afin de savoir si ils sont dans la zone
				//d'affichage 		
				while(count($aToProcess) > 0 && false === $bStop)
				{
					$aToProcessItem = each($aToProcess);
					if(false !== $aToProcessItem)
					{
						$aDependingTasks = array();
						bab_getDependingTasks($aToProcessItem['value'], $aDependingTasks, $iLinkType);
						
						foreach($aDependingTasks as $iIdTask => $aDependingTask)
						{
							$aTask = array();
							if(true === bab_getTaskForGantt($iIdTask, $aTask))
							{
								$this->isTaskDisplayableInGantt($aTask, $aForGantt, $aToProcess);
							}
						}
						
						unset($aToProcess[$aToProcessItem['value']]);
						reset($aToProcess);
					}
					else
					{
						$bStop = false;
					}
				}
			}
			$this->m_result = $babDB->db_query(bab_getSelectQueryForGanttById($aForGantt));
			$this->m_iNbResult = $babDB->db_num_rows($this->m_result);
		}
	}

	function isTaskDisplayableInGantt($aTask, &$aForGantt, &$aToProcess)
	{
		$oTaskTimeManager 			=& getTaskTimeManager();
		$oGanttTask					= $oTaskTimeManager->getTask($aTask);
			
		$iTaskPlannedStartDateTs	= $oGanttTask->m_oPlannedStartDate->getTimeStamp();
		$iTaskPlannedEndDateTs		= $oGanttTask->m_oPlannedEndDate->getTimeStamp();
		
		$iTaskStartDateTs			= $oGanttTask->getStartDateTimeStamp();
		$iTaskEndDateTs				= $oGanttTask->getEndDateTimeStamp();
		
		$oRemainStartDate = null;
		$oRemainEndDate = null;
		$oGanttTask->computeRemainingDates($oRemainStartDate, $oRemainEndDate);
		
		$bRealDatesInBox = ($iTaskEndDateTs > $this->m_aDisplayedStartDate[0] && 
			$iTaskStartDateTs < $this->m_aDisplayedEndDate[0]);
		$bPlannedDatesInBox = ($iTaskPlannedEndDateTs > $this->m_aDisplayedStartDate[0] && 
			$iTaskPlannedStartDateTs < $this->m_aDisplayedEndDate[0]);
		$bRemainingDatesInBox = ($oRemainEndDate->getTimeStamp() > $this->m_aDisplayedStartDate[0] && 
			$oRemainStartDate->getTimeStamp() < $this->m_aDisplayedEndDate[0]);
			
		$iIdTask = $oGanttTask->m_iIdTask;

		if($bRealDatesInBox || $bPlannedDatesInBox || $bRemainingDatesInBox)
		{
			$aForGantt[$iIdTask]	= $iIdTask;
			$aToProcess[$iIdTask]	= $iIdTask;
		}
	
		$this->insertDependingTaskToArray($iIdTask, $aToProcess, BAB_TM_START_TO_START);
		$this->insertDependingTaskToArray($iIdTask, $aToProcess, BAB_TM_END_TO_START);
	}
	
	function insertDependingTaskToArray($iIdTask, &$aToInsert, $iLinkType)
	{
		$aDependingTasks = array();
		bab_getDependingTasks($iIdTask, $aDependingTasks, $iLinkType);
		foreach($aDependingTasks as $iIdTask => $aDependingTask)
		{
			$aToInsert[$iIdTask] = $iIdTask;
		}
	}
	
	function initDates($sStartDate, $iStartWeekDay)
	{
		global $babInstallPath;
		
		$this->m_sPrevMonth = bab_translate("Previous month");
		$this->m_sPrevWeek	= bab_translate("Previous week");
		$this->m_sGotoDate	= bab_translate("Go to date");
		$this->m_sNextWeek	= bab_translate("Next week");
		$this->m_sNextMonth	= bab_translate("Next month");
		$this->m_sToday		= bab_translate("Today");
		
		$sUrlBase			= $GLOBALS['babUrlScript'] . 
			'?tg=usrTskMgr&idx=' . BAB_TM_IDX_DISPLAY_GANTT_CHART . $this->m_GanttViewParamUrl . '&date=';

		$this->setDates($sStartDate, $iStartWeekDay);
		//echo 'StartDate ==> ' . $sStartDate . '<br />';

		$oDate = BAB_DateTime::fromTimeStamp($this->m_aDisplayedStartDate[0]);
		$oDate->add(-1, BAB_DATETIME_MONTH);
		$this->m_sPrevMonthUrl = bab_toHtml($sUrlBase . urlencode(date("Y-m-d", $oDate->_aDate[0])));
		//echo 'sPrevMonth ==> ' . date("Y-m-d", $oDate->_aDate[0]) . '<br />';

		$oDate = BAB_DateTime::fromTimeStamp($this->m_aDisplayedStartDate[0]);
		$oDate->add(-7, BAB_DATETIME_DAY);
		$this->m_sPrevWeekUrl = bab_toHtml($sUrlBase . urlencode(date("Y-m-d", $oDate->_aDate[0])));
		//echo 'sPrevWeek ==> ' . date("Y-m-d", $oDate->_aDate[0]) . '<br />';
		
		$oDate = BAB_DateTime::fromTimeStamp($this->m_aDisplayedStartDate[0]);
		$oDate->add(7, BAB_DATETIME_DAY);
		$this->m_sNextWeekUrl = bab_toHtml($sUrlBase . urlencode(date("Y-m-d", $oDate->_aDate[0])));
		//echo 'sNextWeek ==> ' . date("Y-m-d", $oDate->_aDate[0]) . '<br />';

		$oDate = BAB_DateTime::fromTimeStamp($this->m_aDisplayedStartDate[0]);
		$oDate->add(1, BAB_DATETIME_MONTH);
		$this->m_sNextMonthUrl = bab_toHtml($sUrlBase . urlencode(date("Y-m-d", $oDate->_aDate[0])));
		//echo 'sNextMonth ==> ' . date("Y-m-d", $oDate->_aDate[0]) . '<br />';
		
		$this->m_sTodayUrl		= bab_toHtml($sUrlBase . urlencode(date("Y-m-d")));
		$this->m_sGotoDateUrl 	= bab_toHtml($sUrlBase, BAB_HTML_JS);
	}
	
	function setDates($sStartDate, $iStartWeekDay)
	{
		$this->m_iStartWeekDay = $iStartWeekDay;
		$this->m_aDisplayedStartDate = getdate(strtotime($sStartDate));
		
		$this->m_iYear = $this->m_aDisplayedStartDate['year']; 
		
		//Pour démarrer à un jour spécifique de la semaine
		if($iStartWeekDay != $this->m_aDisplayedStartDate['wday'])
		{
			$iGap = 0;
			if($this->m_aDisplayedStartDate['wday'] < $iStartWeekDay)
			{
				$iGap = $iStartWeekDay - $this->m_aDisplayedStartDate['wday'];
				
				$this->m_iTimeStamp = mktime( $this->m_aDisplayedStartDate['hours'], $this->m_aDisplayedStartDate['minutes'], $this->m_aDisplayedStartDate['seconds'],
						$this->m_aDisplayedStartDate['mon'], ($this->m_aDisplayedStartDate['mday'] + $iGap), $this->m_aDisplayedStartDate['year']);
				
				$this->m_aDisplayedStartDate = getdate($this->m_iTimeStamp);
			}
			else
			{
				$iGap = $this->m_aDisplayedStartDate['wday'] - $iStartWeekDay;
				
				$this->m_iTimeStamp = mktime($this->m_aDisplayedStartDate['hours'], $this->m_aDisplayedStartDate['minutes'], $this->m_aDisplayedStartDate['seconds'],
						$this->m_aDisplayedStartDate['mon'], ($this->m_aDisplayedStartDate['mday'] - $iGap), $this->m_aDisplayedStartDate['year']);
						
				$this->m_aDisplayedStartDate = getdate($this->m_iTimeStamp);
			}
		}
		else
		{
			$this->m_iTimeStamp = mktime($this->m_aDisplayedStartDate['hours'], $this->m_aDisplayedStartDate['minutes'], $this->m_aDisplayedStartDate['seconds'],
					$this->m_aDisplayedStartDate['mon'], $this->m_aDisplayedStartDate['mday'], $this->m_aDisplayedStartDate['year']);
		}

		$iTimeStamp = mktime((int) $this->m_aDisplayedStartDate['hours'], (int) $this->m_aDisplayedStartDate['minutes'], (int) $this->m_aDisplayedStartDate['seconds'],
				$this->m_aDisplayedStartDate['mon'], ($this->m_aDisplayedStartDate['mday'] + $this->m_iTotalDaysToDisplay), 
				(int) $this->m_aDisplayedStartDate['year']);

		$this->m_aDisplayedEndDate	= getdate($iTimeStamp);
		$this->m_iCurrMonth	= $this->m_aDisplayedStartDate['mon'];
		$this->m_iMonthDay	= $this->m_aDisplayedStartDate['mday'] - 1; //The month day is 1 based
		$this->m_iCurrDay	= $this->m_aDisplayedStartDate['wday'];
		
		$this->m_iWeekNumber = $this->m_iStartWeekNumber = date('W', $this->m_aDisplayedStartDate[0]);
		$this->m_iEndWeekNumber = date('W', $this->m_aDisplayedEndDate[0]);

		//Today pos
		{
			$oTaskTimeManager		= getTaskTimeManager();
			$sToday					= $oTaskTimeManager->getTodayIsoDateString();
			$iDisplayedStartDateTs	=& $this->m_aDisplayedStartDate[0];
			$iDisplayedEndDateTs	=& $this->m_aDisplayedEndDate[0];
			$iTodayTS				= strtotime($sToday);
			$this->m_aToDay			= getdate($iTodayTS);
			
			$iNbDays = BAB_DateTime::dateDiffIso($sToday, date("Y-m-d", $this->m_aDisplayedStartDate[0]));

$this->m_oTodayLine	= $oTaskTimeManager->getTodayIsoDateTime();
			
			if($iTodayTS >= $iDisplayedStartDateTs && $iTodayTS <= $iDisplayedEndDateTs)
			{
				$this->m_iTodayPosX = $iNbDays;
				
$iTodayLineTS = $this->m_oTodayLine->getTimeStamp();
$this->m_iTodayPosLineX = round(($iTodayLineTS - $iDisplayedStartDateTs) / $this->m_iOnePxInSecondes);

				$iBorderLeft	= 1;
				$this->m_iTodayPosLineX	-= $iBorderLeft;
			}
		}
	}
	
	function initLayout()
	{
		$iBorderWidth = 1;
		
		$this->m_iNavPosX = 0;
		$this->m_iNavPosY = 0;
		$this->m_iNavHeight = $this->m_iHeight;
		$this->m_iNavWidth = ($this->m_iTaskCaptionWidth + ($this->m_iTotalDaysToDisplay * $this->m_iWidth));
		
		$this->m_iGanttHeaderPosX = 0;
		$this->m_iGanttHeaderPosY = $this->m_iHeight;
		$this->m_iGanttHeaderHeight = (4 * $this->m_iHeight);
		$this->m_iGanttHeaderWidth = ($this->m_iTaskCaptionWidth + ($this->m_iTotalDaysToDisplay * $this->m_iWidth));
	
		$this->m_iGanttTasksPosX = 0;
		$this->m_iGanttTasksPosY = $this->m_iGanttHeaderHeight + $iBorderWidth;
		$this->m_iGanttTasksHeight = ($this->m_iNbResult + 1) * $this->m_iHeight; //+1 pour la taille du titre
		$this->m_iGanttTasksWidth = $this->m_iTaskCaptionWidth;
		
		$this->m_iGanttViewPosX = $this->m_iTaskCaptionWidth;
		$this->m_iGanttViewPosY = $this->m_iGanttHeaderHeight + $iBorderWidth;
		$this->m_iGanttViewHeight = $this->m_iGanttTasksHeight;
		$this->m_iGanttViewWidth = $this->m_iTotalDaysToDisplay * $this->m_iWidth;
		
		$this->initGanttNav();
	}
	
	function initGanttNav()
	{
		$this->m_iPrevWeekPosX = 0;
		$this->m_iPrevWeekPosY = 0;
		$this->m_iPrevWeekHeight = $this->m_iHeight;
		$this->m_iPrevWeekWidth = $this->m_iWidth;
		
		$this->m_iPrevMonthPosX = $this->m_iPrevWeekPosX + $this->m_iWidth;
		$this->m_iPrevMonthPosY = 0;
		$this->m_iPrevMonthHeight = $this->m_iHeight;
		$this->m_iPrevMonthWidth = $this->m_iWidth;
	
		$this->m_iGotoDatePosX = (($this->m_iTaskCaptionWidth + ($this->m_iTotalDaysToDisplay * $this->m_iWidth)) / 2) - ($this->m_iWidth / 2);
		$this->m_iGotoDatePosY = 0;
		$this->m_iGotoDateHeight = $this->m_iHeight;
		$this->m_iGotoDateWidth = $this->m_iWidth * 5;
		
		$this->m_iGotoTodayPosX = $this->m_iGotoDatePosX + $this->m_iWidth;
		$this->m_iGotoTodayPosY = 0;
		
		
		$this->m_iNextWeekPosX = $this->m_iNavWidth - $this->m_iWidth;
		$this->m_iNextWeekPosY = 0;
		$this->m_iNextWeekHeight = $this->m_iHeight;
		$this->m_iNextWeekWidth = $this->m_iWidth;
		
		$this->m_iNextMonthPosX = $this->m_iNextWeekPosX- $this->m_iWidth;
		$this->m_iNextMonthPosY = 0;
		$this->m_iNextMonthHeight = $this->m_iHeight;
		$this->m_iNextMonthWidth = $this->m_iWidth;
	}
	
	// Tools functions
	function getNbDaysInMonth($iMonth, $iYear)
	{
		static $aNbDaysInMonth_leap = array ('1' => 31, '2' => 29, '3' => 31, '4' => 30, '5' => 31, 
			'6' => 30, '7' => 31, '8' => 31, '9' => 30, '10' => 31, '11' => 30, '12' => 31);
		static $aNbDaysInMonth_nonLeap = array ('1' => 31, '2' => 28, '3' => 31, '4' => 30, '5' => 31, 
			'6' => 30, '7' => 31, '8' => 31, '9' => 30, '10' => 31, '11' => 30, '12' => 31);

		if($iMonth >= 1 && $iMonth <= 12)
		{
			$aNbDaysInMonth = ($this->isLeapYear($iYear)) ? $aNbDaysInMonth_leap : $aNbDaysInMonth_nonLeap;
				
			return $aNbDaysInMonth[$iMonth];
		}
		return 0;
	}
	
	function isLeapYear($iYears)
	{
		return ( ($iYears % 4) == 0 && ($iYears % 100) != 0 || ($iYears % 400) == 0 );
	}
	
	function getMonth($iMonth)
	{
		static $aMonths = null;

		if(is_null($aMonths))
		{
			$aMonths = array ('1' => bab_translate("January"), '2' => bab_translate("February"), 
				'3' => bab_translate("March"), '4' => bab_translate("April"), '5' => bab_translate("May"), 
				'6' => bab_translate("June"), '7' => bab_translate("July"), '8' => bab_translate("August"),
				'9' => bab_translate("September"), '10' => bab_translate("October"), '11' => bab_translate("November"), 
				'12' => bab_translate("December"));
		}
			
		if($iMonth >= 1 && $iMonth <= 12)
		{
			return $aMonths[$iMonth];
		}
		return '';
	}

	function getDay($iDay)
	{
		static $aDays = array ('0' => 'D', '1' => 'L', '2' => 'M', '3' => 'M', '4' => 'J', 
				'5' => 'V', '6' => 'S');
			
		if($iDay >= 0 && $iDay <= 6)
		{
			return $aDays[$iDay];
		}
		return $iDay;
	}
		
	function dummyGetNext()
	{
		return ($this->m_iDummy++ == 0);
	}
	
	
	//Layout
	function getNextMonth()
	{
		if($this->m_iTotalDaysToDisplay > 0)
		{
			$iLeftParentBorderWidth = 1;

			$this->m_sMonth = $this->getMonth($this->m_iCurrMonth) . ' ' . $this->m_iYear;
		
			$this->m_iBorderLeft	= 1;
			$this->m_iBorderRight	= 0;
			$this->m_iBorderTop		= 0;
			$this->m_iBorderBottom	= 0;

			$this->m_iMonthHeigth	= $this->m_iHeight - ($this->m_iBorderTop + $this->m_iBorderBottom);

			$this->m_iMonthPosY = 0;
			$this->m_iMonthPosX = ($this->m_iDisplayedDays * $this->m_iWidth) + $this->m_iTaskCaptionWidth - $iLeftParentBorderWidth;

			$iNbDaysInMonth = $this->getNbDaysInMonth($this->m_iCurrMonth, $this->m_iYear);
			$iNbDaysInMonth = $iNbDaysInMonth - $this->m_iMonthDay;
			
			if($iNbDaysInMonth < $this->m_iTotalDaysToDisplay)
			{
				$this->m_iTotalDaysToDisplay -= $iNbDaysInMonth;
			}
			else if($iNbDaysInMonth >= $this->m_iTotalDaysToDisplay)
			{
				$iNbDaysInMonth = $this->m_iTotalDaysToDisplay;
				$this->m_iTotalDaysToDisplay = 0;
			}
			
			$this->m_iDisplayedDays += $iNbDaysInMonth;
			$this->m_iMonthWidth = ($iNbDaysInMonth * $this->m_iWidth);

			$this->m_iMonthDay = 0;
			$this->m_iCurrMonth++;
			
			if(12 < $this->m_iCurrMonth)
			{
				$this->m_iCurrMonth = 1;
				$this->m_iYear++;
			}
			
			return true;
		}
		
		$this->m_iTotalDaysToDisplay = $this->m_iDisplayedDays;
		return false;
	}
	
	function getNextWeekNumber()
	{
		static $iProcessedDays = 0;
		if($this->m_iTotalDaysToDisplay > 0)
		{
			$iNbDays = 7;
		
			if($this->m_iWeekNumber == $this->m_iStartWeekNumber && 1 != $this->m_iStartWeekDay)
			{
				//7 == NB days in a week
				//+1 the weekday is zero based
				$iNbDays = 7 - $this->m_iStartWeekDay +1;
			}
			
			if($iNbDays < $this->m_iTotalDaysToDisplay)
			{
				$this->m_iTotalDaysToDisplay -= $iNbDays;
			}
			else if($iNbDays >= $this->m_iTotalDaysToDisplay)
			{
				$iNbDays = $this->m_iTotalDaysToDisplay;
				$this->m_iTotalDaysToDisplay = 0;
			}

			$iLeftParentBorderWidth = 1;

			$this->m_iBorderLeft	= 1;
			$this->m_iBorderRight	= 0;
			$this->m_iBorderTop		= 1;
			$this->m_iBorderBottom	= 0;
			
			$this->m_iWeekHeigth	= $this->m_iHeight  - ($this->m_iBorderTop + $this->m_iBorderBottom);

			$this->m_iWeekPosY = $this->m_iHeight;
			$this->m_iWeekPosX = ($iProcessedDays * $this->m_iWidth) + $this->m_iTaskCaptionWidth - $iLeftParentBorderWidth;
			$this->m_iWeekWidth = $iNbDays * $this->m_iWidth;
			$this->m_sWeek = sprintf('%s %02s', bab_translate("Week"), $this->m_iWeekNumber);
			$iProcessedDays += $iNbDays;
			
			$this->m_iWeekNumber++;

			if(52 < $this->m_iWeekNumber)
			{
				$this->m_iWeekNumber = 1;
			}
			return true;
		}
		$this->m_iTotalDaysToDisplay = $iProcessedDays;
		return false;
	}
	
	function getNextDay()
	{
		static $iDisplayedDays = 0;
		
		if($iDisplayedDays < $this->m_iTotalDaysToDisplay)
		{
			$iLeftParentBorderWidth = 1;

			$this->m_iBorderLeft	= 1;
			$this->m_iBorderRight	= 0;
			$this->m_iBorderTop		= 1;
			$this->m_iBorderBottom	= 0;

			$this->m_iDayHeigth	= $this->m_iHeight - ($this->m_iBorderTop + $this->m_iBorderBottom);
			$this->m_iDayWidth = $this->m_iWidth;

			$aDate = getdate($this->m_iTimeStamp);
			
			$this->m_sDay		= $this->getDay($this->m_iCurrDay);
			$this->m_sMday		= $aDate['mday'];
			$this->m_iDayPosY	= $this->m_iHeight * 2;
			$this->m_iDayPosX	= ($iDisplayedDays * $this->m_iWidth) + $this->m_iTaskCaptionWidth - $iLeftParentBorderWidth;

			$this->m_iCurrDay	= ($this->m_iCurrDay + 1) % 7;

			$this->m_iTimeStamp = mktime($aDate['hours'], $aDate['minutes'], $aDate['seconds'], $aDate['mon'], ($aDate['mday'] + 1), $aDate['year']);
			
			$iDisplayedDays++;
			return true;
		}
		return false;
	}
	
	function getNextTaskTitle()
	{
		static $i = 0;
		
		$iLeftParentBorderWidth = 1;

		$this->m_iBorderLeft	= 0;
		$this->m_iBorderRight	= 0;
		$this->m_iBorderTop		= 0;
		$this->m_iBorderBottom	= 1;

		$this->m_iTaskTitlePosX = 0;
		$this->m_iTaskTitlePosY = 0;
		$this->m_iTaskTitleHeigth = $this->m_iHeight - ($this->m_iBorderTop + $this->m_iBorderBottom);
		$this->m_iTaskTitleWidth = $this->m_iTaskCaptionWidth - ($this->m_iBorderLeft + $this->m_iBorderRight);
		$this->m_sTaskTitle = bab_translate("Tasks");
	
		
		$this->m_iGanttTasksListPosX = 0;
		$this->m_iGanttTasksListPosY = $this->m_iTaskTitleHeigth + $iLeftParentBorderWidth;
		$this->m_iGanttTasksListHeight = ($this->m_iNbResult * $this->m_iHeight) - ($this->m_iBorderTop + $this->m_iBorderBottom);
		$this->m_iGanttTasksListWidth = $this->m_iTaskCaptionWidth - ($this->m_iBorderLeft + $this->m_iBorderRight);
		return ($i++ == 0);
	}

	function getNextTaskItem()
	{
		global $babDB;
		
		static $iIndex = 0;
		if(false != $this->m_result && false != ($datas = $babDB->db_fetch_assoc($this->m_result)))
		{
			$iLeftParentBorderWidth 	= 1;
			$this->m_iBorderLeft		= 0;
			$this->m_iBorderRight		= 0;
			$this->m_iBorderTop			= 0;
			$this->m_iBorderBottom		= 1;

			$this->m_iTaskInfoPosX		= 0;
			$this->m_iTaskInfoPosY		= ($this->m_iHeight * $iIndex++);
			$this->m_iTaskInfoHeigth	= $this->m_iHeight - ($this->m_iBorderTop + $this->m_iBorderBottom);
			$this->m_iTaskInfoWidth		= $this->m_iTaskCaptionWidth - ($this->m_iBorderLeft + $this->m_iBorderRight);
			$this->m_sTaskInfoBgColor	= (strlen(trim($datas['sBgColor'])) > 0) ? $datas['sBgColor'] : 'EFEFEF';
			$this->m_sTaskInfoColor		= (strlen(trim($datas['sColor'])) > 0) ? $datas['sColor'] : '000000';
			$this->m_sTaskInfo			= $datas['sShortDescription'] . '<br />' . $datas['sProjectName'];
			return true;
		}
		
		if($babDB->db_num_rows($this->m_result) > 0)
		{
			$babDB->db_data_seek($this->m_result, 0);
		}
		
		return false;
	}
	
	function getNextColumn()
	{
		static $iIndex = 0;

		$iBorderWidth = 1;
		
		$oDate = BAB_DateTime::fromTimeStamp($this->m_aDisplayedStartDate[0]);
		$oDate->add($iIndex, BAB_DATETIME_DAY);
		$iDayOfWeek = $oDate->getDayOfWeek();

		$this->m_iColumnPosY = 0;
		$this->m_iColumnPosX = ($iIndex++ * $this->m_iWidth);
		$this->m_iColumnHeigth = ($this->m_iNbResult + 1) * $this->m_iHeight;
		$this->m_iColumnWidth = $this->m_iWidth - $iBorderWidth;

		$this->m_sTodayColumnAddClass = '';
		if($iDayOfWeek == 0 || $iDayOfWeek == 6)
		{
			$this->m_sTodayColumnAddClass .= 'ganttWeek';
		}

		$iPosX				= ($this->m_iTodayPosX * $this->m_iWidth);			
		$this->m_isToday	= (!is_null($this->m_iTodayPosX) && $iPosX == $this->m_iColumnPosX);
		if($this->m_isToday)
		{
			$this->m_sTodayColumnAddClass = ' ganttTodayColumn';
		}
		
		//car on commence à 1
		return ( ($this->m_iTotalDaysToDisplay) >= $iIndex);
	}
	
	function getNextRow()
	{
		static $iIndex = 0;

		$this->m_iBorderLeft	= 0;
		$this->m_iBorderRight	= 0;
		$this->m_iBorderTop		= 0;
		$this->m_iBorderBottom	= 1;

		$this->m_iRowPosX = 0;
		$this->m_iRowPosY = $iIndex++ * $this->m_iHeight;
		$this->m_iRowHeigth = $this->m_iHeight - ($this->m_iBorderTop + $this->m_iBorderBottom);
		$this->m_iRowWidth = $this->m_iTotalDaysToDisplay * $this->m_iWidth;
		
		//car on commence à 1
		return ( ($this->m_iNbResult + 1) >= $iIndex);
	}
}



class BAB_TM_Gantt extends BAB_TM_GanttBase
{
	var $m_iTaskIndex	= 1;
	var $m_iTaskPosX	= 0;
	var $m_iTaskPosY	= 0;
	var $m_iTaskHeigth	= 0;
	var $m_iTaskWidth	= 0;
	var $m_sTaskClass	= '';
	var $m_iIdTask		= 0;
	var $m_sToolTip		= '';
	
	var $m_oGanttTaskManager	= null;
	var $m_aPeriods				= null;
	var $m_sGanttLegend			= '';
	
	function BAB_TM_Gantt()
	{
		parent::BAB_TM_GanttBase();
		$this->m_oGanttTaskManager =& getGanttTaskManager();
		
		$oGanttLegend = new BAB_TM_GanttLegend();
		$this->m_sGanttLegend = $oGanttLegend->getHtml();
	}
	
	function getNextTask(&$bSkip)
	{
		global $babDB;
		
		if(false != $this->m_result && false != ($aTask = $babDB->db_fetch_assoc($this->m_result)))
		{
			$oGanttPeriods = $this->m_oGanttTaskManager->getTask($aTask);
			if(!is_null($oGanttPeriods))
			{
				$this->m_aPeriods = $oGanttPeriods->getPeriods();
				$this->m_sToolTip = $oGanttPeriods->getToolTip();
				$this->m_iTaskIndex++;
				return true;
			}
			else
			{
				$bSkip = true;
				return true;
			}
		}
		
		if($babDB->db_num_rows($this->m_result) > 0)
		{
			$this->m_iTaskIndex = 1;
			$babDB->db_data_seek($this->m_result, 0);
		}
		return false;
	}
		
	function getNextTaskPeriod()
	{
		if(is_array($this->m_aPeriods))
		{
			$aPeriod = each($this->m_aPeriods);
			if(false !== $aPeriod)
			{
				$oTaskPeriod			=& $aPeriod['value'];
				$this->m_iTaskPosX		= $oTaskPeriod->getLeft();
				$this->m_iTaskPosY		= $oTaskPeriod->getTop();
				$this->m_iTaskHeigth	= $oTaskPeriod->getHeight();
				$this->m_iTaskWidth		= $oTaskPeriod->getWidth();
				$this->m_sTaskClass		= $oTaskPeriod->getClassName();
				$this->m_iIdTask		= $oTaskPeriod->getId();
				return true;
			}
		}
		return false;
	}
}



class BAB_TM_GanttTaskManager
{
	var $m_aCache = array();
	
	function BAB_TM_GanttTaskManager()
	{
	}
	
	function getTask($aTask)
	{
		$oGanttTask = null;
		$iIdTask	= (int) $aTask['iIdTask'];
		$iClass		= (int) $aTask['iClass'];
		
		if(!array_key_exists($iIdTask, $this->m_aCache))
		{
			switch($iClass)
			{
				case BAB_TM_TASK:
					$oObj = $this->handleTask($aTask);
					break;
				case BAB_TM_CHECKPOINT:
					$oObj = $this->handleCheckPoint();
				case BAB_TM_TODO:
					$oObj = $this->handleToDo();
				default:
					break;	
			}
			
			if(!is_null($oObj))
			{
				$oObj->buildPeriods($aTask);
				$oObj->buildToolTip($aTask);
				
				$this->m_aCache[$iIdTask] = $oObj;
			}
		}
		return $this->m_aCache[$iIdTask];
	}

	function handleTask($aTask)	
	{
		if(0 == $aTask['iDuration'])
		{
			$oObj = new BAB_TM_GanttTaskDate();
			return $oObj;
		}
		else
		{
			$oObj = new BAB_TM_GanttTaskDuration(); 
			return $oObj; 
		}
	}
	
	function handleCheckPoint()	
	{
		$oObj = new BAB_TM_GanttCheckpoint();
		return $oObj; 
	}
	
	function handleToDo()
	{
		$oObj = new BAB_TM_GanttToDo();
		return $oObj; 
	}
}


function &getGanttTaskManager()
{
	if(!array_key_exists('babTmGanttTaskManager', $GLOBALS))
	{
		$GLOBALS['babTmGanttTaskManager'] = new BAB_TM_GanttTaskManager();
	}
	return $GLOBALS['babTmGanttTaskManager'];
}

function &getGanttTaskInstance()
{
	if(!array_key_exists('babTmGantt', $GLOBALS))
	{
		$GLOBALS['babTmGantt'] = new BAB_TM_Gantt();
	}
	return $GLOBALS['babTmGantt'];
}


class BAB_TM_GanttTaskBase
{
	var	$m_iDisplayedStartDateTs 	= 0;
	var	$m_iDisplayedEndDateTs 		= 0;
	var $m_aPeriods					= array();
	var $m_sToolTip					= '';
	
	function BAB_TM_GanttTaskBase()
	{
		$this->m_oGantt =& getGanttTaskInstance();
		
		$this->m_iDisplayedStartDateTs	= $this->m_oGantt->m_aDisplayedStartDate[0];
		$this->m_iDisplayedEndDateTs	= $this->m_oGantt->m_aDisplayedEndDate[0];
	}
	
	function createPeriod($iTaskStartDateTs, $iTaskEndDateTs)
	{
		if($iTaskStartDateTs < $this->m_iDisplayedStartDateTs)
		{
			$iTaskStartDateTs = $this->m_iDisplayedStartDateTs;
		}
		
		if($iTaskEndDateTs > $this->m_iDisplayedEndDateTs)
		{
			$iTaskEndDateTs = $this->m_iDisplayedEndDateTs;
		}
		
		$iElaspedSecondsFromBigining		= $iTaskStartDateTs - $this->m_iDisplayedStartDateTs;
		$iDisplayedTaskDurationInSeconds	= $iTaskEndDateTs - $iTaskStartDateTs;

		$iLeft		= round($iElaspedSecondsFromBigining / $this->m_oGantt->m_iOnePxInSecondes);
		$iRight		= round(($iElaspedSecondsFromBigining + $iDisplayedTaskDurationInSeconds) / $this->m_oGantt->m_iOnePxInSecondes);
		$iHeight	= round($this->m_oGantt->m_iHeight / 2);
		$iTop		= round(($this->m_oGantt->m_iTaskIndex * $this->m_oGantt->m_iHeight) + ($iHeight / 2));
		$iWidth		= $iRight - $iLeft;

		//Tous les carrés ont des bordures des quatres côtés de 1 px
		$iBorderTop		= 1;
		$iBorderLeft	= 1;
		$iBorderRight	= 1;
		$iBorderBottom	= 1;

		$iLeft		-= $iBorderLeft;
		$iHeight	-= ($iBorderTop + $iBorderBottom);
		$iWidth		-= $iBorderRight;

		$oPeriod = new BAB_TM_GanttTaskPeriod();
		$oPeriod->setTop($iTop);
		$oPeriod->setLeft($iLeft);
		$oPeriod->setHeight($iHeight);
		$oPeriod->setWidth($iWidth);
		
		return $oPeriod;
	}
	
	function addPeriod($oPeriod)
	{
		$this->m_aPeriods[] = $oPeriod; 
	}
	
	function resetPeriod()
	{
		$this->m_aPeriods = array();
	}
	
	function getPeriods()
	{
		return $this->m_aPeriods;
	}
	
	function buildPeriods($aTask)
	{
		
	}
	
	function buildToolTip($aTask)
	{
		$sToolTip = 
			'<h3>' . $aTask['sShortDescription'] . '</h3>' . 
			'<div>' .
				'<p><strong>' . bab_translate("Project") . ': </strong>' . $aTask['sProjectName'] . '</p>' .
				'<p><strong>' . bab_translate("Type") . ': </strong>' . $aTask['sClass'] . '</p>';

		if(BAB_TM_TASK === (int) $aTask['iClass'])
		{
			$sToolTip .= 
				'<p><strong>' . bab_translate("planned_date_from") . ': </strong>' . bab_shortDate(bab_mktime($aTask['plannedStartDate'])) . '</p>' .
				'<p><strong>' . bab_translate("started_from") . ': </strong>' . bab_shortDate(bab_mktime($aTask['startDate'])) . '</p>' .
				'<p><strong>' . bab_translate("planned_date_to") . ': </strong>' . bab_shortDate(bab_mktime($aTask['plannedEndDate'])) . '</p>' .
				'<p><strong>' . bab_translate("finished_to") . ': </strong>' . bab_shortDate(bab_mktime($aTask['endDate'])) . '</p>' .
				'<p><strong>' . bab_translate("Completion") . ': </strong>' . $aTask['iCompletion'] . ' %' . '</p>';
		}
		else
		{
			$sToolTip .= 
				'<p><strong>' . bab_translate("date_from") . ': </strong>' . bab_shortDate(bab_mktime($aTask['startDate'])) . '</p>';
		}
		
		if(strlen($aTask['sCategoryName']) > 0)
		{
			$sToolTip .= 
				'<p><strong>' . bab_translate("Category") . ': </strong>' . $aTask['sCategoryName'] . '</p>';
		}
		
		if(BAB_TM_TASK === (int) $aTask['iClass'])
		{
			bab_getTaskResponsibles($aTask['iIdTask'], $aTaskResponsible);
			
			if(count($aTaskResponsible) > 0)
			{
				$aItem = each($aTaskResponsible);
				if(false !== $aItem)
				{
					$sToolTip .= 
						'<p><strong>' . bab_translate("Responsable") . ': </strong>' . $aItem['value']['name'] . '</p>';
//						'<p><strong>' . bab_translate("Responsable") . ': </strong>' . $aTaskResponsible[$aTask['idOwner']]['name'] . '</p>';
				}
			}
		}
		
		if(strlen($aTask['sDescription']) > 0)
		{
			require_once $GLOBALS['babInstallPath'] . 'utilit/editorincl.php';
			$oEditor = new bab_contentEditor('bab_taskManagerDescription');
			$oEditor->setContent($aTask['sDescription']);
			
			$sToolTip .= 
				'<div class="description">' . $oEditor->getHtml() . '</div>';
		}
		
		$sToolTip .= '</div>';
		$this->m_sToolTip = $sToolTip;
	}
	
	function getToolTip()
	{
		return $this->m_sToolTip;
	}
}


class BAB_TM_GanttToDoCheckpoint extends BAB_TM_GanttTaskBase
{
	var $m_sClassName = '';
	
	function BAB_TM_GanttToDoCheckpoint()
	{
		parent::BAB_TM_GanttTaskBase();
	}
	
	function buildPeriods($aTask)
	{
		$oTaskStartDate		= BAB_DateTime::fromIsoDateTime($aTask['plannedStartDate']);
		$oTaskEndDate		= BAB_DateTime::fromIsoDateTime($aTask['plannedEndDate']);

		$iTaskStartDateTs	= $oTaskStartDate->getTimeStamp();
		$iTaskEndDateTs		= $oTaskEndDate->getTimeStamp();
		
		$oPeriod 			= $this->createPeriod($iTaskStartDateTs, $iTaskEndDateTs);
		
		$oPeriod->setClassName($this->m_sClassName);
		$oPeriod->setId((int) $aTask['iIdTask']);
		$oPeriod->setWidth($this->m_oGantt->m_iWidth);
		
		$this->addPeriod($oPeriod);
	}
}


class BAB_TM_GanttToDo extends BAB_TM_GanttToDoCheckpoint
{
	function BAB_TM_GanttToDo()
	{
		parent::BAB_TM_GanttToDoCheckpoint();
		
		$this->m_sClassName = 'ganttToDo';
	}
}


class BAB_TM_GanttCheckpoint extends BAB_TM_GanttToDoCheckpoint
{
	function BAB_TM_GanttCheckpoint()
	{
		parent::BAB_TM_GanttToDoCheckpoint();
		
		$this->m_sClassName = 'ganttCheckpoint';
	}
}


class BAB_TM_GanttTask extends BAB_TM_GanttTaskBase
{
	var $m_oTaskTime = null;
	
	function BAB_TM_GanttTask()
	{
		parent::BAB_TM_GanttTaskBase();
	}
	
	function init($aTask)
	{
		$oTaskTimeManager =& getTaskTimeManager();
		$this->m_oTaskTime = $oTaskTimeManager->getTask($aTask);
	}
	
	function buildPeriods($aTask)
	{
		$this->init($aTask);
		
		$this->buildPlannedPeriod();
		$this->buildRealPeriod();
		$this->buildEffectivePeriod();
		$this->buildCompletion();
		$this->buildRemaingAccordingToday();
		//$this->buildRealPeriod();
	}
	
	function buildPlannedPeriod()
	{
		$iStartDateTs	= $this->m_oTaskTime->m_oPlannedStartDate->getTimeStamp();
		$iEndDateTs		= $this->m_oTaskTime->m_oPlannedEndDate->getTimeStamp();
		
if($iEndDateTs > $this->m_iDisplayedStartDateTs && $iStartDateTs < $this->m_iDisplayedEndDateTs)
		{
			$oPeriod = $this->createPeriod($iStartDateTs, $iEndDateTs);
			
			$oPeriod->setClassName('ganttTask');
			$oPeriod->setId($this->m_oTaskTime->m_iIdTask);
			
			$this->addPeriod($oPeriod);
		}
	}
	
	function buildRealPeriod()
	{
		if(!is_null($this->m_oTaskTime->m_oStartDate) && !is_null($this->m_oTaskTime->m_oEndDate))
		{
			$iStartDateTs	= $this->m_oTaskTime->m_oStartDate->getTimeStamp();
			$iEndDateTs		= $this->m_oTaskTime->m_oEndDate->getTimeStamp();

if($iEndDateTs > $this->m_iDisplayedStartDateTs && $iStartDateTs < $this->m_iDisplayedEndDateTs)
			{
				$oPeriod 		= $this->createPeriod($iStartDateTs, $iEndDateTs);
				
				$oPeriod->setClassName('ganttTaskReal');
				$oPeriod->setId($this->m_oTaskTime->m_iIdTask);
				
				$oPeriod->setTop($oPeriod->getTop() - 4);
				$oPeriod->setHeight($oPeriod->getHeight() + 8);
				
				$this->addPeriod($oPeriod);
			}
		}
	}
	
	function buildRemaingAccordingToday()
	{
		//Ce qui reste en fonction de la date du jour
		if($this->m_oTaskTime->m_iCompletion < 100)
		{
			$oTaskTimeManager	=& getTaskTimeManager();
			$oTodayDate			= $oTaskTimeManager->getTodayIsoDateTime();
			$oRemainStartDate	= null;
			$oRemainEndDate		= null;
			
			$this->m_oTaskTime->computeRemainingDates($oRemainStartDate, $oRemainEndDate);

if($oRemainEndDate->getTimeStamp() > $this->m_iDisplayedStartDateTs && $oRemainStartDate->getTimeStamp() < $this->m_iDisplayedEndDateTs)
			{
				$oPeriod = $this->createPeriod($oRemainStartDate->getTimeStamp(), 
					$oRemainEndDate->getTimeStamp());
					
				$oPeriod->setClassName('ganttTaskRemaining');
				$oPeriod->setId($this->m_oTaskTime->m_iIdTask);
				
				$oPeriod->setTop($oPeriod->getTop() + ($oPeriod->getHeight() / 2));
				$oPeriod->setHeight($oPeriod->getHeight() / 2);
	
				$this->addPeriod($oPeriod);
			}
			
			//Raccorde la date de fin de la tâche à la date de début de ce qui reste à faire
if($oTodayDate->getTimeStamp() > $this->m_iDisplayedStartDateTs && $this->m_oTaskTime->getEndDateTimeStamp() < $this->m_iDisplayedEndDateTs)
			{
				$oPeriod = $this->createPeriod($this->m_oTaskTime->getEndDateTimeStamp(), 
					$oTodayDate->getTimeStamp());
				$oPeriod->setClassName('ganttTaskFromTaskToRemaing');
				$oPeriod->setId($this->m_oTaskTime->m_iIdTask);
				$oPeriod->setTop($oPeriod->getTop() + ($oPeriod->getHeight() / 2));
				$oPeriod->setHeight(($oPeriod->getHeight() / 2));
				$this->addPeriod($oPeriod);
			}
		}
	}
}



class BAB_TM_GanttTaskDate extends BAB_TM_GanttTask
{

	function BAB_TM_GanttTaskDate()
	{
		parent::BAB_TM_GanttTask();
	}

	function buildEffectivePeriod()
	{
		$iTaskStartDateTs	= $this->m_oTaskTime->getStartDateTimeStamp();
		$iTaskEndDateTs		= $iTaskStartDateTs + $this->m_oTaskTime->m_iEffectiveDurationInSeconds;
		
if($iTaskEndDateTs > $this->m_iDisplayedStartDateTs && $iTaskStartDateTs < $this->m_iDisplayedEndDateTs)
		{
			$oPeriod 			= $this->createPeriod($iTaskStartDateTs, $iTaskEndDateTs);
			
			$oPeriod->setClassName('ganttTaskEffectiveDuration');
			$oPeriod->setId($this->m_oTaskTime->m_iIdTask);
			$oPeriod->setHeight($oPeriod->getHeight() / 2);
			$this->addPeriod($oPeriod);
		}
	}

	function buildCompletion() 
	{
		if($this->m_oTaskTime->m_iCompletion > 0)
		{
			$iDoneDurationInSeconds = ($this->m_oTaskTime->m_iCompletion * $this->m_oTaskTime->m_iEffectiveDurationInSeconds) / 100;

			$oCompletionStartDate	= $this->m_oTaskTime->cloneStartDate();
			$oCompletionEndDate		= $oCompletionStartDate->cloneDate();
			$oCompletionEndDate->add($iDoneDurationInSeconds, BAB_DATETIME_SECOND);

			$iTaskStartDateTs	= $oCompletionStartDate->getTimeStamp();
			$iTaskEndDateTs		= $oCompletionEndDate->getTimeStamp();
			
if($iTaskEndDateTs > $this->m_iDisplayedStartDateTs && $iTaskStartDateTs < $this->m_iDisplayedEndDateTs)
			{
				$oPeriod = $this->createPeriod($iTaskStartDateTs, $iTaskEndDateTs);
				
				$oPeriod->setClassName('ganttTaskDone');
				$oPeriod->setId($this->m_oTaskTime->m_iIdTask);
				$oPeriod->setHeight(($oPeriod->getHeight() / 2));
				$this->addPeriod($oPeriod);
			}
		}
	}
}




class BAB_TM_GanttTaskDuration extends BAB_TM_GanttTask
{
	
	function BAB_TM_GanttTaskDuration()
	{
		parent::BAB_TM_GanttTask();
	}

	function buildEffectivePeriod()
	{
		$oStartDate = $this->m_oTaskTime->getStartDate();
		$oEndDate	= $this->m_oTaskTime->getEndDate();

//		BAB_TM_TaskTime::computeEndDate($oStartDate->getIsoDateTime(), $this->m_oTaskTime->m_fDuration, $this->m_oTaskTime->m_iDurationUnit, $oEndDate);
		
		if($oEndDate->getTimeStamp() > $this->m_iDisplayedStartDateTs && $oStartDate->getTimeStamp() < $this->m_iDisplayedEndDateTs)
		{
			$oPeriod = $this->createPeriod($oStartDate->getTimeStamp(), $oEndDate->getTimeStamp());
			
			$oPeriod->setClassName('ganttTaskEffectiveDuration');
			$oPeriod->setId($this->m_oTaskTime->m_iIdTask);
			$oPeriod->setHeight($oPeriod->getHeight() / 2);
			$this->addPeriod($oPeriod);
		}
	}


	function buildCompletion() 
	{
		if($this->m_oTaskTime->m_iCompletion > 0)
		{
			$oStartDate = $this->m_oTaskTime->getStartDate();
			$oEndDate	= $this->m_oTaskTime->getEndDate();

//			BAB_TM_TaskTime::computeEndDate($oStartDate->getIsoDateTime(), $this->m_oTaskTime->m_fDuration, $this->m_oTaskTime->m_iDurationUnit, $oEndDate);
			
			$iEffectiveDurationInSeconds = $oEndDate->getTimeStamp() - $oStartDate->getTimeStamp();
			$iDoneDurationInSeconds = ($iEffectiveDurationInSeconds / 100) * $this->m_oTaskTime->m_iCompletion;

			$oEndDate = $oStartDate->cloneDate();
			$oEndDate->add($iDoneDurationInSeconds, BAB_DATETIME_SECOND);

			$iTaskStartDateTs	= $oStartDate->getTimeStamp();
			$iTaskEndDateTs		= $oEndDate->getTimeStamp();
			
			if($iTaskEndDateTs > $this->m_iDisplayedStartDateTs && $iTaskStartDateTs < $this->m_iDisplayedEndDateTs)
			{
				$oPeriod = $this->createPeriod($iTaskStartDateTs, $iTaskEndDateTs);
				
				$oPeriod->setClassName('ganttTaskDone');
				$oPeriod->setId($this->m_oTaskTime->m_iIdTask);
				$oPeriod->setHeight(($oPeriod->getHeight() / 2));
				$this->addPeriod($oPeriod);
			}
		}
	}
}


class BAB_TM_GanttTaskPeriod
{
	var $m_iTop						= 0;
	var $m_iLeft					= 0;
	var $m_iHeight					= 0;
	var $m_iWidth					= 0;
	var $m_sClassName				= '';
	var $m_iId						= 0;
	
	function BAB_TM_GanttTaskPeriod()
	{
	}

	function setTop($iTop)
	{
		$this->m_iTop = (int) $iTop;
	}
	
	function getTop()
	{
		return $this->m_iTop;
	}
	
	function setLeft($iLeft)
	{
		$this->m_iLeft = (int) $iLeft;
	}
	
	function getLeft()
	{
		return $this->m_iLeft;
	}
	
	function setHeight($iHeight)
	{
		$this->m_iHeight = (int) $iHeight;
	}
	
	function getHeight()
	{
		return $this->m_iHeight;
	}
	
	function setWidth($iWidth)
	{
		$this->m_iWidth = (int) $iWidth;
	}
	
	function getWidth()
	{
		return $this->m_iWidth;
	}
	
	function setClassName($sClassName)
	{
		$this->m_sClassName = (string) $sClassName;
	}
	
	function getClassName()
	{
		return $this->m_sClassName;
	}
	
	function setId($iId)
	{
		$this->m_iId = (int) $iId;
	}
	
	function getId()
	{
		return $this->m_iId;
	}
}
?>