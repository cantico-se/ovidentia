<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
include_once "base.php";
include $babInstallPath."utilit/forumincl.php";
include $babInstallPath."utilit/mailincl.php";

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
		var $subjecturlflat;
		var $altnoflattxt;
		var $altflattxt;
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

		var $openthreadsinfo;
		var $waitthreadsinfo;
		var $closedthreadsinfo;

		function temp($forum, $active, $pos)
			{
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
			$this->openthreadsinfo = bab_translate("Opened threads");
			$this->waitthreadsinfo = bab_translate("Waiting posts");
			$this->closedthreadsinfo = bab_translate("Closed threads");
			$this->altnoflattxt = bab_translate("View thread as hierarchical list");
			$this->altflattxt = bab_translate("View thread as flat list");

			$this->moderator = bab_isUserForumModerator($forum, $GLOBALS['BAB_SESS_USERID']);

			$this->db = $GLOBALS['babDB'];
			$row = $this->db->db_fetch_array($this->db->db_query("select display from ".BAB_FORUMS_TBL." where id='".$forum."'"));
			$maxrows = $row['display'];

			$req = "select count(*) as total from ".BAB_THREADS_TBL." where forum='$forum' and active='$active'";
			$this->res = $this->db->db_query($req);
			$row = $this->db->db_fetch_array($this->res);
			$total = $row["total"];
			if( $active == "Y")
				$idx = "List";
			else
				$idx = "ListC";

			if( $total > $maxrows)
				{
				if( $pos > 0)
					{
					$this->topurl = $GLOBALS['babUrlScript']."?tg=threads&idx=".$idx."&forum=".$forum."&pos=0";
					$this->topname = "&lt;&lt;";
					}

				$next = $pos - $maxrows;
				if( $next >= 0)
					{
					$this->prevurl = $GLOBALS['babUrlScript']."?tg=threads&idx=".$idx."&forum=".$forum."&pos=".$next;
					$this->prevname = "&lt;";
					}

				$next = $pos + $maxrows;
				if( $next < $total)
					{
					$this->nexturl = $GLOBALS['babUrlScript']."?tg=threads&idx=".$idx."&forum=".$forum."&pos=".$next;
					$this->nextname = "&gt;";
					if( $next + $maxrows < $total)
						{
						$bottom = $total - $maxrows;
						}
					else
						$bottom = $next;
					$this->bottomurl = $GLOBALS['babUrlScript']."?tg=threads&idx=".$idx."&forum=".$forum."&pos=".$bottom;
					$this->bottomname = "&gt;&gt;";
					}
				}

			$req = "select * from ".BAB_THREADS_TBL." where forum='$forum' and active='$active' order by date desc";
			if( $total > $maxrows)
				{
				$req .= " limit ".$pos.",".$maxrows;
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
				$req = "select * from ".BAB_POSTS_TBL." where id_thread='".$this->arrthread['id']."' and id='".$this->arrthread['post']."'";
				$res = $this->db->db_query($req);
				if( $res && $this->db->db_num_rows($res) > 0)
					{
					$this->arrpost = $this->db->db_fetch_array($res);
					$this->subjecturl = $GLOBALS['babUrlScript']."?tg=posts&idx=List&forum=".$this->forum."&thread=".$this->arrthread['id']."&views=1";
					$this->subjectname = $this->arrpost['subject'];
					$this->subjecturlflat  = $this->subjecturl."&flat=1";
					$res = $this->db->db_query("select email from ".BAB_USERS_TBL." where id='".bab_getUserId( $this->arrpost['author'])."'");
					if( $res && $this->db->db_num_rows($res) > 0)
						{
						$r = $this->db->db_fetch_array($res);

						$this->replymail = $r['email']."?subject=";
						if( substr($this->arrpost['subject'], 0, 3) != "RE:")
							$this->replymail .= "RE: ";
						$this->replymail .= $this->arrpost['subject'];
						}
					else
						$this->replymail = 0;

					$req = "select count(*) as total from ".BAB_POSTS_TBL." where id_thread='".$this->arrthread['id']."' and confirmed='Y'";
					$res = $this->db->db_query($req);
					$row = $this->db->db_fetch_array($res);
					$this->replies = $row["total"] > 0 ? ($row["total"] -1): 0;
					if( $row["total"] == 0 && $this->moderator == false )
						$this->disabled = 1;
					else
						$this->disabled = 0;

					$res = $this->db->db_query("select date from ".BAB_POSTS_TBL." where id='".$this->arrthread['lastpost']."'");
					$ar = $this->db->db_fetch_array($res);
					$tmp = explode(" ", $ar['date']);
					$arr0 = explode("-", $tmp[0]);
					$arr1 = explode(":", $tmp[1]);
					$this->lastpostdate = $arr0[2]."/".$arr0[1]."/".$arr0[0]." ".$arr1[0].":".$arr1[1];
					}
				$req = "select count(*) as total from ".BAB_POSTS_TBL." where id_thread='".$this->arrthread['id']."' and confirmed='N'";
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
		var $noteforum;

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
			if( bab_isForumModerated($forum))
				$this->noteforum = bab_translate("Note: Posts are moderate and consequently your post will not be visible immediately");
			else
				$this->noteforum = "";
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
	$req = "insert into ".BAB_THREADS_TBL." (forum, date, notify, starter) values ";
	$req .= "('" .$forum. "', now(), '" . $notifyme. "', '". $idstarter. "')";
	$res = $db->db_query($req);
	$idthread = $db->db_insert_id();

	$arr = $db->db_fetch_array($db->db_query("select * from ".BAB_FORUMS_TBL." where id='".$forum."'"));

	if( $arr['moderation'] == "Y" )
		$confirmed = "N";
	else
		$confirmed = "Y";

	$req = "insert into ".BAB_POSTS_TBL." (id_thread, date, subject, message, author, confirmed) values ";
	$req .= "('" .$idthread. "', now(), '";
	if( !bab_isMagicQuotesGpcOn())
		$req .= addslashes(bab_stripDomainName($subject)). "', '" . addslashes(bab_stripDomainName($message)). "', '". addslashes($name);
	else
		$req .= bab_stripDomainName($subject). "', '" . bab_stripDomainName($message). "', '". $name;

	$req .= "', '". $confirmed. "')";
	$res = $db->db_query($req);
	$idpost = $db->db_insert_id();
	
	$req = "update ".BAB_THREADS_TBL." set lastpost='$idpost', post='$idpost' where id = '$idthread'";
	$res = $db->db_query($req);

	if( $arr['notification'] == "Y" && ($email = bab_getUserEmail($arr['moderator'])) != "")
	    notifyModerator(stripslashes($subject), stripslashes($email), stripslashes($name), $arr['name']);
	}

function getClosedThreads($forum)
	{
	$db = $GLOBALS['babDB'];
	$req = "select count(*) as total from ".BAB_THREADS_TBL." where forum='$forum' and active='N'";
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

$babLevelTwo = bab_getForumName($forum);

switch($idx)
	{
	case "newthread":
		if( bab_isAccessValid(BAB_FORUMSPOST_GROUPS_TBL, $forum))
			{
			$babBody->title = bab_getForumName($forum);
			newThread($forum);
			$babBody->addItemMenu("List", bab_translate("Threads"), $GLOBALS['babUrlScript']."?tg=threads&idx=List&forum=".$forum);
			$babBody->addItemMenu("newthread", bab_translate("New thread"), $GLOBALS['babUrlScript']."?tg=threads&idx=newthread&forum=".$forum);

			}		
		break;

	case "ListC":
		$babBody->title = bab_getForumName($forum);
		if( bab_isAccessValid(BAB_FORUMSVIEW_GROUPS_TBL, $forum))
			{
			$babBody->addItemMenu("List", bab_translate("Threads"), $GLOBALS['babUrlScript']."?tg=threads&idx=List&forum=".$forum);
			$count = listThreads($forum, "N", $pos);
			if( $count > 0)
				$babBody->addItemMenu("ListC", bab_translate("Closed"), $GLOBALS['babUrlScript']."?tg=threads&idx=ListC&forum=".$forum);

			if( bab_isAccessValid(BAB_FORUMSPOST_GROUPS_TBL, $forum))
				{
				$babBody->addItemMenu("newthread", bab_translate("New thread"), $GLOBALS['babUrlScript']."?tg=threads&idx=newthread&forum=".$forum);
				}
			}
		break;

	default:
	case "List":
		$babBody->title = bab_getForumName($forum);
		if( bab_isAccessValid(BAB_FORUMSVIEW_GROUPS_TBL, $forum))
			{
			$count = listThreads($forum, "Y", $pos);
			//if( $count > 0)
				$babBody->addItemMenu("List", bab_translate("Threads"), $GLOBALS['babUrlScript']."?tg=threads&idx=List&forum=".$forum);
			if( getClosedThreads($forum) > 0)
				$babBody->addItemMenu("ListC", bab_translate("Closed"), $GLOBALS['babUrlScript']."?tg=threads&idx=ListC&forum=".$forum);

			if( bab_isAccessValid(BAB_FORUMSPOST_GROUPS_TBL, $forum))
				{
				$babBody->addItemMenu("newthread", bab_translate("New thread"), $GLOBALS['babUrlScript']."?tg=threads&idx=newthread&forum=".$forum);
				}
			}
		break;
	}
$babBody->setCurrentItemMenu($idx);

?>
