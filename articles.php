<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
include "base.php";
include $babInstallPath."utilit/topincl.php";

define("MAX_ARTICLES", 10);

function listArticles($topics, $approver)
	{
	global $babBody;

	class temp
		{
	
		var $content;
		var $arr = array();
		var $db;
		var $count;
		var $res;
		var $more;
		var $topics;
		var $com;
		var $author;
		var $commentsurl;
		var $commentsname;
		var $moreurl;
		var $morename;
		var $approver;
		var $modify;
		var $delete;
		var $modifyurl;
		var $delurl;


		function temp($topics, $approver)
			{
			$this->printable = bab_translate("Print Friendly");
			$this->modify = bab_translate("Modify");
			$this->delete = bab_translate("Delete");
			$this->db = $GLOBALS['babDB'];
			$req = "select id, title, head, LENGTH(body) as blen from ".BAB_ARTICLES_TBL." where id_topic='$topics' and confirmed='Y' and archive='N' order by date desc";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			$this->topics = $topics;
			if( bab_isAccessValid(BAB_TOPICSCOM_GROUPS_TBL, $this->topics) || bab_isUserCommentApprover($topics))
				$this->com = true;
			else
				$this->com = false;
			$this->morename = bab_translate("Read More");
			$res = $this->db->db_query("select count(*) from ".BAB_ARTICLES_TBL." where id_topic='".$topics."' and archive='Y'");
			list($this->nbarch) = $this->db->db_fetch_row($res);
			$this->approver = $approver;
			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$this->arr = $this->db->db_fetch_array($this->res);
				$this->articleauthor = bab_getArticleAuthor($this->arr['id']);
				$this->articledate = bab_getArticleDate($this->arr['id']);
				$this->author = bab_translate("by") . " ". $this->articleauthor. " - ". $this->articledate;
				$this->content = bab_replace($this->arr['head']);
				$this->title = stripslashes($this->arr['title']);
				if( $this->approver )
					$this->blen = 1;
				else
					$this->blen = $this->arr['blen'];
				$this->printurl = $GLOBALS['babUrlScript']."?tg=articles&idx=Print&topics=".$this->topics."&article=".$this->arr['id'];
				$this->modifyurl = $GLOBALS['babUrlScript']."?tg=articles&idx=Modify&topics=".$this->topics."&article=".$this->arr['id'];
				$this->delurl = $GLOBALS['babUrlScript']."?tg=articles&idx=Delete&topics=".$this->topics."&article=".$this->arr['id'];

				if( $this->com)
					{
					$req = "select count(id) as total from ".BAB_COMMENTS_TBL." where id_article='".$this->arr['id']."' and confirmed='Y'";
					$res = $this->db->db_query($req);
					$ar = $this->db->db_fetch_array($res);
					$total = $ar['total'];

					$req = "select count(".BAB_COMMENTS_TBL.".id) as total from ".BAB_COMMENTS_TBL." join ".BAB_FAR_INSTANCES_TBL." where id_article='".$this->arr['id']."' and confirmed='N' and ".BAB_FAR_INSTANCES_TBL.".idschi=".BAB_COMMENTS_TBL.".idfai and ".BAB_FAR_INSTANCES_TBL.".iduser='".$GLOBALS['BAB_SESS_USERID']."' and ".BAB_FAR_INSTANCES_TBL.".result='' and  ".BAB_FAR_INSTANCES_TBL.".notified='Y'";
					$res = $this->db->db_query($req);			
					$ar = $this->db->db_fetch_array($res);
					$totalw = $ar['total'];
					if( $total > 0 || ( $totalw > 0 && bab_isUserCommentApprover($this->topics) ))
						{
						$this->commentsurl = $GLOBALS['babUrlScript']."?tg=comments&idx=List&topics=".$this->topics."&article=".$this->arr['id'];
						if( $totalw > 0 )
							$this->commentsname = bab_translate("Comments")."&nbsp;(".$total."-".$totalw.")";
						else
							$this->commentsname = bab_translate("Comments")."&nbsp;(".$total.")";
						}
					else
						{
						$this->commentsurl = $GLOBALS['babUrlScript']."?tg=comments&idx=addComment&topics=".$this->topics."&article=".$this->arr['id'];
						$this->commentsname = bab_translate("Add Comment");
						}

					}
				else
					{
					$this->commentsurl = "";
					$this->commentsname = "";
					}

				$this->moreurl = $GLOBALS['babUrlScript']."?tg=articles&idx=More&topics=".$this->topics."&article=".$this->arr['id'];

				$this->morename = bab_translate("Read more")."...";
				$i++;
				return true;
				}
			else
				return false;
			}
		}
	
	$temp = new temp($topics, $approver);
	$babBody->babecho(	bab_printTemplate($temp,"articles.html", "introlist"));
	$arr = array($temp->count, $temp->nbarch);
	return $arr;
	}

