<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/

include $babInstallPath."utilit/topincl.php";

function listArticles($topics)
	{
	global $body;

	class temp
		{
	
		var $content;
		var $arr = array();
		var $db;
		var $count;
		var $res;
		var $moreurl;
		var $morename;
		var $topics;

		function temp($topics)
			{
			$this->db = new db_mysql();
			$req = "select * from articles where id_topic='$topics' and confirmed='N'";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			$this->topics = $topics;
			}

		function getnext()
			{
			global $new; 
			static $i = 0;
			if( $i < $this->count)
				{
				$this->arr = $this->db->db_fetch_array($this->res);
				$this->content = locateArticle($this->arr[head]);
				$this->moreurl = $GLOBALS[babUrl]."index.php?tg=waiting&idx=More&topics=".$this->topics."&article=".$this->arr[id];
				if( isset($new) && $new > 0)
					$this->more .= "&new=".$new;

				$this->morename = babTranslate("Read more")."...";
				$i++;
				return true;
				}
			else
				return false;
			}
		}
	
	$temp = new temp($topics);
	$body->babecho(	babPrintTemplate($temp,"waiting.html", "introlist"));
	}

function readMore($topics, $article)
	{
	global $body;

	class temp
		{
	
		var $content;
		var $arr = array();
		var $db;
		var $count;
		var $res;
		var $more;
		var $topics;

		function temp($topics, $article)
			{
			$this->db = new db_mysql();
			$req = "select * from articles where id='$article' and confirmed='N'";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			$this->topics = $topics;
			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$this->arr = $this->db->db_fetch_array($this->res);
				$this->content = locateArticle($this->arr[body]);
				$i++;
				return true;
				}
			else
				return false;
			}
		}
	
	$temp = new temp($topics, $article);
	$body->babecho(	babPrintTemplate($temp,"waiting.html", "readmore"));
	}

function modifyArticle($topics, $article)
	{
	global $body;

	class temp
		{
	
		var $head;
		var $headval;
		var $body;
		var $bodyval;
		var $title;
		var $titleval;
		var $modify;
		var $topics;
		var $article;
		var $arr = array();
		var $db;
		var $count;
		var $res;
		var $msie;

		function temp($topics, $article)
			{
			$this->article = $article;
			$this->topics = $topics;
			$this->head = babTranslate("Head");
			$this->body = babTranslate("Body");
			$this->title = babTranslate("Title");
			$this->modify = babTranslate("Modify");
			$this->db = new db_mysql();
			$req = "select * from articles where id='$article' and confirmed='N'";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			if( $this->count > 0)
				{
				$this->arr = $this->db->db_fetch_array($this->res);
				$this->headval = htmlentities($this->arr[head]);
				$this->bodyval = htmlentities($this->arr[body]);
				$this->titleval = $this->arr[title];
				}
			if( strtolower(browserAgent()) == "msie")
				$this->msie = 1;
			else
				$this->msie = 0;	
			}
		}
	
	$temp = new temp($topics, $article);
	$body->babecho(	babPrintTemplate($temp,"waiting.html", "modifyarticle"));
	}

function confirmArticle($article, $topics)
	{
	global $body;

	class temp
		{
		var $name;
		var $nameval;
		var $action;
		var $confirm;
		var $refuse;
		var $what;
		var $new;
		var $message;
		var $modify;
		var $topics;
		var $article;
		var $fullname;
		var $author;
		var $db;
		var $count;
		var $res;
		var $confval;
		var $idxval;


		function temp($topics, $article)
			{
			global $new;
			$this->article = $article;
			$this->topics = $topics;
			$this->new = $new;
			$this->name = babTranslate("Submiter");
			$this->modify = babTranslate("Update");
			$this->action = babTranslate("Action");
			$this->confirm = babTranslate("Confirm");
			$this->refuse = babTranslate("Refuse");
			$this->homepage0 = babTranslate("Add to unregistered users home page");
			$this->homepage1 = babTranslate("Add to registered users home page");
			$this->what = babTranslate("Send an email to author");
			$this->message = babTranslate("Message");
			$this->confval = "article";
			$this->idxval = "Waiting";

			$this->db = new db_mysql();
			$req = "select * from articles where id='$article'";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			if( $this->count > 0)
				{
				$arr = $this->db->db_fetch_array($this->res);
				$req = "select * from users where id='".$arr[id_author]."'";
				$this->res = $this->db->db_query($req);
				$arr2 = $this->db->db_fetch_array($this->res);
				$this->fullname = composeName($arr2[firstname], $arr2[lastname]);
				$this->author = $arr[id_author];
				}
			}
		}
	
	$temp = new temp($topics, $article);
	$body->babecho(	babPrintTemplate($temp,"waiting.html", "confirmarticle"));
	}

