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
require_once dirname(__FILE__).'/utilit/registerglobals.php';
include_once $babInstallPath.'utilit/uiutil.php';
include_once $babInstallPath.'utilit/mailincl.php';
include_once $babInstallPath.'utilit/topincl.php';
include_once $babInstallPath.'utilit/artincl.php';



function listComments($topics, $article)
{
	class ListCommentsTemplate
	{
		/**
		 * @var resource
		 */
		private $comments;

		// Template variables.
		/**
		 * @var int			The number of comments
		 */
		public $nb_comments;
		public $altbg;
		public $article;
		public $topics;
		public $commentdate;
		public $authorname;
		public $commenttitle;
		public $commentbody;
		public $user_is_topic_manager;
		
		public function __construct($topicId, $articleId)
		{
			global $babDB;
			$req = 'SELECT * FROM ' . BAB_COMMENTS_TBL . ' WHERE id_article=' . $babDB->quote($articleId) . " AND confirmed='Y' ORDER BY date DESC";
			$this->comments = $babDB->db_query($req);
			$this->nb_comments = $babDB->db_num_rows($this->comments);
			$this->article = bab_toHtml($articleId);
			$this->topics = bab_toHtml($topicId);
			$this->alternate = 0;
			$this->altbg = false;
			$this->t_edit_comment = bab_toHtml(bab_translate('Edit this comment'));
			$this->user_is_topic_manager = bab_isAccessValid(BAB_TOPICSMAN_GROUPS_TBL, $topicId);
		}

		public function getnext()
		{
			global $babDB;
			if (($comment = $babDB->db_fetch_assoc($this->comments)) !== false) {
				$this->altbg = !$this->altbg;

				$this->commentid = bab_toHtml($comment['id']);
				$this->editcommenturl = bab_toHtml($GLOBALS['babUrlScript'].'?tg=comments&idx=edit&comment_id=' . $comment['id'] . '&topics=' . $this->topics . '&article=' . $this->article);
				$this->commentdate = bab_toHtml(bab_strftime(bab_mktime($comment['date'])));
				if ($comment['id_author']) {
					$this->authorname = bab_toHtml(bab_getUserName($comment['id_author']));
				} else {
					$this->authorname = bab_toHtml($comment['name']);
				}

				$this->commenttitle = bab_toHtml($comment['subject']);

				$this->article_rating = bab_toHtml($comment['article_rating']);
				$this->article_rating_percent = bab_toHtml($comment['article_rating'] * 20.0);

				include_once $GLOBALS['babInstallPath'] . 'utilit/editorincl.php';
				$editor = new bab_contentEditor('bab_article_comment');
				$editor->setContent($comment['message']);
				$editor->setFormat($comment['message_format']);
				$this->commentbody = $editor->getHtml();

				return true;
			}
			return false;
		}
	}

	global $babBodyPopup;
	
	$listCommentsTemplate = new ListCommentsTemplate($topics, $article);
	$babBodyPopup->babecho(bab_printTemplate($listCommentsTemplate, 'comments.html', 'commentslist'));
}


function editComment($topics, $article, $commentId)
{
	class EditCommentTemplate
	{
		public	$subject;
		public	$subjectval;
		public	$name;
		public	$email;
		public	$message;
		public	$add;
		public	$article;
		public	$username;
		public	$anonyme;
		public	$title;
		public	$titleval;
		public	$com;
		
		public	$rate_articles = true;
		public	$useCaptcha;

		public function __construct($topics, $article, $commentId)
		{
			global $BAB_SESS_USER, $babDB;
			$this->comment_id = bab_toHtml($commentId);

			$req = 'SELECT * FROM ' . BAB_COMMENTS_TBL.' WHERE id=' . $babDB->quote($commentId);
			$res = $babDB->db_query($req);
			$comment = $babDB->db_fetch_assoc($res);

			$this->t_subject = bab_translate('comments-Title');
			$this->t_message = bab_translate('comments-Comment');
			$this->t_save = bab_translate('Save comment');
			$this->t_title = bab_translate('Article');
			$this->article = bab_toHtml($article);
			$this->topics = bab_toHtml($topics);
			$this->subject = bab_toHtml($comment['subject']);
			
			include_once $GLOBALS['babInstallPath'].'utilit/editorincl.php';

			$editor = new bab_contentEditor('bab_article_comment');
			$editor->setContent($comment['message']);
			$editor->setFormat($comment['message_format']);
			$editor->setParameters(array('height' => 200));
			$this->editor = $editor->getEditor();
		}
	}

	if (!bab_isAccessValid(BAB_TOPICSCOM_GROUPS_TBL, $topics)) {
		return;
	}
	
	global $babBodyPopup;
	
	$editCommentTemplate = new EditCommentTemplate($topics, $article, $commentId);
	$babBodyPopup->babecho(bab_printTemplate($editCommentTemplate, 'comments.html', 'commentedit'));
}


