<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
include $babInstallPath."utilit/forumincl.php";

function listThreads($forum, $active, $pos)
	{
	global $babBody;

	class temp
		{
		var $thread;
		var $starter;
		var $replies;
		var $repliesname;
		var $views;
		var $lastpost;
		var $lastpostdate;
		var $replies;
		var $subjecturl;
		var $subjectname;

		var $arrthread = array();
		var $arrpost = array();
		var $db;
		var $count;
		var $res;
		var $forum;
		var $status;
		var $topurl;
		var $bottomurl;
		var $nexturl;
		var $prevurl;
		var $topname;
		var $bottomname;
		var $nextname;
		var $prevname;
		var $moderator;
		var $disabled;

		function temp($forum, $active, $pos)
			{
			global $babMaxRows;
			$this->topurl = "";
			$this->bottomurl = "";
			$this->nexturl = "";
			$this->prevurl = "";
			$this->topname = "";
			$this->bottomname = "";
			$this->nextname = "";
			$this->prevname = "";
			$this->thread = bab_translate("Thread");
			$this->starter = bab_translate("Starter");
			$this->repliesname = bab_translate("Replies");
			$this->views = bab_translate("Views");
			$this->lastpost = bab_translate("Last Post");
			$this->moderator = bab_isUserForumModerator($forum, $GLOBALS['BAB_SESS_USERID']);

			$this->db = $GLOBALS['babDB'];
			$req = "select count(*) as total from threads where forum='$forum' and active='$active'";
			$this->res = $this->db->db_query($req);
			$row = $this->db->db_fetch_array($this->res);
			$total = $row["total"];
			if( $active == "Y")
				$idx = "List";
			else
				$idx = "ListC";

			if( $total > $babMaxRows)
				{
				if( $pos > 0)
					{
					$this->topurl = $GLOBALS['babUrlScript']."?tg=threads&idx=".$idx."&forum=".$forum."&pos=0";
					$this->topname = "&lt;&lt;";
					}

				$next = $pos - $babMaxRows;
				if( $next >= 0)
					{
					$this->prevurl = $GLOBALS['babUrlScript']."?tg=threads&idx=".$idx."&forum=".$forum."&pos=".$next;
					$this->prevname = "&lt;";
					}

				$next = $pos + $babMaxRows;
				if( $next < $total)
					{
					$this->nexturl = $GLOBALS['babUrlScript']."?tg=threads&idx=".$idx."&forum=".$forum."&pos=".$next;
					$this->nextname = "&gt;";
					if( $next + $babMaxRows < $total)
						{
						$bottom = $total - $babMaxRows;
						}
					else
						$bottom = $next;
					$this->bottomurl = $GLOBALS['babUrlScript']."?tg=threads&idx=".$idx."&forum=".$forum."&pos=".$bottom;
					$this->bottomname = "&gt;&gt;";
					}
				}

			$req = "select * from threads where forum='$forum' and active='$active' order by date desc";
			if( $total > $babMaxRows)
				{
				$req .= " limit ".$pos.",".$babMaxRows;
				}

			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			$this->forum = $forum;
			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$this->arrthread = $this->db->db_fetch_array($this->res);
				$req = "select * from posts where id_thread='".$this->arrthread['id']."' and id='".$this->arrthread['post']."'";
				$res = $this->db->db_query($req);
				if( $res && $this->db->db_num_rows($res) > 0)
					{
					$this->arrpost = $this->db->db_fetch_array($res);
					$this->subjecturl = $GLOBALS['babUrlScript']."?tg=posts&idx=List&forum=".$this->forum."&thread=".$this->arrthread['id']."&views=1";
					$this->subjectname = $this->arrpost['subject'];
					$req = "select * from posts where id_thread='".$this->arrthread['id']."' and id='".$this->arrthread['lastpost']."'";
					$res = $this->db->db_query($req);
					$arr = $this->db->db_fetch_array($res);
				
					$tmp = explode(" ", $arr['date']);
					$arr0 = explode("-", $tmp[0]);
					$arr1 = explode(":", $tmp[1]);
					$this->lastpostdate = $arr0[2]."/".$arr0[1]."/".$arr0[0]." ".$arr1[0].":".$arr1[1];
					//$this->lastpostdate = bab_strftime(bab_mktime($arr['date']));

					$req = "select count(*) as total from posts where id_thread='".$this->arrthread['id']."' and confirmed='Y'";
					$res = $this->db->db_query($req);
					$row = $this->db->db_fetch_array($res);
					$this->replies = $row["total"];
					if( $this->replies == 0 && $this->moderator == false )
						$this->disabled = 1;
					else
						$this->disabled = 0;
					}
				$req = "select count(*) as total from posts where id_thread='".$this->arrthread['id']."' and confirmed='N'";
				$res = $this->db->db_query($req);
				$ar = $this->db->db_fetch_array($res);
				if( $this->arrthread['active'] != "N" && $ar['total'] > 0)
					$this->status = "*";
				else
					$this->status = "";

				$i++;
				return true;
				}
			else
				return false;
			}
		}
	
	$temp = new temp($forum, $active, $pos);
	$babBody->babecho(	bab_printTemplate($temp,"threads.html", "threadlist"));
	return $temp->count;
	}

