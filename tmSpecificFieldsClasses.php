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
	require_once($babInstallPath . 'utilit/baseFormProcessingClass.php');
	require_once($babInstallPath . 'utilit/tableWrapperClass.php');
	require_once($babInstallPath . 'utilit/tmdefines.php');
	require_once($babInstallPath . 'utilit/tmIncl.php');
	
class BAB_TM_FieldBase extends BAB_BaseFormProcessing
{
	function BAB_TM_FieldBase()
	{
		parent::BAB_BaseFormProcessing();
		
		$this->set_data('bResubmission', false);
		$this->set_data('bEdition', false);
		$this->set_data('bCreation', false);

		$this->set_caption('fieldName', bab_translate("Field name"));
		$this->set_caption('fieldType', bab_translate("Field type"));
		$this->set_caption('value', bab_translate("Value"));
		$this->set_caption('supp', bab_translate("Del"));
		$this->set_caption('defaultChoice', bab_translate("Default choice"));
		
		$this->set_caption('text', bab_translate("Text"));
		$this->set_caption('area', bab_translate("Text Area"));
		$this->set_caption('choice', bab_translate("Choice"));
		$this->set_caption('option', bab_translate("Option"));
		$this->set_caption('oneMoreOption', bab_translate("One more option"));
		$this->set_caption('deleteOptions', bab_translate("Delete Options"));
		$this->set_caption('imperativeField', bab_translate("Imperative fields"));

		$this->set_caption('add', bab_translate("Add"));
		$this->set_caption('modify', bab_translate("Modify"));
		$this->set_caption('delete', bab_translate("Delete"));
		$this->set_caption('type', bab_translate("Type"));

		
		$this->set_htmlData('sTextSelected', '');
		$this->set_htmlData('sChoiceSelected', '');
		$this->set_htmlData('sAreaSelected', '');
		$this->set_htmlData('sOptionChecked', '');
		$this->set_htmlData('sOptionText', '');

		$this->set_data('isDeletable', false);
		$this->set_htmlData('sFieldName', '');
		$this->set_htmlData('sFieldValue', '');
		$this->set_htmlData('sFieldType', '');
		$this->set_htmlData('iRefCount', 0);

		$this->set_htmlData('iTextType', BAB_TM_TEXT_FIELD);
		$this->set_htmlData('iAreaType', BAB_TM_TEXT_AREA_FIELD);
		$this->set_htmlData('iChoiceType', BAB_TM_RADIO_FIELD);
		
		$this->set_htmlData('tg', bab_rp('tg', ''));
		$this->set_data('displaySpecificFieldFormIdx', BAB_TM_IDX_DISPLAY_SPECIFIC_FIELD_FORM);
		
		$this->set_htmlData('addIdx', BAB_TM_IDX_DISPLAY_SPECIFIC_FIELD_LIST);
		$this->set_htmlData('addAction', BAB_TM_ACTION_ADD_SPECIFIC_FIELD);
		$this->set_htmlData('modifyIdx', BAB_TM_IDX_DISPLAY_SPECIFIC_FIELD_LIST);
		$this->set_htmlData('modifyAction', BAB_TM_ACTION_MODIFY_SPECIFIC_FIELD);
		$this->set_htmlData('delIdx', BAB_TM_IDX_DISPLAY_DELETE_SPECIFIC_FIELD_FORM);
		$this->set_htmlData('delAction', '');
		
		$this->set_data('addIdx', BAB_TM_IDX_DISPLAY_SPECIFIC_FIELD_LIST);
		$this->set_data('addAction', BAB_TM_ACTION_ADD_SPECIFIC_FIELD);
		$this->set_data('modifyIdx', BAB_TM_IDX_DISPLAY_SPECIFIC_FIELD_LIST);
		$this->set_data('modifyAction', BAB_TM_ACTION_MODIFY_SPECIFIC_FIELD);
		$this->set_data('delIdx', BAB_TM_IDX_DISPLAY_DELETE_SPECIFIC_FIELD_FORM);
		$this->set_data('delAction', '');

		$oTmCtx =& getTskMgrContext();
		$iIdProjectSpace = (int) $oTmCtx->getIdProjectSpace();
		$iIdProject = (int) $oTmCtx->getIdProject();
		$this->set_htmlData('iIdProjectSpace', $iIdProjectSpace);
		$this->set_data('iFieldType', (int) bab_rp('iFieldType', BAB_TM_TEXT_FIELD));
		$this->set_htmlData('iIdProject', $iIdProject);
		$this->set_htmlData('iIdField', (int) bab_rp('iIdField', 0));
		$this->set_htmlData('iIdUser', (int) bab_rp('iIdUser', 0));
		
		$this->set_data('iIdProject', $iIdProject);
		$this->set_data('iIdField', (int) bab_rp('iIdField', 0));
		$this->set_data('iIdUser', (int) bab_rp('iIdUser', 0));

		if(!isset($_POST['iIdField']) && !isset($_GET['iIdField']))
		{
			$this->set_data('bCreation', true);
		}
		else if(isset($_GET['iIdField']))
		{
			$this->set_data('bEdition', true);
			$this->loadDatas();
		}
		else
		{
			$this->set_data('bResubmission', true);
			$this->getResubmittedDatas();
		}
		
		$this->buildHtmlselectFieldType();
		$this->buildHtmlfieldButtons();
	}

