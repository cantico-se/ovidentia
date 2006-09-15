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


function isNameUsedInProjectAndProjectSpace($sTblName, $iIdProjectSpace, $iIdProject, $iIdObject, $sName)
{
	$sName = mysql_escape_string(str_replace('\\', '\\\\', $sName));
	
	$bIsDefined = isNameUsedInProjectSpace($sTblName, $iIdProjectSpace, $iIdObject, $sName);
	
	if(0 != $iIdProject && false == $bIsDefined)
	{
		$sIdObject = '';
		if(0 != $iIdObject)
		{
			$sIdObject = ' AND id <> \'' . $iIdObject . '\'';
		}
	
		$query = 
			'SELECT ' . 
				'id, ' .
				'name ' .
			'FROM ' . 
				$sTblName . ' ' .
			'WHERE ' . 
				'idProjectSpace = \'' . $iIdProjectSpace . '\' AND ' .
				'idProject = \'' . $iIdProject . '\' AND ' .
				'name LIKE \'' . $sName . '\' ' .
				$sIdObject;
			
		//bab_debug($query);
		
		$db	= & $GLOBALS['babDB'];
		
		$result = $db->db_query($query);
		$bIsDefined = (false != $result && 0 == $db->db_num_rows($result));
	}
	return $bIsDefined;
}

function isNameUsedInProjectSpace($sTblName, $iIdProjectSpace, $iIdObject, $sName)
{
	$sIdObject = '';
	if(0 != $iIdObject)
	{
		$sIdObject = ' AND id <> \'' . $iIdObject . '\'';
	}

	$query = 
		'SELECT ' . 
			'id, ' .
			'name ' .
		'FROM ' . 
			$sTblName . ' ' .
		'WHERE ' . 
			'idProjectSpace = \'' . $iIdProjectSpace . '\' AND ' .
			'name LIKE \'' . $sName . '\'' .
			$sIdObject;
		
	//bab_debug($query);
	
	$db	= & $GLOBALS['babDB'];
	
	$result = $db->db_query($query);
	return (false != $result && 0 == $db->db_num_rows($result));
}

function getVisualisedIdProjectSpaces(&$aIdProjectSpaces)
{
	require_once($GLOBALS['babInstallPath'] . 'admin/acl.php');
	
	$aIdProjectSpaces = bab_getUserIdObjects(BAB_TSKMGR_DEFAULT_PROJECTS_VISUALIZERS_GROUPS_TBL);
	
	$aIdProjects = bab_getUserIdObjects(BAB_TSKMGR_PROJECTS_VISUALIZERS_GROUPS_TBL);
	if(count($aIdProjects) > 0)
	{
		$query = 
			'SELECT ' . 
				'idProjectSpace ' .
			'FROM ' . 
				BAB_TSKMGR_PROJECTS_TBL . ' ' .
			'WHERE ' . 
				'id IN(\'' . implode('\',\'', array_keys($aIdProjects)) . '\')';
			
		$db	= & $GLOBALS['babDB'];
		
		$result = $db->db_query($query);
		if(false != $result)
		{
			$iRows = $db->db_num_rows($result);
			$iIdx = 0;
			while($iIdx < $iRows && false != ($datas = $db->db_fetch_array($result)))
			{
				$iIdx++;
				$aIdProjectSpaces[$datas['idProjectSpace']] = 1;
			}
		}
	}
}

function add_item_menu($items = array())
{
	global $babBody;

	$sTg = bab_rp('tg', '');
	
	$babBody->addItemMenu(BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_LIST, bab_translate("Projects spaces"), 
		$GLOBALS['babUrlScript'] . '?tg=' . $sTg . '&idx=' . BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_LIST);
		
	if('usrTskMgr' == $sTg)
	{
		$babBody->addItemMenu(BAB_TM_IDX_DISPLAY_TASK_LIST, bab_translate("Tasks list"), 
			$GLOBALS['babUrlScript'] . '?tg=' . $sTg . '&idx=' . BAB_TM_IDX_DISPLAY_TASK_LIST);
	}

	if(count($items) > 0)
	{
		foreach($items as $key => $value)
		{
			$babBody->addItemMenu($value['idx'], $value['mnuStr'], $value['url']);
		}
	}

	$babBody->addItemMenu(BAB_TM_IDX_DISPLAY_MENU, bab_translate("Option(s)"), 
		$GLOBALS['babUrlScript'] . '?tg=' . $sTg . '&idx=' . BAB_TM_IDX_DISPLAY_MENU);
		
	if('admTskMgr' == $sTg)
	{
		$babBody->addItemMenu(BAB_TM_IDX_DISPLAY_PERSONNAL_TASK_RIGHT, bab_translate("Personnal task"), 
			$GLOBALS['babUrlScript'] . '?tg=' . $sTg . '&idx=' . BAB_TM_IDX_DISPLAY_PERSONNAL_TASK_RIGHT);
	}
}

function isFromIdxValid($sFromIdx)
{
	static $aFroms = array(BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_LIST => 0, BAB_TM_IDX_DISPLAY_TASK_LIST => 0);
	return isset($aFroms[$sFromIdx]);
}


if (!function_exists('is_a'))
{
   function is_a($object, $class)
   {
       if (!is_object($object))
           return false;
       if (strtolower(get_class($object)) === strtolower($class))
           return true;
       return is_subclass_of($object, $class);
   }
} 
?>