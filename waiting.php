<?php
include $babInstallPath."utilit/topincl.php";

function listArticles($topics)
	{
	global $body;

	class temp
		{
	
		var $content;
		var $arr = array();
		var $db;
		var $count;
		var $res;
		var $moreurl;
		var $morename;
		var $topics;

		function temp($topics)
			{
			$this->db = new db_mysql();
			$req = "select * from articles where id_topic='$topics' and confirmed='N'";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			$this->topics = $topics;
			}

		function getnext()
			{
			global $new; 
			static $i = 0;
			if( $i < $this->count)
				{
				$this->arr = $this->db->db_fetch_array($this->res);
				$this->content = $this->arr[head];
				//$this->more = "<a class=BabMenuLink href=\"".$GLOBALS[babUrl]."index.php?tg=waiting&idx=More&topics=".$this->topics."&article=".$this->arr[id]."\">".babTranslate("Read more")."...</a>";
				$this->moreurl = $GLOBALS[babUrl]."index.php?tg=waiting&idx=More&topics=".$this->topics."&article=".$this->arr[id];
				if( isset($new) && $new > 0)
					$this->more .= "&new=".$new;

				$this->morename = babTranslate("Read more")."...";
				$i++;
				return true;
				}
			else
				return false;
			}
		}
	
	$temp = new temp($topics);
	$body->babecho(	babPrintTemplate($temp,"waiting.html", "introlist"));
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
			$req = "select * from articles where id='$article' and confirmed='N'";
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
	$body->babecho(	babPrintTemplate($temp,"waiting.html", "readmore"));
	}

function modifyArticle($topics, $article)
	{
	global $body;

	class temp
		{
	
		var $head;
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
			$this->modify = babTranslate("Modify");
			$this->db = new db_mysql();
			$req = "select * from articles where id='$article' and confirmed='N'";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			if( $this->count > 0)
				{
				$this->arr = $this->db->db_fetch_array($this->res);
				$this->headval = htmlentities($this->arr[head]);
				$this->bodyval = htmlentities($this->arr[body]);
				}
			}
		}
	
	$temp = new temp($topics, $article);
	$body->babecho(	babPrintTemplate($temp,"waiting.html", "modifyarticle"));
	}

function confirmArticle($article, $topics)
	{
	global $body;

	class temp
		{
		var $name;
		var $nameval;
		var $action;
		var $confirm;
		var $refuse;
		var $what;
		var $new;
		var $message;
		var $modify;
		var $topics;
		var $article;
		var $fullname;
		var $author;
		var $db;
		var $count;
		var $res;
		var $confval;
		var $idxval;


		function temp($topics, $article)
			{
			global $new;
			$this->article = $article;
			$this->topics = $topics;
			$this->new = $new;
			$this->name = babTranslate("Submiter");
			$this->modify = babTranslate("Update");
			$this->action = babTranslate("Action");
			$this->confirm = babTranslate("Confirm");
			$this->refuse = babTranslate("Refuse");
			$this->what = babTranslate("Send an email to submiter");
			$this->message = babTranslate("Message");
			$this->confval = "article";
			$this->idxval = "Waiting";

			$this->db = new db_mysql();
			$req = "select * from articles where id='$article'";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			if( $this->count > 0)
				{
				$arr = $this->db->db_fetch_array($this->res);
				$req = "select * from users where id='".$arr[id_author]."'";
				$this->res = $this->db->db_query($req);
				$arr2 = $this->db->db_fetch_array($this->res);
				$this->fullname = $arr2[fullname];
				$this->author = $arr[id_author];
				}
			}
		}
	
	$temp = new temp($topics, $article);
	$body->babecho(	babPrintTemplate($temp,"waiting.html", "confirmarticle"));
	}

function listWaitingComments($topics, $article, $newc)
	{
	global $body;

	class temp
		{
	
		var $subjecturl;
		var $subjectval;
		var $arr = array();
		var $db;
		var $count;
		var $res;
		var $topics;
		var $article;
		var $newc;
		var $alternate;

		function temp($topics, $article, $newc)
			{
			$this->db = new db_mysql();
			$req = "select * from comments where id_article='$article' and confirmed='N'";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			$this->topics = $topics;
			$this->article = $article;
			$this->newc = $newc;
			$this->alternate = 0;
			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				if( $this->alternate == 0)
					$this->alternate = 1;
				else
					$this->alternate = 0;
				$this->arr = $this->db->db_fetch_array($this->res);
				$this->arr[date] = bab_strftime(bab_mktime($this->arr[date]));
				$this->subjecturl = $GLOBALS[babUrl]."index.php?tg=waiting&idx=ReadC&topics=".$this->topics."&article=".$this->article."&com=".$this->arr[id]."&newc=".$this->newc;
				$this->subjectname = $this->arr[subject];
				$i++;
				return true;
				}
			else
				return false;
			}
		}
	
	$temp = new temp($topics, $article, $newc);
	$body->babecho(	babPrintTemplate($temp,"comments.html", "commentslist"));
	return $temp->count;
	}

