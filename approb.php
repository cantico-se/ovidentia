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
/**
* @internal SEC1 NA 14/12/2006 FULL
*/
include 'base.php';
require_once dirname(__FILE__).'/utilit/registerglobals.php';
include_once $GLOBALS['babInstallPath'].'utilit/mailincl.php';
include_once $GLOBALS['babInstallPath'].'utilit/afincl.php';
include_once $GLOBALS['babInstallPath'].'utilit/topincl.php';
include_once $GLOBALS['babInstallPath'].'utilit/artincl.php';
include_once $GLOBALS['babInstallPath'].'utilit/evtincl.php';
include_once $GLOBALS['babInstallPath'].'utilit/calincl.php';
include_once $GLOBALS['babInstallPath'].'utilit/forumincl.php';

include_once $GLOBALS['babInstallPath'].'utilit/eventincl.php';


/**
 * Event fired when the approbation page is displayed
 * 
 * @since 6.1.1
 * @package events
 */
class bab_eventBeforeWaitingItemsDisplayed extends bab_event
{
	/**
	 * Set before calling bab_fireEvent
	 * @since 8.0.99
	 * @var bool
	 */
	public $status_only = false;
	
	/**
	 * Property set after the bab_fireEvent if there are item to approve
	 * @var bool
	 */
	public $contain_waiting_items = false;
	
	
	public $objects = array();

	/**
	 * Add object to the waiting items page
	 * 
	 * @param string 	$title		Title for the list of items
	 * @param Array 	$arr		the list of items waiting for approval,
	 * 								Format for each item :
	 * 									text 		: plain text on one line
	 * 									description : HTML content
	 * 									url			: url to the approval form
	 * 									popup		: boolean (url opening method)
	 * 									idschi		: int
	 * 								
	 */
	public function addObject($title, Array $arr) {
		static $i = 0;
		$key = mb_strtolower(mb_substr($title,0,3));
		$this->objects[$key.$i] = array(
			'title' => $title,
			'arr'	=> $arr
		);

		$i++;
		
		$this->contain_waiting_items = true;
	}
	
	
	/**
	 * Use this method in the callback function if the "status_only" property is set tu TRUE
	 * @param bool $status true : there are waiting items | false : no waiting items
	 */
	public function setStatus($status)
	{
		$this->contain_waiting_items = $status;
		
		if ($status)
		{
			$this->stop_propagation = true;
		}
		
		return $this;
	}
}










/**
 * Template class
 * Display list of waiting items collected with the bab_eventBeforeWaitingItemsDisplayed event
 * @see bab_eventBeforeWaitingItemsDisplayed
 */
class listWaitingItemsCls
{
	var $altbg = true;
	var $arrObjects = array();
	var $firstcall = false;

	var $addonTitle;
	var $url;
	var $text;
	var $description;

	public function __construct()
	{
		$event = new bab_eventBeforeWaitingItemsDisplayed();
		bab_fireEvent($event);
		$this->arrObjects = $event->objects;

		/**
		 * @deprecated
		 * Addons should not use this method since 6.1.1
		 */
		include_once $GLOBALS['babInstallPath'].'utilit/addonsincl.php';
		foreach(bab_addonsInfos::getRows() as $key => $row)
		{
			$addonpath = $GLOBALS['babInstallPath'].'addons/'.$row['title'];
			if($row['access'] && is_file($addonpath."/init.php" ))
			{
				bab_setAddonGlobals($row['id']);
				$call = $row['title']."_getWaitingItems";
				
				require_once( $addonpath."/init.php" );

				if( function_exists($call) )
				{

					trigger_error('The callback '.$call.' is deprecated, please use bab_addEventListener() instead');

					$title = $row['title'];
					$arr = array();
					call_user_func_array($call, array(&$title, &$arr));
					if (count($arr) > 0) {
						$key = mb_strtolower(mb_substr($title,0,3));
						$this->arrObjects[$key.$row['id']] = array(
								'title' => $title,
								'arr'	=> $arr
						);
					}
				}
			}
		}

		bab_sort::ksort($this->arrObjects);
	}
	

