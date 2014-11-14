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
require_once "base.php";



class BAB_TM_ConfirmForm
{
	var	$aHiddenField		= array();
	var $sHiddenFieldName	= '';
	var $sHiddenFieldValue	= '';
	var	$sTg				= '';
	var	$sMethod			= 'GET';
	
	var	$sTitle				= '';
	var	$sWarning			= '';
	var	$sMessage			= '';
	var	$sQuestion			= '';
	
	var	$sImg				= 'messagebox_warning.png';
	var	$sOkCaption			= '';
	var	$sOk				= '';
	var	$sCancelCaption		= '';
	var	$sCancel			= '';
	var	$bDisplayCancel		= false;
	
	public function BAB_TM_ConfirmForm()
	{
	}
	
	public function setOkInfo($sIdx, $sCaption)
	{
		$this->sOk			= $sIdx;
		$this->sOkCaption	= $sCaption;
	}
	
	public function setCancelInfo($sIdx, $sCaption)
	{
		$this->sCancel			= $sIdx;
		$this->sCancelCaption	= $sCaption;
		$this->bDisplayCancel	= (0 != strlen($sCaption));
	}
	
	public function setWarning($sWarning)
	{
		$this->sWarning = $sWarning;
	}
		
	public function setMessage($sMessage)
	{
		$this->sMessage = $sMessage;
	}
		
	public function setQuestion($sQuestion)
	{
		$this->sQuestion = $sQuestion;
	}
	
	public function setTitle($sTitle)
	{
		$this->sTitle = $sTitle;
	}
	
	public function setTg($sTg)
	{
		$this->sTg = $sTg;
	}
	
	public function addHiddenField($sName, $sValue)
	{
		$this->aHiddenField[] = array('sName' => $sName, 'sValue' => $sValue);
	}
	
	public function getNextHiddenField()
	{
		$this->sHiddenFieldName = '';
		$this->sHiddenFieldValue = '';
	
		$aDatas = each($this->aHiddenField);
		if(false != $aDatas)
		{
			$this->sHiddenFieldName = bab_toHtml($aDatas['value']['sName']);
			$this->sHiddenFieldValue = bab_toHtml($aDatas['value']['sValue']);
			return true;
		}
		else
		{
			reset($this->aHiddenField);
			return false;
		}
	}

	public function printTemplate()
	{
		return bab_printTemplate($this, 'tmCommon.html', 'confirm');
	}
}


function isNameUsedInProjectAndProjectSpace($sTblName, $iIdProjectSpace, $iIdProject, $iIdObject, $sName)
{
	$sName = str_replace('\\', '\\\\', $sName);
	
	$bIsDefined = isNameUsedInProjectSpace($sTblName, $iIdProjectSpace, $iIdObject, $sName);
	
	if(0 != $iIdProject && false == $bIsDefined)
	{
		global $babDB;
		
		$sIdObject = '';
		if(0 != $iIdObject)
		{
			$sIdObject = ' AND id <> \'' . $babDB->db_escape_string($iIdObject) . '\'';
		}
	
		$query = 
			'SELECT ' . 
				'id, ' .
				'name ' .
			'FROM ' . 
				$sTblName . ' ' .
			'WHERE ' . 
				'idProjectSpace = \'' . $babDB->db_escape_string($iIdProjectSpace) . '\' AND ' .
				'idProject = \'' . $babDB->db_escape_string($iIdProject) . '\' AND ' .
				'name LIKE \'' . $babDB->db_escape_like($sName) . '\' ' .
				$sIdObject;
			
		//bab_debug($query);
		
		$result = $babDB->db_query($query);
		$bIsDefined = (false != $result && 0 == $babDB->db_num_rows($result));
	}
	return $bIsDefined;
}

