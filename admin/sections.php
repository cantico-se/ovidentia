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

function sectionsList()
	{
	global $babBody;
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
		var $access;
		var $accessurl;
		var $groups;

		function temp()
			{
			$this->title = bab_translate("Title");
			$this->description = bab_translate("Description");
			$this->disabled = bab_translate("Disabled");
			$this->uncheckall = bab_translate("Uncheck all");
			$this->checkall = bab_translate("Check all");
			$this->update = bab_translate("Update");
			$this->access = bab_translate("Access");
			$this->groups = bab_translate("View");
			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_SECTIONS_TBL."";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);

			/* don't get Administrator section */
			$this->resa = $this->db->db_query("select * from ".BAB_PRIVATE_SECTIONS_TBL." where id > '1'");
			$this->counta = $this->db->db_num_rows($this->resa);

			$this->rescat = $this->db->db_query("select * from ".BAB_TOPICS_CATEGORIES_TBL."");
			$this->countcat = $this->db->db_num_rows($this->rescat);
			}

		function getnextp()
			{
			static $i = 0;
			if( $i < $this->counta)
				{
				$this->arr = $this->db->db_fetch_array($this->resa);
				$this->arr['title'] = bab_translate($this->arr['title']);
				$this->arr['description'] = bab_translate($this->arr['description']);
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
				$this->arr['title'] = $this->arr['title'];
				$this->arr['description'] = $this->arr['description'];
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
				$this->url = $GLOBALS['babUrlScript']."?tg=section&idx=Modify&item=".$this->arr['id'];
				$this->accessurl = $GLOBALS['babUrlScript']."?tg=section&idx=Groups&item=".$this->arr['id'];
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
	$babBody->babecho(	bab_printTemplate($temp, "sections.html", "sectionslist"));
	return $temp->count;
	}

function sectionsOrder()
	{
	global $babBody;
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
			$this->listleftsectxt = "----------------- ". bab_translate("Left sections") . " -----------------";
			$this->listrightsectxt = "----------------- ". bab_translate("Right sections") . " -----------------";
			$this->update = bab_translate("Update");
			$this->moveup = bab_translate("Move Up");
			$this->movedown = bab_translate("Move Down");
			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_SECTIONS_ORDER_TBL." where position='0' order by ordering asc";
			$this->resleft = $this->db->db_query($req);
			$this->countleft = $this->db->db_num_rows($this->resleft);
			$req = "select * from ".BAB_SECTIONS_ORDER_TBL." where position='1' order by ordering asc";
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
						$req = "select * from ".BAB_PRIVATE_SECTIONS_TBL." where id ='".$arr['id_section']."'";
						break;
					case "3":
						$req = "select * from ".BAB_TOPICS_CATEGORIES_TBL." where id ='".$arr['id_section']."'";
						break;
					case "4":
						$req = "select * from ".BAB_ADDONS_TBL." where id ='".$arr['id_section']."'";
						break;
					default:
						$req = "select * from ".BAB_SECTIONS_TBL." where id ='".$arr['id_section']."'";
						break;
					}
				$res2 = $this->db->db_query($req);
				$arr2 = $this->db->db_fetch_array($res2);
				if( $arr['type'] == "1" )
					$this->listleftsecval = bab_translate($arr2['title']);
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
						$req = "select * from ".BAB_PRIVATE_SECTIONS_TBL." where id ='".$arr['id_section']."'";
						break;
					case "3":
						$req = "select * from ".BAB_TOPICS_CATEGORIES_TBL." where id ='".$arr['id_section']."'";
						break;
					case "4":
						$req = "select * from ".BAB_ADDONS_TBL." where id ='".$arr['id_section']."'";
						break;
					default:
						$req = "select * from ".BAB_SECTIONS_TBL." where id ='".$arr['id_section']."'";
						break;
					}
				$res2 = $this->db->db_query($req);
				$arr2 = $this->db->db_fetch_array($res2);
				if( $arr['type'] == "1" )
					$this->listrightsecval = bab_translate($arr2['title']);
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
	$babBody->babecho(	bab_printTemplate($temp, "sections.html", "sectionordering"));
	return $temp->count;
	}

