<?php
include $babInstallPath."utilit/forumincl.php";

function listPosts($forum, $thread, $pos, $what)
	{
	global $body;

	class temp
		{
	
		var $arr = array();
		var $db;
		var $count;
		var $res;
		var $forum;
		var $thread;
		var $what;
		var $topurl;
		var $bottomurl;
		var $nexturl;
		var $prevurl;
		var $topname;
		var $bottomname;
		var $nextname;
		var $prevname;
		var $more;
		var $moreurl;
		var $morename;
		var $confirmurl;
		var $confirmname;

		function temp($forum, $thread, $pos, $what)
			{
			global $newpost, $views;

			$this->topurl = "";
			$this->bottomurl = "";
			$this->nexturl = "";
			$this->prevurl = "";
			$this->topname = "";
			$this->bottomname = "";
			$this->nextname = "";
			$this->prevname = "";
			$this->more = "";
			$this->db = new db_mysql();
			if( $what == "Y" && $views == "1")
				{
				//update views
				$req = "select * from threads where id='$thread'";
				$res = $this->db->db_query($req);
				$row = $this->db->db_fetch_array($res);
				$views = $row["views"];
				$views += 1;
				$req = "update threads set views='$views' where id='$thread'";
				$res = $this->db->db_query($req);
				}

			$req = "select count(*) as total from posts where id_thread='$thread' and confirmed='$what'";
			$this->res = $this->db->db_query($req);
			$row = $this->db->db_fetch_array($this->res);
			$total = $row["total"];

			$req = "select * from forums where id='$forum'";
			$this->res = $this->db->db_query($req);
			$row = $this->db->db_fetch_array($this->res);
			$maxRows = $row[display];

			$req = "select count(*) as total from posts where id_thread='$thread' and confirmed='N'";
			$this->res = $this->db->db_query($req);
			$row = $this->db->db_fetch_array($this->res);
			$newpost = $row["total"];

			if( $total > $maxRows)
				{
				if( $pos > 0)
					{
					$this->topurl = $GLOBALS[babUrl]."index.php?tg=posts&idx=List&forum=".$forum."&thread=".$thread."&pos=0";
					$this->topname = "&lt;&lt;";
					}

				$next = $pos - $maxRows;
				if( $next >= 0)
					{
					$this->prevurl = $GLOBALS[babUrl]."index.php?tg=posts&idx=List&forum=".$forum."&thread=".$thread."&pos=".$next;
					$this->prevname = "&lt;";
					}

				$next = $pos + $maxRows;
				if( $next < $total)
					{
					$this->nexturl = $GLOBALS[babUrl]."index.php?tg=posts&idx=List&forum=".$forum."&thread=".$thread."&pos=".$next;
					$this->nextname = "&gt;";
					if( $next + $maxRows < $total)
						{
						$bottom = $total - $maxRows;
						}
					else
						$bottom = $next;
					$this->bottomurl = $GLOBALS[babUrl]."index.php?tg=posts&idx=List&forum=".$forum."&thread=".$thread."&pos=".$bottom;
					$this->bottomname = "&gt;&gt;";
					}
				}

			$req = "select * from posts where id_thread='$thread' and confirmed='$what' order by date asc";
			if( $total > $maxRows)
				{
				$req .= " limit ".$pos.",".$maxRows;
				}
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			$this->forum = $forum;
			$this->thread = $thread;
			$this->what = $what;
			}

		function getnext()
			{
			global $newpost;
			static $i = 0;
			if( $i < $this->count)
				{
				$this->arr = $this->db->db_fetch_array($this->res);
				$this->date = bab_strftime(bab_mktime($this->arr[date]));
				$dateupdate = bab_mktime($this->arr[dateupdate]);
				$this->confirmurl = "";
				$this->confirmname = "";
				$this->moreurl = "";
				$this->morename = "";
				if(  $this->what == "Y" && $dateupdate > 0)
					{
					$req = "select * from forums where id='".$this->forum."'";
					$res = $this->db->db_query($req);
					$arr = $this->db->db_fetch_array($res);

					$req = "select * from users where id='".$arr[moderator]."'";
					$res = $this->db->db_query($req);
					$arr = $this->db->db_fetch_array($res);
					$this->more = babTranslate("Modified by")." ".$arr[fullname]." ".babTranslate("on")." ".bab_strftime($dateupdate);
					$this->confirmurl = "";
					$this->confirmname = "";
					$this->moreurl = "";
					$this->morename = "";
					}
				else if( $this->what == "N" )
					{
					$this->confirmurl = $GLOBALS[babUrl]."index.php?tg=posts&idx=Confirm&forum=".$this->forum."&thread=".$this->thread."&post=".$this->arr[id]."&newpost=".$newpost;
					$this->confirmname = babTranslate("Confirm");
					$this->moreurl = $GLOBALS[babUrl]."index.php?tg=posts&idx=Modify&forum=".$this->forum."&thread=".$this->thread."&post=".$this->arr[id]."&newpost=".$newpost;
					$this->morename = babTranslate("Edit");
					}
				$i++;
				return true;
				}
			else
				return false;
			}
		}
	
	$temp = new temp($forum, $thread, $pos, $what);
	$body->babecho(	babPrintTemplate($temp,"posts.html", "postslist"));
	return $temp->count;
	}


