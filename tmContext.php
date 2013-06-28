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
include 'base.php';
require_once $GLOBALS['babInstallPath'] . 'utilit/tableWrapperClass.php';
require_once $GLOBALS['babInstallPath'] . 'utilit/tmIncl.php';

class BAB_TM_Context
{
	var $m_oTblWr;
	var $m_aConfiguration;

	var $m_iIdDelegation;
	var $m_iIdProjectSpace;
	var $m_iIdProject;
	var $m_iIdTask;
	var $m_oWorkingHours;
	
	var $m_bIsProjectVisualizer;
	var $m_aVisualizedIdProjectSpace;
	var $m_aVisualizedIdProject;
	
	var $m_bIsProjectCreator;
	var $m_aProjectSpacesIdWhoUserIsCreator;
	
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
		$this->m_iIdProjectSpace = (int) bab_rp('iIdProjectSpace', 0);
		$this->m_iIdProject = (int) bab_rp('iIdProject', 0);
		$this->m_iIdTask = (int) bab_rp('iIdTask', 0);
		$this->m_iIdDelegation = $babBody->currentAdmGroup;

		$aProjectSpace = array();
		if(false !== bab_getProjectSpace($this->m_iIdProjectSpace, $aProjectSpace))
		{
			$this->m_iIdDelegation = (int) $aProjectSpace['idDelegation'];
		}
				
		$this->m_oWorkingHours = null;
		
		$this->m_aConfiguration = null;
		$this->m_bIsProjectVisualizer = null;
		$this->m_aVisualizedIdProjectSpace = array();
		$this->m_aVisualizedIdProject = array();
		
		$this->m_bIsProjectCreator = null;
		$this->m_aProjectSpacesIdWhoUserIsCreator = array();
		
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
	
