<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
include $babInstallPath."utilit/mailincl.php";
include $babInstallPath."utilit/topincl.php";

function listArticles($topics, $new)
	{
	global $babBody;

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
		var $modify;
		var $confirm;
		var $modifyurl;
		var $confirmurl;


		function temp($topics, $new)
			{
			$this->modify = bab_translate("Modify");
			$this->confirm = bab_translate("Confirm");
			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_ARTICLES_TBL." where id_topic='$topics' and confirmed='N'";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			$this->topics = $topics;
			$this->new = $new;
			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$this->arr = $this->db->db_fetch_array($this->res);
				$this->content = bab_replace($this->arr['head']);
				$this->modifyurl = $GLOBALS['babUrlScript']."?tg=waiting&idx=Modify&topics=".$this->topics."&article=".$this->arr['id'];
				$this->confirmurl = $GLOBALS['babUrlScript']."?tg=waiting&idx=Confirm&topics=".$this->topics."&article=".$this->arr['id'];
				$this->moreurl = $GLOBALS['babUrlScript']."?tg=waiting&idx=More&topics=".$this->topics."&article=".$this->arr['id'];
				if( $this->new > 0)
					{
					$this->moreurl .= "&new=".$this->new;
					$this->modifyurl .= "&new=".$this->new;
					$this->confirmurl .= "&new=".$this->new;
					}

				$this->morename = bab_translate("Read more")."...";
				$i++;
				return true;
				}
			else
				return false;
			}
		}
	
	$temp = new temp($topics, $new);
	$babBody->babecho(	bab_printTemplate($temp,"waiting.html", "introlist"));
	}

function readMore($topics, $article)
	{
	global $babBody;

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
			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_ARTICLES_TBL." where id='$article' and confirmed='N'";
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
				$this->content = bab_replace($this->arr['body']);
				$i++;
				return true;
				}
			else
				return false;
			}
		}
	
	$temp = new temp($topics, $article);
	$babBody->babecho(	bab_printTemplate($temp,"waiting.html", "readmore"));
	}

//##: warn this fucntion is duplicated in articles.php file 
function modifyArticle($topics, $article)
	{
	global $babBody;

	class temp
		{
	
		var $head;
		var $headval;
		var $babBody;
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
			$this->head = bab_translate("Head");
			$this->body = bab_translate("Body");
			$this->title = bab_translate("Title");
			$this->modify = bab_translate("Modify");
			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_ARTICLES_TBL." where id='$article' and confirmed='N'";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			if( $this->count > 0)
				{
				$this->arr = $this->db->db_fetch_array($this->res);
				$this->headval = htmlentities($this->arr['head']);
				$this->bodyval = htmlentities($this->arr['body']);
				$this->titleval = htmlentities($this->arr['title']);
				}
			if(( strtolower(bab_browserAgent()) == "msie") and (bab_browserOS() == "windows"))
				$this->msie = 1;
			else
				$this->msie = 0;	
			}
		}
	
	$temp = new temp($topics, $article);
	$babBody->babecho(	bab_printTemplate($temp,"waiting.html", "modifyarticle"));
	}

function confirmArticle($article, $topics)
	{
	global $babBody;

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
			$this->name = bab_translate("Author");
			$this->modify = bab_translate("Update");
			$this->action = bab_translate("Action");
			$this->confirm = bab_translate("Confirm");
			$this->refuse = bab_translate("Refuse");
			$this->homepage0 = bab_translate("Add to unregistered users home page");
			$this->homepage1 = bab_translate("Add to registered users home page");
			$this->what = bab_translate("Send an email to author");
			$this->message = bab_translate("Message");
			$this->confval = "article";
			$this->idxval = "Waiting";

			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_ARTICLES_TBL." where id='$article'";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			if( $this->count > 0)
				{
				$arr = $this->db->db_fetch_array($this->res);
				$req = "select * from ".BAB_USERS_TBL." where id='".$arr['id_author']."'";
				$this->res = $this->db->db_query($req);
				$arr2 = $this->db->db_fetch_array($this->res);
				$this->fullname = bab_composeUserName($arr2['firstname'], $arr2['lastname']);
				$this->author = $arr['id_author'];
				}
			}
		}
	
	$temp = new temp($topics, $article);
	$babBody->babecho(	bab_printTemplate($temp,"waiting.html", "confirmarticle"));
	}