function listOldArticles($topics, $pos, $approver)
	{
	global $babBody;

	class temp
		{
	
		var $content;
		var $arr = array();
		var $db;
		var $count;
		var $res;
		var $more;
		var $newc;
		var $topics;
		var $com;
		var $author;
		var $commentsurl;
		var $commentsname;
		var $moreurl;
		var $morename;

		function temp($topics, $pos, $approver)
			{
			$this->approver = $approver;
			$this->topurl = "";
			$this->bottomurl = "";
			$this->nexturl = "";
			$this->prevurl = "";
			$this->topname = "";
			$this->bottomname = "";
			$this->nextname = "";
			$this->prevname = "";
			$this->printable = bab_translate("Print Friendly");
			$this->db = $GLOBALS['babDB'];

			$res = $this->db->db_query("select count(*) from ".BAB_ARTICLES_TBL." where id_topic='$topics' and confirmed='Y' and archive='Y'");
			list($total)= $this->db->db_fetch_array($res);

			if( $total > MAX_ARTICLES)
				{
				$this->barch = true;
				if( $pos > 0)
					{
					$this->topurl = $GLOBALS['babUrlScript']."?tg=articles&idx=larch&topics=".$topics;
					$this->topname = "&lt;&lt;";
					}

				$next = $pos - MAX_ARTICLES;
				if( $next >= 0)
					{
					$this->prevurl = $GLOBALS['babUrlScript']."?tg=articles&idx=larch&topics=".$topics."&pos=".$next;
					$this->prevname = "&lt;";
					}

				$next = $pos + MAX_ARTICLES;
				if( $next < $total)
					{
					$this->nexturl = $GLOBALS['babUrlScript']."?tg=articles&idx=larch&topics=".$topics."&pos=".$next;
					$this->nextname = "&gt;";
					if( $next + MAX_ARTICLES < $total)
						{
						$bottom = $total - MAX_ARTICLES;
						}
					else
						$bottom = $next;
					$this->bottomurl = $GLOBALS['babUrlScript']."?tg=articles&idx=larch&topics=".$topics."&pos=".$bottom;
					$this->bottomname = "&gt;&gt;";
					}
				}
			else
				$this->barch = false;


			$req = "select id, title, head, LENGTH(body) as blen from ".BAB_ARTICLES_TBL." where id_topic='$topics' and confirmed='Y' and archive='Y' order by date desc";
			if( $total > MAX_ARTICLES)
				{
				$req .= " limit ".$pos.",".MAX_ARTICLES;
				}
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			$this->topics = $topics;
			if( bab_isAccessValid(BAB_TOPICSCOM_GROUPS_TBL, $this->topics) || bab_isUserCommentApprover($topics))
				$this->com = true;
			else
				$this->com = false;
			$this->morename = bab_translate("Read More");
			}

		function getnext()
			{
			global $new; 
			static $i = 0;
			if( $i < $this->count)
				{
				$this->arr = $this->db->db_fetch_array($this->res);
				$this->articleauthor = bab_getArticleAuthor($this->arr['id']);
				$this->articledate = bab_getArticleDate($this->arr['id']);
				$this->author = bab_translate("by") . " ". $this->articleauthor. " - ". $this->articledate;
				$this->content = bab_replace($this->arr['head']);
				$this->title = stripslashes($this->arr['title']);
				$this->blen = $this->arr['blen'];
				$this->printurl = $GLOBALS['babUrlScript']."?tg=articles&idx=Print&topics=".$this->topics."&article=".$this->arr['id'];

				if( $this->com)
					{
					$req = "select count(id) as total from ".BAB_COMMENTS_TBL." where id_article='".$this->arr['id']."' and confirmed='Y'";
					$res = $this->db->db_query($req);
					$ar = $this->db->db_fetch_array($res);
					$total = $ar['total'];
					$req = "select count(id) as total from ".BAB_COMMENTS_TBL." where id_article='".$this->arr['id']."' and confirmed='N'";
					$res = $this->db->db_query($req);
					$ar = $this->db->db_fetch_array($res);
					$totalw = $ar['total'];
					$this->commentsurl = $GLOBALS['babUrlScript']."?tg=comments&idx=List&topics=".$this->topics."&article=".$this->arr['id'];
					if( $totalw > 0 )
						$this->commentsname = bab_translate("Comments")."&nbsp;(".$total."-".$totalw.")";
					else
						$this->commentsname = bab_translate("Comments")."&nbsp;(".$total.")";
					}
				else
					{
					$this->commentsurl = "";
					$this->commentsname = "";
					}

				$this->moreurl = $GLOBALS['babUrlScript']."?tg=articles&idx=More&topics=".$this->topics."&article=".$this->arr['id'];
				$this->morename = bab_translate("Read more")."...";
				$i++;
				return true;
				}
			else
				return false;
			}
		}
	
	$temp = new temp($topics, $pos, $approver);
	$babBody->babecho(	bab_printTemplate($temp,"articles.html", "introlist"));
	return $temp->count;
	}

