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
		var $counta;
		var $resa;
		var $secchecked;
		var $disabled;
		var $checkall;
		var $uncheckall;
		var $update;

		function temp()
			{
			$this->title = babTranslate("Title");
			$this->description = babTranslate("Description");
			$this->disabled = babTranslate("Disabled");
			$this->uncheckall = babTranslate("Uncheck all");
			$this->checkall = babTranslate("Check all");
			$this->update = babTranslate("Disable");
			$this->db = new db_mysql();
			$req = "select * from sections";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);

			/* don't get Administrator section */
			$this->resa = $this->db->db_query("select * from private_sections where id > '1'");
			$this->counta = $this->db->db_num_rows($this->resa);
			}

		function getnextp()
			{
			static $i = 0;
			if( $i < $this->counta)
				{
				$this->arr = $this->db->db_fetch_array($this->resa);
				$this->arr['title'] = babTranslate($this->arr['title']);
				$this->arr['description'] = babTranslate($this->arr['description']);
				if( $this->arr['enabled'] == "N")
					$this->secchecked = "checked";
				else
					$this->secchecked = "";
				$i++;
				return true;
				}
			else
				return false;

			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$this->arr = $this->db->db_fetch_array($this->res);
				$this->url = $GLOBALS['babUrl']."index.php?tg=section&idx=Modify&item=".$this->arr['id'];
				if( $this->arr['enabled'] == "N")
					$this->secchecked = "checked";
				else
					$this->secchecked = "";
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
				if( $arr['private'] == "Y" )
					$this->listleftsecval = babTranslate($arr2['title']);
				else
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
				if( $arr['private'] == "Y" )
					$this->listrightsecval = babTranslate($arr2['title']);
				else
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

function sectionCreate($jscript)
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
		var $jscript;

		function temp($jscript)
			{
			$this->title = babTranslate("Title");
			$this->description = babTranslate("Description");
			$this->position = babTranslate("Position");
			$this->content = babTranslate("Content");
			$this->create = babTranslate("Create");
			$this->left = babTranslate("Left");
			$this->right = babTranslate("Right");
			$this->script = babTranslate("PHP script");
			$this->jscript = $jscript;
			if( $jscript == 0 && strtolower(browserAgent()) == "msie")
				$this->msie = 1;
			else
				$this->msie = 0;	
			}
		}

	$temp = new temp($jscript);
	$body->babecho(	babPrintTemplate($temp,"sections.html", "sectionscreate"));
	}



function sectionSave($title, $pos, $desc, $content, $script, $js)
	{
	global $body;
	if( empty($title))
		{
		$body->msgerror = babTranslate("ERROR: You must provide a title !!");
		return;
		}

	$db = new db_mysql();
	$query = "select * from sections where title='$title'";	
	$res = $db->db_query($query);
	if( $db->db_num_rows($res) > 0)
		{
		$body->msgerror = babTranslate("ERROR: This section already exists");
		}
	else
		{
		if( $script == "Y")
			$php = "Y";
		else
			$php = "N";

		if( $js == 1)
			$js = "Y";
		else
			$js = "N";
		$query = "insert into sections (title, position, description, content, script, jscript) VALUES ('" .$title. "', '" . $pos. "', '" . $desc. "', '" . $content. "', '" . $php. "', '" . $js."')";
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

function disableSections($sections)
	{
	$db = new db_mysql();
	$req = "select id from sections";
	$res = $db->db_query($req);
	while( $row = $db->db_fetch_array($res))
		{
		if( count($sections) > 0 && in_array($row['id']."N", $sections))
			$enabled = "N";
		else
			$enabled = "Y";

		$req = "update sections set enabled='".$enabled."' where id='".$row['id']."'";
		$db->db_query($req);
		}

	$req = "select id from private_sections";
	$res = $db->db_query($req);
	while( $row = $db->db_fetch_array($res))
		{
		if( count($sections) > 0 && in_array($row['id']."Y", $sections))
			$enabled = "N";
		else
			$enabled = "Y";

		$req = "update private_sections set enabled='".$enabled."' where id='".$row['id']."'";
		$db->db_query($req);
		}
	}

/* main */
if( isset($create))
	{
	sectionSave($title, $position, $description, $content, $script, $js);
	}

if( isset($update))
	{
	if( $update == "order" )
		saveSectionsOrder($listleft, $listright);
	else if( $update == "disable" )
		disableSections($sections);
	}

if( !isset($idx))
	$idx = "List";


switch($idx)
	{
	case "Order":
		$body->title = babTranslate("Sections order");
		sectionsOrder();
		$body->addItemMenu("List", babTranslate("Sections"),$GLOBALS['babUrl']."index.php?tg=sections&idx=List");
		$body->addItemMenu("Order", babTranslate("Order"),$GLOBALS['babUrl']."index.php?tg=sections&idx=Order");
		$body->addItemMenu("ch", babTranslate("Create")."(html)",$GLOBALS['babUrl']."index.php?tg=sections&idx=ch");
		$body->addItemMenu("cj", babTranslate("Create")."(script)",$GLOBALS['babUrl']."index.php?tg=sections&idx=cj");
		break;
	case "ch":
		$body->title = babTranslate("Create section");
		sectionCreate(0);
		$body->addItemMenu("List", babTranslate("Sections"),$GLOBALS['babUrl']."index.php?tg=sections&idx=List");
		$body->addItemMenu("Order", babTranslate("Order"),$GLOBALS['babUrl']."index.php?tg=sections&idx=Order");
		$body->addItemMenu("ch", babTranslate("Create")."(html)",$GLOBALS['babUrl']."index.php?tg=sections&idx=ch");
		$body->addItemMenu("cj", babTranslate("Create")."(script)",$GLOBALS['babUrl']."index.php?tg=sections&idx=cj");
		break;
	case "cj":
		$body->title = babTranslate("Create section");
		sectionCreate(1);
		$body->addItemMenu("List", babTranslate("Sections"),$GLOBALS['babUrl']."index.php?tg=sections&idx=List");
		$body->addItemMenu("Order", babTranslate("Order"),$GLOBALS['babUrl']."index.php?tg=sections&idx=Order");
		$body->addItemMenu("ch", babTranslate("Create")."(html)",$GLOBALS['babUrl']."index.php?tg=sections&idx=ch");
		$body->addItemMenu("cj", babTranslate("Create")."(script)",$GLOBALS['babUrl']."index.php?tg=sections&idx=cj");
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
		$body->addItemMenu("ch", babTranslate("Create")."(html)",$GLOBALS['babUrl']."index.php?tg=sections&idx=ch");
		$body->addItemMenu("cj", babTranslate("Create")."(script)",$GLOBALS['babUrl']."index.php?tg=sections&idx=cj");
		break;
	}

$body->setCurrentItemMenu($idx);


?>