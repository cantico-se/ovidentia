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

class babLittleBody
{
var $menu;
var $msgerror;
var $content;
var $title;
var $message;
var $frrefresh;

function babLittleBody()
{
	global $babDB;
	$this->menu = new babMenu();
	$this->message = "";
	$this->title = "";
	$this->msgerror = "";
	$this->content = "";
	$this->frrefresh = false;
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


function printFlbChartPage()
	{
	class tpl
	{
		var $menuattribute;
		var $menuurl;
		var $menutext;
		var $menukeys = array();
		var $menuvals = array();
		var $content;
		var $title;
		var $msgerror;
		var $frrefresh;

		function tpl()
			{
			global $babBody, $babLittleBody;
			$this->home = bab_translate("Home");
			$this->menukeys = array_keys($babLittleBody->menu->items);
			$this->menuvals = array_values($babLittleBody->menu->items);
			$this->menuitems = count($this->menukeys);

			$this->content = $babLittleBody->printout();
			$this->title = $babLittleBody->title;
			$this->msgerror = $babLittleBody->msgerror;
			$this->frrefresh = $babLittleBody->frrefresh;
			}

		function getNextMenu()
			{
			global $babBody, $babLittleBody;
			static $i = 0;
			if( $i < $this->menuitems)
				{
				if(!strcmp($this->menukeys[$i], $babLittleBody->menu->curItem))
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

	}

	$temp = new tpl();
	echo bab_printTemplate($temp,"flbchart.html", "flbchartpage");
	}


function chart_session_oeid($ocid)
	{
	global $oeid;
	session_register("BAB_SESS_CHARTOEID-".$ocid);
	$GLOBALS['BAB_SESS_CHARTOEID-'.$ocid] = $oeid ;

	}


function chart_session_rootnode($ocid, $rootnode)
	{
	session_register("BAB_SESS_CHARTRN-".$ocid);
	$GLOBALS['BAB_SESS_CHARTRN-'.$ocid] = $rootnode;
	}

function chart_session_closednodes($ocid, $closednodes)
	{
	session_register("BAB_SESS_CHARTCN-".$ocid);
	$GLOBALS['BAB_SESS_CHARTCN-'.$ocid] = $closednodes;
	}

?>