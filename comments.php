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

	class temp
		{
	
		var $subjecturl;
		var $subjectname;
		var $arr = array();
		var $db;
		var $count;
		var $res;
		var $article;
		var $altbg;

		function temp($topics, $article)
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
			if( $i < $this->count)
				{
				$this->altbg = !$this->altbg;
				$arr = $babDB->db_fetch_array($this->res);
				$this->commentdate = bab_toHtml(bab_strftime(bab_mktime($arr['date'])));
				if( $arr['id_author'] )
					{
					$this->authorname = bab_toHtml(bab_getUserName($arr['id_author']));
					}
				else
					{
					$this->authorname = bab_toHtml($arr['name']);
					}

				
				$this->commenttitle = bab_toHtml($arr['subject']);
				
				
				include_once $GLOBALS['babInstallPath']."utilit/editorincl.php";
				$editor = new bab_contentEditor('bab_article_comment');
				$editor->setContent($arr['message']);
				$this->commentbody = $editor->getHtml();
						
				$i++;
				return true;
				}
			else
				{
				return false;
				}
			}
		}
	
	$temp = new temp($topics, $article);
	$babBodyPopup->babecho(	bab_printTemplate($temp,"comments.html", "commentslist"));
	}

function addComment($topics, $article, $subject, $message, $com="")
	{
	global $babBodyPopup;
	
	class addCommentCls
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

		function addCommentCls($topics, $article, $subject, $message, $com)
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
			$req = "select title from ".BAB_ARTICLES_TBL." where id='".$babDB->db_escape_string($article)."'";
			$res = $babDB->db_query($req);
			$arr = $babDB->db_fetch_array($res);
			$this->titleval = bab_toHtml($arr['title']);
			
			include_once $GLOBALS['babInstallPath']."utilit/editorincl.php";
			
			$editor = new bab_contentEditor('bab_article_comment');
			$editor->setContent($message);
			$editor->setFormat('html');
			$this->editor = $editor->getEditor();

			$arr = $babDB->db_fetch_array($babDB->db_query("select idsacom from ".BAB_TOPICS_TBL." where id='".$babDB->db_escape_string($topics)."'"));
			if( $arr['idsacom'] != 0 )
				{
				$this->notcom = bab_translate("Note: for this topic, comments are moderate");
				}
			else
				{
				$this->notcom = '';
				}
			}
		}

	$temp = new addCommentCls($topics, $article, $subject, $message, $com);
	$babBodyPopup->babecho(	bab_printTemplate($temp,"comments.html", "commentcreate"));
	}

function saveComment($topics, $article, $subject, $com, &$msgerror)
	{
	global $babDB, $BAB_SESS_USER, $BAB_SESS_EMAIL, $BAB_SESS_USERID;

	if( empty($subject))
		{
		$msgerror = bab_translate("comments - ERROR: You must provide a title");
		return false;
		}
		
	include_once $GLOBALS['babInstallPath']."utilit/editorincl.php";
			
	$editor = new bab_contentEditor('bab_article_comment');
	$message = $editor->getContent();
	

	if( empty($message))
		{
		$msgerror = bab_translate("comments - ERROR: You must provide a comment");
		return false;
		}

	if( empty($com))
		{
		$com = 0;
		}
	
	$req = "insert into ".BAB_COMMENTS_TBL." (id_topic, id_article, id_parent, date, subject, message, id_author, name, email) values ";
	$req .= "(
		'" .$babDB->db_escape_string($topics). "', 
		'" . $babDB->db_escape_string($article).  "', 
		'" . $babDB->db_escape_string($com). "', 
		now(), 
		'" . $babDB->db_escape_string($subject). "', 
		'" . $babDB->db_escape_string($message). "', '
	";

	if( empty($BAB_SESS_USER))
		{
		$name = bab_translate("Anonymous");
		$email = '';
		$idauthor = 0;
		}
	else
		{
		$name = $BAB_SESS_USER;
		$email = $BAB_SESS_EMAIL;
		$idauthor = $BAB_SESS_USERID;
		}

	$req .= $babDB->db_escape_string($idauthor). "', '" .$babDB->db_escape_string($name). "', '" . $babDB->db_escape_string($email). "')";

	$babDB->db_query($req);
	$id = $babDB->db_insert_id();

	$req = "select * from ".BAB_TOPICS_TBL." where id='".$babDB->db_escape_string($topics)."'";
	$res = $babDB->db_query($req);
	if( $res && $babDB->db_num_rows($res) > 0)
		{
		$arr = $babDB->db_fetch_array($res);

		if( $arr['idsacom'] != 0 )
			{
			include_once $GLOBALS['babInstallPath']."utilit/afincl.php";
			if( $arr['auto_approbation'] == 'Y' )
				{
				$idfai = makeFlowInstance($arr['idsacom'], "com-".$id, $GLOBALS['BAB_SESS_USERID']);
				}
			else
				{
				$idfai = makeFlowInstance($arr['idsacom'], "com-".$id);
				}
			}

		if( $arr['idsacom'] == 0 || $idfai === true)
			{
			$babDB->db_query("update ".BAB_COMMENTS_TBL." set confirmed='Y' where id='".$babDB->db_escape_string($id)."'");
			}
		elseif(!empty($idfai))
			{
			$babDB->db_query("update ".BAB_COMMENTS_TBL." set idfai='".$babDB->db_escape_string($idfai)."' where id='".$babDB->db_escape_string($id)."'");
			$nfusers = getWaitingApproversFlowInstance($idfai, true);
			notifyCommentApprovers($id, $nfusers);
			}
		}
	return true;
	}

/* main */
$topics = bab_rp('topics', 0);
$article = bab_rp('article', 0);
$msgerror = '';
$popupmessage = '';

if(!bab_isAccessValid(BAB_TOPICSVIEW_GROUPS_TBL, $topics))
{
	$idx = 'denied';
}
else
{
if(isset($_POST['addcomment']) && bab_isAccessValid(BAB_TOPICSCOM_GROUPS_TBL, $topics))
	{
	if( !saveComment($topics, $article, bab_pp('subject'), bab_pp('com'), $msgerror))
		{
		$idx = "List";
		}
	else
		{
		$popupmessage = bab_translate("Update done");
		$idx = "unload";
		}
	}
}

switch($idx)
	{
	case "denied":
		$babBodyPopup = new babBodyPopup();
		$babBodyPopup->msgerror = bab_translate("Access denied");
		printBabBodyPopup();
		exit;
		break;

	case "unload":
		include_once $babInstallPath."utilit/uiutil.php";
		$refreshurl = bab_rp('refreshurl');
		popupUnload($popupmessage, $refreshurl, true);
		exit;
		break;

	case "delete":
		break;

	default:
	case "List":
		$babBodyPopup = new babBodyPopup();
		$babBodyPopup->title = bab_translate("List of comments");
		$babBodyPopup->msgerror = $msgerror;
		listComments($topics, $article);
		addComment($topics, $article, bab_pp('subject'), bab_pp('message'), '');
		printBabBodyPopup();
		exit;
		break;
	}
?>