	public function getnextaddon()
	{
		$this->addonTitle = '';
		$this->arr = array();

		if (list(, $arr) = each($this->arrObjects))
		{
			$this->addonTitle = bab_toHtml($arr['title']);
			$this->arr = $arr['arr'];
			return true;
		}
		return false;
	}
	
	
	

	public function getnextitem()
	{
		$this->altbg = !$this->altbg;

		if (!isset($this->arr)) {
			return false;
		}

		if (list( , $arr) = each($this->arr))
		{
			$this->text 			= bab_toHtml($arr['text']);
			$this->description 		= $arr['description'];
			$this->url 				= bab_toHtml($arr['url']);
			$this->popup 			= $arr['popup'];
			$this->idschi 			= bab_toHtml($arr['idschi']);

			return true;
		}
		else
			return false;
	}
}






/**
 * Display list of waiting items collected with the bab_eventBeforeWaitingItemsDisplayed event
 * @see bab_eventBeforeWaitingItemsDisplayed
 */
function listWaitingItems()
{
	global $babBody;

	$temp = new listWaitingItemsCls();
	$babBody->babecho( bab_printTemplate($temp, "approb.html", "waitingItems"));
}





class bab_confirmWaiting
{
	function getHtml($file, $template)
	{
	include_once $GLOBALS['babInstallPath']."utilit/uiutil.php";

	$GLOBALS['babBodyPopup'] = new babBodyPopup();

	$GLOBALS['babBodyPopup']->title = & $GLOBALS['babBody']->title;
	$GLOBALS['babBodyPopup']->msgerror = & $GLOBALS['babBody']->msgerror;

	$GLOBALS['babBodyPopup']->babecho(bab_printTemplate($this, $file, $template));
	printBabBodyPopup();
	}
}




/**
 * 
 * @param unknown_type $idart
 */
function confirmWaitingArticle($idart)
{
	global $babBody;
	class temp extends bab_confirmWaiting
	{
		var $arttxt;
		var $accessDenied = false;

		function temp($idart)
		{
			global $babBody, $babDB;

			$this->accessDenied = false;

			$res = $babDB->db_query("select * from ".BAB_ART_DRAFTS_TBL." where id='".$babDB->db_escape_string($idart)."'");
			if ($res && $babDB->db_num_rows($res) > 0) {
				$arr = $babDB->db_fetch_array($res);
				$arrschi = bab_getWaitingIdSAInstance($GLOBALS['BAB_SESS_USERID']);
				if (count($arrschi) > 0  && in_array($arr['idfai'], $arrschi)) {
					
					$this->preview = bab_previewArticleDraft($idart);
					
					$this->idart = bab_toHtml($idart);
					$this->arttxt = bab_translate("Article");
					$this->pathtxt = bab_translate("Path");
					$this->authortxt = bab_translate("Author");
					$this->confirmtxt = bab_translate("Confirm");
					$this->commenttxt = bab_translate("Comment");
					$this->yes = bab_translate("Yes");
					$this->no = bab_translate("No");
					$this->updatetxt = bab_translate("Update");

					$this->arttitle = bab_toHtml($arr['title']);
					$this->pathname = viewCategoriesHierarchy_txt($arr['id_topic']);
					$this->author = bab_toHtml(bab_getUserName($arr['id_author']));
				} else {
					$babBody->addError(bab_translate("Access denied"));
					$this->accessDenied = true;
				}
			} else {
				$babBody->addError(bab_translate("Access denied"));
				$this->accessDenied = true;
			}
		}

	}

	$temp = new temp($idart);
	if ($temp->accessDenied) {
		$babBody->babpopup('');
		return;
	}
	$temp->getHtml("approb.html", "confirmarticle");
}





