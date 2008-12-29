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
require_once 'base.php';
require_once dirname(__FILE__) . '/artapi.php';



/**
 * Helper class that contain the path
 * used in publication according to
 * the delegation identifier
 *
 */
class bab_PublicationPathsEnv
{
	private $sUploadPath		= null;
	private $sRootImgPath		= null;
	private $sCategoriesImgPath	= null;
	private $sTopicsImgPath		= null;
	private $sArticlesImgPath	= null;
	private $sTempPath			= null;
	private $iIdDelegation		= null;
	private $aError				= array();

	
	public function __construct()
	{
		
	}
	
	/**
	 * Set up all the path
	 *
	 * @param int $iIdDelegation The delegation identifier
	 * 
	 * @return bool	True on success, false on error. To get the error call the method getError()
	 */
	public function setEnv($iIdDelegation)
	{
		$this->iIdDelegation	= (int) $iIdDelegation;
		$this->sUploadPath		= BAB_PathUtil::addEndSlash(BAB_PathUtil::sanitize($GLOBALS['babUploadPath']));
		
		if(!$this->checkDirAccess($this->sUploadPath))
		{
			return false;
		}		
		
		$aPath = array(
			'root'			=> 'articles',
			'categories'	=> 'articles/DG' . $this->iIdDelegation . '/categoriesImg',
			'topics'		=> 'articles/DG' . $this->iIdDelegation . '/topicsImg',
			'articles'		=> 'articles/DG' . $this->iIdDelegation . '/articlesImg',
			'temp'			=> 'articles/temp',
		);
		
		foreach($aPath as $sKey => $sRelativePath)
		{
			BAB_FmFolderHelper::makeDirectory($this->sUploadPath, $sRelativePath);
		}
		
		$this->sRootImgPath			= $this->sUploadPath . $aPath['root'] . '/';
		$this->sCategoriesImgPath	= $this->sUploadPath . $aPath['categories'] . '/';
		$this->sTopicsImgPath		= $this->sUploadPath . $aPath['topics'] . '/';
		$this->sArticlesImgPath		= $this->sUploadPath . $aPath['articles'] . '/';
		$this->sTempPath			= $this->sUploadPath . $aPath['temp'] . '/';
		
		$this->checkDirAccess($this->sRootImgPath);
		$this->checkDirAccess($this->sCategoriesImgPath);
		$this->checkDirAccess($this->sTopicsImgPath);
		$this->checkDirAccess($this->sArticlesImgPath);
		$this->checkDirAccess($this->sTempPath);
		
		return (0 === count($this->aError));
	}
	
	/**
	 * Returns the path to the image(s) associated with category, 
	 * the path is based on the identifier of delegation.
	 * The path is terminated with a '/'. 
	 *
	 * @param int $iIdCategory The identifier of the category to which the path must be returned 
	 * 
	 * @return string The path to the image of the category
	 */
	public function getCategoryImgPath($iIdCategory)
	{
		return $this->sCategoriesImgPath . $iIdCategory . '/';
	}
	
	/**
	 * Returns the path to the image(s) associated with topic, 
	 * the path is based on the identifier of delegation.
	 * The path is terminated with a '/'. 
	 *
	 * @param int $iIdTopic The identifier of the topic to which the path must be returned 
	 * 
	 * @return string The path to the image of the topic
	 */
	public function getTopicImgPath($iIdTopic)
	{
		return $this->sTopicsImgPath . $iIdTopic . '/';
	}
	
	/**
	 * Returns the path to the image(s) associated with article, 
	 * the path is based on the identifier of delegation.
	 * The path is terminated with a '/'. 
	 *
	 * @param int $iIdCategory The identifier of the article to which the path must be returned 
	 * 
	 * @return string The path to the image of the article
	 */
	public function getArticleImgPath($iIdArticle)
	{
		return $this->sTopicsImgPath . $iIdArticle . '/';
	}
	
	
	/**
	 * Returns the temp path used by the publication 
	 * The path is terminated with a '/'. 
	 *
	 * @return string The temp path used by the publication
	 */
	public function getTempPath()
	{
		return $this->sTempPath;
	}
	
	/**
	 * Return a value that indicate if the directory is accessible.
	 * To be accessible the $sFullPathName must be a directory,
	 * must be writable, must be readable.
	 *
	 * @param string $sFullPathName The full path name of the directory
	 * 
	 * @return bool	True on success, false on error. To get the error call the method getError()
	 */
	private function checkDirAccess($sFullPathName)
	{
		$Success = true;
		
		if(!is_dir($sFullPathName))
		{
			$this->addError(sprintf(bab_translate("The directory %s does not exits"), $sFullPathName));
			return false;			
		}
		
		if(!is_writable($sFullPathName))
		{
			$this->addError(sprintf(bab_translate("The directory %s is not writeable"), $sFullPathName));
			$Success = false;
		}
		
		if(!is_readable($sFullPathName))
		{
			$this->addError(sprintf(bab_translate("The directory %s is not readable"), $sFullPathName));
			$Success = false;
		}
		
		return $Success;
	}
	
