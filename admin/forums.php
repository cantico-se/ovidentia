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
include_once $babInstallPath."utilit/forumincl.php";

function addForum($nameval, $descriptionval, $nbmsgdisplayval)
	{
	global $babBody;
	class temp
		{
		var $name;
		var $description;
		var $nameval;
		var $descriptionval;
		var $nbmsgdisplay;
		var $nbmsgdisplayval;
		var $moderation;
		var $active;
		var $yes;
		var $no;
		var $add;
		var $notification;

		function temp($nameval, $descriptionval, $nbmsgdisplayval)
			{
			$this->name = bab_translate("Name");
			$this->description = bab_translate("Description");
			$this->nbmsgdisplay = bab_translate("Threads Per Page");
			$this->moderation = bab_translate("Moderation");
			$this->notification = bab_translate("Notify moderator");
			$this->nbrecipients = bab_translate("Number of recipients per sending");
			$this->yes = bab_translate("Yes");
			$this->no = bab_translate("No");
			$this->add = bab_translate("Add");
			$this->active = bab_translate("Active");
			$this->nameval = $nameval == ""? "": $nameval;
			$this->descriptionval = $descriptionval == ""? "": $descriptionval;
			$this->nbmsgdisplayval = $nbmsgdisplayval == ""? "": $nbmsgdisplayval;
			}
		}

	$temp = new temp($nameval, $descriptionval, $nbmsgdisplayval);
	$babBody->babecho(	bab_printTemplate($temp,"forums.html", "forumcreate"));
	}

function listForums()
	{
	global $babBody;
	class temp
		{
		var $name;
		var $urlname;
		var $url;
		var $description;
		var $descval;
				
		var $arr = array();
		var $db;
		var $count;
		var $res;
		var $rightsurl;
		var $access;
		var $rights;

		function temp()
			{
			global $babBody;
			$this->name = bab_translate("Name");
			$this->description = bab_translate("Description");
			$this->access = bab_translate("Access");
			$this->rights = bab_translate("Rights");
			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_FORUMS_TBL." where id_dgowner='".$babBody->currentAdmGroup."' order by ordering asc";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$this->arr = $this->db->db_fetch_array($this->res);
				$this->url = $GLOBALS['babUrlScript']."?tg=forum&idx=Modify&item=".$this->arr['id'];
				$this->rightsurl = $GLOBALS['babUrlScript']."?tg=forum&idx=rights&item=".$this->arr['id'];
				$this->urlname = $this->arr['name'];
				$this->descval = $this->arr['description'];
				$i++;
				return true;
				}
			else
				{
				return false;
				}

			}
		}

	$temp = new temp();
	$babBody->babecho(	bab_printTemplate($temp, "forums.html", "forumslist"));
	return $temp->count;
	}

function orderForum()
	{
	global $babBody;
	class temp
		{		
		var $forumtxt;
		var $moveup;
		var $movedown;
		var $create;
		var $db;
		var $res;
		var $count;
		var $arrid = array();
		var $forumid;
		var $forumval;


		function temp()
			{
			global $babBody, $BAB_SESS_USERID;
			$this->forumtxt = "---- ".bab_translate("Forums order")." ----";
			$this->moveup = bab_translate("Move Up");
			$this->movedown = bab_translate("Move Down");
			$this->create = bab_translate("Modify");
			$this->db = $GLOBALS['babDB'];
			$req = "select id, id_dgowner from ".BAB_FORUMS_TBL." order by ordering asc";
			$this->res = $this->db->db_query($req);
			while( $arr = $this->db->db_fetch_array($this->res) )
				{
					if( $babBody->isSuperAdmin && $babBody->currentAdmGroup == 0 && $arr['id_dgowner'] == 0)
						{
						$this->arrid[] = $arr['id'];
						}
					else if( $babBody->currentAdmGroup == $arr['id_dgowner'] )
						{
						$this->arrid[] = $arr['id'];
						}
					else if( $babBody->isSuperAdmin && ($babBody->currentAdmGroup != $arr['id_dgowner']) )
					{
						if( count($this->arrid) == 0 || !in_array($arr['id_dgowner']."-0", $this->arrid))
							{
							$this->arrid[] = $arr['id_dgowner']."-0";
							}
					}
				}
			$this->count = count($this->arrid);
			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$rr = explode('-',$this->arrid[$i]);
				if( count($rr) > 1 )
					$this->forumval = "[[".bab_getGroupName($rr[0])."]]";
				else
					$this->forumval = bab_getForumName($this->arrid[$i]);

				$this->forumid = $this->arrid[$i];
				$i++;
				return true;
				}
			else
				return false;
			}
		}
	$temp = new temp();
	$babBody->babecho(	bab_printTemplate($temp, "sites.html", "scripts"));
	$babBody->babecho(	bab_printTemplate($temp,"forums.html", "forumsorder"));
	return $temp->count;
	}

