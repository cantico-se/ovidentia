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
 * @internal SEC1 NA 08/12/2006 FULL
 */
include_once 'base.php';
include_once $babInstallPath.'utilit/uiutil.php';
include_once $babInstallPath.'utilit/mailincl.php';
include_once $babInstallPath.'utilit/topincl.php';
include_once $babInstallPath.'utilit/artincl.php';



function listComments($topics, $article)
{
	global $babBodyPopup;

	class ListCommentsTemplate
	{
		var $subjecturl;
		var $subjectname;
		var $arr = array();
		var $db;
		var $count;
		var $res;
		var $article;
		var $altbg;

		function ListCommentsTemplate($topics, $article)
		{
			global $babBodyPopup, $babDB;
			$req = "select * from ".BAB_COMMENTS_TBL." where id_article='".$babDB->db_escape_string($article)."' and confirmed='Y' order by date desc";
			$this->res = $babDB->db_query($req);
			$this->count = $babDB->db_num_rows($this->res);
			$this->article = bab_toHtml($article);
			$this->topics = bab_toHtml($topics);
			$this->alternate = 0;
			$res = $babDB->db_query("select count(*) from ".BAB_ARTICLES_TBL." where id_topic='".$babDB->db_escape_string($topics)."' and archive='Y'");
			$this->altbg = false;
		}

		function getnext()
		{
			global $babDB;
			static $i = 0;
			if ($i < $this->count) {
				$this->altbg = !$this->altbg;
				$arr = $babDB->db_fetch_array($this->res);
				$this->commentdate = bab_toHtml(bab_strftime(bab_mktime($arr['date'])));
				if ($arr['id_author']) {
					$this->authorname = bab_toHtml(bab_getUserName($arr['id_author']));
				} else {
					$this->authorname = bab_toHtml($arr['name']);
				}

				$this->commenttitle = bab_toHtml($arr['subject']);				

				include_once $GLOBALS['babInstallPath'] . 'utilit/editorincl.php';
				$editor = new bab_contentEditor('bab_article_comment');
				$editor->setContent($arr['message']);
				$this->commentbody = $editor->getHtml();
						
				$i++;
				return true;
			}
			return false;
		}
	}

	$listCommentsTemplate = new ListCommentsTemplate($topics, $article);
	$babBodyPopup->babecho(bab_printTemplate($listCommentsTemplate, 'comments.html', 'commentslist'));
}



function addComment($topics, $article, $subject, $message, $com='')
{
	global $babBodyPopup;
	
	class AddCommentTemplate
	{
		var $subject;
		var $subjectval;
		var $name;
		var $email;
		var $message;
		var $add;
		var $article;
		var $username;
		var $anonyme;
		var $title;
		var $titleval;
		var $com;
		var $msie;

		function AddCommentTemplate($topics, $article, $subject, $message, $com)
		{
			global $BAB_SESS_USER, $babDB;
			$this->subject = bab_translate("comments-Title");
			$this->name = bab_translate("Name");
			$this->email = bab_translate("Email");
			$this->message = bab_translate("comments-Comment");
			$this->add = bab_translate("Add comment");
			$this->title = bab_translate("Article");
			$this->article = bab_toHtml($article);
			$this->topics = bab_toHtml($topics);
			$this->subjectval = bab_toHtml($subject);

			$this->com = bab_toHtml($com);
			$req = 'SELECT title FROM '.BAB_ARTICLES_TBL.' WHERE id=' . $babDB->quote($article);
			$res = $babDB->db_query($req);
			$arr = $babDB->db_fetch_array($res);
			$this->titleval = bab_toHtml($arr['title']);
			
			include_once $GLOBALS['babInstallPath'].'utilit/editorincl.php';
			
			$editor = new bab_contentEditor('bab_article_comment');
			$editor->setContent($message);
			$editor->setFormat('html');
			$editor->setParameters(array('height' => 200));
			$this->editor = $editor->getEditor();

			$arr = $babDB->db_fetch_array($babDB->db_query("select idsacom from ".BAB_TOPICS_TBL." where id='".$babDB->db_escape_string($topics)."'"));
			if ($arr['idsacom'] != 0) {
				$this->notcom = bab_translate('Note: for this topic, comments are moderated');
			} else {
				$this->notcom = '';
			}

			// We use the captcha if it is available as a functionality.
			if (!$GLOBALS['BAB_SESS_USERID']) {
				$captcha = @bab_functionality::get('Captcha');
				$this->useCaptcha = false;
				if (false !== $captcha) {
					$this->useCaptcha = true;
					$this->captchaCaption1 = bab_translate('Word Verification');
					$this->captchaSecurityData = $captcha->getGetSecurityHtmlData();
					$this->captchaCaption2 = bab_translate('Enter the letters in the image above');
				}
			}
				
		}
	}

	if (!bab_isAccessValid(BAB_TOPICSCOM_GROUPS_TBL, $topics)) {
		return;
	}
	
	$addCommentTemplate = new AddCommentTemplate($topics, $article, $subject, $message, $com);
	$babBodyPopup->babecho(bab_printTemplate($addCommentTemplate, 'comments.html', 'commentcreate'));
}



