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

function popupUnload($message, $redirecturl, $openerreload=false)
	{
	class temp
		{
		var $message;
		var $close;
		var $redirecturl;
		var $openerreload;
		var $sContent;
		
		function temp($message, $redirecturl, $openerreload)
			{
			$this->message = $message;
			$this->close = bab_translate("Close");
			$this->redirecturl = $redirecturl;
			$this->openerreload = $openerreload;
			$this->sContent = 'text/html; charset=' . bab_charset::getIso();
			}
		}

	$temp = new temp($message, $redirecturl, $openerreload);
	echo bab_printTemplate($temp,"uiutil.html", "popupunload");
	}

class babBodyPopup
{
var $menu;
var $msgerror;
var $content;
var $title;
var $message;
var $styleSheet = array();

function babBodyPopup()
{
	global $babDB;
	$this->menu = new babMenu();
	$this->message = "";
	$this->title = "";
	$this->msgerror = "";
	$this->content = "";
}

function resetContent()
{
	$this->content = "";
}

function babecho($txt)
{
	$this->content .= $txt;
}

function addItemMenu($title, $txt, $url, $enabled=true)
{
	$this->menu->addItem($title, $txt, $url, $enabled);
}

function addItemMenuAttributes($title, $attr)
{
	$this->menu->addItemAttributes($title, $attr);
}

function setCurrentItemMenu($title, $enabled=false)
{
	$this->menu->setCurrent($title, $enabled);
}

function addStyleSheet($file)
{
	$this->styleSheet[] = $file;
}



function printout()
{
    if(!empty($this->msgerror))
		{
		$this->message = $this->msgerror;
		}
	else if(!empty($this->title))
		{
		$this->message = $this->title;
		}
	return $this->content;
}
} 


function printBabBodyPopup()
	{
	
	class clsPrintBabBodyPopup
	{
		var $menuattribute;
		var $menuurl;
		var $menutext;
		var $menukeys = array();
		var $menuvals = array();
		var $content;
		var $title;
		var $msgerror;
		var $sContent;
		
		function clsPrintBabBodyPopup()
			{
			global $babBodyPopup;
			$this->menukeys = array_keys($babBodyPopup->menu->items);
			$this->menuvals = array_values($babBodyPopup->menu->items);
			$this->menuitems = count($this->menukeys);
			$this->sContent = 'text/html; charset=' . bab_charset::getIso();
			
			$this->content = bab_getDebug();
			$this->content .= $babBodyPopup->printout();
			$this->title = &$babBodyPopup->title;
			$this->msgerror = &$babBodyPopup->msgerror;
			$this->styleSheet = &$babBodyPopup->styleSheet;
			}

		function getNextMenu()
			{
			global $babBodyPopup;
			static $i = 0;
			if( $i < $this->menuitems)
				{
				if(!strcmp($this->menukeys[$i], $babBodyPopup->menu->curItem))
					{
					$this->menuclass = "BabMenuCurArea";
					}
				else
					$this->menuclass = "BabMenuArea";
					 
				$this->menutext = $this->menuvals[$i]["text"];
				if( $this->menuvals[$i]["enabled"] == false)
					$this->enabled = 0;
				else
					{
					$this->enabled = 1;
					if( !empty($this->menuvals[$i]["attributes"]))
						{
						$this->menuattribute = $this->menuvals[$i]["attributes"];
						}
					else
						{
						$this->menuattribute = "";
						}
					$this->menuurl = $this->menuvals[$i]["url"];
					}
				$i++;
				return true;
				}
			else
				return false;
			}

		function getnextstylesheet()
			{
			return list(,$this->file) = each($this->styleSheet);
			}

	}

	$temp = new clsPrintBabBodyPopup();
	echo bab_printTemplate($temp,"uiutil.html", "babbodypopup");
	}
?>
