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
include $babInstallPath."utilit/imgincl.php";

class categoriesHierarchy
{

	var $parentscount;
	var $parentname;
	var $parenturl;
	var $burl;
	var $topics;

	function categoriesHierarchy($topics)
		{
		global $babDB;

		$this->topics = $topics;

		$this->arrparents[] = $topics;
		list($cat) = $babDB->db_fetch_row($babDB->db_query("select id_cat from ".BAB_TOPICS_TBL." where id='".$topics."'"));
		$this->arrparents[] = $cat;
		$res = $babDB->db_query("select id_parent from ".BAB_TOPICS_CATEGORIES_TBL." where id='".$cat."'");
		while($arr = $babDB->db_fetch_array($res))
			{
			if( $arr['id_parent'] == 0 )
				break;
			$this->arrparents[] = $arr['id_parent'];
			$res = $babDB->db_query("select id_parent from ".BAB_TOPICS_CATEGORIES_TBL." where id='".$arr['id_parent']."'");
			}

		$this->arrparents[] = 0;

		$this->parentscount = count($this->arrparents);
		$this->arrparents = array_reverse($this->arrparents);
		}

	function getnextparent()
		{
		static $i = 0;
		if( $i < $this->parentscount)
			{
			if( $i == $this->parentscount - 1 )
				{
				$this->parentname = bab_getCategoryTitle($this->arrparents[$i]);
				$this->parenturl = "";
				$this->burl = false;
				}
			else
				{
				$this->burl = true;
				if( $this->arrparents[$i] == 0 )
					$this->parentname = bab_translate("Top");
				else
					$this->parentname = bab_getTopicCategoryTitle($this->arrparents[$i]);
				$this->parenturl = $GLOBALS['babUrlScript']."?tg=topusr&cat=".$this->arrparents[$i];
				}
			$i++;
			return true;
			}
		else{ $i = 0;
			return false;}
		}
}



function viewCategoriesHierarchy($topics)
	{
	global $babBody;
	class tempvch extends categoriesHierarchy
		{

		function tempvch($topics)
			{
			$this->categoriesHierarchy($topics);
			}
		}

	$temp = new tempvch($topics);
	$babBody->babecho(	bab_printTemplate($temp,"articles.html", "categorieshierarchy"));
	}


function bab_getCategoryTitle($id)
	{
	$db = $GLOBALS['babDB'];
	$query = "select category from ".BAB_TOPICS_TBL." where id='$id'";
	$res = $db->db_query($query);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		return $arr['category'];
		}
	else
		{
		return "";
		}
	}

function bab_getTopicCategoryTitle($id)
	{
	$db = $GLOBALS['babDB'];
	$query = "select title from ".BAB_TOPICS_CATEGORIES_TBL." where id='$id'";
	$res = $db->db_query($query);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		return $arr['title'];
		}
	else
		{
		return "";
		}
	}

function bab_getArticleTitle($article)
	{
	$db = $GLOBALS['babDB'];
	$query = "select title from ".BAB_ARTICLES_TBL." where id='$article'";
	$res = $db->db_query($query);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		return $arr['title'];
		}
	else
		{
		return "";
		}
	}

function bab_getArticleDate($article)
	{
	$db = $GLOBALS['babDB'];
	$query = "select date from ".BAB_ARTICLES_TBL." where id='$article'";
	$res = $db->db_query($query);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		return bab_strftime(bab_mktime($arr['date']));
		}
	else
		{
		return "";
		}
	}

function bab_getArticleAuthor($article)
	{
	$db = $GLOBALS['babDB'];
	$query = "select id_author from ".BAB_ARTICLES_TBL." where id='$article'";
	$res = $db->db_query($query);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		$query = "select firstname, lastname from ".BAB_USERS_TBL." where id='".$arr['id_author']."'";
		$res = $db->db_query($query);
		if( $res && $db->db_num_rows($res) > 0)
			{
			$arr = $db->db_fetch_array($res);
			return bab_composeUserName($arr['firstname'], $arr['lastname']);
			}
		else
			return bab_translate("Anonymous");
		}
	else
		{
		return bab_translate("Anonymous");
		}
	}

function bab_getCommentTitle($com)
	{
	$db = $GLOBALS['babDB'];
	$query = "select subject from ".BAB_COMMENTS_TBL." where id='$com'";
	$res = $db->db_query($query);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		return $arr['subject'];
		}
	else
		{
		return "";
		}
	}