function listWaitingComments($topics, $article, $newc)
	{
	global $babBody;

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
			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_COMMENTS_TBL." where id_article='$article' and confirmed='N'";
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
				$this->arr['date'] = bab_strftime(bab_mktime($this->arr['date']));
				$this->subjecturl = $GLOBALS['babUrlScript']."?tg=waiting&idx=ReadC&topics=".$this->topics."&article=".$this->article."&com=".$this->arr['id']."&newc=".$this->newc;
				if( empty($this->arr['subject']))
					$this->subjectname = "-oOo-";
				else
					$this->subjectname = $this->arr['subject'];
				$i++;
				return true;
				}
			else
				return false;
			}
		}
	
	$temp = new temp($topics, $article, $newc);
	$babBody->babecho(	bab_printTemplate($temp,"comments.html", "commentslist"));
	return $temp->count;
	}

function readComment($topics, $article, $com)
	{
	global $babBody;
	
	class ctp
		{
		var $subject;
		var $add;
		var $topics;
		var $article;
		var $arr = array();

		function ctp($topics, $article, $com)
			{
			$this->subject = bab_translate("Subject");
			$this->by = bab_translate("By");
			$this->date = bab_translate("Date");
			$this->topics = $topics;
			$this->article = $article;
			$db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_COMMENTS_TBL." where id='$com'";
			$res = $db->db_query($req);
			$this->arr = $db->db_fetch_array($res);
			$this->arr['date'] = bab_strftime(bab_mktime($this->arr['date']));
			}
		}

	$ctp = new ctp($topics, $article, $com);
	$babBody->babecho(	bab_printTemplate($ctp,"comments.html", "commentread"));
	}


function confirmComment($article, $topics, $com, $newc)
	{
	global $babBody;

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
			$this->name = bab_translate("Submiter");
			$this->modify = bab_translate("Update");
			$this->action = bab_translate("Action");
			$this->confirm = bab_translate("Confirm");
			$this->refuse = bab_translate("Refuse");
			$this->what = bab_translate("Send an email to author");
			$this->message = bab_translate("Message");
			$this->confval = "comment";
			$this->idxval = "WaitingC";

			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_COMMENTS_TBL." where id='$com'";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			if( $this->count > 0)
				{
				$arr = $this->db->db_fetch_array($this->res);
				$this->fullname = $arr['name'];
				$this->author = $arr['name'];
				}
			}
		}
	
	$temp = new temp($topics, $article, $com, $newc);
	$babBody->babecho(	bab_printTemplate($temp,"waiting.html", "confirmcomment"));
	}

function notifyArticleAuthor($subject, $msg, $title, $from, $to)
	{
	global $babBody, $BAB_SESS_USER, $BAB_SESS_EMAIL, $babAdminEmail, $babInstallPath;
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
            $this->about = bab_translate("About your article");
            $this->title = bab_translate("Title");
            $this->titlename = $title;
            $this->site = bab_translate("Web site");
            $this->sitename = $babSiteName;
            $this->date = bab_translate("Date");
            $this->dateval = bab_strftime(mktime());
            $this->message = $msg;
			}
		}
	
    $mail = bab_mail();
	if( $mail == false )
		return;

    $mail->mailTo($to);
    $mail->mailFrom($from, "Ovidentia Administrator");
    $mail->mailSubject($subject);

	$tempa = new tempa($subject, $msg, $title, $from, $to);
	$message = bab_printTemplate($tempa,"mailinfo.html", "confirmarticle");
    $mail->mailBody($message, "html");

	$message = bab_printTemplate($tempa,"mailinfo.html", "confirmarticletxt");
    $mail->mailAltBody($message);
	$mail->send();
	}

