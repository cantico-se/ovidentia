<?php
include $babInstallPath."utilit/topincl.php";

function listArticles($topics, $newc)
	{
	global $body;

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
			$this->db = new db_mysql();
			$req = "select * from articles where id_topic='$topics' and confirmed='Y' order by date desc";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			$this->topics = $topics;
			$this->newc = $newc;
			if( isAccessValid("topicscom_groups", $this->topics) || isUserApprover($topics))
				$this->com = true;
			else
				$this->com = false;
			$this->morename = babTranslate("Read More");
			}

		function getnext()
			{
			global $new; 
			static $i = 0;
			if( $i < $this->count)
				{
				$this->arr = $this->db->db_fetch_array($this->res);
				$this->author = babTranslate("by") . " ". getArticleAuthor($this->arr[id]). " ".babTranslate("on")." ". getArticleDate($this->arr[id]);
				$this->content = $this->arr[head];

				if( $this->com)
					{
					$req = "select count(id) as total from comments where id_article='".$this->arr[id]."' and confirmed='Y'";
					$res = $this->db->db_query($req);
					$ar = $this->db->db_fetch_array($res);
					$total = $ar[total];
					$req = "select count(id) as total from comments where id_article='".$this->arr[id]."' and confirmed='N'";
					$res = $this->db->db_query($req);
					$ar = $this->db->db_fetch_array($res);
					$totalw = $ar[total];
					$this->commentsurl = $GLOBALS[babUrl]."index.php?tg=comments&idx=List&topics=".$this->topics."&article=".$this->arr[id];
					if( isset($new) && $new > 0)
						$this->commentsurl .= "&new=".$new;
					$this->commentsurl .= "&newc=".$this->newc;
					if( $totalw > 0 )
						$this->commentsname = babTranslate("Comments")."&nbsp;(".$total."-".$totalw.")";
					else
						$this->commentsname = babTranslate("Comments")."&nbsp;(".$total.")";
					}
				else
					{
					$this->commentsurl = "";
					$this->commentsname = "";
					}

				$this->moreurl = $GLOBALS[babUrl]."index.php?tg=articles&idx=More&topics=".$this->topics."&article=".$this->arr[id];
				if( isset($new) && $new > 0)
					$this->moreurl .= "&new=".$new;

				$this->moreurl .= "&newc=".$this->newc;
				$this->morename = babTranslate("Read more")."...";
				$i++;
				return true;
				}
			else
				return false;
			}
		}
	
	$temp = new temp($topics, $newc);
	$body->babecho(	babPrintTemplate($temp,"articles.html", "introlist"));
	return $temp->count;
	}

function readMore($topics, $article)
	{
	global $body;

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
			$this->db = new db_mysql();
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
				$this->content = $this->arr[body];
				$i++;
				return true;
				}
			else
				return false;
			}
		}
	
	$temp = new temp($topics, $article);
	$body->babecho(	babPrintTemplate($temp,"articles.html", "readmore"));
	}

function submitArticleByFile($topics)
	{
	global $body;
	
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
			$this->title = babTranslate("Title");
			$this->doctag = babTranslate("Document Tag");
			$this->introtag = babTranslate("Introduction Tag");
			$this->filename = babTranslate("Filename");
			$this->add = babTranslate("Add article");
			$this->topics = $topics;
			$this->maxupload = $babMaxUpload;
			}
		}

	$temp = new temp($topics);
	$body->babecho(	babPrintTemplate($temp,"articles.html", "articlecreatebyfile"));
	}

function deleteArticle($topics, $article, $new, $newc)
	{
	global $body;
	
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
			$this->message = babTranslate("Are you sure you want to delete the article");
			$this->title = getArticleTitle($article);
			$this->warning = babTranslate("WARNING: This operation will delete the article with all its comments"). "!";
			$this->urlyes = $GLOBALS[babUrl]."index.php?tg=articles&idx=Articles&topics=".$topics."&article=".$article."&action=Yes";
			$this->yes = babTranslate("Yes");
			$this->urlno =$GLOBALS[babUrl]."index.php?tg=articles&idx=More&topics=".$topics."&article=".$article."&new=".$new."&newc=".$newc;
			$this->no = babTranslate("No");
			}
		}

	$temp = new temp($topics, $article, $new, $newc);
	$body->babecho(	babPrintTemplate($temp,"warning.html", "warningyesno"));
	}

