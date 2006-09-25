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




//Project space functions

function bab_selectProjectSpaceList()
{
	global $babBody, $babDB;

	$query = 
		'SELECT ' .
			'id, ' . 
			'name, ' . 
			'description ' .
		'FROM ' .
			BAB_TSKMGR_PROJECTS_SPACES_TBL . ' ' .
		'WHERE ' . 
			'idDelegation =\'' . $babBody->currentAdmGroup . '\'';
	
	//bab_debug($query);
	return $babDB->db_query($query);
}

function bab_getProjectSpaceList(&$aProjectSpaceList)
{
	global $babBody, $babDB;

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

function bab_getProjectSpace($iIdProjectSpace, &$aProjectSpace)
{
	global $babBody, $babDB;

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
			'id =\'' . $iIdProjectSpace . '\'';
	
	//bab_debug($query);
	
	$res = $babDB->db_query($query);
	if(false != $res)
	{
		if(false != ($datas = $babDB->db_fetch_assoc($res)))
		{
			$aProjectSpace = array('id' => $datas['id'], 'name' => $datas['name'], 
				'description' => $datas['description'], 'refCount' => $datas['refCount']);
			return true;
		}
	}
	return false;
}

function bab_isProjectSpaceExist($iIdDelegation, $sName)
{
	global $babBody, $babDB;

	$aProjectSpace = array();

	$query = 
		'SELECT ' .
			'id ' . 
		'FROM ' .
			BAB_TSKMGR_PROJECTS_SPACES_TBL . ' ' .
		'WHERE ' . 
			'idDelegation =\'' . $iIdDelegation . '\' AND ' .
			'name =\'' . $sName . '\'';
	
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
	global $babBody, $babDB;

	$query = 
		'SELECT ' .
			'id ' . 
		'FROM ' .
			BAB_TSKMGR_PROJECTS_TBL . ' ' .
		'WHERE ' . 
			'idProjectSpace =\'' . $iIdProjectSpace . '\'';

	//bab_debug($query);
	
	$res = $babDB->db_query($query);
	if(false != $res)
	{
		if(false != ($datas = $babDB->db_fetch_assoc($res)))
		{
			bab_deleteProject($datas['id']);
		}
	}
	
	$query = 'DELETE FROM ' . BAB_TSKMGR_PROJECTS_SPACES_TBL . ' WHERE id = \'' . $iIdProjectSpace . '\''; 
	//bab_debug($query);
	$babDB->db_query($query);
	
	$query = 'DELETE FROM ' . BAB_TSKMGR_DEFAULT_PROJECTS_CONFIGURATION_TBL . ' WHERE idProjectSpace = \'' . $iIdProjectSpace . '\''; 
	//bab_debug($query);
	$babDB->db_query($query);

	$query = 'DELETE FROM ' . BAB_TSKMGR_CATEGORIES_TBL . ' WHERE idProjectSpace = \'' . $iIdProjectSpace . '\''; 
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
	global $babBody, $babDB;

	$query = 
		'INSERT INTO ' . BAB_TSKMGR_PROJECTS_SPACES_TBL . ' ' .
			'(' .
				'`id`, ' .
				'`idDelegation`, `name`, `description`, `created`, `idUserCreated`' .
			') ' .
		'VALUES ' . 
			'(\'\', \'' . 
				$iIdDelegation . '\', \'' . $sName . '\', \'' . $sDescription . '\', \'' . 
				date("Y-m-d H:i:s") . '\', \'' . $GLOBALS['BAB_SESS_USERID'] . 
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
	global $babBody, $babDB;

	$query = 
		'UPDATE ' . 
			BAB_TSKMGR_PROJECTS_SPACES_TBL . ' ' .
		'SET ' . ' ' .
				'`name` = \'' . $sName . '\', ' .
				'`description` = \'' . $sDescription . '\', ' .
				'`modified` = \'' . date("Y-m-d H:i:s") . '\', ' .
				'`idUserModified` = \'' . $GLOBALS['BAB_SESS_USERID'] . '\' ' .
		'WHERE ' . 
			'`id` = \'' . $iIdProjectSpace . '\'';

	//bab_debug($query);
	return $babDB->db_query($query);
}

function bab_getDefaultProjectSpaceConfiguration($iIdProjectSpace, &$aConfiguration)
{
	global $babBody, $babDB;

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
			'idProjectSpace =\'' . $iIdProjectSpace . '\'';
	
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
	global $babBody, $babDB;

	$query = 
		'INSERT INTO ' . BAB_TSKMGR_DEFAULT_PROJECTS_CONFIGURATION_TBL . ' ' .
			'(' .
				'`id`, ' .
				'`idProjectSpace`, `tskUpdateByMgr`, `endTaskReminder`, `tasksNumerotation`, `emailNotice`, `faqUrl`' .
			') ' .
		'VALUES ' . 
			'(\'\', \'' . 
				$iIdProjectSpace . '\', \'' . BAB_TM_YES . '\', \'' . 5 . '\', \'' . BAB_TM_SEQUENTIAL . '\', \'' . 
				BAB_TM_YES . '\', \'\')'; 

	//bab_debug($query);
	return $babDB->db_query($query);
}

function bab_updateDefaultProjectSpaceConfiguration($aConfiguration)
{
	global $babBody, $babDB;

	$query = 
		'UPDATE ' . 
			BAB_TSKMGR_DEFAULT_PROJECTS_CONFIGURATION_TBL . ' ' .
		'SET ' . ' ' .
				'`idProjectSpace` = \'' . $aConfiguration['idProjectSpace'] . '\', ' .
				'`tskUpdateByMgr` = \'' . $aConfiguration['tskUpdateByMgr'] . '\', ' .
				'`endTaskReminder` = \'' . $aConfiguration['endTaskReminder'] . '\', ' .
				'`tasksNumerotation` = \'' . $aConfiguration['tasksNumerotation'] . '\', ' .
				'`emailNotice` = \'' . $aConfiguration['emailNotice'] . '\', ' .
				'`faqUrl` = \'' . $aConfiguration['faqUrl'] . '\' ' .
		'WHERE ' . 
			'`id` = \'' . $aConfiguration['id'] . '\'';

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
			'ps.idDelegation = \'' . $iIdDelegation . '\'';
			
	//bab_debug($query);
	return $babDB->db_query($query);
}


function bab_selectProjectList($iIdProjectSpace)
{
	global $babBody, $babDB;

	$query = 
		'SELECT ' .
			'* ' .
		'FROM ' .
			BAB_TSKMGR_PROJECTS_TBL . ' ' .
		'WHERE ' . 
			'idProjectSpace = \'' . $iIdProjectSpace . '\'';
			
	//bab_debug($query);
	return $babDB->db_query($query);
}

function bab_getProjectList($iIdProjectSpace, &$aProjectList)
{
	global $babBody, $babDB;

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
	global $babBody, $babDB;

	$aProject = array();
	
	$query = 
		'SELECT ' .
			'* ' .
		'FROM ' .
			BAB_TSKMGR_PROJECTS_TBL . ' ' .
		'WHERE ' . 
			'id = \'' . $iIdProject . '\'';

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
	global $babBody, $babDB;

	$query = 
		'INSERT INTO ' . BAB_TSKMGR_PROJECTS_TBL . ' ' .
			'(' .
				'`id`, ' .
				'`idProjectSpace`, `name`, `description`, `created`, `idUserCreated`' .
			') ' .
		'VALUES ' . 
			'(\'\', \'' . 
				$iIdProjectSpace . '\', \'' . $sName . '\', \'' . $sDescription . '\', \'' . 
				date("Y-m-d H:i:s") . '\', \'' . $GLOBALS['BAB_SESS_USERID'] . 
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
		
		bab_updateRefCount(BAB_TSKMGR_PROJECTS_SPACES_TBL, $iIdProjectSpace, '+ \'1\'');
		
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
		return $iIdProject;		
	}
	return false;
}

function bab_updateProject($iIdProject, $sName, $sDescription)
{
	global $babBody, $babDB;

	$query = 
		'UPDATE ' . 
			BAB_TSKMGR_PROJECTS_TBL . ' ' .
		'SET ' . ' ' .
				'`name` = \'' . $sName . '\', ' .
				'`description` = \'' . $sDescription . '\', ' .
				'`modified` = \'' . date("Y-m-d H:i:s") . '\', ' .
				'`idUserModified` = \'' . $GLOBALS['BAB_SESS_USERID'] . '\' ' .
		'WHERE ' . 
			'`id` = \'' . $iIdProject . '\'';

	//bab_debug($query);
	return $babDB->db_query($query);
}