	function loadDatas()
	{
	}

	function buildHtmlselectFieldType()
	{
		$this->set_data('selectFieldType', '');
		
	}
	
	function buildHtmlfieldButtons()
	{
		$this->set_data('fieldButtons', '');
	}
	
	function getResubmittedDatas()
	{
		$this->set_htmlData('sFieldName', bab_rp('sFieldName', ''));
		$this->set_htmlData('sFieldValue', bab_rp('sFieldValue', ''));
	}
}


class BAB_TM_FieldText extends BAB_TM_FieldBase
{
	function BAB_TM_FieldText()
	{
		parent::BAB_TM_FieldBase();
	}

	function loadDatas()
	{
		$db = &$GLOBALS['babDB'];
		
		$this->set_htmlData('sFieldType', bab_translate("Text"));
		$this->get_data('iIdProject', $iIdProject);
		$this->get_data('iIdField', $iIdField);
		
		$result = $db->db_query(bab_getSpecificTextFieldClassInfoQuery($iIdProject, $iIdField));
		if(false != $result && $db->db_num_rows($result) > 0)
		{
			$datas = $db->db_fetch_assoc($result);
			if(false != $datas)
			{
				$this->set_htmlData('sFieldName', $datas['name']);
				$this->set_htmlData('sFieldValue', $datas['defaultValue']);
				$this->set_htmlData('iRefCount', $datas['refCount']);
				
				$bDelatable = bab_tskmgr_specificFieldDelatable($datas['iIdProjectSpace'], $datas['iIdProject'], $datas['iIdUser']);				
				$this->set_data('is_deletable', (($bDelatable) ? '1' : '0'));
				$this->set_data('is_modifiable', (($bDelatable) ? '1' : '0'));
			}
		}
	}

	function buildHtmlselectFieldType()
	{
		$this->set_htmlData('sTextSelected', 'selected="selected"');
		$this->set_data('selectFieldType', bab_printTemplate($this, 'tmCommon.html', 'selectFieldType'));
	}
	
	function buildHtmlfieldButtons()
	{
		$this->set_data('fieldButtons', bab_printTemplate($this, 'tmCommon.html', 'fieldButtons'));
	}

}


class BAB_TM_FieldArea extends BAB_TM_FieldBase
{
	function BAB_TM_FieldArea()
	{
		parent::BAB_TM_FieldBase();
	}

	function loadDatas()
	{
		$db = &$GLOBALS['babDB'];
		
		$this->set_htmlData('sFieldType', bab_translate("Text Area"));
		$this->get_data('iIdProject', $iIdProject);
		$this->get_data('iIdField', $iIdField);
		
		$result = $db->db_query(bab_getSpecificAreaFieldClassInfoQuery($iIdProject, $iIdField));
		if(false != $result && $db->db_num_rows($result) > 0)
		{
			$datas = $db->db_fetch_assoc($result);
			if(false != $datas)
			{
				$this->set_htmlData('sFieldName', $datas['name']);
				$this->set_htmlData('sFieldValue', $datas['defaultValue']);
				$this->set_htmlData('iRefCount', $datas['refCount']);
				
				$bDelatable = bab_tskmgr_specificFieldDelatable($datas['iIdProjectSpace'], $datas['iIdProject'], $datas['iIdUser']);				
				$this->set_data('is_deletable', (($bDelatable) ? '1' : '0'));
				$this->set_data('is_modifiable', (($bDelatable) ? '1' : '0'));
			}
		}
	}

	function buildHtmlselectFieldType()
	{
		$this->set_htmlData('sAreaSelected', 'selected="selected"');
		$this->set_data('selectFieldType', bab_printTemplate($this, 'tmCommon.html', 'selectFieldType'));
	}
	
	function buildHtmlfieldButtons()
	{
		$this->set_data('fieldButtons', bab_printTemplate($this, 'tmCommon.html', 'fieldButtons'));
	}

}


class BAB_TM_FieldRadio extends BAB_TM_FieldBase
{
	var $m_result;
	function BAB_TM_FieldRadio()
	{
		parent::BAB_TM_FieldBase();

		$this->set_data('aOptions', bab_rp('aOptions', array('')));
		
		$this->get_data('aOptions', $aOptions);
		$this->set_htmlData('iOptionCount', (int) bab_rp('iOptionCount', count($aOptions)));
		
		$this->set_data('addOptionAction', BAB_TM_ACTION_ADD_OPTION);
		$this->set_data('delOptionAction', BAB_TM_ACTION_DEL_OPTION);
	}