//##: warn this fucntion is duplicated in waiting.php file 
function modifyArticle($topics, $article)
	{
	global $body;

	class temp
		{
	
		var $head;
		var $title;
		var $titleval;
		var $headval;
		var $body;
		var $bodyval;
		var $modify;
		var $topics;
		var $article;
		var $arr = array();
		var $db;
		var $count;
		var $res;

		function temp($topics, $article)
			{
			$this->article = $article;
			$this->topics = $topics;
			$this->head = babTranslate("Head");
			$this->body = babTranslate("Body");
			$this->title = babTranslate("Title");
			$this->modify = babTranslate("Modify");
			$this->db = new db_mysql();
			$req = "select * from articles where id='$article'";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			if( $this->count > 0)
				{
				$this->arr = $this->db->db_fetch_array($this->res);
				$this->headval = htmlentities($this->arr[head]);
				$this->bodyval = htmlentities($this->arr[body]);
				$this->titleval = $this->arr[title];
				}
			}
		}
	
	$temp = new temp($topics, $article);
	$body->babecho(	babPrintTemplate($temp,"articles.html", "modifyarticle"));
	}

function submitArticle($topics)
	{
	global $body;

	class temp
		{
	
		var $head;
		var $body;
		var $modify;
		var $topics;
		var $title;

		function temp($topics)
			{
			$this->topics = $topics;
			$this->head = babTranslate("Head");
			$this->body = babTranslate("Body");
			$this->title = babTranslate("Title");
			$this->modify = babTranslate("Add Article");
			}
		}
	
	$temp = new temp($topics);
	$body->babecho(	babPrintTemplate($temp,"articles.html", "createarticle"));
	}

function articlePrint($topics, $article)
	{
	global $body;

	class temp
		{
	
		var $content;
		var $title;
		var $url;

		function temp($topics, $article)
			{
			$this->db = new db_mysql();
			$req = "select * from articles where id='$article'";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			$this->topics = $topics;
			if( $this->count > 0 )
				{
				$this->arr = $this->db->db_fetch_array($this->res);
				$this->content = $this->arr[body];
				$this->title = getArticleTitle($this->arr[id]);
				$this->url = "<a href=\"$GLOBALS[babUrl]\">".$GLOBALS[babSiteName]."</a>";
				}
			}
		}
	
	$temp = new temp($topics, $article);
	echo babPrintTemplate($temp,"articleprint.html");
	}

function notifyApprover($top, $title, $approveremail)
	{
	global $body, $BAB_SESS_USER, $BAB_SESS_EMAIL, $babAdminEmail, $babInstallPath;
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
            $this->message = babTranslate("A new article is waiting for you");
            $this->from = babTranslate("Author");
            $this->category = babTranslate("Topic");
            $this->title = babTranslate("Title");
            $this->categoryname = $top;
            $this->site = babTranslate("Web site");
            $this->sitename = $babSiteName;
            $this->date = babTranslate("Date");
            $this->dateval = bab_strftime(mktime());
            if( !empty($BAB_SESS_USER))
                $this->author = $BAB_SESS_USER;
            else
                $this->author = babTranslate("Unknown user");

            if( !empty($BAB_SESS_EMAIL))
                $this->authoremail = $BAB_SESS_EMAIL;
            else
                $this->authoremail = "";
			}
		}
	
	$tempa = new tempa($top, $title);
	$message = babPrintTemplate($tempa,"mailinfo.html", "articlewait");

    $mail = new babMail();
    $mail->mailTo($approveremail);
    $mail->mailFrom($babAdminEmail, "Ovidentia Administrator");
    $mail->mailSubject(babTranslate("New waiting article"));
    $mail->mailBody($message, "html");
    $mail->send();
	}

