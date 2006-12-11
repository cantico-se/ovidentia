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

function bab_deleteDraftFiles($idart)
{
	global $babDB;
	$fullpath = bab_getUploadDraftsPath();
	$res = $babDB->db_query("select * from ".BAB_ART_DRAFTS_FILES_TBL." where id_draft='".$babDB->db_escape_string($idart)."'");
	while( $arr = $babDB->db_fetch_array($res))
		{
		unlink($fullpath.$arr['id_draft'].",".$arr['name']);
		}

	$babDB->db_query("delete from ".BAB_ART_DRAFTS_FILES_TBL." where id_draft='".$babDB->db_escape_string($idart)."'");
}

function bab_deleteArticleFiles($idart)
{
	global $babDB;
	$fullpath = bab_getUploadArticlesPath();
	$res = $babDB->db_query("select * from ".BAB_ART_FILES_TBL." where id_article='".$babDB->db_escape_string($idart)."'");
	while( $arr = $babDB->db_fetch_array($res))
		{
		unlink($fullpath.$arr['id_article'].",".$arr['name']);
		}

	$babDB->db_query("delete from ".BAB_ART_FILES_TBL." where id_article='".$babDB->db_escape_string($idart)."'");
}

function bab_getUploadDraftsPath()
{
	if( substr($GLOBALS['babUploadPath'], -1) == "/" )
		{
		$path = $GLOBALS['babUploadPath'];
		}
	else
		{
		$path = $GLOBALS['babUploadPath']."/";
		}

	$path = $path."drafts/";

	if(!is_dir($path) && !bab_mkdir($path, $GLOBALS['babMkdirMode']))
		{
		return false;
		}
	return $path;
}

function bab_getUploadArticlesPath()
{
	if( substr($GLOBALS['babUploadPath'], -1) == "/" )
		{
		$path = $GLOBALS['babUploadPath'];
		}
	else
		{
		$path = $GLOBALS['babUploadPath']."/";
		}

	$path = $path."articles/";

	if(!is_dir($path) && !bab_mkdir($path, $GLOBALS['babMkdirMode']))
		{
		return false;
		}
	return $path;
}


function notifyArticleDraftApprovers($id, $users)
	{
	global $babDB, $babBody, $BAB_SESS_USER, $BAB_SESS_EMAIL, $babAdminEmail, $babInstallPath;

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
				global $babDB, $BAB_SESS_USER, $BAB_SESS_EMAIL, $babSiteName;
				$arr = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_ART_DRAFTS_TBL." where id='".$babDB->db_escape_string($id)."'"));
				$this->articletitle = $arr['title'];
				$this->articleurl = $GLOBALS['babUrlScript']."?tg=login&cmd=detect&referer=".urlencode($GLOBALS['babUrlScript']."?tg=approb&idx=all");
				$this->message = bab_translate("A new article is waiting for you");
				$this->from = bab_translate("Author");
				$this->category = bab_translate("Topic");
				$this->title = bab_translate("Title");
				$this->categoryname = viewCategoriesHierarchy_txt($arr['id_topic']);
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

	if( count($users) > 0 )
		{
		$sql = "select email from ".BAB_USERS_TBL." where id IN (".$babDB->quote($users).")";
		$result=$babDB->db_query($sql);
		while( $arr = $babDB->db_fetch_array($result))
			{
			$mail->mailBcc($arr['email']);
			}
		}
	$mail->mailFrom($babAdminEmail, $GLOBALS['babAdminName']);
	$mail->mailSubject(bab_translate("New waiting article"));

	$tempa = new tempa($id);
	$message = $mail->mailTemplate(bab_printTemplate($tempa,"mailinfo.html", "articlewait"));
	$mail->mailBody($message, "html");

	$message = bab_printTemplate($tempa,"mailinfo.html", "articlewaittxt");
	$mail->mailAltBody($message);

	$mail->send();
	}

