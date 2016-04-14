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
require_once($GLOBALS['babInstallPath'] . 'utilit/baseFormProcessingClass.php');





function bab_selectWorkingHours($iIdUser, $iWeekDay, &$bHaveWorkingHours)
{
	global $babDB;

	$bHaveWorkingHours = false;
	
	$query = 
		'SELECT ' .
			'wd.weekDay, ' .
			'wh.idUser, ' .
			'LEFT(wh.startHour, 5) startHour, ' .
			'LEFT(wh.endHour, 5) endHour ' .
		'FROM ' .
			BAB_WEEK_DAYS_TBL . ' wd, ' . 
			BAB_WORKING_HOURS_TBL . ' wh ' .
		'WHERE ' .
			'wd.weekDay = ' . $babDB->quote($iWeekDay) . ' AND ' .
			'wh.weekDay = wd.weekDay AND ' .
			'wh.idUser = ' . $babDB->quote($iIdUser) . ' ' .
		'ORDER BY ' . 
			'wd.position, ' .
			'wh.startHour';
			
	//bab_debug($query);
	$result = $babDB->db_query($query);
	if(false != $result)
	{
		$bHaveWorkingHours = (0 != $babDB->db_num_rows($result) ? true : false);
	}
	return $result;
}



function displayWorkingHoursForm()
{
	global $GLOBALS['babInstallPath'];
	require_once($GLOBALS['babInstallPath'] . 'utilit/calapi.php');
	
	class BAB_DisplayWorkingHours extends BAB_BaseFormProcessing
	{
		var $m_db;
		var $m_result;
		
		var $m_oWh;
		function BAB_DisplayWorkingHours()
		{
			parent::BAB_BaseFormProcessing();
			
			$this->m_db	= & $GLOBALS['babDB'];
			
			$this->set_data('day', '');
			$this->set_caption('beginHour', bab_translate("start hour"));
			$this->set_caption('endHour',  bab_translate("end hour"));
			$this->set_caption('add',  bab_translate("Add"));
			$this->set_caption('save',  bab_translate("Save"));

			$this->set_data('save_idx',  BAB_TM_IDX_DISPLAY_MENU);
			$this->set_data('save_action',  BAB_TM_ACTION_UPDATE_WORKING_HOURS);
			$this->set_data('iIdUser', ('admTskMgr' != bab_rp('tg', 'admTskMgr')) ? 
				$GLOBALS['BAB_SESS_USERID'] : 0);
			$this->set_data('bHaveWorkingHours',  false);
			
			$this->set_data('tg',  bab_rp('tg', 'admTskMgr'));
			$this->set_data('idx',  '');
			$this->set_data('action',  '');
			
			$action = bab_rp('action', '');

			$oTmCtx =& getTskMgrContext();
			$this->m_oWh =& $oTmCtx->getWorkingHoursObject();
				
			if('updateWorkingHours' != $action)
			{
				$this->m_oWh->init();
				$this->m_oWh->buildFromDataBase();
			}
		}

		function getNextDay()
		{
			$day = each($this->m_oWh->m_aWeekDays);
			if(false != $day)
			{
				$this->set_data('day', $day['value']);
				$this->set_data('numDay', $day['key']);
				$this->set_data('bHaveWorkingHours',  (count($this->m_oWh->m_aWorkingHours[$day['key']]) != 0));
				return true;
			}
			return false;
		}
		
		function getNextWorkingHour()
		{
			$this->get_data('numDay', $iWeekDay);

			$wh = each($this->m_oWh->m_aWorkingHours[$iWeekDay]);
			if(false != $wh)
			{
				$wh = $wh['value'];
				$this->set_data('startHour', $wh['sStartHour']);
				$this->set_data('endHour', $wh['sEndHour']);
				return true;
			}
			return false;
		}
	}
	
	global $babBody;
	
	$itemMenu = array(
		array(
			'idx' => BAB_TM_IDX_DISPLAY_WORKING_HOURS_FORM,
			'mnuStr' => bab_translate("Working hours"),
			'url' => $GLOBALS['babUrlScript'] . '?tg=' . bab_rp('tg', '') . 
			'&idx=' . BAB_TM_IDX_DISPLAY_WORKING_HOURS_FORM)
		);
		
	add_item_menu($itemMenu);
	$babBody->title = bab_translate("Working hours");
	
	$dwh = new BAB_DisplayWorkingHours();
	
	$babBody->babecho(bab_printTemplate($dwh, 'tmCommon.html', 'workingHours'));
}




