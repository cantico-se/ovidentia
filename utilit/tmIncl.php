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
require_once $GLOBALS['babInstallPath'] . 'utilit/workinghoursincl.php';
require_once $GLOBALS['babInstallPath'] . 'utilit/tmdefines.php';


//Project space functions

function bab_selectProjectSpaceList()
{
	global $babDB, $babBody;

	$query = 
		'SELECT ' .
			'id, ' . 
			'name, ' . 
			'description ' .
		'FROM ' .
			BAB_TSKMGR_PROJECTS_SPACES_TBL . ' ' .
		'WHERE ' . 
			'idDelegation =\'' . $babDB->db_escape_string($babBody->currentAdmGroup) . '\'';
	
	//bab_debug($query);
	return $babDB->db_query($query);
}

function bab_getProjectSpaceList(&$aProjectSpaceList)
{
	global $babDB;

	$aProjectSpaceList = array();
	
	$res = bab_selectProjectSpaceList();
	if(false != $res)
	{
		$iNumRows = $babDB->db_num_rows($res);	
		$iIndex = 0;
		while($iIndex < $iNumRows && false != ($datas = $babDB->db_fetch_assoc($res)))
		{
			$aProjectSpaceList[] = array('id' => $datas['id'], 'name' => $datas['name'], 'description' => $datas['description']);
			$iIndex++;
		}
	}
}


function bab_selectProjectSpace($iIdProjectSpace)
{
	global $babDB;

	$aProjectSpace = array();

	$query = 
		'SELECT ' .
			'id, ' . 
			'idDelegation, ' . 
			'name, ' . 
			'description, ' .
			'refCount ' .
		'FROM ' .
			BAB_TSKMGR_PROJECTS_SPACES_TBL . ' ' .
		'WHERE ' . 
			'id =\'' . $babDB->db_escape_string($iIdProjectSpace) . '\'';

	return $babDB->db_query($query);
}


function bab_getProjectSpace($iIdProjectSpace, &$aProjectSpace)
{
	global $babDB;
	
	$res = bab_selectProjectSpace($iIdProjectSpace);
	if(false != $res)
	{
		if(false != ($datas = $babDB->db_fetch_assoc($res)))
		{
			$aProjectSpace = array('id' => $datas['id'],
								   'idDelegation' => $datas['idDelegation'],
								   'name' => $datas['name'], 
								   'description' => $datas['description'],
								   'refCount' => $datas['refCount']);
			return true;
		}
	}
	return false;
}


function bab_isProjectSpaceExist($iIdDelegation, $sName)
{
	global $babDB;

	$aProjectSpace = array();

	$query = 
		'SELECT ' .
			'id ' . 
		'FROM ' .
			BAB_TSKMGR_PROJECTS_SPACES_TBL . ' ' .
		'WHERE ' . 
			'idDelegation =\'' . $babDB->db_escape_string($iIdDelegation) . '\' AND ' .
			'name =\'' . $babDB->db_escape_string($sName) . '\'';
	
	//bab_debug($query);
	
	$res = $babDB->db_query($query);
	if(false != $res)
	{
		if(false != ($datas = $babDB->db_fetch_assoc($res)))
		{
			return $datas['id'];
		}
	}
	return false;
}

function bab_deleteProjectSpace($iIdProjectSpace)
{
	global $babDB;

	$query = 
		'SELECT ' .
			'id ' . 
		'FROM ' .
			BAB_TSKMGR_PROJECTS_TBL . ' ' .
		'WHERE ' . 
			'idProjectSpace =\'' . $babDB->db_escape_string($iIdProjectSpace) . '\'';

	//bab_debug($query);
	
	$res = $babDB->db_query($query);
	if(false != $res)
	{
		if(false != ($datas = $babDB->db_fetch_assoc($res)))
		{
			bab_deleteProject($iIdProjectSpace, $datas['id']);
		}
	}
	
	$query = 'DELETE FROM ' . BAB_TSKMGR_PROJECTS_SPACES_TBL . ' WHERE id = \'' . $babDB->db_escape_string($iIdProjectSpace) . '\''; 
	//bab_debug($query);
	$babDB->db_query($query);
	
	$query = 'DELETE FROM ' . BAB_TSKMGR_DEFAULT_PROJECTS_CONFIGURATION_TBL . ' WHERE idProjectSpace = \'' . $babDB->db_escape_string($iIdProjectSpace) . '\''; 
	//bab_debug($query);
	$babDB->db_query($query);

	$query = 'DELETE FROM ' . BAB_TSKMGR_CATEGORIES_TBL . ' WHERE idProjectSpace = \'' . $babDB->db_escape_string($iIdProjectSpace) . '\''; 
	//bab_debug($query);
	$babDB->db_query($query);

	bab_deleteProjectSpaceSpecificFields($iIdProjectSpace);
	
	require_once($GLOBALS['babInstallPath'] . 'admin/acl.php');
	
	aclDelete(BAB_TSKMGR_PROJECT_CREATOR_GROUPS_TBL, $iIdProjectSpace);
	aclDelete(BAB_TSKMGR_PERSONNAL_TASK_CREATOR_GROUPS_TBL, $iIdProjectSpace);
	aclDelete(BAB_TSKMGR_DEFAULT_PROJECTS_MANAGERS_GROUPS_TBL, $iIdProjectSpace);
	aclDelete(BAB_TSKMGR_DEFAULT_PROJECTS_SUPERVISORS_GROUPS_TBL, $iIdProjectSpace);
	aclDelete(BAB_TSKMGR_DEFAULT_PROJECTS_VISUALIZERS_GROUPS_TBL, $iIdProjectSpace);
	aclDelete(BAB_TSKMGR_DEFAULT_TASK_RESPONSIBLE_GROUPS_TBL, $iIdProjectSpace);
	$iIdProject = 0;
	bab_deleteAllNoticeEvent($iIdProjectSpace, $iIdProject);
}

function bab_createProjectSpace($iIdDelegation, $sName, $sDescription)
{
	global $babDB;

	$query = 
		'INSERT INTO ' . BAB_TSKMGR_PROJECTS_SPACES_TBL . ' ' .
			'(' .
				'`id`, ' .
				'`idDelegation`, `name`, `description`, `created`, `idUserCreated`' .
			') ' .
		'VALUES ' . 
			'(\'\', \'' . 
				$babDB->db_escape_string($iIdDelegation) . '\', \'' . 
				$babDB->db_escape_string($sName) . '\', \'' . 
				$babDB->db_escape_string($sDescription) . '\', \'' . 
				$babDB->db_escape_string(date("Y-m-d H:i:s")) . '\', \'' . 
				$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID']) . 
			'\')'; 

	//bab_debug($query);
	$res = $babDB->db_query($query);
	if(false != $res)
	{
		return $babDB->db_insert_id();
	}
	return false;
}

function bab_updateProjectSpace($iIdProjectSpace, $sName, $sDescription)
{
	global $babDB;

	$query = 
		'UPDATE ' . 
			BAB_TSKMGR_PROJECTS_SPACES_TBL . ' ' .
		'SET ' . ' ' .
				'`name` = \'' . $babDB->db_escape_string($sName) . '\', ' .
				'`description` = \'' . $babDB->db_escape_string($sDescription) . '\', ' .
				'`modified` = \'' . $babDB->db_escape_string(date("Y-m-d H:i:s")) . '\', ' .
				'`idUserModified` = \'' . $babDB->db_escape_string($GLOBALS['BAB_SESS_USERID']) . '\' ' .
		'WHERE ' . 
			'`id` = \'' . $babDB->db_escape_string($iIdProjectSpace) . '\'';

	//bab_debug($query);
	return $babDB->db_query($query);
}

function bab_getDefaultProjectSpaceConfiguration($iIdProjectSpace, &$aConfiguration)
{
	global $babDB;

	$aConfiguration = array();

	$query = 
		'SELECT ' .
			'id, ' . 
			'idProjectSpace, ' . 
			'tskUpdateByMgr, ' .
			'endTaskReminder, ' .
			'tasksNumerotation, ' .
			'emailNotice, ' .
			'faqUrl ' .
		'FROM ' .
			BAB_TSKMGR_DEFAULT_PROJECTS_CONFIGURATION_TBL . ' ' .
		'WHERE ' . 
			'idProjectSpace =\'' . $babDB->db_escape_string($iIdProjectSpace) . '\'';
	
	//bab_debug($query);
	
	$res = $babDB->db_query($query);
	if(false != $res)
	{
		if(false != ($datas = $babDB->db_fetch_assoc($res)))
		{
			$aConfiguration = array('id' => $datas['id'], 'idProjectSpace' => $datas['idProjectSpace'], 
				'tskUpdateByMgr' => $datas['tskUpdateByMgr'], 'endTaskReminder' => $datas['endTaskReminder'], 
				'tasksNumerotation' => $datas['tasksNumerotation'], 'emailNotice' => $datas['emailNotice'],
				'faqUrl' => $datas['faqUrl']);
			return true;
		}
	}
	return false;
}

function bab_createDefaultProjectSpaceConfiguration($iIdProjectSpace)
{
	global $babDB;

	$query = 
		'INSERT INTO ' . BAB_TSKMGR_DEFAULT_PROJECTS_CONFIGURATION_TBL . ' ' .
			'(' .
				'`id`, ' .
				'`idProjectSpace`, `tskUpdateByMgr`, `endTaskReminder`, `tasksNumerotation`, `emailNotice`, `faqUrl`' .
			') ' .
		'VALUES ' . 
			'(\'\', \'' . 
				$babDB->db_escape_string($iIdProjectSpace) . '\', \'' . 
				$babDB->db_escape_string(BAB_TM_YES) . '\', \'' . 
				$babDB->db_escape_string(5) . '\', \'' . 
				$babDB->db_escape_string(BAB_TM_SEQUENTIAL) . '\', \'' . 
				$babDB->db_escape_string(BAB_TM_YES) . 
			'\', \'\')'; 

	//bab_debug($query);
	return $babDB->db_query($query);
}

function bab_updateDefaultProjectSpaceConfiguration($aConfiguration)
{
	global $babDB;

	$query = 
		'UPDATE ' . 
			BAB_TSKMGR_DEFAULT_PROJECTS_CONFIGURATION_TBL . ' ' .
		'SET ' . ' ' .
				'`idProjectSpace` = \'' . $babDB->db_escape_string($aConfiguration['idProjectSpace']) . '\', ' .
				'`tskUpdateByMgr` = \'' . $babDB->db_escape_string($aConfiguration['tskUpdateByMgr']) . '\', ' .
				'`endTaskReminder` = \'' . $babDB->db_escape_string($aConfiguration['endTaskReminder']) . '\', ' .
				'`tasksNumerotation` = \'' . $babDB->db_escape_string($aConfiguration['tasksNumerotation']) . '\', ' .
				'`emailNotice` = \'' . $babDB->db_escape_string($aConfiguration['emailNotice']) . '\', ' .
				'`faqUrl` = \'' . $babDB->db_escape_string($aConfiguration['faqUrl']) . '\' ' .
		'WHERE ' . 
			'`id` = \'' . $babDB->db_escape_string($aConfiguration['id']) . '\'';

	//bab_debug($query);
	return $babDB->db_query($query);
}

function bab_deleteProjectSpaceSpecificFields($iIdProjectSpace)
{
	bab_deleteAllSpecificFields('idProjectSpace', $iIdProjectSpace);
}

function bab_isProjectSpaceDeletable($iIdProjectSpace)
{
	$aProjectSpace = null;
	if(bab_getProjectSpace($iIdProjectSpace, $aProjectSpace))
	{
		return (0 == $aProjectSpace['refCount']);
	}
	return false;
}






//Project functions
function bab_selectProjectListByDelegation($iIdDelegation)
{
	global $babDB;

	$query = 
		'SELECT ' .
			'ps.id iIdProjectSpace, ' .
			'ps.name sProjectSpaceName, ' .
			'p.id iIdProject, ' .
			'p.name sProjectName ' .
		'FROM ' .
			BAB_TSKMGR_PROJECTS_TBL . ' p ' .
		'LEFT JOIN ' .
			BAB_TSKMGR_PROJECTS_SPACES_TBL . ' ps ON ps.id = p.idProjectSpace ' .
		'WHERE ' . 
			'ps.idDelegation = \'' . $babDB->db_escape_string($iIdDelegation) . '\'';
			
	//bab_debug($query);
	return $babDB->db_query($query);
}


function bab_selectProjectList($iIdProjectSpace)
{
	global $babDB;

	$query = 
		'SELECT ' .
			'* ' .
		'FROM ' .
			BAB_TSKMGR_PROJECTS_TBL . ' ' .
		'WHERE ' . 
			'idProjectSpace = \'' . $babDB->db_escape_string($iIdProjectSpace) . '\'';
			
	//bab_debug($query);
	return $babDB->db_query($query);
}

function bab_getProjectList($iIdProjectSpace, &$aProjectList)
{
	global $babDB;

	$aProjectList = array();
	
	$res = bab_SelectProjectList($iIdProjectSpace);
	if(false != $res)
	{
		$iNumRows = $babDB->db_num_rows($res);	
		$iIndex = 0;
		while($iIndex < $iNumRows && false != ($datas = $babDB->db_fetch_assoc($res)))
		{
			$aProjectList[] = array('id' => $datas['id'], 'name' => $datas['name'], 'description' => $datas['description']);
			$iIndex++;
		}
	}
}

function bab_getProject($iIdProject, &$aProject)
{
	global $babDB;

	$aProject = array();
	
	$query = 
		'SELECT ' .
			'* ' .
		'FROM ' .
			BAB_TSKMGR_PROJECTS_TBL . ' ' .
		'WHERE ' . 
			'id = \'' . $babDB->db_escape_string($iIdProject) . '\'';

	//bab_debug($query);
	
	$res = $babDB->db_query($query);
	if(false != $res)
	{
		$iNumRows = $babDB->db_num_rows($res);	
		$iIndex = 0;
		if(/*$iIndex < $iNumRows &&*/ false != ($datas = $babDB->db_fetch_assoc($res)))
		{
			$aProject = array('id' => $datas['id'], 'name' => $datas['name'], 'description' => $datas['description'], 
				'isLocked' => $datas['isLocked'], 'state' => $datas['state'], 'idProjectSpace' => $datas['idProjectSpace']);
			$iIndex++;
			return true;
		}
	}
	return false;
}

function bab_createProject($iIdProjectSpace, $sName, $sDescription, $iMajorVersion, $iMinorVersion)
{
	global $babDB;

	$query = 
		'INSERT INTO ' . BAB_TSKMGR_PROJECTS_TBL . ' ' .
			'(' .
				'`id`, ' .
				'`idProjectSpace`, `name`, `description`, `created`, `idUserCreated`' .
			') ' .
		'VALUES ' . 
			'(\'\', \'' . 
				$babDB->db_escape_string($iIdProjectSpace) . '\', \'' . 
				$babDB->db_escape_string($sName) . '\', \'' . 
				$babDB->db_escape_string($sDescription) . '\', \'' . 
				$babDB->db_escape_string(date("Y-m-d H:i:s")) . '\', \'' . 
				$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID']) . 
			'\')'; 

	//bab_debug($query);
	$res = $babDB->db_query($query);
	if(false != $res)
	{
		$iIdProject = $babDB->db_insert_id();

		$iIdProjectComment = 0;
		bab_createProjectRevision($iIdProject, $iIdProjectComment, $iMajorVersion, $iMinorVersion);
		
		require_once($GLOBALS['babInstallPath'] . 'admin/acl.php');

		aclDuplicateRights(
			BAB_TSKMGR_DEFAULT_PROJECTS_VISUALIZERS_GROUPS_TBL, $iIdProjectSpace, 
			BAB_TSKMGR_PROJECTS_VISUALIZERS_GROUPS_TBL, $iIdProject);					
		aclDuplicateRights(
			BAB_TSKMGR_DEFAULT_PROJECTS_SUPERVISORS_GROUPS_TBL, $iIdProjectSpace, 
			BAB_TSKMGR_PROJECTS_SUPERVISORS_GROUPS_TBL, $iIdProject);					
		aclDuplicateRights(
			BAB_TSKMGR_DEFAULT_PROJECTS_MANAGERS_GROUPS_TBL, $iIdProjectSpace, 
			BAB_TSKMGR_PROJECTS_MANAGERS_GROUPS_TBL, $iIdProject);
		aclDuplicateRights(
			BAB_TSKMGR_DEFAULT_TASK_RESPONSIBLE_GROUPS_TBL, $iIdProjectSpace, 
			BAB_TSKMGR_TASK_RESPONSIBLE_GROUPS_TBL, $iIdProject);
			
		$aConfiguration = null;
		$bSuccess = bab_getDefaultProjectSpaceConfiguration($iIdProjectSpace, $aConfiguration);	
		if($bSuccess)
		{
			unset($aConfiguration['id']);
			unset($aConfiguration['idProjectSpace']);
			$aConfiguration['idProject'] = $iIdProject;
			bab_createProjectConfiguration($aConfiguration);
		}
		
		bab_updateRefCount(BAB_TSKMGR_PROJECTS_SPACES_TBL, $iIdProjectSpace, '+ 1');
		
		$result = bab_selectProjectSpaceNoticeEvent($iIdProjectSpace);
		if(false != $result)
		{
			$iNumRows = $babDB->db_num_rows($result);	
			$iIndex = 0;
			while($iIndex < $iNumRows && false != ($datas = $babDB->db_fetch_assoc($result)))
			{
				bab_createNoticeEvent($iIdProjectSpace, $iIdProject, $datas['idEvent'], $datas['profil']);
				$iIndex++;
			}
		}
		
		createDefaultProjectSelectedField($iIdProject);
		
		return $iIdProject;		
	}
	return false;
}

function bab_updateProject($iIdProject, $sName, $sDescription)
{
	global $babDB;

	$query = 
		'UPDATE ' . 
			BAB_TSKMGR_PROJECTS_TBL . ' ' .
		'SET ' . ' ' .
				'`name` = \'' . $babDB->db_escape_string($sName) . '\', ' .
				'`description` = \'' . $babDB->db_escape_string($sDescription) . '\', ' .
				'`modified` = \'' . $babDB->db_escape_string(date("Y-m-d H:i:s")) . '\', ' .
				'`idUserModified` = \'' . $babDB->db_escape_string($GLOBALS['BAB_SESS_USERID']) . '\' ' .
		'WHERE ' . 
			'`id` = \'' . $babDB->db_escape_string($iIdProject) . '\'';

	//bab_debug($query);
	return $babDB->db_query($query);
}

