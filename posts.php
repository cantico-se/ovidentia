<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
include $babInstallPath."utilit/forumincl.php";
include $babInstallPath."utilit/topincl.php";

function listPosts($forum, $thread, $post)
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
		var $more;
		var $moreurl;
		var $morename;
		var $confirmurl;
		var $confirmname;
		var $postid;
		var $alternate;
		var $subject;
		var $author;
		var $date;

		function temp($forum, $thread, $post)
			{
			global $moderator, $views;
			$this->subject = babTranslate("Subject");
			$this->author = babTranslate("Author");
			$this->date = babTranslate("Date");
			$this->forum = $forum;
			$this->thread = $thread;
			$this->alternate = 0;
			$this->more = "";
			$this->db = new db_mysql();
			if( $views == "1")
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
				
			if( $moderator )
				$req = "select * from posts where id_thread='".$thread."' and id_parent='0'";
			else
				$req = "select * from posts where id_thread='".$thread."' and id_parent='0' and confirmed='Y'";
			$res = $this->db->db_query($req);
			if( $res && $this->db->db_num_rows($res) > 0)
				{
				$arr = $this->db->db_fetch_array($res);
				$firstpost = $arr['id'];
				}
			else
				$firstpost = 0;

			$this->postid = $post;

			if( $this->postid > 0)
				{
				if( $moderator )
					$req = "select * from posts where id='".$this->postid."'";
				else
					$req = "select * from posts where id='".$this->postid."' and confirmed='Y'";
				$res = $this->db->db_query($req);
				$arr = $this->db->db_fetch_array($res);
				$this->postdate = bab_strftime(bab_mktime($arr['date']));
				$this->postauthor = $arr['author'];
				$this->postsubject = $arr['subject'];
				$this->postmessage = babReplace($arr['message']);
				$dateupdate = bab_mktime($this->arr['dateupdate']);
				$this->confirmurl = "";
				$this->confirmname = "";
				$this->moreurl = "";
				$this->morename = "";
				$this->more = "";
				$this->what = $arr['confirmed'];
				if(  $arr['confirmed'] == "Y" && $dateupdate > 0)
					{
					$req = "select * from forums where id='".$this->forum."'";
					$res = $this->db->db_query($req);
					$arr = $this->db->db_fetch_array($res);

					$req = "select * from users where id='".$arr['moderator']."'";
					$res = $this->db->db_query($req);
					$arr = $this->db->db_fetch_array($res);
					$this->more = babTranslate("Modified by")." ".composeName($arr['firstname'],$arr['lastname'])." ".babTranslate("on")." ".bab_strftime($dateupdate);
					$this->confirmurl = "";
					$this->confirmname = "";
					$this->moreurl = "";
					$this->morename = "";
					}
				else if( $arr['confirmed']  == "N" )
					{
					$this->confirmurl = $GLOBALS['babUrl']."index.php?tg=posts&idx=Confirm&forum=".$this->forum."&thread=".$this->thread."&post=".$arr['id'];
					$this->confirmname = babTranslate("Confirm");
					$this->deleteurl = $GLOBALS['babUrl']."index.php?tg=posts&idx=DeleteP&forum=".$this->forum."&thread=".$this->thread."&post=".$this->postid;
					$this->deletename = babTranslate("Refuse");
					$this->moreurl = $GLOBALS['babUrl']."index.php?tg=posts&idx=Modify&forum=".$this->forum."&thread=".$this->thread."&post=".$arr['id'];
					$this->morename = babTranslate("Edit");
					}
				}


			$this->arrresult = array();
			$this->getChild($firstpost, 0, -1, 0);
			$this->count = count($this->arrresult['id']);
			}

		function getChild($id, $delta, $iparent, $leaf)
			{
			global $moderator;
			static $k=0;
			if($moderator)
				$req = "select * from posts where id_thread='".$this->thread."' and id='".$id."'";
			else
				$req = "select * from posts where id_thread='".$this->thread."' and id='".$id."' and confirmed='Y'";
			$res = $this->db->db_query($req);
			if( !$res && $this->db->db_num_rows($res) < 1)
				return;
			$arr = $this->db->db_fetch_array($res);
			$idx = $k;
			$this->arrresult["id"][$k] = $arr['id']; 
			$this->arrresult["delta"][$k] = $delta;
			$this->arrresult["parent"][$k] = 1;
			$this->arrresult["iparent"][$k] = $iparent;
			$this->arrresult["leaf"][$k] = $leaf;

			$tab = array();
			if( $iparent >= 0)
				{
				$tab = $this->arrresult["schema"][$iparent];
				$p = $this->arrresult["iparent"][$iparent];
				if( $this->arrresult["leaf"][$iparent] == 1)
					$tab[$this->arrresult["delta"][$p]] = 0;
				else
					$tab[$this->arrresult["delta"][$p]] = 1;
				}
			$this->arrresult["schema"][$k] = $tab;

			$k++;
			if($moderator)
				$req = "select * from posts where id_thread='".$this->thread."' and id_parent='".$arr['id']."' order by date asc";
			else
				$req = "select * from posts where id_thread='".$this->thread."' and id_parent='".$arr['id']."' and confirmed='Y' order by date asc";
			$res = $this->db->db_query($req);
			if( !$res || $this->db->db_num_rows($res) < 1)
				{
				$this->arrresult['parent'][$k-1] = 0;
				return;
				}
			$count = $this->db->db_num_rows($res);
			for( $i = 0; $i < $count; $i++)
				{
				$arr = $this->db->db_fetch_array($res);
				if( $i == $count -1)
					$this->getChild($arr['id'], $delta + 1, $idx, 1);
				else
					$this->getChild($arr['id'], $delta + 1, $idx, 0);
				}

			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$this->replyauthor = "";
				$this->replysubject = "";
				$this->replydate = "";
				$req = "select * from posts where id='".$this->arrresult['id'][$i]."'";
				$res = $this->db->db_query($req);
				if( $res && $this->db->db_num_rows($res) > 0)
					{
					$arr = $this->db->db_fetch_array($res);
					//$this->replydate = bab_strftime(bab_mktime($arr['date']));
					$tmp = explode(" ", $arr['date']);
					$arr0 = explode("-", $tmp[0]);
					$arr1 = explode(":", $tmp[1]);
					$this->replydate = $arr0[2]."/".$arr0[1]."/".$arr0[0]." ".$arr1[0].":".$arr1[1];
					$this->replyauthor = $arr['author'];
					$this->replysubject = $arr['subject'];
					}
				if( $arr['confirmed'] == "N")
					$this->confirmed = "C";
				else
					$this->confirmed = "";
				
				if( $this->alternate == 0)
					$this->alternate = 1;
				else
					$this->alternate = 0;
				$this->transarr = $this->arrresult['schema'][$i];
				$this->leaf = $this->arrresult['leaf'][$i];
				$this->delta = $this->arrresult['delta'][$i];
				if( $this->arrresult['parent'][$i] == 1)
					{
					$this->parent = 1;
					}
				else
					{
					$this->parent = 0;
					}

				$this->nbtrans = count($this->transarr) - 1;
				if($i == 0)
					{
					$this->first = 1;
					}
				else
					$this->first = 0;

				if( $this->arrresult['id'][$i] == $this->postid )
					$this->current = 1;
				else
					$this->current = 0;
				$this->replysubjecturl = $GLOBALS['babUrl']."index.php?tg=posts&idx=List&forum=".$this->forum."&thread=".$this->thread."&post=".$this->arrresult['id'][$i];
				$i++;
				return true;
				}
			else
				return false;
			}

		function gettrans()
			{
			static $m = 0;
			if( $m < $this->nbtrans)
				{
				if( $this->transarr[$m] == 1)
					$this->vert = 1;
				else
					$this->vert = 0;

				$m++;
				return true;
				}
			else
				{
				$m = 0;
				return false;
				}
			}

		}
	
	$temp = new temp($forum, $thread, $post);
	$body->babecho(	babPrintTemplate($temp,"posts.html", "newpostslist"));
	return $temp->count;
	}