//POST
function updateWorkingHours()
{
	$oTmCtx =& getTskMgrContext();
	$oWh =& $oTmCtx->getWorkingHoursObject();
	$oWh->init();
	$oWh->buildFromPost();
	$oWh->checkOverlapping();

	
	if(!$oWh->isError())
	{
		bab_deleteAllWorkingHours($oWh->m_iIdUser);
		
		foreach($oWh->m_aWorkingHours as $iWeekDay => $aWorkingHours)
		{
			foreach($aWorkingHours as $key => $aWorkingHour)
			{
				bab_insertWorkingHours($oWh->m_iIdUser, $iWeekDay, $aWorkingHour['sStartHour'], $aWorkingHour['sEndHour']);
			}
		}
	}
	else
	{
		$_POST['idx'] = BAB_TM_IDX_DISPLAY_WORKING_HOURS_FORM;
	}
}



class BAB_WorkingHours
{
	var $m_aWeekDays;
	var $m_aWorkingHours;
	var $m_aWorkingDays;
	
	var $m_bIsWorkingDaysMissing;
	var $m_bIsWorkingHoursInvalid;
	var $m_bIsWorkingHoursOverlapping;
	var $m_bIsWorkingDayInvalid;
	
	var $m_iIdUser;
	
	function BAB_WorkingHours()
	{
	}
	
	function init()
	{
		$this->m_aWeekDays = array(
			0 => bab_translate("Sunday"), 1 => bab_translate("Monday"), 2 => bab_translate("Tuesday"),
			3 => bab_translate("Wednesday"), 4 => bab_translate("Thursday"), 5 => bab_translate("Friday"), 
			6 => bab_translate("Saturday"));

		$this->m_aWorkingHours = array(0 => array(), 1 => array(), 2 => array(), 3 => array(),
			4 => array(), 5 => array(), 6 => array());

		//Get working days
		{
			global $GLOBALS['babInstallPath'];
			require_once($GLOBALS['babInstallPath'] . 'utilit/calapi.php');

			$this->m_iIdUser = (bab_rp('tg', 'admTskMgr') == 'admTskMgr' ? 0 : $GLOBALS['BAB_SESS_USERID']);
			$sWorkingDays = null;
			bab_calGetWorkingDays($this->m_iIdUser, $sWorkingDays);
			$this->m_aWorkingDays = array_flip(explode(',', $sWorkingDays));
		}
			
		$this->m_bIsWorkingDaysMissing = false;
		$this->m_bIsWorkingHoursInvalid = false;
		$this->m_bIsWorkingHoursOverlapping = false;
		$this->m_bIsWorkingDayInvalid = false;
	}
	
	function buildFromDataBase()
	{
		global $babDB;
		
		foreach($this->m_aWeekDays as $iWeekDay => $value)
		{
			$bHaveWorkingHours = false;
			$result = bab_selectWorkingHours($this->m_iIdUser, $iWeekDay, $bHaveWorkingHours);
			if(!$bHaveWorkingHours && 0 != $this->m_iIdUser)
			{
				$result = bab_selectWorkingHours(0, $iWeekDay, $bHaveWorkingHours);
			}
			
			if($bHaveWorkingHours)
			{
				while(false != ($datas = $babDB->db_fetch_array($result)))
				{
					$iStartTimeToSec = 0;
					$iEndTimeToSec = 0;
					$this->isHourValid($datas['startHour'], $iStartTimeToSec);
					$this->isHourValid($datas['endHour'], $iEndTimeToSec);
								
					$this->m_aWorkingHours[$iWeekDay][] = array('sStartHour' => $datas['startHour'], 
							'sEndHour' => $datas['endHour'], 'iStartTimeToSec' => $iStartTimeToSec, 
							'iEndTimeToSec' => $iEndTimeToSec);
				}
			}
		}
		reset($this->m_aWeekDays);
	}
	
	function buildFromPost()
	{
		foreach($this->m_aWeekDays as $iWeekDay => $value)
		{
			$sStartHourIdx = 'startHour_' . $iWeekDay . '_';
			$aStartHours = (isset($_POST[$sStartHourIdx])) ? $_POST[$sStartHourIdx] : array();
			
			$sEndHourIdx = 'endHour_' . $iWeekDay . '_';
			$aEndHours = (isset($_POST[$sEndHourIdx])) ?  $_POST[$sEndHourIdx] : array();
			
			$iStartTimeToSec = 0;
			$iEndTimeToSec = 0;
			$bIsValid = false;
	
			if(isset($this->m_aWorkingDays[$iWeekDay]))
			{
				if(count($aStartHours) <= 0 && count($aEndHours) <= 0)
				{
					//bab_debug('ERROR ==> ' . $value . ' is a working day, dayVal(' . $iWeekDay . ')');
					$this->m_bIsWorkingDaysMissing = true;
				}
			}
			else if(!isset($this->m_aWorkingDays[$iWeekDay]) && (count($aStartHours) > 0 || count($aEndHours) > 0))
			{
				$this->m_bIsWorkingDayInvalid = true;
			}
			
			
			while( false != ($aStartHour = each($aStartHours)) && false != ($aEndHour = each($aEndHours)) )
			{
				$bIsValid = $this->isWorkingHourValid($aStartHour['value'], $aEndHour['value'], $iStartTimeToSec, $iEndTimeToSec);
				if(!$bIsValid)
				{
					$this->m_bIsWorkingHoursInvalid = true;
				}
				
				$this->m_aWorkingHours[$iWeekDay][] = array('sStartHour' => $aStartHour['value'], 
					'sEndHour' => $aEndHour['value'], 'iStartTimeToSec' => $iStartTimeToSec, 
					'iEndTimeToSec' => $iEndTimeToSec);
				/*
				if($bIsValid)
				{
					bab_debug('sStartHour ==> (' . sprintf('%02s', $sStartHour['value']) . ' [ ' . $iTimeToSec1 . ' ]) sEndHour ==> (' . sprintf('%02s', $sEndHour['value']) . ' [ ' . $iTimeToSec2 . ' ])');
				}
				else
				{
					bab_debug('INVALID ==> [ sStartHour ==> ' . $sStartHour['value'] . ' sEndHour ==> ' . $sEndHour['value'] . ' ] <==');
				}
				//*/
			}
		}
		reset($this->m_aWeekDays);
	}
	
