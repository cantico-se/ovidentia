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

	var $m_iIdDelegation;
	var $m_iIdProjectSpace;
	var $m_iIdProject;
	var $m_iIdTask;
	var $m_oWorkingHours;
	
	var $m_bIsProjectVisualizer;
	var $m_aVisualizedIdProjectSpace;
	var $m_aVisualizedIdProject;
	
	
	function BAB_TM_Context()
	{
		global $babBody;

		$this->m_oTblWr = new BAB_TableWrapper('');
		$this->m_iIdProjectSpace = (int) tskmgr_getVariable('iIdProjectSpace', 0);
		$this->m_iIdProject = (int) tskmgr_getVariable('iIdProject', 0);
		$this->m_iIdTask = (int) tskmgr_getVariable('iIdTask', 0);
		$this->m_iIdDelegation = $babBody->currentAdmGroup;
		
		$this->m_oWorkingHours = null;
		
		$this->m_aConfiguration = null;
		$this->m_bIsProjectVisualizer = null;
		$this->m_aVisualizedIdProjectSpace = array();
		$this->m_aVisualizedIdProject = array();
		
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

	function getUserProfil()
	{
		$iUserProfil = BAB_TM_UNDEFINED;

		if(0 == $this->m_iIdTask) //Creation
		{
			if(0 != $this->m_iIdProject)
			{
				if(bab_isAccessValid(BAB_TSKMGR_PROJECTS_MANAGERS_GROUPS_TBL, $this->m_iIdProject))
				{
					$iUserProfil = BAB_TM_PROJECT_MANAGER;
				}
			}
			else
			{
				$aPersTaskCreator = bab_getUserIdObjects(BAB_TSKMGR_PERSONNAL_TASK_CREATOR_GROUPS_TBL);
				if(count($aPersTaskCreator) > 0 && isset($aPersTaskCreator[$this->m_iIdProjectSpace]))
				{
					$iUserProfil = BAB_TM_PERSONNAL_TASK_OWNER;
				}
			}
		}
		else // Edition
		{
			if(0 != $this->m_iIdProject)
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