<?php
/************************************************************************
 * OVIDENTIA http://www.ovidentia.org                                   *
 ************************************************************************
 * Copyright (c) 2003 by CANTICO ( http://www.cantico.fr )              *
 *                                                                      *
 * This file is part of Ovidentia.                                      *
 *                                                                      *
 * Ovidentia is free software; you can redistribute it and/or modify    *
 * it under the terms of the GNU General Public License as published by *
 * the Free Software Foundation; either version 2, or (at your option)  *
 * any later version.													*
 *																		*
 * This program is distributed in the hope that it will be useful, but  *
 * WITHOUT ANY WARRANTY; without even the implied warranty of			*
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.					*
 * See the  GNU General Public License for more details.				*
 *																		*
 * You should have received a copy of the GNU General Public License	*
 * along with this program; if not, write to the Free Software			*
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307,*
 * USA.																	*
************************************************************************/
include_once "base.php";
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
		var $delete;
		var $langLabel;
		var $langValue;
		var $langSelected;
		var $langFiles;
		var $countLangFiles;
		var $arrtmpl;
		var $counttmpl;
		var $templatetxt;
		var $tmplselected;

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
			$this->delete = bab_translate("Delete");
			$this->templatetxt = bab_translate('Template');
			$this->langLabel = bab_translate('Language');
			$this->langFiles = $GLOBALS['babLangFilter']->getLangFiles();
			$this->countLangFiles = count($this->langFiles);
			$this->id = $id;
			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_SECTIONS_TBL." where id='$id'";
			$this->res = $this->db->db_query($req);
			if( $this->db->db_num_rows($this->res) > 0 )
				{
				$this->arr = $this->db->db_fetch_array($this->res);
				$this->titleval = htmlentities($this->arr['title']);
				$this->pos = $this->arr['position'];
				$this->descval = htmlentities($this->arr['description']);
				$this->contentval = $this->arr['content'];
				if( $this->arr['script'] == "Y")
					$this->ischecked = "checked";
				else
					$this->ischecked = "";
				}
			if(( $this->arr['jscript'] == "N" && strtolower(bab_browserAgent()) == "msie") && (bab_browserOS() == "windows"))
				$this->msie = 1;
			else
				$this->msie = 0;	

			$file = "sectiontemplate.html";
			$filepath = "skins/".$GLOBALS['babSkin']."/templates/". $file;
			if( !file_exists( $filepath ) )
				{
				$filepath = $GLOBALS['babSkinPath']."templates/". $file;
				if( !file_exists( $filepath ) )
					{
					$filepath = $GLOBALS['babInstallPath']."skins/ovidentia/templates/". $file;
					}
				}
			if( file_exists( $filepath ) )
				{
				$tpl = new babTemplate();
				$this->arrtmpl = $tpl->getTemplates($filepath);
				}
			$this->counttmpl = count($this->arrtmpl);
			}

		function getnexttemplate()
			{
			static $i = 0;
			if($i < $this->counttmpl)
				{
				$this->templateid = $this->arrtmpl[$i];
				$this->templateval = $this->arrtmpl[$i];
				if( $this->templateid == $this->arr['template'])
					$this->tmplselected = "selected";
				else
					$this->tmplselected = "";
				$i++;
				return true;
				}
			return false;
			}
			
		function getnextlang()
			{
			static $i = 0;
			if($i < $this->countLangFiles)
				{
				$this->langValue = $this->langFiles[$i];
				if($this->langValue == $this->arr['lang'])
					{
					$this->langSelected = 'selected';
					}
				else
					{
					$this->langSelected = '';
					}
				$i++;
				return true;
				}
			return false;
			}

		}

	$temp = new temp($id);
	if( $temp->db->db_num_rows($temp->res) > 0 )
		$babBody->babecho(	bab_printTemplate($temp, "sections.html", "sectionsmodify"));
	else
		$babBody->msgerror = bab_translate("ERROR: You must choose a valid section !!");
	} // function sectionModify

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

function sectionUpdate($id, $title, $desc, $content, $script, $template, $lang)
	{
	global $babBody;

	if( empty($title))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a title !!");
		return false;
		}

	if( $script == "Y")
		$php = "Y";
	else
		$php = "N";
	$db = $GLOBALS['babDB'];
	$query = "select * from ".BAB_SECTIONS_TBL." where id='".$id."'";
	$res = $db->db_query($query);
	$arr = $db->db_fetch_array($res);

	$content = bab_stripDomainName($content);
	if( !bab_isMagicQuotesGpcOn())
		{
		$desc = addslashes($desc);
		$content = addslashes(bab_stripDomainName($content));
		$title = addslashes($title);
		$template = addslashes($template);
		}
	$query = "update ".BAB_SECTIONS_TBL." set title='".$title."', description='".$desc."', content='".bab_stripDomainName($content)."', script='".$php."', template='".$template."', lang='".$lang."' where id='".$id."'";
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
	if( isset($submit))
		sectionUpdate($item, $title, $description, $content, $script, $template, $lang);
	else if(isset($secdel))
		$idx = "Delete";
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
		break;
	default:
	case "Modify":
		$babBody->title = getSectionName($item);
		sectionModify($item);
		$babBody->addItemMenu("List", bab_translate("Sections"),$GLOBALS['babUrlScript']."?tg=sections&idx=List");
		$babBody->addItemMenu("Modify", bab_translate("Modify"),$GLOBALS['babUrlScript']."?tg=section&idx=Modify&item=".$item);
		$babBody->addItemMenu("Groups", bab_translate("Access"),$GLOBALS['babUrlScript']."?tg=section&idx=Groups&item=".$item);
		break;
	}

$babBody->setCurrentItemMenu($idx);


?>
