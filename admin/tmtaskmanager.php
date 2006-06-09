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
require_once($babInstallPath . 'utilit/tmdefines.php');
require_once($babInstallPath . 'utilit/tmIncl.php');
require_once($babInstallPath . 'utilit/tmList.php');
require_once($babInstallPath . 'tmSpecificFieldsFunc.php');
require_once($babInstallPath . 'tmCategoriesFunc.php');



function displayMenu()
{
	global $babBody;
	
	$bfp = & new BAB_BaseFormProcessing();
	$bfp->set_data('dummy', 'dummy');

	$babBody->title = bab_translate("Task Manager");
	
	$itemMenu = array();
	add_item_menu($itemMenu);

	$bfp->set_anchor($GLOBALS['babUrlScript'] . '?tg=admTskMgr&idx=' . BAB_TM_IDX_DISPLAY_WORKING_HOURS_FORM , '', 'Working hours');
	$bfp->set_anchor($GLOBALS['babUrlScript'] . '?tg=admTskMgr&idx=' . BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_LIST , '', 'Projects space');

	$babBody->babecho(bab_printTemplate($bfp, 'tmCommon.html', 'displayMenu'));
}




function displayProjectsSpacesList()
{
	global $babBody;

	$itemMenu = array(
		array(
			'idx' => BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_LIST,
			'mnuStr' => bab_translate("Projects spaces"),
			'url' => $GLOBALS['babUrlScript'] . '?tg=admTskMgr&idx=' . BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_LIST),
		array(
			'idx' => BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_FORM,
			'mnuStr' => bab_translate("Add a project space"),
			'url' => $GLOBALS['babUrlScript'] . '?tg=admTskMgr&idx=' . BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_FORM)		
		);
		
	add_item_menu($itemMenu);
	$babBody->title = bab_translate("Projects spaces");
	
	$list = new BAB_TM_ListBase(bab_selectProjectSpaceList());
	
	$list->set_data('url', htmlentities($GLOBALS['babUrlScript'] . '?tg=admTskMgr&idx=' . 
		BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_FORM . '&iIdProjectSpace='));
	
	$list->set_anchor($GLOBALS['babUrlScript'] . '?tg=admTskMgr&iIdProjectSpace={ m_datas[id] }&idx=' . BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_RIGHTS_FORM,
		$GLOBALS['babSkinPath'] . 'images/Puces/manager.gif',
		bab_translate("Rights")
		);
	
	$list->set_anchor($GLOBALS['babUrlScript'] . '?tg=admTskMgr&iIdProjectSpace={ m_datas[id] }&idx=' . BAB_TM_IDX_DISPLAY_PROJECTS_CONFIGURATION_FORM,
		$GLOBALS['babSkinPath'] . 'images/Puces/manager.gif',
		bab_translate("Configuration")
		);

	$list->set_anchor($GLOBALS['babUrlScript'] . '?tg=admTskMgr&iIdProjectSpace={ m_datas[id] }&iIdProject=0&idx=' . BAB_TM_IDX_DISPLAY_SPECIFIC_FIELD_LIST,
		$GLOBALS['babSkinPath'] . 'images/Puces/manager.gif',
		bab_translate("Specific fields")
		);

	$list->set_anchor($GLOBALS['babUrlScript'] . '?tg=admTskMgr&iIdProjectSpace={ m_datas[id] }&iIdProject=0&idx=' . BAB_TM_IDX_DISPLAY_CATEGORIES_LIST,
		$GLOBALS['babSkinPath'] . 'images/Puces/manager.gif',
		bab_translate("Categories list")
		);

	$list->set_anchor($GLOBALS['babUrlScript'] . '?tg=admTskMgr&iIdProjectSpace={ m_datas[id] }&iIdProject=0&idx=' . BAB_TM_IDX_DISPLAY_NOTICE_EVENT_FORM,
		$GLOBALS['babSkinPath'] . 'images/Puces/manager.gif',
		bab_translate("Notices")
		);
		
	$babBody->babecho(bab_printTemplate($list, 'tmCommon.html', 'displayList'));
}


