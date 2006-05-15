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
	require_once($babInstallPath . 'tmSpecificFieldsClasses.php');
	require_once($babInstallPath . 'utilit/tmToolsIncl.php');
		
	
	
	
	
function displaySpecificFieldList()
{
	global $babBody;

	$oTmCtx =& getTskMgrContext();
	$iIdProjectSpace = $oTmCtx->getIdProjectSpace();

	if(0 != $iIdProjectSpace)
	{
		$iIdProject = $oTmCtx->getIdProject();
		
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
				$this->set_caption('type', bab_translate("Field type"));
				$this->set_caption('uncheckAll', bab_translate("Uncheck all"));
				$this->set_caption('checkAll', bab_translate("Check all"));
				$this->set_caption('deleteField', bab_translate("Click here to delete"));
				$this->set_caption('update', bab_translate("Update"));
				
				$oTmCtx =& getTskMgrContext();
				$this->set_data('iIdProjectSpace', $oTmCtx->getIdProjectSpace());
				$this->set_data('iIdProject', $oTmCtx->getIdProject());
				
				$this->set_data('iIdField', 0);
				$this->set_data('sFieldName', '');
				$this->set_data('sFieldType', -1);
				$this->set_data('refCount', 0);

				$this->set_data('sFieldLink', '#');
				$this->set_data('tg', 'admTskMgr');
				$this->set_data('deleteFieldIdx', BAB_TM_IDX_DISPLAY_DELETE_SPECIFIC_FIELD_FORM);
				
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
					
					$this->set_data('iIdField', $datas['iIdField']);
					$this->set_data('sFieldName', $datas['sFieldName']);
					$this->set_data('sFieldType', $datas['sFieldType']);

					$iIdProjectSpace = $this->m_oTmCtx->getIdProjectSpace();
					$iIdProject = $this->m_oTmCtx->getIdProject();
					
					$this->set_data('sFieldLink', $GLOBALS['babUrlScript'] . '?tg=admTskMgr' .
						'&iIdProjectSpace=' . $iIdProjectSpace . '&iIdProject=' . $iIdProject .
						'&iIdField=' . $datas['iIdField'] . '&iFieldType=' . $datas['iFieldType'] . 
						'&idx=' . BAB_TM_IDX_DISPLAY_SPECIFIC_FIELD_FORM);
					$this->set_data('refCount', $datas['refCount']);
					return true;
				}
				return false;
			}
		}	

		$tg = tskmgr_getVariable('tg', '');
		
		$itemMenu = array(
			array(
				'idx' => BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_LIST,
				'mnuStr' => bab_translate("Projects spaces"),
				'url' => $GLOBALS['babUrlScript'] . '?tg=' . $tg . '&idx=' . BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_LIST),
			array(
				'idx' => BAB_TM_IDX_DISPLAY_SPECIFIC_FIELD_LIST,
				'mnuStr' => bab_translate("Specific field list"),
				'url' => $GLOBALS['babUrlScript'] . '?tg=' . $tg . '&iIdProjectSpace=' . $iIdProjectSpace . 
					'&iIdProject=' . $iIdProject . '&idx=' . BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_LIST),
			array(
				'idx' => BAB_TM_IDX_DISPLAY_SPECIFIC_FIELD_FORM,
				'mnuStr' => bab_translate("Add specific field"),
				'url' => $GLOBALS['babUrlScript'] . '?tg=' . $tg . '&iIdProjectSpace=' . $iIdProjectSpace . 
					'&iIdProject=' . $iIdProject .'&idx=' . BAB_TM_IDX_DISPLAY_SPECIFIC_FIELD_FORM)
			);
		add_item_menu($itemMenu);
		$babBody->title = bab_translate("Specific field list");
	
		$query = 
			'SELECT ' .
				'fb.id iIdField, ' .
				'fb.name sFieldName, ' .
				'fb.refCount refCount, ' .
				'fb.nature iFieldType, ' .
				'CASE fb.nature ' .
					'WHEN \'' . BAB_TM_TEXT_FIELD . '\' THEN \'' . bab_translate("Text") . '\' ' .
					'WHEN \'' . BAB_TM_TEXT_AREA_FIELD . '\' THEN \'' . bab_translate("Text Area") . '\' ' .
					'WHEN \'' . BAB_TM_RADIO_FIELD . '\' THEN \'' . bab_translate("Choice") . '\' ' .
					'ELSE \'???\' ' .
				'END AS sFieldType ' .
			'FROM ' .
				BAB_TSKMGR_SPECIFIC_FIELDS_BASE_CLASS_TBL . ' fb ' .
			'WHERE ' .
				'idProjectSpace = \'' . $iIdProjectSpace . '\' AND ' .
				'(idProject = \'' . 0 . '\' OR idProject = \'' . $iIdProject . '\')' .
			'GROUP BY fb.name ASC';
		
		//bab_debug($query);	
		$list = & new BAB_List($query);
		
		$babBody->babecho(bab_printTemplate($list, 'tmCommon.html', 'fieldList'));
	}
	else 
	{
		$GLOBALS['babBody']->msgerror = bab_translate("Invalid project space");
	}
}


