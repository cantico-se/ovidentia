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
require_once($babInstallPath . 'utilit/tmToolsIncl.php');
require_once($babInstallPath . 'utilit/tmList.php');
require_once($babInstallPath . 'tmCategoriesFunc.php');

require_once($babInstallPath . 'utilit/baseFormProcessingClass.php');
require_once($babInstallPath . 'tmContext.php');


function displayMenu()
{
	global $babBody;
	
	$bfp = & new BAB_BaseFormProcessing();
	$bfp->set_data('dummy', 'dummy');

	$babBody->title = bab_translate("Task Manager");
	
	$itemMenu = array();
	add_item_menu($itemMenu);

	$bfp->set_anchor($GLOBALS['babUrlScript'] . '?tg=usrTskMgr&idx=' . BAB_TM_IDX_DISPLAY_WORKING_HOURS_FORM , '', 'Working hours');
	$bfp->set_anchor($GLOBALS['babUrlScript'] . '?tg=usrTskMgr&idx=' . BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_LIST , '', 'Projects space');

	$babBody->babecho(bab_printTemplate($bfp, 'tmCommon.html', 'displayMenu'));
}

function displayProjectsSpacesList()
{
	global $babBody, $babDB;

	$babBody->title = bab_translate("Projects spaces list");

	$itemMenu = array(		
		array(
			'idx' => BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_LIST,
			'mnuStr' => bab_translate("Projects spaces"),
			'url' => $GLOBALS['babUrlScript'] . '?tg=usrTskMgr&idx=' . BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_LIST),
	);
	add_item_menu($itemMenu);
	
	$oTmCtx =& getTskMgrContext();
	$query = 
		'SELECT ' .
			'id, ' . 
			'name, ' . 
			'description ' .
		'FROM ' .
			BAB_TSKMGR_PROJECTS_SPACES_TBL . ' ' .
		'WHERE ' . 
			'id IN(\'' . implode('\',\'', array_keys($oTmCtx->getVisualisedIdProjectSpace())) . '\')';

	$result = $babDB->db_query($query);
			
	$list = new BAB_TM_ListBase($result);
	
	$list->set_data('url', $GLOBALS['babUrlScript'] . '?tg=usrTskMgr&idx=' . 
		BAB_TM_IDX_DISPLAY_PROJECTS_LIST . '&iIdProjectSpace=');

	$babBody->babecho(bab_printTemplate($list, 'tmCommon.html', 'displayList'));	
}

function displayProjectsList()
{
	global $babBody;
	$oTmCtx =& getTskMgrContext();
	$iIdProjectSpace = $oTmCtx->getIdProjectSpace();
	
	$babBody->title = bab_translate("Projects list");

	$itemMenu = array(		
		array(
			'idx' => BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_LIST,
			'mnuStr' => bab_translate("Projects spaces"),
			'url' => $GLOBALS['babUrlScript'] . '?tg=usrTskMgr&idx=' . BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_LIST),
		array(
			'idx' => BAB_TM_IDX_DISPLAY_PROJECTS_LIST,
			'mnuStr' => bab_translate("Projects list"),
			'url' => $GLOBALS['babUrlScript'] . '?tg=usrTskMgr&idx=' . BAB_TM_IDX_DISPLAY_PROJECTS_LIST . 
			'&iIdProjectSpace=' . $iIdProjectSpace)
	);

	if(bab_isAccessValid(BAB_TSKMGR_PROJECT_CREATOR_GROUPS_TBL, $iIdProjectSpace))
	{
		$itemMenu[] = array(
			'idx' => BAB_TM_IDX_DISPLAY_PROJECT_FORM,
			'mnuStr' => bab_translate("Add a project"),
			'url' => $GLOBALS['babUrlScript'] . '?tg=usrTskMgr&idx=' . BAB_TM_IDX_DISPLAY_PROJECT_FORM . 
			'&iIdProjectSpace=' . $iIdProjectSpace);
	}
	
	add_item_menu($itemMenu);

	class BAB_TM_ProjectList extends BAB_TM_ListBase
	{
		function BAB_TM_ProjectList()
		{
			parent::BAB_TM_ListBase();
		}
	
		function init()
		{
			$this->set_caption('rights', bab_translate("Rights"));
			$this->set_caption('configuration', bab_translate("Configuration"));
			$this->set_caption('category', bab_translate("Categories list"));
			$this->set_caption('commentary', bab_translate("Display project commentaries list"));
			$this->set_caption('task', bab_translate("Display tasks list"));

			$oTmCtx =& getTskMgrContext();
			$iIdProjectSpace = $oTmCtx->getIdProjectSpace();

			$this->set_data('iIdProjectSpace', $iIdProjectSpace);
			$this->set_data('bIsProjectCreator', 
				bab_isAccessValid(BAB_TSKMGR_PROJECT_CREATOR_GROUPS_TBL, $iIdProjectSpace));
				
			$this->set_data('rightsUrl', '#');
			$this->set_data('configurationUrl', '#');
			$this->set_data('categoryUrl', '#');
			$this->set_data('commentaryUrl', '#');
			$this->set_data('taskUrl', '#');
			$this->m_result = bab_selectProjectList($iIdProjectSpace);
		}
		
		function nextItem()
		{
			if(false != parent::nextItem())
			{
				$this->get_data('iIdProjectSpace', $iIdProjectSpace);
				$this->get_data('bIsProjectCreator', $bIsProjectCreator);
				$bIsManager = bab_isAccessValid(BAB_TSKMGR_PROJECTS_MANAGERS_GROUPS_TBL, $this->m_rowDatas['id']);
				$this->set_data('bIsManager', $bIsManager);
				$this->set_data('bIsRightUrl', ($bIsProjectCreator || $bIsManager));
				
				$this->set_data('rightsUrl', $GLOBALS['babUrlScript'] . '?tg=usrTskMgr&idx=' . 
					BAB_TM_IDX_DISPLAY_PROJECT_RIGHTS_FORM . '&iIdProjectSpace=' . $iIdProjectSpace . '&iIdProject=' . $this->m_rowDatas['id']
					);
					
				$this->set_data('configurationUrl', $GLOBALS['babUrlScript'] . '?tg=usrTskMgr&idx=' .
					BAB_TM_IDX_DISPLAY_PROJECTS_CONFIGURATION_FORM . '&iIdProjectSpace=' . $iIdProjectSpace . '&iIdProject=' . $this->m_rowDatas['id']
					);

				$this->set_data('categoryUrl', $GLOBALS['babUrlScript'] . '?tg=usrTskMgr&idx=' . 
					BAB_TM_IDX_DISPLAY_CATEGORIES_LIST . '&iIdProjectSpace=' . $iIdProjectSpace . '&iIdProject=' . $this->m_rowDatas['id']
					);
					
				$this->set_data('commentaryUrl', $GLOBALS['babUrlScript'] . '?tg=usrTskMgr&idx=' .
					BAB_TM_IDX_DISPLAY_PROJECT_COMMENTARY_LIST . '&iIdProjectSpace=' . $iIdProjectSpace . '&iIdProject=' . $this->m_rowDatas['id']
					);

				$this->set_data('taskUrl', $GLOBALS['babUrlScript'] . '?tg=usrTskMgr&idx=' .
					BAB_TM_IDX_DISPLAY_TASK_LIST . '&iIdProjectSpace=' . $iIdProjectSpace . '&iIdProject=' . $this->m_rowDatas['id']
					);	
				
				return true;
			}
			return false;
		}
	}
	
	$list = new BAB_TM_ProjectList();
	
	$list->set_data('url', $GLOBALS['babUrlScript'] . '?tg=usrTskMgr&idx=' . 
		BAB_TM_IDX_DISPLAY_PROJECT_FORM . '&iIdProjectSpace=' . $iIdProjectSpace . '&iIdProject=');
		
	$babBody->babecho(bab_printTemplate($list, 'tmUser.html', 'displayProjectList'));	
}