function confirmWaitingPost($thread, $post)
{
	global $babBody, $babDB;

	$sql = '
		SELECT thread.forum
		FROM ' . BAB_POSTS_TBL . ' post
		LEFT JOIN ' . BAB_THREADS_TBL . ' thread ON post.id_thread = thread.id
		WHERE thread.id=' . $babDB->quote($thread) . ' AND post.id=' . $babDB->quote($post);

	$thr = $babDB->db_fetch_assoc($babDB->db_query($sql));
	if ($thr === false || !bab_isAccessValid(BAB_FORUMSMAN_GROUPS_TBL, $thr['forum'])) {
		$babBody->addError(bab_translate("Access denied"));
		$babBody->babpopup('');
		return;
	}


	class confirmWaitingPostCls extends bab_confirmWaiting
	{

		var $postmessage;
		var $postsubject;
		var $postdate;
		var $postauthor;
		var $title;
		var $close;

		function confirmWaitingPostCls($thread, $post)
		{
			global $babDB;
			$this->idpost = bab_toHtml($post);
			$this->thread = bab_toHtml($thread);

			$req = "select pt.*, ft.id as forumid, ft.name as forumname from ".BAB_POSTS_TBL." pt left join ".BAB_THREADS_TBL." tt on tt.id=pt.id_thread left join ".BAB_FORUMS_TBL." ft on ft.id=tt.forum where pt.id='".$babDB->db_escape_string($post)."'";

			$arr = $babDB->db_fetch_array($babDB->db_query($req));

			$GLOBALS['babBody']->title = $arr['forumname'];
			$this->postdate = bab_toHtml(bab_strftime(bab_mktime($arr['date'])));
			$this->postauthor = bab_getForumContributor($arr['forumid'], $arr['id_author'], $arr['author']);
			$this->postauthor = bab_toHtml($this->postauthor);
			$this->postsubject = bab_toHtml($arr['subject']);

			include_once $GLOBALS['babInstallPath']."utilit/editorincl.php";
			$editor = new bab_contentEditor('bab_forum_post');
			$editor->setContent($arr['message']);
			$this->postmessage = $editor->getHtml();

			$this->close = bab_translate("Close");
			$this->action = bab_translate("Action");
			$this->confirm = bab_translate("Confirm");
			$this->refuse = bab_translate("Refuse");
			$this->modify = bab_translate("Update");
		}
	}
	$temp = new confirmWaitingPostCls($thread, $post);
	$temp->getHtml("approb.html", "confirmpost");
}



function confirmWaitingComment($idcom)
{
	global $babBody;

	class confirmWaitingCommentCls extends bab_confirmWaiting
	{
		var $action;
		var $confirm;
		var $refuse;
		var $what;
		var $idcom;
		var $message;
		var $modify;
		var $db;
		var $count;

		var $accessDenied = false;

		function confirmWaitingCommentCls($idcom)
		{
			global $babBody, $babDB;

			$this->accessDenied = false;
			
			$this->preview = bab_previewComment($idcom);

			$req = "select * from ".BAB_COMMENTS_TBL." where id='".$babDB->db_escape_string($idcom)."'";
			$res = $babDB->db_query($req);
			$this->count = $babDB->db_num_rows($res);
			if ($this->count > 0) {
				$arr = $babDB->db_fetch_array($res);
				$this->idcom = bab_toHtml($idcom);
				$this->name = bab_translate("Submiter");
				$this->modify = bab_translate("Update");
				$this->action = bab_translate("Action");
				$this->confirm = bab_translate("Confirm");
				$this->refuse = bab_translate("Refuse");
				$this->what = bab_translate("Send an email to author");
				$this->message = bab_translate("Message");
				$this->confval = 'comment';
			} else {
				$babBody->addError(bab_translate("Access denied"));
				$this->accessDenied = true;
			}
		}
	}

	$temp = new confirmWaitingCommentCls($idcom);

	if ($temp->accessDenied) {
		$babBody->babpopup('');
		return;
	}

	$temp->getHtml("approb.html", "confirmcomment");
}





