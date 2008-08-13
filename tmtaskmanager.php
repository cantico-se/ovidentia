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




//Helper class
class BAB_TM_ToolbarItem
{
	var $sText	= '';
	var $sUrl	= '';
	var $sImg	= '';
	var $sTitle = '';
	var $sAlt	= '';
	var $sId	= '';
	
	function BAB_TM_ToolbarItem($sText, $sUrl, $sImg, $sTitle, $sAlt, $sId)
	{
		$this->setText($sText);
		$this->setUrl($sUrl);
		$this->setImg($sImg);
		$this->setTitle($sTitle);
		$this->setAlt($sAlt);
		$this->setId($sId);
	}

	function setText($sText) 
	{
		$this->sText = $sText;
	}
	
	function getText()
	{
		return $this->sText;
	}
	
	function setUrl($sUrl)
	{
		$this->sUrl = $sUrl;
	}
	
	function getUrl()
	{
		return $this->sUrl;
	}

	function setImg($sImg)
	{
		$this->sImg = $sImg;
	}

	function getImg()
	{
		return $this->sImg;
	}

	function setTitle($sTitle)
	{
		$this->sTitle = $sTitle;
	}

	function getTitle()
	{
		return $this->sTitle;
	}

	function setAlt($sAlt)
	{
		$this->sAlt = $sAlt;
	}

	function getAlt()
	{
		return $this->sAlt;
	}
	
	function setId($sId)
	{
		$this->sId = $sId;
	}

	function getId()
	{
		return $this->sId;
	}
}


class BAB_TM_Toolbar
{
	var $aToolbarItem = array();

	var $sText	= '';
	var $sUrl	= '';
	var $sImg	= '';
	var $sTitle = '';
	var $sAlt	= '';
	var $sId	= '';
	
	var $sTemplateFileName = 'tmUser.html';
	var $sTemplate = 'toolbar';

	function BAB_TM_Toolbar()
	{
	}
	
	function addToolbarItem()
	{
    	$iNumArgs = func_num_args();
    	if(0 < $iNumArgs)
    	{
    		for($iIndex = 0; $iIndex < $iNumArgs; $iIndex++)
    		{
				$oToolbarItem = func_get_arg($iIndex);
//    			if(is_a($oToolbarItem, 'BAB_TM_ToolbarItem'))
				{
					$this->aToolbarItem[] = $oToolbarItem;
				}
    		}
    	}
	}

	function getNextItem()
	{
		$aItem = each($this->aToolbarItem);
		if(false !== $aItem)
		{
			$oToolbarItem =& $aItem['value'];

			$this->sText	= $oToolbarItem->getText();
			$this->sUrl		= htmlentities($oToolbarItem->getUrl());
			$this->sImg		= $oToolbarItem->getImg();
			$this->sTitle	= $oToolbarItem->getTitle();
			$this->sAlt		= $oToolbarItem->getAlt();
			$this->sId		= $oToolbarItem->getId();
			return true;
		}
		return false;
	}

	function printTemplate()
	{
		return bab_printTemplate($this, $this->sTemplateFileName, $this->sTemplate);
	}
}


class BAB_TM_SessionContext
{
	var $sKey		= '';
	var $aSettings	= array();
	
	function BAB_TM_SessionContext($sKey)
	{
		$this->setKey($sKey);
		$this->intSettings();
	}
	
	function setKey($sKey)
	{
		$this->sKey = $sKey;
	}
	
	function get($sName, $sDefaultValue = '')
	{
		if(array_key_exists($sName, $this->aSettings))
		{
			return $this->aSettings[$sName];
		}
		return $sDefaultValue;
	}

	function set($sName, $sValue)
	{
		$this->aSettings[$sName] = $sValue;
	}
	
	function intSettings()
	{
		if(!array_key_exists($this->sKey, $_SESSION))
		{
			$_SESSION[$this->sKey] = $this->aSettings;
		}
		$this->aSettings =& $_SESSION[$this->sKey];
	}
	
	function unsetSettings()
	{
		if(array_key_exists($this->sKey, $_SESSION))
		{
			unset($_SESSION[$this->sKey]);
			$this->aSettings = array();
		}
	}
}



function displayProjectsSpacesList()
{
	global $babBody, $babDB;
	$babBody->title = bab_toHtml(bab_translate("Projects spaces list"));
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
			$this->m_sUrlBase = $GLOBALS['babUrlScript'] . '?tg=' . urlencode($sTg) . '&idx=%s&iIdProjectSpace=%d&iIdProject=%d';
			
			$this->createProjectSpaceSubTree();
			$this->createPersonnalTaskSubTree();
		}
		
		function getUrl($sIdx, $iIdProjectSpace, $iIdProject)
		{
			return bab_toHtml(sprintf($this->m_sUrlBase, urlencode($sIdx), urlencode($iIdProjectSpace), urlencode($iIdProject)));
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
					'id IN(' . $babDB->quote(array_keys($oTmCtx->getVisualisedIdProjectSpace())) . ')';	
					
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
					$oProjectSpaceElement =& $this->createElement($this->m_dnps . '_' . $datas['id'], $this->m_dnps, bab_toHtml($datas['name']), 
						bab_toHtml($datas['description']), null);

					if(bab_isAccessValid(BAB_TSKMGR_PROJECT_CREATOR_GROUPS_TBL, (int) $datas['id']))
					{
						$oProjectSpaceElement->addAction('add',
			               bab_toHtml(bab_translate("Add a project")), $GLOBALS['babSkinPath'] . 'images/Puces/edit_add.png', 
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
					
					$sProjectUrl = ($bIsManager) ? $this->getUrl(BAB_TM_IDX_DISPLAY_PROJECT_TASK_LIST, $iIdProjectSpace, $datas['id']) . bab_toHtml('&isProject=' . urlencode(1)) : null;
					$oProjectElement =& $this->createElement($this->m_dnp . '_' . $datas['id'], $this->m_dnp, bab_toHtml($datas['name']) . ' (' . $iTaskCount . ')', 
						bab_toHtml($datas['description']), $sProjectUrl);
               		$this->appendElement($oProjectElement, $this->m_dnps . '_' . $iIdProjectSpace);
               		
					$oProjectElement->setIcon($GLOBALS['babSkinPath'] . 'images/nodetypes/folder.png');
               		
               		if($isAccessValid)
               		{
						$oProjectElement->addAction('Rights',
			               bab_toHtml(bab_translate("Rights")), $GLOBALS['babSkinPath'] . 'images/Puces/agent.png', 
			               $this->getUrl(BAB_TM_IDX_DISPLAY_PROJECT_RIGHTS_FORM, $iIdProjectSpace, $datas['id']), '');
               		}
               		
               		if($bIsManager)
               		{
               			$sGanttViewUrl = $this->getUrl(BAB_TM_IDX_DISPLAY_GANTT_CHART, $iIdProjectSpace, $datas['id']);
						$oProjectElement->addAction('GanttView',
			               bab_toHtml(bab_translate("Display the gantt View")), $GLOBALS['babSkinPath'] . 'images/Puces/schedule.png', 
			               'javascript:bab_popup(\'' . $sGanttViewUrl . '\', 150, 1)' , '');
						$oProjectElement->addAction('Configuration',
			               bab_toHtml(bab_translate("Project properties")), $GLOBALS['babSkinPath'] . 'images/Puces/package_settings.png', 
			               $this->getUrl(BAB_TM_IDX_DISPLAY_PROJECT_PROPERTIES_FORM, $iIdProjectSpace, $datas['id']), '');
						$oProjectElement->addAction('Specific_fields',
			               bab_toHtml(bab_translate("Specific fields")), $GLOBALS['babSkinPath'] . 'images/Puces/list.png', 
			               $this->getUrl(BAB_TM_IDX_DISPLAY_SPECIFIC_FIELD_LIST, $iIdProjectSpace, $datas['id']), '');
						$oProjectElement->addAction('Categories_list',
			               bab_toHtml(bab_translate("Categories list")), $GLOBALS['babSkinPath'] . 'images/Puces/kwikdisk.png', 
			               $this->getUrl(BAB_TM_IDX_DISPLAY_CATEGORIES_LIST, $iIdProjectSpace, $datas['id']), '');
						$oProjectElement->addAction('Fields_list',
							bab_toHtml(bab_translate("Field(s) list")), $GLOBALS['babSkinPath'] . 'images/Puces/a-z.gif', 
						    $this->getUrl(BAB_TM_IDX_DISPLAY_ORDER_TASK_FIELDS_FORM, $iIdProjectSpace, $datas['id']), '');
			          	$oProjectElement->addAction('Notices',
			               bab_toHtml(bab_translate("Notices")), $GLOBALS['babSkinPath'] . 'images/Puces/mailreminder.png', 
			               $this->getUrl(BAB_TM_IDX_DISPLAY_NOTICE_EVENT_FORM, $iIdProjectSpace, $datas['id']), '');
						$oProjectElement->addAction('Commentaries',
			               bab_toHtml(bab_translate("Commentaries list")), $GLOBALS['babSkinPath'] . 'images/Puces/lists.png', 
			               $this->getUrl(BAB_TM_IDX_DISPLAY_PROJECT_COMMENTARY_LIST, $iIdProjectSpace, $datas['id']), '');

						$oProjectElement->addAction('Task_list',
						   bab_toHtml(bab_translate("Add a task")), $GLOBALS['babSkinPath'] . 'images/Puces/edit_add.png', 
						   $this->getUrl(BAB_TM_IDX_DISPLAY_TASK_FORM, $iIdProjectSpace, $datas['id']) 
						   . bab_toHtml('&sFromIdx=' . urlencode(BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_LIST)), '');
               		}

					if(0 === $iTaskCount && $bIsCreator)		               
					{
						$oProjectElement->addAction('Delete_Project',
						   bab_toHtml(bab_translate("Delete project")), $GLOBALS['babSkinPath'] . 'images/Puces/edit_remove.png', 
						   $this->getUrl(BAB_TM_IDX_DISPLAY_DELETE_PROJECT_FORM, $iIdProjectSpace, $datas['id']) 
						   . bab_toHtml('&sFromIdx=' . urlencode(BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_LIST)), '');
					}
				}
			}	
		}
		
		function createPersonnalTaskSubTree()
		{
			$oTmCtx =& getTskMgrContext();
			$iIdDelegation = (int) $oTmCtx->getIdDelegation();
			
			if(bab_isAccessValid(BAB_TSKMGR_PERSONNAL_TASK_CREATOR_GROUPS_TBL, $iIdDelegation))
			{
				$iTaskCount = bab_getTaskCount(0, $GLOBALS['BAB_SESS_USERID']);
				
				$oPersTaskElement =& $this->createElement($this->m_iIdPersTaskElement, 'snps', bab_toHtml(bab_translate("Personnal(s) task(s)")) . '(' . $iTaskCount . ')', bab_toHtml('description'), null);
				
       			$sGanttViewUrl = $this->getUrl(BAB_TM_IDX_DISPLAY_GANTT_CHART, 0, 0);
				$oPersTaskElement->addAction('GanttView',
	               bab_toHtml(bab_translate("Display the gantt View")), $GLOBALS['babSkinPath'] . 'images/Puces/schedule.png', 
	               'javascript:bab_popup(\'' . $sGanttViewUrl . '\', 150, 1)' , '');
				$oPersTaskElement->addAction('Configuration',
	               bab_toHtml(bab_translate("Configuration")), $GLOBALS['babSkinPath'] . 'images/Puces/package_settings.png', 
	               $this->getUrl(BAB_TM_IDX_DISPLAY_PERSONNAL_TASK_CONFIGURATION_FORM, 0, 0), '');
				$oPersTaskElement->addAction('Specific_fields',
	               bab_toHtml(bab_translate("Specific fields")), $GLOBALS['babSkinPath'] . 'images/Puces/list.png', 
	               $this->getUrl(BAB_TM_IDX_DISPLAY_SPECIFIC_FIELD_LIST, 0, 0) . bab_toHtml('&iIdUser=' . urlencode($GLOBALS['BAB_SESS_USERID'])), '');
				$oPersTaskElement->addAction('Categories list',
	               bab_toHtml(bab_translate("Categories list")), $GLOBALS['babSkinPath'] . 'images/Puces/kwikdisk.png', 
	               $this->getUrl(BAB_TM_IDX_DISPLAY_CATEGORIES_LIST, 0, 0) . bab_toHtml('&iIdUser=' . urlencode($GLOBALS['BAB_SESS_USERID'])), '');
				$oPersTaskElement->addAction('Add',
	               bab_toHtml(bab_translate("Add")), $GLOBALS['babSkinPath'] . 'images/Puces/edit_add.png', 
	               $this->getUrl(BAB_TM_IDX_DISPLAY_TASK_FORM, 0, 0) . bab_toHtml('&sFromIdx=' . urlencode(BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_LIST)), '');

			    $this->appendElement($oPersTaskElement, null);
			}
		}
	}
	
	$list = new BAB_TM_List();
	$GLOBALS['babBody']->babecho($list->printTemplate());
}