function displayProjectForm()
{
	global $babBody;

	$oTmCtx =& getTskMgrContext();
	$iIdProjectSpace = $oTmCtx->getIdProjectSpace();
	$iIdProject = $oTmCtx->getIdProject();
	
	//bab_debug('iIdProjectSpace ==> ' . $iIdProjectSpace);
	//bab_debug('iIdProject ==> ' . $iIdProject);
	
	if(bab_isAccessValid(BAB_TSKMGR_PROJECT_CREATOR_GROUPS_TBL, $iIdProjectSpace))
	{
		class BAB_Project extends BAB_BaseFormProcessing
		{
			function BAB_Project($iIdProjectSpace, $iIdProject)
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
				$this->set_data('iIdProject', $iIdProject);
				$this->set_data('bIsDeletable', false);
				
				$this->set_data('add_idx', BAB_TM_IDX_DISPLAY_PROJECTS_LIST);
				$this->set_data('modify_idx', BAB_TM_IDX_DISPLAY_PROJECTS_LIST);
				$this->set_data('delete_idx', BAB_TM_IDX_DISPLAY_DELETE_PROJECT_FORM);
				$this->set_data('add_action', BAB_TM_ACTION_ADD_PROJECT);
				$this->set_data('modify_action', BAB_TM_ACTION_MODIFY_PROJECT);
				$this->set_data('delete_action', '');
				
				$this->set_data('tg', 'usrTskMgr');
				
				
				if(!isset($_POST['iIdProject']) && !isset($_GET['iIdProject']))
				{
					$this->set_data('is_creation', true);
				}
				else if( (isset($_GET['iIdProject']) || isset($_POST['iIdProject'])) && 0 != $iIdProject)
				{
					$this->set_data('is_edition', true);
					
					$aProject = null;
					$bSuccess = bab_getProject($iIdProject, $aProject);
					
					if(false != $bSuccess)
					{
						$this->set_data('sName', htmlentities($aProject['name'], ENT_QUOTES) );
						$this->set_data('sDescription', htmlentities($aProject['description'], ENT_QUOTES));
					}
					
					$this->set_data('bIsDeletable', bab_isProjectDeletable($iIdProject));
				}
				else
				{
					$this->set_data('is_resubmission', true);
				}
			}
		}
		
		$tab_caption = ($iIdProject == 0) ? bab_translate("Add a project") : bab_translate("Edition of a project");
		
		$itemMenu = array(
			array(
				'idx' => BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_LIST,
				'mnuStr' => bab_translate("Projects spaces"),
				'url' => $GLOBALS['babUrlScript'] . '?tg=usrTskMgr&idx=' . BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_LIST),
			array(
				'idx' => BAB_TM_IDX_DISPLAY_PROJECTS_LIST,
				'mnuStr' => bab_translate("Projects list"),
				'url' => $GLOBALS['babUrlScript'] . '?tg=usrTskMgr&idx=' . BAB_TM_IDX_DISPLAY_PROJECTS_LIST . 
				'&iIdProjectSpace=' . $iIdProjectSpace),
			array(
				'idx' => BAB_TM_IDX_DISPLAY_PROJECT_FORM,
				'mnuStr' => bab_translate("Add a project"),
				'url' => $GLOBALS['babUrlScript'] . '?tg=usrTskMgr&idx=' . BAB_TM_IDX_DISPLAY_PROJECT_FORM . 
				'&iIdProjectSpace=' . $iIdProjectSpace)
			);
			
		add_item_menu($itemMenu);

		$babBody->title = $tab_caption;
	
		$oProject = new BAB_Project($iIdProjectSpace, $iIdProject);
		$babBody->babecho(bab_printTemplate($oProject, 'tmUser.html', 'projectForm'));		
	}
	else
	{
		$GLOBALS['babBody']->msgerror = bab_translate("You do not have the right to create/modify a project");
	}
}

