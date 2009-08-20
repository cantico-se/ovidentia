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
	require_once($babInstallPath . 'utilit/tmToolsIncl.php');
	require_once($babInstallPath . 'utilit/tmIncl.php');

function displayCategoriesList($iIdProjectSpace, $iIdProject, $iIdUser)
{
	global $babBody;

	if(/*0 != $iIdProjectSpace*/1)
	{
		$iIdCategory = (int) bab_rp('iIdCategory', 0);
		
		class BAB_List extends BAB_BaseFormProcessing
		{
			var $m_db;
			var $m_result;
			var $m_oTmCtx;
	
			var $m_is_altbg;
	
			function BAB_List($query)
			{
				parent::BAB_BaseFormProcessing();
	
				$this->m_db	= & $GLOBALS['babDB'];
				$this->m_is_altbg = true;
	
				$this->set_caption('name', bab_translate("Name"));
				$this->set_caption('description', bab_translate("Description"));
				$this->set_caption('color', bab_translate("Color"));
				$this->set_caption('uncheckAll', bab_translate("Uncheck all"));
				$this->set_caption('checkAll', bab_translate("Check all"));
				$this->set_caption('deleteField', bab_translate("Click here to delete"));
				$this->set_caption('update', bab_translate("Update"));
				
				$oTmCtx =& getTskMgrContext();
				$this->set_data('iIdProjectSpace', (int) $oTmCtx->getIdProjectSpace());
				$this->set_data('iIdProject', (int) $oTmCtx->getIdProject());
				
				$this->set_data('preview', bab_translate("Preview"));
				$this->set_data('iIdCategory', 0);
				$this->set_data('sCategoryName', '');
				$this->set_data('sCategoryDescription', '');
				$this->set_data('refCount', 0);

				$this->set_data('sCategoryLink', '#');
				$this->set_data('tg', bab_rp('tg', ''));
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

					$this->set_data('iIdCategory', bab_toHtml($datas['iIdCategory']));
					$this->set_data('sCategoryName', bab_toHtml($datas['sCategoryName']));
					$this->set_data('sBgColor', bab_toHtml($datas['sBgColor']));
					$this->set_data('sColor', bab_toHtml($datas['sColor']));
					$this->set_data('sCategoryDescription', bab_toHtml($datas['sCategoryDescription']));
					$this->set_data('bIsDeletable', ($datas['is_deletable'] == 1));

					$iIdProjectSpace = (int) $this->m_oTmCtx->getIdProjectSpace();
					$iIdProject = (int) $this->m_oTmCtx->getIdProject();
					
					$tg = bab_rp('tg', '');
					
					$this->set_data('sCategoryLink', bab_toHtml($GLOBALS['babUrlScript'] . '?tg=' . urlencode($tg) .
						'&iIdProjectSpace=' . urlencode($iIdProjectSpace) . '&iIdProject=' . urlencode($iIdProject) .
						'&iIdUser=' . urlencode($datas['iIdUser']) . '&iIdCategory=' . urlencode($datas['iIdCategory']) . 
						'&idx=' . urlencode(BAB_TM_IDX_DISPLAY_CATEGORY_FORM)));
						
					$this->set_data('refCount', bab_toHtml($datas['refCount']));
					return true;
				}
				return false;
			}
		}	
		$tg = bab_rp('tg', '');
					
		$itemMenu = array(
			array(
				'idx' => BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_LIST,
				'mnuStr' => bab_translate("Projects spaces"),
				'url' => $GLOBALS['babUrlScript'] . '?tg=' . urlencode($tg) . '&idx=' . urlencode(BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_LIST)));

		$itemMenu[] = array(
				'idx' => BAB_TM_IDX_DISPLAY_CATEGORIES_LIST,
				'mnuStr' => bab_translate("Categories list"),
				'url' => $GLOBALS['babUrlScript'] . '?tg=' . urlencode($tg) . '&iIdProjectSpace=' . urlencode($iIdProjectSpace) . 
					'&iIdProject=' . urlencode($iIdProject) . '&iIdUser=' . urlencode($iIdUser) . 
					'&idx=' . urlencode(BAB_TM_IDX_DISPLAY_CATEGORIES_LIST));
		$itemMenu[] = array(
				'idx' => BAB_TM_IDX_DISPLAY_CATEGORY_FORM,
				'mnuStr' => bab_translate("Add a category"),
				'url' => $GLOBALS['babUrlScript'] . '?tg=' . urlencode($tg) . '&iIdProjectSpace=' . urlencode($iIdProjectSpace) . 
					'&iIdProject=' . urlencode($iIdProject) . '&iIdUser=' . urlencode($iIdUser) . 
					'&idx=' . urlencode(BAB_TM_IDX_DISPLAY_CATEGORY_FORM));
					
		add_item_menu($itemMenu);
		$babBody->title = bab_toHtml(bab_translate("Categories list"));
	
		
		//bab_debug($query);	
		$list =& new BAB_List(bab_getCategoriesListQuery($iIdProjectSpace, $iIdProject, $iIdUser));
		$list->raw_2_html(BAB_RAW_2_HTML_CAPTION);
		$list->raw_2_html(BAB_RAW_2_HTML_DATA);
		
		$babBody->babecho(bab_printTemplate($list, 'tmCommon.html', 'categoriesList'));
	}
	else 
	{
		$GLOBALS['babBody']->msgerror = bab_toHtml(bab_translate("Invalid project space"));
	}	
}

