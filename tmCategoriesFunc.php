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
	require_once($babInstallPath . 'tmContext.php');
	require_once($babInstallPath . 'tmCategoryClasses.php');
	
function displayCategoriesList()
{
	global $babBody;

	$oTmCtx =& getTskMgrContext();
	$iIdProjectSpace = $oTmCtx->getIdProjectSpace();

	if(0 != $iIdProjectSpace)
	{
		$iIdProject = $oTmCtx->getIdProject();
		$iIdCategory = (int) tskmgr_getVariable('iIdCategory', 0);
		
		class BAB_List extends BAB_BaseFormProcessing
		{
			var $m_db;
			var $m_result;
			var $m_oTmCtx;
	
			var $m_is_altbg;
	
			function BAB_List(& $query)
			{
				parent::BAB_BaseFormProcessing();
	
				$this->m_db	= & $GLOBALS['babDB'];
				$this->m_is_altbg = true;
	
				$this->set_caption('name', bab_translate("Name"));
				$this->set_caption('description', bab_translate("Description"));
				$this->set_caption('uncheckAll', bab_translate("Uncheck all"));
				$this->set_caption('checkAll', bab_translate("Check all"));
				$this->set_caption('deleteField', bab_translate("Click here to delete"));
				$this->set_caption('update', bab_translate("Update"));
				
				$oTmCtx =& getTskMgrContext();
				$this->set_data('iIdProjectSpace', $oTmCtx->getIdProjectSpace());
				$this->set_data('iIdProject', $oTmCtx->getIdProject());
				
				$this->set_data('iIdCategory', 0);
				$this->set_data('sCategoryName', '');
				$this->set_data('sCategoryDescription', '');
				$this->set_data('refCount', 0);

				$this->set_data('sCategoryLink', '#');
				$this->set_data('tg', 'admTskMgr');
				$this->set_data('deleteCategoryIdx', BAB_TM_IDX_DISPLAY_DELETE_CATEGORY_FORM);
				
				$this->m_oTmCtx =& getTskMgrContext();
				
				$this->m_result = $this->m_db->db_query($query);
			}
	
			function nextField()
			{
				$datas = $this->m_db->db_fetch_array($this->m_result);
	
				if(false != $datas)
				{
					$this->m_is_altbg = !$this->m_is_altbg;

					$this->get_data('iIdProjectSpace', $iIdProjectSpace);
					
					$this->set_data('iIdCategory', $datas['iIdCategory']);
					$this->set_data('sCategoryName', $datas['sCategoryName']);
					$this->set_data('sCategoryDescription', $datas['sCategoryDescription']);

					$iIdProjectSpace = $this->m_oTmCtx->getIdProjectSpace();
					$iIdProject = $this->m_oTmCtx->getIdProject();
					
					$this->set_data('sCategoryLink', $GLOBALS['babUrlScript'] . '?tg=admTskMgr' .
						'&iIdProjectSpace=' . $iIdProjectSpace . '&iIdProject=' . $iIdProject .
						'&iIdCategory=' . $datas['iIdCategory'] . '&idx=' . BAB_TM_IDX_DISPLAY_CATEGORY_FORM);
					$this->set_data('refCount', $datas['refCount']);
					return true;
				}
				return false;
			}
		}	

		$itemMenu = array(
			array(
				'idx' => BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_LIST,
				'mnuStr' => bab_translate("Projects spaces"),
				'url' => $GLOBALS['babUrlScript'] . '?tg=admTskMgr&idx=' . BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_LIST),
			array(
				'idx' => BAB_TM_IDX_DISPLAY_CATEGORIES_LIST,
				'mnuStr' => bab_translate("Categories list"),
				'url' => $GLOBALS['babUrlScript'] . '?tg=admTskMgr&iIdProjectSpace=' . $iIdProjectSpace . 
					'&iIdProject=' . $iIdProject . '&idx=' . BAB_TM_IDX_DISPLAY_CATEGORIES_LIST),
			array(
				'idx' => BAB_TM_IDX_DISPLAY_CATEGORY_FORM,
				'mnuStr' => bab_translate("Add a category"),
				'url' => $GLOBALS['babUrlScript'] . '?tg=admTskMgr&iIdProjectSpace=' . $iIdProjectSpace . 
					'&iIdProject=' . $iIdProject .'&iIdCategory=' . $iIdCategory . '&idx=' . 
					BAB_TM_IDX_DISPLAY_CATEGORY_FORM)
			);
		add_item_menu($itemMenu);
		$babBody->title = bab_translate("Categories list");
	
		$query = 
			'SELECT ' .
				'cat.id iIdCategory, ' .
				'cat.name sCategoryName, ' .
				'cat.description sCategoryDescription, ' .
				'cat.refCount refCount ' .
			'FROM ' .
				BAB_TSKMGR_CATEGORIES_TBL . ' cat ' .
			'WHERE ' .
				'idProjectSpace = \'' . $iIdProjectSpace . '\' AND ' .
				'(idProject = \'' . 0 . '\' OR idProject = \'' . $iIdProject . '\')' .
			'GROUP BY cat.name ASC';
		
		//bab_debug($query);	
		$list = & new BAB_List($query);
		
		$babBody->babecho(bab_printTemplate($list, 'tmCommon.html', 'categoriesList'));
	}
	else 
	{
		$GLOBALS['babBody']->msgerror = bab_translate("Invalid project space");
	}	
}