function displaySpecificFieldForm()
{
	global $babBody;

	$oTmCtx =& getTskMgrContext();
	$iIdProjectSpace = $oTmCtx->getIdProjectSpace();
	$iIdProject = $oTmCtx->getIdProject();

	{
		$iFieldType = (int) tskmgr_getVariable('iFieldType', BAB_TM_TEXT_FIELD);
		
		if($iFieldType == BAB_TM_TEXT_FIELD)
		{
			$sTemplateName = 'fieldText';
			$oField = new BAB_TM_FieldText();
		}
		else if($iFieldType == BAB_TM_TEXT_AREA_FIELD)
		{
			$sTemplateName = 'fieldArea';
			$oField = new BAB_TM_FieldArea();
		}
		else if($iFieldType == BAB_TM_RADIO_FIELD)
		{
			$sTemplateName = 'fieldRadio';
			$oField = new BAB_TM_FieldRadio();
		}
		else
		{
			die("Die with honor !!!");
		}
		
		$oField->get_data('bCreation', $bCreation);
		if($bCreation)
		{
			$babBody->title = bab_translate("Add a new field");
		}
		else
		{
			$babBody->title = bab_translate("Modify the field");
		}
	
		$tg = tskmgr_getVariable('tg', '');
		
		$itemMenu = array(
			array(
				'idx' => BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_LIST,
				'mnuStr' => bab_translate("Projects spaces"),
				'url' => $GLOBALS['babUrlScript'] . '?tg=' . $tg . '&iIdProjectSpace=' . $iIdProjectSpace . 
					'&iIdProject=' . $iIdProject . '&idx=' . BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_LIST),
			array(
				'idx' => BAB_TM_IDX_DISPLAY_SPECIFIC_FIELD_LIST,
				'mnuStr' => bab_translate("Specific field list"),
				'url' => $GLOBALS['babUrlScript'] . '?tg=' . $tg . '&iIdProjectSpace=' . $iIdProjectSpace . 
					'&iIdProject=' . $iIdProject . '&idx=' . BAB_TM_IDX_DISPLAY_SPECIFIC_FIELD_LIST),
			array(
				'idx' => BAB_TM_IDX_DISPLAY_SPECIFIC_FIELD_FORM,
				'mnuStr' => $babBody->title,
				'url' => $GLOBALS['babUrlScript'] . '?tg=' . $tg . '&iIdProjectSpace=' . $iIdProjectSpace . 
					'&iIdProject=' . $iIdProject . '&idx=' . BAB_TM_IDX_DISPLAY_SPECIFIC_FIELD_FORM)
			);
			
		add_item_menu($itemMenu);
		$babBody->babecho(bab_printTemplate($oField, 'tmCommon.html', $sTemplateName));
	}
}


