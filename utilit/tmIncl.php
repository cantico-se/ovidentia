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
		while($iIndex < $iNumRows && false != ($datas = $babDB->db_fetch_assoc($res)))
		{
			$aProject = array('id' => $datas['id'], 'name' => $datas['name'], 'description' => $datas['description'], 
				'isLocked' => $datas['isLocked'], 'state' => $datas['state']);
			return true;
		}
	}
	return false;
}

function bab_deleteProject($iIdProject)
{
	global $babBody, $babDB;

	bab_deleteAllTask($iIdProject);
	
	aclDelete(BAB_TSKMGR_PROJECTS_MANAGERS_GROUPS_TBL, $iIdProject);
	aclDelete(BAB_TSKMGR_PROJECTS_SUPERVISORS_GROUPS_TBL, $iIdProject);
	aclDelete(BAB_TSKMGR_PROJECTS_VISUALIZERS_GROUPS_TBL, $iIdProject);

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
	$babDB->db_query($query);
}

function bab_createProjectConfiguration($aConfiguration)
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
				$aConfiguration['idProjectSpace'] . '\', \'' . $aConfiguration['tskUpdateByMgr'] . '\', \'' . 
				$aConfiguration['endTaskReminder'] . '\', \'' . $aConfiguration['tasksNumerotation'] . '\', \'' . 
				$aConfiguration['emailNotice'] . '\', \'' . $aConfiguration['faqUrl'] . '\')'; 

	//bab_debug($query);
	return $babDB->db_query($query);
}

function bab_getProjectConfiguration($iIdProjectSpace, $iIdProject, &$aConfiguration)
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
			'id =\'' . $iIdProject . '\' AND ' .
			'idProjectSpace =\'' . $iIdProjectSpace . '\'';
	
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
			BAB_TSKMGR_PROJECTS_SPACES_TBL . ' ' .
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
	bab_debug('isProjectDeletable this function must be implemented');
	return true;
}





//Task functions
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
		aclDelete(BAB_TSKMGR_TASK_RESPONSIBLE_GROUPS_TBL, $data['id']);
	}

	$query = 'DELETE FROM ' . BAB_TSKMGR_TASKS_COMMENTS_TBL . ' WHERE idProject = \'' . $iIdProject . '\'';
	$babDB->db_query($query);

	$query = 'DELETE FROM ' . BAB_TSKMGR_TASKS_TBL . ' WHERE idProject = \'' . $iIdProject . '\'';
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
	}
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
			'id = \'' . $id . '\'';

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
	}
	
	$query = 'DELETE FROM ' . BAB_TSKMGR_SPECIFIC_FIELDS_BASE_CLASS_TBL . ' WHERE ' . $sDbFieldName . ' =\'' . $sDbFieldValue . '\''; 
	//bab_debug($query);
	$babDB->db_query($query);
}

?>