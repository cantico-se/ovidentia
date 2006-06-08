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

	class BAB_TM_Task
	{
		var $m_bIsStarted				= false;
		var $m_bIsEnded					= false;
		var	$m_bIsFirstTask				= false;
		
		var $m_aTask					= null;
		
		function BAB_TM_Task()
		{
		}
		
		function loadFromDataBase($iIdTask)
		{
			$success = bab_getTask($iIdTask, $this->m_aTask);
			if($success)
			{
				if('0000-00-00 00:00:00' !== $this->m_aTask['sStartDate'])
				{
					$this->m_bIsStarted = (strtotime('now') >= strtotime($this->m_aTask['sStartDate']) && 
						BAB_TM_ENDED != $this->m_aTask['iParticipationStatus']);
				}
				$this->m_bIsEnded = (BAB_TM_ENDED == $this->m_aTask['iParticipationStatus']);
				$this->m_bIsFirstTask = ($this->m_aTask['iPosition'] == 1);
				
				/*
				bab_debug('m_bIsStarted ==> ' . ($this->m_bIsStarted ? 'Yes' : 'No'));
				bab_debug('m_bIsEnded ==> ' . ($this->m_bIsEnded ? 'Yes' : 'No'));
				bab_debug('m_bIsFirstTask ==> ' . ($this->m_bIsFirstTask ? 'Yes' : 'No'));
				//*/
			}
			return $success;
		}
	}
	
	class BAB_TaskFormBase extends BAB_BaseFormProcessing
	{
		var $m_iIdProjectSpace;
		var $m_iIdProject;
		var $m_iIdTask;
		var $m_iUserProfil;

		var $m_aDurations;
		var $m_aProposable;
		var $m_aCompletion;
		var $m_aClasses;

		function BAB_TaskFormBase()
		{
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

			$oTmCtx =& getTskMgrContext();
			$this->m_iIdProjectSpace = $oTmCtx->getIdProjectSpace();
			$this->m_iIdProject = $oTmCtx->getIdProject();
			$this->m_iIdTask = $oTmCtx->getIdTask();
			$this->m_iUserProfil = $oTmCtx->getUserProfil();

			$this->initCaptions();
			$this->initDatas();

			if(!isset($_POST['iIdTask']) && !isset($_GET['iIdTask']))
			{
				$this->set_data('is_creation', true);
			}
			else if( (isset($_GET['iIdTask']) || isset($_POST['iIdTask'])) && 0 != $this->m_iIdTask)
			{
				$this->set_data('is_edition', true);
			}
			else
			{
				$this->set_data('is_resubmission', true);
			}
		}

		function initCaptions()
		{
			$this->set_caption('sTaskNumber', bab_translate("Task number"));
			$this->set_caption('sClass', bab_translate("Type"));
			$this->set_caption('sCategories', bab_translate("Categories"));
			$this->set_caption('sGeneral', bab_translate("General"));
			$this->set_caption('sPredecessors', bab_translate("Predecessors"));
			$this->set_caption('sResources', bab_translate("Resources"));
			$this->set_caption('sCommentaries', bab_translate("Commentaries"));
			$this->set_caption('sSpFld', bab_translate("Specific fields"));
			$this->set_caption('sDescription', bab_translate("Description"));
			$this->set_caption('sLinkedTask', bab_translate("Linked task"));
			$this->set_caption('sProposable', bab_translate("Proposed"));
			$this->set_caption('sDurationType', bab_translate("Duration type"));
			$this->set_caption('sDuration', bab_translate("Duration"));
			$this->set_caption('sPlannedStartDate', bab_translate("Start Date"));
			$this->set_caption('sPlannedEndDate', bab_translate("End Date"));
			$this->set_caption('sCompletion', bab_translate("Completion"));
			$this->set_caption('sRelation', bab_translate("Relation"));
			$this->set_caption('sTaskResponsible', bab_translate("Task Responsible"));
			$this->set_caption('sNone', bab_translate("None"));
			$this->set_caption('sField', bab_translate("Field"));
			$this->set_caption('sType', bab_translate("Type"));
			$this->set_caption('sValue', bab_translate("Value"));
			$this->set_caption('sAddSpf', bab_translate("Instanciate"));
			$this->set_caption('sAdd', bab_translate("Add"));
			$this->set_caption('sModify', bab_translate("Modify"));
			$this->set_caption('sDelete', bab_translate("Delete"));
		}

		function initDatas()
		{
			$this->set_data('tg', tskmgr_getVariable('tg', 'usrTskMgr'));
			$this->set_data('isLinkable', false);
			$this->set_data('isProposable', false);
			$this->set_data('isReadOnlyDate', false);
			$this->set_data('isCompletionEnabled', false);
			$this->set_data('isResourceAvailable', false);
			$this->set_data('bIsDeletable', false);
			$this->set_data('iClass', -1);
			$this->set_data('iClassType', -1);
			$this->set_data('iIdCategory', 0);
			$this->set_data('iProposable', -1);
			$this->set_data('iDurationType', -1);
			$this->set_data('iCompletion', -1);
			$this->set_data('iIdPredecessor', -1);
			$this->set_data('iStartToStart', -1);
			$this->set_data('iEndToStart', -1);
			$this->set_data('iNone', BAB_TM_NONE);
			$this->set_data('iIdTaskResponsible', -1);
			$this->set_data('iIdSpField', -1);
			$this->set_data('iMajorVersion', tskmgr_getVariable('iMajorVersion', 1));
			$this->set_data('iMinorVersion', tskmgr_getVariable('iMinorVersion', 0));
$this->set_data('iAddSpfIdx', BAB_TM_IDX_DISPLAY_TASK_FORM);
			$this->set_data('iAddSpfAction', BAB_TM_ACTION_CREATE_SPECIFIC_FIELD_INSTANCE);
			
$this->set_data('iAddIdx', BAB_TM_IDX_DISPLAY_TASK_FORM);
			//$this->set_data('iAddIdx', BAB_TM_IDX_DISPLAY_TASK_LIST);
			$this->set_data('iAddAction', BAB_TM_ACTION_ADD_TASK);
			$this->set_data('iModifyIdx', BAB_TM_IDX_DISPLAY_TASK_LIST);
			$this->set_data('iModifyAction', BAB_TM_ACTION_MODIFY_TASK);
			$this->set_data('iDeleteIdx', BAB_TM_IDX_DISPLAY_DELETE_TASK_FORM);
			$this->set_data('iDeleteAction', '');

			$this->set_data('selectedMenu', tskmgr_getVariable('selectedMenu', 'oLiGeneral'));
			$this->set_data('iDateTypeDuration', BAB_TM_DURATION);
			$this->set_data('iDateTypeDate', BAB_TM_DATE);
			
			$this->set_data('iClassTask', BAB_TM_TASK);
			$this->set_data('iClassCheckPoint', BAB_TM_CHECKPOINT);
			$this->set_data('iClassToDo', BAB_TM_TODO);

			$this->set_data('oSpfField', tskmgr_getVariable('oSpfField', -1));

			$this->set_data('sDisabledClass', '');
			$this->set_data('sSelectedClass', '');
			$this->set_data('sDisabledCategory', '');
			$this->set_data('sSelectedCategory', '');
			$this->set_data('sReadonlyDescription', '');
			$this->set_data('sDisabledLinkedTask', '');
			$this->set_data('sCkeckedLinkedTask', '');
			$this->set_data('sSelectedProposable', '');
			$this->set_data('sDisabledDurationType', '');
			$this->set_data('sReadOnlyDuration', '');
			$this->set_data('sSelectedCompletion', '');
			$this->set_data('sSelectedPredecessor', '');
			$this->set_data('sReadOnlyTaskResponsible', '');
			$this->set_data('sSlectedTaskResponsible', '');
			$this->set_data('sSelectedLinkType', '');

			$this->set_data('sTaskNumber', '');
			$this->set_data('sTaskNumberReadOnly', '');
			$this->set_data('sClassName', '');
			$this->set_data('sCategoryName', '');
			$this->set_data('sDescription', '');
			$this->set_data('sProposable', '');
			$this->set_data('sDurationName', '');
			$this->set_data('sDuration', '');
			$this->set_data('sReadOnlyDate', '');
			$this->set_data('sPlannedStartDate', '');
			$this->set_data('sPlannedEndDate', '');
			$this->set_data('sPredecessorNumber', '');
			$this->set_data('sStartToStart', '');
			$this->set_data('sEndToStart', '');
			$this->set_data('sTaskResponsibleName', '');
			$this->set_data('sTaskCommentaries', '');
			$this->set_data('sSelectedSpfField', '');
			$this->set_data('sSpFieldName', '');
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
		
		function getNextCategory()
		{
			return false;
		}
		
		function getNextTaskResponsible()
		{
			return false;
		}
		
		function getNextSpecificField()
		{
			return false;
		}

		function getNextPrecessor()
		{
			return false;
		}
				
		function getNextPrecessorRelation()
		{
			return false;
		}
	}
	
	class BAB_TaskForm extends BAB_TaskFormBase
	{
		var $m_oTask;
		var $m_aCfg;

		var $m_aTaskResponsibles;
		var $m_aAvailableTaskResponsible;
		var $m_aLinkedTasks;

		var $m_catResult;
		var $m_spfResult;
		var $m_linkableTaskResult;
		var $m_iLinkedTaskCount;
		
		var $m_bIsManager;
		var $m_bIsManagerNoStarted;
		var $m_bIsEditableByManager;

		var $m_aRelation;

		function BAB_TaskForm()
		{
			parent::BAB_TaskFormBase();
			
			$oTmCtx =& getTskMgrContext();
			$this->m_aCfg =& $oTmCtx->getConfiguration();

			$this->m_oTask =& new BAB_TM_Task();
			
			$this->m_catResult = false;
			$this->m_spfResult = false;
			$this->m_linkableTaskResult = false;

			$this->m_aAvailableTaskResponsible = array();
			$this->getTaskInfo();

			$this->m_bIsManager = ($this->m_iUserProfil == BAB_TM_PERSONNAL_TASK_OWNER || $this->m_iUserProfil == BAB_TM_PROJECT_MANAGER);
			$this->m_bIsManagerNoStarted = ($this->m_bIsManager && !$this->m_oTask->m_bIsStarted);

			$this->m_bIsEditableByManager = ($this->m_bIsManager && !$this->m_oTask->m_bIsStarted && 0 == $this->m_iLinkedTaskCount);
			
			$this->m_aRelation = array(BAB_TM_NONE => bab_translate("None"), BAB_TM_END_TO_START => bab_translate("End to start"), BAB_TM_START_TO_START => bab_translate("Start to start"));
			
			//manager non démarré
/*
$sUserProfil = (BAB_TM_PROJECT_MANAGER == $this->m_iUserProfil) ? 'BAB_TM_PROJECT_MANAGER' : 
	((BAB_TM_PERSONNAL_TASK_OWNER == $this->m_iUserProfil) ? 'BAB_TM_PERSONNAL_TASK_OWNER' :  
	(BAB_TM_TASK_RESPONSIBLE == $this->m_iUserProfil) ? 'BAB_TM_TASK_RESPONSIBLE' : '???');

bab_debug('m_iUserProfil ==> ' . $sUserProfil);
bab_debug('m_bIsStarted ==> ' . ($this->m_oTask->m_bIsStarted ? 'Yes' : 'No'));
bab_debug('m_bIsFirstTask ==> ' . ($this->m_oTask->m_bIsFirstTask ? 'Yes' : 'No'));
bab_debug('m_iLinkedTaskCount ==> ' . $this->m_iLinkedTaskCount);
bab_debug('m_bIsEditableByManager ==> ' . (($this->m_bIsEditableByManager) ? 'Yes' : 'No'));
//*/

			//A faire ds les classes spécialisées
			/*
			bab_getLastProjectRevision($this->m_iIdProject, $iMajorVersion, $iMinorVersion);
			$this->set_data('iMajorVersion', $iMajorVersion);
			$this->set_data('iMinorVersion', $iMinorVersion);
			//*/
/*
			$this->initCommentaries($iIdProjectSpace, $iIdProject, $iIdTask);
//*/			
			$this->initFormVariables();
		}

		function getTaskInfo()
		{
			if(0 != $this->m_iIdTask)
			{
				$this->m_oTask->loadFromDataBase($this->m_iIdTask);
			}
			
			bab_getTaskResponsibles($this->m_iIdTask, $this->m_aTaskResponsibles);

bab_getLinkedTasks($this->m_iIdTask, $this->m_aLinkedTasks);
$this->m_iLinkedTaskCount = count($this->m_aLinkedTasks);

//bab_getLinkedTaskCount($this->m_iIdTask, $this->m_iLinkedTaskCount);

			$this->set_data('isLinkable', false);
			$this->m_linkableTaskResult = bab_selectLinkableTask($this->m_iIdProject, $this->m_iIdTask);
			global $babDB;
			if($babDB->db_num_rows($this->m_linkableTaskResult) > 0)
			{
				$this->set_data('isLinkable', true);
			}

			bab_getAvailableTaskResponsibles($this->m_iIdProject, $this->m_aAvailableTaskResponsible);

			$this->m_catResult = bab_selectAvailableCategories($this->m_iIdProject);
			$this->m_spfResult = bab_selectAvailableSpecificFields($this->m_iIdProject);

			global $babDB;
			if($babDB->db_num_rows($this->m_spfResult))
			{
			}
		}

		
		//Begin init form variables
		function initTaskNumber($sTaskNumber)
		{
			$this->set_data('sTaskNumber', tskmgr_getVariable('sTaskNumber', $sTaskNumber));
			$isTaskNumberEditable = ($this->m_bIsManagerNoStarted && BAB_TM_MANUAL == $this->m_aCfg['tasksNumerotation']);
			$this->set_data('sTaskNumberReadOnly', ($isTaskNumberEditable) ? '' : 'readonly="readonly"');
		}

		function initTaskClass($iClassType)
		{
			$this->set_data('iSelectedClassType', $iClassType);
			$this->set_data('sDisabledClassType', 
				($this->m_bIsManagerNoStarted && 0 == $this->m_iLinkedTaskCount) ? '' : 'disabled="disabled"');
			$this->set_data('sSelectedClassType', '');
		}

		function initCategory($iIdCategory)
		{	
			$this->set_data('iIdSelectedCategory', $iIdCategory);
			$this->set_data('sDisabledCategory', $this->m_bIsManager ? '' : 'disabled="disabled"');
		}

		function initDescription($sDescription)
		{
			$this->set_data('sDescription', $sDescription);
			$this->set_data('sReadonlyDescription', $this->m_bIsManager ? '' : 'disabled="disabled"');
		}

		function initLink($iIsLinked)
		{
			$this->set_data('sCkeckedLinkedTask', ((BAB_TM_YES == $iIsLinked) ? 'checked="checked"' : ''));
			$bIsLinkable = ($this->m_bIsManagerNoStarted && !$this->m_oTask->m_bIsFirstTask);
			$this->set_data('isLinkable', $bIsLinkable);
			$this->set_data('sDisabledLinkedTask', $bIsLinkable ? '' : 'disabled="disabled"');
		}
		
		function initDurationType($iDurationType)
		{
			$this->set_data('oDurationType', $iDurationType);
			$this->set_data('sDisabledDurationType', $this->m_bIsManagerNoStarted ? '' : 'disabled="disabled"');
			$this->set_data('sSelectedDuration', '');
		}

		function initDuration($iDuration)
		{
			$this->set_data('sDuration', $iDuration);
			$this->set_data('sReadOnlyDuration', $this->m_bIsManagerNoStarted ? '' : 'readonly="readonly"');
		}
		
		function initDates($sStartDate, $sEndDate)
		{
			$this->set_data('sPlannedStartDate', $sStartDate);
			$this->set_data('sPlannedEndDate', $sEndDate);
			$this->set_data('sReadOnlyDate', $this->m_bIsManagerNoStarted ? '' : 'readonly="readonly"');
			$this->set_data('isReadOnlyDate', !$this->m_bIsManagerNoStarted);
		}

		function initResponsible($iIdResponsible)
		{
			$this->set_data('iIdSlectedTaskResponsible', $iIdResponsible);
			$this->set_data('sReadOnlyTaskResponsible', 
				(BAB_TM_PROJECT_MANAGER == $this->m_iUserProfil) ? '' : 'disabled="disabled"');
			$this->set_data('sSlectedTaskResponsible', '');
			$this->set_data('isResourceAvailable', (0 != $this->m_iIdProject));
		}

		function initProposable($iProposable)
		{
			$this->set_data('iSelectedProposable', $iProposable);
			$this->set_data('sSelectedProposable', '');
			$this->set_data('isProposable', (BAB_TM_PROJECT_MANAGER == $this->m_iUserProfil && !$this->m_oTask->m_bIsStarted));
		}

		function initCompletion($iCompletion)
		{
			$this->set_data('iSelectedCompletion', $iCompletion);
			$this->set_data('sSelectedCompletion', '');
			$this->set_data('isCompletionEnabled', 
				($this->m_oTask->m_bIsStarted && !$this->m_oTask->m_bIsEnded && 
					($this->m_bIsManager || BAB_TM_YES == $this->m_aCfg['tskUpdateByMgr'] && 
					BAB_TM_TASK_RESPONSIBLE == $this->m_iUserProfil)));
		}
		
		function initPredecessor($iPredecessor, $iLinkType)
		{
			$this->set_data('iSelectedPredecessor', $iPredecessor);
			//$this->set_data('sSelectedPredecessor', '');
$this->set_data('sSelectedPredecessor', (0 != $this->m_iLinkedTaskCount) ? '' : 'checked="checked"');
//$this->get_data('sSelectedPredecessor', $sSelectedPredecessor);
//bab_debug('sSelectedPredecessor ==> ' . $sSelectedPredecessor);
			
			$this->set_data('iSelectedLinkType', $iLinkType);
		}
		//End init form variables

		function initFormVariables()
		{
			$this->get_data('is_creation', $bIsCreation);
			$this->get_data('is_edition', $bIsEdition);
			$this->get_data('is_resubmission', $bIsResubmission);

			if($bIsCreation || $bIsResubmission)
			{
				bab_getNextTaskNumber($this->m_iIdProject, $this->m_aCfg['tasksNumerotation'], $sTaskNumber);
				$iClassType = (int) tskmgr_getVariable('iClassType', BAB_TM_TASK);
				$iIdCategory = (int) tskmgr_getVariable('iIdCategory', 0);
				$sDescription = tskmgr_getVariable('sDescription', '');
				$iIsLinked = (-1 != (int) tskmgr_getVariable('oLinkedTask', -1)) ? BAB_TM_YES : BAB_TM_NO;
				$iDurationType = (int) tskmgr_getVariable('oDurationType', BAB_TM_DATE);
				$iDuration = (int) tskmgr_getVariable('sDuration', '');
				$sStartDate = tskmgr_getVariable('sPlannedStartDate', '');
				$sEndDate = tskmgr_getVariable('sPlannedEndDate', '');
				$iIdResponsible = (int) tskmgr_getVariable('iIdTaskResponsible', 0);
				$iProposable = (int) tskmgr_getVariable('oProposable', BAB_TM_NO);
				$iCompletion = (int) tskmgr_getVariable('oCompletion', 0);
				$iPredecessor = (int) tskmgr_getVariable('oPredecessor', -1);
				$iLinkType = (int) tskmgr_getVariable('oLinkType', -1);
			}
			else if($bIsEdition)
			{
				$aTask =& $this->m_oTask->m_aTask;
				$sTaskNumber = $aTask['sTaskNumber'];
				$iClassType = $aTask['iClass'];
				$iIdCategory = $aTask['iIdCategory'];
				$sDescription = $aTask['sDescription'];
				$iIsLinked =  $aTask['iIsLinked'];
				$iDurationType = (int) (0 != $aTask['iDuration']) ? BAB_TM_DURATION : BAB_TM_DATE;
				$iDuration = (int) $aTask['iDuration'];
				
				$sStartDate = ($this->m_oTask->m_bIsStarted) ? $aTask['sStartDate'] : $aTask['sPlannedStartDate'];
				$sEndDate =  ($this->m_oTask->m_bIsEnded) ? $aTask['sEndDate'] : $aTask['sPlannedEndDate'];
				
				if(ereg("(^[0-9]{4}-[0-9]{2}-[0-9]{2}).*$", $sStartDate, $aExplodedDate))
				{
					$sStartDate = $aExplodedDate[1];	
				}

				if(ereg("(^[0-9]{4}-[0-9]{2}-[0-9]{2}).*$", $sEndDate, $aExplodedDate))
				{
					$sEndDate = $aExplodedDate[1];	
				}
				
				$iIdResponsible = -1;
				if(is_array($this->m_aTaskResponsibles))
				{
					$aTaskResponsible = each($this->m_aTaskResponsibles);
					$iIdResponsible = (false != $aTaskResponsible) ? $aTaskResponsible['value']['id'] : 0;
					reset($this->m_aTaskResponsibles);
				}

				$iProposable = (int) (BAB_TM_TENTATIVE == $aTask['iParticipationStatus']) ? BAB_TM_YES : BAB_TM_NO;
				$iCompletion = (int) $aTask['iCompletion'];
				
				$iPredecessor = -1;
				$iLinkType = -1;
				if(BAB_TM_YES == $iIsLinked)
				{
					if($this->m_iLinkedTaskCount)
					{
						//bab_debug($aLinkedTasks);
						
						$iPredecessor = (int) $this->m_aLinkedTasks[0]['iIdPredecessorTask'];
						$iLinkType = (int) $this->m_aLinkedTasks[0]['iLinkType'];
					}
				}
			}

			$this->initTaskNumber($sTaskNumber);
			$this->initTaskClass($iClassType);
			$this->initCategory($iIdCategory);
			$this->initDescription($sDescription);
			$this->initLink($iIsLinked);
			$this->initDurationType($iDurationType);
			$this->initDuration($iDuration);
			$this->initDates($sStartDate, $sEndDate);
			$this->initResponsible($iIdResponsible);
			$this->initProposable($iProposable);
			$this->initCompletion($iCompletion);
			$this->initPredecessor($iPredecessor, $iLinkType);
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

		//getNext function
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

		function getNextPredecessor()
		{
/*
$this->get_data('sSelectedPredecessor', $sSelectedPredecessor);
bab_debug('sSelectedPredecessor ==> ' . $sSelectedPredecessor);
//*/
			global $babDB;
			if(false != $this->m_linkableTaskResult)
			{
				$datas = $babDB->db_fetch_assoc($this->m_linkableTaskResult);
				if(false != $datas)
				{
					$this->get_data('iSelectedPredecessor', $iSelectedPredecessor);
					$this->set_data('sSelectedPredecessor', ($datas['id'] == $iSelectedPredecessor) ? 'selected="selected"' : '');
					
					$this->set_data('iIdPredecessor', $datas['id']);
					$this->set_data('sPredecessorNumber', $datas['taskNumber']);
					$this->set_data('iIsStarted', $datas['isStarted']);
					return true;
				}
			}
			return false;
		}
				
		function getNextRelation()
		{
			$aRelation = each($this->m_aRelation);
			if(false != $aRelation)
			{
				$this->get_data('iSelectedLinkType', $iLinkType);
				$this->get_data('iIsStarted', $datas['isStarted']);
				$this->get_data('sSelectedPredecessor', $sSelectedPredecessor);

$this->set_data('iLink', $aRelation['key']);				
$this->set_data('sLink', $aRelation['value']);				
				
				if(BAB_TM_START_TO_START == $aRelation['key'] && '1' == $datas['isStarted'])
				{
					reset($this->m_aRelation);
					return false;
				}

//bab_debug('iLink ==> ' . $aRelation['key'] . ' iSelectedLinkType ==> ' . $iLinkType);				
				
				$this->set_data('sSelectedLinkType', '');
				if('selected="selected"' == $sSelectedPredecessor && $iLinkType == $aRelation['key'])
				{
					$this->set_data('sSelectedLinkType', 'selected="selected"');
				}
				return true;
			}
			reset($this->m_aRelation);
			return false;
		}
		
		function getNone()
		{
			static $i = 0;
			return ($i++ == 0);
		}
	}
	
	
	
	
	
	
	
	class BAB_TM_TaskValidatorBase
	{
		var $m_iIdProjectSpace			= -1;
		var $m_iIdProject				= -1;
		var $m_iIdTask					= -1;
		var $m_iUserProfil				= null;

		var $m_sTaskNumber				= null;
		var $m_sDescription				= null;
		var $m_iIdCategory				= null;
		var $m_sCreated					= null;
		var $m_sModified				= null;
		var $m_iIdUserCreated			= null;
		var $m_iIdUserModified			= null;
		var $m_iClass					= null;
		var $m_iParticipationStatus		= null;
		var $m_iIsLinked				= null;
		var $m_iIdCalEvent				= null;
		var $m_sHashCalEvent			= null;
		var $m_iDuration				= null;
		var $m_iMajorVersion			= null;
		var $m_iMinorVersion			= null;
		var $m_sColor					= null;
		var $m_iPosition				= 0;
		var $m_iCompletion				= null;
		var $m_sPlannedStartDate		= null;
		var $m_sPlannedEndDate			= null;
		var $m_sStartDate				= null;
		var $m_sEndDate					= null;
		var $m_iIsNotified				= null;
		var $m_iIdOwner					= null;

		var $m_aProjectCfg				= null;
		
		var $m_oTask					= null;
		
		var $m_aAvailableResponsibles	= null;
		var $m_aLinkedTasks				= null;
		var $m_aTaskResponsibles		= null;


		function BAB_TM_TaskValidatorBase()
		{
			$this->init();
		}
		
		function init()
		{
			$oTmCtx =& getTskMgrContext();
			
			$this->m_iIdProjectSpace =& $oTmCtx->getIdProjectSpace();
			$this->m_iIdProject =& $oTmCtx->getIdProject();
			$this->m_iIdTask =& $oTmCtx->getIdTask();

			$this->m_iUserProfil = $oTmCtx->getUserProfil();

			$this->m_aProjectCfg =& $oTmCtx->getConfiguration(); 

			$this->m_sTaskNumber			= trim(tskmgr_getVariable('sTaskNumber', ''));
			$this->m_sDescription			= trim(tskmgr_getVariable('sDescription', ''));
			$this->m_iIdCategory			= (int) tskmgr_getVariable('iIdCategory', 0);
			$this->m_sCreated				= date("Y-m-d H:i:s");
			$this->m_sModified				= date("Y-m-d H:i:s");
			$this->m_iIdUserCreated			= $GLOBALS['BAB_SESS_USERID'];
			$this->m_iIdUserModified		= $GLOBALS['BAB_SESS_USERID'];
			$this->m_iClass					= (int) tskmgr_getVariable('iClass', 0);
			$this->m_iParticipationStatus	= 0;
			$this->m_iIsLinked				= (-1 != (int) tskmgr_getVariable('oLinkType', -1)) ? BAB_TM_YES : BAB_TM_NO;
			$this->m_iIdCalEvent			= 0;
			$this->m_sHashCalEvent			= '';
			$this->m_iDuration				= (int) tskmgr_getVariable('sDuration', 0);
			$this->m_iMajorVersion			= (int) tskmgr_getVariable('iMajorVersion', 1);
			$this->m_iMinorVersion			= (int) tskmgr_getVariable('iMinorVersion', 0);
			$this->m_sColor					= '';
			$this->m_iPosition				= 0;
			$this->m_iCompletion			= (int) tskmgr_getVariable('oCompletion', 0);
			$this->m_sPlannedStartDate		= trim(tskmgr_getVariable('sPlannedStartDate', ''));
			$this->m_sPlannedEndDate		= trim(tskmgr_getVariable('sPlannedEndDate', ''));
			$this->m_sStartDate				= '';
			$this->m_sEndDate				= '';
			$this->m_iIsNotified			= BAB_TM_NO;
			$this->m_iIdOwner				= $GLOBALS['BAB_SESS_USERID'];
			
			bab_getAvailableTaskResponsibles($this->m_iIdProject, $this->m_aAvailableResponsibles);
			bab_getLinkedTasks($this->m_iIdTask, $this->m_aLinkedTasks);
			bab_getTaskResponsibles($this->m_iIdTask, $this->m_aTaskResponsibles);
			
			$this->m_oTask =& new BAB_TM_Task();

			if(0 != $this->m_iIdTask)
			{
				$this->m_oTask->loadFromDataBase($this->m_iIdTask);
			}
			else 
			{
				bab_getNextTaskPosition($this->m_iIdProject, $this->m_iPosition);
				$this->m_oTask->m_bIsFirstTask = ($this->m_iPosition == 1);
				$this->m_sTaskNumber = trim(tskmgr_getVariable('sTaskNumber', ''));
			}
		}
		
		function isTaskNumberValid()
		{
			if(strlen($this->m_sTaskNumber) > 0)
			{
				if(!is_null($this->m_aProjectCfg))
				{
					if(BAB_TM_MANUAL == $this->m_aProjectCfg['tasksNumerotation'])
					{
						$sName = mysql_escape_string(str_replace('\\', '\\\\', $this->m_sTaskNumber));
						return bab_isTaskNumberUsed($this->m_iIdProject, $this->m_iIdTask, $sName);
					}
					else
					{
						if(0 != $this->m_iIdTask && !is_null($this->m_oTask))
						{
							return($this->m_oTask->m_aTask['sTaskNumber'] === $this->m_sTaskNumber);
						}
						else
						{
							$sTaskNumber = '';
							bab_getNextTaskNumber($this->m_iIdProject, $this->m_aProjectCfg['tasksNumerotation'], $sTaskNumber);
							//bab_debug('sTaskNumber ==> ' . $this->m_sTaskNumber . ' sNextTaskNumber ==> ' . $sTaskNumber);
							return($sTaskNumber === $this->m_sTaskNumber);
						}
					}
				}
				else
				{
					bab_debug('Cannot get the configuration');
				}
			}
			else
			{
				bab_debug('sTaskNumber is empty');
			}
			return false;
		}
		
		function isResponsibleValid()
		{
			return(isset($this->m_aAvailableResponsibles[$this->m_iIdTaskResponsible]));
		}
		
		function isDateValid($sDate)
		{
			$iYear = 0;
			$iMonth = 1;
			$iDay = 2;
						
			if(strlen($sDate) > 0)
			{
				$aDate = explode('-', $sDate);
				if(count($aDate) == 3)
				{
					if($this->checkDate((int)$aDate[$iDay], (int)$aDate[$iMonth], (int)$aDate[$iYear]))
					{
						if((int)$aDate[$iYear] >= (int) date('Y'))
						{
							//bab_debug('year ==> ' . $aDate[$iYear] . ' month ==> ' . $aDate[$iMonth] . ' day ==> ' . $aDate[$iDay]);
							return true;
						}
					}
				}
			}
			return false;
		}
		
		function checkDate($day, $month, $year)
		{
		   if ($month < 1 || $month > 12 || $day < 1 || $day > 31)
		   {
			   return false;
		   }
		   
		   if (($month == 4 || $month == 6 || $month == 9 || $month == 11) && $day > 30)
		   {
			   return false;
		   }

		   if ($month == 2 && $day > (($year % 4 == 0 && ($year % 100 != 0 || $year % 400 == 0)) ? 29 : 28))
		   {
			   return false;
		   }
		   return true;
		} 

		function isValid()
		{
			return false;
		}
	}
	
	class BAB_TM_ManagerValidator extends BAB_TM_TaskValidatorBase
	{
		var $m_bIsNewTaskResponsable = false;
		
		function BAB_TM_ManagerValidator()
		{
			parent::BAB_TM_TaskValidatorBase();
			//bab_debug(__CLASS__);
		}
		
		function init()
		{
			
			//bab_debug($_POST);
			
			parent::init();
			$this->m_sDescription = trim(tskmgr_getVariable('sDescription', ''));
			$this->m_iMajorVersion = (int) tskmgr_getVariable('iMajorVersion', 1);
			$this->m_iMinorVersion = (int) tskmgr_getVariable('iMinorVersion', 0);
			
			if(0 != $this->m_iIdTask && $this->m_oTask->m_bIsStarted)
			{
				$aTask =& $this->m_oTask->m_aTask;
				
				$this->m_iClass = (int) $aTask['iClass'];
				$this->m_iIsLinked = (int) $aTask['iIsLinked'];
				$this->m_iLinkType = (int) (count($this->m_aLinkedTasks) > 0) ? $this->m_aLinkedTasks['iLinkType'] : -1;
				$this->m_iIdPredecessor = (int) (count($this->m_aLinkedTasks) > 0) ? $this->m_aLinkedTasks['iIdPredecessorTask'] : -1;
				$this->m_iDuration = (int) $aTask['iDuration'];
				$this->m_sStartDate = $aTask['sStartDate'];
				$this->m_sEndDate = $aTask['sPlannedEndDate'];

				$this->m_iIdTaskResponsible = -1;
				if(false != ($aResponsible = each($this->m_aTaskResponsibles)))
				{
					reset($this->m_aTaskResponsibles);
					$this->m_bIsNewTaskResponsable = ($this->m_iIdTaskResponsible != (int) $aResponsible['value']['id']);
				}
				$this->m_iIdCategory = (int) $aTask['iIdCategory'];
				$this->m_iCompletion = (int) tskmgr_getVariable('oCompletion', 0);
			}
			else 
			{
				$this->m_iClass = (int) tskmgr_getVariable('iClass', 0);
				$this->m_iIsLinked = (-1 != (int) tskmgr_getVariable('oLinkedTask', -1)) ? BAB_TM_YES : BAB_TM_NO;
				$this->m_iLinkType = (int) tskmgr_getVariable('oLinkType', -1); 
				$this->m_iIdPredecessor = (int) tskmgr_getVariable('iPredecessor', -1);
				$this->m_iDuration = (int) tskmgr_getVariable('sDuration', 0);
				$this->m_sStartDate = trim(tskmgr_getVariable('sPlannedStartDate', ''));
				$this->m_sEndDate = trim(tskmgr_getVariable('sPlannedEndDate', ''));
				$this->m_iIdTaskResponsible = (int) tskmgr_getVariable('iIdTaskResponsible', -1);
				$this->m_iIdCategory = (int) tskmgr_getVariable('iIdCategory', 0);
				$this->m_iCompletion = 0;
			}
		}

		function isValid()
		{
			if($this->m_oTask->m_bIsEnded)
			{
				bab_debug(__FUNCTION__ . ' the task is ended');
				return false;
			}
			
			if(false == $this->m_oTask->m_bIsStarted)
			{
				if($this->isTaskNumberValid())
				{
					$success = true;
					
					//Si la tache est liée
					if(BAB_TM_YES == $this->m_iIsLinked)
					{
						$success = bab_getTask($this->m_iIdPredecessor, $aTask);
						if($success)
						{
							$this->m_sStartDate = (BAB_TM_END_TO_START == $this->m_iLinkType) ? $aTask['sPlannedEndDate'] : $aTask['sPlannedStartDate'];						
						}
						else 
						{
							bab_debug('Error can get predecessor information');
						}
					}
					
					if($success)
					{
						if(0 != $this->m_iDuration)
						{
							return $this->isTaskValidByDuration();
						}
						else if(0 == $this->m_iDuration)
						{
							return $this->isTaskValidByDate();
						}
						else
						{
							bab_debug(__FUNCTION__ . ' unknown oDurationType');
						}
					}
				}
				else 
				{
					bab_debug(__FUNCTION__ . ' sTaskNumber is invalid');
				}
			}
			else
			{
				return($this->isResponsibleValid());
			}
			return false;
		}
		
		function isTaskValidByDuration()
		{
			//if((int)$this->m_iDuration > 0 && $this->isDateValid($this->m_sStartDate))
			if($this->isDateValid($this->m_sStartDate))
			{
				if($this->m_iUserProfil == BAB_TM_PROJECT_MANAGER && !$this->isResponsibleValid())
				{
					bab_debug(__FUNCTION__ . ': Invalid iIdTaskResponsible');
				}
				else if(strlen($this->m_sEndDate) > 0)
				{
					if(!$this->isDateValid($this->m_sEndDate))
					{
						bab_debug(__FUNCTION__ . ' sEndDate(BAB_TM_DURATION) is invalid');
					}
					else if(strtotime($this->m_sEndDate) > (strtotime($this->m_sStartDate) + ((int)$this->m_iDuration * 24 * 3600)))
					{
						return true;
					}
					else
					{
						bab_debug(__FUNCTION__ . ' sEndDate is less than sStartDate');
					}
				}
				else
				{
					return true;
				}
			}
			else
			{
				bab_debug(__FUNCTION__ . ' invalid duration');
			}
			return false;
		}

		function isTaskValidByDate()
		{
			if($this->isDateValid($this->m_sStartDate) && $this->isDateValid($this->m_sEndDate))
			{
				if(strtotime('now') < strtotime($this->m_sStartDate))
				{
					if(strtotime($this->m_sEndDate) > strtotime($this->m_sStartDate))
					{
						if($this->m_iUserProfil == BAB_TM_PROJECT_MANAGER && !$this->isResponsibleValid())
						{
							bab_debug(__FUNCTION__ . ' iIdTaskResponsible missmatch');
						}
						else 
						{
							return true;
						}
					}
					else 
					{
						bab_debug(__FUNCTION__ . ' sEndDate is less than sStartDate');
					}
				}
				else
				{
						bab_debug(__FUNCTION__ . ' sEndStart is <= now()');
				}
			}
			else 
			{
				bab_debug(__FUNCTION__ . ' invalid Date');
			}
			return false;
		}
		
		function createTask()
		{
			if($this->isValid())
			{
				$iProposable = (int) tskmgr_getVariable('oProposable', BAB_TM_NO);
				$iParticipationStatus = (int) (BAB_TM_NO == $iProposable) ? BAB_TM_ACCEPTED : BAB_TM_TENTATIVE;
				
				$this->m_iIsLinked = (!$this->m_oTask->m_bIsFirstTask && BAB_TM_YES == $this->m_iIsLinked);
				
				$iIsNotified = BAB_TM_NO;
				if(BAB_TM_YES == $iProposable)
				{
					bab_debug('Notification must be implemented');
					$iIsNotified = BAB_TM_YES;
				}
				
				$sStartDate = mysql_escape_string($this->m_sStartDate);
				$sEndDate = mysql_escape_string($this->m_sEndDate);
				
				$aTask =& $this->m_oTask->m_aTask;
				
				$aTask['iIdProject']			= $this->m_iIdProject;
				$aTask['sTaskNumber']			= mysql_escape_string($this->m_sTaskNumber);
				$aTask['sDescription']			= mysql_escape_string($this->m_sDescription);
				$aTask['iIdCategory']			= $this->m_iIdCategory;
				$aTask['sCreated']				= $this->m_sCreated;
				$aTask['iIdUserCreated']		= $this->m_iIdUserCreated;
				$aTask['sModified']				= '';
				$aTask['iIdUserModified']		= 0;
				$aTask['iClass']				= $this->m_iClass;
				$aTask['iParticipationStatus']	= $iParticipationStatus;
				$aTask['iIsLinked']				= $this->m_iIsLinked;
				$aTask['iIdCalEvent']			= $this->m_iIdCalEvent;
				$aTask['sHashCalEvent']			= $this->m_sHashCalEvent;
				$aTask['iDuration']				= $this->m_iDuration;
				$aTask['iMajorVersion']			= $this->m_iMajorVersion;
				$aTask['iMinorVersion']			= $this->m_iMinorVersion;
				$aTask['sColor']				= $this->m_sColor;
				$aTask['iPosition']				= $this->m_iPosition;
				$aTask['iCompletion']			= $this->m_iCompletion;
				$aTask['sPlannedStartDate']		= $sStartDate;
				$aTask['sPlannedEndDate']		= $sEndDate;
				$aTask['sStartDate']			= '';
				$aTask['sEndDate']				= $this->m_iDuration > 0 ? $sEndDate : '';
				$aTask['iIsNotified']			= $iIsNotified;
				$aTask['iIdOwner']				= 0 == $this->m_iIdProject ? $this->m_iIdOwner : 0;
//*				
				$iIdTask = bab_createTask($aTask);
				if(false !== $iIdTask)
				{
					if(BAB_TM_PROJECT_MANAGER == $this->m_iUserProfil)
					{
						bab_deleteTaskResponsibles($iIdTask);
						$aTaskResponsibles = array($this->m_iIdTaskResponsible);
						
						bab_setTaskResponsibles($iIdTask, $aTaskResponsibles);
					}
					
					if(BAB_TM_YES === $this->m_iIsLinked && (BAB_TM_PROJECT_MANAGER == $this->m_iUserProfil || BAB_TM_TASK_RESPONSIBLE == $this->m_iUserProfil))
					{
						bab_deleteTaskLinks($iIdTask);
						$aPredecessors = array(
							array('iIdPredecessorTask' => $this->m_iIdPredecessor, 'iLinkType' => $this->m_iLinkType)
						);
							
						bab_setTaskLinks($iIdTask, $aPredecessors);
					}
				}

				return (false !== $iIdTask);
//*/
			}
			return false;
		}
	}
	
	
	
	
/*	
	class BAB_TM_TaskContext
	{
		var $m_sTaskNumber;
		var $m_iClassType;
		var $m_oLinkedTask;
		var $m_iIdPredecessor;
		var $m_oDurationType;
		var $m_sDuration;
		var $m_sStartDate;
		var $m_sEndDate;
		var $m_iIdTaskResponsible;
		var $m_iIdCategory;
		var $m_sDescription;
		
		var $m_iIdProjectSpace;
		var $m_iIdTask;
		var $m_iIdProject;

		var $m_iMajorVersion;
		var $m_iMinorVersion;

		var $m_iUserProfil;
		var $m_aTaskResponsibles;
		
		function BAB_TM_TaskContext()
		{
			$this->m_sTaskNumber = trim(tskmgr_getVariable('sTaskNumber', ''));
			$this->m_iClassType = (int) tskmgr_getVariable('iClassType', 0);
			$this->m_oLinkedTask = (int) tskmgr_getVariable('oLinkedTask', -1);
			$this->m_iIdPredecessor = (int) tskmgr_getVariable('iIdPredecessor', -1);
			$this->m_oDurationType = (int) tskmgr_getVariable('oDurationType', -1);
			$this->m_sDuration = (int) tskmgr_getVariable('sDuration', 0);
			$this->m_sStartDate = trim(tskmgr_getVariable('sPlannedStartDate', ''));
			$this->m_sEndDate = trim(tskmgr_getVariable('sPlannedEndDate', ''));
			$this->m_iIdTaskResponsible = (int) tskmgr_getVariable('iIdTaskResponsible', -1);
			
			$this->m_iIdProjectSpace = (int) tskmgr_getVariable('iIdProjectSpace', 0);
			$this->m_iIdProject = (int) tskmgr_getVariable('iIdProject', 0);
			$this->m_iIdTask = (int) tskmgr_getVariable('iIdTask', 0);
			
			$this->m_iIdCategory = (int) tskmgr_getVariable('iIdCategory', 0);
			$this->m_sDescription = trim(tskmgr_getVariable('sDescription', ''));
			
			$this->m_iMajorVersion = (int) tskmgr_getVariable('iMajorVersion', 1);
			$this->m_iMinorVersion = (int) tskmgr_getVariable('iMinorVersion', 0);

			$oTmCtx =& getTskMgrContext();
			$this->m_iUserProfil = $oTmCtx->getUserProfil();
			
			$this->m_aTaskResponsibles =& $oTmCtx->getTaskResponsibles();
		}

		function isTaskValid()
		{
			if($this->isTaskNumberValid())
			{
				//Si tâche non liée
				if(-1 === $this->m_oLinkedTask)
				{
					if(BAB_TM_DURATION == $this->m_oDurationType)
					{
						return $this->isNoLinkedTaskValidByDuration();
					}
					else if(BAB_TM_DATE == $this->m_oDurationType)
					{
						return $this->isNoLinkedTaskValidByDate();
					}
					else
					{
						bab_debug(__FUNCTION__ . ' unknown oDurationType');
					}
				}
				else 
				{
bab_debug(__FUNCTION__ . ' linked task must be implemented');
				}
			}
			else 
			{
				bab_debug(__FUNCTION__ . ' sTaskNumber is invalid');
			}
			return false;
		}

		function isNoLinkedTaskValidByDuration()
		{
			if((int)$this->m_sDuration > 0 && $this->isDateValid($this->m_sStartDate))
			{
				if($this->m_iUserProfil == BAB_TM_PROJECT_MANAGER && !$this->isResponsibleValid())
				{
					bab_debug(__FUNCTION__ . ': Invalid iIdTaskResponsible');
				}
				else if(strlen($this->m_sEndDate) > 0)
				{
					if(!$this->isDateValid($this->m_sEndDate))
					{
						bab_debug(__FUNCTION__ . ' sEndDate(BAB_TM_DURATION) is invalid');
					}
					else if(strtotime($this->m_sEndDate) > (strtotime($this->m_sStartDate) + ((int)$this->m_sDuration * 24 * 3600)))
					{
						return true;
					}
					else
					{
						bab_debug(__FUNCTION__ . ' sEndDate is less than sStartDate');
					}
				}
				else
				{
					return true;
				}
			}
			else
			{
				bab_debug(__FUNCTION__ . ' invalid duration');
			}
			return false;
		}

		function isNoLinkedTaskValidByDate()
		{
			if($this->isDateValid($this->m_sStartDate) && $this->isDateValid($this->m_sEndDate))
			{
				if(strtotime($this->m_sEndDate) > strtotime($this->m_sStartDate))
				{
					if($this->m_iUserProfil == BAB_TM_PROJECT_MANAGER && !$this->isResponsibleValid())
					{
						bab_debug(__FUNCTION__ . ' iIdTaskResponsible missmatch');
					}
					else 
					{
						return true;
					}
				}
				else 
				{
					bab_debug(__FUNCTION__ . ' sEndDate is less than sStartDate');
				}
			}
			else 
			{
				bab_debug(__FUNCTION__ . ' invalid Date');
			}
			return false;
		}

		function isResponsibleValid()
		{
			return(isset($this->m_aTaskResponsibles[$this->m_iIdTaskResponsible]));
		}

		function isCheckPointValid()
		{
			if($this->isTaskNumberValid())
			{
				return $this->isDateValid($this->m_sEndDate);
			}
			return false;
		}
		
		function isToDoValid()
		{
			if($this->isTaskNumberValid())
			{
				return $this->isDateValid($this->m_sEndDate);
			}
			return false;
		}
	
		function isValid()
		{
			if($this->m_iUserProfil != BAB_TM_UNDEFINED)
			{
				switch($this->m_iClassType)
				{
					case BAB_TM_TASK:
						return $this->isTaskValid();
					case BAB_TM_CHECKPOINT:
						return $this->isCheckPointValid();
					case BAB_TM_TODO:
						return $this->isToDoValid();
				}
			}
			return false;
		}
	}
//*/
?>