function viewArticle($article)
	{
	global $babBody;

	class temp
		{
	
		var $content;
		var $arr = array();
		var $db;
		var $count;
		var $res;
		var $more;
		var $topics;
		var $baCss;
		var $close;
		var $head;


		function temp($article)
			{
			$this->babCss = bab_printTemplate($this,"config.html", "babCss");
			$this->close = bab_translate("Close");
			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_ARTICLES_TBL." where id='$article'";
			$this->res = $this->db->db_query($req);
			$this->arr = $this->db->db_fetch_array($this->res);
			$this->content = bab_replace($this->arr['body']);
			$this->head = bab_replace($this->arr['head']);
			}
		}
	
	$temp = new temp($article);
	echo bab_printTemplate($temp,"articles.html", "articleview");
	}

function readMore($topics, $article)
	{
	global $babBody;

	class temp
		{
	
		var $content;
		var $arr = array();
		var $db;
		var $count;
		var $res;
		var $more;
		var $topics;
		var $author;

		function temp($topics, $article)
			{
			$this->printable = bab_translate("Print Friendly");
			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_ARTICLES_TBL." where id='$article' and confirmed='Y'";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			$this->topics = $topics;
			$res = $this->db->db_query("select count(*) from ".BAB_ARTICLES_TBL." where id_topic='".$topics."' and archive='Y'");
			list($this->nbarch) = $this->db->db_fetch_row($res);
			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$this->arr = $this->db->db_fetch_array($this->res);
				$this->content = bab_replace($this->arr['body']);
				$this->articleauthor = bab_getArticleAuthor($this->arr['id']);
				$this->articledate = bab_getArticleDate($this->arr['id']);
				$this->author = bab_translate("by") . " ". $this->articleauthor. " - ". $this->articledate;
				$this->printurl = $GLOBALS['babUrlScript']."?tg=articles&idx=Print&topics=".$this->topics."&article=".$this->arr['id'];
				$i++;
				return true;
				}
			else
				return false;
			}
		}
	
	$temp = new temp($topics, $article);
	$babBody->babecho(	bab_printTemplate($temp,"articles.html", "readmore"));
	return $temp->nbarch;
	}