function displayDeleteProjectForm()
{
	global $babBody;

	$oTmCtx =& getTskMgrContext();
	$iIdProjectSpace = $oTmCtx->getIdProjectSpace();
	$iIdProject = $oTmCtx->getIdProject();

	if(bab_isAccessValid(BAB_TSKMGR_PROJECT_CREATOR_GROUPS_TBL, $iIdProjectSpace))
	{
		if(bab_isProjectDeletable($iIdProject))
		{
			$aProject = null;
			$bSuccess = bab_getProject($iIdProject, $aProject);
	
			if(false !== $bSuccess)
			{
				$bf = & new BAB_BaseFormProcessing();
				$bf->set_data('idx', BAB_TM_IDX_DISPLAY_PROJECTS_LIST);
				$bf->set_data('action', BAB_TM_ACTION_DELETE_PROJECT);
				$bf->set_data('iIdProjectSpace', $iIdProjectSpace);
				$bf->set_data('iIdProject', $iIdProject);
				$bf->set_data('tg', 'usrTskMgr');
	
				$bf->set_caption('warning', bab_translate("This action will delete the project and all references"));
				$bf->set_caption('message', bab_translate("Continue ?"));
				$bf->set_caption('title', bab_translate("Project = ") . htmlentities($aProject['name'], ENT_QUOTES));
				$bf->set_caption('yes', bab_translate("Yes"));
				$bf->set_caption('no', bab_translate("No"));
	
				$babBody->title = bab_translate("Delete project");
				$babBody->babecho(bab_printTemplate($bf, 'tmCommon.html', 'warningyesno'));
			}
		}
	}
	else 
	{
		$GLOBALS['babBody']->msgerror = bab_translate("You do not have the right to delete a project");
	}	
}

function displayProjectRightsForm()
{
	global $babBody;

	$oTmCtx =& getTskMgrContext();
	$iIdProjectSpace = $oTmCtx->getIdProjectSpace();
	$iIdProject = $oTmCtx->getIdProject();
	
	//bab_debug('iIdProjectSpace ==> ' . $iIdProjectSpace);
	//bab_debug('iIdProject ==> ' . $iIdProject);
	
	$bIsCreator = bab_isAccessValid(BAB_TSKMGR_PROJECT_CREATOR_GROUPS_TBL, $iIdProjectSpace);
	$bIsManager = bab_isAccessValid(BAB_TSKMGR_PROJECTS_MANAGERS_GROUPS_TBL, $iIdProject);
	
	$isAccessValid = ($bIsCreator || $bIsManager);
	
	if($isAccessValid)
	{
		$itemMenu = array(
			array(
				'idx' => BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_LIST,
				'mnuStr' => bab_translate("Projects spaces"),
				'url' => $GLOBALS['babUrlScript'] . '?tg=usrTskMgr&idx=' . BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_LIST),
			array(
				'idx' => BAB_TM_IDX_DISPLAY_PROJECTS_LIST,
				'mnuStr' => bab_translate("Projects list"),
				'url' => $GLOBALS['babUrlScript'] . '?tg=usrTskMgr&idx=' . BAB_TM_IDX_DISPLAY_PROJECTS_LIST . 
				'&iIdProjectSpace=' . $iIdProjectSpace),
			array(
				'idx' => BAB_TM_IDX_DISPLAY_PROJECT_RIGHTS_FORM,
				'mnuStr' => bab_translate("Project rights"),
				'url' => $GLOBALS['babUrlScript'] . '?tg=usrTskMgr&idx=' . BAB_TM_IDX_DISPLAY_PROJECT_RIGHTS_FORM . 
				'&iIdProjectSpace=' . $iIdProjectSpace .'&iIdProject=' . $iIdProject)
			);
	
		add_item_menu($itemMenu);
		$babBody->title = bab_translate("Projects rights");
		
		$enableGroup	= 0;
		$disableGroup	= 1;
	
		require_once($GLOBALS['babInstallPath'] . 'admin/acl.php');
	
		
		$macl = new macl('usrTskMgr', BAB_TM_IDX_DISPLAY_PROJECTS_LIST, $iIdProject, BAB_TM_ACTION_SET_RIGHT);
		$macl->set_hidden_field('iIdProjectSpace', $iIdProjectSpace);
		$macl->set_hidden_field('iIdProject', $iIdProject);
		
		//if($bIsCreator)
		{
			$macl->addtable(BAB_TSKMGR_PROJECTS_MANAGERS_GROUPS_TBL, bab_translate("Project manager"));
			$macl->filter($enableGroup, $enableGroup, $disableGroup, $enableGroup, $disableGroup);
		}
		
		if($bIsManager)
		{
			$macl->addtable(BAB_TSKMGR_PROJECTS_SUPERVISORS_GROUPS_TBL, bab_translate("Project supervisor"));
			$macl->filter($enableGroup, $enableGroup, $disableGroup, $enableGroup, $disableGroup);
			$macl->addtable(BAB_TSKMGR_PROJECTS_VISUALIZERS_GROUPS_TBL, bab_translate("Project visualizer"));
			$macl->filter($enableGroup, $enableGroup, $disableGroup, $enableGroup, $disableGroup);
			$macl->addtable(BAB_TSKMGR_TASK_RESPONSIBLE_GROUPS_TBL, bab_translate("Task responsible"));
			$macl->filter($enableGroup, $enableGroup, $disableGroup, $enableGroup, $disableGroup);
		}
	
		$macl->babecho();
	}
	else
	{
		
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
				$this->set_data('tg', 'usrTskMgr');
				$this->set_data('save_idx', BAB_TM_IDX_DISPLAY_PROJECTS_LIST);
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
					$this->set_data('iIdConfiguration', $aDPC['id']);
					$this->set_data('isTaskUpdatedByMgr', (BAB_TM_YES == $aDPC['tskUpdateByMgr']));
					$this->set_data('endTaskReminder', $aDPC['endTaskReminder']);
					$this->set_data('taskNumerotation', $aDPC['tasksNumerotation']);
					$this->set_data('isEmailNotice', (BAB_TM_YES == $aDPC['emailNotice']));
					$this->set_data('faqUrl', htmlentities($aDPC['faqUrl']));
					$this->set_data('iIdConfiguration', $aDPC['id']);
				}
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
				'url' => $GLOBALS['babUrlScript'] . '?tg=usrTskMgr&idx=' . BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_LIST),
			array(
				'idx' => BAB_TM_IDX_DISPLAY_PROJECTS_CONFIGURATION_FORM,
				'mnuStr' => bab_translate("Projects configuration"),
				'url' => $GLOBALS['babUrlScript'] . '?tg=usrTskMgr&idx=' . BAB_TM_IDX_DISPLAY_PROJECTS_CONFIGURATION_FORM . 
				'&iIdProjectSpace=' . $iIdProjectSpace . '&iIdProject=' . $iIdProject)
		);
		add_item_menu($itemMenu);

		$babBody->title = bab_translate("Projects configuration");
	
		$pjc = & new BAB_TM_Configuration($iIdProjectSpace, $iIdProject);
		
		
		$babBody->babecho(bab_printTemplate($pjc, 'tmCommon.html', 'configuration'));
	}
	else 
	{
		$GLOBALS['babBody']->msgerror = bab_translate("Invalid project space");
	}
	
}

