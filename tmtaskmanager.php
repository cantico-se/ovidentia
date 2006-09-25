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
	add_item_menu();

	$bfp->set_anchor($GLOBALS['babUrlScript'] . '?tg=usrTskMgr&idx=' . BAB_TM_IDX_DISPLAY_WORKING_HOURS_FORM , '', bab_translate("Working hours"));

	$oTmCtx =& getTskMgrContext();
	$aPersTaskCreator = bab_getUserIdObjects(BAB_TSKMGR_PERSONNAL_TASK_CREATOR_GROUPS_TBL);
	if(count($aPersTaskCreator) > 0 && isset($aPersTaskCreator[$oTmCtx->getIdDelegation()]))
	{
		$bfp->set_anchor($GLOBALS['babUrlScript'] . '?tg=usrTskMgr&idx=' . BAB_TM_IDX_DISPLAY_PERSONNAL_TASK_CONFIGURATION_FORM , '', bab_translate("Personnals tasks configuration"));
	}
	
	$babBody->babecho(bab_printTemplate($bfp, 'tmCommon.html', 'displayMenu'));
}

function displayProjectsSpacesList()
{
	global $babBody, $babDB;
	$babBody->title = bab_translate("Projects spaces list");
	add_item_menu();

	require_once $GLOBALS['babInstallPath'] . 'utilit/tree.php';

	class BAB_TM_List extends bab_TreeView
	{
		var $m_sUrlBase 			= '';

		var $m_iIdSpaceElement		= 'sn_0';
		var $m_iIdPersTaskElement	= 'sn_1';

		var $m_sn		= 'sn'; 	// static node
		var $m_snps		= 'snps';	// static node project space
		
		var $m_dn		= 'dn';		// dynamic node
		var $m_dnps		= 'dnps';	// dynamic node project space
		var $m_dnp 		= 'dnp';	// dynamic node project
		
		var $m_dnt		= 'dnt';	// dynamic node task
		
		function BAB_TM_List()
		{
			parent::bab_TreeView('myTreeView');
			
			$sTg = bab_rp('tg', 'admTskMgr');
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
			$oSpaceElement->setIcon($GLOBALS['babSkinPath'] . 'images/Puces/internet.png');
			
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
               		$oProjectSpaceElement->setIcon($GLOBALS['babSkinPath'] . 'images/Puces/file-manager.png');
               		
               		$this->insertProject($datas['id'], $oProjectSpaceElement);
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
					$bIsManager = bab_isAccessValid(BAB_TSKMGR_DEFAULT_PROJECTS_MANAGERS_GROUPS_TBL, $iIdProjectSpace);
					if(!$bIsManager)
					{
 						$bIsManager = bab_isAccessValid(BAB_TSKMGR_PROJECTS_MANAGERS_GROUPS_TBL, $datas['id']);
					}
					
					$isAccessValid = ($bIsCreator || $bIsManager);

$iTaskCount = (int) bab_getTaskCount($datas['id']);					
					
					$sProjectUrl = ($bIsManager) ? $this->getUrl(BAB_TM_IDX_DISPLAY_TASK_LIST, $iIdProjectSpace, $datas['id']) . '&isProject=1' : null;
					$oProjectElement =& $this->createElement($this->m_dnp . '_' . $datas['id'], $this->m_dnp, $datas['name'] . ' (' . $iTaskCount . ')', 
						$datas['description'], $sProjectUrl);
               		$this->appendElement($oProjectElement, $this->m_dnps . '_' . $iIdProjectSpace);
               		
$oProjectElement->setIcon($GLOBALS['babSkinPath'] . 'images/nodetypes/folder.png');
               		
               		if($isAccessValid)
               		{
						$oProjectElement->addAction('Rights',
			               bab_translate('Rights'), $GLOBALS['babSkinPath'] . 'images/Puces/agent.png', 
			               $this->getUrl(BAB_TM_IDX_DISPLAY_PROJECT_RIGHTS_FORM, $iIdProjectSpace, $datas['id']), '');
               		}
               		
               		if($bIsManager)
               		{
               			$sGanttViewUrl = $this->getUrl(BAB_TM_IDX_DISPLAY_GANTT_CHART, $iIdProjectSpace, $datas['id']);
						$oProjectElement->addAction('GanttView',
			               bab_translate('Gantt view'), $GLOBALS['babSkinPath'] . 'images/Puces/schedule.png', 
			               'javascript:bab_popup(\'' . $sGanttViewUrl . '\')' , '');
						$oProjectElement->addAction('Configuration',
			               bab_translate('Project properties'), $GLOBALS['babSkinPath'] . 'images/Puces/package_settings.png', 
			               $this->getUrl(BAB_TM_IDX_DISPLAY_PROJECT_PROPERTIES_FORM, $iIdProjectSpace, $datas['id']), '');
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

if(0 === $iTaskCount)		               
{
	$oProjectElement->addAction('Task_list',
	   bab_translate('Add a Task'), $GLOBALS['babSkinPath'] . 'images/Puces/edit_remove.png', 
	   $this->getUrl(BAB_TM_IDX_DISPLAY_DELETE_PROJECT_FORM, $iIdProjectSpace, $datas['id']) 
	   . '&sFromIdx=' . BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_LIST, '');
}

						$oProjectElement->addAction('Task_list',
						   bab_translate('Add a Task'), $GLOBALS['babSkinPath'] . 'images/Puces/edit_add.png', 
						   $this->getUrl(BAB_TM_IDX_DISPLAY_TASK_FORM, $iIdProjectSpace, $datas['id']) 
						   . '&sFromIdx=' . BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_LIST, '');
               		}
               		
               		//$this->insertTaskIntoProject($iIdProjectSpace, $datas['id']);
               		
		            /*   
					$oProjectElement->addAction('Task_list',
					   bab_translate('Tasks list'), $GLOBALS['babSkinPath'] . 'images/Puces/windowlist.png', 
					   $this->getUrl(BAB_TM_IDX_DISPLAY_TASK_LIST, $iIdProjectSpace, $datas['id']), '');
					//*/
				}
			}	
		}
		
		/*
		function insertTaskIntoProject($iIdProjectSpace, $iIdProject)
		{
			$result = bab_selectTasksList($iIdProject);
			if(false != $result)
			{
				global $babDB;
				
				$iIndex = 0;
				$iNumRows = $babDB->db_num_rows($result);
				
				$bIsManager = bab_isAccessValid(BAB_TSKMGR_PROJECTS_MANAGERS_GROUPS_TBL, $iIdProject);
				
				while( $iIndex < $iNumRows && false != ($datas = $babDB->db_fetch_array($result)) )
				{
					bab_getTaskResponsibles($datas['id'], $aTaskResponsible);
					$isAccessValid = (isset($aTaskResponsible[$GLOBALS['BAB_SESS_USERID']]) || $bIsManager);

					$sTaskUrl = ($isAccessValid) ? 
						$this->getUrl(BAB_TM_IDX_DISPLAY_TASK_FORM, $iIdProjectSpace, $iIdProject) . 
						'&iIdTask=' . $datas['id'] . '&sFromIdx=' . BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_LIST : null;
					
					$oTaskElement =& $this->createElement($this->m_dnt . '_' . $datas['id'], $this->m_dnt, $datas['shortDescription'], 
						$datas['description'], $sTaskUrl);
						
               		$this->appendElement($oTaskElement, $this->m_dnp . '_' . $iIdProject);

$oTaskElement->setIcon($GLOBALS['babSkinPath'] . 'images/nodetypes/file.png');
				}
			}	
		}
		//*/
		
		function createPersonnalTaskSubTree()
		{
			$oTmCtx =& getTskMgrContext();
			
//			if(BAB_TM_PERSONNAL_TASK_OWNER === $oTmCtx->getUserProfil())
			{
				$iTaskCount = bab_getTaskCount(0, $GLOBALS['BAB_SESS_USERID']);
				
				$oPersTaskElement =& $this->createElement($this->m_iIdPersTaskElement, 'snps', bab_translate("Personnal(s) task(s)") . '(' . $iTaskCount . ')', 'description', null);
				
       			$sGanttViewUrl = $this->getUrl(BAB_TM_IDX_DISPLAY_GANTT_CHART, 0, 0);
				$oPersTaskElement->addAction('GanttView',
	               bab_translate('Gantt view'), $GLOBALS['babSkinPath'] . 'images/Puces/schedule.png', 
	               'javascript:bab_popup(\'' . $sGanttViewUrl . '\')' , '');
				$oPersTaskElement->addAction('Configuration',
	               bab_translate('Configuration'), $GLOBALS['babSkinPath'] . 'images/Puces/package_settings.png', 
	               $this->getUrl(BAB_TM_IDX_DISPLAY_PERSONNAL_TASK_CONFIGURATION_FORM, 0, 0), '');
				$oPersTaskElement->addAction('Specific_fields',
	               bab_translate('Specific fields'), $GLOBALS['babSkinPath'] . 'images/Puces/list.png', 
	               $this->getUrl(BAB_TM_IDX_DISPLAY_SPECIFIC_FIELD_LIST, 0, 0) . '&iIdUser=' . $GLOBALS['BAB_SESS_USERID'], '');
				$oPersTaskElement->addAction('Categories list',
	               bab_translate('Categories_list'), $GLOBALS['babSkinPath'] . 'images/Puces/kwikdisk.png', 
	               $this->getUrl(BAB_TM_IDX_DISPLAY_CATEGORIES_LIST, 0, 0) . '&iIdUser=' . $GLOBALS['BAB_SESS_USERID'], '');
				$oPersTaskElement->addAction('Add',
	               bab_translate('Add'), $GLOBALS['babSkinPath'] . 'images/Puces/edit_add.png', 
	               $this->getUrl(BAB_TM_IDX_DISPLAY_TASK_FORM, 0, 0) . '&sFromIdx=' . BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_LIST, '');

			    $this->appendElement($oPersTaskElement, null);
			    //$this->insertPersonnalTask();
			}
		}
		
		/*
		function insertPersonnalTask()
		{
			$result = bab_selectPersonnalTasksList();
			if(false != $result)
			{
				global $babDB;
				
				$iIndex = 0;
				$iNumRows = $babDB->db_num_rows($result);
				
				while( $iIndex < $iNumRows && false != ($datas = $babDB->db_fetch_array($result)) )
				{
					$sTaskUrl = $this->getUrl(BAB_TM_IDX_DISPLAY_TASK_FORM, 0, 0) . '&iIdTask=' . $datas['id'] .
					 	'&sFromIdx=' . BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_LIST;
					
					$oTaskElement =& $this->createElement($this->m_dnt . '_' . $datas['id'], $this->m_dnt, $datas['shortDescription'], 
						$datas['description'], $sTaskUrl);
						
               		$this->appendElement($oTaskElement, $this->m_iIdPersTaskElement);
				}
			}	
		}
		//*/
	}
	
	//*
	$list = new BAB_TM_List();
	$GLOBALS['babBody']->babecho($list->printTemplate());
	//*/
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
				
				$this->set_data('sName', bab_rp('sName', ''));
				$this->set_data('sDescription', bab_rp('sDescription', ''));
				$this->set_data('iIdProjectSpace', $iIdProjectSpace);
				$this->set_data('iIdProject', $iIdProject);
				$this->set_data('bIsDeletable', bab_isProjectDeletable($iIdProject));

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
	
	$bIsCreator = bab_isAccessValid(BAB_TSKMGR_PROJECT_CREATOR_GROUPS_TBL, $iIdProjectSpace);
