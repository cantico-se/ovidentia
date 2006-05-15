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
	require_once($babInstallPath . 'utilit/baseFormProcessingClass.php');

function displayWorkingHoursForm()
{
	class BAB_DisplayWorkingHours extends BAB_BaseFormProcessing
	{
		var $m_aWeekDays;
		var $m_db;
		var $m_result;
		
		function BAB_DisplayWorkingHours()
		{
			parent::BAB_BaseFormProcessing();
			
			$this->m_db	= & $GLOBALS['babDB'];
			
			$this->m_aWeekDays = array(
				0 => bab_translate("Sunday"), 1 => bab_translate("Monday"), 2 => bab_translate("Tuesday"),
				3 => bab_translate("Wednesday"), 4 => bab_translate("Thursday"), 5 => bab_translate("Friday"), 
				6 => bab_translate("Saturday"));
				
			$this->set_data('day', '');
			$this->set_caption('beginHour', bab_translate("start hour"));
			$this->set_caption('endHour',  bab_translate("end hour"));
			$this->set_caption('add',  bab_translate("Add"));
			$this->set_caption('save',  bab_translate("Save"));

			$this->set_data('save_idx',  BAB_TM_IDX_DISPLAY_WORKING_HOURS_FORM);
			$this->set_data('save_action',  BAB_TM_ACTION_UPDATE_WORKING_HOURS);
			$this->set_data('iIdUser', ('admTskMgr' != tskmgr_getVariable('tg', 'admTskMgr')) ? 
				$GLOBALS['BAB_SESS_USERID'] : 0);
			$this->set_data('bHaveWorkingHours',  false);
			
			$this->set_data('tg',  tskmgr_getVariable('tg', 'admTskMgr'));
			$this->set_data('idx',  '');
			$this->set_data('action',  '');
		}
		
		function queryWorkingHours($iWeekDay)
		{
			$this->get_data('iIdUser',  $iIdUser);
			$query = 
				'SELECT ' .
					'wd.weekDay, ' .
					'wh.idUser, ' .
					'LEFT(wh.startHour, 5) startHour, ' .
					'LEFT(wh.endHour, 5) endHour ' .
				'FROM ' .
					BAB_TSKMGR_TASKS_WEEK_DAYS_TBL . ' wd, ' . 
					BAB_TSKMGR_TASKS_WORKING_HOURS_TBL . ' wh ' .
				'WHERE ' .
					'wd.weekDay = \'' . $iWeekDay . '\' AND ' .
					'wh.weekDay = wd.weekDay AND ' .
					'wh.idUser = \'' . $iIdUser . '\' ' .
				'ORDER BY ' . 
					'wd.position, ' .
					'wh.startHour';
					
				//bab_debug($query);
				$this->m_result = $this->m_db->db_query($query);
				$this->set_data('bHaveWorkingHours',  (0 != $this->m_db->db_num_rows($this->m_result) ? true : false));
		}
		
		function getNextDay()
		{
			$day = each($this->m_aWeekDays);
			if(false != $day)
			{
				$this->set_data('day', $day['value']);
				$this->set_data('numDay', $day['key']);
				$this->queryWorkingHours($day['key']);
				return true;
			}
			return false;
		}
		
		function getNextWorkingHour()
		{
			if(false != $this->m_result)
			{
				$datas = $this->m_db->db_fetch_array($this->m_result);
				if(false != $datas)
				{
					$this->set_data('startHour', $datas['startHour']);
					$this->set_data('endHour', $datas['endHour']);
					return true;
				}
			}
			return false;
		}
	}
	
	global $babBody;
	
	$itemMenu = array(
		array(
			'idx' => BAB_TM_IDX_DISPLAY_WORKING_HOURS_FORM,
			'mnuStr' => bab_translate("Working hours"),
			'url' => $GLOBALS['babUrlScript'] . '?tg=' . tskmgr_getVariable('tg', '') . 
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
	$aWeekDays = array(
		0 => bab_translate("Sunday"), 1 => bab_translate("Monday"), 2 => bab_translate("Tuesday"),
		3 => bab_translate("Wednesday"), 4 => bab_translate("Thursday"), 5 => bab_translate("Friday"), 
		6 => bab_translate("Saturday"));
	
	$oTmCtx =& getTskMgrContext();
	$tblWr =& $oTmCtx->getTableWrapper();
	$tblWr->setTableName(BAB_TSKMGR_TASKS_WORKING_HOURS_TBL);
	
	$iIdUser = ('admTskMgr' != tskmgr_getVariable('tg', 'admTskMgr')) ? $GLOBALS['BAB_SESS_USERID'] : 0;
	$aAttribut = array('idUser' => $iIdUser);
	$tblWr->delete($aAttribut, 0, 1);		

	foreach($aWeekDays as $weekDay => $value)
	{
		$sStartHourIdx = 'startHour_' . $weekDay . '_';
		$aStartHour = (isset($_POST[$sStartHourIdx])) ? $_POST[$sStartHourIdx] : array();
		
		$sEndHourIdx = 'endHour_' . $weekDay . '_';
		$aEndHour = (isset($_POST[$sEndHourIdx])) ?  $_POST[$sEndHourIdx] : array();
		
		$skipFirst = false;
		
		//bab_debug($value);
		
		while( false != ($sStartHour = each($aStartHour)) && false != ($sEndHour = each($aEndHour)) )
		{
			if(false != $sStartHour && false != $sEndHour)
			{
				$aAttribut = array('idUser' => $iIdUser, 'weekDay' => $weekDay,
					'startHour' => $sStartHour['value'], 'endHour' => $sEndHour['value']);
					
				$tblWr->save($aAttribut, $skipFirst);
			}
		}
	}
}



?>