<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
include $babInstallPath."utilit/forumincl.php";

function addForum($nameval, $descriptionval, $moderatorval, $nbmsgdisplayval)
	{
	global $babBody;
	class temp
		{
		var $name;
		var $description;
		var $moderator;
		var $nameval;
		var $descriptionval;
		var $moderatorval;
		var $nbmsgdisplay;
		var $nbmsgdisplayval;
		var $moderation;
		var $active;
		var $yes;
		var $no;
		var $add;

		function temp($nameval, $descriptionval, $moderatorval, $nbmsgdisplayval)
			{
			$this->name = bab_translate("Name");
			$this->description = bab_translate("Description");
			$this->moderator = bab_translate("Moderator");
			$this->nbmsgdisplay = bab_translate("Messages Per Page");
			$this->moderation = bab_translate("Moderation");
			$this->yes = bab_translate("Yes");
			$this->no = bab_translate("No");
			$this->add = bab_translate("Add");
			$this->active = bab_translate("Active");
			$this->nameval = $nameval == ""? "": $nameval;
			$this->descriptionval = $descriptionval == ""? "": $descriptionval;
			$this->moderatorval = $moderatorval == ""? "": $moderatorval;
			$this->nbmsgdisplayval = $nbmsgdisplayval == ""? "": $nbmsgdisplayval;
			}
		}

	$temp = new temp($nameval, $descriptionval, $moderatorval, $nbmsgdisplayval);
	$babBody->babecho(	bab_printTemplate($temp,"forums.html", "forumcreate"));
	}

function listForums()
	{
	global $babBody;
	class temp
		{
		var $name;
		var $moderator;
		var $moderatorname;
		var $urlname;
		var $url;
		var $description;
				
		var $arr = array();
		var $db;
		var $count;
		var $res;
		var $groups;
		var $reply;
		var $posts;
		var $groupsurl;
		var $replyurl;
		var $postsurl;
		var $access;

		function temp()
			{
			$this->name = bab_translate("Name");
			$this->moderator = bab_translate("Moderator Email");
			$this->description = bab_translate("Description");
			$this->access = bab_translate("Access");
			$this->groups = bab_translate("View");
			$this->reply = bab_translate("Reply");
			$this->posts = bab_translate("Post");
			$this->db = $GLOBALS['babDB'];
			$req = "select * from forums order by name asc";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$this->arr = $this->db->db_fetch_array($this->res);
				$this->moderatorname = bab_getUserName($this->arr['moderator']);
				$this->url = $GLOBALS['babUrlScript']."?tg=forum&idx=Modify&item=".$this->arr['id'];
				$this->groupsurl = $GLOBALS['babUrlScript']."?tg=forum&idx=Groups&item=".$this->arr['id'];
				$this->postsurl = $GLOBALS['babUrlScript']."?tg=forum&idx=Post&item=".$this->arr['id'];
				$this->replyurl = $GLOBALS['babUrlScript']."?tg=forum&idx=Reply&item=".$this->arr['id'];
				$this->urlname = $this->arr['name'];
				$i++;
				return true;
				}
			else
				return false;

			}
		}

	$temp = new temp();
	$babBody->babecho(	bab_printTemplate($temp, "forums.html", "forumslist"));
	return $temp->count;
	}


function saveForum($name, $description, $moderator, $moderation, $nbmsgdisplay, $active)
	{
	global $babBody;
	if( empty($name))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a name")." !";
		return false;
		}

	if( $moderation == "Y" && empty($moderator))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a moderator")." !";
		return false;
		}
	
	$db = $GLOBALS['babDB'];
	$query = "select * from forums where name='$name'";	
	$res = $db->db_query($query);
	if( $db->db_num_rows($res) > 0)
		{
		$babBody->msgerror = bab_translate("ERROR: This forum already exists");
		return false;
		}

	if($moderation == "Y")
		{
		$moderatorid = bab_getUserId($moderator);
		if( $moderatorid < 1)
			{
			$babBody->msgerror = bab_translate("ERROR: The moderator doesn't exist !!");
			return false;
			}
		}
	else
		$moderatorid = 0;	
	$query = "insert into forums (name, description, display, moderator, moderation, active)";
	$query .= " values ('" .$name. "', '" . $description. "', '" . $nbmsgdisplay. "', '" . $moderatorid. "', '" . $moderation. "', '" . $active. "')";
	$db->db_query($query);
	return true;

	}

/* main */
if(!isset($idx))
	{
	$idx = "List";
	}

if( isset($addforum) && $addforum == "addforum" )
	{
	if( !saveForum($name, $description, $moderator, $moderation, $nbmsgdisplay, $active))
		$idx = "addforum";
	}

switch($idx)
	{
	case "addforum":
		$babBody->title = bab_translate("Add a new forum");
		$babBody->addItemMenu("List", bab_translate("Forums"), $GLOBALS['babUrlScript']."?tg=forums&idx=List");
		$babBody->addItemMenu("addforum", bab_translate("Add"), $GLOBALS['babUrlScript']."?tg=forums&idx=addforum");
		addForum($name, $description, $moderator, $nbmsgdisplay);
		break;

	default:
	case "List":
		$babBody->title = bab_translate("List of all forums");
		if( listForums() > 0 )
			{
			$babBody->addItemMenu("List", bab_translate("Forums"), $GLOBALS['babUrlScript']."?tg=forums&idx=List");
			}
		else
			$babBody->title = bab_translate("There is no forum");

		$babBody->addItemMenu("addforum", bab_translate("Add"), $GLOBALS['babUrlScript']."?tg=forums&idx=addforum");
		break;
	}
$babBody->setCurrentItemMenu($idx);

?>