function bab_deleteProject($iIdProject)
{
	global $babBody, $babDB;
	
	bab_deleteAllTask($iIdProject);
	
	aclDelete(BAB_TSKMGR_PROJECTS_MANAGERS_GROUPS_TBL, $iIdProject);
	aclDelete(BAB_TSKMGR_PROJECTS_SUPERVISORS_GROUPS_TBL, $iIdProject);
	aclDelete(BAB_TSKMGR_PROJECTS_VISUALIZERS_GROUPS_TBL, $iIdProject);
	aclDelete(BAB_TSKMGR_TASK_RESPONSIBLE_GROUPS_TBL, $iIdProject);

	$query = 'DELETE FROM ' . BAB_TSKMGR_PROJECTS_CONFIGURATION_TBL . ' WHERE idProject = \'' . $iIdProject . '\''; 
	//bab_debug($query);
	$babDB->db_query($query);

	$query = 'DELETE FROM ' . BAB_TSKMGR_PROJECTS_REVISIONS_TBL . ' WHERE idProject = \'' . $iIdProject . '\''; 
	//bab_debug($query);
	$babDB->db_query($query);

	$query = 'DELETE FROM ' . BAB_TSKMGR_PROJECTS_COMMENTS_TBL . ' WHERE idProject = \'' . $iIdProject . '\''; 
	//bab_debug($query);
	$babDB->db_query($query);

	$query = 'DELETE FROM ' . BAB_TSKMGR_CATEGORIES_TBL . ' WHERE idProject = \'' . $iIdProject . '\''; 
	//bab_debug($query);
	$babDB->db_query($query);

	bab_deleteProjectSpecificFields($iIdProject);	
	
	$query = 'DELETE FROM ' . BAB_TSKMGR_PROJECTS_TBL . ' WHERE id = \'' . $iIdProject . '\''; 
	//bab_debug($query);
	return $babDB->db_query($query);
}

function bab_createProjectConfiguration($aConfiguration)
{
	global $babBody, $babDB;

	$query = 
		'INSERT INTO ' . BAB_TSKMGR_PROJECTS_CONFIGURATION_TBL . ' ' .
			'(' .
				'`id`, ' .
				'`idProject`, `tskUpdateByMgr`, `endTaskReminder`, `tasksNumerotation`, `emailNotice`, `faqUrl`' .
			') ' .
		'VALUES ' . 
			'(\'\', \'' . 
				$aConfiguration['idProject'] . '\', \'' . $aConfiguration['tskUpdateByMgr'] . '\', \'' . 
				$aConfiguration['endTaskReminder'] . '\', \'' . $aConfiguration['tasksNumerotation'] . '\', \'' . 
				$aConfiguration['emailNotice'] . '\', \'' . $aConfiguration['faqUrl'] . '\')'; 

	//bab_debug($query);
	return $babDB->db_query($query);
}

function bab_getProjectConfiguration($iIdProject, &$aConfiguration)
{
	global $babBody, $babDB;

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
			'idProject =\'' . $iIdProject . '\'';
	
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
	global $babBody, $babDB;

	$query = 
		'UPDATE ' . 
			BAB_TSKMGR_PROJECTS_CONFIGURATION_TBL . ' ' .
		'SET ' . ' ' .
				'`idProject` = \'' . $aConfiguration['idProject'] . '\', ' .
				'`tskUpdateByMgr` = \'' . $aConfiguration['tskUpdateByMgr'] . '\', ' .
				'`endTaskReminder` = \'' . $aConfiguration['endTaskReminder'] . '\', ' .
				'`tasksNumerotation` = \'' . $aConfiguration['tasksNumerotation'] . '\', ' .
				'`emailNotice` = \'' . $aConfiguration['emailNotice'] . '\', ' .
				'`faqUrl` = \'' . $aConfiguration['faqUrl'] . '\' ' .
		'WHERE ' . 
			'`id` = \'' . $aConfiguration['id'] . '\'';

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
	global $babBody, $babDB;
	
	$query = 
		'SELECT ' .
			'id, ' . 
			'IF(LENGTH(commentary) > \'' . $iLenght . '\', CONCAT(LEFT(commentary, \'' . $iLenght . '\'), \'...\'), commentary) commentary, ' .
			'created ' .
		'FROM ' .
			BAB_TSKMGR_PROJECTS_COMMENTS_TBL . ' ' .
		'WHERE ' . 
			'idProject =\'' . $iIdProject . '\'';
	
	//bab_debug($query);
	return $babDB->db_query($query);
}

function bab_getProjectCommentary($iIdCommentary, &$sCommentary)
{
	global $babBody, $babDB;
	
	$sCommentary = '';
	
	$query = 
		'SELECT ' .
			'id, ' . 
			'commentary ' .
		'FROM ' .
			BAB_TSKMGR_PROJECTS_COMMENTS_TBL . ' ' .
		'WHERE ' . 
			'id =\'' . $iIdCommentary . '\'';
	
	//bab_debug($query);
	$result = $babDB->db_query($query);
	$iNumRows = $babDB->db_num_rows($result);
	$iIndex = 0;
	
	if(/*$iIndex < $iNumRows &&*/ false != ($datas = $babDB->db_fetch_assoc($result)))
	{
		$sCommentary = $datas['commentary'];
		$iIndex++;
		return true;
	}
	return false;
}

function bab_createProjectCommentary($iIdProject, $sCommentary)
{
	global $babBody, $babDB;
	
	$query = 
		'INSERT INTO ' . BAB_TSKMGR_PROJECTS_COMMENTS_TBL . ' ' .
			'(' .
				'`id`, ' .
				'`idProject`, `commentary`, `created`, `idUserCreated`' .
			') ' .
		'VALUES ' . 
			'(\'\', \'' . 
				$iIdProject . '\', \'' . $sCommentary . '\', \'' . 
				date("Y-m-d H:i:s") . '\', \'' . $GLOBALS['BAB_SESS_USERID'] . 
			'\')'; 


	//bab_debug($query);
	return $babDB->db_query($query);
}

function bab_updateProjectCommentary($iIdCommentary, $sCommentary)
{
	global $babBody, $babDB;

	$query = 
		'UPDATE ' . 
			BAB_TSKMGR_PROJECTS_COMMENTS_TBL . ' ' .
		'SET ' . ' ' .
				'`commentary` = \'' . $sCommentary . '\', ' .
				'`modified` = \'' . date("Y-m-d H:i:s") . '\', ' .
				'`idUserModified` = \'' . $GLOBALS['BAB_SESS_USERID'] . '\' ' .
		'WHERE ' . 
			'`id` = \'' . $iIdCommentary . '\'';

	//bab_debug($query);
	return $babDB->db_query($query);
}

function bab_deleteProjectCommentary($iIdCommentary)
{
	global $babDB;
	$query = 'DELETE FROM '	. BAB_TSKMGR_PROJECTS_COMMENTS_TBL . ' WHERE id = \'' . $iIdCommentary . '\'';
	$babDB->db_query($query);
}

function bab_createProjectRevision($iIdProject, $iIdProjectComment, $iMajorVersion, $iMinorVersion)
{
	global $babBody, $babDB;

	$query = 
		'INSERT INTO ' . BAB_TSKMGR_PROJECTS_REVISIONS_TBL . ' ' .
			'(' .
				'`id`, ' .
				'`idProject`, `idProjectComment`, `majorVersion`, `minorVersion`' .
			') ' .
		'VALUES ' . 
			'(\'\', \'' . 
				$iIdProject . '\', \'' . $iIdProjectComment . '\', \'' . $iMajorVersion . '\', \'' . $iMinorVersion . '\')'; 

	//bab_debug($query);
	
	return $babDB->db_query($query);
}