function submitArticleByFile($topics)
	{
	global $babBody;
	
	class temp
		{
		var $title;
		var $doctag;
		var $introtag;
		var $filename;
		var $add;
		var $topics;
		var $maxupload;
		var $notearticle;

		function temp($topics)
			{
			global $babMaxUpload;
			$this->title = bab_translate("Title");
			$this->doctag = bab_translate("Document Tag");
			$this->introtag = bab_translate("Introduction Tag");
			$this->filename = bab_translate("Filename");
			$this->add = bab_translate("Add article");
			$this->topics = $topics;
			$this->maxupload = $babMaxUpload;
			$this->notearticle = bab_translate("Note: Articles are moderate and consequently your article will not be visible immediately");
			}
		}

	$temp = new temp($topics);
	$babBody->babecho(	bab_printTemplate($temp,"articles.html", "articlecreatebyfile"));
	}

function deleteArticle($topics, $article, $new, $newc)
	{
	global $babBody;
	
	class temp
		{
		var $warning;
		var $message;
		var $title;
		var $urlyes;
		var $urlno;
		var $yes;
		var $no;
		var $topics;
		var $article;

		function temp($topics, $article, $new, $newc)
			{
			$this->message = bab_translate("Are you sure you want to delete the article");
			$this->title = bab_getArticleTitle($article);
			$this->warning = bab_translate("WARNING: This operation will delete the article with all its comments"). "!";
			$this->urlyes = $GLOBALS['babUrlScript']."?tg=articles&idx=Articles&topics=".$topics."&article=".$article."&action=Yes";
			$this->yes = bab_translate("Yes");
			$this->urlno =$GLOBALS['babUrlScript']."?tg=articles&idx=More&topics=".$topics."&article=".$article."&new=".$new."&newc=".$newc;
			$this->no = bab_translate("No");
			}
		}

	$temp = new temp($topics, $article, $new, $newc);
	$babBody->babecho(	bab_printTemplate($temp,"warning.html", "warningyesno"));
	}

//##: warn this fucntion is duplicated in waiting.php file 
function modifyArticle($topics, $article)
	{
	global $babBody;

	class temp
		{
	
		var $head;
		var $title;
		var $titleval;
		var $headval;
		var $babBody;
		var $bodyval;
		var $modify;
		var $topics;
		var $article;
		var $arr = array();
		var $db;
		var $count;
		var $res;
		var $msie;
		var $topicstxt;

		function temp($topics, $article)
			{
			$this->article = $article;
			$this->topics = $topics;
			$this->head = bab_translate("Head");
			$this->body = bab_translate("Body");
			$this->title = bab_translate("Title");
			$this->modify = bab_translate("Modify");
			$this->topicstxt = bab_translate("Topic");
			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_ARTICLES_TBL." where id='$article'";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			if( $this->count > 0)
				{
				$this->arr = $this->db->db_fetch_array($this->res);
				$this->headval = htmlentities($this->arr['head']);
				$this->bodyval = htmlentities($this->arr['body']);
				$this->titleval = htmlentities($this->arr['title']);
				}
			$this->images = bab_translate("Images");
			$this->urlimages = $GLOBALS['babUrlScript']."?tg=images";
			$this->files = bab_translate("Files");
			$this->urlfiles = $GLOBALS['babUrlScript']."?tg=fileman&idx=brow";
			if(( strtolower(bab_browserAgent()) == "msie") and (bab_browserOS() == "windows"))
				$this->msie = 1;
			else
				$this->msie = 0;

			$req = "select ".BAB_TOPICS_TBL.".* from ".BAB_TOPICS_TBL." join ".BAB_TOPICS_CATEGORIES_TBL." where ".BAB_TOPICS_TBL.".id_cat=".BAB_TOPICS_CATEGORIES_TBL.".id and ".BAB_TOPICS_TBL.".id_approver='".$GLOBALS['BAB_SESS_USERID']."'";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			}

		function getnexttopic()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $this->db->db_fetch_array($this->res);
				$this->topicid = $arr['id'];
				$this->topictitle = $arr['category'];
				if( $arr['id'] == $this->topics )
					$this->selected = "selected";
				else
					$this->selected = "";
				$i++;
				return true;
				}
			else
				return false;
			}
		}
	
	$temp = new temp($topics, $article);
	$babBody->babecho(	bab_printTemplate($temp,"articles.html", "modifyarticle"));
	}

