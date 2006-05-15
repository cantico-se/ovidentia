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
require_once($babInstallPath . 'utilit/tmToolsIncl.php');
require_once($babInstallPath . 'utilit/tmList.php');

require_once($babInstallPath . 'utilit/baseFormProcessingClass.php');
require_once($babInstallPath . 'tmContext.php');


//-- BEGIN TOOLS FUNC
function tmIsProjectDeletable($iIdProject)
{
	bab_debug('isProjectDeletable this function must be implemented');
	return true;
}

function tmDuplicateConfiguration($srcTable, $srcIdObject, $trgTable, $trgIdObject)
{
	$oTmCtx =& getTskMgrContext();
	$tblWr =& $oTmCtx->getTableWrapper();
	
	$tblWr->setTableName($srcTable);
				
	$aAttributs = array(
		'idProjectSpace' => $srcIdObject,
		'tskUpdateByMgr' => '',
		'endTaskReminder' => '',
		'tasksNumerotation' => '',
		'emailNotice' => '',
		'faqUrl' => ''
	);

	$aAttributs = $tblWr->load($aAttributs, 1, 5, 0, 1);
	if(false != $aAttributs)
	{
		$aAttributs['faqUrl'] = mysql_escape_string($aAttributs['faqUrl']);
		$aAttributs['idProject'] = $trgIdObject;
		$skipFirst = false;
		$tblWr->setTableName($trgTable);
		return $tblWr->save($aAttributs, $skipFirst);
		
	}
	return false;
}

function tmDeleteProject($iIdProject)
{
	tmDeleteAllTask($iIdProject);
	
	aclDelete(BAB_TSKMGR_PROJECTS_MANAGERS_GROUPS_TBL, $iIdProject);
	aclDelete(BAB_TSKMGR_PROJECTS_SUPERVISORS_GROUPS_TBL, $iIdProject);
	aclDelete(BAB_TSKMGR_PROJECTS_VISUALIZERS_GROUPS_TBL, $iIdProject);

	$oTmCtx =& getTskMgrContext();
	$tblWr =& $oTmCtx->getTableWrapper();
	
	$aAttribut = array('idProject' => $iIdProject);
	$tblWr->setTableName(BAB_TSKMGR_PROJECTS_CONFIGURATION_TBL);
	$tblWr->delete($aAttribut);
	
	$tblWr->setTableName(BAB_TSKMGR_PROJECTS_REVISIONS_TBL);
	$tblWr->delete($aAttribut);

	$tblWr->setTableName(BAB_TSKMGR_PROJECTS_COMMENTS_TBL);
	$tblWr->delete($aAttribut);

	$aAttribut = array('id' => $iIdProject);
	$tblWr->setTableName(BAB_TSKMGR_PROJECTS_TBL);
	$tblWr->delete($aAttribut);
}

function tmDeleteAllTask($iIdProject)
{
	$db = &$GLOBALS['babDB'];
	
	$query = 
		'SELECT ' .
			'id ' .
		'FROM ' .
			BAB_TSKMGR_TASKS_TBL . ' ' .
		'WHERE ' .
			'idProject = \'' . $iIdProject . '\'';
			
	
	$result = $db->db_query($query);
	$iNumRows = $db->db_num_rows($result);
	$iIndex = 0;
	
	while($iIndex < $iNumRows && false != ($data = $db->$db->db_fetch_assoc($result)))
	{
		aclDelete(BAB_TSKMGR_TASK_RESPONSIBLE_GROUPS_TBL, $data['id']);
	}

	$query = 'DELETE FROM ' . BAB_TSKMGR_TASKS_COMMENTS_TBL . ' WHERE idProject = \'' . $iIdProject . '\'';
	$db->db_query($query);

	$query = 'DELETE FROM ' . BAB_TSKMGR_TASKS_TBL . ' WHERE idProject = \'' . $iIdProject . '\'';
	$db->db_query($query);
}