//	$bIsManager = bab_isAccessValid(BAB_TSKMGR_PROJECTS_MANAGERS_GROUPS_TBL, $iIdProject);
	$bIsManager = false;
	if(!bab_isAccessValid(BAB_TSKMGR_DEFAULT_PROJECTS_MANAGERS_GROUPS_TBL, $iIdProjectSpace))
	{
			$bIsManager = bab_isAccessValid(BAB_TSKMGR_PROJECTS_MANAGERS_GROUPS_TBL, $iIdProject);
	}
	else 
	{
		$bIsManager = true;
	}
	
	$isAccessValid = ($bIsCreator || $bIsManager);
	
	if($isAccessValid)
	{
		$itemMenu = array(
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

	if(bab_isAccessValid(BAB_TSKMGR_PROJECTS_MANAGERS_GROUPS_TBL, $iIdProject))
	{
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
	else 
	{
		$GLOBALS['babBody']->msgerror = bab_translate("You are not a projet manager");
	}
}


function displayProjectPropertiesForm()
{
	global $babBody;

	$oTmCtx =& getTskMgrContext();
	
	$iIdProjectSpace = $oTmCtx->getIdProjectSpace();
	$iIdProject = $oTmCtx->getIdProject();

	if(bab_isAccessValid(BAB_TSKMGR_PROJECTS_MANAGERS_GROUPS_TBL, $iIdProject))
	{
		if(0 != $iIdProjectSpace)
		{	
			$itemMenu = array(		
				array(
					'idx' => BAB_TM_IDX_DISPLAY_PROJECT_PROPERTIES_FORM,
					'mnuStr' => bab_translate("Project properties"),
					'url' => $GLOBALS['babUrlScript'] . '?tg=usrTskMgr&idx=' . BAB_TM_IDX_DISPLAY_PROJECT_PROPERTIES_FORM . 
					'&iIdProjectSpace=' . $iIdProjectSpace . '&iIdProject=' . $iIdProject)
			);
			add_item_menu($itemMenu);
	
			$babBody->title = bab_translate("Project properties");
			
			class BAB_ProjectProperties extends BAB_BaseFormProcessing
			{
				function BAB_ProjectProperties($iIdProjectSpace, $iIdProject)
				{
					parent::BAB_BaseFormProcessing();
		
					$this->set_caption('sName', bab_translate("Name"));
					$this->set_caption('sDescription', bab_translate("Description"));
					$this->set_caption('update', bab_translate("Update"));
					$this->set_caption('delete', bab_translate("Delete"));
					$this->set_caption('Project', bab_translate("Project"));
					$this->set_caption('Configuration', bab_translate("Configuration"));
					$this->set_caption('taskUpdate', bab_translate("Task updated by task responsible"));
					$this->set_caption('notice', bab_translate("Reminder before project expiration"));
					$this->set_caption('taskNumerotation', bab_translate("Task numerotation"));
					$this->set_caption('emailNotice', bab_translate("Email notification"));
					$this->set_caption('faq', bab_translate("Task manager FAQ"));
					$this->set_caption('days', bab_translate("Day(s)"));
					
					$this->set_data('sName', bab_rp('sName', ''));
					$this->set_data('sDescription', bab_rp('sDescription', ''));
					$this->set_data('iIdProjectSpace', $iIdProjectSpace);
					$this->set_data('iIdProject', $iIdProject);
					$this->set_data('bIsDeletable', bab_isProjectDeletable($iIdProject));
					$this->set_caption('yes', bab_translate("Yes"));
					$this->set_caption('no', bab_translate("No"));
					$this->set_caption('save', bab_translate("Save"));
	
					$this->set_data('update_idx', BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_LIST);
					$this->set_data('delete_idx', BAB_TM_IDX_DISPLAY_DELETE_PROJECT_FORM);
					$this->set_data('update_action', BAB_TM_ACTION_MODIFY_PROJECT_PROPERTIES);
					$this->set_data('delete_action', '');
					
					$this->set_data('tg', 'usrTskMgr');
					
					$this->set_data('tmCode', '');
					$this->set_data('tmValue', '');
					$this->set_data('tnSelected', '');
					
					$this->set_data('isTaskUpdatedByMgr', true);
					$this->set_data('endTaskReminder', 5);
					$this->set_data('taskNumerotation', BAB_TM_SEQUENTIAL);
					$this->set_data('isEmailNotice', true);
					$this->set_data('faqUrl', '');
					$this->set_data('iIdConfiguration', -1);

				
		
					$this->set_data('aTaskNumerotation', array(
						BAB_TM_MANUAL => bab_translate("Manual"), BAB_TM_SEQUENTIAL => bab_translate("Sequential (automatique)"),
						BAB_TM_YEAR_SEQUENTIAL => bab_translate("Year + Sequential (automatique)"),
						BAB_TM_YEAR_MONTH_SEQUENTIAL => bab_translate("Year + Month + Sequential (automatique)")));
						
					$this->set_data('yes', BAB_TM_YES);
					$this->set_data('no', BAB_TM_NO);					
					
					$this->getProjectConfiguration();
					
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
				
				function getProjectConfiguration()
				{					
					$oTmCtx =& getTskMgrContext();
					$iIdProject = $oTmCtx->getIdProject();
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
			
			$oPrjP = & new BAB_ProjectProperties($iIdProjectSpace, $iIdProject);
			$babBody->babecho(bab_printTemplate($oPrjP, 'tmUser.html', 'projectProperties'));
			
		}
		else 
		{
			$GLOBALS['babBody']->msgerror = bab_translate("Invalid project space");
		}
	}	
	else 
	{
		$GLOBALS['babBody']->msgerror = bab_translate("You are not a projet manager");
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
	$iIdTask = $oTmCtx->getIdTask();

	$iUserProfil = $oTmCtx->getUserProfil();

	if(BAB_TM_PROJECT_MANAGER == $iUserProfil || BAB_TM_PERSONNAL_TASK_OWNER == $iUserProfil)
//	if(bab_isAccessValid(BAB_TSKMGR_PROJECTS_MANAGERS_GROUPS_TBL, $iIdProject))
	{
		$iIdCommentary = bab_rp('iIdCommentary', 0);
		$isPopUp = bab_rp('isPopUp', 0);
		$tab_caption = ($iIdCommentary == 0) ? bab_translate("Add a commentary") : bab_translate("Edition of a commentary");
		$babBody->title = $tab_caption;
	
		
		$oBf = & new BAB_BaseFormProcessing();
		
		$oBf->set_caption('add', bab_translate("Add"));
		$oBf->set_caption('modify', bab_translate("Modify"));
		$oBf->set_caption('delete', bab_translate("Delete"));

		$oBf->set_data('iIdProjectSpace', $iIdProjectSpace);
		$oBf->set_data('iIdCommentary', $iIdCommentary);
		$oBf->set_data('iIdProject', $iIdProject);
		$oBf->set_data('iIdTask', $iIdTask);
		$oBf->set_data('isPopUp', $isPopUp);
		$oBf->set_data('delAction', '');
		$oBf->set_data('tg', 'usrTskMgr');
		
		$success = false;
		
		if(0 == $isPopUp)
		{
			$itemMenu = array(		
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
			
			$oBf->set_data('addIdx', BAB_TM_IDX_DISPLAY_PROJECT_COMMENTARY_LIST);
			$oBf->set_data('addAction', BAB_TM_ACTION_ADD_PROJECT_COMMENTARY);
			$oBf->set_data('modifyIdx', BAB_TM_IDX_DISPLAY_PROJECT_COMMENTARY_LIST);
			$oBf->set_data('modifyAction', BAB_TM_ACTION_MODIFY_PROJECT_COMMENTARY);
			$oBf->set_data('delIdx', BAB_TM_IDX_DISPLAY_DELETE_PROJECT_COMMENTARY);
			
			$success = bab_getProjectCommentary($iIdCommentary, $sCommentary);
		}
		else 
		{
			$oBf->set_data('addIdx', BAB_TM_IDX_DISPLAY_TASK_COMMENTARY_LIST);
			$oBf->set_data('addAction', BAB_TM_ACTION_ADD_TASK_COMMENTARY);
			$oBf->set_data('modifyIdx', BAB_TM_IDX_DISPLAY_TASK_COMMENTARY_LIST);
			$oBf->set_data('modifyAction', BAB_TM_ACTION_MODIFY_TASK_COMMENTARY);
			$oBf->set_data('delIdx', '');
			$oBf->set_data('delAction', BAB_TM_ACTION_DELETE_TASK_COMMENTARY);
			
			$success = bab_getTaskCommentary($iIdCommentary, $sCommentary);
		}


		$oBf->set_data('commentary', '');
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
		$iIdCommentary = bab_rp('iIdCommentary', 0);
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
	$isProject = (int) bab_rp('isProject', 0);
	
	if(1 === $isProject && !bab_isAccessValid(BAB_TSKMGR_PROJECTS_MANAGERS_GROUPS_TBL, (int) bab_rp('iIdProject', 0)))
	{
		$GLOBALS['babBody']->msgerror = bab_translate("You are not a projects manager");
		return false;
	}
	
	global $babBody;
	$oTmCtx =& getTskMgrContext();
	$babBody->title = bab_translate("Task list");
	add_item_menu();
	
	
	class BAB_TM_TaskFilterForm extends BAB_BaseFormProcessing
	{
		var $m_aTasksFilter;
		var $m_aTasksTypeFilter;
		
		var $m_aSelectedFilterValues;
		var $m_sGanttViewUrl;
		
		function BAB_TM_TaskFilterForm()
		{
			$this->set_data('tg', bab_rp('tg', 'usrTskMgr'));				
			$this->set_data('idx', bab_rp('tg', ''));				
			$this->set_caption('sAddTask', bab_translate("Add a task"));
			$this->set_caption('sFilter', bab_translate("Filter"));
			
			$this->set_data('sFilterIdx', BAB_TM_IDX_DISPLAY_TASK_LIST);
			$this->set_data('sFilterAction', '');
			$this->set_data('sAddTaskIdx', BAB_TM_IDX_DISPLAY_TASK_FORM);
			$this->set_data('sAddTaskAction', '');
			$this->set_data('bIsAddButton', false);

			$this->set_data('isProjectDisplayed', (0 === (int) bab_rp('isProject', 0)));
			$this->get_data('isProjectDisplayed', $isProjectDisplayed);
			
			$this->m_aSelectedFilterValues = null;
			bab_getTaskListFilter($GLOBALS['BAB_SESS_USERID'], $this->m_aSelectedFilterValues);
			
			$this->m_aSelectedFilterValues['iIdProject'] = ($isProjectDisplayed ? 
				(int) bab_rp('oTaskFilter', $this->m_aSelectedFilterValues['iIdProject']) : (int) bab_rp('iIdProject', 0));
			$this->m_aSelectedFilterValues['iTaskClass'] = (int) bab_rp('oTaskTypeFilter', $this->m_aSelectedFilterValues['iTaskClass']);
			
			$this->set_data('iIdProjectSpace', (int) bab_rp('iIdProjectSpace', 0));
			$this->set_data('iIdProject', $this->m_aSelectedFilterValues['iIdProject']);
			
			//Task filter (-1 ==> All, -2 ==> personnal task)
			$this->set_caption('sProject', (0 === (int) bab_rp('isProject', 0) ? bab_translate("Project") : ''));
			$this->set_data('iTaskFilterValue', -1);
			$this->set_data('sTaskFilterSelected', '');
			$this->set_data('sTaskFilterName', '');
			$this->set_data('iSelectedTaskFilter', $this->m_aSelectedFilterValues['iIdProject']);

			$this->m_aTasksFilter = array(
				array('value' => -1, 'text' => bab_translate("All")));
			
			//Task type filter	
			$this->set_caption('sTaskType', bab_translate("Task type"));
			$this->set_data('iTaskTypeFilterValue', -1);
			$this->set_data('sTaskTypeFilterSelected', '');
			$this->set_data('sTaskTypeFilterName', '');
			$this->set_data('iSelectedTaskTypeFilter', $this->m_aSelectedFilterValues['iTaskClass']);

			$this->m_aTasksTypeFilter = array(
				array('value' => -1, 'text' => bab_translate("All")),
				array('value' => BAB_TM_TASK, 'text' => bab_translate("Task")),
				array('value' => BAB_TM_CHECKPOINT, 'text' => bab_translate("Checkpoint")),
				array('value' => BAB_TM_TODO, 'text' => bab_translate("ToDo"))
			);
				
			$this->initTaskFilter();
			
			
			if(-1 != $this->m_aSelectedFilterValues['id'])
			{
				bab_updateTaskListFilter($GLOBALS['BAB_SESS_USERID'], $this->m_aSelectedFilterValues);
			}
			else 
			{
				bab_createTaskListFilter($GLOBALS['BAB_SESS_USERID'], $this->m_aSelectedFilterValues);
			}
		}
		
		function initTaskFilter()
		{
			$oTmCtx =& getTskMgrContext();
			$res = bab_selectProjectListByDelegation($oTmCtx->getIdDelegation());
			
			if(false != $res)
			{
				global $babDB;
				$iNumRows = $babDB->db_num_rows($res);	
				$iIndex = 0;
				while($iIndex < $iNumRows && false != ($datas = $babDB->db_fetch_assoc($res)))
				{
					if(bab_isAccessValid(BAB_TSKMGR_PROJECTS_VISUALIZERS_GROUPS_TBL, $datas['iIdProject']))
					{
						if(bab_isAccessValid(BAB_TSKMGR_PROJECTS_MANAGERS_GROUPS_TBL, $datas['iIdProject']))
						{
							$this->m_aTasksFilter[] = array('value' => $datas['iIdProject'], 
								'text' => $datas['sProjectName']);
						}
					}
					$iIndex++;
				}
			}
			
			$aPersTaskCreator = bab_getUserIdObjects(BAB_TSKMGR_PERSONNAL_TASK_CREATOR_GROUPS_TBL);
			if(count($aPersTaskCreator) > 0 && isset($aPersTaskCreator[$oTmCtx->getIdDelegation()]))
			{
				$this->m_aTasksFilter[] = array('value' => -2, 
					'text' => bab_translate("Personnal task"));
			}
			
			if(count($this->m_aTasksFilter) >= 2)
			{
				$this->set_data('bIsAddButton', true);
			}
			
			reset($this->m_aTasksFilter);
			//bab_debug($this->m_aTasksFilter);
		}
		
		function getNextTaskFilter()
		{
			$datas = each($this->m_aTasksFilter);
			if(false != $datas)
			{
				$this->get_data('iSelectedTaskFilter', $iSelectedTaskFilter);
				$this->set_data('sTaskFilterSelected', ($iSelectedTaskFilter == $datas['value']['value']) ? 'selected="selected"' : '');
				
				$this->set_data('iTaskFilterValue', $datas['value']['value']);				
				$this->set_data('sTaskFilterName', $datas['value']['text']);
				
				return true;				
			}
			return false;
		}
		
		function getNextTaskTypeFilter()
		{
			$datas = each($this->m_aTasksTypeFilter);
			if(false != $datas)
			{
				$this->get_data('iSelectedTaskTypeFilter', $iSelectedTaskTypeFilter);
				$this->set_data('sTaskTypeFilterSelected', ($iSelectedTaskTypeFilter == $datas['value']['value']) ? 'selected="selected"' : '');
				
				$this->set_data('iTaskTypeFilterValue', $datas['value']['value']);				
				$this->set_data('sTaskTypeFilterName', $datas['value']['text']);
				
				return true;				
			}
			return false;
		}
		
		function printTemplate()
		{
			return bab_printTemplate($this, 'tmUser.html', 'taskListFilter');
		}
	}
	
	$oTaskFilterForm = new BAB_TM_TaskFilterForm();
	$GLOBALS['babBody']->babecho($oTaskFilterForm->printTemplate());
	$iTaskFilter =& $oTaskFilterForm->m_aSelectedFilterValues['iIdProject'];
	$iTaskClass =& $oTaskFilterForm->m_aSelectedFilterValues['iTaskClass'];

	global $babUrlScript;
	$sGanttViewUrl = $babUrlScript . '?tg=usrTskMgr&idx=' . BAB_TM_IDX_DISPLAY_GANTT_CHART;
	
	$aFilters = array();
	if(-1 != $iTaskFilter)
	{
		//iTaskFilter (-1 ==> All, -2 ==> personnal task)
		if(-2 == $iTaskFilter)
		{
			$aFilters['isPersonnal'] = BAB_TM_YES;
			$sGanttViewUrl .= '&isPersonnal=' . BAB_TM_YES;
		}
		else 
		{
			$aFilters['iIdProject'] = $iTaskFilter;
			$sGanttViewUrl .= '&iIdProject=' . $aFilters['iIdProject'];
		}
	}
		
	if(-1 != $iTaskClass)
	{
		$aFilters['iTaskClass'] = $iTaskClass;
		$sGanttViewUrl .= '&iTaskClass=' . $iTaskClass;
	}

	if(0 === $isProject)
	{
		$aFilters['iIdOwner'] = $GLOBALS['BAB_SESS_USERID'];
	}
		
	$sGanttViewUrl .= '&iIdOwner=' . $GLOBALS['BAB_SESS_USERID'];
	
	$oTaskFilterForm->m_sGanttViewUrl = urlencode($sGanttViewUrl);
	
	require_once($GLOBALS['babInstallPath'] . 'utilit/multipage.php');
	
	class BAB_TaskDS extends BAB_MySqlDataSource
	{
		var $m_sImgSrc = '';
		var $m_sImgText = '';
		
		function BAB_TaskDS($query, $iPage, $iNbRowsPerPage)
		{
			parent::BAB_MySqlDataSource($query, $iPage, $iNbRowsPerPage);
		}
		
		function getNextItem()
		{
			$datas = parent::getNextItem();
			if(false != $datas)
			{
				$datas['startDate'] = bab_shortDate(bab_mktime($datas['startDate']), false);
				$datas['endDate'] = bab_shortDate(bab_mktime($datas['endDate']), false);

				switch($datas['iClass'])
				{
					case BAB_TM_TASK:
						$this->m_sImgSrc = $GLOBALS['babSkinPath'] . 'images/Puces/kded.png';
						$this->m_sImgText = $datas['sClass'];
						$datas['sClass'] = bab_printTemplate($this, 'multipage.html', 'img');
						break;
					case BAB_TM_CHECKPOINT:
						$this->m_sImgSrc = $GLOBALS['babSkinPath'] . 'images/Puces/kmines.png';
						$this->m_sImgText = $datas['sClass'];
						$datas['sClass'] = bab_printTemplate($this, 'multipage.html', 'img');
						break;
					case BAB_TM_TODO:
						$this->m_sImgSrc = $GLOBALS['babSkinPath'] . 'images/Puces/kate.png';
						$this->m_sImgText = $datas['sClass'];
						$datas['sClass'] = bab_printTemplate($this, 'multipage.html', 'img');
						break;
				}
				
				$datas['idOwner'] = bab_getUserName($datas['idOwner']);
				
			}
			return $datas;
		}
	}

	$oMultiPage = new BAB_MultiPageBase();
	$oMultiPage->sIdx = BAB_TM_IDX_DISPLAY_TASK_LIST;

	$oMultiPage->setColumnDataSource(new BAB_TaskDS(bab_selectTaskQuery($aFilters), 
		(int) bab_rp('iPage', 1), $oMultiPage->iNbRowsPerPage));
	
	$oMultiPage->addColumnHeader(0, bab_translate("Short description"), 'sShortDescription');
	$oMultiPage->addColumnHeader(1, bab_translate("Type"), 'sClass');
	$oMultiPage->addColumnHeader(2, bab_translate("Start date"), 'startDate');
	$oMultiPage->addColumnHeader(3, bab_translate("End date"), 'endDate');
	
	if(1 === $isProject)
	{
		$oMultiPage->addColumnHeader(4, bab_translate("Responsible"), 'idOwner');
	}
		
	$sTg = bab_rp('tg', 'admTskMgr');
	$sLink = $GLOBALS['babUrlScript'] . '?tg=' . $sTg . '&idx=' . BAB_TM_IDX_DISPLAY_TASK_FORM .
		'&sFromIdx=' . BAB_TM_IDX_DISPLAY_TASK_LIST;

	//Pour les icônes
	{		
		$aDataSourceFields = array(
			array('sDataSourceFieldName' => 'iIdProjectSpace', 'sUrlParamName' => 'iIdProjectSpace'),	
			array('sDataSourceFieldName' => 'iIdProject', 'sUrlParamName' => 'iIdProject'),	
			array('sDataSourceFieldName' => 'iIdTask', 'sUrlParamName' => 'iIdTask')
		);
		
		$oMultiPage->addAction(0, bab_translate("Edit"), 
			$GLOBALS['babSkinPath'] . 'images/Puces/edit.png', 
			$sLink, $aDataSourceFields);
	}
	
	$GLOBALS['babBody']->babecho(bab_printTemplate($oTaskFilterForm, 'tmUser.html', 'ganttView'));
	$GLOBALS['babBody']->babecho($oMultiPage->printTemplate());
}


function displayTaskForm()
{
	global $babBody;

	$oTmCtx =& getTskMgrContext();
	$iIdProjectSpace = $oTmCtx->getIdProjectSpace();
	$iIdProject = $oTmCtx->getIdProject();
	
	if(0 != $iIdProject)
	{
		if(false != bab_getProject($iIdProject, $aProject))
		{
			$iIdProjectSpace = $oTmCtx->m_iIdProjectSpace = $aProject['idProjectSpace'];
		}
	}

	$bIsTaskResp = false;
	$bIsManager = false;

	if(0 !== $iIdProjectSpace && 0 !== $iIdProject)
	{
		$bIsTaskResp = bab_isAccessValid(BAB_TSKMGR_TASK_RESPONSIBLE_GROUPS_TBL, $iIdProject);
		$bIsManager = bab_isAccessValid(BAB_TSKMGR_PROJECTS_MANAGERS_GROUPS_TBL, $iIdProject);
	}
	
	/*
	bab_debug('iIdProjectSpace ==> ' . $iIdProjectSpace . ' iIdProject ==> ' . 
		$iIdProject . ' iIdTask ==> ' . $oTmCtx->m_iIdTask .
		' UserProfil ==> ' . $oTmCtx->getUserProfil() . 
		' bIsTaskResp ==> ' . (($bIsTaskResp) ? 'Yes' : 'No') .
		' bIsManager ==> ' . (($bIsManager) ? 'Yes' : 'No'));
	//*/
	
	if($bIsTaskResp || $bIsManager || BAB_TM_PERSONNAL_TASK_OWNER === $oTmCtx->getUserProfil())
	{
		$iIdTask = bab_rp('iIdTask', 0);
		$tab_caption = ($iIdTask == 0) ? bab_translate("Add a task") : bab_translate("Edition of a task");
		$babBody->title = $tab_caption;

		$itemMenu = array(		
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
/*	
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
//*/
		return true;
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
	
			
	$sFromIdx = bab_rp('sFromIdx', BAB_TM_IDX_DISPLAY_TASK_LIST);
	if(!isFromIdxValid($sFromIdx))
	{
		$sFromIdx = BAB_TM_IDX_DISPLAY_TASK_LIST;
	}
	$bf->set_data('idx', $sFromIdx);
	
	global $babInstallPath;
	require_once($babInstallPath . 'tmTaskClasses.php');
	
	$oTask = new BAB_TM_Task();
	
	if($oTask->loadFromDataBase($iIdTask))
	{
		$bf->set_data('action', BAB_TM_ACTION_DELETE_TASK);

		$bf->set_caption('warning', bab_translate("This action will delete the task and all references"));
		$bf->set_caption('message', bab_translate("Continue ?"));
		$bf->set_caption('title', bab_translate("Task number = ") . htmlentities($oTask->m_aTask['sShortDescription'], ENT_QUOTES));
	}
	else 
	{
		$bf->set_data('action', '');
		$bf->set_caption('warning', bab_translate("Cannot get the task information"));
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

function displayPersonnalTaskConfigurationForm()
{
	global $babBody;
	$oTmCtx =& getTskMgrContext();
	$aPersTaskCreator = bab_getUserIdObjects(BAB_TSKMGR_PERSONNAL_TASK_CREATOR_GROUPS_TBL);
	if(count($aPersTaskCreator) > 0 && isset($aPersTaskCreator[$oTmCtx->getIdDelegation()]))
	{
		class BAB_TM_Configuration extends BAB_BaseFormProcessing
		{
			function BAB_TM_Configuration()
			{
				parent::BAB_BaseFormProcessing();
				
				$this->set_caption('notice', bab_translate("Reminder before task expiration"));
				$this->set_caption('taskNumerotation', bab_translate("Task numerotation"));
				$this->set_caption('emailNotice', bab_translate("Email notification"));
			
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
				$this->set_data('save_action', BAB_TM_ACTION_SAVE_PERSONNAL_TASK_CONFIGURATION);
				
				$this->set_data('tmCode', '');
				$this->set_data('tmValue', '');
				$this->set_data('tnSelected', '');
				
				$this->set_data('endTaskReminder', 5);
				$this->set_data('taskNumerotation', BAB_TM_SEQUENTIAL);
				$this->set_data('isEmailNotice', true);
				
				$aCfg = array();
				$bSuccess = bab_getPersonnalTaskConfiguration($GLOBALS['BAB_SESS_USERID'], $aCfg);
				if($bSuccess)
				{
					$this->set_data('endTaskReminder', $aCfg['endTaskReminder']);
					$this->set_data('taskNumerotation', $aCfg['tasksNumerotation']);
					$this->set_data('isEmailNotice', (BAB_TM_YES == $aCfg['emailNotice']));
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
				'idx' => BAB_TM_IDX_DISPLAY_PERSONNAL_TASK_CONFIGURATION_FORM,
				'mnuStr' => bab_translate("Personnals tasks configuration"),
				'url' => $GLOBALS['babUrlScript'] . '?tg=usrTskMgr&idx=' . BAB_TM_IDX_DISPLAY_PERSONNAL_TASK_CONFIGURATION_FORM)
		);
		
		add_item_menu($itemMenu);
		$babBody->title = bab_translate("Personnals tasks configuration");
		$pjc = & new BAB_TM_Configuration();
		$babBody->babecho(bab_printTemplate($pjc, 'tmUser.html', 'PersonnalTaskConfiguration'));
	}
}

function displayGanttChart()
{
	global $babInstallPath;
	require_once($babInstallPath . 'tmGantt.php');

	$sStartDate = bab_rp('date', date("Y-m-d"));
	$oGantt = new BAB_TM_Gantt($sStartDate);
	
	die(bab_printTemplate($oGantt, 'tmUser.html', "gantt"));
/*
	global $babBody;
	$babBody->babecho(bab_printTemplate($oGantt, 'tmUser.html', "gantt2"));
//*/
}

function tskmgClosePopup()
{
	$bf = & new BAB_BaseFormProcessing();
	die(bab_printTemplate($bf, $GLOBALS['babAddonHtmlPath'] . 'tmUser.html', 'close_popup'));
}
//POST


function addModifyProject()
{
	global $babBody, $babDB;

	$oTmCtx =& getTskMgrContext();
	$iIdProjectSpace = $oTmCtx->getIdProjectSpace();
	$iIdProject = $oTmCtx->getIdProject();
	
	if(bab_isAccessValid(BAB_TSKMGR_PROJECT_CREATOR_GROUPS_TBL, $iIdProjectSpace))
	{
		$sName = trim(bab_rp('sName', ''));

		if(0 < strlen($sName))
		{
			$isValid = isNameUsedInProjectSpace(BAB_TSKMGR_PROJECTS_TBL, $iIdProjectSpace, $iIdProject, $sName);
			$sName = mysql_escape_string($sName);
			
			$sDescription = mysql_escape_string(trim(bab_rp('sDescription', '')));
			
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
	
	$iTaskUpdateByMgr = (int) bab_rp('iTaskUpdateByMgr', BAB_TM_YES);
	$iIdConfiguration = (int) bab_rp('iIdConfiguration', 0);
	$iEndTaskReminder = (int) bab_rp('iEndTaskReminder', 5);
	$iTaskNumerotation = (int) bab_rp('iTaskNumerotation', BAB_TM_SEQUENTIAL);
	$iEmailNotice = (int) bab_rp('iEmailNotice', BAB_TM_YES);
	$sFaqUrl = mysql_escape_string(bab_rp('sFaqUrl', ''));

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
			
		$aDPC = $oTmCtx->getConfiguration();
				
		if(!is_null($aDPC))
		{
			global $babDB;
			$result = bab_selectTasksList($iIdProject);
			
			if(false != $result && $babDB->db_num_rows($result) !== 0)
			{
				$aConfiguration['tasksNumerotation'] = $aDPC['tasksNumerotation'];
			}
		}
		bab_updateProjectConfiguration($aConfiguration);
	}
}	

function addModifyProjectCommentary()
{
	$iIdProject = (int) bab_rp('iIdProject', 0);
	$iIdCommentary = (int) bab_rp('iIdCommentary', 0);

	if(bab_isAccessValid(BAB_TSKMGR_PROJECTS_MANAGERS_GROUPS_TBL, $iIdProject))
	{
		$sCommentary = mysql_escape_string(trim(bab_rp('sCommentary', '')));
		
		if(strlen(trim($sCommentary)) > 0)
		{
			if(0 == $iIdCommentary)
			{
				bab_createProjectCommentary($iIdProject, mysql_escape_string($sCommentary));
			}
			else 
			{
				bab_updateProjectCommentary($iIdCommentary, mysql_escape_string($sCommentary));
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
	$iIdProject = (int) bab_rp('iIdProject', 0);
	$iIdCommentary = (int) bab_rp('iIdCommentary', 0);

	if(bab_isAccessValid(BAB_TSKMGR_PROJECTS_MANAGERS_GROUPS_TBL, $iIdProject))
	{
		bab_deleteProjectCommentary($iIdCommentary);
	}
	else 
	{
		bab_debug('addModifyProjectCommentary: acces denied');
	}
}

function addModifyTaskCommentary()
{
	$oTmCtx =& getTskMgrContext();
	$iUserProfil = $oTmCtx->getUserProfil();

	$iIdProject = $oTmCtx->getIdProject();
	$iIdTask = $oTmCtx->getIdTask();
	$iIdCommentary = (int) bab_rp('iIdCommentary', 0);

	if(0 != $oTmCtx->m_iIdTask && (BAB_TM_PROJECT_MANAGER == $iUserProfil || BAB_TM_PERSONNAL_TASK_OWNER == $iUserProfil))
	{
		$sCommentary = mysql_escape_string(trim(bab_rp('sCommentary', '')));
		
		if(strlen(trim($sCommentary)) > 0)
		{
			if(0 == $iIdCommentary)
			{
				bab_createTaskCommentary($iIdProject, $iIdTask, $sCommentary);
			}
			else 
			{
				bab_updateTaskCommentary($iIdCommentary, $sCommentary);
			}
		}
		else 
		{
			bab_debug('addModifyTaskCommentary: commentary empty');
		}
	}
	else 
	{
		bab_debug('addModifyTaskCommentary: acces denied');
	}
	
	if(1 == (int) bab_rp('isPopUp', 0))	
	{
		$bf = & new BAB_BaseFormProcessing();
		die(bab_printTemplate($bf, $GLOBALS['babAddonHtmlPath'] . 'tmUser.html', 'close_popup'));
	}
}

function deleteTaskCommentary()
{
	$oTmCtx =& getTskMgrContext();
	$iUserProfil = $oTmCtx->getUserProfil();
	
	$iIdCommentary = (int) bab_rp('iIdCommentary', 0);

	if(BAB_TM_PROJECT_MANAGER == $iUserProfil || BAB_TM_PERSONNAL_TASK_OWNER == $iUserProfil)
	{
		bab_deleteTaskCommentary($iIdCommentary);
	}
	else 
	{
		bab_debug('deleteTaskCommentary: acces denied');
	}
	
	if(1 == (int) bab_rp('isPopUp', 0))	
	{
		$bf = & new BAB_BaseFormProcessing();
		die(bab_printTemplate($bf, $GLOBALS['babAddonHtmlPath'] . 'tmUser.html', 'close_popup'));
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
	
	$iClass = (int) bab_rp('iClassType', BAB_TM_TASK);
	
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
		$iIdProject = (int) bab_rp('iIdProject', 0);
		bab_test($iIdProject);
//*/
	}

	if(!$bIsOk)
	{
		//Pour être en création
		if(isset($_POST['iIdTask']) && 0 == $_POST['iIdTask'])
		{
			unset($_POST['iIdTask']);
		}
		
		$_POST['idx'] = BAB_TM_IDX_DISPLAY_TASK_FORM;
	}
}


function deleteTask()
{
	$oTmCtx =& getTskMgrContext();
	$iIdProjectSpace = $oTmCtx->getIdProjectSpace();
	$iIdProject = $oTmCtx->getIdProject();
	$iIdTask = $oTmCtx->getIdTask();
	$iUserProfil = $oTmCtx->getUserProfil();

	if((BAB_TM_PROJECT_MANAGER == $iUserProfil || BAB_TM_PERSONNAL_TASK_OWNER == $iUserProfil))
	{
		$aTaskToDel = array();
		bab_getTask($iIdTask, $aTaskToDel);
		
		{
			$aDependingTasks = array();
			bab_getDependingTasks($iIdTask, $aDependingTasks);
			if(count($aDependingTasks) > 0)
			{
				foreach($aDependingTasks as $iIdT)
				{
					bab_getTask($iIdT, $aTask);
					$aTask['isLinked'] = BAB_TM_NO;
					bab_updateTask($iIdTask, $aTask);
					bab_deleteTaskLinks($iIdT);
				}
			}
		}

		$sTaskNumber = ((strlen(trim($aTaskToDel['sShortDescription'])) > 0) ? $aTaskToDel['sShortDescription'] : $aTaskToDel['sTaskNumber']);
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
	$iIdProject = (int) bab_rp('iIdProject', 0);
	$iIdTask = (int) bab_rp('iIdTask', 0);
	$iIdSpecificField = (int) bab_rp('oSpfField', 0);

	if(bab_isAccessValid(BAB_TSKMGR_PROJECTS_MANAGERS_GROUPS_TBL, $iIdProject) && 0 < $iIdTask)
	{
		bab_createSpecificFieldInstance($iIdTask, $iIdSpecificField);
	}
	else 
	{
		bab_debug('createSpecificFieldInstance: acces denied');
	}
}

function savePersonnalTaskConfiguration()
{
	global $babBody;
	$oTmCtx =& getTskMgrContext();
	$aPersTaskCreator = bab_getUserIdObjects(BAB_TSKMGR_PERSONNAL_TASK_CREATOR_GROUPS_TBL);
	if(count($aPersTaskCreator) > 0 && isset($aPersTaskCreator[$oTmCtx->getIdDelegation()]))
	{
		$aCfg = array();
		$iIdUser = $GLOBALS['BAB_SESS_USERID'];
		$aCfg['endTaskReminder'] = (int) bab_rp('iEndTaskReminder', 5);
		$aCfg['tasksNumerotation'] = (int) bab_rp('iTaskNumerotation', BAB_TM_SEQUENTIAL);
		$aCfg['emailNotice'] = (int) bab_rp('iEmailNotice', BAB_TM_YES);
		
		$aCfgT = array();
		$bSuccess = bab_getPersonnalTaskConfiguration($iIdUser, $aCfgT);
		if($bSuccess)
		{
			bab_updatePersonnalTaskConfiguration($iIdUser, $aCfg);
		}
		else
		{
			bab_createPersonnalTaskConfiguration($iIdUser, $aCfg);
		}
	}	
}

function modifyProjectProperties()
{
	saveProjectConfiguration();
	addModifyProject();
}

//bab_cleanGpc();

/*
require_once($babInstallPath . 'upgrade.php');
require_once($babInstallPath . 'utilit\upgradeincl.php');
upgrade585to586();
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
		
	case BAB_TM_ACTION_ADD_TASK_COMMENTARY:
	case BAB_TM_ACTION_MODIFY_TASK_COMMENTARY:
		addModifyTaskCommentary();
		break;

	case BAB_TM_ACTION_DELETE_TASK_COMMENTARY:
		deleteTaskCommentary();
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
		
	case BAB_TM_ACTION_SAVE_PERSONNAL_TASK_CONFIGURATION:
		savePersonnalTaskConfiguration();
		break;
		
	case BAB_TM_ACTION_MODIFY_PROJECT_PROPERTIES:
		modifyProjectProperties();
		break;
}


$idx = isset($_POST['idx']) ? $_POST['idx'] : (isset($_GET['idx']) ? $_GET['idx'] : BAB_TM_IDX_DISPLAY_TASK_LIST);
//$idx = isset($_POST['idx']) ? $_POST['idx'] : (isset($_GET['idx']) ? $_GET['idx'] : BAB_TM_IDX_DISPLAY_GANTT_CHART);

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
		$oTmCtx =& getTskMgrContext();
		$iIdProjectSpace = $oTmCtx->getIdProjectSpace();
		$iIdProject = $oTmCtx->getIdProject();
		displayCategoriesList($iIdProjectSpace, $iIdProject, (int) bab_rp('iIdUser', 0));
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
		
	case BAB_TM_IDX_DISPLAY_PERSONNAL_TASK_CONFIGURATION_FORM:
		displayPersonnalTaskConfigurationForm();
		break;
		
	case BAB_TM_IDX_DISPLAY_GANTT_CHART:
		displayGanttChart();
		break;
		
	/*	
	case BAB_TM_IDX_CLOSE_POPUP:
		tskmgClosePopup();
	//*/
	
	case BAB_TM_IDX_DISPLAY_PROJECT_PROPERTIES_FORM:
		displayProjectPropertiesForm();
		break;
}
$babBody->setCurrentItemMenu($idx);
?>