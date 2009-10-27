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
/**
* @internal SEC1 NA 18/12/2006 FULL
*/
include_once 'base.php';

function notesCreate()
	{
	global $babBody;
	class temp
		{
		var $notes;
		var $create;

		function temp()
			{
			$this->create = bab_translate("Create");
			$this->notes = bab_translate("Content");
			
			include_once $GLOBALS['babInstallPath']."utilit/editorincl.php";
			$editor = new bab_contentEditor('bab_note');
			$this->editor = $editor->getEditor();
			}
		}

	$temp = new temp();
	$babBody->babecho(	bab_printTemplate($temp,"notes.html", "notescreate"));
	}

function notesList($id)
	{
	global $babBody;
	class temp
		{
		var $date;
		var $content;
		var $editurl;
		var $editname;
		var $delurl;
		var $delname;
		
		var $id;
		var $arr = array();
		var $db;
		var $count;
		var $res;
		var $reqid;

		function temp($id)
			{
			global $babDB, $BAB_SESS_USERID;
			$this->editname = bab_translate("Edit");
			$this->delname = bab_translate("Delete");
			$this->date = bab_translate("Date");
			$this->content = bab_translate("Content");
			if( $id != '' )
				{
				$reqid = " and id='".$babDB->db_escape_string($id)."' ";
				}
			else
				{
				$reqid = '';
				}

			$req = "select * from ".BAB_NOTES_TBL." where id_user='".$babDB->db_escape_string($BAB_SESS_USERID)."'".$reqid." order by date desc";
			$this->res = $babDB->db_query($req);
			$this->count = $babDB->db_num_rows($this->res);
			
			include_once $GLOBALS['babInstallPath']."utilit/editorincl.php";
			
			}

		function getnext()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->count)
				{
				$this->arr = $babDB->db_fetch_array($this->res);
				$this->editurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=note&idx=Modify&item=".$this->arr['id']);
				$this->delurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=note&idx=Delete&item=".$this->arr['id']);
				
				$editor = new bab_contentEditor('bab_note');
				$editor->setContent($this->arr['content']);
				$editor->setFormat($this->arr['content_format']);
				$this->note_content = $editor->getHtml();
				
				$this->note_date = bab_toHtml(bab_strftime(bab_mktime($this->arr['date'])));
				$i++;
				return true;
				}
			else
				return false;
			}
		}

	$temp = new temp($id);
	$babBody->babecho(	bab_printTemplate($temp, 'notes.html', 'noteslist'));
	return $temp->count;
	}


function saveNotes()
	{
	global $babDB, $BAB_SESS_USERID;
	
	
	include_once $GLOBALS['babInstallPath']."utilit/editorincl.php";
	$editor = new bab_contentEditor('bab_note');
	$content = $editor->getContent();
	$format = $editor->getFormat();
	
	bab_debug('content received from bab_contentEditor as format : '.$format);

	if( empty($content) || empty($BAB_SESS_USERID))
		{
		return false;
		}

	$query = "insert into ".BAB_NOTES_TBL." 
		(
			id_user, 
			date, 
			content,
			content_format
		) 
	VALUES 
		(
			'". $babDB->db_escape_string($BAB_SESS_USERID). "',
			now(), 
			'" . $babDB->db_escape_string($content). "',
			'" . $babDB->db_escape_string($format). "'
		)
	";
	
	$babDB->db_query($query);
	}

/* main */
if( !bab_notesAccess() )
{
	$babBody->addError(bab_translate("Access denied"));
	return;
}

$idx = bab_rp('idx', 'List');
$id = bab_rp('id', '');


if( isset($_POST['create']))
{
	saveNotes();
}

switch($idx)
	{
	case "Create":
		$babBody->title = bab_translate("Create a note");
		notesCreate();
		$babBody->addItemMenu("List", bab_translate("Notes"), $GLOBALS['babUrlScript']."?tg=notes&idx=List");
		$babBody->addItemMenu("Create", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=notes&idx=Create");
		break;

	case "View":
		$babBody->title = bab_translate("Note");
		if( notesList($id) > 0 )
			{
			$babBody->addItemMenu("List", bab_translate("Notes"), $GLOBALS['babUrlScript']."?tg=notes&idx=List");
			}
		$babBody->addItemMenu("Create", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=notes&idx=Create");
		break;

	default:
	case "List":
		$babBody->title = bab_translate("Notes list");
		if( notesList($id) > 0 )
			{
			$babBody->addItemMenu("List", bab_translate("Notes"), $GLOBALS['babUrlScript']."?tg=notes&idx=List");
			}
		$babBody->addItemMenu("Create", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=notes&idx=Create");
		break;
	}

$babBody->setCurrentItemMenu($idx);
bab_siteMap::setPosition('bab','UserNotes');
?>