function sectionCreate($jscript)
	{
	global $babBody;
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
			$this->title = bab_translate("Title");
			$this->description = bab_translate("Description");
			$this->position = bab_translate("Position");
			$this->content = bab_translate("Content");
			$this->create = bab_translate("Create");
			$this->left = bab_translate("Left");
			$this->right = bab_translate("Right");
			$this->script = bab_translate("PHP script");
			$this->jscript = $jscript;
			if(( $jscript == 0 && strtolower(bab_browserAgent()) == "msie") && (bab_browserOS() == "windows"))
				$this->msie = 1;
			else
				$this->msie = 0;	
			}
		}

	$temp = new temp($jscript);
	$babBody->babecho(	bab_printTemplate($temp,"sections.html", "sectionscreate"));
	}



function sectionSave($title, $pos, $desc, $content, $script, $js)
	{
	global $babBody;
	if( empty($title))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a title !!");
		return;
		}

	$db = $GLOBALS['babDB'];
	$query = "select * from ".BAB_SECTIONS_TBL." where title='$title'";	
	$res = $db->db_query($query);
	if( $db->db_num_rows($res) > 0)
		{
		$babBody->msgerror = bab_translate("ERROR: This section already exists");
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
		$query = "insert into ".BAB_SECTIONS_TBL." (title, position, description, content, script, jscript) VALUES ('" .$title. "', '" . $pos. "', '" . $desc. "', '" . $content. "', '" . $php. "', '" . $js."')";
		$db->db_query($query);
		$id = $db->db_insert_id();
		$db->db_query("insert into ".BAB_SECTIONS_GROUPS_TBL." (id_object, id_group) values ('". $id. "', '3')");
		$res = $db->db_query("select max(ordering) from ".BAB_SECTIONS_ORDER_TBL." where position='".$pos."'");
		$arr = $db->db_fetch_array($res);
		$db->db_query("insert into ".BAB_SECTIONS_ORDER_TBL." (id_section, position, type, ordering) VALUES ('" .$id. "', '" . $pos. "', '2', '" . ($arr[0]+1). "')");		
		}
	}

function saveSectionsOrder($listleft, $listright)
	{
		$db = $GLOBALS['babDB'];

		for( $i = 0; $i < count($listleft); $i++)
		{
			$db->db_query("update ".BAB_SECTIONS_ORDER_TBL." set position='0', ordering='".($i+1)."' where id='".$listleft[$i]."'");
			$arr = $db->db_fetch_array($db->db_query("select id, type from ".BAB_SECTIONS_ORDER_TBL." where id='".$listleft[$i]."'"));
			if( $arr['type'] == "2")
				{
				$db->db_query("update ".BAB_SECTIONS_TBL." set position='0' where id='".$listleft[$i]."'");
				}
		}

		for( $i = 0; $i < count($listright); $i++)
		{
			$db->db_query("update ".BAB_SECTIONS_ORDER_TBL." set position='1', ordering='".($i+1)."' where id='".$listright[$i]."'");
			$arr = $db->db_fetch_array($db->db_query("select id, type from ".BAB_SECTIONS_ORDER_TBL." where id='".$listright[$i]."'"));
			if( $arr['type'] == "2")
				{
				$db->db_query("update ".BAB_SECTIONS_TBL." set position='1' where id='".$listright[$i]."'");
				}
		}
	}