function notifyArticleDraftAuthor($idart, $what)
	{
	global $babBody, $babDB, $babAdminEmail;

	if(!class_exists("clsNotifyArticleDraftAuthor"))
		{
		class clsNotifyArticleDraftAuthor
			{
			var $titlename;
			var $about;
			var $from;
			var $author;
			var $category;
			var $categoryname;
			var $title;
			var $site;
			var $sitename;
			var $date;
			var $dateval;


			function clsNotifyArticleDraftAuthor($id, $what, $title, $topic, $authorname)
				{
				global $BAB_SESS_USER, $BAB_SESS_EMAIL, $babSiteName;
				$this->titlename = $title;
				$this->articleurl = $GLOBALS['babUrlScript']."?tg=login&cmd=detect&referer=".urlencode($GLOBALS['babUrlScript']."?tg=approb&idx=all");
				if( $what == 0 )
					{
					$this->about = bab_translate("Your article has been refused");
					}
				else
					{
					$this->about = bab_translate("Your article has been accepted");
					}
				$this->message = "";
				$this->from = bab_translate("Author");
				$this->category = bab_translate("Topic");
				$this->title = bab_translate("Title");
				$this->categoryname = viewCategoriesHierarchy_txt($topic);
				$this->site = bab_translate("Web site");
				$this->sitename = $babSiteName;
				$this->date = bab_translate("Date");
				$this->dateval = bab_strftime(mktime());
				$this->author = $authorname;
				}
			}
		}
	
    $mail = bab_mail();
	if( $mail == false )
		return;

	$arr = $babDB->db_fetch_array($babDB->db_query("select title, id_topic, id_author from ".BAB_ART_DRAFTS_TBL." where id='".$babDB->db_escape_string($idart)."'"));

	if( !empty($arr['id_author']) && $arr['id_author'] != 0)
		{
		$authorname = bab_getUserName($arr['id_author']);
		$authoremail = bab_getUserEmail($arr['id_author']);
		$mail->mailTo($authoremail, $authorname);
		$mail->mailFrom($GLOBALS['babAdminEmail'], $GLOBALS['babAdminName']);

		$tempc = new clsNotifyArticleDraftAuthor($idart, $what, $arr['title'], $arr['id_topic'], $authorname);
		$message = $mail->mailTemplate(bab_printTemplate($tempc,"mailinfo.html", "confirmarticle"));
		$mail->mailSubject($tempc->about);
		$mail->mailBody($message, "html");

		$message = bab_printTemplate($tempc,"mailinfo.html", "confirmarticletxt");
		$mail->mailAltBody($message);
		$mail->send();
		}
	}


function notifyArticleHomePage($top, $title, $homepage0, $homepage1)
	{
	global $babBody, $babDB, $BAB_SESS_USER, $BAB_SESS_EMAIL, $babAdminEmail, $babInstallPath;

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

	$sql = "select email, firstname, lastname from ".BAB_USERS_TBL." ut LEFT JOIN ".BAB_USERS_GROUPS_TBL." ugt on ut.id=ugt.id_object where id_group='3'";
	$result=$babDB->db_query($sql);
	while( $arr = $babDB->db_fetch_array($result))
		{
		$mail->mailBcc($arr['email'], bab_composeUserName($arr['firstname'] , $arr['lastname']));
		}

	$mail->mailFrom($babAdminEmail, $GLOBALS['babAdminName']);
	$mail->mailSubject(bab_translate("New article for home page"));

	$tempa = new tempa($top, $title, $homepage0, $homepage1);
	$message = $mail->mailTemplate(bab_printTemplate($tempa,"mailinfo.html", "articlehomepage"));
	$mail->mailBody($message, "html");

	$message = bab_printTemplate($tempa,"mailinfo.html", "articlehomepagetxt");
	$mail->mailAltBody($message);

	$mail->send();
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


			function tempcc($topicname, $title, $author, $msg,$topics)
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
				$this->linkurl = $GLOBALS['babUrlScript']."?tg=login&cmd=detect&referer=".urlencode($GLOBALS['babUrlScript']."?tg=articles&topics=".$topics);
				$this->linkname = viewCategoriesHierarchy_txt($topics);
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


    $mail->mailFrom($babAdminEmail, $GLOBALS['babAdminName']);
    $mail->mailSubject($msg);

	$tempc = new tempcc($topicname, $title, $author, $msg,$topics);
	$message = $mail->mailTemplate(bab_printTemplate($tempc,"mailinfo.html", "notifyarticle"));

	$messagetxt = bab_printTemplate($tempc,"mailinfo.html", "notifyarticletxt");

	$mail->mailBody($message, "html");
	$mail->mailAltBody($messagetxt);

	$sep = ',';
	if( !empty($restriction))
	{
		if( strchr($restriction, ","))
			$sep = ',';
		else
			$sep = '&';
		$arrres = explode($sep, $restriction);			
	}


	include_once $babInstallPath."admin/acl.php";
	$users = aclGetAccessUsers(BAB_TOPICSVIEW_GROUPS_TBL, $topics);

	$arrusers = array();
	$count = 0;
	foreach($users as $id => $arr)
		{
		if( count($arrusers) == 0 || !in_array($id, $arrusers))
			{
			$arrusers[] = $id;
			if( !empty($restriction))
				$add = bab_articleAccessByRestriction($restriction, $id);
			else
				$add = true;
			if( $add )
				{
				$mail->mailBcc($arr['email'], $arr['name']);
				$count++;
				}
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


function notifyCommentApprovers($idcom, $nfusers)
	{
	global $babDB, $babBody, $BAB_SESS_USER, $BAB_SESS_EMAIL, $babAdminEmail, $babInstallPath;

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
				global $babDB, $BAB_SESS_USER, $BAB_SESS_EMAIL, $babSiteName;
				$arr = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_COMMENTS_TBL." where id='".$babDB->db_escape_string($idcom)."'"));

				$this->message = bab_translate("A new comment is waiting for you");
				$this->from = bab_translate("Author");
				$this->subject = bab_translate("Subject");
				$this->subjectname = $arr['subject'];
				$this->subjecturl = $GLOBALS['babUrlScript']."?tg=login&cmd=detect&referer=".urlencode($GLOBALS['babUrlScript']."?tg=waiting&idx=WaitingC&topics=".$arr['id_topic']."&article=".$arr['id_article']);
				$this->article = bab_translate("Article");
				$this->articlename = bab_getArticleTitle($arr['id_article']);
				$this->category = bab_translate("Topic");
				$this->categoryname = viewCategoriesHierarchy_txt($arr['id_topic']);
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

		if( count($nfusers) > 0 )
			{
			$sql = "select email from ".BAB_USERS_TBL." where id IN (".$babDB->quote($nfusers).")";
			$result=$babDB->db_query($sql);
			while( $arr = $babDB->db_fetch_array($result))
				{
				$mail->mailBcc($arr['email']);
				}
			}
		$mail->mailFrom($babAdminEmail, $GLOBALS['babAdminName']);
		$mail->mailSubject(bab_translate("New waiting comment"));

		$tempa = new tempca($idcom);
		$message = $mail->mailTemplate(bab_printTemplate($tempa,"mailinfo.html", "commentwait"));
		$mail->mailBody($message, "html");

		$message = bab_printTemplate($tempa,"mailinfo.html", "commentwaittxt");
		$mail->mailAltBody($message);
		$mail->send();
		}
	}


