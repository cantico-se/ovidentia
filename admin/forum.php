<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
include_once "base.php";
include $babInstallPath."admin/acl.php";
include $babInstallPath."utilit/forumincl.php";

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
		var $moderator;
		var $moderatorname;
		var $nbmsgdisplay;
		var $active;
		var $moderatorval;
		var $update;

		var $db;
		var $arr = array();
		var $arr2 = array();
		var $res;
		var $notification;

		function temp($id)
			{
			$this->name = bab_translate("Name");
			$this->moderator = bab_translate("Moderator");
			$this->description = bab_translate("Description");
			$this->update = bab_translate("Update Forum");
			$this->nbmsgdisplay = bab_translate("Messages Per Page");
			$this->moderation = bab_translate("Moderation");
			$this->active = bab_translate("Active");
			$this->yes = bab_translate("Yes");
			$this->no = bab_translate("No");
			$this->notification = bab_translate("Notify moderator");
			$this->usersbrowurl = $GLOBALS['babUrlScript']."?tg=users&idx=brow&cb=";

			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_FORUMS_TBL." where id='$id'";
			$this->res = $this->db->db_query($req);
			$this->arr = $this->db->db_fetch_array($this->res);

			$req = "select * from ".BAB_USERS_TBL." where id='".$this->arr['moderator']."'";
			$this->res = $this->db->db_query($req);
			if( $this->res && $this->db->db_num_rows($this->res) > 0)
				{
				$this->arr2 = $this->db->db_fetch_array($this->res);
				$this->managerval = bab_composeUserName($this->arr2['firstname'], $this->arr2['lastname']);
				$this->managerid = $this->arr2['id'];
				}
			else
				{
				$this->managerval = "";
				$this->managerid = "";
				}
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

function updateForum($id, $name, $description, $managerid, $moderation, $notification, $nbmsgdisplay, $active)
	{
	global $babBody;
	if( empty($name))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a name")." !!";
		return;
		}

	if( $moderation == "Y" && empty($managerid))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a moderator")." !";
		return;
		}

	$db = $GLOBALS['babDB'];
	if( $moderation == "Y")
		{
		$moderatorid = $managerid;
		}
	else
		$moderatorid = 0;

	if( !bab_isMagicQuotesGpcOn())
		{
		$name = addslashes($name);
		$description = addslashes($description);
		}

	$query = "update ".BAB_FORUMS_TBL." set name='$name', description='$description', moderation='$moderation', notification='$notification', moderator='$moderatorid', display='$nbmsgdisplay', active='$active' where id = '$id'";
	$db->db_query($query);
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=forums&idx=List");

	}

function confirmDeleteForum($id)
	{
	
	$db = $GLOBALS['babDB'];
	//@@: delete all posts

	$req = "delete from ".BAB_FORUMSVIEW_GROUPS_TBL." where id_object='$id'";
	$res = $db->db_query($req);
	
	$req = "delete from ".BAB_FORUMSPOST_GROUPS_TBL." where id_object='$id'";
	$res = $db->db_query($req);

	$req = "delete from ".BAB_FORUMSREPLY_GROUPS_TBL." where id_object='$id'";
	$res = $db->db_query($req);

	$req = "delete from ".BAB_FORUMS_TBL." where id='$id'";
	$res = $db->db_query($req);
	}

/* main */
if(!isset($idx))
	{
	$idx = "Modify";
	}

if( isset($update) && $update == "updateforum")
	{
	updateForum($item, $name, $description, $managerid, $moderation, $notification, $nbmsgdisplay, $active);
	}

if( isset($aclview))
	{
	aclUpdate($table, $item, $groups, $what);
	}

if( isset($action) && $action == "Yes")
	{
	confirmDeleteForum($category);
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=forums&idx=List");
	}

