<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
include $babInstallPath."utilit/topincl.php";

function listArticles($topics, $newc)
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

		function temp($topics, $newc)
			{
			$this->db = $GLOBALS['babDB'];
			$req = "select * from articles where id_topic='$topics' and confirmed='Y' order by date desc";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			$this->topics = $topics;
			$this->newc = $newc;
			if( bab_isAccessValid("topicscom_groups", $this->topics) || bab_isUserApprover($topics))
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
				$this->author = bab_translate("by") . " ". bab_getArticleAuthor($this->arr['id']). " - ". bab_getArticleDate($this->arr['id']);
				$this->content = bab_replace($this->arr['head']);

				if( $this->com)
					{
					$req = "select count(id) as total from comments where id_article='".$this->arr['id']."' and confirmed='Y'";
					$res = $this->db->db_query($req);
					$ar = $this->db->db_fetch_array($res);
					$total = $ar['total'];
					$req = "select count(id) as total from comments where id_article='".$this->arr['id']."' and confirmed='N'";
					$res = $this->db->db_query($req);
					$ar = $this->db->db_fetch_array($res);
					$totalw = $ar['total'];
					$this->commentsurl = $GLOBALS['babUrlScript']."?tg=comments&idx=List&topics=".$this->topics."&article=".$this->arr['id'];
					if( isset($new) && $new > 0)
						$this->commentsurl .= "&new=".$new;
					$this->commentsurl .= "&newc=".$this->newc;
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
				if( isset($new) && $new > 0)
					$this->moreurl .= "&new=".$new;

				$this->moreurl .= "&newc=".$this->newc;
				$this->morename = bab_translate("Read more")."...";
				$i++;
				return true;
				}
			else
				return false;
			}
		}
	
	$temp = new temp($topics, $newc);
	$babBody->babecho(	bab_printTemplate($temp,"articles.html", "introlist"));
	return $temp->count;
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

		function temp($topics, $article)
			{
			$this->db = $GLOBALS['babDB'];
			$req = "select * from articles where id='$article' and confirmed='Y'";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			$this->topics = $topics;
			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$this->arr = $this->db->db_fetch_array($this->res);
				$this->content = bab_replace($this->arr['body']);
				$i++;
				return true;
				}
			else
				return false;
			}
		}
	
	$temp = new temp($topics, $article);
	$babBody->babecho(	bab_printTemplate($temp,"articles.html", "readmore"));
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

		function temp($topics, $article)
			{
			$this->article = $article;
			$this->topics = $topics;
			$this->head = bab_translate("Head");
			$this->body = bab_translate("Body");
			$this->title = bab_translate("Title");
			$this->modify = bab_translate("Modify");
			$this->db = $GLOBALS['babDB'];
			$req = "select * from articles where id='$article'";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			if( $this->count > 0)
				{
				$this->arr = $this->db->db_fetch_array($this->res);
				$this->headval = htmlentities($this->arr['head']);
				$this->bodyval = htmlentities($this->arr['body']);
				$this->titleval = $this->arr['title'];
				}
			if(( strtolower(bab_browserAgent()) == "msie") and (bab_browserOS() == "windows"))
				$this->msie = 1;
			else
				$this->msie = 0;	
			}
		}
	
	$temp = new temp($topics, $article);
	$babBody->babecho(	bab_printTemplate($temp,"articles.html", "modifyarticle"));
	}

function submitArticle($topics)
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

		function temp($topics)
			{
			$this->topics = $topics;
			$this->head = bab_translate("Head");
			$this->body = bab_translate("Body");
			$this->title = bab_translate("Title");
			$this->modify = bab_translate("Add Article");
			if(( strtolower(bab_browserAgent()) == "msie") and (bab_browserOS() == "windows"))
				$this->msie = 1;
			else
				$this->msie = 0;	
			}
		}
	
	$temp = new temp($topics);
	$babBody->babecho(	bab_printTemplate($temp,"articles.html", "createarticle"));
	}

function articlePrint($topics, $article)
	{
	global $babBody;

	class temp
		{
	
		var $content;
		var $title;
		var $url;

		function temp($topics, $article)
			{
			$this->db = $GLOBALS['babDB'];
			$req = "select * from articles where id='$article'";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			$this->topics = $topics;
			if( $this->count > 0 )
				{
				$this->arr = $this->db->db_fetch_array($this->res);
				$this->content = bab_replace($this->arr['body']);
				$this->title = bab_getArticleTitle($this->arr['id']);
				$this->url = "<a href=\"".$GLOBALS['babUrl']."\">".$GLOBALS['babSiteName']."</a>";
				}
			}
		}
	
	$temp = new temp($topics, $article);
	echo bab_printTemplate($temp,"articleprint.html");
	}