function listWaitingComments($topics, $article, $newc)
	{
	global $body;

	class temp
		{
	
		var $subjecturl;
		var $subjectval;
		var $arr = array();
		var $db;
		var $count;
		var $res;
		var $topics;
		var $article;
		var $newc;
		var $alternate;

		function temp($topics, $article, $newc)
			{
			$this->db = new db_mysql();
			$req = "select * from comments where id_article='$article' and confirmed='N'";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			$this->topics = $topics;
			$this->article = $article;
			$this->newc = $newc;
			$this->alternate = 0;
			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				if( $this->alternate == 0)
					$this->alternate = 1;
				else
					$this->alternate = 0;
				$this->arr = $this->db->db_fetch_array($this->res);
				$this->arr[date] = bab_strftime(bab_mktime($this->arr[date]));
				$this->subjecturl = $GLOBALS[babUrl]."index.php?tg=waiting&idx=ReadC&topics=".$this->topics."&article=".$this->article."&com=".$this->arr[id]."&newc=".$this->newc;
				if( empty($this->arr[subject]))
					$this->subjectname = "-oOo-";
				else
					$this->subjectname = $this->arr[subject];
				$i++;
				return true;
				}
			else
				return false;
			}
		}
	
	$temp = new temp($topics, $article, $newc);
	$body->babecho(	babPrintTemplate($temp,"comments.html", "commentslist"));
	return $temp->count;
	}

function readComment($topics, $article, $com)
	{
	global $body;
	
	class ctp
		{
		var $subject;
		var $add;
		var $topics;
		var $article;
		var $arr = array();

		function ctp($topics, $article, $com)
			{
			$this->subject = babTranslate("Subject");
			$this->by = babTranslate("By");
			$this->date = babTranslate("Date");
			$this->topics = $topics;
			$this->article = $article;
			$db = new db_mysql();
			$req = "select * from comments where id='$com'";
			$res = $db->db_query($req);
			$this->arr = $db->db_fetch_array($res);
			$this->arr[date] = bab_strftime(bab_mktime($this->arr[date]));
			}
		}

	$ctp = new ctp($topics, $article, $com);
	$body->babecho(	babPrintTemplate($ctp,"comments.html", "commentread"));
	}


function confirmComment($article, $topics, $com, $newc)
	{
	global $body;

	class temp
		{
		var $name;
		var $nameval;
		var $action;
		var $confirm;
		var $refuse;
		var $what;
		var $com;
		var $new;
		var $message;
		var $modify;
		var $topics;
		var $article;
		var $fullname;
		var $author;
		var $db;
		var $count;
		var $res;
		var $confval;
		var $idxval;

		function temp($topics, $article, $com, $newc)
			{
			$this->article = $article;
			$this->topics = $topics;
			$this->new = $newc;
			$this->com = $com;
			$this->name = babTranslate("Submiter");
			$this->modify = babTranslate("Update");
			$this->action = babTranslate("Action");
			$this->confirm = babTranslate("Confirm");
			$this->refuse = babTranslate("Refuse");
			$this->what = babTranslate("Send an email to author");
			$this->message = babTranslate("Message");
			$this->confval = "comment";
			$this->idxval = "WaitingC";

			$this->db = new db_mysql();
			$req = "select * from comments where id='$com'";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			if( $this->count > 0)
				{
				$arr = $this->db->db_fetch_array($this->res);
				$this->fullname = $arr[name];
				$this->author = $arr[name];
				}
			}
		}
	
	$temp = new temp($topics, $article, $com, $newc);
	$body->babecho(	babPrintTemplate($temp,"waiting.html", "confirmcomment"));
	}