function bab_deleteProject($iIdProjectSpace, $iIdProject)
{
	global $babDB;
	
	bab_deleteAllTask($iIdProjectSpace, $iIdProject);
	
	aclDelete(BAB_TSKMGR_PROJECTS_MANAGERS_GROUPS_TBL, $iIdProject);
	aclDelete(BAB_TSKMGR_PROJECTS_SUPERVISORS_GROUPS_TBL, $iIdProject);
	aclDelete(BAB_TSKMGR_PROJECTS_VISUALIZERS_GROUPS_TBL, $iIdProject);
	aclDelete(BAB_TSKMGR_TASK_RESPONSIBLE_GROUPS_TBL, $iIdProject);

	$query = 
		'DELETE FROM ' . 
			BAB_TSKMGR_PROJECTS_CONFIGURATION_TBL . ' ' .
		'WHERE ' . 
			'idProject = \'' . $babDB->db_escape_string($iIdProject) . '\''; 
	//bab_debug($query);
	$babDB->db_query($query);

	$query = 
		'DELETE FROM ' . 
			BAB_TSKMGR_PROJECTS_REVISIONS_TBL . ' ' . 
		'WHERE ' .
			'idProject = \'' . $babDB->db_escape_string($iIdProject) . '\''; 
	//bab_debug($query);
	$babDB->db_query($query);

	$query = 
		'DELETE FROM ' . 
			BAB_TSKMGR_PROJECTS_COMMENTS_TBL . ' ' .
		'WHERE ' .
			'idProject = \'' . $babDB->db_escape_string($iIdProject) . '\''; 
	//bab_debug($query);
	$babDB->db_query($query);

	$query = 
		'DELETE FROM ' . 
			BAB_TSKMGR_CATEGORIES_TBL . ' ' .
		'WHERE ' . 
			'idProject = \'' . $babDB->db_escape_string($iIdProject) . '\'';
	//bab_debug($query);
	$babDB->db_query($query);

	bab_deleteProjectSpecificFields($iIdProject);	
	
	bab_tskmgr_deleteSelectedTaskFields($iIdProject);
	bab_tskmgr_dropAdditionalFieldTable($iIdProjectSpace, $iIdProject);

	$query = 
		'DELETE FROM ' . 
			BAB_TSKMGR_PROJECTS_TBL . ' ' .
		'WHERE ' .
			'id = \'' . $babDB->db_escape_string($iIdProject) . '\''; 
	//bab_debug($query);
	return $babDB->db_query($query);
}

function bab_createProjectConfiguration($aConfiguration)
{
	global $babDB;

	$query = 
		'INSERT INTO ' . BAB_TSKMGR_PROJECTS_CONFIGURATION_TBL . ' ' .
			'(' .
				'`id`, ' .
				'`idProject`, `tskUpdateByMgr`, `endTaskReminder`, `tasksNumerotation`, `emailNotice`, `faqUrl`' .
			') ' .
		'VALUES ' . 
			'(\'\', \'' . 
				$babDB->db_escape_string($aConfiguration['idProject']) . '\', \'' . 
				$babDB->db_escape_string($aConfiguration['tskUpdateByMgr']) . '\', \'' . 
				$babDB->db_escape_string($aConfiguration['endTaskReminder']) . '\', \'' . 
				$babDB->db_escape_string($aConfiguration['tasksNumerotation']) . '\', \'' . 
				$babDB->db_escape_string($aConfiguration['emailNotice']) . '\', \'' . 
				$babDB->db_escape_string($aConfiguration['faqUrl']) . 
			'\')'; 

	//bab_debug($query);
	return $babDB->db_query($query);
}

function bab_getProjectConfiguration($iIdProject, &$aConfiguration)
{
	global $babDB;

	$aConfiguration = array();

	$query = 
		'SELECT ' .
			'id, ' . 
			'idProject, ' . 
			'tskUpdateByMgr, ' .
			'endTaskReminder, ' .
			'tasksNumerotation, ' .
			'emailNotice, ' .
			'faqUrl ' .
		'FROM ' .
			BAB_TSKMGR_PROJECTS_CONFIGURATION_TBL . ' ' .
		'WHERE ' . 
			'idProject =\'' . $babDB->db_escape_string($iIdProject) . '\'';
	
	//bab_debug($query);
	
	$res = $babDB->db_query($query);
	if(false != $res)
	{
		if(false != ($datas = $babDB->db_fetch_assoc($res)))
		{
			$aConfiguration = array('id' => $datas['id'], 'idProject' => $datas['idProject'], 
				'tskUpdateByMgr' => $datas['tskUpdateByMgr'], 'endTaskReminder' => $datas['endTaskReminder'], 
				'tasksNumerotation' => $datas['tasksNumerotation'], 'emailNotice' => $datas['emailNotice'],
				'faqUrl' => $datas['faqUrl']);
			return true;
		}
	}
	return false;	
}

function bab_updateProjectConfiguration($aConfiguration)
{
	global $babDB;

	$query = 
		'UPDATE ' . 
			BAB_TSKMGR_PROJECTS_CONFIGURATION_TBL . ' ' .
		'SET ' . ' ' .
				'`idProject` = \'' . $babDB->db_escape_string($aConfiguration['idProject']) . '\', ' .
				'`tskUpdateByMgr` = \'' . $babDB->db_escape_string($aConfiguration['tskUpdateByMgr']) . '\', ' .
				'`endTaskReminder` = \'' . $babDB->db_escape_string($aConfiguration['endTaskReminder']) . '\', ' .
				'`tasksNumerotation` = \'' . $babDB->db_escape_string($aConfiguration['tasksNumerotation']) . '\', ' .
				'`emailNotice` = \'' . $babDB->db_escape_string($aConfiguration['emailNotice']) . '\', ' .
				'`faqUrl` = \'' . $babDB->db_escape_string($aConfiguration['faqUrl']) . '\' ' .
		'WHERE ' . 
			'`id` = \'' . $babDB->db_escape_string($aConfiguration['id']) . '\'';

	//bab_debug($query);
	return $babDB->db_query($query);
}

function bab_deleteProjectSpecificFields($iIdProject)
{
	bab_deleteAllSpecificFields('idProject', $iIdProject);
}

function bab_isProjectDeletable($iIdProject)
{
	$iTaskCount = bab_getTaskCount($iIdProject);
	return (0 == $iTaskCount);
}

function bab_selectProjectCommentaryList($iIdProject, $iLenght = 50)
{
	global $babDB;
	
	$query = 
		'SELECT ' .
			'id, ' . 
			'IF(LENGTH(commentary) > \'' . $babDB->db_escape_string($iLenght) . '\', ' . 
				'CONCAT(LEFT(commentary, \'' . $babDB->db_escape_string($iLenght) . '\'), \'...\'), commentary) commentary, ' .
			'created ' .
		'FROM ' .
			BAB_TSKMGR_PROJECTS_COMMENTS_TBL . ' ' .
		'WHERE ' . 
			'idProject =\'' . $babDB->db_escape_string($iIdProject) . '\'';
	
	//bab_debug($query);
	return $babDB->db_query($query);
}

function bab_getProjectCommentary($iIdCommentary, &$sCommentary)
{
	global $babDB;
	
	$sCommentary = '';
	
	$query = 
		'SELECT ' .
			'id, ' . 
			'commentary ' .
		'FROM ' .
			BAB_TSKMGR_PROJECTS_COMMENTS_TBL . ' ' .
		'WHERE ' . 
			'id =\'' . $babDB->db_escape_string($iIdCommentary) . '\'';
	
	//bab_debug($query);
	$result = $babDB->db_query($query);
	
	if(false != ($datas = $babDB->db_fetch_assoc($result)))
	{
		$sCommentary = $datas['commentary'];
		return true;
	}
	return false;
}

function bab_createProjectCommentary($iIdProject, $sCommentary)
{
	global $babDB;
	
	$query = 
		'INSERT INTO ' . BAB_TSKMGR_PROJECTS_COMMENTS_TBL . ' ' .
			'(' .
				'`id`, ' .
				'`idProject`, `commentary`, `created`, `idUserCreated`' .
			') ' .
		'VALUES ' . 
			'(\'\', \'' . 
				$babDB->db_escape_string($iIdProject) . '\', \'' . 
				$babDB->db_escape_string($sCommentary) . '\', \'' . 
				$babDB->db_escape_string(date("Y-m-d H:i:s")) . '\', \'' . 
				$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID']) . 
			'\')'; 

	//bab_debug($query);
	return $babDB->db_query($query);
}

function bab_updateProjectCommentary($iIdCommentary, $sCommentary)
{
	global $babDB;

	$query = 
		'UPDATE ' . 
			BAB_TSKMGR_PROJECTS_COMMENTS_TBL . ' ' .
		'SET ' . ' ' .
				'`commentary` = \'' . $babDB->db_escape_string($sCommentary) . '\', ' .
				'`modified` = \'' . $babDB->db_escape_string(date("Y-m-d H:i:s")) . '\', ' .
				'`idUserModified` = \'' . $babDB->db_escape_string($GLOBALS['BAB_SESS_USERID']) . '\' ' .
		'WHERE ' . 
			'`id` = \'' . $babDB->db_escape_string($iIdCommentary) . '\'';

	//bab_debug($query);
	return $babDB->db_query($query);
}

function bab_deleteProjectCommentary($iIdCommentary)
{
	global $babDB;
	
	$query = 
		'DELETE FROM '	. 
			BAB_TSKMGR_PROJECTS_COMMENTS_TBL . ' ' . 
		'WHERE ' . 
			'id = \'' . $babDB->db_escape_string($iIdCommentary) . '\'';
			
	$babDB->db_query($query);
}

function bab_createProjectRevision($iIdProject, $iIdProjectComment, $iMajorVersion, $iMinorVersion)
{
	global $babDB;

	$query = 
		'INSERT INTO ' . BAB_TSKMGR_PROJECTS_REVISIONS_TBL . ' ' .
			'(' .
				'`id`, ' .
				'`idProject`, `idProjectComment`, `majorVersion`, `minorVersion`' .
			') ' .
		'VALUES ' . 
			'(\'\', \'' . 
				$babDB->db_escape_string($iIdProject) . '\', \'' . 
				$babDB->db_escape_string($iIdProjectComment) . '\', \'' . 
				$babDB->db_escape_string($iMajorVersion) . '\', \'' . 
				$babDB->db_escape_string($iMinorVersion) . 
			'\')'; 

	//bab_debug($query);
	
	return $babDB->db_query($query);
}

function bab_getLastProjectRevision($iIdProject, &$iMajorVersion, &$iMinorVersion)
{
	global $babDB;
	
	//Selection de la date
	$query_major = 
		'SELECT ' .
			'@major := MAX(majorVersion) ' .
		'FROM ' .
			BAB_TSKMGR_PROJECTS_REVISIONS_TBL . ' ' . 
		'WHERE ' . 
			'idProject = \'' . $babDB->db_escape_string($iIdProject) . '\'';
	
	$query_minor = 
		'SELECT ' .
			'@minor := MAX(minorVersion) ' .
		'FROM ' .
			BAB_TSKMGR_PROJECTS_REVISIONS_TBL . ' ' . 
		'WHERE ' . 
			'idProject = \'' . $babDB->db_escape_string($iIdProject) . '\' AND ' .
			'majorVersion = @major';
			
	$query = 
		'SELECT ' .
			'majorVersion, ' .
			'minorVersion ' .
		'FROM ' .
			BAB_TSKMGR_PROJECTS_REVISIONS_TBL . ' ' . 
		'WHERE ' . 
			'idProject = \'' . $babDB->db_escape_string($iIdProject) . '\' AND ' .
			'majorVersion = @major AND ' .
			'minorVersion = @minor';

	$babDB->db_query($query_major);
	$babDB->db_query($query_minor);
	$result = $babDB->db_query($query);
	$iNumRows = $babDB->db_num_rows($result);
	$iIndex = 0;
	
	if($iIndex < $iNumRows && false != ($datas = $babDB->db_fetch_assoc($result)))
	{
		$iMajorVersion = $datas['majorVersion'];
		$iMinorVersion = $datas['minorVersion'];
	}
}


function bab_getTaskCount($iIdProject, $iIdUser = -1)
{
	global $babDB;
	
	$query = 
		'SELECT ' . 
			'COUNT(id) iTaskCount ' .
		'FROM ' . 
			BAB_TSKMGR_TASKS_TBL . ' t ' .
		'WHERE ' . 
			't.idProject = \'' . $babDB->db_escape_string($iIdProject) . '\' ';
			
	if(-1 !== $iIdUser)
	{
		$query .= 'AND idUserCreated = \'' . $babDB->db_escape_string($iIdUser) . '\'';
	}
		
	//bab_debug($query);
	
	$result = $babDB->db_query($query);
	$iNumRows = $babDB->db_num_rows($result);
	if(false != $result && $iNumRows > 0)
	{
		$datas = $babDB->db_fetch_assoc($result);
		return $datas['iTaskCount'];
	}
	return 0;
}


function bab_getDependingTasks($iIdTask, &$aDependingTasks, $iLinkType = -1)
{
	global $babDB;
	
	$query = 
		'SELECT ' . 
			'lt.idTask, ' .
			'lt.linkType iLinkType, ' .
			'IFNULL(tr.idResponsible, 0) idResponsible ' .
		'FROM ' . 
			BAB_TSKMGR_LINKED_TASKS_TBL . ' lt, ' .
			BAB_TSKMGR_TASKS_TBL . ' t ' .
		'LEFT JOIN ' .
			BAB_TSKMGR_TASKS_RESPONSIBLES_TBL . ' tr ON tr.idTask = t.id ' .
		'WHERE ' . 
			'lt.idPredecessorTask = \'' . $babDB->db_escape_string($iIdTask) . '\'' .
			(($iLinkType != -1) ? ' AND lt.linkType = \'' . $babDB->db_escape_string($iLinkType) . '\' ' : ' ') .
		'GROUP BY lt.idTask';
		
	//bab_debug($query);


	$result = $babDB->db_query($query);
	$iNumRows = $babDB->db_num_rows($result);
	$iIndex = 0;
	
	while($iIndex < $iNumRows && false != ($datas = $babDB->db_fetch_assoc($result)))
	{
		$aDependingTasks[$datas['idTask']] = array('iIdTask' => $datas['idTask'],
			'iIdResponsible' => $datas['idResponsible'], 'iLinkType' => $datas['iLinkType']);
		$iIndex++;
	}
}


function bab_getAllTaskIndexedById($iIdProject, &$aTasks)
{
	global $babDB;

	$aTasks = array();	

	$query = 
		'SELECT ' .
			'id, ' . 
			'idProject, ' .
			'taskNumber, ' .
			'description, ' .
			'shortDescription, ' .
			'idCategory, ' .
			'created, ' .
			'modified, ' .
			'idUserCreated, ' .
			'idUserModified, ' .
			'class, ' .
			'participationStatus, ' .
			'isLinked, ' .
			'idCalEvent, ' .
			'hashCalEvent, ' .
			'duration, ' .
			'iDurationUnit, ' .
			'majorVersion, ' .
			'minorVersion, ' .
			'color, ' .
			'position, ' .
			'completion, ' .
			'plannedStartDate, ' .
			'plannedEndDate, ' .
			'startDate, ' .
			'endDate, ' .
			'isNotified, ' .
			'iPlannedTime, ' .			  
			'iPlannedTimeDurationUnit, ' .			  
			'iTime, ' .			  
			'iTimeDurationUnit, ' .			  
			'iPlannedCost, ' .			  
			'iCost, ' .
			'iPriority ' .			  
		'FROM ' .
			BAB_TSKMGR_TASKS_TBL . ' ' .
		'WHERE ' . 
			'idProject = \'' . $babDB->db_escape_string($iIdProject) . '\'';
			
//	bab_debug($query);

	$result = $babDB->db_query($query);
	$iNumRows = $babDB->db_num_rows($result);
	$iIndex = 0;
	
	while($iIndex < $iNumRows && false != ($datas = $babDB->db_fetch_assoc($result)))
	{
		$aTasks[$datas['id']] = array('id' => $datas['id'], 'iIdProject' =>  $datas['idProject'], 
			'sTaskNumber' => $datas['taskNumber'], 'sDescription' => $datas['description'], 
			'iIdCategory' => $datas['idCategory'], 'sCreated' => $datas['created'], 
			'sModified' => $datas['modified'], 'iIdUserCreated' => $datas['idUserCreated'], 
			'iIdUserModified' => $datas['idUserModified'], 'iClass' => $datas['class'], 
			'iParticipationStatus' => $datas['participationStatus'],
			'iIsLinked' => $datas['isLinked'], 'iIdCalEvent' => $datas['idCalEvent'],  
			'sHashCalEvent' => $datas['hashCalEvent'], 'iDuration' => $datas['duration'],  
			'iDurationUnit' => $datas['iDurationUnit'], 'iMajorVersion' => $datas['majorVersion'], 
			'iMinorVersion' => $datas['minorVersion'], 'sColor' => $datas['color'], 
			'iPosition' => $datas['position'], 'iCompletion' => $datas['completion'],
			'sPlannedStartDate' => $datas['plannedStartDate'], 'sStartDate' => $datas['startDate'],
			'sPlannedEndDate' => $datas['plannedEndDate'], 'sEndDate' => $datas['endDate'],
			'iIsNotified' => $datas['isNotified'], 'sShortDescription' => $datas['shortDescription'],
			'iPlannedTime' => $datas['iPlannedTime'], 'iPlannedTimeDurationUnit' => $datas['iPlannedTimeDurationUnit'], 			  
			'iTime' => $datas['iTime'], 'iTimeDurationUnit' => $datas['iTimeDurationUnit'], 			  
			'iPlannedCost' => $datas['iPlannedCost'], 'iCost' => $datas['iCost'],
			'iPriority' => $datas['iPriority']);
		$iIndex++;
	}
}


function bab_createTask($aParams)
{
	global $babDB;

	$aTask = array();	

	$query = 
		'INSERT INTO ' . BAB_TSKMGR_TASKS_TBL . ' ' .
			'(' .
				'`id`, ' .
				'`idProject`, `taskNumber`, `description`, `idCategory`, `class`, ' .
				'`participationStatus`, `isLinked`, `idCalEvent`, `hashCalEvent`, ' .
				'`duration`, `iDurationUnit`, `majorVersion`, `minorVersion`, `color`, `position`, ' .
				'`completion`, `startDate`, `endDate`, `plannedStartDate`, ' .
				'`plannedEndDate`, `created`, `idUserCreated`, `isNotified`, ' .
				'`idUserModified`, `modified`, `shortDescription`, ' .
				'`iPlannedTime`, `iPlannedTimeDurationUnit`, `iTime`, ' . 
				'`iTimeDurationUnit`, `iPlannedCost`, `iCost`, `iPriority` ' .			  
			') ' .
		'VALUES ' . 
			'(\'\', \'' . 
				$babDB->db_escape_string($aParams['iIdProject']) . '\', \'' . 
				$babDB->db_escape_string($aParams['sTaskNumber']) . '\', \'' . 
				$babDB->db_escape_string($aParams['sDescription']) . '\', \'' . 
				$babDB->db_escape_string($aParams['iIdCategory']) . '\', \'' . 
				$babDB->db_escape_string($aParams['iClass']) . '\', \'' . 
				$babDB->db_escape_string($aParams['iParticipationStatus']) . '\', \'' . 
				$babDB->db_escape_string($aParams['iIsLinked']) . '\', \'' . 
				$babDB->db_escape_string($aParams['iIdCalEvent']) . '\', \'' . 
				$babDB->db_escape_string($aParams['sHashCalEvent']) . '\', \'' . 
				$babDB->db_escape_string($aParams['iDuration']) . '\', \'' . 
				$babDB->db_escape_string($aParams['iDurationUnit']) . '\', \'' . 
				$babDB->db_escape_string($aParams['iMajorVersion']) . '\', \'' . 
				$babDB->db_escape_string($aParams['iMinorVersion']) . '\', \'' . 
				$babDB->db_escape_string($aParams['sColor']) . '\', \'' . 
				$babDB->db_escape_string($aParams['iPosition']) . '\', \'' . 
				$babDB->db_escape_string($aParams['iCompletion']) . '\', \'' . 
				$babDB->db_escape_string($aParams['sStartDate']) . '\', \'' . 
				$babDB->db_escape_string($aParams['sEndDate']) . '\', \'' . 
				$babDB->db_escape_string($aParams['sPlannedStartDate']) . '\', \'' . 
				$babDB->db_escape_string($aParams['sPlannedEndDate']) . '\', \'' . 
				$babDB->db_escape_string($aParams['sCreated']) . '\', \'' . 
				$babDB->db_escape_string($aParams['iIdUserCreated']) . '\', \'' . 
				$babDB->db_escape_string($aParams['iIsNotified']) . '\', \'' . 
				$babDB->db_escape_string($aParams['iIdUserModified']) . '\', \'' . 
				$babDB->db_escape_string($aParams['sModified']) . '\', \'' .
				$babDB->db_escape_string($aParams['sShortDescription']) . '\', \'' .
				$babDB->db_escape_string($aParams['iPlannedTime']) . '\', \'' .
				$babDB->db_escape_string($aParams['iPlannedTimeDurationUnit']) . '\', \'' . 			  
				$babDB->db_escape_string($aParams['iTime']) . '\', \'' . 
				$babDB->db_escape_string($aParams['iTimeDurationUnit']) . '\', \'' . 			  
				$babDB->db_escape_string($aParams['iPlannedCost']) . '\', \'' . 
				$babDB->db_escape_string($aParams['iCost']) . '\', \'' . 
				$babDB->db_escape_string($aParams['iPriority']) .
			'\')'; 

	//bab_debug($query);
	$res = $babDB->db_query($query);
	if(false != $res)
	{
		return $babDB->db_insert_id();
	}
	return false;
}