function submitArticle($title, $headtext, $bodytext, $topics)
	{
	global $babBody;

	class temp
		{
	
		var $head;
		var $babBody;
		var $modify;
		var $topics;
		var $title;
		var $msie;
		var $notearticle;

		function temp($title, $headtext, $bodytext, $topics)
			{
			if( empty($title))
				$this->titleval = "";
			else
				$this->titleval = $title;
			if( empty($headtext))
				$this->headval = "";
			else
				$this->headval = $headtext;
			if( empty($bodytext))
				$this->bodyval = "";
			else
				$this->bodyval = $bodytext;
			$this->topics = $topics;
			$this->head = bab_translate("Head");
			$this->body = bab_translate("Body");
			$this->title = bab_translate("Title");
			$this->modify = bab_translate("Add Article");
			$this->notearticle = bab_translate("Note: Articles are moderate and consequently your article will not be visible immediately");
			$this->images = bab_translate("Images");
			$this->urlimages = $GLOBALS['babUrlScript']."?tg=images";
			$this->files = bab_translate("Files");
			$this->urlfiles = $GLOBALS['babUrlScript']."?tg=fileman&idx=brow";
			if(( strtolower(bab_browserAgent()) == "msie") and (bab_browserOS() == "windows"))
				$this->msie = 1;
			else
				$this->msie = 0;	
			}
		}
	
	$temp = new temp($title, $headtext, $bodytext, $topics);
	$babBody->babecho(	bab_printTemplate($temp,"articles.html", "createarticle"));
	}

function articlePrint($topics, $article)
	{
	global $babBody;

	class temp
		{
	
		var $content;
		var $head;
		var $title;
		var $url;

		function temp($topics, $article)
			{
			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_ARTICLES_TBL." where id='$article'";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			$this->topics = $topics;
			if( $this->count > 0 )
				{
				$this->arr = $this->db->db_fetch_array($this->res);
				$this->head = bab_replace($this->arr['head']);
				$this->content = bab_replace($this->arr['body']);
				$this->title = bab_getArticleTitle($this->arr['id']);
				$this->url = "<a href=\"".$GLOBALS['babUrl']."\">".$GLOBALS['babSiteName']."</a>";
				}
			}
		}
	
	$temp = new temp($topics, $article);
	echo bab_printTemplate($temp,"articleprint.html");
	}

function notifyApprovers($id, $topics)
	{
	include_once $GLOBALS['babInstallPath']."utilit/afincl.php";
	$db = $GLOBALS['babDB'];
	$req = "select * from ".BAB_TOPICS_TBL." where id='".$topics."'";
	$res = $db->db_query($req);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		if( $arr['idsaart'] == 0 )
			{
			$db->db_query("update ".BAB_ARTICLES_TBL." set confirmed='Y' where id='".$id."'");
			return true;
			}

		$res = $db->db_query("select * from ".BAB_FLOW_APPROVERS_TBL." where id='".$arr['idsaart']."'");
		if( $res && $db->db_num_rows($res) > 0)
			{
			$idfai = makeFlowInstance($arr['idsaart'], "art-".$id);
			$db->db_query("update ".BAB_ARTICLES_TBL." set idfai='".$idfai."' where id='".$id."'");
			$nfusers = getWaitingApproversFlowInstance($idfai, true);
			notifyArticleApprovers($id, $nfusers);
			}
		}
	}