function displayProjectForm()
{
	global $babBody;

	$oTmCtx =& getTskMgrContext();
	$iIdProjectSpace = (int) $oTmCtx->getIdProjectSpace();
	$iIdProject = (int) $oTmCtx->getIdProject();
	
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
						$this->set_data('sName', $aProject['name']);
						$this->set_data('sDescription', $aProject['description']);
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
				'url' => $GLOBALS['babUrlScript'] . '?tg=' . urlencode('usrTskMgr') . '&idx=' . urlencode(BAB_TM_IDX_DISPLAY_PROJECT_FORM) . 
				'&iIdProjectSpace=' . urlencode($iIdProjectSpace))
			);
			
		add_item_menu($itemMenu);

		$babBody->title = bab_toHtml($tab_caption);
	
		$oProject = new BAB_Project($iIdProjectSpace, $iIdProject);
		$oProject->raw_2_html(BAB_RAW_2_HTML_CAPTION);
		$oProject->raw_2_html(BAB_RAW_2_HTML_DATA);
		$babBody->babecho(bab_printTemplate($oProject, 'tmUser.html', 'projectForm'));		
	}
	else
	{
		$GLOBALS['babBody']->msgerror = bab_toHtml(bab_translate("You do not have the right to create/modify a project"));
	}
}

function displayDeleteProjectForm()
{
	global $babBody;

	$oTmCtx =& getTskMgrContext();
	$iIdProjectSpace = (int) $oTmCtx->getIdProjectSpace();
	$iIdProject = (int) $oTmCtx->getIdProject();

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
				$bf->set_caption('title', bab_translate("Project = ") . $aProject['name']);
				$bf->set_caption('yes', bab_translate("Yes"));
				$bf->set_caption('no', bab_translate("No"));
	
				$babBody->title = bab_toHtml(bab_translate("Delete project"));
				$bf->raw_2_html(BAB_RAW_2_HTML_CAPTION);
				$bf->raw_2_html(BAB_RAW_2_HTML_DATA);
				$babBody->babecho(bab_printTemplate($bf, 'tmCommon.html', 'warningyesno'));
			}
		}
	}
	else 
	{
		$GLOBALS['babBody']->msgerror = bab_toHtml(bab_translate("You do not have the right to delete a project"));
	}	
}

function displayProjectRightsForm()
{
	global $babBody;

	$oTmCtx =& getTskMgrContext();
	$iIdProjectSpace = (int) $oTmCtx->getIdProjectSpace();
	$iIdProject = (int) $oTmCtx->getIdProject();
	
	$bIsCreator = bab_isAccessValid(BAB_TSKMGR_PROJECT_CREATOR_GROUPS_TBL, $iIdProjectSpace);
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
				'url' => $GLOBALS['babUrlScript'] . '?tg=' . urlencode('usrTskMgr') . '&idx=' . urlencode(BAB_TM_IDX_DISPLAY_PROJECT_RIGHTS_FORM) . 
				'&iIdProjectSpace=' . urlencode($iIdProjectSpace) .'&iIdProject=' . urlencode($iIdProject))
			);
	
		add_item_menu($itemMenu);
		$babBody->title = bab_toHtml(bab_translate("Project right"));
		
		$enableGroup	= 0;
		$disableGroup	= 1;
	
		require_once($GLOBALS['babInstallPath'] . 'admin/acl.php');
		
		$macl = new macl('usrTskMgr', BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_LIST, $iIdProject, BAB_TM_ACTION_SET_RIGHT, true, $oTmCtx->getIdDelegation());
		$macl->set_hidden_field('iIdProjectSpace', $iIdProjectSpace);
		$macl->set_hidden_field('iIdProject', $iIdProject);
		
		$macl->addtable(BAB_TSKMGR_PROJECTS_MANAGERS_GROUPS_TBL, bab_translate("Project manager"));
		$macl->filter($enableGroup, $enableGroup, $disableGroup, $enableGroup, $disableGroup);
		
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

function displayProjectPropertiesForm()
{
	global $babBody;

	$oTmCtx =& getTskMgrContext();
	
	$iIdProjectSpace = (int) $oTmCtx->getIdProjectSpace();
	$iIdProject = (int) $oTmCtx->getIdProject();

	if(bab_isAccessValid(BAB_TSKMGR_PROJECTS_MANAGERS_GROUPS_TBL, $iIdProject))
	{
		if(0 != $iIdProjectSpace)
		{	
			$itemMenu = array(		
				array(
					'idx' => BAB_TM_IDX_DISPLAY_PROJECT_PROPERTIES_FORM,
					'mnuStr' => bab_translate("Project properties"),
					'url' => $GLOBALS['babUrlScript'] . '?tg=' . urlencode('usrTskMgr') . '&idx=' . urlencode(BAB_TM_IDX_DISPLAY_PROJECT_PROPERTIES_FORM) . 
					'&iIdProjectSpace=' . urlencode($iIdProjectSpace) . '&iIdProject=' . urlencode($iIdProject))
			);
			add_item_menu($itemMenu);
	
			$babBody->title = bab_toHtml(bab_translate("Project properties"));
			
			class BAB_ProjectProperties extends BAB_BaseFormProcessing
			{
				var $m_aTaskNumerotation;
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
		
					$this->set_data('isProjectCreator', 
						bab_isAccessValid(BAB_TSKMGR_PROJECT_CREATOR_GROUPS_TBL, $iIdProjectSpace));
					$this->set_data('isProjectManager', 
						bab_isAccessValid(BAB_TSKMGR_PROJECTS_MANAGERS_GROUPS_TBL, $iIdProject));
					
					$this->m_aTaskNumerotation = array(
						BAB_TM_MANUAL => bab_translate("Manual"), BAB_TM_SEQUENTIAL => bab_translate("Sequential (automatique)"),
						BAB_TM_YEAR_SEQUENTIAL => bab_translate("Year + Sequential (automatique)"),
						BAB_TM_YEAR_MONTH_SEQUENTIAL => bab_translate("Year + Month + Sequential (automatique)"));
						
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
							$this->set_data('sName', $aProject['name']);
							$this->set_data('sDescription', $aProject['description']);
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
						$this->set_data('faqUrl', $aDPC['faqUrl']);
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
						$this->set_data('tmValue', $this->m_aTaskNumerotation[$aDPC['tasksNumerotation']]);
					}
				}
				
				function getNextTaskNumerotation()
				{
					$this->get_data('taskNumerotation', $taskNumerotation);
					$this->set_data('tnSelected', '');
					
					$datas = each($this->m_aTaskNumerotation);
					if(false != $datas)
					{
						$this->set_data('tmCode', $datas['key']);
						$this->set_data('tmValue', $datas['value']);
						
						if($taskNumerotation == $datas['key'])
						{
							$this->set_data('tnSelected', bab_toHtml('selected="selected"'));
						}
						
						return true;
					}
					else
					{
						reset($this->m_aTaskNumerotation);
						return false;
					}
				}
			}				
			
			$oPrjP = & new BAB_ProjectProperties($iIdProjectSpace, $iIdProject);
			$oPrjP->raw_2_html(BAB_RAW_2_HTML_CAPTION);
			$oPrjP->raw_2_html(BAB_RAW_2_HTML_DATA);
			$babBody->babecho(bab_printTemplate($oPrjP, 'tmUser.html', 'projectProperties'));
			
		}
		else 
		{
			$GLOBALS['babBody']->msgerror = bab_toHtml(bab_translate("Invalid project space"));
		}
	}	
	else 
	{
		$GLOBALS['babBody']->msgerror = bab_toHtml(bab_translate("You are not a projet manager"));
	}
}

function displayProjectCommentaryList()
{
	global $babBody;

	$oTmCtx =& getTskMgrContext();
	$iIdProjectSpace = (int) $oTmCtx->getIdProjectSpace();
	$iIdProject = (int) $oTmCtx->getIdProject();
		
	if(bab_isAccessValid(BAB_TSKMGR_PROJECTS_MANAGERS_GROUPS_TBL, $iIdProject))
	{
		$babBody->title = bab_toHtml(bab_translate("Commentaries list"));
	
		$itemMenu = array(		
			array(
				'idx' => BAB_TM_IDX_DISPLAY_PROJECT_COMMENTARY_LIST,
				'mnuStr' => bab_translate("Commentaries list"),
				'url' => $GLOBALS['babUrlScript'] . '?tg=' . urlencode('usrTskMgr') . '&idx=' . urlencode(BAB_TM_IDX_DISPLAY_PROJECT_COMMENTARY_LIST) . 
				'&iIdProject=' . urlencode($iIdProject)),
			array(
				'idx' => BAB_TM_IDX_DISPLAY_COMMENTARY_FORM,
				'mnuStr' => bab_translate("Add a commentary"),
				'url' => $GLOBALS['babUrlScript'] . '?tg=' . urlencode('usrTskMgr') . '&idx=' . urlencode(BAB_TM_IDX_DISPLAY_COMMENTARY_FORM) . 
				'&iIdProjectSpace=' . urlencode($iIdProjectSpace) . '&iIdProject=' . urlencode($iIdProject))
		);
		
		add_item_menu($itemMenu);
		
		$result = bab_selectProjectCommentaryList($iIdProject);	
		$oList = new BAB_TM_ListBase($result);
	
		$oList->set_data('isAddCommentaryUrl', true);
		$oList->set_data('url', bab_toHtml($GLOBALS['babUrlScript'] . '?tg=' . urlencode('usrTskMgr') . '&idx=' . 
			urlencode(BAB_TM_IDX_DISPLAY_COMMENTARY_FORM) . '&iIdProjectSpace=' . urlencode($iIdProjectSpace) .
			'&iIdProject=' . urlencode($iIdProject) . '&iIdCommentary='));

		$oList->raw_2_html(BAB_RAW_2_HTML_CAPTION);
		$oList->raw_2_html(BAB_RAW_2_HTML_DATA);
		$babBody->babecho(bab_printTemplate($oList, 'tmUser.html', 'commentariesList'));
	}
	else 
	{
		$GLOBALS['babBody']->msgerror = bab_toHtml(bab_translate("You do not have the right to list the commentaries"));
	}
}

function displayCommentaryForm()
{
	global $babBody;

	$oTmCtx =& getTskMgrContext();
	$iIdProjectSpace = (int) $oTmCtx->getIdProjectSpace();
	$iIdProject = (int) $oTmCtx->getIdProject();
	$iIdTask = (int) $oTmCtx->getIdTask();
	$iUserProfil = (int) $oTmCtx->getUserProfil();

	if(BAB_TM_PROJECT_MANAGER == $iUserProfil || BAB_TM_PERSONNAL_TASK_OWNER == $iUserProfil)
	{
		$iIdCommentary = (int) bab_rp('iIdCommentary', 0);
		$isPopUp = (int) bab_rp('isPopUp', 0);
		$tab_caption = ($iIdCommentary == 0) ? bab_translate("Add a commentary") : bab_translate("Edition of a commentary");
		$babBody->title = bab_toHtml($tab_caption);
		
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
					'url' => $GLOBALS['babUrlScript'] . '?tg=' . urlencode('usrTskMgr') . '&idx=' . urlencode(BAB_TM_IDX_DISPLAY_PROJECT_COMMENTARY_LIST) . 
					'&iIdProject=' . urlencode($iIdProject)),
				array(
					'idx' => BAB_TM_IDX_DISPLAY_COMMENTARY_FORM,
					'mnuStr' => $tab_caption,
					'url' => $GLOBALS['babUrlScript'] . '?tg=' . urlencode('usrTskMgr') . '&idx=' . urlencode(BAB_TM_IDX_DISPLAY_COMMENTARY_FORM) . 
					'&iIdProject=' . urlencode($iIdProject))
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
			$oBf->set_data('commentary', $sCommentary);
		}
		
		$oBf->raw_2_html(BAB_RAW_2_HTML_CAPTION);
		$oBf->raw_2_html(BAB_RAW_2_HTML_DATA);
		
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
		$GLOBALS['babBody']->msgerror = bab_toHtml(bab_translate("You do not have the right to list the commentaries"));
	}
}

function displayDeleteProjectCommentary()
{
	global $babBody;

	$oTmCtx =& getTskMgrContext();
	$iIdProjectSpace = (int) $oTmCtx->getIdProjectSpace();
	$iIdProject = (int) $oTmCtx->getIdProject();

	if(bab_isAccessValid(BAB_TSKMGR_PROJECTS_MANAGERS_GROUPS_TBL, $iIdProject))
	{
		$iIdCommentary = (int) bab_rp('iIdCommentary', 0);
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
				$bf->set_caption('title', bab_translate("Project = ") . $aProject['name']);
				$bf->set_caption('yes', bab_translate("Yes"));
				$bf->set_caption('no', bab_translate("No"));
	
				$babBody->title = bab_toHtml(bab_translate("Delete project commentary "));
				
				$bf->raw_2_html(BAB_RAW_2_HTML_CAPTION);
				$bf->raw_2_html(BAB_RAW_2_HTML_DATA);
				$babBody->babecho(bab_printTemplate($bf, 'tmCommon.html', 'warningyesno'));
			}
		}
	}
	else 
	{
		$GLOBALS['babBody']->msgerror = bab_toHtml(bab_translate("You do not have the right to delete a project"));
	}	
}