function acceptWaitingArticle($idart)
{
	global $babBody, $babDB;

	$res = $babDB->db_query("select adt.*, tt.category as topicname, tt.allow_attachments, tct.id_dgowner, tt.busetags from ".BAB_ART_DRAFTS_TBL." adt left join ".BAB_TOPICS_TBL." tt on adt.id_topic=tt.id left join ".BAB_TOPICS_CATEGORIES_TBL." tct on tt.id_cat=tct.id  where adt.id='".$babDB->db_escape_string($idart)."'");
	if( $res && $babDB->db_num_rows($res) > 0 )
		{
		include_once $GLOBALS['babInstallPath']."utilit/imgincl.php";
		$arr = $babDB->db_fetch_array($res);

		if( $arr['id_article'] != 0 )
			{
			$articleid = $arr['id_article'];
			$req = "update ".BAB_ARTICLES_TBL." set id_modifiedby='".$babDB->db_escape_string($arr['id_author'])."', date_archiving='".$babDB->db_escape_string($arr['date_archiving'])."', date_publication='".$babDB->db_escape_string($arr['date_publication'])."', restriction='".$babDB->db_escape_string($arr['restriction'])."', lang='".$babDB->db_escape_string($arr['lang'])."'";
			if( $arr['update_datemodif'] != 'N')
				{
				$req .= ", date_modification=now()";
				}
			$req .= " where id='".$babDB->db_escape_string($articleid)."'";
			$babDB->db_query($req);
			bab_deleteArticleFiles($articleid);
			}
		else
			{
			$req = "insert into ".BAB_ARTICLES_TBL." (id_topic, id_author, date, date_publication, date_archiving, date_modification, restriction, lang) values ";
			$req .= "('" .$babDB->db_escape_string($arr['id_topic']). "', '".$babDB->db_escape_string($arr['id_author']). "', now()";

			if( $arr['date_publication'] == '0000-00-00 00:00:00' )	
				{
				$req .= ", now()";
				}
			else
				{
				$req .= ", '".$babDB->db_escape_string($arr['date_publication'])."'";
				}
			$req .= ", '".$babDB->db_escape_string($arr['date_archiving'])."', now(), '".$babDB->db_escape_string($arr['restriction'])."', '".$babDB->db_escape_string($arr['lang']). "')";
			$babDB->db_query($req);
			$articleid = $babDB->db_insert_id();
			}

		$GLOBALS['babWebStat']->addNewArticle($arr['id_dgowner']);


		$head = imagesUpdateLink($arr['head'], $idart."_draft_", $articleid."_art_" );
		$body = imagesUpdateLink($arr['body'], $idart."_draft_", $articleid."_art_" );

		$req = "update ".BAB_ARTICLES_TBL." set head='".$babDB->db_escape_string($head)."', body='".$babDB->db_escape_string($body)."', title='".$babDB->db_escape_string($arr['title'])."' where id='".$babDB->db_escape_string($articleid)."'";
		$res = $babDB->db_query($req);

		/* move attachements */
		if( $arr['allow_attachments'] ==  'Y' )
			{
			$res = $babDB->db_query("select * from ".BAB_ART_DRAFTS_FILES_TBL." where id_draft='".$babDB->db_escape_string($idart)."'");
			$pathdest = bab_getUploadArticlesPath();
			$pathorg = bab_getUploadDraftsPath();
			$files_to_index = array();
			$files_to_insert = array();

			include_once $GLOBALS['babInstallPath']."utilit/indexincl.php";

			while($rr = $babDB->db_fetch_array($res))
				{
				if( copy($pathorg.$idart.",".$rr['name'], $pathdest.$articleid.",".$rr['name']))
					{
					// index files
					$files_to_index[] = $pathdest.$articleid.",".$rr['name'];

					// inserts
					$files_to_insert[] = array(
							'id_article'	=> $articleid,
							'name'			=> $rr['name'],
							'description'	=> $rr['description'],
							'ordering'	=> $rr['ordering']
						);
					}
				}

			bab_debug($files_to_index);

			$index_status = bab_indexOnLoadFiles($files_to_index , 'bab_art_files');

			foreach($files_to_insert as $arrf) {
					$babDB->db_query(
					
					"INSERT INTO ".BAB_ART_FILES_TBL." 
						(id_article, name, description, ordering, index_status) 
					VALUES 
						(
						'".$babDB->db_escape_string($arrf['id_article'])."', 
						'".$babDB->db_escape_string($arrf['name'])."', 
						'".$babDB->db_escape_string($arrf['description'])."', 
						'".$babDB->db_escape_string($arrf['ordering'])."', 
						'".$babDB->db_escape_string($index_status)."' 
						)
					");
				}
			}

		$babDB->db_query("delete from ".BAB_ART_TAGS_TBL." where id_art='".$babDB->db_escape_string($articleid)."'");
		if( $arr['busetags'] ==  'Y' )
			{
			$res = $babDB->db_query("select id_tag from ".BAB_ART_DRAFTS_TAGS_TBL." where id_draft='".$babDB->db_escape_string($idart)."'");
			while($rr = $babDB->db_fetch_array($res))
				{
				$babDB->db_query("insert into ".BAB_ART_TAGS_TBL." (id_art ,id_tag) values ('".$babDB->db_escape_string($articleid)."','".$babDB->db_escape_string($rr['id_tag'])."')");
				}
			}
		$babDB->db_query("delete from ".BAB_ART_DRAFTS_TAGS_TBL." where id_draft='".$babDB->db_escape_string($idart)."'");		

		if( $arr['id_author'] == 0 || (($artauthor = bab_getUserName($arr['id_author'])) == ''))
			{
			$artauthor = bab_translate("Anonymous");
			}
		if( $arr['notify_members'] == "Y" && bab_mktime($arr['date_publication']) <= mktime())
			{
			notifyArticleGroupMembers($arr['topicname'], $arr['id_topic'], $arr['title'], $artauthor, 'add', $arr['restriction']);
			}

		if( $arr['hpage_private'] == "Y" || $arr['hpage_public'] == "Y" )
			{
			if( $arr['hpage_private'] == "Y")
				{
				$res = $babDB->db_query("insert into ".BAB_HOMEPAGES_TBL." (id_article, id_site, id_group) values ('" .$babDB->db_escape_string($articleid). "', '" . $babDB->db_escape_string($babBody->babsite['id']). "', '1')");
				}

			if( $arr['hpage_public'] == "Y" )
				{
				$res = $babDB->db_query("insert into ".BAB_HOMEPAGES_TBL." (id_article, id_site, id_group) values ('" .$babDB->db_escape_string($articleid). "', '" . $babDB->db_escape_string($babBody->babsite['id']). "', '2')");
				}

			notifyArticleHomePage($arr['topicname'], $arr['title'], ($arr['hpage_public'] == "Y"? 2:0), ($arr['hpage_private'] == "Y"?1:0));
			}
	
		return $articleid;
		}
	else
	{
		return 0;
	}
}

function bab_editArticle($title, $head, $body, $lang, $template)
	{
	global $babBody;

	class clsEditArticle
		{
	
		var $title;
		var $head;
		var $body;

		function clsEditArticle($title, $head, $body, $lang, $template)
			{
			global $babDB;

			$this->mode = 1;

			$this->t_bab_image = bab_translate("Insert image");
			$this->t_bab_file = bab_translate("Insert file link");
			$this->t_bab_article = bab_translate("Insert article link");
			$this->t_bab_faq = bab_translate("Insert FAQ link");
			$this->t_bab_ovml = bab_translate("Insert OVML file");
			$this->t_bab_contdir = bab_translate("Insert contact link");

			$this->text_toolbar_head = bab_editor_text_toolbar('headtext',1);
			$this->text_toolbar_body = bab_editor_text_toolbar('bodytext',1);


			if( empty($title))
				{
				$this->titleval = "";
				}
			else
				{
				$this->titleval = htmlentities($title);
				}

			if( empty($head))
				{
				$this->headval = "";
				}
			else
				{
				$this->headval = htmlentities($head);
				}
			if( empty($body))
				{
				$this->bodyval = "";
				}
			else
				{
				$this->bodyval = htmlentities($body);
				}

			if( empty($lang))
				{
				$this->lang = $GLOBALS['babLanguage'];
				}
			else
				{
				$this->lang = $lang;
				}


			$this->head = bab_translate("Head");
			$this->body = bab_translate("Body");
			$this->title = bab_translate("Title");
			$this->ok = bab_translate("Ok");
			
			$this->images = bab_translate("Images");
			$this->urlimages = $GLOBALS['babUrlScript']."?tg=images";
			$this->files = bab_translate("Files");
			$this->langLabel = bab_translate("Language");
			$this->langFiles = $GLOBALS['babLangFilter']->getLangFiles();
			if(isset($GLOBALS['babApplyLanguageFilter']) && $GLOBALS['babApplyLanguageFilter'] == 'loose')
			{
				if($lang != '*')
				{
					$this->langFiles = array();
					$this->langFiles[] = '*';
				}
			}
			$this->countLangFiles = count($this->langFiles);

			// do not load script for ie < 5.5 to avoid javascript parsing errors
			preg_match("/MSIE\s+([\d|\.]*?);/", $_SERVER['HTTP_USER_AGENT'], $matches);
			$this->loadscripts = !isset($matches[1]) || ($matches[1] >= 5.5);

			if( $template != '' && $this->headval == '' && $this->bodyval == '')
				{
				$file = "articlestemplate.html";
				$filepath = "skins/".$GLOBALS['babSkin']."/templates/". $file;
				if( !file_exists( $filepath ) )
					{
					$filepath = $GLOBALS['babSkinPath']."templates/". $file;
					if( !file_exists( $filepath ) )
						{
						$filepath = $GLOBALS['babInstallPath']."skins/ovidentia/templates/". $file;
						}
					}
				if( file_exists( $filepath ) )
					{
					$tp = new babTemplate();
					$this->headval = $tp->printTemplate($this, $filepath, "head_".$template);
					$this->bodyval = $tp->printTemplate($this, $filepath, "body_".$template);
					}
				}

			}
			
			function getnextlang()
			{
				static $i = 0;
				if($i < $this->countLangFiles)
					{
					$this->langValue = $this->langFiles[$i];
					if($this->langValue == $this->lang)
						{
						$this->langSelected = 'selected';
						}
					else
						{
						$this->langSelected = '';
						}
					$i++;
					return true;
					}
				return false;
			} // function getnextlang

		} // class temp
	
	$temp = new clsEditArticle($title, $head, $body, $lang, $template);
	return bab_printTemplate($temp,"artincl.html", "editarticle");
	}


function bab_previewArticleDraft($idart, $echo=0)
	{
	global $babBody;

	class clsPreviewArticleDraft
		{
	
		var $titleval;
		var $headval;
		var $bodyvat;

		function clsPreviewArticleDraft($idart)
			{
			global $babDB;
			$this->counf = 0;

			$res = $babDB->db_query("select adt.* from ".BAB_ART_DRAFTS_TBL." adt where adt.id='".$babDB->db_escape_string($idart)."'");
			if( $res && $babDB->db_num_rows($res) > 0 )
				{
				$this->idart = bab_toHTML($idart);
				$this->filesval = bab_translate("Associated documents");
				$this->notesval = bab_translate("Associated comments");
				$arr = $babDB->db_fetch_array($res);
				$this->titleval = bab_toHTML(bab_replace($arr['title']));
				$this->headval = bab_replace($arr['head']);
				$this->bodyval = bab_replace($arr['body']);
				
				$this->resf = $babDB->db_query("select * from ".BAB_ART_DRAFTS_FILES_TBL." where id_draft='".$babDB->db_escape_string($idart)."' order by ordering asc");
				$this->countf =  $babDB->db_num_rows($this->resf);

				$this->resn = $babDB->db_query("select * from ".BAB_ART_DRAFTS_NOTES_TBL." where id_draft='".$babDB->db_escape_string($idart)."' order by date_note asc");
				$this->countn =  $babDB->db_num_rows($this->resn);
				$this->altbg = false;
				}
			else
				{
				$this->titleval = '';
				$this->headval = '';
				$this->bodyval = '';
				}
			}

		function getnextfile()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->countf)
				{
				$arr = $babDB->db_fetch_array($this->resf);
				$this->urlfile = bab_toHTML($GLOBALS['babUrlScript']."?tg=artedit&idx=getf&idart=".$this->idart."&idf=".$arr['id']);
				$this->filename = bab_toHTML($arr['name']);
				$i++;
				return true;
				}
			else
				return false;
			}

		function getnextnote()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->countn)
				{
				$arr = $babDB->db_fetch_array($this->resn);
				$this->note = str_replace("\n", "<br>", bab_toHTML($arr['content']));
				$this->author = bab_toHTML(bab_getUserName($arr['id_author']));
				$this->date = bab_toHTML(bab_strftime(bab_mktime($arr['date_note'])));
				$this->altbg = !$this->altbg;
				$i++;
				return true;
				}
			else
				return false;
			}

		}
	
	$temp = new clsPreviewArticleDraft($idart);
	if( $echo )
		{
		echo bab_printTemplate($temp,"artincl.html", "previewarticledraft");
		}
	else
		{
		return bab_printTemplate($temp,"artincl.html", "previewarticledraft");
		}
	}

