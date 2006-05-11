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
require_once($babInstallPath . 'utilit/tmdefines.php');
require_once($babInstallPath . 'utilit/tmToolsIncl.php');

	require_once($babInstallPath . 'utilit/baseFormProcessingClass.php');
	require_once($babInstallPath . 'utilit/tableWrapperClass.php');


function displayProjectsSpacesList()
{
	$aIdProjectSpaces = null;
	getVisualisedIdProjectSpaces($aIdProjectSpaces);
	
	bab_debug($aIdProjectSpaces);
	
	global $babBody;

	class BAB_List extends BAB_BaseFormProcessing
	{
		var $m_db;
		var $m_result;

		var $m_is_altbg;

		function BAB_List(& $query)
		{
			parent::BAB_BaseFormProcessing();

			$this->m_db	= & $GLOBALS['babDB'];
			$this->m_is_altbg = true;

			$this->set_caption('name', bab_translate("Name"));
			$this->set_caption('description', bab_translate("Description"));
			$this->set_data('url', $GLOBALS['babUrlScript'] . '?tg=userTskMgr&idx=' . BAB_TM_IDX_DISPLAY_PROJECT_SPACE_MENU);
			$this->set_data('isLink', false);
			$this->set_data('name', '');
			$this->set_data('description', '');

			$this->m_result = $this->m_db->db_query($query);
		}

		function nextProjectSpace()
		{
			$data = $this->m_db->db_fetch_array($this->m_result);

			if(false != $data)
			{
				$this->m_is_altbg = !$this->m_is_altbg;
				$this->set_data('id', $data['id']);
				$this->set_data('name', htmlentities($data['name'], ENT_QUOTES));
				$this->set_data('description', htmlentities($data['description'], ENT_QUOTES));
				return true;
			}
			return false;
		}
	}

	$babBody->title = bab_translate("Projects spaces list");
	
	$query = 
		'SELECT ' .
			'id, ' . 
			'name, ' . 
			'description ' .
		'FROM ' .
			BAB_TSKMGR_PROJECTS_SPACES_TBL . ' ' .
		'WHERE ' . 
			'id IN(\'' . implode('\',\'', array_keys($aIdProjectSpaces)) . '\')';
	
	$list = new BAB_List($query);
	
	$babBody->babecho(bab_printTemplate($list, 'tmUser.html', 'projectSpaceList'));	
}


/* main */
if( count(bab_getUserIdObjects(BAB_TSKMGR_DEFAULT_PROJECTS_VISUALIZERS_GROUPS_TBL)) < 0 && 
	count(bab_getUserIdObjects(BAB_TSKMGR_PROJECTS_VISUALIZERS_GROUPS_TBL) < 0 )			)
{
	$babBody->msgerror = bab_translate("Access denied");
	return;
}


/* main */
$action = isset($_POST['action']) ? $_POST['action'] : 
	(isset($_GET['action']) ? $_GET['action'] :  
	(isset($_POST[BAB_TM_ACTION_SET_RIGHT]) ? BAB_TM_ACTION_SET_RIGHT : '???')
	);

//bab_debug('action ==> ' . $action);

switch($action)
{

}


$idx = isset($_POST['idx']) ? $_POST['idx'] : (isset($_GET['idx']) ? $_GET['idx'] : BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_LIST);

//bab_debug('idx ==> ' . $idx);

switch($idx)
{
	case BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_LIST:
		displayProjectsSpacesList();
		break;
}
$babBody->setCurrentItemMenu($idx);
?>