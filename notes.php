<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
include_once "base.php";

function notesCreate()
	{
	global $babBody;
	class temp
		{
		var $notes;
		var $create;
		var $msie;

		function temp()
			{
			$this->create = bab_translate("Create");
			$this->notes = bab_translate("Content");
			if(( strtolower(bab_browserAgent()) == "msie") and (bab_browserOS() == "windows"))
				$this->msie = 1;
			else
				$this->msie = 0;	
			}
		}

	$temp = new temp();
	$babBody->babecho(	bab_printTemplate($temp,"notes.html", "notescreate"));
	}

function notesList()
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

		function temp()
			{
			global $BAB_SESS_USERID;
			$this->editname = bab_translate("Edit");
			$this->delname = bab_translate("Delete");
			$this->date = bab_translate("Date");
			$this->content = bab_translate("Content");
			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_NOTES_TBL." where id_user='".$BAB_SESS_USERID."' order by date desc";
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
				$this->arr['content'] = bab_replace($this->arr['content']);// nl2br($this->arr['content']);
				$this->arr['date'] = bab_strftime(bab_mktime($this->arr['date']));
				$i++;
				return true;
				}
			else
				return false;
			}
		}

	$temp = new temp();
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

	default:
	case "List":
		$babBody->title = bab_translate("Notes list");
		if( notesList() > 0 )
			{
			$babBody->addItemMenu("List", bab_translate("Notes"), $GLOBALS['babUrlScript']."?tg=notes&idx=List");
			}
		$babBody->addItemMenu("Create", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=notes&idx=Create");
		break;
	}

$babBody->setCurrentItemMenu($idx);

?>