function newReply($forum, $thread, $post)
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
		var $postid;
		var $msie;
		

		function temp($forum, $thread, $post)
			{
			global $BAB_SESS_USER;
			$this->subject = babTranslate("Subject");
			$this->name = babTranslate("Your Name");
			$this->message = babTranslate("Message");
			$this->add = babTranslate("New reply");
			$this->forum = $forum;
			$this->thread = $thread;
			$this->postid = $post;

			$db = new db_mysql();
			$req = "select * from posts where id='".$post."'";
			$res = $db->db_query($req);
			$arr = $db->db_fetch_array($res);
			if( substr($arr['subject'], 0, 3) == "RE:")
				$this->subjectval = $arr['subject'];
			else
				$this->subjectval = "RE:".$arr['subject'];
			if( empty($BAB_SESS_USER))
				$this->anonyme = 1;
			else
				{
				$this->anonyme = 0;
				$this->username = $BAB_SESS_USER;
				}
			if(( strtolower(browserAgent()) == "msie") and (browserOS() == "windows"))
				$this->msie = 1;
			else
				$this->msie = 0;	
			}
		}

	$temp = new temp($forum, $thread, $post);
	$body->babecho(	babPrintTemplate($temp,"posts.html", "postcreate"));
	}

function editPost($forum, $thread, $post)
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
		var $msie;

		function temp($forum, $thread, $post)
			{
			global $BAB_SESS_USER;
			$this->subject = babTranslate("Subject");
			$this->name = babTranslate("Name");
			$this->message = babTranslate("Message");
			$this->update = babTranslate("Update reply");
			$this->forum = $forum;
			$this->thread = $thread;
			$this->post = $post;
			$db = new db_mysql();
			$req = "select * from posts where id='$post'";
			$res = $db->db_query($req);
			$this->arr = $db->db_fetch_array($res);
			if(( strtolower(browserAgent()) == "msie") and (browserOS() == "windows"))
				$this->msie = 1;
			else
				$this->msie = 0;	
			}
		}

	$temp = new temp($forum, $thread, $post);
	$body->babecho(	babPrintTemplate($temp,"posts.html", "postedit"));
	}

