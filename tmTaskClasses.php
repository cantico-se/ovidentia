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
		var $m_iIdProjectSpace;
		var $m_iIdProject;
		var $m_iIdTask;

		var $m_aCfg;
		var $m_aTask;
		var $m_aTaskResponsibles;

		var $m_iUserProfil;

		var $m_aDurations;
		var $m_aAvailableTaskResponsible;
		var $m_aProposable;
		var $m_aCompletion;
		
		var $m_catResult;
		var $m_spfResult;
		var $m_linkableTaskResult;
		
		var $m_bIsStarted;
		var $m_bIsEnded;
		var $m_bIsFirstTask;
		var $m_iLinkedTaskCount;
		
		var $m_bIsEditableByManager;
		var $m_bIsManager;

		function BAB_TaskFormBase()
		{
			$oTmCtx =& getTskMgrContext();

			$this->m_iIdProjectSpace = $oTmCtx->getIdProjectSpace();
			$this->m_iIdProject = $oTmCtx->getIdProject();
//			$this->m_iIdProject = 0;
			$this->m_iIdTask = $oTmCtx->getIdTask();
			$this->m_aCfg =& $oTmCtx->getConfiguration();

			$this->m_bIsStarted = false;
			$this->m_bIsEnded = false;
			$this->m_bIsFirstTask = false;

			$this->m_catResult = false;
			$this->m_spfResult = false;
			$this->m_linkableTaskResult = false;

			$this->set_data('tg', tskmgr_getVariable('tg', 'usrTskMgr'));
			$this->set_data('eventTask', BAB_TM_TASK);
			$this->set_data('eventCheckPoint', BAB_TM_CHECKPOINT);
			$this->set_data('eventToDo', BAB_TM_TODO);
			$this->set_data('dtDuration', BAB_TM_DURATION);
			$this->set_data('dtDate', BAB_TM_DATE);
			$this->set_data('none', BAB_TM_NONE);

$this->set_data('sTaskNumberReadOnly', '');
$this->set_data('sDisabledClassType', '');
$this->set_data('sSelectedClassType', '');
$this->set_data('sDisabledCategory', '');
$this->set_data('sReadonlyDescription', '');
$this->set_data('isLinkable', true);
$this->set_data('sDisabledLinkedTask', '');
$this->set_data('sCkeckedLinkedTask', '');
$this->set_data('sDisabledDurationType', '');
$this->set_data('sSelectedDuration', '');
$this->set_data('sReadOnlyDuration', '');
$this->set_data('taskCommentaries', '');
$this->set_data('sReadOnlyDurationType', '');
$this->set_data('isReadOnlyDate', false);
$this->set_data('sReadOnlyDate', '');
$this->set_data('sReadOnlyTaskResponsible', '');
$this->set_data('sSlectedTaskResponsible', '');
$this->set_data('sSelectedProposable', '');
$this->set_data('sSelectedCompletion', '');
$this->set_data('iSelectedCompletion', true);
$this->set_data('isCompletionEnabled', true);
$this->set_data('bIsDeletable', false);
$this->set_data('isProposable', true);
$this->set_data('sSelectedSpfField', '');

			$this->set_data('oSpfField', tskmgr_getVariable('oSpfField', -1));
			$this->set_data('selectedMenu', tskmgr_getVariable('selectedMenu', 'oLiGeneral'));
			$this->set_data('iMajorVersion', tskmgr_getVariable('iMajorVersion', 1));
			$this->set_data('iMinorVersion', tskmgr_getVariable('iMinorVersion', 0));

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

			$this->m_aProposable = array(
				array('iProposable' => BAB_TM_YES, 'sProposable' => bab_translate("Yes")),
				array('iProposable' => BAB_TM_NO, 'sProposable' => bab_translate("No"))
			);

			$this->m_aCompletion = array(0, 10, 20, 30, 40, 50, 60, 70, 80, 90, 100);

			$this->m_aAvailableTaskResponsible = array();

			$this->initCaptions();
			$this->getTaskInfo();
			$this->getUserProfil();

			$this->m_bIsManager = ($this->m_iUserProfil == BAB_TM_PERSONNAL_TASK_OWNER || $this->m_iUserProfil == BAB_TM_PROJECT_MANAGER);
			$this->m_bIsEditableByManager = ($this->m_bIsManager && !$this->m_bIsStarted && 0 == $this->m_iLinkedTaskCount);
/*
$sUserProfil = (BAB_TM_PROJECT_MANAGER == $this->m_iUserProfil) ? 'BAB_TM_PROJECT_MANAGER' : 
	((BAB_TM_PERSONNAL_TASK_OWNER == $this->m_iUserProfil) ? 'BAB_TM_PERSONNAL_TASK_OWNER' :  
	(BAB_TM_TASK_RESPONSIBLE == $this->m_iUserProfil) ? 'BAB_TM_TASK_RESPONSIBLE' : '???');

bab_debug('m_iUserProfil ==> ' . $sUserProfil);
bab_debug('m_bIsStarted ==> ' . ($this->m_bIsStarted ? 'Yes' : 'No'));
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

			$this->initFormVariables();
		}
		
		function getUserProfil()
		{
			$this->m_iUserProfil = BAB_TM_UNDEFINED;

			if(0 == $this->m_iIdTask) //Creation
			{
				if(0 != $this->m_iIdProject)
				{
					if(bab_isAccessValid(BAB_TSKMGR_PROJECTS_MANAGERS_GROUPS_TBL, $this->m_iIdProject))
					{
						$this->m_iUserProfil = BAB_TM_PROJECT_MANAGER;
					}
				}
				else
				{
					$aPersTaskCreator = bab_getUserIdObjects(BAB_TSKMGR_PERSONNAL_TASK_CREATOR_GROUPS_TBL);
					if(count($aPersTaskCreator) > 0 && isset($aPersTaskCreator[$this->m_iIdProjectSpace]))
					{
						$this->m_iUserProfil = BAB_TM_PERSONNAL_TASK_OWNER;
					}
				}
			}
			else // Edition
			{
				if(0 != $this->m_iIdProject)
				{
					if(bab_isAccessValid(BAB_TSKMGR_PROJECTS_MANAGERS_GROUPS_TBL, $this->m_iIdProject))
					{
						$this->m_iUserProfil = BAB_TM_PROJECT_MANAGER;
					}
					else if(isset($this->m_aTaskResponsibles[$GLOBALS['BAB_SESS_USERID']]))
					{
						$this->m_iUserProfil = BAB_TM_TASK_RESPONSIBLE;
					}
				}
				else if(isset($this->m_aTaskResponsibles[$GLOBALS['BAB_SESS_USERID']]))
				{
					$this->m_iUserProfil = BAB_TM_PERSONNAL_TASK_OWNER;
				}
			}
		}

		function getTaskInfo()
		{
			if(0 != $this->m_iIdTask)
			{
				bab_getTask($this->m_iIdTask, $this->m_aTask);
				bab_getTaskResponsibles($this->m_iIdTask, $this->m_aTaskResponsibles);
//bab_debug($this->m_aTask);
				$this->m_bIsStarted = (strlen($this->m_aTask['sStartDate']) > 0 && mktime() >= strtotime($this->m_aTask['sStartDate']) && 
					BAB_TM_ENDED != $this->m_aTask['iParticipationStatus']);
				$this->m_bIsEnded = (BAB_TM_ENDED == $this->m_aTask['iParticipationStatus']);
			}
			
			$iPosition = 0;
			bab_getNextTaskPosition($this->m_iIdProject, $iPosition);
			$this->m_bIsFirstTask = ($iPosition == 1);
			
			bab_getLinkedTaskCount($this->m_iIdTask, $this->m_iLinkedTaskCount);
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
//				$this->set_data('isHaveSpFields', true);
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

		//Begin init form variables
		function initTaskNumber($sTaskNumber)
		{
			$this->set_data('sTaskNumber', tskmgr_getVariable('sTaskNumber', $sTaskNumber));
			$isTaskNumberEditable = ($this->m_bIsEditableByManager && BAB_TM_MANUAL == $this->m_aCfg['tasksNumerotation']);
			$this->set_data('sTaskNumberReadOnly', ($isTaskNumberEditable) ? '' : 'readonly="readonly"');
		}

		function initTaskClass($iClassType)
		{
			$this->set_data('iSelectedClassType', $iClassType);
			$this->set_data('sDisabledClassType', $this->m_bIsEditableByManager ? '' : 'disabled="disabled"');
			$this->set_data('sSelectedClassType', '');
		}

		function initCategory($iIdCategory)
		{	
			$this->set_data('iIdSelectedCategory', $iIdCategory);
			$this->set_data('sDisabledCategory', $this->m_bIsEditableByManager ? '' : 'disabled="disabled"');
		}

		function initDescription($sDescription)
		{
			$this->set_data('sDescription', $sDescription);
			$this->set_data('sReadonlyDescription', $this->m_bIsEditableByManager ? '' : 'disabled="disabled"');
		}

		function initLink($iIsLinked)
		{
			$this->set_data('sCkeckedLinkedTask', ((BAB_TM_YES == $iIsLinked) ? 'checked="checked"' : ''));
			$bIsLinkable = ($this->m_bIsEditableByManager && !$this->m_bIsFirstTask);
			$this->set_data('isLinkable', $bIsLinkable);
			$this->set_data('sDisabledLinkedTask', $bIsLinkable ? '' : 'disabled="disabled"');
		}
		
		function initDurationType($iDurationType)
		{
			$this->set_data('oDurationType', $iDurationType);
			$this->set_data('sDisabledDurationType', $this->m_bIsEditableByManager ? '' : 'disabled="disabled"');
			$this->set_data('sSelectedDuration', '');
		}

		function initDuration($iDuration)
		{
			$this->set_data('sDuration', $iDuration);
			$this->set_data('sReadOnlyDuration', $this->m_bIsEditableByManager ? '' : 'readonly="readonly"');
		}
		
		function initDates($sStartDate, $sEndDate)
		{
			$this->set_data('sPlannedStartDate', $sStartDate);
			$this->set_data('sPlannedEndDate', $sEndDate);
			$this->set_data('sReadOnlyDate', $this->m_bIsEditableByManager ? '' : 'readonly="readonly"');
			$this->set_data('isReadOnlyDate', !$this->m_bIsEditableByManager);
		}

		function initResponsible($iIdResponsible)
		{
			$this->set_data('iIdSlectedTaskResponsible', $iIdResponsible);
			$this->set_data('sReadOnlyTaskResponsible', $this->m_bIsEditableByManager ? '' : 'disabled="disabled"');
			$this->set_data('sSlectedTaskResponsible', '');
		}

		function initProposable($iProposable)
		{
			$this->set_data('iSelectedProposable', $iProposable);
			$this->set_data('sSelectedProposable', '');
			$this->set_data('isProposable', (BAB_TM_PROJECT_MANAGER == $this->m_iUserProfil && !$this->m_bIsStarted));
		}

		function initCompletion($iCompletion)
		{
			$this->set_data('iSelectedCompletion', $iCompletion);
			$this->set_data('sSelectedCompletion', '');
			$this->set_data('isCompletionEnabled', 
				($this->m_bIsStarted && !$this->m_bIsEnded && 
					($this->m_bIsManager || BAB_TM_YES == $this->m_aCfg['tskUpdateByMgr'] && 
					BAB_TM_TASK_RESPONSIBLE == $this->m_iUserProfil)));
		}
		//End init form variables

		function initFormVariables()
		{
			$this->get_data('is_creation', $bIsCreation);
			$this->get_data('is_edition', $bIsEdition);
			$this->get_data('is_resubmission', $bIsResubmission);
/*			
			$sTaskNumber = '';
			$iClassType = -1;
			$iIdCategory = -1;
			$sDescription = '';
			$iIsLinked = BAB_TM_NO;
			$iDurationType = BAB_TM_DATE;
			$iDuration = 0;
			$sStartDate = '';
			$sEndDate =  '';
			$iIdResponsible = 0;
			$iProposable = 0;
//*/
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
			}
			else if($bIsEdition)
			{
				$sTaskNumber = $this->m_aTask['sTaskNumber'];
				$iClassType = $this->m_aTask['iClass'];
				$iIdCategory = $this->m_aTask['iIdCategory'];
				$sDescription = $this->m_aTask['sDescription'];
				$iIsLinked =  $this->m_aTask['iIsLinked'];
				$iDurationType = (int) (0 != $this->m_aTask['iDuration']) ? BAB_TM_DURATION : BAB_TM_DATE;
				$iDuration = (int) $this->m_aTask['iDuration'];
				$sStartDate = ($this->m_bIsStarted) ? $this->m_aTask['sStartDate'] : $this->m_aTask['sPlannedStartDate'];
				$sEndDate =  ($this->m_bIsEnded) ? $this->m_aTask['sEndDate'] : $this->m_aTask['sPlannedEndDate'];

				if(is_array($this->m_aTaskResponsibles))
				{
					$aTaskResponsible = each($this->m_aTaskResponsibles);
					$iIdResponsible = (false != $aTaskResponsible) ? $aTaskResponsible['value']['id'] : 0;
					reset($this->m_aTaskResponsibles);
				}

				$iProposable = (int) (BAB_TM_TENTATIVE == $this->m_aTask['iParticipationStatus']) ? BAB_TM_YES : BAB_TM_NO;
				$iCompletion = (int) $this->m_aTask['iCompletion'];
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
	
//*
	class BAB_TM_TaskValidatorBase
	{
		var $m_sTaskNumber			= null;
		var $m_iClassType			= null;
		var $m_oLinkedTask			= null;
		var $m_iIdPredecessor		= null;
		var $m_oDurationType		= null;
		var $m_sDuration			= null;
		var $m_sStartDate			= null;
		var $m_sEndDate				= null;
		var $m_iIdTaskResponsible	= null;
		var $m_iIdCategory			= null;
		var $m_sDescription			= null;
		var $m_iIdProjectSpace		= null;
		var $m_iIdTask				= null;
		var $m_iIdProject			= null;
		var $m_iMajorVersion		= null;
		var $m_iMinorVersion		= null;
		var $m_iUserProfil			= null;
		var $m_aTaskResponsibles	= null;

		function BAB_TM_TaskValidatorBase()
		{
			$this->init();
		}

		function init()
		{
		}
	}
//*/

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

			bab_getAvailableTaskResponsibles($this->m_iIdProject, $this->m_aTaskResponsibles);
			$this->getUserProfil();
		}
		
		function isTaskNumberValid()
		{
			if(strlen($this->m_sTaskNumber) > 0)
			{
				$aConfiguration = null;
				$bSuccess = bab_getProjectConfiguration($this->m_iIdProject, $aConfiguration);
				if(false != $bSuccess)
				{
					if(BAB_TM_MANUAL == $aConfiguration['tasksNumerotation'])
					{
						$sName = mysql_escape_string(str_replace('\\', '\\\\', $this->m_sTaskNumber));
						return bab_isTaskNumberUsed($this->m_iIdProject, $this->m_iIdTask, $sName);
					}
					else
					{
						if(0 != $this->m_iIdTask)
						{
							$aTask = array();
							if(bab_getTask($this->m_iIdTask, $aTask))
							{
								if($aTask['sTaskNumber'] === $this->m_sTaskNumber)
								{
									return true;
								}
							}
						}
						else
						{
							$sTaskNumber = '';
							bab_getNextTaskNumber($this->m_iIdProject, $aConfiguration['tasksNumerotation'], $sTaskNumber);
							
							//bab_debug('sTaskNumber ==> ' . $this->m_sTaskNumber . ' sNextTaskNumber ==> ' . $sTaskNumber);
							
							if($sTaskNumber === $this->m_sTaskNumber)
							{
								return true;
							}
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

		function getUserProfil()
		{
			$this->m_iUserProfil = BAB_TM_UNDEFINED;

			if(0 == $this->m_iIdTask) //Creation
			{
				if(0 != $this->m_iIdProject)
				{
					if(bab_isAccessValid(BAB_TSKMGR_PROJECTS_MANAGERS_GROUPS_TBL, $this->m_iIdProject))
					{
						$this->m_iUserProfil = BAB_TM_PROJECT_MANAGER;
					}
				}
				else
				{
					$aPersTaskCreator = bab_getUserIdObjects(BAB_TSKMGR_PERSONNAL_TASK_CREATOR_GROUPS_TBL);
					if(count($aPersTaskCreator) > 0 && isset($aPersTaskCreator[$this->m_iIdProjectSpace]))
					{
						$this->m_iUserProfil = BAB_TM_PERSONNAL_TASK_OWNER;
					}
				}
			}
			else // Edition
			{
				if(0 != $this->m_iIdProject)
				{
					if(bab_isAccessValid(BAB_TSKMGR_PROJECTS_MANAGERS_GROUPS_TBL, $this->m_iIdProject))
					{
						$this->m_iUserProfil = BAB_TM_PROJECT_MANAGER;
					}
					else if(isset($this->m_aTaskResponsibles[$GLOBALS['BAB_SESS_USERID']]))
					{
						$this->m_iUserProfil = BAB_TM_TASK_RESPONSIBLE;
					}
				}
				else if(isset($this->m_aTaskResponsibles[$GLOBALS['BAB_SESS_USERID']]))
				{
					$this->m_iUserProfil = BAB_TM_PERSONNAL_TASK_OWNER;
				}
			}
		}
	}
?>