function displayProjectCommentaryList()
{
	global $babBody;

	$oTmCtx =& getTskMgrContext();
	$iIdProjectSpace = $oTmCtx->getIdProjectSpace();
	$iIdProject = $oTmCtx->getIdProject();
	
	if(bab_isAccessValid(BAB_TSKMGR_PROJECTS_MANAGERS_GROUPS_TBL, $iIdProject))
	{
		$babBody->title = bab_translate("Commentaries list");
	
		$itemMenu = array(		
			array(
				'idx' => BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_LIST,
				'mnuStr' => bab_translate("Projects spaces"),
				'url' => $GLOBALS['babUrlScript'] . '?tg=usrTskMgr&idx=' . BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_LIST),
			array(
				'idx' => BAB_TM_IDX_DISPLAY_PROJECTS_LIST,
				'mnuStr' => bab_translate("Projects list"),
				'url' => $GLOBALS['babUrlScript'] . '?tg=usrTskMgr&idx=' . BAB_TM_IDX_DISPLAY_PROJECTS_LIST . 
				'&iIdProjectSpace=' . $iIdProjectSpace),
			array(
				'idx' => BAB_TM_IDX_DISPLAY_PROJECT_COMMENTARY_LIST,
				'mnuStr' => bab_translate("Commentaries list"),
				'url' => $GLOBALS['babUrlScript'] . '?tg=usrTskMgr&idx=' . BAB_TM_IDX_DISPLAY_PROJECT_COMMENTARY_LIST . 
				'&iIdProject=' . $iIdProject),
			array(
				'idx' => BAB_TM_IDX_DISPLAY_COMMENTARY_FORM,
				'mnuStr' => bab_translate("Add a commentary"),
				'url' => $GLOBALS['babUrlScript'] . '?tg=usrTskMgr&idx=' . BAB_TM_IDX_DISPLAY_COMMENTARY_FORM . 
				'&iIdProjectSpace=' . $iIdProjectSpace . '&iIdProject=' . $iIdProject)
		);
		
		add_item_menu($itemMenu);
		
		$result = tmSelectProjectCommentary($iIdProject);	
		$oList = new BAB_TM_ListBase($result);
	
		$oList->set_data('url', $GLOBALS['babUrlScript'] . '?tg=usrTskMgr&idx=' . 
			BAB_TM_IDX_DISPLAY_COMMENTARY_FORM . '&iIdProjectSpace=' . $iIdProjectSpace .
			'&iIdProject=' . $iIdProject . '&iIdCommentary=');

		$babBody->babecho(bab_printTemplate($oList, 'tmUser.html', 'commentariesList'));
	}
	else 
	{
		$GLOBALS['babBody']->msgerror = bab_translate("You do not have the right to list the commentaries");
	}
}