function disableSections($sections)
	{
	$db = $GLOBALS['babDB'];
	$req = "select id from ".BAB_SECTIONS_TBL."";
	$res = $db->db_query($req);
	while( $row = $db->db_fetch_array($res))
		{
		if( count($sections) > 0 && in_array($row['id']."-2", $sections))
			$enabled = "N";
		else
			$enabled = "Y";

		$req = "update ".BAB_SECTIONS_TBL." set enabled='".$enabled."' where id='".$row['id']."'";
		$db->db_query($req);
		}

	$req = "select id from ".BAB_PRIVATE_SECTIONS_TBL."";
	$res = $db->db_query($req);
	while( $row = $db->db_fetch_array($res))
		{
		if( count($sections) > 0 && in_array($row['id']."-1", $sections))
			$enabled = "N";
		else
			$enabled = "Y";

		$req = "update ".BAB_PRIVATE_SECTIONS_TBL." set enabled='".$enabled."' where id='".$row['id']."'";
		$db->db_query($req);
		}

	$req = "select id from ".BAB_TOPICS_CATEGORIES_TBL."";
	$res = $db->db_query($req);
	while( $row = $db->db_fetch_array($res))
		{
		if( count($sections) > 0 && in_array($row['id']."-3", $sections))
			$enabled = "N";
		else
			$enabled = "Y";

		$req = "update ".BAB_TOPICS_CATEGORIES_TBL." set enabled='".$enabled."' where id='".$row['id']."'";
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
		$babBody->title = bab_translate("Sections order");
		sectionsOrder();
		$babBody->addItemMenu("List", bab_translate("Sections"),$GLOBALS['babUrlScript']."?tg=sections&idx=List");
		$babBody->addItemMenu("Order", bab_translate("Order"),$GLOBALS['babUrlScript']."?tg=sections&idx=Order");
		$babBody->addItemMenu("ch", bab_translate("Create")."(html)",$GLOBALS['babUrlScript']."?tg=sections&idx=ch");
		$babBody->addItemMenu("cj", bab_translate("Create")."(script)",$GLOBALS['babUrlScript']."?tg=sections&idx=cj");
		break;
	case "ch":
		$babBody->title = bab_translate("Create section");
		sectionCreate(0);
		$babBody->addItemMenu("List", bab_translate("Sections"),$GLOBALS['babUrlScript']."?tg=sections&idx=List");
		$babBody->addItemMenu("Order", bab_translate("Order"),$GLOBALS['babUrlScript']."?tg=sections&idx=Order");
		$babBody->addItemMenu("ch", bab_translate("Create")."(html)",$GLOBALS['babUrlScript']."?tg=sections&idx=ch");
		$babBody->addItemMenu("cj", bab_translate("Create")."(script)",$GLOBALS['babUrlScript']."?tg=sections&idx=cj");
		break;
	case "cj":
		$babBody->title = bab_translate("Create section");
		sectionCreate(1);
		$babBody->addItemMenu("List", bab_translate("Sections"),$GLOBALS['babUrlScript']."?tg=sections&idx=List");
		$babBody->addItemMenu("Order", bab_translate("Order"),$GLOBALS['babUrlScript']."?tg=sections&idx=Order");
		$babBody->addItemMenu("ch", bab_translate("Create")."(html)",$GLOBALS['babUrlScript']."?tg=sections&idx=ch");
		$babBody->addItemMenu("cj", bab_translate("Create")."(script)",$GLOBALS['babUrlScript']."?tg=sections&idx=cj");
		break;
	case "List":
	default:
		$babBody->title = bab_translate("Sections list");
		if( sectionsList() > 0 )
			{
			$babBody->addItemMenu("List", bab_translate("Sections"),$GLOBALS['babUrlScript']."?tg=sections&idx=List");
			}
		else
			$babBody->title = bab_translate("There is no section");

		$babBody->addItemMenu("Order", bab_translate("Order"),$GLOBALS['babUrlScript']."?tg=sections&idx=Order");
		$babBody->addItemMenu("ch", bab_translate("Create")."(html)",$GLOBALS['babUrlScript']."?tg=sections&idx=ch");
		$babBody->addItemMenu("cj", bab_translate("Create")."(script)",$GLOBALS['babUrlScript']."?tg=sections&idx=cj");
		break;
	}

$babBody->setCurrentItemMenu($idx);


?>