	function checkOverlapping()
	{
		foreach($this->m_aWeekDays as $iWeekDay => $value)
		{
			foreach($this->m_aWorkingHours[$iWeekDay] as $key => $aWorkingHour)
			{
				//bab_debug('weekDay ==> ' . $value . ' ' .$aWorkingHour['sStartHour'] . ' ' . $aWorkingHour['sEndHour']);

				$iCount = count($this->m_aWorkingHours[$iWeekDay]);
				$this->isOverlapping($key + 1, $iCount, $iWeekDay, 
					$aWorkingHour['iStartTimeToSec'], $aWorkingHour['iEndTimeToSec'],
					$aWorkingHour['sStartHour'], $aWorkingHour['sEndHour']);
			}
			reset($this->m_aWorkingHours[$iWeekDay]);
		}
		reset($this->m_aWeekDays);
	}
	
	function isOverlapping($iStartIndex, $iCount, $iWeekDay, $iStartTimeToSec, $iEndTimeToSec, $aDebut = '', $aFin = '')
	{
		global $babBody;
		global $GLOBALS['babInstallPath'];

		$iIndex = 0;
		for($iIndex = $iStartIndex; $iIndex < $iCount; $iIndex++)
		{
			$iStart = $this->m_aWorkingHours[$iWeekDay][$iIndex]['iStartTimeToSec'];
			$iEnd = $this->m_aWorkingHours[$iWeekDay][$iIndex]['iEndTimeToSec'];

			$bDebut = $this->m_aWorkingHours[$iWeekDay][$iIndex]['sStartHour'];
			$bFin = $this->m_aWorkingHours[$iWeekDay][$iIndex]['sEndHour'];

			//bab_debug($bDebut . ' ' . $bFin);
			
			if( $iStartTimeToSec < $iEnd && $iEndTimeToSec > $iStart )
			{
				//bab_debug($aDebut . ' < ' . $bFin . ' && ' . $aFin . ' > ' . $bDebut);
				$this->m_bIsWorkingHoursOverlapping = true;
			}
		}
	}
	
	function isWorkingHourValid($sStartTime, $sEndTime, &$iStartTimeToSec, &$iEndTimeToSec)
	{
		if($this->isHourValid($sStartTime, $iStartTimeToSec) && $this->isHourValid($sEndTime, $iEndTimeToSec))
		{
			return($iStartTimeToSec < $iEndTimeToSec);
		}
		return false;
	}
	
	function isHourValid($sTime, &$iTimeToSec)
	{
		$iTimeToSec = 0;
		
		$aHours = array();
		$iHours = 0;
		$iSeconds = 0;
		
		if(preg_match('/^(\d|[0-1]\d|2[0-3]):([0-5][0-9])$/', $sTime, $aHours))
		{
			$iHours = (int) $aHours[1];
			$iSeconds = (int) $aHours[2];
		}
		else
		{
			if(!preg_match('/^(\d|[0-1]\d|2[0-3])$/', $sTime, $aHours))
			{
				return false;
			}
			$iHours = (int) $aHours[1];
		}
		
		if(0 <= $iHours && 23 >= $iHours && 0 <= $iSeconds && 59 >= $iSeconds)
		{
			$iTimeToSec = (int)(3600 * $iHours) + $iSeconds;
			return true;
		}
		return false;
	}

	function isError()
	{
		return ($this->m_bIsWorkingDaysMissing || $this->m_bIsWorkingHoursInvalid || $this->m_bIsWorkingHoursOverlapping || $this->m_bIsWorkingDayInvalid);
	}
}
?>