function bab_getTask($iIdTask, &$aTask)
{
	global $babDB;

	$aTask = array();	

	$query = 
		'SELECT ' .
			't.id, ' . 
			't.idProject, ' .
			't.taskNumber, ' .
			't.description, ' .
			't.shortDescription, ' .
			't.idCategory, ' .
			't.created, ' .
			't.modified, ' .
			't.idUserCreated, ' .
			't.idUserModified, ' .
			't.class, ' .
			't.participationStatus, ' .
			't.isLinked, ' .
			't.idCalEvent, ' .
			't.hashCalEvent, ' .
			't.duration, ' .
			't.iDurationUnit, ' .
			't.majorVersion, ' .
			't.minorVersion, ' .
			't.color, ' .
			't.position, ' .
			't.completion, ' .
			't.plannedStartDate, ' .
			't.plannedEndDate, ' .
			't.startDate, ' .
			't.endDate, ' .
			't.isNotified, ' .
			't.iPlannedTime, ' .			  
			't.iPlannedTimeDurationUnit, ' .			  
			't.iTime, ' .			  
			't.iTimeDurationUnit, ' .			  
			't.iPlannedCost, ' .			  
			't.iCost, ' .			  
			't.iPriority, ' .
			'ti.idOwner ' .
		'FROM ' .
			BAB_TSKMGR_TASKS_TBL . ' t ' .
		'LEFT JOIN ' .
			BAB_TSKMGR_TASKS_INFO_TBL . ' ti ON ti.idTask = t.id ' .
		'WHERE ' . 
			't.id = \'' . $babDB->db_escape_string($iIdTask) . '\'';
			
//	echo $query . '<br />';
//	bab_debug($query);
	
	$result = $babDB->db_query($query);
	$iNumRows = $babDB->db_num_rows($result);
	$iIndex = 0;
	
	if($iIndex < $iNumRows && false != ($datas = $babDB->db_fetch_assoc($result)))
	{
		$aTask = array('id' => $datas['id'], 'iIdProject' =>  $datas['idProject'], 
			'sTaskNumber' => $datas['taskNumber'], 'sDescription' => $datas['description'], 
			'iIdCategory' => $datas['idCategory'], 'sCreated' => $datas['created'], 
			'sModified' => $datas['modified'], 'iIdUserCreated' => $datas['idUserCreated'], 
			'iIdUserModified' => $datas['idUserModified'], 'iClass' => $datas['class'], 
			'iParticipationStatus' => $datas['participationStatus'],
			'iIsLinked' => $datas['isLinked'], 'iDuration' => $datas['duration'],
			'iIdCalEvent' => $datas['idCalEvent'], 'sHashCalEvent' => $datas['hashCalEvent'], 
			'iDurationUnit' => $datas['iDurationUnit'], 'iMajorVersion' => $datas['majorVersion'], 
			'iMinorVersion' => $datas['minorVersion'], 'sColor' => $datas['color'], 
			'iPosition' => $datas['position'], 'iCompletion' => $datas['completion'],
			'sPlannedStartDate' => $datas['plannedStartDate'], 'sStartDate' => $datas['startDate'],
			'sPlannedEndDate' => $datas['plannedEndDate'], 'sEndDate' => $datas['endDate'],
			'iIsNotified' => $datas['isNotified'], 'iIdOwner' => $datas['idOwner'],
			'sShortDescription' => $datas['shortDescription'], 'iPlannedTime' => $datas['iPlannedTime'], 
			'iPlannedTimeDurationUnit' => $datas['iPlannedTimeDurationUnit'], 			  
			'iTime' => $datas['iTime'], 'iTimeDurationUnit' => $datas['iTimeDurationUnit'], 			  
			'iPlannedCost' => $datas['iPlannedCost'], 'iCost' => $datas['iCost'],
			'iPriority' => $datas['iPriority']);
		return true;
	}
	return false;
}


/* Hack pour l'affichage du gantt, ne pas utiliser. Lorsque j'aurai du temp je referai une belle api*/
function bab_getTaskForGantt($iIdTask, &$aTask)
{
	global $babDB;
	
	$query = 
		'SELECT ' . 
			'IFNULL(ps.id, 0) iIdProjectSpace, ' .
			'IFNULL(ps.name, \'\') sProjectSpaceName, ' .
			'IFNULL(p.id, 0) iIdProject, ' .
			'IFNULL(p.name, \'\') sProjectName, ' .
			't.id iIdTask, ' .
			't.taskNumber sTaskNumber, ' .
			't.description sDescription, ' .
			't.shortDescription sShortDescription, ' .
			't.class iClass, ' .
			't.iPriority iPriority, ' .
		'CASE t.class ' .
			'WHEN \'' . BAB_TM_CHECKPOINT . '\' THEN \'ganttCheckpoint\' ' . 
			'WHEN \'' . BAB_TM_TODO . '\' THEN \'ganttToDo\' ' .
			'WHEN \'' . BAB_TM_TASK . '\' THEN \'ganttTask\' ' .
			'ELSE \'\' ' .
		'END AS sClassName, ' .
		'CASE t.class ' .			
			'WHEN \'' . BAB_TM_TASK . '\' THEN \'' . bab_translate("Task") . '\' ' .
			'WHEN \'' . BAB_TM_CHECKPOINT . '\' THEN \'' . bab_translate("Checkpoint") . '\' ' .
			'WHEN \'' . BAB_TM_TODO . '\' THEN \'' . bab_translate("ToDo") . '\' ' .
			'ELSE \'???\' ' .
		'END AS sClass, ' .
			't.completion iCompletion, ' .
			't.startDate startDate, ' .
			't.endDate endDate, ' .
			't.plannedStartDate plannedStartDate, ' .
			't.plannedEndDate plannedEndDate, ' .
			't.duration iDuration, ' .
			't.iDurationUnit iDurationUnit, ' .
			'ti.idOwner idOwner, ' .
			'cat.id iIdCategory, ' .
			'cat.name sCategoryName, ' .
			'IFNULL(cat.bgColor, \'\' ) sBgColor, ' .
			'IFNULL(cat.color, \'\' ) sColor, ' .
			'IFNULL(lt.idPredecessorTask, 0) iIdPredecessorTask, ' .
			'IFNULL(lt.linkType, -1) iLinkType ' .
		'FROM ' . 
			BAB_TSKMGR_TASKS_INFO_TBL . ' ti, ' .
			BAB_TSKMGR_TASKS_TBL . ' t ' .
		'LEFT JOIN ' . 
			BAB_TSKMGR_CATEGORIES_TBL . ' cat ON cat.id = t.idCategory ' .
		'LEFT JOIN ' . 
			BAB_TSKMGR_PROJECTS_TBL . ' p ON p.id = t.idProject ' .
		'LEFT JOIN ' . 
			BAB_TSKMGR_PROJECTS_SPACES_TBL . ' ps ON ps.id = p.idProjectSpace ' .
		'LEFT JOIN ' . 
			BAB_TSKMGR_LINKED_TASKS_TBL . ' lt ON lt.idTask = t.id ' .
		'WHERE ' . 
			't.id = \'' . $babDB->db_escape_string($iIdTask) . '\'';
	
	$result = $babDB->db_query($query);
	$iNumRows = $babDB->db_num_rows($result);
	$iIndex = 0;
	
	if($iIndex < $iNumRows && false != ($datas = $babDB->db_fetch_assoc($result)))
	{
		$aTask = array('iIdTask' => $datas['iIdTask'], 'iIdProjectSpace' =>  $datas['iIdProjectSpace'], 
			'sProjectSpaceName' => $datas['sProjectSpaceName'], 'iIdProject' => $datas['iIdProject'], 
			'sProjectName' => $datas['sProjectName'], 'sTaskNumber' => $datas['sTaskNumber'], 
			'sDescription' => $datas['sDescription'], 'sShortDescription' => $datas['sShortDescription'], 
			'iClass' => $datas['iClass'], 'iPriority' => $datas['iPriority'], 
			'sClassName' => $datas['sClassName'], 'sClass' => $datas['sClass'], 
			'iCompletion' => $datas['iCompletion'], 'startDate' => $datas['startDate'], 
			'endDate' => $datas['endDate'], 'plannedStartDate' => $datas['plannedStartDate'], 
			'plannedEndDate' => $datas['plannedEndDate'], 'iDuration' => $datas['iDuration'], 
			'iDurationUnit' => $datas['iDurationUnit'], 'idOwner' => $datas['idOwner'], 
			'iIdCategory' => $datas['iIdCategory'], 'sCategoryName' => $datas['sCategoryName'], 
			'sBgColor' => $datas['sBgColor'], 'sColor' => $datas['sColor'], 
			'iIdPredecessorTask' => $datas['iIdPredecessorTask'], 'iLinkType' => $datas['iLinkType']);
		return true;
	}
	return false;
}


function bab_updateTask($iIdTask, $aParams)
{
	global $babDB;
	
	$query = 
		'UPDATE ' . 
			BAB_TSKMGR_TASKS_TBL . ' ' .
		'SET ' . ' ' .
			'`taskNumber` = \'' . $babDB->db_escape_string($aParams['sTaskNumber']) . '\', ' .
			'`description` = \'' . $babDB->db_escape_string($aParams['sDescription']) . '\', ' .
			'`idCategory` = \'' . $babDB->db_escape_string($aParams['iIdCategory']) . '\', ' .
			'`class` = \'' . $babDB->db_escape_string($aParams['iClass']) . '\', ' .
			'`participationStatus` = \'' . $babDB->db_escape_string($aParams['iParticipationStatus']) . '\', ' .
			'`isLinked` = \'' . $babDB->db_escape_string($aParams['iIsLinked']) . '\', ' .
			'`duration` = \'' . $babDB->db_escape_string($aParams['iDuration']) . '\', ' .
			'`iDurationUnit` = \'' . $babDB->db_escape_string($aParams['iDurationUnit']) . '\', ' .
			'`majorVersion` = \'' . $babDB->db_escape_string($aParams['iMajorVersion']) . '\', ' .
			'`minorVersion` = \'' . $babDB->db_escape_string($aParams['iMinorVersion']) . '\', ' .
			'`color` = \'' . $babDB->db_escape_string($aParams['sColor']) . '\', ' .
			'`completion` = \'' . $babDB->db_escape_string($aParams['iCompletion']) . '\', ' .
			'`startDate` = \'' . $babDB->db_escape_string($aParams['sStartDate']) . '\', ' .
			'`endDate` = \'' . $babDB->db_escape_string($aParams['sEndDate']) . '\', ' .
			'`plannedStartDate` = \'' . $babDB->db_escape_string($aParams['sPlannedStartDate']) . '\', ' .
			'`plannedEndDate` = \'' . $babDB->db_escape_string($aParams['sPlannedEndDate']) . '\', ' .
			'`idUserModified` = \'' . $babDB->db_escape_string($aParams['iIdUserModified']) . '\', ' .
			'`modified` = \'' . $babDB->db_escape_string($aParams['sModified']) . '\', ' .
			'`shortDescription` = \'' . $babDB->db_escape_string($aParams['sShortDescription']) . '\', ' .
			'`iPlannedTime` = \'' . $babDB->db_escape_string($aParams['iPlannedTime']) . '\', ' .
			'`iPlannedTimeDurationUnit` = \'' . $babDB->db_escape_string($aParams['iPlannedTimeDurationUnit']) . '\', ' . 			  
			'`iTime` = \'' . $babDB->db_escape_string($aParams['iTime']) . '\', ' . 
			'`iTimeDurationUnit` = \'' . $babDB->db_escape_string($aParams['iTimeDurationUnit']) . '\', ' . 			  
			'`iPlannedCost` = \'' . $babDB->db_escape_string($aParams['iPlannedCost']) . '\', ' . 
			'`iCost` = \'' . $babDB->db_escape_string($aParams['iCost']) . '\', ' .
			'`iPriority` = \'' . $babDB->db_escape_string($aParams['iPriority']) . '\' ' .
		'WHERE ' . 
			'id = \'' . $babDB->db_escape_string($iIdTask) . '\'';
			
//	bab_debug($query);
	if(true === $babDB->db_query($query))
	{
		return true;
	}
	return false;
}


function bab_deleteTask($iIdProjectSpace, $iIdProject, $iIdTask)
{
	bab_tskmgr_deleteTaskAdditionalFields($iIdProjectSpace, $iIdProject, $iIdTask);
	bab_deleteTaskLinks($iIdTask);
	bab_deleteTaskResponsibles($iIdTask);

	global $babDB;
	$query = 
		'DELETE FROM ' . 
			BAB_TSKMGR_TASKS_INFO_TBL . ' ' .
		'WHERE ' .
			'idTask = \'' . $babDB->db_escape_string($iIdTask) . '\'';
	$babDB->db_query($query);

	$query = 
		'DELETE FROM ' . 
			BAB_TSKMGR_TASKS_TBL . ' ' .
		'WHERE ' .
			'id = \'' . $babDB->db_escape_string($iIdTask) . '\'';
	$babDB->db_query($query);

	$query = 
		'DELETE FROM ' . 
			BAB_TSKMGR_TASKS_COMMENTS_TBL . ' ' .
		'WHERE ' .
			'idTask = \'' . $babDB->db_escape_string($iIdTask) . '\'';
	$babDB->db_query($query);
}

function bab_deleteAllTask($iIdProjectSpace, $iIdProject)
{
	global $babDB;
	
	$query = 
		'SELECT ' .
			'id ' .
		'FROM ' .
			BAB_TSKMGR_TASKS_TBL . ' ' .
		'WHERE ' .
			'idProject = \'' . $babDB->db_escape_string($iIdProject) . '\'';
	
	$result = $babDB->db_query($query);
	$iNumRows = $babDB->db_num_rows($result);
	$iIndex = 0;
	
	while($iIndex < $iNumRows && false != ($data = $babDB->db_fetch_assoc($result)))
	{
		bab_tskmgr_deleteTaskAdditionalFields($iIdProjectSpace, $iIdProject, $data['id']);
		bab_deleteTaskLinks($data['id']);
		bab_deleteTaskResponsibles($data['id']);

		$query = 
			'DELETE FROM ' . 
				BAB_TSKMGR_TASKS_INFO_TBL . ' ' .
			'WHERE ' .
				'idTask = \'' . $babDB->db_escape_string($data['id']) . '\'';
		$babDB->db_query($query);
		
		$iIndex++;
	}

	$query = 
		'DELETE FROM ' . 
			BAB_TSKMGR_TASKS_COMMENTS_TBL . ' ' .
		'WHERE ' .
			'idProject = \'' . $babDB->db_escape_string($iIdProject) . '\'';
	$babDB->db_query($query);

	$query = 
		'DELETE FROM ' . 
			BAB_TSKMGR_TASKS_TBL . ' ' .
		'WHERE ' .
			'idProject = \'' . $babDB->db_escape_string($iIdProject) . '\'';
	$babDB->db_query($query);
}

function bab_setTaskLinks($iIdTask, $aPredecessors)
{
	if(is_array($aPredecessors) && count($aPredecessors) > 0)
	{
		global $babDB;
		foreach($aPredecessors as $key => $aPredecessor)
		{
			$query = 
				'INSERT INTO ' . BAB_TSKMGR_LINKED_TASKS_TBL . ' ' .
					'(' .
						'`id`, ' .
						'`idTask`, `idPredecessorTask`, `linkType`' .
					') ' .
				'VALUES ' . 
					'(\'\', \'' . 
						$babDB->db_escape_string($iIdTask) . '\', \'' . 
						$babDB->db_escape_string($aPredecessor['iIdPredecessorTask']) . '\', \'' . 
						$babDB->db_escape_string($aPredecessor['iLinkType']) . 
					'\')'; 
			
			//bab_debug($query);
			$babDB->db_query($query);
		}
	}
}

function bab_getLinkedTaskCount($iIdTask, &$iCount)
{
	global $babDB;
	
	$iCount = 0;
	
	$query = 
		'SELECT ' .
			'count(DISTINCT id) iCount ' .
		'FROM ' .
			BAB_TSKMGR_LINKED_TASKS_TBL . ' ' .
		'WHERE ' .
			'idPredecessorTask = \'' . $babDB->db_escape_string($iIdTask) . '\'';
			
	//bab_debug($query);
	$result = $babDB->db_query($query);
	
	if(false != ($data = $babDB->db_fetch_assoc($result)))
	{
		$iCount = $data['iCount'];
	}	
}

function bab_deleteTaskLinks($iIdTask)
{
	global $babDB;
	$query = 
		'DELETE FROM ' . 
			BAB_TSKMGR_LINKED_TASKS_TBL . ' ' .
		'WHERE ' .
			'idTask = \'' . $babDB->db_escape_string($iIdTask) . '\'';
			
	//bab_debug($query);
	$babDB->db_query($query);
}

function bab_deleteTaskResponsibles($iIdTask)
{
	global $babDB;
	$query = 
		'DELETE FROM ' . 
			BAB_TSKMGR_TASKS_RESPONSIBLES_TBL . ' ' .
		'WHERE ' .
			'idTask = \'' . $babDB->db_escape_string($iIdTask) . '\'';
	
	//bab_debug($query);
	$babDB->db_query($query);
}

function bab_selectTasksList($iIdProject, $iLenght = 50)
{
	global $babDB;
	
	$query = 
		'SELECT ' .
			't.id, ' . 
			't.taskNumber, ' . 
			'IF(LENGTH(t.description) > \'' . $babDB->db_escape_string($iLenght) . '\', ' . 
				'CONCAT(LEFT(t.description, \'' . $babDB->db_escape_string($iLenght) . '\'), \'...\'), t.description) description, ' .
			't.created, ' .
			't.shortDescription ' .
		'FROM ' .
			BAB_TSKMGR_TASKS_TBL . ' t ' .
		'WHERE ' . 
			't.idProject =\'' . $babDB->db_escape_string($iIdProject) . '\' ' .
		'ORDER BY t.position';
	
	//bab_debug($query);
	return $babDB->db_query($query);
}

