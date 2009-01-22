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
include_once $babInstallPath."admin/acl.php";

function getFaqName($id)
	{
	$db = $GLOBALS['babDB'];
	$query = "select * from ".BAB_FAQCAT_TBL." where id='$id'";
	$res = $db->db_query($query);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		return $arr['category'];
		}
	else
		{
		return "";
		}
	}


function modifyCategory($id)
	{
	global $babBody;
	if( !isset($id))
		{
		$babBody->msgerror = bab_translate("ERROR: You must choose a valid category !!");
		return;
		}
	class temp
		{
		var $category;
		var $description;
		var $manager;
		var $managername;
		var $add;

		var $db;
		var $arr = array();
		var $res;
		var $delete;
		var $langLabel;
		var $langValue;
		var $langSelected;
		var $langFiles;
		var $countLangFiles;

		function temp($id)
			{
			global $babDB;
			
			$this->category = bab_translate("FAQ Name");
			$this->description = bab_translate("Description");
			$this->add = bab_translate("Update FAQ");
			$this->delete = bab_translate("Delete");
			$this->langLabel = bab_translate("Language");
			$this->langFiles = $GLOBALS['babLangFilter']->getLangFiles();
			$this->countLangFiles = count($this->langFiles);
			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_FAQCAT_TBL." where id=".$babDB->quote($id);
			$this->res = $this->db->db_query($req);
			$this->arr = $this->db->db_fetch_array($this->res);
			$this->arr['category'] = bab_toHtml($this->arr['category']);

			include_once $GLOBALS['babInstallPath']."utilit/editorincl.php";
			$editor = new bab_contentEditor('bab_faq');
			$editor->setContent($this->arr['description']);
			$editor->setFormat('html');
			$editor->setParameters(array('height' => 300));
			$this->editor = $editor->getEditor();
			
			
			$this->item = $id;
			$this->bdel = true;
			$this->tgval = "admfaq";
			$this->usersbrowurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=users&idx=brow&cb=");
			$this->faqname = $this->arr['category'];

			} // function temp
			
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
			} // function getnextlang

		} // class temp

	$temp = new temp($id);
	$babBody->babecho(	bab_printTemplate($temp,"admfaqs.html", "categorycreate"));
	}

function deleteCategory($id)
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

		function temp($id)
			{
			$this->message = bab_translate("Are you sure you want to delete this faq");
			$this->title = getFaqName($id);
			$this->warning = bab_translate("WARNING: This operation will delete the FAQ with all questions and responses"). "!";
			$this->urlyes = $GLOBALS['babUrlScript']."?tg=admfaq&idx=Delete&item=".$id."&action=Yes";
			$this->yes = bab_translate("Yes");
			$this->urlno = $GLOBALS['babUrlScript']."?tg=admfaq&idx=Modify&item=".$id;
			$this->no = bab_translate("No");
			}
		}

	$temp = new temp($id);
	$babBody->babecho(	bab_printTemplate($temp,"warning.html", "warningyesno"));
	}

function updateCategory($id, $category, $lang)
	{
	global $babBody, $babDB;
	if( empty($category))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a FAQ name !!");
		return false;
		}
		
	include_once $GLOBALS['babInstallPath']."utilit/editorincl.php";
	$editor = new bab_contentEditor('bab_faq');
	$description = $editor->getContent();

	$query = "
	UPDATE ".BAB_FAQCAT_TBL." 
	SET 
		category='".$babDB->db_escape_string($category)."', 
		description='".$babDB->db_escape_string($description)."', 
		lang='".$babDB->db_escape_string($lang)."' 
	WHERE 
		id = '".$babDB->db_escape_string($id)."'";
	$babDB->db_query($query);
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=admfaqs&idx=Categories");

	}

function confirmDeleteFaq($id)
	{
	include_once $GLOBALS['babInstallPath']."utilit/delincl.php";
	bab_deleteFaq($id);
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=admfaqs&idx=Categories");
	}

/* main */
if( !$babBody->isSuperAdmin && $babBody->currentDGGroup['faqs'] != 'Y')
{
	$babBody->msgerror = bab_translate("Access denied");
	return;
}

if(!isset($idx))
	{
	$idx = "Modify";
	}

if( isset($add))
	{
	if( isset($submit))
		{
		if(!updateCategory($item, $category, $lang))
			$idx = "Modify";
		}
	else if( isset($faqdel))
		$idx = "Delete";
	}

if( isset($aclfaq))
	{
	if( !isset($groups)) { $groups = array(); }
	aclUpdate($table, $item, $groups, $what);
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=admfaqs&idx=Categories");
	}

if( isset($action) && $action == "Yes")
	{
	confirmDeleteFaq($item);
	}

switch($idx)
	{

	case "Groups":
		$babBody->title = bab_translate("FAQ").": ". getFaqName($item);

		$macl = new macl("admfaq", "Modify", $item, "aclfaq");
		$macl->addtable( BAB_FAQCAT_GROUPS_TBL,bab_translate("View"));
		$macl->addtable( BAB_FAQMANAGERS_GROUPS_TBL,bab_translate("View and manage"));
		$macl->filter(0,0,1,1,1);
		$macl->babecho();

		$babBody->addItemMenu("Categories", bab_translate("Faqs"), $GLOBALS['babUrlScript']."?tg=admfaqs&idx=Categories");
		$babBody->addItemMenu("Modify", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=admfaq&idx=Modify&item=".$item);
		$babBody->addItemMenu("Groups", bab_translate("Rights"), $GLOBALS['babUrlScript']."?tg=admfaq&idx=Groups&item=".$item);
		break;

	case "Delete":
		$babBody->title = bab_translate("Delete FAQ");
		deleteCategory($item);
		$babBody->addItemMenu("Categories", bab_translate("Faqs"), $GLOBALS['babUrlScript']."?tg=admfaqs&idx=Categories");
		$babBody->addItemMenu("Modify", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=admfaq&idx=Modify&item=".$item);
		$babBody->addItemMenu("Groups", bab_translate("Rights"), $GLOBALS['babUrlScript']."?tg=admfaq&idx=Groups&item=".$item);
		$babBody->addItemMenu("Delete", bab_translate("Delete"), $GLOBALS['babUrlScript']."?tg=admfaq&idx=Delete&item=".$item);
		break;

	default:
	case "Modify":
		$babBody->title = bab_translate("Modify FAQ").": ". getFaqName($item);
		modifyCategory($item);
		$babBody->addItemMenu("Categories", bab_translate("Faqs"), $GLOBALS['babUrlScript']."?tg=admfaqs&idx=Categories");
		$babBody->addItemMenu("Modify", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=admfaq&idx=Modify&item=".$item);
		$babBody->addItemMenu("Groups", bab_translate("Rights"), $GLOBALS['babUrlScript']."?tg=admfaq&idx=Groups&item=".$item);
		break;
	}
$babBody->setCurrentItemMenu($idx);

?>
