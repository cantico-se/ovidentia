<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
include_once "base.php";
include $babInstallPath."utilit/forumincl.php";
include $babInstallPath."utilit/topincl.php";
include $babInstallPath."utilit/mailincl.php";

function listPosts($forum, $thread, $post)
	{
	global $babBody;

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
			$this->subject = bab_translate("Subject");
			$this->author = bab_translate("Author");
			$this->date = bab_translate("Date");
			$this->forum = $forum;
			$this->thread = $thread;
			$this->alternate = 0;
			$this->more = "";
			$this->db = $GLOBALS['babDB'];
			if( $views == "1")
				{
				//update views
				$req = "select * from ".BAB_THREADS_TBL." where id='$thread'";
				$res = $this->db->db_query($req);
				$row = $this->db->db_fetch_array($res);
				$views = $row["views"];
				$views += 1;
				$req = "update ".BAB_THREADS_TBL." set views='$views' where id='$thread'";
				$res = $this->db->db_query($req);
				}
				
			if( $moderator )
				$req = "select * from ".BAB_POSTS_TBL." where id_thread='".$thread."' and id_parent='0'";
			else
				$req = "select * from ".BAB_POSTS_TBL." where id_thread='".$thread."' and id_parent='0' and confirmed='Y'";
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
					$req = "select * from ".BAB_POSTS_TBL." where id='".$this->postid."'";
				else
					$req = "select * from ".BAB_POSTS_TBL." where id='".$this->postid."' and confirmed='Y'";
				$res = $this->db->db_query($req);
				$arr = $this->db->db_fetch_array($res);
				$this->postdate = bab_strftime(bab_mktime($arr['date']));
				$this->postauthor = $arr['author'];
				$this->postsubject = $arr['subject'];
				$this->postmessage = bab_replace($arr['message']);
				$dateupdate = bab_mktime($this->arr['dateupdate']);
				$this->confirmurl = "";
				$this->confirmname = "";
				$this->moreurl = "";
				$this->morename = "";
				$this->more = "";
				$this->what = $arr['confirmed'];
				if(  $arr['confirmed'] == "Y" && $dateupdate > 0)
					{
					$req = "select * from ".BAB_FORUMS_TBL." where id='".$this->forum."'";
					$res = $this->db->db_query($req);
					$arr = $this->db->db_fetch_array($res);

					$req = "select * from ".BAB_USERS_TBL." where id='".$arr['moderator']."'";
					$res = $this->db->db_query($req);
					$arr = $this->db->db_fetch_array($res);
					$this->more = bab_translate("Modified by")." ".bab_composeUserName($arr['firstname'],$arr['lastname'])." ".bab_translate("on")." ".bab_strftime($dateupdate);
					$this->confirmurl = "";
					$this->confirmname = "";
					$this->moreurl = "";
					$this->morename = "";
					}
				else if( $arr['confirmed']  == "N" )
					{
					$this->confirmurl = $GLOBALS['babUrlScript']."?tg=posts&idx=Confirm&forum=".$this->forum."&thread=".$this->thread."&post=".$arr['id'];
					$this->confirmname = bab_translate("Confirm");
					$this->deleteurl = $GLOBALS['babUrlScript']."?tg=posts&idx=DeleteP&forum=".$this->forum."&thread=".$this->thread."&post=".$this->postid;
					$this->deletename = bab_translate("Refuse");
					$this->moreurl = $GLOBALS['babUrlScript']."?tg=posts&idx=Modify&forum=".$this->forum."&thread=".$this->thread."&post=".$arr['id'];
					$this->morename = bab_translate("Edit");
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
				$req = "select * from ".BAB_POSTS_TBL." where id_thread='".$this->thread."' and id='".$id."'";
			else
				$req = "select * from ".BAB_POSTS_TBL." where id_thread='".$this->thread."' and id='".$id."' and confirmed='Y'";
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
				$req = "select * from ".BAB_POSTS_TBL." where id_thread='".$this->thread."' and id_parent='".$arr['id']."' order by date asc";
			else
				$req = "select * from ".BAB_POSTS_TBL." where id_thread='".$this->thread."' and id_parent='".$arr['id']."' and confirmed='Y' order by date asc";
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
				$req = "select * from ".BAB_POSTS_TBL." where id='".$this->arrresult['id'][$i]."'";
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
					$res = $this->db->db_query("select email from ".BAB_USERS_TBL." where id='".bab_getUserId( $arr['author'])."'");
					if( $res && $this->db->db_num_rows($res) > 0)
						{
						$r = $this->db->db_fetch_array($res);

						$this->replymail = $r['email']."?subject=";
						if( substr($arr['subject'], 0, 3) != "RE:")
							$this->replymail .= "RE: ";
						$this->replymail .= $arr['subject'];
						}
					else
						$this->replymail = 0;
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
				$this->replysubjecturl = $GLOBALS['babUrlScript']."?tg=posts&idx=List&forum=".$this->forum."&thread=".$this->thread."&post=".$this->arrresult['id'][$i];
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
	$babBody->babecho(	bab_printTemplate($temp,"posts.html", "newpostslist"));
	return $temp->count;
	}


function newReply($forum, $thread, $post)
	{
	global $babBody;
	
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
		var $noteforum;
		

		function temp($forum, $thread, $post)
			{
			global $BAB_SESS_USER;
			$this->subject = bab_translate("Subject");
			$this->name = bab_translate("Your Name");
			$this->message = bab_translate("Message");
			$this->add = bab_translate("New reply");
			$this->forum = $forum;
			$this->thread = $thread;
			$this->postid = $post;

			$db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_POSTS_TBL." where id='".$post."'";
			$res = $db->db_query($req);
			$arr = $db->db_fetch_array($res);
			if( substr($arr['subject'], 0, 3) == "RE:")
				$this->subjectval = htmlentities($arr['subject']);
			else
				$this->subjectval = "RE:".htmlentities($arr['subject']);
			if( empty($BAB_SESS_USER))
				$this->anonyme = 1;
			else
				{
				$this->anonyme = 0;
				$this->username = $BAB_SESS_USER;
				}
			if(( strtolower(bab_browserAgent()) == "msie") and (bab_browserOS() == "windows"))
				$this->msie = 1;
			else
				$this->msie = 0;	

			$this->postdate = bab_strftime(bab_mktime($arr['date']));
			$this->postauthor = $arr['author'];
			$this->postsubject = $arr['subject'];
			$this->postmessage = bab_replace($arr['message']);
			if( bab_isForumModerated($forum))
				$this->noteforum = bab_translate("Note: Posts are moderate and consequently your post will not be visible immediately");
			else
				$this->noteforum = "";
			}
		}

	$temp = new temp($forum, $thread, $post);
	$babBody->babecho(	bab_printTemplate($temp,"posts.html", "postcreate"));
	}