function displayCategoryForm()
{
	global $babBody;
	
	$oTmCtx =& getTskMgrContext();
	
	$iIdProjectSpace = $oTmCtx->getIdProjectSpace();
	$iIdProject = $oTmCtx->getIdProject();
	$iIdCategory = (int) tskmgr_getVariable('iIdCategory', 0);
		
	class BAB_Category extends BAB_BaseFormProcessing
	{
		function BAB_Category($iIdProjectSpace, $iIdProject)
		{
			parent::BAB_BaseFormProcessing();

			$this->set_caption('sName', bab_translate("Name"));
			$this->set_caption('sDescription', bab_translate("Description"));
			$this->set_caption('add', bab_translate("Add"));
			$this->set_caption('delete', bab_translate("Delete"));
			$this->set_caption('modify', bab_translate("Modify"));
			
			$this->set_data('sName', tskmgr_getVariable('sCategoryName', ''));
			$this->set_data('sDescription', tskmgr_getVariable('sCategoryDescription', ''));
			$this->set_data('iIdProjectSpace', $iIdProjectSpace);
			$this->set_data('iIdProject', $iIdProject);
			$this->set_data('iRefCount', 0);
			$this->set_data('addIdx', BAB_TM_IDX_DISPLAY_CATEGORIES_LIST);
			$this->set_data('modifyIdx', BAB_TM_IDX_DISPLAY_CATEGORIES_LIST);
			$this->set_data('deleteIdx', BAB_TM_IDX_DISPLAY_DELETE_CATEGORY_FORM);
			$this->set_data('addAction', BAB_TM_ACTION_ADD_CATEGORY);
			$this->set_data('modifyAction', BAB_TM_ACTION_MODIFY_CATEGORY);
			$this->set_data('deleteAction', '');
			

			$this->set_data('tg', 'admTskMgr');
			$this->set_data('iIdCategory', (int) tskmgr_getVariable('iIdCategory', 0));
			$this->get_data('iIdCategory', $iIdCategory);
			
			
			if(!isset($_POST['iIdCategory']) && !isset($_GET['iIdCategory']))
			{
				$this->set_data('is_creation', true);
			}
			else if( (isset($_GET['iIdCategory']) || isset($_POST['iIdCategory'])) && 0 != $iIdCategory)
			{
				$this->set_data('is_edition', true);
		
				$attributs = array(
					'id' => $iIdCategory, 
					'idProjectSpace' => $iIdProjectSpace, 
					'name' => '',
					'description' => '',
					'refCount' => -1);
					
				$tblWr =& $GLOBALS['BAB_TM_Context']->getTableWrapper();
				$tblWr->setTableName(BAB_TSKMGR_CATEGORIES_TBL);
				
				if(false != ($attributs = $tblWr->load($attributs, 2, 3, 0, 2)))
				{
					$this->set_data('sName', htmlentities($attributs['name'], ENT_QUOTES) );
					$this->set_data('sDescription', htmlentities($attributs['description'], ENT_QUOTES));
					$this->set_data('iRefCount', $attributs['refCount']);
				}
			}
			else
			{
				$this->set_data('is_resubmission', true);
			}
		}
	}
	
	$tab_caption = ($iIdCategory == 0) ? bab_translate("Add a category") : bab_translate("Edition of a category");
	
		$itemMenu = array(
			array(
				'idx' => BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_LIST,
				'mnuStr' => bab_translate("Projects spaces"),
				'url' => $GLOBALS['babUrlScript'] . '?tg=admTskMgr&idx=' . BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_LIST),
			array(
				'idx' => BAB_TM_IDX_DISPLAY_CATEGORIES_LIST,
				'mnuStr' => bab_translate("Categories list"),
				'url' => $GLOBALS['babUrlScript'] . '?tg=admTskMgr&iIdProjectSpace=' . $iIdProjectSpace . 
					'&iIdProject=' . $iIdProject . '&idx=' . BAB_TM_IDX_DISPLAY_CATEGORIES_LIST),
			array(
				'idx' => BAB_TM_IDX_DISPLAY_CATEGORY_FORM,
				'mnuStr' => $tab_caption,
				'url' => $GLOBALS['babUrlScript'] . '?tg=admTskMgr&iIdProjectSpace=' . $iIdProjectSpace . 
					'&iIdProject=' . $iIdProject .'&iIdCategory=' . $iIdCategory . '&idx=' . 
					BAB_TM_IDX_DISPLAY_CATEGORY_FORM)
			);		
			
	add_item_menu($itemMenu);
	$babBody->title = $tab_caption;

	$cat = new BAB_Category($iIdProjectSpace, $iIdProject);
	$babBody->babecho(bab_printTemplate($cat, 'tmCommon.html', 'categoryForm'));
	
}