	function loadDatas()
	{
		$db = &$GLOBALS['babDB'];

		$this->set_htmlData('sFieldType', bab_translate("Choice"));
		$this->get_data('iIdField', $iIdField);
		
		$this->getOptionCount($iIdField);
		$this->getFieldNameAndDefaultChoice($iIdField);

		$this->m_result = $db->db_query(bab_getSpecificChoiceFieldClassDefaultValueAndPositionQuery($iIdField));
	}

	function getResubmittedDatas()
	{
		parent::getResubmittedDatas();
		
		$this->set_data('iDefaultOption', bab_rp('iDefaultOption', 0));
		
		$this->get_data('iIdProject', $iIdProject);
		$this->get_data('iIdField', $iIdField);
		
		$this->isFieldModifiableAndDeletable($iIdProject, $iIdField, $is_deletable, $is_modifiable);
		$this->set_data('is_deletable', $is_deletable);
		$this->set_data('is_modifiable', $is_modifiable);
	}

	function buildHtmlselectFieldType()
	{
		$this->set_htmlData('sChoiceSelected', 'selected="selected"');
		$this->set_data('selectFieldType', bab_printTemplate($this, 'tmCommon.html', 'selectFieldType'));
	}
	
	function buildHtmlfieldButtons()
	{
		$this->set_data('fieldButtons', bab_printTemplate($this, 'tmCommon.html', 'fieldButtons'));
	}
	
	function getOptionCount($iIdField)
	{
		$this->set_htmlData('iOptionCount', bab_getSpecificChoiceFieldClassOptionCount($iIdField));
	}
	
	function selectFieldNameAndDefaultChoiceQuery($iIdProject, $iIdField)
	{
		$db = &$GLOBALS['babDB'];
		return $db->db_query(bab_getSpecificChoiceFieldClassNameAndDefaultChoiceQuery($iIdProject, $iIdField));
	}
	
	function getFieldNameAndDefaultChoice($iIdField)
	{
		$db = &$GLOBALS['babDB'];
		$this->get_data('iIdProject', $iIdProject);
		$result = $this->selectFieldNameAndDefaultChoiceQuery($iIdProject, $iIdField);

		bab_debug($iIdField);
		
		if(false != $result && $db->db_num_rows($result) > 0)
		{
			$datas = $db->db_fetch_assoc($result);
			if(false != $datas)
			{
				$this->set_htmlData('sFieldName', $datas['sFieldName']);
				$this->set_data('iRefCount', $datas['iRefCount']);
				$this->set_data('iDefaultOption', $datas['iDefaultOption']);
				$this->set_data('is_modifiable', ($datas['idProject'] == $iIdProject));
			}
		}
	}

	function isFieldModifiableAndDeletable($iIdProject, $iIdField, &$is_deletable, &$is_modifiable)
	{
		$is_deletable = 0;
		$is_modifiable = false;

		$db = &$GLOBALS['babDB'];
		
		$result = $this->selectFieldNameAndDefaultChoiceQuery($iIdProject, $iIdField);
		if(false != $result && $db->db_num_rows($result) > 0)
		{
			$datas = $db->db_fetch_assoc($result);
			if(false != $datas)
			{
				$is_deletable = $datas['is_deletable'];
				$is_modifiable = ($datas['idProject'] == $iIdProject);
			}
		}
	}
	
	function nextOption() 
	{
		$this->get_data('bResubmission', $bResubmission);
		$this->get_data('bEdition', $bEdition);
		$this->get_data('bCreation', $bCreation);
		$this->get_data('iDefaultOption', $iDefaultOption);
		$this->get_data('aOptions', $aOptions);

		if($bEdition)
		{
			$db = &$GLOBALS['babDB'];
			
			$datas = $db->db_fetch_assoc($this->m_result);
			
			if(false != $datas)
			{
				$sOption = 'option_' . $datas['iPosition'];				
				$this->set_htmlData('sOptionText', $sOption);
				$this->set_htmlData('sOptionNbr', $datas['iPosition']);
				$this->set_htmlData('sOptionValue', $datas['defaultValue']);
				$this->set_htmlData('sOptionChecked', ($iDefaultOption == $datas['iPosition']) ? 'checked="checked"' : '');
				return true;
			}
		}
		else if($bResubmission)
		{
			if(isset($this->m_datas['aOptions']) && count($this->m_datas['aOptions']) > 0)
			{
				$datas = each($this->m_datas['aOptions']);
				if(false != $datas)
				{
					$sOption = 'option_' . $datas['key'];				
					$this->set_htmlData('sOptionText', $sOption);
					$this->set_htmlData('sOptionNbr', $datas['key']);
					
					$this->set_htmlData('sOptionValue', $aOptions[$datas['key']], ENT_QUOTES);
					$this->set_htmlData('sOptionChecked', ($iDefaultOption == $datas['key']) ? 'checked="checked"' : '');
					return true;
				}
			}
		}
		return false;
	}
}
?>