function displayTaskList($sIdx)
{
/*	
	require_once $GLOBALS['babInstallPath'] . 'upgrade.php';
	tskMgrFieldOrderUpgrade();
//*/	

	$aItemMenu = array();
	
	$isProject			= (int) bab_rp('isProject', 0);
	$iIdProject			= (int) bab_rp('iIdProject', 0);
	$iIdProjectSpace	= (int) bab_rp('iIdProjectSpace', 0);
	
	$sTitle = bab_translate("My tasks");
	
	if(1 === $isProject)
	{
		if(!bab_isAccessValid(BAB_TSKMGR_PROJECTS_MANAGERS_GROUPS_TBL, $iIdProject))
		{
			$GLOBALS['babBody']->msgerror = bab_toHtml(bab_translate("You are not a projects manager"));
			return false;
		}
		else
		{
			$sTitle = bab_translate("Tasks of the project");
			if(false !== bab_getProject($iIdProject, $aProject))
			{
				$sTitle .= ': ' . $aProject['name'];
			}

			$aItemMenu = array(
				array(
					'idx' => BAB_TM_IDX_DISPLAY_PROJECT_TASK_LIST,
					'mnuStr' => bab_translate("Tasks of the project"),
					'url' => $GLOBALS['babUrlScript'] . '?tg=' . urlencode('usrTskMgr') . '&idx=' . urlencode(BAB_TM_IDX_DISPLAY_PROJECT_TASK_LIST) . 
					'&isProject=' . urlencode($isProject) . '&iIdProjectSpace=' . urlencode($iIdProjectSpace) .
					'&iIdProject=' . urlencode($iIdProject))
				);
		}
	}
	
	global $babBody;
	$oTmCtx =& getTskMgrContext();
	$babBody->title = bab_toHtml($sTitle);
	
	add_item_menu($aItemMenu);
	
	class BAB_TM_TaskFilterForm extends BAB_BaseFormProcessing
	{
		var $m_aTasksFilter				= null;
		var $m_aTasksTypeFilter			= null;
		var $m_aTask 					= array();
		var $m_aCompletion 				= array();
		var $m_aTaskResponsible 		= array();
		var $m_oFilterSessionContext	= null;
		
		function BAB_TM_TaskFilterForm($sIdx)
		{
			$this->set_data('tg', bab_rp('tg', 'usrTskMgr'));				
			$this->set_data('idx', bab_rp('tg', ''));
			$this->set_data('isProject', (int) bab_rp('isProject', 0));		
			$this->set_caption('sAddTask', bab_translate("Add a task"));
			$this->set_caption('sFilter', bab_translate("Filter"));
			$this->set_caption('sStartDate', bab_translate("Real start date"));
			$this->set_caption('sEndDate', bab_translate("Real end date"));
			$this->set_caption('sPlannedStartDate', bab_translate("Planned start date"));
			$this->set_caption('sPlannedEndDate', bab_translate("Planned end date"));
			$this->set_caption('sTaskResponsible', bab_translate("Task Responsible"));
			$this->set_caption('sCompletion', bab_translate("Completion"));
			
			$this->set_data('sFilterIdx', $sIdx);
			$this->set_data('sFromIdx', $sIdx);
			$this->set_data('sFilterAction', '');
			$this->set_data('sAddTaskIdx', BAB_TM_IDX_DISPLAY_TASK_FORM);
			$this->set_data('sAddTaskAction', '');
			$this->set_data('bIsAddButton', false);
			
			$this->set_data('sPlannedStartDate', '');
			$this->set_data('iPlannedStartHour', 0);
			$this->set_data('sSelectedPlannedStartHour', '');
			$this->set_data('iPlannedStartMinut', 0);
			$this->set_data('sSelectedPlannedStartMinut', '');
			
			$this->set_data('sPlannedEndDate', '');
			$this->set_data('iPlannedEndHour', 0);
			$this->set_data('sSelectedPlannedEndHour', '');
			$this->set_data('iPlannedEndMinut', 0);
			$this->set_data('sSelectedPlannedEndMinut', '');
			
			$this->set_data('sStartDate', '');
			$this->set_data('iStartHour', 0);
			$this->set_data('sSelectedStartHour', '');
			$this->set_data('iStartMinut', 0);
			$this->set_data('sSelectedStartMinut', '');
			
			$this->set_data('sEndDate', '');
			$this->set_data('iEndHour', 0);
			$this->set_data('sSelectedEndHour', '');
			$this->set_data('iEndMinut', 0);
			$this->set_data('sSelectedEndMinut', '');
			
			$this->iniContext();

			$this->set_data('isProjectDisplayed', (0 === (int) bab_rp('isProject', 0)));
			$this->get_data('isProjectDisplayed', $isProjectDisplayed);

			$this->set_data('iIdProjectSpace', (int) bab_rp('iIdProjectSpace', 0));
			
			//Task filter (-1 ==> All, -2 ==> personnal task)
			//Task filter (-1 ==> All, 0 ==> personnal task)
			$this->set_caption('sProject', (0 === (int) bab_rp('isProject', 0) ? bab_translate("Project") : ''));
			$this->set_data('iTaskFilterValue', -1);
			$this->set_data('sTaskFilterSelected', '');
			$this->set_data('sTaskFilterName', '');

			$this->m_aTasksFilter = array(
				array('value' => -1, 'text' => bab_translate("All")));
			
			//Task type filter	
			$this->set_caption('sTaskType', bab_translate("Task type"));
			$this->set_data('iTaskTypeFilterValue', -1);
			$this->set_data('sTaskTypeFilterSelected', '');
			$this->set_data('sTaskTypeFilterName', '');

			$this->m_aTasksTypeFilter = array(
				array('value' => -1, 'text' => bab_translate("All")),
				array('value' => BAB_TM_TASK, 'text' => bab_translate("Task")),
				array('value' => BAB_TM_CHECKPOINT, 'text' => bab_translate("Checkpoint")),
				array('value' => BAB_TM_TODO, 'text' => bab_translate("ToDo"))
			);
				
			$this->initTaskFilter();
			
			$this->set_data('sSelectedTaskCompletion', '');
			$this->m_aCompletion = array(
				array('value' => -1, 'text' => bab_translate("All")),
				array('value' => BAB_TM_IN_PROGRESS, 'text' => bab_translate("In progress")),
				array('value' => BAB_TM_ENDED, 'text' => bab_translate("Ended")),
			);
			
			if(1 === (int) bab_rp('isProject', 0))
			{
				$this->initTaskResponsible();
			}
		}
		
		function iniContext()
		{
			$this->get_data('isProject', $iIsProject);
			
			$sKey = (0 === $iIsProject) ? 'tskMgrPersonnalFilter' : 'tskMgrProjectFilter';
			
			$this->m_oFilterSessionContext = new BAB_TM_SessionContext($sKey);
			
			$this->m_oFilterSessionContext->set('iTaskClass', bab_rp('oTaskTypeFilter', 
				$this->m_oFilterSessionContext->get('iTaskClass', -1)));
			$this->set_data('iTaskClass', $this->m_oFilterSessionContext->get('iTaskClass', -1));	
			
			$this->m_oFilterSessionContext->set('iTaskCompletion', bab_rp('iCompletion', 
				$this->m_oFilterSessionContext->get('iTaskCompletion', -1)));
			$this->set_data('iCompletion', $this->m_oFilterSessionContext->get('iTaskCompletion', -1));	
			
			$this->m_oFilterSessionContext->set('iIdOwner', bab_rp('iIdOwner', 
				$this->m_oFilterSessionContext->get('iIdOwner', 0)));
			$this->set_data('iIdOwner', $this->m_oFilterSessionContext->get('iIdOwner', 0));
			
			$this->m_oFilterSessionContext->set('iIdProject', bab_rp('iIdProject', 
				$this->m_oFilterSessionContext->get('iIdProject', -1)));
			$this->set_data('iIdProject', $this->m_oFilterSessionContext->get('iIdProject', -1));
			
			$iTaskFilter = (int) bab_rp('oTaskFilter', -10);
			if(-10 !== $iTaskFilter)
			{
				$this->m_oFilterSessionContext->set('oTaskFilter', $iTaskFilter);
			}	
			$this->set_data('iSelectedTaskFilter', $this->m_oFilterSessionContext->get('oTaskFilter', -1));
			
			$this->m_oFilterSessionContext->set('sStartDate', bab_rp('_sStartDate', 
				$this->m_oFilterSessionContext->get('sStartDate', '')));
			$this->set_data('sStartDate', $this->m_oFilterSessionContext->get('sStartDate', ''));
			
			$this->m_oFilterSessionContext->set('iStartHour', bab_rp('_oStartHour', 
				$this->m_oFilterSessionContext->get('iStartHour', 0)));
			$this->set_data('iStartHour', $this->m_oFilterSessionContext->get('iStartHour', 0));
			
			$this->m_oFilterSessionContext->set('iStartMinut', bab_rp('_oStartMinut', 
				$this->m_oFilterSessionContext->get('iStartMinut', 0)));
			$this->set_data('iStartMinut', $this->m_oFilterSessionContext->get('iStartMinut', 0));

			$this->m_oFilterSessionContext->set('sEndDate', bab_rp('_sEndDate', 
				$this->m_oFilterSessionContext->get('sEndDate', '')));
			$this->set_data('sEndDate', bab_rp('_sEndDate', ''));
			
			$this->m_oFilterSessionContext->set('iEndHour', bab_rp('_oEndHour', 
				$this->m_oFilterSessionContext->get('iEndHour', 0)));
			$this->set_data('iEndHour', $this->m_oFilterSessionContext->get('iEndHour', 0));
			
			$this->m_oFilterSessionContext->set('iEndMinut', bab_rp('_oEndMinut', 
				$this->m_oFilterSessionContext->get('iEndMinut', 0)));
			$this->set_data('iEndMinut', $this->m_oFilterSessionContext->get('iEndMinut', 0));
			
			$this->m_oFilterSessionContext->set('sPlannedStartDate', bab_rp('_sPlannedStartDate', 
				$this->m_oFilterSessionContext->get('sPlannedStartDate', '')));
			$this->set_data('sPlannedStartDate', $this->m_oFilterSessionContext->get('sPlannedStartDate', ''));
			
			$this->m_oFilterSessionContext->set('iPlannedStartHour', bab_rp('_oPlannedStartHour', 
				$this->m_oFilterSessionContext->get('iPlannedStartHour', 0)));
			$this->set_data('iPlannedStartHour', $this->m_oFilterSessionContext->get('iPlannedStartHour', 0));
			
			$this->m_oFilterSessionContext->set('iPlannedStartMinut', bab_rp('_oPlannedStartMinut', 
				$this->m_oFilterSessionContext->get('iPlannedStartMinut', 0)));
			$this->set_data('iPlannedStartMinut', $this->m_oFilterSessionContext->get('iPlannedStartMinut', 0));

			$this->m_oFilterSessionContext->set('sPlannedEndDate', bab_rp('_sPlannedEndDate', 
				$this->m_oFilterSessionContext->get('sPlannedEndDate', '')));
			$this->set_data('sPlannedEndDate', bab_rp('_sPlannedEndDate', ''));
			
			$this->m_oFilterSessionContext->set('iPlannedEndHour', bab_rp('_oPlannedEndHour', 
				$this->m_oFilterSessionContext->get('iPlannedEndHour', 0)));
			$this->set_data('iPlannedEndHour', $this->m_oFilterSessionContext->get('iPlannedEndHour', 0));
			
			$this->m_oFilterSessionContext->set('iPlannedEndMinut', bab_rp('_oPlannedEndMinut', 
				$this->m_oFilterSessionContext->get('iPlannedEndMinut', 0)));
			$this->set_data('iPlannedEndMinut', $this->m_oFilterSessionContext->get('iPlannedEndMinut', 0));
			
//			bab_debug($_GET);
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
//						if(bab_isAccessValid(BAB_TSKMGR_PROJECTS_MANAGERS_GROUPS_TBL, $datas['iIdProject']))
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
				$this->m_aTasksFilter[] = array('value' => 0, 
					'text' => bab_translate("Personnal task"));
			}
			
			if(count($this->m_aTasksFilter) >= 2)
			{
				$this->set_data('bIsAddButton', true);
			}
			
			reset($this->m_aTasksFilter);
		}
		
		function initTaskResponsible()
		{
			bab_getAllTaskIndexedById($this->m_oFilterSessionContext->get('iIdProject', 0), $this->m_aTask);
			
			while(false != ($datas = each($this->m_aTask)))
			{
				$aTaskResponsible = array();
				bab_getTaskResponsibles($datas['value']['id'], $aTaskResponsible);
				if(count($aTaskResponsible) > 0)
				{
					$aTaskResponsible = each($aTaskResponsible);
					$aTaskResponsible = $aTaskResponsible['value'];
					$iIdResponsible = (int) $aTaskResponsible['id'];
					$sName			= (string) $aTaskResponsible['name'];
					
					if(false === array_key_exists($iIdResponsible, $this->m_aTaskResponsible))
					{
						$this->m_aTaskResponsible[$iIdResponsible] = array('iIdResponsable' => $iIdResponsible,
							'sName' => $sName);
					}
				}
			}
			reset($this->m_aTask);			
		}
		
		function getNextTaskResponsible()
		{
			$this->set_data('sSelectedUserName', '');
			$this->get_data('iIdOwner', $iIdOwner);
			
			$this->set_data('idResponsible', 0);
			$this->set_data('sSelectedUserName', '');
			$this->set_data('sUserName', '');
			
			$datas = each($this->m_aTaskResponsible);
			if(false !== $datas)
			{
				$iIdResponsible = (int) $datas['value']['iIdResponsable'];
				$sName			= (string) $datas['value']['sName'];
				
				$this->set_data('idResponsible', $iIdResponsible);
				$this->set_data('sSelectedUserName', (((int) $iIdOwner === $iIdResponsible) ? 'selected="selected"' : ''));
				$this->set_data('sUserName', bab_toHtml($sName));
				return true;
			}			
			return false;
		}
		
		function getNextTaskFilter()
		{
			$datas = each($this->m_aTasksFilter);
			if(false != $datas)
			{
				$this->get_data('iSelectedTaskFilter', $iSelectedTaskFilter);
				$this->set_data('sTaskFilterSelected', bab_toHtml(($iSelectedTaskFilter == $datas['value']['value']) ? 'selected="selected"' : ''));
				
				$this->set_data('iTaskFilterValue', bab_toHtml($datas['value']['value']));				
				$this->set_data('sTaskFilterName', bab_toHtml($datas['value']['text']));
				
				return true;				
			}
			return false;
		}
		
		function getNextTaskTypeFilter()
		{
			$datas = each($this->m_aTasksTypeFilter);
			if(false != $datas)
			{
				$this->get_data('iTaskClass', $iSelectedTaskTypeFilter);
				$this->set_data('sTaskTypeFilterSelected', bab_toHtml(($iSelectedTaskTypeFilter == $datas['value']['value']) ? 'selected="selected"' : ''));
				
				$this->set_data('iTaskTypeFilterValue', bab_toHtml($datas['value']['value']));				
				$this->set_data('sTaskTypeFilterName', bab_toHtml($datas['value']['text']));
				
				return true;				
			}
			return false;
		}
			
		function getNextTaskCompletion()
		{
			$datas = each($this->m_aCompletion);
			if(false != $datas)
			{
				$this->get_data('iCompletion', $iCompletion);
				$this->set_data('sSelectedTaskCompletion', (($iCompletion == $datas['value']['value']) ? 'selected="selected"' : ''));
				
				$this->set_data('iTaskCompletionValue', bab_toHtml($datas['value']['value']));				
				$this->set_data('sTaskCompletionText', bab_toHtml($datas['value']['text']));
				
				return true;				
			}
			return false;
		}
		
		function printTemplate()
		{
			return bab_printTemplate($this, 'tmUser.html', 'taskListFilter');
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
	
	$oTaskFilterForm = new BAB_TM_TaskFilterForm($sIdx);
	$iTaskFilter = $oTaskFilterForm->m_oFilterSessionContext->get('iIdProject');

	$iTaskClass = $oTaskFilterForm->m_oFilterSessionContext->get('iTaskClass');
	$iTaskCompletion = $oTaskFilterForm->m_oFilterSessionContext->get('iTaskCompletion');

	global $babUrlScript;
	$sGanttViewUrl = $babUrlScript . '?tg=' . urlencode('usrTskMgr') . '&idx=' . urlencode(BAB_TM_IDX_DISPLAY_GANTT_CHART);
	
	if(0 !== $iIdProjectSpace)
	{
		$sGanttViewUrl .= '&iIdProjectSpace=' . urlencode($iIdProjectSpace); 
	}
	
	$aFilters = array();
	if(-1 != $iTaskFilter)
	{
		//iTaskFilter (-1 ==> All, -2 ==> personnal task)
		//if(-2 == $iTaskFilter)
		//iTaskFilter (-1 ==> All, -2 ==> personnal task)
		if(0 === $iTaskFilter)
		{
			$aFilters['isPersonnal'] = BAB_TM_YES;
			$sGanttViewUrl .= '&isPersonnal=' . urlencode(BAB_TM_YES);
		}
		else 
		{
			
			$aFilters['iIdProject'] = $iTaskFilter;
			$sGanttViewUrl .= '&iIdProject=' . urlencode($aFilters['iIdProject']);
		}
	}
		
	if(-1 != $iTaskClass)
	{
		$aFilters['iTaskClass'] = $iTaskClass;
		$sGanttViewUrl .= '&iTaskClass=' . urlencode($iTaskClass);
	}

	if(0 === $isProject)
	{
		$aFilters['iIdOwner'] = $GLOBALS['BAB_SESS_USERID'];
		
		$sGanttViewUrl .= '&iIdOwner=' . urlencode($GLOBALS['BAB_SESS_USERID']);
	}
	
	if(-1 !== $iTaskCompletion)
	{
		$aFilters['iCompletion'] = $iTaskCompletion;
	}
	
	global $babInstallPath;
	require_once($babInstallPath . 'tmTaskClasses.php');
	
	$sStartDate			= (string) $oTaskFilterForm->m_oFilterSessionContext->get('sStartDate');
	$sEndDate 			= (string) $oTaskFilterForm->m_oFilterSessionContext->get('sEndDate');
	$sPlannedStartDate	= (string) $oTaskFilterForm->m_oFilterSessionContext->get('sPlannedStartDate');
	$sPlannedEndDate	= (string) $oTaskFilterForm->m_oFilterSessionContext->get('sPlannedEndDate');

	if(strlen(trim($sStartDate)) > 0)
	{
		$oDate = BAB_DateTime::fromDateStr(str_replace('-', '/', $sStartDate));
		if(!is_null($oDate))
		{
			$oDate->init($oDate->_iYear, $oDate->_iMonth, $oDate->_iDay, 
				$oTaskFilterForm->m_oFilterSessionContext->get('iStartHour'), 
				$oTaskFilterForm->m_oFilterSessionContext->get('iStartMinut'));
			$aFilters['sStartDate'] = $oDate->getIsoDateTime();
		}
	}

	if(strlen(trim($sEndDate)) > 0)
	{
		$oDate = BAB_DateTime::fromDateStr(str_replace('-', '/', $sEndDate));
		if(!is_null($oDate))
		{
			$oDate->init($oDate->_iYear, $oDate->_iMonth, $oDate->_iDay, 
				$oTaskFilterForm->m_oFilterSessionContext->get('iEndHour'), 
				$oTaskFilterForm->m_oFilterSessionContext->get('iEndMinut'));
			$aFilters['sEndDate'] = $oDate->getIsoDateTime();
		}
	}

	if(strlen(trim($sPlannedStartDate)) > 0)
	{
		$oDate = BAB_DateTime::fromDateStr(str_replace('-', '/', $sPlannedStartDate));
		if(!is_null($oDate))
		{
			$oDate->init($oDate->_iYear, $oDate->_iMonth, $oDate->_iDay, 
				$oTaskFilterForm->m_oFilterSessionContext->get('iPlannedStartHour'), 
				$oTaskFilterForm->m_oFilterSessionContext->get('iPlannedStartMinut'));
			$aFilters['sPlannedStartDate'] = $oDate->getIsoDateTime();
		}
	}

	if(strlen(trim($sPlannedEndDate)) > 0)
	{
		$oDate = BAB_DateTime::fromDateStr(str_replace('-', '/', $sPlannedEndDate));
		if(!is_null($oDate))
		{
			$oDate->init($oDate->_iYear, $oDate->_iMonth, $oDate->_iDay, 
				$oTaskFilterForm->m_oFilterSessionContext->get('iPlannedEndHour'), 
				$oTaskFilterForm->m_oFilterSessionContext->get('iPlannedEndMinut'));
			$aFilters['sPlannedEndDate'] = $oDate->getIsoDateTime();
		}
	}
	
	$iIdOwner = (int) bab_rp('iIdOwner', 0);
	if(0 !== $iIdOwner)
	{
		$aFilters['iIdOwner'] = $iIdOwner;
	}
	
	// A non-manager user must not see the tasks he refused.
	$iUserProfil = (int) $oTmCtx->getUserProfil();
	$aFilters['bIsManager'] = ($iUserProfil === BAB_TM_PROJECT_MANAGER);
	
	require_once($GLOBALS['babInstallPath'] . 'utilit/multipage.php');
	
	
	class BAB_TaskDS extends BAB_MySqlDataSource
	{
		var $m_sImgSrc				= '';
		var $m_sImgText				= '';
		var $m_fTotalPlannedTime	= 0.00;
		var $m_fTotalTime			= 0.00;
		var $m_fTotalPlannedCost	= 0.00;
		var $m_fTotalCost			= 0.00;
		var $m_aDisplayedField		= array();
		
		function BAB_TaskDS($query, $iPage, $iNbRowsPerPage, $aDisplayedField)
		{
			parent::BAB_MySqlDataSource($query, $iPage, $iNbRowsPerPage);
			
			$this->m_aDisplayedField = $aDisplayedField;
		}
		
		function getNextItem()
		{
			$datas = parent::getNextItem();
			if(false != $datas)
			{
				$aDate = array('startDate', 'endDate',
					'plannedStartDate', 'plannedEndDate',
					'sCreatedDate', 'sModifiedDate');
				
				foreach($aDate as $sKey)
				{				
					$this->setDateTime($datas[$sKey]);	
				}
				
				if($datas['iPlannedTimeDurationUnit'] == BAB_TM_DAY)
				{
					$datas['iPlannedTime'] = ((float) $datas['iPlannedTime'] * 24);
				}
				$this->m_fTotalPlannedTime += (float) $datas['iPlannedTime'];
				
				if($datas['iTimeDurationUnit'] == BAB_TM_DAY)
				{
					$datas['iTime'] = ((float) $datas['iTime'] * 24);
				}
				$this->m_fTotalTime	+= (float) $datas['iTime'];
				
				
				$this->m_fTotalPlannedCost	+= (float) $datas['iPlannedCost'];
				$this->m_fTotalCost			+= (float) $datas['iCost'];
		
				switch($datas['iClass'])
				{
					case BAB_TM_TASK:
						$this->m_sImgSrc = $GLOBALS['babSkinPath'] . 'images/Puces/kded.png';
						$this->m_sImgText = bab_toHtml($datas['sClass']);
						$datas['sClass'] = bab_printTemplate($this, 'multipage.html', 'img');
						break;
					case BAB_TM_CHECKPOINT:
						$this->m_sImgSrc = $GLOBALS['babSkinPath'] . 'images/Puces/kmines.png';
						$this->m_sImgText = bab_toHtml($datas['sClass']);
						$datas['sClass'] = bab_printTemplate($this, 'multipage.html', 'img');
						break;
					case BAB_TM_TODO:
						$this->m_sImgSrc = $GLOBALS['babSkinPath'] . 'images/Puces/kate.png';
						$this->m_sImgText = bab_toHtml($datas['sClass']);
						$datas['sClass'] = bab_printTemplate($this, 'multipage.html', 'img');
						break;
				}
				
				$aIdUser = array('idOwner', 'iIdUserCreated', 'iIdUserModified');
				foreach($aIdUser as $sKey)
				{				
					$datas[$sKey] = bab_toHtml(bab_getUserName($datas[$sKey]));
				}
			}
			return $datas;
		}
		
		function getLastRow()
		{
			$datas = array();
			foreach($this->m_aDisplayedField as $sKey)
			{
				$datas[$sKey] = '';
			}
			
			if(array_key_exists('iPlannedTime', $datas))
			{
				$datas['iPlannedTime'] = number_format($this->m_fTotalPlannedTime, 2, '.', '');
			}

			if(array_key_exists('iTime', $datas))
			{
				$datas['iTime'] = number_format($this->m_fTotalTime, 2, '.', '');
			}

			if(array_key_exists('iPlannedCost', $datas))
			{
				$datas['iPlannedCost'] = number_format($this->m_fTotalPlannedCost, 2, '.', '');
			}

			if(array_key_exists('iCost', $datas))
			{
				$datas['iCost'] = number_format($this->m_fTotalCost, 2, '.', '');
			}
			return $datas;
		}
		
		function setDateTime(&$sDateTime)
		{
			if('0000-00-00 00:00:00' != $sDateTime)
			{
				$sDateTime = bab_toHtml(bab_shortDate(bab_mktime($sDateTime)));
			}
			else
			{
				$sDateTime = '&nbsp';
			}
		}
	}

	
	class BAB_TaskMultipage extends BAB_MultiPageBase
	{
		var $aCurrentColumnHeader;
		var $aCurrentColumnData;
		var $sClassName 	= '';
		var $bFirst 		= false;
		var $m_bLastPage	= false;
		var $sCostClassName = '';
		
		function BAB_TaskMultipage()
		{
			parent::BAB_MultiPageBase();
		}
	
		function setColumnDataSource($oDataSource)
		{
			parent::setColumnDataSource($oDataSource);
			
			if($this->iTotalNumOfRows > 0)
			{
				$iNbPages = ceil($this->iTotalNumOfRows / $this->iNbRowsPerPage);
				
				$isProject = (int) bab_rp('isProject', 0);
				$this->m_bLastPage = ($this->iPage == $iNbPages && 0 !== $isProject);
			}
			else 
			{
				$this->m_bLastPage = false;
			}
		}
		
		function addColumnHeader($iId, $aText, $aDataSourceFieldName)
		{
			$this->aColumnHeaders[] = array('iId' => $iId, 'aText' => $aText, 'aDataSourceFieldName' => $aDataSourceFieldName);
		}
		
		function getNextColumnHeader()
		{
			$this->bFirst = !$this->bFirst;
			$this->aCurrentColumnHeader = each($this->aColumnHeaders);
			
			if(false !== $this->aCurrentColumnHeader)
			{
				
reset($this->aCurrentColumnHeader['value']['aText']);
reset($this->aCurrentColumnHeader['value']['aDataSourceFieldName']);
				
				return true;
			}
			
			$this->bFirst = false;
			reset($this->aColumnHeaders);
			return false;
		}
		
		function getNextColumnHeaderItem()
		{
			$aText = each($this->aCurrentColumnHeader['value']['aText']);
			$aDataSourceFieldName = each($this->aCurrentColumnHeader['value']['aDataSourceFieldName']);
			if(false !== $aText && false !== $aDataSourceFieldName)
			{
				if($this->bFirst)
				{
					$this->sClassName = '';
					$this->bFirst = false;
				}
				else
				{
					$this->sClassName = 'planned';
				}
				
				if($this->bIsColumnHeaderUrl)
				{
					$sOrderBy = (string) bab_rp('sOrderBy', '');
					$sOrder = (string) bab_rp('sOrder', '');
					
					$this->sColumnHeaderUrl = $this->buildPageUrl($this->iPage, false);
					$this->sColumnHeaderUrl = ereg_replace('&sOrderBy=[^&.]+', '', $this->sColumnHeaderUrl);
					$this->sColumnHeaderUrl = ereg_replace('&sOrder=[^&.]+', '', $this->sColumnHeaderUrl);
					$this->sColumnHeaderUrl .= '&sOrderBy=' . $aDataSourceFieldName['value'];
					
					if($sOrderBy === (string) $aDataSourceFieldName['value'])
					{
						if($sOrder === (string) 'ASC')
						{
							$this->sColumnHeaderUrl .= '&sOrder=' . 'DESC';
						}
						else 
						{
							$this->sColumnHeaderUrl .= '&sOrder=' . 'ASC';
						}
					}
					else 
					{
						$this->sColumnHeaderUrl .= '&sOrder=' . 'ASC';
					}
					$this->sColumnHeaderUrl = htmlentities($this->sColumnHeaderUrl);
				}
				else 
				{
					$this->sColumnHeaderUrl = '#';
				}
				$this->sColumnHeaderText = $aText['value'];
				return true;
			}
			return false;
		}
	
		function getNextColumnData()
		{
			$this->bFirst = !$this->bFirst;
			$this->aCurrentColumnHeader = each($this->aColumnHeaders);
			if(false !== $this->aCurrentColumnHeader)
			{
				
reset($this->aCurrentColumnHeader['value']['aText']);
reset($this->aCurrentColumnHeader['value']['aDataSourceFieldName']);
				
				return true;
			}
			
			$this->bFirst = false;
			reset($this->aColumnHeaders);
			return false;
		}
	
		function getNextColumnDataItem()
		{
			$aText = each($this->aCurrentColumnHeader['value']['aText']);
			$aDataSourceFieldName = each($this->aCurrentColumnHeader['value']['aDataSourceFieldName']);

			if(false !== $aText && false !== $aDataSourceFieldName)
			{
				if($this->bFirst)
				{
					$this->sClassName = '';
					$this->bFirst = false;
				}
				else
				{
					$this->sClassName = 'planned';
				}
				
				$this->sCostClassName = '';
				$aColumn = array('iTime', 'iCost', 'iPlannedTime', 'iPlannedCost');
				if(in_array($aDataSourceFieldName['value'], $aColumn))
				{
					$this->sCostClassName = 'cost';
				}
				
				$this->sColumnData = '???';
				if(isset($this->aRow[$aDataSourceFieldName['value']]))
				{
					$this->sColumnData = $this->aRow[$aDataSourceFieldName['value']];
				}
				return true;
			}
			return false;
		}
		
		function getLastRow()
		{
			static $iIndex = 0;
			
			if(!is_null($this->oDataSource) && is_a($this->oDataSource, 'BAB_DataSourceBase'))
			{
				$this->aRow = $this->oDataSource->getLastRow();
					
				if($iIndex === 0 && false !== $this->aRow)
				{
					$iIndex++;
					$this->bIsAltbg = !$this->bIsAltbg;
					return true;
				}
			}
			
			$iIndex = 0;
			return false;
		}

		function printTemplate()
		{
			//+1 for the action column
			$this->iNbrColumnHeaders = count($this->aColumnHeaders) + 1;
			return parent::printTemplate();
		}
	}
	
	
	
	$oMultiPage = new BAB_TaskMultipage();

	$oMultiPage->sTemplateFileName = 'tmUser.html';
	$oMultiPage->sMultipageTemplate = 'taskMultipage';
	
	$oMultiPage->addPaginationAndFormParameters('sFromIdx', $sIdx);
	$oMultiPage->addPaginationAndFormParameters('isProject', $isProject);
	$oMultiPage->addPaginationAndFormParameters('iIdProject', $iIdProject);
	$oMultiPage->addPaginationAndFormParameters('iIdOwner', $iIdOwner);
	$oMultiPage->addPaginationAndFormParameters('idx', $sIdx);
	
	if(0 !== $iIdProjectSpace)
	{
		$oMultiPage->addPaginationAndFormParameters('iIdProjectSpace', $iIdProjectSpace);
	}
	
	
	$aOrder = array();
	$sOrderBy = (string) bab_rp('sOrderBy', '');
	if(strlen(trim($sOrderBy)) > 0)
	{
		$oMultiPage->addPaginationAndFormParameters('sOrderBy', $sOrderBy);
	
		$sOrder = (string) bab_rp('sOrder', '');
		if(strlen(trim($sOrder)) > 0)
		{
			$oMultiPage->addPaginationAndFormParameters('sOrder', $sOrder);
			$aOrder = array('sName' => $sOrderBy, 'sOrder' => $sOrder);
		}
	}

	$aColumnHeader = bab_tskmgr_getSelectedField($iIdProject);

//	bab_debug('iIdProject ==> ' . $iIdProject);
//	bab_debug($aColumnHeader);

	$oMultiPage->sIdx = $sIdx;
//	$oMultiPage->iNbRowsPerPage = (int) 2;
//	bab_debug($oMultiPage->m_aAdditionnalPaginationAndFormParameters);

$aField		= array();
$aLeftJoin	= array();
$aWhere		= array();
		
	$aLeftJoin[]	= 'LEFT JOIN ' . 
		BAB_TSKMGR_SELECTED_TASK_FIELDS_TBL . ' stf ON stf.iIdProject = t0.idProject ';
		
	$aWhere[]		= 'AND stf.iIdProject = \'' . $iIdProject . '\'';
	
	$sTableAlias = 't5';
	
	$aDisplayedField = array();
	
	foreach($aColumnHeader as $aColumnHeaderItem)
	{
		$iPosition				= $aColumnHeaderItem['iPosition'];
		$aCaption				= explode(',', $aColumnHeaderItem['sLegend']);
		$aDataSourceFieldName	= explode(',', $aColumnHeaderItem['sName']);
		
		foreach($aCaption as $iKey => $sCaption)
		{
			$aCaption[$iKey] = bab_translate($sCaption);
		}

		
		foreach($aDataSourceFieldName as $iKey => $sFieldName)
		{
			if(BAB_TM_ADDITIONAL_FIELD == $aColumnHeaderItem['iType'])
			{
				$sAlias = $sTableAlias . '__' . $sFieldName;
				
				$aDataSourceFieldName[$iKey] = $sAlias;
				
				$aField[] = $sTableAlias . '.sField' . $aColumnHeaderItem['iId'] . ' AS ' . $sAlias;
				
				$aDisplayedField[$sAlias] = $sAlias;
			}
			else
			{
				$aDisplayedField[$sFieldName] = $sFieldName;
			}
		}
				
		$oMultiPage->addColumnHeader($iPosition, $aCaption, $aDataSourceFieldName);
	}
	
	$sTableName = bab_tskmgr_getAdditionalFieldTableName($iIdProjectSpace, $iIdProject);
	
	require_once $GLOBALS['babInstallPath'] . 'utilit/upgradeincl.php';
	
	if(bab_isTable($sTableName))
	{
		$aLeftJoin[]	= 'LEFT JOIN ' . 
			$sTableName . ' ' . $sTableAlias . ' ON ' . $sTableAlias . '.iIdTask = t0.id ';
	}
	else 
	{
		$aField		= array();
		$aLeftJoin	= array();
		$aWhere		= array();
	}
		
	$oMultiPage->bIsColumnHeaderUrl = true;
	
	//Doit-on afficher la ligne de totalisation ?
	{
		$aToLook = array('iPlannedTime', 'iTime', 
			'iPlannedCost', 'iCost');
		
		$oMultiPage->m_bLastPage = false;
		foreach($aToLook as $sKey)
		{
			if(array_key_exists($sKey, $aDisplayedField))
			{
				$oMultiPage->m_bLastPage = true;
				break;
			}
		}
	}
		
//*	
	$oMultiPage->setColumnDataSource(new BAB_TaskDS(bab_selectTaskQueryEx($aFilters, $aField, $aLeftJoin, $aWhere, $aOrder), 
		(int) bab_rp('iPage', 1), $oMultiPage->iNbRowsPerPage, $aDisplayedField));
//*/

/*
	$oMultiPage->setColumnDataSource(new BAB_TaskDS(bab_selectTaskQuery($aFilters, $aOrder), 
		(int) bab_rp('iPage', 1), $oMultiPage->iNbRowsPerPage, $aDisplayedField));
//*/
	
	
	
	$sTg = bab_rp('tg', 'admTskMgr');
	$sLink = $GLOBALS['babUrlScript'] . '?tg=' . urlencode($sTg) . '&idx=' . urlencode(BAB_TM_IDX_DISPLAY_TASK_FORM) .
		'&sFromIdx=' . urlencode($sIdx) . '&isProject=' . urlencode($isProject);


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
	
	$GLOBALS['babBody']->addStyleSheet('taskManager.css');

	$oToolbar = new BAB_TM_Toolbar();
	$oToolbar->addToolbarItem(
		new BAB_TM_ToolbarItem(bab_translate("Display the gantt View"), 'javascript:bab_popup("' . $sGanttViewUrl . '", 150, 1)', 
			'ganttView.png', bab_translate("Display the gantt View"), bab_translate("Display the gantt View"), 'oGanttIcon'),
		new BAB_TM_ToolbarItem(bab_translate("Add a task"), 'javascript:addTask(\'displayTaskForm\', \'\')', 
			'list-add.png', bab_translate("Add a task"), bab_translate("Add a task"), 'oAddIcon'));

	if($iIdProject > 0 && (bab_isAccessValid(BAB_TSKMGR_PROJECTS_MANAGERS_GROUPS_TBL, $iIdProject) || 
		bab_isAccessValid(BAB_TSKMGR_PROJECTS_SUPERVISORS_GROUPS_TBL, $iIdProject)) )
	{
		$sExportUrl = $babUrlScript . '?tg=' . urlencode('usrTskMgr') . '&action=' . urlencode(BAB_TM_ACTION_PROCESS_EXPORT) . 
			'&iIdProjectSpace=' . urlencode($iIdProjectSpace);
		
		$oToolbar->addToolbarItem(
			new BAB_TM_ToolbarItem(bab_translate("Export"), $sExportUrl, 
				'cvsExport.png', bab_translate("Export"), bab_translate("Export"), 'oExportIcon'));
	}

	$oToolbar->addToolbarItem(
		new BAB_TM_ToolbarItem(bab_translate("Search"), 'javascript:tskMgr_showHideFilter()', 
			'search.png', bab_translate("Search"), bab_translate("Search"), 'oSearchIcon'));
		
	$GLOBALS['babBody']->babecho($oToolbar->printTemplate());
	
	$oTaskFilterForm->raw_2_html(BAB_RAW_2_HTML_CAPTION);
	$oTaskFilterForm->raw_2_html(BAB_RAW_2_HTML_DATA);
	$GLOBALS['babBody']->babecho($oTaskFilterForm->printTemplate());
	$GLOBALS['babBody']->babecho($oMultiPage->printTemplate());
}



function displayTaskForm()
{
	global $babBody;

	$oTmCtx =& getTskMgrContext();
	$iIdProjectSpace = (int) $oTmCtx->getIdProjectSpace();
	$iIdProject = (int) $oTmCtx->getIdProject();
	
	if(0 < $iIdProject)
	{
		if(false != bab_getProject($iIdProject, $aProject))
		{
			$iIdProjectSpace = $oTmCtx->m_iIdProjectSpace = (int) $aProject['idProjectSpace'];
		}
	}

	$bIsTaskResp = false;
	$bIsManager = false;

	if(0 < $iIdProjectSpace && 0 < $iIdProject)
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
		$iIdTask = (int) bab_rp('iIdTask', 0);
		$tab_caption = ($iIdTask == 0) ? bab_translate("Add a task") : bab_translate("Edition of a task");
		$babBody->title = bab_toHtml($tab_caption);

		$aItemMenu = array();

		$isProject = (int) bab_rp('isProject', 0);
		if(1 === $isProject)
		{
			$aItemMenu[] = array(
				'idx' => BAB_TM_IDX_DISPLAY_PROJECT_TASK_LIST,
				'mnuStr' => bab_translate("Tasks of the project"),
				'url' => $GLOBALS['babUrlScript'] . '?tg=' . urlencode('usrTskMgr') . '&idx=' . urlencode(BAB_TM_IDX_DISPLAY_PROJECT_TASK_LIST) . 
				'&isProject=' . urlencode($isProject) . '&iIdProjectSpace=' . urlencode($iIdProjectSpace) .
				'&iIdProject=' . urlencode($iIdProject));
		}
		
		$aItemMenu[] = array(		
				'idx' => BAB_TM_IDX_DISPLAY_TASK_FORM,
				'mnuStr' => $tab_caption,
				'url' => $GLOBALS['babUrlScript'] . '?tg=' . urlencode('usrTskMgr') . '&idx=' . urlencode(BAB_TM_IDX_DISPLAY_TASK_FORM) . 
				'&iIdProject=' . urlencode($iIdProject));

		add_item_menu($aItemMenu);
		
		global $babInstallPath;
		require_once($babInstallPath . 'tmTaskClasses.php');
		
		$oTaskForm = & new BAB_TaskForm();
		
		$oTaskForm->raw_2_html(BAB_RAW_2_HTML_CAPTION);
//		$oTaskForm->raw_2_html(BAB_RAW_2_HTML_DATA);
		$babBody->babecho(bab_printTemplate($oTaskForm, 'tmUser.html', 'taskForm'));
	}
	else 
	{
		$GLOBALS['babBody']->msgerror = bab_toHtml(bab_translate("You do not have the right to create/edit task"));
	}
}

