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



class BAB_TM_GanttBase
{
	var $m_iWidth = '14';
	var $m_iHeight = '25';
	
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
	var $m_sPrevMonthUrl = "";
	var $m_sPrevWeekUrl = "";
	var $m_sNextWeekUrl = "";
	var $m_sNextMonthUrl = "";
	var $m_sGotoDateUrl = "";
	
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

	var $m_GanttViewParamUrl = '';
	
	var $m_sTitle = '';
	
	function BAB_TM_GanttBase($sStartDate, $iStartWeekDay = 1)
	{
		$this->m_sTitle = bab_translate("Gantt view");
		
		$this->m_iOnePxInSecondes = 86400 / $this->m_iWidth;
		
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
			
			$iTaskClass = bab_rp('iTaskClass', -1);
			if(-1 != $iTaskClass)
			{
				$aFilters['iTaskClass'] = $iTaskClass;
				$this->m_GanttViewParamUrl .= '&iTaskClass=' . $aFilters['iTaskClass'];
			}
			
		
			$this->initDates($sStartDate, $iStartWeekDay);

			$sStartDate = date("Y-m-d H:i:s", $this->m_aDisplayedStartDate[0]);
			$sEndDate = date("Y-m-d H:i:s", $this->m_aDisplayedEndDate[0]);
			
			global $babDB;
			$this->m_result = $babDB->db_query(bab_selectForGantt($sStartDate, $sEndDate));
		}
		
		if(false != $this->m_result)	
		{
			$this->m_iNbResult = $babDB->db_num_rows($this->m_result);
		}
		
