<?php
//-------------------------------------------------------------------------
// OVIDENTIA http://www.ovidentia.org
//
// Ovidentia is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2, or (at your option)
// any later version.
//
// This program is distributed in the hope that it will be useful, but
// WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
// See the GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307,
// USA.
//-------------------------------------------------------------------------
/**
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 * @copyright Copyright (c) 2006 by CANTICO ({@link http://www.cantico.fr})
 */
include_once 'base.php';
//include_once $GLOBALS['babInstallPath']."utilit/delegincl.php";
require_once $GLOBALS['babInstallPath'] . 'utilit/tmIncl.php';
require_once $GLOBALS['babInstallPath'] . 'tmContext.php';



/**
 * OVML Container <OCTmSpaces>
 *
 * This container returns the list of Project Spaces viewable by the current user.
 *
 * Returned OVML variables are :
 * - OVSpaceId
 * - OVSpaceName
 * - OVSpaceDescription
 */
class bab_TmSpaces extends bab_handler
{
	var $index;
	var $count;
	var $res;


	function getProjectSpaces()
	{
		global $babDB;

		$oTmCtx =& getTskMgrContext();
		$conditions = array();
		//		if(($babBody->isSuperAdmin && $babBody->currentAdmGroup == 0) || $babBody->currentDGGroup['taskmanager'] == 'Y') {
		//		if ($babBody->currentAdmGroup) {
			//			$conditions[] = 'idDelegation = ' . $babDB->quote($babBody->currentAdmGroup);
			//		}
		$conditions[] = 'id IN(' . $babDB->quote(array_keys($oTmCtx->getVisualisedIdProjectSpace())) . ')';
		$query = "
				SELECT
					id, name, description
				FROM 
					" . BAB_TSKMGR_PROJECTS_SPACES_TBL . "
				WHERE 
					" . implode(' AND ', $conditions);
		return $babDB->db_query($query);
	}


	/**
	 * @param bab_Context	$ctx
	 * @return bab_TmSpaces
	 */
	function bab_TmSpaces(&$ctx)
	{
		global $babDB, $babBody;

		$this->bab_handler($ctx);
		$this->res = $this->getProjectSpaces();
		$this->count = $babDB->db_num_rows($this->res);
		$this->ctx->curctx->push('CCount', $this->count);
	}


	/**
	 * Fetch the next element of the container.
	 *
	 * @return bool		FALSE if there are no more elements.
	 */
	function getnext()
	{
		global $babDB;

		if ($this->idx < $this->count)
		{
			$space = $babDB->db_fetch_assoc($this->res);
			$this->ctx->curctx->push('CIndex', $this->idx);
			$this->ctx->curctx->push('SpaceId', $space['id']);
			$this->ctx->curctx->push('SpaceName', $space['name']);
			$this->ctx->curctx->push('SpaceDescription', $space['description']);
			$this->idx++;
			$this->index = $this->idx;
			return true;
		}
		else
		{
			$this->idx = 0;
			return false;
		}
	}
}



/**
 * OVML Container <OCTmProjects spaceid="id_space">
 *
 * This container returns the list of projects from the specified project space.
 *
 * Returned OVML variables are :
 * - OVProjectId
 * - OVProjectName
 * - OVProjectDescription
 */
class bab_TmProjects extends bab_handler
{
	var $index;
	var $count;
	var $res;

	var $projectIds;

	/**
	 * @param bab_Context	$ctx
	 * @return bab_TmProjects
	 */
	function bab_TmProjects(&$ctx)
	{
		global $babDB, $babBody;

		$this->bab_handler($ctx);
		$spaceId = $ctx->get_value('spaceid');

		// We look for all the project for which the user has visualisation rights.
		$this->projectIds = array();
		$res = bab_selectProjectList($spaceId);
		while ($project = $babDB->db_fetch_assoc($res))
		{
			if (bab_isAccessValid(BAB_TSKMGR_PROJECTS_VISUALIZERS_GROUPS_TBL, $project['id']))
			{
				array_push($this->projectIds, $project['id']);
			}
		}

		$this->count = count($this->projectIds);
		if ($this->count > 0)
		{
			$sql =
				'SELECT ' .
					'* ' .
				' FROM ' .
			BAB_TSKMGR_PROJECTS_TBL .
				' WHERE ' . 
					'id IN (' . $babDB->quote($this->projectIds) . ')' .
				' ORDER BY id';

			$this->res = $babDB->db_query($sql);
			$this->count = $babDB->db_num_rows($this->res);
		}

		$this->ctx->curctx->push('CCount', $this->count);
	}


	/**
	 * Fetch the next element of the container.
	 *
	 * @return bool		FALSE if there are no more elements.
	 */
	function getnext()
	{
		global $babDB;

		if ($this->idx < $this->count)
		{
			$project = $babDB->db_fetch_assoc($this->res);
			if ( ! bab_isAccessValid(BAB_TSKMGR_PROJECTS_VISUALIZERS_GROUPS_TBL, $project['id']))
			{
				$skip = true;
			}
			else
			{
				$skip = false;
				$this->ctx->curctx->push('CIndex', $this->idx);
				$this->ctx->curctx->push('ProjectId', $project['id']);
				$this->ctx->curctx->push('ProjectName', $project['name']);
				$this->ctx->curctx->push('ProjectDescription', $project['description']);
				$this->idx++;
				$this->index = $this->idx;
			}
			return true;
		}
		else
		{
			$this->idx = 0;
			return false;
		}
	}
}