function bab_selectPersonnalTasksList($iLenght = 50)
{
	global $babDB;
	
	$query = 
		'SELECT ' .
			't.id, ' . 
			't.taskNumber, ' . 
			'IF(LENGTH(t.description) > \'' . $babDB->db_escape_string($iLenght) . '\', ' . 
				'CONCAT(LEFT(t.description, \'' . $babDB->db_escape_string($iLenght) . '\'), \'...\'), t.description) description, ' .
			't.created, ' .
			't.shortDescription ' .
		'FROM ' .
			BAB_TSKMGR_TASKS_INFO_TBL . ' ti, ' . 
			BAB_TSKMGR_TASKS_TBL . ' t ' .
		'WHERE ' . 
			'ti.idOwner =\'' . $babDB->db_escape_string($GLOBALS['BAB_SESS_USERID']) . '\' AND ' .
			'ti.isPersonnal =\'' . $babDB->db_escape_string(BAB_TM_YES) . '\' AND ' .
			't.id = ti.idTask ' .
		'ORDER BY t.position';
	
	//bab_debug($query);
	return $babDB->db_query($query);
}

function bab_getNextTaskNumber($iIdProject, $iTasksNumerotation, &$sTaskNumber)
{
	bab_getNextTaskPosition($iIdProject, $iPosition);
	
	switch($iTasksNumerotation)
	{
		case BAB_TM_MANUAL:
			$sTaskNumber = sprintf('%05s', $iPosition);
			break;
		case BAB_TM_SEQUENTIAL:
			$sTaskNumber = sprintf('%05s', $iPosition);
			break;
		case BAB_TM_YEAR_SEQUENTIAL:
			$sTaskNumber = date('y') . sprintf('%05s', $iPosition);
			break;
		case BAB_TM_YEAR_MONTH_SEQUENTIAL:
			$sTaskNumber = date('ym') . sprintf('%05s', $iPosition);
			break;
	}
}

function bab_getNextTaskPosition($iIdProject, &$iPosition)
{	
	global $babDB;

	//Personnal task
	if(0 == $iIdProject)
	{
		$query = 
			'SELECT ' .
				'IFNULL(MAX(ti.idTask), 0) idTask ' .
			'FROM ' . 
				BAB_TSKMGR_TASKS_INFO_TBL . ' ti ' .
			'WHERE ' . 
				'ti.idOwner =\'' . $babDB->db_escape_string($GLOBALS['BAB_SESS_USERID']) . '\' AND ' .
				'ti.isPersonnal =\'' . $babDB->db_escape_string(BAB_TM_YES) . '\'';
				
		//bab_debug($query);
		$res = $babDB->db_query($query);
		if(false != $res && $babDB->db_num_rows($res) > 0)
		{
			$data = $babDB->db_fetch_array($res);
			if(0 == $data['idTask'])
			{
				$iPosition = 1;
				return;
			}
			
			$query = 
				'SELECT ' .
					'position ' .
				'FROM ' . 
					BAB_TSKMGR_TASKS_TBL . ' ' .
				'WHERE ' . 
					'id=\'' . $babDB->db_escape_string($data['idTask']) . '\'';
		
			//bab_debug($query);
			$res = $babDB->db_query($query);
		
			if(false != $res && $babDB->db_num_rows($res) > 0)
			{
				$data = $babDB->db_fetch_array($res);
		
				if(false != $data)
				{
					$iPosition = (int) $data['position'] + 1;
				}
			}
		}
		else 
		{
			$iPosition = 1;
		}
	}
	else
	{
		$iPosition = 0;
	
		$query = 
			'SELECT ' .
				'IFNULL(MAX(position), 0) position ' .
			'FROM ' . 
				BAB_TSKMGR_TASKS_TBL . ' ' .
			'WHERE ' . 
				'idProject=\'' . $babDB->db_escape_string($iIdProject) . '\'';
	
		//bab_debug($query);
		$res = $babDB->db_query($query);
	
		if(false != $res && $babDB->db_num_rows($res) > 0)
		{
			$data = $babDB->db_fetch_array($res);
	
			if(false != $data)
			{
				$iPosition = (int) $data['position'] + 1;
			}
		}
	}
}

/**
 * Fills the $aTaskResponsible array with the list of task responsible users for project $iIdProject.
 * 
 * The array is sorted alphabetically on user names.
 *
 * @param int $iIdProject
 * @param array $aTaskResponsible
 */
function bab_getAvailableTaskResponsibles($iIdProject, &$aTaskResponsible)
{
	if(!function_exists('bab_compareTaskResponsibles'))
	{
		function bab_compareTaskResponsibles($r1, $r2)
		{
			return strcmp($r1['name'], $r2['name']);
		}
	}

	$aTaskResponsible = array();
	
	$aIdObject = bab_getGroupsAccess(BAB_TSKMGR_TASK_RESPONSIBLE_GROUPS_TBL, $iIdProject);

	if(count($aIdObject) > 0)
	{
		foreach($aIdObject as $key => $iIdGroup)
		{
			$aMembers = bab_getGroupsMembers($iIdGroup);
			
			if(is_array($aMembers) && count($aMembers) > 0)
			{
				foreach($aMembers as $k => $aMember)
				{
					$aTaskResponsible[$aMember['id']] = array('id' => $aMember['id'], 'name' => bab_getUserName($aMember['id']));
				}
			}
		}
	}
	uasort($aTaskResponsible, 'bab_compareTaskResponsibles');
}


function bab_getTaskResponsibles($iIdTask, &$aTaskResponsible)
{
	global $babDB;
	
	$aTaskResponsible = array();

	$query = 
		'SELECT ' .
			'idResponsible ' . 
		'FROM ' .
			BAB_TSKMGR_TASKS_RESPONSIBLES_TBL . ' ' .
		'WHERE ' . 
			'idTask =\'' . $babDB->db_escape_string($iIdTask) . '\'';
	
//	bab_debug($query);
	$result = $babDB->db_query($query);
	$iNumRows = $babDB->db_num_rows($result);
	$iIndex = 0;
	
	if($iIndex < $iNumRows && false != ($datas = $babDB->db_fetch_assoc($result)))
	{
		$iIndex++;
		$aTaskResponsible[$datas['idResponsible']] = array('id' => $datas['idResponsible'], 
			'name' => bab_getUserName($datas['idResponsible']), 
			'email' => bab_getUserEmail($datas['idResponsible']));
	}
}

function bab_setTaskResponsibles($iIdTask, $aTaskResponsibles)
{
	if(is_array($aTaskResponsibles) && count($aTaskResponsibles) > 0)
	{
		global $babDB;
		foreach($aTaskResponsibles as $key => $iIdResponsible)
		{
			$query = 
				'INSERT INTO ' . BAB_TSKMGR_TASKS_RESPONSIBLES_TBL . ' ' .
					'(' .
						'`id`, ' .
						'`idTask`, `idResponsible`' .
					') ' .
				'VALUES ' . 
					'(\'\', \'' . 
						$babDB->db_escape_string($iIdTask) . '\', \'' . 
						$babDB->db_escape_string($iIdResponsible) . 
					'\')'; 
			$babDB->db_query($query);
		}
	}
}

function bab_selectTaskCommentary($iIdTask, $iLenght = 50)
{
	global $babDB;
	
	$query = 
		'SELECT ' .
			'id, ' . 
			'IF(LENGTH(commentary) > \'' . $babDB->db_escape_string($iLenght) . '\', ' . 
				'CONCAT(LEFT(commentary, \'' . $babDB->db_escape_string($iLenght) . '\'), \'...\'), commentary) commentary, ' .
			'created ' .
		'FROM ' .
			BAB_TSKMGR_TASKS_COMMENTS_TBL . ' ' .
		'WHERE ' . 
			'idTask =\'' . $babDB->db_escape_string($iIdTask) . '\'';
	
	//bab_debug($query);
	return $babDB->db_query($query);
}

function bab_createTaskCommentary($iIdProject, $iIdTask, $sCommentary)
{
	global $babDB;
	
	$query = 
		'INSERT INTO ' . BAB_TSKMGR_TASKS_COMMENTS_TBL . ' ' .
			'(' .
				'`id`, `idTask`, ' .
				'`idProject`, `commentary`, `created`, `idUserCreated`' .
			') ' .
		'VALUES ' . 
			'(\'\', \'' . 
				$babDB->db_escape_string($iIdTask) . '\', \'' . 
				$babDB->db_escape_string($iIdProject) . '\', \'' . 
				$babDB->db_escape_string($sCommentary) . '\', \'' . 
				$babDB->db_escape_string(date("Y-m-d H:i:s")) . '\', \'' . 
				$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID']) . 
			'\')'; 

	//bab_debug($query);
	return $babDB->db_query($query);
}

function bab_updateTaskCommentary($iIdCommentary, $sCommentary)
{
	global $babDB;

	$query = 
		'UPDATE ' . 
			BAB_TSKMGR_TASKS_COMMENTS_TBL . ' ' .
		'SET ' . ' ' .
				'`commentary` = \'' . $babDB->db_escape_string($sCommentary) . '\', ' .
				'`modified` = \'' . $babDB->db_escape_string(date("Y-m-d H:i:s")) . '\', ' .
				'`idUserModified` = \'' . $babDB->db_escape_string($GLOBALS['BAB_SESS_USERID']) . '\' ' .
		'WHERE ' . 
			'`id` = \'' . $babDB->db_escape_string($iIdCommentary) . '\'';

	//bab_debug($query);
	return $babDB->db_query($query);
}

function bab_deleteTaskCommentary($iIdCommentary)
{
	global $babDB;
	$query = 
		'DELETE FROM '	. 
			BAB_TSKMGR_TASKS_COMMENTS_TBL . ' ' .
		'WHERE ' . 
			'id = \'' . $babDB->db_escape_string($iIdCommentary) . '\'';
	$babDB->db_query($query);
}

function bab_getTaskCommentary($iIdCommentary, &$sCommentary)
{
	global $babDB;
	
	$sCommentary = '';
	
	$query = 
		'SELECT ' .
			'id, ' . 
			'commentary ' .
		'FROM ' .
			BAB_TSKMGR_TASKS_COMMENTS_TBL . ' ' .
		'WHERE ' . 
			'id =\'' . $babDB->db_escape_string($iIdCommentary) . '\'';
	
	//bab_debug($query);
	$result = $babDB->db_query($query);
	
	if(false != ($datas = $babDB->db_fetch_assoc($result)))
	{
		$sCommentary = $datas['commentary'];
		return true;
	}
	return false;
}

function bab_isTaskNumberUsed($iIdProject, $iIdTask, $sTaskNumber)
{
	global $babDB;
	
	$sIdTask = '';
	if(0 != $iIdTask)
	{
		$sIdTask = ' AND id <> \'' . $babDB->db_escape_string($iIdTask) . '\'';
	}

	$query = 
		'SELECT ' . 
			'id, ' .
			'taskNumber ' .
		'FROM ' . 
			BAB_TSKMGR_TASKS_TBL . ' ' .
		'WHERE ' . 
			'idProject = \'' . $babDB->db_escape_string($iIdProject) . '\' AND ' .
			'taskNumber LIKE \'' . $babDB->db_escape_like($sTaskNumber) . '\'' .
			$sIdTask;
		
	//bab_debug($query);
	
	$result = $babDB->db_query($query);
	return (false != $result && 0 == $babDB->db_num_rows($result));
}

function bab_selectLinkableTask($iIdProject, $iIdTask)
{
	global $babDB;

	$sIdTask = '';
	if(0 != $iIdTask)
	{
		if(0 == $iIdProject)
		{
			$sIdTask = ' AND idTask <> \'' . $babDB->db_escape_string($iIdTask) . '\'';
		}
		else
		{
			$sIdTask = ' AND id <> \'' . $babDB->db_escape_string($iIdTask) . '\'';
		}
	}

	$sIdOwner = '';
	if(0 == $iIdProject)
	{
		$query = 
			'SELECT ' . 
				'idTask ' .
			'FROM ' . 
				BAB_TSKMGR_TASKS_INFO_TBL . ' ' .
			'WHERE ' . 
				'idOwner =\'' . $babDB->db_escape_string($GLOBALS['BAB_SESS_USERID']) . '\'' .
				$sIdTask;
				
		//bab_debug($query);
		$result = $babDB->db_query($query);
		$iNumRows = $babDB->db_num_rows($result);
		$iIndex = 0;
		
		$aTask = array();
		while($iIndex < $iNumRows && false != ($datas = $babDB->db_fetch_assoc($result)))
		{
			$iIndex++;
			$aTask[] = $datas['idTask'];
		}
		$sIdTask = 'AND id IN (\'' . implode('\',\'', $aTask) . '\')';
	}
	
	$query = 
		'SELECT ' . 
			'id, ' .
			'taskNumber, ' .
			'shortDescription, ' .
//			'IF(startDate = \'0000-00-00 00:00:00\', 0, ' .
//				'IF(startDate > now(), 0, 1)) isStarted ' .
			'0 AS isStarted ' .
		'FROM ' . 
			BAB_TSKMGR_TASKS_TBL . ' ' .
		'WHERE ' . 
			'idProject = \'' . $babDB->db_escape_string($iIdProject) . '\' AND ' .
			'class =\'' . $babDB->db_escape_string(BAB_TM_TASK) . '\' AND ' .
			'participationStatus <> \'' . $babDB->db_escape_string(BAB_TM_ENDED) . '\'' . ' ' . 
			$sIdTask . ' ' .
		'ORDER BY position';

	//bab_debug($query);
	
	return $babDB->db_query($query);
}

function bab_getLinkedTasks($iIdTask, &$aLinkedTasks)
{
	global $babDB;
	
	$aLinkedTasks = array();
	
	$query = 
		'SELECT ' . 
			'idPredecessorTask, ' .
			'linkType ' .
		'FROM ' . 
			BAB_TSKMGR_LINKED_TASKS_TBL . ' ' .
		'WHERE ' . 
			'idTask = \'' . $babDB->db_escape_string($iIdTask) . '\'';

	//bab_debug($query);
	

	$result = $babDB->db_query($query);
	$iNumRows = $babDB->db_num_rows($result);
	$iIndex = 0;
	
	while($iIndex < $iNumRows && false != ($datas = $babDB->db_fetch_assoc($result)))
	{
		$aLinkedTasks[] = array('iIdPredecessorTask' => $datas['idPredecessorTask'], 'iLinkType' => $datas['linkType']);
		$iIndex++;
	}
}


function bab_selectTaskQuery($aFilters, $aOrder = array())
{
	global $babDB;

	$query = 
		'SELECT ' . 
			'IFNULL(t4.id, 0) iIdProjectSpace, ' .
			'IFNULL(t4.name, \'\') sProjectSpaceName, ' .
			'IFNULL(t3.id, 0) iIdProject, ' .
			'IFNULL(t3.name, \'\') sProjectName, ' .
			't0.id iIdTask, ' .
			't0.taskNumber sTaskNumber, ' .
			't0.shortDescription sShortDescription, ' .
			't0.description sDescription, ' .
			't0.class iClass, ' .
		'CASE t0.class ' .
			'WHEN \'' . BAB_TM_CHECKPOINT . '\' THEN \'ganttCheckpoint\' ' . 
			'WHEN \'' . BAB_TM_TODO . '\' THEN \'ganttToDo\' ' .
			'ELSE \'\' ' .
		'END AS sAdditionnalClass, ' .
		'CASE t0.class ' .			
			'WHEN \'' . BAB_TM_TASK . '\' THEN \'' . bab_translate("Task") . '\' ' .
			'WHEN \'' . BAB_TM_CHECKPOINT . '\' THEN \'' . bab_translate("Checkpoint") . '\' ' .
			'WHEN \'' . BAB_TM_TODO . '\' THEN \'' . bab_translate("ToDo") . '\' ' .
			'ELSE \'???\' ' .
		'END AS sClass, ' .
			't0.completion iCompletion, ' .
			't0.startDate startDate, ' .
			't0.endDate endDate, ' .
			't0.plannedStartDate plannedStartDate, ' .
			't0.plannedEndDate plannedEndDate, ' .
			't0.iPlannedTime iPlannedTime, ' .
			't0.iPlannedTimeDurationUnit iPlannedTimeDurationUnit, ' . 			  
			't0.iTime iTime, ' . 
			't0.iTimeDurationUnit iTimeDurationUnit, ' . 			  
			't0.iPlannedCost iPlannedCost, ' . 
			't0.iCost iCost, ' .
			't0.iPriority iPriority, ' .
			't1.idOwner idOwner, ' .
			't2.id iIdCategory, ' .
			't2.name sCategoryName, ' .
			'IFNULL(t2.bgColor, \'\' ) sBgColor, ' .
			'IFNULL(t2.color, \'\' ) sColor ' .
		'FROM ' . 
			BAB_TSKMGR_TASKS_INFO_TBL . ' t1, ' .
			BAB_TSKMGR_TASKS_TBL . ' t0 ' .
		'LEFT JOIN ' . 
			BAB_TSKMGR_CATEGORIES_TBL . ' t2 ON t2.id = t0.idCategory ' .
		'LEFT JOIN ' . 
			BAB_TSKMGR_PROJECTS_TBL . ' t3 ON t3.id = t0.idProject ' .
		'LEFT JOIN ' . 
			BAB_TSKMGR_PROJECTS_SPACES_TBL . ' t4 ON t4.id = t3.idProjectSpace ' .
		'WHERE ' . 
			't0.id = t1.idTask ';


	if(isset($aFilters['iIdProject']) && 0 !== (int) $aFilters['iIdProject'])
	{
		$query .= 'AND t0.idProject = ' . $babDB->quote((int) $aFilters['iIdProject']) . ' ';
	}

	if(isset($aFilters['iIdOwner']))
	{
		$query .= 'AND t1.idOwner = ' . $babDB->quote((int) $aFilters['iIdOwner']) . ' ';
	}

	if(isset($aFilters['sStartDate']))
	{
		$query .= 'AND t0.startDate >= ' . $babDB->quote($aFilters['sStartDate']) . ' ';
	}

	if(isset($aFilters['sEndDate']))
	{
		$query .= 'AND t0.endDate <= ' . $babDB->quote($aFilters['sEndDate']) . ' ';
	}

	if(isset($aFilters['sPlannedStartDate']))
	{
		$query .= 'AND t0.plannedStartDate >= ' . $babDB->quote($aFilters['sPlannedStartDate']) . ' ';
	}

	if(isset($aFilters['sPlannedEndDate']))
	{
		$query .= 'AND t0.plannedEndDate <= ' . $babDB->quote($aFilters['sPlannedEndDate']) . ' ';
	}

	if(isset($aFilters['iTaskClass']))
	{
		$query .= 'AND t0.class = ' . $babDB->quote((int) $aFilters['iTaskClass']) . ' ';
	}

	if(isset($aFilters['isPersonnal']))
	{
		$query .= 'AND t1.isPersonnal = ' . $babDB->quote(BAB_TM_YES) . ' ';
	}
	
	if(isset($aFilters['bIsManager']) && false === $aFilters['bIsManager'])
	{
		$query .= 'AND t0.participationStatus <> ' . $babDB->quote(BAB_TM_REFUSED) . ' ';
	}
	
	if(isset($aFilters['iCompletion']) && -1 !== (int) $aFilters['iCompletion'])
	{
		$sCompletion = '= ' . $babDB->quote('100');
		if(BAB_TM_IN_PROGRESS === (int) $aFilters['iCompletion'])
		{
			$sCompletion = '<> ' . $babDB->quote('100'); 
		}
		
		$query .= 'AND t1.completion ' . $sCompletion . ' ';
	}
	
	$query .= 
		'GROUP BY ' .
			'sProjectSpaceName ASC, sProjectName ASC, sTaskNumber ASC ';

	if(count($aOrder) > 0)
	{
		$query .= 'ORDER BY ' . $babDB->backTick($aOrder['sName']) . ' ' . $aOrder['sOrder'] . ' ';
	}

//	bab_debug($query);
//	echo $query . '<br />';
	return $query;
}


