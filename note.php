<?php

function notesModify($id)
	{
	global $body;
	if( !isset($id))
		{
		$body->msgerror = babTranslate("ERROR: You must choose a valid notes")." !!";
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
			$this->notes = babTranslate("Content");
			$this->modify = babTranslate("Update Note");
			$this->db = new db_mysql();
			$req = "select * from notes where id='$id'";
			$this->res = $this->db->db_query($req);
			$this->arr = $this->db->db_fetch_array($this->res);
			if( strtolower(browserAgent()) == "msie")
				$this->msie = 1;
			else
				$this->msie = 0;	
			}
		}

	$temp = new temp($id);
	$body->babecho(	babPrintTemplate($temp,"notes.html", "notesmodify"));
	}

function deleteNotes($id)
	{
	$db = new db_mysql();
	$query = "delete from notes where id = '$id'";
	$db->db_query($query);
	Header("Location: index.php?tg=notes&idx=List");
	}

function updateNotes($id, $content)
	{
	$db = new db_mysql();
	$query = "update notes set content='$content' where id = '$id'";
	$db->db_query($query);
	Header("Location: index.php?tg=notes&idx=List");
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
		$body->title = babTranslate("Modify a note");
		notesModify($item);
		$body->addItemMenu("List", babTranslate("List"), $GLOBALS['babUrl']."index.php?tg=notes&idx=List");
		$body->addItemMenu("Modify", babTranslate("Modify"), $GLOBALS['babUrl']."index.php?tg=note&idx=Modify&item=".$item);
		$body->addItemMenu("Delete", babTranslate("Delete"), $GLOBALS['babUrl']."index.php?tg=note&idx=Delete&item=".$item);
		break;
	}

$body->setCurrentItemMenu($idx);

?>