function notifyApprover($top, $title, $approveremail)
	{
	global $babBody, $BAB_SESS_USER, $BAB_SESS_EMAIL, $babAdminEmail, $babInstallPath;
    include $babInstallPath."utilit/mailincl.php";

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


		function tempa($top, $title)
			{
            global $BAB_SESS_USER, $BAB_SESS_EMAIL, $babSiteName;
            $this->articletitle = $title;
            $this->message = bab_translate("A new article is waiting for you");
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
	
	$tempa = new tempa($top, $title);
	$message = bab_printTemplate($tempa,"mailinfo.html", "articlewait");

    $mail = new babMail();
    $mail->mailTo($approveremail);
    $mail->mailFrom($babAdminEmail, "Ovidentia Administrator");
    $mail->mailSubject(bab_translate("New waiting article"));
    $mail->mailBody($message, "html");
    $mail->send();
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
	$req = "insert into articles (id_topic, id_author, date, title, body, head) values ";
	$req .= "('" .$topics. "', '" . $BAB_SESS_USERID. "', now(), '" . $title. "', '" . $bodytext. "', '" . $headtext. "')";
	$res = $db->db_query($req);
	$id = $db->db_insert_id();

	//##: mail to approver
	$req = "select * from topics where id='$topics'";
	$res = $db->db_query($req);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
        $top = $arr['category'];
		$req = "select * from users where id='".$arr['id_approver']."'";
		$res = $db->db_query($req);
		if( $res && $db->db_num_rows($res) > 0)
			{
			$arr = $db->db_fetch_array($res);
			//$message = bab_translate("A new article is waiting for you"). ":\n". $title ."\n";
            notifyApprover($top, stripslashes($title), $arr['email']);
			//mail ($arr['email'],bab_translate("New waiting article"),$message,"From: ".$babAdminEmail);
			}
		}
	}


function saveArticle($title, $headtext, $bodytext, $topics)
	{
	global $BAB_SESS_USERID, $babBody ;

	if( empty($title))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a title");
		return;
		}
	$db = $GLOBALS['babDB'];

	if(!get_cfg_var("magic_quotes_gpc"))
		{
		$headtext = addslashes($headtext);
		$bodytext = addslashes($bodytext);
		$title = addslashes($title);
		}

	$req = "insert into articles (id_topic, id_author, date, title, body, head) values ";
	$req .= "('" .$topics. "', '" . $BAB_SESS_USERID. "', now(), '" . $title. "', '" . $bodytext. "', '" . $headtext. "')";
	$res = $db->db_query($req);
	$id = $db->db_insert_id();

	$req = "select * from topics where id='$topics'";
	$res = $db->db_query($req);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
        $top = $arr['category'];
		$req = "select * from users where id='".$arr['id_approver']."'";
		$res = $db->db_query($req);
		if( $res && $db->db_num_rows($res) > 0)
			{
			$arr = $db->db_fetch_array($res);
            notifyApprover($top, stripslashes($title), $arr['email']);
			//mail ($arr['email'],bab_translate("New waiting article"),$message,"From: ".$babAdminEmail);
			}
		}
	}

//@@: warn this function is duplicated in waiting.php file 
function updateArticle($topics, $title, $article, $headtext, $bodytext)
	{
	global $babBody;

	if( empty($title))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a title");
		return;
		}

	if(get_cfg_var("magic_quotes_gpc"))
		{
		$headtext = stripslashes($headtext);
		$bodytext = stripslashes($bodytext);
		}

	$headtext = addslashes($headtext);
	$bodytext = addslashes($bodytext);
	$db = $GLOBALS['babDB'];
	$req = "update articles set title='$title', head='$headtext', body='$bodytext' where id='$article'";
	$res = $db->db_query($req);

	}


/* main */
if(!isset($idx))
	{
	$idx = "Articles";
	}

if( isset($addarticle))
	{
	saveArticleByFile($filename, $title, $doctag, $introtag, $topics);
	$idx = "Articles";
	}

if( isset($addart) && $addart == "add")
	{
	saveArticle($title, $headtext, $bodytext, $topics);
	$idx = "Articles";
	}

if( isset($action) && $action == "Yes" && bab_isUserApprover($topics))
	{
	bab_confirmDeleteArticle($topics, $article);
	}

if( isset($modify))
	{
	updateArticle($topics, $title, $article, $headtext, $bodytext);
	$idx = "Articles";
	}