/**
 * @param int	$idart
 * @param  $bconfirm
 * @param  $comment
 * @return boolean
 */
function updateConfirmationWaitingArticle($idart, $bconfirm, $comment)
{
	global $babDB;
	require_once dirname(__FILE__).'/utilit/artdraft.class.php';

	$res = $babDB->db_query("select id, idfai, id_author, id_article from ".BAB_ART_DRAFTS_TBL." where id='".$babDB->db_escape_string($idart)."'");
	$draft = new bab_ArtDraft;
	$draft->getFromIdDraft($idart);
	
	$arrschi = bab_getWaitingIdSAInstance($GLOBALS['BAB_SESS_USERID']);
	if (count($arrschi) > 0 && in_array($draft->idfai,$arrschi)) 
	{
		$bret = $bconfirm == "Y"? true: false;

		$babDB->db_query("insert into ".BAB_ART_DRAFTS_NOTES_TBL." (id_draft, content, id_author, date_note) values ('".$babDB->db_escape_string($idart)."','".$babDB->db_escape_string($comment)."','".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."', now())");

		$res = updateFlowInstance($draft->idfai, $GLOBALS['BAB_SESS_USERID'], $bret);
		switch($res) {
			case 0:
				$babDB->db_query("update ".BAB_ART_DRAFTS_TBL." set result='".BAB_ART_STATUS_NOK."', idfai='0' where id = '".$babDB->db_escape_string($idart)."'");
				if ($draft->id_article != 0) {
					$draft->log('refused', $comment);
				}
				deleteFlowInstance($draft->idfai);
				notifyArticleDraftAuthor($idart, 0);
				break;
			case 1:
				$articleid = acceptWaitingArticle($idart);
				if ($articleid == 0) {
					return false;
				}
				deleteFlowInstance($draft->idfai);
				notifyArticleDraftAuthor($idart, 1);
				bab_deleteArticleDraft($idart);
				if ($draft->id_article != 0) {
					$draft->log('accepted');
				}
				break;
			default:
				$nfusers = getWaitingApproversFlowInstance($draft->idfai, true);
				if (count($nfusers) > 0) {
					notifyArticleDraftApprovers($idart, $nfusers);
				}
				break;
		}

		bab_sitemap::clearAll();
		return true;
	}
	
	return false;
}


/**
 * @param int $idcom
 * @param $action
 * @param $send
 * @param $message
 *
 * @return boolean
 */
function updateConfirmationWaitingComment($idcom, $action, $send, $message)
{
	global $babBody, $babDB, $new, $BAB_SESS_USERID, $babAdminEmail;

	$query = 'SELECT * FROM ' . BAB_COMMENTS_TBL . ' WHERE id=' . $babDB->quote($idcom);
	$res = $babDB->db_query($query);
	if (!$res || $babDB->db_num_rows($res) <= 0) {
		return false;
	}
	$arr = $babDB->db_fetch_array($res);
	$arrschi = bab_getWaitingIdSAInstance($GLOBALS['BAB_SESS_USERID']);
	if (count($arrschi) <= 0 || !in_array($arr['idfai'], $arrschi)) {
		return false;
	}


	$bret = $action == "1"? true: false;
	$res = updateFlowInstance($arr['idfai'], $GLOBALS['BAB_SESS_USERID'], $bret);
	switch($res) {
		case 0:
			include_once $GLOBALS['babInstallPath']."utilit/delincl.php";
			$subject = bab_translate("Your comment has been refused");
			deleteFlowInstance($arr['idfai']);
			bab_deleteComments($idcom);
			break;
		case 1:
			$subject = bab_translate("Your comment has been accepted");
			deleteFlowInstance($arr['idfai']);
			$babDB->db_query("update ".BAB_COMMENTS_TBL." set confirmed='Y', idfai='0' where id = '".$babDB->db_escape_string($idcom)."'");
			break;
		default:
			$subject = bab_translate("About your comment");
			$nfusers = getWaitingApproversFlowInstance($arr['idfai'], true);
			if (count($nfusers) > 0) {
				notifyCommentApprovers($idcom, $nfusers);
			}
			break;
	}

	if ($send == '1' && $arr['email'] != '') {
		$msg = nl2br($message);
        notifyCommentAuthor($subject, $msg, $BAB_SESS_USERID, $arr['email']);
	}
	
	
	
	return true;
}


