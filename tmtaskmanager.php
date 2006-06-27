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
require_once($babInstallPath . 'tmSpecificFieldsFunc.php');
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
	$babBody->babecho(bab_printTemplate($bfp, 'tmCommon.html', 'displayMenu'));
}

function displayProjectsSpacesList()
{
	global $babBody, $babDB;

	$babBody->title = bab_translate("Projects spaces list");

	$itemMenu = array();
	add_item_menu($itemMenu);

	require_once $GLOBALS['babInstallPath'] . 'utilit/tree.php';

	class BAB_TM_List extends bab_TreeView
	{
		var $m_sUrlBase 			= '';

		var $m_iIdSpaceElement		= 'sn_0';
		var $m_iIdPersTaskElement	= 'sn_1';

		var $m_sn		= 'sn';
		var $m_snps		= 'snps';
		
		var $m_dn		= 'dn';
		var $m_dnps		= 'dnps';
		var $m_dnp 		= 'dnp';
		
		function BAB_TM_List()
		{
			parent::bab_TreeView('myTreeView');
			
			$sTg = tskmgr_getVariable('tg', 'admTskMgr');
			$this->m_sUrlBase = $GLOBALS['babUrlScript'] . '?tg=' . $sTg . '&idx=%s&iIdProjectSpace=%d&iIdProject=%d';
			
			$this->createProjectSpaceSubTree();
			$this->createPersonnalTaskSubTree();
		}
		
		function getUrl($sIdx, $iIdProjectSpace, $iIdProject)
		{
			return sprintf($this->m_sUrlBase, $sIdx, $iIdProjectSpace, $iIdProject);
		}
		
		function createProjectSpaceSubTree()
		{
			$oSpaceElement =& $this->createElement($this->m_iIdSpaceElement, $this->m_snps, bab_translate("Space(s)"), 
				'description', null);
			$this->appendElement($oSpaceElement, null);
			
			$this->insertVisualizedProjectSpace($oSpaceElement);
		}
		
		function getVisualizedProjectSpaceQueryResult()
		{
			global $babDB;
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
		
			//bab_debug($query);
			return $babDB->db_query($query);
		}
		
		function insertVisualizedProjectSpace(&$oSpaceElement)
		{
			$result = $this->getVisualizedProjectSpaceQueryResult();
			if(false != $result)
			{
				global $babDB;
				
				$iIdProject = 0;
				$iIndex = 0;
				$iNumRows = $babDB->db_num_rows($result);
				
				while( $iIndex < $iNumRows && false != ($datas = $babDB->db_fetch_array($result)) )
				{
					$oProjectSpaceElement =& $this->createElement($this->m_dnps . '_' . $datas['id'], $this->m_dnps, $datas['name'], 
						$datas['description'], null);
					
					if(bab_isAccessValid(BAB_TSKMGR_PROJECT_CREATOR_GROUPS_TBL, $datas['id']))
					{
						$oProjectSpaceElement->addAction('add',
			               bab_translate('Add a project'), $GLOBALS['babSkinPath'] . 'images/Puces/edit_add.png', 
			               $this->getUrl(BAB_TM_IDX_DISPLAY_PROJECT_FORM, $datas['id'], 0), '');
					}
					
               		$this->appendElement($oProjectSpaceElement, $this->m_iIdSpaceElement);
               		
               		$this->insertProject($datas['id'], $oProjectSpaceElemen);
 				}
			}	
		}
		
		function insertProject($iIdProjectSpace, &$oProjectSpaceElement)
		{
			$result = bab_selectProjectList($iIdProjectSpace);
			if(false != $result)
			{
				global $babDB;
				
				$iIndex = 0;
				$iNumRows = $babDB->db_num_rows($result);
				
				$bIsCreator = bab_isAccessValid(BAB_TSKMGR_PROJECT_CREATOR_GROUPS_TBL, $iIdProjectSpace);
				
				while( $iIndex < $iNumRows && false != ($datas = $babDB->db_fetch_array($result)) )
				{
					$bIsManager = bab_isAccessValid(BAB_TSKMGR_PROJECTS_MANAGERS_GROUPS_TBL, $datas['id']);
					$isAccessValid = ($bIsCreator || $bIsManager);

					$oProjectElement =& $this->createElement($this->m_dnp . '_' . $datas['id'], $this->m_dnp, $datas['name'], 
						$datas['description'], $this->getUrl(BAB_TM_IDX_DISPLAY_PROJECT_FORM, $iIdProjectSpace, $datas['id']));
               		$this->appendElement($oProjectElement, $this->m_dnps . '_' . $iIdProjectSpace);
               		
               		if($isAccessValid)
               		{
						$oProjectElement->addAction('Rights',
			               bab_translate('Rights'), $GLOBALS['babSkinPath'] . 'images/Puces/agent.png', 
			               $this->getUrl(BAB_TM_IDX_DISPLAY_PROJECT_RIGHTS_FORM, $iIdProjectSpace, $datas['id']), '');
               		}
               		
               		if($bIsManager)
               		{
						$oProjectElement->addAction('Configuration',
			               bab_translate('Configuration'), $GLOBALS['babSkinPath'] . 'images/Puces/package_settings.png', 
			               $this->getUrl(BAB_TM_IDX_DISPLAY_PROJECTS_CONFIGURATION_FORM, $iIdProjectSpace, $datas['id']), '');
						$oProjectElement->addAction('Specific_fields',
			               bab_translate('Specific fields'), $GLOBALS['babSkinPath'] . 'images/Puces/list.png', 
			               $this->getUrl(BAB_TM_IDX_DISPLAY_SPECIFIC_FIELD_LIST, $iIdProjectSpace, $datas['id']), '');
						$oProjectElement->addAction('Categories_list',
			               bab_translate('Categories list'), $GLOBALS['babSkinPath'] . 'images/Puces/kwikdisk.png', 
			               $this->getUrl(BAB_TM_IDX_DISPLAY_CATEGORIES_LIST, $iIdProjectSpace, $datas['id']), '');
						$oProjectElement->addAction('Notices',
			               bab_translate('Notices'), $GLOBALS['babSkinPath'] . 'images/Puces/mailreminder.png', 
			               $this->getUrl(BAB_TM_IDX_DISPLAY_NOTICE_EVENT_FORM, $iIdProjectSpace, $datas['id']), '');
						$oProjectElement->addAction('Commentaries',
			               bab_translate('Commentaries list'), $GLOBALS['babSkinPath'] . 'images/Puces/lists.png', 
			               $this->getUrl(BAB_TM_IDX_DISPLAY_PROJECT_COMMENTARY_LIST, $iIdProjectSpace, $datas['id']), '');
						$oProjectElement->addAction('Task_list',
						   bab_translate('Add a Task'), $GLOBALS['babSkinPath'] . 'images/Puces/edit_add.png', 
						   $this->getUrl(BAB_TM_IDX_DISPLAY_TASK_FORM, $iIdProjectSpace, $datas['id']), '');
               		}
               		
		            /*   
					$oProjectElement->addAction('Task_list',
					   bab_translate('Tasks list'), $GLOBALS['babSkinPath'] . 'images/Puces/windowlist.png', 
					   $this->getUrl(BAB_TM_IDX_DISPLAY_TASK_LIST, $iIdProjectSpace, $datas['id']), '');
					//*/
				}
			}	
		}
		
		function createPersonnalTaskSubTree()
		{
			$oTmCtx =& getTskMgrContext();
			if(BAB_TM_PERSONNAL_TASK_OWNER === $oTmCtx->getUserProfil())
			{
				$oPersTaskElement =& $this->createElement($this->m_iIdPersTaskElement, 'snps', bab_translate("Personnal Task"), 'description', null);
				
				$oPersTaskElement->addAction('Add',
	               bab_translate('Add'), $GLOBALS['babSkinPath'] . 'images/Puces/edit_add.png', 
	               $this->getUrl(BAB_TM_IDX_DISPLAY_TASK_FORM, 0, 0), '');

			    $this->appendElement($oPersTaskElement, null);
			}
		}
	}
	
//	$list = new BAB_TM_List();
//	$GLOBALS['babBody']->babecho($list->printTemplate());

	$oMultiPage = new BAB_MultiPageBase();
	
	$oMultiPage->oDataSource = new BAB_DataSourceBase();
	$oMultiPage->iTotalNumOfRows = 50;
	$oMultiPage->iCurrentPage = 6;
	
	//$oMultiPage->iNbRowsPerPage = -1;
	

	$oMultiPage->sTg = tskmgr_getVariable('tg', 'admTskMgr');
	$oMultiPage->iIdx = BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_LIST;
	
	$oMultiPage->addColumnHeader(0, 'Name', 'name');
	$oMultiPage->addColumnHeader(1, 'Description', 'description');
	$oMultiPage->addColumnHeader(2, 'Date', 'date');
	
	$oMultiPage->computeStartEndPos();
	$oMultiPage->sStatusLine = $oMultiPage->getStatusLine();
	$oMultiPage->sPagination = $oMultiPage->getPagination();
	
	$GLOBALS['babBody']->babecho($oMultiPage->printTemplate());
}

