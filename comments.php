<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
include $babInstallPath."utilit/topincl.php";


function listComments($topics, $article, $newc)
	{
	global $body;

	class temp
		{
	
		var $subjecturl;
		var $subjectname;
		var $arr = array();
		var $db;
		var $count;
		var $res;
		var $topics;
		var $article;
		var $alternate;
		var $newc;

		function temp($topics, $article, $newc)
			{
			$this->db = new db_mysql();
			$req = "select * from comments where id_article='$article' and confirmed='Y'";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			$this->topics = $topics;
			$this->article = $article;
			$this->alternate = 0;
			$this->newc = $newc;
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
				$this->subjecturl = $GLOBALS['babUrl']."index.php?tg=comments&idx=read&topics=".$this->topics."&article=".$this->article."&com=".$this->arr['id']."&newc=".$this->newc;
				$this->subjectname = $this->arr['subject'];
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


function addComment($topics, $article, $subject, $com="")
	{
	global $body;
	
	class temp
		{
		var $subject;
		var $subjectval;
		var $name;
		var $email;
		var $message;
		var $add;
		var $topics;
		var $article;
		var $username;
		var $anonyme;
		var $title;
		var $titleval;
		var $com;
		var $msie;

		function temp($topics, $article, $subject, $com)
			{
			global $BAB_SESS_USER;
			$this->subject = babTranslate("Subject");
			$this->name = babTranslate("Name");
			$this->email = babTranslate("Email");
			$this->message = babTranslate("Message");
			$this->add = babTranslate("Add comment");
			$this->title = babTranslate("Article");
			$this->topics = $topics;
			$this->article = $article;
			$this->subjectval = $subject;
			$this->com = $com;
			if( empty($BAB_SESS_USER))
				$this->anonyme = 1;
			else
				{
				$this->anonyme = 0;
				$this->username = $BAB_SESS_USER;
				}
			$db = new db_mysql();
			$req = "select * from articles where id='$article'";
			$res = $db->db_query($req);
			$arr = $db->db_fetch_array($res);
			$this->titleval = $arr['title'];
			if(( strtolower(browserAgent()) == "msie") and (browserOS() == "windows"))
				$this->msie = 1;
			else
				$this->msie = 0;	
			}
		}

	$temp = new temp($topics, $article, $subject, $com);
	$tpl = new Template();
	$body->babecho(	babPrintTemplate($temp,"comments.html", "commentcreate"));
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
			$this->arr['date'] = bab_strftime(bab_mktime($this->arr['date']));
			}
		}

	$ctp = new ctp($topics, $article, $com);
	$body->babecho(	babPrintTemplate($ctp,"comments.html", "commentread"));
	addComment($topics, $article, "RE: ".$ctp->arr['subject'], $com);
	}


function deleteComment($topics, $article, $com, $newc)
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

		function temp($topics, $article, $com, $newc)
			{
			$this->message = babTranslate("Are you sure you want to delete this comment");
			$this->title = getCommentTitle($com);
			$this->warning = babTranslate("WARNING: This operation will delete the comment with all its replys"). "!";
			$this->urlyes = $GLOBALS['babUrl']."index.php?tg=comments&idx=List&topics=".$topics."&article=".$article."&newc=".$newc."&com=".$com."&action=Yes";
			$this->yes = babTranslate("Yes");
			$this->urlno = $GLOBALS['babUrl']."index.php?tg=comments&idx=List&topics=".$topics."&article=".$article."&newc=".$newc;
			$this->no = babTranslate("No");
			}
		}

	$temp = new temp($topics, $article, $com, $newc);
	$body->babecho(	babPrintTemplate($temp,"warning.html", "warningyesno"));
	}

function notifyApprover($top, $article, $title, $approveremail)
	{
	global $body, $BAB_SESS_USER, $BAB_SESS_EMAIL, $babAdminEmail, $babInstallPath;
    include $babInstallPath."utilit/mailincl.php";

	class tempa
		{
		var $article;
		var $articlename;
		var $message;
        var $from;
        var $author;
        var $category;
        var $categoryname;
        var $subject;
        var $subjectname;
        var $title;
        var $site;
        var $sitename;
        var $date;
        var $dateval;


		function tempa($top, $article, $title)
			{
            global $BAB_SESS_USER, $BAB_SESS_EMAIL, $babSiteName;
            $this->subjectname = $title;
            $this->message = babTranslate("A new comment is waiting for you");
            $this->from = babTranslate("Author");
            $this->subject = babTranslate("Subject");
            $this->subjectname = $title;
            $this->article = babTranslate("Article");
            $this->articlename = $article;
            $this->category = babTranslate("Topic");
            $this->categoryname = $top;
            $this->site = babTranslate("Web site");
            $this->sitename = $babSiteName;
            $this->date = babTranslate("Date");
            $this->dateval = bab_strftime(mktime());
            if( !empty($BAB_SESS_USER))
                $this->author = $BAB_SESS_USER;
            else
                $this->author = babTranslate("Unknown user");

            if( !empty($BAB_SESS_EMAIL))
                $this->authoremail = $BAB_SESS_EMAIL;
            else
                $this->authoremail = "";
			}
		}
	
	$tempa = new tempa($top, $article, $title);
	$message = babPrintTemplate($tempa,"mailinfo.html", "commentwait");

    $mail = new babMail();
    $mail->mailTo($approveremail);
    $mail->mailFrom($babAdminEmail, "Ovidentia Administrator");
    $mail->mailSubject(babTranslate("New waiting comment"));
    $mail->mailBody($message, "html");
    $mail->send();
	}


