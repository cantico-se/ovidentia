<?php
//-------------------------------------------------------------------------
// OVIDENTIA http://www.ovidentia.org
//
// Ovidentia is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2, or (at your option)
// any later version.
// 
// This program is distributed in the hope that it will be useful, but
// WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
// See the GNU General Public License for more details.
// 
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307,
// USA.
//-------------------------------------------------------------------------
/**
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 * @copyright Copyright (c) 2008 by CANTICO ({@link http://www.cantico.fr})
 */
include_once 'base.php';

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
		$this->fltrefresh = false;
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
		var $fltrefresh;

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
			$this->frrefresh = isset($babLittleBody->frrefresh)? $babLittleBody->frrefresh: false;
			$this->fltrefresh = isset($babLittleBody->fltrefresh)? $babLittleBody->fltrefresh: false;
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
	$_SESSION['BAB_SESS_CHARTOEID-'.$ocid] = $oeid ;
}


function chart_session_rootnode($ocid, $rootnode)
{
	$_SESSION['BAB_SESS_CHARTRN-'.$ocid] = $rootnode;
}

function chart_session_closednodes($ocid, $closednodes)
{
	$_SESSION['BAB_SESS_CHARTCN-'.$ocid] = $closednodes;
}



/**
 * Returns a mysql result set containing the members of the entity $entityId.
 * 
 * Results fetched from the result set have the following structure:
 * array(
 * 		'id_dir_entry' => directory entry id (@see bab_getDirEntry)
 * 		'role_type' =>  1 = Superior, 2 = Temporary employee, 3 = Members, 0 = Other collaborators
 * 		'role_name' => The role title
 * 		'user_disabled' => 1 = disabled, 0 = not disabled
 * 		'user_confirmed' => 1 = confirmed, 0 = not confirmed
 * 		'sn' =>	The member's surname (last name)
 * 		'givenname' => The member's given name (first name)
 * )
 * The result set is ordered by role types (in order 1,2,3,0) and by user name (according to ovidentia name ordering rules).
 * 
 * @param int $orgChartId		Id of orgchart containing the entity.
 * @param int $entityId			Id of orgchart entity.
 * 
 * @return resource		The mysql resource or FALSE on error
 */
function bab_selectEntityMembers($orgChartId, $entityId)
{
	global $babDB, $babBody;

	$sql = 'SELECT users.id_user AS id_dir_entry,';
	$sql .= ' roles.type AS role_type,';
	$sql .= ' roles.name AS role_name,';
	$sql .= ' babusers.disabled AS user_disabled,';
	$sql .= ' babusers.is_confirmed AS user_confirmed,';
	$sql .= ' dir_entries.sn, dir_entries.givenname';
	$sql .= ' FROM ' . BAB_OC_ROLES_USERS_TBL . ' AS users';
	$sql .= ' LEFT JOIN ' . BAB_OC_ROLES_TBL . ' AS roles ON users.id_role = roles.id';
	$sql .= ' LEFT JOIN ' . BAB_DBDIR_ENTRIES_TBL . ' AS dir_entries ON users.id_user = dir_entries.id';
	$sql .= ' LEFT JOIN ' . BAB_USERS_TBL . ' AS babusers ON dir_entries.id_user = babusers.id';
	$sql .= ' WHERE roles.id_entity = ' . $babDB->quote($entityId);
	$sql .= ' AND roles.id_oc = ' . $babDB->quote($orgChartId);
	$sql .= ' ORDER BY (roles.type - 1 % 4) ASC, '; // We want role types to appear in the order 1,2,3,0
	$sql .= ($babBody->nameorder[0] === 'F') ?
					' dir_entries.givenname ASC, dir_entries.sn ASC'
					: ' dir_entries.sn ASC, dir_entries.givenname ASC';

	$members = $babDB->db_query($sql);

	return $members;
}