function addComment($topics, $article, $subject, $message, $com = '', $messageFormat = null)
{
	class AddCommentTemplate
	{
		public	$subject;
		public	$subjectval;
		public	$name;
		public	$email;
		public	$message;
		public	$add;
		public	$article;
		public	$username;
		public	$anonyme;
		public	$title;
		public	$titleval;
		public	$com;
		
		public	$rate_articles = true;
		public	$useCaptcha;

		public function __construct($topics, $article, $subject, $message, $com, $messageFormat)
		{
			global $BAB_SESS_USER, $babDB;
			$this->subject = bab_translate('comments-Title');
			$this->name = bab_translate('Name');
			$this->email = bab_translate('Email');
			$this->message = bab_translate('comments-Comment');
			$this->add = bab_translate('Add comment');
			$this->title = bab_translate('Article');
			$this->article = bab_toHtml($article);
			$this->topics = bab_toHtml($topics);
			$this->subjectval = bab_toHtml($subject);

			$this->t_rate_this_article = bab_translate('Rate this article:');

			$this->t_bad = bab_translate('Bad');
			$this->t_rather_bad = bab_translate('Rather bad');
			$this->t_average = bab_translate('Average');
			$this->t_rather_good = bab_translate('Rather good');
			$this->t_good = bab_translate('Good');
			
			$this->com = bab_toHtml($com);

			$req = 'SELECT allow_article_rating FROM ' . BAB_TOPICS_TBL.' WHERE id=' . $babDB->quote($topics);
			$res = $babDB->db_query($req);
			$topic = $babDB->db_fetch_assoc($res);
			$this->rate_articles = ($topic['allow_article_rating'] === 'Y');
			
			$req = 'SELECT title FROM ' . BAB_ARTICLES_TBL.' WHERE id=' . $babDB->quote($article);
			$res = $babDB->db_query($req);
			$arr = $babDB->db_fetch_assoc($res);
			$this->titleval = bab_toHtml($arr['title']);
			
			include_once $GLOBALS['babInstallPath'].'utilit/editorincl.php';
			
			$editor = new bab_contentEditor('bab_article_comment');
			$editor->setContent($message);
			if (isset($messageFormat)) {
				$editor->setFormat($messageFormat);
			}
			$editor->setParameters(array('height' => 200));
			$this->editor = $editor->getEditor();

			$arr = $babDB->db_fetch_array($babDB->db_query('SELECT idsacom FROM '.BAB_TOPICS_TBL.' WHERE id='.$babDB->quote($topics)));
			if ($arr['idsacom'] != 0) {
				$this->notcom = bab_translate('Note: for this topic, comments are moderated');
			} else {
				$this->notcom = '';
			}

			$this->useCaptcha = false;

			// We use the captcha if it is available as a functionality.
			if (!$GLOBALS['BAB_SESS_LOGGED']) {
//				$this->rate_articles = false;
				$captcha = @bab_functionality::get('Captcha');
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
	
	global $babBodyPopup;
	
	$addCommentTemplate = new AddCommentTemplate($topics, $article, $subject, $message, $com, $messageFormat);
	$babBodyPopup->babecho(bab_printTemplate($addCommentTemplate, 'comments.html', 'commentcreate'));
}



/**
 * @param int		$topics			The article topic id.
 * @param int		$article		The article id.
 * @param string	$subject		The title of the comment.
 * @param int		$com			The comment id.
 * @param int		$articleRating	The rating attibuted to the related article.
 * @param string	$msgerror		In case of error, this string will contain the error message to display.
 * 
 * @return	bool	True on success, false otherwise.
 */
function saveComment($topics, $article, $subject, $message, $com, $articleRating, $commentId, &$msgerror)
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
	
	if (empty($message)) {
		$msgerror = bab_translate('comments - ERROR: You must provide a comment');
		return false;
	}

	if (empty($com)) {
		$com = 0;
	}

	bab_saveArticleComment($topics, $article, $subject, $message, $com, $articleRating, $commentId);

	return true;
}



/* main */
$topics = bab_rp('topics', 0);
$article = bab_rp('article', 0);


$msgerror = '';
$popupmessage = '';

$action = bab_rp('action', null);

if (!bab_requireAccess(BAB_TOPICSVIEW_GROUPS_TBL, $topics, '')) {
	$idx = 'denied';
} elseif (($action == 'save' || isset($_POST['addcomment'])) && bab_isAccessValid(BAB_TOPICSCOM_GROUPS_TBL, $topics)) {
	include_once $GLOBALS['babInstallPath'] . 'utilit/editorincl.php';
	$editor = new bab_contentEditor('bab_article_comment');
	$message = $editor->getContent();
	$subject = bab_pp('subject');
	$com = bab_pp('com');
	$articleRating = bab_pp('article_rating', '0');
	$commentId = bab_pp('comment_id', null);
	
	if (!saveComment($topics, $article, $subject, $message, $com, $articleRating, $commentId, $msgerror)) {
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
		$babBodyPopup->msgerror = bab_translate('Access denied');
		printBabBodyPopup();
		exit;
		break;

	case 'unload':
		include_once $GLOBALS['babInstallPath'] . 'utilit/uiutil.php';
		$refreshurl = bab_rp('refreshurl');
		popupUnload($popupmessage, $refreshurl, true);
		exit;
		break;

	case 'delete':
		break;

	case 'edit':
		$babBodyPopup = new babBodyPopup();
		$babBodyPopup->title = bab_translate('Edit comment');
		$babBodyPopup->msgerror = $msgerror;
	
		$commentId = bab_rp('comment_id', null);
		editComment($topics, $article, $commentId);
		printBabBodyPopup();
		exit;
		break;

	case 'List':
	default:
		$babBodyPopup = new babBodyPopup();
		$babBodyPopup->title = bab_translate("List of comments");
		$babBodyPopup->msgerror = $msgerror;
		listComments($topics, $article);

		if (isset($editor)) {
			$message = $editor->getContent();
			$messageFormat = $editor->getFormat();
		} else {
			$message = '';
			$messageFormat = null;
		}
		$subject = bab_pp('subject');

		addComment($topics, $article, $subject, $message, '', $messageFormat);
		printBabBodyPopup();
		exit;
		break;
}