function updateConfirmArticle($topics, $article, $action, $send, $author, $message, $homepage0, $homepage1)
	{
	global $babBody, $new;
	$db = $GLOBALS['babDB'];

	$query = "select * from ".BAB_ARTICLES_TBL." where id='$article'";
	$res = $db->db_query($query);
	$arr = $db->db_fetch_array($res);
	$filename = $arr['filename'];
	$title = $arr['title'];

	$query = "select * from ".BAB_USERS_TBL." where id='$author'";
	$res = $db->db_query($query);
	$arr = $db->db_fetch_array($res);

	$query = "select * from ".BAB_TOPICS_TBL." where id='$topics'";
	$res = $db->db_query($query);
	$arr2 = $db->db_fetch_array($res);
	$topicname = $arr2['category'];

	$query = "select * from ".BAB_USERS_TBL." where id='".$arr2['id_approver']."'";
	$res = $db->db_query($query);
	$arr2 = $db->db_fetch_array($res);

	if( $action == "1")
		{
		$query = "update ".BAB_ARTICLES_TBL." set confirmed='Y' where id = '$article'";
		$subject = bab_translate("Your article has been accepted");
		$res = $db->db_query($query);

		$query = "select * from ".BAB_SITES_TBL." where name='".addslashes($GLOBALS['babSiteName'])."'";
		$res = $db->db_query($query);
		if( $res && $db->db_num_rows($res) > 0)
			{
			$arr3 = $db->db_fetch_array($res);
			if( $homepage0 == "2")
				{
				$query = "insert into ".BAB_HOMEPAGES_TBL." (id_article, id_site, id_group) values ('" .$article. "', '" . $arr3['id']. "', '" . $homepage0. "')";
				$res = $db->db_query($query);
				}

			if( $homepage1 == "1")
				{
				$query = "insert into ".BAB_HOMEPAGES_TBL." (id_article, id_site, id_group) values ('" .$article. "', '" . $arr3['id']. "', '" . $homepage1. "')";
				$res = $db->db_query($query);
				}

			if( $homepage0 == "2" || $homepage1 == "1" )
				{
				notifyArticleHomePage($topicname, $title, $homepage0, $homepage1);
				}
			}

		}
	else
		{
		bab_confirmDeleteArticle($article);
		$subject = bab_translate("Your article has been refused");
		}

	if( $send == "1")
		{
		$msg = nl2br($message);
		if( bab_isMagicQuotesGpcOn())
			$msg = stripslashes($msg);
        notifyArticleAuthor($subject, $msg, $title, $arr2['email'], $arr['email']);
		//mail ($arr['email'],$subject,$title . "\n". $msg,"From: ".$arr2['email']);
		}

	$new--;
	if( $new < 1)
		Header("Location: ". $GLOBALS['babUrlScript']."?tg=topman");
		//Header("Location: ". $GLOBALS['babUrlScript']."?tg=articles&topics=".$topics);
	}


function updateArticle($topics, $article, $title, $headtext, $bodytext)
	{
	global $babBody;

	if( empty($title))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a title");
		return;
		}

	$db = $GLOBALS['babDB'];
	if( bab_isMagicQuotesGpcOn())
		{
		$headtext = stripslashes($headtext);
		$bodytext = stripslashes($bodytext);
		$title = stripslashes($title);
		}

	$headtext = addslashes($headtext);
	$bodytext = addslashes($bodytext);
	$title = addslashes($title);
	$db = $GLOBALS['babDB'];
	$req = "update ".BAB_ARTICLES_TBL." set title='$title', head='$headtext', body='$bodytext', date=now() where id='$article'";
	$res = $db->db_query($req);		
	}

function notifyCommentAuthor($subject, $msg, $from, $to)
	{
	global $babBody, $BAB_SESS_USER, $BAB_SESS_EMAIL, $babAdminEmail, $babInstallPath;
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
            $this->about = bab_translate("About your comment");
            $this->site = bab_translate("Web site");
            $this->sitename = $babSiteName;
            $this->date = bab_translate("Date");
            $this->dateval = bab_strftime(mktime());
            $this->message = $msg;
			}
		}
	
    $mail = bab_mail();
	if( $mail == false )
		return;

	$mail->mailTo($to);
    $mail->mailFrom($from, "Ovidentia Administrator");
    $mail->mailSubject($subject);

	$tempa = new tempa($subject, $msg, $from, $to);
	$message = bab_printTemplate($tempa,"mailinfo.html", "confirmcomment");
    $mail->mailBody($message, "html");

	$message = bab_printTemplate($tempa,"mailinfo.html", "confirmcommenttxt");
    $mail->mailAltBody($message);

	$mail->send();
	}