function editPost($forum, $thread, $post)
	{
	global $babBody;
	
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
			$this->subject = bab_translate("Subject");
			$this->name = bab_translate("Name");
			$this->message = bab_translate("Message");
			$this->update = bab_translate("Update reply");
			$this->forum = $forum;
			$this->thread = $thread;
			$this->post = $post;
			$db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_POSTS_TBL." where id='$post'";
			$res = $db->db_query($req);
			$this->arr = $db->db_fetch_array($res);
			if(( strtolower(bab_browserAgent()) == "msie") and (bab_browserOS() == "windows"))
				$this->msie = 1;
			else
				$this->msie = 0;	
			}
		}

	$temp = new temp($forum, $thread, $post);
	$babBody->babecho(	bab_printTemplate($temp,"posts.html", "postedit"));
	}

function deleteThread($forum, $thread)
	{
	global $babBody;

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
			$this->message = bab_translate("Are you sure you want to delete this thread");
			$this->title = bab_getForumThreadTitle($thread);
			$this->warning = bab_translate("WARNING: This operation will delete the thread and all references"). "!";
			$this->urlyes = $GLOBALS['babUrlScript']."?tg=posts&idx=DeleteT&forum=".$forum."&thread=".$thread."&action=Yes";
			$this->yes = bab_translate("Yes");
			$this->urlno = $GLOBALS['babUrlScript']."?tg=posts&idx=List&forum=".$forum."&thread=".$thread;
			$this->no = bab_translate("No");
			}
		}

	$temp = new temp($forum, $thread);
	$babBody->babecho(	bab_printTemplate($temp,"warning.html", "warningyesno"));
	}