$approver = bab_isUserApprover($topics);
switch($idx)
	{
	case "Submit":
		$babBody->title = bab_translate("Submit an article");
		if( bab_isAccessValid("topicssub_groups", $topics) || $approver)
			{
			submitArticle($topics);
			$babBody->addItemMenu("Submit", bab_translate("Submit"), $GLOBALS['babUrlScript']."?tg=articles&idx=Submit&topics=".$topics);
			$babBody->addItemMenu("subfile", bab_translate("File"), $GLOBALS['babUrlScript']."?tg=articles&idx=subfile&topics=".$topics);
			}
		break;

	case "subfile":
		$babBody->title = bab_translate("Submit an article");
		if( bab_isAccessValid("topicssub_groups", $topics) || $approver)
			{
			submitArticleByFile($topics);
			$babBody->addItemMenu("Submit", bab_translate("Submit"), $GLOBALS['babUrlScript']."?tg=articles&idx=Submit&topics=".$topics);
			$babBody->addItemMenu("subfile", bab_translate("File"), $GLOBALS['babUrlScript']."?tg=articles&idx=subfile&topics=".$topics);
			}
		break;

	case "Comments":
		Header("Location: ". $GLOBALS['babUrlScript']."?tg=comments&topics=".$topics."&article=".$article."&newc=".$newc);
		return;

	case "More":
		$babBody->title = bab_getCategoryTitle($topics);
		if( bab_isAccessValid("topicsview_groups", $topics)|| $approver)
			{
			readMore($topics, $article);
			if( bab_isAccessValid("topicssub_groups", $topics) || $approver)
				{
				$babBody->addItemMenu("Articles", bab_translate("Articles"), $GLOBALS['babUrlScript']."?tg=articles&idx=Articles&topics=".$topics."&new=".$new."&newc=".$newc);
				//$babBody->addItemMenu("Comments", bab_translate("Comments"), $GLOBALS['babUrlScript']."?tg=comments&idx=List&topics=".$topics."&article=".$article."&newc=".$newc);
				if( $approver)
					{
					$babBody->addItemMenu("Delete", bab_translate("Delete"), $GLOBALS['babUrlScript']."?tg=articles&idx=Delete&topics=".$topics."&article=".$article."&new=".$new."&newc=".$newc);
					$babBody->addItemMenu("Modify", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=articles&idx=Modify&topics=".$topics."&article=".$article."&new=".$new."&newc=".$newc);
					}
				}
			if( bab_isAccessValid("topicscom_groups", $topics) || $approver)
				{
				$babBody->addItemMenu("Comments", bab_translate("Comments"), $GLOBALS['babUrlScript']."?tg=comments&idx=List&topics=".$topics."&article=".$article."&newc=".$newc);
				}
			$babBody->addItemMenu("Print Friendly", bab_translate("Print Friendly"),$GLOBALS['babUrlScript']."?tg=articles&idx=Print&topics=".$topics."&article=".$article."&new=".$new."&newc=".$newc);
			$babBody->addItemMenuAttributes("Print Friendly", "target=_blank");
			}
		break;

	case "Delete":
		$babBody->title = bab_translate("Delete article");
		if( $approver)
			{
			deleteArticle($topics, $article, $new, $newc);
			$babBody->addItemMenu("Delete", bab_translate("Delete"), $GLOBALS['babUrlScript']."?tg=comments&idx=Delete&topics=".$topics."&article=".$article."&new=".$new."&newc=".$newc);
			}
		break;

	case "Modify":
		$babBody->title = bab_getArticleTitle($article);
		if( $approver)
			{
			modifyArticle($topics, $article);
			$babBody->addItemMenu("Cancel", bab_translate("Cancel"), $GLOBALS['babUrlScript']."?tg=articles&idx=More&topics=".$topics."&article=".$article."&new=".$new."&newc=".$newc);
			$babBody->addItemMenu("Modify", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=articles&idx=Modify&topics=".$topics."&article=".$article."&new=".$new."&newc=".$newc);
			}
		break;

	case "Print":
		if( bab_isAccessValid("topicsview_groups", $topics) || $approver)
			articlePrint($topics, $article);
		exit();
		break;

	default:
	case "Articles":
		$babBody->title = bab_translate("List of articles");
		if( bab_isAccessValid("topicsview_groups", $topics)|| $approver)
			{
			$count = listArticles($topics, $newc);
			if( bab_isAccessValid("topicssub_groups", $topics)|| $approver)
				{
				if( $approver)
					{
					if( isset($new) && $new > 0)
						$babBody->addItemMenu("Waiting", bab_translate("Waiting"), $GLOBALS['babUrlScript']."?tg=waiting&idx=Waiting&topics=".$topics."&new=".$new."&newc=".$newc);
					}
				$babBody->addItemMenu("Submit", bab_translate("Submit"), $GLOBALS['babUrlScript']."?tg=articles&idx=Submit&topics=".$topics);
				}
			if( $count < 1)
				$babBody->title = bab_translate("Today, there is no articles");
			else
				$babBody->title = bab_getCategoryTitle($topics);
			}
		break;
	}
$babBody->setCurrentItemMenu($idx);

?>