function displayCommentaryForm()
{
	global $babBody;

	$oTmCtx =& getTskMgrContext();
	$iIdProjectSpace = $oTmCtx->getIdProjectSpace();
	$iIdProject = $oTmCtx->getIdProject();
	
	if(bab_isAccessValid(BAB_TSKMGR_PROJECTS_MANAGERS_GROUPS_TBL, $iIdProject))
	{
		$iIdCommentary = tskmgr_getVariable('iIdCommentary', 0);
		$tab_caption = ($iIdCommentary == 0) ? bab_translate("Add a commentary") : bab_translate("Edition of a commentary");
		$babBody->title = $tab_caption;
	
		$itemMenu = array(		
			array(
				'idx' => BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_LIST,
				'mnuStr' => bab_translate("Projects spaces"),
				'url' => $GLOBALS['babUrlScript'] . '?tg=usrTskMgr&idx=' . BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_LIST),
			array(
				'idx' => BAB_TM_IDX_DISPLAY_PROJECTS_LIST,
				'mnuStr' => bab_translate("Projects list"),
				'url' => $GLOBALS['babUrlScript'] . '?tg=usrTskMgr&idx=' . BAB_TM_IDX_DISPLAY_PROJECTS_LIST . 
				'&iIdProjectSpace=' . $iIdProjectSpace),
			array(
				'idx' => BAB_TM_IDX_DISPLAY_PROJECT_COMMENTARY_LIST,
				'mnuStr' => bab_translate("Commentaries list"),
				'url' => $GLOBALS['babUrlScript'] . '?tg=usrTskMgr&idx=' . BAB_TM_IDX_DISPLAY_PROJECT_COMMENTARY_LIST . 
				'&iIdProject=' . $iIdProject),
			array(
				'idx' => BAB_TM_IDX_DISPLAY_COMMENTARY_FORM,
				'mnuStr' => $tab_caption,
				'url' => $GLOBALS['babUrlScript'] . '?tg=usrTskMgr&idx=' . BAB_TM_IDX_DISPLAY_COMMENTARY_FORM . 
				'&iIdProject=' . $iIdProject)
		);
		
		add_item_menu($itemMenu);
		
		$oBf = & new BAB_BaseFormProcessing();
		
		$oBf->set_caption('add', bab_translate("Add"));
		$oBf->set_caption('modify', bab_translate("Modify"));
		$oBf->set_caption('delete', bab_translate("Delete"));

		$oBf->set_data('addIdx', BAB_TM_IDX_DISPLAY_PROJECT_COMMENTARY_LIST);
		$oBf->set_data('addAction', BAB_TM_ACTION_ADD_PROJECT_COMMENTARY);
		$oBf->set_data('modifyIdx', BAB_TM_IDX_DISPLAY_PROJECT_COMMENTARY_LIST);
		$oBf->set_data('modifyAction', BAB_TM_ACTION_MODIFY_PROJECT_COMMENTARY);
		$oBf->set_data('delIdx', BAB_TM_IDX_DISPLAY_DELETE_PROJECT_COMMENTARY);
		$oBf->set_data('delAction', '');
		$oBf->set_data('tg', 'usrTskMgr');

		$oBf->set_data('iIdProjectSpace', $iIdProjectSpace);
		$oBf->set_data('iIdCommentary', $iIdCommentary);
		$oBf->set_data('iIdProject', $iIdProject);
		$oBf->set_data('iIdTask', 0);

		$oBf->set_data('commentary', '');
		
		$success = tmGetProjectCommentary($iIdCommentary, $sCommentary);
		if(false != $success)
		{
			$oBf->set_data('commentary', htmlentities($sCommentary, ENT_QUOTES));
		}
		
		$babBody->babecho(bab_printTemplate($oBf, 'tmUser.html', 'displayCommentary'));
	}
	else 
	{
		$GLOBALS['babBody']->msgerror = bab_translate("You do not have the right to list the commentaries");
	}
}

function displayDeleteProjectCommentary()
{
	global $babBody;

	$oTmCtx =& getTskMgrContext();
	$iIdProjectSpace = $oTmCtx->getIdProjectSpace();
	$iIdProject = $oTmCtx->getIdProject();

	if(bab_isAccessValid(BAB_TSKMGR_PROJECTS_MANAGERS_GROUPS_TBL, $iIdProject))
	{
		$iIdCommentary = tskmgr_getVariable('iIdCommentary', 0);
		if(0 != $iIdCommentary)
		{
			$aProject = null;
			$bSuccess = bab_getProject($iIdProject, $aProject);
	
			if(false !== $bSuccess)
			{
				$bf = & new BAB_BaseFormProcessing();
				$bf->set_data('idx', BAB_TM_IDX_DISPLAY_PROJECT_COMMENTARY_LIST);
				$bf->set_data('action', BAB_TM_ACTION_DELETE_PROJECT_COMMENTARY);
				$bf->set_data('iIdProjectSpace', $iIdProjectSpace);
				$bf->set_data('iIdProject', $iIdProject);
				$bf->set_data('iIdObject', $iIdCommentary);
				$bf->set_data('objectName', 'iIdCommentary');
				$bf->set_data('tg', 'usrTskMgr');
	
				$bf->set_caption('warning', bab_translate("This action will delete the project commentary"));
				$bf->set_caption('message', bab_translate("Continue ?"));
				$bf->set_caption('title', bab_translate("Project = ") . htmlentities($aProject['name'], ENT_QUOTES));
				$bf->set_caption('yes', bab_translate("Yes"));
				$bf->set_caption('no', bab_translate("No"));
	
				$babBody->title = bab_translate("Delete project commentary ");
				$babBody->babecho(bab_printTemplate($bf, 'tmCommon.html', 'warningyesno'));
			}
		}
	}
	else 
	{
		$GLOBALS['babBody']->msgerror = bab_translate("You do not have the right to delete a project");
	}	
}

function displayTaskList()
{
	global $babBody;

	$oTmCtx =& getTskMgrContext();
	$iIdProjectSpace = $oTmCtx->getIdProjectSpace();
	$iIdProject = $oTmCtx->getIdProject();
	
	if(bab_isAccessValid(BAB_TSKMGR_PROJECTS_MANAGERS_GROUPS_TBL, $iIdProject))
	{
		$babBody->title = bab_translate("Commentaries list");
	
		$itemMenu = array(		
			array(
				'idx' => BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_LIST,
				'mnuStr' => bab_translate("Projects spaces"),
				'url' => $GLOBALS['babUrlScript'] . '?tg=usrTskMgr&idx=' . BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_LIST),
			array(
				'idx' => BAB_TM_IDX_DISPLAY_PROJECTS_LIST,
				'mnuStr' => bab_translate("Projects list"),
				'url' => $GLOBALS['babUrlScript'] . '?tg=usrTskMgr&idx=' . BAB_TM_IDX_DISPLAY_PROJECTS_LIST . 
				'&iIdProjectSpace=' . $iIdProjectSpace),
			array(
				'idx' => BAB_TM_IDX_DISPLAY_TASK_LIST,
				'mnuStr' => bab_translate("Tasks list"),
				'url' => $GLOBALS['babUrlScript'] . '?tg=usrTskMgr&idx=' . BAB_TM_IDX_DISPLAY_TASK_LIST . 
				'&iIdProjectSpace=' . $iIdProjectSpace . '&iIdProject=' . $iIdProject),
			array(
				'idx' => BAB_TM_IDX_DISPLAY_TASK_FORM,
				'mnuStr' => bab_translate("Add a task"),
				'url' => $GLOBALS['babUrlScript'] . '?tg=usrTskMgr&idx=' . BAB_TM_IDX_DISPLAY_TASK_FORM . 
				'&iIdProjectSpace=' . $iIdProjectSpace . '&iIdProject=' . $iIdProject)
		);
		
		add_item_menu($itemMenu);
		
		$result = tmSelectTasksList($iIdProject);	
		$oList = new BAB_TM_ListBase($result);
	
		$oList->set_data('url', $GLOBALS['babUrlScript'] . '?tg=usrTskMgr&idx=' . 
			BAB_TM_IDX_DISPLAY_TASK_FORM . '&iIdProjectSpace=' . $iIdProjectSpace .
			'&iIdProject=' . $iIdProject . '&iIdTask=');

		$babBody->babecho(bab_printTemplate($oList, 'tmUser.html', 'taskList'));
	}
	else 
	{
		$GLOBALS['babBody']->msgerror = bab_translate("You do not have the right to list the tasks");
	}	
}

