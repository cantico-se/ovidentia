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
include_once $babInstallPath."admin/acl.php";
include_once $babInstallPath."utilit/forumincl.php";

function modifyForum($id)
	{
	global $babBody;
	if( !isset($id))
		{
		$babBody->msgerror = bab_translate("ERROR: You must choose a valid forum")." !!";
		return;
		}
	class temp
		{
		var $name;
		var $description;
		var $nbmsgdisplay;
		var $active;
		var $update;
		var $delete;

		var $db;
		var $arr = array();
		var $arr2 = array();
		var $res;
		var $notification;

		function temp($id)
			{
			$this->name = bab_translate("Name");
			$this->description = bab_translate("Description");
			$this->update = bab_translate("Update Forum");
			$this->nbmsgdisplay = bab_translate("Messages Per Page");
			$this->moderation = bab_translate("Moderation");
			$this->active = bab_translate("Active");
			$this->yes = bab_translate("Yes");
			$this->no = bab_translate("No");
			$this->notification = bab_translate("Notify moderator");
			$this->delete = bab_translate("Delete");

			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_FORUMS_TBL." where id='$id'";
			$this->res = $this->db->db_query($req);
			$this->arr = $this->db->db_fetch_array($this->res);
			}
		}

	$temp = new temp($id);
	$babBody->babecho(	bab_printTemplate($temp,"forums.html", "forummodify"));
	}

function deleteForum($id)
	{
	global $babBody;
	
	class temp
		{
		var $warning;
		var $message;
		var $title;
		var $urlyes;
		var $urlno;
		var $yes;
		var $no;
		var $topics;
		var $article;

		function temp($id)
			{
			$this->message = bab_translate("Are you sure you want to delete this forum");
			$this->title = bab_getForumName($id);
			$this->warning = bab_translate("WARNING: This operation will delete the forum and all posts"). "!";
			$this->urlyes = $GLOBALS['babUrlScript']."?tg=forum&idx=Delete&category=".$id."&action=Yes";
			$this->yes = bab_translate("Yes");
			$this->urlno = $GLOBALS['babUrlScript']."?tg=forum&idx=Modify&item=".$id;
			$this->no = bab_translate("No");
			}
		}

	$temp = new temp($id);
	$babBody->babecho(	bab_printTemplate($temp,"warning.html", "warningyesno"));
	}

function updateForum($id, $name, $description, $moderation, $notification, $nbmsgdisplay, $active)
	{
	global $babBody;
	if( empty($name))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a name")." !!";
		return;
		}

	$db = $GLOBALS['babDB'];

	if( !bab_isMagicQuotesGpcOn())
		{
		$name = addslashes($name);
		$description = addslashes($description);
		}

	$query = "update ".BAB_FORUMS_TBL." set name='$name', description='$description', moderation='$moderation', notification='$notification', display='$nbmsgdisplay', active='$active' where id = '$id'";
	$db->db_query($query);
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=forums&idx=List");
	}

function confirmDeleteForum($id)
	{
	include_once $GLOBALS['babInstallPath']."utilit/delincl.php";
	bab_deleteForum($id);
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=forums");
	}

/* main */
if( !$babBody->isSuperAdmin && $babBody->currentDGGroup['forums'] != 'Y')
{
	$babBody->msgerror = bab_translate("Access denied");
	return;
}

if(!isset($idx))
	{
	$idx = "Modify";
	}

if( isset($update) && $update == "updateforum")
	{
	if( isset($submit))
		{
		updateForum($item, $fname, $description, $moderation, $notification, $nbmsgdisplay, $active);
		}
	elseif( isset($bdelete))
		{
		$idx = "Delete";
		}
	}

if( isset($aclview))
	{
	maclGroups();
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=forums&idx=list");
	}

if( isset($action) && $action == "Yes")
	{
	confirmDeleteForum($category);
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=forums&idx=List");
	exit;
	}

switch($idx)
	{
	case "rights":
		$babBody->title = bab_getForumName($item) . ": ".bab_translate("List of groups");
		$macl = new macl("forum", "Modify", $item, "aclview");
        $macl->addtable( BAB_FORUMSVIEW_GROUPS_TBL,bab_translate("Who can read posts?"));
		$macl->addtable( BAB_FORUMSPOST_GROUPS_TBL,bab_translate("Who can post?"));
        $macl->addtable( BAB_FORUMSREPLY_GROUPS_TBL,bab_translate("Who can reply?"));
		$macl->addtable( BAB_FORUMSFILES_GROUPS_TBL,bab_translate("Who can join dependent files?"));
		$macl->addtable( BAB_FORUMSMAN_GROUPS_TBL,bab_translate("Who can manage this forum?"));
		$macl->filter(0,0,1,1,1);
        $macl->babecho();
		$babBody->addItemMenu("List", bab_translate("Forums"), $GLOBALS['babUrlScript']."?tg=forums&idx=List");
		$babBody->addItemMenu("Modify", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=forum&idx=Modify&item=".$item);
		$babBody->addItemMenu("rights", bab_translate("Rights"), $GLOBALS['babUrlScript']."?tg=forum&idx=rights&item=".$item);
		break;

	case "Delete":
		$babBody->title = bab_translate("Delete a forum");
		deleteForum($item);
		$babBody->addItemMenu("List", bab_translate("Forums"), $GLOBALS['babUrlScript']."?tg=forums&idx=List");
		$babBody->addItemMenu("Modify", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=forum&idx=Modify&item=".$item);
		$babBody->addItemMenu("rights", bab_translate("Rights"), $GLOBALS['babUrlScript']."?tg=forum&idx=rights&item=".$item);
		break;

	default:
	case "Modify":
		$babBody->title = bab_translate("Modify a forum");
		modifyForum($item);
		$babBody->addItemMenu("List", bab_translate("Forums"), $GLOBALS['babUrlScript']."?tg=forums&idx=List");
		$babBody->addItemMenu("Modify", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=forum&idx=Modify&item=".$item);
		$babBody->addItemMenu("rights", bab_translate("Rights"), $GLOBALS['babUrlScript']."?tg=forum&idx=rights&item=".$item);
		break;
	}
$babBody->setCurrentItemMenu($idx);

?>