function saveArticleByFile($filename, $title, $doctag, $introtag, $topics)
	{
	global $BAB_SESS_USERID, $body , $babAdminEmail;

	if( $filename == "none")
		{
		$body->msgerror = babTranslate("ERROR: You must provide a file name");
		return;
		}

	if( empty($title))
		{
		$body->msgerror = babTranslate("ERROR: You must provide a title");
		return;
		}

	$tp = new Template();
	$headtext = $tp->printTemplate(none, $filename, $introtag);
	$bodytext = $tp->printTemplate(none, $filename, $doctag);

	$headtext = addslashes($headtext);
	$bodytext = addslashes($bodytext);

	$db = new db_mysql();
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
        $top = $arr[category];
		$req = "select * from users where id='$arr[id_approver]'";
		$res = $db->db_query($req);
		if( $res && $db->db_num_rows($res) > 0)
			{
			$arr = $db->db_fetch_array($res);
			//$message = babTranslate("A new article is waiting for you"). ":\n". $title ."\n";
            notifyApprover($top, $title, $arr[email]);
			//mail ($arr[email],babTranslate("New waiting article"),$message,"From: ".$babAdminEmail);
			}
		}
	}


function saveArticle($title, $headtext, $bodytext, $topics)
	{
	global $BAB_SESS_USERID, $body ;

	if( empty($title))
		{
		$body->msgerror = babTranslate("ERROR: You must provide a title");
		return;
		}

	if(get_cfg_var("magic_quotes_gpc"))
		{
		$headtext = stripslashes($headtext);
		$bodytext = stripslashes($bodytext);
		}

	$headtext = addslashes($headtext);
	$bodytext = addslashes($bodytext);

	$db = new db_mysql();
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
        $top = $arr[category];
		$req = "select * from users where id='$arr[id_approver]'";
		$res = $db->db_query($req);
		if( $res && $db->db_num_rows($res) > 0)
			{
			$arr = $db->db_fetch_array($res);
            notifyApprover($top, $title, $arr[email]);
			//mail ($arr[email],babTranslate("New waiting article"),$message,"From: ".$babAdminEmail);
			}
		}
	}

