<?php
/************************************************************************
 * Ovidentia                                                            *
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ************************************************************************
 * This program is free software; you can redistribute it and/or modify *
 * it under the terms of the GNU General Public License as published by *
 * the Free Software Foundation; either version 2, or (at your option)  *
 * any later version.													*
 *																		*
 * This program is distributed in the hope that it will be useful, but  *
 * WITHOUT ANY WARRANTY; without even the implied warranty of			*
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.					*
 * See the  GNU General Public License for more details.				*
 *																		*
 * You should have received a copy of the GNU General Public License	*
 * along with this program; if not, write to the Free Software			*
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307,*
 * USA.																	*
************************************************************************/
include_once "base.php";
include $babInstallPath."utilit/topincl.php";


function listComments($topics, $article)
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

		function temp($topics, $article)
			{
			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_COMMENTS_TBL." where id_article='".$article."' and confirmed='Y'";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			$this->topics = $topics;
			$this->article = $article;
			$this->alternate = 0;
			$res = $this->db->db_query("select count(*) from ".BAB_ARTICLES_TBL." where id_topic='".$topics."' and archive='Y'");
			list($this->nbarch) = $this->db->db_fetch_row($res);
			$res = $this->db->db_query("select archive from ".BAB_ARTICLES_TBL." where id_topic='".$topics."' and id='".$article."'");
			list($this->barch) = $this->db->db_fetch_row($res);
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
				$this->subjecturl = $GLOBALS['babUrlScript']."?tg=comments&idx=read&topics=".$this->topics."&article=".$this->article."&com=".$this->arr['id'];
				$this->subjectname = $this->arr['subject'];
				$i++;
				return true;
				}
			else
				return false;
			}
		}
	
	$temp = new temp($topics, $article);
	$babBody->babecho(	bab_printTemplate($temp,"comments.html", "commentslist"));
	return array('count' => $temp->count, 'nbarch' => $temp->nbarch,'barch' => $temp->barch);
	}


function addComment($topics, $article, $subject, $message, $com="")
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
		var $urlsee;
		var $see;

		function temp($topics, $article, $subject, $message, $com)
			{
			global $BAB_SESS_USER;
			$this->subject = bab_translate("comments-Title");
			$this->name = bab_translate("Name");
			$this->email = bab_translate("Email");
			$this->message = bab_translate("comments-Comment");
			$this->add = bab_translate("Add comment");
			$this->title = bab_translate("Article");
			$this->see = bab_translate("Read article");
			$this->topics = $topics;
			$this->article = $article;
			$this->subjectval = $subject;
			$this->messageval = $message;
			$this->com = $com;
			if( empty($BAB_SESS_USER))
				$this->anonyme = 1;
			else
				{
				$this->anonyme = 0;
				$this->username = $BAB_SESS_USER;
				}
			$db = $GLOBALS['babDB'];
			$req = "select title from ".BAB_ARTICLES_TBL." where id='".$article."'";
			$res = $db->db_query($req);
			$arr = $db->db_fetch_array($res);
			$this->titleval = $arr['title'];
			if(( strtolower(bab_browserAgent()) == "msie") and (bab_browserOS() == "windows"))
				$this->msie = 1;
			else
				$this->msie = 0;
			$this->urlsee = $GLOBALS['babUrlScript']."?tg=topman&idx=viewa&item=".$article;
			$res = $db->db_query("select count(*) from ".BAB_ARTICLES_TBL." where id_topic='".$topics."' and archive='Y'");
			list($this->nbarch) = $db->db_fetch_row($res);
			$arr = $db->db_fetch_array($db->db_query("select mod_com from ".BAB_TOPICS_TBL." where id='".$topics."'"));
			if( $arr['mod_com'] == "Y" )
				$this->notcom = bab_translate("Note: for this topic, comments are moderate");
			else
				$this->notcom = "";
			}
		}

	$temp = new temp($topics, $article, $subject, $message, $com);
	$babBody->babecho(	bab_printTemplate($temp,"comments.html", "commentcreate"));
	return $temp->nbarch;
	}