function bab_previewComment($com)
	{
	global $babBody;

	class bab_previewCommentCls
		{
	
		var $content;
		var $arr = array();
		var $count;
		var $res;
		var $close;
		var $title;


		function bab_previewCommentCls($com)
			{
			global $babDB;
			$this->close = bab_translate("Close");
			$req = "select * from ".BAB_COMMENTS_TBL." where id='".$babDB->db_escape_string($com)."'";
			$this->res = $babDB->db_query($req);
			$this->arr = $babDB->db_fetch_array($this->res);
			$this->title = bab_toHTML(bab_replace($this->arr['subject']));
			$this->content = bab_replace($this->arr['message']);
			}
		}
	
	$temp = new bab_previewCommentCls($com);
	echo bab_printTemplate($temp,"artincl.html", "previewcomment");
	}

function bab_getDocumentArticle( $idf )
	{
	global $babDB, $babBody, $BAB_SESS_USERID;
	$access = false;

	$res = $babDB->db_query("select * from ".BAB_ART_FILES_TBL." where id='".$babDB->db_escape_string($idf)."'");
	if( $res && $babDB->db_num_rows($res) > 0 )
		{
		$access = true;
		}

	if( !$access )
		{
		echo bab_translate("Access denied");
		return;
		}

	$GLOBALS['babWebStat']->addArticleFile($idf);
	$arr = $babDB->db_fetch_array($res);
	$file = stripslashes($arr['name']);

	$fullpath = bab_getUploadArticlesPath();
	

	$fullpath .= $arr['id_article'].",".$file;
	$fsize = filesize($fullpath);
	$mime = bab_getFileMimeType($file);

	if( strtolower(bab_browserAgent()) == "msie")
		header('Cache-Control: public');
	$inl = bab_getFileContentDisposition() == 1? 1: '';
	if( $inl == '1' )
		header("Content-Disposition: inline; filename=\"$file\""."\n");
	else
		header("Content-Disposition: attachment; filename=\"$file\""."\n");
	header("Content-Type: $mime"."\n");
	header("Content-Length: ". $fsize."\n");
	header("Content-transfert-encoding: binary"."\n");
	$fp=fopen($fullpath,"rb");
	while(!feof($fp)) {
		echo fread($fp,4096);
		}
	fclose($fp);
	}


