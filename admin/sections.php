<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
include $babInstallPath."admin/acl.php";

function getSectionName($id)
	{
	$db = new db_mysql();
	$query = "select * from sections where id='$id'";
	$res = $db->db_query($query);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		return $arr[title];
		}
	else
		{
		return "";
		}
	}

function sectionsList()
	{
	global $body;
	class temp
		{
		var $title;
		var $urltitle;
		var $url;
		var $description;
		
		var $id;
		var $arr = array();
		var $db;
		var $count;
		var $res;
		var $checked;

		function temp()
			{
			$this->title = babTranslate("Title");
			$this->description = babTranslate("Description");
			$this->db = new db_mysql();
			$req = "select id, title, description from sections";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				if( $i == 0)
					$this->checked = "checked";
				else
					$this->checked = "";
				$this->arr = $this->db->db_fetch_array($this->res);
				$this->url = $GLOBALS[babUrl]."index.php?tg=section&idx=Modify&item=".$this->arr[id];
				$this->urltitle = $this->arr[title];
				$i++;
				return true;
				}
			else
				return false;

			}
		}

	$temp = new temp();
	$body->babecho(	babPrintTemplate($temp, "sections.html", "sectionslist"));
	return $temp->count;
	}

function sectionCreate()
	{
	global $body;
	class temp
		{
		var $title;
		var $description;
		var $position;
		var $content;
		var $create;
		var $left;
		var $right;
		var $script;

		function temp()
			{
			$this->title = babTranslate("Title");
			$this->description = babTranslate("Description");
			$this->position = babTranslate("Position");
			$this->content = babTranslate("Content");
			$this->create = babTranslate("Create");
			$this->left = babTranslate("Left");
			$this->right = babTranslate("Right");
			$this->script = babTranslate("PHP script");
			}
		}

	$temp = new temp();
	$body->babecho(	babPrintTemplate($temp,"sections.html", "sectionscreate"));
	}



function sectionSave($title, $pos, $desc, $content, $script)
	{
	global $body;
	if( empty($title))
		{
		$body->msgerror = babTranslate("ERROR: You must provide a title !!");
		return;
		}
	if( $script == "Y")
		$php = "Y";
	else
		$php = "N";

	$db = new db_mysql();
	$query = "select * from sections where title='$title'";	
	$res = $db->db_query($query);
	if( $db->db_num_rows($res) > 0)
		{
		$body->msgerror = babTranslate("ERROR: This section already exists");
		}
	else
		{
		$query = "insert into sections (title, position, description, content, script) VALUES ('" .$title. "', '" . $pos. "', '" . $desc. "', '" . $content. "', '" . $php."')";
		$db->db_query($query);
		}
	}


/* main */
if( isset($create))
	{
	sectionSave($title, $position, $description, $content, $script);
	}

if( !isset($idx))
	$idx = "List";


switch($idx)
	{
	case "Create":
		$body->title = babTranslate("Create section");
		sectionCreate();
		$body->addItemMenu("List", babTranslate("Sections"),$GLOBALS[babUrl]."index.php?tg=sections&idx=List");
		$body->addItemMenu("Create", babTranslate("Create"),$GLOBALS[babUrl]."index.php?tg=sections&idx=Create");
		break;
	case "List":
	default:
		$body->title = babTranslate("Sections list");
		if( sectionsList() > 0 )
			{
			$body->addItemMenu("List", babTranslate("Sections"),$GLOBALS[babUrl]."index.php?tg=sections&idx=List");
			}
		else
			$body->title = babTranslate("There is no section");

		$body->addItemMenu("Create", babTranslate("Create"),$GLOBALS[babUrl]."index.php?tg=sections&idx=Create");
		break;
	}

$body->setCurrentItemMenu($idx);


?>