	function getIdTask()
	{
		return $this->m_iIdTask;
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

	function isUserCanCreateProject()
	{
		if(is_null($this->m_bIsProjectCreator))
		{
			$this->queryProjectSpaceWhoUserCanCreate();
		}
		return $this->m_bIsProjectCreator;
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

	function isUserPersonnalTaskOwner()
	{
		if(is_null($this->m_bIsPersonnalTaskOwner))
		{
			$this->queryPersonnalOwnedTask();
		}
		return $this->m_bIsPersonnalTaskOwner;
	}

	function &getWorkingHoursObject()
	{
		if(is_null($this->m_oWorkingHours))
		{
			global $babInstallPath;
			require_once($babInstallPath . 'tmWorkingHoursFunc.php');
			$this->m_oWorkingHours = new BAB_WorkingHours();
		}
		return $this->m_oWorkingHours;
	}

	function &getTableWrapper()
	{
		return $this->m_oTblWr;
	}
	
	/**
	 * Returns the list of project spaces that can be visualized by the current user.
	 *
	 * @return array	An array where the keys represent the id's of visualizable project spaces 
	 */
	function getVisualisedIdProjectSpace()
	{
		if(is_null($this->m_bIsProjectVisualizer))
		{
			$this->queryVisualisedObject();
		}
		return $this->m_aVisualizedIdProjectSpace;
	}
	
	// Private
	function loadConfiguration()
	{
		global $babInstallPath;
		require_once($babInstallPath . 'utilit/tmIncl.php');
		
		$success = false;
		if(0 != $this->m_iIdProjectSpace)
		{
			if(0 == $this->m_iIdProject)
			{
				$success = bab_getDefaultProjectSpaceConfiguration($this->m_iIdProjectSpace, 
					$this->m_aConfiguration);
			}
			else
			{
				$success = bab_getProjectConfiguration($this->m_iIdProject, $this->m_aConfiguration);
			}
		}
		
		if($success)
		{
			return true;
		}
		else
		{
			$this->m_aConfiguration = null;
			return false;
		}
	}

	/**
	 * Determines the projets and project spaces that can be visualised by the current user.
	 * 
	 * A user can view a project space for which he is a default project visualiser or if is
	 * a project visualiser of a project contained in this space.
	 * 
	 * @access private
	 */
	function queryVisualisedObject()
	{
		require_once($GLOBALS['babInstallPath'] . 'admin/acl.php');
		
		$this->m_aVisualizedIdProjectSpace = bab_getUserIdObjects(BAB_TSKMGR_PROJECT_CREATOR_GROUPS_TBL);
		
		$this->m_aVisualizedIdProject = bab_getUserIdObjects(BAB_TSKMGR_PROJECTS_VISUALIZERS_GROUPS_TBL);
		if(count($this->m_aVisualizedIdProject) > 0)
		{
			$query = 
				'SELECT ' . 
					'idProjectSpace ' .
				'FROM ' . 
					BAB_TSKMGR_PROJECTS_TBL . ' ' .
				'WHERE ' . 
					'id IN(\'' . implode('\',\'', array_keys($this->m_aVisualizedIdProject)) . '\')';
				
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

	function queryProjectSpaceWhoUserCanCreate()
	{
		require_once($GLOBALS['babInstallPath'] . 'admin/acl.php');
		
		$this->m_aProjectSpacesIdWhoUserIsCreator = bab_getUserIdObjects(BAB_TSKMGR_PROJECT_CREATOR_GROUPS_TBL);
		if(count($this->m_aProjectSpacesIdWhoUserIsCreator) > 0)
		{
			$this->m_bIsProjectCreator = true;
		}
		else 
		{
			$this->m_bIsProjectCreator = false;
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
		/*		
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
		//*/
		
		$this->m_aPersonnalOwnedIdTask = bab_getUserIdObjects(BAB_TSKMGR_PERSONNAL_TASK_CREATOR_GROUPS_TBL);
		if(count($this->m_aPersonnalOwnedIdTask) > 0)
		{
			$this->m_bIsPersonnalTaskOwner = true;
		}
		else 
		{
			$this->m_bIsPersonnalTaskOwner = false;
		}
	}

	function getUserProfil()
	{
		$iUserProfil = BAB_TM_UNDEFINED;

		if(0 == $this->m_iIdTask) //Creation
		{
			if(0 < $this->m_iIdProject)
			{
				if(bab_isAccessValid(BAB_TSKMGR_PROJECTS_MANAGERS_GROUPS_TBL, $this->m_iIdProject))
				{
					$iUserProfil = BAB_TM_PROJECT_MANAGER;
				}
			}
			else
			{
				$aPersTaskCreator = bab_getUserIdObjects(BAB_TSKMGR_PERSONNAL_TASK_CREATOR_GROUPS_TBL);
				if(count($aPersTaskCreator) > 0)
				{
//					$success = array_search($this->m_iIdProjectSpace, $aPersTaskCreator);
//					if(!is_null($success) && false !== $success)
					{
						$iUserProfil = BAB_TM_PERSONNAL_TASK_OWNER;
					}
				}
			}
		}
		else // Edition
		{
			if(0 < $this->m_iIdProject)
			{
				if(bab_isAccessValid(BAB_TSKMGR_PROJECTS_MANAGERS_GROUPS_TBL, $this->m_iIdProject))
				{
					$iUserProfil = BAB_TM_PROJECT_MANAGER;
				}
				else 
				{
					bab_getTaskResponsibles($this->m_iIdTask, $aTaskResponsible);
					if(isset($aTaskResponsible[$GLOBALS['BAB_SESS_USERID']]))
					{
						$iUserProfil = BAB_TM_TASK_RESPONSIBLE;
					}
				}
			}
			else 
			{
				$aTask = array();
				if(bab_getTask($this->m_iIdTask, $aTask) && $GLOBALS['BAB_SESS_USERID'] == $aTask['iIdOwner'])
				{
					$iUserProfil = BAB_TM_PERSONNAL_TASK_OWNER;
				}
			}
		}
		return $iUserProfil;
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