<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
include_once "base.php";
include $babInstallPath."utilit/imgincl.php";

function bab_getCategoryTitle($id)
	{
	$db = $GLOBALS['babDB'];
	$query = "select * from ".BAB_TOPICS_TBL." where id='$id'";
	$res = $db->db_query($query);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		return $arr['category'];
		}
	else
		{
		return "";
		}
	}

function bab_getTopicCategoryTitle($id)
	{
	$db = $GLOBALS['babDB'];
	$query = "select * from ".BAB_TOPICS_CATEGORIES_TBL." where id='$id'";
	$res = $db->db_query($query);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		return $arr['title'];
		}
	else
		{
		return "";
		}
	}

function bab_getArticleTitle($article)
	{
	$db = $GLOBALS['babDB'];
	$query = "select * from ".BAB_ARTICLES_TBL." where id='$article'";
	$res = $db->db_query($query);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		return $arr['title'];
		}
	else
		{
		return "";
		}
	}

function bab_getArticleDate($article)
	{
	$db = $GLOBALS['babDB'];
	$query = "select * from ".BAB_ARTICLES_TBL." where id='$article'";
	$res = $db->db_query($query);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		return bab_strftime(bab_mktime($arr['date']));
		}
	else
		{
		return "";
		}
	}

function bab_getArticleAuthor($article)
	{
	$db = $GLOBALS['babDB'];
	$query = "select * from ".BAB_ARTICLES_TBL." where id='$article'";
	$res = $db->db_query($query);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		$query = "select * from ".BAB_USERS_TBL." where id='".$arr['id_author']."'";
		$res = $db->db_query($query);
		if( $res && $db->db_num_rows($res) > 0)
			{
			$arr = $db->db_fetch_array($res);
			return bab_composeUserName($arr['firstname'], $arr['lastname']);
			}
		else
			return bab_translate("Anonymous");
		}
	else
		{
		return bab_translate("Anonymous");
		}
	}

function bab_getCommentTitle($com)
	{
	$db = $GLOBALS['babDB'];
	$query = "select * from ".BAB_COMMENTS_TBL." where id='$com'";
	$res = $db->db_query($query);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		return $arr['subject'];
		}
	else
		{
		return "";
		}
	}

function bab_confirmDeleteTopic($id)
	{

	$db = $GLOBALS['babDB'];
	$req = "select * from ".BAB_ARTICLES_TBL." where id_topic='$id'";
	$res = $db->db_query($req);
	while( $arr = $db->db_fetch_array($res))
		{
		// delete article and comments
		bab_confirmDeleteArticle($arr['id']);
		}
	$req = "delete from ".BAB_TOPICSCOM_GROUPS_TBL." where id_object='$id'";
	$res = $db->db_query($req);
	
	$req = "delete from ".BAB_TOPICSSUB_GROUPS_TBL." where id_object='$id'";
	$res = $db->db_query($req);

	$req = "delete from ".BAB_TOPICSVIEW_GROUPS_TBL." where id_object='$id'";
	$res = $db->db_query($req);

	$req = "delete from ".BAB_TOPICS_TBL." where id='$id'";
	$res = $db->db_query($req);
	}

function bab_confirmDeleteArticle($article)
	{
	include_once $GLOBALS['babInstallPath']."utilit/afincl.php";
	// delete comments
	$db = $GLOBALS['babDB'];
	$req = "delete from ".BAB_COMMENTS_TBL." where id_article='".$article."'";
	$res = $db->db_query($req);

	$req = "delete from ".BAB_HOMEPAGES_TBL." where id_article='".$article."'";
	$res = $db->db_query($req);

	$arr = $db->db_fetch_array($db->db_query("select * from ".BAB_ARTICLES_TBL." where id='".$article."'"));
	deleteImagesArticle($arr['head'], $article);
	deleteImagesArticle($arr['body'], $article);
	
	if( $arr['idfai'] != 0 )
		deleteFlowInstance($arr['idfai']);
	// delete article
	$req = "delete from ".BAB_ARTICLES_TBL." where id='".$article."'";
	$res = $db->db_query($req);
	}

function bab_deleteComments($com)
	{
	$db = $GLOBALS['babDB'];
	$req = "select * from ".BAB_COMMENTS_TBL." where id_parent='".$com."'";
	$res = $db->db_query($req);
	if( $res && $db->db_num_rows($res))
		{
		while( $arr = $db->db_fetch_array($res))
			{
			bab_deleteComments($arr['id']);
			}
		}

	$arr = $db->db_fetch_array($db->db_query("select idfai from ".BAB_COMMENTS_TBL." where id='".$com."'"));
	if( $arr['idfai'] != 0)
		deleteFlowInstance($arr['idfai']);
	$req = "delete from ".BAB_COMMENTS_TBL." where id='".$com."'";
	$res = $db->db_query($req);	
	}