function notifyThreadAuthor($threadTitle, $email, $author)
	{
	global $babBody, $BAB_SESS_USER, $BAB_SESS_EMAIL, $babAdminEmail, $babInstallPath;

	class tempb
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


		function tempb($threadTitle, $email, $author)
			{
            global $BAB_SESS_USER, $BAB_SESS_EMAIL, $babSiteName;
            $this->message = bab_translate("A new post has been registered on thread");
            $this->from = bab_translate("Author");
            $this->thread = bab_translate("Thread");
            $this->threadname = $threadTitle;
            $this->site = bab_translate("Web site");
            $this->sitename = $babSiteName;
            $this->date = bab_translate("Date");
            $this->dateval = bab_strftime(mktime());

            $this->author = $author;
			}
		}
	
    $mail = bab_mail();
	if( $mail == false )
		return;

	$mail->mailTo($email);
    $mail->mailFrom($babAdminEmail, bab_translate("Ovidentia Administrator"));
    $mail->mailSubject(bab_translate("New post"));

	$tempb = new tempb($threadTitle, $email, $author);
	$message = bab_printTemplate($tempb,"mailinfo.html", "newpost");
    $mail->mailBody($message, "html");

	$message = bab_printTemplate($tempb,"mailinfo.html", "newposttxt");
    $mail->mailAltBody($message);

	$mail->send();
	}


function saveReply($forum, $thread, $post, $name, $subject, $message)
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
		}
	else
		{
		$name = $BAB_SESS_USER;
		}

	$db = $GLOBALS['babDB'];

	if( bab_isForumModerated($forum))
		$confirmed = "N";
	else
		$confirmed = "Y";

	$req = "insert into ".BAB_POSTS_TBL." (id_thread, date, subject, message, author, confirmed, id_parent) values ";
	$req .= "('" .$thread. "', now(), '";
	if( !bab_isMagicQuotesGpcOn())
		$req .= addslashes(bab_stripDomainName($subject)). "', '" . addslashes(bab_stripDomainName($message)). "', '". addslashes($name);
	else
		$req .= bab_stripDomainName($subject). "', '" . bab_stripDomainName($message). "', '". $name;
	$req .= "', '". $confirmed."', '". $post. "')";
	$res = $db->db_query($req);
	$idpost = $db->db_insert_id();
	
	$req = "update ".BAB_THREADS_TBL." set lastpost='$idpost' where id='$thread'";
	$res = $db->db_query($req);

	$req = "select * from ".BAB_THREADS_TBL." where id='$thread'";
	$res = $db->db_query($req);
	$arr = $db->db_fetch_array($res);
	if( $confirmed == "Y" && $arr['notify'] == "Y" && $arr['starter'] != 0)
		{
		$req = "select * from ".BAB_USERS_TBL." where id='".$arr['starter']."'";
		$res = $db->db_query($req);
		$arr = $db->db_fetch_array($res);
        notifyThreadAuthor(bab_getForumThreadTitle($thread), $arr['email'], $name);
		}

	$arr = $db->db_fetch_array($db->db_query("select * from ".BAB_FORUMS_TBL." where id='".$forum."'"));
	if( $arr['notification'] == "Y" && ($email = bab_getUserEmail($arr['moderator'])) != "")
		{
	    notifyModerator(stripslashes($subject), stripslashes($email), stripslashes($name), $arr['name']);
		}
	}

function confirm($forum, $thread, $post)
	{
	$db = $GLOBALS['babDB'];
	$req = "update ".BAB_THREADS_TBL." set lastpost='".$post."' where id='".$thread."'";
	$res = $db->db_query($req);

	$req = "update ".BAB_POSTS_TBL." set confirmed='Y' where id='".$post."'";
	$res = $db->db_query($req);

	$req = "select * from ".BAB_THREADS_TBL." where id='".$thread."'";
	$res = $db->db_query($req);
	$arr = $db->db_fetch_array($res);
	if( $arr['notify'] == "Y" && $arr['starter'] != 0)
		{
		$req = "select email from ".BAB_USERS_TBL." where id='".$arr['starter']."'";
		$res = $db->db_query($req);
		$arr = $db->db_fetch_array($res);
		$email = $arr['email'];

		$req = "select author from ".BAB_POSTS_TBL." where id='".$post."'";
		$res = $db->db_query($req);
		$arr = $db->db_fetch_array($res);
		$name = $arr['author'];
		
		notifyThreadAuthor(bab_getForumThreadTitle($thread), $email, $name);
		}
	}

