<?php

function notesCreate()
	{
	global $body;
	class temp
		{
		var $notes;
		var $create;
		var $msie;

		function temp()
			{
			$this->create = babTranslate("Create");
			$this->notes = babTranslate("Content");
			if(( strtolower(browserAgent()) == "msie") and (browserOS() == "windows"))
				$this->msie = 1;
			else
				$this->msie = 0;	
			}
		}

	$temp = new temp();
	$body->babecho(	babPrintTemplate($temp,"notes.html", "notescreate"));
	}

function notesList()
	{
	global $body;
	class temp
		{
		var $date;
		var $content;
		var $editurl;
		var $editname;
		
		var $id;
		var $arr = array();
		var $db;
		var $count;
		var $res;

		function temp()
			{
			global $BAB_SESS_USERID;
			$this->date = babTranslate("Date");
			$this->content = babTranslate("Content");
			$this->db = new db_mysql();
			$req = "select * from notes where id_user='".$BAB_SESS_USERID."'";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$this->arr = $this->db->db_fetch_array($this->res);
				$this->editurl = $GLOBALS['babUrl']."index.php?tg=note&idx=Modify&item=".$this->arr['id'];
				$this->editname = babTranslate("Edit");
				$this->arr['content'] = babReplace($this->arr['content']);// nl2br($this->arr['content']);
				$this->arr['date'] = bab_strftime(bab_mktime($this->arr['date']));
				$i++;
				return true;
				}
			else
				return false;
			}
		}

	$temp = new temp();
	$body->babecho(	babPrintTemplate($temp, "notes.html", "noteslist"));
	return $temp->count;
	}


function saveNotes($content)
	{
	global $BAB_SESS_USERID;
	if( empty($content) || empty($BAB_SESS_USERID))
		{
		return;
		}

	$db = new db_mysql();
	$query = "insert into notes (id_user, date, content) VALUES ('". $BAB_SESS_USERID. "',now(), '" . $content. "')";
	$db->db_query($query);
	}

/* main */
if( !isset($idx))
	{
	$idx="List";
	}

if( isset($create))
	saveNotes($content);

switch($idx)
	{
	case "Create":
		$body->title = babTranslate("Create a note");
		notesCreate();
		$body->addItemMenu("List", babTranslate("List"), $GLOBALS['babUrl']."index.php?tg=notes&idx=List");
		$body->addItemMenu("Create", babTranslate("Create"), $GLOBALS['babUrl']."index.php?tg=notes&idx=Create");
		break;

	default:
	case "List":
		$body->title = babTranslate("Notes list");
		if( notesList() > 0 )
			{
			$body->addItemMenu("List", babTranslate("List"), $GLOBALS['babUrl']."index.php?tg=notes&idx=List");
			}
		$body->addItemMenu("Create", babTranslate("Create"), $GLOBALS['babUrl']."index.php?tg=notes&idx=Create");
		break;
	}

$body->setCurrentItemMenu($idx);

?>