function notifyArticleHomePage($top, $title, $homepage0, $homepage1)
	{
	global $babBody, $BAB_SESS_USER, $BAB_SESS_EMAIL, $babAdminEmail, $babInstallPath;

	if(!class_exists("tempa"))
		{
		class tempa
			{
			var $articletitle;
			var $message;
			var $from;
			var $author;
			var $category;
			var $categoryname;
			var $title;
			var $site;
			var $sitename;
			var $date;
			var $dateval;


			function tempa($top, $title, $homepage0, $homepage1)
				{
				global $BAB_SESS_USER, $BAB_SESS_EMAIL, $babSiteName;
				$this->articletitle = $title;
				$this->message = bab_translate("A new article is proposed for home page(s)"). ": ";
				if( $homepage1 == "1" )
					$this->message .= bab_translate("Registered users");
				$this->message .= " - ";
				if( $homepage0 == "2" )
					$this->message .= bab_translate("Unregistered users");

				$this->from = bab_translate("Author");
				$this->category = bab_translate("Topic");
				$this->title = bab_translate("Title");
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
		}
    $mail = bab_mail();
	if( $mail == false )
		return;

	$db = $GLOBALS['babDB'];
	$sql = "select * from ".BAB_USERS_GROUPS_TBL." where id_group='3'";
	$result=$db->db_query($sql);
	if( $result && $db->db_num_rows($result) > 0 )
		{
		while( $arr = $db->db_fetch_array($result))
			{
			$sql = "select email, firstname, lastname from ".BAB_USERS_TBL." where id='".$arr['id_object']."'";
			$res=$db->db_query($sql);
			$r = $db->db_fetch_array($res);
			$mail->mailTo($r['email'], bab_composeUserName($r['firstname'] , $r['lastname']));
			}
		}

	$mail->mailFrom($babAdminEmail, bab_translate("Ovidentia Administrator"));
	$mail->mailSubject(bab_translate("New article for home page"));

	$tempa = new tempa($top, $title, $homepage0, $homepage1);
	$message = bab_printTemplate($tempa,"mailinfo.html", "articlehomepage");
	$mail->mailBody($message, "html");

	$message = bab_printTemplate($tempa,"mailinfo.html", "articlehomepagetxt");
	$mail->mailAltBody($message);

	$mail->send();
	}


function notifyArticleApprovers($id, $users)
	{
	global $babBody, $BAB_SESS_USER, $BAB_SESS_EMAIL, $babAdminEmail, $babInstallPath;
    include_once $babInstallPath."utilit/mailincl.php";

	if(!class_exists("tempa"))
		{
		class tempa
			{
			var $articletitle;
			var $message;
			var $from;
			var $author;
			var $category;
			var $categoryname;
			var $title;
			var $site;
			var $sitename;
			var $date;
			var $dateval;


			function tempa($id)
				{
				global $BAB_SESS_USER, $BAB_SESS_EMAIL, $babSiteName;
				$db = $GLOBALS['babDB'];
				$arr = $db->db_fetch_array($db->db_query("select * from ".BAB_ARTICLES_TBL." where id='".$id."'"));
				$this->articletitle = $arr['title'];
				$this->message = bab_translate("A new article is waiting for you");
				$this->from = bab_translate("Author");
				$this->category = bab_translate("Topic");
				$this->title = bab_translate("Title");
				$rr = $db->db_fetch_array($db->db_query("select category from ".BAB_TOPICS_TBL." where id='".$arr['id_topic']."'"));
				$this->categoryname = $rr['category'];
				$this->site = bab_translate("Web site");
				$this->sitename = $babSiteName;
				$this->date = bab_translate("Date");
				$this->dateval = bab_strftime(mktime());
				if( !empty($arr['id_author']) && $arr['id_author'] != 0)
					{
					$this->author = bab_getUserName($arr['id_author']);
					$this->authoremail = bab_getUserEmail($arr['id_author']);
					}
				else
					{
					$this->author = bab_translate("Unknown user");
					$this->authoremail = "";
					}
				}
			}
		}

	$mail = bab_mail();
	if( $mail == false )
		return;

	for( $i=0; $i < count($users); $i++)
		$mail->mailTo(bab_getUserEmail($users[$i]));
	$mail->mailFrom($babAdminEmail, bab_translate("Ovidentia Administrator"));
	$mail->mailSubject(bab_translate("New waiting article"));

	$tempa = new tempa($id);
	$message = bab_printTemplate($tempa,"mailinfo.html", "articlewait");
	$mail->mailBody($message, "html");

	$message = bab_printTemplate($tempa,"mailinfo.html", "articlewaittxt");
	$mail->mailAltBody($message);

	$mail->send();
	}

function notifyCommentApprovers($idcom, $nfusers)
	{
	global $babBody, $BAB_SESS_USER, $BAB_SESS_EMAIL, $babAdminEmail, $babInstallPath;
    include $babInstallPath."utilit/mailincl.php";

	if(!class_exists("tempa"))
		{
		class tempca
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

			function tempca($idcom)
				{
				global $BAB_SESS_USER, $BAB_SESS_EMAIL, $babSiteName;
				$db = $GLOBALS['babDB'];
				$arr = $db->db_fetch_array($db->db_query("select * from ".BAB_COMMENTS_TBL." where id='".$idcom."'"));

				$this->message = bab_translate("A new comment is waiting for you");
				$this->from = bab_translate("Author");
				$this->subject = bab_translate("Subject");
				$this->subjectname = $arr['subject'];
				$this->article = bab_translate("Article");
				$this->articlename = bab_getArticleTitle($arr['id_article']);
				$this->category = bab_translate("Topic");
				$this->categoryname = bab_getCategoryTitle($arr['id_topic']);
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

		for( $i=0; $i < count($nfusers); $i++)
			$mail->mailTo(bab_getUserEmail($nfusers[$i]));
		$mail->mailFrom($babAdminEmail, bab_translate("Ovidentia Administrator"));
		$mail->mailSubject(bab_translate("New waiting comment"));

		$tempa = new tempca($idcom);
		$message = bab_printTemplate($tempa,"mailinfo.html", "commentwait");
		$mail->mailBody($message, "html");

		$message = bab_printTemplate($tempa,"mailinfo.html", "commentwaittxt");
		$mail->mailAltBody($message);
		$mail->send();
		}
	}
?>