function bab_submitArticleDraft($idart)
{
	
	global $babBody, $babDB, $BAB_SESS_USERID;
	$res = $babDB->db_query("select id_article, id_topic, id_author, approbation from ".BAB_ART_DRAFTS_TBL." where id='".$babDB->db_escape_string($idart)."'");
	if( $res && $babDB->db_num_rows($res) > 0 )
		{
		$arr = $babDB->db_fetch_array($res);

		if( $arr['id_topic'] == 0 )
			{
			return false;
			}

		if( $arr['id_article'] != 0 )
			{
			$babDB->db_query("insert into ".BAB_ART_LOG_TBL." (id_article, id_author, date_log, action_log) values ('".$arr['id_article']."', '".$babDB->db_escape_string($arr['id_author'])."', now(), 'commit')");	
			
			$res = $babDB->db_query("select at.id_topic, at.id_author, tt.allow_update, tt.allow_manupdate, tt.idsa_update as saupdate, tt.auto_approbation from ".BAB_ARTICLES_TBL." at left join ".BAB_TOPICS_TBL." tt on at.id_topic=tt.id where at.id='".$babDB->db_escape_string($arr['id_article'])."'");
			$rr = $babDB->db_fetch_array($res);
			if( $rr['saupdate'] != 0 && ( $rr['allow_update'] == '2' && $rr['id_author'] == $GLOBALS['BAB_SESS_USERID']) || ( $rr['allow_manupdate'] == '2' && bab_isAccessValid(BAB_TOPICSMAN_GROUPS_TBL, $rr['id_topic'])))
				{
				if( $arr['approbation'] == '2' )
					{
					$rr['saupdate'] = 0;
					}
				}
			}
		else
			{
			$res = $babDB->db_query("select tt.idsaart as saupdate, tt.auto_approbation from ".BAB_TOPICS_TBL." tt where tt.id='".$babDB->db_escape_string($arr['id_topic'])."'");
			$rr = $babDB->db_fetch_array($res);
			}

		if( $rr['saupdate'] !=  0 )
			{
			include_once $GLOBALS['babInstallPath']."utilit/afincl.php";
			if( $rr['auto_approbation'] == 'Y' )
				{
				$idfai = makeFlowInstance($rr['saupdate'], "draft-".$idart, $GLOBALS['BAB_SESS_USERID']); // Auto approbation
				}
			else
				{
				$idfai = makeFlowInstance($rr['saupdate'], "draft-".$idart);
				}
			}

		if( $rr['saupdate'] ==  0 || $idfai === true)
			{
			if( $arr['id_article'] != 0 )
				{
				$babDB->db_query("insert into ".BAB_ART_LOG_TBL." (id_article, id_author, date_log, action_log) values ('".$babDB->db_escape_string($arr['id_article'])."', '".$arr['id_author']."', now(), 'accepted')");		
				}

			$articleid = acceptWaitingArticle($idart);
			if( $articleid == 0)
				{
				return false;
				}
			bab_deleteArticleDraft($idart);
			}
		else
			{
			if( !empty($idfai))
				{
				$babDB->db_query("update ".BAB_ART_DRAFTS_TBL." set result='".BAB_ART_STATUS_WAIT."' , idfai='".$idfai."', date_submission=now() where id='".$babDB->db_escape_string($idart)."'");
				$nfusers = getWaitingApproversFlowInstance($idfai, true);
				notifyArticleDraftApprovers($idart, $nfusers);
				}
			}		
		}
	return true;
}