function updateReply($forum, $thread, $subject, $message, $post)
	{
	global $babBody;

	if( empty($message))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a content for your message")." !";
		return;
		}

	$db = $GLOBALS['babDB'];

	if( !bab_isMagicQuotesGpcOn())
		$req = "update ".BAB_POSTS_TBL." set message='".addslashes(bab_stripDomainName($message))."', subject='".addslashes(bab_stripDomainName($subject))."', dateupdate=now() where id='$post'";
	else
		$req = "update ".BAB_POSTS_TBL." set message='".bab_stripDomainName($message)."', subject='".bab_stripDomainName($subject)."', dateupdate=now() where id='$post'";

	$res = $db->db_query($req);

	}

function closeThread($thread)
	{
	$db = $GLOBALS['babDB'];
	$req = "update ".BAB_THREADS_TBL." set active='N' where id='$thread'";
	$res = $db->db_query($req);
	}

function openThread($thread)
	{
	$db = $GLOBALS['babDB'];
	$req = "update ".BAB_THREADS_TBL." set active='Y' where id='$thread'";
	$res = $db->db_query($req);
	}


function deletePost($forum, $post)
	{
	$db = $GLOBALS['babDB'];

	$req = "select * from ".BAB_POSTS_TBL." where id='$post'";
	$res = $db->db_query($req);
	$arr = $db->db_fetch_array($res);
	

	if( $arr['id_parent'] == 0)
		{
		/* if it's the only post in the thread, delete the thread also */
		$req = "delete from ".BAB_POSTS_TBL." where id_thread = '".$arr['id_thread']."'";
		$res = $db->db_query($req);
		$req = "delete from ".BAB_THREADS_TBL." where id = '".$arr['id_thread']."'";
		$res = $db->db_query($req);
		Header("Location: ". $GLOBALS['babUrlScript']."?tg=threads&forum=".$forum);
		}
	else
		{
		$req = "delete from ".BAB_POSTS_TBL." where id = '$post'";
		$res = $db->db_query($req);

		$req = "select * from ".BAB_THREADS_TBL." where id='".$arr['id_thread']."'";
		$res = $db->db_query($req);
		$arr2 = $db->db_fetch_array($res);
		if( $arr2['lastpost'] == $post ) // it's the lastpost
			{
			$req = "select * from ".BAB_POSTS_TBL." where id_thread='".$arr['id_thread']."' order by date desc";
			$res = $db->db_query($req);
			$arr2 = $db->db_fetch_array($res);
			$req = "update ".BAB_THREADS_TBL." set lastpost='".$arr2['id']."' where id='".$arr['id_thread']."'";
			$res = $db->db_query($req);
			}

		}

	}

