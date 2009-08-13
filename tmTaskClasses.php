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
			$oTmCtx 					=& getTskMgrContext();
			$this->m_iIdProjectSpace	= $oTmCtx->getIdProjectSpace();
			$this->m_iIdProject			= $oTmCtx->getIdProject();
			$this->m_iIdTask 			= $oTmCtx->getIdTask();
			
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
				$this->m_bIsStarted		= (0 != $this->m_aTask['iCompletion'] && BAB_TM_ENDED != $this->m_aTask['iParticipationStatus']);
				$this->m_bIsEnded 		= (BAB_TM_ENDED == $this->m_aTask['iParticipationStatus']);
				$this->m_bIsFirstTask 	= ($this->m_aTask['iPosition'] == 1);
			}
			
			return $success;
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
					
		var $m_iIdSessUser;
		
		var $m_aDurations;
		var $m_aDurationUnit;
		var $m_aProposable;
		var $m_aCompletion;
		var $m_aClasses;
		var $m_aTaskPriority;
		
		var $m_iHour	= 0;
		var $m_iMinut	= 0;

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
			
			$this->m_aDurationUnit = array(
				array('iUnit' => BAB_TM_DAY, 'sName' => bab_translate("Jour(s)")),
				array('iUnit' => BAB_TM_HOUR, 'sName' => bab_translate("Heure(s)"))
			);
			
			$this->m_aProposable = array(
				array('iProposable' => BAB_TM_YES, 'sProposable' => bab_translate("Yes")),
				array('iProposable' => BAB_TM_NO, 'sProposable' => bab_translate("No"))
			);

			$this->m_aCompletion = array(0, 10, 20, 30, 40, 50, 60, 70, 80, 90, 100);

			$this->m_aTaskPriority = array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10);
			
			$oTmCtx 					=& getTskMgrContext();
			$this->m_iIdProjectSpace	= $oTmCtx->getIdProjectSpace();
			$this->m_iIdProject			= $oTmCtx->getIdProject();
			$this->m_iIdTask			= $oTmCtx->getIdTask();
			$this->m_iUserProfil		= $oTmCtx->getUserProfil();
			$this->m_iIdSessUser		= $GLOBALS['BAB_SESS_USERID'];
			
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
			$this->set_caption('sShortDescription', bab_translate("Name"));
			$this->set_caption('sPredecessorShortDescription', bab_translate("Name"));