function displayTaskForm()
{
	global $babBody;

	$oTmCtx =& getTskMgrContext();
	$iIdProjectSpace = $oTmCtx->getIdProjectSpace();
	$iIdProject = $oTmCtx->getIdProject();
	
	if(bab_isAccessValid(BAB_TSKMGR_PROJECTS_MANAGERS_GROUPS_TBL, $iIdProject))
	{
		$iIdTask = tskmgr_getVariable('iIdTask', 0);
		$tab_caption = ($iIdTask == 0) ? bab_translate("Add a task") : bab_translate("Edition of a task");
		$babBody->title = $tab_caption;
	
		$itemMenu = array(		
			array(
				'idx' => BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_LIST,
				'mnuStr' => bab_translate("Projects spaces"),
				'url' => $GLOBALS['babUrlScript'] . '?tg=usrTskMgr&idx=' . BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_LIST),
			array(
				'idx' => BAB_TM_IDX_DISPLAY_PROJECTS_LIST,
				'mnuStr' => bab_translate("Projects list"),
				'url' => $GLOBALS['babUrlScript'] . '?tg=usrTskMgr&idx=' . BAB_TM_IDX_DISPLAY_PROJECTS_LIST . 
				'&iIdProjectSpace=' . $iIdProjectSpace),
			array(
				'idx' => BAB_TM_IDX_DISPLAY_TASK_LIST,
				'mnuStr' => bab_translate("Tasks list"),
				'url' => $GLOBALS['babUrlScript'] . '?tg=usrTskMgr&idx=' . BAB_TM_IDX_DISPLAY_TASK_LIST . 
				'&iIdProjectSpace=' . $iIdProjectSpace . '&iIdProject=' . $iIdProject),
			array(
				'idx' => BAB_TM_IDX_DISPLAY_TASK_FORM,
				'mnuStr' => $tab_caption,
				'url' => $GLOBALS['babUrlScript'] . '?tg=usrTskMgr&idx=' . BAB_TM_IDX_DISPLAY_TASK_FORM . 
				'&iIdProject=' . $iIdProject)
		);
		add_item_menu($itemMenu);

		class BAB_TaskForm extends BAB_BaseFormProcessing
		{
			var $m_catResult;
			
			function BAB_TaskForm($iIdProjectSpace, $iIdProject, $iIdTask)
			{
				$this->set_caption('general', bab_translate("General"));
				$this->set_caption('predecessors', bab_translate("Predecessors"));
				$this->set_caption('resources', bab_translate("Resources"));
				$this->set_caption('commentaries', bab_translate("Commentaries"));
				$this->set_caption('spFld', bab_translate("Specific fields"));
				
				$this->set_caption('taskNumber', bab_translate("Number"));
				$this->set_caption('eventType', bab_translate("Type"));
				$this->set_caption('eventTask', bab_translate("Task"));
				$this->set_caption('eventCheckPoint', bab_translate("CheckPoint"));
				$this->set_caption('eventToDo', bab_translate("ToDo"));
				$this->set_caption('categories', bab_translate("Categories"));
				
				$this->set_data('tg', tskmgr_getVariable('tg', 'usrTskMgr'));
				$this->set_data('iIdProjectSpace', $iIdProjectSpace);
				$this->set_data('iIdProject', $iIdProject);
				$this->set_data('iIdTask', $iIdTask);
				
				$this->set_data('eventTask', BAB_TM_TASK);
				$this->set_data('eventCheckPoint', BAB_TM_CHECKPOINT);
				$this->set_data('eventToDo', BAB_TM_TODO);
				
				$this->set_data('sTaskNumber', tskmgr_getVariable('sTaskNumber', ''));
				$this->set_data('bIsTaskNumberReadOnly', tskmgr_getVariable('bIsTaskNumberReadOnly', '0'));
				$this->set_data('sTaskNumberReadOnly', '');
								
				$this->set_data('sSelectedCategory', '');
				
				$this->m_catResult = tmSelectAvailableCategories($iIdProject);
				
				if(!isset($_POST['iIdTask']) && !isset($_GET['iIdTask']))
				{
					$this->set_data('is_creation', true);
					$this->initTaskForm($iIdProject);
				}
				else if( (isset($_GET['iIdTask']) || isset($_POST['iIdTask'])) && 0 != $iIdTask)
				{
					$this->set_data('is_edition', true);
				}
				else
				{
					$this->set_data('is_resubmission', true);
				}
				
				
			}
			
			function getNextCategory()
			{
				global $babDB;
				if(false != $this->m_catResult)
				{
					$datas = $babDB->db_fetch_assoc($this->m_catResult);
					if(false != $datas)
					{
						$this->set_data('iIdCategory', $datas['id']);
						$this->set_data('sCategoryName', $datas['name']);
						return true;
					}
				}
				return false;
			}
		
			function initTaskForm($iIdProject)
			{
				tmGetNextTaskNumber($iIdProject, $bIsReadOnly, $sTaskNumber);
				$this->set_data('sTaskNumber', $sTaskNumber);
				if(true == $bIsReadOnly)
				{
					$this->set_data('bIsTaskNumberReadOnly', '1');
					$this->set_data('sTaskNumberReadOnly', 'readonly="readonly"');
				}
			}
		}
		
		
		
		
		
		tmGetTaskResponsibleList($iIdProject, $aTaskResponsible);
		
		
		//bab_debug($aTaskResponsible);
		
		
		
		$oTaskForm = & new BAB_TaskForm($iIdProjectSpace, $iIdProject, $iIdTask);
		
		
/*		$oBf->set_caption('add', bab_translate("Add"));
		$oBf->set_caption('modify', bab_translate("Modify"));
		$oBf->set_caption('delete', bab_translate("Delete"));

		$oBf->set_data('addIdx', BAB_TM_IDX_DISPLAY_PROJECT_COMMENTARY_LIST);
		$oBf->set_data('addAction', BAB_TM_ACTION_ADD_PROJECT_COMMENTARY);
		$oBf->set_data('modifyIdx', BAB_TM_IDX_DISPLAY_PROJECT_COMMENTARY_LIST);
		$oBf->set_data('modifyAction', BAB_TM_ACTION_MODIFY_PROJECT_COMMENTARY);
		$oBf->set_data('delIdx', BAB_TM_IDX_DISPLAY_DELETE_PROJECT_COMMENTARY);
		$oBf->set_data('delAction', '');
		$oBf->set_data('tg', 'usrTskMgr');

		$oBf->set_data('iIdProjectSpace', $iIdProjectSpace);
		$oBf->set_data('iIdCommentary', $iIdCommentary);
		$oBf->set_data('iIdProject', $iIdProject);
		$oBf->set_data('iIdTask', 0);

		$oBf->set_data('commentary', '');
		
		$success = tmGetProjectCommentary($iIdCommentary, $sCommentary);
		if(false != $success)
		{
			$oBf->set_data('commentary', htmlentities($sCommentary, ENT_QUOTES));
		}
*/		
		$babBody->babecho(bab_printTemplate($oTaskForm, 'tmUser.html', 'taskForm'));

	}
	else 
	{
		$GLOBALS['babBody']->msgerror = bab_translate("You do not have the right to list the commentaries");
	}
}