function displayDeleteTaskForm()
{
	global $babBody;
	$babBody->title = bab_toHtml(bab_translate("Delete task"));
	
	$oTmCtx =& getTskMgrContext();
	$iIdProjectSpace = (int) $oTmCtx->getIdProjectSpace();
	$iIdProject = (int) $oTmCtx->getIdProject();
	$iIdTask = (int) $oTmCtx->getIdTask();
	$iUserProfil = (int) $oTmCtx->getUserProfil();
	$isProject = (int) bab_rp('isProject', 0);
	
	$bf = & new BAB_BaseFormProcessing();
	$bf->set_data('iIdProjectSpace', $iIdProjectSpace);
	$bf->set_data('iIdProject', $iIdProject);
	$bf->set_data('isProject', $isProject);
	$bf->set_data('objectName', 'iIdTask');
	$bf->set_data('iIdObject', $iIdTask);
	$bf->set_data('tg', 'usrTskMgr');

	$bf->set_caption('yes', bab_translate("Yes"));
	$bf->set_caption('no', bab_translate("No"));

	$sFromIdx = bab_rp('sFromIdx', BAB_TM_IDX_DISPLAY_MY_TASK_LIST);
	if(!isFromIdxValid($sFromIdx))
	{
		$sFromIdx = BAB_TM_IDX_DISPLAY_MY_TASK_LIST;
	}
	$bf->set_data('idx', $sFromIdx);
	
	global $babInstallPath;
	require_once($babInstallPath . 'tmTaskClasses.php');
	
	$oTask = new BAB_TM_Task();
	
	$sTemplateName = 'warningyesno';
	
	$aDependingTasks = array();
	bab_getDependingTasks($iIdTask, $aDependingTasks);
	if($oTask->loadFromDataBase($iIdTask))
	{
		if(count($aDependingTasks) == 0)	
		{
			$bf->set_data('action', BAB_TM_ACTION_DELETE_TASK);
			$bf->set_caption('warning', bab_translate("This action will delete the task and all references"));
			$bf->set_caption('message', bab_translate("Continue ?"));
			$bf->set_caption('title', bab_translate("Short description") . " = " . $oTask->m_aTask['sShortDescription']);
		}
		else
		{
			$sTemplateName = 'warning';
			$bf->set_caption('no', bab_translate("Back to list"));
			$bf->set_caption('warning', bab_translate("You can not delete tis task because another task are linked on it"));
			$bf->set_caption('message', bab_translate(""));
			$bf->set_caption('title', bab_translate("Short description") . " = " . $oTask->m_aTask['sShortDescription']);
		}
	}
	else 
	{
		$bf->set_data('action', '');
		$bf->set_caption('warning', bab_translate("Cannot get the task information"));
		$bf->set_caption('message', '');
		$bf->set_caption('title', '');
	}
		
	$bf->raw_2_html(BAB_RAW_2_HTML_CAPTION);
	$bf->raw_2_html(BAB_RAW_2_HTML_DATA);
	$babBody->babecho(bab_printTemplate($bf, 'tmCommon.html', $sTemplateName));
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
						$this->set_data('tnSelected', bab_toHtml('selected="selected"'));
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
				'url' => $GLOBALS['babUrlScript'] . '?tg=' . urlencode('usrTskMgr') . '&idx=' . urlencode(BAB_TM_IDX_DISPLAY_PERSONNAL_TASK_CONFIGURATION_FORM))
		);
		
		add_item_menu($itemMenu);
		$babBody->title = bab_toHtml(bab_translate("Personnals tasks configuration"));
		$pjc = & new BAB_TM_Configuration();
		$pjc->raw_2_html(BAB_RAW_2_HTML_CAPTION);
		$pjc->raw_2_html(BAB_RAW_2_HTML_DATA);
		$babBody->babecho(bab_printTemplate($pjc, 'tmUser.html', 'PersonnalTaskConfiguration'));
	}
}