function bab_getLastProjectRevision($iIdProject, &$iMajorVersion, &$iMinorVersion)
{
	//Selection de la date
	$query_major = 
		'SELECT ' .
			'@major := MAX(majorVersion) ' .
		'FROM ' .
			BAB_TSKMGR_PROJECTS_REVISIONS_TBL . ' ' . 
		'WHERE ' . 
			'idProject = \'' . $iIdProject . '\'';
	
	$query_minor = 
		'SELECT ' .
			'@minor := MAX(minorVersion) ' .
		'FROM ' .
			BAB_TSKMGR_PROJECTS_REVISIONS_TBL . ' ' . 
		'WHERE ' . 
			'idProject = \'' . $iIdProject . '\' AND ' .
			'majorVersion = @major';
			
	$query = 
		'SELECT ' .
			'majorVersion, ' .
			'minorVersion ' .
		'FROM ' .
			BAB_TSKMGR_PROJECTS_REVISIONS_TBL . ' ' . 
		'WHERE ' . 
			'idProject = \'' . $iIdProject . '\' AND ' .
			'majorVersion = @major AND ' .
			'minorVersion = @minor';

	global $babBody, $babDB;
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


//Task functions	
/*
function bab_startDependingTask($iIdProjectSpace, $iIdProject, $iIdTask, $iLinkType)
{
	$aDependingTasks = array();
	bab_getDependingTasks($iIdTask, $iLinkType, $aDependingTasks);
	
	require_once $GLOBALS['babInstallPath'] . 'tmSendMail.php';
	$oSendMail = new BAB_TM_SendEmail();
	
	foreach($aDependingTasks as $iId => $aTaskInfo)
	{
		if(bab_getTask($iId, $aTask))
		{
			$aTask['iParticipationStatus'] = BAB_TM_IN_PROGRESS;
			$aTask['sStartDate'] = date("Y-m-d");
			bab_updateTask($iId, $aTask);
			
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
				
				$iIdEvent = BAB_TM_EV_TASK_STARTED;
				$g_aEmailMsg =& $GLOBALS['g_aEmailMsg'];
				$sSubject = $g_aEmailMsg[$iIdEvent]['subject'];
				$sBody = $g_aEmailMsg[$iIdEvent]['body'];
				
				$sBody = sprintf($sBody, $aTask['sTaskNumber'], $sProjectName, $sProjectSpaceName);
				$oSendMail->send_notification(bab_getUserEmail($aTaskInfo['iIdResponsible']), $sSubject, $sBody);
			}
		}
	}
	
	if(BAB_TM_END_TO_START == $iLinkType)
	{
		reset($aDependingTasks);
		foreach($aDependingTasks as $iId => $aTaskInfo)
		{
			bab_startDependingTask($iIdProjectSpace, $iIdProject, $iId, BAB_TM_START_TO_START);
		}
	}
}
//*/

function bab_getTaskCount($iIdProject, $iIdUser = -1)
{
	$query = 
		'SELECT ' . 
			'COUNT(id) iTaskCount ' .
		'FROM ' . 
			BAB_TSKMGR_TASKS_TBL . ' t ' .
		'WHERE ' . 
			't.idProject = \'' . $iIdProject . '\' ';
			
	if(-1 !== $iIdUser)
	{
		$query .= 'AND idUserCreated = \'' . $iIdUser . '\'';
	}
		
	//bab_debug($query);
	
	global $babDB;
	$db	= & $GLOBALS['babDB'];

	$result = $babDB->db_query($query);
	$iNumRows = $babDB->db_num_rows($result);
	if(false != $result && $iNumRows > 0)
	{
		$datas = $babDB->db_fetch_assoc($result);
		return $datas['iTaskCount'];
	}
	return 0;
}

/*
function bab_getDependingTasks($iIdTask, $iLinkType, &$aDependingTasks)
{
	$query = 
		'SELECT ' . 
			'lt.idTask, ' .
			'tr.idResponsible ' .
		'FROM ' . 
			BAB_TSKMGR_LINKED_TASKS_TBL . ' lt, ' .
			BAB_TSKMGR_TASKS_TBL . ' t, ' .
			BAB_TSKMGR_TASKS_RESPONSIBLES_TBL . ' tr ' .
		'WHERE ' . 
			'lt.idPredecessorTask = \'' . $iIdTask . '\' AND ' .
			'lt.linkType = \'' . $iLinkType . '\' AND ' .
			't.participationStatus NOT IN(\'' . BAB_TM_IN_PROGRESS . '\', \'' . BAB_TM_ENDED . '\') ' .
		'GROUP BY lt.idTask';
		
	//bab_debug($query);
	
	global $babDB;
	$db	= & $GLOBALS['babDB'];

	$result = $babDB->db_query($query);
	$iNumRows = $babDB->db_num_rows($result);
	$iIndex = 0;
	
	while($iIndex < $iNumRows && false != ($datas = $babDB->db_fetch_assoc($result)))
	{
		bab_getDependingTasks($datas['idTask'], $iLinkType, $aDependingTasks);
		$aDependingTasks[$datas['idTask']] = array('iIdTask' => $datas['idTask'],
			'iIdResponsible' => $datas['idResponsible']);
		$iIndex++;
	}
}
//*/

function bab_getDependingTasks($iIdTask, &$aDependingTasks, $iLinkType = -1)
{
	$query = 
		'SELECT ' . 
			'lt.idTask, ' .
			'IFNULL(tr.idResponsible, 0) idResponsible ' .
		'FROM ' . 
			BAB_TSKMGR_LINKED_TASKS_TBL . ' lt, ' .
			BAB_TSKMGR_TASKS_TBL . ' t ' .
		'LEFT JOIN ' .
			BAB_TSKMGR_TASKS_RESPONSIBLES_TBL . ' tr ON tr.idTask = t.id ' .
		'WHERE ' . 
			'lt.idPredecessorTask = \'' . $iIdTask . '\'' .
			(($iLinkType != -1) ? ' AND lt.linkType = \'' . $iLinkType . '\' ' : ' ') .
		'GROUP BY lt.idTask';
		
	//bab_debug($query);

	global $babDB;
	$db	= & $GLOBALS['babDB'];

	$result = $babDB->db_query($query);
	$iNumRows = $babDB->db_num_rows($result);
	$iIndex = 0;
	
	while($iIndex < $iNumRows && false != ($datas = $babDB->db_fetch_assoc($result)))
	{
		$aDependingTasks[$datas['idTask']] = array('iIdTask' => $datas['idTask'],
			'iIdResponsible' => $datas['idResponsible']);
		$iIndex++;
	}
}


function bab_getAllTaskIndexedById($iIdProject, &$aTasks)
{
	global $babBody, $babDB;

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
			'majorVersion, ' .
			'minorVersion, ' .
			'color, ' .
			'position, ' .
			'completion, ' .
			'plannedStartDate, ' .
			'plannedEndDate, ' .
			'startDate, ' .
			'endDate, ' .
			'isNotified ' .
		'FROM ' .
			BAB_TSKMGR_TASKS_TBL . ' ' .
		'WHERE ' . 
			'idProject = \'' . $iIdProject . '\'';
			
	//bab_debug($query);

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
			'iIsLinked' => $datas['isLinked'], 
			'iIdCalEvent' => $datas['idCalEvent'], 'sHashCalEvent' => $datas['hashCalEvent'], 
			'iDuration' => $datas['duration'], 'iMajorVersion' => $datas['majorVersion'], 
			'iMinorVersion' => $datas['minorVersion'], 'sColor' => $datas['color'], 
			'iPosition' => $datas['position'], 'iCompletion' => $datas['completion'],
			'sPlannedStartDate' => $datas['plannedStartDate'], 'sStartDate' => $datas['startDate'],
			'sPlannedEndDate' => $datas['plannedEndDate'], 'sEndDate' => $datas['endDate'],
			'iIsNotified' => $datas['isNotified'],
			'sShortDescription' => $datas['shortDescription']);
			
		$iIndex++;
	}
}


function bab_createTask($aParams)
{
	global $babBody, $babDB;

	$aTask = array();	

	$query = 
		'INSERT INTO ' . BAB_TSKMGR_TASKS_TBL . ' ' .
			'(' .
				'`id`, ' .
				'`idProject`, `taskNumber`, `description`, `idCategory`, `class`, ' .
				'`participationStatus`, `isLinked`, `idCalEvent`, `hashCalEvent`, ' .
				'`duration`, `majorVersion`, `minorVersion`, `color`, `position`, ' .
				'`completion`, `startDate`, `endDate`, `plannedStartDate`, ' .
				'`plannedEndDate`, `created`, `idUserCreated`, `isNotified`, ' .
				'`idUserModified`, `modified`, `shortDescription`' .
			') ' .
		'VALUES ' . 
			'(\'\', \'' . 
				$aParams['iIdProject'] . '\', \'' . $aParams['sTaskNumber'] . '\', \'' . 
				$aParams['sDescription'] . '\', \'' . $aParams['iIdCategory'] . '\', \'' . 
				$aParams['iClass'] . '\', \'' . $aParams['iParticipationStatus'] . '\', \'' . 
				$aParams['iIsLinked'] . '\', \'' . $aParams['iIdCalEvent'] . '\', \'' . 
				$aParams['sHashCalEvent'] . '\', \'' . $aParams['iDuration'] . '\', \'' . 
				$aParams['iMajorVersion'] . '\', \'' . $aParams['iMinorVersion'] . '\', \'' . 
				$aParams['sColor'] . '\', \'' . $aParams['iPosition'] . '\', \'' . 
				$aParams['iCompletion'] . '\', \'' . $aParams['sStartDate'] . '\', \'' . 
				$aParams['sEndDate'] . '\', \'' . /*$aParams['sPlannedStartDate']*/'' . '\', \'' . 
				/*$aParams['sPlannedEndDate']*/'' . '\', \'' . $aParams['sCreated'] . '\', \'' . 
				$aParams['iIdUserCreated'] . '\', \'' . $aParams['iIsNotified'] . '\', \'' . 
				$aParams['iIdUserModified'] . '\', \'' . $aParams['sModified'] . '\', \'' .
				$aParams['sShortDescription'] . '\')'; 

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
	global $babBody, $babDB;

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
			'ti.idOwner ' .
		'FROM ' .
			BAB_TSKMGR_TASKS_TBL . ' t ' .
		'LEFT JOIN ' .
			BAB_TSKMGR_TASKS_INFO_TBL . ' ti ON ti.idTask = t.id ' .
		'WHERE ' . 
			't.id = \'' . $iIdTask . '\'';
			
	//bab_debug($query);

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
			'iIsLinked' => $datas['isLinked'], 
			'iIdCalEvent' => $datas['idCalEvent'], 'sHashCalEvent' => $datas['hashCalEvent'], 
			'iDuration' => $datas['duration'], 'iMajorVersion' => $datas['majorVersion'], 
			'iMinorVersion' => $datas['minorVersion'], 'sColor' => $datas['color'], 
			'iPosition' => $datas['position'], 'iCompletion' => $datas['completion'],
			'sPlannedStartDate' => $datas['plannedStartDate'], 'sStartDate' => $datas['startDate'],
			'sPlannedEndDate' => $datas['plannedEndDate'], 'sEndDate' => $datas['endDate'],
			'iIsNotified' => $datas['isNotified'], 'iIdOwner' => $datas['idOwner'],
			'sShortDescription' => $datas['shortDescription']);
		return true;
	}
	return false;
}