function displayProjectsSpacesForm()
{
	global $babBody;
	
	$oTmCtx =& getTskMgrContext();
	
	$iIdProjectSpace = $oTmCtx->getIdProjectSpace();
	$iIdDelegation = $oTmCtx->getIdDelegation();
		
	class BAB_ProjectSpace extends BAB_BaseFormProcessing
	{
		function BAB_ProjectSpace($iIdProjectSpace, $iIdDelegation)
		{
			parent::BAB_BaseFormProcessing();

			$this->set_caption('sName', bab_translate("Name"));
			$this->set_caption('sDescription', bab_translate("Description"));
			$this->set_caption('add', bab_translate("Add"));
			$this->set_caption('delete', bab_translate("Delete"));
			$this->set_caption('modify', bab_translate("Modify"));
			
			$this->set_data('sName', tskmgr_getVariable('sName', ''));
			$this->set_data('sDescription', tskmgr_getVariable('sDescription', ''));
			$this->set_data('iIdProjectSpace', $iIdProjectSpace);
			$this->set_data('iIdDelegation', $iIdDelegation);
			$this->set_data('add_idx', BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_LIST);
			$this->set_data('modify_idx', BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_LIST);
			$this->set_data('delete_idx', BAB_TM_IDX_DISPLAY_DELETE_PROJECTS_SPACES_FORM);
			$this->set_data('add_action', BAB_TM_ACTION_ADD_PROJECT_SPACE);
			$this->set_data('modify_action', BAB_TM_ACTION_MODIFY_PROJECT_SPACE);
			$this->set_data('delete_action', '');
			$this->set_data('isDeletable', bab_isProjectSpaceDeletable($iIdProjectSpace));
			
			$this->set_data('tg', 'admTskMgr');
			
			if(!isset($_POST['iIdProjectSpace']) && !isset($_GET['iIdProjectSpace']))
			{
				$this->set_data('is_creation', true);
			}
			else if( (isset($_GET['iIdProjectSpace']) || isset($_POST['iIdProjectSpace'])) && 0 != $iIdProjectSpace)
			{
				$this->set_data('is_edition', true);
				$aProjectSpace = null;
				if(bab_getProjectSpace($iIdProjectSpace, $aProjectSpace))
				{
					$this->set_data('sName', htmlentities($aProjectSpace['name'], ENT_QUOTES) );
					$this->set_data('sDescription', htmlentities($aProjectSpace['description'], ENT_QUOTES));
				}
			}
			else
			{
				$this->set_data('is_resubmission', true);
			}
		}
	}
	
	$tab_caption = ($iIdProjectSpace == 0) ? bab_translate("Add a project space") : bab_translate("Edition of a project space");
	
	$itemMenu = array(
		array(
			'idx' => BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_LIST,
			'mnuStr' => bab_translate("Projects spaces"),
			'url' => $GLOBALS['babUrlScript'] . '?tg=admTskMgr&idx=' . BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_LIST),
		array(
			'idx' => BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_FORM,
			'mnuStr' => $tab_caption,
			'url' => $GLOBALS['babUrlScript'] . '?tg=admTskMgr&idx=' . BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_FORM)		
		);
		
	add_item_menu($itemMenu);
	$babBody->title = $tab_caption;

	$dwh = new BAB_ProjectSpace($iIdProjectSpace, $iIdDelegation);
	$babBody->babecho(bab_printTemplate($dwh, 'tmAdmin.html', 'projectSpaceForm'));
}


function displayDeleteProjectsSpacesForm()
{
	global $babBody;

	$oTmCtx =& getTskMgrContext();
	
	$iIdProjectSpace = $oTmCtx->getIdProjectSpace();
	$iIdProject = $oTmCtx->getIdProject();
	$iIdDelegation = $oTmCtx->getIdDelegation();

	if(0 != $iIdProjectSpace)
	{
		$bf = & new BAB_BaseFormProcessing();
		$bf->set_data('iIdProjectSpace', $iIdProjectSpace);
		$bf->set_data('iIdProject', $iIdProject);
		$bf->set_data('tg', 'admTskMgr');
		$bf->set_data('idx', BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_LIST);
		$bf->set_caption('yes', bab_translate("Yes"));
		$bf->set_caption('no', bab_translate("No"));

		$bf->set_data('action', '');
		$bf->set_caption('title', '');
		$bf->set_caption('warning', '');
		$bf->set_caption('message', '');

		$aProjectSpace = null;
		$bSuccess = bab_getProjectSpace($iIdProjectSpace, $aProjectSpace);
		if($bSuccess)
		{
			$bf->set_caption('title', bab_translate("Project space = ") . htmlentities($aProjectSpace['name'], ENT_QUOTES));

			$bSuccess = bab_isProjectSpaceDeletable($iIdProjectSpace);
			if($bSuccess)	
			{
				$bf->set_data('action', BAB_TM_ACTION_DELETE_PROJECT_SPACE);
				$bf->set_caption('warning', bab_translate("This action will delete the project space and all references"));
				$bf->set_caption('message', bab_translate("Continue ?"));
			}
			else
			{
				$bf->set_caption('warning', bab_translate("You cannot delete this project space before is not empty"));
			}
		}
		else 
		{
			$bf->set_caption('warning', bab_translate("Cannot retreive project space information"));
		}
		
		$babBody->title = bab_translate("Delete project space");
		$babBody->babecho(bab_printTemplate($bf, 'tmCommon.html', 'warningyesno'));
	}
	else 
	{
		$GLOBALS['babBody']->msgerror = bab_translate("Invalid project space");
	}
}