function displayGanttChart()
{
	global $babInstallPath;
	require_once($babInstallPath . 'tmGantt.php');
	
	$iIdProjectSpace	= (int) bab_rp('iIdProjectSpace', 0);
	$sStartDate			= date("Y-m-d");
	
	if(0 !== $iIdProjectSpace)
	{
		$sStartDate = getFirstProjectTaskDate(bab_rp('iIdProject'));
		if(strlen($sStartDate) > 10)
		{
			$sStartDate = substr($sStartDate, 0, 10);
		}
	}
	else
	{
		$sStartDate = bab_rp('date', $sStartDate);
	}
	
	$oGantt =& getGanttTaskInstance('BAB_TM_Gantt');
	$oGantt->init($sStartDate);
	die(bab_printTemplate($oGantt, 'tmUser.html', "gantt"));
}

function tskmgClosePopup()
{
	$bf = & new BAB_BaseFormProcessing();
	die(bab_printTemplate($bf, 'tmUser.html', 'close_popup'));
}



function displayOrderTaskFieldsForm()
{
	global $babBody;
	
	$iIdProject			= (int) bab_rp('iIdProject', 0);
	$iIdProjectSpace	= (int) bab_rp('iIdProjectSpace', 0);
	
	if(!bab_isAccessValid(BAB_TSKMGR_PROJECTS_MANAGERS_GROUPS_TBL, $iIdProject))
	{
		$GLOBALS['babBody']->msgerror = bab_toHtml(bab_translate("You are not a projects manager"));
		return false;
	}
	
	$sTitle = bab_translate("Field(s) list");
	if(false !== bab_getProject($iIdProject, $aProject))
	{
		$sTitle .= ': ' . $aProject['name'];
	}

	$aItemMenu = array(
		array(
			'idx' => BAB_TM_IDX_DISPLAY_ORDER_TASK_FIELDS_FORM,
			'mnuStr' => bab_translate("Field(s) list"),
			'url' => $GLOBALS['babUrlScript'] . '?tg=' . urlencode('usrTskMgr') . '&idx=' . urlencode(BAB_TM_IDX_DISPLAY_ORDER_TASK_FIELDS_FORM) . 
			'&iIdProjectSpace=' . urlencode($iIdProjectSpace) .
			'&iIdProject=' . urlencode($iIdProject))
		);
		
	global $babBody;
	$oTmCtx =& getTskMgrContext();
	$babBody->title = bab_toHtml($sTitle);
	
	add_item_menu($aItemMenu);
	
	class BAB_OrderTaskFields extends BAB_BaseFormProcessing
	{
		function BAB_OrderTaskFields($iIdProjectSpace, $iIdProject)
		{
			parent::BAB_BaseFormProcessing();
			
			$this->set_data('idx', BAB_TM_IDX_DISPLAY_ORDER_TASK_FIELDS_FORM);
			$this->set_data('action', BAB_TM_ACTION_SAVE_SELECTED_TASK_FIELD);
			$this->set_data('tg', 'usrTskMgr');
			$this->set_data('iIdProjectSpace', $iIdProjectSpace);
			$this->set_data('iIdProject', $iIdProject);
			$this->set_data('sSelected', '');//Utilisé seulement lors du déplacement
			$this->set_data('sSelectedField', '');//Utilisé seulement lors du déplacement
			
			$this->set_caption('sSelectableField', bab_translate("Selectable fields"));
			$this->set_caption('sSelectedField', bab_translate("Selected fields"));
			$this->set_caption('sGrabField', bab_translate("Add"));
			$this->set_caption('sDropField', bab_translate("Drop"));
			$this->set_caption('sUp', bab_translate("Up"));
			$this->set_caption('sDown', bab_translate("Down"));
			$this->set_caption('sSave', bab_translate("Save"));
			$this->set_caption('sTaskField', bab_translate("Task fields"));
			$this->set_caption('sAdditionalField', bab_translate("Specific fields"));
			
			$this->initSessionContex();
			$this->initSession($iIdProjectSpace, $iIdProject);
			
			$this->processAction();
			
			if(!function_exists('babTskMgrCompareField'))
			{		
				function babTskMgrCompareField($f1, $f2)
				{
					return strcasecmp($f1['sLegend'], $f2['sLegend']);
				}
			}
			uasort($_SESSION['babTskMgrSelectableField'], 'babTskMgrCompareField');
			
//			bab_debug($_SESSION['babTskMgrSelectableField']);
//			bab_debug($_SESSION['babTskMgrSelectedField']);
		}
	
		function initSessionContex()
		{
			$sCleanSessVar = (null == bab_rp('sCleanSessVar', null)) ? 'Y' : 'N';
			$aSessionKey = array('babTskMgrSelectableField', 'babTskMgrSelectedField');
	
			foreach($aSessionKey as $sArrayName)
			{
				if(!array_key_exists($sArrayName, $_SESSION) || 'Y' == $sCleanSessVar)
				{
					$_SESSION[$sArrayName] = array();
				}
			}
			
			$_SESSION['babTskMgrInitSessionField'] = $sCleanSessVar;
			$this->set_data('sCleanSessVar', 'N');
		}
		
		function initSession($iIdProjectSpace, $iIdProject)
		{
			if('Y' == $_SESSION['babTskMgrInitSessionField'])
			{
				$aTaskField			= bab_tskmgr_getSelectedFieldId($iIdProject, BAB_TM_TASK_FIELD);
				$aAdditionalField	= bab_tskmgr_getSelectedFieldId($iIdProject, BAB_TM_ADDITIONAL_FIELD);
				$aSelectableField	= bab_tskmgr_getSelectableTaskFields($iIdProjectSpace, $iIdProject, $aTaskField, $aAdditionalField);
				$aSelectedField		= bab_tskmgr_getSelectedField($iIdProject);
				
				$aToProcess = array('babTskMgrSelectableField' => 'aSelectableField', 
					'babTskMgrSelectedField' => 'aSelectedField');
				
				foreach($aToProcess as $sSessionKeyName => $sArrayName) 
				{
					foreach($$sArrayName as $aItem) 
					{
						$sClassName =  (BAB_TM_TASK_FIELD === (int) $aItem['iType']) ? 'taskField' : 'additionalField';
						$sValue = $aItem['iId'] . '_' . $aItem['iType'];
						
						$aLegend = explode(',', $aItem['sLegend']);
						foreach($aLegend as $iKey => $sLegend)
						{
							$aLegend[$iKey] = bab_translate($sLegend);
						}
						
						$sLegend = implode('/', $aLegend);
						
						$_SESSION[$sSessionKeyName][$sValue] = array('sClassName' => $sClassName,
							'sValue' => $sValue, 'sLegend' => $sLegend);
					}
				}
				
				$_SESSION['babTskMgrInitSessionField'] = 'N';
				
//				bab_debug($_SESSION['babTskMgrSelectableField']);
//				bab_debug($_SESSION['babTskMgrSelectedField']);
			}
		}
		
		function getSelectedFieldSelectedIndex(&$sSelectedIndex, &$iSelectedIndex)
		{
			$sSelectedIndex = '';
			$iSelectedIndex = -1;
			
			if(isset($_POST['aSelectedField']) && 0 < count($_POST['aSelectedField'])) 			
			{
				foreach($_POST['aSelectedField'] as $sValue) 
				{
					$iIndex = 0;
					foreach($_SESSION['babTskMgrSelectedField'] as $iKey => $aItem)
					{
						if($sValue == $aItem['sValue'])
						{
							$sSelectedIndex = $sValue;
							$iSelectedIndex = $iIndex;
							
							reset($_SESSION['babTskMgrSelectedField']);
							return;
						}
						$iIndex++;
					}
				}
				reset($_SESSION['babTskMgrSelectedField']);
			}
		}
		
		
		function processAction()
		{
			if(!isset($_POST['action']) || !is_array($_POST['action']))
			{
				return;
			}
			
			$sAction = isset($_POST['action']) ? key($_POST['action']) : false;
		
			switch($sAction) 
			{
				case 'sGrabField':
					if(isset($_POST['aSelectableField']) && 0 < count($_POST['aSelectableField'])) 
					{
						foreach($_POST['aSelectableField'] as $sValue) 
						{
							if(array_key_exists($sValue, $_SESSION['babTskMgrSelectableField']))
							{
								$_SESSION['babTskMgrSelectedField'][$sValue] = $_SESSION['babTskMgrSelectableField'][$sValue];
								unset($_SESSION['babTskMgrSelectableField'][$sValue]);
							}
						}
					}
					break;
					
				case 'sDropField':
					if(isset($_POST['aSelectedField']) && 0 < count($_POST['aSelectedField'])) 
					{
						foreach($_POST['aSelectedField'] as $sValue) 
						{
							if(array_key_exists($sValue, $_SESSION['babTskMgrSelectedField']))
							{
								$_SESSION['babTskMgrSelectableField'][$sValue] = $_SESSION['babTskMgrSelectedField'][$sValue];
								unset($_SESSION['babTskMgrSelectedField'][$sValue]);
							}
						}
					}
					break;
			
				case 'sUpField':
				case 'sDownField':
					$sSelectedIndex = '';
					$iSelectedIndex = -1;
					
					$this->getSelectedFieldSelectedIndex($sSelectedIndex, $iSelectedIndex);
					if(-1 != $iSelectedIndex)
					{
						$this->set_data('sSelectedField', $sSelectedIndex);
						
						if($iSelectedIndex == 0 && 'sUpField' == $sAction)
						{
							return;
						}
						else if($iSelectedIndex == (count($_SESSION['babTskMgrSelectedField']) - 1) && 'sDownField' == $sAction)
						{
							return;
						}
						
						$iNewIndex = ('sUpField' == $sAction) ? $iSelectedIndex - 1 : $iSelectedIndex + 1;
						
						$aItemToMove = $_SESSION['babTskMgrSelectedField'][$sSelectedIndex];
						unset($_SESSION['babTskMgrSelectedField'][$sSelectedIndex]);
						
						$aSelectedField = array();
						
						$iIndex = 0;
						foreach($_SESSION['babTskMgrSelectedField'] as $sKey => $aItem)
						{
							if($iNewIndex == $iIndex)
							{
								$aSelectedField[$sSelectedIndex] = $aItemToMove;
							}
							$iIndex++;
							
							$aSelectedField[$sKey] = $aItem;
						}
						
						//Pour le déplacement vers le bas
						//Vu que l'on supprime(unset) l'élément à déplacer
						if(!array_key_exists($sSelectedIndex, $_SESSION))
						{
							$aSelectedField[$sSelectedIndex] = $aItemToMove;
						}
						
						$_SESSION['babTskMgrSelectedField'] = $aSelectedField;
					}
					break;
			}
			reset($_SESSION['babTskMgrSelectedField']);
		}
		
		function getNextField($sSessionKeyName)
		{
			$this->set_data('sSelected', '');//Utilisé seulement lors du déplacement
			$this->get_data('sSelectedField', $sSelectedField);//Utilisé seulement lors du déplacement

			if(array_key_exists($sSessionKeyName, $_SESSION))
			{
				$aItem = each($_SESSION[$sSessionKeyName]);
				if(false !== $aItem)
				{
					$this->set_data('sClassName', $aItem['value']['sClassName']);
					$this->set_data('sValue', $aItem['value']['sValue']);
					$this->set_data('sLegend', $aItem['value']['sLegend']);
					if($sSelectedField == $aItem['value']['sValue'])
					{
						$this->set_data('sSelected', 'selected="selected"');
					}
					
					return true;
				}
			}
			return false;
		}
		
		function getNextSelectableField()
		{
			return $this->getNextField('babTskMgrSelectableField');
		}
		
		function getNextSelectedField()
		{
			return $this->getNextField('babTskMgrSelectedField');
		}
	}
	
	$oOrderTaskFields = new BAB_OrderTaskFields($iIdProjectSpace, $iIdProject);
	$babBody->addStyleSheet('taskManager.css');
	$babBody->babecho(bab_printTemplate($oOrderTaskFields, 'tmUser.html', 'orderTaskFields'));
}