//POST


function addModifyProject()
{
	global $babBody;

	$oTmCtx =& getTskMgrContext();
	$iIdProjectSpace = $oTmCtx->getIdProjectSpace();
	$iIdProject = $oTmCtx->getIdProject();
	
	//bab_debug('iIdProjectSpace ==> ' . $iIdProjectSpace);
	//bab_debug('iIdProject ==> ' . $iIdProject);
	
	if(bab_isAccessValid(BAB_TSKMGR_PROJECT_CREATOR_GROUPS_TBL, $iIdProjectSpace))
	{
		$sName = trim(tskmgr_getVariable('sName', ''));

		if(0 < strlen($sName))
		{
			$isValid = isNameUsedInProjectSpace(BAB_TSKMGR_PROJECTS_TBL, $iIdProjectSpace, $iIdProject, $sName);
			$sName = mysql_escape_string($sName);
			
			$sDescription = mysql_escape_string(trim(tskmgr_getVariable('sDescription', '')));
			
			if($isValid)
			{
				if(0 == $iIdProject)
				{
					bab_createProject($iIdProjectSpace, $sName, $sDescription);
				}
				else
				{
					bab_updateProject($iIdProject, $sName, $sDescription);
				}
			}
			else
			{
				$GLOBALS['babBody']->msgerror = bab_translate("There is an another project with the name") . '\'' . $sName . '\'';
				$_POST['idx'] = BAB_TM_IDX_DISPLAY_PROJECT_FORM;
				return false;
			}
		}
		else
		{
			$GLOBALS['babBody']->msgerror = bab_translate("The field name must not be blank");
			$_POST['idx'] = BAB_TM_IDX_DISPLAY_PROJECT_FORM;
			//unset($_POST['iIdProject']);
			return false;
		}
	}
	else
	{
		$GLOBALS['babBody']->msgerror = bab_translate("You do not have the right to create a project");
	}
	
}

function deleteProject()
{
	global $babBody;

	$oTmCtx =& getTskMgrContext();
	$iIdProjectSpace = $oTmCtx->getIdProjectSpace();
	$iIdProject = $oTmCtx->getIdProject();
	
	//bab_debug('iIdProjectSpace ==> ' . $iIdProjectSpace);
	
	if(bab_isAccessValid(BAB_TSKMGR_PROJECT_CREATOR_GROUPS_TBL, $iIdProjectSpace))
	{
		if(bab_isProjectDeletable($iIdProject))
		{
			require_once($GLOBALS['babInstallPath'] . 'admin/acl.php');

			if(bab_deleteProject($iIdProject))
			{
				bab_updateRefCount(BAB_TSKMGR_PROJECTS_SPACES_TBL, $iIdProjectSpace, '- \'1\'');
			}
		}
	}
	else
	{
		$GLOBALS['babBody']->msgerror = bab_translate("You do not have the right to delete a project");
	}
}

function setRight()
{
	require_once($GLOBALS['babInstallPath'] . 'admin/acl.php');
	maclGroups();
}