function displayProjectsSpacesRightsForm()
{
	global $babBody;

	$oTmCtx =& getTskMgrContext();
	
	$iIdProjectSpace = $oTmCtx->getIdProjectSpace();
	$iIdDelegation = $oTmCtx->getIdDelegation();

	if(0 != $iIdProjectSpace)
	{
		$itemMenu = array(
			array(
				'idx' => BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_LIST,
				'mnuStr' => bab_translate("Projects spaces"),
				'url' => $GLOBALS['babUrlScript'] . '?tg=admTskMgr&idx=' . BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_LIST),
			array(
				'idx' => BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_RIGHTS_FORM,
				'mnuStr' => bab_translate("Projects space rights"),
				'url' => $GLOBALS['babUrlScript'] . '?tg=admTskMgr&idx=' . BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_RIGHTS_FORM)
			);
	
		add_item_menu($itemMenu);
		$babBody->title = bab_translate("Projects spaces rights");
		
		$enableGroup	= 0;
		$disableGroup	= 1;
	
		require_once($GLOBALS['babInstallPath'] . 'admin/acl.php');
	
		$macl = new macl('admTskMgr', BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_LIST, $iIdProjectSpace, BAB_TM_ACTION_SET_RIGHT);
	
		$macl->addtable(BAB_TSKMGR_PROJECT_CREATOR_GROUPS_TBL, bab_translate("Default project creators"));
		$macl->filter($enableGroup, $enableGroup, $disableGroup, $enableGroup, $disableGroup);
		$macl->addtable(BAB_TSKMGR_PERSONNAL_TASK_CREATOR_GROUPS_TBL, bab_translate("Default personnal task owner"));
		$macl->filter($enableGroup, $enableGroup, $disableGroup, $enableGroup, $disableGroup);
		$macl->addtable(BAB_TSKMGR_DEFAULT_PROJECTS_MANAGERS_GROUPS_TBL, bab_translate("Default project manager"));
		$macl->filter($enableGroup, $enableGroup, $disableGroup, $enableGroup, $disableGroup);
		$macl->addtable(BAB_TSKMGR_DEFAULT_PROJECTS_SUPERVISORS_GROUPS_TBL, bab_translate("Default project supervisor"));
		$macl->filter($enableGroup, $enableGroup, $disableGroup, $enableGroup, $disableGroup);
		$macl->addtable(BAB_TSKMGR_DEFAULT_PROJECTS_VISUALIZERS_GROUPS_TBL, bab_translate("Default project visualizer"));
		$macl->filter($enableGroup, $enableGroup, $disableGroup, $enableGroup, $disableGroup);
		$macl->addtable(BAB_TSKMGR_DEFAULT_TASK_RESPONSIBLE_GROUPS_TBL, bab_translate("Default task responsible"));
		$macl->filter($enableGroup, $enableGroup, $disableGroup, $enableGroup, $disableGroup);
	
		$macl->babecho();
	}
	else 
	{
		$GLOBALS['babBody']->msgerror = bab_translate("Invalid project space");
	}
}