/*
function displayProjectsList()
{
	global $babBody;
	$oTmCtx =& getTskMgrContext();
	$iIdProjectSpace = $oTmCtx->getIdProjectSpace();
	
	$babBody->title = bab_translate("Projects list");

	$itemMenu = array();
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
			$this->set_caption('spfFld', bab_translate("Specific fields"));
			$this->set_caption('category', bab_translate("Categories list"));
			$this->set_caption('commentary', bab_translate("Display project commentaries list"));
			$this->set_caption('task', bab_translate("Display tasks list"));
			$this->set_caption('notice', bab_translate("Notice events"));

			$oTmCtx =& getTskMgrContext();
			$iIdProjectSpace = $oTmCtx->getIdProjectSpace();

			$this->set_data('iIdProjectSpace', $iIdProjectSpace);
			$this->set_data('bIsProjectCreator', 
				bab_isAccessValid(BAB_TSKMGR_PROJECT_CREATOR_GROUPS_TBL, $iIdProjectSpace));
				
			$this->set_data('rightsUrl', '#');
			$this->set_data('configurationUrl', '#');
			$this->set_data('specificFieldUrl', '#');
			$this->set_data('categoryUrl', '#');
			$this->set_data('commentaryUrl', '#');
			$this->set_data('taskUrl', '#');
			$this->set_data('noticeUrl', '#');
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

				$this->set_data('specificFieldUrl', $GLOBALS['babUrlScript'] . '?tg=usrTskMgr&idx=' .
					BAB_TM_IDX_DISPLAY_SPECIFIC_FIELD_LIST . '&iIdProjectSpace=' . $iIdProjectSpace . '&iIdProject=' . $this->m_rowDatas['id']
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

				$this->set_data('noticeUrl', $GLOBALS['babUrlScript'] . '?tg=usrTskMgr&idx=' .
					BAB_TM_IDX_DISPLAY_NOTICE_EVENT_FORM . '&iIdProjectSpace=' . $iIdProjectSpace . '&iIdProject=' . $this->m_rowDatas['id']
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
//*/

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
				
				$this->set_data('add_idx', BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_LIST);
				$this->set_data('modify_idx', BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_LIST);
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
/*			array(
				'idx' => BAB_TM_IDX_DISPLAY_PROJECTS_LIST,
				'mnuStr' => bab_translate("Projects list"),
				'url' => $GLOBALS['babUrlScript'] . '?tg=usrTskMgr&idx=' . BAB_TM_IDX_DISPLAY_PROJECTS_LIST . 
				'&iIdProjectSpace=' . $iIdProjectSpace),//*/
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
				$bf->set_data('idx', BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_LIST);
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
/*			array(
				'idx' => BAB_TM_IDX_DISPLAY_PROJECTS_LIST,
				'mnuStr' => bab_translate("Projects list"),
				'url' => $GLOBALS['babUrlScript'] . '?tg=usrTskMgr&idx=' . BAB_TM_IDX_DISPLAY_PROJECTS_LIST . 
				'&iIdProjectSpace=' . $iIdProjectSpace),//*/
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
	
		
		$macl = new macl('usrTskMgr', BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_LIST, $iIdProject, BAB_TM_ACTION_SET_RIGHT);
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
					$this->set_data('iIdConfiguration', $aDPC['id']);
					$this->set_data('isTaskUpdatedByMgr', (BAB_TM_YES == $aDPC['tskUpdateByMgr']));
					$this->set_data('endTaskReminder', $aDPC['endTaskReminder']);
					$this->set_data('taskNumerotation', $aDPC['tasksNumerotation']);
					$this->set_data('isEmailNotice', (BAB_TM_YES == $aDPC['emailNotice']));
					$this->set_data('faqUrl', htmlentities($aDPC['faqUrl']));
					$this->set_data('iIdConfiguration', $aDPC['id']);
				}

				$isEmpty = true;
				global $babDB;
				$result = bab_selectTasksList($iIdProject);
				if(false != $result && $babDB->db_num_rows($result) > 0)
				{
					$isEmpty = false;
				}
				$this->set_data('isProjectEmpty', $isEmpty);
				
				if(!$isEmpty)
				{
					$this->get_data('aTaskNumerotation', $aTaskNumerotation);
					$this->set_data('tmValue', $aTaskNumerotation[$aDPC['tasksNumerotation']]);
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
/*			array(
				'idx' => BAB_TM_IDX_DISPLAY_PROJECTS_LIST,
				'mnuStr' => bab_translate("Projects list"),
				'url' => $GLOBALS['babUrlScript'] . '?tg=usrTskMgr&idx=' . BAB_TM_IDX_DISPLAY_PROJECTS_LIST . 
				'&iIdProjectSpace=' . $iIdProjectSpace),//*/
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
/*			array(
				'idx' => BAB_TM_IDX_DISPLAY_PROJECTS_LIST,
				'mnuStr' => bab_translate("Projects list"),
				'url' => $GLOBALS['babUrlScript'] . '?tg=usrTskMgr&idx=' . BAB_TM_IDX_DISPLAY_PROJECTS_LIST . 
				'&iIdProjectSpace=' . $iIdProjectSpace),//*/
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
		
		$result = bab_selectProjectCommentaryList($iIdProject);	
		$oList = new BAB_TM_ListBase($result);
	
		$oList->set_data('isAddCommentaryUrl', true);
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
		$isPopUp = tskmgr_getVariable('isPopUp', 0);
		$tab_caption = ($iIdCommentary == 0) ? bab_translate("Add a commentary") : bab_translate("Edition of a commentary");
		$babBody->title = $tab_caption;
	
		$itemMenu = array(		
			array(
				'idx' => BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_LIST,
				'mnuStr' => bab_translate("Projects spaces"),
				'url' => $GLOBALS['babUrlScript'] . '?tg=usrTskMgr&idx=' . BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_LIST),
/*			array(
				'idx' => BAB_TM_IDX_DISPLAY_PROJECTS_LIST,
				'mnuStr' => bab_translate("Projects list"),
				'url' => $GLOBALS['babUrlScript'] . '?tg=usrTskMgr&idx=' . BAB_TM_IDX_DISPLAY_PROJECTS_LIST . 
				'&iIdProjectSpace=' . $iIdProjectSpace),//*/
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
		$oBf->set_data('isPopUp', $isPopUp);

		$oBf->set_data('commentary', '');
		
		$success = bab_getProjectCommentary($iIdCommentary, $sCommentary);
		if(false != $success)
		{
			$oBf->set_data('commentary', htmlentities($sCommentary, ENT_QUOTES));
		}
		
		if(0 == $isPopUp)
		{
			$babBody->babecho(bab_printTemplate($oBf, 'tmUser.html', 'displayCommentary'));
		}
		else
		{
			die(bab_printTemplate($oBf, 'tmUser.html', 'displayCommentary'));	
		}
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
	
	$bIsTaskResp = bab_isAccessValid(BAB_TSKMGR_TASK_RESPONSIBLE_GROUPS_TBL, $iIdProject);
	$bIsManager = bab_isAccessValid(BAB_TSKMGR_PROJECTS_MANAGERS_GROUPS_TBL, $iIdProject);
	
	if($bIsTaskResp || $bIsManager)
	{
		$babBody->title = bab_translate("Task list");
	
		$itemMenu = array(		
			array(
				'idx' => BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_LIST,
				'mnuStr' => bab_translate("Projects spaces"),
				'url' => $GLOBALS['babUrlScript'] . '?tg=usrTskMgr&idx=' . BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_LIST),
/*			array(
				'idx' => BAB_TM_IDX_DISPLAY_PROJECTS_LIST,
				'mnuStr' => bab_translate("Projects list"),
				'url' => $GLOBALS['babUrlScript'] . '?tg=usrTskMgr&idx=' . BAB_TM_IDX_DISPLAY_PROJECTS_LIST . 
				'&iIdProjectSpace=' . $iIdProjectSpace),//*/
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
		
		$result = bab_selectTasksList($iIdProject);	
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
	
	$bIsTaskResp = bab_isAccessValid(BAB_TSKMGR_TASK_RESPONSIBLE_GROUPS_TBL, $iIdProject);
	$bIsManager = bab_isAccessValid(BAB_TSKMGR_PROJECTS_MANAGERS_GROUPS_TBL, $iIdProject);
	
	if($bIsTaskResp || $bIsManager || BAB_TM_PERSONNAL_TASK_OWNER === $oTmCtx->getUserProfil())
	{
		$iIdTask = tskmgr_getVariable('iIdTask', 0);
		$tab_caption = ($iIdTask == 0) ? bab_translate("Add a task") : bab_translate("Edition of a task");
		$babBody->title = $tab_caption;

		$itemMenu = array(		
			array(
				'idx' => BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_LIST,
				'mnuStr' => bab_translate("Projects spaces"),
				'url' => $GLOBALS['babUrlScript'] . '?tg=usrTskMgr&idx=' . BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_LIST),
/*			array(
				'idx' => BAB_TM_IDX_DISPLAY_PROJECTS_LIST,
				'mnuStr' => bab_translate("Projects list"),
				'url' => $GLOBALS['babUrlScript'] . '?tg=usrTskMgr&idx=' . BAB_TM_IDX_DISPLAY_PROJECTS_LIST . 
				'&iIdProjectSpace=' . $iIdProjectSpace),//*/
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
		
		global $babInstallPath;
		require_once($babInstallPath . 'tmTaskClasses.php');
		
		$oTaskForm = & new BAB_TaskForm();
		$babBody->babecho(bab_printTemplate($oTaskForm, 'tmUser.html', 'taskForm'));
	}
	else 
	{
		$GLOBALS['babBody']->msgerror = bab_translate("You do not have the right to create/edit task");
	}
}

function isTaskDeletable($iIdTask, $iUserProfil, &$sTaskNumber)
{
	if(BAB_TM_PROJECT_MANAGER == $iUserProfil || BAB_TM_PERSONNAL_TASK_OWNER == $iUserProfil)
	{
		if(0 != $iIdTask)
		{
			
			global $babInstallPath;
			require_once($babInstallPath . 'tmTaskClasses.php');
	
			$oTask = new BAB_TM_Task();
			if($oTask->loadFromDataBase($iIdTask))
			{
				if(BAB_TM_TENTATIVE == $oTask->m_aTask['iParticipationStatus'] || 
					BAB_TM_ACCEPTED == $oTask->m_aTask['iParticipationStatus'] || 
					BAB_TM_ENDED == $oTask->m_aTask['iParticipationStatus']			)
				{
					$sTaskNumber = $oTask->m_aTask['sTaskNumber'];
					return true;
				}
				else
				{
					$GLOBALS['babBody']->msgerror = bab_translate("The task is not delatable because it is not stopped");					
				}
			}
			else
			{
				$GLOBALS['babBody']->msgerror = bab_translate("Cannot retrieve task information");
			}
		}
		else
		{
			$GLOBALS['babBody']->msgerror = bab_translate("Invalid task");
		}
	}		
	else 
	{
		$GLOBALS['babBody']->msgerror = bab_translate("You do not have the right to delete a task");
	}
	return false;
}

function displayDeleteTaskForm()
{
	global $babBody;
	$babBody->title = bab_translate("Delete task");
	

	$oTmCtx =& getTskMgrContext();
	$iIdProjectSpace = $oTmCtx->getIdProjectSpace();
	$iIdProject = $oTmCtx->getIdProject();
	$iIdTask = $oTmCtx->getIdTask();
	$iUserProfil = $oTmCtx->getUserProfil();


	$bf = & new BAB_BaseFormProcessing();
	$bf->set_data('iIdProjectSpace', $iIdProjectSpace);
	$bf->set_data('iIdProject', $iIdProject);
	$bf->set_data('objectName', 'iIdTask');
	$bf->set_data('iIdObject', $iIdTask);
	$bf->set_data('tg', 'usrTskMgr');

	$bf->set_caption('yes', bab_translate("Yes"));
	$bf->set_caption('no', bab_translate("No"));
	
	$bf->set_data('idx', BAB_TM_IDX_DISPLAY_TASK_LIST);
	
	
	if(isTaskDeletable($iIdTask, $iUserProfil, $sTaskNumber))
	{
		$bf->set_data('action', BAB_TM_ACTION_DELETE_TASK);

		$bf->set_caption('warning', bab_translate("This action will delete the task and all references"));
		$bf->set_caption('message', bab_translate("Continue ?"));
		$bf->set_caption('title', bab_translate("Task number = ") . htmlentities($sTaskNumber, ENT_QUOTES));
	}
	else 
	{
		$bf->set_data('action', '');
		$bf->set_caption('warning', bab_translate("This task is not deletable"));
		$bf->set_caption('message', '');
		$bf->set_caption('title', '');
	}
	
	$babBody->babecho(bab_printTemplate($bf, 'tmCommon.html', 'warningyesno'));
}

function isTaskStoppable($iIdTask, $iUserProfil, &$sTaskNumber)
{
	if(BAB_TM_PROJECT_MANAGER == $iUserProfil)
	{
		if(0 != $iIdTask)
		{
			
			global $babInstallPath;
			require_once($babInstallPath . 'tmTaskClasses.php');
	
			$oTask = new BAB_TM_Task();
			if($oTask->loadFromDataBase($iIdTask))
			{
				if(/*BAB_TM_IN_PROGRESS == $oTask->m_aTask['iParticipationStatus']*/$oTask->m_bIsStarted)
				{
					$sTaskNumber = $oTask->m_aTask['sTaskNumber'];
					return true;
				}
				else
				{
					$GLOBALS['babBody']->msgerror = bab_translate("The task is not stoppable because it is not started");					
				}
			}
			else
			{
				$GLOBALS['babBody']->msgerror = bab_translate("Cannot retrieve task information");
			}
		}
		else
		{
			$GLOBALS['babBody']->msgerror = bab_translate("Invalid task");
		}
	}		
	else 
	{
		$GLOBALS['babBody']->msgerror = bab_translate("You do not have the right to stop a task");
	}
	return false;
}

function displayStopTaskForm()
{
	global $babBody;
	$babBody->title = bab_translate("Stop task");
	

	$oTmCtx =& getTskMgrContext();
	$iIdProjectSpace = $oTmCtx->getIdProjectSpace();
	$iIdProject = $oTmCtx->getIdProject();
	$iIdTask = $oTmCtx->getIdTask();
	$iUserProfil = $oTmCtx->getUserProfil();


	$bf = & new BAB_BaseFormProcessing();
	$bf->set_data('iIdProjectSpace', $iIdProjectSpace);
	$bf->set_data('iIdProject', $iIdProject);
	$bf->set_data('objectName', 'iIdTask');
	$bf->set_data('iIdObject', $iIdTask);
	$bf->set_data('tg', 'usrTskMgr');

	$bf->set_caption('yes', bab_translate("Yes"));
	$bf->set_caption('no', bab_translate("No"));
	
	$bf->set_data('idx', BAB_TM_IDX_DISPLAY_TASK_LIST);
	
	
	if(isTaskStoppable($iIdTask, $iUserProfil, $sTaskNumber))
	{
		$bf->set_data('action', BAB_TM_ACTION_STOP_TASK);

		$bf->set_caption('warning', bab_translate("This action will stop the task"));
		$bf->set_caption('message', bab_translate("Continue ?"));
		$bf->set_caption('title', bab_translate("Task number = ") . htmlentities($sTaskNumber, ENT_QUOTES));
	}
	else 
	{
		$bf->set_data('action', '');
		$bf->set_caption('warning', bab_translate("This task is not stoppable"));
		$bf->set_caption('message', '');
		$bf->set_caption('title', '');
	}
	
	$babBody->babecho(bab_printTemplate($bf, 'tmCommon.html', 'warningyesno'));
}
//POST


function addModifyProject()
{
	global $babBody, $babDB;

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
					$iMajorVersion = 1;
					$iMinorVersion = 0;
					$iIdProject = bab_createProject($iIdProjectSpace, $sName, $sDescription, $iMajorVersion, $iMinorVersion);
					
					if(false !== $iIdProject)
					{
						require_once $GLOBALS['babInstallPath'] . 'tmSendMail.php';
				
						$sProjectSpaceName = '???';
						if(bab_getProjectSpace($iIdProjectSpace, $aProjectSpace))
						{
							$sProjectSpaceName = $aProjectSpace['name'];
						}
						
						$iIdEvent = BAB_TM_EV_PROJECT_CREATED;
						$g_aEmailMsg =& $GLOBALS['g_aEmailMsg'];
						$sSubject = $g_aEmailMsg[$iIdEvent]['subject'];
						$sBody = $g_aEmailMsg[$iIdEvent]['body'];
						
						$sBody = sprintf($sBody, $sName, $sProjectSpaceName);
						$iIdTask = 0;
						sendNotice($iIdProjectSpace, $iIdProject, $iIdTask, $iIdEvent, $sSubject, $sBody);
					}
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
			{
				$sProjectName = '???';
				if(bab_getProject($iIdProject, $aProject))
				{
					$sProjectName = $aProject['name'];
				}
					
				$sProjectSpaceName = '???';
				if(bab_getProjectSpace($iIdProjectSpace, $aProjectSpace))
				{
					$sProjectSpaceName = $aProjectSpace['name'];
				}
				
				require_once $GLOBALS['babInstallPath'] . 'tmSendMail.php';
				$iIdEvent = BAB_TM_EV_PROJECT_DELETED;
				$g_aEmailMsg =& $GLOBALS['g_aEmailMsg'];
				$sSubject = $g_aEmailMsg[$iIdEvent]['subject'];
				$sBody = $g_aEmailMsg[$iIdEvent]['body'];
				
				$sBody = sprintf($sBody, $sProjectName, $sProjectSpaceName);
				$iIdTask = 0;
				sendNotice($iIdProjectSpace, $iIdProject, $iIdTask, $iIdEvent, $sSubject, $sBody);
			}

			require_once($GLOBALS['babInstallPath'] . 'admin/acl.php');

			if(bab_deleteProject($iIdProject))
			{
				bab_updateRefCount(BAB_TSKMGR_PROJECTS_SPACES_TBL, $iIdProjectSpace, '- \'1\'');
				bab_deleteAllNoticeEvent($iIdProjectSpace, $iIdProject);
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
			
		$oTmCtx =& getTskMgrContext();
		$aDPC = $oTmCtx->getConfiguration();
				
		if(!is_null($aDPC))
		{
			global $babDB;
			$result = bab_selectTasksList($iIdProject);
			if(false != $result && $babDB->db_num_rows($result) > 0)
			{
				$aConfiguration['tasksNumerotation'] = $aDPC['tasksNumerotation'];
			}
		}
		
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
				bab_createProjectCommentary($iIdProject, $sCommentary);
			}
			else 
			{
				bab_updateProjectCommentary($iIdCommentary, $sCommentary);
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
		bab_deleteProjectCommentary($iIdCommentary);
	}
	else 
	{
		bab_debug('addModifyProjectCommentary: acces denied');
	}
}

function addModifyTask()
{
	global $babInstallPath;
	require_once($babInstallPath . 'tmTaskClasses.php');
	
	$oTmCtx =& getTskMgrContext();
	$iUserProfil = $oTmCtx->getUserProfil();
	$bIsOk = false;
	
	$oTaskValidator = null;
	
	$iClass = (int) tskmgr_getVariable('iClassType', BAB_TM_TASK);
	
	if(0 == $oTmCtx->m_iIdTask && (BAB_TM_PROJECT_MANAGER == $iUserProfil || BAB_TM_PERSONNAL_TASK_OWNER == $iUserProfil))
	{
		$oTaskValidator =& new BAB_TM_MgrTaskCreatorValidator();
	}
	else if(0 != $oTmCtx->m_iIdTask && BAB_TM_UNDEFINED != $iUserProfil)
	{
		$oTaskValidator =& new BAB_TM_TaskUpdaterValidator();
	}
	else
	{
		bab_debug('access denied');		
	}
	
	if(!is_null($oTaskValidator))
	{
//		require_once $GLOBALS['babInstallPath'] . 'tmSendMail.php';
//		bab_debug($g_aEmailMsg);
//*
		$bIsOk = $oTaskValidator->save();
		
		if($bIsOk)
		{
			bab_debug(__FUNCTION__ . ' sTask ==> ' . $oTaskValidator->m_sTaskNumber . ' is valid');
		}
		else
		{
			bab_debug(__FUNCTION__ . ' sTask ==> ' . $oTaskValidator->m_sTaskNumber . ' invalid');
		}
//*/
/*
		$iIdProject = (int) tskmgr_getVariable('iIdProject', 0);
		bab_test($iIdProject);
//*/
	}
	
	//Pour être en création
	if(!$bIsOk && isset($_POST['iIdTask']) && 0 == $_POST['iIdTask'])
	{
		unset($_POST['iIdTask']);
	}
}


function deleteTask()
{
	$oTmCtx =& getTskMgrContext();
	$iIdProjectSpace = $oTmCtx->getIdProjectSpace();
	$iIdProject = $oTmCtx->getIdProject();
	$iIdTask = $oTmCtx->getIdTask();
	$iUserProfil = $oTmCtx->getUserProfil();

	$sTaskNumber = '';
	if((BAB_TM_PROJECT_MANAGER == $iUserProfil || BAB_TM_PERSONNAL_TASK_OWNER == $iUserProfil) && 
		isTaskDeletable($iIdTask, $iUserProfil, $sTaskNumber))
	{
		bab_startDependingTask($iIdProjectSpace, $iIdProject, $iIdTask, BAB_TM_END_TO_START);
		
		{
			$sProjectSpaceName = '???';
			if(bab_getProjectSpace($iIdProjectSpace, $aProjectSpace))
			{
				$sProjectSpaceName = $aProjectSpace['name'];
			}
			
			$sProjectName = '???';
			if(bab_getProject($iIdProject, $aProject))
			{
				$sProjectName = $aProject['name'];
			}
			
			require_once $GLOBALS['babInstallPath'] . 'tmSendMail.php';
			$iIdEvent = BAB_TM_EV_TASK_DELETED;
			$g_aEmailMsg =& $GLOBALS['g_aEmailMsg'];
			$sSubject = $g_aEmailMsg[$iIdEvent]['subject'];
			$sBody = $g_aEmailMsg[$iIdEvent]['body'];
			
			$sBody = sprintf($sBody, $sTaskNumber, $sProjectName, $sProjectSpaceName, 
				bab_getUserName($GLOBALS['BAB_SESS_USERID']));
			//bab_debug($sBody);
			sendNotice($iIdProjectSpace, $iIdProject, $iIdTask, $iIdEvent, $sSubject, $sBody);
		}
		
		bab_deleteTask($iIdTask);
	}
}

function stopTask()
{
	$oTmCtx =& getTskMgrContext();
	$iIdProjectSpace = $oTmCtx->getIdProjectSpace();
	$iIdProject = $oTmCtx->getIdProject();
	$iIdTask = $oTmCtx->getIdTask();
	$iUserProfil = $oTmCtx->getUserProfil();

	$sTaskNumber = '';
	if((BAB_TM_PROJECT_MANAGER == $iUserProfil || BAB_TM_PERSONNAL_TASK_OWNER == $iUserProfil) && 
		isTaskStoppable($iIdTask, $iUserProfil, $sTaskNumber))
	{
		if(bab_getTask($iIdTask, $aTask))
		{
			$aTask['sEndDate'] = date("Y-m-d");
			$aTask['iParticipationStatus'] = BAB_TM_ENDED;
	
			if(bab_updateTask($iIdTask, $aTask))
			{
				bab_startDependingTask($iIdProjectSpace, $iIdProject, $iIdTask, BAB_TM_END_TO_START);
	
				{
					$sProjectSpaceName = '???';
					if(bab_getProjectSpace($iIdProjectSpace, $aProjectSpace))
					{
						$sProjectSpaceName = $aProjectSpace['name'];
					}
					
					$sProjectName = '???';
					if(bab_getProject($iIdProject, $aProject))
					{
						$sProjectName = $aProject['name'];
					}
					
					require_once $GLOBALS['babInstallPath'] . 'tmSendMail.php';
					$iIdEvent = BAB_TM_EV_TASK_UPDATED_BY_MGR;
					$g_aEmailMsg =& $GLOBALS['g_aEmailMsg'];
					$sSubject = $g_aEmailMsg[$iIdEvent]['subject'];
					$sBody = $g_aEmailMsg[$iIdEvent]['body'];
					
					$sBody = sprintf($sBody, $sTaskNumber, $sProjectName, $sProjectSpaceName, 
						bab_getUserName($GLOBALS['BAB_SESS_USERID']));
					//bab_debug($sBody);
					sendNotice($iIdProjectSpace, $iIdProject, $iIdTask, $iIdEvent, $sSubject, $sBody);
				}
			}
		}
	}
}

function createSpecificFieldInstance()
{
	bab_debug('createSpecificFieldInstance()');
	
	$iIdProject = (int) tskmgr_getVariable('iIdProject', 0);
	$iIdTask = (int) tskmgr_getVariable('iIdTask', 0);

	if(bab_isAccessValid(BAB_TSKMGR_PROJECTS_MANAGERS_GROUPS_TBL, $iIdProject) && 0 < $iIdTask)
	{
		//bab_createSpecificFieldInstance($iIdTask, $iIdSpecificField);
	}
	else 
	{
		bab_debug('createSpecificFieldInstance: acces denied');
	}
}


bab_cleanGpc();



/* main */

/*
$context =& getTskMgrContext();
if(false == $context->isUserProjectVisualizer())
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
		
	case BAB_TM_ACTION_ADD_TASK:
	case BAB_TM_ACTION_MODIFY_TASK:
		addModifyTask();
		break;

	case BAB_TM_ACTION_DELETE_TASK:
		deleteTask();
		break;
		
	case BAB_TM_ACTION_STOP_TASK:
		stopTask();
		break;
		
	case BAB_TM_ACTION_CREATE_SPECIFIC_FIELD_INSTANCE:
		createSpecificFieldInstance();
		break;
		
	case BAB_TM_ACTION_MODIFY_NOTICE_EVENT:
		require_once($GLOBALS['babInstallPath'] . 'tmNoticesFunc.php');
		modifyNoticeEvent();
		break;
}


$idx = isset($_POST['idx']) ? $_POST['idx'] : (isset($_GET['idx']) ? $_GET['idx'] : BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_LIST);

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
/*		
	case BAB_TM_IDX_DISPLAY_PROJECTS_LIST:
		displayProjectsList();
		break;
//*/
		
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
		
	case BAB_TM_IDX_DISPLAY_DELETE_TASK_FORM:
		displayDeleteTaskForm();
		break;
		
	case BAB_TM_IDX_DISPLAY_STOP_TASK_FORM:
		displayStopTaskForm();
		break;
//*//
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