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


function sectionModify($id)
	{

	global $body;
	class temp
		{
		var $title;
		var $description;
		var $content;
		var $left;
		var $right;
		var $script;
		var $position;
		var $modify;
	
		var $titleval;
		var $descval;
		var $contentval;
		var $ischecked;
		var $pos;
		var $id;
		var $arr = array();
		var $db;
		var $res;
		var $msie;

		function temp($id)
			{
			$this->title = babTranslate("Title");
			$this->description = babTranslate("Description");
			$this->content = babTranslate("Content");
			$this->left = babTranslate("Left");
			$this->right = babTranslate("Right");
			$this->position = babTranslate("Position");
			$this->script = babTranslate("PHP script");
			$this->modify = babTranslate("Modify");
			$this->id = $id;
			$this->db = new db_mysql();
			$req = "select * from sections where id='$id'";
			$this->res = $this->db->db_query($req);
			if( $this->db->db_num_rows($this->res) > 0 )
				{
				$arr = $this->db->db_fetch_array($this->res);
				$this->titleval = $arr['title'];
				$this->pos = $arr['position'];
				$this->descval = $arr['description'];
				$this->contentval = $arr['content'];
				if( $arr['script'] == "Y")
					$this->ischecked = "checked";
				else
					$this->ischecked = "";
				}
			if(( $arr['jscript'] == "N" && strtolower(browserAgent()) == "msie") && (browserOS() == "windows"))
				$this->msie = 1;
			else
				$this->msie = 0;	
			}

		function getnext()
			{
			return false;
			}
		}

	$temp = new temp($id);
	if( $temp->db->db_num_rows($temp->res) > 0 )
		$body->babecho(	babPrintTemplate($temp, "sections.html", "sectionsmodify"));
	else
		$body->msgerror = babTranslate("ERROR: You must choose a valid section !!");
	}

function sectionDelete($id)
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
			$this->message = babTranslate("Are you sure you want to delete this section");
			$this->title = getSectionName($id);
			$this->warning = babTranslate("WARNING: This operation will delete the section and all references"). "!";
			$this->urlyes = $GLOBALS['babUrl']."index.php?tg=section&idx=Delete&section=".$id."&action=Yes";
			$this->yes = babTranslate("Yes");
			$this->urlno = $GLOBALS['babUrl']."index.php?tg=section&idx=Modify&item=".$id;
			$this->no = babTranslate("No");
			}
		}

	$temp = new temp($id);
	$body->babecho(	babPrintTemplate($temp,"warning.html", "warningyesno"));
	}

function sectionUpdate($id, $title, $desc, $content, $script)
	{
	if( $script == "Y")
		$php = "Y";
	else
		$php = "N";
	$db = new db_mysql();
	$query = "select * from sections where id='".$id."'";
	$res = $db->db_query($query);
	$arr = $db->db_fetch_array($res);
	/*
	if( $arr['position'] != $pos)
		{
		$query = "select max(ordering) from sections_order where private='N' and position='".$arr['position']."'";
		$res = $db->db_query($query);
		$arr = $db->db_fetch_array($res);
		$query = "update sections_order set position='".$pos."', ordering='".($arr[0]+1)."' where id_section='".$id."'";
		$db->db_query($query);
		}
	*/
	//$query = "update sections set title='$title', position='$pos', description='$desc', content='$content', script='$php' where id=$id";
	if(!get_cfg_var("magic_quotes_gpc"))
		{
		$desc = addslashes($desc);
		$content = addslashes($content);
		$title = addslashes($title);
		}
			
	$query = "update sections set title='$title', description='$desc', content='$content', script='$php' where id=$id";
	$db->db_query($query);
	Header("Location: index.php?tg=sections&idx=List");
	}

function confirmDeleteSection($id)
	{
	$db = new db_mysql();

	// delete refernce group
	$req = "delete from sections_groups where id_object='$id'";
	$res = $db->db_query($req);	

	// delete from sections_order
	$req = "delete from sections_order where id_section='$id' and type='2'";
	$res = $db->db_query($req);	

	// delete from sections_states
	$req = "delete from sections_states where id_section='$id' and type='2'";
	$res = $db->db_query($req);	

	// delete section
	$req = "delete from sections where id='$id'";
	$res = $db->db_query($req);
	Header("Location: index.php?tg=sections&idx=List");
	}

/* main */
if( isset($modify))
	{
	sectionUpdate($item, $title, $description, $content, $script);
	}

if( isset($aclsec))
	aclUpdate($table, $item, $groups, $what);

if( !isset($idx))
	$idx = "Modify";

if( isset($action) && $action == "Yes")
	{
	confirmDeleteSection($section);
	}

switch($idx)
	{
	case "Delete":
		$body->title = getSectionName($item);
		sectionDelete($item);
		$body->addItemMenu("List", babTranslate("Sections"),$GLOBALS['babUrl']."index.php?tg=sections&idx=List");
		$body->addItemMenu("Modify", babTranslate("Modify"),$GLOBALS['babUrl']."index.php?tg=section&idx=Modify&item=".$item);
		$body->addItemMenu("Groups", babTranslate("Access"),$GLOBALS['babUrl']."index.php?tg=section&idx=Groups&item=".$item);
		$body->addItemMenu("Delete", babTranslate("Delete"),$GLOBALS['babUrl']."index.php?tg=section&idx=Delete&item=".$item);
		break;
	case "Groups":
		$body->title = getSectionName($item) . babTranslate(" is visible by groups");
		aclGroups("section", "Modify", "sections_groups", $item, "aclsec");
		$body->addItemMenu("List", babTranslate("Sections"),$GLOBALS['babUrl']."index.php?tg=sections&idx=List");
		$body->addItemMenu("Modify", babTranslate("Modify"),$GLOBALS['babUrl']."index.php?tg=section&idx=Modify&item=".$item);
		$body->addItemMenu("Groups", babTranslate("Access"),$GLOBALS['babUrl']."index.php?tg=section&idx=Groups&item=".$item);
		$body->addItemMenu("Delete", babTranslate("Delete"),$GLOBALS['babUrl']."index.php?tg=section&idx=Delete&item=".$item);
		break;
	default:
	case "Modify":
		$body->title = getSectionName($item);
		sectionModify($item);
		$body->addItemMenu("List", babTranslate("Sections"),$GLOBALS['babUrl']."index.php?tg=sections&idx=List");
		$body->addItemMenu("Modify", babTranslate("Modify"),$GLOBALS['babUrl']."index.php?tg=section&idx=Modify&item=".$item);
		$body->addItemMenu("Groups", babTranslate("Access"),$GLOBALS['babUrl']."index.php?tg=section&idx=Groups&item=".$item);
		$body->addItemMenu("Delete", babTranslate("Delete"),$GLOBALS['babUrl']."index.php?tg=section&idx=Delete&item=".$item);
		break;
	}

$body->setCurrentItemMenu($idx);


?>