function bab_updateTask($iIdTask, $aParams)
{
	global $babBody, $babDB;
	
	$query = 
		'UPDATE ' . 
			BAB_TSKMGR_TASKS_TBL . ' ' .
		'SET ' . ' ' .
			'`taskNumber` = \'' . $aParams['sTaskNumber'] . '\', ' .
			'`description` = \'' . $aParams['sDescription'] . '\', ' .
			'`idCategory` = \'' . $aParams['iIdCategory'] . '\', ' .
			'`class` = \'' . $aParams['iClass'] . '\', ' .
			'`participationStatus` = \'' . $aParams['iParticipationStatus'] . '\', ' .
			'`isLinked` = \'' . $aParams['iIsLinked'] . '\', ' .
			'`duration` = \'' . $aParams['iDuration'] . '\', ' .
			'`majorVersion` = \'' . $aParams['iMajorVersion'] . '\', ' .
			'`minorVersion` = \'' . $aParams['iMinorVersion'] . '\', ' .
			'`color` = \'' . $aParams['sColor'] . '\', ' .
			'`completion` = \'' . $aParams['iCompletion'] . '\', ' .
			'`startDate` = \'' . $aParams['sStartDate'] . '\', ' .
			'`endDate` = \'' . $aParams['sEndDate'] . '\', ' .
			'`plannedStartDate` = \'' . $aParams['sPlannedStartDate'] . '\', ' .
			'`plannedEndDate` = \'' . $aParams['sPlannedEndDate'] . '\', ' .
			'`idUserModified` = \'' . $aParams['iIdUserModified'] . '\', ' .
			'`modified` = \'' . $aParams['sModified'] . '\', ' .
			'`shortDescription` = \'' . $aParams['sShortDescription'] . '\' ' .
		'WHERE ' . 
			'id = \'' . $iIdTask . '\'';
			
	//bab_debug($query);
	return $babDB->db_query($query);
}

function bab_deleteTask($iIdTask)
{
	bab_deleteAllTaskSpecificFieldInstance($iIdTask);
	bab_deleteTaskLinks($iIdTask);
	bab_deleteTaskResponsibles($iIdTask);

	global $babDB;
	$query = 'DELETE FROM ' . BAB_TSKMGR_TASKS_INFO_TBL . ' WHERE idTask = \'' . $iIdTask . '\'';
	$babDB->db_query($query);

	$query = 'DELETE FROM ' . BAB_TSKMGR_TASKS_TBL . ' WHERE id = \'' . $iIdTask . '\'';
	$babDB->db_query($query);

	$query = 'DELETE FROM ' . BAB_TSKMGR_TASKS_COMMENTS_TBL . ' WHERE idTask = \'' . $iIdTask . '\'';
	$babDB->db_query($query);
}

function bab_deleteAllTask($iIdProject)
{
	global $babDB;
	
	$query = 
		'SELECT ' .
			'id ' .
		'FROM ' .
			BAB_TSKMGR_TASKS_TBL . ' ' .
		'WHERE ' .
			'idProject = \'' . $iIdProject . '\'';
			
	
	$result = $babDB->db_query($query);
	$iNumRows = $babDB->db_num_rows($result);
	$iIndex = 0;
	
	while($iIndex < $iNumRows && false != ($data = $babDB->db_fetch_assoc($result)))
	{
		bab_deleteAllTaskSpecificFieldInstance($data['id']);
		bab_deleteTaskLinks($data['id']);
		bab_deleteTaskResponsibles($data['id']);
//		aclDelete(BAB_TSKMGR_TASK_RESPONSIBLE_GROUPS_TBL, $data['id']);

		$query = 'DELETE FROM ' . BAB_TSKMGR_TASKS_INFO_TBL . ' WHERE idTask = \'' . $data['id'] . '\'';
		$babDB->db_query($query);
		
		$iIndex++;
	}

	$query = 'DELETE FROM ' . BAB_TSKMGR_TASKS_COMMENTS_TBL . ' WHERE idProject = \'' . $iIdProject . '\'';
	$babDB->db_query($query);

	$query = 'DELETE FROM ' . BAB_TSKMGR_TASKS_TBL . ' WHERE idProject = \'' . $iIdProject . '\'';
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
					'(\'\', \'' . $iIdTask . '\', \'' . $aPredecessor['iIdPredecessorTask'] . '\', \'' . $aPredecessor['iLinkType'] . '\')'; 
			
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
			'idPredecessorTask = \'' . $iIdTask . '\'';
			
	
	$result = $babDB->db_query($query);
	$iNumRows = $babDB->db_num_rows($result);
	$iIndex = 0;
	
	if(/*$iIndex < $iNumRows &&*/ false != ($data = $babDB->db_fetch_assoc($result)))
	{
		$iCount = $data['iCount'];
	}	
}

function bab_deleteTaskLinks($iIdTask)
{
	global $babDB;
	$query = 'DELETE FROM ' . BAB_TSKMGR_LINKED_TASKS_TBL . ' WHERE idTask = \'' . $iIdTask . '\'';
	$babDB->db_query($query);
}

function bab_deleteTaskResponsibles($iIdTask)
{
	global $babDB;
	$query = 'DELETE FROM ' . BAB_TSKMGR_TASKS_RESPONSIBLES_TBL . ' WHERE idTask = \'' . $iIdTask . '\'';
	
	//bab_debug($query);
	
	$babDB->db_query($query);
}

function bab_deleteAllTaskSpecificFieldInstance($iIdTask)
{
	global $babDB;
	
	$query = 
		'SELECT ' .
			'idSpFldClass ' .
		'FROM ' .
			BAB_TSKMGR_SPECIFIC_FIELDS_INSTANCE_LIST_TBL . ' ' .
		'WHERE ' .
			'idTask = \'' . $iIdTask . '\'';
			
	
	$result = $babDB->db_query($query);
	$iNumRows = $babDB->db_num_rows($result);
	$iIndex = 0;
	
	while($iIndex < $iNumRows && false != ($data = $babDB->db_fetch_assoc($result)))
	{
		bab_updateRefCount(BAB_TSKMGR_SPECIFIC_FIELDS_BASE_CLASS_TBL, $data['idSpFldClass'], '- \'1\'');
		$iIndex++;
	}
}

function bab_selectTasksList($iIdProject, $iLenght = 50)
{
	global $babBody, $babDB;
	
	$query = 
		'SELECT ' .
			't.id, ' . 
			't.taskNumber, ' . 
			'IF(LENGTH(t.description) > \'' . $iLenght . '\', CONCAT(LEFT(t.description, \'' . $iLenght . '\'), \'...\'), t.description) description, ' .
			't.created, ' .
			't.shortDescription ' .
		'FROM ' .
//			BAB_TSKMGR_TASKS_INFO_TBL . ' ti, ' . 
			BAB_TSKMGR_TASKS_TBL . ' t ' .
		'WHERE ' . 
			't.idProject =\'' . $iIdProject . '\' ' .
		'ORDER BY t.position';
	
	//bab_debug($query);
	return $babDB->db_query($query);
}