function bab_selectTaskQueryEx($aFilters, $aField, $aLeftJoin, $aWhere, $aOrder = array())
{
	global $babDB;

	$sField = '';
	$sLeftJoin = '';
	$sWhere = '';

	if(isset($aFilters['iIdProject']) && 0 !== (int) $aFilters['iIdProject'])
	{
		if(0 < count($aField))
		{
			$sField = ',' . implode(',', $aField) . ' ';
		}
	
		if(0 < count($aLeftJoin))
		{
			$sLeftJoin = implode('', $aLeftJoin) . ' ';
		}
	
		if(0 < count($aWhere))
		{
			$sWhere = implode('', $aWhere) . ' ';
		}
	}
	
	$sQuery = 
		'SELECT ' . 
			'IFNULL(t4.id, 0) iIdProjectSpace, ' .
			'IFNULL(t4.name, \'\') sProjectSpaceName, ' .
			'IFNULL(t3.id, 0) iIdProject, ' .
			'IFNULL(t3.name, \'\') sProjectName, ' .
			't0.id iIdTask, ' .
			't0.taskNumber sTaskNumber, ' .
			't0.shortDescription sShortDescription, ' .
			't0.description sDescription, ' .
			't0.class iClass, ' .
		'CASE t0.class ' .
			'WHEN \'' . BAB_TM_CHECKPOINT . '\' THEN \'ganttCheckpoint\' ' . 
			'WHEN \'' . BAB_TM_TODO . '\' THEN \'ganttToDo\' ' .
			'ELSE \'\' ' .
		'END AS sAdditionnalClass, ' .
		'CASE t0.class ' .			
			'WHEN \'' . BAB_TM_TASK . '\' THEN \'' . bab_translate("Task") . '\' ' .
			'WHEN \'' . BAB_TM_CHECKPOINT . '\' THEN \'' . bab_translate("Checkpoint") . '\' ' .
			'WHEN \'' . BAB_TM_TODO . '\' THEN \'' . bab_translate("ToDo") . '\' ' .
			'ELSE \'???\' ' .
		'END AS sClass, ' .
			't0.completion iCompletion, ' .
			't0.startDate startDate, ' .
			't0.endDate endDate, ' .
			't0.plannedStartDate plannedStartDate, ' .
			't0.plannedEndDate plannedEndDate, ' .
			't0.iPlannedTime iPlannedTime, ' .
			't0.iPlannedTimeDurationUnit iPlannedTimeDurationUnit, ' . 			  
			't0.iTime iTime, ' . 
			't0.iTimeDurationUnit iTimeDurationUnit, ' . 			  
			't0.iPlannedCost iPlannedCost, ' . 
			't0.iCost iCost, ' .
			't0.iPriority iPriority, ' .
			't0.created sCreatedDate, ' .
			't0.modified sModifiedDate, ' .
			't0.idUserCreated iIdUserCreated, ' .
			't0.idUserModified iIdUserModified, ' .
			't0.duration iDuration, ' .
			't1.idOwner idOwner, ' .
			't2.id iIdCategory, ' .
			'IFNULL(t2.name, \'\') sCategoryName, ' .
			'IFNULL(t2.bgColor, \'\' ) sBgColor, ' .
			'IFNULL(t2.color, \'\' ) sColor ' . $sField .
		'FROM ' . 
			BAB_TSKMGR_TASKS_INFO_TBL . ' t1, ' .
			BAB_TSKMGR_TASKS_TBL . ' t0 ' .
		'LEFT JOIN ' . 
			BAB_TSKMGR_CATEGORIES_TBL . ' t2 ON t2.id = t0.idCategory ' .
		'LEFT JOIN ' . 
			BAB_TSKMGR_PROJECTS_TBL . ' t3 ON t3.id = t0.idProject ' .
		'LEFT JOIN ' . 
			BAB_TSKMGR_PROJECTS_SPACES_TBL . ' t4 ON t4.id = t3.idProjectSpace ' . $sLeftJoin .
		'WHERE ' . 
			't0.id = t1.idTask ' . $sWhere;


	if(isset($aFilters['iIdProject']) && 0 !== (int) $aFilters['iIdProject'])
	{
		$sQuery .= 'AND t0.idProject = ' . $babDB->quote((int) $aFilters['iIdProject']) . ' ';
	}

	if(isset($aFilters['iIdOwner']))
	{
		$sQuery .= 'AND t1.idOwner = ' . $babDB->quote((int) $aFilters['iIdOwner']) . ' ';
	}

	if(isset($aFilters['sStartDate']))
	{
		$sQuery .= 'AND t0.startDate >= ' . $babDB->quote($aFilters['sStartDate']) . ' ';
	}

	if(isset($aFilters['sEndDate']))
	{
		$sQuery .= 'AND t0.endDate <= ' . $babDB->quote($aFilters['sEndDate']) . ' ';
	}

	if(isset($aFilters['sPlannedStartDate']))
	{
		$sQuery .= 'AND t0.plannedStartDate >= ' . $babDB->quote($aFilters['sPlannedStartDate']) . ' ';
	}

	if(isset($aFilters['sPlannedEndDate']))
	{
		$sQuery .= 'AND t0.plannedEndDate <= ' . $babDB->quote($aFilters['sPlannedEndDate']) . ' ';
	}

	if(isset($aFilters['iTaskClass']))
	{
		$sQuery .= 'AND t0.class = ' . $babDB->quote((int) $aFilters['iTaskClass']) . ' ';
	}

	if(isset($aFilters['isPersonnal']))
	{
		$sQuery .= 'AND t1.isPersonnal = ' . $babDB->quote(BAB_TM_YES) . ' ';
	}
	
	if(isset($aFilters['bIsManager']) && false === $aFilters['bIsManager'])
	{
		$sQuery .= 'AND t0.participationStatus <> ' . $babDB->quote(BAB_TM_REFUSED) . ' ';
	}
	
	if(isset($aFilters['iCompletion']) && -1 !== (int) $aFilters['iCompletion'])
	{
		$sCompletion = '= ' . $babDB->quote('100');
		if(BAB_TM_IN_PROGRESS === (int) $aFilters['iCompletion'])
		{
			$sCompletion = '<> ' . $babDB->quote('100'); 
		}
		
		$sQuery .= 'AND t1.completion ' . $sCompletion . ' ';
	}
	
	$sQuery .= 
		'GROUP BY ' .
			'sProjectSpaceName ASC, sProjectName ASC, sTaskNumber ASC ';

	if(count($aOrder) > 0)
	{
		$sQuery .= 'ORDER BY ' . $babDB->backTick($aOrder['sName']) . ' ' . $aOrder['sOrder'] . ' ';
	}

	bab_debug($sQuery);
//	echo $sQuery . '<br />';
	return $sQuery;
}

function getFirstProjectTaskDate($iIdProject)
{
	global $babDB;
	
	$aLinkedTasks = array();
	
	$query = 
		'SELECT ' . 
			'MIN(plannedStartDate) plannedStartDate ' .
		'FROM ' . 
			BAB_TSKMGR_TASKS_TBL . ' ' .
		'WHERE ' . 
			'idProject = \'' . $babDB->db_escape_string($iIdProject) . '\' AND ' .
			'plannedStartDate <> \'0000-00-00 00:00:00\'';

//	echo $query . '<br />';
	
	$result = $babDB->db_query($query);
	$iNumRows = $babDB->db_num_rows($result);
	$iIndex = 0;
	
	if($iIndex < $iNumRows && false != ($datas = $babDB->db_fetch_assoc($result)))
	{
		return $datas['plannedStartDate'];
	}
	return date("Y-m-d");
}

function bab_selectForGantt($aFilters, $aOrder = array())
{
	global $babDB;
	
	$query = 
		'SELECT ' . 
			'IFNULL(ps.id, 0) iIdProjectSpace, ' . "\n\r" .
			'IFNULL(ps.name, \'\') sProjectSpaceName, ' .
			'IFNULL(p.id, 0) iIdProject, ' .
			'IFNULL(p.name, \'\') sProjectName, ' .
			't.id iIdTask, ' .
			't.taskNumber sTaskNumber, ' .
			't.description sDescription, ' .
			't.shortDescription sShortDescription, ' .
			't.class iClass, ' .
			't.iPriority iPriority, ' .
		'CASE t.class ' .
			'WHEN \'' . BAB_TM_CHECKPOINT . '\' THEN \'ganttCheckpoint\' ' . 
			'WHEN \'' . BAB_TM_TODO . '\' THEN \'ganttToDo\' ' .
			'WHEN \'' . BAB_TM_TASK . '\' THEN \'ganttTask\' ' .
			'ELSE \'\' ' .
		'END AS sClassName, ' .
		'CASE t.class ' .			
			'WHEN \'' . BAB_TM_TASK . '\' THEN \'' . bab_translate("Task") . '\' ' .
			'WHEN \'' . BAB_TM_CHECKPOINT . '\' THEN \'' . bab_translate("Checkpoint") . '\' ' .
			'WHEN \'' . BAB_TM_TODO . '\' THEN \'' . bab_translate("ToDo") . '\' ' .
			'ELSE \'???\' ' .
		'END AS sClass, ' .
			't.completion iCompletion, ' .
			't.startDate startDate, ' .
			't.endDate endDate, ' .
			't.plannedStartDate plannedStartDate, ' .
			't.plannedEndDate plannedEndDate, ' .
			't.duration iDuration, ' .
			't.iDurationUnit iDurationUnit, ' .
			'ti.idOwner idOwner, ' .
			'cat.id iIdCategory, ' .
			'cat.name sCategoryName, ' .
			'IFNULL(cat.bgColor, \'\' ) sBgColor, ' .
			'IFNULL(cat.color, \'\' ) sColor, ' .
			'IFNULL(lt.idPredecessorTask, 0) iIdPredecessorTask, ' .
			'IFNULL(lt.linkType, -1) iLinkType ' .
		'FROM ' . 
			BAB_TSKMGR_TASKS_INFO_TBL . ' ti, ' .
			BAB_TSKMGR_TASKS_TBL . ' t ' .
		'LEFT JOIN ' . 
			BAB_TSKMGR_CATEGORIES_TBL . ' cat ON cat.id = t.idCategory ' .
		'LEFT JOIN ' . 
			BAB_TSKMGR_PROJECTS_TBL . ' p ON p.id = t.idProject ' .
		'LEFT JOIN ' . 
			BAB_TSKMGR_PROJECTS_SPACES_TBL . ' ps ON ps.id = p.idProjectSpace ' .
		'LEFT JOIN ' . 
			BAB_TSKMGR_LINKED_TASKS_TBL . ' lt ON lt.idTask = t.id ' .
		'WHERE ' . 
			't.id = ti.idTask ';
			

	if(isset($aFilters['iIdProject']))
	{
		$query .= 'AND t.idProject = ' . $babDB->quote((int) $aFilters['iIdProject']) . ' ';
	}

	if(isset($aFilters['iIdOwner']))
	{
		$query .= 'AND ti.idOwner = ' . $babDB->quote((int) $aFilters['iIdOwner']) . ' ';
	}
	if(isset($aFilters['isPersonnal']))
	{
		$query .= 'AND ti.isPersonnal = ' . $babDB->quote(BAB_TM_YES) . ' ';
	}
	$query .= 		
		'GROUP BY ' .
			'sProjectSpaceName ASC, sProjectName ASC, sTaskNumber ASC ';

//	bab_debug($query);
//	echo $query . '<br />';
	return $query;
}


function bab_getSelectQueryForGanttById($aIdTask)
{
	global $babDB;
	
	$query = 
		'SELECT ' . 
			'IFNULL(ps.id, 0) iIdProjectSpace, ' .
			'IFNULL(ps.name, \'\') sProjectSpaceName, ' .
			'IFNULL(p.id, 0) iIdProject, ' .
			'IFNULL(p.name, \'\') sProjectName, ' .
			't.id iIdTask, ' .
			't.taskNumber sTaskNumber, ' .
			't.description sDescription, ' .
			't.shortDescription sShortDescription, ' .
			't.class iClass, ' .
			't.iPriority iPriority, ' .
		'CASE t.class ' .
			'WHEN \'' . BAB_TM_CHECKPOINT . '\' THEN \'ganttCheckpoint\' ' . 
			'WHEN \'' . BAB_TM_TODO . '\' THEN \'ganttToDo\' ' .
			'WHEN \'' . BAB_TM_TASK . '\' THEN \'ganttTask\' ' .
			'ELSE \'\' ' .
		'END AS sClassName, ' .
		'CASE t.class ' .			
			'WHEN \'' . BAB_TM_TASK . '\' THEN \'' . bab_translate("Task") . '\' ' .
			'WHEN \'' . BAB_TM_CHECKPOINT . '\' THEN \'' . bab_translate("Checkpoint") . '\' ' .
			'WHEN \'' . BAB_TM_TODO . '\' THEN \'' . bab_translate("ToDo") . '\' ' .
			'ELSE \'???\' ' .
		'END AS sClass, ' .
			't.completion iCompletion, ' .
			't.startDate startDate, ' .
			't.endDate endDate, ' .
			't.plannedStartDate plannedStartDate, ' .
			't.plannedEndDate plannedEndDate, ' .
			't.duration iDuration, ' .
			't.iDurationUnit iDurationUnit, ' .
			'ti.idOwner idOwner, ' .
			'cat.id iIdCategory, ' .
			'cat.name sCategoryName, ' .
			'IFNULL(cat.bgColor, \'\' ) sBgColor, ' .
			'IFNULL(cat.color, \'\' ) sColor, ' .
			'IFNULL(lt.idPredecessorTask, 0) iIdPredecessorTask, ' .
			'IFNULL(lt.linkType, -1) iLinkType ' .
		'FROM ' . 
			BAB_TSKMGR_TASKS_INFO_TBL . ' ti, ' .
			BAB_TSKMGR_TASKS_TBL . ' t ' .
		'LEFT JOIN ' . 
			BAB_TSKMGR_CATEGORIES_TBL . ' cat ON cat.id = t.idCategory ' .
		'LEFT JOIN ' . 
			BAB_TSKMGR_PROJECTS_TBL . ' p ON p.id = t.idProject ' .
		'LEFT JOIN ' . 
			BAB_TSKMGR_PROJECTS_SPACES_TBL . ' ps ON ps.id = p.idProjectSpace ' .
		'LEFT JOIN ' . 
			BAB_TSKMGR_LINKED_TASKS_TBL . ' lt ON lt.idTask = t.id ' .
		'WHERE ' . 
			't.id IN(' . $babDB->quote($aIdTask) . ') ' .
		'GROUP BY ' .
			'sProjectSpaceName ASC, sProjectName ASC, sTaskNumber ASC ' .
		'ORDER BY ps.name ASC, p.name ASC, plannedEndDate ASC, iPriority ASC';

//	bab_debug($query);
//	echo $query . '<br />';
	return $query;
}


function bab_createTaskInfo($iIdTask, $iIdOwner, $iIsPersonnal)
{
	global $babDB;
	$query = 
		'INSERT INTO ' . BAB_TSKMGR_TASKS_INFO_TBL . ' ' .
			'(' .
				'`id`, ' .
				'`idTask`, `idOwner`, `isPersonnal` ' .
			') ' .
		'VALUES ' . 
			'(\'\', \'' . 
				$babDB->db_escape_string($iIdTask) . '\', \'' . 
				$babDB->db_escape_string($iIdOwner) . '\', \'' . 
				$babDB->db_escape_string($iIsPersonnal) . 
			'\')'; 
	
	//bab_debug($query);
	return $babDB->db_query($query);
}


function bab_updateTaskInfo($iIdTask, $iIdOwner, $iIsPersonnal)
{
	global $babDB;
	
	$query = 
		'UPDATE ' . 
			BAB_TSKMGR_TASKS_INFO_TBL . ' ' .
		'SET ' .
			'idOwner = \'' . $babDB->db_escape_string($iIdOwner) . '\', ' .
			'isPersonnal = \'' . $babDB->db_escape_string($iIsPersonnal) . '\' ' .
		'WHERE ' .
			'idTask = \'' . $babDB->db_escape_string($iIdTask) . '\'';

	//bab_debug($query);
	return $babDB->db_query($query);
}


function bab_getPersonnalTaskConfiguration($iIdUser, &$aCfg)
{
	global $babBody, $babDB;

	$aCfg = array();	

	$query = 
		'SELECT ' .
			'id, ' . 
			'idUser, ' .
			'endTaskReminder, ' .
			'tasksNumerotation, ' .
			'emailNotice ' .
		'FROM ' .
			BAB_TSKMGR_PERSONNAL_TASKS_CONFIGURATION_TBL . ' ' .
		'WHERE ' . 
			'idUser = \'' . $babDB->db_escape_string($iIdUser) . '\'';
			
	//bab_debug($query);
	$result = $babDB->db_query($query);
	$iNumRows = $babDB->db_num_rows($result);
	$iIndex = 0;
	
	if($iIndex < $iNumRows && false != ($datas = $babDB->db_fetch_assoc($result)))
	{
		$aCfg = array('id' => $datas['id'], 'iIdUser' =>  $datas['idUser'], 
			'endTaskReminder' => $datas['endTaskReminder'], 
			'tasksNumerotation' => $datas['tasksNumerotation'],
			'emailNotice' => $datas['emailNotice']);
		return true;
	}
	return false;
}

function bab_createPersonnalTaskConfiguration($iIdUser, &$aCfg)
{
	global $babDB;
	
	$query = 
		'INSERT INTO ' . BAB_TSKMGR_PERSONNAL_TASKS_CONFIGURATION_TBL . ' ' .
			'(' .
				'`id`, ' .
				'`idUser`, `endTaskReminder`, `tasksNumerotation`, `emailNotice` ' .
			') ' .
		'VALUES ' . 
			'(\'\', \'' . 
				$babDB->db_escape_string($iIdUser) . '\', \'' . 
				$babDB->db_escape_string($aCfg['endTaskReminder']) . '\', \'' . 
				$babDB->db_escape_string($aCfg['tasksNumerotation']) . '\', \'' . 
				$babDB->db_escape_string($aCfg['emailNotice']) . 
			'\')'; 
	
	//bab_debug($query);
	return $babDB->db_query($query);
}