function displayCategoryForm()
{
	global $babBody;
	
	$oTmCtx =& getTskMgrContext();
	
	$iIdProjectSpace = (int) $oTmCtx->getIdProjectSpace();
	$iIdProject = (int) $oTmCtx->getIdProject();
	$iIdCategory = (int) bab_rp('iIdCategory', 0);
	$iIdUser = (int) bab_rp('iIdUser', 0);
		
	class BAB_Category extends BAB_BaseFormProcessing
	{
		function BAB_Category($iIdProjectSpace, $iIdProject, $iIdUser)
		{
			parent::BAB_BaseFormProcessing();

			$this->set_caption('sName', bab_translate("Name"));
			$this->set_caption('sDescription', bab_translate("Description"));
			$this->set_caption('add', bab_translate("Add"));
			$this->set_caption('delete', bab_translate("Delete"));
			$this->set_caption('modify', bab_translate("Modify"));
			$this->set_caption('sPreview', bab_translate("Preview"));
			
			$this->set_data('sName', bab_rp('sCategoryName', ''));
			$this->set_data('sDescription', bab_rp('sCategoryDescription', ''));
			$this->set_data('sColor', bab_rp('sColor', ''));
			$this->set_data('iIdProjectSpace', $iIdProjectSpace);
			$this->set_data('iIdProject', $iIdProject);
			$this->set_data('iIdUser', $iIdUser);
			$this->set_data('iRefCount', 0);
			$this->set_data('addIdx', BAB_TM_IDX_DISPLAY_CATEGORIES_LIST);
			$this->set_data('modifyIdx', BAB_TM_IDX_DISPLAY_CATEGORIES_LIST);
			$this->set_data('delIdx', BAB_TM_IDX_DISPLAY_DELETE_CATEGORY_FORM);
			$this->set_data('addAction', BAB_TM_ACTION_ADD_CATEGORY);
			$this->set_data('modifyAction', BAB_TM_ACTION_MODIFY_CATEGORY);
			$this->set_data('delAction', '');
			
			$this->set_data('sColorTxt', bab_translate("Text color"));
			$this->set_data('sBgColorTxt', bab_translate("Background color"));
			$this->set_data('sPreview', bab_translate("Preview"));
			$this->set_data('sColor', 'FFF');
			$this->set_data('sBgColor', '000');

			$this->set_data('tg', bab_rp('tg', ''));
			$this->set_data('iIdCategory', (int) bab_rp('iIdCategory', 0));
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
					'idProject' => -1, 
					'idUser' => -1, 
					'name' => '',
					'description' => '',
					'color' => '',
					'bgColor' => '',
					'refCount' => -1);
					
				$tblWr =& $GLOBALS['BAB_TM_Context']->getTableWrapper();
				$tblWr->setTableName(BAB_TSKMGR_CATEGORIES_TBL);
				
				if(false != ($attributs = $tblWr->load($attributs, 2, 7, 0, 2)))
				{
					$this->set_data('sName', $attributs['name']);
					$this->set_data('sDescription', $attributs['description']);
					$this->set_data('iRefCount', $attributs['refCount']);
					$this->set_data('bIsDeletable', ($attributs['refCount'] == 0 && $attributs['idProject'] == $iIdProject));
					$this->set_data('bIsModifiable', ($attributs['idProject'] == $iIdProject));
					$this->set_data('sColor', $attributs['color']);
					$this->set_data('sBgColor', $attributs['bgColor']);
				}
			}
			else
			{
				$this->set_data('is_resubmission', true);
			}
		}
	}
	
	$tab_caption = ($iIdCategory == 0) ? bab_translate("Add a category") : bab_translate("Edition of a category");
	
	$tg = bab_rp('tg', '');
	
	$itemMenu = array(
		array(
			'idx' => BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_LIST,
			'mnuStr' => bab_translate("Projects spaces"),
			'url' => $GLOBALS['babUrlScript'] . '?tg=' . urlencode($tg) . '&idx=' . urlencode(BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_LIST)));

	$itemMenu[] = array(
		'idx' => BAB_TM_IDX_DISPLAY_CATEGORIES_LIST,
		'mnuStr' => bab_translate("Categories list"),
		'url' => $GLOBALS['babUrlScript'] . '?tg=' . urlencode($tg) . '&iIdProjectSpace=' . urlencode($iIdProjectSpace) . 
			'&iIdProject=' . urlencode($iIdProject) . '&iIdUser=' . urlencode($iIdUser) . 
			'&idx=' . urlencode(BAB_TM_IDX_DISPLAY_CATEGORIES_LIST));
			
	$itemMenu[] = array(
		'idx' => BAB_TM_IDX_DISPLAY_CATEGORY_FORM,
		'mnuStr' => $tab_caption,
		'url' => $GLOBALS['babUrlScript'] . '?tg=' . urlencode($tg) . '&iIdProjectSpace=' . urlencode($iIdProjectSpace) . 
			'&iIdProject=' . urlencode($iIdProject) . '&iIdUser=' . urlencode($iIdUser) . '&iIdCategory=' . urlencode($iIdCategory) . 
			'&idx=' . urlencode(BAB_TM_IDX_DISPLAY_CATEGORY_FORM));		
			
	add_item_menu($itemMenu);
	$babBody->title = bab_toHtml($tab_caption);

	$cat = new BAB_Category($iIdProjectSpace, $iIdProject, $iIdUser);
	$cat->raw_2_html(BAB_RAW_2_HTML_CAPTION);
	$cat->raw_2_html(BAB_RAW_2_HTML_DATA);
	$babBody->babecho(bab_printTemplate($cat, 'tmCommon.html', 'categoryForm'));
	
}

