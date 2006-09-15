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


class BAB_DataSourceBase
{
	var $aDatas = array();
	
	function BAB_DataSourceBase()
	{
	}
	
	function getNextItem()
	{
		$datas = each($this->aDatas);
		if(false != $datas)
		{
			return $datas['value'];
		}
		return false;
	}
	
	function count()
	{
		return (count($this->aDatas));
	}
}

class BAB_MySqlDataSource extends BAB_DataSourceBase
{
	var $m_result = false;
	var $m_iCount = 0;
	
	function BAB_MySqlDataSource($query, $iPage, $iNbRowsPerPage)
	{
		parent::BAB_DataSourceBase();
		
		//bab_debug($query);
		
		global $babDB;
		$this->m_result = $babDB->db_query($query);
		if(false != $this->m_result)
		{
			$this->m_iCount = $babDB->db_num_rows($this->m_result);
			
			if(-1 !== $iNbRowsPerPage)
			{
				$query .= ' LIMIT ' . (($iPage - 1) * $iNbRowsPerPage) . ', ' . $iNbRowsPerPage;
				$this->m_result = $babDB->db_query($query);
			}
		}
	}
	
	function count()
	{
		return $this->m_iCount;
	}
	
	function getNextItem()
	{
		if(false != $this->m_result)
		{
			global $babDB;
			return $babDB->db_fetch_assoc($this->m_result);
		}
		return false;
	}
}

class BAB_TaskDS extends BAB_MySqlDataSource
{
	function BAB_TaskDS($query, $iPage, $iNbRowsPerPage)
	{
		parent::BAB_MySqlDataSource($query, $iPage, $iNbRowsPerPage);
	}
	
	function getNextItem()
	{
		$datas = parent::getNextItem();
		if(false != $datas)
		{
			$datas['startDate'] = bab_shortDate(bab_mktime($datas['startDate']), false);
			$datas['endDate'] = bab_shortDate(bab_mktime($datas['endDate']), false);
		}
		return $datas;
	}
}


class BAB_MultiPageBase
{
	var $bIsPrevUrl = false;
	var $bIsNextUrl = false;
	var $bIsPageUrl = false;
	
	var $sPrevPageUrl = '';
	var $sNextPageUrl = '';
	var $sPageUrl = '';
	
	var $sPrevPageText = '';
	var $sNextPageText = '';
	var $sPageText = '';

	var $sTemplateFileName = 'multipage.html';
	var $sMultipageTemplate = 'multipage';
	var $sPaginationTemplate = 'pagination';
	var $sResultPerPage = '';
	var $sStatusLine = '';
	var $sPagination = '';
	
	var $iStartLink = 0;
	var $iEndLink = 0;
	var $iNbLink = 5;
	var $iNumOfPages = 0;
	
	var $iPage = 1;
	var $iNbRowsPerPage;
	var $iTotalNumOfRows = 0;
	
	var $aColumnHeaders = array();
	var $bIsColumnHeaderUrl = false;
	var $sColumnHeaderUrl = '';
	var $sColumnHeaderText = '';
	var $iColumnHeadersCount = 0;
	
	var $bIsAltbg = true;
	var $sColumnData = '';
	
	var $oDataSource = null;
	var $aRow = null;
	
	var $aNbRowsPerPages;
	var $sNbRowPerPageSelected = '';
	
	var $sDisplay;
	
	var $sTg = '';
	var $sIdx = '';
	
	var $aActions = array();
	var $aActionItems = array();
	
	var $m_iDummy = 0;
	
	function BAB_MultiPageBase()
	{
		$this->sPrevPageText = bab_translate("Prev");
		$this->sNextPageText = bab_translate("Next");
		$this->sDisplay = bab_translate("Display");
		
		$this->aNbRowsPerPages = array(
			array('value' => 10, 'text' => '10'),
			array('value' => 20, 'text' => '20'),
			array('value' => 50, 'text' => '50'),
			array('value' => 100, 'text' => '100'),
			array('value' => 200, 'text' => '200'),
			array('value' => 300, 'text' => '300'),
			array('value' => 400, 'text' => '400'),
			array('value' => 500, 'text' => '500'),
			array('value' => -1, 'text' => bab_translate("All")));
			
		$this->sResultPerPage = bab_translate("Result(s) per page");
		
		$this->setColumnDataSource(new BAB_DataSourceBase());
		if(isset($_GET['_onNbRowPerPageChanged']))
		{
			$this->iPage = 1;
		}
		else
		{
			$this->iPage = (int) bab_rp('iPage', 1);	
		}
		
		$this->iNbRowsPerPage = (int) bab_rp('iNbRowsPerPage', 10);
		
		$this->sTg = bab_rp('tg', '');
		$this->sIdx = bab_rp('idx', '');
	}
	
