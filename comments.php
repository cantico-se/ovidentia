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
include_once $babInstallPath."utilit/uiutil.php";
include_once $babInstallPath."utilit/mailincl.php";
include_once $babInstallPath."utilit/topincl.php";
include_once $babInstallPath."utilit/artincl.php";

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
			global $babBodyPopup;
			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_COMMENTS_TBL." where id_article='".$article."' and confirmed='Y' order by date desc";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			$this->article = $article;
			$this->alternate = 0;
			$res = $this->db->db_query("select count(*) from ".BAB_ARTICLES_TBL." where id_topic='".$topics."' and archive='Y'");
			$this->altbg = false;
			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$this->altbg = !$this->altbg;
				$arr = $this->db->db_fetch_array($this->res);
				$this->commentdate = bab_strftime(bab_mktime($arr['date']));
				$this->authorname = $arr['name'];
				$this->commenttitle = $arr['subject'];
				$this->commentbody = bab_replace($arr['message']);
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
			global $BAB_SESS_USER;
			$this->subject = bab_translate("comments-Title");
			$this->name = bab_translate("Name");
			$this->email = bab_translate("Email");
			$this->message = bab_translate("comments-Comment");
			$this->add = bab_translate("Add comment");
			$this->title = bab_translate("Article");
			$this->article = $article;
			$this->subjectval = $subject;
			$this->messageval = $message;
			$this->com = $com;
			if( empty($BAB_SESS_USER))
				{
				$this->authorname = "Anonymous";
				}
			else
				{
				$this->authorname = $BAB_SESS_USER;
				}
			$db = $GLOBALS['babDB'];
			$req = "select title from ".BAB_ARTICLES_TBL." where id='".$article."'";
			$res = $db->db_query($req);
			$arr = $db->db_fetch_array($res);
			$this->titleval = $arr['title'];
			$this->editor = bab_editor($this->messageval, 'message', 'comcreate');

			$arr = $db->db_fetch_array($db->db_query("select idsacom from ".BAB_TOPICS_TBL." where id='".$topics."'"));
			if( $arr['idsacom'] != 0 )
				$this->notcom = bab_translate("Note: for this topic, comments are moderate");
			else
				$this->notcom = "";
			}
		}

	$temp = new addCommentCls($topics, $article, $subject, $message, $com);
	$babBodyPopup->babecho(	bab_printTemplate($temp,"comments.html", "commentcreate"));
	}

function saveComment($topics, $article, $name, $subject, $message, $com, &$msgerror)
	{
	global $BAB_SESS_USER, $BAB_SESS_EMAIL;

	if( empty($subject))
		{
		$msgerror = bab_translate("comments - ERROR: You must provide a title");
		return false;
		}

	if( empty($message))
		{
		$msgerror = bab_translate("comments - ERROR: You must provide a comment");
		return false;
		}

	if( !bab_isMagicQuotesGpcOn())
		{
		$subject = addslashes($subject);
		$message = addslashes($message);
		}

	if( empty($com))
		{
		$com = 0;
		}
	$db = $GLOBALS['babDB'];
	$req = "insert into ".BAB_COMMENTS_TBL." (id_topic, id_article, id_parent, date, subject, message, name, email) values ";
	$req .= "('" .$topics. "', '" . $article.  "', '" . $com. "', now(), '" . $subject. "', '" . $message. "', '";
	if( !isset($name) || empty($name))
		{
		$req .= $BAB_SESS_USER. "', '" . $BAB_SESS_EMAIL. "')";
		}
	else
		{
		$req .= $name. "', '')";
		}

	$db->db_query($req);
	$id = $db->db_insert_id();

	$req = "select * from ".BAB_TOPICS_TBL." where id='".$topics."'";
	$res = $db->db_query($req);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		if( $arr['idsacom'] == 0 )
			{
			$db->db_query("update ".BAB_COMMENTS_TBL." set confirmed='Y' where id='".$id."'");
			return true;
			}

		include_once $GLOBALS['babInstallPath']."utilit/afincl.php";
		$idfai = makeFlowInstance($arr['idsacom'], "com-".$id);
		$db->db_query("update ".BAB_COMMENTS_TBL." set idfai='".$idfai."' where id='".$id."'");
		$nfusers = getWaitingApproversFlowInstance($idfai, true);
		notifyCommentApprovers($id, $nfusers);
		}
	return true;
	}

/* main */
if( count($babBody->topview) == 0 || !isset($babBody->topview[$topics]))
{
	$idx = 'denied';
}


if(isset($addcomment) && bab_isAccessValid(BAB_TOPICSCOM_GROUPS_TBL, $topics))
	{
	$msgerror = '';
	if( !saveComment($topics, $article, $cname, $subject, $message, $com, $msgerror))
		{
		$idx = "List";
		}
	else
		{
		$popupmessage = bab_translate("Update done");
		$idx = "unload";
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
		if( !isset($popupmessage)) { $popupmessage ='';}
		if( !isset($refreshurl)) { $refreshurl ='';}
		popupUnload($popupmessage, $refreshurl, true);
		exit;
		break;

	case "delete":
		break;

	default:
	case "List":
		if( !isset($msgerror)) { $msgerror = '';}
		$babBodyPopup = new babBodyPopup();
		$babBodyPopup->title = bab_translate("List of comments");
		$babBodyPopup->msgerror = $msgerror;
		listComments($topics, $article);
		if(!isset($subject)){ $subject = "";}
		if(!isset($message)){ $message = "";}
		addComment($topics, $article, $subject, $message, "");
		printBabBodyPopup();
		exit;
		break;
	}
?>