function displayProjectsConfigurationForm()
{
	global $babBody;

	$oTmCtx =& getTskMgrContext();
	
	$iIdProjectSpace = $oTmCtx->getIdProjectSpace();
	$iIdProject = $oTmCtx->getIdProject();

	if(0 != $iIdProjectSpace)
	{
		class BAB_TM_Configuration extends BAB_BaseFormProcessing
		{
			function BAB_TM_Configuration($iIdProjectSpace, $iIdProject)
			{
				parent::BAB_BaseFormProcessing();
				
				$this->set_caption('taskUpdate', bab_translate("Task updated by task responsible"));
				$this->set_caption('notice', bab_translate("Reminder before project expiration"));
				$this->set_caption('taskNumerotation', bab_translate("Task numerotation"));
				$this->set_caption('emailNotice', bab_translate("Email notification"));
				$this->set_caption('faq', bab_translate("Task manager FAQ"));
				$this->set_caption('days', bab_translate("Day(s)"));
			
				$this->set_caption('yes', bab_translate("Yes"));
				$this->set_caption('no', bab_translate("No"));
				$this->set_caption('save', bab_translate("Save"));
	
				$this->set_data('aTaskNumerotation', array(
					BAB_TM_MANUAL => bab_translate("Manual"), BAB_TM_SEQUENTIAL => bab_translate("Sequential (automatique)"),
					BAB_TM_YEAR_SEQUENTIAL => bab_translate("Year + Sequential (automatique)"),
					BAB_TM_YEAR_MONTH_SEQUENTIAL => bab_translate("Year + Month + Sequential (automatique)")));
					
				$this->set_data('yes', BAB_TM_YES);
				$this->set_data('no', BAB_TM_NO);
				$this->set_data('tg', 'admTskMgr');
				$this->set_data('save_idx', BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_LIST);
				$this->set_data('save_action', BAB_TM_ACTION_SAVE_PROJECTS_CONFIGURATION);
				
				$this->set_data('tmCode', '');
				$this->set_data('tmValue', '');
				$this->set_data('tnSelected', '');
				
				$this->set_data('iIdProjectSpace', $iIdProjectSpace);
				$this->set_data('iIdProject', $iIdProject);
				$this->set_data('isTaskUpdatedByMgr', true);
				$this->set_data('endTaskReminder', 5);
				$this->set_data('taskNumerotation', BAB_TM_SEQUENTIAL);
				$this->set_data('isEmailNotice', true);
				$this->set_data('faqUrl', '');
				$this->set_data('iIdConfiguration', -1);
				
				$oTmCtx =& getTskMgrContext();
				$aDPC = $oTmCtx->getConfiguration();
				
				if(null != $aDPC)
				{
					$this->set_data('isTaskUpdatedByMgr', (BAB_TM_YES == $aDPC['tskUpdateByMgr']));
					$this->set_data('endTaskReminder', $aDPC['endTaskReminder']);
					$this->set_data('taskNumerotation', $aDPC['tasksNumerotation']);
					$this->set_data('isEmailNotice', (BAB_TM_YES == $aDPC['emailNotice']));
					$this->set_data('faqUrl', htmlentities($aDPC['faqUrl']));
					$this->set_data('iIdConfiguration', $aDPC['id']);
				}
				$this->set_data('isProjectEmpty', true);
			}
			
			function getNextTaskNumerotation()
			{
				$this->get_data('taskNumerotation', $taskNumerotation);
				$this->set_data('tnSelected', '');
				
				$datas = each($this->m_datas['aTaskNumerotation']);
				if(false != $datas)
				{
					$this->set_data('tmCode', $datas['key']);
					$this->set_data('tmValue', $datas['value']);
					
					if($taskNumerotation == $datas['key'])
					{
						$this->set_data('tnSelected', 'selected="selected"');
					}
					
					return true;
				}
				else
				{
					reset($this->m_datas['aTaskNumerotation']);
					return false;
				}
			}
		}
		
		$itemMenu = array(
			array(
				'idx' => BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_LIST,
				'mnuStr' => bab_translate("Projects spaces"),
				'url' => $GLOBALS['babUrlScript'] . '?tg=admTskMgr&idx=' . BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_LIST),
			array(
				'idx' => BAB_TM_IDX_DISPLAY_PROJECTS_CONFIGURATION_FORM,
				'mnuStr' => bab_translate("Default projects configuration"),
				'url' => $GLOBALS['babUrlScript'] . '?tg=admTskMgr&iIdProjectSpace=' . $iIdProjectSpace . '&idx=' . BAB_TM_IDX_DISPLAY_PROJECTS_CONFIGURATION_FORM)
			);
			
		add_item_menu($itemMenu);
		$babBody->title = bab_translate("Default projects configuration");
	
		$pjc = & new BAB_TM_Configuration($iIdProjectSpace, $iIdProject);
		
		
		$babBody->babecho(bab_printTemplate($pjc, 'tmCommon.html', 'configuration'));
	}
	else 
	{
		$GLOBALS['babBody']->msgerror = bab_translate("Invalid project space");
	}
}


