<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
include $babInstallPath."admin/acl.php";
include $babInstallPath."utilit/forumincl.php";

function modifyForum($id)
	{
	global $body;
	if( !isset($id))
		{
		$body->msgerror = babTranslate("ERROR: You must choose a valid forum")." !!";
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

		function temp($id)
			{
			$this->name = babTranslate("Name");
			$this->moderator = babTranslate("Moderator");
			$this->description = babTranslate("Description");
			$this->update = babTranslate("Update Forum");
			$this->nbmsgdisplay = babTranslate("Messages Per Page");
			$this->moderation = babTranslate("Moderation");
			$this->active = babTranslate("Active");
			$this->yes = babTranslate("Yes");
			$this->no = babTranslate("No");

			$this->db = new db_mysql();
			$req = "select * from forums where id='$id'";
			$this->res = $this->db->db_query($req);
			$this->arr = $this->db->db_fetch_array($this->res);

			$req = "select * from users where id='".$this->arr[moderator]."'";
			$this->res = $this->db->db_query($req);
			if( $this->res && $this->db->db_num_rows($this->res) > 0)
				{
				$this->arr2 = $this->db->db_fetch_array($this->res);
				$this->moderatorname = composeName($this->arr2[firstname],$this->arr2[lastname]);
				}
			else
				{
				$this->moderatorname = "";
				}
			}
		}

	$temp = new temp($id);
	$body->babecho(	babPrintTemplate($temp,"forums.html", "forummodify"));
	}

function deleteForum($id)
	{
	global $body;
	
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
			$this->message = babTranslate("Are you sure you want to delete this forum");
			$this->title = getForumName($id);
			$this->warning = babTranslate("WARNING: This operation will delete the forum and all posts"). "!";
			$this->urlyes = $GLOBALS[babUrl]."index.php?tg=forum&idx=Delete&category=".$id."&action=Yes";
			$this->yes = babTranslate("Yes");
			$this->urlno = $GLOBALS[babUrl]."index.php?tg=forum&idx=Modify&item=".$id;
			$this->no = babTranslate("No");
			}
		}

	$temp = new temp($id);
	$body->babecho(	babPrintTemplate($temp,"warning.html", "warningyesno"));
	}

function updateForum($id, $name, $description, $moderator, $moderation, $nbmsgdisplay, $active)
	{
	global $body;
	if( empty($name))
		{
		$body->msgerror = babTranslate("ERROR: You must provide a name")." !!";
		return;
		}

	if( $moderation == "Y" && empty($moderator))
		{
		$body->msgerror = babTranslate("ERROR: You must provide a moderator")." !";
		return;
		}

	$db = new db_mysql();
	if( $moderation == "Y")
		{
		$moderatorid = getUserId($moderator);
		if( $moderatorid < 1)
			{
			$body->msgerror = babTranslate("ERROR: The moderator doesn't exist !!");
			return;
			}
		}
	else
		$moderatorid = 0;

	$query = "update forums set name='$name', description='$description', moderation='$moderation', moderator='$moderatorid', display='$nbmsgdisplay', active='$active' where id = '$id'";
	$db->db_query($query);
	Header("Location: index.php?tg=forums&idx=List");

	}

function confirmDeleteForum($id)
	{
	
	$db = new db_mysql();
	//@@: delete all posts

	$req = "delete from forumsview_groups where id_object='$id'";
	$res = $db->db_query($req);
	
	$req = "delete from forumspost_groups where id_object='$id'";
	$res = $db->db_query($req);

	$req = "delete from forumsreply_groups where id_object='$id'";
	$res = $db->db_query($req);

	$req = "delete from forums where id='$id'";
	$res = $db->db_query($req);
	}

/* main */
if(!isset($idx))
	{
	$idx = "Modify";
	}

if( isset($update) && $update == "updateforum")
	{
	updateForum($item, $name, $description, $moderator, $moderation, $nbmsgdisplay, $active);
	}

if( isset($aclview))
	{
	aclUpdate($table, $item, $groups, $what);
	}

if( isset($action) && $action == "Yes")
	{
	confirmDeleteForum($category);
	Header("Location: index.php?tg=forums&idx=List");
	}