//POST

function addModifyCategory()
{
	$oTmCtx =& getTskMgrContext();
	$iIdProjectSpace = $oTmCtx->getIdProjectSpace();
	$iIdProject = $oTmCtx->getIdProject();

	$sCategoryName = trim(tskmgr_getVariable('sCategoryName', ''));
	
	if(0 < strlen($sCategoryName))
	{
		$iIdCategory = (int) tskmgr_getVariable('iIdCategory', 0);
		
		$isValid = isCategoryNameValid($iIdCategory, $sCategoryName, $iIdProjectSpace, $iIdProject);
		$sCategoryName = mysql_escape_string($sCategoryName);

		$sCategoryDescription = trim(tskmgr_getVariable('sCategoryDescription', ''));
		$sCategoryDescription = mysql_escape_string($sCategoryDescription);
		
		if($isValid)
		{
			$oTmCtx =& getTskMgrContext();
			$tblWr =& $oTmCtx->getTableWrapper();
			$tblWr->setTableName(BAB_TSKMGR_CATEGORIES_TBL);
			
			$attribut = array(
				'id' => $iIdCategory,
				'name' => $sCategoryName,
				'description' => $sCategoryDescription,
				'idProjectSpace' => $iIdProjectSpace,
				'idProject' => $iIdProject,
			);
			
			if(0 == $iIdCategory)
			{
				$attribut['refCount'] = 0;
				$attribut['created'] = date("Y-m-d H:i:s");
				$attribut['idUserCreated'] = $GLOBALS['BAB_SESS_USERID'];
				
				$skipFirst = true;
				return $tblWr->save($attribut, $skipFirst);
			}
			else
			{
				$attribut['modified'] = date("Y-m-d H:i:s");
				$attribut['idUserModified'] = $GLOBALS['BAB_SESS_USERID'];
				return $tblWr->update($attribut);
			}
		}
		else
		{
			$GLOBALS['babBody']->msgerror = bab_translate("There is an another category with the name") . '\'' . $sCategoryName . '\'';
			$_POST['idx'] = BAB_TM_IDX_DISPLAY_CATEGORY_FORM;
			return false;
		}
	}
	else
	{
		$GLOBALS['babBody']->msgerror = bab_translate("The field name must not be blank");
		$_POST['idx'] = BAB_TM_IDX_DISPLAY_CATEGORY_FORM;
		return false;
	}	
}


function isCategoryNameValid($iIdCategory, $sCategoryName, $iIdProjectSpace, $iIdProject)
{
	$sCategoryName = mysql_escape_string(str_replace('\\', '\\\\', $sCategoryName));
	
	$bIsDefined = isCategoryDefined($iIdCategory, $sCategoryName, $iIdProjectSpace);
	
	if(0 != $iIdProject && false == $bIsDefined)
	{
		$sIdCategory = '';
		if(0 != $iIdCategory)
		{
			$sIdCategory = ' AND id <> \'' . $iIdCategory . '\'';
		}
	
		$query = 
			'SELECT ' . 
				'id, ' .
				'name ' .
			'FROM ' . 
				BAB_TSKMGR_CATEGORIES_TBL . ' ' .
			'WHERE ' . 
				'idProjectSpace = \'' . $iIdProjectSpace . '\' AND ' .
				'name LIKE \'' . $sCategoryName . '\' AND ' .
				'idProject = \'' . $iIdProject . '\'' .
				$sIdCategory;
			
		//bab_debug($query);
		
		$db	= & $GLOBALS['babDB'];
		
		$result = $db->db_query($query);
		$bIsDefined = (false != $result && 0 == $db->db_num_rows($result));
	}
	return $bIsDefined;
}

function isCategoryDefined($iIdCategory, $sCategoryName, $iIdProjectSpace)
{
	$sIdCategory = '';
	if(0 != $iIdCategory)
	{
		$sIdCategory = ' AND id <> \'' . $iIdCategory . '\'';
	}

	$query = 
		'SELECT ' . 
			'id, ' .
			'name ' .
		'FROM ' . 
			BAB_TSKMGR_CATEGORIES_TBL . ' ' .
		'WHERE ' . 
			'idProjectSpace = \'' . $iIdProjectSpace . '\' AND ' .
			'name LIKE \'' . $sCategoryName . '\'' .
			$sIdCategory;
		
	//bab_debug($query);
	
	$db	= & $GLOBALS['babDB'];
	
	$result = $db->db_query($query);
	return (false != $result && 0 == $db->db_num_rows($result));
}
?>