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
include_once "base.php";


class bab_myAddonSection
{
function bab_myAddonSection()
	{
	$this->elements_types = array('list','strong','text','script');
	$this->elements = array();
	}

function addElement($type)
	{
	if (in_array($type,$this->elements_types))
		{
		$id = count($this->elements);
		$this->elements[] = array(0 => $type, 1 => array());
		return $id;
		}
	else 
		{
		trigger_error("Parameter of addElement fuction must be part of : ".implode(',',$this->elements_types), E_USER_ERROR);
		return false;
		}
	}

function removeElement($id)
	{
	if (isset($this->elements[$id])) 
		{
		unset($this->elements[$id]);
		return true;
		}
	else return false;
	}

function pushHtmlData($id,$str, $attrib = false)
	{
	$strattrib = '';
	if (is_array($attrib))
		{
		foreach ($attrib as $key => $value)
			$strattrib .= $key.'="'.$value.'" ';
		}
	$this->elements[$id][1][] = array(0 => $str, 1 => $strattrib);
	}

function getnextelement()
	{
	return count($this->elements) > 0 ? list($this->id,list($this->type, $this->html)) = each($this->elements) : false;
	}

function getnextitem()
	{
	return isset($this->html) ? list($null, list($this->str, $this->attrib)) = each($this->html) : false;
	}

function getHtml()
	{
	return bab_printTemplate($this,"insections.html", "myaddonsection");
	}

}


?>