function newReply($forum, $thread)
	{
	global $body;
	
	class temp
		{
		var $subject;
		var $name;
		var $message;
		var $add;
		var $forum;
		var $thread;
		var $username;
		var $anonyme;
		var $notifyme;

		function temp($forum, $thread)
			{
			global $BAB_SESS_USER;
			$this->subject = babTranslate("Subject");
			$this->name = babTranslate("Name");
			$this->message = babTranslate("Message");
			$this->add = babTranslate("New reply");
			$this->forum = $forum;
			$this->thread = $thread;
			if( empty($BAB_SESS_USER))
				$this->anonyme = 1;
			else
				{
				$this->anonyme = 0;
				$this->username = $BAB_SESS_USER;
				}
			}
		}

	$temp = new temp($forum, $thread);
	$body->babecho(	babPrintTemplate($temp,"posts.html", "postcreate"));
	}

function editPost($forum, $thread, $post, $newpost)
	{
	global $body;
	
	class temp
		{
		var $subject;
		var $name;
		var $message;
		var $update;
		var $forum;
		var $thread;
		var $post;
		var $newpost;
		var $arr = array();

		function temp($forum, $thread, $post, $newpost)
			{
			global $BAB_SESS_USER;
			$this->subject = babTranslate("Subject");
			$this->name = babTranslate("Name");
			$this->message = babTranslate("Message");
			$this->update = babTranslate("Update reply");
			$this->forum = $forum;
			$this->thread = $thread;
			$this->newpost = $newpost;
			$this->post = $post;
			$db = new db_mysql();
			$req = "select * from posts where id='$post'";
			$res = $db->db_query($req);
			$this->arr = $db->db_fetch_array($res);
			}
		}

	$temp = new temp($forum, $thread, $post, $newpost);
	$body->babecho(	babPrintTemplate($temp,"posts.html", "postedit"));
	}

function deleteThread($forum, $thread, $newpost)
	{
	global $body;

	class temp
		{
		var $warning;
		var $message;
		var $title;
		var $urlyes;
		var $urlno;
		var $yes;
		var $no;
		var $topics;
		var $article;

		function temp($forum, $thread, $newpost)
			{
			$this->message = babTranslate("Are you sure you want to delete this thread");
			$this->title = getThreadTitle($thread);
			$this->warning = babTranslate("WARNING: This operation will delete the thread and all references"). "!";
			$this->urlyes = $GLOBALS[babUrl]."index.php?tg=posts&idx=DeleteT&forum=".$forum."&thread=".$thread."&action=Yes";
			$this->yes = babTranslate("Yes");
			$this->urlno = $GLOBALS[babUrl]."index.php?tg=posts&idx=List&forum=".$forum."&thread=".$thread."&newpost=".$newpost;
			$this->no = babTranslate("No");
			}
		}

	$temp = new temp($forum, $thread, $newpost);
	$body->babecho(	babPrintTemplate($temp,"warning.html", "warningyesno"));
	}