function bab_updatePersonnalTaskConfiguration($iIdUser, &$aCfg)
{
	global $babDB;
	
	$query = 
		'UPDATE ' . 
			BAB_TSKMGR_PERSONNAL_TASKS_CONFIGURATION_TBL . ' ' .
		'SET ' .
			'endTaskReminder = \'' . $babDB->db_escape_string($aCfg['endTaskReminder']) . '\', ' .
			'tasksNumerotation = \'' . $babDB->db_escape_string($aCfg['tasksNumerotation']) . '\', ' .
			'emailNotice = \'' . $babDB->db_escape_string($aCfg['emailNotice']) . '\' ' .
		'WHERE ' .
			'idUser = \'' . $babDB->db_escape_string($iIdUser) . '\'';

	//bab_debug($query);
	return $babDB->db_query($query);
}


/*
	$sRefCount == '+ 1' ==> pour ajouter 1
	$sRefCount == '- 1' ==> pour retrancher 1
*/
function bab_updateRefCount($sTblName, $iId, $sRefCount)
{
	global $babDB;
	$query = 
		'UPDATE ' . 
			$sTblName . ' ' .
		'SET ' .
			'refCount = refCount ' . $babDB->db_escape_string($sRefCount) . ' ' .
		'WHERE ' .
			'id = \'' . $babDB->db_escape_string($iId) . '\'';

	//bab_debug($query);

	return $babDB->db_query($query);
}

function bab_getSpecificFieldListQuery($iIdProjectSpace, $iIdProject)
{
	global $babDB;
	
	$iIdUser = (0 === $iIdProjectSpace && 0 === $iIdProject) ? $GLOBALS['BAB_SESS_USERID'] : 0;
	
	$query = 
		'SELECT ' .
			'fb.id iIdField, ' .
			'fb.idUser iIdUser, ' .
			'fb.name sFieldName, ' .
			'fb.refCount refCount, ' .
			'fb.nature iFieldType, ' .
			'CASE fb.nature ' .
				'WHEN \'' . BAB_TM_TEXT_FIELD . '\' THEN \'' . bab_translate("Text") . '\' ' .
				'WHEN \'' . BAB_TM_TEXT_AREA_FIELD . '\' THEN \'' . bab_translate("Text Area") . '\' ' .
				'WHEN \'' . BAB_TM_RADIO_FIELD . '\' THEN \'' . bab_translate("Choice") . '\' ' .
				'ELSE \'???\' ' .
			'END AS sFieldType, ' .
//			'IF(fb.idProject = \'' . $babDB->db_escape_string($iIdProject) . 
//				'\' AND fb.refCount = \'' . $babDB->db_escape_string(0) . '\', 1, 0) is_deletable ' .
				'1 AS is_deletable ' .
		'FROM ' .
			BAB_TSKMGR_SPECIFIC_FIELDS_BASE_CLASS_TBL . ' fb ' .
		'WHERE ' .
			'idProjectSpace = \'' . $babDB->db_escape_string($iIdProjectSpace) . '\' AND ' .
			'(idProject = \'' . $babDB->db_escape_string(0) . '\' OR idProject = \'' . $babDB->db_escape_string($iIdProject) . '\') AND ' .
			'idUser = \'' . $babDB->db_escape_string($iIdUser) . '\' ' .
		'GROUP BY fb.name ASC';
	
		//bab_debug($query);
		return $query;
}


function bab_getSpecificFieldListQueryEx($iIdProjectSpace, $iIdProject)
{
	global $babDB;
	
	$iIdUser = (0 === $iIdProjectSpace && 0 === $iIdProject) ? $GLOBALS['BAB_SESS_USERID'] : 0;
	
	$sQuery = 
		'SELECT ' .
			'fb.id iIdField, ' .
			'fb.idUser iIdUser, ' .
			'fb.name sFieldName, ' .
			'fb.nature iFieldType, ' .
			'CASE fb.nature ' .
				'WHEN \'' . BAB_TM_TEXT_FIELD . '\' THEN \'' . bab_translate("Text") . '\' ' .
				'WHEN \'' . BAB_TM_TEXT_AREA_FIELD . '\' THEN \'' . bab_translate("Text Area") . '\' ' .
				'WHEN \'' . BAB_TM_RADIO_FIELD . '\' THEN \'' . bab_translate("Choice") . '\' ' .
				'ELSE \'???\' ' .
			'END AS sFieldType, ' .
			'CASE fb.nature ' .
				'WHEN \'' . BAB_TM_TEXT_FIELD . '\' THEN ft.defaultValue ' .
				'WHEN \'' . BAB_TM_TEXT_AREA_FIELD . '\' THEN  fa.defaultValue  ' .
				'WHEN \'' . BAB_TM_RADIO_FIELD . '\' THEN frd.value ' .
				'ELSE \'???\' ' .
			'END AS sDefaultValue ' .
		'FROM ' .
			BAB_TSKMGR_SPECIFIC_FIELDS_BASE_CLASS_TBL . ' fb ' .
		'LEFT JOIN ' . 
			BAB_TSKMGR_SPECIFIC_FIELDS_TEXT_CLASS_TBL . ' ft ON ft.id = fb.id ' .
		'LEFT JOIN ' . 
			BAB_TSKMGR_SPECIFIC_FIELDS_AREA_CLASS_TBL . ' fa ON fa.id = fb.id ' .
		'LEFT JOIN ' .
			BAB_TSKMGR_SPECIFIC_FIELDS_RADIO_CLASS_TBL . ' frd ON frd.idFldBase = fb.id ' .
		'WHERE ' .
			'idProjectSpace = \'' . $babDB->db_escape_string($iIdProjectSpace) . '\' AND ' .
			'(idProject = \'' . $babDB->db_escape_string(0) . '\' OR idProject = \'' . $babDB->db_escape_string($iIdProject) . '\') AND ' .
			'idUser = \'' . $babDB->db_escape_string($iIdUser) . '\' AND ' .
			'(ft.isDefaultValue = \'' . $babDB->db_escape_string(BAB_TM_YES) . '\' OR ' . 
				'fa.isDefaultValue = \'' . $babDB->db_escape_string(BAB_TM_YES) . '\' OR ' . 
				'frd.isDefaultValue = \'' . $babDB->db_escape_string(BAB_TM_YES) . 
			'\')';
			'GROUP BY fb.name ASC';
	
//		bab_debug($sQuery);
		return $sQuery;
}


function bab_getSpecificTextFieldClassInfoQuery($iIdProject, $iIdField)
{
	global $babDB;
	
	$query = 
		'SELECT ' .
			'fb.name name, ' .
			'fb.refCount refCount, ' .
			'fb.idProject idProject, ' .
			'ft.defaultValue defaultValue, ' .
//			'IF(fb.idProject = \'' . $babDB->db_escape_string($iIdProject) . '\' AND fb.refCount = \'' . 
//				$babDB->db_escape_string(0) . '\', 1, 0) is_deletable ' .
			'1 AS is_deletable ' .
		'FROM ' . 
			BAB_TSKMGR_SPECIFIC_FIELDS_BASE_CLASS_TBL . ' fb ' .
		'LEFT JOIN ' .
			BAB_TSKMGR_SPECIFIC_FIELDS_TEXT_CLASS_TBL . ' ft ON ft.id = fb.id ' .
		'WHERE ' . 
			'fb.id = \'' . $babDB->db_escape_string($iIdField) . '\'';
	
		//bab_debug($query);
		return $query;
}

function bab_getSpecificAreaFieldClassInfoQuery($iIdProject, $iIdField)
{
	global $babDB;
	
	$query = 
		'SELECT ' .
			'fb.name name, ' .
			'fb.refCount refCount, ' .
			'fb.idProject idProject, ' .
			'fa.defaultValue defaultValue, ' .
//			'IF(fb.idProject = \'' . $babDB->db_escape_string($iIdProject) . '\' AND fb.refCount = \'' . 
//				$babDB->db_escape_string(0) . '\', 1, 0) is_deletable ' .
			'1 AS is_deletable ' .
		'FROM ' . 
			BAB_TSKMGR_SPECIFIC_FIELDS_BASE_CLASS_TBL . ' fb ' .
		'LEFT JOIN ' .
			BAB_TSKMGR_SPECIFIC_FIELDS_AREA_CLASS_TBL . ' fa ON fa.id = fb.id ' .
		'WHERE ' . 
			'fb.id = \'' . $babDB->db_escape_string($iIdField) . '\'';
	
		//bab_debug($query);
		return $query;
}

function bab_getSpecificChoiceFieldClassDefaultValueAndPositionQuery($iIdField)
{
	global $babDB;
	
	$query = 
		'SELECT ' .
			'frd.value defaultValue, ' .
			'frd.position iPosition ' .
		'FROM ' . 
			BAB_TSKMGR_SPECIFIC_FIELDS_RADIO_CLASS_TBL . ' frd ' .
		'WHERE ' . 
			'frd.idFldBase = \'' . $babDB->db_escape_string($iIdField) . '\' ' .
		'ORDER BY ' . 
			'frd.position ASC';
	
		//bab_debug($query);
		return $query;
}

function bab_getSpecificChoiceFieldClassNameAndDefaultChoiceQuery($iIdProject, $iIdField)
{
	global $babDB;
	
	$query = 
		'SELECT ' .
			'fb.name sFieldName, ' .
			'fb.refCount iRefCount, ' .
			'fb.idProject idProject, ' .
			'position iDefaultOption, ' .
//			'IF(fb.idProject = \'' . $babDB->db_escape_string($iIdProject) . '\' AND fb.refCount = \'' . 
//				$babDB->db_escape_string(0) . '\', 1, 0) is_deletable ' .
			'1 AS is_deletable ' .
		'FROM ' . 
			BAB_TSKMGR_SPECIFIC_FIELDS_BASE_CLASS_TBL . ' fb ' .
		'LEFT JOIN ' .
			BAB_TSKMGR_SPECIFIC_FIELDS_RADIO_CLASS_TBL . ' frd ON frd.idFldBase = fb.id ' .
		'WHERE ' . 
			'fb.id = \'' . $babDB->db_escape_string($iIdField) . '\' AND ' .
			'frd.isDefaultValue = \'' . $babDB->db_escape_string(BAB_TM_YES) . '\'';

	//bab_debug($query);
	return $query;
}

function bab_getSpecificChoiceFieldClassOptionCount($iIdField)
{
	global $babDB;
	
	$query = 
		'SELECT ' .
			'COUNT(DISTINCT(frd.id)) count ' .
		'FROM ' . 
			BAB_TSKMGR_SPECIFIC_FIELDS_RADIO_CLASS_TBL . ' frd ' .
		'WHERE ' . 
			'frd.idFldBase = \'' . $babDB->db_escape_string($iIdField) . '\'';
	
	//bab_debug($query);
	$result = $babDB->db_query($query);
	$iNumRows = $babDB->db_num_rows($result);
	$iIndex = 0;
	
	if($iIndex < $iNumRows && false != ($datas = $babDB->db_fetch_assoc($result)))
	{
		return (int) $datas['count'];
		$iIndex++;
	}
	return 0;
}

function bab_deleteAllSpecificFields($sDbFieldName, $sDbFieldValue)
{
	global $babDB;
	$query = 
		'SELECT ' .
			'id ' . 
		'FROM ' .
			BAB_TSKMGR_SPECIFIC_FIELDS_BASE_CLASS_TBL . ' ' .
		'WHERE ' . 
			$sDbFieldName . ' =\'' . $babDB->db_escape_string($sDbFieldValue) . '\'';

	//bab_debug($query);
	
	$result = $babDB->db_query($query);
	$iNumRows = $babDB->db_num_rows($result);
	$iIndex = 0;
	
	while($iIndex < $iNumRows && false != ($data = $babDB->db_fetch_assoc($result)))
	{
		$query = 
			'DELETE FROM ' . 
				BAB_TSKMGR_SPECIFIC_FIELDS_TEXT_CLASS_TBL . ' ' .
			'WHERE ' .
				'id = \'' . $babDB->db_escape_string($data['id']) . '\''; 
		//bab_debug($query);
		$babDB->db_query($query);
	
		$query = 
			'DELETE FROM ' . 
				BAB_TSKMGR_SPECIFIC_FIELDS_AREA_CLASS_TBL . ' ' .
			'WHERE ' .
				'id = \'' . $babDB->db_escape_string($data['id']) . '\''; 
		//bab_debug($query);
		$babDB->db_query($query);
	
		$query = 
			'DELETE FROM ' . 
				BAB_TSKMGR_SPECIFIC_FIELDS_RADIO_CLASS_TBL . ' ' .
			'WHERE ' .
				'idFldBase = \'' . $babDB->db_escape_string($data['id']) . '\''; 
		//bab_debug($query);
		$babDB->db_query($query);

		$iIndex++;
	}
	
	$query = 
		'DELETE FROM ' . 
			BAB_TSKMGR_SPECIFIC_FIELDS_BASE_CLASS_TBL . ' ' .
		'WHERE ' . $sDbFieldName . ' =\'' . $babDB->db_escape_string($sDbFieldValue) . '\''; 
	//bab_debug($query);
	$babDB->db_query($query);
}





function bab_selectAvailableSpecificFieldClassesByProject($iIdProjectSpace, $iIdProject)
{
	global $babDB;

	$query = 
		'SELECT ' .
			'id, ' . 
			'name, ' . 
			'nature iFieldType, ' .
			'CASE nature ' .
				'WHEN \'' . BAB_TM_TEXT_FIELD . '\' THEN \'' . bab_translate("Text") . '\' ' .
				'WHEN \'' . BAB_TM_TEXT_AREA_FIELD . '\' THEN \'' . bab_translate("Text Area") . '\' ' .
				'WHEN \'' . BAB_TM_RADIO_FIELD . '\' THEN \'' . bab_translate("Choice") . '\' ' .
				'ELSE \'???\' ' .
			'END AS sFieldType ' .
		'FROM ' .
			BAB_TSKMGR_SPECIFIC_FIELDS_BASE_CLASS_TBL . ' ' .
		'WHERE ' . 
			'idProjectSpace IN(\'' . $babDB->db_escape_string($iIdProjectSpace) . '\') AND ' .
			'idProject IN(\'' . $babDB->db_escape_string($iIdProject ). '\',\'' . $babDB->db_escape_string(0) . '\')';
	
	//bab_debug($query);
	return $babDB->db_query($query);
}


function bab_selectSpecificFieldClasses($aFieldId)
{
	global $babDB;

	$query = 
		'SELECT ' .
			'id, ' . 
			'name, ' . 
			'nature iFieldType, ' .
			'CASE nature ' .
				'WHEN \'' . BAB_TM_TEXT_FIELD . '\' THEN \'' . bab_translate("Text") . '\' ' .
				'WHEN \'' . BAB_TM_TEXT_AREA_FIELD . '\' THEN \'' . bab_translate("Text Area") . '\' ' .
				'WHEN \'' . BAB_TM_RADIO_FIELD . '\' THEN \'' . bab_translate("Choice") . '\' ' .
				'ELSE \'???\' ' .
			'END AS sFieldType ' .
		'FROM ' .
			BAB_TSKMGR_SPECIFIC_FIELDS_BASE_CLASS_TBL . ' ' .
		'WHERE ' . 
			'id IN(' . $babDB->quote($aFieldId) . ')';
	
//	bab_debug($query);
	return $babDB->db_query($query);
}


/*
function bab_getSpecificFieldClassDefaultValue($iIdSpecificFieldClass, &$sDefaultValue)
{
	global $babDB;

	$sDefaultValue = '';
	
	$query = 
		'SELECT ' .
			'fb.id, ' . 
			'fb.name, ' . 
			'fb.nature iFieldType, ' .
			'CASE fb.nature ' .
				'WHEN \'' . BAB_TM_TEXT_FIELD . '\' THEN ft.defaultValue ' .
				'WHEN \'' . BAB_TM_TEXT_AREA_FIELD . '\' THEN  fa.defaultValue  ' .
				'WHEN \'' . BAB_TM_RADIO_FIELD . '\' THEN frd.value ' .
				'ELSE \'???\' ' .
			'END AS sDefaultValue ' .
		'FROM ' .
			BAB_TSKMGR_SPECIFIC_FIELDS_BASE_CLASS_TBL . ' fb ' .
		'LEFT JOIN ' . 
			BAB_TSKMGR_SPECIFIC_FIELDS_TEXT_CLASS_TBL . ' ft ON ft.id = fb.id ' .
		'LEFT JOIN ' . 
			BAB_TSKMGR_SPECIFIC_FIELDS_AREA_CLASS_TBL . ' fa ON fa.id = fb.id ' .
		'LEFT JOIN ' .
			BAB_TSKMGR_SPECIFIC_FIELDS_RADIO_CLASS_TBL . ' frd ON frd.idFldBase = fb.id ' .
		'WHERE ' . 
			'fb.id = \'' . $babDB->db_escape_string($iIdSpecificFieldClass) . '\' AND ' .
			'(ft.isDefaultValue = \'' . $babDB->db_escape_string(BAB_TM_YES) . '\' OR ' . 
				'fa.isDefaultValue = \'' . $babDB->db_escape_string(BAB_TM_YES) . '\' OR ' . 
				'frd.isDefaultValue = \'' . $babDB->db_escape_string(BAB_TM_YES) . 
			'\')';
			
	//bab_debug($query);

	$result = $babDB->db_query($query);
	$iNumRows = $babDB->db_num_rows($result);
	$iIndex = 0;
	
	if($iIndex < $iNumRows && false != ($data = $babDB->db_fetch_assoc($result)))
	{
		$sDefaultValue = $data['sDefaultValue'];
	}
}
//*/

function bab_selectSpecificFieldClassValues($iIdSpecificFieldClass)
{
	global $babDB;

	$query = 
		'SELECT ' .
			'fb.id iIdSpecificFieldClass, ' . 
			'fb.name sSpecificFieldName, ' . 
			'fb.nature iFieldType, ' .
			'CASE fb.nature ' .
				'WHEN \'' . BAB_TM_TEXT_FIELD . '\' THEN ft.defaultValue ' .
				'WHEN \'' . BAB_TM_TEXT_AREA_FIELD . '\' THEN  fa.defaultValue  ' .
				'WHEN \'' . BAB_TM_RADIO_FIELD . '\' THEN frd.value ' .
				'ELSE \'???\' ' .
			'END AS sValue ' .
		'FROM ' .
			BAB_TSKMGR_SPECIFIC_FIELDS_BASE_CLASS_TBL . ' fb ' .
		'LEFT JOIN ' . 
			BAB_TSKMGR_SPECIFIC_FIELDS_TEXT_CLASS_TBL . ' ft ON ft.id = fb.id ' .
		'LEFT JOIN ' . 
			BAB_TSKMGR_SPECIFIC_FIELDS_AREA_CLASS_TBL . ' fa ON fa.id = fb.id ' .
		'LEFT JOIN ' .
			BAB_TSKMGR_SPECIFIC_FIELDS_RADIO_CLASS_TBL . ' frd ON frd.idFldBase = fb.id ' .
		'WHERE ' . 
			'fb.id = \'' . $babDB->db_escape_string($iIdSpecificFieldClass) . '\'';

	//bab_debug($query);
	return $babDB->db_query($query);
}