	function getNextColumnHeader()
	{
		$aDatas = each($this->aColumnHeaders);
		
		if(false != $aDatas)
		{
			if($this->bIsColumnHeaderUrl)
			{
				$this->sColumnHeaderUrl = '#';
			}
			
			$this->sColumnHeaderText = $aDatas['value']['sText'];
			return true;
		}
		
		reset($this->aColumnHeaders);
		return false;
	}
	
	function getNextRow()
	{
		if(!is_null($this->oDataSource) && is_a($this->oDataSource, 'BAB_DataSourceBase'))
		{
			$this->aRow = $this->oDataSource->getNextItem();
			
			//bab_debug($this->aRow);
			
			if(false != $this->aRow)
			{
				$this->bIsAltbg = !$this->bIsAltbg;
				return true;
			}
		}
		return false;
	}
	
	function getNextColumnData()
	{
		$aHeaders = each($this->aColumnHeaders);

		if(false != $aHeaders)
		{
			$this->sColumnData = '???';
			if(isset($this->aRow[$aHeaders['value']['sDataSourceFieldName']]))
			{
				$this->sColumnData = $this->aRow[$aHeaders['value']['sDataSourceFieldName']];
			}
			return true;
		}
		reset($this->aColumnHeaders);
		return false;
	}
	
	function getNextNbRow()
	{
		$aDatas = each($this->aNbRowsPerPages);
		if(false != $aDatas)
		{
			$this->iNbRow = $aDatas['value']['value'];
			$this->sNbRow = $aDatas['value']['text'];
			
			$this->sNbRowPerPageSelected = ($this->iNbRowsPerPage == $this->iNbRow) ? 'selected="selected"' : '';
			
			return true;
		}
		return false;
	}
	
	function addColumnHeader($iId, $sText, $sDataSourceFieldName)
	{
		$this->aColumnHeaders[$sDataSourceFieldName] = array('iId' => $iId, 'sText' => $sText, 'sDataSourceFieldName' => $sDataSourceFieldName);
	}
	
	function setColumnDataSource($oDataSource)
	{
		if(is_a($oDataSource, 'BAB_DataSourceBase'))
		{
			$this->oDataSource = $oDataSource;
			$this->iTotalNumOfRows = $oDataSource->count();
		}
	}
	
	function addAction($sId, $sText, $sIcon, $sLink, $aDataSourceFields)
	{
		$this->aActions[] = array('sId' => $sId, 'sText' => $sText, 'sIcon' => $sIcon, 'sLink' => $sLink, 'aDataSourceFields' => $aDataSourceFields);
	}

	function getNextAction()
	{
		$datas = each($this->aActions);
		if(false != $datas)
		{
			$this->aActionItems = $datas['value'];
			$aDataSourceFields = $this->aActionItems['aDataSourceFields'];
			
			foreach($aDataSourceFields as $key => $value)
			{
				if(isset($this->aRow[$value['sDataSourceFieldName']]))
				{
					$this->aActionItems['sLink'] .= '&' . $value['sUrlParamName'] . '=' . $this->aRow[$value['sDataSourceFieldName']];
				}
			}
			return true;				
		}
		reset($this->aActions);
		return false;
	}
	
	/* calcul l'offset de début et de fin */
	function computeStartEndPos()
	{
		$this->iNumOfPages	= 0;
		$this->iEndLink		= 0;
		$this->iStartLink	= 1;

		if( $this->iNbRowsPerPage > 0 && $this->iTotalNumOfRows > $this->iNbRowsPerPage )
		{
			$this->iNumOfPages = ceil($this->iTotalNumOfRows / $this->iNbRowsPerPage);
		}
		else if($this->iTotalNumOfRows == $this->iNbRowsPerPage)
		{
			$this->iPage = $this->iNumOfPages = 1;
		}

		/*
		if($this->iPage > $this->iNumOfPages)
		{
bab_debug('8 heures de train');
			$this->iNumOfPages = $this->iPage - 1;
		}
		//*/
		
		if($this->iPage >= $this->iNbLink)
		{
			$this->iStartLink = $this->iPage - $this->iNbLink;
			if(0 >= $this->iStartLink)
			{
				$this->iStartLink = 1;
			}
		}

		if(($this->iPage + $this->iNbLink) > $this->iNumOfPages)
		{
			$this->iEndLink = $this->iNumOfPages;
		}
		else
		{
			$this->iEndLink = $this->iPage + $this->iNbLink;
		}
	}	

