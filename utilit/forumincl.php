<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
include_once "base.php";

function bab_getForumName($id)
	{
	$db = $GLOBALS['babDB'];
	$query = "select name from ".BAB_FORUMS_TBL." where id='".$id."'";
	$res = $db->db_query($query);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		return $arr['name'];
		}
	else
		{
		return "";
		}
	}

function bab_isForumModerated($forum)
	{
	$db = $GLOBALS['babDB'];
	$query = "select moderation from ".BAB_FORUMS_TBL." where id='".$forum."'";
	$res = $db->db_query($query);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		if( $arr['moderation'] == "Y")
			return true;
		else
			return false;
		}
	return false;
	}

function bab_isForumThreadOpen($forum, $thread)
	{
	$db = $GLOBALS['babDB'];
	$query = "select active from ".BAB_THREADS_TBL." where id='".$thread."' and forum='".$forum."'";
	$res = $db->db_query($query);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		if( $arr['active'] == "Y")
			return true;
		else
			return false;
		}
	return false;
	}

function bab_getForumThreadTitle($id)
	{
	$db = $GLOBALS['babDB'];
	$query = "select post from ".BAB_THREADS_TBL." where id='".$id."'";
	$res = $db->db_query($query);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		$query = "select subject from ".BAB_POSTS_TBL." where id='".$arr['post']."'";
		$res = $db->db_query($query);
		if( $res && $db->db_num_rows($res) > 0)
			{
			$arr = $db->db_fetch_array($res);
			return $arr['subject'];
			}
		return "";
		}
	else
		{
		return "";
		}
	}

function bab_isUserForumModerator($forum, $id)
	{
	if( empty($forum) || empty($id))
		return false;

	$db = $GLOBALS['babDB'];
	$query = "select id from ".BAB_FORUMS_TBL." where id='$forum' and moderator='$id'";
	$res = $db->db_query($query);
	if( $res && $db->db_num_rows($res) > 0)
		{
		return true;
		}
	return false;
	}

function notifyModerator($threadTitle, $email, $author, $forumname)
	{
	global $babBody, $BAB_SESS_USER, $BAB_SESS_EMAIL, $babAdminEmail, $babInstallPath;
 
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


		function tempa($threadTitle, $email, $author, $forumname)
			{
            global $BAB_SESS_USER, $BAB_SESS_EMAIL, $babSiteName;
            $this->message = bab_translate("A new post has been registered on forum") .": ".$forumname;
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

	$tempa = new tempa($threadTitle, $email, $author, $forumname);
	$message = bab_printTemplate($tempa,"mailinfo.html", "newpost");
    $mail->mailBody($message, "html");

	$message = bab_printTemplate($tempa,"mailinfo.html", "newposttxt");
    $mail->mailAltBody($message);

	$mail->send();
	}
?>