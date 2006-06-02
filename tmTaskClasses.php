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

	class BAB_TaskFormBase extends BAB_BaseFormProcessing
	{
		var $m_aCfg;
		var $m_aDurations;
		var $m_aAvailableTaskResponsible;
		var $m_aProposable;
		var $m_aCompletion;
		
		var $m_catResult;
		var $m_spfResult;
		var $m_linkableTaskResult;
		
		var $m_aTask;
		var $m_aTaskResponsibles;
		var $m_bIsProjectManager;
		var $m_bIsTaskResponsible;
		var $m_bIsStarted;
		var $m_bIsEnded;
		var $m_bIsFirstTask;
		var $m_iLinkedTaskCount;
		
		function BAB_TaskFormBase($iIdProjectSpace, $iIdProject, $iIdTask)
		{
			$this->initCaptions();
			$this->initTaskInfo($iIdProject, $iIdTask);
			
			$this->m_aClasses = array(
				array('iClassType' => BAB_TM_TASK, 'sClassName' => bab_translate("Task")),
				array('iClassType' => BAB_TM_CHECKPOINT, 'sClassName' => bab_translate("CheckPoint")),
				array('iClassType' => BAB_TM_TODO, 'sClassName' => bab_translate("ToDo"))
			);
			
			$this->m_aDurations = array(
				array('iDurationType' => BAB_TM_DURATION, 'sDurationName' => bab_translate("Duration")),
				array('iDurationType' => BAB_TM_DATE, 'sDurationName' => bab_translate("Date"))
			);

			$this->m_aProposable = array(
				array('iProposable' => BAB_TM_YES, 'sProposable' => bab_translate("Yes")),
				array('iProposable' => BAB_TM_NO, 'sProposable' => bab_translate("No"))
			);

			$this->m_aCompletion = array(0, 10, 20, 30, 40, 50, 60, 70, 80, 90, 100);

			$this->set_data('tg', tskmgr_getVariable('tg', 'usrTskMgr'));
			$this->set_data('iIdProjectSpace', $iIdProjectSpace);
			$this->set_data('iIdProject', $iIdProject);
			$this->set_data('iIdTask', $iIdTask);
			
			$oTmCtx =& getTskMgrContext();
			$this->m_aCfg =& $oTmCtx->getConfiguration();

			$this->initFormVariables();
			$this->initCommentaries($iIdProjectSpace, $iIdProject, $iIdTask);
			
			if(!isset($_POST['iIdTask']) && !isset($_GET['iIdTask']))
			{
				$this->set_data('is_creation', true);
				$this->initTaskForm($iIdProject, $this->m_aCfg['tasksNumerotation']);
			}
			else if( (isset($_GET['iIdTask']) || isset($_POST['iIdTask'])) && 0 != $iIdTask)
			{
				$this->set_data('is_edition', true);
			}
			else
			{
				$this->set_data('is_resubmission', true);
			}
			
			
			
			{
				bab_getAvailableTaskResponsibles($iIdProject, $this->m_aAvailableTaskResponsible);
				$this->m_catResult = bab_selectAvailableCategories($iIdProject);
				$this->m_spfResult = bab_selectAvailableSpecificFields($iIdProject);
	
				global $babDB;
				if($babDB->db_num_rows($this->m_spfResult))
				{
	//				$this->set_data('isHaveSpFields', true);
				}
			}
			
		}
		
		function initTaskInfo($iIdProject, $iIdTask)
		{
			$this->m_aTask = array();
			$this->m_aTaskResponsibles = array();
			$this->m_bIsProjectManager = bab_isAccessValid(BAB_TSKMGR_PROJECTS_MANAGERS_GROUPS_TBL, $iIdProject);
			$this->m_bIsTaskResponsible = false;
			$this->m_bIsStarted = false;
			$this->m_bIsEnded = false;
			
			if(0 != $iIdTask)
			{
				bab_getTaskResponsibles($iIdTask, $this->m_aTaskResponsibles);
				$bIsTaskResponsible = isset($this->m_aTaskResponsibles[$GLOBALS['BAB_SESS_USERID']]);
				
				if(bab_getTask($iIdTask, $this->m_aTask))
				{
					$this->m_bIsStarted = (mktime() >= strtotime($this->m_aTask['startDate']));
					$this->m_bIsEnded = (BAB_TM_ENDED == $this->m_aTask['participationStatus']);
				}
			}
			
			$iPosition = 0;
			bab_getNextTaskPosition($iIdProject, $iPosition);
			$this->m_bIsFirstTask = ($iPosition == 1);
			
			bab_getLinkedTaskCount($iIdTask, $this->m_iLinkedTaskCount);

			$this->set_data('isLinkable', false);
			$this->m_linkableTaskResult = bab_selectLinkableTask($iIdProject, $iIdTask);
			global $babDB;
			if($babDB->db_num_rows($this->m_linkableTaskResult))
			{
				$this->set_data('isLinkable', true);
			}
		}

		function initCaptions()
		{
			$this->set_caption('general', bab_translate("General"));
			$this->set_caption('predecessors', bab_translate("Predecessors"));
			$this->set_caption('resources', bab_translate("Resources"));
			$this->set_caption('commentaries', bab_translate("Commentaries"));
			$this->set_caption('spFld', bab_translate("Specific fields"));
			$this->set_caption('taskNumber', bab_translate("Number"));
			$this->set_caption('classType', bab_translate("Type"));
			$this->set_caption('categories', bab_translate("Categories"));
			$this->set_caption('description', bab_translate("Description"));
			$this->set_caption('linkedTask', bab_translate("Linked task"));
			$this->set_caption('durationType', bab_translate("Duration type"));
			$this->set_caption('duration', bab_translate("Duration"));
			$this->set_caption('plannedStartDate', bab_translate("Start Date"));
			$this->set_caption('plannedEndDate', bab_translate("End Date"));
			$this->set_caption('none', bab_translate("None"));
			$this->set_caption('taskResponsible', bab_translate("Task Responsible"));
			$this->set_caption('spfField', bab_translate("Specific Fields"));
			$this->set_caption('field', bab_translate("Field"));
			$this->set_caption('type', bab_translate("Type"));
			$this->set_caption('value', bab_translate("Value"));
			$this->set_caption('addSpf', bab_translate("Instanciate"));
			$this->set_caption('add', bab_translate("Add"));
			$this->set_caption('modify', bab_translate("Modify"));
			$this->set_caption('delete', bab_translate("Delete"));
			$this->set_caption('proposable', bab_translate("Proposed"));
			$this->set_caption('completion', bab_translate("Completion"));
		}
		
		function initFormVariables()
		{
			$bIsEditableByProjectManager = false;
			$bIsEditableByProjectManager = ($this->m_bIsProjectManager && !$this->m_bIsStarted && !$this->m_bIsEnded);
			$isTaskNumberReadOnly = ($bIsEditableByProjectManager && BAB_TM_MANUAL != $this->m_aCfg['tasksNumerotation']);

			$bIsEditableByTaskResponsible = (BAB_TM_YES == $this->m_aCfg['tskUpdateByMgr'] && $this->m_bIsStarted && !$this->m_bIsEnded);

			{
				$this->set_data('sTaskNumber', tskmgr_getVariable('sTaskNumber', ''));
				$this->set_data('sTaskNumberReadOnly', ($isTaskNumberReadOnly) ? 'readonly="readonly"' : '');
			}

			{
				$this->set_data('sDisabledClassType', ($bIsEditableByProjectManager && 0 == $this->m_iLinkedTaskCount) ? '' : 'disabled="disabled"');
				
				$iClassType = tskmgr_getVariable('iClassType', BAB_TM_TASK);
				$this->set_data('iSelectedClassType', $iClassType);
				$this->set_data('sSelectedClassType', '');
			}

			{	
				$this->set_data('sDisabledCategory', ($bIsEditableByProjectManager) ? '' : 'disabled="disabled"');
				$this->set_data('iIdSelectedCategory', tskmgr_getVariable('iIdCategory', -1));
				$this->set_data('sSelectedCategory', '');
			}
			
			{
				$this->set_data('sReadonlyDescription', ($bIsEditableByProjectManager) ? '' : 'disabled="disabled"');
				$this->set_data('sDescription', tskmgr_getVariable('sDescription', ''));
			}
			
			{
				$bIsLinkable = ($bIsEditableByProjectManager && !$this->m_bIsFirstTask);
				$this->set_data('isLinkable', $bIsLinkable);
				$this->set_data('sDisabledLinkedTask', (($bIsEditableByProjectManager && !$this->m_bIsFirstTask) ? '' : 'disabled="disabled"'));
				$this->set_data('sCkeckedLinkedTask', (-1 != (int) tskmgr_getVariable('oLinkedTask', -1)) ? 'checked="checked"' : '');
			}
			
			{
				$this->set_data('sDisabledDurationType', ($bIsEditableByProjectManager) ? '' : 'disabled="disabled"');
				$this->set_data('oDurationType', tskmgr_getVariable('oDurationType', BAB_TM_DATE));
				$this->set_data('sSelectedDuration', '');
			}
			
			{
				$this->set_data('sReadOnlyDurationType', ($bIsEditableByProjectManager) ? '' : 'readonly="readonly"');
				$this->set_data('sDuration', tskmgr_getVariable('sDuration', ''));
			}

			{
				
				$this->set_data('sReadOnlyDate', ($bIsEditableByProjectManager) ? '' : 'readonly="readonly"');
				$this->set_data('isReadOnlyDate', $bIsEditableByProjectManager);
				$this->set_data('sPlannedStartDate', tskmgr_getVariable('sPlannedStartDate', ''));
				$this->set_data('sPlannedEndDate', tskmgr_getVariable('sPlannedEndDate', ''));
			}
			
			{
				$this->set_data('sReadOnlyTaskResponsible', ($bIsEditableByProjectManager) ? '' : 'disabled="disabled"');
				$aTaskResponsible = each($this->m_aTaskResponsibles);
				$iIdTaskResponsible = (false != $aTaskResponsible) ? $aTaskResponsible['value']['id'] : 0;
				$this->set_data('iIdSlectedTaskResponsible', tskmgr_getVariable('iIdTaskResponsible', $iIdTaskResponsible));
				$this->set_data('sSlectedTaskResponsible', '');
			}
			
			{
				$this->set_data('sSelectedProposable', '');
				$this->set_data('iSelectedProposable', tskmgr_getVariable('oProposable', BAB_TM_NO));
				$this->set_data('isProposable', $bIsEditableByProjectManager);
			}
			
			{
				$this->set_data('sSelectedCompletion', '');
				$this->set_data('iSelectedCompletion', tskmgr_getVariable('oCompletion', 0));
				$this->set_data('isCompletionEnabled', 
					($this->m_bIsStarted && !$this->m_bIsEnded && 
						($this->m_bIsProjectManager || BAB_TM_YES == $this->m_aCfg['tskUpdateByMgr'] && $this->m_bIsTaskResponsible)));
			}
			
			$this->set_data('oSpfField', tskmgr_getVariable('oSpfField', -1));
			$this->set_data('sSelectedSpfField', '');
			
			$this->set_data('selectedMenu', tskmgr_getVariable('selectedMenu', 'oLiGeneral'));
			
			$this->set_data('iMajorVersion', tskmgr_getVariable('iMajorVersion', 1));
			$this->set_data('iMinorVersion', tskmgr_getVariable('iMinorVersion', 0));
			
			$this->set_data('eventTask', BAB_TM_TASK);
			$this->set_data('eventCheckPoint', BAB_TM_CHECKPOINT);
			$this->set_data('eventToDo', BAB_TM_TODO);
			$this->set_data('dtDuration', BAB_TM_DURATION);
			$this->set_data('dtDate', BAB_TM_DATE);
			$this->set_data('none', BAB_TM_NONE);
			
$this->set_data('addIdx', BAB_TM_IDX_DISPLAY_TASK_FORM);
			//$this->set_data('addIdx', BAB_TM_IDX_DISPLAY_TASK_LIST);
			$this->set_data('addAction', BAB_TM_ACTION_ADD_TASK);
			$this->set_data('modifyIdx', BAB_TM_IDX_DISPLAY_TASK_LIST);
			$this->set_data('modifyAction', BAB_TM_ACTION_MODIFY_TASK);
			$this->set_data('delIdx', BAB_TM_IDX_DISPLAY_DELETE_TASK_FORM);
			$this->set_data('delAction', '');
$this->set_data('addSpfIdx', BAB_TM_IDX_DISPLAY_TASK_FORM);
			$this->set_data('addSpfAction', BAB_TM_ACTION_CREATE_SPECIFIC_FIELD_INSTANCE);
		}
		
		function initCommentaries($iIdProjectSpace, $iIdProject, $iIdTask)
		{
			$result = bab_selectTaskCommentary($iIdTask);	
			$oList = new BAB_TM_ListBase($result);
		
			$url = $GLOBALS['babUrlScript'] . '?tg=usrTskMgr&idx=' . 
				BAB_TM_IDX_DISPLAY_COMMENTARY_FORM . '&isPopUp=1&iIdProjectSpace=' . $iIdProjectSpace .
				'&iIdProject=' . $iIdProject;
			
			$oList->set_caption('addCommentary', bab_translate("Add a commentary"));
			$oList->set_data('addCommentaryUrl', $url);
			$oList->set_data('iIdTask', $iIdTask);
			
			$oList->set_data('url', $url . '&iIdCommentary=');
			$this->set_data('taskCommentaries', bab_printTemplate($oList, 'tmUser.html', 'taskCommentariesList'));
		}

		function initTaskForm($iIdProject, $iTasksNumerotation)
		{
			bab_getNextTaskNumber($iIdProject, $iTasksNumerotation, $sTaskNumber);
			$this->set_data('sTaskNumber', $sTaskNumber);
			
			bab_getLastProjectRevision($iIdProject, $iMajorVersion, $iMinorVersion);
			$this->set_data('iMajorVersion', $iMajorVersion);
			$this->set_data('iMinorVersion', $iMinorVersion);
		}

		//getNext function
		function getNextCompletion()
		{
			$aCompletion = each($this->m_aCompletion);
			if(false != $aCompletion)
			{
				$this->get_data('iSelectedCompletion', $iCompletion);
				$this->set_data('sSelectedCompletion', ((int)$aCompletion['value'] == (int)$iCompletion) ? 
					'selected="selected"' : '');
				$this->set_data('iCompletion', $aCompletion['value']);
				return true;
			}
			return false;
		}

		function getNextProposable()
		{
			$aProposable = each($this->m_aProposable);
			if(false != $aProposable)
			{
				$this->get_data('iSelectedProposable', $iProposable);
				$this->set_data('sSelectedProposable', ((int)$aProposable['value']['iProposable'] == (int)$iProposable) ? 
					'selected="selected"' : '');
					
				$this->set_data('iProposable', $aProposable['value']['iProposable']);
				$this->set_data('sProposable', $aProposable['value']['sProposable']);
				return true;
			}
			return false;
		}

		function getNextClass()
		{
			$class = each($this->m_aClasses);
			if(false != $class)
			{
				$this->get_data('iSelectedClassType', $iClassType);
				$this->set_data('sSelectedClassType', ((int)$class['value']['iClassType'] == (int)$iClassType) ? 
					'selected="selected"' : '');
	
				$this->set_data('iClassType', $class['value']['iClassType']);
				$this->set_data('sClassName', $class['value']['sClassName']);
				return true;
			}
			return false;
		}
		
		function getNextCategory()
		{
			global $babDB;
			if(false != $this->m_catResult)
			{
				$datas = $babDB->db_fetch_assoc($this->m_catResult);
				if(false != $datas)
				{
					$this->get_data('iIdSelectedCategory', $iIdCategory);
					$this->set_data('sSelectedCategory', ($iIdCategory == $datas['id']) ? 
						'selected="selected"' : '');

					$this->set_data('iIdCategory', $datas['id']);
					$this->set_data('sCategoryName', $datas['name']);
					return true;
				}
			}
			return false;
		}
		
		function getNextDurationType()
		{
			$duration = each($this->m_aDurations);
			if(false != $duration)
			{
				$this->get_data('oDurationType', $oDurationType);
				$this->set_data('sSelectedDuration', ($duration['value']['iDurationType'] == $oDurationType) ? 
					'selected="selected"' : '');
	
				$this->set_data('iDurationType', $duration['value']['iDurationType']);
				$this->set_data('sDurationName', $duration['value']['sDurationName']);
				return true;
			}
			return false;
		}
		
		function getNextTaskResponsible()
		{
			$aResponsible = each($this->m_aAvailableTaskResponsible);
			if(false != $aResponsible)
			{
				$this->get_data('iIdSlectedTaskResponsible', $iIdTaskResponsible);
				$this->set_data('sSlectedTaskResponsible', ($aResponsible['value']['id'] == $iIdTaskResponsible) ? 
					'selected="selected"' : '');
				
				$this->set_data('iIdTaskResponsible', $aResponsible['value']['id']);
				$this->set_data('sTaskResponsibleName', $aResponsible['value']['name']);
				return true;
			}
			return false;
		}
		
		function getNextSpecificField()
		{
			global $babDB;
			if(false != $this->m_spfResult)
			{
				$datas = $babDB->db_fetch_assoc($this->m_spfResult);
				if(false != $datas)
				{
					$this->get_data('oSpfField', $oSpfField);
					$this->set_data('sSelectedSpfField', ($datas['id'] == $oSpfField) ? 
						'selected="selected"' : '');
	
					$this->set_data('iIdSpField', $datas['id']);
					$this->set_data('sSpFieldName', $datas['name']);
					return true;
				}
			}
			return false;
		}
	}
	