function notifyArticleHomePage($top, $title, $homepage0, $homepage1)
	{
	global $babBody, $BAB_SESS_USER, $BAB_SESS_EMAIL, $babAdminEmail, $babInstallPath;

	if(!class_exists("tempa"))
		{
		class tempa
			{
			var $articletitle;
			var $message;
			var $from;
			var $author;
			var $category;
			var $categoryname;
			var $title;
			var $site;
			var $sitename;
			var $date;
			var $dateval;


			function tempa($top, $title, $homepage0, $homepage1)
				{
				global $BAB_SESS_USER, $BAB_SESS_EMAIL, $babSiteName;
				$this->articletitle = $title;
				$this->message = bab_translate("A new article is proposed for home page(s)"). ": ";
				if( $homepage1 == "1" )
					$this->message .= bab_translate("Registered users");
				$this->message .= " - ";
				if( $homepage0 == "2" )
					$this->message .= bab_translate("Unregistered users");

				$this->from = bab_translate("Author");
				$this->category = bab_translate("Topic");
				$this->title = bab_translate("Title");
				$this->categoryname = $top;
				$this->site = bab_translate("Web site");
				$this->sitename = $babSiteName;
				$this->date = bab_translate("Date");
				$this->dateval = bab_strftime(mktime());
				if( !empty($BAB_SESS_USER))
					$this->author = $BAB_SESS_USER;
				else
					$this->author = bab_translate("Unknown user");

				if( !empty($BAB_SESS_EMAIL))
					$this->authoremail = $BAB_SESS_EMAIL;
				else
					$this->authoremail = "";
				}
			}
		}
    $mail = bab_mail();
	if( $mail == false )
		return;

	$db = $GLOBALS['babDB'];
	$sql = "select * from ".BAB_USERS_GROUPS_TBL." where id_group='3'";
	$result=$db->db_query($sql);
	if( $result && $db->db_num_rows($result) > 0 )
		{
		while( $arr = $db->db_fetch_array($result))
			{
			$sql = "select email, firstname, lastname from ".BAB_USERS_TBL." where id='".$arr['id_object']."'";
			$res=$db->db_query($sql);
			$r = $db->db_fetch_array($res);
			$mail->mailTo($r['email'], bab_composeUserName($r['firstname'] , $r['lastname']));
			}
		}

	$mail->mailFrom($babAdminEmail, bab_translate("Ovidentia Administrator"));
	$mail->mailSubject(bab_translate("New article for home page"));

	$tempa = new tempa($top, $title, $homepage0, $homepage1);
	$message = bab_printTemplate($tempa,"mailinfo.html", "articlehomepage");
	$mail->mailBody($message, "html");

	$message = bab_printTemplate($tempa,"mailinfo.html", "articlehomepagetxt");
	$mail->mailAltBody($message);

	$mail->send();
	}


function notifyArticleApprovers($id, $users)
	{
	global $babBody, $BAB_SESS_USER, $BAB_SESS_EMAIL, $babAdminEmail, $babInstallPath;

	if(!class_exists("tempa"))
		{
		class tempa
			{
			var $articletitle;
			var $message;
			var $from;
			var $author;
			var $category;
			var $categoryname;
			var $title;
			var $site;
			var $sitename;
			var $date;
			var $dateval;


			function tempa($id)
				{
				global $BAB_SESS_USER, $BAB_SESS_EMAIL, $babSiteName;
				$db = $GLOBALS['babDB'];
				$arr = $db->db_fetch_array($db->db_query("select * from ".BAB_ARTICLES_TBL." where id='".$id."'"));
				$this->articletitle = $arr['title'];
				$this->message = bab_translate("A new article is waiting for you");
				$this->from = bab_translate("Author");
				$this->category = bab_translate("Topic");
				$this->title = bab_translate("Title");
				$rr = $db->db_fetch_array($db->db_query("select category from ".BAB_TOPICS_TBL." where id='".$arr['id_topic']."'"));
				$this->categoryname = $rr['category'];
				$this->site = bab_translate("Web site");
				$this->sitename = $babSiteName;
				$this->date = bab_translate("Date");
				$this->dateval = bab_strftime(mktime());
				if( !empty($arr['id_author']) && $arr['id_author'] != 0)
					{
					$this->author = bab_getUserName($arr['id_author']);
					$this->authoremail = bab_getUserEmail($arr['id_author']);
					}
				else
					{
					$this->author = bab_translate("Unknown user");
					$this->authoremail = "";
					}
				}
			}
		}

	$mail = bab_mail();
	if( $mail == false )
		return;

	for( $i=0; $i < count($users); $i++)
		$mail->mailTo(bab_getUserEmail($users[$i]));
	$mail->mailFrom($babAdminEmail, bab_translate("Ovidentia Administrator"));
	$mail->mailSubject(bab_translate("New waiting article"));

	$tempa = new tempa($id);
	$message = bab_printTemplate($tempa,"mailinfo.html", "articlewait");
	$mail->mailBody($message, "html");

	$message = bab_printTemplate($tempa,"mailinfo.html", "articlewaittxt");
	$mail->mailAltBody($message);

	$mail->send();
	}

