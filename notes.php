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
			$this->editor = bab_editor('', 'content', 'notcreate');
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
			global $BAB_SESS_USERID;
			$this->editname = bab_translate("Edit");
			$this->delname = bab_translate("Delete");
			$this->date = bab_translate("Date");
			$this->content = bab_translate("Content");
			$this->db = $GLOBALS['babDB'];
			if( $id != '' )
				$reqid = " and id='".$id."' ";
			else
				$reqid = '';

			$req = "select * from ".BAB_NOTES_TBL." where id_user='".$BAB_SESS_USERID."'".$reqid." order by date desc";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$this->arr = $this->db->db_fetch_array($this->res);
				$this->editurl = $GLOBALS['babUrlScript']."?tg=note&idx=Modify&item=".$this->arr['id'];
				$this->delurl = $GLOBALS['babUrlScript']."?tg=note&idx=Delete&item=".$this->arr['id'];
				$this->arr['content'] = bab_replace($this->arr['content']);
				$this->arr['date'] = bab_strftime(bab_mktime($this->arr['date']));
				$i++;
				return true;
				}
			else
				return false;
			}
		}

	$temp = new temp($id);
	$babBody->babecho(	bab_printTemplate($temp, "notes.html", "noteslist"));
	return $temp->count;
	}


function saveNotes($content)
	{
	global $BAB_SESS_USERID;
	if( empty($content) || empty($BAB_SESS_USERID))
		{
		return;
		}
	if( !bab_isMagicQuotesGpcOn())
		{
		$content = addslashes(bab_stripDomainName($content));
		}

	$db = $GLOBALS['babDB'];
	$query = "insert into ".BAB_NOTES_TBL." (id_user, date, content) VALUES ('". $BAB_SESS_USERID. "',now(), '" . $content. "')";
	$db->db_query($query);
	}

/* main */
if( !isset($idx))
	{
	$idx="List";
	}
if( !isset($id))
	{
	$id='';
	}

if( isset($create))
	saveNotes($content);

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

?>