function updateConfirmComment($topics, $article, $action, $send, $author, $message, $com, $newc)
	{
	global $babBody, $new, $BAB_SESS_USER, $babAdminEmail;

	$db = $GLOBALS['babDB'];
	$query = "select * from ".BAB_COMMENTS_TBL." where id='$com'";
	$res = $db->db_query($query);
	$arr = $db->db_fetch_array($res);

	if( $action == "1")
		{
		$query = "update ".BAB_COMMENTS_TBL." set confirmed='Y' where id = '$com'";
		}
	else
		{
		$query = "delete from ".BAB_COMMENTS_TBL." where id = '$com'";
		}
	$res = $db->db_query($query);

	if( $send == "1")
		{
		if( $action == "1")
			$subject = "Your comment has been accepted";
		else
			$subject = "Your comment has been refused";
		$msg = nl2br($message);
		if( bab_isMagicQuotesGpcOn())
			$msg = stripslashes($msg);
		//mail ($arr['email'], bab_translate("About your comment"), $msg,"From: ".$arr2['email']);
        notifyCommentAuthor($subject, $msg, empty($BAB_SESS_USER)? $babAdminEmail: $BAB_SESS_USER, $arr['email']);
		}

	$newc--;
	if( $newc < 1)
		Header("Location: ". $GLOBALS['babUrlScript']."?tg=topman");
	}

/* main */
if(!isset($idx))
	{
	$idx = "Waiting";
	}

if( !bab_isUserApprover($topics))
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
		$babBody->title = bab_getCategoryTitle($topics);
		readMore($topics, $article);
		$babBody->addItemMenu("Waiting", bab_translate("Waiting"), $GLOBALS['babUrlScript']."?tg=waiting&idx=Waiting&topics=".$topics."&new=".$new);
		$babBody->addItemMenu("Modify", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=waiting&idx=Modify&topics=".$topics."&article=".$article."&new=".$new);
		$babBody->addItemMenu("Confirm", bab_translate("Confirm"), $GLOBALS['babUrlScript']."?tg=waiting&idx=Confirm&topics=".$topics."&article=".$article."&new=".$new);
		break;

	case "Modify":
		$babBody->title = bab_getArticleTitle($article);
		modifyArticle($topics, $article);
		$babBody->addItemMenu("Waiting", bab_translate("Waiting"), $GLOBALS['babUrlScript']."?tg=waiting&idx=Waiting&topics=".$topics."&new=".$new);
		$babBody->addItemMenu("Modify", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=waiting&idx=Modify&topics=".$topics."&article=".$article."&new=".$new);
		$babBody->addItemMenu("Confirm", bab_translate("Confirm"), $GLOBALS['babUrlScript']."?tg=waiting&idx=Confirm&topics=".$topics."&article=".$article."&new=".$new);
		break;

	case "Confirm":
		$babBody->title = bab_getArticleTitle($article);
		confirmArticle($article, $topics);
		$babBody->addItemMenu("Waiting", bab_translate("Waiting"), $GLOBALS['babUrlScript']."?tg=waiting&idx=Waiting&topics=".$topics."&new=".$new);
		$babBody->addItemMenu("Modify", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=waiting&idx=Modify&topics=".$topics."&article=".$article."&new=".$new);
		$babBody->addItemMenu("Confirm", bab_translate("Confirm"), $GLOBALS['babUrlScript']."?tg=waiting&idx=Confirm&topics=".$topics."&article=".$article."&new=".$new);
		break;

	case "WaitingC":
		$babBody->title = bab_translate("Waiting comments");
		$babBody->addItemMenu("List", bab_translate("List"), $GLOBALS['babUrlScript']."?tg=comments&idx=List&topics=".$topics."&article=".$article."&newc=".$newc);
		$babBody->addItemMenu("WaitingC", bab_translate("Waiting"), $GLOBALS['babUrlScript']."?tg=waiting&idx=WaitingC&topics=".$topics."&article=".$article."&newc=".$newc);
		listWaitingComments($topics, $article, $newc);
		break;

	case "ReadC":
		$babBody->title = bab_translate("Waiting Comment");
		$babBody->addItemMenu("WaitingC", bab_translate("Waiting"), $GLOBALS['babUrlScript']."?tg=waiting&idx=WaitingC&topics=".$topics."&article=".$article."&newc=".$newc);
		readComment($topics, $article, $com);
		confirmComment($article, $topics, $com, $newc);
		break;

	default:
	case "Waiting":
		$babBody->title = bab_getCategoryTitle($topics);
		$babBody->addItemMenu("Waiting", bab_translate("Waiting"), $GLOBALS['babUrlScript']."?tg=waiting&idx=Waiting&topics=".$topics."&new=".$new);
		listArticles($topics, $new);
		break;
	}
$babBody->setCurrentItemMenu($idx);

?>