/*
function bab_getNextSpecificFieldInstancePosition($iIdTask, &$iPosition)
{
	global $babDB;

	$iPosition = 0;

	$query = 
		'SELECT ' .
			'IFNULL(MAX(position), 0) position ' .
		'FROM ' . 
			BAB_TSKMGR_SPECIFIC_FIELDS_INSTANCE_LIST_TBL . ' ' .
		'WHERE ' . 
			'idTask =\'' . $babDB->db_escape_string($iIdTask) . '\'';

	//bab_debug($query);

	$res = $babDB->db_query($query);

	if(false != $res && $babDB->db_num_rows($res) > 0)
	{
		$data = $babDB->db_fetch_array($res);

		if(false != $data)
		{
			$iPosition = (int) $data['position'] + 1;
		}
	}
}
//*/

/*
function bab_createSpecificFieldInstance($iIdTask, $iIdSpecificField)
{
	global $babDB;
	
	$sDefaultValue = '';
	$iPosition = 0;
	
	bab_getSpecificFieldClassDefaultValue($iIdSpecificField, $sDefaultValue);
	bab_getNextSpecificFieldInstancePosition($iIdTask, $iPosition);

	$query = 
		'INSERT INTO ' . BAB_TSKMGR_SPECIFIC_FIELDS_INSTANCE_LIST_TBL . ' ' .
			'(' .
				'`id`, ' .
				'`idSpFldClass`, `idTask`, `value`, `position`' .
			') ' .
		'VALUES ' . 
			'(\'\', \'' . 
				$babDB->db_escape_string($iIdSpecificField) . '\', \'' . 
				$babDB->db_escape_string($iIdTask) . '\', \'' . 
				$babDB->db_escape_string($sDefaultValue) . '\', \'' . 
				$babDB->db_escape_string($iPosition) . 
			'\')'; 

	//bab_debug($query);
	
	$res = $babDB->db_query($query);
	if(false != $res)
	{
		bab_updateRefCount(BAB_TSKMGR_SPECIFIC_FIELDS_BASE_CLASS_TBL, $iIdSpecificField, '+ 1');
		return true;
	}
	return false;
}
//*/

/*
function bab_updateSpecificInstanceValue($iIdSpecificFieldInstance, $sValue)
{
	global $babDB;
	$query = 
		'UPDATE ' . 
			BAB_TSKMGR_SPECIFIC_FIELDS_INSTANCE_LIST_TBL . ' ' .
		'SET ' .
			'value = \'' . $sValue . '\' ' .
		'WHERE ' .
			'id = \'' . $babDB->db_escape_string($iIdSpecificFieldInstance) . '\'';

	//bab_debug($query);
	return $babDB->db_query($query);
}
//*/

/*
function bab_deleteSpecificFieldInstance($iIdSpecificFieldInstance)
{
	global $babDB;
	
	$iIdSpecificFieldClass = 0;
	
	$result = bab_selectSpecificFieldInstance($iIdSpecificFieldInstance);
	if(false != $result && $babDB->db_num_rows($result) == 1)
	{
		$datas = $babDB->db_fetch_array($result);
		
		$query = 
			'DELETE FROM '	. 
				BAB_TSKMGR_SPECIFIC_FIELDS_INSTANCE_LIST_TBL . ' ' .
			'WHERE ' .
				'id = \'' . $babDB->db_escape_string($iIdSpecificFieldInstance) . '\'';
		$babDB->db_query($query);
		
		bab_updateRefCount(BAB_TSKMGR_SPECIFIC_FIELDS_BASE_CLASS_TBL, $datas['iIdSpFldClass'], '- 1');
		return true;
	}
	return false;
}
//*/

/*
function bab_deleteAllSpecificFieldInstance($iIdTask)
{
	global $babDB;

	$result = bab_selectAllSpecificFieldInstance($iIdTask);

	$result = $babDB->db_query($query);
	$iNumRows = $babDB->db_num_rows($result);
	$iIndex = 0;
	
	while($iIndex < $iNumRows && false != ($datas = $babDB->db_fetch_assoc($result)))
	{
		$iIndex++;
		
		$datas = $babDB->db_fetch_array($result);
		
		$query = 
			'DELETE FROM '	. 
				BAB_TSKMGR_SPECIFIC_FIELDS_INSTANCE_LIST_TBL . ' ' .
			'WHERE ' .
				'id = \'' . $babDB->db_escape_string($datas['iIdSpecificFieldInstance']) . '\'';
		$babDB->db_query($query);
		
		bab_updateRefCount(BAB_TSKMGR_SPECIFIC_FIELDS_BASE_CLASS_TBL, $datas['iIdSpFldClass'], '- 1');
	}
}
//*/

/*
function bab_selectSpecificFieldInstance($iIdSpecificFieldInstance)
{
	global $babDB;
	$query = 
		'SELECT ' .
			'si.id iIdSpecificFieldInstance, ' . 
			'si.value sValue, ' .
			'si.position iPosition, ' .
			'sb.name sFieldName, ' .
			'sb.nature iType, ' .
		'CASE sb.nature ' .
			'WHEN \'' . BAB_TM_TEXT_FIELD . '\' THEN \'' . bab_translate("Text") . '\' ' .
			'WHEN \'' . BAB_TM_TEXT_AREA_FIELD . '\' THEN \'' . bab_translate("Text Area") . '\' ' .
			'WHEN \'' . BAB_TM_RADIO_FIELD . '\' THEN \'' . bab_translate("Choice") . '\' ' .
			'ELSE \'???\' ' .
		'END AS sType, ' .
			'si.idSpFldClass iIdSpFldClass ' .
		'FROM ' .
			BAB_TSKMGR_SPECIFIC_FIELDS_INSTANCE_LIST_TBL . ' si ' .
		'LEFT JOIN ' .
			BAB_TSKMGR_SPECIFIC_FIELDS_BASE_CLASS_TBL . ' sb ON sb.id = si.idSpFldClass ' .
		'WHERE ' .
			'si.id = \'' . $babDB->db_escape_string($iIdSpecificFieldInstance) . '\'';
			
	//bab_debug($query);
	return $babDB->db_query($query);
}
//*/

/*
function bab_selectAllSpecificFieldInstance($iIdTask)
{
	global $babDB;
	$query = 
		'SELECT ' .
			'si.id iIdSpecificFieldInstance, ' . 
			'si.value sValue, ' .
			'si.position iPosition, ' .
			'sb.name sFieldName, ' .
			'sb.nature iType, ' .
		'CASE sb.nature ' .
			'WHEN \'' . BAB_TM_TEXT_FIELD . '\' THEN \'' . bab_translate("Text") . '\' ' .
			'WHEN \'' . BAB_TM_TEXT_AREA_FIELD . '\' THEN \'' . bab_translate("Text Area") . '\' ' .
			'WHEN \'' . BAB_TM_RADIO_FIELD . '\' THEN \'' . bab_translate("Choice") . '\' ' .
			'ELSE \'???\' ' .
		'END AS sType, ' .
			'si.idSpFldClass iIdSpFldClass ' .
		'FROM ' .
			BAB_TSKMGR_SPECIFIC_FIELDS_INSTANCE_LIST_TBL . ' si ' .
		'LEFT JOIN ' .
			BAB_TSKMGR_SPECIFIC_FIELDS_BASE_CLASS_TBL . ' sb ON sb.id = si.idSpFldClass ' .
		'WHERE ' .
			'idTask = \'' . $babDB->db_escape_string($iIdTask) . '\'';
			
	//bab_debug($query);
	return $babDB->db_query($query);
}
//*/


function bab_getAdditionalTaskField($iIdProjectSpace, $iIdProject, $iIdTask)
{
	$aField = array();
	
	require_once $GLOBALS['babInstallPath'] . 'utilit/upgradeincl.php';
	
	$sTableName = bab_tskmgr_getAdditionalFieldTableName($iIdProjectSpace, $iIdProject);
	
	if(bab_isTable($sTableName))
	{
		global $babDB;
		$sQuery = 
			'SELECT ' .
				'* ' .
			'FROM ' .
				 $sTableName . ' ' .
			'WHERE ' .
				'iIdTask = \'' . $babDB->db_escape_string($iIdTask) . '\'';
				
//		bab_debug($sQuery);
		
		$oResult = $babDB->db_query($sQuery);
		if(false !== $oResult)
		{
			$iNumRows = $babDB->db_num_rows($oResult);
			if(0 < $iNumRows)
			{
				$aDatas = array();
				if(false !== ($aDatas = $babDB->db_fetch_assoc($oResult)))
				{
					foreach($aDatas as $sKey => $sValue)
					{
						$sField = substr($sKey, 0, strlen('sField'));
						if('sField' == $sField)
						{
							$iIdFieldClass = substr($sKey, strlen('sField'));
							$aField[$iIdFieldClass] = array('sValue' => $sValue);
						}
					}
					
					if(0 < count($aField))
					{
						$sQuery = 
							'SELECT ' .
								'sb.id iIdFieldClass, ' .
								'sb.name sFieldName, ' .
								'sb.nature iType, ' .
							'CASE sb.nature ' .
								'WHEN \'' . BAB_TM_TEXT_FIELD . '\' THEN \'' . bab_translate("Text") . '\' ' .
								'WHEN \'' . BAB_TM_TEXT_AREA_FIELD . '\' THEN \'' . bab_translate("Text Area") . '\' ' .
								'WHEN \'' . BAB_TM_RADIO_FIELD . '\' THEN \'' . bab_translate("Choice") . '\' ' .
								'ELSE \'???\' ' .
							'END AS sType ' .
							'FROM ' .
								BAB_TSKMGR_SPECIFIC_FIELDS_BASE_CLASS_TBL . ' sb ' .
							'WHERE ' .
								'id IN(' . $babDB->quote(array_keys($aField)) . ')';
								
//						bab_debug($sQuery);
						$oResult = $babDB->db_query($sQuery);
						if(false !== $oResult)
						{
							$iNumRows = $babDB->db_num_rows($oResult);
							if(0 < $iNumRows)
							{
								$aDatas = array();
								while(false !== ($aDatas = $babDB->db_fetch_assoc($oResult)))
								{
									if(array_key_exists($aDatas['iIdFieldClass'], $aField))
									{
										$aField[$aDatas['iIdFieldClass']] = 
											array('sValue' => $aField[$aDatas['iIdFieldClass']]['sValue'], 
											'iIdFieldClass' => $aDatas['iIdFieldClass'], 
											'sFieldName' => $aDatas['sFieldName'],
											'iType' => $aDatas['iType'],
											'sType' => $aDatas['sType']);
									}
								}
							}
						}
					}
				}
			}
		}
	}
//	bab_debug($aField);
	return $aField;
}


function bab_createNoticeEvent($iIdProjectSpace, $iIdProject, $iIdEvent, $iProfil)
{
	global $babDB;
	
	$query = 
		'INSERT INTO ' . BAB_TSKMGR_NOTICE_TBL . ' ' .
			'(' .
				'`id`, ' .
				'`idProjectSpace`, `idProject`, `profil`, `idEvent`' .
			') ' .
		'VALUES ' . 
			'(\'\', \'' . 
				$babDB->db_escape_string($iIdProjectSpace) . '\', \'' . 
				$babDB->db_escape_string($iIdProject) . '\', \'' . 
				$babDB->db_escape_string($iProfil) . '\', \'' . 
				$babDB->db_escape_string($iIdEvent) . 
			'\')'; 

	//bab_debug($query);
	return $babDB->db_query($query);
}

function bab_isNoticeEventSet($iIdProjectSpace, $iIdProject, $iIdEvent, $iProfil)
{
	global $babDB;
	$query = 
		'SELECT ' .
			'profil, '	. 
			'idEvent '	. 
		'FROM ' .
			BAB_TSKMGR_NOTICE_TBL . ' ' .
		'WHERE ' .
			'idProjectSpace = \'' . $babDB->db_escape_string($iIdProjectSpace) . '\' AND ' .
			'idProject = \'' . $babDB->db_escape_string($iIdProject) . '\' AND ' .
			'idEvent = \'' . $babDB->db_escape_string($iIdEvent) . '\' AND ' .
			'profil = \'' . $babDB->db_escape_string($iProfil) . '\'';
	//bab_debug($query);
	$result = $babDB->db_query($query);
	return (false != $result && $babDB->db_num_rows($result) == 1);
}

function bab_selectProjectSpaceNoticeEvent($iIdProjectSpace)
{
	global $babDB;
	$query = 
		'SELECT ' .
			'idProjectSpace, '	. 
			'idProject, '	. 
			'profil, '	. 
			'idEvent '	. 
		'FROM ' .
			BAB_TSKMGR_NOTICE_TBL . ' ' .
		'WHERE ' .
			'idProjectSpace = \'' . $babDB->db_escape_string($iIdProjectSpace) . '\' AND ' .
			'idProject = \'' . $babDB->db_escape_string(0) . '\'';
	//bab_debug($query);
	return $babDB->db_query($query);
}

function bab_deleteAllNoticeEvent($iIdProjectSpace, $iIdProject)
{
	global $babDB;
	$query = 
		'DELETE FROM '	. 
			BAB_TSKMGR_NOTICE_TBL . ' ' .
		'WHERE ' .
			'idProjectSpace = \'' . $babDB->db_escape_string($iIdProjectSpace) . '\' AND ' .
			'idProject = \'' . $babDB->db_escape_string($iIdProject) . '\'';
	return $babDB->db_query($query);
}

function bab_createDefaultProjectSpaceNoticeEvent($iIdProjectSpace)
{
	$iIdProject = 0;
	bab_createNoticeEvent($iIdProjectSpace, $iIdProject, BAB_TM_EV_PROJECT_CREATED, BAB_TM_SUPERVISOR);
	bab_createNoticeEvent($iIdProjectSpace, $iIdProject, BAB_TM_EV_PROJECT_DELETED, BAB_TM_SUPERVISOR);
	bab_createNoticeEvent($iIdProjectSpace, $iIdProject, BAB_TM_EV_TASK_CREATED, BAB_TM_TASK_RESPONSIBLE);
	bab_createNoticeEvent($iIdProjectSpace, $iIdProject, BAB_TM_EV_TASK_UPDATED_BY_MGR, BAB_TM_TASK_RESPONSIBLE);
	bab_createNoticeEvent($iIdProjectSpace, $iIdProject, BAB_TM_EV_TASK_UPDATED_BY_RESP, BAB_TM_PROJECT_MANAGER);
	bab_createNoticeEvent($iIdProjectSpace, $iIdProject, BAB_TM_EV_TASK_DELETED, BAB_TM_TASK_RESPONSIBLE);
	bab_createNoticeEvent($iIdProjectSpace, $iIdProject, BAB_TM_EV_NOTICE_ALERT, BAB_TM_TASK_RESPONSIBLE);
}

function bab_getCategoriesListQuery($iIdProjectSpace, $iIdProject, $iIdUser)
{
	global $babDB;
	$query = 
		'SELECT ' .
			'cat.id iIdCategory, ' .
			'cat.name sCategoryName, ' .
			'cat.description sCategoryDescription, ' .
			'cat.refCount refCount,' .
			'cat.idProject iIdProject,' .
			'cat.color sColor,' .
			'cat.bgColor sBgColor,' .
			'cat.idUser iIdUser,' .
			'IF(cat.idProject = \'' . $babDB->db_escape_string($iIdProject) . '\' AND ' .
				'cat.refCount = \'' . $babDB->db_escape_string(0) . '\', 1, 0) is_deletable ' .
		'FROM ' .
			BAB_TSKMGR_CATEGORIES_TBL . ' cat ' .
		'WHERE ' .
			'idProjectSpace = \'' . $babDB->db_escape_string($iIdProjectSpace) . '\' AND ' .
			'(idProject = \'' . $babDB->db_escape_string(0) . '\' OR ' . 
				'idProject = \'' . $babDB->db_escape_string($iIdProject) . '\') AND ' .
			'idUser = \'' . $babDB->db_escape_string($iIdUser) . '\' ' .
		'GROUP BY cat.name ASC';
		
	return $query;
}

function bab_selectAvailableCategories($iIdProjectSpace, $iIdProject, $iIdUser)
{
	global $babDB;

	$query = 
		'SELECT ' .
			'id, ' . 
			'name ' . 
		'FROM ' .
			BAB_TSKMGR_CATEGORIES_TBL . ' ' .
		'WHERE ' . 
			'idProjectSpace IN(\'' . $babDB->db_escape_string($iIdProjectSpace) . '\') AND ' .
			'idProject IN(\'' . $babDB->db_escape_string($iIdProject) . '\',\'' . $babDB->db_escape_string(0) . '\') AND ' .
			'idUser = \'' . $babDB->db_escape_string($iIdUser) . '\'';
	
	//bab_debug($query);
	return $babDB->db_query($query);
}

function bab_getCategoriesName($aIdCategories, $bIsDeletable)
{
	if(is_array($aIdCategories) && count($aIdCategories) > 0)
	{
		global $babDB;
		$sId = '';
		
		foreach($aIdCategories as $key => $iId)
		{
			$sId .= ', \'' . $babDB->db_escape_string($iId) . '\'';
		}
		
		$sId = substr($sId, strlen(', '));
		
		$query = 
			'SELECT ' .
				'id iIdCategory, ' .
				'name sCategoryName ' .
			'FROM ' .
				BAB_TSKMGR_CATEGORIES_TBL . ' ' .
			'WHERE ' .
				'id IN (' . $sId . ') ' .
				(($bIsDeletable) ? 'AND refCount = \'0\'' : '') .
			'GROUP BY name ASC';
			
		//bab_debug($query);
		
		$res = $babDB->db_query($query);
		if(false != $res)
		{
			$iNumRows = $babDB->db_num_rows($res);
			$iIdx = 0;
			$aIdCategories = array();
				
			while($iIdx < $iNumRows)
			{
				$iIdx++;
				$datas = $babDB->db_fetch_array($res);
		
				if(false != $datas)
				{
					$aIdCategories[] = array('iIdCategory' => $datas['iIdCategory'], 'sCategoryName' => $datas['sCategoryName']);
				}
			}
			return $aIdCategories;
		}
		
	}
	return array();
}

function bab_tskmgr_setPeriods(&$oUserWorkingHours, $aIdUsers, $oStartDate, $oEndDate)
{
	foreach($aIdUsers as $iIdUser)
	{
		$aFilters = array('iIdOwner' => (int) $iIdUser, 'sStartDate' => $oStartDate->getIsoDateTime(), 
			'sEndDate' => $oEndDate->getIsoDateTime());
		
		$query = bab_selectTaskQuery($aFilters);	
		
		global $babDB;
		$result = $babDB->db_query($query);
		
		if(false != $result && $babDB->db_num_rows($result) > 0)
		{
			while(false != ($datas = $babDB->db_fetch_assoc($result)))
			{
				$date_begin = BAB_DateTime::fromIsoDateTime($datas['startDate']);
				$date_end	= BAB_DateTime::fromIsoDateTime($datas['endDate']);

				$oBabCalPeriod = $oUserWorkingHours->setUserPeriod($datas['idOwner'], $date_begin, $date_end, BAB_PERIOD_TSKMGR);

				$oBabCalPeriod->setProperty('SUMMARY', $datas['sShortDescription']);
				$oBabCalPeriod->setProperty('DTSTART', $datas['startDate']);
				$oBabCalPeriod->setProperty('DTEND', $datas['endDate']);
				$oBabCalPeriod->setProperty('CATEGORIES', $datas['sCategoryName']);
				$oBabCalPeriod->color = $datas['sBgColor'];
			}
		}
	}
}