function newThread($forum)
	{
	global $babBody;
	
	class temp
		{
		var $subject;
		var $name;
		var $message;
		var $add;
		var $forum;
		var $username;
		var $anonyme;
		var $notifyme;
		var $msie;

		function temp($forum)
			{
			global $BAB_SESS_USER;
			$this->subject = bab_translate("Subject");
			$this->name = bab_translate("Your Name");
			$this->notifyme = bab_translate("Notify me whenever someone replies ( only valid for registered users )");
			$this->message = bab_translate("Message");
			$this->add = bab_translate("New thread");
			$this->forum = $forum;
			if( empty($BAB_SESS_USER))
				$this->anonyme = 1;
			else
				{
				$this->anonyme = 0;
				$this->username = $BAB_SESS_USER;
			if(( strtolower(bab_browserAgent()) == "msie") and (bab_browserOS() == "windows"))
				$this->msie = 1;
			else
				$this->msie = 0;	
				}
			}
		}

	$temp = new temp($forum);
	$babBody->babecho(	bab_printTemplate($temp,"threads.html", "threadcreate"));
	}

function saveThread($forum, $name, $subject, $message, $notifyme)
	{
	global $BAB_SESS_USER, $BAB_SESS_USERID, $babBody;

	if( empty($message))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a content for your message")." !";
		return;
		}

	if( empty($subject))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a subject for your message")." !";
		return;
		}

	if( empty($BAB_SESS_USER))
		{
		if( empty($name))
			{
			$name = bab_translate("Anonymous");
			}
		$idstarter = 0;
		}
	else
		{
		$name = $BAB_SESS_USER;
		$idstarter = $BAB_SESS_USERID;
		}

	if( $notifyme == "Y")
		$notifyme = "Y";
	else
		$notifyme = "N";

	$db = $GLOBALS['babDB'];
	$req = "insert into threads (forum, date, notify, starter) values ";
	$req .= "('" .$forum. "', now(), '" . $notifyme. "', '". $idstarter. "')";
	$res = $db->db_query($req);
	$idthread = $db->db_insert_id();

	if( bab_isForumModerated($forum))
		$confirmed = "N";
	else
		$confirmed = "Y";

	if(!get_cfg_var("magic_quotes_gpc"))
		{
		$subject = addslashes($subject);
		$message = addslashes($message);
		$name = addslashes($name);
		}
	$req = "insert into posts (id_thread, date, subject, message, author, confirmed) values ";
	$req .= "('" .$idthread. "', now(), '" . $subject. "', '" . $message. "', '". $name. "', '". $confirmed. "')";
	$res = $db->db_query($req);
	$idpost = $db->db_insert_id();
	
	$req = "update threads set lastpost='$idpost', post='$idpost' where id = '$idthread'";
	$res = $db->db_query($req);
	}

function getClosedThreads($forum)
	{
	$db = $GLOBALS['babDB'];
	$req = "select count(*) as total from threads where forum='$forum' and active='N'";
	$res = $db->db_query($req);
	$arr = $db->db_fetch_array($res);
	return $arr['total'];
	}

/* main */
if(!isset($idx))
	{
	$idx = "List";
	}

if( !isset($pos))
	$pos = 0;

if( isset($add) && $add == "addthread")
	{
	saveThread($forum, $name, $subject, $message, $notifyme);
	}

switch($idx)
	{
	case "newthread":
		if( bab_isAccessValid("forumspost_groups", $forum))
			{
			$babBody->title = bab_getForumName($forum);
			newThread($forum);
			$babBody->addItemMenu("List", bab_translate("List"), $GLOBALS['babUrlScript']."?tg=threads&idx=List&forum=".$forum);
			$babBody->addItemMenu("newthread", bab_translate("New thread"), $GLOBALS['babUrlScript']."?tg=threads&idx=newthread&forum=".$forum);

			}		
		break;

	case "ListC":
		$babBody->title = bab_getForumName($forum);
		if( bab_isAccessValid("forumsview_groups", $forum))
			{
			$babBody->addItemMenu("List", bab_translate("List"), $GLOBALS['babUrlScript']."?tg=threads&idx=List&forum=".$forum);
			$count = listThreads($forum, "N", $pos);
			if( $count > 0)
				$babBody->addItemMenu("ListC", bab_translate("Closed"), $GLOBALS['babUrlScript']."?tg=threads&idx=ListC&forum=".$forum);

			if( bab_isAccessValid("forumspost_groups", $forum))
				{
				$babBody->addItemMenu("newthread", bab_translate("New thread"), $GLOBALS['babUrlScript']."?tg=threads&idx=newthread&forum=".$forum);
				}
			}
		break;

	default:
	case "List":
		$babBody->title = bab_getForumName($forum);
		if( bab_isAccessValid("forumsview_groups", $forum))
			{
			$count = listThreads($forum, "Y", $pos);
			//if( $count > 0)
				$babBody->addItemMenu("List", bab_translate("List"), $GLOBALS['babUrlScript']."?tg=threads&idx=List&forum=".$forum);
			if( getClosedThreads($forum) > 0)
				$babBody->addItemMenu("ListC", bab_translate("Closed"), $GLOBALS['babUrlScript']."?tg=threads&idx=ListC&forum=".$forum);

			if( bab_isAccessValid("forumspost_groups", $forum))
				{
				$babBody->addItemMenu("newthread", bab_translate("New thread"), $GLOBALS['babUrlScript']."?tg=threads&idx=newthread&forum=".$forum);
				}
			}
		break;
	}
$babBody->setCurrentItemMenu($idx);

?>