//POST


function addModifyProject()
{
	global $babBody, $babDB;

	$oTmCtx =& getTskMgrContext();
	$iIdProjectSpace = (int) $oTmCtx->getIdProjectSpace();
	$iIdProject = (int) $oTmCtx->getIdProject();
	
	if(bab_isAccessValid(BAB_TSKMGR_PROJECT_CREATOR_GROUPS_TBL, $iIdProjectSpace))
	{
		$sName = trim(bab_rp('sName', ''));

		if(0 < strlen($sName))
		{
			$isValid = isNameUsedInProjectSpace(BAB_TSKMGR_PROJECTS_TBL, $iIdProjectSpace, $iIdProject, $sName);
			
			$sDescription = trim(bab_rp('sDescription', ''));
			
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
				$GLOBALS['babBody']->msgerror = bab_toHtml(bab_translate("There is an another project with the name") . '\'' . $sName . '\'');
				$_POST['idx'] = BAB_TM_IDX_DISPLAY_PROJECT_FORM;
				return false;
			}
		}
		else
		{
			$GLOBALS['babBody']->msgerror = bab_toHtml(bab_translate("The field name must not be blank"));
			$_POST['idx'] = BAB_TM_IDX_DISPLAY_PROJECT_FORM;
			//unset($_POST['iIdProject']);
			return false;
		}
	}
	else
	{
		$GLOBALS['babBody']->msgerror = bab_toHtml(bab_translate("You do not have the right to create a project"));
	}
	
}

