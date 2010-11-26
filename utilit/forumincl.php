<?php
//-------------------------------------------------------------------------
// OVIDENTIA http://www.ovidentia.org
// Ovidentia is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2, or (at your option)
// any later version.
//
// This program is distributed in the hope that it will be useful, but
// WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
// See the GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307,
// USA.
//-------------------------------------------------------------------------
/**
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 * @copyright Copyright (c) 2009 by CANTICO ({@link http://www.cantico.fr})
 */
include_once 'base.php';

include_once $GLOBALS['babInstallPath'].'utilit/pagesincl.php';

define('DELTA_TIME', 86400);



/**
 * Get Forums as mysql resource or false if no accessible forums
 * @param	false | array		$forumid			array of id or false for all accessible forums
 * @param	false | int			$delegationid		if delegationid is false, forums are not filtered
 * @return 	resource | false
 */
function bab_getForumsRes($forumid = false, $delegationid = false) {

	global $babDB;

	$fv = bab_getUserIdObjects(BAB_FORUMSVIEW_GROUPS_TBL);

	$req = "
		SELECT * from ".BAB_FORUMS_TBL." 
		WHERE 
			active='Y' 
			AND id IN(".$babDB->quote(array_keys($fv)).') 
	';

	if ($delegationid !== false) {
		$req .= '
			AND id_dgowner='.$babDB->quote($delegationid).'
		';
	}

	$req .= '
			ORDER BY ordering ASC 
	';

	$res = $babDB->db_query($req);
	
	if (0 === $babDB->db_num_rows($res)) {
		return false;
	}

	return $res;
}



function bab_get_forums() {
		static $forumsview = null;
		if (!is_null($forumsview))
			return $forumsview;

		global $babDB;

		include_once dirname(__FILE__).'/forumincl.php';
		$res = bab_getForumsRes();

		if (false === $res) {
			$forumsview = array();
			return $forumsview;
		}


		while($arr = $babDB->db_fetch_array($res))
			{
			$forumsview[$arr['id']]['name'] = $arr['name'];
			$forumsview[$arr['id']]['description'] = $arr['description'];
			$forumsview[$arr['id']]['display'] = $arr['display'];
			$forumsview[$arr['id']]['moderation'] = $arr['moderation'];
			$forumsview[$arr['id']]['bdisplayemailaddress'] = $arr['bdisplayemailaddress'];
			$forumsview[$arr['id']]['bdisplayauhtordetails'] = $arr['bdisplayauhtordetails'];
			$forumsview[$arr['id']]['bflatview'] = $arr['bflatview'];
			$forumsview[$arr['id']]['bupdatemoderator'] = $arr['bupdatemoderator'];
			$forumsview[$arr['id']]['bupdateauthor'] = $arr['bupdateauthor'];
			}

		return $forumsview;
	}





function bab_getForumName($id)
	{
	global $babDB;
	$query = "select name from ".BAB_FORUMS_TBL." where id='".$babDB->db_escape_string($id)."'";
	$res = $babDB->db_query($query);
	if( $res && $babDB->db_num_rows($res) > 0)
		{
		$arr = $babDB->db_fetch_array($res);
		return $arr['name'];
		}
	else
		{
		return '';
		}
	}

function bab_isForumModerated($forum)
	{
	global $babDB;
	$query = "select moderation from ".BAB_FORUMS_TBL." where id='".$babDB->db_escape_string($forum)."'";
	$res = $babDB->db_query($query);
	if( $res && $babDB->db_num_rows($res) > 0)
		{
		$arr = $babDB->db_fetch_array($res);
		if( $arr['moderation'] == 'Y')
			return true;
		else
			return false;
		}
	return false;
	}

function bab_isForumThreadOpen($forum, $thread)
	{
	global $babDB;
	$query = "select active from ".BAB_THREADS_TBL." where id='".$babDB->db_escape_string($thread)."' and forum='".$babDB->db_escape_string($forum)."'";
	$res = $babDB->db_query($query);
	if( $res && $babDB->db_num_rows($res) > 0)
		{
		$arr = $babDB->db_fetch_array($res);
		if( $arr['active'] == 'Y')
			return true;
		else
			return false;
		}
	return false;
	}

function bab_getForumThreadTitle($id)
	{
	global $babDB;
	$query = "select post from ".BAB_THREADS_TBL." where id='".$babDB->db_escape_string($id)."'";
	$res = $babDB->db_query($query);
	if( $res && $babDB->db_num_rows($res) > 0)
		{
		$arr = $babDB->db_fetch_array($res);
		$query = "select subject from ".BAB_POSTS_TBL." where id='".$babDB->db_escape_string($arr['post'])."'";
		$res = $babDB->db_query($query);
		if( $res && $babDB->db_num_rows($res) > 0)
			{
			$arr = $babDB->db_fetch_array($res);
			return $arr['subject'];
			}
		return '';
		}
	else
		{
		return '';
		}
	}

function notifyForumGroups(bab_eventForumPost $event)
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


		function tempa($forum, $threadTitle, $author, $forumname, $url, $postId)
			{
            global $BAB_SESS_USER, $BAB_SESS_EMAIL, $babSiteName;
            $this->message = bab_translate("A new post has been registered on forum") .': '.$forumname;
            $this->from = bab_translate("Author");
            $this->thread = bab_translate("Thread");
            $this->threadname = bab_toHtml($threadTitle);
            $this->site = bab_translate("Web site");
            $this->sitename = bab_toHtml($babSiteName);
            $this->date = bab_translate("Date");
            $this->dateval = bab_strftime(mktime());
            $this->author = bab_toHtml($author);
            $this->idpost = $postId;
			if( !empty($url) )
				{
				$groups = bab_getGroupsAccess(BAB_FORUMSVIEW_GROUPS_TBL, $forum);
				if( count($groups) > 0 && in_array(BAB_ALLUSERS_GROUP, $groups))
					{
					$this->url = $url;
					}
				else
					{
					
					if (0 === mb_strpos($url, $GLOBALS['babUrl'].$GLOBALS['babPhpSelf'])) {
						$url = mb_substr($url, mb_strlen($GLOBALS['babUrl'].$GLOBALS['babPhpSelf']));
					}
					
					
					$this->url = $GLOBALS['babUrlScript'].'?tg=login&cmd=detect&referer='.urlencode($url);
					}
				}
			else
				{
				$this->url = false;
				}

			$this->babtpl_thread = bab_toHtml($this->threadname);
			$this->babtpl_author = bab_toHtml($this->author);
			$this->babtpl_forum = bab_toHtml($forumname);
			}
		}
	
    $mail = bab_mail();
	if( $mail == false )
		return;
		
	$forum = $event->getForumId();
	$thread = $event->getThreadId();	
	$threadTitle = $event->getThreadTitle();
	$author = $event->getPostAuthor();
	$postId = $event->getPostId();
	
	$tmp = $event->getForumInfos();
	$forumname = $tmp['name'];
	$nbrecipients = $tmp['nb_recipients'];
	$flat = $tmp['bflatview'] == 'Y' ? '1' : '0';
	
	$url = $GLOBALS['babUrlScript'] ."?tg=posts&idx=List&forum=$forum&thread=$thread&flat=$flat&views=1";

	$mailBCT = 'mail'.$babBody->babsite['mail_fieldaddress'];
	$clearBCT = 'clear'.$babBody->babsite['mail_fieldaddress'];

    $mail->mailFrom($babAdminEmail, $GLOBALS['babAdminName']);

	$tempa = new tempa($forum, $threadTitle, $author, $forumname, $url, $postId);
	$message = $mail->mailTemplate(bab_printTemplate($tempa,'mailinfo.html', 'newpost'));
	$messagetxt = bab_printTemplate($tempa,'mailinfo.html', 'newposttxt');

	$mail->mailBody($message, 'html');
	$mail->mailAltBody($messagetxt);

	$subject = bab_printTemplate($tempa,'mailinfo.html', 'newpost_subject');
	if( empty($subject) )
		$mail->mailSubject(bab_translate("New post"));
	else
		$mail->mailSubject($subject);

	
	
	$users = $event->getUsersToNotify();
	$count = 0;
	
	foreach($users as $id => $arr)
		{
		$mail->$mailBCT($arr['email'], $arr['name']);
		$count++;
		$event->addInformedUser($id);

		if( $count >= $nbrecipients )
			{
			$mail->send();
			$mail->$clearBCT();
			$mail->clearTo();
			$count = 0;
			}

		}

	if( $count > 0 )
		{
		$mail->send();
		$mail->$clearBCT();
		$mail->clearTo();
		$count = 0;
		}
		
	}