function displayDeleteSpecificFieldForm()
{
	global $babBody;

	$aDeletableObjects = tskmgr_getVariable('aDeletableObjects', array());

	$sDeletableField = '\'' . implode('\',\'', array_unique($aDeletableObjects)) . '\'';

//	bab_debug('sDeletableField ==> ' . $sDeletableField);
	
	if('\'\'' != $sDeletableField)
	{	
		$bf = & new BAB_BaseFormProcessing();
		
		$query = 
			'SELECT ' .
				'fb.id iIdField, ' .
				'fb.name sFieldName ' .
			'FROM ' .
				BAB_TSKMGR_SPECIFIC_FIELDS_BASE_CLASS_TBL . ' fb ' .
			'WHERE ' .
				'fb.id IN (' . $sDeletableField . ') AND ' .
				'fb.refCount = \'0\' ' .
			'GROUP BY fb.name ASC';
		
			//bab_debug($query);
				
			$db = & $GLOBALS['babDB'];
			$res = $db->db_query($query);
			$numrows = $db->db_num_rows($res);

			$title = '';
			$items = array();
			$idx = 0;
			while($idx < $numrows && false != ($data = $db->db_fetch_array($res)))
			{
				$title .= "<br>"."-". $data['sFieldName'];
				$items[] = $data['iIdField'];
				$idx++;
			}
					
			$bf->set_data('idx', BAB_TM_IDX_DISPLAY_SPECIFIC_FIELD_LIST);
			$bf->set_data('action', BAB_TM_ACTION_DELETE_SPECIFIC_FIELD);

			$oTmCtx =& getTskMgrContext();
			$bf->set_data('iIdProjectSpace', $oTmCtx->getIdProjectSpace());
			$bf->set_data('iIdProject', $oTmCtx->getIdProject());
			$bf->set_data('objectName', 'sDeletableField');
			$bf->set_data('iIdObject', implode(',', array_unique($items)));
			$bf->set_data('tg', tskmgr_getVariable('tg', ''));
	
			if(count($items) > 0)
			{
				$bf->set_caption('warning', bab_translate("This action will delete those specific fields and all references"));
			}
			else
			{
				$bf->set_caption('warning', bab_translate("This action will delete the specific field and all references"));
			}

				
			$bf->set_caption('message', bab_translate("Continue ?"));
			$bf->set_caption('title', $title);
			$bf->set_caption('yes', bab_translate("Yes"));
			$bf->set_caption('no', bab_translate("No"));
	
			$babBody->title = bab_translate("Delete specific field");
			$babBody->babecho(bab_printTemplate($bf, 'tmCommon.html', 'warningyesno'));
	}
}


//POST

function addOption()
{
	$oTmCtx =& getTskMgrContext();
	$iIdProjectSpace = $oTmCtx->getIdProjectSpace();

	if(0 != $iIdProjectSpace)
	{
		if(isset($_POST['aOptions']))
		{
			if(is_array($_POST['aOptions']))
			{
				$_POST['aOptions'][] = '';
			}
			else 
			{
				$_POST['aOptions'] = array();
			}
		}
	}
	else 
	{
		$GLOBALS['babBody']->msgerror = bab_translate("Invalid project space");
	}
}

function delOption()
{
	$oTmCtx =& getTskMgrContext();
	$iIdProjectSpace = $oTmCtx->getIdProjectSpace();

	if(0 != $iIdProjectSpace)
	{
		$iOptionCount = (int) tskmgr_getVariable('iOptionCount', 0);
		$iDefaultOption = (int) tskmgr_getVariable('iDefaultOption', 0);
		$aDelOptions = tskmgr_getVariable('aDelOptions', array());
		$aOptions = tskmgr_getVariable('aOptions', array());
		
		$iNbrOptToDel = count($aDelOptions);
	
		if(0 < $iNbrOptToDel)
		{
			for($idx = $iNbrOptToDel - 1; $idx >= 0; $idx--)
			{
				$iOptionNbr = $aDelOptions[$idx];
	//			bab_debug('aOptions[' . $iOptionNbr . '] = ' . $_POST['aOptions'][$iOptionNbr]);
				unset($_POST['aOptions'][$iOptionNbr]);
			}
		}
		
		$key = array_search($iDefaultOption, $aDelOptions);
		if(false !== $key && null !== $key)
		{
			$_POST['iDefaultOption'] = 0;
		}
		
		$_POST['aOptions'] = array_values($_POST['aOptions']);
		$_POST['iOptionCount'] = count($_POST['aOptions']);
	}
	else 
	{
		$GLOBALS['babBody']->msgerror = bab_translate("Invalid project space");
	}
}


