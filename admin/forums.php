<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
include $babInstallPath."utilit/forumincl.php";

function addForum($nameval, $descriptionval, $moderatorval, $nbmsgdisplayval)
	{
	global $body;
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
			$this->name = babTranslate("Name");
			$this->description = babTranslate("Description");
			$this->moderator = babTranslate("Moderator");
			$this->nbmsgdisplay = babTranslate("Messages Per Page");
			$this->moderation = babTranslate("Moderation");
			$this->yes = babTranslate("Yes");
			$this->no = babTranslate("No");
			$this->add = babTranslate("Add");
			$this->active = babTranslate("Active");
			$this->nameval = $nameval == ""? "": $nameval;
			$this->descriptionval = $descriptionval == ""? "": $descriptionval;
			$this->moderatorval = $moderatorval == ""? "": $moderatorval;
			$this->nbmsgdisplayval = $nbmsgdisplayval == ""? "": $nbmsgdisplayval;
			}
		}

	$temp = new temp($nameval, $descriptionval, $moderatorval, $nbmsgdisplayval);
	$body->babecho(	babPrintTemplate($temp,"forums.html", "forumcreate"));
	}

function listForums()
	{
	global $body;
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

		function temp()
			{
			$this->name = babTranslate("Name");
			$this->moderator = babTranslate("Moderator Email");
			$this->description = babTranslate("Description");
			$this->db = new db_mysql();
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
				$this->moderatorname = getUserName($this->arr['moderator']);
				$this->url = $GLOBALS['babUrl']."index.php?tg=forum&idx=Modify&item=".$this->arr['id'];
				$this->urlname = $this->arr['name'];
				$i++;
				return true;
				}
			else
				return false;

			}
		}

	$temp = new temp();
	$body->babecho(	babPrintTemplate($temp, "forums.html", "forumslist"));
	return $temp->count;
	}


function saveForum($name, $description, $moderator, $moderation, $nbmsgdisplay, $active)
	{
	global $body;
	if( empty($name))
		{
		$body->msgerror = babTranslate("ERROR: You must provide a name")." !";
		return false;
		}

	if( $moderation == "Y" && empty($moderator))
		{
		$body->msgerror = babTranslate("ERROR: You must provide a moderator")." !";
		return false;
		}
	
	$db = new db_mysql();
	$query = "select * from forums where name='$name'";	
	$res = $db->db_query($query);
	if( $db->db_num_rows($res) > 0)
		{
		$body->msgerror = babTranslate("ERROR: This forum already exists");
		return false;
		}

	if($moderation == "Y")
		{
		$moderatorid = getUserId($moderator);
		if( $moderatorid < 1)
			{
			$body->msgerror = babTranslate("ERROR: The moderator doesn't exist !!");
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
		$body->title = babTranslate("Add a new forum");
		$body->addItemMenu("List", babTranslate("Forums"), $GLOBALS['babUrl']."index.php?tg=forums&idx=List");
		$body->addItemMenu("addforum", babTranslate("Add"), $GLOBALS['babUrl']."index.php?tg=forums&idx=addforum");
		addForum($name, $description, $moderator, $nbmsgdisplay);
		break;

	default:
	case "List":
		$body->title = babTranslate("List of all forums");
		if( listForums() > 0 )
			{
			$body->addItemMenu("List", babTranslate("Forums"), $GLOBALS['babUrl']."index.php?tg=forums&idx=List");
			}
		else
			$body->title = babTranslate("There is no forum");

		$body->addItemMenu("addforum", babTranslate("Add"), $GLOBALS['babUrl']."index.php?tg=forums&idx=addforum");
		break;
	}
$body->setCurrentItemMenu($idx);

?>