function bab_selectPersonnalTasksList($iLenght = 50)
{
	global $babBody, $babDB;
	
	$query = 
		'SELECT ' .
			't.id, ' . 
			't.taskNumber, ' . 
			'IF(LENGTH(t.description) > \'' . $iLenght . '\', CONCAT(LEFT(t.description, \'' . $iLenght . '\'), \'...\'), t.description) description, ' .
			't.created, ' .
			't.shortDescription ' .
		'FROM ' .
			BAB_TSKMGR_TASKS_INFO_TBL . ' ti, ' . 
			BAB_TSKMGR_TASKS_TBL . ' t ' .
		'WHERE ' . 
			'ti.idOwner =\'' . $GLOBALS['BAB_SESS_USERID'] . '\' AND ' .
			'ti.isPersonnal =\'' . BAB_TM_YES . '\' AND ' .
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
	$db = & $GLOBALS['babDB'];

	//Personnal task
	if(0 == $iIdProject)
	{
		$query = 
			'SELECT ' .
				'IFNULL(MAX(ti.idTask), 0) idTask ' .
			'FROM ' . 
				BAB_TSKMGR_TASKS_INFO_TBL . ' ti ' .
			'WHERE ' . 
				'ti.idOwner =\'' . $GLOBALS['BAB_SESS_USERID'] . '\' AND ' .
				'ti.isPersonnal =\'' . BAB_TM_YES . '\'';
				
		//bab_debug($query);
		
		$res = $db->db_query($query);
		if(false != $res && $db->db_num_rows($res) > 0)
		{
			$data = $db->db_fetch_array($res);
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
					'id=\'' . $data['idTask'] . '\'';
		
			//bab_debug($query);
		
			$res = $db->db_query($query);
		
			if(false != $res && $db->db_num_rows($res) > 0)
			{
				$data = $db->db_fetch_array($res);
		
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
				'idProject=\'' . $iIdProject . '\'';
	
		//bab_debug($query);
	
		$res = $db->db_query($query);
	
		if(false != $res && $db->db_num_rows($res) > 0)
		{
			$data = $db->db_fetch_array($res);
	
			if(false != $data)
			{
				$iPosition = (int) $data['position'] + 1;
			}
		}
	}
}

function bab_getAvailableTaskResponsibles($iIdProject, &$aTaskResponsible)
{
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
}

function bab_getTaskResponsibles($iIdTask, &$aTaskResponsible)
{
	global $babBody, $babDB;
	
	$aTaskResponsible = array();

	$query = 
		'SELECT ' .
			'idResponsible ' . 
		'FROM ' .
			BAB_TSKMGR_TASKS_RESPONSIBLES_TBL . ' ' .
		'WHERE ' . 
			'idTask =\'' . $iIdTask . '\'';
	
	//bab_debug($query);
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
					'(\'\', \'' . $iIdTask . '\', \'' . $iIdResponsible . '\')'; 
			$babDB->db_query($query);
		}
	}
}

function bab_selectTaskCommentary($iIdTask, $iLenght = 50)
{
	global $babBody, $babDB;
	
	$query = 
		'SELECT ' .
			'id, ' . 
			'IF(LENGTH(commentary) > \'' . $iLenght . '\', CONCAT(LEFT(commentary, \'' . $iLenght . '\'), \'...\'), commentary) commentary, ' .
			'created ' .
		'FROM ' .
			BAB_TSKMGR_TASKS_COMMENTS_TBL . ' ' .
		'WHERE ' . 
			'idTask =\'' . $iIdTask . '\'';
	
	//bab_debug($query);
	return $babDB->db_query($query);
}

function bab_createTaskCommentary($iIdProject, $iIdTask, $sCommentary)
{
	global $babBody, $babDB;
	
	$query = 
		'INSERT INTO ' . BAB_TSKMGR_TASKS_COMMENTS_TBL . ' ' .
			'(' .
				'`id`, `idTask`, ' .
				'`idProject`, `commentary`, `created`, `idUserCreated`' .
			') ' .
		'VALUES ' . 
			'(\'\', \'' . 
				$iIdTask . '\', \'' . $iIdProject . '\', \'' . $sCommentary . '\', \'' . 
				date("Y-m-d H:i:s") . '\', \'' . $GLOBALS['BAB_SESS_USERID'] . 
			'\')'; 

	//bab_debug($query);
	return $babDB->db_query($query);
}

function bab_updateTaskCommentary($iIdCommentary, $sCommentary)
{
	global $babBody, $babDB;

	$query = 
		'UPDATE ' . 
			BAB_TSKMGR_TASKS_COMMENTS_TBL . ' ' .
		'SET ' . ' ' .
				'`commentary` = \'' . $sCommentary . '\', ' .
				'`modified` = \'' . date("Y-m-d H:i:s") . '\', ' .
				'`idUserModified` = \'' . $GLOBALS['BAB_SESS_USERID'] . '\' ' .
		'WHERE ' . 
			'`id` = \'' . $iIdCommentary . '\'';

	//bab_debug($query);
	return $babDB->db_query($query);
}

function bab_deleteTaskCommentary($iIdCommentary)
{
	global $babDB;
	$query = 'DELETE FROM '	. BAB_TSKMGR_TASKS_COMMENTS_TBL . ' WHERE id = \'' . $iIdCommentary . '\'';
	$babDB->db_query($query);
}

function bab_getTaskCommentary($iIdCommentary, &$sCommentary)
{
	global $babBody, $babDB;
	
	$sCommentary = '';
	
	$query = 
		'SELECT ' .
			'id, ' . 
			'commentary ' .
		'FROM ' .
			BAB_TSKMGR_TASKS_COMMENTS_TBL . ' ' .
		'WHERE ' . 
			'id =\'' . $iIdCommentary . '\'';
	
	//bab_debug($query);
	$result = $babDB->db_query($query);
	$iNumRows = $babDB->db_num_rows($result);
	$iIndex = 0;
	
	if(/*$iIndex < $iNumRows &&*/ false != ($datas = $babDB->db_fetch_assoc($result)))
	{
		$sCommentary = $datas['commentary'];
		$iIndex++;
		return true;
	}
	return false;
}

function bab_isTaskNumberUsed($iIdProject, $iIdTask, $sTaskNumber)
{
	$sIdTask = '';
	if(0 != $iIdTask)
	{
		$sIdTask = ' AND id <> \'' . $iIdTask . '\'';
	}

	$query = 
		'SELECT ' . 
			'id, ' .
			'taskNumber ' .
		'FROM ' . 
			BAB_TSKMGR_TASKS_TBL . ' ' .
		'WHERE ' . 
			'idProject = \'' . $iIdProject . '\' AND ' .
			'taskNumber LIKE \'' . $sTaskNumber . '\'' .
			$sIdTask;
		
	//bab_debug($query);
	
	$db	= & $GLOBALS['babDB'];
	
	$result = $db->db_query($query);
	return (false != $result && 0 == $db->db_num_rows($result));
}

