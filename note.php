<?php

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
			$req = "select * from notes where id='$id'";
			$this->res = $this->db->db_query($req);
			$this->arr = $this->db->db_fetch_array($this->res);
			if(( strtolower(bab_browserAgent()) == "msie") and (bab_browserOS() == "windows"))
				$this->msie = 1;
			else
				$this->msie = 0;	
			}
		}

	$temp = new temp($id);
	$babBody->babecho(	bab_printTemplate($temp,"notes.html", "notesmodify"));
	}

function deleteNotes($id)
	{
	$db = $GLOBALS['babDB'];
	$query = "delete from notes where id = '$id'";
	$db->db_query($query);
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=notes&idx=List");
	}

function updateNotes($id, $content)
	{
	$db = $GLOBALS['babDB'];
	$query = "update notes set content='$content' where id = '$id'";
	$db->db_query($query);
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=notes&idx=List");
	}

/* main */
if( !isset($idx))
	{
	$idx = "Modify";
	}
if( isset($update))
	updateNotes($item, $content);

switch($idx)
	{
	case "Delete":
		deleteNotes($item);
		$idx = "List";

	default:
	case "Modify":
		$babBody->title = bab_translate("Modify a note");
		notesModify($item);
		$babBody->addItemMenu("List", bab_translate("List"), $GLOBALS['babUrlScript']."?tg=notes&idx=List");
		$babBody->addItemMenu("Modify", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=note&idx=Modify&item=".$item);
		$babBody->addItemMenu("Delete", bab_translate("Delete"), $GLOBALS['babUrlScript']."?tg=note&idx=Delete&item=".$item);
		break;
	}

$babBody->setCurrentItemMenu($idx);

?>