function saveArticleByFile($filename, $title, $doctag, $introtag, $topics)
	{
	global $BAB_SESS_USERID, $babBody , $babAdminEmail;

	class dummy
		{
		}

	$dummy = new dummy();
	if( $filename == "none")
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a file name");
		return;
		}

	if( empty($title))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a title");
		return;
		}

	$tp = new babTemplate();
	$headtext = $tp->printTemplate($dummy, $filename, $introtag);
	$bodytext = $tp->printTemplate($dummy, $filename, $doctag);

	$headtext = addslashes($headtext);
	$bodytext = addslashes($bodytext);

	$db = $GLOBALS['babDB'];
	$req = "insert into ".BAB_ARTICLES_TBL." (id_topic, id_author, confirmed, date, title, body, head) values ";
	$req .= "('" .$topics. "', '" . $BAB_SESS_USERID. "', 'N', now(), '" . $title. "', '" . $bodytext. "', '" . $headtext. "')";
	$res = $db->db_query($req);
	$id = $db->db_insert_id();
	notifyApprovers($id, $topics);
	}


function saveArticle($title, $headtext, $bodytext, $topics)
	{
	global $BAB_SESS_USERID, $babBody ;

	if( empty($title))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a title");
		return false;
		}

	if( empty($headtext))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a head for your article");
		return false;
		}

	$db = $GLOBALS['babDB'];
	$req = "insert into ".BAB_ARTICLES_TBL." (id_topic, id_author, confirmed, date) values ";
	$req .= "('" .$topics. "', '" . $BAB_SESS_USERID. "', 'N', now())";
	$res = $db->db_query($req);
	$id = $db->db_insert_id();

	$headtext = stripslashes($headtext);
	$bodytext = stripslashes($bodytext);
	$title = stripslashes($title);

	$ar = array();
	$headtext = imagesReplace($headtext, $id."_art_", $ar);
	$bodytext = imagesReplace($bodytext, $id."_art_", $ar);

	$req = "update ".BAB_ARTICLES_TBL." set head='".addslashes(bab_stripDomainName($headtext))."', body='".addslashes(bab_stripDomainName($bodytext))."', title='".addslashes($title)."' where id='".$id."'";
	$res = $db->db_query($req);

	notifyApprovers($id, $topics);
	return true;
	}

//@@: warn this function is duplicated in waiting.php file 
function updateArticle($topics, $title, $article, $headtext, $bodytext, $topicid)
	{
	global $babBody;

	if( empty($title))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a title");
		return;
		}

	if( bab_isMagicQuotesGpcOn())
		{
		$headtext = stripslashes($headtext);
		$bodytext = stripslashes($bodytext);
		$title = stripslashes($title);
		}

	$ar = array();
	$headtext = imagesReplace($headtext, $article."_art_", $ar);
	$bodytext = imagesReplace($bodytext, $article."_art_", $ar);

	$title = addslashes($title);
	$db = $GLOBALS['babDB'];
	$req = "update ".BAB_ARTICLES_TBL." set title='".addslashes($title)."', head='".addslashes(bab_stripDomainName($headtext))."', body='".addslashes(bab_stripDomainName($bodytext))."', date=now(), id_topic='".$topicid."' where id='".$article."'";
	$res = $db->db_query($req);

	}


/* main */
if(!isset($idx))
	{
	$idx = "Articles";
	}
if( !isset($pos))
	$pos = 0;

if( isset($addarticle))
	{
	saveArticleByFile($filename, $title, $doctag, $introtag, $topics);
	$idx = "Articles";
	}

if( isset($addart) && $addart == "add")
	{
	if( saveArticle($title, $headtext, $bodytext, $topics))
		$idx = "Articles";
	else
		$idx = "Submit";
	}