function addModifySpecificField()
{
	$oTmCtx =& getTskMgrContext();
	$iIdProjectSpace = $oTmCtx->getIdProjectSpace();

	if(0 != $iIdProjectSpace)
	{
		$iIdProject = $oTmCtx->getIdProject();
		$iFieldType = (int) tskmgr_getVariable('iFieldType', -1);

		//bab_debug('iFieldType ==> ' . $iFieldType);
		
		switch($iFieldType)
		{
			case BAB_TM_TEXT_FIELD:
				addModifySpecificFieldTextOrArea($iIdProjectSpace, $iIdProject, $iFieldType, BAB_TSKMGR_SPECIFIC_FIELDS_TEXT_CLASS_TBL);
				break;
				
			case BAB_TM_TEXT_AREA_FIELD:
				addModifySpecificFieldTextOrArea($iIdProjectSpace, $iIdProject, $iFieldType, BAB_TSKMGR_SPECIFIC_FIELDS_AREA_CLASS_TBL);
				break;
				
			case BAB_TM_RADIO_FIELD:
				addModifySpecificFieldRadio($iIdProjectSpace, $iIdProject);
				break;
		}
	}
	else 
	{
		$GLOBALS['babBody']->msgerror = bab_translate("Invalid project space");
	}
}


function addModifySpecificFieldRadio($iIdProjectSpace, $iIdProject)
{
	$bBaseFldProcessed = processSpecificFieldBaseClass($iIdProjectSpace, $iIdProject, BAB_TM_RADIO_FIELD);
	if($bBaseFldProcessed)
	{
		$iIdField = (int) tskmgr_getVariable('iIdField', 0);
		$iDefaultOption = (int) tskmgr_getVariable('iDefaultOption', 0);
		
		$oTmCtx =& getTskMgrContext();
		$tblWr =& $oTmCtx->getTableWrapper();
		$tblWr->setTableName(BAB_TSKMGR_SPECIFIC_FIELDS_RADIO_CLASS_TBL);

		if(0 != $iIdField)
		{
			$aAttribut = array('idFldBase' => $iIdField);
			$tblWr->delete($aAttribut, 0, 1);
		}
		else
		{
			$db =& $tblWr->getDbObject();
			$iIdField = $db->db_insert_id();
		}
		
		$aOptions = tskmgr_getVariable('aOptions', array());
		
		$skipFirst = false;
		foreach($aOptions as $key => $value)
		{
			$aAttribut = array(
				'idFldBase' => $iIdField, 'value' => $value, 'position' => $key, 
				'isDefaultOption' => ($iDefaultOption == $key) ? BAB_TM_YES : BAB_TM_NO);
				
			$tblWr->save($aAttribut, $skipFirst);
		}
	}
}

function addModifySpecificFieldTextOrArea($iIdProjectSpace, $iIdProject, $iFieldType, $sTblName)
{
	$oTmCtx =& getTskMgrContext();
	$tblWr =& $oTmCtx->getTableWrapper();
	$bBaseFldProcessed = processSpecificFieldBaseClass($iIdProjectSpace, $iIdProject, $iFieldType);

	if($bBaseFldProcessed)
	{
		$iIdField = (int) tskmgr_getVariable('iIdField', 0);
		$sFieldValue = mysql_escape_string(trim(tskmgr_getVariable('sFieldValue', '')));
		if(0 == $iIdField)
		{
			$db =& $tblWr->getDbObject();
			$tblWr->setTableName($sTblName);
			
			$attribut = array(
				'id' => $db->db_insert_id(),
				'defaultValue' => $sFieldValue);
				
			$skipFirst = false;
			$tblWr->save($attribut, $skipFirst);
		}
		else
		{
			$tblWr->setTableName($sTblName);
			
			$attribut = array(
				'id' => $iIdField,
				'defaultValue' => $sFieldValue);
				
			$tblWr->update($attribut);
		}
	}
}


