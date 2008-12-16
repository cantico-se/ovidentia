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
require_once 'base.php';


class BAB_ToolbarItem
{
	var $sText	= '';
	var $sUrl	= '';
	var $sImg	= '';
	var $sTitle = '';
	var $sAlt	= '';
	var $sId	= '';
	var $bId	= false;
	
	function BAB_ToolbarItem($sText, $sUrl, $sImg, $sTitle, $sAlt, $sId)
	{
		$this->setText($sText);
		$this->setUrl($sUrl);
		$this->setImg($sImg);
		$this->setTitle($sTitle);
		$this->setAlt($sAlt);
		
		static $iInstance = 0;
		++$iInstance;
		
		if(0 == mb_strlen($sId))
		{
			$this->setId('_babTbItem' . $iInstance . '_');
		}
		else
		{
			$this->setId($sId);
		}
	}

	function setText($sText) 
	{
		$this->sText = $sText;
	}
	
	function getText()
	{
		return $this->sText;
	}
	
	function setUrl($sUrl)
	{
		$this->sUrl = $sUrl;
	}
	
	function getUrl()
	{
		return $this->sUrl;
	}

	function setImg($sImg)
	{
		$this->sImg = $sImg;
	}

	function getImg()
	{
		return $this->sImg;
	}

	function setTitle($sTitle)
	{
		$this->sTitle = $sTitle;
	}

	function getTitle()
	{
		return $this->sTitle;
	}

	function setAlt($sAlt)
	{
		$this->sAlt = $sAlt;
	}

	function getAlt()
	{
		return $this->sAlt;
	}
	
	function setId($sId)
	{
		$this->sId = $sId;
	}

	function getId()
	{
		return $this->sId;
	}
}


class BAB_Toolbar
{
	var $aToolbarItem = array();

	var $sText	= '';
	var $sUrl	= '';
	var $sImg	= '';
	var $sTitle = '';
	var $sAlt	= '';
	var $sId	= '';
	
	var $sTemplateFileName = 'toolbar.html';
	var $sTemplate = 'toolbar';

	function BAB_Toolbar()
	{
	}
	
	function addToolbarItem()
	{
    	$iNumArgs = func_num_args();
    	if(0 < $iNumArgs)
    	{
    		for($iIndex = 0; $iIndex < $iNumArgs; $iIndex++)
    		{
				$oToolbarItem = func_get_arg($iIndex);
//    			if(is_a($oToolbarItem, 'BAB_TM_ToolbarItem'))
				{
					$this->aToolbarItem[] = $oToolbarItem;
				}
    		}
    	}
	}

	function getNextItem()
	{
		$aItem = each($this->aToolbarItem);
		if(false !== $aItem)
		{
			$oToolbarItem =& $aItem['value'];

			$this->sText	= $oToolbarItem->getText();
			$this->sUrl		= bab_toHtml($oToolbarItem->getUrl());
			$this->sImg		= $oToolbarItem->getImg();
			$this->sTitle	= $oToolbarItem->getTitle();
			$this->sAlt		= $oToolbarItem->getAlt();
			$this->sId		= $oToolbarItem->getId();
			return true;
		}
		return false;
	}

	function printTemplate()
	{
		return bab_printTemplate($this, $this->sTemplateFileName, $this->sTemplate);
	}
}