function bab_newArticleDraft($idtopic, $idarticle)
	{
	global $babDB, $BAB_SESS_USERID;
	if( empty($BAB_SESS_USERID) || $BAB_SESS_USERID == 0 )
		{
		$res = $babDB->db_query("select id from ".BAB_USERS_LOG_TBL." where sessid='".session_id()."' and id_user='0'");
		if( $res && $babDB->db_num_rows($res) == 1 )
			{
			$arr = $babDB->db_fetch_array($res);
			$idanonymous = $arr['id'];
			}
		else
			{
			return 0;
			}
		}
	else
		{
		$idanonymous = 0;
		}

	$notify = 'N';

	$babDB->db_query("insert into ".BAB_ART_DRAFTS_TBL." (id_author, id_topic, id_article, title, date_creation, date_modification, id_anonymous, notify_members) values ('" .$babDB->db_escape_string($BAB_SESS_USERID). "', '".$babDB->db_escape_string($idtopic). "', '".$babDB->db_escape_string($idarticle)."', '".bab_translate("New article")."', now(), now(), '".$babDB->db_escape_string($idanonymous)."', '".$babDB->db_escape_string($notify)."')");
	$id = $babDB->db_insert_id();

	if( $idarticle != 0 )
		{
		$res = $babDB->db_query("select * from ".BAB_ARTICLES_TBL." where id='".$babDB->db_escape_string($idarticle)."'");
		if( $res && $babDB->db_num_rows($res) == 1 )
			{
			$arr = $babDB->db_fetch_array($res);
			$babDB->db_query("update ".BAB_ART_DRAFTS_TBL." set head='".$babDB->db_escape_string($arr['head'])."', body='".$babDB->db_escape_string($arr['body'])."', title='".$babDB->db_escape_string($arr['title'])."', date_archiving='".$babDB->db_escape_string($arr['date_archiving'])."', lang='".$babDB->db_escape_string($arr['lang'])."', restriction='".$babDB->db_escape_string($arr['restriction'])."' where id='".$babDB->db_escape_string($id)."'");

			$res = $babDB->db_query("select * from ".BAB_ART_FILES_TBL." where id_article='".$babDB->db_escape_string($idarticle)."'");
			$pathorg = bab_getUploadArticlesPath();
			$pathdest = bab_getUploadDraftsPath();
			while($rr = $babDB->db_fetch_array($res))
				{
				if( copy($pathorg.$idarticle.",".$rr['name'], $pathdest.$id.",".$rr['name']))
					{
					$babDB->db_query("insert into ".BAB_ART_DRAFTS_FILES_TBL." (id_draft, name, description, ordering) values ('".$id."','".$babDB->db_escape_string($rr['name'])."','".$babDB->db_escape_string($rr['description'])."','".$babDB->db_escape_string($rr['ordering'])."')");
					}
				}
			$res = $babDB->db_query("select * from ".BAB_ART_TAGS_TBL." where id_art='".$babDB->db_escape_string($idarticle)."'");
			while($rr = $babDB->db_fetch_array($res))
				{
				$babDB->db_query("insert into ".BAB_ART_DRAFTS_TAGS_TBL." (id_draft, id_tag) values ('".$id."','".$babDB->db_escape_string($rr['id_tag'])."')");
				}
			}
		}

	return $id;
	}