function bab_selectLinkableTask($iIdProject, $iIdTask)
{
	global $babDB;

	$sIdTask = '';
	if(0 != $iIdTask)
	{
		if(0 == $iIdProject)
		{
			$sIdTask = ' AND idTask <> \'' . $iIdTask . '\'';
		}
		else
		{
			$sIdTask = ' AND id <> \'' . $iIdTask . '\'';
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
				'idOwner =\'' . $GLOBALS['BAB_SESS_USERID'] . '\'' .
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
			'idProject = \'' . $iIdProject . '\' AND ' .
			'class =\'' . BAB_TM_TASK . '\' AND ' .
			'participationStatus <> \'' . BAB_TM_ENDED . '\'' . ' ' . 
			$sIdTask . ' ' .
		'ORDER BY position';

	//bab_debug($query);
	
	return $babDB->db_query($query);
}

function bab_getLinkedTasks($iIdTask, &$aLinkedTasks)
{
	$aLinkedTasks = array();
	
	$query = 
		'SELECT ' . 
			'idPredecessorTask, ' .
			'linkType ' .
		'FROM ' . 
			BAB_TSKMGR_LINKED_TASKS_TBL . ' ' .
		'WHERE ' . 
			'idTask = \'' . $iIdTask . '\'';

	//bab_debug($query);
	
	global $babDB;

	$result = $babDB->db_query($query);
	$iNumRows = $babDB->db_num_rows($result);
	$iIndex = 0;
	
	while($iIndex < $iNumRows && false != ($datas = $babDB->db_fetch_assoc($result)))
	{
		$aLinkedTasks[] = array('iIdPredecessorTask' => $datas['idPredecessorTask'], 'iLinkType' => $datas['linkType']);
		$iIndex++;
	}
}

/*
function bab_getOwnedTaskQuery($iTaskFilter = null, $iTaskClass = null)
{
	$query = 
		'SELECT ' . 
			'IFNULL(ps.id, 0) iIdProjectSpace, ' .
			'IFNULL(ps.name, \'\') sProjectSpaceName, ' .
			'IFNULL(p.id, 0) iIdProject, ' .
			'IFNULL(p.name, \'\') sProjectName, ' .
			't.id iIdTask, ' .
			't.shortDescription sShortDescription, ' .
			't.taskNumber sTaskNumber, ' .
			't.class iClass, ' .
			't.startDate startDate, ' .
			't.endDate endDate, ' .
		'CASE t.class ' .			
			'WHEN \'' . BAB_TM_TASK . '\' THEN \'' . bab_translate("Task") . '\' ' .
			'WHEN \'' . BAB_TM_CHECKPOINT . '\' THEN \'' . bab_translate("Checkpoint") . '\' ' .
			'WHEN \'' . BAB_TM_TODO . '\' THEN \'' . bab_translate("ToDo") . '\' ' .
			'ELSE \'???\' ' .
		'END AS sClass ' .
		'FROM ' . 
			BAB_TSKMGR_TASKS_INFO_TBL . ' ti, ' .
			BAB_TSKMGR_TASKS_TBL . ' t ' .
		'LEFT JOIN ' . 
			BAB_TSKMGR_PROJECTS_TBL . ' p ON p.id = t.idProject ' .
		'LEFT JOIN ' . 
			BAB_TSKMGR_PROJECTS_SPACES_TBL . ' ps ON ps.id = p.idProjectSpace ' .
		'WHERE ' . 
			'ti.idOwner = \'' . $GLOBALS['BAB_SESS_USERID'] . '\' AND ' .
			't.id = ti.idTask ';
			
	if(!is_null($iTaskFilter) && -1 != $iTaskFilter)
	{
		//iTaskFilter (-1 ==> All, -2 ==> personnal task)
		if(-2 == $iTaskFilter)
		{
			$query .= 'AND ti.isPersonnal = \'' . BAB_TM_YES . '\' ';
		}
		else 
		{
			$query .= 'AND t.idProject = \'' . $iTaskFilter . '\' ';
		}
	}
		
	if(!is_null($iTaskClass) && -1 != $iTaskClass)
	{
		$query .= 'AND t.class = \'' . $iTaskClass . '\' ';
	}
			
	$query .= 
		'GROUP BY ' .
			'sProjectSpaceName ASC, sProjectName ASC, sTaskNumber ASC';

	//bab_debug($query);
	return $query;
}
//*/

/*
function bab_selectOwnedTaskQueryByDate($sStartDate, $sEndDate, $iTaskFilter = null, $iTaskClass = null)
{
	$query = 
		'SELECT ' . 
			'IFNULL(ps.id, 0) iIdProjectSpace, ' .
			'IFNULL(ps.name, \'\') sProjectSpaceName, ' .
			'IFNULL(p.id, 0) iIdProject, ' .
			'IFNULL(p.name, \'\') sProjectName, ' .
			't.id iIdTask, ' .
			't.taskNumber sTaskNumber, ' .
			't.shortDescription sShortDescription, ' .
			't.class iClass, ' .
		'CASE t.class ' .
			'WHEN \'' . BAB_TM_CHECKPOINT . '\' THEN \'ganttCheckpoint\' ' . 
			'WHEN \'' . BAB_TM_TODO . '\' THEN \'ganttToDo\' ' .
			'ELSE \'\' ' .
		'END AS sAdditionnalClass, ' .
			't.completion iCompletion, ' .
			't.startDate startDate, ' .
			't.endDate endDate, ' .
			'cat.id iIdCategory, ' .
			'IFNULL(cat.color, \'\' ) sBgColor ' .
		'FROM ' . 
			BAB_TSKMGR_TASKS_INFO_TBL . ' ti, ' .
			BAB_TSKMGR_TASKS_TBL . ' t ' .
		'LEFT JOIN ' . 
			BAB_TSKMGR_CATEGORIES_TBL . ' cat ON cat.id = t.idCategory ' .
		'LEFT JOIN ' . 
			BAB_TSKMGR_PROJECTS_TBL . ' p ON p.id = t.idProject ' .
		'LEFT JOIN ' . 
			BAB_TSKMGR_PROJECTS_SPACES_TBL . ' ps ON ps.id = p.idProjectSpace ' .
		'WHERE ' . 
			'ti.idOwner = \'' . $GLOBALS['BAB_SESS_USERID'] . '\' AND ' .
			't.id = ti.idTask AND ' .
			't.endDate > \'' . $sStartDate . '\' AND ' .
			't.startDate < \'' . $sEndDate . '\' ';

	if(!is_null($iTaskFilter) && -1 != $iTaskFilter)
	{
		//iTaskFilter (-1 ==> All, -2 ==> personnal task)
		if(-2 == $iTaskFilter)
		{
			$query .= 'AND ti.isPersonnal = \'' . BAB_TM_YES . '\' ';
		}
		else 
		{
			$query .= 'AND t.idProject = \'' . $iTaskFilter . '\' ';
		}
	}
		
	if(!is_null($iTaskClass) && -1 != $iTaskClass)
	{
		$query .= 'AND t.class = \'' . $iTaskClass . '\' ';
	}
			
	$query .= 
		'GROUP BY ' .
			'sProjectSpaceName ASC, sProjectName ASC, sTaskNumber ASC';

	//bab_debug($query);
	
	//echo $query . '<br />';
	global $babDB;
	return $babDB->db_query($query);
}
//*/

function bab_selectTaskQuery($aFilters)
{
	$query = 
		'SELECT ' . 
			'IFNULL(ps.id, 0) iIdProjectSpace, ' .
			'IFNULL(ps.name, \'\') sProjectSpaceName, ' .
			'IFNULL(p.id, 0) iIdProject, ' .
			'IFNULL(p.name, \'\') sProjectName, ' .
			't.id iIdTask, ' .
			't.taskNumber sTaskNumber, ' .
			't.shortDescription sShortDescription, ' .
			't.class iClass, ' .
		'CASE t.class ' .
			'WHEN \'' . BAB_TM_CHECKPOINT . '\' THEN \'ganttCheckpoint\' ' . 
			'WHEN \'' . BAB_TM_TODO . '\' THEN \'ganttToDo\' ' .
			'ELSE \'\' ' .
		'END AS sAdditionnalClass, ' .
		'CASE t.class ' .			
			'WHEN \'' . BAB_TM_TASK . '\' THEN \'' . bab_translate("Task") . '\' ' .
			'WHEN \'' . BAB_TM_CHECKPOINT . '\' THEN \'' . bab_translate("Checkpoint") . '\' ' .
			'WHEN \'' . BAB_TM_TODO . '\' THEN \'' . bab_translate("ToDo") . '\' ' .
			'ELSE \'???\' ' .
		'END AS sClass, ' .
			't.completion iCompletion, ' .
			't.startDate startDate, ' .
			't.endDate endDate, ' .
			'ti.idOwner idOwner, ' .
			'cat.id iIdCategory, ' .
			'IFNULL(cat.bgColor, \'\' ) sBgColor, ' .
			'IFNULL(cat.color, \'\' ) sColor ' .
		'FROM ' . 
			BAB_TSKMGR_TASKS_INFO_TBL . ' ti, ' .
			BAB_TSKMGR_TASKS_TBL . ' t ' .
		'LEFT JOIN ' . 
			BAB_TSKMGR_CATEGORIES_TBL . ' cat ON cat.id = t.idCategory ' .
		'LEFT JOIN ' . 
			BAB_TSKMGR_PROJECTS_TBL . ' p ON p.id = t.idProject ' .
		'LEFT JOIN ' . 
			BAB_TSKMGR_PROJECTS_SPACES_TBL . ' ps ON ps.id = p.idProjectSpace ' .
		'WHERE ' . 
			't.id = ti.idTask ';

	if(isset($aFilters['iIdProject']))
	{
		$query .= 'AND t.idProject = \'' . (int) $aFilters['iIdProject'] . '\' ';
	}

	if(isset($aFilters['iIdOwner']))
	{
		$query .= 'AND ti.idOwner = \'' . (int) $aFilters['iIdOwner'] . '\' ';
	}

	if(isset($aFilters['sStartDate']))
	{
		$query .= 'AND t.startDate < \'' . $aFilters['sEndDate'] . '\' ';
	}

	if(isset($aFilters['sEndDate']))
	{
		$query .= 'AND t.endDate > \'' . $aFilters['sStartDate'] . '\' ';
	}

	if(isset($aFilters['iTaskClass']))
	{
		$query .= 'AND t.class = \'' . (int) $aFilters['iTaskClass'] . '\' ';
	}

	if(isset($aFilters['isPersonnal']))
	{
		$query .= 'AND ti.isPersonnal = \'' . BAB_TM_YES . '\' ';
	}

	$query .= 
		'GROUP BY ' .
			'sProjectSpaceName ASC, sProjectName ASC, sTaskNumber ASC';

	//bab_debug($query);
	//echo $query . '<br />';
	return $query;
}

function bab_createTaskInfo($iIdTask, $iIdOwner, $iIsPersonnal)
{
	$query = 
		'INSERT INTO ' . BAB_TSKMGR_TASKS_INFO_TBL . ' ' .
			'(' .
				'`id`, ' .
				'`idTask`, `idOwner`, `isPersonnal` ' .
			') ' .
		'VALUES ' . 
			'(\'\', \'' . $iIdTask . '\', \'' . $iIdOwner . '\', \'' . $iIsPersonnal . '\')'; 
	
	//bab_debug($query);

	global $babDB;
	return $babDB->db_query($query);
}


function bab_updateTaskInfo($iIdTask, $iIdOwner, $iIsPersonnal)
{
	$query = 
		'UPDATE ' . 
			BAB_TSKMGR_TASKS_INFO_TBL . ' ' .
		'SET ' .
			'idOwner = \'' . $iIdOwner . '\', ' .
			'isPersonnal = \'' . $iIsPersonnal . '\' ' .
		'WHERE ' .
			'idTask = \'' . $iIdTask . '\'';

	//bab_debug($query);

	global $babDB;
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
			'idUser = \'' . $iIdUser . '\'';
			
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
	$query = 
		'INSERT INTO ' . BAB_TSKMGR_PERSONNAL_TASKS_CONFIGURATION_TBL . ' ' .
			'(' .
				'`id`, ' .
				'`idUser`, `endTaskReminder`, `tasksNumerotation`, `emailNotice` ' .
			') ' .
		'VALUES ' . 
			'(\'\', \'' . $iIdUser . '\', \'' . $aCfg['endTaskReminder'] . '\', \'' . $aCfg['tasksNumerotation'] . '\', \'' . $aCfg['emailNotice'] . '\')'; 
	
	//bab_debug($query);

	global $babDB;
	return $babDB->db_query($query);
}

function bab_updatePersonnalTaskConfiguration($iIdUser, &$aCfg)
{
	$query = 
		'UPDATE ' . 
			BAB_TSKMGR_PERSONNAL_TASKS_CONFIGURATION_TBL . ' ' .
		'SET ' .
			'endTaskReminder = \'' . $aCfg['endTaskReminder'] . '\', ' .
			'tasksNumerotation = \'' . $aCfg['tasksNumerotation'] . '\', ' .
			'emailNotice = \'' . $aCfg['emailNotice'] . '\' ' .
		'WHERE ' .
			'idUser = \'' . $iIdUser . '\'';

	//bab_debug($query);

	global $babDB;
	return $babDB->db_query($query);
}


/*
	$sRefCount == '+ \'1\'' ==> pour ajouter 1
	$sRefCount == '- \'1\'' ==> pour retrancher 1
*/
function bab_updateRefCount($sTblName, $iId, $sRefCount)
{
	$query = 
		'UPDATE ' . 
			$sTblName . ' ' .
		'SET ' .
			'refCount = refCount ' . $sRefCount . ' ' .
		'WHERE ' .
			'id = \'' . $iId . '\'';

	//bab_debug($query);

	global $babDB;
	return $babDB->db_query($query);
}

function bab_deleteAllSpecificFields($sDbFieldName, $sDbFieldValue)
{
	global $babBody, $babDB;
	$query = 
		'SELECT ' .
			'id ' . 
		'FROM ' .
			BAB_TSKMGR_SPECIFIC_FIELDS_BASE_CLASS_TBL . ' ' .
		'WHERE ' . 
			$sDbFieldName . ' =\'' . $sDbFieldValue . '\'';

	//bab_debug($query);
	
	$result = $babDB->db_query($query);
	$iNumRows = $babDB->db_num_rows($result);
	$iIndex = 0;
	
	while($iIndex < $iNumRows && false != ($data = $babDB->db_fetch_assoc($result)))
	{
		$query = 'DELETE FROM ' . BAB_TSKMGR_SPECIFIC_FIELDS_TEXT_CLASS_TBL . ' WHERE id = \'' . $data['id'] . '\''; 
		//bab_debug($query);
		$babDB->db_query($query);
	
		$query = 'DELETE FROM ' . BAB_TSKMGR_SPECIFIC_FIELDS_AREA_CLASS_TBL . ' WHERE id = \'' . $data['id'] . '\''; 
		//bab_debug($query);
		$babDB->db_query($query);
	
		$query = 'DELETE FROM ' . BAB_TSKMGR_SPECIFIC_FIELDS_RADIO_CLASS_TBL . ' WHERE idFldBase = \'' . $data['id'] . '\''; 
		//bab_debug($query);
		$babDB->db_query($query);

		$iIndex++;
	}
	
	$query = 'DELETE FROM ' . BAB_TSKMGR_SPECIFIC_FIELDS_BASE_CLASS_TBL . ' WHERE ' . $sDbFieldName . ' =\'' . $sDbFieldValue . '\''; 
	//bab_debug($query);
	$babDB->db_query($query);
}

function bab_selectWorkingHours($iIdUser, $iWeekDay, &$bHaveWorkingHours)
{
	global $babDB;

	$bHaveWorkingHours = false;
	
	$query = 
		'SELECT ' .
			'wd.weekDay, ' .
			'wh.idUser, ' .
			'LEFT(wh.startHour, 5) startHour, ' .
			'LEFT(wh.endHour, 5) endHour ' .
		'FROM ' .
			BAB_TSKMGR_WEEK_DAYS_TBL . ' wd, ' . 
			BAB_TSKMGR_WORKING_HOURS_TBL . ' wh ' .
		'WHERE ' .
			'wd.weekDay = \'' . $iWeekDay . '\' AND ' .
			'wh.weekDay = wd.weekDay AND ' .
			'wh.idUser = \'' . $iIdUser . '\' ' .
		'ORDER BY ' . 
			'wd.position, ' .
			'wh.startHour';
			
	//bab_debug($query);
	$result = $babDB->db_query($query);
	if(false != $result)
	{
		$bHaveWorkingHours = (0 != $babDB->db_num_rows($result) ? true : false);
	}
	return $result;
}

function bab_insertWorkingHours($iIdUser, $iWeekDay, $sStartHour, $sEndHour)
{
	global $babDB;

	$query = 
		'INSERT INTO ' . BAB_TSKMGR_WORKING_HOURS_TBL . ' ' .
			'(' .
				'`id`, ' .
				'`weekDay`, `idUser`, `startHour`, `endHour`' .
			') ' .
		'VALUES ' . 
			'(\'\', \'' . 
				$iWeekDay . '\', \'' . $iIdUser . '\', \'' . $sStartHour . '\', \'' . $sEndHour . '\')'; 

	//bab_debug($query);
	
	$res = $babDB->db_query($query);
	if(false != $res)
	{
		return $babDB->db_insert_id();
	}
	return false;
}

function bab_deleteAllWorkingHours($iIdUser)
{
	global $babDB;
	$query = 'DELETE FROM '	. BAB_TSKMGR_WORKING_HOURS_TBL . ' WHERE idUser = \'' . $iIdUser . '\'';
	$babDB->db_query($query);
}

function bab_onWorkingHoursChanged($iIdUser, $aWorkingDays)
{
	if(count($aWorkingDays) > 0)
	{
		bab_deleteAllWorkingHours($iIdUser);
		foreach($aWorkingDays as $key => $iWeekDay)
		{
			bab_insertWorkingHours($iIdUser, $iWeekDay, '09:00', '12:00');
			bab_insertWorkingHours($iIdUser, $iWeekDay, '13:00', '18:00');
		}
	}
}

function bab_createDefaultWorkingHours($iIdUser)
{
	global $babDB, $babInstallPath;
	require_once($babInstallPath . 'utilit/calapi.php');

	$sWorkingDays = null;
	bab_calGetWorkingDays($iIdUser, $sWorkingDays);
	$aWorkingDays = explode(',', $sWorkingDays);
	
	foreach($aWorkingDays as $key => $iWeekDay)
	{
		bab_insertWorkingHours($iIdUser, $iWeekDay, '09:00', '12:00');
		bab_insertWorkingHours($iIdUser, $iWeekDay, '13:00', '18:00');
	}
}

function bab_selectAvailableSpecificFieldClassesByProject($iIdProjectSpace, $iIdProject)
{
	global $babBody, $babDB;

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
			'idProjectSpace IN(\'' . $iIdProjectSpace . '\') AND ' .
			'idProject IN(\'' . $iIdProject . '\',\'' . 0 . '\')';
	
	//bab_debug($query);
	return $babDB->db_query($query);
}

function bab_getSpecificFieldClassDefaultValue($iIdSpecificFieldClass, &$sDefaultValue)
{
	global $babBody, $babDB;

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
			'fb.id = \'' . $iIdSpecificFieldClass . '\' AND ' .
			'(ft.isDefaultValue = \'' . BAB_TM_YES . '\' OR fa.isDefaultValue = \'' . BAB_TM_YES . '\' OR frd.isDefaultValue = \'' . BAB_TM_YES . '\')';
			
	//bab_debug($query);

	$result = $babDB->db_query($query);
	$iNumRows = $babDB->db_num_rows($result);
	$iIndex = 0;
	
	if($iIndex < $iNumRows && false != ($data = $babDB->db_fetch_assoc($result)))
	{
		$sDefaultValue = $data['sDefaultValue'];
	}
}

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
			'fb.id = \'' . $iIdSpecificFieldClass . '\'';

	//bab_debug($query);
	return $babDB->db_query($query);
}

function bab_getNextSpecificFieldInstancePosition($iIdTask, &$iPosition)
{
	global $babBody, $babDB;

	$iPosition = 0;

	$query = 
		'SELECT ' .
			'IFNULL(MAX(position), 0) position ' .
		'FROM ' . 
			BAB_TSKMGR_SPECIFIC_FIELDS_INSTANCE_LIST_TBL . ' ' .
		'WHERE ' . 
			'idTask =\'' . $iIdTask . '\'';

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

function bab_createSpecificFieldInstance($iIdTask, $iIdSpecificField)
{
	global $babBody, $babDB;
	
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
				$iIdSpecificField . '\', \'' . $iIdTask . '\', \'' . $sDefaultValue . '\', \'' . $iPosition . '\')'; 

	//bab_debug($query);
	
	$res = $babDB->db_query($query);
	if(false != $res)
	{
		bab_updateRefCount(BAB_TSKMGR_SPECIFIC_FIELDS_BASE_CLASS_TBL, $iIdSpecificField, '+ \'1\'');
		return true;
	}
	return false;
}

function bab_updateSpecificInstanceValue($iIdSpecificFieldInstance, $sValue)
{
	$query = 
		'UPDATE ' . 
			BAB_TSKMGR_SPECIFIC_FIELDS_INSTANCE_LIST_TBL . ' ' .
		'SET ' .
			'value = \'' . $sValue . '\' ' .
		'WHERE ' .
			'id = \'' . $iIdSpecificFieldInstance . '\'';

	//bab_debug($query);

	global $babDB;
	return $babDB->db_query($query);
}

function bab_deleteSpecificFieldInstance($iIdSpecificFieldInstance)
{
	global $babDB;
	
	$iIdSpecificFieldClass = 0;
	
	$result = bab_selectSpecificFieldInstance($iIdSpecificFieldInstance);
	if(false != $result && $babDB->db_num_rows($result) == 1)
	{
		$datas = $babDB->db_fetch_array($result);
		
		$query = 'DELETE FROM '	. BAB_TSKMGR_SPECIFIC_FIELDS_INSTANCE_LIST_TBL . ' WHERE id = \'' . $iIdSpecificFieldInstance . '\'';
		$babDB->db_query($query);
		
		bab_updateRefCount(BAB_TSKMGR_SPECIFIC_FIELDS_BASE_CLASS_TBL, $datas['iIdSpFldClass'], '- \'1\'');
		return true;
	}
	return false;
}

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
		
		$query = 'DELETE FROM '	. BAB_TSKMGR_SPECIFIC_FIELDS_INSTANCE_LIST_TBL . ' WHERE id = \'' . $datas['iIdSpecificFieldInstance'] . '\'';
		$babDB->db_query($query);
		
		bab_updateRefCount(BAB_TSKMGR_SPECIFIC_FIELDS_BASE_CLASS_TBL, $datas['iIdSpFldClass'], '- \'1\'');
	}
}

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
			'si.id = \'' . $iIdSpecificFieldInstance . '\'';
			
	//bab_debug($query);
	return $babDB->db_query($query);
}


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
			'idTask = \'' . $iIdTask . '\'';
			
	//bab_debug($query);
	return $babDB->db_query($query);
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
				$iIdProjectSpace . '\', \'' . $iIdProject . '\', \'' . $iProfil . '\', \'' . $iIdEvent . '\')'; 

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
			'idProjectSpace = \'' . $iIdProjectSpace . '\' AND ' .
			'idProject = \'' . $iIdProject . '\' AND ' .
			'idEvent = \'' . $iIdEvent . '\' AND ' .
			'profil = \'' . $iProfil . '\'';
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
			'idProjectSpace = \'' . $iIdProjectSpace . '\' AND ' .
			'idProject = \'' . 0 . '\'';
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
			'idProjectSpace = \'' . $iIdProjectSpace . '\' AND ' .
			'idProject = \'' . $iIdProject . '\'';
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

