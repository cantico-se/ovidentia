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
		$this->set_caption('defaultValue', bab_translate("Default value"));
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

		
		$this->set_data('sTextSelected', '');
		$this->set_data('sChoiceSelected', '');
		$this->set_data('sAreaSelected', '');
		$this->set_data('sOptionChecked', '');
		$this->set_data('sOptionText', '');

		$this->set_data('isDeletable', false);
		$this->set_data('sFieldName', '');
		$this->set_data('sFieldValue', '');
		$this->set_data('sFieldType', '');
		$this->set_data('iRefCount', 0);

		$this->set_data('iTextType', BAB_TM_TEXT_FIELD);
		$this->set_data('iAreaType', BAB_TM_TEXT_AREA_FIELD);
		$this->set_data('iChoiceType', BAB_TM_RADIO_FIELD);
		
		$this->set_data('tg', bab_rp('tg', ''));
		$this->set_data('displaySpecificFieldFormIdx', BAB_TM_IDX_DISPLAY_SPECIFIC_FIELD_FORM);
		
		$this->set_data('addIdx', BAB_TM_IDX_DISPLAY_SPECIFIC_FIELD_LIST);
		$this->set_data('addAction', BAB_TM_ACTION_ADD_SPECIFIC_FIELD);
		$this->set_data('modifyIdx', BAB_TM_IDX_DISPLAY_SPECIFIC_FIELD_LIST);
		$this->set_data('modifyAction', BAB_TM_ACTION_MODIFY_SPECIFIC_FIELD);
		$this->set_data('delIdx', BAB_TM_IDX_DISPLAY_DELETE_SPECIFIC_FIELD_FORM);
		$this->set_data('delAction', '');
		
		$oTmCtx =& getTskMgrContext();
		$iIdProjectSpace = $oTmCtx->getIdProjectSpace();
		$iIdProject = $oTmCtx->getIdProject();
		$this->set_data('iIdProjectSpace', $iIdProjectSpace);
		$this->set_data('iIdProject', $iIdProject);
		$this->set_data('iIdUser', (int) bab_rp('iIdUser', 0));
		$this->set_data('iFieldType', (int) bab_rp('iFieldType', BAB_TM_TEXT_FIELD));
		$this->set_data('iIdField', (int) bab_rp('iIdField', 0));

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
		$this->set_data('sFieldName', bab_rp('sFieldName', ''));
		$this->set_data('sFieldValue', bab_rp('sFieldValue', ''));
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
		
		$this->set_data('sFieldType', bab_translate("Text"));
		$this->get_data('iIdProject', $iIdProject);
		$this->get_data('iIdField', $iIdField);
		$this->get_data('iIdUser', $iIdUser);
		
		$query = 
			'SELECT ' .
				'fb.name name, ' .
				'fb.refCount refCount, ' .
				'fb.idProject idProject, ' .
				'ft.defaultValue defaultValue, ' .
				'IF(fb.idProject = \'' . $iIdProject . '\' AND fb.refCount = \'' . 0 . '\', 1, 0) is_deletable ' .
			'FROM ' . 
				BAB_TSKMGR_SPECIFIC_FIELDS_BASE_CLASS_TBL . ' fb ' .
			'LEFT JOIN ' .
				BAB_TSKMGR_SPECIFIC_FIELDS_TEXT_CLASS_TBL . ' ft ON ft.id = fb.id ' .
			'WHERE ' . 
				'fb.id = \'' . $iIdField . '\'';

		//bab_debug($query);
		
		$result = $db->db_query($query);
		if(false != $result && $db->db_num_rows($result) > 0)
		{
			$datas = $db->db_fetch_assoc($result);
			if(false != $datas)
			{
				//bab_debug($datas);
				$this->set_data('sFieldName', htmlentities($datas['name'], ENT_QUOTES));
				$this->set_data('sFieldValue', htmlentities($datas['defaultValue'], ENT_QUOTES));
				$this->set_data('iRefCount', $datas['refCount']);
				$this->set_data('is_deletable', $datas['is_deletable']);
				$this->set_data('is_modifiable', ($datas['idProject'] == $iIdProject));
			}
		}
	}

	function buildHtmlselectFieldType()
	{
		$this->set_data('sTextSelected', 'selected="selected"');
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
		
		$this->set_data('sFieldType', bab_translate("Text Area"));
		$this->get_data('iIdProject', $iIdProject);
		$this->get_data('iIdField', $iIdField);
		
		$query = 
			'SELECT ' .
				'fb.name name, ' .
				'fb.refCount refCount, ' .
				'fb.idProject idProject, ' .
				'fa.defaultValue defaultValue, ' .
				'IF(fb.idProject = \'' . $iIdProject . '\' AND fb.refCount = \'' . 0 . '\', 1, 0) is_deletable ' .
			'FROM ' . 
				BAB_TSKMGR_SPECIFIC_FIELDS_BASE_CLASS_TBL . ' fb ' .
			'LEFT JOIN ' .
				BAB_TSKMGR_SPECIFIC_FIELDS_AREA_CLASS_TBL . ' fa ON fa.id = fb.id ' .
			'WHERE ' . 
				'fb.id = \'' . $iIdField . '\'';

		//echo $query . '<br />';
		
		$result = $db->db_query($query);
		if(false != $result && $db->db_num_rows($result) > 0)
		{
			$datas = $db->db_fetch_assoc($result);
			if(false != $datas)
			{
				$this->set_data('sFieldName', htmlentities($datas['name'], ENT_QUOTES));
				$this->set_data('sFieldValue', htmlentities($datas['defaultValue'], ENT_QUOTES));
				$this->set_data('iRefCount', $datas['refCount']);
				$this->set_data('is_deletable', $datas['is_deletable']);
				$this->set_data('is_modifiable', ($datas['idProject'] == $iIdProject));
			}
		}
	}

	function buildHtmlselectFieldType()
	{
		$this->set_data('sAreaSelected', 'selected="selected"');
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
		$this->set_data('iOptionCount', (int) bab_rp('iOptionCount', count($aOptions)));
		
		$this->set_data('addOptionAction', BAB_TM_ACTION_ADD_OPTION);
		$this->set_data('delOptionAction', BAB_TM_ACTION_DEL_OPTION);
	}

	function loadDatas()
	{
		$db = &$GLOBALS['babDB'];

		$this->set_data('sFieldType', bab_translate("Choice"));
		$this->get_data('iIdField', $iIdField);
		
		$this->getOptionCount($iIdField);
		$this->getFieldNameAndDefaultChoice($iIdField);

		{
			$query = 
				'SELECT ' .
					'frd.value defaultValue, ' .
					'frd.position iPosition ' .
				'FROM ' . 
					BAB_TSKMGR_SPECIFIC_FIELDS_RADIO_CLASS_TBL . ' frd ' .
				'WHERE ' . 
					'frd.idFldBase = \'' . $iIdField . '\' ' .
				'ORDER BY ' . 
					'frd.position ASC';
			
			$this->m_result = $db->db_query($query);
		}
	}

	function buildHtmlselectFieldType()
	{
		$this->set_data('sChoiceSelected', 'selected="selected"');
		$this->set_data('selectFieldType', bab_printTemplate($this, 'tmCommon.html', 'selectFieldType'));
	}
	
	function buildHtmlfieldButtons()
	{
		$this->set_data('fieldButtons', bab_printTemplate($this, 'tmCommon.html', 'fieldButtons'));
	}
	
	function getOptionCount($iIdField)
	{
		$db = &$GLOBALS['babDB'];

		$query = 
			'SELECT ' .
				'COUNT(DISTINCT(frd.id)) count ' .
			'FROM ' . 
				BAB_TSKMGR_SPECIFIC_FIELDS_RADIO_CLASS_TBL . ' frd ' .
			'WHERE ' . 
				'frd.idFldBase = \'' . $iIdField . '\'';

		//bab_debug($query);

		list($iOptionCount) = $db->db_fetch_row($db->db_query($query));
		$this->set_data('iOptionCount', $iOptionCount);
	}
	
	
	function getFieldNameAndDefaultChoice($iIdField)
	{
		$this->get_data('iIdProject', $iIdProject);
		$db = &$GLOBALS['babDB'];
		
		$query = 
			'SELECT ' .
				'fb.name sFieldName, ' .
				'fb.refCount iRefCount, ' .
				'fb.idProject idProject, ' .
				'position iDefaultOption, ' .
				'IF(fb.idProject = \'' . $iIdProject . '\' AND fb.refCount = \'' . 0 . '\', 1, 0) is_deletable ' .
			'FROM ' . 
				BAB_TSKMGR_SPECIFIC_FIELDS_BASE_CLASS_TBL . ' fb ' .
			'LEFT JOIN ' .
				BAB_TSKMGR_SPECIFIC_FIELDS_RADIO_CLASS_TBL . ' frd ON frd.idFldBase = fb.id ' .
			'WHERE ' . 
				'fb.id = \'' . $iIdField . '\' AND ' .
				'frd.isDefaultValue = \'' . BAB_TM_YES . '\'';

		//bab_debug($query);

		$result = $db->db_query($query);
		if(false != $result && $db->db_num_rows($result) > 0)
		{
			$datas = $db->db_fetch_assoc($result);
			if(false != $datas)
			{
				$this->set_data('sFieldName', htmlentities($datas['sFieldName'], ENT_QUOTES));
				$this->set_data('iRefCount', $datas['iRefCount']);
				$this->set_data('iDefaultOption', $datas['iDefaultOption']);
				$this->set_data('is_deletable', $datas['is_deletable']);
				$this->set_data('is_modifiable', ($datas['idProject'] == $iIdProject));
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
				$this->set_data('sOptionText', $sOption);
				$this->set_data('sOptionNbr', $datas['iPosition']);
				$this->set_data('sOptionValue', htmlentities($datas['defaultValue'], ENT_QUOTES));
				$this->set_data('sOptionChecked', ($iDefaultOption == $datas['iPosition']) ? 'checked="checked"' : '');
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
					$this->set_data('sOptionText', $sOption);
					$this->set_data('sOptionNbr', $datas['key']);
					
					//bab_debug('$aOptions[' . $aOptions[$datas['key']] . ']=' . $aOptions[$datas['key']] );
					
					$this->set_data('sOptionValue', htmlentities($aOptions[$datas['key']], ENT_QUOTES));
					$this->set_data('sOptionChecked', ($iDefaultOption == $datas['key']) ? 'checked="checked"' : '');
					return true;
				}
			}
		}
		return false;
	}
}
?>