<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
include $babInstallPath."utilit/topincl.php";


function listComments($topics, $article, $newc)
	{
	global $babBody;

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
			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_COMMENTS_TBL." where id_article='$article' and confirmed='Y'";
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
				$this->subjecturl = $GLOBALS['babUrlScript']."?tg=comments&idx=read&topics=".$this->topics."&article=".$this->article."&com=".$this->arr['id']."&newc=".$this->newc;
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


function addComment($topics, $article, $subject, $com="")
	{
	global $babBody;
	
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
			$this->subject = bab_translate("Subject");
			$this->name = bab_translate("Name");
			$this->email = bab_translate("Email");
			$this->message = bab_translate("Message");
			$this->add = bab_translate("Add comment");
			$this->title = bab_translate("Article");
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
			$db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_ARTICLES_TBL." where id='$article'";
			$res = $db->db_query($req);
			$arr = $db->db_fetch_array($res);
			$this->titleval = $arr['title'];
			if(( strtolower(bab_browserAgent()) == "msie") and (bab_browserOS() == "windows"))
				$this->msie = 1;
			else
				$this->msie = 0;	
			}
		}

	$temp = new temp($topics, $article, $subject, $com);
	$tpl = new babTemplate();
	$babBody->babecho(	bab_printTemplate($temp,"comments.html", "commentcreate"));
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
	addComment($topics, $article, "RE: ".$ctp->arr['subject'], $com);
	}


function deleteComment($topics, $article, $com, $newc)
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

		function temp($topics, $article, $com, $newc)
			{
			$this->message = bab_translate("Are you sure you want to delete this comment");
			$this->title = bab_getCommentTitle($com);
			$this->warning = bab_translate("WARNING: This operation will delete the comment with all its replys"). "!";
			$this->urlyes = $GLOBALS['babUrlScript']."?tg=comments&idx=List&topics=".$topics."&article=".$article."&newc=".$newc."&com=".$com."&action=Yes";
			$this->yes = bab_translate("Yes");
			$this->urlno = $GLOBALS['babUrlScript']."?tg=comments&idx=List&topics=".$topics."&article=".$article."&newc=".$newc;
			$this->no = bab_translate("No");
			}
		}

	$temp = new temp($topics, $article, $com, $newc);
	$babBody->babecho(	bab_printTemplate($temp,"warning.html", "warningyesno"));
	}

function notifyApprover($top, $article, $title, $approveremail)
	{
	global $babBody, $BAB_SESS_USER, $BAB_SESS_EMAIL, $babAdminEmail, $babInstallPath;
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
            $this->message = bab_translate("A new comment is waiting for you");
            $this->from = bab_translate("Author");
            $this->subject = bab_translate("Subject");
            $this->subjectname = $title;
            $this->article = bab_translate("Article");
            $this->articlename = $article;
            $this->category = bab_translate("Topic");
            $this->categoryname = $top;
            $this->site = bab_translate("Web site");
            $this->sitename = $babSiteName;
            $this->date = bab_translate("Date");
            $this->dateval = bab_strftime(mktime());
            if( !empty($BAB_SESS_USER))
                $this->author = $BAB_SESS_USER;
            else
                $this->author = bab_translate("Unknown user");

            if( !empty($BAB_SESS_EMAIL))
                $this->authoremail = $BAB_SESS_EMAIL;
            else
                $this->authoremail = "";
			}
		}
	
	$tempa = new tempa($top, $article, $title);
	$message = bab_printTemplate($tempa,"mailinfo.html", "commentwait");

    $mail = new babMail();
    $mail->mailTo($approveremail);
    $mail->mailFrom($babAdminEmail, "Ovidentia Administrator");
    $mail->mailSubject(bab_translate("New waiting comment"));
    $mail->mailBody($message, "html");
    $mail->send();
	}