function saveProjectConfiguration()
{
	$oTmCtx =& getTskMgrContext();
	$iIdProject = $oTmCtx->getIdProject();
	$bIsManager = bab_isAccessValid(BAB_TSKMGR_PROJECTS_MANAGERS_GROUPS_TBL, $iIdProject);
	
	$iTaskUpdateByMgr = (int) tskmgr_getVariable('iTaskUpdateByMgr', BAB_TM_YES);
	$iIdConfiguration = (int) tskmgr_getVariable('iIdConfiguration', 0);
	$iEndTaskReminder = (int) tskmgr_getVariable('iEndTaskReminder', 5);
	$iTaskNumerotation = (int) tskmgr_getVariable('iTaskNumerotation', BAB_TM_SEQUENTIAL);
	$iEmailNotice = (int) tskmgr_getVariable('iEmailNotice', BAB_TM_YES);
	$sFaqUrl = mysql_escape_string(tskmgr_getVariable('sFaqUrl', ''));

	if(0 < $iIdConfiguration && 0 < $iIdProject && $bIsManager)
	{
		$aConfiguration = array(
			'id' => $iIdConfiguration,
			'idProject' => $iIdProject,
			'tskUpdateByMgr' => $iTaskUpdateByMgr,
			'endTaskReminder' => $iEndTaskReminder,
			'tasksNumerotation' => $iTaskNumerotation,
			'emailNotice' => $iEmailNotice,
			'faqUrl' => $sFaqUrl);

		bab_updateProjectConfiguration($aConfiguration);
	}
}	

function addModifyProjectCommentary()
{
	$iIdProject = (int) tskmgr_getVariable('iIdProject', 0);
	$iIdCommentary = (int) tskmgr_getVariable('iIdCommentary', 0);

	if(bab_isAccessValid(BAB_TSKMGR_PROJECTS_MANAGERS_GROUPS_TBL, $iIdProject))
	{
		$sCommentary = mysql_escape_string(trim(tskmgr_getVariable('sCommentary', '')));
		
		if(strlen($sCommentary) > 0)
		{
			if(0 == $iIdCommentary)
			{
				tmCreateProjectCommentary($iIdProject, $sCommentary);
			}
			else 
			{
				tmUpdateProjectCommentary($iIdCommentary, $sCommentary);
			}
		}
		else 
		{
			bab_debug('addModifyProjectCommentary: commentary empty');
		}
	}
	else 
	{
		bab_debug('addModifyProjectCommentary: acces denied');
	}
}

function deleteProjectCommentary()
{
	$iIdProject = (int) tskmgr_getVariable('iIdProject', 0);
	$iIdCommentary = (int) tskmgr_getVariable('iIdCommentary', 0);

	if(bab_isAccessValid(BAB_TSKMGR_PROJECTS_MANAGERS_GROUPS_TBL, $iIdProject))
	{
		tmDeleteProjectCommentary($iIdCommentary);
	}
	else 
	{
		bab_debug('addModifyProjectCommentary: acces denied');
	}
}

bab_cleanGpc();



/* main */
$context =& getTskMgrContext();
if(false == $context->isUserProjectVisualizer())
{
	$babBody->msgerror = bab_translate("Access denied");
	return;
}


/* main */
$action = isset($_POST['action']) ? $_POST['action'] : 
	(isset($_GET['action']) ? $_GET['action'] :  
	(isset($_POST[BAB_TM_ACTION_SET_RIGHT]) ? BAB_TM_ACTION_SET_RIGHT : '???')
	);

//bab_debug('action ==> ' . $action);

switch($action)
{
	case BAB_TM_ACTION_ADD_PROJECT:
	case BAB_TM_ACTION_MODIFY_PROJECT:
		addModifyProject();
		break;
		
	case BAB_TM_ACTION_DELETE_PROJECT:
		deleteProject();
		break;
		
	case BAB_TM_ACTION_SET_RIGHT:
		setRight();
		break;
		
	case BAB_TM_ACTION_UPDATE_WORKING_HOURS:
		require_once($GLOBALS['babInstallPath'] . 'tmWorkingHoursFunc.php');
		updateWorkingHours();
		break;
		
	case BAB_TM_ACTION_SAVE_PROJECTS_CONFIGURATION:
		saveProjectConfiguration();
		break;

	case BAB_TM_ACTION_ADD_PROJECT_COMMENTARY:
	case BAB_TM_ACTION_MODIFY_PROJECT_COMMENTARY:
		addModifyProjectCommentary();
		break;
		
	case BAB_TM_ACTION_DELETE_PROJECT_COMMENTARY:
		deleteProjectCommentary();
		break;
		
	case BAB_TM_ACTION_DELETE_CATEGORY:
		deleteCategory();
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
		
	case BAB_TM_IDX_DISPLAY_PROJECTS_LIST:
		displayProjectsList();
		break;
		
	case BAB_TM_IDX_DISPLAY_PROJECT_FORM:
		displayProjectForm();
		break;
		
	case BAB_TM_IDX_DISPLAY_DELETE_PROJECT_FORM:
		displayDeleteProjectForm();
		break;
		
	case BAB_TM_IDX_DISPLAY_PROJECT_RIGHTS_FORM:
		displayProjectRightsForm();
		break;
		
	case BAB_TM_IDX_DISPLAY_PROJECTS_CONFIGURATION_FORM:
		displayProjectsConfigurationForm();
		break;
		
	case BAB_TM_IDX_DISPLAY_PROJECT_COMMENTARY_LIST:
		displayProjectCommentaryList();
		break;
		
	case BAB_TM_IDX_DISPLAY_COMMENTARY_FORM:
		displayCommentaryForm();
		break;
		
	case BAB_TM_IDX_DISPLAY_DELETE_PROJECT_COMMENTARY:
		displayDeleteProjectCommentary();
		break;
		
	case BAB_TM_IDX_DISPLAY_TASK_LIST:
		displayTaskList();
		break;
		
	case BAB_TM_IDX_DISPLAY_TASK_FORM:
		displayTaskForm();
		break;
//*//
	case BAB_TM_IDX_DISPLAY_CATEGORIES_LIST:
		displayCategoriesList();
		break;
		
	case BAB_TM_IDX_DISPLAY_CATEGORY_FORM:
		displayCategoryForm();
		break;
		
	case BAB_TM_IDX_DISPLAY_DELETE_CATEGORY_FORM:
		displayDeleteCategoryForm();
		break;
}
$babBody->setCurrentItemMenu($idx);
?>