function confirmDeleteThread($forum, $thread)
	{
	// delete posts owned by this thread
	$db = $GLOBALS['babDB'];
	$req = "delete from ".BAB_POSTS_TBL." where id_thread = '$thread'";
	$res = $db->db_query($req);

	// delete thread
	$req = "delete from ".BAB_THREADS_TBL." where id = '$thread'";
	$res = $db->db_query($req);
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=threads&forum=".$forum);
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

if( $idx == "Close" && bab_isUserForumModerator($forum, $BAB_SESS_USERID))
	{
	closeThread($thread);
	$idx = "List";
	}

if( $idx == "Open" && bab_isUserForumModerator($forum, $BAB_SESS_USERID))
	{
	openThread($thread);
	$idx = "List";
	}

if( $idx == "DeleteP" && bab_isUserForumModerator($forum, $BAB_SESS_USERID))
	{
	deletePost($forum, $post);
	unset($post);
	$idx = "List";
	}

if( isset($action) && $action == "Yes")
	{
	confirmDeleteThread($forum, $thread);
	}

$moderator = bab_isUserForumModerator($forum, $BAB_SESS_USERID);

if( !isset($post))
	{
	$db = $GLOBALS['babDB'];
	if( $moderator )
		$req = "select * from ".BAB_POSTS_TBL." where id_thread='".$thread."' and id_parent='0'";
	else
		$req = "select * from ".BAB_POSTS_TBL." where id_thread='".$thread."' and id_parent='0' and confirmed='Y'";
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
		if( bab_isAccessValid(BAB_FORUMSREPLY_GROUPS_TBL, $forum))
			{
			$babBody->title = bab_getForumName($forum);
			newReply($forum, $thread, $post);
			$babBody->addItemMenu("List", bab_translate("List"), $GLOBALS['babUrlScript']."?tg=posts&idx=List&forum=".$forum."&thread=".$thread."&post=".$post);
			$open = bab_isForumThreadOpen($thread);
			if( bab_isAccessValid(BAB_FORUMSREPLY_GROUPS_TBL, $forum) && $open)
				{
				$babBody->addItemMenu("reply", bab_translate("Reply"), $GLOBALS['babUrlScript']."?tg=posts&idx=reply&forum=".$forum."&thread=".$thread."&post=".$post);
				}
			if( $moderator )
				{
				if($open)
					$babBody->addItemMenu("Close", bab_translate("Close thread"), $GLOBALS['babUrlScript']."?tg=posts&idx=Close&forum=".$forum."&thread=".$thread);
			else
				$babBody->addItemMenu("Open", bab_translate("Open thread"), $GLOBALS['babUrlScript']."?tg=posts&idx=Open&forum=".$forum."&thread=".$thread);
				}
			}		
		break;

	case "Modify":
		if( $moderator)
			{
			$babBody->title = bab_getForumName($forum);
			editPost($forum, $thread, $post);
			$babBody->addItemMenu("List", bab_translate("List"), $GLOBALS['babUrlScript']."?tg=posts&idx=List&forum=".$forum."&thread=".$thread."&post=".$post);
			$open = bab_isForumThreadOpen($thread);
			if( bab_isAccessValid(BAB_FORUMSREPLY_GROUPS_TBL, $forum) && $open)
				{
				$babBody->addItemMenu("reply", bab_translate("Reply"), $GLOBALS['babUrlScript']."?tg=posts&idx=reply&forum=".$forum."&thread=".$thread."&post=".$post);
				}
			if($open)
				$babBody->addItemMenu("Close", bab_translate("Close thread"), $GLOBALS['babUrlScript']."?tg=posts&idx=Close&forum=".$forum."&thread=".$thread);
			else
				$babBody->addItemMenu("Open", bab_translate("Open thread"), $GLOBALS['babUrlScript']."?tg=posts&idx=Open&forum=".$forum."&thread=".$thread);
			$babBody->addItemMenu("Modify", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=posts&idx=Modify&forum=".$forum."&thread=".$thread."&post=".$post);
			}		
		break;
	case "DeleteT":
		if( $moderator)
			{
			deleteThread($forum, $thread);
			if( bab_isAccessValid(BAB_FORUMSVIEW_GROUPS_TBL, $forum))
				{
				$babBody->title = bab_getForumName($forum);
				$babBody->addItemMenu("List", bab_translate("List"), $GLOBALS['babUrlScript']."?tg=posts&idx=List&forum=".$forum."&thread=".$thread."&post=".$post);
				$babBody->addItemMenu("DeleteT", bab_translate("Delete thread"), $GLOBALS['babUrlScript']."?tg=posts&idx=DeleteT&forum=".$forum."&thread=".$thread);
				}		
			}
		break;

	case "Confirm":
		confirm($forum, $thread, $post);
		$idx = "List";
		/* no break */
	default:
	case "List":
		$babBody->title = bab_getForumName($forum);
		$open = bab_isForumThreadOpen($thread);
		if( bab_isAccessValid(BAB_FORUMSVIEW_GROUPS_TBL, $forum))
			{
			$count = listPosts($forum, $thread, $post);
			$babBody->addItemMenu("Threads", bab_translate("Threads"), $GLOBALS['babUrlScript']."?tg=threads&idx=List&forum=".$forum);
			$babBody->addItemMenu("List", bab_translate("List"), $GLOBALS['babUrlScript']."?tg=posts&idx=List&forum=".$forum."&thread=".$thread."&post=".$post);
			if( bab_isAccessValid(BAB_FORUMSREPLY_GROUPS_TBL, $forum) && $open)
				{
				$babBody->addItemMenu("reply", bab_translate("Reply"), $GLOBALS['babUrlScript']."?tg=posts&idx=reply&forum=".$forum."&thread=".$thread."&post=".$post);
				}
			if( $moderator )
				{
				if( $open)
					$babBody->addItemMenu("Close", bab_translate("Close thread"), $GLOBALS['babUrlScript']."?tg=posts&idx=Close&forum=".$forum."&thread=".$thread);
				else
					$babBody->addItemMenu("Open", bab_translate("Open thread"), $GLOBALS['babUrlScript']."?tg=posts&idx=Open&forum=".$forum."&thread=".$thread);
				$babBody->addItemMenu("DeleteT", bab_translate("Delete thread"), $GLOBALS['babUrlScript']."?tg=posts&idx=DeleteT&forum=".$forum."&thread=".$thread);
				}
			}
		break;
	}
$babBody->setCurrentItemMenu($idx);

?>