function notifyArticleAuthor($subject, $msg, $title, $from, $to)
	{
	global $body, $BAB_SESS_USER, $BAB_SESS_EMAIL, $babAdminEmail, $babInstallPath;
    include $babInstallPath."utilit/mailincl.php";

	class tempa
		{
		var $message;
        var $from;
        var $author;
        var $about;
        var $title;
        var $titlename;
        var $site;
        var $sitename;
        var $date;
        var $dateval;


		function tempa($subject, $msg, $title, $from, $to)
			{
            global $BAB_SESS_USER, $BAB_SESS_EMAIL, $babSiteName;
            $this->about = babTranslate("About your article");
            $this->title = babTranslate("Title");
            $this->titlename = $title;
            $this->site = babTranslate("Web site");
            $this->sitename = $babSiteName;
            $this->date = babTranslate("Date");
            $this->dateval = bab_strftime(mktime());
            $this->message = $msg;
			}
		}
	
	$tempa = new tempa($subject, $msg, $title, $from, $to);
	$message = babPrintTemplate($tempa,"mailinfo.html", "confirmarticle");

    $mail = new babMail();
    $mail->mailTo($to);
    $mail->mailFrom($from, "Ovidentia Administrator");
    $mail->mailSubject($subject);
    $mail->mailBody($message, "html");
    $mail->send();
	}

function updateConfirmArticle($topics, $article, $action, $send, $author, $message, $homepage0, $homepage1)
	{
	global $body, $new;
	$db = new db_mysql();

	$query = "select * from articles where id='$article'";
	$res = $db->db_query($query);
	$arr = $db->db_fetch_array($res);
	$filename = $arr[filename];
	$title = $arr[title];

	$query = "select * from users where id='$author'";
	$res = $db->db_query($query);
	$arr = $db->db_fetch_array($res);

	$query = "select * from topics where id='$topics'";
	$res = $db->db_query($query);
	$arr2 = $db->db_fetch_array($res);

	$query = "select * from users where id='$arr2[id_approver]'";
	$res = $db->db_query($query);
	$arr2 = $db->db_fetch_array($res);

	if( $action == "1")
		{
		$query = "update articles set confirmed='Y' where id = '$article'";
		$subject = babTranslate("Your article has been accepted");
		$res = $db->db_query($query);

		$query = "select * from sites where name='".$GLOBALS[babSiteName]."'";
		$res = $db->db_query($query);
		if( $res && $db->db_num_rows($res) > 0)
			{
			$arr3 = $db->db_fetch_array($res);
			if( $homepage0 == "2")
				{
				$query = "insert into homepages (id_article, id_site, id_group) values ('" .$article. "', '" . $arr3[id]. "', '" . $homepage0. "')";
				$res = $db->db_query($query);
				}

			if( $homepage1 == "1")
				{
				$query = "insert into homepages (id_article, id_site, id_group) values ('" .$article. "', '" . $arr3[id]. "', '" . $homepage1. "')";
				$res = $db->db_query($query);
				}
			}

		}
	else
		{
		$query = "delete from articles where id = '$article'";
		$subject = babTranslate("Your article has been refused");
		$res = $db->db_query($query);
		}

	if( $send == "1")
		{
		$msg = nl2br($message);
		if(get_cfg_var("magic_quotes_gpc"))
			$msg = stripslashes($msg);
        notifyArticleAuthor($subject, $msg, $title, $arr2[email], $arr[email]);
		//mail ($arr[email],$subject,$title . "\n". $msg,"From: ".$arr2[email]);
		}

	$new--;
	if( $new < 1)
		Header("Location: index.php?tg=articles&topics=".$topics);
	}