function saveReply($forum, $thread, $name, $subject, $message)
	{
	global $BAB_SESS_USER, $BAB_SESS_USERID, $body;

	if( empty($message))
		{
		$body->msgerror = babTranslate("ERROR: You must provide a content for your message")." !";
		return;
		}

	if( empty($BAB_SESS_USER))
		{
		if( empty($name))
			{
			$body->msgerror = babTranslate("ERROR: You must provide a name")." !";
			return;
			}
		}
	else
		{
		$name = $BAB_SESS_USER;
		}

	$db = new db_mysql();

	if( isForumModerated($forum))
		$confirmed = "N";
	else
		$confirmed = "Y";

	$req = "insert into posts (id_thread, date, subject, message, author, confirmed) values ";
	$req .= "('" .$thread. "', now(), '" . $subject. "', '" . $message. "', '". $name. "', '". $confirmed. "')";
	$res = $db->db_query($req);
	$idpost = $db->db_insert_id();
	
	$req = "update threads set lastpost='$idpost' where id='$thread'";
	$res = $db->db_query($req);

	$req = "select * from threads where id='$thread'";
	$res = $db->db_query($req);
	$arr = $db->db_fetch_array($res);
	if( $arr[notify] == "Y" && $arr[starter] != 0)
		{
		$req = "select * from users where id='".$arr[starter]."'";
		$res = $db->db_query($req);
		$arr = $db->db_fetch_array($res);
		$msg = babTranslate("A new post has been registered on thread").": \n  ". getThreadTitle($thread);
		mail ($arr[email],'New Post',$msg,"From: ".$babAdminEmail);
		}
	}

function confirm($forum, $thread, $post)
	{
	$db = new db_mysql();
	$req = "update threads set lastpost='$post' where id='$thread'";
	$res = $db->db_query($req);

	$req = "update posts set confirmed='Y' where id='$post'";
	$res = $db->db_query($req);
	}

function updateReply($forum, $thread, $subject, $message, $post)
	{
	global $body;

	if( empty($message))
		{
		$body->msgerror = babTranslate("ERROR: You must provide a content for your message")." !";
		return;
		}

	$db = new db_mysql();

	$req = "update posts set message='$message', subject='$subject', dateupdate=now() where id='$post'";
	$res = $db->db_query($req);

	}

function closeThread($thread)
	{
	$db = new db_mysql();
	$req = "update threads set active='N' where id='$thread'";
	$res = $db->db_query($req);
	}

function openThread($thread)
	{
	$db = new db_mysql();
	$req = "update threads set active='Y' where id='$thread'";
	$res = $db->db_query($req);
	}


function deletePost($post)
	{
	global $newpost;
	$db = new db_mysql();
	$req = "delete from posts where id = '$post'";
	$res = $db->db_query($req);
	if( $newpost > 0)
		$newpost -= 1;
	}

function confirmDeleteThread($forum, $thread)
	{
	// delete posts owned by this thread
	$db = new db_mysql();
	$req = "delete from posts where id_thread = '$thread'";
	$res = $db->db_query($req);

	// delete thread
	$req = "delete from threads where id = '$thread'";
	$res = $db->db_query($req);
	Header("Location: index.php?tg=threads&forum=".$forum);
	}

/* main */
if(!isset($idx))
	{
	$idx = "List";
	}
if( !isset($pos))
	$pos = 0;

if( !isset($newpost))
	$newpost = 0;

if( isset($add) && $add == "addreply")
	{
	saveReply($forum, $thread, $name, $subject, $message);
	}

if( isset($update) && $update == "updatereply")
	{
	updateReply($forum, $thread, $subject, $message, $post);
	}

if( $idx == "Close" && isUserModerator($forum, $BAB_SESS_USERID))
	{
	closeThread($thread);
	$idx = "List";
	}

