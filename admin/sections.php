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
		return $arr['title'];
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
				$this->url = $GLOBALS['babUrl']."index.php?tg=section&idx=Modify&item=".$this->arr['id'];
				$this->urltitle = $this->arr['title'];
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

function sectionsOrder()
	{
	global $body;
	class temp
		{
		var $id;
		var $arr = array();
		var $db;
		var $count;
		var $res;
		var $moveup;
		var $movedown;

		function temp()
			{
			$this->listleftsectxt = "----------------- ". babTranslate("Left sections") . " -----------------";
			$this->listrightsectxt = "----------------- ". babTranslate("Right sections") . " -----------------";
			$this->update = babTranslate("Update");
			$this->moveup = babTranslate("Move Up");
			$this->movedown = babTranslate("Move Down");
			$this->db = new db_mysql();
			$req = "select * from sections_order where position='0' order by ordering asc";
			$this->resleft = $this->db->db_query($req);
			$this->countleft = $this->db->db_num_rows($this->resleft);
			$req = "select * from sections_order where position='1' order by ordering asc";
			$this->resright = $this->db->db_query($req);
			$this->countright = $this->db->db_num_rows($this->resright);
			}

		function getnextsecleft()
			{
			static $i = 0;
			if( $i < $this->countleft)
				{
				$arr = $this->db->db_fetch_array($this->resleft);
				if( $arr['private'] == "Y" )
					$req = "select * from private_sections where id ='".$arr['id_section']."'";
				else
					$req = "select * from sections where id ='".$arr['id_section']."'";
				$res2 = $this->db->db_query($req);
				$arr2 = $this->db->db_fetch_array($res2);
				$this->listleftsecval = $arr2['title'];
				$this->secid = $arr['id'];
				$i++;
				return true;
				}
			else
				return false;

			}
		function getnextsecright()
			{
			static $j = 0;
			if( $j < $this->countright)
				{
				$arr = $this->db->db_fetch_array($this->resright);
				if( $arr['private'] == "Y" )
					$req = "select * from private_sections where id ='".$arr['id_section']."'";
				else
					$req = "select * from sections where id ='".$arr['id_section']."'";
				$res2 = $this->db->db_query($req);
				$arr2 = $this->db->db_fetch_array($res2);
				$this->listrightsecval = $arr2['title'];
				$this->secid = $arr['id'];
				$j++;
				return true;
				}
			else
				return false;

			}
		}

	$temp = new temp();
	$body->babecho(	babPrintTemplate($temp, "sections.html", "sectionordering"));
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
		var $msie;

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
			if( strtolower(browserAgent()) == "msie")
				$this->msie = 1;
			else
				$this->msie = 0;	
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
		$id = $db->db_insert_id();
		$query = "select max(ordering) from sections_order where private='N' and position='".$pos."'";
		$res = $db->db_query($query);
		$arr = $db->db_fetch_array($res);
		$query = "insert into sections_order (id_section, position, private, ordering) VALUES ('" .$id. "', '" . $pos. "', 'N', '" . ($arr[0]+1). "')";
		$db->db_query($query);		
		}
	}

function saveSectionsOrder($listleft, $listright)
	{
		$db = new db_mysql();

		for( $i = 0; $i < count($listleft); $i++)
		{
			$req = "update sections_order set position='0', ordering='".($i+1)."' where id='".$listleft[$i]."'";
			$res = $db->db_query($req);
			$req = "update sections set position='0' where id='".$listleft[$i]."'";
			$res = $db->db_query($req);
		}
		for( $i = 0; $i < count($listright); $i++)
		{
			$req = "update sections_order set position='1', ordering='".($i+1)."' where id='".$listright[$i]."'";
			$res = $db->db_query($req);
			$req = "update sections set position='1' where id='".$listleft[$i]."'";
			$res = $db->db_query($req);
		}
	}


/* main */
if( isset($create))
	{
	sectionSave($title, $position, $description, $content, $script);
	}

if( isset($update) && $update = "order")
	{
	saveSectionsOrder($listleft, $listright);
	}

if( !isset($idx))
	$idx = "List";


switch($idx)
	{
	case "Order":
		$body->title = babTranslate("Create section");
		sectionsOrder();
		$body->addItemMenu("List", babTranslate("Sections"),$GLOBALS['babUrl']."index.php?tg=sections&idx=List");
		$body->addItemMenu("Order", babTranslate("Order"),$GLOBALS['babUrl']."index.php?tg=sections&idx=Order");
		$body->addItemMenu("Create", babTranslate("Create"),$GLOBALS['babUrl']."index.php?tg=sections&idx=Create");
		break;
	case "Create":
		$body->title = babTranslate("Create section");
		sectionCreate();
		$body->addItemMenu("List", babTranslate("Sections"),$GLOBALS['babUrl']."index.php?tg=sections&idx=List");
		$body->addItemMenu("Order", babTranslate("Order"),$GLOBALS['babUrl']."index.php?tg=sections&idx=Order");
		$body->addItemMenu("Create", babTranslate("Create"),$GLOBALS['babUrl']."index.php?tg=sections&idx=Create");
		break;
	case "List":
	default:
		$body->title = babTranslate("Sections list");
		if( sectionsList() > 0 )
			{
			$body->addItemMenu("List", babTranslate("Sections"),$GLOBALS['babUrl']."index.php?tg=sections&idx=List");
			}
		else
			$body->title = babTranslate("There is no section");

		$body->addItemMenu("Order", babTranslate("Order"),$GLOBALS['babUrl']."index.php?tg=sections&idx=Order");
		$body->addItemMenu("Create", babTranslate("Create"),$GLOBALS['babUrl']."index.php?tg=sections&idx=Create");
		break;
	}

$body->setCurrentItemMenu($idx);


?>