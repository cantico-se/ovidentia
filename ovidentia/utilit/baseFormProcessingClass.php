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
	
	
	define('BAB_RAW_2_HTML_DATA', 0x0);
	define('BAB_RAW_2_HTML_HTMLDATA', 0x1);
	define('BAB_RAW_2_HTML_CAPTION', 0x2);


	class BAB_RawToHtml
	{
		function BAB_RawToHtml()
		{
		}

		function transform(& $value, $key, $option)
		{
			if(is_array($value))
			{
				array_walk($value, array('BAB_RawToHtml', 'transform'), $option);
			}
			else if(false === is_numeric($value))
			{
				$value = bab_toHtml($value, $option);
			}
		}
	}

	class BAB_BaseFormProcessing
	{
		var $m_datas;
		var $m_htmlDatas;
		var $m_captions;

		var $m_errors;
		var $m_implodedErrorKeys;
		var $m_implodedErrorValues;

		var $m_anchor;
		var $m_anchorItem;

		function BAB_BaseFormProcessing()
		{
			$this->m_datas					= array();
			$this->m_htmlDatas				= array();
			$this->m_captions				= array();
			$this->m_errors					= array();
			$this->m_anchor					= array();
			$this->m_anchorItem				= array();
			$this->m_implodedErrorKeys		= '';
			$this->m_implodedErrorValues	= '';
			$this->set_data('className', '');
		}

		function get_data($property_name, &$property_value)
		{
			if(isset($this->m_datas[$property_name]))
			{
				$property_value = $this->m_datas[$property_name];
				return true;
			}
			return false;
		}

		function set_data($property_name, $property_value)
		{
			$this->m_datas[$property_name] = $property_value;
			return true;
		}

		function get_htmlData($property_name, &$property_value)
		{
			if(isset($this->m_htmlDatas[$property_name]))
			{
				$property_value = $this->m_htmlDatas[$property_name];
				return true;
			}
			return false;
		}

		function set_htmlData($property_name, $property_value)
		{
			$this->m_htmlDatas[$property_name] = $property_value;
			return true;
		}

		function get_caption($property_name, &$property_value)
		{
			if(isset($this->m_captions[$property_name]))
			{
				$property_value = $this->m_captions[$property_name];
				return true;
			}
			return false;
		}

		function set_caption($property_name, $property_value)
		{
			$this->m_captions[$property_name] = $property_value;
			return true;
		}

		function set_error($property_name, $property_value)
		{
			$this->m_errors[$property_name] = $property_value;
			return true;
		}

		function implode_errors($separator)
		{
			$this->m_implodedErrorKeys = implode($separator, array_keys($this->m_errors));
			$this->m_implodedErrorValues = implode($separator, array_values($this->m_errors));
			$this->m_implodedErrorValues = bab_toHtml($this->m_implodedErrorValues);
		}

		function raw_2_html($type, $option = BAB_HTML_ENTITIES)
		{
			switch($type)
			{
				case BAB_RAW_2_HTML_DATA:
					array_walk($this->m_datas, array('BAB_RawToHtml', 'transform'), $option);
					break;

				case BAB_RAW_2_HTML_HTMLDATA:
					array_walk($this->m_htmlDatas, array('BAB_RawToHtml', 'transform'), $option);
					break;

				case BAB_RAW_2_HTML_CAPTION:
					array_walk($this->m_captions, array('BAB_RawToHtml', 'transform'), $option);
					break;
			}
		}

		function set_anchor($href, $imgSrc, $title, $msg = '')
		{
			$item = array('href' => bab_toHtml($href),
				'title' => bab_toHtml($title),
				'imgSrc' => $imgSrc,
				'msg' => bab_toHtml($msg));

			array_push($this->m_anchor, $item);
		}
		
		function getNextAnchor()
		{
			$this->m_anchorItem = each($this->m_anchor);

			//false != $this->m_datas est necessaire car ds la variable m_anchorItem
			//il y a des choses comme m_datas[XXX] qui doivent être parsée par le
			//moteur de template

			if(false !== $this->m_datas && false !== $this->m_anchorItem)
			{
				/*
					La fonction each retourne un tableau associatif
					dont une des cles est 'value'
				*/
				$this->m_anchorItem = $this->m_anchorItem['value'];
				return true;
			}
			else
			{
				reset($this->m_anchor);
				return false;
			}
		}
	}
?>