switch($idx)
	{

	case "Groups":
		$babBody->title = bab_getForumName($item) . ": ".bab_translate("List of groups");
		aclGroups("forum", "Modify", BAB_FORUMSVIEW_GROUPS_TBL, $item, "aclview");
		$babBody->addItemMenu("List", bab_translate("Forums"), $GLOBALS['babUrlScript']."?tg=forums&idx=List");
		$babBody->addItemMenu("Modify", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=forum&idx=Modify&item=".$item);
		$babBody->addItemMenu("Groups", bab_translate("View"), $GLOBALS['babUrlScript']."?tg=forum&idx=Groups&item=".$item);
		$babBody->addItemMenu("Post", bab_translate("Post"), $GLOBALS['babUrlScript']."?tg=forum&idx=Post&item=".$item);
		$babBody->addItemMenu("Reply", bab_translate("Reply"), $GLOBALS['babUrlScript']."?tg=forum&idx=Reply&item=".$item);
		$babBody->addItemMenu("Delete", bab_translate("Delete"), $GLOBALS['babUrlScript']."?tg=forum&idx=Delete&item=".$item);
		break;

	case "Reply":
		$babBody->title = bab_getForumName($item) . ": ".bab_translate("List of groups");
		aclGroups("forum", "Modify", BAB_FORUMSREPLY_GROUPS_TBL, $item, "aclview");
		$babBody->addItemMenu("List", bab_translate("Forums"), $GLOBALS['babUrlScript']."?tg=forums&idx=List");
		$babBody->addItemMenu("Modify", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=forum&idx=Modify&item=".$item);
		$babBody->addItemMenu("Groups", bab_translate("View"), $GLOBALS['babUrlScript']."?tg=forum&idx=Groups&item=".$item);
		$babBody->addItemMenu("Post", bab_translate("Post"), $GLOBALS['babUrlScript']."?tg=forum&idx=Post&item=".$item);
		$babBody->addItemMenu("Reply", bab_translate("Reply"), $GLOBALS['babUrlScript']."?tg=forum&idx=Reply&item=".$item);
		$babBody->addItemMenu("Delete", bab_translate("Delete"), $GLOBALS['babUrlScript']."?tg=forum&idx=Delete&item=".$item);
		break;

	case "Post":
		$babBody->title = bab_getForumName($item) . ": ".bab_translate("List of groups");
		aclGroups("forum", "Modify", BAB_FORUMSPOST_GROUPS_TBL, $item, "aclview");
		$babBody->addItemMenu("List", bab_translate("Forums"), $GLOBALS['babUrlScript']."?tg=forums&idx=List");
		$babBody->addItemMenu("Modify", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=forum&idx=Modify&item=".$item);
		$babBody->addItemMenu("Groups", bab_translate("View"), $GLOBALS['babUrlScript']."?tg=forum&idx=Groups&item=".$item);
		$babBody->addItemMenu("Post", bab_translate("Post"), $GLOBALS['babUrlScript']."?tg=forum&idx=Post&item=".$item);
		$babBody->addItemMenu("Reply", bab_translate("Reply"), $GLOBALS['babUrlScript']."?tg=forum&idx=Reply&item=".$item);
		$babBody->addItemMenu("Delete", bab_translate("Delete"), $GLOBALS['babUrlScript']."?tg=forum&idx=Delete&item=".$item);
		break;

	case "Delete":
		$babBody->title = bab_translate("Delete a forum");
		deleteForum($item);
		$babBody->addItemMenu("List", bab_translate("Forums"), $GLOBALS['babUrlScript']."?tg=forums&idx=List");
		$babBody->addItemMenu("Modify", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=forum&idx=Modify&item=".$item);
		$babBody->addItemMenu("Groups", bab_translate("View"), $GLOBALS['babUrlScript']."?tg=forum&idx=Groups&item=".$item);
		$babBody->addItemMenu("Post", bab_translate("Post"), $GLOBALS['babUrlScript']."?tg=forum&idx=Post&item=".$item);
		$babBody->addItemMenu("Reply", bab_translate("Reply"), $GLOBALS['babUrlScript']."?tg=forum&idx=Reply&item=".$item);
		$babBody->addItemMenu("Delete", bab_translate("Delete"), $GLOBALS['babUrlScript']."?tg=forum&idx=Delete&item=".$item);
		break;

	default:
	case "Modify":
		$babBody->title = bab_translate("Modify a forum");
		modifyForum($item);
		$babBody->addItemMenu("List", bab_translate("Forums"), $GLOBALS['babUrlScript']."?tg=forums&idx=List");
		$babBody->addItemMenu("Modify", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=forum&idx=Modify&item=".$item);
		$babBody->addItemMenu("Groups", bab_translate("View"), $GLOBALS['babUrlScript']."?tg=forum&idx=Groups&item=".$item);
		$babBody->addItemMenu("Post", bab_translate("Post"), $GLOBALS['babUrlScript']."?tg=forum&idx=Post&item=".$item);
		$babBody->addItemMenu("Reply", bab_translate("Reply"), $GLOBALS['babUrlScript']."?tg=forum&idx=Reply&item=".$item);
		$babBody->addItemMenu("Delete", bab_translate("Delete"), $GLOBALS['babUrlScript']."?tg=forum&idx=Delete&item=".$item);
		break;
	}
$babBody->setCurrentItemMenu($idx);

?>