// POST
function addModifyProjectSpace()
{
	$oTmCtx =& getTskMgrContext();
	$iIdProjectSpace = $oTmCtx->getIdProjectSpace();
	$iIdDelegation = $oTmCtx->getIdDelegation();

	
	$sName = mysql_escape_string(tskmgr_getVariable('sName', ''));
	$sDescription = mysql_escape_string(tskmgr_getVariable('sDescription', ''));
	
	if(strlen(trim($sName)) > 0)
	{
		$id = bab_isProjectSpaceExist($iIdDelegation, $sName);
		
		if(false == $id)
		{
			$iIdProjectSpace = bab_createProjectSpace($iIdDelegation, $sName, $sDescription);
			if(false != $iIdProjectSpace)
			{
				bab_createDefaultProjectSpaceConfiguration($iIdProjectSpace);
				bab_createDefaultProjectSpaceNoticeEvent($iIdProjectSpace);
			}
		}
		else if($id == $iIdProjectSpace)
		{
			bab_updateProjectSpace($iIdProjectSpace, $sName, $sDescription);
		}
		else
		{
			$GLOBALS['babBody']->msgerror = bab_translate("A project space with the name '") . $sName . bab_translate("' already exist");
			$_POST['idx'] = BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_FORM;
		}
	}
	else
	{
		$GLOBALS['babBody']->msgerror = bab_translate("The name field must not be blank");
		$_POST['idx'] = BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_FORM;
	}
}


function deleteProjectSpace()
{
	global $babBody;

	$oTmCtx =& getTskMgrContext();
	
	$iIdProjectSpace = $oTmCtx->getIdProjectSpace();
	$iIdDelegation = $oTmCtx->getIdDelegation();

	if(0 != $iIdProjectSpace)
	{
		if(bab_isProjectSpaceDeletable($iIdProjectSpace))
		{
			bab_deleteProjectSpace($iIdProjectSpace);
		}
		else
		{
			$GLOBALS['babBody']->msgerror = bab_translate("Cannot delete the project because there is some reference on it");
		}
	}
	else 
	{
		$GLOBALS['babBody']->msgerror = bab_translate("Invalid project space");
	}
}


function saveProjectConfiguration()
{
	$oTmCtx =& getTskMgrContext();
	$iIdProjectSpace = $oTmCtx->getIdProjectSpace();
	
	$iTaskUpdateByMgr = (int) tskmgr_getVariable('iTaskUpdateByMgr', BAB_TM_YES);
	$iIdConfiguration = (int) tskmgr_getVariable('iIdConfiguration', 0);
	$iEndTaskReminder = (int) tskmgr_getVariable('iEndTaskReminder', 5);
	$iTaskNumerotation = (int) tskmgr_getVariable('iTaskNumerotation', BAB_TM_SEQUENTIAL);
	$iEmailNotice = (int) tskmgr_getVariable('iEmailNotice', BAB_TM_YES);
	$sFaqUrl = mysql_escape_string(tskmgr_getVariable('sFaqUrl', ''));

	if(0 < $iIdConfiguration && 0 < $iIdProjectSpace)
	{
		$aConfiguration = array(
			'id' => $iIdConfiguration,
			'idProjectSpace' => $iIdProjectSpace,
			'tskUpdateByMgr' => $iTaskUpdateByMgr,
			'endTaskReminder' => $iEndTaskReminder,
			'tasksNumerotation' => $iTaskNumerotation,
			'emailNotice' => $iEmailNotice,
			'faqUrl' => $sFaqUrl);

		bab_updateDefaultProjectSpaceConfiguration($aConfiguration);
	}
}


bab_cleanGpc();


//*
require_once($babInstallPath . 'upgrade.php');
upgradeXXXtoYYY();
//*/

/*
global $babBody;
if(!($babBody->isSuperAdmin && $babBody->currentAdmGroup == 0) && $babBody->currentDGGroup['taskmanager'] !== 'Y')
{
	$babBody->msgerror = bab_translate("Access denied");
	return;
}
//*/


