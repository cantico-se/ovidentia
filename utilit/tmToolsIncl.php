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

	$sTg = tskmgr_getVariable('tg', '');
	
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

class BAB_ColumnDataSource extends BAB_DataSourceBase
{
	function BAB_ColumnDataSource()
	{
		parent::BAB_DataSourceBase();
		
		$this->aDatas = array(
			array('name' => 'Noureddine', 'description' => 'Big chief', 'date' => '24/06/06'),
			array('name' => 'Paul', 'description' => 'Developer 1', 'date' => '24/06/06'),
			array('name' => 'Laurent', 'description' => 'Developer 2', 'date' => '24/06/06'),
			array('name' => 'Samuel', 'description' => 'Developer 3', 'date' => '24/06/06')
		);
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
		$sPageUrl = ereg_replace('&iPage=[^&.]+', '', $_SERVER['REQUEST_URI']);
		$sPageUrl = ereg_replace('&iNbRowsPerPage=[^&.]+', '', $sPageUrl);
		
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

class BAB_TM_Gantt
{
	var $m_iWidth = '16';
	var $m_iHeight = '19';
	var $m_iBorderLeft = 0;
	var $m_iBorderRight = 0;
	var $m_iBorderTop = 0;
	var $m_iBorderBottom = 0;

	var $m_iUpperLeftPosX = 0;
	var $m_iUpperLeftPosY = 0;
	var $m_iUpperHeight = 0;
	var $m_iUpperWidth = 0;
	var $m_sUpperLeftColor = 'BD6363';
	
	var $m_iMonthPosX = 0;
	var $m_iMonthPosY = 0;
	var $m_iMonthWidth = 0;
	var $m_iMonthHeigth = 0;
	var $m_sBgMonthColor = 'BD6363';
	var $m_sMonthColor = 'FFF';
	var $m_sMonth = '';
	var $m_iCurrMonth = -1;
	
	var $m_iWeekPosX = 0;
	var $m_iWeekPosY = 0;
	var $m_iWeekHeigth = 0;
	var $m_iWeekWidth = 0;
	var $m_sBgWeekColor = 'BD6363';
	var $m_sWeekColor = 'FFF';
	var $m_sWeek = '';
	var $m_iWeekNumber = 0;
	var $m_iStartWeekNumber = 0;
	var $m_iEndWeekNumber = 0;
	
	var $m_iDayPosX = 0;
	var $m_iDayPosY = 0;
	var $m_iDayHeigth = 0;
	var $m_iDayWidth = 0;
	var $m_sBgDayColor = 'BD6363';
	var $m_sDayColor = 'FFF';
	var $m_sDay = '';
	var $m_iStartWeekDay = -1;
	var $m_iMonthDay = -1;
	var $m_iCurrDay = -1;
	var $m_iTotalDaysToDisplay = 49;
	var $m_iDisplayedDays = 0;

	var $m_iTaskWidth = 200;

	var $m_iTaskTitlePosX = 0;
	var $m_iTaskTitlePosY = 0;
	var $m_iTaskTitleHeigth = 0;
	var $m_iTaskTitleWidth = 0;
	var $m_sTaskTitleBgColor = '787878';
	var $m_sTaskTitleColor = 'FFF';
	var $m_sTaskTitle = '';
	
	var $m_iTaskPosX = 0;
	var $m_iTaskPosY = 0;
	var $m_sTaskColor = 'FCC';
	var $m_sTask = '';
	
	var $m_aStartDate = array();
	var $m_aEndDate = array();
	var $m_bIsAltBg = false;
	var $m_sBgColor1 = 'FCC';
	var $m_sBgColor2 = 'CCF';
	
	var $m_result = false;
	var $m_iNbResult = 0;
	
	var $m_iTimeStamp;
	
	var $m_iColumnPosX = 0;
	var $m_iColumnPosY = 0;
	var $m_iColumnHeigth = 0;
	var $m_iColumnWidth = 0;
	var $m_sColumnBgColor = 'FFF';
	
	var $m_iRowPosX = 0;
	var $m_iRowPosY = 0;
	var $m_iRowHeigth = 0;
	var $m_iRowWidth = 0;
	var $m_sRowBgColor = '000';
	
	function BAB_TM_Gantt($sStartDate, $iStartWeekDay = 3)
	{
		$this->m_iTaskPosY = 3 * $this->m_iHeight;
		$this->m_sTask = bab_translate("Tasks");
		
		$this->setDates($sStartDate, $iStartWeekDay);
		
		$this->m_sDate = strtotime($sStartDate);

		$this->m_result = bab_selectOwnedTaskQueryByDate(date("Y-m-d H:i:s", $this->m_aStartDate[0]), 
			date("Y-m-d H:i:s", $this->m_aEndDate[0]));
		
		if(false != $this->m_result)	
		{
			global $babDB;
			$this->m_iNbResult = $babDB->db_num_rows($this->m_result);
		}
	}
	
	function setDates($sStartDate, $iStartWeekDay)
	{
		$this->m_iStartWeekDay = $iStartWeekDay;
		$this->m_aStartDate = getdate(strtotime($sStartDate));
		
		//Pour démarrer à un jour spécifique de la semaine
		if($iStartWeekDay != $this->m_aStartDate['wday'])
		{
			$iGap = 0;
			if($this->m_aStartDate['wday'] < $iStartWeekDay)
			{
				$iGap = $iStartWeekDay - $this->m_aStartDate['wday'];
				
				$this->m_iTimeStamp = mktime( $this->m_aStartDate['hours'], $this->m_aStartDate['minutes'], $this->m_aStartDate['seconds'],
						$this->m_aStartDate['mon'], ($this->m_aStartDate['mday'] + $iGap), $this->m_aStartDate['year']);
				
				$this->m_aStartDate = getdate($this->m_iTimeStamp);
				
			}
			else
			{
				$iGap = $this->m_aStartDate['wday'] - $iStartWeekDay;
				
				$this->m_iTimeStamp = mktime($this->m_aStartDate['hours'], $this->m_aStartDate['minutes'], $this->m_aStartDate['seconds'],
						$this->m_aStartDate['mon'], ($this->m_aStartDate['mday'] - $iGap), $this->m_aStartDate['year']);
						
				$this->m_aStartDate = getdate($this->m_iTimeStamp);
			}
		}
		else
		{
			$this->m_iTimeStamp = mktime($this->m_aStartDate['hours'], $this->m_aStartDate['minutes'], $this->m_aStartDate['seconds'],
					$this->m_aStartDate['mon'], $this->m_aStartDate['mday'], $this->m_aStartDate['year']);
		}

		$iTimeStamp = mktime((int) $this->m_aStartDate['hours'], (int) $this->m_aStartDate['minutes'], (int) $this->m_aStartDate['seconds'],
				$this->m_aStartDate['mon'], ($this->m_aStartDate['mday'] + $this->m_iTotalDaysToDisplay), 
				(int) $this->m_aStartDate['year']);

		$this->m_aEndDate	= getdate($iTimeStamp);
		$this->m_iCurrMonth	= $this->m_aStartDate['mon'];
		$this->m_iMonthDay	= $this->m_aStartDate['mday'] - 1; //The month day is 1 based
		$this->m_iCurrDay	= $this->m_aStartDate['wday'];
		
		$this->m_iWeekNumber = $this->m_iStartWeekNumber = date('W', $this->m_aStartDate[0]);
		$this->m_iEndWeekNumber = date('W', $this->m_aEndDate[0]);
	}
	
	
	
	function getNbDaysInMonth($iMonth, $iYear)
	{
		static $aNbDaysInMonth_leap = array ('1' => 31, '2' => 29, '3' => 31, '4' => 30, '5' => 31, 
			'6' => 30, '7' => 31, '8' => 31, '9' => 30, '10' => 31, '11' => 30, '12' => 31);
		static $aNbDaysInMonth_nonLeap = array ('1' => 31, '2' => 28, '3' => 31, '4' => 30, '5' => 31, 
			'6' => 30, '7' => 31, '8' => 31, '9' => 30, '10' => 31, '11' => 30, '12' => 31);

		if($iMonth >= 1 && $iMonth <= 12)
		{
			$aNbDaysInMonth = ($this->isLeapYear($iYear)) ? $aNbDaysInMonth_leap : $aNbDaysInMonth_nonLeap;
				
			return $aNbDaysInMonth[$iMonth];
		}
		return 0;
	}
	
	function isLeapYear($iYears)
	{
		return ( ($iYears % 4) == 0 && ($iYears % 100) != 0 || ($iYears % 400) == 0 );
	}
	
	
	//
	function getNextMonth()
	{
		if($this->m_iTotalDaysToDisplay > 0)
//		if($this->m_iCurrMonth <= $this->m_aEndDate['mon'])
		{
			$this->m_sMonth = $this->getMonth($this->m_iCurrMonth);
		
			$this->m_iBorderLeft	= 0;
			$this->m_iBorderRight	= 1;
			$this->m_iBorderTop		= 1;
			$this->m_iBorderBottom	= 1;

			$this->m_iMonthHeigth	= $this->m_iHeight - ($this->m_iBorderTop + $this->m_iBorderBottom);

			$this->m_iMonthPosY = 0;
			$this->m_iMonthPosX = ($this->m_iDisplayedDays * $this->m_iWidth) + $this->m_iTaskWidth;

			$iNbDaysInMonth = $this->getNbDaysInMonth($this->m_iCurrMonth, $this->m_aStartDate['year']);
			$iNbDaysInMonth = $iNbDaysInMonth - $this->m_iMonthDay;
			
			if($iNbDaysInMonth < $this->m_iTotalDaysToDisplay)
			{
				$this->m_iTotalDaysToDisplay -= $iNbDaysInMonth;
			}
			else if($iNbDaysInMonth >= $this->m_iTotalDaysToDisplay)
			{
				$iNbDaysInMonth = $this->m_iTotalDaysToDisplay;
				$this->m_iTotalDaysToDisplay = 0;
			}
			
			$this->m_iDisplayedDays += $iNbDaysInMonth;
			$this->m_iMonthWidth = $iNbDaysInMonth * $this->m_iWidth - ($this->m_iBorderLeft + $this->m_iBorderRight);
			
			$this->m_iMonthDay = 0;
			$this->m_iCurrMonth++;
			
			if(12 < $this->m_iCurrMonth)
			{
				$this->m_iCurrMonth = 1;
			}
			
			return true;
		}
		
		$this->m_iTotalDaysToDisplay = $this->m_iDisplayedDays;
		return false;
	}
	
	function getMonth($iMonth)
	{
		static $aMonths = null;

		if(is_null($aMonths))
		{
			$aMonths = array ('1' => bab_translate("January"), '2' => bab_translate("February"), 
				'3' => bab_translate("March"), '4' => bab_translate("April"), '5' => bab_translate("May"), 
				'6' => bab_translate("June"), '7' => bab_translate("July"), '8' => bab_translate("August"),
				'9' => bab_translate("September"), '10' => bab_translate("October"), '11' => bab_translate("November"), 
				'12' => bab_translate("December"));
		}
			
		if($iMonth >= 1 && $iMonth <= 12)
		{
			return $aMonths[$iMonth];
		}
		return '';
	}

	function getDay($iDay)
	{
		static $aDays = array ('0' => 'D', '1' => 'L', '2' => 'M', '3' => 'M', '4' => 'J', 
				'5' => 'V', '6' => 'S');
			
		if($iDay >= 0 && $iDay <= 6)
		{
			return $aDays[$iDay];
		}
		return $iDay;
	}
	
	function getNexDay()
	{
		static $iDisplayedDays = 0;
		
		if($iDisplayedDays < $this->m_iTotalDaysToDisplay)
		{
			$this->m_iBorderLeft	= 0;
			$this->m_iBorderRight	= 1;
			$this->m_iBorderTop		= 0;
			$this->m_iBorderBottom	= 1;

			$this->m_iDayHeigth	= $this->m_iHeight - ($this->m_iBorderTop + $this->m_iBorderBottom);
			$this->m_iDayWidth = $this->m_iWidth - ($this->m_iBorderLeft + $this->m_iBorderRight);

			$aDate = getdate($this->m_iTimeStamp);
			
			$this->m_sDay		= $this->getDay($this->m_iCurrDay);
			$this->m_sMday		= $aDate['mday'];
			$this->m_iDayPosY	= $this->m_iHeight * 2;
			$this->m_iDayPosX	= ($iDisplayedDays * $this->m_iWidth) + $this->m_iTaskWidth;
			$this->m_iCurrDay	= ($this->m_iCurrDay + 1) % 7;

			
			
			$this->m_iColumnPosY = $this->m_iHeight * 3;
			$this->m_iColumnPosX = ($iDisplayedDays * $this->m_iWidth) + $this->m_iTaskWidth;
			$this->m_iColumnHeigth = (($this->m_iNbResult + 1) * $this->m_iHeight) - ($this->m_iBorderTop + $this->m_iBorderBottom);
			$this->m_iColumnWidth =  $this->m_iWidth - ($this->m_iBorderLeft + $this->m_iBorderRight);
//			var $m_sColumnBgColor = 'FFF';
			
			$this->m_iTimeStamp = mktime($aDate['hours'], $aDate['minutes'], $aDate['seconds'], $aDate['mon'], ($aDate['mday'] + 1), $aDate['year']);

			$iDisplayedDays++;
			return true;
		}
		return false;
	}
	
	function getNextWeekNumber()
	{
		static $iProcessedDays = 0;
		if($this->m_iTotalDaysToDisplay > 0)
		{
			$iNbDays = 7;
		
			if($this->m_iWeekNumber == $this->m_iStartWeekNumber && 1 != $this->m_iStartWeekDay)
			{
				//7 == NB days in a week
				//+1 the weekday is zero based
				$iNbDays = 7 - $this->m_iStartWeekDay +1;
			}
			
			if($iNbDays < $this->m_iTotalDaysToDisplay)
			{
				$this->m_iTotalDaysToDisplay -= $iNbDays;
			}
			else if($iNbDays >= $this->m_iTotalDaysToDisplay)
			{
				$iNbDays = $this->m_iTotalDaysToDisplay;
				$this->m_iTotalDaysToDisplay = 0;
			}

			$this->m_iBorderLeft	= 0;
			$this->m_iBorderRight	= 1;
			$this->m_iBorderTop		= 0;
			$this->m_iBorderBottom	= 1;
			$this->m_iWeekHeigth	= $this->m_iHeight  - ($this->m_iBorderTop + $this->m_iBorderBottom);

			$this->m_iWeekPosY = $this->m_iHeight;
			$this->m_iWeekPosX = ($iProcessedDays * $this->m_iWidth) + $this->m_iTaskWidth;
			$this->m_iWeekWidth = $iNbDays * $this->m_iWidth - ($this->m_iBorderLeft + $this->m_iBorderRight);
			$this->m_sWeek = sprintf('%s %02s', bab_translate("Week"), $this->m_iWeekNumber);
			$iProcessedDays += $iNbDays;
			
			$this->m_iWeekNumber++;

			if(52 < $this->m_iWeekNumber)
			{
				$this->m_iWeekNumber = 1;
			}
			return true;
		}
		$this->m_iTotalDaysToDisplay = $iProcessedDays;
		return false;
	}
	
	function getNextTask()
	{
		global $babDB;
		
		if(false != $this->m_result && false != ($datas = $babDB->db_fetch_assoc($this->m_result)))
		{
			$this->m_iTaskPosX = 0;
			$this->m_iTaskPosY = $this->m_iTaskPosY + $this->m_iHeight;
			$this->m_iTaskWidth = 200;
			$this->m_sTaskColor = 'FCC';
			$this->m_sTask = $datas['sTaskNumber'];
			
$this->m_bIsAltBg	= !$this->m_bIsAltBg;
$this->m_sTaskColor = (!$this->m_bIsAltBg) ? $this->m_sBgColor1 : $this->m_sBgColor2;
			
			$aStartDate = getdate(strtotime($datas['startDate']));
			$aEndDate = getdate(strtotime($datas['endDate']));
			$this->_iPosX =  (($aStartDate['yday'] - $this->m_aStartDate['yday']) * $this->m_iWidth) + $this->m_iTaskWidth;
			
$iDayFromBegining = $aStartDate['yday'] - $this->m_aStartDate['yday'];
$iDuration = ($aEndDate['yday'] - $aStartDate['yday'] + 1); //yday is zero based
$iRemainDay = $this->m_iTotalDaysToDisplay - $iDayFromBegining;
			
			$this->_iWidth = (($aEndDate['yday'] - $aStartDate['yday'] + 1) * $this->m_iWidth); //yday is zero based
			if($iDuration > $iRemainDay)
			{
				$this->_iWidth = $iRemainDay * $this->m_iWidth;
			}
			
			//echo 'R ==> ' . $iRemainDay . ' D ==> ' . $iDuration . '<br />';
			
			/*
			if($this->_iWidth > $iRemainWidth)
			{
				$this->_iWidth = $iRemainWidth;
			}
			//*/
			
			$this->m_iRowPosX = $this->m_iTaskWidth;
			$this->m_iRowPosY = ($this->m_iTaskPosY) - $this->m_iBorderBottom;
			$this->m_iRowHeigth = 1;
			$this->m_iRowWidth = $this->m_iTotalDaysToDisplay * $this->m_iWidth;
			$this->m_sRowBgColor = '000';
			
			return true;
		}
		
		return false;
	}
		
	function dummyGetNext()
	{
		return ($this->m_iDummy++ == 0);
	}
	
	function getNextUpperLeft()
	{
		static $i = 0;
		
		$this->m_iBorderLeft	= 1;
		$this->m_iBorderRight	= 1;
		$this->m_iBorderTop		= 1;
		$this->m_iBorderBottom	= 1;
		$this->m_iUpperHeight	= (3 * $this->m_iHeight)  - ($this->m_iBorderTop + $this->m_iBorderBottom);
		$this->m_iUpperWidth	= $this->m_iTaskWidth - ($this->m_iBorderLeft + $this->m_iBorderRight);
		return ($i++ == 0);
	}
	
	function getNextTaskTitle()
	{
		static $i = 0;
		
		$this->m_iTaskTitlePosX = 0;
		$this->m_iTaskTitlePosY = 3 * $this->m_iHeight;
		
		$this->m_iBorderLeft	= 1;
		$this->m_iBorderRight	= 1;
		$this->m_iBorderTop		= 0;
		$this->m_iBorderBottom	= 1;

		$this->m_iTaskTitleHeigth = $this->m_iHeight  - ($this->m_iBorderTop + $this->m_iBorderBottom);
		$this->m_iTaskTitleWidth = $this->m_iTaskWidth - ($this->m_iBorderLeft + $this->m_iBorderRight);
		$this->m_sTaskTitle = bab_translate("Tasks");
		
		$this->m_iRowPosX = $this->m_iTaskWidth;
		$this->m_iRowPosY = (4 * $this->m_iHeight) - $this->m_iBorderBottom;
		$this->m_iRowHeigth = 1;
		$this->m_iRowWidth = $this->m_iTotalDaysToDisplay * $this->m_iWidth;
		$this->m_sRowBgColor = '000';
		
		return ($i++ == 0);
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