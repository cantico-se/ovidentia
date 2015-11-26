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
 * 
 */
include_once 'base.php';
require_once dirname(__FILE__).'/utilit/registerglobals.php';
include_once $babInstallPath.'utilit/uiutil.php';
include_once $babInstallPath.'utilit/mailincl.php';
include_once $babInstallPath.'utilit/topincl.php';
include_once $babInstallPath.'utilit/artincl.php';
require_once dirname(__FILE__).'/utilit/commentincl.php';


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
	

	if (!bab_isAccessValid(BAB_TOPICSCOM_GROUPS_TBL, $topics)) {
		return;
	}
	
	global $babBodyPopup;
	
	$editCommentTemplate = new bab_EditCommentTemplate($topics, $article, $commentId);
	$babBodyPopup->babecho(bab_printTemplate($editCommentTemplate, 'comments.html', 'commentedit'));
}







function addComment($topics, $article, $subject, $message, $com = '', $messageFormat = null)
{
	

	if (!bab_isAccessValid(BAB_TOPICSCOM_GROUPS_TBL, $topics)) {
		return;
	}
	
	global $babBodyPopup;
	
	$addCommentTemplate = new bab_AddCommentTemplate($topics, $article, $subject, $message, $com, $messageFormat);
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
function saveComment($topics, $article, $subject, $message, $com, $articleRating, $commentId, $userName, &$msgerror)
{
	global $babDB, $BAB_SESS_USER, $BAB_SESS_EMAIL, $BAB_SESS_USERID;

	// We first check that the user entered the correct captcha.
	if (!$GLOBALS['BAB_SESS_LOGGED']) {
		$captcha = bab_functionality::get('Captcha');
		if (false !== $captcha) {
			$captchaSecurityCode = bab_pp('captchaSecurityCode', '');

			if (!$captcha->securityCodeValid($captchaSecurityCode)) {
				$msgerror = bab_translate('The captcha value is incorrect');
				return false;
			}
		}
	}

	if (empty($message)) {
		$msgerror = bab_translate('comments - ERROR: You must provide a comment');
		return false;
	}

	if (empty($com)) {
		$com = 0;
	}

	bab_saveArticleComment($topics, $article, $subject, $message, $com, $articleRating, $commentId, 'text', $userName);

	return true;
}



/* main */
$topics = bab_rp('topics', 0);
$article = bab_rp('article', 0);


$msgerror = '';

$action = bab_rp('action', null);

if (!bab_requireAccess(BAB_TOPICSVIEW_GROUPS_TBL, $topics, '')) {
	$idx = 'denied';
} elseif (($action == 'save' || isset($_POST['addcomment'])) && bab_isAccessValid(BAB_TOPICSCOM_GROUPS_TBL, $topics)) {
	
	$message = bab_pp('message');
	$subject = bab_pp('subject');
	$com = bab_pp('com');
	$articleRating = bab_pp('article_rating', '0');
	$commentId = bab_pp('comment_id', null);
	
	if (!saveComment($topics, $article, $subject, $message, $com, $articleRating, $commentId, bab_pp('name'), $msgerror)) {
		$babBody->addNextPageError($msgerror);
	}
	

	$articleUrl = bab_sitemap::url('babArticle_'.$article, $GLOBALS['babUrlScript']."?tg=articles&idx=More&topics=".$topics."&article=".$article);
	
	$articlesUrl = new bab_url($articleUrl);
	$articlesUrl->location();
}

switch ($idx)
{


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