/**
 * Index all articles files
 * @param array $status
 * @return object bab_indexReturn
 */
function indexAllArtFiles($status, $prepare) 
	{
	
	global $babDB;

	$res = $babDB->db_query("
	
		SELECT 
			f.id,
			f.name,
			f.id_article, 
			a.id_topic 

		FROM 
			".BAB_ART_FILES_TBL." f,
			".BAB_ARTICLES_TBL." a 
		WHERE 
			a.id = f.id_article 
			AND f.index_status IN(".$babDB->quote($status).")
		
	");

	
	$files = array();
	$rights = array();
	$fullpath = bab_getUploadArticlesPath();

	$articlepath = 'articles/';

	

	while ($arr = $babDB->db_fetch_assoc($res)) {
		$files[] = $fullpath.$arr['id_article'].",".$arr['name'];
		$rights[$articlepath.$arr['id_article'].",".$arr['name']] = array(
				'id_file'		=> $arr['id'],
				'id_topic'		=> $arr['id_topic']
			);
	}

	if (!$files) {
		$r = new bab_indexReturn;
		$r->addError(bab_translate("No files to index in the articles"));
		$r->result = false;
		return $r;
	}


	include_once $GLOBALS['babInstallPath']."utilit/indexincl.php";
	$obj = new bab_indexObject('bab_art_files');


	$param = array(
			'status' => $status,
			'rights' => $rights
		);

	if (in_array(BAB_INDEX_STATUS_INDEXED, $status)) {
		if ($prepare) {
			return $obj->prepareIndex($files, $GLOBALS['babInstallPath'].'utilit/artincl.php', 'indexAllArtFiles_end', $param );
		} else {
			$r = $obj->resetIndex($files);
		}
	} else {
		$r = $obj->addFilesToIndex($files);
	}

	if (true === $r->result) {
		indexAllArtFiles_end($param);
	}

	return $r;
}



function indexAllArtFiles_end($param) {
	
	global $babDB;

	$babDB->db_query("
	
		UPDATE ".BAB_ART_FILES_TBL." SET index_status='".BAB_INDEX_STATUS_INDEXED."'
		WHERE 
			index_status IN('".implode("','",$param['status'])."')
		
	");

	include_once $GLOBALS['babInstallPath']."utilit/indexincl.php";
	$obj = new bab_indexObject('bab_art_files');

	foreach($param['rights'] as $f => $arr) {
		$obj->setIdObjectFile($f, $arr['id_file'], $arr['id_topic']);
	}

	return true;
}


?>