function saveComment($topics, $article, $name, $subject, $message, $com)
	{
	global $BAB_SESS_USER, $BAB_SESS_EMAIL;

	if( empty($com))
		$com = 0;
	$db = new db_mysql();
	$req = "insert into comments (id_topic, id_article, id_parent, date, subject, message, name, email) values ";
	$req .= "('" .$topics. "', '" . $article.  "', '" . $com. "', now(), '" . $subject. "', '" . $message. "', '";
	if( !isset($name) || empty($name))
		$req .= $BAB_SESS_USER. "', '" . $BAB_SESS_EMAIL. "')";
	else
		$req .= $name. "', '')";

	$res = $db->db_query($req);

	$req = "select * from topics where id='$topics'";
	$res = $db->db_query($req);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
        $top = $arr['category'];
		$req = "select * from users where id='".$arr['id_approver']."'";
		$res = $db->db_query($req);
		if( $res && $db->db_num_rows($res) > 0)
			{
			$arr2 = $db->db_fetch_array($res);
			$req = "select * from articles where id='$article'";
			$res = $db->db_query($req);
			$arr3 = $db->db_fetch_array($res);
			//$message = babTranslate("A new Comment is waiting for you on topic: \n  "). $arr['category'];
			//mail ($arr2['email'],'New waiting article',$message,"From: ".$babAdminEmail);
            notifyApprover($top, $arr3['title'], $subject, $arr2['email']);
			}
		}
	}

function confirmDeleteComment($topics, $article, $com)
	{
	// delete comments
	deleteComments($com);
	}

/* main */
$approver = isUserApprover($topics);

if(!isset($idx))
	{
	$idx = "List";
	}

if(isset($addcomment))
	{
	if( isset($name) && empty($name))
		$name = "Anonymous";
	saveComment($topics, $article, $name, $subject, $message, $com);
	}

if( isset($action) && $action == "Yes" && $approver)
	{
	confirmDeleteComment($topics, $article, $com);
	}

switch($idx)
	{

	case "addComment":
		$body->title = getArticleTitle($article);
		if( isAccessValid("topicscom_groups", $topics) || $approver)
			{
			addComment($topics, $article, "");
			$body->addItemMenu("List", babTranslate("List"), $GLOBALS['babUrl']."index.php?tg=comments&idx=List&topics=".$topics."&article=".$article."&newc=".$newc);
			$body->addItemMenu("addComment", babTranslate("Add Comment"), $GLOBALS['babUrl']."index.php?tg=comments&idx=addComment&topics=".$topics."&article=".$article."&newc=".$newc);
			$body->addItemMenu("Articles", babTranslate("Topic"), $GLOBALS['babUrl']."index.php?tg=articles&topics=".$topics."&newc=".$newc);
			}
		break;

	case "read":
		$body->title = getArticleTitle($article);
		if( isAccessValid("topicscom_groups", $topics) || $approver)
			{
			readComment($topics, $article, $com);
			$body->addItemMenu("List", babTranslate("List"), $GLOBALS['babUrl']."index.php?tg=comments&idx=List&topics=".$topics."&article=".$article."&newc=".$newc);
			$body->addItemMenu("addComment", babTranslate("Add Comment"), $GLOBALS['babUrl']."index.php?tg=comments&idx=addComment&topics=".$topics."&article=".$article."&newc=".$newc);
			if( $approver)
				{
				$body->addItemMenu("delete", babTranslate("Delete"), $GLOBALS['babUrl']."index.php?tg=comments&idx=delete&topics=".$topics."&article=".$article."&com=".$com."&newc=".$newc);
				}
			$body->addItemMenu("Articles", babTranslate("Topic"), $GLOBALS['babUrl']."index.php?tg=articles&topics=".$topics."&newc=".$newc);
			}
		break;

	case "delete":
		$body->title = babTranslate("Delete Comment");
		if( $approver)
			{
			deleteComment($topics, $article, $com, $newc);
			$body->addItemMenu("delete", babTranslate("Delete"), $GLOBALS['babUrl']."index.php?tg=comments&idx=delete&topics=".$topics."&article=".$article."&com=".$com);
			}
		break;

	default:
	case "List":
		$body->title = babTranslate("List of comments");
		if( isAccessValid("topicscom_groups", $topics) || $approver)
			{
			$count = listComments($topics, $article, $newc);
			$body->addItemMenu("List", babTranslate("List"), $GLOBALS['babUrl']."index.php?tg=comments&idx=List&topics=".$topics."&article=".$article."&newc=".$newc);
			$body->addItemMenu("AddComment", babTranslate("Add Comment"), $GLOBALS['babUrl']."index.php?tg=comments&idx=addComment&topics=".$topics."&article=".$article."&newc=".$newc);
			if( isset($newc) && $newc > 0)
				$body->addItemMenu("Waiting", babTranslate("Waiting"), $GLOBALS['babUrl']."index.php?tg=waiting&idx=WaitingC&topics=".$topics."&article=".$article."&newc=".$newc);				
			if( $count < 1)
				$body->title = babTranslate("Today, there is no comment on this article");
			else
				$body->title = getArticleTitle($article);
			$body->addItemMenu("Articles", babTranslate("Articles"), $GLOBALS['babUrl']."index.php?tg=articles&topics=".$topics."&newc=".$newc);
			}
		break;
	}
$body->setCurrentItemMenu($idx);

?>