/**
 * OVML Container <OCTmTasks [projectid="project_id"] [startdate="date"] [enddate="date"] [orderby="field_name"] [order="asc|desc"]>
 *
 * This container returns the list of tasks from the specified project
 * or the list of personal tasks if no project is specified.
 *
 * Returned OVML variables are:
 * - OVTaskId					The task id
 * - OVTaskProjectId			The id of the task's project
 * - OVTaskNumber				The task number
 * - OVTaskShortDescription		The task short description
 * - OVTaskStartDate
 * - OVTaskEndDate
 * - OVTaskCategoryId			The category id
 * - OVTaskCategoryName			The category name
 * - OVTaskCompletion			The task completion (in percent)
 * - OVTaskOwnerId				The user id of the task owner
 * - OVTaskClass
 */
class bab_TmTasks extends bab_handler
{
	var $index;
	var $count;
	var $res;

	/**
	 * @param bab_context	$ctx
	 * @return bab_TmTasks
	 */
	function bab_TmTasks(&$ctx)
	{
		global $babDB, $babBody;

		// Mapping between OVML variable names and parameters of function bab_selectTaskQuery.
		$columnNames = array(
						'TaskId' => 'iIdTask',
						'TaskProjectId' => 'iIdProject',
						'TaskNumber' => 'sTaskNumber',
						'TaskShortDescription' => 'sShortDescription',
						'TaskStartDate' => 'startDate',
						'TaskEndDate' => 'endDate',
						'TaskCategoryId' => 'iIdCategory',
						'TaskCategoryName' => 'sCategoryName',
						'TaskCompletion' => 'iCompletion',
						'TaskOwnerId' => 'idOwner',
						'TaskClass' => 'idClass'
						);

						$this->bab_handler($ctx);
						$aFilter = array();

						if ($idProject = $ctx->get_value('projectid'))
						{
							// If the parameter 'projectid' is specified we will return the tasks belonging
							// to this project if the user has visibility on the project.
							$aFilter['iIdProject'] = $idProject;
							if (!bab_isAccessValid(BAB_TSKMGR_PROJECTS_VISUALIZERS_GROUPS_TBL, $idProject))
							{
								$this->count = 0;
								$this->ctx->curctx->push('CCount', $this->count);
								return;
							}
						}
						else
						{
							// If the parameter 'projectid' is NOT specified we return the user's personal tasks.
							$aFilter['iIdOwner'] = $GLOBALS['BAB_SESS_USERID'];
							$aFilter['isPersonnal'] = true;
						}
						if ($startDate = $ctx->get_value('startdate'))
						{
							$aFilter['sStartDate'] = $startDate;
						}
						if ($endDate = $ctx->get_value('enddate'))
						{
							$aFilter['sEndDate'] = $endDate;
						}

						// The default ordering is ascending on field 'TaskNumber'.
						$sortFields = array('sName' => 'sTaskNumber', 'sOrder' => 'ASC');

						// The 'orderby' parameter must contain the name of the column (OVML variable name without OV prefix) on which
						// the container should be ordered.
						if (($orderBy = $ctx->get_value('orderby')) && array_key_exists($orderBy, $columnNames))
						{
							$sortFields['sName'] = $columnNames[$orderBy];
						}
						// The 'order' parameter must contain 'asc' or 'desc'.
						if (($order = $ctx->get_value('order')) && (strtoupper($order) == 'ASC' || strtoupper($order) == 'DESC'))
						{
							$sortFields['sOrder'] = strtoupper($order);
						}

						$sql = bab_selectTaskQuery($aFilter, $sortFields);
						$this->res = $babDB->db_query($sql);
						$this->count = $babDB->db_num_rows($this->res);
						$this->ctx->curctx->push('CCount', $this->count);
	}


	/**
	 * Fetch the next element of the container.
	 *
	 * @return bool		FALSE if there are no more elements.
	 */
	function getnext()
	{
		global $babDB;

		if ($this->idx < $this->count)
		{
			$task = $babDB->db_fetch_assoc($this->res);
			$this->ctx->curctx->push('CIndex', $this->idx);
			$this->ctx->curctx->push('TaskId', $task['iIdTask']);
			$this->ctx->curctx->push('TaskProjectId', $task['iIdProject']);
			$this->ctx->curctx->push('TaskNumber', $task['sTaskNumber']);
			$this->ctx->curctx->push('TaskShortDescription', $task['sShortDescription']);
			$this->ctx->curctx->push('TaskStartDate', bab_mktime($task['startDate']));
			$this->ctx->curctx->push('TaskEndDate', bab_mktime($task['endDate']));
			$this->ctx->curctx->push('TaskCategoryId', $task['iIdCategory']);
			$this->ctx->curctx->push('TaskCategoryName', $task['sCategoryName']);
			$this->ctx->curctx->push('TaskCompletion', $task['iCompletion']);
			$this->ctx->curctx->push('TaskOwnerId', $task['idOwner']);
			$this->ctx->curctx->push('TaskClass', $task['iClass']);
			$this->idx++;
			$this->index = $this->idx;
			return true;
		}
		else
		{
			$this->idx = 0;
			return false;
		}
	}

}



?>