function bab_getTaskListFilter($iIdUser, &$aTaskFilters)
{
	global $babDB;

	
	$query = 
		'SELECT ' .
			'* ' .
		'FROM ' . 
			BAB_TSKMGR_TASK_LIST_FILTER_TBL . ' ' .
		'WHERE ' . 
			'idUser =\'' . $iIdUser . '\'';

	//echo $query . '<br />';
	//bab_debug($query);

	$res = $babDB->db_query($query);

	if(false != $res && $babDB->db_num_rows($res) > 0)
	{
		$datas = $babDB->db_fetch_array($res);

		if(false != $datas)
		{
			$aTaskFilters = array('id' => $datas['id'], 'iIdUser' => $datas['idUser'], 
				'iIdProject' => $datas['idProject'], 'iTaskClass' => $datas['iTaskClass']);
		}
	}
	else 
	{
		$aTaskFilters = array('id' => -1, 'iIdUser' => $iIdUser, 'iIdProject' => -1, 'iTaskClass' => -1);
	}
}

function bab_createTaskListFilter($iIdUser, $aTaskFilters)
{
	global $babDB;
	
	$query = 
		'INSERT INTO ' . BAB_TSKMGR_TASK_LIST_FILTER_TBL . ' ' .
			'(' .
				'`id`, ' .
				'`idUser`, `idProject`, `iTaskClass`' .
			') ' .
		'VALUES ' . 
			'(\'\', \'' . 
				$aTaskFilters['iIdUser'] . '\', \'' . $aTaskFilters['iIdProject'] . '\', \'' . $aTaskFilters['iTaskClass'] . '\')'; 

	//bab_debug($query);
	
	return $babDB->db_query($query);
}