function saveForum($name, $description, $moderation, $notification, $nbmsgdisplay, $active, $nbrecipients)
	{
	global $babBody;
	if( empty($name))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a name")." !";
		return false;
		}


	if( empty($managerid))
		$managerid = 0;

	$db = $GLOBALS['babDB'];
	$query = "select * from ".BAB_FORUMS_TBL." where name='".$db->db_escape_string($name)."'";	
	$res = $db->db_query($query);
	if( $db->db_num_rows($res) > 0)
		{
		$babBody->msgerror = bab_translate("ERROR: This forum already exists");
		return false;
		}

	$res = $db->db_query("select max(ordering) from ".BAB_FORUMS_TBL."");
	if( $res )
		{
		$arr = $db->db_fetch_array($res);
		$max = $arr[0] + 1;
		}
	else
		$max = 0;

	if (!is_numeric($nbmsgdisplay) || empty($nbmsgdisplay))
		{
		$nbmsgdisplay = 20;
		}

	if (!is_numeric($nbrecipients) || empty($nbrecipients))
		{
		$nbrecipients = 30;
		}

	$query = "insert into ".BAB_FORUMS_TBL." (name, description, display, moderation, notification, active, ordering, id_dgowner, nb_recipients)";

	$query .= " values (
		'" . $db->db_escape_string($name). "',
		'" . $db->db_escape_string($description). "', 
		'" . $db->db_escape_string($nbmsgdisplay). "', 
		'" . $db->db_escape_string($moderation). "', 
		'" . $db->db_escape_string($notification). "', 
		'" . $db->db_escape_string($active). "', 
		'" . $db->db_escape_string($max). "', 
		'" . $db->db_escape_string($babBody->currentAdmGroup). "',
		'" . $db->db_escape_string($nbrecipients). "'
	)";

	$db->db_query($query);
	$id = $db->db_insert_id();
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=forum&idx=rights&item=".$id);
	exit;
	}

function saveOrderForums($listforums)
	{
	global $babBody;
	$db = $GLOBALS['babDB'];
	
	if( $babBody->currentAdmGroup == 0 )
		{
		$pos = 1;
		for($i=0; $i < count($listforums); $i++)
			{
			$rr = explode('-',$listforums[$i]);
			if( count($rr) > 1 )
				{
				$res = $db->db_query("select id from ".BAB_FORUMS_TBL." where id_dgowner='".$db->db_escape_string($rr[0])."' order by ordering asc");
				while( $arr = $db->db_fetch_array($res))
					{
					$db->db_query("update ".BAB_FORUMS_TBL." set ordering='".$db->db_escape_string($pos)."' where id='".$db->db_escape_string($arr['id'])."'");
					$pos++;
					}
				}
			else
				{
				$db->db_query("update ".BAB_FORUMS_TBL." set ordering='".$db->db_escape_string($pos)."' where id='".$db->db_escape_string($listforums[$i])."'");
				$pos++;
				}
			}
		}
	else
		{
		$res = $db->db_query("select min(ordering) from ".BAB_FORUMS_TBL." where id_dgowner='".$db->db_escape_string($babBody->currentAdmGroup)."'");
		$arr = $db->db_fetch_array($res);
		if( isset($arr[0]))
			$pos = $arr[0];
		else
			{
			$res = $db->db_query("select max(ordering) from ".BAB_FORUMS_TBL."");
			$arr = $db->db_fetch_array($res);
			if( isset($arr[0]))
				$pos = $arr[0];
			else
				{
				$pos = 1;
				}
			}
		for( $i = 0; $i < count($listforums); $i++)
			{
			$db->db_query("update ".BAB_FORUMS_TBL." set ordering='".$db->db_escape_string($pos)."' where id='".$db->db_escape_string($listforums[$i])."'");
			$pos++;
			}
		}
	}
/* main */
if( !$babBody->isSuperAdmin && $babBody->currentDGGroup['forums'] != 'Y')
{
	$babBody->msgerror = bab_translate("Access denied");
	return;
}

if(!isset($idx))
	{
	$idx = "List";
	}

if( isset($addforum) && $addforum == "addforum" )
	{
	if( !saveForum($fname, $description, $moderation, $notification, $nbmsgdisplay, $active, $nbrecipients))
		$idx = "addforum";
	}

if( isset($update) && $update == "order")
	{
	saveOrderForums($listforums);
	}

switch($idx)
	{
	case "addforum":
		$babBody->title = bab_translate("Add a new forum");
		$babBody->addItemMenu("List", bab_translate("Forums"), $GLOBALS['babUrlScript']."?tg=forums&idx=List");
		$babBody->addItemMenu("addforum", bab_translate("Add"), $GLOBALS['babUrlScript']."?tg=forums&idx=addforum");
		if (!isset($name)) $name  ='';
		if (!isset($description)) $description  ='';
		if (!isset($nbmsgdisplay)) $nbmsgdisplay  ='';
		addForum($name, $description, $nbmsgdisplay);
		break;

	case "ord":
		$babBody->title = bab_translate("Order forums");
		$babBody->addItemMenu("List", bab_translate("Forums"), $GLOBALS['babUrlScript']."?tg=forums&idx=List");
		$babBody->addItemMenu("ord", bab_translate("Order"), $GLOBALS['babUrlScript']."?tg=forums&idx=ord");
		$babBody->addItemMenu("addforum", bab_translate("Add"), $GLOBALS['babUrlScript']."?tg=forums&idx=addforum");
		orderForum();
		break;
	default:
	case "List":
		$babBody->title = bab_translate("List of all forums");
		if( listForums() > 0 )
			{
			$babBody->addItemMenu("List", bab_translate("Forums"), $GLOBALS['babUrlScript']."?tg=forums&idx=List");
			$babBody->addItemMenu("ord", bab_translate("Order"), $GLOBALS['babUrlScript']."?tg=forums&idx=ord");
			}
		else
			$babBody->title = bab_translate("There is no forum");

		$babBody->addItemMenu("addforum", bab_translate("Add"), $GLOBALS['babUrlScript']."?tg=forums&idx=addforum");
		break;
	}
$babBody->setCurrentItemMenu($idx);

?>