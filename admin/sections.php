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
		var $countcat;
		var $rescat;
		var $secchecked;
		var $disabled;
		var $checkall;
		var $uncheckall;
		var $update;
		var $idvalue;

		function temp()
			{
			$this->title = babTranslate("Title");
			$this->description = babTranslate("Description");
			$this->disabled = babTranslate("Disabled");
			$this->uncheckall = babTranslate("Uncheck all");
			$this->checkall = babTranslate("Check all");
			$this->update = babTranslate("Update");
			$this->db = new db_mysql();
			$req = "select * from sections";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);

			/* don't get Administrator section */
			$this->resa = $this->db->db_query("select * from private_sections where id > '1'");
			$this->counta = $this->db->db_num_rows($this->resa);

			$this->rescat = $this->db->db_query("select * from topics_categories");
			$this->countcat = $this->db->db_num_rows($this->rescat);
			}

		function getnextp()
			{
			static $i = 0;
			if( $i < $this->counta)
				{
				$this->arr = $this->db->db_fetch_array($this->resa);
				$this->arr['title'] = babTranslate($this->arr['title']);
				$this->arr['description'] = babTranslate($this->arr['description']);
				$this->idvalue = $this->arr['id']."-1";
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

		function getnextcat()
			{
			static $i = 0;
			if( $i < $this->countcat)
				{
				$this->arr = $this->db->db_fetch_array($this->rescat);
				$this->arr['title'] = babTranslate($this->arr['title']);
				$this->arr['description'] = babTranslate($this->arr['description']);
				$this->idvalue = $this->arr['id']."-3";
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
				$this->idvalue = $this->arr['id']."-2";
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
				switch( $arr['type'] )
					{
					case "1":
						$req = "select * from private_sections where id ='".$arr['id_section']."'";
						break;
					case "3":
						$req = "select * from topics_categories where id ='".$arr['id_section']."'";
						break;
					default:
						$req = "select * from sections where id ='".$arr['id_section']."'";
						break;
					}
				$res2 = $this->db->db_query($req);
				$arr2 = $this->db->db_fetch_array($res2);
				if( $arr['type'] == "1" )
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
				switch( $arr['type'] )
					{
					case "1":
						$req = "select * from private_sections where id ='".$arr['id_section']."'";
						break;
					case "3":
						$req = "select * from topics_categories where id ='".$arr['id_section']."'";
						break;
					default:
						$req = "select * from sections where id ='".$arr['id_section']."'";
						break;
					}
				$res2 = $this->db->db_query($req);
				$arr2 = $this->db->db_fetch_array($res2);
				if( $arr['type'] == "1" )
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
			if(( $jscript == 0 && strtolower(browserAgent()) == "msie") && (browserOS() == "windows"))
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
		if(!get_cfg_var("magic_quotes_gpc"))
			{
			$desc = addslashes($desc);
			$content = addslashes($content);
			$title = addslashes($title);
			}
		
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
		$db->db_query("insert into sections_groups (id_object, id_group) values ('". $id. "', '3')");
		$res = $db->db_query("select max(ordering) from sections_order where position='".$pos."'");
		$arr = $db->db_fetch_array($res);
		$db->db_query("insert into sections_order (id_section, position, type, ordering) VALUES ('" .$id. "', '" . $pos. "', '2', '" . ($arr[0]+1). "')");		
		}
	}

function saveSectionsOrder($listleft, $listright)
	{
		$db = new db_mysql();

		for( $i = 0; $i < count($listleft); $i++)
		{
			$db->db_query("update sections_order set position='0', ordering='".($i+1)."' where id='".$listleft[$i]."'");
			$arr = $db->db_fetch_array($db->db_query("select id, type from sections_order where id='".$listleft[$i]."'"));
			if( $arr['type'] == "2")
				{
				$db->db_query("update sections set position='0' where id='".$listleft[$i]."'");
				}
		}

		for( $i = 0; $i < count($listright); $i++)
		{
			$db->db_query("update sections_order set position='1', ordering='".($i+1)."' where id='".$listright[$i]."'");
			$arr = $db->db_fetch_array($db->db_query("select id, type from sections_order where id='".$listright[$i]."'"));
			if( $arr['type'] == "2")
				{
				$db->db_query("update sections set position='1' where id='".$listright[$i]."'");
				}
		}
	}

function disableSections($sections)
	{
	$db = new db_mysql();
	$req = "select id from sections";
	$res = $db->db_query($req);
	while( $row = $db->db_fetch_array($res))
		{
		if( count($sections) > 0 && in_array($row['id']."-2", $sections))
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
		if( count($sections) > 0 && in_array($row['id']."-1", $sections))
			$enabled = "N";
		else
			$enabled = "Y";

		$req = "update private_sections set enabled='".$enabled."' where id='".$row['id']."'";
		$db->db_query($req);
		}

	$req = "select id from topics_categories";
	$res = $db->db_query($req);
	while( $row = $db->db_fetch_array($res))
		{
		if( count($sections) > 0 && in_array($row['id']."-3", $sections))
			$enabled = "N";
		else
			$enabled = "Y";

		$req = "update topics_categories set enabled='".$enabled."' where id='".$row['id']."'";
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