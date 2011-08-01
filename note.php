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
require_once dirname(__FILE__).'/utilit/registerglobals.php';

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

		function temp($id)
			{
			global $babDB, $BAB_SESS_USERID;
			$this->notes = bab_translate("Content");
			$this->modify = bab_translate("Update Note");
			$res = $babDB->db_query("select * from ".BAB_NOTES_TBL." where id='".$babDB->db_escape_string($id)."' and id_user='".$babDB->db_escape_string($BAB_SESS_USERID)."'");
			$arr = $babDB->db_fetch_array($res);
			$this->note_id = bab_toHtml($arr['id']);
				
			include_once $GLOBALS['babInstallPath']."utilit/editorincl.php";
			$editor = new bab_contentEditor('bab_note');
			$editor->setContent($arr['content']);
			$editor->setFormat($arr['content_format']);
			$this->editor = $editor->getEditor();
			}
		}

	$temp = new temp($id);
	$babBody->babecho(	bab_printTemplate($temp,"notes.html", "notesmodify"));
	}

function deleteNotes($id)
	{
	global $babDB, $BAB_SESS_USERID;
	$query = "delete from ".BAB_NOTES_TBL." where id = '".$babDB->db_escape_string($id)."' and id_user='".$babDB->db_escape_string($BAB_SESS_USERID)."'";
	$babDB->db_query($query);
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=notes&idx=List");
	}

function updateNotes($id, $content)
	{
	global $babDB, $BAB_SESS_USERID;

	include_once $GLOBALS['babInstallPath']."utilit/editorincl.php";
	$editor = new bab_contentEditor('bab_note');
	$content = $editor->getContent();
	$format = $editor->getFormat();
	
	$query = "
	UPDATE ".BAB_NOTES_TBL." SET 
		content='".$babDB->db_escape_string($content)."' 
	WHERE 
		id='".$babDB->db_escape_string($id)."' 
		AND id_user='".$babDB->db_escape_string($BAB_SESS_USERID)."' 
	";
	$babDB->db_query($query);
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=notes&idx=List");
	}

/* main */
if( !bab_notesAccess() )
{
	$babBody->addError(bab_translate("Access denied"));
	return;
}

$idx = bab_rp('idx', 'Modify');
$item = bab_rp('item', 0);

list($iduser) = $babDB->db_fetch_row($babDB->db_query("select id_user from ".BAB_NOTES_TBL." where id = '".$babDB->db_escape_string($item)."'"));

if( isset($_POST['update']) && $iduser == $BAB_SESS_USERID)
	{
	updateNotes($item, bab_pp('content'));
	}

switch($idx)
	{
	case 'Delete':
		if( $iduser != $BAB_SESS_USERID )
			{
			$babBody->msgerror = bab_translate("Access denied");
			}
		else
			{
			deleteNotes($item);
			}
		break;

	default:
	case 'Modify':
		if( $iduser != $BAB_SESS_USERID )
			{
			$babBody->msgerror = bab_translate("Access denied");
			}
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