if( isset($action) && $action == "Yes" && bab_isUserTopicManager($topics))
	{
	bab_confirmDeleteArticle($article);
	}

if( isset($modify))
	{
	updateArticle($topics, $title, $article, $headtext, $bodytext, $topicid);
	$idx = "Articles";
	}

$approver = bab_isUserTopicManager($topics);
$uaapp = bab_isUserArticleApprover($topics);
$ucapp = bab_isUserCommentApprover($topics);
if( $approver || $uaapp || $ucapp )
	$access = true;
else
	$access = false;
if( $uaapp )
	{
	$db = $GLOBALS['babDB'];
	$req = "select ".BAB_ARTICLES_TBL.".id from ".BAB_ARTICLES_TBL." join ".BAB_FAR_INSTANCES_TBL." where id_topic='".$topics."' and confirmed='N' and ".BAB_FAR_INSTANCES_TBL.".idschi=".BAB_ARTICLES_TBL.".idfai and ".BAB_FAR_INSTANCES_TBL.".iduser='".$BAB_SESS_USERID."' and ".BAB_FAR_INSTANCES_TBL.".result='' and  ".BAB_FAR_INSTANCES_TBL.".notified='Y'";
	$res = $db->db_query($req);
	$new = $db->db_num_rows($res);
	}
else
	$new = 0;