if( $idx == "Open" && isUserModerator($forum, $BAB_SESS_USERID))
	{
	openThread($thread);
	$idx = "List";
	}

if( $idx == "DeleteP" && isUserModerator($forum, $BAB_SESS_USERID))
	{
	deletePost($post);
	if( $newpost > 0)
		$idx = "Waiting";
	else
		$idx = "List";
	}

if( isset($action) && $action == "Yes")
	{
	confirmDeleteThread($forum, $thread);
	}

switch($idx)
	{
	case "reply":
		if( isAccessValid("forumsreply_groups", $forum))
			{
			$body->title = getForumName($forum);
			newReply($forum, $thread);
			$body->addItemMenu("List", babTranslate("List"), $GLOBALS[babUrl]."index.php?tg=posts&idx=List&forum=".$forum."&thread=".$thread."&pos=".$pos);
			$open = isThreadOpen($thread);
			if( isAccessValid("forumsreply_groups", $forum) && $open)
				{
				$body->addItemMenu("reply", babTranslate("Reply"), $GLOBALS[babUrl]."index.php?tg=posts&idx=reply&forum=".$forum."&thread=".$thread."&newpost=".$newpost);
				}
			if( isUserModerator($forum, $BAB_SESS_USERID) && $newpost > 0)
				{
				$body->addItemMenu("Waiting", babTranslate("Waiting Posts"), $GLOBALS[babUrl]."index.php?tg=posts&idx=Waiting&forum=".$forum."&thread=".$thread."&newpost=".$newpost);
				if($open)
					$body->addItemMenu("Close", babTranslate("Close thread"), $GLOBALS[babUrl]."index.php?tg=posts&idx=Close&forum=".$forum."&thread=".$thread."&newpost=".$newpost);
			else
				$body->addItemMenu("Open", babTranslate("Open thread"), $GLOBALS[babUrl]."index.php?tg=posts&idx=Open&forum=".$forum."&thread=".$thread."&newpost=".$newpost);
				}
			}		
		break;

	case "Waiting":
		if( isUserModerator($forum, $BAB_SESS_USERID) && $newpost > 0)
			{
			$body->title = getForumName($forum);
			listPosts($forum, $thread, $pos, "N");
			$body->addItemMenu("List", babTranslate("List"), $GLOBALS[babUrl]."index.php?tg=posts&idx=List&forum=".$forum."&thread=".$thread."&pos=".$pos);
			$open = isThreadOpen($thread);
			if( isAccessValid("forumsreply_groups", $forum) && $open)
				{
				$body->addItemMenu("reply", babTranslate("Reply"), $GLOBALS[babUrl]."index.php?tg=posts&idx=reply&forum=".$forum."&thread=".$thread."&newpost=".$newpost);
				}
			$body->addItemMenu("Waiting", babTranslate("Waiting Posts"), $GLOBALS[babUrl]."index.php?tg=posts&idx=Waiting&forum=".$forum."&thread=".$thread."&newpost=".$newpost);
			if( $open)
				$body->addItemMenu("Close", babTranslate("Close thread"), $GLOBALS[babUrl]."index.php?tg=posts&idx=Close&forum=".$forum."&thread=".$thread."&newpost=".$newpost);
			else
				$body->addItemMenu("Open", babTranslate("Open thread"), $GLOBALS[babUrl]."index.php?tg=posts&idx=Open&forum=".$forum."&thread=".$thread."&newpost=".$newpost);
			}		
		break;

	case "Modify":
		if( isUserModerator($forum, $BAB_SESS_USERID))
			{
			$body->title = getForumName($forum);
			editPost($forum, $thread, $post, $newpost);
			$body->addItemMenu("List", babTranslate("List"), $GLOBALS[babUrl]."index.php?tg=posts&idx=List&forum=".$forum."&thread=".$thread."&pos=".$pos);
			$open = isThreadOpen($thread);
			if( isAccessValid("forumsreply_groups", $forum) && $open)
				{
				$body->addItemMenu("reply", babTranslate("Reply"), $GLOBALS[babUrl]."index.php?tg=posts&idx=reply&forum=".$forum."&thread=".$thread."&newpost=".$newpost);
				}
			if( $newpost > 0)
				$body->addItemMenu("Waiting", babTranslate("Waiting Posts"), $GLOBALS[babUrl]."index.php?tg=posts&idx=Waiting&forum=".$forum."&thread=".$thread."&newpost=".$newpost);
			if($open)
				$body->addItemMenu("Close", babTranslate("Close thread"), $GLOBALS[babUrl]."index.php?tg=posts&idx=Close&forum=".$forum."&thread=".$thread."&newpost=".$newpost);
			else
				$body->addItemMenu("Open", babTranslate("Open thread"), $GLOBALS[babUrl]."index.php?tg=posts&idx=Open&forum=".$forum."&thread=".$thread."&newpost=".$newpost);
			$body->addItemMenu("DeleteP", babTranslate("Delete"), $GLOBALS[babUrl]."index.php?tg=posts&idx=DeleteP&forum=".$forum."&thread=".$thread."&newpost=".$newpost."&post=".$post);
			}		
		break;
	case "DeleteT":
		if( isUserModerator($forum, $BAB_SESS_USERID))
			{
			deleteThread($forum, $thread, $newpost);
			if( isAccessValid("forumsview_groups", $forum))
				{
				$body->title = getForumName($forum);
				$body->addItemMenu("List", babTranslate("List"), $GLOBALS[babUrl]."index.php?tg=posts&idx=List&forum=".$forum."&thread=".$thread."&pos=".$pos);
				$body->addItemMenu("DeleteT", babTranslate("Delete thread"), $GLOBALS[babUrl]."index.php?tg=posts&idx=DeleteT&forum=".$forum."&thread=".$thread."&newpost=".$newpost);
				}		
			}
		break;

	case "Confirm":
		confirm($forum, $thread, $post);
		$idx = "List";
		/* no break */
	default:
	case "List":
		$body->title = getForumName($forum);
		$open = isThreadOpen($thread);
		if( isAccessValid("forumsview_groups", $forum))
			{
			$count = listPosts($forum, $thread, $pos, "Y");
			$body->addItemMenu("Threads", babTranslate("Threads"), $GLOBALS[babUrl]."index.php?tg=threads&idx=List&forum=".$forum);
			$body->addItemMenu("List", babTranslate("List"), $GLOBALS[babUrl]."index.php?tg=posts&idx=List&forum=".$forum."&thread=".$thread."&pos=".$pos);
			if( isAccessValid("forumsreply_groups", $forum) && $open)
				{
				$body->addItemMenu("reply", babTranslate("Reply"), $GLOBALS[babUrl]."index.php?tg=posts&idx=reply&forum=".$forum."&thread=".$thread."&newpost=".$newpost);
				}
			if( isUserModerator($forum, $BAB_SESS_USERID) )
				{
				if( $newpost > 0 )
				$body->addItemMenu("Waiting", babTranslate("Waiting Posts"), $GLOBALS[babUrl]."index.php?tg=posts&idx=Waiting&forum=".$forum."&thread=".$thread."&newpost=".$newpost);
				if( $open)
					$body->addItemMenu("Close", babTranslate("Close thread"), $GLOBALS[babUrl]."index.php?tg=posts&idx=Close&forum=".$forum."&thread=".$thread."&newpost=".$newpost);
				else
					$body->addItemMenu("Open", babTranslate("Open thread"), $GLOBALS[babUrl]."index.php?tg=posts&idx=Open&forum=".$forum."&thread=".$thread."&newpost=".$newpost);
				$body->addItemMenu("DeleteT", babTranslate("Delete thread"), $GLOBALS[babUrl]."index.php?tg=posts&idx=DeleteT&forum=".$forum."&thread=".$thread."&newpost=".$newpost);
				}
			}
		break;
	}
$body->setCurrentItemMenu($idx);

?>