function processSpecificFieldBaseClass($iIdProjectSpace, $iIdProject, $iFieldType)
{
	$sFieldName = trim(tskmgr_getVariable('sFieldName', ''));
	
	if(0 < strlen($sFieldName))
	{
		$iIdField = (int) tskmgr_getVariable('iIdField', 0);
		
		$isValid = isNameUsedInProjectAndProjectSpace(BAB_TSKMGR_SPECIFIC_FIELDS_BASE_CLASS_TBL, $iIdProjectSpace, $iIdProject, $iIdField, $sFieldName);
		$sFieldName = mysql_escape_string($sFieldName);
		
		if($isValid)
		{
			$oTmCtx =& getTskMgrContext();
			$tblWr =& $oTmCtx->getTableWrapper();
			$tblWr->setTableName(BAB_TSKMGR_SPECIFIC_FIELDS_BASE_CLASS_TBL);
			
			$attribut = array(
				'id' => $iIdField,
				'name' => $sFieldName,
				'nature' => $iFieldType,
				'idProjectSpace' => $iIdProjectSpace,
				'idProject' => $iIdProject,
			);
			
			if(0 == $iIdField)
			{
				$attribut['refCount'] = 0;
				$attribut['created'] = date("Y-m-d H:i:s");
				$attribut['idUserCreated'] = $GLOBALS['BAB_SESS_USERID'];
				
				$skipFirst = true;
				return $tblWr->save($attribut, $skipFirst);
			}
			else
			{
				return $tblWr->update($attribut);
			}
		}
		else
		{
			$GLOBALS['babBody']->msgerror = bab_translate("There is an another field with the name") . '\'' . $sFieldName . '\'';
			$_POST['idx'] = BAB_TM_IDX_DISPLAY_SPECIFIC_FIELD_FORM;
			return false;
		}
	}
	else
	{
		$GLOBALS['babBody']->msgerror = bab_translate("The field name must not be blank");
		$_POST['idx'] = BAB_TM_IDX_DISPLAY_SPECIFIC_FIELD_FORM;
		unset($_POST['iIdField']);
		return false;
	}
}


function deleteSpecificField()
{
	bab_debug('deleteSpecificField ==> il manque les babIsAccessValid');
	
	$sDeletableField = trim(tskmgr_getVariable('sDeletableField', ''));
	
	$aIdFldToDelete = explode(',', $sDeletableField);
	
	if(is_array($aIdFldToDelete) && count($aIdFldToDelete) > 0)
	{
		$oTmCtx =& getTskMgrContext();
		$tblWr =& $oTmCtx->getTableWrapper();

		foreach($aIdFldToDelete as $key => $id)
		{
			$tblWr->setTableName(BAB_TSKMGR_SPECIFIC_FIELDS_BASE_CLASS_TBL);
			
			$aAttribut = array('id' => $id, 'nature' => -1, 'refCount' => -1);
			
			$aAttribut = $tblWr->load($aAttribut, 0, count($aAttribut), 0, 1);
			
			if(false !== $aAttribut && 0 == $aAttribut['refCount'])
			{
				switch($aAttribut['nature'])
				{
					case BAB_TM_TEXT_FIELD:
						$tblWr->setTableName(BAB_TSKMGR_SPECIFIC_FIELDS_BASE_CLASS_TBL);
						$tblWr->delete($aAttribut, 0, 1);
						$tblWr->setTableName(BAB_TSKMGR_SPECIFIC_FIELDS_TEXT_CLASS_TBL);
						$tblWr->delete($aAttribut, 0, 1);
						break;
					
					case BAB_TM_TEXT_AREA_FIELD:
						$tblWr->setTableName(BAB_TSKMGR_SPECIFIC_FIELDS_BASE_CLASS_TBL);
						$tblWr->delete($aAttribut, 0, 1);
						$tblWr->setTableName(BAB_TSKMGR_SPECIFIC_FIELDS_AREA_CLASS_TBL);
						$tblWr->delete($aAttribut, 0, 1);
						break;
					
					case BAB_TM_RADIO_FIELD:
						$tblWr->setTableName(BAB_TSKMGR_SPECIFIC_FIELDS_BASE_CLASS_TBL);
						$tblWr->delete($aAttribut, 0, 1);
						$tblWr->setTableName(BAB_TSKMGR_SPECIFIC_FIELDS_RADIO_CLASS_TBL);
						$aAttribut = array('idFldBase' => $id);
						$tblWr->delete($aAttribut, 0, 1);
						break;
				}
			}
		}
	}
}

?>