function updateArticle($topics, $article, $title, $headtext, $bodytext)
	{
	global $body;

	if( empty($title))
		{
		$body->msgerror = babTranslate("ERROR: You must provide a title");
		return;
		}

	$db = new db_mysql();
	if(get_cfg_var("magic_quotes_gpc"))
		{
		$headtext = stripslashes($headtext);
		$bodytext = stripslashes($bodytext);
		}

	$headtext = addslashes($headtext);
	$bodytext = addslashes($bodytext);
	$db = new db_mysql();
	$req = "update articles set title='$title', head='$headtext', body='$bodytext' where id='$article'";
	$res = $db->db_query($req);		
	}

function notifyCommentAuthor($subject, $msg, $from, $to)
	{
	global $body, $BAB_SESS_USER, $BAB_SESS_EMAIL, $babAdminEmail, $babInstallPath;
    include $babInstallPath."utilit/mailincl.php";

	class tempa
		{
		var $message;
        var $from;
        var $author;
        var $about;
        var $site;
        var $sitename;
        var $date;
        var $dateval;


		function tempa($subject, $msg, $from, $to)
			{
            global $BAB_SESS_USER, $BAB_SESS_EMAIL, $babSiteName;
            $this->about = babTranslate("About your comment");
            $this->site = babTranslate("Web site");
            $this->sitename = $babSiteName;
            $this->date = babTranslate("Date");
            $this->dateval = bab_strftime(mktime());
            $this->message = $msg;
			}
		}
	
	$tempa = new tempa($subject, $msg, $from, $to);
	$message = babPrintTemplate($tempa,"mailinfo.html", "confirmcomment");

    $mail = new babMail();
    $mail->mailTo($to);
    $mail->mailFrom($from, "Ovidentia Administrator");
    $mail->mailSubject($subject);
    $mail->mailBody($message, "html");
    $mail->send();
	}

function updateConfirmComment($topics, $article, $action, $send, $author, $message, $com, $newc)
	{
	global $body, $new, $BAB_SESS_USER, $babAdminEmail;

	$db = new db_mysql();
	$query = "select * from comments where id='$com'";
	$res = $db->db_query($query);
	$arr = $db->db_fetch_array($res);

	if( $action == "1")
		{
		$query = "update comments set confirmed='Y' where id = '$com'";
		}
	else
		{
		$query = "delete from comments where id = '$com'";
		}
	$res = $db->db_query($query);

	if( $send == "1")
		{
		if( $action == "1")
			$subject = "Your comment has been accepted";
		else
			$subject = "Your comment has been refused";
		$msg = nl2br($message);
		if(get_cfg_var("magic_quotes_gpc"))
			$msg = stripslashes($msg);
		//mail ($arr[email], babTranslate("About your comment"), $msg,"From: ".$arr2[email]);
        notifyCommentAuthor($subject, $msg, empty($BAB_SESS_USER)? $babAdminEmail: $BAB_SESS_USER, $arr[email]);
		}

	$newc--;
	if( $newc < 1)
		Header("Location: index.php?tg=articles&topics=".$topics);
	}

/* main */
if(!isset($idx))
	{
	$idx = "Waiting";
	}

if( !isUserApprover($topics))
	return;

if( isset($modify))
	{
	updateArticle($topics, $article, $title, $headtext, $bodytext);
	}

if( isset($confirm) )
	{
	if($confirm == "article")
		updateConfirmArticle($topics, $article, $action, $send, $author, $message,$homepage0, $homepage1);
	if($confirm == "comment")
		updateConfirmComment($topics, $article, $action, $send, $author, $message, $comment, $new);
	}

