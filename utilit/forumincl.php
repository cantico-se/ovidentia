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

function notifyForumGroups($forum, $threadTitle, $author, $forumname, $tables, $url = '')
	{
	global $babBody, $babDB, $BAB_SESS_USER, $BAB_SESS_EMAIL, $babAdminEmail, $babInstallPath;
 
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


		function tempa($forum, $threadTitle, $author, $forumname, $url)
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
			if( !empty($url) )
				{
				$groups = bab_getGroupsAccess(BAB_FORUMSVIEW_GROUPS_TBL, $forum);
				if( count($groups) > 0 && in_array(BAB_ALLUSERS_GROUP, $groups))
					{
					$this->url = $url;
					}
				else
					{
					$this->url = $GLOBALS['babUrlScript']."?tg=login&cmd=detect&referer=".urlencode($url);
					}
				}
			else
				{
				$this->url = false;
				}

			$this->babtpl_thread = $this->threadname;
			$this->babtpl_author = $this->author;
			$this->babtpl_forum = $forumname;
			}
		}
	
    $mail = bab_mail();
	if( $mail == false )
		return;

    $mail->mailFrom($babAdminEmail, $GLOBALS['babAdminName']);

	$tempa = new tempa($forum, $threadTitle, $author, $forumname, $url);
	$message = $mail->mailTemplate(bab_printTemplate($tempa,"mailinfo.html", "newpost"));
	$messagetxt = bab_printTemplate($tempa,"mailinfo.html", "newposttxt");

	$mail->mailBody($message, "html");
	$mail->mailAltBody($messagetxt);

	$subject = bab_printTemplate($tempa,"mailinfo.html", "newpost_subject");
	if( empty($subject) )
		$mail->mailSubject(bab_translate("New post"));
	else
		$mail->mailSubject($subject);

	list($nbrecipients) = $babDB->db_fetch_row($babDB->db_query("select nb_recipients from ".BAB_FORUMS_TBL." where id='".$forum."'"));
	for( $mk=0; $mk < count($tables); $mk++ )
		{
		include_once $babInstallPath."admin/acl.php";
		$users = aclGetAccessUsers($tables[$mk], $forum);
		$arrusers = array();
		$count = 0;

		foreach($users as $id => $arr)
			{
			if( count($arrusers) == 0 || !in_array($id, $arrusers))
				{
				$arrusers[] = $id;
				if( $nbrecipients == 1 )
					{
					$mail->mailTo($arr['email'], $arr['name']);
					}
				else
					{
					$mail->mailBcc($arr['email'], $arr['name']);
					}
				$count++;
				}

			if( $count >= $nbrecipients )
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


function bab_uploadPostFiles($postid, $id_forum) {
	$db = $GLOBALS['babDB'];
	$baseurl = $GLOBALS['babUploadPath'].'/forums/';
	if (!is_dir($baseurl))
		{
		if (!@bab_mkdir($baseurl))
			{
			$GLOBALS['babBody']->msgerror = bab_translate("Can't create forums directory in").' '.$GLOBALS['babUploadPath'];
			return false;
			}
		}

	include_once $GLOBALS['babInstallPath']."utilit/indexincl.php";
	$postfiles = array();


	foreach ($_FILES as $file) {
		if( bab_isMagicQuotesGpcOn())
			{
			$file['name'] = stripslashes($file['name']);
			}
		if( isset($GLOBALS['babFileNameTranslation']))
			{
			$file['name'] = strtr($file['name'], $GLOBALS['babFileNameTranslation']);
			}

		$dest = $baseurl.$postid.','.$file['name'];
		

		if (move_uploaded_file($file['tmp_name'], $dest)) {
			$postfiles[$file['name']] = $dest;	
		}

	}

	bab_debug($postfiles);
	$index_status = bab_indexOnLoadFiles($postfiles, 'bab_forumsfiles');


	foreach($postfiles as $name => $dest) {
	

		$res = $db->db_query("SELECT id, index_status FROM ".BAB_FORUMSFILES_TBL." WHERE id_post='".$postid."' AND name='".$db->db_escape_string($name)."'");


		if ($res && $arr = $db->db_fetch_assoc($res)) {
			// old file overwrited
			
			if ($index_status != $arr['index_status']) {
				$db->db_query("UPDATE ".BAB_FORUMSFILES_TBL." SET index_status='".$index_status."' WHERE id='".$arr['id']."'");
			}
			
		} else {
			// new file
			$db->db_query("INSERT INTO ".BAB_FORUMSFILES_TBL." 
					(id_post, name, index_status) 
				VALUES 
					('".$postid."', '".$db->db_escape_string($name)."', '".$index_status."')
			");
		}
	}	



	return true;
}

/**
 * Get files associated with a forum post
 * clean files if not in databases
 * clean database if file not exists
 * @param integer $forum
 * @param integer $postid
 * @return array
 */
function bab_getPostFiles($forum,$postid)
	{
	include_once $GLOBALS['babInstallPath']."utilit/indexincl.php";
	$filedirectory = array();
	$out = array();
	$baseurl = $GLOBALS['babUploadPath'].'/forums/';
	$db  =&$GLOBALS['babDB'];


	if (is_dir($baseurl) && $h = opendir($baseurl)) {
		while (false !== ($file = readdir($h))) {
			if (substr($file,0,strpos($file,',')) == $postid) {
				$name = substr(strstr($file,','),1);
				$filedirectory[$name] = $baseurl.$file;
			}
		}
	}


	$res = $db->db_query("SELECT * FROM ".BAB_FORUMSFILES_TBL." WHERE id_post='".$postid."'");
	while ($arr = $db->db_fetch_assoc($res)) {

		if (isset($filedirectory[$arr['name']])) {
			$path = $filedirectory[$arr['name']];

			$out[] = array(
						'url' => $GLOBALS['babUrlScript']."?tg=posts&idx=dlfile&forum=".$forum."&post=".$postid."&file=".urlencode($arr['name']),
						'path' =>  $path,
						'name' => $arr['name'],
						'size' => round(filesize($path)/1024).' '.bab_translate('Kb'),
						'index_status' => $arr['index_status'],
						'index_label' => bab_getIndexStatusLabel($arr['index_status'])
						);

			unset($filedirectory[$arr['name']]);

		} else {
			$db->db_query("DELETE FROM ".BAB_FORUMSFILES_TBL." WHERE id='".$arr['id']."'");
		}
	}


	if (0 < count($filedirectory)) {
		foreach($filedirectory as $path) {
			unlink($path);
		}
	}
	
	return $out;
	}








/**
 * Index all forum files
 * @param array $status
 */
function indexAllForumFiles($status) {
	
	$db = &$GLOBALS['babDB'];

	$res = $db->db_query("
	
		SELECT 
			f.id,
			f.name,
			f.id_post, 
			t.forum 

		FROM 
			".BAB_FORUMSFILES_TBL." f,
			".BAB_POSTS_TBL." p,
			".BAB_THREADS_TBL." t 
		WHERE 
			f.index_status IN('".implode("','",$status)."') 
			AND p.id = f.id_post 
			AND t.id = p.id_thread 
		
	");

	$baseurl = $GLOBALS['babUploadPath'].'/forums/';
	$files = array();
	$rights = array();

	while ($arr = $db->db_fetch_assoc($res)) {

		$files[] = $baseurl.$arr['id_post'].",".$arr['name'];
		$rights['forums/'.$arr['id_post'].",".$arr['name']] = array(
				'id' => $arr['id'],
				'id_forum' => $arr['forum']
			);
	}

	if (!$files)
		return false;

	include_once $GLOBALS['babInstallPath']."utilit/indexincl.php";

	$obj = new bab_indexObject('bab_forumsfiles');

	
	if (in_array(BAB_INDEX_STATUS_INDEXED, $status)) {
		$obj->resetIndex($files);
	} else {
		$obj->addFilesToIndex($files);
	}

	foreach($rights as $f => $arr) {
		$obj->setIdObjectFile($f, $arr['id'], $arr['id_forum']);
	}

	$db->db_query("
	
		UPDATE ".BAB_FORUMSFILES_TBL." SET index_status='".BAB_INDEX_STATUS_INDEXED."'
		WHERE 
			index_status IN('".implode("','",$status)."')
	");

	return count($files);
}





?>