//			$this->set_caption('sShortDescription', bab_translate("Short description"));
//			$this->set_caption('sPredecessorShortDescription', bab_translate("Short description"));
			$this->set_caption('sLinkedTask', bab_translate("Linked task"));
			$this->set_caption('sProposable', bab_translate("Proposed"));
			$this->set_caption('sDurationType', bab_translate("Duration type"));
			$this->set_caption('sDuration', bab_translate("Duration"));
			$this->set_caption('sStartDate', bab_translate("Real start date"));
			$this->set_caption('sEndDate', bab_translate("Real end date"));
			$this->set_caption('sPlannedStartDate', bab_translate("Planned start date"));
			$this->set_caption('sPlannedEndDate', bab_translate("Planned end date"));
			$this->set_caption('sDeadline', bab_translate("Deadline"));
			$this->set_caption('sCompletion', bab_translate("Completion"));
			$this->set_caption('sRelation', bab_translate("Relation"));
			$this->set_caption('sTaskResponsible', bab_translate("Task Responsible"));
			$this->set_caption('sTaskPriority', bab_translate("Task priority"));
			$this->set_caption('sPlannedTime', bab_translate("Planned time"));
			$this->set_caption('sTime', bab_translate("Real time"));
			$this->set_caption('sPlannedCost', bab_translate("Planned cost"));
			$this->set_caption('sCost', bab_translate("Real cost"));
			$this->set_caption('sCostTab', bab_translate("Cost"));
			$this->set_caption('sNone', bab_translate("None"));
			$this->set_caption('sField', bab_translate("Field"));
			$this->set_caption('sType', bab_translate("Type"));
			$this->set_caption('sValue', bab_translate("Value"));
			$this->set_caption('sAddSpf', bab_translate("Add a field"));
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
			$this->set_caption('sDateFormat', bab_translate("The date format is JJ-MM-AAAA"));
		}

		function initDatas()
		{
			$this->set_data('tg', bab_rp('tg', 'usrTskMgr'));
			$this->set_data('isLinkable', false);
			$this->set_data('isProposable', false);
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
			$this->set_data('sDisabledDurationUnit', '');
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

			$this->set_data('isTaskPriority', false);
			
			$this->set_data('isReadOnlyPlannedStartDate', false);
			$this->set_data('sReadOnlyPlannedStartDate', '');
			$this->set_data('sDisabledPlannedStartDateTime', '');
			$this->set_data('sPlannedStartDate', '');
			$this->set_data('iPlannedStartHour', 0);
			$this->set_data('iPlannedStartMinut', 0);
			$this->set_data('sSelectedPlannedStartHour', '');
			$this->set_data('sSelectedPlannedStartMinut', '');

			$this->set_data('isReadOnlyPlannedEndDate', false);
			$this->set_data('sReadOnlyPlannedEndDate', '');
			$this->set_data('sDisabledPlannedEndDateTime', '');
			$this->set_data('sPlannedEndDate', '');
			$this->set_data('iPlannedEndHour', 0);
			$this->set_data('iPlannedEndMinut', 0);
			$this->set_data('sSelectedPlannedEndHour', '');
			$this->set_data('sSelectedPlannedEndMinut', '');
			
			$this->set_data('isReadOnlyStartDate', false);
			$this->set_data('sReadOnlyStartDate', '');
			$this->set_data('sDisabledStartDateTime', '');
			$this->set_data('sStartDate', '');
			$this->set_data('iStartHour', 0);
			$this->set_data('iStartMinut', 0);
			$this->set_data('sSelectedStartHour', '');
			$this->set_data('sSelectedStartMinut', '');
			
			$this->set_data('isReadOnlyEndDate', false);
			$this->set_data('sReadOnlyEndDate', '');
			$this->set_data('sDisabledEndDateTime', '');
			$this->set_data('sEndDate', '');
			$this->set_data('iEndHour', 0);
			$this->set_data('iEndMinut', 0);
			$this->set_data('sSelectedEndHour', '');
			$this->set_data('sSelectedEndMinut', '');
			
			
			$this->set_data('sPredecessorNumber', '');
			$this->set_data('sStartToStart', '');
			$this->set_data('sEndToStart', '');
			$this->set_data('sTaskResponsibleName', '');
			$this->set_data('sTaskCommentaries', '');
			$this->set_data('sSelectedSpfField', '');
			$this->set_data('sSpFieldName', '');
			$this->set_data('sProjectSpace', '');
			$this->set_data('sProject', '');
			$this->set_data('sReadOnlyCost', '');
			$this->set_data('iPlannedTime', 0);
			$this->set_data('iTime', 0);
			$this->set_data('iPlannedCost', 0);
			$this->set_data('iCost', 0);
			$this->set_data('sSlectedTaskPriority', '');
			$this->set_data('iIdTaskPriority', 0);
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
		
		function getNextTaskPriority()
		{
			$aTaskPriority = each($this->m_aTaskPriority);
			if(false != $aTaskPriority)
			{
				$this->get_data('iSelectedTaskPriority', $iPriority);
				$this->set_data('sSlectedTaskPriority', ((int)$aTaskPriority['value'] == (int)$iPriority) ? 
					'selected="selected"' : '');
				$this->set_data('iIdTaskPriority', $aTaskPriority['value']);
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
		
		function getNextDurationUnit($sFieldName)
		{
			$aDurationUnit = each($this->m_aDurationUnit);
			if(false != $aDurationUnit)
			{
				$this->get_data($sFieldName, $oDurationUnit);
				$this->set_data('sSelectedDurationUnit', ($aDurationUnit['value']['iUnit'] == $oDurationUnit) ? 
					'selected="selected"' : '');
				
				$this->set_data('iDurationUnit', $aDurationUnit['value']['iUnit']);
				$this->set_data('sDurationUnitName', $aDurationUnit['value']['sName']);
				return true;
			}
			reset($this->m_aDurationUnit);
			return false;
		}

		function getNextTaskDurationUnit()
		{
			$sFieldName = 'oDurationUnit';
			return $this->getNextDurationUnit($sFieldName);
		}

		function getNextPlannedTimeDurationUnit()
		{
			$sFieldName = 'oPlannedTimeDurationUnit';
			return $this->getNextDurationUnit($sFieldName);
		}

		function getNextTimeDurationUnit()
		{
			$sFieldName = 'oTimeDurationUnit';
			return $this->getNextDurationUnit($sFieldName);
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

		function getNextPlannedStartHour()
		{
			$sFieldPart = 'PlannedStart';
			return $this->getNextHour($sFieldPart);
		}

		function getNextPlannedEndHour()
		{
			$sFieldPart = 'PlannedEnd';
			return $this->getNextHour($sFieldPart);
		}

		function getNextStartHour()
		{
			$sFieldPart = 'Start';
			return $this->getNextHour($sFieldPart);
		}

		function getNextEndHour()
		{
			$sFieldPart = 'End';
			return $this->getNextHour($sFieldPart);
		}
		
		function getNextHour($sFieldPart)
		{
			static $iHour = -1;
			
			if($iHour < 23)
			{
				$this->set_data('iHour', ++$iHour);
				$this->set_data('sHour', sprintf("%02d", $iHour));
				$this->get_data('i' . $sFieldPart . 'Hour', $iFieldValue);
				$this->set_data('sSelected' . $sFieldPart . 'Hour', ($iHour == $iFieldValue) ? 
					'selected="selected"' : '');
				return true;
			}
			else
			{
				$iHour = - 1; 
				return false;
			}
		}

		function getNextPlannedStartMinut()
		{
			$sFieldPart = 'PlannedStart';
			return $this->getNextMinut($sFieldPart);
		}

		function getNextPlannedEndMinut()
		{
			$sFieldPart = 'PlannedEnd';
			return $this->getNextMinut($sFieldPart);
		}

		function getNextStartMinut()
		{
			$sFieldPart = 'Start';
			return $this->getNextMinut($sFieldPart);
		}

		function getNextEndMinut()
		{
			$sFieldPart = 'End';
			return $this->getNextMinut($sFieldPart);
		}

		function getNextMinut($sFieldPart)
		{
			static $iMinut = -1;
			
			if($iMinut < 59)
			{
				$this->set_data('iMinut', ++$iMinut);
				$this->set_data('sMinut', sprintf("%02d", $iMinut));
				$this->get_data('i' . $sFieldPart . 'Minut', $iFieldValue);
				$this->set_data('sSelected' . $sFieldPart . 'Minut', ($iMinut == $iFieldValue) ? 
					'selected="selected"' : '');
				return true;
			}
			else
			{
				$iMinut = - 1; 
				return false;
			}
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
//		var $m_spfResult;
//		var $m_spfInstResult;

		var $aAdditionnalField = array();

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
//			$this->m_spfResult = false;
			$this->m_linkableTaskResult = false;
			$this->m_spfValueResult = false;

			$this->m_aAvailableTaskResponsible = array();
			$this->getTaskInfo();

			$this->m_bIsManager = ($this->m_iUserProfil == BAB_TM_PERSONNAL_TASK_OWNER || 
				$this->m_iUserProfil == BAB_TM_PROJECT_MANAGER);
			
			$this->m_aRelation = array(BAB_TM_NONE => bab_translate("None"), BAB_TM_END_TO_START => bab_translate("End to start"), BAB_TM_START_TO_START => bab_translate("Start to start"));

			$bIsModifiable = ($this->m_bIsManager ||  (is_array($this->m_aCfg) && BAB_TM_YES === (int) $this->m_aCfg['tskUpdateByMgr']));
			$this->set_data('isModifiable', $bIsModifiable);
			
			$this->set_data('isDeletable', $this->m_bIsManager);
			$this->set_data('isTaskPriority', $this->m_bIsManager);
			
			$this->m_iUseEditor = (int) bab_rp('iUseEditor', 0);
			$isProject = (int) bab_rp('isProject', 0);
			$this->m_bDisplayEditorLink = (0 === $this->m_iUseEditor && $this->m_bIsManager);
			
			$this->m_sEditTaskDescriptionUrl = $GLOBALS['babUrlScript'] . '?tg=' . urlencode('usrTskMgr') . 
				'&idx=' . urlencode(BAB_TM_IDX_DISPLAY_TASK_FORM) . '&iIdProjectSpace=' . 
				urlencode($this->m_iIdProjectSpace) . '&iIdProject=' . urlencode($this->m_iIdProject) .
				'&iIdTask=' . urlencode($this->m_iIdTask) . '&iUseEditor=1&isProject=' . $isProject;
			$this->initCommentaries($this->m_iIdProjectSpace, $this->m_iIdProject, $this->m_iIdTask);
			$this->initFormVariables();
		}

		function getTaskInfo()
		{
			$this->m_oTask =new BAB_TM_Task();
			
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
//			$this->m_spfResult = bab_selectAvailableSpecificFieldClassesByProject($this->m_iIdProjectSpace, $this->m_iIdProject);
//			$this->m_spfInstResult = bab_selectAllSpecificFieldInstance($this->m_iIdTask);
			
			$this->aAdditionnalField = bab_getAdditionalTaskField($this->m_iIdProjectSpace, $this->m_iIdProject, $this->m_iIdTask);
			
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
			$oEditor->setParameters(array('height' => 300));
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
			
			if(BAB_TM_DURATION == $iDurationType)
			{
				$this->set_caption('sPlannedEndDate', bab_translate("Deadline"));
			}
		}

		function initDuration($iDuration)
		{
			$this->set_data('sDuration', $iDuration);
			$this->set_data('sReadOnlyDuration', $this->m_bIsManager ? '' : 'readonly="readonly"');
		}

		function initDurationUnit($iDurationUnit)
		{
			$this->set_data('oDurationUnit', $iDurationUnit);
			$this->set_data('sDisabledDurationUnit', $this->m_bIsManager ? '' : 'disabled="disabled"');
			$this->set_data('sSelectedDurationUnit', '');
		}

		function initDateTime($sFieldNamePart, $sDate, $iHour, $iMinut)
		{
			$this->set_data('s' . $sFieldNamePart . 'Date', (('00-00-0000' !== (string) $sDate) ? (string) $sDate : ''));
			$this->set_data('i' . $sFieldNamePart . 'Hour', (int) $iHour);
			$this->set_data('i' . $sFieldNamePart . 'Minut', (int) $iMinut);

			$isEnabled = false;
			if('PlannedStart' == $sFieldNamePart || 'PlannedEnd' == $sFieldNamePart)
			{
				$isEnabled = $this->m_bIsManager;
				 
			}
			else
			{
				$isEnabled = ($this->m_bIsManager || (BAB_TM_TASK_RESPONSIBLE == $this->m_iUserProfil && BAB_TM_YES == $this->m_aCfg['tskUpdateByMgr']));
			}
			
			$this->set_data('isReadOnly' . $sFieldNamePart . 'Date', !$isEnabled);
			$this->set_data('sReadOnly' . $sFieldNamePart . 'Date', (($isEnabled) ? '' : 'readonly="readonly"'));
			$this->set_data('sDisabled' . $sFieldNamePart . 'DateTime', (($isEnabled) ? '' : 'disabled="disabled"'));
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
//				$bIsParticipationStatusOk = (BAB_TM_ACCEPTED == $iParticipationStatus || $iParticipationStatus == BAB_TM_IN_PROGRESS);
				$bIsParticipationStatusOk = (BAB_TM_ACCEPTED == $iParticipationStatus || 
					$iParticipationStatus == BAB_TM_IN_PROGRESS || $iParticipationStatus == BAB_TM_ENDED);
			}
			
			$bIsToDoOrCheckpoint = ($this->m_oTask->m_aTask['iClass'] == BAB_TM_CHECKPOINT || 
				$this->m_oTask->m_aTask['iClass'] == BAB_TM_TODO);

			$this->set_data('isCompletionEnabled', 
				((($this->m_bIsManager || (BAB_TM_TASK_RESPONSIBLE == $this->m_iUserProfil && BAB_TM_YES == $this->m_aCfg['tskUpdateByMgr']) 
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
				$iClassType 				= (int) bab_rp('iClass', BAB_TM_TASK);
				$iIdCategory 				= (int) bab_rp('iIdCategory', 0);
				
				$sDescription = '';
				
				$iUseEditor = (int) bab_rp('iUseEditor', 0);
				if(1 === $iUseEditor)
				{
					require_once $GLOBALS['babInstallPath'] . 'utilit/editorincl.php';
					$oEditor = new bab_contentEditor('bab_taskManagerDescription');
					$sDescription = $oEditor->getContent();
				}
				else 
				{
					$sDescription = bab_rp('sDescription', '');
				}
				
				
				
				$sShortDescription 			= bab_rp('sShortDescription', '');
				$iDurationType 				= (int) bab_rp('oDurationType', BAB_TM_DATE);
				$iDurationUnit 				= (int) bab_rp('oDurationUnit', BAB_TM_DAY);
				$iDuration 					= number_format(bab_rp('sDuration', ''), 2, '.', '');
				$iPlannedTimeDurationUnit 	= (int) bab_rp('oPlannedTimeDurationUnit', BAB_TM_DAY);
				$iPlannedTime 				= number_format(bab_rp('oPlannedTime', 0), 2, '.', '');
				$iTimeDurationUnit 			= (int) bab_rp('oTimeDurationUnit', BAB_TM_DAY);
				$iTime 						= number_format(bab_rp('oTime', 0), 2, '.', '');
				$iPlannedCost				= number_format(bab_rp('oPlannedCost', 0), 2, '.', '');
				$iCost 						= number_format(bab_rp('oCost', 0), 2, '.', '');
				$iPriority					= (int) bab_rp('oTaskPriority', 5);
				
				$this->extractDateTimePart('PlannedStart', $sPlannedStartDate, 
					$iPlannedStartHour, $iPlannedStartMinut);
				
				$this->extractDateTimePart('PlannedEnd', $sPlannedEndDate, 
					$iPlannedEndHour, $iPlannedEndMinut);
				
				$this->extractDateTimePart('Start', $sStartDate, 
					$iStartHour, $iStartMinut);
				
				$this->extractDateTimePart('End', $sEndDate, 
					$iEndHour, $iEndMinut);
				
				$iIdResponsible 	= (int) bab_rp('iIdTaskResponsible', 0);
				$iProposable 		= (int) bab_rp('oProposable', BAB_TM_NO);
				$iCompletion 		= (int) bab_rp('oCompletion', 0);
				$iPredecessor 		= (int) bab_rp('iPredecessor', -1);
				
				$iIsLinked = -1;
				$iLinkType = -1;
				if(-1 != $iPredecessor && bab_getTask($iPredecessor, $aTask))
				{
					//zero basediUseEditor
//					$iPosition = $aTask['iPosition'] -1;
					$iPosition = $aTask['iPosition'];
					if( isset($_POST['oLinkType']) && isset($_POST['oLinkType'][$iPosition]) )
					{
						$iLinkType = $_POST['oLinkType'][$iPosition];
						$iIsLinked = (-1 != $iLinkType) ? BAB_TM_YES : BAB_TM_NO;
					}
				}
			}
			else if($bIsEdition)
			{
				$aTask 						=& $this->m_oTask->m_aTask;
				$sTaskNumber 				= $aTask['sTaskNumber'];
				$iClassType 				= $aTask['iClass'];
				$iIdCategory 				= (int) bab_rp('iIdCategory', $aTask['iIdCategory']);
				$sDescription 				= bab_rp('sDescription', $aTask['sDescription']);
				$sShortDescription 			= bab_rp('sShortDescription', $aTask['sShortDescription']);
				$iDurationType 				= (int) bab_rp('oDurationType', ((int) $aTask['iDuration'] != 0) ? BAB_TM_DURATION : BAB_TM_DATE);
				$iDurationUnit 				= (int) bab_rp('oDurationUnit', (int) $aTask['iDurationUnit']);
				$iDuration 					= number_format(bab_rp('sDuration', $aTask['iDuration']), 2, '.', '');
				$iPlannedTimeDurationUnit 	= (int) bab_rp('oPlannedTimeDurationUnit', (int) $aTask['iPlannedTimeDurationUnit']);
				$iPlannedTime 				= number_format(bab_rp('oPlannedTime', $aTask['iPlannedTime']), 2, '.', '');
				$iTimeDurationUnit 			= (int) bab_rp('oTimeDurationUnit', (int) $aTask['iTimeDurationUnit']);
				$iTime 						= number_format(bab_rp('oTime', $aTask['iTime']), 2, '.', '');
				$iPlannedCost				= number_format(bab_rp('oPlannedCost', $aTask['iPlannedCost']), 2, '.', '');
				$iCost 						= number_format(bab_rp('oCost', $aTask['iCost']), 2, '.', '');
				$iPriority					= (int) bab_rp('oTaskPriority', $aTask['iPriority']);
				
				$this->extractDateTimePart('PlannedStart', $sPlannedStartDate, 
					$iPlannedStartHour, $iPlannedStartMinut);
				
				$this->extractDateTimePart('PlannedEnd', $sPlannedEndDate, 
					$iPlannedEndHour, $iPlannedEndMinut);
				
				$this->extractDateTimePart('Start', $sStartDate, 
					$iStartHour, $iStartMinut);
				
				$this->extractDateTimePart('End', $sEndDate, 
					$iEndHour, $iEndMinut);
				
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
			$this->initDurationUnit($iDurationUnit);
			$this->initDuration($iDuration);
			
			$this->initDateTime('PlannedStart', $sPlannedStartDate, 
				$iPlannedStartHour, $iPlannedStartMinut);
			
			$this->initDateTime('PlannedEnd', $sPlannedEndDate, 
				$iPlannedEndHour, $iPlannedEndMinut);
			
			$this->initDateTime('Start', $sStartDate, 
				$iStartHour, $iStartMinut);
			
			$this->initDateTime('End', $sEndDate, 
				$iEndHour, $iEndMinut);
			
			$this->initResponsible($iIdResponsible);
			$this->initProposable($iProposable);
			$this->initCompletion($iCompletion);
			$this->initPredecessor($iPredecessor, $iLinkType);
			$this->initAnwser($bIsCreation);
			
			$this->set_data('oPlannedTimeDurationUnit', $iPlannedTimeDurationUnit);
			$this->set_data('iPlannedTime', $iPlannedTime);
			$this->set_data('oTimeDurationUnit', $iTimeDurationUnit);
			$this->set_data('iTime', $iTime);
			$this->set_data('iPlannedCost', $iPlannedCost);
			$this->set_data('iCost', $iCost);
			$this->set_data('iSelectedTaskPriority', $iPriority);
		}
		
		function extractDateTimePart($sFieldNamePart, &$sDate, &$iHour, &$iMinut)
		{
			$sDate 	= bab_rp('s' . $sFieldNamePart . 'Date', 'undefined');
			$iHour 	= (int) bab_rp('o' . $sFieldNamePart . 'Hour', 0);
			$iMinut	= (int) bab_rp('o' . $sFieldNamePart . 'Minut', 0);
			
			if('undefined' == $sDate)
			{
				$sDate = $this->m_oTask->m_aTask['s' . $sFieldNamePart . 'Date'];
				
				if('0000-00-00 00:00:00' !== $sDate && preg_match("/(^[0-9]{4}-[0-9]{2}-[0-9]{2}) ([0-9]{2}):([0-9]{2}).*$/", $sDate, $aExplodedDate))
				{
					$sDate	= $aExplodedDate[1];
					$sDate = str_replace('-', '/', $sDate);
					$oDate = BAB_DateTime::fromIsoDateTime($sDate);
					if(null !== $oDate)
					{
						$sDate = bab_shortDate($oDate->getTimeStamp(), false);
						$sDate = str_replace('/', '-', $sDate);
					}
					$iHour	= $aExplodedDate[2];	
					$iMinut	= $aExplodedDate[3];	
				}
				else
				{
					$sDate	= '';
					$iHour	= 0;	
					$iMinut	= 0;	
				}
			}
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
		
/*
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
//*/

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

//					$sPredecessor = (mb_strlen(trim($datas['shortDescription']) > 0)) ? $datas['shortDescription'] : $datas['taskNumber'];
					$this->set_data('sPredecessorNumber', $datas['taskNumber']);
					$this->set_data('sPredecessorShortDescription', $datas['shortDescription']);
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
			
			$aItem = each($this->aAdditionnalField);
			
			
			if(false !== $aItem)
			{
				$this->set_data('sSpFldInstanceName', $aItem['value']['sFieldName']);
				$this->set_data('sSpFldInstanceType', $aItem['value']['sType']);
				$this->set_data('sSpFldInstanceValue', $aItem['value']['sValue']);
				$this->set_data('iSpFldInstanceClass', $aItem['value']['iType']);
$this->set_data('iSpFldInstanceId', $aItem['value']['iIdFieldClass']);
				
				$this->set_data('sSpFldInstanceSelected', '');
				if(BAB_TM_RADIO_FIELD == $aItem['value']['iType'])
				{
					$this->m_spfValueResult = bab_selectSpecificFieldClassValues($aItem['value']['iIdFieldClass']);
				}
				
				return true;
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
		var $m_iDurationType			= null;
		var $m_iDurationUnit			= null;
		
		var $m_iMajorVersion			= null;
		var $m_iMinorVersion			= null;
		var $m_sColor					= null;
		var $m_iPosition				= 0;
		var $m_iCompletion				= null;
		
		var $m_sPlannedStartDate		= null;
		var $m_iPlannedStartHour		= null;
		var $m_iPlannedStartMinut		= null;
		
		var $m_sPlannedEndDate			= null;
		var $m_iPlannedEndHour			= null;
		var $m_iPlannedEndMinut			= null;
		
		var $m_sStartDate				= null;
		var $m_iStartHour				= null;
		var $m_iStartMinut				= null;
		
		var $m_sEndDate					= null;
		var $m_iEndHour					= null;
		var $m_iEndMinut				= null;
		
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
		
		var $m_iPlannedTimeDurationUnit = null;
		var $m_iPlannedTime				= null;
		var $m_iTimeDurationUnit		= null;
		var $m_iTime					= null;
		var $m_iPlannedCost				= null;
		var $m_iCost					= null;
		
		var $m_iPriority				= null;
		
		function BAB_TM_TaskValidatorBase()
		{
			$this->init();
		}
		
		function init()
		{
			$oTmCtx						=& getTskMgrContext();
			$this->m_iIdProjectSpace	= $oTmCtx->getIdProjectSpace();
			$this->m_iIdProject			= $oTmCtx->getIdProject();
			$this->m_iIdTask			= $oTmCtx->getIdTask();
			$this->m_iUserProfil		= $oTmCtx->getUserProfil();
			$this->m_oTask				=new BAB_TM_Task();

			$this->m_sTaskNumber = trim(bab_rp('sTaskNumber', ''));

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
			
			$this->m_sShortDescription			= trim(bab_rp('sShortDescription', ''));
			$this->m_iIdCategory				= (int) bab_rp('iIdCategory', 0);
			$this->m_sCreated					= date("Y-m-d H:i:s");
			$this->m_sModified					= date("Y-m-d H:i:s");
			$this->m_iIdUserCreated				= $GLOBALS['BAB_SESS_USERID'];
			$this->m_iIdUserModified			= $GLOBALS['BAB_SESS_USERID'];
			$this->m_iClass						= (int) bab_rp('iClass', 0);
			$this->m_iParticipationStatus		= 0;
			$this->m_iIdCalEvent				= 0;
			$this->m_sHashCalEvent				= '';
			
			$this->m_iDurationType 				= (int) bab_rp('oDurationType', BAB_TM_DATE);
			$this->m_iDuration					= (float) (BAB_TM_DATE != $this->m_iDurationType) ? number_format(bab_rp('sDuration', ''), 2, '.', '') : 0;
			$this->m_iDurationUnit 				= (int) bab_rp('oDurationUnit', BAB_TM_DAY);
			
			$this->m_iMajorVersion				= (int) bab_rp('iMajorVersion', 1);
			$this->m_iMinorVersion				= (int) bab_rp('iMinorVersion', 0);
			$this->m_sColor						= '';
			$this->m_iPosition					= (0 != $this->m_iIdTask) ? $this->m_oTask->m_aTask['iPosition'] : $this->m_oTask->m_iNextPosition;
			$this->m_iCompletion				= (int) bab_rp('oCompletion', 0);
			
			$this->m_iIsNotified				= BAB_TM_NO;
			$this->m_iAnswer					= (int) bab_rp('oAnswerEnable', -1);
			
			$this->m_iIsLinked					= (isset($_POST['oLinkedTask'])) ? BAB_TM_YES : BAB_TM_NO;
			$this->m_iLinkType 					= -1;
			$this->m_iIdPredecessor 			= (int) bab_rp('iPredecessor', -1);
			
			$this->m_iPlannedTimeDurationUnit	= (int) bab_rp('oPlannedTimeDurationUnit', BAB_TM_DAY);
			$this->m_iPlannedTime				= number_format(bab_rp('oPlannedTime', 0), 2, '.', '');
			$this->m_iTimeDurationUnit			= (int) bab_rp('oTimeDurationUnit', BAB_TM_DAY);
			$this->m_iTime						= number_format(bab_rp('oTime', 0), 2, '.', '');
			$this->m_iPlannedCost				= number_format(bab_rp('oPlannedCost', 0), 2, '.', '');
			$this->m_iCost						= number_format(bab_rp('oCost', 0), 2, '.', '');
			$this->m_iPriority					= (int) bab_rp('oTaskPriority', 5);
			$this->m_iIdTaskResponsible			= (int) bab_rp('iIdTaskResponsible', -1);
			
			/*
			bab_debug($_POST);
			bab_debug('oPlannedTimeDurationUnit ==> ' . $this->m_iPlannedTimeDurationUnit);
			bab_debug('iPlannedTime ==> ' . $this->m_iPlannedTime);
			bab_debug('iTimeDurationUnit ==> ' . $this->m_iTimeDurationUnit);
			bab_debug('iTime ==> ' . $this->m_iTime);
			bab_debug('iPlannedCost ==> ' . $this->m_iPlannedCost);
			bab_debug('iCost ==> ' . $this->m_iCost);
			//*/			
			
			$aTask = null;
			if(BAB_TM_YES === $this->m_iIsLinked && -1 != $this->m_iIdPredecessor && bab_getTask($this->m_iIdPredecessor, $aTask))
			{
				if( isset($_POST['oLinkType']) && isset($_POST['oLinkType'][$this->m_iIdPredecessor]) )
				{
					$this->m_iLinkType = (int) $_POST['oLinkType'][$this->m_iIdPredecessor];
				}
			}
			
			$this->processPostedDate('PlannedStart');
			$this->processPostedDate('PlannedEnd');
			$this->processPostedDate('Start');
			$this->processPostedDate('End');

			bab_getAvailableTaskResponsibles($this->m_iIdProject, $this->m_aAvailableResponsibles);
			bab_getTaskResponsibles($this->m_iIdTask, $this->m_aTaskResponsibles);
			bab_getDependingTasks($this->m_iIdTask, $this->m_aDependingTasks);
			
			//Si il y a un predecesseur
			if(!is_null($aTask))
			{
/*
$sMsg = 'This task have a predecessor';
bab_debug($sMsg);			
//echo $sMsg . '<br/>';
//*/
				if(BAB_TM_START_TO_START == $this->m_iLinkType)
				{
					$this->m_sPlannedStartDate = $aTask['sPlannedStartDate'];
				}
				else if(BAB_TM_END_TO_START == $this->m_iLinkType)
				{
/*
$sMsg = 'This task is linked : The link is END ==> START';
bab_debug($sMsg);			
//echo $sMsg . '<br/>';
//*/
					$oStartDate = BAB_DateTime::fromIsoDateTime($aTask['sPlannedEndDate']);
					$this->m_sPlannedStartDate = $oStartDate->getIsoDateTime();
				}
				else 
				{
					bab_debug(__CLASS__ . ' ' . __FUNCTION__ . ': LinkType error');
				}
			}
			
			//Lors de la mise à jour d'une tâche cette variable n'est pas
			//posté si on est pas gestionnaire
			if(-1 == $this->m_iIdTaskResponsible && !array_key_exists('iIdTaskResponsible', $_POST))
			{
				$aResponsible = array();
				if(false !== ($aResponsible = each($this->m_aTaskResponsibles)))
				{
					reset($this->m_aTaskResponsibles);
					$this->m_iIdTaskResponsible = (int) $aResponsible['value']['id'];
				}
			}
			
			if(BAB_TM_DURATION === $this->m_iDurationType && 0 == mb_strlen(trim(bab_rp('sPlannedEndDate', ''))))
			{
				//$this->computeEndDate();
				
				require_once($GLOBALS['babInstallPath'] . 'tmTaskTime.class.php');
				$oPlannedEndDate = null;
				BAB_TM_TaskTime::computeEndDate($this->m_sPlannedStartDate, $this->m_iDuration, $this->m_iDurationUnit, $oPlannedEndDate);
				$this->m_sPlannedEndDate = $oPlannedEndDate->getIsoDateTime();
			}

			/*			
			echo 'sPlannedStartDate ==> ' . $this->m_sPlannedStartDate . '<br/>';
			echo 'sPlannedEndDate ==> ' . $this->m_sPlannedEndDate . '<br/>';
			echo 'sStartDate ==> ' . $this->m_sStartDate . '<br/>';
			echo 'sEndDate ==> ' . $this->m_sEndDate . '<br/>';
			//*/
			
			if($this->m_oTask->m_isPersonnal)
			{
				$this->m_aCfg =& $this->m_oTask->getConfiguration();
			}
			else
			{
				$this->m_aCfg =& $oTmCtx->getConfiguration();
			}
		}
		
		function processPostedDate($sFieldPartName)
		{
			$sDateFieldName		= 's' . $sFieldPartName . 'Date';
			$sHourFieldName 	= 'o' . $sFieldPartName . 'Hour';
			$sMinutFieldName 	= 'o' . $sFieldPartName . 'Minut';
			
			$sDate	= trim(bab_rp($sDateFieldName, ''));
			$iHour	= (int) bab_rp($sHourFieldName, 0);
			$iMinut	= (int) bab_rp($sMinutFieldName, 0);

			//$sDate = str_replace('-', '/', $sDate);

			$sDateFieldName 	= 'm_s' . $sFieldPartName . 'Date';
			$sHourFieldName 	= 'm_i' . $sFieldPartName . 'Hour';
			$sMinutFieldName	= 'm_i' . $sFieldPartName . 'Minut';
			
			$this->$sDateFieldName	= $sDate;
			$this->$sHourFieldName	= $iHour;
			$this->$sMinutFieldName	= $iMinut;
			
			$oDate = BAB_DateTime::fromUserInput($sDate);
			if(null !== $oDate)
			{
				$oDate->init($oDate->_iYear, $oDate->_iMonth, $oDate->_iDay, $iHour, $iMinut);
				$this->$sDateFieldName = $oDate->getIsoDateTime();
			}
		}

		function isTaskNumberValid()
		{
			if(mb_strlen($this->m_sTaskNumber) > 0)
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
			if(0 != trim(mb_strlen($sDate)))
			{
				$oDate = BAB_DateTime::fromIsoDateTime($sDate);
				return (!is_null($oDate));
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
			$sBody = sprintf($sBody, ((mb_strlen(trim($this->m_sShortDescription)) > 0) ? $this->m_sShortDescription : $this->m_sTaskNumber), $sProjectName, $sProjectSpaceName);
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
			$sBody = sprintf($sBody, ((mb_strlen(trim($this->m_sShortDescription)) > 0) ? $this->m_sShortDescription : $this->m_sTaskNumber), $sProjectName, $sProjectSpaceName);
			
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
			$sBody = sprintf($sBody, ((mb_strlen(trim($this->m_sShortDescription)) > 0) ? $this->m_sShortDescription : $this->m_sTaskNumber), $sProjectName, $sProjectSpaceName);
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
			$sBody = sprintf($sBody, ((mb_strlen(trim($this->m_sShortDescription)) > 0) ? $this->m_sShortDescription : $this->m_sTaskNumber), $sProjectName, $sProjectSpaceName);
			
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
			$sBody = sprintf($sBody, ((mb_strlen(trim($this->m_sShortDescription)) > 0) ? $this->m_sShortDescription : $this->m_sTaskNumber), $sProjectName, $sProjectSpaceName, 
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
/*		
			if($this->m_oTask->m_bIsEnded)
			{
				bab_debug(__FUNCTION__ . ' the task is ended');
				$GLOBALS['babBody']->msgerror = bab_translate("The task is ended");
				return false;
			}
//*/			
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
			return false;
		}
		
		function isTaskValidByDuration()
		{
			if($this->isTaskValidByDate())
			{
				//si date butoir de fin
				if(0 != mb_strlen(trim(bab_rp('sPlannedEndDate', ''))))
				{
					$oLimitEndDate = BAB_DateTime::fromIsoDateTime($this->m_sPlannedEndDate);
					$oEndDate = BAB_DateTime::fromIsoDateTime($this->m_sPlannedStartDate);
					
					//3600 nombre de secondes dans une heure
					$oEndDate->add(3600 * $this->m_iDuration, BAB_DATETIME_SECOND);
					
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
			global $babBody;
			
			$bPlannedStartDateValid = $this->isDateValid($this->m_sPlannedStartDate);
			$bPlannedEndDateValid = $this->isDateValid($this->m_sPlannedEndDate);
			if($bPlannedStartDateValid && $bPlannedEndDateValid)
			{
				$iIsEqual	= 0;
				$iIsBefore	= -1;
				$iIsAfter	= 1;
				
				//bab_debug('sStart ==> ' . $this->m_sStartDate . ' sEnd ==> ' . $this->m_sEndDate);
				$oStart = BAB_DateTime::fromIsoDateTime($this->m_sPlannedStartDate);
				$oEnd = BAB_DateTime::fromIsoDateTime($this->m_sPlannedEndDate);
				
				if($iIsBefore == BAB_DateTime::compare($oStart, $oEnd))
				{
					if($this->m_iUserProfil == BAB_TM_PROJECT_MANAGER && !$this->isResponsibleValid())
					{
						bab_debug(__FUNCTION__ . ': Invalid iIdTaskResponsible');
						$babBody->addError(bab_translate("The choosen task responsible is invalid"));
					}
					else 
					{
						return true;
					}
				}
				else 
				{
					bab_debug(__FUNCTION__ . ' sEndDate is lower than sStartDate');
					$babBody->addError(bab_translate("The planned end date is lower than the planned start date"));
					$babBody->addError(bab_translate("Planned start date") . ' ' . bab_shortDate($oStart->getTimeStamp()));
					$babBody->addError(bab_translate("Planned end date") . ' ' . bab_shortDate($oEnd->getTimeStamp()));
				}
			}
			else 
			{
				if(!$bPlannedStartDateValid)
				{
					$babBody->addError(bab_translate("The planned start date is not valid"));
					bab_debug(__FUNCTION__ . ' The planned start date is not valid');
				}
				
				if(!$bPlannedEndDateValid)
				{
					$babBody->addError(bab_translate("The planned end date is not valid"));
					bab_debug(__FUNCTION__ . ' The planned end date is not valid');
				}
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
			$oStartDate = BAB_DateTime::fromIsoDateTime($this->m_sEndDate);
			$oStartDate->init($oStartDate->_iYear, $oStartDate->_iMonth, $oStartDate->_iDay, 00, 00, 00);
			$sStartDate = date('Y-m-d H:i:s', $oStartDate->getTimeStamp());
			$sEndDate = $this->m_sEndDate;
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
				
					
				$aTask =& $this->m_oTask->m_aTask;
				
				$aTask['iIdProject']				= $this->m_iIdProject;
				$aTask['sTaskNumber']				= $this->m_sTaskNumber;
				$aTask['sDescription']				= $this->m_sDescription;
				$aTask['sShortDescription']			= mb_substr($this->m_sShortDescription, 0, 255);
				$aTask['iIdCategory']				= $this->m_iIdCategory;
				$aTask['sCreated']					= $this->m_sCreated;
				$aTask['iIdUserCreated']			= $this->m_iIdUserCreated;
				$aTask['sModified']					= '';
				$aTask['iIdUserModified']			= 0;
				$aTask['iClass']					= $this->m_iClass;
				$aTask['iParticipationStatus']		= $iParticipationStatus;
				$aTask['iIsLinked']					= $this->m_iIsLinked;
				$aTask['iIdCalEvent']				= $this->m_iIdCalEvent;
				$aTask['sHashCalEvent']				= $this->m_sHashCalEvent;
				$aTask['iDuration']					= $this->m_iDuration;
				$aTask['iDurationUnit']				= $this->m_iDurationUnit;
				$aTask['iMajorVersion']				= $this->m_iMajorVersion;
				$aTask['iMinorVersion']				= $this->m_iMinorVersion;
				$aTask['sColor']					= $this->m_sColor;
				$aTask['iPosition']					= $this->m_iPosition;
				$aTask['iCompletion']				= 0;
				$aTask['sStartDate']				= $this->m_sStartDate;
				$aTask['sEndDate'] 					= $this->m_sEndDate;
				$aTask['sPlannedStartDate']			= $this->m_sPlannedStartDate;
				$aTask['sPlannedEndDate'] 			= $this->m_sPlannedEndDate;
				$aTask['iIsNotified']				= BAB_TM_YES;
				$aTask['iPlannedTimeDurationUnit']	= $this->m_iPlannedTimeDurationUnit;
				$aTask['iPlannedTime']				= $this->m_iPlannedTime;
				$aTask['iTimeDurationUnit']			= $this->m_iTimeDurationUnit;
				$aTask['iTime']						= $this->m_iTime;
				$aTask['iPlannedCost']				= $this->m_iPlannedCost;
				$aTask['iCost']						= $this->m_iCost;
				$aTask['iPriority']					= $this->m_iPriority;
				
				
				//bab_debug($aTask);
//*				
				$iIdTask = bab_createTask($aTask);
				if(false !== $iIdTask)
				{
					$this->m_iIdTask = $iIdTask;
					
					bab_tskmgr_createTaskAdditionalFields($this->m_iIdProjectSpace, $this->m_iIdProject, $this->m_iIdTask);
					
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
				
				$aTask['iIdProject']				= $this->m_iIdProject;
				$aTask['sTaskNumber']				= $this->m_sTaskNumber;
				$aTask['sDescription']				= $this->m_sDescription;
				$aTask['sShortDescription']			= mb_substr($this->m_sShortDescription, 0, 255);
				$aTask['iIdCategory']				= $this->m_iIdCategory;
				$aTask['sCreated']					= $this->m_sCreated;
				$aTask['iIdUserCreated']			= $this->m_iIdUserCreated;
				$aTask['sModified']					= '';
				$aTask['iIdUserModified']			= 0;
				$aTask['iClass']					= $this->m_iClass;
				$aTask['iParticipationStatus']		= 0;
				$aTask['iIsLinked']					= BAB_TM_NO;
				$aTask['iIdCalEvent']				= 0;
				$aTask['sHashCalEvent']				= '';
				$aTask['iDuration']					= 0;
				$aTask['iDurationUnit']				= $this->m_iDurationUnit;
				$aTask['iMajorVersion']				= $this->m_iMajorVersion;
				$aTask['iMinorVersion']				= $this->m_iMinorVersion;
				$aTask['sColor']					= $this->m_sColor;
				$aTask['iPosition']					= $this->m_iPosition;
				$aTask['iCompletion']				= 0;
				
				$this->getIsoDatesFromEndDate($sStartDate, $sEndDate);
				
				$aTask['sStartDate']				= $sStartDate;
				$aTask['sEndDate'] 					= $sEndDate;
				$aTask['sPlannedStartDate']			= $sStartDate;
				$aTask['sPlannedEndDate'] 			= $sEndDate;
				$aTask['iIsNotified']				= BAB_TM_NO;
				$aTask['iPlannedTimeDurationUnit']	= 0;
				$aTask['iPlannedTime']				= 0.00;
				$aTask['iTimeDurationUnit']			= 0;
				$aTask['iTime']						= 0.00;
				$aTask['iPlannedCost']				= 0.00;
				$aTask['iCost']						= 0.00;
				$aTask['iPriority']					= $this->m_iPriority;
				
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
				
				$aTask['iIdProject']				= $this->m_iIdProject;
				$aTask['sTaskNumber']				= $this->m_sTaskNumber;
				$aTask['sDescription']				= $this->m_sDescription;
				$aTask['sShortDescription']			= mb_substr($this->m_sShortDescription, 0, 255);
				$aTask['iIdCategory']				= $this->m_iIdCategory;
				$aTask['sCreated']					= $this->m_sCreated;
				$aTask['iIdUserCreated']			= $this->m_iIdUserCreated;
				$aTask['sModified']					= '';
				$aTask['iIdUserModified']			= 0;
				$aTask['iClass']					= $this->m_iClass;
				$aTask['iParticipationStatus']		= 0;
				$aTask['iIsLinked']					= BAB_TM_NO;
				$aTask['iIdCalEvent']				= 0;
				$aTask['sHashCalEvent']				= '';
				$aTask['iDuration']					= 0;
				$aTask['iDurationUnit']				= $this->m_iDurationUnit;
				$aTask['iMajorVersion']				= $this->m_iMajorVersion;
				$aTask['iMinorVersion']				= $this->m_iMinorVersion;
				$aTask['sColor']					= $this->m_sColor;
				$aTask['iPosition']					= $this->m_iPosition;
				$aTask['iCompletion']				= 0;
				
				$this->getIsoDatesFromEndDate($sStartDate, $sEndDate);
				
				$aTask['sStartDate']				= $sStartDate;
				$aTask['sEndDate'] 					= $sEndDate;
				$aTask['sPlannedStartDate']			= $sStartDate;
				$aTask['sPlannedEndDate'] 			= $sEndDate;
				$aTask['iIsNotified']				= BAB_TM_NO;
				$aTask['iPlannedTimeDurationUnit']	= 0;
				$aTask['iPlannedTime']				= 0.00;
				$aTask['iTimeDurationUnit']			= 0;
				$aTask['iTime']						= 0.00;
				$aTask['iPlannedCost']				= 0.00;
				$aTask['iCost']						= 0.00;
				$aTask['iPriority']					= $this->m_iPriority;
				
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
				
				/*
				if((is_null($this->m_sStartDate) || 0 == mb_strlen($this->m_sStartDate)) && 0 === (int) $aTask['iCompletion'] && 0 < $this->m_iCompletion)
				{
					$this->m_sStartDate = date('Y-m-d H:i:s');
				}
				//*/
				
				$aTask['sTaskNumber']				= $this->m_sTaskNumber;
				$aTask['sDescription']				= (BAB_TM_TASK_RESPONSIBLE !== $this->m_iUserProfil) ? $this->m_sDescription : $aTask['sShortDescription'];
				$aTask['sShortDescription']			= (BAB_TM_TASK_RESPONSIBLE !== $this->m_iUserProfil) ? mb_substr($this->m_sShortDescription, 0, 255) : $aTask['sShortDescription'];
				$aTask['iIdCategory']				= $this->m_iIdCategory;
				$aTask['sModified']					= $this->m_sModified;
				$aTask['iIdUserModified']			= $this->m_iIdUserModified;
				$aTask['iIsLinked']					= $this->m_iIsLinked;
				$aTask['iDuration']					= $this->m_iDuration;
				$aTask['iDurationUnit']				= $this->m_iDurationUnit;
				$aTask['iMajorVersion']				= $this->m_iMajorVersion;
				$aTask['iMinorVersion']				= $this->m_iMinorVersion;
//				$aTask['iCompletion']				= $this->m_iCompletion;
				$aTask['sStartDate']				= $this->m_sStartDate;
				$aTask['sEndDate'] 					= $this->m_sEndDate;
				$aTask['sPlannedStartDate']			= $this->m_sPlannedStartDate;
				$aTask['sPlannedEndDate'] 			= $this->m_sPlannedEndDate;
				$aTask['iIsNotified']				= BAB_TM_YES;
				$aTask['iPlannedTimeDurationUnit']	= $this->m_iPlannedTimeDurationUnit;
				$aTask['iPlannedTime']				= $this->m_iPlannedTime;
				$aTask['iTimeDurationUnit']			= $this->m_iTimeDurationUnit;
				$aTask['iTime']						= $this->m_iTime;
				$aTask['iPlannedCost']				= $this->m_iPlannedCost;
				$aTask['iCost']						= $this->m_iCost;
				$aTask['iPriority']					= $this->m_iPriority;
				
				if(-1 != $this->m_iAnswer)
				{
					$aTask['iParticipationStatus'] = (BAB_TM_YES == $this->m_iAnswer) ? BAB_TM_ACCEPTED : BAB_TM_REFUSED;
				}
				
				if(100 != (int) $aTask['iCompletion'] && ((int) $this->m_iCompletion >= 100 || BAB_TM_ENDED === (int) $aTask['iParticipationStatus']))
				{
					//$aTask['sEndDate'] = date("Y-m-d H:i:s");
					$aTask['iParticipationStatus'] = BAB_TM_ENDED;
				}
				else if(100 == (int) $aTask['iCompletion'] && (int) $this->m_iCompletion < 100)
				{
					//$aTask['sEndDate'] = '';
					$aTask['iParticipationStatus'] = BAB_TM_IN_PROGRESS;
				}
				
				$aTask['iCompletion'] = $this->m_iCompletion;
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
					
					$this->updateAdditionalField();
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
							$aDependingTask['sPlannedStartDate'] = $aTask['sPlannedStartDate'];
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
				if($bIsNewTaskResponsible)
				{
					$this->noticeNewTaskResponsible($this->m_iIdTaskResponsible);
				}
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

		
		function updateAdditionalField()
		{
//			$aDeletableSpfObjects = isset($_POST['aDeletableSpfObjects']) ? $_POST['aDeletableSpfObjects'] : array();
			$aSpFldInstanceValue = isset($_POST['aSpFldInstanceValue']) ? $_POST['aSpFldInstanceValue'] : array();
			
			$aDatas = array();
			
			foreach($aSpFldInstanceValue as $iIdFieldClass => $sValue)
			{
				$aDatas['sField' . $iIdFieldClass] = $sValue;
			}
			
			if(count($aDatas) > 0)
			{
				bab_tskmgr_updateAdditionalField($this->m_iIdProjectSpace, $this->m_iIdProject, 
					$this->m_iIdTask, $aDatas);
			}
		}

		
		function saveCheckPoint()
		{
			if($this->isCheckPointValid())
			{
				bab_debug(__CLASS__ . ' ' . __FUNCTION__ . ' is Valid');
//*				
				$aTask =& $this->m_oTask->m_aTask;
				
				$aTask['iIdProject']				= $this->m_iIdProject;
				$aTask['sTaskNumber']				= $this->m_sTaskNumber;
				$aTask['sDescription']				= $this->m_sDescription;
				$aTask['sShortDescription']			= mb_substr($this->m_sShortDescription, 0, 255);
				$aTask['iIdCategory']				= $this->m_iIdCategory;
				$aTask['sModified']					= $this->m_sModified;
				$aTask['iIdUserModified']			= $this->m_iIdUserModified;
				$aTask['iParticipationStatus']		= 0;
				$aTask['iIsLinked']					= BAB_TM_NO;
				$aTask['iIdCalEvent']				= 0;
				$aTask['sHashCalEvent']				= '';
				$aTask['iDuration']					= 0;
				$aTask['iDurationUnit']				= $this->m_iDurationUnit;
				$aTask['iMajorVersion']				= $this->m_iMajorVersion;
				$aTask['iMinorVersion']				= $this->m_iMinorVersion;
				$aTask['sColor']					= $this->m_sColor;
				$aTask['iPosition']					= $this->m_iPosition;
				$aTask['iCompletion']				= $this->m_iCompletion;
				
				$this->getIsoDatesFromEndDate($sStartDate, $sEndDate);
				
				$aTask['sStartDate']				= $sStartDate;
				$aTask['sEndDate'] 					= $sEndDate;
				$aTask['sPlannedStartDate']			= $sStartDate;
				$aTask['sPlannedEndDate'] 			= $sEndDate;
				$aTask['iIsNotified']				= BAB_TM_NO;
				$aTask['iPlannedTimeDurationUnit']	= 0;
				$aTask['iPlannedTime']				= 0.00;
				$aTask['iTimeDurationUnit']			= 0;
				$aTask['iTime']						= 0.00;
				$aTask['iPlannedCost']				= 0.00;
				$aTask['iCost']						= 0.00;
				$aTask['iPriority']					= $this->m_iPriority;
				
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
				
				$aTask['iIdProject']				= $this->m_iIdProject;
				$aTask['sTaskNumber']				= $this->m_sTaskNumber;
				$aTask['sDescription']				= $this->m_sDescription;
				$aTask['sShortDescription']			= mb_substr($this->m_sShortDescription, 0, 255);
				$aTask['iIdCategory']				= $this->m_iIdCategory;
				$aTask['sModified']					= $this->m_sModified;
				$aTask['iIdUserModified']			= $this->m_iIdUserModified;
				$aTask['iParticipationStatus']		= 0;
				$aTask['iIsLinked']					= BAB_TM_NO;
				$aTask['iIdCalEvent']				= 0;
				$aTask['sHashCalEvent']				= '';
				$aTask['iDuration']					= 0;
				$aTask['iDurationUnit']				= $this->m_iDurationUnit;
				$aTask['iMajorVersion']				= $this->m_iMajorVersion;
				$aTask['iMinorVersion']				= $this->m_iMinorVersion;
				$aTask['sColor']					= $this->m_sColor;
				$aTask['iPosition']					= $this->m_iPosition;
				$aTask['iCompletion']				= $this->m_iCompletion;
				
				$this->getIsoDatesFromEndDate($sStartDate, $sEndDate);
				
				$aTask['sStartDate']				= $sStartDate;
				$aTask['sEndDate'] 					= $sEndDate;
				$aTask['sPlannedStartDate']			= $sStartDate;
				$aTask['sPlannedEndDate'] 			= $sEndDate;
				$aTask['iIsNotified']				= BAB_TM_NO;
				$aTask['iPlannedTimeDurationUnit']	= 0;
				$aTask['iPlannedTime']				= 0.00;
				$aTask['iTimeDurationUnit']			= 0;
				$aTask['iTime']						= 0.00;
				$aTask['iPlannedCost']				= 0.00;
				$aTask['iCost']						= 0.00;
				$aTask['iPriority']					= $this->m_iPriority;
				
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
	
	
	class BAB_TM_TaskUpdateByTaskResponsible
	{
		var $m_sStartDate				= null;
		var $m_iStartHour				= null;
		var $m_iStartMinut				= null;
		var $m_sEndDate					= null;
		var $m_iEndHour					= null;
		var $m_iEndMinut				= null;
		var $m_sModified				= null;
		var $m_iIdUserModified			= null;
		var $m_iCompletion				= null;

		var $m_iIdProjectSpace			= null;
		var $m_iIdProject				= null;
		var $m_iIdTask					= null;
		var $m_iUserProfil				= null;
		var $m_oTask					= null;
		
		var $m_sTaskNumber				= null;
		function BAB_TM_TaskUpdateByTaskResponsible()
		{
			$oTmCtx						=& getTskMgrContext();
			$this->m_iIdProjectSpace	= $oTmCtx->getIdProjectSpace();
			$this->m_iIdProject			= $oTmCtx->getIdProject();
			$this->m_iIdTask			= $oTmCtx->getIdTask();
			$this->m_iUserProfil		= $oTmCtx->getUserProfil();
			$this->m_oTask				= new BAB_TM_Task();

			$this->processPostedDate('Start');
			$this->processPostedDate('End');
			
			$this->m_sModified			= date("Y-m-d H:i:s");
			$this->m_iIdUserModified	= $GLOBALS['BAB_SESS_USERID'];
			$this->m_iCompletion		= (int) bab_rp('oCompletion', 0);
			
			$this->m_sTaskNumber		= $this->m_oTask->m_aTask['sTaskNumber'];
			//$this->m_iParticipationStatus		= 0;
		}
		
		function processPostedDate($sFieldPartName)
		{
			$sDateFieldName		= 's' . $sFieldPartName . 'Date';
			$sHourFieldName 	= 'o' . $sFieldPartName . 'Hour';
			$sMinutFieldName 	= 'o' . $sFieldPartName . 'Minut';
			
			$sDate	= trim(bab_rp($sDateFieldName, ''));
			$iHour	= (int) bab_rp($sHourFieldName, 0);
			$iMinut	= (int) bab_rp($sMinutFieldName, 0);

			$sDate = str_replace('-', '/', $sDate);

			$sDateFieldName 	= 'm_s' . $sFieldPartName . 'Date';
			$sHourFieldName 	= 'm_i' . $sFieldPartName . 'Hour';
			$sMinutFieldName	= 'm_i' . $sFieldPartName . 'Minut';
			
			$this->$sDateFieldName	= $sDate;
			$this->$sHourFieldName	= $iHour;
			$this->$sMinutFieldName	= $iMinut;
			
			$oDate = BAB_DateTime::fromUserInput($sDate);
			if(null !== $oDate)
			{
				$oDate->init($oDate->_iYear, $oDate->_iMonth, $oDate->_iDay, $iHour, $iMinut);
				$this->$sDateFieldName = $oDate->getIsoDateTime();
			}
		}
		
		function isTaskValid()
		{
			return $this->datesValid();
		}
				
		function datesValid()
		{
			global $babBody;
			$bSuccess = true;
			
			$bStartDateValid = $this->isoDateTimeDateValid($this->m_sStartDate);
			$bEndDateValid = $this->isoDateTimeDateValid($this->m_sEndDate);

			if($this->m_iCompletion > 0 && !$bStartDateValid)
			{
				$babBody->addError(bab_translate("You must enter a valid real start date when the completion rate is greater than 0%"));
				$bSuccess = false;
			}
			
			if($this->m_iCompletion >= 100 && !$bEndDateValid)
			{
				$babBody->addError(bab_translate("You must enter a valid real end date when the completion rate is equal to 100%"));
				$bSuccess = false;
			}

			if($bStartDateValid && $this->m_iCompletion <= 0)
			{
				$babBody->addError(bab_translate("You must select a completion rate greater than 0% when you enter a real start date"));
				$bSuccess = false;
			}

			if($bEndDateValid && $this->m_iCompletion < 100)
			{
				$babBody->addError(bab_translate("You must select 100% for the completion rate when you enter a real end date"));
				$bSuccess = false;
			}
			
			
			//bab_debug($this->m_sStartDate . ' ' . $this->m_sEndDate);
			
			if($bStartDateValid && $bEndDateValid)
			{
				$oStartDate = BAB_DateTime::fromIsoDateTime($this->m_sStartDate);
				$oEndDate = BAB_DateTime::fromIsoDateTime($this->m_sEndDate);
				
				$iIsEqual	= 0;
				$iIsBefore	= -1;
				$iIsAfter	= 1;
				
				if($iIsAfter == BAB_DateTime::compare($oStartDate, $oEndDate))
				{
					$babBody->addError(bab_translate("The real start date is greater than the real end date"));
					$bSuccess = false;
				}
			}
			return $bSuccess;
		}
		
		function isoDateTimeDateValid($sIsoDateTime)
		{
			if(0 !== preg_match("/^([0-9]{4}).{1}([0-9]{2}).{1}([0-9]{2})[[:space:]]{1}([0-9]{2})\:([0-9]{2})\:([0-9]{2})/", $sIsoDateTime, $aMatch))
			{
				if(mb_strlen(trim($sIsoDateTime)) > 0)
				{
					$oDate = BAB_DateTime::fromIsoDateTime($sIsoDateTime);
					if(null !== $oDate)
					{
						return BAB_DateTime::isValidDate($oDate->_iDay, $oDate->_iMonth, $oDate->_iYear);
					}
				}
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

		function noticeTaskUpdatedBy($iIdEvent)
		{
			$aTask =& $this->m_oTask->m_aTask;
			
			$g_aEmailMsg =& $GLOBALS['g_aEmailMsg'];
			$sSubject = $g_aEmailMsg[$iIdEvent]['subject'];
			$sBody = $g_aEmailMsg[$iIdEvent]['body'];
			
			$sProjectSpaceName = $this->getProjectSpaceName();
			$sProjectName = $this->getProjectName();
			$sBody = sprintf($sBody, ((mb_strlen(trim($aTask['sShortDescription'])) > 0) ? $aTask['sShortDescription'] : $aTask['sTaskNumber']), $sProjectName, $sProjectSpaceName, 
				bab_getUserName($GLOBALS['BAB_SESS_USERID']));
			sendNotice($this->m_iIdProjectSpace, $this->m_iIdProject, $this->m_iIdTask, 
				$iIdEvent, $sSubject, $sBody);
		}
		
		function updateAdditionalField()
		{
			$aSpFldInstanceValue = isset($_POST['aSpFldInstanceValue']) ? $_POST['aSpFldInstanceValue'] : array();
			$aDatas = array();
						
			foreach($aSpFldInstanceValue as $iIdFieldClass => $sValue)
			{
				$aDatas['sField' . $iIdFieldClass] = $sValue;
			}
			
			if(count($aDatas) > 0)
			{
				bab_tskmgr_updateAdditionalField($this->m_iIdProjectSpace, $this->m_iIdProject, 
					$this->m_iIdTask, $aDatas);
			}
		}
		
		function save()
		{
			if($this->isTaskValid())
			{
				$aTask =& $this->m_oTask->m_aTask;
				
				$aTask['sModified']					= $this->m_sModified;
				$aTask['iIdUserModified']			= $this->m_iIdUserModified;
				$aTask['sStartDate']				= $this->m_sStartDate;
				$aTask['sEndDate'] 					= $this->m_sEndDate;
				
				/*
				if(-1 != $this->m_iAnswer)
				{
					$aTask['iParticipationStatus'] = (BAB_TM_YES == $this->m_iAnswer) ? BAB_TM_ACCEPTED : BAB_TM_REFUSED;
				}
				//*/
				
				if(100 != (int) $aTask['iCompletion'] && ((int) $this->m_iCompletion >= 100 || BAB_TM_ENDED === (int) $aTask['iParticipationStatus']))
				{
					//$aTask['sEndDate'] = date("Y-m-d H:i:s");
					$aTask['iParticipationStatus'] = BAB_TM_ENDED;
				}
				else if(100 == (int) $aTask['iCompletion'] && (int) $this->m_iCompletion < 100)
				{
					//$aTask['sEndDate'] = '';
					$aTask['iParticipationStatus'] = BAB_TM_IN_PROGRESS;
				}

				if(0 === (int) $this->m_iCompletion)
				{
					$aTask['sStartDate'] = '';
					$aTask['sEndDate'] = '';
				}
				
				$aTask['iCompletion'] = $this->m_iCompletion;
				
				$bSuccess = bab_updateTask($this->m_iIdTask, $aTask);
				if(true === $bSuccess)				
				{
					$this->updateAdditionalField();
					
					require_once $GLOBALS['babInstallPath'] . 'tmSendMail.php';
					$this->noticeTaskUpdatedBy(BAB_TM_EV_TASK_UPDATED_BY_RESP);
				}
				return $bSuccess;
			}
			return false;
		}
	}
?>