	function buildPrevPageUrl()
	{
		if( $this->iPage > 1 && $this->iPage <= $this->iNumOfPages)
		{
			$iPrevPage = $this->iPage - 1;
			$this->bIsPrevUrl = true;
			$this->sPrevPageUrl	= $this->buildPageUrl($iPrevPage);
			$this->sPrevPageText = bab_translate("Prev");
		}
		else
		{	
			$this->bIsPrevUrl = false;
			$this->iPage = 1;
			$this->sPrevPageText = '';
		}
	}

	function buildNextPageUrl()
	{
		if( $this->iPage >= 1 && $this->iPage < $this->iNumOfPages && 
			(($this->iTotalNumOfRows - ($this->iNbRowsPerPage * $this->iPage)) > 0) 
		  )
		{
			$iNextPage = $this->iPage + 1;
			$this->bIsNextUrl = true;
			$this->sNextPageUrl	= $this->buildPageUrl($iNextPage);
			$this->sNextPageText = bab_translate("Next");
		}
		else
		{
			$this->bIsNextUrl = false;
			$this->sNextPageText = '';
		}
	}
	
	function getNextPage()
	{
		$this->sPageText = $this->iStartLink;

		if($this->iStartLink != $this->iPage)
		{
			$this->bIsPageUrl = true;
			$this->sPageUrl = $this->buildPageUrl($this->iStartLink);
		}
		else
		{
			$this->bIsPageUrl = false;
		}

		if($this->iStartLink <= $this->iEndLink)
		{
			$this->iStartLink++;
			return true;
		}
		else 
		{
			return false;
		}
	}

	/**
	 * Creates an url by concatenating the baseUrl and and the parameters.
	 * The parameters are in an array where the key will be used as the name of the parameter
	 * and the value as the parameters value.
	 * The function checks if the baseUrl already contains parameters in which case the parameters
	 * are appended after a '&'. If baseUrl does not contain any parameter, the parameters are
	 * appended after a '?'.
	 * @param string $baseUrl
	 * @param array $parameters
	 * @return string
	 */
	function createUrl($baseUrl, $parameters)
	{
		$l = array();
		$url = $baseUrl;
		if ($parameters)
		{
			foreach ($parameters as $paramaterName => $paramaterValue)
				$l[] = $paramaterName . '=' . $paramaterValue;
			if (strpos($url, '?') === false)
				$url .= '?';
			else
				$url .= '&';
			$url .= implode('&', $l);
		}
		return $url;	
	}			

	function buildPageUrl($iPageNumber)
	{
		$sPageUrl = ereg_replace('\?tg=[^&.]+', '', $_SERVER['REQUEST_URI']);
		$sPageUrl = ereg_replace('&iPage=[^&.]+', '', $sPageUrl);
		$sPageUrl = ereg_replace('&iNbRowsPerPage=[^&.]+', '', $sPageUrl);
		$sPageUrl = '?tg=' . $this->sTg;
		return htmlentities($sPageUrl .= '&iPage=' . $iPageNumber . '&iNbRowsPerPage=' . $this->iNbRowsPerPage);
	}
	
	function getPagination()
	{
		$this->buildPrevPageUrl();
		$this->buildNextPageUrl();
		return bab_printTemplate($this, $this->sTemplateFileName, $this->sPaginationTemplate);
	}

	function printTemplate()
	{
		$this->iColumnHeadersCount = count($this->aColumnHeaders);

		$this->computeStartEndPos();
		$this->sStatusLine = $this->getStatusLine();
		$this->sPagination = $this->getPagination();

		return bab_printTemplate($this, $this->sTemplateFileName, $this->sMultipageTemplate);
	}
		
	function getStatusLine()
	{
		if($this->iNbRowsPerPage > 0)
		{
			$iNumOfPages = ceil($this->iTotalNumOfRows / $this->iNbRowsPerPage);
			$iStart = (($this->iPage * $this->iNbRowsPerPage) - $this->iNbRowsPerPage) + 1;
			$iEnd   = ($this->iPage * $this->iNbRowsPerPage);
		}

		$sResults = bab_translate("Results");
		$sResult = bab_translate("Result");
		$sPages = bab_translate("Pages");
		$sPage = bab_translate("Page");
		
		//-1 == ALL
		if(-1 != $this->iNbRowsPerPage)
		{
			$sString = bab_translate("Result(s) from %d to %d (%d %s / %d %s )");
			return htmlentities(sprintf($sString, $iStart, $iEnd, $this->iTotalNumOfRows, 
				(((1 < $this->iTotalNumOfRows) ? $sResults : $sResult)), $iNumOfPages, 
				((1 < $iNumOfPages) ? $sPages : $sPage)));
		}
		else
		{
			$sString = bab_translate("All results (%d %s / 1 %s)");
			return htmlentities(sprintf($sString, $this->iTotalNumOfRows, 
				((1 < $this->iTotalNumOfRows) ? $sResults : $sResult), $sPage));
		}
	}
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