function deleteThread($forum, $thread)
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

		function temp($forum, $thread)
			{
			$this->message = babTranslate("Are you sure you want to delete this thread");
			$this->title = getThreadTitle($thread);
			$this->warning = babTranslate("WARNING: This operation will delete the thread and all references"). "!";
			$this->urlyes = $GLOBALS['babUrl']."index.php?tg=posts&idx=DeleteT&forum=".$forum."&thread=".$thread."&action=Yes";
			$this->yes = babTranslate("Yes");
			$this->urlno = $GLOBALS['babUrl']."index.php?tg=posts&idx=List&forum=".$forum."&thread=".$thread;
			$this->no = babTranslate("No");
			}
		}

	$temp = new temp($forum, $thread);
	$body->babecho(	babPrintTemplate($temp,"warning.html", "warningyesno"));
	}

function notifyThreadAuthor($threadTitle, $email, $author)
	{
	global $body, $BAB_SESS_USER, $BAB_SESS_EMAIL, $babAdminEmail, $babInstallPath;
    include $babInstallPath."utilit/mailincl.php";

	class tempa
		{
		var $message;
        var $from;
        var $author;
        var $thread;
        var $threadname;
        var $site;
        var $sitename;
        var $date;
        var $dateval;


		function tempa($threadTitle, $email, $author)
			{
            global $BAB_SESS_USER, $BAB_SESS_EMAIL, $babSiteName;
            $this->message = babTranslate("A new post has been registered on thread");
            $this->from = babTranslate("Author");
            $this->thread = babTranslate("Thread");
            $this->threadname = $threadTitle;
            $this->site = babTranslate("Web site");
            $this->sitename = $babSiteName;
            $this->date = babTranslate("Date");
            $this->dateval = bab_strftime(mktime());

            $this->author = $author;
			}
		}
	
	$tempa = new tempa($threadTitle, $email, $author);
	$message = babPrintTemplate($tempa,"mailinfo.html", "newpost");

    $mail = new babMail();
    $mail->mailTo($email);
    $mail->mailFrom($babAdminEmail, "Ovidentia Administrator");
    $mail->mailSubject(babTranslate("New post"));
    $mail->mailBody($message, "html");
    $mail->send();
	}