function notifyThreadAuthor($threadTitle, $email, $author, $idpost = null)
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


		function tempb($threadTitle, $email, $author, $idpost)
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
            $this->idpost = $idpost;
			}
		}
	
    $mail = bab_mail();
	if( $mail == false )
		return;

	$mail->mailTo($email);
    $mail->mailFrom($babAdminEmail, $GLOBALS['babAdminName']);
    $mail->mailSubject(bab_translate("New post"));

	$tempb = new tempb($threadTitle, $email, $author, $idpost);
	$message = $mail->mailTemplate(bab_printTemplate($tempb,'mailinfo.html', 'newpost'));
    $mail->mailBody($message, 'html');

	$message = bab_printTemplate($tempb,'mailinfo.html', 'newposttxt');
    $mail->mailAltBody($message);

	$mail->send();
	}


function bab_uploadPostFiles($postid, $id_forum) {
	global $babDB;
	$baseurl = $GLOBALS['babUploadPath'].'/forums/';
	if (!is_dir($baseurl))
		{
		if (!@bab_mkdir($baseurl))
			{
			$GLOBALS['babBody']->msgerror = bab_translate("Can't create forums directory in").' '.$GLOBALS['babUploadPath'];
			return false;
			}
		}

	include_once $GLOBALS['babInstallPath'].'utilit/indexincl.php';
	$postfiles = array();


	foreach ($_FILES as $file) {
		if( isset($GLOBALS['babFileNameTranslation']))
			{
			$file['name'] = strtr($file['name'], $GLOBALS['babFileNameTranslation']);
			}

		$dest = $baseurl.$postid.','.$file['name'];
		

		if (move_uploaded_file($file['tmp_name'], $dest)) {
			$postfiles[$file['name']] = $dest;	
		}

	}

	//bab_debug($postfiles);
	$index_status = bab_indexOnLoadFiles($postfiles, 'bab_forumsfiles');


	foreach($postfiles as $name => $dest) {
	

		$res = $babDB->db_query("SELECT id, index_status FROM ".BAB_FORUMSFILES_TBL." WHERE id_post='".$babDB->db_escape_string($postid)."' AND name='".$babDB->db_escape_string($name)."'");


		if ($res && $arr = $babDB->db_fetch_assoc($res)) {
			// old file overwrited
			
			if ($index_status != $arr['index_status']) {
				$babDB->db_query("UPDATE ".BAB_FORUMSFILES_TBL." SET index_status='".$index_status."' WHERE id='".$arr['id']."'");
			}
			
		} else {
			// new file
			$babDB->db_query("INSERT INTO ".BAB_FORUMSFILES_TBL." 
					(id_post, name, index_status) 
				VALUES 
					('".$postid."', '".$babDB->db_escape_string($name)."', '".$index_status."')
			");
		}
	}	



	return true;
}







/**
 * get parts of a forum file as stored in upload directory
 * @param	string	$file	full path name or file name	
 * @return array | false if the file is not compliant
 */
function bab_getForumFileParts($file) {

	$filename = basename($file);

	
	$iOffset = mb_strpos($filename,',');

	if(false !== $iOffset)
	{
		$id_post = (int) mb_substr($filename, 0, $iOffset);
		$name = mb_substr($filename, $iOffset + 1);
		
		return array($id_post, $name);
	}

	return false;
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
	global $babDB;
	include_once $GLOBALS['babInstallPath'].'utilit/indexincl.php';
	$filedirectory = array();
	$out = array();
	$baseurl = $GLOBALS['babUploadPath'].'/forums/';

	if (is_dir($baseurl) && $h = opendir($baseurl)) {
		while (false !== ($file = readdir($h))) {

			$arr = bab_getForumFileParts($file);
			if (!$arr) {
				continue;
			}

			list($id_post, $name) = $arr;
			if ($id_post === (int) $postid) {
				$filedirectory[$name] = $baseurl.$file;
			}
		}
	}

	$res = $babDB->db_query("SELECT * FROM ".BAB_FORUMSFILES_TBL." WHERE id_post='".$babDB->db_escape_string($postid)."'");
	while ($arr = $babDB->db_fetch_assoc($res)) {

		if (isset($filedirectory[$arr['name']])) {
			$path = $filedirectory[$arr['name']];

			$out[] = array(
						'url' => $GLOBALS['babUrlScript'].'?tg=posts&idx=dlfile&forum='.$forum.'&post='.$postid.'&file='.urlencode($arr['name']),
						'path' =>  $path,
						'name' => $arr['name'],
						'size' => ceil(filesize($path)/1024).' '.bab_translate('Kb'),
						'index_status' => $arr['index_status'],
						'index_label' => bab_getIndexStatusLabel($arr['index_status'])
						);

			unset($filedirectory[$arr['name']]);

		} else {
			$babDB->db_query("DELETE FROM ".BAB_FORUMSFILES_TBL." WHERE id='".$babDB->db_escape_string($arr['id'])."'");
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
 * @return object bab_indexReturn
 */
function indexAllForumFiles($status, $prepare) {
	
	global $babDB;

	$res = $babDB->db_query("
	
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
			f.index_status IN(".$babDB->quote($status).") 
			AND p.id = f.id_post 
			AND t.id = p.id_thread 
		
	");

	$baseurl = $GLOBALS['babUploadPath'].'/forums/';
	$files = array();
	$rights = array();

	while ($arr = $babDB->db_fetch_assoc($res)) {

		$files[] = $baseurl.$arr['id_post'].','.$arr['name'];
		$rights['forums/'.$arr['id_post'].','.$arr['name']] = array(
				'id' => $arr['id'],
				'id_forum' => $arr['forum']
			);
	}

	if (!$files) {
		$r = new bab_indexReturn;
		$r->addError(bab_translate("No files to index in the forums"));
		$r->result = false;
		return $r;
	}

	include_once $GLOBALS['babInstallPath'].'utilit/indexincl.php';

	$obj = new bab_indexObject('bab_forumsfiles');

	$param = array(
			'status' => $status,
			'rights' => $rights
		);
	
	if (in_array(BAB_INDEX_STATUS_INDEXED, $status)) {
		if ($prepare) {
			return $obj->prepareIndex($files, $GLOBALS['babInstallPath'].'utilit/forumincl.php', 'indexAllForumFiles_end', $param );
		} else {
			$r = $obj->resetIndex($files);
		}
	} else {
		$r = $obj->addFilesToIndex($files);
	}

	if (true === $r->result) {
		indexAllForumFiles_end($param);
	}

	return $r;
}


function indexAllForumFiles_end($param) {

	global $babDB;
	$babDB->db_query("
	
		UPDATE ".BAB_FORUMSFILES_TBL." SET index_status='".BAB_INDEX_STATUS_INDEXED."'
		WHERE 
			index_status IN(".$babDB->quote($param['status']).")
	");

	$obj = new bab_indexObject('bab_forumsfiles');

	foreach($param['rights'] as $f => $arr) {
		$obj->setIdObjectFile($f, $arr['id'], $arr['id_forum']);
	}

	return true;
}




function bab_confirmPost($forum, $thread, $post)
	{
	require_once dirname(__FILE__).'/eventforum.php';
	global $babDB;
	$req = "update ".BAB_THREADS_TBL." set lastpost='".$babDB->db_escape_string($post)."' where id='".$babDB->db_escape_string($thread)."'";
	$res = $babDB->db_query($req);

	$req = "update ".BAB_POSTS_TBL." set confirmed='Y', date_confirm=now() where id='".$babDB->db_escape_string($post)."'";
	$res = $babDB->db_query($req);

	$req = "
		select 
			t.*, p.subject 
		from  
			".BAB_THREADS_TBL." t, 
			".BAB_POSTS_TBL." p 
		where 
			p.id=t.post 
			AND t.id='".$babDB->db_escape_string($thread)."'
		";
	
	$res = $babDB->db_query($req);
	$arr = $babDB->db_fetch_array($res);
	
	if (!$arr)
	{
		throw new Exception('Thread not found');
	}

	$arrpost = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_POSTS_TBL." where id='".$babDB->db_escape_string($post)."'"));

	if( $arr['notify'] == "Y" && $arr['starter'] != 0)
		{
		$req = "select email from ".BAB_USERS_TBL." where id='".$babDB->db_escape_string($arr['starter'])."'";
		$res = $babDB->db_query($req);
		list($email) = $babDB->db_fetch_array($res);

		notifyThreadAuthor(bab_getForumThreadTitle($thread), $email, $arrpost['author'], $arrpost['id']);
		}
	
	$bthread = ($arr['post'] == $arr['lastpost']);
	
	if ($bthread)
	{
		// new thread
		$event = new bab_eventForumAfterThreadAdd;
	} else {
		// new post
		$event = new bab_eventForumAfterPostAdd;
	}
	
	$event->setForum($forum);
	$event->setThread($arr['id'], $arr['subject']);
	$event->setPost($arrpost['id'], $arrpost['author'], true);
	
	bab_fireEvent($event);
}

/**
 * Deletes the specified post.
 *  
 * @param int	$forum		The forum id.
 * @param int	$post		The post id.
 */
function bab_deletePost($forum, $post)
	{
	global $babDB;

	include_once $GLOBALS['babInstallPath']."utilit/delincl.php";
	$req = "select * from ".BAB_POSTS_TBL." where id='".$babDB->db_escape_string($post)."'";
	$res = $babDB->db_query($req);
	$arr = $babDB->db_fetch_array($res);
	

	if( $arr['id_parent'] == 0)
		{
		/* if it's the only post in the thread, delete the thread also */
		bab_deleteThread($forum, $arr['id_thread']);
		Header("Location: ". $GLOBALS['babUrlScript']."?tg=threads&forum=".$forum);
		}
	else
		{
		bab_deletePostFiles($forum, $post);
		$req = "delete from ".BAB_POSTS_TBL." where id = '".$babDB->db_escape_string($post)."'";
		$res = $babDB->db_query($req);

		$req = "select lastpost from ".BAB_THREADS_TBL." where id='".$babDB->db_escape_string($arr['id_thread'])."'";
		$res = $babDB->db_query($req);
		$arr2 = $babDB->db_fetch_array($res);
		if( $arr2['lastpost'] == $post ) // it's the lastpost
			{
			$req = "select id from ".BAB_POSTS_TBL." where id_thread='".$babDB->db_escape_string($arr['id_thread'])."' order by date desc";
			$res = $babDB->db_query($req);
			$arr2 = $babDB->db_fetch_array($res);
			$req = "update ".BAB_THREADS_TBL." set lastpost='".$babDB->db_escape_string($arr2['id'])."' where id='".$babDB->db_escape_string($arr['id_thread'])."'";
			$res = $babDB->db_query($req);
			}

		}

	}

/**
 * Return fields to display for a forum.
 *  
 * @param int	$forum		The forum id.
 */
function bab_getForumFields($forum)
	{
		global $babDB;
		static $forums_fields = array();
		
		if( isset($forums_fields[$forum]))
		{
			return $forums_fields[$forum];
		}
		
		include_once $GLOBALS['babInstallPath'].'utilit/dirincl.php';
		$ret = array();
		list($iddir) = $babDB->db_fetch_row($babDB->db_query("select id from ".BAB_DB_DIRECTORIES_TBL." where id_group='".BAB_REGISTERED_GROUP."'"));
		$fields = bab_getDirectoriesFields(array($iddir));
		$res = $babDB->db_query("select * from ".BAB_FORUMS_FIELDS_TBL." where id_forum=".$babDB->quote($forum)." order by field_order asc");
		while($arr = $babDB->db_fetch_array($res))
		{
			if( isset($fields[$arr['id_field']]))
			{
				$ret[] = $fields[$arr['id_field']];
			}
		}
		$forums_fields[$forum] = $ret;
		return $ret;
	}

/**
 * Return name to display instead of full name.
 *  
 * @param int		$id_author		The author id.
 * @param array		$fields			fields to fetch.
 * @param string	$author			return value if not found
 */	
function bab_getForumContributor($id_forum, $id_author, $author)
{
	static $forums_contributors = array();
	
	if( isset($forums_contributors[$id_forum]) && isset($forums_contributors[$id_forum][$id_author]))
	{
		$author = $forums_contributors[$id_forum][$id_author];
	}
	
	$fields = bab_getForumFields($id_forum);
	
	if( $id_author && count($fields))
	{
		$author = '';
		$entries = bab_getDirEntry($id_author, BAB_DIR_ENTRY_ID_USER);
		foreach($fields as $key => $info )
		{
			if( isset($entries[$info['name']]))
			{
				$author .= ' '.bab_toHTML($entries[$info['name']]['value']);
			}
		}
		$forums_contributors[$id_forum][$id_author] = $author;
	}
	
	return $author;
}
?>