function bab_updateTaskListFilter($iIdUser, $aTaskFilters)
{
	$query = 
		'UPDATE ' . 
			BAB_TSKMGR_TASK_LIST_FILTER_TBL . ' ' .
		'SET ' .
			'idProject = \'' . $aTaskFilters['iIdProject'] . '\', ' .
			'iTaskClass = \'' . $aTaskFilters['iTaskClass'] . '\' ' .
		'WHERE ' .
			'idUser = \'' . $iIdUser . '\'';

	//bab_debug($query);

	global $babDB;
	return $babDB->db_query($query);
}

function bab_getCategoriesListQuery($iIdProjectSpace, $iIdProject, $iIdUser)
{
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
			'IF(cat.idProject = \'' . $iIdProject . '\' AND cat.refCount = \'' . 0 . '\', 1, 0) is_deletable ' .
		'FROM ' .
			BAB_TSKMGR_CATEGORIES_TBL . ' cat ' .
		'WHERE ' .
			'idProjectSpace = \'' . $iIdProjectSpace . '\' AND ' .
			'(idProject = \'' . 0 . '\' OR idProject = \'' . $iIdProject . '\') AND ' .
			'idUser = \'' . $iIdUser . '\' ' .
		'GROUP BY cat.name ASC';
		
	return $query;
}

function bab_selectAvailableCategories($iIdProjectSpace, $iIdProject, $iIdUser)
{
	global $babBody, $babDB;

	$query = 
		'SELECT ' .
			'id, ' . 
			'name ' . 
		'FROM ' .
			BAB_TSKMGR_CATEGORIES_TBL . ' ' .
		'WHERE ' . 
			'idProjectSpace IN(\'' . $iIdProjectSpace . '\') AND ' .
			'idProject IN(\'' . $iIdProject . '\',\'' . 0 . '\') AND ' .
			'idUser = \'' . $iIdUser . '\'';
	
	//bab_debug($query);
	return $babDB->db_query($query);
}
?>