/*	
	class BAB_TaskForm extends BAB_BaseFormProcessing
	{
		var $m_catResult;
		var $m_spfResult;
		var $m_linkableTaskResult;
		
		var $m_aClasses;
		var $m_aDurations;
		var $m_aPersonnalizationStatus;
		
		var $m_aAvailableTaskResponsible;
		
		function BAB_TaskForm($iIdProjectSpace, $iIdProject, $iIdTask)
		{
			$this->init();
			
			$oTmCtx =& getTskMgrContext();
			$aCfg =& $oTmCtx->getConfiguration();
			$iTasksNumerotation = $aCfg['tasksNumerotation'];
			//form var
			{
				$this->set_data('tg', tskmgr_getVariable('tg', 'usrTskMgr'));
				$this->set_data('iIdProjectSpace', $iIdProjectSpace);
				$this->set_data('iIdProject', $iIdProject);
				$this->set_data('iIdTask', $iIdTask);
	
				$this->set_data('iSelectedClassType', tskmgr_getVariable('iClassType', BAB_TM_TASK));
				$this->set_data('sSelectedClassType', '');
				
				$this->set_data('sTaskNumber', tskmgr_getVariable('sTaskNumber', ''));
				$this->set_data('sTaskNumberReadOnly', (BAB_TM_MANUAL != $iTasksNumerotation) ? 'readonly="readonly"' : '');
				
				$this->set_data('iIdCategory', tskmgr_getVariable('iIdCategory', -1));
				$this->set_data('sSelectedCategory', '');
	
				$this->set_data('sDescription', tskmgr_getVariable('sDescription', ''));
				
				$oLinkedTask = tskmgr_getVariable('oLinkedTask', -1);
				$this->set_data('sCkeckedLinkedTask', (-1 != $oLinkedTask) ? 'checked="checked"' : '');
	
				$this->set_data('oDurationType', tskmgr_getVariable('oDurationType', BAB_TM_DATE));
				$this->set_data('sSelectedDuration', '');
				
				$this->set_data('sDuration', tskmgr_getVariable('sDuration', ''));
				
				$this->set_data('sStartDate', tskmgr_getVariable('sStartDate', ''));
				$this->set_data('sEndDate', tskmgr_getVariable('sEndDate', ''));
				
				$this->set_data('iIdSlectedTaskResponsible', tskmgr_getVariable('iIdTaskResponsible', 0));
				$this->set_data('sSlectedTaskResponsible', '');
	
				$this->set_data('oSpfField', tskmgr_getVariable('oSpfField', -1));
				$this->set_data('sSelectedSpfField', '');
				
				$this->set_data('selectedMenu', tskmgr_getVariable('selectedMenu', 'oLiGeneral'));
	
				$this->set_data('iMajorVersion', tskmgr_getVariable('iMajorVersion', 1));
				$this->set_data('iMinorVersion', tskmgr_getVariable('iMinorVersion', 0));
	
				$this->set_data('isHaveSpFields', false);
				$this->set_data('isHaveLinkableTask', false);
				
				$this->set_data('iSelectedPersonnalizationStatus', tskmgr_getVariable('iPersonnalizationStatus', BAB_TM_TENTATIVE));
				$this->set_data('sSelectedPersonnalizationStatus', '');
			}
			//bab_debug('iMajorVersion ==> ' . $iMajorVersion . ' iMinorVersion ==> ' . $iMinorVersion);
			
			bab_getTaskResponsibleList($iIdProject, $this->m_aAvailableTaskResponsible);
			
			$this->m_catResult = bab_selectAvailableCategories($iIdProject);
			$this->m_spfResult = bab_selectAvailableSpecificFields($iIdProject);
			
			global $babDB;
			if($babDB->db_num_rows($this->m_spfResult))
			{
				$this->set_data('isHaveSpFields', true);
			}
			
			$this->m_linkableTaskResult = bab_selectLinkableTask($iIdProject, $iIdTask);
			if($babDB->db_num_rows($this->m_linkableTaskResult))
			{
				$this->set_data('isHaveLinkableTask', true);
			}
		
			if(!isset($_POST['iIdTask']) && !isset($_GET['iIdTask']))
			{
				$this->set_data('is_creation', true);
				$this->initTaskForm($iIdProject, $iTasksNumerotation);
			}
			else if( (isset($_GET['iIdTask']) || isset($_POST['iIdTask'])) && 0 != $iIdTask)
			{
				$this->set_data('is_edition', true);
			}
			else
			{
				$this->set_data('is_resubmission', true);
			}
			
			$result = bab_selectTaskCommentary($iIdTask);	
			$oList = new BAB_TM_ListBase($result);
		
			$url = $GLOBALS['babUrlScript'] . '?tg=usrTskMgr&idx=' . 
				BAB_TM_IDX_DISPLAY_COMMENTARY_FORM . '&isPopUp=1&iIdProjectSpace=' . $iIdProjectSpace .
				'&iIdProject=' . $iIdProject;
			
			$oList->set_caption('addCommentary', bab_translate("Add a commentary"));
			$oList->set_data('addCommentaryUrl', $url);
			$oList->set_data('iIdTask', $iIdTask);
			
			$oList->set_data('url', $url . '&iIdCommentary=');
			$this->set_data('taskCommentaries', bab_printTemplate($oList, 'tmUser.html', 'taskCommentariesList'));
			
		}
	
		function init()
		{
			$this->set_caption('general', bab_translate("General"));
			$this->set_caption('predecessors', bab_translate("Predecessors"));
			$this->set_caption('resources', bab_translate("Resources"));
			$this->set_caption('commentaries', bab_translate("Commentaries"));
			$this->set_caption('spFld', bab_translate("Specific fields"));
			$this->set_caption('taskNumber', bab_translate("Number"));
			$this->set_caption('classType', bab_translate("Type"));
			$this->set_caption('categories', bab_translate("Categories"));
			$this->set_caption('description', bab_translate("Description"));
			$this->set_caption('linkedTask', bab_translate("Linked task"));
			$this->set_caption('durationType', bab_translate("Duration type"));
			$this->set_caption('duration', bab_translate("Duration"));
			$this->set_caption('startDate', bab_translate("Start Date"));
			$this->set_caption('endDate', bab_translate("End Date"));
			$this->set_caption('none', bab_translate("None"));
			$this->set_caption('taskResponsible', bab_translate("Task Responsible"));
			$this->set_caption('spfField', bab_translate("Specific Fields"));
			$this->set_caption('field', bab_translate("Field"));
			$this->set_caption('type', bab_translate("Type"));
			$this->set_caption('value', bab_translate("Value"));
			$this->set_caption('addSpf', bab_translate("Instanciate"));
			$this->set_caption('add', bab_translate("Add"));
			$this->set_caption('modify', bab_translate("Modify"));
			$this->set_caption('delete', bab_translate("Delete"));
			$this->set_caption('personnalizationStatus', bab_translate("Personnalization status"));
	
			$this->set_data('eventTask', BAB_TM_TASK);
			$this->set_data('eventCheckPoint', BAB_TM_CHECKPOINT);
			$this->set_data('eventToDo', BAB_TM_TODO);
			$this->set_data('dtDuration', BAB_TM_DURATION);
			$this->set_data('dtDate', BAB_TM_DATE);
			$this->set_data('isHaveSpFields', false);
			
			$this->set_data('none', BAB_TM_NONE);
			
	$this->set_data('addIdx', BAB_TM_IDX_DISPLAY_TASK_FORM);
			//$this->set_data('addIdx', BAB_TM_IDX_DISPLAY_TASK_LIST);
			$this->set_data('addAction', BAB_TM_ACTION_ADD_TASK);
			$this->set_data('modifyIdx', BAB_TM_IDX_DISPLAY_TASK_LIST);
			$this->set_data('modifyAction', BAB_TM_ACTION_MODIFY_TASK);
			$this->set_data('delIdx', BAB_TM_IDX_DISPLAY_DELETE_TASK_FORM);
			$this->set_data('delAction', '');
			$this->set_data('addSpfIdx', BAB_TM_IDX_DISPLAY_TASK_FORM);
			$this->set_data('addSpfAction', BAB_TM_ACTION_CREATE_SPECIFIC_FIELD_INSTANCE);
	
			$this->m_aClasses = array(
				array('iClassType' => BAB_TM_TASK, 'sClassName' => bab_translate("Task")),
				array('iClassType' => BAB_TM_CHECKPOINT, 'sClassName' => bab_translate("CheckPoint")),
				array('iClassType' => BAB_TM_TODO, 'sClassName' => bab_translate("ToDo"))
			);
			
			$this->m_aDurations = array(
				array('iDurationType' => BAB_TM_DURATION, 'sDurationName' => bab_translate("Duration")),
				array('iDurationType' => BAB_TM_DATE, 'sDurationName' => bab_translate("Date"))
			);
			
			$this->m_aPersonnalizationStatus = array(
				array('iType' => BAB_TM_TENTATIVE, 'sName' => bab_translate("Tentative")),
				array('iType' => BAB_TM_ACCEPTED, 'sName' => bab_translate("Accepted")),
				array('iType' => BAB_TM_IN_PROGRESS, 'sName' => bab_translate("In progress")),
				array('iType' => BAB_TM_ENDED, 'sName' => bab_translate("Ended"))
			);
		}
		
		function getNextPersonnalizationStatus()
		{
			$aPersonnalizationStatus = each($this->m_aPersonnalizationStatus);
			if(false != $aPersonnalizationStatus)
			{
				$this->get_data('iSelectedPersonnalizationStatus', $iPersonnalizationStatus);
				$this->set_data('sSelectedPersonnalizationStatus', ((int)$aPersonnalizationStatus['value']['iType'] == (int)$iPersonnalizationStatus) ? 
					'selected="selected"' : '');
	
				$this->set_data('iPersonnalizationStatus', $aPersonnalizationStatus['value']['iType']);
				$this->set_data('sPersonnalizationStatusName', $aPersonnalizationStatus['value']['sName']);
				return true;
			}
			return false;
		}
		
		function getNextClass()
		{
			$class = each($this->m_aClasses);
			if(false != $class)
			{
				$this->get_data('iSelectedClassType', $iClassType);
				$this->set_data('sSelectedClassType', ((int)$class['value']['iClassType'] == (int)$iClassType) ? 
					'selected="selected"' : '');
	
				$this->set_data('iClassType', $class['value']['iClassType']);
				$this->set_data('sClassName', $class['value']['sClassName']);
				return true;
			}
			return false;
		}
		
		function getNextCategory()
		{
			global $babDB;
			if(false != $this->m_catResult)
			{
				$datas = $babDB->db_fetch_assoc($this->m_catResult);
				if(false != $datas)
				{
					$this->get_data('iIdCategory', $iIdCategory);
					$this->set_data('sSelectedCategory', ($iIdCategory == $datas['id']) ? 
						'selected="selected"' : '');
					
					$this->set_data('iIdCategory', $datas['id']);
					$this->set_data('sCategoryName', $datas['name']);
					return true;
				}
			}
			return false;
		}
		
		function getNextDuration()
		{
			$duration = each($this->m_aDurations);
			if(false != $duration)
			{
				$this->get_data('oDurationType', $oDurationType);
				$this->set_data('sSelectedDuration', ($duration['value']['iDurationType'] == $oDurationType) ? 
					'selected="selected"' : '');
	
				$this->set_data('iDurationType', $duration['value']['iDurationType']);
				$this->set_data('sDurationName', $duration['value']['sDurationName']);
				return true;
			}
			return false;
		}
		
		function initTaskForm($iIdProject, $iTasksNumerotation)
		{
			bab_getNextTaskNumber($iIdProject, $iTasksNumerotation, $sTaskNumber);
			$this->set_data('sTaskNumber', $sTaskNumber);
			
			bab_getLastProjectRevision($iIdProject, $iMajorVersion, $iMinorVersion);
			$this->set_data('iMajorVersion', $iMajorVersion);
			$this->set_data('iMinorVersion', $iMinorVersion);
		}
		
		function getNextTaskResponsible()
		{
			$aResponsible = each($this->m_aAvailableTaskResponsible);
			if(false != $aResponsible)
			{
				$this->get_data('iIdSlectedTaskResponsible', $iIdTaskResponsible);
				$this->set_data('sSlectedTaskResponsible', ($aResponsible['value']['id'] == $iIdTaskResponsible) ? 
					'selected="selected"' : '');
				
				$this->set_data('iIdTaskResponsible', $aResponsible['value']['id']);
				$this->set_data('sTaskResponsibleName', $aResponsible['value']['name']);
				return true;
			}
			return false;
		}
		
		function getNextSpecificField()
		{
			global $babDB;
			if(false != $this->m_spfResult)
			{
				$datas = $babDB->db_fetch_assoc($this->m_spfResult);
				if(false != $datas)
				{
					$this->get_data('oSpfField', $oSpfField);
					$this->set_data('sSelectedSpfField', ($datas['id'] == $oSpfField) ? 
						'selected="selected"' : '');
	
					$this->set_data('iIdSpField', $datas['id']);
					$this->set_data('sSpFieldName', $datas['name']);
					return true;
				}
			}
			return false;
		}
	}
//*/
?>