switch($idx)
	{
	case "More":
		$body->title = getCategoryTitle($topics);
		readMore($topics, $article);
		$body->addItemMenu("Waiting", babTranslate("Waiting"), $GLOBALS[babUrl]."index.php?tg=waiting&idx=Waiting&topics=".$topics."&new=".$new);
		$body->addItemMenu("Modify", babTranslate("Modify"), $GLOBALS[babUrl]."index.php?tg=waiting&idx=Modify&topics=".$topics."&article=".$article."&new=".$new);
		$body->addItemMenu("Confirm", babTranslate("Confirm"), $GLOBALS[babUrl]."index.php?tg=waiting&idx=Confirm&topics=".$topics."&article=".$article."&new=".$new);
		break;

	case "Modify":
		$body->title = getArticleTitle($article);
		modifyArticle($topics, $article);
		$body->addItemMenu("Waiting", babTranslate("Waiting"), $GLOBALS[babUrl]."index.php?tg=waiting&idx=Waiting&topics=".$topics."&new=".$new);
		$body->addItemMenu("Modify", babTranslate("Modify"), $GLOBALS[babUrl]."index.php?tg=waiting&idx=Modify&topics=".$topics."&article=".$article."&new=".$new);
		$body->addItemMenu("Confirm", babTranslate("Confirm"), $GLOBALS[babUrl]."index.php?tg=waiting&idx=Confirm&topics=".$topics."&article=".$article."&new=".$new);
		break;

	case "Confirm":
		$body->title = getArticleTitle($article);
		confirmArticle($article, $topics);
		$body->addItemMenu("Waiting", babTranslate("Waiting"), $GLOBALS[babUrl]."index.php?tg=waiting&idx=Waiting&topics=".$topics."&new=".$new);
		$body->addItemMenu("Modify", babTranslate("Modify"), $GLOBALS[babUrl]."index.php?tg=waiting&idx=Modify&topics=".$topics."&article=".$article."&new=".$new);
		$body->addItemMenu("Confirm", babTranslate("Confirm"), $GLOBALS[babUrl]."index.php?tg=waiting&idx=Confirm&topics=".$topics."&article=".$article."&new=".$new);
		break;

	case "WaitingC":
		$body->title = babTranslate("Waiting comments");
		$body->addItemMenu("List", babTranslate("List"), $GLOBALS[babUrl]."index.php?tg=comments&idx=List&topics=".$topics."&article=".$article."&newc=".$newc);
		$body->addItemMenu("WaitingC", babTranslate("Waiting"), $GLOBALS[babUrl]."index.php?tg=waiting&idx=WaitingC&topics=".$topics."&article=".$article."&newc=".$newc);
		listWaitingComments($topics, $article, $newc);
		break;

	case "ReadC":
		$body->title = babTranslate("Waiting Comment");
		$body->addItemMenu("WaitingC", babTranslate("Waiting"), $GLOBALS[babUrl]."index.php?tg=waiting&idx=WaitingC&topics=".$topics."&article=".$article."&newc=".$newc);
		$body->addItemMenu("ConfirmC", babTranslate("Confirm"), $GLOBALS[babUrl]."index.php?tg=waiting&idx=ConfirmC&topics=".$topics."&article=".$article."&newc=".$newc."&com=".$com);
		readComment($topics, $article, $com);
		break;

	case "ConfirmC":
		$body->title = babTranslate("Confirm a comment");
		confirmComment($article, $topics, $com, $newc);
		$body->addItemMenu("WaitingC", babTranslate("Waiting"), $GLOBALS[babUrl]."index.php?tg=waiting&idx=WaitingC&topics=".$topics."&article=".$article."&newc=".$newc);
		$body->addItemMenu("ConfirmC", babTranslate("Confirm"), $GLOBALS[babUrl]."index.php?tg=waiting&idx=ConfirmC&topics=".$topics."&article=".$article."&newc=".$newc);
		break;

	default:
	case "Waiting":
		$body->title = getCategoryTitle($topics);
		$body->addItemMenu("Waiting", babTranslate("Waiting"), $GLOBALS[babUrl]."index.php?tg=waiting&idx=Waiting&topics=".$topics."&new=".$new);
		listArticles($topics);
		break;
	}
$body->setCurrentItemMenu($idx);

?>