	/**
	 * Add an error
	 *
	 * @param string $sMessage The error message
	 */
	private function addError($sMessage)
	{
		$this->aError[] = $sMessage;
	}
	
	/**
	 * Return a value that indicate if there is error
	 *
	 * @return bool True if there is error, false otherwise
	 */
	public function haveError()
	{
		return (0 !== count($this->aError));
	}
	
	/**
	 * Return an array of error string
	 *
	 * @return Array The array of error string
	 */
	public function getError()
	{
		return $this->aError;
	}
}



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
	if( mb_substr($GLOBALS['babUploadPath'], -1) == "/" )
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
	if( mb_substr($GLOBALS['babUploadPath'], -1) == "/" )
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
				$this->articleurl = $GLOBALS['babUrlScript']."?tg=login&cmd=detect&referer=".urlencode("?tg=approb&idx=all");
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
	$mailBCT = 'mail'.$babBody->babsite['mail_fieldaddress'];

	if( count($users) > 0 )
		{
		$sql = "select email from ".BAB_USERS_TBL." where id IN (".$babDB->quote($users).")";
		$result=$babDB->db_query($sql);
		while( $arr = $babDB->db_fetch_array($result))
			{
			$mail->$mailBCT($arr['email']);
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
				$this->articleurl = $GLOBALS['babUrlScript']."?tg=login&cmd=detect&referer=".urlencode("?tg=approb&idx=all");
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

	if(!class_exists("notifyArticleHomePageCls"))
		{
		class notifyArticleHomePageCls
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


			function notifyArticleHomePageCls($top, $title, $homepage0, $homepage1)
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
				$this->dateval = bab_longDate(time());
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
	$mailBCT = 'mail'.$babBody->babsite['mail_fieldaddress'];
	$clearBCT = 'clear'.$babBody->babsite['mail_fieldaddress'];

	$mail->mailFrom($babAdminEmail, $GLOBALS['babAdminName']);
	$mail->mailSubject(bab_translate("New article for home page"));

	$tempa = new notifyArticleHomePageCls($top, $title, $homepage0, $homepage1);
	$message = $mail->mailTemplate(bab_printTemplate($tempa,"mailinfo.html", "articlehomepage"));
	$mail->mailBody($message, "html");

	$message = bab_printTemplate($tempa,"mailinfo.html", "articlehomepagetxt");
	$mail->mailAltBody($message);


	include_once $GLOBALS['babInstallPath'].'admin/acl.php';
	$arrusers = aclGetAccessUsers(BAB_SITES_HPMAN_GROUPS_TBL, $babBody->babsite['id']);
			
	if( $arrusers )
		{
		$count = 0;
		while(list(,$arr) = each($arrusers))
			{
			$mail->$mailBCT($arr['email'], $arr['name']);
			$count++;

			if( $count > $babBody->babsite['mail_maxperpacket'] )
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
				$this->linkurl = $GLOBALS['babUrlScript']."?tg=login&cmd=detect&referer=".urlencode("?tg=articles&topics=".$topics);
				$this->linkname = viewCategoriesHierarchy_txt($topics);
				}
			}
		}	
    $mail = bab_mail();
	if( $mail == false )
		return;
	$mailBCT = 'mail'.$babBody->babsite['mail_fieldaddress'];
	$clearBCT = 'clear'.$babBody->babsite['mail_fieldaddress'];

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
				$mail->$mailBCT($arr['email'], $arr['name']);
				$count++;
				}
			}

		if( $count > $babBody->babsite['mail_maxperpacket'] )
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
				$this->subjecturl = $GLOBALS['babUrlScript']."?tg=login&cmd=detect&referer=".urlencode("?tg=waiting&idx=WaitingC&topics=".$arr['id_topic']."&article=".$arr['id_article']);
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

		$mailBCT = 'mail'.$babBody->babsite['mail_fieldaddress'];
		$clearBCT = 'clear'.$babBody->babsite['mail_fieldaddress'];

		if( count($nfusers) > 0 )
			{
			$sql = "select email from ".BAB_USERS_TBL." where id IN (".$babDB->quote($nfusers).")";
			$result=$babDB->db_query($sql);
			while( $arr = $babDB->db_fetch_array($result))
				{
				$mail->$mailBCT($arr['email']);
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

function notifyCommentAuthor($subject, $msg, $idfrom, $to)
	{
	global $babBody, $BAB_SESS_USER, $BAB_SESS_EMAIL, $babAdminEmail, $babInstallPath;

	class tempa
		{
		var $message;
        var $from;
        var $author;
        var $about;
        var $site;
        var $sitename;
        var $date;
        var $dateval;


		function tempa($subject, $msg, $from, $to)
			{
            global $BAB_SESS_USER, $BAB_SESS_EMAIL, $babSiteName;
            $this->about = bab_translate("About your comment");
            $this->site = bab_translate("Web site");
            $this->sitename = $babSiteName;
            $this->date = bab_translate("Date");
            $this->dateval = bab_strftime(mktime());
            $this->message = $msg;
			}
		}
	
    $mail = bab_mail();
	if( $mail == false )
		return;

	$mail->mailTo($to);
    $mail->mailFrom(bab_getUserEmail($idfrom), bab_getUserName($idfrom));
    $mail->mailSubject($subject);

	$tempa = new tempa($subject, $msg, $idfrom, $to);
	$message = $mail->mailTemplate(bab_printTemplate($tempa,"mailinfo.html", "confirmcomment"));
    $mail->mailBody($message, "html");

	$message = bab_printTemplate($tempa,"mailinfo.html", "confirmcommenttxt");
    $mail->mailAltBody($message);

	$mail->send();
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

		


			if( empty($title))
				{
				$this->titleval = "";
				}
			else
				{
				$this->titleval = bab_toHtml($title);
				}

			if( empty($head))
				{
				$this->headval = "";
				}
			else
				{
				$this->headval = $head;
				}
			if( empty($body))
				{
				$this->bodyval = "";
				}
			else
				{
				$this->bodyval = $body;
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
				
			include_once $GLOBALS['babInstallPath']."utilit/editorincl.php";
				// l'ordre des appels est important
			$editorhead = new bab_contentEditor('bab_article_head');
			$editorhead->setContent($this->headval);
			$editorhead->setFormat('html');
			
			$editorbody = new bab_contentEditor('bab_article_body');
			$editorbody->setContent($this->bodyval);
			$editorbody->setFormat('html');
			
			$this->editorhead = $editorhead->getEditor();
			$this->editorbody = $editorbody->getEditor();

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
				$this->idart = bab_toHtml($idart);
				$this->filesval = bab_translate("Associated documents");
				$this->notesval = bab_translate("Associated comments");
				$arr = $babDB->db_fetch_array($res);
				$this->titleval = bab_toHtml($arr['title']);

				include_once $GLOBALS['babInstallPath']."utilit/editorincl.php";
			
				$editor = new bab_contentEditor('bab_article_body');
				$editor->setContent($arr['body']);
				$this->bodyval = $editor->getHtml();
				
				$editor = new bab_contentEditor('bab_article_head');
				$editor->setContent($arr['head']);
				$this->headval = $editor->getHtml();
				
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
				$this->urlfile = bab_toHtml($GLOBALS['babUrlScript']."?tg=artedit&idx=getf&idart=".$this->idart."&idf=".$arr['id']);
				$this->filename = bab_toHtml($arr['name']);
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
				$this->note = str_replace("\n", "<br>", bab_toHtml($arr['content']));
				$this->author = bab_toHtml(bab_getUserName($arr['id_author']));
				$this->date = bab_toHtml(bab_strftime(bab_mktime($arr['date_note'])));
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
		var $sContent;

		function bab_previewCommentCls($com)
			{
			global $babDB;
			$this->close	= bab_translate("Close");
			$req			= "select * from ".BAB_COMMENTS_TBL." where id='".$babDB->db_escape_string($com)."'";
			$this->res		= $babDB->db_query($req);
			$this->arr		= $babDB->db_fetch_array($this->res);
			$this->title	= bab_toHtml($this->arr['subject']);
			$this->sContent	= 'text/html; charset=' . bab_charset::getIso();
			
			include_once $GLOBALS['babInstallPath']."utilit/editorincl.php";
			
			$editor = new bab_contentEditor('bab_article_comment');
			$editor->setContent($this->arr['message']);
			$this->content = $editor->getHtml();
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

	if( mb_strtolower(bab_browserAgent()) == "msie")
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


function bab_newArticleDraft($idtopic, $idarticle)
	{
	global $babDB, $BAB_SESS_USERID;
	
	$error = '';
	$id = bab_addArticleDraft(bab_translate("New article"), '', '', $idtopic, $error);

	$babDB->db_query("update ".BAB_ART_DRAFTS_TBL." set id_article='".$babDB->db_escape_string($idarticle)."' where id='".$id."'");

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