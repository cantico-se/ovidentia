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
	require_once $babInstallPath . 'utilit/dateTime.php';

	class BAB_TM_Task
	{
		var $m_bIsStarted		= false;
		var $m_bIsEnded			= false;
		var	$m_bIsFirstTask		= false;
		
		var $m_iNextPosition	= 0;
		
		var $m_isPersonnal		= false;

		var $m_iIdProjectSpace	= -1;
		var $m_iIdProject		= -1;
		var $m_iIdTask			= -1;
		
		var $m_aTask			= null;
		var $m_aCfg				= null;
		
		
		function BAB_TM_Task()
		{
			$oTmCtx =& getTskMgrContext();
			
			$this->m_iIdProjectSpace =& $oTmCtx->getIdProjectSpace();
			$this->m_iIdProject =& $oTmCtx->getIdProject();
			$this->m_iIdTask =& $oTmCtx->getIdTask();
			
			$this->m_isPersonnal = (0 === $this->m_iIdProjectSpace && 0 === $this->m_iIdProject);
			
			if($this->m_isPersonnal)
			{
				$bSuccess = bab_getPersonnalTaskConfiguration($GLOBALS['BAB_SESS_USERID'], $this->m_aCfg);
				if(!$bSuccess)
				{
					$this->m_aCfg = array('endTaskReminder' => 5, 'tasksNumerotation' => BAB_TM_SEQUENTIAL, 'iEmailNotice' => BAB_TM_YES);
				}
			}
			
			if(0 != $this->m_iIdTask)
			{
				$this->loadFromDataBase();
			}
			else if(0 == $this->m_iIdTask) 
			{
				bab_getNextTaskPosition($this->m_iIdProject, $this->m_iNextPosition);
				$this->m_bIsFirstTask = ($this->m_iNextPosition == 1);
			}
		}
		
		function loadFromDataBase()
		{
			$success = bab_getTask($this->m_iIdTask, $this->m_aTask);
			if($success)
			{
				$this->m_bIsStarted = (0 != $this->m_aTask['iCompletion'] && BAB_TM_ENDED != $this->m_aTask['iParticipationStatus']);
				$this->m_bIsEnded = (BAB_TM_ENDED == $this->m_aTask['iParticipationStatus']);
				$this->m_bIsFirstTask = ($this->m_aTask['iPosition'] == 1);
				
				$this->isoDateToFrench($this->m_aTask['sStartDate']);
				$this->isoDateToFrench($this->m_aTask['sEndDate']);

				/*
				bab_debug('m_bIsStarted ==> ' . ($this->m_bIsStarted ? 'Yes' : 'No'));
				bab_debug('m_bIsEnded ==> ' . ($this->m_bIsEnded ? 'Yes' : 'No'));
				bab_debug('m_bIsFirstTask ==> ' . ($this->m_bIsFirstTask ? 'Yes' : 'No'));
				bab_debug('iPosition ==> ' . $this->m_aTask['iPosition']);
				//*/
			}
			
			
			return $success;
		}
		
		function isoDateToFrench(&$sDate)
		{
			$aDate = explode(' ', $sDate);
			if(count($aDate) == 2)
			{
				$aDate = explode('-', $aDate[0]);
				if(count($aDate) == 3)
				{
					$iYear = 0;
					$iMonth = 1;
					$iDay = 2;
					
					if(strlen($aDate[0]) == 4)
					{
						$sDate = $aDate[$iDay] . '-' . $aDate[$iMonth] . '-' . $aDate[$iYear];
					}
				}
			}
		}

		function &getConfiguration()
		{
			return $this->m_aCfg;
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
			parent::BAB_BaseFormProcessing();
			
			$this->m_aClasses = array(
				array('iClassType' => BAB_TM_TASK, 'sClassName' => bab_translate("Task")),
				array('iClassType' => BAB_TM_CHECKPOINT, 'sClassName' => bab_translate("Checkpoint")),
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
			$this->m_iIdProjectSpace =& $oTmCtx->getIdProjectSpace();
			$this->m_iIdProject =& $oTmCtx->getIdProject();
			$this->m_iIdTask =& $oTmCtx->getIdTask();
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
			$this->set_caption('sCategory', bab_translate("Category"));
			$this->set_caption('sGeneral', bab_translate("General"));
			$this->set_caption('sPredecessors', bab_translate("Predecessors"));
			$this->set_caption('sResources', bab_translate("Resources"));
			$this->set_caption('sCommentaries', bab_translate("Commentaries"));
			$this->set_caption('sSpFld', bab_translate("Specific fields"));
			$this->set_caption('sDescription', bab_translate("Description"));
			$this->set_caption('sShortDescription', bab_translate("Short description"));
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
			$this->set_caption('sAddSpf', bab_translate("Create an instance"));
			$this->set_caption('sAdd', bab_translate("Add"));
			$this->set_caption('sModify', bab_translate("Modify"));
			$this->set_caption('sDelete', bab_translate("Delete"));
			$this->set_caption('sStop', bab_translate("Stop"));
			$this->set_caption('sAnwser', bab_translate("Do you accept the task ?"));
			$this->set_caption('sProjectSpace', bab_translate("Project space"));
			$this->set_caption('sProject', bab_translate("Project"));
			$this->set_caption('sModify', bab_translate("Modify"));
			$this->set_caption('sCheckAll', bab_translate("Check all"));
			$this->set_caption('sUncheckAll', bab_translate("Uncheck all"));
		}

		function initDatas()
		{
			$this->set_data('tg', bab_rp('tg', 'usrTskMgr'));
			$this->set_data('isLinkable', false);
			$this->set_data('isProposable', false);
			$this->set_data('isReadOnlyDate', false);
			$this->set_data('isCompletionEnabled', false);
			$this->set_data('isResourceAvailable', false);
			$this->set_data('isAnswerEnable', false);
			$this->set_data('bIsProjectSpace', true);
			$this->set_data('isModifiable', false);
			$this->set_data('isDeletable', false);
			$this->set_data('isProject', (int) bab_rp('isProject', 0));
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
			$this->set_data('iMajorVersion', bab_rp('iMajorVersion', 1));
			$this->set_data('iMinorVersion', bab_rp('iMinorVersion', 0));
			
			$sFromIdx = bab_rp('sFromIdx', BAB_TM_IDX_DISPLAY_MY_TASK_LIST);
			if(!isFromIdxValid($sFromIdx))
			{
				$sFromIdx = BAB_TM_IDX_DISPLAY_MY_TASK_LIST;
			}
			$this->set_data('sFromIdx', $sFromIdx);

			$this->set_data('iAddSpfIdx', BAB_TM_IDX_DISPLAY_TASK_FORM);
			$this->set_data('iAddSpfAction', BAB_TM_ACTION_CREATE_SPECIFIC_FIELD_INSTANCE);
			$this->set_data('iAddIdx', $sFromIdx);
			$this->set_data('iAddAction', BAB_TM_ACTION_ADD_TASK);
			$this->set_data('iModifyIdx', $sFromIdx);
			$this->set_data('iModifyAction', BAB_TM_ACTION_MODIFY_TASK);
			$this->set_data('iDeleteIdx', BAB_TM_IDX_DISPLAY_DELETE_TASK_FORM);
			$this->set_data('iDeleteAction', '');
			
			$this->set_data('selectedMenu', bab_rp('selectedMenu', 'oLiGeneral'));
			$this->set_data('iDateTypeDuration', BAB_TM_DURATION);
			$this->set_data('iDateTypeDate', BAB_TM_DATE);
			
			$this->set_data('iClassTask', BAB_TM_TASK);
			$this->set_data('iClassCheckPoint', BAB_TM_CHECKPOINT);
			$this->set_data('iClassToDo', BAB_TM_TODO);

			$this->set_data('oSpfField', bab_rp('oSpfField', -1));

			$this->set_data('sDisabledClass', '');
			$this->set_data('sSelectedClass', '');
			$this->set_data('sDisabledCategory', '');
			$this->set_data('sSelectedCategory', '');
			$this->set_data('sReadonlyDescription', '');
			$this->set_data('sReadonlyShortDescription', '');
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
			$this->set_data('sShortDescription', '');
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
			
			$this->set_data('sProjectSpace', '');
			$this->set_data('sProject', '');
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
			reset($this->m_aProposable);
			return false;
		}

		function getNextAnswer()
		{
			$aProposable = each($this->m_aProposable);
			if(false != $aProposable)
			{
				$this->set_data('iProposable', $aProposable['value']['iProposable']);
				$this->set_data('sProposable', $aProposable['value']['sProposable']);
				return true;
			}
			reset($this->m_aProposable);
			return false;
		}

		function getNextClass()
		{
			$class = each($this->m_aClasses);
			if(false != $class)
			{
				$this->get_data('iSelectedClass', $iClassType);
				$this->set_data('sSelectedClass', ((int)$class['value']['iClassType'] == (int)$iClassType) ? 
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
		var $m_aDependingTasks;

		var $m_catResult;
		var $m_spfResult;
		var $m_spfInstResult;
		var $m_spfValueResult;
		var $m_linkableTaskResult;
		var $m_iLinkedTaskCount;
		var $m_iDependingTasksCount;
		
		var $m_bIsManager;

		var $m_aRelation;

		var $m_bDisplayEditorLink = false;
		var $m_sEditTaskDescriptionUrl = '#';
		var $m_iUseEditor = 0;
		
		function BAB_TaskForm()
		{
			parent::BAB_TaskFormBase();
			
			$this->m_catResult = false;
			$this->m_spfResult = false;
			$this->m_linkableTaskResult = false;
			$this->m_spfValueResult = false;

			$this->m_aAvailableTaskResponsible = array();
			$this->getTaskInfo();

			$this->m_bIsManager = ($this->m_iUserProfil == BAB_TM_PERSONNAL_TASK_OWNER || 
				$this->m_iUserProfil == BAB_TM_PROJECT_MANAGER);
			
			$this->m_aRelation = array(BAB_TM_NONE => bab_translate("None"), BAB_TM_END_TO_START => bab_translate("End to start"), BAB_TM_START_TO_START => bab_translate("Start to start"));

			$bIsModifiable = ($this->m_bIsManager ||  (is_array($this->m_aCfg) && BAB_TM_YES === (int) $this->m_aCfg['tskUpdateByMgr']));
			$this->set_data('isModifiable', $bIsModifiable);
			
			$this->set_data('isDeletable', ($this->m_bIsManager));
			
			$this->m_iUseEditor = (int) bab_rp('iUseEditor', 0);
			$isProject = (int) bab_rp('isProject', 0);
			$this->m_bDisplayEditorLink = (0 === $this->m_iUseEditor && $this->m_bIsManager);
			
			$this->m_sEditTaskDescriptionUrl = $GLOBALS['babUrlScript'] . '?tg=' . urlencode('usrTskMgr') . 
				'&idx=' . urlencode(BAB_TM_IDX_DISPLAY_TASK_FORM) . '&iIdProjectSpace=' . 
				urlencode($this->m_iIdProjectSpace) . '&iIdProject=' . urlencode($this->m_iIdProject) .
				'&iIdTask=' . urlencode($this->m_iIdTask) . '&iUseEditor=1&isProject=' . $isProject;
			
			
			//A faire ds les classes spécialisées
			/*
			bab_getLastProjectRevision($this->m_iIdProject, $iMajorVersion, $iMinorVersion);
			$this->set_data('iMajorVersion', $iMajorVersion);
			$this->set_data('iMinorVersion', $iMinorVersion);
			//*/
//*
			$this->initCommentaries($this->m_iIdProjectSpace, $this->m_iIdProject, $this->m_iIdTask);
//*/			
			$this->initFormVariables();
		}

		function getTaskInfo()
		{
			$this->m_oTask =& new BAB_TM_Task();
			
			if($this->m_oTask->m_isPersonnal)
			{
				$this->set_data('bIsProjectSpace', false);
				$this->set_data('sProject', bab_translate("Personnal task"));
				
				$this->m_aCfg =& $this->m_oTask->getConfiguration();
			}
			else
			{
				if(bab_getProjectSpace($this->m_iIdProjectSpace, $aProjectSpace))
				{
					$this->set_data('sProjectSpace', $aProjectSpace['name']);
				}
				
				if(bab_getProject($this->m_iIdProject, $aProject))
				{
					$this->set_data('sProject', $aProject['name']);
				}
				
				$oTmCtx =& getTskMgrContext();
				$this->m_aCfg =& $oTmCtx->getConfiguration();
			}
			
			
			bab_getTaskResponsibles($this->m_iIdTask, $this->m_aTaskResponsibles);

			bab_getLinkedTasks($this->m_iIdTask, $this->m_aLinkedTasks);
			$this->m_iLinkedTaskCount = count($this->m_aLinkedTasks);

			bab_getDependingTasks($this->m_iIdTask, $this->m_aDependingTasks);
			$this->m_iDependingTasksCount = count($this->m_aDependingTasks);
			
			//bab_debug('iDependingTasksCount ==> ' . $this->m_iDependingTasksCount);
			
			$this->set_data('isLinkable', false);
			$this->m_linkableTaskResult = bab_selectLinkableTask($this->m_iIdProject, $this->m_iIdTask);
			global $babDB;
			if($babDB->db_num_rows($this->m_linkableTaskResult) > 0)
			{
				$this->set_data('isLinkable', true);
			}

			bab_getAvailableTaskResponsibles($this->m_iIdProject, $this->m_aAvailableTaskResponsible);

			$iIdUser = (0 === $this->m_iIdProjectSpace && 0 === $this->m_iIdProject) ? $GLOBALS['BAB_SESS_USERID'] : 0;
			
			$this->m_catResult = bab_selectAvailableCategories($this->m_iIdProjectSpace, $this->m_iIdProject, $iIdUser);
			$this->m_spfResult = bab_selectAvailableSpecificFieldClassesByProject($this->m_iIdProjectSpace, $this->m_iIdProject);
			$this->m_spfInstResult = bab_selectAllSpecificFieldInstance($this->m_iIdTask);
			
			$this->set_caption('sProjectSpace', bab_translate("Project space"));
			$this->set_caption('sProject', bab_translate("Project"));

		}

		
		//Begin init form variables
		function initTaskNumber($sTaskNumber)
		{
			$this->set_data('sTaskNumber', bab_rp('sTaskNumber', $sTaskNumber));
			$isTaskNumberEditable = ($this->m_bIsManager && BAB_TM_MANUAL == $this->m_aCfg['tasksNumerotation']);
			$this->set_data('sTaskNumberReadOnly', ($isTaskNumberEditable) ? '' : 'readonly="readonly"');
		}

		function initTaskClass($iClassType)
		{
			$this->set_data('iSelectedClass', $iClassType);
			
			$this->set_data('sDisabledClass', 
				(($this->m_bIsManager && 0 == $this->m_iLinkedTaskCount && 0 == $this->m_iDependingTasksCount) ? '' : 'disabled="disabled"'));
			$this->set_data('sSelectedClass', '');
		}

		function initCategory($iIdCategory)
		{	
			$this->set_data('iIdSelectedCategory', $iIdCategory);
			$this->set_data('sDisabledCategory', $this->m_bIsManager ? '' : 'disabled="disabled"');
		}

		function initDescription($sDescription)
		{
			require_once $GLOBALS['babInstallPath'] . 'utilit/editorincl.php';
			$oEditor = new bab_contentEditor('bab_taskManagerDescription');
			$oEditor->setContent($sDescription);
			
			if(1 === $this->m_iUseEditor)			
			{
				$this->set_data('sDescription', $oEditor->getEditor());	
			}
			else 
			{
				$this->set_data('sDescription', $oEditor->getHtml());
			}
			
			$this->set_data('sReadonlyDescription', 'disabled="disabled"');
		}

		function initShortDescription($sShortDescription)
		{
			$this->set_data('sShortDescription', $sShortDescription);
			$this->set_data('sReadonlyShortDescription', $this->m_bIsManager ? '' : 'disabled="disabled"');
		}

		function initLink($iIsLinked)
		{
			$this->set_data('sCkeckedLinkedTask', ((BAB_TM_YES == $iIsLinked) ? 'checked="checked"' : ''));
			$bIsLinkable = ($this->m_bIsManager && !$this->m_oTask->m_bIsFirstTask);
			$this->set_data('isLinkable', $bIsLinkable);
			$this->set_data('sDisabledLinkedTask', $bIsLinkable ? '' : 'disabled="disabled"');
		}
		
		function initDurationType($iDurationType)
		{
			$this->set_data('oDurationType', $iDurationType);
			$this->set_data('sDisabledDurationType', $this->m_bIsManager ? '' : 'disabled="disabled"');
			$this->set_data('sSelectedDuration', '');
		}

		function initDuration($iDuration)
		{
			$this->set_data('sDuration', $iDuration);
			$this->set_data('sReadOnlyDuration', $this->m_bIsManager ? '' : 'readonly="readonly"');
		}
		
		function initDates($sStartDate, $sEndDate)
		{
			$this->set_data('sPlannedStartDate', $sStartDate);
			$this->set_data('sPlannedEndDate', $sEndDate);
			$this->set_data('sReadOnlyDate', $this->m_bIsManager ? '' : 'readonly="readonly"');
			$this->set_data('isReadOnlyDate', !$this->m_bIsManager);
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
			$this->set_data('isProposable', (BAB_TM_PROJECT_MANAGER == $this->m_iUserProfil));
		}

		function initCompletion($iCompletion)
		{
			$this->set_data('iSelectedCompletion', $iCompletion);
			$this->set_data('sSelectedCompletion', '');
			
			$bIsParticipationStatusOk = false;
			if(0 != $this->m_iIdTask)
			{
				$iParticipationStatus =& $this->m_oTask->m_aTask['iParticipationStatus'];
				$bIsParticipationStatusOk = 
					((BAB_TM_ACCEPTED == $iParticipationStatus || $iParticipationStatus == BAB_TM_IN_PROGRESS) && $this->m_bIsManager);
			}
			
			$bIsToDoOrCheckpoint = ($this->m_oTask->m_aTask['iClass'] == BAB_TM_CHECKPOINT || 
				$this->m_oTask->m_aTask['iClass'] == BAB_TM_TODO);
			
			$this->set_data('isCompletionEnabled', 
				((($this->m_bIsManager || BAB_TM_TASK_RESPONSIBLE == $this->m_iUserProfil && BAB_TM_YES == $this->m_aCfg['tskUpdateByMgr'] 
					) && $bIsParticipationStatusOk) || $bIsToDoOrCheckpoint));
		}
		
		function initPredecessor($iPredecessor, $iLinkType)
		{
			$this->set_data('iSelectedPredecessor', $iPredecessor);
			$this->set_data('sSelectedPredecessor', (0 != $this->m_iLinkedTaskCount) ? '' : 'checked="checked"');
			$this->set_data('iSelectedLinkType', $iLinkType);
		}
		
		function initAnwser($bIsCreation)
		{
			$isAnswerEnable = false;
			if(!$bIsCreation && 0 != $this->m_iIdTask)
			{
				$iParticipationStatus =& $this->m_oTask->m_aTask['iParticipationStatus'];
				$isAnswerEnable = (BAB_TM_TENTATIVE == $iParticipationStatus && BAB_TM_TASK_RESPONSIBLE == $this->m_iUserProfil);
			}
			$this->set_data('isAnswerEnable', $isAnswerEnable);
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
				$iClassType = (int) bab_rp('iClass', BAB_TM_TASK);
				$iIdCategory = (int) bab_rp('iIdCategory', 0);
				$sDescription = bab_rp('sDescription', '');
				$sShortDescription = bab_rp('sShortDescription', '');
				$iDurationType = (int) bab_rp('oDurationType', BAB_TM_DATE);
				$iDuration = (int) bab_rp('sDuration', '');
				$sStartDate = bab_rp('sPlannedStartDate', '');
				$sEndDate = bab_rp('sPlannedEndDate', '');
				$iIdResponsible = (int) bab_rp('iIdTaskResponsible', 0);
				$iProposable = (int) bab_rp('oProposable', BAB_TM_NO);
				$iCompletion = (int) bab_rp('oCompletion', 0);
				$iPredecessor = (int) bab_rp('iPredecessor', -1);
				
				$iIsLinked = -1;
				$iLinkType = -1;
				if(-1 != $iPredecessor && bab_getTask($iPredecessor, $aTask))
				{
					//zero based
					$iPosition = $aTask['iPosition'] -1;
					if( isset($_POST['oLinkType']) && isset($_POST['oLinkType'][$iPosition]) )
					{
						$iLinkType = $_POST['oLinkType'][$iPosition];
						$iIsLinked = (-1 != $iLinkType) ? BAB_TM_YES : BAB_TM_NO;
					}
				}
			}
			else if($bIsEdition)
			{
				$aTask =& $this->m_oTask->m_aTask;
				$sTaskNumber = $aTask['sTaskNumber'];
				$iClassType = $aTask['iClass'];
				
				$iIdCategory = (int) bab_rp('iIdCategory', $aTask['iIdCategory']);
				$sDescription = bab_rp('sDescription', $aTask['sDescription']);
				$sShortDescription = bab_rp('sShortDescription', $aTask['sShortDescription']);
				$iDurationType = (int) bab_rp('oDurationType', ((int) $aTask['iDuration'] != 0) ? BAB_TM_DURATION : BAB_TM_DATE);
				$iDuration = (int) bab_rp('sDuration', $aTask['iDuration']);

				$sStartDate = bab_rp('sPlannedStartDate', $aTask['sStartDate']);
				$sEndDate =  bab_rp('sPlannedEndDate', $aTask['sEndDate']);
				
				if(preg_match("/(^[0-9]{4}-[0-9]{2}-[0-9]{2}).*$/", $sStartDate, $aExplodedDate))
				{
					$sStartDate = $aExplodedDate[1];	
				}

				if(preg_match("/(^[0-9]{4}-[0-9]{2}-[0-9]{2}).*$/", $sEndDate, $aExplodedDate))
				{
					$sEndDate = $aExplodedDate[1];	
				}
				
				$iIdResponsible = -1;
				if(isset($_POST['iIdTaskResponsible']))
				{
					$iIdResponsible = (int) bab_rp('iIdTaskResponsible', -1);
				}
				else 
				{
					if(is_array($this->m_aTaskResponsibles))
					{
						$aTaskResponsible = each($this->m_aTaskResponsibles);
						$iIdResponsible = (false != $aTaskResponsible) ? $aTaskResponsible['value']['id'] : 0;
						reset($this->m_aTaskResponsibles);
					}
				}

				$iProposable = (int) (BAB_TM_TENTATIVE == $aTask['iParticipationStatus']) ? BAB_TM_YES : BAB_TM_NO;
				$iCompletion = (int) bab_rp('oCompletion', $aTask['iCompletion']);

				$iPosition = $aTask['iPosition'] -1;
				$iIsLinked = BAB_TM_NO;
				$iLinkType = -1;
				$iPredecessor = -1;
				if(isset($_POST['oLinkType']) && isset($_POST['oLinkType'][$iPosition]) )
				{
					$iPredecessor = (int) bab_rp('iPredecessor', -1);
					$iLinkType = $_POST['oLinkType'][$iPosition];
					$iIsLinked = BAB_TM_YES;
				}
				else 
				{
					$iIsLinked = $aTask['iIsLinked'];
					if(BAB_TM_YES == $iIsLinked)
					{
						if($this->m_iLinkedTaskCount)
						{
							$iPredecessor = (int) $this->m_aLinkedTasks[0]['iIdPredecessorTask'];
							$iLinkType = (int) $this->m_aLinkedTasks[0]['iLinkType'];
						}
					}
					else 
					{
					}
				}
				
			}

			$this->initTaskNumber($sTaskNumber);
			$this->initTaskClass($iClassType);
			$this->initCategory($iIdCategory);
			$this->initDescription($sDescription);
			$this->initShortDescription($sShortDescription);
			$this->initLink($iIsLinked);
			$this->initDurationType($iDurationType);
			$this->initDuration($iDuration);
			$this->initDates($sStartDate, $sEndDate);
			$this->initResponsible($iIdResponsible);
			$this->initProposable($iProposable);
			$this->initCompletion($iCompletion);
			$this->initPredecessor($iPredecessor, $iLinkType);
			$this->initAnwser($bIsCreation);
		}
		
		function initCommentaries($iIdProjectSpace, $iIdProject, $iIdTask)
		{
			$result = bab_selectTaskCommentary($iIdTask);	
			$oList = new BAB_TM_ListBase($result);
		
			$url = $GLOBALS['babUrlScript'] . '?tg=usrTskMgr&idx=' . 
				BAB_TM_IDX_DISPLAY_COMMENTARY_FORM . '&isPopUp=1&iIdProjectSpace=' . $iIdProjectSpace .
				'&iIdProject=' . $iIdProject . '&iIdTask=' . $iIdTask;
			
			$oList->set_caption('addCommentary', bab_translate("Add a commentary"));
			$oList->set_data('addCommentaryUrl', $url);
			$oList->set_data('iIdTask', $iIdTask);
			
			$oList->set_data('url', $url . '&iIdCommentary=');
			$this->set_data('sTaskCommentaries', bab_printTemplate($oList, 'tmUser.html', 'taskCommentariesList'));
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
			global $babDB;
			if(false != $this->m_linkableTaskResult)
			{
				$datas = $babDB->db_fetch_assoc($this->m_linkableTaskResult);
				if(false != $datas)
				{
					$this->get_data('iSelectedPredecessor', $iSelectedPredecessor);
					$this->set_data('sSelectedPredecessor', ($datas['id'] == $iSelectedPredecessor) ? 'checked="checked"' : '');
					$this->set_data('iIdPredecessor', $datas['id']);

					$sPredecessor = (strlen(trim($datas['shortDescription']) > 0)) ? $datas['shortDescription'] : $datas['taskNumber'];
					$this->set_data('sPredecessorNumber', $sPredecessor);
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
				
				$this->set_data('sSelectedLinkType', '');
				if('checked="checked"' == $sSelectedPredecessor && $iLinkType == $aRelation['key'])
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
		
		function getNextSpecificFieldInstance()
		{
			global $babDB;
			if(false != $this->m_spfInstResult)
			{
				$datas = $babDB->db_fetch_assoc($this->m_spfInstResult);
				if(false != $datas)
				{
					$this->set_data('sSpFldInstanceName', $datas['sFieldName']);
					$this->set_data('sSpFldInstanceType', $datas['sType']);
					$this->set_data('sSpFldInstanceValue', $datas['sValue']);
					$this->set_data('iSpFldInstanceClass', $datas['iType']);
					$this->set_data('iSpFldInstanceId', $datas['iIdSpecificFieldInstance']);
					
					$this->set_data('sSpFldInstanceSelected', '');
					if(BAB_TM_RADIO_FIELD == $datas['iType'])
					{
						$this->m_spfValueResult = bab_selectSpecificFieldClassValues($datas['iIdSpFldClass']);
					}
					
					return true;
				}
			}
			return false;
		}
		
		function getNextSpecificFieldInstanceValue()
		{
			global $babDB;
			if(false != $this->m_spfValueResult)
			{
				$datas = $babDB->db_fetch_assoc($this->m_spfValueResult);
				if(false != $datas)
				{
					$this->get_data('sSpFldInstanceValue', $sSpFldInstanceValue);
					$this->set_data('sSpFldInstanceSelected', ($datas['sValue'] == $sSpFldInstanceValue) ? 'selected="selected"' : '');
					$this->set_data('sSpFldInstanceRdValue', $datas['sValue']);
					
					return true;
				}
			}
			return false;
			
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
		var $m_sShortDescription		= null;
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

		var $m_iLinkType 				= null; 
		var $m_iIdPredecessor 			= null;
		var $m_iIdTaskResponsible 		= null;
		var $m_iAnswer					= null;
		
		var $m_aCfg						= null;
		
		var $m_oTask					= null;
		
		var $m_aAvailableResponsibles	= null;
//		var $m_aLinkedTasks				= null;
		var $m_aTaskResponsibles		= null;
		var $m_aDependingTasks			= null;
		
		var $m_oSendMail				= null;

		
		
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

			$this->m_oTask =& new BAB_TM_Task();
			
			$this->m_sTaskNumber			= trim(bab_rp('sTaskNumber', ''));

			$iUseEditor = (int) bab_rp('iUseEditor', 0);
			if(1 === $iUseEditor)
			{
				require_once $GLOBALS['babInstallPath'] . 'utilit/editorincl.php';
				$oEditor = new bab_contentEditor('bab_taskManagerDescription');
				$this->m_sDescription = $oEditor->getContent();
			}
			else 
			{
				$this->m_sDescription = $this->m_oTask->m_aTask['sDescription'];
			}
			
			$this->m_sShortDescription		= trim(bab_rp('sShortDescription', ''));
			$this->m_iIdCategory			= (int) bab_rp('iIdCategory', 0);
			$this->m_sCreated				= date("Y-m-d H:i:s");
			$this->m_sModified				= date("Y-m-d H:i:s");
			$this->m_iIdUserCreated			= $GLOBALS['BAB_SESS_USERID'];
			$this->m_iIdUserModified		= $GLOBALS['BAB_SESS_USERID'];
			$this->m_iClass					= (int) bab_rp('iClass', 0);
			$this->m_iParticipationStatus	= 0;
			$this->m_iIdCalEvent			= 0;
			$this->m_sHashCalEvent			= '';
			
			$iDurationType 					= (int) bab_rp('oDurationType', BAB_TM_DATE);
			
			$this->m_iDuration				= (BAB_TM_DATE != $iDurationType) ? (int) bab_rp('sDuration', 0) : 0;
			$this->m_iMajorVersion			= (int) bab_rp('iMajorVersion', 1);
			$this->m_iMinorVersion			= (int) bab_rp('iMinorVersion', 0);
			$this->m_sColor					= '';
			$this->m_iPosition				= (0 != $this->m_iIdTask) ? $this->m_oTask->m_aTask['iPosition'] : $this->m_oTask->m_iNextPosition;
			$this->m_iCompletion			= (int) bab_rp('oCompletion', 0);
			
			$this->m_iIsNotified			= BAB_TM_NO;
			$this->m_iAnswer				= (int) bab_rp('oAnswerEnable', -1);
			
			$this->m_iIsLinked				= (isset($_POST['oLinkedTask'])) ? BAB_TM_YES : BAB_TM_NO;
			$this->m_iLinkType 				= -1;
			$this->m_iIdPredecessor 		= (int) bab_rp('iPredecessor', -1);
			$aTask = null;
			if(BAB_TM_YES === $this->m_iIsLinked && -1 != $this->m_iIdPredecessor && bab_getTask($this->m_iIdPredecessor, $aTask))
			{
				if( isset($_POST['oLinkType']) && isset($_POST['oLinkType'][$this->m_iIdPredecessor]) )
				{
					$this->m_iLinkType = (int) $_POST['oLinkType'][$this->m_iIdPredecessor];
//					$this->m_iIsLinked = (-1 != $this->m_iLinkType) ? BAB_TM_YES : BAB_TM_NO;
				}
			}
			
			
			
			$this->m_sPlannedStartDate		= '';
			$this->m_sPlannedEndDate		= '';
			
			$this->m_sStartDate				= trim(bab_rp('sPlannedStartDate', ''));
			$this->frenchDateToIso($this->m_sStartDate);
			$this->m_sStartDate				.= (0 != strlen($this->m_sStartDate)) ? ' 00:00:00' : '';

			$this->m_sEndDate 				= trim(bab_rp('sPlannedEndDate', ''));
			$this->frenchDateToIso($this->m_sEndDate);
			$this->m_sEndDate 				.= (0 != strlen($this->m_sEndDate)) ? ' 23:59:59' : '';


			//Si il y a un predecesseur
			if(!is_null($aTask))
			{
				if(BAB_TM_START_TO_START == $this->m_iLinkType)
				{
					$this->m_sStartDate = $aTask['sStartDate'];
				}
				else if(BAB_TM_END_TO_START == $this->m_iLinkType)
				{
					if(0 != $aTask['iDuration'])
					{
						$oStartDate = BAB_DateTime::fromIsoDateTime($aTask['sStartDate']);
						$oStartDate->add($aTask['iDuration']);
						$this->m_sStartDate = date('Y-m-d H:i:s', $oStartDate->getTimeStamp());
					}
					else 
					{
						$oStartDate = BAB_DateTime::fromIsoDateTime($aTask['sEndDate']);
//						$oStartDate->init($oStartDate->_iYear, $oStartDate->_iMonth, $oStartDate->_iDay, 0, 0, 0);
						$this->m_sStartDate = date('Y-m-d H:i:s', $oStartDate->getTimeStamp());
					}
				}
				else 
				{
					bab_debug(__CLASS__ . ' ' . __FUNCTION__ . ': LinkType error');
				}
			}

			if(BAB_TM_DURATION === $iDurationType && 0 == strlen(trim(bab_rp('sPlannedEndDate', ''))))
			{
				$this->computeEndDate();
			}

/*			
			//Si c'est par durée et qu'il n'y a pas de date butoir de fin
			if($this->m_iDuration > 0 && 0 == strlen(trim(bab_rp('sPlannedEndDate', ''))))
			{
				$oEndDate = BAB_DateTime::fromIsoDateTime($this->m_sStartDate);
				$oEndDate->add(($this->m_iDuration - 1));
				$oEndDate->init($oEndDate->_iYear, $oEndDate->_iMonth, $oEndDate->_iDay, 23, 59, 59);
				$this->m_sEndDate = date('Y-m-d H:i:s', $oEndDate->getTimeStamp());
			}
//*/
			
			//bab_debug(__FUNCTION__ . ' sStart ==> ' . $this->m_sStartDate . ' sEnd ==> ' . $this->m_sEndDate);
			
			$this->m_iIdTaskResponsible = (int) bab_rp('iIdTaskResponsible', -1);

			bab_getAvailableTaskResponsibles($this->m_iIdProject, $this->m_aAvailableResponsibles);
//			bab_getLinkedTasks($this->m_iIdTask, $this->m_aLinkedTasks);
			bab_getTaskResponsibles($this->m_iIdTask, $this->m_aTaskResponsibles);
			bab_getDependingTasks($this->m_iIdTask, $this->m_aDependingTasks);
			
			if($this->m_oTask->m_isPersonnal)
			{
				$this->m_aCfg =& $this->m_oTask->getConfiguration();
			}
			else
			{
				$this->m_aCfg =& $oTmCtx->getConfiguration();
			}
		}
		
		function frenchDateToIso(&$sDate)
		{
			$aDate = explode('-', $sDate);
			if(count($aDate) == 3)
			{
				$iYear = 2;
				$iMonth = 1;
				$iDay = 0;
				
				if(strlen($aDate[0]) != 4)
				{
					$sDate = $aDate[$iYear] . '-' . $aDate[$iMonth] . '-' . $aDate[$iDay];
				}
			}
		}
		
		function computeEndDate()
		{
			include_once $GLOBALS['babInstallPath'] . 'utilit/nwdaysincl.php';
			include_once $GLOBALS['babInstallPath'] . 'utilit/calapi.php';
			
			$sWorkingDays = '';
			bab_calGetWorkingDays($GLOBALS['BAB_SESS_USERID'], $sWorkingDays);
			$aWorkingDays = array_flip(explode(',', $sWorkingDays));
			
			$iDuration = $this->m_iDuration;
			$oStartDate = BAB_DateTime::fromIsoDateTime($this->m_sStartDate);
			$oEndDate = BAB_DateTime::fromIsoDateTime($this->m_sStartDate);
			
			$iWorkingDaysCount = count($aWorkingDays);
			
			do
			{
				$aNWD = bab_getNonWorkingDaysBetween($oEndDate->getTimeStamp(), $oEndDate->getTimeStamp());
				if(isset($aWorkingDays[$oEndDate->getDayOfWeek()]) && 0 == count($aNWD))
				{
					$iDuration--;	
				}
				$oEndDate->add(1);
			}
			while(0 != $iDuration && $iWorkingDaysCount > 0);
			
			$sStartDate = $oStartDate->getIsoDateTime();
			
			$oEndDate->add(-1);
			$oEndDate->init($oEndDate->_iYear, $oEndDate->_iMonth, $oEndDate->_iDay, 23, 59, 59);
			$this->m_sEndDate = $oEndDate->getIsoDateTime();
			//bab_debug(__FUNCTION__ . ' sStart ==> ' . $this->m_sStartDate . ' sEnd ==> ' . $this->m_sEndDate);
		}

		function isTaskNumberValid()
		{
			if(strlen($this->m_sTaskNumber) > 0)
			{
				if(!is_null($this->m_aCfg))
				{
					if(BAB_TM_MANUAL == $this->m_aCfg['tasksNumerotation'])
					{
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
							bab_getNextTaskNumber($this->m_iIdProject, $this->m_aCfg['tasksNumerotation'], $sTaskNumber);
							return($sTaskNumber === $this->m_sTaskNumber);
						}
					}
				}
				else
				{
					bab_debug(__FUNCTION__ . ': cannot get the configuration');
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
			if('0000-00-00 00:00:00' !== $sDate && '0000-00-00 23:59:59' !== $sDate && 0 != trim(strlen($sDate)))
			{
				$oDate = BAB_DateTime::fromIsoDateTime($sDate);
				return BAB_DateTime::isValidDate($oDate->_iDay, $oDate->_iMonth, $oDate->_iYear);
			}
			return false;
		}

		function getProjectSpaceName()
		{
			$sProjectSpaceName = '???';
			if(bab_getProjectSpace($this->m_iIdProjectSpace, $aProjectSpace))
			{
				$sProjectSpaceName = $aProjectSpace['name'];
			}
			return $sProjectSpaceName;
		}

		function getProjectName()
		{
			$sProjectName = '???';
			if(bab_getProject($this->m_iIdProject, $aProject))
			{
				$sProjectName = $aProject['name'];
			}
			return $sProjectName;	
		}

		function noticeCreateSuccess()
		{
			$iIdEvent = BAB_TM_EV_TASK_CREATED;
			
			$g_aEmailMsg =& $GLOBALS['g_aEmailMsg'];
			$sBody = $g_aEmailMsg[$iIdEvent]['body'];
			$sSubject = $g_aEmailMsg[$iIdEvent]['subject'];
			
			$sProjectSpaceName = $this->getProjectSpaceName();
			$sProjectName = $this->getProjectName();
			$sBody = sprintf($sBody, ((strlen(trim($this->m_sShortDescription)) > 0) ? $this->m_sShortDescription : $this->m_sTaskNumber), $sProjectName, $sProjectSpaceName);
			sendNotice($this->m_iIdProjectSpace, $this->m_iIdProject, $this->m_iIdTask, 
				$iIdEvent, $sSubject, $sBody);
		}
		
		function noticeNewTaskResponsible($iIdUser)
		{
			$iIdEvent = BAB_TM_EV_NEW_TASK_RESPONSIBLE;
			$g_aEmailMsg =& $GLOBALS['g_aEmailMsg'];
			$sSubject = $g_aEmailMsg[$iIdEvent]['subject'];
			$sBody = $g_aEmailMsg[$iIdEvent]['body'];
			
			$sProjectSpaceName = $this->getProjectSpaceName();
			$sProjectName = $this->getProjectName();
			$sBody = sprintf($sBody, ((strlen(trim($this->m_sShortDescription)) > 0) ? $this->m_sShortDescription : $this->m_sTaskNumber), $sProjectName, $sProjectSpaceName);
			
			if(is_null($this->m_oSendMail))
			{
				$this->m_oSendMail = new BAB_TM_SendEmail();
			}
			$this->m_oSendMail->send_notification(bab_getUserEmail($iIdUser), $sSubject, $sBody);
		}
		
		function noticeNotAnyMoreTaskResponsible($iIdUser)
		{
			$iIdEvent = BAB_TM_EV_NO_MORE_TASK_RESPONSIBLE;
			$g_aEmailMsg =& $GLOBALS['g_aEmailMsg'];
			$sSubject = $g_aEmailMsg[$iIdEvent]['subject'];
			$sBody = $g_aEmailMsg[$iIdEvent]['body'];
			
			$sProjectSpaceName = $this->getProjectSpaceName();
			$sProjectName = $this->getProjectName();
			$sBody = sprintf($sBody, ((strlen(trim($this->m_sShortDescription)) > 0) ? $this->m_sShortDescription : $this->m_sTaskNumber), $sProjectName, $sProjectSpaceName);
			if(is_null($this->m_oSendMail))
			{
				$this->m_oSendMail = new BAB_TM_SendEmail();
			}
			$this->m_oSendMail->send_notification(bab_getUserEmail($iIdUser), $sSubject, $sBody);
		}
		
		function noticeTaskResponsibleProposed($iIdUser)
		{
			$iIdEvent = BAB_TM_EV_TASK_RESPONSIBLE_PROPOSED;
			$g_aEmailMsg =& $GLOBALS['g_aEmailMsg'];
			$sSubject = $g_aEmailMsg[$iIdEvent]['subject'];
			$sBody = $g_aEmailMsg[$iIdEvent]['body'];
			
			$sProjectSpaceName = $this->getProjectSpaceName();
			$sProjectName = $this->getProjectName();
			$sBody = sprintf($sBody, ((strlen(trim($this->m_sShortDescription)) > 0) ? $this->m_sShortDescription : $this->m_sTaskNumber), $sProjectName, $sProjectSpaceName);
			
			if(is_null($this->m_oSendMail))
			{
				$this->m_oSendMail = new BAB_TM_SendEmail();
			}
			$this->m_oSendMail->send_notification(bab_getUserEmail($iIdUser), $sSubject, $sBody);
		}

		function noticeTaskUpdatedBy($iIdEvent)
		{
			$g_aEmailMsg =& $GLOBALS['g_aEmailMsg'];
			$sSubject = $g_aEmailMsg[$iIdEvent]['subject'];
			$sBody = $g_aEmailMsg[$iIdEvent]['body'];
			
			$sProjectSpaceName = $this->getProjectSpaceName();
			$sProjectName = $this->getProjectName();
			$sBody = sprintf($sBody, ((strlen(trim($this->m_sShortDescription)) > 0) ? $this->m_sShortDescription : $this->m_sTaskNumber), $sProjectName, $sProjectSpaceName, 
				bab_getUserName($GLOBALS['BAB_SESS_USERID']));
			sendNotice($this->m_iIdProjectSpace, $this->m_iIdProject, $this->m_iIdTask, 
				$iIdEvent, $sSubject, $sBody);
		}
	}
	
	class BAB_TM_MgrTaskValidatorBase extends BAB_TM_TaskValidatorBase
	{
		function BAB_TM_MgrTaskValidatorBase()
		{
			parent::BAB_TM_TaskValidatorBase();
		}
		
		function init()
		{
			parent::init();
		}

		function isTaskValid()
		{
			//bab_debug(__CLASS__ . ' ' . __FUNCTION__);
		
			if($this->m_oTask->m_bIsEnded)
			{
				bab_debug(__FUNCTION__ . ' the task is ended');
				$GLOBALS['babBody']->msgerror = bab_translate("The task is ended");
				return false;
			}
			
//			if(false == $this->m_oTask->m_bIsStarted)
			{
				if($this->isTaskNumberValid())
				{
					$success = true;

					//Si la tache est liée
					if(BAB_TM_YES == $this->m_iIsLinked)
					{
						//bab_debug('class: ' . __CLASS__ . ' fn: ' . __FUNCTION__ . ' the task is linked');
						
						$success = bab_getTask($this->m_iIdPredecessor, $aTask);
						if($success)
						{
							if(-1 == $this->m_iLinkType)
							{
								bab_debug(__FUNCTION__ . ' invalid LinkType');
								$GLOBALS['babBody']->msgerror = bab_translate("Invalid LinkType");
								return false;
							}
/*							
							$sStartDate = (BAB_TM_END_TO_START == $this->m_iLinkType) ? $aTask['sEndDate'] : $aTask['sStartDate'];
							if(!preg_match("/(^[0-9]{4}-[0-9]{2}-[0-9]{2}).*$/", $sStartDate, $aExplodedDate))
							{
								bab_debug(__FUNCTION__ . ' cannot get the start date');
								$GLOBALS['babBody']->msgerror = bab_translate("Cannot get the start date");
								return false;
							}
							
							$this->m_sStartDate = $aExplodedDate[1] . ' 00:00:00';
//*/					
						}
						else 
						{
							bab_debug('Error can get predecessor information');
							$GLOBALS['babBody']->msgerror = bab_translate("Error can get predecessor information");
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
							$GLOBALS['babBody']->msgerror = bab_translate("Unknown duration type");
						}
					}
				}
				else 
				{
					bab_debug(__FUNCTION__ . ' sTaskNumber is invalid');
					$GLOBALS['babBody']->msgerror = bab_translate("The task number is invalid");
				}
			}
/*			else
			{
				bab_debug('Babe !!!');
				return($this->isResponsibleValid());
			} //*/
			return false;
		}
		
		function isTaskValidByDuration()
		{
			if($this->isTaskValidByDate())
			{
				//si date butoir de fin
				if(0 != strlen(trim(bab_rp('sPlannedEndDate', ''))))
				{
					$oLimitEndDate = BAB_DateTime::fromIsoDateTime($this->m_sEndDate);
					$oEndDate = BAB_DateTime::fromIsoDateTime($this->m_sStartDate);
					$oEndDate->add(($this->m_iDuration -1));
					$oEndDate->init($oEndDate->_iYear, $oEndDate->_iMonth, $oEndDate->_iDay, 23, 59, 59);

					/*
					bab_debug(__FUNCTION__ . 
						' sEndDate ==> ' . date('Y-m-d H:i:s', $oEndDate->getTimeStamp()) . 
						' sLimitEndDate ==> ' .date('Y-m-d H:i:s', $oLimitEndDate->getTimeStamp()));
					//*/
						
					$iIsEqual	= 0;
					$iIsBefore	= -1;
					$iIsAfter	= 1;
					
					if($iIsAfter == BAB_DateTime::compare($oEndDate, $oLimitEndDate))
					{
						$GLOBALS['babBody']->msgerror = bab_translate("The end date is greater than the limit date");
						bab_debug(__FUNCTION__ . " The end date is greater than the limit date");
						return false;
					}
				}
				return true;
			}
			return false;
		}

		function isTaskValidByDate()
		{
			if($this->isDateValid($this->m_sStartDate) && $this->isDateValid($this->m_sEndDate))
			{
				$iIsEqual	= 0;
				$iIsBefore	= -1;
				$iIsAfter	= 1;
				
				//bab_debug('sStart ==> ' . $this->m_sStartDate . ' sEnd ==> ' . $this->m_sEndDate);
				$oStart = BAB_DateTime::fromIsoDateTime($this->m_sStartDate);
				$oEnd = BAB_DateTime::fromIsoDateTime($this->m_sEndDate);
				
				if($iIsBefore == BAB_DateTime::compare($oStart, $oEnd))
				{
					if($this->m_iUserProfil == BAB_TM_PROJECT_MANAGER && !$this->isResponsibleValid())
					{
						bab_debug(__FUNCTION__ . ': Invalid iIdTaskResponsible');
						$GLOBALS['babBody']->msgerror = bab_translate("The choosen task responsible is invalid");
					}
					else 
					{
						return true;
					}
				}
				else 
				{
					bab_debug(__FUNCTION__ . ' sEndDate is lower than sStartDate');
					$GLOBALS['babBody']->msgerror = bab_translate("The end date is lower than the start date");
				}
			}
			else 
			{
				bab_debug(__FUNCTION__ . ' invalid Date');
				$GLOBALS['babBody']->msgerror = bab_translate("Invalid date");
			}
			return false;
		}

		function isCheckPointValid()
		{
			if($this->isTaskNumberValid())
			{
				if($this->isDateValid($this->m_sEndDate))
				{
					return true;
				}
			}
			return false;
		}
		
		function isToDoValid()
		{
			if($this->isTaskNumberValid())
			{
				if($this->isDateValid($this->m_sEndDate))
				{
					return true;
				}
			}
			return false;
		}
		
		function save()
		{
			if($this->m_iUserProfil != BAB_TM_UNDEFINED)
			{
				switch($this->m_iClass)
				{
					case BAB_TM_TASK:
						return $this->saveTask();
					case BAB_TM_CHECKPOINT:
						return $this->saveCheckPoint();
					case BAB_TM_TODO:
						return $this->saveToDo();
				}
			}
			return false;
		}
		
		function saveTask()
		{
			return false;
		}
		
		function saveCheckPoint()
		{
			return false;
		}
		
		function saveToDo()
		{
			return false;
		}
		
		function getIsoDatesFromEndDate(&$sStartDate, &$sEndDate)
		{
			$sEndDate = trim($this->m_sEndDate);
			$oStartDate = BAB_DateTime::fromIsoDateTime($sEndDate);
			$oStartDate->init($oStartDate->_iYear, $oStartDate->_iMonth, $oStartDate->_iDay, 00, 00, 00);
			$sStartDate = date('Y-m-d H:i:s', $oStartDate->getTimeStamp());
		}
	}
	
	class BAB_TM_MgrTaskCreatorValidator extends BAB_TM_MgrTaskValidatorBase
	{
		function BAB_TM_MgrTaskCreatorValidator()
		{
			parent::BAB_TM_MgrTaskValidatorBase();
		}
		
		function init()
		{
			parent::init();
		}

		function saveTask()
		{
			if($this->isTaskValid())
			{
				$iProposable = BAB_TM_NO;
				if((int) $GLOBALS['BAB_SESS_USERID'] !== (int) $this->m_iIdTaskResponsible)
				{
					$iProposable = (int) bab_rp('oProposable', BAB_TM_NO);
				}
				
				$iParticipationStatus = (int) (BAB_TM_NO == $iProposable) ? BAB_TM_ACCEPTED : BAB_TM_TENTATIVE;
				
				$this->m_iIsLinked = (false === $this->m_oTask->m_bIsFirstTask && BAB_TM_YES === $this->m_iIsLinked) ? 
					BAB_TM_YES : BAB_TM_NO;
				
				$sStartDate = trim($this->m_sStartDate);
				$sEndDate = trim($this->m_sEndDate);
				
				$aTask =& $this->m_oTask->m_aTask;
				
				$aTask['iIdProject']			= $this->m_iIdProject;
				$aTask['sTaskNumber']			= $this->m_sTaskNumber;
				$aTask['sDescription']			= $this->m_sDescription;
				$aTask['sShortDescription']		= substr($this->m_sShortDescription, 0, 255);
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
				$aTask['iCompletion']			= 0;
				$aTask['sStartDate']			= $sStartDate;
				$aTask['sEndDate'] 				= $sEndDate;
				$aTask['iIsNotified']			= BAB_TM_YES;

				//bab_debug($aTask);
//*				
				$iIdTask = bab_createTask($aTask);
				if(false !== $iIdTask)
				{
					$this->m_iIdTask = $iIdTask;
					if(BAB_TM_PROJECT_MANAGER == $this->m_iUserProfil)
					{
						bab_deleteTaskResponsibles($iIdTask);
						$aTaskResponsibles = array($this->m_iIdTaskResponsible);
						
						bab_setTaskResponsibles($iIdTask, $aTaskResponsibles);
					}
					
					$iIdOwner = (BAB_TM_PROJECT_MANAGER == $this->m_iUserProfil) ? $this->m_iIdTaskResponsible : $GLOBALS['BAB_SESS_USERID'];
					
					$iIsPersonnal = $this->m_oTask->m_isPersonnal ? BAB_TM_YES : BAB_TM_NO;
					bab_createTaskInfo($iIdTask, $iIdOwner, $iIsPersonnal);

					if(BAB_TM_YES == $this->m_iIsLinked && (BAB_TM_PROJECT_MANAGER == $this->m_iUserProfil || BAB_TM_PERSONNAL_TASK_OWNER == $this->m_iUserProfil))
					{
						bab_deleteTaskLinks($iIdTask);
						$aPredecessors = array(
							array('iIdPredecessorTask' => $this->m_iIdPredecessor, 'iLinkType' => $this->m_iLinkType)
						);
							
						bab_setTaskLinks($iIdTask, $aPredecessors);
					}
					
					require_once $GLOBALS['babInstallPath'] . 'tmSendMail.php';

					$this->noticeCreateSuccess();
					
					$iIsNotified = BAB_TM_NO;
					if(BAB_TM_YES == $iProposable)
					{
						$iIsNotified = BAB_TM_YES;
						$this->noticeTaskResponsibleProposed($this->m_iIdTaskResponsible);
					}
					else
					{
						$iIsNotified = BAB_TM_YES;
						$this->noticeNewTaskResponsible($this->m_iIdTaskResponsible);
					}
				}

				return (false !== $iIdTask);
//*/
			}
			return false;
		}

		function saveCheckPoint()
		{
			if($this->isCheckPointValid())
			{
				$aTask =& $this->m_oTask->m_aTask;
				
				$aTask['iIdProject']			= $this->m_iIdProject;
				$aTask['sTaskNumber']			= $this->m_sTaskNumber;
				$aTask['sDescription']			= $this->m_sDescription;
				$aTask['sShortDescription']		= substr($this->m_sShortDescription, 0, 255);
				$aTask['iIdCategory']			= $this->m_iIdCategory;
				$aTask['sCreated']				= $this->m_sCreated;
				$aTask['iIdUserCreated']		= $this->m_iIdUserCreated;
				$aTask['sModified']				= '';
				$aTask['iIdUserModified']		= 0;
				$aTask['iClass']				= $this->m_iClass;
				$aTask['iParticipationStatus']	= 0;
				$aTask['iIsLinked']				= BAB_TM_NO;
				$aTask['iIdCalEvent']			= 0;
				$aTask['sHashCalEvent']			= '';
				$aTask['iDuration']				= 0;
				$aTask['iMajorVersion']			= $this->m_iMajorVersion;
				$aTask['iMinorVersion']			= $this->m_iMinorVersion;
				$aTask['sColor']				= $this->m_sColor;
				$aTask['iPosition']				= $this->m_iPosition;
				$aTask['iCompletion']			= 0;
				
				$this->getIsoDatesFromEndDate($sStartDate, $sEndDate);
				
				$aTask['sStartDate']			= $sStartDate;
				$aTask['sEndDate'] 				= $sEndDate;
				$aTask['iIsNotified']			= BAB_TM_NO;
//*				
				$iIdTask = bab_createTask($aTask);
				if(false !== $iIdTask)
				{
					$this->m_iIdTask = $iIdTask;
					
					$iIdOwner = $GLOBALS['BAB_SESS_USERID'];
					$iIsPersonnal = $this->m_oTask->m_isPersonnal ? BAB_TM_YES : BAB_TM_NO;
					bab_createTaskInfo($iIdTask, $iIdOwner, $iIsPersonnal);

					require_once $GLOBALS['babInstallPath'] . 'tmSendMail.php';
					$this->noticeCreateSuccess();
				}
				return (false !== $iIdTask);
//*/
			}
			return false;
		}

		function saveToDo()
		{
			if($this->isToDoValid())
			{
				$aTask =& $this->m_oTask->m_aTask;
				
				$aTask['iIdProject']			= $this->m_iIdProject;
				$aTask['sTaskNumber']			= $this->m_sTaskNumber;
				$aTask['sDescription']			= $this->m_sDescription;
				$aTask['sShortDescription']		= substr($this->m_sShortDescription, 0, 255);
				$aTask['iIdCategory']			= $this->m_iIdCategory;
				$aTask['sCreated']				= $this->m_sCreated;
				$aTask['iIdUserCreated']		= $this->m_iIdUserCreated;
				$aTask['sModified']				= '';
				$aTask['iIdUserModified']		= 0;
				$aTask['iClass']				= $this->m_iClass;
				$aTask['iParticipationStatus']	= 0;
				$aTask['iIsLinked']				= BAB_TM_NO;
				$aTask['iIdCalEvent']			= 0;
				$aTask['sHashCalEvent']			= '';
				$aTask['iDuration']				= 0;
				$aTask['iMajorVersion']			= $this->m_iMajorVersion;
				$aTask['iMinorVersion']			= $this->m_iMinorVersion;
				$aTask['sColor']				= $this->m_sColor;
				$aTask['iPosition']				= $this->m_iPosition;
				$aTask['iCompletion']			= 0;
				
				$this->getIsoDatesFromEndDate($sStartDate, $sEndDate);
				
				$aTask['sStartDate']			= $sStartDate;
				$aTask['sEndDate'] 				= $sEndDate;
				$aTask['iIsNotified']			= BAB_TM_NO;
//*				
				$iIdTask = bab_createTask($aTask);
				if(false !== $iIdTask)
				{
					$this->m_iIdTask = $iIdTask;
					
					$iIdOwner = $GLOBALS['BAB_SESS_USERID'];
					$iIsPersonnal = $this->m_oTask->m_isPersonnal ? BAB_TM_YES : BAB_TM_NO;
					bab_createTaskInfo($iIdTask, $iIdOwner, $iIsPersonnal);
					
					require_once $GLOBALS['babInstallPath'] . 'tmSendMail.php';
					$this->noticeCreateSuccess();
				}
				return (false !== $iIdTask);
//*/
			}
			return false;
		}
	}
	
	class BAB_TM_TaskUpdaterValidator extends BAB_TM_MgrTaskValidatorBase
	{
		var $m_bIsClassChanged = false;
		
		function BAB_TM_TaskUpdaterValidator()
		{
			parent::BAB_TM_MgrTaskValidatorBase();
		}
		
		function init()
		{
			parent::init();
			
			$aTask =& $this->m_oTask->m_aTask;
			
			//Si on a changé de type
			if($this->m_iClass != $aTask['iClass'])
			{
				//si c'était une tâche
				if(BAB_TM_TASK == $aTask['iClass']) 
				{
					if( BAB_TM_PERSONNAL_TASK_OWNER == $this->m_iUserProfil || 
						BAB_TM_PROJECT_MANAGER == $this->m_iUserProfil    		)
					{
						$aTask['iClass'] = $this->m_iClass;
						$this->m_bIsClassChanged = true;
					}
				}
				else
				{
					$aTask['iClass'] = $this->m_iClass;
					$this->m_bIsClassChanged = true;
				}
			}
		}
		
		function saveTask()
		{
			if($this->isTaskValid())
			{
				$iProposable = BAB_TM_NO;
				if((int) $GLOBALS['BAB_SESS_USERID'] !== (int) $this->m_iIdTaskResponsible)
				{
					$iProposable = (int) bab_rp('oProposable', BAB_TM_NO);
				}

				$this->m_iIsLinked = (false === $this->m_oTask->m_bIsFirstTask && BAB_TM_YES === $this->m_iIsLinked) ? 
					BAB_TM_YES : BAB_TM_NO;

				$aTask =& $this->m_oTask->m_aTask;

//				$aTask['iIdProject']			= $this->m_iIdProject;
				$aTask['sTaskNumber']			= $this->m_sTaskNumber;
				$aTask['sDescription']			= (BAB_TM_TASK_RESPONSIBLE !== $this->m_iUserProfil) ? $this->m_sDescription : $aTask['sShortDescription'];
				$aTask['sShortDescription']		= (BAB_TM_TASK_RESPONSIBLE !== $this->m_iUserProfil) ? substr($this->m_sShortDescription, 0, 255) : $aTask['sShortDescription'];
				$aTask['iIdCategory']			= $this->m_iIdCategory;
//				$aTask['sCreated']				= $this->m_sCreated;
//				$aTask['iIdUserCreated']		= $this->m_iIdUserCreated;
				$aTask['sModified']				= $this->m_sModified;
				$aTask['iIdUserModified']		= $this->m_iIdUserModified;
//				$aTask['iClass']				= $this->m_iClass;
//				$aTask['iParticipationStatus']	= $iParticipationStatus;
				$aTask['iIsLinked']				= $this->m_iIsLinked;
//				$aTask['iIdCalEvent']			= $this->m_iIdCalEvent;
//				$aTask['sHashCalEvent']			= $this->m_sHashCalEvent;
				$aTask['iDuration']				= $this->m_iDuration;
				$aTask['iMajorVersion']			= $this->m_iMajorVersion;
				$aTask['iMinorVersion']			= $this->m_iMinorVersion;
//				$aTask['sColor']				= $this->m_sColor;
//				$aTask['iPosition']				= $this->m_iPosition;
				$aTask['iCompletion']			= $this->m_iCompletion;
				$aTask['sStartDate']			= $this->m_sStartDate;
				$aTask['sEndDate'] 				= $this->m_sEndDate;
				$aTask['iIsNotified']			= BAB_TM_YES;

				if(-1 != $this->m_iAnswer)
				{
					$aTask['iParticipationStatus'] = (BAB_TM_YES == $this->m_iAnswer) ? BAB_TM_ACCEPTED : BAB_TM_REFUSED;
				}
				
				if((int) $this->m_iCompletion >= 100 || BAB_TM_ENDED === (int) $aTask['iParticipationStatus'])
				{
					$aTask['sEndDate'] = date("Y-m-d") . ' 23:59:59';
					$aTask['iParticipationStatus'] = BAB_TM_ENDED;
					/*bab_startDependingTask($this->m_iIdProjectSpace, $this->m_iIdProject, 
						$this->m_iIdTask, BAB_TM_END_TO_START);//*/
				}
				
				if(bab_updateTask($this->m_iIdTask, $aTask))
				{
					require_once $GLOBALS['babInstallPath'] . 'tmSendMail.php';

					$this->processTaskResponsible($iProposable);
					$iIsNotified = BAB_TM_NO;
					
					$this->processTaskLink();
					
					//bab_debug('iUserProfil ==> ' . $this->m_iUserProfil);	
					if(BAB_TM_PROJECT_MANAGER == $this->m_iUserProfil)
					{
						$this->noticeTaskUpdatedBy(BAB_TM_EV_TASK_UPDATED_BY_MGR);
					}
					else if(BAB_TM_TASK_RESPONSIBLE == $this->m_iUserProfil)
					{
						$this->noticeTaskUpdatedBy(BAB_TM_EV_TASK_UPDATED_BY_RESP);
					}
					
					$this->processSpecificFieldIntance();
					
					$this->updateDependingTask($this->m_iIdTask, $aTask);
				}
				return true;
			}
			return false;			
		}
		
		function updateDependingTask($iIdTask, $aTask)
		{
bab_debug('A terminer, PB avec la date butoir de fin');
			return;	
			$aDependingTasks = array();
			
			bab_getDependingTasks($iIdTask, $aDependingTasks);
			
			if(count($aDependingTasks) > 0)
			{
				foreach($aDependingTasks as $key => $value)
				{
					//$value[iIdTask]
					//$value[iIdResponsible]
					
					$aDependingTask = array();
					if(true === bab_getTask($value['iIdTask'], $aDependingTask))
					{
						if(BAB_TM_START_TO_START === (int) $value['iLinkType'])
						{
							$aDependingTask['startDate'] = $aTask['startDate'];
							bab_updateTask($aDependingTask['id'], $aDependingTask);
							$this->noticeTaskUpdatedBy(BAB_TM_EV_TASK_UPDATED_BY_MGR);
						}
						else if(BAB_TM_END_TO_START === (int) $value['iLinkType'])
						{
						}
					}
				}
			}
		}
		
		function processTaskResponsible($iProposable)
		{
			$bIsNewTaskResponsible = false;
			$iIdOldTaskResponsible = 0;
			
			if(false === $this->m_bIsClassChanged)
			{
				if(false !== ($aResponsible = each($this->m_aTaskResponsibles)))
				{
					reset($this->m_aTaskResponsibles);
					$bIsNewTaskResponsible = ($this->m_iIdTaskResponsible != (int) $aResponsible['value']['id']);
					$iIdOldTaskResponsible = (int) $aResponsible['value']['id'];
					
					/*
					bab_debug('bIsNewTaskResponsible ==> ' . (($bIsNewTaskResponsible) ? 'YES' : 'NO') . 
						' iIdOldTaskResponsible ==> ' . $iIdOldTaskResponsible . 
						' iIdTaskResponsible ==> ' . $this->m_iIdTaskResponsible);
					//*/
						
					if($bIsNewTaskResponsible)
					{
						if(0 != $iIdOldTaskResponsible)
						{
							$this->noticeNotAnyMoreTaskResponsible($iIdOldTaskResponsible);
						}
					}
				}
			}
			
			if(BAB_TM_PROJECT_MANAGER == $this->m_iUserProfil)
			{
				bab_deleteTaskResponsibles($this->m_iIdTask);
				$aTaskResponsibles = array($this->m_iIdTaskResponsible);
				bab_setTaskResponsibles($this->m_iIdTask, $aTaskResponsibles);
				
				$iIdOwner = (BAB_TM_PROJECT_MANAGER == $this->m_iUserProfil) ? $this->m_iIdTaskResponsible : $GLOBALS['BAB_SESS_USERID'];
				$iIsPersonnal = $this->m_oTask->m_isPersonnal ? BAB_TM_YES : BAB_TM_NO;
				bab_updateTaskInfo($this->m_iIdTask, $iIdOwner, $iIsPersonnal);
			}

			if(BAB_TM_YES == $iProposable)
			{
				$this->noticeTaskResponsibleProposed($this->m_iIdTaskResponsible);
			}
			else
			{
				$this->noticeNewTaskResponsible($this->m_iIdTaskResponsible);
			}
		}
		
		function processTaskLink()
		{
			bab_deleteTaskLinks($this->m_iIdTask);
				
			if(BAB_TM_YES == $this->m_iIsLinked && (BAB_TM_PROJECT_MANAGER == $this->m_iUserProfil || BAB_TM_PERSONNAL_TASK_OWNER == $this->m_iUserProfil))
			{
				$aPredecessors = array(
					array('iIdPredecessorTask' => $this->m_iIdPredecessor, 'iLinkType' => $this->m_iLinkType)
				);
					
				bab_setTaskLinks($this->m_iIdTask, $aPredecessors);
			}
		}

		
		function processSpecificFieldIntance()
		{
			$aDeletableSpfObjects = isset($_POST['aDeletableSpfObjects']) ? $_POST['aDeletableSpfObjects'] : array();
			$aSpFldInstanceValue = isset($_POST['aSpFldInstanceValue']) ? $_POST['aSpFldInstanceValue'] : array();
			
			foreach($aSpFldInstanceValue as $key => $value)
			{
				if(!isset($aDeletableSpfObjects[$key]))
				{
					bab_updateSpecificInstanceValue($key, trim($value));
				}
				else
				{
					bab_deleteSpecificFieldInstance($key);
				}
			}
		}

		
		function saveCheckPoint()
		{
			if($this->isCheckPointValid())
			{
				bab_debug(__CLASS__ . ' ' . __FUNCTION__ . ' is Valid');
//*				
				$aTask =& $this->m_oTask->m_aTask;
				
				$aTask['iIdProject']			= $this->m_iIdProject;
				$aTask['sTaskNumber']			= $this->m_sTaskNumber;
				$aTask['sDescription']			= $this->m_sDescription;
				$aTask['sShortDescription']		= substr($this->m_sShortDescription, 0, 255);
				$aTask['iIdCategory']			= $this->m_iIdCategory;
				$aTask['sModified']				= $this->m_sModified;
				$aTask['iIdUserModified']		= $this->m_iIdUserModified;
				//$aTask['iClass']				= $this->m_iClass;
				$aTask['iParticipationStatus']	= 0;
				$aTask['iIsLinked']				= BAB_TM_NO;
				$aTask['iIdCalEvent']			= 0;
				$aTask['sHashCalEvent']			= '';
				$aTask['iDuration']				= 0;
				$aTask['iMajorVersion']			= $this->m_iMajorVersion;
				$aTask['iMinorVersion']			= $this->m_iMinorVersion;
				$aTask['sColor']				= $this->m_sColor;
				$aTask['iPosition']				= $this->m_iPosition;
				$aTask['iCompletion']			= $this->m_iCompletion;
				
				$this->getIsoDatesFromEndDate($sStartDate, $sEndDate);
				
				$aTask['sStartDate']			= $sStartDate;
				$aTask['sEndDate'] 				= $sEndDate;
				$aTask['iIsNotified']			= BAB_TM_NO;
				
				$bSuccess = bab_updateTask($this->m_iIdTask, $aTask);
				if(true === $bSuccess && -1 !== $this->m_iIdTaskResponsible)
				{
					if(BAB_TM_PROJECT_MANAGER == $this->m_iUserProfil)
					{
						//Faire ce qui suit dans la fn de sauvegarde
						bab_deleteTaskResponsibles($this->m_iIdTask);
					}
				}
				return (false !== $bSuccess);
//*/
			}
			return false;
		}
		
		function saveToDo()
		{
			if($this->isToDoValid())
			{
				bab_debug(__CLASS__ . ' ' . __FUNCTION__ . ' is Valid');
				
				$aTask =& $this->m_oTask->m_aTask;
				
				$aTask['iIdProject']			= $this->m_iIdProject;
				$aTask['sTaskNumber']			= $this->m_sTaskNumber;
				$aTask['sDescription']			= $this->m_sDescription;
				$aTask['sShortDescription']		= substr($this->m_sShortDescription, 0, 255);
				$aTask['iIdCategory']			= $this->m_iIdCategory;
				$aTask['sModified']				= $this->m_sModified;
				$aTask['iIdUserModified']		= $this->m_iIdUserModified;
				//$aTask['iClass']				= $this->m_iClass;
				$aTask['iParticipationStatus']	= 0;
				$aTask['iIsLinked']				= BAB_TM_NO;
				$aTask['iIdCalEvent']			= 0;
				$aTask['sHashCalEvent']			= '';
				$aTask['iDuration']				= 0;
				$aTask['iMajorVersion']			= $this->m_iMajorVersion;
				$aTask['iMinorVersion']			= $this->m_iMinorVersion;
				$aTask['sColor']				= $this->m_sColor;
				$aTask['iPosition']				= $this->m_iPosition;
				$aTask['iCompletion']			= $this->m_iCompletion;
				
				$this->getIsoDatesFromEndDate($sStartDate, $sEndDate);
				
				$aTask['sStartDate']			= $sStartDate;
				$aTask['sEndDate'] 				= $sEndDate;
				$aTask['iIsNotified']			= BAB_TM_NO;
				
				$bSuccess = bab_updateTask($this->m_iIdTask, $aTask);
				if(true === $bSuccess && -1 !== $this->m_iIdTaskResponsible)
				{
					if(BAB_TM_PROJECT_MANAGER == $this->m_iUserProfil)
					{
						//Faire ce qui suit dans la fn de sauvegarde
						bab_deleteTaskResponsibles($this->m_iIdTask);
					}
				}
				return (false !== $bSuccess);
			}
			return false;
		}
	}
?>