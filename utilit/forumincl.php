<?php
/************************************************************************
 * OVIDENTIA http://www.ovidentia.org                                   *
 ************************************************************************
 * Copyright (c) 2003 by CANTICO ( http://www.cantico.fr )              *
 *                                                                      *
 * This file is part of Ovidentia.                                      *
 *                                                                      *
 * Ovidentia is free software; you can redistribute it and/or modify    *
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

define("DELTA_TIME", 86400);

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

function notifyModerator($forum, $threadTitle, $author, $forumname, $url = '')
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


		function tempa($threadTitle, $author, $forumname, $url)
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
			$this->url = !empty($url) ? $GLOBALS['babUrlScript']."?tg=login&cmd=detect&referer=".urlencode($url) : false;
			}
		}
	
    $mail = bab_mail();
	if( $mail == false )
		return;

    $mail->mailFrom($babAdminEmail, $GLOBALS['babAdminName']);
    $mail->mailSubject(bab_translate("New post"));

	$tempa = new tempa($threadTitle, $author, $forumname, $url);
	$message = $mail->mailTemplate(bab_printTemplate($tempa,"mailinfo.html", "newpost"));
	$messagetxt = bab_printTemplate($tempa,"mailinfo.html", "newposttxt");

	$mail->mailBody($message, "html");
	$mail->mailAltBody($messagetxt);

	$db = $GLOBALS['babDB'];
	$res = $db->db_query("select id_group from ".BAB_FORUMSMAN_GROUPS_TBL." where id_object='".$forum."'");
	$arrusers = array();
	if( $res && $db->db_num_rows($res) > 0 )
		{
		while( $row = $db->db_fetch_array($res))
			{
			switch($row['id_group'])
				{
				case 0:
				case 1:
					$res2 = $db->db_query("select id, email, firstname, lastname from ".BAB_USERS_TBL." where is_confirmed='1' and disabled='0'");
					break;
				case 2:
					return;
				default:
					$res2 = $db->db_query("select ".BAB_USERS_TBL.".id, ".BAB_USERS_TBL.".email, ".BAB_USERS_TBL.".firstname, ".BAB_USERS_TBL.".lastname from ".BAB_USERS_TBL." join ".BAB_USERS_GROUPS_TBL." where is_confirmed='1' and disabled='0' and ".BAB_USERS_GROUPS_TBL.".id_group='".$row['id_group']."' and ".BAB_USERS_GROUPS_TBL.".id_object=".BAB_USERS_TBL.".id");
					break;
				}

			if( $res2 && $db->db_num_rows($res2) > 0 )
				{
				$count = 0;
				$mail->mailTo('');
				while(($arr = $db->db_fetch_array($res2)))
					{
					if( count($arrusers) == 0 || !in_array($arr['id'], $arrusers))
						{
						$arrusers[] = $arr['id'];
						$mail->mailBcc($arr['email'], bab_composeUserName($arr['firstname'],$arr['lastname']));
						$count++;
						}

					if( $count > 25 )
						{
						$mail->send();
						$mail->clearBcc();
						$mail->clearTo();
						$count = 0;
						}
					}

				if( $count > 0 )
					{
					$mail->send();
					$mail->clearBcc();
					$mail->clearTo();
					$count = 0;
					}
				}	
			}
		}
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
    $mail->mailFrom($babAdminEmail, $GLOBALS['babAdminName']);
    $mail->mailSubject(bab_translate("New post"));

	$tempb = new tempb($threadTitle, $email, $author);
	$message = $mail->mailTemplate(bab_printTemplate($tempb,"mailinfo.html", "newpost"));
    $mail->mailBody($message, "html");

	$message = bab_printTemplate($tempb,"mailinfo.html", "newposttxt");
    $mail->mailAltBody($message);

	$mail->send();
	}


function bab_uploadPostFiles($postid)
	{
	$baseurl = $GLOBALS['babUploadPath'].'/forums/';
	if (!is_dir($baseurl))
		{
		if (!@bab_mkdir($baseurl))
			{
			$GLOBALS['babBody']->msgerror = bab_translate("Can't create forums directory in").' '.$GLOBALS['babUploadPath'];
			return false;
			}
		}

	foreach ($_FILES as $file)
		{
		if( bab_isMagicQuotesGpcOn())
			{
			$file['name'] = stripslashes($file['name']);
			}
		if( isset($GLOBALS['babFileNameTranslation']))
			{
			$file['name'] = strtr($file['name'], $GLOBALS['babFileNameTranslation']);
			}
		move_uploaded_file($file['tmp_name'],$baseurl.$postid.','.$file['name']);
		}

	return true;
	}

function bab_getPostFiles($forum,$postid)
	{
	$out = array();
	$baseurl = $GLOBALS['babUploadPath'].'/forums/';
	if (is_dir($baseurl) && $h = opendir($baseurl))
		{
		while (false !== ($file = readdir($h))) 
			{
			if (substr($file,0,strpos($file,',')) == $postid)
				{
				$name = substr($file,strstr(',',$file)+2);
				$out[] = array(
						'url' => $GLOBALS['babUrlScript']."?tg=posts&idx=dlfile&forum=".$forum."&post=".$postid."&file=".urlencode($name),
						'path' => $baseurl.$file,
						'name' => $name,
						'size' => round(filesize($baseurl.$file)/1024).' '.bab_translate('Kb')
						);
				}
			}
		}
	return $out;
	}

?>