/* main */
$action = isset($_POST['action']) ? $_POST['action'] : 
	(isset($_GET['action']) ? $_GET['action'] :  
	(isset($_POST[BAB_TM_ACTION_SET_RIGHT]) ? BAB_TM_ACTION_SET_RIGHT : '???')
	);

//bab_debug('action ==> ' . $action);

switch($action)
{
	case BAB_TM_ACTION_ADD_PROJECT_SPACE:
	case BAB_TM_ACTION_MODIFY_PROJECT_SPACE:
		addModifyProjectSpace();
		break;
		
	case BAB_TM_ACTION_DELETE_PROJECT_SPACE:
		deleteProjectSpace();
		break;

	case BAB_TM_ACTION_SET_RIGHT:
		require_once($GLOBALS['babInstallPath'] . 'admin/acl.php');
		maclGroups();
		break;
		
	case BAB_TM_ACTION_SAVE_PROJECTS_CONFIGURATION:
		saveProjectConfiguration();
		break;
		
	case BAB_TM_ACTION_ADD_OPTION:
		addOption();
		break;
		
	case BAB_TM_ACTION_DEL_OPTION:
		delOption();
		break;
		
	case BAB_TM_ACTION_ADD_SPECIFIC_FIELD:
	case BAB_TM_ACTION_MODIFY_SPECIFIC_FIELD:
		addModifySpecificField();
		break;
		
	case BAB_TM_ACTION_DELETE_SPECIFIC_FIELD:
		deleteSpecificField();
		break;
		
	case BAB_TM_ACTION_ADD_CATEGORY:
	case BAB_TM_ACTION_MODIFY_CATEGORY:
		addModifyCategory();
		break;
		
	case BAB_TM_ACTION_DELETE_CATEGORY:
		deleteCategory();
		break;
		
	case BAB_TM_ACTION_UPDATE_WORKING_HOURS:
		require_once($GLOBALS['babInstallPath'] . 'tmWorkingHoursFunc.php');
		updateWorkingHours();
		break;
		
	case BAB_TM_ACTION_MODIFY_NOTICE_EVENT:
		require_once($GLOBALS['babInstallPath'] . 'tmNoticesFunc.php');
		modifyNoticeEvent();
		break;
}


$idx = isset($_POST['idx']) ? $_POST['idx'] : (isset($_GET['idx']) ? $_GET['idx'] : BAB_TM_IDX_DISPLAY_MENU);

//bab_debug('idx ==> ' . $idx);

switch($idx)
{
	case BAB_TM_IDX_DISPLAY_MENU:
		displayMenu();
		break;

	case BAB_TM_IDX_DISPLAY_WORKING_HOURS_FORM:
		require_once($GLOBALS['babInstallPath'] . 'tmWorkingHoursFunc.php');
		displayWorkingHoursForm();
		break;
		
	case BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_LIST:
		displayProjectsSpacesList();
		break;
		
	case BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_FORM:
		displayProjectsSpacesForm();
		break;
		
	case BAB_TM_IDX_DISPLAY_DELETE_PROJECTS_SPACES_FORM:
		displayDeleteProjectsSpacesForm();
		break;
		
	case BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_RIGHTS_FORM:
		displayProjectsSpacesRightsForm();
		break;

	case BAB_TM_IDX_DISPLAY_PROJECTS_CONFIGURATION_FORM:
		displayProjectsConfigurationForm();
		break;
		
	case BAB_TM_IDX_DISPLAY_SPECIFIC_FIELD_LIST:
		displaySpecificFieldList();
		break;
		
	case BAB_TM_IDX_DISPLAY_SPECIFIC_FIELD_FORM:
		displaySpecificFieldForm();
		break;
		
	case BAB_TM_IDX_DISPLAY_DELETE_SPECIFIC_FIELD_FORM:
		displayDeleteSpecificFieldForm();
		break;

	case BAB_TM_IDX_DISPLAY_CATEGORIES_LIST:
		displayCategoriesList();
		break;
		
	case BAB_TM_IDX_DISPLAY_CATEGORY_FORM:
		displayCategoryForm();
		break;
		
	case BAB_TM_IDX_DISPLAY_DELETE_CATEGORY_FORM:
		displayDeleteCategoryForm();
		break;

	case BAB_TM_IDX_DISPLAY_NOTICE_EVENT_FORM:
		require_once($GLOBALS['babInstallPath'] . 'tmNoticesFunc.php');
		displayNoticeEventForm();
		break;
}
$babBody->setCurrentItemMenu($idx);
?>