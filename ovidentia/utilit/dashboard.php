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

class bab_Dashboard
{
	var $_elements;
	var $_filters;

	var $t_filter_type;
	var $t_filter_label;
	var $t_not_first_filter;
	var $t_dashboard_element;
	var $t_export;
	var $t_export_url;
	var $t_title;

	function bab_Dashboard()
	{
		$this->_elements = array();
		$this->_filters = array();
		$this->t_filter_type = '';
		$this->t_filter_label = '';
		$this->t_dashboard_element = null;
		$this->t_not_first_filter = false;
		$this->t_export = bab_translate("Export");
		$this->t_export_url = '';
		$this->t_global_filter = bab_translate("Global filter");
	}

	function setTitle($title)
	{
		$this->t_title = $title;
	}

	function setExportUrl($url)
	{
		$this->t_export_url= $url;
	}

	function addElement($element)
	{
		$this->_elements[] = $element;
		reset($this->_elements);
	}

	function addFilter($label, $type)
	{
		$this->_filters[$type] = $label;
		reset($this->_filters);
	}

	/**
	 * @return string
	 */
	function printTemplate()
	{
		$html = bab_printTemplate($this, 'dashboard.html', 'dashboard_css');
		$html .= bab_printTemplate($this, 'dashboard.html', 'dashboard_scripts');
		$html .= bab_printTemplate($this, 'dashboard.html', 'dashboard');
		return $html;
	}

	/**
	 * @return string
	 */
	function printTemplateCsv()
	{
		$html = bab_printTemplate($this, 'dashboard.html', 'dashboard_csv');
		return $html;
	}


	// Template functions.
	function getNextElement()
	{
		if (list(,$element) = each($this->_elements)) {
			$this->t_dashboard_element = $element->printTemplate();
			return true;
		}
		reset($this->_elements);
		return false;
	}

	function getNextFilter()
	{
		static $i = 0;
		if (list($this->t_filter_type, $this->t_filter_label) = each($this->_filters)) {
			$this->t_not_first_filter = ($i++ != 0);
			return true;
		}
		$i = 0;
		reset($this->_filters);
		return false;
	}

	function getNextElementCsv()
	{
		static $i = 0;
		if ($i < count($this->_elements)) {
			$this->t_dashboard_element = $this->_elements[$i]->printTemplateCsv();
			$i++;
			return true;
		}
		$i = 0;
		return false;
	}

}

class bab_DashboardElement
{
	var $_columnHeaders;
	var $_rows;
	var $_row;

	var $t_column_type;
	var $t_column_header;
	var $t_dashboard_name;
	var $t_dashboard_id;
	var $t_odd_row;
	var $t_column_number;

	function bab_DashboardElement($name, $id)
	{
		$this->_columnHeaders = array();
		$this->_rows = array();
		$this->_row = null;
		$this->t_dashboard_name = bab_toHtml($name);
		$this->t_dashboard_element_id = bab_toHtml($id);
		$this->t_odd_row = false;
		$this->t_column_number = 0;
	}

	function setColumnHeaders($columnHeaders)
	{
		$this->_columnHeaders = $columnHeaders;
	}

	function addRow($row)
	{
		$this->_rows[] = $row;
	}

	/**
	 * @return string
	 */
	function printTemplate()
	{
		$html = bab_printTemplate($this, 'dashboard.html', 'dashboard_element');
		return $html;
	}

	/**
	 * @return string
	 */
	function printTemplateCsv()
	{
		$html = bab_printTemplate($this, 'dashboard.html', 'dashboard_element_csv');
		return $html;
	}


	// Template functions.
	function getNextColumnHeader()
	{
		if (list(, $columnHeader) = each($this->_columnHeaders)) {
			$this->t_column_number++;
			$this->t_column_type = bab_toHtml($columnHeader['type']);
			$this->t_column_header = bab_toHtml($columnHeader['name']);
			return true;
		}
		$this->t_column_number = 0;
		reset($this->_columnHeaders);
		return false;
	}

	function getNextColumn()
	{
		if (list(, $columnValue) = each($this->_row)) {
			$this->t_column_number++;
			$this->t_column_content = bab_toHtml($columnValue);
			return true;
		}
		$this->t_column_number = 0;
		reset($this->_row);
		return false;

	}

	function getNextRow()
	{
		if (list(, $this->_row) = each($this->_rows)) {
			$this->t_odd_row = ! $this->t_odd_row;
			reset($this->_row);
			$this->t_column_number = 0;
			return true;
		}
		$this->t_odd_row = false;
		reset($this->_rows);
		return false;

	}
}