function readComment($topics, $article, $com)
	{
	global $body;
	
	class ctp
		{
		var $subject;
		var $add;
		var $topics;
		var $article;
		var $arr = array();

		function ctp($topics, $article, $com)
			{
			$this->subject = babTranslate("Subject");
			$this->by = babTranslate("By");
			$this->date = babTranslate("Date");
			$this->topics = $topics;
			$this->article = $article;
			$db = new db_mysql();
			$req = "select * from comments where id='$com'";
			$res = $db->db_query($req);
			$this->arr = $db->db_fetch_array($res);
			$this->arr[date] = bab_strftime(bab_mktime($this->arr[date]));
			}
		}

	$ctp = new ctp($topics, $article, $com);
	$body->babecho(	babPrintTemplate($ctp,"comments.html", "commentread"));
	}


function confirmComment($article, $topics, $com, $newc)
	{
	global $body;

	class temp
		{
		var $name;
		var $nameval;
		var $action;
		var $confirm;
		var $refuse;
		var $what;
		var $com;
		var $new;
		var $message;
		var $modify;
		var $topics;
		var $article;
		var $fullname;
		var $author;
		var $db;
		var $count;
		var $res;
		var $confval;
		var $idxval;

		function temp($topics, $article, $com, $newc)
			{
			$this->article = $article;
			$this->topics = $topics;
			$this->new = $newc;
			$this->com = $com;
			$this->name = babTranslate("Submiter");
			$this->modify = babTranslate("Update");
			$this->action = babTranslate("Action");
			$this->confirm = babTranslate("Confirm");
			$this->refuse = babTranslate("Refuse");
			$this->what = babTranslate("Send an email to submiter");
			$this->message = babTranslate("Message");
			$this->confval = "comment";
			$this->idxval = "WaitingC";

			$this->db = new db_mysql();
			$req = "select * from comments where id='$com'";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			if( $this->count > 0)
				{
				$arr = $this->db->db_fetch_array($this->res);
				$this->fullname = $arr[name];
				$this->author = $arr[name];
				}
			}
		}
	
	$temp = new temp($topics, $article, $com, $newc);
	$body->babecho(	babPrintTemplate($temp,"waiting.html", "confirmarticle"));
	}

function updateConfirmArticle($topics, $article, $action, $send, $author, $message)
	{
	global $body, $new;

	$db = new db_mysql();
	$query = "select * from articles where id='$article'";
	$res = $db->db_query($query);
	$arr = $db->db_fetch_array($res);
	$filename = $arr[filename];
	$title = $arr[title];

	$query = "select * from users where id='$author'";
	$res = $db->db_query($query);
	$arr = $db->db_fetch_array($res);

	$query = "select * from topics where id='$topics'";
	$res = $db->db_query($query);
	$arr2 = $db->db_fetch_array($res);

	$query = "select * from users where id='$arr2[id_approver]'";
	$res = $db->db_query($query);
	$arr2 = $db->db_fetch_array($res);

	if( $action == "1")
		{
		$query = "update articles set confirmed='Y' where id = '$article'";
		$subject = babTranslate("Your article has been accepted");
		}
	else
		{
		$query = "delete from articles where id = '$article'";
		$subject = babTranslate("Your article has been refused");
		}
	$res = $db->db_query($query);

	if( $send == "1")
		{
		$msg = nl2br($message);
		if(get_cfg_var("magic_quotes_gpc"))
			$msg = stripslashes($msg);
		mail ($arr[email],$subject,$title . "\n". $msg,"From: ".$arr2[email]);
		}

	$new--;
	if( $new < 1)
		Header("Location: index.php?tg=articles&topics=".$topics);
	}


function updateArticle($topics, $article, $headtext, $bodytext)
	{
	global $body;

	$db = new db_mysql();
	if(get_cfg_var("magic_quotes_gpc"))
		{
		$headtext = stripslashes($headtext);
		$bodytext = stripslashes($bodytext);
		}

	$headtext = addslashes($headtext);
	$bodytext = addslashes($bodytext);
	$db = new db_mysql();
	$req = "update articles set head='$headtext', body='$bodytext' where id='$article'";
	$res = $db->db_query($req);		
	}


function updateConfirmComment($topics, $article, $action, $send, $author, $message, $com, $newc)
	{
	global $body, $new;

	$db = new db_mysql();
	$query = "select * from comments where id='$com'";
	$res = $db->db_query($query);
	$arr = $db->db_fetch_array($res);

	if( $action == "1")
		{
		$query = "update comments set confirmed='Y' where id = '$com'";
		}
	else
		{
		$query = "delete from comments where id = '$com'";
		}
	$res = $db->db_query($query);

	if( $send == "1")
		{
		$msg = nl2br($message);
		if(get_cfg_var("magic_quotes_gpc"))
			$msg = stripslashes($msg);
		mail ($arr[email], babTranslate("About your comment"), $msg,"From: ".$arr2[email]);
		}

	$newc--;
	if( $newc < 1)
		Header("Location: index.php?tg=articles&topics=".$topics);
	}

