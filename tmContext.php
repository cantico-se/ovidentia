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
	require_once($babInstallPath . 'utilit/tableWrapperClass.php');

class BAB_TM_Context
{
	var $m_oTblWr;
	var $m_aConfiguration;

	var $m_iIdProjectSpace;
	var $m_iIdProject;
	var $m_iIdDelegation;
	
	var $m_bIsProjectVisualizer;
	var $m_aVisualizedIdProjectSpace;
	var $m_aVisualizedIdProject;

	var $m_bIsProjectManager;
	var $m_aManagedIdProject;

	var $m_bIsProjectSupervisor;
	var $m_aSupervisedIdProject;

	var $m_bIsManageTask;
	var $m_aManagedTaskId;

	var $m_bIsPersonnalTaskOwner;
	var $m_aPersonnalOwnedIdTask;
	
	function BAB_TM_Context()
	{
		global $babBody;

		$this->m_oTblWr = new BAB_TableWrapper('');
		$this->m_iIdProjectSpace = (int) tskmgr_getVariable('iIdProjectSpace', 0);
		$this->m_iIdProject = (int) tskmgr_getVariable('iIdProject', 0);
		$this->m_iIdDelegation = $babBody->currentAdmGroup;
		
		//bab_debug('BAB_TM_Context::m_iIdDelegation ==> ' . $this->m_iIdDelegation);
		
		$this->m_aConfiguration = null;
		$this->m_bIsProjectVisualizer = null;
		$this->m_aVisualizedIdProjectSpace = array();
		$this->m_aVisualizedIdProject = array();

		$this->m_bIsProjectManager = null;
		$this->m_aManagedIdProject = array();

		$this->m_bIsProjectSupervisor = null;
		$this->m_aSupervisedIdProject = array();

		$this->m_bIsManageTask = null;
		$this->m_aManagedTaskId = array();

		$this->m_bIsPersonnalTaskOwner = null;
		$this->m_aPersonnalOwnedIdTask = array();
	}
	
	
	// Public
	function getIdProjectSpace()
	{
		return $this->m_iIdProjectSpace;
	}
	
	function getIdProject()
	{
		return $this->m_iIdProject;
	}
	
	function getIdDelegation()
	{
		return $this->m_iIdDelegation;
	}
	
	function &getConfiguration()
	{
		if(is_null($this->m_aConfiguration))
		{
			$this->loadConfiguration();
		}
		return $this->m_aConfiguration;
	}
	
	function isUserProjectVisualizer()
	{
		if(is_null($this->m_bIsProjectVisualizer))
		{
			$this->queryVisualisedObject();
		}
		return $this->m_bIsProjectVisualizer;
	}

	function isUserProjectManager()
	{
		if(is_null($this->m_bIsProjectManager))
		{
			$this->queryManagedProject();
		}
		return $this->m_bIsProjectManager;
	}
	
	function isUserSuperviseProject()
	{
		if(is_null($this->m_bIsProjectSupervisor))
		{
			$this->querySupervisedProject();
		}
		return $this->m_bIsProjectSupervisor;
	}
	
	function isUserManageTask()
	{
		if(is_null($this->m_bIsManageTask))
		{
			$this->queryManagedTask();
		}
		return $this->m_bIsManageTask;
	}

	function isUserOwnPersonnalTask()
	{
		if(is_null($this->m_bIsPersonnalTaskOwner))
		{
			$this->queryPersonnalOwnedTask();
		}
		return $this->m_bIsPersonnalTaskOwner;
	}

	function &getTableWrapper()
	{
		return $this->m_oTblWr;
	}
	
	function getVisualisedIdProjectSpace()
	{
		if(is_null($this->m_bIsProjectVisualizer))
		{
			$this->queryVisualisedObject();
		}
		return $this->m_aVisualizedIdProjectSpace;
	}
	
	function getManagedIdProject()
	{
		if(is_null($this->m_bIsProjectManager))
		{
			$this->queryManagedProject();
		}
		return $this->m_aManagedIdProject;
	}

	function getManagedTaskId()
	{
		if(is_null($this->m_bIsManageTask))
		{
			$this->queryManagedTask();
		}
		return $this->m_aManagedTaskId;
	}
	
	function getSupervisedIdProject()
	{
		if(is_null($this->m_bIsProjectSupervisor))
		{
			$this->querySupervisedProject();
		}
		return $this->m_aSupervisedIdProject;
	}