function saveReply($forum, $thread, $post, $name, $subject, $message)
	{
	global $BAB_SESS_USER, $BAB_SESS_USERID, $body;

	if( empty($message))
		{
		$body->msgerror = babTranslate("ERROR: You must provide a content for your message")." !";
		return;
		}

	if( empty($subject))
		{
		$body->msgerror = babTranslate("ERROR: You must provide a subject for your message")." !";
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

	if(!get_cfg_var("magic_quotes_gpc"))
		{
		$subject = addslashes($subject);
		$message = addslashes($message);
		$name = addslashes($name);
		}

	$req = "insert into posts (id_thread, date, subject, message, author, confirmed, id_parent) values ";
	$req .= "('" .$thread. "', now(), '" . $subject. "', '" . $message. "', '". $name. "', '". $confirmed."', '". $post. "')";
	$res = $db->db_query($req);
	$idpost = $db->db_insert_id();
	
	$req = "update threads set lastpost='$idpost' where id='$thread'";
	$res = $db->db_query($req);

	$req = "select * from threads where id='$thread'";
	$res = $db->db_query($req);
	$arr = $db->db_fetch_array($res);
	if( $arr['notify'] == "Y" && $arr['starter'] != 0)
		{
		$req = "select * from users where id='".$arr['starter']."'";
		$res = $db->db_query($req);
		$arr = $db->db_fetch_array($res);
		//$msg = babTranslate("A new post has been registered on thread").": \n  ". getThreadTitle($thread);
		//mail ($arr['email'],'New Post',$msg,"From: ".$babAdminEmail);
        notifyThreadAuthor(getThreadTitle($thread), $arr['email'], $name);
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


function deletePost($forum, $post)
	{
	$db = new db_mysql();

	$req = "select * from posts where id='$post'";
	$res = $db->db_query($req);
	$arr = $db->db_fetch_array($res);
	

	if( $arr['id_parent'] == 0)
		{
		/* if it's the only post in the thread, delete the thread also */
		$req = "delete from posts where id_thread = '".$arr['id_thread']."'";
		$res = $db->db_query($req);
		$req = "delete from threads where id = '".$arr['id_thread']."'";
		$res = $db->db_query($req);
		Header("Location: index.php?tg=threads&forum=".$forum);
		}
	else
		{
		$req = "delete from posts where id = '$post'";
		$res = $db->db_query($req);

		$req = "select * from threads where id='".$arr['id_thread']."'";
		$res = $db->db_query($req);
		$arr2 = $db->db_fetch_array($res);
		if( $arr2['lastpost'] == $post ) // it's the lastpost
			{
			$req = "select * from posts where id_thread='".$arr['id_thread']."' order by date desc";
			$res = $db->db_query($req);
			$arr2 = $db->db_fetch_array($res);
			$req = "update threads set lastpost='".$arr2['id']."' where id='".$arr['id_thread']."'";
			$res = $db->db_query($req);
			}

		}

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

if( isset($add) && $add == "addreply")
	{
	saveReply($forum, $thread, $postid, $author, $subject, $message);
	$post = $postid;
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
	deletePost($forum, $post);
	unset($post);
	$idx = "List";
	}

if( isset($action) && $action == "Yes")
	{
	confirmDeleteThread($forum, $thread);
	}

$moderator = isUserModerator($forum, $BAB_SESS_USERID);

if( !isset($post))
	{
	$db = new db_mysql();
	if( $moderator )
		$req = "select * from posts where id_thread='".$thread."' and id_parent='0'";
	else
		$req = "select * from posts where id_thread='".$thread."' and id_parent='0' and confirmed='Y'";
	$res = $db->db_query($req);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		$post = $arr['id'];
		}
	else
		$post = 0;
	}


switch($idx)
	{
	case "reply":
		if( isAccessValid("forumsreply_groups", $forum))
			{
			$body->title = getForumName($forum);
			newReply($forum, $thread, $post);
			$body->addItemMenu("List", babTranslate("List"), $GLOBALS['babUrl']."index.php?tg=posts&idx=List&forum=".$forum."&thread=".$thread."&post=".$post);
			$open = isThreadOpen($thread);
			if( isAccessValid("forumsreply_groups", $forum) && $open)
				{
				$body->addItemMenu("reply", babTranslate("Reply"), $GLOBALS['babUrl']."index.php?tg=posts&idx=reply&forum=".$forum."&thread=".$thread."&post=".$post);
				}
			if( $moderator )
				{
				if($open)
					$body->addItemMenu("Close", babTranslate("Close thread"), $GLOBALS['babUrl']."index.php?tg=posts&idx=Close&forum=".$forum."&thread=".$thread);
			else
				$body->addItemMenu("Open", babTranslate("Open thread"), $GLOBALS['babUrl']."index.php?tg=posts&idx=Open&forum=".$forum."&thread=".$thread);
				}
			}		
		break;

	case "Modify":
		if( $moderator)
			{
			$body->title = getForumName($forum);
			editPost($forum, $thread, $post);
			$body->addItemMenu("List", babTranslate("List"), $GLOBALS['babUrl']."index.php?tg=posts&idx=List&forum=".$forum."&thread=".$thread."&post=".$post);
			$open = isThreadOpen($thread);
			if( isAccessValid("forumsreply_groups", $forum) && $open)
				{
				$body->addItemMenu("reply", babTranslate("Reply"), $GLOBALS['babUrl']."index.php?tg=posts&idx=reply&forum=".$forum."&thread=".$thread."&post=".$post);
				}
			if($open)
				$body->addItemMenu("Close", babTranslate("Close thread"), $GLOBALS['babUrl']."index.php?tg=posts&idx=Close&forum=".$forum."&thread=".$thread);
			else
				$body->addItemMenu("Open", babTranslate("Open thread"), $GLOBALS['babUrl']."index.php?tg=posts&idx=Open&forum=".$forum."&thread=".$thread);
			$body->addItemMenu("Modify", babTranslate("Modify"), $GLOBALS['babUrl']."index.php?tg=posts&idx=Modify&forum=".$forum."&thread=".$thread."&post=".$post);
			}		
		break;
	case "DeleteT":
		if( $moderator)
			{
			deleteThread($forum, $thread);
			if( isAccessValid("forumsview_groups", $forum))
				{
				$body->title = getForumName($forum);
				$body->addItemMenu("List", babTranslate("List"), $GLOBALS['babUrl']."index.php?tg=posts&idx=List&forum=".$forum."&thread=".$thread."&post=".$post);
				$body->addItemMenu("DeleteT", babTranslate("Delete thread"), $GLOBALS['babUrl']."index.php?tg=posts&idx=DeleteT&forum=".$forum."&thread=".$thread);
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
			$count = listPosts($forum, $thread, $post);
			$body->addItemMenu("Threads", babTranslate("Threads"), $GLOBALS['babUrl']."index.php?tg=threads&idx=List&forum=".$forum);
			$body->addItemMenu("List", babTranslate("List"), $GLOBALS['babUrl']."index.php?tg=posts&idx=List&forum=".$forum."&thread=".$thread."&post=".$post);
			if( isAccessValid("forumsreply_groups", $forum) && $open)
				{
				$body->addItemMenu("reply", babTranslate("Reply"), $GLOBALS['babUrl']."index.php?tg=posts&idx=reply&forum=".$forum."&thread=".$thread."&post=".$post);
				}
			if( $moderator )
				{
				if( $open)
					$body->addItemMenu("Close", babTranslate("Close thread"), $GLOBALS['babUrl']."index.php?tg=posts&idx=Close&forum=".$forum."&thread=".$thread);
				else
					$body->addItemMenu("Open", babTranslate("Open thread"), $GLOBALS['babUrl']."index.php?tg=posts&idx=Open&forum=".$forum."&thread=".$thread);
				$body->addItemMenu("DeleteT", babTranslate("Delete thread"), $GLOBALS['babUrl']."index.php?tg=posts&idx=DeleteT&forum=".$forum."&thread=".$thread);
				}
			}
		break;
	}
$body->setCurrentItemMenu($idx);

?>