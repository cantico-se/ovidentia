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

function notesModify($id)
	{
	global $babBody;
	if( !isset($id))
		{
		$babBody->msgerror = bab_translate("ERROR: You must choose a valid notes")." !!";
		return;
		}
	class temp
		{
		var $notes;
		var $modify;

		var $db;
		var $arr = array();
		var $res;
		var $msie;

		function temp($id)
			{
			$this->notes = bab_translate("Content");
			$this->modify = bab_translate("Update Note");
			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_NOTES_TBL." where id='$id'";
			$this->res = $this->db->db_query($req);
			$this->arr = $this->db->db_fetch_array($this->res);
			$this->editor = bab_editor($this->arr['content'], 'content', 'notemod');	
			}
		}

	$temp = new temp($id);
	$babBody->babecho(	bab_printTemplate($temp,"notes.html", "notesmodify"));
	}

function deleteNotes($id)
	{
	$db = $GLOBALS['babDB'];
	$query = "delete from ".BAB_NOTES_TBL." where id = '$id'";
	$db->db_query($query);
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=notes&idx=List");
	}

function updateNotes($id, $content)
	{
	$db = $GLOBALS['babDB'];
	if( !bab_isMagicQuotesGpcOn())
		{
		$content = addslashes(bab_stripDomainName($content));
		}
	$query = "update ".BAB_NOTES_TBL." set content='$content' where id = '$id'";
	$db->db_query($query);
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=notes&idx=List");
	}

/* main */
if( !isset($idx))
	{
	$idx = "Modify";
	}

list($iduser) = $babDB->db_fetch_row($babDB->db_query("select id_user from ".BAB_NOTES_TBL." where id = '".$item."'"));

if( isset($update) && $iduser == $BAB_SESS_USERID)
	updateNotes($item, $content);

switch($idx)
	{
	case "Delete":
		if( $iduser != $BAB_SESS_USERID )
			$babBody->msgerror = bab_translate("Access denied");
		else
			deleteNotes($item);
		break;

	default:
	case "Modify":
		if( $iduser != $BAB_SESS_USERID )
			$babBody->msgerror = bab_translate("Access denied");
		else
		{
			$babBody->title = bab_translate("Modify a note");
			notesModify($item);
			$babBody->addItemMenu("List", bab_translate("List"), $GLOBALS['babUrlScript']."?tg=notes&idx=List");
			$babBody->addItemMenu("Modify", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=note&idx=Modify&item=".$item);
			$babBody->addItemMenu("Delete", bab_translate("Delete"), $GLOBALS['babUrlScript']."?tg=note&idx=Delete&item=".$item);
		}
		break;
	}

$babBody->setCurrentItemMenu($idx);

?>