/* main */
if(!isset($idx))
	{
	$idx = "Waiting";
	}

if( !isUserApprover($topics))
	return;

if( isset($modify))
	{
	updateArticle($topics, $article, $headtext, $bodytext);
	}

if( isset($confirm) )
	{
	if($confirm == "article")
		updateConfirmArticle($topics, $article, $action, $send, $author, $message);
	if($confirm == "comment")
		updateConfirmComment($topics, $article, $action, $send, $author, $message, $comment, $new);
	}

switch($idx)
	{
	case "More":
		$body->title = getCategoryTitle($topics);
		readMore($topics, $article);
		$body->addItemMenu("Waiting", babTranslate("Waiting"), $GLOBALS[babUrl]."index.php?tg=waiting&idx=Waiting&topics=".$topics."&new=".$new);
		$body->addItemMenu("Modify", babTranslate("Modify"), $GLOBALS[babUrl]."index.php?tg=waiting&idx=Modify&topics=".$topics."&article=".$article."&new=".$new);
		$body->addItemMenu("Confirm", babTranslate("Confirm"), $GLOBALS[babUrl]."index.php?tg=waiting&idx=Confirm&topics=".$topics."&article=".$article."&new=".$new);
		break;

	case "Modify":
		$body->title = getArticleTitle($article);
		modifyArticle($topics, $article);
		$body->addItemMenu("Waiting", babTranslate("Waiting"), $GLOBALS[babUrl]."index.php?tg=waiting&idx=Waiting&topics=".$topics."&new=".$new);
		$body->addItemMenu("Modify", babTranslate("Modify"), $GLOBALS[babUrl]."index.php?tg=waiting&idx=Modify&topics=".$topics."&article=".$article."&new=".$new);
		$body->addItemMenu("Confirm", babTranslate("Confirm"), $GLOBALS[babUrl]."index.php?tg=waiting&idx=Confirm&topics=".$topics."&article=".$article."&new=".$new);
		break;

	case "Confirm":
		$body->title = getArticleTitle($article);
		confirmArticle($article, $topics);
		$body->addItemMenu("Waiting", babTranslate("Waiting"), $GLOBALS[babUrl]."index.php?tg=waiting&idx=Waiting&topics=".$topics."&new=".$new);
		$body->addItemMenu("Modify", babTranslate("Modify"), $GLOBALS[babUrl]."index.php?tg=waiting&idx=Modify&topics=".$topics."&article=".$article."&new=".$new);
		$body->addItemMenu("Confirm", babTranslate("Confirm"), $GLOBALS[babUrl]."index.php?tg=waiting&idx=Confirm&topics=".$topics."&article=".$article."&new=".$new);
		break;

	case "WaitingC":
		$body->title = babTranslate("Waiting comments");
		$body->addItemMenu("List", babTranslate("List"), $GLOBALS[babUrl]."index.php?tg=comments&idx=List&topics=".$topics."&article=".$article."&newc=".$newc);
		$body->addItemMenu("WaitingC", babTranslate("Waiting"), $GLOBALS[babUrl]."index.php?tg=waiting&idx=WaitingC&topics=".$topics."&article=".$article."&newc=".$newc);
		listWaitingComments($topics, $article, $newc);
		break;

	case "ReadC":
		$body->title = babTranslate("Waiting Comment");
		$body->addItemMenu("WaitingC", babTranslate("Waiting"), $GLOBALS[babUrl]."index.php?tg=waiting&idx=WaitingC&topics=".$topics."&article=".$article."&newc=".$newc);
		$body->addItemMenu("ConfirmC", babTranslate("Confirm"), $GLOBALS[babUrl]."index.php?tg=waiting&idx=ConfirmC&topics=".$topics."&article=".$article."&newc=".$newc."&com=".$com);
		readComment($topics, $article, $com);
		break;

	case "ConfirmC":
		$body->title = babTranslate("Confirm a comment");
		confirmComment($article, $topics, $com, $newc);
		$body->addItemMenu("WaitingC", babTranslate("Waiting"), $GLOBALS[babUrl]."index.php?tg=waiting&idx=WaitingC&topics=".$topics."&article=".$article."&newc=".$newc);
		$body->addItemMenu("ConfirmC", babTranslate("Confirm"), $GLOBALS[babUrl]."index.php?tg=waiting&idx=ConfirmC&topics=".$topics."&article=".$article."&newc=".$newc);
		break;

	default:
	case "Waiting":
		$body->title = getCategoryTitle($topics);
		$body->addItemMenu("Waiting", babTranslate("Waiting"), $GLOBALS[babUrl]."index.php?tg=waiting&idx=Waiting&topics=".$topics."&new=".$new);
		listArticles($topics);
		break;
	}
$body->setCurrentItemMenu($idx);

?>