function readComment($topics, $article, $subject, $message, $com)
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
			$req = "select * from ".BAB_COMMENTS_TBL." where id='".$com."'";
			$res = $db->db_query($req);
			$this->arr = $db->db_fetch_array($res);
			$this->arr['date'] = bab_strftime(bab_mktime($this->arr['date']));
			$res = $db->db_query("select archive from ".BAB_ARTICLES_TBL." where id_topic='".$topics."' and id='".$article."'");
			list($this->barch) = $db->db_fetch_row($res);
			}
		}

	$ctp = new ctp($topics, $article, $com);
	$babBody->babecho(	bab_printTemplate($ctp,"comments.html", "commentread"));
	if( $ctp->barch == "N" )
		{
		if( empty($subject))
			$subject = "RE: ".$ctp->arr['subject'];
		addComment($topics, $article, $subject, $message, $com);
		}
	return $ctp->barch;
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
			$this->urlyes = $GLOBALS['babUrlScript']."?tg=comments&idx=List&topics=".$topics."&article=".$article."&com=".$com."&action=Yes";
			$this->yes = bab_translate("Yes");
			$this->urlno = $GLOBALS['babUrlScript']."?tg=comments&idx=List&topics=".$topics."&article=".$article;
			$this->no = bab_translate("No");
			}
		}

	$temp = new temp($topics, $article, $com, $newc);
	$babBody->babecho(	bab_printTemplate($temp,"warning.html", "warningyesno"));
	}

function notifyApprover($top, $article, $title, $approveremail, $modcom)
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


		function tempa($top, $article, $title, $modcom)
			{
            global $BAB_SESS_USER, $BAB_SESS_EMAIL, $babSiteName;
            $this->subjectname = $title;
			if( $modcom == "Y")
				$this->message = bab_translate("A new comment is waiting for you");
			else
				$this->message = bab_translate("A new comment has been added");
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
	
    $mail = bab_mail();
	if( $mail == false )
		return;

    $mail->mailTo($approveremail);
    $mail->mailFrom($babAdminEmail, bab_translate("Ovidentia Administrator"));
    $mail->mailSubject(bab_translate("New waiting comment"));

	$tempa = new tempa($top, $article, $title, $modcom);
	$message = bab_printTemplate($tempa,"mailinfo.html", "commentwait");
    $mail->mailBody($message, "html");

	$message = bab_printTemplate($tempa,"mailinfo.html", "commentwaittxt");
    $mail->mailAltBody($message);
	$mail->send();
	}


function saveComment($topics, $article, $name, $subject, $message, $com)
	{
	global $babBody, $BAB_SESS_USER, $BAB_SESS_EMAIL;

	if( empty($subject))
		{
		$babBody->msgerror = bab_translate("comments - ERROR: You must provide a title");
		return false;
		}

	if( empty($message))
		{
		$babBody->msgerror = bab_translate("comments - ERROR: You must provide a comment");
		return false;
		}

	if( !bab_isMagicQuotesGpcOn())
		{
		$subject = addslashes($subject);
		$message = addslashes($message);
		}

	if( empty($com))
		$com = 0;
	$db = $GLOBALS['babDB'];
	$req = "insert into ".BAB_COMMENTS_TBL." (id_topic, id_article, id_parent, date, subject, message, name, email) values ";
	$req .= "('" .$topics. "', '" . $article.  "', '" . $com. "', now(), '" . $subject. "', '" . $message. "', '";
	if( !isset($name) || empty($name))
		$req .= $BAB_SESS_USER. "', '" . $BAB_SESS_EMAIL. "')";
	else
		$req .= $name. "', '')";

	$db->db_query($req);
	$id = $db->db_insert_id();

	$req = "select * from ".BAB_TOPICS_TBL." where id='".$topics."'";
	$res = $db->db_query($req);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		if( $arr['mod_com'] == "N" )
			{
			$db->db_query("update ".BAB_COMMENTS_TBL." set confirmed='Y' where id='".$id."'");
			}
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
            notifyApprover($top, $arr3['title'], $subject, $arr2['email'], $arr['mod_com']);
			}
		}
	return true;
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
	if( !saveComment($topics, $article, $cname, $subject, $message, $com))
		{
		if( empty($com))
			$idx = "addComment";
		else
			$idx = "read";
		}
	}

if( isset($action) && $action == "Yes" && $approver)
	{
	confirmDeleteComment($topics, $article, $com);
	}