function bab_tskmgr_getSelectedFieldId($iIdProject, $iType)
{
	global $babDB;
	
	$sQuery = 
		'SELECT 
			`iIdField` iIdField
		FROM ' .
			BAB_TSKMGR_SELECTED_TASK_FIELDS_TBL . ' ' . 
		'WHERE ' . 
			'`iIdProject` = ' . $babDB->quote($iIdProject) . ' AND ' .
			'`iType` = ' . $babDB->quote($iType);

//	bab_debug($sQuery);
	
	$aField = array();
	$oResult = $babDB->db_query($sQuery);
	if(false !== $oResult)
	{
		$iNumRows = $babDB->db_num_rows($oResult);
		if(0 < $iNumRows)
		{
			$aDatas = array();
			while(false !== ($aDatas = $babDB->db_fetch_assoc($oResult)))
			{
				$aField[] = $aDatas['iIdField'];
			}
		}
	}
	return $aField;
}


function bab_tskmgr_getSelectedField($iIdProject)
{
	global $babDB;
	
	$sWereClauseItem = ' ';
	if(0 >= (int) $iIdProject)
	{
		$sWereClauseItem = 'spf.`idUser` = ' . $babDB->quote($GLOBALS['BAB_SESS_USERID']);
		$iIdProject = 0;
	}
	else
	{
		$sWereClauseItem = 'spf.`idProject` = ' . $babDB->quote($iIdProject);
	}
	
	$sQuery = 
		'SELECT 
			stf.`iIdField` iId,
			stf.`iType` iType,
			stf.`iPosition` iPosition,
			IFNULL(tf.`sName`, spf.`name`) sName,
			IFNULL(tf.`sLegend`, spf.`name`) sLegend
		FROM ' .
			BAB_TSKMGR_SELECTED_TASK_FIELDS_TBL . ' stf ' . 
		'LEFT JOIN ' .
			BAB_TSKMGR_TASK_FIELDS_TBL . ' tf ON tf.iId = stf.iIdField AND stf.iType = ' . BAB_TM_TASK_FIELD . ' ' .
		'LEFT JOIN ' .
			BAB_TSKMGR_SPECIFIC_FIELDS_BASE_CLASS_TBL . ' spf ON spf.id = stf.iIdField AND stf.iType = ' . BAB_TM_ADDITIONAL_FIELD . ' ' .
		'WHERE ' . 
			'stf.`iIdProject` = ' . $babDB->quote($iIdProject) . ' OR ' .
			$sWereClauseItem . ' ' .
		'ORDER BY ' .
			'stf.`iPosition` ASC';

//	bab_debug($sQuery);
	
	$aField = array();
	$oResult = $babDB->db_query($sQuery);
	if(false !== $oResult)
	{
		$iNumRows = $babDB->db_num_rows($oResult);
		if(0 < $iNumRows)
		{
			$aDatas = array();
			while(false !== ($aDatas = $babDB->db_fetch_assoc($oResult)))
			{
				$aField[] = array(
					'iId' => $aDatas['iId'], 'iType' => $aDatas['iType'],
					'sName' => $aDatas['sName'], 'sLegend' => $aDatas['sLegend'],
					'iPosition' => $aDatas['iPosition']);
			}
		}
	}
	return $aField;
}


function bab_tskmgr_deleteSelectedTaskFields($iIdProject)
{
	global $babDB;
	
	$query = 'DELETE FROM ' . BAB_TSKMGR_SELECTED_TASK_FIELDS_TBL . ' WHERE iIdProject = \'' . $babDB->db_escape_string($iIdProject) . '\''; 
	//bab_debug($query);
	$babDB->db_query($query);
}


function bab_tskmgr_saveSelectedTaskField($aField)
{
	global $babDB;
	
	$iIndex = 0;
	foreach($aField as $aItem)
	{
		$sQuery = 
			'INSERT INTO ' . BAB_TSKMGR_SELECTED_TASK_FIELDS_TBL . ' ' .
				'(' .
					'`iId`, `iIdField`, `iIdProject`,  `iPosition`, `iType` ' .
				') ' .
			'VALUES ' . 
				'(\'\', ' . 
					$babDB->quote($aItem['iIdField']) . ', ' . 
					$babDB->quote($aItem['iIdProject']) . ', ' . 
					$babDB->quote($iIndex) . ', ' . 
					$babDB->quote($aItem['iType']) . 
				')'; 
				
		$babDB->db_query($sQuery);
		++$iIndex;
	}
	
}

function bab_tskmgr_getSelectableTaskFields($iIdProject, $aSelectedTaskField, $aSelectedAdditionalField)
{
	global $babDB;
	
	$sQuery = 
		'SELECT 
			t0.`iId` iId,
			t0.`sName` sName, 
			t0.`sLegend` sLegend
		FROM ' .
			BAB_TSKMGR_TASK_FIELDS_TBL . ' t0 ' . 
		'WHERE ' . 
			't0.`iId` NOT IN(' . $babDB->quote($aSelectedTaskField) . ')';

//	bab_debug($sQuery);
	
	$aField = array();
	$oResult = $babDB->db_query($sQuery);
	if(false !== $oResult)
	{
		$iNumRows = $babDB->db_num_rows($oResult);
		if(0 < $iNumRows)
		{
			$aDatas = array();
			while(false !== ($aDatas = $babDB->db_fetch_assoc($oResult)))
			{
				$aField[] = array(
					'iId' => $aDatas['iId'], 'iType' => BAB_TM_TASK_FIELD,
					'sName' => $aDatas['sName'], 'sLegend' => $aDatas['sLegend']);
			}
		}
	}
	
	$sQuery = 
		'SELECT 
			t0.`id` iId,
			t0.`name` sName 
		FROM ' .
			BAB_TSKMGR_SPECIFIC_FIELDS_BASE_CLASS_TBL . ' t0 ' . 
		'WHERE ' . 
			't0.`idProject` = ' . $babDB->quote($iIdProject) . ' AND ' .
			't0.`id` NOT IN(' . $babDB->quote($aSelectedAdditionalField) . ')';
			
//	bab_debug($sQuery);
	
	$oResult = $babDB->db_query($sQuery);
	if(false !== $oResult)
	{
		$iNumRows = $babDB->db_num_rows($oResult);
		if(0 < $iNumRows)
		{
			$aDatas = array();
			while(false !== ($aDatas = $babDB->db_fetch_assoc($oResult)))
			{
				$aField[] = array(
					'iId' => $aDatas['iId'], 'iType' => BAB_TM_ADDITIONAL_FIELD,
					'sName' => $aDatas['sName'], 'sLegend' => $aDatas['sName']);
			}
		}
	}
	return $aField;
}


function bab_tskmgr_getAdditionalFieldTableName($iIdProjectSpace, $iIdProject)
{
	$sNamePart = '';
	
	if(0 >= $iIdProjectSpace && 0 >= $iIdProject)
	{
		$sNamePart = 'user' . $GLOBALS['BAB_SESS_USERID'];
	}
	else 
	{
		$sNamePart = 'project' . $iIdProject;
	}
	
	return 'bab_tskmgr_' . $sNamePart . '_additional_fields';
}

//Ajoute un champ additionel dans la table dynamique
function bab_tskmgr_createAdditionalField($iIdProjectSpace, $iIdProject, $iFieldType, $iIdFieldClass, $sFieldValue)
{
	global $babDB;
	
	$sTableName = bab_tskmgr_getAdditionalFieldTableName($iIdProjectSpace, $iIdProject);
	
	require_once $GLOBALS['babInstallPath'] . 'utilit/upgradeincl.php';
	
	$sType = (1 == $iFieldType) ? 'TEXT' : 'VARCHAR(255)';
	
	if(!bab_isTable($sTableName))
	{
		//Si la table n'existe pas la créer
		
		$aTableDefinition	= array();
		$aTableDefinition[] = '`iId` int(11) unsigned NOT NULL auto_increment';
		$aTableDefinition[] = '`iIdTask` int(11) unsigned NOT NULL';
		$aTableDefinition[] = '`sField' . $iIdFieldClass . '` ' . $sType . ' NOT NULL';
		$aTableDefinition[] = 'PRIMARY KEY (`iId`)';
		$aTableDefinition[] = 'KEY `iIdTask` (`iIdTask`)';
		
		$sQuery = 'CREATE TABLE `' . $sTableName . '` (' . implode(',', $aTableDefinition) . ')';
		$babDB->db_query($sQuery);
	}
	else
	{
		//Si la table existe alors ajouter la colonne
		if(!bab_isTableField($sTableName, 'sField' . $iIdFieldClass))
		{		
			$sQuery = 'ALTER TABLE `' . $sTableName . '` ADD `sField' . $iIdFieldClass . '` ' . $sType . ' NOT NULL';
			$babDB->db_query($sQuery);
		}
	}

	
	$aWhereClauseItem = array();
	if(0 >= $iIdProjectSpace && 0 >= $iIdProject)
	{
		$aWhereClauseItem[] = 't1.idOwner = ' . $babDB->quote((int) $GLOBALS['BAB_SESS_USERID']);
		$aWhereClauseItem[] = 't1.isPersonnal = ' . $babDB->quote(BAB_TM_YES);
	}
	else
	{
		$aWhereClauseItem[] = 't0.idProject = ' . $babDB->quote((int) $iIdProject);
	}
	
	$aWhereClauseItem[] = 't0.class = ' . $babDB->quote(BAB_TM_TASK);
	$aWhereClauseItem[] = 't0.id = t1.idTask';
	
	//Récupérer la liste de toutes les tâches perso ou du projet 
	//Si la tâche existe alors on lui rajoute la valeur par défaut
	//Si la tâche n'existe pas alors on créer une entrée pour la tâche et on lui rajoute la valeur par défaut
	
	$sQuery = 
		'SELECT ' . 
			't0.id iIdTask ' .
		'FROM ' . 
			BAB_TSKMGR_TASKS_INFO_TBL . ' t1, ' .
			BAB_TSKMGR_TASKS_TBL . ' t0 ' .
		'WHERE ' . 
			implode(' AND ', $aWhereClauseItem);

//	bab_debug($sQuery);
	
	$oResult = $babDB->db_query($sQuery);
	if(false !== $oResult)
	{
		$iNumRows = $babDB->db_num_rows($oResult);
		if(0 < $iNumRows)
		{
			$aDatas = array();
			while(false !== ($aDatas = $babDB->db_fetch_assoc($oResult)))
			{
				$sQuery = 
					'SELECT ' . 
						'iId, ' .
						'iIdTask ' .
					'FROM ' .
						$sTableName . ' ' .
					'WHERE ' . 
						'iIdTask = ' . $babDB->quote($aDatas['iIdTask']);
						
				$oResultTask = $babDB->db_query($sQuery);
				$iNumRows = $babDB->db_num_rows($oResultTask);
				if(0 == $iNumRows)
				{
					$sQuery = 
						'INSERT INTO ' . $sTableName . ' ' .
							'(' .
								'`iId`, ' .
								'`iIdTask`, `sField' . $iIdFieldClass . '` ' .
							') ' .
						'VALUES ' . 
							'(\'\', \'' . 
								$babDB->db_escape_string($aDatas['iIdTask']) . '\', \'' . 
								$babDB->db_escape_string($sFieldValue) . 
							'\')'; 
								
//					bab_debug($sQuery);
					$babDB->db_query($sQuery);
				}
				else
				{
					if(false !== ($aDatasTask = $babDB->db_fetch_assoc($oResultTask)))
					{
						$sQuery = 
							'UPDATE ' .	
								$sTableName . ' ' . 
							'SET ' .
								'sField' . $iIdFieldClass . ' = ' . $babDB->quote($sFieldValue) . ' ' .
							'WHERE ' .
								'iId = ' . $babDB->quote($aDatasTask['iId']);
								
//						bab_debug($sQuery);
						$babDB->db_query($sQuery);
					}
				}
			}
		}
	}
}

function bab_tskmgr_createTaskAdditionalFields($iIdProjectSpace, $iIdProject, $iIdTask)
{
	require_once $GLOBALS['babInstallPath'] . 'utilit/upgradeincl.php';
	
	$sTableName = bab_tskmgr_getAdditionalFieldTableName($iIdProjectSpace, $iIdProject);
	
	global $babDB;
	$sQuery = bab_getSpecificFieldListQueryEx($iIdProjectSpace, $iIdProject);
			
	$oResult = $babDB->db_query($sQuery);
	$iNumRows = $babDB->db_num_rows($oResult);
	if(0 < $iNumRows)
	{			
		if(bab_isTable($sTableName))
		{
			$sQuery = 
				'INSERT INTO ' . $sTableName . ' ' .
					'(' .
						'`iId`, ' .
						'`iIdTask` ' .
					') ' .
				'VALUES ' . 
					'(\'\', \'' . 
						$babDB->db_escape_string($iIdTask) . 
					'\')'; 
						
			//bab_debug($sQuery);
			$babDB->db_query($sQuery);
		
			$aSet = array();
			while(false !== ($aDatas = $babDB->db_fetch_assoc($oResult)))
			{
				if(bab_isTableField($sTableName, 'sField' . $aDatas['iIdField']))
				{	
	
					$aSet[] = 'sField' . $aDatas['iIdField'] . ' = ' . $babDB->quote($aDatas['sDefaultValue']);
				}
			}
	
			if(count($aSet) > 0)
			{
				$sQuery = 
					'UPDATE ' .	
						$sTableName . ' ' . 
					'SET ' .
						implode(', ', $aSet) . ' ' . 
					'WHERE ' .
						'iIdTask = ' . $babDB->quote($iIdTask);
			
				//bab_debug($sQuery);
				$babDB->db_query($sQuery);
			}
		}
	}
}


function bab_tskmgr_deleteTaskAdditionalFields($iIdProjectSpace, $iIdProject, $iIdTask)
{
	global $babDB;
	require_once $GLOBALS['babInstallPath'] . 'utilit/upgradeincl.php';

	$sTableName = bab_tskmgr_getAdditionalFieldTableName($iIdProjectSpace, $iIdProject);
	
	if(bab_isTable($sTableName))
	{
		$sQuery = 
			'DELETE FROM ' . 
				$sTableName . ' ' . 
			'WHERE ' . 
				'iIdTask = ' . $babDB->quote($iIdTask);
				 
		//bab_debug($sQuery);
		$babDB->db_query($sQuery);
		
	}	
}



//Appelé lors de la mise à jour d'une tâche
function bab_tskmgr_updateAdditionalField($iIdProjectSpace, $iIdProject, $iIdTask, $aDatas)
{
	global $babDB;
	
	$sTableName = bab_tskmgr_getAdditionalFieldTableName($iIdProjectSpace, $iIdProject);
	
	require_once $GLOBALS['babInstallPath'] . 'utilit/upgradeincl.php';
	
	if(bab_isTable($sTableName))
	{
		$aSet = array();
		
		foreach($aDatas as $sFieldName => $sFieldValue)
		{
			$aSet[] = $sFieldName . ' = ' . $babDB->quote($sFieldValue);
		}
		
		$sQuery = 
			'UPDATE ' .	
				$sTableName . ' ' . 
			'SET ' .
				implode(', ', $aSet) . ' ' . 
			'WHERE ' .
				'iIdTask = ' . $babDB->quote($iIdTask);
	
		//bab_debug($sQuery);
		return $babDB->db_query($sQuery);
	}
}

function bab_tskmgr_deleteAdditionalField($iIdFieldClass)
{
	global $babDB;
	
	$sQuery = 
		'SELECT ' . 
			'idProjectSpace, ' .
			'idProject ' .
		'FROM ' .
			BAB_TSKMGR_SPECIFIC_FIELDS_BASE_CLASS_TBL . ' ' .
		'WHERE ' . 
			'id = ' . $babDB->quote($iIdFieldClass);
	//bab_debug($sQuery);
			
	$oResult = $babDB->db_query($sQuery);
	$iNumRows = $babDB->db_num_rows($oResult);
	if(0 != $iNumRows)
	{
		if(false !== ($aDatas = $babDB->db_fetch_assoc($oResult)))
		{
			$sQuery = 
				'DELETE FROM ' . 
					BAB_TSKMGR_SELECTED_TASK_FIELDS_TBL . ' ' . 
				'WHERE ' . 
					'iIdField = ' . $babDB->quote($iIdFieldClass) . 'AND ' .
					'iType = ' . $babDB->quote(BAB_TM_ADDITIONAL_FIELD);
					 
			//bab_debug($sQuery);
			$babDB->db_query($sQuery);
						
			$iIdProjectSpace	= (int) $aDatas['idProjectSpace'];
			$iIdProject			= (int) $aDatas['idProject'];
			
			$sTableName = bab_tskmgr_getAdditionalFieldTableName($iIdProjectSpace, $iIdProject);
			
			require_once $GLOBALS['babInstallPath'] . 'utilit/upgradeincl.php';
			
			if(bab_isTable($sTableName))
			{
				if(bab_isTableField($sTableName, 'sField' . $iIdFieldClass))
				{
					$sQuery = 'ALTER TABLE `' . $sTableName . '` DROP `' . 'sField' . $iIdFieldClass . '`';	
						
					//bab_debug($sQuery);
					$babDB->db_query($sQuery);
				}
			}
		}
	}
}


function bab_tskmgr_dropAdditionalFieldTable($iIdProjectSpace, $iIdProject) 
{
	global $babDB;
	$sTableName = bab_tskmgr_getAdditionalFieldTableName($iIdProjectSpace, $iIdProject);
	
	require_once $GLOBALS['babInstallPath'] . 'utilit/upgradeincl.php';
	
	if(bab_isTable($sTableName))
	{
		$sQuery = 'DROP TABLE `' . $sTableName . '`';	
			
		//bab_debug($sQuery);
		$babDB->db_query($sQuery);
	}
}



function createDefaultProjectSelectedField($iIdProject)
{
	$aDefaultField = array(
		array('iIdTaskField' => 5,  'iIdProject' => 0, 'iPosition' => 1, 'iType' => 0),
		array('iIdTaskField' => 6,  'iIdProject' => 0, 'iPosition' => 2, 'iType' => 0),
		array('iIdTaskField' => 14, 'iIdProject' => 0, 'iPosition' => 3, 'iType' => 0),
		array('iIdTaskField' => 15, 'iIdProject' => 0, 'iPosition' => 4, 'iType' => 0),
		array('iIdTaskField' => 17, 'iIdProject' => 0, 'iPosition' => 5, 'iType' => 0),
		array('iIdTaskField' => 16, 'iIdProject' => 0, 'iPosition' => 6, 'iType' => 0),
		array('iIdTaskField' => 13, 'iIdProject' => 0, 'iPosition' => 7, 'iType' => 0)
	);
	
	global $babDB;
	foreach($aDefaultField as $aDefaultFieldItem)
	{
		$sQuery = 
			'INSERT INTO ' . BAB_TSKMGR_SELECTED_TASK_FIELDS_TBL . ' ' .
				'(' .
					'`iId`, `iIdField`, `iIdProject`,  `iPosition`, `iType` ' .
				') ' .
			'VALUES ' . 
				'(\'\', ' . 
					$babDB->quote($aDefaultFieldItem['iIdTaskField']) . ', ' . 
					$babDB->quote($iIdProject) . ', ' . 
					$babDB->quote($aDefaultFieldItem['iPosition']) . ', ' . 
					$babDB->quote($aDefaultFieldItem['iType']) . 
				')'; 
				
		$babDB->db_query($sQuery);
	}
}
?>