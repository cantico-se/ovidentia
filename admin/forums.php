<?php
include $babInstallPath."utilit/forumincl.php";

function addForum()
	{
	global $body;
	class temp
		{
		var $name;
		var $description;
		var $moderator;
		var $nbmsgdisplay;
		var $moderation;
		var $active;
		var $yes;
		var $no;
		var $add;

		function temp()
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
			}
		}

	$temp = new temp();
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
				$this->moderatorname = getUserName($this->arr[moderator]);
				$this->url = $GLOBALS[babUrl]."index.php?tg=forum&idx=Modify&item=".$this->arr[id];
				$this->urlname = $this->arr[name];
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
		return;
		}

	if( $moderation == "Y" && empty($moderator))
		{
		$body->msgerror = babTranslate("ERROR: You must provide a moderator")." !";
		return;
		}
	
	$db = new db_mysql();
	if($moderation == "Y")
		{
		$query = "select * from users where email='$moderator'";	
		$res = $db->db_query($query);
		if( $db->db_num_rows($res) < 1)
			{
			$body->msgerror = babTranslate("ERROR: The moderator doesn't exist !!");
			return;
			}
		$arr = $db->db_fetch_array($res);
		$moderatorid = $arr[id];
		}
	else
		$moderatorid = 0;	
	$query = "select * from forums where name='$name'";	
	$res = $db->db_query($query);
	if( $db->db_num_rows($res) > 0)
		{
		$body->msgerror = babTranslate("ERROR: This forum already exists");
		}
	else
		{
		$query = "insert into forums (name, description, display, moderator, moderation, active)";
		$query .= " values ('" .$name. "', '" . $description. "', '" . $nbmsgdisplay. "', '" . $moderatorid. "', '" . $moderation. "', '" . $active. "')";
		$db->db_query($query);
		}


	}

/* main */
if(!isset($idx))
	{
	$idx = "List";
	}

if( isset($addforum) && $addforum == "addforum" )
	{
	saveForum($name, $description, $moderator, $moderation, $nbmsgdisplay, $active);
	}

switch($idx)
	{
	case "addforum":
		$body->title = babTranslate("Add a new forum");
		$body->addItemMenu("List", babTranslate("List"), $GLOBALS[babUrl]."index.php?tg=forums&idx=List");
		$body->addItemMenu("addforum", babTranslate("Add"), $GLOBALS[babUrl]."index.php?tg=forums&idx=addforum");
		addForum();
		break;

	default:
	case "List":
		$body->title = babTranslate("List of all forums");
		if( listForums() > 0 )
			{
			$body->addItemMenu("List", babTranslate("List"), $GLOBALS[babUrl]."index.php?tg=forums&idx=List");
			}
		else
			$body->title = babTranslate("There is no forum");

		$body->addItemMenu("addforum", babTranslate("Add"), $GLOBALS[babUrl]."index.php?tg=forums&idx=addforum");
		break;
	}
$body->setCurrentItemMenu($idx);

?>