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
require_once dirname(__FILE__) . '/pathUtil.class.php';
require_once dirname(__FILE__) . '/fileincl.php';


/**
 * Helper class to make hidden
 * upload (hidden iFrame technique)
 *
 */
class bab_HiddenUploadForm
{
	private	$aHiddenField		= array();
	public	$sHiddenFieldName	= null;
	public	$sHiddenFieldValue	= null;
	
	public	$sPhpSelf			= null;
	public	$iMaxFileSize		= null;
	public	$sLanguage			= null;
	public	$sContent			= null;
	
	public function __construct()
	{
		$this->sPhpSelf		= $GLOBALS['babPhpSelf'];
		$this->sLanguage	= $GLOBALS['babLanguage'];
		$this->sContent		= 'text/html; charset=' . bab_charset::getIso();
	}
	
	public function addHiddenField($sName, $sValue)
	{
		$this->aHiddenField[$sName] = $sValue;
	}
	
	public function getNextHiddenField()
	{
		if(0 < count($this->aHiddenField))
		{
			$aItem = each($this->aHiddenField);
			if(false !== $aItem)
			{
				$this->sHiddenFieldName		= $aItem['key'];
				$this->sHiddenFieldValue	= $aItem['value'];
				return true;
			}
		}
		return false;
	}
	
	public function getHtml()
	{
		return bab_printTemplate($this, 'hiddenUpload.html', "hiddenUpload");
	}
	
	public static function getHiddenIframeHtml($sJSon)
	{
		return '
			<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
			<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="' . $GLOBALS['babLanguage'] . '" lang="' . $GLOBALS['babLanguage'] . '">
				<head>
					<meta http-equiv="Content-type" content="text/html; charset=' . bab_charset::getIso() . '" />
					<script type="text/javascript">
						function init() {
							if(top.uploadDone)
							{ 
								top.uploadDone(); //top means parent frame.
							}
						}
						window.onload=init;
					</script>
				</head>
				<body>'
					. $sJSon .	
				'</body>
			</html>';
	}
}