function deleteProject()
{
	global $babBody;

	$oTmCtx =& getTskMgrContext();
	$iIdProjectSpace = (int) $oTmCtx->getIdProjectSpace();
	$iIdProject = (int) $oTmCtx->getIdProject();
	
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

			if(bab_deleteProject($iIdProjectSpace, $iIdProject))
			{
				bab_updateRefCount(BAB_TSKMGR_PROJECTS_SPACES_TBL, $iIdProjectSpace, '- 1');
				bab_deleteAllNoticeEvent($iIdProjectSpace, $iIdProject);
			}
		}
	}
	else
	{
		$GLOBALS['babBody']->msgerror = bab_toHtml(bab_translate("You do not have the right to delete a project"));
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
	$iIdProject = (int) $oTmCtx->getIdProject();
	$bIsManager = bab_isAccessValid(BAB_TSKMGR_PROJECTS_MANAGERS_GROUPS_TBL, $iIdProject);
	
	$iTaskUpdateByMgr = (int) bab_rp('iTaskUpdateByMgr', BAB_TM_YES);
	$iIdConfiguration = (int) bab_rp('iIdConfiguration', 0);
	$iEndTaskReminder = (int) bab_rp('iEndTaskReminder', 5);
	$iTaskNumerotation = (int) bab_rp('iTaskNumerotation', BAB_TM_SEQUENTIAL);
	$iEmailNotice = (int) bab_rp('iEmailNotice', BAB_TM_YES);
	$sFaqUrl = bab_rp('sFaqUrl', '');

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
		$sCommentary = trim(bab_rp('sCommentary', ''));
		
		if(strlen(trim($sCommentary)) > 0)
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
	$iUserProfil = (int) $oTmCtx->getUserProfil();

	$iIdProject = (int) $oTmCtx->getIdProject();
	$iIdTask = (int) $oTmCtx->getIdTask();
	$iIdCommentary = (int) bab_rp('iIdCommentary', 0);

	if(0 != $oTmCtx->m_iIdTask && (BAB_TM_PROJECT_MANAGER == $iUserProfil || BAB_TM_PERSONNAL_TASK_OWNER == $iUserProfil))
	{
		$sCommentary = trim(bab_rp('sCommentary', ''));
		
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
	$iUserProfil = (int) $oTmCtx->getUserProfil();
	
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
	$iUserProfil = (int) $oTmCtx->getUserProfil();
	$bIsOk = false;
	
	$oTaskValidator = null;
	
	$iClass = (int) bab_rp('iClassType', BAB_TM_TASK);
	
	if(0 == $oTmCtx->m_iIdTask && (BAB_TM_PROJECT_MANAGER == $iUserProfil || BAB_TM_PERSONNAL_TASK_OWNER == $iUserProfil))
	{
		$oTaskValidator =& new BAB_TM_MgrTaskCreatorValidator();
	}
	else if(0 != $oTmCtx->m_iIdTask && (BAB_TM_PROJECT_MANAGER == $iUserProfil || BAB_TM_PERSONNAL_TASK_OWNER == $iUserProfil))
	{
		$oTaskValidator =& new BAB_TM_TaskUpdaterValidator();
	}
	else if(0 != $oTmCtx->m_iIdTask && BAB_TM_TASK_RESPONSIBLE == $iUserProfil)
	{
		$oTaskValidator =& new BAB_TM_TaskUpdateByTaskResponsible();
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
			bab_debug('addModifyTask sTask ==> ' . $oTaskValidator->m_sTaskNumber . ' is valid');
		}
		else
		{
			bab_debug('addModifyTask sTask ==> ' . $oTaskValidator->m_sTaskNumber . ' invalid');
		}
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
				/*
				foreach($aDependingTasks as $iIdT)
				{
					bab_getTask($iIdT, $aTask);
					$aTask['isLinked'] = BAB_TM_NO;
					bab_updateTask($iIdTask, $aTask);
					bab_deleteTaskLinks($iIdT);
				}
				//*/
				
				global $babBody;
				$babBody->addError(bab_translate("You can not delete tis task because another task are linked on it"));
				return;
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
		
		bab_deleteTask($iIdProjectSpace, $iIdProject, $iIdTask);
	}
}

/*
function createSpecificFieldInstance()
{
	$oTmCtx =& getTskMgrContext();
	$iIdProjectSpace = (int) $oTmCtx->getIdProjectSpace();
//	$iIdProject = (int) $oTmCtx->getIdProject();
//	$iIdTask = (int) $oTmCtx->getIdTask();
	$iUserProfil = (int) $oTmCtx->getUserProfil();

	$iIdProject = (int) bab_rp('iIdProject', 0);
	$iIdTask = (int) bab_rp('iIdTask', 0);
	$iIdSpecificField = (int) bab_rp('oSpfField', 0);

	if(0 !== $iIdSpecificField)
	{
		if((bab_isAccessValid(BAB_TSKMGR_PROJECTS_MANAGERS_GROUPS_TBL, $iIdProject) || BAB_TM_PERSONNAL_TASK_OWNER == $iUserProfil)&& 0 < $iIdTask)
		{
			bab_createSpecificFieldInstance($iIdTask, $iIdSpecificField);
		}
		else 
		{
			bab_debug('createSpecificFieldInstance: acces denied');
		}
	}
}
//*/

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
	$oTmCtx =& getTskMgrContext();
	$iIdProjectSpace = (int) $oTmCtx->getIdProjectSpace();
	$iIdProject = (int) $oTmCtx->getIdProject();

	if(bab_isAccessValid(BAB_TSKMGR_PROJECTS_MANAGERS_GROUPS_TBL, $iIdProject))
	{
		saveProjectConfiguration();
	}
	
	if(true === bab_isAccessValid(BAB_TSKMGR_PROJECT_CREATOR_GROUPS_TBL, $iIdProjectSpace))
	{
		addModifyProject();
	}
}