//##: warn this function is duplicated in waiting.php file 
function updateArticle($topics, $title, $article, $headtext, $bodytext)
	{
	global $body;

	if( empty($title))
		{
		$body->msgerror = babTranslate("ERROR: You must provide a title");
		return;
		}

	if(get_cfg_var("magic_quotes_gpc"))
		{
		$headtext = stripslashes($headtext);
		$bodytext = stripslashes($bodytext);
		}

	$headtext = addslashes($headtext);
	$bodytext = addslashes($bodytext);
	$db = new db_mysql();
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

if( isset($action) && $action == "Yes" && isUserApprover($topics))
	{
	confirmDeleteArticle($topics, $article);
	}

if( isset($modify))
	{
	updateArticle($topics, $title, $article, $headtext, $bodytext);
	$idx = "Articles";
	}

$approver = isUserApprover($topics);
switch($idx)
	{
	case "Submit":
		$body->title = babTranslate("Submit an article");
		if( isAccessValid("topicssub_groups", $topics) || $approver)
			{
			submitArticle($topics);
			$body->addItemMenu("Submit", babTranslate("Submit"), $GLOBALS[babUrl]."index.php?tg=articles&idx=Submit&topics=".$topics);
			$body->addItemMenu("subfile", babTranslate("File"), $GLOBALS[babUrl]."index.php?tg=articles&idx=subfile&topics=".$topics);
			}
		break;

	case "subfile":
		$body->title = babTranslate("Submit an article");
		if( isAccessValid("topicssub_groups", $topics) || $approver)
			{
			submitArticleByFile($topics);
			$body->addItemMenu("Submit", babTranslate("Submit"), $GLOBALS[babUrl]."index.php?tg=articles&idx=Submit&topics=".$topics);
			$body->addItemMenu("subfile", babTranslate("File"), $GLOBALS[babUrl]."index.php?tg=articles&idx=subfile&topics=".$topics);
			}
		break;

	case "Comments":
		Header("Location: index.php?tg=comments&topics=".$topics."&article=".$article."&newc=".$newc);
		return;

	case "More":
		$body->title = getCategoryTitle($topics);
		if( isAccessValid("topicsview_groups", $topics)|| $approver)
			{
			readMore($topics, $article);
			if( isAccessValid("topicssub_groups", $topics) || $approver)
				{
				$body->addItemMenu("Articles", babTranslate("Articles"), $GLOBALS[babUrl]."index.php?tg=articles&idx=Articles&topics=".$topics."&new=".$new."&newc=".$newc);
				//$body->addItemMenu("Comments", babTranslate("Comments"), $GLOBALS[babUrl]."index.php?tg=comments&idx=List&topics=".$topics."&article=".$article."&newc=".$newc);
				if( $approver)
					{
					$body->addItemMenu("Delete", babTranslate("Delete"), $GLOBALS[babUrl]."index.php?tg=articles&idx=Delete&topics=".$topics."&article=".$article."&new=".$new."&newc=".$newc);
					$body->addItemMenu("Modify", babTranslate("Modify"), $GLOBALS[babUrl]."index.php?tg=articles&idx=Modify&topics=".$topics."&article=".$article."&new=".$new."&newc=".$newc);
					}
				}
			if( isAccessValid("topicscom_groups", $topics) || $approver)
				{
				$body->addItemMenu("Comments", babTranslate("Comments"), $GLOBALS[babUrl]."index.php?tg=comments&idx=List&topics=".$topics."&article=".$article."&newc=".$newc);
				}
			$body->addItemMenu("Print Friendly", babTranslate("Print Friendly"),$GLOBALS[babUrl]."index.php?tg=articles&idx=Print&topics=".$topics."&article=".$article."&new=".$new."&newc=".$newc);
			$body->addItemMenuAttributes("Print Friendly", "target=_blank");
			}
		break;

	case "Delete":
		$body->title = babTranslate("Delete article");
		if( $approver)
			{
			deleteArticle($topics, $article, $new, $newc);
			$body->addItemMenu("Delete", babTranslate("Delete"), $GLOBALS[babUrl]."index.php?tg=comments&idx=Delete&topics=".$topics."&article=".$article."&new=".$new."&newc=".$newc);
			}
		break;

	case "Modify":
		$body->title = getArticleTitle($article);
		if( $approver)
			{
			modifyArticle($topics, $article);
			$body->addItemMenu("Cancel", babTranslate("Cancel"), $GLOBALS[babUrl]."index.php?tg=articles&idx=More&topics=".$topics."&article=".$article."&new=".$new."&newc=".$newc);
			$body->addItemMenu("Modify", babTranslate("Modify"), $GLOBALS[babUrl]."index.php?tg=articles&idx=Modify&topics=".$topics."&article=".$article."&new=".$new."&newc=".$newc);
			}
		break;

	case "Print":
		if( isAccessValid("topicsview_groups", $topics) || $approver)
			articlePrint($topics, $article);
		exit();
		break;

	default:
	case "Articles":
		$body->title = babTranslate("List of articles");
		if( isAccessValid("topicsview_groups", $topics)|| $approver)
			{
			$count = listArticles($topics, $newc);
			if( isAccessValid("topicssub_groups", $topics)|| $approver)
				{
				if( $approver)
					{
					if( isset($new) && $new > 0)
						$body->addItemMenu("Waiting", babTranslate("Waiting"), $GLOBALS[babUrl]."index.php?tg=waiting&idx=Waiting&topics=".$topics."&new=".$new."&newc=".$newc);
					}
				$body->addItemMenu("Submit", babTranslate("Submit"), $GLOBALS[babUrl]."index.php?tg=articles&idx=Submit&topics=".$topics);
				}
			if( $count < 1)
				$body->title = babTranslate("Today, there is no articles");
			else
				$body->title = getCategoryTitle($topics);
			}
		break;
	}
$body->setCurrentItemMenu($idx);

?>