if( $approver )
	{
	$db = $GLOBALS['babDB'];
	$req = "select ".BAB_COMMENTS_TBL.".id from ".BAB_COMMENTS_TBL." where id_article='".$article."' and confirmed='N'";
	$res = $db->db_query($req);			
	$new = $db->db_num_rows($res);
	}

$babLevelTwo = bab_getCategoryTitle($topics);
$arr = $babDB->db_fetch_array($babDB->db_query("select id_cat from ".BAB_TOPICS_TBL." where id='".$topics."'"));
$babLevelOne = bab_getTopicCategoryTitle($arr['id_cat']);

switch($idx)
	{

	case "addComment":
		$babBody->title = bab_getArticleTitle($article);
		if( bab_isAccessValid(BAB_TOPICSCOM_GROUPS_TBL, $topics) || $approver)
			{
			if(!isset($subject))
				$subject = "";
			if(!isset($message))
				$message = "";
			if(!isset($com))
				$com = "";
			$nbarch = addComment($topics, $article, $subject, $message, $com);
			$babBody->addItemMenu("List", bab_translate("Comments"), $GLOBALS['babUrlScript']."?tg=comments&idx=List&topics=".$topics."&article=".$article);
			$babBody->addItemMenu("addComment", bab_translate("Add Comment"), $GLOBALS['babUrlScript']."?tg=comments&idx=addComment&topics=".$topics."&article=".$article);
			$babBody->addItemMenu("Articles", bab_translate("Articles"), $GLOBALS['babUrlScript']."?tg=articles&topics=".$topics);
			if( $nbarch > 0 )
				$babBody->addItemMenu("larch", bab_translate("Archives"), $GLOBALS['babUrlScript']."?tg=articles&idx=larch&topics=".$topics);
			}
		break;

	case "read":
		$babBody->title = bab_getArticleTitle($article);
		if( bab_isAccessValid(BAB_TOPICSCOM_GROUPS_TBL, $topics) || $approver)
			{
			if(!isset($subject))
				$subject = "";
			if(!isset($message))
				$message = "";
			$barch = readComment($topics, $article, $subject, $message, $com);
			$babBody->addItemMenu("List", bab_translate("Comments"), $GLOBALS['babUrlScript']."?tg=comments&idx=List&topics=".$topics."&article=".$article);
			if( $barch == "N" )
				$babBody->addItemMenu("addComment", bab_translate("Add Comment"), $GLOBALS['babUrlScript']."?tg=comments&idx=addComment&topics=".$topics."&article=".$article);
			if( $approver)
				{
				$babBody->addItemMenu("delete", bab_translate("Delete"), $GLOBALS['babUrlScript']."?tg=comments&idx=delete&topics=".$topics."&article=".$article."&com=".$com);
				}
			$babBody->addItemMenu("Articles", bab_translate("Articles"), $GLOBALS['babUrlScript']."?tg=articles&topics=".$topics);
			if( $barch == "Y" )
				$babBody->addItemMenu("larch", bab_translate("Archives"), $GLOBALS['babUrlScript']."?tg=articles&idx=larch&topics=".$topics);
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
			$arr = listComments($topics, $article);
			$babBody->addItemMenu("List", bab_translate("Comments"), $GLOBALS['babUrlScript']."?tg=comments&idx=List&topics=".$topics."&article=".$article);
			if( $arr['barch'] == "N")
				$babBody->addItemMenu("AddComment", bab_translate("Add Comment"), $GLOBALS['babUrlScript']."?tg=comments&idx=addComment&topics=".$topics."&article=".$article);
			if( $approver && isset($new) && $new > 0)
				$babBody->addItemMenu("Waiting", bab_translate("Waiting"), $GLOBALS['babUrlScript']."?tg=waiting&idx=WaitingC&topics=".$topics."&article=".$article);				
			if( $arr['count'] < 1)
				$babBody->title = bab_translate("Today, there is no comment on this article");
			else
				$babBody->title = bab_getArticleTitle($article);
			$babBody->addItemMenu("Articles", bab_translate("Articles"), $GLOBALS['babUrlScript']."?tg=articles&topics=".$topics);
			if( $arr['nbarch'] > 0 )
				$babBody->addItemMenu("larch", bab_translate("Archives"), $GLOBALS['babUrlScript']."?tg=articles&idx=larch&topics=".$topics);
			}
		break;
	}
$babBody->setCurrentItemMenu($idx);

?>