function tmSelectProjectList($iIdProjectSpace)
{
	$db = &$GLOBALS['babDB'];

	$query = 
		'SELECT ' .
			'* ' .
		'FROM ' .
			BAB_TSKMGR_PROJECTS_TBL . ' ' .
		'WHERE ' . 
			'idProjectSpace = \'' . $iIdProjectSpace . '\'';
			
	//bab_debug($query);
	return $db->db_query($query);
}
//-- END TOOLS FUNC


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
	global $babBody;

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

	$list = new BAB_TM_List($query);
	
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

	class BAB_TM_ProjectList extends BAB_BaseFormProcessing
	{
		var $m_db;
		var $m_result;
		var $m_is_altbg;
	
		function BAB_TM_ProjectList($iIdProjectSpace)
		{
			parent::BAB_BaseFormProcessing();
	
			$this->m_db	= & $GLOBALS['babDB'];
			$this->m_is_altbg = true;
	
			$this->set_caption('name', bab_translate("Name"));
			$this->set_caption('description', bab_translate("Description"));
			$this->set_caption('rights', bab_translate("Rights"));
			$this->set_data('isLink', true);
			$this->set_data('name', '');
			$this->set_data('description', '');
			$this->set_data('iIdProjectSpace', $iIdProjectSpace);
			
			$this->set_data('bIsProjectCreator', 
				bab_isAccessValid(BAB_TSKMGR_PROJECT_CREATOR_GROUPS_TBL, $iIdProjectSpace));
				
			$this->set_data('rightsUrl', '#');

			$this->m_result = tmSelectProjectList($iIdProjectSpace);
		}
	
		function nextItem()
		{
			$data = $this->m_db->db_fetch_array($this->m_result);
	
			if(false != $data)
			{
				$this->m_is_altbg = !$this->m_is_altbg;
				$this->set_data('id', $data['id']);
				$this->set_data('name', htmlentities($data['name'], ENT_QUOTES));
				$this->set_data('description', htmlentities($data['description'], ENT_QUOTES));
			
				$this->get_data('iIdProjectSpace', $iIdProjectSpace);
				$this->get_data('bIsProjectCreator', $bIsProjectCreator);
				$this->set_data('bIsRightUrl', ($bIsProjectCreator || bab_isAccessValid(BAB_TSKMGR_PROJECTS_MANAGERS_GROUPS_TBL, $data['id'])));
				
				$this->set_data('rightsUrl', $GLOBALS['babUrlScript'] . '?tg=usrTskMgr&idx=' . 
					BAB_TM_IDX_DISPLAY_PROJECT_RIGHTS_FORM . '&iIdProjectSpace=' . $iIdProjectSpace . '&iIdProject=' . $data['id']
					);
				return true;
			}
			return false;
		}
	}
	
	$list = new BAB_TM_ProjectList($iIdProjectSpace);
	
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
					require_once($GLOBALS['babInstallPath'] . 'utilit/tableWrapperClass.php');
			
					$attributs = array(
						'id' => $iIdProject, 
						'idProjectSpace' => $iIdProjectSpace, 
						'name' => '',
						'description' => '');
						
					$oTmCtx =& getTskMgrContext();
					$tblWr =& $oTmCtx->getTableWrapper();
					$tblWr->setTableName(BAB_TSKMGR_PROJECTS_TBL);
					
					if(false != ($attributs = $tblWr->load($attributs, 2, 2, 0, 2)))
					{
						$this->set_data('sName', htmlentities($attributs['name'], ENT_QUOTES) );
						$this->set_data('sDescription', htmlentities($attributs['description'], ENT_QUOTES));
					}
					
					$this->set_data('bIsDeletable', tmIsProjectDeletable($iIdProject));
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
		if(tmIsProjectDeletable($iIdProject))
		{
			$bf = & new BAB_BaseFormProcessing();
			
			$tblWr =& $oTmCtx->getTableWrapper();
			$tblWr->setTableName(BAB_TSKMGR_PROJECTS_TBL);
	
			$attributs = array(
				'id' => $iIdProject, 
				'idProjectSpace' => $iIdProjectSpace, 
				'name' => '',
				'description' => '');
					
			if(false !== ($attributs = $tblWr->load($attributs, 2, 2, 0, 2)))
			{
				$bf->set_data('idx', BAB_TM_IDX_DISPLAY_PROJECTS_LIST);
				$bf->set_data('action', BAB_TM_ACTION_DELETE_PROJECT);
				$bf->set_data('iIdProjectSpace', $iIdProjectSpace);
				$bf->set_data('iIdProject', $iIdProject);
				$bf->set_data('tg', 'usrTskMgr');
	
				$bf->set_caption('warning', bab_translate("This action will delete the project and all references"));
				$bf->set_caption('message', bab_translate("Continue ?"));
				$bf->set_caption('title', bab_translate("Project = ") . htmlentities($attributs['name'], ENT_QUOTES));
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
		}
	
		$macl->babecho();
	}
	else
	{
		
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
				$oTmCtx =& getTskMgrContext();
				$tblWr =& $oTmCtx->getTableWrapper();
				$tblWr->setTableName(BAB_TSKMGR_PROJECTS_TBL);
				
				$attribut = array(
					'id' => $iIdProject,
					'idProjectSpace' => $iIdProjectSpace,
					'name' => $sName,
					'description' => $sDescription
				);
				
				if(0 == $iIdProject)
				{
					$attribut['created'] = date("Y-m-d H:i:s");
					$attribut['idUserCreated'] = $GLOBALS['BAB_SESS_USERID'];
					
					require_once($GLOBALS['babInstallPath'] . 'admin/acl.php');

					$skipFirst = true;
					$success = $tblWr->save($attribut, $skipFirst);
					if($success)
					{
						$db =& $tblWr->getDbObject();
						$iIdProject = $db->db_insert_id();
						
						aclDuplicateRights(
							BAB_TSKMGR_DEFAULT_PROJECTS_VISUALIZERS_GROUPS_TBL, $iIdProjectSpace, 
							BAB_TSKMGR_PROJECTS_VISUALIZERS_GROUPS_TBL, $iIdProject);					
						aclDuplicateRights(
							BAB_TSKMGR_DEFAULT_PROJECTS_SUPERVISORS_GROUPS_TBL, $iIdProjectSpace, 
							BAB_TSKMGR_PROJECTS_SUPERVISORS_GROUPS_TBL, $iIdProject);					
						aclDuplicateRights(
							BAB_TSKMGR_DEFAULT_PROJECTS_MANAGERS_GROUPS_TBL, $iIdProjectSpace, 
							BAB_TSKMGR_PROJECTS_MANAGERS_GROUPS_TBL, $iIdProject);
						tmDuplicateConfiguration(
							BAB_TSKMGR_DEFAULT_PROJECTS_CONFIGURATION_TBL, $iIdProjectSpace, 
							BAB_TSKMGR_PROJECTS_CONFIGURATION_TBL, $iIdProject);
					}
				}
				else
				{
					$attribut['modified'] = date("Y-m-d H:i:s");
					$attribut['idUserModified'] = $GLOBALS['BAB_SESS_USERID'];

					return $tblWr->update($attribut);
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
		if(tmIsProjectDeletable($iIdProject))
		{
			require_once($GLOBALS['babInstallPath'] . 'admin/acl.php');

			tmDeleteAllTask($iIdProject);
			tmDeleteProject($iIdProject);
		}
	}
	else
	{
		$GLOBALS['babBody']->msgerror = bab_translate("You do not have the right to delete a project");
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
	{
		require_once($GLOBALS['babInstallPath'] . 'admin/acl.php');
		maclGroups();
		
		$oTmCtx =& getTskMgrContext();
		$tblWr =& $oTmCtx->getTableWrapper();
		
		$aAttribut = array('idProject' => $oTmCtx->getIdProject(), 'idProjectSpace' => -1);
		$tblWr->setTableName(BAB_TSKMGR_PROJECTS_TBL);
		$aAttributs = $tblWr->load($aAttributs, 0, 2, 0, 1);
		if(false != $aAttributs)
		{
			$_POST['idProjectSpace'] = $aAttributs['idProjectSpace'];
		}
		
		break;		
	}
}


$idx = isset($_POST['idx']) ? $_POST['idx'] : (isset($_GET['idx']) ? $_GET['idx'] : BAB_TM_IDX_DISPLAY_MENU);

//bab_debug('idx ==> ' . $idx);

switch($idx)
{
	case BAB_TM_IDX_DISPLAY_MENU:
		displayMenu();
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

}
$babBody->setCurrentItemMenu($idx);
?>