	function getPersonnalOwnedIdTask()
	{
		if(is_null($this->m_bIsPersonnalTaskOwner))
		{
			$this->queryPersonnalOwnedTask();
		}
		return $this->m_aPersonnalOwnedIdTask;
	}
	
	
	// Private
	function loadConfiguration()
	{
		if(0 != $this->m_iIdProjectSpace)
		{
			$attributs = array(
				'idProjectSpace' => $this->m_iIdProjectSpace,
				'idProject' => $this->m_iIdProject,
				'id' => -1,
				'tskUpdateByMgr' => -1,
				'endTaskReminder' => -1,
				'tasksNumerotation' => -1,
				'emailNotice' => -1,
				'faqUrl' => '');

			$sTblName = BAB_TSKMGR_PROJECTS_CONFIGURATION_TBL;
			$whereClauseLength = 2;
				
			if(0 == $this->m_iIdProject)
			{
				$sTblName = BAB_TSKMGR_DEFAULT_PROJECTS_CONFIGURATION_TBL;
				$whereClauseLength = 1;
				unset($attributs['idProject']);
			}
				
			$this->m_oTblWr->setTableName($sTblName);
			
			$this->m_aConfiguration = $this->m_oTblWr->load($attributs, 0, count($attributs), 0, $whereClauseLength);
			if(false != $this->m_aConfiguration)
			{
				return true;
			}
		}
		$this->m_aConfiguration = null;
		return false;
	}

	function queryVisualisedObject()
	{
		require_once($GLOBALS['babInstallPath'] . 'admin/acl.php');
		
		$this->m_aVisualizedIdProjectSpace = bab_getUserIdObjects(BAB_TSKMGR_DEFAULT_PROJECTS_VISUALIZERS_GROUPS_TBL);
		
		$this->m_aVisualizedIdProject = bab_getUserIdObjects(BAB_TSKMGR_PROJECTS_VISUALIZERS_GROUPS_TBL);
		if(count($this->m_aVisualizedIdProject) > 0)
		{
			$query = 
				'SELECT ' . 
					'idProjectSpace ' .
				'FROM ' . 
					BAB_TSKMGR_PROJECTS_TBL . ' ' .
				'WHERE ' . 
					'id = IN(\'' . implode('\',\'', array_keys($this->m_aVisualizedIdProject)) . '\')';
				
			$db	= & $GLOBALS['babDB'];
			
			$result = $db->db_query($query);
			if(false != $result)
			{
				$iRows = $db->db_num_rows($result);
				$iIdx = 0;
				while($iIdx < $iRows && false != ($datas = $db->db_fetch_array($result)))
				{
					$iIdx++;
					$this->m_aVisualizedIdProjectSpace[$datas['idProjectSpace']] = 1;
				}
			}
		}
		
		if(count($this->m_aVisualizedIdProjectSpace) > 0)
		{
			$this->m_bIsProjectVisualizer = true;
		}
		else 
		{
			$this->m_bIsProjectVisualizer = false;
		}
	}

	function queryManagedProject()
	{
		require_once($GLOBALS['babInstallPath'] . 'admin/acl.php');
		
		$this->m_aManagedIdProject = bab_getUserIdObjects(BAB_TSKMGR_PROJECTS_MANAGERS_GROUPS_TBL);
		if(count($this->m_aManagedIdProject) > 0)
		{
			$this->m_bIsProjectManager = true;
		}
		else 
		{
			$this->m_bIsProjectManager = false;
		}
	}
	
	function querySupervisedProject()
	{
		require_once($GLOBALS['babInstallPath'] . 'admin/acl.php');
		
		$this->m_aSupervisedIdProject = bab_getUserIdObjects(BAB_TSKMGR_PROJECTS_SUPERVISORS_GROUPS_TBL);
		if(count($this->m_aSupervisedIdProject) > 0)
		{
			$this->m_bIsProjectSupervisor = true;
		}
		else 
		{
			$this->m_bIsProjectSupervisor = false;
		}
	}
	
	function queryManagedTask()
	{
		require_once($GLOBALS['babInstallPath'] . 'admin/acl.php');
		
		$this->m_aManagedTaskId = bab_getUserIdObjects(BAB_TSKMGR_TASK_RESPONSIBLE_GROUPS_TBL);
		if(count($this->m_aManagedTaskId) > 0)
		{
			$this->m_bIsManageTask = true;
		}
		else 
		{
			$this->m_bIsManageTask = false;
		}
	}
	
	function queryPersonnalOwnedTask()
	{
		require_once($GLOBALS['babInstallPath'] . 'admin/acl.php');
		
		$query = 
			'SELECT ' . 
				'id ' .
			'FROM ' . 
				BAB_TSKMGR_TASKS_TBL . ' ' .
			'WHERE ' . 
				'idProject = \'0\' AND ' .
				'idResponsible = \'' . $GLOBALS['BAB_SESS_USERID'] . '\'';
			
		$db	= & $GLOBALS['babDB'];
		
		$result = $db->db_query($query);
		if(false != $result)
		{
			$iRows = $db->db_num_rows($result);
			$iIdx = 0;
			while($iIdx < $iRows && false != ($datas = $db->db_fetch_array($result)))
			{
				$iIdx++;
				$this->m_aPersonnalOwnedIdTask[$datas['id']] = 1;
			}
		}

		if(count($this->m_aPersonnalOwnedIdTask) > 0)
		{
			$this->m_bIsPersonnalTaskOwner = true;
		}
		else 
		{
			$this->m_bIsPersonnalTaskOwner = false;
		}
	}
}

function& getTskMgrContext()
{
	if(!isset($GLOBALS['BAB_TM_Context']))
	{
		$GLOBALS['BAB_TM_Context'] = new BAB_TM_Context();
	}
	return $GLOBALS['BAB_TM_Context'];
}

?>