function isNameUsedInProjectSpace($sTblName, $iIdProjectSpace, $iIdObject, $sName)
{
	global $babDB;

	$sIdObject = '';
	if(0 != $iIdObject)
	{
		$sIdObject = ' AND id <> \'' . $babDB->db_escape_string($iIdObject) . '\'';
	}

	$query = 
		'SELECT ' . 
			'id, ' .
			'name ' .
		'FROM ' . 
			$sTblName . ' ' .
		'WHERE ' . 
			'idProjectSpace = \'' . $babDB->db_escape_string($iIdProjectSpace) . '\' AND ' .
			'name LIKE \'' . $babDB->db_escape_like($sName) . '\'' .
			$sIdObject;
		
	//bab_debug($query);
	
	$result = $babDB->db_query($query);
	return (false != $result && 0 == $babDB->db_num_rows($result));
}

/*
function getVisualisedIdProjectSpaces(&$aIdProjectSpaces)
{
	require_once($GLOBALS['babInstallPath'] . 'admin/acl.php');
	
	$aIdProjectSpaces = bab_getUserIdObjects(BAB_TSKMGR_DEFAULT_PROJECTS_VISUALIZERS_GROUPS_TBL);
	
	$aIdProjects = bab_getUserIdObjects(BAB_TSKMGR_PROJECTS_VISUALIZERS_GROUPS_TBL);
	if(count($aIdProjects) > 0)
	{
		global $babDB;
		
		$query = 
			'SELECT ' . 
				'idProjectSpace ' .
			'FROM ' . 
				BAB_TSKMGR_PROJECTS_TBL . ' ' .
			'WHERE ' . 
				'id IN(' . $babDB->quote($aIdProjects) . ')';
		
		bab_debug($query);
			
		$result = $babDB->db_query($query);
		if(false != $result)
		{
			$iRows = $babDB->db_num_rows($result);
			$iIdx = 0;
			while($iIdx < $iRows && false != ($datas = $babDB->db_fetch_array($result)))
			{
				$iIdx++;
				$aIdProjectSpaces[$datas['idProjectSpace']] = 1;
			}
		}
	}
}
//*/

function add_item_menu($items = array())
{
	global $babBody;

	$sTg = bab_rp('tg', '');
	
	$babBody->addItemMenu(BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_LIST, bab_translate("Projects spaces"), 
		$GLOBALS['babUrlScript'] . '?tg=' . urlencode($sTg) . '&idx=' . urlencode(BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_LIST));
		
	if('usrTskMgr' == $sTg)
	{
		$babBody->addItemMenu(BAB_TM_IDX_DISPLAY_MY_TASK_LIST, bab_translate("My tasks"), 
			$GLOBALS['babUrlScript'] . '?tg=' . urlencode($sTg) . '&idx=' . urlencode(BAB_TM_IDX_DISPLAY_MY_TASK_LIST));
	}

	if(count($items) > 0)
	{
		foreach($items as $key => $value)
		{
			$babBody->addItemMenu($value['idx'], $value['mnuStr'], $value['url']);
		}
	}

	if('admTskMgr' == $sTg)
	{
		$babBody->addItemMenu(BAB_TM_IDX_DISPLAY_PERSONNAL_TASK_RIGHT, bab_translate("Personnals tasks"), 
			$GLOBALS['babUrlScript'] . '?tg=' . urlencode($sTg) . '&idx=' . urlencode(BAB_TM_IDX_DISPLAY_PERSONNAL_TASK_RIGHT));
	}
}

function isFromIdxValid($sFromIdx)
{
	static $aFroms = array(BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_LIST => 0, BAB_TM_IDX_DISPLAY_MY_TASK_LIST => 0, 
		BAB_TM_IDX_DISPLAY_PROJECT_TASK_LIST => 0);
	return isset($aFroms[$sFromIdx]);
}


if (!function_exists('is_a'))
{
   function is_a($object, $class)
   {
       if (!is_object($object))
           return false;
       if (mb_strtolower(get_class($object)) === mb_strtolower($class))
           return true;
       return is_subclass_of($object, $class);
   }
} 
?>