switch($idx)
	{

	case "Groups":
		$body->title = babTranslate("List of groups");
		aclGroups("forum", "Modify", "forumsview_groups", $item, "aclview");
		$body->addItemMenu("List", babTranslate("Forums"), $GLOBALS[babUrl]."index.php?tg=forums&idx=List");
		$body->addItemMenu("Modify", babTranslate("Modify"), $GLOBALS[babUrl]."index.php?tg=forum&idx=Modify&item=".$item);
		$body->addItemMenu("Groups", babTranslate("Groups"), $GLOBALS[babUrl]."index.php?tg=forum&idx=Groups&item=".$item);
		$body->addItemMenu("Post", babTranslate("Post"), $GLOBALS[babUrl]."index.php?tg=forum&idx=Post&item=".$item);
		$body->addItemMenu("Reply", babTranslate("Reply"), $GLOBALS[babUrl]."index.php?tg=forum&idx=Reply&item=".$item);
		$body->addItemMenu("Delete", babTranslate("Delete"), $GLOBALS[babUrl]."index.php?tg=forum&idx=Delete&item=".$item);
		break;

	case "Reply":
		$body->title = babTranslate("List of groups");
		aclGroups("forum", "Modify", "forumsreply_groups", $item, "aclview");
		$body->addItemMenu("List", babTranslate("Forums"), $GLOBALS[babUrl]."index.php?tg=forums&idx=List");
		$body->addItemMenu("Modify", babTranslate("Modify"), $GLOBALS[babUrl]."index.php?tg=forum&idx=Modify&item=".$item);
		$body->addItemMenu("Groups", babTranslate("Groups"), $GLOBALS[babUrl]."index.php?tg=forum&idx=Groups&item=".$item);
		$body->addItemMenu("Post", babTranslate("Post"), $GLOBALS[babUrl]."index.php?tg=forum&idx=Post&item=".$item);
		$body->addItemMenu("Reply", babTranslate("Reply"), $GLOBALS[babUrl]."index.php?tg=forum&idx=Reply&item=".$item);
		$body->addItemMenu("Delete", babTranslate("Delete"), $GLOBALS[babUrl]."index.php?tg=forum&idx=Delete&item=".$item);
		break;

	case "Post":
		$body->title = babTranslate("List of groups");
		aclGroups("forum", "Modify", "forumspost_groups", $item, "aclview");
		$body->addItemMenu("List", babTranslate("Forums"), $GLOBALS[babUrl]."index.php?tg=forums&idx=List");
		$body->addItemMenu("Modify", babTranslate("Modify"), $GLOBALS[babUrl]."index.php?tg=forum&idx=Modify&item=".$item);
		$body->addItemMenu("Groups", babTranslate("Groups"), $GLOBALS[babUrl]."index.php?tg=forum&idx=Groups&item=".$item);
		$body->addItemMenu("Post", babTranslate("Post"), $GLOBALS[babUrl]."index.php?tg=forum&idx=Post&item=".$item);
		$body->addItemMenu("Reply", babTranslate("Reply"), $GLOBALS[babUrl]."index.php?tg=forum&idx=Reply&item=".$item);
		$body->addItemMenu("Delete", babTranslate("Delete"), $GLOBALS[babUrl]."index.php?tg=forum&idx=Delete&item=".$item);
		break;

	case "Delete":
		$body->title = babTranslate("Delete a category");
		deleteForum($item);
		$body->addItemMenu("List", babTranslate("Forums"), $GLOBALS[babUrl]."index.php?tg=forums&idx=List");
		$body->addItemMenu("Modify", babTranslate("Modify"), $GLOBALS[babUrl]."index.php?tg=forum&idx=Modify&item=".$item);
		$body->addItemMenu("Groups", babTranslate("Groups"), $GLOBALS[babUrl]."index.php?tg=forum&idx=Groups&item=".$item);
		$body->addItemMenu("Post", babTranslate("Post"), $GLOBALS[babUrl]."index.php?tg=forum&idx=Post&item=".$item);
		$body->addItemMenu("Reply", babTranslate("Reply"), $GLOBALS[babUrl]."index.php?tg=forum&idx=Reply&item=".$item);
		$body->addItemMenu("Delete", babTranslate("Delete"), $GLOBALS[babUrl]."index.php?tg=forum&idx=Delete&item=".$item);
		break;

	default:
	case "Modify":
		$body->title = babTranslate("Modify a category");
		modifyForum($item);
		$body->addItemMenu("List", babTranslate("Forums"), $GLOBALS[babUrl]."index.php?tg=forums&idx=List");
		$body->addItemMenu("Modify", babTranslate("Modify"), $GLOBALS[babUrl]."index.php?tg=forum&idx=Modify&item=".$item);
		$body->addItemMenu("Groups", babTranslate("Groups"), $GLOBALS[babUrl]."index.php?tg=forum&idx=Groups&item=".$item);
		$body->addItemMenu("Post", babTranslate("Post"), $GLOBALS[babUrl]."index.php?tg=forum&idx=Post&item=".$item);
		$body->addItemMenu("Reply", babTranslate("Reply"), $GLOBALS[babUrl]."index.php?tg=forum&idx=Reply&item=".$item);
		$body->addItemMenu("Delete", babTranslate("Delete"), $GLOBALS[babUrl]."index.php?tg=forum&idx=Delete&item=".$item);
		break;
	}
$body->setCurrentItemMenu($idx);

?>