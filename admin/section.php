<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
include $babInstallPath."admin/acl.php";

function getSectionName($id)
	{
	$db = $GLOBALS['babDB'];
	$query = "select * from ".BAB_SECTIONS_TBL." where id='$id'";
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

	global $babBody;
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
			$this->title = bab_translate("Title");
			$this->description = bab_translate("Description");
			$this->content = bab_translate("Content");
			$this->left = bab_translate("Left");
			$this->right = bab_translate("Right");
			$this->position = bab_translate("Position");
			$this->script = bab_translate("PHP script");
			$this->modify = bab_translate("Modify");
			$this->id = $id;
			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_SECTIONS_TBL." where id='$id'";
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
			if(( $arr['jscript'] == "N" && strtolower(bab_browserAgent()) == "msie") && (bab_browserOS() == "windows"))
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
		$babBody->babecho(	bab_printTemplate($temp, "sections.html", "sectionsmodify"));
	else
		$babBody->msgerror = bab_translate("ERROR: You must choose a valid section !!");
	}

function sectionDelete($id)
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
			$this->message = bab_translate("Are you sure you want to delete this section");
			$this->title = getSectionName($id);
			$this->warning = bab_translate("WARNING: This operation will delete the section and all references"). "!";
			$this->urlyes = $GLOBALS['babUrlScript']."?tg=section&idx=Delete&section=".$id."&action=Yes";
			$this->yes = bab_translate("Yes");
			$this->urlno = $GLOBALS['babUrlScript']."?tg=section&idx=Modify&item=".$id;
			$this->no = bab_translate("No");
			}
		}

	$temp = new temp($id);
	$babBody->babecho(	bab_printTemplate($temp,"warning.html", "warningyesno"));
	}

function sectionUpdate($id, $title, $desc, $content, $script)
	{
	if( $script == "Y")
		$php = "Y";
	else
		$php = "N";
	$db = $GLOBALS['babDB'];
	$query = "select * from ".BAB_SECTIONS_TBL." where id='".$id."'";
	$res = $db->db_query($query);
	$arr = $db->db_fetch_array($res);
	/*
	if( $arr['position'] != $pos)
		{
		$query = "select max(ordering) from ".BAB_SECTIONS_ORDER_TBL." where private='N' and position='".$arr['position']."'";
		$res = $db->db_query($query);
		$arr = $db->db_fetch_array($res);
		$query = "update ".BAB_SECTIONS_ORDER_TBL." set position='".$pos."', ordering='".($arr[0]+1)."' where id_section='".$id."'";
		$db->db_query($query);
		}
	*/
	//$query = "update ".BAB_SECTIONS_TBL." set title='$title', position='$pos', description='$desc', content='$content', script='$php' where id=$id";
	if(!get_cfg_var("magic_quotes_gpc"))
		{
		$desc = addslashes($desc);
		$content = addslashes($content);
		$title = addslashes($title);
		}
			
	$query = "update ".BAB_SECTIONS_TBL." set title='$title', description='$desc', content='$content', script='$php' where id=$id";
	$db->db_query($query);
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=sections&idx=List");
	}

function confirmDeleteSection($id)
	{
	$db = $GLOBALS['babDB'];

	// delete refernce group
	$req = "delete from ".BAB_SECTIONS_GROUPS_TBL." where id_object='$id'";
	$res = $db->db_query($req);	

	// delete from ".BAB_SECTIONS_ORDER_TBL
	$req = "delete from ".BAB_SECTIONS_ORDER_TBL." where id_section='$id' and type='2'";
	$res = $db->db_query($req);	

	// delete from BAB_SECTIONS_STATES_TBL
	$req = "delete from ".BAB_SECTIONS_STATES_TBL." where id_section='$id' and type='2'";
	$res = $db->db_query($req);	

	// delete section
	$req = "delete from ".BAB_SECTIONS_TBL." where id='$id'";
	$res = $db->db_query($req);
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=sections&idx=List");
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
		$babBody->title = getSectionName($item);
		sectionDelete($item);
		$babBody->addItemMenu("List", bab_translate("Sections"),$GLOBALS['babUrlScript']."?tg=sections&idx=List");
		$babBody->addItemMenu("Modify", bab_translate("Modify"),$GLOBALS['babUrlScript']."?tg=section&idx=Modify&item=".$item);
		$babBody->addItemMenu("Groups", bab_translate("Access"),$GLOBALS['babUrlScript']."?tg=section&idx=Groups&item=".$item);
		$babBody->addItemMenu("Delete", bab_translate("Delete"),$GLOBALS['babUrlScript']."?tg=section&idx=Delete&item=".$item);
		break;
	case "Groups":
		$babBody->title = getSectionName($item) . bab_translate(" is visible by groups");
		aclGroups("section", "Modify", BAB_SECTIONS_GROUPS_TBL, $item, "aclsec");
		$babBody->addItemMenu("List", bab_translate("Sections"),$GLOBALS['babUrlScript']."?tg=sections&idx=List");
		$babBody->addItemMenu("Modify", bab_translate("Modify"),$GLOBALS['babUrlScript']."?tg=section&idx=Modify&item=".$item);
		$babBody->addItemMenu("Groups", bab_translate("Access"),$GLOBALS['babUrlScript']."?tg=section&idx=Groups&item=".$item);
		$babBody->addItemMenu("Delete", bab_translate("Delete"),$GLOBALS['babUrlScript']."?tg=section&idx=Delete&item=".$item);
		break;
	default:
	case "Modify":
		$babBody->title = getSectionName($item);
		sectionModify($item);
		$babBody->addItemMenu("List", bab_translate("Sections"),$GLOBALS['babUrlScript']."?tg=sections&idx=List");
		$babBody->addItemMenu("Modify", bab_translate("Modify"),$GLOBALS['babUrlScript']."?tg=section&idx=Modify&item=".$item);
		$babBody->addItemMenu("Groups", bab_translate("Access"),$GLOBALS['babUrlScript']."?tg=section&idx=Groups&item=".$item);
		$babBody->addItemMenu("Delete", bab_translate("Delete"),$GLOBALS['babUrlScript']."?tg=section&idx=Delete&item=".$item);
		break;
	}

$babBody->setCurrentItemMenu($idx);


?>