/**
 * @param int $thread
 * @param int $post
 * @return boolean
 */
function updateConfirmationWaitingPost($thread, $post)
{
	global $babBody, $babDB;

	$thread = intval($thread);
	$post = intval($post);
	if ($thread && $post) {
		$sql = '
				SELECT thread.forum
				FROM ' . BAB_POSTS_TBL . ' post
				LEFT JOIN ' . BAB_THREADS_TBL . ' thread ON post.id_thread = thread.id
				WHERE thread.id=' . $babDB->quote($thread) . ' AND post.id=' . $babDB->quote($post);

		$thr = $babDB->db_fetch_assoc($babDB->db_query($sql));
		if ($thr === false || !bab_isAccessValid(BAB_FORUMSMAN_GROUPS_TBL, $thr['forum'])) {
			return false;
		}

		$res = $babDB->db_query("select tt.forum, tt.starter, tt.notify, pt.subject from ".BAB_THREADS_TBL." tt left join ".BAB_POSTS_TBL." pt on tt.post=pt.id where tt.id='".$babDB->db_escape_string($thread)."'");
		$arrf = $babDB->db_fetch_array($res);
		$action = bab_pp('action', '');

		if ($action !== '' ) {
			if ($action == 1) {
					bab_confirmPost($arrf['forum'], $thread, $post);
			} else {
					bab_deletePost($arrf['forum'], $post);
			}
		}

	}
	
	bab_sitemap::clearAll();

	return true;
}





/* main */

$idx = bab_rp('idx', 'all');

if( '' != ($conf = bab_rp('conf')))
{
	if( $conf == 'art')
	{
		$bconfirm = bab_pp('bconfirm', 'N');
		if (!updateConfirmationWaitingArticle(bab_pp('idart'), $bconfirm, bab_pp('comment'))) {
			$babBody->addError(bab_translate('Access denied'));
			return;
		}
		$idx = 'unload';
	}
	elseif( $conf == 'com' )
	{
		if (!updateConfirmationWaitingComment(bab_pp('idcom'), bab_pp('action'), bab_pp('send'), bab_pp('message'))) {
			$babBody->addError(bab_translate('Access denied'));
			return;
		}
		$idx = 'unload';
	}
	elseif( $conf == 'post' )
	{
		if (!updateConfirmationWaitingPost(bab_pp('thread'), bab_pp('idpost'))) {
			$babBody->addError(bab_translate('Access denied'));
			return;
		}
		$idx = 'unload';
	}
	
}

switch($idx)
{
	case "unload":
		include_once $babInstallPath."utilit/uiutil.php";
		popupUnload(bab_translate("Update done"), $GLOBALS['babUrlScript']."?tg=approb&idx=all");
		exit;
		
	case "viewart":
	case "confart":
		confirmWaitingArticle(bab_gp('idart'));
		exit;
		break;

	case "viewcom":
	case "confcom":
		confirmWaitingComment(bab_gp('idcom'));
		exit;
		break;

	case "confpost":
		confirmWaitingPost(bab_gp('thread'), bab_gp('idpost'));
		exit;
		break;
	

	case "all":
	default:
		include_once $GLOBALS['babInstallPath']."utilit/userincl.php";
		if (!bab_isUserLogged()) {
			$babBody->addError(bab_translate('Access denied'));
			return;
		}

		$babBody->title = bab_translate("Approbations");
		listWaitingItems();

		$babBody->addItemMenu("all", bab_translate("Approbations"), $GLOBALS['babUrlScript']."?tg=approb&idx=all");
		break;
}

$babBody->setCurrentItemMenu($idx);