function displayDeleteCategoryForm()
{
	global $babBody;

	$aDeletableObjects = bab_rp('aDeletableObjects', array());

	$bf =& new BAB_BaseFormProcessing();
	$bf->set_data('idx', BAB_TM_IDX_DISPLAY_CATEGORIES_LIST);
	$oTmCtx =& getTskMgrContext();
	$bf->set_data('iIdProjectSpace', (int) $oTmCtx->getIdProjectSpace());
	$bf->set_data('iIdProject', (int) $oTmCtx->getIdProject());
	$bf->set_data('tg', bab_rp('tg', ''));

	$aIdCategories = bab_getCategoriesName($aDeletableObjects, true);
	
	if(count($aIdCategories) > 0)
	{	
		$title = '';
		foreach($aIdCategories as $key => $aItem)
		{
			$title .= "\n"."-". $aItem['sCategoryName'];
			$items[] = $aItem['iIdCategory'];
		}
	
		$bf->set_data('action', BAB_TM_ACTION_DELETE_CATEGORY);
		$bf->set_data('objectName', 'sDeletableObjects');
		$bf->set_data('iIdObject', implode(',', array_unique($items)));

		if(count($items) > 1)
		{
			$babBody->title = bab_toHtml(bab_translate("Delete categories"));
			$bf->set_caption('warning', bab_translate("This action will delete those categories and all references"));
		}
		else
		{
			$babBody->title = bab_toHtml(bab_translate("Delete category"));
			$bf->set_caption('warning', bab_translate("This action will delete the category and all references"));
		}
		
		$bf->set_caption('message', bab_translate("Continue ?"));
		$bf->set_caption('title', $title);
		$bf->set_caption('yes', bab_translate("Yes"));
		$bf->set_caption('no', bab_translate("No"));
	}
	else 
	{
		$bf->set_caption('warning', bab_translate("There is nothing to delete"));
		$bf->set_caption('message', bab_translate("Continue"));
		$bf->set_caption('title', '');
		$bf->set_caption('yes', bab_translate("Yes"));
		$bf->set_caption('no', bab_translate("No"));
		$babBody->title = bab_toHtml(bab_translate("Delete category"));
	}
	
	$bf->raw_2_html(BAB_RAW_2_HTML_CAPTION, BAB_HTML_ENTITIES | BAB_HTML_BR);
	$bf->raw_2_html(BAB_RAW_2_HTML_DATA);
	$babBody->babecho(bab_printTemplate($bf, 'tmCommon.html', 'warningyesno'));
}