function processExport()
{
	$sKey = 'tskMgrProjectFilter';
	
	$oFilterSessionContext = new BAB_TM_SessionContext($sKey);
	$iIdProject	= (int) $oFilterSessionContext->get('iIdProject', -1);
	
	if($iIdProject > 0 && (bab_isAccessValid(BAB_TSKMGR_PROJECTS_MANAGERS_GROUPS_TBL, $iIdProject) || 
		bab_isAccessValid(BAB_TSKMGR_PROJECTS_SUPERVISORS_GROUPS_TBL, $iIdProject)) )
	{
		$iTaskClass			= (int) $oFilterSessionContext->get('iTaskClass', -1);	
		$iTaskCompletion	= (int) $oFilterSessionContext->get('iTaskCompletion', -1);	
		$iIdOwner			= (int) $oFilterSessionContext->get('iIdOwner', 0);
		$sStartDate			= (string) $oFilterSessionContext->get('sStartDate', '');
		$iStartHour			= (int) $oFilterSessionContext->get('iStartHour', 0);
		$iStartMinut		= (int) $oFilterSessionContext->get('iStartMinut', 0);
		$sEndDate			= (string) $oFilterSessionContext->get('sEndDate', '');
		$iEndHour			= (int) $oFilterSessionContext->get('iEndHour', 0);
		$iEndMinut			= (int) $oFilterSessionContext->get('iEndMinut', 0);
		$sPlannedStartDate	= (string) $oFilterSessionContext->get('sPlannedStartDate', '');
		$iPlannedStartHour	= (int) $oFilterSessionContext->get('iPlannedStartHour', 0);
		$iPlannedStartMinut	= (int) $oFilterSessionContext->get('iPlannedStartMinut', 0);
		$sPlannedEndDate	= (string) $oFilterSessionContext->get('sPlannedEndDate', '');
		$iPlannedEndHour	= (int) $oFilterSessionContext->get('iPlannedEndHour', 0);
		$iPlannedEndMinut	= (int) $oFilterSessionContext->get('iPlannedEndMinut', 0);
		
		$aFilters = array();
		
		$aFilters['iIdProject'] = $iIdProject;
				
		if(-1 != $iTaskClass)
		{
			$aFilters['iTaskClass'] = $iTaskClass;
		}
	
		if(-1 !== $iTaskCompletion)
		{
			$aFilters['iCompletion'] = $iTaskCompletion;
		}
		
		global $babInstallPath;
		require_once($babInstallPath . 'utilit/dateTime.php');
	
		if(strlen(trim($sStartDate)) > 0)
		{
			$oDate = BAB_DateTime::fromDateStr(str_replace('-', '/', $sStartDate));
			if(!is_null($oDate))
			{
				$oDate->init($oDate->_iYear, $oDate->_iMonth, $oDate->_iDay, 
					$iStartHour, $iStartMinut);
				$aFilters['sStartDate'] = $oDate->getIsoDateTime();
			}
		}
	
		if(strlen(trim($sEndDate)) > 0)
		{
			$oDate = BAB_DateTime::fromDateStr(str_replace('-', '/', $sEndDate));
			if(!is_null($oDate))
			{
				$oDate->init($oDate->_iYear, $oDate->_iMonth, $oDate->_iDay, 
					$iEndHour, $iEndMinut);
				$aFilters['sEndDate'] = $oDate->getIsoDateTime();
			}
		}
	
		if(strlen(trim($sPlannedStartDate)) > 0)
		{
			$oDate = BAB_DateTime::fromDateStr(str_replace('-', '/', $sPlannedStartDate));
			if(!is_null($oDate))
			{
				$oDate->init($oDate->_iYear, $oDate->_iMonth, $oDate->_iDay, 
					$iPlannedStartHour, $iPlannedStartMinut);
				$aFilters['sPlannedStartDate'] = $oDate->getIsoDateTime();
			}
		}
	
		if(strlen(trim($sPlannedEndDate)) > 0)
		{
			$oDate = BAB_DateTime::fromDateStr(str_replace('-', '/', $sPlannedEndDate));
			if(!is_null($oDate))
			{
				$oDate->init($oDate->_iYear, $oDate->_iMonth, $oDate->_iDay, 
					$iPlannedEndHour, $iPlannedEndMinut);
				$aFilters['sPlannedEndDate'] = $oDate->getIsoDateTime();
			}
		}
		
		if(0 !== $iIdOwner)
		{
			$aFilters['iIdOwner'] = $iIdOwner;
		}
		
		$aFilters['bIsManager'] = true;
		
		$aField			= array();
		$aLeftJoin		= array();
		$aWhere			= array();
		
		$aLeftJoin[] = 'LEFT JOIN ' . 
			BAB_TSKMGR_SELECTED_TASK_FIELDS_TBL . ' stf ON stf.iIdProject = t0.idProject ';
			
		$aWhere[] = 'AND stf.iIdProject = \'' . $iIdProject . '\'';
		
		$sTableAlias = 't5';
		
		$aFieldInfo = array();
		
		$aFieldInfo		= array();
		$aSelectedField	= bab_tskmgr_getSelectedField($iIdProject);
		foreach($aSelectedField as $aSelectedFieldItem)
		{
			$aCaption				= explode(',', $aSelectedFieldItem['sLegend']);
			$aDataSourceFieldName	= explode(',', $aSelectedFieldItem['sName']);
			
			foreach($aDataSourceFieldName as $iKey => $sFieldName)
			{
				$sAlias = $sFieldName;
				
				if(BAB_TM_ADDITIONAL_FIELD == $aSelectedFieldItem['iType'])
				{
					$sAlias		= $sTableAlias . '__' . $sFieldName;
					$aField[]	= $sTableAlias . '.sField' . $aSelectedFieldItem['iId'] . ' AS ' . $sAlias;
				}
				
				$sLegend = '???';
				if(array_key_exists($iKey, $aCaption))
				{
					$sLegend = bab_translate($aCaption[$iKey]);
				}
				$aFieldInfo[$sAlias] = $sLegend;
			}
		}
		
		$sTableName = bab_tskmgr_getAdditionalFieldTableName(bab_rp('iIdProjectSpace', 1), $iIdProject);
		
		require_once $GLOBALS['babInstallPath'] . 'utilit/upgradeincl.php';
		
		if(bab_isTable($sTableName))
		{
			$aLeftJoin[]	= 'LEFT JOIN ' . 
				$sTableName . ' ' . $sTableAlias . ' ON ' . $sTableAlias . '.iIdTask = t0.id ';
		}
		else 
		{
			$aField		= array();
			$aLeftJoin	= array();
			$aWhere		= array();
		}
		
		$aOrder = array();
//		$sQuery = bab_selectTaskQuery($aFilters, $aOrder);
		$sQuery = bab_selectTaskQueryEx($aFilters, $aField, $aLeftJoin, $aWhere, $aOrder);
//		bab_debug($sQuery);
	
		global $babDB;
		$oResult = $babDB->db_query($sQuery);
		$iNumRows = $babDB->db_num_rows($oResult);
		$iIndex = 0;
		
		if($iNumRows > 0)
		{		
			$sSeparator	= ',';
			$sCrlf		= "\r\n";
			$sOutput	= '';
			 
			foreach($aFieldInfo as $sAlias => $sLegend)
			{
				if('sDescription' == $sAlias)
				{
					//SZ vue avec JLB le 12/08/2008 pour l'instant
					//on ne traite pas la description car l'éditeur HTML
					//remonte des caractères qui génére des saut de lignes
					continue;
				}
				
				$sOutput .= '"' . $sLegend . '"' . $sSeparator;
			}
			
			$sLastChar = substr($sOutput, -1);
			if(false !== $sLastChar && ',' == $sLastChar)
			{
				$sOutput = substr($sOutput, 0, -1);
			}
			
			$sOutput .= $sCrlf;

			$aDateField = array('startDate', 'endDate', 'plannedStartDate', 
				'plannedEndDate', 'sCreatedDate', 'sModifiedDate');
			
			$aIdUserField = array('idOwner', 'iIdUserCreated', 'iIdUserModified');
			
			require_once $GLOBALS['babInstallPath'] . 'utilit/editorincl.php';
			$oEditor = new bab_contentEditor('bab_taskManagerDescription');
			
			
			while($iIndex < $iNumRows && false != ($aDatas = $babDB->db_fetch_assoc($oResult)))
			{
				$iIndex++;
				
				foreach($aFieldInfo as $sAlias => $sLegend)
				{
					$sData = $aDatas[$sAlias];
					
					if(in_array($sAlias, $aDateField))
					{
						$sData = '';
						if('0000-00-00 00:00:00' != $aDatas[$sAlias])
						{
							$sData = bab_toHtml(bab_shortDate(bab_mktime($aDatas[$sAlias])));
						}
					}
					else if(in_array($sAlias, $aIdUserField))
					{
						$sData = bab_toHtml(bab_getUserName($aDatas[$sAlias]));
					}
					else if('iPlannedTime' == $sAlias)
					{
						$sData = $aDatas[$sAlias];
						if(BAB_TM_DAY == $aDatas['iPlannedTimeDurationUnit'])
						{
							$sData = ((float) $aDatas[$sAlias] * 24);
						}
					}
					else if('iTime' == $sAlias)
					{
						$sData = $aDatas[$sAlias];
						if(BAB_TM_DAY == $aDatas['iTimeDurationUnit'])
						{
							$sData = ((float) $aDatas[$sAlias] * 24);
						}
					}
					else if('sDescription' == $sAlias)
					{
//						$oEditor->setContent($aDatas[$sAlias]);
//						$sData = tskmgr_htmlToText(html_entity_decode($oEditor->getHtml(), ENT_QUOTES));
						
						//SZ vue avec JLB le 12/08/2008 pour l'instant
						//on ne traite pas la description car l'éditeur HTML
						//remonte des caractères qui génére des saut de lignes
						continue;
					}

					$sOutput .= '"' . $sData . '"' . $sSeparator;
				}

				$sLastChar = substr($sOutput, -1);
				if(false !== $sLastChar && ',' == $sLastChar)
				{
					$sOutput = substr($sOutput, 0, -1);
				}
				
				$sOutput .= $sCrlf;
			}
			
			$sFileName = 'listeTâches.csv';
		
			header("Content-Disposition: attachment; filename=\"" . $sFileName . "\""."\n");
			header("Content-Type: csv/plain"."\n");
			header("Content-Length: ". strlen($sOutput) ."\n");
			header("Content-transfert-encoding: binary"."\n");
			print $sOutput;
			die;
		}
	}
}

function tskmgr_htmlToText($sHtml)
{
	$sHtml = eregi_replace('<BR[[:space:]]*/?[[:space:]]*>', "\n ", $sHtml);
	$sHtml = eregi_replace('<P>|</P>|<P />|<P/>', "\n ", $sHtml);
	$sHtml = strip_tags($sHtml);
	return $sHtml;
}

function saveSelectedTaskField()
{
	global $babBody;
	
	$iIdProject			= (int) bab_rp('iIdProject', 0);
	$iIdProjectSpace	= (int) bab_rp('iIdProjectSpace', 0);
	
	if(!bab_isAccessValid(BAB_TSKMGR_PROJECTS_MANAGERS_GROUPS_TBL, $iIdProject))
	{
		$GLOBALS['babBody']->msgerror = bab_toHtml(bab_translate("You are not a projects manager"));
		return false;
	}
	
	if(array_key_exists('babTskMgrSelectedField', $_SESSION))
	{
		$aField = array();
		
		$iIdFieldIdx	= 0;
		$iTypeIdx		= 1;
		$iIndex			= 0;
		
		foreach($_SESSION['babTskMgrSelectedField'] as $aItem) 
		{
			$aFieldInfo	= explode('_', $aItem['sValue']);
			
			if(false !== $aFieldInfo && 2 == count($aFieldInfo))
			{
				$aField[] = array('iIdField' => $aFieldInfo[$iIdFieldIdx],
					'iType' => $aFieldInfo[$iTypeIdx], 'iIdProject' => $iIdProject,
					'iPosition' => $iIndex++);

			}			
		}
		
		reset($_SESSION['babTskMgrSelectedField']);
		bab_tskmgr_deleteSelectedTaskFields($iIdProject);
		bab_tskmgr_saveSelectedTaskField($aField);
	}
	
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=usrTskMgr&idx=displayProjectsSpacesList");
	exit;
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

/*		
	case BAB_TM_ACTION_CREATE_SPECIFIC_FIELD_INSTANCE:
		createSpecificFieldInstance();
		break;
//*/
		
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
		
	case BAB_TM_ACTION_PROCESS_EXPORT:
		processExport();
		break;
		
	case BAB_TM_ACTION_SAVE_SELECTED_TASK_FIELD:
		saveSelectedTaskField();
		break;
}


$idx = isset($_POST['idx']) ? $_POST['idx'] : (isset($_GET['idx']) ? $_GET['idx'] : BAB_TM_IDX_DISPLAY_MY_TASK_LIST);

//bab_debug('idx ==> ' . $idx);

switch($idx)
{
	case BAB_TM_IDX_DISPLAY_WORKING_HOURS_FORM:
		require_once($GLOBALS['babInstallPath'] . 'tmWorkingHoursFunc.php');
		displayWorkingHoursForm();
		break;

	case BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_LIST:
		displayProjectsSpacesList();
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
		
	case BAB_TM_IDX_DISPLAY_PROJECT_TASK_LIST:
	case BAB_TM_IDX_DISPLAY_MY_TASK_LIST:
		displayTaskList($idx);
		break;
		
	case BAB_TM_IDX_DISPLAY_TASK_FORM:
		displayTaskForm();
		break;
		
	case BAB_TM_IDX_DISPLAY_DELETE_TASK_FORM:
		displayDeleteTaskForm();
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
		
	case BAB_TM_IDX_DISPLAY_PROJECT_PROPERTIES_FORM:
		displayProjectPropertiesForm();
		break;
		
	case BAB_TM_IDX_DISPLAY_ORDER_TASK_FIELDS_FORM:
		displayOrderTaskFieldsForm();
		break;
}
$babBody->setCurrentItemMenu($idx);
?>