/**
 * @param int		$topics		The article topic id.
 * @param int		$article	The article id.
 * @param string	$subject	The title of the comment.
 * @param int		$com		The comment id.
 * @param string	$msgerror	In case of error, this string will contain the error message to display.
 */
function saveComment($topics, $article, $subject, $com, &$msgerror)
{
	global $babDB, $BAB_SESS_USER, $BAB_SESS_EMAIL, $BAB_SESS_USERID;

	// We first check that the user entered the correct captcha.
	if (!$GLOBALS['BAB_SESS_LOGGED']) {
		$captcha = @bab_functionality::get('Captcha');
		if (false !== $captcha) {
			$captchaSecurityCode = bab_pp('captchaSecurityCode', '');
						
			if (!$captcha->securityCodeValid($captchaSecurityCode)) {
				$msgerror = bab_translate('The captcha value is incorrect');
				return false;
			}
		}
	}

	if (empty($subject)) {
		$msgerror = bab_translate('comments - ERROR: You must provide a title');
		return false;
	}
	
	include_once $GLOBALS['babInstallPath'] . 'utilit/editorincl.php';
		
	$editor = new bab_contentEditor('bab_article_comment');
	$message = $editor->getContent();

	if (empty($message)) {
		$msgerror = bab_translate('comments - ERROR: You must provide a comment');
		return false;
	}

	if (empty($com)) {
		$com = 0;
	}

	bab_saveArticleComment($topics, $article, $subject , $message, $com);

	return true;
}



/* main */
$topics = bab_rp('topics', 0);
$article = bab_rp('article', 0);
$msgerror = '';
$popupmessage = '';

if (!bab_requireAccess(BAB_TOPICSVIEW_GROUPS_TBL, $topics, '')) {
	$idx = 'denied';
} elseif (isset($_POST['addcomment']) && bab_isAccessValid(BAB_TOPICSCOM_GROUPS_TBL, $topics)) {
	if (!saveComment($topics, $article, bab_pp('subject'), bab_pp('com'), $msgerror)) {
		$idx = 'List';
	} else {
		$popupmessage = bab_translate('Update done');
		$idx = 'unload';
	}
}

switch ($idx)
{
	case 'denied':
		$babBodyPopup = new babBodyPopup();
		$babBodyPopup->msgerror = bab_translate("Access denied");
		printBabBodyPopup();
		exit;
		break;

	case 'unload':
		include_once $babInstallPath."utilit/uiutil.php";
		$refreshurl = bab_rp('refreshurl');
		popupUnload($popupmessage, $refreshurl, true);
		exit;
		break;

	case 'delete':
		break;

	case 'List':
	default:
		$babBodyPopup = new babBodyPopup();
		$babBodyPopup->title = bab_translate("List of comments");
		$babBodyPopup->msgerror = $msgerror;
		listComments($topics, $article);
		addComment($topics, $article, bab_pp('subject'), bab_pp('message'), '');
		printBabBodyPopup();
		exit;
		break;
}