switch($idx)
	{
	case "viewa":
		viewArticle($article);
		exit;

	case "Submit":
		$babBody->title = bab_translate("Submit an article")." [ ". bab_getCategoryTitle($topics) ." ]";
		if( bab_isAccessValid(BAB_TOPICSSUB_GROUPS_TBL, $topics) || $access)
			{
			submitArticle($title, $headtext, $bodytext, $topics);
			$babBody->addItemMenu("Articles", bab_translate("Articles"), $GLOBALS['babUrlScript']."?tg=articles&idx=Articles&topics=".$topics);
			$babBody->addItemMenu("Submit", bab_translate("Submit"), $GLOBALS['babUrlScript']."?tg=articles&idx=Submit&topics=".$topics);
			$babBody->addItemMenu("subfile", bab_translate("File"), $GLOBALS['babUrlScript']."?tg=articles&idx=subfile&topics=".$topics);
			}
		break;

	case "subfile":
		$babBody->title = bab_translate("Submit an article")." [ ". bab_getCategoryTitle($topics) ." ]";
		if( bab_isAccessValid(BAB_TOPICSSUB_GROUPS_TBL, $topics) || $access)
			{
			submitArticleByFile($topics);
			$babBody->addItemMenu("Articles", bab_translate("Articles"), $GLOBALS['babUrlScript']."?tg=articles&idx=Articles&topics=".$topics);
			$babBody->addItemMenu("Submit", bab_translate("Submit"), $GLOBALS['babUrlScript']."?tg=articles&idx=Submit&topics=".$topics);
			$babBody->addItemMenu("subfile", bab_translate("File"), $GLOBALS['babUrlScript']."?tg=articles&idx=subfile&topics=".$topics);
			}
		break;

	case "Comments":
		Header("Location: ". $GLOBALS['babUrlScript']."?tg=comments&topics=".$topics."&article=".$article);
		return;

	case "More":
		$babBody->title = bab_getCategoryTitle($topics);
		if( bab_isAccessValid(BAB_TOPICSVIEW_GROUPS_TBL, $topics)|| $access)
			{
			$barch = readMore($topics, $article);
			if( bab_isAccessValid(BAB_TOPICSSUB_GROUPS_TBL, $topics) || $access)
				{
				$babBody->addItemMenu("Articles", bab_translate("Articles"), $GLOBALS['babUrlScript']."?tg=articles&idx=Articles&topics=".$topics);
				if( $barch > 0 )
					$babBody->addItemMenu("larch", bab_translate("Archives"), $GLOBALS['babUrlScript']."?tg=articles&idx=larch&topics=".$topics);

				if( $approver)
					{
					$babBody->addItemMenu("Delete", bab_translate("Delete"), $GLOBALS['babUrlScript']."?tg=articles&idx=Delete&topics=".$topics."&article=".$article);
					$babBody->addItemMenu("Modify", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=articles&idx=Modify&topics=".$topics."&article=".$article);
					}
				}
			if( bab_isAccessValid(BAB_TOPICSCOM_GROUPS_TBL, $topics) || $access)
				{
				$babBody->addItemMenu("Comments", bab_translate("Comments"), $GLOBALS['babUrlScript']."?tg=comments&idx=List&topics=".$topics."&article=".$article);
				}
			}
		break;

	case "Delete":
		$babBody->title = bab_translate("Delete article");
		if( $approver)
			{
			deleteArticle($topics, $article, $new, $newc);
			$babBody->addItemMenu("Delete", bab_translate("Delete"), $GLOBALS['babUrlScript']."?tg=articles&idx=Delete&topics=".$topics."&article=".$article);
			}
		break;

	case "Modify":
		$babBody->title = bab_getArticleTitle($article);
		if( $approver)
			{
			modifyArticle($topics, $article);
			$babBody->addItemMenu("Cancel", bab_translate("Cancel"), $GLOBALS['babUrlScript']."?tg=articles&idx=More&topics=".$topics."&article=".$article);
			$babBody->addItemMenu("Modify", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=articles&idx=Modify&topics=".$topics."&article=".$article);
			}
		break;

	case "Print":
		if( bab_isAccessValid(BAB_TOPICSVIEW_GROUPS_TBL, $topics) || $access)
			articlePrint($topics, $article);
		exit();
		break;

	case "larch":
		$babBody->title = bab_translate("List of old articles");
		if( bab_isAccessValid(BAB_TOPICSVIEW_GROUPS_TBL, $topics)|| $access)
			{
			$nbarch = listOldArticles($topics, $pos, $approver);
			if( bab_isAccessValid(BAB_TOPICSSUB_GROUPS_TBL, $topics)|| $access)
				{
				$babBody->addItemMenu("Articles", bab_translate("Articles"), $GLOBALS['babUrlScript']."?tg=articles&idx=Articles&topics=".$topics);
				$babBody->addItemMenu("larch", bab_translate("Archives"), $GLOBALS['babUrlScript']."?tg=articles&idx=larch&topics=".$topics);
				}
			if( $nbarch < 1)
				$babBody->title = bab_getCategoryTitle($topics).": ". bab_translate("Today, there are no article");
			else
				$babBody->title = bab_getCategoryTitle($topics).": ".bab_translate("List of old articles");
			}
		break;

	default:
	case "Articles":
		$babBody->title = bab_translate("List of articles");
		if( bab_isAccessValid(BAB_TOPICSVIEW_GROUPS_TBL, $topics)|| $access)
			{
			$arr = listArticles($topics, $approver);
			if( bab_isAccessValid(BAB_TOPICSSUB_GROUPS_TBL, $topics)|| $access)
				{
				$babBody->addItemMenu("Submit", bab_translate("Submit"), $GLOBALS['babUrlScript']."?tg=articles&idx=Submit&topics=".$topics);
				if( $arr[1] > 0 )
					$babBody->addItemMenu("larch", bab_translate("Archives"), $GLOBALS['babUrlScript']."?tg=articles&idx=larch&topics=".$topics);
				if( isset($new) && $new > 0 && $uaapp)
					$babBody->addItemMenu("Waiting", bab_translate("Waiting"), $GLOBALS['babUrlScript']."?tg=waiting&idx=Waiting&topics=".$topics);
				}
			if( $arr[0] < 1)
				$babBody->title = bab_getCategoryTitle($topics).": ".bab_translate("Today, there are no article");
			else
				$babBody->title = bab_getCategoryTitle($topics);
			}
		break;
	}
$babBody->setCurrentItemMenu($idx);

?>