function saveComment($topics, $article, $name, $subject, $message, $com)
	{
	global $BAB_SESS_USER, $BAB_SESS_EMAIL;

	if( empty($com))
		$com = 0;
	$db = $GLOBALS['babDB'];
	$req = "insert into ".BAB_COMMENTS_TBL." (id_topic, id_article, id_parent, date, subject, message, name, email) values ";
	$req .= "('" .$topics. "', '" . $article.  "', '" . $com. "', now(), '" . $subject. "', '" . $message. "', '";
	if( !isset($name) || empty($name))
		$req .= $BAB_SESS_USER. "', '" . $BAB_SESS_EMAIL. "')";
	else
		$req .= $name. "', '')";

	$res = $db->db_query($req);

	$req = "select * from ".BAB_TOPICS_TBL." where id='$topics'";
	$res = $db->db_query($req);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
        $top = $arr['category'];
		$req = "select * from ".BAB_USERS_TBL." where id='".$arr['id_approver']."'";
		$res = $db->db_query($req);
		if( $res && $db->db_num_rows($res) > 0)
			{
			$arr2 = $db->db_fetch_array($res);
			$req = "select * from ".BAB_ARTICLES_TBL." where id='$article'";
			$res = $db->db_query($req);
			$arr3 = $db->db_fetch_array($res);
			//$message = bab_translate("A new Comment is waiting for you on topic: \n  "). $arr['category'];
			//mail ($arr2['email'],'New waiting article',$message,"From: ".$babAdminEmail);
            notifyApprover($top, $arr3['title'], $subject, $arr2['email']);
			}
		}
	}

function confirmDeleteComment($topics, $article, $com)
	{
	// delete comments
	bab_deleteComments($com);
	}

/* main */
$approver = bab_isUserApprover($topics);

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
		$babBody->title = bab_getArticleTitle($article);
		if( bab_isAccessValid(BAB_TOPICSCOM_GROUPS_TBL, $topics) || $approver)
			{
			addComment($topics, $article, "");
			$babBody->addItemMenu("List", bab_translate("List"), $GLOBALS['babUrlScript']."?tg=comments&idx=List&topics=".$topics."&article=".$article."&newc=".$newc);
			$babBody->addItemMenu("addComment", bab_translate("Add Comment"), $GLOBALS['babUrlScript']."?tg=comments&idx=addComment&topics=".$topics."&article=".$article."&newc=".$newc);
			$babBody->addItemMenu("Articles", bab_translate("Articles"), $GLOBALS['babUrlScript']."?tg=articles&topics=".$topics."&newc=".$newc);
			}
		break;

	case "read":
		$babBody->title = bab_getArticleTitle($article);
		if( bab_isAccessValid(BAB_TOPICSCOM_GROUPS_TBL, $topics) || $approver)
			{
			readComment($topics, $article, $com);
			$babBody->addItemMenu("List", bab_translate("List"), $GLOBALS['babUrlScript']."?tg=comments&idx=List&topics=".$topics."&article=".$article."&newc=".$newc);
			$babBody->addItemMenu("addComment", bab_translate("Add Comment"), $GLOBALS['babUrlScript']."?tg=comments&idx=addComment&topics=".$topics."&article=".$article."&newc=".$newc);
			if( $approver)
				{
				$babBody->addItemMenu("delete", bab_translate("Delete"), $GLOBALS['babUrlScript']."?tg=comments&idx=delete&topics=".$topics."&article=".$article."&com=".$com."&newc=".$newc);
				}
			$babBody->addItemMenu("Articles", bab_translate("Articles"), $GLOBALS['babUrlScript']."?tg=articles&topics=".$topics."&newc=".$newc);
			}
		break;

	case "delete":
		$babBody->title = bab_translate("Delete Comment");
		if( $approver)
			{
			deleteComment($topics, $article, $com, $newc);
			$babBody->addItemMenu("delete", bab_translate("Delete"), $GLOBALS['babUrlScript']."?tg=comments&idx=delete&topics=".$topics."&article=".$article."&com=".$com);
			}
		break;

	default:
	case "List":
		$babBody->title = bab_translate("List of comments");
		if( bab_isAccessValid(BAB_TOPICSCOM_GROUPS_TBL, $topics) || $approver)
			{
			$count = listComments($topics, $article, $newc);
			$babBody->addItemMenu("List", bab_translate("List"), $GLOBALS['babUrlScript']."?tg=comments&idx=List&topics=".$topics."&article=".$article."&newc=".$newc);
			$babBody->addItemMenu("AddComment", bab_translate("Add Comment"), $GLOBALS['babUrlScript']."?tg=comments&idx=addComment&topics=".$topics."&article=".$article."&newc=".$newc);
			if( isset($newc) && $newc > 0)
				$babBody->addItemMenu("Waiting", bab_translate("Waiting"), $GLOBALS['babUrlScript']."?tg=waiting&idx=WaitingC&topics=".$topics."&article=".$article."&newc=".$newc);				
			if( $count < 1)
				$babBody->title = bab_translate("Today, there is no comment on this article");
			else
				$babBody->title = bab_getArticleTitle($article);
			$babBody->addItemMenu("Articles", bab_translate("Articles"), $GLOBALS['babUrlScript']."?tg=articles&topics=".$topics."&newc=".$newc);
			}
		break;
	}
$babBody->setCurrentItemMenu($idx);

?>