		$this->initLayout();
	}
	
	function initDates($sStartDate, $iStartWeekDay)
	{
		global $babInstallPath;
		require_once($babInstallPath . 'utilit/dateTime.php');
		
		$this->m_sPrevMonth = bab_translate("Previous month");
		$this->m_sPrevWeek	= bab_translate("Previous week");
		$this->m_sGotoDate	= bab_translate("Go to date");
		$this->m_sNextWeek	= bab_translate("Next week");
		$this->m_sNextMonth	= bab_translate("Next month");
		$sUrlBase			= $GLOBALS['babUrlScript'] . 
			'?tg=usrTskMgr&idx=' . BAB_TM_IDX_DISPLAY_GANTT_CHART . $this->m_GanttViewParamUrl . '&date=';

		$this->setDates($sStartDate, $iStartWeekDay);
		//echo 'StartDate ==> ' . $sStartDate . '<br />';

		$oDate = BAB_DateTime::fromTimeStamp($this->m_aDisplayedStartDate[0]);
		$oDate->add(-1, BAB_DATETIME_MONTH);
		$this->m_sPrevMonthUrl = $sUrlBase . urlencode(date("Y-m-d", $oDate->_aDate[0]));
		//echo 'sPrevMonth ==> ' . date("Y-m-d", $oDate->_aDate[0]) . '<br />';

		$oDate = BAB_DateTime::fromTimeStamp($this->m_aDisplayedStartDate[0]);
		$oDate->add(-7, BAB_DATETIME_DAY);
		$this->m_sPrevWeekUrl = $sUrlBase . urlencode(date("Y-m-d", $oDate->_aDate[0]));
		//echo 'sPrevWeek ==> ' . date("Y-m-d", $oDate->_aDate[0]) . '<br />';
		
		$oDate = BAB_DateTime::fromTimeStamp($this->m_aDisplayedStartDate[0]);
		$oDate->add(7, BAB_DATETIME_DAY);
		$this->m_sNextWeekUrl = $sUrlBase . urlencode(date("Y-m-d", $oDate->_aDate[0]));
		//echo 'sNextWeek ==> ' . date("Y-m-d", $oDate->_aDate[0]) . '<br />';

		$oDate = BAB_DateTime::fromTimeStamp($this->m_aDisplayedStartDate[0]);
		$oDate->add(1, BAB_DATETIME_MONTH);
		$this->m_sNextMonthUrl = $sUrlBase . urlencode(date("Y-m-d", $oDate->_aDate[0]));
		//echo 'sNextMonth ==> ' . date("Y-m-d", $oDate->_aDate[0]) . '<br />';
		
		$this->m_sGotoDateUrl = $sUrlBase;
	}
	
	function setDates($sStartDate, $iStartWeekDay)
	{
		$this->m_iStartWeekDay = $iStartWeekDay;
		$this->m_aDisplayedStartDate = getdate(strtotime($sStartDate));
		
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
		$this->m_iGotoDateWidth = $this->m_iWidth;
		
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
//		if($this->m_iCurrMonth <= $this->m_aDisplayedEndDate['mon'])
		{
			$iLeftParentBorderWidth = 1;

			$this->m_sMonth = $this->getMonth($this->m_iCurrMonth);
		
			$this->m_iBorderLeft	= 1;
			$this->m_iBorderRight	= 0;
			$this->m_iBorderTop		= 0;
			$this->m_iBorderBottom	= 0;

			$this->m_iMonthHeigth	= $this->m_iHeight - ($this->m_iBorderTop + $this->m_iBorderBottom);

			$this->m_iMonthPosY = 0;
			$this->m_iMonthPosX = ($this->m_iDisplayedDays * $this->m_iWidth) + $this->m_iTaskCaptionWidth - $iLeftParentBorderWidth;
			
			$iNbDaysInMonth = $this->getNbDaysInMonth($this->m_iCurrMonth, $this->m_aDisplayedStartDate['year']);
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
			$iLeftParentBorderWidth = 1;

			$this->m_iBorderLeft	= 0;
			$this->m_iBorderRight	= 0;
			$this->m_iBorderTop		= 0;
			$this->m_iBorderBottom	= 1;

			$this->m_iTaskInfoPosX = 0;
			$this->m_iTaskInfoPosY = ($this->m_iHeight * $iIndex++);
			$this->m_iTaskInfoHeigth = $this->m_iHeight - ($this->m_iBorderTop + $this->m_iBorderBottom);
			$this->m_iTaskInfoWidth = $this->m_iTaskCaptionWidth - ($this->m_iBorderLeft + $this->m_iBorderRight);
			$this->m_sTaskInfoBgColor = (strlen($datas['sBgColor']) != 0) ? $datas['sBgColor'] : 'EFEFEF';
			$this->m_sTaskInfoColor =  (strlen($datas['sColor']) != 0) ? $datas['sColor'] : '000000';
			$this->m_sTaskInfo = $datas['sShortDescription'];
		
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
		
		$this->m_iColumnPosY = 0;
		$this->m_iColumnPosX = ($iIndex++ * $this->m_iWidth);
		$this->m_iColumnHeigth = ($this->m_iNbResult + 1) * $this->m_iHeight;
		$this->m_iColumnWidth = $this->m_iWidth - $iBorderWidth;
		
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
	var $m_iTaskPosX = 0;
	var $m_iTaskPosY = 0;
	var $m_iTaskHeigth = 0;
	var $m_iTaskWidth = 0;
	var $m_sTaskBgColor = 'FCC';
	var $m_sTaskColor = 'FFF';
	var $m_sTask = '';

	var $m_iTaskIndex = 1;
	
	var $m_bIsTaskCompletion = false;
	
	var $m_sAdditionnalClass = '';
	var	$m_iClass;

	function BAB_TM_Gantt($sStartDate, $iStartWeekDay = 1)
	{
		parent::BAB_TM_GanttBase($sStartDate, $iStartWeekDay);
	}
	
	function getNextTask()
	{
		global $babDB;
		
		if(false != $this->m_result && false != ($datas = $babDB->db_fetch_assoc($this->m_result)))
		{
			if(BAB_TM_TASK == $datas['iClass'])
			{
				$this->m_iBorderLeft	= 1;
				$this->m_iBorderRight	= 1;
				$this->m_iBorderTop		= 0;
				$this->m_iBorderBottom	= 1;
			}
			else {
				$this->m_iBorderLeft	= 0;
				$this->m_iBorderRight	= 0;
				$this->m_iBorderTop		= 0;
				$this->m_iBorderBottom	= 0;
			}

			$this->m_bIsTaskCompletion = false;
			
			/*
			$this->m_sAdditionnalClass = (BAB_TM_CHECKPOINT == $datas['iClass']) ? 'ganttCheckpoint' : 
				((BAB_TM_TODO == $datas['iClass']) ? 'ganttToDo' : '');
			//*/
			
			$this->m_sAdditionnalClass = $datas['sAdditionnalClass'];
			$this->m_iClass = $datas['iClass'];
			
			$oTaskStartDate = BAB_DateTime::fromIsoDateTime($datas['startDate']);
			$oTaskEndDate = BAB_DateTime::fromIsoDateTime($datas['endDate']);
			
			$iTaskStartDateTs = $oTaskStartDate->getTimeStamp();
			$iTaskEndDateTs = $oTaskEndDate->getTimeStamp();
			
			$iTaskDurationInSeconds = $iTaskEndDateTs - $iTaskStartDateTs;
			
			$this->getBox($iTaskStartDateTs, $iTaskEndDateTs, $this->m_iTaskPosX, $this->m_iTaskPosY, $this->m_iTaskHeigth, $this->m_iTaskWidth);
			$this->m_sTaskBgColor = 'B0B0B0';
			$this->m_sTask = '';
			
			$iDoneDurationInSeconds = ($datas['iCompletion'] * $iTaskDurationInSeconds) / 100;
			$iDoneEndDateTs = $iTaskStartDateTs + $iDoneDurationInSeconds;
			
			if($iDoneEndDateTs > $this->m_aDisplayedStartDate[0])
			{
				$this->m_bIsTaskCompletion = true;
				
				$this->getBox($iTaskStartDateTs, $iDoneEndDateTs, $this->m_iDonePosX, $this->m_iDonePosY, $this->m_iDoneHeigth, $this->m_iDoneWidth);
				$this->m_sDoneBgColor = '00F';
				$this->m_sDoneColor = 'FFF';
				
				/*
				$oDoneStartDate = BAB_DateTime::fromTimeStamp($iTaskStartDateTs);
				$oDoneEndDate = BAB_DateTime::fromTimeStamp($iDoneEndDateTs);
				echo 'startDate ==> ' . $oDoneStartDate->getIsoDateTime() . ' endDate ==> ' . $oDoneEndDate->getIsoDateTime() . '<br />';
				//*/
			}
			
			$this->m_iTaskIndex++;
			return true;
		}
		
		if($babDB->db_num_rows($this->m_result) > 0)
		{
			$this->m_iTaskIndex = 1;
			$babDB->db_data_seek($this->m_result, 0);
		}
		
		return false;
	}
	
	function getBox($iTaskStartDateTs, $iTaskEndDateTs, &$iPosX, &$iPosY, &$iHeigth, &$iWidth)
	{
		$iDisplayedStartDateTs =& $this->m_aDisplayedStartDate[0];
		$iDisplayedEndDateTs =& $this->m_aDisplayedEndDate[0];
		
		if($iTaskStartDateTs < $iDisplayedStartDateTs)
		{
			$iTaskStartDateTs = $iDisplayedStartDateTs;
		}
		
		if($iTaskEndDateTs > $iDisplayedEndDateTs)
		{
			$iTaskEndDateTs = $iDisplayedEndDateTs;
		}
		
		$iElaspedSecondsFromBigining = $iTaskStartDateTs - $iDisplayedStartDateTs;
		$iDisplayedTaskDurationInSeconds = $iTaskEndDateTs - $iTaskStartDateTs;
		
		$iPosX = round(($iElaspedSecondsFromBigining / $this->m_iOnePxInSecondes) - $this->m_iBorderLeft);
		$iPosY = round($this->m_iTaskIndex * $this->m_iHeight);
		$iHeigth = round($this->m_iHeight - ($this->m_iBorderTop + $this->m_iBorderBottom));
		$iWidth = round(($iDisplayedTaskDurationInSeconds / $this->m_iOnePxInSecondes) - ($this->m_iBorderLeft));
	}
}
?>