//POST

function addModifyCategory()
{
	$oTmCtx =& getTskMgrContext();
	$iIdProjectSpace = $oTmCtx->getIdProjectSpace();
	$iIdProject = $oTmCtx->getIdProject();

	$sCategoryName = trim(bab_rp('sCategoryName', ''));
	$sColor = bab_rp('sColor', '');
	$sBgColor = bab_rp('sBgColor', '');
	$iIdUser = bab_rp('iIdUser', 0);
	
	if(0 < mb_strlen($sCategoryName))
	{
		$iIdCategory = (int) bab_rp('iIdCategory', 0);
		
		$isValid = isNameUsedInProjectAndProjectSpace(BAB_TSKMGR_CATEGORIES_TBL, $iIdProjectSpace, $iIdProject, $iIdCategory, $sCategoryName);
		$sCategoryName = $sCategoryName;

		$sCategoryDescription = trim(bab_rp('sCategoryDescription', ''));
		
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
				'idUser' => $iIdUser,
				'bgColor' => $sBgColor,
				'color' => $sColor,
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
		unset($_POST['iIdCategory']);
		return false;
	}	
}

function deleteCategory()
{
	bab_debug('deleteCategory ==> il manque les babIsAccessValid');
	
	$sDeletableObjects = trim(bab_rp('sDeletableObjects', ''));
	
	$aIdCategoryToDelete = explode(',', $sDeletableObjects);
	
	if(is_array($aIdCategoryToDelete) && count($aIdCategoryToDelete) > 0)
	{
		$oTmCtx =& getTskMgrContext();
		$tblWr =& $oTmCtx->getTableWrapper();
		$tblWr->setTableName(BAB_TSKMGR_CATEGORIES_TBL);

		foreach($aIdCategoryToDelete as $key => $id)
		{
			$aAttribut = array('id' => $id, 'idProjectSpace' => -1, 'idProject' => -1, 'refCount' => -1);
			
			$aAttribut = $tblWr->load($aAttribut, 0, count($aAttribut), 0, 1);
			
			if(false !== $aAttribut && 0 == $aAttribut['refCount'])
			{
				$tblWr->delete($aAttribut, 0, 1);
			}
		}
	}	
}
?>