function notifyCommentApprovers($idcom, $nfusers)
	{
	global $babBody, $BAB_SESS_USER, $BAB_SESS_EMAIL, $babAdminEmail, $babInstallPath;

	if(!class_exists("tempa"))
		{
		class tempca
			{
			var $article;
			var $articlename;
			var $message;
			var $from;
			var $author;
			var $category;
			var $categoryname;
			var $subject;
			var $subjectname;
			var $title;
			var $site;
			var $sitename;
			var $date;
			var $dateval;

			function tempca($idcom)
				{
				global $BAB_SESS_USER, $BAB_SESS_EMAIL, $babSiteName;
				$db = $GLOBALS['babDB'];
				$arr = $db->db_fetch_array($db->db_query("select * from ".BAB_COMMENTS_TBL." where id='".$idcom."'"));

				$this->message = bab_translate("A new comment is waiting for you");
				$this->from = bab_translate("Author");
				$this->subject = bab_translate("Subject");
				$this->subjectname = $arr['subject'];
				$this->article = bab_translate("Article");
				$this->articlename = bab_getArticleTitle($arr['id_article']);
				$this->category = bab_translate("Topic");
				$this->categoryname = bab_getCategoryTitle($arr['id_topic']);
				$this->site = bab_translate("Web site");
				$this->sitename = $babSiteName;
				$this->date = bab_translate("Date");
				$this->dateval = bab_strftime(mktime());
				if( !empty($BAB_SESS_USER))
					$this->author = $BAB_SESS_USER;
				else
					$this->author = bab_translate("Unknown user");

				if( !empty($BAB_SESS_EMAIL))
					$this->authoremail = $BAB_SESS_EMAIL;
				else
					$this->authoremail = "";
				}
			}
		
		$mail = bab_mail();
		if( $mail == false )
			return;

		for( $i=0; $i < count($nfusers); $i++)
			$mail->mailTo(bab_getUserEmail($nfusers[$i]));
		$mail->mailFrom($babAdminEmail, bab_translate("Ovidentia Administrator"));
		$mail->mailSubject(bab_translate("New waiting comment"));

		$tempa = new tempca($idcom);
		$message = bab_printTemplate($tempa,"mailinfo.html", "commentwait");
		$mail->mailBody($message, "html");

		$message = bab_printTemplate($tempa,"mailinfo.html", "commentwaittxt");
		$mail->mailAltBody($message);
		$mail->send();
		}
	}
function notifyArticleGroupMembers($topicname, $topics, $title, $author, $what, $restriction)
	{
	global $babBody, $BAB_SESS_USER, $BAB_SESS_EMAIL, $babAdminEmail, $babInstallPath;

	if(!class_exists("tempcc"))
		{
		class tempcc
			{
			var $message;
			var $from;
			var $author;
			var $about;
			var $title;
			var $titlename;
			var $site;
			var $sitename;
			var $date;
			var $dateval;


			function tempcc($topicname, $title, $author, $msg)
				{
				global $BAB_SESS_USER, $BAB_SESS_EMAIL, $babSiteName;
				$this->topic = bab_translate("Topic");
				$this->topicname = $topicname;
				$this->title = bab_translate("Title");
				$this->authorname = $author;
				$this->author = bab_translate("Author");
				$this->titlename = $title;
				$this->site = bab_translate("Web site");
				$this->sitename = $babSiteName;
				$this->date = bab_translate("Date");
				$this->dateval = bab_strftime(mktime());
				$this->message = $msg;
				}
			}
		}	
    $mail = bab_mail();
	if( $mail == false )
		return;

	if( $what == 'mod' )
		$msg = bab_translate("An article has been modified");
	else
		$msg = bab_translate("An article has been published");


    $mail->mailFrom($babAdminEmail, bab_translate("Ovidentia Administrator"));
    $mail->mailSubject($msg);

	$tempc = new tempcc($topicname, $title, $author, $msg);
	$message = bab_printTemplate($tempc,"mailinfo.html", "notifyarticle");

	$messagetxt = bab_printTemplate($tempc,"mailinfo.html", "notifyarticletxt");

	$sep = ',';
	if( !empty($restriction))
	{
		if( strchr($restriction, ","))
			$sep = ',';
		else
			$sep = '&';
		$arrres = explode($sep, $restriction);			
	}

	$db = $GLOBALS['babDB'];
	$res = $db->db_query("select id_group from ".BAB_TOPICSVIEW_GROUPS_TBL." where  id_object='".$topics."'");
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
				while($arr = $db->db_fetch_array($res2))
					{
					if( !empty($restriction))
						$add = bab_articleAccessByRestriction($restriction, $arr['id']);
					else
						$add = true;

					if( $add )
						{
						$mail->mailTo($arr['email'], bab_composeUserName($arr['firstname'],$arr['lastname']));
						$count++;
						}

					while(($arr = $db->db_fetch_array($res2)) && $count < 25)
						{
						if( !empty($restriction))
							$add = bab_articleAccessByRestriction($restriction, $arr['id']);
						else
							$add = true;
						if( $add )
							{
							$mail->mailBcc($arr['email'], bab_composeUserName($arr['firstname'],$arr['lastname']));
							$count++;
							}
						}

					$mail->mailBody($message, "html");
					$mail->mailAltBody($messagetxt);
					$mail->send();
					$mail->clearBcc();
					$mail->clearTo();
					$count = 0;
					}

				}	
			}
		}	
	}
?>
